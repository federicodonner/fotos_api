<?php

use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

// Devuelve todas las clases
$app->get('/api/clase', function (Request $request, Response $response) {
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
                    $sql="SELECT * FROM clase";

                    // Get db object
                    $db = new db();
                    // Connect
                    $db = $db->connect();

                    $stmt = $db->query($sql);
                    $clases = $stmt->fetchAll(PDO::FETCH_OBJ);


                    foreach ($clases as $clase) {
                        $clase_id = $clase->id;
                        $sql="SELECT count(*) AS cuenta FROM clase_x_alumno axc WHERE clase_id = $clase_id";

                        $stmt = $db->query($sql);
                        $cuentas = $stmt->fetchAll(PDO::FETCH_OBJ);

                        $clase->alumnos = $cuentas[0]->cuenta;
                    }

                    // Genero un objeto para devolver
                    $clasesObject->clases = $clases;

                    $db = null;
                    return dataResponse($response, $clasesObject);
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

// Obtener una clase en particular
$app->get('/api/clase/{id}', function (Request $request, Response $response) {
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
                    $clase_id = $request->getAttribute('id');

                    $sql = "SELECT * FROM clase WHERE id = $clase_id";

                    // Get db object
                    $db = new db();
                    // Connect
                    $db = $db->connect();
                    $stmt = $db->query($sql);
                    $clases = $stmt->fetchAll(PDO::FETCH_OBJ);

                    $clase_objeto = $clases[0];

                    $sql="SELECT a.* FROM clase_x_alumno cxa LEFT JOIN alumno a ON cxa.alumno_id = a.id WHERE cxa.clase_id = $clase_id";

                    $stmt = $db->query($sql);
                    $alumnos = $stmt->fetchAll(PDO::FETCH_OBJ);

                    $clase_objeto->alumnos = $alumnos;

                    return dataResponse($response, $clase_objeto);
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
$app->post('/api/clase', function (Request $request, Response $response) {
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
                    $ano= $request->getParam('ano');
                    $semestre= $request->getParam('semestre');
                    $nombre= $request->getParam('nombre');


                    if ($ano && $semestre && $nombre) {
                        $sql="INSERT INTO clase (ano, semestre, nombre) VALUES (:ano,:semestre,:nombre)";

                        // Get db object
                        $db = new db();
                        // Connect
                        $db = $db->connect();

                        $stmt = $db->prepare($sql);
                        $stmt->bindParam(':ano', $ano);
                        $stmt->bindParam(':semestre', $semestre);
                        $stmt->bindParam(':nombre', $nombre);
                        $stmt->execute();

                        $sql="SELECT * FROM clase WHERE id = LAST_INSERT_ID()";
                        $stmt = $db->query($sql);
                        $clases = $stmt->fetchAll(PDO::FETCH_OBJ);

                        $db = null;
                        return dataResponse($response, $clases[0]);
                    } else { //   if ($ano && $semestre && $nombre) {
                        $db = null;
                        return respondWithError($response, 'Parámetros faltantes. Debe incluir el año, el semestre y el nombre de la clase.', 400);
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
