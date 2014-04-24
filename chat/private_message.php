<?php
//  ------------------------------------------------------------------------ //
// Author:   Carlos Leopoldo Magaa Zavala                                   //
// Mail:     carloslmz@msn.com                                               //
// URL:      http://www.xoopsmx.com & http://www.miguanajuato.com            //
// Module:   ChatMX                                                          //
// Project:  The XOOPS Project (http://www.xoops.org/)                       //
// Based on  Develooping flash Chat version 1.5.2                            //
// ------------------------------------------------------------------------- //
require ('config.php');
require_once("../lib/misc.lib.php");
print "<html><body bgcolor='#FFFFFF'>";

$person=recoger_valor("person",$_GET,$_POST);
$password=recoger_valor("password",$_GET,$_POST);
$dest=recoger_valor("dest",$_GET,$_POST);
$before_name=recoger_valor("before_name",$_GET,$_POST);
$after_name=recoger_valor("after_name",$_GET,$_POST);
$private_message_to=recoger_valor("private_message_to",$_GET,$_POST);

if ($dest!=""){
//inserta movie de flash si hay destinatario y el usuario esta en la lista

$existe_usuario=true;

if ($existe_usuario){
	?>
<OBJECT classid="clsid:D27CDB6E-AE6D-11cf-96B8-444553540000"
codebase="http://active.macromedia.com/flash2/cabs/swflash.cab#version=4,0,0,0"
ID="private_messages" WIDTH=550 HEIGHT=50>

<PARAM
NAME=movie
VALUE="private_messages.swf?pre=<?=$before_name?>&post=<?=$after_name?>&dest=<?=$dest?>&person=<?=$person?>&password=<?=$password?>&private_message_to=<?=$private_message_to.' '.$dest?>">
<PARAM
NAME=menu
VALUE=false>
<PARAM NAME=quality VALUE=best>
<PARAM NAME=wmode
VALUE=transparent>
<EMBED name="private_messages" src="private_messages.swf?pre=<?=$before_name?>&post=<?=$after_name?>&dest=<?=$dest?>&person=<?=$person?>&password=<?=$password?>&private_message_to=<?=$private_message_to.' '.$dest?>"
quality=best menu=false wmode=transparent WIDTH=550 HEIGHT=50 TYPE="application/x-shockwave-flash"
PLUGINSPAGE="http://www.macromedia.com/shockwave/download/index.cgi?P1_Prod_Version=ShockwaveFlash"
swLiveConnect="true">
</EMBED>
</OBJECT> 

<?php 
}
}	
print "</body></html>";
?>
