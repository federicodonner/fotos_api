<?php

use \Psr\Http\Message\ResponseInterface as Response;

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


function moveUploadedFile($directory, UploadedFile $uploadedFile)
{
    $extension = pathinfo($uploadedFile->getClientFilename(), PATHINFO_EXTENSION);
    $basename = bin2hex(random_bytes(8));
    $filename = sprintf('%s.%0.8s', $basename, $extension);

    $uploadedFile->moveTo($directory . DIRECTORY_SEPARATOR . $filename);

    return $filename;
}


function image_orientate($source, $filename, $destination)
{
    $info = getimagesize($source);
    if ($info['mime'] === 'image/jpeg') {
        $image = imagecreatefromjpeg($source);
        $exif = exif_read_data($source);

        if (!empty($exif['Orientation']) && in_array($exif['Orientation'], [2, 3, 4, 5, 6, 7, 8])) {
            if (in_array($exif['Orientation'], [3, 4])) {
                $image = imagerotate($image, 180, 0);
            }
            if (in_array($exif['Orientation'], [5, 6])) {
                $image = imagerotate($image, -90, 0);
            }
            if (in_array($exif['Orientation'], [7, 8])) {
                $image = imagerotate($image, 90, 0);
            }
            if (in_array($exif['Orientation'], [2, 5, 7, 4])) {
                imageflip($image, IMG_FLIP_HORIZONTAL);
            }
        }
        imagejpeg($image, './'.$destination.'/'.$filename, 70);
    }

    return true;
}
