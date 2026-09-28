<?php
/**
 * Datos para los selects dependientes departamento -> municipio -> distrito (JSON).
 */

class UbicacionController extends Controller
{
    /** GET /api/municipios/{id} */
    public function municipios(int $idDepartamento): void
    {
        $this->json(['items' => $this->modelo('Ubicacion')->municipios($idDepartamento)]);
    }

    /** GET /api/distritos/{id} */
    public function distritos(int $idMunicipio): void
    {
        $this->json(['items' => $this->modelo('Ubicacion')->distritos($idMunicipio)]);
    }
}
