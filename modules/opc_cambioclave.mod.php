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
// | Autores: Iv� Ochoa    <iochoa2@hotmail.com>                         |
// +----------------------------------------------------------------------+
//
// $Id: opc_cambioclave.mod.php,v 1.1.1.1 2006/03/03 21:59:09 ainiguez Exp $

require_once ("conf/default.conf.php");
require_once ("lib/paloTemplate.class.php");
require_once ("lib/paloACL.class.php");
require_once ("modules/ul_usuario.class.php");

$tpl->assign("CONTENT", _moduleContent($pDB, $_GET, $_POST));

function _moduleContent(&$pDB, &$_GET, &$_POST)
{
    global $config; // definda en conf/default.conf.php

    $oPlantillas =& new paloTemplate("skins/".$config->skin);
    $oPlantillas->definirDirectorioPlantillas("");
    $oPlantillas->assign("IMG_PATH", "skins/$config->skin/images");
    $oEntidad=new ul_usuario($pDB,$oPlantillas);
    $sContenido="";
    $oACL=getACL();
    $sLoginAlumno = $_SESSION['session_user'];
    $id_acl_user = $oACL->getIdUser($sLoginAlumno);

    // Verificar si se desean guardar cambios
    $tuplaForm = $oEntidad->deducirFormulario($_POST);
    if (is_array($tuplaForm)) {
        if ($tuplaForm[0] == "UPDATE") {
            $bExito = $oEntidad->manejarFormularioUpdate($tuplaForm[1], $_POST);
        }
        // Mostrar mensaje de error en caso de fallo
        if ($bExito) {
            return $sContenido .= $oPlantillas->crearAlerta("!","Al cambiar clave","La clave ha sido cambiada exitosamente");
        } else {
            $sContenido .= $oPlantillas->crearAlerta(
                "error",
                "Problema al cambiar Clave",
                "Al cambiar clave: ".$oEntidad->getMessage());
        }
    }

    $sContenido .= $oEntidad->generarFormularioUpdate("CAMBIO_PASSWORD", $_POST,array('id'=>$id_acl_user));
    return $sContenido;


}

?>
