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
// $Id: ul_configuracion.class.php,v 1.1.1.1 2006/03/03 21:59:09 ainiguez Exp $
if(isset($gsRutaBase)){
   require_once ($gsRutaBase."/lib/paloEntidad.class.php");
   require_once ($gsRutaBase."/lib/paloReporte.class.php");
}
else{
   require_once ("lib/paloEntidad.class.php");
   require_once ("lib/paloReporte.class.php");
}


class ul_configuracion extends PaloEntidad {

    var $configuracion;
    var $escala;

    /**
     *  Lee la configuracion de todos los parametros
     */
    function leeConfiguracion(&$oDB) {
        $this->configuracion = array();
	$this->_db=$oDB;

        $sQuery = "SELECT grupo, parametro, valor FROM ul_configuracion ORDER BY grupo, parametro";
        $arr_datos =& $oDB->fetchTable($sQuery);

        foreach ($arr_datos as $tupla) {
            $grupo = strtoupper($tupla[0]);
            $clave = strtoupper($tupla[1]);

            if (!isset($this->configuracion[$grupo]))
                $this->configuracion[$grupo] = array();
            $this->configuracion[$grupo][$clave] = $tupla[2];
        }
    }

    /**
     * Retorna el valor de una configuracion del usuario
     * Si el valor no esta establecido retorna <$default>
     */
    function getProperty($grupo, $key, $default='') {
        $grupo = strtoupper($grupo); $key = strtoupper($key);
        if (isset($this->configuracion[$grupo][$key])) return $this->configuracion[$grupo][$key]; else return $default;
    }

    /**
     * Devuelve la parte del query para mostrar el nombre apellido del alumno/docente/represante
     * @param  <$table_prefix> prefijo para poner adelante de el nombre de las columnas
     */
    function getFormatoNombre($table_prefix='') {
        $formato = $this->getProperty('Presentacion', 'Nombre');
        if ($table_prefix) $table_prefix = "$table_prefix.";
        switch ($formato) {
        case 'Nombre-Apellido':
            return "CONCAT({$table_prefix}nombre,', ',{$table_prefix}apellido) as nomape";

        case 'Apellido-Nombre':
        default:
            return "CONCAT({$table_prefix}apellido,', ',{$table_prefix}nombre) as nomape";
        }
    }

    /**
     * Devuelve el combo de horario definido en la configuracion
     */
    function getArrayHorario() {
        $hora_ini = $this->getProperty("Horario","HoraInicial");
        $hora_fin = $this->getProperty("Horario","HoraFinal");
        $escala_m = $this->getProperty("Horario","Escala");
        $this->escala=$escala_m;
        return $this->getArrayTime();
    }

    /**
     * Devuelve un array con el tiempo
     *    Valida que no se quede un un loop infinito: maximo 300 iteraciones
     *    por si se env�n parametros errneos
     * @param $hi HoraInicial
     * @param $hf HoraFinal
     * @param $escala Escala en minutos
     */
    function getArrayTime() {
        /*$hm = explode(':', $hi);
        $escala *= 60;
        $time = array();
        $hora = mktime($hm[0], $hm[1]);
        $exit = 0;

        while ($hi != $hf && $exit++ < 300) {
            $time[] = $hi = date('H:i', $hora);
            $hora += $escala;
        }*/
        $db=$this->_db;

	//Debo retornar los valores que aparecen en los valores de hora_ini y hora_fin de la tabla sa_horario
        $sQuery="select distinct hora_ini from sa_horario union select hora_fin from sa_horario order by 1";
        $result=$db->fetchTable($sQuery);
        $time=array();

	  if(is_array($result) && count($result)>0){
              foreach($result as $fila){
                 $time[]=$fila[0];
	      }
	  }
        return $time;
    }

    /**
     * Constructor
     */
    function ul_configuracion(&$oDB, $oPlantillas=null)
    {
        //Lee la configuracion de todos los parametros
        $this->leeConfiguracion($oDB);

        if (!is_null($oPlantillas)) {
            $defTabla = PaloEntidad::describirTabla($oDB, "ul_configuracion");
            $defTabla["campos"]["id"]["DESC"]     = "Id ";
            $defTabla["campos"]["grupo"]["DESC"]  = "Grupo";
            $defTabla["campos"]["parametro"]["DESC"] = "Par�etro";
            $defTabla["campos"]["valor"]["DESC"]     = "Valor de este par�etro";
            $defTabla["campos"]["descripcion"]["DESC"] = "Descripcin";

            // Crear clase base de PaloEntidad
            $this->PaloEntidad($oDB, $oPlantillas, $defTabla);

            // Construir todos los formularios requeridos para insertar un nuevo registro en sa_carrera
            if (!$this->definirFormulario("UPDATE", "CONFIGURACION",
            array(
                "title"     =>  "Modificaci&oacute;n de Preferencia de Usuario<br>".
                                "<a href='?menu1op=submenu_prefer'>&laquo;&nbsp;Regresar</a>&nbsp;",

                "submit"    =>  array("name"   =>  "submit_config", "value"  =>  "Guardar cambios",),
                "fields"    =>  array(
                    array(
                        "tag"       =>  "Grupo:",
                        "_field"    =>  "grupo",
                        "type"      =>  "label",
                    ),
                    array(
                        "tag"       =>  "Par&aacute;metro:",
                        "_field"    =>  "parametro",
                        "type"      =>  "label",
                    ),
                    array(
                        "tag"       =>  "Descripci&oacute;n:",
                        "_field"    =>  "descripcion",
                        "type"      =>  "label",
                    ),
                    array(
                        "tag"       =>  "Valor:",
                        "title"     =>  "Ingrese el valor de este par&aacute;metro",
                        "_field"    =>  "valor",
                        "size"        =>  40,
                        "maxlength"    =>  255,
                        "_empty"    =>  TRUE,
                    ),
            ),
            ))) die ("Al definir formulario UPDATE - ".$this->_msMensajeError);
        }
    }

     function event_validarValoresFormularioUpdate($sNombreFormulario, $prevPK, $formVars)
    {   $oACL=getACL();
        $bValido = TRUE;

        switch ($sNombreFormulario) {
           case "CONFIGURACION":

	       if (!$oACL->isUserAuthorized($_SESSION['session_user'], 'updat', 'cfg_opciones')){
                 $this->setMessage("Usted no está autorizado para realizar esta acción");
	         return FALSE;
	       }

	       return $bValido;
	       break;
	   default:
	}
    }
}

/**
 * Muestra las preferencias de Usuario
 */
class ul_configuracion_reporte extends PaloReporte
{
    function ul_configuracion_reporte(&$oDB, &$oPlantillas, $sBaseURL)
    {
        $this->PaloReporte($oDB, $oPlantillas);

        //Definir reporte
        //  id  grupo  parametro  valor  lectura_flg  descripcion
        if (!$this->definirReporte("LISTA", array(
            "TITLE"         =>  "Preferencias de Usuario<br>\n",
            "DATA_COLS"     =>
                                array(
                                    "ID"=>"configuracion.id",
                                    "GRUPO"=>"configuracion.grupo",
                                    "PARAMETRO"=>"configuracion.parametro",
                                    "VALOR"=>"configuracion.valor",
                                    "LECTURA_FLG"=>"configuracion.lectura_flg",
                                    "DESCRIPCION"=>"configuracion.descripcion",
                                ),
            "PRIMARY_KEY"   =>  array("ID"),
            "FROM"          =>  "ul_configuracion configuracion",
            "CONST_WHERE"   =>  "1",
            "PAGECHOICE"    =>  array(15,30,60),
            "ORDERING"      =>  array( "DEFAULT"=>  array("GRUPO","PARAMETRO"), 'PARAMETRO'=> array("PARAMETRO","GRUPO")),
            "BASE_URL"      =>  $sBaseURL,
            "HEADERS"       =>  array(
                                    array('Grupo','DEFAULT'),
                                    array('Parámetro','PARAMETRO'),
                                    'Descripci&oacute;n',
                                    'Valor',
                                    'Opciones'
                                ),
            "ROW"            =>
                                array(
                                    "{_DATA_GRUPO}",
                                    "{_DATA_PARAMETRO}",
                                    "{_DATA_DESCRIPCION}",
                                    array("{_DATA_VALOR}",7),
                                    "{_DATA_MODIFICAR}",
                                ),
        ))) die ("Al definir reporte - ".$this->_msMensajeError);
    }

    /**
    * Procedimiento que muestra y agrega las secciones extras del listado
    * como una fila adicional de datos.
    *
    * @param string $sNombreReporte Nombre del reporte para el que se proveen las columnas
    * @param array  $tuplaSQL       Tupla con los valores a usar para la fila actual
    *
    * @return array    Valores a agregar a la tupla existente de SQL
    */
    function event_proveerCampos($sNombreReporte, $tuplaSQL)
    {   $oACL=getACL();

        switch ($sNombreReporte) {

	case "LISTA":
             if (!$oACL->isUserAuthorized($_SESSION['session_user'], 'updat', 'cfg_opciones')){
                  if($tuplaSQL["LECTURA_FLG"] == 0)
                       $link_modificar="<a href='?action=modificar&id_configuracion=".$tuplaSQL["ID"]."'>Modificar</a>&nbsp;";
		  else
		       $link_modificar="Sólo-Lectura";
             }
	     else
	        $link_modificar="";

            return array("MODIFICAR"=>$link_modificar,
	                 "CLASS"=>'cell_normal');

        default:
            return array();
        }
    }


}

?>
