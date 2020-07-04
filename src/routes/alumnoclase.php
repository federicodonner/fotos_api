<?php

use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

// Elimina un alumno de una clase
$app->delete('/api/alumnoclase', function (Request $request, Response $response) {
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

                  // Verifica que todos los parámetros fueron enviados
                    $clase_id= $request->getParam('clase');
                    $alumno_id= $request->getParam('alumno');

                    if ($clase_id && $alumno_id) {
                        $sql="SELECT * FROM clase_x_alumno WHERE clase_id = $clase_id and alumno_id = $alumno_id";
                        // Get db object
                        $db = new db();
                        // Connect
                        $db = $db->connect();
                        $stmt = $db->query($sql);
                        $alumnos = $stmt->fetchAll(PDO::FETCH_OBJ);

                        if (count($alumnos)>0) {
                            $sql="DELETE FROM clase_x_alumno WHERE clase_id = $clase_id AND alumno_id = $alumno_id";

                            $stmt = $db->prepare($sql);
                            $stmt->execute();

                            $objeto_respuesta->mensaje='Alumno eliminado de la clase exitosamente.';


                            $db = null;
                            return dataResponse($response, $objeto_respuesta);
                        } else {
                            $db = null;
                            return respondWithError($response, 'El alumno seleccionado no es parte de la clase especificada.', 404);
                        }
                    } else {
                        $db = null;
                        return respondWithError($response, 'Parámetros faltantes. Debe incluir el identificador del alumno y de la clase.', 400);
                    }
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


// Agrega un alumno a una clase
$app->post('/api/alumnoclase', function (Request $request, Response $response) {
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

                  // Verifica que todos los parámetros fueron enviados
                    $clase_id= $request->getParam('clase');
                    $alumno_id= $request->getParam('alumno');

                    if ($clase_id && $alumno_id) {

                      // Verifica que el alumno exista
                        $sql="SELECT * FROM alumno WHERE id = $alumno_id";
                        // Get db object
                        $db = new db();
                        // Connect
                        $db = $db->connect();
                        $stmt = $db->query($sql);
                        $alumnos_existen = $stmt->fetchAll(PDO::FETCH_OBJ);

                        if (count($alumnos_existen)>0) {

                          // Verifica que la clase exista
                            $sql="SELECT * FROM clase WHERE id = $clase_id";
                            $stmt = $db->query($sql);
                            $clases_existen = $stmt->fetchAll(PDO::FETCH_OBJ);

                            if (count($clases_existen)>0) {
                                $sql="SELECT * FROM clase_x_alumno WHERE clase_id = $clase_id and alumno_id = $alumno_id";


                                $stmt = $db->query($sql);
                                $alumnos = $stmt->fetchAll(PDO::FETCH_OBJ);

                                if (count($alumnos)==0) {
                                    $sql = "INSERT INTO clase_x_alumno (clase_id,alumno_id) VALUES (:clase_id,:alumno_id)";


                                    $stmt = $db->prepare($sql);
                                    $stmt->bindParam(':clase_id', $clase_id);
                                    $stmt->bindParam(':alumno_id', $alumno_id);
                                    $stmt->execute();

                                    $objeto_respuesta->mensaje='Alumno añadido a la clase exitosamente.';


                                    $db = null;
                                    return dataResponse($response, $objeto_respuesta);
                                } else {
                                    $db = null;
                                    return respondWithError($response, 'El alumno ya es parte de la clase seleccionada.', 409);
                                }
                            } else {
                                $db = null;
                                return respondWithError($response, 'La clase especificada no existe.', 404);
                            }
                        } else {
                            $db = null;
                            return respondWithError($response, 'El alumno especificado no existe.', 404);
                        }
                    } else {
                        $db = null;
                        return respondWithError($response, 'Parámetros faltantes. Debe incluir el identificador del alumno y de la clase.', 400);
                    }
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
