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
// $Id: ul_calificar_calificable.class.php,v 1.1.1.1 2006/03/03 21:59:09 ainiguez Exp $

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

class ul_calificar_calificable
{
   var $sBaseURL;
   var $id_mpl;
   var $id_calf;
   var $oDB;
   var $tpl;
   var $errMsg;
   var $nota_base;

   function ul_calificar_calificable(&$oDB,&$tpl,$sBaseURL,$id_materia_periodo_lectivo,$id_calificable)
   {
      $this->sBaseURL=$sBaseURL;
      $this->oDB=$oDB;
      $this->id_mpl=$id_materia_periodo_lectivo;
      $this->id_calf=$id_calificable;
      $this->tpl=$tpl;
      $this->nota_base=$this->obtener_nota_base();
   }

   function calificar_calificable()
   {
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


   // La vista para calificar realiza una presentacion con los elementos
   // creados en el Cuestionario en un formato más natural
   // permite revisar y calificar el cuestionario segun la información
   // ingresada por el estudiante

   function vista_calificar(){

      global $config;
      $oDB=$this->oDB;
      $img_path="skins/$config->skin/images";
      $id_materia_periodo_lectivo = recoger_valor("id_materia_periodo_lectivo",$_GET,$_POST);
      $id_calificable=recoger_valor("id_calificable",$_GET,$_POST);
      $id_alumno_calificable=recoger_valor("id_alumno_calificable",$_GET,$_POST);

      $url=$this->sBaseURL;
      //$pantalla.="<a href='".$url."' title='".$url."'>".substr(htmlentities($url),0,20)."</a> <br/>";
      $url2=$_SERVER['PHP_SELF'].$url;

      $insTpl =& new paloTemplate("skins/".$config->skin);
      $insTpl->definirDirectorioPlantillas("calificable");
      $insTpl->assign("IMG_PATH", "skins/$config->skin/images");

      $titulo='&nbsp;';
      $sQuery = "SELECT titulo, ponderacion, base FROM ul_calificable WHERE id_calificable='$id_calificable'";
      $result = $oDB->getFirstRowQuery($sQuery, TRUE);
      if(is_array($result) && count($result)>0){
         $titulo = $result['titulo'];
         $ponderacion = $result['ponderacion'];
         $base = $result['base'];
      }

      $orden=0;
      // Grupos que forman el Calificable
      $sQuery = "SELECT id_grupo_pregunta,contenido,orden FROM ul_grupo_pregunta WHERE id_calificable='$id_calificable' ORDER BY orden";
      $result = $oDB->fetchTable($sQuery,TRUE);
      if(is_array($result) && count($result)>0){
         foreach($result as $i1 => $value){
            $insTpl->clear("PREGUNTAS");

            // Preguntas que forman el Grupo del Calificable
            $sQuery = "SELECT * FROM ul_pregunta WHERE id_grupo_pregunta=".$value['id_grupo_pregunta']." ORDER BY orden";
            $result2 = $oDB->fetchTable($sQuery,TRUE);
            if(is_array($result2) && count($result2)>0){
               foreach($result2 as $i2 => $value2){
                  $id_pregunta = $value2['id_pregunta'];
                  $orden++;

                  $insTpl->clear("RESPUESTAS");

                  ////////////para mostrar los archivos incrustados (imagenes, o archivos) en el preview
                  $file_incrustado="";

                  if(!is_null($value2['URL']) || $value2['URL']!=""){
                        if(ereg("\.(jpg|gif|png|jpeg|jpe|bmp)$",$value2['URL'])){   //si la extension del archivo es una imagen
                           $file_incrustado="<img src='modules/img.php?id_materia_periodo_lectivo=$id_materia_periodo_lectivo&URL=".$value2['URL']."'>";
                        }
                        else
                          $file_incrustado="<a href='modules/file.php?id_materia_periodo_lectivo=$id_materia_periodo_lectivo&URL=".$value2['URL']."'>33".$value2['URL']."</a>";
                  }
                  //////////////////////////////////////////////////////////////////////////////////////////

                  $id_pregunta=$value2['id_pregunta'];

                  $sQuery= "SELECT id_alumno_pregunta, puntuacion, fecha_hora ".
                           "FROM ul_alumno_pregunta ".
                           "WHERE id_alumno_calificable=$id_alumno_calificable AND id_pregunta=$id_pregunta";
                  $result3=$oDB->getFirstRowQuery($sQuery,TRUE);

                  $id_alumno_pregunta="";
                  $puntuacion="";
                  $fecha_hora="";
                  if(is_array($result3)){
                     if(count($result3)>0){
                        $id_alumno_pregunta=$result3['id_alumno_pregunta'];
                        $puntuacion = $result3['puntuacion'];
                        $fecha_hora = $result3['fecha_hora'];
                     }
                  }

                  $correcto=NULL;
                  if($value2['tipo_respuesta']=='M'){

                     $correcto=TRUE;
                     // Respuestas de la Pregunta con Multiples Opciones
                     $sQuery = "SELECT * FROM ul_respuesta WHERE id_pregunta='$id_pregunta' ORDER BY orden";
                     $result3 = $oDB->fetchTable($sQuery,TRUE);

                     if(is_array($result3) && count($result3)>0){
                        foreach($result3 as $i3 => $value3){

                           ////////////para mostrar los archivos incrustados (imagenes, o archivos) en el preview
                           $file_incrustado2="";

                           if(!is_null($value3['URL']) || $value3['URL']!=""){
                              if(ereg("\.(jpg|gif|png|jpeg|jpe|bmp)$",$value3['URL'])){   //si la extension del archivo es una imagen
                                 $file_incrustado2="<img src='modules/img.php?id_materia_periodo_lectivo=$id_materia_periodo_lectivo&URL=".$value3['URL']."'>";
                              }
                              else
                                 $file_incrustado2="<a href='modules/file.php?id_materia_periodo_lectivo=$id_materia_periodo_lectivo&URL=".$value3['URL']."'>".$value3['URL']."</a>";
                           }
                           //////////////////////////////////////////////////////////////////////////////////////////

                           $id_respuesta=$value3['id_respuesta'];
                           $sQuery =   " SELECT count(*) ".
                                       " FROM ul_respuesta_alumno ".
                                       " WHERE id_alumno_pregunta='$id_alumno_pregunta' AND".
                                       " id_respuesta='$id_respuesta'";
                           $result4 = $oDB->getFirstRowQuery($sQuery);

                           if(is_array($result4)){
                              if(count($result4)>0){
                                 $resp=$result4[0];
                              }
                           }

                           // Con un error es falso
                           //($value3['correcto']==$resp);
                           //$correcto*=

                           //////////////////////////////////////////////////////////////////////////////////////////
                           $insTpl->assign("OPCIONES_RESPUESTA","&nbsp;");
                           $insTpl->assign("TEMA_RESPUESTA",chr($value3['orden']+ord('a')-1).") ".$value3['contenido']); //""<a href='$url&editar_calificable=Modificar_Respuesta&id_grupo_pregunta=".$value['id_grupo_pregunta']."&id_pregunta=".$value2['id_pregunta']."&id_respuesta=".$value3['id_respuesta']."' title='Modificar Respuesta'>".chr($value3['orden']+ord('a')-1).") ".$value3['contenido']."</a>&nbsp;&nbsp;&nbsp;$file_incrustado2");
                           $insTpl->assign("VALOR_RESPUESTA",($value3['correcto']==$resp?($value3['correcto']?"<img src='$img_path/verdadero.jpg' />":"<img src='$img_path/falso.jpg' />"):($value3['correcto']?"<img src='$img_path/no_verdadero.gif' />":"<img src='$img_path/no_falso.gif' />"))); //($value3['correcto']?"<img src='$img_path/verdadero.jpg' />":"<img src='$img_path/falso.jpg' />"));
                           $insTpl->parse("RESPUESTAS",".tpl_respuesta");
                        }
                     }else
                        $insTpl->assign("RESPUESTAS","");

                  }else{ // La respuesta es Abierta
                     $sQuery= " SELECT URL_Texto ".
                              " FROM ul_alumno_pregunta AS a_p, ul_respuesta_alumno AS a_r, ul_respuesta_abierta AS r_a ".
                              " WHERE a_p.id_alumno_calificable=$id_alumno_calificable AND ".
                              " a_p.id_pregunta=$id_pregunta AND ".
                              " a_r.id_alumno_pregunta=a_p.id_alumno_pregunta AND ".
                              " r_a.id_respuesta_abierta=a_r.id_respuesta_abierta";
                     $result3=$oDB->getFirstRowQuery($sQuery,TRUE);
                     $URL_Texto="";
                     if(is_array($result3)){
                        if(count($result3)>0){
                           $URL_Texto=$result3['URL_Texto'];

                        }
                     }
                     if($value2['abierta']=='T'){
                        $insTpl->assign("TEMA_RESPUESTA",$URL_Texto==""?"No hay Respuesta.":$URL_Texto);
                        $insTpl->parse("RESPUESTAS",".tpl_respuesta_abierta");
                     }else{
                        $insTpl->assign("TEMA_RESPUESTA",$URL_Texto==""?"No hay Respuesta.":"Download File: <a href='modules/file.php?id_materia_periodo_lectivo=$id_materia_periodo_lectivo&URL=$URL_Texto'>$URL_Texto</a>");
                        $insTpl->parse("RESPUESTAS",".tpl_respuesta_abierta");
                     }
                  }

                  // valor de la pregunta
                  $valor_pregunta = $value2['v_ponderacion'];
                  if($value2['t_ponderacion']=='P')
                     $valor_pregunta *= $this->nota_base;

                  // Información de la Pregunta
                  $insTpl->assign("OPCIONES_PREGUNTA","&nbsp;");
                  $insTpl->assign("TEMA_PREGUNTA",$orden.".- ".$value2['contenido']);
                  $insTpl->assign("INFO_PREGUNTA","visto: $fecha_hora  valor: ".sprintf("%.2f",$valor_pregunta));


                  // Calificacion de la Pregunta
                  $v_pregunta="";
                  if(is_null($correcto)){ // preguntas Abiertas
                     $v_pregunta.="<input type='hidden' name='pregunta[$id_alumno_pregunta][preg]' value='$orden'/>";
                     $v_pregunta.="<input type='hidden' name='pregunta[$id_alumno_pregunta][max]' value='$valor_pregunta'/>";
                     $v_pregunta.="<input type='text' name='pregunta[$id_alumno_pregunta][val]' value='".sprintf("%.2f",$puntuacion)."' size='5' maxlength='5'/>";
                  }
                  else{
                     // Esto fue calificado previamente
                     $valor_pregunta=0;
                     $sQuery= " SELECT puntuacion ".
                              " FROM ul_alumno_pregunta ".
                              " WHERE id_alumno_calificable=$id_alumno_calificable AND id_pregunta=$id_pregunta";
                     $result_tmp=$oDB->getFirstRowQuery($sQuery,TRUE);
                     if(is_array($result_tmp)){
                        if(count($result_tmp)>0)
                           $valor_pregunta=$result_tmp['puntuacion'];
                     }else{
                        return $this->tpl->crearAlerta(
                           "error",
                           "vista_calificar",
                           "El dato no es valido.<br />\n".$oDB->errMsg);
                     }
                     $v_pregunta=sprintf("%.2f",$valor_pregunta);
                  }

                  $insTpl->assign("VALOR_PREGUNTA",$v_pregunta);
                  $insTpl->parse("PREGUNTAS",".tpl_pregunta");
               }
            }else
               $insTpl->assign("PREGUNTAS","");

            $insTpl->assign("OPCIONES_GRUPO","&nbsp;"); //"<a href='$url&editar_calificable=Eliminar_Grupo_Pregunta&id_grupo_pregunta=".$value['id_grupo_pregunta']."' title='Eliminar Grupo' onClick=\"return confirm('Está seguro que desea eliminar este grupo?')\"><img src='$img_path/trash_eliminar.png' border='0' /></a>");
            $insTpl->assign("TEMA_GRUPO","<h2>".$value['contenido']."</h2>"); //"<a href='$url&editar_calificable=Modificar_Grupo_Pregunta&id_grupo_pregunta=".$value['id_grupo_pregunta']."' title='Modificar Grupo'><h2>".$value['contenido']."</h2></a>");
            $insTpl->parse("GRUPOS",".tpl_grupo");
         }
      }else
         $insTpl->assign("GRUPOS","");

      ///Se busca el total de la puntuacion del calificable para mostrarlo debajo del titulo
      $total_puntos=$this->total_puntuacion_calificable($id_calificable);
      $ponderacion = 0;
      $sQuery =   " SELECT SUM(puntuacion) ".
                  " FROM ul_alumno_pregunta ".
                  " WHERE id_alumno_calificable='$id_alumno_calificable' AND puntuacion IS NOT NULL";
      $result = $this->oDB->getFirstRowQuery($sQuery);
      if(is_array($result)){
         if(count($result)>0)
            $ponderacion=$result[0];
      }else{
      }
      $puntuacion="<div align=center> Puntuación Base: = $total_puntos </div><br/>".
                  "<div align=center> Total Obtenido: ".sprintf("%.2f",$ponderacion) ."</div>";


      $insTpl->assign("FORM_NAME","edicion");
      $insTpl->assign("REGRESAR",$url);
      $insTpl->assign("ACTION_URL",$url2);
      $insTpl->assign("NAVEGACION","<input type='submit' name='calificar_calificable' value='Guardar Calificacion' />");
      $insTpl->assign("TITULO",$titulo);
      $insTpl->assign("PUNTUACION",$puntuacion);
      $insTpl->parse("SALIDA","tpl_calificable");
      $pantalla=$insTpl->fetch("SALIDA");
      return $pantalla;
   }

   function total_puntuacion_calificable($id_calificable,$id_pregunta='')
   {
      $db=$this->oDB;
      $total=0;
      $clauseWHERE="";

      if($id_pregunta!="" && $id_pregunta>0)
         $clauseWHERE="and p.id_pregunta<>$id_pregunta";

      $sQuery="SELECT p.t_ponderacion,p.v_ponderacion FROM ul_pregunta p, ul_grupo_pregunta gp ".
              "WHERE p.id_grupo_pregunta=gp.id_grupo_pregunta and gp.id_calificable=$id_calificable $clauseWHERE";
      $result=$db->fetchTable($sQuery,true);

      if(is_array($result)){
         if(count($result)>0)
            foreach($result as $fila){
               $tipo=$fila['t_ponderacion'];
               $valor=$fila['v_ponderacion'];
                  if($tipo=='P')
                     $total+=$valor*$this->nota_base;
                  else
                     $total+=$valor;
            }
      }else{/*
         return $this->tpl->crearAlerta(
            "error",
            "total_puntuacion_calificable",
            "Al calificar calificable: ".$this->oDB->errMsg);
            */
      }
      return $total;
   }

   // Proceso de calificacion del calificable
   function realizar_calificar_calificable()
   {
      $msg1=$msg2=$msg3="";
      $db=$this->oDB;
      if(isset($_POST['pregunta'])){
         //echo "<pre>";print_r($_POST['pregunta']);echo "</pre>";
         foreach($_POST['pregunta'] as $i=>$value){
            $val = (float)$value['val'];
            $max = (float)$value['max'];

            if(!ereg("^[[:digit:]]+(\\.[[:digit:]]*)*$",$value['val']))
                  $msg2 .= "<br/>&nbsp;&nbsp;&nbsp;Preg#".$value['preg'].": El valor ingresado no es valido ( ".$value['val']." )";
            elseif($val<=$max){
               $sQuery = "UPDATE ul_alumno_pregunta SET puntuacion=".$value['val']." WHERE id_alumno_pregunta=$i";
               //echo $sQuery;
               $result=$db->genQuery($sQuery);
               if($result===FALSE){
                  $msg1 .= "<br/>&nbsp;&nbsp;&nbsp;".$this->oDB->errMsg;
               }
            }
            else
               $msg2 .= "<br/>&nbsp;&nbsp;&nbsp;Preg#".$value['preg'].": No se puede ingresar la nota ".$value['val']." porque el valor maximo es ".$value['max']." ";
         }
         $msg3 = $this->actualizar_puntuacion_calificable();
      }

      if($msg1!="" || $msg2!="" || $msg3!=""){
         return $this->tpl->crearAlerta(
            "error",
            "realizar_calificar_calificable",
            $msg1.($msg2==""?"":"Errores encontrados: ".$msg2)).$msg3;
      }
      return TRUE;
   }

   // Proceso de actualizacion de la puntuacion del calificable
   function actualizar_puntuacion_calificable()
   {
      $db = $this->oDB;
      $id_alumno_calificable=recoger_valor("id_alumno_calificable",$_GET,$_POST);

      $sQuery = "SELECT SUM(puntuacion)".
         "FROM ul_alumno_pregunta ".
         "WHERE id_alumno_calificable='$id_alumno_calificable' AND puntuacion IS NOT NULL ";

      $result = $db->getFirstRowQuery($sQuery);
      if(is_array($result)){
         if(count($result)>0){
            $puntuacion=$result[0];

            $sQuery = "UPDATE ul_alumno_calificable SET puntuacion=$puntuacion WHERE id_alumno_calificable=$id_alumno_calificable";
            $result=$db->genQuery($sQuery);
            if($result===FALSE){
               return $this->tpl->crearAlerta(
                  "error",
                  "actualizar_puntuacion_calificable",
                  "Al calificar calificable: ".$db->errMsg);
            }
         }
      }else{
         return $this->tpl->crearAlerta(
            "error",
            "actualizar_puntuacion_calificable",
            "Al calificar calificable: ".$db->errMsg);
      }
      return "";
   }

   function obtener_nota_base(){
      $db=$this->oDB;
      $nota_base=0;

      $sQuery="SELECT valor FROM ul_configuracion WHERE grupo='Notas' and parametro='Nota_base'";
      $result=$db->getFirstRowQuery($sQuery);
      if(is_array($result) && count($result)>0){
         $nota_base=$result[0];
      }

      return $nota_base;
   }
}

?>
