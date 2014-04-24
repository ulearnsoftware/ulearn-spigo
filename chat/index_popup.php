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
// Include the page header
include(XOOPS_ROOT_PATH.'/header.php');
require ('config.php');
if(eregi("win", $HTTP_USER_AGENT) and eregi("MSIE", $HTTP_USER_AGENT)){
$browser= "explorerwin";
}
else{
$browser= "";
}
?>
<html>
<head>
<title><?php print "".$xoopsConfig['sitename']." - "._MD_CHAT_POPTI.""; ?></title>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
<link rel="stylesheet" type="text/css" href="<?php print "".XOOPS_THEME_URL."/".$xoopsConfig['theme_set']."/style.css";?>">
<script language="JavaScript">
<!--
function MM_openBrWindow(theURL,winName,features) { //v2.0
  window.open(theURL,winName,features);
}
function check_chat_entra() { 
  var the_error='';
  var the_error_name='';
  var the_error_password='';
  var the_person=document.chat_entra.person.value;
  var the_password=document.chat_entra.password.value;
  var validperson=" abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ";
<?php 
if ($password_system=="ip"){
?>
  var validpassword=" 0123456789.";
<?php 
}else{
?>
var validpassword="abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
<?php
}
?>
if (the_person.length < 4){
the_error = "<?php echo $alert_message_1;?>\n";
}
<?php 
if ($password_system!="ip"){
?>
if (the_password.length < 4){
the_error = the_error + "<?php echo $alert_message_2;?>\n";
}
<?php 
}
?>   
for (var i=0; i<the_person.length; i++) {
   if (validperson.indexOf(the_person.charAt(i)) < 0) {
         the_error_name = "<?php echo $alert_message_3;?>\n";
        }
    }  
for (var i=0; i<the_password.length; i++) {
   if (validpassword.indexOf(the_password.charAt(i)) < 0) {
    <?php 
if ($password_system=="ip"){
?>
the_error_password = "<?php echo $alert_message_4;?>\n";
<?php 
}else{
?>
the_error_password = "<?php echo $alert_message_5;?>\n";
<?php 
}
?>
        }
    }  
the_error = the_error + the_error_name + the_error_password ;  
if (the_error!=''){alert('<?php echo $intro_alert;?>\t\t\t\t\t\n\n'+the_error)}
  document.return_the_value = (the_error=='');
}
//-->
</script>
<body class="even">
<?php print "<table align=\"center\"  bgcolor=\"#FFFFFF\" style=\"width:95% \">
   <tr>
     <td><center><font size=\"3\" face=\"Arial, Helvetica, sans-serif\"><strong>"._MD_CHAT_POPTI."</strong></font></center></td>
   </tr>
   <tr>
     <td align=\"center\"><hr noshade size=\"1\">
      <font size=\"2\"><strong>"._MD_CHAT_UACT."</strong></font><br><OBJECT classid=\"clsid:D27CDB6E-AE6D-11cf-96B8-444553540000\" codebase=\"http://download.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=6,0,29,0\" WIDTH=\"475\" HEIGHT=\"20\" align=\"baseline\">
 <PARAM NAME=movie VALUE=\"count_users.swf?browser="; echo $browser; print "\"><PARAM NAME=menu VALUE=false><PARAM NAME=\"quality\" VALUE=\"best\"><PARAM NAME=\"wmode\" VALUE=\"transparent\"><EMBED src=\"count_users.swf?browser="; echo $browser; print "\" WIDTH=\"475\" HEIGHT=\"20\" align=\"baseline\" menu=\"false\" quality=\"best\" wmode=\"transparent\" TYPE=\"application/x-shockwave-flash\" PLUGINSPAGE=\"http://www.macromedia.com/shockwave/download/index.cgi?P1_Prod_Version=ShockwaveFlash\" swLiveConnect=\"true\"></EMBED>
</OBJECT><hr noshade size=\"1\"></td>
   </tr>
   <tr class=\"even\">
     <td align=\"center\"> 
  <table width=\"100%\" border=\"0\" cellspacing=\"0\" cellpadding=\"0\" align=\"center\">
    <form name=\"chat_entra\" action=\"chatflash_popup.php\" method=\"POST\" onSubmit=\"check_chat_entra();return document.return_the_value\">
	<tr>
      <td colspan=\"2\" align=\"left\">        <font face=\"Verdana, Arial, Helvetica, sans-serif\" size=\"1\">&nbsp;
      </font>      <font face=\"Verdana, Arial, Helvetica, sans-serif\" size=\"2\"><strong>";
echo htmlentities($enter_sentence_1);
if ($password_system!="ip"){
echo htmlentities($enter_sentence_2);
}
echo htmlentities($enter_sentence_3);

print "</strong></font></td>
      </tr>
    <tr>
      <td align=\"right\"><font face=\"Verdana, Arial, Helvetica, sans-serif\" size=\"2\">";
if($nametaken==1 and $name_taken){
echo"<font color='#9900000'>".$name_taken;
}else{
echo htmlentities($name_word);
}
if($nametaken==1){
echo"&nbsp;</font>";
};
print "</font></td>
      <td align=\"left\"><input type=\"text\" name=\"person\" maxlength=\"12\" size=\"8\">";
 
if ($password_system=="ip"){

if (getenv("HTTP_CLIENT_IP")) $ip = getenv("HTTP_CLIENT_IP"); 
//else if(getenv("HTTP_X_FORWARDED_FOR")) $ip = getenv("HTTP_X_FORWARDED_FOR"); 
else if(getenv("REMOTE_ADDR")) $ip = getenv("REMOTE_ADDR"); 
else if($_SERVER["REMOTE_ADDR"]) $ip = $_SERVER["REMOTE_ADDR"]; // Linea por Edgar Landivar
else $ip = "UNKNOWN"; 

print "<input type=\"hidden\" name=\"password\" value=\""; echo $ip; print "\">";

}else{

print "</td>
      </tr>
    <tr>
      <td align=\"right\"><font face=\"Verdana, Arial, Helvetica, sans-serif\" size=\"2\">";
 echo htmlentities($password_word); 
print "</font></td>
      <td align=\"left\"><input type=\"text\" name=\"password2\" maxlength=\"12\" size=\"8\">";
}

print "</td>
      </tr>
    <tr>
      <td></td>
      <td align=\"left\"><input type=\"submit\" name=\"Submit\" value=\"";
 echo htmlentities($enter_button); 
print "\"></td>
      </tr>
    <tr align=\"left\">";
if ($password_system=="ip"){
print "<td>";
}else{ 
print "<td>";
}
print "</form></table>
    </td>
   </tr>
   <tr>
     <td align=\"center\"><table width=\"480\"  border=\"0\" cellspacing=\"0\" cellpadding=\"0\">
      <tr>
        <td valign=\"top\"><font face=\"Verdana, Arial, Helvetica, sans-serif\" size=\"1\">
          <ul>
          <strong>"._MD_CHAT_INTR1."</strong><br>
              <li>"._MD_CHAT_INTR2."</li>
              <li>"._MD_CHAT_INTR3."</li>
              <li>"._MD_CHAT_INTR4."</li>
        </ul></font></td>
        <td width=\"6\"></td>
        <td valign=\"top\"><font face=\"Verdana, Arial, Helvetica, sans-serif\" size=\"1\"><ul>
          <strong>"._MD_CHAT_INTR5."</strong>
              <li>"._MD_CHAT_INTR6." <a href=\"http://www.macromedia.com/go/getflashplayer/\" target=\"_blank\">"._MD_CHAT_INTR9."</a>.</li>
              <li>"._MD_CHAT_INTR7."</li>
              <li>"._MD_CHAT_INTR8."</li>
          </ul></font></td>
      </tr>
    </table></td>
   </tr>
   <tr>
     <td align=\"center\"><hr noshade size=\"1\">
      <font face=\"Verdana, Arial, Helvetica, sans-serif\" size=\"1\">"._MD_CHAT_HEL1." <a href=\"javascript:MM_openBrWindow('help.php','Help','toolbar=no,scrollbars=no,width=492,height=198')\">"._MD_CHAT_HEL2."</a>.</font></td>
   </tr>
 </table>"; 

?>

</body>
</html>
