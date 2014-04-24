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
// | Autores: Iv� Ochoa    <iochoa2@telefonica.net>                         |
// +----------------------------------------------------------------------+
//
// $Id: download.php,v 1.1.1.1 2006/03/03 21:59:09 ainiguez Exp $
session_start();
$gsRutaBase="..";
require_once ("../lib/paloTemplate.class.php");
require_once ("../lib/paloDB.class.php");
require_once ("../lib/paloACL.class.php");
require_once ("ul_download.class.php");
//require_once ("ul_recurso.class.php");
require_once ("../lib/misc.lib.php");
require_once ("../conf/default.conf.php");

$dsn =& $config->dsn;
$pDB = new paloDB($dsn);
print _moduleContent($pDB, $_GET, $_POST);


function _moduleContent(&$pDB, &$_GET, &$_POST)
{
    global $config; // definda en conf/default.conf.php

    $insDB =& new PaloDB($config->dsn);
    $insTpl =& new paloTemplate("../skins/".$config->skin);
    $insTpl->definirDirectorioPlantillas("");
    $insTpl->assign("IMG_PATH", "../skins/$config->skin/images");
    $db=$insDB;
    $sContenido="";
    $login=$_SESSION['session_user'];

    //Si existe un session_user se continua
      if($login!=""){
         $id_recurso=recoger_valor("id",$_GET,$_POST,"");

            if($id_recurso!=""){
               ///////Si el id recurso es distinto de NULL se debe verificar que el id_recurso este disponible al usuario
               $oACL=new paloACL($db);
               $id_user=$oACL->getIdUser($login);  //Se obtiene el id_user
               $id_grupo=obtener_grupo_usuario($oACL,$login); //Se obtiene el id_grupo
               $grupo=getEnumDescripcion("Grupo",$id_grupo);  //Se obtiene el nombre del grupo asociado a id_grupo
               $oDownload =& new ul_download($insDB,$insTpl,$id_recurso,$login);

               $id_materia=$oDownload->id_materia;
               $id_materia_periodo_lectivo=$oDownload->id_materia_periodo_lectivo;
               $tipo=$oDownload->tipo;
               $sQuery="";

                  if($tipo=='D')
                     return alerta("No se puede descargar un directorio");

                  if($id_materia==NULL)
                     return alerta("No existe referencia de materia.");

                        ///Dependiendo del grupo se busca si el usuario puede o no tener acceso al recurso
                  switch($grupo){
                     case "administrador":
                     case "decano":
                           ////TODO
                           $sQuery="SELECT count(*) from ul_docente where 1";
                           break;

                     case "docente":
                           ///TODO verificacion de coincidencia de materias recurso relacionadas con docente
                           $sQuery="SELECT count(*) from ul_docente where 1";

                           break;

                     case "representante":
                           break;

                     case "alumno":
                           //Se obtiene el id del alumno
                           $sPeticionSQL="SELECT id FROM ul_alumno where id_acl_user=$id_user";
                           $result=$db->getFirstRowQuery($sPeticionSQL);
                              if(is_array($result) && count($result)>0){
                                 $id_alumno=$result[0];

                                    if($id_materia_periodo_lectivo!=NULL)
                                       $clause_where="and am.id_materia_periodo_lectivo=$id_materia_periodo_lectivo";
                                    else
                                       $clause_where="";
                                 ///Se busca que las materias del recurso coincidan con las materias relacionadas del alumno
                                 $sQuery="SELECT count(*) ".
                                          "FROM ul_alumno_materia am ".
                                          "WHERE am.id_materia=$id_materia and am.id_alumno=$id_alumno ".$clause_where;
                              }
                           break;

                     default:
                        $arrGrupo=$oACL->getGroups($id_grupo);
                        ///Si el usuario pertenece a un grupo relacionado con la materia entonces de debe permitir
                        ///descargar el recurso
                           if($id_materia_periodo_lectivo!=NULL)
                              $clauseWhere="AND mg.id_materia_periodo_lectivo=$id_materia_periodo_lectivo";
                           else
                              $clauseWhere="";

                        $sQuery="SELECT count(mg.id) FROM ul_materias_grupo mg,acl_group g,acl_membership m WHERE ".
                                "mg.id_group=g.id AND g.name='{$arrGrupo[0][1]}' and m.id_group=g.id and ".
                                "m.id_user=$id_user $clauseWhere";
                  }
                     ////Se ejecuta el query para comprobar que las materias relacionadas al recurso esten relacionadas con el usuario
                  if($sQuery!=""){
                     $res=$db->getFirstRowQuery($sQuery);
                        if(is_array($res)){
                           if($res[0]>0){    ///Si las materias están efectivamente relacioandas al alumno se permite descargar el recurso
                                 if(isset($oDownload->URL))
                                    $oDownload->descargar_archivo();
                           }
                           else
                              return alerta("No esta autorizado para descargar este recurso.");
                        }
                  }
            }
      }
      else
         $sContenido.=alerta("Acceso Denegado");


    return $sContenido;
}
function alerta($mensaje){
$str="<script>alert(\"".$mensaje."\");</script>";
return $str;

}
?>
