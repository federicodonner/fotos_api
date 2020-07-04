<?php

use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

// Devuelve todos los profesores
$app->get('/api/profesor', function (Request $request, Response $response) {
    // Verify if the auth header is available
    if ($request->getHeaders()['HTTP_AUTHORIZATION']) {
        // If the header is available, get the token
        $access_token = $request->getHeaders()['HTTP_AUTHORIZATION'][0];
        $access_token = explode(" ", $access_token)[1];
        // Find the access token, if a user is returned, post the products
        if (!empty($access_token)) {
            $user_found = verifyToken($access_token);
            // Verify that there is a user logged in
            if (!empty($user_found)) {
                $sql = "SELECT * FROM profesor";
                try {
                    // Get db object
                    $db = new db();
                    // Connect
                    $db = $db->connect();

                    $stmt = $db->query($sql);
                    $profesores = $stmt->fetchAll(PDO::FETCH_OBJ);
                    $db = null;

                    // Delete the password hash index
                    foreach ($profesores as $profesor) {
                        unset($profesor->password);
                    }
                    // Agrega los examenes al array para respuesta
                    $profesoresObject->profesores = $profesores;

                    return dataResponse($response, $profesoresObject);
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



// Devuevle un solo profesor
$app->get('/api/profesor/{id}', function (Request $request, Response $response) {
    // Verify if the auth header is available
    if ($request->getHeaders()['HTTP_AUTHORIZATION']) {
        // If the header is available, get the token
        $access_token = $request->getHeaders()['HTTP_AUTHORIZATION'][0];
        $access_token = explode(" ", $access_token)[1];
        // Find the access token, if a user is returned, post the products
        if (!empty($access_token)) {
            $user_found = verifyToken($access_token);
            // Verify that there is a user logged in
            if (!empty($user_found)) {
                $id = $request->getAttribute('id');
                $sql = "SELECT * FROM profesor WHERE id = $id";

                try {
                    // Get db object
                    $db = new db();
                    // Connect
                    $db = $db->connect();

                    $stmt = $db->query($sql);
                    $profesores = $stmt->fetchAll(PDO::FETCH_OBJ);
                    $db = null;

                    // Add the users array inside an object
                    if (!empty($profesores)) {
                        // Delete the password hash for the response
                        unset($profesores[0]->password);

                        $profesor = $profesores[0];
                        return dataResponse($response, $profesor);
                    } else {
                        return respondWithError($response, 'Id incorrecto', 401);
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



// Agrega un profesor
$app->post('/api/profesor', function (Request $request, Response $response) {

    // Get the user's details from the request body
    $nombre = $request->getParam('nombre');
    $email = $request->getParam('email');
    $password = $request->getParam('password');

    // Verify that the information is present
    if ($nombre && $email && $password) {
        // Verify that the email has an email format
        if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
            // Check that there is no other users's with the same username
            $sql = "SELECT email FROM profesor where email = '$email'";

            try {
                // Get db object
                $db = new db();
                // Connect
                $db = $db->connect();

                $stmt = $db->query($sql);
                $usuarios = $stmt->fetchAll(PDO::FETCH_OBJ);

                if (empty($usuarios)) {

                    // If it is, create the hash for storage
                    $password_hash = password_hash($password, PASSWORD_BCRYPT);
                    $false = 0;

                    // Store the information in the database
                    $sql = "INSERT INTO profesor (nombre, email, password, pendiente_cambio_pass, activo) VALUES (:nombre,:email,:password,:pendiente_cambio_pass,:activo)";

                    $stmt = $db->prepare($sql);
                    $stmt->bindparam(':nombre', $nombre);
                    $stmt->bindparam(':password', $password_hash);
                    $stmt->bindparam(':email', $email);
                    $stmt->bindparam(':pendiente_cambio_pass', $false);
                    $stmt->bindparam(':activo', $false);

                    $stmt->execute();

                    $sql="SELECT * FROM profesor WHERE id = LAST_INSERT_ID()";
                    $stmt = $db->query($sql);
                    $profesores = $stmt->fetchAll(PDO::FETCH_OBJ);

                    unset($profesores[0]->password);

                    $profesor = $profesores[0];

                    // Si se crea el profesor correctamente, lo loguea
                    // Store the user token in the database
                    // Prepare viarables
                    $access_token = random_str(32);
                    $now = time();
                    $user_id = $profesor->id;

                    // SQL statement
                    $sql = "INSERT INTO login (profesor_id,token,fechahora) VALUES (:user_id,:token,:now)";

                    $stmt = $db->prepare($sql);

                    $stmt->bindParam(':user_id', $user_id);
                    $stmt->bindParam(':token', $access_token);
                    $stmt->bindParam(':now', $now);

                    $stmt->execute();

                    $profesor->token = $access_token;
                    $profesor->grant_type = "password";

                    $db = null;

                    return dataResponse($response, $profesor);
                } else { // if (empty($user)) {
                    return respondWithError($response, 'El usuario ya existe', 401);
                }
            } catch (PDOException $e) {
                $db = null;
                return respondWithError($response, $e->getMessage(), 500);
            }
        } else { // if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return respondWithError($response, 'Formato de email incorrecto', 401);
        }
    } else { // if ($name && $username && $password && $email) {
        return respondWithError($response, 'Campos incorrectos', 401);
    }
});


// Actualizar profesor
$app->put('/api/usuario/{id}', function (Request $request, Response $response) {
    $params = $request->getBody();
    if ($request->getHeaders()['HTTP_AUTHORIZATION']) {
        $access_token = $request->getHeaders()['HTTP_AUTHORIZATION'][0];
        $access_token = explode(" ", $access_token)[1];
        // Find the access token, if a user is returned, post the products
        if (!empty($access_token)) {
            $user_found = verifyToken($access_token);
            if (!empty($user_found)) {
                $id = $request->getAttribute('id');

                $price_s = $request->getParam('price_s');
                $price_l = $request->getParam('price_l');
                $menuMonday = $request->getParam('menuMonday');
                $menuTuesday = $request->getParam('menuTuesday');
                $menuWednesday = $request->getParam('menuWednesday');
                $menuThursday = $request->getParam('menuThursday');
                $menuFriday = $request->getParam('menuFriday');
                $menuSaturday = $request->getParam('menuSaturday');
                $menuSunday =  $request->getParam('menuSunday');

                $sql = "UPDATE almuerzos SET
        price_s = :price_s,
        price_l = :price_l,
        menuMonday = :menuMonday,
        menuTuesday = :menuTuesday,
        menuWednesday = :menuWednesday,
        menuThursday = :menuThursday,
        menuFriday = :menuFriday,
        menuSaturday = :menuSaturday,
        menuSunday = :menuSunday
        WHERE id = $id";

                try {
                    // Get db object
                    $db = new db();
                    // Connect
                    $db = $db->connect();

                    $stmt = $db->prepare($sql);

                    $stmt->bindParam(':price_s', $price_s);
                    $stmt->bindParam(':price_l', $price_l);
                    $stmt->bindParam(':menuMonday', $menuMonday);
                    $stmt->bindParam(':menuTuesday', $menuTuesday);
                    $stmt->bindParam(':menuWednesday', $menuWednesday);
                    $stmt->bindParam(':menuThursday', $menuThursday);
                    $stmt->bindParam(':menuFriday', $menuFriday);
                    $stmt->bindParam(':menuSaturday', $menuSaturday);
                    $stmt->bindParam(':menuSunday', $menuSunday);

                    $stmt->execute();

                    echo('{"notice":{"text":"product updated"}}');
                } catch (PDOException $e) {
                    echo '{"error":{"text": '.$e->getMessage().'}}';
                }
            } else {
                return respondWithError($response, 'Error de login, usuario no encontrado');
            }
        } else {
            return respondWithError($response, 'Error de login, falta access token');
        }
    } else {
        return respondWithError($response, 'Error de encabezado HTTP');
    }
});
