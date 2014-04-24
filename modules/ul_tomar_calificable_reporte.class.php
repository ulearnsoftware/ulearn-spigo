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
// $Id: ul_tomar_calificable_reporte.class.php,v 1.1.1.1 2006/03/03 21:59:09 ainiguez Exp $

require_once ("lib/paloEntidad.class.php");
require_once ("lib/paloACL.class.php");


class ul_tomar_calificable_reporte extends PaloReporte{
   var $id_mpl;
   var $sBaseURL;

   function ul_tomar_calificable_reporte(&$oDB, &$oPlantillas, $sBaseURL,$id_materia_periodo_lectivo,$id_calificable)
   {
      global $config;
      $this -> PaloReporte($oDB, $oPlantillas);
      $oACL=getACL();
      setLocale(LC_TIME,$config->locale);

      $this->id_mpl=$id_materia_periodo_lectivo;
      $this->sBaseURL=$sBaseURL;
      $tabla_cabecera_calificable=$this->generar_cabecera_opciones("LISTA_CALIFICABLES");


      //////////// Verificacion de perfil de usuario para mostrar columnas con input

      $arrHEADER_CALIFICABLE=array("CALIFICABLE", "ESTATUS", "INICIO", "CIERRE","FECHA REALIZACIÓN","FECHA TERMINACIÓN");
      $arrROW_CALIFICABLE   =array(
                     array("{_DATA_TITULO}","ALIGN"=>"LEFT"),
                     array("{_DATA_ESTATUS}","ALIGN"=>"LEFT"),
                     array("{_DATA_FECHA_INICIO}","ALIGN"=>"RIGHT"),
                     array("{_DATA_FECHA_CIERRE}","ALIGN"=>"RIGHT"),
                     array("{_DATA_FECHA_REALIZACION}","ALIGN"=>"RIGHT"),
                     array("{_DATA_FECHA_TERMINACION}","ALIGN"=>"RIGHT"),
                     );

      // grupo al que pertenece el usuario
      $id_user=$oACL->getIdUser($_SESSION['session_user']);
      //$oACL->getMembership($id_alumno)

      if (!$this->definirReporte("LISTA_CALIFICABLES", array(
         //"DEBUG"=>true,
         "TITLE"         =>  "Listado de Calificables a Tomar<br>\n".
                              "<input type='hidden' name='id_materia_periodo_lectivo' value=$id_materia_periodo_lectivo>",
         "FILTRO"        =>  $tabla_cabecera_calificable,
         "PAGECHOICE"    =>  array(15,30,60),
         "DATA_COLS"     =>  array(
                                 "ID_ALUMNO_CALIFICABLE"       => "al_calf.id_alumno_calificable",
                                 "ID_CALIFICABLE"              => "al_calf.id_calificable",
                                 "FECHA_INICIO"                => "al_calf.fecha_inicio",
                                 "FECHA_CIERRE"                => "al_calf.fecha_cierre",
                                 "FECHA_REALIZACION"           => "al_calf.fecha_realizacion",
                                 "FECHA_TERMINACION"           => "al_calf.fecha_terminacion",
                                 "ESTATUS"                     => "al_calf.estatus",
                                 "TITULO"                      => "calf.titulo",
                             ),
         "PRIMARY_KEY"   =>  array("ID_ALUMNO_CALIFICABLE"),
         "FROM"          =>  "ul_alumno_calificable AS al_calf, ul_alumno_materia AS al_mat, ul_calificable AS calf, ul_alumno AS al",
         "CONST_WHERE"   =>  "al_calf.id_alumno_materia=al_mat.id AND al_calf.id_calificable=calf.id_calificable AND calf.id_materia_periodo_lectivo=$id_materia_periodo_lectivo AND al_mat.id_alumno=al.id AND al.id_acl_user=$id_user",
         "ORDERING"      =>  array(
                                 "DEFAULT"   =>  array("FECHA_INICIO", "FECHA_CIERRE"),
                             ),
         "BASE_URL"      =>  $sBaseURL,
         "HEADERS"       =>  $arrHEADER_CALIFICABLE,
         "ROW"           =>  $arrROW_CALIFICABLE
      ))) die ("ul_calificable_reporte: - al definir reporte LISTA_CALIFICABLES - ".$this->_msMensajeError);

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
      $insTpl->definirDirectorioPlantillas("calificable");
      $insTpl->assign("IMG_PATH", "skins/$config->skin/images");

      //echo "<pre>"; print_r($tuplaSQL); echo "</pre>";

      switch ($sNombreReporte) {
         case "LISTA_CALIFICABLES":
            $fecha_hora=explode(' ',$tuplaSQL['FECHA_INICIO']);

            $fecha = explode('-',$fecha_hora[0]);
            $hora = explode(':',$fecha_hora[1]);
            $ini_t=mktime($hora[0], $hora[1], $hora[2], $fecha[1], $fecha[2], $fecha[0]);

            $fecha_hora=explode(' ',$tuplaSQL['FECHA_CIERRE']);
            $fecha = explode('-',$fecha_hora[0]);
            $hora = explode(':',$fecha_hora[1]);
            $fin_t=mktime($hora[0], $hora[1], $hora[2], $fecha[1], $fecha[2], $fecha[0]);

            // Puede tomar el examen?
            $titulo=$tuplaSQL['TITULO'];
            if($ini_t<=time() && $fin_t>time())
               if(($tuplaSQL['ESTATUS']=="N" || $tuplaSQL['ESTATUS']=="V"))
                  $titulo="<a href='".$this->sBaseURL."&comenzar_calificable=yes&id_calificable=".$tuplaSQL['ID_CALIFICABLE']."&id_alumno_calificable=".$tuplaSQL['ID_ALUMNO_CALIFICABLE']."'>".$tuplaSQL['TITULO']."</a>";

            // estado del calificable
            switch($tuplaSQL['ESTATUS']){
            case "N":
               $estatus="No realizado";
               break;
            case "V":
               $estatus="Visto";
               break;
            case "T":
               $estatus="Terminado";
               break;
            case "A":
               $estatus="Anulado";
               break;
            default:
               $estatus="";
            }


            return array("TITULO" => $titulo,
                         "ESTATUS"  => $estatus,
                         "FECHA_INICIO" => $tuplaSQL['FECHA_INICIO'],
                         //"FECHA_INICIO" => utf8_encode(strftime("%A, %e de %B %Y - %T",$ini_t)),
                         "FECHA_CIERRE" => $tuplaSQL['FECHA_CIERRE'],
                         //"FECHA_CIERRE" => utf8_encode(strftime("%A, %e de %B %Y - %T",$fin_t)),
                         );
         default:
      }

      return array("TITULO" => $tuplaSQL['TITULO'],
                   "ESTATUS"  => $tuplaSQL['ESTATUS'],
                   "FECHA_INICIO" => $tuplaSQL['FECHA_INICIO'],
                   //"FECHA_INICIO" => utf8_encode(strftime("%A, %e de %B %Y - %T",$ini_t)),
                   "FECHA_CIERRE" => $tuplaSQL['FECHA_CIERRE'],
                   //"FECHA_CIERRE" => utf8_encode(strftime("%A, %e de %B %Y - %T",$fin_t)),
                   );
   }

   ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
   ////////Funciones de Eliminacion
   ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
   function eliminar_calificable($id_calificable){
      $oACL=getACL();
      $oDB=$this->_db;

      if (!$oACL->isUserAuthorized($_SESSION['session_user'], 'delet', 'calf_lista')){
         $this->setMessage("Usted no est? autorizado para realizar esta acci?n.");
         return FALSE;
      }

      // eliminar de la base de datos
      $sQuery="DELETE from ul_calificable WHERE id_calificable=$id_calificable";
      $bValido=$oDB->genQuery($sQuery);
      if(!$bValido){
         $this->setMessage("Error al eliminar calificable. Error: ".$oDB->errMsg);
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

         case "LISTA_CALIFICABLES":

               if ($oACL->isUserAuthorized($_SESSION['session_user'], 'creat', 'calf_lista'))
                  $arr_botones[]="<input type='submit' name='crear_calificable' value='Crear'>&nbsp;&nbsp;";

               if ($oACL->isUserAuthorized($_SESSION['session_user'], 'updat', 'calf_lista'))
                  $arr_botones[]="<input type='submit' name='modificar_calificable' value='Modificar'>&nbsp;&nbsp;";

               if ($oACL->isUserAuthorized($_SESSION['session_user'], 'updat', 'calf_lista'))
                  $arr_botones[]="<input type='submit' name='editar_calificable' value='Editar'>&nbsp;&nbsp;";

               if ($oACL->isUserAuthorized($_SESSION['session_user'], 'delet', 'calf_lista'))
                  $arr_botones[]="<input type='submit' name='eliminar_calificable' value='Eliminar' onClick=\"return confirm('Est? seguro que desea eliminar este calificable?')\">&nbsp;&nbsp;";
               break;
         default:
      }

      for($i=0;$i<count($arr_botones);$i++)
         $sContenido.=$arr_botones[$i];

      return $sContenido;
   }


}
?>
