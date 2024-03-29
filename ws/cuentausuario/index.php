<?php
session_start();
header('Access-Control-Allow-Origin: *');
header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept");
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');

require_once("../conexion.php");
require_once("../encrypted.php");
$conexion = new Conexion();

$frm = json_decode(file_get_contents('php://input'), true);

try {
    //  listar todos los posts o solo uno
  if ($_SERVER['REQUEST_METHOD'] == 'GET') {
      if (isset($_GET['id'])) {
        $sql = $conexion->prepare("SELECT distinct
                                    usua.usua_id as id,
                                    pena.pena_documento as documento,
                                    pena.pena_primernombre as primernombre,
                                    pena.pena_segundonombre as segundonombre,
                                    pena.pena_primerapellido as primerapellido,
                                    pena.pena_segundoapellido as segundoapellido,
                                    pena.pena_fechanacimiento as fechanacimiento,
                                    pena.pena_sexo as sexo,
                                    pena.pena_foto as foto,
                                    pena.pege_id as idpersonageneral,
                                    pena.ciud_id as idciudad,
                                    usua.usua_cuenta as cuenta,
                                    usua.usua_clave as clave
                                    FROM pinchetas_general.personanatural pena
                                    inner join pinchetas_general.personageneral pege on (pege.pege_id = pena.pege_id)
                                    inner join pinchetas_general.usuario usua on (usua.pege_id = pege.pege_id)
                                    where pena.pena_id = ?
                                    order by pena.pena_primernombre; ");
                    							
        $sql->bindValue(1, $_GET['id']);                                
        $sql->execute();
        header("HTTP/1.1 200 OK");
        $result = $sql->fetch(PDO::FETCH_ASSOC);
        if ($result == false) {
          $data = (object) array();
          $data->mensaje = "No se encontraron registros.";
          header("HTTP/1.1 400 Bad Request");
          echo json_encode( $data );
          exit();
        } else {
          echo json_encode($result);
          exit();
        }
  	  } else {
        $sql = $conexion->prepare(" SELECT distinct
                                    usua.usua_id as id,
                                    pena.pena_documento as documento,
                                    pena.pena_primernombre as primernombre,
                                    pena.pena_segundonombre as segundonombre,
                                    pena.pena_primerapellido as primerapellido,
                                    pena.pena_segundoapellido as segundoapellido,
                                    pena.pena_fechanacimiento as fechanacimiento,
                                    pena.pena_sexo as sexo,
                                    pena.pena_foto as foto,
                                    pena.pege_id as idpersonageneral,
                                    pena.ciud_id as idciudad,
                                    usua.usua_cuenta as cuenta,
                                    usua.usua_clave as clave
                                    FROM pinchetas_general.personanatural pena
                                    inner join pinchetas_general.personageneral pege on (pege.pege_id = pena.pege_id)
                                    inner join pinchetas_general.usuario usua on (usua.pege_id = pege.pege_id)
                                    where pena.pege_id <> 1 
                                    order by pena.pena_primernombre; ");
        $sql->execute();
        $sql->setFetchMode(PDO::FETCH_ASSOC);
        header("HTTP/1.1 200 OK");
        echo json_encode( $sql->fetchAll()  );
        exit();
  	  }
  }
  if ($_SERVER['REQUEST_METHOD'] == 'POST') {
      $input = $_POST;
          
      $cuenta = $frm['cuenta'];
      $clave = $frm['clave'];
      $idpersonageneral = $frm['idpersonageneral'];
      $registradopor = openCypher('decrypt', $frm['token']);
      $date = date("Y-m-d H:i:s");
      
      $sql = "INSERT INTO 
              pinchetas_general.usuario (usua_cuenta, usua_clave, pege_id, usua_registradopor, usua_fechacambio)
              VALUES (?, ?, ?, ?, ?); ";
            
      $sql = $conexion->prepare($sql);
      $sql->bindValue(1, $cuenta);
      $sql->bindValue(2, $clave);
      $sql->bindValue(3, $idpersonageneral);
      $sql->bindValue(4, $registradopor);
      $sql->bindValue(5, $date);
      $sql->execute();
      $postId = $conexion->lastInsertId();
 

    $input['id'] = $postId;
    $input['mensaje'] = "Registrado con éxito";
    header("HTTP/1.1 200 OK");
    echo json_encode($input);
    exit();
  	  
  }
  //Actualizar
  else if ($_SERVER['REQUEST_METHOD'] == 'PUT') {
      $input = $_GET;
      
      $id = $frm['id'];
      $cuenta = $frm['cuenta'];
      $clave = $frm['clave'];
      $idpersonageneral = $frm['idpersonageneral'];
      $registradopor = openCypher('decrypt', $frm['token']);
      $date = date("Y-m-d H:i:s");
      
      $sql = "UPDATE pinchetas_general.usuario 
              SET usua_cuenta = ?, usua_clave = ?, pege_id = ?, usua_registradopor = ?, usua_fechacambio = ?
              WHERE usua_id = ?; ";
            
      $sql = $conexion->prepare($sql);
      $sql->bindValue(1, $cuenta);
      $sql->bindValue(2, $clave);
      $sql->bindValue(3, $idpersonageneral);
      $sql->bindValue(4, $registradopor);
      $sql->bindValue(5, $date);
      $sql->bindValue(6, $id);
      $result = $sql->execute();
      
      if($result) {
        $input['id'] = $result;
        $input['mensaje'] = "Actualizado con éxito";
        header("HTTP/1.1 200 OK");
        echo json_encode($input);
        exit();
  	  } else {
        $input['id'] = $result;
        $input['mensaje'] = "Error actualizando";
        header("HTTP/1.1 400 Bad Request");
        echo json_encode($input);
        exit();
  	  }
  	  
  }
  // Eliminar
  else if ($_SERVER['REQUEST_METHOD'] == 'DELETE') {
      $input = $_GET;
      $id = $input['id'];
      $registradopor = openCypher('decrypt', $input['token']);

      $date = date("Y-m-d H:i:s");
      
      $sql = "CALL procedimiento_eliminar_usuario(?, ?); ";
            
      $sql = $conexion->prepare($sql);
      $sql->bindValue(1, $id);
      $sql->bindValue(2, $registradopor);
      $result = $sql->execute();
      if($result) {
        $output['mensaje'] = "Eliminado con éxito";
        header("HTTP/1.1 200 OK");
        echo json_encode($output);
        exit();
  	  } else {
        $output['mensaje'] = "Error eliminando";
        header("HTTP/1.1 400 Bad Request");
        echo json_encode($output);
        exit();
  	  }
  }

} catch (Exception $e) {
    echo 'Excepción capturada: ', $e->getMessage(), "\n";
}

//En caso de que ninguna de las opciones anteriores se haya ejecutado
// header("HTTP/1.1 400 Bad Request");

?>