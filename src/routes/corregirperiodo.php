<?php

use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

// Ingresa las notas y comentarios de la corrección de un período
$app->put('/api/corregirperiodo', function (Request $request, Response $response) {
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

                    // Obtiene los datos de la corrección para ingresar las notas
                    $periodo_id = $request->getParam('periodo_id');
                    $alumno_id = $request->getParam('alumno_id');
                    $notas = $request->getParam('notas');

                    // Obtiene los datos del período especificado
                    $sql = "SELECT e.id AS examen_id FROM examen e LEFT JOIN materia_x_profesor mxp ON e.materia_id = mxp.materia_id LEFT JOIN periodo p ON e.id = p.examen_id LEFT JOIN materia m ON e.materia_id = m.id WHERE p.id = $periodo_id";

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
                        // Si tiene permisos, ingresa las notas a la base de datos
                        foreach ($notas as $nota) {
                            $nota_objeto = json_decode($nota);
                            $pregunta_id = $nota_objeto->id;
                            $nota_pregunta = $nota_objeto->nota;
                            $comentarios_profesor = $nota_objeto->comentarios_profesor;

                            $sql="UPDATE instancia_preguntas SET nota = :nota, comentarios_profesor=:comentarios_profesor WHERE id = $pregunta_id";

                            $stmt = $db->prepare($sql);

                            $stmt->bindParam(':nota', $nota_pregunta);
                            $stmt->bindParam(':comentarios_profesor', $comentarios_profesor);

                            $stmt->execute();
                        }

                        // Verifica que todas las preguntas del período tengan nota
                        $sql="SELECT * FROM instancia_preguntas WHERE periodo_id = $periodo_id AND alumno_id = $alumno_id";

                        $stmt = $db->query($sql);
                        $preguntas = $stmt->fetchAll(PDO::FETCH_OBJ);

                        // Si alguna pregunta no es corregida, marca el flag
                        $todas_corregidas = true;
                        $suma_notas = 0;

                        foreach ($preguntas as $pregunta) {
                            if ($pregunta->nota == null) {
                                $todas_corregidas = false;
                            } else {
                                // Suma las notas para ingresar la nota total final en el período
                                $suma_notas = $suma_notas + $pregunta->nota;
                            }
                        }

                        if ($todas_corregidas) {
                            // Actualizar la nota del período
                            $sql="UPDATE alumno_x_periodo SET nota = :nota, estado = 1 WHERE periodo_id = $periodo_id AND alumno_id = $alumno_id";

                            $stmt = $db->prepare($sql);

                            $stmt->bindParam(':nota', $suma_notas);
                            $stmt->execute();

                            $db = null;

                            $respuesta->mensaje = 'Corrección ingresada correctamente';
                            return dataResponse($response, $respuesta);
                        } else {
                            return respondWithError($response, 'Faltan notas para marcar el período como corregido.', 417);
                        }
                    } else { // if ($acceso_permitido_al_examen) {
                        // Si el profesor no tiene acceso al examen, devuelve error 403
                        $db = null;
                        return respondWithError($response, 'No tiene permisos para editar el período seleccionado.', 403);
                    }

                    // $db = null;
                    // return dataResponse($response, $periodosObject);
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
