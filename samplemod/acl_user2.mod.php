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
// | Autores: Alex Villacis  <a_villacis@palosanto.com                    |
// |          Otro           <alguien@example.com>                        |
// +----------------------------------------------------------------------+
//
// $Id: acl_user2.mod.php,v 1.1.1.1 2006/03/03 21:59:09 ainiguez Exp $

require_once "conf/default.conf.php";
require_once "lib/paloTemplate.class.php";
require_once "lib/paloACL.class.php" ;
require_once "modules/acl_user.class.php";

$tpl->assign("CONTENT", _moduleContent($pDB, $_GET, $_POST));

function _moduleContent(&$pDB, &$_GET, &$_POST)
{
    global $config; // definda en conf/default.conf.php
    $sSeccion = "lista_usuarios";
    $sContenido = "";

    $oPlantillas =& new paloTemplate("skins/".$config->skin);
    $oPlantillas->definirDirectorioPlantillas("acl_manip");
    $oPlantillas->assign("IMG_PATH", "skins/default/images");

    // Verificar qué operación se pide en el módulo
    if (isset($_POST["seccion"])) {
        $sSeccion = $_POST["seccion"];
    } else if (isset($_GET["seccion"])) {
        $sSeccion = $_GET["seccion"];
    }

    // Ejecutar operación según la opción elegida
    switch ($sSeccion) {
    case "modificar_usuario":
        $sContenido = _acl_user_ModificarUsuario($oPlantillas, $pDB, $_GET, $_POST);
        break;
    case "membresia":
        break;
    case "permisos":
        break;
    case "password_usuario":
        $sContenido = _acl_user_ModificarPassword($oPlantillas, $pDB, $_GET, $_POST);
        break;
    case "lista_usuarios":
    default:
        $sContenido = _acl_user_ListarUsuarios($oPlantillas, $pDB, $_GET, $_POST);
        break;
    }
    return $sContenido;
}

// Construir la tabla que lista los usuarios, remover usuario si se indica botón
function _acl_user_ListarUsuarios(&$oPlantillas, &$pDB, &$_GET, &$_POST)
{
    $sContenido = "";
    $oACL =& new paloACL($pDB);

    // Listar usuarios de ACLs
    $arr_users = $oACL->getUsers();
    $arr_cabeceras = array(
        "<input type=\"submit\" class=\"mi_submit\" name=\"in_remover\" value=\"Remover\" title=\"Remover los usuarios seleccionados del sistema\">",
        "Nombre",
        "Descripcion",
        "Opciones");
    $arr_contenido = array();
    foreach ($arr_users as $tupla_user) {
        $arr_contenido[] = array(
            "<div align=\"center\"><input type=\"radio\" class=\"mi_input\" name=\"in_id_usuario\" value=\"".$tupla_user[0]."\"></div>",
            $tupla_user[1],
            $tupla_user[2],
            "<a href=\"?seccion=membresia&id_user=".$tupla_user[0]."\">Membres&iacute;a</a>&nbsp;".
            "<a href=\"?seccion=permisos&id_user=".$tupla_user[0]."\">Permisos</a>&nbsp;".
            "<a href=\"?seccion=modificar_usuario&id_user=".$tupla_user[0]."\">Modificar usuario</a>&nbsp;".
            "<a href=\"?seccion=password_usuario&id_user=".$tupla_user[0]."\">Cambiar password</a>&nbsp;"
            );
    }
    $sContenido = $oPlantillas->crearTabla(
        $arr_cabeceras,
        $arr_contenido,
        "Usuarios autorizados en el sistema<br>\n".
        "<a href=\"?seccion=modificar_usuario\">Crear nuevo usuario&nbsp;&raquo;</a>");
    return $sContenido;
}

// Modificar la clave de acceso de un usuario
function _acl_user_ModificarPassword($oPlantillas, $pDB, $_GET, $_POST)
{
    $sContenido = "";

    if (isset($_GET["id_user"])) {
        $id_user = $_GET["id_user"];
    } else {
        Header("Location: ?seccion=lista_usuarios");
    }
    $oEntidad_acl_user =& new acl_user($pDB, $oPlantillas);

    // Verificar si se desean guardar cambios
    $tuplaForm = $oEntidad_acl_user->deducirFormulario($_POST);
    if (is_array($tuplaForm)) {
        if ($tuplaForm[0] == "UPDATE") {
            $bExito = $oEntidad_acl_user->manejarFormularioUpdate($tuplaForm[1], $_POST);
        }
        // Mostrar mensaje de error en caso de fallo
        if ($bExito) {
            Header("Location: ?seccion=lista_usuarios");
        } else {
            $sContenido .= $oPlantillas->crearAlerta(
                "error",
                "Problema en actualizaci&oacute;n de clave de acceso de usuario",
                "Al cambiar clave: ".$oEntidad_acl_user->getMessage());
        }
    }
    $sContenido .= $oEntidad_acl_user->generarFormularioUpdate("PasswordUsuarioACL", $_POST,
        array("id" => $id_user));

    return $sContenido;
}

// Crear usuario nuevo o modificar usuario existente
function _acl_user_ModificarUsuario(&$oPlantillas, &$pDB, &$_GET, &$_POST)
{
    $sContenido = "";
    if (isset($_GET["id_user"])) {
        $id_user = $_GET["id_user"];
    } else {
        $id_user = NULL;
    }
    $oEntidad_acl_user =& new acl_user($pDB, $oPlantillas);
    $oACL =& new paloACL($pDB);

    // Verificar si se desean guardar cambios
    $tuplaForm = $oEntidad_acl_user->deducirFormulario($_POST);
    if (is_array($tuplaForm)) {
        $bExito = FALSE;
        if ($tuplaForm[0] == "INSERT") {
            $bExito = $oEntidad_acl_user->manejarFormularioInsert($tuplaForm[1], $_POST);
        } else if ($tuplaForm[0] == "UPDATE") {
            $bExito = $oEntidad_acl_user->manejarFormularioUpdate($tuplaForm[1], $_POST);
        }

        // Mostrar mensaje de error en caso de fallo
        if ($bExito) {
            Header("Location: ?seccion=lista_usuarios");
        } else {
            $sContenido .= $oPlantillas->crearAlerta(
                "error",
                "Problema en actualizaci&oacute;n de usuario",
                "Al ingresar usuario: ".$oEntidad_acl_user->getMessage());
        }
    }
    if (is_null($id_user)) {
        $sContenido .= $oEntidad_acl_user->generarFormularioInsert("UsuarioACL", $_POST);
    } else {
        $sContenido .= $oEntidad_acl_user->generarFormularioUpdate("UsuarioACL", $_POST,
            array("id" => $id_user));
    }

    return $sContenido;
}
?>
