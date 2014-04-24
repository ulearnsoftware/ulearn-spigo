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
// $Id: ul_eliminacion.class.php,v 1.1.1.1 2006/03/03 21:59:09 ainiguez Exp $

if (isset($gsRutaBase)){
   require_once ("$gsRutaBase/conf/default.conf.php");
   require_once ("$gsRutaBase/lib/paloACL.class.php");
}
else{
   require_once ("conf/default.conf.php");
   require_once ("lib/paloACL.class.php");
}

class ul_eliminacion
{
   var $errMsg;
   var $db;

   function ul_eliminacion($oDB){
      $this->db=$oDB;
   }

   function setMessage($txt){
      $this->errMsg=$txt;
   }
   function getMessage(){
      return $this->errMsg;
   }



   function eliminar_periodo_lectivo($id_periodo_lectivo)
   {
      $db=$this->db;
      //Se verifica que el periodo lectivo este con estatus cerrado
      if(ereg("[[:digit:]]+",$id_periodo_lectivo))
      {

         $sQuery="SELECT estatus FROM ul_periodo_lectivo WHERE id=$id_periodo_lectivo";
         $result=$db->getFirstRowQuery($sQuery,true);
            if(is_array($result) && count($result)>0)
            {
               $estatus=$result['estatus'];
                  if($estatus=='C')
                  {
                     $error="";
                     $bValido=$this->eliminar_materias($id_periodo_lectivo,$error);
                        if(!$bValido){
                           $this->setMessage($error);
                           return FALSE;
                        }
                        else
                           return TRUE;      ///Exito
                  }
                  else{
                     $this->setMessage("El periodo lectivo debe estar cerrado<br>");
                     return FALSE;
                  }
            }
            else{
               $this->setMessage("No se pudo obtener información del periodo lectivo. Error: ".$oDB->errMsg."<br>");
               return FALSE;
            }
      }
      else{
         $this->setMessage("No se recibio un id_periodo_lectivo válido<br>");
         return FALSE;
      }

   }


function eliminar_materias($id_periodo_lectivo,&$error){
$db=$this->db;
//Se obtienen todas las materias del periodo lectivo
$sQuery="SELECT id FROM ul_materia_periodo_lectivo WHERE id_periodo_lectivo=$id_periodo_lectivo ORDER BY id";
$result=$db->fetchTable($sQuery,true);

   if(is_array($result))
   {
      if(count($result)>0)
      {
         ////Se itera por cada una de las materias
         foreach($result as $fila)
         {
            $id_mpl=$fila['id'];
            $bValido=$this->eliminar_tablas_sencillas($id_mpl);
               if($bValido)
               {
                  $bValido=$this->eliminar_colaboracion($id_mpl);
                     if($bValido){
                        $bValido=$this->eliminar_calificable($id_mpl);
                           if($bValido){
                              ///Se debe actualizar la tabla de ul_materia_alumno y ul_materia_periodo_lectivo
                              $bValido=$this->actualizar("ul_alumno_materia","abierto","0","id_materia_periodo_lectivo",$id_mpl,$error);
                                 if(!$bValido)
                                    return FALSE;
                              $bValido=$this->actualizar("ul_materia_periodo_lectivo","abierto","0","id",$id_mpl,$error);
                                 if(!$bValido)
                                    return FALSE;
                           }
                           else{
                              $error.=$this->getMessage();
                              return FALSE;
                           }
                     }
                     else{
                        $error.=$this->getMessage();
                        return FALSE;
                     }
               }
               else{
                  $error.=$this->getMessage();
                  return FALSE;
               }
         }

         ///Si se han realizado satisfactoriamente las tareas se retorna TRUE
         return TRUE;
      }
      else{
         $error="No hay materias para eliminar";
         return FALSE;

      }
   }
   else{
      $error="No se pudo obtener las materias del periodo lectivo";
      return FALSE;
   }


}




   function eliminar_tablas_sencillas($id_mpl){
      $db=$this->db;
      $arrQuery=array();
      $arrTablas=array("ul_recurso","ul_evento","chat_materias","chat_messages","chat_users","ul_materias_grupo");
         foreach($arrTablas as $tabla)
            $arrQuery[]="DELETE FROM $tabla WHERE id_materia_periodo_lectivo=$id_mpl";

      $error="";
         foreach($arrQuery as $sQuery){

            $bValido=$db->genQuery($sQuery);
               if(!$bValido)
                  $error.="Error al eliminar recursos. Error:".$db->errMsg."<br>";
         }

      if(strlen($error)>0){
         $this->setMessage($error);
         return FALSE;
      }
      else
         return TRUE;
   }


   function select($tabla,$columna_id,$columna_where,$valor_columna){
      $db=$this->db;

      $arrIDS=array();

      $sQuery="SELECT $columna_id FROM $tabla WHERE $columna_where=$valor_columna";
      $res=$db->fetchTable($sQuery);
         if(is_array($res) && count($res)>0){
            foreach($res as $fila){
               $id=$fila[0];
                  if(!is_null($id) && $id>0)
                     $arrIDS[]=$fila[0];
            }
         }

      return $arrIDS;

   }

   function actualizar($tabla,$columna,$valor,$columna_where,$valor_where,&$error){
      $db=$this->db;
      $sQuery="UPDATE $tabla SET $columna='$valor' WHERE $columna_where='$valor_where'";

      $bValido=$db->genQuery($sQuery);
         if($bValido)
            return TRUE;
         else{
            $error.="No se pudo actualizar la tabla $tabla. Error: ".$db->errMsg."<br>";
            return FALSE;
         }
   }

   function eliminar($tabla,$columna_where,$valor_columna,&$error){
      $db=$this->db;
      $sQuery="DELETE FROM $tabla WHERE $columna_where=$valor_columna";

      $bValido=$db->genQuery($sQuery);
         if(!$bValido){
            $error.="No se pudo eliminar inf de $tabla. Error:".$db->errMsg."<br>";
            return FALSE;
         }
         else
            return TRUE;
   }









   function eliminar_colaboracion($id_mpl){
      $error="";
      $arrForos=$this->select("ul_foro","id_foro","id_materia_periodo_lectivo",$id_mpl);
         foreach($arrForos as $id_foro)
         {
            $arrTopicos=$this->select("ul_topico","id_topico","id_foro",$id_foro);
               foreach($arrTopicos as $id_topico)
               {
                  $arrMensajes=$this->select("ul_mensaje","id_mensaje","id_topico",$id_topico);
                     foreach($arrMensajes as $id_mensaje)
                     {
                        $this->eliminar("ul_mensaje_archivo","id_mensaje",$id_mensaje,$error);
                     }
                     if(strlen($error==0))
                        $this->eliminar("ul_mensaje","id_topico",$id_topico,$error);
               }
               if(strlen($error==0))
                  $this->eliminar("ul_topico","id_foro",$id_foro,$error);
         }

         if(strlen($error)==0){
            if($this->eliminar("ul_foro","id_materia_periodo_lectivo",$id_mpl,$error))
               return TRUE;
            else{
               $this->setMessage($error);
               return FALSE;
            }
         }
   }





   function eliminar_calificable($id_mpl){
      $error="";
      $arrCalificables=$this->select("ul_calificable","id_calificable","id_materia_periodo_lectivo",$id_mpl);

         foreach($arrCalificables as $id_calificable)
         {
            ///SE buscan los registros relacionados con el alumno
            $arrAlumnoCalif=$this->select("ul_alumno_calificable","id_alumno_calificable","id_calificable",$id_calificable);
               foreach($arrAlumnoCalif as $id_alumno_calificable)
               {
                  $arrAlumnoPreg=$this->select("ul_alumno_pregunta","id_alumno_pregunta","id_alumno_calificable",$id_alumno_calificable);
                     foreach($arrAlumnoPreg as $id_alumno_pregunta)
                     {
                        $arrRespAbierta=$this->select("ul_respuesta_alumno","id_respuesta_abierta","id_alumno_pregunta",$id_alumno_pregunta);
                           foreach($arrRespAbierta as $id_respuesta_abierta)
                           {
                              $this->eliminar("ul_respuesta_abierta","id_respuesta_abierta",$id_respuesta_abierta,$error);
                           }
                           if(strlen($error)==0)
                              $this->eliminar("ul_respuesta_alumno","id_alumno_pregunta",$id_alumno_pregunta,$error);
                     }
                     if(strlen($error)==0)
                        $this->eliminar("ul_alumno_pregunta","id_alumno_calificable",$id_alumno_calificable,$error);
               }
               if(strlen($error)==0)
                  $this->eliminar("ul_alumno_calificable","id_calificable",$id_calificable,$error);
         }

         foreach($arrCalificables as $id_calificable){
            //Se buscan los registro relacionados con las preguntas
            $arrGrupoPreg=$this->select("ul_grupo_pregunta","id_grupo_pregunta","id_calificable",$id_calificable);
               foreach($arrGrupoPreg as $id_grupo_pregunta){
                  $arrPreguntas=$this->select("ul_pregunta","id_pregunta","id_grupo_pregunta",$id_grupo_pregunta);
                     foreach($arrPreguntas as $id_pregunta){
                        $this->eliminar("ul_respuesta","id_pregunta",$id_pregunta,$error);
                     }
                     if(strlen($error)==0)
                        $this->eliminar("ul_pregunta","id_grupo_pregunta",$id_grupo_pregunta,$error);
               }
               if(strlen($error)==0)
                  $this->eliminar("ul_grupo_pregunta","id_calificable",$id_calificable,$error);
         }




         if(strlen($error)==0)
         {
            if($this->eliminar("ul_calificable","id_materia_periodo_lectivo",$id_mpl,$error)){
               return TRUE;
            }
            else{
               $this->setMessage($error);
               return FALSE;
            }
         }



   }



   /*function eliminar_colaboracion($id_mpl){
      $db=$this->db;
      $error="";
      //Se obtiene el id_foro
      $sQuery="SELECT id_foro FROM ul_foro WHERE id_materia_periodo_lectivo=$id_mpl";
      $result=$db->fetchTable($sQuery,true);
         if(is_array($result) && count($result))
         {
            foreach($result as $fila){
               $id_foro=$fila['id_foro'];
               /////Ahora se buscan los topicos relacionados
               $SQL="SELECT id_topico FROM ul_topico WHERE id_foro=$id_foro";
               $res=$db->fetchTable($SQL,true);
                  if(is_array($res) && count($res)>0){
                     foreach($res as $row){
                        $id_topico=$res['id_topico'];
                        $sPeticion="SELECT id_mensaje FROM ul_mensaje WHERE id_topico=$id_topico";
                        $recordset=$db->fetchTable($sPeticion,true);
                           if(is_array($recordset) && count($recordset)>0){
                              foreach($recordset)
                           }
                     }
                  }

                  if(strlen($error)==0){
                     $bValido=$db->genQuery("DELETE FROM ul_topico WHERE id_foro=$id_foro");
                        if(!$bValido)
                           $error.="No se pudo borrar inf de topicos. Error: ".$db->errMsg;
                  }
            }

            if(strlen($error)==0)
            {
               $bValido=$db->genQuery("DELETE FROM ul_foro WHERE id_materia_periodo_lectivo=$id_mpl");
                  if(!$bValido)
                     $error.="No se pudo borrar inf de foro. Error: ".$db->errMsg;
            }
         }
   }
*/


}

?>
