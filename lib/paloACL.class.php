<?php
/* vim: set expandtab tabstop=4 softtabstop=4 shiftwidth=4: */
// Codificación: UTF-8
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
// $Id: paloACL.class.php,v 1.1.1.1 2006/03/03 21:59:09 ainiguez Exp $

// TODO:
// ****
// 1 - Usar clase paloDB.class.php. Esto evitaria por un lado conectarme a la base de datos
//     desde aqui mismo y me da la opcion de usar las funciones genericas que tiene paloDB
// 2 - Pensar en cambiar el disenio mismo de la base de datos de esta clase... se me ha ocurrido
//     implementar grupos de recursos asi como tambien crear una tabla de relacion entre acciones
//     y recursos
// 3 - Implementar una interfase administrativa que se pueda usar como modulo en el framework

if (isset($gsRutaBase))
     require_once("$gsRutaBase/lib/paloDB.class.php");
else require_once("lib/paloDB.class.php");

class paloACL {

    var $_DB; // instancia de la clase paloDB
    var $errMsg;

    function paloACL(&$pDB)
    {
        // Se recibe como parámetro una referencia a una conexión paloDB
        if (is_object($pDB)) {
            $this->_DB =& $pDB;
            $this->errMsg = $this->_DB->errMsg;
        } else {
            $dsn = (string)$pDB;
            $this->_DB = new paloDB($dsn);

            if (!$this->_DB->connStatus) {
                $this->errMsg = $this->_DB->errMsg;
                // debo llenar alguna variable de error
            } else {
                // debo llenar alguna variable de error
            }
        }
    }

    /**
     * Procedimiento para obtener el listado de los usuarios existentes en los ACL. Si
     * se especifica un ID numérico de usuario, el listado contendrá únicamente al usuario
     * indicado. De otro modo, se listarán todos los usuarios.
     *
     * @param int   $id_user    Si != NULL, indica el ID del usuario a recoger
     *
     * @return array    Listado de usuarios en el siguiente formato, o FALSE en caso de error:
     *  array(
     *      array(id, name, description),
     *      ...
     *  )
     */
    function getUsers($id_user = NULL)
    {
        $arr_result = FALSE;
        if (!is_null($id_user) && !ereg('^[[:digit:]]+$', "$id_user")) {
            $this->errMsg = "ID de usuario no es un ID numerico valido";
        } else {
            $this->errMsg = "";
            $sPeticionSQL = "SELECT id, name, description FROM acl_user".
                (is_null($id_user) ? '' : " WHERE id = $id_user");
            $arr_result =& $this->_DB->fetchTable($sPeticionSQL);
            if (!is_array($arr_result)) {
                $arr_result = FALSE;
                $this->errMsg = $this->_DB->errMsg;
            }
        }
        return $arr_result;
    }

    /**
     * Procedimiento para crear un nuevo usuario con hash MD5 de la clave ya proporcionada.
     *
     * @param string    $username       Login del usuario a crear
     * @param string    $description    Descripción del usuario a crear
     * @param string    $md5_password   Hash MD5 de la clave a asignar (32 dígitos y letras min a-f)
     *
     * @return bool     VERDADERO si el usuario se crea correctamente, FALSO en error
     */
    function createUser($username, $description, $md5_password)
    {
        $bExito = FALSE;
        if ($username == "") {
            $this->errMsg = "Nombre del usuario no puede estar vac&iacute;o";
        } else if (!ereg("^[[:digit:]a-f]{32}$", $md5_password)) {
            $this->errMsg = "Clave de acceso no es un hash MD5 valido";
        } else {
            if ( !$description ) $description = $username;

            // Verificar que el nombre de usuario no existe previamente
            $id_user = $this->getIdUser($username);
            if ($id_user !== FALSE) {
                $this->errMsg = "Ya existe un usuario con login '$username'";
            } elseif ($this->errMsg == "") {

                $sPeticionSQL = paloDB::construirInsert(
                    "acl_user",
                    array(
                        "name"          =>  paloDB::DBCAMPO($username),
                        "description"   =>  paloDB::DBCAMPO($description),
                        "md5_password"  =>  paloDB::DBCAMPO($md5_password)
                    )
                );
                if ($this->_DB->genQuery($sPeticionSQL)) {
                    $bExito = TRUE;
                } else {
                    $this->errMsg = $this->_DB->errMsg;
                }
            }
        }

        return $bExito;
    }

    /**
     * Procedimiento para modificar al usuario con el ID de usuario especificado, para
     * darle un nuevo login y descripción.
     *
     * @param int       $id_user        Indica el ID del usuario a modificar
     * @param string    $username       Login del usuario a crear
     * @param string    $description    Descripción del usuario a crear
     *
     * @return bool VERDADERO si se ha modificar correctamente el usuario, FALSO si ocurre un error.
     */
    function updateUser($id_user, $username, $description)
    {
        $bExito = FALSE;
        if ($username == "") {
            $this->errMsg = "Nombre del usuario no puede estar vac&iacute;o";
        } else if (!ereg("^[[:digit:]]+$", "$id_user")) {
            $this->errMsg = "ID de usuario a actualizar no es num&eacute;rico";
        } else {
            if ( !$description ) $description = $username;

            // Verificar que el usuario indicado existe
            $tuplaUser =& $this->getUsers($id_user);
            if (!is_array($tuplaUser)) {
                $this->errMsg = "al verificar existencia de usuario - ".$this->errMsg;
            } else if (count($tuplaUser) == 0) {
                $this->errMsg = "no se encuentra el usuario con el ID indicado";
            } else {
                $bContinuar = TRUE;

                // Si el nuevo login es distinto al anterior, se verifica si el nuevo
                // login colisiona con un login ya existente
                if ($tuplaUser[0][1] != $username) {
                    $id_user_conflicto = $this->getIdUser($username);
                    if ($id_user_conflicto !== FALSE) {
                        $this->errMsg = "Ya existe un usuario con login '$username'";
                        $bContinuar = FALSE;
                    } elseif ($this->errMsg != "") {
                        $bContinuar = FALSE;
                    }
                }

                if ($bContinuar) {
                    // Proseguir con la modificación del usuario
                    $sPeticionSQL = paloDB::construirUpdate(
                        "acl_user",
                        array(
                            "name"          =>  paloDB::DBCAMPO($username),
                            "description"   =>  paloDB::DBCAMPO($description)),
                        array(
                            "id"  =>  $id_user));
                    if ($this->_DB->genQuery($sPeticionSQL)) {
                        $bExito = TRUE;
                    } else {
                        $this->errMsg = $this->_DB->errMsg;
                    }
                }
            }
        }
        return $bExito;
    }

    /**
     * Procedimiento para cambiar la clave de un usuario, dado su ID de usuario.
     *
     * @param int       $id_user        ID del usuario para el que se cambia la clave
     * @param string    $md5_password   Nuevo hash MD5 a asignar al usuario
     *
     * @return bool VERDADERO si se ha modificar correctamente el usuario, FALSO si ocurre un error.
     */
    function changePassword($id_user, $md5_password)
    {
        $bExito = FALSE;
        if (!ereg("^[[:digit:]]+$", "$id_user")) {
            $this->errMsg = "ID de usuario a actualizar no es num&eacute;rico";
        } else if (!ereg("^[[:digit:]a-f]{32}$", $md5_password)) {
            $this->errMsg = "Clave de acceso no es un hash MD5 v&aacute;lido";
        } else {
             if ($this->errMsg == "") {
                $sPeticionSQL = paloDB::construirUpdate(
                    "acl_user",
                    array('md5_password'    =>  paloDB::DBCAMPO($md5_password)),
                    array('id'              =>  $id_user)
                );
                if ($this->_DB->genQuery($sPeticionSQL)) {
                    $bExito = TRUE;
                } else {
                    $this->errMsg = $this->_DB->errMsg;
                }
               }
        }

        return $bExito;
    }
    
    /**
     * Procedimiento para borrar un usuario ACL, dado su ID numérico de usuario
     *
     * @param int   $id_user    ID del usuario que debe eliminarse
     *
     * @return bool VERDADERO si el usuario puede borrarse correctamente
     */
    function deleteUser($id_user)
    {
        $bExito = FALSE;
        if (!ereg('^[[:digit:]]+$', "$id_user")) {
            $this->errMsg = "ID de usuario no es un ID numerico valido";
        } else {
            $this->errMsg = "";
            $listaSQL = array(
                "DELETE FROM acl_user_permission WHERE id_user = '$id_user'",
                "DELETE FROM acl_membership WHERE id_user = '$id_user'",
                "DELETE FROM acl_user WHERE id = '$id_user'",
            );
            $bExito = TRUE;

            foreach ($listaSQL as $sPeticionSQL) {
                $bExito = $this->_DB->genQuery($sPeticionSQL);
                if (!$bExito) {
                    $this->errMsg = $this->_DB->errMsg;
                    break;
                }
            }
        }
        return $bExito;
    }

    /**
     * Procedimiento para averiguar el ID de un usuario, dado su login.
     *
     * @param string    $sNombreUser    Login del usuario para buscar ID
     *
     * @return  mixed   Valor entero del ID de usuario, o FALSE en caso de error o si el usuario no existe
     */
    function getIdUser($sNombreUser)
    {
        $idUser = FALSE;

        $this->errMsg = '';
        $sPeticionSQL = "SELECT id FROM acl_user WHERE name = ".paloDB::DBCAMPO($sNombreUser);
        $result = $this->_DB->conn->query($sPeticionSQL);
        if (DB::isError($result)) {
            $this->errMsg = $result->getMessage();
        } else {
            if($row = $result->fetchRow()) {
                $idUser = (int)$row[0];
            }
        }
        return $idUser;
    }

    /**
     * Procedimiento para obtener el listado de los grupos existentes en los ACL. Si
     * se especifica un ID numérico de grupos, el listado contendrá únicamente al grupos
     * indicado. De otro modo, se listarán todos los grupos.
     *
     * @param int   $id_group    Si != NULL, indica el ID del grupos a recoger
     *
     * @return array    Listado de grupos en el siguiente formato, o FALSE en caso de error:
     *  array(
     *      array(id, name, description),
     *      ...
     *  )
     */
    function getGroups($id_group = NULL)
    {
        $arr_result = FALSE;
        if (!is_null($id_group) && !ereg('^[[:digit:]]+$', "$id_group")) {
            $this->errMsg = "ID de grupo no es un ID numerico valido";
        } else {
            $this->errMsg = "";
            $sPeticionSQL = "SELECT id, name, description FROM acl_group".
                (is_null($id_group) ? '' : " WHERE id = $id_group");
            $arr_result =& $this->_DB->fetchTable($sPeticionSQL);
            if (!is_array($arr_result)) {
                $arr_result = FALSE;
                $this->errMsg = $this->_DB->errMsg;
            }
        }
        return $arr_result;
    }

    /**
     * Procedimiento para crear un grupo bajo el nombre descrito, con una descripción opcional.
     * Si un grupo con el nombre indicado ya existe, se reemplaza la descripción.
     *
     * @param string    $groupname      Nombre del grupo a crear
     * @param string    $description    Descripción del grupo a crear, opcional
     *
     * @return bool     VERDADERO si el grupo ya existe o fue creado/actualizado correctamente
     */
    function createGroup($groupname, $description = '')
    {
        $bExito = FALSE;
        $this->errMsg = "";
        if ($groupname == "") {
            $this->errMsg = "Nombre del grupo no puede estar vac&iacute;o";
        } else {
            if ($description == '') $description = $groupname;

            // Verificar si el grupo ya existe
            $sPeticionSQL =
                "SELECT description FROM acl_group ".
                "WHERE name = ".paloDB::DBCAMPO($groupname);
            $tupla =& $this->_DB->getFirstRowQuery($sPeticionSQL);
            if (!is_array($tupla)) {
                // Ocurre error de DB en consulta
                $this->errMsg = $this->_DB->errMsg;
            } else if (count($tupla) == 0) {
                // Grupo no existía previamente
                $sPeticionSQL = paloDB::construirInsert(
                    "acl_group",
                    array(
                        "name"          =>  paloDB::DBCAMPO($groupname),
                        "description"   =>  paloDB::DBCAMPO($description),
                    )
                );
                if ($this->_DB->genQuery($sPeticionSQL)) {
                    $bExito = TRUE;
                } else {
                    $this->errMsg = $this->_DB->errMsg;
                }
            } else {
                // Grupo existía previamente, se actualiza opcionalmente desc
                if ($tupla[0] != $description) {
                    // Se modifica descripción de grupo existente
                    $sPeticionSQL = paloDB::construirUpdate(
                        'acl_group',
                        array('description' =>  paloDB::DBCAMPO($description)),
                        array('name'        =>  paloDB::DBCAMPO($groupname)));
                    if ($this->_DB->genQuery($sPeticionSQL)) {
                        $bExito = TRUE;
                    } else {
                        $this->errMsg = $this->_DB->errMsg;
                    }
                } else {
                    // Se intenta crear grupo idéntico a existente en DB
                    $bExito = TRUE;
                }
            }
        }

        return $bExito;
    }

    /**
     * Procedimiento para construir un arreglo que describe los grupos a los cuales
     * pertenece un usuario identificado por un ID. El arreglo devuelto tiene el siguiente
     * formato:
     *  array(
     *      nombre_grupo_1  =>  id_grupo_1,
     *      nombre_grupo_2  =>  id_grupo_2,
     *  )
     *
     * @param int   $id_user    ID del usuario para el cual se pide la pertenencia
     *
     * @return mixed    Arreglo que describe la pertenencia, o NULL en caso de error.
     */
    function getMembership($id_user)
    {
        $arr_resultado = NULL;
        if (!is_null($id_user) && !ereg('^[[:digit:]]+$', "$id_user")) {
            $this->errMsg = "ID de usuario no es un ID numerico valido";
        } else {
            $this->errMsg = "";
            $sPeticionSQL =
                "SELECT g.id, g.name ".
                "FROM acl_group as g, acl_membership as m ".
                "WHERE m.id_group = g.id AND m.id_user = $id_user";
            $recordset =& $this->_DB->conn->query($sPeticionSQL);
            if (DB::isError($recordset)) {
                $this->errMsg = $recordset->getMessage();
            } else {
                $arr_resultado = array();
                while ($tupla = $recordset->fetchRow()) {
                    $arr_resultado[$tupla[1]] = (int)$tupla[0];
                }
            }
        }
        return $arr_resultado;
    }

    /**
     * Procedimiento para averiguar el ID de un grupo, dado su nombre.
     *
     * @param string    $sNombreUser    Login del usuario para buscar ID
     *
     * @return  mixed   Valor entero del ID de usuario, o FALSE en caso de error o si el usuario no existe
     */
    function getIdGroup($sNombreGroup)
    {
        $idGroup = FALSE;

        $this->errMsg = '';
        $sPeticionSQL = "SELECT id FROM acl_group WHERE name = ".paloDB::DBCAMPO($sNombreGroup);
        $result =& $this->_DB->conn->query($sPeticionSQL);
        if (DB::isError($result)) {
            $this->errMsg = $result->getMessage();
        } else {
            if($row = $result->fetchRow()) {
                $idGroup = (int)$row[0];
            }
        }
        return $idGroup;
    }
    
    /**
     * Procedimiento para asegurar que un usuario identificado por su ID pertenezca al grupo
     * identificado también por su ID. Se verifica primero que tanto el usuario como el grupo
     * existen en las tablas ACL.
     *
     * @param int   $id_user    ID del usuario que se desea agregar al grupo
     * @param int   $id_group   ID del grupo al cual se desea agregar al usuario
     *
     * @return bool VERDADERO si se puede agregar el usuario al grupo, o si ya pertenecía al grupo
     */
    function addToGroup($id_user, $id_group)
    {
        $bExito = FALSE;
        if (is_null($id_user) || is_null($id_group)) {
            $this->errMsg = "Se debe proporcionar ID de usuario y de grupo";
        } else if (is_array($listaUser =& $this->getUsers($id_user)) &&
            is_array($listaGrupo =& $this->getGroups($id_group))) {

            if (count($listaUser) == 0) {
                $this->errMsg = "No se encuentra el usuario con ID especifiado";
            } else if (count($listaGrupo) == 0) {
                $this->errMsg = "No se encuentra el grupo con el ID especificado";
            } else {
                // Verificar existencia de la combinación usuario-grupo
                $sPeticionSQL = "SELECT id FROM acl_membership WHERE id_user = $id_user AND id_group = $id_group";
                $listaMembresia =& $this->_DB->fetchTable($sPeticionSQL);
                if (!is_array($listaMembresia)) {
                    // Ocurre un error de base de datos
                    $this->errMsg = $this->_DB->errMsg;
                } else if (count($listaMembresia) > 0) {
                    // El usuario ya tiene membresía en el grupo - no se hace nada
                    $bExito = TRUE;
                } else {
                    // El usuario no tiene membresía en el grupo - se debe de agregar
                    $sPeticionSQL = paloDB::construirInsert(
                        'acl_membership', 
                        array(
                            'id_user'   =>  paloDB::DBCAMPO($id_user),
                            'id_group'  =>  paloDB::DBCAMPO($id_group),
                        ));
                    if (!($bExito = $this->_DB->genQuery($sPeticionSQL))) {
                        // Ocurre un error de base de datos
                        $this->errMsg = $this->_DB->errMsg;
                    }
                }
            }
        }
        return $bExito;
    }

    /**
     * Procedimiento para asegurar que un usuario ya no pertenece al grupo indicado
     *
     * @param int   $id_user    ID del usuario que se desea agregar al grupo
     * @param int   $id_group   ID del grupo al cual se desea agregar al usuario
     *
     * @return bool VERDADERO si se puede remover el usuario del grupo, FALSO en caso de error.
     */
    function delFromGroup($id_user, $id_group)
    {
        $bExito = FALSE;

        if (!ereg('^[[:digit:]]+$', "$id_user")) {
            $this->errMsg = "ID de usuario no es un ID numerico valido";
        } else if (!ereg('^[[:digit:]]+$', "$id_group")) {
            $this->errMsg = "ID de grupo no es un ID numerico valido";
        } else {
            $sql = "DELETE FROM acl_membership WHERE id_user = '$id_user' AND id_group = '$id_group'";
            if (!($bExito = $this->_DB->genQuery($sql))) {
                $this->errMsg = $this->_DB->errMsg;
            }
        }
        return $bExito;
    }

    /**
     * Procedimiento para leer la lista de acciones disponibles para validar. Si se
     * especifica un ID numérico de acción, el listado contendrá únicamente la acción
     * indicada. De otro modo, se listarán todas las acciones.
     *
     * @param int   $id_action  Si != NULL, indica el ID de la acción a leer
     *
     * @return mixed Matriz de la forma descrita abajo, o FALSE en caso de error
     *  array(
     *      array(id, name, description),
     *      ...
     *  )
     */
    function getActions($id_action = NULL)
    {
        $arr_result = FALSE;
        if (!is_null($id_action) && !ereg('^[[:digit:]]+$', "$id_action")) {
            $this->errMsg = "ID de accion no es un ID numerico valido";
        } else {
            $this->errMsg = "";
            $sPeticionSQL = "SELECT id, name, description FROM acl_action".
                (is_null($id_action) ? '' : " WHERE id = $id_action");
            $arr_result =& $this->_DB->fetchTable($sPeticionSQL);
            if (!is_array($arr_result)) {
                $arr_result = FALSE;
                $this->errMsg = $this->_DB->errMsg;
            }
        }
        return $arr_result;

    }

    /**
     * Procedimiento para crear una acción bajo el nombre descrito, con una descripción opcional.
     * Si una acción con el nombre indicado ya existe, se reemplaza la descripción.
     *
     * @param string    $name           Nombre de la acción a crear
     * @param string    $description    Descripción de la acción a crear, opcional
     *
     * @return bool     VERDADERO si la acción ya existe o fue creada/actualizada correctamente
     */
    function createAction($name, $description = '')
    {
        $bExito = FALSE;
        $this->errMsg = "";
        if ($groupname == "") {
            $this->errMsg = "Nombre de la accion no puede estar vac&iacute;o";
        } else {
            if ($description == '') $description = $groupname;

            // Verificar si la acción ya existe
            $sPeticionSQL =
                "SELECT description FROM acl_action ".
                "WHERE name = ".paloDB::DBCAMPO($groupname);
            $tupla =& $this->_DB->getFirstRowQuery($sPeticionSQL);
            if (!is_array($tupla)) {
                // Ocurre error de DB en consulta
                $this->errMsg = $this->_DB->errMsg;
            } else if (count($tupla) == 0) {
                // Acción no existía previamente
                $sPeticionSQL = paloDB::construirInsert(
                    "acl_action",
                    array(
                        "name"          =>  paloDB::DBCAMPO($groupname),
                        "description"   =>  paloDB::DBCAMPO($description),
                    )
                );
                if ($this->_DB->genQuery($sPeticionSQL)) {
                    $bExito = TRUE;
                } else {
                    $this->errMsg = $this->_DB->errMsg;
                }
            } else {
                // Acción existía previamente, se actualiza opcionalmente desc
                if ($tupla[0] != $description) {
                    // Se modifica descripción de acción existente
                    $sPeticionSQL = paloDB::construirUpdate(
                        'acl_action',
                        array('description' =>  paloDB::DBCAMPO($description)),
                        array('name'        =>  paloDB::DBCAMPO($groupname)));
                    if ($this->_DB->genQuery($sPeticionSQL)) {
                        $bExito = TRUE;
                    } else {
                        $this->errMsg = $this->_DB->errMsg;
                    }
                } else {
                    // Se intenta crear acción idéntica a existente en DB
                    $bExito = TRUE;
                }
            }
        }

        return $bExito;
    }

    /**
     * Procedimiento para obtener el listado de los recursos existentes en los ACL. Si
     * se especifica un ID numérico de recurso, el listado contendrá únicamente al recurso
     * indicado. De otro modo, se listarán todos los recursos.
     *
     * @param int   $id_rsrc    Si != NULL, indica el ID del recurso a recoger
     *
     * @return array    Listado de recursos en el siguiente formato, o FALSE en caso de error:
     *  array(
     *      array(id, name, description),
     *      ...
     *  )
     */
    function getResources($id_rsrc = NULL)
    {
        $arr_result = FALSE;
        if (!is_null($id_rsrc) && !ereg('^[[:digit:]]+$', "$id_rsrc")) {
            $this->errMsg = "ID de recurso no es un ID numerico valido";
        } else {
            $this->errMsg = "";
            $sPeticionSQL = "SELECT id, name, description FROM acl_resource".
                (is_null($id_rsrc) ? '' : " WHERE id = $id_rsrc");
            $arr_result =& $this->_DB->fetchTable($sPeticionSQL);
            if (!is_array($arr_result)) {
                $arr_result = FALSE;
                $this->errMsg = $this->_DB->errMsg;
            }
        }
        return $arr_result;
    }

    /**
     * Procedimiento para crear un recurso bajo el nombre descrito, con una descripción opcional.
     * Si un recurso con el nombre indicado ya existe, se reemplaza la descripción.
     *
     * @param string    $name           Nombre del grupo a crear
     * @param string    $description    Descripción del grupo a crear, opcional
     *
     * @return bool     VERDADERO si el grupo ya existe o fue creado/actualizado correctamente
     */
    function createResource($name, $description = NULL)
    {
        $bExito = FALSE;
        $this->errMsg = "";
        if ($name == "") {
            $this->errMsg = "Nombre del recurso no puede estar vac&iacute;o";
        } else {
            if ($description == '') $description = $name;

            // Verificar si el recurso ya existe
            $sPeticionSQL =
                "SELECT description FROM acl_resource ".
                "WHERE name = ".paloDB::DBCAMPO($name);
            $tupla =& $this->_DB->getFirstRowQuery($sPeticionSQL);
            if (!is_array($tupla)) {
                // Ocurre error de DB en consulta
                $this->errMsg = $this->_DB->errMsg;
            } else if (count($tupla) == 0) {
                // Recurso no existía previamente
                $sPeticionSQL = paloDB::construirInsert(
                    'acl_resource',
                    array(
                        "name"          =>  paloDB::DBCAMPO($name),
                        "description"   =>  paloDB::DBCAMPO($description),
                    )
                );
                if ($this->_DB->genQuery($sPeticionSQL)) {
                    $bExito = TRUE;
                } else {
                    $this->errMsg = $this->_DB->errMsg;
                }
            } else {
                // Recurso existía previamente, se actualiza opcionalmente desc
                if ($tupla[0] != $description) {
                    // Se modifica descripción de grupo existente
                    $sPeticionSQL = paloDB::construirUpdate(
                        'acl_resource',
                        array('description' =>  paloDB::DBCAMPO($description)),
                        array('name'        =>  paloDB::DBCAMPO($name)));
                    if ($this->_DB->genQuery($sPeticionSQL)) {
                        $bExito = TRUE;
                    } else {
                        $this->errMsg = $this->_DB->errMsg;
                    }
                } else {
                    // Se intenta crear recurso idéntico a existente en DB
                    $bExito = TRUE;
                }
            }
        }

        return $bExito;
    }

    /**
     * Procedimiento que devuelve un arreglo con todas las acciones y recursos
     *
     * array("calendar" => array("view"),
     *       "calendar" => array("edit"),
     *       "task"     => array("view"),
     *       "contact"  => array("edit"))
     *
     *  como se puede ver el indice es el recurso y el valor es la accion.
     *  Con un arreglo de esta forma se puede usar la funcion array_merge_recursive
     *  para hacer un merge entre los permisos del usuario y de sus grupos
     *
     * @param int   $id_user    ID del usuario para el que se devuelve acciones autorizadas sobre recursos
     *
     * @return mixed    Matriz que describe las acciones autorizadas, o NULL en caso de error
     */
    function getUserPermissions($id_user)
    {
        $arr_resultado = NULL;
        if (!ereg('^[[:digit:]]+$', "$id_user")) {
            $this->errMsg = "ID de usuario no es un ID numerico valido";
        } else {
            $sql =
                "SELECT a.name, r.name, up.id ".
                "FROM acl_user_permission as up, acl_action as a, acl_resource as r ".
                "WHERE up.id_user = $id_user AND up.id_action = a.id AND up.id_resource = r.id";
            $result = $this->_DB->conn->query($sql);
            if (DB::isError($result)) {
                $this->errMsg = $result->getMessage();
            } else {
                $arr_resultado = array();
                while($row = $result->fetchRow()) {
                    $indice  = $row[1];
                    $valor   = $row[0];
                    $indice2 = "u" . $row[2];
                    $arr_resultado[$indice][$indice2] = $valor;
                }
            }
        }
        return $arr_resultado;
    }

    /**
     * Procedimiento que agrega un permiso para una acción sobre un recurso para un usuario,
     * si el permiso para la acción sobre el usuario no existía antes.
     *
     * @param int   $id_user        ID del usuario para el que se agrega permiso
     * @param int   $id_action      ID de la acción para la que se agrega permiso
     * @param int   $id_resource    ID del recurso para el cual se agrega permiso
     *
     * @return bool VERDADERO si se agrega el permiso o si ya existía, FALSO en error
     */
    function addUserPermission($id_user, $id_action, $id_resource)
    {
        // revisar si ya existe tal permiso... si fuese asi no se deja ingresar otra vez
        $sql = "INSERT INTO acl_user_permission (id_action, id_user, id_resource) values ('$id_action', '$id_user', '$id_resource')";
        return $this->_DB->genQuery($sql);
    }

    function delUserPermission($idUserPermission)
    {
        $sql = "DELETE FROM acl_user_permission where id=$idUserPermission";
        return $this->_DB->genQuery($sql);
    }

    /**
     * Procedimiento que devuelve un arreglo con todas las acciones y recursos de un grupo
     *
     * array("calendar" => array("view"),
     *       "calendar" => array("edit"),
     *       "task"     => array("view"),
     *       "contact"  => array("edit"))
     *
     * @param int   $id_group   ID del grupo para el que se devuelve acciones autorizadas sobre recursos
     *
     * @return mixed    Matriz que describe las acciones autorizadas, o NULL en caso de error
     */
    function getGroupPermissions($id_group)
    {
        $arr_resultado = NULL;
        if (!ereg('^[[:digit:]]+$', "$id_group")) {
            $this->errMsg = "ID de grupo no es un ID numerico valido";
        } else {
            $sql =
                "SELECT a.name, r.name, gp.id ".
                "FROM acl_group_permission as gp, acl_action as a , acl_resource as r ".
                "WHERE gp.id_group = $id_group AND gp.id_action = a.id AND gp.id_resource = r.id";
            $result = $this->_DB->conn->query($sql);
            if (DB::isError($result)) {
                $this->errMsg = $result->getMessage();
            } else {
                $arr_resultado = array();
                while($row = $result->fetchRow()) {
                    $indice  = $row[1];
                    $valor   = $row[0];
                    $indice2 = "g" . $row[2];
                    $arr_resultado[$indice][$indice2] = $valor;
                }
            }
        }
        return $arr_resultado;
    }

    function addGroupPermission($id_group, $id_action, $id_resource)
    {
        $sql = "INSERT INTO acl_group_permission (id_action, id_group, id_resource) values ('$id_action', '$id_group', '$id_resource')";
        return $this->_DB->genQuery($sql);
    }

    function delGroupPermission($idUserPermission)
    {
        $sql = "DELETE FROM acl_group_permission where id=$idUserPermission";
        return $this->_DB->genQuery($sql);
    }

    function getArrayPermissionsByUsername($username)
    {
        $idUser = $this->getIdUser($username);
        return $this->getArrayPermissions($idUser);
    }

    /**
     * Procedimiento que construye un arreglo que describe los permisos que tiene el usuario
     * indicado por sí mismo y por su pertenecia a todos los grupos registrados.
     *
     * @param int   $id_user    ID del usuario para el que se recuperan los permisos
     *
     * @return mixed    Arreglo de todos los permisos del usuario, o NULL en caso de error
     */
    function getArrayPermissions($id_user)
    {
        $arr_priv = NULL;
        
        if (!ereg('^[[:digit:]]+$', "$id_user")) {
            $this->errMsg = "ID de usuario no es un ID numerico valido";
        } else {
            $listaUsuarios = $this->getUsers($id_user);
            if (is_array($listaUsuarios)) {
                if (count($listaUsuarios) == 0) {
                    $this->errMsg = "No se encuentra el usuario con el ID indicado";
                } else {
                    // Permisos personales del usuario
                    $arr_priv = $this->getUserPermissions($id_user);

                    // Agregar los permisos de los grupos del usuario
                    $arr_groups = $this->getMembership($id_user);
                    if (!is_array($arr_groups)) {
                        $arr_priv = NULL;
                    } else foreach ($arr_groups as $id_group) {
                        $arr_gpriv = $this->getGroupPermissions($id_group);
                        if (!is_array($arr_gpriv)) {
                            $arr_priv = NULL;
                            break;
                        } else {
                            $arr_priv = array_merge_recursive($arr_priv, $arr_gpriv);
                        }
                    }
                }
            }
        }
        return $arr_priv;
    }

    function isUserAuthorizedById($id_user, $action_name, $resource_name)
    {
        $arr_priv = $this->getArrayPermissions($id_user);
        // ahora hayo el subarreglo perteneciente al recurso en el cual estoy
        // interesado y busco si alli existe la accion

        if(!isset($arr_priv[$resource_name]) || !is_array($arr_priv[$resource_name])) {
            return FALSE; // probablemente porque no existia tal recurso o no tenia acciones
        }

        if(in_array($action_name, $arr_priv[$resource_name])) {
            return TRUE;
        } else {
            return FALSE;
        }

    }

    function isUserAuthorized($username, $action_name, $resource_name)
    {
        if($id_user = $this->getIdUser($username)) {
            $resultado = $this->isUserAuthorizedById($id_user, $action_name, $resource_name);
        } else {
            $resultado = false;
        }
        return $resultado;
    }

    function isUserAuthorizedWithoutHierarchy() // quiere decir sin contar los permisos de (el)los grupos a los que pertenece
    {

    }

    // Procedimiento para buscar la autenticación de un usuario en la tabla de ACLs.
    // Devuelve VERDADERO si el usuario existe y tiene el password MD5 indicado,
    // FALSE si no lo tiene, o en caso de error
    function authenticateUser ($user, $pass)
    {
        $user = trim($user);
        $pass = trim($pass);
        $pass = md5($pass);

        if ($this->_DB->connStatus) {
            return FALSE;
        } else {
            $this->errMsg = "";

            if($user == "" or $pass == "") {
                $this->errMsg = "El campo Usuario o Clave se dejaron vac&iacute;os";
                return FALSE;
            } else if (!ereg("^[[:alnum:]\\-_]+$", $user)) {
                $this->errMsg = "Campo de usuario debe consistir de caracteres alfanum&eacute;ricos, gui&oacute;n o subgui&oacute;n";
                return FALSE;
            } else if (!ereg("^[[:alnum:]]{32}$", $pass)) {
                $this->errMsg = "(interno) clave md5 no v&aacute;lida";
                return FALSE;
            }

            $sql = "SELECT name FROM acl_user WHERE name = '$user' AND md5_password = '$pass'";
            $arr =& $this->_DB->fetchTable($sql);
            if (is_array($arr)) {
                return (count($arr) > 0);
            } else {
                $this->errMsg = $this->_DB->errMsg;
                return FALSE;
            }
        }
    }

}
?>
