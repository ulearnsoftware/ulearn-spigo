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
// | Autores: Alvaro Iñiguez <a_iniguez@palosanto.com>                    |
// |                                                                      |
// +----------------------------------------------------------------------+
//
// $Id: ul_materia_periodo_lectivo.class.php,v 1.1.1.1 2006/03/03 21:59:08 ainiguez Exp $

require_once ("lib/paloEntidad.class.php");
require_once ("lib/paloReporte.class.php");


class ul_materia_periodo_lectivo{
var $_db;

function ul_materia_periodo_lectivo(&$oDB){

   $this->_db=$oDB;

}

function cabecera_seleccion($login,&$_GET, &$_POST, &$id_materia_periodo_lectivo,$baseURL=""){
$db=$this->_db;
$oACL=getACL();
$id_user=$oACL->getIdUser($login);  //Se obtiene el id_user
$id_grupo=obtener_grupo_usuario($oACL,$login); //Se obtiene el id_grupo
$grupo=getEnumDescripcion("Grupo",$id_grupo);  //Se obtiene el nombre del grupo asociado a id_grupo

$sCodigoTabla="";
$sQuery="";



   switch($grupo){    //Dependiendo del tipo de grupo al que pertenece el usuario se buscan las materias a mostrar en el combo
       case "administrador":
       case "decano":
             ///En el caso de que el usuario sea administrador (o decano) se muestran todas las materias activas
             $sQuery="SELECT mpl.id as id_materia_periodo_lectivo,mpl.paralelo,m.nombre ".
                  "FROM ul_materia m, ul_materia_periodo_lectivo mpl ".
                  "WHERE mpl.id_materia=m.id and mpl.estatus='A' and mpl.abierto='1'".
                  "ORDER BY m.nombre, mpl.paralelo ASC";
             break;

       case "docente":
            $sPeticionSQL="SELECT id from ul_docente where id_acl_user=$id_user";
            $result=$db->getFirstRowQuery($sPeticionSQL);
               if(is_array($result) && count($result)>0){
                  $id_docente=$result[0];
                  $sQuery="SELECT mpl.id as id_materia_periodo_lectivo,mpl.paralelo,m.nombre ".
                           "FROM ul_materia m, ul_materia_periodo_lectivo mpl ".
                           "WHERE mpl.id_materia=m.id and mpl.estatus='A' and mpl.abierto='1' and mpl.id_docente=$id_docente ".
                           "ORDER BY m.nombre, mpl.paralelo ASC";
               }
             break;

       case "representante":
             break;

       case "alumno":
             ///////En el caso de que sea un alumno, primero se obtiene el id de alumno en base al login
	         $sPeticionSQL="SELECT id from ul_alumno where id_acl_user=$id_user";
	         $result=$db->getFirstRowQuery($sPeticionSQL);
               if(is_array($result) && count($result)>0){
                  $id_alumno=$result[0];
                  $sQuery="SELECT am.id_materia_periodo_lectivo,mpl.paralelo,m.nombre ".
                            "FROM ul_materia m, ul_alumno_materia am, ul_materia_periodo_lectivo mpl ".
                            "WHERE am.id_alumno=$id_alumno and am.id_materia=m.id and am.abierto='1' ".
                            "and mpl.id=am.id_materia_periodo_lectivo and mpl.estatus='A' and mpl.abierto='1' ".
                            "ORDER BY m.nombre, mpl.paralelo ASC";
               }
            break;
       default:
            ///Si el usuario no es de ninguno de los grupos anteriores se debe buscar las materias en ul_materias_grupo
            $sQuery="SELECT mg.id_materia_periodo_lectivo,mpl.paralelo,m.nombre ".
                     "FROM ul_materias_grupo mg,ul_materia m, ul_materia_periodo_lectivo mpl ".
                     "WHERE mg.id_group=$id_grupo and mg.id_materia_periodo_lectivo=mpl.id and mpl.id_materia=m.id and mpl.abierto='1'";
   }

$arr_materias=array();

   if($sQuery!=""){  ///Si el query es distinto de vacio entonces se ejecuta el query para obtener las materias periodo lectivo
       $result=$db->fetchTable($sQuery,true);

          if(is_array($result) && count($result)>0){
	      foreach($result as $fila){
                  $arr_materias[$fila['id_materia_periodo_lectivo']]=$fila['nombre']." P".$fila['paralelo'];
	      }
	  }
   }

//////Si el id_materia_periodo_lectivo NO esta en el arr_materias se setea como NULL
   if(!array_key_exists($id_materia_periodo_lectivo,$arr_materias)){
      $id_materia_periodo_lectivo=NULL;
      $_POST['id_materia_periodo_lectivo']=NULL;
      $_GET['id_materia_periodo_lectivo']=NULL;
   }

$combo_materias="<select name='id_materia_periodo_lectivo' onChange='submit();'>".
                "<option value=''>-- Seleccione una Materia -- </option>".
                 combo($arr_materias,$id_materia_periodo_lectivo)."</select>";

$sCodigoTabla.="<form method=\"POST\" name=\"seleccion\" action='$baseURL'>".
               "<table align='right' cellspacing=0 cellpadding=0><tr>".
               "<td class='menudescription2'>MATERIA&nbsp;</td><td class='menudescription2'>".$combo_materias."</td>".
               "</tr></table>".
	       "</form>";

return $sCodigoTabla;

}


}


?>
