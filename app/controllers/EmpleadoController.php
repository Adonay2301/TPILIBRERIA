<?php
/**
 * Gestión de empleados (solo administrador).
 * Un único rol de empleado, sin subroles. Nunca se eliminan: baja lógica.
 */

class EmpleadoController extends Controller
{
    /** GET /admin/empleados */
    public function index(): void
    {
        Auth::requerirRol(['administrador']);
        $q = mb_substr($this->parametro('q'), 0, 100);
        $estado = in_array($this->parametro('estado'), ['activo', 'baja'], true) ? $this->parametro('estado') : '';
        $empleados = $this->modelo('Empleado');

        $this->vista('admin/empleados/index', [
            'titulo'    => 'Gestión de empleados',
            'empleados' => $empleados->listar($q, $estado),
            'stats'     => $empleados->estadisticas(),
            'q'         => $q,
            'estado'    => $estado,
        ], 'admin');
    }

    /** GET /admin/empleados/crear */
    public function crear(): void
    {
        Auth::requerirRol(['administrador']);
        $this->vista('admin/empleados/formulario', [
            'titulo'           => 'Nuevo empleado',
            'empleado'         => null,
            'contrasenaSugerida' => generarContrasena(),
        ], 'admin');
    }

    /** POST /admin/empleados/guardar */
    public function guardar(): void
    {
        Auth::requerirRol(['administrador']);
        $this->validarCsrf();

        $datos = $this->validar(0, 0, '/admin/empleados/crear');
        $contrasena = (string) ($_POST['contrasena'] ?? '');
        if (mb_strlen($contrasena) < 8) {
            $this->recordarEntrada();
            flash('danger', 'La contraseña temporal debe tener al menos 8 caracteres.');
            $this->redirigir('/admin/empleados/crear');
        }

        $id = $this->modelo('Empleado')->crear($datos, $contrasena);
        flash('success', 'Empleado registrado. Comparte la contraseña temporal de forma segura: ' . $contrasena);
        $this->redirigir('/admin/empleados/' . $id);
    }

    /** GET /admin/empleados/{id} — ficha */
    public function ver(int $id): void
    {
        Auth::requerirRol(['administrador']);
        $modelo = $this->modelo('Empleado');
        $empleado = $modelo->obtener($id) ?? throw new HttpError(404);

        $this->vista('admin/empleados/ver', [
            'titulo'    => $empleado['nombres'] . ' ' . $empleado['apellidos'],
            'empleado'  => $empleado,
            'metricas'  => $modelo->metricas($id, (int) $empleado['id_usuario']),
            'actividad' => $modelo->actividad((int) $empleado['id_usuario']),
            'pedidos'   => array_slice($this->modelo('Pedido')->delEmpleado($id), 0, 10),
        ], 'admin');
    }

    /** GET /admin/empleados/editar/{id} */
    public function editar(int $id): void
    {
        Auth::requerirRol(['administrador']);
        $empleado = $this->modelo('Empleado')->obtener($id) ?? throw new HttpError(404);
        $this->vista('admin/empleados/formulario', ['titulo' => 'Editar empleado', 'empleado' => $empleado], 'admin');
    }

    /** POST /admin/empleados/actualizar/{id} */
    public function actualizar(int $id): void
    {
        Auth::requerirRol(['administrador']);
        $this->validarCsrf();
        $empleado = $this->modelo('Empleado')->obtener($id) ?? throw new HttpError(404);

        $datos = $this->validar($id, (int) $empleado['id_usuario'], "/admin/empleados/editar/$id");
        $this->modelo('Empleado')->actualizar($id, $datos);
        flash('success', 'Datos del empleado actualizados.');
        $this->redirigir('/admin/empleados/' . $id);
    }

    /** POST /admin/empleados/baja/{id} */
    public function baja(int $id): void
    {
        Auth::requerirRol(['administrador']);
        $this->validarCsrf();
        $this->modelo('Empleado')->obtener($id) ?? throw new HttpError(404);

        $liberados = $this->modelo('Empleado')->darDeBaja($id, mb_substr($this->entrada('motivo'), 0, 255));
        flash('success', 'Empleado dado de baja. Ya no puede iniciar sesión; su historial se conserva.'
            . ($liberados ? " Se liberaron $liberados pedido(s) para reasignar." : ''));
        $this->redirigir('/admin/empleados/' . $id);
    }

    /** POST /admin/empleados/reactivar/{id} */
    public function reactivar(int $id): void
    {
        Auth::requerirRol(['administrador']);
        $this->validarCsrf();
        $this->modelo('Empleado')->obtener($id) ?? throw new HttpError(404);
        $this->modelo('Empleado')->reactivar($id);
        flash('success', 'Empleado reactivado. Recuperó el acceso con su cuenta anterior.');
        $this->redirigir('/admin/empleados/' . $id);
    }

    /** POST /admin/empleados/restablecer/{id} — genera una contraseña temporal nueva */
    public function restablecerContrasena(int $id): void
    {
        Auth::requerirRol(['administrador']);
        $this->validarCsrf();
        $empleado = $this->modelo('Empleado')->obtener($id) ?? throw new HttpError(404);

        $contrasena = generarContrasena();
        $this->modelo('Usuario')->cambiarContrasena((int) $empleado['id_usuario'], password_hash($contrasena, PASSWORD_DEFAULT));
        flash('success', 'Contraseña restablecida. Nueva contraseña temporal: ' . $contrasena);
        $this->redirigir('/admin/empleados/' . $id);
    }

    private function validar(int $idEmpleado, int $idUsuario, string $rutaError): array
    {
        $datos = [
            'nombres'            => mb_substr($this->entrada('nombres'), 0, 80),
            'apellidos'          => mb_substr($this->entrada('apellidos'), 0, 80),
            'dui'                => $this->entrada('dui'),
            'telefono'           => $this->entrada('telefono') !== '' ? telefonoSv($this->entrada('telefono')) : '',
            'fecha_contratacion' => $this->entrada('fecha_contratacion'),
            'correo'             => mb_strtolower($this->entrada('correo')),
        ];

        $errores = [];
        if ($datos['nombres'] === '') $errores[] = 'Escribe los nombres.';
        if ($datos['apellidos'] === '') $errores[] = 'Escribe los apellidos.';
        if (!duiValido($datos['dui'])) {
            $errores[] = 'El DUI debe tener el formato 01234567-8.';
        } elseif ($this->modelo('Empleado')->duiExiste($datos['dui'], $idEmpleado)) {
            $errores[] = 'Ya existe un empleado con ese DUI.';
        }
        if ($datos['telefono'] !== '' && !telefonoValido($datos['telefono'])) $errores[] = 'El teléfono debe tener el formato 7123-4567.';
        if (!fechaValida($datos['fecha_contratacion']) || $datos['fecha_contratacion'] > date('Y-m-d')) $errores[] = 'La fecha de ingreso no es válida.';
        if (!filter_var($datos['correo'], FILTER_VALIDATE_EMAIL)) {
            $errores[] = 'El correo no es válido.';
        } elseif ($this->modelo('Usuario')->correoExiste($datos['correo'], $idUsuario)) {
            $errores[] = 'Este correo ya está registrado.';
        }

        if ($errores) {
            $this->recordarEntrada();
            foreach ($errores as $error) flash('danger', $error);
            $this->redirigir($rutaError);
        }
        return $datos;
    }
}
