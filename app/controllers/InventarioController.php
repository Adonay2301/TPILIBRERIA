<?php
/**
 * Control de inventario (libros).
 * Administrador: crea, edita, registra entradas y oculta libros.
 * Empleado: solo lectura.
 */

class InventarioController extends Controller
{
    /** GET /admin/libros */
    public function index(): void
    {
        Auth::requerirRol(Auth::ROLES_PANEL);
        $esAdmin = Auth::esAdmin();

        $visibilidad = $esAdmin ? $this->parametro('visibilidad') : '';
        $filtros = [
            'q'              => mb_substr($this->parametro('q'), 0, 100),
            'categorias'     => $this->parametroEnteros('categoria'),
            'editoriales'    => $this->parametroEnteros('editorial'),
            'disponibilidad' => in_array($this->parametro('disponibilidad'), ['disponibles', 'bajas', 'agotados'], true) ? $this->parametro('disponibilidad') : '',
            'solo_activos'   => $visibilidad !== 'todos',
        ];

        $libros = $this->modelo('Libro');
        $paginacion = paginar($libros->contar($filtros), POR_PAGINA_ADMIN);

        $this->vista('admin/libros/index', [
            'titulo'      => 'Control de inventario',
            'libros'      => $libros->buscar($filtros, 'titulo-az', $paginacion['por_pagina'], $paginacion['offset']),
            'filtros'     => $filtros,
            'visibilidad' => $visibilidad,
            'paginacion'  => $paginacion,
            'stats'       => $libros->estadisticasInventario(),
            'categorias'  => $this->modelo('Categoria')->opciones(),
            'editoriales' => $this->modelo('Editorial')->opciones(),
            'esAdmin'     => $esAdmin,
        ], 'admin');
    }

    /** GET /admin/libros/crear */
    public function crear(): void
    {
        Auth::requerirRol(['administrador']);
        $this->formulario(null);
    }

    /** POST /admin/libros/guardar */
    public function guardar(): void
    {
        Auth::requerirRol(['administrador']);
        $this->validarCsrf();

        [$datos, $autores, $errores] = $this->validar();
        $stockInicial = max(0, (int) $this->entrada('stock_inicial', '0'));

        if (!$errores) {
            try {
                $datos['portada'] = guardarPortada($_FILES['portada'] ?? null);
            } catch (DomainException $e) {
                $errores[] = $e->getMessage();
            }
        }
        if ($errores) {
            $this->regresarConErrores($errores, '/admin/libros/crear');
        }

        $this->modelo('Libro')->crear($datos, $autores, $stockInicial, Auth::id());
        flash('success', 'Libro agregado al catálogo.');
        $this->redirigir('/admin/libros');
    }

    /** GET /admin/libros/editar/{id} */
    public function editar(int $id): void
    {
        Auth::requerirRol(['administrador']);
        $libro = $this->modelo('Libro')->obtener($id, false);
        if (!$libro) {
            throw new HttpError(404);
        }
        $this->formulario($libro);
    }

    /** POST /admin/libros/actualizar/{id} */
    public function actualizar(int $id): void
    {
        Auth::requerirRol(['administrador']);
        $this->validarCsrf();

        $modelo = $this->modelo('Libro');
        if (!$modelo->obtener($id, false)) {
            throw new HttpError(404);
        }

        [$datos, $autores, $errores] = $this->validar($id);
        if (!$errores) {
            try {
                $datos['portada'] = guardarPortada($_FILES['portada'] ?? null); // null = conserva la actual
            } catch (DomainException $e) {
                $errores[] = $e->getMessage();
            }
        }
        if ($errores) {
            $this->regresarConErrores($errores, "/admin/libros/editar/$id");
        }

        $modelo->actualizar($id, $datos, $autores);
        flash('success', 'Cambios guardados.');
        $this->redirigir('/admin/libros');
    }

    /** POST /admin/libros/entrada/{id} — registra unidades recibidas */
    public function registrarEntrada(int $id): void
    {
        Auth::requerirRol(['administrador']);
        $this->validarCsrf();

        $cantidad = (int) $this->entrada('cantidad');
        if ($cantidad < 1 || $cantidad > 10000) {
            flash('danger', 'La cantidad debe ser un número entre 1 y 10,000.');
            $this->volver('/admin/libros');
        }
        if (!$this->modelo('Libro')->obtener($id, false)) {
            throw new HttpError(404);
        }

        $this->modelo('MovimientoInventario')->registrarEntrada($id, $cantidad, Auth::id(), mb_substr($this->entrada('observacion'), 0, 255));
        flash('success', "Inventario actualizado: +$cantidad unidades.");
        $this->volver('/admin/libros');
    }

    /** POST /admin/libros/ocultar/{id} — oculta o vuelve a mostrar en el catálogo */
    public function ocultar(int $id): void
    {
        Auth::requerirRol(['administrador']);
        $this->validarCsrf();

        $mostrar = $this->entrada('activo') === '1';
        $this->modelo('Libro')->cambiarActivo($id, $mostrar);
        flash('success', $mostrar ? 'El libro vuelve a mostrarse en el catálogo.' : 'El libro se ocultó del catálogo. Su historial se conserva.');
        $this->volver('/admin/libros');
    }

    // -----------------------------------------------------------------

    private function formulario(?array $libro): void
    {
        $this->vista('admin/libros/formulario', [
            'titulo'      => $libro ? 'Editar libro' : 'Nuevo libro',
            'libro'       => $libro,
            'autoresLibro' => $libro ? $this->modelo('Libro')->idsAutores((int) $libro['id_libro']) : [],
            'autores'     => $this->modelo('Autor')->opciones(),
            'categorias'  => $this->modelo('Categoria')->opciones(),
            'editoriales' => $this->modelo('Editorial')->opciones(),
        ], 'admin');
    }

    /** Valida el formulario. Devuelve [datos, autores, errores]. */
    private function validar(int $idLibro = 0): array
    {
        $isbn = preg_replace('/[^0-9]/', '', $this->entrada('isbn'));
        $autores = array_values(array_unique(array_filter(array_map('intval', (array) ($_POST['autores'] ?? [])))));
        $anio = $this->entrada('anio_publicacion');
        $paginas = $this->entrada('numero_paginas');

        $datos = [
            'isbn'             => $isbn,
            'titulo'           => mb_substr($this->entrada('titulo'), 0, 200),
            'sinopsis'         => $this->entrada('sinopsis') ?: null,
            'id_categoria'     => (int) $this->entrada('id_categoria'),
            'id_editorial'     => (int) $this->entrada('id_editorial'),
            'anio_publicacion' => $anio !== '' ? (int) $anio : null,
            'numero_paginas'   => $paginas !== '' ? (int) $paginas : null,
            'idioma'           => mb_substr($this->entrada('idioma', 'Español'), 0, 30) ?: 'Español',
            'precio'           => round((float) $this->entrada('precio'), 2),
            'stock_minimo'     => max(0, (int) $this->entrada('stock_minimo', '5')),
            'es_novedad'       => $this->entrada('es_novedad') === '1' ? 1 : 0,
            'activo'           => $this->entrada('activo', '1') === '1' ? 1 : 0,
            'portada'          => null,
        ];

        $errores = [];
        if ($datos['titulo'] === '') $errores[] = 'El título es obligatorio.';
        if (!preg_match('/^97[89]\d{10}$/', $isbn)) {
            $errores[] = 'El ISBN debe tener 13 dígitos y empezar con 978 o 979.';
        } elseif ($this->modelo('Libro')->isbnExiste($isbn, $idLibro)) {
            $errores[] = 'Ya existe un libro con ese ISBN.';
        }
        $idsAutores = array_column($this->modelo('Autor')->opciones(), 'id');
        if (!$autores || array_diff($autores, $idsAutores)) $errores[] = 'Selecciona al menos un autor.';
        if (!in_array($datos['id_categoria'], array_column($this->modelo('Categoria')->opciones(), 'id'))) $errores[] = 'Selecciona una categoría.';
        if (!in_array($datos['id_editorial'], array_column($this->modelo('Editorial')->opciones(), 'id'))) $errores[] = 'Selecciona una editorial.';
        if ($datos['precio'] <= 0 || $datos['precio'] > 9999) $errores[] = 'El precio debe ser mayor que $0.00.';
        if ($datos['anio_publicacion'] !== null && ($datos['anio_publicacion'] < 1000 || $datos['anio_publicacion'] > (int) date('Y') + 1)) $errores[] = 'El año de publicación no es válido.';
        if ($datos['numero_paginas'] !== null && ($datos['numero_paginas'] < 1 || $datos['numero_paginas'] > 20000)) $errores[] = 'El número de páginas no es válido.';

        return [$datos, $autores, $errores];
    }

    private function regresarConErrores(array $errores, string $ruta): never
    {
        $this->recordarEntrada();
        $_SESSION['old']['autores'] = implode(',', array_map('intval', (array) ($_POST['autores'] ?? [])));
        foreach ($errores as $error) {
            flash('danger', $error);
        }
        $this->redirigir($ruta);
    }
}
