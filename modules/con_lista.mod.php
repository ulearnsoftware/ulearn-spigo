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
// $Id: con_lista.mod.php,v 1.1.1.1 2006/03/03 21:59:09 ainiguez Exp $

require_once ("conf/default.conf.php");
require_once ("lib/paloTemplate.class.php");
require_once ("lib/paloACL.class.php");
require_once ("modules/ul_contenido.class.php");
require_once ("modules/ul_contenido_reporte.class.php");

$tpl->assign("CONTENT", _moduleContent($pDB, $_GET, $_POST));

function _moduleContent(&$pDB, &$_GET, &$_POST)
{
	global $config; // definda en conf/default.conf.php

   $oPlantillas =& new paloTemplate("skins/".$config->skin);
   $oPlantillas->definirDirectorioPlantillas("");
   $oPlantillas->assign("IMG_PATH", "skins/$config->skin/images");

   $id_materia_periodo_lectivo=recoger_valor("id_materia_periodo_lectivo",$_GET,$_POST);

   $sAccion=recoger_valor("action",$_GET,$_POST,"");

   if($id_materia_periodo_lectivo!=NULL)
      if(!ereg("^[[:digit:]]+$",$id_materia_periodo_lectivo))
         return $oPlantillas->crearAlerta("error","Error","No se ha recibido un valor válido de id materia");

   ///////Se setea la accion si el permiso es correspondiente
   $oACL=getACL();

   $id_contenido="";
   if(isset($_GET['id_contenido']))
      $id_contenido=$_GET['id_contenido'];

   if(isset($_POST['in_contenido']))
      $id_contenido=$_POST['in_contenido'];

   if(isset($_POST['eliminar']) && $oACL->isUserAuthorized($_SESSION['session_user'], 'admin', 'con_lista'))
      $sAccion='eliminar';
   if(isset($_POST['crear']) && $oACL->isUserAuthorized($_SESSION['session_user'], 'admin', 'con_lista'))
      $sAccion='crear_contenido';
   if(isset($_POST['modificar']) && $oACL->isUserAuthorized($_SESSION['session_user'], 'admin', 'con_lista'))
      $sAccion="modificar_contenido";

   ////Si el id_materia_periodo_lectivo no es NULL se crea un objeto Reporte_contenido
   if($id_materia_periodo_lectivo!=NULL)
      $oReporte_contenido = &  new ul_contenido_reporte($pDB, $oPlantillas,"?menu1op=submenu_contenido&submenuop=con_lista&id_materia_periodo_lectivo=$id_materia_periodo_lectivo",$id_materia_periodo_lectivo,$id_contenido);


   ////////Efectuar validacion del id_contenido que pertenezca a la materia seleccionada
   ////////En caso de existir un id_contenido
   //si existe
   //se llama a la funcion verificar
   if($id_contenido!=""){
      $sQuery="SELECT count(*) FROM ul_contenido WHERE id_contenido=$id_contenido AND id_materia=".buscar_id_materia($pDB, $id_materia_periodo_lectivo);
      $result=$pDB->getFirstRowQuery($sQuery);
      if(is_array($result) && count($result)>0)
         if($result[0]==0)
            $sAccion="listar";

   }


   // Ejecutar accion segun la opcion elegida
   $sCodigoTabla = "";

   switch ($sAccion) {
   // necesitan seleccionar archivo o directorio
   case "eliminar":
      if(isset($oReporte_contenido)){
         if(isset($_POST['in_contenido'])){
            $bValido=$oReporte_contenido->eliminar_contenido($_POST['in_contenido']);
            if(!$bValido){
               $sCodigoTabla.=$oPlantillas->crearAlerta("error", "Al efectuar operación de Eliminación", $oReporte_contenido->getMessage());
            }
         }
         else
            $sCodigoTabla.=$oPlantillas->crearAlerta("error", "Error al eliminar", "No se ha seleccionado un id_contenido");

         $sCodigoTabla .=  $oReporte_contenido->generarReporte("LISTA_CONTENIDO",$_GET,$_POST);
      }
      break;
   // no necesitan seleccionar archivo o directorio
   case "crear_contenido":
         if(isset($oReporte_contenido)){
            return mostrar_formulario_crear_contenido($pDB,$oPlantillas,$_GET,$_POST,$id_materia_periodo_lectivo);
         }
      break;
   case "modificar_contenido":
         if(isset($oReporte_contenido)){
            if(isset($_POST['in_contenido'])){
               return mostrar_formulario_modificar_contenido($pDB,$oPlantillas,$_GET,$_POST,$id_materia_periodo_lectivo,$_POST['in_contenido']);
            }
            else{
               //
            }
         }
      break;
   case "mostrar_contenido":
         if(isset($_GET['id_contenido'])){
            $oContenido=new ul_contenido(
               $pDB,
               $oPlantillas,
               $id_materia_periodo_lectivo);
            return $oContenido->visualizar_contenido($_GET);
         }

      break;
   case "listar":
   default:
      if(isset($oReporte_contenido)){
         $sCodigoTabla .=  $oReporte_contenido->generarReporte("LISTA_CONTENIDO",$_GET,$_POST);
      }
   }
   return $sCodigoTabla;
}

function mostrar_formulario_crear_contenido(&$oDB,&$tpl,&$_GET,&$_POST,$id_materia_periodo_lectivo){
   $sContenido="";
   $oContenido=new ul_contenido($oDB,$tpl,$id_materia_periodo_lectivo);

   // Verificar si se desean guardar cambios
   $tuplaForm = $oContenido->deducirFormulario($_POST);
   if (is_array($tuplaForm)) {
      if ($tuplaForm[0] == "INSERT") {
         $bExito = $oContenido->manejarFormularioInsert($tuplaForm[1], $_POST);
         if($bExito)
            header("Location: ?menu1op=submenu_contenido&submenuop=con_lista&id_materia_periodo_lectivo=$id_materia_periodo_lectivo");
         else{
            $sContenido .= $tpl->crearAlerta(
               "error",
               "Problema al Crear Contenido",
               "Al crear contenido: ".$oContenido->getMessage());
         }
      }
   }
   $sContenido .= $oContenido->generarFormularioInsert("CREAR_CONTENIDO", $_POST);
   return $sContenido;
}




function mostrar_formulario_modificar_contenido(&$oDB,&$tpl,&$_GET,&$_POST,$id_materia_periodo_lectivo,$id_contenido){
   $sContenido="";
   $oContenido=new ul_contenido($oDB,$tpl,$id_materia_periodo_lectivo, $id_contenido);

   // Verificar si se desean guardar cambios
   $tuplaForm = $oContenido->deducirFormulario($_POST);
   if (is_array($tuplaForm)) {
      if ($tuplaForm[0] == "UPDATE") {
         $bExito = $oContenido->manejarFormularioUpdate($tuplaForm[1], $_POST);
         if($bExito)
            header("Location: ?menu1op=submenu_contenido&submenuop=con_lista&id_materia_periodo_lectivo=$id_materia_periodo_lectivo");
         else{
            $sContenido .= $tpl->crearAlerta(
               "error",
               "Problema al Modificar Contenido",
               "Al Modificar Contenido: ".$oContenido->getMessage());
         }
      }
   }
   $sContenido .= $oContenido->generarFormularioUpdate("MODIFICAR_CONTENIDO", $_POST,array('id_contenido'=>$id_contenido));
   return $sContenido;
}

function buscar_id_materia($pDB, $id_mpl){
   $sQuery="SELECT id_materia FROM ul_materia_periodo_lectivo WHERE id=$id_mpl";
   $result=$pDB->getFirstRowQuery($sQuery);
   if(is_array($result) && count($result)>0)
      return $result[0];
   return FALSE;
}

?>
