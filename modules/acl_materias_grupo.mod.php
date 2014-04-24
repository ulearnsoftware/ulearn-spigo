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
// $Id: acl_materias_grupo.mod.php,v 1.1.1.1 2006/03/03 21:59:08 ainiguez Exp $

require_once ("conf/default.conf.php");
require_once ("modules/ul_materias_grupo.class.php");

$tpl->assign("CONTENT", _moduleContent($pDB, $_GET, $_POST));

function _moduleContent(&$pDB, &$_GET, &$_POST)
{
	global $config; // definda en conf/default.conf.php

   $oPlantillas =& new paloTemplate("skins/".$config->skin);
   $oPlantillas->definirDirectorioPlantillas("");
   $oPlantillas->assign("IMG_PATH", "skins/$config->skin/images");

   $id_materia_periodo_lectivo = recoger_valor("id_materia_periodo_lectivo",$_GET,$_POST);
   $id_grupo=recoger_valor("id_grupo",$_GET,$_POST);
   $id_periodo_lectivo=recoger_valor("id_periodo_lectivo",$_GET,$_POST);
   $id_materia=recoger_valor("id_materia",$_GET,$_POST);
   $paralelo=recoger_valor("paralelo",$_GET,$_POST);

   $sAccion = recoger_valor("action",$_GET,$_POST,"");

   if($id_materia_periodo_lectivo!=NULL)
      if(!ereg("^[[:digit:]]+$",$id_materia_periodo_lectivo))
         return $oPlantillas->crearAlerta("error","Error","No se ha recibido un valor válido de id materia");

   ///////Se setea la accion si el permiso es correspondiente
   $oACL=getACL();
   $id_evento="";

   if(isset($_POST['id']))
      $id_materia_grupo=$_POST['id'];

   if(isset($_POST['eliminar']) && $oACL->isUserAuthorized($_SESSION['session_user'], 'delet', 'acl_materias_grupo'))
      $sAccion='eliminar';

   if(isset($_POST['agregar']) && $oACL->isUserAuthorized($_SESSION['session_user'], 'creat', 'acl_materias_grupo'))
      $sAccion='agregar';

   $baseURL="?menu1op=submenu_acl&submenuop=acl_materias_grupo&id_materia_periodo_lectivo=$id_materia_periodo_lectivo&id_grupo=$id_grupo&id_periodo_lectivo=$id_periodo_lectivo&id_materia=$id_materia&paralelo=$paralelo";


   // Ejecutar accion segun la opcion elegida
   $sCodigoTabla = "";

   switch ($sAccion) {
   // necesitan seleccionar archivo o directorio
   case "eliminar":
      if(isset($_POST['id'])){
         $oReporte = &  new ul_materias_grupo_reporte($pDB, $oPlantillas,$baseURL);

            if(ereg("^[[:digit:]]+$",$_POST['id'])){
               $bValido=$oReporte->eliminar_materia($_POST['id']);
                  if(!$bValido){
                     $sCodigoTabla.=$oPlantillas->crearAlerta("error", "Al efectuar operación de Eliminación", $oReporte->getMessage());
                  }
            }
            else
               $sCodigoTabla.=$oPlantillas->crearAlerta("error", "Error al eliminar", "No se ha recibido un id válido");
         $oReporte = &  new ul_materias_grupo_reporte($pDB, $oPlantillas,$baseURL);
         $sCodigoTabla .=  $oReporte->generarReporte("LISTA_MATERIAS_GRUPO",$_GET,$_POST);
      }
      break;
   // no necesitan seleccionar archivo o directorio
   case "agregar":
            $oReporte = &  new ul_materias_grupo_reporte($pDB, $oPlantillas,$baseURL);
            $bValido=$oReporte->agregar_materia();
               if(!$bValido){
                  $sCodigoTabla.=$oPlantillas->crearAlerta("error", "Al Agregar Materia", $oReporte->getMessage());
               }


   case "listar":
   default:
     ////Si el id_materia_periodo_lectivo no es NULL se crea un objeto Reporte_evento

     $oReporte = &  new ul_materias_grupo_reporte($pDB, $oPlantillas,$baseURL);

     $sCodigoTabla .=  $oReporte->generarReporte("LISTA_MATERIAS_GRUPO",$_GET,$_POST);

   }
   return $sCodigoTabla;
}





?>
