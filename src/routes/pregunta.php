<?php

use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

// Todas las preguntas
$app->get('/api/pregunta', function (Request $request, Response $response) {
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

                    $sql="SELECT p.*, s.titulo AS titulo_seccion, s.descripcion AS descripcion_seccion, e.nombre AS nombre_examen, m.nombre AS nombre_materia FROM pregunta p LEFT JOIN seccion s ON p.seccion_id = s.id LEFT JOIN examen e ON s.examen_id = e.id LEFT JOIN materia m ON e.materia_id = m.id WHERE m.id IN (SELECT materia_id FROM materia_x_profesor WHERE profesor_id = $profesor_id)";

                    // Get db object
                    $db = new db();
                    // Connect
                    $db = $db->connect();

                    $stmt = $db->query($sql);
                    $preguntas = $stmt->fetchAll(PDO::FETCH_OBJ);

                    // Genero un objeto para devolver
                    $preguntasObject->preguntas = $preguntas;

                    $db = null;
                    return dataResponse($response, $preguntasObject);
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

// Obtener una pregunta en particular
$app->get('/api/pregunta/{id}', function (Request $request, Response $response) {
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
                    $pregunta_id = $request->getAttribute('id');

                    // Obtiene el id del examen en el que está la pregunta
                    $sql="SELECT e.id FROM pregunta p LEFT JOIN seccion s ON p.seccion_id = s.id LEFT JOIN examen e ON s.examen_id = e.id WHERE p.id = $pregunta_id";

                    // Get db object
                    $db = new db();
                    // Connect
                    $db = $db->connect();
                    $stmt = $db->query($sql);
                    $examenes = $stmt->fetchAll(PDO::FETCH_OBJ);
                    $examen_id = $examenes[0]->id;

                    // Si el examen id no existe, inventa uno imposible para devolver sin acceso
                    if (!$examen_id) {
                        $examen_id = -1;
                    }

                    // Si el profesor tiene acceso a la pregunta, devuelve los detalles
                    if (verificarPermisosProfesorExamen($profesor_id, $examen_id)) {
                        $sql="SELECT p.*, s.titulo AS titulo_seccion, s.descripcion AS descripcion_seccion, e.nombre AS nombre_examen, m.nombre AS nombre_materia FROM pregunta p LEFT JOIN seccion s ON p.seccion_id = s.id LEFT JOIN examen e ON s.examen_id = e.id LEFT JOIN materia m ON e.materia_id = m.id WHERE p.id = $pregunta_id";
                        $stmt = $db->query($sql);
                        $preguntas = $stmt->fetchAll(PDO::FETCH_OBJ);

                        return dataResponse($response, $preguntas[0]);
                    } else {
                        // Si el profesor no tiene acceso al examen, devuelve error 403
                        $db = null;
                        return respondWithError($response, 'No tiene permisos para ver la pregunta seleccionada.', 403);
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


// Genera una nueva pregunta
$app->post('/api/pregunta', function (Request $request, Response $response) {
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
                    $seccion_id= $request->getParam('seccion_id');
                    $texto_pregunta= $request->getParam('texto_pregunta');
                    $tipo_pregunta= $request->getParam('tipo_pregunta');
                    $texto_respuesta= $request->getParam('texto_respuesta');
                    $habilitada= $request->getParam('habilitada');
                    $uploadedFiles = $request->getUploadedFiles();



                    if ($seccion_id && $texto_pregunta && $tipo_pregunta) {
                        $sql="SELECT examen_id FROM seccion WHERE id = $seccion_id";
                        // Get db object
                        $db = new db();
                        // Connect
                        $db = $db->connect();
                        $stmt = $db->query($sql);
                        $examenes = $stmt->fetchAll(PDO::FETCH_OBJ);
                        $examen_id = $examenes[0]->examen_id;

                        // Si el examen id no existe, inventa uno imposible para devolver sin acceso
                        if (!$examen_id) {
                            $examen_id = -1;
                        }

                        // Obtiene los datos del usuario logueado para armar la query
                        $profesor_id = $user_found[0]->profesor_id;

                        // Si el profesor tiene acceso al examen, se crea el período
                        if (verificarPermisosProfesorExamen($profesor_id, $examen_id)) {

                          // Si viene una imagen la procesa y la sube
                            if (count($uploadedFiles) != 0) {
                                $directory = $this->get('upload_directory');
                                $uploadedFile = $uploadedFiles['imagen_pregunta'];
                                if ($uploadedFile->getError() === UPLOAD_ERR_OK) {
                                    $extension = pathinfo($uploadedFile->getClientFilename(), PATHINFO_EXTENSION);
                                    $basename = bin2hex(random_bytes(8));
                                    $filename = sprintf('%s.%0.8s', $basename, $extension);
                                    $uploadedFile->moveTo($directory . DIRECTORY_SEPARATOR . $filename);
                                }
                            }


                            $sql="INSERT INTO pregunta (seccion_id, texto_pregunta, imagen_pregunta, tipo_pregunta, texto_respuesta, habilitada) VALUES (:seccion_id, :texto_pregunta, :imagen_pregunta, :tipo_pregunta, :texto_respuesta, :habilitada)";

                            $stmt = $db->prepare($sql);
                            $stmt->bindParam(':seccion_id', $seccion_id);
                            $stmt->bindParam(':texto_pregunta', $texto_pregunta);
                            $stmt->bindParam(':imagen_pregunta', $filename);
                            $stmt->bindParam(':tipo_pregunta', $tipo_pregunta);
                            $stmt->bindParam(':texto_respuesta', $texto_respuesta);
                            $stmt->bindParam(':habilitada', $habilitada);
                            $stmt->execute();

                            $sql="SELECT * FROM pregunta WHERE id = LAST_INSERT_ID()";
                            $stmt = $db->query($sql);
                            $preguntas = $stmt->fetchAll(PDO::FETCH_OBJ);

                            $db = null;
                            return dataResponse($response, $preguntas[0]);
                        } else { // if ($acceso_permitido_al_examen) {
                            // Si el profesor no tiene acceso al examen, devuelve error 403
                            $db = null;
                            return respondWithError($response, 'No tiene permisos para crear preguntas en examen seleccionado a través de su seccion.', 403);
                        }
                    } else { // if ($seccion_id && $texto_pregunta && $tipo_pregunta && $habilitada) {
                        $db = null;
                        return respondWithError($response, 'Parámetros faltantes. Debe incluir la sección del examen, el texto de la pregunta, el tipo de la pregunta y si está habilitada.', 400);
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
