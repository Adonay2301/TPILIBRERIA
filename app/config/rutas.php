<?php
/**
 * Tabla de rutas: [método HTTP, patrón de URL, 'Controlador@método'].
 *
 * {id} solo acepta números; {texto} acepta cualquier segmento.
 * Si el controlador de una ruta todavía no existe, el Router responde 404.
 */

return [
    // ---------- Público ----------
    ['GET',  '/',                       'CatalogoController@index'],
    ['GET',  '/catalogo',               'CatalogoController@index'],
    ['GET',  '/libro/{id}',             'LibroController@ver'],
    ['GET',  '/novedades',              'NovedadesController@index'],

    // ---------- Autenticación ----------
    ['GET',  '/login',                  'AuthController@formulario'],
    ['POST', '/login',                  'AuthController@iniciar'],
    ['GET',  '/registro',               'AuthController@registro'],
    ['POST', '/registro',               'AuthController@registrar'],
    ['POST', '/logout',                 'AuthController@cerrar'],

    // Ubicaciones para los selects dependientes (JSON)
    ['GET',  '/api/municipios/{id}',    'UbicacionController@municipios'],
    ['GET',  '/api/distritos/{id}',     'UbicacionController@distritos'],

    // ---------- Cliente: carrito, compra y seguimiento ----------
    ['GET',  '/carrito',                'CarritoController@index'],
    ['POST', '/carrito/agregar',        'CarritoController@agregar'],
    ['POST', '/carrito/actualizar',     'CarritoController@actualizar'],
    ['POST', '/carrito/eliminar',       'CarritoController@eliminar'],
    ['GET',  '/pedido/confirmar',       'PedidoController@confirmar'],
    ['POST', '/pedido/crear',           'PedidoController@crear'],
    ['GET',  '/mis-pedidos',            'SeguimientoController@index'],
    ['GET',  '/seguimiento/{id}',       'SeguimientoController@ver'],

    // ---------- Panel (administrador y empleado) ----------
    ['GET',  '/admin',                              'PanelController@index'],

    ['GET',  '/admin/pedidos',                      'PedidoController@index'],
    ['GET',  '/admin/pedidos/{id}',                 'PedidoController@ver'],
    ['POST', '/admin/pedidos/{id}/estado',          'PedidoController@cambiarEstado'],
    ['POST', '/admin/pedidos/{id}/asignar',         'PedidoController@asignar'],

    ['GET',  '/admin/preparacion',                  'PreparacionController@index'],
    ['POST', '/admin/preparacion/{id}/listo',       'PreparacionController@listo'],

    ['GET',  '/admin/libros',                       'InventarioController@index'],
    ['GET',  '/admin/libros/crear',                 'InventarioController@crear'],
    ['POST', '/admin/libros/guardar',               'InventarioController@guardar'],
    ['GET',  '/admin/libros/editar/{id}',           'InventarioController@editar'],
    ['POST', '/admin/libros/actualizar/{id}',       'InventarioController@actualizar'],
    ['POST', '/admin/libros/entrada/{id}',          'InventarioController@registrarEntrada'],
    ['POST', '/admin/libros/ocultar/{id}',          'InventarioController@ocultar'],

    ['GET',  '/admin/autores',                      'AutorController@index'],
    ['GET',  '/admin/autores/crear',                'AutorController@crear'],
    ['POST', '/admin/autores/guardar',              'AutorController@guardar'],
    ['GET',  '/admin/autores/editar/{id}',          'AutorController@editar'],
    ['POST', '/admin/autores/actualizar/{id}',      'AutorController@actualizar'],
    ['POST', '/admin/autores/eliminar/{id}',        'AutorController@eliminar'],

    ['GET',  '/admin/categorias',                   'CategoriaController@index'],
    ['GET',  '/admin/categorias/crear',             'CategoriaController@crear'],
    ['POST', '/admin/categorias/guardar',           'CategoriaController@guardar'],
    ['GET',  '/admin/categorias/editar/{id}',       'CategoriaController@editar'],
    ['POST', '/admin/categorias/actualizar/{id}',   'CategoriaController@actualizar'],
    ['POST', '/admin/categorias/eliminar/{id}',     'CategoriaController@eliminar'],

    ['GET',  '/admin/editoriales',                  'EditorialController@index'],
    ['GET',  '/admin/editoriales/crear',            'EditorialController@crear'],
    ['POST', '/admin/editoriales/guardar',          'EditorialController@guardar'],
    ['GET',  '/admin/editoriales/editar/{id}',      'EditorialController@editar'],
    ['POST', '/admin/editoriales/actualizar/{id}',  'EditorialController@actualizar'],
    ['POST', '/admin/editoriales/eliminar/{id}',    'EditorialController@eliminar'],

    ['GET',  '/admin/clientes',                     'ClienteController@index'],
    ['GET',  '/admin/clientes/{id}',                'ClienteController@ver'],
    ['POST', '/admin/clientes/{id}/estado',         'ClienteController@cambiarEstado'],

    ['GET',  '/admin/empleados',                    'EmpleadoController@index'],
    ['GET',  '/admin/empleados/crear',              'EmpleadoController@crear'],
    ['POST', '/admin/empleados/guardar',            'EmpleadoController@guardar'],
    ['GET',  '/admin/empleados/{id}',               'EmpleadoController@ver'],
    ['GET',  '/admin/empleados/editar/{id}',        'EmpleadoController@editar'],
    ['POST', '/admin/empleados/actualizar/{id}',    'EmpleadoController@actualizar'],
    ['POST', '/admin/empleados/baja/{id}',          'EmpleadoController@baja'],
    ['POST', '/admin/empleados/reactivar/{id}',     'EmpleadoController@reactivar'],
    ['POST', '/admin/empleados/restablecer/{id}',   'EmpleadoController@restablecerContrasena'],

    ['GET',  '/admin/reportes',                     'ReporteController@index'],
];
