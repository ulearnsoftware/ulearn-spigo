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
// $Id: rec_lista.mod.php,v 1.1.1.1 2006/03/03 21:59:09 ainiguez Exp $

require_once ("conf/default.conf.php");
require_once ("lib/paloTemplate.class.php");
require_once ("lib/paloACL.class.php");
require_once ("modules/ul_recurso.class.php");
require_once ("modules/ul_recurso_reporte.class.php");

$tpl->assign("CONTENT", _moduleContent($pDB, $_GET, $_POST));

function _moduleContent(&$pDB, &$_GET, &$_POST)
{
	global $config; // definda en conf/default.conf.php

   $oPlantillas =& new paloTemplate("skins/".$config->skin);
   $oPlantillas->definirDirectorioPlantillas("");
   $oPlantillas->assign("IMG_PATH", "skins/$config->skin/images");

   $id_parent=recoger_valor("id_parent",$_GET,$_POST);
   $id_materia_periodo_lectivo=recoger_valor("id_materia_periodo_lectivo",$_GET,$_POST);
   $sAccion=recoger_valor("action",$_GET,$_POST,"");

   if($id_parent!=NULL)
		if(!ereg("^[[:digit:]]+$",$id_parent))
             return $oPlantillas->crearAlerta("error","Error","No se ha recibido un valor válido de id recurso");
   if($id_materia_periodo_lectivo!=NULL)
		if(!ereg("^[[:digit:]]+$",$id_materia_periodo_lectivo))
			return $oPlantillas->crearAlerta("error","Error","No se ha recibido un valor válido de id materia");

   ///////Se setea la accion si el permiso es correspondiente
   $oACL=getACL();

   if(isset($_POST['reemplazar']) && $oACL->isUserAuthorized($_SESSION['session_user'], 'updat', 'rec_lista'))
		$sAccion='reemplazar';
   if(isset($_POST['renombrar']) && $oACL->isUserAuthorized($_SESSION['session_user'], 'updat', 'rec_lista'))
		$sAccion='renombrar';
   if(isset($_POST['eliminar']) && $oACL->isUserAuthorized($_SESSION['session_user'], 'delet', 'rec_lista'))
		$sAccion='eliminar';
   if(isset($_POST['subir_archivo']) && $oACL->isUserAuthorized($_SESSION['session_user'], 'creat', 'rec_lista'))
		$sAccion='subir_archivo';
   if(isset($_POST['crear_directorio']) && $oACL->isUserAuthorized($_SESSION['session_user'], 'creat', 'rec_lista'))
		$sAccion="crear_directorio";
   ////Si el id_materia_periodo_lectivo no es NULL se crea un objeto Reporte_recurso
   if($id_materia_periodo_lectivo!=NULL)
		$oReporte_recurso = &  new ul_recurso_reporte($pDB, $oPlantillas,"?menu1op=submenu_recursos&submenuop=rec_lista&id_materia_periodo_lectivo=$id_materia_periodo_lectivo",$id_materia_periodo_lectivo,$id_parent);


   // Ejecutar accion segun la opcion elegida
   $sCodigoTabla = "";

   switch ($sAccion) {
	// necesitan seleccionar archivo o directorio
	case "reemplazar":
		if(isset($oReporte_recurso)){
			if(isset($_POST['in_recurso'])){
				return mostrar_formulario_reemplazar_archivo($pDB,$oPlantillas,$_GET,$_POST,$id_parent,$id_materia_periodo_lectivo,$_POST['in_recurso']);
			}
			else
				$sCodigoTabla.=$oPlantillas->crearAlerta("error", "Error", "No se ha seleccionado un id_recurso");
			$sCodigoTabla .=  $oReporte_recurso->generarReporte("LISTA_RECURSOS",$_GET,$_POST);
		}
                      break;
	case "renombrar":
   	if(isset($oReporte_recurso)){
      	if(isset($_POST['in_recurso'])){
         	return mostrar_formulario_renombrar_archivo($pDB,$oPlantillas,$_GET,$_POST,$id_parent,$id_materia_periodo_lectivo,$_POST['in_recurso']);
			}
			else
         	$sCodigoTabla.=$oPlantillas->crearAlerta("error", "Error", "No se ha seleccionado un id_recurso");
   			$sCodigoTabla .=  $oReporte_recurso->generarReporte("LISTA_RECURSOS",$_GET,$_POST);
		}
   	break;

   case "desactivar":
         if(isset($oReporte_recurso)){
            if(isset($_GET ['in_recurso'])){
               $bValido=$oReporte_recurso->desactivar_recurso($_GET['in_recurso']);
                  if(!$bValido)
                     $sCodigoTabla.=$oPlantillas->crearAlerta("error", "Error al desactivar",$oReporte_recurso->getMessage());
            }


           $sCodigoTabla .=  $oReporte_recurso->generarReporte("LISTA_RECURSOS",$_GET,$_POST);
         }

         break;

   case "activar":
         if(isset($oReporte_recurso)){
            if(isset($_GET ['in_recurso'])){
               $bValido=$oReporte_recurso->activar_recurso($_GET['in_recurso']);
                  if(!$bValido)
                     $sCodigoTabla.=$oPlantillas->crearAlerta("error", "Error al Activar",$oReporte_recurso->getMessage());
            }


           $sCodigoTabla .=  $oReporte_recurso->generarReporte("LISTA_RECURSOS",$_GET,$_POST);
         }

         break;


   case "eliminar":
   	if(isset($oReporte_recurso)){
   		if(isset($_POST['in_recurso'])){
   			$bValido=$oReporte_recurso->eliminar_recurso($_POST['in_recurso']);
				if(!$bValido){
      			$sCodigoTabla.=$oPlantillas->crearAlerta("error", "Al efectuar operación de Eliminación", $oReporte_recurso->getMessage());
				}
			}
			else
         	$sCodigoTabla.=$oPlantillas->crearAlerta("error", "Error al eliminar", "No se ha seleccionado un id_recurso");

			$sCodigoTabla .=  $oReporte_recurso->generarReporte("LISTA_RECURSOS",$_GET,$_POST);
		}
      break;
   // no necesitan seleccionar archivo o directorio
    case "subir_archivo":
   	if(isset($oReporte_recurso)){
      	     if(isset($id_parent)){
         	return mostrar_formulario_subir_archivo($pDB,$oPlantillas,$_GET,$_POST,$id_parent,$id_materia_periodo_lectivo);
	     }
	}
        break;
     case "crear_directorio":
   	if(isset($oReporte_recurso)){
      	     if(isset($id_parent)){
         	return mostrar_formulario_crear_directorio($pDB,$oPlantillas,$_GET,$_POST,$id_parent,$id_materia_periodo_lectivo);
	     }
	}
      break;
   case "listar":

   default:
   	if(isset($oReporte_recurso)){
      	$sCodigoTabla .=  $oReporte_recurso->generarReporte("LISTA_RECURSOS",$_GET,$_POST);
	   }
	}
   return $sCodigoTabla;
}

function mostrar_formulario_subir_archivo(&$oDB,&$tpl,&$_GET,&$_POST,$id_parent,$id_materia_periodo_lectivo){
   $sContenido="";
   $oRecurso=new ul_recurso($oDB,$tpl,$id_parent,$id_materia_periodo_lectivo);

	// Verificar si se desean guardar cambios
   $tuplaForm = $oRecurso->deducirFormulario($_POST);
   if (is_array($tuplaForm)) {
   	if ($tuplaForm[0] == "INSERT") {
      	$bExito = $oRecurso->manejarFormularioInsert($tuplaForm[1], $_POST);
	      if($bExito)
	      	header("Location: ?menu1op=submenu_recursos&submenuop=rec_lista&id_materia_periodo_lectivo=$id_materia_periodo_lectivo&id_parent=$id_parent");
	      else{
         	$sContenido .= $tpl->crearAlerta(
            	"error",
            	"Problema al Subir Archivo",
            	"Al subir archivo: ".$oRecurso->getMessage());
	      }
   	}
	}
	$sContenido .= $oRecurso->generarFormularioInsert("SUBIR_ARCHIVO", $_POST);
	return $sContenido;
}




function mostrar_formulario_crear_directorio(&$oDB,&$tpl,&$_GET,&$_POST,$id_parent,$id_materia_periodo_lectivo){
	$sContenido="";
	$oRecurso=new ul_recurso($oDB,$tpl,$id_parent,$id_materia_periodo_lectivo);

   // Verificar si se desean guardar cambios
   $tuplaForm = $oRecurso->deducirFormulario($_POST);
   if (is_array($tuplaForm)) {
		if ($tuplaForm[0] == "INSERT") {
			$bExito = $oRecurso->manejarFormularioInsert($tuplaForm[1], $_POST);
			if($bExito)
				header("Location: ?menu1op=submenu_recursos&submenuop=rec_lista&id_materia_periodo_lectivo=$id_materia_periodo_lectivo&id_parent=$id_parent");
			else{
				$sContenido .= $tpl->crearAlerta(
					"error",
					"Problema al Crear Directorio",
					"Al Crear Directorio: ".$oRecurso->getMessage());
			}
		}
	}
	$sContenido .= $oRecurso->generarFormularioInsert("CREAR_DIRECTORIO", $_POST);
	return $sContenido;
}



function mostrar_formulario_reemplazar_archivo($oDB,$tpl,$_GET,$_POST,$id_parent,$id_materia_periodo_lectivo,$id_recurso){
	$sContenido="";
	$oRecurso=new ul_recurso($oDB,$tpl,$id_parent,$id_materia_periodo_lectivo,$id_recurso);
   // Verificar si se desean guardar cambios
   $tuplaForm = $oRecurso->deducirFormulario($_POST);
   if (is_array($tuplaForm)) {
   	if ($tuplaForm[0] == "UPDATE") {
      	$bExito = $oRecurso->manejarFormularioUpdate($tuplaForm[1], $_POST);
	      if($bExito)
	      	header("Location: ?menu1op=submenu_recursos&submenuop=rec_lista");
	      else{
         	$sContenido .= $tpl->crearAlerta(
            	"error",
               "Problema al Subir Archivo",
               "Al subir archivo: ".$oRecurso->getMessage());
	      }
		}
   }
	$sContenido .= $oRecurso->generarFormularioUpdate("REEMPLAZAR_ARCHIVO", $_POST,array('id_recurso'=>$id_recurso));
	return $sContenido;
}


function mostrar_formulario_renombrar_archivo($oDB,$tpl,$_GET,$_POST,$id_parent,$id_materia_periodo_lectivo,$id_recurso){
	$sContenido="";
	$oRecurso=new ul_recurso($oDB,$tpl,$id_parent,$id_materia_periodo_lectivo,$id_recurso);

   // Verificar si se desean guardar cambios
   $tuplaForm = $oRecurso->deducirFormulario($_POST);
   if (is_array($tuplaForm)) {
   	if ($tuplaForm[0] == "UPDATE") {
      	$bExito = $oRecurso->manejarFormularioUpdate($tuplaForm[1], $_POST);
	      if($bExito)
	      	header("Location: ?menu1op=submenu_recursos&submenuop=rec_lista&id_materia_periodo_lectivo=$id_materia_periodo_lectivo&id_parent=$id_parent");
	      else{
         	$sContenido .= $tpl->crearAlerta(
            	"error",
             "Problema al Renombrar Archivo",
             "Al renombrar archivo: ".$oRecurso->getMessage());
	      }
   	}
   }
	$sContenido .= $oRecurso->generarFormularioUpdate("RENOMBRAR_ARCHIVO", $_POST,array("id_recurso"=>$id_recurso));
	return $sContenido;
}


?>
