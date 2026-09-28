<?php
/**
 * Cuentas de acceso (tabla usuarios) y su perfil según el rol.
 */

class UsuarioModel extends Model
{
    /**
     * Datos para iniciar sesión: credenciales + nombre e id del perfil
     * (clientes, empleados o administradores según usuarios.rol).
     */
    public function paraLogin(string $correo): ?array
    {
        return $this->uno(
            "SELECT u.id_usuario, u.correo, u.contrasena, u.rol, u.activo,
                    COALESCE(c.id_cliente, e.id_empleado, a.id_administrador) AS id_perfil,
                    CONCAT_WS(' ', COALESCE(c.nombres, e.nombres, a.nombres),
                                   COALESCE(c.apellidos, e.apellidos, a.apellidos)) AS nombre,
                    e.estado AS estado_empleado
               FROM usuarios u
               LEFT JOIN clientes c        ON c.id_usuario = u.id_usuario AND u.rol = 'cliente'
               LEFT JOIN empleados e       ON e.id_usuario = u.id_usuario AND u.rol = 'empleado'
               LEFT JOIN administradores a ON a.id_usuario = u.id_usuario AND u.rol = 'administrador'
              WHERE u.correo = ?",
            [$correo]
        );
    }

    public function registrarAcceso(int $idUsuario): void
    {
        $this->ejecutar('UPDATE usuarios SET ultimo_acceso = NOW() WHERE id_usuario = ?', [$idUsuario]);
    }

    public function cambiarContrasena(int $idUsuario, string $hash): void
    {
        $this->ejecutar('UPDATE usuarios SET contrasena = ? WHERE id_usuario = ?', [$hash, $idUsuario]);
    }

    public function cambiarActivo(int $idUsuario, bool $activo): void
    {
        $this->ejecutar('UPDATE usuarios SET activo = ? WHERE id_usuario = ?', [$activo ? 1 : 0, $idUsuario]);
    }

    /** ¿El correo ya está registrado? (opcionalmente sin contar a un usuario) */
    public function correoExiste(string $correo, ?int $excluirIdUsuario = null): bool
    {
        return (bool) $this->valor(
            'SELECT COUNT(*) FROM usuarios WHERE correo = ? AND id_usuario <> ?',
            [$correo, $excluirIdUsuario ?? 0]
        );
    }
}
