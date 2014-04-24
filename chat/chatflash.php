<?php
session_start();
header("Content-Type: text/html; charset=UTF-8");

$gsRutaBase="..";

require_once("language/spanish/main.php");
require_once("../lib/misc.lib.php");
require_once("../lib/paloDB.class.php");
require_once("../lib/paloACL.class.php");
require_once("../conf/default.conf.php");

global $config;

$username=recoger_valor("username",$_GET,$_POST);
//$login=generar_login_chat($username);
$login=$username;
$id_materia_periodo_lectivo=recoger_valor("id_materia_periodo_lectivo",$_GET,$_POST);
$text_string=recoger_valor("text_string",$_GET,$_POST);


require ('config.php');

?>
<html>
<script language="JavaScript">


<?php 


if(eregi("win", $HTTP_USER_AGENT) and eregi("MSIE", $HTTP_USER_AGENT)){
$browser= "explorerwin";
}
else{
$browser= "";
}
?>


function errorsuppressor(){
return true;
}
window.onerror=errorsuppressor;


function MM_openBrWindow(theURL,winName,features) {
 window.open(theURL,winName,features);
}

var deleted=0;
var esapersona = '<?php echo $login;?>';


function addperson(unapersona){
var esapersona = unapersona;
}

function deleteuser(lapersona){
var baseURL="<?=$_SERVER['SERVER_NAME']."".dirname($_SERVER['PHP_SELF'])?>";
var deleted=1;
var laurl='http://'+baseURL+'/users.php?login='+lapersona+'&id_materia_periodo_lectivo=<?php echo $id_materia_periodo_lectivo;?>&bye=bye';
document.location.replace(laurl);

}

function borraunload(){

if(deleted!=1){
deleteuser(esapersona);
deleted=1;
}

}


</script>
<body onUnload="borraunload();">
<table  border="0" align="center" cellpadding="0" cellspacing="0">
  <tr>
    <td align="center">
    
<OBJECT classid="clsid:D27CDB6E-AE6D-11cf-96B8-444553540000"
 codebase="http://active.macromedia.com/flash2/cabs/swflash.cab#version=4,0,0,0"
 ID="chat" WIDTH=700 HEIGHT=500>
 <PARAM NAME=movie VALUE="chat.swf?login=<? print $login;?>&id_materia_periodo_lectivo=<? print $id_materia_periodo_lectivo;?>&browser=<? print $browser;?>">
 <PARAM NAME=menu VALUE=false>
 <PARAM NAME=quality VALUE=best>
 <PARAM NAME=wmode VALUE=transparent>
 <EMBED name="chat" src="chat.swf?login=<? print $login;?>&id_materia_periodo_lectivo=<?=$id_materia_periodo_lectivo?>&browser=<? print $browser?>" menu=false quality=best wmode=transparent WIDTH=640 HEIGHT=480 TYPE="application/x-shockwave-flash" PLUGINSPAGE="http://www.macromedia.com/shockwave/download/index.cgi?P1_Prod_Version=ShockwaveFlash" swLiveConnect="true"></EMBED>
</OBJECT></td>
  </tr>
  <tr>
    <td align="center"></td>
  </tr>
  <tr class="even">
    <td><?php print "<hr noshade size=\"1\">
        <table  align=\"center\" border=\"0\" cellspacing=\"0\" cellpadding=\"0\">
         <tr>
           <td valign=\"top\"><font face=\"Verdana, Arial, Helvetica, sans-serif\" size=\"1\">
             <ul>
               <strong>"._MD_CHAT_REC1."</strong><br>
               <li>"._MD_CHAT_REC2."</li>
               <li>"._MD_CHAT_REC3."</li>
               <li>"._MD_CHAT_REC4."</li>
             </ul></font>
           </td>
         </tr>
       </table>"; ?></td>
  </tr>
</table>
</body>
</html>
