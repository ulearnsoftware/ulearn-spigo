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
// | Autores: Iv? Ochoa    <iochoa2@telefonica.net>                         |
// +----------------------------------------------------------------------+
//
// $Id: calf_asignar.mod.php,v 1.1.1.1 2006/03/03 21:59:09 ainiguez Exp $

require_once ("conf/default.conf.php");
require_once ("lib/paloTemplate.class.php");
require_once ("lib/paloACL.class.php");
require_once ("modules/ul_asignar_calificable.class.php");
require_once ("modules/ul_asignar_calificable_reporte.class.php");

$tpl->assign("CONTENT", _moduleContent($pDB, $_GET, $_POST));

function _moduleContent(&$pDB, &$_GET, &$_POST)
{
   //echo "<pre>";print_r($_POST);echo "</pre>";echo "<pre>";print_r($_GET);echo "</pre>";

   global $config;   // definda en conf/default.conf.php
   $oACL=getACL();   // Para conocer los Permisos
   $sCodigoTabla = "";

   // cambiando la plantilla (necesario para las alertas)
   $oPlantillas =& new paloTemplate("skins/".$config->skin);
   $oPlantillas->definirDirectorioPlantillas("");
   $oPlantillas->assign("IMG_PATH", "skins/$config->skin/images");

   // Verificacion del identificador id_materia_periodo_lectivo
   $id_materia_periodo_lectivo = recoger_valor("id_materia_periodo_lectivo",$_GET,$_POST);
   if($id_materia_periodo_lectivo!=NULL)
      if(!ereg("^[[:digit:]]+$",$id_materia_periodo_lectivo)){
         return $oPlantillas->crearAlerta("error","Error","No se ha recibido un valor válido de id_materia_periodo_lectivo");
      }

   // Verificacion del identificador id_calificable
   $id_calificable=recoger_valor("id_calificable",$_GET,$_POST);
   if($id_calificable!=NULL)
      if(!ereg("^[0-9]+$",$id_calificable)){
         return $oPlantillas->crearAlerta("error","Error","No se ha recibido un valor válido de id_calificable");
      }

   $sAccion = recoger_valor("action",$_GET,$_POST,"");

   if($asignar_calificable=recoger_valor("asignar",$_GET,$_POST))
      $sAccion="asignar_calificable";
   if(isset($_POST['eliminar']))
      $sAccion="eliminar_asignacion";

   // si existe el id_calificable debe existir el id_materia_periodo_lectivo
   if($id_materia_periodo_lectivo==NULL)
      $sAccion="listar";
   else{
      if($id_calificable!=""){
         // debe existir el id_calificable para el id_materia_periodo_lectivo ingresado
         $sQuery = "SELECT count(*) FROM ul_calificable WHERE id_calificable=$id_calificable AND id_materia_periodo_lectivo=$id_materia_periodo_lectivo AND (contenido IS NOT NULL) AND final>=NOW()";
         $result=$pDB->getFirstRowQuery($sQuery);
         if(is_array($result) && count($result)>0)
            if($result[0]==0)
               $sAccion="listar";
      }
   }
   ////Si el id_materia_periodo_lectivo no es NULL se crea un objeto Reporte_calificable
   if($id_materia_periodo_lectivo!=NULL)
      $oReporte_calificable = &  new ul_asignar_calificable_reporte(
         $pDB,
         $oPlantillas,
         "?menu1op=submenu_calificable&submenuop=calf_asignar&id_materia_periodo_lectivo=$id_materia_periodo_lectivo",
         $id_materia_periodo_lectivo,
         $id_calificable);

   if($id_calificable==NULL && (
   $sAccion=="asignar_calificable")){
      $sCodigoTabla.=$oPlantillas->crearAlerta("error", "Error al Tomar Calificable", "No se ha seleccionado un calificable");
      $sAccion="listar";
   }

   // Ejecutar accion segun la opcion elegida
   switch ($sAccion) {
   case "asignar_calificable":
      if($oACL->isUserAuthorized($_SESSION['session_user'], 'creat', 'calf_asignar') &&
      $oACL->isUserAuthorized($_SESSION['session_user'], 'delet', 'calf_asignar') &&
      $oACL->isUserAuthorized($_SESSION['session_user'], 'updat', 'calf_asignar')){
         // Informaci?n necesaria para tomar el calificable:
         // * Tiempo restante              * Total de Grupos
         // * Total de Preguntas en Grupo  * Numero de Grupo
         // * Numero de Pregunta           * Id de Grupo
         // * Id de Pregunta

         // validaciones

         // acciones
         $oAsignar = & new ul_asignar_calificable($pDB,$oPlantillas,"?menu1op=submenu_calificable&submenuop=calf_asignar",$id_materia_periodo_lectivo,$id_calificable);
         if(isset($oReporte_calificable)){
            $sCodigoTabla .=  $oAsignar->asignar_calificable();
            $sCodigoTabla .=  $oReporte_calificable->generarReporte("ASIGNAR_CALIFICABLES",$_GET,$_POST);
         }
      }
      else
         return $oPlantillas->crearAlerta("error","Error al asignar calificable","Usted no está autorizado para esta acción");
      break;

   case "eliminar_asignacion":
         if(isset($oReporte_calificable)){
            $id_alumno_materia=recoger_valor("id_alumno_materia",$_GET,$_POST); ///array con los ids de alumno_materia
            $bValido=  $oReporte_calificable->eliminar_asignacion($id_calificable,$id_alumno_materia);
               if(!$bValido)
                  $sCodigoTabla= $oPlantillas->crearAlerta("error","Error al eliminar asignacion calificable",$oReporte_calificable->getMessage());
            $sCodigoTabla .=  $oReporte_calificable->generarReporte("ASIGNAR_CALIFICABLES",$_GET,$_POST);
         }
         break;
   case "anular":
         if(isset($oReporte_calificable)){
            $id_alumno_calificable=recoger_valor("id_alumno_calificable",$_GET,$_POST); ///array con los ids de alumno_materia
            $bValido=  $oReporte_calificable->anular_asignacion($id_alumno_calificable);
               if(!$bValido)
                  $sCodigoTabla= $oPlantillas->crearAlerta("error","Error al anular calificable de alumno ",$oReporte_calificable->getMessage());

         }
   case "listar":
   default:
      if(isset($oReporte_calificable)){
         $sCodigoTabla .=  $oReporte_calificable->generarReporte("ASIGNAR_CALIFICABLES",$_GET,$_POST);
      }
   }
   return $sCodigoTabla;
}





?>
