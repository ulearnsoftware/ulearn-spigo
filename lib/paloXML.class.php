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
// | la ley sin saberlo.                                                  |
// +----------------------------------------------------------------------+
// | Autores: Edgar Landivar <e_landivar@palosanto.com                    |
// |          Otro           <alguien@example.com>                        |
// +----------------------------------------------------------------------+
//
// $Id: paloXML.class.php,v 1.1.1.1 2006/03/03 21:59:09 ainiguez Exp $

class XML {
    var $data;
    var $arrCurrElements;
    var $currOpenElement;
    var $pila;
    var $lastidmenu;
    var $arrMenus;
    var $arreglo_final;

    function XML($filename) {
        $this->file = $filename;
        $xml_parser = xml_parser_create("UTF-8");
        xml_set_object($xml_parser, $this);
        xml_set_element_handler($xml_parser, "startElement", "endElement");
        xml_set_character_data_handler($xml_parser, "characterData");
                # Why should one want to use case-folding with XML? XML is case-sensitiv, I think this is nonsense
        xml_parser_set_option($xml_parser, XML_OPTION_CASE_FOLDING, false);

        if (!($fp = @fopen($this->file, "r"))) {
            die(print("Couldn't open file: $this->file\n"));
        }

        while ($data = fread($fp, 4096)) {
          if (!xml_parse($xml_parser, $data, feof($fp))) {
            die(sprintf("XML error: %s at line %d\n",
              xml_error_string(xml_get_error_code($xml_parser)),
              xml_get_current_line_number($xml_parser)));
          }
        }
    }

    function startElement($parser, $name, $attribs) {
        # ??? segun la logica de esta funcion y de endElement (hasta ahora) si no hay "link", "label" o "description" no hay arrMenus
        $this->currOpenElement = $name;

        switch($name) {
            case "menu":
                $id_menu = $attribs["id"];
                $this->arrCurrElements["menu"]["id"] = $attribs["id"];
                $this->arrMenus[$id_menu]["type"]    = $attribs["type"];
                break;
            case "item":
                $id_item = $attribs["id"];
                $this->arrCurrElements["item"]["id"] = $id_item;
                break;
            case "link":
                $id_menu = $this->arrCurrElements["menu"]["id"];
                $id_item = $this->arrCurrElements["item"]["id"];
                $this->arrMenus[$id_menu]["items"][$id_item]["link"] = $attribs["ref"];
                break;
            case "module":
                // Este caso solo deberia darse si el tipo de menu es "submenu"
                // asi que voy a comprobar. Caso contrario lo ignoro
                $id_menu = $this->arrCurrElements["menu"]["id"];
                $id_item = $this->arrCurrElements["item"]["id"];
                if($this->arrMenus[$id_menu]["type"]=="submenu") {
                    $this->arrMenus[$id_menu]["items"][$id_item]["module"] = $attribs["name"];
                }
                break;
            default:
                $this->data = "";
        }

    }

    function endElement($parser, $name) {
        switch($name) {
            case "menu":
                unset($this->arrCurrElements["menu"]);
                break;
            case "item":
                // == inicio de adicion especial ==
                // el "link" para un submenu es igual al id_item
                $id_menu = $this->arrCurrElements["menu"]["id"];
                $id_item = $this->arrCurrElements["item"]["id"];
                if($this->arrMenus[$id_menu]["type"]=="submenu") {
                    $this->arrMenus[$id_menu]["items"][$id_item]["link"] = $id_item;
                }
                // === fin de lo adicionado ==
                unset($this->arrCurrElements["item"]);
                break;
            case "label":
                $id_menu = $this->arrCurrElements["menu"]["id"];
                $id_item = $this->arrCurrElements["item"]["id"];
                $this->arrMenus[$id_menu]["items"][$id_item]["label"] = $this->data;
                break;
            case "description":
                $id_menu = $this->arrCurrElements["menu"]["id"];
                $id_item = $this->arrCurrElements["item"]["id"];
                $this->arrMenus[$id_menu]["items"][$id_item]["description"] = $this->data;
                break;
            default:
        }
    }

    function characterData($parser, $data) {
        $this->data .= $data;
    }

    function getPermissionTree($id_menu, &$acl, $id_user="")
    {
        $arrFinal = array();
        if (isset($this->arrMenus[$id_menu])) {
            if($this->arrMenus[$id_menu]['type']=="container") {
                foreach($this->arrMenus[$id_menu]["items"] as $i2 => $v2) {
                        $nombre_submenu = $v2["link"];
                        $arreglo_submenu = $this->getPermissionTree($nombre_submenu, $acl, $id_user);
                        if (count($arreglo_submenu['items']) > 0) {
                            $arrFinal[$id_menu]['items'][$nombre_submenu] = $arreglo_submenu;
                            $arrFinal[$id_menu]['items'][$nombre_submenu]["label"] = $v2["label"];
                            $arrFinal[$id_menu]['items'][$nombre_submenu]["description"] = $v2["description"];
                            $arrFinal[$id_menu]['items'][$nombre_submenu]["link"] = $v2["link"];
                        }
                }
            } else if ($this->arrMenus[$id_menu]["type"]=="submenu"){
                $arrFinal = $this->arrMenus[$id_menu];
                if ($id_user != "") foreach (array_keys($arrFinal['items']) as $key) {
                    // Remover el item para el cual el usuario actual no está autorizado
                    if (!$acl->isUserAuthorized($id_user, "view", $arrFinal['items'][$key]['module'])) {
                        unset($arrFinal['items'][$key]);
                    }
                }
            } else {
                    // si el type no es ni container ni submenu el tipo esta incorrecto
            }
        }
        return $arrFinal;
    }

    function validox($nombre_modulo, $id_user) {
        if(ereg("^[abcdefghijklm]", $nombre_modulo)) {
            return true;
        } else {
            return false;
        }
    }
}
?>
