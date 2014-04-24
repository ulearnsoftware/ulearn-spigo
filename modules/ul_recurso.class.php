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
// $Id: ul_recurso.class.php,v 1.1.1.1 2006/03/03 21:59:09 ainiguez Exp $

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

class ul_recurso extends PaloEntidad
{
   function ul_recurso(&$oDB, &$oPlantillas,$id_parent=NULL,$id_materia_periodo_lectivo,$id_recurso='')
   {
      $oACL=getACL();

      $defTabla = PaloEntidad::describirTabla($oDB, "ul_recurso");
      $defTabla["campos"]["id_recurso"]["DESC"]        = "id de clave primaria del recurso";
      $defTabla["campos"]["URL"]["DESC"]               = "Ruta donde se encuentra el archivo";
      $defTabla["campos"]["comentario"]["DESC"]        = "Comentario sobre el recurso";
      $defTabla["campos"]["tipo"]["DESC"]              = "tipo del recurso";
      $defTabla["campos"]["id_parent"]["DESC"]         = "id del recurso que contiene a este recurso";
      $defTabla["campos"]["id_materia_periodo_lectivo"]["DESC"]  = "id de la materia_periodo_lectivo a la que pertenece el recurso";
      $defTabla["campos"]["id_materia"]["DESC"]  = "id de la materia a la que pertenece el recurso";
      $this->PaloEntidad($oDB, $oPlantillas, $defTabla);

      if($id_parent!=NULL)
         $ruta_directorio=urldecode($this->obtener_ruta($id_parent));
      else
         $ruta_directorio="/";

      // si está en el directorio raiz, puede elegir escribir en el
      // directorio general o en el directorio de la materia_periodo_lectivo

      // permiso para escribir en directorio materia general

      if($id_parent==NULL){ // directorio raiz
         if ($oACL->isUserAuthorized($_SESSION['session_user'], 'admin', 'rec_lista') &&
            $oACL->isUserAuthorized($_SESSION['session_user'], 'creat', 'rec_lista')){
               $arr_opcion = array(
               "type"      =>  "checkbox",
               "tag"       =>  "Materia General:",
               "name"      =>  "contenido_materia",
               "value"     =>  TRUE ); // tiene ambos permisos
         }
         else
            $arr_opcion = array(
            "type"      =>  "hidden",
            "name"      =>  "sin_seleccion", // dependerá del permiso
            "value"     =>  1, );
      }
      else{
         $arr_opcion = array(
         "type"      =>  "hidden",
         "name"      =>  "sin_seleccion",
         "value"     =>  1, ); // dependerá del permiso
      }


      // Construir todos los formularios requeridos para sa_alumno
      if (!$this->definirFormulario("INSERT", "SUBIR_ARCHIVO",
         array(
            "title"     =>  "Subir Archivo<br />\n".
               "<input type='hidden' name='MAX_FILE_SIZE' value='2000000'>".
               "<input type='hidden' name='action' value='subir_archivo'>".
               "<input type='hidden' name='id_materia_periodo_lectivo' value=$id_materia_periodo_lectivo>".
               "<input type='hidden' name='id_parent' value=$id_parent>".
               "<a href=\"?menu1op=submenu_recursos&submenuop=rec_lista&id_materia_periodo_lectivo=$id_materia_periodo_lectivo&id_parent=$id_parent\">&laquo;&nbsp;Regresar</a>&nbsp;",
            "submit"    =>  array( "name" => "submit_recurso", "value" => "Subir Archivo", ),
            "options" => array( "enctype" => "multipart/form-data", ),
            "fields"    =>  array(
               array(
                  "tag"       =>  "Ubicación Servidor:",
                  "type"      =>  "html",
                  "value"     =>  $ruta_directorio, ),
               array(
                  "type"      =>  "hidden",
                  "name"      =>  "id_parent",
                  "value"     =>  $id_parent,
                  "_field"    =>  "id_parent",
                  "_empty"    =>  TRUE,),
               array(
                  "type"      =>  "hidden",
                  "name"      =>  "id_materia_periodo_lectivo",
                  "value"     =>  $id_materia_periodo_lectivo,
                  "_field"    =>  "id_materia_periodo_lectivo", ),
               $arr_opcion,
               array(
                  "tag"       =>  "Ubicación Local:",
                  "title"     =>  "Seleccione la ubicación",
                  "_field"    =>  "URL",
                  "type"      =>  "html",
                  "value"     =>  "<input type='file' name='userfile' size='50'>", ),
               array(
                  "tag"       =>  "Comentario:",
                  "title"     =>  "Ingrese un comentario",
                  "_field"    =>  "comentario",
                  "_empty"    =>  TRUE,
                  "size"      =>  40,
                  "maxlength" =>  40, ),
            ),
         )
      )) die ("ul_recurso::ul_recurso() - al definir formulario INSERT SUBIR_ARCHIVO - ".$this->_msMensajeError);

      if (!$this->definirFormulario("INSERT", "CREAR_DIRECTORIO",
         array(
            "title"     =>  "Crear Directorio<br />\n".
               "<input type='hidden' name='action' value='crear_directorio'>".
               "<input type='hidden' name='id_materia_periodo_lectivo' value=$id_materia_periodo_lectivo>".
               "<input type='hidden' name='id_parent' value=$id_parent>".
               "<a href=\"?menu1op=submenu_recursos&submenuop=rec_lista&id_materia_periodo_lectivo=$id_materia_periodo_lectivo&id_parent=$id_parent\">&laquo;&nbsp;Regresar</a>&nbsp;",
            "submit"    =>  array( "name" => "submit_recurso", "value" => "Aceptar", ),
            "fields"    =>  array(
               array(
                  "tag"       =>  "Ubicación Servidor:",
                  "type"      =>  "html",
                  "value"     =>  $ruta_directorio, ),
               $arr_opcion,
               array(
                  "type"      =>  "hidden",
                  "name"      =>  "tipo",
                  "value"     =>  "D",
                  "_field"    =>  "tipo",
                  ),
               array(
                  "type"      =>  "hidden",
                  "name"      =>  "id_parent",
                  "value"     =>  $id_parent,
                  "_field"    =>  "id_parent",
                  '_empty'    =>  TRUE,
                  ),
               array(
                  "type"      =>  "hidden",
                  "name"      =>  "id_materia_periodo_lectivo",
                  "value"     =>  $id_materia_periodo_lectivo,
                  "_field"    =>  "id_materia_periodo_lectivo", ),
               array(
                  "tag"       =>  "Nombre:",
                  "title"     =>  "Ingrese un nombre de directorio",
                  "name"      =>  "nombre",
                  "type"      =>  "text",
                  "size"      =>   40,
                  '_field'    =>  'URL',
                  ),
               array(
                  "tag"       =>  "Comentario:",
                  "title"     =>  "Ingrese un comentario",
                  "_field"    =>  "comentario",
                  "_empty"    =>  TRUE,
                  "size"      =>  40,
                  "maxlength" =>  40, ),
            ),
         ))) die ("ul_recurso::ul_recurso() - al definir formulario INSERT SUBIR_ARCHIVO - ".$this->_msMensajeError);



      /////Si se recibe un id_recurso se guarda la variable $hidden,
      $ruta_archivo=$nombre_archivo="";

      if($id_recurso>0){
         $html_recurso="<input type='hidden' name='in_recurso' value=$id_recurso>";
         $sQuery="select URL from ul_recurso where id_recurso=".paloDB::DBCAMPO($id_recurso);
         $result=$oDB->getFirstRowQuery($sQuery);
         if(is_array($result) && count($result)>0)
            $nombre_archivo=urldecode($result[0]);
         $ruta_archivo=$ruta_directorio.$nombre_archivo;
      }
      else
         $html_recurso="";

      if (!$this->definirFormulario("UPDATE", "REEMPLAZAR_ARCHIVO",
         array(
            "title"     =>  "Reemplazar Archivo<br />\n".
               "<input type='hidden' name='MAX_FILE_SIZE' value='2000000'>".
               "<input type='hidden' name='id_materia_periodo_lectivo' value=$id_materia_periodo_lectivo>".
               "<input type='hidden' name='id_parent' value=$id_parent>".
               "<input type='submit' name='regresar' value='Regresar'>".
               $html_recurso,
            "submit"    =>  array( "name" => "submit_recurso", "value" => "Reemplazar Archivo", ),
            "options" => array( "enctype" => "multipart/form-data", ),
            "fields"    =>  array(
               array(
                  "tag"       =>  "Ubicación Servidor:",
                  "type"      =>  "html",
                  "value"     =>  $ruta_archivo, ),
               array(
                  "type"      =>  "hidden",
                  "name"      =>  "id_parent",
                  "value"     =>  $id_parent, ),
               array(
                  "type"      =>  "hidden",
                  "name"      =>  "id_materia_periodo_lectivo",
                  "value"     =>  $id_materia_periodo_lectivo, ),
               array(
                  "tag"       =>  "Ubicación Local:",
                  "title"     =>  "Seleccione la ubicación",
                  "_field"    =>  "URL",
                  "type"      =>  "html",
                  "value"     =>  "<input type='file' name='ubicacion' size='50'>", ),
               array(
                  "tag"       =>  "Comentario:",
                  "title"     =>  "Ingrese un comentario",
                  "_field"    =>  "comentario",
                  "_empty"    =>  TRUE,
                  "size"      =>  40, ),
            ),
        ))) die ("ul_recurso::ul_recurso() - al definir formulario UPDATE REEMPLAZAR_ARCHIVO - ".$this->_msMensajeError);

      if (!$this->definirFormulario("UPDATE", "RENOMBRAR_ARCHIVO",
         array(
            "title"     =>  "Renombrar Archivo<br />\n".
               "<input type='hidden' name='action' value='renombrar'>".
               "<input type='hidden' name='id_materia_periodo_lectivo' value=$id_materia_periodo_lectivo>".
               "<input type='hidden' name='id_parent' value=$id_parent>".
               "<a href=\"?menu1op=submenu_recursos&submenuop=rec_lista&id_materia_periodo_lectivo=$id_materia_periodo_lectivo&id_parent=$id_parent\">&laquo;&nbsp;Regresar</a>&nbsp;".
               $html_recurso,
            "submit"    =>  array( "name" => "submit_recurso", "value" => "Renombrar Archivo", ),
            "fields"    =>  array(
               array(
                  "tag"       =>  "Ubicación Servidor:",
                  "type"      =>  "html",
                  "value"     =>  $ruta_archivo, ),
               array(
                  "type"      =>  "hidden",
                  "name"      =>  "id_parent",
                  "value"     =>  $id_parent,
                  "_field"    =>  "id_parent",
                  '_empty'    =>  TRUE, ),
               array(
                  "type"      =>  "hidden",
                  "name"      =>  "id_materia_periodo_lectivo",
                  "value"     =>  $id_materia_periodo_lectivo, ),
               array(
                  "tag"       =>  "Nombre Actual:",
                  "type"      =>  "label",
                  "name"      =>  "nombre_archivo",
                  "value"     =>  $nombre_archivo, ),
               array(
                  "tag"       =>  "Nuevo Nombre:",
                  "title"     =>  "Ingrese el nuevo nombre",
                  "name"      =>  "nuevo_nombre",
                  "type"      =>  "text",
                  "_empty"    =>  FALSE,
                  "size"      =>  30, ),
            ),
         ))) die ("ul_recurso::ul_recurso() - al definir formulario UPDATE RENOMBRAR_ARCHIVO".$this->_msMensajeError);
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
      $id_parent = $formVars['id_parent'];

      // Los admin solo pueden subir archivos y crear directorios
      // en el directorio general de la materia

      // Los creat solo pueden subir archivos y crear directorios
      // en el directorio de la materia_periodo_lectivo que le corresponde

      switch ($sNombreFormulario) {
      case "CREAR_DIRECTORIO":
         // en directorio raiz
         if($id_parent==NULL){
            // materia general
            if(isset($formVars['contenido_materia'])) // tiene ambos permisos
               return TRUE;
            else{
               if (!$oACL->isUserAuthorized($_SESSION['session_user'], 'creat', 'rec_lista')){
                  $this->_msMensajeError.="Usted no está autorizado para realizar esta acción";
                  return FALSE;
               }
            }
         }
         else{ // subdirectorio
            if($this->obtener_id_materia_periodo_lectivo($id_parent)==NULL){ // Materia General
               if (!$oACL->isUserAuthorized($_SESSION['session_user'], 'admin', 'rec_lista')){
                  $this->_msMensajeError.="Usted no está autorizado para realizar esta acción";
                  return FALSE;
               }
            }
            else
               if (!$oACL->isUserAuthorized($_SESSION['session_user'], 'creat', 'rec_lista')){
                  $this->_msMensajeError.="Usted no está autorizado para realizar esta acción";
                  return FALSE;
               }
         }
         break;
      case "SUBIR_ARCHIVO":
         // en directorio raiz
         if($id_parent==NULL){
            // materia general
            if(isset($formVars['contenido_materia'])) // tiene ambos permisos
               return TRUE;
            else{
               if (!$oACL->isUserAuthorized($_SESSION['session_user'], 'creat', 'rec_lista')){
                  $this->_msMensajeError.="Usted no está autorizado para realizar esta acción";
                  return FALSE;
               }
            }
         }
         else{ // subdirectorio
            if($this->obtener_id_materia_periodo_lectivo($id_parent)==NULL){ // Materia General
               if (!$oACL->isUserAuthorized($_SESSION['session_user'], 'admin', 'rec_lista')){
                  $this->_msMensajeError.="Usted no está autorizado para realizar esta acción";
                  return FALSE;
               }
            }
            else
               if (!$oACL->isUserAuthorized($_SESSION['session_user'], 'creat', 'rec_lista')){
                  $this->_msMensajeError.="Usted no está autorizado para realizar esta acción";
                  return FALSE;
               }
         }
         break;
      }
      return TRUE;
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
      $bValido = parent::event_precondicionInsert($sNombreFormulario, $dbVars, $formVars);
      if ($bValido){
         $dbVars['id_materia']=$this->obtener_id_materia($formVars['id_materia_periodo_lectivo']);

         switch ($sNombreFormulario) {
         case "SUBIR_ARCHIVO":
            $dbVars['URL'] = $_FILES['userfile']['name'];

            $bValido = $this->realizar_upload($formVars);

            break;

         case "CREAR_DIRECTORIO":
            //$this->actualizar_DB(); // Lee los recursos almacenados físicamente
            //$this->sincronizar_DB(); // Elimina los recursos de la BD que no se encuentran físicamente
            //return FALSE;

            $bValido=$this->crear_directorio($formVars);

            if($bValido)
               $dbVars['URL']=urlencode($formVars['nombre']);
            break;
         }

         if($bValido){
            $id_parent = $formVars['id_parent'];
            if($id_parent == NULL){ // raiz
               if(isset($formVars['contenido_materia']))
                  if($formVars['contenido_materia']==1)
                  $dbVars['id_materia_periodo_lectivo'] = NULL;
            }elseif($this->obtener_id_materia_periodo_lectivo($id_parent)==NULL)
               $dbVars['id_materia_periodo_lectivo'] = NULL;
         }
      }
      return $bValido;
  }


   /**
    * Luego de insertar el nuevo alumno, se debe de agregar el registro al historial
    * del alumno
    */
   function event_postcondicionInsert($sNombreFormulario, $dbVars, $formVars)
   {
       switch ($sNombreFormulario) {
       case "SUBIR_ARCHIVO":
       }
   }

   /**
    * Procedimiento para deshacer los efectos colaterales introducidos en el sistema
    * por la rutina event_precondicionInsert(). En el caso de AlumnoSA, se debe
    * deshacer la creacin del usuario y de la membres� al grupo alumno.
    *
    * @param string $sNombreFormulario Formulario que se maneja
    * @param array  $dbVars Variables que se intentaron ingresar a la tabla
    * @param array  $formVars Variables del formulario original de ingreso
    *
    * @return void
    */
   function event_deshacerPrecondicionInsert($sNombreFormulario, $dbVars, $formVars)
   {
       switch ($sNombreFormulario) {
       case "SUBIR_ARCHIVO":
       }
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
      $bExito = TRUE;
      $id_parent = $formVars['id_parent'];

      switch ($sNombreFormulario) {
      case "RENOMBRAR_ARCHIVO":
         $id_recurso = $prevPK['id_recurso'];
         $oDB = $this->_db;

         $sQuery = "SELECT id_materia_periodo_lectivo FROM ul_recurso WHERE id_recurso=$id_recurso";
         $result = $oDB->getFirstRowQuery($sQuery);
         $id_materia_periodo_lectivo="";
         if(is_array($result) && count($result)>0){
            $id_materia_periodo_lectivo=$result[0];
         }
         else // no existe el registro
            return FALSE;

         // materia general (solo admin)
         if($id_materia_periodo_lectivo==NULL){
            if (!$oACL->isUserAuthorized($_SESSION['session_user'], 'admin', 'rec_lista')){
               $this->_msMensajeError.="Usted no está autorizado para realizar esta acción";
               return FALSE;
            }
         }
         else // materia_periodo_lectivo (solo creat)
            if (!$oACL->isUserAuthorized($_SESSION['session_user'], 'creat', 'rec_lista')){
               $this->_msMensajeError.="Usted no está autorizado para realizar esta acción";
               return FALSE;
            }
         break;
       }
       return TRUE;
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
   function event_precondicionUpdate($sNombreFormulario, $prevPK, &$dbVars, $formVars)
   {
      $bExito = parent::event_precondicionUpdate($sNombreFormulario, $prevPK, $dbVars, $formVars);
      if ($bExito)
         switch ($sNombreFormulario){
         case "RENOMBRAR_ARCHIVO":
            ////llamara funcion que cambia nombre en el disco duro
            $bExito = $this->renombrar_archivo($formVars);
            $dbVars['URL']=urlencode($formVars['nuevo_nombre']);

            break;
         case "REEMPLAZAR_ARCHIVO":
      }
      return $bExito;
   }

   /**
    * Procedimiento para completar cambios en la base de datos luego de modificar la tupla
    * de la base de datos.
    *
    * @param string $sNombreFormulario Nombre del formulario que se est�manejando
    * @param array  $prevPK            Clave primaria previa del registro modificado
    * @param array  $dbVars            Variables a insertar en la base de datos, con comillas
    * @param array  $formVars          Variables del formulario de insercin
    *
    * @return void
    */
   function event_postcondicionUpdate($sNombreFormulario, $prevPK, $dbVars, $formVars)
   {
       switch ($sNombreFormulario) {
       case "REEMPLAZAR_ARCHIVO":
       }
   }


   /**
    * Procedimiento para deshacer los efectos laterales realizados por event_precondicionUpdate().
    * Para AlumnoSA, se deshace el cambio de login para el alumno.
    *
    * @param string $sNombreFormulario Nombre del formulario que se est�manejando
    * @param array  $prevPK            Clave primaria previa del registro modificado
    * @param array  $dbVars            Variables a insertar en la base de datos, con comillas
    * @param array  $formVars          Variables del formulario de insercin
    *
    * @return void
    */
   function event_deshacerPrecondicionUpdate($sNombreFormulario, $prevPK, $dbVars, $formVars)
   {
      switch ($sNombreFormulario) {
         case "SUBIR_ARCHIVO":

      }
   }


   // obtiene la ruta desde la raiz hasta el directorio padre
   function obtener_ruta($id_parent){
      $db=$this->getDB();
      $ruta_directorio=$this->obtener_parent($db,$id_parent);
      return "/".$ruta_directorio;
   }


   // permite obtiener la ruta desde la raiz hasta el directorio padre
   function obtener_parent($oDB, $id_parent){
      $sQuery="SELECT id_parent,URL,id_recurso from ul_recurso where id_recurso=$id_parent and tipo='D'";
      $result=$oDB->getFirstRowQuery($sQuery);
      $str="";
      if(is_array($result) && count($result)>0){
         $str.=$this->obtener_parent($oDB,$result[0]);
         $str.=$result[1]."/";
      }
      return $str;
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

   // obtiene el id de la materia_periodo_lectivo
   // utiliza el $id_recurso o el $id_parent del recurso
   function obtener_id_materia_periodo_lectivo($id_recurso){
      $oDB=$this->getDB();
      if($id_recurso == NULL)
         return FALSE;
      $sQuery="SELECT id_materia_periodo_lectivo from ul_recurso where id_recurso=$id_recurso";
      $result=$oDB->getFirstRowQuery($sQuery);
      $str="";
      if(is_array($result) && count($result)>0){
         $str=$result[0];
      }
      return $str;
   }

   /**
   * Procedimiento para realizar operaciones previas a la insercion de la tupla en la base
   * de datos. Esta implementacion crea los directorios utilizados para organizar los
   * recursos utilizados en la materia.
   *
   * @param array  $formVars          Variables del formulario de insercin
   *
   * @return boolean TRUE si se creó o ya existe el directorio, FALSE si no.
   */
   function crear_directorio($formVars){
      $dir_ruta=$this->crear_directorio_raiz($formVars);

      if($dir_ruta===FALSE){
         $this->_msMensajeError.="No se creó el directorio<br />";
         return FALSE;
      }

      // crea el directorio deseado
      $dir = $dir_ruta.$formVars['nombre'];

      if(!is_dir($dir)){
         $oldmask = umask(0);
         if(!@mkdir(urldecode($dir), 0764)){
            $this->_msMensajeError.="No tiene los permisos necesarios para ejecutar esta operación<br />";
            umask($oldmask);
            return FALSE;
         }
         umask($oldmask);
      }
      /*
      else{
         $this->_msMensajeError.="El archivo ya existe en el directorio.";
         //return FALSE;
      }
      */
      return TRUE;
   }


   /**
   * Este procedimiento crea los directorios necesarios para organizar los
   * recursos utilizados en la materia.
   *
   * @param array  $formVars          Variables del formulario de insercin
   *
   * @return boolean TRUE si se creó o ya existe el directorio, FALSE si no.
   */
   function crear_directorio_raiz($formVars){
      global $config;
      $dir_base = $config->dir_base;
      $prefix_mpl=$config->prefix_mpl;
                $prefix_mat=$config->prefix_mat;

      $ruta = $this->obtener_ruta($formVars['id_parent']);
      $id_materia_periodo_lectivo = $formVars['id_materia_periodo_lectivo'];
      $id_materia = $this->obtener_id_materia($id_materia_periodo_lectivo);

      // validación del nombre del directorio
      $dirname="";
      if(isset($formVars['nombre']))
         $dirname=$formVars['nombre'];
      if(isset($formVars['URL']))
         $dirname=$formVars['URL'];

      if(!(strpos($dirname,".")===FALSE) || !(strpos($dirname,"/")===FALSE)){
            $this->_msMensajeError.="No es un nombre de Directorio válido<br />";
            return FALSE;
      }

      // Verificacion del permiso de escritura del directorio base
      if(!is_writable($dir_base)){
         echo $dir_materia;
         $this->_msMensajeError.="No tiene permiso de escritura del directorio base<br />";
         return FALSE;
      }

      // path de la materia
      $dir_materia=$dir_base."/".$prefix_mat.$id_materia;

      // Creacion del directorio de la materia
      if(!is_dir($dir_materia)){
         $oldmask = umask(0);
         if(!@mkdir($dir_materia, 0764)){
            $this->_msMensajeError.="No pudo crear el directorio de la materia<br />";
            umask($oldmask);
            return FALSE;
         }
         umask($oldmask);
      }

      // path de la materia_periodo_lectivo
      $id_parent = $formVars['id_parent'];
      $dir_materia_periodo_lectivo = $dir_materia;
      if($id_parent == NULL){ // raiz
         if(!isset($formVars['contenido_materia'])){
            $dir_materia_periodo_lectivo .= "/".$prefix_mpl.$formVars['id_materia_periodo_lectivo'];
         }
         else{
            if($formVars['contenido_materia']==0){
               $dir_materia_periodo_lectivo .= "/".$prefix_mpl.$formVars['id_materia_periodo_lectivo'];
            }
         }
      }
      else{ // directorio
         // es dependiente del directorio (recurso)
         $id_materia_periodo_lectivo = $this->obtener_id_materia_periodo_lectivo($id_parent);
         if($id_materia_periodo_lectivo)
            $dir_materia_periodo_lectivo .= "/".$prefix_mpl.$id_materia_periodo_lectivo;
      }

      // Verificacion del permiso de escritura del directorio de la materia
      if(!is_writable($dir_materia)){
         $this->_msMensajeError.="No tiene permiso de escritura para el directorio materia<br />";
         return FALSE;
      }

      // Creacion del directorio para la materia_periodo_lectivo
      if(!is_dir($dir_materia_periodo_lectivo)){
         $oldmask = umask(0);
         if(!@mkdir($dir_materia_periodo_lectivo, 0764)){
            $this->_msMensajeError.="No pudo crear el directorio de la materia_periodo_lectivo<br />";
            umask($oldmask);
            return FALSE;
         }
         umask($oldmask);
      }

      return $dir_materia_periodo_lectivo.$ruta;
   }


   /**
   * Procedimiento para realizar operaciones previas a la carga de archivos al sistema
   * y en la base de datos. Esta implementacion crea los directorios utilizados para
   * organizar los recursos utilizados en la materia.
   *
   * @param array  $formVars          Variables del formulario de insercin
   *
   * @return boolean TRUE si se creó el archivo, FALSE si no.
   */
   function realizar_upload($formVars){

      ///Se debe verificar si existe el directorio antes de continuar
      $dir_ruta=$this->crear_directorio_raiz($formVars);

      if($dir_ruta===FALSE){
         $this->_msMensajeError.="No se realizó la subida del archivo<br />";
         return FALSE;
      }
      $dir = $dir_ruta;

      ///Se debe verificar si existe el archivo subio correctamente

      $userfile = $_FILES['userfile']['tmp_name'];
      $userfile_name = $_FILES['userfile']['name'];
      $userfile_size = $_FILES['userfile']['size'];
      $userfile_type = $_FILES['userfile']['type'];

      $error = $_FILES['userfile']['error'];

      switch($error){
      case 1:
         $this->_msMensajeError.="El archivo excede el tamaño máximo<br />";
         return FALSE;
      case 2:
         $this->_msMensajeError.="El archivo es muy grande<br />";
         return FALSE;
      case 3:
         $this->_msMensajeError.="El archivo fue subido parcialmente<br />";
         return FALSE;
      case 4:
         $this->_msMensajeError.="El archivo no fue subido<br />";
         return FALSE;
      }

      if($userfile=="none"){
         $this->_msMensajeError.="No file uploaded<br />";
         return FALSE;
      }

      if($userfile_size==0){
         $this->_msMensajeError.="El tamaño del archivo es cero<br />";
         return FALSE;
      }

      if(!is_uploaded_file($userfile)){
         $this->_msMensajeError.="Possible file upload attack<br />";
         return FALSE;
      }

      $upfile = urldecode($dir.$userfile_name);

      if(file_exists($upfile)){
         $this->_msMensajeError.="El archivo ya existe en el directorio<br />";
         return FALSE;
      }

      if(!@copy($userfile, $upfile)){
         $this->_msMensajeError.="No se pudo copiar archivo<br />";
         return FALSE;
      }

      // "File uploaded successfully";
      return TRUE;
   }



   function renombrar_archivo($formVars){
      global $config;
      $dir_base = $config->dir_base;
      $prefix_mat = $config->prefix_mat;
      $prefix_mpl = $config->prefix_mpl;

      $oDB = $this->_db;

      $id_recurso = $formVars['PREVPK_id_recurso'];

      $sQuery="SELECT URL,id_parent,id_materia,id_materia_periodo_lectivo FROM ul_recurso WHERE id_recurso=$id_recurso";
      $result=$oDB->getFirstRowQuery($sQuery);
      if(is_array($result) && count($result)>0){
         $url = $result[0];
         $id_parent = $result[1];
         $id_mat = $result[2];
         $id_mat_per_lect = $result[3];
      }
      else{
         $this->_msMensajeError.="No existe el recurso indicado<br />";
         return FALSE;
      }

      $dir_ruta = $dir_base."/".$prefix_mat.$id_mat;
      if($id_mat_per_lect!=NULL){
         $dir_ruta .= "/".$prefix_mpl.$id_mat_per_lect;
      }

      $dir_ruta .= $this->obtener_ruta($id_parent);

      $dir_anterior = urldecode($dir_ruta."/".$url);
      $dir_nuevo = urldecode($dir_ruta)."/".$formVars['nuevo_nombre'];

      // renombra el archivo o directorio deseado
      if(!@rename($dir_anterior,$dir_nuevo)){
         $this->_msMensajeError.="No tiene los permisos necesarios para ejecutar esta operación<br />";
         return FALSE;
      }
      return TRUE;

   }


   /**
    * proceso para obtener la información del directorio base
    */
   function actualizar_DB(){
      $arr = $this->actualizar_DB_2();
      $msg = $this->getMessage();

      if(strlen($msg)>0 || count($arr)>0){
         if(strlen($msg)>0)
            $this->_msMensajeError.="<br />";
         if(count($arr)>0){
            $this->_msMensajeError.="<br />\nDirectorios sin permiso de lectura:";
            foreach ($arr as $i => $value)
                $this->_msMensajeError.="<br />\n&nbsp ".$arr[$i];
         }
         return FALSE;
      }
      return TRUE;
   }

   function actualizar_DB_2(){
      // leer directorio raiz ($dir_base)
      global $config;
      $directorios_sin_permisos = array();
      $dir_base = $config->dir_base;
      $prefix_mat=$config->prefix_mat;
      $oDB = $this->_db;

      $directorio = $dir_base;

      if(!file_exists($directorio)){
         $this->_msMensajeError.="El directorio base no existe<br />";
         return FALSE;
      }

      if(!is_readable($directorio)){
         $this->_msMensajeError.="No tiene permiso para acceder al directorio base<br />";
         $this->_msMensajeError.=$directorio." .<br />\n".$this->getMessage();
         return FALSE;
      }

      // materia y materia_periodo_lectivo
      $dir = opendir($directorio);
      if($dir===FALSE){
         $this->_msMensajeError.="No se pudo acceder al directorio<br />";
         return FALSE;
      }


      $bValido = TRUE;
      while($file=readdir($dir)){
         // las materias
         if(is_readable($directorio."/".$file)){
            if(is_dir($directorio."/".$file)){
               if($file!="." && $file!="..")
                  if(!(strpos($file,$prefix_mat)===FALSE)){
                     $id_file=str_replace($prefix_mat,"",$file); // elimina el prefijo para dejar el id_materia
                     // verificamos la existencia de la materia en la DB (debe existir)
                     $sQuery="SELECT count(*) FROM ul_materia WHERE id=$id_file";
                     $result=$oDB->getFirstRowQuery($sQuery);
                     if(is_array($result) && $result[0]>0){
                        $arr = $this->_actualizar_DB_materia($directorio."/".$file,$id_file);

                        if(is_array($arr) && count($arr)>0){
                           $directorios_sin_permisos = array_merge($directorios_sin_permisos, $arr);
                        }
                     }
                     else
                        $this->_msMensajeError.="<br />\nProblemas en la Actualización: La materia del directorio no existe en la BD. ".$directorio."/".$file." ";
                  } // no se toman en cuenta los directorios que no son de las materias
            }
            // no se toman en cuenta los archivos que están en el directorio $dir_base
         }
         else{ // no es readable
            $directorios_sin_permisos[]=$directorio."/".$file;
         }
      }
      closedir($dir);
      return $directorios_sin_permisos;
   }



   /**
    * proceso para obtener la información del directorio de la materia
    * $directorio - ruta (materia)
    * $id_materia - id de la materia
    */
   function _actualizar_DB_materia($directorio, $id_materia){
      // leer el directorio de la materia y cargar su contenido en la BD
      global $config;
      $directorios_sin_permisos=array();
      $prefix_mpl=$config->prefix_mpl;
      $oDB = $this->_db;

      // materia y materia_periodo_lectivo
      $dir = opendir($directorio);
      while($file = readdir($dir)){
         // las materias
         if(is_readable($directorio."/".$file)){
            if(is_dir($directorio."/".$file)){
               if($file!="." && $file!=".."){
                  //$bValido = FALSE;

                  // directorios de la materia_periodo_lectivo
                  if(!(strpos($file,$prefix_mpl)===FALSE)){
                     $id_file=str_replace($prefix_mpl,"",$file); // elimina el prefijo para dejar el id_materia

                     // verificamos la existencia de la materia en la DB (debe existir)
                     $sQuery="SELECT count(*) FROM ul_materia_periodo_lectivo WHERE id=$id_file";
                     $result=$oDB->getFirstRowQuery($sQuery);
                     // si existe la materia_periodo_lectivo se revisa el contenido del directorio
                     if(is_array($result) && $result[0]>0){
                        $arr = $this->_actualizar_DB_recursivo($directorio."/".$file, $id_materia, $id_file);
                        if(is_array($arr) && count($arr)>0)
                           $directorios_sin_permisos = array_merge($directorios_sin_permisos,$arr);
                        //$bValido = TRUE;
                     }
                     else
                        $this->_msMensajeError.="<br />\nProblemas en la Actualización: La materia_periodo_lectivo del directorio no existe en la BD. ".$directorio."/".$file." ";
                  }
                  else{
                  //if(!$bValido){ // otros directorios
                     if(($id_dir_parent=$this->buscar_archivo_DB($file, 'D', $id_materia))===FALSE){
                        $id_dir_parent=$this->agregar_archivo_DB($file,"D",$id_materia);
                     }
                     $arr = $this->_actualizar_DB_recursivo($directorio."/".$file, $id_materia, NULL, $id_dir_parent);
                     if(is_array($arr) && count($arr)>0)
                        $directorios_sin_permisos = array_merge($directorios_sin_permisos,$arr);
                  }
               }
            }
            else{
               // hay que añadir los archivos de la materia
               if($this->buscar_archivo_DB($file,"A",$id_materia)===FALSE){
                  $this->agregar_archivo_DB($file,"A",$id_materia);
               }
            }
         }
         else{ // no es readable
            $directorios_sin_permisos[]=$directorio."/".$file;
         }
      }
      closedir($dir);
      return $directorios_sin_permisos;
   }

   /**
    * proceso recursivo para obtener la información del directorio base con las materias
    * $directorio - ruta (materia + subdirectorios)
    * $id_materia - id de la materia
    * $id_materia_periodo_lectivo - id de la materia_periodo_lectivo
    */
   function _actualizar_DB_recursivo($directorio, $id_materia, $id_materia_periodo_lectivo, $id_parent=NULL){
      global $config;
      $directorios_sin_permisos = array();

      $dir = opendir($directorio);
      if($dir == NULL){
         $this->_msMensajeError.="<br />\nNo se pudo abrir el directorio: ".$directorio;
         return $directorios_sin_permisos; // arreglo vacio
      }

      while($file=readdir($dir)){
         if(is_readable($directorio."/".$file)){
            if(is_dir($directorio."/".$file)){
               if($file!="." && $file!=".."){
                  if($this->buscar_archivo_DB($file,"D",$id_materia, $id_materia_periodo_lectivo, $id_parent)===FALSE){
                     $id_dir_parent = $this->agregar_archivo_DB($file,"D",$id_materia, $id_materia_periodo_lectivo, $id_parent);

                     if($id_dir_parent!==FALSE){
                        $arr = $this->_actualizar_DB_recursivo($directorio."/".$file, $id_materia, $id_materia_periodo_lectivo, $id_dir_parent);
                        if(is_array($arr) && count($arr))
                           $directorios_sin_permisos = array_merge($directorios_sin_permisos,$arr);
                     }else
                        $this->_msMensajeError.="<br />\nNo hay id_dir_parent del directorio: ".$directorio."/".$file;
                  }
               }
            }
            else{
               // hay que añadir los archivos de la materia_periodo_lectivo
               if($this->buscar_archivo_DB($file,"A",$id_materia, $id_materia_periodo_lectivo, $id_parent)===FALSE){
                  if($this->agregar_archivo_DB($file,"A",$id_materia, $id_materia_periodo_lectivo, $id_parent)===FALSE){
                  }
               }
            }
         }
         else{ // no es readable
            $directorios_sin_permisos[]=$directorio."/".$file;
         }
      }
      closedir($dir);
      return $directorios_sin_permisos;
   }


   /***
    * busca un recurso (archivo o directorio) en la base de datos
    * si existe, retorna el id del recurso
    * si no existe, retorna FALSE
    */
   function buscar_archivo_DB($nombre, $tipo, $id_materia, $id_materia_periodo_lectivo=NULL, $id_parent=NULL){
      $oDB = $this->_db;
      $url=urlencode($nombre);
      $sQuery="SELECT * FROM ul_recurso WHERE (URL='$url' and tipo='$tipo' and id_materia=$id_materia and id_materia_periodo_lectivo";

      if($id_materia_periodo_lectivo==NULL)
         $sQuery.=" IS NULL";
      else
         $sQuery.="=$id_materia_periodo_lectivo";
      $sQuery.=" and id_parent";

      if($id_parent==NULL)
         $sQuery.=" IS NULL";
      else
         $sQuery.="=$id_parent";
      $sQuery.=")";

      $result=$oDB->getFirstRowQuery($sQuery);

      // si existe la materia_periodo_lectivo se revisa el contenido del directorio
      if(is_array($result) && count($result)>0){
         return $result[0];
      }
      return FALSE;
   }

   /***
    * inserta un recurso en la BD
    */
   function agregar_archivo_DB($nombre, $tipo, $id_materia, $id_materia_periodo_lectivo=NULL, $id_parent=NULL){
      $oDB = $this->_db;
      $url=urlencode($nombre);
      $sQuery="INSERT INTO ul_recurso values (NULL,'$url',NULL,'$tipo',";

      if($id_parent==NULL)
         $sQuery.="NULL,";
      else
         $sQuery.="$id_parent,";

      if($id_materia_periodo_lectivo==NULL)
         $sQuery.="NULL,";
      else
         $sQuery.="$id_materia_periodo_lectivo,";
      $sQuery.="$id_materia)";

      $bValido = $oDB->genQuery($sQuery);
      if($bValido){
         $sQuery ="SELECT LAST_INSERT_ID()";
         $result = $oDB->getFirstRowQuery($sQuery);
         $bValido=$result[0];
      }
      else{
         $this->_msMensajeError.="<br />\nNo pudo realizar la Inserción";
         $bValido = FALSE;
      }

      return $bValido;
   }


   function sincronizar_DB(){
      $bValido = $this->sincronizar_DB_con_archivos(NULL,NULL);
      return $bValido;
   }

   /***
    * realiza la eliminación de los recursos de la BD que
    * no existen de forma física en el directorio raiz
    * retorna el número de archivos y directorios eliminados y el
    * número de registros revisados de la BD
    */
   function sincronizar_DB_con_archivos($directorio, $id_parent){
      global $config;
      $oDB = $this->_db;
      $A = array("directorios" => 0, "archivos" => 0, "registros" => 0,);
      $bValido = TRUE;

      $prefix_mat=$config->prefix_mat;
      $prefix_mpl=$config->prefix_mpl;

      $dir_base=$config->dir_base;
      $dir=$directorio;
      if($directorio==NULL)
         $dir=$dir_base;

      $sQuery="SELECT id_recurso, URL, tipo, id_materia, id_materia_periodo_lectivo FROM ul_recurso WHERE id_parent";

      if($id_parent==NULL)
         $sQuery.=" is NULL";
      else
         $sQuery.="=$id_parent";

      $respuesta = $oDB->fetchTable($sQuery);
      $numero_materias=count($respuesta);

      for($i=0;$i<$numero_materias;$i++){
         $id_recurso = $respuesta[$i][0];
         $url = urldecode($respuesta[$i][1]);
         $tipo = $respuesta[$i][2];
         $id_materia = $respuesta[$i][3];
         $id_materia_periodo_lectivo = $respuesta[$i][4];

         $dir2=$dir;
         if($id_parent==NULL){
            $dir2.="/".$prefix_mat.$id_materia;
            if($id_materia_periodo_lectivo!=NULL)
               $dir2.="/".$prefix_mpl.$id_materia_periodo_lectivo;
         }
         $dir2 .= "/".$url;

         if($tipo=='D'){ // directorio
            $B=$this->sincronizar_DB_con_archivos($dir2, $id_recurso);
            if($B===FALSE)
               return FALSE;

            $A["directorios"] += $B["directorios"];
            $A["archivos"] += $B["archivos"];
            $A["registros"] += $B["registros"];

            if(!file_exists($dir2)){
               $A["directorios"]++;
               $sQuery="DELETE FROM ul_recurso WHERE id_recurso=$id_recurso";
               $bValido = $oDB->genQuery($sQuery);
            }
         }
         elseif($tipo=='A'){ // archivo
            $A["registros"]++;

            if(!file_exists($dir2)){
               $A["archivos"]++;
               $sQuery="DELETE FROM ul_recurso WHERE id_recurso=$id_recurso";
               $bValido = $oDB->genQuery($sQuery);
            }
         }
      }
      if(!$bValido){
         $this->_msMensajeError .= "<br />\nNo se pudo eliminar de la BD.<br />\nRegistros=".$A['registros']." <br />\nArchivos=".$A['archivos']." <br />\nDirectorios=".$A['directorios'];
         return FALSE;
      }
      return $A;
   }
}

?>
