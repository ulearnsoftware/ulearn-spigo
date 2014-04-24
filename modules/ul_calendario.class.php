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
// | Autores: Iv Ochoa    <iochoa2@telefonica.net>                        |
// +----------------------------------------------------------------------+
//
// $Id: ul_calendario.class.php,v 1.1.1.1 2006/03/03 21:59:09 ainiguez Exp $

if (isset($gsRutaBase)){
   require_once ("$gsRutaBase/conf/default.conf.php");
   require_once ("$gsRutaBase/lib/paloACL.class.php");
   //require_once ("$gsRutaBase/modules/ul_evento.class.php");
   //require_once ("$gsRutaBase/modules/ul_cartelera.class.php");
}
else{
   require_once ("conf/default.conf.php");
   require_once ("lib/paloACL.class.php");
   //require_once ("modules/ul_evento.class.php");
   //require_once ("modules/ul_cartelera.class.php");
}

class ul_calendario
{
   var $oDB;
   var $oPlantillas;
   var $sBaseURL;
   var $id_mpl;
   var $un_dia = 86400; // segundos en un día (24*60*60)

   function ul_calendario(&$oDB, &$oPlantillas, $sBaseURL, $id_materia_periodo_lectivo)
   {
      //$this->un_dia = 86400; // segundos en un día (24*60*60)
      global $config;

      $this->oDB = $oDB;
      $this->oPlantillas=$oPlantillas;
      $this->sBaseURL=$sBaseURL;
      $this->id_mpl=$id_materia_periodo_lectivo;
      setLocale(LC_TIME,$config->locale);
   }

   function visualizar_calendario($mes=NULL,$anio=NULL)
   {
      $calendario=$this->generar_calendario($mes,$anio);

      return $calendario;
   }

   // Obtiene el primer día que ha de aparecer en el
   // calendario, es un número entero (timestamp)
   function obtener_primer_timestamp($mes, $anio)
   {
      global $config;

      if($mes==NULL || $anio==NULL){
         $fecha = getdate();
         if($mes==NULL)
            $mes = $fecha['mon'];
         if($anio==NULL)
            $anio = $fecha['year'];
      }

      $primero = mktime(0,0,0, $mes, 1, $anio);
      $aprimero = getdate($primero);

      return $primero - $this->un_dia*$aprimero['wday'];
   }

   function obtener_numero_semanas($mes, $anio)
   {
      global $config;

      if($mes==NULL || $anio==NULL){
         $fecha = getdate();
         if($mes==NULL)
            $mes = $fecha['mon'];
         if($anio==NULL)
            $anio = $fecha['year'];
      }

      $M_A = array(
               array("zero", 0),
               array("Enero", 31),
               array("Febrero", $anio%4==0? 29: 28),
               array("Marzo", 31),
               array("Abril", 30),
               array("Mayo", 31),
               array("Junio", 30),
               array("Julio", 31),
               array("Agosto", 31),
               array("Septiembre", 30),
               array("Octubre", 31),
               array("Noviembre", 30),
               array("Diciembre", 31));

      $primero = mktime(0,0,0, $mes, 1, $anio);
      $aprimero = getdate($primero);
      //(dias del mes + dias de la semana antes del 1ero del mes) dividido para 7
      return ($M_A[$mes][1] + $aprimero['wday'])/7;
   }

   //timestamp(entero) del primer día y el numero de semanas a mostrar
   function generar_dias($dia_1, $nsem)
   {
      // Genera un arreglo con la fecha como clave para almacenar los eventos del calendario
      $calendar=array();
      $nsem=7*$nsem;
      for($j=0;$j<$nsem;$j++){
         $tst_dia = $dia_1 + $j*$this->un_dia;
         $dia=getdate($tst_dia);
         $calendar[strftime("%Y-%m-%d %T",$tst_dia)] = array('nFecha'=>$dia,'eventos'=>array());
      }
      return $calendar;
   }

   function timestamp($fecha)
   {
      $f1=explode(' ',$fecha);
      $f2=explode('-',$f1[0]);
      $f3=explode(':',$f1[1]);
      return mktime($f3[0],$f3[1],$f3[2],$f2[1],$f2[2],$f2[0]);
   }

   function generar_calendario($mes,$anio)
   {
      // verificacion de la existencia del mes y del año
      if($mes==NULL || $anio==NULL){
         $fecha = getdate();
         if($mes==NULL)
            $mes = $fecha['mon'];
         if($anio==NULL)
            $anio = $fecha['year'];
      }
      if($mes==1){
         $lmes=12;
         $lanio=$anio-1;
         $rmes=2;
         $ranio=$anio;
      }elseif($mes==12){
         $lmes=11;
         $lanio=$anio;
         $rmes=1;
         $ranio=$anio+1;
      }else{
         $lmes=$mes-1;
         $rmes=$mes+1;
         $lanio=$ranio=$anio;
      }

      // Fecha inicial con timestamp entero
      $cal_dia_1 = $this->obtener_primer_timestamp($mes,$anio);
      // numero de semanas del més
      $cal_sem = ceil($this->obtener_numero_semanas($mes,$anio));
      // Generacion de Arreglo con eventos fechados
      $fechas = $this->generar_dias($cal_dia_1, $cal_sem);

      // Fecha Inicial
      reset($fechas);
      $first=each($fechas);
      // Fecha Final
      end($fechas);
      $last=each($fechas);

      // Agregar eventos de la Cartelera al arreglo de eventos fechados
      $sQuery = "SELECT id_cartelera, inicio, final, titulo, contenido FROM ul_cartelera WHERE inicio<'".$last['key']."' AND final>'".$first['key']."'";
      $result = $this->oDB->fetchTable($sQuery,TRUE);
      if(is_array($result) and count($result)>0){
         foreach ($result as $key => $value){
            $vtime=$this->timestamp($value['inicio']);
            foreach($fechas as $i=>$valor){
               $vi=$this->timestamp($i);
               if($vtime>=$vi && $vtime<($vi+$this->un_dia)){
                  $evento = substr($value['titulo'],0,16);
                  if(strlen($value['titulo'])>16)
                     $evento.='...';
                  if($value['contenido']!=NULL)
                     $fechas[$i]['eventos'][]="<a href='?menu1op=submenu_agenda&submenuop=ag_calendario&action=mostrar_cartelera&id_materia_periodo_lectivo=".$this->id_mpl."&id_cartelera=".$value['id_cartelera']."' title='".$value['titulo']."'>".$evento."</a>";
                  else
                     $fechas[$i]['eventos'][]="<label title='".$value['titulo']."'>".$evento."</label>";
               }
            /*
               if($i>=$value['inicio'] && $i<=$value['final']){
                  $evento = substr($value['titulo'],0,16);
                  if(strlen($value['titulo'])>16)
                     $evento.='...';
                  if($value['contenido']!=NULL)
                     $fechas[$i]['eventos'][]="<a href='?menu1op=submenu_agenda&submenuop=ag_calendario&action=mostrar_cartelera&id_materia_periodo_lectivo=".$this->id_mpl."&id_cartelera=".$value['id_cartelera']."' title='".$value['titulo']."'>".$evento."</a>";
                  else
                     $fechas[$i]['eventos'][]="<label title='".$value['titulo']."'>".$evento."</label>";
               }
            */
            }
         }
      }

      // Agregar eventos de la Lista de Eventos al arreglo de eventos fechados
      // depende de id_materia_periodo_lectivo
      $url_mpl="";
      if($this->id_mpl){
         $url_mpl="&id_materia_periodo_lectivo=".$this->id_mpl;
         $sQuery = "SELECT id_evento, inicio, final, titulo, contenido FROM ul_evento WHERE id_materia_periodo_lectivo=".$this->id_mpl." AND inicio<'".$last['key']."' AND final>'".$first['key']."'";
         $result = $this->oDB->fetchTable($sQuery,TRUE);
         if(is_array($result) and count($result)>0){
            foreach ($result as $key => $value){
               $vtime=$this->timestamp($value['inicio']);
               foreach($fechas as $i=>$valor){
                  $vi=$this->timestamp($i);
                  if($vtime>=$vi && $vtime<($vi+$this->un_dia)){
                     $evento = substr($value['titulo'],0,16);
                     if(strlen($value['titulo'])>16)
                        $evento.='...';
                     if($value['contenido']!=NULL)
                        $fechas[$i]['eventos'][]="<a href='?menu1op=submenu_agenda&submenuop=ag_calendario&action=mostrar_evento&id_materia_periodo_lectivo=".$this->id_mpl."&id_evento=".$value['id_evento']."' title='".$value['titulo']."'>".$evento."</a>";
                     else
                        $fechas[$i]['eventos'][]="<label title='".$value['titulo']."'>".$evento."</label>";
                  }
               /*
                  if($i>=$value['inicio'] && $i<=$value['final']){
                     $evento = substr($value['titulo'],0,16);
                     if(strlen($value['titulo'])>16)
                        $evento.='...';
                     if($value['contenido']!=NULL)
                        $fechas[$i]['eventos'][]="<a href='?menu1op=submenu_agenda&submenuop=ag_calendario&action=mostrar_evento&id_materia_periodo_lectivo=".$this->id_mpl."&id_evento=".$value['id_evento']."' title='".$value['titulo']."'>".$evento."</a>";
                     else
                        $fechas[$i]['eventos'][]="<label title='".$value['titulo']."'>".$evento."</label>";
                  }
               */
               }
            }
         }
      }


      // Creacion de la Tabla con el calendario
      $calendar ="<table width='98%' border=1px cellspacing='0'>";
      // Titulo: Desplazamiento y Fecha del Calendario Actual
      $calendar.="<tr>";
      $calendar.="<th class='table_title_row' align='CENTER' colspan='2'>";
      // mes anterior
      $calendar.="<a class='letra_10' href='".$this->sBaseURL.$url_mpl."&mes=$lmes&anio=$lanio'>&lt; ".utf8_encode(strftime("%B %Y",mktime(0,0,0,$lmes,1,$lanio)))."</a>";
      $calendar.="</th>";
      $calendar.="<th class='table_title_row' align='CENTER' colspan='3' style='background-color: #DFDFDF'>";
      // mes actual
      $calendar.=utf8_encode(strftime("%B %Y",mktime(0,0,0,$mes,1,$anio)));
      $calendar.="</th>";
      $calendar.="<th class='table_title_row' align='CENTER' colspan='2'>";
      // mes siguiente
      $calendar.="<a class='letra_10' href='".$this->sBaseURL.$url_mpl."&mes=$rmes&anio=$ranio'>".utf8_encode(strftime("%B %Y",mktime(0,0,0,$rmes,1,$ranio)))." &gt;</a>";
      $calendar.="</th>";
      $calendar.="</tr>";
      // Dias de la Semana
      $calendar.="<tr>";
      for($i=0;$i<7;$i++)
         $calendar.="<td class='table_title_row' width='14%' align='CENTER'>".utf8_encode(strftime("%A",$cal_dia_1+$this->un_dia*$i))."</td>";
      $calendar.="</tr>";

      // Dias del Mes y Eventos
      for($j=0; $j<$cal_sem; $j++){
         // semana
         $calendar.="<tr>";
         for($i=0;$i<7;$i++){
            $tst_dia=$cal_dia_1+($i+$j*7)*$this->un_dia;
            $dia=getdate($tst_dia);

            $mes_semana = strcmp($fechas[strftime("%Y-%m-%d %T",$tst_dia)]['nFecha']['mon'],$mes);
            $mclass="";
            if($mes_semana!=0)
               $mclass="class='mes_diferente'";

            // dia de la semana
            $calendar.="<td align='LEFT' $mclass>";
               // formato para el dia y los eventos del día
               $calendar.="<table width='100%' cellspacing='0'>";
               $calendar.="<tr>";

               // dia
               $calendar.="<td align='LEFT' $mclass>";
               $calendar.=$dia['mday'];
               $calendar.="</td>";

               // algún detalle adicional
               $calendar.="<td align='RIGHT' $mclass>";
               $calendar.='&nbsp;';
               $calendar.="</td>";
               $calendar.="</tr>";

               // evento
               $calendar.="<tr height=50>";
               $calendar.="<td align='LEFT' colspan='2' $mclass>";
               $calendar.=implode("<br />",$fechas[strftime("%Y-%m-%d %T",$tst_dia)]['eventos']);
               $calendar.="</td>";
               $calendar.="</tr>";
               $calendar.="</table>";
            $calendar.="</td>";
         }
         $calendar.="</tr>";
      }
      $calendar.="</table>";
      $strCalendario = $calendar;

      return $strCalendario;
   }
}


?>
