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
// $Id: ul_download.class.php,v 1.1.1.1 2006/03/03 21:59:08 ainiguez Exp $
$gsRutaBase="..";
require_once ("../conf/default.conf.php");


class ul_download{
   var $URL;
   var $_db;
   var $id_materia;
   var $id_materia_periodo_lectivo;
   var $tipo;

   function ul_download(&$oDB,&$oTpl,$id_recurso,$login=''){
       //Primero se busca el URL del recurso
       global $config;

       $this->_db=$oDB;

       $sQuery="SELECT URL,id_materia,id_materia_periodo_lectivo,id_parent,tipo from ul_recurso where id_recurso=$id_recurso";
       $result=$oDB->getFirstRowQuery($sQuery,true);

          if(is_array($result) && count($result)>0){
             $ruta = $this->obtener_ruta($result['id_parent']);
                 if($result['id_materia_periodo_lectivo']!=NULL)
                    $carpeta_materia="/".$config->prefix_mpl.$result['id_materia_periodo_lectivo'];
                 else
                    $carpeta_materia="";
             $URL=$config->dir_base."/".$config->prefix_mat.$result['id_materia'].$carpeta_materia.urldecode($ruta).urldecode($result['URL']);

             $this->URL=$URL;
             $this->id_materia=$result['id_materia'];
             $this->id_materia_periodo_lectivo=$result['id_materia_periodo_lectivo'];
             $this->tipo=$result['tipo'];
          }

   }

   function descargar_archivo(){
        if(isset($this->URL)){
            $URL=$this->URL;

            header("Content-type: application/octet-stream; name=".basename($URL)."");
            header("Content-disposition: attachment; filename=".basename($URL)."");
            header("Content-Transfer-Encoding: binary");
            header("Content-Length: ".filesize($URL));
            readfile($URL);

        }
    }

    // obtiene la ruta desde la raiz hasta el directorio padre
   function obtener_ruta($id_parent){
      $db=$this->_db;
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


   }


class ul_download_foro extends ul_download{

function ul_download_foro(&$oDB,&$oTpl,$id_mensaje_archivo,$login=''){
       //Primero se busca el URL del recurso
       global $config;

       $this->_db=$oDB;

       $sQuery="SELECT ma.URL,mpl.id_materia,f.id_materia_periodo_lectivo FROM ".
               "ul_mensaje_archivo ma, ul_mensaje m, ul_topico t, ul_foro f, ul_materia_periodo_lectivo=mpl ".
               "WHERE ma.id=$id_mensaje_archivo and ma.id_mensaje=m.id_mensaje and m.id_topico=t.id_topico ".
               "AND t.id_foro=f.id_foro and f.id_materia_periodo_lectivo=mpl.id";
       $result=$oDB->getFirstRowQuery($sQuery,true);

          if(is_array($result) && count($result)>0){
                 if($result['id_materia_periodo_lectivo']!=NULL)
                    $carpeta_materia="/".$config->prefix_mpl.$result['id_materia_periodo_lectivo'];
                 else
                    $carpeta_materia="";

             $URL=$config->dir_base_foros."/".$config->prefix_mat.$result['id_materia'].$carpeta_materia."/".$result['URL'];

             $this->URL=$URL;
             $this->id_materia=$result['id_materia'];
             $this->id_materia_periodo_lectivo=$result['id_materia_periodo_lectivo'];
          }

   }

function descargar_archivo(){
        if(isset($this->URL)){
            $URL=$this->URL;
            $filename=extraer_prefijo(basename($URL));
            //$filename=urldecode($filename);

            header("Content-type: application/octet-stream; name=".$filename."");
            header("Content-disposition: attachment; filename=".$filename."");
            header("Content-Transfer-Encoding: binary");
            header("Content-Length: ".filesize($URL));
            readfile($URL);

        }
    }


}


?>
