<?php

use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

// Todas los alumnos
$app->get('/api/alumno', function (Request $request, Response $response) {
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
                    // Trae a los alumnos
                    $sql='SELECT * FROM alumno';

                    // Get db object
                    $db = new db();
                    // Connect
                    $db = $db->connect();

                    $stmt = $db->query($sql);
                    $alumnos = $stmt->fetchAll(PDO::FETCH_OBJ);

                    foreach ($alumnos as $alumno) {
                        $alumno_id = $alumno->id;
                        $sql = "SELECT * FROM clase_x_alumno cxa LEFT JOIN clase c ON cxa.clase_id = c.id WHERE alumno_id = $alumno_id ";
                        $stmt = $db->query($sql);
                        $clases = $stmt->fetchAll(PDO::FETCH_OBJ);

                        $alumno->clases = $clases;
                    }

                    // Genero un objeto para devolver
                    $alumnosObject->alumnos = $alumnos;

                    $db = null;
                    return dataResponse($response, $alumnosObject);
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

// // Obtener una pregunta en particular
// $app->get('/api/pregunta/{id}', function (Request $request, Response $response) {
//     // Verifica que el cabezal de autenticación esté disponible
//     if ($request->getHeaders()['HTTP_AUTHORIZATION']) {
//         // Si hay cabezal, obtiene el token de login
//         $access_token = $request->getHeaders()['HTTP_AUTHORIZATION'][0];
//         $access_token = explode(" ", $access_token)[1];
//         // Si encuentra el token, busca el usuario logueado
//         if (!empty($access_token)) {
//             $user_found = verifyToken($access_token);
//             // Verifica que haya un usuario logueado para seguir adelante
//             if (!empty($user_found)) {
//                 try {
//                     // Obtiene los datos del usuario logueado para armar la query
//                     $profesor_id = $user_found[0]->profesor_id;
//                     $pregunta_id = $request->getAttribute('id');
//
//                     // Obtiene el id del examen en el que está la pregunta
//                     $sql="SELECT e.id FROM pregunta p LEFT JOIN seccion s ON p.seccion_id = s.id LEFT JOIN examen e ON s.examen_id = e.id WHERE p.id = $pregunta_id";
//
//                     // Get db object
//                     $db = new db();
//                     // Connect
//                     $db = $db->connect();
//                     $stmt = $db->query($sql);
//                     $examenes = $stmt->fetchAll(PDO::FETCH_OBJ);
//                     $examen_id = $examenes[0]->id;
//
//                     // Si el examen id no existe, inventa uno imposible para devolver sin acceso
//                     if (!$examen_id) {
//                         $examen_id = -1;
//                     }
//
//                     // Si el profesor tiene acceso a la pregunta, devuelve los detalles
//                     if (verificarPermisosProfesorExamen($profesor_id, $examen_id)) {
//                         $sql="SELECT p.*, s.titulo AS titulo_seccion, s.descripcion AS descripcion_seccion, e.nombre AS nombre_examen, m.nombre AS nombre_materia FROM pregunta p LEFT JOIN seccion s ON p.seccion_id = s.id LEFT JOIN examen e ON s.examen_id = e.id LEFT JOIN materia m ON e.materia_id = m.id WHERE p.id = $pregunta_id";
//                         $stmt = $db->query($sql);
//                         $preguntas = $stmt->fetchAll(PDO::FETCH_OBJ);
//
//                         return dataResponse($response, $preguntas[0]);
//                     } else {
//                         // Si el profesor no tiene acceso al examen, devuelve error 403
//                         $db = null;
//                         return respondWithError($response, 'No tiene permisos para ver la pregunta seleccionada.', 403);
//                     }
//                 } catch (PDOException $e) {
//                     $db = null;
//                     return respondWithError($response, $e->getMessage(), 500);
//                 }
//             } else {  // if (!empty($user_found)) {
//                 $db = null;
//                 return respondWithError($response, 'Error de login, usuario no encontrado', 401);
//             }
//         } else { // if (!empty($access_token)) {
//             $db = null;
//             return respondWithError($response, 'Error de login, falta access token', 401);
//         }
//     } else { // if ($request->getHeaders()['HTTP_AUTHORIZATION']) {
//         $db = null;
//         return respondWithError($response, 'Error de encabezado HTTP', 401);
//     }
// });
//

// Genera una nueva pregunta
$app->post('/api/alumno', function (Request $request, Response $response) {
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
                    $nombre= $request->getParam('nombre');
                    $apellido= $request->getParam('apellido');
                    $email= strtolower($request->getParam('email'));
                    $clase= $request->getParam('clase');

                    if ($nombre && $apellido && $email) {

                        // Verifica que el mail tenga estructura de mail
                        if (filter_var($email, FILTER_VALIDATE_EMAIL)) {

                            // Verifica que no haya un alumno con el email ingresado
                            $sql="SELECT * FROM alumno WHERE email = '$email'";
                            // Get db object
                            $db = new db();
                            // Connect
                            $db = $db->connect();
                            $stmt = $db->query($sql);
                            $alumnos = $stmt->fetchAll(PDO::FETCH_OBJ);

                            if (count($alumnos)==0) {
                                // Si no hay alumnos con ese email lo guarda en la base de datos
                                $sql="INSERT INTO alumno (nombre, apellido, email) VALUES (:nombre, :apellido, :email)";

                                $stmt = $db->prepare($sql);
                                $stmt->bindParam(':nombre', $nombre);
                                $stmt->bindParam(':apellido', $apellido);
                                $stmt->bindParam(':email', $email);
                                $stmt->execute();

                                $sql="SELECT * FROM alumno WHERE id = LAST_INSERT_ID()";
                                $stmt = $db->query($sql);
                                $alumnos = $stmt->fetchAll(PDO::FETCH_OBJ);

                                $alumno_creado = $alumnos[0];

                                // Si el request trajo una clase se debe asignar el alumno a la clase
                                if ($clase) {
                                    // Verifica que la clase exista
                                    $sql="SELECT * FROM clase WHERE id = $clase";
                                    $stmt = $db->query($sql);
                                    $clases = $stmt->fetchAll(PDO::FETCH_OBJ);

                                    if (count($clases)>0) {
                                        $alumno_id = $alumno_creado->id;
                                        $sql = "INSERT INTO clase_x_alumno (clase_id,alumno_id) VALUES (:clase_id,:alumno_id)";

                                        $stmt = $db->prepare($sql);
                                        $stmt->bindParam(':clase_id', $clase);
                                        $stmt->bindParam(':alumno_id', $alumno_id);
                                        $stmt->execute();

                                        // Si no hay clase especificada y el alumno fue creado, responde
                                        $alumno_respuesta->mensaje='Alumno creado correctamente, agregado a la clase especificada.';
                                        $alumno_respuesta->alumno='Creado correctamente, id '.$alumno_id;
                                        $alumno_respuesta->alumno = $alumno_creado;
                                        $alumno_respuesta->clase='Alumno agregado a la clase correctamente';
                                        $db = null;
                                        return dataResponse($response, $alumno_respuesta);
                                    } else {
                                        $alumno_id = $alumno_creado->id;
                                        // Si no hay clase especificada y el alumno fue creado, responde
                                        $alumno_respuesta->mensaje='Alumno creado correctamente, no agregado a la clase especificada.';
                                        $alumno_respuesta->alumno='Creado correctamente, id '.$alumno_id;
                                        $alumno_respuesta->alumno = $alumno_creado;
                                        $alumno_respuesta->clase='La clase especificada no existe, el alumno no fue agregado.';
                                        $db = null;
                                        return dataResponse($response, $alumno_respuesta);
                                    }
                                } else {  // if ($clase) {
                                    // Si no hay clase especificada y el alumno fue creado, responde
                                    $alumno_id = $alumno_creado->id;
                                    $alumno_respuesta->mensaje='Alumno creado correctamente.';
                                    $alumno_respuesta->alumno='Creado correctamente, id '.$alumno_id;
                                    $alumno_respuesta->alumno = $alumno_creado;
                                    $db = null;
                                    return dataResponse($response, $alumno_respuesta);
                                }
                            } else { // if (count($alumnos)==0) {
                                $db = null;
                                return respondWithError($response, 'Ya existe un alumno con ese email.', 403);
                            }
                        } else { // if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                            $db = null;
                            return respondWithError($response, 'El email especificado no tiene estructura de dirección de email.', 403);
                        }
                    } else { // if ($seccion_id && $texto_pregunta && $tipo_pregunta && $habilitada) {
                        $db = null;
                        return respondWithError($response, 'Parámetros faltantes. Debe el nombre, apellido y email del alumno.', 400);
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
