-- =====================================================================
--  PUNTO Y APARTE - migracion_pagos.sql
--  Agrega la tabla de pagos (contra entrega / PayPal) a una base que ya
--  existe, sin borrar datos. Las bases nuevas no la necesitan: schema.sql
--  y seed.sql ya incluyen la tabla y los pagos de prueba.
-- =====================================================================

USE punto_y_aparte;
SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS pagos (
  id_pago             INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  id_pedido           INT UNSIGNED  NOT NULL,
  metodo              ENUM('contra_entrega','paypal') NOT NULL,
  estado              ENUM('pendiente','completado','reembolsado','cancelado') NOT NULL DEFAULT 'pendiente',
  monto               DECIMAL(10,2) NOT NULL,
  paypal_orden_id     VARCHAR(40)   NULL COMMENT 'Id de la orden de PayPal (Orders API v2)',
  paypal_captura_id   VARCHAR(40)   NULL COMMENT 'Id de la captura; se usa para reembolsar',
  paypal_correo       VARCHAR(120)  NULL COMMENT 'Correo de la cuenta PayPal que pagó',
  fecha_creacion      DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  fecha_actualizacion DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id_pago),
  UNIQUE KEY uq_pagos_pedido (id_pedido),
  UNIQUE KEY uq_pagos_paypal_orden (paypal_orden_id),
  KEY idx_pagos_metodo_estado (metodo, estado),
  CONSTRAINT fk_pagos_pedido FOREIGN KEY (id_pedido)
    REFERENCES pedidos (id_pedido) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT chk_pagos_monto CHECK (monto >= 0),
  CONSTRAINT chk_pagos_paypal CHECK (metodo <> 'paypal' OR paypal_orden_id IS NOT NULL)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Método y estado del pago de cada pedido';

-- Los pedidos anteriores a esta migración se pagaron contra entrega.
INSERT INTO pagos (id_pedido, metodo, estado, monto, fecha_creacion)
SELECT p.id_pedido, 'contra_entrega',
       CASE p.estado WHEN 'entregado' THEN 'completado' WHEN 'cancelado' THEN 'cancelado' ELSE 'pendiente' END,
       p.total, p.fecha_pedido
  FROM pedidos p
 WHERE NOT EXISTS (SELECT 1 FROM pagos g WHERE g.id_pedido = p.id_pedido);
