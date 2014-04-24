<?php
//  ------------------------------------------------------------------------ //
// Author:   Carlos Leopoldo Magaa Zavala                                   //
// Mail:     carloslmz@msn.com                                               //
// URL:      http://www.xoopsmx.com & http://www.miguanajuato.com            //
// Module:   ChatMX                                                          //
// Project:  The XOOPS Project (http://www.xoops.org/)                       //
// Based on  Develooping flash Chat version 1.5.2                            //
// ------------------------------------------------------------------------- //

$gsRutaBase="..";
require_once("../lib/paloACL.class.php");
require_once("../lib/paloDB.class.php");
require_once("../lib/misc.lib.php");
require_once("../conf/default.conf.php");

session_start();

header("Content-Type: text/html; charset=UTF-8");
global $config;

$db=new paloDB($config->dsn);

require ('config.php');

$cont=recoger_valor("cont",$_GET,$_POST,0);
   if(!isset($cont) || $cont=="")
     $cont=0;
   if(!ereg("^[[:digit:]]+$",$cont))
      $cont=0;

$message=recoger_valor("message",$_GET,$_POST);
$login=recoger_valor("login",$_GET,$_POST);
$id_materia_periodo_lectivo=recoger_valor("id_materia_periodo_lectivo",$_GET,$_POST);

   if($show_without_time == "no"){//show time?
      $substart=0;
   }else{
      $substart=19;
   }


$login = trim ($login);

   if(isset($message) && $message!="")
      $enviar=TRUE;
   else
      $enviar=FALSE;

//$msg= urldecode($msg);
$msg = str_replace ("\n"," ", $message);
$msg = addslashes($msg);
//$msg = stripslashes ($msg);

 
/*$number_of_bad_words = count($words_to_filter);
      for($i = 0; $i <= $number_of_bad_words ;$i++){
         if (strval($words_to_filter[$i])!=""){
            $msg = eregi_replace(strval($words_to_filter[$i]),$replace_by,$msg);
         }
      }
*/
$activo=FALSE;
   //Solo si esta activa la sala se debe permitir el ingreso del mensaje
$sQuery="SELECT estatus FROM chat_materias WHERE id_materia_periodo_lectivo=$id_materia_periodo_lectivo";
$result=$db->getFirstRowQuery($sQuery,true);
    if(is_array($result) && count($result)>0){
        if($result['estatus']=='A')
          $activo=TRUE;
    }
         
         
    if ($msg != "" && $activo){
         $text_to_write = date ("(H:i:s)",time()+$correct_time)." ".generar_login_chat($login)." : ".$msg."\n";//compound single message
         ////aqui se debe guardar en la base de datos

         $sQuery="INSERT INTO chat_messages (message,id_materia_periodo_lectivo,login) values (\"$text_to_write\",$id_materia_periodo_lectivo,'$login')";
         $bValido=$db->genQuery($sQuery);
            ///Se busca si existe un usuario en la tabla usuarios, y se modifica la fecha de ultimo envio, si no se crea
         $sQuery="SELECT id FROM chat_users WHERE id_materia_periodo_lectivo=$id_materia_periodo_lectivo AND login='$login'";
         $result=$db->getFirstRowQuery($sQuery);
             if(is_array($result)){
                 if(count($result)>0){
                    $sQuery="UPDATE chat_users SET ultimo_envio=NOW() WHERE id_materia_periodo_lectivo=$id_materia_periodo_lectivo AND login='$login'";
                    $bValido=$db->genQuery($sQuery);
                  }
                  else{
                     $sQuery="INSERT INTO chat_users (id_materia_periodo_lectivo,login,ultimo_envio) values($id_materia_periodo_lectivo,'$login',NOW())";
                     $bValido=$db->genQuery($sQuery);
                  }
             }

     }

///Hace un output de los mensajes
$texto_output="";

$sQuery="SELECT distinct message,id as cont FROM chat_messages WHERE id_materia_periodo_lectivo=$id_materia_periodo_lectivo AND id>$cont order by id";
$result=$db->fetchTable($sQuery,true);
    if(is_array($result) && count($result)>0){
        foreach($result as $fila){
            $texto_output.=$fila['message'];
            $cont=$fila['cont'];
        }
    }
    

    if(!$enviar){
      //$texto_output=urlencode($texto_output);
      $texto_output=stripslashes($texto_output);
      print "&output=$texto_output&cont=$cont&order=$text_order&salir= "._MD_CHAT_LOGOUT ;
    }



?>