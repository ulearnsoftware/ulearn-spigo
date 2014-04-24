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
// $Id: ul_calificable.class.php,v 1.1.1.1 2006/03/03 21:59:09 ainiguez Exp $

if (isset($gsRutaBase)){
     require_once ("$gsRutaBase/conf/default.conf.php");
     require_once ("$gsRutaBase/lib/paloEntidad.class.php");
     require_once ("$gsRutaBase/lib/paloACL.class.php");
     require_once ("$gsRutaBase/modules/datetime.class.php");
     require_once ("$gsRutaBase/modules/ul_calificable_pregunta.class.php");
     require_once ("$gsRutaBase/modules/ul_archivo_calificable.class.php");
}
else{
   require_once ("conf/default.conf.php");
   require_once ("lib/paloEntidad.class.php");
   require_once ("lib/paloACL.class.php");
   require_once ("modules/datetime.class.php");
   require_once ("modules/ul_calificable_pregunta.class.php");
   require_once ("modules/ul_archivo_calificable.class.php");
}

class ul_calificable extends PaloEntidad
{
   var $sBaseURL;
   var $sDB;
   var $nota_base;

   function ul_calificable(&$oDB, &$oPlantillas,$sBaseURL,$id_materia_periodo_lectivo,$id_calificable='')
   {
      $this->sBaseURL=$sBaseURL;
      $this->sDB=$oDB;
      $oACL=getACL();

      $defTabla = PaloEntidad::describirTabla($oDB, "ul_calificable");
      $defTabla["campos"]["id_calificable"]["DESC"] = "id de clave primaria del calificable";
      $defTabla["campos"]["id_subparcial"]["DESC"]         = "Id del sub-parcial";
      $defTabla["campos"]["codigo"]["DESC"]         = "Orden en el que se mostrarán los calificables";
      $defTabla["campos"]["titulo"]["DESC"]       = "Título del calificable";
      $defTabla["campos"]["base"]["DESC"]    = "Texto del Calificable";
      $defTabla["campos"]["ponderacion"]["DESC"]    = "Ponderacion del Calificable";
     // $defTabla["campos"]["nota"]["DESC"]    = "nota del Calificable";
      $defTabla["campos"]["duracion"]["DESC"]    = "duracion del Calificable";
      $defTabla["campos"]["disponibilidad"]["DESC"]    = "disponibilidad del Calificable";
      $defTabla["campos"]["fecha_inicio"]["DESC"]    = "fecha de inicio del Calificable";
      $defTabla["campos"]["fecha_creacion"]["DESC"]    = "fecha de creacion del Calificable";
      $defTabla["campos"]["fecha_cierre"]["DESC"]    = "fecha de cierre del Calificable";
      $defTabla["campos"]["id_materia_periodo_lectivo"]["DESC"]   = "Id de materia periodo lectivo";
      $this->PaloEntidad($oDB, $oPlantillas, $defTabla);

      $this->nota_base=$this->obtener_nota_base();



   }

   function definicion_Formulario($sNombreFormulario)
   {
      $id_materia_periodo_lectivo=recoger_valor("id_materia_periodo_lectivo",$_GET,$_POST);
      $id_calificable=recoger_valor("id_calificable",$_GET,$_POST);
      $porcentaje_disponible = "";

      $fields = array(
         array(
            "tag"       =>    "Codigo:",
            "name"      =>    "codigo",
            "_empty"    =>    FALSE,
            "_field"    =>    "codigo",
            'size'      =>    40,
            ),
         array(
            "tag"       =>    "Título:",
            "name"      =>    "titulo",
            "_empty"    =>    FALSE,
            "_field"    =>    "titulo",
            'size'      =>    40,
            ),
         array(
            "type"      =>    "integer",
            "tag"       =>    "Duración (min):",
            "name"      =>    "duracion",
            "_empty"    =>    FALSE,
            "_field"    =>    "duracion",
            ),
         array(
            "type"      =>    "integer",
            "tag"       =>    "% Ponderacion:",
            "name"      =>    "ponderacion",
            "_empty"    =>    FALSE,
            "_field"    =>    "ponderacion",
            ),
         array(
            "type"      =>    "html",
            "tag"       =>    "",
            "name"      =>    "disponible",
            "_empty"    =>    TRUE,
            "value"    =>    $porcentaje_disponible."&nbsp;",
            ),
         array(
            "type"      =>    "datetime",
            "tag"       =>    "Fecha de Inicio:",
            "name"      =>    "inicio",
            "_empty"    =>    FALSE,
            "_field"    =>    "fecha_inicio",
            ),
         array(
            "type"      =>    "datetime",
            "tag"       =>    "Fecha de Cierre:",
            "name"      =>    "cierre",
            "_empty"    =>    FALSE,
            "_field"    =>    "fecha_cierre",
            ),
         array(
            "type"      =>    "hidden",
            "name"      =>    "fecha_creacion",
            "value"     =>    strftime("%Y-%m-%d %T"),
            "_field"    =>    "fecha_creacion",
            ),
         array(
            "type"      =>    "hidden",
            "name"      =>    "id_materia_periodo_lectivo",
            "value"     =>    $id_materia_periodo_lectivo,
            "_field"    =>    "id_materia_periodo_lectivo",
            ),
      );

      $id_parcial=recoger_valor("id_parcial",$_GET,$_POST);
      $id_subparcial=recoger_valor("id_subparcial",$_GET,$_POST);

      $arr_parciales=$this->obtener_arreglo_parciales($id_materia_periodo_lectivo);
      $arr_subparciales=$this->obtener_arreglo_subparciales($id_parcial);
      $arr_cabecera1=array();
      $arr_cabecera2=array();

      if($id_calificable>0){  ///Caso modificar
          //Se debe obtener el id de parcial y subparcial del calificable
          $sQuery="SELECT p.id as id_parcial,p.nombre as parcial, sp.id as id_subparcial, sp.nombre as subparcial ".
                  "FROM ul_calificable c, ul_parcial p, ul_subparcial sp ".
                  "WHERE c.id_calificable=$id_calificable and c.id_subparcial=sp.id and sp.id_parcial=p.id";
           $result=$this->sDB->getFirstRowQuery($sQuery,true);
            if(is_array($result) && count($result)>0){
               $parcial=$result['parcial'];
               $subparcial=$result['subparcial'];
            }

          $arr_cabecera1=array(
                           "tag"       =>    "Parcial:",
                           "type"      =>    "label",
                           "value"      =>    $parcial,
                        );
         $arr_cabecera2=array(
                           "tag"       =>    "Subparcial:",
                           "type"      =>    "label",
                           "value"      =>    $subparcial,
                         );
      }
      else{ //caso insert
         $arr_cabecera1=array(
                           "tag"       =>    "Parcial:",
                           "type"      =>    "html",
                           "name"      =>    "id_parcial",
                           "value"     =>    "<select name='id_parcial' onChange='submit()'>".combo($arr_parciales,$id_parcial)."</select>",
                        );

         $arr_cabecera2=array(
                           "tag"       =>    "Subparcial:",
                           "type"      =>    "html",
                           "name"      =>    "id_subparcial",
                           "value"     =>    "<select name='id_subparcial' onChange='submit()'>".combo($arr_subparciales,$id_subparcial)."</select>",
                        );
      }

      array_unshift($fields,$arr_cabecera1,$arr_cabecera2);

      switch($sNombreFormulario){
      case "CREAR_CALIFICABLE":

         if (!$this->definirFormulario("INSERT", $sNombreFormulario, array(
               "title"     =>  "Crear Calificable<br>\n".
                  "<input type='hidden' name='action' value='crear_calificable'>".
                  "<input type='hidden' name='id_materia_periodo_lectivo' value=$id_materia_periodo_lectivo>".
                  "<a href=\"?menu1op=submenu_calificable&submenuop=calf_lista&id_materia_periodo_lectivo=$id_materia_periodo_lectivo\">&laquo;&nbsp;Regresar</a>&nbsp;",
               "submit"    =>  array( "name" => "submit_calificable", "value" => "Guardar", ),
               "fields"    => $fields ,
            ))) die ("ul_calificable::ul_calificable() - al definir formulario INSERT CREAR_CALIFICABLE - ".$this->_msMensajeError);
         break;

      case "MODIFICAR_CALIFICABLE":
         $fields[]=array(
            "type"      =>    "hidden",
            "name"      =>    "id_calificable",
            "_field"    =>    "id_calificable",
            );
         $fields[]=array(
            "type"      =>    "hidden",
            "name"      =>    "id_subparcial",
            "_field"    =>    "id_subparcial",
            );

         if (!$this->definirFormulario("UPDATE", "MODIFICAR_CALIFICABLE", array(
               "title"     =>  "Modificar Calificable<br>\n".
                  "<input type='hidden' name='action' value='modificar_calificable'>".
                  "<input type='hidden' name='id_materia_periodo_lectivo' value=$id_materia_periodo_lectivo>".
                  "<input type='hidden' name='id_calificable' value=$id_calificable>".
                  "<a href=\"?menu1op=submenu_calificable&submenuop=calf_lista&id_materia_periodo_lectivo=$id_materia_periodo_lectivo\">&laquo;&nbsp;Regresar</a>&nbsp;",
               "submit"    =>  array( "name" => "submit_calificable", "value" => "Guardar", ),
               "fields"    =>  $fields,
            ))) die ("ul_calificable::ul_calificable() - al definir formulario UPDATE MODIFICAR_CALIFICABLE - ".$this->_msMensajeError);

         break;
      }
   }


   /**
   * Procedimiento que valida que las copias de las claves de acceso sean iguales.
   *
   * @param string $sNombreFormulario Nombre del formulario que se est�manejando
   * @param array  $formVars          Variables obtenidas del formulario, en arreglo asociativo
   *
   * @return boolean TRUE si los par�etros parecen v�idos hasta ahora, FALSE si no lo son.
   * La rutina puede asignar $this->_msMensajeError a un texto explicativo del error.
   */
   function event_validarValoresFormularioInsert($sNombreFormulario, $formVars){
      $oACL=getACL();
      $bValido=TRUE;

      switch ($sNombreFormulario) {
      case "CREAR_CALIFICABLE":

         if(!isset($_POST['id_subparcial']) || $_POST['id_subparcial']==""){
            $this->setMessage("Debe seleccionar un subparcial");
            return FALSE;
         }

         if (!$oACL->isUserAuthorized($_SESSION['session_user'], 'creat', 'calf_lista')){
            $this->setMessage("Usted no está autorizado para realizar esta acción");
            return FALSE;
         }
         if(!is_numeric($formVars['ponderacion'])){
            $this->setMessage("El valor de ponderación debe ser numérico");
            return FALSE;
         }
         if($formVars['ponderacion']<0 || $formVars['ponderacion']>1){
            $this->setMessage("El valor para la ponderación debe estar entre 0 y 1");
            return FALSE;
         }
         $total=$this->total_ponderacion($_POST['id_subparcial']);
            if(($total+$formVars['ponderacion'])>1){
               $this->setMessage("No se puede almacenar la ponderación porque daría un valor mayor a 1 (100%)");
               return FALSE;
            }

         if($formVars['inicio']>=$formVars['cierre']){
            $this->setMessage("La fecha de cierre no puede ser mayor o igual a la fecha de inicio.");
            return FALSE;
         }

         break;
      }
      return $bValido;
   }



   function event_precondicionInsert($sNombreFormulario, &$dbVars, $formVars) {
      global $config;

      $oDB = $this->_db;
      $this->setMessage("");
      $bValido = parent::event_precondicionInsert($sNombreFormulario, $dbVars, $formVars);
      if ($bValido){
         $id_materia=$this->obtener_id_materia($formVars['id_materia_periodo_lectivo']);
         switch ($sNombreFormulario) {
         case "CREAR_CALIFICABLE":
            $dbVars['fecha_creacion'] = strftime("%Y-%m-%d %T");
            $dbVars['id_subparcial']=$_POST['id_subparcial'];

            break;
         }
      }
      return $bValido;
   }

   function event_postcondicionInsert($sNombreFormulario, $dbVars, $formVars)
   {
         // Enviar crear evento - calificable
         $titulo = $formVars['titulo'];
         $creacion = $formVars['fecha_creacion'];

         $fecha = $formVars['inicio'];
         $inicio = sprintf("%4d-%02d-%02d %02d:%02d:%02d",$fecha['ANIO'],$fecha['MES'],$fecha['DIA'],$fecha['HORA'],$fecha['MINUTO'],$fecha['SEGUNDO']);

         $fecha = $formVars['cierre'];
         $cierre = sprintf("%4d-%02d-%02d %02d:%02d:%02d",$fecha['ANIO'],$fecha['MES'],$fecha['DIA'],$fecha['HORA'],$fecha['MINUTO'],$fecha['SEGUNDO']);

         $id_materia_periodo_lectivo = $formVars['id_materia_periodo_lectivo'];

         $sQuery = "SELECT LAST_INSERT_ID()";
         $result = $this->sDB->getFirstRowQuery($sQuery);
         $id_calificable = $result[0];

         $enlace = "<a href=\"?menu1op=submenu_calificable&submenuop=calf_tomar&id_materia_periodo_lectivo=$id_materia_periodo_lectivo&id_calificable=$id_calificable\">Enlace a: $titulo</a>";

         $sQuery = "INSERT INTO ul_evento (titulo, contenido, creacion, inicio, final, id_calificable, tipo, id_materia_periodo_lectivo) ".
            "VALUES('Calificable: $titulo','$enlace','$creacion','$inicio', '$cierre', '$id_calificable', 'N', '$id_materia_periodo_lectivo')";
         $result = $this->sDB->genQuery($sQuery);
         if($result===FALSE){
            $this->setMessage("No se puede crear el evento");
            return FALSE;
         }
         return FALSE;
   }



   function event_validarValoresFormularioUpdate($sNombreFormulario, $prevPK, $formVars)
   {       ///////Si el usuario tiene los permisos para ingresar se permite la accion, si no se deniega
      $oACL=getACL();
      $bValido=TRUE;

      switch ($sNombreFormulario) {
      case "MODIFICAR_CALIFICABLE":

            if (!$oACL->isUserAuthorized($_SESSION['session_user'], 'updat', 'calf_lista')){
               $this->setMessage("Usted no está autorizado para realizar esta acción");
               return FALSE;
            }
         if(!is_numeric($formVars['ponderacion'])){
            $this->setMessage("El valor de ponderación debe ser numérico");
            return FALSE;
         }
         if($formVars['ponderacion']<0 || $formVars['ponderacion']>1){
            $this->setMessage("El valor para la ponderación debe estar entre 0 y 1");
            return FALSE;
         }
         $total=$this->total_ponderacion($formVars['id_subparcial'],$prevPK['id_calificable']);
            if(($total+$formVars['ponderacion'])>1){
               $this->setMessage("No se puede almacenar la ponderación porque daría un valor mayor a 1 (100%)");
               return FALSE;
            }
         if($formVars['inicio']>=$formVars['cierre']){
            $this->setMessage("La fecha de inicio no puede ser mayor o igual a la fecha de inicio.");
            return FALSE;
         }

         // Enviar modificar Evento - Calificable
         $inicio = $formVars['inicio'];

         $cierre = $formVars['cierre'];

         $id_materia_periodo_lectivo = $formVars['id_materia_periodo_lectivo'];
         $id_calificable = $formVars['id_calificable'];

         $sQuery = "UPDATE ul_evento SET inicio='$inicio', final='$cierre' WHERE id_calificable='$id_calificable' AND id_materia_periodo_lectivo='$id_materia_periodo_lectivo'";
         $result = $this->sDB->genQuery($sQuery);
         if($result===FALSE){
            $this->setMessage("No se puede modificar el evento ".$this->sDB->errMsg);
            return FALSE;
         }

         break;
      }
      return $bValido;
   }

   function event_postcondicionUpdate($sNombreFormulario, $prevPK, $dbVars, $formVars){
      $oACL=getACL();
      $db=$this->getDB();

      switch ($sNombreFormulario) {
         case "MODIFICAR_CALIFICABLE":
               ////Se debe verificar si existen alumnos asignados al calificable y si tienen el estatus de no visto 'N'
               /// entonces actualizar la hora de inicio y cierre del calificable
               $sQuery="SELECT id_alumno_calificable,fecha_inicio,fecha_cierre FROM ul_alumno_calificable ".
                       "WHERE id_calificable=".$dbVars['id_calificable']." and estatus='N'";
               $result=$db->fetchTable($sQuery,true);
                  if(is_array($result) && count($result)>0){
                     foreach($result as $fila){
                        if($fila['fecha_inicio']!=$dbVars['fecha_inicio'] || $fila['fecha_cierre']!=$dbVars['fecha_cierre']){
                           $sPeticionSQL="UPDATE ul_alumno_calificable SET fecha_inicio='".$dbVars['fecha_inicio']."',".
                                          "fecha_cierre='".$dbVars['fecha_cierre']."' WHERE id_alumno_calificable=".$fila['id_alumno_calificable'];
                           $db->genQuery($sPeticionSQL);
                        }
                     }
                  }

         default:
      }

   }

   // obtiene el id de la materia
   // utiliza el $id_materia_periodo_lectivo del formulario (Siempre existe).
   function obtener_id_materia($id_materia_periodo_lectivo){
      $oDB=$this->getDB();
      $sQuery="SELECT id_materia from ul_materia_periodo_lectivo where id=$id_materia_periodo_lectivo";
      $result=$oDB->getFirstRowQuery($sQuery);
      $str="";
         if(is_array($result) && count($result)>0){
            $str=$result[0];
         }
      return $str;
   }

   ////Se debe validar que la ponderacion del calificable no exceda el 100%

   function total_ponderacion($id_subparcial,$id_calificable=""){
      $db=$this->getDB();
      $clauseWHERE="";
         if($id_calificable!="" && $id_calificable>0)
            $clauseWHERE="and id_calificable<>$id_calificable";
      $sQuery="SELECT sum(ponderacion) FROM ul_calificable WHERE id_subparcial=$id_subparcial $clauseWHERE";
      $result=$db->getFirstRowQuery($sQuery);
         if(is_array($result) && count($result)>0){
            $total=$result[0];
            return $total;
         }
   }


   // La vista de edicion realiza una presentacion con los elementos
   // creados para el Cuestionario en un formato más natural
   // permite revisar y modificar el cuestionario


   function vista_edicion($_Get){

      global $config;
      $oDB=$this->sDB;
      $img_path="skins/$config->skin/images";
      $id_materia_periodo_lectivo=$_Get['id_materia_periodo_lectivo'];

      $url=$this->sBaseURL;
      //$pantalla.="<a href='".$url."' title='".$url."'>".substr(htmlentities($url),0,20)."</a> <br/>";
      $url2=$_SERVER['PHP_SELF'].$url;

      $insTpl =& new paloTemplate("skins/".$config->skin);
      $insTpl->definirDirectorioPlantillas("calificable");
      $insTpl->assign("IMG_PATH", "skins/$config->skin/images");

      $titulo='&nbsp;';
      $sQuery = "SELECT titulo FROM ul_calificable WHERE id_calificable=".$_Get['id_calificable'];
      $result = $oDB->getFirstRowQuery($sQuery, TRUE);
      if(is_array($result) && count($result)>0)
         $titulo = $result['titulo'];

      $orden=0;
      // Grupos que forman el Calificable
      $sQuery = "SELECT id_grupo_pregunta,contenido,orden FROM ul_grupo_pregunta WHERE id_calificable=".$_Get['id_calificable']." ORDER BY orden";
      $result = $oDB->fetchTable($sQuery,TRUE);
      if(is_array($result) && count($result)>0){
         foreach($result as $i => $value){
            $insTpl->clear("PREGUNTAS");

            // Preguntas que forman el Grupo del Calificable
            $sQuery = "SELECT * FROM ul_pregunta WHERE id_grupo_pregunta=".$value['id_grupo_pregunta']." ORDER BY orden";
            $result2 = $oDB->fetchTable($sQuery,TRUE);
            if(is_array($result2) && count($result2)>0){
               foreach($result2 as $i2 => $value2){
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

                  if($value2['tipo_respuesta']=='M'){

                     // Respuestas de la Pregunta con Multiples Opciones
                     $sQuery = "SELECT * FROM ul_respuesta WHERE id_pregunta=".$value2['id_pregunta']." ORDER BY orden";
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

                              $insTpl->assign("OPCIONES_RESPUESTA","<a href='$url&editar_calificable=Eliminar_Respuesta&id_grupo_pregunta=".$value['id_grupo_pregunta']."&id_pregunta=".$value2['id_pregunta']."&id_respuesta=".$value3['id_respuesta']."' title='Eliminar Respuesta' onClick=\"return confirm('Está seguro que desea eliminar esta respuesta?')\"><img src='$img_path/trash_eliminar.png' border='0' /></a>");
                              $insTpl->assign("TEMA_RESPUESTA","<a href='$url&editar_calificable=Modificar_Respuesta&id_grupo_pregunta=".$value['id_grupo_pregunta']."&id_pregunta=".$value2['id_pregunta']."&id_respuesta=".$value3['id_respuesta']."' title='Modificar Respuesta'>".chr($value3['orden']+ord('a')-1).") ".nl2br($value3['contenido'])."</a>&nbsp;&nbsp;&nbsp;$file_incrustado2");
                              $insTpl->assign("VALOR_RESPUESTA",($value3['correcto']?"<img src='$img_path/verdadero.jpg' />":"<img src='$img_path/falso.jpg' />"));
                              $insTpl->parse("RESPUESTAS",".tpl_respuesta");
                           }
                        }else
                           $insTpl->assign("RESPUESTAS","");

                  }else{
                     if($value2['abierta']=='T'){
                        $insTpl->assign("TEMA_RESPUESTA","Respuesta Abierta - TEXTO");
                        $insTpl->parse("RESPUESTAS",".tpl_respuesta_abierta");
                     }else{
                        $insTpl->assign("TEMA_RESPUESTA","Respuesta Abierta - ARCHIVO");
                        $insTpl->parse("RESPUESTAS",".tpl_respuesta_abierta");
                     }
                  }
                  $insTpl->assign("OPCIONES_PREGUNTA","<a href='$url&editar_calificable=Eliminar_Pregunta&id_grupo_pregunta=".$value['id_grupo_pregunta']."&id_pregunta=".$value2['id_pregunta']."' title='Eliminar Pregunta' onClick=\"return confirm('Está seguro que desea eliminar esta pregunta?')\"><img src='$img_path/trash_eliminar.png' border='0' /></a>");
                  $insTpl->assign("TEMA_PREGUNTA","<a href='$url&editar_calificable=Modificar_Pregunta&id_grupo_pregunta=".$value['id_grupo_pregunta']."&id_pregunta=".$value2['id_pregunta']."' title='Modificar Pregunta'><b>".$orden.".- ".nl2br($value2['contenido'])."</b></a><br>$file_incrustado");
                  $insTpl->assign("INFO_PREGUNTA","");

                     if($value2['t_ponderacion']=='P')
                        $valor_pregunta=$value2['v_ponderacion']*$this->obtener_nota_base();
                     else
                        $valor_pregunta=$value2['v_ponderacion'];

                  $insTpl->assign("VALOR_PREGUNTA",$valor_pregunta);
                  $insTpl->parse("PREGUNTAS",".tpl_pregunta");
               }
            }else
               $insTpl->assign("PREGUNTAS","");

            $contenido=$value['contenido'];
            $contenido=nl2br($contenido);   
            $insTpl->assign("OPCIONES_GRUPO","<a href='$url&editar_calificable=Eliminar_Grupo_Pregunta&id_grupo_pregunta=".$value['id_grupo_pregunta']."' title='Eliminar Grupo' onClick=\"return confirm('Está seguro que desea eliminar este grupo?')\"><img src='$img_path/trash_eliminar.png' border='0' /></a>");
            $insTpl->assign("TEMA_GRUPO","<a href='$url&editar_calificable=Modificar_Grupo_Pregunta&id_grupo_pregunta=".$value['id_grupo_pregunta']."' title='Modificar Grupo'><h2>".$contenido."</h2></a>");
            $insTpl->parse("GRUPOS",".tpl_grupo");
         }
      }else
         $insTpl->assign("GRUPOS","");

      ///Se busca el total de la puntuacion del calificable para mostrarlo debajo del titulo
      $oPregunta=new ul_calificable_pregunta($oDB,$insTpl,"",""); ///El parametro $_Get se envia como vacio para que no trate de cargar valores en la clase por gusto
      $total_puntos=$oPregunta->total_puntuacion_calificable($_Get['id_calificable']);
      $puntuacion="<div align=center> Total Puntuación: $total_puntos </div>";


      $insTpl->assign("FORM_NAME","edicion");
      $insTpl->assign("REGRESAR",$url);
      $insTpl->assign("ACTION_URL",$url2);
      $insTpl->assign("NAVEGACION","<input type='submit' name='editar_calificable' value='Crear Grupo' />&nbsp;".
                                    "<input type='submit' name='editar_calificable' value='Crear Pregunta' />&nbsp;".
                                    "<input type='submit' name='editar_calificable' value='Crear Respuesta' />");
      $insTpl->assign("TITULO",$titulo);
      $insTpl->assign("PUNTUACION",$puntuacion);
      $insTpl->parse("SALIDA","tpl_calificable");
      $pantalla=$insTpl->fetch("SALIDA");
      return $pantalla;
   }





   function obtener_nota_base(){
   $db=$this->getDB();
   $nota_base=0;

   $sQuery="SELECT valor FROM ul_configuracion WHERE grupo='Notas' and parametro='Nota_base'";
   $result=$db->getFirstRowQuery($sQuery);
      if(is_array($result) && count($result)>0){
         $nota_base=$result[0];
      }

   return $nota_base;


   }

   function obtener_arreglo_parciales($id_materia_periodo_lectivo){
   /////////////Se busca la informacion del grupo parcialde los parciales y subparciales
   $arr_parciales=array(""=>"-- Seleccione un Parcial --");

   $sQuery="SELECT DISTINCT p.id,p.nombre FROM ul_parcial p, ul_grupo_parcial gp, ul_materia_periodo_lectivo mpl, ".
            "ul_subparcial sp WHERE mpl.id=$id_materia_periodo_lectivo and mpl.id_periodo_lectivo=gp.id_periodo_lectivo ".
            "and p.id_grupo_parcial=gp.id and sp.id_parcial=p.id ORDER BY p.id";
   $result=$this->sDB->fetchTable($sQuery,true);
      if(is_array($result) && count($result)>0){
         foreach($result as $fila){
            $arr_parciales[$fila['id']]=$fila['nombre'];
         }
      }
   return $arr_parciales;

   }

   function obtener_arreglo_subparciales($id_parcial){
   /////////////Se busca la informacion del grupo parcialde los parciales y subparciales
   $arr_subparciales=array(""=>"-- Seleccione un Parcial --");

      if($id_parcial>0){
         $sQuery="SELECT sp.id,sp.nombre FROM ul_subparcial sp WHERE sp.id_parcial=$id_parcial ".
                  "ORDER BY sp.id";
         $result=$this->sDB->fetchTable($sQuery,true);
            if(is_array($result) && count($result)>0){
               foreach($result as $fila){
                  $arr_subparciales[$fila['id']]=$fila['nombre'];
               }
            }
         return $arr_subparciales;
      }
   }

   // La vista para calificar realiza una presentacion con los elementos
   // creados en el Cuestionario en un formato más natural
   // permite revisar y calificar el cuestionario segun la información
   // ingresada por el estudiante

   function vista_calificar($_Get){

      global $config;
      $oDB=$this->sDB;
      $img_path="skins/$config->skin/images";
      $id_materia_periodo_lectivo=$_Get['id_materia_periodo_lectivo'];

      $url=$this->sBaseURL;
      //$pantalla.="<a href='".$url."' title='".$url."'>".substr(htmlentities($url),0,20)."</a> <br/>";
      $url2=$_SERVER['PHP_SELF'].$url;

      $insTpl =& new paloTemplate("skins/".$config->skin);
      $insTpl->definirDirectorioPlantillas("calificable");
      $insTpl->assign("IMG_PATH", "skins/$config->skin/images");

      $titulo='&nbsp;';
      $sQuery = "SELECT titulo FROM ul_calificable WHERE id_calificable=".$_Get['id_calificable'];
      $result = $oDB->getFirstRowQuery($sQuery, TRUE);
      if(is_array($result) && count($result)>0)
         $titulo = $result['titulo'];

      $orden=0;
      // Grupos que forman el Calificable
      $sQuery = "SELECT id_grupo_pregunta,contenido,orden FROM ul_grupo_pregunta WHERE id_calificable=".$_Get['id_calificable']." ORDER BY orden";
      $result = $oDB->fetchTable($sQuery,TRUE);
      if(is_array($result) && count($result)>0){
         foreach($result as $i => $value){
            $insTpl->clear("PREGUNTAS");

            // Preguntas que forman el Grupo del Calificable
            $sQuery = "SELECT * FROM ul_pregunta WHERE id_grupo_pregunta=".$value['id_grupo_pregunta']." ORDER BY orden";
            $result2 = $oDB->fetchTable($sQuery,TRUE);
            if(is_array($result2) && count($result2)>0){
               foreach($result2 as $i2 => $value2){
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

                  if($value2['tipo_respuesta']=='M'){

                     // Respuestas de la Pregunta con Multiples Opciones
                     $sQuery = "SELECT * FROM ul_respuesta WHERE id_pregunta=".$value2['id_pregunta']." ORDER BY orden";
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

                              $insTpl->assign("OPCIONES_RESPUESTA","<a href='$url&editar_calificable=Eliminar_Respuesta&id_grupo_pregunta=".$value['id_grupo_pregunta']."&id_pregunta=".$value2['id_pregunta']."&id_respuesta=".$value3['id_respuesta']."' title='Eliminar Respuesta' onClick=\"return confirm('Está seguro que desea eliminar esta respuesta?')\"><img src='$img_path/trash_eliminar.png' border='0' /></a>");
                              $insTpl->assign("TEMA_RESPUESTA","<a href='$url&editar_calificable=Modificar_Respuesta&id_grupo_pregunta=".$value['id_grupo_pregunta']."&id_pregunta=".$value2['id_pregunta']."&id_respuesta=".$value3['id_respuesta']."' title='Modificar Respuesta'>".chr($value3['orden']+ord('a')-1).") ".nl2br($value3['contenido'])."</a>&nbsp;&nbsp;&nbsp;$file_incrustado2");
                              $insTpl->assign("VALOR_RESPUESTA",($value3['correcto']?"<img src='$img_path/verdadero.jpg' />":"<img src='$img_path/falso.jpg' />"));
                              $insTpl->parse("RESPUESTAS",".tpl_respuesta");
                           }
                        }else
                           $insTpl->assign("RESPUESTAS","");

                  }else{
                     if($value2['abierta']=='T'){
                        $insTpl->assign("TEMA_RESPUESTA","Respuesta Abierta - TEXTO");
                        $insTpl->parse("RESPUESTAS",".tpl_respuesta_abierta");
                     }else{
                        $insTpl->assign("TEMA_RESPUESTA","Respuesta Abierta - ARCHIVO");
                        $insTpl->parse("RESPUESTAS",".tpl_respuesta_abierta");
                     }
                  }
                  $insTpl->assign("OPCIONES_PREGUNTA","<a href='$url&editar_calificable=Eliminar_Pregunta&id_grupo_pregunta=".$value['id_grupo_pregunta']."&id_pregunta=".$value2['id_pregunta']."' title='Eliminar Pregunta' onClick=\"return confirm('Está seguro que desea eliminar esta pregunta?')\"><img src='$img_path/trash_eliminar.png' border='0' /></a>");
                  $insTpl->assign("TEMA_PREGUNTA","<a href='$url&editar_calificable=Modificar_Pregunta&id_grupo_pregunta=".$value['id_grupo_pregunta']."&id_pregunta=".$value2['id_pregunta']."' title='Modificar Pregunta'><b>".$orden.".- ".nl2br($value2['contenido'])."</b></a><br>$file_incrustado");
                  $insTpl->assign("INFO_PREGUNTA","");

                     if($value2['t_ponderacion']=='P')
                        $valor_pregunta=$value2['v_ponderacion']*$this->obtener_nota_base();
                     else
                        $valor_pregunta=$value2['v_ponderacion'];

                  $insTpl->assign("VALOR_PREGUNTA",$valor_pregunta);
                  $insTpl->parse("PREGUNTAS",".tpl_pregunta");
               }
            }else
               $insTpl->assign("PREGUNTAS","");

            $insTpl->assign("OPCIONES_GRUPO","<a href='$url&editar_calificable=Eliminar_Grupo_Pregunta&id_grupo_pregunta=".$value['id_grupo_pregunta']."' title='Eliminar Grupo' onClick=\"return confirm('Está seguro que desea eliminar este grupo?')\"><img src='$img_path/trash_eliminar.png' border='0' /></a>");
            $insTpl->assign("TEMA_GRUPO","<a href='$url&editar_calificable=Modificar_Grupo_Pregunta&id_grupo_pregunta=".$value['id_grupo_pregunta']."' title='Modificar Grupo'><h2>".nl2br($value['contenido'])."</h2></a>");
            $insTpl->parse("GRUPOS",".tpl_grupo");
         }
      }else
         $insTpl->assign("GRUPOS","");

      ///Se busca el total de la puntuacion del calificable para mostrarlo debajo del titulo
      $oPregunta=new ul_calificable_pregunta($oDB,$insTpl,"",""); ///El parametro $_Get se envia como vacio para que no trate de cargar valores en la clase por gusto
      $total_puntos=$oPregunta->total_puntuacion_calificable($_Get['id_calificable']);
      $puntuacion="<div align=center> Total Puntuación: $total_puntos </div>";


      $insTpl->assign("FORM_NAME","edicion");
      $insTpl->assign("REGRESAR",$url);
      $insTpl->assign("ACTION_URL",$url2);
      $insTpl->assign("NAVEGACION","<input type='submit' name='editar_calificable' value='Crear Grupo' />&nbsp;".
                                    "<input type='submit' name='editar_calificable' value='Crear Pregunta' />&nbsp;".
                                    "<input type='submit' name='editar_calificable' value='Crear Respuesta' />");
      $insTpl->assign("TITULO",$titulo);
      $insTpl->assign("PUNTUACION",$puntuacion);
      $insTpl->parse("SALIDA","tpl_calificable");
      $pantalla=$insTpl->fetch("SALIDA");
      return $pantalla;
   }

}

?>
