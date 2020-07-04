<?php

use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

// Genera y envía los hash del período para los alumnos
$app->post('/api/hashperiodo', function (Request $request, Response $response) {
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
                    $periodo_id= $request->getParam('periodo');

                    if ($periodo_id) {

                      // Obtiene los datos del usuario logueado para armar la query
                        $profesor_id = $user_found[0]->profesor_id;

                        // Obtiene el id del examen para verificar si el profesor tiene permisos sobre ese examen
                        $sql = "SELECT * FROM periodo WHERE id = $periodo_id";

                        // Get db object
                        $db = new db();
                        // Connect
                        $db = $db->connect();

                        $stmt = $db->query($sql);
                        $periodos = $stmt->fetchAll(PDO::FETCH_OBJ);

                        // Verifica que el período especificado exista
                        if ($periodos) {
                            $examen_id = $periodos[0]->examen_id;

                            // Obtiene la información necesaria para generar las instancias de las preguntas para cada alumno
                            // Se hace esta query afuera para evitar repetirla para acada alumno
                            $sql = "SELECT * FROM seccion WHERE examen_id = $examen_id ORDER BY orden";
                            $stmt = $db->query($sql);
                            $secciones = $stmt->fetchAll(PDO::FETCH_OBJ);

                            // Obtiene los datos del examen para el email a los alumnos
                            $sql = "SELECT e.id, e.nombre AS examen_nombre, m.nombre AS materia_nombre FROM examen e LEFT JOIN materia m ON e.materia_id = m.id WHERE e.id = $examen_id";
                            $stmt = $db->query($sql);
                            $examenes = $stmt->fetchAll(PDO::FETCH_OBJ);

                            // Si el profesor tiene acceso al examen, se crea el período
                            if (verificarPermisosProfesorExamen($profesor_id, $examen_id)) {
                                // Obtiene los datos del profesor para copiarlo en los mails a los alumnos
                                $sql = "SELECT * from profesor WHERE id = $profesor_id";
                                $stmt = $db->query($sql);
                                $profesores = $stmt->fetchAll(PDO::FETCH_OBJ);

                                $sql = "SELECT a.nombre, a.apellido, a.email, a.id AS alumno_id from alumno_x_periodo axp LEFT JOIN alumno a on axp.alumno_id = a.id WHERE periodo_id = $periodo_id";
                                $stmt = $db->query($sql);
                                $alumnos = $stmt->fetchAll(PDO::FETCH_OBJ);

                                // Crea un array para guardar los alumnos impactados
                                $alumnos_hash_creado = array();
                                foreach ($alumnos as $alumno) {
                                    $hash = random_str(12);
                                    $alumno_id = $alumno->alumno_id;

                                    $sql="INSERT INTO instancia_hash (alumno_id, periodo_id, hash) VALUES ($alumno_id, $periodo_id, '$hash')";
                                    $stmt = $db->prepare($sql);
                                    $stmt->execute();

                                    // $sql = "SELECT * FROM alumno WHERE id = $alumno_id";
                                    // $stmt = $db->query($sql);
                                    // $alumnos_impactados = $stmt->fetchAll(PDO::FETCH_OBJ);
                                    // $alumno_impactado = $alumnos_impactados[0];

                                    $link_examen = "http://www.albus.federicodonner.com/examen/".$hash;
                                    $alumno->link = $link_examen;

                                    array_push($alumnos_hash_creado, $alumno);

                                    // Una vez que el alumno está creado, le envía un email con el link al examen y los detalles.
                                    $alumno_nombre = $alumno->nombre;
                                    $alumno_apellido = $alumno->apellido;
                                    $alumno_email = $alumno->email;
                                    $examen_nombre = $examenes[0]->examen_nombre;
                                    $materia_nombre = $examenes[0]->materia_nombre;
                                    $profesor_email = $profesores[0]->email;
                                    $fechahora_inicio = $periodos[0]->inicio_dttm;

                                    enviarLinkAExamen($alumno_nombre, $alumno_apellido, $alumno_email, $link_examen, $materia_nombre, $examen_nombre, $fechahora_inicio, $profesor_email);

                                    // Crea las instancia de las preguntas para cada alumno en la base datos
                                    // Recorre todas las secciones buscando la cantidad de preguntas de la sección
                                    $orden = 1;
                                    foreach ($secciones as $seccion) {
                                        $seccion_id = $seccion->id;
                                        $cantidad_preguntas = $seccion->cantidad_preguntas;

                                        // Obtiene un número al azar de preguntas ordenadas aleatoreamente
                                        $sql="SELECT * FROM pregunta where seccion_id = $seccion_id ORDER BY RAND() LIMIT $cantidad_preguntas";
                                        $stmt = $db->query($sql);
                                        $preguntas_seccion = $stmt->fetchAll(PDO::FETCH_OBJ);


                                        // Una vez que tiene las preguntas aleatoreas de la seccion, las inserta en la base de datos
                                        foreach ($preguntas_seccion as $pregunta_seccion) {
                                            $pregunta_id = $pregunta_seccion->id;
                                            $timestamp_ahora = time();

                                            $sql = "INSERT INTO instancia_preguntas (pregunta_id, alumno_id, periodo_id, timestamp_instancia, orden) VALUES (:pregunta_id,:alumno_id,:periodo_id,:timestamp_ahora,:orden)";
                                            $stmt = $db->prepare($sql);
                                            $stmt->bindParam(':pregunta_id', $pregunta_id);
                                            $stmt->bindParam(':alumno_id', $alumno_id);
                                            $stmt->bindParam(':periodo_id', $periodo_id);
                                            $stmt->bindParam(':timestamp_ahora', $timestamp_ahora);
                                            $stmt->bindParam(':orden', $orden);
                                            $stmt->execute();
                                            $orden = $orden + 1;
                                        }
                                    }
                                }

                                // Agrega los alumnos impactados al array para respuesta
                                $alumnosObject->alumnos = $alumnos_hash_creado;

                                $db = null;
                                return dataResponse($response, $alumnosObject);
                            } else { // if ($acceso_permitido_al_examen) {
                                // Si el profesor no tiene acceso al examen, devuelve error 403
                                $db = null;
                                return respondWithError($response, 'No tiene permisos para crear periodos del examen seleccionado.', 403);
                            }
                        } else { // if ($periodos.length()) {
                            $db = null;
                            return respondWithError($response, 'El período especificado no existe', 400);
                        }
                    } else { // if ($examen_id && $inicio_dttm && $fin_dttm) {
                        $db = null;
                        return respondWithError($response, 'Parámetros faltantes. Debe incluir el período del examen.', 400);
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



// Actualiza la fechahora_ingreso del hash indicado
$app->put('/api/hashperiodo', function (Request $request, Response $response) {
    try {

        // Verifica que todos los parámetros fueron enviados
        $hash= $request->getParam('hash');

        if ($hash) {
            // Obtiene el id del examen para verificar si el profesor tiene permisos sobre ese examen
            $sql = "SELECT * FROM instancia_hash WHERE hash = '$hash' AND fechahora_ingreso is null";

            // Get db object
            $db = new db();
            // Connect
            $db = $db->connect();

            $stmt = $db->query($sql);
            $instancias = $stmt->fetchAll(PDO::FETCH_OBJ);

            // Verifica que la instancia especificado exista
            if ($instancias) {
                $instancia_id = $instancias[0]->id;

                $fechahora_ahora = time();
                // Actualiza el campo de fechahora_ahora con la fecha actual
                $sql = "UPDATE instancia_hash SET fechahora_ingreso = :fechahora_ahora WHERE id = $instancia_id";

                $stmt = $db->prepare($sql);
                $stmt->bindParam(':fechahora_ahora', $fechahora_ahora);
                $stmt->execute();

                // Selecciona el hash modificado para devolverlo
                $sql="SELECT hash, fechahora_ingreso FROM instancia_hash WHERE id = $instancia_id";
                $stmt = $db->query($sql);
                $instancias = $stmt->fetchAll(PDO::FETCH_OBJ);
                $instancia = $instancias[0];

                return dataResponse($response, $instancia);
            } else { // if ($instancias) {
                return respondWithError($response, 'No hay un examen con el hash especificado.', 400);
            }
        } else { // if ($examen_id && $inicio_dttm && $fin_dttm) {
            return respondWithError($response, 'Parámetros faltantes. Debe incluir hash del examen.', 400);
        }
    } catch (PDOException $e) {
        return respondWithError($response, $e->getMessage(), 500);
    }
});
