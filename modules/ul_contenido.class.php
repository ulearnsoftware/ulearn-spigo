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
// $Id: ul_contenido.class.php,v 1.1.1.1 2006/03/03 21:59:09 ainiguez Exp $

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

class ul_contenido extends PaloEntidad
{
   function ul_contenido(&$oDB, &$oPlantillas,$id_materia_periodo_lectivo,$id_contenido='')
   {
      $oACL=getACL();

      $defTabla = PaloEntidad::describirTabla($oDB, "ul_contenido");
      $defTabla["campos"]["id_contenido"]["DESC"] = "id de clave primaria del contenido";
      $defTabla["campos"]["orden"]["DESC"]         = "Orden en el que se mostrarán los contenidos";
      $defTabla["campos"]["titulo"]["DESC"]       = "Título del contenido";
      $defTabla["campos"]["contenido"]["DESC"]    = "Texto del Contenido";
      $defTabla["campos"]["id_materia"]["DESC"]   = "Id de materia";
      $this->PaloEntidad($oDB, $oPlantillas, $defTabla);

      $arr_orden[0] = array("value"  => 1,
         "tag"    => "Al comienzo", );

      $id_materia = $this -> obtener_id_materia($id_materia_periodo_lectivo);
      $sQuery = "SELECT id_contenido, titulo, orden FROM ul_contenido WHERE id_materia=$id_materia ORDER BY orden";
      $result=$oDB->fetchTable($sQuery, TRUE);
      $count = count($result);

      // posicion (orden) donde insertar
      $posicion = 0;
      if(is_array($result) && $count>0){
         $orden=1;
         for($i=0; $i<$count; $i++){
            $orden = $result[$i]['orden'];
            $arr_orden[$i+1] = array(
               "value"  => $orden+1,
               "tag"    => "Después de '".$result[$i]['titulo']."'", );
         }
         $arr_orden[++$i] = array("value"  => $orden+1,
            "tag"    => "Al final", );
         $posicion = $i;
      }

      // posición (orden) a modificar
      $mantener_orden=NULL;
      $arr_mod_orden=array();
      if(is_array($result) && $count>0){
         $orden=1;
         $mantener_orden=NULL;
         for($i=0; $i<$count; $i++){
            $orden = $result[$i]['orden'];
            if($id_contenido==$result[$i]['id_contenido']){
               $mantener_orden=$orden;
            }
            if($mantener_orden==NULL)
               $arr_mod_orden[$i] = array(
                  "value"  => $orden,
                  "tag"    => "Antes de '".$result[$i]['titulo']."'", );
            elseif($mantener_orden<$orden)
               $arr_mod_orden[$i] = array(
                  "value"  => $orden,
                  "tag"    => "Después de '".$result[$i]['titulo']."'", );
            else
               $arr_mod_orden[$i] = array(
                  "value"  => $orden,
                  "tag"    => "Mantener '".$result[$i]['titulo']."'", );
         }
      }



      if (!$this->definirFormulario("INSERT", "CREAR_CONTENIDO",
         array(
            "title"     =>  "Crear Contenido<br>\n".
               "<input type='hidden' name='action' value='crear_contenido'>".
               "<input type='hidden' name='id_materia_periodo_lectivo' value=$id_materia_periodo_lectivo>".
               "<a href=\"?menu1op=submenu_contenido&submenuop=con_lista&id_materia_periodo_lectivo=$id_materia_periodo_lectivo\">&laquo;&nbsp;Regresar</a>&nbsp;",
            "submit"    =>  array( "name" => "submit_contenido", "value" => "Guardar", ),
            "fields"    =>  array(
               array(
                  "tag"       =>    "Posición:",
                  "name"      =>    "orden",
                  "type"      =>    "select",
                  "options"   =>    $arr_orden,
                  "value"     =>    $posicion,
                  "_field"    =>    "orden",
                  ),
               array(
                  "type"      =>    "varchar",
                  "tag"       =>    "Observación:",
                  "name"      =>    "observacion",
                  "_empty"    =>    TRUE,
                  "_field"    =>    "observacion",
                  ),     
               array(
                  "tag"       =>    "Título:",
                  "name"      =>    "titulo",
                  "_empty"    =>    FALSE,
                  "_field"    =>    "titulo",
                  'size'      =>    40,
                  ),
               array(
                  "tag"       =>    "Contenido:",
                  "name"      =>    "contenido",
                  "_field"    =>    "contenido",
                  '_empty'    =>    FALSE,
                  'cols'      =>    60,
                  'rows'      =>    20,
                  ),
               array("tag"      =>     "Estatus:",
                     "name"      =>    "estatus",
                     "_field"    =>    "estatus",
                     '_empty'    =>    FALSE,
                     ),
               array(
                  "type"      =>    "hidden",
                  "name"      =>    "id_materia_periodo_lectivo",
                  "value"     =>    $id_materia_periodo_lectivo,
                  ),
            ),
         ))) die ("ul_contenido::ul_contenido() - al definir formulario INSERT CREAR_CONTENIDO - ".$this->_msMensajeError);


      if (!$this->definirFormulario("UPDATE", "MODIFICAR_CONTENIDO",
         array(
            "title"     =>  "Modificar Contenido<br>\n".
               "<input type='hidden' name='action' value='modificar_contenido'>".
               "<input type='hidden' name='in_contenido' value=$id_contenido>".
               "<input type='hidden' name='id_materia_periodo_lectivo' value=$id_materia_periodo_lectivo>".
               "<a href=\"?menu1op=submenu_contenido&submenuop=con_lista&id_materia_periodo_lectivo=$id_materia_periodo_lectivo\">&laquo;&nbsp;Regresar</a>&nbsp;",
            "submit"    =>  array( "name" => "submit_contenido", "value" => "Guardar", ),
            "fields"    =>  array(
               array(
                  "tag"          =>  "orden_actual",
                  "type"         =>  "hidden",
                  "name"         =>  "orden_actual",
                  "value"        =>  $mantener_orden,
                  '_empty'    =>    FALSE, ),
               array("tag"       =>    "Posición:",
                     "name"      =>    "orden",
                     "type"      =>    "select",
                     "options"   =>    $arr_mod_orden,
                     "value"     =>    $mantener_orden,
                     "_field"    =>    "orden",
                     ),
               array(
                  "type"      =>    "varchar",
                  "tag"       =>    "Observación:",
                  "name"      =>    "observacion",
                  "_empty"    =>    TRUE,
                  "_field"    =>    "observacion",
                  ),  
               array("tag"       =>    "Título:",
                     "name"      =>    "titulo",
                     "_empty"    =>    FALSE,
                     "_field"    =>    "titulo",
                     ),
               array("tag"      =>     "Contenido:",
                     "name"      =>    "contenido",
                     "_field"    =>    "contenido",
                     '_empty'    =>    FALSE,
                     'cols'      =>    60,
                     'rows'      =>    20,
                     ),
                array("tag"      =>     "Estatus:",
                     "name"      =>    "estatus",
                     "_field"    =>    "estatus",
                     '_empty'    =>    FALSE,
                     ),
               array("type"      =>    "hidden",
                     "name"      =>    "id_materia_periodo_lectivo",
                     "value"     =>    $id_materia_periodo_lectivo,
                     ),
            ),
         ))) die ("ul_contenido::ul_contenido() - al definir formulario UPDATE MODIFICAR_CONTENIDO - ".$this->_msMensajeError);



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
      case "CREAR_CONTENIDO":

         if (!$oACL->isUserAuthorized($_SESSION['session_user'], 'admin', 'con_lista')){
            $this->setMessage("Usted no está autorizado para realizar esta acción");
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
         $id_materia=$this->obtener_id_materia($formVars['id_materia_periodo_lectivo']);
         switch ($sNombreFormulario) {
         case "CREAR_CONTENIDO":
            // actualizar el orden
            $sQuery = "UPDATE ul_contenido SET orden=orden+1 WHERE orden>=".$formVars['orden']." AND id_materia=".$id_materia;
            $result = $oDB->genQuery($sQuery);

            if($result===FALSE){
               $this->setMessage("No se pudo realizar la actualización del Contenido.");
               return FALSE;
            }

            $dbVars['contenido'] = strip_tags($formVars['contenido'],$config->html_default);
            //$dbVars['contenido'] = $formVars['contenido'];

            ///TODO obtener el valor del combo orden y guardar el valor en dbVars
            //Verificar que el orden sea unico y que se cambie al resto de contenidos
            //Si el cambio es correcto entonces se continua con el insert si no se retorna FALSE


            $dbVars['id_materia']=$this->obtener_id_materia($formVars['id_materia_periodo_lectivo']);

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
      case "MODIFICAR_CONTENIDO":

            if (!$oACL->isUserAuthorized($_SESSION['session_user'], 'admin', 'con_lista')){
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
         $id_materia=$this->obtener_id_materia($formVars['id_materia_periodo_lectivo']);
         switch ($sNombreFormulario){
         case "MODIFICAR_CONTENIDO":
            $orden = $formVars['orden'];
            $orden_actual = $formVars['orden_actual'];
            if($orden!=$orden_actual){
               if($orden<$orden_actual){
                  $sQuery = "UPDATE ul_contenido SET orden=orden+1 WHERE (orden BETWEEN ".$orden." AND ".($orden_actual-1).") AND id_materia=".$id_materia;
               }elseif($orden>$orden_actual){
                  $sQuery = "UPDATE ul_contenido SET orden=orden-1 WHERE (orden BETWEEN ".($orden_actual+1)." AND ".$orden.") AND id_materia=".$id_materia;
               }

               $result = $oDB->genQuery($sQuery);

               if($result===FALSE){
                  $this->setMessage("No se pudo realizar la actualización del Contenido.");
                  return FALSE;
               }
            }

            ///TODO obtener el valor del combo orden y guardar el valor en dbVars
            //Verificar que el orden sea unico y que se cambie al resto de contenidos
            //Si el cambio es correcto entonces se continua con el insert si no se retorna FALSE
            $dbVars['contenido'] = strip_tags($formVars['contenido'], $config->html_default);

            break;

         }
      return $bExito;
   }

   
   
   function es_alumno($login){
      $oACL=getACL();
      $id_user=$oACL->getIdUser($login);
      $grupos=$oACL->getMembership($id_user);
               
        if(is_array($grupos) && array_key_exists("alumno",$grupos)){
          return TRUE;
        }
        else
          return FALSE;
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


   function visualizar_contenido($_Get){
      global $config;
      $id_contenido = $_Get['id_contenido'];
      $id_materia_periodo_lectivo = $_Get['id_materia_periodo_lectivo'];

      ///SE debe buscar los datos del contenido

      if(!ereg("^[0-9]+$",$id_contenido)){
         return "No se ha recibido un id_contenido válido";
      }
      else{
         ////Se crea un objeto plantilla
         $insTpl =& new paloTemplate("skins/".$config->skin);
         $insTpl->definirDirectorioPlantillas("contenido");
         $insTpl->assign("IMG_PATH", "skins/$config->skin/images");

          ///Verificar que el contenido pertenezca a la materia periodo_lectivo
         $oDB=$this->_db;
         $clause="";
            if($this->es_alumno($_SESSION['session_user']))
              $clause="AND estatus='A'";
              
         $sQuery="SELECT * from ul_contenido WHERE id_contenido=$id_contenido $clause";
         $result=$oDB->getFirstRowQuery($sQuery,true);
         $strContenido="";

         if(is_array($result) && count($result)>0){
            $titulo = $result['titulo'];
            $contenido = $result['contenido'];
            $orden = $result['orden'];
            $id_materia = $result['id_materia'];

            // anterior
            $oDB=$this->_db;
            $sQuery="SELECT id_contenido from ul_contenido WHERE id_materia=$id_materia $clause AND orden=".($orden-1);
            $result=$oDB->getFirstRowQuery($sQuery,true);
            if(is_array($result) && count($result)>0){
               $id_anterior = $result['id_contenido'];
            }

            $sQuery="SELECT id_contenido from ul_contenido WHERE id_materia=$id_materia $clause AND orden=".($orden+1);
            $result=$oDB->getFirstRowQuery($sQuery,true);
            if(is_array($result) && count($result)>0){
               $id_siguiente = $result['id_contenido'];
            }

            // siguiente
            if(isset($id_anterior)){
               $insTpl->assign("ATRAS", "<a class='letra_10' href='?id_materia_periodo_lectivo=".$id_materia_periodo_lectivo."&menu1op=submenu_contenido&submenuop=con_lista&action=mostrar_contenido&id_contenido=".$id_anterior."'> &laquo; atrás </a>");
            }
            else{
               $insTpl->assign("ATRAS", "&laquo; atrás ");
            }

            $insTpl->assign("A_CONTENIDO", "<a class='letra_10' href='?menu1op=submenu_contenido&submenuop=con_lista&id_materia_periodo_lectivo=".$id_materia_periodo_lectivo."'>Listado de Contenidos</a>");

            if(isset($id_siguiente)){
               $insTpl->assign("ADELANTE", "<a class='letra_10' href='?id_materia_periodo_lectivo=".$id_materia_periodo_lectivo."&menu1op=submenu_contenido&submenuop=con_lista&action=mostrar_contenido&id_contenido=".$id_siguiente."'> adelante &raquo;</a>");
            }
            else{
               $insTpl->assign("ADELANTE", " adelante &raquo;");
            }


            $insTpl->assign("TITULO", $titulo);
            $contenido = nl2br($contenido);

            //$contenido=$this->tags_quitar_atributos($contenido);
            $contenido=$this->autoLink($contenido);

            $insTpl->assign("CONTENIDO", $contenido);

            $insTpl->parse("SALIDA", "tpl_contenido");
            $strContenido=$insTpl->fetch("SALIDA");
         }
         return $strContenido;
      }
   }

   function tags_quitar_atributos($string, $attrib=NULL){
      // <tag atributo=valor atributo="propiedad:valores;"></tag>
      // <tag /> <input type='text' name='torero' />
      // Error: <tag> <br>
      $final=0;
      do
      {
         $len = strlen($string);
         if($final>=$len)
            $final=$len;

         // buscar tag
         do{
            $inicio = strpos($string,'<',$final);
         }while($inicio!==FALSE && $string[$inicio]=='<' && $string[$inicio+1]==' ');

         if($inicio!==FALSE){
            $final = strpos($string,'>',$inicio);

            $substr = substr($string, $inicio, $final-$inicio+1);

            $final_tag=0;
            if($substr!=NULL)
               $final_tag = strpos($substr,' ') + $inicio; // + $inicio;

            // analizar tag
            echo $inicio." - ".$final." - ".$final_tag."<br/>";
            echo "'".htmlentities($substr)."' <br/>";

            if($final_tag<$final && $final_tag!==FALSE){
               $substr = substr($string, $final_tag, $final-$final_tag);
               echo "sub: '".$substr."'<br/>";

               if($string[$final-1]=='/')
                  $string = substr_replace($string,"",$final_tag, $final-$final_tag-1);
               else
                  $string = substr_replace($string,"",$final_tag, $final - $final_tag);


               $final = $inicio+1;
            }
         }
      }
      while($inicio!==FALSE);
      return $string;
   }


///Funcion que busca en un string cualquier ocurrencia de un URL o una direccion mail y las convierte en link
function autoLink($str) {
// link all urls and email

    $str2 = preg_replace("[(?<!<a href=\")(?<!\")(?<!\">)((http|https|ftp)://[\w?=&./-]+)]","<a href=\"\$1\" target='blank'>\$1</a>",$str);
    $str2 = preg_replace("((?<!<a href=\"mailto:)(?<!\">)([\w_-]+@[\w_.-]+[\w]+))","<a href=\"mailto:\$1\">\$1</a>",$str2);
    return $str2;
}




}

?>
