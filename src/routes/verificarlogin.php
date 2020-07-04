<?php

use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

// Todas las preguntas
$app->get('/api/verificarlogin', function (Request $request, Response $response) {
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
                $objeto_respuesta->texto = 'Usuario encontrado, login correcto.';
                return dataResponse($response, $objeto_respuesta);
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
