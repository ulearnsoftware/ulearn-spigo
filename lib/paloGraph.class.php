<?php
/* vim: set expandtab tabstop=4 softtabstop=4 shiftwidth=4: */
// +----------------------------------------------------------------------+
// | Copyright (c) 1997-2003 PaloSanto Solutions S. A.                    |
// +----------------------------------------------------------------------+
// | Cdla. Nueva Kennedy Calle E #222 y 9na. Este                         |
// | Telfs. 2283-268, 2294-440, 2284-356                                  |
// | Guayaquil - Ecuador                                                  |
// +----------------------------------------------------------------------+
// | Este archivo fuente esta sujeto a las politicas de licenciamiento    |
// | de PaloSanto Solutions S. A. y no esta disponible publicamente.      |
// | El acceso a este documento esta restringido segun lo estipulado      |
// | en los acuerdos de confidencialidad los cuales son parte de las      |
// | politicas internas de PaloSanto Solutions S. A.                      |
// | Si Ud. esta viendo este archivo y no tiene autorizacion explicita    |
// | de hacerlo comuniquese con nosotros, podria estar infringiendo       |
// | la ley sin saberlo.                                                  |
// +----------------------------------------------------------------------+
// | Autores: Edgar Landivar <e_landivar@palosanto.com                    |
// |          Otro           <alguien@example.com>                        |
// +----------------------------------------------------------------------+
//
// $Id: paloGraph.class.php,v 1.1.1.1 2006/03/03 21:59:09 ainiguez Exp $

// NOTA IMPORTANTE: No se si hacer que el formato de un color hexadecimal comience siempre con
// un numeral # ya que se me ocurre que por ejemplo si tengo un color como 009933 php lo va ha
// hacer 9933 quitandole los ceros a la izquierda... si pongo el # prevengo esto... mmm... no estoy
// seguro todavia

class PaloGraph {

    var $image;
    var $arr_colores;
    var $arrPaleta;
    var $lienzoAncho;
    var $lienzoAltura;
    var $anchoCuadricula;
    var $bordeInternoIzq;
    var $_timestampInicioIntervalo;
    var $_timestampInicioSiguienteIntervalo;
    var $_duracionIntervaloSegundos;
    var $_duracionGraficoSegundos;
    var $arrGrilla;

    function PaloGraph($ancho, $altura, $bgColorHEX="")
    {
        $this->lienzoAncho  = $ancho;
        $this->lienzoAltura = $altura;

        $this->image        = imagecreate($this->lienzoAncho, $this->lienzoAltura);
        if($bgColorHEX=="" or !$this->_esColorHex($bgColorHEX)) {
            $bgColorHEX = "FFFFFF";
        }
        $this->arrPaleta = array($bgColorHEX => $this->_imageColorAllocateHEX($bgColorHEX));
        imagefill($this->image, $this->lienzoAncho, $this->lienzoAltura, $this->arrPaleta[$bgColorHEX]);
    }    

    // hay que pasarle ancho, alto, x, y
    function crearPie3D($arrValores, $arrColores) // seria bueno pasar el ultimo parametro por defecto y tener un arreglo por defecto
     {
        // tengo que verificar que $arr_valores contenga un arreglo

        $arrColoresCla   = array();
        $arrColoresOsc   = array();

        $contador=0;
        foreach($arrColores as $v) {
            // NOTA IMPORTANTE: Supongo que el color hexadecimal, es decir $v no va a contener el caracter de #... mejor dicho
            // ya voy a quitar el soporte de este caracter...
            $arrColoresCla[$contador] = $v;
            $colorOscurecido = $this->_oscureceColor($v, 0.2);
            $arrColoresOsc[$contador]= $colorOscurecido;
            if(!$this->_estaColorEnPaleta($v)) {
                $this->arrPaleta[$v] = $this->_imageColorAllocateHEX($v);
            }
            if(!$this->_estaColorEnPaleta($colorOscurecido)) {
                $this->arrPaleta[$colorOscurecido] = $this->_imageColorAllocateHEX($colorOscurecido);
            }
            $contador++;
        }

        for($y=60; $y>50; $y--) {
            $this->_dibujaPie($arrValores, 60, $y, 100, 50, $arrColoresOsc);
        }
        $this->_dibujaPie($arrValores, 60, 50, 100, 50, $arrColoresCla);
        // me gustaria aqui al final desalocar colores... no se si se pueda antes de dibujar la imagen final... mmm
    }

    function crearBarra($progreso, $x=6, $y=6, $altura=5, $ancho=16, $color_relleno="FF6600", $color_borde="333333")
    {
        // ta verde aun la funcion crearBarra, tengo que terminarla de implementar...
        if(!$this->_esColorHEX($color_borde)) {
            $color_borde = "333333";
        }

        if(!$this->_esColorHEX($color_relleno)) {
            $color_relleno = "FF6600";
        }

        // valores por defecto
        if(!isset($progreso) or ($progreso<0) or ($progreso > 1)) $progreso = 0;
        if(!isset($altura) or ($altura<0)) $altura = 5;
        if(!isset($ancho) or ($ancho<0)) $ancho = 16;
        if(!isset($x) or ($x<0)) $x = 6;
        if(!isset($y) or ($y<0)) $y = 6;

        if(!$this->_estaColorEnPaleta($color_borde)) {
            $this->arrPaleta[$color_borde]   = $this->_imageColorAllocateHEX($color_borde);
        }

        if(!$this->_estaColorEnPaleta($color_relleno)) {
            $this->arrPaleta[$color_relleno] = $this->_imageColorAllocateHEX($color_relleno);
        }

        /* dibujo el borde de la barra de progreso */
    
        // el espesor de la barra de progreso es el definido por la variable espesor_barra
    
        $barra_xizq = $x;
        $barra_xder = $x+$ancho;
        $barra_ysup = $y;
        $barra_yinf = $y+$altura;
    
        imageline ($this->image, $barra_xizq, $barra_ysup, $barra_xizq, $barra_yinf, $this->arrPaleta[$color_borde]); // marco izq.
        imageline ($this->image, $barra_xder, $barra_ysup, $barra_xder, $barra_yinf, $this->arrPaleta[$color_borde]); // marco der.
        imageline ($this->image, $barra_xizq, $barra_ysup, $barra_xder, $barra_ysup, $this->arrPaleta[$color_borde]); // marco sup.
        imageline ($this->image, $barra_xizq, $barra_yinf, $barra_xder, $barra_yinf, $this->arrPaleta[$color_borde]); // marco inf.

        /* dibujo la barra de progreso */
    
        // los limites para dicha barra son
    
        $prog_xizq = $barra_xizq + 1; // le aumento un pixel para que no dibuje encima del borde
        $prog_xder = $barra_xder - 1; // le disminuyo un pixel para que no dibuje encima del borde
        $prog_ysup = $barra_ysup + 1; // le aumento un pixel para que no dibuje encima del borde
        $prog_yinf = $barra_yinf - 1; // le disminuyo un pixel para que no dibuje encima del borde
    
        $ancho_barra_progreso = $prog_xder - $prog_xizq;
    
        $prog_xprogress = round((($progreso/1) * $ancho_barra_progreso) + $prog_xizq);
    
        imagefilledrectangle ($this->image, $prog_xizq, $prog_ysup, $prog_xprogress, $prog_yinf, $this->arrPaleta[$color_relleno]);
    }

    /*
    paso_x:     Debido a que se van a dibujar lineas rectas horizontales en lugar de puntos para cada coordenada, 
                se debe especificar cuanto medira cada linea. paso_x por tanto es la dimension de c/linea.
                Las lineas se dibujaran con un ancho de paso_x detras del punto de coordenada.
    */

    function crearLienzoGraficoContinuo($Xmin, $Xmax, $Ymin, $Ymax="", $duracionIntervalo="", $titulo="", $nombre_eje_x="", $nombre_eje_y="")
    {
        // Si el ancho del lienzo es menor a cierto valor, no grafico nada... doy error: lienzo muy pequenio
        // En esta version, el ancho del marco izq. va ha ser de 100 pixeles y el derecho de 40 pixeles 
        
        /*********** VALORES POR DEFECTO ***********/
        if(empty($margenPixeles)) $margenPixeles = 0;
        // Si no se ha especificado un ancho o altura se los calcula a partir de las variables de clase 
        // lienzoAncho y lienzoAltura
        if(empty($anchoPixeles)) $anchoPixeles = $this->lienzoAncho - ($margenPixeles*2);
        if(empty($alturaPixeles)) $alturaPixeles = $this->lienzoAltura - ($margenPixeles*2);
        if(empty($titulo)) $titulo = "Titulo";
        if(empty($nombre_eje_x)) $nombre_eje_x = "Eje X";
        if(empty($nombre_eje_y)) $nombre_eje_y = "Eje Y";

        // Dibujo primero el marco general (un rectangulo gris oscuro)
        imagefilledrectangle($this->image, $margenPixeles, $margenPixeles, $margenPixeles+$anchoPixeles-1, 
                        $margenPixeles+$alturaPixeles-1, $this->_obtenerColor('F3F3F3'));
        imagerectangle($this->image, $margenPixeles, $margenPixeles, $margenPixeles+$anchoPixeles-1, 
                        $margenPixeles+$alturaPixeles-1, $this->_obtenerColor('CCCCCC'));
        // Dibujo el marco del grafico en si, que va dentro del marco general
        // y que tiene un margen_izq=45px, margen_der=8, margen_sup=8, margen_inf=40

        if(empty($duracionIntervalo)) { 
            $this->_duracionIntervaloSegundos = 300;
        } else {
            $this->_duracionIntervaloSegundos = $duracionIntervalo; 
        }

        $margen_izq=55;
        $margen_der=8;
        $margen_sup=8;
        $margen_inf=55;
        $bordeInternoIzq=$margenPixeles+$margen_izq;
        $bordeInternoDer=$margenPixeles+$anchoPixeles-$margen_der;
        $bordeInternoSup=$margenPixeles+$margen_sup;
        $bordeInternoInf=$margenPixeles+$alturaPixeles-$margen_inf;
        $this->anchoCuadricula = $bordeInternoDer - $bordeInternoIzq - 2; // El menos 2 es por los 2 pixeles del marco

        imagefilledrectangle($this->image, $bordeInternoIzq, $bordeInternoSup, $bordeInternoDer, 
                        $bordeInternoInf, $this->_obtenerColor('FFFFFF'));
        imagerectangle($this->image, $bordeInternoIzq, $bordeInternoSup, $bordeInternoDer, 
                        $bordeInternoInf, $this->_obtenerColor('999999'));

        // Dibujo el titulo
        $this->_alinearTextoUnix2($titulo, $this->lienzoAncho/2, $this->lienzoAltura-$margenPixeles-30);
        $this->_alinearTextoUnix2("$nombre_eje_x vs. $nombre_eje_y", $this->lienzoAncho/2, $this->lienzoAltura-$margenPixeles-16); 


        if(empty($Xmax)) $Xmax = time();
        $this->timestampInicioGrafico = $Xmin; 
        $this->_duracionGraficoSegundos = $Xmax-$Xmin;

        /*************************
         *   MARCAS VERTICALES   *
         *************************/

        // Tengo que determinar que tipo de marca vertical dibujar. Pudiendo ser: 
        // a) c/2horas, b) cada dia, c) cada semana o d) cada mes, dependiendo
        // de la resolucion del grafico. 
        // Digamos que la resolucion del grafico se mide en tiempo/pixeles, de modo 
        // una resolucion de 300 seg/px significa que cada pixel del grafico representa
        // 300 segundos (5 min).
        // Para calcular la resolucion de un grafico entonces bastara con la sgte. formula:

        // Nota: Cabe hacer notar que he decidido intencionalmente no tomar en cuenta
        // la duracion del intervalo ($this->_duracionIntervaloSegundos) para este calculo 
        // aunque ciertamente constituye un parametro que denota resolucion.
        
        $resolucionGrafico = $this->_duracionGraficoSegundos/$this->anchoCuadricula;

        // Voy a clasificar el tipo de marca vertical dependiendo de la resolucion, asi:
        // a) c/2horas para resoluciones < 500 seg/px
        // b) c/dia para resoluciones < 5000 seg/px
        // c) c/semana para resoluciones < 20000 seg/px
        // d) c/mes para resoluciones >= 20000 seg/px 

        $arrMarcas = array();

        if($resolucionGrafico<500) {

            // Encuentro los timestamp para c/2 horas
            $numeroHorasDibujadas = floor($this->_duracionGraficoSegundos/3600);

            $timestampHoraActual = mktime(date('G'), 0, 0);
            for($i=0; $i<=$numeroHorasDibujadas; $i++) {
                $timestampMarca = $timestampHoraActual-$i*3600;
                $hora = date('G', $timestampMarca);
                if(($hora%2)!=0) $hora = "";
                // Verifico si las marcas entran en el grafico (Esto suele ocurrir con la ultima marca a la izq)
                if($timestampMarca>$this->timestampInicioGrafico) {
                    $arrMarcas[$timestampMarca] = $hora;
                }
            }
            
        } else if($resolucionGrafico>=500 and $resolucionGrafico<5000) {
            $numeroDiasDibujados = floor($this->_duracionGraficoSegundos/86400);

            $timestampDiaActual = mktime(0,0,0,date('n'),date('j'));
            for($i=0; $i<=$numeroDiasDibujados; $i++) {
                $timestampMarca = $timestampDiaActual - $i*86400;
                // Verifico si las marcas entran en el grafico (Esto suele ocurrir con la ultima marca a la izq)
                if($timestampMarca>$this->timestampInicioGrafico) {
                    $arrMarcas[$timestampMarca] = date('D', $timestampMarca); 
                }
            }

        } else if($resolucionGrafico>=5000 and $resolucionGrafico<20000) {
            $numeroSemanasDibujadas = floor($this->_duracionGraficoSegundos/604800);

            $timestampSemanaActual = mktime(0,0,0,date('n'),date('j')); // ESTOY POR AQUI
            for($i=0; $i<=$numeroSemanasDibujadas; $i++) {
                $timestampMarca = $timestampSemanaActual - $i*604800;
                // Verifico si las marcas entran en el grafico (Esto suele ocurrir con la ultima marca a la izq)
                if($timestampMarca>$this->timestampInicioGrafico) {
                    $arrMarcas[$timestampMarca] = "Semana";
                }
            }


        } else {
            $mesInicio = date('n', $this->timestampInicioGrafico); 
            $anioInicio = date('Y', $this->timestampInicioGrafico); 
            $mesFin = date('n', $this->timestampInicioGrafico+$this->_duracionGraficoSegundos);
            $anioFin = date('Y', $this->timestampInicioGrafico+$this->_duracionGraficoSegundos);

            $aniosTranscurridos = $anioFin-$anioInicio;

            $numeroMesesDibujados = floor($mesFin-$mesInicio+1+12*$aniosTranscurridos);

            $timestampMesActual = mktime(0,0,0,date('n'),1);
            for($i=0; $i<=$numeroMesesDibujados; $i++) {
                $timestampMarca = mktime(0,0,0,$mesFin-$i,1,$anioFin);
                // Verifico si las marcas entran en el grafico (Esto suele ocurrir con la ultima marca a la izq)
                if($timestampMarca>$this->timestampInicioGrafico) {
                    $arrMarcas[$timestampMarca] = date("M", $timestampMarca);
                }
            }

        }

        // Dibujo las lineas verticales
        if(is_array($arrMarcas)) {
            foreach($arrMarcas as $timestamp => $hora) {
                $Xmarca = $this->_timestampAPixel($timestamp, $bordeInternoIzq);
                // Dibujo la marca
                $this->dibujarLineaPunteada($Xmarca, $bordeInternoSup+1, $Xmarca, $bordeInternoInf-1, "CCCCCC");
                imageline($this->image, $Xmarca, $bordeInternoInf+1, $Xmarca, $bordeInternoInf+3, 
                          $this->_obtenerColor('999999'));
                $this->_alinearTextoUnix2($hora, $Xmarca, $bordeInternoInf+6);
            }
        }

        /*****************************
         *   FIN MARCAS VERTICALES   *
         *****************************/

        $this->arrGrilla['Xizq'] = $bordeInternoIzq;
        $this->arrGrilla['Xder'] = $bordeInternoDer;
        $this->arrGrilla['Ysup'] = $bordeInternoSup;
        $this->arrGrilla['Yinf'] = $bordeInternoInf;
    }

    function genSalida()
    {

        $this->_tareasFinales();
        header("Content-type: image/png");
        imagepng($this->image); 
        imagedestroy($this->image);
    }

    function _tareasFinales() 
    {
        // Esta funcion se deja vacia para que luego pueda ser redefinida en clases extendidas
    }

    // A CONTINUACION FUNCIONES PRIVADAS

    function _dibujaPie($arr_valores, $cx, $cy, $w, $h, $arr_colores)
    {   
        $suma_valores = array_sum($arr_valores);
        $totalcolores = sizeof($arr_colores);
        $totalvalores = sizeof($arr_valores);
        $grado_fin = 0;
        $numcolor=0;
        $numvalor=0;
        foreach($arr_valores as $i => $v) { 
            if($numcolor>=$totalcolores) $numcolor=0;
            // A continuacion considero el caso en el q el numero de valoreses n*$totalcolores+1 donde n es un entero
            // en este caso el color del ultimo arco no puede ser igual al color del primer arco porque se confundirian
            // NOTA: TENGO QUE CONSIDERAR EL CASO PARTICULAR DE CUANDO TENGO 3 COLORES O MENOS!!!...
            if($numcolor==0 and $numvalor==($totalvalores-1)) $numcolor++;
            // aqui me barro el arreglo arr_valores y obtengo las cantidades almacenadas y obtengo su
            // equivalente de 0-360 en relacion con el total almacenado en $suma_valores
            $grados_ancho = ($v/$suma_valores) * 360;
            $grado_ini = $grado_fin;
            $grado_fin = $grado_ini + $grados_ancho;
            $color_hex = $arr_colores[$numcolor];
            imagefilledarc ($this->image, $cx, $cy, $w, $h, $grado_ini, $grado_fin , $this->arrPaleta[$color_hex], IMG_ARC_PIE);
            $numcolor++;
            $numvalor++;
        }
    }

    function _oscureceColor($colorHEX, $intensidad)
    {
        if(!$this->_esColorHEX($colorHEX)) {
            return false;
        }

        if($intensidad>1) {
            $intensidad=1;
        }

        // calculo la intensidad...
        $intensidadRGB = $intensidad*100*2.55;
        
        $colorRR = hexdec(substr($colorHEX, 0, 2)) - $intensidadRGB;
        $colorGG = hexdec(substr($colorHEX, 2, 2)) - $intensidadRGB;
        $colorBB = hexdec(substr($colorHEX, 4, 2)) - $intensidadRGB;

        if($colorRR<0) $colorRR=0; 
        if($colorGG<0) $colorGG=0; 
        if($colorBB<0) $colorBB=0;
    
        $strRR = dechex($colorRR);
        $strGG = dechex($colorGG);
        $strBB = dechex($colorBB);

        if(strlen($strRR)==1) $strRR = "0" . $strRR; 
        if(strlen($strGG)==1) $strGG = "0" . $strGG; 
        if(strlen($strBB)==1) $strBB = "0" . $strBB;
 
        return $strRR . $strGG . $strBB;
    }

    function _imageColorAllocateHEX($s){
        // tendria que comprobar aqui si el string es de la longitud correcta y contiene los caracteres correctos
        if($this->_esColorHex($s)) { 
            $bg_dec=hexdec($s);
            return imagecolorallocate($this->image,
                   ($bg_dec & 0xFF0000) >> 16,
                   ($bg_dec & 0x00FF00) >>  8,
                   ($bg_dec & 0x0000FF)
                   );
        } else {
            return false; // no se si esta bien esta parte
        }
    }

    function _esColorHex($s)
    {
        return ereg("^[[:digit:]ABCDEFabcdef]{6}$", $s);
    }

    function _estaColorEnPaleta($s)
    {
        return array_key_exists($s, $this->arrPaleta);
    }

    function _ingresarColorEnPaleta($colorHex)
    {
        if(!$this->_estaColorEnPaleta($colorHex)) {
            $this->arrPaleta[$colorHex]   = $this->_imageColorAllocateHEX($colorHex);
        }
        return true;
    }

    function _obtenerColor($colorHex) {
        // Esta funcion maneja colores automaticamente

        $this->_ingresarColorEnPaleta($colorHex);
        return $this->arrPaleta[$colorHex];
    }

    function _alinearTextoUnix2($texto, $x, $y, $tipo="1") {
        // Parece que este tipo de texto es de ancho fijo de 5 pixeles
        // Primero calculo la longitud del texto
        $longitud = strlen($texto)*5;
        
        if($tipo==1) { // CENTRADO
            $xizq = $x - (int)$longitud/2;
            imagestring($this->image, 2, $xizq, $y, $texto, $this->_obtenerColor('000000'));
        } else if ($tipo==2) { // HACIA LA DER
            $xizq = $x - (int)$longitud;
            imagestring($this->image, 2, $xizq, $y, $texto, $this->_obtenerColor('000000'));
        } else { // HACIA LA IZQ
            imagestring($this->image, 2, $x, $y, $texto, $this->_obtenerColor('000000'));
        }
    }

    function dibujarLineaPunteada($x1, $y1, $x2, $y2, $colorHex="FF3333", $strPatron="x--")
    {
        if($x1>$x2) {
            $tmpx=$x1; $tmpy=$y1;
            $x1=$x2;   $y1=$y2;
            $x2=$tmpx; $y2=$tmpy;
        }    

        $longPatron = strlen($strPatron);

        if($strPatron{0}=="x" or $strPatron{0}=="X") {
            imagesetpixel($this->image, $x1, $y1, $this->_obtenerColor($colorHex));
        }

        $cuentaCaracter = 1;

        if(abs($x2-$x1)<abs($y2-$y1)) { // esta linea es mas vertical que horizontal

            if($y1>$y2) {
                $tmpx=$x1; $tmpy=$y1;
                $x1=$x2;   $y1=$y2;
                $x2=$tmpx; $y2=$tmpy;
            }


            for($i=($y1+1); $i<=$y2; $i++) {
                $x = $x1 + ($x2-$x1)/($i-$y1);
                if($strPatron{$cuentaCaracter}=="x" or $strPatron{$cuentaCaracter}=="X") {
                    imagesetpixel($this->image, $x, $i, $this->_obtenerColor($colorHex));
                }
                if($cuentaCaracter>=($longPatron-1)) {
                    $cuentaCaracter=0;
                } else {
                    $cuentaCaracter++;
                }
            }

        } else { // esta linea es mas horizontal que vertical

            if($x1>$x2) {
                $tmpx=$x1; $tmpy=$y1;
                $x1=$x2;   $y1=$y2;
                $x2=$tmpx; $y2=$tmpy;
            }

            for($i=($x1+1); $i<=$x2; $i++) {
                $y = $y1 + ($y2-$y1)/($i-$x1);
                if($strPatron{$cuentaCaracter}=="x" or $strPatron{$cuentaCaracter}=="X") {
                    imagesetpixel($this->image, $i, $y, $this->_obtenerColor($colorHex));
                }
                if($cuentaCaracter>=($longPatron-1)) {
                    $cuentaCaracter=0;
                } else {
                    $cuentaCaracter++;
                }
            }
        }
    }

    function _timestampAPixel($timestamp, $bordeIzquierdo)
    {
        // por ahora el tipo siempre es diario
        return ceil(((($this->anchoCuadricula*($timestamp-$this->timestampInicioGrafico))/($this->_duracionGraficoSegundos)) 
                + $bordeIzquierdo) + 1); // el anchoCuadricula es el ancho de la Grilla descontando ya el borde
                                      //

    }
}
?>
