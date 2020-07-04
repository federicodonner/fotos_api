<?php

use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

// Obtener todos los períodos del profesor
$app->get('/api/periodo', function (Request $request, Response $response) {
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

                    $sql = "SELECT e.nombre AS examen_nombre, m.nombre AS materia_nombre, p.* FROM examen e LEFT JOIN materia_x_profesor mxp ON e.materia_id = mxp.materia_id LEFT JOIN periodo p ON e.id = p.examen_id LEFT JOIN materia m ON e.materia_id = m.id WHERE mxp.profesor_id = $profesor_id";

                    // Get db object
                    $db = new db();
                    // Connect
                    $db = $db->connect();

                    $stmt = $db->query($sql);
                    $periodos = $stmt->fetchAll(PDO::FETCH_OBJ);

                    // Agrega los examenes al array para respuesta
                    $periodosObject->periodos = $periodos;

                    $db = null;
                    return dataResponse($response, $periodosObject);
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

// Obtener un período específico
$app->get('/api/periodo/{id}', function (Request $request, Response $response) {
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
                    $periodo_id = $request->getAttribute('id');

                    // Obtiene los datos del período especificado
                    $sql = "SELECT e.nombre AS examen_nombre, m.nombre AS materia_nombre, p.*, e.id AS examen_id FROM examen e LEFT JOIN materia_x_profesor mxp ON e.materia_id = mxp.materia_id LEFT JOIN periodo p ON e.id = p.examen_id LEFT JOIN materia m ON e.materia_id = m.id WHERE p.id = $periodo_id";

                    // Get db object
                    $db = new db();
                    // Connect
                    $db = $db->connect();

                    $stmt = $db->query($sql);
                    $periodos = $stmt->fetchAll(PDO::FETCH_OBJ);
                    $periodo = $periodos[0];
                    $examen_id = $periodo->examen_id;

                    // Verifica que el profesor tenga permisos para ver ese período
                    if (verificarPermisosProfesorExamen($profesor_id, $examen_id)) {

                        // Agrega los alumnos al período
                        $sql = "SELECT a.*, axp.estado, axp.nota FROM alumno_x_periodo axp LEFT JOIN alumno a ON axp.alumno_id = a.id WHERE periodo_id = $periodo_id ORDER BY a.apellido, a.nombre";
                        $stmt = $db->query($sql);
                        $alumnos = $stmt->fetchAll(PDO::FETCH_OBJ);

                        // Genera un contador para cada alumno para el frontend
                        $orden = 1;

                        // Por cada alumno, busca las respuestas del período
                        foreach ($alumnos as $alumno) {
                            // Guarda el orden en el objeto de alumno y lo incrementa
                            $alumno->orden = $orden;
                            $orden = $orden + 1;

                            $alumno_id = $alumno->id;
                            $sql = "SELECT ip.id, ip.nota, ip.comentarios_profesor, ip.orden, ip.texto_respuesta AS respuesta_alumno, ip.timestamp_respuesta, p.texto_pregunta, p.imagen_pregunta, p.puntaje, p.texto_respuesta FROM instancia_preguntas ip LEFT JOIN pregunta p ON ip.pregunta_id = p.id  WHERE alumno_id = $alumno_id AND periodo_id = $periodo_id ORDER BY orden";

                            $stmt = $db->query($sql);
                            $respuestas = $stmt->fetchAll(PDO::FETCH_OBJ);

                            // En cada pregunta suma el puntaje para saber el puntaje total del examen
                            $puntaje_examen_total = 0;

                            // Por cada respuesta, agrega las respuestas interim
                            foreach ($respuestas as $respuesta) {
                                // Toma la fechahora de submit de una respuesta para saber cuándo entregó el examen
                                // recorre todas para evitar guardar un null
                                if ($respuesta->timestamp_respuesta) {
                                    $fechahora_respuesta = $respuesta->timestamp_respuesta;
                                }

                                // Suma los puntajes
                                $puntaje_examen_total = $puntaje_examen_total + $respuesta->puntaje;

                                $instancia_id = $respuesta->id;
                                $sql = "SELECT texto_respuesta, fechahora, id FROM respuesta_interim WHERE instancia_id = $instancia_id";
                                $stmt = $db->query($sql);
                                $respuestas_interim = $stmt->fetchAll(PDO::FETCH_OBJ);

                                $respuesta->respuestas_interim = $respuestas_interim;
                            }
                            $alumno->fechahora_respuesta = $fechahora_respuesta;
                            $alumno->respuestas = $respuestas;
                            $alumno->puntaje_examen_total = $puntaje_examen_total;
                        }

                        $periodo->alumnos = $alumnos;

                        $db = null;
                        return dataResponse($response, $periodo);
                    } else { //     if (verificarPermisosProfesorExamen($profesor_id, $examen_id)) {
                        // Si el profesor no tiene acceso al examen, devuelve error 403
                        $db = null;
                        return respondWithError($response, 'No tiene permisos para ver el período seleccionado.', 403);
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




// Genera un nuevo período de un examen
$app->post('/api/periodo', function (Request $request, Response $response) {
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
                    $examen_id = $request->getParam('examen');
                    $inicio_dttm = $request->getParam('inicio');
                    $fin_dttm = $request->getParam('fin');
                    $clase_id = $request->getParam('clase');
                    $nombre = $request->getParam('nombre');

                    if ($examen_id && $inicio_dttm && $fin_dttm && $clase_id) {

                        // Obtiene los datos del usuario logueado para armar la query
                        $profesor_id = $user_found[0]->profesor_id;

                        // Si el profesor tiene acceso al examen, se crea el período
                        if (verificarPermisosProfesorExamen($profesor_id, $examen_id)) {
                            $sql = "INSERT INTO periodo (examen_id, inicio_dttm, fin_dttm, nombre) VALUES (:examen_id,:inicio_dttm,:fin_dttm,:nombre)";

                            // Get db object
                            $db = new db();
                            // Connect
                            $db = $db->connect();

                            $stmt = $db->prepare($sql);
                            $stmt->bindParam(':examen_id', $examen_id);
                            $stmt->bindParam(':inicio_dttm', $inicio_dttm);
                            $stmt->bindParam(':fin_dttm', $fin_dttm);
                            $stmt->bindParam(':nombre', $nombre);
                            $stmt->execute();

                            // Obtiene el id del período recién creado
                            $id_insertado = $db->lastInsertId();

                            // Hace la consulta para devolver el período creado
                            $sql="SELECT * FROM periodo WHERE id = $id_insertado";

                            $stmt = $db->query($sql);
                            $periodos = $stmt->fetchAll(PDO::FETCH_OBJ);
                            $periodo = $periodos[0];

                            // Una vez creado el período, se le asigna a cada alumno
                            $sql="SELECT * FROM clase WHERE id = $clase_id";

                            $stmt = $db->query($sql);
                            $clases = $stmt->fetchAll(PDO::FETCH_OBJ);

                            $sql="SELECT a.* FROM clase_x_alumno cxa LEFT JOIN alumno a ON cxa.alumno_id = a.id WHERE clase_id = $clase_id";

                            $stmt = $db->query($sql);
                            $alumnos = $stmt->fetchAll(PDO::FETCH_OBJ);

                            $clases[0]->alumnos = $alumnos;

                            $periodo->clase = $clases[0];

                            foreach ($alumnos as $alumno) {
                                $alumno_id = $alumno->id;

                                $sql="INSERT INTO alumno_x_periodo (periodo_id, alumno_id) VALUES (:periodo_id, :alumno_id)";

                                $stmt = $db->prepare($sql);
                                $stmt->bindParam(':periodo_id', $id_insertado);
                                $stmt->bindParam(':alumno_id', $alumno_id);
                                $stmt->execute();
                            }

                            $db = null;
                            return dataResponse($response, $periodo);
                        } else { // if ($acceso_permitido_al_examen) {
                            // Si el profesor no tiene acceso al examen, devuelve error 403
                            $db = null;
                            return respondWithError($response, 'No tiene permisos para crear periodos del examen seleccionado.', 403);
                        }
                    } else { // if ($examen_id && $inicio_dttm && $fin_dttm) {
                        $db = null;
                        return respondWithError($response, 'Parámetros faltantes. Debe incluir materia, fecha y hora de incio del período y fecha y hora de finalización del período.', 400);
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


// Marca el período como corregido
$app->put('/api/periodo/{id}', function (Request $request, Response $response) {
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
                    $periodo_id = $request->getAttribute('id');

                    // Obtiene los datos del período especificado
                    $sql = "SELECT e.nombre AS examen_nombre, m.nombre AS materia_nombre, p.*, e.id AS examen_id FROM examen e LEFT JOIN materia_x_profesor mxp ON e.materia_id = mxp.materia_id LEFT JOIN periodo p ON e.id = p.examen_id LEFT JOIN materia m ON e.materia_id = m.id WHERE p.id = $periodo_id";

                    // Get db object
                    $db = new db();
                    // Connect
                    $db = $db->connect();

                    $stmt = $db->query($sql);
                    $periodos = $stmt->fetchAll(PDO::FETCH_OBJ);
                    $periodo = $periodos[0];
                    $examen_id = $periodo->examen_id;

                    // Verifica que el profesor tenga permisos para ver ese período
                    if (verificarPermisosProfesorExamen($profesor_id, $examen_id)) {
                        // Si los tiene, actualiza el estado del período a 1
                        $sql = "UPDATE periodo SET estado='1' WHERE id = $periodo_id";
                        $stmt = $db->prepare($sql);
                        $stmt->execute();

                        // Una vez que actualiza el período,
                        // le manda mails a los alumnos con su link para ver la corrección
                        $sql="SELECT ih.hash, a.nombre, a.apellido, a.email, e.nombre AS examen_nombre, m.nombre AS materia_nombre, m.id AS materia_id FROM instancia_hash ih LEFT JOIN alumno a ON ih.alumno_id = a.id LEFT JOIN periodo p ON ih.periodo_id = p.id LEFT JOIN examen e ON p.examen_id = e.id LEFT JOIN materia m ON e.materia_id = m.id WHERE periodo_id = $periodo_id";

                        $stmt = $db->query($sql);
                        $alumnos = $stmt->fetchAll(PDO::FETCH_OBJ);

                        foreach ($alumnos as $alumno) {
                            $alumno_nombre = $alumno->nombre;
                            $alumno_apellido = $alumno->apellido;
                            $alumno_email = $alumno->email;
                            $alumno_hash = $alumno->hash;
                            $link = 'http://www.albus.federicodonner.com/verificarcorreccion/'.$alumno_hash;
                            $materia_nombre = $alumno->materia_nombre;
                            $examen_nombre = $alumno->examen_nombre;
                            $materia_id = $alumno->materia_id;

                            enviarLinkACorreccion($alumno_nombre, $alumno_apellido, $alumno_email, $link, $materia_nombre, $examen_nombre);
                        }

                        // Una vez que termina de enviarle los mails a los alumnos, le manda un mail de confirmación al profesor
                        $sql="SELECT * FROM materia_x_profesor mxp LEFT JOIN profesor p on mxp.profesor_id = p.id WHERE mxp.materia_id = $materia_id";

                        $stmt = $db->query($sql);
                        $profesores = $stmt->fetchAll(PDO::FETCH_OBJ);

                        $profesor_email = $profesores[0]->email;
                        $profesor_nombre = $profesores[0]->nombre;

                        confirmarEnvioCorreccion($alumnos, $materia_nombre, $examen_nombre, $profesor_nombre, $profesor_email);


                        $db = null;
                        $respuesta_invocacion->mensaje = "Período actualizado correctamente";
                        return dataResponse($response, $respuesta_invocacion);
                    } else { //     if (verificarPermisosProfesorExamen($profesor_id, $examen_id)) {
                        // Si el profesor no tiene acceso al examen, devuelve error 403
                        $db = null;
                        return respondWithError($response, 'No tiene permisos para ver el período seleccionado.', 403);
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
