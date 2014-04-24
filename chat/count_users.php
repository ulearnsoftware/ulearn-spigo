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
        $lines = file("cache/users.txt");
        $a = count($lines);
        $counter=0;
        $users_counter=0;
        for($i = $a; $i >= 0 ;$i=$i-2){
        $each_user = strval($lines[$i]);//each connected user
        $each_user = str_replace ("\n","", $each_user);
        $each_password = strval($lines[$i+1]);
        $each_password = str_replace ("\n","", $each_password);
        $each_password = trim ($each_password);
        $userisgood=1;
        if (($each_password=="kicked")or($each_password=="banned")){$userisgood=0;}
        if (($each_user!="") and ($userisgood==1)){
        $users_counter++;
        $counter=$users_counter;
        }
        }
        $users_counter=strval($users_counter)." ".$person_word;
        
        if($counter != 1){
        $users_counter=$users_counter.$plural_particle;
        }
$users_counter=$users_counter.$now_in_the_chat;
        
        echo "&users_counter=";
echo urlencode($users_counter);
?>