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
// $Id: ul_mensaje.class.php,v 1.1.1.1 2006/03/03 21:59:09 ainiguez Exp $

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

class ul_mensaje extends PaloEntidad
{
   function ul_mensaje(&$oDB, &$oPlantillas,$id_materia_periodo_lectivo,$id_foro,$id_topico)
   {
      $oACL=getACL();

      $defTabla = PaloEntidad::describirTabla($oDB, "ul_mensaje");
      $defTabla["campos"]["id_mensaje"]["DESC"]      = "id de clave primaria del mensaje";
      $defTabla["campos"]["titulo"]["DESC"]       = "Título del foro";
      $defTabla["campos"]["contenido"]["DESC"]    = "Contenido del Foro";
      $defTabla["campos"]["fecha_envio"]["DESC"]  = "Fecha de ultimo envio";
      $defTabla["campos"]["autor"]["DESC"]        = "nombre del autor";
      $defTabla["campos"]["id_parent"]["DESC"]     = "id_parent";
      $defTabla["campos"]["id_topico"]["DESC"]    = "id_topico";
      $this->PaloEntidad($oDB, $oPlantillas, $defTabla);

      $id_parent=recoger_valor("id_parent",$_GET,$_POST);
      $id_materia_periodo_lectivo=recoger_valor("id_materia_periodo_lectivo",$_GET,$_POST);
      //////Buscar el titulo del mensaje del id_parent y anteponerle Re:
      $titulo="";
      if($id_parent!=NULL){
         $sQuery = "SELECT titulo FROM ul_mensaje WHERE id_mensaje=$id_parent";
         $result=$oDB->getFirstRowQuery($sQuery,true);
         if(is_array($result) && count($result)>0){
            $titulo="RE: ".$result['titulo'];
         }else
            $titulo="RE:";
         $field_parent=array(
                        "type"      =>    "hidden",
                        "name"      =>    "id_parent",
                        "value"     =>    $id_parent,
                        "_field"    =>    "id_parent",
                        );
      }
      else
         $field_parent=array("type"=>"hidden",
                              "name"=>"parent",
                              "value"=>"NULL");

      ///Se crean 5 campos file (para Upload)
      for($i=0;$i<5;$i++){
         $cont=$i+1;
         $arr=array(
                  "tag"       =>    "Subir Archivo #$cont: <br> (Opcional)",
                  "name"      =>    "archivo_$i",
                  "type"      =>    "html",
                  "value"      =>   "<input type='file' name='archivo[$i]' size='40'>",
                  );
         $campo_file[$i]=$arr;
      }

      if (!$this->definirFormulario("INSERT", "CREAR_MENSAJE",
         array(
            "title"     =>  "Crear Mensaje<br>".
               "<input type='hidden' name='MAX_FILE_SIZE' value='2000000'>".
               "<input type='hidden' name='action' value='crear_mensaje'>".
               "<input type='hidden' name='id_foro' value=$id_foro>".
               "<input type='hidden' name='id_topico' value=$id_topico>".
               "<input type='hidden' name='id_materia_periodo_lectivo' value=$id_materia_periodo_lectivo>".
               "<input type='hidden' name='id_parent' value=$id_parent>".
               "<a href=\"?menu1op=submenu_colaboracion&submenuop=col_foros&id_materia_periodo_lectivo=$id_materia_periodo_lectivo&id_foro=$id_foro&id_topico=$id_topico&action=mostrar_mensajes\">&laquo;&nbsp;Regresar</a>&nbsp;",
            "submit"    =>  array( "name" => "submit_foro", "value" => "Guardar", ),
            "options" => array( "enctype" => "multipart/form-data", ),
            "fields"    =>  array(
               array(
                  "tag"       =>    "Título:",
                  "name"      =>    "titulo",
                  "_empty"    =>    TRUE,
                  "_field"    =>    "titulo",
                  "size"      =>    52,
                  "value"    =>   $titulo,
                  ),
               array(
                  "tag"       =>    "Contenido:",
                  "name"      =>    "contenido",
                  "_field"    =>    "contenido",
                  '_empty'    =>    FALSE,
                  "cols"      =>    50,
                  "rows"      =>    10,
                  ),
               $campo_file[0],
               $campo_file[1],
               $campo_file[2],
               $campo_file[3],
               $campo_file[4],

               $field_parent,
               array(
                  "tag"       =>"",
                  "type"      =>"label",
                  "name"      =>"comentarios",
                  "value"     =>"Nota: El tamaño total de los archivos no debe superar los 2mb",
                  ),
               array(
                  "type"      =>    "hidden",
                  "name"      =>    "id_topico",
                  "value"     =>    $id_topico,
                  "_field"    =>    "id_topico",
                  ),
               array(
                  "type"      =>    "hidden",
                  "name"      =>    "id_materia_periodo_lectivo",
                  "value"     =>    $id_materia_periodo_lectivo,
                  ),
               array(
                  "type"      =>    "hidden",
                  "name"      =>    "archivo",
                  "value"     =>    "0",
                  "_empty"    =>    true,
                  ),
            ),
         ))) die ("ul_mensaje::ul_mensaje() - al definir formulario INSERT CREAR_MENSAJE - ".$this->_msMensajeError);


     }



function event_traducirFormularioBaseInsert($sNombreFormulario, $formVars){
// Servirse de la validacin de la clase PaloEntidad
$oDB=$this->getDB();
$oACL=getACL();
$dbVars = parent::event_traducirFormularioBaseInsert($sNombreFormulario, $formVars);
   if (is_array($dbVars)){
      switch ($sNombreFormulario) {
         case "CREAR_MENSAJE":
               $dbVars["fecha_envio"] = date("Y-m-d H:i:s",time());
               $dbVars["autor"]=obtener_nombre_usuario($oDB,$_SESSION['session_user']);
               ///Se debe buscar el grupo al que pertenece el autor, si es docente se pone el campo de tipo_docente=1
               $id_grupo=obtener_grupo_usuario($oACL,$_SESSION['session_user']); //Se obtiene el id_grupo
               $grupo=getEnumDescripcion("Grupo",$id_grupo);
                  if($grupo=='docente')
                     $dbVars['tipo_docente']='1';
               break;
         default:
               break;
      }
	}
        return $dbVars;
}



function deshacer_upload(){
global $config;

///Se borran los archivos creados por realizar_upload
   if(isset($_POST['archivo'])){
      $archivo=$_POST['archivo'];
         if(count($archivo)>0){
            foreach($archivo as $file){
                  if(file_exists($file))
                     unlink($file);
            }
         }
   }

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
   function event_validarValoresFormularioInsert($sNombreFormulario, &$formVars){
      $oACL=getACL();
      $bValido=TRUE;

      switch ($sNombreFormulario) {
      case "CREAR_MENSAJE":
              $formVars['contenido']=nl2br($formVars['contenido']);
              $bValido=$this->realizar_upload($formVars);
                  if(!$bValido)
                     $this->deshacer_upload($formVars);
         break;
      }
      return $bValido;
   }

function event_postcondicionInsert($sNombreFormulario, $dbVars, $formVars){

   switch($sNombreFormulario){
      case "CREAR_MENSAJE":
            //se obtiene el ultimo id insertado
            $db =& $this->getDB();
            $sQuery = "select LAST_INSERT_ID()";
            $result = $db->getFirstRowQuery($sQuery);
               if(is_array($result) && count($result)>0){
                  $id_mensaje=$result[0];
                  $bValido=$this->asignar_archivos($id_mensaje);
               }

   }
}



function asignar_archivos($id_mensaje){
$db=$this->getDB();
$bValido=TRUE;
global $config;

   if(isset($_POST['archivo'])){
      $arr_archivo=$_POST['archivo'];

         if(count($arr_archivo)>0){
            foreach($arr_archivo as $file){
               $URL=basename($file);
               $sQuery="INSERT INTO ul_mensaje_archivo (id_mensaje,URL) values ($id_mensaje,'$URL')";
               $bValido*=$db->genQuery($sQuery);

            }
         }
   }
   else
      return $bValido;

return $bValido;

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


   function realizar_upload(&$formVars){

      ///Se debe verificar si existe el directorio antes de continuar
      $dir_ruta=$this->crear_directorio_raiz($formVars);

      if($dir_ruta===FALSE){
         $this->_msMensajeError.="No se realizó la subida del archivo<br />";
         return FALSE;
      }
      $dir = $dir_ruta;
      $arr_archivos=array();


      ///Se debe verificar si existe el archivo subio correctamente
      $cont=0;
      foreach($_FILES['archivo']['name'] as $key=>$filename){
         if($filename!=""){
            $userfile = $_FILES['archivo']['tmp_name'][$key];
            $userfile_name = $_FILES['archivo']['name'][$key];
            $userfile_size = $_FILES['archivo']['size'][$key];
            $userfile_type = $_FILES['archivo']['type'][$key];

            $error = $_FILES['archivo']['error'][$key];


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

             ///reemplazar cualquier espacio en blanco por _
               $userfile_name=ereg_replace(" ","_",$userfile_name);
              ///Se le pone un timestamp del momento de creacion antes del nombre del archivo
               $upfile = $dir."/".time()."".$cont."_".urlencode($userfile_name);
               $cont++;

               if(file_exists($upfile)){
                  $this->_msMensajeError.="El archivo ya existe en el directorio<br />";
                  return FALSE;
               }

               if(!@copy($userfile, $upfile)){
                  $this->_msMensajeError.="No se pudo copiar archivo<br />";
                  return FALSE;
               }
               //Si es exitosa la copia se guarda el nombre del archivo en $formVars
               $_POST['archivo'][$key]=$upfile;
         }

      }

      // "File uploaded successfully";
    //  $formVars['archivo']=$arr_archivos;
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
      $dir_base = $config->dir_base_foros;
      $prefix_mpl=$config->prefix_mpl;
      $prefix_mat=$config->prefix_mat;

      $id_materia_periodo_lectivo = $formVars['id_materia_periodo_lectivo'];
      $id_materia = $this->obtener_id_materia($id_materia_periodo_lectivo);

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


}

?>
