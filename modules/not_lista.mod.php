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
// | Autores: Alex Villacis <iochoa2@telefonica.net>                      |
// +----------------------------------------------------------------------+
//
// $Id: not_lista.mod.php,v 1.1.1.1 2006/03/03 21:59:09 ainiguez Exp $

require_once ("lib/paloTemplate.class.php");
require_once ("lib/paloDB.class.php");
require_once ("modules/ul_docente_materia.class.php");


$tpl->assign("CONTENT", _moduleContent($pDB, $_GET, $_POST));

function _moduleContent(&$pDB, &$_GET, &$_POST)
{
    global $config; // definda en conf/default.conf.php

    $oACL=getACL();
    $insDB =& new PaloDB($config->dsn);
    $insTpl =& new paloTemplate("skins/".$config->skin);
    $insTpl->definirDirectorioPlantillas("");
    $insTpl->assign("IMG_PATH", "skins/$config->skin/images");

    // Decidir si se debe mostrar el formulario de modificacin
    if (isset($_GET["action"])) {
        $sAccion = $_GET["action"];
    } else {
        $sAccion = "listar";
    }

    if(isset($_POST['Ver_parcial']))
      $sAccion='parcial';
    if(isset($_POST['Ver_subparcial']))
      $sAccion='subparcial';


    $sCodigoTabla = "";
    $id_parcial=recoger_valor("id_parcial",$_GET,$_POST);
    $id_materia_periodo_lectivo=recoger_valor("id_materia_periodo_lectivo",$_GET,$_POST);

    if (!in_array($sAccion, array("notas","parcial","subparcial"))) $sAccion = "notas";

    switch ($sAccion) {

    case "notas":
        return mostrarFormularioConsultaNotas($pDB, $insTpl, $_GET, $_POST);
        break;

    case "parcial":
        return mostrarFormularioConsultaParcial($pDB, $insTpl, $_GET, $_POST);
        break;
    case "subparcial":
        return mostrarFormularioConsultaSubparcial($pDB, $insTpl, $_GET, $_POST);
        break;

    case "listar":
    default:


        $oReporte_sa_docente_materia =& new sa_docente_materia_reporte($pDB, $insTpl, "?menu1op=submenu_docentes&submenuop=doc_materias&id_periodo_lectivo=$id_periodo_lectivo&id_docente=$id_docente");

        $sCodigoTabla .= $oReporte_sa_docente_materia->generarReporte("LISTA_DOCENTE_MATERIAS", $_GET, $_POST);
        return $sCodigoTabla;
        break;
    }
}




function mostrarFormularioConsultaNotas(&$pDB, &$Tpl, &$_GET, &$_POST)
{
    $sContenido = "";
    global $config;
    $tpl =new paloTemplate("skins/".$config->skin);
    $tpl->definirDirectorioPlantillas("");
    $tpl->assign("IMG_PATH", "skins/$config->skin/images");

    // Verificar si se dispone de un ID de usuario
    $id_materia_periodo_lectivo=recoger_valor("id_materia_periodo_lectivo",$_GET,$_POST);

    //Si no existe materia periodo lectivo no se muestra nada
      if(is_null($id_materia_periodo_lectivo) || $id_materia_periodo_lectivo=="")
         return $tpl->crearAlerta("!","Nota", "Debe seleccionar una materia para consultar las notas");
         
      //Obtener el id docente
      $sQuery="SELECT id_docente FROM ul_materia_periodo_lectivo WHERE id=$id_materia_periodo_lectivo";
      $result=$pDB->getFirstRowQuery($sQuery);
      $id_docente=NULL;

         if(is_array($result) && count($result)>0)
            $id_docente=$result[0];

      //Se crea el objeto docente materia
    $oEntidad=new ul_docente_materia($pDB,$id_materia_periodo_lectivo);

    if (is_null($id_docente) || is_null($id_materia_periodo_lectivo)) {
        Header("Location: ?menu1op=submenu_notas&submenuop=not_lista");
        return "";
    } else {

        $strHTML=$oEntidad->generarFormularioNotasGeneral($pDB,$tpl,$id_materia_periodo_lectivo,$_GET,$_POST);
            if($strHTML===false)
               $strHTML=$tpl->crearAlerta("error","Al generar Formulario Notas General",$oEntidad->errMsg);

         ////Se debe añadir la funcionalidad en una plantilla adicional
         $tbl_cabecera=cabecera_notas($pDB,$tpl,$id_docente,$id_materia_periodo_lectivo);

         $tpl->clear();
         $tpl->assign("COLSPAN","5");
         $tpl->assign("TITLE",$tbl_cabecera);
         $tpl->parse("TITLE_ROW","tpl__table_title_row");
         $tpl->assign("HEADER_ROW","");

         $tpl->assign("DATA",$strHTML);
         $tpl->parse("TDs_DATA","tpl__table_data_cell");

         $tpl->parse("DATA_ROWs","tpl__table_data_row");
         $tpl->assign("TBL_WIDTH","100%");
         $tpl->parse("TABLA", "tpl__table_container");
         $resultado = $tpl->fetch("TABLA");
         $sContenido.=$resultado;


        return $sContenido;
    }
}






function mostrarFormularioConsultaParcial(&$pDB, &$tpl, &$_GET, &$_POST)
{
    $sContenido = "";

    // Verificar si se dispone de un ID de usuario
    $id_materia_periodo_lectivo=recoger_valor("id_materia_periodo_lectivo",$_GET,$_POST);
    $id_parcial=recoger_valor("id_parcial",$_GET,$_POST);

    //Obtener el id docente
      $sQuery="SELECT id_docente FROM ul_materia_periodo_lectivo WHERE id=$id_materia_periodo_lectivo";
      $result=$pDB->getFirstRowQuery($sQuery);
      $id_docente=NULL;

         if(is_array($result) && count($result)>0)
            $id_docente=$result[0];

      //Se crea el objeto docente materia
    $oEntidad=new ul_docente_materia($pDB,$id_materia_periodo_lectivo);


    if (is_null($id_docente) || is_null($id_materia_periodo_lectivo)) {
        Header("Location: ?menu1op=submenu_notas&submenuop=not_lista");
        return "";
    } else {

        $solo_lectura=TRUE;

        $strHTML=$oEntidad->generarFormularioNotasParcial($pDB,$tpl,$id_parcial,$id_materia_periodo_lectivo,$_GET,$_POST, $solo_lectura);
           if($strHTML===false)
               $strHTML=$tpl->crearAlerta("error","Al generar Formulario Notas General",$oEntidad->errMsg);

////Se debe añadir la funcionalidad en una plantilla adicional
         $tbl_cabecera=cabecera_parcial($pDB,$tpl,$id_parcial,$id_docente,$id_materia_periodo_lectivo);

         $tpl->assign("COLSPAN","5");
         $tpl->assign("TITLE",$tbl_cabecera);
         $tpl->parse("TITLE_ROW","tpl__table_title_row");
         $tpl->assign("HEADER_ROW","");

         $tpl->assign("DATA",$strHTML);
         $tpl->parse("TDs_DATA","tpl__table_data_cell");

         $tpl->parse("DATA_ROWs","tpl__table_data_row");
         $tpl->assign("TBL_WIDTH","100%");
         $tpl->parse("TABLA", "tpl__table_container");
         $tabla = $tpl->fetch("TABLA");

         $resultado="<form name='main' method='POST'>".$tabla."</form>";

         $sContenido.=$resultado;

        return $sContenido;
    }
}




function mostrarFormularioConsultaSubparcial(&$pDB, &$tpl, &$_GET, &$_POST)
{
    $sContenido = "";

    // Verificar si se dispone de un ID de usuario
    $id_materia_periodo_lectivo=recoger_valor("id_materia_periodo_lectivo",$_GET,$_POST);
    $id_parcial=recoger_valor("id_parcial",$_GET,$_POST);
    $id_subparcial=recoger_valor("id_subparcial",$_GET,$_POST);

    //Obtener el id docente
      $sQuery="SELECT id_docente FROM ul_materia_periodo_lectivo WHERE id=$id_materia_periodo_lectivo";
      $result=$pDB->getFirstRowQuery($sQuery);
      $id_docente=NULL;

         if(is_array($result) && count($result)>0)
            $id_docente=$result[0];

      //Se crea el objeto docente materia
    $oEntidad=new ul_docente_materia($pDB,$id_materia_periodo_lectivo);


    if (is_null($id_docente) || is_null($id_materia_periodo_lectivo)) {
        Header("Location: ?menu1op=submenu_notas&submenuop=not_lista");
        return "";
    } else {

        $solo_lectura=TRUE;

        $strHTML=$oEntidad->generarFormularioNotasSubparcial($pDB,$tpl,$id_subparcial,$id_materia_periodo_lectivo,$_GET,$_POST, $solo_lectura);
           if($strHTML===false)
               $strHTML=$tpl->crearAlerta("error","Al generar Formulario Notas Subparcial",$oEntidad->errMsg);

////Se debe añadir la funcionalidad en una plantilla adicional
         $tbl_cabecera=cabecera_subparcial($pDB,$tpl,$id_parcial,$id_docente,$id_materia_periodo_lectivo);

         $tpl->assign("COLSPAN","5");
         $tpl->assign("TITLE",$tbl_cabecera);
         $tpl->parse("TITLE_ROW","tpl__table_title_row");
         $tpl->assign("HEADER_ROW","");

         $tpl->assign("DATA",$strHTML);
         $tpl->parse("TDs_DATA","tpl__table_data_cell");

         $tpl->parse("DATA_ROWs","tpl__table_data_row");
         $tpl->assign("TBL_WIDTH","100%");
         $tpl->parse("TABLA", "tpl__table_container");
         $tabla = $tpl->fetch("TABLA");

         $resultado="<form name='main' method='POST'>".$tabla."</form>";

         $sContenido.=$resultado;

        return $sContenido;
    }
}











///////////////Esta funcion devuelve la cabecera HTML para asignar a la plantilla. En la pantalla de Consulta notas materia

function cabecera_notas($oDB,$tpl,$id_docente,$id_materia_periodo_lectivo){
$tpl->clear();


$docente=$materia=$paralelo=$combo_parcial="";

/////Se obtienen los datos del docente y la materia
$sQuery="SELECT concat(d.apellido,' ',d.nombre) as docente,p.nombre as periodo, m.nombre as materia,mpl.paralelo ".
        "FROM ul_docente d, ul_materia_periodo_lectivo mpl, ul_materia m, ul_periodo_lectivo p ".
        "WHERE d.id=$id_docente and mpl.id=$id_materia_periodo_lectivo and mpl.id_docente=d.id and ".
        "mpl.id_materia=m.id and mpl.id_periodo_lectivo=p.id";
$result=$oDB->getFirstRowQuery($sQuery,true);
   if(is_array($result) && count($result)>0){
      $docente=$result['docente'];
      $materia=$result['materia'];
      $paralelo=$result['paralelo'];
      $periodo=$result['periodo'];
   }

///////////Se buscan los parciales disponibles y se los asigna a un combo
$sQuery="SELECT p.nombre as parcial,p.id as id_parcial FROM ul_grupo_parcial gp,ul_parcial p, ul_materia_periodo_lectivo mpl ".
        "WHERE mpl.id=$id_materia_periodo_lectivo and mpl.id_periodo_lectivo=gp.id_periodo_lectivo and ".
        "p.id_grupo_parcial=gp.id and p.calificable='S' ORDER BY gp.id,p.id";
$result=$oDB->fetchTable($sQuery,true);
   if(is_array($result) && count($result)>0){
      foreach($result as $fila){
         $arr_parciales[$fila['id_parcial']]=$fila['parcial'];
      }
   }

$combo_parcial="<select name='id_parcial'>".combo($arr_parciales,"")."</select>";
$boton_submit="<input class='mi_submit' type='submit' name='Ver_parcial' value='Ver Parcial'>";
$link_imprimir="<a href='modules/impresion_notas.php?id_materia_periodo_lectivo=$id_materia_periodo_lectivo&action=notas' target='notas'>Imprimir</a>";

$arr_filas=array(
                 array("tag"=>"<b>Periodo Lectivo:</b>","value"=>$periodo),
                 array("tag"=>"<b>Materia:</b>","value"=>$materia),
                 array("tag"=>"<b>Paralelo:</b>","value"=>"P".$paralelo),
                 array("tag"=>"<b>Docente:</b>","value"=>$docente),
                 array("tag"=>"<b>Detalle Notas:</b>","value"=>$combo_parcial.str_repeat("&nbsp;",4).$boton_submit.str_repeat("&nbsp;",10).$link_imprimir),
           );


//Se asigna a la plantilla la fila con la informacion de las filas
   foreach($arr_filas as $fila){
      $tpl->assign("DATA",$fila['tag']);
      $tpl->parse("TDs_DATA","tpl__table_data_cell");
      $tpl->assign("DATA",$fila['value']);
      $tpl->parse("TDs_DATA",".tpl__table_data_cell");
      $tpl->parse("DATA_ROWs",".tpl__table_data_row");
   }

$titulo="Notas x Materia".
        "<input type='hidden' name='id_docente' value=$id_docente>".
        "<input type='hidden' name='id_materia_periodo_lectivo' value=$id_materia_periodo_lectivo>";
//Se asigna a la plantilla la fila con la if
$tpl->assign("TITLE_ROW",$titulo);
$tpl->assign("HEADER_ROW","");
$tpl->assign("TBL_WIDTH","100%");
$tpl->parse("TABLA", "tpl__table_container");
$tabla = $tpl->fetch("TABLA");

$resultado="<form name='main' method='POST'>".$tabla."</form>";
return $resultado;

}







///////////////Esta funcion devuelve la cabecera HTML para asignar a la plantilla. En la pantalla de Consulta notas parcial

function cabecera_parcial($oDB,$tpl,$id_parcial,$id_docente,$id_materia_periodo_lectivo){
$docente=$materia=$paralelo=$combo_parcial="";

$id_materia_periodo_lectivo=recoger_valor("id_materia_periodo_lectivo",$_GET,$_POST);
$id_docente=recoger_valor("id_docente",$_GET,$_POST);

/////Se obtienen los datos del docente y la materia
$sQuery="SELECT concat(d.apellido,' ',d.nombre) as docente,p.nombre as periodo, m.nombre as materia,mpl.paralelo ".
        "FROM ul_docente d, ul_materia_periodo_lectivo mpl, ul_materia m, ul_periodo_lectivo p ".
        "WHERE d.id=$id_docente and mpl.id=$id_materia_periodo_lectivo and mpl.id_docente=d.id and ".
        "mpl.id_materia=m.id and mpl.id_periodo_lectivo=p.id";
$result=$oDB->getFirstRowQuery($sQuery,true);
   if(is_array($result) && count($result)>0){
      $docente=$result['docente'];
      $materia=$result['materia'];
      $paralelo=$result['paralelo'];
      $periodo=$result['periodo'];
   }

//Se obtiene nombre del parcial
$sQuery="SELECT nombre FROM ul_parcial WHERE id=$id_parcial";
$result=$oDB->getFirstRowQuery($sQuery,true);
   if(is_array($result) && count($result)>0){
      $parcial=$result['nombre'];
   }
   else
      $parcial="";

///////////Se buscan los subparciales disponibles y se los asigna a un combo
$sQuery="SELECT sp.nombre as subparcial,sp.id as id_subparcial FROM ul_subparcial sp ".
         "WHERE sp.id_parcial=$id_parcial ORDER BY sp.id";
$result=$oDB->fetchTable($sQuery,true);
   if(is_array($result) && count($result)>0){
      foreach($result as $fila){
         $arr_subparciales[$fila['id_subparcial']]=$fila['subparcial'];
      }
   }

$combo_subparcial="<select name='id_subparcial'>".combo($arr_subparciales,"")."</select>";
$link_imprimir="<a href='modules/impresion_notas.php?id_materia_periodo_lectivo=$id_materia_periodo_lectivo&action=notas_parcial&id_parcial=$id_parcial' target='notas'>Imprimir</a>";
$boton_submit="<input class='mi_submit' type='submit' name='Ver_subparcial' value='Ver Subparcial'>";


$arr_filas=array(
                 array("tag"=>"<b>Periodo Lectivo:</b>","value"=>$periodo),
                 array("tag"=>"<b>Materia:</b>","value"=>$materia),
                 array("tag"=>"<b>Paralelo:</b>","value"=>"P".$paralelo),
                 array("tag"=>"<b>Parcial:</b>","value"=>$parcial),
                 array("tag"=>"<b>Docente:</b>","value"=>$docente),
                 array("tag"=>"<b>Detalle Subparcial:</b>","value"=>$combo_subparcial.str_repeat("&nbsp;",4).$boton_submit),
                 array("tag"=>"&nbsp;","value"=>$link_imprimir),
           );

$tpl->clear();
//Se asigna a la plantilla la fila con la informacion de las filas
   foreach($arr_filas as $fila){
      $tpl->assign("DATA",$fila['tag']);
      $tpl->parse("TDs_DATA","tpl__table_data_cell");
      $tpl->assign("DATA",$fila['value']);
      $tpl->parse("TDs_DATA",".tpl__table_data_cell");
      $tpl->parse("DATA_ROWs",".tpl__table_data_row");
   }

$titulo="<a href=\"?menu1op=submenu_notas&submenuop=not_lista&id_materia_periodo_lectivo=$id_materia_periodo_lectivo\"'>&laquo; Regresar</a>".
        "<input type='hidden' name='id_docente' value=$id_docente>".
        "<input type='hidden' name='id_parcial' value=$id_parcial>".
        "<input type='hidden' name='id_materia_periodo_lectivo' value=$id_materia_periodo_lectivo>".
        "<input type='hidden' name='id_parcial' value=$id_parcial>";
//Se asigna a la plantilla la fila con la if
$tpl->assign("TITLE_ROW",$titulo);
$tpl->assign("HEADER_ROW","");
$tpl->assign("TBL_WIDTH","100%");
$tpl->parse("TABLA", "tpl__table_container");
$tabla = $tpl->fetch("TABLA");


return $tabla;

}



///////////////Esta funcion devuelve la cabecera HTML para asignar a la plantilla. En la pantalla de Consulta notas parcial

function cabecera_subparcial($oDB,$tpl,$id_parcial,$id_docente,$id_materia_periodo_lectivo){
$docente=$materia=$paralelo=$parcial=$subparcial="";

$id_materia_periodo_lectivo=recoger_valor("id_materia_periodo_lectivo",$_GET,$_POST);
$id_docente=recoger_valor("id_docente",$_GET,$_POST);
$id_parcial=recoger_valor("id_parcial",$_GET,$_POST);
$id_subparcial=recoger_valor("id_subparcial",$_GET,$_POST);

/////Se obtienen los datos del docente y la materia
$sQuery="SELECT concat(d.apellido,' ',d.nombre) as docente,p.nombre as periodo, m.nombre as materia,mpl.paralelo ".
        "FROM ul_docente d, ul_materia_periodo_lectivo mpl, ul_materia m, ul_periodo_lectivo p ".
        "WHERE d.id=$id_docente and mpl.id=$id_materia_periodo_lectivo and mpl.id_docente=d.id and ".
        "mpl.id_materia=m.id and mpl.id_periodo_lectivo=p.id";
$result=$oDB->getFirstRowQuery($sQuery,true);
   if(is_array($result) && count($result)>0){
      $docente=$result['docente'];
      $materia=$result['materia'];
      $paralelo=$result['paralelo'];
      $periodo=$result['periodo'];
   }

//Se obtiene nombre del subparcial
$sQuery="SELECT p.nombre as parcial, sp.nombre as subparcial FROM ul_subparcial sp, ul_parcial p ".
         "WHERE sp.id=$id_subparcial and sp.id_parcial=p.id";
$result=$oDB->getFirstRowQuery($sQuery,true);
   if(is_array($result) && count($result)>0){
      $subparcial=$result['subparcial'];
      $parcial=$result['parcial'];
   }
   else
      $subparcial="";



$link_imprimir="<a href='modules/impresion_notas.php?id_materia_periodo_lectivo=$id_materia_periodo_lectivo&action=notas_subparcial&id_subparcial=$id_subparcial' target='notas'>Imprimir</a>";

$arr_filas=array(
                 array("tag"=>"<b>Periodo Lectivo:</b>","value"=>$periodo),
                 array("tag"=>"<b>Materia:</b>","value"=>$materia),
                 array("tag"=>"<b>Paralelo:</b>","value"=>"P".$paralelo),
                 array("tag"=>"<b>Parcial:</b>","value"=>$parcial),
                 array("tag"=>"<b>Subparcial:</b>","value"=>$subparcial),
                 array("tag"=>"<b>Docente:</b>","value"=>$docente),
                 array("tag"=>"&nbsp;","value"=>$link_imprimir),
           );

$tpl->clear();
//Se asigna a la plantilla la fila con la informacion de las filas
   foreach($arr_filas as $fila){
      $tpl->assign("DATA",$fila['tag']);
      $tpl->parse("TDs_DATA","tpl__table_data_cell");
      $tpl->assign("DATA",$fila['value']);
      $tpl->parse("TDs_DATA",".tpl__table_data_cell");
      $tpl->parse("DATA_ROWs",".tpl__table_data_row");
   }

$titulo="<a href=\"?menu1op=submenu_notas&submenuop=not_lista&id_materia_periodo_lectivo=$id_materia_periodo_lectivo&action=parcial&id_parcial=$id_parcial&id_docente=$id_docente\"'>&laquo; Regresar</a>".
        "<input type='hidden' name='id_docente' value=$id_docente>".
        "<input type='hidden' name='id_parcial' value=$id_parcial>".
        "<input type='hidden' name='id_subparcial' value=$id_subparcial>".
        "<input type='hidden' name='id_materia_periodo_lectivo' value=$id_materia_periodo_lectivo>";
//Se asigna a la plantilla la fila con la if
$tpl->assign("TITLE_ROW",$titulo);
$tpl->assign("HEADER_ROW","");
$tpl->assign("TBL_WIDTH","100%");
$tpl->parse("TABLA", "tpl__table_container");
$tabla = $tpl->fetch("TABLA");


return $tabla;

}





?>
