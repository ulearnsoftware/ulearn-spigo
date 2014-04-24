<?php
/* vim: set expandtab tabstop=4 softtabstop=4 shiftwidth=4: */
// +----------------------------------------------------------------------+
// | Copyright (c) 1997-2003 PaloSanto Solutions S. A.                    |
// +----------------------------------------------------------------------+
// | Cdla. Nueva Kennedy Calle E #222 y 9na. Este                         |
// | Telfs. 2283-268, 2294-440, 2284-356                                  |
// | Guayaquil - Ecuador                                                  |
// +----------------------------------------------------------------------+
// | Este archivo fuente esta sujeto a las politicas de licenciamiento    |
// | de PaloSanto Solutions S. A. y no esta disponible publicamente.      |
// | El acceso a este documento esta restringido segun lo estipulado      |
// | en los acuerdos de confidencialidad los cuales son parte de las      |
// | politicas internas de PaloSanto Solutions S. A.                      |
// | Si Ud. esta viendo este archivo y no tiene autorizacion explicita    |
// | de hacerlo comuniquese con nosotros, podria estar infringiendo       |
// | la ley sin saberlo.                                                  |
// +----------------------------------------------------------------------+
// | Autores: Iv� Ochoa    <iochoa2@telefonica.net>                         |
// +----------------------------------------------------------------------+
//
// $Id: chat.mod.php,v 1.1.1.1 2006/03/03 21:59:09 ainiguez Exp $

require_once ("conf/default.conf.php");
require_once ("lib/paloTemplate.class.php");
require_once ("lib/paloACL.class.php");

$tpl->assign("CONTENT", _moduleContent($pDB, $_GET, $_POST));

function _moduleContent(&$pDB, &$_GET, &$_POST)
{
   //echo "<pre>";print_r($_POST);echo "</pre>";echo "<pre>";print_r($_GET);echo "</pre>";
   global $config;   // definda en conf/default.conf.php
   $oACL=getACL();   // Para conocer los Permisos
   global $config; // definda en conf/default.conf.php

   $tpl =& new paloTemplate("skins/".$config->skin);
   $tpl->definirDirectorioPlantillas("");
   $tpl->assign("IMG_PATH", "skins/$config->skin/images");

   $id_materia_periodo_lectivo=recoger_valor("id_materia_periodo_lectivo",$_GET,$_POST);
   $username=$_SESSION['session_user'];
   $sContenido="";

      if($id_materia_periodo_lectivo>0){
         $estatus=$boton_abrir=$boton_cerrar=$boton_activar=$boton_desactivar="";
          ///Se busca el estatus de la materia en el chat
         $sQuery="SELECT estatus FROM chat_materias WHERE id_materia_periodo_lectivo=$id_materia_periodo_lectivo";
         $result=$pDB->getFirstRowQuery($sQuery);

            if(is_array($result) && count($result)>0){
               $estatus=$result[0];
            }
          if ($oACL->isUserAuthorized($_SESSION['session_user'], 'admin', 'chat')){
                if(isset($_POST['abrir_chat'])){
                     abrir_chat($pDB,$id_materia_periodo_lectivo);
                     $estatus='A';
                }
                if(isset($_POST['cerrar_chat'])){
                     cerrar_chat($pDB,$id_materia_periodo_lectivo);
                     $estatus="";
                }
                if(isset($_POST['activar_chat'])){
                     activar_chat($pDB,$id_materia_periodo_lectivo);
                     $estatus="A";
                }
                if(isset($_POST['desactivar_chat'])){
                     desactivar_chat($pDB,$id_materia_periodo_lectivo);
                     $estatus="I";
                }

                  switch($estatus){
                     case "":    ///Si no existe creado un chat en la tabla chat_materias
                        $boton_abrir="<input type=submit name='abrir_chat' value='Abrir Sala'>";
                        break;
                     case "A":
                        $boton_cerrar="<input type=submit name='cerrar_chat' value='Cerrar Sala' onClick=\"return confirm('Está seguro que desea cerrar esta sala?. Se borraran todos los mensajes enviados.')\">";
                        $boton_desactivar="<input type=submit name='desactivar_chat' value='Desactivar Sala'>";
                        break;
                     case "I":
                        $boton_cerrar="<input type=submit name='cerrar_chat' value='Cerrar Sala' onClick=\"return confirm('Está seguro que desea cerrar esta sala?. Se borraran todos los mensajes enviados.')\">";
                        $boton_activar="<input type=submit name='activar_chat' value='Activar Sala'>";
                        break;
                     default:
                  }
               $botonera="$boton_abrir &nbsp;&nbsp; $boton_cerrar &nbsp;&nbsp; $boton_activar &nbsp;&nbsp; $boton_desactivar";


               $tpl->assign("DATA",$botonera);
               $tpl->parse("TDs_DATA","tpl__table_data_cell");
               $tpl->parse("DATA_ROWs",".tpl__table_data_row");
                // Parsing tabla
               $tpl->assign("TITLE_ROW","<input type=hidden name=id_materia_periodo_lectivo value=$id_materia_periodo_lectivo>");
               $tpl->assign("TBL_WIDTH",300);
               $tpl->assign("HEADER_TEXT","Opciones de Administrador");
               $tpl->parse("HEADER_TDs", "tpl__table_header_cell");
               $tpl->parse("HEADER_ROW","tpl__table_header_row");
               $tpl->parse("TABLA", "tpl__table_container");
               $sContenido .= "<form name='main' method=POST>".$tpl->fetch("TABLA")."</form>";
               $sContenido .="<br><br>";
          }

          if($estatus=='A'){
            $sContenido.="<iframe name=\"chat\" src=\"chat/chatflash.php?id_materia_periodo_lectivo=$id_materia_periodo_lectivo&username=$username\" width=\"800\" height=\"600\"
                     scrolling=\"No\" frameborder=\"0\" marginwidth=\"0\" marginheight=\"0\">
                     </iframe> ";
          }
          else
            $sContenido.=$tpl->crearAlerta("!","Módulo Conversación","El instructor no se encuentra en línea, por favor intente más tarde");

      }
      else{
         $sContenido.=$tpl->crearAlerta("!","Módulo Conversación","Es necesario que seleccione una materia para acceder al modulo de conversación");
      }


   return $sContenido;
}

function abrir_chat($db,$id_materia_periodo_lectivo){
$sQuery="INSERT INTO chat_materias(id_materia_periodo_lectivo,estatus) ".
        "values ($id_materia_periodo_lectivo,'A')";
$bValido=$db->genQuery($sQuery);
return $bValido;
}

function cerrar_chat($db,$id_materia_periodo_lectivo){
$arr_query[]="DELETE FROM chat_materias WHERE id_materia_periodo_lectivo=$id_materia_periodo_lectivo";
$arr_query[]="DELETE FROM chat_messages WHERE id_materia_periodo_lectivo=$id_materia_periodo_lectivo";
$arr_query[]="DELETE FROM chat_users WHERE id_materia_periodo_lectivo=$id_materia_periodo_lectivo";
$bValido=TRUE;

   for($i=0;$i<count($arr_query);$i++)
      $bValido.=$db->genQuery($arr_query[$i]);

return $bValido;

}

function activar_chat($db,$id_materia_periodo_lectivo){
$sQuery="UPDATE chat_materias SET estatus='A' WHERE id_materia_periodo_lectivo=$id_materia_periodo_lectivo";
$bValido=$db->genQuery($sQuery);
return $bValido;
}

function desactivar_chat($db,$id_materia_periodo_lectivo){
$sQuery="UPDATE chat_materias SET estatus='I' WHERE id_materia_periodo_lectivo=$id_materia_periodo_lectivo";
$bValido=$db->genQuery($sQuery);
return $bValido;
}



?>
