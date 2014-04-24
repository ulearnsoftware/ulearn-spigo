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
// $Id: ul_cartelera.class.php,v 1.1.1.1 2006/03/03 21:59:09 ainiguez Exp $

if (isset($gsRutaBase)){
     require_once ("$gsRutaBase/conf/default.conf.php");
     require_once ("$gsRutaBase/lib/paloEntidad.class.php");
     require_once ("$gsRutaBase/lib/paloACL.class.php");
}
else{
   require_once ("conf/default.conf.php");
   require_once ("lib/paloEntidad.class.php");
   require_once ("lib/paloACL.class.php");
}

class ul_cartelera extends PaloEntidad
{
   var $sBaseURL;
   function ul_cartelera(&$oDB, &$oPlantillas, $sBaseURL, $id_materia_periodo_lectivo,$id_cartelera='')
   {
      $this->sBaseURL=$sBaseURL;
      global $config;
      $oACL=getACL();
      setLocale(LC_TIME,$config->locale);

      $defTabla = PaloEntidad::describirTabla($oDB, "ul_cartelera");
      $defTabla["campos"]["id_cartelera"]["DESC"]   = "id de clave primaria del cartelera";
      $defTabla["campos"]["titulo"]["DESC"]      = "Título del cartelera";
      $defTabla["campos"]["contenido"]["DESC"]   = "Contenido del cartelera";
      $defTabla["campos"]["creacion"]["DESC"]    = "Fecha de Creacion del cartelera";
      $defTabla["campos"]["inicio"]["DESC"]      = "Fecha en que Comienza el cartelera";
      $defTabla["campos"]["final"]["DESC"]       = "Fecha en que Termina el cartelera";
      $this->PaloEntidad($oDB, $oPlantillas, $defTabla);

      //getdate();
      if (!$this->definirFormulario("INSERT", "CREAR_CARTELERA",
         array(
            "title"     =>  "Crear Cartelera<br>\n".
               "<input type='hidden' name='action' value='crear_cartelera'>".
               "<input type='hidden' name='id_materia_periodo_lectivo' value=$id_materia_periodo_lectivo>".
               "<a href=\"?menu1op=submenu_agenda&submenuop=cart_lista&id_materia_periodo_lectivo=$id_materia_periodo_lectivo\">&laquo;&nbsp;Regresar</a>&nbsp;",
            "submit"    =>  array( "name" => "submit_cartelera", "value" => "Guardar", ),
            "fields"    =>  array(
               array(
                  "type"      =>    "html",
                  "tag"       =>    "Fecha Actual:",
                  "name"      =>    "actual",
                  'value'     =>    utf8_encode(strftime("%A, %e de %B %Y %T")),
                  ),
               array(
                  "type"      =>    "hidden",
                  "name"      =>    "creacion",
                  "value"     =>    strftime("%Y-%m-%d %T"),
                  "_empty"    =>    TRUE,
                  "_field"    =>    "creacion",
                  ),
               array(
                  "type"      =>    "varchar",
                  "tag"       =>    "Título:",
                  "name"      =>    "titulo",
                  "_empty"    =>    FALSE,
                  "_field"    =>    "titulo",
                  ),
               array(
                  "type"      =>    "textarea",
                  "tag"       =>    "Contenido:",
                  "name"      =>    "contenido",
                  "value"     =>    NULL,
                  "_empty"    =>    FALSE,
                  "_field"    =>    "contenido",
                  "rows"      =>    4,
                  "cols"      =>    50,
                  "maxlength" =>    500,
                  ),
               array(
                  "type"      =>    "html",
                  "tag"       =>    "Fecha de Comienzo:",
                  "value"     =>    get_datetime("inicio",1),
                  ),
               array(
                  "type"      =>    "html",
                  "tag"       =>    "Fecha de Término:",
                  "value"     =>    get_datetime("final",1),
                  ),
            ),
         ))) die ("ul_cartelera::ul_cartelera() - al definir formulario INSERT CREAR_CARTELERA - ".$this->_msMensajeError);

     }


   /**
   * Procedimiento que valida que las copias de las claves de acceso sean iguales.
   *
   * @param string $sNombreFormulario Nombre del formulario que se est�manejando
   * @param array  $formVars          Variables obtenidas del formulario, en arreglo asociativo
   *
   * @return boolean TRUE si los par�etros parecen v�idos hasta ahora, FALSE si no lo son.
   * La rutina puede asignar $this->_msMensajeError a un texto explicativo del error.
   */
   function event_validarValoresFormularioInsert($sNombreFormulario, $formVars){
      $oACL=getACL();
      $bValido=TRUE;

      switch ($sNombreFormulario) {
      case "CREAR_CARTELERA":

         if (!$oACL->isUserAuthorized($_SESSION['session_user'], 'admin', 'cart_lista')){
            $this->setMessage("Usted no está autorizado para realizar esta acción");
            return FALSE;
         }

         if(!checkdate($_POST['inicio_mes'],$_POST['inicio_dia'],$_POST['inicio_anio'])){
            $this->setMessage("La fecha de comienzo no es valida");
            return FALSE;
         }
         if(!checkdate($_POST['final_mes'],$_POST['final_dia'],$_POST['final_anio'])){
            $this->setMessage("La fecha de término no es valida");
            return FALSE;
         }
         if(mktime($_POST['inicio_hora'],$_POST['inicio_minuto'],0,$_POST['inicio_mes'],$_POST['inicio_dia'],$_POST['inicio_anio']) >=
         mktime($_POST['final_hora'],$_POST['final_minuto'],0,$_POST['final_mes'],$_POST['final_dia'],$_POST['final_anio'])){
            $this->setMessage("La fecha de término debe ser mayor a la fecha de comienzo");
            return FALSE;
         }
         break;
      }
      return $bValido;
   }

    /**
     * Procedimiento para realizar la insercin en la tabla acl_user ANTES de insertar en
     * la tabla sa_alumno. El ID de insercin en acl_user es requerido para la insercin
     * en sa_alumno.
     *
     * @param string $sNombreFormulario Nombre del formulario que se est�manejando
     * @param array  $dbVars            Variables a insertar en la base de datos, con comillas
     * @param array  $formVars          Variables del formulario de insercin
     *
     * @return boolean TRUE si se complet la precondicin, FALSE si no.
     */
   function event_precondicionInsert($sNombreFormulario, &$dbVars, $formVars) {
      global $config;

      $oDB = $this->_db;
      $this->setMessage("");
      $bValido = parent::event_precondicionInsert($sNombreFormulario, $dbVars, $formVars);
      if ($bValido){
         
         switch ($sNombreFormulario) {
         case "CREAR_CARTELERA":
            $ini_t=mktime($_POST['inicio_hora'],$_POST['inicio_minuto'],0,$_POST['inicio_mes'],$_POST['inicio_dia'],$_POST['inicio_anio']);
            $dbVars['inicio'] = strftime("%Y-%m-%d %T",$ini_t);

            $fin_t=mktime($_POST['final_hora'],$_POST['final_minuto'],0,$_POST['final_mes'],$_POST['final_dia'],$_POST['final_anio']);
            $dbVars['final'] = strftime("%Y-%m-%d %T",$fin_t);

            if(isset($formVars['contenido'])){
               trim($formVars['contenido']);
               if(strlen($formVars['contenido'])==0)
                  $dbVars['contenido']=NULL;
            }
            else
               $dbVars['contenido']=NULL;

            break;
         }
      }
      return $bValido;
  }



   /**
    * Verificar que el login no sea usado por otro usuario al actualizar alumno.
    *
    * @param string $sNombreFormulario Nombre del formulario que se est�manejando
    * @param array  $prevPK            Clave primaria previa del registro modificado
    * @param array  $formVars          Variables obtenidas del formulario, en arreglo asociativo
    *
    * @return boolean TRUE si los par�etros parecen v�idos hasta ahora, FALSE si no lo son.
    * La rutina puede asignar $this->_msMensajeError a un texto explicativo del error.
    */
   function event_validarValoresFormularioUpdate($sNombreFormulario, $prevPK, $formVars)
   {       ///////Si el usuario tiene los permisos para ingresar se permite la accion, si no se deniega
      $oACL=getACL();
      $bValido=TRUE;

      switch ($sNombreFormulario) {
      case "MODIFICAR_CARTELERA":

         if (!$oACL->isUserAuthorized($_SESSION['session_user'], 'admin', 'cart_lista')){
            $this->setMessage("Usted no está autorizado para realizar esta acción");
            return FALSE;
         }


         break;
      }
      return $bValido;
   }

  /**
   * Procedimiento para realizar operaciones previas a la insercion de la tupla en la base
   * de datos. Esta implementacion guarda el valor previo del login, y modifica el login para
   * guardar el nuevo valor indicado en el formulario.
   *
   * @param string $sNombreFormulario Nombre del formulario que se est�manejando
   * @param array  $prevPK            Clave primaria previa del registro modificado
   * @param array  $dbVars            Variables a insertar en la base de datos, con comillas
   * @param array  $formVars          Variables del formulario de insercin
   *
   * @return boolean TRUE si se complet la precondicin, FALSE si no.
   */
   function event_precondicionUpdate($sNombreFormulario, $prevPK, &$dbVars, $formVars){
      global $config;
      $oDB = $this->_db;
      $bExito = parent::event_precondicionUpdate($sNombreFormulario, $prevPK, $dbVars, $formVars);

      if ($bExito)

         switch ($sNombreFormulario){
         case "MODIFICAR_CARTELERA":
            ///TODO obtener el valor del combo orden y guardar el valor en dbVars
            //Verificar que el orden sea unico y que se cambie al resto de carteleras
            //Si el cambio es correcto entonces se continua con el insert si no se retorna FALSE
            //$dbVars['cartelera'] = strip_tags($formVars['cartelera'], $config->html_default);

            break;

         }
      return $bExito;
   }


   // obtiene el id de la materia
   // utiliza el $id_materia_periodo_lectivo del formulario (Siempre existe).
   function obtener_id_materia($id_materia_periodo_lectivo){
      $oDB=$this->getDB();
      $sQuery="SELECT id_materia from ul_materia_periodo_lectivo where id=$id_materia_periodo_lectivo";
      $result=$oDB->getFirstRowQuery($sQuery);
      $str="";
      if(is_array($result) && count($result)>0){
         $str=$result[0];
      }
      return $str;
   }


   function visualizar_cartelera($_Get){
      global $config;
      $id_cartelera = $_Get['id_cartelera'];
      $id_materia_periodo_lectivo = $_Get['id_materia_periodo_lectivo'];

      ///SE debe buscar los datos del cartelera

      if(!ereg("^[0-9]+$",$id_cartelera)){
         return "No se ha recibido un id_cartelera válido";
      }
      else{
         ////Se crea un objeto plantilla
         $insTpl =& new paloTemplate("skins/".$config->skin);
         $insTpl->definirDirectorioPlantillas("cartelera");
         $insTpl->assign("IMG_PATH", "skins/$config->skin/images");

          ///Verificar que el cartelera pertenezca a la materia periodo_lectivo
         $oDB=$this->_db;
         $sQuery="SELECT * from ul_cartelera WHERE id_cartelera=$id_cartelera";
         $result=$oDB->getFirstRowQuery($sQuery,true);
         $strCartelera="";

         if(is_array($result) && count($result)>0){
            //"creacion"
            //"id_materia_periodo_lectivo"
            $titulo = $result['titulo'];
            $contenido = $result['contenido'];
            $inicio = $result['inicio'];
            $final = $result['final'];

            //?menu1op=submenu_agenda&submenuop=cart_lista
            $insTpl->assign("LINK_REGRESAR", "<a class='letra_10' href='".$this->sBaseURL."&id_materia_periodo_lectivo=".$id_materia_periodo_lectivo."'>&laquo;&nbsp;Regresar</a>");
            $fecha_hora = explode(' ',$final);
            $fecha = explode('-',$fecha_hora[0]);
            $hora = explode(':',$fecha_hora[1]);

            $exp_t=mktime($hora[0], $hora[1], $hora[2], $fecha[1], $fecha[2], $fecha[0]);
            //$date=date("l dS of F Y h:i:s A",$exp_t);
            $date=utf8_encode(strftime("%A, %e de %B %Y %T",$exp_t));

            $insTpl->assign("EXPIRACION", $date);
            $insTpl->assign("TITULO", $titulo);
            $contenido = nl2br($contenido);

            $insTpl->assign("CONTENIDO", $contenido);

            $insTpl->parse("SALIDA", "tpl_cartelera");
            $strCartelera=$insTpl->fetch("SALIDA");
         }
         //echo nl2br(htmlentities($strCartelera));
         return $strCartelera;
      }
   }
}

?>
