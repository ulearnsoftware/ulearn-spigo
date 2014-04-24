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
// $Id: not_notas_alumno.mod.php,v 1.1.1.1 2006/03/03 21:59:09 ainiguez Exp $

require_once ("lib/paloTemplate.class.php");
require_once ("lib/paloDB.class.php");
require_once ("modules/ul_docente_materia.class.php");
require_once ("modules/ul_nota.class.php");


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

    //////Se debe recoger el id del usuario conectado y ver si es de tipo alumno
    $id_user=$oACL->getIdUser($_SESSION['session_user']);
    /////Se debe buscar el id de alumno que sea del tipo alumno
    $sQuery="SELECT id FROM ul_alumno WHERE id_acl_user=$id_user";
    $id_alumno=NULL;
    $result=$pDB->getFirstRowQuery($sQuery);
      if(is_array($result) && count($result)>0){
         $id_alumno=$result[0];
         $_POST['id_alumno']=$id_alumno;
      }

      if($id_alumno==NULL || $id_alumno<=0){
         return $insTpl->crearAlerta("error","Error","No se pudo obtener el id_alumno para el usuario actual");
      }

    $sCodigoTabla = "";
    $id_parcial=recoger_valor("id_parcial",$_GET,$_POST);
    $id_materia_periodo_lectivo=recoger_valor("id_materia_periodo_lectivo",$_GET,$_POST);
    $id_periodo_lectivo=recoger_valor("id_periodo_lectivo",$_GET,$_POST);

    if (!in_array($sAccion, array("listar","notas","parcial","subparcial"))) $sAccion = "notas";

    switch ($sAccion) {

    case "notas":
        //return mostrarFormularioConsultaNotas($pDB, $insTpl, $_GET, $_POST);
        break;

    case "parcial":
        return mostrarFormularioConsultaParcial($pDB, $insTpl, $_GET, $_POST);
        break;
    case "subparcial":
        return mostrarFormularioConsultaSubparcial($pDB, $insTpl, $_GET, $_POST);
        break;

    case "listar":
    default:

        $oNota = new ul_nota($pDB, $insTpl);
        $sCodigoTabla.=cabecera_notas($pDB,$insTpl,$id_alumno,$id_periodo_lectivo);
        $matriz=$oNota->matriz_libreta($insTpl,$id_alumno,$id_periodo_lectivo);
            if($matriz===FALSE)
               return $insTpl->crearAlerta("error","Error",$oNota->getMessage());
            else
               $sCodigoTabla.=$matriz;
        return $sCodigoTabla;
        break;
    }
}




function mostrarFormularioConsultaNotas($pDB, $tpl, &$_GET, &$_POST)
{
   $sCodigoTabla= "";

   $oNota = new ul_nota($pDB, $tpl);
   $sCodigoTabla.=cabecera_parcial($pDB,$tpl,$id_alumno,$id_periodo_lectivo);
   $matriz=$oNota->matriz_libreta_parcial($tpl,$id_alumno,$id_periodo_lectivo);
         if($matriz===FALSE)
            return $tpl->crearAlerta("error","Error",$oNota->getMessage());
         else
            $sCodigoTabla.=$matriz;
   return $sCodigoTabla;

}






function mostrarFormularioConsultaParcial(&$pDB, $insTpl, &$_GET, &$_POST)
{   $sCodigoTabla="";
    $oNota = new ul_nota($pDB, $insTpl);
    $id_alumno=recoger_valor("id_alumno",$_GET,$_POST);
    $id_parcial=recoger_valor("id_parcial",$_GET,$_POST);
    $id_periodo_lectivo=recoger_valor("id_periodo_lectivo",$_GET,$_POST);

   $sCodigoTabla.=cabecera_parcial($pDB,$insTpl,$id_alumno,$id_periodo_lectivo,$id_parcial);
   $matriz=$oNota->matriz_libreta_parcial($insTpl,$id_alumno,$id_periodo_lectivo,$id_parcial);
      if($matriz===FALSE)
         return $insTpl->crearAlerta("error","Error",$oNota->getMessage());
      else
         $sCodigoTabla.="<br>".$matriz;
   return $sCodigoTabla;

}




function mostrarFormularioConsultaSubparcial(&$pDB, &$insTpl, &$_GET, &$_POST)
{
    $sCodigoTabla="";

    $id_alumno=recoger_valor("id_alumno",$_GET,$_POST);
    $id_subparcial=recoger_valor("id_subparcial",$_GET,$_POST);
    $id_periodo_lectivo=recoger_valor("id_periodo_lectivo",$_GET,$_POST);
    $id_materia_periodo_lectivo=recoger_valor("id_materia_periodo_lectivo",$_GET,$_POST);

    $oNota = new ul_nota_reporte($pDB, $insTpl,"?action=subparcial&id_subparcial=$id_subparcial&id_periodo_lectivo=$id_periodo_lectivo&id_materia_periodo_lectivo=$id_materia_periodo_lectivo&id_alumno=$id_alumno",$id_alumno,$id_materia_periodo_lectivo,$id_subparcial);

   $sCodigoTabla.=cabecera_subparcial($pDB,$insTpl,$id_alumno,$id_periodo_lectivo,$id_subparcial);
   $sCodigoTabla.="<br>".$oNota->generarReporte("LISTA_CALIFICABLES_ALUMNO",$_GET,$_POST);

   return $sCodigoTabla;
}











///////////////Esta funcion devuelve la cabecera HTML para asignar a la plantilla. En la pantalla de Consulta notas materia

function cabecera_notas($oDB,$tpl,$id_alumno,&$id_periodo_lectivo){
$combo_parcial="";

/////Se obtienen los datos del alumno
$sQuery="SELECT concat(a.apellido,' ',a.nombre) as alumno ".
        "FROM ul_alumno a ".
        "WHERE a.id=$id_alumno";
$result=$oDB->getFirstRowQuery($sQuery,true);
   if(is_array($result) && count($result)>0)
      $alumno=$result['alumno'];

////////////////////////////////////////////////////////////////////////////////////////////////
/////////////////////para obtener el combo de periodo lectivo //////////////////////////////////
////////////////////////////////////////////////////////////////////////////////////////////////

$sQuery="SELECT distinct pl.id,pl.nombre FROM ul_periodo_lectivo pl, ul_materia_periodo_lectivo mpl, ul_alumno_materia am, ul_calificable c ".
         "WHERE mpl.id_periodo_lectivo=pl.id and am.id_materia_periodo_lectivo=mpl.id and c.id_materia_periodo_lectivo=mpl.id ".
         "and am.id_alumno=$id_alumno ORDER BY pl.fecha_inicio,pl.fecha_fin DESC";
$result=$oDB->fetchTable($sQuery,true);
$arr_periodos=array();
   if(is_array($result) && count($result)>0){
      foreach($result as $fila)
         $arr_periodos[$fila['id']]=$fila['nombre'];
   }
$combo_periodos="<select name='id_periodo_lectivo'>".combo($arr_periodos,"")."</select>";

//Si es la primera carga se debe seleccionar el primer elemento del combo
   if($id_periodo_lectivo==NULL OR $id_periodo_lectivo==""){
      reset($arr_periodos);
      $fila=each($arr_periodos);
      $id_periodo_lectivo=$fila['key'];
   }

   if($id_periodo_lectivo>0){
      ///////////Se buscan los parciales disponibles y se los asigna a un combo//////////////////////
      $sQuery="SELECT p.nombre as parcial,p.id as id_parcial FROM ul_grupo_parcial gp,ul_parcial p ".
            "WHERE gp.id_periodo_lectivo=$id_periodo_lectivo and p.id_grupo_parcial=gp.id and p.calificable='S' ".
            "ORDER BY gp.id,p.id";
      $result=$oDB->fetchTable($sQuery,true);
         if(is_array($result) && count($result)>0){
            foreach($result as $fila){
               $arr_parciales[$fila['id_parcial']]=$fila['parcial'];
            }
         }
   }

$combo_parcial="<select name='id_parcial'>".combo($arr_parciales,"")."</select>";
$boton_submit="<input class='mi_submit' type='submit' name='Ver_parcial' value='Ver Parcial'>";

$arr_filas=array(
                 array("tag"=>"<b>Periodo Lectivo:</b>","value"=>$combo_periodos),
                 array("tag"=>"<b>Alumno:</b>","value"=>$alumno),
                 array("tag"=>"<b>Detalle Notas:</b>","value"=>$combo_parcial.str_repeat("&nbsp;",4).$boton_submit),
           );


//Se asigna a la plantilla la fila con la informacion de las filas
   foreach($arr_filas as $fila){
      $tpl->assign("DATA",$fila['tag']);
      $tpl->parse("TDs_DATA","tpl__table_data_cell");
      $tpl->assign("DATA",$fila['value']);
      $tpl->parse("TDs_DATA",".tpl__table_data_cell");
      $tpl->parse("DATA_ROWs",".tpl__table_data_row");
   }

$titulo="<div align='center'><b>Listado de Notas de Alumno</b><br>&nbsp;</div>".
        "<input type='hidden' name='id_alumno' value=$id_alumno>".
        "<input type='hidden' name='id_periodo_lectivo' value=$id_periodo_lectivo>";
//Se asigna a la plantilla la fila con la if
$tpl->assign("TITLE_ROW",$titulo);
$tpl->assign("HEADER_ROW","");
$tpl->assign("TBL_WIDTH","100%");
$tpl->parse("TABLA", "tpl__table_container");
$tabla = $tpl->fetch("TABLA");

$resultado="<form name='main' method='POST'>".$tabla."</form><br>";
return $resultado;

}







///////////////Esta funcion devuelve la cabecera HTML para asignar a la plantilla. En la pantalla de Consulta notas parcial

function cabecera_parcial($oDB,$tpl,$id_alumno,$id_periodo_lectivo,$id_parcial){
$id_materia_periodo_lectivo=recoger_valor("id_materia_periodo_lectivo",$_GET,$_POST);

/////Se obtienen los datos del alumno
$sQuery="SELECT concat(a.apellido,' ',a.nombre) as alumno, p.nombre as periodo ".
        "FROM ul_alumno a, ul_periodo_lectivo p ".
        "WHERE a.id=$id_alumno and p.id=$id_periodo_lectivo";
$result=$oDB->getFirstRowQuery($sQuery,true);
   if(is_array($result) && count($result)>0){
      $alumno=$result['alumno'];
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



$arr_filas=array(
                 array("tag"=>"<b>Periodo Lectivo:</b>","value"=>$periodo),
                 array("tag"=>"<b>Alumno:</b>","value"=>$alumno),
                 array("tag"=>"<b>Parcial:</b>","value"=>$parcial),
           );


//Se asigna a la plantilla la fila con la informacion de las filas
   foreach($arr_filas as $fila){
      $tpl->assign("DATA",$fila['tag']);
      $tpl->parse("TDs_DATA","tpl__table_data_cell");
      $tpl->assign("DATA",$fila['value']);
      $tpl->parse("TDs_DATA",".tpl__table_data_cell");
      $tpl->parse("DATA_ROWs",".tpl__table_data_row");
   }

$titulo="<div align='center' style='font-size:10pt'><b><a href=\"?menu1op=submenu_notas&submenuop=not_lista&id_materia_periodo_lectivo=$id_materia_periodo_lectivo\"'>&laquo; Regresar</a></b></div><br>".
        "<input type='hidden' name='id_alumno' value=$id_alumno>".
        "<input type='hidden' name='id_parcial' value=$id_parcial>".
        "<input type='hidden' name='id_materia_periodo_lectivo' value=$id_materia_periodo_lectivo>";
//Se asigna a la plantilla la fila con la if
$tpl->assign("TITLE_ROW",$titulo);
$tpl->assign("HEADER_ROW","");
$tpl->assign("TBL_WIDTH","100%");
$tpl->parse("TABLA", "tpl__table_container");
$tabla = $tpl->fetch("TABLA");


return $tabla;

}



///////////////Esta funcion devuelve la cabecera HTML para asignar a la plantilla. En la pantalla de Consulta notas parcial

function cabecera_subparcial($oDB,$tpl,$id_alumno,$id_periodo_lectivo,$id_subparcial){
$id_materia_periodo_lectivo=recoger_valor("id_materia_periodo_lectivo",$_GET,$_POST);
/////Se obtienen los datos del alumno
$sQuery="SELECT concat(a.apellido,' ',a.nombre) as alumno, p.nombre as periodo, m.nombre as materia,mpl.paralelo as paralelo ".
        "FROM ul_alumno a, ul_periodo_lectivo p, ul_materia m, ul_materia_periodo_lectivo mpl ".
        "WHERE a.id=$id_alumno and p.id=$id_periodo_lectivo and mpl.id_materia=m.id  and mpl.id=$id_materia_periodo_lectivo";
$result=$oDB->getFirstRowQuery($sQuery,true);
   if(is_array($result) && count($result)>0){
      $alumno=$result['alumno'];
      $periodo=$result['periodo'];
      $materia=$result['materia']." P".$result['paralelo'];
   }


//Se obtiene nombre del parcial
$sQuery="SELECT p.id as id_parcial,p.nombre as parcial ,s.nombre as subparcial ".
         "FROM ul_parcial p, ul_subparcial s WHERE s.id=$id_subparcial and s.id_parcial=p.id";
$result=$oDB->getFirstRowQuery($sQuery,true);
   if(is_array($result) && count($result)>0){
      $parcial=$result['parcial'];
      $subparcial=$result['subparcial'];
      $id_parcial=$result['id_parcial'];
   }
   else{
      $parcial=$subparcial=$id_parcial="";
   }

$arr_filas=array(
                 array("tag"=>"<b>Periodo Lectivo:</b>","value"=>$periodo),
                 array("tag"=>"<b>Alumno:</b>","value"=>$alumno),
                 array("tag"=>"<b>Parcial:</b>","value"=>$parcial),
                 array("tag"=>"<b>Subparcial:</b>","value"=>$subparcial),
                 array("tag"=>"<b>Materia:</b>","value"=>$materia),
           );


//Se asigna a la plantilla la fila con la informacion de las filas
   foreach($arr_filas as $fila){
      $tpl->assign("DATA",$fila['tag']);
      $tpl->parse("TDs_DATA","tpl__table_data_cell");
      $tpl->assign("DATA",$fila['value']);
      $tpl->parse("TDs_DATA",".tpl__table_data_cell");
      $tpl->parse("DATA_ROWs",".tpl__table_data_row");
   }

$titulo="<div align='center' style='font-size:10pt'><b><a href=\"?menu1op=submenu_notas&submenuop=not_lista&action=parcial&id_alumno=$id_alumno&id_parcial=$id_parcial&id_periodo_lectivo=$id_periodo_lectivo&id_materia_periodo_lectivo=$id_materia_periodo_lectivo\"'>&laquo; Regresar</a></b></div><br>".
        "<input type='hidden' name='id_alumno' value=$id_alumno>".
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
