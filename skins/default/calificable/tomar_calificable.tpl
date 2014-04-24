<form {FORM_OPTIONS} name='{FORM_NAME}' action='{ACTION_URL}' method='post'>
{HIDDEN}
<table align='center' style="border:1px solid #909090" cellspacing=0 width='90%'>
<tr><td class="table_nav_bar" align='center' height='40' valign='center' style="border-bottom:1px solid #909090">{NAVEGACION}</td></tr>
<tr><td class="table_nav_bar" style='background:#BBcFF2'>{TIEMPO}</td></tr>
<tr><td class="table_nav_bar" style='background:#BBcFF2'>{GRUPO}</td></tr>
<tr><td class="table_nav_bar" style='font-size:10pt'><b>{PREGUNTA}</b></td></tr>
{RESPUESTAS}
<tr><td align='center' height='40' valign='center'>{NAVEGACION}</td></tr>
<tr><td align='center'>{EXIT}</td></tr>
</table>
</form>
<script language='javascript' type='text/javascript'>
<!--
   var htime = Number(document.{FORM_NAME}.htiempo.value);
   function cada_segundo(){
      var seg= htime%60;
      var min= Math.floor(htime/60)%60;
      var hor= Math.floor(htime/3600);
      document.{FORM_NAME}.tiempo.value =  hor + ":" + (min<10?"0":"") +min + ":" + (seg<10?"0":"") +seg;
      if(htime>0)
         htime--;
      if(htime<=0){
         document.{FORM_NAME}.tomar_calificable.value = 'no';
         document.{FORM_NAME}.submit();
      }else
         setTimeout("cada_segundo()",{MILISEGUNDOS});
   }
   cada_segundo();
-->
</script>
