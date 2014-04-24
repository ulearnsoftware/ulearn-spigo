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
// $Id: ul_tomar_calificable.class.php,v 1.1.1.1 2006/03/03 21:59:09 ainiguez Exp $

if (isset($gsRutaBase)){
     require_once ("$gsRutaBase/conf/default.conf.php");
     require_once ("$gsRutaBase/lib/paloEntidad.class.php");
     require_once ("$gsRutaBase/lib/paloACL.class.php");
     require_once ("$gsRutaBase/modules/datetime.class.php");
     require_once ("$gsRutaBase/modules/ul_archivo_calificable.class.php");
}
else{
   require_once ("conf/default.conf.php");
   require_once ("lib/paloEntidad.class.php");
   require_once ("lib/paloACL.class.php");
   require_once ("modules/datetime.class.php");
   require_once ("modules/ul_archivo_calificable.class.php");
}

class ul_tomar_calificable extends PaloEntidad
{
   // plantilla
   var $tpl;
   // Mensajes de error utilizando la plantilla tpl
   var $_msgError;

   var $sBaseURL;
   var $oDB;


   // arreglo con los datos del grupo y pregunta actual
   var $gp_datos;

   ///// estos datos deben ser enviados entre páginas

   // arreglo con los ids de los grupos y las preguntas
   var $gp_ids;
   // informacion del calificable
   var $calificable;
   // grupo-pregunta actual
   var $gp_actual;
   // tiempo de inicio
   var $t_inicio; // debe ser almacenado en la BD

   function ul_tomar_calificable(&$sDB, &$tpl,$sBaseURL)
   {
      $this->_msgError="";
      //$this->id_mpl = recoger_valor("id_materia_periodo_lectivo",$_GET,$_POST);
      $this->sBaseURL=$sBaseURL;
      $this->oDB=$sDB;
      $this->tpl=$tpl;

      $this->gp_ids = unserialize(ereg_replace("[\\\"]+","\"",recoger_valor('gp_ids',$_GET,$_POST)));
      $this->calificable = unserialize(ereg_replace("[\\\"]+","\"",recoger_valor('calificable',$_GET,$_POST)));
      $this->gp_actual = recoger_valor('gp_actual',$_GET,$_POST);
      $this->t_inicio = recoger_valor('t_inicio',$_GET,$_POST);
      //$this->gp_datos = $this->obtener_datos_pregunta_actual();
   }


   function obtener_datos_pregunta_actual($gp_posicion = NULL)
   {
      if($gp_posicion==NULL){
         $gp_posicion = $this->gp_actual;
         if(is_null($gp_posicion)){
            $this->_msgError = $this->tpl->crearAlerta(
               "error",
               "Obtener Datos",
               "El número de pregunta indicado no es válido.<br />\n".$this->oDB->errMsg
               );
            return FALSE;
         }
      }

      $id_grupo = $this->gp_ids[$gp_posicion]['id_grupo'];
      $id_pregunta = $this->gp_ids[$gp_posicion]['id_pregunta'];

      $sQuery = "SELECT gp.id_grupo_pregunta, gp.contenido AS gp_contenido, p.id_pregunta,p.contenido,p.URL,p.tipo_respuesta,p.abierta,p.id_grupo_pregunta ".
                "FROM ul_grupo_pregunta AS gp, ul_pregunta AS p ".
                "WHERE gp.id_grupo_pregunta=$id_grupo AND p.id_pregunta=$id_pregunta AND p.id_grupo_pregunta=gp.id_grupo_pregunta";
      $result = $this->oDB->fetchTable($sQuery,TRUE);


      if(is_array($result)){
         if(count($result)>0)
            $datos=$result[0];
      }else{
         $this->_msgError = $this->tpl->crearAlerta(
            "error",
            "Obtener Datos",
            "El calificable seleccionado no contiene datos.<br />\n".$this->oDB->errMsg
            );
         return FALSE;
      }

      $id_alumno_calificable = recoger_valor('id_alumno_calificable',$_GET,$_POST);
      $sQuery = "SELECT id_alumno_pregunta ".
                "FROM ul_alumno_pregunta ".
                "WHERE id_pregunta=".$datos['id_pregunta']." AND id_alumno_calificable=$id_alumno_calificable";
      $result = $this->oDB->fetchTable($sQuery,TRUE);

      if(is_array($result)){
         if(count($result)>0)
            $datos['id_alumno_pregunta']=$result[0]['id_alumno_pregunta'];
      }else{
         $this->_msgError = $this->tpl->crearAlerta(
            "error",
            "Obtener Datos",
            "El calificable seleccionado no contiene datos.<br />\n".$this->oDB->errMsg
            );
         return FALSE;
      }

      return $datos;
   }


   function inicializacion()
   {
      $id_mpl = recoger_valor("id_materia_periodo_lectivo",$_GET,$_POST);
      $id_calificable = recoger_valor("id_calificable",$_GET,$_POST);
      $id_al_calf = recoger_valor("id_alumno_calificable",$_GET,$_POST);

      $this->calificable=array();
      // Verificamos que el calificable sea correcto para la materia_periodo_lectivo indicado
      // Almacenamos el titulo, ponderación y duración
      $sQuery = "SELECT titulo, base, ponderacion, duracion, estatus FROM ul_calificable WHERE id_calificable=$id_calificable AND id_materia_periodo_lectivo=$id_mpl";
      $result = $this->oDB->getFirstRowQuery($sQuery,TRUE);
      if(is_array($result) and count($result)>0){
         if($result['estatus']=='I'){
            // Inactivo
            $this->_msgError = $this->tpl->crearAlerta(
               "error",
               "Inicializacion",
               "El calificable seleccionado está inactivo.<br />\n".$this->oDB->errMsg
               );
            return FALSE;
         }
         // datos del calificable
         $this->calificable['titulo'] = $result['titulo'];
         $this->calificable['ponderacion'] = $result['ponderacion'];
         $this->calificable['duracion'] = $result['duracion'];
         $this->calificable['base'] = $result['base'];
      }else{
         //Los datos no concuerdan: id_calificable y id_materia_periodo_lectivo
         $this->_msgError = $this->tpl->crearAlerta(
            "error",
            "Inicializacion",
            "Los datos no coinciden".$this->oDB->errMsg
            );
         return FALSE;
      }

      // información del calificable del alumno
      $sQuery =   " SELECT * ".
                  " FROM ul_alumno_calificable ".
                  " WHERE id_calificable=$id_calificable AND id_alumno_calificable=$id_al_calf";
      $result = $this->oDB->getFirstRowQuery($sQuery,TRUE);
      if(is_array($result) and count($result)>0){
         $al_calf = $result; // todos los datos del registro en ul_alumno_calificable
      }else{
         // Los datos ingresados no son validos: id_calificable AND id_alumno_calificable
         $this->_msgError = $this->tpl->crearAlerta(
            "error",
            "Inicializacion",
            "Los datos ingresados no son validos.".$this->oDB->errMsg
            );
         return FALSE;
      }

      // Se adelantó a la hora de inicio del test
      if(conv_datetime($al_calf['fecha_inicio']) > time()){
         $this->_msgError = $this->tpl->crearAlerta(
            "error",
            "Inicializacion",
            "No es hora de realizar el Test."
            );
         return FALSE;
      }

      // No ingresó a tiempo para realizar el test
      if(conv_datetime($al_calf['fecha_cierre']) < time()){
         $this->_msgError = $this->tpl->crearAlerta(
            "error",
            "Inicializacion",
            "Ya pasó el tiempo para realizar el Test."
            );
         return FALSE;
      }

      if($al_calf['estatus']=='T'){
         $this->_msgError = $this->tpl->crearAlerta(
            "error",
            "Inicializacion",
            "El calificable seleccionado ha sido marcado como terminado.<br />\n".$this->oDB->errMsg
            );
         return FALSE;
      }

      if($al_calf['estatus']=='A'){
         $this->_msgError = $this->tpl->crearAlerta(
            "error",
            "Inicializacion",
            "El calificable seleccionado ha sido marcado como Anulado.<br />\n".$this->oDB->errMsg
            );
         return FALSE;
      }


      // verificacion y obtención de los ids de los grupos y
      // preguntas del calificable seleccionado
      $sQuery = "SELECT gp.id_grupo_pregunta AS id_grupo, p.id_pregunta ".
                "FROM ul_grupo_pregunta AS gp, ul_pregunta AS p ".
                "WHERE gp.id_calificable=$id_calificable AND p.id_grupo_pregunta=gp.id_grupo_pregunta ORDER BY gp.orden, p.orden";
      $result = $this->oDB->fetchTable($sQuery,TRUE);

      if(is_array($result) and count($result)>0){
          $arr_mix=array();
            foreach($result as $fila){
               $campos=array();
                  foreach($fila as $key=>$value)
                     $campos[$key]=urlencode($value);
               $arr_mix[]=$campos;
            }
         $this->gp_ids=$arr_mix;
      }else{
         $this->_msgError = $this->tpl->crearAlerta(
            "error",
            "Inicializacion",
            "El calificable seleccionado no contiene datos.<br />\n".$this->oDB->errMsg
            );
         return FALSE;
      }

      //// Actualizacion del estado y Toma del Tiempo disponible para realizar el test.

      if($al_calf['estatus']=='N'){
         // grupo pregunta inicial
         $this->gp_actual=0;

         // tiempo en que se toma el test y cambio de estado
         $this->t_inicio=time();
         $sQuery =   " UPDATE ul_alumno_calificable ".
                     " SET fecha_realizacion=now(), estatus='V' ".
                     " WHERE id_alumno_calificable='$id_al_calf'";
         $result= $this->oDB->genQuery($sQuery);
         if($result===FALSE){
            $this->_msgError = $this->tpl->crearAlerta(
               "error",
               "Inicializacion",
               "No se puede actualizar el sistema.<br />\n".$this->oDB->errMsg
               );
            return FALSE;
         }
      }elseif($al_calf['estatus']=='V'){
         // grupo pregunta inicial
         $this->gp_actual=0;

         // tiempo en que se toma el test y cambio de estado
         $sQuery =   " SELECT fecha_realizacion ".
                     " FROM ul_alumno_calificable ".
                     " WHERE id_alumno_calificable='$id_al_calf'";
         $result= $this->oDB->getFirstRowQuery($sQuery,TRUE);
         if(is_array($result) && count($result)>0){
            $this->t_inicio = conv_datetime($result['fecha_realizacion']);
         }else{
            $this->_msgError = $this->tpl->crearAlerta(
               "error",
               "Inicializacion",
               "No se pudo leer la fecha del sistema.<br />\n".$this->oDB->errMsg
               );
            return FALSE;
         }

         // preguntas que ya han sido vistas o realizadas
         $sQuery = "SELECT id_alumno_pregunta, id_pregunta FROM ul_alumno_pregunta WHERE id_alumno_calificable='$id_al_calf'";
         $result= $this->oDB->fetchTable($sQuery,TRUE);
         if(is_array($result)){
            if(count($result)>0){
               $arr=array();
               foreach($result as $i=>$value)
                  $arr[$value['id_pregunta']]=$value['id_alumno_pregunta'];

               foreach($this->gp_ids as $i=>$value)
                  if(isset($arr[$this->gp_ids[$i]['id_pregunta']]))
                     $this->gp_ids[$i]['id_alumno_pregunta'] = $arr[$this->gp_ids[$i]['id_pregunta']];
            }
         }else{
            $this->_msgError = $this->tpl->crearAlerta(
               "error",
               "Inicializacion",
               "No se pudieron recuperar las respuestas ingresadas por el estudiante.<br />\n".$this->oDB->errMsg
               );
            return FALSE;
         }
      }

      // tiempo restante
      if((conv_datetime($this->t_inicio) + $this->calificable['duracion']*60) < time()){
         $this->_msgError = $this->tpl->crearAlerta(
            "error",
            "Inicializacion",
            "Su tiempo ha terminado."
            );
         return FALSE;
      }

      return TRUE;
   }



   function procesar_cambios()
   {
      $boton = recoger_valor('boton',$_GET,$_POST);
      $tomar_calificable=recoger_valor('tomar_calificable',$_GET,$_POST);
      if($tomar_calificable=='no')
         $boton="Terminar";

      // Preparamos la siguente pregunta o terminamos de realizar el test
      switch($boton){
      case "< Anterior":
         if($this->gp_actual>0){
            $this->gp_actual--;
            // se necesita actualizar la presentación
         }
         break;

      case "Siguiente >":
         if($this->gp_actual<count($this->gp_ids)-1){
            $this->gp_actual++;
            // se necesita actualizar la presentación
         }
         break;

      case "Terminar":
         return $this->terminar();

      default:
      }
   }


   function terminar()
   {
      $id_al_calf = recoger_valor("id_alumno_calificable",$_GET,$_POST);
      $sQuery = "UPDATE ul_alumno_calificable SET fecha_terminacion=now(), estatus='T' WHERE id_alumno_calificable='$id_al_calf' AND estatus='V'";
      $result= $this->oDB->genQuery($sQuery);
      if($result===FALSE){
         $this->_msgError = $this->tpl->crearAlerta(
            "error",
            "guardar_cambios",
            "No se puede actualizar el sistema para terminar.<br />\n".$this->oDB->errMsg
            );
         return FALSE;
      }
   //   echo "TERMINAR";
    //  echo $this->sBaseURL;
      header("location: ".$this->sBaseURL);
     // echo "TERMINAR2";
      return TRUE;
   }


   function id_respuesta_abierta($id_alumno_pregunta){
      // Eliminamos las respuestas abiertas que no tengas solucion
      $sQuery = "SELECT id_respuesta_abierta FROM ul_respuesta_alumno WHERE id_alumno_pregunta='$id_alumno_pregunta'";
      $result = $this->oDB->getFirstRowQuery($sQuery,TRUE);
      if(is_array($result) && count($result)>0){
         $id = $result['id_respuesta_abierta'];

         $sQuery = "SELECT * FROM ul_respuesta_abierta WHERE id_respuesta_abierta='$id'";
         $result = $this->oDB->getFirstRowQuery($sQuery,TRUE);
         if(is_array($result) && count($result)>0){
            return $result['id_respuesta_abierta'];
         }

         $sQuery = "DELETE FROM ul_respuesta_alumno WHERE id_alumno_pregunta='$id_alumno_pregunta'";
         $result = $this->oDB->genQuery($sQuery);
      }

      return FALSE;
   }

   function guardar_cambios()
   {
      // Almacenamos las respuestas ingresadas por el estudiante
      // obtenemos las respuestas actuales(si las hay)
      $resp_multiple = recoger_valor('resp_multiple',$_GET,$_POST);
      $resp_texto = recoger_valor('resp_texto',$_GET,$_POST);

      // Obtenemos los datos de la pregunta actual
      $this->gp_datos = $this->obtener_datos_pregunta_actual();

      if($this->gp_datos===FALSE){
         return FALSE;
      }

      switch($this->gp_datos['tipo_respuesta']){
      // ABIERTAS
      case 'A':

         switch($this->gp_datos['abierta']){
         // TEXTO - no se elimina, solo se modifica si existe
         case 'T':
            $id_r_a = $this->id_respuesta_abierta($this->gp_datos['id_alumno_pregunta']);
            if($id_r_a!==FALSE){

               // Actualizamos el contenido
               $sQuery = "UPDATE ul_respuesta_abierta SET URL_Texto='$resp_texto' WHERE id_respuesta_abierta='$id_r_a'";
               $result = $this->oDB->genQuery($sQuery);
               if($result===FALSE){
                  $this->_msgError = $this->tpl->crearAlerta(
                     "error",
                     "guardar_cambios - Update resp_texto",
                     "No se pueden actualizar los datos del sistema.<br />\n".$this->oDB->errMsg
                     );
                  return FALSE;
               }
            }else{

               // si no se cumplen, es nuevo.
               $url_texto = $resp_texto;
               $tipo='T';
               if($url_texto!=NULL){
                  // insertamos la solucion a la respuesta abierta
                  $sQuery = "INSERT INTO ul_respuesta_abierta (tipo,URL_Texto) values ('$tipo','$url_texto')";
                  $result= $this->oDB->genQuery($sQuery);
                  if($result===FALSE){
                     $this->_msgError = $this->tpl->crearAlerta(
                        "error",
                        "guardar_cambios - Insert: R_Texto",
                        "No se puede actualizar el sistema.<br />\n".$this->oDB->errMsg
                        );
                     return FALSE;
                  }

                  $sQuery = "SELECT LAST_INSERT_ID()";
                  $result = $this->oDB->getFirstRowQuery($sQuery,TRUE);
                  if(is_array($result) && count($result)>0){
                     $id_resp_abierta=$result['last_insert_id()'];
                  }else{
                     $this->_msgError = $this->tpl->crearAlerta(
                        "error",
                        "guardar_cambios: L_ID",
                        "No se puede actualizar el sistema.<br />\n".$this->oDB->errMsg
                        );
                     return FALSE;
                  }

                  $sQuery = "INSERT INTO ul_respuesta_alumno (fecha_hora,id_alumno_pregunta,id_respuesta_abierta) values ('".strftime("%Y-%m-%d %T",time())."','".$this->gp_datos['id_alumno_pregunta']."','$id_resp_abierta')";
                  $result= $this->oDB->genQuery($sQuery);
                  if($result===FALSE){
                     $this->_msgError = $this->tpl->crearAlerta(
                        "error",
                        "guardar_cambios - Insert : T_Texto",
                        "No se puede actualizar el sistema.<br />\n".$this->oDB->errMsg
                        );
                     return FALSE;
                  }
               }
            }
            break;

         // ARCHIVOS
         case 'A':
            break;

         default:
         }
         break;

      // MULTIPLES
      case 'M':
         // eliminar respuestas multiples existentes
         $sQuery = "DELETE FROM ul_respuesta_alumno WHERE id_alumno_pregunta=".$this->gp_datos['id_alumno_pregunta'];
         $result = $this->oDB->genQuery($sQuery);
         if($result===FALSE){
            $this->_msgError = $this->tpl->crearAlerta(
               "error",
               "guardar_cambios - Delete",
               "No se pueden actualizar los datos del sistema.<br />\n".$this->oDB->errMsg
               );
            return FALSE;
         }
         // crear nuevas respuestas multiples (si las hay)
         if(is_array($resp_multiple)){
            foreach($resp_multiple as $i=>$values){
               $sQuery = "INSERT INTO ul_respuesta_alumno (fecha_hora,id_alumno_pregunta,id_respuesta) values ('".strftime("%Y-%m-%d %T",time())."','".$this->gp_datos['id_alumno_pregunta']."','$values')";
               $result= $this->oDB->genQuery($sQuery);
               if($result===FALSE){
                  $this->_msgError = $this->tpl->crearAlerta(
                     "error",
                     "guardar_cambios - Insert: M",
                     "No se puede actualizar el sistema.<br />\n".$this->oDB->errMsg
                     );
                  return FALSE;
               }
            }
         }
         break;
      default:
      }

      return $this->actualizar_calificable(recoger_valor("id_alumno_calificable",$_GET,$_POST), $this->gp_ids[$this->gp_actual]['id_pregunta']);
   }


   function actualizar_alumno_pregunta()
   {
      //////
      $boton = recoger_valor('boton',$_GET,$_POST);
      // Preparamos la siguente pregunta o terminamos de realizar el test
      switch($boton){
      case "Eliminar":
         //$nombre_archivo = recoger_valor('nombre_archivo',$_GET,$_POST);
         $nombre_archivo = urlencode(recoger_valor('nombre_archivo',$_GET,$_POST));

         $sQuery = "SELECT id_respuesta_abierta FROM ul_respuesta_abierta WHERE URL_Texto='$nombre_archivo'";
         $result = $this->oDB->getFirstRowQuery($sQuery,TRUE);
         if(is_array($result)){
            $id=NULL;
            if(count($result)>0)
               $id=$result['id_respuesta_abierta'];
         }else{
            $this->_msgError = $this->tpl->crearAlerta(
               "error",
               "actualizar_alumno_pregunta",
               "No se pudo leer la respuesta de la BD.<br />\n".$this->oDB->errMsg
               );
            return FALSE;
         }
         if(is_null($id)){
            $this->_msgError = $this->tpl->crearAlerta(
               "error",
               "actualizar_alumno_pregunta",
               "En la BD no existe el archivo indicado.<br />\n".$this->oDB->errMsg
               );
            return FALSE;
         }
         $sQuery = "DELETE FROM ul_respuesta_abierta WHERE id_respuesta_abierta =$id";
         $result = $this->oDB->genQuery($sQuery);
         if($result===FALSE){
            $this->_msgError = $this->tpl->crearAlerta(
               "error",
               "actualizar_alumno_pregunta",
               "No se pudo eliminar la respuesta de la BD.<br />\n".$this->oDB->errMsg
               );
            return FALSE;
         }

         $sQuery = "UPDATE ul_respuesta_alumno SET id_respuesta_abierta=NULL WHERE id_respuesta_abierta=$id";
         $result = $this->oDB->genQuery($sQuery);
         if($result===FALSE){
            $this->_msgError = $this->tpl->crearAlerta(
               "error",
               "actualizar_alumno_pregunta",
               "No se pudo actualizar la respuesta de la BD.<br />\n".$this->oDB->errMsg
               );
            return FALSE;
         }

         break;

      case "Subir":


         if(isset($_FILES['resp_archivo']))
            // se ha seleccionado un nuevo archivo

            if($_FILES['resp_archivo']['size']>0 && $_FILES['resp_archivo']['tmp_name']!=""){
               // se carga el nuevo archivo
               $oArchivo_Calificable=new ul_archivo_calificable($this->oDB);

               if($oArchivo_Calificable->realizar_upload('resp_archivo',recoger_valor('id_materia_periodo_lectivo',$_GET,$_POST))===FALSE){
                  $this->_msgError = $this->tpl->crearAlerta(
                     "error",
                     "actualizar_alumno_pregunta - Cargar resp_archivo",
                     "No se pueden actualizar los datos del sistema.<br />\n".$oArchivo_Calificable->getMessage()
                     );
                  return FALSE;
               }

               // se almacena el nombre del archivo cargado
               $resp_archivo = urlencode($oArchivo_Calificable->archivo);

               // ARCHIVOS - no se elimina, solo se modifica si existe
               $sQuery = "SELECT id_respuesta_abierta FROM ul_respuesta_alumno WHERE id_alumno_pregunta=".$this->gp_datos['id_alumno_pregunta'];
               $result = $this->oDB->getFirstRowQuery($sQuery,TRUE);
               if($result===FALSE){
                  $this->_msgError = $this->tpl->crearAlerta(
                     "error",
                     "actualizar_alumno_pregunta - Select resp_archivo",
                     "No se pueden actualizar los datos del sistema.<br />\n".$this->oDB->errMsg
                     );
                  return FALSE;
               }
               // existe algun registro
               if(count($result)>0 && $result['id_respuesta_abierta']!="" && $result['id_respuesta_abierta']>0){
                  $id_r_a=$result['id_respuesta_abierta'];
                  // Actualizamos el contenido
                  $sQuery = "UPDATE ul_respuesta_abierta SET URL_Texto='$resp_archivo' WHERE id_respuesta_abierta=".$id_r_a;
                  $result = $this->oDB->genQuery($sQuery);
                  if($result===FALSE){
                     $this->_msgError = $this->tpl->crearAlerta(
                        "error",
                        "actualizar_alumno_pregunta - Update resp_archivo",
                        "No se pueden actualizar los datos del sistema.<br />\n".$this->oDB->errMsg
                        );
                     return FALSE;
                  }
               }
               else{ // no existe el registro, es nuevo
                  $url_texto = $resp_archivo;
                  $tipo='A';
                  if($url_texto!=NULL){
                     // insertamos la solucion a la respuesta abierta
                     $sQuery = "INSERT INTO ul_respuesta_abierta (tipo,URL_Texto) values ('$tipo','$url_texto')";
                     $result= $this->oDB->genQuery($sQuery);
                     if($result===FALSE){
                        $this->_msgError = $this->tpl->crearAlerta(
                           "error",
                           "actualizar_alumno_pregunta - Insert: R_Archivo",
                           "No se puede actualizar el sistema.<br />\n".$this->oDB->errMsg
                           );
                        return FALSE;
                     }

                     $sQuery = "SELECT LAST_INSERT_ID()";
                     $result = $this->oDB->getFirstRowQuery($sQuery,TRUE);
                     if(is_array($result) && count($result)>0){
                        $id_resp_abierta=$result['last_insert_id()'];
                     }else{
                        $this->_msgError = $this->tpl->crearAlerta(
                           "error",
                           "actualizar_alumno_pregunta: L_ID",
                           "No se puede actualizar el sistema.<br />\n".$this->oDB->errMsg
                           );
                        return FALSE;
                     }

                     $sQuery = "INSERT INTO ul_respuesta_alumno (fecha_hora,id_alumno_pregunta,id_respuesta_abierta) values ('".strftime("%Y-%m-%d %T",time())."','".$this->gp_datos['id_alumno_pregunta']."','$id_resp_abierta')";
                     $result= $this->oDB->genQuery($sQuery);
                     if($result===FALSE){
                        $this->_msgError = $this->tpl->crearAlerta(
                           "error",
                           "actualizar_alumno_pregunta - Insert : T_Archivo",
                           "No se puede actualizar el sistema.<br />\n".$this->oDB->errMsg
                           );
                        return FALSE;
                     }
                  }
               }
            }
         break;
      }

      // se ha visto la pregunta y hay que crear la relación entre ul_alumno_calificable y ul_pregunta
      $id_al_calf = recoger_valor("id_alumno_calificable",$_GET,$_POST);

      if(!is_array($this->gp_ids) || !ereg("^[[:digit:]]+$",$this->gp_actual)){
         $this->_msgError = $this->tpl->crearAlerta(
            "error",
            "actualizar_alumno_pregunta",
            "No se han creado los grupos y las preguntas.<br />\n"
            );
         return FALSE;
      }

      // Obtenemos los datos de la pregunta actual
      $this->gp_datos = $this->obtener_datos_pregunta_actual();

      $id_pregunta = $this->gp_datos['id_pregunta'];

      if(!isset($this->gp_datos['id_alumno_pregunta'])){
         $sQuery = "SELECT id_alumno_pregunta FROM ul_alumno_pregunta WHERE id_alumno_calificable='$id_al_calf' AND id_pregunta='$id_pregunta'";
         $result = $this->oDB->getFirstRowQuery($sQuery,TRUE);
         if(is_array($result) && count($result)>0){
            $id_al_preg=$result['id_alumno_pregunta'];
         }else{
            // Creamos la relacion entre ul_alumno_calificable y ul_pregunta
            $sQuery = " INSERT INTO ul_alumno_pregunta (id_alumno_calificable, id_pregunta, fecha_hora) VALUES('$id_al_calf','$id_pregunta', NOW())";
            $result= $this->oDB->genQuery($sQuery);
            if($result===FALSE){
               $this->_msgError = $this->tpl->crearAlerta(
                  "error",
                  "actualizar_alumno_pregunta",
                  "No se puede actualizar el sistema.<br />\n".$this->oDB->errMsg
                  );
               return FALSE;
            }
         }
      }

      // obtenemos los datos de la pregunta actual que estan almacenados en la Base de Datos
      if(isset($this->gp_datos['id_alumno_pregunta'])){
         $id_al_preg=$this->gp_datos['id_alumno_pregunta'];
         // Insertamos la información en el POST para actualizar la presentación
         switch($this->gp_datos['tipo_respuesta']){
         case "M": // Multiple
            $sQuery = "SELECT id_respuesta FROM ul_respuesta_alumno WHERE id_alumno_pregunta=$id_al_preg";
            $result = $this->oDB->fetchTable($sQuery,TRUE);
            if(is_array($result) && count($result)>0){
               $_POST['_respuesta']=$result;
            }
            break;
         case "A": // Abierta
            $sQuery = "SELECT URL_Texto FROM ul_respuesta_alumno AS resp_al,ul_respuesta_abierta AS resp_ab WHERE resp_al.id_respuesta_abierta=resp_ab.id_respuesta_abierta AND id_alumno_pregunta=$id_al_preg";
            $result = $this->oDB->getFirstRowQuery($sQuery,TRUE);
            if(is_array($result) && count($result)>0){
               switch($this->gp_datos['abierta']){
               case "T": // Texto
                  $_POST['_respuesta']['texto'] = $result['URL_Texto'];
                  break;
               case "A": // Archivo
                  $_POST['_respuesta']['archivo'] = $result['URL_Texto'];
                  break;
               }

            }
            break;
         }
      }

      return TRUE;
   }

   // para almacenar campos ocultos
   function input_hidden($dato){
      $campos='';
      if(is_array($dato) && count($dato)>0){
         foreach($dato as $i=>$value){
            if(is_array($value)){
               if(count($value)>0)
                  $campos.="<input type='hidden' name='".$i."' value='".serialize($value)."' />\n";
            }else{
               $campos.="<input type='hidden' name='".$i."' value='".$value."' />\n";
            }
         }
      }
      return $campos;
   }

   // presentación del formulario para contestar a las preguntas
   function tomar_calificable(){
      global $config;
      $tomar_calificable='';
      $id_materia_periodo_lectivo=recoger_valor("id_materia_periodo_lectivo",$_GET,$_POST);
      $forma=$accion=$opciones_forma=$navegacion=$tiempo=$grupo=$pregunta=$respuesta=$hidden="&nbsp;";

      $insTpl =& new paloTemplate("skins/".$config->skin);
      $insTpl->definirDirectorioPlantillas("calificable");
      $insTpl->assign("IMG_PATH", "skins/$config->skin/images");

      $accion = $_SERVER['PHP_SELF'].$this->sBaseURL;

      $g_p = $this->gp_datos;

      // Nombre de la Forma
      $forma="Tomar_Calificable";
      $opciones_forma="enctype='multipart/form-data'";

      // Navegación
      $boton_anterior="<input type='submit' name='boton' value='< Anterior' ".($this->gp_actual==0?"disabled='disabled'":"")." />&nbsp;";
      $boton_terminar="<input type='submit' name='boton' value='Terminar' onClick=\"return confirm('Está seguro que desea Terminar este calificable?')\"/>&nbsp;";
      $boton_siguiente="<input type='submit' name='boton' value='Siguiente >' ".($this->gp_actual==(count($this->gp_ids)-1)?"disabled='disabled'":"")."/>";

      ///No mostrar el boton siguiente si es la ultima pregunta
         if($this->gp_actual+1==count($this->gp_ids))
            $boton_siguiente="&nbsp;";
      ///No mostrar el boton anterior si es la primera pregunta
         if($this->gp_actual+1==1)
            $boton_anterior="&nbsp;";

      $navegacion="<table align='center' border=0 width='100%' cellspacing=0>".
                     "<tr><td align='left' class='table_nav_bar' width='33%'>$boton_anterior</td>".
                          "<td align='center' class='table_nav_bar' width='34%'>$boton_terminar</td>".
                          "<td align='right' class='table_nav_bar' width='33%'>$boton_siguiente</td></tr></table>";
      // Tiempo
      $tiempo = "<div class='top_calificable'> <b>Pregunta: ".($this->gp_actual+1)."/".(count($this->gp_ids))."</b></div>".
                 "<div class='top_calificable'><b>Tiempo Restante: <input type='text' name='tiempo' readonly='TRUE'/></b></div></br>".
                "<input type='hidden' name='htiempo' value='".($this->t_inicio+$this->calificable['duracion']*60-time())."' />";

      $_respuesta="";
      if(isset($_POST['_respuesta'])){
         $_resp=$_POST['_respuesta'];
         if(isset($_resp['texto']))
            $_respuesta=$_resp['texto'];
         elseif(isset($_resp['archivo']))
            $_respuesta=$_resp['archivo'];
         else{
            $_respuesta=array();
            foreach($_resp as $i=>$value)
               $_respuesta[$value['id_respuesta']]=$value['id_respuesta'];
         }
      }

      // Respuestas
      if($g_p['tipo_respuesta']=='M'){ // Multiple
         $sQuery = "SELECT * FROM ul_respuesta WHERE id_pregunta=".$g_p['id_pregunta']." ORDER BY orden";
         $result = $this->oDB->fetchTable($sQuery,"TRUE");
         $respuesta="";
            if(is_array($result) && count($result)>0){
               $respuesta.="<tr><td><table width='100%'>";
               foreach($result as $i=>$value){
                  $archivo_incrustado="";
                     if(!is_null($value['URL']) && $value['URL']!=""){
                        if(ereg("\.(jpg|gif|png|jpeg|jpe|bmp)$",$value['URL']))   //si la extension del archivo es una imagen
                           $archivo_incrustado="<img src='modules/img.php?id_materia_periodo_lectivo=$id_materia_periodo_lectivo&URL=".$value['URL']."'>";
                        else
                           $archivo_incrustado="<a href='modules/file.php?id_materia_periodo_lectivo=$id_materia_periodo_lectivo&URL=".$value['URL']."'>".$value['URL']."</a>";
                     }

                  $respuesta.="<tr><td>".chr(ord('a')+$value['orden']-1).") ".nl2br($value['contenido'])."&nbsp;&nbsp;$archivo_incrustado</td>".
                                   "<td width='40'><input type='checkbox' name='resp_multiple[".$value['orden']."]' value='".$value['id_respuesta']."' ".(isset($_respuesta[$value['id_respuesta']])?"checked='checked'":"")."/> </td></tr>";
               }
               $respuesta.="</table></td></tr>";
            }else{
            }
      }else{
         if($g_p['abierta']=='T'){ // Texto
            $respuesta="<tr><td><textarea name='resp_texto' cols='50' rows='5'>$_respuesta</textarea></td></tr>";
         }else{ // Archivo
            $_respuesta=urldecode($_respuesta);
            $respuesta ="<tr><td><table align='center'>";
            $respuesta.="<tr><td><input type='submit' name='boton' value='Subir' /></td>";
            $respuesta.="<td><input type='hidden' name='MAX_FILE_SIZE' value='2000000' /><input type='file' name='resp_archivo' size='60'/></td></tr>";
            $respuesta.="<tr><td><input type='submit' name='boton' value='Eliminar' onClick=\"return confirm('Está seguro que desea Eliminar este archivo?')\"/></td>";
            $respuesta.="<td> Archivo: ".ereg_replace("^[[:digit:]]+-",'',$_respuesta)."<input type='hidden' name='nombre_archivo' value='$_respuesta'/></td></tr>";
            $respuesta.="</table></td></tr>";
         }
      }

      $grupo = "<div class='top_calificable'>".nl2br(urldecode($g_p['gp_contenido']))."</div>";
      $pregunta = nl2br(urldecode($g_p['contenido']));
         if(!is_null($g_p['URL']) && $g_p['URL']!=""){
               if(ereg("\.(jpg|gif|png|jpeg|jpe|bmp)$",$g_p['URL']))   //si la extension del archivo es una imagen
                  $file_incrustado="<img src='modules/img.php?id_materia_periodo_lectivo=$id_materia_periodo_lectivo&URL=".$g_p['URL']."'>";
               else
                  $file_incrustado="<a href='modules/file.php?id_materia_periodo_lectivo=$id_materia_periodo_lectivo&URL=".$g_p['URL']."'>".$g_p['URL']."</a>";
            $pregunta.="<br>$file_incrustado";
         }

      $hidden = $this->input_hidden(array(
         'gp_ids'                => $this->gp_ids,
         'calificable'           => $this->calificable,
         'gp_actual'             => $this->gp_actual,
         't_inicio'              => $this->t_inicio,
         'tomar_calificable'     => "yes",
         'terminar_calificable'  => "no",
         'id_alumno_calificable' => recoger_valor("id_alumno_calificable",$_GET,$_POST),
         'id_calificable'        => recoger_valor("id_calificable",$_GET,$_POST)
         ));


      $insTpl->assign("HIDDEN", $hidden);


      $insTpl->assign("EXIT", "<a href='".$this->sBaseURL."'>Menú Principal</a>");

      $insTpl->assign("FORM_NAME", $forma);
      $insTpl->assign("ACTION_URL", $accion);
      $insTpl->assign("FORM_OPTIONS", $opciones_forma);
      $insTpl->assign("NAVEGACION", $navegacion);
      $insTpl->assign("MILISEGUNDOS", 1000);
      $insTpl->assign("TIEMPO", $tiempo);
      $insTpl->assign("GRUPO", $grupo);
      $insTpl->assign("PREGUNTA", $pregunta);
      $insTpl->assign("RESPUESTAS", $respuesta);


      //$insTpl->assign("ENDTIME", $accion."Terminar=yes&id_alumno_calificable=".recoger_valor("id_alumno_calificable",$_GET,$_POST)."&id_calificable=".recoger_valor("id_calificable",$_GET,$_POST));




      $insTpl->parse("SALIDA", "tpl_tomar_calificable");
      $tomar_calificable.=$insTpl->fetch("SALIDA");

      return $tomar_calificable;
   }

   // actualiza la puntuacion a las preguntas de tipo multiple
   function actualizar_calificable($id_alumno_calificable,$id_pregunta)
   {

      // PREGUNTA
      $pregunta=NULL;
      $sQuery = "SELECT * FROM ul_pregunta WHERE id_pregunta=$id_pregunta";
      $result= $this->oDB->getFirstRowQuery($sQuery,TRUE);
      if(is_array($result)){
         if(count($result)>0){
            $pregunta=$result;
         }
      }else{
         $this->_msgError = $this->tpl->crearAlerta(
            "error",
            "actualizar_calificable",
            "No se puede actualizar el sistema.<br />\n".$this->oDB->errMsg
            );
         return FALSE;
      }

      if(is_null($pregunta) || $pregunta['tipo_respuesta']=='A')
         return FALSE;

      // Respuestas del Profesor
      $respuestas=NULL;
      $sQuery = "SELECT id_respuesta, correcto ".
         "FROM ul_respuesta ".
         "WHERE id_pregunta=$id_pregunta";
      $result = $this->oDB->fetchTable($sQuery,TRUE);
      if(is_array($result)){
         if(count($result)>0)
            $respuestas=$result;
      }else{
         $this->_msgError = $this->tpl->crearAlerta(
            "error",
            "actualizar_calificable",
            "No se puede actualizar el sistema.<br />\n".$this->oDB->errMsg
            );
         return FALSE;
      }

      if(is_null($respuestas))
         return FALSE;

      // Respuestas del Alumno
      $r_alumno=NULL;
      $sQuery = "SELECT r_a.id_respuesta ".
         "FROM ul_respuesta_alumno AS r_a, ul_alumno_pregunta AS a_p ".
         "WHERE r_a.id_alumno_pregunta=a_p.id_alumno_pregunta AND a_p.id_pregunta=$id_pregunta AND a_p.id_alumno_calificable=$id_alumno_calificable";
      $result = $this->oDB->fetchTable($sQuery,TRUE);
      if(is_array($result)){
         if(count($result)>0){
            foreach($result as $i=>$value)
               $r_alumno[$value['id_respuesta']]=$value['id_respuesta'];
         }
      }else{
         $this->_msgError = $this->tpl->crearAlerta(
            "error",
            "actualizar_calificable",
            "No se puede actualizar el sistema.<br />\n".$this->oDB->errMsg
            );
         return FALSE;
      }

      if(is_null($r_alumno))
         return FALSE;

      // Valor de la pregunta
      if($pregunta['t_ponderacion']=='P')
         $valor_pregunta = $pregunta['v_ponderacion']*$this->calificable['base']; //this->obtener_nota_base();
      else
         $valor_pregunta = $pregunta['v_ponderacion'];

      $correcto=TRUE;

      foreach($respuestas as $i=>$value){
         switch($value['correcto']){
         case '1':
            if(isset($r_alumno[$value['id_respuesta']]))
               $resp=1;
            else
               $resp=0;
            break;
         case '0':
            if(!isset($r_alumno[$value['id_respuesta']]))
               $resp=1;
            else
               $resp=0;
            break;
         }

         // Con un error es falso
         $correcto &= $resp;
      }

      if(!$correcto)
         $valor_pregunta=0;

      $sQuery = "UPDATE ul_alumno_pregunta SET puntuacion=$valor_pregunta WHERE id_pregunta=$id_pregunta AND id_alumno_calificable=$id_alumno_calificable";
      $result = $this->oDB->genQuery($sQuery);
      if($result===FALSE){
         $this->_msgError = $this->tpl->crearAlerta(
            "error",
            "actualizar_calificable",
            "No se puede actualizar el sistema.<br />\n".$this->oDB->errMsg
            );
         return FALSE;
      }

      $sQuery = "SELECT SUM(puntuacion) FROM ul_alumno_pregunta WHERE id_alumno_calificable=$id_alumno_calificable";
      $result = $this->oDB->getFirstRowQuery($sQuery);
      if(is_array($result)){
         if(count($result)>0){
            $puntuacion=$result[0];
            $sQuery = "UPDATE ul_alumno_calificable SET puntuacion=$puntuacion WHERE id_alumno_calificable=$id_alumno_calificable";
            $result = $this->oDB->genQuery($sQuery);
            if($result===FALSE){
               $this->_msgError = $this->tpl->crearAlerta(
                  "error",
                  "actualizar_calificable",
                  "No se puede actualizar el sistema.<br />\n".$this->oDB->errMsg
                  );
               return FALSE;
            }
         }
      }else{
         $this->_msgError = $this->tpl->crearAlerta(
            "error",
            "actualizar_calificable",
            "No se puede actualizar el sistema.<br />\n".$this->oDB->errMsg
            );
         return FALSE;
      }
   }

   function pantalla_bienvenida(){
      global $config;
      $tomar_calificable='';
      $id_materia_periodo_lectivo=recoger_valor("id_materia_periodo_lectivo",$_GET,$_POST);

      $accion = $_SERVER['PHP_SELF'].$this->sBaseURL;

      $insTpl =& new paloTemplate("skins/".$config->skin);
      $insTpl->definirDirectorioPlantillas("calificable");


      $hidden = $this->input_hidden(array(
         'tomar_calificable'     => "yes",
         'id_alumno_calificable' => recoger_valor("id_alumno_calificable",$_GET,$_POST),
         'id_calificable'        => recoger_valor("id_calificable",$_GET,$_POST)
         ));

      $insTpl->assign("ACTION_URL", $accion);
      $insTpl->assign("REGRESAR", "<a href='".$this->sBaseURL."'><< Regresar</a>");
      $insTpl->assign("ARREGLOS", $hidden);
      $insTpl->assign("BOTONES", "<input type='submit' name='GO' value='Tomar Calificable'/>");

      $insTpl->parse("SALIDA", "tpl_bienvenida");
      $tomar_calificable.=$insTpl->fetch("SALIDA");
      return $tomar_calificable;
   }
}

?>
