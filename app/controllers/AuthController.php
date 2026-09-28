<?php
/**
 * Inicio de sesión, registro de clientes y cierre de sesión.
 * El rol se detecta desde la base de datos; el usuario nunca lo elige.
 */

class AuthController extends Controller
{
    private const MAX_INTENTOS = 5;
    private const SEGUNDOS_BLOQUEO = 60;

    /** GET /login */
    public function formulario(): void
    {
        if (Auth::check()) {
            $this->redirigir($this->inicioSegunRol());
        }
        $this->vista('auth/login', ['titulo' => 'Iniciar sesión'], 'simple');
    }

    /** POST /login */
    public function iniciar(): void
    {
        $this->validarCsrf();

        // Freno simple contra intentos repetidos desde la misma sesión
        if (($_SESSION['login_bloqueado_hasta'] ?? 0) > time()) {
            flash('warning', 'Demasiados intentos fallidos. Espera un minuto e inténtalo de nuevo.');
            $this->redirigir('/login');
        }

        $correo = mb_strtolower($this->entrada('correo'));
        $contrasena = (string) ($_POST['contrasena'] ?? '');

        $modelo = $this->modelo('Usuario');
        $usuario = ($correo !== '' && $contrasena !== '') ? $modelo->paraLogin($correo) : null;

        if (!$usuario || !password_verify($contrasena, $usuario['contrasena'])) {
            $this->registrarFallo();
            $this->recordarEntrada();
            flash('danger', 'Correo o contraseña incorrectos.');
            $this->redirigir('/login');
        }

        if (!$usuario['activo'] || $usuario['id_perfil'] === null) {
            $this->recordarEntrada();
            flash('danger', 'Tu cuenta está desactivada. Comunícate con la librería.');
            $this->redirigir('/login');
        }

        // Si PHP cambió el algoritmo recomendado, se actualiza el hash
        if (password_needs_rehash($usuario['contrasena'], PASSWORD_DEFAULT)) {
            $modelo->cambiarContrasena((int) $usuario['id_usuario'], password_hash($contrasena, PASSWORD_DEFAULT));
        }

        $modelo->registrarAcceso((int) $usuario['id_usuario']);
        unset($_SESSION['login_intentos'], $_SESSION['login_bloqueado_hasta']);

        $destino = $_SESSION['despues_login'] ?? null;
        unset($_SESSION['despues_login']);

        Auth::iniciarSesion($usuario);
        flash('success', 'Bienvenido/a, ' . explode(' ', $usuario['nombre'])[0] . '.');

        // Personal va al panel; el cliente regresa a donde estaba (solo rutas internas)
        if ($usuario['rol'] === 'cliente' && is_string($destino) && str_starts_with($destino, '/') && !str_starts_with($destino, '//')) {
            header('Location: ' . $destino);
            exit;
        }
        $this->redirigir($this->inicioSegunRol());
    }

    /** GET /registro */
    public function registro(): void
    {
        if (Auth::check()) {
            $this->redirigir($this->inicioSegunRol());
        }
        $this->vista('auth/registro', [
            'titulo'        => 'Crear cuenta',
            'departamentos' => $this->modelo('Ubicacion')->departamentos(),
        ], 'simple');
    }

    /** POST /registro */
    public function registrar(): void
    {
        $this->validarCsrf();

        $datos = [
            'nombres'     => $this->entrada('nombres'),
            'apellidos'   => $this->entrada('apellidos'),
            'correo'      => mb_strtolower($this->entrada('correo')),
            'telefono'    => $this->entrada('telefono') !== '' ? telefonoSv($this->entrada('telefono')) : '',
            'contrasena'  => (string) ($_POST['contrasena'] ?? ''),
            'id_distrito' => (int) $this->entrada('id_distrito'),
            'direccion'   => $this->entrada('direccion'),
            'referencia'  => $this->entrada('referencia'),
        ];

        $errores = [];
        if ($datos['nombres'] === '' || mb_strlen($datos['nombres']) > 80) $errores[] = 'Escribe tus nombres.';
        if ($datos['apellidos'] === '' || mb_strlen($datos['apellidos']) > 80) $errores[] = 'Escribe tus apellidos.';
        if (!filter_var($datos['correo'], FILTER_VALIDATE_EMAIL) || mb_strlen($datos['correo']) > 150) {
            $errores[] = 'El correo no es válido.';
        } elseif ($this->modelo('Usuario')->correoExiste($datos['correo'])) {
            $errores[] = 'Ya existe una cuenta con ese correo.';
        }
        if ($datos['telefono'] !== '' && !telefonoValido($datos['telefono'])) $errores[] = 'El teléfono debe tener el formato 7123-4567.';
        if (mb_strlen($datos['contrasena']) < 8) $errores[] = 'La contraseña debe tener al menos 8 caracteres.';
        if ($datos['contrasena'] !== ($_POST['contrasena_confirmacion'] ?? '')) $errores[] = 'Las contraseñas no coinciden.';
        if (!$this->modelo('Ubicacion')->distrito($datos['id_distrito'])) $errores[] = 'Selecciona departamento, municipio y distrito.';
        if ($datos['direccion'] === '' || mb_strlen($datos['direccion']) > 255) $errores[] = 'Escribe tu dirección.';

        if ($errores) {
            $this->recordarEntrada();
            foreach ($errores as $error) {
                flash('danger', $error);
            }
            $this->redirigir('/registro');
        }

        $usuario = $this->modelo('Cliente')->registrar($datos);
        Auth::iniciarSesion($usuario);
        flash('success', '¡Tu cuenta fue creada! Ya puedes comprar.');
        $this->redirigir('/catalogo');
    }

    /** POST /logout */
    public function cerrar(): void
    {
        $this->validarCsrf();
        Auth::cerrarSesion();
        Auth::iniciar(); // sesión nueva y vacía, solo para el mensaje
        flash('info', 'Cerraste sesión. ¡Vuelve pronto!');
        $this->redirigir('/catalogo');
    }

    private function inicioSegunRol(): string
    {
        return Auth::esRol(...Auth::ROLES_PANEL) ? '/admin' : '/catalogo';
    }

    private function registrarFallo(): void
    {
        $_SESSION['login_intentos'] = ($_SESSION['login_intentos'] ?? 0) + 1;
        if ($_SESSION['login_intentos'] >= self::MAX_INTENTOS) {
            $_SESSION['login_bloqueado_hasta'] = time() + self::SEGUNDOS_BLOQUEO;
            $_SESSION['login_intentos'] = 0;
        }
    }
}
