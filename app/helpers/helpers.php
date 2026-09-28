<?php
/**
 * Funciones de ayuda globales: URLs, escape de HTML, formatos de El Salvador,
 * token CSRF, mensajes flash y paginación.
 */

// ---------------------------------------------------------------------
// URLs
// ---------------------------------------------------------------------

/** url('/libro/3') -> '/punto-y-aparte/libro/3' */
function url(string $ruta = '/'): string
{
    return BASE_URL . '/' . ltrim($ruta, '/');
}

/** Archivo de public/assets: asset('css/estilos.css') */
function asset(string $ruta): string
{
    $ruta = ltrim($ruta, '/');
    // ?v=fecha de modificación: el navegador descarga el archivo de nuevo cuando cambia
    $archivo = RUTA_RAIZ . '/public/assets/' . $ruta;
    return BASE_URL . '/assets/' . $ruta . (is_file($archivo) ? '?v=' . filemtime($archivo) : '');
}

/**
 * ¿La ruta actual empieza con $prefijo? Sirve para marcar el menú activo.
 * Con $exacta = true solo coincide la ruta idéntica (ej. '/admin' pero no '/admin/pedidos').
 */
function rutaActiva(string $prefijo, bool $exacta = false): bool
{
    $ruta = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/';
    if (BASE_URL !== '' && str_starts_with($ruta, BASE_URL)) {
        $ruta = substr($ruta, strlen(BASE_URL));
    }
    $ruta = '/' . trim($ruta, '/');

    if ($exacta || $prefijo === '/') {
        return $ruta === $prefijo;
    }
    return $ruta === $prefijo || str_starts_with($ruta, rtrim($prefijo, '/') . '/');
}

// ---------------------------------------------------------------------
// Escape de HTML
// ---------------------------------------------------------------------

/** Escapa todo lo que se imprime en una vista. */
function e(mixed $valor): string
{
    return htmlspecialchars((string) ($valor ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

// ---------------------------------------------------------------------
// Formatos de El Salvador
// ---------------------------------------------------------------------

/** 1234.5 -> '$1,234.50' */
function moneda(float|int|string|null $monto): string
{
    return '$' . number_format((float) $monto, 2, '.', ',');
}

/** '2026-09-27 14:30:00' -> '27/09/2026' */
function fecha(?string $valor): string
{
    if (!$valor) {
        return '—';
    }
    $marca = strtotime($valor);
    return $marca ? date('d/m/Y', $marca) : '—';
}

/** '2026-09-27 14:30:00' -> '27/09/2026 · 14:30' */
function fechaHora(?string $valor): string
{
    if (!$valor) {
        return '—';
    }
    $marca = strtotime($valor);
    return $marca ? date('d/m/Y · H:i', $marca) : '—';
}

/** Días transcurridos desde una fecha (para "Nuevo", antigüedad, etc.). */
function diasDesde(?string $valor): int
{
    if (!$valor) {
        return 0;
    }
    return (int) floor((time() - strtotime($valor)) / 86400);
}

/** '71234567' o '+503 7123-4567' -> '+503 7123-4567' */
function telefonoSv(?string $numero): string
{
    $digitos = preg_replace('/\D/', '', (string) $numero);
    if (str_starts_with($digitos, '503') && strlen($digitos) === 11) {
        $digitos = substr($digitos, 3);
    }
    return strlen($digitos) === 8
        ? '+503 ' . substr($digitos, 0, 4) . '-' . substr($digitos, 4)
        : (string) $numero;
}

/** Valida el formato que exige la base de datos: +503 7123-4567 */
function telefonoValido(string $telefono): bool
{
    return (bool) preg_match('/^\+503 [267]\d{3}-\d{4}$/', $telefono);
}

/** Valida el DUI: 01234567-8 */
function duiValido(string $dui): bool
{
    return (bool) preg_match('/^\d{8}-\d$/', $dui);
}

/** id 7 -> 'PYA-000007' (código visible del pedido; no es una columna) */
function codigoPedido(int|string $id): string
{
    return 'PYA-' . str_pad((string) $id, 6, '0', STR_PAD_LEFT);
}

/** id 3 -> 'EMP-003' (código visible del empleado; no es una columna) */
function codigoEmpleado(int|string $id): string
{
    return 'EMP-' . str_pad((string) $id, 3, '0', STR_PAD_LEFT);
}

/** 'María José Hernández' -> 'MH' */
function iniciales(string $nombre): string
{
    $partes = preg_split('/\s+/', trim($nombre)) ?: [];
    $letras = array_map(fn ($p) => mb_substr($p, 0, 1), array_slice($partes, 0, 2));
    return mb_strtoupper(implode('', $letras));
}

/**
 * Etiqueta y estilo de cada estado de pedido (ENUM de pedidos.estado).
 * Devuelve ['texto' => ..., 'clase' => 'estado-...'] para la insignia.
 */
function estadoPedido(string $estado): array
{
    return [
        'pendiente'      => ['texto' => 'Pendiente',   'clase' => 'estado-pendiente'],
        'en_preparacion' => ['texto' => 'Preparando',  'clase' => 'estado-preparacion'],
        'enviado'        => ['texto' => 'En tránsito', 'clase' => 'estado-enviado'],
        'entregado'      => ['texto' => 'Entregado',   'clase' => 'estado-entregado'],
        'cancelado'      => ['texto' => 'Cancelado',   'clase' => 'estado-cancelado'],
    ][$estado] ?? ['texto' => ucfirst($estado), 'clase' => 'estado-cancelado'];
}

/** Texto del método de pago. */
function metodoPago(?string $metodo): string
{
    return ['contra_entrega' => 'Contra entrega', 'paypal' => 'PayPal'][$metodo] ?? 'Sin registro';
}

/** Texto y clase (reutiliza los colores de los estados del pedido) del estado del pago. */
function estadoPago(?string $estado): array
{
    return [
        'pendiente'   => ['texto' => 'Por cobrar',  'clase' => 'estado-pendiente'],
        'completado'  => ['texto' => 'Pagado',      'clase' => 'estado-entregado'],
        'reembolsado' => ['texto' => 'Reembolsado', 'clase' => 'estado-enviado'],
        'cancelado'   => ['texto' => 'Anulado',     'clase' => 'estado-cancelado'],
    ][$estado] ?? ['texto' => '—', 'clase' => 'estado-cancelado'];
}

/** Disponibilidad según el stock mínimo propio de cada libro. */
function estadoStock(int $stock, int $minimo): array
{
    if ($stock <= 0) {
        return ['texto' => 'Agotado', 'clase' => 'stock-agotado'];
    }
    if ($stock <= $minimo) {
        return ['texto' => 'Últimas unidades', 'clase' => 'stock-bajo'];
    }
    return ['texto' => 'Disponible', 'clase' => 'stock-disponible'];
}

/** Ruta de la portada o null si el libro no tiene (la vista dibuja una portada genérica). */
function portadaUrl(?string $portada): ?string
{
    if (!$portada) {
        return null;
    }
    return preg_match('#^https?://#', $portada) ? $portada : asset('img/portadas/' . basename($portada));
}

/** ¿Es una fecha real con formato Y-m-d? */
function fechaValida(?string $valor): bool
{
    if (!$valor) {
        return false;
    }
    $fecha = DateTime::createFromFormat('Y-m-d', $valor);
    return $fecha !== false && $fecha->format('Y-m-d') === $valor;
}

/** Variación porcentual entre dos valores (null si no hay base de comparación). */
function variacion(float $actual, float $anterior): ?float
{
    return $anterior > 0 ? round(($actual - $anterior) / $anterior * 100, 1) : null;
}

/** Contraseña temporal legible (sin 0/O ni 1/l). */
function generarContrasena(int $largo = 12): string
{
    $caracteres = 'abcdefghjkmnpqrstuvwxyzABCDEFGHJKMNPQRSTUVWXYZ23456789!@#$';
    $clave = '';
    for ($i = 0; $i < $largo; $i++) {
        $clave .= $caracteres[random_int(0, strlen($caracteres) - 1)];
    }
    return $clave;
}

/**
 * Guarda la portada subida en public/assets/img/portadas y devuelve el nombre del archivo.
 * Devuelve null si no se subió nada. Lanza DomainException si el archivo no es válido.
 */
function guardarPortada(?array $archivo): ?string
{
    if (!$archivo || ($archivo['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    if ($archivo['error'] !== UPLOAD_ERR_OK || !is_uploaded_file($archivo['tmp_name'])) {
        throw new DomainException('No se pudo subir la portada.');
    }
    if ($archivo['size'] > 4 * 1024 * 1024) {
        throw new DomainException('La portada no puede pesar más de 4 MB.');
    }

    // El tipo se verifica por contenido, no por la extensión que manda el navegador
    $extensiones = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    $tipo = (new finfo(FILEINFO_MIME_TYPE))->file($archivo['tmp_name']);
    if (!isset($extensiones[$tipo])) {
        throw new DomainException('La portada debe ser JPG, PNG o WEBP.');
    }

    $nombre = bin2hex(random_bytes(8)) . '.' . $extensiones[$tipo];
    $destino = RUTA_RAIZ . '/public/assets/img/portadas/' . $nombre;
    if (!move_uploaded_file($archivo['tmp_name'], $destino)) {
        throw new DomainException('No se pudo guardar la portada.');
    }
    return $nombre;
}

// ---------------------------------------------------------------------
// Token CSRF
// ---------------------------------------------------------------------

function csrf_token(): string
{
    if (empty($_SESSION['_csrf'])) {
        $_SESSION['_csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['_csrf'];
}

/** Campo oculto para todos los formularios POST. */
function csrf_campo(): string
{
    return '<input type="hidden" name="_csrf" value="' . e(csrf_token()) . '">';
}

function csrf_valido(?string $token): bool
{
    return is_string($token) && !empty($_SESSION['_csrf']) && hash_equals($_SESSION['_csrf'], $token);
}

// ---------------------------------------------------------------------
// Mensajes flash y datos del formulario anterior
// ---------------------------------------------------------------------

/** Guarda un mensaje para la siguiente página. $tipo: success, danger, warning, info */
function flash(string $tipo, string $mensaje): void
{
    $_SESSION['flash'][] = ['tipo' => $tipo, 'mensaje' => $mensaje];
}

/** Devuelve y borra los mensajes pendientes. */
function flashes(): array
{
    $mensajes = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $mensajes;
}

/** Valor que el usuario escribió antes de un error de validación. */
function old(string $campo, string $defecto = ''): string
{
    return (string) ($_SESSION['old'][$campo] ?? $defecto);
}

/** Se llama al final del layout para que los datos viejos no reaparezcan. */
function limpiarOld(): void
{
    unset($_SESSION['old']);
}

// ---------------------------------------------------------------------
// Peticiones JSON (fetch)
// ---------------------------------------------------------------------

function esPeticionJson(): bool
{
    return ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest'
        || str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json');
}

function responderJson(array $datos, int $codigo = 200): never
{
    http_response_code($codigo);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($datos, JSON_UNESCAPED_UNICODE);
    exit;
}

// ---------------------------------------------------------------------
// Paginación
// ---------------------------------------------------------------------

/**
 * Calcula la página actual y el OFFSET para el modelo.
 * Devuelve: pagina, por_pagina, offset, total, total_paginas
 */
function paginar(int $total, int $porPagina, ?int $pagina = null): array
{
    $totalPaginas = max(1, (int) ceil($total / $porPagina));
    $pagina = $pagina ?? (int) ($_GET['pagina'] ?? 1);
    $pagina = min(max(1, $pagina), $totalPaginas);

    return [
        'pagina'        => $pagina,
        'por_pagina'    => $porPagina,
        'offset'        => ($pagina - 1) * $porPagina,
        'total'         => $total,
        'total_paginas' => $totalPaginas,
    ];
}

/**
 * URL actual sin un filtro (para los chips "x" de filtros activos).
 * Si $valor se indica, solo quita ese valor de un filtro múltiple.
 */
function urlSin(string $clave, mixed $valor = null): string
{
    $parametros = $_GET;
    unset($parametros['pagina']);

    if ($valor !== null && is_array($parametros[$clave] ?? null)) {
        $parametros[$clave] = array_values(array_filter($parametros[$clave], fn ($v) => (string) $v !== (string) $valor));
    } else {
        unset($parametros[$clave]);
    }
    $ruta = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/';
    $query = http_build_query($parametros);
    return $ruta . ($query !== '' ? '?' . $query : '');
}

/** URL actual cambiando (o agregando) un parámetro. */
function urlCon(string $clave, string $valor): string
{
    $parametros = $_GET;
    unset($parametros['pagina']);
    $parametros[$clave] = $valor;
    $ruta = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/';
    return $ruta . '?' . http_build_query($parametros);
}

/** URL de otra página conservando los filtros de la búsqueda actual. */
function urlPagina(int $pagina): string
{
    $parametros = $_GET;
    $parametros['pagina'] = $pagina;
    $ruta = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/';
    return $ruta . '?' . http_build_query($parametros);
}
