<?php

use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

// Obtener un período específico
$app->get('/api/verificarcorreccion/{hash}', function (Request $request, Response $response) {
    try {
        $periodo_hash = $request->getAttribute('hash');

        // Verifica que el hash exista
        $sql="SELECT * FROM instancia_hash WHERE hash = '$periodo_hash'";

        // Get db object
        $db = new db();
        // Connect
        $db = $db->connect();

        $stmt = $db->query($sql);
        $instancias = $stmt->fetchAll(PDO::FETCH_OBJ);

        // Si no encontró el hash especificado devuelve un error
        if (count($instancias)==0) {
            return respondWithError($response, "El identificador especificado no existe. Por favor verifica que hayas seguido el vínculo correcto. Si continúas viendo este error por favor ponte en contacto con tu profesor. Gracias.", 403);
        }




        // Busca las respuestas del alumno en base al hash
        // combina con el textdo de las preguntas
        $sql = "SELECT ip.id, ip.nota, ip.comentarios_profesor, ip.orden, ip.texto_respuesta AS respuesta_alumno, ip.timestamp_respuesta, p.texto_pregunta, p.imagen_pregunta, p.puntaje, p.texto_respuesta, ip.periodo_id, ip.alumno_id FROM instancia_preguntas ip";
        $sql.=" LEFT JOIN pregunta p ON ip.pregunta_id = p.id LEFT JOIN instancia_hash ih ON ip.alumno_id = ih.alumno_id AND ip.periodo_id = ih.periodo_id WHERE hash = '$periodo_hash'";

        $stmt = $db->query($sql);
        $respuestas = $stmt->fetchAll(PDO::FETCH_OBJ);

        // Obtiene el id del período para ir a buscar la información del examen
        $periodo_id = $respuestas[0]->periodo_id;
        $alumno_id = $respuestas[0]->alumno_id;


        // En cada pregunta suma el puntaje para saber el puntaje total del examen
        $puntaje_examen_total = 0;
        $puntaje_obtenido_total = 0;
        foreach ($respuestas as $respuesta) {
            // Suma los puntajes
            $puntaje_examen_total = $puntaje_examen_total + $respuesta->puntaje;
            $puntaje_obtenido_total = $puntaje_obtenido_total + $respuesta->nota;
        }

        $sql="SELECT p.inicio_dttm, m.nombre AS materia_nombre, e.nombre AS examen_nombre, p.estado FROM periodo p LEFT JOIN examen e ON p.examen_id = e.id LEFT JOIN materia m ON e.materia_id = m.id WHERE p.id = $periodo_id";
        $stmt = $db->query($sql);
        $periodos = $stmt->fetchAll(PDO::FETCH_OBJ);

        $periodo_estado = $periodos[0]->estado;
        switch ($periodo_estado) {
          case 0:
            $db = null;
            return respondWithError($response, "La evaluación aún no ha sido corregida por el profesor. Vuelve a ingresar cuando recibas la notificación de corrección. Gracias.", 403);
          break;
          case 1:

          $sql="SELECT * FROM alumno WHERE id = $alumno_id";
          $stmt = $db->query($sql);
          $alumnos = $stmt->fetchAll(PDO::FETCH_OBJ);



            $respuestas_objeto->periodo = $periodos[0];
            $respuestas_objeto->alumno = $alumnos[0];

            $respuestas_objeto->puntaje_examen_total = $puntaje_examen_total;
            $respuestas_objeto->puntaje_obtenido_total = $puntaje_obtenido_total;
            $respuestas_objeto->respuestas = $respuestas;

            $db = null;
            return dataResponse($response, $respuestas_objeto);
        break;
      }
    } catch (PDOException $e) {
        $db = null;
        return respondWithError($response, $e->getMessage(), 500);
    }
});
