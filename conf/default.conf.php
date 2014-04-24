<?php

$config->nombre_proyecto = "Ulearn";
$config->dsn  = "mysql://ulearn:palosanto@localhost/ulearn";
$config->dsn2 = "mysql://spigo:test@localhost/spigo";
$config->skin = "default";
$config->default_user = "";
$config->dir_base = "/opt/ulearn-spigo";
$config->dir_base_foros = "/opt/ulearn-spigo/foros";
$config->dir_base_calificables = "/opt/ulearn-spigo/calificables";
$config->prefix_mat ="materia_";
$config->prefix_mpl ="mpl_";
// idioma local
$config->locale = "es_ES";
// tags que pueden ser utilizados en el diseño del contenido
$config->html_tablas = "<table> <tr> <td> <th> ";
$config->html_parrafos = "<br> <p> ";
$config->html_titulos = "<h1> <h2> <h3> <h4> <h5> <h6> ";
$config->html_textos = "<b> <i> <strong> <small> <big> <em> <sub> <sup> <pre> ";
$config->html_listas = "<ul> <ol> <li> ";
$config->html_diccionarios = "<dl> <dt> <dd> ";
$config->html_enlaces = "<a> ";
$config->html_imagenes = "<img> ";
// tags que se permite ingresar en contenido
$config->html_default = "";

$conf =& $config;
?>
