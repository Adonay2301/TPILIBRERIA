<?php
/**
 * Carrito del cliente (tablas carritos y carrito_detalles).
 */

class CarritoModel extends Model
{
    /** Total de ejemplares en el carrito (ícono del navbar). */
    public function contarArticulos(int $idCliente): int
    {
        return (int) $this->valor(
            'SELECT COALESCE(SUM(cd.cantidad), 0)
               FROM carritos c
               JOIN carrito_detalles cd ON cd.id_carrito = c.id_carrito
              WHERE c.id_cliente = ?',
            [$idCliente]
        );
    }

    /** Id del carrito del cliente; lo crea la primera vez. */
    private function idCarrito(int $idCliente): int
    {
        $id = $this->valor('SELECT id_carrito FROM carritos WHERE id_cliente = ?', [$idCliente]);
        if ($id) {
            return (int) $id;
        }
        $this->ejecutar('INSERT INTO carritos (id_cliente) VALUES (?)', [$idCliente]);
        return $this->ultimoId();
    }

    /** Libros del carrito con precio y stock actuales. */
    public function items(int $idCliente): array
    {
        return $this->todos(
            "SELECT cd.id_libro, cd.cantidad, l.titulo, l.precio, l.portada, l.stock_actual, l.activo,
                    (cd.cantidad * l.precio) AS subtotal,
                    (SELECT GROUP_CONCAT(CONCAT(a.nombres, ' ', a.apellidos) ORDER BY la.orden SEPARATOR ', ')
                       FROM libros_autores la JOIN autores a ON a.id_autor = la.id_autor
                      WHERE la.id_libro = l.id_libro) AS autores
               FROM carritos c
               JOIN carrito_detalles cd ON cd.id_carrito = c.id_carrito
               JOIN libros l ON l.id_libro = cd.id_libro
              WHERE c.id_cliente = ?
              ORDER BY cd.fecha_agregado",
            [$idCliente]
        );
    }

    /**
     * Vista previa de montos. El carrito aún no es un pedido, por eso aquí se aplica la regla;
     * al confirmar la compra, los triggers de pedidos calculan los montos definitivos.
     */
    public function resumen(array $items): array
    {
        $subtotal = array_sum(array_map(fn ($i) => (float) $i['subtotal'], $items));
        $envio = ($subtotal > ENVIO_GRATIS_DESDE || $subtotal == 0) ? 0.0 : ENVIO_COSTO;

        return [
            'articulos' => array_sum(array_column($items, 'cantidad')),
            'subtotal'  => $subtotal,
            'envio'     => $envio,
            'total'     => $subtotal + $envio,
            'falta_envio_gratis' => $subtotal > ENVIO_GRATIS_DESDE ? 0 : ENVIO_GRATIS_DESDE - $subtotal + 0.01,
        ];
    }

    /**
     * Suma ejemplares de un libro sin pasar del stock disponible.
     * Lanza DomainException con un mensaje para el cliente si no se puede.
     */
    public function agregar(int $idCliente, int $idLibro, int $cantidad): int
    {
        $libro = $this->uno('SELECT titulo, stock_actual, activo FROM libros WHERE id_libro = ?', [$idLibro]);
        if (!$libro || !$libro['activo']) {
            throw new DomainException('El libro no está disponible.');
        }
        if ((int) $libro['stock_actual'] <= 0) {
            throw new DomainException('Este libro está agotado.');
        }

        $idCarrito = $this->idCarrito($idCliente);
        $actual = (int) $this->valor(
            'SELECT cantidad FROM carrito_detalles WHERE id_carrito = ? AND id_libro = ?',
            [$idCarrito, $idLibro]
        );
        $nueva = min($actual + max(1, $cantidad), (int) $libro['stock_actual']);

        if ($nueva === $actual) {
            throw new DomainException('Ya tienes en el carrito todas las unidades disponibles.');
        }

        if ($actual > 0) {
            $this->ejecutar('UPDATE carrito_detalles SET cantidad = ? WHERE id_carrito = ? AND id_libro = ?', [$nueva, $idCarrito, $idLibro]);
        } else {
            $this->ejecutar('INSERT INTO carrito_detalles (id_carrito, id_libro, cantidad) VALUES (?, ?, ?)', [$idCarrito, $idLibro, $nueva]);
        }
        $this->ejecutar('UPDATE carritos SET fecha_actualizacion = NOW() WHERE id_carrito = ?', [$idCarrito]);

        return $nueva;
    }

    /** Cambia la cantidad (entre 1 y el stock). Devuelve la cantidad final. */
    public function actualizar(int $idCliente, int $idLibro, int $cantidad): int
    {
        $stock = (int) $this->valor('SELECT stock_actual FROM libros WHERE id_libro = ?', [$idLibro]);
        $cantidad = max(1, min($cantidad, max(1, $stock)));

        $this->ejecutar(
            'UPDATE carrito_detalles cd JOIN carritos c ON c.id_carrito = cd.id_carrito
                SET cd.cantidad = ?
              WHERE c.id_cliente = ? AND cd.id_libro = ?',
            [$cantidad, $idCliente, $idLibro]
        );
        return $cantidad;
    }

    public function eliminar(int $idCliente, int $idLibro): void
    {
        $this->ejecutar(
            'DELETE cd FROM carrito_detalles cd JOIN carritos c ON c.id_carrito = cd.id_carrito
              WHERE c.id_cliente = ? AND cd.id_libro = ?',
            [$idCliente, $idLibro]
        );
    }
}
