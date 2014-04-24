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
// $Id: ul_nota.class.php,v 1.1.1.1 2006/03/03 21:59:09 ainiguez Exp $
if(isset($gsRutaBase)){
   require_once ($gsRutaBase."/lib/paloEntidad.class.php");
   require_once ($gsRutaBase."/lib/paloReporte.class.php");
   require_once ($gsRutaBase."/modules/ul_docente_materia.class.php");
}
else{
   require_once ("lib/paloEntidad.class.php");
   require_once ("lib/paloReporte.class.php");
   require_once ("modules/ul_docente_materia.class.php");
}




/**
* Alumno_Materia
*/
class ul_nota {

   var $_db;
   var $_tpl;
   var $msMensajeError;
    /**
    * Constructor
    */
    function ul_nota(&$oDB, &$oPlantillas) {
        $this->_db=$oDB;
        $this->_tpl=$oPlantillas;
    }


function setMessage($txt){
   $this->msMensajeError=$txt;
}


function getMessage(){
   return $this->msMensajeError;
}


function matriz_certificado($tpl,$id_alumno){

//El objeto de tipo paloTemplate ya tiene las plantillas para construir formularios asignadas
//se deben utilizar para abstraer el codigo HTML de la funcion
 $tpl_table_container    = "tpl__table_container";
 $tpl_table_header_cell  = "tpl__table_header_cell_bw";
 $tpl_table_header_row   = "tpl__table_header_row";
 $tpl_table_data_cell    = "tpl__table_data_cell_bw";
 $tpl_table_data_row     = "tpl__table_data_row";



$db=$this->getDB();
$sCodigoTabla="";
///Se buscan las materias asociadas al alumno

   if($id_flujo>0)  ///////Si el flujo carrera esta seteado se muestran las materias que pertenezcan a ese flujo
      $sQuery="SELECT m.nombre as materia, am.nota_final, am.aprobada,pl.fecha_inicio, pl.nombre as semestre ".
              "FROM sa_alumno_materia am, sa_materia m, sa_periodo_lectivo pl, sa_flujo_carrera fc, sa_flujo_materia fm ".
              "WHERE am.id_alumno=$id_alumno and am.id_periodo_lectivo=pl.id and am.aprobada in ('S','H') ".
              "and am.id_materia=m.id and m.id=fm.id_materia and fm.id_flujo_carrera=fc.id and fc.id=$id_flujo ".
              "ORDER by pl.fecha_inicio";
   else
      $sQuery="SELECT m.nombre as materia, am.nota_final, am.aprobada,pl.fecha_inicio, pl.nombre as semestre FROM sa_alumno_materia am, sa_materia m, sa_periodo_lectivo pl ".
              "WHERE am.id_materia=m.id and am.id_alumno=$id_alumno and am.id_periodo_lectivo=pl.id and am.aprobada in ('S','H') ".
              "ORDER by pl.fecha_inicio";

   $result=$db->fetchTable($sQuery,true);

///Asignacion de las cabeceras de la plantilla
$arr_header=array("SEMESTRE","MATERIA","NOTA FINAL","APROBADA");
   foreach($arr_header as $value){
      $tpl->assign("HEADER_TEXT", "<b>".$value."</b>");
      $tpl->parse("HEADER_TDs", ".". $tpl_table_header_cell);
   }


   if(is_array($result) && count($result)>0){
      foreach($result as $fila){
         $materia=$fila['materia'];
         $nota_final=$fila['nota_final'];
         $semestre=$fila['semestre'];
         $aprobada=$fila['aprobada'];


         $tpl->assign("DATA", $semestre);
         $tpl->parse("TDs_DATA",$tpl_table_data_cell);

         $tpl->assign("DATA", $materia);
         $tpl->parse("TDs_DATA",".".$tpl_table_data_cell);

         $tpl->assign("DATA", $nota_final);
         $tpl->parse("TDs_DATA",".".$tpl_table_data_cell);

         $tpl->assign("DATA", $aprobada);
         $tpl->parse("TDs_DATA",".".$tpl_table_data_cell);

         $tpl->parse("DATA_ROWs",".". $tpl_table_data_row);
      }
   }
   else
      $tpl->assign("DATA_ROWs","No se pudieron encontrar registros");


 // Parsing final de la tabla
$tpl->assign("TITLE_ROW","");
$tpl->parse("HEADER_ROW",".". $tpl_table_header_row);
$tpl->parse("TABLA", $tpl_table_container);
$sCodigoTabla .= $tpl->fetch("TABLA");
return $sCodigoTabla;

}





function matriz_libreta($tpl,$id_alumno,$id_periodo_lectivo=''){

//El objeto de tipo paloTemplate ya tiene las plantillas para construir formularios asignadas
//se deben utilizar para abstraer el codigo HTML de la funcion
 $tpl_table_container    = "tpl__table_container";
 $tpl_table_header_cell  = "tpl__table_header_cell_bw";
 $tpl_table_header_row   = "tpl__table_header_row";
 $tpl_table_data_cell    = "tpl__table_data_cell_bw";
 $tpl_table_data_row     = "tpl__table_data_row";


$db=$this->_db;
$sCodigoTabla="";

   if($id_periodo_lectivo=="" || $id_periodo_lectivo==NULL)
      return "Debe seleccionarse un periodo lectivo";

///Se deben mostrar las materias en el periodo lectivo seleccionado

$PETICION_MATERIAS="SELECT am.id, mpl.id as id_mpl,m.nombre as materia, mpl.paralelo ".
                  "FROM ul_alumno_materia am, ul_materia m, ul_materia_periodo_lectivo mpl ".
                  "WHERE am.id_alumno=$id_alumno and am.id_periodo_lectivo=$id_periodo_lectivo and am.id_materia=m.id ".
                  "and am.id_materia_periodo_lectivo=mpl.id and mpl.id_materia=m.id ORDER BY m.nombre";

/////Primero se deben crear las cabeceras de la libreta


///Primero se deben obtener los parciales y subparciales

$sQuery="SELECT gp.nombre as grupo,p.nombre as parcial,p.orden,p.id as id_parcial,p.calificable,p.formula ".
        "FROM ul_grupo_parcial gp,ul_parcial p ".
        "WHERE gp.id_periodo_lectivo=$id_periodo_lectivo and p.id_grupo_parcial=gp.id ORDER BY gp.id,p.id";
$result=$db->fetchTable($sQuery,true);


//Se asigna la primera columna del reporte
$tpl->assign("HEADER_TEXT", "<b>Materia</b>");
$tpl->parse("HEADER_TDs",".".$tpl_table_header_cell);
$arr_parciales=array();

   if(is_array($result) && count($result)>0){
      foreach($result as $fila){
         $id_parcial=$fila['id_parcial'];
         $calificable=$fila['calificable'];
         $arr_parciales[]=$id_parcial;

         ///Se asignan las siguientes columnas de los parciales
         $tpl->assign("HEADER_TEXT", "<b>".$fila['parcial']."</b>");
         $tpl->parse("HEADER_TDs", ".". $tpl_table_header_cell);
      }
   }
   else{
      $this->setMessage("No se pudo obtener la información de los parciales. Error:".$db->errMsg);
      return FALSE;
   }

/////Ahora se buscan los materias en las que el alumno está inscrito



$sQuery=$PETICION_MATERIAS;
$result=$db->fetchTable($sQuery,true);


   if(is_array($result) && count($result)>0){
      foreach($result as $fila){
         $materia=$fila['materia']." P".$fila['paralelo'];
         $id_alumno_materia=$fila['id'];
         $id_materia_periodo_lectivo=$fila['id_mpl'];
               ///Se debe crear un nuevo objeto sa_docente materia con cada materia
         $oDocenteMateria=new ul_docente_materia($db,$id_materia_periodo_lectivo);

         $tpl->assign("DATA", $materia);
         $tpl->parse("TDs_DATA",$tpl_table_data_cell);
            for($i=0;$i<count($arr_parciales);$i++){
               $nota=$oDocenteMateria->calcular_parcial($arr_parciales[$i],$id_materia_periodo_lectivo,$id_alumno_materia);
                  if($nota==0)
                     $nota="";

               $tpl->assign("DATA", $nota);
               $tpl->parse("TDs_DATA",".".$tpl_table_data_cell);
            }
        $tpl->parse("DATA_ROWs",".". $tpl_table_data_row);
      }
   }
   else{
      $this->setMessage("No se pudo obtener información de las materias del alumno. Error:".$db->errMsg);     return FALSE;
   }


//////////Luego se debe buscar la información para cada parcial

 // Parsing final de la tabla
$tpl->assign("TITLE_ROW","");

$tpl->parse("HEADER_ROW",".". $tpl_table_header_row);
$tpl->parse("TABLA", $tpl_table_container);

$resultado = $tpl->fetch("TABLA");
return $resultado;




}





function matriz_libreta_parcial($tpl,$id_alumno,$id_periodo_lectivo,$id_parcial){

//El objeto de tipo paloTemplate ya tiene las plantillas para construir formularios asignadas
//se deben utilizar para abstraer el codigo HTML de la funcion
 $tpl_table_container    = "tpl__table_container";
 $tpl_table_header_cell  = "tpl__table_header_cell_bw";
 $tpl_table_header_row   = "tpl__table_header_row";
 $tpl_table_data_cell    = "tpl__table_data_cell_bw";
 $tpl_table_data_row     = "tpl__table_data_row";


$db=$this->_db;
$sCodigoTabla="";

   if($id_periodo_lectivo=="" || $id_periodo_lectivo==NULL)
      return "Debe seleccionarse un periodo lectivo";
   if($id_parcial=="" || $id_parcial==NULL)
      return "Debe seleccionarse un parcial";

///Se deben mostrar las materias en el periodo lectivo seleccionado

$PETICION_MATERIAS="SELECT am.id, mpl.id as id_mpl,m.nombre as materia, mpl.paralelo, p.nombre as parcial ".
                  "FROM ul_alumno_materia am, ul_materia m, ul_materia_periodo_lectivo mpl, ul_parcial p ".
                  "WHERE am.id_alumno=$id_alumno and am.id_periodo_lectivo=$id_periodo_lectivo and am.id_materia=m.id ".
                  "and am.id_materia_periodo_lectivo=mpl.id and mpl.id_materia=m.id and p.id=$id_parcial ORDER BY m.nombre";

/////Primero se deben crear las cabeceras de la libreta

///Primero se deben obtener los subparciales
$sQuery="SELECT sp.nombre as subparcial,sp.id as id_subparcial FROM ul_subparcial sp ".
        "WHERE sp.id_parcial=$id_parcial ORDER BY sp.id";
$result=$db->fetchTable($sQuery,true);


//Se asigna la primera columna del reporte
$tpl->assign("HEADER_TEXT", "<b>Materia</b>");
$tpl->parse("HEADER_TDs",".".$tpl_table_header_cell);
$tpl->assign("HEADER_TEXT", "<b>Parcial</b>");
$tpl->parse("HEADER_TDs",".".$tpl_table_header_cell);
$arr_subparciales=array();

   if(is_array($result) && count($result)>0){
      foreach($result as $fila){
         $id_subparcial=$fila['id_subparcial'];
         $arr_subparciales[]=$id_subparcial;

         ///Se asignan las siguientes columnas de los parciales
         $tpl->assign("HEADER_TEXT", "<b>".$fila['subparcial']."</b>");
         $tpl->parse("HEADER_TDs", ".". $tpl_table_header_cell);
      }
   }
   else{
      $this->setMessage("No se pudo obtener la información de los subparciales. Error:".$db->errMsg);
      return FALSE;
   }

$tpl->assign("HEADER_TEXT", "<b>Total</b>");
$tpl->parse("HEADER_TDs",".".$tpl_table_header_cell);
/////Ahora se buscan los materias en las que el alumno está inscrito



$sQuery=$PETICION_MATERIAS;
$result=$db->fetchTable($sQuery,true);


   if(is_array($result) && count($result)>0){
      foreach($result as $fila){
         $materia=$fila['materia']." P".$fila['paralelo'];
         $id_alumno_materia=$fila['id'];
         $parcial=$fila['parcial'];
         $id_materia_periodo_lectivo=$fila['id_mpl'];
               ///Se debe crear un nuevo objeto sa_docente materia con cada materia
         $oDocenteMateria=new ul_docente_materia($db,$id_materia_periodo_lectivo);
         $sumatoria=0;
         $tpl->assign("DATA", $materia);
         $tpl->parse("TDs_DATA",$tpl_table_data_cell);
         $tpl->assign("DATA", $parcial);
         $tpl->parse("TDs_DATA",".".$tpl_table_data_cell);
            for($i=0;$i<count($arr_subparciales);$i++){
               $nota=$oDocenteMateria->calcular_subparcial($arr_subparciales[$i],$id_materia_periodo_lectivo,$id_alumno_materia);
               $sumatoria+=$nota;
                  if($nota==0)
                     $link_nota="";
                  else
                     $link_nota="<a href='?action=subparcial&id_subparcial=".$arr_subparciales[$i]."&id_periodo_lectivo=$id_periodo_lectivo&id_materia_periodo_lectivo=$id_materia_periodo_lectivo&id_alumno=$id_alumno'>$nota</a>";

               $tpl->assign("DATA", $link_nota);
               $tpl->parse("TDs_DATA",".".$tpl_table_data_cell);
            }

      ///El total
         $tpl->assign("DATA", $sumatoria);
         $tpl->parse("TDs_DATA",".".$tpl_table_data_cell);

         $tpl->parse("DATA_ROWs",".". $tpl_table_data_row);
      }
   }
   else{
      $this->setMessage("No se pudo obtener información de las materias del alumno. Error:".$db->errMsg);
      return FALSE;
   }


//////////Luego se debe buscar la información para cada parcial

 // Parsing final de la tabla
$tpl->assign("TITLE_ROW","");

$tpl->parse("HEADER_ROW",".". $tpl_table_header_row);
$tpl->parse("TABLA", $tpl_table_container);

$resultado = $tpl->fetch("TABLA");
return $resultado;

}



}


class ul_nota_reporte extends PaloReporte{

 function ul_nota_reporte(&$oDB, $oPlantillas, $sBaseURL,$id_alumno,$id_materia_periodo_lectivo,$id_subparcial)
 {
      $this->PaloReporte($oDB, $oPlantillas);

      if (!$this->definirReporte("LISTA_CALIFICABLES_ALUMNO", array(
            //"DEBUG"=>true,
            "TITLE"         =>  "Listado de Calificables en Subparcial<br>\n".
                                 "<input type='hidden' name='id_materia_periodo_lectivo' value=$id_materia_periodo_lectivo>",
            "PAGECHOICE"    =>  array(1000),
            "DATA_COLS"     =>  array(
                                    "ID_ALUMNO_CALIFICABLE"=>"ac.id_alumno_calificable",
                                    "CALIFICABLE"=>"c.titulo",
                                    "PONDERACION"=>"c.ponderacion",
                                    "PUNTUACION"=>"ac.puntuacion",
                                    "ID_MATERIA_PERIODO_LECTIVO"=>"c.id_materia_periodo_lectivo",
                                ),
            "PRIMARY_KEY"   =>  array("ID_ALUMNO_CALIFICABLE"),
            "FROM"          =>  "ul_calificable c, ul_alumno_calificable ac, ul_alumno_materia am",
            "CONST_WHERE"   =>  "ac.id_alumno_materia=am.id and am.id_alumno=$id_alumno and ".
                                 "am.id_materia_periodo_lectivo=$id_materia_periodo_lectivo and ac.id_calificable=c.id_calificable ".
                                 "and c.id_subparcial=$id_subparcial",
            "ORDERING"      =>  array(
                                    "DEFAULT"   =>  array("ID_ALUMNO_CALIFICABLE","CALIFICABLE"),
                                ),
            "BASE_URL"      =>  $sBaseURL,
            "HEADERS"       =>  array(
                                 "CALIFICABLE",
                                 "PONDERACION",
                                 "PUNTUACIÓN",
                                 ),
            "ROW"           =>  array(
                                  "{_DATA_CALIFICABLE}",
                                  array("{_DATA_PORCENTAJE}","ALIGN"=>"CENTER"),
                                  array("{_DATA_PUNTUACION}","ALIGN"=>"RIGHT"),
                                 ),
        ))) die ("ul_nota_reporte: - al definir reporte LISTA_CALIFICABLES_ALUMNO - ".$this->_msMensajeError);


}



function event_proveerCampos($sNombreReporte, $tuplaSQL)
{     global $config;

      switch ($sNombreReporte) {
         case "LISTA_CALIFICABLES_ALUMNO":
            $porcentaje=$tuplaSQL['PONDERACION']*100;
            $porcentaje.="%";
            ///Se debe obtener informacion de topicos del foro
            return array("PORCENTAJE"=>$porcentaje);

            break;
      }
}


}








