<?php
//  ------------------------------------------------------------------------ //
// Author:   Carlos Leopoldo Magaña Zavala                                   //
// Mail:     carloslmz@msn.com                                               //
// URL:      http://www.xoopsmx.com & http://www.miguanajuato.com            //
// Module:   ChatMX                                                          //
// Project:  The XOOPS Project (http://www.xoops.org/)                       //
// Based on  Develooping flash Chat version 1.5.2                            //
// ------------------------------------------------------------------------- //

require('header.php');
// include the page header
include(XOOPS_ROOT_PATH.'/header.php');

//if username(.num) and password is taken go back
$users_file = "cache/users.txt";
$text_string = join ('', file ($users_file));
if (ereg ("$person(0|[1-9][0-9]*)*\n $password\n", $text_string)){
header("location:index_popup.php?nametaken=1");
}
require ('config.php');

if ($password_system=="ip"){
if (getenv("HTTP_CLIENT_IP")) $ip = getenv("HTTP_CLIENT_IP"); 
//else if(getenv("HTTP_X_FORWARDED_FOR")) $ip = getenv("HTTP_X_FORWARDED_FOR"); 
else if(getenv("REMOTE_ADDR")) $ip = getenv("REMOTE_ADDR"); 
else if($_SERVER["REMOTE_ADDR"]) $ip = $_SERVER["REMOTE_ADDR"]; // Linea por Edgar Landivar
else $ip = "UNKNOWN"; 
if (($password != $ip) or ($person=="")){
header("location:index_popup.php");
}
}else{
if (($person=="") or($password == "")) {
header("location:index_popup.php");
}
}
?>
<html>
<head>
<title><?php print "".$xoopsConfig['sitename']." - "._MD_CHAT_POPTI.""; ?></title>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
<link rel="stylesheet" type="text/css" href="<?php print "".XOOPS_THEME_URL."/".$xoopsConfig['theme_set']."/style.css";?>">
<script language="JavaScript">
<!--

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
var esapersona = '<?php echo $person;?>';


function addperson(unapersona){
var esapersona = unapersona;
}

function deleteuser(lapersona){
var deleted=1;
var laurl='users.php?person='+lapersona+'&password=<?php echo $password;?>&bye=bye';
window.open(laurl,'Bye','toolbar=no,scrollbars=no,width=300,height=300');
location.replace('index_popup.php');
}

function borraunload(){
if(deleted!=1){
deleteuser(esapersona);
deleted=1;
}

}

//-->
</script>
<body onUnload="borraunload();" class="even">
<table  border="0" align="center" cellpadding="0" cellspacing="0"  style="width:95% ">
  <tr>
    <td align="center" bgcolor="#FFFFFF"><font size=\"3\" face=\"Arial, Helvetica, sans-serif\"><strong><?php print ""._MD_CHAT_POPTI."";?></strong></font><br>
      <iframe name="private_messages" src="private_message.php"
					  width="550" height="50"
					  scrolling="No" frameborder="0" marginwidth="0" marginheight="0">
	  </iframe>   
<OBJECT classid="clsid:D27CDB6E-AE6D-11cf-96B8-444553540000"
 codebase="http://active.macromedia.com/flash2/cabs/swflash.cab#version=4,0,0,0"
 ID="chat" WIDTH=550 HEIGHT=300>
 <PARAM NAME=movie VALUE="chat.swf?person=<?php echo $person;?>&password=<?php echo $password;?>&browser=<?php echo $browser;?>"><PARAM NAME=menu VALUE=false><PARAM NAME=quality VALUE=best><PARAM NAME=wmode VALUE=transparent><EMBED name="chat" src="chat.swf?person=<?php echo $person;?>&password=<?php echo $password;?>&browser=<?php echo $browser;?>" menu=false quality=best wmode=transparent WIDTH=550 HEIGHT=300 TYPE="application/x-shockwave-flash" PLUGINSPAGE="http://www.macromedia.com/shockwave/download/index.cgi?P1_Prod_Version=ShockwaveFlash" swLiveConnect="true"></EMBED>
</OBJECT></td>
  </tr>
  <tr>
    <td align="center" bgcolor="#FFFFFF"><?php
print "<input type=\"button\" value=\""._MD_CHAT_SALI."\" style=\"cursor:hand;\" onClick=\"window.close()\"><br><br>";?></td>
  </tr>
  <tr>
    <td><?php print "<hr noshade size=\"1\">
    <font face=\"Verdana, Arial, Helvetica, sans-serif\" size=\"1\">"._MD_CHAT_HEL1." <a href=\"javascript:MM_openBrWindow('help.php','Help','toolbar=no,scrollbars=no,width=492,height=198')\">"._MD_CHAT_HEL2."</a>.</font>"; ?> </td>
  </tr>
  <tr>
    <td align="right"><font face="Verdana, Arial, Helvetica, sans-serif" size="1"><a href="http://www.xoopsmx.com" target="_blank">XOOPS M&eacute;xico</a></font></td>
  </tr>
</table>
</body>
</html>
