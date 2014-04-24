<?php
//  ------------------------------------------------------------------------ //
// Author:   Carlos Leopoldo Magaña Zavala                                   //
// Mail:     carloslmz@msn.com                                               //
// URL:      http://www.xoopsmx.com & http://www.miguanajuato.com            //
// Module:   ChatMX                                                          //
// Project:  The XOOPS Project (http://www.xoops.org/)                       //
// Based on  Develooping flash Chat version 1.5.2                            //
// ------------------------------------------------------------------------- //

require ('config.php');
echo "&url=".urlencode($url);
echo "&pre=".urlencode($before_name)."&post=".urlencode($after_name);
echo "&output=".urlencode($conn);
echo "&you_are=".urlencode($you_are);
echo "&intro_text=".urlencode($intro_text);
echo "&private_message_to=".urlencode($private_message_to);
echo "&connected_users=".urlencode($connected_users);
echo "&private_message_text=".urlencode($private_message_text);
?>