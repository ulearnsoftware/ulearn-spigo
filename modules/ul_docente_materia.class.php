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
// $Id: ul_docente_materia.class.php,v 1.1.1.1 2006/03/03 21:59:09 ainiguez Exp $

if (isset($gsRutaBase)){
   require_once ($gsRutaBase."/lib/paloEntidad.class.php");
   require_once ($gsRutaBase."/lib/paloACL.class.php");
   require_once ($gsRutaBase."/lib/paloReporte.class.php");
   require_once ("ul_configuracion.class.php");

}
else{
   require_once ("lib/paloEntidad.class.php");
   require_once ("lib/paloACL.class.php");
   require_once ("lib/paloReporte.class.php");
   require_once ("ul_configuracion.class.php");
}


class ul_docente_materia{
var $_db;
var $errMsg;
var $nota_base;
var $valor_aprobacion;
var $inicio_redondeo;
var $fin_redondeo;
var $arr_ids;
var $arr_subparcial;
var $n_alumnos;


function ul_docente_materia($oDB,$id_materia_periodo_lectivo){
   $this->_db=$oDB;
   //////Se debe obtener la nota base de la tabla sa_configuracion
   $oConfiguracion=new ul_configuracion($oDB);
   $oConfiguracion->leeConfiguracion($oDB);
   $this->nota_base=$oConfiguracion->getProperty("Notas","Nota_base",'');
   $this->inicio_redondeo=$oConfiguracion->getProperty("Notas","Inicio_redondeo",'');
   $this->fin_redondeo=$oConfiguracion->getProperty("Notas","Fin_redondeo",'');
   $this->valor_aprobacion=$oConfiguracion->getProperty("Notas","Valor_aprobacion",'');
   $this->asignar_arr_ids($id_materia_periodo_lectivo);
}




function asignar_arr_ids($id_materia_periodo_lectivo){
$db=$this->_db;
///Primero se deben obtener los parciales y subparciales
$sQuery="SELECT p.nombre as parcial,p.orden,p.id as id_parcial,p.calificable,p.formula ".
         "FROM ul_grupo_parcial gp,ul_parcial p, ul_materia_periodo_lectivo mpl ".
        "WHERE mpl.id=$id_materia_periodo_lectivo and mpl.id_periodo_lectivo=gp.id_periodo_lectivo and ".
        "p.id_grupo_parcial=gp.id ORDER BY gp.id,p.id";
$result=$db->fetchTable($sQuery,true);

   if(is_array($result) && count($result)>0){
      foreach($result as $fila){
         $calificable=$fila['calificable'];
         //Si el calificable es de tipo 'C' se buscan los ids parciales
         if($calificable=='C'){
            $formula=$fila['formula'];
            $id_parcial=$fila['id_parcial'];
            //Se carga en un arreglo los ids de los parciales
            $arr_par=$this->getParcialIDs($formula);
            $arr_subparcial=$this->getSubParcialIDs($formula);
            $this->arr_ids[$id_parcial]=$arr_par;
            $this->arr_subparcial[$id_parcial]=$arr_subparcial;
         }
      }
   }
}




function setMessage($mensaje){
   $this->errMsg=$mensaje;
}




function verificar_aprobacion($nota_final){

if($nota_final>=$this->valor_aprobacion)
   return TRUE;
else
   return FALSE;

}

function redondear_nota($nota_final){

   if($this->inicio_redondeo>0 && $this->fin_redondeo>0){
      if($nota_final>=$this->inicio_redondeo && $nota_final<$this->fin_redondeo)
         $nota_final=ceil($nota_final);
   }

return $nota_final;
}



//////////Fin funciones nota final///////////////////////////////////////////////////////////////////




function generarFormularioNotasGeneral($db,$tpl,$id_materia_periodo_lectivo,$_GET,$_POST){

//El objeto de tipo paloTemplate ya tiene las plantillas para construir formularios asignadas
//se deben utilizar para abstraer el codigo HTML de la funcion
 $tpl_table_container    = "tpl__table_container";
 $tpl_table_header_cell  = "tpl__table_header_cell_bw";
 $tpl_table_header_row   = "tpl__table_header_row";
 $tpl_table_data_cell    = "tpl__table_data_cell_bw";
 $tpl_table_data_row     = "tpl__table_data_row";



///Primero se deben obtener los parciales y subparciales
$sQuery="SELECT gp.nombre as grupo,p.nombre as parcial,p.orden,p.id as id_parcial,p.calificable,p.formula ".
         "FROM ul_grupo_parcial gp,ul_parcial p, ul_materia_periodo_lectivo mpl ".
        "WHERE mpl.id=$id_materia_periodo_lectivo and mpl.id_periodo_lectivo=gp.id_periodo_lectivo and ".
        "p.id_grupo_parcial=gp.id ORDER BY gp.id,p.id";
$result=$db->fetchTable($sQuery,true);

//Se asigna la primera columna del reporte
$tpl->assign("HEADER_TEXT", "<b>Alumno</b>");
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

/////Ahora se buscan los alumnos inscritos en la materia periodo lectivo

$sQuery="SELECT a.nombre,a.apellido,am.id ,'' as carrera FROM ul_alumno a, ul_alumno_materia am ".
        "WHERE am.id_materia_periodo_lectivo=$id_materia_periodo_lectivo and am.id_alumno=a.id ".
        "ORDER BY a.apellido, a.nombre";
/*$sQuery="SELECT a.nombre,a.apellido,am.id,am.id_materia,am.id_alumno, max(c.nombre) as carrera ".
        "FROM ul_alumno a, ul_alumno_materia am, ul_materia m ".
        "LEFT JOIN ul_alumno_flujo af ON af.id_alumno=am.id_alumno ".
        "LEFT JOIN ul_flujo_carrera fc ON fc.id=af.id_flujo ".
        "LEFT JOIN ul_flujo_materia fm ON fm.id_flujo_carrera=fc.id and fm.id_materia=m.id ".
        "LEFT JOIN ul_carrera c ON fc.id_carrera=c.id ".
        "WHERE am.id_materia_periodo_lectivo=$id_materia_periodo_lectivo and am.id_alumno=a.id and am.id_materia=m.id ".
        "GROUP BY 1,2,3,4,5 ".
        "ORDER BY carrera,a.apellido, a.nombre";*/

$result=$db->fetchTable($sQuery,true);

$cont=0;
$grupo="";
   if(is_array($result) && count($result)>0){
      foreach($result as $fila){
         $alumno=$fila['apellido']." ".$fila['nombre'];
         $id_alumno_materia=$fila['id'];

            if($grupo!=$fila['carrera']){
               $grupo=$fila['carrera'];
               ///Se asigna una fila con cabecera el nombre de la carrera para separara los alumnos de las carreras
               $str_separador="<tr><td class='table_data_bw' style='font-size:8pt;' colspan=".(count($arr_parciales)+1)."><b>".$fila['carrera']."</b></td></tr>";

               $tpl->assign("TDs_DATA",$str_separador);
               $tpl->parse("DATA_ROWs",".". $tpl_table_data_row);
            }


         $tpl->assign("DATA", $alumno);
         $tpl->parse("TDs_DATA",$tpl_table_data_cell);
            for($i=0;$i<count($arr_parciales);$i++){
               $nota=$this->calcular_parcial($arr_parciales[$i],$id_materia_periodo_lectivo,$id_alumno_materia);
                  if($nota==0)
                     $nota="";
                  else{
                     if($this->es_nota_final($arr_parciales[$i]))
                        $nota=$this->redondear_nota($nota);

                     $nota=number_format($nota,2);
                  }

               $tpl->assign("DATA", "<div align=right>".$nota."</div>");
               $tpl->parse("TDs_DATA",".".$tpl_table_data_cell);
            }
        $tpl->parse("DATA_ROWs",".". $tpl_table_data_row);
        $cont++;
      }
   }
   else{
      $this->setMessage("No se pudo obtener información de los alumnos Inscritos en la materia. Error:".$db->errMsg);
      return FALSE;
   }

$this->n_alumnos=$cont;

//////////Luego se debe buscar la información para cada parcial

 // Parsing final de la tabla
$tpl->assign("TITLE_ROW","");

$tpl->parse("HEADER_ROW",".". $tpl_table_header_row);
$tpl->parse("TABLA", $tpl_table_container);

$resultado = $tpl->fetch("TABLA");
return $resultado;



}



function es_nota_final($id_parcial){
$db=$this->_db;
$bValido=FALSE;
$sQuery="SELECT n_final FROM sa_parcial WHERE id=$id_parcial";
$result=$db->getFirstRowQuery($sQuery);

   if(is_array($result) && count($result)>0){
      if($result[0]==1)
         $bValido=TRUE;
   }


return $bValido;
}



//////////////////////////Formulario para ingresar PARCIAL



function generarFormularioNotasParcial($db,$tpl,$id_parcial,$id_materia_periodo_lectivo,$_GET,$_POST,$solo_lectura=TRUE){

//El objeto de tipo paloTemplate ya tiene las plantillas para construir formularios asignadas
//se deben utilizar para abstraer el codigo HTML de la funcion
 $tpl_table_container    = "tpl__table_container";
 $tpl_table_header_cell  = "tpl__table_header_cell_bw";
 $tpl_table_header_row   = "tpl__table_header_row";
 $tpl_table_data_cell    = "tpl__table_data_cell_bw";
 $tpl_table_data_row     = "tpl__table_data_row";



///Primero se deben obtener los subparciales
$sQuery="SELECT sp.nombre as subparcial,sp.id FROM ul_subparcial sp, ul_materia_periodo_lectivo mpl ".
        "WHERE mpl.id=$id_materia_periodo_lectivo and sp.id_parcial=$id_parcial ORDER BY sp.id";
$result=$db->fetchTable($sQuery,true);

//Se asigna la primera columna del reporte
$tpl->assign("HEADER_TEXT", "<b>Alumno</b>");
$tpl->parse("HEADER_TDs",".".$tpl_table_header_cell);
$arr_parciales=array();

   if(is_array($result) && count($result)>0){
      foreach($result as $fila){
         $id_subparcial=$fila['id'];
         $arr_subparciales[]=$id_subparcial;

         ///Se asignan las siguientes columnas de los parciales
         $tpl->assign("HEADER_TEXT", "<b>".$fila['subparcial']."</b>");
         $tpl->parse("HEADER_TDs", ".". $tpl_table_header_cell);
      }
      ///Se asigna la columna de sumatoria
      $tpl->assign("HEADER_TEXT", "<b>Total</b>");
      $tpl->parse("HEADER_TDs", ".". $tpl_table_header_cell);
   }
   else{
      $this->setMessage("No se pudo obtener la información de los subparciales. Error:".$db->errMsg);
      return FALSE;
   }

/////Ahora se buscan los alumnos inscritos en la materia periodo lectivo

$sQuery="SELECT a.nombre,a.apellido,am.id,'' as carrera FROM ul_alumno a, ul_alumno_materia am ".
        "WHERE am.id_materia_periodo_lectivo=$id_materia_periodo_lectivo and am.id_alumno=a.id ".
        "ORDER BY a.apellido, a.nombre";
/*
$sQuery="SELECT a.nombre,a.apellido,am.id,am.id_materia,am.id_alumno, max(c.nombre) as carrera ".
        "FROM ul_alumno a, ul_alumno_materia am, ul_materia m ".
        "LEFT JOIN ul_alumno_flujo af ON af.id_alumno=am.id_alumno ".
        "LEFT JOIN ul_flujo_carrera fc ON fc.id=af.id_flujo ".
        "LEFT JOIN ul_flujo_materia fm ON fm.id_flujo_carrera=fc.id and fm.id_materia=m.id ".
        "LEFT JOIN ul_carrera c ON fc.id_carrera=c.id ".
        "WHERE am.id_materia_periodo_lectivo=$id_materia_periodo_lectivo and am.id_alumno=a.id and am.id_materia=m.id ".
        "GROUP BY 1,2,3,4,5 ".
        "ORDER BY carrera,a.apellido, a.nombre";*/


$result=$db->fetchTable($sQuery,true);
$cont=0;
$grupo="";

   if(is_array($result) && count($result)>0){
      foreach($result as $fila){
         $alumno=$fila['apellido']." ".$fila['nombre'];
         $id_alumno_materia=$fila['id'];

          if($grupo!=$fila['carrera']){
               $grupo=$fila['carrera'];
               ///Se asigna una fila con cabecera el nombre de la carrera para separara los alumnos de las carreras
               $str_separador="<tr><td class='table_data_bw' style='font-size:8pt;' colspan=".(count($arr_subparciales)+2)."><b>".$fila['carrera']."</b></td></tr>";

               $tpl->assign("TDs_DATA",$str_separador);
               $tpl->parse("DATA_ROWs",".". $tpl_table_data_row);
            }

         $tpl->assign("DATA", $alumno);
         $tpl->parse("TDs_DATA",$tpl_table_data_cell);
         $sumatoria=0;

            for($i=0;$i<count($arr_subparciales);$i++){
               $txt_nota="";
               /////Se deben buscar las submaterias
               $arr_submaterias=$this->obtener_submaterias($arr_subparciales[$i],$id_materia_periodo_lectivo,$id_alumno_materia);

                 if(is_array($arr_submaterias) && count($arr_submaterias)>0){
                     $total=0;
                     foreach($arr_submaterias as $fila){
                        $id_calificable=$fila['id_calificable'];
                        $nombre=$fila['nombre'];
                        $nota=$fila['nota'];
                        $ponderacion=$fila['ponderacion'];
                        $nota_ponderada=$nota*$ponderacion;
                        $total+=$nota_ponderada;
                        $sumatoria+=$nota_ponderada;

                     }
                       //Si solo lectura es falso se muestra el textbox para cambiar la nota
                     if(!$solo_lectura)
                        $txt_nota.="<input class='text_nota' type='text' size=5 name='arr_notas[$id_alumno_materia][$id_calificable]' value=$total>";
                     else
                        $txt_nota.="<div>$total</div>";
                  }



               $tpl->assign("DATA", $txt_nota);
               $tpl->parse("TDs_DATA",".".$tpl_table_data_cell);
            }

        $tpl->assign("DATA", number_format($sumatoria,2));
        $tpl->parse("TDs_DATA",".".$tpl_table_data_cell);

        $tpl->parse("DATA_ROWs",".". $tpl_table_data_row);
        $cont++;
      }
   }
   else{
      $this->setMessage("No se pudo obtener información de los alumnos Inscritos en la materia. Error:".$db->errMsg);
      return FALSE;
   }

$this->n_alumnos=$cont;

 // Parsing final de la tabla
$tpl->assign("TITLE_ROW","");

$tpl->parse("HEADER_ROW",".". $tpl_table_header_row);
$tpl->parse("TABLA", $tpl_table_container);

$resultado = $tpl->fetch("TABLA");
return $resultado;



}





//////////////////////////Formulario para Consultar SUBPARCIAL



function generarFormularioNotasSubparcial($db,$tpl,$id_subparcial,$id_materia_periodo_lectivo,$_GET,$_POST,$solo_lectura=TRUE){

//El objeto de tipo paloTemplate ya tiene las plantillas para construir formularios asignadas
//se deben utilizar para abstraer el codigo HTML de la funcion
 $tpl_table_container    = "tpl__table_container";
 $tpl_table_header_cell  = "tpl__table_header_cell_bw";
 $tpl_table_header_row   = "tpl__table_header_row";
 $tpl_table_data_cell    = "tpl__table_data_cell_bw";
 $tpl_table_data_row     = "tpl__table_data_row";



///Primero se deben obtener los calificables
$sQuery="SELECT c.titulo,c.id_calificable FROM ul_calificable c ".
        "WHERE c.id_materia_periodo_lectivo=$id_materia_periodo_lectivo and c.id_subparcial=$id_subparcial ".
        "ORDER BY c.id_calificable";
$result=$db->fetchTable($sQuery,true);

//Se asigna la primera columna del reporte
$tpl->assign("HEADER_TEXT", "<b>Alumno</b>");
$tpl->parse("HEADER_TDs",".".$tpl_table_header_cell);
$arr_calificables=array();

   if(is_array($result) && count($result)>0){
      foreach($result as $fila){
         $id_calificable=$fila['id_calificable'];
         $arr_calificables[]=$id_calificable;

         ///Se asignan las siguientes columnas de los parciales
         $tpl->assign("HEADER_TEXT", "<b>".substr($fila['titulo'],0,40)."...</b>");
         $tpl->parse("HEADER_TDs", ".". $tpl_table_header_cell);
      }
      ///Se asigna la columna de sumatoria
      $tpl->assign("HEADER_TEXT", "<b>Total</b>");
      $tpl->parse("HEADER_TDs", ".". $tpl_table_header_cell);
   }
   else{
      $this->setMessage("No hay calificables relacionados al subparcial:");
      return FALSE;
   }

/////Ahora se buscan los alumnos inscritos en la materia periodo lectivo

$sQuery="SELECT a.nombre,a.apellido,am.id,'' as carrera FROM ul_alumno a, ul_alumno_materia am ".
        "WHERE am.id_materia_periodo_lectivo=$id_materia_periodo_lectivo and am.id_alumno=a.id ".
        "ORDER BY a.apellido, a.nombre";
/*
$sQuery="SELECT a.nombre,a.apellido,am.id,am.id_materia,am.id_alumno, max(c.nombre) as carrera ".
        "FROM ul_alumno a, ul_alumno_materia am, ul_materia m ".
        "LEFT JOIN ul_alumno_flujo af ON af.id_alumno=am.id_alumno ".
        "LEFT JOIN ul_flujo_carrera fc ON fc.id=af.id_flujo ".
        "LEFT JOIN ul_flujo_materia fm ON fm.id_flujo_carrera=fc.id and fm.id_materia=m.id ".
        "LEFT JOIN ul_carrera c ON fc.id_carrera=c.id ".
        "WHERE am.id_materia_periodo_lectivo=$id_materia_periodo_lectivo and am.id_alumno=a.id and am.id_materia=m.id ".
        "GROUP BY 1,2,3,4,5 ".
        "ORDER BY carrera,a.apellido, a.nombre";*/


$result=$db->fetchTable($sQuery,true);
$cont=0;
$grupo="";

   if(is_array($result) && count($result)>0){
      foreach($result as $fila){
         $alumno=$fila['apellido']." ".$fila['nombre'];
         $id_alumno_materia=$fila['id'];

          if($grupo!=$fila['carrera']){
               $grupo=$fila['carrera'];
               ///Se asigna una fila con cabecera el nombre de la carrera para separara los alumnos de las carreras
               $str_separador="<tr><td class='table_data_bw' style='font-size:8pt;' colspan=".(count($arr_subparciales)+2)."><b>".$fila['carrera']."</b></td></tr>";

               $tpl->assign("TDs_DATA",$str_separador);
               $tpl->parse("DATA_ROWs",".". $tpl_table_data_row);
            }

         $tpl->assign("DATA", $alumno);
         $tpl->parse("TDs_DATA",$tpl_table_data_cell);
         $sumatoria=0;

            for($i=0;$i<count($arr_calificables);$i++){
               $txt_nota="";
               /////Se deben buscar los calificables
               $sQuery="SELECT c.ponderacion, ac.puntuacion FROM ul_calificable c, ul_alumno_calificable ac ".
                        "WHERE c.id_calificable=".$arr_calificables[$i]." and ac.id_calificable=c.id_calificable ".
                        "and ac.id_alumno_materia=$id_alumno_materia";
               $result=$db->getFirstRowQuery($sQuery,true);
                 if(is_array($result) && count($result)>0){

                        $puntuacion=$result['puntuacion'];
                        $ponderacion=$result['ponderacion'];
                        $nota_ponderada=$puntuacion*$ponderacion;
                        $sumatoria+=$nota_ponderada;

                     //TODO: En caso de que una materia tenga varias submaterias para el subparcial se debe hacer una tabla
                     //que muestre en cada columna lo que retorna el arr submateria

                     //Si solo lectura es falso se muestra el textbox para cambiar la nota
                     $txt_nota.="<div>$nota_ponderada</div>";
                  }



               $tpl->assign("DATA", $txt_nota);
               $tpl->parse("TDs_DATA",".".$tpl_table_data_cell);
            }

        $tpl->assign("DATA", number_format($sumatoria,2));
        $tpl->parse("TDs_DATA",".".$tpl_table_data_cell);

        $tpl->parse("DATA_ROWs",".". $tpl_table_data_row);
        $cont++;
      }
   }
   else{
      $this->setMessage("No se pudo obtener información de los alumnos Inscritos en la materia. Error:".$db->errMsg);
      return FALSE;
   }

$this->n_alumnos=$cont;

 // Parsing final de la tabla
$tpl->assign("TITLE_ROW","");

$tpl->parse("HEADER_ROW",".". $tpl_table_header_row);
$tpl->parse("TABLA", $tpl_table_container);

$resultado = $tpl->fetch("TABLA");
return $resultado;



}










function calcular_parcial($id_parcial,$id_materia_periodo_lectivo,$id_alumno_materia){

$db=$this->_db;

$sQuery="SELECT * from ul_parcial WHERE id=$id_parcial";
$result=$db->getFirstRowQuery($sQuery,true);
   if(is_array($result) && count($result)>0){
      $calificable=$result['calificable'];
         switch($calificable){
            case "S":   //Caso de obtenido de SUBPARCIALES
                  $sPeticionSQL="SELECT id FROM ul_subparcial WHERE id_parcial=$id_parcial";
                  $result=$db->fetchTable($sPeticionSQL,true);
                     if(is_array($result) && count($result)>0){
                        $nota=0;
                           foreach($result as $fila){
                              $id_subparcial=$fila['id'];

                              $nota+=$this->calcular_subparcial($id_subparcial,$id_materia_periodo_lectivo,$id_alumno_materia);

                           }
                        return $nota;

                     }
                     else{
                        $this->setMessage("No se pudo obtener el calculo de las suma de subparciales. Error:".$db->errMsg);
                        return FALSE;
                     }

                  break;

            case "C":   ///Caso de calculado por Formula
                  $arr_par=$this->arr_ids[$id_parcial];
                  $arr_subparcial=$this->arr_subparcial[$id_parcial];
                  $formula=$result['formula'];


                     foreach($arr_par as $id){
                        $valor=$this->calcular_parcial($id,$id_materia_periodo_lectivo,$id_alumno_materia);
                        $formula = ereg_replace("\{".$id."\}","$valor",$formula);
                     }
                     ////Se necesita reemplazar en la formula los valores de subparciales si aparecen

                     if(count($arr_subparcial)>0){
                        foreach($arr_subparcial as $id_sub){
                           ////Para el subparcial se debe obtener la nota de cada submateria
                           $sPeticionSQL="SELECT * from sa_materia_sub WHERE id_subparcial=$id_sub and ".
                                       "id_materia_periodo_lectivo=$id_materia_periodo_lectivo and estatus in ('A','B')";
                           $recordset=$db->getFirstRowQuery($sPeticionSQL,true);
                              if(is_array($recordset) && count($recordset)>0){
                                 $valor=$this->calcular_submateria($recordset['id'],$id_alumno_materia);
                                 $formula = ereg_replace("\{SUB_".$id_sub."\}","$valor",$formula);
                              }
                              else
                                 $formula = ereg_replace("\{SUB_[[:digit:]]+\}","0",$formula);  //en caso de que no existan subparciales creados se reemplaza con cero
                        }
                     }
                     else{
                        ////Si no hay inicializadas las submaterias se debe reemplazar toda ocurrencia de {SUB_numero} con cero
                        $formula = ereg_replace("\{SUB_[[:digit:]]+\}","0",$formula);
                     }


                  $formula=$this->reemplazar_anidacion($formula);
                  $nota=$this->evaluar($formula);





















                  return $nota;
                  break;

            case "N":   //En caso de no ser calificado (espacio)
            default:
                  return "";
                  break;

         }


   }
   else{
      $this->setMessage("No se pudo obtener informacion del parcial con id: $id_parcial. Error:".$db->errMsg);
      return FALSE;
   }



}



function reemplazar_anidacion($str){
$patron="\[([[:digit:]\+\-\*\/\. \|\{\}\(\)\<\>\=\!]+|and|or|SUB_)+\]";
   if(ereg($patron,$str,$arr)){
      $subpatron=ereg_replace("([\{\}\(\)\>\<\=\+\-\*\/\|])","\\\\1",$arr[0]);
      $subpatron=ereg_replace("(\[)","\\\\1",$subpatron);
      $subpatron=ereg_replace("(\])","\\\\1",$subpatron);
      $str= ereg_replace($subpatron,"".$this->evaluar($arr[1])."",$str);
      $str=$this->reemplazar_anidacion($str);
      return $str;
   }
   else
      return $str;
}


function evaluar($formula){
$arr_enunciados=explode('|', $formula);
$nota="";
   if(count($arr_enunciados)==3){  ///Si existen 3 enunciados (condicion, opcion verdadero, opcion falso)
      eval("\$val_condicion=".$arr_enunciados[0].";");
         if(isset($val_condicion) && $val_condicion)
            eval("\$nota=".$arr_enunciados[1].";");
         else
            eval("\$nota=".$arr_enunciados[2].";");
   }
   else{
      $formula="\$nota=".$formula.";";
      eval($formula);
   }
return $nota;
}








function calcular_subparcial($id_subparcial,$id_materia_periodo_lectivo,$id_alumno_materia){

$db=$this->_db;

////Para el subparcial se debe obtener cada submateria
$sPeticionSQL="SELECT * from ul_calificable WHERE id_subparcial=$id_subparcial and ".
              "id_materia_periodo_lectivo=$id_materia_periodo_lectivo and estatus='A'";
$result=$db->fetchTable($sPeticionSQL,true);
$sumatoria=0;
   if(is_array($result))
   {
         if(count($result)>0)
         {
               foreach($result as $fila)
               {
                  $id_calificable=$fila['id_calificable'];
                  $ponderacion=$fila['ponderacion'];
                  $nota=$this->calcular_submateria($id_calificable,$id_alumno_materia);
                     if($nota===FALSE)
                        return FALSE;
                  $sumatoria+=$ponderacion*$nota;


               }


         }

      return $sumatoria;
   }
   else{
      $this->setMessage("No se pudo obtener información de Submaterias para el subparcial con id:$id_subparcial. Error:".$db->errMsg);
      return FALSE;
   }


}






function obtener_submaterias($id_subparcial,$id_materia_periodo_lectivo,$id_alumno_materia){

$db=$this->_db;

////Para el subparcial se debe obtener cada submateria
$sPeticionSQL="SELECT * from ul_calificable WHERE id_subparcial=$id_subparcial and ".
              "id_materia_periodo_lectivo=$id_materia_periodo_lectivo and estatus= 'A'";
$result=$db->fetchTable($sPeticionSQL,true);
$arr_submaterias=array();
   if(is_array($result))
   {
         if(count($result)>0)
         {
               foreach($result as $fila)
               {
                  $id_calificable=$fila['id_calificable'];
                  $nombre=$fila['titulo'];
                  $ponderacion=$fila['ponderacion'];
                  $nota=$this->calcular_submateria($id_calificable,$id_alumno_materia);

                  $arr_submaterias[]=array("id_calificable"=>$id_calificable,"nombre"=>$nombre,"nota"=>$nota,"ponderacion"=>$ponderacion);

               }


         }

      return $arr_submaterias;
   }
   else{
      $this->setMessage("No se pudo obtener información de Submaterias para el subparcial con id:$id_subparcial. Error:".$db->errMsg);
      return FALSE;
   }


}


/**********************************************************************************************************************/


function obtener_nota_final($id_materia_periodo_lectivo,$id_alumno_materia){
////Primero se debe buscar el id_parcial con n_final=1
$db=$this->_db;
$sQuery="SELECT p.id FROM ul_grupo_parcial gp, ul_parcial p, ul_materia_periodo_lectivo mpl ".
        "WHERE mpl.id=$id_materia_periodo_lectivo and mpl.id_periodo_lectivo=gp.id_periodo_lectivo ".
        "and p.id_grupo_parcial=gp.id and p.n_final=1";
$result=$db->getFirstRowQuery($sQuery,true);
   if(is_array($result) && count($result)>0){
      $id_parcial=$result['id'];
      $nota_final=$this->calcular_parcial($id_parcial,$id_materia_periodo_lectivo,$id_alumno_materia);
      return $nota_final;
   }
   else{
      $this->setMessage("No se pudo obtener la informacion del parcial asignado como nota final. Error:".$db->errMsg);
      return FALSE;
   }

}


/**********************************************************************************************************************/




function calcular_submateria($id_calificable,$id_alumno_materia){
$db=$this->_db;
/////Ahora se busca la nota mas reciente de la submateria
$sQuery="SELECT puntuacion FROM ul_alumno_calificable ".
         "WHERE id_calificable=$id_calificable and id_alumno_materia=$id_alumno_materia";
$res=$db->getFirstRowQuery($sQuery);
$nota=0;
         ///Se obtiene la nota ordenada por fecha desde la mas reciente hacia atras. Se obtiene la primera fila
   if(is_array($res))
   {  if(count($res)>0)
         $nota=$res[0];
   }
   else{
      $this->setMessage("No se pudo obtener informacion de la submateria. Error".$db->errMsg);
      return FALSE;
   }

return $nota;

}














function getParcialIDs($str){
$arreglo=array();


   while(strpos($str,"{")!==false && strpos($str,"}")!==false){

      $pos1=strpos($str,"{");
      $pos2=strpos($str,"}");
      $p1=$pos1+1;
      $p2=$pos2-1;
      $ln=$p2-$p1+1;

      //Se obtiene la porcion del arreglo entre pos1 y pos2
      $id_parcial=substr($str,$p1,$ln);
      $id_parcial=intval($id_parcial);
         if(is_int($id_parcial))
            $arreglo[]=$id_parcial;
      //Se reemplaza por '' el substr leido
      $length=$pos2-$pos1+1;
      $str=substr_replace($str,' ',$pos1,$length);

   }

return $arreglo;

}



function getSubParcialIDs($str){
$arreglo=array();

   while(strpos($str,"{SUB_")!==false && strpos($str,"}")!==false){

      $pos1=strpos($str,"{SUB_");
      $pos2=strpos($str,"}");
      $p1=$pos1+1+4;
      $p2=$pos2-1;
      $ln=$p2-$p1+1;

      //Se obtiene la porcion del arreglo entre pos1 y pos2
      $id_subparcial=substr($str,$p1,$ln);
      $id_subparcial=intval($id_subparcial);
         if(is_int($id_subparcial))
            $arreglo[]=$id_subparcial;
      //Se reemplaza por '' el substr leido
      $length=$pos2-$pos1+1;

      $str=substr_replace($str,' ',$pos1,$length);

   }

return $arreglo;

}


}









class ul_docente_materia_reporte extends PaloReporte
{
    function ul_docente_materia_reporte(&$oDB, &$oPlantillas, $sBaseURL)
    {   $this->PaloReporte($oDB, $oPlantillas);
     // ComboBox de las Periodo Lectivo
        $id_periodo_lectivo=recoger_valor("id_periodo_lectivo",$_GET,$_POST);
        $id_docente=recoger_valor("id_docente",$_GET,$_POST);

        $sQuery = getSQLData('PERIODO_LECTIVO_DESC', "estatus not in ('C','F')");
        $periodo_field = getSelectInputFromQuery($sQuery, $oDB, 'id_periodo_lectivo', 'onChange="if (this.value!=\'\') submit();"', $id_periodo_lectivo, '-- Sel. P. Lectivo --');

        /////Si el usuario es un docente se le debe mostrar solo las materias que tiene asignadas

        $sQuery =$this->obtener_lista_docentes($id_docente);

        $combo_docente = getSelectInputFromQuery($sQuery, $oDB, 'id_docente', 'onChange="submit();"', $id_docente, '-- Seleccione un Docente --');


        $sClause="";

         if($id_periodo_lectivo>0)
            $sClause.="and mpl.id_periodo_lectivo=$id_periodo_lectivo ";

         if($id_docente>0){
            $sClause.="and mpl.id_docente=$id_docente";
         }




        if (!$this->definirReporte("LISTA_DOCENTE_MATERIAS", array(
        //"DEBUG"=>true,
            "TITLE"         =>  "Listado de Materias x Docente<br>\n",
            "DATA_COLS"     =>  array(
                                    "ID_DOCENTE"=>"d.id",
                                    "DOCENTE"=>"concat(d.nombre,' ',d.apellido)",
                                    "CEDULA"=>"d.cedula",
                                    "APELLIDO"=>"d.apellido",
                                    "NOMBRE"=>"d.nombre",
                                    "PERIODO"=>"pl.nombre",
                                    "MATERIA"=>"m.nombre",
                                    "PARALELO"=>"mpl.paralelo",
                                    "ID_MATERIA_PERIODO_LECTIVO"=>"mpl.id",
                                 ),
            "PRIMARY_KEY"   =>  array("ID_DOCENTE"),
            "FROM"          =>  "ul_docente d, ul_materia_periodo_lectivo mpl, ul_materia m, ul_periodo_lectivo pl",
            "CONST_WHERE"   =>  "mpl.id_materia=m.id and mpl.id_docente=d.id and mpl.id_periodo_lectivo=pl.id $sClause",
            "FILTRO"        =>  "<div class=filter_normal><font size=-1>Periodo Lectivo: </font>$periodo_field <font size=-1> &nbsp; Docente: </font>$combo_docente</div>",
            "PAGECHOICE"    =>  array(20,40,60),
            "ORDERING"      =>  array(
                                    "DEFAULT"   =>  array("ID_DOCENTE", "DOCENTE", "MATERIA","PARALELO"),
                                ),
            "BASE_URL"      =>  $sBaseURL,
            "HEADERS"       =>  array(
                                    "Cédula",
                                    "Docente",
                                    "Materia",
                                    "Paralelo",
                                    "Nº <br>Alumnos",
                                    "Periodo",
                                    "",
                                ),
            "ROW"           =>  array(
                                    "{_DATA_CEDULA}",
                                    "{_DATA_DOCENTE}",
                                    "{_DATA_MATERIA}",
                                    "{_DATA_PARALELO}",
                                    "{_DATA_N_ALUMNOS}",
                                    "{_DATA_PERIODO}",
                                    "{_DATA_MODIFICAR}",
                                ),
        ))) die ("ul_docente_materia_reporte::ul_docente_materia_reporte() - al definir reporte LISTA_DOCENTE_MATERIAS - ".$this->_msMensajeError);

    }



///////*****eventos para  filtro reporte

    function event_recogerVariablesFiltro($sNombreReporte, $_GET, $_POST)
    {
        switch ($sNombreReporte) {
        case 'LISTA_DOCENTE_MATERIAS':
            $varFiltro = array();
            $varFiltro['in_patron']=recoger_valor('in_patron', $_GET, $_POST);
            $varFiltro['in_columna']=recoger_valor('in_columna', $_GET, $_POST);

            // Recoger cadena y columna de búsqueda, si existen
            if (isset($varFiltro['in_patron'])) {
                $varFiltro['in_patron'] = trim($varFiltro['in_patron']);
                if ($varFiltro['in_patron'] == '') unset($varFiltro['in_patron']);
                if (!in_array($varFiltro['in_columna'], array('d.cedula','d.nombre','d.apellido','m.nombre','mpl.paralelo')))
                    unset($varFiltro['in_columna']);

            }
            if (!isset($varFiltro['in_columna'])) unset($varFiltro['in_patron']);
            if (!isset($varFiltro['in_patron'])) unset($varFiltro['in_columna']);


            return $varFiltro;
        default:
            return array();
        }
    }

    /**
     * Procedimiento a sobrecargar en subclases del reporte, que construye la condicion SQL
     * a agregar al final de la petición SQL del reporte, según las variables del filtro recogidas
     * por event_recogerVariablesFiltro()
     *
     * @param string    $sNombreReporte Nombre del reporte para el que se proveen las columnas
     * @param array     $varFiltro      Variables provistas por event_recogerVariablesFiltro()
     *
     * @return string   Cadena que expresa una condición WHERE de un SELECT de SQL, por ejemplo,
     *  "tabla1.col1 = 4 AND tabla2.col4 = 8"
     *
     */
    function event_construirCondicionFiltro($sNombreReporte, $varFiltro)
    {
        switch ($sNombreReporte) {
        case 'LISTA_DOCENTE_MATERIAS':
            $sCondicionWHERE = "";

            // Verificar si se debe filtrar por cadenas
            if (isset($varFiltro['in_patron'])) {
                $oDB = $this->getDB();
                $sPatronBusqueda = paloDB::DBCAMPO("%".$varFiltro['in_patron']."%");
                   if ($sCondicionWHERE != "") $sCondicionWHERE .= " AND ";
                $sCondicionWHERE .= $varFiltro['in_columna']." LIKE $sPatronBusqueda";
            }
            // Guardar el filtro para que sea referenciado al construir la tabla
            $this->varFiltro = $varFiltro;

            return $sCondicionWHERE;
        default:
            return "";
        }
    }

    /**
     * Procedimiento a sobrecargar en subclases del reporte, que construye el código HTML a
     * mostrar para ilustrar el estado del filtro aplicado al reporte mostrado. El código
     * devuelto se concatena al código HTML del miembro FILTRO de la definición del reporte.
     *
     * @param string    $sNombreReporte Nombre del reporte para el que se proveen las columnas
     * @param array     $varFiltro      Variables provistas por event_recogerVariablesFiltro()
     *
     * @return string   Cadena que contiene el HTML del filtro con el estado indicado por $varFiltro
     */
    function event_construirFormularioFiltro($sNombreReporte, $varFiltro)
    {
        switch ($sNombreReporte) {
        case 'LISTA_DOCENTE_MATERIAS':
            if(isset($varFiltro['in_columna']))
               $selected=$varFiltro['in_columna'];
            else
               $selected="";
            if(isset($varFiltro['in_patron']))
               $patron=$varFiltro['in_patron'];
            else
               $patron="";

            $arrColumna=array(
                           'd.cedula'=>'Cédula',
                           'd.nombre'=>'Nombre Docente',
                           'd.apellido'=>'Apellido Docente',
                           'm.nombre'=>'Materia',
                           'mpl.paralelo'=>'Paralelo'
                           );

                  $comboColumna="<select name='in_columna'>".combo($arrColumna,$selected).
                           "</select>";

                  $strTable= "<table border=0>
                           <tr class='table_title_row'>
                     <td>Columna:</td>
                     <td>$comboColumna</td>
                     <td>Patrón:</td>
                     <td><input type='text' name='in_patron' value='$patron'></td>
                     <td><input type='submit' name='buscar' value='Buscar'></td>
               </tr></table>";
         return $strTable;
        default:
            return "";
        }
    }











    /**
     * Procedimiento que muestra condicionalmente el enlace a la modificacin de la cuenta.
     * como una fila adicional de datos.
     *
     * @param string $sNombreReporte Nombre del reporte para el que se proveen las columnas
     * @param array  $tuplaSQL       Tupla con los valores a usar para la fila actual
     *
     * @return array    Valores a agregar a la tupla existente de SQL
     */
    function event_proveerCampos($sNombreReporte, $tuplaSQL)
    {   $oACL=getACL();
        $db=$this->getDB();

        switch ($sNombreReporte) {
        case "LISTA_DOCENTE_MATERIAS":
         $link_input=$link_reactivar=$link_modificar=$link_clave="";

            //Se obtiene el número de alumnos en el curso

            $sQuery="select count(*) from ul_alumno_materia WHERE id_materia_periodo_lectivo=".$tuplaSQL['ID_MATERIA_PERIODO_LECTIVO'];
            $result=$db->getFirstRowQuery($sQuery);
               if(is_array($result) && count($result)>0){
                  $n_alumnos=$result[0];
               }
               else
                  $n_alumnos="";

            if($n_alumnos>0)
               $link_modificar="<a href=\"?action=notas&id_docente=".$tuplaSQL["ID_DOCENTE"]."&id_materia_periodo_lectivo=".$tuplaSQL["ID_MATERIA_PERIODO_LECTIVO"]."\">Notas</a>";



            return array("MODIFICAR"=>$link_modificar,
                         "N_ALUMNOS"=>$n_alumnos);

            break;
        default:
            return array();
        }
    }




   function obtener_lista_docentes(&$id_docente){
      $db=$this->getDB();
      $oACL=getACL();

      $login=$_SESSION['session_user'];
      $id_user=$oACL->getIdUser($login);  //Se obtiene el id_user
      $id_grupo=$this->obtener_grupo_usuario($oACL,$login); //Se obtiene el id_grupo
      $grupo=getEnumDescripcion("Grupo",$id_grupo);  //Se obtiene el nombre del grupo asociado a id_grupo
         switch($grupo){
            case "docente":
                  $sQuery="SELECT id,concat(apellido,' ',nombre) FROM ul_docente where id_acl_user=$id_user";
                     if(is_null($id_docente) || $id_docente==""){
                        //Se debe buscar el id del docente en base al id_user
                        $result=$db->getFirstRowQuery($sQuery);
                           if(is_array($result) && count($result)>0)
                              $id_docente=$result[0];
                     }

                  break;
            default:
                  $sQuery="SELECT id,concat(apellido,' ',nombre) FROM ul_docente order by apellido,nombre";
         }
      return $sQuery;
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



}
?>
