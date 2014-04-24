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
// $Id: ul_calificable_grupo_pregunta.class.php,v 1.1.1.1 2006/03/03 21:59:09 ainiguez Exp $

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

class ul_calificable_grupo_pregunta extends PaloEntidad
{
   var $sBaseURL;
   var $sDB;
   function ul_calificable_grupo_pregunta(&$oDB, &$oPlantillas,$sBaseURL,$_Get)
   {
      $this->sBaseURL=$sBaseURL;
      $this->sDB=$oDB;
      $oACL=getACL();

      //echo "<pre>";print_r($_Get);echo "</pre>";

      $id_materia_periodo_lectivo=NULL;
      if(isset($_Get['id_materia_periodo_lectivo']))
         $id_materia_periodo_lectivo=$_Get['id_materia_periodo_lectivo'];

      $id_calificable=NULL;
      if(isset($_Get['id_calificable']))
         $id_calificable=$_Get['id_calificable'];

      $defTabla = PaloEntidad::describirTabla($oDB, "ul_grupo_pregunta");
      $defTabla["campos"]["id_grupo_pregunta"]["DESC"]= "id de clave primaria del ul_grupo_pregunta";
      $defTabla["campos"]["contenido"]["DESC"]        = "Descripcion o titulo del Grupo de Preguntas";
      $defTabla["campos"]["orden"]["DESC"]            = "Orden entre los grupos";
      $defTabla["campos"]["id_calificable"]["DESC"]   = "calificable al que pertenece";
      $this->PaloEntidad($oDB, $oPlantillas, $defTabla);

      // Orden de los Grupos
      $sQuery = "SELECT id_grupo_pregunta,contenido,orden FROM ul_grupo_pregunta WHERE id_calificable='$id_calificable' ORDER BY orden";
      $result = $oDB->fetchTable($sQuery,TRUE);

      $i=0;
      $orden=array();
      $orden[]=array("value"=>"1",
            "tag"=>"Al Inicio",);
      $s_orden=1;
      $s_ordenUpdate=0;
      if(is_array($result) && count($result)>0){
         foreach($result as $i => $value){
            $s_orden = $value['orden']+1;
            // El orden del grupo seleccionado
            if(isset($_Get['id_grupo_pregunta']))
               if($value['id_grupo_pregunta']==$_Get['id_grupo_pregunta'])
                  $s_ordenUpdate=$value['orden'];

            $orden[]=array("value"=>$s_orden,
                  "tag"=>"Despues de: ".$value['contenido'],);
         }
      }
      $orden[]=array("value"=>$s_orden,
            "tag"=>"Al Final",);

      // Definicion de Formularios
      if (!$this->definirFormulario("INSERT", "CREAR_GRUPO_PREGUNTAS", array(
            "title"     =>  "Crear Grupo de Preguntas<br>\n".
               "<input type='hidden' name='editar_calificable' value='Crear Grupo'>".
               "<input type='hidden' name='id_materia_periodo_lectivo' value=$id_materia_periodo_lectivo>".
               "<a href=\"$sBaseURL\">&laquo;&nbsp;Regresar</a>&nbsp;",
            "submit"    =>  array( "name" => "submit_calificable", "value" => "Guardar", ),
            "fields"    =>  array(
               array(
                  "tag"       =>    "Orden:",
                  "name"      =>    "orden",
                  "type"      =>    "select",
                  "options"   =>    $orden,
                  "value"     =>    $s_orden,
                  "_field"    =>    "orden",
                  ),
               array(
                  "type"      =>    "texto",
                  "tag"       =>    "Titulo:",
                  "name"      =>    "contenido",
                  "_empty"    =>    FALSE,
                  "_field"    =>    "contenido",
                  'size'      =>    40,
                  ),
               array(
                  "type"      =>    "hidden",
                  "name"      =>    "id_calificable",
                  "value"     =>    $id_calificable,
                  "_field"    =>    "id_calificable",
                  ),
            ),
         ))) die ("ul_calificable_grupo_preguntas::ul_calificable_grupo_preguntas() - al definir formulario INSERT CREAR_GRUPO_PREGUNTAS - ".$this->_msMensajeError);

      $i=0;
      $ordenUpdate=array();
      if($s_ordenUpdate>0)
         if(is_array($result) && count($result)>0){
            foreach($result as $i => $value){
               if($value['orden']<$s_ordenUpdate)
                  $ordenUpdate[]=array("value"=>$value['orden'],
                        "tag"=>"Antes de: ".$value['contenido'],);
               elseif($value['orden']==$s_ordenUpdate)
                  $ordenUpdate[]=array("value"=>$value['orden'],
                        "tag"=>"Mantener en: ".$value['contenido'],);
               else
                  $ordenUpdate[]=array("value"=>$value['orden'],
                        "tag"=>"Despues de: ".$value['contenido'],);
            }
         }

      if (!$this->definirFormulario("UPDATE", "MODIFICAR_GRUPO_PREGUNTAS", array(
            "title"     =>  "Modificar Grupo Preguntas<br>\n".
               "<input type='hidden' name='action' value='modificar_calificable'>".
               "<input type='hidden' name='id_materia_periodo_lectivo' value=$id_materia_periodo_lectivo>".
               "<a href=\"$sBaseURL\">&laquo;&nbsp;Regresar</a>&nbsp;",
            "submit"    =>  array( "name" => "submit_calificable", "value" => "Guardar", ),
            "fields"    =>  array(
               array(
                  "type"      =>    "hidden",
                  "name"      =>    "orden_actual",
                  "value"     =>    $s_ordenUpdate,
                  ),
               array(
                  "tag"       =>    "Orden:",
                  "name"      =>    "orden",
                  "type"      =>    "select",
                  "options"   =>    $ordenUpdate,
                  "value"     =>    $s_ordenUpdate,
                  "_field"    =>    "orden",
                  ),
               array(
                  "type"      =>    "texto",
                  "tag"       =>    "Titulo:",
                  "name"      =>    "contenido",
                  "_empty"    =>    FALSE,
                  "_field"    =>    "contenido",
                  'size'      =>    40,
                  ),
               array(
                  "type"      =>    "hidden",
                  "name"      =>    "id_calificable",
                  "value"     =>    $id_calificable,
                  "_field"    =>    "id_calificable",
                  ),
            ),
         ))) die ("ul_calificable_grupo_pregunta::ul_calificable_grupo_pregunta() - al definir formulario UPDATE MODIFICAR_GRUPO_PREGUNTAS - ".$this->_msMensajeError);

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
      case "CREAR_GRUPO_PREGUNTAS":
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
     * @param string $sNombreFormulario Nombre del formulario que se est�manejando
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
         case "CREAR_GRUPO_PREGUNTAS":
            // actualizar el orden
            $sQuery = "UPDATE ul_grupo_pregunta SET orden=orden+1 WHERE orden>=".$formVars['orden']." AND id_calificable=".$formVars['id_calificable'];
            $result = $oDB->genQuery($sQuery);


            if($result===FALSE){
               $this->setMessage("No se pudieron actualizar los Grupos del Calificable.");
               return FALSE;
            }

            break;
         }
      }
      return $bValido;
  }



   /**
    * Verificar que el login no sea usado por otro usuario al actualizar alumno.
    *
    * @param string $sNombreFormulario Nombre del formulario que se est�manejando
    * @param array  $prevPK            Clave primaria previa del registro modificado
    * @param array  $formVars          Variables obtenidas del formulario, en arreglo asociativo
    *
    * @return boolean TRUE si los par�etros parecen v�idos hasta ahora, FALSE si no lo son.
    * La rutina puede asignar $this->_msMensajeError a un texto explicativo del error.
    */
   function event_validarValoresFormularioUpdate($sNombreFormulario, $prevPK, $formVars)
   {       ///////Si el usuario tiene los permisos para ingresar se permite la accion, si no se deniega
      $oACL=getACL();
      $bValido=TRUE;

      switch ($sNombreFormulario) {
      case "MODIFICAR_GRUPO_PREGUNTAS":

            if (!$oACL->isUserAuthorized($_SESSION['session_user'], 'updat', 'calf_lista')){
               $this->setMessage("Usted no está autorizado para realizar esta acción");
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
   * @param string $sNombreFormulario Nombre del formulario que se est�manejando
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
         case "MODIFICAR_GRUPO_PREGUNTAS":
            $orden = $formVars['orden'];
            $orden_actual = $formVars['orden_actual'];
            $id_calificable = $formVars['id_calificable'];

            if($orden<=$orden_actual){
               $sQuery = "UPDATE ul_grupo_pregunta SET orden=orden+1 WHERE orden BETWEEN ".$orden." AND ".($orden_actual-1)." AND id_calificable=$id_calificable";
            }elseif($orden>$orden_actual){
               $sQuery = "UPDATE ul_grupo_pregunta SET orden=orden-1 WHERE orden BETWEEN ".($orden_actual+1)." AND ".$orden." AND id_calificable=$id_calificable";
            }


            $result = $oDB->genQuery($sQuery);

            if($result===FALSE){
               $this->setMessage("No se pudieron actualizar los Grupos del Calificable.");
               return FALSE;
            }
            break;
         }
      }
      return $bExito;
   }



}

?>
