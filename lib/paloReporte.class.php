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
// $Id: paloReporte.class.php,v 1.1.1.1 2006/03/03 21:59:09 ainiguez Exp $

/*
    Clase que implementa un reporte en forma tabular a partir de una petición
    SQL con porciones construidas sobre la marcha para implementar lo siguiente:
    * Paginación (requiere reemplazar columnas con COUNT, y agregar LIMIT)
    * Ordenamiento por columnas específicas (requiere agregar ORDER BY)
    * Modificación opcional sobre la marcha de cada una de las columnas por
      medio de una función a sobrecargar en una subclase
 */
class PaloReporte
{
    var $_db;               // Instancia de clase paloDB usada para las operaciones DB
    var $_tpl;              // Instancia de clase paloTemplate usada para construir plantillas
    var $_msMensajeError;   // Mensaje de error sobre la última operación sobre la tabla
    var $_listaReportes;    // Lista de reportes asociados a esta instancia

    /**
     * Constructor que recibe la especificación del objeto de base de datos, y del
     * conjunto de plantillas a usar.
     *
     * @param object $oDB Instancia de clase paloDB que implementa la conexin a DB
     * @param object $oPlantillas Instancia de clase paloTemplate que contiene plantillas
     *
     * @return void
     */
    function PaloReporte(&$oDB, &$oPlantillas)
    {
        $this->_msMensajeError = "";

        // Validar que los parámetros son correctos
        if (!is_a($oDB, "paloDB")) die("PaloEntidad::PaloEntidad() - Objeto \$oDB no es de clase paloDB");
        if (!is_a($oPlantillas, "PaloTemplate")) die("PaloEntidad::PaloEntidad() - Objeto \$oPlantillas no es de clase PaloTemplate");

        // Completar la información de la tabla con los regexp y las longitudes
        $this->_db =& $oDB;
        $this->_tpl =& $oPlantillas;
        $this->_listaReportes = array();
    }

    function & getMessage()
    {
        return $this->_msMensajeError;
    }
    function & getDB()
    {
        return $this->_db;
    }
    function & getTpl()
    {
        return $this->_tpl;
    }
    function setMessage($s)
    {
        $this->_msMensajeError = $s;
    }

    /*
        La definición del reporte debe tener el siguiente formato:
        $defReporte = array(
            "DATA_COLS"     =>  array("a.id_clave", "a.col1", "a.col2", "b.col1", "b.col2"),
            "PRIMARY_KEY"   =>  array(      ^
                                            |
                "id_clave"    =>  0, -------|
            ),
            "TITLE"         =>  "Reporte de prueba",
            "FROM"          =>  "tabla1 AS a, tabla2 AS b",
            "CONST_WHERE"   =>  "a.id_clave = b.id_clave_foranea AND b.activo = 1",
            "PAGECHOICE"    =>  array(20, 40, 60),
            "ORDERING"      =>  array(
                "DEFAULT"   =>  array(1, 2, 3, 4),
                "BY_COL2"   =>  array(2, 1, 3, 4)),
            "BASE_URL"      =>  "listado.php?action=mostrarlista",
            "FILTRO"        =>  "",
            "HEADERS"       =>  array(
                "<input type='submit' name='in_remover' value='Remover' />",
                array("Columna 1", "DEFAULT"),
                array("Columna 2", "BY_COL2"),
                "Columna 3",
                "Columna 4"
            ),
            ROW             =>  array(
                "<input type='checkbox' name='in_lista_remover' value='{_DATA_PRIMARY_KEY}'>",
                "{_DATA_1}",
                "{_DATA_2}",
                "{_DATA_3}",
                "{_DATA_4}",
                "<a href=\"?action=modificar&clave={_DATA_PRIMARY_KEY}&{_REPORT_STATE}\">Modificar</a>",
            ),
        );

        TITLE       Título del reporte que aparece en la parte superior.
        DATA_COLS   Columnas del SELECT que deben usarse para construir el SELECT. Al hacer
                    referencia a estas columnas, se cuenta desde 0 en caso de no especificar
                    índices asociativos.
        PRIMARY_KEY Arreglo que contiene los índices (numéricos o asociativos) a las columnas
                    declaradas en DATA_COLS que sirven como clave primaria para identificar el
                    registro de la fila específica. Si no se especifica, se asume que la totalidad
                    de la tupla de la base de datos es la clave primaria.
        FILTRO      Opcional: Código HTML que se agrega como fila adicional antes de las
                    cabeceras, para agregar controles de filtro al reporte.
        FROM        Cadena que declara todo lo que va desde el FROM hasta el WHERE del SELECT
                    construido para la recuperación. Aquí van los LEFT JOIN, UNION, ON <condicion>
                    que sean necesarios para la construcción adecuada de la petición SQL.
        LEFT JOIN   Si está presente, arreglo que contiene pares (FROM=>val, ON=>val) que
                    se asumen parte de una porción LEFT JOIN de la petición SQL. Esta
                    especificación se ofrece para poder dar soporte a la manipulación de
                    condiciones variables via filtro en la cláusula LEFT JOIN
        CONST_WHERE Parte constante de la condición WHERE para obedecer la unión de tablas indicadas
                    en la parte FROM. Las condiciones WHERE generadas sobre la marcha se usarán
                    ANTES de esta cadena, así que se pueden aadir cláusulas GROUP BY en esta
                    cadena. Si no se especifica, se asume vacía.
        PAGECHOICE  Lista de elecciones de registros por página del reporte a mostrar. Si no se
                    indica un valor específico, se asume array(10) (siempre 10 registros por página)
        ORDERING    Arreglo que declara ordenamientos posibles. Cada ordenamiento se declara como
                    una entrada de arreglo asociativo, cuya clave es el nombre del ordenamiento
                    y cuyo contenido es otro arreglo, que lista índices en el arreglo de columnas
                    de DATA_COLS (numéricos o asociativos) en el orden que construye el ordenamiento
                    indicado. Un ordenamiento debe de declararse aquí antes de poderse usar en
                    la opción HEADERS. Si se declara un ordenamiento llamado DEFAULT, este
                    ordenamiento se usará si no se especifica otro en las columnas.
        BASE_URL    Varias funciones en el reporte requieren construir enlaces con URLs. Esta opción
                    indica el URL base, con parámetros, al que se añadirá las opciones construidas
                    por la generación del reporte.
        HEADERS     Arreglo de cabeceras que se usan como cabeceras de la tabla HTML generada.
                    Si no se especifica, se usan los nombres de las columnas de SQL, y se extiende
                    con "DATA". Opcionalmente, un elemento puede consistir de una tupla, cuyo
                    primer elemento es el texto a mostrar como cabecera, y el segundo elemento, el
                    ordenamiento asociado a esta columna, tal como se declara en ORDERING. Entonces
                    se construye un enlace que al ser usado, hace generar el reporte con el
                    ordenamiento indicado.
        ROW         Arreglo que declara los textos a ser usados para la construcción de la fila de
                    la tabla. Además de las columnas indicadas en DATA_COLS, se puede hacer
                    referencia a columnas adicionales agregadas durante el evento
                    event_proveerCampos() de una subclase, los cuales se consideran agregados al
                    final de la tupla original devuelta por la base de datos.
        MENSAJE_VACIO Mensaje opcional a mostrar si no se encuentran registros. Por omisión es la
                    cadena "No se encuentran registros" sin adornos HTML

        Durante la generación de los textos del arreglo, se disponen de las siguientes macros:
        {_DATA_n}       Para n entre 0 y el total de columnas de la tupla leída y de los valores
                        provistos por la funcion event_proveeCampos(), se reemplaza por el valor
                        n-ésimo contando desde 0.
        {_DATA_PRIMARY_KEY}  Valor de la clave primaria para la fila actual de la tabla. Este valor
                        es una cadena codificada que representa un arreglo. Al ser decodificada
                        mediante:
                            $tupla = unserialize(urldecode($clave))
                        se obtiene la clave primaria del registro de la fila actual.
        {_REPORT_STATE} Estado de todas las variables mantenidas por el reporte como una cadena
                        GET (var1=valor&var2=valor&...). Al ingresar esto en un enlace, se obtiene
                        un enlace que reproduce el estado actual del reporte. Esto es til cuando
                        se debe navegar a un formulario, y se debe regresar al reporte en el estado
                        anterior a la navegación.

        Al generar el reporte, se realiza lo siguiente:

     */

    /**
     * Procedimiento que registra un reporte en esta instancia del objeto.
     *
     * @param string    $sNombreReporte Nombre simblico del reporte
     * @param array     $defReporte     Definición del reporte
     *
     * @return boolean  TRUE si el reporte es aceptado, FALSE si no lo es, con mensaje de error
     */
    function definirReporte($sNombreReporte, $defReporte)
    {
        $bExito = FALSE;
        $bValido = TRUE;

        // Se verifica que $defReporte es un arreglo
        if ($bValido && !is_array($defReporte)) {
            $bValido = FALSE;
            $this->setMessage("PaloReporte::definirReporte() - defReporte no es un arreglo");
        }
        // Se verifica si existe el elemento DATA_COLS
        if ($bValido && !isset($defReporte["DATA_COLS"])) {
            $bValido = FALSE;
            $this->setMessage("PaloReporte::definirReporte() - se requiere definición de DATA_COLS (columnas requeridas o disponibles en consulta)");
        }
        // Se verifica si DATA_COLS es un arreglo
        if ($bValido && !is_array($defReporte["DATA_COLS"])) {
            $bValido = FALSE;
            $this->setMessage("PaloReporte::definirReporte() - DATA_COLS debe de ser un arreglo");
        }
        // Se verifica que existe el elemento FROM
        if ($bValido && !isset($defReporte["FROM"])) {
            $bValido = FALSE;
            $this->setMessage("PaloReporte::definirReporte() - se requiere definición de FROM (tabla(s) para consulta)");
        }
        // Se verifica que LEFT JOIN, en caso de existir, sea un arreglo en formato correcto
        if ($bValido && isset($defReporte['LEFT JOIN'])) {
            if (!is_array($defReporte['LEFT JOIN'])) {
                $bValido = FALSE;
                $this->setMessage("PaloReporte::definirReporte() - la definición de LEFT JOIN debe de ser un arreglo");
            } else foreach ($defReporte['LEFT JOIN'] as $i => $tuplaJoin) {
                if (!is_array($tuplaJoin)) {
                    $bValido = FALSE;
                    $this->setMessage("PaloReporte::definirReporte() - elemento en posición '$i' de LEFT JOIN debe de ser un arreglo");
                } else if (!isset($tuplaJoin['FROM'])) {
                    $bValido = FALSE;
                    $this->setMessage("PaloReporte::definirReporte() - elemento en posición '$i' de LEFT JOIN no define FROM para tabla de LEFT JOIN");
                } else if (!isset($tuplaJoin['ON'])) {
                    $bValido = FALSE;
                    $this->setMessage("PaloReporte::definirReporte() - elemento en posición '$i' de LEFT JOIN no define ON para condición de unión de LEFT JOIN");
                }
            }
        }
        // Se verifica que PRIMARY_KEY, en caso de existir, sea un arreglo
        if ($bValido && isset($defReporte["PRIMARY_KEY"]) && !is_array($defReporte["PRIMARY_KEY"])) {
            $bValido = FALSE;
            $this->setMessage("PaloReporte::definirReporte() - PRIMARY_KEY debe de ser un arreglo");
        }
        // Se verifica que PRIMARY_KEY, en caso de existir, slo referencie columnas que existen
        // en DATA_COLS
        if ($bValido && isset($defReporte["PRIMARY_KEY"])) {
            foreach ($defReporte["PRIMARY_KEY"] as $k) {
                if (!isset($defReporte["DATA_COLS"][$k])) {
                    $bValido = FALSE;
                    $this->setMessage("PaloReporte::definirReporte() - PRIMARY_KEY referencia columna inexistente $k");
                    break;
                }
            }
        }
        // Se verifica que PAGECHOICE, en caso de existir, sea un arreglo. Si es un escalar, se
        // verifica que sea numérico, entero y positivo, y se agrega como un arreglo de 1 elemento.
        // Todos los elementos deben ser enteros y positivos.
        if ($bValido && isset($defReporte["PAGECHOICE"])) {
            if (!is_array($defReporte["PAGECHOICE"])) {
                $defReporte["PAGECHOICE"] = array($defReporte["PAGECHOICE"]);
            }
            foreach ($defReporte["PAGECHOICE"] as $numRegistros) {
                // Si no es arreglo, se verifica que el número de registros sea entero
                if (!ereg("^[[:digit:]]+$", "$numRegistros") || $numRegistros <= 0) {
                    $bValido = FALSE;
                    $this->setMessage("PaloReporte::definirReporte() - valor de '$numRegistros' para PAGECHOICE no es un entero positivo");
                    break;
                }
            }
        }
        // Se verifica que exista el elemento HEADERS y que sea un arreglo
        if ($bValido && !isset($defReporte["HEADERS"])) {
            $bValido = FALSE;
            $this->setMessage("PaloReporte::definirReporte() - se requiere definición de HEADERS (lista de plantillas para fila de cabecera)");
        }
        if ($bValido && !is_array($defReporte["HEADERS"])) {
            $bValido = FALSE;
            $this->setMessage("PaloReporte::definirReporte() - HEADERS debe de ser un arreglo");
        }
        // Se verifica que exista el elemento ROW y que sea un arreglo
        if ($bValido && !isset($defReporte["ROW"])) {
            $bValido = FALSE;
            $this->setMessage("PaloReporte::definirReporte() - se requiere definición de ROW (lista de plantillas para fila de datos)");
        }
        if ($bValido && !is_array($defReporte["ROW"])) {
            $bValido = FALSE;
            $this->setMessage("PaloReporte::definirReporte() - ROW debe de ser un arreglo");
        }
        // Se verifica que ROW y HEADERS tengan el mismo número de elementos
        if ($bValido && count($defReporte["HEADERS"]) != count($defReporte["ROW"])) {
            $bValido = FALSE;
            $this->setMessage("PaloReporte::definirReporte() - HEADERS y ROW tienen distinto número de elementos");
        }
        // Se verifica que el elemento ORDERING, si existe, sea un arreglo, y cada ordenamiento
        // referencie nicamente columnas de DATA_COLS
        if ($bValido && isset($defReporte["ORDERING"])) {
            if (!is_array($defReporte["ORDERING"])) {
                $bValido = FALSE;
                $this->setMessage("PaloReporte::definirReporte() - ORDERING debe de ser un arreglo");
            } else {
                foreach ($defReporte["ORDERING"] as $sNombreOrden => $tuplaOrden) {
                    foreach ($tuplaOrden as $i => $k) {
                        if (eregi("^(.*)[[:space:]]+desc$", $k, $regs)) {
                            $k = $regs[1];
                        }
                        if (!isset($defReporte["DATA_COLS"][$k])) {
                            $bValido = FALSE;
                            $this->setMessage("PaloReporte::definirReporte() - ordenamiento $sNombreOrden referencia columna inexistente $k");
                            break;
                        }
                    }
                    if (!$bValido) break;
                }
            }
        }
        // Se verifica que los elementos de HEADERS que son tuplas, referencien arreglos existentes
        // en ORDERING
        if ($bValido) {
            foreach ($defReporte["HEADERS"] as $cabecera) {
                if (is_array($cabecera)) {
                    if (!isset($defReporte["ORDERING"])) {
                        $bValido = FALSE;
                        $this->setMessage("PaloReporte::definirReporte() - cabecera requiere un ordenamiento, pero no se ha definido ORDERING");
                    } else if (!isset($defReporte["ORDERING"][$cabecera[1]])) {
                        $bValido = FALSE;
                        $this->setMessage("PaloReporte::definirReporte() - cabecera referencia ordenamiento inexistente ".$cabecera[1]);
                    }
                }
                if (!$bValido) break;
            }
        }

        // Completar la información faltante en la definición del reporte
        if ($bValido) {
            if (!isset($defReporte["PRIMARY_KEY"])) $defReporte["PRIMARY_KEY"] = array_keys($defReporte["DATA_COLS"]);
            if (!isset($defReporte["BASE_URL"])) $defReporte["BASE_URL"] = "?";
            if (!isset($defReporte["TITLE"])) $defReporte["TITLE"] = "Listado de items";
            if (!isset($defReporte["PAGECHOICE"]) && !in_array('PAGECHOICE', array_keys($defReporte))) $defReporte["PAGECHOICE"] = array(10);
            $this->_listaReportes[$sNombreReporte] = $defReporte;
            $bExito = TRUE;
        }
        return $bExito;
    }

    /**
     * Procedimiento que genera la tabla del reporte a partir del nombre del reporte y de las
     * variables GET y POST disponibles en el formulario.
     *
     * @param string    $sNombreReporte Nombre del reporte que se debe mostrar
     * @param array     $_GET           Referencia a las variables POST
     * @param array     $_POST          Referencia a las variables POST
     *
     * @return string   Código HTML de la tabla generada
     */
    function generarReporte($sNombreReporte, &$_GET,  &$_POST)
    {
        $sCodigoTabla = "";

        // Verificar que el reporte indicado existe
        if (!isset($this->_listaReportes[$sNombreReporte])) {
            $sCodigoTabla = $this->_tpl->crearAlerta("warning",
                "PaloReporte::generarReporte()",
                ($this->_msMensajeError = "PaloReporte::generarReporte() - no ".
                "se encuentra reporte de nombre '$sNombreReporte'"));
        } else {
            // Obtener referencia al reporte deseado
            $defReporte =& $this->_listaReportes[$sNombreReporte];

            // Filtrar las variables que pertenecen al reporte que está siendo manejado
            $formVars = $this->_privado_filtrarVariablesReporte($sNombreReporte, $_GET, $_POST);

            // Verificar que los valores son correctos
            if (isset($formVars["filas_por_pagina"])) {
                $formVars["filas_por_pagina"] = (int)$formVars["filas_por_pagina"];
                if (!in_array($formVars["filas_por_pagina"], $defReporte["PAGECHOICE"])) {
                    $formVars["filas_por_pagina"] = $defReporte["PAGECHOICE"][0];
                }
            } else {
                $formVars["filas_por_pagina"] = $defReporte["PAGECHOICE"][0];
            }
            if (isset($formVars["pagina"])) {
                $formVars["pagina"] = (int)$formVars["pagina"];
                if ($formVars["pagina"] <= 0) $formVars["pagina"] = 1;
            } else {
                $formVars["pagina"] = 1;
            }
            if (isset($defReporte["ORDERING"])) {
                if (isset($formVars["orden"])) {
                    if (!in_array($formVars["orden"], array_keys($defReporte["ORDERING"]))) {
                        unset($formVars["orden"]);
                    }
                }
                if (!isset($formVars["orden"]) && isset($defReporte["ORDERING"]["DEFAULT"])) {
                    $formVars["orden"] = "DEFAULT";
                }
            } else {
                unset($formVars["orden"]);
            }

            // Filtrar las variables que pertenecen al filtro por reporte
            $varsFiltro = $this->event_recogerVariablesFiltro($sNombreReporte, $_GET, $_POST);
            if (!is_array($varsFiltro)) $varsFiltro = array();

            // Si la orden SELECT debe de incluir un GROUP BY, no puede usarse un
            // COUNT(*) para determinar por adelantado el número de registros a
            // recuperar. Entonces se debe de recuperar todo el recordset y recortarlo
            // en código de PHP. Si la orden SELECT no contiene GROUP BY entonces se
            // puede manipular con COUNT(*).
            $sCondicionAdicional = $this->event_construirCondicionFiltro($sNombreReporte, $varsFiltro);
            if (isset($defReporte["CONST_WHERE"]) && eregi("GROUP[[:space:]]+BY", $defReporte["CONST_WHERE"])) {
                list($iNumPagina, $listaTuplas, $iTotalRegistros, $iTotalPaginas) = $this->_privado_paginarTabla_PHP(
                    $defReporte,
                    $formVars["filas_por_pagina"],
                    $formVars["pagina"],
                    isset($formVars["orden"]) ? $formVars["orden"] : NULL,
                    $sCondicionAdicional);
            } else {
                list($iNumPagina, $listaTuplas, $iTotalRegistros, $iTotalPaginas) = $this->_privado_paginarTabla_LIMIT(
                    $defReporte,
                    $formVars["filas_por_pagina"],
                    $formVars["pagina"],
                    isset($formVars["orden"]) ? $formVars["orden"] : NULL,
                    $sCondicionAdicional);
            }

            // Verificar si el SELECT se ejecut correctamente
            if (!is_array($listaTuplas)) {
                $sCodigoTabla = $this->_tpl->crearAlerta("error",
                    "PaloReporte::generarReporte()",
                    ($this->_msMensajeError = "PaloReporte::generarReporte() - al generar ".
                    "'$sNombreReporte' - ".$this->getMessage()));
            } else {
                $formVars["pagina"] = $iNumPagina;
                $sPrefijoForm = $this->_privado_prefijoReporte($sNombreReporte);

                // Construir el contenido de _REPORT_STATE
                $report_state = array($sPrefijoForm."filas_por_pagina" =>  $formVars["filas_por_pagina"]);
                if ($formVars["pagina"] > 0) $report_state[$sPrefijoForm."pagina"] = $formVars["pagina"];
                if (isset($formVars["orden"])) $report_state[$sPrefijoForm."orden"] = $formVars["orden"];
                $_REPORT_STATE = "";
                foreach (array_merge($report_state, $varsFiltro) as $sClave => $sValor) {
                    if ($_REPORT_STATE != "") $_REPORT_STATE .= "&";
//                    $_REPORT_STATE .= urlencode($sClave)."=".urlencode($sValor);
                    $_REPORT_STATE .= PaloReporte::_privado_array_urlencode($sClave, $sValor);
                }
//                print $_REPORT_STATE;

                // Procesar cada una de las tuplas a través del evento event_proveerCampos()
                $listaTuplasTabla = array();
                foreach ($listaTuplas as $_tuplaSQL) {
                    $tuplaSQL = array();
                    $tuplaSQL_raw = array();
                    $i = 0;
                    foreach ($defReporte['DATA_COLS'] as $k => $col) {
                        $tuplaSQL_raw[$k] = $_tuplaSQL[$i];
                        $tuplaSQL[$k] = is_null($_tuplaSQL[$i]) ? NULL : htmlentities($_tuplaSQL[$i], ENT_COMPAT, "UTF-8");
                        $i++;
                    }


                    // Construir los contenidos de la variable de _DATA_PRIMARY_KEY
                    $primaryKey = array();
                    foreach ($defReporte["PRIMARY_KEY"] as $k) {
                        $primaryKey[$k] = $tuplaSQL[$k];
                    }

                    $tuplaAdicional = $this->event_proveerCampos(
                        $sNombreReporte,
                        array_merge(
                            $tuplaSQL,
                            array(
                                "PRIMARY_KEY" => urlencode(serialize($primaryKey)),
                                "REPORT_STATE"  =>  $_REPORT_STATE,
                                "_RAW_SQL_ROW"  =>  $tuplaSQL_raw,
                            )));
                    $tuplaTabla = array_merge(
                        $tuplaSQL,
                        $tuplaAdicional,
                        array(
                            "PRIMARY_KEY"   =>  urlencode(serialize($primaryKey)),
                            "REPORT_STATE"  =>  $_REPORT_STATE,
                        ));
                    $listaTuplasTabla[] = $tuplaTabla;
                }
/*
                print "<pre>";
                print_r($listaTuplasTabla);
                print "</pre>";
*/
                // Construir la cabecera. Para cada columna con una cabecera que sea una tupla,
                // se construye el texto con el enlace apropiado, el cual indica un ordenamiento
                // específico distinto.
                $listaHeaders = array();
                foreach ($defReporte["HEADERS"] as $infoHeader) {
                    if (is_array($infoHeader)) {
                        // Se construye enlace apropiado para el ordenamiento
                        $sTextoCabecera = $infoHeader[0];

                        // Construir el contenido del URL que cambia el ordenamiento
                        $report_ordenamiento = array($sPrefijoForm."filas_por_pagina" =>  $formVars["filas_por_pagina"]);
                        if ($formVars["pagina"] > 0) $report_ordenamiento[$sPrefijoForm."pagina"] = $formVars["pagina"];
                        $report_ordenamiento[$sPrefijoForm."orden"] = $infoHeader[1];
                        $_REPORT_ORDENAMIENTO = "";
                        foreach (array_merge($report_ordenamiento, $varsFiltro) as $sClave => $sValor) {
                            if ($_REPORT_ORDENAMIENTO != "") $_REPORT_ORDENAMIENTO .= "&";
//                            $_REPORT_ORDENAMIENTO .= urlencode($sClave)."=".urlencode($sValor);
                            $_REPORT_ORDENAMIENTO .= PaloReporte::_privado_array_urlencode($sClave, $sValor);
                        }
                        $_REPORT_ORDENAMIENTO = $defReporte["BASE_URL"]."&".$_REPORT_ORDENAMIENTO;
                        $sTextoCabecera = "<a href=\"$_REPORT_ORDENAMIENTO\">$sTextoCabecera</a>";
                        $listaHeaders[] = $sTextoCabecera;
                    } else {
                        // Cadena simple de texto se usa directamente
                        $listaHeaders[] = $infoHeader;
                    }
                }

                // Construir la fila del título
                $sTituloReporte = $defReporte["TITLE"];
                $this->_tpl->assign("NUM_REGISTROS", $iTotalRegistros);
                if ($formVars["pagina"] > 0) {
                    $this->_tpl->assign("NUM_PAGINA", $formVars["pagina"]);
                    $this->_tpl->assign("TOTAL_PAGINAS", $iTotalPaginas);

                    // Construir los URLs para la paginación
                    $url_pag_anterior = array($sPrefijoForm."filas_por_pagina" =>  $formVars["filas_por_pagina"]);
                    $url_pag_siguiente = $url_pag_anterior;
                    $url_pag_anterior[$sPrefijoForm."pagina"] = $formVars["pagina"] - 1;
                    $url_pag_siguiente[$sPrefijoForm."pagina"] = $formVars["pagina"] + 1;
                    if (isset($formVars["orden"])) {
                        $url_pag_anterior[$sPrefijoForm."orden"] = $formVars["orden"];
                        $url_pag_siguiente[$sPrefijoForm."orden"] = $formVars["orden"];
                    }
                    $sUrlAnterior = $defReporte["BASE_URL"];
                    $sUrlSiguiente = $defReporte["BASE_URL"];
                    foreach (array_merge($url_pag_anterior, $varsFiltro) as $sClave => $sValor) {
                        if ($sUrlAnterior != "") $sUrlAnterior .= "&";
//                        $sUrlAnterior .= urlencode($sClave)."=".urlencode($sValor);
                        $sUrlAnterior .= PaloReporte::_privado_array_urlencode($sClave, $sValor);
                    }
                    foreach (array_merge($url_pag_siguiente, $varsFiltro) as $sClave => $sValor) {
                        if ($sUrlSiguiente != "") $sUrlSiguiente .= "&";
//                        $sUrlSiguiente .= urlencode($sClave)."=".urlencode($sValor);
                        $sUrlSiguiente .= PaloReporte::_privado_array_urlencode($sClave, $sValor);
                    }
                    $this->_tpl->assign("URL_PAG_ANTERIOR", $sUrlAnterior);
                    $this->_tpl->assign("URL_PAG_SIGUIENTE", $sUrlSiguiente);

                    // Construir el contenido del combo de páginas disponibles
                    $this->_tpl->assign("IN_NUEVA_PAG_REPORTE", $sPrefijoForm."pagina");
                    $this->_tpl->assign("NOMBRE_REPORTE", $sNombreReporte);
                    $sOpcionesPag = "";
                    for ($i = 1; $i <= $iTotalPaginas; $i++) {
                        $sSelected = ($i == $formVars["pagina"]) ? "selected" : "";
                        $sOpcionesPag .= "<option value=\"$i\" $sSelected >$i</option>\n";
                    }
                    $this->_tpl->assign("COMBO_LISTA_PAG", $sOpcionesPag);

                    switch ($formVars["pagina"]) {
                    case 1:
                        $this->_tpl->parse("NAV_TOOL", "tpl__nav_tool_inicio");
                        break;
                    case $iTotalPaginas:
                        $this->_tpl->parse("NAV_TOOL", "tpl__nav_tool_final");
                        break;
                    default:
                        $this->_tpl->parse("NAV_TOOL", "tpl__nav_tool_medio");
                        break;
                    }
                } else {
                    // No hay necesidad de paginación
                    $this->_tpl->assign("NUM_PAGINA", 1);
                    $this->_tpl->assign("TOTAL_PAGINAS", 1);
                    $this->_tpl->assign("NAV_TOOL", "&nbsp;");
                }

                // Verificar si se requiere mostrar una selección de
                // los números de registros por páginas disponibles
                $this->_tpl->assign("COMBO_REG_POR_PAGINA", "");
                if ($iNumPagina > 0 && count($defReporte["PAGECHOICE"]) > 0) {
                    $sTextoCombo = "<select name=\"$sPrefijoForm"."filas_por_pagina\" onChange=\"document.$sNombreReporte.submit()\" >\n";
                    foreach ($defReporte["PAGECHOICE"] as $iValor) {
                        $sTextoCombo .= "<option value=\"$iValor\" ".(($iValor == $formVars["filas_por_pagina"]) ? "selected" : "").">$iValor</option>\n";
                    }
                    $sTextoCombo .= "</select>&nbsp;registros/p&aacute;gina\n";
                    $this->_tpl->assign("COMBO_REG_POR_PAGINA", $sTextoCombo);
                }

                // Reemplazar cualquier ocurrencia de {_REPORT_STATE} con el valor real
                $defTupla = array();
                foreach ($defReporte['ROW'] as $key => $value) {
                    if (is_array($value)) {
                        $defTupla[$key] = $value;
                        if (isset($value[0])) $defTupla[$key][0] = ereg_replace('{_REPORT_STATE}', $_REPORT_STATE, $value[0]);
                        if (isset($value['value'])) $defTupla[$key]['value'] = ereg_replace('{_REPORT_STATE}', $_REPORT_STATE, $value['value']);
//                        print_r($defTupla[$key]);print "<br>\n";
                    } else {
                        $defTupla[$key] = ereg_replace('{_REPORT_STATE}', $_REPORT_STATE, $value);
                    }
                }

                // Generar la fila de navegación
                $this->_tpl->parse("REPORTE_NAV_TEXTO", "tpl__table_nav_row");
                $sTextoNavegacion = $this->_tpl->fetch("REPORTE_NAV_TEXTO");
                if (isset($defReporte["FILTRO"])) $sTextoNavegacion .= $defReporte["FILTRO"];
                $sTextoNavegacion .= $this->event_construirFormularioFiltro($sNombreReporte, $varsFiltro);

                $sActionURL = $defReporte["BASE_URL"]."&".$_REPORT_STATE;
                $sCodigoTabla =
                    "<form method=\"POST\" name=\"$sNombreReporte\" action=\"$sActionURL\">".
                    $this->_tpl->crearTabla(
                        $listaHeaders,
                        $listaTuplasTabla,
                        $sTituloReporte.$sTextoNavegacion,
                        "", "",
                        isset($defReporte["MENSAJE_VACIO"]) ? $defReporte["MENSAJE_VACIO"] : "No se encuentran registros",
                        $defTupla).
                    "</form>";
            }
        }

        return $sCodigoTabla;
    }

   // Procedimiento que puede codificar variables tanto escalares como arreglos, como formato urlencode
   function _privado_array_urlencode($sClave, &$sValor)
   {
      if (!is_array($sValor)) {
         return urlencode($sClave)."=".urlencode($sValor);
      } else {
         $sUrlEncoding = '';

         foreach ($sValor as $iPos => $val) {
            if ($sUrlEncoding != '') $sUrlEncoding .= '&';
            $sUrlEncoding .= PaloReporte::_privado_array_urlencode($sClave.'['.$iPos.']', $val);
         }
         return $sUrlEncoding;
      }
   }

    /**
     * Procedimiento que recoge todas las variables que pasan a través del GET y del POST y
     * que tengan el prefijo del reporte manejado, y devuelve la lista de estas variables en otro
     * arreglo, una vez removido el prefijo de las variables. Las variables de interés son:
     * * Nmero de registros por página
     * * Nmero de página deseado
     * * Ordenamiento seleccionado para presentación.
     */
    function _privado_filtrarVariablesReporte($sNombreReporte, &$_GET, &$_POST)
    {
        $sPrefijoForm = $this->_privado_prefijoReporte($sNombreReporte);
        $formVars = array();
        foreach ($_GET as $sKey => $val) {
            if (substr($sKey, 0, strlen($sPrefijoForm)) == $sPrefijoForm) {
                $formVars[substr($sKey, strlen($sPrefijoForm))] = $val;
            }
        }
        foreach ($_POST as $sKey => $val) {
            if (substr($sKey, 0, strlen($sPrefijoForm)) == $sPrefijoForm) {
                $formVars[substr($sKey, strlen($sPrefijoForm))] = $val;
            }
        }
        return $formVars;
    }

    /**
     * Procedimiento que construye el prefijo que se aade a las variables de formulario para
     * distinguir este reporte de otros posibles reportes que existan en la página.
     */
    function _privado_prefijoReporte($sNombreReporte)
    {
        return "in_".$sNombreReporte."_";
    }

    /**
     * Procedimiento privado que construye y ejecuta el SELECT asociado al reporte. Esta versión
     * lista todos los registros y luego recorta el recordset para ajustarse a la petición de
     * número de registros por página y número de página.
     *
     * Devuelve una tupla que consiste del número de página, seguido de la matriz de tuplas,
     * seguido de el número total de registros, seguido del total de páginas
     */
    function _privado_paginarTabla_PHP($defReporte, $iNumRegPagina, $iNumPagina, $sOrdenamiento, $sCondicionAdicional)
    {
/*        $this->setMessage("No implementado");
        return array(0, NULL, 0, 0);
*/
        $db =& $this->getDB();
        $tuplaRespuesta = array(0, NULL, 0, 0);

        // Construir la cláusula de ordenamiento
        $sClausulaOrdenamiento = $this->_privado_construirClausulaOrdenamiento($defReporte, $sOrdenamiento);

        // Construir el SELECT verdadero con ordenamiento
        $sPeticionSQL =
            "SELECT ".join(", ", $defReporte["DATA_COLS"])." ".
            "FROM ".$defReporte["FROM"];

        // Separar la condición WHERE de las demás condiciones disponibles
        $sCondicionWHERE = '';
        if (is_array($sCondicionAdicional)) {
            if (isset($sCondicionAdicional['WHERE'])) {
                $sCondicionWHERE = $sCondicionAdicional['WHERE'];
                unset($sCondicionAdicional['WHERE']);
            }
        } else {
            $sCondicionWHERE = $sCondicionAdicional;
        }

        // Construir la condición LEFT JOIN para la consulta. Para cada componente
        // del LEFT JOIN del reporte, si existe un elemento con la misma clave en
        // las condiciones adicionales, se adiciona la condición asumiendo que está
        // en formato AND, de forma parecida al tratamiento de la condición WHERE
        if (isset($defReporte['LEFT JOIN'])) {
            foreach ($defReporte['LEFT JOIN'] as $sKey => $tuplaJoin) {
                $sCondicionJoin = " LEFT JOIN $tuplaJoin[FROM] ON $tuplaJoin[ON]";
                if (is_array($sCondicionAdicional) && isset($sCondicionAdicional[$sKey])) {
                    $sCondicionJoin .= " AND $sCondicionAdicional[$sKey]";
                }
                $sPeticionSQL .= $sCondicionJoin;
            }
        }

        // Agregar la condición WHERE antes de las condiciones constantes del reporte
        if (isset($defReporte["CONST_WHERE"])) {
            if ($sCondicionWHERE != "") $sCondicionWHERE .= " AND ";
            $sCondicionWHERE .= $defReporte["CONST_WHERE"];
        }
        if ($sCondicionWHERE != "")
            $sPeticionSQL .= " WHERE $sCondicionWHERE";

        $sPeticionSQL .= $sClausulaOrdenamiento;
        if (isset($defReporte["DEBUG"]) && $defReporte["DEBUG"]) print "$sPeticionSQL<br/>";
        $listaTuplas =& $db->fetchTable($sPeticionSQL);
        if (is_array($listaTuplas)) {
            // Obtener el total de registros del reporte
            $iNumRegistros = count($listaTuplas);
            $tuplaRespuesta[2] = $iNumRegistros;

            // Verificar si se debe conservar la página
            if (isset($defReporte['PAGECHOICE']) && $iNumRegistros > $iNumRegPagina) {
                $iTotalPaginas = (int)(($iNumRegistros + $iNumRegPagina - 1) / $iNumRegPagina);
                if ($iNumPagina > $iTotalPaginas) $iNumPagina = $iTotalPaginas;
                $tuplaRespuesta[0] = $iNumPagina;
                $tuplaRespuesta[3] = $iTotalPaginas;
                $iOffsetRegistro = ($iNumPagina - 1) * $iNumRegPagina;
            } else {
                $tuplaRespuesta[0] = 0; // No se requiere conservar la página...
                $iOffsetRegistro = 0;
            }

            // Recortar los registros requeridos
            if ($tuplaRespuesta[0] != 0) {
                $tuplaRespuesta[1] = array_slice($listaTuplas, $iOffsetRegistro, $iNumRegPagina);
            } else {
                $tuplaRespuesta[1] = $listaTuplas;
            }
        } else {
            $this->setMessage("Al leer filas - ".$db->errMsg);
        }

        return $tuplaRespuesta;
    }

    /**
     * Procedimiento privado que construye y ejecuta el SELECT asociado al reporte. Esta versión
     * construye un SELECT que cuenta todos los registros a seleccionar, y a continuación construye
     * el SELECT de datos con la cláusula LIMIT (si es necesaria)
     *
     * Devuelve una tupla que consiste del número de página, seguido de la matriz de tuplas,
     * seguido de el número total de registros, seguido del total de páginas
     */
    function _privado_paginarTabla_LIMIT($defReporte, $iNumRegPagina, $iNumPagina, $sOrdenamiento, $sCondicionAdicional)
    {
        $db =& $this->getDB();
        $tuplaRespuesta = array(0, NULL, 0, 0);

        // Construir la petición SQL de cuenta de registros
        $sPeticionSQL =
            "SELECT COUNT(*) ".
            "FROM ".$defReporte["FROM"];

        // Separar la condición WHERE de las demás condiciones disponibles
        $sCondicionWHERE = '';
        if (is_array($sCondicionAdicional)) {
            if (isset($sCondicionAdicional['WHERE'])) {
                $sCondicionWHERE = $sCondicionAdicional['WHERE'];
                unset($sCondicionAdicional['WHERE']);
            }
        } else {
            $sCondicionWHERE = $sCondicionAdicional;
        }

        if (isset($defReporte["CONST_WHERE"])) {
            if ($sCondicionWHERE != "") $sCondicionWHERE .= " AND ";
            $sCondicionWHERE .= $defReporte["CONST_WHERE"];
        }
        if (isset($defReporte['PAGECHOICE'])) {
            if ($sCondicionWHERE != "")
                $sPeticionSQL .= " "."WHERE $sCondicionWHERE";
            if (isset($defReporte["DEBUG"]) && $defReporte["DEBUG"]) print "$sPeticionSQL<br/>";
            $tupla =& $db->getFirstRowQuery($sPeticionSQL);
        } else {
            $tupla = array(0);
        }
        if (!is_array($tupla)) {
            $this->setMessage("Al contar filas - ".$db->errMsg);
        } else {
            // Validar el número de registros, las páginas y la necesidad de LIMIT
            $iNumRegistros = $tupla[0];
            $tuplaRespuesta[2] = $iNumRegistros;
            if (isset($defReporte['PAGECHOICE']) && $iNumRegistros > $iNumRegPagina) {
                $iTotalPaginas = (int)(($iNumRegistros + $iNumRegPagina - 1) / $iNumRegPagina);
                if ($iNumPagina > $iTotalPaginas) $iNumPagina = $iTotalPaginas;
                $tuplaRespuesta[0] = $iNumPagina;
                $tuplaRespuesta[3] = $iTotalPaginas;
                $iOffsetRegistro = ($iNumPagina - 1) * $iNumRegPagina;
                $sClausulaLimite = " LIMIT $iOffsetRegistro,$iNumRegPagina";
            } else {
                $sClausulaLimite = "";
                $tuplaRespuesta[0] = 0; // No se requiere conservar la página...
            }

            // Construir la cláusula de ordenamiento
            $sClausulaOrdenamiento = $this->_privado_construirClausulaOrdenamiento($defReporte, $sOrdenamiento);

            // Construir el SELECT verdadero con ordenamiento
            $sPeticionSQL =
                "SELECT ".join(", ", $defReporte["DATA_COLS"])." ".
                "FROM ".$defReporte["FROM"];

            // Construir la condición LEFT JOIN para la consulta. Para cada componente
            // del LEFT JOIN del reporte, si existe un elemento con la misma clave en
            // las condiciones adicionales, se adiciona la condición asumiendo que está
            // en formato AND, de forma parecida al tratamiento de la condición WHERE
            if (isset($defReporte['LEFT JOIN'])) {
                foreach ($defReporte['LEFT JOIN'] as $sKey => $tuplaJoin) {
                    $sCondicionJoin = " LEFT JOIN $tuplaJoin[FROM] ON $tuplaJoin[ON]";
                    if (is_array($sCondicionAdicional) && isset($sCondicionAdicional[$sKey])) {
                        $sCondicionJoin .= " AND $sCondicionAdicional[$sKey]";
                    }
                    $sPeticionSQL .= $sCondicionJoin;
                }
            }

            if ($sCondicionWHERE != "")
                $sPeticionSQL .= " WHERE $sCondicionWHERE";
            $sPeticionSQL .= $sClausulaOrdenamiento.$sClausulaLimite;
            if (isset($defReporte["DEBUG"]) && $defReporte["DEBUG"]) print "$sPeticionSQL<br/>";
            $listaTuplas =& $db->fetchTable($sPeticionSQL);
            if (is_array($listaTuplas)) {
                $tuplaRespuesta[1] = $listaTuplas;
                if (!isset($defReporte['PAGECHOICE'])) {
                    $tuplaRespuesta[2] = $iNumRegistros = count($listaTuplas);
                }
            } else {
                $this->setMessage("Al leer filas - ".$db->errMsg);
            }
        }
        return $tuplaRespuesta;
    }

    // Procedimiento para construir la cláusula de ordenamiento adecuada para el reporte
    function _privado_construirClausulaOrdenamiento(&$defReporte, $sOrdenamiento)
    {
        if (!is_null($sOrdenamiento)) {
            $listaCampos = array();
            foreach ($defReporte["ORDERING"][$sOrdenamiento] as $indiceCampo) {
                // Verificar si se requiere agregar cláusula DESC
                if (eregi("^(.*)[[:space:]]+desc$", $indiceCampo, $regs)) {
                    $sDesc = " DESC";
                    $indiceCampo = $regs[1];
                } else {
                    $sDesc = "";
                }

                $sTextoCampo = $defReporte["DATA_COLS"][$indiceCampo];
                if (eregi("^.*[[:space:]]+AS[[:space:]]+([[:alnum:]_]+)$", $sTextoCampo, $regs)) {
                    $listaCampos[] = $regs[1].$sDesc;
                } else {
                    $listaCampos[] = $sTextoCampo.$sDesc;
                }
            }
            $sClausulaOrdenamiento = " ORDER BY ".join(",", $listaCampos);
        } else {
            $sClausulaOrdenamiento = "";
        }
        return $sClausulaOrdenamiento;
    }

    /**
     * Procedimiento a sobrecargar en subclases del reporte, que recoge del GET y del POST
     * las variables que definen el filtro a aplicar para el query de la base de datos. Se
     * espera que esta función devuelva variables que se deben conservar al realizar paginación.
     * El arreglo devuelto por esta función se pasa sin cambios a event_construirCondicionFiltro()
     * y a event_construirFormularioFiltro().
     *
     * @param string    $sNombreReporte Nombre del reporte para el que se proveen las columnas
     * @param array     $_GET           Variables GET de la petición del browser
     * @param array     $_POST          Variables POST de la petición del browser
     *
     * @return array    Variables de interés del filtro. Este arreglo debe tener por claves
     * los nombres de variables de formulario o URL a generar, y por valores los valores
     * recogidos del GET y del POST.
     */
    function event_recogerVariablesFiltro($sNombreReporte, $_GET, $_POST)
    {
        return array();
    }

    /**
     * Procedimiento a sobrecargar en subclases del reporte, que construye la condicion SQL
     * a agregar al final de la petición SQL del reporte, según las variables del filtro recogidas
     * por event_recogerVariablesFiltro()
     *
     * @param string    $sNombreReporte Nombre del reporte para el que se proveen las columnas
     * @param array     $varFiltro      Variables provistas por event_recogerVariablesFiltro()
     *
     * @return mixed   Se devuelve una de dos cosas:
     *     * Cadena que expresa una condición WHERE de un SELECT de SQL, por ejemplo,
     *       "tabla1.col1 = 4 AND tabla2.col4 = 8"
     *     * Arreglo asociativo que contiene múltiples condiciones: la clave WHERE indica
     *       una condición a colocar para el WHERE, como en la primera forma, y otras claves
     *       indican condiciones a agregar a las porciones del LEFT JOIN indexadas por la
     *       clave que se indique
     *
     */
    function event_construirCondicionFiltro($sNombreReporte, $varFiltro)
    {
        return "";
    }

    /**
     * Procedimiento a sobrecargar en subclases del reporte, que construye el código HTML a
     * mostrar para ilustrar el estado del filtro aplicado al reporte mostrado. El código
     * devuelto se concatena al código HTML del miembro FILTRO de la definición del reporte.
     *
     * @param string    $sNombreReporte Nombre del reporte para el que se proveen las columnas
     * @param array     $varFiltro      Variables provistas por event_recogerVariablesFiltro()
     *
     * @return string   Cadena que contiene el HTML del filtro con el estado indicado por $varFiltro
     */
    function event_construirFormularioFiltro($sNombreReporte, $varFiltro)
    {
        return "";
    }

    /**
     * Procedimiento a sobrecargar en subclases del reporte, que provee columnas adicionales según
     * el contenido de la tupla leída de la base de datos. Estas columnas pueden ser procesamientos
     * de los valores existentes, o pueden ser bsquedas SQL que devuelven tuplas con campos
     * adicionales a mostrar en la tabla. La implementación por omisión devuelve un arreglo vacío.
     *
     * @param string $sNombreReporte Nombre del reporte para el que se proveen las columnas
     * @param array  $tuplaSQL       Tupla con los valores a usar para la fila actual
     *
     * @return array    Valores a agregar a la tupla existente de SQL
     */
    function event_proveerCampos($sNombreReporte, $tuplaSQL)
    {
        return array();
    }
}

?>
