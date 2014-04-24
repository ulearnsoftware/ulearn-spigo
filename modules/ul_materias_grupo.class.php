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
// $Id: ul_materias_grupo.class.php,v 1.1.1.1 2006/03/03 21:59:08 ainiguez Exp $

require_once ("lib/paloReporte.class.php");


class ul_materias_grupo_reporte extends PaloReporte
{  var $id_mpl;

   function ul_materias_grupo_reporte(&$oDB, &$oPlantillas, $sBaseURL)
   {
      $this->PaloReporte($oDB, $oPlantillas);
      $oACL=getACL();
      $tabla_cabecera_opciones=$this->generar_cabecera_opciones();

      $sClauseWhere="";
      //////////// Verificacion de perfil de usuario para mostrar columnas con input

      $id_grupo=recoger_valor("id_grupo",$_GET,$_POST);
      $id_periodo_lectivo=recoger_valor("id_periodo_lectivo",$_GET,$_POST);
      $id_materia_periodo_lectivo=recoger_valor("id_materia_periodo_lectivo",$_GET,$_POST);

         if(is_null($id_grupo) || $id_grupo=="")
            $id_grupo=0;

         if(!is_null($id_periodo_lectivo) && $id_periodo_lectivo>0){
            $sClauseWhere="and mpl.id_periodo_lectivo=$id_periodo_lectivo";
         }

      if (!$this->definirReporte("LISTA_MATERIAS_GRUPO", array(
         //"DEBUG"=>true,
         "TITLE"         =>  "Listado de Materias Asociadas a Grupo<br>\n",
         "FILTRO"        =>  $tabla_cabecera_opciones,
         "PAGECHOICE"    =>  array(500,1000),
         "DATA_COLS"     =>  array(
                                 "ID"=>"mg.id",
                                 "ID_GRUPO"=>"mg.id_group",
                                 "GRUPO"=>"g.name",-
                                 "ID_MATERIA_PERIODO_LECTIVO"=>"mg.id_materia_periodo_lectivo",
                                 "MATERIA"=>"m.nombre",
                                 "PARALELO"=>"mpl.paralelo",
                                 "ID_PERIODO_LECTIVO"=>"mpl.id_periodo_lectivo",
                                 "PERIODO"=>"pl.nombre",
                             ),
         "PRIMARY_KEY"   =>  array("ID_GRUPO","MATERIA","PARALELO"),
         "FROM"          =>  "ul_materias_grupo mg, acl_group g, ul_materia_periodo_lectivo mpl, ul_periodo_lectivo pl, ul_materia m",
         "CONST_WHERE"   =>  "mg.id_group=g.id and mg.id_materia_periodo_lectivo=mpl.id and mpl.id_materia=m.id ".
                             "and mpl.id_periodo_lectivo=pl.id and mg.id_group=$id_grupo $sClauseWhere ",
         "PAGECHOICE"    =>  array(1000),
         "ORDERING"      =>  array(
                                 "DEFAULT"   =>  array("GRUPO","PERIODO","MATERIA","PARALELO"),
                             ),
         "BASE_URL"      =>  $sBaseURL,
         "HEADERS"       =>  array(
                                 "<input type='submit' name='eliminar' value='Eliminar' onClick=\"return confirm('Está seguro que desea eliminar la relación con la materia?')\">",
                                 "GRUPO",
                                 "PERIODO",
                                 "MATERIA",
                              ),
         "ROW"           =>  array(
                                 "{_DATA_INPUT}",
                                 "{_DATA_GRUPO}",
                                 "{_DATA_PERIODO}",
                                 "{_DATA_MATERIA} P{_DATA_PARALELO}",

                              ),
      ))) die ("ul_materias_grupo: - al definir reporte LISTA_MATERIAS_GRUPO - ".$this->_msMensajeError);
   }


    /**
     * Procedimiento que muestra condicionalmente el enlace a la modificacin de la cuenta.
     * como una fila adicional de datos.
     *
     * @param string $sNombreReporte Nombre del reporte para el que se proveen las columnas
     * @param array  $tuplaSQL       Tupla con los valores a usar para la fila actual
     *
     * @return array    Valores a agregar a la tupla existente de SQL
     */
   function event_proveerCampos($sNombreReporte, $tuplaSQL){
      global $config;
      $oACL=getACL();

      switch ($sNombreReporte) {
      case "LISTA_MATERIAS_GRUPO":

         if ($oACL->isUserAuthorized($_SESSION['session_user'], 'delet', 'acl_materias_grupo'))
            $link_input="<input type='radio' name='id' value='".$tuplaSQL['ID']."'>";
         else
            $link_input="";


      default:
         return array(
                     "INPUT" => $link_input,
                      );
      }
   }



   function agregar_materia(){
      global $config;
      $oDB=$this->_db;

      $id_grupo=recoger_valor("id_grupo",$_GET,$_POST);
      $id_periodo_lectivo=recoger_valor("id_periodo_lectivo",$_GET,$_POST);
      $id_materia=recoger_valor("id_materia",$_GET,$_POST);
      $paralelo=recoger_valor("paralelo",$_GET,$_POST);

         if(!ereg("^[[:digit:]]+$",$id_grupo)){
            $this->setMessage("No se recibio un id_grupo válido");
            return FALSE;
         }
         if(!ereg("^[[:digit:]]+$",$id_periodo_lectivo)){
            $this->setMessage("No se recibio un periodo lectivo válido");
            return FALSE;
         }
         if(!ereg("^[[:digit:]]+$",$id_materia)){
            $this->setMessage("No se recibio una materia válida");
            return FALSE;
         }
         if(!ereg("^[[:digit:]]+$",$paralelo)){
            $this->setMessage("No se recibio un paralelo válido");
            return FALSE;
         }

         if($paralelo==0){
            ///Se deben buscar los ids de materias y no volver a ingresarlos
             /////Se deben buscar los paralelos de las materias que no esten ya incluidas
            $sPeticionSQL="SELECT mg.id_materia_periodo_lectivo FROM ul_materias_grupo mg, ul_materia_periodo_lectivo mpl ".
                           "WHERE mg.id_group=$id_grupo and mg.id_materia_periodo_lectivo=mpl.id and mpl.id_periodo_lectivo=$id_periodo_lectivo ".
                           "and mpl.id_materia=$id_materia";
            $recordset=$oDB->fetchTable($sPeticionSQL);
            $lista_mpl=$sClauseWhere="";
               if(is_array($recordset) && count($recordset)>0){
                  foreach($recordset as $fila){
                        if(strlen($lista_mpl)>0)
                           $lista_mpl.=",";
                       $lista_mpl.="'".$fila[0]."'";
                  }
               }
               if(strlen($lista_mpl)>0)
                  $sClauseWhere="and mpl.id not in ($lista_mpl)";
               else
                  $sClauseWhere="";
         }
         else
            $sClauseWhere="and paralelo=$paralelo";

         $sQuery = "INSERT INTO ul_materias_grupo (id_group,id_materia_periodo_lectivo)  ".
                  "(SELECT '$id_grupo' as grupo,mpl.id FROM ul_materia_periodo_lectivo mpl ".
                  "WHERE mpl.id_periodo_lectivo=$id_periodo_lectivo and mpl.id_materia=$id_materia $sClauseWhere)";

      $bValido=$oDB->genQuery($sQuery);

         if(!$bValido){
            $this->setMessage("Error ingresar Materia. Error: ".$oDB->errMsg);
            return FALSE;
         }
         else
            return TRUE;


   }


   function eliminar_materia($id_materia_grupo){
      global $config;
      $oACL=getACL();
      $oDB=$this->_db;

      if (!$oACL->isUserAuthorized($_SESSION['session_user'], 'delet', 'acl_materias_grupo')){
         $this->setMessage("Usted no está autorizado para realizar esta acción.");
         return FALSE;
      }

      // eliminar de la base de datos
      $sQuery="DELETE from ul_materias_grupo where id=$id_materia_grupo";
      $bValido=$oDB->genQuery($sQuery);
         if(!$bValido){
            $this->setMessage("Error al eliminar registro. Error: ".$oDB->errMsg);
            return FALSE;
         }
         else
            return TRUE;

   }




   function generar_cabecera_opciones(){
      //Se debe mostrar los botones para modificar, eliminar e ingresar dependiendo de los privilegios del usuario
      $oACL=getACL();
      global $config;
      $db=$this->getDB();

      $id_grupo=recoger_valor("id_grupo",$_GET,$_POST);
      $id_periodo_lectivo=recoger_valor("id_periodo_lectivo",$_GET,$_POST);
      $id_materia=recoger_valor("id_materia",$_GET,$_POST);
      $paralelo=recoger_valor("paralelo",$_GET,$_POST);

      $sContenido="";

      ///Se genera el combo de grupos
      $arr_grupos=$oACL->getGroups();
      $lista_grupos=array();
         if(is_array($arr_grupos) && count($arr_grupos)>0){
            foreach($arr_grupos as $row){
               $lista_grupos[$row[0]]=$row[1];
            }
         }
      $combo_grupos="<select name='id_grupo' onChange='submit();'><option value=''>--Seleccione un grupo--</option>".
                     combo($lista_grupos,$id_grupo)."</select>";

      ///////Se genera el combo de periodos////////////////////////////////////
      $sQuery="SELECT id, nombre FROM ul_periodo_lectivo ORDER BY fecha_inicio DESC";
      $result=$db->fetchTable($sQuery,true);
      $lista_periodos=array();
         if(is_array($result) && count($result)>0){
            foreach($result as $fila)
               $lista_periodos[$fila['id']]=$fila['nombre'];
         }
      $combo_periodos="<select name='id_periodo_lectivo' onChange='submit();'><option value=''>--Seleccione un Período Lectivo--</option>".
                     combo($lista_periodos,$id_periodo_lectivo)."</select>";






      //////Se genera el combo con las materias en el periodo lectivo
      $lista_materias=array();
         if($id_periodo_lectivo>0){

            ////////Se deben evitar mostrar las materias que ya estan asignadas
            /////Se deben buscar los paralelos de las materias que no esten ya incluidas
            $sPeticionSQL="SELECT mg.id_materia_periodo_lectivo FROM ul_materias_grupo mg, ul_materia_periodo_lectivo mpl ".
                           "WHERE mg.id_group=$id_grupo and mpl.id_periodo_lectivo=$id_periodo_lectivo";
            $recordset=$db->fetchTable($sPeticionSQL);
            $lista_mpl=$sClauseWhere="";
               if(is_array($recordset) && count($recordset)>0){
                  foreach($recordset as $fila){
                        if(strlen($lista_mpl)>0)
                           $lista_mpl.=",";
                       $lista_mpl.="'".$fila[0]."'";
                  }
               }
               if(strlen($lista_mpl)>0)
                  $sClauseWhere="and mpl.id not in ($lista_mpl)";


            $sQuery="SELECT distinct m.id, m.nombre FROM ul_materia m, ul_materia_periodo_lectivo mpl ".
                     "WHERE mpl.id_materia=m.id and mpl.id_periodo_lectivo=$id_periodo_lectivo $sClauseWhere ORDER BY m.nombre";
            $result=$db->fetchTable($sQuery,true);

               if(is_array($result) && count($result)>0){
                  foreach($result as $fila)
                     $lista_materias[$fila['id']]=$fila['nombre'];
               }
         }
      $combo_materias="<select name='id_materia' onChange='submit();'><option value=''>--Seleccione una Materia--</option>".
                        combo($lista_materias,$id_materia)."</select>";








      //////Se genera el combo con los paralelos de la materia en el periodo lectivo
      $lista_paralelos=array();

         if($id_materia>0 && $id_periodo_lectivo>0){
            /////Se deben buscar los paralelos de las materias que no esten ya incluidas
            $sPeticionSQL="SELECT mg.id_materia_periodo_lectivo FROM ul_materias_grupo mg, ul_materia_periodo_lectivo mpl ".
                           "WHERE mg.id_group=$id_grupo and mg.id_materia_periodo_lectivo=mpl.id and mpl.id_materia=$id_materia ".
                           "and mpl.id_periodo_lectivo=$id_periodo_lectivo";
            $recordset=$db->fetchTable($sPeticionSQL);
            $lista_mpl=$sClauseWhere="";
               if(is_array($recordset) && count($recordset)>0){
                  foreach($recordset as $fila){
                        if(strlen($lista_mpl)>0)
                           $lista_mpl.=",";
                       $lista_mpl.="'".$fila[0]."'";
                  }
               }
               if(strlen($lista_mpl)>0)
                  $sClauseWhere="and mpl.id not in ($lista_mpl)";

            $sQuery="SELECT distinct mpl.paralelo FROM ul_materia_periodo_lectivo mpl ".
                     "WHERE mpl.id_materia=$id_materia and mpl.id_periodo_lectivo=$id_periodo_lectivo $sClauseWhere ORDER BY mpl.paralelo";
            $result=$db->fetchTable($sQuery,true);

               if(is_array($result) && count($result)>0){
                     foreach($result as $fila)
                        $lista_paralelos[$fila['paralelo']]="P".$fila['paralelo'];
                  $lista_paralelos[0]="--- Todos ---";
               }
         }
      $combo_paralelos="<select name='paralelo' onChange='submit();'><option value=''>--Seleccione un Paralelo--</option>".
                        combo($lista_paralelos,$paralelo)."</select>";


      $boton_submit="<input type=submit name=agregar value='Agregar'>";


      $sContenido = "<table><tr><td class='table_nav_bar'>GRUPO:</td><td class='table_nav_bar'>$combo_grupos</td>".
                               "<td class='table_nav_bar'>PERIODO:</td><td class='table_nav_bar'>$combo_periodos</td></tr>".
                           "<tr><td class='table_nav_bar'>MATERIAS:</td><td class='table_nav_bar'>$combo_materias</td>".
                               "<td class='table_nav_bar'>PARALELO:</td><td class='table_nav_bar'>$combo_paralelos&nbsp;&nbsp;$boton_submit</td></tr>".
                     "</tr></table>";


      return $sContenido;
   }



}

?>
