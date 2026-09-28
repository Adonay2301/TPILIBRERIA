<?php
/**
 * Movimientos de inventario. El trigger de la tabla actualiza libros.stock_actual
 * y rechaza salidas que dejarían el stock en negativo.
 */

class MovimientoInventarioModel extends Model
{
    /** Entrada por compra a proveedor (botón "Registrar entrada"). */
    public function registrarEntrada(int $idLibro, int $cantidad, int $idUsuario, string $observacion): void
    {
        $this->ejecutar(
            "INSERT INTO movimientos_inventario (id_libro, tipo, motivo, cantidad, id_usuario, observacion)
             VALUES (?, 'entrada', 'compra', ?, ?, ?)",
            [$idLibro, $cantidad, $idUsuario, $observacion !== '' ? $observacion : null]
        );
    }

    /** Últimos movimientos de un libro. */
    public function delLibro(int $idLibro, int $limite = 10): array
    {
        return $this->todos(
            'SELECT tipo, motivo, cantidad, stock_anterior, stock_nuevo, observacion, fecha
               FROM movimientos_inventario WHERE id_libro = ? ORDER BY fecha DESC, id_movimiento DESC LIMIT ' . (int) $limite,
            [$idLibro]
        );
    }
}
