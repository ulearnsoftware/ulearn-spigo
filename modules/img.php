<?
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
// $Id: img.php,v 1.1.1.1 2006/03/03 21:59:09 ainiguez Exp $

session_start();
$gsRutaBase="..";

require_once("../lib/paloDB.class.php");
require_once("../conf/default.conf.php");
require_once("../lib/misc.lib.php");
require_once("ul_archivo_calificable.class.php");


$db=new paloDB($config->dsn);

$oFile=new ul_archivo_calificable($db);

$id_materia_periodo_lectivo=recoger_valor("id_materia_periodo_lectivo",$_GET,$_POST);
$URL=recoger_valor("URL",$_GET,$_POST);

$str=$oFile->abrir_archivo($id_materia_periodo_lectivo,$URL);
print $str;

?>
