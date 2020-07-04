<?php

use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

// Devuelve los detalles del examen en base al request
$app->get('/api/examendesdehash/{hash}', function (Request $request, Response $response) {
    try {
        // Obtiene el hash del request
        $examen_hash = $request->getAttribute('hash');

        // Verifica que el hash exista

        $sql = "SELECT * FROM instancia_hash WHERE hash = '$examen_hash'";

        // Get db object
        $db = new db();
        // Connect
        $db = $db->connect();

        $stmt = $db->query($sql);
        $registros = $stmt->fetchAll(PDO::FETCH_OBJ);

        // Si hay registros en la consulta significa que encontró el array
        if (count($registros) != 0) {
            $sql = "SELECT ih.id, m.nombre AS materia_nombre, p.nombre AS periodo_nombre, e.nombre AS examen_nombre, a.nombre AS alumno_nombre, a.apellido AS alumno_apellido, p.inicio_dttm, p.fin_dttm, ih.fechahora_ingreso, ih.hash FROM instancia_hash ih LEFT JOIN periodo p ON ih.periodo_id = p.id LEFT JOIN examen e ON p.examen_id = e.id LEFT JOIN materia m ON e.materia_id = m.id LEFT JOIN alumno a on ih.alumno_id = a.id WHERE ih.hash = '$examen_hash'";

            // Get db object
            $db = new db();
            // Connect
            $db = $db->connect();

            $stmt = $db->query($sql);
            $examenes = $stmt->fetchAll(PDO::FETCH_OBJ);
            $examen = $examenes[0];

            // Verifica que está dentro de la fecha del período
            $examen->dentro_periodo = ($examen->inicio_dttm < time() && time() < $examen->fin_dttm);
            $examen->fechahora_actual = time();

            $db = null;
            return dataResponse($response, $examen);
        } else { // if(count($registros) != 0){
            $db = null;
            return respondWithError($response, 'El hash no existe, si seguiste un link enviado por tu profesor, ponte en contacto para verificarlo.', 404);
        }
    } catch (PDOException $e) {
        $db = null;
        return respondWithError($response, $e->getMessage(), 500);
    }
});
