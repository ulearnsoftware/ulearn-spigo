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
// $Id: ul_recurso_reporte.class.php,v 1.1.1.1 2006/03/03 21:59:09 ainiguez Exp $

require_once ("lib/paloEntidad.class.php");
require_once ("lib/paloACL.class.php");


class ul_recurso_reporte extends PaloReporte
{   var $id_mpl;

    function ul_recurso_reporte(&$oDB, &$oPlantillas, $sBaseURL,$id_materia_periodo_lectivo,$id_parent=NULL)
    {
        $this->PaloReporte($oDB, $oPlantillas);
                ////////////Verificacion de perfil de usuario para mostrar columnas con input
        $oACL=getACL();
            if ($oACL->isUserAuthorized($_SESSION['session_user'], 'delet', 'rec_lista')){
               $arrHEADER=array("","ESTATUS","TIPO","TÍTULO","TAMAÑO","");
               $arrROW   =array(
                              array("{_DATA_INPUT}",'style'=>"CLASS",'ALIGN'=>'CENTER'),
                              array("{_DATA_ESTATUS}",'style'=>"CLASS",'ALIGN'=>'CENTER'),
                              array("{_DATA_IMG_TIPO}","ALIGN"=>"CENTER",'style'=>"CLASS"),
                              array("{_DATA_NOMBRE}",'style'=>"CLASS"),
                              array("{_DATA_TAMAÑO}","ALIGN"=>"RIGHT",'style'=>"CLASS"),
                              array("{_DATA_OPCIONES}","ALIGN"=>"RIGHT",'style'=>"CLASS"));
            }
            else{
               $arrHEADER=array("TIPO","TÍTULO","TAMAÑO");
               $arrROW   =array(
                              array("{_DATA_IMG_TIPO}","ALIGN"=>"CENTER",'style'=>"CLASS"),
                              array("{_DATA_NOMBRE}",'style'=>"CLASS"),
                              array("{_DATA_TAMAÑO}",'style'=>"CLASS"));
            }


        $clause_where="and id_parent is NULL";

            if($id_parent!=NULL){
               if(ereg("^[[:digit:]]+$",$id_parent))
                  $clause_where="and id_parent=$id_parent ";
            }
        $tabla_arbol_directorio=$this->generar_arbol_directorio("?menu1op=submenu_recursos&submenuop=rec_lista&id_materia_periodo_lectivo=$id_materia_periodo_lectivo",$id_parent);
        $tabla_cabecera_opciones=$this->generar_cabecera_opciones();
       //////Se obtiene el id_materia
        $id_materia=$this->obtener_id_materia($id_materia_periodo_lectivo);
        $nombre_docente=$this->obtener_nombre_docente($id_materia_periodo_lectivo);

        $this->id_mpl=$id_materia_periodo_lectivo;

        if (!$this->definirReporte("LISTA_RECURSOS", array(
   //"DEBUG"=>true,
            "TITLE"         =>  "Listado de Recursos<br><br>\n".
               "<div style='font-size:8pt;'>Docente: $nombre_docente</div>".
               "<input type='hidden' name='id_materia_periodo_lectivo' value=$id_materia_periodo_lectivo>".
               "<input type='hidden' name='id_parent' value=$id_parent>",
            "FILTRO"        =>  $tabla_arbol_directorio."<br>".$tabla_cabecera_opciones,
            "PAGECHOICE"    =>  array(15,30,60),
            "DATA_COLS"     =>  array(
                                    "ID_RECURSO"=>"id_recurso",
                                    "URL"=>"URL",
                                    "COMENTARIO"=>"comentario",
                                    "TIPO"=>"tipo",
                                    "ID_PARENT"=>"id_parent",
                                    "ID_MATERIA_PERIODO_LECTIVO"=>"id_materia_periodo_lectivo",
                                    "ID_MATERIA"=>"id_materia",
                                    "ESTATUS"=>"estatus",
                                ),
            "PRIMARY_KEY"   =>  array("ID_RECURSO"),
            "FROM"          =>  "ul_recurso",
            "CONST_WHERE"   =>  "id_materia=$id_materia ".$clause_where.
                                " and (id_materia_periodo_lectivo=$id_materia_periodo_lectivo OR id_materia_periodo_lectivo is NULL)",
            "ORDERING"      =>  array(
                                    "DEFAULT"   =>  array("ID_MATERIA_PERIODO_LECTIVO","TIPO","URL"),
                                ),
            "BASE_URL"      =>  $sBaseURL,
            "HEADERS"       =>  $arrHEADER,
            "ROW"           =>  $arrROW
        ))) die ("ul_recurso_reporte: - al definir reporte LISTA_RECURSOS - ".$this->_msMensajeError);
    }


    /**
     * Procedimiento que muestra condicionalmente el enlace a la modificacin de la cuenta.
     * como una fila adicional de datos.
     *
     * @param string $sNombreReporte Nombre del reporte para el que se proveen las columnas
     * @param array  $tuplaSQL       Tupla con los valores a usar para la fila actual
     *
     * @return array    Valores a agregar a la tupla existente de SQL
     */
    function event_proveerCampos($sNombreReporte, $tuplaSQL)
    { global $config;
      $oACL=getACL();
      $prefix_mpl=$config->prefix_mpl;
      $prefix_mat=$config->prefix_mat;

      switch ($sNombreReporte) {
         case "LISTA_RECURSOS":
            $bgcolor="#EEEEEE"; //////style que sobrecarga a la clase table_data con el color de fondo de los recursos generales de la materia
            $id_mat_per_lect=$tuplaSQL['ID_MATERIA_PERIODO_LECTIVO'];
            $ruta_materia="";
            $url=$tuplaSQL['URL'];
            $class="";

            $id_materia_periodo_lectivo=recoger_valor("id_materia_periodo_lectivo",$_GET,$_POST);

               if($id_mat_per_lect!=NULL){
                  $ruta_materia = "materia_".$this->obtener_id_materia($id_mat_per_lect) ;
                  $ruta_archivo = $config->dir_base."/".$ruta_materia."/".$prefix_mpl.$id_mat_per_lect.$this->obtener_ruta($tuplaSQL['ID_PARENT']).$url;
               }
               else{
                  $class="BACKGROUND: $bgcolor";
                  $ruta_archivo=$config->dir_base."/".$config->prefix_mat.$tuplaSQL['ID_MATERIA'].$this->obtener_ruta($tuplaSQL['ID_PARENT']).$url;
               }

            $ruta_archivo=urldecode($ruta_archivo);
            $nombre_archivo=$size_archivo=$link_download=$link_imagen="";

               if(file_exists($ruta_archivo)){
                  if(is_readable($ruta_archivo))
                     $nombre_archivo=basename($ruta_archivo);
                  else
                     $nombre_archivo="Error: El archivo no puede ser leído. <br>No tiene suficientes permisos.";
               }

               elseif (is_file($ruta_archivo))
                     $nombre_archivo="Error: El archivo '".$url."' referenciado en la base de datos <br> no existe en el directorio.";
                  else
                     $nombre_archivo="Error: El directorio '".$url."' referenciado en la base de datos <br> no existe.";

               if(is_readable($ruta_archivo)){
                  $size_archivo=NULL;
                  if(!is_dir($ruta_archivo)){
                     $size_archivo=filesize($ruta_archivo);
                     ///TODO conversion de tamaño a KB y MB
                     $size_archivo=number_format($size_archivo/1024,2). " Kb";
                  }
               }

               if(is_readable($ruta_archivo)){
                  $img=$this->obtener_imagen_archivo($url,$tuplaSQL['TIPO']);

                     if($tuplaSQL['TIPO']=='D'){
                        $link_download="<a href='?id_materia_periodo_lectivo=".$this->id_mpl."&id_parent=".$tuplaSQL['ID_RECURSO']."'>";
                        $n_archivos=$this->obtener_numero_archivos($tuplaSQL['ID_RECURSO']);
                           if($n_archivos>0){
                              if($n_archivos==1)
                                 $size_archivo="<div align='center'>1 archivo</div>";
                              else
                                 $size_archivo="<div align='center'>$n_archivos archivos</div>";
                           }
                           else
                              $size_archivo="<div align='center'>-vacío-</div>";
                     }
                     else
                        $link_download="<a href='modules/download.php?id=".$tuplaSQL['ID_RECURSO']."' target='_self'>";

                     if($tuplaSQL['ESTATUS']=='A')
                        $link_imagen="$link_download<img src='skins/".$config->skin."/images/$img' border=0></a>";
                     else
                        $link_imagen="<img src='skins/{$config->skin}/images/$img' border='0' alt='No disponible' title='No Disponible'>";
               }

               if ($oACL->isUserAuthorized($_SESSION['session_user'], 'delet', 'rec_lista'))
                  $link_input="<input type='radio' name='in_recurso' value='".$tuplaSQL['ID_RECURSO']."'>";
               else
                  $link_input="";

               if($oACL->isUserAuthorized($_SESSION['session_user'], 'toggl', 'rec_lista')){
                  if($tuplaSQL['ESTATUS']=='A')
                     $link_opciones="<a href='?action=desactivar&in_recurso={$tuplaSQL['ID_RECURSO']}&id_materia_periodo_lectivo=$id_materia_periodo_lectivo'>Desactivar</a>";
                  else
                     $link_opciones="<a href='?action=activar&in_recurso={$tuplaSQL['ID_RECURSO']}&id_materia_periodo_lectivo=$id_materia_periodo_lectivo'>Activar</a>";
               }
               else
                  $link_opciones="";


         default:
            return array(
                        "INPUT" => $link_input,
                        "IMG_TIPO" => $link_imagen,
                        "NOMBRE" => $nombre_archivo,
                        "TAMAÑO" => $size_archivo,
                        "CLASS"  => $class,
                        "OPCIONES"=>$link_opciones,
                         );
      }
   }


   function eliminar_recurso($id_recurso){
      global $config;
      $oACL=getACL();
      
      $dir_base = $config->dir_base;
      $prefix_mpl=$config->prefix_mpl;
      $prefix_mat=$config->prefix_mat;

         if (!$oACL->isUserAuthorized($_SESSION['session_user'], 'delet', 'rec_lista')){
            $this->setMessage("Usted no está autorizado para realizar esta acción.");
            return FALSE;
         }
              $arr_botones[]="<input type='submit' name='renombrar' value='Renombrar'>";

      // obtener id_parent, id_materia_periodo_lectivo, id_materia y tipo de ul_recurso
      $oDB = $this->_db;
      $sQuery="SELECT id_parent, URL, id_materia_periodo_lectivo, id_materia, tipo from ul_recurso where id_recurso=$id_recurso";
      $result=$oDB->getFirstRowQuery($sQuery);

      $URL=$id_parent=$id_materia_periodo_lectivo=$id_materia=$tipo="";

      if(is_array($result) && count($result)>0){
         $id_parent.=$result[0];
         $URL.=urldecode($result[1]);
         $id_materia_periodo_lectivo.=$result[2];
         $id_materia.=$result[3];
         $tipo.=$result[4];
      }
      //obtener ruta de directorios
      $ruta = $dir_base."/".$prefix_mat.$id_materia;

      if($id_materia_periodo_lectivo!=NULL)
         $ruta .= "/".$prefix_mpl.$id_materia_periodo_lectivo;
      else
         ///Si el id_materia_periodo_lectivo es NULO se debe verificar si tiene permisos para eliminar. Accion para Admin
         if (!$oACL->isUserAuthorized($_SESSION['session_user'], 'admin', 'rec_lista')){
            $this->setMessage("Usted no está autorizado para realizar esta acción.");
            return FALSE;
         }

      $ruta .= $this->obtener_ruta($id_parent);
      $ruta = urldecode($ruta);
      $bValido = TRUE;

      if($URL)
         switch ($tipo){
         case 'A':
            if(file_exists($ruta.$URL))
               $bValido = unlink($ruta.$URL);
            break;

         case 'D':
            $dir = opendir($ruta."/".$URL);
            $i=0;
               while($file = readdir($dir)){
                  $i++;
               }
               if($i>2){ // hay archivos o directorios
                  $this->setMessage("El directorio no está vacío.");
                  $bValido = FALSE;
               }
               else
                  $bValido = rmdir($ruta."/".$URL);
            break;

         case 'L':
            break;
         }

      if($bValido){
         // eliminar de la base de datos
         $sQuery="DELETE from ul_recurso where id_recurso=$id_recurso";
         $result=$oDB->genQuery($sQuery);
      }
      return $bValido;
   }


function desactivar_recurso($id_recurso){
$oACL=getACL();
$db=$this->getDB();

   if (!$oACL->isUserAuthorized($_SESSION['session_user'], 'toggl', 'rec_lista')){
      $this->setMessage("Usted no está autorizado para realizar esta acción.");
      return FALSE;
   }
   else{
      $sQuery="UPDATE ul_recurso SET estatus='I' WHERE id_recurso=$id_recurso";
      $bValido=$db->genQuery($sQuery);
         if($bValido)
            return TRUE;
         else{
            $this->setMessage($db->errMsg);
            return FALSE;
         }
   }
}


function activar_recurso($id_recurso){
$oACL=getACL();
$db=$this->getDB();

   if (!$oACL->isUserAuthorized($_SESSION['session_user'], 'toggl', 'rec_lista')){
      $this->setMessage("Usted no está autorizado para realizar esta acción.");
      return FALSE;
   }
   else{
      $sQuery="UPDATE ul_recurso SET estatus='A' WHERE id_recurso=$id_recurso";
      $bValido=$db->genQuery($sQuery);
         if($bValido)
            return TRUE;
         else{
            $this->setMessage($db->errMsg);
            return FALSE;
         }
   }
}


   function generar_cabecera_opciones(){
      //Se debe mostrar los botones para modificar, eliminar e ingresar dependiendo de los privilegios del usuario
      $oACL=getACL();
      $sContenido="";
      $arr_botones=array();

      if ($oACL->isUserAuthorized($_SESSION['session_user'], 'updat', 'rec_lista'))
         $arr_botones[]="<input type='submit' name='renombrar' value='Renombrar'>&nbsp;&nbsp;";

    //  if ($oACL->isUserAuthorized($_SESSION['session_user'], 'updat', 'rec_lista'))
     //    $arr_botones[]="<input type='submit' name='reemplazar' value='Reemplazar'>";

      if ($oACL->isUserAuthorized($_SESSION['session_user'], 'delet', 'rec_lista'))
         $arr_botones[]="<input type='submit' name='eliminar' value='Eliminar' onClick=\"return confirm('Está seguro que desea eliminar este recurso?')\">&nbsp;&nbsp;";

      if ($oACL->isUserAuthorized($_SESSION['session_user'], 'creat', 'rec_lista'))
         $arr_botones[]="<input type='submit' name='crear_directorio' value='Crear Directorio'>&nbsp;&nbsp;";

      if ($oACL->isUserAuthorized($_SESSION['session_user'], 'creat', 'rec_lista'))
         $arr_botones[]="<input type='submit' name='subir_archivo' value='Subir Archivo'>&nbsp;&nbsp;";

      for($i=0;$i<count($arr_botones);$i++)
         $sContenido.=$arr_botones[$i];

      return $sContenido;
   }

////////////////////////**********************//////////////////////////////


   function obtener_imagen_archivo($url,$tipo){
      $arreglo_extensiones=array(
         "doc"	=>"doc.gif",
         "xls"	=>"xls.gif",
         "ppt"	=>"ppt.gif",
         "swf"	=>"swf.gif",
         "exe"	=>"exe.gif",
         "mp3"	=>"mp3.gif",
         "pdf"	=>"pdf.gif",
         "jgp"	=>"jpg.gif",
         "jpeg"	=>"jpg.gif",
         "mpg"	=>"mpg.gif",
         "mpeg"	=>"mpg.gif",
         "gif"   =>"gif.gif",
      );
      $img_default = "archivo.gif";
      $img_directorio = "folder.gif";

      if($tipo=='D')
         return $img_directorio;

      $extension=strtolower($this->obtener_extension_archivo($url));
          //Una vez obtenida la extension se busca si la clave existe en el arreglo de extensiones
      if(array_key_exists($extension,$arreglo_extensiones))
         return $arreglo_extensiones[$extension];
      else
         return $img_default;
   }


    function obtener_extension_archivo ($Filename) {
       $Extension = explode (".", $Filename);
       $ultimo_valor = (count($Extension) - 1);

       return trim($Extension[$ultimo_valor]);

    }


    function generar_arbol_directorio($baseURL,$id_parent){
       global $config;

       $sContenido="<div align=left class=textoNegro>";
       $db=$this->_db;
       $db=$this->getDB();
       $arr_nivel=array();
          ///Si el id_parent es distinto de nulo quiere decir que existe por lo menos un nivel
     ///Se muestra el link para regresar al directorio raiz
     if($id_parent!=NULL)
              $sContenido.="<a href='".$baseURL."' class='linkNegro'>".
                 "<img src='skins/".$config->skin."/images/dir_open.gif' border=0>Home</a>&nbsp;&nbsp;";

       $this->obtener_parent($db,$id_parent,$arr_nivel);

          for($i=0;$i<count($arr_nivel);$i++){
         if($i<count($arr_nivel)-1)
                 $sContenido.="<a href='".$baseURL."&id_parent=".$arr_nivel[$i]['id_recurso']."' class='linkNegro'>".
                    "<img src='skins/".$config->skin."/images/dir_open.gif' border=0>".$arr_nivel[$i]['nombre']."</a>&nbsp;&nbsp;";
         else
            $sContenido.="<img src='skins/".$config->skin."/images/dir_open.gif' border=0>".$arr_nivel[$i]['nombre']."&nbsp;&nbsp;";
     }
          if(count($arr_nivel)==0){   ///Si el id_parent no es valido no se obtiene ningun valor en arr_nivel
             $sContenido="";          ///No se muestra link de home
     }
       $sContenido.="</div>";
       return $sContenido;
    }

   function obtener_parent($oDB,$id_parent,&$arr_nivel){
      $sQuery="SELECT id_parent,URL,id_recurso from ul_recurso where id_recurso=$id_parent and tipo='D'";
      $result=$oDB->getFirstRowQuery($sQuery);
      if(is_array($result) && count($result)>0){
         $this->obtener_parent($oDB,$result[0],$arr_nivel);
         $arr_nivel[]=array('id_parent'=>$result[0],'nombre'=>urldecode($result[1]),'id_recurso'=>$result[2]);
      }
      return;
   }

   function obtener_ruta($id_padre){
      $db=$this->_db;
      $ruta_directorio=$this->obtener_ruta_parent($db,$id_padre);
      return "/".$ruta_directorio;
   }


   function obtener_ruta_parent($oDB,$id_parent){

      $sQuery="SELECT id_parent,URL,id_recurso from ul_recurso where id_recurso=$id_parent and tipo='D'";
      $result=$oDB->getFirstRowQuery($sQuery);
      $str="";
      if(is_array($result) && count($result)>0){
         $str.=$this->obtener_ruta_parent($oDB,$result[0]);
         $str.=basename($result[1])."/";
      }
      return $str;
   }

   function obtener_id_materia($id_mat_per_lect){
      $oDB=$this->getDB();
      $sQuery="SELECT id_materia from ul_materia_periodo_lectivo where id=$id_mat_per_lect";
      $result=$oDB->getFirstRowQuery($sQuery);
      $str="";
      if(is_array($result) && count($result)>0){
         $str=$result[0];
      }
      return $str;
   }

   function obtener_nombre_docente($id_mat_per_lect){
      $oDB=$this->getDB();
      $sQuery="SELECT concat(d.nombre,' ',d.apellido) as docente ".
               "FROM ul_materia_periodo_lectivo mpl, ul_docente d ".
               "WHERE mpl.id=$id_mat_per_lect and mpl.id_docente=d.id";
      $result=$oDB->getFirstRowQuery($sQuery);
      $str="";
      if(is_array($result) && count($result)>0){
         $str=$result[0];
      }
      return $str;
   }

   function obtener_numero_archivos($id_recurso){
      $oDB=$this->getDB();
      $sQuery="SELECT count(*) from ul_recurso where id_parent=$id_recurso and tipo='A'";
      $result=$oDB->getFirstRowQuery($sQuery);
      $cantidad=0;

         if(is_array($result) && count($result)>0){
            $cantidad=$result[0];
               ///Ahora se debe buscar si existen directorios en el id_recurso
            $sQuery="SELECT id_recurso from ul_recurso where id_parent=$id_recurso and tipo='D'";
            $recordset=$oDB->fetchTable($sQuery);
               if(is_array($recordset)){
                  if(count($recordset)>0){
                     foreach($recordset as $fila){
                        $id_dir=$fila[0];
                        $cantidad+=$this->obtener_numero_archivos($id_dir);
                     }
                  }
               }

         }
      return $cantidad;
   }

}
?>
