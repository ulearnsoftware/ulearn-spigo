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
// $Id: ul_topico.class.php,v 1.1.1.1 2006/03/03 21:59:09 ainiguez Exp $

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

class ul_topico extends PaloEntidad
{
   function ul_topico(&$oDB, &$oPlantillas,$id_materia_periodo_lectivo,$id_foro,$id_topico='')
   {
      $oACL=getACL();

      $defTabla = PaloEntidad::describirTabla($oDB, "ul_topico");
      $defTabla["campos"]["id_topico"]["DESC"]      = "id de clave primaria del foro";
      $defTabla["campos"]["titulo"]["DESC"]       = "Título del foro";
      $defTabla["campos"]["contenido"]["DESC"]    = "Contenido del Foro";
      $defTabla["campos"]["fecha_envio"]["DESC"]  = "Fecha de ultimo envio";
      $defTabla["campos"]["id_ultimo_envio"]["DESC"]        = "id del ultimo envio";
      $defTabla["campos"]["autor"]["DESC"]        = "login del autor";
      $defTabla["campos"]["n_respuestas"]["DESC"]        = "número de respuestas";
      $defTabla["campos"]["fecha_creacion"]["DESC"]        = "fecha de creacion";
      $defTabla["campos"]["fecha_cierre"]["DESC"]        = "fecha cierre";
      $defTabla["campos"]["id_foro"]["DESC"]        = "id_foro";
      $this->PaloEntidad($oDB, $oPlantillas, $defTabla);

      $arr_estatus = array(
                        array("value"  => "A", "tag"    => "Activo"),
                        array("value"  => "I", "tag" => "Inactivo"),
                     );


      $cierre=recoger_valor("cierre",$_GET,$_POST);
      $opcion="";
      if(isset($_POST['modificar_topico'])){
               ////Buscar si la fecha de cierre es distinto de NULL
            if($id_topico>0){
               $sQuery="SELECT * FROM ul_topico WHERE id_topico=$id_topico";
               $result=$oDB->getFirstRowQuery($sQuery,true);
                  if(is_array($result) && count($result)>0){
                     $fecha_cierre=$result['fecha_cierre'];
                        if($fecha_cierre!=NULL && $cierre==NULL)
                           $cierre=1;
                  }
            }
      }

         if($cierre){
            $opcion="checked";
            $arr_fecha=array(
                        "tag"       =>    "Fecha Cierre:",
                        "name"      =>    "fecha_cierre",
                        "_field"    =>    "fecha_cierre",
                        "type"      =>    "datetime",
                        "_empty"    =>    true,
                        );
         }
         else
            $arr_fecha=array("type"    =>    "hidden",
                             "name"    =>    "fecha",
                             "value"   =>    " ",
                           );

         if (!$this->definirFormulario("INSERT", "CREAR_TOPICO",
         array(
            "title"     =>  "Crear Tópico<br>\n".
               "<input type='hidden' name='action' value='crear_topico'>".
               "<input type='hidden' name='id_foro' value=$id_foro>".
               "<input type='hidden' name='id_materia_periodo_lectivo' value=$id_materia_periodo_lectivo>".
               "<a href=\"?menu1op=submenu_colaboracion&submenuop=col_foros&id_materia_periodo_lectivo=$id_materia_periodo_lectivo&id_foro=$id_foro&action=mostrar_topicos\">&laquo;&nbsp;Regresar</a>&nbsp;",
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
                  "tag"       =>    "Establecer Cierre:",
                  "name"      =>    "asignar_cierre",
                  "type"      =>    "html",
                  "value"      =>    "<input type='checkbox' name='cierre' value=1 onChange='submit()' $opcion>",
                  ),
               $arr_fecha,
               array(
                  "type"      =>    "hidden",
                  "name"      =>    "id_foro",
                  "value"     =>    $id_foro,
                  "_field"    =>    "id_foro",
                  ),
            ),
         ))) die ("ul_topico::ul_topico() - al definir formulario INSERT CREAR_TOPICO - ".$this->_msMensajeError);




         if (!$this->definirFormulario("UPDATE", "MODIFICAR_TOPICO",
         array(
            "title"     =>  "Modificar Tópico<br>\n".
               "<input type='hidden' name='action' value='modificar_topico'>".
               "<input type='hidden' name='id_foro' value=$id_foro>".
               "<input type='hidden' name='id_topico' value=$id_topico>".
               "<input type='hidden' name='id_materia_periodo_lectivo' value=$id_materia_periodo_lectivo>".
               "<a href=\"?menu1op=submenu_colaboracion&submenuop=col_foros&id_materia_periodo_lectivo=$id_materia_periodo_lectivo&id_foro=$id_foro&action=mostrar_topicos\">&laquo;&nbsp;Regresar</a>&nbsp;",
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
                  "tag"       =>    "Establecer Cierre:",
                  "name"      =>    "asignar_cierre",
                  "type"      =>    "html",
                  "value"      =>    "<input type='checkbox' name='cierre' value=1 onChange='submit()' $opcion>",
                  ),
               $arr_fecha,
               array(
                  "type"      =>    "hidden",
                  "name"      =>    "id_foro",
                  "value"     =>    $id_foro,
                  "_field"    =>    "id_foro",
                  ),
            ),
         ))) die ("ul_topico::ul_topico() - al definir formulario UPDATE MODIFICAR_TOPICO - ".$this->_msMensajeError);


     }

function event_traducirFormularioBaseInsert($sNombreFormulario, $formVars){
// Servirse de la validacin de la clase PaloEntidad
$oDB=$this->getDB();
$dbVars = parent::event_traducirFormularioBaseInsert($sNombreFormulario, $formVars);
   if (is_array($dbVars)){
      switch ($sNombreFormulario) {
         case "CREAR_TOPICO":
               $dbVars["fecha_creacion"] = date("Y-m-d H:i:s",time());
               $dbVars["autor"]=obtener_nombre_usuario($oDB,$_SESSION['session_user']);
                  if(isset($_POST['cierre']))
                     $dbVars['fecha_cierre']=$formVars['fecha_cierre'];
               break;
         default:
               break;
      }
	}
        return $dbVars;
}


function event_traducirFormularioBaseUpdate($sNombreFormulario, $prevPK, $formVars){

// Servirse de la validacin de la clase PaloEntidad
$oDB=$this->getDB();
$dbVars = parent::event_traducirFormularioBaseUpdate($sNombreFormulario, $prevPK,$formVars);
   if (is_array($dbVars)){
      switch ($sNombreFormulario) {
         case "MODIFICAR_TOPICO":
                  if(isset($_POST['cierre']))
                     $dbVars['fecha_cierre']=$formVars['fecha_cierre'];
                  else
                     $dbVars['fecha_cierre']=NULL;
               break;
         default:
               break;
      }
	}
        return $dbVars;


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
      case "CREAR_TOPICO":

         if (!$oACL->isUserAuthorized($_SESSION['session_user'], 'creat', 'col_foros')){
            $this->setMessage("Usted no está autorizado para realizar esta acción");
            return FALSE;
         }
         $formVars['contenido'] = strip_tags($formVars['contenido']);
            if(isset($_POST['cierre'])){
               $fecha_cierre=$formVars['fecha_cierre'];
               $f_cierre=strtotime($fecha_cierre);
               $now=time();
               $formVars['fecha_cierre']=date("Y-m-d H:i:s",$f_cierre);
                  if($f_cierre<=$now){
                     $this->setMessage("La fecha de cierre no puede ser menor a la actual");
                     return FALSE;
                  }

            }
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
      case "MODIFICAR_TOPICO":

            if (!$oACL->isUserAuthorized($_SESSION['session_user'], 'updat', 'col_foros')){
               $this->setMessage("Usted no está autorizado para realizar esta acción");
               return FALSE;
            }
         $formVars['contenido'] = strip_tags($formVars['contenido']);

            if(isset($_POST['cierre'])){
               $fecha_cierre=$formVars['fecha_cierre'];

               $f_cierre=strtotime($fecha_cierre);
               $now=time();
               $formVars['fecha_cierre']=date("Y-m-d H:i:s",$f_cierre);

                  if($f_cierre<=$now){
                     $this->setMessage("La fecha de cierre no puede ser menor a la actual");
                     return FALSE;
                  }

            }

         break;
      }
      return $bValido;
   }








}

?>
