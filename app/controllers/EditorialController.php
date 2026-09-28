<?php
/**
 * Editoriales. Administrador: CRUD. Empleado: solo consulta.
 */

class EditorialController extends Controller
{
    /** GET /admin/editoriales */
    public function index(): void
    {
        Auth::requerirRol(Auth::ROLES_PANEL);
        $q = mb_substr($this->parametro('q'), 0, 100);
        $this->vista('admin/editoriales/index', [
            'titulo'      => 'Editoriales',
            'editoriales' => $this->modelo('Editorial')->listar($q),
            'q'           => $q,
            'esAdmin'     => Auth::esAdmin(),
        ], 'admin');
    }

    /** GET /admin/editoriales/crear */
    public function crear(): void
    {
        Auth::requerirRol(['administrador']);
        $this->vista('admin/editoriales/formulario', ['titulo' => 'Nueva editorial', 'editorial' => null], 'admin');
    }

    /** POST /admin/editoriales/guardar */
    public function guardar(): void
    {
        Auth::requerirRol(['administrador']);
        $this->validarCsrf();
        $this->modelo('Editorial')->crear($this->validar(0, '/admin/editoriales/crear'));
        flash('success', 'Editorial guardada correctamente.');
        $this->redirigir('/admin/editoriales');
    }

    /** GET /admin/editoriales/editar/{id} */
    public function editar(int $id): void
    {
        Auth::requerirRol(['administrador']);
        $editorial = $this->modelo('Editorial')->obtener($id) ?? throw new HttpError(404);
        $this->vista('admin/editoriales/formulario', ['titulo' => 'Editar editorial', 'editorial' => $editorial], 'admin');
    }

    /** POST /admin/editoriales/actualizar/{id} */
    public function actualizar(int $id): void
    {
        Auth::requerirRol(['administrador']);
        $this->validarCsrf();
        $this->modelo('Editorial')->obtener($id) ?? throw new HttpError(404);
        $this->modelo('Editorial')->actualizar($id, $this->validar($id, "/admin/editoriales/editar/$id"));
        flash('success', 'Editorial actualizada.');
        $this->redirigir('/admin/editoriales');
    }

    /** POST /admin/editoriales/eliminar/{id} */
    public function eliminar(int $id): void
    {
        Auth::requerirRol(['administrador']);
        $this->validarCsrf();
        $ok = $this->modelo('Editorial')->eliminar($id);
        flash($ok ? 'success' : 'danger', $ok ? 'Editorial eliminada.' : 'No es posible eliminar: la editorial tiene libros.');
        $this->redirigir('/admin/editoriales');
    }

    private function validar(int $id, string $rutaError): array
    {
        $datos = [
            'nombre' => mb_substr($this->entrada('nombre'), 0, 100),
            'pais'   => mb_substr($this->entrada('pais'), 0, 60),
        ];
        $error = match (true) {
            $datos['nombre'] === '' => 'Escribe el nombre de la editorial.',
            $this->modelo('Editorial')->nombreExiste($datos['nombre'], $id) => 'Ya existe una editorial con ese nombre.',
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
