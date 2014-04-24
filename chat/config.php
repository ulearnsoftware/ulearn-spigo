<?php
//  ------------------------------------------------------------------------ //
// Author:   Carlos Leopoldo Magaa Zavala                                   //
// Mail:     carloslmz@msn.com                                               //
// URL:      http://www.xoopsmx.com & http://www.miguanajuato.com            //
// Module:   ChatMX                                                          //
// Project:  The XOOPS Project (http://www.xoops.org/)                       //
// Based on  Develooping flash Chat version 1.5.2                            //
// ------------------------------------------------------------------------- //

// Include the page header
//include(XOOPS_ROOT_PATH.'/header.php');
require_once("language/spanish/main.php");

error_reporting(7);

// Ajustes del chat
$url=""; //url absoluta del directorio donde se encuentran los scripts
$text_order = "down"; //usa "down" o "up" para mostrar el texto hacia abajo o hacia arriba respectivamente
$review_text_order = "down"; //igual pero en la ventana de revision de mensajes
$delete_empty_room = "no"; //usa "yes" si deseas que se borren los textos de la sala al quedarse vacia
$show_without_time = "no"; //"no" muestra siempre la hora, "yes" solo la muestra en la entrada y salida del usuario
// usa "ip" o "password" segn quieras usar la ip o un password para identificar usuarios
// NOTA: El sistema de banning (inhabilitacion de usuarios) solo funciona con "ip" Usa "password" preferiblemente solo en entornos donde haya usuarios conectados a trav� de una misma ip
$password_system = "ip"; // Si cambias esta variable. debes cambiar la misma variable del archivo admin/admin_config.php

//   Variables numericas del chat    //
$correct_time = 0; //diferencia en segundos con el tiempo en el servidor
$chat_lenght = 15; //numero de mensajes mostrados en la sala
$review_lenght = 500; //numero de mensajes mostrados al revisar mensajes
$total_lenght = 1000; //numero de mensajes almacenados por el sistema
$minutes_to_delete = 15; //minutos para eliminar usuarios inactivos

// Palabras para filtrar //
$words_to_filter = array("mierda", "joder", "follar", "jodan", "gilipollas", "capullo", "puto","puta", "pinche", "chinga", "wey", "culo", "verga", "cabron","chucha","chepa","culear","huevon",);//lista de palabrotas para filtar, pon las que quieras
$replace_by = "*@####!";//expresion para reemplazar a las palabrotas

//  TRADUCCION
// Fases en la pagina intro
$intro_alert=""._MD_CHAT_PORF.""; //
$alert_message_1=""._MD_CHAT_MSG1.""; //
$alert_message_2=""._MD_CHAT_MSG2.""; //
$alert_message_3=""._MD_CHAT_MSG3.""; //
$alert_message_4=""._MD_CHAT_MSG4.""; //
$alert_message_5=""._MD_CHAT_MSG5.""; //
$person_word=""._MD_CHAT_PERS.""; //
$plural_particle=""._MD_CHAT_PRES.""; //
$now_in_the_chat= ""._MD_CHAT_AHOR."";//
$name_word = ""._MD_CHAT_TUNO.""; //
$password_word = ""._MD_CHAT_TUCO.""; //
$enter_button = ""._MD_CHAT_ENTR."" ; //
$enter_sentence_1 = ""._MD_CHAT_RENO."";//
$enter_sentence_2 = ""._MD_CHAT_YCON."";//
$enter_sentence_3 = ""._MD_CHAT_BOTO."";//
$name_taken= ""._MD_CHAT_ONOM."";

// Frases en el chat
$private_message_expression = ""._MD_CHAT_PARA.""; // Expresion regular para indicar mensaje privado.
$before_name=""._MD_CHAT_PAR2.""; // si cambias la expresion regular pon aqui lo que hay entre "\ y (.*)
$after_name=""._MD_CHAT_PAR3.""; // si cambias la expresion regular pon aqui lo que detras de (.*) 
$not_here_string = ""._MD_CHAT_NODE.""; //el receptor del mensaje privado no esta en la sala
$bye_string = ""._MD_CHAT_BYEV."";//despedida para el usuario
$enter_string = ""._MD_CHAT_ADEN."";//mensaje que avisa que un usuario entra en la sala.
$bye_user = ""._MD_CHAT_ADSA."";//mensaje que avisa que un usuario abandona la sala.
$kicked_user = ""._MD_CHAT_HASE."";//mensaje mostrado en el chat al usuario expulsado.
$bye_kicked_user = ""._MD_CHAT_ADVE."";//despedida para el usuario expulsado
$bye_banned_user = ""._MD_CHAT_NOEN."";//despedida para el usuario inhabilitado
$banned_user = ""._MD_CHAT_SIEN."";//mensaje mostrado en el chat al usuario inhabilitado

// frases en la interface flash
$intro_text=""._MD_CHAT_INT1."\n\n";
$intro_text .="- "._MD_CHAT_INT2."\n";
$intro_text .="- "._MD_CHAT_INT3."\n";
$intro_text .="- "._MD_CHAT_INT4."\n\n";
$intro_text .=""._MD_CHAT_INT5."";
$conn=""._MD_CHAT_CONE."";
$you_are=""._MD_CHAT_TUER."";
$connected_users= ""._MD_CHAT_USCO."";
$private_message_to= ""._MD_CHAT_MEPR."";
$private_message_text=""._MD_CHAT_MPR1."";
$private_message_text.=""._MD_CHAT_NREC.""; 
$private_message_text.=""._MD_CHAT_TIP1."";
?>
