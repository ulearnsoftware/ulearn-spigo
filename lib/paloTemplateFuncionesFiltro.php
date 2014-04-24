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
// $Id: paloTemplateFuncionesFiltro.php,v 1.1.1.1 2006/03/03 21:59:09 ainiguez Exp $

class funcionesFiltro
{

    function funcionesFiltro()
    {

    }

    function holaMundo()
    {
        return "Hola Alex";
    }

    function truncarCadena($cadena, $longitudMax)
    {
        if(strlen($cadena)>$longitudMax) {
            return substr($cadena, 0, $longitudMax) . "...";
        }

        return $cadena;
    }

    // FUNCIONES PARA EL MODULO FACTURA

    function agregarLink($estatus, $texto)
    {
        if($estatus=='0') {
            $salida = "Abierta";
        } elseif ($estatus=='1') {
            $salida = "Cerrada";
        } elseif ($estatus=='2') {
            $salida = "Pendiente";
        } else {
            $salida = "Pagada";
        }

        return $salida;
    }

    function mostrarCheckbox($estatus, $idFactura)
    {
        $estatus = strtolower($estatus);
        if($estatus=='abierta') {
            $salida = "<input type='checkbox' name='id_factura' value='$idFactura'>";
        } elseif ($estatus=='1') {
        } elseif ($estatus=='2') {
        } else {
        }

        return $salida;
    }
}
?>
