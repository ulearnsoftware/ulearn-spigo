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
// |          Edgar Landivar <e_landivar@palosanto.com>                   |
// +----------------------------------------------------------------------+
//
// $Id: paloTemplate.class.php,v 1.1.1.1 2006/03/03 21:59:09 ainiguez Exp $

if (isset($gsRutaBase))
     require_once("$gsRutaBase/lib/FastTemplate.class.php");
else require_once("lib/FastTemplate.class.php");

// Clase que proporciona un aspecto unificado a la vista de todas las páginas
// de la aplicación PHP
class PaloTemplate extends FastTemplate
{

    var $listaPlantillas;

	// Crear un nuevo motor de plantillas (clase FastTemplate), la cual
	// ha sido inicializada con el directorio indicado y con las plantillas
	// adicionales listadas en los parámetros
	function definirDirectorioPlantillas($sDirPlantillas = "")
		// FastTemplate
	{
		//global $config;

		// Construcción de la ruta de las plantillas según el skin configurado
        $sRutaBase = $this->ROOT;
		//$sRutaBase = "skins/" . $config->skin;
		//$this->oPlantillas =& new FastTemplate($sRutaBase);

		// Se buscan todas las plantillas en $sRutaBase/comun y en
		// $sRutaBase/$sDirPlantillas (si se proporciona) para construir
		// el arreglo de la función FastTemplate::define(). Debido al
		// comportamiento de array_merge(), una plantilla privada con el mismo
		// nombre que una plantilla común reemplazará a la plantilla común

		$listaPlantillas = $this->_listarPlantillas(
			$sRutaBase, "_common");

		if ($sDirPlantillas != "")
		{
			$listaPlantillas = array_merge(
				$listaPlantillas,
				$this->_listarPlantillas($sRutaBase, $sDirPlantillas));
		}

        $this->listaPlantillas = $listaPlantillas;
		$this->define($listaPlantillas);

		// Elección del tema, que determina la ruta
		$this->assign("UBICACION_CSS", $sRutaBase);
		$this->assign("RUTA_IMG", "$sRutaBase/images");
		$this->assign("PHP_SELF", $_SERVER["PHP_SELF"]);
		$this->assign("OPCIONES_FORM", "");
	}

	// Listar todos los archivos con extensión .tpl del directorio
	// nombrado, y convertirlos en tpl_[nombre_archivo] para todo
	// [nombre_archivo].tpl. Devuelve un arreglo con todos los nombres,
	// de la forma tpl_[nombre_archivo] => [nombre_archivo].tpl
	function _listarPlantillas($sDirBase, $sSubDir)	// array
	{
		// Quitar cualquier separador al final del directorio base
		if (ereg('^(.*)/$', $sDirBase, $token))
			$sDirBase = $token[1];

		$listaPlantillas = array();
		$hDir = @opendir("$sDirBase/$sSubDir");
		if ($hDir)
		{
			while ($sNombreArchivo = readdir($hDir))
			{
				if (ereg('^(.*)\.tpl$', $sNombreArchivo, $token))
				{
					$nombre = $token[1];
					$listaPlantillas["tpl_$nombre"] = "$sSubDir/$nombre.tpl";
				}
			}
			closedir($hDir);
		}
		return $listaPlantillas;
	}

    function _existePlantilla($nombrePlantilla) {
        $resultado = array_key_exists($nombrePlantilla, $this->listaPlantillas);
        return $resultado;
    }


    function _construirNombrePlantilla($prefijo, $sufijo, $prefijo_default) {

        $nombre_plantilla         = "tpl_" . $prefijo . $sufijo;
        $nombre_plantilla_default = "tpl_" . $prefijo_default . $sufijo;

        if($this->_existePlantilla($nombre_plantilla)) {
            return $nombre_plantilla;
        } else if($this->_existePlantilla($nombre_plantilla_default)) {
            return $nombre_plantilla_default;
        } else {
            $this->error = "No se encontro la plantilla $nombre_plantilla_default";
            return false;
        }
    }

    // aniadir la contruccion de entidades html basadas en las plantillas

    function crearMenu($arr_tags, $width="", $id_tag_selected="", $tpl_prefix="_menu", $prefix_link="", $optText="")
    {
        // Pregunta: Que hago si el tag seleccionado ($id_tag_selected) no corresponde a ninguno de los indices del
        // arreglo $arr_tags??
        // Respuesta: Voy a revisar si este caso ocurre, y de ser asi hago $id_tag_selected="" ya que este comporta

        $this->assign("_TEXT", $optText);

        if($id_tag_selected=="") $id_tag_selected = "__none__";
        if($tpl_prefix=="") $tpl_prefix = "_menu";

        if(!is_array($arr_tags)) {
            $this->assign("TDs_TAG", "");
        }

        $tpl_contenedor = $this->_construirNombrePlantilla($tpl_prefix, "_container", "_menu");
        $tpl_tag_on     = $this->_construirNombrePlantilla($tpl_prefix, "_tag_on",    "_menu");
        $tpl_tag_off    = $this->_construirNombrePlantilla($tpl_prefix, "_tag_off",   "_menu");
        $tpl_color_1    = $this->_construirNombrePlantilla($tpl_prefix, "_color_1",   "_menu");
        $tpl_color_2    = $this->_construirNombrePlantilla($tpl_prefix, "_color_2",   "_menu");
        $tpl_color_3    = $this->_construirNombrePlantilla($tpl_prefix, "_color_3",   "_menu");

        // chequeo que todas las plantillas existan, si no existe trato de forzar el uso de las
        // plantillas _menu, si tampoco existen esas, entonces muestro error.

        // si el id_tag_selected no corresponde a ninguno de los tags id en el arreglo arr_tags
        // entonces asumo al primer elemento de $arr_tag como el seleccionado

        // Tengo que revisar que width sea un valor valido
        if($width=="") $width = "100%";

        // --> manejo los colores
        $this->parse("MENU_COLOR_1", $tpl_color_1);
        $this->parse("MENU_COLOR_2", $tpl_color_2);
        $this->parse("MENU_COLOR_3", $tpl_color_3);

        $color1 = $this->fetch("MENU_COLOR_1");
        $color2 = $this->fetch("MENU_COLOR_2");
        $color3 = $this->fetch("MENU_COLOR_3");

        $this->assign("MENU_COLOR_1", trim($color1));
        $this->assign("MENU_COLOR_2", trim($color2));
        $this->assign("MENU_COLOR_3", trim($color3));
        // --- fin colores

        if(is_array($arr_tags)) {
            foreach($arr_tags as $id => $arr_tag) {
                // arr_tag contiene nombre, link, mensaje
                $this->assign("TAG_NAME", $arr_tag['label']);
                if(!empty($prefix_link)) {
                    $this->assign("TAG_LINK", $prefix_link . $arr_tag['link']);
                } else {
                    $this->assign("TAG_LINK", $arr_tag['link']);
                }
                if($id == $id_tag_selected) {
                    $this->assign("TAG_DESCRIPCION", $arr_tag['description']);
                    $this->parse("TDs_TAG", "." . $tpl_tag_on);
                } else {
                    $this->parse("TDs_TAG", "." . $tpl_tag_off);
                }
            }
        }

        if($id_tag_selected=="__none__") { $this->assign("TAG_DESCRIPCION", ""); }

        $this->assign("MENU_WIDTH", $width);
        // ahora hago un parse del contenedor
        $this->parse("_FINAL", $tpl_contenedor);
        // limpio
        $this->clear("TDs_TAG");
        return $this->fetch("_FINAL");
    }

    /**
     * Crea un formulario completo a partir de una especificación
     *
     * @param array $arr_formulario Un arreglo que describe el formulario.
     * Un ejemplo del arreglo requerido se encuentra al final del procedimiento.
     *
     * @return string           Codigo HTML del formulario generado
     *
     */
    function crearFormulario($arr_formulario)
    {
        $tpl_table_container    = "tpl__form_container";
        $tpl_table_data_cell    = "tpl__table_data_cell";
        $tpl_table_data_row     = "tpl__table_data_row";

        // Asignar valores estáticos del formulario
        $this->assign("TITLE", $arr_formulario["title"]);
        $this->assign("SUBMIT_NAME", $arr_formulario["submit"]["name"]);
        $this->assign("SUBMIT_VALUE", $arr_formulario["submit"]["value"]);
        $this->assign("TBL_WIDTH", isset($arr_formulario["width"]) ? $arr_formulario["width"] : "");

        // Asignar las opciones del formulario
        $sOpcionesForm = "";
        if (isset($arr_formulario["options"])) {
            foreach ($arr_formulario["options"] as $sAttr => $sValor) {
                $sOpcionesForm .= " $sAttr=\"".htmlentities($sValor, ENT_COMPAT, "UTF-8")."\"";
            }
        }
        $this->assign("FORM_OPTIONS", $sOpcionesForm);

        // Texto de variables escondidas se inicia a vacío
        $sVariablesHidden = "";

        // Asignar valores a cada una de las filas del formulario
        for ($i = 0; $i < count($arr_formulario["fields"]); $i++) {
            $field = $arr_formulario["fields"][$i];

            if (!isset($field["type"]) || $field["type"] != "hidden") {
                $this->assign("DATA", "<b>".$field["tag"]."</b>");
                $this->parse("TDs_DATA", "." . $tpl_table_data_cell);
            }

            unset($field["tag"]);
            if (isset($field["type"])) {
                switch ($field["type"]) {
                case "html":
                    $sEtiqueta = $field["value"]; unset($field["value"]);
                    break;
                case "hidden":
                    $sValor = isset($field["value"]) ? $field["value"] : "";
                    $sVariablesHidden .= "<input type=\"hidden\" name=\"".$field["name"]."\" value=\"".htmlentities($sValor, ENT_COMPAT, "UTF-8")."\">\n";
                    break;
                case "select":
                    // Aislar opciones y selección
                    $arr_options = $field["options"];
                    unset ($field["options"]);
                    $seleccion = isset($field["value"]) ? $field["value"] : NULL;
                    unset($field["value"]);
                    unset($field["type"]);

                    // Construir la etiqueta SELECT
                    $sEtiqueta = "<select";
                    foreach ($field as $sAttr => $sValor) {
                        if (is_null($sValor)) {
                            $sEtiqueta .= " $sAttr";
                        } else {
                            if (in_array("multiple", array_keys($field)) && $sAttr == "name") {
                                $sEtiqueta .= " name=\"".htmlentities($sValor, ENT_COMPAT, "UTF-8")."[]\"";
                            } else {
                                $sEtiqueta .= " $sAttr=\"".htmlentities($sValor, ENT_COMPAT, "UTF-8")."\"";
                            }
                        }
                    }
                    $sEtiqueta .= ">\n";

                    // Construir las opciones disponibles para el SELECT
                    for ($j = 0; $j < count($arr_options); $j++) {
                        $sEtiqueta .= "<option value=\"".$arr_options[$j]["value"]."\" ";
                        if (!is_null($seleccion)) {
                            if (is_array($seleccion)) {
                                if (in_array($arr_options[$j]["value"], $seleccion)) $sEtiqueta .= "selected";
                            } else {
                                if ($seleccion == $arr_options[$j]["value"]) $sEtiqueta .= "selected";
                            }
                        }
                        $sEtiqueta .= ">".$arr_options[$j]["tag"]."</option>\n";
                    }
                    $sEtiqueta .= "</select>\n";
                    break;
                case "radio":
                    // Aislar opciones y selección
                    $arr_options = $field["options"];
                    unset ($field["options"]);
                    $sSeleccion = isset($field["value"]) ? $field["value"] : NULL;
                    unset($field["value"]);

                    $sEtiqueta = "<table border=\"0\" cellspacing=\"0\" cellpadding=\"0\">\n";
                    for ($j = 0; $j < count($arr_options); $j++) {
                        $sEtiqueta .= "<tr><td class=\"table_data\"><input";
                        foreach ($field as $sAttr => $sValor) {
                            if (is_null($sValor)) {
                                $sEtiqueta .= " $sAttr";
                            } else {
                                $sEtiqueta .= " $sAttr=\"".htmlentities($sValor, ENT_COMPAT, "UTF-8")."\"";
                            }
                        }
                        foreach ($arr_options[$j] as $sAttr => $sValor) {
                            if ($sAttr != "tag") {
                                if (is_null($sValor)) {
                                    $sEtiqueta .= " $sAttr";
                                } else {
                                    $sEtiqueta .= " $sAttr=\"".htmlentities($sValor, ENT_COMPAT, "UTF-8")."\"";
                                }

                                if ($sAttr == "value" && $sValor == $sSeleccion)
                                    $sEtiqueta .= " checked";
                            }
                        }
                        $sEtiqueta .= ">";
                        $sEtiqueta .= "<td class=\"table_data\">".(isset($arr_options[$j]["tag"]) ? $arr_options[$j]["tag"] : "&nbsp;")."</td>";
                        $sEtiqueta .= "</td></tr>\n";
                    }
                    $sEtiqueta .= "</table>";
                    break;
                case "textarea":
                    $sTexto = isset($field["value"]) ? $field["value"] : "";
                    unset($field["value"]);
                    $sEtiqueta = "<textarea";
                    foreach ($field as $sAttr => $sValor) {
                        if (is_null($sValor)) {
                            $sEtiqueta .= " $sAttr";
                        } else {
                            $sEtiqueta .= " $sAttr=\"".htmlentities($sValor, ENT_COMPAT, "UTF-8")."\"";
                        }
                    }
                    $sEtiqueta .= ">".htmlentities($sTexto, ENT_COMPAT, "UTF-8")."</textarea>\n";
                    break;
                case "date":
                    unset ($field["type"]);
                    $sEtiqueta = $this->_privado_crearComboFecha($field);
                    break;
                case "time":
                    unset ($field["type"]);
                    $sEtiqueta = $this->_privado_crearComboHora($field);
                    break;
                case "datetime":
                    unset ($field["type"]);
                    $sSaltoLinea = "&nbsp;";
                    if (isset($field['_wrap'])) {
                        unset($field['_wrap']);
                        $sSaltoLinea = "</td></tr><tr><td>";
                    }
                    $sEtiqueta = "<table border=\"0\"><tr><td>".
                        $this->_privado_crearComboFecha($field).
                        $sSaltoLinea.$this->_privado_crearComboHora($field).
                        "</td></tr></table>";
                    break;
                case "label":
                    unset ($field["type"]);
                    if (isset($field['value'])) {
                        $sTexto = $field["value"]; unset($field["value"]);
                    } else {
                        $sTexto = "";
                    }
                    $sEtiqueta = "<div";
                    foreach ($field as $sAttr => $sValor) {
                        if (is_null($sValor)) {
                            $sEtiqueta .= " $sAttr";
                        } else {
                            $sEtiqueta .= " $sAttr=\"".htmlentities($sValor, ENT_COMPAT, "UTF-8")."\"";
                        }
                    }
                    $sEtiqueta .= ">".htmlentities($sTexto, ENT_COMPAT, "UTF-8")."</div>";
                    break;
                case "checkbox":
                    if (isset($field["value"]) && $field["value"]) {
                        $field["checked"] = NULL;
                    } else {
                        unset($field["value"]);
                    }
                case "text":
                case "password":
                case "file":
                default:
                    $sEtiqueta = "<input";
                    foreach ($field as $sAttr => $sValor) {
                        if (is_null($sValor)) {
                            $sEtiqueta .= " $sAttr";
                        } else {
                            $sEtiqueta .= " $sAttr=\"".htmlentities($sValor, ENT_COMPAT, "UTF-8")."\"";
                        }
                    }
                    $sEtiqueta .= ">";
                    break;
                }
            } else {
                $sTexto = $field["value"]; unset($field["value"]);
                $sEtiqueta = "<div";
                foreach ($field as $sAttr => $sValor) {
                    if (is_null($sValor)) {
                        $sEtiqueta .= " $sAttr";
                    } else {
                        $sEtiqueta .= " $sAttr=\"".htmlentities($sValor, ENT_COMPAT, "UTF-8")."\"";
                    }
                }
                $sEtiqueta .= ">".htmlentities($sTexto, ENT_COMPAT, "UTF-8")."</div>";
            }
            if (!isset($field["type"]) || $field["type"] != "hidden") {
                $this->assign("DATA", $sEtiqueta);
                $sEtiqueta = "";

                $this->assign('CELL_ATTRIBUTES', 'class="table_data"');
                $this->parse("TDs_DATA", "." . $tpl_table_data_cell);
                $this->parse("FIELD_ROWS", "." . $tpl_table_data_row);

                // Aqui debo limpiar las celdas para la prox. iteracion
                $this->clear("TDs_DATA");
            }
        }

        // Parsing final de la tabla
        $this->assign("HIDDEN_FIELDS", $sVariablesHidden);
        $this->parse("TABLA", $tpl_table_container);
        $this->clear("FIELD_ROWS");
        $resultado = $this->fetch("TABLA");
        return $resultado;
    }
/*
    $sContenido = $oPlantillas->crearFormulario(array(
        "title"     =>  (is_null($id_user) ? "Crear nuevo usuario" : "Modificar usuario")."<br>\n<a href=\"?seccion=lista_usuarios\">&laquo;Regresar</a>&nbsp;",
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
                "title"     =>  "Ingrese el nombre del usuario",
                "name"      =>  "in_nombre_usuario",
                "type"      =>  "text",
                "size"      =>  18,
                "maxlength" =>  50,
                "value"     =>  ""),
            array(
                "name"      =>  "in_hidden_1",
                "type"      =>  "hidden",
                "value"     =>  "45",
                ),
            array(
                "name"      =>  "in_hidden_2",
                "type"      =>  "hidden",
                "value"     =>  "46",
                ),
            array(
                "tag"       =>  "Descripci&oacute;n:",
                "title"     =>  "Descripción del rol del usuario en el sistema",
                "name"      =>  "in_desc_usuario",
                "type"      =>  "text",
                "size"      =>  18,
                "maxlength" =>  180,
                "value"     =>  ""),
            array(
                "tag"       =>  "Texto común y corriente:",
                "type"      =>  "label",
                "title"     =>  "Texto emergente para texto corriente",
                "value"     =>  "Este es un texto común"),
            array(
                "tag"       =>  "Contenido arbitrario HTML:",
                "type"      =>  "html",
                "value"     =>  "<a href=\"http://www.linux.org/\">Visite www.linux.org</a>",
                ),
            array(
                "tag"       =>  "Selecci&oacute;n de checkbox:",
                "title"     =>  "Esta es una seleccion de prueba",
                "name"      =>  "in_checkbox",
                "type"      =>  "checkbox",
                "value"     =>  TRUE,
                ),
            array(
                "tag"       =>  "Selecci&oacute;n de radio:",
                "name"      =>  "in_radiobutton",
                "type"      =>  "radio",
                "options"   =>  array(
                    array(
                        "value" =>  "A",
                        "tag"   =>  "Opcion A",
                        "title" =>  "Esta es la opcion A"),
                    array(
                        "value" =>  "B",
                        "tag"   =>  "Opcion B",
                        "title" =>  "Esta es la opcion B"),
                    array(
                        "value" =>  "C",
                        "tag"   =>  "Opcion C",
                        "title" =>  "Esta es la opcion C"),
                    ),
                "value"     =>  "B",
                ),
            array(
                "tag"       =>  "Selecci&oacute;n de combo",
                "title"     =>  "Elija al menos una opción",
                "name"      =>  "in_select",
                "type"      =>  "select",
                "multiple"  =>  NULL,
                "size"      =>  4,
                "options"   =>  array(
                    array(
                        "value" =>  "A",
                        "tag"   =>  "Opcion A"),
                    array(
                        "value" =>  "B",
                        "tag"   =>  "Opcion B"),
                    array(
                        "value" =>  "C",
                        "tag"   =>  "Opcion C"),
                    ),
                "value"     =>  array("A", "C"),
                ),

            array(
                "tag"       =>  "Selecci&oacute;n de combo",
                "title"     =>  "Elija exactamente una opción",
                "name"      =>  "in_select2",
                "type"      =>  "select",
                "options"   =>  array(
                    array(
                        "value" =>  "A",
                        "tag"   =>  "Opcion A"),
                    array(
                        "value" =>  "B",
                        "tag"   =>  "Opcion B"),
                    array(
                        "value" =>  "C",
                        "tag"   =>  "Opcion C"),
                    ),
                "value"     =>  "C",
                ),
            array(
                "tag"       =>  "Entrada TEXTAREA:",
                "title"     =>  "Ingrese texto cualquiera",
                "name"      =>  "in_texto_usuario",
                "type"      =>  "textarea",
                "rows"      =>  5,
                "cols"      =>  30,
                "maxlength" =>  500,
                "value"     =>  "\"ga\nto&"),

            ),
            array(
                "tag"       =>  "Fecha arbitraria:",
                "title"     =>  "Prueba de ingreso de fechas",
                "name"      =>  "in_fecha",
                "type"      =>  "datetime",
                "yearlimit" =>  array("2000", "2037"),
                "value"     =>  date("Y-m-d H:i:s")),
        ));

*/

    /**
     * Procedimiento que crea y devuelve 3 combos que sirven para especificar una hora
     */
    function _privado_crearComboHora($field)
    {
        unset($field["yearlimit"]);
        $sEtiqueta = "";

        // Obtener el valor de la variable a usar
        $sNombreVarForm = $field["name"];
        unset($field["name"]);

        // Obtener los valores de año, mes y día
        if (isset($field["value"]) && ereg("[[:digit:]]{2}:[[:digit:]]{2}:[[:digit:]]{2}", $field["value"])) {
            $sFechaOmision = $field["value"];
            unset($field["value"]);
        } else {
            $sFechaOmision = date("H:i:s");
        }
        ereg("([[:digit:]]{2}):([[:digit:]]{2}):([[:digit:]]{2})", $sFechaOmision, $regs);
        $iHora = $regs[1];
        $iMinuto = $regs[2];
        $iSegundo = $regs[3];

        // Construir el combo de la hora
            $sEtiqueta .= "<select name=\"$sNombreVarForm"."[HORA]\"";
            foreach ($field as $sAttr => $sValor) {
                if (is_null($sValor)) {
                    $sEtiqueta .= " $sAttr";
                } else {
                    $sEtiqueta .= " $sAttr=\"".htmlentities($sValor, ENT_COMPAT, "UTF-8")."\"";
                }
            }
            $sEtiqueta .= ">\n";
            for ($i = 0; $i <= 23; $i++) {
                $sEtiqueta .= "   <option value=\"$i\"";
                if ($i == $iHora) $sEtiqueta .= " selected";
                $sEtiqueta .= ">".sprintf("%02d", $i)."</option>\n";
            }
            $sEtiqueta .= "</select>\n";

        // Construir el combo del minuto
            $sEtiqueta .= ":&nbsp;";
            $sEtiqueta .= "<select name=\"$sNombreVarForm"."[MINUTO]\"";
            foreach ($field as $sAttr => $sValor) {
                if (is_null($sValor)) {
                    $sEtiqueta .= " $sAttr";
                } else {
                    $sEtiqueta .= " $sAttr=\"".htmlentities($sValor, ENT_COMPAT, "UTF-8")."\"";
                }
            }
            $sEtiqueta .= ">\n";
            for ($i = 0; $i <= 59; $i++) {
                $sEtiqueta .= "   <option value=\"$i\"";
                if ($i == $iMinuto) $sEtiqueta .= " selected";
                $sEtiqueta .= ">".sprintf("%02d", $i)."</option>\n";
            }
            $sEtiqueta .= "</select>\n";

        // Construir el combo del segundo
            $sEtiqueta .= ":&nbsp;";
            $sEtiqueta .= "<select name=\"$sNombreVarForm"."[SEGUNDO]\"";
            foreach ($field as $sAttr => $sValor) {
                if (is_null($sValor)) {
                    $sEtiqueta .= " $sAttr";
                } else {
                    $sEtiqueta .= " $sAttr=\"".htmlentities($sValor, ENT_COMPAT, "UTF-8")."\"";
                }
            }
            $sEtiqueta .= ">\n";
            for ($i = 0; $i <= 59; $i++) {
                $sEtiqueta .= "   <option value=\"$i\"";
                if ($i == $iSegundo) $sEtiqueta .= " selected";
                $sEtiqueta .= ">".sprintf("%02d", $i)."</option>\n";
            }
            $sEtiqueta .= "</select>\n";

        return $sEtiqueta;
    }

    /**
     * Procedimiento que crea y devuelve 3 combos que sirven para especificar una fecha
     */
    function _privado_crearComboFecha($field)
    {
        // Obtener el valor de la variable a usar
        $sNombreVarForm = $field["name"];
        unset($field["name"]);

        // Obtener los valores de año, mes y día
        if (isset($field["value"]) && ereg("[[:digit:]]{4}-[[:digit:]]{2}-[[:digit:]]{2}", $field["value"])) {
            $sFechaOmision = $field["value"];
            unset($field["value"]);
        } else {
            $sFechaOmision = date("Y-m-d");
        }
        ereg("([[:digit:]]{4})-([[:digit:]]{2})-([[:digit:]]{2})", $sFechaOmision, $regs);
        $iAnio = $regs[1];
        $iMes = $regs[2];
        $iDia = $regs[3];

        // Construir la etiqueta necesaria para año
        $iAnioActual = date("Y");
        if (isset($field["yearlimit"])) {

            // Determinar los límites inferior y superior del año
            if (!is_array($field["yearlimit"])) $field["yearlimit"] = array((int)$field["yearlimit"]);
            if (count($field["yearlimit"]) == 1) {
                if ($field["yearlimit"][0] > $iAnioActual) {
                    $iAnioInicial = $iAnioActual;
                    $iAnioFinal = (int)$field["yearlimit"][0];
                } else {
                    $iAnioInicial = (int)$field["yearlimit"][0];
                    $iAnioFinal = $iAnioActual;
                }
            } else {
                $iAnioInicial = (int)$field["yearlimit"][0];
                $iAnioFinal = (int)$field["yearlimit"][1];
                if ($iAnioFinal < $iAnioInicial) {
                    $T = $iAnioFinal;
                    $iAnioFinal = $iAnioInicial;
                    $iAnioInicial = $T;
                }
            }

            // Construir el combo de año
            $sEtiqueta = "<select name=\"$sNombreVarForm"."[ANIO]\"";
            unset($field["yearlimit"]);
            foreach ($field as $sAttr => $sValor) {
                if (is_null($sValor)) {
                    $sEtiqueta .= " $sAttr";
                } else {
                    $sEtiqueta .= " $sAttr=\"".htmlentities($sValor, ENT_COMPAT, "UTF-8")."\"";
                }
            }
            $sEtiqueta .= ">\n";
            for ($i = $iAnioInicial; $i <= $iAnioFinal; $i++) {
                $sEtiqueta .= "   <option value=\"$i\"";
                if ($i == $iAnio) $sEtiqueta .= " selected";
                $sEtiqueta .= ">$i</option>\n";
            }
            $sEtiqueta .= "</select>\n";
        } else {
            // Permitir el ingreso del año como texto
            $sEtiqueta = "<input type=\"text\" name=\"$sNombreVarForm"."[ANIO]\" size=\"4\" maxlength=\"4\" value=\"$iAnio\"";
            foreach ($field as $sAttr => $sValor) {
                if (is_null($sValor)) {
                    $sEtiqueta .= " $sAttr";
                } else {
                    $sEtiqueta .= " $sAttr=\"".htmlentities($sValor, ENT_COMPAT, "UTF-8")."\"";
                }
            }
            $sEtiqueta .= ">";
        }

        // Construir el combo del mes
        $sEtiqueta .= "/&nbsp;";
        $sEtiqueta .= "<select name=\"$sNombreVarForm"."[MES]\"";
        foreach ($field as $sAttr => $sValor) {
            if (is_null($sValor)) {
                $sEtiqueta .= " $sAttr";
            } else {
                $sEtiqueta .= " $sAttr=\"".htmlentities($sValor, ENT_COMPAT, "UTF-8")."\"";
            }
        }
        $sEtiqueta .= ">\n";
        $meses = array("Enero", "Febrero", "Marzo", "Abril", "Mayo", "Junio", "Julio",
            "Agosto", "Septiembre", "Octubre", "Noviembre", "Diciembre");
        for ($i = 1; $i <= 12; $i++) {
            $sEtiqueta .= "   <option value=\"$i\"";
            if ($i == $iMes) $sEtiqueta .= " selected";
            $sEtiqueta .= ">".$meses[$i - 1]."</option>\n";
        }
        $sEtiqueta .= "</select>\n";

        // Construir el combo del día
        $sEtiqueta .= "/&nbsp;";
        $sEtiqueta .= "<select name=\"$sNombreVarForm"."[DIA]\"";
        foreach ($field as $sAttr => $sValor) {
            if (is_null($sValor)) {
                $sEtiqueta .= " $sAttr";
            } else {
                $sEtiqueta .= " $sAttr=\"".htmlentities($sValor, ENT_COMPAT, "UTF-8")."\"";
            }
        }
        $sEtiqueta .= ">\n";
        for ($i = 1; $i <= 31; $i++) {
            $sEtiqueta .= "   <option value=\"$i\"";
            if ($i == $iDia) $sEtiqueta .= " selected";
            $sEtiqueta .= ">$i</option>\n";
        }
        $sEtiqueta .= "</select>\n";
        return $sEtiqueta;
    }

    /**

    */

    function definirObjetoFuncionesTabla(&$objetoFunciones)
    {
        $this->_insFunc =& $objetoFunciones;
    }

    /**
     * Crea una tabla a partir de las plantillas de la carpeta _common (a menos que dichas
     * plantillas sean sobreescritas por plantillas locales)
     *
     * @param   array   $arr_header Un arreglo de 1d con los nombres de las columnas
     * @param   array   $arr_data   Un arreglo de 2d con la data de las filas
     * @param   string  $title     Titulo de la tabla
     * @param   string  $width     Ancho de la tabla. En caso de no especificarse, el
     * ancho de la tabla sera determinado por el tamanio del arreglo $arr_header
     * @param   integer $numcols  En caso de ser especificado forza un numero de columnas
     * @param   string  $empty_msg  En caso de que no hayan datos, se muestra este mensaje
     * @param   array   $arr_layout Especifica el layout de una fila genérica de la tabla.
     * Cada elemento del arreglo es un texto que puede tener una o más de las siguientes macros:
     * {_DATA_n}    Indica que se use el valor de la columna n, contando desde 0
     * El elemento del arreglo puede ser a su vez también una tupla, en cuyo caso el primer elemento
     * es el texto a usar para la celda, y el segundo elemento es el índice de la columna de
     * datos que se debe usar como clase CSS (<td class={clase_css}>) de la celda correspondiente.
     *
     * @return string           Codigo HTML de la tabla generada
     */

    function crearTabla($arr_header, $arr_data, $title="", $width="", $numcols="",
        $empty_msg="No existen datos que mostrar", $arr_layout="")
    {
        $this->clear('HEADER_TDs');
        $this->clear('DATA_ROWs');

        if(!isset($tpl_table_container) || $tpl_table_container=="")    $tpl_table_container    = "tpl__table_container";
        if(!isset($tpl_table_title_row) || $tpl_table_title_row=="")    $tpl_table_title_row    = "tpl__table_title_row";
        if(!isset($tpl_table_empty_row) || $tpl_table_empty_row=="")    $tpl_table_empty_row    = "tpl__table_empty_row";
        if(!isset($tpl_table_header_cell) || $tpl_table_header_cell=="")  $tpl_table_header_cell  = "tpl__table_header_cell";
        if(!isset($tpl_table_header_row) || $tpl_table_header_row=="")   $tpl_table_header_row   = "tpl__table_header_row";
        if(!isset($tpl_table_data_cell) || $tpl_table_data_cell== "")   $tpl_table_data_cell    = "tpl__table_data_cell";
        if(!isset($tpl_table_data_row) || $tpl_table_data_row=="")     $tpl_table_data_row     = "tpl__table_data_row";

        // Si no se especifica $numcols entonces el ancho de la tabla sera determinado por
        // el tamanio del arreglo $arr_header... se puede hacer mejor lo siguiente...
        // TODO: numcols tambien puede ser el numero de elementos de la fila mas larga de data (arr_data)

        if($numcols=="") {
            $cuenta_header = count($arr_header);
            if($cuenta_header > 0) {
                $numcols = $cuenta_header;
            } else {
                // tengo que llenar una variable de error
                return false;
            }
        }
        $this->assign("COLSPAN", $numcols);

        // ancho
        $this->assign("TBL_WIDTH", $width);

        // Parsing de la fila del titulo
        if($title=="") {
            $this->assign("TITLE_ROW", "");
        } else {
            $this->assign("TITLE",    $title);
            $this->parse("TITLE_ROW", $tpl_table_title_row);
        }
        // Parsing de la cabecera de las columnas (header)
        if(is_array($arr_header)) {
            foreach($arr_header as $header) {
                $this->assign("HEADER_TEXT", $header);
                $this->parse("HEADER_TDs", "." . $tpl_table_header_cell);
            }
            $this->parse("HEADER_ROW", $tpl_table_header_row);
        } else {
            $this->assign("HEADER_ROW", "");
        }

        // = = = = =
        if(is_array($arr_data) and sizeof($arr_data)>0) {
            foreach($arr_data as $data) {
                for($i = 0; $i < $numcols; $i++) {
                    // Si se ha pasado el arreglo Layout entonces parseo el contenido del arreglo
                    $atributosCelda = array(
                        'class' =>  'table_data',
                    );
                    if(is_array($arr_layout) and sizeof($arr_layout)>0) {
                        if (is_array($arr_layout[$i])) {
                            // El valor de $arr_layout[$i] es un arreglo que contiene
                            // el valor de la celda y la clase, en caso de una disposición
                            // vieja, o una serie de valores asociativos que se traducen
                            // directamente a atributos para la celda. Si el valor indicado
                            // por el esquema de fila es una clave en la tupla real de fila,
                            // se usa el valor indicado a través de la tupla real de fila.
                            // De lo contrario, se usa directamente el valor indicado por
                            // el esquema de fila
                            $contenido_celda = '';
                            foreach ($arr_layout[$i] as $key => $value) {
                                switch ("$key") {
                                case '0':
                                case 'value':
                                    $contenido_celda = $this->obtenerContenidoCelda($value, $data);
                                    break;
                                case '1':
                                case 'class':
                                    if (isset($data[$value])) {
                                        // Usar valor indicado por tupla de datos
                                        $atributosCelda['class'] = $data[$value];
                                    } else {
                                        // Usar directamente valor de esquema de fila
                                        $atributosCelda['class'] = $value;
                                    }
                                    break;
                                default:
                                    if (isset($data[$value])) {
                                        // Usar valor indicado por tupla de datos
                                        $atributosCelda[$key] = $data[$value];
                                    } else {
                                        // Usar directamente valor de esquema de fila
                                        $atributosCelda[$key] = $value;
                                    }
                                    break;
                                }
                            }
                        } else {
                            $contenido_celda = $this->obtenerContenidoCelda($arr_layout[$i], $data);
                        }
                        $this->assign("DATA", $contenido_celda);
                    } else {
                        $this->assign("DATA", $data[$i]);
                    }

                    // Construir la lista de atributos para la celda actual
                    $sListaAttrib = '';
                    foreach ($atributosCelda as $key => $value) {
                        if ($sListaAttrib != '') $sListaAttrib .= ' ';
                        $sListaAttrib .= "$key=\"$value\"";
                    }

                    $this->assign('CELL_ATTRIBUTES', $sListaAttrib);
                    $this->parse("TDs_DATA", "." . $tpl_table_data_cell);
                }
                $this->parse("DATA_ROWs", "." . $tpl_table_data_row);

                // Aqui debo limpiar las celdas para la prox. iteracion
                $this->clear("TDs_DATA");
            }
        } else {
            // el arreglo de data $arr_data estaba vacio, asi que muestro un mensaje.
            $this->assign("EMPTY_MSG", $empty_msg);
            $this->parse("DATA_ROWs", $tpl_table_empty_row);
        }

        // Parsing final de la tabla
        $this->parse("TABLA", $tpl_table_container);
        $resultado = $this->fetch("TABLA");
        return $resultado;
    }

    function obtenerContenidoCelda($plantilla, $arrData)
    {
        // reemplazo todo lo que sea _DATA_$indice_data. Si no se encuentra similar no se reemplaza
        foreach($arrData as $i => $v) {
           $plantilla = str_replace("{_DATA_$i}", $v, $plantilla);
        }

        // una vez que estan reemplazados los valores de las macros {_DATA_n}
        // hago parsing de la funcion filtro, si esta existe

        // MINIMAL MATCHING!! -----V

        if(isset($this->_insFunc) && is_object($this->_insFunc)) {
            // primero hallo el nombre de la funcion
            ereg("{_FUNCTION_(.*)_}", $plantilla, $arrCoincidencias);
            if(!empty($arrCoincidencias[1])) {
                eval("\$salida_eval = \$this->_insFunc->" . $arrCoincidencias[1] . ";");
                // ahora que supuestamente tengo la salida de la funcion en la variable $salida_eval
                // reemplazo la macro por este valor
                $plantilla = ereg_replace("{_FUNCTION_(.*)_}", $salida_eval, $plantilla);
            }
        }

        return $plantilla;
    }

    /**
     * Crea una cajon a partir de las plantillas de la carpeta _common (a menos que dichas
     * plantillas sean sobreescritas por plantillas locales)
     *
     * El cajon creado es de una columna por 2 filas. La fila superior se denomina header y
     * la inferior es donde va el contenido.
     *
     * @param string $box_header Contiene el header del cajo. Por lo general es un menu
     * @param string $box_content Contiene el contenido del box.
     *
     * @return string           Codigo HTML del cajon generado
     */

    function crearBox($nombre_macro, $content_macro, $header_macro, $width="100%")
    {

        $tpl_box_container = "tpl__box_container";
        $content = $this->fetch($content_macro);
        $header  = $this->fetch($header_macro);
        $this->assign("BOX_WIDTH", $width);
        $this->assign("BOX_HEADER",  $header);
        $this->assign("BOX_CONTENT", $content);
        $this->parse($nombre_macro, $tpl_box_container);

        //$resultado = $this->fetch("BOX");
        //return $resultado;

    }

    /**
     * Crea un cajon de alerta a partir de las plantillas de la carpeta _common (a menos
     * que dichas plantillas sean sobreescritas por plantillas locales)
     *
     * @param string $alt_tipo Contiene el tipo de alerta que se va a mostrar, puediendo
     * de los siguientes cuatro tipos:
     *  'i' - information
     *  'c' - confirmation
     *  'w' - warning
     *  'e' - error
     * @param string $alt_title Contiene el titulo del cuadro de alerta.
     * @param string $alt_mensaje Contiene el mensaje del cuadro de alerta.
     *
     * @return string           Codigo HTML del cajon generado
     */

    function crearAlerta($alt_tipo, $alt_title, $alt_message, $tpl_prefix="_alert")
    {
        switch($alt_tipo) {
            case 'error':
                // falta por implementar plantilla, mientras tanto uso plantilla information
                $tpl_alt_container = $this->_construirNombrePlantilla($tpl_prefix, "_container_error", "_alert");
                break;
            case 'warning':
                // falta por implementar plantilla, mientras tanto uso plantilla information
                $tpl_alt_container = $this->_construirNombrePlantilla($tpl_prefix, "_container_information", "_alert");
                break;
            case 'confirmation':
                $tpl_alt_container = $this->_construirNombrePlantilla($tpl_prefix, "_container_confirmation", "_alert");
                break;
            default:
                $tpl_alt_container = $this->_construirNombrePlantilla($tpl_prefix, "_container_information", "_alert");
        }

        $this->assign("_ALERT_TITLE",   $alt_title);
        $this->assign("_ALERT_MSG", $alt_message);
        $this->parse("_ALERT", $tpl_alt_container);
        return $this->fetch("_ALERT");
    }
}
?>
