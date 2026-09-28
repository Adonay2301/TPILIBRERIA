<?php
/**
 * Autores. Administrador: CRUD. Empleado: solo consulta.
 */

class AutorController extends Controller
{
    /** GET /admin/autores */
    public function index(): void
    {
        Auth::requerirRol(Auth::ROLES_PANEL);
        $autores = $this->modelo('Autor');
        $q = mb_substr($this->parametro('q'), 0, 100);
        $nacionalidad = $this->parametro('nacionalidad');

        $this->vista('admin/autores/index', [
            'titulo'         => 'Autores',
            'autores'        => $autores->listar($q, $nacionalidad),
            'nacionalidades' => $autores->nacionalidades(),
            'q'              => $q,
            'nacionalidad'   => $nacionalidad,
            'esAdmin'        => Auth::esAdmin(),
        ], 'admin');
    }

    /** GET /admin/autores/crear */
    public function crear(): void
    {
        Auth::requerirRol(['administrador']);
        $this->vista('admin/autores/formulario', ['titulo' => 'Nuevo autor', 'autor' => null], 'admin');
    }

    /** POST /admin/autores/guardar */
    public function guardar(): void
    {
        Auth::requerirRol(['administrador']);
        $this->validarCsrf();
        $datos = $this->validar(0, '/admin/autores/crear');
        $this->modelo('Autor')->crear($datos);
        flash('success', 'Autor agregado al catálogo.');
        $this->redirigir('/admin/autores');
    }

    /** GET /admin/autores/editar/{id} */
    public function editar(int $id): void
    {
        Auth::requerirRol(['administrador']);
        $autor = $this->modelo('Autor')->obtener($id) ?? throw new HttpError(404);
        $this->vista('admin/autores/formulario', ['titulo' => 'Editar autor', 'autor' => $autor], 'admin');
    }

    /** POST /admin/autores/actualizar/{id} */
    public function actualizar(int $id): void
    {
        Auth::requerirRol(['administrador']);
        $this->validarCsrf();
        $this->modelo('Autor')->obtener($id) ?? throw new HttpError(404);
        $datos = $this->validar($id, "/admin/autores/editar/$id");
        $this->modelo('Autor')->actualizar($id, $datos);
        flash('success', 'Autor actualizado correctamente.');
        $this->redirigir('/admin/autores');
    }

    /** POST /admin/autores/eliminar/{id} */
    public function eliminar(int $id): void
    {
        Auth::requerirRol(['administrador']);
        $this->validarCsrf();
        $ok = $this->modelo('Autor')->eliminar($id);
        flash($ok ? 'success' : 'danger', $ok ? 'Autor eliminado del catálogo.' : 'No es posible eliminar: el autor tiene libros asociados. Reasigna o elimina esos libros primero.');
        $this->redirigir('/admin/autores');
    }

    private function validar(int $id, string $rutaError): array
    {
        $datos = [
            'nombres'      => mb_substr($this->entrada('nombres'), 0, 80),
            'apellidos'    => mb_substr($this->entrada('apellidos'), 0, 80),
            'nacionalidad' => mb_substr($this->entrada('nacionalidad'), 0, 60),
            'biografia'    => $this->entrada('biografia'),
        ];
        $errores = [];
        if ($datos['nombres'] === '') $errores[] = 'Escribe los nombres del autor.';
        if ($datos['apellidos'] === '') $errores[] = 'Escribe los apellidos del autor (o el seudónimo).';
        if (!$errores && $this->modelo('Autor')->existe($datos['nombres'], $datos['apellidos'], $id)) $errores[] = 'Ese autor ya está registrado.';

        if ($errores) {
            $this->recordarEntrada();
            foreach ($errores as $error) flash('danger', $error);
            $this->redirigir($rutaError);
        }
        return $datos;
    }
}
