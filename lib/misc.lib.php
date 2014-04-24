<?php
/* vim: set expandtab tabstop=4 softtabstop=4 shiftwidth=4: */
// Codificación: UTF-8
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
// $Id: misc.lib.php,v 1.1.1.1 2006/03/03 21:59:09 ainiguez Exp $

////Arreglo que guarda el id de grupo
$arr_enums['Grupo'] = array(1=>'administrador', 2=>'docente',3=>'representante',4=>'alumno',12=>'decano');
 /**
     * Devuelve la descripcion de un valor perteneciente a una enumeracion!
     *
     * @param <$enumName> Nombre de la enumeracion
     * @param <$key> Elemento de la enumeracion
     */
    function getEnumDescripcion($enumName, $key) {
        global $arr_enums;
        if (isset($arr_enums[$enumName][$key]))
            return $arr_enums[$enumName][$key];
        else
            return false;
    }


function combo($arreglo_valores, $selected)
{
     /* TODO: Verificar si $arreglo_valores es un arreglo o no */
     // $arreglo_valores es un arreglo asociativo donde el indice (o clave) corresponde a el valor que toma la variable
     // relacionada con el combo y el valor corresponde a lo que se muestra en pantalla
    $cadena = "";
    if(!is_array($arreglo_valores) or empty($arreglo_valores)) return "";

    foreach ($arreglo_valores as $key => $value) {
        if ($selected == $key) 
             $cadena .= "<option value=\"".htmlentities($key, ENT_COMPAT, "UTF-8")."\" selected>".htmlentities($value, ENT_COMPAT, "UTF-8")."</option>\n";
        else $cadena .= "<option value=\"".htmlentities($key, ENT_COMPAT, "UTF-8")."\">".htmlentities($value, ENT_COMPAT, "UTF-8")."</option>\n";
    }
    return $cadena;
}

function combo2($arrValores, $selected) {

	$arrResultado = array();

	// primero acomodo el $arrValores
	foreach($arrValores as $k => $arrV) {
		$nuevoIndice  = $arrV[0];
		$nuevoValor   = $arrV[1];
		$arrResultado[$nuevoIndice] = $nuevoValor;
	}

	$resultado = combo($arrResultado, $selected);
	return $resultado;
}


     //*
    function recoger_valor($key, $_GET, $_POST, $default = NULL) {
        if (isset($_POST[$key])) return $_POST[$key];
        elseif (isset($_GET[$key])) return $_GET[$key];
        else return $default;
    }



// Asegurar que en lo posible exista un valor en $_POST[$key]
function setear_valor($key, &$_GET, &$_POST, $default = NULL)
{
    if (!isset($_POST[$key])) {
        if (isset($_GET[$key])) {
            $_POST[$key] = $_GET[$key];
        } else if (!is_null($default)) {
            $_POST[$key] = $default;
        }
    }
}


function obtener_grupo_usuario($oACL,$login){

////SE debe verificar si el usuario es de tipo alumno, profesor o administrador

$id_user=$oACL->getIdUser($login);
$arr_grupo=$oACL->getMembership($id_user); 
/////SE obtiene el grupo al que pertenece
   if(is_array($arr_grupo) && count($arr_grupo)>0){
         //Se debe obtener el membership del grupo con mayores privilegios, en este caso del ultimo
         //TODO, hacer que devuelva el del grupo con mayores privilegios
      $id_grupo=array_pop($arr_grupo);
      return $id_grupo;
   }
   else
         return FALSE;

}

function extraer_prefijo($str){

$str2 = preg_replace("(^[0-9]+_)","",$str);
return $str2;

}

function generar_login_chat($username){

global $config;

$db=new paloDB($config->dsn);
$oACL=new paloACL($db);


$id_user=$oACL->getIdUser($username);  //Se obtiene el id_user
$arr_user=$oACL->getUsers($id_user);
$description=$arr_user[0][2];

   if($description=="")
      $description="usuario desc";
   

return $description;

}

?>
