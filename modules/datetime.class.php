<?php

if (isset($gsRutaBase)){
   require_once ("$gsRutaBase/conf/default.conf.php");
   require_once ("$gsRutaBase/lib/paloTemplate.class.php");
}
else{
   require_once ("conf/default.conf.php");
   require_once ("lib/paloTemplate.class.php");
}

   // t_formato=0 => year/month/day hour:min:sec
   // t_formato=1 => year/month/day hour:min
   function get_datetime($namevar, $t_formato=0, $reutilizar=NULL)
   {
      global $config;

      $datetime="";
      $fecha="";
      $insTpl =& new paloTemplate("skins/".$config->skin);
      $insTpl->definirDirectorioPlantillas("datetime");
      $insTpl->assign("IMG_PATH", "skins/$config->skin/images");

      switch($t_formato){
      case 1:
         $string="d / m / y &nbsp; hor : min";
         break;
      default:
         $string="d / m / y &nbsp; hor : min : sec";
      }

      $time=getdate();
      $time['minutes']=30;
      $time['seconds']=30;

      $parse_fecha = explode(' ',$string);

      $anio=$mes=$dia=$horas=$minutos=$segundos='&nbsp;';

      if($reutilizar!=NULL){
         $time['hours'] =  recoger_valor($namevar.'_hora',$_GET,$_POST,$time['hours']);
         $time['minutes']  =  recoger_valor($namevar.'_minuto',$_GET,$_POST,30);
         $time['seconds']  =  recoger_valor($namevar.'_segundo',$_GET,$_POST,30);
         $time['mon']  =  recoger_valor($namevar.'_mes',$_GET,$_POST,$time['mon']);
         $time['mday']  =  recoger_valor($namevar.'_dia',$_GET,$_POST,$time['mday']);
         $time['year'] =  recoger_valor($namevar.'_anio',$_GET,$_POST,$time['year']);
      }

      foreach($parse_fecha as $i=>$value){
         switch($value){
         case "y":
            $actual=$time['year'];
            $desde=$actual-2;
            $hasta=$actual+5;

            $anio ="\n<select name='".$namevar."_anio'>";
            for($i=$desde; $i<=$hasta; $i++)
               $anio.="\n<option value=$i".($actual==$i?" selected='selected'":"").">$i</option>";
            $anio.="\n</select>";
            $fecha.=$anio;
            break;

         case "m":
            $mes ="\n<select name='".$namevar."_mes'>";
            $mes.="\n<option value=1".($time['mon']==1?" selected='selected'":"").">Enero</option>";
            $mes.="\n<option value=2".($time['mon']==2?" selected='selected'":"").">Febrero</option>";
            $mes.="\n<option value=3".($time['mon']==3?" selected='selected'":"").">Marzo</option>";
            $mes.="\n<option value=4".($time['mon']==4?" selected='selected'":"").">Abril</option>";
            $mes.="\n<option value=5".($time['mon']==5?" selected='selected'":"").">Mayo</option>";
            $mes.="\n<option value=6".($time['mon']==6?" selected='selected'":"").">Junio</option>";
            $mes.="\n<option value=7".($time['mon']==7?" selected='selected'":"").">Julio</option>";
            $mes.="\n<option value=8".($time['mon']==8?" selected='selected'":"").">Agosto</option>";
            $mes.="\n<option value=9".($time['mon']==9?" selected='selected'":"").">Septiembre</option>";
            $mes.="\n<option value=10".($time['mon']==10?" selected='selected'":"").">Octubre</option>";
            $mes.="\n<option value=11".($time['mon']==11?" selected='selected'":"").">Noviembre</option>";
            $mes.="\n<option value=12".($time['mon']==12?" selected='selected'":"").">Diciembre</option>";
            $mes.="\n</select>";
            $fecha.=$mes;
            break;

         case "d":
            $dia ="\n<select name='".$namevar."_dia'>";
            for($i=1; $i<=31; $i++)
               $dia.="\n<option value='$i'".($time['mday']==$i?" selected='selected'":"").">$i</option>";
            $dia.="\n</select>";
            $fecha.=$dia;
            break;

         case "hor":
            $horas ="\n<select name='".$namevar."_hora'>";
            for($i=0; $i<=23; $i++){
               $horas.="\n<option value='$i'".($time['hours']==$i?" selected='selected'":"").">".($i<10?"0$i":"$i")."</option>";
            }
            $horas.="\n</select>";
            $fecha.=$horas;
            break;

         case "min":
            $minutos ="\n<select name='".$namevar."_minuto'>";
            for($i=0; $i<=59; $i+=5)
               $minutos.="\n<option value='$i'".($i==$time['minutes']?" selected='selected'":"").">".($i<10?"0$i":"$i")."</option>";
            $minutos.="\n</select>";
            $fecha.=$minutos;
            break;

         case "sec":
            $segundos ="\n<select name='".$namevar."_segundo'>";
            for($i=0; $i<=59; $i+=15)
               $segundos.="\n<option value='$i'".($i==30?" selected='selected'":"").">".($i<10?"0$i":"$i")."</option>";
            $segundos.="\n</select>";
            $fecha.=$segundos;
            break;
         default:
            $fecha.=$value;
         }
         $fecha.='&nbsp;';
      }

      $insTpl->assign("FECHA", $fecha);
      $insTpl->parse("SALIDA", "tpl_datetime");
      $datetime.=$insTpl->fetch("SALIDA");
      return $datetime;
   }

   function checkdatetime($name){
      $mes  =  recoger_valor($name.'_mes',$_GET,$_POST,0);
      $dia  =  recoger_valor($name.'_dia',$_GET,$_POST,0);
      $anio =  recoger_valor($name.'_anio',$_GET,$_POST,0);

      return checkdate($mes,$dia,$anio);
   }

   function mk_datetime($name){
      $hora =  recoger_valor($name.'_hora',$_GET,$_POST,0);
      $min  =  recoger_valor($name.'_minuto',$_GET,$_POST,0);
      $seg  =  recoger_valor($name.'_segundo',$_GET,$_POST,0);
      $mes  =  recoger_valor($name.'_mes',$_GET,$_POST,0);
      $dia  =  recoger_valor($name.'_dia',$_GET,$_POST,0);
      $anio =  recoger_valor($name.'_anio',$_GET,$_POST,0);
      return $anio."-".$mes."-".$dia." ".$hora.":".$min.":".$seg;
   }

   function conv_datetime($strdate){
      $fecha_hora=explode(' ',$strdate);
      if(count($fecha_hora)==2){
         $fecha = explode('-' , $fecha_hora[0]);
         $hora = explode(':' , $fecha_hora[1]);
         return mktime($hora[0], $hora[1], $hora[2], $fecha[1], $fecha[2], $fecha[0]);
      }else
         return $strdate;

   }

?>
