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
// $Id: ag_calendario.mod.php,v 1.1.1.1 2006/03/03 21:59:08 ainiguez Exp $

require_once ("conf/default.conf.php");
require_once ("lib/paloTemplate.class.php");
require_once ("lib/paloACL.class.php");
require_once ("modules/ul_calendario.class.php");
require_once ("modules/ul_cartelera.class.php");
require_once ("modules/ul_evento.class.php");
//require_once ("modules/ul_evento_reporte.class.php");

$tpl->assign("CONTENT", _moduleContent($pDB, $_GET, $_POST));

function _moduleContent(&$pDB, &$_GET, &$_POST)
{
   global $config; // definda en conf/default.conf.php
   setLocale(LC_TIME,$config->locale);


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
   //if($id_materia_periodo_lectivo!=NULL)
   //   $oReporte_evento = &  new ul_evento_reporte($pDB, $oPlantillas,"?menu1op=submenu_agenda&submenuop=ag_lista&id_materia_periodo_lectivo=$id_materia_periodo_lectivo",$id_materia_periodo_lectivo,$id_evento);

   $id_evento=recoger_valor("id_evento",$_GET,$_POST);
   if($id_evento!=NULL){
      if($id_materia_periodo_lectivo==NULL)
         $sAccion="listar";
      else{
         $sQuery = "SELECT count(*) FROM ul_evento WHERE id_evento=$id_evento AND id_materia_periodo_lectivo=$id_materia_periodo_lectivo AND (contenido IS NOT NULL)";
        // echo $sQuery;
         $result=$pDB->getFirstRowQuery($sQuery);
         if(is_array($result) && count($result)>0)
            if($result[0]==0)
               $sAccion="listar";
      }
   }

   $id_cartelera=recoger_valor("id_cartelera",$_GET,$_POST);
   if($id_cartelera!=NULL){
      $sQuery = "SELECT count(*) FROM ul_cartelera WHERE id_cartelera=$id_cartelera AND contenido IS NOT NULL";
      $result=$pDB->getFirstRowQuery($sQuery);
      if(is_array($result) && count($result)>0)
         if($result[0]==0)
            $sAccion="listar";
   }



   // Ejecutar accion segun la opcion elegida
   $sCodigoTabla = "";

   switch ($sAccion) {
   case "mostrar_cartelera":
      if(isset($_GET['id_cartelera'])){
         $oContenido=new ul_cartelera(
            $pDB,
            $oPlantillas,
            "?menu1op=submenu_agenda&submenuop=ag_calendario",
            $id_materia_periodo_lectivo);
         return $oContenido->visualizar_cartelera($_GET);
      }
      break;
   case "mostrar_evento":

         if(isset($_GET['id_evento'])){
            $oContenido=new ul_evento(
               $pDB,
               $oPlantillas,
               "?menu1op=submenu_agenda&submenuop=ag_calendario",
               $id_materia_periodo_lectivo);
            return $oContenido->visualizar_evento($_GET);
         }
      break;
   case "listar":
   default:
         $oContenido=new ul_calendario(
            $pDB,
            $oPlantillas,
            "?menu1op=submenu_agenda&submenuop=ag_calendario",
            $id_materia_periodo_lectivo);
         $mes=$anio=NULL;
         if(isset($_GET['mes']))
            $mes=$_GET['mes'];
         if(isset($_GET['anio']))
            $anio=$_GET['anio'];
         return $oContenido->visualizar_calendario($mes, $anio);
      //if(isset($oReporte_evento)){
         //$sCodigoTabla .=  $oReporte_evento->generarReporte("LISTA_EVENTOS",$_GET,$_POST);
      //}

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
