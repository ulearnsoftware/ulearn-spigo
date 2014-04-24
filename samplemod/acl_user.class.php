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
// $Id: acl_user.class.php,v 1.1.1.1 2006/03/03 21:59:09 ainiguez Exp $

require_once ("lib/paloEntidad.class.php");

/* Clase que provee utilidades básicas de listado, inserción, modificación y
 * eliminación para la tabla de ACL del framework
 */
class acl_user extends PaloEntidad
{
    function acl_user(&$oDB, &$oPlantillas)
    {
        // Crear clase base de PaloEntidad
        $this->PaloEntidad($oDB, $oPlantillas, array(
            "tabla"     =>  "acl_user",
            "campos"    =>  array(
                "id"            => array(
                    "DESC"      =>  "id de clave primaria de acl_user",
                    "PRIMARY"   =>  TRUE,
                    "SQLTYPE"   =>  "INT",
                    "AUTOINC"   =>  TRUE,
                ),
                "name"          => array(
                    "DESC"      =>  "nombre de login de acl_user",
                    "SQLTYPE"   =>  "VARCHAR",
                    "SQLLEN"    =>  50,
                    "REGEXP"    =>  "[[:alnum:]\\-_]+",
//                    "ENUM"      =>  array("gato", "perro", "loro", "mono"),
                ),
                "description"   => array(
                    "DESC"      =>  "descripcion de rol de acl_user",
                    "SQLTYPE"   =>  "VARCHAR",
                    "SQLLEN"    =>  180,
                ),
                "md5_password"  => array(
                    "DESC"      =>  "hash MD5 de clave de acceso acl_user",
                    "SQLTYPE"   =>  "VARCHAR",
                    "SQLLEN"    =>  32,
                    "REGEXP"    =>  "[[:alnum:]]{32}",
                ),
            ),
        ));

        // Construir todos los formularios requeridos para acl_user
        if (!$this->definirFormulario("INSERT", "UsuarioACL", array(
            "title"     =>  "Crear nuevo usuario<br>\n".
                            "<a href=\"?seccion=lista_usuarios\">&laquo;&nbsp;Regresar</a>&nbsp;",
            "submit"    =>  array(
                "name"   =>  "submit_usuario",
                "value"  =>  "Crear usuario",
                ),
            "fields"    =>  array(
                array(
                    "tag"       =>  "Nombre:",
                    "title"     =>  "Ingrese el nombre del usuario",
                    "_field"    =>  "name",
//                    "_regexp"   =>  ".+",
                    "_empty"    =>  FALSE,
                    ),
                array(
                    "tag"       =>  "Descripci&oacute;n:",
                    "title"     =>  "Descripción del rol del usuario en el sistema",
                    "_field"    =>  "description",
//                    "_regexp"   =>  ".+",
                    "_empty"    =>  FALSE,
                    ),
                array(
                    "tag"       =>  "Clave de acceso:",
                    "title"     =>  "Ingrese la clave de acceso para este usuario",
                    "name"      =>  "clave_usuario[]",
                    "type"      =>  "password",
                    "size"      =>  20,
                    "maxlength" =>  256,
//                    "_regexp"   =>  ".+",
                    "_empty"    =>  FALSE,
                    ),
                array(
                    "tag"       =>  "Confirme clave de acceso:",
                    "title"     =>  "Confirme la clave de acceso para este usuario",
                    "name"      =>  "clave_usuario[]",
                    "type"      =>  "password",
                    "size"      =>  20,
                    "maxlength" =>  256,
//                    "_regexp"   =>  ".+",
                    "_empty"    =>  FALSE,
                    ),
                ),
        ))) die ("acl_user::acl_user() - al definir formulario INSERT UsuarioACL - ".$this->_msMensajeError);
        if (!$this->definirFormulario("UPDATE", "UsuarioACL", array(
            "title"     =>  "Modificar usuario<br>\n".
                            "<a href=\"?seccion=lista_usuarios\">&laquo;&nbsp;Regresar</a>&nbsp;",
            "submit"    =>  array(
                "name"   =>  "submit_usuario",
                "value"  =>  "Guardar cambios",
                ),
            "fields"    =>  array(
                array(
                    "tag"       =>  "Nombre:",
                    "title"     =>  "Ingrese el nombre del usuario",
                    "_field"    =>  "name",
                    "_empty"    =>  FALSE,
                    ),
                array(
                    "tag"       =>  "Descripci&oacute;n:",
                    "title"     =>  "Descripción del rol del usuario en el sistema",
                    "_field"    =>  "description",
                    "_empty"    =>  FALSE,
                    ),
                ),
        ))) die ("acl_user::acl_user() - al definir formulario UPDATE UsuarioACL - ".$this->_msMensajeError);

        if (!$this->definirFormulario("UPDATE", "PasswordUsuarioACL", array(
            "title"     =>  "Cambiar clave de usuario<br>\n".
                            "<a href=\"?seccion=lista_usuarios\">&laquo;&nbsp;Regresar</a>&nbsp;",
            "submit"    =>  array(
                "name"   =>  "submit_usuario",
                "value"  =>  "Guardar clave",
                ),
            "fields"    =>  array(
                array(
                    "tag"       =>  "Nombre de usuario:",
                    "title"     =>  "Nombre de logon del usuario",
                    "type"      =>  "label",
                    "_field"    =>  "name",
                ),
                array(
                    "tag"       =>  "Clave de acceso:",
                    "title"     =>  "Ingrese la clave de acceso para este usuario",
                    "name"      =>  "clave_usuario[]",
                    "type"      =>  "password",
                    "size"      =>  20,
                    "maxlength" =>  256,
                    "_empty"    =>  FALSE,
                    ),
                array(
                    "tag"       =>  "Confirme clave de acceso:",
                    "title"     =>  "Confirme la clave de acceso para este usuario",
                    "name"      =>  "clave_usuario[]",
                    "type"      =>  "password",
                    "size"      =>  20,
                    "maxlength" =>  256,
                    "_empty"    =>  FALSE,
                ),
            ),
        ))) die ("acl_user::acl_user() - al definir formulario UPDATE PasswordUsuarioACL - ".$this->_msMensajeError);
    }

    /**
     * Procedimiento que define la restricción de que ambas copias de la clave sean iguales
     *
     * @param string $sNombreFormulario Nombre del formulario que se está manejando
     * @param array  $formVars          Variables obtenidas del formulario, en arreglo asociativo
     *
     * @return boolean TRUE si los parámetros parecen válidos hasta ahora, FALSE si no lo son.
     */
    function event_validarValoresFormularioInsert($sNombreFormulario, $formVars)
    {
        $bValido = TRUE;
        switch ($sNombreFormulario) {
        case "UsuarioACL":
            if ($formVars["clave_usuario"][0] != $formVars["clave_usuario"][1]) {
                $this->_msMensajeError = "La clave y su confirmación no son iguales. Por favor vuelva a ingresar ambas copias.";
                $bValido = FALSE;
            }
            break;
        default:
            break;
        }
        return $bValido;
    }

    /**
     * Procedimiento que agrega la asignación de la clave de acceso encerrada en una evaluación
     * de MD5() de la base de datos, con todo y la inserción de comillas simples.
     *
     * @param string $sNombreFormulario Nombre del formulario que se está manejando
     * @param array  $formVars          Variables obtenidas del formulario, en arreglo asociativo
     *
     * @return array Arreglo asociativo de las variables a insertar en la base de datos, o NULL
     * si alguna de las variables no pasa la validación de la base de datos.
     */
    function event_traducirFormularioBaseInsert($sNombreFormulario, $formVars)
    {
        // Servirse de la validación de la clase PaloEntidad
        $dbVars = parent::event_traducirFormularioBaseInsert($sNombreFormulario, $formVars);
        if (is_array($dbVars)) switch ($sNombreFormulario) {
        case "UsuarioACL":
            $dbVars["md5_password"] = "MD5(".paloDB::DBCAMPO($formVars["clave_usuario"][0]).")";
            break;
        default:
            break;
        }
        return $dbVars;
    }

    /**
     * Procedimiento para listar los campos que NO deben ser escapados
     *
     * @param string $sNombreFormulario Nombre del formulario que se está manejando
     * @param array  $dbVars            Variables a insertar en la base de datos
     *
     * @return array Lista de los campos sin escape
     */
    function event_listarCamposNoEscapeInsert($sNombreFormulario, $dbVars)
    {
        $listaExcluir = parent::event_listarCamposNoEscapeInsert($sNombreFormulario, $dbVars);
        switch ($sNombreFormulario) {
        case "UsuarioACL":
            $listaExcluir[] = "md5_password";
            break;
        }
        return $listaExcluir;
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
        $bValido = TRUE;
        switch ($sNombreFormulario) {
        case "PasswordUsuarioACL":
            if ($formVars["clave_usuario"][0] != $formVars["clave_usuario"][1]) {
                $this->_msMensajeError = "La clave y su confirmación no son iguales. Por favor vuelva a ingresar ambas copias.";
                $bValido = FALSE;
            }
            break;
        default:
            break;
        }
        return $bValido;
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
        $dbVars = parent::event_traducirFormularioBaseUpdate($sNombreFormulario, $prevPK, $formVars);
        switch ($sNombreFormulario) {
        case "PasswordUsuarioACL":
            $dbVars["md5_password"] = "MD5(".paloDB::DBCAMPO($formVars["clave_usuario"][0]).")";
            break;
        }
        return $dbVars;
    }

    /**
     * Procedimiento para listar los campos que no deben escaparse en comillas durante
     * la inserción. El campo de md5_password se omite de la operación.
     *
     * @param string $sNombreFormulario Nombre del formulario que se está manejando
     * @param array  $dbVars            Variables a insertar en la base de datos
     *
     * @return array Lista de tuplas con los campos escapados
     */
    function event_listarCamposNoEscapeUpdate($sNombreFormulario, $prevPK, $dbVars)
    {
        $listaExcluir = parent::event_listarCamposNoEscapeUpdate($sNombreFormulario, $prevPK, $dbVars);
        switch ($sNombreFormulario) {
        case "PasswordUsuarioACL":
            $listaExcluir[] = "md5_password";
            break;
        }
        return $listaExcluir;
    }
}
?>
