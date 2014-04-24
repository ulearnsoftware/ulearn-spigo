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
// $Id: ul_foro_reporte.class.php,v 1.1.1.1 2006/03/03 21:59:09 ainiguez Exp $

require_once ("lib/paloEntidad.class.php");
require_once ("lib/paloACL.class.php");


class ul_foro_reporte extends PaloReporte
{   var $id_mpl;
    var $id_foro;
    var $id_topico;

    function ul_foro_reporte(&$oDB, $oPlantillas, $sBaseURL,$id_materia_periodo_lectivo,$id_foro,$id_topico)
    {    $this->PaloReporte($oDB, $oPlantillas);
         $estatus_foro=$estatus_topico="";

        if($id_foro>0){
            $sBaseURL.="&id_foro=$id_foro&action=mostrar_topicos";
            $this->id_foro=$id_foro;
            ///Se debe buscar el estatus del foro
            $estatus_foro=$this->obtener_estatus('FORO',$id_foro);
            ///Se debe buscar el estatus de los topicos y desactivar los que hayan expirado
            $this->desactivar_topico_expiracion($id_foro);
        }
        if($id_topico>0){
            $sBaseURL.="&id_topico=$id_topico&action=mostrar_mensajes";
            $this->id_topico=$id_topico;
            $estatus_topico=$this->obtener_estatus('TOPICO',$id_topico);
         }


        $oACL=getACL();
        $this->id_mpl=$id_materia_periodo_lectivo;
        $tabla_cabecera_foro=$this->generar_cabecera_opciones("LISTA_FOROS",$estatus_foro,$estatus_topico);
        $tabla_cabecera_topico=$this->generar_cabecera_opciones("LISTA_TOPICOS",$estatus_foro,$estatus_topico);
        $tabla_cabecera_mensaje=$this->generar_cabecera_opciones("LISTA_MENSAJES",$estatus_foro,$estatus_topico);

        $cabecera_navegacion_foro=$this->cabecera_navegacion("LISTA_FOROS",$id_materia_periodo_lectivo);
        $cabecera_navegacion_topico=$this->cabecera_navegacion("LISTA_TOPICOS",$id_materia_periodo_lectivo,$id_foro);
        $cabecera_navegacion_mensaje=$this->cabecera_navegacion("LISTA_MENSAJES",$id_materia_periodo_lectivo,$id_foro,$id_topico);

                ////////////Verificacion de perfil de usuario para mostrar columnas con input

            if ($oACL->isUserAuthorized($_SESSION['session_user'], 'delet', 'col_foros')){
               $arrHEADER_FORO=array("","FORO","TÓPICOS","ENVÍOS","ULT. ENVÍO","ESTATUS");
               $arrROW_FORO   =array(
                              array("{_DATA_INPUT}",'ALIGN'=>'CENTER'),
                              array("{_DATA_LINK}","ALIGN"=>"CENTER"),
                              array("{_DATA_N_TOPICOS}","ALIGN"=>"CENTER"),
                              array("{_DATA_RESPUESTAS}","ALIGN"=>"CENTER"),
                              array("{_DATA_ULTIMO_ENVIO}","ALIGN"=>"CENTER"),
                              array("{_DATA_TXT_ESTATUS}"),
                              );
            }
            else{
               $arrHEADER_FORO=array("FORO","TÓPICOS","ENVÍOS","ULT. ENVÍO","ESTATUS");
               $arrROW_FORO   =array(
                              array("{_DATA_LINK}","ALIGN"=>"CENTER"),
                              array("{_DATA_N_TOPICOS}","ALIGN"=>"CENTER"),
                              array("{_DATA_RESPUESTAS}","ALIGN"=>"CENTER"),
                              array("{_DATA_ULTIMO_ENVIO}","ALIGN"=>"CENTER"),
                              array("{_DATA_TXT_ESTATUS}"),
                              );
            }



        if (!$this->definirReporte("LISTA_FOROS", array(
   //"DEBUG"=>true,
            "TITLE"         =>  "Listado de Foros<br>\n".
                                 "<input type='hidden' name='id_materia_periodo_lectivo' value=$id_materia_periodo_lectivo>",
            "FILTRO"        =>  $cabecera_navegacion_foro."<br>".$tabla_cabecera_foro,
            "PAGECHOICE"    =>  array(15,30,60),
            "DATA_COLS"     =>  array(
                                    "ID_FORO"=>"id_foro",
                                    "TITULO"=>"titulo",
                                    "CONTENIDO"=>"contenido",
                                    "ESTATUS"=>"estatus",
                                    "ID_MATERIA_PERIODO_LECTIVO"=>"id_materia_periodo_lectivo",
                                ),
            "PRIMARY_KEY"   =>  array("ID_FORO"),
            "FROM"          =>  "ul_foro",
            "CONST_WHERE"   =>  "id_materia_periodo_lectivo=$id_materia_periodo_lectivo ",
            "ORDERING"      =>  array(
                                    "DEFAULT"   =>  array("ID_FORO","TITULO"),
                                ),
            "BASE_URL"      =>  $sBaseURL,
            "HEADERS"       =>  $arrHEADER_FORO,
            "ROW"           =>  $arrROW_FORO
        ))) die ("ul_foro_reporte: - al definir reporte LISTA_FOROS - ".$this->_msMensajeError);

              ////////////Verificacion de perfil de usuario para mostrar columnas con input

            if ($oACL->isUserAuthorized($_SESSION['session_user'], 'delet', 'col_foros')){
               $arrHEADER_TOPICO=array("","TÓPICO","RESP","ULT. ENVÍO","FECHA CREACIÓN","FECHA CIERRE","ESTATUS");
               $arrROW_TOPICO   =array(
                              array("{_DATA_INPUT}",'ALIGN'=>'CENTER'),
                              array("{_DATA_LINK}","ALIGN"=>"CENTER"),
                              array("{_DATA_RESPUESTAS}","ALIGN"=>"CENTER"),
                              array("{_DATA_ULTIMO_ENVIO}"),
                              array("{_DATA_FECHA_CREACION}"),
                              array("{_DATA_FECHA_CIERRE}"),
                              array("{_DATA_TXT_ESTATUS}"),
                              );
            }
            else{
               $arrHEADER_TOPICO=array("TÓPICO","RESP","ULT. ENVÍO","FECHA CREACIÓN","FECHA CIERRE","ESTATUS");
               $arrROW_TOPICO   =array(
                              array("{_DATA_LINK}","ALIGN"=>"CENTER"),
                              array("{_DATA_RESPUESTAS}","ALIGN"=>"CENTER"),
                              array("{_DATA_ULTIMO_ENVIO}"),
                              array("{_DATA_FECHA_CREACION}"),
                              array("{_DATA_FECHA_CIERRE}"),
                              array("{_DATA_TXT_ESTATUS}"),
                              );
            }

      $contenido_foro="";
      global $config;
      $tpl=new PaloTemplate("skins/".$config->skin);
      $tpl->definirDirectorioPlantillas("");

         if($id_foro>0){
            ///Se busca el contenido del foro para mostrar en la parte superior
            $sQuery="SELECT contenido FROM ul_foro WHERE id_foro=$id_foro";
            $result=$oDB->getFirstRowQuery($sQuery,true);
               if(is_array($result) && count($result)>0){
                  //Se convierten los caracteres especiales a saltos de linea
                  $contenido=nl2br($result['contenido']);
                  $contenido=htmlentities($contenido,ENT_QUOTES,"UTF-8");
                  $contenido=str_replace("&lt;br /&gt;","<br>",$contenido);

                  $tpl->assign("DATA",$contenido);
                  $tpl->parse("TDs_DATA","tpl__table_data_cell");
                  $tpl->parse("DATA_ROWs","tpl__table_data_row");
                  $tpl->assign("HEADER_ROW","");
                  $tpl->assign("TITLE_ROW","");
                  $tpl->assign("TBL_WIDTH","100%");

                  $tpl->parse("TABLA", "tpl__table_container");
                  $contenido_foro=$tpl->fetch("TABLA");
               }

         }

        if (!$this->definirReporte("LISTA_TOPICOS", array(
   //"DEBUG"=>true,
            "TITLE"         =>  "Listado de Tópicos<br>\n".
                                 "<input type='hidden' name='id_foro' value='$id_foro'>".
                                 "<input type='hidden' name='id_materia_periodo_lectivo' value=$id_materia_periodo_lectivo>",
            "FILTRO"        =>  $cabecera_navegacion_topico."<br>".$contenido_foro."<br><br>".$tabla_cabecera_topico,
            "PAGECHOICE"    =>  array(15,30,60),
            "DATA_COLS"     =>  array(
                                    "ID_TOPICO"=>"id_topico",
                                    "ESTATUS"=>"estatus",
                                    "TITULO"=>"titulo",
                                    "CONTENIDO"=>"contenido",
                                    "FECHA_ENVIO"=>"fecha_envio",
                                    "ID_ULTIMO_ENVIO"=>"id_ultimo_envio",
                                    "AUTOR"=>"autor",
                                    "N_RESPUESTAS"=>"n_respuestas",
                                    "FECHA_CREACION"=>"fecha_creacion",
                                    "FECHA_CIERRE"=>"fecha_cierre",
                                    "ID_FORO"=>"id_foro",
                                ),
            "PRIMARY_KEY"   =>  array("ID_TOPICO"),
            "FROM"          =>  "ul_topico",
            "CONST_WHERE"   =>  "id_foro=$id_foro",
            "ORDERING"      =>  array(
                                    "DEFAULT"   =>  array("ID_TOPICO","TITULO"),
                                ),
            "BASE_URL"      =>  $sBaseURL,
            "HEADERS"       =>  $arrHEADER_TOPICO,
            "ROW"           =>  $arrROW_TOPICO
        ))) die ("ul_foro_reporte: - al definir reporte LISTA_TOPICOS - ".$this->_msMensajeError);


////////////Verificacion de perfil de usuario para mostrar columnas con input

            if ($oACL->isUserAuthorized($_SESSION['session_user'], 'delet', 'col_foros')){
               $arrHEADER_MENSAJE=array("","MENSAJES");
               $arrROW_MENSAJE   =array(
                              array("{_DATA_INPUT}",'ALIGN'=>'CENTER'),
                              array("{_DATA_CONTENIDO}","ALIGN"=>"CENTER"),
                              );
            }
            else{
               $arrHEADER_MENSAJE=array("MENSAJES");
               $arrROW_MENSAJE   =array(
                              array("{_DATA_CONTENIDO}","ALIGN"=>"CENTER"),
                              );
            }


      $contenido_topico="";

         if($id_topico>0){
            ///Se busca el contenido del foro para mostrar en la parte superior
            $sQuery="SELECT contenido FROM ul_topico WHERE id_topico=$id_topico";
            $result=$oDB->getFirstRowQuery($sQuery,true);
               if(is_array($result) && count($result)>0){
                  $contenido=nl2br($result['contenido']);
                  $contenido=htmlentities($contenido,ENT_QUOTES,"UTF-8");
                  $contenido=str_replace("&lt;br /&gt;","<br>",$contenido);
                  
                  $tpl->assign("DATA",$contenido);
                  $tpl->parse("TDs_DATA","tpl__table_data_cell");
                  $tpl->parse("DATA_ROWs","tpl__table_data_row");
                  $tpl->assign("HEADER_ROW","");
                  $tpl->assign("TITLE_ROW","");
                  $tpl->assign("TBL_WIDTH","100%");

                  $tpl->parse("TABLA", "tpl__table_container");
                  $contenido_topico=$tpl->fetch("TABLA");
               }

         }


        if (!$this->definirReporte("LISTA_MENSAJES", array(
   //"DEBUG"=>true,
            "TITLE"         =>  "Listado de Mensajes<br>\n".
                                 "<input type='hidden' name='id_foro' value='$id_foro'>".
                                 "<input type='hidden' name='id_topico' value='$id_topico'>".
                                 "<input type='hidden' name='id_materia_periodo_lectivo' value=$id_materia_periodo_lectivo>",
            "FILTRO"        =>  $cabecera_navegacion_mensaje."<br>".$contenido_topico."<br>".$tabla_cabecera_mensaje,
            "PAGECHOICE"    =>  array(1000),  ///tamaño grande para evitar paginacion
            "DATA_COLS"     =>  array(
                                    "ID_MENSAJE"=>"id_mensaje",
                                    "TITULO"=>"titulo",
                                    "CONTENIDO"=>"contenido",
                                    "FECHA_ENVIO"=>"fecha_envio",
                                    "AUTOR"=>"autor",
                                    "TIPO_DOCENTE"=>"tipo_docente",
                                    "ID_TOPICO"=>"id_topico",
                                ),
            "PRIMARY_KEY"   =>  array("ID_MENSAJE"),
            "FROM"          =>  "ul_mensaje",
            "CONST_WHERE"   =>  "id_topico=$id_topico",
            "ORDERING"      =>  array(
                                    "DEFAULT"   =>  array("ID_MENSAJE"),
                                ),
            "BASE_URL"      =>  $sBaseURL,
            "HEADERS"       =>  $arrHEADER_MENSAJE,
            "ROW"           =>  $arrROW_MENSAJE,
        ))) die ("ul_foro_reporte: - al definir reporte LISTA_MENSAJES - ".$this->_msMensajeError);
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
      $link_input=$link_vista=$contenido=$n_topicos=$n_respuestas=$ultimo_envio=$fecha_creacion=$txt_estatus="";

      ////Se crea un objeto plantilla
      $insTpl =& new paloTemplate("skins/".$config->skin);
      $insTpl->definirDirectorioPlantillas("colaboracion");
      $insTpl->assign("IMG_PATH", "skins/$config->skin/images");

      $template=& new paloTemplate("skins/".$config->skin);
      $template->definirDirectorioPlantillas("");
      $template->assign("IMG_PATH", "skins/$config->skin/images");

      switch ($sNombreReporte) {
         case "LISTA_FOROS":

               if ($oACL->isUserAuthorized($_SESSION['session_user'], 'updat', 'col_foros') || $oACL->isUserAuthorized($_SESSION['session_user'], 'delet', 'col_foros'))
                  $link_input="<input type='radio' name='id_foro' value='".$tuplaSQL['ID_FORO']."'>";
               else
                  $link_input="";

           $link_vista="<a href='?id_materia_periodo_lectivo=".$tuplaSQL['ID_MATERIA_PERIODO_LECTIVO']."&menu1op=submenu_colaboracion&submenuop=col_foros&action=mostrar_topicos&id_foro=".$tuplaSQL['ID_FORO']."'>".$tuplaSQL['TITULO']."</a>";

               if($tuplaSQL['ESTATUS']=='A')
                  $txt_estatus="Activo";
               else
                  $txt_estatus="Inactivo";



            ///Se debe obtener informacion de topicos del foro
            $arr_topicos=$this->obtener_info_topicos($tuplaSQL['ID_FORO']);
            $n_topicos=$arr_topicos['n_topicos'];
            $n_respuestas=$arr_topicos['n_respuestas'];
            $ultimo_envio=$arr_topicos['ultimo_envio'];

            break;

         case "LISTA_TOPICOS":
               if ($oACL->isUserAuthorized($_SESSION['session_user'], 'updat', 'col_foros') || $oACL->isUserAuthorized($_SESSION['session_user'], 'delet', 'col_foros'))
                  $link_input="<input type='radio' name='id_topico' value='".$tuplaSQL['ID_TOPICO']."'>";
               else
                  $link_input="";
            $link_vista="<a href='?id_materia_periodo_lectivo=".$this->id_mpl."&menu1op=submenu_colaboracion&submenuop=col_foros&id_foro=".$tuplaSQL['ID_FORO']."&id_topico=".$tuplaSQL['ID_TOPICO']."&action=mostrar_mensajes'>".$tuplaSQL['TITULO']."</a>";

               if($tuplaSQL['ESTATUS']=='A')
                  $txt_estatus="Activo";
               else
                  $txt_estatus="Inactivo";

            ///Se debe obtener la informacion de los mensajes del topico
            $arr_mensajes=$this->obtener_info_mensajes($tuplaSQL['ID_TOPICO']);
            $n_respuestas=$arr_mensajes['n_respuestas'];
            $ultimo_envio=$arr_mensajes['ultimo_envio'];
            $fecha_creacion=$tuplaSQL['FECHA_CREACION'];
            break;

         case "LISTA_MENSAJES":
            if ($oACL->isUserAuthorized($_SESSION['session_user'], 'delet', 'col_foros'))
               $link_input="<input type='radio' name='id_mensaje' value='".$tuplaSQL['ID_MENSAJE']."'>";
            else
               $link_input="";

            $class_head="head_mensaje";
            $class_mensaje="contenido_mensaje";
            //Si el autor del mensaje es de tipo docente se debe mostrar otro color
            if($tuplaSQL['TIPO_DOCENTE']=='1'){
               $class_head="head_mensaje_docente";
               $class_mensaje="contenido_mensaje_docente";
            }

                  ///TODO: Tratar de implementar soporte para etiquetas html
            $contenido=$tuplaSQL['CONTENIDO'];

            ///Se crea un string html con las variables que necesita el formulario dentro del mensaje para poder
            ///hacer que funcion el boton responder. Solo si el topico esta activo
            $estatus_topico=$this->obtener_estatus("TOPICO",$tuplaSQL['ID_TOPICO']);
            if($estatus_topico=='A')
               $link_responder="<a href='?menu1op=submenu_colaboracion&submenuop=col_foros&id_materia_periodo_lectivo=".$this->id_mpl."&id_foro=".$this->id_foro."&id_topico=".$this->id_topico."&action=crear_mensaje&id_parent=".$tuplaSQL['ID_MENSAJE']."'>".
                               "<img src=\"skins/".$config->skin."/images/responder.gif\" border=0 alt='Responder'></a>";
            else
               $link_responder="";

            ////Se debe buscar si el mensaje tiene archivos adjuntos
            $tabla_archivos="";
            $arr_archivos=$this->obtener_archivos($tuplaSQL['ID_MENSAJE']);
            if(is_array($arr_archivos) && count($arr_archivos)>0){
               foreach($arr_archivos as $key=>$value){
                  $link="<a href='modules/download_foros.php?id=$key'>$value</a>";
                  $template->assign("DATA",$link);
                  $template->parse("TDs_DATA","tpl__table_data_cell");
                  $template->parse("DATA_ROWs",".tpl__table_data_row");
               }
                // Parsing tabla
               $template->assign("TITLE_ROW","");
               $template->assign("TBL_WIDTH",250);
               $template->assign("HEADER_TEXT","<b>Archivos Adjuntos</b>");
               $template->parse("HEADER_TDs", "tpl__table_header_cell");
               $template->parse("HEADER_ROW","tpl__table_header_row");
               $template->parse("TABLA", "tpl__table_container");
               $tabla_archivos = $template->fetch("TABLA");
            }

            $titulo="<b>Autor:</b> &nbsp;".$tuplaSQL['AUTOR'];
            $insTpl->assign("TITULO", $titulo);
            $insTpl->assign("CLASS_HEAD", $class_head);
            $insTpl->assign("CLASS_MENSAJE", $class_mensaje);
            $insTpl->assign("FECHA_ENVIO",$tuplaSQL['FECHA_ENVIO']);
            $insTpl->assign("LINK_RESPONDER",$link_responder);
            $insTpl->assign("TABLA_ARCHIVOS",$tabla_archivos);
            $insTpl->assign("CONTENIDO",$contenido);
            $insTpl->parse("SALIDA", "tpl_mensaje");
            $contenido=$insTpl->fetch("SALIDA");
            break;
         default:
      }

      return array("INPUT"       =>    $link_input,
                   "LINK"        =>    $link_vista,
                   "CONTENIDO"   =>    $contenido,
                   "N_TOPICOS"   =>    $n_topicos,
                   "RESPUESTAS"  =>    $n_respuestas,
                   "ULTIMO_ENVIO"=>    $ultimo_envio,
                   "FECHA_CREACION"=>  $fecha_creacion,
                   "TXT_ESTATUS"=>$txt_estatus,
                   );

   }

   ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
   ////////Funciones de Eliminacion
   ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
   function eliminar_foro($id_foro){
      $oACL=getACL();
      $oDB=$this->_db;

      if (!$oACL->isUserAuthorized($_SESSION['session_user'], 'delet', 'col_foros')){
         $this->setMessage("Usted no está autorizado para realizar esta acción.");
         return FALSE;
      }
      ///Se buscan primero los topicos relacionados al foro
      $sPeticionSQL="SELECT id_topico from ul_topico WHERE id_foro=$id_foro";
      $recordset=$oDB->fetchTable($sPeticionSQL);
      if(is_array($recordset) && count($recordset)>0){
         foreach($recordset as $fila){
            $id_topico=$fila[0];
               $bValido=$this->eliminar_topico($id_topico);
                  if(!$bValido)  ///El mensaje de error ya esta seteado por la funcion eliminar mensaje
                     return FALSE;
         }
      }
         // eliminar de la base de datos
      $sQuery="DELETE from ul_foro WHERE id_foro=$id_foro";
      $bValido=$oDB->genQuery($sQuery);
      if(!$bValido){
         $this->setMessage("Error al eliminar foro. Error: ".$oDB->errMsg);
         return FALSE;
      }


      return $bValido;
   }

   function eliminar_topico($id_topico){
      $oACL=getACL();
      $oDB=$this->_db;

         if (!$oACL->isUserAuthorized($_SESSION['session_user'], 'delet', 'col_foros')){
            $this->setMessage("Usted no está autorizado para realizar esta acción.");
            return FALSE;
         }

      ///Se buscan primero los mensajes relacionados al topico
      $sPeticionSQL="SELECT id_mensaje from ul_mensaje WHERE id_topico=$id_topico";
      $recordset=$oDB->fetchTable($sPeticionSQL);
         if(is_array($recordset) && count($recordset)>0){
            foreach($recordset as $fila){
               $id_mensaje=$fila[0];
               $bValido=$this->eliminar_mensaje($id_mensaje);
               if(!$bValido)  ///El mensaje de error ya esta seteado por la funcion eliminar mensaje
                  return FALSE;
            }
         }

         // eliminar de la base de datos
      $sQuery="DELETE from ul_topico WHERE id_topico=$id_topico";
      $bValido=$oDB->genQuery($sQuery);
         if(!$bValido){
            $this->setMessage("Error al eliminar tópico. Error: ".$oDB->errMsg);
            return FALSE;
         }


      return $bValido;
   }



function desactivar_topico_expiracion($id_foro){
$db=$this->getDB();
$fecha_actual=date("Y-m-d H:i:s",time());

$sQuery="SELECT id_topico FROM ul_topico WHERE id_foro=$id_foro and estatus='A' and fecha_cierre<='$fecha_actual'";
$result=$db->fetchTable($sQuery);


   if(is_array($result) && count($result)>0){
      foreach($result as $fila){
         $id_topico=$fila[0];
         $sPeticionSQL="UPDATE ul_topico SET estatus='I' WHERE id_topico=$id_topico";
         $bValido=$db->genQuery($sPeticionSQL);
      }
   }


}



    function eliminar_mensaje($id_mensaje){
      $oACL=getACL();
      $oDB=$this->_db;
      global $config;

         if (!$oACL->isUserAuthorized($_SESSION['session_user'], 'delet', 'col_foros')){
            $this->setMessage("Usted no está autorizado para realizar esta acción.");
            return FALSE;
         }

         // eliminar de la base de datos
      //Se debe eliminar primero todos los archivos relacionados con el mensaje
      //del disco duro
      $bValido=TRUE;
      $sQuery="SELECT ma.URL,ma.id,mpl.id_materia,f.id_materia_periodo_lectivo ".
              "FROM ul_mensaje_archivo ma, ul_mensaje m, ul_topico t, ul_foro f, ul_materia_periodo_lectivo mpl ".
              "WHERE ma.id_mensaje=$id_mensaje and ma.id_mensaje=m.id_mensaje and m.id_topico=t.id_topico ".
               "AND t.id_foro=f.id_foro and f.id_materia_periodo_lectivo=mpl.id";
      $result=$oDB->fetchTable($sQuery,true);

         if(is_array($result) && count($result)>0){
            foreach($result as $fila){
               $URL=$fila['URL'];
               $id_materia=$fila['id_materia'];
               $id_materia_periodo_lectivo=$fila['id_materia_periodo_lectivo'];

               $ruta=$config->dir_base_foros."/".$config->prefix_mat.$id_materia."/".$config->prefix_mpl.$id_materia_periodo_lectivo."/".$URL;

                  if(file_exists($ruta))
                     $bValido *= unlink($ruta);
            }

         }

         if(!$bValido){
            $this->setMessage("Error: No se pueden eliminar del disco duro los archivos asociados al mensaje");
            return FALSE;
         }


      $sQuery="DELETE from ul_mensaje_archivo WHERE id_mensaje=$id_mensaje";
      $bool=$oDB->genQuery($sQuery);
         if($bool){
            $sQuery="DELETE from ul_mensaje WHERE id_mensaje=$id_mensaje";
            $bValido=$oDB->genQuery($sQuery);
               if(!$bValido){
                  $this->setMessage("Error al eliminar mensaje. Error: ".$oDB->errMsg);
                  return FALSE;
               }
         }
         else{
            $this->setMessage("Error al eliminar los archivos asociados al mensaje");
            return FALSE;
         }

      return $bValido;
   }


   ////////////////////////////////////////////////////////////////////////////////////////////////////////////////

   function generar_cabecera_opciones($nombreFormulario,$estatus_foro='',$estatus_topico=''){
      //Se debe mostrar los botones para modificar, eliminar e ingresar dependiendo de los privilegios del usuario
      $oACL=getACL();
      $sContenido="";
      $arr_botones=array();

      switch($nombreFormulario){

         case "LISTA_FOROS":

               if ($oACL->isUserAuthorized($_SESSION['session_user'], 'updat', 'col_foros'))
                  $arr_botones[]="<input type='submit' name='modificar_foro' value='Modificar'>&nbsp;&nbsp;";

               if ($oACL->isUserAuthorized($_SESSION['session_user'], 'delet', 'col_foros'))
                  $arr_botones[]="<input type='submit' name='eliminar_foro' value='Eliminar' onClick=\"return confirm('Está seguro que desea eliminar este Foro?')\">&nbsp;&nbsp;";

               if ($oACL->isUserAuthorized($_SESSION['session_user'], 'creat', 'col_foros'))
                  $arr_botones[]="<input type='submit' name='crear_foro' value='Crear'>&nbsp;&nbsp;";
               break;
         case "LISTA_TOPICOS":
               if ($oACL->isUserAuthorized($_SESSION['session_user'], 'updat', 'col_foros'))
                  $arr_botones[]="<input type='submit' name='modificar_topico' value='Modificar'>&nbsp;&nbsp;";

               if ($oACL->isUserAuthorized($_SESSION['session_user'], 'delet', 'col_foros'))
                  $arr_botones[]="<input type='submit' name='eliminar_topico' value='Eliminar' onClick=\"return confirm('Está seguro que desea eliminar este Tópico?')\">&nbsp;&nbsp;";

               if($estatus_foro=='A'){
                  if ($oACL->isUserAuthorized($_SESSION['session_user'], 'creat', 'col_foros'))
                     $arr_botones[]="<input type='submit' name='crear_topico' value='Crear'>&nbsp;&nbsp;";
               }
               break;

         case "LISTA_MENSAJES":

               if ($oACL->isUserAuthorized($_SESSION['session_user'], 'delet', 'col_foros'))
                  $arr_botones[]="<input type='submit' name='eliminar_mensaje' value='Eliminar' onClick=\"return confirm('Está seguro que desea eliminar este Mensaje?')\">&nbsp;&nbsp;";

               ///Todos los usuarios pueden agregar mensajes si esta activo el topico
               if($estatus_topico=='A')
                  $arr_botones[]="<input type='submit' name='crear_mensaje' value='Crear Mensaje'>&nbsp;&nbsp;";
               break;
         default:
      }

      for($i=0;$i<count($arr_botones);$i++)
         $sContenido.=$arr_botones[$i];

      return $sContenido;
   }



   ///////Funcion que retorna la cabecera de navegacion por tipo de reporte

   function cabecera_navegacion($nombreFormulario,$id_materia_periodo_lectivo,$id_foro="",$id_topico=""){
      global $config;

      $db=$this->getDB();
      $sContenido="";
      $titulo=$home=$foro=$topico="";
      $link_home=$link_foro=$link_topico="";

      ////Se crea un objeto plantilla

      $insTpl =& new paloTemplate("skins/".$config->skin);
      $insTpl->definirDirectorioPlantillas("colaboracion");
      $insTpl->assign("IMG_PATH", "skins/$config->skin/images");

      ////Se debe obtener el titulo del foro
      if($id_foro>0){
         $sQuery="SELECT titulo,estatus FROM ul_foro WHERE id_foro=$id_foro";
         $result=$db->getFirstRowQuery($sQuery,true);
            if(is_array($result) && count($result)>0)
               $foro=htmlentities($result['titulo'],ENT_COMPAT,"UTF-8");


      }

      if($id_topico>0){
         $sQuery="SELECT titulo FROM ul_topico WHERE id_topico=$id_topico";
         $result=$db->getFirstRowQuery($sQuery,true);
            if(is_array($result) && count($result)>0)
               $topico=htmlentities($result['titulo'],ENT_COMPAT,"UTF-8");
      }

      $l_home="<a href='?id_materia_periodo_lectivo=$id_materia_periodo_lectivo&menu1op=submenu_colaboracion&submenuop=col_foros' class='letra_10'>Listado de Foros </a>";
      $l_foro="<a href='?id_materia_periodo_lectivo=$id_materia_periodo_lectivo&menu1op=submenu_colaboracion&submenuop=col_foros&action=mostrar_topicos&id_foro=$id_foro' class='letra_10'>&raquo; $foro </a>";
      $l_topico="<a href='?id_materia_periodo_lectivo=$id_materia_periodo_lectivo&menu1op=submenu_colaboracion&submenuop=col_foros&action=mostrar_mensajes&id_foro=$id_foro&id_topico=$id_topico' class='letra_10'>&raquo; $topico</a>";

      switch($nombreFormulario){
         case "LISTA_TOPICOS":
               $link_home=$l_home;
               $link_foro="&raquo; ".$foro;
               break;
         case "LISTA_MENSAJES":
               $link_home=$l_home;
               $link_foro=$l_foro;
               $link_topico="&raquo; ".$topico;
               break;

         default:
      }
      $insTpl->assign("TITULO", $titulo);
      $insTpl->assign("LINK_HOME",$link_home);
      $insTpl->assign("LINK_FORO",$link_foro);
      $insTpl->assign("LINK_TOPICO",$link_topico);

      $insTpl->parse("SALIDA", "tpl_cabecera");
      $sContenido=$insTpl->fetch("SALIDA");
      return $sContenido;
   }



   function obtener_info_topicos($id_foro){
   $db=$this->getDB();
   $n_topicos=$n_mensajes=0;
   $ultimo_envio="";

///Se obtiene el numero de topicos del foro
   $sQuery="SELECT count(id_topico) FROM ul_topico ".
            "WHERE id_foro=$id_foro";
   $result=$db->getFirstRowQuery($sQuery);
      if(is_array($result) && count($result)>0){
            $n_topicos=$result[0];
      }
///Se busca el numero total de mensajes de los topicos del foro y la ultima fecha del ultimo mensaje
   $sQuery="SELECT count(m.id_mensaje),max(m.fecha_envio) FROM ul_topico t, ul_mensaje m ".
            "WHERE t.id_foro=$id_foro and m.id_topico=t.id_topico";
   $result=$db->getFirstRowQuery($sQuery);
      if(is_array($result) && count($result)>0){
            $n_mensajes=$result[0];
            $ultimo_envio=$result[1];
      }

   return array("n_topicos"=>$n_topicos,
                "n_respuestas"=>$n_mensajes,
                "ultimo_envio"=>$ultimo_envio);

   }


   function obtener_info_mensajes($id_topico){

   $db=$this->getDB();
   $n_mensajes=0;
   $ultimo_envio="";

///Se obtiene el numero de mensajes del topico
   $sQuery="SELECT count(id_mensaje),max(fecha_envio) FROM ul_mensaje ".
            "WHERE id_topico=$id_topico";
   $result=$db->getFirstRowQuery($sQuery);
      if(is_array($result) && count($result)>0){
            $n_mensajes=$result[0];
            $ultimo_envio=$result[1];
      }


   return array("n_respuestas"=>$n_mensajes,
                "ultimo_envio"=>$ultimo_envio);


   }

function obtener_estatus($tabla,$id){
$db=$this->_db;
///$tabla { FORO -> ul_foros, TOPICO->ul_topico}

   switch($tabla){
      case "FORO":
         $sQuery="SELECT estatus from ul_foro where id_foro=$id";
         break;
      case "TOPICO":
         $sQuery="SELECT estatus FROM ul_topico where id_topico=$id";
         break;
      default:
         $sQuery="";
   }

   if($sQuery!=""){
      $result=$db->getFirstRowQuery($sQuery);
         if(is_array($result) && count($result)>0){
            $estatus=$result[0];
            return $estatus;
         }
         else
            return FALSE;
   }
}


///Obtiene el id y el nombre de los archivos asociados a un mensaje

function obtener_archivos($id_mensaje){

$db=$this->getDB();
$sQuery="SELECT * FROM ul_mensaje_archivo WHERE id_mensaje=$id_mensaje";
$result=$db->fetchTable($sQuery,true);
$arr_archivos=array();

   if(is_array($result)){
      if(count($result)>0){
         foreach($result as $fila){
            $id=$fila['id'];
            $URL=urldecode($fila['URL']);
            ///Se debe extraer el prefijo numerico del URL
            $URL=extraer_prefijo($URL);
            $arr_archivos[$id]=$URL;
         }
         return $arr_archivos;
      }
      else
         return $arr_archivos;
   }


}

}
?>
