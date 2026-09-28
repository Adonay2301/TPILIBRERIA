<?php
/**
 * Muestra las páginas de error (403, 404, 500) o su versión JSON para fetch().
 */

class ErrorController extends Controller
{
    private const MENSAJES = [
        403 => 'No tienes permiso para ver esta página.',
        404 => 'La página que buscas no existe.',
        500 => 'Ocurrió un error inesperado. Intenta de nuevo en unos minutos.',
    ];

    public function mostrar(int $codigo, ?Throwable $detalle = null): void
    {
        $codigo = isset(self::MENSAJES[$codigo]) ? $codigo : 500;
        http_response_code($codigo);

        if (esPeticionJson()) {
            $this->json(['ok' => false, 'mensaje' => self::MENSAJES[$codigo]], $codigo);
        }

        $this->vista('errores/error', [
            'titulo'  => "Error $codigo",
            'codigo'  => $codigo,
            'mensaje' => self::MENSAJES[$codigo],
            'detalle' => $detalle,
        ]);
    }
}
