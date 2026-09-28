<?php
/**
 * Categorías. Administrador: CRUD. Empleado: solo consulta.
 */

class CategoriaController extends Controller
{
    /** GET /admin/categorias */
    public function index(): void
    {
        Auth::requerirRol(Auth::ROLES_PANEL);
        $q = mb_substr($this->parametro('q'), 0, 60);
        $this->vista('admin/categorias/index', [
            'titulo'     => 'Categorías',
            'categorias' => $this->modelo('Categoria')->listar($q),
            'q'          => $q,
            'esAdmin'    => Auth::esAdmin(),
        ], 'admin');
    }

    /** GET /admin/categorias/crear */
    public function crear(): void
    {
        Auth::requerirRol(['administrador']);
        $this->vista('admin/categorias/formulario', ['titulo' => 'Nueva categoría', 'categoria' => null], 'admin');
    }

    /** POST /admin/categorias/guardar */
    public function guardar(): void
    {
        Auth::requerirRol(['administrador']);
        $this->validarCsrf();
        $this->modelo('Categoria')->crear($this->validar(0, '/admin/categorias/crear'));
        flash('success', 'Categoría guardada correctamente.');
        $this->redirigir('/admin/categorias');
    }

    /** GET /admin/categorias/editar/{id} */
    public function editar(int $id): void
    {
        Auth::requerirRol(['administrador']);
        $categoria = $this->modelo('Categoria')->obtener($id) ?? throw new HttpError(404);
        $this->vista('admin/categorias/formulario', ['titulo' => 'Editar categoría', 'categoria' => $categoria], 'admin');
    }

    /** POST /admin/categorias/actualizar/{id} */
    public function actualizar(int $id): void
    {
        Auth::requerirRol(['administrador']);
        $this->validarCsrf();
        $this->modelo('Categoria')->obtener($id) ?? throw new HttpError(404);
        $this->modelo('Categoria')->actualizar($id, $this->validar($id, "/admin/categorias/editar/$id"));
        flash('success', 'Categoría actualizada.');
        $this->redirigir('/admin/categorias');
    }

    /** POST /admin/categorias/eliminar/{id} */
    public function eliminar(int $id): void
    {
        Auth::requerirRol(['administrador']);
        $this->validarCsrf();
        $ok = $this->modelo('Categoria')->eliminar($id);
        flash($ok ? 'success' : 'danger', $ok ? 'Categoría eliminada.' : 'No es posible eliminar: la categoría tiene libros.');
        $this->redirigir('/admin/categorias');
    }

    private function validar(int $id, string $rutaError): array
    {
        $datos = [
            'nombre'      => mb_substr($this->entrada('nombre'), 0, 60),
            'descripcion' => mb_substr($this->entrada('descripcion'), 0, 255),
        ];
        $error = match (true) {
            $datos['nombre'] === '' => 'Escribe el nombre de la categoría.',
            $this->modelo('Categoria')->nombreExiste($datos['nombre'], $id) => 'Ya existe una categoría con ese nombre.',
            default => null,
        };
        if ($error) {
            $this->recordarEntrada();
            flash('danger', $error);
            $this->redirigir($rutaError);
        }
        return $datos;
    }
}
