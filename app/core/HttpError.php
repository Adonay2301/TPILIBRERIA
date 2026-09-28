<?php
/**
 * Error HTTP controlado (403, 404...). El front controller lo atrapa y muestra la página de error.
 */

class HttpError extends Exception
{
    public function __construct(int $codigo, string $mensaje = '')
    {
        parent::__construct($mensaje, $codigo);
    }
}
