<?php

use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

// Registra una respuesta intermedia de un alumno
$app->post('/api/respuestainterim', function (Request $request, Response $response) {
    try {

        // Verifica que todos los parámetros fueron enviados
        $instancia_id = $request->getParam('instancia_id');
        $texto_respuesta = $request->getParam('texto_respuesta');
        if (!$texto_respuesta) {
            $texto_respuesta = '[vacio]';
        }

        if ($instancia_id) {
            $timestamp_ahora = time();

            $sql="INSERT INTO respuesta_interim (instancia_id, texto_respuesta, fechahora) VALUES (:instancia_id,:texto_respuesta,:timestamp_ahora)";
            // Get db object
            $db = new db();
            // Connect
            $db = $db->connect();

            $stmt = $db->prepare($sql);
            $stmt->bindParam(':instancia_id', $instancia_id);
            $stmt->bindParam(':texto_respuesta', $texto_respuesta);
            $stmt->bindParam(':timestamp_ahora', $timestamp_ahora);
            $stmt->execute();

            $sql="SELECT * FROM respuesta_interim WHERE id = LAST_INSERT_ID()";
            $stmt = $db->query($sql);
            $respuestas = $stmt->fetchAll(PDO::FETCH_OBJ);

            $db = null;
            return dataResponse($response, $respuestas[0]);
        } else { // if ($examen_id && $inicio_dttm && $fin_dttm) {
            $db = null;
            return respondWithError($response, 'Parámetros faltantes. Debe incluir instancia_id.', 400);
        }
    } catch (PDOException $e) {
        $db = null;
        return respondWithError($response, $e->getMessage(), 500);
    }
});
