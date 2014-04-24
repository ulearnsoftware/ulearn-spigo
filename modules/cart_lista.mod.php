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
// $Id: cart_lista.mod.php,v 1.1.1.1 2006/03/03 21:59:09 ainiguez Exp $

require_once ("conf/default.conf.php");
require_once ("lib/paloTemplate.class.php");
require_once ("lib/paloACL.class.php");
require_once ("modules/ul_cartelera.class.php");
require_once ("modules/ul_cartelera_reporte.class.php");
require_once ("modules/ul_evento.class.php");

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
   $id_cartelera="";


   if(isset($_POST['id_cartelera']))
      $id_cartelera=$_POST['id_cartelera'];

   if(isset($_POST['eliminar_cartelera']) && $oACL->isUserAuthorized($_SESSION['session_user'], 'admin', 'cart_lista'))
      $sAccion='eliminar_cartelera';
   if(isset($_POST['crear_cartelera']) && $oACL->isUserAuthorized($_SESSION['session_user'], 'admin', 'cart_lista'))
      $sAccion='crear_cartelera';
   if(isset($_POST['modificar_cartelera']) && $oACL->isUserAuthorized($_SESSION['session_user'], 'admin', 'cart_lista'))
      $sAccion="modificar_cartelera";

   ////Si el id_materia_periodo_lectivo no es NULL se crea un objeto Reporte_cartelera
   if($id_materia_periodo_lectivo!=NULL)
      $oReporte_cartelera = &  new ul_cartelera_reporte($pDB, $oPlantillas,"?menu1op=submenu_agenda&submenuop=cart_lista&id_materia_periodo_lectivo=$id_materia_periodo_lectivo",$id_materia_periodo_lectivo,$id_cartelera);
   else
      $oReporte_cartelera = &  new ul_cartelera_reporte($pDB, $oPlantillas,"?menu1op=submenu_agenda&submenuop=cart_lista",NULL,$id_cartelera);

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

   $id_cartelera=recoger_valor("id_cartelera",$_GET,$_POST);
   if($id_cartelera!=NULL){
      $sQuery = "SELECT count(*) FROM ul_cartelera WHERE id_cartelera=$id_cartelera AND contenido IS NOT NULL AND final>=NOW()";
      $result=$pDB->getFirstRowQuery($sQuery);
      if(is_array($result) && count($result)>0)
         if($result[0]==0)
            $sAccion="listar";
   }

   // Ejecutar accion segun la opcion elegida
   $sCodigoTabla = "";
   switch ($sAccion) {
   // necesitan seleccionar archivo o directorio
   case "eliminar_cartelera":
      if(isset($oReporte_cartelera)){
         if(isset($_POST['id_cartelera'])){
            $bValido=$oReporte_cartelera->eliminar_cartelera($_POST['id_cartelera']);
            if(!$bValido){
               $sCodigoTabla.=$oPlantillas->crearAlerta("error", "Al efectuar operación de Eliminación", $oReporte_cartelera->getMessage());
            }
         }
         else
            $sCodigoTabla.=$oPlantillas->crearAlerta("error", "Error al eliminar", "No se ha seleccionado un id_cartelera");

         $sCodigoTabla .=  $oReporte_cartelera->generarReporte("LISTA_CARTELERAS",$_GET,$_POST);
      }
      break;
   // no necesitan seleccionar archivo o directorio
   case "crear_cartelera":
         if(isset($oReporte_cartelera)){
            return mostrar_formulario_crear_cartelera($pDB,$oPlantillas,$_GET,$_POST,$id_materia_periodo_lectivo);
         }
      break;
   case "modificar_cartelera":
         if(isset($oReporte_cartelera)){
            if(isset($_POST['in_agenda'])){
               return mostrar_formulario_modificar_cartelera($pDB,$oPlantillas,$_GET,$_POST,$id_materia_periodo_lectivo,$_POST['in_agenda']);
            }
            else{
               //
            }
         }
      break;
   case "mostrar_cartelera":
      if(isset($_GET['id_cartelera'])){
         $oContenido=new ul_cartelera(
            $pDB,
            $oPlantillas,
            "?menu1op=submenu_agenda&submenuop=cart_lista",
            $id_materia_periodo_lectivo);
         return $oContenido->visualizar_cartelera($_GET);
      }
      break;
   case "mostrar_evento":
      if(isset($_GET['id_evento'])){
         $oContenido=new ul_evento(
            $pDB,
            $oPlantillas,
            "?menu1op=submenu_agenda&submenuop=cart_lista",
            $id_materia_periodo_lectivo);
         return $oContenido->visualizar_evento($_GET);
      }
      break;
   case "listar":
   default:
      if(isset($oReporte_cartelera)){
         $sCodigoTabla .=  $oReporte_cartelera->generarReporte("LISTA_CARTELERAS",$_GET,$_POST);
      }
   }
   return $sCodigoTabla;
}

function mostrar_formulario_crear_cartelera(&$oDB,&$tpl,&$_GET,&$_POST,$id_materia_periodo_lectivo){
   $sContenido="";
   $oContenido=new ul_cartelera($oDB,$tpl,"?menu1op=submenu_agenda&submenuop=cart_lista",$id_materia_periodo_lectivo);

   // Verificar si se desean guardar cambios
   $tuplaForm = $oContenido->deducirFormulario($_POST);
   if (is_array($tuplaForm)) {
      if ($tuplaForm[0] == "INSERT") {
         $bExito = $oContenido->manejarFormularioInsert($tuplaForm[1], $_POST);
         if($bExito)
            header("Location: ?menu1op=submenu_agenda&submenuop=cart_lista&id_materia_periodo_lectivo=$id_materia_periodo_lectivo");
         else{
            $sContenido .= $tpl->crearAlerta(
               "error",
               "Problema al Crear Cartelera",
               "Al crear cartelera: ".$oContenido->getMessage());
         }
      }
   }
   $sContenido .= $oContenido->generarFormularioInsert("CREAR_CARTELERA", $_POST);
   return $sContenido;
}




function mostrar_formulario_modificar_cartelera(&$oDB,&$tpl,&$_GET,&$_POST,$id_materia_periodo_lectivo,$id_cartelera){
   $sContenido="";
   $oContenido=new ul_cartelera($oDB,$tpl,"?menu1op=submenu_agenda&submenuop=cart_lista",$id_materia_periodo_lectivo, $id_cartelera);

   // Verificar si se desean guardar cambios
   $tuplaForm = $oContenido->deducirFormulario($_POST);
   if (is_array($tuplaForm)) {
      if ($tuplaForm[0] == "UPDATE") {
         $bExito = $oContenido->manejarFormularioUpdate($tuplaForm[1], $_POST);
         if($bExito)
            header("Location: ?menu1op=submenu_agenda&submenuop=cart_lista&id_materia_periodo_lectivo=$id_materia_periodo_lectivo");
         else{
            $sContenido .= $tpl->crearAlerta(
               "error",
               "Problema al Modificar Contenido",
               "Al Modificar Contenido: ".$oContenido->getMessage());
         }
      }
   }
   $sContenido .= $oContenido->generarFormularioUpdate("MODIFICAR_CARTELERA", $_POST,array('id_cartelera'=>$id_cartelera));
   return $sContenido;
}



?>
