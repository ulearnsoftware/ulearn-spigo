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
// $Id: calf_lista.mod.php,v 1.1.1.1 2006/03/03 21:59:09 ainiguez Exp $

require_once ("conf/default.conf.php");
require_once ("lib/paloTemplate.class.php");
require_once ("lib/paloACL.class.php");
require_once ("modules/ul_calificable_reporte.class.php");
require_once ("modules/ul_calificable.class.php");
require_once ("modules/ul_calificable_grupo_pregunta.class.php");
require_once ("modules/ul_calificable_pregunta.class.php");
require_once ("modules/ul_calificable_respuesta.class.php");

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

   if(isset($_POST['eliminar_calificable']))
      $sAccion='eliminar_calificable';
   if(isset($_POST['crear_calificable']))
      $sAccion='crear_calificable';
   if(isset($_POST['modificar_calificable']))
      $sAccion="modificar_calificable";
   if($editar_calificable=recoger_valor("editar_calificable",$_GET,$_POST))
      $sAccion="editar_calificable";

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
     $oReporte_calificable = &  new ul_calificable_reporte($pDB, $oPlantillas,"?menu1op=submenu_calificable&submenuop=calf_lista&id_materia_periodo_lectivo=$id_materia_periodo_lectivo",$id_materia_periodo_lectivo,$id_calificable);

   if($id_calificable==NULL && (
   $sAccion=="modificar_calificable" ||
   $sAccion=="editar_calificable" ||
   $sAccion=="eliminar_calificable")){
      $sCodigoTabla.=$oPlantillas->crearAlerta("error", "Error al editar", "No se ha seleccionado un calificable");
      $sAccion="listar";
   }

   if($sAccion=="editar_calificable"){
      // no se puede editar si ha sido asignado a algun estudiante
      $Query="SELECT count(*) FROM ul_alumno_calificable WHERE id_calificable='$id_calificable' AND (estatus='V' OR estatus='T')";
      $result=$pDB->getFirstRowQuery($Query);
      if(is_array($result)){
         if(count($result)>0 && $result[0]>0){
            $sCodigoTabla.=$oPlantillas->crearAlerta("error", "Error al editar", "No se puede editar si ha sido visto o terminado por algún estudiante. #Est=".$result[0]);
            $sAccion="listar";
         }
      }else{
         $sCodigoTabla.=$oPlantillas->crearAlerta("error", "Error al editar", "No se puedo acceder a la BD. ".$pDB->errMsg);
         $sAccion="listar";
      }
   }

   // Ejecutar accion segun la opcion elegida
   switch ($sAccion) {
   // no necesitan seleccionar archivo o directorio
   case "crear_calificable":
      if($oACL->isUserAuthorized($_SESSION['session_user'], 'creat', 'calf_lista') &&
      isset($oReporte_calificable)){
         return mostrar_formulario_crear_calificable($pDB,$oPlantillas,$_GET,$_POST,$id_materia_periodo_lectivo);
      }
      break;

   case "modificar_calificable":
      if($oACL->isUserAuthorized($_SESSION['session_user'], 'updat', 'calf_lista') &&
      isset($oReporte_calificable)){
            return mostrar_formulario_modificar_calificable($pDB,$oPlantillas,$_GET,$_POST,$id_materia_periodo_lectivo,$id_calificable);
      }
      break;
   // necesitan seleccionar archivo o directorio
   case "eliminar_calificable":
      if($oACL->isUserAuthorized($_SESSION['session_user'], 'delet', 'calf_lista') &&
      isset($oReporte_calificable)){
         $bValido=$oReporte_calificable->eliminar_calificable($id_calificable);
         if(!$bValido){
            $sCodigoTabla.=$oPlantillas->crearAlerta("error", "Al efectuar operación de Eliminación", $oReporte_calificable->getMessage());
         }
         $sCodigoTabla .=  $oReporte_calificable->generarReporte("LISTA_CALIFICABLES",$_GET,$_POST);

      }
      break;

   case "editar_calificable":
      if($oACL->isUserAuthorized($_SESSION['session_user'], 'creat', 'calf_lista') &&
      $oACL->isUserAuthorized($_SESSION['session_user'], 'delet', 'calf_lista') &&
      $oACL->isUserAuthorized($_SESSION['session_user'], 'updat', 'calf_lista')){

         // validaciones
         switch($editar_calificable){
         case "Crear Pregunta":
            //buscamos grupos creados
            if(count(id_grupos($pDB,$id_calificable))==0){
               $sCodigoTabla.=$oPlantillas->crearAlerta("error", "Error al editar", "No se han creado grupos.");
               $editar_calificable="Editar";
            }
            break;
         case "Crear Respuesta":
            // buscamos preguntas multiples creadas
            if(count(id_preguntas($pDB,id_grupos($pDB,$id_calificable)))==0){
               $sCodigoTabla.=$oPlantillas->crearAlerta("error", "Error al editar", "No se han creado grupos o preguntas cerradas(multiples).");
               $editar_calificable="Editar";
            }
            break;
         case "Modificar_Grupo_Pregunta":
         case "Eliminar_Grupo_Pregunta":
            $id_grupo_pregunta=recoger_valor("id_grupo_pregunta",$_GET,$_POST);
            if($id_grupo_pregunta==NULL){
               $sCodigoTabla.=$oPlantillas->crearAlerta("error", "Error al editar", "No se ha seleccionado un grupo");
               $editar_calificable="Editar";
            }
            break;
         case "Modificar_Pregunta":
         case "Eliminar_Pregunta":
            $id_pregunta=recoger_valor("id_pregunta",$_GET,$_POST);
            if($id_pregunta==NULL){
               $sCodigoTabla.=$oPlantillas->crearAlerta("error", "Error al editar", "No se ha seleccionado una pregunta");
               $editar_calificable="Editar";
            }
            break;
         case "Modificar_Respuesta":
         case "Eliminar_Respuesta":
            $id_respuesta=recoger_valor("id_respuesta",$_GET,$_POST);
            if($id_respuesta==NULL){
               $sCodigoTabla.=$oPlantillas->crearAlerta("error", "Error al editar", "No se ha seleccionado una respuesta");
               $editar_calificable="Editar";
            }
            break;
         }


         // acciones
         switch($editar_calificable){
         case "Crear Grupo":
            return mostrar_formulario_crear_calificable_grupo_pregunta($pDB,$oPlantillas,$_GET,$_POST,$id_materia_periodo_lectivo,$id_calificable)."<hr />".mostrar_formulario_vista_edicion($pDB,$oPlantillas,$_GET,$_POST);
         case "Crear Pregunta":
            return mostrar_formulario_crear_calificable_pregunta($pDB,$oPlantillas,$_GET,$_POST)."<hr />".mostrar_formulario_vista_edicion($pDB,$oPlantillas,$_GET,$_POST);
         case "Crear Respuesta":
            return mostrar_formulario_crear_calificable_respuesta($pDB,$oPlantillas,$_GET,$_POST)."<hr />".mostrar_formulario_vista_edicion($pDB,$oPlantillas,$_GET,$_POST);

         case "Modificar_Grupo_Pregunta":
            return mostrar_formulario_modificar_calificable_grupo_pregunta($pDB,$oPlantillas,$_GET,$_POST,$id_materia_periodo_lectivo,$id_calificable,$id_grupo_pregunta)."<hr />".mostrar_formulario_vista_edicion($pDB,$oPlantillas,$_GET,$_POST);
         case "Modificar_Pregunta":
            return mostrar_formulario_modificar_calificable_pregunta($pDB,$oPlantillas,$_GET,$_POST)."<hr />".mostrar_formulario_vista_edicion($pDB,$oPlantillas,$_GET,$_POST);
         case "Modificar_Respuesta":
            return mostrar_formulario_modificar_calificable_respuesta($pDB,$oPlantillas,$_GET,$_POST)."<hr />".mostrar_formulario_vista_edicion($pDB,$oPlantillas,$_GET,$_POST);

         case "Eliminar_Grupo_Pregunta":
            return mostrar_formulario_eliminar_calificable_grupo_pregunta($pDB,$oPlantillas,$_GET,$_POST);
         case "Eliminar_Pregunta":
            return mostrar_formulario_eliminar_calificable_pregunta($pDB,$oPlantillas,$_GET,$_POST);
         case "Eliminar_Respuesta":
            return mostrar_formulario_eliminar_calificable_respuesta($pDB,$oPlantillas,$_GET,$_POST);

         case "Editar":
         default:
            return $sCodigoTabla.mostrar_formulario_vista_edicion($pDB,$oPlantillas,$_GET,$_POST);
         }
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

function mostrar_formulario_crear_calificable(&$oDB,&$tpl,&$_GET,&$_POST,$id_materia_periodo_lectivo){
   $sContenido="";
   $oContenido=new ul_calificable($oDB, $tpl, "?menu1op=submenu_calificable&submenuop=calf_lista", $id_materia_periodo_lectivo);
   $msg="";

   $sNombreFormulario="CREAR_CALIFICABLE";
   $oContenido->definicion_Formulario($sNombreFormulario);
   // Verificar si se desean guardar cambios
   $tuplaForm = $oContenido->deducirFormulario($_POST);
   if (is_array($tuplaForm)) {
      if ($tuplaForm[0] == "INSERT") {
         $bExito = $oContenido->manejarFormularioInsert($sNombreFormulario, $_POST);
         if($bExito){
            header("Location: ?menu1op=submenu_calificable&submenuop=calf_lista&id_materia_periodo_lectivo=$id_materia_periodo_lectivo");
         }
         else{
            $sContenido .= $tpl->crearAlerta(
               "error",
               "Problema al Crear Calificable",
               "Al crear calificable: ".$oContenido->getMessage());
         }
      }
   }
   $sContenido .= $oContenido->generarFormularioInsert($sNombreFormulario, $_POST);
   return $sContenido;
}




function mostrar_formulario_modificar_calificable(&$oDB,&$tpl,&$_GET,&$_POST,$id_materia_periodo_lectivo,$id_calificable){
   $sContenido="";
   $oContenido=new ul_calificable($oDB, $tpl, "?menu1op=submenu_calificable&submenuop=calf_lista", $id_materia_periodo_lectivo, $id_calificable);

   $sNombreFormulario="MODIFICAR_CALIFICABLE";
   $oContenido->definicion_Formulario($sNombreFormulario);
   // Verificar si se desean guardar cambios
   $tuplaForm = $oContenido->deducirFormulario($_POST);
   if (is_array($tuplaForm)) {
      if ($tuplaForm[0] == "UPDATE") {
         $bExito = $oContenido->manejarFormularioUpdate($sNombreFormulario, $_POST);
         if($bExito)
            header("Location: ?menu1op=submenu_calificable&submenuop=calf_lista&id_materia_periodo_lectivo=$id_materia_periodo_lectivo");
         else{
            $sContenido .= $tpl->crearAlerta(
               "error",
               "Problema al Modificar Calificable",
               "Al Modificar Contenido: ".$oContenido->getMessage());
         }
      }
   }

   $sContenido .= $oContenido->generarFormularioUpdate($sNombreFormulario, $_POST,array('id_calificable'=>$id_calificable));
   return $sContenido;
}

// Formulario Vista de Edicion
function mostrar_formulario_vista_edicion($pDB,$oPlantillas,$_GET,$_POST)
{
   $id_materia_periodo_lectivo = recoger_valor("id_materia_periodo_lectivo",$_GET,$_POST);
   $id_calificable = recoger_valor("id_calificable",$_GET,$_POST);

   $oContenido=new ul_calificable(
      $pDB,
      $oPlantillas,
      "?menu1op=submenu_calificable&submenuop=calf_lista&id_materia_periodo_lectivo=$id_materia_periodo_lectivo&id_calificable=$id_calificable",
      $id_materia_periodo_lectivo,
      $id_calificable);
   return $oContenido->vista_edicion(array_merge($_GET,$_POST));
}


///calificable_grupo_pregunta

// Crear calificable_grupo_pregunta
function mostrar_formulario_crear_calificable_grupo_pregunta(&$oDB, &$tpl, &$_GET, &$_POST,$id_materia_periodo_lectivo, $id_calificable){
   $sContenido="";
   $oContenido=new ul_calificable_grupo_pregunta(
      $oDB,
      $tpl,
      "?menu1op=submenu_calificable&submenuop=calf_lista&editar_calificable=Editar&id_materia_periodo_lectivo=$id_materia_periodo_lectivo&id_calificable=$id_calificable",
      array_merge($_GET,$_POST));

   // Verificar si se desean guardar cambios
   $tuplaForm = $oContenido->deducirFormulario($_POST);
   if (is_array($tuplaForm)) {
      if ($tuplaForm[0] == "INSERT") {
         $bExito = $oContenido->manejarFormularioInsert($tuplaForm[1], $_POST);
         if(!$bExito)
            $sContenido .= $tpl->crearAlerta(
               "error",
               "Problema al Crear Calificable_Grupo_Pregunta",
               "Al crear calificable: ".$oContenido->getMessage());
         else{
            $sQuery="SELECT LAST_INSERT_ID()";
            $result=$oDB->getFirstRowQuery($sQuery,TRUE);
            header("Location: ?menu1op=submenu_calificable&submenuop=calf_lista&editar_calificable=Crear Pregunta&id_materia_periodo_lectivo=$id_materia_periodo_lectivo&id_calificable=$id_calificable&id_grupo_pregunta=".$result['last_insert_id()']);
         }
      }
   }
   $sContenido .= $oContenido->generarFormularioInsert("CREAR_GRUPO_PREGUNTAS", $_POST);
   return $sContenido;
}

// Modificar calificable_grupo_pregunta
function mostrar_formulario_modificar_calificable_grupo_pregunta(&$oDB, &$tpl, &$_GET, &$_POST,$id_materia_periodo_lectivo, $id_calificable,$id_grupo_pregunta){
   $sContenido="";
   $oContenido=new ul_calificable_grupo_pregunta(
      $oDB,
      $tpl,
      "?menu1op=submenu_calificable&submenuop=calf_lista&editar_calificable=Editar&id_materia_periodo_lectivo=$id_materia_periodo_lectivo&id_calificable=$id_calificable",
      array_merge($_GET,$_POST));

   // Verificar si se desean guardar cambios
   $tuplaForm = $oContenido->deducirFormulario($_POST);
   if (is_array($tuplaForm)) {
      if ($tuplaForm[0] == "UPDATE") {
         $bExito = $oContenido->manejarFormularioUpdate($tuplaForm[1], $_POST);
         if(!$bExito)
            $sContenido .= $tpl->crearAlerta(
               "error",
               "Problema al Modificar Calificable_Grupo_Pregunta",
               "Al Modificar Contenido: ".$oContenido->getMessage());
         else
            header("Location: ?menu1op=submenu_calificable&submenuop=calf_lista&editar_calificable=Editar&id_materia_periodo_lectivo=$id_materia_periodo_lectivo&id_calificable=$id_calificable");
      }
   }
   $sContenido .= $oContenido->generarFormularioUpdate("MODIFICAR_GRUPO_PREGUNTAS", $_POST,array("id_grupo_pregunta"=>$id_grupo_pregunta));
   return $sContenido;
}

///calificable_pregunta

// Crear calificable_pregunta
function mostrar_formulario_crear_calificable_pregunta(&$oDB, &$tpl, &$_GET, &$_POST){
   $id_materia_periodo_lectivo = recoger_valor("id_materia_periodo_lectivo",$_GET,$_POST);
   $id_calificable = recoger_valor("id_calificable",$_GET,$_POST);
   $id_grupo_pregunta = recoger_valor("id_grupo_pregunta",$_GET,$_POST);

   $sContenido="";
   $oContenido=new ul_calificable_pregunta(
      $oDB,
      $tpl,
      "?menu1op=submenu_calificable&submenuop=calf_lista&editar_calificable=Editar&id_materia_periodo_lectivo=$id_materia_periodo_lectivo&id_calificable=$id_calificable",
      array_merge($_GET,$_POST));

   // Verificar si se desean guardar cambios
   $tuplaForm = $oContenido->deducirFormulario($_POST);
   if (is_array($tuplaForm)) {
      if ($tuplaForm[0] == "INSERT") {
         $bExito = $oContenido->manejarFormularioInsert($tuplaForm[1], $_POST);
         if(!$bExito)
            $sContenido .= $tpl->crearAlerta(
               "error",
               "Problema al Crear Calificable_Pregunta",
               "Al crear calificable: ".$oContenido->getMessage());
         else{
            //echo "<pre>";print_r($_POST);echo "</pre>";echo "<pre>";print_r($_GET);echo "</pre>";

            $sQuery="SELECT LAST_INSERT_ID()";
            $result=$oDB->getFirstRowQuery($sQuery,TRUE);
            if(recoger_valor("in_ul_pregunta_INSERT_CREAR_PREGUNTAS_tipo_respuesta",$_GET,$_POST)=='M')
               header("Location: ?menu1op=submenu_calificable&submenuop=calf_lista&editar_calificable=Crear Respuesta&id_materia_periodo_lectivo=$id_materia_periodo_lectivo&id_calificable=$id_calificable&id_grupo_pregunta=$id_grupo_pregunta&id_pregunta=".$result['last_insert_id()']);
            else
               header("Location: ?menu1op=submenu_calificable&submenuop=calf_lista&editar_calificable=Crear Pregunta&id_materia_periodo_lectivo=$id_materia_periodo_lectivo&id_calificable=$id_calificable&id_grupo_pregunta=$id_grupo_pregunta");
         }
      }
   }
   $sContenido .= $oContenido->generarFormularioInsert("CREAR_PREGUNTAS", $_POST);
   return $sContenido;
}

// Crear calificable_pregunta
function mostrar_formulario_modificar_calificable_pregunta(&$oDB, &$tpl, &$_GET, &$_POST){
   $id_materia_periodo_lectivo = recoger_valor("id_materia_periodo_lectivo",$_GET,$_POST);
   $id_calificable = recoger_valor("id_calificable",$_GET,$_POST);
   $id_pregunta = recoger_valor("id_pregunta",$_GET,$_POST);

   $sContenido="";
   $oContenido=new ul_calificable_pregunta(
      $oDB,
      $tpl,
      "?menu1op=submenu_calificable&submenuop=calf_lista&editar_calificable=Editar&id_materia_periodo_lectivo=$id_materia_periodo_lectivo&id_calificable=$id_calificable",
      array_merge($_GET,$_POST));

   // Verificar si se desean guardar cambios
   $tuplaForm = $oContenido->deducirFormulario($_POST);
   if (is_array($tuplaForm)) {
      if ($tuplaForm[0] == "UPDATE") {
         $bExito = $oContenido->manejarFormularioUpdate($tuplaForm[1], $_POST);
         if(!$bExito)
            $sContenido .= $tpl->crearAlerta(
               "error",
               "Problema al Modificar Calificable_Pregunta",
               "Al modificar calificable: ".$oContenido->getMessage());
         else
            header("Location: ?menu1op=submenu_calificable&submenuop=calf_lista&editar_calificable=Editar&id_materia_periodo_lectivo=$id_materia_periodo_lectivo&id_calificable=$id_calificable");
      }
   }
   $sContenido .= $oContenido->generarFormularioUpdate("MODIFICAR_PREGUNTAS", $_POST,array("id_pregunta"=>$id_pregunta));
   return $sContenido;
}

///calificable_respuesta

// Crear calificable_respuesta
function mostrar_formulario_crear_calificable_respuesta(&$oDB, &$tpl, &$_GET, &$_POST){
   //echo "<pre>";print_r($_POST);echo "</pre>";echo "<pre>";print_r($_GET);echo "</pre>";
   $id_materia_periodo_lectivo = recoger_valor("id_materia_periodo_lectivo",$_GET,$_POST);
   $id_calificable = recoger_valor("id_calificable",$_GET,$_POST);

   $sContenido="";
   $oContenido=new ul_calificable_respuesta(
      $oDB,
      $tpl,
      "?menu1op=submenu_calificable&submenuop=calf_lista&editar_calificable=Editar&id_materia_periodo_lectivo=$id_materia_periodo_lectivo&id_calificable=$id_calificable",
      array_merge($_GET,$_POST));

   // Verificar si se desean guardar cambios
   $tuplaForm = $oContenido->deducirFormulario($_POST);
   if (is_array($tuplaForm)) {
      if ($tuplaForm[0] == "INSERT") {
         $bExito = $oContenido->manejarFormularioInsert($tuplaForm[1], $_POST);

         $id_grupo_pregunta = recoger_valor("in_ul_respuesta_INSERT_CREAR_RESPUESTAS_id_grupo_pregunta",$_GET,$_POST);
         $id_pregunta = recoger_valor("in_ul_respuesta_INSERT_CREAR_RESPUESTAS_id_pregunta",$_GET,$_POST);

         if(!$bExito)
            $sContenido .= $tpl->crearAlerta(
               "error",
               "Problema al Crear Calificable_Respuesta",
               "Al crear calificable: ".$oContenido->getMessage());
         else{
            header("Location: ?menu1op=submenu_calificable&submenuop=calf_lista&editar_calificable=Crear Respuesta&id_materia_periodo_lectivo=$id_materia_periodo_lectivo&id_calificable=$id_calificable&id_grupo_pregunta=$id_grupo_pregunta&id_pregunta=$id_pregunta");
         }
      }
   }
   $sContenido .= $oContenido->generarFormularioInsert("CREAR_RESPUESTAS", $_POST);
   return $sContenido;
}

// Crear calificable_respuesta
function mostrar_formulario_modificar_calificable_respuesta(&$oDB, &$tpl, &$_GET, &$_POST){
   $id_materia_periodo_lectivo = recoger_valor("id_materia_periodo_lectivo",$_GET,$_POST);
   $id_calificable = recoger_valor("id_calificable",$_GET,$_POST);
   //$id_grupo_pregunta = recoger_valor("id_grupo_pregunta",$_GET,$_POST);
   //$id_pregunta = recoger_valor("id_pregunta",$_GET,$_POST);
   $id_respuesta = recoger_valor("id_respuesta",$_GET,$_POST);

   $sContenido="";
   $oContenido=new ul_calificable_respuesta(
      $oDB,
      $tpl,
      "?menu1op=submenu_calificable&submenuop=calf_lista&editar_calificable=Editar&id_materia_periodo_lectivo=$id_materia_periodo_lectivo&id_calificable=$id_calificable",
      array_merge($_GET,$_POST));

   // Verificar si se desean guardar cambios
   $tuplaForm = $oContenido->deducirFormulario($_POST);
   if (is_array($tuplaForm)) {
      if ($tuplaForm[0] == "UPDATE") {
         $bExito = $oContenido->manejarFormularioUpdate($tuplaForm[1], $_POST);
         if(!$bExito)
            $sContenido .= $tpl->crearAlerta(
               "error",
               "Problema al Modificar Calificable_Respuesta",
               "Al modificar calificable: ".$oContenido->getMessage());
         else
            header("Location: ?menu1op=submenu_calificable&submenuop=calf_lista&editar_calificable=Editar&id_materia_periodo_lectivo=$id_materia_periodo_lectivo&id_calificable=$id_calificable");
      }
   }
   $sContenido .= $oContenido->generarFormularioUpdate("MODIFICAR_RESPUESTAS", $_POST,array("id_respuesta"=>$id_respuesta));
   return $sContenido;
}

function mostrar_formulario_eliminar_calificable_grupo_pregunta($oDB,$oPlantillas,$_GET,$_POST)
{
   $error='';
   $id_materia_periodo_lectivo = recoger_valor("id_materia_periodo_lectivo",$_GET,$_POST);
   $id_calificable = recoger_valor("id_calificable",$_GET,$_POST);
   $id_grupo_pregunta = recoger_valor("id_grupo_pregunta",$_GET,$_POST);

   // verificamos que borramos los datos correctos
   $sQuery="SELECT u_g.orden FROM ul_calificable u_c, ul_grupo_pregunta u_g, ul_pregunta u_p WHERE u_c.id_calificable=$id_calificable AND u_c.id_materia_periodo_lectivo=$id_materia_periodo_lectivo AND u_g.id_calificable=$id_calificable AND u_g.id_grupo_pregunta=$id_grupo_pregunta";
   $result = $oDB->getFirstRowQuery($sQuery,TRUE);
   if(is_array($result) && count($result)>0){

      $sQuery="UPDATE ul_grupo_pregunta SET orden=orden-1 WHERE orden>".$result['orden'];
      $result = $oDB->genQuery($sQuery);

      if($result===FALSE){
         $error .= "No se pudo actualizar el orden de los grupos. ".$oDB->errMsg;
      }else{
         $sQuery="SELECT id_pregunta FROM ul_pregunta WHERE id_grupo_pregunta='$id_grupo_pregunta'";
         $result = $oDB->fetchTable($sQuery,TRUE);
         if(is_array($result) && count($result)>0){
            foreach($result as $i=>$value){
               $sQuery="DELETE FROM ul_respuesta WHERE id_pregunta=".$value['id_pregunta'];
               $result = $oDB->genQuery($sQuery);

               if($result===FALSE){
                  $error .= "Al eliminar respuestas: ".$oDB->errMsg;
               }
            }
         }

         $sQuery="DELETE FROM ul_pregunta WHERE id_grupo_pregunta=$id_grupo_pregunta";
         $result = $oDB->genQuery($sQuery);

         if($result===FALSE){
            $error .= "Al eliminar las preguntas: ".$oDB->errMsg;
            //$this->setMessage("No se pudieron eliminar las preguntas del grupo seleccionado.");
         }else{
            $sQuery="DELETE FROM ul_grupo_pregunta WHERE id_grupo_pregunta=$id_grupo_pregunta";
            $result = $oDB->genQuery($sQuery);

            if($result===FALSE){
               $error .= "Problema al eliminar el grupo seleccionado: ".$oDB->errMsg;
            }
         }
      }
   }else{
      if($oDB->errMsg)
         $error .= "No se encontró el registro deseado: ".$oDB->errMsg;
      else
         $error .= "La información ingresada no es correcta ";
   }

   if($error!='')
      $error = $oPlantillas->crearAlerta(
            "error",
            "Problemas al Eliminar el Grupo Seleccionado",
            $error);
   return $error.mostrar_formulario_vista_edicion($oDB,$oPlantillas,$_GET,$_POST);
}

function mostrar_formulario_eliminar_calificable_pregunta($oDB,$oPlantillas,$_GET,$_POST)
{
   $error="";
   $id_materia_periodo_lectivo = recoger_valor("id_materia_periodo_lectivo",$_GET,$_POST);
   $id_calificable = recoger_valor("id_calificable",$_GET,$_POST);
   $id_pregunta = recoger_valor("id_pregunta",$_GET,$_POST);

   // verificamos que borramos los datos correctos
   $sQuery="SELECT u_p.orden FROM ul_calificable u_c, ul_grupo_pregunta u_g, ul_pregunta u_p WHERE u_c.id_calificable=$id_calificable AND u_c.id_materia_periodo_lectivo=$id_materia_periodo_lectivo AND u_g.id_calificable=$id_calificable AND u_p.id_grupo_pregunta=u_g.id_grupo_pregunta AND u_p.id_pregunta=$id_pregunta";
   $result = $oDB->getFirstRowQuery($sQuery,TRUE);
   if(is_array($result) && count($result)>0){

      $sQuery="UPDATE ul_pregunta SET orden=orden-1 WHERE orden>".$result['orden'];
      $result = $oDB->genQuery($sQuery);

      if($result===FALSE){
         $error .= "No se pudo actualizar el orden de las preguntas. ".$oDB->errMsg;
         //$this->setMessage("No se pudo eliminar la pregunta seleccionada.");
      }else{
         $sQuery="DELETE FROM ul_respuesta WHERE id_pregunta=$id_pregunta";
         $result = $oDB->genQuery($sQuery);

         if($result===FALSE){
            $error .= "No se eliminaron las respuestas. ".$oDB->errMsg;
         }

         $sQuery="DELETE FROM ul_pregunta WHERE id_pregunta=$id_pregunta";
         $result = $oDB->genQuery($sQuery);

         if($result===FALSE){
            $error .= "No se eliminó la pregunta seleccionada".$oDB->errMsg;
         }
      }
   }else{
      $error .= "La información ingresada no es correcta";
   }

   if($error!='')
      $error = $oPlantillas->crearAlerta(
            "error",
            "Problemas al Eliminar la Pregunta Seleccionada",
            $error);

   return $error.mostrar_formulario_vista_edicion($oDB,$oPlantillas,$_GET,$_POST);
}

function mostrar_formulario_eliminar_calificable_respuesta($oDB,$oPlantillas,$_GET,$_POST)
{
   $error='';
   $id_materia_periodo_lectivo = recoger_valor("id_materia_periodo_lectivo",$_GET,$_POST);
   $id_calificable = recoger_valor("id_calificable",$_GET,$_POST);
   $id_respuesta = recoger_valor("id_respuesta",$_GET,$_POST);

   // verificamos que borramos los datos correctos
   $sQuery="SELECT u_r.orden FROM ul_calificable u_c, ul_grupo_pregunta u_g, ul_pregunta u_p, ul_respuesta u_r WHERE u_c.id_calificable=$id_calificable AND u_c.id_materia_periodo_lectivo=$id_materia_periodo_lectivo AND u_g.id_calificable=$id_calificable AND u_p.id_grupo_pregunta=u_g.id_grupo_pregunta AND u_r.id_pregunta=u_p.id_pregunta AND u_r.id_respuesta=$id_respuesta";
   $result = $oDB->getFirstRowQuery($sQuery,TRUE);
   if(is_array($result) && count($result)>0){

      $sQuery="UPDATE ul_respuesta SET orden=orden-1 WHERE orden>".$result['orden'];
      $result = $oDB->genQuery($sQuery);

      if($result===FALSE){
         $error.="No se pudo actualizar el orden de las respuestas.".$oDB->errMsg;
      }

      $sQuery="DELETE FROM ul_respuesta WHERE id_respuesta=$id_respuesta";
      $result = $oDB->genQuery($sQuery);

      if($result===FALSE){
         $error.="No se pudo eliminar la respuesta seleccionada.".$oDB->errMsg;
      }

   }else{
      $error.="La información ingresada no es correcta.".$oDB->errMsg;
   }

   if($error!='')
      $error = $oPlantillas->crearAlerta(
            "error",
            "Problemas al Eliminar la Respuesta Seleccionada",
            $error);
   return $error.mostrar_formulario_vista_edicion($oDB,$oPlantillas,$_GET,$_POST);
}

?>
