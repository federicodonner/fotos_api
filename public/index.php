<?php
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require '../vendor/autoload.php';
require '../src/config/db.php';

require '../src/auxiliares/funciones.php';

$app = new \Slim\App;

$container = $app->getContainer();
$container['upload_directory'] = __DIR__ . '/imagenes';

$checkProxyHeaders = true; // Note: Never trust the IP address for security processes!
$trustedProxies = ['10.0.0.1', '10.0.0.2']; // Note: Never trust the IP address for security processes!
$app->add(new RKA\Middleware\IpAddress($checkProxyHeaders, $trustedProxies));


// Customer routes

require '../src/routes/oauth.php';
require '../src/routes/examen.php';
require '../src/routes/examendesdehash.php';
require '../src/routes/tomarexamen.php';
require '../src/routes/periodo.php';
require '../src/routes/hashperiodo.php';
require '../src/routes/respuestainterim.php';
require '../src/routes/respuestafinal.php';
require '../src/routes/verificarconexion.php';
require '../src/routes/pregunta.php';
require '../src/routes/profesor.php';
require '../src/routes/corregirperiodo.php';
require '../src/routes/verificarcorreccion.php';
require '../src/routes/verificarlogin.php';
require '../src/routes/clase.php';
require '../src/routes/alumnoclase.php';
require '../src/routes/alumno.php';
require '../src/routes/cors.php';


$app->run();

// echo('hla');
