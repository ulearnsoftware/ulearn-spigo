<?php
/* vim: set expandtab tabstop=4 softtabstop=4 shiftwidth=4: */
// Codificación: UTF-8 (el siguiente texto es chino: 給懶惰使用者)
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
// | la ley sin saberlo.
// +----------------------------------------------------------------------+
// | Autores: Edgar Landivar <e_landivar@palosanto.com>                   |
// |          Otro           <otro@example.com>                           |
// +----------------------------------------------------------------------+
//
// $Id: index.php,v 1.1.1.1 2006/03/03 21:59:08 ainiguez Exp $

session_name("ulearn");
session_start();


header("Content-Type: text/html; charset=UTF-8");
require_once("conf/default.conf.php");

//require_once("conf/definitions.php");
require_once("lib/misc.lib.php");
require_once("lib/paloTemplate.class.php");
require_once("lib/paloDB.class.php");
require_once("lib/paloACL.class.php");
require_once("lib/paloXML.class.php");
require_once("modules/ul_materia_periodo_lectivo.class.php");


// La siguiente variable lleva la cuenta del objeto de ACL a usar
$acl = NULL;
function & getACL() { global $acl; return $acl; }

// La siguiente variable está puesta a VERDADERO por omisión. Se asigna a FALSO
// si al script le interesa desactivar el FASTPRINT al final para proveer su
// propia salida al browser.
$gbHabilitarSalida = TRUE;
function getHabilitarSalida()   { global $gbHabilitarSalida; return $gbHabilitarSalida; }
function setHabilitarSalida($b) { global $gbHabilitarSalida; $gbHabilitarSalida = ($b) ? TRUE : FALSE; }

$tpl = new PaloTemplate("skins/$conf->skin");
$dsn =& $config->dsn;
$pDB = new paloDB($dsn);

// Decidir si se usa la clase ACL por omisión. Si la configuración especifica
// otro método para autenticar, se usa en vez de la clase ACL del framework
if (isset($config->acl_class)) {
    eval("\$acl = new $config->acl_class(\$pDB);");
} else {
    $acl = new paloACL($pDB);
}

$tpl->definirDirectorioPlantillas("index");

$tpl->assign("ERROR_BOX", $pDB->errMsg);
$tpl->assign("IMG_PATH", "skins/$conf->skin/images");
$tpl->assign("SKIN",$conf->skin);
$tpl->assign("PROJECT_NAME", $config->nombre_proyecto);

// ==========================================
// Aqui deberia manejar el login, en caso de que se de este evento
// En el caso de que el usuario haga logout lo unico que tendria que hacer
// no es destruir la sesion sino desregistrar el nombre y password del usuario
// Lo siguiente se ejecutara solo en el caso de que un cliente trate de hacer logon por primera vez
/*
print "<pre>\n";print_r($_POST);print "</pre>\n";
print "<pre>\n";print_r($_SESSION);print "</pre>\n";
*/

if(isset($_POST["submit_login"]) &&
    $acl->authenticateUser($_POST["input_user"], $_POST["input_pass"])) {

    // Registro el login y pass en la sesion en curso
    $session_user = $_POST["input_user"];
    $session_pass = $_POST["input_pass"];
    session_register("session_user", "session_pass");
    $_SESSION["session_user"] = $session_user;
    $_SESSION["session_pass"] = $session_pass;
    $_GET['logout']="";
    unset($_GET['logout']);
} else {
    // Verificar si se ha intentado la entrada, pero la autenticación ha fallado.
    if (isset($_POST["submit_login"])) {
        if ($acl->errMsg != "") {
            // Mostrar error de DB
            $tpl->assign("ERROR_BOX", $acl->errMsg);
        } else {
            // Mostrar error de autenticación
            $tpl->assign("ERROR_BOX", "Usuario o contrase&ntilde;a no v&aacute;lidos");
        }
    } elseif (isset($_SESSION["session_user"]) && isset($_SESSION["session_pass"]) &&
        $acl->authenticateUser($_SESSION["session_user"], $_SESSION["session_pass"])) {
        if(isset($_GET["logout"]) && $_GET["logout"] == 1) {
            $_SESSION["session_user"] = $conf->default_user;
            $_SESSION["session_pass"] = "";
        }
    } else {
        $_SESSION["session_user"] = $conf->default_user;
        $_SESSION["session_pass"] = ""; // no se si esto tenga sentido... creo que si... asi no se guarda en la session un password que ya hizo logout
    }
}

if($_SESSION["session_user"]==$conf->default_user) {
    $tpl->parse("PRINCIPAL", "tpl_login");

} else if ($_SESSION["session_user"]!=$conf->default_user) {
    // Aqui se llama a la funcion que devuelve la tabla de seleccion combo materias

    $id_materia_periodo_lectivo=recoger_valor("id_materia_periodo_lectivo",$_GET,$_POST);
      if(!ereg("^[0-9]+$",$id_materia_periodo_lectivo))  ///Si no se recibe un id_materia entero se pone en blanco
          $id_materia_periodo_lectivo=NULL;

    $oMateria=new ul_materia_periodo_lectivo($pDB);
    $tabla_seleccion_materias=$oMateria->cabecera_seleccion($_SESSION['session_user'],$_GET,$_POST,$id_materia_periodo_lectivo,$_SERVER['PHP_SELF']);
       ////La funcion cabecera_seleccion setea a NULL el id_materia si no esta en la lista permitida del usuario
       ////asi que se setea la variable POST con ese valor. Esta funcion recibe la variable por referencia
    //$_POST['id_materia_periodo_lectivo']=$id_materia_periodo_lectivo;
    $tpl->assign("SELECCION", $tabla_seleccion_materias);

    ///////Se busca el id del usuario en base al login
    $nombre_usuario="";
    $id_user=$acl->getIdUser($_SESSION['session_user']);
      //Solo si se encontro un id_user
      if($id_user!=FALSE){
         $arr_usuario=$acl->getUsers($id_user);
            if(is_array($arr_usuario) && count($arr_usuario)>0)
               $nombre_usuario="&nbsp; Usuario:&nbsp;".$arr_usuario[0][2];
      }
   session_register("nombre_usuario");
   $tpl->assign("NOMBRE_USUARIO", $nombre_usuario);


    // El sgte. bloque lee el archivo de recursos, parsea el xml y devuelve el arreglo principal de navegacion
    $xmlres = new XML("conf/resources.xml");
    $arr = $xmlres->getPermissionTree("root", $acl, $_SESSION["session_user"]);
    //print_r($arr);
    // ==========================================
    // El sgte. bloque quieren decir que la opcion por default de un menu es siempre la primera que se
    // especifica en el archivo de recursos .xml. Esto lo hago con un each porque el each siempre devuelve
    // el primer par indice=>valor (suponiendo que el apuntador del arreglo este en la primera posicion)
    //
    // Una variable enviada con el mismo nombre que las usadas aqui tiene mas precedencia que si son enviadas serializadas
    // bajo un nombre distinto
    $arr_unserialized = isset($_SESSION["serialdata"]) ? unserialize($_SESSION["serialdata"]) : NULL;
    // las siguientes dos lineas son temporales, hay que hacer algo mas robusto y elegante
    // por ejemplo que pasa si $arr_unserialized si es un arreglo pero no contiene un indice llamado menu1op o submenuop???
    if (!isset($menu1op) && isset($_POST["menu1op"])) $menu1op = $_POST["menu1op"];
    if (!isset($menu1op) && isset($_GET["menu1op"])) $menu1op = $_GET["menu1op"];
    if (!isset($submenuop) && isset($_POST["submenuop"])) $submenuop = $_POST["submenuop"];
    if (!isset($submenuop) && isset($_GET["submenuop"])) $submenuop = $_GET["submenuop"];
    if(!isset($menu1op) and is_array($arr_unserialized)) $menu1op = $arr_unserialized["menu1op"];
    if(!isset($submenuop) and is_array($arr_unserialized)) $submenuop = $arr_unserialized["submenuop"];

    $arr_nivel_1 = $arr["root"]["items"];
    if((!isset($menu1op) and isset($arr_nivel_1)) or (!array_key_exists($menu1op, $arr_nivel_1))) {
        list($menu1op) = each($arr_nivel_1);
    }
    $arr_nivel_2 = $arr["root"]["items"][$menu1op]["items"];
    if((!isset($submenuop) and isset($arr_nivel_2)) or (!array_key_exists($submenuop, $arr_nivel_2))) {
        list($submenuop) = each($arr_nivel_2);
    }
    $_SESSION["serialdata"] = serialize(array("menu1op"=> $menu1op, "submenuop" => $submenuop));
    session_register("serialdata");
    // aqui podria serializar estas dos variables y enviarlas como una sola variable llamada sess_data o algo asi
    // ==========================================
    $logoutText = "<a class='letra_logout' href='" . $_SERVER["PHP_SELF"] . "?logout=1'>Logout&raquo;</a>";
    $menu_str  = $tpl->crearMenu($arr_nivel_1, "100%", $menu1op, "", $_SERVER["PHP_SELF"] . "?id_materia_periodo_lectivo=$id_materia_periodo_lectivo&menu1op=", $logoutText);
    $tpl->assign("MENU",  $menu_str);
    $menu3_str = $tpl->crearMenu($arr_nivel_2, "100%", $submenuop, "_menuh", $_SERVER["PHP_SELF"] . "?id_materia_periodo_lectivo=$id_materia_periodo_lectivo&menu1op=" . $menu1op . "&submenuop=");
    $tpl->assign("MENU3", $menu3_str);
    // ==========================================

    $modulo_nombre  = $arr_nivel_2[$submenuop]["module"];
    $modulo_archivo = "modules/" . $modulo_nombre . ".mod.php";

    if($acl->isUserAuthorized($_SESSION["session_user"], "view", $modulo_nombre)) {
        if(file_exists($modulo_archivo)) {
            //ob_start("recuperar_texto"); // esta linea es para pruebas nomas... estoy probando redirigir el buffer
            include $modulo_archivo;
            //ob_end_flush(); // mmm... al parecer hay ocaciones en que php hace un die cuando ocurre algun error y yo no lo alcanzo
                            // a mostrar porque recien hago un FastPrint al final del script... como soluciono esto?
            //$tpl->assign("CONTENT", $gsTexto);
        } else {
            $cajon_alerta = $tpl->crearAlerta("information", "Alerta", "No se encontro el m&oacute;dulo $modulo_nombre");
            $tpl->assign("CONTENT", $cajon_alerta);
        }
    } else {
          $cajon_alerta = $tpl->crearAlerta("information", "Alerta", "Lo sentimos, usted no esta autorizado para esta acci&oacute;n");
          $tpl->assign("CONTENT", $cajon_alerta);
    }
    //Establece el codigo adicional dentro de la seccion <HEAD>

    $head_code = "";
    if (is_readable("lib/paloFormValidate.js")) {
        $head_code = file_get_contents("lib/paloFormValidate.js");
    }
    $tpl->assign("HEAD_CODE", $head_code);


    //Si el contenido esta vacio se debe cargar la plantilla de bienvenida
    $arr_contenido=$tpl->PARSEVARS;
      if(isset($arr_contenido['CONTENT']) && $arr_contenido['CONTENT']=="")
        $tpl->parse("CONTENT","tpl_bienvenida");
    
    // ==========================================
    $tpl->parse("PRINCIPAL", "tpl_main");
}
if ($gbHabilitarSalida) $tpl->FastPrint("PRINCIPAL");
?>
