-- =====================================================================
--  PUNTO Y APARTE - seed.sql
--  Datos de prueba. Ejecutar DESPUÉS de schema.sql y ubicaciones.sql,
--  sobre una base recién creada (usa ids fijos).
--
--  CONTRASEÑAS DE PRUEBA (texto plano -> hash bcrypt de password_hash()):
--    Administrador : Admin2026!
--    Empleados     : Empleado2026!
--    Clientes      : Cliente2026!
-- =====================================================================

USE punto_y_aparte;
SET NAMES utf8mb4;

-- Variables de sesión que leen los triggers del historial de pedidos.
SET @id_usuario_actual = NULL;
SET @comentario_estado = NULL;

-- Distritos usados en las direcciones (municipio y departamento salen por relación).
SET @d_san_salvador   = (SELECT d.id_distrito FROM distritos d JOIN municipios m USING (id_municipio)
                         WHERE d.nombre = 'San Salvador'      AND m.nombre = 'San Salvador Centro');
SET @d_mejicanos      = (SELECT d.id_distrito FROM distritos d JOIN municipios m USING (id_municipio)
                         WHERE d.nombre = 'Mejicanos'         AND m.nombre = 'San Salvador Centro');
SET @d_santa_tecla    = (SELECT d.id_distrito FROM distritos d JOIN municipios m USING (id_municipio)
                         WHERE d.nombre = 'Santa Tecla'       AND m.nombre = 'La Libertad Sur');
SET @d_antiguo_cusca  = (SELECT d.id_distrito FROM distritos d JOIN municipios m USING (id_municipio)
                         WHERE d.nombre = 'Antiguo Cuscatlán' AND m.nombre = 'La Libertad Este');
SET @d_santa_ana      = (SELECT d.id_distrito FROM distritos d JOIN municipios m USING (id_municipio)
                         WHERE d.nombre = 'Santa Ana'         AND m.nombre = 'Santa Ana Centro');
SET @d_san_miguel     = (SELECT d.id_distrito FROM distritos d JOIN municipios m USING (id_municipio)
                         WHERE d.nombre = 'San Miguel'        AND m.nombre = 'San Miguel Centro');


-- =====================================================================
--  USUARIOS Y PERFILES
-- =====================================================================

INSERT INTO usuarios (id_usuario, correo, contrasena, rol, activo, ultimo_acceso, fecha_creacion) VALUES
  -- Administrador  (contraseña: Admin2026!)
  (1, 'admin@puntoyaparte.com.sv',
      '$2y$10$UjNA9dqKW9rOKNZM4EQGz.WpveV3fq9rx./gI0Yv8RxcorTHK0rpS', 'administrador', 1, '2026-09-27 08:05:00', '2025-01-10 09:00:00'),
  -- Empleados      (contraseña: Empleado2026!)
  (2, 'carlos.mejia@puntoyaparte.com.sv',
      '$2y$10$Nx06tIUulvHV7ZsgLnCqF.06Kt.OrsNTIVU0.4op8f6KV4kJqMmW.', 'empleado', 1, '2026-09-27 07:55:00', '2025-01-15 09:00:00'),
  (3, 'andrea.portillo@puntoyaparte.com.sv',
      '$2y$10$Nx06tIUulvHV7ZsgLnCqF.06Kt.OrsNTIVU0.4op8f6KV4kJqMmW.', 'empleado', 1, '2026-09-26 13:20:00', '2025-03-03 09:00:00'),
  (4, 'jose.rivas@puntoyaparte.com.sv',
      '$2y$10$Nx06tIUulvHV7ZsgLnCqF.06Kt.OrsNTIVU0.4op8f6KV4kJqMmW.', 'empleado', 1, '2026-08-29 17:00:00', '2025-02-01 09:00:00'),
  -- Clientes       (contraseña: Cliente2026!)
  (5, 'maria.hernandez@ejemplo.com',
      '$2y$10$3Obckp8XrUy93.1QF2xEIe4ZrlvS3ztD/0QwNKm7vVhemjOeJ4gpy', 'cliente', 1, '2026-09-20 19:12:00', '2025-06-14 18:30:00'),
  (6, 'luis.guevara@ejemplo.com',
      '$2y$10$3Obckp8XrUy93.1QF2xEIe4ZrlvS3ztD/0QwNKm7vVhemjOeJ4gpy', 'cliente', 1, '2026-09-25 21:40:00', '2025-08-02 10:05:00'),
  (7, 'karla.flores@ejemplo.com',
      '$2y$10$3Obckp8XrUy93.1QF2xEIe4ZrlvS3ztD/0QwNKm7vVhemjOeJ4gpy', 'cliente', 1, '2026-09-12 09:00:00', '2025-11-21 15:45:00'),
  (8, 'roberto.ayala@ejemplo.com',
      '$2y$10$3Obckp8XrUy93.1QF2xEIe4ZrlvS3ztD/0QwNKm7vVhemjOeJ4gpy', 'cliente', 1, '2026-09-22 18:25:00', '2026-02-08 12:10:00'),
  (9, 'gabriela.menjivar@ejemplo.com',
      '$2y$10$3Obckp8XrUy93.1QF2xEIe4ZrlvS3ztD/0QwNKm7vVhemjOeJ4gpy', 'cliente', 1, '2026-09-26 20:05:00', '2026-07-19 08:50:00');

INSERT INTO administradores (id_administrador, id_usuario, nombres, apellidos, telefono) VALUES
  (1, 1, 'Ana Lucía', 'Martínez Cañas', '+503 2264-1800');

-- José Antonio Rivas se registra activo; se da de baja al final del script,
-- después de haber preparado el pedido 1 (así se conserva su trazabilidad).
INSERT INTO empleados (id_empleado, id_usuario, nombres, apellidos, dui, telefono, fecha_contratacion, estado) VALUES
  (1, 2, 'Carlos Ernesto',  'Mejía Rodríguez', '04587123-6', '+503 7012-3456', '2025-01-15', 'activo'),
  (2, 3, 'Andrea Beatriz',  'Portillo López',  '05213987-1', '+503 7845-2210', '2025-03-03', 'activo'),
  (3, 4, 'José Antonio',    'Rivas Quintanilla', '03891456-2', '+503 6123-9087', '2025-02-01', 'activo');

INSERT INTO clientes (id_cliente, id_usuario, nombres, apellidos, telefono, fecha_registro) VALUES
  (1, 5, 'María José',        'Hernández Castillo', '+503 7654-3210', '2025-06-14 18:30:00'),
  (2, 6, 'Luis Fernando',     'Guevara Molina',     '+503 7789-1122', '2025-08-02 10:05:00'),
  (3, 7, 'Karla Patricia',    'Flores Aguilar',     '+503 7390-5566', '2025-11-21 15:45:00'),
  (4, 8, 'Roberto Carlos',    'Ayala Sorto',        '+503 6030-7788', '2026-02-08 12:10:00'),
  (5, 9, 'Gabriela Alejandra','Menjívar Rosales',   '+503 7211-4433', '2026-07-19 08:50:00');

INSERT INTO direcciones (id_direccion, id_cliente, alias, direccion, referencia, id_distrito, es_principal) VALUES
  (1, 1, 'Casa',    'Colonia Escalón, 3.ª Calle Poniente, casa #4512', 'Portón negro frente al parque', @d_san_salvador, 1),
  (2, 1, 'Trabajo', 'Bulevar Merliot, Edificio Torre Uno, nivel 6',    'Recepción del edificio',        @d_antiguo_cusca, 0),
  (3, 2, 'Casa',    'Residencial Las Palmeras, Pasaje 5, casa #23',    'Cerca del redondel',            @d_santa_tecla, 1),
  (4, 3, 'Casa',    'Barrio San Rafael, 10.ª Avenida Sur, #18',         'A media cuadra de la iglesia',  @d_santa_ana, 1),
  (5, 4, 'Casa',    'Colonia Ciudad Jardín, Calle Los Almendros, #7',   'Casa esquinera color verde',    @d_san_miguel, 1),
  (6, 5, 'Casa',    'Colonia Zacamil, Edificio 34, apartamento 12',     'Frente a la cancha',            @d_mejicanos, 1);


-- =====================================================================
--  CATÁLOGO
-- =====================================================================

INSERT INTO categorias (id_categoria, nombre, descripcion) VALUES
  (1, 'Novela',               'Narrativa de ficción de largo aliento'),
  (2, 'Cuento',               'Colecciones de relatos breves'),
  (3, 'Poesía',               'Poemarios y antologías poéticas'),
  (4, 'Ensayo',               'Ensayo, memorias y crónica'),
  (5, 'Divulgación',          'Historia, ciencia y pensamiento para el público general'),
  (6, 'Clásicos universales', 'Obras fundamentales de la literatura mundial');

INSERT INTO editoriales (id_editorial, nombre, pais) VALUES
  (1, 'Penguin Random House',                   'España'),
  (2, 'Editorial Planeta',                      'España'),
  (3, 'Tusquets Editores',                      'España'),
  (4, 'UCA Editores',                           'El Salvador'),
  (5, 'Dirección de Publicaciones e Impresos',  'El Salvador');

INSERT INTO autores (id_autor, nombres, apellidos, nacionalidad) VALUES
  (1,  'Gabriel',         'García Márquez',     'Colombiana'),
  (2,  'Isabel',          'Allende',            'Chilena'),
  (3,  'Roque',           'Dalton',             'Salvadoreña'),
  (4,  'Salvador',        'Salazar Arrué',      'Salvadoreña'),
  (5,  'Horacio',         'Castellanos Moya',   'Salvadoreña'),
  (6,  'Claudia',         'Lars',               'Salvadoreña'),
  (7,  'Mario',           'Vargas Llosa',       'Peruana'),
  (8,  'Julio',           'Cortázar',           'Argentina'),
  (9,  'George',          'Orwell',             'Británica'),
  (10, 'Yuval Noah',      'Harari',             'Israelí');

-- Todos los libros entran con stock 0: el stock real se carga con
-- movimientos_inventario (más abajo) y lo aplica el trigger.
-- Los libros 14, 24 y 25 ingresaron hace pocos días (novedades por fecha);
-- 24 y 25 además tienen la marca es_novedad.
INSERT INTO libros (id_libro, isbn, titulo, sinopsis, id_categoria, id_editorial, anio_publicacion,
                    numero_paginas, precio, stock_minimo, es_novedad, fecha_ingreso) VALUES
  (1,  '9788430079193', 'Cien años de soledad',
       'La saga de la familia Buendía en el pueblo de Macondo.',                    1, 1, 1967, 496, 18.50,  5, 0, '2025-02-10 09:00:00'),
  (2,  '9788430158386', 'El amor en los tiempos del cólera',
       'Florentino Ariza espera más de medio siglo por el amor de Fermina Daza.',   1, 1, 1985, 464, 16.95,  5, 0, '2025-02-10 09:00:00'),
  (3,  '9788430237579', 'Crónica de una muerte anunciada',
       'Todo el pueblo sabía que iban a matar a Santiago Nasar.',                    1, 1, 1981, 144, 11.50,  5, 0, '2025-02-10 09:00:00'),
  (4,  '9788430316762', 'La casa de los espíritus',
       'Cuatro generaciones de la familia Trueba en un país latinoamericano.',       1, 1, 1982, 512, 17.25,  5, 0, '2025-03-05 09:00:00'),
  (5,  '9788430395958', 'Paula',
       'Carta de Isabel Allende a su hija y memoria de su propia vida.',             4, 1, 1994, 432, 15.00,  5, 0, '2025-03-05 09:00:00'),
  (6,  '9789992342220', 'Las historias prohibidas del Pulgarcito',
       'Collage poético sobre la historia de El Salvador.',                          3, 4, 1974, 240, 12.00,  5, 0, '2025-04-01 09:00:00'),
  (7,  '9789992342596', 'Taberna y otros lugares',
       'Poemario ganador del Premio Casa de las Américas 1969.',                      3, 4, 1969, 168, 10.50,  4, 0, '2025-04-01 09:00:00'),
  (8,  '9789992342961', 'Pobrecito poeta que era yo',
       'Novela sobre un grupo de jóvenes escritores salvadoreños.',                  1, 4, 1976, 528, 14.00,  5, 0, '2025-04-01 09:00:00'),
  (9,  '9789992343333', 'Cuentos de barro',
       'Relatos de la vida campesina salvadoreña.',                                  2, 5, 1933, 160,  9.00,  8, 0, '2025-01-20 09:00:00'),
  (10, '9789992343708', 'Cuentos de cipotes',
       'El mundo visto y contado por los niños salvadoreños.',                       2, 5, 1945, 176,  8.75,  8, 0, '2025-01-20 09:00:00'),
  (11, '9789992344071', 'O''Yarkandal',
       'Cuentos fantásticos de tierras imaginarias.',                               2, 5, 1929, 144,  9.50,  3, 0, '2025-01-20 09:00:00'),
  (12, '9788430950287', 'El asco',
       'Monólogo feroz de un salvadoreño que regresa a su país.',                    1, 3, 1997, 112, 12.00,  5, 0, '2025-05-12 09:00:00'),
  (13, '9788431029470', 'Insensatez',
       'Un escritor corrige un informe sobre masacres en un país centroamericano.',  1, 3, 2004, 160, 14.50,  5, 0, '2025-05-12 09:00:00'),
  (14, '9788431108663', 'La diabla en el espejo',
       'Laura Rivera investiga el asesinato de su mejor amiga en San Salvador.',     1, 3, 2000, 208, 15.50,  5, 0, NOW() - INTERVAL 12 DAY),
  (15, '9789992345559', 'Tierra de infancia',
       'Recuerdos de la niñez de la autora en el occidente salvadoreño.',            4, 5, 1958, 184, 10.00,  5, 0, '2025-06-02 09:00:00'),
  (16, '9788431267049', 'La ciudad y los perros',
       'La vida de los cadetes del Colegio Militar Leoncio Prado en Lima.',          1, 1, 1963, 448, 19.00,  5, 0, '2025-06-20 09:00:00'),
  (17, '9788431346232', 'La fiesta del chivo',
       'Los últimos días de la dictadura de Trujillo en República Dominicana.',      1, 1, 2000, 576, 21.00,  5, 0, '2025-06-20 09:00:00'),
  (18, '9788431425425', 'Rayuela',
       'Una novela que puede leerse en más de un orden.',                            1, 1, 1963, 736, 19.95,  5, 0, '2025-07-08 09:00:00'),
  (19, '9788431504618', 'Bestiario',
       'Primer libro de cuentos de Cortázar, incluye «Casa tomada».',                2, 1, 1951, 160, 13.50,  5, 0, '2025-07-08 09:00:00'),
  (20, '9788431583804', '1984',
       'Winston Smith vive bajo la vigilancia constante del Gran Hermano.',          6, 2, 1949, 352, 12.95, 10, 0, '2025-02-25 09:00:00'),
  (21, '9788431662998', 'Rebelión en la granja',
       'Los animales de una granja se rebelan contra sus dueños.',                   6, 2, 1945, 144,  9.95, 10, 0, '2025-02-25 09:00:00'),
  (22, '9788431742188', 'Sapiens. De animales a dioses',
       'Breve historia de la humanidad desde la Edad de Piedra.',                    5, 1, 2011, 496, 22.95,  5, 0, '2025-09-15 09:00:00'),
  (23, '9788431821371', 'Homo Deus. Breve historia del mañana',
       'Los desafíos que enfrentará la humanidad en este siglo.',                    5, 1, 2015, 496, 21.50,  5, 0, '2025-09-15 09:00:00'),
  (24, '9788431900564', '21 lecciones para el siglo XXI',
       'Reflexiones sobre los grandes temas del presente.',                          5, 1, 2018, 408, 20.75,  5, 1, NOW() - INTERVAL 5 DAY),
  (25, '9789992349250', 'Antología de poesía salvadoreña',
       'Selección de poemas de Roque Dalton y Claudia Lars.',                        3, 5, 2026, 220, 13.00,  5, 1, NOW() - INTERVAL 3 DAY);

-- El libro 25 tiene dos autores (relación muchos a muchos).
INSERT INTO libros_autores (id_libro, id_autor, orden) VALUES
  (1, 1, 1), (2, 1, 1), (3, 1, 1),
  (4, 2, 1), (5, 2, 1),
  (6, 3, 1), (7, 3, 1), (8, 3, 1),
  (9, 4, 1), (10, 4, 1), (11, 4, 1),
  (12, 5, 1), (13, 5, 1), (14, 5, 1),
  (15, 6, 1),
  (16, 7, 1), (17, 7, 1),
  (18, 8, 1), (19, 8, 1),
  (20, 9, 1), (21, 9, 1),
  (22, 10, 1), (23, 10, 1), (24, 10, 1),
  (25, 3, 1), (25, 6, 2);


-- =====================================================================
--  INVENTARIO: carga inicial y una compra a proveedor
--  (el trigger llena stock_anterior/stock_nuevo y actualiza libros)
-- =====================================================================

INSERT INTO movimientos_inventario (id_libro, tipo, motivo, cantidad, id_usuario, observacion, fecha) VALUES
  (1,  'entrada', 'inventario_inicial', 20, 1, 'Carga inicial de inventario', '2025-02-10 09:00:00'),
  (2,  'entrada', 'inventario_inicial', 12, 1, 'Carga inicial de inventario', '2025-02-10 09:00:00'),
  (3,  'entrada', 'inventario_inicial', 15, 1, 'Carga inicial de inventario', '2025-02-10 09:00:00'),
  (4,  'entrada', 'inventario_inicial',  8, 1, 'Carga inicial de inventario', '2025-03-05 09:00:00'),
  (5,  'entrada', 'inventario_inicial',  3, 1, 'Carga inicial de inventario', '2025-03-05 09:00:00'),
  (6,  'entrada', 'inventario_inicial',  6, 1, 'Carga inicial de inventario', '2025-04-01 09:00:00'),
  (7,  'entrada', 'inventario_inicial',  2, 1, 'Carga inicial de inventario', '2025-04-01 09:00:00'),
  (8,  'entrada', 'inventario_inicial',  4, 1, 'Carga inicial de inventario', '2025-04-01 09:00:00'),
  (9,  'entrada', 'inventario_inicial', 25, 1, 'Carga inicial de inventario', '2025-01-20 09:00:00'),
  (10, 'entrada', 'inventario_inicial', 18, 1, 'Carga inicial de inventario', '2025-01-20 09:00:00'),
  (11, 'entrada', 'inventario_inicial',  1, 1, 'Carga inicial de inventario', '2025-01-20 09:00:00'),
  (12, 'entrada', 'inventario_inicial', 10, 1, 'Carga inicial de inventario', '2025-05-12 09:00:00'),
  (13, 'entrada', 'inventario_inicial',  7, 1, 'Carga inicial de inventario', '2025-05-12 09:00:00'),
  (14, 'entrada', 'inventario_inicial',  5, 1, 'Carga inicial de inventario', NOW() - INTERVAL 12 DAY),
  (15, 'entrada', 'inventario_inicial',  9, 1, 'Carga inicial de inventario', '2025-06-02 09:00:00'),
  (16, 'entrada', 'inventario_inicial',  6, 1, 'Carga inicial de inventario', '2025-06-20 09:00:00'),
  (17, 'entrada', 'inventario_inicial',  9, 1, 'Carga inicial de inventario', '2025-06-20 09:00:00'),
  (18, 'entrada', 'inventario_inicial',  7, 1, 'Carga inicial de inventario', '2025-07-08 09:00:00'),
  (19, 'entrada', 'inventario_inicial',  5, 1, 'Carga inicial de inventario', '2025-07-08 09:00:00'),
  (20, 'entrada', 'inventario_inicial', 30, 1, 'Carga inicial de inventario', '2025-02-25 09:00:00'),
  (21, 'entrada', 'inventario_inicial', 22, 1, 'Carga inicial de inventario', '2025-02-25 09:00:00'),
  (22, 'entrada', 'inventario_inicial', 10, 1, 'Carga inicial de inventario', '2025-09-15 09:00:00'),
  (23, 'entrada', 'inventario_inicial',  6, 1, 'Carga inicial de inventario', '2025-09-15 09:00:00'),
  (24, 'entrada', 'inventario_inicial', 12, 1, 'Carga inicial de inventario', NOW() - INTERVAL 5 DAY),
  (25, 'entrada', 'inventario_inicial',  3, 1, 'Carga inicial de inventario', NOW() - INTERVAL 3 DAY);

INSERT INTO movimientos_inventario (id_libro, tipo, motivo, cantidad, id_usuario, observacion, fecha) VALUES
  (9, 'entrada', 'compra', 10, 2, 'Reposición: factura DPI #2026-0418', '2026-07-30 10:30:00');


-- =====================================================================
--  CARRITOS
-- =====================================================================

INSERT INTO carritos (id_carrito, id_cliente) VALUES (1, 2), (2, 5);

INSERT INTO carrito_detalles (id_carrito, id_libro, cantidad) VALUES
  (1, 16, 1),
  (2, 24, 1),
  (2, 25, 1);


-- =====================================================================
--  PEDIDOS
--  Se insertan como 'pendiente' y luego se avanzan de estado con UPDATE,
--  igual que lo haría la aplicación. Los triggers calculan montos y
--  llenan el historial de seguimiento.
-- =====================================================================

-- Pedido 1: María José, entregado, preparado por José Rivas (luego dado de baja).
-- Subtotal 18.50 + 2 x 9.00 = 36.50 -> envío 4.00 -> total 40.50
SET @id_usuario_actual = 5, @comentario_estado = NULL;
INSERT INTO pedidos (id_pedido, id_cliente, direccion_entrega, referencia_entrega, id_distrito, telefono_contacto, fecha_pedido)
VALUES (1, 1, 'Colonia Escalón, 3.ª Calle Poniente, casa #4512', 'Portón negro frente al parque',
        @d_san_salvador, '+503 7654-3210', '2026-08-03 10:15:00');
INSERT INTO pedido_detalles (id_pedido, id_libro, cantidad, precio_unitario) VALUES
  (1, 1, 1, 18.50),
  (1, 9, 2,  9.00);

SET @id_usuario_actual = 4, @comentario_estado = 'Pedido asignado para preparación';
UPDATE pedidos SET estado = 'en_preparacion', id_empleado = 3 WHERE id_pedido = 1;
INSERT INTO movimientos_inventario (id_libro, tipo, motivo, cantidad, id_usuario, id_pedido, observacion, fecha) VALUES
  (1, 'salida', 'venta', 1, 4, 1, 'Venta pedido #1', '2026-08-04 04:15:00'),
  (9, 'salida', 'venta', 2, 4, 1, 'Venta pedido #1', '2026-08-04 04:15:00');
SET @comentario_estado = 'Entregado al repartidor';
UPDATE pedidos SET estado = 'enviado' WHERE id_pedido = 1;
SET @comentario_estado = 'Recibido por la clienta';
UPDATE pedidos SET estado = 'entregado' WHERE id_pedido = 1;

-- Pedido 2: Luis Fernando, entregado, supera $50.00 -> ENVÍO GRATIS.
-- Subtotal 22.95 + 21.50 + 12.95 = 57.40 -> envío 0.00 -> total 57.40
SET @id_usuario_actual = 6, @comentario_estado = NULL;
INSERT INTO pedidos (id_pedido, id_cliente, direccion_entrega, referencia_entrega, id_distrito, telefono_contacto, fecha_pedido)
VALUES (2, 2, 'Residencial Las Palmeras, Pasaje 5, casa #23', 'Cerca del redondel',
        @d_santa_tecla, '+503 7789-1122', '2026-08-10 16:40:00');
INSERT INTO pedido_detalles (id_pedido, id_libro, cantidad, precio_unitario) VALUES
  (2, 22, 1, 22.95),
  (2, 23, 1, 21.50),
  (2, 20, 1, 12.95);

SET @id_usuario_actual = 2, @comentario_estado = 'Pedido asignado para preparación';
UPDATE pedidos SET estado = 'en_preparacion', id_empleado = 1 WHERE id_pedido = 2;
INSERT INTO movimientos_inventario (id_libro, tipo, motivo, cantidad, id_usuario, id_pedido, observacion, fecha) VALUES
  (22, 'salida', 'venta', 1, 2, 2, 'Venta pedido #2', '2026-08-11 10:40:00'),
  (23, 'salida', 'venta', 1, 2, 2, 'Venta pedido #2', '2026-08-11 10:40:00'),
  (20, 'salida', 'venta', 1, 2, 2, 'Venta pedido #2', '2026-08-11 10:40:00');
SET @comentario_estado = 'Entregado al repartidor';
UPDATE pedidos SET estado = 'enviado' WHERE id_pedido = 2;
SET @comentario_estado = 'Recibido por el cliente';
UPDATE pedidos SET estado = 'entregado' WHERE id_pedido = 2;

-- Pedido 3: Karla Patricia, enviado (en camino), preparado por Andrea Portillo.
-- Subtotal 19.95 + 13.50 = 33.45 -> envío 4.00 -> total 37.45
SET @id_usuario_actual = 7, @comentario_estado = NULL;
INSERT INTO pedidos (id_pedido, id_cliente, direccion_entrega, referencia_entrega, id_distrito, telefono_contacto, fecha_pedido)
VALUES (3, 3, 'Barrio San Rafael, 10.ª Avenida Sur, #18', 'A media cuadra de la iglesia',
        @d_santa_ana, '+503 7390-5566', '2026-09-12 09:05:00');
INSERT INTO pedido_detalles (id_pedido, id_libro, cantidad, precio_unitario) VALUES
  (3, 18, 1, 19.95),
  (3, 19, 1, 13.50);

SET @id_usuario_actual = 3, @comentario_estado = 'Pedido asignado para preparación';
UPDATE pedidos SET estado = 'en_preparacion', id_empleado = 2 WHERE id_pedido = 3;
INSERT INTO movimientos_inventario (id_libro, tipo, motivo, cantidad, id_usuario, id_pedido, observacion, fecha) VALUES
  (18, 'salida', 'venta', 1, 3, 3, 'Venta pedido #3', '2026-09-13 03:05:00'),
  (19, 'salida', 'venta', 1, 3, 3, 'Venta pedido #3', '2026-09-13 03:05:00');
SET @comentario_estado = 'Enviado por encomienda a Santa Ana';
UPDATE pedidos SET estado = 'enviado' WHERE id_pedido = 3;

-- Pedido 4: Roberto Carlos, en preparación, supera $50.00 -> ENVÍO GRATIS.
-- Subtotal 21.00 + 17.25 + 11.50 + 8.75 = 58.50 -> envío 0.00 -> total 58.50
SET @id_usuario_actual = 8, @comentario_estado = NULL;
INSERT INTO pedidos (id_pedido, id_cliente, direccion_entrega, referencia_entrega, id_distrito, telefono_contacto, fecha_pedido)
VALUES (4, 4, 'Colonia Ciudad Jardín, Calle Los Almendros, #7', 'Casa esquinera color verde',
        @d_san_miguel, '+503 6030-7788', '2026-09-22 18:30:00');
INSERT INTO pedido_detalles (id_pedido, id_libro, cantidad, precio_unitario) VALUES
  (4, 17, 1, 21.00),
  (4,  4, 1, 17.25),
  (4,  3, 1, 11.50),
  (4, 10, 1,  8.75);

SET @id_usuario_actual = 2, @comentario_estado = 'Pedido asignado para preparación';
UPDATE pedidos SET estado = 'en_preparacion', id_empleado = 1 WHERE id_pedido = 4;
INSERT INTO movimientos_inventario (id_libro, tipo, motivo, cantidad, id_usuario, id_pedido, observacion, fecha) VALUES
  (17, 'salida', 'venta', 1, 2, 4, 'Venta pedido #4', '2026-09-23 12:30:00'),
  (4,  'salida', 'venta', 1, 2, 4, 'Venta pedido #4', '2026-09-23 12:30:00'),
  (3,  'salida', 'venta', 1, 2, 4, 'Venta pedido #4', '2026-09-23 12:30:00'),
  (10, 'salida', 'venta', 1, 2, 4, 'Venta pedido #4', '2026-09-23 12:30:00');

-- Pedido 5: Gabriela Alejandra, pendiente. Subtotal EXACTO de $50.00:
-- como no SUPERA $50.00, sí paga envío.
-- Subtotal 2 x 12.00 + 14.50 + 11.50 = 50.00 -> envío 4.00 -> total 54.00
SET @id_usuario_actual = 9, @comentario_estado = NULL;
INSERT INTO pedidos (id_pedido, id_cliente, direccion_entrega, referencia_entrega, id_distrito, telefono_contacto, notas, fecha_pedido)
VALUES (5, 5, 'Colonia Zacamil, Edificio 34, apartamento 12', 'Frente a la cancha',
        @d_mejicanos, '+503 7211-4433', 'Llamar antes de llegar', '2026-09-26 20:10:00');
INSERT INTO pedido_detalles (id_pedido, id_libro, cantidad, precio_unitario) VALUES
  (5, 12, 2, 12.00),
  (5, 13, 1, 14.50),
  (5,  3, 1, 11.50);

-- Pedido 6: Karla Patricia, cancelado por la clienta antes de prepararse.
-- Subtotal 9.95 -> envío 4.00 -> total 13.95
SET @id_usuario_actual = 7, @comentario_estado = NULL;
INSERT INTO pedidos (id_pedido, id_cliente, direccion_entrega, referencia_entrega, id_distrito, telefono_contacto, fecha_pedido)
VALUES (6, 3, 'Barrio San Rafael, 10.ª Avenida Sur, #18', 'A media cuadra de la iglesia',
        @d_santa_ana, '+503 7390-5566', '2026-09-05 11:00:00');
INSERT INTO pedido_detalles (id_pedido, id_libro, cantidad, precio_unitario) VALUES
  (6, 21, 1, 9.95);
SET @comentario_estado = 'Cancelado por la clienta';
UPDATE pedidos SET estado = 'cancelado' WHERE id_pedido = 6;

-- Los triggers fechan el historial con NOW(); para que el seguimiento de
-- prueba sea coherente, cada cambio se ubica 18 horas después del anterior
-- a partir de la fecha del pedido.
-- (Se usa una tabla temporal porque MySQL 8 no permite leer y actualizar
-- la misma tabla en una sola sentencia.)
CREATE TEMPORARY TABLE tmp_primer_historial AS
  SELECT id_pedido, MIN(id_historial) AS primero
  FROM historial_estados_pedido
  GROUP BY id_pedido;

UPDATE historial_estados_pedido h
JOIN pedidos p              ON p.id_pedido = h.id_pedido
JOIN tmp_primer_historial x ON x.id_pedido = h.id_pedido
SET h.fecha_cambio = p.fecha_pedido + INTERVAL ((h.id_historial - x.primero) * 18) HOUR;

DROP TEMPORARY TABLE tmp_primer_historial;

-- Pagos: los pedidos de prueba se pagaron contra entrega.
-- Entregado = cobrado; cancelado = no se cobró; el resto sigue pendiente.
INSERT INTO pagos (id_pedido, metodo, estado, monto, fecha_creacion)
SELECT id_pedido, 'contra_entrega',
       CASE estado WHEN 'entregado' THEN 'completado' WHEN 'cancelado' THEN 'cancelado' ELSE 'pendiente' END,
       total, fecha_pedido
  FROM pedidos;


-- =====================================================================
--  BAJA LÓGICA DE UN EMPLEADO
--  José Rivas deja la empresa. El trigger desactiva su usuario, pero el
--  pedido 1 conserva quién lo preparó.
-- =====================================================================

UPDATE empleados
   SET estado = 'baja', fecha_baja = '2026-08-31 17:00:00', motivo_baja = 'Renuncia voluntaria'
 WHERE id_empleado = 3;

-- Limpieza de variables de sesión.
SET @id_usuario_actual = NULL;
SET @comentario_estado = NULL;
