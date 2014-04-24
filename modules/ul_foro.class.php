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
// $Id: ul_foro.class.php,v 1.1.1.1 2006/03/03 21:59:09 ainiguez Exp $

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

class ul_foro extends PaloEntidad
{
   function ul_foro(&$oDB, &$oPlantillas,$id_materia_periodo_lectivo,$id_foro='',$estatus='')
   {
      $oACL=getACL();

      $defTabla = PaloEntidad::describirTabla($oDB, "ul_foro");
      $defTabla["campos"]["id_foro"]["DESC"]      = "id de clave primaria del foro";
      $defTabla["campos"]["titulo"]["DESC"]       = "Título del foro";
      $defTabla["campos"]["contenido"]["DESC"]    = "Contenido del Foro";
      $defTabla["campos"]["estatus"]["DESC"]      = "Estatus del Foroo";
      $defTabla["campos"]["autor"]["DESC"]        = "Autor";
      $defTabla["campos"]["id_materia_periodo_lectivo"]["DESC"]        = "id_materia_periodo_lectivo";
      $this->PaloEntidad($oDB, $oPlantillas, $defTabla);

      $arr_estatus = array(
                        array("value"  => "A", "tag"    => "Activo"),
                        array("value"  => "I", "tag" => "Inactivo"),
                     );

      if (!$this->definirFormulario("INSERT", "CREAR_FORO",
         array(
            "title"     =>  "Crear Foro<br>\n".
               "<input type='hidden' name='action' value='crear_foro'>".
               "<input type='hidden' name='id_materia_periodo_lectivo' value=$id_materia_periodo_lectivo>".
               "<a href=\"?menu1op=submenu_colaboracion&submenuop=col_foros&id_materia_periodo_lectivo=$id_materia_periodo_lectivo\">&laquo;&nbsp;Regresar</a>&nbsp;",
            "submit"    =>  array( "name" => "submit_foro", "value" => "Guardar", ),
            "fields"    =>  array(
               array(
                  "tag"       =>    "Título:",
                  "name"      =>    "titulo",
                  "_empty"    =>    FALSE,
                  "_field"    =>    "titulo",
                  "size"      =>    52,
                  ),
               array(
                  "tag"       =>    "Contenido:",
                  "name"      =>    "contenido",
                  "_field"    =>    "contenido",
                  '_empty'    =>    FALSE,
                  'cols'      =>    50,
                  'rows'      =>    10,
                  ),
               array(
                  "tag"       =>    "Estatus:",
                  "name"      =>    "estatus",
                  "_field"    =>    "estatus",
                  "type"      =>    "select",
                  "options"   =>    $arr_estatus,
                  ),
               array(
                  "type"      =>    "hidden",
                  "name"      =>    "id_materia_periodo_lectivo",
                  "value"     =>    $id_materia_periodo_lectivo,
                  "_field"    =>    "id_materia_periodo_lectivo",
                  ),
            ),
         ))) die ("ul_foro::ul_foro() - al definir formulario INSERT CREAR_FORO - ".$this->_msMensajeError);

      if (!$this->definirFormulario("UPDATE", "MODIFICAR_FORO",
         array(
            "title"     =>  "Modificar Foro<br>\n".
               "<input type='hidden' name='action' value='modificar_foro'>".
               "<input type='hidden' name='id_foro' value='$id_foro'>".
               "<input type='hidden' name='id_materia_periodo_lectivo' value=$id_materia_periodo_lectivo>".
               "<a href=\"?menu1op=submenu_colaboracion&submenuop=col_foros&id_materia_periodo_lectivo=$id_materia_periodo_lectivo\">&laquo;&nbsp;Regresar</a>&nbsp;",
            "submit"    =>  array( "name" => "submit_foro", "value" => "Guardar", ),
            "fields"    =>  array(
               array(
                  "tag"       =>    "Título:",
                  "name"      =>    "titulo",
                  "_empty"    =>    FALSE,
                  "_field"    =>    "titulo",
                  "size"      =>    52,
                  ),
               array(
                  "tag"       =>    "Contenido:",
                  "name"      =>    "contenido",
                  "_field"    =>    "contenido",
                  '_empty'    =>    FALSE,
                  'cols'      =>    50,
                  'rows'      =>    10,
                  ),
               array(
                  "tag"       =>    "Estatus:",
                  "name"      =>    "estatus",
                  "_field"    =>    "estatus",
                  "type"      =>    "select",
                  "options"   =>    $arr_estatus,
                  ),
               array(
                  "type"      =>    "hidden",
                  "name"      =>    "id_materia_periodo_lectivo",
                  "value"     =>    $id_materia_periodo_lectivo,
                  "_field"    =>    "id_materia_periodo_lectivo",
                  ),
            ),
         ))) die ("ul_foro::ul_foro() - al definir formulario UPDATE MODIFICAR_FORO - ".$this->_msMensajeError);


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
   function event_validarValoresFormularioInsert($sNombreFormulario, &$formVars){
      $oACL=getACL();
      $bValido=TRUE;

      switch ($sNombreFormulario) {
      case "CREAR_FORO":
         if (!$oACL->isUserAuthorized($_SESSION['session_user'], 'admin', 'col_foros')){
            $this->setMessage("Usted no está autorizado para realizar esta acción");
            return FALSE;
         }
         $formVars['contenido'] = strip_tags($formVars['contenido']);
         break;
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
   function event_validarValoresFormularioUpdate($sNombreFormulario, $prevPK, &$formVars)
   {       ///////Si el usuario tiene los permisos para ingresar se permite la accion, si no se deniega
      $oACL=getACL();
      $bValido=TRUE;

      switch ($sNombreFormulario) {
      case "MODIFICAR_FORO":

            if (!$oACL->isUserAuthorized($_SESSION['session_user'], 'admin', 'col_foros')){
               $this->setMessage("Usted no está autorizado para realizar esta acción");
               return FALSE;
            }
            $formVars['contenido'] = strip_tags($formVars['contenido']);

         break;
      }
      return $bValido;
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



}

?>
