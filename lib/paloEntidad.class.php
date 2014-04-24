<?php
/* vim: set expandtab tabstop=4 softtabstop=4 shiftwidth=4: */
// Codificación: UTF-8 (el siguiente texto es chino: 給懶惰使用者)
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
// | Autores: Alex Villacis <a_villacis@palosanto.com>                    |
// +----------------------------------------------------------------------+
//
// $Id: paloEntidad.class.php,v 1.1.1.1 2006/03/03 21:59:09 ainiguez Exp $

if (isset($gsRutaBase))
     require_once("$gsRutaBase/lib/paloTemplateFuncionesFiltro.php");
else require_once("lib/paloTemplateFuncionesFiltro.php");

function remover_comillas($s) { ereg("^'(.*)'$", $s, $r); return $r[1]; }

/* Clase que provee utilidades básicas de listado, inserción, modificación y
 * eliminación para una tabla arbitraria de la base de datos a través de
 * plantillas HTML
 */
class PaloEntidad
{
    var $_db;               // Instancia de clase paloDB usada para las operaciones DB
    var $_tpl;              // Instancia de clase paloTemplate usada para construir plantillas
    var $_infoTabla;        // Información sobre la tabla a manipular a través de la clase
    var $_msMensajeError;   // Mensaje de error sobre la última operación sobre la tabla
    var $_listaFormularios; // Lista de formularios de modificación sobre la tabla

    var $_insFunc;          // Instancia de la clase funcionesFiltro utilizada para almacenar funciones de plantillas

    /*
        La información contenida en $_infoTabla debe de ser de la siguiente manera:
        $_infoTabla = array(
            "tabla"     =>  "nombretabla",
            "campos"    =>  array(
                "campo1"    => array(
                    "DESC"      =>  "Clave primaria de campo 1",
                    "PRIMARY"   =>  TRUE,
                    "AUTOINC"   =>  TRUE,
                    "NULL"      =>  FALSE,
                    "SQLTYPE"   =>  "int",
                    "SQLATTR"   =>  "unsigned",
                    "SQLLEN"    =>  "11",
                    "REGEXP"    =>  "[[:digit:]]+",
                ),
                "campo2"    =>  array(...),
            ),
        );
        campo1..campoN son las columnas de la tabla
        DESC    es la descripción del propósito de la columna, y se usa en mensajes de error.
        PRIMARY debe asignarse a TRUE si la columna pertenece a la clave primaria de la tabla
        AUTOINC debe asignarse a TRUE si la columna recibe un valor de autoincremento
        NULL    debe asignarse a TRUE si la columna puede recibir NULL. Este valor y PRIMARY
            no deben asignarse al mismo tiempo. Esta condición se verifica en el momento de
            la construcción de la entidad.
        SQLTYPE es uno de los valores "int", "char", "varchar", "blob", "real", "decimal", "date", "time", "datetime"
        SQLATTR puede ser "unsigned"
        SQLLEN  es la especificación de la longitud del valor de (var)char o int
            bit         ->  tinyint 1
            tinyint     ->  tinyint 4 (3 + signo)
            smallint    ->  smallint 6 (5 + signo)
            mediumint   ->  mediumint 9 (8 + signo)
            int         ->  int 11
            bigint      ->  bigint 20
            decimal     ->  decimal 10,0    OJO NO HAY DECIMALES
        REGEXP  expresión regular que filtra los valores posibles de inserción. Esta expresión
            es la expresión más general que debe cumplir el campo. En otros lugares, se pueden
            especificar expresiones más estrictas, pero no más generales. Esta expresión se
            evalua siempre ANTES de evaluar las expresiones más específicas.
        SQLREGEXP filtro más general que REGEXP, para uso interno del código. Los siguientes
            valores se usan para los distintos tipos:
                (var)char/blob  (ninguna)
                int             [+-]?[[:digit:]]{1,n}
                int unsigned    [[:digit:]]{1,n}
                real/decimal    [+-]?[[:digit:]]*\.[[:digit:]]*
                date            [[:digit:]]{4}-[[:digit:]]{2}-[[:digit:]]{2}
                time            [[:digit:]]{2}:[[:digit:]]{2}:[[:digit:]]{2}
                datetime        [[:digit:]]{4}-[[:digit:]]{2}-[[:digit:]]{2} [[:digit:]]{2}:[[:digit:]]{2}:[[:digit:]]{2}
            El valor de n en los regexp de int se construye a partir de SQLLEN

            Esta expresión se asigna siempre, sobreescribiendo cualquier valor proporcionado.
        ENUM    array() de valores que puede tomar el campo, y que se valida luego de los regexp.
            Si no se especifica, se permite cualquier valor que calce con el regexp

        El orden de los campos en la especificación no es significativo.
    */

    function & getMessage()
    {
        return $this->_msMensajeError;
    }
    function & getDB()
    {
        return $this->_db;
    }
    function setMessage($s)
    {
        $this->_msMensajeError = $s;
    }

    /**
     * Constructor que recibe la especificación del objeto de base de datos, del
     * conjunto de plantillas a usar, y de la especificación de la tabla a manipular.
     *
     * @param object $oDB Instancia de clase paloDB que implementa la conexión a DB
     * @param object $oPlantillas Instancia de clase paloTemplate que contiene plantillas
     * @param array $infoTabla especificación de la tabla de la base de datos
     *
     * @return void
     */
    function PaloEntidad(&$oDB, &$oPlantillas, $infoTabla = NULL)
    {
        $this->_msMensajeError = "";

        // Validar que los parámetros son correctos
        if (!is_a($oDB, "paloDB")) die("PaloEntidad::PaloEntidad() - Objeto \$oDB no es de clase paloDB");
        if (!is_a($oPlantillas, "PaloTemplate")) die("PaloEntidad::PaloEntidad() - Objeto \$oPlantillas no es de clase PaloTemplate");

        // Completar la información de la tabla con los regexp y las longitudes
        $this->_db =& $oDB;
        $this->_tpl =& $oPlantillas;
        $this->_infoTabla = $infoTabla;
        if (!is_null($infoTabla)) {
            if (!$this->_privado_validarInfoTabla($infoTabla)) die("PaloEntidad::PaloEntidad() - $this->_msMensajeError");
            $this->_privado_completarInfoTabla();
        }
        $this->_listaFormularios = array(
            "INSERT"    =>  array(),
            "UPDATE"    =>  array(),
            );

        // Código movido de paloEntidad2
        $this->_insFunc = new funcionesFiltro;
    }

    /**
     * Procedimiento para depuración que construye una sentencia CREATE TABLE de SQL con la
     * información contenida de la tabla.
     *
     * @return string   Sentencia SQL de creación de la tabla
     */
    function generar_CREATE()
    {
        if (is_null($this->_infoTabla)) die("paloEntidad::generar_CREATE() - no existe info de tabla");
        $sSpecTabla = "";
        $sClavePK = "";

        // Agregar cada una de las especificaciones de los campos
        foreach ($this->_infoTabla["campos"] as $sNombreCampo => $infoCampo) {
            $sFiltroCampo = "'^".$infoCampo["SQLREGEXP"]."$'";
            if (isset($infoCampo["REGEXP"])) $sFiltroCampo .= ", '^".$infoCampo["REGEXP"]."$'";

            if ($infoCampo["PRIMARY"]) {
                if ($sClavePK != "") $sClavePK .= ", ";
                $sClavePK .= $sNombreCampo;
            }
            $sSpecCampo =
                "    /**\n".
                "     * Proposito: ".$infoCampo["DESC"]."\n".
                "     * Filtro(s) de campo: $sFiltroCampo\n".
                (isset($infoCampo["ENUM"]) ?
                "     * Valores aceptados: '".join("', '", $infoCampo["ENUM"])."'\n" :
                "").
                "     */\n".
                "    $sNombreCampo ".$this->_privado_construirTipoSQL($infoCampo);
            if (!$infoCampo["NULL"]) $sSpecCampo .= " NOT NULL";
            if ($infoCampo["AUTOINC"]) $sSpecCampo .= " AUTO_INCREMENT";

            $sSpecTabla .= "$sSpecCampo,\n\n";
        }
        $sClavePK = "    PRIMARY KEY ($sClavePK)\n";
        $sSpecTabla .= $sClavePK;
        return "CREATE TABLE IF NOT EXISTS ".$this->_infoTabla["tabla"]."\n(\n$sSpecTabla);\n";
    }

    /**
     * Procedimiento que valida la información de la tabla para verificar que es consistente.
     */
    function _privado_validarInfoTabla($infoTabla)
    {
        $bValido = FALSE;
        $this->_msMensajeError = "";

        // Verificar que realmente la información es un arreglo
        if (!is_array($infoTabla)) {
            $this->_msMensajeError = "informacion de tabla no es un arreglo";
        } else if (!(isset($infoTabla["tabla"]) && $infoTabla["tabla"] != "")) {
            $this->_msMensajeError = "informacion de tabla no contiene clave 'tabla'";
        } else if (!ereg("^[[:alnum:]_.]+$", $infoTabla["tabla"])) {
            $this->_msMensajeError = "informacion de tabla contiene 'tabla' que no es nombre apropiado de tabla SQL";
        } else if (!isset($infoTabla["campos"])) {
            $this->_msMensajeError = "informacion de tabla no contiene lista 'campos'";
        } else if (!is_array($infoTabla["campos"])) {
            $this->_msMensajeError = "informacion de tabla contiene 'campos' que no es un arreglo";
        } else if (count($infoTabla["campos"]) <= 0) {
            $this->_msMensajeError = "informacion de tabla debe tener al menos un campo en 'campos'";
        } else {
            $bValido = TRUE;
            $iNumCamposPK = 0;  // Número de campos en la clave primaria

            // Revisar todos los campos de la tabla para verificar si son válidos
            foreach ($infoTabla["campos"] as $sNombreCampo => $infoCampo) {
                // Revisar el nombre del campo para verificar que es válido
                if ($bValido && !ereg("^[[:alnum:]_]+$", $sNombreCampo)) {
                    $this->_msMensajeError = "nombre de campo '$sNombreCampo' no es adecuado";
                    $bValido = FALSE;
                }
                // Revisar la presencia de determinados atributos
                if ($bValido && !isset($infoCampo["SQLTYPE"])) {
                    $this->_msMensajeError = "campo '$sNombreCampo' - SQLTYPE no especificado";
                    $bValido = FALSE;
                }

                // Revisar la validez individual de cada atributo
                if ($bValido) foreach ($infoCampo as $sNombreAttr => $sValorAttr) {
                    switch ($sNombreAttr) {
                    case "DESC":
                    case "AUTOINC":
                    case "NULL":
                    case "REGEXP":
                        break;
                    case "SQLTYPE":
                        if (!in_array(strtoupper($sValorAttr), array("TINYINT", "BOOL", "BIT", "BOOLEAN",
                            "SMALLINT", "MEDIUMINT", "INT", "INTEGER", "BIGINT", "FLOAT", "DOUBLE",
                            "DECIMAL", "DATE", "TIME", "DATETIME", "CHAR", "VARCHAR", "BLOB"))) {
                            $this->_msMensajeError = "campo $sNombreCampo - tipo de dato SQL desconocido '$sValorAttr'";
                            $bValido = FALSE;
                        }
                        if ($bValido && in_array(strtoupper($infoCampo["SQLTYPE"]), array("CHAR", "VARCHAR"))) {
                            if (!isset($infoCampo["SQLLEN"])) {
                                $this->_msMensajeError = "campo $sNombreCampo - tipo de dato SQL '".
                                    $infoCampo["SQLTYPE"]."' requiere atributo SQLLEN";
                                $bValido = FALSE;
                            }
                        }
                        if ($bValido && !in_array(strtoupper($infoCampo["SQLTYPE"]), array("TINYINT", "SMALLINT", "MEDIUMINT", "INT", "INTEGER", "BIGINT"))) {
                            if (isset($infoCampo["AUTOINC"])) {
                                $this->_msMensajeError = "campo $sNombreCampo - tipo de dato SQL '".
                                    $infoCampo["SQLTYPE"]."' no soporta atributo AUTOINC";
                                $bValido = FALSE;
                            }
                        }
                        break;
                    case "SQLATTR":
                        // El único atributo soportado es UNSIGNED
                        if (strtoupper($sValorAttr) == "UNSIGNED") {
                            if (in_array(strtoupper($infoCampo["SQLTYPE"]),
                                array("DATE", "TIME", "DATETIME", "CHAR", "VARCHAR", "BLOB"))) {
                                $this->_msMensajeError = "campo $sNombreCampo - tipo de dato SQL '".
                                    $infoCampo["SQLTYPE"]."' no soporta atributo UNSIGNED";
                                $bValido = FALSE;
                            }
                        } else {
                            $this->_msMensajeError = "campo $sNombreCampo - atributo desconocido '$sValorAttr'";
                            $bValido = FALSE;
                        }
                        break;
                    case "SQLLEN":
                        if (in_array(strtoupper($infoCampo["SQLTYPE"]),
                            array("TINYINT", "SMALLINT", "MEDIUMINT", "INT", "INTEGER", "BIGINT",
                            "CHAR", "VARCHAR"))) {
                            // Tipos de longitud simple
                            if (!ereg("^[[:digit:]]{1,3}$", $sValorAttr)) {
                                $this->_msMensajeError = "campo $sNombreCampo - '$sValorAttr' no es una longitud válida para ".$infoCampo["SQLTYPE"];
                                $bValido = FALSE;
                            }
                        } else if (in_array(strtoupper($infoCampo["SQLTYPE"]),
                            array("FLOAT", "DOUBLE", "DECIMAL"))) {
                            // Tipos de longitud con decimales opcionales
                            if (!ereg("^[[:digit:]]{1,4}(,[[:digit:]]+)?$", $sValorAttr)) {
                                $this->_msMensajeError = "campo $sNombreCampo - '$sValorAttr' no es una precisión válida para ".$infoCampo["SQLTYPE"];
                                $bValido = FALSE;
                            }
                        } else {
                            // Tipos sin longitud
                            $this->_msMensajeError = "campo $sNombreCampo - tipo de dato SQL '".
                                $infoCampo["SQLTYPE"]."' no soporta atributo SQLLEN";
                            $bValido = FALSE;
                        }
                        break;
                    case "ENUM":
                        if (!is_array($sValorAttr)) {
                            $this->_msMensajeError = "campo $sNombreCampo - enumeración no es un arreglo";
                            $bValido = FALSE;
                        } else if (count($sValorAttr) < 1) {
                            $this->_msMensajeError = "campo $sNombreCampo - enumeración debe tener al menos un valor";
                            $bValido = FALSE;
                        }
                        break;
                    case "PRIMARY":
                        // Llevar la cuenta de los campos de clave primaria
                        if ($sValorAttr) $iNumCamposPK++;
                        break;
                    case "SQLREGEXP":
                        $this->_msMensajeError = "campo $sNombreCampo - atributo SQLREGEXP es para uso interno unicamente";
                        $bValido = FALSE;
                        break;
                    default:
                        $this->_msMensajeError = "campo $sNombreCampo - atributo de campo desconocido $sNombreAttr";
                        $bValido = FALSE;
                        break;
                    }
                }
                if (!$bValido) break;

                // Revisar si el campo tiene un valor de enumeración. Si lo tiene, se revisa
                // que todos los valores de la enumeración pasan la validación SQLREGEXP y REGEXP
                if ($bValido && isset($infoCampo["ENUM"])) {

                    // Verificación de dato base de SQL
                    $sRegExp = $this->_privado_construirRegExp($infoCampo);
                    if (!is_null($sRegExp)) {
                        foreach ($infoCampo["ENUM"] as $sValor) {
                            if (!ereg("^$sRegExp$", "$sValor")) {
                                $this->_msMensajeError =
                                    "campo $sNombreCampo - valor de enumeracion '$sValor' ".
                                    "no cumple filtro de ".strtoupper($infoCampo["SQLTYPE"]).
                                    " '$sRegExp'";
                                $bValido = FALSE;
                            }
                            if (!$bValido) break;
                        }
                    }

                    // Verificación de expresión regular de usuario
                    if (isset($infoCampo["REGEXP"])) {
                        foreach ($infoCampo["ENUM"] as $sValor) {
                            if (!ereg("^".$infoCampo["REGEXP"]."$", "$sValor")) {
                                $this->_msMensajeError =
                                    "campo $sNombreCampo - valor de enumeracion '$sValor' ".
                                    "no cumple filtro requerido '".$infoCampo["REGEXP"]."'";
                                $bValido = FALSE;
                            }
                            if (!$bValido) break;
                        }
                    }
                }
            }

            // Verificar que la expresión tiene al menos una columna de clave primaria
            if ($bValido && $iNumCamposPK == 0) {
                $this->_msMensajeError = "tabla requiere la especificacion de al menos una columna de clave primaria";
                $bValido = FALSE;
            }
        }

        return $bValido;
    }

    /**
     * Procedimiento que completa con valores por omisión la información no disponible en
     * la especificación de la tabla.
     */
    function _privado_completarInfoTabla()
    {
        foreach (array_keys($this->_infoTabla["campos"]) as $sNombreCampo) {
            $infoCampo =& $this->_infoTabla["campos"][$sNombreCampo];

            // Poner todos los atributos relevantes en mayúsculas
            if (!isset($infoCampo["SQLATTR"])) $infoCampo["SQLATTR"] = "";
            $infoCampo["SQLTYPE"] = strtoupper($infoCampo["SQLTYPE"]);
            $infoCampo["SQLATTR"] = strtoupper($infoCampo["SQLATTR"]);

            // Normalizar la longitud del tipo de dato de la tabla
            if (in_array($infoCampo["SQLTYPE"], array("TINYINT", "BOOL", "BIT",
                "BOOLEAN", "SMALLINT", "MEDIUMINT", "INT", "INTEGER", "BIGINT"))) {

                // Longitud entera del valor SQL
                $infoCampo["SQLLEN"] = (int)$this->_privado_validarLongitudSQLINT(
                    $infoCampo["SQLTYPE"], isset($infoCampo["SQLLEN"]) ? $infoCampo["SQLLEN"] : NULL);
            }
            if (in_array($infoCampo["SQLTYPE"], array("CHAR", "VARCHAR"))) {
                // Longitud de cadena SQL
                $infoCampo["SQLLEN"] = ($infoCampo["SQLLEN"] < 255) ? (int)$infoCampo["SQLLEN"] : 255;
            }
            if (in_array($infoCampo["SQLTYPE"], array("BLOB", "DATE", "TIME", "DATETIME"))) {
                unset($infoCampo["SQLLEN"]);
            }

            // Normalizar el tipo de dato SQL usado en SQLTYPE
            switch ($infoCampo["SQLTYPE"]) {
            case "BOOL":
            case "BIT":
            case "BOOLEAN":
            case "TINYINT":
            case "SMALLINT":
            case "MEDIUMINT":
            case "INT":
            case "INTEGER":
            case "BIGINT":
                $infoCampo["SQLTYPE"] = "INT";
                break;
            case "FLOAT":
            case "DOUBLE":
                $infoCampo["SQLTYPE"] = "DOUBLE";
                break;
            }

            // Normalizar el tipo de expresión regular a usar para validación
            $infoCampo["SQLREGEXP"] = $this->_privado_construirRegExp($infoCampo);

            // Agregar valores por omisión de atributos
            foreach (array("NULL", "AUTOINC", "PRIMARY") as $s) {
                if (!isset($infoCampo[$s])) $infoCampo[$s] = FALSE;
            }
            if (!isset($infoCampo["DESC"])) $infoCampo["DESC"] = "columna $sNombreCampo";
        }
    }

    /**
     * Procedimiento para construir el tipo de SQL a partir de la información del campo
     */
    function _privado_construirTipoSQL($infoCampo)
    {
        if ($infoCampo["SQLTYPE"] == "INT") {
            $iLongitudPropuesta = $infoCampo["SQLLEN"];

            // Decidir el tipo más apropiado de SQL para la longitud indicada
            if ($iLongitudPropuesta > 0) $sTipoSQL = "TINYINT";
            if ($iLongitudPropuesta > 3) $sTipoSQL = "SMALLINT";
            if ($iLongitudPropuesta > 5) $sTipoSQL = "MEDIUMINT";
            if ($iLongitudPropuesta > 8) $sTipoSQL = "INT";
            if ($iLongitudPropuesta > 11) $sTipoSQL = "BIGINT";

            // Aprovechar longitudes por omisión
            if ($sTipoSQL == "TINYINT" && $iLongitudPropuesta == 3) $iLongitudPropuesta = NULL;
            if ($sTipoSQL == "SMALLINT" && $iLongitudPropuesta == 5) $iLongitudPropuesta = NULL;
            if ($sTipoSQL == "MEDIUMINT" && $iLongitudPropuesta == 8) $iLongitudPropuesta = NULL;
            if ($sTipoSQL == "INT" && $iLongitudPropuesta == 11) $iLongitudPropuesta = NULL;
            if ($sTipoSQL == "BIGINT" && $iLongitudPropuesta == 20) $iLongitudPropuesta = NULL;

            if (!is_null($iLongitudPropuesta)) {
                if (strstr($infoCampo["SQLATTR"], "UNSIGNED")) $iLongitudPropuesta++;
                $sTipoSQL .= "($iLongitudPropuesta)";
            }
        } else {
            $sTipoSQL = $infoCampo["SQLTYPE"];
            if (isset($infoCampo["SQLLEN"])) $sTipoSQL .= "(".$infoCampo["SQLLEN"].")";
        }
        if ($infoCampo["SQLATTR"] != "") $sTipoSQL .= " ".$infoCampo["SQLATTR"];

        return $sTipoSQL;
    }

    /**
     * Procedimiento que devuelve el regexp estándar según el tipo SQL soportado. Este
     * procedimiento asume que el arreglo $infoCampo dispone del atributo SQLLEN válido.
     */
    function _privado_construirRegExp($infoCampo)
    {
        // Si el atributo SQLLEN existe, se usa el valor. De otro modo,
        // se asigna NULL por omisión
        if (isset($infoCampo["SQLLEN"])) {
            $sLongitud = $infoCampo["SQLLEN"];
        } else {
            $sLongitud = NULL;
        }

        // Según el tipo de dato SQL, se elige una longitud
        switch (strtoupper($infoCampo["SQLTYPE"])) {
        case "BOOL":
        case "BIT":
        case "BOOLEAN":
        case "TINYINT":
        case "SMALLINT":
        case "MEDIUMINT":
        case "INT":
        case "INTEGER":
        case "BIGINT":
            $sRegExp = "[[:digit:]]";
            $sLongitud = $this->_privado_validarLongitudSQLINT(strtoupper($infoCampo["SQLTYPE"]), $sLongitud);
            $sRegExp .= "{1,$sLongitud}";
            if (!(isset($infoCampo["SQLATTR"]) && strtoupper($infoCampo["SQLATTR"]) == "UNSIGNED"))
                $sRegExp = "[+-]?".$sRegExp;
            return $sRegExp;
        case "FLOAT":
        case "DOUBLE":
        case "DECIMAL":
//            $sRegExp = "[[:digit:]]([[:digit:]]*\.[[:digit:]]*)?";
//            $sRegExp = '[+-]?[[:digit:]]*\.[[:digit:]]*';
            $sRegExp = '[[:digit:]]+\.?[[:digit:]]*';
            if (!(isset($infoCampo["SQLATTR"]) && strtoupper($infoCampo["SQLATTR"]) == "UNSIGNED"))
                $sRegExp = "[+-]?".$sRegExp;
            return $sRegExp;
        case "DATE":
            return "[[:digit:]]{4}-[[:digit:]]{2}-[[:digit:]]{2}";
        case "TIME":
            return "[[:digit:]]{2}:[[:digit:]]{2}(:[[:digit:]]{2})?";
        case "DATETIME":
            return "[[:digit:]]{4}-[[:digit:]]{2}-[[:digit:]]{2} [[:digit:]]{2}:[[:digit:]]{2}:[[:digit:]]{2}";
        case "CHAR":
        case "VARCHAR":
            if (is_null($sLongitud) || $sLongitud > 255) $sLongitud = 255;
            return ".{0,$sLongitud}";
        case "BLOB":
        default:
            return ".*";
        }
    }

    /**
     * Procedimiento que devuelve la longitud validada para el tipo de dato entero SQL
     */
    function _privado_validarLongitudSQLINT($sqlType, $sLongitud = NULL)
    {
        switch ($sqlType) {
        case "BOOL":
        case "BIT":
        case "BOOLEAN":
            if (is_null($sLongitud) || $sLongitud != 1) $sLongitud = 1;
            break;
        case "TINYINT":
            if (is_null($sLongitud) || $sLongitud > 3) $sLongitud = 3;
            break;
        case "SMALLINT":
            if (is_null($sLongitud) || $sLongitud > 5) $sLongitud = 5;
            break;
        case "MEDIUMINT":
            if (is_null($sLongitud) || $sLongitud > 8) $sLongitud = 8;
            break;
        case "INT":
        case "INTEGER":
            if (is_null($sLongitud) || $sLongitud > 11) $sLongitud = 11;
            break;
        case "BIGINT":
            if (is_null($sLongitud) || $sLongitud > 20) $sLongitud = 20;
            break;
        }
        return $sLongitud;
    }

    /*
        La variable $_listaFormularios es un arreglo asociativo. Cada entrada identifica
        un formulario que modifica la tabla modelada por la instancia de esta clase.
        Cada formulario es de la forma siguiente:

        $this->$_listaFormularios["FORMID"] = array(
            "title"     =>  "Crear nuevo usuario<br>\n<a href=\"?seccion=lista_usuarios\">&laquo;Regresar</a>&nbsp;",
            "width"     =>  200,
            "options"   =>  array(
                "enctype"   =>  "multipart/form-data",
                ),
            "submit"    =>  array(
                "name"   =>  "in_modificar_usuario",
                "value"  =>  (is_null($id_user) ? "Crear usuario" : "Guardar cambios"),
                ),
            "fields"    =>  array(
                array(
                    "tag"       =>  "Nombre:",
//                    "title"     =>  "Ingrese el nombre del usuario",
//                    "name"      =>  "in_nombre_usuario",
//                    "type"      =>  "text",
                    "_field"    =>  "nombre_usuario",
//                    "size"      =>  18,
//                    "maxlength" =>  50,
                    "value"     =>  ""
                    "_regexp"   =>  "[[:alnum:]\\-_]+",
                    ),
                array(
                    "tag"       =>  "Selecci&oacute;n de checkbox:",
                    "title"     =>  "Esta es una seleccion de prueba",
                    "name"      =>  "in_checkbox",
                    "type"      =>  "checkbox",
                    "value"     =>  TRUE,
                    ),
            ),
        );
        _regexp es una expresión regular que debe cumplirse para el valor que llega del formulario
            (no es necesaria para campos date y time). Si el _regexp se especifica, se valida
            SIEMPRE, antes de aplicar los regexp de la especificación de la tabla.
        _field es el campo de la tabla que corresponde al campo del formulario

        Si no se especifica el valor de _field, corresponde a las rutinas de eventos de formulario
        decidir qué hacer con el valor correspondiente del formulario. Si se especifica el valor
        _field, se pueden omitir los valores de title, name, type, size y maxlength. Entonces
        estos valores se asumen por omisión de la siguiente manera:
            title   --->    descripción del campo en la especificación de la tabla
            name    --->    in_NOMBRECAMPO, donde NOMBRECAMPO es el nombre de la columna de la tabla
            type    --->    según el SQLTYPE resultante y la presencia de ENUM, se elige uno
                            de los siguientes tipos:
                    ENUM presente                           lista desplegable
                    INT,CHAR,VARCHAR,FLOAT,DOUBLE,DECIMAL   text
                    BLOB                                    textarea
                    DATE                                    date
                    TIME                                    time
                    DATETIME                                datetime
            size    --->    El menor valor de entre 20 y la longitud del campo. Si la longitud es
                            de tipo a,b (FLOAT,DOUBLE,DECIMAL), la longitud del campo se asume a 20
            maxlength ->    Longitud del campo de la especificación, y aumentado en 1 si requiere signo
            value   --->    Si se omite value, y la especificación de la tabla tiene un ENUM,
                            se inicializa value a la lista indicada por ENUM. La especificación
                            de un campo con ENUM es compatible también con type=radio
        Si un campo se ha marcado como perteneciente a la clave primaria en la especificación de
        la tabla, y no consta entre los campos indicados por _field, se asume que las rutinas de
        transformación del formulario a la tupla de la base de datos proporcionarán un valor para
        este campo. Si no lo hacen, ocurre un error en tiempo de ejecución.
    */

    /**
     * Procedimiento encargado de registrar un formulario en el objeto. Este procedimiento
     * debe de invocarse en el constructor de la subclase, con la definición del formulario.
     *
     * @param string $sFuncionFormulario Uno de INSERT, UPDATE
     * @param string $sNombreFormulario Nombre bajo el cual se registra el formulario
     * @param array  $infoFormulario Especificación del formulario indicada arriba
     *
     * @return boolean TRUE si el formulario es válido y se registró con éxito
     */
    function definirFormulario($sFuncionFormulario, $sNombreFormulario, $infoFormulario)
    {
        if (is_null($this->_infoTabla)) die("paloEntidad::definirFormulario() - no existe info de tabla");
        $bValido = TRUE;
        $bExito = FALSE;

        // Verificar si el formulario debe de ser de inserción o actualización
        if ($bValido) {
            switch (strtoupper($sFuncionFormulario)) {
            case "INSERT":
                $sFuncionFormulario = "INSERT";
                break;
            case "UPDATE":
                $sFuncionFormulario = "UPDATE";
                break;
            default:
                $this->_msMensajeError = "PaloEntidad::definirFormulario() - funcion de formulario debe de ser INSERT o UPDATE";
                $bValido = FALSE;
            }
        }

        // Verificar si la información del formulario es válida
        if ($bValido && !is_array($infoFormulario)) {
            $this->_msMensajeError = "PaloEntidad::definirFormulario() - informacion de formulario debe de ser arreglo";
            $bValido = FALSE;
        }
        if ($bValido && !isset($infoFormulario["submit"])) {
            $this->_msMensajeError = "PaloEntidad::definirFormulario() - informacion de formulario requiere definicion de 'submit'";
            $bValido = FALSE;
        }
        if ($bValido && !isset($infoFormulario["fields"])) {
            $this->_msMensajeError = "PaloEntidad::definirFormulario() - informacion de formulario requiere definicion de campos";
            $bValido = FALSE;
        }
        if ($bValido && !is_array($infoFormulario["fields"])) {
            $this->_msMensajeError = "PaloEntidad::definirFormulario() - definicion de campos debe de ser un arreglo";
            $bValido = FALSE;
        }
        if ($bValido && count($infoFormulario["fields"]) == 0) {
            $this->_msMensajeError = "PaloEntidad::definirFormulario() - definicion de campos debe tener al menos un campo";
            $bValido = FALSE;
        }
        if ($bValido) {
            $clavesRaras = array_diff(
                array_keys($infoFormulario),
                array("title", "width", "options", "submit", "fields"));
            if (count($clavesRaras) > 0) {
                $this->_msMensajeError = "PaloEntidad::definirFormulario() - no se reconocen las ".
                    "entradas ".join(" ", $clavesRaras)." en informacion de formulario";
                $bValido = FALSE;
            }
        }

        // Si el formulario no define una opción name propia, se agrega una con
        // el nombre genérico del formulario
        if (!isset($infoFormulario['options'])) $infoFormulario['options'] = array();
        if (is_array($infoFormulario['options']) &&
            !isset($infoFormulario['options']['name'])) {
            $infoFormulario['options']['name'] = 'Formulario_'.rtrim($this->_privado_prefijoForm($sFuncionFormulario, $sNombreFormulario), '_');
        }

        // Verificar si los campos que definen la entrada _field realmente referencian a una
        // columna de la definición de la tabla. En caso de hacerlo, se pueden completar
        // valores que no fueron especificados en el formulario, según la especificación de
        // la tabla.
        if ($bValido) {
            foreach ($infoFormulario["fields"] as $indiceCampo => $infoCampo) {
                // Todos los campos son requeridos por omisión
                if (!isset($infoCampo["_empty"])) {
                    $infoFormulario["fields"][$indiceCampo]["_empty"] = FALSE;
                    $infoCampo["_empty"] = FALSE;
                } else {
                    $infoFormulario["fields"][$indiceCampo]["_empty"] = ($infoCampo["_empty"]) ? TRUE : FALSE;
                    $infoCampo["_empty"] = ($infoCampo["_empty"]) ? TRUE : FALSE;
                }

                if (isset($infoCampo["_field"])) {
                    $sNombreColumna = $infoCampo["_field"];
                    if (!isset($this->_infoTabla["campos"][$sNombreColumna])) {
                        $this->_msMensajeError = "PaloEntidad::definirFormulario() - el campo de ".
                            "formulario $indiceCampo referencia una columna de nombre ".
                            "'$sNombreColumna' que no existe en la definicion de la tabla ".
                            $this->_infoTabla["tabla"];
                        $bValido = FALSE;
                    }

                    // Un campo asociado a una columna de tabla no puede tener un valor
                    // múltiple dentro de 'value'
                    if ($bValido && isset($infoCampo["value"]) && is_array($infoCampo["value"])) {
                        $this->_msMensajeError = "PaloEntidad::definirFormulario() - el campo de ".
                            "formulario $indiceCampo no puede especificar valores múltiples ".
                            "para una columna de una tabla";
                    }

                    // Completar valores de la especificación del formulario
                    if ($bValido) {
                        // Proveer un título por omisión en el formulario
                        if (!isset($infoCampo["title"])) {
                            $infoFormulario["fields"][$indiceCampo]["title"] = $this->_infoTabla["campos"][$sNombreColumna]["DESC"];
                        }
                        // Proveer un nombre de la variable del formulario
                        if (!isset($infoCampo["name"])) {
                            $infoFormulario["fields"][$indiceCampo]["name"] = $sNombreColumna;
                        }

                        // Decidir qué tipo de widget se requiere por omisión
                        if (!isset($infoCampo["type"])) {
                            // Si el campo posee un ENUM válido, se construye una lista desplegable,
                            // lo cual requiere una asignación de options
                            if (isset($this->_infoTabla["campos"][$sNombreColumna]["ENUM"])) {
                                $infoFormulario["fields"][$indiceCampo]["type"] = "select";
                                $infoFormulario["fields"][$indiceCampo]["options"] = array();
                                foreach ($this->_infoTabla["campos"][$sNombreColumna]["ENUM"] as $sValor) {
                                    $infoFormulario["fields"][$indiceCampo]["options"][] = array(
                                        "value" => "$sValor", "tag"   =>  "$sValor");
                                }
                            } else switch ($this->_infoTabla["campos"][$sNombreColumna]["SQLTYPE"]) {
                            case "BLOB":
                                $infoFormulario["fields"][$indiceCampo]["type"] = "textarea";
                                break;
                            case "DATE":
                                $infoFormulario["fields"][$indiceCampo]["type"] = "date";
                                break;
                            case "TIME":
                                $infoFormulario["fields"][$indiceCampo]["type"] = "time";
                                break;
                            case "DATETIME":
                                $infoFormulario["fields"][$indiceCampo]["type"] = "datetime";
                                break;
                            default:
                                $infoFormulario["fields"][$indiceCampo]["type"] = "text";
                                break;
                            }
                            $infoCampo["type"] = $infoFormulario["fields"][$indiceCampo]["type"];
                        }

                        // Verificar si se pueden asignar los valores size y maxlength
                        if (in_array(strtolower($infoCampo["type"]), array("text", "password"))) {

                            // Asignar un valor apropiado para size
                            if (!isset($infoCampo["size"])) {
                                $iValorSize = 20;
                                if (isset($this->_infoTabla["campos"][$sNombreColumna]["SQLLEN"]) &&
                                    ereg("^[[:digit:]]+$", $this->_infoTabla["campos"][$sNombreColumna]["SQLLEN"]) &&
                                    $this->_infoTabla["campos"][$sNombreColumna]["SQLLEN"] < 20)
                                    $iValorSize = $this->_infoTabla["campos"][$sNombreColumna]["SQLLEN"];
                                $infoFormulario["fields"][$indiceCampo]["size"] = "$iValorSize";
                            }

                            // Asignar un valor apropiado para maxlength
                            if (!isset($infoCampo["maxlength"])) {
                                $iValorMax = 20;
//                                print "<pre>";print_r($this->_infoTabla["campos"][$sNombreColumna]);print "</pre>";
                                if (isset($this->_infoTabla["campos"][$sNombreColumna]["SQLLEN"]) &&
                                    ereg("^[[:digit:]]+$", $this->_infoTabla["campos"][$sNombreColumna]["SQLLEN"])) {

                                    // Asignar maxlength más espacio adicional para signo
                                    $iValorMax = $this->_infoTabla["campos"][$sNombreColumna]["SQLLEN"];
                                    if (!strstr(strtoupper($this->_infoTabla["campos"][$sNombreColumna]["SQLATTR"]), "UNSIGNED") &&
                                        $this->_infoTabla["campos"][$sNombreColumna]["SQLTYPE"] == "INT") {
                                        $infoFormulario["fields"][$indiceCampo]["size"]++;
                                    }
                                }
                                $infoFormulario["fields"][$indiceCampo]["maxlength"] = "$iValorMax";
                            }
                        }
                    }
                } else {
                    // Si no se define un campo _field para asignar name y type, se alerta que son
                    // requeridos para campos independientes en el formulario
                    if (!in_array($infoFormulario["fields"][$indiceCampo]["type"], array("html", "label"))) {
                        if ($bValido && !isset($infoFormulario["fields"][$indiceCampo]["name"])) {
                            $this->_msMensajeError = "PaloEntidad::definirFormulario() - el campo de ".
                                "formulario $indiceCampo no define _field asociado, y tampoco posee ".
                                "atributo 'name' (nombre de variable en formulario).";
                            $bValido = FALSE;
                        }
                        if ($bValido && !isset($infoFormulario["fields"][$indiceCampo]["type"])) {
                            $this->_msMensajeError = "PaloEntidad::definirFormulario() - el campo de ".
                                "formulario $indiceCampo no define _field asociado, y tampoco posee ".
                                "atributo 'type' (tipo de variable en formulario).";
                            $bValido = FALSE;
                        }
                    }
                }

                if (!$bValido) break;
            }

            // Si el formulario es de UPDATE, se agregan todos los campos de la clave primaria
            // con el prefijo PREVPK_ para permitir la modificación de campos que forman la clave
            // primaria en una sentencia UPDATE
            if ($bValido && $sFuncionFormulario == "UPDATE") {
                foreach ($this->_infoTabla["campos"] as $sNombreColumna => $infoColumna) {
                    if (isset($infoColumna["PRIMARY"]) && $infoColumna["PRIMARY"]) {
                        $infoFormulario["fields"][] = array(
                            "_field"    =>  $sNombreColumna,
                            "name"      =>  "PREVPK_".$sNombreColumna,
                            "type"      =>  "hidden",
                            "_empty"    =>  FALSE,
                            );
                    }
                }
            }
        }

        // Si la información del formulario pasan todas las validaciones, se agrega
        // al listado correspondiente
        if ($bValido) {
            $this->_listaFormularios[$sFuncionFormulario][$sNombreFormulario] = $infoFormulario;
            $bExito = TRUE;
        }

        return $bExito;
    }

    /**
     * Procedimiento que genera el código HTML del formulario de interés (INSERT)
     *
     * @param string $sNombreFormulario Nombre bajo el cual se registró el formulario
     * @param array  $_POST Arreglo POST de los valores previos de un intento de inserción.
     * Los valores del $_POST se usan como valor por omisión de los valores si pasan la
     * validación indicada por el _regex del formulario.
     *
     * @return string Código HTML del formulario generado
     */
    function generarFormularioInsert($sNombreFormulario, $_POST = NULL)
    {
        return $this->_privado_generarFormulario("INSERT", $sNombreFormulario, $_POST, NULL);
    }

    function _privado_generarFormulario($sFuncionFormulario, $sNombreFormulario, $_POST, $tuplaPK)
    {
        if (is_null($this->_infoTabla)) die("paloEntidad::definirFormulario".$sFuncionFormulario."() - no existe información de tabla");
        $sCodigoForm = "";
        $this->_msMensajeError = "";

        // Usar arreglo vacío en reemplazo de NULL o no-arreglo
        if (is_null($_POST) || !is_array($_POST)) $_POST = array();

        // Verificar si el formulario INSERT existe en la lista de formularios definidos
        if (!isset($this->_listaFormularios[$sFuncionFormulario][$sNombreFormulario])) {
            $sCodigoForm = $this->_tpl->crearAlerta("warning",
                "PaloEntidad::generarFormulario".$sFuncionFormulario."()",
                ($this->_msMensajeError = "PaloEntidad::generarFormulario".$sFuncionFormulario."() - no ".
                "se encuentra formulario $sFuncionFormulario de nombre '$sNombreFormulario'"));
        } else {
            // Copia profunda de la información del formulario.
            $infoFormulario = $this->_listaFormularios[$sFuncionFormulario][$sNombreFormulario];
/*
            print "<pre>";
            print_r($infoFormulario);
            print "</pre>";
*/

            // Validar cada uno de los valores $_POST contra el regexp del formulario
            // y contra los SQLREGEXP, REGEXP y ENUM de la información de la tabla
            // (en caso de encontrar _field). En caso de encontrar valores por asignar,
            // se debe de asignar el valor de acuerdo al tipo de variable
            $formVars = $this->_privado_filtrarVariablesForm($sFuncionFormulario, $sNombreFormulario, $_POST);
            for ($i = 0; $i < count($infoFormulario["fields"]); $i++) {
/*
                // Recuperar valor esperado del campo según el formulario
                $sValorForm = $this->_privado_obtenerValorForm(
                    $infoFormulario["fields"][$i]["name"], $formVars);
*/

                // Para los campos requeridos, se agrega un asterisco rojo a la izquierda
                // del tag del formulario
                if (!in_array($infoFormulario["fields"][$i]["type"], array("label", "html", "checkbox", "radio")) &&
                    !$infoFormulario["fields"][$i]["_empty"]) {
                    $sBanderaReq = "<font color=\"#FF0000\">*</font>";
                } else {
                    $sBanderaReq = "";
                }
                if (isset($infoFormulario["fields"][$i]["tag"])) {
                    $infoFormulario["fields"][$i]["tag"] = $sBanderaReq.$infoFormulario["fields"][$i]["tag"];
                }

                // Verificar si el nombre de la variable del formulario es una variable
                // escalar, o un arreglo.
                if (isset($infoFormulario["fields"][$i]["name"])) {
                    if (ereg("^(.+)\[(.*)\]$", $infoFormulario["fields"][$i]["name"], $regs)) {
                        $sNombreVar = $regs[1];
                        $sIndiceVar = $regs[2];
                        $bArreglo = TRUE;
                    } else {
                        $sNombreVar = $infoFormulario["fields"][$i]["name"];
                        $sIndiceVar = NULL;
                        $bArreglo = FALSE;
                    }

                    // Recuperar valor esperado del campo según el formulario. Dependiendo de
                    // cómo está construido el formulario, $sValorForm puede ser un arreglo
                    $sValorForm = $this->_privado_obtenerValorForm($sNombreVar, $formVars);
                    if ($bArreglo) {
                        // Caso de variable de arreglo
                        if ($sIndiceVar == "") {
                            // Índice de la variable es dependiente de la posición
                            // de aparición de la variable indexada en el formulario.
                            // Se asume que si $formVarsConsolidado[$sNombreVar] no está
                            // definido, el índice es 0, y si lo está, el índice es la
                            // cuenta del arreglo
                            if (isset($formVarsConsolidado[$sNombreVar])) {
                                $sIndiceVar = count($formVarsConsolidado[$sNombreVar]);
                            } else {
                                $sIndiceVar = 0;
                            }
                        }
                        $sValorForm = $sValorForm[$sIndiceVar];
                        $formVarsConsolidado[$sNombreVar][$sIndiceVar] = $sValorForm;
                    } else {
                        // Caso normal de variable escalar
                        if (count($formVars) > 0 && $infoFormulario["fields"][$i]['type'] == 'checkbox') {
                            $sValorForm = $sValorForm ? TRUE : FALSE;
                        }
                        $formVarsConsolidado[$sNombreVar] = $sValorForm;
                    }

                    // Si variable de formulario está enlazada con campo de tabla, se valida
                    // a través de especificación de campo de la tabla.
                    if (isset($infoFormulario["fields"][$i]["_field"])) {
                        $sNombreColumna = $infoFormulario["fields"][$i]["_field"];

                        // Pasar el valor del formulario a través de la regexp de SQL
                        // y de la tabla del formulario
                        if (isset($this->_infoTabla["campos"][$sNombreColumna]["SQLREGEXP"])) {
                            // Regexp de SQL
                            if (!is_null($sValorForm) && !ereg(
                                "^".$this->_infoTabla["campos"][$sNombreColumna]["SQLREGEXP"]."$",
                                $sValorForm)) {
                                $sValorForm = NULL;
                            }
                        }
                        if (isset($this->_infoTabla["campos"][$sNombreColumna]["REGEXP"])) {
                            // Regexp de tabla
                            if (!is_null($sValorForm) && !ereg(
                                "^".$this->_infoTabla["campos"][$sNombreColumna]["REGEXP"]."$",
                                $sValorForm)) {
                                $sValorForm = NULL;
                            }
                        }
                    }

                    // Si la variable de formulario proporciona su propio regexp, se valida con
                    // este regexp del formulario.
                    if (isset($infoFormulario["fields"][$i]["_regexp"])) {
                        // Regexp de formulario
                        if (!is_null($sValorForm) && !ereg(
                            "^".$infoFormulario["fields"][$i]["_regexp"]."$",
                            $sValorForm)) {
                            $sValorForm = NULL;
                        }
                    }

                    // Usar el valor proporcionado si no es NULL
                    if (!is_null($sValorForm)) $infoFormulario["fields"][$i]["value"] = $sValorForm;

                }
            }

            // Si el formulario es de UPDATE y se proporciona la tupla de clave primaria,
            // se intenta leer el registro desde la base de datos.
            if ($sFuncionFormulario == "UPDATE") {
                foreach ($tuplaPK as $sNC => $sVal) $tuplaPK[$sNC] = paloDB::DBCAMPO($sVal);
                $tuplaRegistro = $this->_privado_leerRegistroModificar($tuplaPK);
                if (!is_null($tuplaRegistro)) {
//                    print_r($tuplaRegistro);
                    // Se proporciona la oportunidad de asignar valores adicionales del formulario
                    // a partir de la tupla del registro
                    $valoresForm = $this->event_proveerValoresFormularioUpdate(
                        $sNombreFormulario, $tuplaRegistro);
                    if (!is_array($valoresForm)) $valoresForm = array();

                    for ($i = 0; $i < count($infoFormulario["fields"]); $i++) {
                        // Verificar si se obtiene un valor a partir de $valoresForm
                        if (!isset($infoFormulario["fields"][$i]["value"]) &&
                            isset($valoresForm[$infoFormulario["fields"][$i]["name"]])) {
                            $infoFormulario["fields"][$i]["value"] = $valoresForm[$infoFormulario["fields"][$i]["name"]];
                        }

                        // Si variable de formulario está enlazada con campo de tabla, se valida
                        // a través de especificación de campo de la tabla.
                        if (isset($infoFormulario["fields"][$i]["_field"])) {
                            $sNombreColumna = $infoFormulario["fields"][$i]["_field"];

                            if (!isset($infoFormulario["fields"][$i]["value"]) &&
                                isset($tuplaRegistro[$sNombreColumna])) {
                                    $infoFormulario["fields"][$i]["value"] = $tuplaRegistro[$sNombreColumna];
                            }
                        }
                    }
                } else {
                    // No se encontró el registro, o ha ocurrido un error.
                    if ($this->_msMensajeError != "") {
                        // Ocurrió un error de base de datos
                        return $this->_tpl->crearAlerta("error",
                            "PaloEntidad::generarFormulario".$sFuncionFormulario."()",
                            "PaloEntidad::generarFormulario".$sFuncionFormulario.
                            "() - no se pudo leer registro de tabla ".$this->_infoTabla["tabla"].
                            " - ".$this->_msMensajeError);
                    } else {
                        // No se ha encontrado el registro
                        return $this->_tpl->crearAlerta("warning",
                            "PaloEntidad::generarFormulario".$sFuncionFormulario."()",
                            ($this->_msMensajeError = "PaloEntidad::generarFormulario".$sFuncionFormulario.
                            "() - no se encuentra el registro para tabla ".$this->_infoTabla["tabla"]));
                    }
                }
            }

            // Remover los valores extraños _field y _regexp, y agregar el
            // prefijo del formulario a las variables del formulario y al submit
            $iNumCamposRequeridos = 0;
            $sPrefijoForm = $this->_privado_prefijoForm($sFuncionFormulario, $sNombreFormulario);
            for ($i = 0; $i < count($infoFormulario["fields"]); $i++) {
                unset($infoFormulario["fields"][$i]["_field"]);
                unset($infoFormulario["fields"][$i]["_regexp"]);
                if (!$infoFormulario["fields"][$i]["_empty"]) $iNumCamposRequeridos++;
                unset($infoFormulario["fields"][$i]["_empty"]);
                if (isset($infoFormulario["fields"][$i]["name"])) {
                    $infoFormulario["fields"][$i]["name"] = $sPrefijoForm.$infoFormulario["fields"][$i]["name"];
                }
            }
            $infoFormulario["submit"]["name"] = $sPrefijoForm.$infoFormulario["submit"]["name"];

            // Generar el formulario con las variables ya modificadas
            $sCodigoForm = $this->_tpl->crearFormulario($infoFormulario);
            if ($iNumCamposRequeridos > 0) $sCodigoForm .= "<div align=\"center\"><font color=\"#FF0000\">* Campo es requerido</font></div>";
        }

        return $sCodigoForm;
    }

    /**
     * Procedimiento a sobrecargar en subclase que provee valores adicionales para un formulario
     * específico de UPDATE, que no aparecen directamente como columnas de la tabla de entidad.
     *
     * @param string $sNombreFormulario Nombre del formulario que se maneja
     * @param array  $tuplaRegistro Tupla completa del registro de la base de datos
     *
     * @return array Arreglo de valores a asignar a las variables no asociadas a columnas
     */
    function event_proveerValoresFormularioUpdate($sNombreFormulario, $tuplaRegistro)
    {
        return array();
    }

    /**
     * Procedimiento que recoge todas las variables que pasan a través del POST y
     * que tengan el prefijo de la entidad, la función y el formulario, y devuelve
     * la lista de estas variables en otro arreglo, una vez removido el prefijo del
     * las entidades.
     */
    function _privado_filtrarVariablesForm($sFuncionFormulario, $sNombreFormulario, $_POST)
    {
        $sPrefijoForm = $this->_privado_prefijoForm($sFuncionFormulario, $sNombreFormulario);
        $formVars = array();
        foreach ($_POST as $sKey => $val) {
            if (substr($sKey, 0, strlen($sPrefijoForm)) == $sPrefijoForm) {
                if (get_magic_quotes_gpc() && !is_array($val)) {
                    // Deshacer backslash insertados por directiva magic_quotes_gpc
                    $val = stripslashes($val);
                }
                $formVars[substr($sKey, strlen($sPrefijoForm))] = $val;
            }
        }

        return $formVars;
    }

    /**
     * Procedimiento que construye el prefijo que se añade a las variables de formulario para
     * distinguir este formulario de otros posibles formularios que existan en la página.
     */
    function _privado_prefijoForm($sFuncionFormulario, $sNombreFormulario)
    {
        return "in_".
            $this->_infoTabla["tabla"]."_".
            $sFuncionFormulario."_".
            $sNombreFormulario."_";
    }

    /**
     * Exponer la construcción del prefijo del formulario
     */
    function prefijoForm($sFuncionFormulario, $sNombreFormulario)
    {
        return $this->_privado_prefijoForm($sFuncionFormulario, $sNombreFormulario);
    }

    /**
     * Procedimiento que reconstruye el valor obtenido del formulario a partir del nombre
     * de variable del formulario. En particular, aquí se construye el valor de fecha y hora
     * como una sola entidad, a partir de los múltiples combos mostrados en el formulario.
     * Según el contenido de $formVars, puede devolver una cadena o un arreglo (en caso de
     * un combo de selección múltiple).
     */
    function _privado_obtenerValorForm($sNombreVar, $formVars)
    {
        if (isset($formVars[$sNombreVar])) {
            $valorForm = $formVars[$sNombreVar];
            if (is_array($valorForm)) {
                if (isset($valorForm["ANIO"]) || isset($valorForm["HORA"])) {
                    $sFechaHora = "";
                    if (isset($valorForm["ANIO"])) {
                        if (isset($valorForm["ANIO"])) $sFechaHora .= sprintf("%04d", $valorForm["ANIO"])."-";
                        if (isset($valorForm["MES"])) $sFechaHora .= sprintf("%02d", $valorForm["MES"])."-";
                        if (isset($valorForm["DIA"])) $sFechaHora .= sprintf("%02d", $valorForm["DIA"]);
                    }

                    if (isset($valorForm["HORA"])) {
                        if ($sFechaHora != "") $sFechaHora .= " ";

                        if (isset($valorForm["HORA"])) $sFechaHora .= sprintf("%02d", $valorForm["HORA"]).":";
                        if (isset($valorForm["MINUTO"])) $sFechaHora .= sprintf("%02d", $valorForm["MINUTO"]).":";
                        if (isset($valorForm["SEGUNDO"])) $sFechaHora .= sprintf("%02d", $valorForm["SEGUNDO"]);
                    }
                    $valorForm = $sFechaHora;
                }
            }
            return $valorForm;
        } else {
            return NULL;
        }
    }

    /**
     * Procedimiento que intenta reconocer el formulario del cual salió el conjunto de variables
     * POST que se suministra en los parámetros
     *
     * @param array $_POST Variables POST de las cuales se intenta deducir el formulario usado
     *
     * @return array   tupla de la forma ("<funcion de formulario>", "<nombre del formulario>")
     */
    function deducirFormulario($_POST)
    {
        if (is_null($this->_infoTabla)) die("paloEntidad::deducirFormulario() - no existe información de tabla");
        $tupla = NULL;

        // Construir la lista de todos los nombres de variables que resultan de construir
        // los formularios en este objeto
        foreach ($this->_listaFormularios as $sFuncionFormulario => $listaForms) {
            foreach ($listaForms as $sNombreFormulario => $infoFormulario) {
                $sPrefijoFormulario = $this->_privado_prefijoForm($sFuncionFormulario, $sNombreFormulario);
                if (isset($_POST[$sPrefijoFormulario.$infoFormulario["submit"]["name"]])) {
                    $tupla = array($sFuncionFormulario, $sNombreFormulario);
                    break;
                }
            }
            if (!is_null($tupla)) break;
        }

        return $tupla;
    }

    /**
     * Procedimiento que genera y ejecuta el SELECT para la tabla de la entidad.
     */
    function _privado_leerRegistroModificar($where)
    {
        $this->_msMensajeError = "";
        $tupla = NULL;
        $sValores = "";
        $sCondicion = "";

        // Si la condicion $where es un arreglo, se construye
        // lista AND con los valores de igualdad. Si no, se
        // asume una condición WHERE directa.
        if (!is_null($where)) {
            $sPredicado = "";
            if (is_array($where)) {
                foreach ($where as $sCol => $sVal) {
                    if ($sPredicado != "") $sPredicado .= " AND ";
                    if (is_integer($sCol))
                        $sPredicado .= "$sVal";   // Se asume condición compleja
                    else if (is_null($sVal))
                        $sPredicado .= "$sCol IS NULL";
                    else $sPredicado .= "$sCol = $sVal"; // Se asume igualdad
                }
            } else {
                $sPredicado = $where;
            }
            $sCondicion = "WHERE $sPredicado";
        }

        // Construir la lista de valores a seleccionar
        $sValores = join(", ", array_keys($this->_infoTabla["campos"]));
        $sPeticionSQL = "SELECT $sValores FROM ".$this->_infoTabla["tabla"]." $sCondicion";
//        print $sPeticionSQL;
        $tupla =& $this->_db->getFirstRowQuery($sPeticionSQL, TRUE);
        if (is_array($tupla)) {
            if (count($tupla) == 0) $tupla = NULL; else $tupla = (array)$tupla;
//            print_r($tupla);
        } else {
            $tupla = NULL;
            $this->_msMensajeError = $this->_db->errMsg;
        }
        return $tupla;
    }

    /*
        Al manejar el formulario de INSERT, el objeto paloEntidad pasa por las siguientes
        fases durante la validación de los valores del formulario hasta la inserción:

        1) El conjunto de valores del formulario se filtra para reconstruir los valores date/time
           y eliminar los valores de POST extraños al formulario manejado.
        2) Para cada uno de los valores del formulario, se aplica la expresión regular especificada
           en la definición del formulario. Si alguno de los valores del formulario no pasa esta
           validación, se indica un error y se devuelve FALSE.
        3) Se invoca la función $this->event_validarValoresFormularioInsert() con los parámetros
           de el nombre del formulario manejado y la tupla de las variables del formulario. La
           implementación por omisión de paloEntidad no hace nada. Aquí se pueden ubicar validaciones
           que no requieran acceso a la base de datos. Se continua si la función devuelve VERDADERO.
        4) Se invoca la función $this->event_traducirFormularioBaseInsert() con los parámetros
           de el nombre del formulario manejado y la tupla del formulario. Esta función se espera
           que devuelva un arreglo asociativo indexado por las columnas definidas de la tabla
           de la entidad. La implementación por omisión recoge las variables de formulario con
           atributo _field, las copia directamente a la tupla tras validarlas con el esquema de la
           entidad via regexp, y desecha el resto de variables. La subclase puede agregar campos
           adicionales, cambiar los valores devueltos por la implementación por omisión, así como
           definir validaciones en el contexto de la base de datos. Se recomienda que la subclase
           use la tupla devuelta por la implementación por omisión, junto con las variables originales
           del formulario, para construir la tupla a ser devuelta. Se continua con el formulario si
           la tupla devuelta es un arreglo no vacío, y no NULL.
        5) Se verifica que la tupla a insertar en la base de datos defina por completo la clave
           primaria en los campos que no han sido marcados como AUTOINC. Si esto no se cumple, se
           devuelve un error. El contenido mismo de los valores de los campos NO SE EXAMINA, con la
           suposición de ya fue validado por $this->event_traducirFormularioBaseInsert()
        6) Se invoca la rutina $this->event_precondicionInsert() con los parámetros del formulario
           manejado, la tupla escapada con comillas, y las variables del formulario. La
           implementación por omisión verifica si la tupla define campos de la clave primaria que
           no sean AUTOINC en un esquema que no tenga ningún campo AUTOINC, y realiza un SELECT para
           verificar que el registro no duplica una clave primaria previamente insertada. La
           subclase puede agregar código para realizar operaciones previas a la inserción, tanto
           en la base de datos como a nivel del sistema. A la rutina también se le permite modificar
           y agregar columnas a la tupla que se va a insertar, en caso de que las precondiciones
           incluyan inserciones de valores para los cuales se recogen nuevos ID de clave primaria.
           Se continua si la función devuelve VERDADERO.
        7) Se invoca la función $this->event_listarCamposNoEscapeInsert() con los parámetros
           del formulario manejado y la tupla a insertar en la base de datos. La implementación
           por omisión devuelve una arreglo vacío. La subclase debe devolver en su implementación
           todos los campos del formulario para los cuales NO se deba realizar escape de
           comillas simples (por ejemplo, MD5(clave_de_acceso)).
        8) Se invoca paloDB->construirInsert() con la tupla escapada según las instrucciones
           indicadas por la rutina $this->event_listarCamposNoEscapeInsert(). Esta
           sentencia SQL se ejecuta inmediatamente en la base de datos.
        9) Si se tiene éxito en la ejecución de la sentencia SQL, se invoca la función
           $this->event_postcondicionInsert() con los parámetros del formulario
           manejado, la tupla escapada con comillas, y las variables del formulario. La
           implementación por omisión no hace nada. Aquí se pueden definir operaciones a realizar
           luego de la inserción del registro.
        10) Si no se tiene éxito en la ejecución de la sentencia SQL, se invoca
           $this->event_deshacerPrecondicionInsert() con los parámetros del formulario
           manejado, la tupla escapada con comillas, y las variables del formulario. La
           implementación por omisión no hace nada. La subclase puede definir aquí operaciones para
           deshacer las acciones hechas por $this->event_precondicionInsert().
        11) Se invoca por precaución UNLOCK TABLES sobre la base de datos.

     */

    /**
     * Procedimiento que maneja el formulario indicado con las variables POST pasadas como
     * parámetro al procedimiento (INSERT).
     *
     * @param string $sNombreFormulario Nombre bajo el cual se registró el formulario
     * @param array  $_POST Arreglo POST de los valores previos de un intento de inserción.
     *
     * @return boolean TRUE si el registro fue correctamente manejado, FALSE si los datos
     * del formulario fallan la validación o si ocurre un error de base de datos.
     */
    function manejarFormularioInsert($sNombreFormulario, $_POST)
    {
        if (is_null($this->_infoTabla)) die("paloEntidad::manejarFormularioInsert() - no existe información de tabla");
        $this->_msMensajeError = "";
        $bExito = FALSE;
        $bValido = TRUE;
        $sFuncionFormulario = "INSERT";

        // Verificar si el formulario INSERT existe en la lista de formularios definidos
        if (!isset($this->_listaFormularios[$sFuncionFormulario][$sNombreFormulario])) {
            $sCodigoForm = $this->_tpl->crearAlerta("error",
                "PaloEntidad::manejarFormulario".$sFuncionFormulario."()",
                ($this->_msMensajeError = "PaloEntidad::manejarFormulario".$sFuncionFormulario."() - no ".
                "se encuentra formulario $sFuncionFormulario de nombre '$sNombreFormulario'"));
        } else {
            // Referencia a la información del formulario.
            $infoFormulario =& $this->_listaFormularios[$sFuncionFormulario][$sNombreFormulario];

            // Filtrar las variables que pertenecen al formulario que está siendo manejado
            $formVars = $this->_privado_filtrarVariablesForm($sFuncionFormulario, $sNombreFormulario, $_POST);
            $formVarsConsolidado = array();

            // Este bucle acumula las variables consolidadas de fecha y hora en el
            // arreglo $formVarsConsolidado y verifica si el formulario define un regexp
            // contra el cual debe validarse el valor del formulario.
            for ($i = 0; $i < count($infoFormulario["fields"]); $i++) {
                if (isset($infoFormulario["fields"][$i]["name"])) {
                    // Verificar si el nombre de la variable del formulario es una variable
                    // escalar, o un arreglo.
                    if (ereg("^(.+)\[(.*)\]$", $infoFormulario["fields"][$i]["name"], $regs)) {
                        $sNombreVar = $regs[1];
                        $sIndiceVar = $regs[2];
                        $bArreglo = TRUE;
                    } else {
                        $sNombreVar = $infoFormulario["fields"][$i]["name"];
                        $sIndiceVar = NULL;
                        $bArreglo = FALSE;
                    }

                    // Recuperar valor esperado del campo según el formulario. Dependiendo de
                    // cómo está construido el formulario, $sValorForm puede ser un arreglo
                    $sValorForm = $this->_privado_obtenerValorForm($sNombreVar, $formVars);
                    if ($bArreglo) {
                        // Caso de variable de arreglo
                        if ($sIndiceVar == "") {
                            // Índice de la variable es dependiente de la posición
                            // de aparición de la variable indexada en el formulario.
                            // Se asume que si $formVarsConsolidado[$sNombreVar] no está
                            // definido, el índice es 0, y si lo está, el índice es la
                            // cuenta del arreglo
                            if (isset($formVarsConsolidado[$sNombreVar])) {
                                $sIndiceVar = count($formVarsConsolidado[$sNombreVar]);
                            } else {
                                $sIndiceVar = 0;
                            }
                        }
                        $sValorForm = $sValorForm[$sIndiceVar];
                        $formVarsConsolidado[$sNombreVar][$sIndiceVar] = $sValorForm;
                    } else {
                        // Caso normal de variable escalar
                        $formVarsConsolidado[$sNombreVar] = $sValorForm;
                    }

    /*
                    print "<pre>'$sValorForm'\n";
                    print_r($infoFormulario["fields"][$i]);print "</pre>";
    */
                    // Verificar que el valor del formulario es o no requerido
                    if ($bValido && !$infoFormulario["fields"][$i]["_empty"] &&
                        !in_array($infoFormulario["fields"][$i]["type"], array("label", "html", "checkbox"))) {
                        if ($sValorForm == "") {
                            $sDescCampo = '(description not set for field $i)';

                            // Construir la descripción del campo
                            if (isset($infoFormulario["fields"][$i]['_field'])) $sDescCampo = $infoFormulario["fields"][$i]['_field'];
                            if (isset($infoFormulario["fields"][$i]['name'])) $sDescCampo = $infoFormulario["fields"][$i]['name'];
                            if (isset($infoFormulario["fields"][$i]['tag'])) $sDescCampo = $infoFormulario["fields"][$i]['tag'];
                            $this->_msMensajeError = "Valor para '$sDescCampo' no debe estar vacío";
                            $bValido = FALSE;
                        }
                    }

                    // Si la variable de formulario proporciona su propio regexp, se valida con
                    // este regexp del formulario.
                    if ($bValido && isset($infoFormulario["fields"][$i]["_regexp"])) {
                        // Regexp de formulario
                        if (!is_null($sValorForm) && !ereg(
                            "^".$infoFormulario["fields"][$i]["_regexp"]."$",
                            $sValorForm)) {

                            // Construir mensaje de error para formulario
                            $this->_msMensajeError = "Valor de '$sValorForm' para '".
                                $infoFormulario["fields"][$i]["tag"].
                                "' no es apropiado a nivel de formulario";
                            $bValido = FALSE;
                        }
                    }
                }
                if (!$bValido) break;
            }

            // Si hasta ahora los valores del formulario son válidos, se procede a validar
            // con el criterio de la entidad específica
            if ($bValido) $bValido = $this->event_validarValoresFormularioInsert(
                $sNombreFormulario, $formVarsConsolidado);

            // Se intenta traducir los valores del formulario a la base de datos. Aquí se espera
            // que se realicen las validaciones de la relación entre el valor del formulario y
            // el campo correspondiente de la base de datos.
            $dbVars = NULL;
            if ($bValido) {
                $dbVars = $this->event_traducirFormularioBaseInsert($sNombreFormulario,
                    $formVarsConsolidado);
                if (!is_array($dbVars)) $bValido = FALSE;
//                print "<pre>";print_r($dbVars);print "</pre>";
            }

            // Se verifica que las columnas de $dbVars efectivamente son columnas de la
            // tabla de la entidad
            if ($bValido) {
                foreach (array_keys($dbVars) as $sNombreColumna) {
                    if (!isset($this->_infoTabla["campos"][$sNombreColumna])) {
                        $bValido = FALSE;
                        $this->_msMensajeError = "PaloEntidad::manejarFormularioInsert() - ".
                            "no se reconoce la columna '$sNombreColumna' como una columna valida ".
                            "de la tabla '".$this->_infoTabla["tabla"]."'";
                    }
                    if (!$bValido) break;
                }
            }

            // Se verifica que se tiene al menos un valor a modificar
            if ($bValido) {
                if (count($dbVars) == 0) {
                    $bValido = FALSE;
                    $this->_msMensajeError = "PaloEntidad::manejarFormularioInsert() - ".
                        "no se disponen de campos a insertar para tabla '".$this->_infoTabla["tabla"]."'";
                }
            }

            // Se verifica que si el esquema de la tabla define campos de clave primaria que no
            // son AUTOINC, los claves de la tupla devuelta incluyan por completo a la clave
            // primaria. Esta verificación es para depuración, de que el formulario y la traducción
            // del formulario definan completamente la clave primaria.
            if ($bValido) {
                $listaPK = array();
                foreach ($this->_infoTabla["campos"] as $sNombreColumna => $infoColumna) {
                    if (!$infoColumna["AUTOINC"] && $infoColumna["PRIMARY"]) $listaPK[] = $sNombreColumna;
                }
                $camposFaltantesPK = array_diff($listaPK, array_keys($dbVars));
                if (count($camposFaltantesPK) > 0) {
                    $this->_msMensajeError = "PaloEntidad::manejarFormularioInsert() - los siguientes ".
                        " campos de clave primaria no fueron especificados en formulario $sNombreFormulario: ".
                        join(" ", $camposFaltantesPK);
                    $bValido = FALSE;
                }
            }

            // Se escapan los valores a insertar en la base de datos.
            if ($bValido) {
                $bValido = $this->event_precondicionInsert($sNombreFormulario, $dbVars, $formVars);
//                print "<pre>";print_r($dbVars);print "</pre>";
            }

            if ($bValido) {
                $listaCamposExcluir = $this->event_listarCamposNoEscapeInsert($sNombreFormulario, $dbVars);
                $dbVarsEscape = $this->_privado_escapeComillas($sNombreFormulario, $dbVars, $listaCamposExcluir);
                $sPeticionSQL = paloDB::construirInsert($this->_infoTabla["tabla"], $dbVarsEscape);
//                print "<pre>$sPeticionSQL</pre>";
                if ($this->_db->genQuery($sPeticionSQL)) {
                    $bExito = TRUE;
                    $this->event_postcondicionInsert($sNombreFormulario, $dbVars, $formVars);
                } else {
                    $this->_msMensajeError = $this->_db->errMsg;
                    $this->event_deshacerPrecondicionInsert($sNombreFormulario, $dbVars, $formVars);
                }
            }

            // Desbloquear las tabla que siguieran bloqueadas
            $this->_db->genQuery("UNLOCK TABLES");
        }

        return $bExito;
    }

    /**
     * Procedimiento a sobrecargar en clases derivadas para implementar validaciones adicionales
     * sobre los valores del formulario, sin leer la base de datos.
     *
     * @param string $sNombreFormulario Nombre del formulario que se está manejando
     * @param array  $formVars          Variables obtenidas del formulario, en arreglo asociativo
     *
     * @return boolean TRUE si los parámetros parecen válidos hasta ahora, FALSE si no lo son.
     * La rutina puede asignar $this->_msMensajeError a un texto explicativo del error.
     */
    function event_validarValoresFormularioInsert($sNombreFormulario, $formVars)
    {
        return TRUE;
    }

    /**
     * Procedimiento a sobrecargar en clases derivadas para traducir entre las variables del
     * formulario hacia las variables a insertar en la base de datos
     *
     * @param string $sNombreFormulario Nombre del formulario que se está manejando
     * @param array  $formVars          Variables obtenidas del formulario, en arreglo asociativo
     *
     * @return array Arreglo asociativo de las variables a insertar en la base de datos, o NULL
     * si alguna de las variables no pasa la validación de la base de datos.
     */
    function event_traducirFormularioBaseInsert($sNombreFormulario, $formVars)
    {
        $sFuncionFormulario = "INSERT";
        return $this->_privado_traducirFormularioBase($sFuncionFormulario, $sNombreFormulario, $formVars);
    }
    function _privado_traducirFormularioBase($sFuncionFormulario, $sNombreFormulario, $formVars)
    {
        $this->_msMensajeError = "";
        $bExito = FALSE;
        $bValido = TRUE;
        $dbVars = array();

        // Referencia a la información del formulario.
        $infoFormulario =& $this->_listaFormularios[$sFuncionFormulario][$sNombreFormulario];
        for ($i = 0; $i < count($infoFormulario["fields"]); $i++) {
            // Verificar si el formulario referencia un campo de la base de datos
            if (isset($infoFormulario["fields"][$i]["_field"]) &&
                !ereg("^PREVPK_", $infoFormulario["fields"][$i]["name"]) &&
                !in_array($infoFormulario["fields"][$i]["type"], array("label", "html"))) {

                $bColumnaNULL = FALSE;

                $sNombreColumna = $infoFormulario["fields"][$i]["_field"];
                $sNombreVar = $infoFormulario["fields"][$i]["name"];
                $infoColumna =& $this->_infoTabla["campos"][$sNombreColumna];

                // Verificar que el campo del formulario obedece la expresión regular que
                // define al tipo de dato SQL
                if (!is_null($dbVars) && !ereg("^".$infoColumna["SQLREGEXP"]."$", $formVars[$sNombreVar])) {
		    if ($infoColumna["NULL"] && $formVars[$sNombreVar] == "") {
                        // Cadena vacía puede volverse NULL
                        $bColumnaNULL = TRUE;
                    } else {
                        $dbVars = NULL;
                        $this->_msMensajeError = "Valor de '".$formVars[$sNombreVar]."' para ".
                            $infoColumna["DESC"]." no es apropiado para ser almacenado.";
                    }
                }

                // Si el campo de base de datos define un regexp propio, se verifica que
                // el campo del formulario obedece la expresión regular del campo
                if (!is_null($dbVars) && isset($infoColumna["REGEXP"])) {
//                    print "Verificando para $sNombreColumna ";
                    if (!ereg("^".$infoColumna["REGEXP"]."$", $formVars[$sNombreVar])) {
                        $dbVars = NULL;
                        $this->_msMensajeError = "Valor de '".$formVars[$sNombreVar]."' para ".
                            $infoColumna["DESC"]." no es apropiado para campo.";
                    }
                }

                // Si el campo de base de datos define una enumeración, se verifica que
                // el campo del formulario se encuentra entre los valores de la enumeración
                if (!is_null($dbVars) && isset($infoColumna["ENUM"]) && is_array($infoColumna["ENUM"])) {
                    if (!in_array($formVars[$sNombreVar], $infoColumna["ENUM"])) {
                        if ($infoColumna["NULL"] && $formVars[$sNombreVar] == "") {
                            // Si valor es vacío, puede ser NULL en vez de enumeración
                            $bColumnaNULL = TRUE;
                        } else {
                            $dbVars = NULL;
                            $this->_msMensajeError = "Valor de '".$formVars[$sNombreVar]."' para ".
                                $infoColumna["DESC"]." no consta en la lista de valores apropiados ".
                                "para el campo.";
                        }
                    }
                }

                // Si la tupla no ha sido anulada, se añade el valor a la tupla
                if (!is_null($dbVars)) $dbVars[$sNombreColumna] = $bColumnaNULL ? NULL : $formVars[$sNombreVar];
            }

            if (is_null($dbVars)) break;
        }
        return $dbVars;
    }

    /**
     * Procedimiento para listar todos los campos que NO deben ser escapados en la
     * tupla indicada de la base de datos, al insertar según el formulario indicado.
     *
     * @param string $sNombreFormulario Nombre del formulario que se está manejando
     * @param array  $dbVars            Variables a insertar en la base de datos
     *
     * @return array Lista de nombres de campos que NO deben ser escapados
     */
    function event_listarCamposNoEscapeInsert($sNombreFormulario, $dbVars)
    {
        return array();
    }

    /**
     * Procedimiento para escapar con comillas los campos de $dbVars y devolver la tupla
     * de los campos ya escapados. Por omisión se escapan todos los campos.
     *
     * @param string $sNombreFormulario Nombre del formulario que se está manejando
     * @param array  $dbVars            Variables a insertar en la base de datos
     *
     * @return array Lista de tuplas con los campos escapados
     */
    function _privado_escapeComillas($sNombreFormulario, $dbVars, $listaExcluir)
    {
        $dbVarsEscape = array();

        if (!is_array($listaExcluir)) $listaExcluir = array();
        foreach ($dbVars as $sNombreColumna => $sValorColumna) {
            if (in_array($sNombreColumna, $listaExcluir)) {
                $dbVarsEscape[$sNombreColumna] = $sValorColumna;
            } else {
                $dbVarsEscape[$sNombreColumna] = is_null($sValorColumna) ? NULL : paloDB::DBCAMPO($sValorColumna);
            }
        }

        return $dbVarsEscape;
    }

    /**
     * Procedimiento para realizar operaciones previas a la inserción de la tupla en la base
     * de datos. Esta implementación por omisión ignora el formulario especificado y verifica
     * que si el esquema de tabla define una clave primaria sin campos AUTOINC, la tupla a
     * insertar defina una clave primaria que no esté ya presente en la base de datos.
     *
     * @param string $sNombreFormulario Nombre del formulario que se está manejando
     * @param array  $dbVars            Variables a insertar en la base de datos, con comillas
     * @param array  $formVars          Variables del formulario de inserción
     *
     * @return boolean TRUE si se completó la precondición, FALSE si no.
     */
    function event_precondicionInsert($sNombreFormulario, &$dbVars, $formVars)
    {
        $bExito = FALSE;
        $this->_msMensajeError = "";
        $bExisteAutoinc = FALSE;
        $listaPK = array();

        foreach ($this->_infoTabla["campos"] as $sNombreColumna => $infoColumna) {
            if ($infoColumna["PRIMARY"]) {
                if ($infoColumna["AUTOINC"]) $bExisteAutoinc = TRUE;
                $listaPK[] = $sNombreColumna;
            }
        }
        if (!$bExisteAutoinc) {
            $listaExcluir = $this->event_listarCamposNoEscapeInsert($sNombreFormulario, $dbVars);

            $where = array();
            foreach ($listaPK as $sCampoPK) {
                if (in_array($sCampoPK, $listaExcluir)) {
                    $where[$sCampoPK] = $dbVars[$sCampoPK];
                } else {
                    $where[$sCampoPK] = paloDB::DBCAMPO($dbVars[$sCampoPK]);
                }
            }
            $tupla = $this->_privado_leerRegistroModificar($where);
            if (is_array($tupla)) {
                $this->_msMensajeError = "Ya existe otro registro con la identidad indicada";
            } else if (is_null($tupla) && $this->_msMensajeError != "") {
            } else {
                $bExito = TRUE;
            }
        } else {
            $bExito = TRUE;
        }

        return $bExito;
    }

    /**
     *
     */
    function event_postcondicionInsert($sNombreFormulario, $dbVars, $formVars)
    {
    }

    /**
     * Procedimiento para deshacer los efectos colaterales introducidos en el sistema
     * por la rutina event_precondicionInsert().
     *
     * @param string $sNombreFormulario Formulario que se maneja
     * @param array  $dbVars Variables que se intentaron ingresar a la tabla
     * @param array  $formVars Variables del formulario original de ingreso
     *
     * @return void
     */
    function event_deshacerPrecondicionInsert($sNombreFormulario, $dbVars, $formVars)
    {
    }

    /**
     * Procedimiento para obtener la referencia al formulario de función y nombre indicado
     */
    function & referenciaForm($sFuncionFormulario, $sNombreFormulario)
    {
        return $this->_listaFormularios[$sFuncionFormulario][$sNombreFormulario];
    }

    /**
     * Procedimiento que genera el código HTML del formulario de interés (UPDATE)
     *
     * @param string $sNombreFormulario Nombre bajo el cual se registró el formulario
     * @param array  $_POST Arreglo POST de los valores previos de un intento de inserción.
     * @param array  $tupla Tupla de valores de la clave primaria del formulario
     *  Si se asigna a != NULL, se intenta armar un SELECT usando los campos de $tupla
     *  como la clave primaria, y se intenta llenar los valores a través de las etiquetas
     *  _field del formulario indicado.
     *
     * @return string Código HTML del formulario generado
     */
    function generarFormularioUpdate($sNombreFormulario, $_POST = NULL, $tupla = NULL)
    {
        return $this->_privado_generarFormulario("UPDATE", $sNombreFormulario, $_POST, $tupla);
    }

    /*
        Al manejar el formulario de UPDATE, el objeto paloEntidad pasa por las siguientes fases
        durante la validación de los valores del formulario hasta la modificación.

        1) El conjunto de valores del formulario se filtra para reconstruir los valores date/time
           y eliminar los valores de POST extraños al formulario manejado.
        2) Para cada uno de los valores del formulario, se aplica la expresión regular especificada
           en la definición del formulario. Si alguno de los valores del formulario no pasa esta
           validación, se indica un error y se devuelve FALSE.
        3) Se separan los valores de tipo hidden que contienen el valor previo de la clave primaria
           del registro que se está modificando, para que sea comunicado en pasos sucesivos. Estos
           valores se validan con los regexp de la tabla de la entidad y se continua si estos valores
           pasan los regexp de la tabla.
        4) Se invoca la función $this->event_validarValoresFormularioUpdate() con los parámetros
           de el nombre del formulario manejado, la tupla de las variables previas de clave primaria
           y la tupla de las variables del formulario. La implementación por omisión de paloEntidad
           no hace nada. Aquí se pueden ubicar validaciones que no requieran acceso a la base de
           datos. Se continua si la función devuelve VERDADERO.
        5) Se invoca la función $this->event_traducirFormularioBaseUpdate() con los parámetros de
           el nombre del formulario manejado, la tupla de las variables previas de clave primaria
           y la tupla de las variables del formulario. Esta función se espera que devuelva un
           arreglo asociativo indexado por las columnas definidas de la tabla de la entidad. La
           implementación por omisión recoge las variables de formulario con atributo _field, las
           copia directamente a la tupla tras validarlas con el esquema de la entidad via regexp, y
           desecha el resto de variables. La subclase puede agregar campos adicionales, cambiar los
           valores devueltos por la implementación por omisión, así como definir validaciones en el
           contexto de la base de datos. Se recomienda que la subclase use la tupla devuelta por la
           implementación por omisión, junto con las variables originales del formulario y la tupla,
           previa de la clave primaria, para construir la tupla a ser devuelta. Se continua con el
           formulario si la tupla devuelta es un arreglo no vacío, y no NULL.
        6) Se invoca la rutina $this->event_precondicionUpdate() con los parámetros del formulario
           manejado, la tupla de la clave primaria previa escapada con comillas, la tupla de los
           valores modificados escapada con comillas, y las variables del formulario. La
           implementación por omisión verifica si la tupla de valores modificados especifica
           columnas de la clave primaria con valores distintos de la clave primaria previa. Si es
           así, se construye la clave primaria del registro potencial modificado, y se verifica que
           no exista un registro con una clave primaria igual a la del registro potencial. La
           subclase puede agregar código para realizar operaciones previas a la modificación, tanto
           en la base de datos como a nivel del sistema. A la rutina también se le permite modificar
           y agregar columnas a la tupla que se va a modificar, en caso de que las precondiciones
           incluyan inserciones de valores para los cuales se recogen nuevos ID de clave primaria.
           Se continua si la función devuelve VERDADERO.
        7) Se invoca la función $this->event_listarCamposNoEscapeUpdate() con los parámetros
           del formulario manejado, la clave primaria previa y la tupla a insertar en la base de
           datos. La implementación por omisión devuelve una arreglo vacío. La subclase debe
           devolver en su implementación todos los campos del formulario para los cuales NO se
           deba realizar escape de comillas simples (por ejemplo, MD5(clave_de_acceso)). Nótese
           que los campos de la clave primaria previa se escapan de forma independiente, en la
           suposición de que todos los valores son escalares simples.
        8) Se invoca paloDB->construirUpdate() con las tuplas devueltas. La sentencia SQL resultante
           se ejecuta inmediatamente en la base de datos.
        9) Si se tiene éxito en la ejecución de la sentencia SQL, se invoca la función
           $this->event_postcondicionUpdate() con los parámetros del formulario manejado, la tupla
           previa de la clave primaria escapada, la tupla de valores modificados escapada, y las
           variables del formulario. La implementación por omisión no hace nada. Aquí se pueden
           definir operaciones a realizar luego de la modificación del registro.
        10) Si no se tiene éxito en la ejecución de la sentencia SQL, se invoca
           $this->event_deshacerPrecondicionUpdate() con los parámetros del formulario manejado, la
           tupla previa de la clave primaria escapada, la tupla de valores modificados escapada, y
           las variables del formulario. La implementación por omisión no hace nada. Aquí se pueden
           deshacer las acciones hechas por $this->event_precondicionUpdate().
        11) Se invoca por precaución UNLOCK TABLES sobre la base de datos

     */

    /**
     * Procedimiento que maneja el formulario indicado con las variables POST pasadas como
     * parámetro al procedimiento (UPDATE).
     *
     * @param string $sNombreFormulario Nombre bajo el cual se registró el formulario
     * @param array  $_POST Arreglo POST de los valores previos de un intento de inserción.
     *
     * @return boolean TRUE si el registro fue correctamente manejado, FALSE si los datos
     * del formulario fallan la validación o si ocurre un error de base de datos.
     */
    function manejarFormularioUpdate($sNombreFormulario, $_POST)
    {
        if (is_null($this->_infoTabla)) die ("paloEntidad::manejarFormularioUpdate() - no se tiene información de tabla");
        $this->_msMensajeError = "";
        $bExito = FALSE;
        $bValido = TRUE;
        $sFuncionFormulario = "UPDATE";

        // Verificar si el formulario UPDATE existe en la lista de formularios definidos
        if (!isset($this->_listaFormularios[$sFuncionFormulario][$sNombreFormulario])) {
            $sCodigoForm = $this->_tpl->crearAlerta("error",
                "PaloEntidad::manejarFormulario".$sFuncionFormulario."()",
                ($this->_msMensajeError = "PaloEntidad::manejarFormulario".$sFuncionFormulario."() - no ".
                "se encuentra formulario $sFuncionFormulario de nombre '$sNombreFormulario'"));
        } else {
            // Referencia a la información del formulario.
            $infoFormulario =& $this->_listaFormularios[$sFuncionFormulario][$sNombreFormulario];

            // Filtrar las variables que pertenecen al formulario que está siendo manejado
            $formVars = $this->_privado_filtrarVariablesForm($sFuncionFormulario, $sNombreFormulario, $_POST);
            $formVarsConsolidado = array();

            // Este bucle acumula las variables consolidadas de fecha y hora en el
            // arreglo $formVarsConsolidado y verifica si el formulario define un regexp
            // contra el cual debe validarse el valor del formulario.
            for ($i = 0; $i < count($infoFormulario["fields"]); $i++) {
                if (isset($infoFormulario["fields"][$i]["name"]) &&
                    !in_array($infoFormulario["fields"][$i]["type"], array("label", "html"))) {
                    // Verificar si el nombre de la variable del formulario es una variable
                    // escalar, o un arreglo.
                    if (ereg("^(.+)\[(.*)\]$", $infoFormulario["fields"][$i]["name"], $regs)) {
                        $sNombreVar = $regs[1];
                        $sIndiceVar = $regs[2];
                        $bArreglo = TRUE;
                    } else {
                        $sNombreVar = $infoFormulario["fields"][$i]["name"];
                        $sIndiceVar = NULL;
                        $bArreglo = FALSE;
                    }

                    // Recuperar valor esperado del campo según el formulario. Dependiendo de
                    // cómo está construido el formulario, $sValorForm puede ser un arreglo
                    $sValorForm = $this->_privado_obtenerValorForm($sNombreVar, $formVars);
                    if ($bArreglo) {
                        // Caso de variable de arreglo
                        if ($sIndiceVar == "") {
                            // Índice de la variable es dependiente de la posición
                            // de aparición de la variable indexada en el formulario.
                            // Se asume que si $formVarsConsolidado[$sNombreVar] no está
                            // definido, el índice es 0, y si lo está, el índice es la
                            // cuenta del arreglo
                            if (isset($formVarsConsolidado[$sNombreVar])) {
                                $sIndiceVar = count($formVarsConsolidado[$sNombreVar]);
                            } else {
                                $sIndiceVar = 0;
                            }
                        }
                        $sValorForm = $sValorForm[$sIndiceVar];
                        $formVarsConsolidado[$sNombreVar][$sIndiceVar] = $sValorForm;
                    } else {
                        // Caso normal de variable escalar
                        $formVarsConsolidado[$sNombreVar] = $sValorForm;
                    }
    /*
                    print "<pre>'$sValorForm'\n";
                    print_r($infoFormulario["fields"][$i]);print "</pre>";
    */
                    // Verificar que el valor del formulario es o no requerido
                    if ($bValido && !$infoFormulario["fields"][$i]["_empty"] &&
                        !in_array($infoFormulario["fields"][$i]["type"], array("label", "html", "checkbox"))) {
                        if ($sValorForm == "") {
                            $this->_msMensajeError = "Valor para '".$infoFormulario["fields"][$i]["tag"].
                                "' no debe estar vacío";
                            $bValido = FALSE;
                        }
                    }

                    // Si la variable de formulario proporciona su propio regexp, se valida con
                    // este regexp del formulario.
                    if ($bValido && isset($infoFormulario["fields"][$i]["_regexp"])) {
                        // Regexp de formulario
                        if (!is_null($sValorForm) && !ereg(
                            "^".$infoFormulario["fields"][$i]["_regexp"]."$",
                            $sValorForm)) {

                            // Construir mensaje de error para formulario
                            $this->_msMensajeError = "Valor de '$sValorForm' para '".
                                $infoFormulario["fields"][$i]["tag"].
                                "' no es apropiado a nivel de formulario";
                            $bValido = FALSE;
                        }
                    }
                }
                if (!$bValido) break;
            }

//            print "<pre>";print_r($formVarsConsolidado);print "</pre>";

            // Se separa la clave primaria previa del formulario de la tupla de variables del
            // formulario. Se valida que para cada $sNombreColumna de la clave primaria, exista
            // una variable PREVPK_$sNombreColumna en el formulario. Entonces se remueve este valor
            // de las variables del formulario y se añade a la tupla de clave primaria.
            $prevPK = NULL;
            if ($bValido) {
                $prevPK = array();
                foreach ($this->_infoTabla["campos"] as $sNombreColumna => $infoColumna) {
                    if ($infoColumna["PRIMARY"]) {
                        if (isset($formVarsConsolidado["PREVPK_".$sNombreColumna])) {
                            $prevPK[$sNombreColumna] = $formVarsConsolidado["PREVPK_".$sNombreColumna];
                            unset($formVarsConsolidado["PREVPK_".$sNombreColumna]);
                        } else {
                            $this->_msMensajeError = "No se encuentra en formulario el valor previo de '$sNombreColumna' de identidad de registro.";
                            $bValido = FALSE;
                        }
                    }
                    if (!$bValido) break;
                }
            }

            // Se valida que los valores de la clave primaria no han sido modificados
            if ($bValido) foreach ($prevPK as $sNombreColumna => $sValorPK) {
                $infoColumna =& $this->_infoTabla["campos"][$sNombreColumna];

                // Verificar que el campo del formulario obedece la expresión regular que
                // define al tipo de dato SQL
                if ($bValido && !ereg("^".$infoColumna["SQLREGEXP"]."$", $sValorPK)) {
                    $bValido = FALSE;
                    $this->_msMensajeError = "Valor de '$sValorPK' para ".
                        $infoColumna["DESC"]." no es apropiado para clave primaria.";
                }

                // Si el campo de base de datos define un regexp propio, se verifica que
                // el campo del formulario obedece la expresión regular del campo
                if ($bValido && isset($infoColumna["REGEXP"])) {
//                    print "Verificando para $sNombreColumna ";
                    if (!ereg("^".$infoColumna["REGEXP"]."$", $sValorPK)) {
                        $bValido = FALSE;
                        $this->_msMensajeError = "Valor de '$sValorPK' para ".
                            $infoColumna["DESC"]." no es apropiado para clave primaria.";
                    }
                }

                // Si el campo de base de datos define una enumeración, se verifica que
                // el campo del formulario se encuentra entre los valores de la enumeración
                if ($bValido && isset($infoColumna["ENUM"]) && is_array($infoColumna["ENUM"])) {
                    if (!in_array($sValorPK, $infoColumna["ENUM"])) {
                        $bValido = FALSE;
                        $this->_msMensajeError = "Valor de '$sValorPK' para ".
                            $infoColumna["DESC"]." no consta en la lista de valores apropiados ".
                            "para el campo de clave primaria.";
                    }
                }
            }

            // Si hasta ahora los valores del formulario son válidos, se procede a validar
            // con el criterio de la entidad específica
            if ($bValido) $bValido = $this->event_validarValoresFormularioUpdate(
                $sNombreFormulario, $prevPK, $formVarsConsolidado);

            // Se intenta traducir los valores del formulario a la base de datos. Aquí se espera
            // que se realicen las validaciones de la relación entre el valor del formulario y
            // el campo correspondiente de la base de datos.
            $dbVars = NULL;
            if ($bValido) {
                $dbVars = $this->event_traducirFormularioBaseUpdate($sNombreFormulario,
                    $prevPK, $formVarsConsolidado);
                if (!is_array($dbVars)) $bValido = FALSE;
//                print "<pre>";print_r($dbVars);print "</pre>";
            }

            // Se verifica que las columnas de $dbVars efectivamente son columnas de la
            // tabla de la entidad
            if ($bValido) {
                foreach (array_keys($dbVars) as $sNombreColumna) {
                    if (!isset($this->_infoTabla["campos"][$sNombreColumna])) {
                        $bValido = FALSE;
                        $this->_msMensajeError = "PaloEntidad::manejarFormularioUpdate() - ".
                            "no se reconoce la columna '$sNombreColumna' como una columna valida ".
                            "de la tabla '".$this->_infoTabla["tabla"]."'";
                    }
                    if (!$bValido) break;
                }
            }

            // Se escapan los valores a modificar en la base de datos.
            if ($bValido) {
//                print "<pre>";print_r($dbVars);print "</pre>";
                $bValido = $this->event_precondicionUpdate($sNombreFormulario, $prevPK, $dbVars, $formVars);
            }
/*
            // Se verifica que se tiene al menos un valor a modificar
            if ($bValido) {
                if (count($dbVars) == 0) {
                    $bValido = FALSE;
                    $this->_msMensajeError = "PaloEntidad::manejarFormularioUpdate() - ".
                        "no se disponen de campos a modificar para tabla '".$this->_infoTabla["tabla"]."'";
                }
            }
*/
            if ($bValido) {
                if (count($dbVars) > 0) {
                    // Preparar la tupla para inserción si se tiene al menos un valor a modificar
                    $listaExcluir = $this->event_listarCamposNoEscapeUpdate($sNombreFormulario, $prevPK, $dbVars);
                    $dbVarsEscape = $this->_privado_escapeComillas($sNombreFormulario, $dbVars, $listaExcluir);
                    $prevPKEscape = array();
                    foreach ($prevPK as $sNombreColumna => $sValorColumna) {
                        $prevPKEscape[$sNombreColumna] = paloDB::DBCAMPO($sValorColumna);
                    }
                    $sPeticionSQL = paloDB::construirUpdate($this->_infoTabla["tabla"], $dbVarsEscape, $prevPKEscape);
//                    print "<pre>$sPeticionSQL</pre>";
                    if ($this->_db->genQuery($sPeticionSQL)) {
                        $bExito = TRUE;
                        $this->event_postcondicionUpdate($sNombreFormulario, $prevPK, $dbVars, $formVars);
                    } else {
                        $this->_msMensajeError = $this->_db->errMsg;
                        $this->event_deshacerPrecondicionUpdate($sNombreFormulario, $prevPK, $dbVars, $formVars);
                    }
                } else {
                    $bExito = TRUE;
                    $this->event_postcondicionUpdate($sNombreFormulario, $prevPK, $dbVars, $formVars);
                }
            }

            // Desbloquear las tabla que siguieran bloqueadas
            $this->_db->genQuery("UNLOCK TABLES");
        }

        return $bExito;
    }

    /**
     * Procedimiento a sobrecargar en clases derivadas para implementar validaciones adicionales
     * sobre los valores del formulario, sin leer la base de datos.
     *
     * @param string $sNombreFormulario Nombre del formulario que se está manejando
     * @param array  $prevPK            Clave primaria previa del registro modificado
     * @param array  $formVars          Variables obtenidas del formulario, en arreglo asociativo
     *
     * @return boolean TRUE si los parámetros parecen válidos hasta ahora, FALSE si no lo son.
     * La rutina puede asignar $this->_msMensajeError a un texto explicativo del error.
     */
    function event_validarValoresFormularioUpdate($sNombreFormulario, $prevPK, $formVars)
    {
        return TRUE;
    }

    /**
     * Procedimiento a sobrecargar en clases derivadas para traducir entre las variables del
     * formulario hacia las variables a insertar en la base de datos
     *
     * @param string $sNombreFormulario Nombre del formulario que se está manejando
     * @param array  $prevPK            Clave primaria previa del registro modificado
     * @param array  $formVars          Variables obtenidas del formulario, en arreglo asociativo
     *
     * @return array Arreglo asociativo de las variables a modificar en la base de datos, o NULL
     * si alguna de las variables no pasa la validación de la base de datos.
     */
    function event_traducirFormularioBaseUpdate($sNombreFormulario, $prevPK, $formVars)
    {
        $sFuncionFormulario = "UPDATE";
        return $this->_privado_traducirFormularioBase($sFuncionFormulario, $sNombreFormulario, $formVars);
    }

    /**
     * Procedimiento para listar los campos que no deben ser escapados durante la
     * actualización de la tupla de la base de datos. La implementación por omisión
     * devuelve una tupla vacía.
     *
     * @param string $sNombreFormulario Nombre del formulario que se está manejando
     * @param array  $prevPK            Clave primaria previa del registro modificado
     * @param array  $dbVars            Variables a insertar en la base de datos
     *
     * @return array Lista de nombres de campos que no deben escaparse
     */
    function event_listarCamposNoEscapeUpdate($sNombreFormulario, $prevPK, $dbVars)
    {
        return array();
    }

    /**
     * Procedimiento para realizar operaciones previas a la inserción de la tupla en la base
     * de datos. Esta implementación por omisión ignora el formulario especificado y verifica
     * que si la tupla de valores a modificar define nuevos valores para la clave primaria distintos
     * de los indicados en la tupla $prevPK, la nueva clave primaria no corresponda a registro
     * alguno presente en la base de datos.
     *
     * @param string $sNombreFormulario Nombre del formulario que se está manejando
     * @param array  $prevPK            Clave primaria previa del registro modificado
     * @param array  $dbVars            Variables a insertar en la base de datos, con comillas
     * @param array  $formVars          Variables del formulario de inserción
     *
     * @return boolean TRUE si se completó la precondición, FALSE si no.
     */
    function event_precondicionUpdate($sNombreFormulario, $prevPK, &$dbVars, $formVars)
    {
        $bValido = TRUE;
        $nuevaPK = array();
        $nuevaPKEscape = array();
        $bClaveDistinta = FALSE;

        // Construir la nueva clave primaria para el registro ya modificado
        foreach ($prevPK as $sNombreColumna => $sValorColumna) {
            $nuevaPK[$sNombreColumna] = $sValorColumna;
            $nuevaPKEscape[$sNombreColumna] = paloDB::DBCAMPO($sValorColumna);
        }
        $listaExcluir = $this->event_listarCamposNoEscapeUpdate($sNombreFormulario, $prevPK, $dbVars);
        foreach ($dbVars as $sNombreColumna => $sValorColuma) {
            if (isset($nuevaPK[$sNombreColumna]) && $nuevaPK[$sNombreColumna] != $sValorColumna) {
                $bClaveDistinta = TRUE;
                $nuevaPK[$sNombreColumna] = $sValorColumna;
                $nuevaPKEscape[$sNombreColumna] = paloDB::DBCAMPO($sValorColumna);
                if (in_array($sNombreColumna, $listaExcluir)) $nuevaPKEscape[$sNombreColumna] = $sValorColumna;
            }
        }

        // Verificar si la clave es distinta y si existe o no registro en base
        if ($bClaveDistinta) {

            $tupla = $this->_privado_leerRegistroModificar($nuevaPKEscape);
            if (is_array($tupla)) {
                // Otro registro ya tiene la clave primaria indicada
                $bValido = FALSE;
                $this->_msMensajeError = "Ya existe otro registro con la identidad indicada";
            } else if (is_null($tupla) && $this->_msMensajeError != "") {
                // Ocurre un error de base de datos
                $bValido = FALSE;
            } else {
                // Clave primaria estimada está desocupada
            }
        }

        return $bValido;
    }

    /**
     * Procedimiento para completar cambios en la base de datos luego de modificar la tupla
     * de la base de datos.
     *
     * @param string $sNombreFormulario Nombre del formulario que se está manejando
     * @param array  $prevPK            Clave primaria previa del registro modificado
     * @param array  $dbVars            Variables a insertar en la base de datos, con comillas
     * @param array  $formVars          Variables del formulario de inserción
     *
     * @return void
     */
    function event_postcondicionUpdate($sNombreFormulario, $prevPK, $dbVars, $formVars)
    {
    }

    /**
     * Procedimiento para deshacer los efectos laterales realizados por event_precondicionUpdate()
     *
     * @param string $sNombreFormulario Nombre del formulario que se está manejando
     * @param array  $prevPK            Clave primaria previa del registro modificado
     * @param array  $dbVars            Variables a insertar en la base de datos, con comillas
     * @param array  $formVars          Variables del formulario de inserción
     *
     * @return void
     */
    function event_deshacerPrecondicionUpdate($sNombreFormulario, $prevPK, $dbVars, $formVars)
    {
    }

    /**
     * Procedimiento de clase que intenta describir la tabla indicada por $sNombreTabla
     * en el formato esperado por el constructor de PaloEntidad
     *
     * @param object $oDB           Objeto de tipo paloDB que contiene la conexión abierta a la DB
     * @param string $sNombreTabla  Nombre de la tabla a ser descrita
     *
     * @return array Descripción de la tabla en formato de PaloEntidad
     */
    function describirTabla(&$oDB, $sNombreTabla)
    {
        $listaColumnas =& $oDB->fetchTable("DESCRIBE $sNombreTabla", TRUE);
        if (is_array($listaColumnas)) {
            $descTabla = array(
                "tabla"     =>  $sNombreTabla,
                "campos"    =>  array());
            foreach ($listaColumnas as $tuplaColumna) {
                $sNombreColumna = $tuplaColumna["Field"];
                $descColumna = array();

                // Construir los atributos de clave primaria
                if (strpos($tuplaColumna["Key"], "PRI") !== FALSE) {
                    $descColumna["PRIMARY"] = TRUE;
                } else if ($tuplaColumna["Null"] == "YES") {
                    $descColumna["NULL"] = TRUE;
                }

                // Verificar si tiene atributo de autoincremento
                if (stristr($tuplaColumna["Extra"], "auto_increment")) {
                    $descColumna["AUTOINC"] = TRUE;
                }

                // Deducir el tipo de dato SQL
                if (ereg('^([[:alpha:]]+)(\((.+)\))?[[:space:]]*([[:alnum:]]*)$',
                    $tuplaColumna["Type"], $regs)) {
//                    print_r($regs);print "<br>\n";
                    list(, $sTipoSQL, , $sLongitudSQL, $sAttrSQL) = $regs;
                    $sTipoSQL = strtoupper($sTipoSQL);

                    // Verificar las traducciones de tipos de datos
                    if ($sTipoSQL == "TEXT") $sTipoSQL = "BLOB";
                    if ($sTipoSQL == "ENUM") {
                        $sTipoSQL = "CHAR";
                        $listaEnum = split(",", $sLongitudSQL);
                        $listaEnum = array_map("remover_comillas", $listaEnum);
                        $sLongitudSQL = 1;
                        $descColumna["ENUM"] = $listaEnum;
                    }
                    if (stristr($sAttrSQL, "unsigned")) $descColumna["SQLATTR"] = "UNSIGNED";

                    // Verificar si se debe recoger la longitud del campo
                    if (in_array($sTipoSQL, array("CHAR", "VARCHAR", "FLOAT", "DOUBLE", "DECIMAL", "NUMERIC"))) {
                        $descColumna["SQLLEN"] = $sLongitudSQL;
                    } else {
                        if (in_array($sTipoSQL, array("TINYINT", "SMALLINT", "MEDIUMINT", "INT", "INTEGER", "BIGINT"))) {
                            if (!(isset($descColumna["SQLATTR"]) && $descColumna["SQLATTR"] == "UNSIGNED")) {
                                $sLongitudSQL--;
                            }
                            if ($sLongitudSQL < 1) $sLongitudSQL = 1;

                            // Determinar la longitud más apropiada para la columna
                            if ($sLongitudSQL > 0) $sTipoSQL = "TINYINT";
                            if ($sLongitudSQL > 3) $sTipoSQL = "SMALLINT";
                            if ($sLongitudSQL > 5) $sTipoSQL = "MEDIUMINT";
                            if ($sLongitudSQL > 8) $sTipoSQL = "INT";
                            if ($sLongitudSQL > 11) $sTipoSQL = "BIGINT";

                            // Aprovechar longitudes por omisión
                            if ($sTipoSQL == "TINYINT" && $sLongitudSQL == 3) $sLongitudSQL = NULL;
                            if ($sTipoSQL == "SMALLINT" && $sLongitudSQL == 5) $sLongitudSQL = NULL;
                            if ($sTipoSQL == "MEDIUMINT" && $sLongitudSQL == 8) $sLongitudSQL = NULL;
                            if ($sTipoSQL == "INT" && $sLongitudSQL == 10) $sLongitudSQL = NULL;
                            if ($sTipoSQL == "BIGINT" && $sLongitudSQL == 20) $sLongitudSQL = NULL;

                            if (!is_null($sLongitudSQL)) $descColumna["SQLLEN"] = $sLongitudSQL;
                        }
                    }

                    // Asignar el tipo de columna
                    $descColumna["SQLTYPE"] = $sTipoSQL;

                    // Intentar decidir educadamente el propósito del campo
                    if ($descColumna["SQLTYPE"] == "VARCHAR" || $descColumna["SQLTYPE"] == "CHAR") {
                        if (eregi("email", $sNombreColumna) || eregi("correo", $sNombreColumna)) {
                            // El nombre luce como un campo de correo electrónico
                            $descColumna["REGEXP"] = "[a-zA-Z0-9_\-\.]+@[a-zA-Z0-9_\-]+\.[a-zA-Z0-9\-\.]+";
                        }
                    }
                }

                $descTabla["campos"][$sNombreColumna] = $descColumna;
            }
        } else {
            $descTabla = NULL;
        }
        return $descTabla;
    }

    // Código movido de paloEntidad2

    function mostrarListadoEntidad($sqlSelect, $arrHeader, $tituloListado="Listado de items", $arrLayout="")
    {
        $mensajeNoData = "";
        $arrTabla = $this->_db->fetchTable($sqlSelect);
        // Aqui chequeo de errores

        $this->_tpl->definirObjetoFuncionesTabla($this->_insFunc);

        return $this->_tpl->crearTabla($arrHeader, $arrTabla, $tituloListado,
                                $width="", $numcols="", $mensajeNoData, $arrLayout);
    }

    function definirObjetoFunciones(&$objetoFuncions)
    {
        $this->_insFunc =& $objetoFunciones;
    }
}

?>
