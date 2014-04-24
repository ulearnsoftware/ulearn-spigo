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
// $Id: ul_cartelera_reporte.class.php,v 1.1.1.1 2006/03/03 21:59:09 ainiguez Exp $

require_once ("lib/paloEntidad.class.php");
require_once ("lib/paloACL.class.php");


class ul_cartelera_reporte extends PaloReporte{
   var $id_mpl;

   function ul_cartelera_reporte(&$oDB, &$oPlantillas, $sBaseURL, $id_materia_periodo_lectivo,$id_cartelera)
   {
      global $config;
      $this -> PaloReporte($oDB, $oPlantillas);
      $oACL=getACL();
      setLocale(LC_TIME,$config->locale);

      $this->id_mpl=$id_materia_periodo_lectivo;
      $tabla_cabecera_cartelera=$this->generar_cabecera_opciones("LISTA_CARTELERAS");
      $tabla_cabecera_evento=$this->generar_cabecera_opciones("LISTA_EVENTOS");


      //////////// Verificacion de perfil de usuario para mostrar columnas con input

      if ($oACL->isUserAuthorized($_SESSION['session_user'], 'delet', 'cart_lista')){
         $arrHEADER_CARTELERA=array("","EVENTO","FECHA DE COMIENZO","FECHA DE TERMINO");
         $arrROW_CARTELERA   =array(
                        array("{_DATA_INPUT}",'ALIGN'=>'CENTER'),
                        array("{_DATA_LINK}","ALIGN"=>"LEFT"),
                        array("{_DATA_FECHA_COMIENZO}","ALIGN"=>"RIGHT"),
                        array("{_DATA_FECHA_TERMINO}","ALIGN"=>"RIGHT"),
                        );
      }
      else{
         $arrHEADER_CARTELERA=array("EVENTO","FECHA","HORA");
         $arrROW_CARTELERA   =array(
                        array("{_DATA_LINK}","ALIGN"=>"LEFT"),
                        array("{_DATA_FECHA_COMIENZO}","ALIGN"=>"RIGHT"),
                        array("{_DATA_FECHA_TERMINO}","ALIGN"=>"RIGHT"),
                        );
      }


      
      $TABLE_FROM="(SELECT id_cartelera,titulo,contenido,creacion,inicio,final,'' as id_calificable,'C' as tipo,'0' as id_materia_periodo_lectivo,'' as vacio ".
                   "FROM ul_cartelera WHERE final>=NOW()) ".
                   "UNION ".
                   "(SELECT id_evento,titulo,contenido,creacion,".
                   "inicio,final,id_calificable,tipo,id_materia_periodo_lectivo,'GROUP BY' FROM ul_evento WHERE ".
                   "id_materia_periodo_lectivo=$id_materia_periodo_lectivo and tipo='A' and final>=NOW())";
      
      if (!$this->definirReporte("LISTA_CARTELERAS", array(
         //"DEBUG"=>true,
         "TITLE"         =>  "Cartelera<br>\n".
                              "<input type='hidden' name='id_materia_periodo_lectivo' value=$id_materia_periodo_lectivo>",
         "FILTRO"        =>  $tabla_cabecera_cartelera,
         "PAGECHOICE"    =>  array(15,30,60),
         "DATA_COLS"     =>  array(
                                 "ID"=>"T.id_cartelera",
                                 "TITULO"=>"T.titulo",
                                 "CONTENIDO"=>"T.contenido",
                                 "CREACION"=>"T.creacion",
                                 "INICIO"=>"T.inicio",
                                 "FINAL"=>"T.final",
                                 "ID_CALIFICABLE"=>"T.id_calificable",
                                 "TIPO"=>"T.tipo",
                                 "ID_MATERIA_PERIODO_LECTIVO"=>"T.id_materia_periodo_lectivo",
                                 "VACIO"=>"T.vacio",
                             ),
         "PRIMARY_KEY"   =>  array("ID"),
         "FROM"          =>  "($TABLE_FROM) as T",
         "CONST_WHERE"   =>  "1",
         "ORDERING"      =>  array(
                                 "DEFAULT"   =>  array("ID_MATERIA_PERIODO_LECTIVO", "INICIO", "FINAL"),
                             ),
         "BASE_URL"      =>  $sBaseURL,
         "HEADERS"       =>  $arrHEADER_CARTELERA,
         "ROW"           =>  $arrROW_CARTELERA
      ))) die ("ul_cartelera_reporte: - al definir reporte LISTA_CARTELERAS - ".$this->_msMensajeError);


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
    {
      global $config;
      $oACL=getACL();
      $link_input=$link_vista=$contenido=$n_topicos=$n_respuestas=$ultimo_envio=$fecha_creacion="";

      ////Se crea un objeto plantilla
      $insTpl =& new paloTemplate("skins/".$config->skin);
      $insTpl->definirDirectorioPlantillas("cartelera");
      $insTpl->assign("IMG_PATH", "skins/$config->skin/images");

      switch ($sNombreReporte) {
         case "LISTA_CARTELERAS":
            if ($oACL->isUserAuthorized($_SESSION['session_user'], 'delet', 'cart_lista')){
               if($tuplaSQL['ID_MATERIA_PERIODO_LECTIVO']==0)
                  $link_input="<input type='radio' name='id_cartelera' value='".$tuplaSQL['ID']."'>";
               else
                  $link_input="";
            }

            else
               $link_input="";

            ///Si $tuplaSQL['id_materia_periodo_lectivo']==NULL entonces es de tipo cartelera
            if($tuplaSQL['ID_MATERIA_PERIODO_LECTIVO']==0){
               if($tuplaSQL['CONTENIDO']!=NULL)
                  $link_vista = "<a href='?menu1op=submenu_agenda&submenuop=cart_lista&action=mostrar_cartelera&id_materia_periodo_lectivo=".$this->id_mpl."&id_cartelera=".$tuplaSQL['ID']."'>".$tuplaSQL['TITULO']."</a>";
               else
                  $link_vista = $tuplaSQL['TITULO'];
            }
            else{
               if($tuplaSQL['CONTENIDO']!=NULL)
                  $link_vista = "<a href='?menu1op=submenu_agenda&submenuop=cart_lista&action=mostrar_evento&id_materia_periodo_lectivo=".$this->id_mpl."&id_evento=".$tuplaSQL['ID']."'>".$tuplaSQL['TITULO']."</a>";
               else{
                  if($tuplaSQL['ID_CALIFICABLE']!=NULL)
                     $link_vista = "<a href='?menu1op=submenu_agenda&submenuop=cart_lista&action=mostrar_evento&id_materia_periodo_lectivo=".$this->id_mpl."&id_evento=".$tuplaSQL['ID']."'>".$tuplaSQL['TITULO']."</a>";
                  else
                     $link_vista = $tuplaSQL['TITULO'];
               }
            }

            $fecha_hora=explode(' ',$tuplaSQL['INICIO']);
            $fecha = explode('-',$fecha_hora[0]);
            $hora = explode(':',$fecha_hora[1]);
            $ini_t=mktime($hora[0], $hora[1], $hora[2], $fecha[1], $fecha[2], $fecha[0]);

            $fecha_hora=explode(' ',$tuplaSQL['FINAL']);
            $fecha = explode('-',$fecha_hora[0]);
            $hora = explode(':',$fecha_hora[1]);
            $fin_t=mktime($hora[0], $hora[1], $hora[2], $fecha[1], $fecha[2], $fecha[0]);

            return array("INPUT" => $link_input,
                         "LINK"  => $link_vista,
                         "FECHA_COMIENZO" => utf8_encode(strftime("%A, %e de %B %Y - %T",$ini_t)),
                         "FECHA_TERMINO"  => utf8_encode(strftime("%A, %e de %B %Y - %T",$fin_t)),
                         );

         default:
      }

      return array("INPUT" => $link_input,
                   "LINK"  => $link_vista,
                   "FECHA" => $ultimo_envio,
                   "HORA"  => $fecha_creacion,
                   );
   }

   ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
   ////////Funciones de Eliminacion
   ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
   function eliminar_cartelera($id_cartelera){
      $oACL=getACL();
      $oDB=$this->_db;

      if (!$oACL->isUserAuthorized($_SESSION['session_user'], 'delet', 'cart_lista')){
         $this->setMessage("Usted no está autorizado para realizar esta acción.");
         return FALSE;
      }

      // eliminar de la base de datos
      $sQuery="DELETE from ul_cartelera WHERE id_cartelera=$id_cartelera";
      $bValido=$oDB->genQuery($sQuery);
      if(!$bValido){
         $this->setMessage("Error al eliminar cartelera. Error: ".$oDB->errMsg);
         return FALSE;
      }


      return $bValido;
   }


   ////////////////////////////////////////////////////////////////////////////////////////////////////////////////

   function generar_cabecera_opciones($nombreFormulario){
      //Se debe mostrar los botones para modificar, eliminar e ingresar dependiendo de los privilegios del usuario
      $oACL=getACL();
      $sContenido="";
      $arr_botones=array();

      switch($nombreFormulario){

         case "LISTA_CARTELERAS":
            if ($oACL->isUserAuthorized($_SESSION['session_user'], 'delet', 'cart_lista'))
               $arr_botones[]="<input type='submit' name='eliminar_cartelera' value='Eliminar' onClick=\"return confirm('Está seguro que desea eliminar este cartelera?')\">&nbsp;&nbsp;";

            if ($oACL->isUserAuthorized($_SESSION['session_user'], 'creat', 'cart_lista'))
               $arr_botones[]="<input type='submit' name='crear_cartelera' value='Crear'>&nbsp;&nbsp;";
            break;
         case "LISTA_EVENTOS":
            break;

         default:
      }

      for($i=0;$i<count($arr_botones);$i++)
         $sContenido.=$arr_botones[$i];

      return $sContenido;
   }


}
?>
