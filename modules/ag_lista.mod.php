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
// $Id: ag_lista.mod.php,v 1.1.1.1 2006/03/03 21:59:08 ainiguez Exp $

require_once ("conf/default.conf.php");
require_once ("lib/paloTemplate.class.php");
require_once ("lib/paloACL.class.php");
require_once ("modules/ul_evento.class.php");
require_once ("modules/ul_evento_reporte.class.php");

$tpl->assign("CONTENT", _moduleContent($pDB, $_GET, $_POST));

function _moduleContent(&$pDB, &$_GET, &$_POST)
{
	global $config; // definda en conf/default.conf.php

   $oPlantillas =& new paloTemplate("skins/".$config->skin);
   $oPlantillas->definirDirectorioPlantillas("");
   $oPlantillas->assign("IMG_PATH", "skins/$config->skin/images");

   $id_materia_periodo_lectivo = recoger_valor("id_materia_periodo_lectivo",$_GET,$_POST);

   $sAccion = recoger_valor("action",$_GET,$_POST,"");

   if($id_materia_periodo_lectivo!=NULL)
      if(!ereg("^[[:digit:]]+$",$id_materia_periodo_lectivo))
         return $oPlantillas->crearAlerta("error","Error","No se ha recibido un valor válido de id materia");

   ///////Se setea la accion si el permiso es correspondiente
   $oACL=getACL();
   $id_evento="";

   if(isset($_POST['id_evento']))
      $id_evento=$_POST['id_evento'];

   if(isset($_POST['eliminar_evento']) && $oACL->isUserAuthorized($_SESSION['session_user'], 'admin', 'ag_lista'))
      $sAccion='eliminar_evento';
   if(isset($_POST['crear_evento']) && $oACL->isUserAuthorized($_SESSION['session_user'], 'admin', 'ag_lista'))
      $sAccion='crear_evento';
   if(isset($_POST['modificar_evento']) && $oACL->isUserAuthorized($_SESSION['session_user'], 'admin', 'ag_lista'))
      $sAccion="modificar_evento";

   ////Si el id_materia_periodo_lectivo no es NULL se crea un objeto Reporte_evento
   if($id_materia_periodo_lectivo!=NULL)
      $oReporte_evento = &  new ul_evento_reporte($pDB, $oPlantillas,"?menu1op=submenu_agenda&submenuop=ag_lista&id_materia_periodo_lectivo=$id_materia_periodo_lectivo",$id_materia_periodo_lectivo,$id_evento);

   $id_evento=recoger_valor("id_evento",$_GET,$_POST);
   if($id_evento!=NULL){
      if($id_materia_periodo_lectivo==NULL)
         $sAccion="listar";
      else{
         $sQuery = "SELECT count(*) FROM ul_evento WHERE id_evento=$id_evento AND id_materia_periodo_lectivo=$id_materia_periodo_lectivo AND (contenido IS NOT NULL) AND final>=NOW()";
         $result=$pDB->getFirstRowQuery($sQuery);
         if(is_array($result) && count($result)>0)
            if($result[0]==0)
               $sAccion="listar";
      }
   }

   // Ejecutar accion segun la opcion elegida
   $sCodigoTabla = "";

   switch ($sAccion) {
   // necesitan seleccionar archivo o directorio
   case "eliminar_evento":
      if(isset($oReporte_evento)){
         if(isset($_POST['id_evento'])){
            $bValido=$oReporte_evento->eliminar_evento($_POST['id_evento']);
            if(!$bValido){
               $sCodigoTabla.=$oPlantillas->crearAlerta("error", "Al efectuar operación de Eliminación", $oReporte_evento->getMessage());
            }
         }
         else
            $sCodigoTabla.=$oPlantillas->crearAlerta("error", "Error al eliminar", "No se ha seleccionado un id_evento");

         $sCodigoTabla .=  $oReporte_evento->generarReporte("LISTA_EVENTOS",$_GET,$_POST);
      }
      break;
   // no necesitan seleccionar archivo o directorio
   case "crear_evento":
         if(isset($oReporte_evento)){
            return mostrar_formulario_crear_evento($pDB,$oPlantillas,$_GET,$_POST,$id_materia_periodo_lectivo);
         }
      break;
   case "modificar_evento":
         if(isset($oReporte_evento)){
            if(isset($_POST['in_agenda'])){
               return mostrar_formulario_modificar_evento($pDB,$oPlantillas,$_GET,$_POST,$id_materia_periodo_lectivo,$_POST['in_agenda']);
            }
            else{
               //
            }
         }
      break;
   case "mostrar_evento":

         if(isset($_GET['id_evento'])){
            $oContenido=new ul_evento(
               $pDB,
               $oPlantillas,
               "?menu1op=submenu_agenda&submenuop=ag_lista",
               $id_materia_periodo_lectivo);
            return $oContenido->visualizar_evento($_GET);
         }
      break;
   case "listar":
   default:
      if(isset($oReporte_evento)){
         $sCodigoTabla .=  $oReporte_evento->generarReporte("LISTA_EVENTOS",$_GET,$_POST);
      }
   }
   return $sCodigoTabla;
}

function mostrar_formulario_crear_evento(&$oDB,&$tpl,&$_GET,&$_POST,$id_materia_periodo_lectivo){
   $sContenido="";
   $oContenido=new ul_evento($oDB, $tpl, "?menu1op=submenu_agenda&submenuop=ag_lista", $id_materia_periodo_lectivo);

   // Verificar si se desean guardar cambios
   $tuplaForm = $oContenido->deducirFormulario($_POST);
   if (is_array($tuplaForm)) {
      if ($tuplaForm[0] == "INSERT") {
         $bExito = $oContenido->manejarFormularioInsert($tuplaForm[1], $_POST);
         if($bExito)
            header("Location: ?menu1op=submenu_agenda&submenuop=ag_lista&id_materia_periodo_lectivo=$id_materia_periodo_lectivo");
         else{
            $sContenido .= $tpl->crearAlerta(
               "error",
               "Problema al Crear Contenido",
               "Al crear evento: ".$oContenido->getMessage());
         }
      }
   }
   $sContenido .= $oContenido->generarFormularioInsert("CREAR_EVENTO", $_POST);
   return $sContenido;
}




function mostrar_formulario_modificar_evento(&$oDB,&$tpl,&$_GET,&$_POST,$id_materia_periodo_lectivo,$id_evento){
   $sContenido="";
   $oContenido=new ul_evento($oDB, $tpl, "?menu1op=submenu_agenda&submenuop=ag_lista", $id_materia_periodo_lectivo, $id_evento);

   // Verificar si se desean guardar cambios
   $tuplaForm = $oContenido->deducirFormulario($_POST);
   if (is_array($tuplaForm)) {
      if ($tuplaForm[0] == "UPDATE") {
         $bExito = $oContenido->manejarFormularioUpdate($tuplaForm[1], $_POST);
         if($bExito)
            header("Location: ?menu1op=submenu_agenda&submenuop=ag_lista&id_materia_periodo_lectivo=$id_materia_periodo_lectivo");
         else{
            $sContenido .= $tpl->crearAlerta(
               "error",
               "Problema al Modificar Contenido",
               "Al Modificar Contenido: ".$oContenido->getMessage());
         }
      }
   }
   $sContenido .= $oContenido->generarFormularioUpdate("MODIFICAR_EVENTO", $_POST,array('id_evento'=>$id_evento));
   return $sContenido;
}



?>
