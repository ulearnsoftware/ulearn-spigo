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
// $Id: ul_archivo_calificable.class.php,v 1.1.1.1 2006/03/03 21:59:09 ainiguez Exp $

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

class ul_archivo_calificable
{  var $_db;
   var $_msMensajeError;
   var $archivo;

   function ul_archivo_calificable(&$oDB)
   {  $this->_db=$oDB;
      $id_materia_periodo_lectivo=recoger_valor("id_materia_periodo_lectivo",$_GET,$_POST);
   }


function getDB(){
return $this->_db;
}
function setMessage($text){
$this->_msMensajeError=$text;
}
function getMessage(){
return $this->_msMensajeError;
}



function abrir_archivo($id_materia_periodo_lectivo,$URL){

$ruta_base=$this->obtener_ruta_base($id_materia_periodo_lectivo);
$archivo=$ruta_base."/".urldecode($URL);

   if (file_exists($archivo)){
      $str=file_get_contents($archivo);
      return $str;
   }
   else
      return FALSE;

}




/*
* Procedimiento para realizar operaciones previas a la carga de archivos al sistema
   * y en la base de datos. Esta implementacion crea los directorios utilizados para
   * organizar los recursos utilizados en la materia.
   *
   * @param array  $formVars          Variables del formulario de insercin
   *
   * @return boolean TRUE si se creó el archivo, FALSE si no.
   */


   function realizar_upload($variable,$id_materia_periodo_lectivo){
      //variable: Nombre del input type=file
      $oACL=getACL();
     ///Se debe verificar si existe el directorio antes de continuar
      $dir_ruta=$this->crear_directorio_raiz($id_materia_periodo_lectivo);

      if($dir_ruta===FALSE){
         $this->_msMensajeError.="No se realizó la subida del archivo<br />";
         return FALSE;
      }
      $dir = $dir_ruta;

      ///Se debe verificar si existe el archivo subio correctamente
      $userfile = $_FILES[$variable]['tmp_name'];
      $userfile_name = $_FILES[$variable]['name'];
      $userfile_size = $_FILES[$variable]['size'];
      $userfile_type = $_FILES[$variable]['type'];

      $error = $_FILES[$variable]['error'];



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
      $id_user=$oACL->getIdUser($_SESSION['session_user']);
      ///reemplazar cualquier espacio en blanco por _
      $userfile_name=ereg_replace(" ","_",$userfile_name);
      $archivo=time().$id_user."-".urlencode($userfile_name);
      $upfile = $dir."/".$archivo;

      if(file_exists($upfile)){
         $this->_msMensajeError.="El archivo ya existe en el directorio<br />";
         return FALSE;
      }

      if(!@copy($userfile, $upfile)){
         $this->_msMensajeError.="No se pudo copiar archivo<br />";
         return FALSE;
      }

      // "File uploaded successfully";
      $this->archivo=$archivo;
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
   function crear_directorio_raiz($id_materia_periodo_lectivo){
      global $config;
      $dir_base = $config->dir_base_calificables;
      $prefix_mpl=$config->prefix_mpl;
      $prefix_mat=$config->prefix_mat;

      $id_materia = $this->obtener_id_materia($id_materia_periodo_lectivo);

      // Verificacion del permiso de escritura del directorio base
      if(!is_writable($dir_base)){
         //echo $dir_materia;
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
         $dir_materia_periodo_lectivo=$dir_materia;
         if($id_materia_periodo_lectivo>0)
            $dir_materia_periodo_lectivo .= "/".$prefix_mpl.$id_materia_periodo_lectivo;


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

      return $dir_materia_periodo_lectivo;
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

   function obtener_ruta_base($id_materia_periodo_lectivo){
      global $config;
      $dir_base = $config->dir_base_calificables;
      $prefix_mpl=$config->prefix_mpl;
      $prefix_mat=$config->prefix_mat;

      $id_materia = $this->obtener_id_materia($id_materia_periodo_lectivo);
      $ruta_base=$dir_base."/".$prefix_mat.$id_materia."/".$prefix_mpl.$id_materia_periodo_lectivo;
      return $ruta_base;
   }
}

?>
