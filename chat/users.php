<?php
$gsRutaBase="..";

require_once ("../lib/misc.lib.php");
require_once ("../lib/paloDB.class.php");
require_once ("../conf/default.conf.php");
require_once ("../lib/paloACL.class.php");
require_once ('config.php');

session_start();

header("Expires: ".gmdate("D, d M Y H:i:s")."GMT");
header("Cache-Control: no-cache, must-revalidate");
header("Pragma: no-cache");
header("Content-Type: text/html; charset=UTF-8");


//error_reporting(7);

global $config;
$db=new paloDB($config->dsn);

$login=recoger_valor("login",$_GET,$_POST);

$action=recoger_valor("action",$_GET,$_POST);
$id_materia_periodo_lectivo=recoger_valor("id_materia_periodo_lectivo",$_GET,$_POST);
$bye=recoger_valor("bye",$_GET,$_POST);


if ($bye!="bye"){
$login = trim ($login);
echo "action=";
echo $action;
echo "&id_materia_periodo_lectivo=";
echo $id_materia_periodo_lectivo;
echo "&login=";

   if ($action =="delete"){
      ///borrar de la base de datos
      $sQuery="DELETE FROM chat_users WHERE login='$login' and id_materia_periodo_lectivo=$id_materia_periodo_lectivo";
      $bValido=$db->genQuery($sQuery);
   }

   if ($action =="add"){
      //Se añade el usuario
      $sQuery="SELECT count(*) FROM chat_users WHERE login='$login' and id_materia_periodo_lectivo=$id_materia_periodo_lectivo";
      $result=$db->getFirstRowQuery($sQuery);
         if(is_array($result) && count($result)>0){
            $cant=$result[0];
               if($cant==0){
                  $sQuery="INSERT INTO chat_users (login,id_materia_periodo_lectivo,ultimo_envio) values ('$login',$id_materia_periodo_lectivo,NOW())";
                  $bValido=$db->genQuery($sQuery);
               }
         }


  }
print $login;
print "&usuarios=";

/////El maximo valor de tiempo en (segundos) de inactividad del usuario
$maximo_tiempo=10*60;


//////////En caso de que el ultimo envio sea mayor a 15 minutos se debe borrar el usuario de la tabla
$sQuery="DELETE FROM chat_users WHERE UNIX_TIMESTAMP()-UNIX_TIMESTAMP(ultimo_envio)>$maximo_tiempo";
$bValido=$db->genQuery($sQuery);


////////////Se buscan los usuarios
$sQuery="SELECT * from chat_users WHERE id_materia_periodo_lectivo=$id_materia_periodo_lectivo";
$result=$db->fetchTable($sQuery,true);
$usuario="";
   if(is_array($result) && count($result)>0){
      foreach($result as $fila){
         $usuario=$fila['login'];
         $nombre=generar_login_chat($usuario);
         $lista_usuarios.=$nombre."<br>";
      }
   }

   print $lista_usuarios;

}
else{
      ///Borrar el usuario de la lista de usuarios
      $sQuery="DELETE FROM chat_users WHERE login='$login' and id_materia_periodo_lectivo=$id_materia_periodo_lectivo";
      $bValido=$db->genQuery($sQuery);



}


?>