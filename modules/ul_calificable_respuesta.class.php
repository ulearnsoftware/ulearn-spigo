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
// $Id: ul_calificable_respuesta.class.php,v 1.1.1.1 2006/03/03 21:59:09 ainiguez Exp $

if (isset($gsRutaBase)){
     require_once ("$gsRutaBase/conf/default.conf.php");
     require_once ("$gsRutaBase/lib/paloEntidad.class.php");
     require_once ("$gsRutaBase/lib/paloACL.class.php");
     require_once ("$gsRutaBase/modules/ul_archivo_calificable.class.php");
}
else{
   require_once ("conf/default.conf.php");
   require_once ("lib/paloEntidad.class.php");
   require_once ("lib/paloACL.class.php");
   require_once ("modules/ul_archivo_calificable.class.php");
}

class ul_calificable_respuesta extends PaloEntidad
{
   var $sBaseURL;
   var $sDB;
   function ul_calificable_respuesta(&$oDB, &$oPlantillas,$sBaseURL,$_Get)
   {
      $this->sBaseURL=$sBaseURL;
      $this->sDB=$oDB;
      $oACL=getACL();

      //echo "<pre>";print_r($_Get);echo "</pre>";

      $id_materia_periodo_lectivo=NULL;
      if(isset($_Get['id_materia_periodo_lectivo']))
         $id_materia_periodo_lectivo=$_Get['id_materia_periodo_lectivo'];

      $defTabla = PaloEntidad::describirTabla($oDB, "ul_respuesta");
      $defTabla["campos"]["id_respuesta"]["DESC"]     = "id de clave primaria del ul_grupo_pregunta";
      $defTabla["campos"]["orden"]["DESC"]            = "Orden entre las preguntas";
      $defTabla["campos"]["contenido"]["DESC"]        = "Descripcion o titulo del Grupo de Preguntas";
      $defTabla["campos"]["URL"]["DESC"]              = "URL a presentar en lugar del contenido";
      $defTabla["campos"]["correcto"]["DESC"]         = "valor de verdad de la respuesta";
      $defTabla["campos"]["id_pregunta"]["DESC"]      = "id de la pregunta a la que pertenece la respuesta";
      $this->PaloEntidad($oDB, $oPlantillas, $defTabla);

      ///////////////////
      // CREAR

      $crear_id_grupo = (isset($_Get['id_grupo_pregunta'])?$_Get['id_grupo_pregunta']:NULL);
      $crear_id_pregunta = (isset($_Get['id_pregunta'])?$_Get['id_pregunta']:NULL);

         if(isset($_Get['in_ul_respuesta_INSERT_CREAR_RESPUESTAS_id_grupo_pregunta'])){
            $crear_id_grupo=$_Get['in_ul_respuesta_INSERT_CREAR_RESPUESTAS_id_grupo_pregunta'];

            if(isset($_Get['id_grupo_pregunta'])){
               if($crear_id_grupo==$_Get['id_grupo_pregunta'])
                  $crear_id_pregunta = $_Get['in_ul_respuesta_INSERT_CREAR_RESPUESTAS_id_pregunta'];

               if($crear_id_grupo!=$_Get['id_grupo_pregunta'] ||
               (isset($_Get['id_pregunta']) && $crear_id_pregunta!=$_Get['id_pregunta'])){
                  // hay que volver a generar el formulario
                  unset($_POST['in_ul_respuesta_INSERT_CREAR_RESPUESTAS_id_grupo_pregunta']);
                  unset($_POST['in_ul_respuesta_INSERT_CREAR_RESPUESTAS_id_pregunta']);
                  unset($_POST['in_ul_respuesta_INSERT_CREAR_RESPUESTAS_orden']);
                  unset($_POST['in_ul_respuesta_INSERT_CREAR_RESPUESTAS_check_contenido']);
                  unset($_POST['in_ul_respuesta_INSERT_CREAR_RESPUESTAS_contenido']);
                  unset($_POST['in_ul_respuesta_INSERT_CREAR_RESPUESTAS_check_URL']);
                  unset($_POST['in_ul_respuesta_INSERT_CREAR_RESPUESTAS_URL']);
                  unset($_POST['in_ul_respuesta_INSERT_CREAR_RESPUESTAS_tipo']);
               }
            }
         }


      // id de Grupos segun el orden
      $sQuery = "SELECT DISTINCT gp.id_grupo_pregunta, gp.contenido FROM ul_grupo_pregunta gp,ul_pregunta p WHERE gp.id_grupo_pregunta=p.id_grupo_pregunta AND gp.id_calificable='".$_Get['id_calificable']."' AND p.tipo_respuesta='M' ORDER BY gp.orden";
      $result = $oDB->fetchTable($sQuery,TRUE);

      $crear_grupo = array();
      if(is_array($result) && count($result)>0){
         foreach($result as $i => $value){
            if($crear_id_grupo == NULL)
               $crear_id_grupo = $value['id_grupo_pregunta'];
            $crear_grupo[] = array("value"=>$value['id_grupo_pregunta'],
                  "tag"=>$value['contenido'],);
         }
         if(!isset($crear_id_grupo))
            $crear_id_grupo=$value['id_grupo_pregunta'];
      }
      else{
         $crear_id_grupo=1;
         $crear_no_existen_preguntas=TRUE;
      }

      // id de las Preguntas del Grupo segun el orden
      $sQuery = "SELECT id_pregunta,contenido FROM ul_pregunta WHERE id_grupo_pregunta='$crear_id_grupo' ORDER BY orden";
      $result = $oDB->fetchTable($sQuery,TRUE);

      $crear_pregunta=array();
      if(is_array($result) && count($result)>0){
         foreach($result as $i => $value){
            if($crear_id_pregunta==NULL)
               $crear_id_pregunta=$value['id_pregunta'];
            $crear_pregunta[]=array("value"=>$value['id_pregunta'],
                  "tag"=>substr($value['contenido'],0,40),);
         }
      }

      // Orden de las Respuestas de la Pregunta y Grupo seleccionado
      $sQuery = "SELECT * FROM ul_respuesta WHERE id_pregunta='$crear_id_pregunta' ORDER BY orden";
      $result = $oDB->fetchTable($sQuery,TRUE);

      $crear_orden=array();
      $crear_orden[]=array("value"=>"1","tag"=>"Al Inicio",);
      $crear_s_orden=1;
      if(is_array($result) && count($result)>0){
         foreach($result as $i => $value){
            $crear_s_orden = $value['orden']+1;
            $crear_orden[]=array("value"=>$crear_s_orden,
                  "tag"=>"Despues de: ".chr($value['orden']+ord('a')-1).") ".substr($value['contenido'],0,40),);
         }
      }
      $crear_orden[]=array("value"=>$crear_s_orden,
            "tag"=>"Al Final",);

      // Definicion de Formularios
      if (!$this->definirFormulario("INSERT", "CREAR_RESPUESTAS", array(
            "title"     =>  "Crear Respuestas<br>\n".
               "<input type='hidden' name='editar_calificable' value='Crear Respuesta'>".
               "<input type='hidden' name='id_materia_periodo_lectivo' value=$id_materia_periodo_lectivo>".
               "<input type='hidden' name='id_calificable' value=".$_Get['id_calificable'].">".
               "<input type='hidden' name='id_grupo_pregunta' value=".$crear_id_grupo.">".
               "<input type='hidden' name='id_pregunta' value=".$crear_id_pregunta.">".
               "<a href=\"$sBaseURL\">&laquo;&nbsp;Regresar</a>&nbsp;",
            "submit"    =>  array( "name" => "submit_calificable", "value" => "Guardar", ),
             "options" => array( "enctype" => "multipart/form-data", ),
            "fields"    =>  array(
               array(
                  "tag"       =>    "Grupo:",
                  "name"      =>    "id_grupo_pregunta",
                  "type"      =>    "select",
                  "options"   =>    $crear_grupo,
                  "value"     =>    $crear_id_grupo,
                  "onchange"  =>    "submit();"
                  ),
               array(
                  "tag"       =>    "Pregunta:",
                  "name"      =>    "id_pregunta",
                  "type"      =>    "select",
                  "options"   =>    $crear_pregunta,
                  "value"     =>    $crear_id_pregunta,
                  "_field"    =>    "id_pregunta",
                  "onchange"  =>    "submit();"
                  ),
               array(
                  "tag"       =>    "Orden:",
                  "name"      =>    "orden",
                  "type"      =>    "select",
                  "options"   =>    $crear_orden,
                  "value"     =>    $crear_s_orden,
                  "_field"    =>    "orden",
                  ),
             /*  array(
                  "type"      =>  "checkbox",
                  "name"      =>  "check_contenido",
                  "tag"       =>  "Contenido:",
                  "title"     =>  "Una descripcion textual de la respuesta",
                  "value"     =>  TRUE,
                  "onchange"    =>  ""
                  ),*/
               array(
                  "type"      =>    "textarea",
                  "name"      =>    "contenido",
                  "tag"       =>    "Contenido:",
                  "_empty"    =>    TRUE,
                  "_field"    =>    "contenido",
                  'cols'      =>    60,
                  ),
               array(
                  "type"      =>  "checkbox",
                  "name"      =>  "check_URL",
                  "tag"       =>  "Incrustar Imagen:",
                  "title"     =>  "Una descripcion de la respuesta utilizando imágenes",
                  ),
               array(
                  "tag"       =>  "Ubicación Local:",
                  "title"     =>  "Seleccione la ubicación",
                  "type"      =>  "html",
                  "value"     =>  "<input type='file' name='URL' size='50'>",
                  ),
               array(
                  "tag"       =>    "Valor de la Respuesta:",
                  "name"      =>    "tipo",
                  "type"      =>    "select",
                  "options"   =>    array(
                                       array("tag"=>'Verdadero',"value"=>1,),
                                       array("tag"=>'Falso', "value"=>0,),
                                    ),
                  "value"     =>    FALSE,
                  "_field"    =>    "correcto",
                  ),
            ),
         ))) die ("ul_calificable_respuesta::ul_calificable_respuesta() - al definir formulario INSERT CREAR_RESPUESTAS - ".$this->_msMensajeError);


      ///////////////////
      // MODIFICAR

      // Grupo
      $modificar_id_grupo = 1;
      if(isset($_Get['id_grupo_pregunta']))
         $modificar_id_grupo = $_Get['id_grupo_pregunta'];

      $sQuery = "SELECT contenido FROM ul_grupo_pregunta WHERE id_grupo_pregunta='$modificar_id_grupo'";
      $result = $oDB->getFirstRowQuery($sQuery,TRUE);

      $modificar_n_grupo='';
         if(is_array($result) && count($result)>0)
            $modificar_n_grupo=$result['contenido'];

      // Pregunta
      $modificar_id_pregunta = 1;
         if(isset($_Get['id_pregunta']))
            $modificar_id_pregunta = $_Get['id_pregunta'];

      $sQuery = "SELECT contenido FROM ul_pregunta WHERE id_pregunta=$modificar_id_pregunta";
      $result = $oDB->getFirstRowQuery($sQuery,TRUE);

      $modificar_n_pregunta='';

         if(is_array($result) && count($result)>0)
            $modificar_n_pregunta=$result['contenido'];


      $id_respuesta=recoger_valor("id_respuesta",$_GET,$_POST);
      $sQuery="SELECT URL from ul_respuesta WHERE id_respuesta=$id_respuesta";
      $result=$oDB->getFirstRowQuery($sQuery,true);
      $archivo=false;
         if(is_array($result) && count($result)>0){
            if($result['URL']!=NULL || $result['URL']!="")
                  $archivo=true;
         }


      // Orden de las Respuestas de Pregunta
      $modificar_ordenUpdate=array();
      $modificar_s_ordenUpdate=1;
         if(isset($_Get['id_respuesta'])){
            $sQuery = "SELECT * FROM ul_respuesta WHERE id_pregunta='$modificar_id_pregunta' ORDER BY orden";
            $result = $oDB->fetchTable($sQuery,TRUE);

            $modificar_ordenUpdate=array();
               if(is_array($result) && count($result)>0){
                  foreach($result as $i => $value){
                     if($value['id_respuesta']==$_Get['id_respuesta']){
                        $modificar_s_ordenUpdate = $value['orden'];
                        $modificar_ordenUpdate[]=array("value"=>$value['orden'],
                              "tag"=>"Mantener en: ".chr($value['orden']+ord('a')-1).") ",);
                     }
                     else
                        $modificar_ordenUpdate[]=array("value"=>$value['orden'],
                              "tag"=>"Mover a: ".chr($value['orden']+ord('a')-1).") ",);
                  }
               }
         }

      if (!$this->definirFormulario("UPDATE", "MODIFICAR_RESPUESTAS", array(
            "title"     =>  "Modificar Respuesta<br>\n".
               "<input type='hidden' name='editar_calificable' value='Modificar_Respuesta'>".
               "<input type='hidden' name='id_materia_periodo_lectivo' value=$id_materia_periodo_lectivo>".
               "<input type='hidden' name='id_calificable' value=".$_Get['id_calificable'].">".
               "<input type='hidden' name='id_grupo_pregunta' value=".$modificar_id_grupo.">".
               "<a href=\"$sBaseURL\">&laquo;&nbsp;Regresar</a>&nbsp;",
            "submit"    =>  array( "name" => "submit_calificable", "value" => "Guardar", ),
             "options" => array( "enctype" => "multipart/form-data", ),
            "fields"    =>  array(
               array(
                  "type"      =>    "html",
                  "tag"       =>    "Grupo:",
                  "name"      =>    "id_grupo_pregunta",
                  "value"     =>    $modificar_n_grupo,
                  ),
               array(
                  "type"      =>    "hidden",
                  "value"     =>    $modificar_id_pregunta,
                  "_field"    =>    "id_pregunta",
                  ),
               array(
                  "type"      =>    "hidden",
                  "name"      =>    "orden_actual",
                  "_field"    =>    "orden",
                  ),
               array(
                  "type"      =>    "html",
                  "tag"       =>    "Pregunta:",
                  "name"      =>    "id_pregunta",
                  "value"     =>    $modificar_n_pregunta,
                  "_field"    =>    "id_pregunta",
                  ),
               array(
                  "type"      =>    "select",
                  "tag"       =>    "Orden:",
                  "name"      =>    "orden",
                  "options"   =>    $modificar_ordenUpdate,
                  "value"     =>    $modificar_s_ordenUpdate,
                  "_field"    =>    "orden",
                  "align"     =>    "right",
                  ),
               /*array(
                  "type"      =>  "checkbox",
                  "name"      =>  "check_contenido",
                  "tag"       =>  "Descripcion:",
                  "title"     =>  "Una descripcion textual de la respuesta",
                  "value"     =>  TRUE,
                  "onchange"    =>  ""
                  ),*/
               array(
                  "type"      =>    "texto",
                  "name"      =>    "contenido",
                  "tag"       =>    "Contenido:",
                  "_empty"    =>    TRUE,
                  "_field"    =>    "contenido",
                  'size'      =>    40,
                  ),
               array(
                  "type"      =>  "checkbox",
                  "name"      =>  "check_URL",
                  "tag"       =>  "Incrustar Imagen:",
                  "title"     =>  "Una descripcion de la respuesta utilizando imágenes",
                  "value"     =>  $archivo,
                  ),
               array(
                  "tag"       =>  "Ubicación Local:",
                  "title"     =>  "Seleccione la ubicación",
                  "type"      =>  "html",
                  "value"     =>  "<input type='file' name='URL' size='50'>",
                  ),
               array(
                  "tag"       =>    "Valor de la Respuesta:",
                  "name"      =>    "tipo",
                  "type"      =>    "select",
                  "options"   =>    array(
                                          array("tag"=>'Verdadero',"value"=>1,),
                                          array("tag"=>'Falso', "value"=>0,),
                                       ),
                  "value"     =>    FALSE,
                  "_field"    =>    "correcto",
                  ),
            ),
         ))) die ("ul_calificable_respuesta::ul_calificable_respuesta() - al definir formulario UPDATE MODIFICAR_RESPUESTAS - ".$this->_msMensajeError);
     }


   /**
   * Procedimiento que valida que las copias de las claves de acceso sean iguales.
   *
   * @param string $sNombreFormulario Nombre del formulario que se est?manejando
   * @param array  $formVars          Variables obtenidas del formulario, en arreglo asociativo
   *
   * @return boolean TRUE si los par?etros parecen v?idos hasta ahora, FALSE si no lo son.
   * La rutina puede asignar $this->_msMensajeError a un texto explicativo del error.
   */
   function event_validarValoresFormularioInsert($sNombreFormulario, $formVars){
      $oACL=getACL();
      $bValido=TRUE;

      switch ($sNombreFormulario) {
      case "CREAR_RESPUESTAS":
         if (!$oACL->isUserAuthorized($_SESSION['session_user'], 'creat', 'calf_lista')){
            $this->setMessage("Usted no está autorizado para realizar esta acción");
            return FALSE;
         }
         break;
        /* if(isset($formVars['check_contenido'])){
            if(!isset($formVars['contenido']) || trim($formVars['contenido'])){
               $this->setMessage("No ha ingresado la descripción");
               return FALSE;
            }
         }*/
         if(isset($formVars['check_URL'])){
            if(!isset($formVars['URL']) || trim($formVars['URL'])){
               $this->setMessage("No ha seleccionado una imagen");
               return FALSE;
            }
         }
      /*   if(!isset($formVars['check_contenido']) && !isset($formVars['check_URL'])){
            $this->setMessage("Es necesario seleccionar la descripción y/o la imagen");
            return FALSE;
         }*/
      }
      return $bValido;
   }

    /**
     * Procedimiento para realizar la insercin en la tabla acl_user ANTES de insertar en
     * la tabla sa_alumno. El ID de insercin en acl_user es requerido para la insercin
     * en sa_alumno.
     *
     * @param string $sNombreFormulario Nombre del formulario que se est?manejando
     * @param array  $dbVars            Variables a insertar en la base de datos, con comillas
     * @param array  $formVars          Variables del formulario de insercin
     *
     * @return boolean TRUE si se complet la precondicin, FALSE si no.
     */
   function event_precondicionInsert($sNombreFormulario, &$dbVars, $formVars) {
      global $config;

      $oDB = $this->_db;
      $this->setMessage("");
      $bValido = parent::event_precondicionInsert($sNombreFormulario, $dbVars, $formVars);
      if ($bValido){
         switch ($sNombreFormulario) {
         case "CREAR_RESPUESTAS":
            // actualizar el orden
            $sQuery = "UPDATE ul_respuesta SET orden=orden+1 WHERE orden>=".$formVars['orden']." AND id_pregunta=".$formVars['id_pregunta'];
            $result = $oDB->genQuery($sQuery);


            if($result===FALSE){
               $this->setMessage("No se pudo actualizar el orden de las respuestas. Error:".$oDB->errMsg);
               return FALSE;
            }

            ////Si se tiene un valor en $files se debe realizar el upload
            if(isset($_FILES['URL']['name'])){
               if($_FILES['URL']['name']=="")
                  $dbVars['URL']=NULL;
               else{
                  $oFile=new ul_archivo_calificable($oDB);
                  $bValido=$oFile->realizar_upload('URL',$_POST['id_materia_periodo_lectivo']);
                     if(!$bValido){
                        $this->setMessage($oFile->getMessage());
                        return FALSE;
                     }
                     else
                        $dbVars['URL']=urlencode($oFile->archivo);
               }
            }


            break;
         }
      }
      return $bValido;
  }



   /**
    * Verificar que el login no sea usado por otro usuario al actualizar alumno.
    *
    * @param string $sNombreFormulario Nombre del formulario que se est?manejando
    * @param array  $prevPK            Clave primaria previa del registro modificado
    * @param array  $formVars          Variables obtenidas del formulario, en arreglo asociativo
    *
    * @return boolean TRUE si los par?etros parecen v?idos hasta ahora, FALSE si no lo son.
    * La rutina puede asignar $this->_msMensajeError a un texto explicativo del error.
    */
   function event_validarValoresFormularioUpdate($sNombreFormulario, $prevPK, $formVars)
   {       ///////Si el usuario tiene los permisos para ingresar se permite la accion, si no se deniega
      $oACL=getACL();
      $bValido=TRUE;

      switch ($sNombreFormulario) {
      case "MODIFICAR_RESPUESTAS":

            if (!$oACL->isUserAuthorized($_SESSION['session_user'], 'updat', 'calf_lista')){
               $this->setMessage("Usted no está autorizado para realizar esta acción");
               return FALSE;
            }
            if($formVars['contenido']=="" && !isset($formVars['check_URL'])){
               $this->setMessage("Debe ingresar un contenido y/o una imagen");
               return FALSE;
            }


         break;
      }
      return $bValido;
   }

  /**
   * Procedimiento para realizar operaciones previas a la insercion de la tupla en la base
   * de datos. Esta implementacion guarda el valor previo del login, y modifica el login para
   * guardar el nuevo valor indicado en el formulario.
   *
   * @param string $sNombreFormulario Nombre del formulario que se est?manejando
   * @param array  $prevPK            Clave primaria previa del registro modificado
   * @param array  $dbVars            Variables a insertar en la base de datos, con comillas
   * @param array  $formVars          Variables del formulario de insercin
   *
   * @return boolean TRUE si se complet la precondicin, FALSE si no.
   */
   function event_precondicionUpdate($sNombreFormulario, $prevPK, &$dbVars, $formVars){
      global $config;
      $oDB = $this->_db;
      $bExito = parent::event_precondicionUpdate($sNombreFormulario, $prevPK, $dbVars, $formVars);

      if ($bExito){
         switch ($sNombreFormulario){
         case "MODIFICAR_RESPUESTAS":

            $orden = $formVars['orden'];
            $orden_actual = $formVars['orden_actual'];
            $id_pregunta = $formVars['id_pregunta'];

            if($orden!=$orden_actual){
               if($orden<$orden_actual){
                  $sQuery = "UPDATE ul_respuesta SET orden=orden+1 WHERE orden BETWEEN ".$orden." AND ".($orden_actual-1)." AND id_pregunta=$id_pregunta";
               }elseif($orden>$orden_actual){
                  $sQuery = "UPDATE ul_respuesta SET orden=orden-1 WHERE orden BETWEEN ".($orden_actual+1)." AND ".$orden." AND id_pregunta=$id_pregunta";
               }

               $result = $oDB->genQuery($sQuery);
               if($result===FALSE){
                  $this->setMessage("No se pudo actualizar el orden de las respuestas. Error:".$oDB->errMsg);
                  return FALSE;
               }
            }



            if(isset($formVars['check_URL']) && $formVars['check_URL']){

               ////Si se tiene un valor en $files se debe realizar el upload
               if(isset($_FILES['URL']['name'])){
                  if($_FILES['URL']['name']!=""){
                     $oFile=new ul_archivo_calificable($oDB);
                     $bValido=$oFile->realizar_upload('URL',$_POST['id_materia_periodo_lectivo']);
                        if(!$bValido){
                           $this->setMessage($oFile->getMessage());
                           return FALSE;
                        }
                        else{
                           $dbVars['URL']=urlencode($oFile->archivo);
                        }
                  }
               }
            }
            else
               $dbVars['URL']=NULL;

            break;
         }
      }
      return $bExito;
   }

   function eliminar_respuesta($id_respuesta)
   {
      if($id_respuesta==NULL)
         return FALSE;

      $sQueues="DELETE ul_respuesta WHERE id_respuesta=$id_respuesta";
      $result = $oDB->genQuery($sQuery);

      if($result===FALSE){
         $this->setMessage("No se pudo eliminar la respuesta seleccionada.");
         return FALSE;
      }
   }



}

?>
