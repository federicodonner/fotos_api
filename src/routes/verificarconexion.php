<?php
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

// Verifica que la API está arriba y hay conexión.
// Utilizado antes de enviar las respuestas del examen
$app->get('/api/verificarconexion', function (Request $request, Response $response) {
    $dataRespuesta->estado = "Exito";
    return dataResponse($response, $dataRespuesta);
});
