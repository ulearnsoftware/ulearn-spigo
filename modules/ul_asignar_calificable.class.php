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
// $Id: ul_asignar_calificable.class.php,v 1.1.1.1 2006/03/03 21:59:09 ainiguez Exp $

if (isset($gsRutaBase)){
     require_once ("$gsRutaBase/conf/default.conf.php");
     require_once ("$gsRutaBase/lib/paloEntidad.class.php");
     require_once ("$gsRutaBase/lib/paloACL.class.php");
}
else{
   require_once ("conf/default.conf.php");
   require_once ("lib/paloEntidad.class.php");
   require_once ("lib/paloACL.class.php");
}

class ul_asignar_calificable
{
   var $sBaseURL;
   var $id_mpl;
   var $id_calf;
   var $oDB;
   var $tpl;
   var $errMsg;

   function ul_asignar_calificable(&$oDB,&$tpl,$sBaseURL,$id_materia_periodo_lectivo,$id_calificable)
   {
      $this->sBaseURL=$sBaseURL;
      $this->oDB=$oDB;
      $this->id_mpl=$id_materia_periodo_lectivo;
      $this->id_calf=$id_calificable;
      $this->tpl=$tpl;
   }

   function asignar_calificable()
   {
      /*if(!checkdate(recoger_valor("inicio_mes",$_GET,$_POST),recoger_valor("inicio_dia",$_GET,$_POST),recoger_valor("inicio_anio",$_GET,$_POST))){
         return $this->tpl->crearAlerta(
            "error",
            "Problema al Asignar Calificables",
            "La fecha de inicio no es valida.<br/>\n".$this->oDB->errMsg);
      }
      if(!checkdate(recoger_valor("cierre_mes",$_GET,$_POST),recoger_valor("cierre_dia",$_GET,$_POST),recoger_valor("cierre_anio",$_GET,$_POST))){
         return $this->tpl->crearAlerta(
            "error",
            "Problema al Asignar Calificables",
            "La fecha de cierre no es valida.<br/>\n".$this->oDB->errMsg);
      }*/
      //echo "<pre>";print_r($_POST);echo "</pre>";echo "<pre>";print_r($_GET);echo "</pre>";
      $asignar=recoger_valor("asignar",$_GET,$_POST);
      $fecha_inicio=recoger_valor("fecha_inicio",$_GET,$_POST);
      $fecha_cierre=recoger_valor("fecha_cierre",$_GET,$_POST);

      switch($asignar){
      case "Asignar a Todos":
         $sQuery = "SELECT id FROM ul_alumno_materia WHERE id_materia_periodo_lectivo=".$this->id_mpl;
         $result = $this->oDB->fetchTable($sQuery,TRUE);
         if(is_array($result) && count($result)>0){
            foreach($result as $i=>$value){
               ////Solo si no existe una asignacion previa se realiza la insercion
               if(!$this->verificar_existencia($value['id'],$this->id_calf)){
                  $sQuery="INSERT INTO ul_alumno_calificable (fecha_inicio,fecha_cierre,id_alumno_materia,id_calificable) ".
                        "VALUES ('$fecha_inicio','$fecha_cierre',".$value['id'].",".$this->id_calf.")";
                  $result = $this->oDB->genQuery($sQuery);
                  if($result===FALSE){
                     return $this->tpl->crearAlerta(
                        "error",
                        "Problema al Asignar Calificables",
                        "Al asignar calificable: ".$this->oDB->errMsg);
                  }
               }
            }
         }
         break;

      case "Asignar Seleccionados":

         $id_alumno_materia = recoger_valor("id_alumno_materia",$_GET,$_POST);
         if($id_alumno_materia==NULL){
               return $this->tpl->crearAlerta(
                  "error",
                  "Problema al Asignar Calificables",
                  "No ha seleccionado algun alumno.<br />\n".$this->oDB->errMsg);
         }
         if(!is_array($id_alumno_materia) || count($id_alumno_materia)==0){
               return $this->tpl->crearAlerta(
                  "error",
                  "Problema al Asignar Calificables",
                  "El dato no es valido.<br />\n".$this->oDB->errMsg);
         }

         foreach($id_alumno_materia as $i=>$value){
          ////Solo si no existe una asignacion previa se realiza la insercion
               if(!$this->verificar_existencia($value,$this->id_calf)){
                  $sQuery="INSERT INTO ul_alumno_calificable (fecha_inicio,fecha_cierre,id_alumno_materia,id_calificable) ".
                           "VALUES ('$fecha_inicio','$fecha_cierre',$value,".$this->id_calf.")";

                  $result = $this->oDB->genQuery($sQuery);
                     if($result===FALSE){
                        return $this->tpl->crearAlerta("error","Problema al Asignar Calificables","Ya ha sido asignado.<br />\n".$this->oDB->errMsg);
                     }
               }
         }
         break;
      }
      return "";
   }


   function verificar_existencia($id_alumno_materia,$id_calificable){
      $db=$this->oDB;
      $sQuery="SELECT * FROM ul_alumno_calificable WHERE id_alumno_materia=$id_alumno_materia and id_calificable=$id_calificable and estatus<>'A'";
      $result=$db->getFirstRowQuery($sQuery);
         if(is_array($result) && count($result)>0){
            $this->errMsg="El alumno ya tiene asignado el calificable";
            return TRUE;
         }
         else
            return FALSE;
   }
}

?>
