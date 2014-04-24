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
// $Id: ul_calificable_reporte.class.php,v 1.1.1.1 2006/03/03 21:59:09 ainiguez Exp $

require_once ("lib/paloEntidad.class.php");
require_once ("lib/paloACL.class.php");
require_once ("modules/datetime.class.php");


class ul_calificable_reporte extends PaloReporte{
   var $id_mpl;

   function ul_calificable_reporte(&$oDB, &$oPlantillas, $sBaseURL,$id_materia_periodo_lectivo,$id_calificable)
   {
      global $config;
      $this -> PaloReporte($oDB, $oPlantillas);
      $oACL=getACL();
      setLocale(LC_TIME,$config->locale);

      $this->id_mpl=$id_materia_periodo_lectivo;
      $tabla_cabecera_calificable=$this->generar_cabecera_opciones("LISTA_CALIFICABLES");


      //////////// Verificacion de perfil de usuario para mostrar columnas con input

      if ($oACL->isUserAuthorized($_SESSION['session_user'], 'delet', 'calf_lista')){
         $arrHEADER_CALIFICABLE=array("",
                                    "CODIGO",
                                    "PARCIAL",
                                    "SUBPARCIAL",
                                    "CALIFICABLE",
                                    "Nº PREG",
                                    "DURACION",
                                    "PONDERACION",
                                    "FECHA DE COMIENZO",
                                    "FECHA DE CIERRE");
         $arrROW_CALIFICABLE   =array(
                        array("{_DATA_INPUT}",'ALIGN'=>'CENTER'),
                        array("{_DATA_CODIGO}","ALIGN"=>"LEFT"),
                        array("{_DATA_PARCIAL}",'ALIGN'=>'CENTER'),
                        array("{_DATA_SUBPARCIAL}",'ALIGN'=>'CENTER'),
                        array("{_DATA_LINK}","ALIGN"=>"LEFT"),
                        "{_DATA_N_PREGUNTAS}",
                        array("{_DATA_DURACION}","ALIGN"=>"RIGHT"),
                        array("{_DATA_PONDERACION}","ALIGN"=>"RIGHT"),
                        array("{_DATA_FECHA_COMIENZO}","ALIGN"=>"RIGHT"),
                        array("{_DATA_FECHA_CIERRE}","ALIGN"=>"RIGHT"),
                        );
      }
      else{
         $arrHEADER_CALIFICABLE=array("CODIGO", "PARCIAL","SUBPARCIAL","CALIFICABLE",
                                      "Nº PREG", "DURACION", "PONDERACION","FECHA DE COMIENZO","FECHA DE CIERRE");
         $arrROW_CALIFICABLE   =array(
                        array("{_DATA_CODIGO}","ALIGN"=>"LEFT"),
                        array("{_DATA_PARCIAL}",'ALIGN'=>'CENTER'),
                        array("{_DATA_SUBPARCIAL}",'ALIGN'=>'CENTER'),
                        array("{_DATA_LINK}","ALIGN"=>"LEFT"),
                        "{_DATA_N_PREGUNTAS}",
                        array("{_DATA_DURACION}","ALIGN"=>"RIGHT"),
                        array("{_DATA_PONDERACION}","ALIGN"=>"RIGHT"),
                        array("{_DATA_FECHA_COMIENZO}","ALIGN"=>"RIGHT"),
                        array("{_DATA_FECHA_CIERRE}","ALIGN"=>"RIGHT"),
                        );
      }

      // grupo al que pertenece el usuario
      switch($oACL->getMembership($oACL->getIdUser($_SESSION['session_user']))){
         case "alumno":
                $const_where="c.id_materia_periodo_lectivo=$id_materia_periodo_lectivo AND c.fecha_cierre>=NOW()";
                break;
         default:
                $const_where="c.id_materia_periodo_lectivo=$id_materia_periodo_lectivo";
      }

      if (!$this->definirReporte("LISTA_CALIFICABLES", array(
         //"DEBUG"=>true,
         "TITLE"         =>  "Listado de Calificables<br>\n".
                              "<input type='hidden' name='id_materia_periodo_lectivo' value=$id_materia_periodo_lectivo>",
         "FILTRO"        =>  $tabla_cabecera_calificable,
         "PAGECHOICE"    =>  array(15,30,60),
         "DATA_COLS"     =>  array(
                                 "ID_CALIFICABLE"              => "c.id_calificable",
                                 "ID_SUBPARCIAL"              => "c.id_subparcial",
                                 "CODIGO"                      => "c.codigo",
                                 "TITULO"                      => "c.titulo",
                                 "BASE"                        => "c.base",
                                 "PONDERACION"                 => "c.ponderacion",
                                 //"NOTA"                        => "nota",
                                 "DURACION"                    => "c.duracion",
                                 "DISPONIBILIDAD"              => "c.disponibilidad",
                                 "FECHA_INICIO"                => "c.fecha_inicio",
                                 "FECHA_CREACION"              => "c.fecha_creacion",
                                 "FECHA_CIERRE"                => "c.fecha_cierre",
                                 "ESTATUS"                     => "c.estatus",
                                 "ID_MATERIA_PERIODO_LECTIVO"  => "c.id_materia_periodo_lectivo",
                                 "ID_PARCIAL"                  => "p.id",
                                 "PARCIAL"                     => "p.nombre",
                                 "ID_SUBPARCIAL"               => "sp.id",
                                 "SUBPARCIAL"                  => "sp.nombre",
                                 "N_PREGUNTAS"                 => "count(pre.id_pregunta)",
                             ),
         "PRIMARY_KEY"   =>  array("ID_CALIFICABLE"),
         "FROM"          =>  "(ul_calificable c, ul_parcial p, ul_subparcial sp) ".
                              "LEFT JOIN ul_grupo_pregunta gp ON gp.id_calificable=c.id_calificable ".
                              "LEFT JOIN ul_pregunta pre ON pre.id_grupo_pregunta=gp.id_grupo_pregunta ",
         "CONST_WHERE"   =>  $const_where." and c.id_subparcial=sp.id and sp.id_parcial=p.id ".
                              "GROUP BY 1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16",
         "ORDERING"      =>  array(
                                 "DEFAULT"   =>  array("ID_PARCIAL","ID_SUBPARCIAL","FECHA_INICIO", "FECHA_CIERRE"),
                             ),
         "BASE_URL"      =>  $sBaseURL,
         "HEADERS"       =>  $arrHEADER_CALIFICABLE,
         "ROW"           =>  $arrROW_CALIFICABLE,
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

      /*
         echo "<pre>";
         print_r($tuplaSQL);
         echo "</pre>";
      */
      switch ($sNombreReporte) {
         case "LISTA_CALIFICABLES":
            if ($oACL->isUserAuthorized($_SESSION['session_user'], 'delet', 'calf_lista'))
               $link_input="<input type='radio' name='id_calificable' value='".$tuplaSQL['ID_CALIFICABLE']."'>";
            else
               $link_input="";

            $link_codigo = $tuplaSQL['CODIGO'];
            $link_titulo = $tuplaSQL['TITULO'];
            $link_duracion = $tuplaSQL['DURACION'];
            $link_ponderacion = $tuplaSQL['PONDERACION'];

            // funcion creada en datetime
            $ini_t = conv_datetime($tuplaSQL['FECHA_INICIO']);
            $fin_t = conv_datetime($tuplaSQL['FECHA_CIERRE']);

            ////Se debe obtener el numero de preguntas del calificable

            return array("INPUT" => $link_input,
                         "CODIGO"  => $link_codigo,
                         "LINK"  => $link_titulo,
                         "DURACION"  => $link_duracion,
                         "PONDERACION"  => $link_ponderacion,
                         "FECHA_COMIENZO" => utf8_encode(strftime("%A, %e de %B %Y - %T",$ini_t)),
                         "FECHA_CIERRE" => utf8_encode(strftime("%A, %e de %B %Y - %T",$fin_t)),
                         );
         default:
      }

      return array("INPUT" => $link_input,
                   "CODIGO"  => $link_codigo,
                   "LINK"  => $link_titulo,
                   "DURACION"  => $link_duracion,
                   "PONDERACION"  => $link_ponderacion,
                   "FECHA_COMIENZO" => utf8_encode(strftime("%A, %e de %B %Y - %T",$ini_t)),
                   "FECHA_CIERRE" => utf8_encode(strftime("%A, %e de %B %Y - %T",$fin_t)),
                   );
   }

   ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
   ////////Funciones de Eliminacion
   ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
   function eliminar_calificable($id_calificable){
      $oACL=getACL();
      $oDB=$this->_db;

      if (!$oACL->isUserAuthorized($_SESSION['session_user'], 'delet', 'calf_lista')){
         $this->setMessage("Usted no está autorizado para realizar esta acción.");
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
                  $arr_botones[]="<input type='submit' name='eliminar_calificable' value='Eliminar' onClick=\"return confirm('Está seguro que desea eliminar este calificable?')\">&nbsp;&nbsp;";
               break;
         default:
      }

      for($i=0;$i<count($arr_botones);$i++)
         $sContenido.=$arr_botones[$i];

      return $sContenido;
   }


}
?>
