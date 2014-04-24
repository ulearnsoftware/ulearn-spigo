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
// | Autores: Alex Villacis <a_villacis@palosanto.com>                    |
// +----------------------------------------------------------------------+
//
// $Id: paloReporteFind.class.php,v 1.1.1.1 2006/03/03 21:59:09 ainiguez Exp $
require_once ("lib/paloReporte.class.php");

/**
 * Clase que extiende la funcionalidad de PaloReporte
 * @mario
 *
 * Uso:  Añadir un tag $defReporte["SEARCH_COLS"] en la definición
 *       del reporte con la lista de columnas del reporte que se pueden usar 
 *       para generar un filtro de búsqueda. Ej. 
 *    
 *       "SEARCH_COLS" =>  array(                                    
 *                             array("Cédula", 1),
 *                             array("Matrícula", 2),
 *                             array("Nombre", 4),
 *                             array("Usuario", 3),
 *       ),
 * TODO: 1. Un indicador que muestre que el reporte ha sido filtrado y
 *       2. Opcion para quitar el filtro del reporte
 *       3. Link-> window.close();
 *       4. Cerrar ventana automáticamente con el evento onUnload() 
 */
class PaloReporteFind extends PaloReporte {

    var $TiposBusqueda = array('1'=>'Comienza con...','2'=>'Termina con...','3'=>'Contiene...');
    
    /**
     * Devuelve los tipos de Busqueda
     */
    function _p_getTiposBusquedaOptionTag() {
        return combo($this->TiposBusqueda, '1');
    }
    
    /**
     * Devuelve las columnas de búsqueda del reporte
     */
    function _p_getColumnasOptionTag($sNombreReporte) {
        $defReporte =& $this->_listaReportes[$sNombreReporte];
        
        $SEARCH_COLS = $defReporte["SEARCH_COLS"];
        $DATA_COLS = $defReporte["DATA_COLS"];
        
        if (is_array($SEARCH_COLS)) {
            $arr = array();
            foreach($SEARCH_COLS as $tupla) $arr[$tupla[1]] = $tupla[0];
            return combo($arr, '1');
        }
    }
    
    /**
     * Genera el código javascript para mostrar la ventana de búsqueda
     */
    function _p_generarFindWindow($sNombreReporte) {
                
        if ($this->_tpl->_existePlantilla('tpl_buscar')) {             
            $find_window = $this->_tpl->get_template($this->_tpl->listaPlantillas['tpl_buscar']);
            $find_window = str_replace ('{TIPO_DE_BUSQUEDA_OPCIONES}', $this->_p_getTiposBusquedaOptionTag($sNombreReporte), $find_window);
            $find_window = str_replace ('{COLUMNA_A_BUSCAR_OPCIONES}', $this->_p_getColumnasOptionTag($sNombreReporte), $find_window);
            
            $find_window = str_replace ('{TEXTO_A_BUSCAR_NOMBRE}',  "{$sNombreReporte}_buscar_texto", $find_window);
            $find_window = str_replace ('{COLUMNA_A_BUSCAR_NOMBRE}',"{$sNombreReporte}_buscar_columna", $find_window);
            $find_window = str_replace ('{TIPO_DE_BUSQUEDA_NOMBRE}',"{$sNombreReporte}_buscar_tipo", $find_window);
            
            $sFindWindow = '';
            foreach (split("\n", $find_window) as $unaLinea) 
                $sFindWindow .= "\n                      findDocument += \"". str_replace('+', ' ', urlEncode($unaLinea)) ."\";";
            
            $sJSAction = "\n
                
                <script language='JavaScript'>
                <!--                                        
                    
                    function onEventUnload(e) {                      
                      if (document.findWindow && !document.findWindow.closed) document.findWindow.close();  
                    }
                
                    function {$sNombreReporte}_openFindWindow() {
                      var findWindow = null; 
                      var findDocument = \"\"; 
                    
                      // Contenido de la ventana de búsqueda                    
                      {$sFindWindow}                    
                    
                      // Dar formato y mostrar la ventana
                      //findDocument = findDocument.replace(/\+/g,\" \");  
                      findDocument = unescape(findDocument);
                      findWindow = openWindow(\"\",350,200,\"findWindow\", 0, 0, 0, 0, 0, 0, 200, 200);
                      findWindow.document.write(findDocument);
                      
                      //Añadir un control: Si cambia el reporte que se esta 
                      //presentandose se debe cerrar ventana findWindow
                      document.findWindow = findWindow;                                            
                    }
                //-->    
                </script>\n";
                          
            return $sJSAction;
        }
        else die('skins/default/_common/buscar.tpl no es leible');
    }
    
    /**
     * Genera tags ocultos para transportar el filtro de busqueda!
     */
    function _p_getInputTags($sNombreReporte) {
        return "<input type=hidden name='{$sNombreReporte}_buscar_texto'>
                <input type=hidden name='{$sNombreReporte}_buscar_columna'>
                <input type=hidden name='{$sNombreReporte}_buscar_tipo'>";
    }
    
    /**
     * Generar reporte
     * @see paloReporte->generarReporte($sNombreReporte, &$_GET,  &$_POST)
     */
    function generarReporte($sNombreReporte, &$_GET,  &$_POST) {
        $defReporte =& $this->_listaReportes[$sNombreReporte];
        
        //Si se encuentra "SEARCH_COLS" entonces añadir esta funcionalidad
        if (isset($defReporte["SEARCH_COLS"])) {      
            
            //Añadir filtro a la consulta!
            if (isset($defReporte["CONST_WHERE"])) {            
                $sSearchQuery = $this->_p_construir_SearchQuery($sNombreReporte, $_GET, $_POST);
                if ($sSearchQuery)
                    $defReporte["CONST_WHERE"] = "{$sSearchQuery} and {$defReporte['CONST_WHERE']}";
            } 
            
            //Codigo para la ventana de búsqueda  
            $sFindText = $this->_p_generarFindWindow($sNombreReporte);        
            $sBuscarButton = "\n". $sFindText ."\n
                <a href='javascript:{$sNombreReporte}_openFindWindow();' title='Filtro de búsqueda por columnas'>
                <img src='skins/default/images/search.gif' alt='Búsqueda' border=0></a>\n";
            $sBuscarButton .= $this->_p_getInputTags($sNombreReporte);
            
            //Añadir el codigo de la ventana de búsqueda
            if (isset($defReporte["FILTRO"])) 
                $defReporte["FILTRO"] = "<table cellspacing=0 cellpadding=4 width='100%' border=0><tr><td>{$defReporte["FILTRO"]}</td><td width=10 valign=top align=right>$sBuscarButton</td></tr></table>";            
            else
                $defReporte["FILTRO"] = "<table align=right cellspacing=4 cellpadding=4 width='100%' border=0><tr><td>{$defReporte["FILTRO"]}</td><td width=10>$sBuscarButton</td></tr></table>";
            
        } 
        
        //Llama a paloReporte->generarReporte(...)
        $sContents = parent::generarReporte($sNombreReporte, $_GET,  $_POST);
        return $sContents;
    }
    
    /**
     * Construye el Query que se debe añadir
     * 
     */
    function _p_construir_SearchQuery($sNombreReporte, &$_GET, &$_POST) {
        $sColumna = recoger_valor("{$sNombreReporte}_buscar_columna", $_GET, $_POST);
        $sCriterio = recoger_valor("{$sNombreReporte}_buscar_tipo", $_GET, $_POST);
        $sTexto = recoger_valor("{$sNombreReporte}_buscar_texto", $_GET, $_POST);
        
        if ($sColumna && $sCriterio && $sTexto) {
            switch ($sCriterio) {
            case '1':
                $sTexto = "{$sTexto}%";
                break;
            case '2':
                $sTexto = "%{$sTexto}";
                break;
            default:
                $sTexto = "%{$sTexto}%";
                break;
            }
            $defReporte =& $this->_listaReportes[$sNombreReporte];
            $DATA_COLS = $defReporte["DATA_COLS"];
            //echo " {$sColumna} like '$sTexto'";
            
            $sColumna = $DATA_COLS[$sColumna];
            $pos = strpos(strtolower($sColumna), ' as ');
            if ($pos === false) 
                $sColumna = $sColumna;
            else 
                $sColumna = substr($sColumna, 0, $pos);
                
            return " {$sColumna} like '$sTexto' ";
        }                       
    }     

}
?>