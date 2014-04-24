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
// | Autores: Edgar Landivar <e_landivar@palosanto.com                    |
// |          Otro           <alguien@example.com>                        |
// +----------------------------------------------------------------------+
//
// $Id: acl_user.mod.php,v 1.1.1.1 2006/03/03 21:59:09 ainiguez Exp $

//function _moduleContent($arrPost) 
//{
//    if(is_array($arrPost)) {
//        foreach($arrPost as $varName => $varVal) {
//            $$varName = $varVal;
//        }
//    }

    require_once("lib/paloACL.class.php");
//    $dsn = "mysql://root:ulises31@localhost/stickgate"; // esto esta aqui temporalmente... no debe ir aqui...
$dsn =& $config->dsn;
/*
    print "<pre>\n";
    print_r ($_POST);
    print "\n</pre>\n";
*/
    $boxaction = "";
    if(isset($_GET["boxaction"])) $boxaction = $_GET["boxaction"];
    if(isset($_POST["boxaction"])) $boxaction = $_POST["boxaction"];
    if(isset($_POST["add_permission"])) $boxaction='permisos';
    if(isset($_POST["del_permission"])) $boxaction='permisos';

    $id_user = NULL;
    if (isset($_GET["id_user"])) $id_user = $_GET["id_user"];
    if (isset($_POST["id_user"])) $id_user = $_POST["id_user"];

    $id_action = NULL;
    if (isset($_GET["id_action"])) $id_action = $_GET["id_action"];
    if (isset($_POST["id_action"])) $id_action = $_POST["id_action"];

    $id_resource = NULL;
    if (isset($_GET["id_resource"])) $id_resource = $_GET["id_resource"];
    if (isset($_POST["id_resource"])) $id_resource = $_POST["id_resource"];


    if($boxaction=='permisos') {
        if(isset($_POST["add_permission"])) { $acl->addUserPermission($id_user, $id_action, $id_resource); }
        if(isset($_POST["del_permission"])) { $acl->delUserPermission($id_action); }

        $arrRecursos   = $acl->getResources();
        $comboRecursos = combo2($arrRecursos, "");

        $arrActions    = $acl->getActions();
        $comboActions  = combo2($arrActions, "");

        $arr_permission = $acl->getArrayPermissions($id_user);

        $tabla_exterior = "<table cellspacing=1 cellpadding=4 bgcolor=#666666><tr><td bgcolor=#cccccc>
                            Recurso</td><td bgcolor=#cccccc>Accion(es)</td></tr><tr><td bgcolor=#ffffff>
                            &nbsp;</td><td bgcolor=#ffffff><input type=submit name=del_permission value='Remover
                            permiso de usuario'></td></tr>";
        foreach ($arr_permission as $k => $v) {
            $tabla_interior = "<table border=1>";
            foreach($v as $k2 => $v2) {
                // crear tabla interior de una sola columna
                $tabla_interior .= "<tr><td>";
                if(ereg("^u", $k2)) {
                    $id_action = str_replace("u", "", $k2); // debo cambiar esta funcion por una mas eficiente que elimine el primer char
                    $tabla_interior .= "<input type=radio name=id_action value=$id_action>";
                }
                $tabla_interior .= "</td><td>$v2</td></tr>";
            }
            $tabla_interior .= "</table>";
            $tabla_exterior .= "<tr><td bgcolor=#ffffff>$k</td><td bgcolor=#ffffff>$tabla_interior</td></tr>";
            
            // meter la tabla interior de una sola columna en la celda de la tabla contenedora
        }
        $tabla_exterior .= "</table>";
        $boton_nuevo_permiso = "
                        <select name=id_action>
                            $comboActions
                        </select>
                        <select name=id_resource>
                            $comboRecursos
                        </select>
                        <input type=submit class=mi_submit name=add_permission value='Ingresar permiso'><input type=hidden 
                        name=id_user value=$id_user><br><br>";
     
        $contenido = "<form method=\"POST\">" . $boton_nuevo_permiso . $tabla_exterior . "</form>";
        $tpl->assign("CONTENT", $contenido);
    
    } else {
    
        $arr_users = $acl->getUsers();
    
        foreach ($arr_users as $v) {
            $arrResultado[] = array($v[1], $v[2], "Gruposh", "<a href='?boxaction=membresia&id_user=" . $v[0]. "'>Membresia
                                </a>&nbsp;<a href='?boxaction=permisos&id_user=" . $v[0] . "'>Permisos</a>");
        }
    
        $arr_headers = array("Nombre", "Descripcion", "Grupo(s)", "Opciones");
  //      return $tpl->crearTabla($arr_headers, $arrResultado);
        $variablevaga = $tpl->crearTabla($arr_headers, $arrResultado);
        $tpl->assign("CONTENT", $variablevaga);
        
    }
//}

//$contenido_tabla = _moduleContent($_POST);
//$tpl->assign("CONTENT", $contenido_tabla);
?>
