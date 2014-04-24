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
// $Id: ul_usuario.class.php,v 1.1.1.1 2006/03/03 21:59:09 ainiguez Exp $

require_once ("lib/paloEntidad.class.php");
require_once ("lib/paloACL.class.php");


class ul_usuario extends PaloEntidad
{
    function ul_usuario(&$oDB, &$oPlantillas)
    {
        $defTabla = PaloEntidad::describirTabla($oDB, "acl_user");
        $defTabla["campos"]["id"]["DESC"]                = "id de clave primaria de alumno";
        $defTabla["campos"]["md5_password"]["DESC"]      = "clave";
        $this->PaloEntidad($oDB, $oPlantillas, $defTabla);

        $login=$_SESSION['session_user'];

        // Construir todos los formularios requeridos para sa_alumno
         if (!$this->definirFormulario("UPDATE", "CAMBIO_PASSWORD", array(
            "title"     =>  "Cambiar clave de Usuario<br>\n&nbsp;",
            "submit"    =>  array(
                "name"   =>  "submit_alumno",
                "value"  =>  "Cambiar clave",
            ),
            "fields"    =>  array(
                array (
                    "tag"       =>  "Login Usuario:",
                    "title"     =>  "Login del usuario para el sistema",
                    "name"      =>  "login",
                    "type"      =>  "label",
                    "value"     =>  $login,
                    ),
                array (
                    "tag"       =>  "Clave Actual:",
                    "title"     =>  "Ingrese la clave de acceso actual para este usuario",
                    "name"      =>  "clave_actual",
                    "type"      =>  "password",
                    "size"      =>  20,
                    "maxlength" =>  100,
                    "_empty"    =>  FALSE,
                ),
                array (
                    "tag"       =>  "Nueva Clave:",
                    "title"     =>  "Ingrese la nueva clave",
                    "name"      =>  "clave_usuario[]",
                    "type"      =>  "password",
                    "size"      =>  20,
                    "maxlength" =>  100,
                    "_empty"    =>  FALSE,
                ),
                array (
                    "tag"       =>  "Confirme clave de acceso:",
                    "title"     =>  "Confirme la clave de acceso para este usuario",
                    "name"      =>  "clave_usuario[]",
                    "type"      =>  "password",
                    "size"      =>  20,
                    "maxlength" =>  100,
                    "_empty"    =>  FALSE,
                ),
            ),
        ))) die ("ul_usuario() - al definir formulario UPDATE CAMBIO_PASSWORD - ".$this->_msMensajeError);
    }


    function event_validarValoresFormularioUpdate($sNombreFormulario, $prevPK,$formVars)
    { $oACL=getACL();
      $bExito = TRUE;

      switch ($sNombreFormulario) {
        case "CAMBIO_PASSWORD":
            ///Verificar que la clave actual sea igual a la de la base de datos
            if(!$oACL->authenticateUser ($_SESSION['session_user'], $formVars['clave_actual'])){
               $this->setMessage("La clave actual es incorrecta.");
               return FALSE;
            }

            if($formVars["clave_usuario"][0] != $formVars["clave_usuario"][1]){
               $this->setMessage("La clave y su confirmacin no son iguales.");
               return FALSE;
            }

            //Verificar que la clave tenga numeros o letras y no caracteres especiales
            if(!ereg("^[A-Za-z0-9]+$",$formVars['clave_usuario'][0])){
               $this->setMessage("La clave solo puede tener valores alfanuméricos");
               return FALSE;
            }


           break;
        }
        return $bExito;
    }

    /**
     * Procedimiento para realizar operaciones previas a la insercin de la tupla en la base
     * de datos. Esta implementacin guarda el valor previo del login, y modifica el login para
     * guardar el nuevo valor indicado en el formulario.
     *
     * @param string $sNombreFormulario Nombre del formulario que se est�manejando
     * @param array  $prevPK            Clave primaria previa del registro modificado
     * @param array  $dbVars            Variables a insertar en la base de datos, con comillas
     * @param array  $formVars          Variables del formulario de insercin
     *
     * @return boolean TRUE si se complet la precondicin, FALSE si no.
     */
    function event_precondicionUpdate($sNombreFormulario,$prevPK, &$dbVars, $formVars)
    {  global $config;
       $oACL=getACL();
       $bExito=TRUE;

         switch ($sNombreFormulario) {
            case "CAMBIO_PASSWORD":
               $sLoginAlumno = $_SESSION['session_user'];
               $id_acl_user = $oACL->getIdUser($sLoginAlumno);
               $md5_password=md5($formVars['clave_usuario'][0]);

               ///Primero se debe crear una conexion con el sistema academico y guardar la clave ahi
               $dbSIGA=new paloDB($config->dsn2);
               $oACL_SIGA=new paloACL($dbSIGA);

                  if($dbSIGA->errMsg!=""){
                     $this->setMessage("No se pudo realizar conexión con sistema académico.");
                     return FALSE;
                  }

                  ///Se actualiza la clave en el sistema academico
                  if (!$oACL_SIGA->changePassword($id_acl_user,$md5_password)) {
                     $this->setMessage("Al actualizar clave en sistema Académico - ".$oACL_SIGA->errMsg);
                     return FALSE;
                  }
                  else{
                     ///Si no existe error al actualizar el SIGA se actualiza el ULEARN
                     $dbVars['md5_password']=$md5_password;
                  }
            break;
        }
        return $bExito;
    }


}

?>
