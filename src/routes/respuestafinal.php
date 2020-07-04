<?php

use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

// Registra una respuesta intermedia de un alumno
$app->post('/api/respuestafinal', function (Request $request, Response $response) {
    try {

        // Guarda el string de la respuesta tal como fue recibido
        $respuestas = serialize($request->getParams());
        $direccion_ip = $request->getAttribute('ip_address');
        $sql = "INSERT INTO respuesta_cruda (respuesta, fechahora, direccion_ip) VALUES (:respuesta, :fechahora, :direccion_ip)";

        $db = new db();
        // Connect
        $db = $db->connect();
        $stmt = $db->prepare($sql);
        $stmt->bindParam(':respuesta', $respuestas);
        $stmt->bindParam(':fechahora', time());
        $stmt->bindParam(':direccion_ip', $direccion_ip);
        $stmt->execute();


        // Verifica que todos los parámetros fueron enviados
        $hash = $request->getParam('hash');
        $respuestas = $request->getParam('respuestas');

        if ($hash) {
            // Verifica que el hash sea correcto para cada una de las instancias de las respuestas
            $hash_correcto = true;
            // Recorre todas las respuestas buscando el hash correspondiente a cada una
            foreach ($respuestas as $respuesta) {
                $respuesta_objeto = json_decode($respuesta);
                $instancia = $respuesta->instancia_id;

                $instancia_id = $respuesta_objeto->instancia_id;
                $sql = "SELECT ih.hash FROM instancia_preguntas ip LEFT JOIN instancia_hash ih ON ip.alumno_id = ih.alumno_id AND ip.periodo_id = ih.periodo_id WHERE ip.id = $instancia_id";

                $db = new db();
                // Connect
                $db = $db->connect();
                $stmt = $db->query($sql);
                $hashes_obtenidos = $stmt->fetchAll(PDO::FETCH_OBJ);

                $hash_obtenido = $hashes_obtenidos[0];
                $hash_obtenido_final = $hash_obtenido->hash;
                // Si algún hash no concide, devuelve error forbidden
                if ($hash_obtenido_final != $hash) {
                    return respondWithError($response, 'Las respuestas recibidas no corresponden a tu examen.', 403);
                }
            }

            // Si llega hasta esta línea es porque todos los hash coincidieron,
            // se guardan las respuestas en la base de datos
            $timestamp_ahora = time();
            foreach ($respuestas as $respuesta) {
                $respuesta_objeto = json_decode($respuesta);
                $texto_respuesta = $respuesta_objeto->texto_respuesta;
                $instancia_id = $respuesta_objeto->instancia_id;
                $sql = "UPDATE instancia_preguntas  SET texto_respuesta = '$texto_respuesta', timestamp_respuesta = $timestamp_ahora WHERE id=$instancia_id";

                $stmt = $db->prepare($sql);
                $stmt->execute();
            }

            $db = null;
            $respuesta_invocacion->mensaje = "Respuestas registradas correctamente";
            return dataResponse($response, $respuesta_invocacion);
        } else { //   if ($hash) {
            $db = null;
            return respondWithError($response, 'Parámetros faltantes. Debe incluir el hash del examen.', 400);
        }
    } catch (PDOException $e) {
        $db = null;
        return respondWithError($response, $e->getMessage(), 500);
    }
});
