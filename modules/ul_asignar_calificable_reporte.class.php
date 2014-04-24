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
// $Id: ul_asignar_calificable_reporte.class.php,v 1.1.1.1 2006/03/03 21:59:09 ainiguez Exp $

require_once ("lib/paloEntidad.class.php");
require_once ("lib/paloACL.class.php");
require_once ("datetime.class.php");


class ul_asignar_calificable_reporte extends PaloReporte{
   var $oDB;
   var $id_mpl;
   var $sBaseURL;

   function ul_asignar_calificable_reporte(&$oDB, $oPlantillas, $sBaseURL,$id_materia_periodo_lectivo,$id_calificable)
   {
      global $config;
      $this -> PaloReporte($oDB, $oPlantillas);
      $oACL=getACL();
      setLocale(LC_TIME,$config->locale);

      $this->oDB = $oDB;
      $this->id_mpl = $id_materia_periodo_lectivo;
      $this->sBaseURL = $sBaseURL;
   }

   function generarReporte($sNombreReporte)
   {
      $tabla_cabecera_calificable = $this->generar_cabecera_opciones($sNombreReporte);

      switch($sNombreReporte){
      case "ASIGNAR_CALIFICABLES":
         $id_calificable=recoger_valor("id_calificable",$_GET,$_POST);
         //////////// Verificacion de perfil de usuario para mostrar columnas con input
         //if($oACL->getMembership($oACL->getIdUser($_SESSION['session_user']))=="alumno")
         $clauseWhere=$clauseWhere2="";
         if($id_calificable>0)
            $clauseWhere="and ac.id_calificable=$id_calificable";
         else
            $clauseWhere2="and am.id_materia_periodo_lectivo=0";


         if (!$this->definirReporte("ASIGNAR_CALIFICABLES", array(
            //"DEBUG"=>true,
            "TITLE"         =>  "Asignacion de Calificables<br>\n".
                                 "<input type='hidden' name='id_materia_periodo_lectivo' value=".$this->id_mpl.">",
            "FILTRO"        =>  $tabla_cabecera_calificable,
            "PAGECHOICE"    =>  array(560),
            // Solo las utilizadas en el select
            "DATA_COLS"     =>  array(
                                    "ID_ALUMNO_CALIFICABLE" => "ac.id_alumno_calificable",
                                    "ID_ALUMNO"             => "a.id",
                                    "ID_ALUMNO_MATERIA"     => "am.id",
                                    "ALUMNO"                => "CONCAT(a.apellido,' ',a.nombre)",
                                    "NOMBRE"                => "a.nombre",
                                    "APELLIDO"              => "a.apellido",
                                    "EMAIL"                 => "a.email",
                                    "ESTATUS"               => "ac.estatus",
                                    "CALIFICABLE"           => "c.titulo",
                                    "ID_CALIFICABLE"        =>  "c.id_calificable",
                                    "FECHA_INICIO"          => "ac.fecha_inicio",
                                    "FECHA_CIERRE"          => "ac.fecha_cierre",
                                    "FECHA_REALIZACION"     => "ac.fecha_realizacion",
                                    "FECHA_TERMINACION"     => "ac.fecha_terminacion",
                                ),
            "PRIMARY_KEY"   =>  array("ID_ALUMNO"),
            "FROM"          =>  "ul_alumno a, ul_alumno_materia am ".
                                 "LEFT JOIN ul_alumno_calificable ac ON am.id = ac.id_alumno_materia $clauseWhere ".
                                 "LEFT JOIN ul_calificable c ON ac.id_calificable=c.id_calificable",
            "CONST_WHERE"   =>  "am.id_alumno = a.id AND am.id_materia_periodo_lectivo =".$this->id_mpl." $clauseWhere2",
            "ORDERING"      =>  array(
                                    "DEFAULT"   =>  array("APELLIDO", "NOMBRE"),
                                ),
            "BASE_URL"      =>  $this->sBaseURL,
            "HEADERS"       =>  array("","CALIFICABLE","FECHA_INICIO","FECHA_CIERRE","ALUMNO","F_REALIZACION","F_TERMINACION","ESTATUS",""),
            "ROW"           =>  array(
                                    array("{_DATA_INPUT}",'ALIGN'=>'CENTER'),
                                    array("{_DATA_CALIFICABLE}","STYLE"=>"font-size:8pt"),
                                    array("{_DATA_FECHA_INICIO}","STYLE"=>"font-size:8pt"),
                                    array("{_DATA_FECHA_CIERRE}","STYLE"=>"font-size:8pt"),
                                    array("{_DATA_ALUMNO}","ALIGN"=>"LEFT","STYLE"=>"font-size:7pt"),
                                    array("{_DATA_FECHA_REALIZACION}","STYLE"=>"font-size:8pt"),
                                    array("{_DATA_FECHA_TERMINACION}","STYLE"=>"font-size:8pt"),
                                    array("{_DATA_ESTATUS}","ALIGN"=>"CENTER"),
                                    array("{_DATA_MODIFICAR}","ALIGN"=>"CENTER"),
                                    )
         ))) die ("ul_calificable_reporte: - al definir reporte LISTA_CALIFICABLES - ".$this->_msMensajeError);
         break;
      }
      return parent::generarReporte($sNombreReporte, $_GET,  $_POST);
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
    {
      global $config;
      $oACL=getACL();
      $input=$apellido=$nombre=$email="";

      //echo "<pre>";print_r($tuplaSQL);echo "</pre>";

      switch ($sNombreReporte) {
         case "ASIGNAR_CALIFICABLES":
            $input="<input type='checkbox' name='id_alumno_materia[".$tuplaSQL['ID_ALUMNO_MATERIA']."]' value='".$tuplaSQL['ID_ALUMNO_MATERIA']."'>";
            $apellido=$tuplaSQL['APELLIDO'];
            $nombre=$tuplaSQL['NOMBRE'];
            $email=$tuplaSQL['EMAIL'];
            $estatus=$tuplaSQL['ESTATUS'];
            $link_anular   ="<a onClick=\"return confirm('Esta seguro que desea anular el calificable realizado por el alumno?')\" ".
                            "href='?menu1op=submenu_calificable&submenuop=calf_asignar&id_materia_periodo_lectivo=".$this->id_mpl.
                            "&action=anular&id_alumno_calificable=".$tuplaSQL['ID_ALUMNO_CALIFICABLE']."&id_calificable=".$tuplaSQL['ID_CALIFICABLE']."'>Anular</a>";
            $modificar="";
                switch($estatus){
                  case 'N':
                     $estatus='No Realizado';
                     break;
                  case 'V':
                     $estatus="<div style='color:#aa5500'>Visto</div>";
                     $modificar=$link_anular;
                     break;
                  case 'T':
                     $estatus="<div style='color:green;'>Terminado";
                     $modificar=$link_anular;
                     break;
                  case 'A':
                     $estatus='Anulado';
                     break;
                  default:
                     $estatus="<div style='color:red;'>No Asignado</div>";
               }

            return array("INPUT"    => $input,
                         "APELLIDO" => $apellido,
                         "NOMBRE"   => $nombre,
                         "EMAIL"    => $email,
                         "ESTATUS"  => $estatus,
                         "MODIFICAR"=> $modificar,
                         );
         default:
      }

      return array("INPUT"    => $input,
                   "APELLIDO" => $apellido,
                   "NOMBRE"   => $nombre,
                   "EMAIL"    => $email,
                   );
   }

   ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
   ////////Funciones de Eliminacion
   ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
   function eliminar_asignacion($id_calificable,$arr_alumno_materia){
      $oACL=getACL();
      $oDB=$this->_db;

         if (!$oACL->isUserAuthorized($_SESSION['session_user'], 'delet', 'calf_lista')){
            $this->setMessage("Usted no está autorizado para realizar esta acción.");
            return FALSE;
         }

         if($arr_alumno_materia==NULL){
               $this->setMessage("No ha seleccionado ningún alumno.");
               return FALSE;
         }
         if(!is_array($arr_alumno_materia) || count($arr_alumno_materia)==0){
            $this->setMessage("El dato no es valido.");
            return FALSE;
         }
      $error="";
         foreach($arr_alumno_materia as $i=>$value){
          ////Solo se debe borrar si el estado es N (No visto)
               $sQuery="DELETE FROM ul_alumno_calificable WHERE id_calificable=$id_calificable ".
                       "and id_alumno_materia=$value and estatus='N'";
               $bValido=$oDB->genQuery($sQuery);
                  if(!$bValido)
                     $error.="No se pudo eliminar el registro.".$oDB->errMsg."<br>";
         }
      if($error!=""){
         $this->setMessage($error);
         return FALSE;
      }
      else
         return TRUE;


   }

   ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
   ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

   function anular_asignacion($id_alumno_calificable){
      $oACL=getACL();
      $oDB=$this->_db;

         if (!$oACL->isUserAuthorized($_SESSION['session_user'], 'updat', 'calf_lista')){
            $this->setMessage("Usted no está autorizado para realizar esta acción.");
            return FALSE;
         }


      $error="";
         if($id_alumno_calificable>0){
          ////Solo se debe borrar si el estado es N (No visto)
               $sQuery="UPDATE ul_alumno_calificable SET estatus='A' WHERE id_alumno_calificable=$id_alumno_calificable";
               $bValido=$oDB->genQuery($sQuery);
                  if(!$bValido)
                     $error.="No se pudo actualizar el registro.".$oDB->errMsg."<br>";
         }
         else
            $error="No se recibio un id_alumno_calificable válido";

      if($error!=""){
         $this->setMessage($error);
         return FALSE;
      }
      else
         return TRUE;

   }


   ////////////////////////////////////////////////////////////////////////////////////////////////////////////////

   function generar_cabecera_opciones($nombreFormulario){
      //Se debe mostrar los botones para modificar, eliminar e ingresar dependiendo de los privilegios del usuario
      $oACL=getACL();
      $sContenido="";

      switch($nombreFormulario){
      case "ASIGNAR_CALIFICABLES":
            $fecha_inicio=$fecha_cierre="";
            $sContenido.="<table><tr><td>Calificable</td><td>";

            $array=array(NULL=>"-- Seleccione un Calificable --");
            $sQuery = "SELECT * FROM ul_calificable WHERE id_materia_periodo_lectivo=".$this->id_mpl;
            $result = $this->oDB->fetchTable($sQuery,TRUE);
               if(is_array($result) && count($result)>0){
                  foreach($result as $i=>$value){
                        $array[$value['id_calificable']]=$value['titulo'];
                  }
               }
            $id_calificable=recoger_valor("id_calificable",$_GET,$_POST);

               if($id_calificable>0){
                   ///Si hay seleccionado un calificable se debe buscar la fecha de inicio y fin
                  $sQuery = "SELECT * FROM ul_calificable WHERE id_calificable=".$id_calificable;
                  $result = $this->oDB->getFirstRowQuery($sQuery,TRUE);
                     if(is_array($result) && count($result)>0){
                        $fecha_inicio=$result['fecha_inicio'];
                        $fecha_cierre=$result['fecha_cierre'];
                     }
               }

            $sContenido.="<select name='id_calificable' onChange='submit();'>";
            $sContenido.=combo($array,$id_calificable);
            $sContenido.="</select>";
            $sContenido.="</td></tr></table>";
            $sContenido.="<table>";
            $sContenido.="<tr><td>Fecha Inicio: &nbsp;".
                         "<input type='hidden' name='fecha_inicio' value='$fecha_inicio'></td>".
                         "<td>&nbsp;$fecha_inicio</td>";
            $sContenido.="<tr><td>Fecha Cierre: &nbsp;".
                         "<input type='hidden' name='fecha_cierre' value='$fecha_cierre'></td>".
                         "<td>&nbsp;$fecha_cierre</td>";
            $sContenido.="</table>";

            /*$sContenido.="<table>";
            $sContenido.="<tr><td>Fecha Inicio:</td><td>";
            $sContenido.=get_datetime("inicio", 1);
            $sContenido.="</td></tr>";
            $sContenido.="</table>";
            $sContenido.="<table>";
            $sContenido.="<tr><td>Fecha Cierre:</td><td>";
            $sContenido.=get_datetime("cierre", 1);
            $sContenido.="</td></tr>";
            $sContenido.="</table>";*/
            $sContenido.="<br>";
               if($oACL->isUserAuthorized($_SESSION['session_user'], 'creat', 'calf_asignar')){
                  $sContenido.="<input type='submit' name='asignar' value='Asignar a Todos' />";
                  $sContenido.="&nbsp;";
                  $sContenido.="<input type='submit' name='asignar' value='Asignar Seleccionados' />";
                  $sContenido.="&nbsp;";
               }
               if($oACL->isUserAuthorized($_SESSION['session_user'], 'delet', 'calf_asignar')){
                   $sContenido.="<input type='submit' name='eliminar' value='Eliminar Asignación' ".
                           "onClick=\"return confirm('Está seguro que desea eliminar la asignación de los alumnos seleccionados?. ".
                           "Sólo se podrá eliminar una asignación si el calificable NO ha sido VISTO por el estudiante. ".
                           "Caso contrario deberá anularlo.')\"/>";
               }
            return $sContenido;

         default:
      }


      return $sContenido;
   }


}
?>
