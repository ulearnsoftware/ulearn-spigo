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
// $Id: col_foros.mod.php,v 1.1.1.1 2006/03/03 21:59:09 ainiguez Exp $

require_once ("conf/default.conf.php");
require_once ("lib/paloTemplate.class.php");
require_once ("lib/paloACL.class.php");
require_once ("modules/ul_foro.class.php");
require_once ("modules/ul_topico.class.php");
require_once ("modules/ul_mensaje.class.php");
require_once ("modules/ul_foro_reporte.class.php");


$tpl->assign("CONTENT", _moduleContent($pDB, $_GET, $_POST));

function _moduleContent(&$pDB, &$_GET, &$_POST)
{
	global $config; // definda en conf/default.conf.php

   $oPlantillas =& new paloTemplate("skins/".$config->skin);
   $oPlantillas->definirDirectorioPlantillas("");
   $oPlantillas->assign("IMG_PATH", "skins/$config->skin/images");

   $id_materia_periodo_lectivo=recoger_valor("id_materia_periodo_lectivo",$_GET,$_POST);
   $id_foro=recoger_valor("id_foro",$_GET,$_POST,"");
   $id_topico=recoger_valor("id_topico",$_GET,$_POST,"");

   $sAccion=recoger_valor("action",$_GET,$_POST,"");

   if($id_materia_periodo_lectivo!=NULL)
      if(!ereg("^[[:digit:]]+$",$id_materia_periodo_lectivo))
         return $oPlantillas->crearAlerta("error","Error","No se ha recibido un valor válido de id materia");

   ///////Se setea la accion si el permiso es correspondiente
   $oACL=getACL();

   ////Si el id_materia_periodo_lectivo no es NULL se crea un objeto foro_reporte
   if($id_materia_periodo_lectivo!=NULL)
      $oReporte_foro = &  new ul_foro_reporte($pDB, $oPlantillas,"?menu1op=submenu_colaboracion&submenuop=col_foros&id_materia_periodo_lectivo=$id_materia_periodo_lectivo",$id_materia_periodo_lectivo,$id_foro,$id_topico);

   if(isset($_POST['eliminar_foro']) && $oACL->isUserAuthorized($_SESSION['session_user'], 'delet', 'col_foros'))
      $sAccion='eliminar_foro';
   if(isset($_POST['crear_foro']) && $oACL->isUserAuthorized($_SESSION['session_user'], 'creat', 'col_foros'))
      $sAccion='crear_foro';
   if(isset($_POST['modificar_foro']) && $oACL->isUserAuthorized($_SESSION['session_user'], 'updat', 'col_foros'))
      $sAccion="modificar_foro";

   if(isset($_POST['eliminar_topico']) && $oACL->isUserAuthorized($_SESSION['session_user'], 'delet', 'col_foros'))
      $sAccion='eliminar_topico';
   if(isset($_POST['crear_topico']) && $oACL->isUserAuthorized($_SESSION['session_user'], 'creat', 'col_foros'))
      $sAccion='crear_topico';
   if(isset($_POST['modificar_topico']) && $oACL->isUserAuthorized($_SESSION['session_user'], 'updat', 'col_foros'))
      $sAccion="modificar_topico";

   if(isset($_POST['eliminar_mensaje']) && $oACL->isUserAuthorized($_SESSION['session_user'], 'delet', 'col_foros'))
      $sAccion='eliminar_mensaje';
   if(isset($_POST['crear_mensaje']))
      $sAccion='crear_mensaje';
   if(isset($_POST['modificar_mensaje']) && $oACL->isUserAuthorized($_SESSION['session_user'], 'updat', 'col_foros'))
      $sAccion="modificar_mensaje";


   if($id_foro!=""){
      $sQuery = "SELECT count(*) FROM ul_foro WHERE id_foro=$id_foro AND id_materia_periodo_lectivo=$id_materia_periodo_lectivo";
      $result=$pDB->getFirstRowQuery($sQuery);
      if(is_array($result) && count($result)>0)
         if($result[0]==0)
            $sAccion="listar";
   }
   if($id_topico!=""){
      $sQuery = "SELECT count(*) FROM ul_topico WHERE id_topico=$id_topico AND id_foro=$id_foro";
      $result=$pDB->getFirstRowQuery($sQuery);
      if(is_array($result) && count($result)>0)
         if($result[0]==0)
            $sAccion="listar";
   }

   // Ejecutar accion segun la opcion elegida
   $sCodigoTabla = "";
   switch ($sAccion) {
   // necesitan seleccionar archivo o directorio
   case "eliminar_foro":
         if(isset($oReporte_foro)){
               if (isset($_POST["id_foro"])) {
                  $id_foro = $_POST["id_foro"];
                  $bExito=$oReporte_foro->eliminar_foro($id_foro);
                     if (!$bExito)
                        $sCodigoTabla .= $oPlantillas->crearAlerta("error", "Al efectuar operaci&oacute;n: ", $oReporte_foro->getMessage());

               }
               else
                  $sCodigoTabla .= $oPlantillas->crearAlerta("error", "Al efectuar operaci&oacute;n: ","No se ha seleccionado un foro.");

            $sCodigoTabla .=  $oReporte_foro->generarReporte("LISTA_FOROS",$_GET,$_POST);
         }

        return $sCodigoTabla;
      break;

   case "eliminar_topico":
         if(isset($oReporte_foro)){
               if (isset($_POST["id_topico"])) {
                  $id_topico = $_POST["id_topico"];
                  $bExito=$oReporte_foro->eliminar_topico($id_topico);
                     if (!$bExito)
                        $sCodigoTabla .= $oPlantillas->crearAlerta("error", "Al efectuar operaci&oacute;n: ", $oReporte_foro->getMessage());

               }
               else
                  $sCodigoTabla .= $oPlantillas->crearAlerta("error", "Al efectuar operaci&oacute;n: ","No se ha seleccionado un topico.");

            $sCodigoTabla .=  $oReporte_foro->generarReporte("LISTA_TOPICOS",$_GET,$_POST);
         }

        return $sCodigoTabla;
      break;

   case "eliminar_mensaje":
         if(isset($oReporte_foro)){
               if (isset($_POST["id_mensaje"])) {
                  $id_mensaje = $_POST["id_mensaje"];
                  $bExito=$oReporte_foro->eliminar_mensaje($id_mensaje);
                     if (!$bExito)
                        $sCodigoTabla .= $oPlantillas->crearAlerta("error", "Al efectuar operaci&oacute;n: ", $oReporte_foro->getMessage());

               }
               else
                  $sCodigoTabla .= $oPlantillas->crearAlerta("error", "Al efectuar operaci&oacute;n: ","No se ha seleccionado un mensaje.");

            $sCodigoTabla .=  $oReporte_foro->generarReporte("LISTA_MENSAJES",$_GET,$_POST);
         }

        return $sCodigoTabla;
      break;

   case "crear_foro":
         return mostrar_formulario_crear_foro($pDB,$oPlantillas,$_GET,$_POST,$id_materia_periodo_lectivo);

   case "crear_topico":
         return mostrar_formulario_crear_topico($pDB,$oPlantillas,$_GET,$_POST,$id_materia_periodo_lectivo);

   case "crear_mensaje":
         return mostrar_formulario_crear_mensaje($pDB,$oPlantillas,$_GET,$_POST,$id_materia_periodo_lectivo);
      break;

   case "modificar_foro":
      if(isset($oReporte_foro))
            if(isset($_POST['id_foro']))
               return mostrar_formulario_modificar_foro($pDB,$oPlantillas,$_GET,$_POST,$id_materia_periodo_lectivo,$id_foro);
            else{
               $sCodigoTabla .= $oPlantillas->crearAlerta("error", "Al efectuar operaci&oacute;n: ","No se ha seleccionado un Foro.");
               $sCodigoTabla.=$oReporte_foro->generarReporte("LISTA_FOROS",$_GET,$_POST);
               return $sCodigoTabla;
            }
      break;
   case "modificar_topico":
      if(isset($oReporte_foro))
            if(isset($_POST['id_topico']))
               return mostrar_formulario_modificar_topico($pDB,$oPlantillas,$_GET,$_POST,$id_materia_periodo_lectivo,$id_foro,$id_topico);
            else{
               $sCodigoTabla .= $oPlantillas->crearAlerta("error", "Al efectuar operaci&oacute;n: ","No se ha seleccionado un tópico.");
               $sCodigoTabla.=$oReporte_foro->generarReporte("LISTA_TOPICOS",$_GET,$_POST);
               return $sCodigoTabla;
            }

      break;
   case "mostrar_topicos":
         if(isset($oReporte_foro)){
            return $oReporte_foro->generarReporte("LISTA_TOPICOS",$_GET,$_POST);
         }
      break;

   case "mostrar_mensajes":
      if(isset($oReporte_foro)){
         $sCodigoTabla .=  $oReporte_foro->generarReporte("LISTA_MENSAJES",$_GET,$_POST);
         $sCodigoTabla = str_replace("&lt;br /&gt;","<br />",$sCodigoTabla);
         //echo nl2br(htmlentities($sCodigoTabla));
      }
      break;

   case "listar":
   default:
      if(isset($oReporte_foro)){
         $sCodigoTabla .=  $oReporte_foro->generarReporte("LISTA_FOROS",$_GET,$_POST);
      }
   }
   return $sCodigoTabla;
}



///////////////////////////////////////////////////////*****************////////////////////////////////////////////////////
///////Definición de Funciones para Mostrar Pantallas y/o realizar funciones
//////////////////////////////////////////////////////******************////////////////////////////////////////////////////

function mostrar_formulario_crear_foro(&$oDB,&$tpl,&$_GET,&$_POST,$id_materia_periodo_lectivo){
   $sContenido="";
   $oForo=new ul_foro($oDB,$tpl,$id_materia_periodo_lectivo);

   // Verificar si se desean guardar cambios
   $tuplaForm = $oForo->deducirFormulario($_POST);
   if (is_array($tuplaForm)) {
      if ($tuplaForm[0] == "INSERT") {
         $bExito = $oForo->manejarFormularioInsert($tuplaForm[1], $_POST);
         if($bExito)
            header("Location: ?menu1op=submenu_colaboracion&submenuop=col_foros&id_materia_periodo_lectivo=$id_materia_periodo_lectivo");
         else{
            $sContenido .= $tpl->crearAlerta(
               "error",
               "Problema al Crear Foro",
               "Al crear foro: ".$oForo->getMessage());
         }
      }
   }
   $sContenido .= $oForo->generarFormularioInsert("CREAR_FORO", $_POST);
   return $sContenido;
}


function mostrar_formulario_modificar_foro(&$oDB,&$tpl,&$_GET,&$_POST,$id_materia_periodo_lectivo,$id_foro){
   $sContenido="";
   $oForo=new ul_foro($oDB,$tpl,$id_materia_periodo_lectivo, $id_foro);
   // Verificar si se desean guardar cambios
   $tuplaForm = $oForo->deducirFormulario($_POST);
   if (is_array($tuplaForm)) {
      if ($tuplaForm[0] == "UPDATE") {
         $bExito = $oForo->manejarFormularioUpdate($tuplaForm[1], $_POST);
         if($bExito)
            header("Location: ?menu1op=submenu_colaboracion&submenuop=col_foros&id_materia_periodo_lectivo=$id_materia_periodo_lectivo");
         else{
            $sContenido .= $tpl->crearAlerta(
               "error",
               "Problema al Modificar Foro",
               "Al Modificar Foro: ".$oForo->getMessage());
         }
      }
   }
   $sContenido .= $oForo->generarFormularioUpdate("MODIFICAR_FORO", $_POST,array('id_foro'=>$id_foro));
   return $sContenido;
}

function mostrar_formulario_crear_topico(&$oDB,&$tpl,&$_GET,&$_POST,$id_materia_periodo_lectivo){
   $sContenido="";
   $id_foro=recoger_valor("id_foro",$_GET,$_POST);

   $oTopico=new ul_topico($oDB,$tpl,$id_materia_periodo_lectivo,$id_foro);

   // Verificar si se desean guardar cambios
   $tuplaForm = $oTopico->deducirFormulario($_POST);

   if (is_array($tuplaForm)) {
      if ($tuplaForm[0] == "INSERT") {
         $bExito = $oTopico->manejarFormularioInsert($tuplaForm[1], $_POST);
         if($bExito)
            header("Location: ?menu1op=submenu_colaboracion&submenuop=col_foros&id_materia_periodo_lectivo=$id_materia_periodo_lectivo&id_foro=$id_foro&action=mostrar_topicos");
         else{
            $sContenido .= $tpl->crearAlerta(
               "error",
               "Problema al Crear Tópico",
               "Al crear Tópico: ".$oTopico->getMessage());
         }
      }
   }
   $sContenido .= $oTopico->generarFormularioInsert("CREAR_TOPICO", $_POST);
   return $sContenido;
}

function mostrar_formulario_modificar_topico(&$oDB,&$tpl,&$_GET,&$_POST,$id_materia_periodo_lectivo,$id_foro,$id_topico){
   $sContenido="";
   $oTopico=new ul_topico($oDB,$tpl,$id_materia_periodo_lectivo, $id_foro,$id_topico);
   // Verificar si se desean guardar cambios
   $tuplaForm = $oTopico->deducirFormulario($_POST);
   if (is_array($tuplaForm)) {
      if ($tuplaForm[0] == "UPDATE") {
         $bExito = $oTopico->manejarFormularioUpdate($tuplaForm[1], $_POST);
         if($bExito)
            header("Location: ?menu1op=submenu_colaboracion&submenuop=col_foros&id_materia_periodo_lectivo=$id_materia_periodo_lectivo&action=mostrar_topicos&id_foro=$id_foro");
         else{
            $sContenido .= $tpl->crearAlerta(
               "error",
               "Problema al Modificar Tópico",
               "Al Modificar Tópico: ".$oTopico->getMessage());
         }
      }
   }
   $sContenido .= $oTopico->generarFormularioUpdate("MODIFICAR_TOPICO", $_POST,array('id_topico'=>$id_topico));
   return $sContenido;
}



function mostrar_formulario_crear_mensaje(&$oDB,&$tpl,&$_GET,&$_POST,$id_materia_periodo_lectivo){
   $sContenido="";
   $id_foro=recoger_valor("id_foro",$_GET,$_POST);
   $id_topico=recoger_valor("id_topico",$_GET,$_POST);

   $oMensaje=new ul_mensaje($oDB,$tpl,$id_materia_periodo_lectivo,$id_foro,$id_topico);

   /////Se debe verificar si se apreto el boton Adjuntar
   if(isset($_POST['Adjuntar']))
      $adjuntar=TRUE;
   else
      $adjuntar=FALSE;


   // Verificar si se desean guardar cambios
   $tuplaForm = $oMensaje->deducirFormulario($_POST);

   if (is_array($tuplaForm)) {
      if ($tuplaForm[0] == "INSERT") {
         $bExito = $oMensaje->manejarFormularioInsert($tuplaForm[1], $_POST);
         if($bExito)
            header("Location: ?menu1op=submenu_colaboracion&submenuop=col_foros&id_materia_periodo_lectivo=$id_materia_periodo_lectivo&id_foro=$id_foro&id_topico=$id_topico&action=mostrar_mensajes");
         else{
            $sContenido .= $tpl->crearAlerta(
               "error",
               "Problema al Crear Mensaje",
               "Al crear Mensaje: ".$oMensaje->getMessage());
         }
      }
   }
   $sContenido .= $oMensaje->generarFormularioInsert("CREAR_MENSAJE", $_POST);
   return $sContenido;
}

function obtener_nombre_usuario($oDB,$login){
$oACL=getACL();
$id_user=$oACL->getIdUser($login);  //Se obtiene el id_user


//////En base al id_user se debe buscar el nombre, buscando en sa_alumno y sa_docente
//////si no se encuentra se retorna el login

//Primero se busca en la tabla alumno
$sQuery="SELECT concat(nombre,' ',apellido) FROM ul_alumno WHERE id_acl_user=$id_user";
$result=$oDB->getFirstRowQuery($sQuery);
   if(is_array($result) && count($result)>0)
      return $result[0];

///Si no se encontro en alumno se busca en docente
$sQuery="SELECT concat(nombre,' ',apellido) FROM ul_docente WHERE id_acl_user=$id_user";
$result=$oDB->getFirstRowQuery($sQuery);
   if(is_array($result) && count($result)>0)
      return $result[0];

///Si no se encontro en docente se devuelve el login

return $login;

}


?>
