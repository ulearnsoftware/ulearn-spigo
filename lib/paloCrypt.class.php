<?php
    //**************************************
    //     
    // Name: BlastCipher Encryption/Decrypti
    //     on Algorithm
    // Description:This encrypts and decrypt
    //     s data based on a key in many levels, us
    //     ing double XOR, Vigenere and more, with 
    //     almost unpredictable key-generating func
    //     tions. The many functions demonstrate en
    //     cryption to beginners and intermediate u
    //     sers and it is useful for encrypting you
    //     r sensitive data (although it is difficu
    //     lt for individuals to crack, don't think
    //     experienced cryptanalysts can't...) Even
    //     if one single character of the key is ch
    //     anged, the output will be totally differ
    //     ent, due to the key generating gang of f
    //     unctions.
    // By: Benjamin Choi
    //
    //This code is copyrighted and has    // limited warranties.Please see http://
    //     www.Planet-Source-Code.com/vb/scripts/Sh
    //     owCode.asp?txtCodeId=1066&lngWId=8    //for details.    //**************************************
    //     
    
    $toencrypt = "Text to be encrypted";
    $key = array("Hmmm, try to crack this!", "Are you good at cracking?", "Bruteforcing is no easy task on a slow computer.");
    $encrypt = 1; # 0 means decrypt, 1 means encrypt
    $numtimes = 1;
    $mymsg = $toencrypt;
    for($i=0; $i<$numtimes; $i++) {
    	$mymsg = blastcipher_crypt($mymsg, $key[$i], $encrypt);
    }
    echo $mymsg;

echo "original" .    blastcipher_crypt($mymsg, $key[0], 0);

    function blastcipher_crypt($toencrypt, $key, $encrypt=1) {
    	$mykey = keygen($key, $toencrypt);
    	$mykey2 = keygen2($key, $toencrypt);
    	$mykey3 = keygen3($key, $toencrypt);
    	
    	$mymsg = $toencrypt;
    	if ($encrypt) {
    		$mymsg = xorcrypt($mykey2, $mymsg, true);
    		$mymsg = blastcipher($mykey, $mykey3, $mymsg, true);
    		$mymsg = xorcrypt($mykey2, $mymsg, true);
    		$mymsg = base64_encode($mymsg);
    	}
    	else {
    		$mymsg = base64_decode($mymsg);
    		$mymsg = xorcrypt($mykey2, $mymsg, false);
    		$mymsg = blastcipher($mykey, $mykey3, $mymsg, false);
    		$mymsg = xorcrypt($mykey2, $mymsg, false);
    	}
    	
    	return $mymsg;
    }
    function keygen($key, $msg) {
    	# BlastCipher key generator
    	# Comments: totally unpredictable!
    	$thekey = md5($key);
    	while (strlen($thekey) < strlen($msg)) {
    		$seed = abs(crc32($thekey)) % strlen($thekey);
    		$thekey .= substr(md5(substr($thekey, $seed, 1)), ($seed % 4), 8);
    	}
    	return $thekey;
    }
    function keygen2($key, $msg) {
    	# BlastCipher key generator 2
    	# Comments: totally unpredictable again! now even crazier!
    	$thekey = md5($key . $msg);
    	while(strlen($thekey) < strlen($msg)) {
    		$seed = 5;
    		if (abs(crc32($thekey)) % 2 == 0) {
    			$thekey .= base_convert(substr(crypt($thekey, $msg), 10, 3), 36, 16);
    		}
    		else {
    			$thekey .= md5($thekey . substr($msg, strlen($thekey), 3));
    		}
    	}
    	return $thekey;
    }
    function keygen3($key, $msg) {
    	# BlastCipher key generator 3
    	# Comments: totally unpredictable yet again!
    	$thekey = base_convert(abs(crc32($key . $msg)), 10, 16);
    	while (strlen($thekey) < strlen($msg)) {
    		$seed = abs(crc32($thekey . $key . $msg . $seed)) % strlen($thekey);
    		$thekey .= substr(md5($key . $thekey . $msg . $seed), ($seed % 4), 8);
    	}
    	return $thekey;
    }
    function blastcipher($key, $key2, $msg, $encrypt=true) {
    	$themsg = $msg;
    	$mymsg = "";
    	for($i=0; $i<strlen($themsg); $i++) {
    		if ($encrypt) {
    			$ord = (ord(substr($themsg, $i, 1)) + ord(substr($key, $i, 1))) % 255;
    			if ($ord < 0) $ord = 255 - $ord;
    			$mymsg .= chr($ord);
    		}
    		else {
    			$ord = (ord(substr($themsg, $i, 1)) - ord(substr($key, $i, 1))) % 255;
    			if ($ord < 0) $ord = 255 - $ord;
    			$mymsg .= chr($ord);
    		}
    	}
    	return $mymsg;
    }
    function xorcrypt($key, $msg, $encrypt=true) {
    	$mymsg = "";
    	for($i=0; $i<strlen($msg); $i++) {
    		# XOR encryption
    		$base2_msg = base_convert(ord(substr($msg, $i, 1)), 10, 2);
    		$base2_key = base_convert(ord(substr($key, $i, 1)), 10, 2);
    		while(strlen($base2_msg) < strlen($base2_key)) $base2_msg = "0" . $base2_msg;
    		while(strlen($base2_msg) > strlen($base2_key)) $base2_key = "0" . $base2_key;
    		$mybinary = "";
    		# I like to flip bits
    		for($j=0; $j<strlen($base2_msg); $j++) {
    			$thebyte = intval(substr($base2_msg, $j, 1)) xor intval(substr($base2_key, $j, 1)) . "";
    			$mybinary .= $thebyte;
    		}
    		# Convert to character in ASCII
    		$endchar = chr(base_convert($mybinary, 2, 10));
    		$mymsg .= $endchar;
    	}
    	return $mymsg;
    }
    function compress($data, $key) {
    	$arrdata = explode(" ", $data);
    	$compressed = "";
    	$dictionary = array();
    	for($i=0; $i<count($arrdata); $i++) {
    		$exists = false;
    		unset($exists_location);
    		for($j=0; $j<count($dictionary); $j++) {
    			if ($arrdata[$i] == $dictionary[$j]) {
    				$exists = true;
    				$exists_location = $j;
    			}
    		}
    		if (!$exists) {
    			$dictionary[count($dictionary)] = $arrdata[$i];
    			$compressed .= chr(count($dictionary) - 1);
    		}
    		else {
    			$compressed .= chr($exists_location);
    		}
    	}
    	$dictionary_str = implode(";", $dictionary);
    	return $dictionary_str . "@" . $compressed;
    }
    function decompress($data, $key) {
    	$arrdata = explode("@", $data);
    	$dictionary = explode(";", $arrdata[0]);
    	$compressed = $arrdata[1];
    	for($i=0; $i<strlen($compressed); $i++) {
    		$decompressed .= $dictionary[ord(substr($compressed, $i, 1))] . " ";
    	}
    	#return $data;
    	return $decompressed;
    }
    ?>
