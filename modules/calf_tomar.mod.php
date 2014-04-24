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
// | Autores:                                                             |
// +----------------------------------------------------------------------+
//
// $Id: calf_tomar.mod.php,v 1.1.1.1 2006/03/03 21:59:09 ainiguez Exp $

require_once ("conf/default.conf.php");
require_once ("lib/paloTemplate.class.php");
require_once ("lib/paloACL.class.php");
require_once ("modules/ul_tomar_calificable.class.php");
require_once ("modules/ul_tomar_calificable_reporte.class.php");

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

   if($tomar_calificable=recoger_valor("tomar_calificable",$_GET,$_POST))
      $sAccion="tomar_calificable";

   if($tomar_calificable=recoger_valor("comenzar_calificable",$_GET,$_POST))
      $sAccion="comenzar_calificable";


   if($tomar_calificable=recoger_valor("Terminar",$_GET,$_POST))
      $sAccion="Terminar";

   // si existe el id_calificable debe existir el id_materia_periodo_lectivo
   if($id_calificable!=""){
      if($id_materia_periodo_lectivo==NULL)
         $sAccion="listar";
      else{
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
     $oReporte_calificable = &  new ul_tomar_calificable_reporte($pDB, $oPlantillas,"?menu1op=submenu_calificable&submenuop=calf_tomar&id_materia_periodo_lectivo=$id_materia_periodo_lectivo",$id_materia_periodo_lectivo,$id_calificable);

   if($id_calificable==NULL && (
   $sAccion=="tomar_calificable")){
      $sCodigoTabla.=$oPlantillas->crearAlerta("error", "Error al Tomar Calificable", "No se ha seleccionado un calificable");
      $sAccion="listar";
   }

   // Ejecutar accion segun la opcion elegida
   switch ($sAccion) {

   case "comenzar_calificable":
      return mostrar_formulario_bienvenida_calificable($pDB,$oPlantillas,$_GET,$_POST);
   break;


   case "tomar_calificable":
      $id_alumno_calificable=recoger_valor("id_alumno_calificable",$_GET,$_POST);
      if(!is_null($id_alumno_calificable)){
         $sQuery = "SELECT estatus FROM ul_alumno_calificable WHERE id_alumno_calificable=$id_alumno_calificable";
         $result= $pDB->getFirstRowQuery($sQuery,TRUE);
         if(is_array($result)){
            if(count($result)>0)
               if($result['estatus']=='T'){
                  header("Location: ?menu1op=submenu_calificable&submenuop=calf_tomar&id_materia_periodo_lectivo=".recoger_valor("id_materia_periodo_lectivo",$_GET,$_POST)."&id_calificable=".recoger_valor("id_calificable",$_GET,$_POST));
               }
         }else{
         }
      }

      return mostrar_formulario_tomar_calificable($pDB,$oPlantillas,$_GET,$_POST);
   break;

   case "terminar": // solo se puede terminar si se ha estado tomando el test
      $oTCalificable = new ul_tomar_calificable(
                        $pDB,
                        $tpl,
                        "?menu1op=submenu_calificable&submenuop=calf_tomar&id_materia_periodo_lectivo=".recoger_valor("id_materia_periodo_lectivo",$_GET,$_POST)."&id_calificable=".recoger_valor("id_calificable",$_GET,$_POST));

      // realiza la presentación del formulario con los datos
      if($oTCalificable->Terminar()===FALSE){
         $sCodigoTabla = $oTCalificable->_msgError;
      }

      break;

   case "listar":
   default:
      if(isset($oReporte_calificable)){
         $sCodigoTabla .=  $oReporte_calificable->generarReporte("LISTA_CALIFICABLES",$_GET,$_POST);
      }
   }
   return $sCodigoTabla;
}

function id_grupos($oDB,$id_calificable)
{
   $resp=array();
   if($id_calificable==NULL)
      return $resp;
   $sQuery="SELECT id_grupo_pregunta FROM ul_grupo_pregunta WHERE id_calificable=$id_calificable";
   $result=$oDB->fetchTable($sQuery,TRUE);
   if(is_array($result) && count($result)>0)
      foreach($result as $i=>$value)
         $resp[]=$value['id_grupo_pregunta'];
   return $resp;
}

function id_preguntas($oDB,$id_grupos)
{
   $resp=array();
   if($id_grupos==NULL)
      return $resp;
   if(is_array($id_grupos))
      $grupos=implode(",",$id_grupos);
   else
      $grupos=$id_grupos;
   $sQuery="SELECT id_pregunta FROM ul_pregunta WHERE id_grupo_pregunta IN ($grupos)";
   $result=$oDB->fetchTable($sQuery,TRUE);
   if(is_array($result) && count($result)>0)
      foreach($result as $i=>$value)
         $resp[]=$value['id_pregunta'];
   return $resp;
}

function mostrar_formulario_bienvenida_calificable(&$oDB,&$tpl,&$_GET,&$_POST){
   $sContenido="";

   $oTCalificable = new ul_tomar_calificable(
                     $oDB,
                     $tpl,
                     "?menu1op=submenu_calificable&submenuop=calf_tomar&id_materia_periodo_lectivo=".recoger_valor("id_materia_periodo_lectivo",$_GET,$_POST));

   // realiza la presentación del formulario con los datos
   return $oTCalificable->pantalla_bienvenida();
}

function mostrar_formulario_tomar_calificable(&$oDB,&$tpl,&$_GET,&$_POST){
   $sContenido="";

   $oTCalificable = new ul_tomar_calificable(
                     $oDB,
                     $tpl,
                     "?menu1op=submenu_calificable&submenuop=calf_tomar&id_materia_periodo_lectivo=".recoger_valor("id_materia_periodo_lectivo",$_GET,$_POST)."&id_calificable=".recoger_valor("id_calificable",$_GET,$_POST));


   if(!isset($_POST['gp_ids']) && !isset($_POST['calificable'])){
      // Solicita los datos necesarios al realizar la ejecución por primera vez
      if($oTCalificable->inicializacion()===FALSE){
         return $oTCalificable->_msgError;
      }
   }else{
      // se almacenan los cambios en la base de datos
      if($oTCalificable->guardar_cambios()===FALSE){
         $sContenido .= $oTCalificable->_msgError;
      }
      // Procesar los datos ingresados
      // Si se presiona terminar se retorna a la pagina principal
      $oTCalificable->procesar_cambios();
   }


   // actualiza los datos que se van a presentar
   if($oTCalificable->actualizar_alumno_pregunta()===FALSE){
      $sContenido .= $oTCalificable->_msgError;
   }

   // realiza la presentación del formulario con los datos
   $sContenido .= $oTCalificable->tomar_calificable();
   return $sContenido;
}

?>
