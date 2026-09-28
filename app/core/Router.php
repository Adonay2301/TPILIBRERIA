<?php
/**
 * Convierte la URL en controlador, método y parámetros usando la tabla de app/config/rutas.php.
 */

class Router
{
    /** @var array<int, array{0:string,1:string,2:string,3:string}> método, patrón, acción, regex */
    private array $rutas = [];

    public function __construct(array $rutas)
    {
        foreach ($rutas as [$metodo, $patron, $accion]) {
            $this->rutas[] = [$metodo, $patron, $accion, $this->compilar($patron)];
        }
    }

    /** '/libro/{id}' -> '#^/libro/(\d+)$#' */
    private function compilar(string $patron): string
    {
        $regex = preg_replace_callback('#\{(\w+)\}#', function (array $m): string {
            return $m[1] === 'id' ? '(\d+)' : '([^/]+)';
        }, $patron);

        return '#^' . $regex . '$#';
    }

    public function despachar(string $metodo, string $uri): void
    {
        $ruta = $this->rutaRelativa($uri);
        $metodo = $metodo === 'HEAD' ? 'GET' : strtoupper($metodo);

        foreach ($this->rutas as [$metodoRuta, , $accion, $regex]) {
            if ($metodoRuta !== $metodo || !preg_match($regex, $ruta, $coincidencias)) {
                continue;
            }

            [$claseControlador, $accionMetodo] = explode('@', $accion);

            // Módulos que aún no existen responden 404 en lugar de romper el sistema
            if (!class_exists($claseControlador) || !method_exists($claseControlador, $accionMetodo)) {
                throw new HttpError(404);
            }

            // Los parámetros numéricos llegan como int
            $parametros = array_map(
                fn (string $p) => ctype_digit($p) ? (int) $p : urldecode($p),
                array_slice($coincidencias, 1)
            );

            (new $claseControlador())->$accionMetodo(...$parametros);
            return;
        }

        throw new HttpError(404);
    }

    /** Quita BASE_URL, /public, /index.php y la query string: '/punto-y-aparte/libro/3?x=1' -> '/libro/3' */
    private function rutaRelativa(string $uri): string
    {
        $ruta = rawurldecode(parse_url($uri, PHP_URL_PATH) ?? '/');

        if (BASE_URL !== '' && str_starts_with($ruta, BASE_URL)) {
            $ruta = substr($ruta, strlen(BASE_URL));
        }
        $ruta = preg_replace('#^/public(?=/|$)#', '', $ruta);
        $ruta = preg_replace('#^/index\.php(?=/|$)#', '', $ruta);

        $ruta = '/' . trim($ruta, '/');
        return $ruta;
    }
}
