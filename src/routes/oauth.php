<?php
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

// Add product
$app->post('/api/oauth', function (Request $request, Response $response) {
    $params = $request->getBody();
    $grant_type = $request->getParam('grant_type');
    $access = $request->getParam('access');

    if ($grant_type == 'password') {
        $username = $request->getParam('username');
        // $username = json_decode($params)->user;
        //$pass = $request->getParam('pass');

        $sql = "SELECT * FROM profesor WHERE email = '$username'";
        try {
            // Get db object
            $db = new db();
            // Connect
            $db = $db->connect();

            $stmt = $db->query($sql);
            $profesores = $stmt->fetchAll(PDO::FETCH_OBJ);

            // Si no hay ningún usuario con ese nombre
            if ($profesores == null) {
                //cambio el estatus del mensaje e incluyo el mensaje de error
                return respondWithError($response, 'Nombre de usuario o password incorrecto', 409);
            } else {
                // Verifica el password contra el hash
                if (password_verify($access, $profesores[0]->password)) {
                    // Si el password coincide, verifica que el usuario esté activo
                    if ($profesores[0]->activo == 1) {
                        // Si el password coincide y el usuario está activo,
                        // genera el token y lo responde
                        $access_token = random_str(32);
                        $now = time();
                        $user_id = $profesores[0]->id;

                        // SQL statement
                        $sql = "INSERT INTO login (profesor_id,token,fechahora) VALUES (:user_id,:token,:now)";

                        $stmt = $db->prepare($sql);

                        $stmt->bindParam(':user_id', $user_id);
                        $stmt->bindParam(':token', $access_token);
                        $stmt->bindParam(':now', $now);

                        $stmt->execute();

                        $authRespuesta->token = $access_token;
                        $authRespuesta->grant_type = $grant_type;
                        $authRespuesta->user_id = $user_id;

                        $db = null;
                        return dataResponse($response, $authRespuesta);
                    } else { //   if ($profesores[0]->activo == 1) {
                        return respondWithError($response, 'Tu usuario aún no fue activado, ponte en contacto con el administrador.', 403);
                    }
                } else { //   if (password_verify($access, $profesores[0]->password)) {
                    // Si no coincide, devuelve error
                    return respondWithError($response, 'Nombre de usuario o password incorrecto', 409);
                }
            }
        } catch (PDOException $e) {
            $db = null;
            return respondWithError($response, $e->getMessage(), 500);
        }
    } elseif ($grant_type == 'token') {
        try {
            // Si el grant_type es token, voy a buscarlo a la base
            $sql = "SELECT * FROM login WHERE token = '$access'";

            // Get db object
            $db = new db();
            // Connect
            $db = $db->connect();

            $stmt = $db->query($sql);
            $tokens = $stmt->fetchAll(PDO::FETCH_OBJ);

            // Si no devuelve ningún token, devuelvo error
            if ($tokens == null) {
                //cambio el estatus del mensaje e incluyo el mensaje de error
                return respondWithError($response, 'Token incorrecto', 409);
            } else {  // if ($tokens == null) {

                // Si encuentra uno, devuelve los detalles del usuario
                $profesor->profesor_id = $tokens[0]->profesor_id;
                return dataResponse($response, $profesor);
            }
        } catch (PDOException $e) {
            $db = null;
            return respondWithError($response, $e->getMessage(), 500);
        }
    } else {
        $db = null;
        return respondWithError($response, 'Grant type incorrecto.', 406);
    }
});
