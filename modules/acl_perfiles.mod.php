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

require_once ("conf/default.conf.php");
require_once ("lib/paloTemplate.class.php");
require_once ("lib/paloACL.class.php");

$tpl->assign("CONTENT", _moduleContent($pDB, $_GET, $_POST));

function _moduleContent(&$pDB, &$_GET, &$_POST)
{
    global $config; // definda en conf/default.conf.php

    $arrValores=recoger_valor("arrValores",$_GET,$_POST,array());
    $id_grupo=recoger_valor("id_grupo",$_GET,$_POST);

    $oPlantillas =& new paloTemplate("skins/".$config->skin);
    $oPlantillas->definirDirectorioPlantillas("");
    $oPlantillas->assign("IMG_PATH", "skins/$config->skin/images");


    if(isset($_POST['guardar'])){
       $bool=guardar_perfil($pDB,$id_grupo,$arrValores);
    }



    $sCodigoTabla="";
    $sCodigoTabla.=tabla_grid($pDB,$oPlantillas,$id_grupo);

    return $sCodigoTabla;

}


function tabla_grid(&$pDB,$tpl,$id_grupo){

$oACL=getACL();
$arrAcciones=$oACL->getActions();

/////Obtener los grupos
$arr_grupos=$oACL->getGroups();
$lista_grupos=array();

   if(is_array($arr_grupos) && count($arr_grupos)>0){
       foreach($arr_grupos as $row){
          $lista_grupos[$row[0]]=$row[1];
       }
   }

$combo_grupos="<select name=id_grupo onChange='submit();'><option value=''>--Seleccione un grupo--</option>".
                combo($lista_grupos,$id_grupo)."</select>";

$sCodigoTabla=script();

////Se asigna a la plantilla las cabeceras de la tabla

$tpl->assign("HEADER_TEXT", "<input type=\"checkbox\" onClick=\"asignarTodos(this.checked)\" onChange=\"asignarTodos(this.checked)\">&nbsp;<b>Recursos</b>");
$tpl->parse("HEADER_TDs","tpl__table_header_cell");


   if(is_array($arrAcciones) && count($arrAcciones)>0){
      foreach($arrAcciones as $col){

         ///Se asignan las siguientes columnas de las acciones
         $tpl->assign("HEADER_TEXT", "<input type='checkbox' name='col[".$col[0]."]' onClick='asignarFila(".$col[0].",this.checked)' onChange='asignarFila(".$col[0].", this.checked)'><b>".$col[1]."</b>");
         $tpl->parse("HEADER_TDs", ".tpl__table_header_cell");
      }
   }
   else{
      ///Crear plantilla con error
      return "error";
   }




$cabecera="<table>".
            "<tr><td>Grupo</td><td>$combo_grupos</td><td><input type='submit' name='guardar' value='Guardar'></td></tr>".
            "</table>";


/////////Se carga el arrValores en base a los recursos y acciones relacionadas al grupo
$sQuery="select id_action,id_resource from acl_group_permission where id_group=$id_grupo";
$result=$pDB->fetchTable($sQuery,true);
$arrValores=array();

   if(is_array($result) && count($result)>0){
      foreach($result as $fila){
           $arrValores[$fila['id_resource']][$fila['id_action']]=1;
      }
   }

////////////Ahora se tienen que hacer las filas por cada resource
$arrResources=$oACL->getResources();


$str="";

$background='#CCCCFF';
$selcolor='#3366CC';

     foreach($arrResources as $fila){
         $tpl->assign("DATA", "<input name='fila[".$fila[0]."]' type='checkbox' onClick='asignarColumna(".$fila[0].", this.checked)' onChange='asignarColumna(".$fila[0].", this.checked)'>&nbsp;<b>$fila[1]</b>");
         $tpl->parse("TDs_DATA","tpl__table_data_cell");

            for($i=0;$i<count($arrAcciones);$i++){
               $x=$fila[0];
               $y=$arrAcciones[$i][0];
               $checked="";
                  if(array_key_exists($x,$arrValores)){
                     if(array_key_exists($y,$arrValores[$x])){
                        if($arrValores[$x][$y]){
                              $checked="checked";
                        }
                     }
                  }
                  if($checked!="")
                     $bgcolor=$selcolor;
                  else
                     $bgcolor=$background;

               $tpl->assign("DATA", "<div style='background: $bgcolor'><input type='checkbox' name='arrValores[".$fila[0]."][".$arrAcciones[$i][0]."]' $checked></div>");
               $tpl->parse("TDs_DATA",".tpl__table_data_cell");

            }

        $tpl->parse("DATA_ROWs",".tpl__table_data_row");

      }


 // Parsing final de la tabla

$tpl->assign("TITLE",$cabecera);
$tpl->assign("COLSPAN",count($arrAcciones)+1);
$tpl->parse("TITLE_ROW","tpl__table_title_row");
$tpl->assign("TBL_WIDTH","50%");
$tpl->parse("HEADER_ROW","tpl__table_header_row");
$tpl->parse("TABLA", "tpl__table_container");

$tabla = $tpl->fetch("TABLA");
$sCodigoTabla.="<form method='post' name='main'>".$tabla."</form>";
return $sCodigoTabla;
}


function guardar_perfil($oDB,$id_grupo,$arrValores){

$oACL=getACL();

   if(is_array($arrValores)){

      $sQuery="delete from acl_group_permission where id_group=$id_grupo";
      $bValido=$oDB->genQuery($sQuery);

         if(!$bValido)
            return;


         foreach($arrValores as $id_res=>$resource){
            $id_resource=$id_res;
               foreach($resource as $id_act=>$action){
                  $id_action=$id_act;
                     if($id_resource>0 && $id_action>0){
                              $oACL->addGroupPermission($id_grupo, $id_action, $id_resource);
                     }
               }

         }
   }
///TODO: revisar manera de no borrar todas los registros de group permision para el id_grupo
///Evaluar si es necesario el no borrar todos los registros

/*
$oACL=getACL();

//Se deben obtener los ids del acl_group_permision de los registros que no tengan los ids de arr_grupo

$lista_recursos="(";
$lista_acciones="(";
$cont=0;
   foreach($arrValores as $id_recurso=>$arr_acciones){
         if($id_recurso>0)
            $lista_recursos.=$id_recurso;

         if($cont<(count($arrValores)-1))
            $lista_recursos.=",";

      $aux=0;
         foreach($arr_acciones as $id_action=>$action){
            if($id_action>0)
               $lista_acciones.=$id_action;
            if($aux<(count($arr_acciones)-1))
               $lista_acciones.=",";
            $aux++;

         }
      $cont++;


   }


   for($i=0;$i<count($arrValores);$i++){
      for($j=0;$j<count($arrValores[$i]);$j++){
         $str_lista.=$arr_grupos[$i];
            if($i<(count($arr_grupos)-1))
               $str_lista.=",";
      }
   }
$str_lista.=")";

/*
if(is_array($arrValores)){

$sQuery="delete from acl_group_permission where id_group=$id_grupo";
$bValido=$oDB->genQuery($sQuery);

   if(!$bValido)
      return;


   foreach($arrValores as $id_res=>$resource){
       $id_resource=$id_res;
        foreach($resource as $id_act=>$action){
            $id_action=$id_act;
	       if($id_resource>0 && $id_action>0){
                  $oACL->addGroupPermission($id_grupo, $id_action, $id_resource);
	       }
	}



   }*/
}

function script(){


return "<script language=\"javascript\">
<!-- hide

    /* Procedimiento que asigna a todos los valores del formulario */
    function asignarTodos(v)
    {
        for (n = 0; n < document.main.elements.length; n++) {
          var oElemento = document.main.elements[n];
            if (oElemento.name.substring(0, 10) == \"arrValores\") {
                oElemento.checked = v;
            }
        }
    }

    /* Procedimiento que asigna a toda una columna numerada */
    function asignarColumna(c, v)
    {
        sPrefijoInput = \"arrValores[\" + c + \"]\";
        for (n = 0; n < document.main.elements.length; n++) {
            var oElemento = document.main.elements[n];
            if (oElemento.name.substring(0, sPrefijoInput.length) == sPrefijoInput) {
                oElemento.checked = v;
            }
        }
    }

    /* Procedimiento que asigna a toda una fila numerada */
    function asignarFila(f, v)
    {
        sSufijoInput = \"[\" + f + \"]\";
        for (n = 0; n < document.main.elements.length; n++) {
            var oElemento = document.main.elements[n];
            if (oElemento.name.substring(0, 10) == \"arrValores\" && oElemento.name.substring(oElemento.name.length - sSufijoInput.length, oElemento.name.length) == sSufijoInput) {
                oElemento.checked = v;
            }
        }
    }

  // -->
</script>";



}



?>
