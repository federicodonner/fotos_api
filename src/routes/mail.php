<?php
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

// Send email
$app->post('/api/email', function(Request $request, Response $response){

  $params = $request->getBody();
  if($request->getHeaders()['HTTP_AUTHORIZATION']){
    $access_token = $request->getHeaders()['HTTP_AUTHORIZATION'][0];
    $access_token = explode(" ", $access_token)[1];
    // Find the access token, if a user is returned, post the products
    if(!empty($access_token)){
      $user_found = verifyToken($access_token);
      if(!empty($user_found)){

        $price_s = $request->getParam('price_s');
        $price_l = $request->getParam('price_l');
        $monday = $request->getParam('monday');
        $menuMonday = $request->getParam('menuMonday');
        $menuTuesday = $request->getParam('menuTuesday');
        $menuWednesday = $request->getParam('menuWednesday');
        $menuThursday = $request->getParam('menuThursday');
        $menuFriday = $request->getParam('menuFriday');
        $menuSaturday = $request->getParam('menuSaturday');
        $menuSunday =  $request->getParam('menuSunday');

        $sql = "INSERT INTO almuerzos (price_s,price_l,monday,menuMonday,menuTuesday,menuWednesday,menuThursday,menuFriday,menuSaturday,menuSunday) VALUES (:price_s,:price_l,:monday,:menuMonday,:menuTuesday,:menuWednesday,:menuThursday,:menuFriday,:menuSaturday,:menuSunday)";
        try{
          // Get db object
          $db = new db();
          // Connect
          $db = $db->connect();

          $stmt = $db->prepare($sql);

          $stmt->bindParam(':price_s', $price_s);
          $stmt->bindParam(':price_l', $price_l);
          $stmt->bindParam(':monday', $monday);
          $stmt->bindParam(':menuMonday', $menuMonday);
          $stmt->bindParam(':menuTuesday', $menuTuesday);
          $stmt->bindParam(':menuWednesday', $menuWednesday);
          $stmt->bindParam(':menuThursday', $menuThursday);
          $stmt->bindParam(':menuFriday', $menuFriday);
          $stmt->bindParam(':menuSaturday', $menuSaturday);
          $stmt->bindParam(':menuSunday', $menuSunday);

          $stmt->execute();

          $newResponse = $response->withStatus(200);
          $body = $response->getBody();
          $body->write('{"status": "success","message": "Almuerzo agregado", "almuerzo": "'.$menu.'"}');
          $newResponse = $newResponse->withBody($body);
          return $newResponse;


        }catch(PDOException $e){
          echo '{"error":{"text": '.$e->getMessage().'}}';

        }
      }else{
        return loginError($response, 'Error de login, usuario no encontrado');
      }
    }else{
      return loginError($response, 'Error de login, falta access token');
    }
  }else{
    return loginError($response, 'Error de encabezado HTTP');
  }
});
