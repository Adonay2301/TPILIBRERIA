<?php
/**
 * Detalle público de un libro.
 */

class LibroController extends Controller
{
    /** GET /libro/{id} */
    public function ver(int $id): void
    {
        $modelo = $this->modelo('Libro');
        $libro = $modelo->obtener($id);
        if (!$libro) {
            throw new HttpError(404);
        }

        $this->vista('libro/ver', [
            'titulo'       => $libro['titulo'],
            'libro'        => $libro,
            'relacionados' => $modelo->relacionados($id, (int) $libro['id_categoria']),
        ]);
    }
}
