<?php
/**
 * Sesión y permisos.
 *
 * El rol NO lo elige el usuario: se lee de usuarios.rol al iniciar sesión.
 * Los controladores protegen sus métodos con Auth::requerirRol([...]).
 */

class Auth
{
    public const ROLES_PANEL = ['administrador', 'empleado'];

    public static function iniciar(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        session_name('PYASESSID');
        session_set_cookie_params([
            'lifetime' => 0,
            'path'     => BASE_URL === '' ? '/' : BASE_URL . '/',
            'httponly' => true,                      // JavaScript no puede leer la cookie
            'samesite' => 'Lax',
            'secure'   => !empty($_SERVER['HTTPS']),
        ]);
        ini_set('session.use_strict_mode', '1');
        session_start();
    }

    /**
     * Guarda al usuario en sesión.
     * $usuario: id_usuario, correo, rol, nombre, id_perfil (id de cliente/empleado/administrador)
     */
    public static function iniciarSesion(array $usuario): void
    {
        session_regenerate_id(true); // evita la fijación de sesión

        $_SESSION['usuario'] = [
            'id_usuario' => (int) $usuario['id_usuario'],
            'id_perfil'  => (int) $usuario['id_perfil'],
            'correo'     => $usuario['correo'],
            'rol'        => $usuario['rol'],
            'nombre'     => $usuario['nombre'],
        ];

        Database::fijarUsuario((int) $usuario['id_usuario']);
    }

    public static function cerrarSesion(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
        }
        session_destroy();
    }

    public static function usuario(): ?array
    {
        return $_SESSION['usuario'] ?? null;
    }

    public static function check(): bool
    {
        return self::usuario() !== null;
    }

    public static function id(): ?int
    {
        return self::usuario()['id_usuario'] ?? null;
    }

    /** id_cliente, id_empleado o id_administrador según el rol. */
    public static function idPerfil(): ?int
    {
        return self::usuario()['id_perfil'] ?? null;
    }

    public static function rol(): ?string
    {
        return self::usuario()['rol'] ?? null;
    }

    public static function esRol(string ...$roles): bool
    {
        return in_array(self::rol(), $roles, true);
    }

    public static function esAdmin(): bool
    {
        return self::esRol('administrador');
    }

    /** Si no hay sesión, guarda la URL pedida y manda al login. */
    public static function requerirLogin(): void
    {
        if (self::check()) {
            return;
        }

        if (esPeticionJson()) {
            responderJson(['ok' => false, 'mensaje' => 'Tu sesión expiró. Inicia sesión de nuevo.', 'login' => url('/login')], 401);
        }

        $_SESSION['despues_login'] = $_SERVER['REQUEST_URI'] ?? url('/');
        flash('warning', 'Inicia sesión para continuar.');
        header('Location: ' . url('/login'));
        exit;
    }

    /** Permite el paso solo a los roles indicados; a los demás les responde 403. */
    public static function requerirRol(array $roles): void
    {
        self::requerirLogin();

        if (!in_array(self::rol(), $roles, true)) {
            throw new HttpError(403);
        }
    }
}
