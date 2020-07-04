<?php

use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

// Obtener todos los examenes
$app->get('/api/examen', function (Request $request, Response $response) {
    // Verifica que el cabezal de autenticación esté disponible
    if ($request->getHeaders()['HTTP_AUTHORIZATION']) {
        // Si hay cabezal, obtiene el token de login
        $access_token = $request->getHeaders()['HTTP_AUTHORIZATION'][0];
        $access_token = explode(" ", $access_token)[1];
        // Si encuentra el token, busca el usuario logueado
        if (!empty($access_token)) {
            $user_found = verifyToken($access_token);
            // Verifica que haya un usuario logueado para seguir adelante
            if (!empty($user_found)) {
                try {
                    // Obtiene los datos del usuario logueado para armar la query
                    $profesor_id = $user_found[0]->profesor_id;

                    $sql = "SELECT e.*, m.nombre AS materia FROM materia_x_profesor mxp LEFT JOIN examen e ON e.materia_id = mxp.materia_id LEFT JOIN materia m ON e.materia_id = m.id WHERE mxp.profesor_id = $profesor_id";

                    // Get db object
                    $db = new db();
                    // Connect
                    $db = $db->connect();

                    $stmt = $db->query($sql);
                    $examenes = $stmt->fetchAll(PDO::FETCH_OBJ);

                    // Agrega los examenes al array para respuesta
                    $examenesObject->examenes = $examenes;

                    $db = null;
                    return dataResponse($response, $examenesObject);
                } catch (PDOException $e) {
                    $db = null;
                    return respondWithError($response, $e->getMessage(), 500);
                }
            } else {  // if (!empty($user_found)) {
                $db = null;
                return respondWithError($response, 'Error de login, usuario no encontrado', 401, 'error');
            }
        } else { // if (!empty($access_token)) {
            $db = null;
            return respondWithError($response, 'Error de login, falta access token', 401, 'error');
        }
    } else { // if ($request->getHeaders()['HTTP_AUTHORIZATION']) {
        $db = null;
        return respondWithError($response, 'Error de encabezado HTTP', 401, 'error');
    }
});




// Obtener un examen en particular
// Obtener todos los examenes
$app->get('/api/examen/{id}', function (Request $request, Response $response) {
    // Verifica que el cabezal de autenticación esté disponible
    if ($request->getHeaders()['HTTP_AUTHORIZATION']) {
        // Si hay cabezal, obtiene el token de login
        $access_token = $request->getHeaders()['HTTP_AUTHORIZATION'][0];
        $access_token = explode(" ", $access_token)[1];
        // Si encuentra el token, busca el usuario logueado
        if (!empty($access_token)) {
            $user_found = verifyToken($access_token);
            // Verifica que haya un usuario logueado para seguir adelante
            if (!empty($user_found)) {
                try {
                    // Obtiene los datos del usuario logueado para armar la query
                    $profesor_id = $user_found[0]->profesor_id;
                    $examen_id = $request->getAttribute('id');

                    // Si el profesor tiene acceso al examen, devuelve los detalles
                    if (verificarPermisosProfesorExamen($profesor_id, $examen_id)) {
                        $sql = "SELECT e.id, e.nombre AS examen, m.nombre AS materia, e.materia_id AS materia_id FROM examen e LEFT JOIN materia m ON e.materia_id = m.id WHERE m.id = $examen_id";

                        // Get db object
                        $db = new db();
                        // Connect
                        $db = $db->connect();
                        $stmt = $db->query($sql);
                        $examenes = $stmt->fetchAll(PDO::FETCH_OBJ);
                        $examen = $examenes[0];

                        // Obtiene los detalles del examen
                        $sql = "SELECT * FROM seccion WHERE examen_id = $examen_id ORDER BY orden";
                        $stmt = $db->query($sql);
                        $secciones = $stmt->fetchAll(PDO::FETCH_OBJ);

                        foreach ($secciones as $seccion) {
                            $seccion_id = $seccion->id;
                            $sql = "SELECT * FROM pregunta WHERE seccion_id = $seccion_id";
                            $stmt = $db->query($sql);
                            $preguntas = $stmt->fetchAll(PDO::FETCH_OBJ);

                            $seccion->preguntas = $preguntas;
                        }

                        $examen->secciones = $secciones;

                        $timestamp_actual = time();
                        $sql = "SELECT * FROM periodo WHERE examen_id = $examen_id AND inicio_dttm > $timestamp_actual ORDER BY inicio_dttm";
                        $stmt = $db->query($sql);
                        $periodos_futuros = $stmt->fetchAll(PDO::FETCH_OBJ);

                        $examen->periodos_futuros = $periodos_futuros;

                        $sql = "SELECT * FROM periodo WHERE examen_id = $examen_id AND inicio_dttm <= $timestamp_actual ORDER BY inicio_dttm";
                        $stmt = $db->query($sql);
                        $periodos_pasados = $stmt->fetchAll(PDO::FETCH_OBJ);

                        $examen->periodos_pasados = $periodos_pasados;

                        $db = null;
                        return dataResponse($response, $examen);
                    } else { //     if (verificarPermisosProfesorExamen($profesor_id, $examen_id)) {
                        // Si el profesor no tiene acceso al examen, devuelve error 403
                        $db = null;
                        return respondWithError($response, 'No tiene permisos para ver el examen seleccionado.', 403);
                    }
                } catch (PDOException $e) {
                    $db = null;
                    return respondWithError($response, $e->getMessage(), 500);
                }
            } else {  // if (!empty($user_found)) {
                $db = null;
                return respondWithError($response, 'Error de login, usuario no encontrado', 401);
            }
        } else { // if (!empty($access_token)) {
            $db = null;
            return respondWithError($response, 'Error de login, falta access token', 401);
        }
    } else { // if ($request->getHeaders()['HTTP_AUTHORIZATION']) {
        $db = null;
        return respondWithError($response, 'Error de encabezado HTTP', 401);
    }
});
