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
// $Id: rec_opciones.mod.php,v 1.1.1.1 2006/03/03 21:59:09 ainiguez Exp $

require_once ("conf/default.conf.php");
require_once ("lib/paloTemplate.class.php");
require_once ("lib/paloACL.class.php");
require_once ("modules/ul_recurso.class.php");

$tpl->assign("CONTENT", _moduleContent($pDB, $_GET, $_POST));

function _moduleContent(&$pDB, &$_GET, &$_POST)
{
	global $config; // definda en conf/default.conf.php

   $oPlantillas =& new paloTemplate("skins/".$config->skin);
   $oPlantillas->definirDirectorioPlantillas("");
   $oPlantillas->assign("IMG_PATH", "skins/$config->skin/images");
   $id_materia_periodo_lectivo=recoger_valor("id_materia_periodo_lectivo",$_GET,$_POST);

     // Ejecutar accion segun la opcion elegida
   $sCodigoTabla = "";

   $oRecurso=new ul_recurso($pDB,$oPlantillas,'',$id_materia_periodo_lectivo);
   ///////Se ejecutan las acciones si el permiso es correspondiente a administrador
   $oACL=getACL();

      if (isset($_POST['actualizar']) OR isset($_POST['sincronizar'])){
         if($oACL->isUserAuthorized($_SESSION['session_user'], 'admin', 'rec_opciones')){

            if(isset($_POST['actualizar'])){
               $bValido=$oRecurso->actualizar_DB();
                  if(!$bValido)
                     $sCodigoTabla.=$oPlantillas->crearAlerta("error", "Al actualizar DB","Error al actualizar DB:".$oRecurso->getMessage());
                  else
                     $sCodigoTabla.=$oPlantillas->crearAlerta("!", "Al actualizar DB","Se han cargado exitosamente los archivos del disco duro en la base de datos.");
            }

            if(isset($_POST['sincronizar'])){
               $bValido=$oRecurso->sincronizar_DB();
                  if(!$bValido)
                     $sCodigoTabla.=$oPlantillas->crearAlerta("error", "Al sincronizar DB","Error al sincronizar DB:".$oRecurso->getMessage());
                  else
                     $sCodigoTabla.=$oPlantillas->crearAlerta("!", "Al sincronizar DB","Se ha actualizado exitosamente la base de datos con respecto a los archivos del disco duro");
            }
         }
         else
            $sCodigoTabla.=$oPlantillas->crearAlerta("error", "Error","Usted no está autorizado para realizar esta acción.");
      }



//Se genera la tabla para mostrar los botones
   $sCodigoTabla .= "<form method='POST'>".
                     "<table align='center' border=0 cellspacing=1 width=250 bgcolor='#000000'>".
                     "<tr><td aling=center class='table_title_row'><div class=letra_12 align=center><b>Opciones de Administración de Recursos</b></div></td><tr>".
                     "<tr><td align=center class='table_data'><br><input class='mi_submit' type='submit' name='actualizar' value='Actualizar BD'><br>&nbsp;</td></tr>".
                     "<tr><td align=center class='table_data'><br><input class='mi_submit' type='submit' name='sincronizar' value='Sincronizar BD'><br>&nbsp;</td></tr>".
                     "</table>".
                     "</form>";


   return $sCodigoTabla;
}


?>
