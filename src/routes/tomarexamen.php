<?php

use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

// Devuelve los detalles del examen en base al request
$app->get('/api/tomarexamen/{hash}', function (Request $request, Response $response) {
    try {
        // Obtiene el hash del request
        $examen_hash = $request->getAttribute('hash');

        // Verifica que el hash exista
        $sql = "SELECT * FROM instancia_hash WHERE hash = '$examen_hash'";

        // Get db object
        $db = new db();
        // Connect
        $db = $db->connect();

        $stmt = $db->query($sql);
        $registros = $stmt->fetchAll(PDO::FETCH_OBJ);
        // Si hay registros en la consulta significa que encontró el array
        if (count($registros) != 0) {

            // Si hay algún examen con ese hash, verifica que no haya sido ya usado
            $registro = $registros[0];
            $fechahora_ingreso = $registro->fechahora_ingreso;

            // Si no hay fechahora_ingreso significa que el examen no fue respondido
            if (!$fechahora_ingreso) {

                // Verifica que el período aún esté abierto
                // Obtiene la información del período
                $periodo_id = $registro->periodo_id;
                $sql = "SELECT * FROM periodo WHERE id = $periodo_id";
                $stmt = $db->query($sql);
                $periodos = $stmt->fetchAll(PDO::FETCH_OBJ);
                $periodo = $periodos[0];

                $fechahora_ahora = time();
                $fechahora_fin = $periodo->fin_dttm;
                if ($fechahora_ahora < $fechahora_fin) {

                    // Si el período sigue abierto, guarda la información del período y sigue
                    // armando el objeto
                    $registro->periodo = $periodo;

                    $periodo_id = $registro->periodo_id;
                    $sql = "SELECT * FROM periodo WHERE id = $periodo_id";
                    $stmt = $db->query($sql);
                    $alumnos = $stmt->fetchAll(PDO::FETCH_OBJ);


                    // Obtiene la información del alumno del examen
                    $alumno_id = $registro->alumno_id;
                    $sql = "SELECT * FROM alumno WHERE id = $alumno_id";
                    $stmt = $db->query($sql);
                    $alumnos = $stmt->fetchAll(PDO::FETCH_OBJ);

                    $registro->alumno = $alumnos[0];


                    // Obtiene la información del examen
                    $examen_id = $periodo->examen_id;
                    $sql = "SELECT * FROM examen WHERE id = $examen_id";
                    $stmt = $db->query($sql);
                    $examenes = $stmt->fetchAll(PDO::FETCH_OBJ);
                    $examen = $examenes[0];

                    $registro->examen = $examen;

                    // Obtiene la información de la materia
                    $materia_id = $examen->materia_id;
                    $sql = "SELECT * FROM materia WHERE id = $materia_id";
                    $stmt = $db->query($sql);
                    $materias = $stmt->fetchAll(PDO::FETCH_OBJ);

                    $registro->materia = $materias[0];

                    // Obtiene la información de las secciones
                    $sql = "SELECT * FROM seccion WHERE examen_id = $examen_id";
                    $stmt = $db->query($sql);
                    $secciones = $stmt->fetchAll(PDO::FETCH_OBJ);

                    foreach ($secciones as $seccion) {
                        // Obtiene las preguntas de la seccion
                        $seccion_id = $seccion->id;

                        $sql = "SELECT ip.id as instancia_id, ip.orden, p.* FROM instancia_preguntas ip LEFT JOIN pregunta p ON ip.pregunta_id = p.id WHERE alumno_id = $alumno_id AND periodo_id = $periodo_id AND p.seccion_id = $seccion_id ORDER BY orden";
                        $stmt = $db->query($sql);
                        $preguntas = $stmt->fetchAll(PDO::FETCH_OBJ);

                        $seccion->preguntas = $preguntas;
                    }
                    $registro->secciones = $secciones;

                    $db = null;
                    return dataResponse($response, $registro);
                } else { // if($fechahora_ahora < $fechahora_fin){
                    $db = null;
                    return respondWithError($response, 'El período de la evaluación ya terminó.', 403);
                }
            } else { // if (!$fechahora_ingreso) {
                $db = null;
                return respondWithError($response, 'La evaluación ya fue utilizada.', 403);
            }
        } else { // if(count($registros) != 0){
            $db = null;
            return respondWithError($response, 'El hash no existe, si seguiste un link enviado por tu profesor, ponte en contacto para verificarlo.', 404);
        }
    } catch (PDOException $e) {
        $db = null;
        return respondWithError($response, $e->getMessage(), 500);
    }
});
