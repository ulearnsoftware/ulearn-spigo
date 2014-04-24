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
// $Id: ul_contenido_reporte.class.php,v 1.1.1.1 2006/03/03 21:59:09 ainiguez Exp $

require_once ("lib/paloEntidad.class.php");
require_once ("lib/paloACL.class.php");


class ul_contenido_reporte extends PaloReporte
{  var $id_mpl;

   function ul_contenido_reporte(&$oDB, &$oPlantillas, $sBaseURL,$id_materia_periodo_lectivo)
   {
      $this->PaloReporte($oDB, $oPlantillas);
      $oACL=getACL();
      $this->id_mpl=$id_materia_periodo_lectivo;
      $tabla_cabecera_opciones=$this->generar_cabecera_opciones();

      //////////// Verificacion de perfil de usuario para mostrar columnas con input

      if ($oACL->isUserAuthorized($_SESSION['session_user'], 'delet', 'rec_lista')){
         $arrHEADER=array("","ESTATUS","ORDEN","OBS","TÍTULO");
         $arrROW   =array(
                        array("{_DATA_INPUT}",'ALIGN'=>'CENTER'),
                        array("{_DATA_ESTATUS}",'ALIGN'=>'CENTER'),
                        array("{_DATA_ORDEN}","ALIGN"=>"CENTER"),
                        array("{_DATA_OBSERVACION}","ALIGN"=>"CENTER"),
                        array("{_DATA_LINK}"),
                        );
      }
      else{
         $arrHEADER=array("ORDEN","OBS","TÍTULO");
         $arrROW   =array(
                        array("{_DATA_ORDEN}","ALIGN"=>"CENTER"),
                        array("{_DATA_OBSERVACION}","ALIGN"=>"CENTER"),
                        array("{_DATA_LINK}"));
      }


      if (!$this->definirReporte("LISTA_CONTENIDO", array(
         //"DEBUG"=>true,
         "TITLE"         =>  "Listado de Contenido<br>\n".
                              "<input type='hidden' name='id_materia_periodo_lectivo' value=$id_materia_periodo_lectivo>",
         "FILTRO"        =>  $tabla_cabecera_opciones,
         "PAGECHOICE"    =>  array(15,30,60),
         "DATA_COLS"     =>  array(
                                 "ID_CONTENIDO"=>"c.id_contenido",
                                 "TITULO"=>"c.titulo",
                                 "ORDEN"=>"c.orden",
                                 "ID_MATERIA"=>"c.id_materia",
                                 "ESTATUS"=>"c.estatus",
                                 "OBSERVACION"=>"c.observacion",
                             ),
         "PRIMARY_KEY"   =>  array("ID_CONTENIDO"),
         "FROM"          =>  "ul_contenido c, ul_materia_periodo_lectivo mpl",
         "CONST_WHERE"   =>  "mpl.id_materia=c.id_materia and mpl.id=$id_materia_periodo_lectivo ",
         "ORDERING"      =>  array(
                                 "DEFAULT"   =>  array("ORDEN","ID_CONTENIDO"),
                             ),
         "BASE_URL"      =>  $sBaseURL,
         "HEADERS"       =>  $arrHEADER,
         "ROW"           =>  $arrROW
      ))) die ("ul_contenido_reporte: - al definir reporte LISTA_CONTENIDO - ".$this->_msMensajeError);
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
   function event_proveerCampos($sNombreReporte, $tuplaSQL){
      global $config;
      $oACL=getACL();

      switch ($sNombreReporte) {
      case "LISTA_CONTENIDO":
         $id_materia=$tuplaSQL['ID_MATERIA'];

         if ($oACL->isUserAuthorized($_SESSION['session_user'], 'admin', 'con_lista'))
            $link_input="<input type='radio' name='in_contenido' value='".$tuplaSQL['ID_CONTENIDO']."'>";
         else
            $link_input="";

         if($tuplaSQL['ESTATUS']=='A')
            $link_vista="<a href='?id_materia_periodo_lectivo=".$this->id_mpl."&menu1op=submenu_contenido&submenuop=con_lista&action=mostrar_contenido&id_contenido=".$tuplaSQL['ID_CONTENIDO']."'>".$tuplaSQL['TITULO']."</a>";
         else
            $link_vista=$tuplaSQL['TITULO'];

      default:
         return array(
                     "INPUT" => $link_input,
                     "LINK" => $link_vista,
                      );
      }
   }


   function eliminar_contenido($id_contenido){
      global $config;
      $oACL=getACL();
      $oDB=$this->_db;

      if (!$oACL->isUserAuthorized($_SESSION['session_user'], 'admin', 'con_lista')){
         $this->setMessage("Usted no está autorizado para realizar esta acción.");
         return FALSE;
      }

      // obtener posicion (orden) del registro a eliminar
      $sQuery = "SELECT orden FROM ul_contenido WHERE id_contenido=$id_contenido";
      $result=$oDB->getFirstRowQuery($sQuery,true);
      if(is_array($result) && count($result)>0)
         $orden = $result['orden'];
      else{
         $this->setMessage("No se pudo reorganizar el Contenido.");
         return FALSE;
      }

      // eliminar de la base de datos
      $sQuery="DELETE from ul_contenido where id_contenido=$id_contenido";
      $bValido=$oDB->genQuery($sQuery);
      if(!$bValido){
         $this->setMessage("Error al eliminar contenido. Error: ".$oDB->errMsg);
         return FALSE;
      }

      // actualizar el orden de los registros
      $sQuery = "UPDATE ul_contenido SET orden=orden-1 WHERE orden>".$orden;
      $result = $oDB->genQuery($sQuery);
      if($result===FALSE){
         $this->setMessage("No se pudo realizar la actualización del Contenido.");
         return FALSE;
      }

      return $bValido;
   }


   function generar_cabecera_opciones(){
      //Se debe mostrar los botones para modificar, eliminar e ingresar dependiendo de los privilegios del usuario
      $oACL=getACL();
      $sContenido="";
      $arr_botones=array();

      if ($oACL->isUserAuthorized($_SESSION['session_user'], 'admin', 'con_lista'))
         $arr_botones[]="<input type='submit' name='modificar' value='Modificar'>&nbsp;&nbsp;";

      if ($oACL->isUserAuthorized($_SESSION['session_user'], 'admin', 'con_lista'))
         $arr_botones[]="<input type='submit' name='eliminar' value='Eliminar' onClick=\"return confirm('Está seguro que desea eliminar este recurso?')\">&nbsp;&nbsp;";

      if ($oACL->isUserAuthorized($_SESSION['session_user'], 'admin', 'con_lista'))
         $arr_botones[]="<input type='submit' name='crear' value='Crear'>&nbsp;&nbsp;";

      for($i=0;$i<count($arr_botones);$i++)
         $sContenido.=$arr_botones[$i];

      return $sContenido;
   }



}
?>
