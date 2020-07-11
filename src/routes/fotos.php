<?php

use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

// Todas los fotos
$app->get('/api/fotos/{identificador}', function (Request $request, Response $response) {
    $identificador = $request->getAttribute('identificador');
    /* connect to mail */
    $hostname = '{mail.federicodonner.com:993/imap/ssl/novalidate-cert}INBOX';
    $username = $identificador.'@federicodonner.com';
    $password = '010203040506070809';



    /* try to connect */
    $inbox = imap_open($hostname, $username, $password);

    if ($inbox) {

      // Borra todos los archivos de la carpeta
      $files = glob('./'.$identificador."/*"); // get all file names
      foreach ($files as $file) { // iterate files
        if (is_file($file)) {
            unlink($file);
        } // delete file
      }

        // Borra todos los archivos de la carpeta temporal
        $files_temp = glob('./tmp/*');
        foreach ($files_temp as $file) { // iterate files
            if (is_file($file)) {
                unlink($file);
            } // delete file
        }

        /* grab emails */
        $emails = imap_search($inbox, 'ALL');

        /* if emails are returned, cycle through each... */
        if ($emails) {

        /* begin output var */
            $output = '';

            /* put the newest emails on top */
            rsort($emails);
            $cuenta = 0;
            $nombresArchivos = array();
            /* for every email... */
            foreach ($emails as $email_number) {

            /* get information specific to this email */
                $overview = imap_fetch_overview($inbox, $email_number, 0);

                $message = imap_fetchbody($inbox, $email_number, 2);

                /* get mail structure */
                $structure = imap_fetchstructure($inbox, $email_number);

                $attachments = array();

                /* if any attachments found... */
                if (isset($structure->parts) && count($structure->parts)) {
                    for ($i = 0; $i < count($structure->parts); $i++) {
                        $attachments[$i] = array(
                     'is_attachment' => false,
                     'filename' => '',
                     'name' => '',
                     'attachment' => ''
                 );

                        if ($structure->parts[$i]->ifdparameters) {
                            foreach ($structure->parts[$i]->dparameters as $object) {
                                if (strtolower($object->attribute) == 'filename') {
                                    $attachments[$i]['is_attachment'] = true;
                                    $attachments[$i]['filename'] = $object->value;
                                }
                            }
                        }

                        if ($structure->parts[$i]->ifparameters) {
                            foreach ($structure->parts[$i]->parameters as $object) {
                                if (strtolower($object->attribute) == 'name') {
                                    $attachments[$i]['is_attachment'] = true;
                                    // $attachments[$i]['name'] = $object->value;
                                    $attachments[$i]['name'] = $object->value;
                                }
                            }
                        }

                        if ($attachments[$i]['is_attachment']) {
                            $attachments[$i]['attachment'] = imap_fetchbody($inbox, $email_number, $i+1);

                            /* 3 = BASE64 encoding */
                            if ($structure->parts[$i]->encoding == 3) {
                                $attachments[$i]['attachment'] = base64_decode($attachments[$i]['attachment']);
                            }
                            /* 4 = QUOTED-PRINTABLE encoding */
                            elseif ($structure->parts[$i]->encoding == 4) {
                                $attachments[$i]['attachment'] = quoted_printable_decode($attachments[$i]['attachment']);
                            }
                        }
                    }
                }



                /* iterate through each attachment and save it */
                foreach ($attachments as $attachment) {
                    $borrarEmail = false;
                    if ($cuenta < 10) {
                        if ($attachment['is_attachment'] == 1) {
                            $filename = $attachment['name'];
                            if (empty($filename)) {
                                $filename = $attachment['filename'];
                            }

                            if (empty($filename)) {
                                $filename = time() . ".dat";
                            }
                            $folder = $identificador;
                            if (!is_dir($folder)) {
                                mkdir($folder);
                            }

                            // Verifica que exista la carpeta tmp, si no existe la crea
                            if (!is_dir('tmp')) {
                                mkdir('tmp');
                            }

                            // Verifica que el archivo exista por si hay dos adjuntos con el mismo nombre
                            // Primero guarda los archivos en el tmp
                            $file = "./tmp/" . $filename;
                            if (!file_exists($file)) {
                                $fp = fopen($file, "w+");
                                fwrite($fp, $attachment['attachment']);
                                fclose($fp);

                                // Una vez que está guardado, verifica que la orientación sea la correcta y lo guarda
                                // en la carpeta correspondiente a la cuenta
                                image_orientate($file, $filename, $folder);


                                // Si guarda el archivo, guarda el nombre para responder el request
                                array_push($nombresArchivos, "http://federicodonner.com/fotos_api/public/".$folder.'/'.$filename);



                                $cuenta=$cuenta+1;
                            }
                        }
                    } else {
                        // Si es un mail mayor al décimo, lo marca para borrar
                        $borrarEmail = true;
                    }
                }
                if ($borrarEmail) {
                    // Borra el mail marcado
                    $structure = imap_delete($inbox, $email_number);
                }
            }
        }
    } else {
        return respondWithError($response, 'Hubo un error recuperando los archivos. Avisale a Fefi.', 400);
    }

    // Expunge borra los mails marcados para borrar
    imap_expunge($inbox);

    /* close the connection */
    imap_close($inbox);
    $objetoRespuesta->fotos = $nombresArchivos;
    return dataResponse($response, $objetoRespuesta);
});
