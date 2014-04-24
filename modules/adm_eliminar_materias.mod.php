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
// | la ley sin saberlo.
// +----------------------------------------------------------------------+
// | Autores: Edgar Landivar <e_landivar@palosanto.com>                   |
// +----------------------------------------------------------------------+
//
// $Id: adm_eliminar_materias.mod.php,v 1.1.1.1 2006/03/03 21:59:09 ainiguez Exp $

require_once ("lib/paloTemplate.class.php");
require_once ("lib/paloDB.class.php");
require_once ("modules/ul_eliminacion.class.php");

$tpl->assign("CONTENT", _moduleContent($pDB, $_GET, $_POST));


function _moduleContent(&$pDB, &$_GET, &$_POST)
{     ///Se genera un objeto tipo plantilla (PaloTemplate)
     global $config;
    $insTpl =& new paloTemplate("skins/".$config->skin);
    $insTpl->definirDirectorioPlantillas("");
    $insTpl->assign("IMG_PATH", "skins/$config->skin/images");

    $db =& new PaloDB($config->dsn);

    $sContenido="";
    $error="";

    $id_periodo_lectivo=recoger_valor("id_periodo_lectivo",$_GET,$_POST);

      if (isset($_POST['eliminar'])){
         if($id_periodo_lectivo>0){
            $oEliminacion=new ul_eliminacion($db);
            $bool=$oEliminacion->eliminar_periodo_lectivo($id_periodo_lectivo);

            if(!$bool)
               $sContenido.=$insTpl->crearAlerta("error","Al Eliminar",$oEliminacion->getMessage());
            else
               $sContenido.=$insTpl->crearAlerta("!","Al Eliminar","Se han eliminado exitosamente las materias del periodo lectivo");
         }
         else{
            $sContenido.=$insTpl->crearAlerta("error","Al Eliminar","No se ha recibido un id_periodo_lectivo válido");
         }
      }

      //Se genera la tabla para mostrar los botones
    //Se buscan los periodos lectivos

     $sQuery="SELECT id,nombre FROM ul_periodo_lectivo"; // WHERE estatus in ('A','P')";
     $result=$pDB->fetchTable($sQuery,true);
      if(is_array($result) && count($result)>0){
         foreach($result as $fila)
            $arrPeriodos[$fila['id']]=$fila['nombre'];
      }

   $combo_periodos="<select name='id_periodo_lectivo'>".combo($arrPeriodos,$id_periodo_lectivo)."</select>";

   $boton_eliminar="<input type='submit' name='eliminar' value='Eliminar' onClick=\"return confirm('Está seguro que desea eliminar toda la información de las materias pertenecientes ".
                     "al periodo lectivo seleccionado?  Esta operación puede tardar varios minutos y NO puede ser deshecha. Desea continuar?.')\">";

   $arrFilas[]=array("<b>Periodo Lectivo:</b>",$combo_periodos);
   $arrFilas[]=array("&nbsp;",$boton_eliminar);
  //Se genera la tabla para mostrar los botones
   $sContenido .= $insTpl->crearTabla("",$arrFilas,"Eliminar Materias Periodo Lectivo","",2);


    return "<form name='main' method='POST'>$sContenido</form>";
}


?>
