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
// $Id: ul_calificable_pregunta.class.php,v 1.1.1.1 2006/03/03 21:59:09 ainiguez Exp $

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

class ul_calificable_pregunta extends PaloEntidad
{
   var $sBaseURL;
   var $sDB;
   var $nota_base;

   function ul_calificable_pregunta(&$oDB, &$oPlantillas,$sBaseURL,$_Get)
   {
      $this->sBaseURL=$sBaseURL;
      $this->sDB=$oDB;
      $oACL=getACL();
      //echo "<pre>";print_r($_Get);echo "</pre>";

      $defTabla = PaloEntidad::describirTabla($oDB, "ul_pregunta");
      $defTabla["campos"]["id_pregunta"]["DESC"]      = "id de clave primaria del ul_grupo_pregunta";
      $defTabla["campos"]["contenido"]["DESC"]        = "Descripcion o titulo del Grupo de Preguntas";
      $defTabla["campos"]["orden"]["DESC"]            = "Orden entre las preguntas";
      $defTabla["campos"]["tipo_respuesta"]["DESC"]   = "tipo de respuesta (abierta o multiple)";
      $defTabla["campos"]["abierta"]["DESC"]          = "tipos de respuesta abierta";
      $defTabla["campos"]["t_ponderacion"]["DESC"]    = "tipo de ponderacion (valor o porcentaje)";
      $defTabla["campos"]["v_ponderacion"]["DESC"]    = "ponderacion de la pregunta segun el tipo";
      $defTabla["campos"]["id_grupo_pregunta"]["DESC"]= "id del grupo al que pertenece la pregunta";
      $this->PaloEntidad($oDB, $oPlantillas, $defTabla);

            //Se obtiene la nota base
      $this->nota_base=$this->obtener_nota_base();

      $id_materia_periodo_lectivo=NULL;
      if(!is_array($_Get))
         return; ///En caso de que $_Get no sea arreglo se termina la ejecucion
      if(isset($_Get['id_materia_periodo_lectivo']))
         $id_materia_periodo_lectivo=$_Get['id_materia_periodo_lectivo'];

      ///////////////////
      // CREAR

      $crear_id_grupo = (isset($_Get['id_grupo_pregunta'])?$_Get['id_grupo_pregunta']:NULL);
      $crear_id_pregunta = (isset($_Get['id_pregunta'])?$_Get['id_pregunta']:NULL);
      if(isset($_Get['in_ul_pregunta_INSERT_CREAR_PREGUNTAS_id_grupo_pregunta'])){
         $crear_id_grupo=$_Get['in_ul_pregunta_INSERT_CREAR_PREGUNTAS_id_grupo_pregunta'];

         if(isset($_Get['id_grupo_pregunta']) &&
         $crear_id_grupo!=$_Get['id_grupo_pregunta']){
            // hay que volver a generar el formulario
            unset($_POST['in_ul_pregunta_INSERT_CREAR_PREGUNTAS_id_grupo_pregunta']);
            unset($_POST['in_ul_pregunta_INSERT_CREAR_PREGUNTAS_orden']);
            unset($_POST['in_ul_pregunta_INSERT_CREAR_PREGUNTAS_contenido']);
            unset($_POST['in_ul_pregunta_INSERT_CREAR_PREGUNTAS_tipo_respuesta']);
            unset($_POST['in_ul_pregunta_INSERT_CREAR_PREGUNTAS_abierta']);
            unset($_POST['in_ul_pregunta_INSERT_CREAR_PREGUNTAS_t_ponderacion']);
            unset($_POST['in_ul_pregunta_INSERT_CREAR_PREGUNTAS_v_ponderacion']);
         }
      }elseif(isset($_Get['id_grupo_pregunta']))
         $crear_id_grupo=$_Get['id_grupo_pregunta'];

      // id de Grupos segun el orden

      $sQuery = "SELECT * FROM ul_grupo_pregunta WHERE id_calificable='".$_Get['id_calificable']."' ORDER BY orden";
      $result = $oDB->fetchTable($sQuery,TRUE);

      $crear_grupo=array();

      if(is_array($result) && count($result)>0){
         foreach($result as $i => $value){
            if($crear_id_grupo==NULL)
               $crear_id_grupo=$value['id_grupo_pregunta'];
            $crear_grupo[]=array("value"=>$value['id_grupo_pregunta'],"tag"=>substr($value['contenido'],0,40)."...");
         }
      }

      // Orden de las Preguntas del Grupo
      $sQuery = "SELECT * FROM ul_pregunta WHERE id_grupo_pregunta='$crear_id_grupo' ORDER BY orden";
      $result = $oDB->fetchTable($sQuery,TRUE);

      $crear_orden=array();
      $crear_orden[]=array("value"=>"1",
            "tag"=>"Al Inicio",);
      $crear_s_orden=1;
      if(is_array($result) && count($result)>0){
         foreach($result as $i => $value){
            $crear_s_orden = $value['orden']+1;
            $contenido=substr($value['contenido'],0,40);
            $crear_orden[]=array("value"=>$crear_s_orden,"tag"=>"Despues de: ".$contenido."...");
         }
      }
      $crear_orden[]=array("value"=>$crear_s_orden,
            "tag"=>"Al Final",);


      ///////////////////
      // MODIFICAR

      $modificar_id_grupo = 1;
      if(isset($_Get['id_grupo_pregunta']))
         $modificar_id_grupo = $_Get['id_grupo_pregunta'];

      // Grupos
      $sQuery = "SELECT contenido FROM ul_grupo_pregunta WHERE id_grupo_pregunta='$modificar_id_grupo' ORDER BY orden";
      $result = $oDB->getFirstRowQuery($sQuery,TRUE);

      $modificar_n_grupo="";
      if(is_array($result) && count($result)>0)
         $modificar_n_grupo=$result['contenido'];

      $modificar_orden=1;
      $modificar_s_orden=1;
      // Orden de las Preguntas del Grupo
      if(isset($_Get['id_pregunta'])){
         $sQuery = "SELECT * FROM ul_pregunta WHERE id_grupo_pregunta='$modificar_id_grupo' ORDER BY orden";
         $result = $oDB->fetchTable($sQuery,TRUE);

         $modificar_orden = array();
         if(is_array($result) && count($result)>0){
            foreach($result as $i => $value){
               if($value['id_pregunta']==$_Get['id_pregunta']){
                  $modificar_s_orden = $value['orden'];
                  $modificar_orden[]=array("value"=>$value['orden'],
                        "tag"=>"Mantener en: ".$value['orden'].") ".substr($value['contenido'],0,40)."...",);
               }
               else
                  $modificar_orden[]=array("value"=>$value['orden'],
                        "tag"=>"Mover a: ".$value['orden'].") ".substr($value['contenido'],0,40)."...",);
            }
         }
      }


      // Definicion de Formularios
      if (!$this->definirFormulario("INSERT", "CREAR_PREGUNTAS", array(
            "title"     =>  "Crear Pregunta<br>\n".
               "<input type='hidden' name='editar_calificable' value='Crear Pregunta'>".
               "<input type='hidden' name='id_materia_periodo_lectivo' value=$id_materia_periodo_lectivo>".
               "<input type='hidden' name='id_calificable' value=".$_Get['id_calificable'].">".
               "<input type='hidden' name='id_grupo_pregunta' value=".$crear_id_grupo.">".
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
                  "_field"    =>    "id_grupo_pregunta",
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
               array(
                  "type"      =>    "textarea",
                  "tag"       =>    "Contenido:",
                  "name"      =>    "contenido",
                  "_empty"    =>    FALSE,
                  "_field"    =>    "contenido",
                  'cols'      =>    60,
                  ),
               array(
                  "tag"       =>  "Ubicación Local:",
                  "title"     =>  "Seleccione la ubicación",
                  "type"      =>  "html",
                  "value"     =>  "<input type='file' name='URL' size='50'>",
                  ),
               array(
                  "tag"       =>    "Tipo de Respuesta:",
                  "name"      =>    "tipo_respuesta",
                  "type"      =>    "select",
                  "options"   =>    array(
                                          array("tag"=>'Abierta',"value"=>'A',),
                                          array("tag"=>'Cerrada',"value"=>'M',),
                                    ),
                  "value"     =>    'M',
                  "_field"    =>    "tipo_respuesta",
                  ),
               array(
                  "tag"       =>    "Abierta:",
                  "name"      =>    "abierta",
                  "type"      =>    "select",
                  "options"   =>    array(
                                          array("tag"=>'Texto',"value"=>'T',),
                                          array("tag"=>'Archivo',"value"=>'A',),
                                    ),
                  "_field"    =>    "abierta",
                  ),
               array(
                  "tag"       =>    "Tipo de Ponderación:",
                  "name"      =>    "t_ponderacion",
                  "type"      =>    "select",
                  "options"   =>    array(
                                          array("tag"=>'Porcentaje', "value"=>'P',),
                                          array("tag"=>'Valor',"value"=>'V',),
                                    ),
                  "_field"    =>    "t_ponderacion",
                  ),
               array(
                  "tag"       =>    "Ponderación: ",
                  "name"      =>    "v_ponderacion",
                  "type"      =>    "text",
                  "value"     =>    0,
                  "_empty"    =>    FALSE,
                  "_field"    =>    "v_ponderacion",
                  ),
            ),
         ))) die ("ul_calificable_grupo_preguntas::ul_calificable_grupo_preguntas() - al definir formulario INSERT CREAR_PREGUNTAS - ".$this->_msMensajeError);


      ////Se busca si la pregunta tiene asignado un archivo
      $id_pregunta=recoger_valor("id_pregunta",$_GET,$_POST,'');
      $archivo="";

         if($id_pregunta>0){
            $sQuery="SELECT URL FROM ul_pregunta WHERE id_pregunta=$id_pregunta";
            $result=$oDB->getFirstRowQuery($sQuery);
               if(is_array($result) && count($result)>0){
                  if($result[0]==NULL || $result[0]=="")
                     $archivo=0;
                  else
                     $archivo=1;
               }
         }

      if (!$this->definirFormulario("UPDATE", "MODIFICAR_PREGUNTAS", array(
            "title"     =>  "Modificar Pregunta<br>\n".
               "<input type='hidden' name='editar_calificable' value='Modificar_Pregunta'>".
               "<input type='hidden' name='id_materia_periodo_lectivo' value=$id_materia_periodo_lectivo>".
               "<input type='hidden' name='id_calificable' value=".$_Get['id_calificable'].">".
               "<a href=\"$sBaseURL\">&laquo;&nbsp;Regresar</a>&nbsp;",
            "submit"    =>  array( "name" => "submit_calificable", "value" => "Guardar", ),
            "options" => array( "enctype" => "multipart/form-data", ),
            "fields"    =>  array(
               array(
                  "type"      =>    "hidden",
                  "name"      =>    "id_calificable",
                  "value"     =>    $_Get['id_calificable'],
                  ),
               array(
                  "type"      =>    "hidden",
                  "name"      =>    "orden_actual",
                  "value"     =>    $modificar_s_orden,
                  ),
               array(
                  "type"      =>    "html",
                  "tag"       =>    "Grupo:",
                  "name"      =>    "n_grupo_pregunta",
                  "value"     =>    $modificar_n_grupo,
                  ),
               array(
                  "type"      =>    "hidden",
                  "name"      =>    "id_grupo_pregunta",
                  "value"     =>    $modificar_id_grupo,
                  ),
               array(
                  "tag"       =>    "Orden:",
                  "name"      =>    "orden",
                  "type"      =>    "select",
                  "options"   =>    $modificar_orden,
                  "value"     =>    $modificar_s_orden,
                  "_field"    =>    "orden",
                  ),
               array(
                  "type"      =>    "textarea",
                  "tag"       =>    "Contenido:",
                  "name"      =>    "contenido",
                  "_empty"    =>    FALSE,
                  "_field"    =>    "contenido",
                  'cols'      =>    60,
                  ),
               array(
                  "type"      =>    "checkbox",
                  "tag"       =>    "Archivo Incrustado",
                  "name"      =>    "archivo",
                  "value"      =>    $archivo,
                  ),
               array(
                  "tag"       =>  "Ubicación Local:",
                  "title"     =>  "Seleccione la ubicación",
                  "type"      =>  "html",
                  "value"     =>  "<input type='file' name='URL' size='50'>",
                  ),
               array(
                  "tag"       =>    "Tipo de Respuesta:",
                  "name"      =>    "tipo_respuesta",
                  "type"      =>    "select",
                  "options"   =>    array(
                                       array("tag"=>'Abierta', "value"=>'A',),
                                       array("tag"=>'Cerrada',"value"=>'M',),
                                    ),
                  "_field"    =>    "tipo_respuesta",
                  ),
               array(
                  "tag"       =>    "Abierta:",
                  "name"      =>    "abierta",
                  "type"      =>    "select",
                  "options"   =>    array(
                                          array("tag"=>'Texto',"value"=>'T',),
                                          array("tag"=>'Archivo',"value"=>'A',),
                                    ),
                  "_field"    =>    "abierta",
                  ),
               array(
                  "tag"       =>    "Tipo de Ponderación:",
                  "name"      =>    "t_ponderacion",
                  "type"      =>    "select",
                  "options"   =>    array(
                                          array("tag"=>'Porcentaje',"value"=>'P',),
                                          array("tag"=>'Valor',"value"=>'V',),
                                    ),
                  "_field"    =>    "t_ponderacion",
                  ),
               array(
                  "tag"       =>    "Ponderación: ",
                  "name"      =>    "v_ponderacion",
                  "type"      =>    "text",
                  "_empty"    =>    FALSE,
                  "_field"    =>    "v_ponderacion",
                  ),
            ),
         ))) die ("ul_calificable_grupo_pregunta::ul_calificable_grupo_pregunta() - al definir formulario UPDATE MODIFICAR_PREGUNTAS - ".$this->_msMensajeError);



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
      case "CREAR_PREGUNTAS":
         if (!$oACL->isUserAuthorized($_SESSION['session_user'], 'creat', 'calf_lista')){
            $this->setMessage("Usted no está autorizado para realizar esta acción");
            return FALSE;
         }
         break;
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
         case "CREAR_PREGUNTAS":
               if(!$this->validar_ponderacion($formVars))
                  return FALSE;  ///msg de error seteado por la funcion


            // actualizar el orden
            $sQuery = "UPDATE ul_pregunta SET orden=orden+1 WHERE orden>=".$formVars['orden']." AND id_grupo_pregunta=".$formVars['id_grupo_pregunta'];
            $result = $oDB->genQuery($sQuery);

            if($result===FALSE){
               $this->setMessage("No se pudo actualizar el orden de las preguntas del calificable.");
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
   function event_validarValoresFormularioUpdate($sNombreFormulario, $prevPK, &$formVars)
   {       ///////Si el usuario tiene los permisos para ingresar se permite la accion, si no se deniega
      $oACL=getACL();
      $bValido=TRUE;

      switch ($sNombreFormulario) {
      case "MODIFICAR_PREGUNTAS":

            if (!$oACL->isUserAuthorized($_SESSION['session_user'], 'updat', 'calf_lista')){
               $this->setMessage("Usted no está autorizado para realizar esta acción");
               return FALSE;
            }
            $formVars['id_pregunta']=$prevPK['id_pregunta'];

            if(!$this->validar_ponderacion($formVars))
               return FALSE;  ///msg de error seteado por la funcion


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
   function event_precondicionUpdate($sNombreFormulario, $prevPK, &$dbVars, &$formVars){
      global $config;
      $oDB = $this->_db;
      $bExito = parent::event_precondicionUpdate($sNombreFormulario, $prevPK, $dbVars, $formVars);

      if ($bExito){
         switch ($sNombreFormulario){
         case "MODIFICAR_PREGUNTAS":
            $orden = $formVars['orden'];
            $orden_actual = $formVars['orden_actual'];
            $id_calificable = $formVars['id_calificable'];
            $id_grupo_pregunta = $formVars['id_grupo_pregunta'];

            if($orden!=$orden_actual){
               if($orden<$orden_actual){
                  $sQuery = "UPDATE ul_pregunta SET orden=orden+1 WHERE orden BETWEEN ".$orden." AND ".($orden_actual-1)." AND id_grupo_pregunta=$id_grupo_pregunta";
               }elseif($orden>$orden_actual){
                  $sQuery = "UPDATE ul_pregunta SET orden=orden-1 WHERE orden BETWEEN ".($orden_actual+1)." AND ".$orden." AND id_grupo_pregunta=$id_grupo_pregunta";
               }

               $result = $oDB->genQuery($sQuery);

               if($result===FALSE){
                  $this->setMessage("No se pudo actualizar el orden de la pregunta del calificable.".$oDB->errMsg. " ".$sQuery);
                  return FALSE;
               }
            }

            if(isset($formVars['archivo']) && $formVars['archivo']){

               ////Si se tiene un valor en $files se debe realizar el upload
               if(isset($_FILES['URL']['name'])){
                  if($_FILES['URL']['name']!=""){
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
            }
            else
               $dbVars['URL']=NULL;
            break;
         }
      }
      return $bExito;
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


function validar_ponderacion($formVars){

$db=$this->getDB();
///Se debe verificar la ponderacion
   if(!is_numeric($formVars['v_ponderacion'])){
      $this->setMessage("El valor no es de tipo numerico");
      return FALSE;
   }


   if($formVars['t_ponderacion']=='V'){
      //si la ponderacion es valor no debe ser mayor que la nota base
      if($formVars['v_ponderacion']>$this->nota_base){
         $this->setMessage("El valor de la puntuacion no puede ser mayor a ".$this->nota_base);
         return FALSE;
      }
   }
   else{ //se asume que el valor va a ser ponderado, debe ser un valor entre 0 y 1
      if($formVars['v_ponderacion']<0 || $formVars['v_ponderacion']>1){
         $this->setMessage("El valor para la ponderación debe estar entre 0 y 1");
         return FALSE;
      }
   }
   ///Ahora se debe verificar que el valor no sobrepase la maxima puntuacion
   $sQuery="SELECT id_calificable from ul_grupo_pregunta WHERE id_grupo_pregunta=".$formVars['id_grupo_pregunta'];
   $result=$db->getFirstRowQuery($sQuery);
      if(is_array($result) && count($result)>0)
         $id_calificable=$result[0];

   $total=0;
      if(isset($id_calificable) && $id_calificable>0){
         if(isset($formVars['id_pregunta']))
            $id_pregunta=$formVars['id_pregunta'];
         else
            $id_pregunta="";
         $total=$this->total_puntuacion_calificable($id_calificable,$id_pregunta);
      }

      if($formVars['t_ponderacion']=='P')
         $total+=$formVars['v_ponderacion']*$this->nota_base;
      else
         $total+=$formVars['v_ponderacion'];

      if($total>$this->nota_base){
         $this->setMessage("No se pudo guardar la pregunta porque el valor de puntuación excediría a ".$this->nota_base);
         return FALSE;
      }
      else
         return TRUE;

}

   function total_puntuacion_calificable($id_calificable,$id_pregunta=''){
      $db=$this->getDB();
      $total=0;
      $clauseWHERE="";

         if($id_pregunta!="" && $id_pregunta>0)
            $clauseWHERE="and p.id_pregunta<>$id_pregunta";

      $sQuery="SELECT p.t_ponderacion,p.v_ponderacion FROM ul_pregunta p, ul_grupo_pregunta gp ".
              "WHERE p.id_grupo_pregunta=gp.id_grupo_pregunta and gp.id_calificable=$id_calificable $clauseWHERE";
      $result=$db->fetchTable($sQuery,true);

         if(is_array($result) && count($result)>0){
            foreach($result as $fila){
               $tipo=$fila['t_ponderacion'];
               $valor=$fila['v_ponderacion'];
                  if($tipo=='P')
                     $total+=$valor*$this->nota_base;
                  else
                     $total+=$valor;
            }
         }
      return $total;
   }

}

?>
