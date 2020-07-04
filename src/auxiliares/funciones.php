<?php

use \Psr\Http\Message\ResponseInterface as Response;

// Devuelve el usuario del login en base al token
  function verifyToken(String $access_token)
  {
      if (!empty($access_token)) {
          $sql = "SELECT * FROM login WHERE token = '$access_token'";
          try {
              // Get db object
              $db = new db();
              // Connect
              $db = $db->connect();
              $stmt = $db->query($sql);
              $users = $stmt->fetchAll(PDO::FETCH_OBJ);
              return $users;
          } catch (PDOException $e) {
              echo '{"error":{"text": '.$e->getMessage().'}}';
          }
      } else {
          return [];
      }
  };

// Responde con error especificado y mensaje
 function respondWithError(Response $response, String $errorText, Int $status)
 {
     $responseBody = array('status' => 'error', 'detail' => $errorText);
     $newResponse = $response
 ->withStatus($status)
 ->withJson($responseBody);
     return $newResponse;
 };

// Responde un objeto con status 201
function dataResponse(Response $response, object $data)
{
    $newResponse = $response
->withStatus(201)
->withJson($data);
    return $newResponse;
}


// Devuelve un string aleatorio de largo especificado
 function random_str($length, $keyspace = '23456789abcdefghjkmnpqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ')
 {
     $pieces = [];
     $max = mb_strlen($keyspace, '8bit') - 1;
     for ($i = 0; $i < $length; ++$i) {
         $pieces []= $keyspace[random_int(0, $max)];
     }
     return implode('', $pieces);
 };

// Verifica que un profesor tenga permisos para acceder a un examen específico
function verificarPermisosProfesorExamen(Int $profesor_id, Int $examen_id)
{

  // Si no se envía un id, devuelve falso
    if (!$examen_id) {
        return false;
    }

    $sql = "SELECT mxp.profesor_id FROM materia_x_profesor mxp LEFT JOIN examen e ON e.materia_id = mxp.materia_id WHERE e.id = $examen_id";
    try {
        // Get db object
        $db = new db();
        // Connect
        $db = $db->connect();
        $stmt = $db->query($sql);
        $profesores = $stmt->fetchAll(PDO::FETCH_OBJ);

        // Verifica que el profesor logueado tenga permisos para ver el examen
        $acceso_permitido_al_examen = false;

        foreach ($profesores as $profesor) {
            $examen_profesor_id = $profesor->profesor_id;
            if ($examen_profesor_id == $profesor_id) {
                $acceso_permitido_al_examen = true;
            }
        }

        return $acceso_permitido_al_examen;
    } catch (PDOException $e) {
        echo '{"error":{"text": '.$e->getMessage().'}}';
    }
}

function moveUploadedFile($directory, UploadedFile $uploadedFile)
{
    $extension = pathinfo($uploadedFile->getClientFilename(), PATHINFO_EXTENSION);
    $basename = bin2hex(random_bytes(8));
    $filename = sprintf('%s.%0.8s', $basename, $extension);

    $uploadedFile->moveTo($directory . DIRECTORY_SEPARATOR . $filename);

    return $filename;
}

// Función que envía el link del examen al alumno. Invocada por la función de hash de período.
function enviarLinkAExamen($alumno_nombre, $alumno_apellido, $alumno_email, $link, $materia_nombre, $examen_nombre, $fechahora_inicio, $profesor_email)
{
    $fecha_examen = date('d/m/Y', $fechahora_inicio);

    $from_mail = "no-reply-albus@federicodonner.com";
    $from_name = 'Albus evaluaciones online';
    $replyto = 'federico.donner@gmail.com';
    $asunto = "Link a tu evaluación ".$materia_nombre;
    $asunto_codificado = "=?utf-8?b?".base64_encode($asunto)."=?=";

    $header = "From: ".$from_name." <".$from_mail.">\r\n";
    $header .= "Reply-To: ".$replyto."\r\n";
    $header .="MIME-Version: 1.0"."\r\n";
    $header .="Content-Type: text/plain;charset=utf-8"."\r\n";

    $message = "Hola ".$alumno_nombre." ".$alumno_apellido.",\r\n\r\n";
    $message .= "Debajo encontrarás el link para ".$examen_nombre." de ".$materia_nombre.".\r\n";
    $message .= "Al ingresar verás instrucciones específicas para la evaluación. No podrás comenzar a completarla hasta que el período esté habilitado el ".$fecha_examen." a la hora correspondiente.\r\n";
    $message .= "Recuerda que sólo podrás ingresar a la evaluación una vez, así que asegúrate de comenzar la evaluación sólo si es seguro que podrás terminarla. Puedes seguir el link debajo para corroborar que tus datos sean los correctos, tendrás una confirmación adicional antes de comenzar la evaluación.\r\n\r\n";
    $message .= $link."\r\n\r\n";
    $message .= "¡Buena suerte!";

    mail($alumno_email, $asunto_codificado, $message, $header);
}


// Función que envía el link de la corrección del examen al alumno.
function enviarLinkACorreccion($alumno_nombre, $alumno_apellido, $alumno_email, $link, $materia_nombre, $examen_nombre)
{
    $from_mail = "no-reply-albus@federicodonner.com";
    $from_name = 'Albus evaluaciones online';
    $replyto = 'federico.donner@gmail.com';
    $asunto = "Tu evaluación de ".$materia_nombre." fue corregida.";
    $asunto_codificado = "=?utf-8?b?".base64_encode($asunto)."=?=";

    $header = "From: ".$from_name." <".$from_mail.">\r\n";
    $header .= "Reply-To: ".$replyto."\r\n";
    $header .="MIME-Version: 1.0"."\r\n";
    $header .="Content-Type: text/plain;charset=utf-8"."\r\n";

    $message = "Hola ".$alumno_nombre." ".$alumno_apellido.",\r\n\r\n";
    $message .= "Debajo encontrarás el link para ver la corrección de ".$examen_nombre." de ".$materia_nombre.".\r\n";
    $message .= "Siguiendo el link podrás ver tus respuestas, el puntaje que tuviste en cada una y notas del profesor.\r\n\r\n";
    $message .= $link."\r\n\r\n";
    $message .= "Saludos,\r\n";
    $message .= "El equipo de Albus";

    mail($alumno_email, $asunto_codificado, $message, $header);
}

// Función que envía el link de la corrección del examen al alumno.
function confirmarEnvioCorreccion($alumnos, $materia_nombre, $examen_nombre, $profesor_nombre, $profesor_email)
{
    $from_mail = "no-reply-albus@federicodonner.com";
    $from_name = 'Albus evaluaciones online';
    $replyto = 'federico.donner@gmail.com';
    $asunto = "Se envió la corrección de ".$materia_nombre." a los alumnos.";
    $asunto_codificado = "=?utf-8?b?".base64_encode($asunto)."=?=";

    $header = "From: ".$from_name." <".$from_mail.">\r\n";
    $header .= "Reply-To: ".$replyto."\r\n";
    $header .="MIME-Version: 1.0"."\r\n";
    $header .="Content-Type: text/plain;charset=utf-8"."\r\n";

    $message = "Hola ".$profesor_nombre.",\r\n\r\n";
    $message .= "Este correo es una confirmación de que se envió el link de la verificación de corrección de ".$examen_nombre." de ".$materia_nombre." a todos los alumnos de la materia.\r\n\r\n";

    foreach ($alumnos as $alumno) {
        $alumno_nombre = $alumno->nombre;
        $alumno_apellido = $alumno->apellido;
        $alumno_email = $alumno->email;
        $alumno_hash = $alumno->hash;
        $link = 'http://www.albus.federicodonner.com/verificarcorreccion/'.$alumno_hash;

        $message .= $alumno_nombre." ".$alumno_apellido."\r\n";
        $message .= $link."\r\n\r\n";
    }

    $message .= "Saludos,\r\n";
    $message .= "El equipo de Albus";

    mail($profesor_email, $asunto_codificado, $message, $header);
}
