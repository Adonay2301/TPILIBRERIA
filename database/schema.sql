-- =====================================================================
--  PUNTO Y APARTE - Librería en línea (El Salvador)
--  schema.sql: estructura completa de la base de datos
--
--  Compatible con MariaDB 10.4+ (XAMPP) y MySQL 8.0.16+
--  Orden de ejecución: 1) schema.sql  2) ubicaciones.sql  3) seed.sql
--
--  ATENCIÓN: este script BORRA y vuelve a crear todas las tablas.
-- =====================================================================

CREATE DATABASE IF NOT EXISTS punto_y_aparte
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE punto_y_aparte;

SET NAMES utf8mb4;

-- ---------------------------------------------------------------------
-- Limpieza: vistas y tablas en orden inverso de dependencias.
-- Al borrar una tabla también se borran sus triggers.
-- ---------------------------------------------------------------------
SET FOREIGN_KEY_CHECKS = 0;

DROP VIEW IF EXISTS vw_novedades;
DROP VIEW IF EXISTS vw_stock_bajo;

DROP TABLE IF EXISTS historial_estados_pedido;
DROP TABLE IF EXISTS pedido_detalles;
DROP TABLE IF EXISTS pedidos;
DROP TABLE IF EXISTS carrito_detalles;
DROP TABLE IF EXISTS carritos;
DROP TABLE IF EXISTS movimientos_inventario;
DROP TABLE IF EXISTS libros_autores;
DROP TABLE IF EXISTS libros;
DROP TABLE IF EXISTS editoriales;
DROP TABLE IF EXISTS categorias;
DROP TABLE IF EXISTS autores;
DROP TABLE IF EXISTS direcciones;
DROP TABLE IF EXISTS administradores;
DROP TABLE IF EXISTS empleados;
DROP TABLE IF EXISTS clientes;
DROP TABLE IF EXISTS usuarios;
DROP TABLE IF EXISTS distritos;
DROP TABLE IF EXISTS municipios;
DROP TABLE IF EXISTS departamentos;

SET FOREIGN_KEY_CHECKS = 1;


-- =====================================================================
--  1. UBICACIÓN (división territorial vigente desde 2024)
--     departamento (14) -> municipio (44) -> distrito (262)
-- =====================================================================

-- Departamentos de El Salvador.
CREATE TABLE departamentos (
  id_departamento INT UNSIGNED NOT NULL AUTO_INCREMENT,
  nombre          VARCHAR(50)  NOT NULL,
  PRIMARY KEY (id_departamento),
  UNIQUE KEY uq_departamentos_nombre (nombre)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Los 14 departamentos de El Salvador';

-- Municipios creados por la reestructuración de 2024 (ej. San Salvador Centro).
CREATE TABLE municipios (
  id_municipio    INT UNSIGNED NOT NULL AUTO_INCREMENT,
  id_departamento INT UNSIGNED NOT NULL,
  nombre          VARCHAR(60)  NOT NULL,
  PRIMARY KEY (id_municipio),
  UNIQUE KEY uq_municipios_nombre (nombre),
  KEY idx_municipios_departamento (id_departamento),
  CONSTRAINT fk_municipios_departamento FOREIGN KEY (id_departamento)
    REFERENCES departamentos (id_departamento) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Los 44 municipios vigentes desde 2024; cada uno pertenece a un departamento';

-- Distritos (los antiguos 262 municipios). Hay nombres repetidos en
-- distintos municipios (ej. San Isidro, San Rafael), por eso el UNIQUE es compuesto.
CREATE TABLE distritos (
  id_distrito  INT UNSIGNED NOT NULL AUTO_INCREMENT,
  id_municipio INT UNSIGNED NOT NULL,
  nombre       VARCHAR(80)  NOT NULL,
  PRIMARY KEY (id_distrito),
  UNIQUE KEY uq_distritos_municipio_nombre (id_municipio, nombre),
  KEY idx_distritos_nombre (nombre),
  CONSTRAINT fk_distritos_municipio FOREIGN KEY (id_municipio)
    REFERENCES municipios (id_municipio) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Los 262 distritos (antiguos municipios); cada uno pertenece a un municipio';


-- =====================================================================
--  2. SEGURIDAD Y USUARIOS
--     usuarios guarda el acceso (correo, contraseña, rol).
--     clientes / empleados / administradores guardan el perfil (1 a 1).
-- =====================================================================

-- Credenciales de acceso. El rol se detecta en el login a partir de esta tabla.
CREATE TABLE usuarios (
  id_usuario     INT UNSIGNED NOT NULL AUTO_INCREMENT,
  correo         VARCHAR(150) NOT NULL,
  contrasena     VARCHAR(255) NOT NULL COMMENT 'Hash generado con password_hash() de PHP',
  rol            ENUM('cliente','empleado','administrador') NOT NULL DEFAULT 'cliente',
  activo         TINYINT(1)   NOT NULL DEFAULT 1 COMMENT '0 = no puede iniciar sesión',
  ultimo_acceso  DATETIME     NULL,
  fecha_creacion DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id_usuario),
  UNIQUE KEY uq_usuarios_correo (correo),
  KEY idx_usuarios_rol (rol),
  CONSTRAINT chk_usuarios_activo CHECK (activo IN (0, 1))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Cuentas de acceso al sistema con su rol (cliente, empleado o administrador)';

-- Perfil de los clientes registrados (los únicos que pueden comprar).
CREATE TABLE clientes (
  id_cliente     INT UNSIGNED NOT NULL AUTO_INCREMENT,
  id_usuario     INT UNSIGNED NOT NULL,
  nombres        VARCHAR(80)  NOT NULL,
  apellidos      VARCHAR(80)  NOT NULL,
  telefono       VARCHAR(15)  NULL COMMENT 'Formato +503 7123-4567',
  fecha_registro DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id_cliente),
  UNIQUE KEY uq_clientes_usuario (id_usuario),
  KEY idx_clientes_apellidos (apellidos, nombres),
  CONSTRAINT fk_clientes_usuario FOREIGN KEY (id_usuario)
    REFERENCES usuarios (id_usuario) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT chk_clientes_telefono CHECK (telefono IS NULL OR telefono REGEXP '^\\+503 [267][0-9]{3}-[0-9]{4}$')
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Datos personales de los clientes registrados';

-- Perfil de los empleados. Un único rol, sin cargos ni turnos.
-- Nunca se eliminan: se dan de baja con estado = 'baja' y fecha_baja.
CREATE TABLE empleados (
  id_empleado        INT UNSIGNED NOT NULL AUTO_INCREMENT,
  id_usuario         INT UNSIGNED NOT NULL,
  nombres            VARCHAR(80)  NOT NULL,
  apellidos          VARCHAR(80)  NOT NULL,
  dui                CHAR(10)     NOT NULL COMMENT 'Documento Único de Identidad, formato 01234567-8',
  telefono           VARCHAR(15)  NULL COMMENT 'Formato +503 7123-4567',
  fecha_contratacion DATE         NOT NULL,
  estado             ENUM('activo','baja') NOT NULL DEFAULT 'activo',
  fecha_baja         DATETIME     NULL COMMENT 'Se llena al dar de baja al empleado',
  motivo_baja        VARCHAR(255) NULL,
  PRIMARY KEY (id_empleado),
  UNIQUE KEY uq_empleados_usuario (id_usuario),
  UNIQUE KEY uq_empleados_dui (dui),
  KEY idx_empleados_estado (estado),
  CONSTRAINT fk_empleados_usuario FOREIGN KEY (id_usuario)
    REFERENCES usuarios (id_usuario) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT chk_empleados_dui CHECK (dui REGEXP '^[0-9]{8}-[0-9]$'),
  CONSTRAINT chk_empleados_telefono CHECK (telefono IS NULL OR telefono REGEXP '^\\+503 [267][0-9]{3}-[0-9]{4}$'),
  CONSTRAINT chk_empleados_baja CHECK (
    (estado = 'activo' AND fecha_baja IS NULL) OR
    (estado = 'baja'   AND fecha_baja IS NOT NULL)
  )
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Empleados de la librería; baja lógica para conservar su historial';

-- Perfil de los administradores del sistema.
CREATE TABLE administradores (
  id_administrador INT UNSIGNED NOT NULL AUTO_INCREMENT,
  id_usuario       INT UNSIGNED NOT NULL,
  nombres          VARCHAR(80)  NOT NULL,
  apellidos        VARCHAR(80)  NOT NULL,
  telefono         VARCHAR(15)  NULL COMMENT 'Formato +503 7123-4567',
  PRIMARY KEY (id_administrador),
  UNIQUE KEY uq_administradores_usuario (id_usuario),
  CONSTRAINT fk_administradores_usuario FOREIGN KEY (id_usuario)
    REFERENCES usuarios (id_usuario) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT chk_administradores_telefono CHECK (telefono IS NULL OR telefono REGEXP '^\\+503 [267][0-9]{3}-[0-9]{4}$')
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Datos personales de los administradores del sistema';

-- Libreta de direcciones del cliente. Solo guarda el distrito:
-- municipio y departamento se obtienen por las relaciones.
CREATE TABLE direcciones (
  id_direccion   INT UNSIGNED NOT NULL AUTO_INCREMENT,
  id_cliente     INT UNSIGNED NOT NULL,
  alias          VARCHAR(40)  NOT NULL DEFAULT 'Casa' COMMENT 'Ej. Casa, Trabajo',
  direccion      VARCHAR(255) NOT NULL COMMENT 'Colonia, calle, pasaje, número de casa',
  referencia     VARCHAR(255) NULL COMMENT 'Punto de referencia para el repartidor',
  id_distrito    INT UNSIGNED NOT NULL,
  es_principal   TINYINT(1)   NOT NULL DEFAULT 0,
  fecha_creacion DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id_direccion),
  KEY idx_direcciones_cliente (id_cliente),
  KEY idx_direcciones_distrito (id_distrito),
  CONSTRAINT fk_direcciones_cliente FOREIGN KEY (id_cliente)
    REFERENCES clientes (id_cliente) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT fk_direcciones_distrito FOREIGN KEY (id_distrito)
    REFERENCES distritos (id_distrito) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT chk_direcciones_principal CHECK (es_principal IN (0, 1))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Direcciones de entrega guardadas por cada cliente';


-- =====================================================================
--  3. CATÁLOGO
-- =====================================================================

-- Autores de los libros.
CREATE TABLE autores (
  id_autor     INT UNSIGNED NOT NULL AUTO_INCREMENT,
  nombres      VARCHAR(80)  NOT NULL,
  apellidos    VARCHAR(80)  NOT NULL,
  nacionalidad VARCHAR(60)  NULL,
  biografia    TEXT         NULL,
  PRIMARY KEY (id_autor),
  UNIQUE KEY uq_autores_nombre_completo (nombres, apellidos),
  KEY idx_autores_apellidos (apellidos)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Autores de los libros del catálogo';

-- Categorías o géneros del catálogo.
CREATE TABLE categorias (
  id_categoria INT UNSIGNED NOT NULL AUTO_INCREMENT,
  nombre       VARCHAR(60)  NOT NULL,
  descripcion  VARCHAR(255) NULL,
  PRIMARY KEY (id_categoria),
  UNIQUE KEY uq_categorias_nombre (nombre)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Categorías o géneros literarios del catálogo';

-- Editoriales que publican los libros.
CREATE TABLE editoriales (
  id_editorial INT UNSIGNED NOT NULL AUTO_INCREMENT,
  nombre       VARCHAR(100) NOT NULL,
  pais         VARCHAR(60)  NULL,
  PRIMARY KEY (id_editorial),
  UNIQUE KEY uq_editoriales_nombre (nombre)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Editoriales de los libros';

-- Libros del catálogo, con su inventario (stock actual y mínimo).
-- Novedades: es_novedad = 1 o fecha_ingreso de los últimos 30 días (ver vw_novedades).
CREATE TABLE libros (
  id_libro            INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  isbn                CHAR(13)      NOT NULL COMMENT 'ISBN-13 sin guiones',
  titulo              VARCHAR(200)  NOT NULL,
  sinopsis            TEXT          NULL,
  id_categoria        INT UNSIGNED  NOT NULL,
  id_editorial        INT UNSIGNED  NOT NULL,
  anio_publicacion    SMALLINT UNSIGNED NULL,
  numero_paginas      SMALLINT UNSIGNED NULL,
  idioma              VARCHAR(30)   NOT NULL DEFAULT 'Español',
  precio              DECIMAL(10,2) NOT NULL COMMENT 'Precio de venta en USD',
  stock_actual        INT           NOT NULL DEFAULT 0 COMMENT 'Se actualiza con movimientos_inventario',
  stock_minimo        INT           NOT NULL DEFAULT 5 COMMENT 'Por debajo o igual se muestra en Stock bajo',
  portada             VARCHAR(255)  NULL COMMENT 'Ruta o URL de la imagen de portada',
  es_novedad          TINYINT(1)    NOT NULL DEFAULT 0 COMMENT 'Marca manual de novedad',
  activo              TINYINT(1)    NOT NULL DEFAULT 1 COMMENT '0 = oculto del catálogo público',
  fecha_ingreso       DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  fecha_actualizacion DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id_libro),
  UNIQUE KEY uq_libros_isbn (isbn),
  KEY idx_libros_titulo (titulo),
  KEY idx_libros_categoria (id_categoria),
  KEY idx_libros_editorial (id_editorial),
  KEY idx_libros_catalogo (activo, id_categoria, precio),
  KEY idx_libros_novedades (es_novedad, fecha_ingreso),
  KEY idx_libros_stock (stock_actual, stock_minimo),
  FULLTEXT KEY ft_libros_busqueda (titulo, sinopsis),
  CONSTRAINT fk_libros_categoria FOREIGN KEY (id_categoria)
    REFERENCES categorias (id_categoria) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT fk_libros_editorial FOREIGN KEY (id_editorial)
    REFERENCES editoriales (id_editorial) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT chk_libros_isbn CHECK (isbn REGEXP '^97[89][0-9]{10}$'),
  CONSTRAINT chk_libros_precio CHECK (precio > 0),
  CONSTRAINT chk_libros_stock_actual CHECK (stock_actual >= 0),
  CONSTRAINT chk_libros_stock_minimo CHECK (stock_minimo >= 0),
  CONSTRAINT chk_libros_flags CHECK (es_novedad IN (0, 1) AND activo IN (0, 1))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Libros del catálogo con precio e inventario';

-- Relación muchos a muchos entre libros y autores.
CREATE TABLE libros_autores (
  id_libro INT UNSIGNED     NOT NULL,
  id_autor INT UNSIGNED     NOT NULL,
  orden    TINYINT UNSIGNED NOT NULL DEFAULT 1 COMMENT 'Orden del autor en la portada',
  PRIMARY KEY (id_libro, id_autor),
  KEY idx_libros_autores_autor (id_autor),
  CONSTRAINT fk_libros_autores_libro FOREIGN KEY (id_libro)
    REFERENCES libros (id_libro) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT fk_libros_autores_autor FOREIGN KEY (id_autor)
    REFERENCES autores (id_autor) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Autores de cada libro (un libro puede tener varios autores)';


-- =====================================================================
--  4. INVENTARIO
-- =====================================================================

-- Bitácora de entradas y salidas. Un trigger aplica cada movimiento a
-- libros.stock_actual y rechaza salidas que dejarían el stock en negativo.
CREATE TABLE movimientos_inventario (
  id_movimiento  INT UNSIGNED NOT NULL AUTO_INCREMENT,
  id_libro       INT UNSIGNED NOT NULL,
  tipo           ENUM('entrada','salida') NOT NULL,
  motivo         ENUM('inventario_inicial','compra','venta','devolucion','ajuste') NOT NULL,
  cantidad       INT          NOT NULL,
  stock_anterior INT          NULL COMMENT 'Lo llena el trigger',
  stock_nuevo    INT          NULL COMMENT 'Lo llena el trigger',
  id_usuario     INT UNSIGNED NULL COMMENT 'Quién registró el movimiento',
  id_pedido      INT UNSIGNED NULL COMMENT 'Pedido relacionado (ventas y devoluciones)',
  observacion    VARCHAR(255) NULL,
  fecha          DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id_movimiento),
  KEY idx_movimientos_libro_fecha (id_libro, fecha),
  KEY idx_movimientos_usuario (id_usuario),
  KEY idx_movimientos_pedido (id_pedido),
  CONSTRAINT fk_movimientos_libro FOREIGN KEY (id_libro)
    REFERENCES libros (id_libro) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT fk_movimientos_usuario FOREIGN KEY (id_usuario)
    REFERENCES usuarios (id_usuario) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT chk_movimientos_cantidad CHECK (cantidad > 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Historial de entradas y salidas de inventario por libro';
-- La llave foránea hacia pedidos se agrega más abajo, después de crear esa tabla.


-- =====================================================================
--  5. CARRITO
-- =====================================================================

-- Un carrito por cliente.
CREATE TABLE carritos (
  id_carrito          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  id_cliente          INT UNSIGNED NOT NULL,
  fecha_creacion      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  fecha_actualizacion DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id_carrito),
  UNIQUE KEY uq_carritos_cliente (id_cliente),
  CONSTRAINT fk_carritos_cliente FOREIGN KEY (id_cliente)
    REFERENCES clientes (id_cliente) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Carrito de compras activo de cada cliente';

-- Libros dentro del carrito. Es el único CASCADE del modelo: el carrito
-- es temporal y no guarda historial, así que vaciarlo no pierde información.
CREATE TABLE carrito_detalles (
  id_carrito_detalle INT UNSIGNED NOT NULL AUTO_INCREMENT,
  id_carrito         INT UNSIGNED NOT NULL,
  id_libro           INT UNSIGNED NOT NULL,
  cantidad           INT          NOT NULL DEFAULT 1,
  fecha_agregado     DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id_carrito_detalle),
  UNIQUE KEY uq_carrito_detalles_libro (id_carrito, id_libro),
  KEY idx_carrito_detalles_libro (id_libro),
  CONSTRAINT fk_carrito_detalles_carrito FOREIGN KEY (id_carrito)
    REFERENCES carritos (id_carrito) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_carrito_detalles_libro FOREIGN KEY (id_libro)
    REFERENCES libros (id_libro) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT chk_carrito_detalles_cantidad CHECK (cantidad > 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Libros y cantidades agregados a cada carrito';


-- =====================================================================
--  6. PEDIDOS
-- =====================================================================

-- Encabezado del pedido. subtotal, costo_envio y total los calculan los
-- triggers: no hace falta enviarlos desde PHP.
-- La dirección se copia al momento de comprar para que no cambie si el
-- cliente edita después su libreta de direcciones.
CREATE TABLE pedidos (
  id_pedido           INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  id_cliente          INT UNSIGNED  NOT NULL,
  id_empleado         INT UNSIGNED  NULL COMMENT 'Empleado que preparó el pedido',
  direccion_entrega   VARCHAR(255)  NOT NULL,
  referencia_entrega  VARCHAR(255)  NULL,
  id_distrito         INT UNSIGNED  NOT NULL,
  telefono_contacto   VARCHAR(15)   NOT NULL COMMENT 'Formato +503 7123-4567',
  subtotal            DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT 'Suma de pedido_detalles (trigger)',
  costo_envio         DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT '$4.00, o $0.00 si subtotal > $50.00 (trigger)',
  total               DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT 'subtotal + costo_envio (trigger)',
  estado              ENUM('pendiente','en_preparacion','enviado','entregado','cancelado') NOT NULL DEFAULT 'pendiente',
  notas               VARCHAR(255)  NULL,
  fecha_pedido        DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  fecha_actualizacion DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id_pedido),
  KEY idx_pedidos_cliente_fecha (id_cliente, fecha_pedido),
  KEY idx_pedidos_estado_fecha (estado, fecha_pedido),
  KEY idx_pedidos_empleado (id_empleado),
  KEY idx_pedidos_distrito (id_distrito),
  CONSTRAINT fk_pedidos_cliente FOREIGN KEY (id_cliente)
    REFERENCES clientes (id_cliente) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT fk_pedidos_empleado FOREIGN KEY (id_empleado)
    REFERENCES empleados (id_empleado) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT fk_pedidos_distrito FOREIGN KEY (id_distrito)
    REFERENCES distritos (id_distrito) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT chk_pedidos_montos CHECK (subtotal >= 0 AND costo_envio >= 0 AND total >= 0),
  CONSTRAINT chk_pedidos_telefono CHECK (telefono_contacto REGEXP '^\\+503 [267][0-9]{3}-[0-9]{4}$')
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Pedidos de los clientes con montos, dirección de entrega y estado';

-- Libros de cada pedido. precio_unitario congela el precio del momento de
-- la compra; subtotal es una columna calculada.
CREATE TABLE pedido_detalles (
  id_pedido_detalle INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  id_pedido         INT UNSIGNED  NOT NULL,
  id_libro          INT UNSIGNED  NOT NULL,
  cantidad          INT           NOT NULL,
  precio_unitario   DECIMAL(10,2) NOT NULL COMMENT 'Precio del libro al momento de la compra',
  subtotal          DECIMAL(10,2) GENERATED ALWAYS AS (cantidad * precio_unitario) STORED,
  PRIMARY KEY (id_pedido_detalle),
  UNIQUE KEY uq_pedido_detalles_libro (id_pedido, id_libro),
  KEY idx_pedido_detalles_libro (id_libro),
  CONSTRAINT fk_pedido_detalles_pedido FOREIGN KEY (id_pedido)
    REFERENCES pedidos (id_pedido) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT fk_pedido_detalles_libro FOREIGN KEY (id_libro)
    REFERENCES libros (id_libro) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT chk_pedido_detalles_cantidad CHECK (cantidad > 0),
  CONSTRAINT chk_pedido_detalles_precio CHECK (precio_unitario > 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Libros, cantidades y precios de cada pedido';

-- Seguimiento del pedido: una fila por cada cambio de estado (la llenan triggers).
CREATE TABLE historial_estados_pedido (
  id_historial    INT UNSIGNED NOT NULL AUTO_INCREMENT,
  id_pedido       INT UNSIGNED NOT NULL,
  estado_anterior ENUM('pendiente','en_preparacion','enviado','entregado','cancelado') NULL,
  estado_nuevo    ENUM('pendiente','en_preparacion','enviado','entregado','cancelado') NOT NULL,
  id_usuario      INT UNSIGNED NULL COMMENT 'Quién hizo el cambio; NULL = sistema',
  comentario      VARCHAR(255) NULL,
  fecha_cambio    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id_historial),
  KEY idx_historial_pedido_fecha (id_pedido, fecha_cambio),
  KEY idx_historial_usuario (id_usuario),
  CONSTRAINT fk_historial_pedido FOREIGN KEY (id_pedido)
    REFERENCES pedidos (id_pedido) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT fk_historial_usuario FOREIGN KEY (id_usuario)
    REFERENCES usuarios (id_usuario) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Historial de cambios de estado de cada pedido (seguimiento del cliente)';

-- Llave foránea pendiente: movimientos de inventario -> pedidos.
ALTER TABLE movimientos_inventario
  ADD CONSTRAINT fk_movimientos_pedido FOREIGN KEY (id_pedido)
    REFERENCES pedidos (id_pedido) ON DELETE RESTRICT ON UPDATE CASCADE;


-- =====================================================================
--  7. VISTAS
-- =====================================================================

-- Libros cuyo stock está en el mínimo o por debajo (pantalla "Stock bajo").
CREATE OR REPLACE VIEW vw_stock_bajo AS
SELECT l.id_libro,
       l.isbn,
       l.titulo,
       l.stock_actual,
       l.stock_minimo,
       (l.stock_minimo - l.stock_actual) AS faltante
FROM libros l
WHERE l.activo = 1
  AND l.stock_actual <= l.stock_minimo;

-- Novedades: libros marcados como novedad o ingresados en los últimos 30 días.
CREATE OR REPLACE VIEW vw_novedades AS
SELECT l.id_libro,
       l.isbn,
       l.titulo,
       l.precio,
       l.fecha_ingreso,
       l.es_novedad
FROM libros l
WHERE l.activo = 1
  AND (l.es_novedad = 1 OR l.fecha_ingreso >= NOW() - INTERVAL 30 DAY);


-- =====================================================================
--  8. TRIGGERS
--
--  Variables de sesión opcionales que puede fijar PHP antes de cambiar
--  el estado de un pedido (quedan en historial_estados_pedido):
--    $pdo->exec("SET @id_usuario_actual = 12");
--    $pdo->exec("SET @comentario_estado = 'Entregado en portería'");
-- =====================================================================

DELIMITER $$

-- ---------------------------------------------------------------------
-- Pedidos: regla de envío.
-- Envío fijo de $4.00; gratis cuando el subtotal SUPERA $50.00.
-- Al insertar el pedido aún no tiene detalles, por eso subtotal = 0.
-- ---------------------------------------------------------------------
CREATE TRIGGER trg_pedidos_bi BEFORE INSERT ON pedidos
FOR EACH ROW
BEGIN
  SET NEW.subtotal    = 0.00;
  SET NEW.costo_envio = 4.00;
  SET NEW.total       = NEW.subtotal + NEW.costo_envio;

  IF NEW.id_empleado IS NOT NULL AND NOT EXISTS (
       SELECT 1 FROM empleados WHERE id_empleado = NEW.id_empleado AND estado = 'activo') THEN
    SIGNAL SQLSTATE '45000'
      SET MESSAGE_TEXT = 'El empleado asignado no existe o está dado de baja';
  END IF;
END$$

-- Recalcula subtotal (desde los detalles), envío y total en cada UPDATE.
-- Así el monto nunca queda desincronizado aunque alguien edite el pedido a mano.
CREATE TRIGGER trg_pedidos_bu BEFORE UPDATE ON pedidos
FOR EACH ROW
BEGIN
  SET NEW.subtotal = (SELECT COALESCE(SUM(d.subtotal), 0.00)
                      FROM pedido_detalles d
                      WHERE d.id_pedido = NEW.id_pedido);
  SET NEW.costo_envio = IF(NEW.subtotal > 50.00, 0.00, 4.00);
  SET NEW.total       = NEW.subtotal + NEW.costo_envio;

  IF NEW.id_empleado IS NOT NULL
     AND NOT (NEW.id_empleado <=> OLD.id_empleado)
     AND NOT EXISTS (SELECT 1 FROM empleados
                     WHERE id_empleado = NEW.id_empleado AND estado = 'activo') THEN
    SIGNAL SQLSTATE '45000'
      SET MESSAGE_TEXT = 'El empleado asignado no existe o está dado de baja';
  END IF;
END$$

-- Primer registro del seguimiento al crear el pedido.
CREATE TRIGGER trg_pedidos_ai AFTER INSERT ON pedidos
FOR EACH ROW
BEGIN
  INSERT INTO historial_estados_pedido (id_pedido, estado_anterior, estado_nuevo, id_usuario, comentario)
  VALUES (NEW.id_pedido, NULL, NEW.estado, @id_usuario_actual,
          COALESCE(@comentario_estado, 'Pedido registrado'));
END$$

-- Registra en el seguimiento cada cambio de estado.
CREATE TRIGGER trg_pedidos_au AFTER UPDATE ON pedidos
FOR EACH ROW
BEGIN
  IF NOT (NEW.estado <=> OLD.estado) THEN
    INSERT INTO historial_estados_pedido (id_pedido, estado_anterior, estado_nuevo, id_usuario, comentario)
    VALUES (NEW.id_pedido, OLD.estado, NEW.estado, @id_usuario_actual, @comentario_estado);
  END IF;
END$$

-- ---------------------------------------------------------------------
-- Detalles del pedido: al agregar, cambiar o quitar un libro se "toca"
-- el pedido para que trg_pedidos_bu recalcule subtotal, envío y total.
-- ---------------------------------------------------------------------
CREATE TRIGGER trg_pedido_detalles_ai AFTER INSERT ON pedido_detalles
FOR EACH ROW
BEGIN
  UPDATE pedidos SET fecha_actualizacion = NOW() WHERE id_pedido = NEW.id_pedido;
END$$

CREATE TRIGGER trg_pedido_detalles_au AFTER UPDATE ON pedido_detalles
FOR EACH ROW
BEGIN
  UPDATE pedidos SET fecha_actualizacion = NOW() WHERE id_pedido = NEW.id_pedido;
  IF OLD.id_pedido <> NEW.id_pedido THEN
    UPDATE pedidos SET fecha_actualizacion = NOW() WHERE id_pedido = OLD.id_pedido;
  END IF;
END$$

CREATE TRIGGER trg_pedido_detalles_ad AFTER DELETE ON pedido_detalles
FOR EACH ROW
BEGIN
  UPDATE pedidos SET fecha_actualizacion = NOW() WHERE id_pedido = OLD.id_pedido;
END$$

-- ---------------------------------------------------------------------
-- Inventario: cada movimiento actualiza el stock del libro.
-- ---------------------------------------------------------------------
CREATE TRIGGER trg_movimientos_bi BEFORE INSERT ON movimientos_inventario
FOR EACH ROW
BEGIN
  DECLARE v_stock INT;

  SELECT stock_actual INTO v_stock FROM libros WHERE id_libro = NEW.id_libro;

  SET NEW.stock_anterior = v_stock;
  SET NEW.stock_nuevo = IF(NEW.tipo = 'entrada', v_stock + NEW.cantidad, v_stock - NEW.cantidad);

  IF NEW.stock_nuevo < 0 THEN
    SIGNAL SQLSTATE '45000'
      SET MESSAGE_TEXT = 'Stock insuficiente para registrar la salida';
  END IF;
END$$

CREATE TRIGGER trg_movimientos_ai AFTER INSERT ON movimientos_inventario
FOR EACH ROW
BEGIN
  UPDATE libros
     SET stock_actual = IF(NEW.tipo = 'entrada', stock_actual + NEW.cantidad, stock_actual - NEW.cantidad)
   WHERE id_libro = NEW.id_libro;
END$$

-- ---------------------------------------------------------------------
-- Empleados: baja lógica.
-- ---------------------------------------------------------------------
-- Mantiene coherentes estado y fecha_baja.
CREATE TRIGGER trg_empleados_bi BEFORE INSERT ON empleados
FOR EACH ROW
BEGIN
  IF NEW.estado = 'activo' THEN
    SET NEW.fecha_baja = NULL, NEW.motivo_baja = NULL;
  ELSEIF NEW.fecha_baja IS NULL THEN
    SET NEW.fecha_baja = NOW();
  END IF;
END$$

CREATE TRIGGER trg_empleados_bu BEFORE UPDATE ON empleados
FOR EACH ROW
BEGIN
  IF NEW.estado = 'activo' THEN
    SET NEW.fecha_baja = NULL, NEW.motivo_baja = NULL;
  ELSEIF NEW.fecha_baja IS NULL THEN
    SET NEW.fecha_baja = NOW();
  END IF;
END$$

-- Un empleado dado de baja ya no puede iniciar sesión (y al reactivarlo, sí).
CREATE TRIGGER trg_empleados_au AFTER UPDATE ON empleados
FOR EACH ROW
BEGIN
  IF NOT (NEW.estado <=> OLD.estado) THEN
    UPDATE usuarios SET activo = IF(NEW.estado = 'activo', 1, 0)
     WHERE id_usuario = NEW.id_usuario;
  END IF;
END$$

-- Impide el borrado físico de empleados.
CREATE TRIGGER trg_empleados_bd BEFORE DELETE ON empleados
FOR EACH ROW
BEGIN
  SIGNAL SQLSTATE '45000'
    SET MESSAGE_TEXT = 'Los empleados no se eliminan: actualice estado = ''baja''';
END$$

DELIMITER ;
