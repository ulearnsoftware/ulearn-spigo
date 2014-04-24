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
// |          Otro           <otro@example.com>                           |
// +----------------------------------------------------------------------+
//
// $Id: impresion_notas.php,v 1.1.1.1 2006/03/03 21:59:09 ainiguez Exp $
session_start();
$gsRutaBase="..";
header("Content-Type: text/html; charset=UTF-8");

require_once ("../lib/paloTemplate.class.php");
require_once ("../lib/paloEntidad.class.php");
require_once ("../lib/paloDB.class.php");
require_once ("../lib/misc.lib.php");
require_once ("../conf/default.conf.php");
require_once ("../modules/ul_docente_materia.class.php");
require_once ("../modules/ul_nota.class.php");

$dsn =& $config->dsn;
$pDB = new paloDB($dsn);
print _moduleContent($pDB, $_GET, $_POST);


function _moduleContent(&$pDB, &$_GET, &$_POST)
{
    global $config; // definda en conf/default.conf.php

    $insDB =& new PaloDB($config->dsn);
    $insTpl =& new paloTemplate("../skins/".$config->skin);
    $insTpl->definirDirectorioPlantillas("_common");
    $insTpl->assign("IMG_PATH", "../skins/$config->skin/images");

    $tpl= new paloTemplate("../skins/".$config->skin);
    $tpl->definirDirectorioPlantillas("notas");


    $sContenido="";
    $id_materia_periodo_lectivo=recoger_valor('id_materia_periodo_lectivo',$_GET,$_POST);
    $id_parcial=recoger_valor('id_parcial',$_GET,$_POST);
    $id_subparcial=recoger_valor('id_subparcial',$_GET,$_POST);
    $id_alumno=recoger_valor('id_alumno',$_GET,$_POST);
    $id_flujo=recoger_valor("id_flujo",$_GET,$_POST);
    $id_periodo_lectivo=recoger_valor("id_periodo_lectivo",$_GET,$_POST);

    $fecha_emision=date("Y-m-d H:i:s");

    $action=recoger_valor("action",$_GET,$_POST);

   $docente=$materia=$paralelo=$combo_parcial=$periodo=$titulo=$n_alumnos="";
   $cabecera_pagina=$pie_pagina="";

   /////Se obtienen los datos del docente y la materia
      if($id_materia_periodo_lectivo>0){
         $sQuery="SELECT concat(d.apellido,' ',d.nombre) as docente,p.nombre as periodo, m.nombre as materia,mpl.paralelo ".
                 "FROM ul_docente d, ul_materia_periodo_lectivo mpl, ul_materia m, ul_periodo_lectivo p ".
                 "WHERE d.id=mpl.id_docente and mpl.id=$id_materia_periodo_lectivo and mpl.id_docente=d.id and ".
                 "mpl.id_materia=m.id and mpl.id_periodo_lectivo=p.id";
         $result=$pDB->getFirstRowQuery($sQuery,true);
            if(is_array($result) && count($result)>0){
               $docente=$result['docente'];
               $materia=$result['materia'];
               $paralelo=$result['paralelo'];
               $periodo=$result['periodo'];
            }
      }


//////////Se busca el nombre del periodo lectivo
      if($id_periodo_lectivo>0){
         $sQuery="SELECT nombre FROM ul_periodo_lectivo WHERE id=$id_periodo_lectivo";
         $result=$pDB->getFirstRowQuery($sQuery,true);
            if(is_array($result) && count($result)>0){
               $periodo=$result['nombre'];
            }
      }

    $oEntidad=new ul_docente_materia($insDB,$id_materia_periodo_lectivo);
    $oNota=new ul_nota($insDB,$insTpl);
    $sCodigoTabla="";
    $arr_filas=array();

       switch($action){
         case "notas":
               $tpl->clear();
               $tpl->parse("CABECERA", "tpl_cabecera_pagina_acta");
               $cabecera_pagina= $tpl->fetch("CABECERA");
               $tpl->parse("PIE", "tpl_pie_pagina_acta");
               $pie_pagina= $tpl->fetch("PIE");

               if($id_materia_periodo_lectivo>0){
                     $arr_filas=array(
                       array("tag"=>"<b>Periodo Lectivo:</b>","value"=>$periodo),
                       array("tag"=>"<b>Materia:</b>","value"=>$materia),
                       array("tag"=>"<b>Paralelo:</b>","value"=>"P".$paralelo),
                       array("tag"=>"<b>Docente:</b>","value"=>$docente),
                       array("tag"=>"<b>Fecha Emisión:</b>","value"=>$fecha_emision),
                       array("tag"=>"<b>Usuario:</b>","value"=>$_SESSION['session_user']),
                     );
                    $sCodigoTabla=$oEntidad->generarFormularioNotasGeneral($insDB,$insTpl,$id_materia_periodo_lectivo,$_GET,$_POST);
                    $n_alumnos=$oEntidad->n_alumnos;
                    $sCodigoTabla.="<table border=0><tr><td align=left class=table_data><b>Total Alumnos: $n_alumnos</td></tr></table>";
               }
            break;
         case "notas_parcial":
               $tpl->clear();
               $tpl->parse("CABECERA", "tpl_cabecera_pagina_acta");
               $cabecera_pagina= $tpl->fetch("CABECERA");
               $tpl->parse("PIE", "tpl_pie_pagina_acta");
               $pie_pagina= $tpl->fetch("PIE");


               if($id_parcial>0){
                   $sCodigoTabla=$oEntidad->generarFormularioNotasParcial($insDB,$insTpl,$id_parcial,$id_materia_periodo_lectivo,$_GET,$_POST,TRUE);
                   $n_alumnos=$oEntidad->n_alumnos;
                   $sCodigoTabla.="<table border=0><tr><td align=left class=table_data><b>Total Alumnos: $n_alumnos</td></tr></table>";


                   $sQuery="SELECT nombre as parcial FROM ul_parcial WHERE id=$id_parcial";
                   $result=$pDB->getFirstRowQuery($sQuery,true);
                     if(is_array($result) && count($result)>0){
                        $parcial=$result['parcial'];
                     };
                   $arr_filas=array(
                       array("tag"=>"<b>Periodo Lectivo:</b>","value"=>$periodo),
                       array("tag"=>"<b>Materia:</b>","value"=>$materia),
                       array("tag"=>"<b>Paralelo:</b>","value"=>"P".$paralelo),
                       array("tag"=>"<b>Docente:</b>","value"=>$docente),
                       array("tag"=>"<b>Parcial:</b>","value"=>$parcial),
                       array("tag"=>"<b>Fecha Emisión:</b>","value"=>$fecha_emision),
                       array("tag"=>"<b>Usuario:</b>","value"=>$_SESSION['session_user']),
                   );
               }
            break;


            case "notas_subparcial":
               $tpl->clear();
               $tpl->parse("CABECERA", "tpl_cabecera_pagina_acta");
               $cabecera_pagina= $tpl->fetch("CABECERA");
               $tpl->parse("PIE", "tpl_pie_pagina_acta");
               $pie_pagina= $tpl->fetch("PIE");


               if($id_subparcial>0){
                   $sCodigoTabla=$oEntidad->generarFormularioNotasSubparcial($insDB,$insTpl,$id_subparcial,$id_materia_periodo_lectivo,$_GET,$_POST,TRUE);
                   $n_alumnos=$oEntidad->n_alumnos;
                   $sCodigoTabla.="<table border=0><tr><td align=left class=table_data><b>Total Alumnos: $n_alumnos</td></tr></table>";


                   $sQuery="SELECT s.nombre as subparcial,p.nombre as parcial FROM ul_subparcial s, ul_parcial p ".
                           "WHERE s.id=$id_subparcial and s.id_parcial=p.id";
                   $result=$pDB->getFirstRowQuery($sQuery,true);
                     if(is_array($result) && count($result)>0){
                        $subparcial=$result['subparcial'];
                        $parcial=$result['parcial'];
                     }
                   $arr_filas=array(
                       array("tag"=>"<b>Periodo Lectivo:</b>","value"=>$periodo),
                       array("tag"=>"<b>Materia:</b>","value"=>$materia),
                       array("tag"=>"<b>Paralelo:</b>","value"=>"P".$paralelo),
                       array("tag"=>"<b>Parcial:</b>","value"=>$parcial),
                       array("tag"=>"<b>Subparcial:</b>","value"=>$subparcial),
                       array("tag"=>"<b>Docente:</b>","value"=>$docente),
                       array("tag"=>"<b>Fecha Emisión:</b>","value"=>$fecha_emision),
                       array("tag"=>"<b>Usuario:</b>","value"=>$_SESSION['session_user']),
                   );
               }
            break;

         default:
           $sCodigoTabla="";
       }

$insTpl->clear();
 foreach($arr_filas as $fila){
      $insTpl->assign("DATA",$fila['tag']);
      $insTpl->assign("CELL_ATTRIBUTES","");
      $insTpl->parse("TDs_DATA","tpl__table_data_cell");
      $insTpl->assign("DATA",$fila['value']);
      $insTpl->parse("TDs_DATA",".tpl__table_data_cell");
      $insTpl->parse("DATA_ROWs",".tpl__table_data_row");
   }


//Se asigna a la plantilla la fila con la if
$insTpl->assign("TITLE_ROW",$titulo);
$insTpl->assign("HEADER_ROW","");
$insTpl->assign("TBL_WIDTH","100%");
$insTpl->parse("CABECERA", "tpl__table_container");
$tabla_cabecera = $insTpl->fetch("CABECERA");

$insTpl->assign("TITLE_ROW","");
$insTpl->assign("HEADER_ROW","");
$insTpl->assign("TBL_WIDTH","100%");
$insTpl->assign("DATA_ROWs",$sCodigoTabla);
$insTpl->parse("TABLA", "tpl__table_container");
$tabla_notas = $insTpl->fetch("TABLA");



$sContenido="<head>
               <link rel='stylesheet' href=\"../skins/".$config->skin."/_common/styles.css\" type=\"text/css\">
               <title>Sistema Academico</title>
                  <script>
                     window.focus();
                  </script>
               </head>";
$sContenido.=$tabla_cabecera."<br>".$tabla_notas;

    return "<html>".$cabecera_pagina.
                    $sContenido.
                    $pie_pagina."</html>";
}

?>
