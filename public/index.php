<?php
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require '../vendor/autoload.php';

require '../src/auxiliares/funciones.php';

$app = new \Slim\App;

// Customer routes
require '../src/routes/fotos.php';
require '../src/routes/cors.php';


$app->run();

// echo('hla');
