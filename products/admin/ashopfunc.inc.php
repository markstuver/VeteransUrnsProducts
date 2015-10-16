<?php
// AShop
// Copyright 2015 - AShop Software - http://www.ashopsoftware.com
//
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; either version 2
// of the License, or (at your option) any later version.
//
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with this program; if not, see: http://www.gnu.org/licenses/.

// Validate variable name...
function ashop_validatename($name) {
	if (!preg_match("/[a-zA-Z_\x7f-\xff]/", substr($name, 0, 1))) return FALSE;
	for ($i = 1; $i <= strlen($name)-1; $i++) {
		if (!preg_match("/[a-zA-Z0-9_\x7f-\xff]/", substr($name, $i, 1))) return FALSE;
	}
	return TRUE;
}

// GD merging for watermarks...
function imagecopymerge_alpha($dst_im, $src_im, $dst_x, $dst_y, $src_x, $src_y, $src_w, $src_h, $pct){
    // creating a cut resource
    $cut = imagecreatetruecolor($src_w, $src_h);

    // copying relevant section from background to the cut resource
    imagecopy($cut, $dst_im, 0, 0, $dst_x, $dst_y, $src_w, $src_h);

    // copying relevant section from watermark to the cut resource
    imagecopy($cut, $src_im, 0, 0, $src_x, $src_y, $src_w, $src_h);

    // insert cut resource to destination image
    imagecopymerge($dst_im, $cut, $dst_x, $dst_y, 0, 0, $src_w, $src_h, $pct);
} 

// Backwards compatibility for MySQLi PHP extension...
function mysqli_result($res,$row=0,$col=0){
	$numrows = @mysqli_num_rows($res);
	if ($numrows && $row <= ($numrows-1) && $row >=0) {
        mysqli_data_seek($res,$row);
        $resrow = mysqli_fetch_array($res);
        if (isset($resrow[$col])){
            return $resrow[$col];
        }
    }
    return false;
}

// Copy a row in a database table...
function ashop_copyrow($table, $idfield, $copyid) {
	global $db;
	if ($table AND $idfield AND $copyid > 0) {
		$sql = "SELECT * FROM $table WHERE $idfield = '$copyid'";
		$result = @mysqli_query($db, $sql);
		if ($result) {
			$sql = "INSERT INTO $table SET ";
			$row = @mysqli_fetch_array($result);
			$rowkeys = array_keys($row);
			$rowvalues = array_values($row);
			$fieldnumber = 1;
			if (is_integer($rowkeys[0])) $startfield = 1;
			else $startfield = 0;
			for ($i=$startfield;$i<count($rowkeys);$i+=2) {
				if ($rowkeys[$i] != $idfield) {
					if ($fieldnumber!=1) $sql .= ", ";
					$sql .= $rowkeys[$i] . " = '" . $rowvalues[$i] . "'";
					$fieldnumber++;
				}
			}
			$result = @mysqli_query($db, $sql);
		}
	}
}

// Create a lock in the database...
function ashop_getlock($lockName) {
	global $db;
    $query = "SELECT GET_LOCK('".$lockName."', 0)";
    $result = mysqli_query($db, $query);
    $lockResult = mysqli_fetch_row($result);
    $lockResult = $lockResult[0];
    return $lockResult == 1 ? true : false;
}

// Release a lock in the database...
function ashop_releaselock($lockName) {
	global $db;
    $query = "SELECT RELEASE_LOCK('".$lockName."')";
    $result = mysqli_query($db, $query);
}

// Register a notification for debugging or logging...
function ashop_notification($message, $type="debug") {
	global $db;

	$message = addslashes($message);
	$type = addslashes($type);

	@mysqli_query($db, "INSERT INTO notification (type, message) VALUES ('$type','$message')");
}

// Check if a directory is empty...
function ashop_is_emptydir($dir) {
    if ($dh = @opendir($dir))
    {
        while ($file = readdir($dh))
        {
            if ($file != '.' && $file != '..') {
                closedir($dh);
                return false;
            }
        }
        closedir($dh);
        return true;
    }
    else return false;
}

// Input validation functions...
function ashop_is_zip($zip) {
	if (preg_match("/^[\-\: A-Za-z0-9]*$/", $zip)) return TRUE;
	else return FALSE;
}

function ashop_is_md5($hash) {
	if (preg_match("/^[0-9a-f]{32}$/", $hash)) return TRUE;
	else return FALSE;
}

function ashop_is_name($name) {
	if (preg_match("/^[ \-\.\'a-zA-ZÀ-ÿ]*$/", $name)) return TRUE;
	else return FALSE;
}

function ashop_is_address($address) {
	if (preg_match("/^[\-\.\(\)\#\'\\/, A-Za-zÀ-ÿ0-9]*$/", $address)) return TRUE;
	else return FALSE;
}

function ashop_is_phonenumber($phonenumber) {
	if (preg_match("/^[\-\.\(\)\+ 0-9]*$/", $phonenumber)) return TRUE;
	else return FALSE;
}

function ashop_is_email($email) {
	if (preg_match("/^[[:alnum:]][A-Za-z0-9_\.\-]*@[A-Za-z0-9\.\-]+\.[A-Za-z]{2,4}$/", $email)) return TRUE;
	else return FALSE;
}

function ashop_is_validemail($email) {
   $isValid = true;
   $atIndex = strrpos($email, "@");
   if (is_bool($atIndex) && !$atIndex) $isValid = false;
   else {
      $domain = substr($email, $atIndex+1);
      $local = substr($email, 0, $atIndex);
      $localLen = strlen($local);
      $domainLen = strlen($domain);
      if ($localLen < 1 || $localLen > 64) {
         // local part length exceeded
         $isValid = false;
      } else if ($domainLen < 1 || $domainLen > 255) {
         // domain part length exceeded
         $isValid = false;
      } else if ($local[0] == '.' || $local[$localLen-1] == '.') {
         // local part starts or ends with '.'
         $isValid = false;
      } else if (preg_match('/\\.\\./', $local)) {
         // local part has two consecutive dots
         $isValid = false;
      } else if (!preg_match('/^[A-Za-z0-9\\-\\.]+$/', $domain)) {
         // character not valid in domain part
         $isValid = false;
      } else if (preg_match('/\\.\\./', $domain)) {
         // domain part has two consecutive dots
         $isValid = false;
      } else if (!preg_match('/^(\\\\.|[A-Za-z0-9!#%&`_=\\/$\'*+?^{}|~.-])+$/', str_replace("\\\\","",$local))) {
         // character not valid in local part unless 
         // local part is quoted
         if (!preg_match('/^"(\\\\"|[^"])+"$/', str_replace("\\\\","",$local))) $isValid = false;
      }
      if ($isValid && !(checkdnsrr($domain,"MX") || checkdnsrr($domain,"A"))) {
         // domain not found in DNS
         $isValid = false;
      }
   }
   return $isValid;
}

function ashop_is_country($country) {
	if (preg_match("/^[\,\-\.\(\) A-Za-zÀ-ÿ]*$/", $country)) return TRUE;
	else return FALSE;
}

function ashop_is_state($state) {
	if (preg_match("/^[\,\-\.\(\) A-Za-zÀ-ÿ]*$/", $state)) return TRUE;
	else return FALSE;
}

function ashop_is_vatnumber($vatnumber) {
	if (preg_match("/^[\*\- A-Za-z0-9]*$/", $vatnumber)) return TRUE;
	else return FALSE;
}

function ashop_is_businessname($businessname) {
	if (preg_match("/^[\-\.\(\)\#\'\, A-Za-zÀ-ÿ0-9]*$/", $businessname)) return TRUE;
	else return FALSE;
}

function ashop_is_url($url) {
	if (preg_match("/^(http(s)?\:\/\/)?[\=\.\#\?\-\&\/A-Za-z0-9]*$/", $url)) return TRUE;
	else return FALSE;
}

function ashop_is_captchacode($captchacode) {
	if (preg_match("/^[0-9]{6}$/", $captchacode)) return TRUE;
	else return FALSE;
}

function ashop_is_ip($ipnumber) {
	if (preg_match('/^(?:25[0-5]|2[0-4]\d|1\d\d|[1-9]\d|\d)(?:[.](?:25[0-5]|2[0-4]\d|1\d\d|[1-9]\d|\d)){3}$/',$ipnumber)) return TRUE;
	else return FALSE;
}

function ashop_cleanfield($value) {
	$value = strip_tags($value);
	$value = urldecode($value);
	$value = html_entity_decode($value);
	$value = str_replace("<","",$value);
	$value = str_replace(">","",$value);
	$value = str_ireplace("script","",$value);
	return $value;
}

function ashop_simulatepost($posttoscript,$querystring) {
	unset($_POST);
	unset($_GET);
	unset($_COOKIE);

	$queryarray = explode("&",$querystring);
	if (!empty($queryarray) && is_array($queryarray)) foreach($queryarray as $querypart) {
		$querypartarray = explode("=",$querypart);
		$queryname = $querypartarray[0];
		$queryvalue = $querypartarray[1];
		$_POST["$queryname"] = $queryvalue;
	}

	if (file_exists($posttoscript)) {
		include "$posttoscript";
		return "SUCCESS";
	} else return "FAILED";
}

// Detect mobile devices and override layout with an adapted version...
function ashop_mobile() {
	global $device, $ashoppath, $itemsperrow, $displayitems, $templatepath, $buttonpath, $devices, $usethememobile, $iosdevice;

	if (!$devices) include $ashoppath."/admin/ashopconstants.inc.php";

	if (!$device) {
		$useragent = $_SERVER['HTTP_USER_AGENT'];
		$device = "";
		foreach ($devices as $thisuseragent=>$thisdevice) if (stripos($useragent,$thisuseragent)) $device = $thisuseragent;
		if ($device == "Android" && !stripos($useragent,"mobile")) $device = "AndroidTablet";
	}

	if (!empty($device) && (array_key_exists($device,$devices) || $device == "mobile")) {
		if ($devices[$useragent]["itemsperrow"] < $itemsperrow) $itemsperrow = $devices[$device]["itemsperrow"];
		if ($devices[$useragent]["displayitems"] < $displayitems) $displayitems = $devices[$device]["displayitems"];
		if ($usethememobile == "false") $mobiletemplatepath = "/templates";
		else $mobiletemplatepath = $templatepath;
		$newtemplatepath = $mobiletemplatepath."/".$devices[$device]["name"];
		if (is_dir($ashoppath.$newtemplatepath)) $templatepath = $newtemplatepath;
		$newbuttonpath = $buttonpath.$devices[$device]["name"];
		if (is_dir($ashoppath."/".$newbuttonpath."/images")) $buttonpath = $newbuttonpath;
		if ($device == "iPod" || $device == "iPad" || $device == "iPhone") $iosdevice = TRUE;
		return $devices[$device]["name"];
	} else return FALSE;
}


// Display the shipping form on another page...
function ashop_shippingform($action, $showfullform) {
	global $ashoppath, $basket;
	$mode = "include";
	$changeshipping = "true";
	include "$ashoppath/shipping.php";
}

// Get size of a directory in megabytes...
function ashop_getdirsize($dir){
	$dir_size =0;
	if (@is_dir($dir)) {
		if ($dh = @opendir($dir)) {
			while (($file = @readdir($dh)) !== false) {
				if($file !="." && $file != "..") {
					if(@is_file($dir."/".$file)) $dir_size += @filesize($dir."/".$file);
					if(@is_dir($dir."/".$file)) $dir_size += ashop_getdirsize($dir."/".$file);
				}
			}
		}
	}
	@closedir($dh);
	$dir_size = $dir_size/1024;
	$dir_size = $dir_size/1024;
	$dir_size = number_format($dir_size,2,'.','');
	return $dir_size;
}

// Get filesize of a remotely hosted file...
function ashop_remotefilesize($url) {
	ob_start();
	$ch = curl_init($url);
	curl_setopt($ch, CURLOPT_HEADER, 1);
	curl_setopt($ch, CURLOPT_NOBODY, 1);
	$ok = curl_exec($ch);
	curl_close($ch);
	$head = ob_get_contents();
	ob_end_clean();
	$regex = '/Content-Length:\s([0-9].+?)\s/';
	$count = preg_match($regex, $head, $matches);
	return isset($matches[1]) ? $matches[1] : "unknown";
}

// Check for spam injection attempts...
function ashop_mailsafe($field) {
	$nonewlines = preg_match("/(%0A|%0D|\\n+|\\r+)/i", $field) == 0;
	$nomailheaders = preg_match("/(content-type:|to:|cc:|bcc:)/i", $field) == 0;
	if ($nonewlines && $nomailheaders) return $field;
	else return FALSE;
}

// Open database connection...
function ashop_opendatabase() {
	global $db, $databaseserver, $databaseuser, $databasepasswd, $databasename;
	$error = 0;
	if (!@mysqli_get_server_info()) {
		$db = @mysqli_connect("$databaseserver", "$databaseuser", "$databasepasswd","$databasename");
		if (!$db) $error = 1;
	}
	return $error;
}

// Function for generating a random string...
function ashop_randomstring ($min, $max, $useupper, $usespecial, $usenumbers) {
	$characters = "abcdefghijklmnopqrstuvwxyz";
	if ($useupper) $characters .= "ABCDEFGHIJKLMNOPQRSTUVWXYZ";
	if ($usenumbers) $characters .= "0123456789";
	if ($usespecial) $characters .= "~@#$%^*()_+-={}|][";
	if ($min > $max) $length = mt_rand ($max, $min);
	else $length = mt_rand ($min, $max);
	$randomstring = "";
	for ($i=0; $i<$length; $i++) $randomstring .= $characters[(mt_rand(0,(strlen($characters)-1)))];
	return $randomstring;
}

// Function for generating an array with variations of a product...
function ashop_producttypes ($productid,$mode="valueids",$offset=0) {
	global $db;
	$types = array();
	$typecount = 0;
	$result = @mysqli_query($db, "SELECT * FROM parameters WHERE productid='$productid' ORDER BY parameterid LIMIT $offset,99999999999999");
	$row = @mysqli_fetch_array($result);
	$result2 = @mysqli_query($db, "SELECT * FROM parametervalues WHERE parameterid='{$row["parameterid"]}' ORDER BY valueid");
	if (@mysqli_num_rows($result2) > 1) {
		while ($row2 = @mysqli_fetch_array($result2)) {
			$valueid = $row2["valueid"];
			$value = $row2["value"];
			$valueprice = $row2["price"];
			if ($mode == "valueids") {
				if (@mysqli_num_rows($result) > 1) {
					$subvalueids = ashop_producttypes ($productid,$mode,$offset+1);
					foreach ($subvalueids as $subvalueid) {
						$types[$typecount] = $valueid."|".$subvalueid;
						$typecount++;
					}
				} else {
					$types[$typecount] = $valueid;
					$typecount++;
				}
			} else if ($mode == "values") {
				if (@mysqli_num_rows($result) > 1) {
					$subvalues = ashop_producttypes ($productid,$mode,$offset+1);
					foreach ($subvalues as $subvalue) {
						$types[$typecount] = $value.", ".$subvalue;
						$typecount++;
					}
				} else {
					$types[$typecount] = $value;
					$typecount++;
				}
			} else if ($mode == "prices") {
				$valueprice = 0;
				if (@mysqli_num_rows($result) > 1) {
					$subprices = ashop_producttypes ($productid,$mode,$offset+1);
					foreach ($subprices as $subprice) if (!empty($subprice)) $valueprice += $subprice;
				}
				$types[$typecount] = $valueprice;
				$typecount++;
			}
		}
	}
	return ($types);
}


// Function for checking for variations of a product...
function ashop_gettypes ($productid) {
	global $typestring, $typevalues, $typeprices;
	$typestring = ashop_producttypes ($productid,$mode="valueids");
	$typevalues = ashop_producttypes ($productid,$mode="values");
	$typeprices = ashop_producttypes ($productid,$mode="prices");
}

// Function for encryption of data...
function ashop_encrypt($decrypted, $password) {
	global $ashoppath;
	$key = hash('SHA256', $ashoppath . $password, true);
	srand(); $iv = mcrypt_create_iv(mcrypt_get_iv_size(MCRYPT_RIJNDAEL_128, MCRYPT_MODE_CBC), MCRYPT_RAND);
	if (strlen($iv_base64 = rtrim(base64_encode($iv), '=')) != 22) return false;
	$encrypted = base64_encode(mcrypt_encrypt(MCRYPT_RIJNDAEL_128, $key, $decrypted . md5($decrypted), MCRYPT_MODE_CBC, $iv));
	return $iv_base64 . $encrypted;
}

// Function for decryption of data...
function ashop_decrypt($encrypted, $password) {
	global $ashoppath;
	$key = hash('SHA256', $ashoppath . $password, true);
	$iv = base64_decode(substr($encrypted, 0, 22) . '==');
	$encrypted = substr($encrypted, 22);
	$decrypted = rtrim(mcrypt_decrypt(MCRYPT_RIJNDAEL_128, $key, base64_decode($encrypted), MCRYPT_MODE_CBC, $iv), "\0\4");
	$hash = substr($decrypted, -32);
	$decrypted = substr($decrypted, 0, -32);
	if (md5($decrypted) != $hash) return false;
	return $decrypted;
}

// Function for RC4 encryption/decryption of data...
function ashop_endecrypt($pwd, $data, $case='') {
	if ($case == 'de') {
		$data = urldecode($data);
	}
	$key[] = "";
	$box[] = "";
	$temp_swap = "";
	$pwd_length = 0;
	$pwd_length = strlen($pwd);
	for ($i = 0; $i <= 255; $i++) {
		$key[$i] = ord(substr($pwd, ($i % $pwd_length), 1));
		$box[$i] = $i;
	}
	$x = 0;
	for ($i = 0; $i <= 255; $i++) {
		$x = ($x + $box[$i] + $key[$i]) % 256;
		$temp_swap = $box[$i];
		$box[$i] = $box[$x];
		$box[$x] = $temp_swap;
	}
	$temp = "";
	$k = "";
	$cipherby = "";
	$cipher = "";
	$a = 0;
	$j = 0;
	for ($i = 0; $i < strlen($data); $i++) {
		$a = ($a + 1) % 256;
		$j = ($j + $box[$a]) % 256;
		$temp = $box[$a];
		$box[$a] = $box[$j];
		$box[$j] = $temp;
		$k = $box[(($box[$a] + $box[$j]) % 256)];
		$cipherby = ord(substr($data, $i, 1)) ^ $k;
		$cipher .= chr($cipherby);
	}
	if ($case == 'de') {
		$cipher = urldecode(urlencode($cipher));
	} else {
		$cipher = urlencode($cipher);
	}
	return $cipher;
}

// Generate code for form security check...
function generatecode($random) {
	global $databaseuser;
    $datekey = date("F j");
    $rcode = hexdec(md5($_SERVER[HTTP_USER_AGENT] . $databaseuser . $random . $datekey));
    $code = substr($rcode, 2, 6);
	return $code;
}

// Send mail by regular php mail(), SMTP and directly to eMerchant inbox if needed...
if(!function_exists('ashop_mail')) {
	function ashop_mail($recipient, $subject, $message, $headers, $attachment="") {
		global $db, $timezoneoffset, $ashoppath, $ashopname, $ashopemail, $databaseserver, $databaseuser, $databasepasswd, $databasename, $mailertype, $mailerserver, $mailerport, $maileruser, $mailerpass, $mimetypes;
		if (!$db) {
			// Open database connection if missing...
			$db = @mysqli_connect("$databaseserver", "$databaseuser", "$databasepasswd","$databasename");
			$openeddb = TRUE;
		} else $openeddb = FALSE;
		$result = @mysqli_query($db, "SELECT confvalue FROM emerchant_configuration WHERE confname='customeremail'");
		$emmail = @mysqli_result($result, 0, "confvalue");
		if ($recipient == $emmail && is_dir("$ashoppath/emerchant/mail")) {
			$timestamp = time()+$timezoneoffset;
			@mysqli_query($db, "INSERT INTO emerchant_inbox (received, name, email, subject) VALUES ('$timestamp', '$ashopname', '$recipient', '$subject')");
			$mailid = @mysqli_insert_id($db);
			if ($message) {
				$fp = @fopen ("$ashoppath/emerchant/mail/in1-$mailid", "w");
				if ($fp) {
					fwrite($fp, $headers."\n\n");
					fwrite($fp, $message);
					fclose($fp);
				}
			}
		} else if ($mailertype == "smtp") {
			require_once "$ashoppath/includes/class.phpmailer.php";
			$mail = new PHPMailer();
			if (strstr($headers,"text/html")) $mail->MsgHTML($message);
			else $mail->Body = $message;
			if ($mailerserver && $maileruser && $mailerpass) {
				$headersarray = explode("\n",$headers);
				if (!empty($headersarray) && is_array($headersarray)) {
					foreach ($headersarray as $header) {
						$thisheaderarray = explode(": ",$header);
						if (isset($thisheaderarray[0]) && $thisheaderarray[0] == "From") {
							$fullsender = $thisheaderarray[1];
							$senderarray = explode ("<",$fullsender);
							if (isset($senderarray[1])) {
								$sendername = $senderarray[0];
								$senderemail = str_replace(">","",$senderarray[1]);
							} else $senderemail = $fullsender;
						}
					}
				}
				if (!$senderemail) $senderemail = $ashopemail;
				if (!$sendername) $sendername = $ashopname;
				if (!$mailerport) $mailerport = "25";
				$mail->Port = $mailerport;
				$mail->IsSMTP();
				//$mail->SMTPDebug = 2;
				$mail->Host = $mailerserver;
				$mail->SMTPAuth = true;
				$mail->Username = $maileruser;
				$mail->Password = $mailerpass;
				$mail->From = $senderemail;
				$mail->FromName = $sendername;
				$mail->Subject = $subject;
				$mail->AddAddress($recipient, $recipientname);
				$result = $mail->Send();
				if (!$result) $result = $mail->ErrorInfo;
			} else $result = "SMTP Configuration Error";
		} else {
			if (!empty($attachment) && file_exists($attachment)) {
				$fileinfo = pathinfo("$attachment");
				$extension = $fileinfo["extension"];
				$filename = $fileinfo["basename"];
				if (!isset($mimetypes)) include "$ashoppath/ashopconstants.inc.php";
				$attachmentmimetype = $mimetypes["$extension"];
				$file = fopen($attachment,'rb');
				$filedata = fread($file,filesize($attachment));
				fclose($file);
				$mime_boundary = "<<<:" . md5(uniqid(mt_rand(), 1));
				$data = chunk_split(base64_encode($filedata));
				$sep = sha1(date('r', time()));
				if (strstr($headers,"text/html")) $messagetype = "text/html";
				else $messagetype = "text/plain";
				$headersarray = explode("Content-Type:",$headers);
				$headers = $headersarray[0];
				$headers .= "Content-Type: multipart/mixed;\n";
				$headers .= " boundary=\"".$mime_boundary."\"\n";
				$content = "This is a multi-part message in MIME format.\n\n";
				$content.= "--".$mime_boundary."\n";
				$content.= "Content-Type: $messagetype; charset=\"iso-8859-1\"\n";
				$content.= "Content-Transfer-Encoding: 7bit\n\n";
				$content.= "$message\n";
				$content.= "--".$mime_boundary."\n";
				$content.= "Content-Disposition: attachment;\n";
				$content.= "Content-Type: $attachmentmimetype; name=\"".$filename."\"\n";
				$content.= "Content-Transfer-Encoding: base64\n";
				$content.= "Content-ID: <PHP-CID-{$sep}>\n\n";
				$content.= $data."\n";
				$message = $content;
			}
			$result = @mail($recipient, $subject, $message, $headers);
		}
		if ($openeddb) @mysqli_close($db);
		if (empty($result)) $result = FALSE;
		return $result;
	}
}

// Windows and Mac compatible memory usage function...
if(!function_exists('memory_get_usage')) { 
	function memory_get_usage() {
		if ( substr(PHP_OS,0,3) == 'WIN') {
			if ( substr( PHP_OS, 0, 3 ) == 'WIN' ) {
				$output = array();
				exec( 'tasklist /FI "PID eq ' . getmypid() . '" /FO LIST', $output );
				return preg_replace( '/[\D]/', '', $output[5] ) * 1024;
			}
		} else {
			$pid = getmypid();
			exec("ps -eo%mem,rss,pid | grep $pid", $output);
			$output = explode("  ", $output[0]);
			return $output[1] * 1024;
		}
	} 
}

// Chunked readfile to preserve memory...
function readfile_chunked($filename,$retbytes=true) { 
   $chunksize = 1*(1024*1024);
   $buffer = ''; 
   $cnt =0; 
   $handle = fopen($filename, 'rb'); 
   if ($handle === false) { 
       return false; 
   } 
   while (!feof($handle)) { 
       $buffer = fread($handle, $chunksize); 
       echo $buffer; 
       ob_flush(); 
       flush(); 
       if ($retbytes) { 
           $cnt += strlen($buffer); 
       } 
   } 
       $status = fclose($handle); 
   if ($retbytes && $status) { 
       return $cnt;
   } 
   return $status; 
}

// Use this function to calculate shipping by price...
function priceshipping($price) {
	global $priceshipping;

	$shippingcost = 0;
	$previousshippinglevel = 0;
	if (!empty($priceshipping) && !empty($price) && is_numeric($price)) {
		$priceshippingarray = explode("|",$priceshipping);
		if (is_array($priceshippingarray)) {
			foreach ($priceshippingarray as $priceshippingpart) {
				$priceshippingpartarray = explode(":",$priceshippingpart);
				if (is_array($priceshippingpartarray)) {
					$priceshippinglevel = $priceshippingpartarray[0];
					$priceshippingcost = $priceshippingpartarray[1];
					if ($price > $previousshippinglevel && $price <= $priceshippinglevel) $shippingcost = $priceshippingcost;
					$previousshippinglevel = $priceshippinglevel;
				}
			}
		} else return 0;
	} else return 0;
	if ($shippingcost > 0) return $shippingcost;
	else return 0;
}

// Use this function to calculate shipping by weight range...
function weightshipping($weight) {
	global $weightshipping;

	$shippingcost = 0;
	$previousshippinglevel = 0;
	if (!empty($weightshipping) && !empty($weight) && is_numeric($weight)) {
		$weightshippingarray = explode("|",$weightshipping);
		if (is_array($weightshippingarray)) {
			foreach ($weightshippingarray as $weightshippingpart) {
				$weightshippingpartarray = explode(":",$weightshippingpart);
				if (is_array($weightshippingpartarray)) {
					$weightshippinglevel = $weightshippingpartarray[0];
					$weightshippingcost = $weightshippingpartarray[1];
					if ($weight > $previousshippinglevel && $weight <= $weightshippinglevel) $shippingcost = $weightshippingcost;
					$previousshippinglevel = $weightshippinglevel;
				}
			}
		} else return 0;
	} else return 0;
	if ($shippingcost > 0) return $shippingcost;
	else return 0;
}

// Use this function to calculate zip zone shipping...
function zoneshipping($destzip, $destcountry, $productid) {
	global $db;

	$sql  = "SELECT zone FROM zipzones, product WHERE product.productid = $productid AND product.shipping = CONCAT('zone',zipzones.zonename) AND zipzones.zip = '" . substr( $destzip, 0, 3 ) . "'";

	$result = @mysqli_query($db, $sql);

	if (!@mysqli_num_rows($result)) {
		$sql = "SELECT MAX(zip) FROM zipzones, product WHERE product.productid = $productid AND product.shipping = CONCAT('zone',zipzones.zonename) AND zipzones.zip < '" . substr( $destzip, 0, 3 ) . "'";

		$result = @mysqli_query($db, $sql);

		$zip = @mysqli_result($result, 0, "MAX(zip)");

		$sql = "SELECT zone FROM zipzones, product WHERE product.productid = $productid AND product.shipping = CONCAT('zone',zipzones.zonename) AND zipzones.zip = '$zip'";

		$result = @mysqli_query($db, $sql);
	}
	$zone = @mysqli_result($result, 0, "zone");

	if ( $zone > 1 and $zone < 9 ) {
		$sql = "SELECT rate FROM zonerates WHERE productid = " . $productid . " and zone = " . $zone;
		$result = @mysqli_query($db,  $sql );
		return @mysqli_result( $result, 0, "rate" );
	} else return 0;
}

function getresponsevalue($responsecode, $responsestring) {
	$startpos = strpos($responsestring, "\"$responsecode,\"");
	if ($startpos === false) return "";
	else {
		$endresponsestring = substr($responsestring, $startpos+strlen("\"$responsecode,\""));
		return substr($endresponsestring, 0, strpos($endresponsestring, "\""));
	}
}

// Get updated rates from the EU currency rates service...
function getcurrencyrates() {
	global $ashopcurrency;

	$euroratesurl = "http://www.ecb.europa.eu/stats/eurofxref/eurofxref-daily.xml";

	$currencies = array();
	$currencies["eur"] = "1.00";

	// Get Euro conversion rates...
	$ch = curl_init();
	if (file_exists("$ashoppath/admin/curl.inc.php")) include "$ashoppath/admin/curl.inc.php";
	curl_setopt ($ch, CURLOPT_URL,$euroratesurl);
	curl_setopt ($ch, CURLOPT_HEADER, 0);
	curl_setopt($ch, CURLOPT_POST, 0);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
	curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);
	$euroratesresult = curl_exec ($ch);
	curl_close ($ch);

	// Parse rates XML into an array...
	if (strpos($euroratesresult,"<Cube currency='")) {
		$currenciesxml = substr($euroratesresult,strpos($euroratesresult,"<Cube currency='"));
		$currenciesxml = substr($currenciesxml,0,strpos($currenciesxml,"</Cube>"));
		$currenciesxmlarray = explode("\n",$currenciesxml);
		foreach ($currenciesxmlarray as $currencypart) {
			$currencypart = trim($currencypart);
			$currencypart = str_replace("<Cube currency='","",$currencypart);
			$currencypart = str_replace("' rate='",":",$currencypart);
			$currencypart = str_replace("'/>","",$currencypart);
			$currency = explode(":",$currencypart);
			if (!empty($currency) && is_array($currency) && !empty($currency[0]) && !empty($currency[1])) {
				$currency[0] = strtolower($currency[0]);
				$currencies["{$currency[0]}"] = $currency[1];
			}
		}

		// Convert rates to the currency for this shop...
		if ($ashopcurrency != "eur") {
			$convertby = $currencies["$ashopcurrency"];			
			foreach($currencies as $currency=>$rate) {
				$rate = $rate/$convertby;
				$rate = number_format($rate,2,'.','');
				$currencies["$currency"] = $rate;
			}
		}

		$updated = time();

		// Store the rates in the database...
		foreach($currencies as $currency=>$rate) {
			if (!empty($rate)) {
				$result = @mysqli_query($db, "SELECT * FROM currencies WHERE currencycode='$currency'");
				if (!@mysqli_num_rows($result)) @mysqli_query($db, "INSERT INTO currencies (currencycode,rate,updated) VALUES ('$currency','$rate','$updated')");
				else @mysqli_query($db, "UPDATE currencies SET rate='$rate',updated='$updated' WHERE currencycode='$currency'");
			}
		}
	}
}

// Get the currency rate for a specific currency...
function getcurrency($currencycode) {
	global $db;
	$now = time();
	$currencycode = strtolower($currencycode);
	$getcurrencyresult = @mysqli_query($db, "SELECT * FROM currencies WHERE currencycode='$currencycode'");
	$updated = @mysqli_result($getcurrencyresult,0,"updated");
	if (($now - $updated) > 21600) {
		getcurrencyrates();
		$getcurrencyresult = @mysqli_query($db, "SELECT * FROM currencies WHERE currencycode='$currencycode'");
	}
	$rate = @mysqli_result($getcurrencyresult,0,"rate");
	return $rate;
}

// Use this function to calculate Fedex shipping fees...
function fedexshipping($origzip,$destzip,$origcountry,$destcountry,$origstate,$deststate,$weight,$declaredvalue,$fedexservice,$length=1,$width=1,$height=1) {
	global $fedexaccount, $fedexmeternumber, $fedexkey, $fedexpassword, $fedexserviceusa, $fedexserviceworld, $fedexpackagetype, $fedexpackagetypeworld, $errorstring, $ashoppath, $timezoneoffset, $ashopname, $ashopcurrency;

	if (empty($length)) $length = 1;
	if (empty($width)) $width = 1;
	if (empty($height)) $height = 1;

	if ($fedexservice == "00") $fedexservice = "";

	$currency = strtoupper($ashopcurrency);
	$weight = number_format($weight,0,'.','');
	$declaredvalue = number_format($declaredvalue,2,'.','');

	if ($destcountry == "US") $packagingtype = $fedexpackagetype;
	else $packagingtype = $fedexpackagetypeworld;
	if ($destcountry == "US") $service = $fedexserviceusa;
	else $service = $fedexserviceworld;
	if (!empty($fedexservice)) $service = $fedexservice;

	if ($service == "GROUND_HOME_DELIVERY") $residential = true;
	else $residential = false;

	$path_to_wsdl = "$ashoppath/admin/fedexwsdl/RateService_v13.wsdl";
	ini_set("soap.wsdl_cache_enabled", "0");

	$client = new SoapClient($path_to_wsdl, array('trace' => 1));
	
	$request['WebAuthenticationDetail'] = array('UserCredential' =>array('Key' => $fedexkey, 'Password' => $fedexpassword));
	$request['ClientDetail'] = array('AccountNumber' => $fedexaccount, 'MeterNumber' => $fedexmeternumber);
	$request['TransactionDetail'] = array('CustomerTransactionId' => ' *** Rate Request v13 using PHP ***');
	$request['Version'] = array('ServiceId' => 'crs', 'Major' => '13', 'Intermediate' => '0', 'Minor' => '0');
	$request['ReturnTransitAndCommit'] = true;
	
	$request['RequestedShipment']['DropoffType'] = 'REGULAR_PICKUP';
	$request['RequestedShipment']['ShipTimestamp'] = date('c');
	$request['RequestedShipment']['ServiceType'] = $service;
	$request['RequestedShipment']['PackagingType'] = $packagingtype;
	$request['RequestedShipment']['TotalInsuredValue']=array('Amount'=>$declaredvalue,'Currency'=>$currency);
	$request['RequestedShipment']['Shipper'] = array(
		'Contact' => array('PersonName' => '', 'CompanyName' => $ashopname, 'PhoneNumber' => ''),
		'Address' => array('StreetLines' => array(''), 'City' => '', 'StateOrProvinceCode' => $origstate, 'PostalCode' => $origzip, 'CountryCode' => $origcountry)
	);
	$request['RequestedShipment']['Recipient'] = array(
		'Contact' => array('PersonName' => '', 'CompanyName' => '', 'PhoneNumber' => ''),
		'Address' => array('StreetLines' => array(''), 'City' => '', 'StateOrProvinceCode' => $deststate, 'PostalCode' => $destzip, 'CountryCode' => $destcountry, 'Residential' => $residential)
	);
	$request['RequestedShipment']['ShippingChargesPayment'] = array(
		'PaymentType' => 'SENDER', // valid values RECIPIENT, SENDER and THIRD_PARTY
		'Payor' => array( 'ResponsibleParty' => array('AccountNumber' => $fedexaccount, 'CountryCode' => 'US') )
	);
	$request['RequestedShipment']['RateRequestTypes'] = 'ACCOUNT';
	$request['RequestedShipment']['RateRequestTypes'] = 'LIST';
	$request['RequestedShipment']['PackageCount'] = '1';
	$request['RequestedShipment']['RequestedPackageLineItems'] = array(
		'SequenceNumber'=>1,
		'GroupPackageCount'=>1,
		'Weight' => array(
			'Value' => $weight,
			'Units' => 'LB'
		),
		'Dimensions' => array(
			'Length' => $length,
			'Width' => $width,
			'Height' => $height,
			'Units' => 'IN'
		)
	);
	try {
		$response = $client ->getRates($request);
		if ($response -> HighestSeverity != 'FAILURE' && $response -> HighestSeverity != 'ERROR') {
			$rateReply = $response -> RateReplyDetails;
			return $rateReply->RatedShipmentDetails[0]->ShipmentRateDetail->TotalNetCharge->Amount;
		} else {
			$errorstring = "FedEx Error: ".$response->Notifications[0]->Message;
		}
	} catch (SoapFault $exception) { $errorstring = "error: $exception"; }
}

// Use this function to calculate UPS shipping fees...
function upsshipping($origzip,$destzip,$origcountry,$destcountry,$weight,$residential,$service) {
	global $upsaccesskey, $upsserviceusa, $upsservicecanusa, $upsserviceworld, $upspackagetype, $upspickuptype, $upspackagetypeworld, $errorstring, $ashoppath;

	if (($destcountry == "US" && $origcountry == "US") || ($destcountry == "CA" && $origcountry == "CA")) {
		$upsservice = $upsserviceusa;
		$upspackage = $upspackagetype;
	} else if (($destcountry == "US" && $origcountry == "CA") || ($destcountry == "CA" && $origcountry == "US")) {
		$upsservice = $upsservicecanusa;
		$upspackage = $upspackagetype;
	} else if ($destcountry == "PR") {
		if ($origcountry == "PR" || $origcountry == "US") {
			$upsservice = $upsserviceusa;
			$upspackage = $upspackagetype;
		} else {
			$upsservice = $upsserviceworld;
			$upspackage = $upspackagetypeworld;
		}
	} else {
		$upsservice = $upsserviceworld;
		$upspackage = $upspackagetypeworld;
	}
	if ($service && $service != "00") $upsservice = $service;
	if ($residential == "yes") $residentialflag = "<ResidentialAddressIndicator></ResidentialAddressIndicator>";
	else $residentialflag = "";
	if ($upspickuptype == "11") $customerclassification = "<CustomerClassification><Code>03</Code></CustomerClassification>";
	else $customerclassification = "";

	$raterequest = "<?xml version=\"1.0\"?><AccessRequest xml:lang=\"en-US\"><AccessLicenseNumber>$upsaccesskey</AccessLicenseNumber><UserId>rimheden</UserId><Password>rimheden</Password></AccessRequest><?xml version=\"1.0\"?><RatingServiceSelectionRequest xml:lang=\"en-US\"><Request><TransactionReference><CustomerContext>Rating and Service</CustomerContext><XpciVersion>1.0001</XpciVersion></TransactionReference><RequestAction>Rate</RequestAction><RequestOption>rate</RequestOption></Request><PickupType><Code>$upspickuptype</Code></PickupType>$customerclassification<Shipment><Shipper><Address><PostalCode>$origzip</PostalCode><CountryCode>$origcountry</CountryCode></Address></Shipper><ShipTo><Address><PostalCode>$destzip</PostalCode><CountryCode>$destcountry</CountryCode>$residentialflag</Address></ShipTo><Service><Code>$upsservice</Code></Service><Package><PackagingType><Code>$upspackage</Code><Description>Package</Description></PackagingType><Description>Rate Shopping</Description><PackageWeight><Weight>$weight</Weight></PackageWeight></Package></Shipment></RatingServiceSelectionRequest>";

	$ch = curl_init();
	if (file_exists("$ashoppath/admin/curl.inc.php")) include "$ashoppath/admin/curl.inc.php";
	curl_setopt ($ch, CURLOPT_URL,"https://www.ups.com/ups.app/xml/Rate");
	curl_setopt ($ch, CURLOPT_HEADER, 0);
	curl_setopt($ch, CURLOPT_POST, 1);
	curl_setopt($ch, CURLOPT_POSTFIELDS, "$raterequest");
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
	curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);
	$rateresult = curl_exec ($ch);
	curl_close ($ch);

	$totalchargestring = substr($rateresult, strpos($rateresult, "<TotalCharges>")+14);
	$totalchargestring = substr($totalchargestring, strpos($totalchargestring, "<MonetaryValue>")+15, strpos($totalchargestring, "</MonetaryValue>"));
	if (!$totalchargestring) $errorstring = "UPS Error: ".substr($rateresult, strpos($rateresult, "<ErrorDescription>")+18, strpos($rateresult, "</ErrorDescription>"));

	if ($errorstring) return "";
	else return $totalchargestring;
}

// Use this function to calculate USPS shipping fees...
function uspsshipping($origzip,$destzip,$destcountry,$weight,$declaredvalue,$service) {
	global $uspsuserid, $uspsserviceusa, $uspsserviceworld, $uspsmachinable, $uspssize, $uspscontainer, $errorstring, $ashoppath, $countries;

	// Quick fix to solve missing package dimensions issue...
	$uspssize = "REGULAR";

	// Convert weight to pounds and ounces...
	if ($weight > 1) {
		$pounds = round($weight);
		$ounces = ($weight-$pounds)*16;
	} else {
		$pounds = 0;
		$ounces = $weight*16;
	}

	if ($destcountry == "US") {
		if ($service && is_numeric($service)) {
			if ($service == "01") $uspsservice = "FIRST CLASS";
			else if ($service == "02") $uspsservice = "PRIORITY";
			else if ($service == "03") $uspsservice = "EXPRESS";
			else if ($service == "04") $uspsservice = "PARCEL";
			else if ($service == "05") $uspsservice = "MEDIA";
			else if ($service == "06") $uspsservice = "LIBRARY";
			else $uspsservice = $uspsserviceusa;
		} else $uspsservice = $uspsserviceusa;
		if ($weight > 0.8125 && $uspsservice == "FIRST CLASS") $uspsservice = "PRIORITY";
		$rateapi = "RateV4";
		$ratetype = "RateV4Request";
		if ($uspsservice == "PARCEL" && $uspssize == "REGULAR") $uspscontainer = "VARIABLE";
		if ($uspsservice == "PRIORITY" && $uspssize == "REGULAR" && ($uspscontainer == "RECTANGULAR" || $uspscontainer == "NONRECTANGULAR")) $uspscontainer = "VARIABLE";
		if ($uspsservice == "EXPRESS" && $uspssize == "REGULAR" && ($uspscontainer == "RECTANGULAR" || $uspscontainer == "NONRECTANGULAR" || $uspscontainer == "FLAT RATE BOX")) $uspscontainer = "VARIABLE";
		if ($uspsservice == "MEDIA" && $uspssize == "REGULAR" && ($uspscontainer == "FLAT RATE BOX" || $uspscontainer == "FLAT RATE ENVELOPE" || $uspscontainer == "RECTANGULAR" || $uspscontainer == "NONRECTANGULAR")) $uspscontainer = "VARIABLE";
		if ($uspsservice == "LIBRARY" && $uspssize == "REGULAR" && ($uspscontainer == "FLAT RATE BOX" || $uspscontainer == "FLAT RATE ENVELOPE" || $uspscontainer == "RECTANGULAR" || $uspscontainer == "NONRECTANGULAR")) $uspscontainer = "VARIABLE";
	} else {
		if ($service && is_numeric($service)) {
			if ($service == "01") $uspsservice = "First-Class Mail International Parcel";
			else if ($service == "02") $uspsservice = "USPS GXG";
			else if ($service == "03") $uspsservice = "Priority Mail International";
			else if ($service == "04") $uspsservice = "Express Mail International";
			else $uspsservice = $uspsserviceworld;
		} else $uspsservice = $uspsserviceworld;
		if ($uspsservice == "USPS GXG") $uspscontainer = "FLAT RATE ENVELOPE";
		$rateapi = "IntlRateV2";
		$ratetype = "IntlRateV2Request";
		foreach ($countries as $shortcountry=>$longcountry) if ($destcountry == $shortcountry) $destcountry = $longcountry;
	}
	if ($uspssize == "LARGE" && $uspscontainer != "RECTANGULAR" && $uspscontainer != "NON-RECTANGULAR") $uspscontainer = "RECTANGULAR";

	if ($rateapi == "RateV4") {
		$raterequest = "API=$rateapi&XML=<$ratetype USERID=\"$uspsuserid\">
		<Revision/>
		<Package ID=\"1ST\">
		<Service>$uspsservice</Service>";
		if ($uspsservice == "FIRST CLASS") $raterequest .= "
		<FirstClassMailType>LETTER</FirstClassMailType>";
		$raterequest .= "
		<ZipOrigination>$origzip</ZipOrigination>
		<ZipDestination>$destzip</ZipDestination>
		<Pounds>$pounds</Pounds>
		<Ounces>$ounces</Ounces>
		<Container>$uspscontainer</Container>
		<Size>$uspssize</Size>
		<Machinable>$uspsmachinable</Machinable>
		</Package>
		</$ratetype>";
	} else {
		$raterequest = "API=$rateapi&XML=<$ratetype USERID=\"$uspsuserid\">
		<Package ID=\"1ST\">
		<Pounds>$pounds</Pounds>
		<Ounces>$ounces</Ounces>
		<Machinable>$uspsmachinable</Machinable>
		<MailType>Package</MailType>
		<ValueOfContents>$declaredvalue</ValueOfContents>
		<Country>$destcountry</Country>
		<Container>$uspscontainer</Container>
		<Size>$uspssize</Size>
		<Width></Width>
		<Length></Length>
		<Height></Height>
		<Girth></Girth>
		<CommercialFlag>N</CommercialFlag>
		</Package>
		</$ratetype>";
	}

	$ch = curl_init();
	if (file_exists("$ashoppath/admin/curl.inc.php")) include "$ashoppath/admin/curl.inc.php";
	curl_setopt ($ch, CURLOPT_URL,"http://production.shippingapis.com/ShippingAPI.dll");
	curl_setopt ($ch, CURLOPT_HEADER, 0);
	curl_setopt ($ch, CURLOPT_POST, 1);
	curl_setopt ($ch, CURLOPT_POSTFIELDS,$raterequest);
	curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);
	$rateresult = curl_exec ($ch);
	curl_close ($ch);

	if ($rateapi == "IntlRateV2") {
		$availableservices = array();
		$servicesarray = explode("</Service>",$rateresult);
		foreach ($servicesarray as $thisservice) {
			if (strstr($thisservice,"<Postage>")) {
				$frompos = strpos($thisservice, "<Postage>")+9;
				$strlength = strpos($thisservice, "</Postage>")-$frompos;
				$thispostage = substr($thisservice, $frompos, $strlength);
				$frompos = strpos($thisservice, "<SvcDescription>")+16;
				$strlength = strpos($thisservice, "</SvcDescription>")-$frompos;
				$thisservicename = substr($thisservice, $frompos, $strlength);
				$thisservicename = str_replace("&amp;","&",$thisservicename);
				$thisservicename = str_replace("&lt;","<",$thisservicename);
				$thisservicename = str_replace("&gt;",">",$thisservicename);
				$thisservicename = htmlspecialchars_decode($thisservicename);
				$thisservicename = str_replace("&trade;","",$thisservicename);
				$thisservicename = str_replace("&reg;","",$thisservicename);
				$thisservicename = str_replace("*","",$thisservicename);
				$thisservicename = strip_tags($thisservicename);
				if (strstr($thisservicename,"Large")) {
					$thisservicesize = "LARGE";
					$thisservicename = str_replace("Large","",$thisservicename);
				} else {
					$thisservicesize = "REGULAR";
					$thisservicename = str_replace("Medium","",$thisservicename);
					$thisservicename = str_replace("Small","",$thisservicename);
				}
				if (strstr($thisservicename,"Flat Rate Box")) {
					$thisservicepackage = "FLAT RATE BOX";
					$thisservicename = str_replace("Flat Rate Boxes","",$thisservicename);
					$thisservicename = str_replace("Flat Rate Box","",$thisservicename);
				} else if (strstr($thisservicename,"Envelopes")) {
					$thisservicepackage = "FLAT RATE ENVELOPE";
					$thisservicename = str_replace("Envelopes","",$thisservicename);
				} else $thisservicepackage = "";
				$thisservicename = trim($thisservicename);
				$thisservicename = str_replace("  "," ",$thisservicename);
				if ($thisservicename == $uspsservice && $thisservicesize == $uspssize && (empty($thisservicepackage) || $thisservicepackage == $uspscontainer)) $totalchargestring = $thispostage;
				$availableservices[] .= $thispostage;
			}
			if (empty($totalchargestring)) {
				sort($availableservices);
				$totalchargestring = $availableservices[0];
			}

		}
	} else {
		if (strstr($rateresult,"<Rate>")) {
			$frompos = strpos($rateresult, "<Rate>")+6;
			$strlength = strpos($rateresult, "</Rate>")-$frompos;
			$totalchargestring = substr($rateresult, $frompos, $strlength);
		} else {
			$frompos = strpos($rateresult, "<Description>")+13;
			$strlength = strpos($rateresult, "</Description>")-$frompos;
			$errorstring = "USPS Error: ".substr($rateresult, $frompos, $strlength);
		}
	}
	if ($errorstring || empty($totalchargestring)) return "";
	else return $totalchargestring;
}

// Use this function to calculate Watkins Motor Lines shipping fees...
function wmlshipping($origzip,$destzip,$weight,$class) {
	global $ashoppath;
	$weight = floor($weight);
	$transaction = "org=$origzip&dest=$destzip&weight1=$weight&class1=$class";

	$ch = curl_init();
	if (file_exists("$ashoppath/admin/curl.inc.php")) include "$ashoppath/admin/curl.inc.php";
	curl_setopt ($ch, CURLOPT_URL,"http://www.fedexnational.fedex.com/us/autosub/autorate.asp?$transaction");
	curl_setopt ($ch, CURLOPT_HEADER, 0);
	curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);

	$result=curl_exec ($ch);
	$error=curl_error ($ch);
	curl_close ($ch);

	$resultlines = explode("\n",$result);
	foreach ($resultlines as $linenumber=>$line) if (strpos($line,"TOTAL:")) $totalline = $line;
	$total = strip_tags($totalline);
	$total = str_replace("TOTAL:","",$total);
	if ($error || !$total) return "error";
	else return $total;
}

// Use this function to initiate an Authorize.Net ARB rebilling cycle...
function authnetarb($user, $password, $productname, $amount, $recurring, $startdays, $repeat, $ccnumber, $expdate, $orderid, $firstname, $lastname, $address, $city, $zip, $state, $country, $email, $phone, $testmode) {
	global $ashoppath, $timezoneoffset;
	if ($repeat == "0") $repeat = "9999";
	$date = date("Y-m-d H:i:s", time()+$timezoneoffset);
	$datearray = explode(" ",$date);
	$datetime = explode(":",$datearray[1]);
	$datearray = explode("-",$datearray[0]);
	$startdatetimestamp = mktime($datetime[0],$datetime[1],$datetime[2],$datearray[1],$datearray[2]+$startdays,$datearray[0]);
	$startdate = date("Y-m-d", $startdatetimestamp);
	if ($testmode) $ccnumber = "4012888818888";
	$arburl = "https://api.authorize.net/xml/v1/request.api";
	if ($recurring == "weekly") {
		$unit = "days";
		$length = "7";		
	} else if ($recurring == "monthly") {
		$unit = "months";
		$length = "1";		
	} else if ($recurring == "quarterly") {
		$unit = "months";
		$length = "3";		
	} else if ($recurring == "semiannually") {
		$unit = "months";
		$length = "6";		
	} else if ($recurring == "annually") {
		$unit = "months";
		$length = "12";		
	}
	$arbsubscriptionrequest = "<?xml version=\"1.0\" encoding=\"utf-8\"?><ARBCreateSubscriptionRequest xmlns=\"AnetApi/xml/v1/schema/AnetApiSchema.xsd\"><merchantAuthentication><name>$user</name><transactionKey>$password</transactionKey></merchantAuthentication><subscription><name>$productname</name><paymentSchedule><interval><length>$length</length><unit>$unit</unit></interval><startDate>$startdate</startDate><totalOccurrences>$repeat</totalOccurrences></paymentSchedule><amount>$amount</amount><payment><creditCard><cardNumber>$ccnumber</cardNumber><expirationDate>$expdate</expirationDate></creditCard></payment><order><invoiceNumber>$orderid</invoiceNumber><description>AShop Deluxe Order</description></order><customer><email>$email</email><phoneNumber>$phone</phoneNumber></customer><billTo><firstName>$firstname</firstName><lastName>$lastname</lastName><address>$address</address><city>$city</city><state>$state</state><zip>$zip</zip><country>$country</country></billTo></subscription></ARBCreateSubscriptionRequest>";

	$ch = curl_init();
	if (file_exists("$ashoppath/admin/curl.inc.php")) include "$ashoppath/admin/curl.inc.php";
	$this_header = array(
		"MIME-Version: 1.0",
		"Content-type: text/xml; charset=unicode",
		"Content-transfer-encoding: text"
	);
	curl_setopt($ch, CURLOPT_URL,$arburl);
	curl_setopt($ch, CURLOPT_HEADER, 0);
	curl_setopt($ch, CURLOPT_HTTPHEADER, $this_header);
	curl_setopt($ch, CURLOPT_POST, 1);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $arbsubscriptionrequest);	
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

	$result=curl_exec ($ch);
	$error=curl_error ($ch);
	curl_close ($ch);
	return $result;
}

function get_facebook_user($accesstoken) {
	$facebookurl = "https://graph.facebook.com/me?access_token=$accesstoken";
	$ch = curl_init();
	if (file_exists("$ashoppath/admin/curl.inc.php")) include "$ashoppath/admin/curl.inc.php";
	curl_setopt ($ch, CURLOPT_URL,$facebookurl);
	curl_setopt ($ch, CURLOPT_HEADER, 0);
	curl_setopt($ch, CURLOPT_HTTPGET, 1);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
	curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);
	$facebookresult = curl_exec ($ch);
	curl_close ($ch);
	$user = json_decode($facebookresult);
	return $user;
}

function ashop_minfraudgeoip($ipaddress,$country) {
	global $minfraudgeoipkey;
	$query = "http://geoip.maxmind.com/a?l=" . $minfraudgeoipkey . "&i=" . $ipaddress;
	$url = parse_url($query);
	$host = $url["host"];
	$path = $url["path"] . "?" . $url["query"];
	$timeout = 1;
	$fp = fsockopen ($host, 80, $errno, $errstr, $timeout);
	if ($fp) {
		fputs ($fp, "GET $path HTTP/1.0\nHost: " . $host . "\n\n");
		while (!feof($fp)) $buf .= fgets($fp, 128);
		$lines = explode("\n", $buf);
		$data = $lines[count($lines)-1];
		fclose($fp);
		if ($data == strtoupper($country)) return true;
		else return false;
	} else return false;
}

function ashop_minfraudproxycheck($ipaddress) {
	global $minfraudkey, $minfraudgeoipkey;
	if (empty($minfraudkey)) $licensekey = $minfraudgeoipkey;
	else $licensekey = $minfraudkey;
	$query = "https://minfraud.maxmind.com/app/ipauth_http?l=" . $licensekey . "&ipaddr=" . $ipaddress;
	$url = parse_url($query);
	$host = $url["host"];
	$path = $url["path"] . "?" . $url["query"];
	$timeout = 1;
	$fp = fsockopen ($host, 80, $errno, $errstr, $timeout);
	if ($fp) {
		fputs ($fp, "GET $path HTTP/1.0\nHost: " . $host . "\n\n");
		while (!feof($fp)) $buf .= fgets($fp, 128);
		$lines = explode("\n", $buf);
		$data = $lines[count($lines)-1];
		fclose($fp);
		if (substr($data,0,11) == "proxyScore=") return str_replace("proxyScore=","",$data);
		else return false;
	} else return false;
}

function ashop_minfraudscore($ip,$email,$city,$region,$zip,$country,$invoice,$customerid,$shipaddress="",$shipcity="",$shipregion="",$shipzip="",$shipcountry="") {
	global $minfraudkey, $ashoppath, $countries;

	$emaildomain = substr($email,strpos($email,"@")+1);
	$emailmd5 = md5(strtolower($email));

	require_once("$ashoppath/includes/minfraud/CreditCardFraudDetection.php");

	if (strlen($country) > 2 || strlen($shipcountry) > 2) {
		if (empty($countries)) require_once("$ashoppath/admin/ashopconstants.inc.php");
		if (strlen($country) > 2) foreach ($countries as $shortcountry => $longcountry) if ($longcountry == $country) $country = $shortcountry;
		if (strlen($shipcountry) > 2) foreach ($countries as $shortcountry => $longcountry) if ($longcountry == $shipcountry) $shipcountry = $shortcountry;
	}

	$ccfs = new CreditCardFraudDetection;

	$h["license_key"] = $minfraudkey;
	$h["i"] = $ip;
	$h["city"] = $city;
	$h["region"] = $region;
	$h["postal"] = $zip;
	$h["country"] = $country;
	$h["domain"] = $emaildomain;
	$h["bin"] = "";			// bank identification number
	$h["forwardedIP"] = "";	// X-Forwarded-For or Client-IP HTTP Header
	$h["emailMD5"] = "$emailmd5";
	$h["usernameMD5"] = "";
	$h["passwordMD5"] = "";
	$h["binName"] = "";	// bank name
	$h["binPhone"] = "";	// bank customer service phone number on back of credit card
	$h["custPhone"] = "";		// Area-code and local prefix of customer phone number
	$h["requested_type"] = "";	// Which level (free, city, premium) of CCFD to use
	$h["shipAddr"] = $shipaddress;	// Shipping Address
	$h["shipCity"] = $shipcity;	// the City to Ship to
	$h["shipRegion"] = $shipregion;	// the Region to Ship to
	$h["shipPostal"] = $shipzip;	// the Postal Code to Ship to
	$h["shipCountry"] = $shipcountry;	// the country to Ship to
	$h["txnID"] = $invoice;			// Transaction ID
	$h["sessionID"] = $customerid;		// Session ID
	$h["accept_language"] = "";
	$h["user_agent"] = "";

	// $ccfs->isSecure = 0;
	$ccfs->timeout = 10;
	$ccfs->useDNS = 0;
	$ccfs->isSecure = 0;
	$ccfs->input($h);
	$ccfs->query();
	$h = $ccfs->output();

	return $h["riskScore"];

}

function ashop_productimages($productid,$imagenumber=0) {
	global $ashoppath;

	$imageinfo = array();
	$additionalimages = 0;
	if ($imagenumber > 0) $imagenumberpath = "/$imagenumber";
	else $imagenumberpath = "";

	if (is_numeric($productid) && is_dir("$ashoppath/prodimg/$productid$imagenumberpath")) {
		$findfile = opendir("$ashoppath/prodimg/$productid$imagenumberpath");
		if ($findfile) while (false !== ($foundfile = readdir($findfile))) {
			if (strtolower(substr($foundfile,-4)) == ".gif") $imageinfo["format"] = "gif";
			if (strtolower(substr($foundfile,-4)) == ".jpg") $imageinfo["format"] = "jpg";
			if (!is_dir("$ashoppath/prodimg/$productid$imagenumberpath/$foundfile") && substr($foundfile,0,2) != "m-" && substr($foundfile,0,2) != "p-" && substr($foundfile,0,2) != "t-" && !is_dir($foundfile) && (strtolower(substr($foundfile,-4)) == ".gif" || strtolower(substr($foundfile,-4)) == ".jpg")) $imageinfo["main"] = $foundfile;
			if (substr($foundfile,0,2) == "m-" && !is_dir($foundfile)) $imageinfo["mini"] = $foundfile;
			if (substr($foundfile,0,2) == "p-" && !is_dir($foundfile)) $imageinfo["product"] = $foundfile;
			if (substr($foundfile,0,2) == "t-" && !is_dir($foundfile)) $imageinfo["thumbnail"] = $foundfile;
			if (is_dir("$ashoppath/prodimg/$productid$imagenumberpath/$foundfile") && is_numeric($foundfile)) $imageinfo["additionalimages"]++;
		}
	}

	return $imageinfo;
}

function ashop_deleteimages($dir) {
	global $ashoppath;
	$dir = str_replace("\\","/",$dir);
	$dir = str_replace("../","",$dir);
	$dir = str_replace("./","",$dir);
	$imagepath = strtolower($ashoppath."/prodimg/");
	if (strtolower(substr($dir,0,strlen($imagepath))) != $imagepath || strtolower($dir) == strtolower($imagepath) || strlen($dir) < strlen($imagepath)+2) return false;
	else {
		$files = glob( $dir . '*', GLOB_MARK );
		if (!empty($files) && is_array($files)) foreach( $files as $file ){
			if( substr( $file, -1 ) == '/' || substr( $file, -1 ) == '\\' ) ashop_deleteimages( $file );
			else unlink( $file );
		}
		if (is_dir($dir)) rmdir( $dir );
	}   
}

function ashop_copydir($src,$dst) {
	if (!file_exists($src) || file_exists($dst)) return false;
	else {
		$dir = opendir($src);
		@mkdir($dst);
		while(false !== ( $file = readdir($dir)) ) {
			if (( $file != '.' ) && ( $file != '..' )) {
				if ( is_dir($src . '/' . $file) ) {
					ashop_copydir($src . '/' . $file,$dst . '/' . $file);
				}
				else {
					copy($src . '/' . $file,$dst . '/' . $file);
				}
			}
		}
		closedir($dir);
	}
} 

function ashop_saasu_getinventory($skucode) {
	global $saasuwsaccesskey, $saasufileid;

	$ch = curl_init();
	$saasuurl = "https://secure.saasu.com/webservices/rest/r1/InventoryItemList?WSAccessKey=$saasuwsaccesskey&FileUid=$saasufileid&CodeBeginsWith=$skucode";
	curl_setopt ($ch, CURLOPT_URL,$saasuurl);
	curl_setopt ($ch, CURLOPT_HEADER, 0);
	curl_setopt($ch, CURLOPT_HTTPGET, 1);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
	curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);
	$result = curl_exec ($ch);
	curl_close ($ch);

	$correctinventory = "nodata";
	if (strpos($result,"<inventoryItemListItem>")) {
		$resultarray = explode("<inventoryItemListItem>",$result);
		foreach($resultarray as $partnumber=>$xmlpart) {
			if (strpos($xmlpart,"stockOnHand")) {
				$subresultarray = explode("<stockOnHand>",$xmlpart);
				$subresultarray = explode("</stockOnHand>",$subresultarray[1]);
				if (!strpos($xmlpart,"<stockOnHand>")) $inventory = "0";
				else $inventory =  number_format($subresultarray[0],0);
			}
			if (strpos($xmlpart,"<code>")) {
				$subresultarray = explode("<code>",$xmlpart);
				$subresultarray = explode("</code>",$subresultarray[1]);
				$fullskucode = $subresultarray[0];
			}
			if ($fullskucode == $skucode) $correctinventory = $inventory;
		}
	}
	return $correctinventory;
}

function ashop_saasu_getitemuid($skucode) {
	global $saasuwsaccesskey, $saasufileid;

	$ch = curl_init();
	$saasuurl = "https://secure.saasu.com/webservices/rest/r1/InventoryItemList?WSAccessKey=$saasuwsaccesskey&FileUid=$saasufileid&CodeBeginsWith=$skucode";
	curl_setopt ($ch, CURLOPT_URL,$saasuurl);
	curl_setopt ($ch, CURLOPT_HEADER, 0);
	curl_setopt($ch, CURLOPT_HTTPGET, 1);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
	curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);
	$result = curl_exec ($ch);
	curl_close ($ch);

	$correctitemuid = "nodata";
	if (strpos($result,"<inventoryItemListItem>")) {
		$resultarray = explode("<inventoryItemListItem>",$result);
		foreach($resultarray as $partnumber=>$xmlpart) {
			if (strpos($xmlpart,"<inventoryItemUid>")) {
				$subresultarray = explode("<inventoryItemUid>",$xmlpart);
				$subresultarray = explode("</inventoryItemUid>",$subresultarray[1]);
				$itemuid = $subresultarray[0];
			}
			if (strpos($xmlpart,"<code>")) {
				$subresultarray = explode("<code>",$xmlpart);
				$subresultarray = explode("</code>",$subresultarray[1]);
				$fullskucode = $subresultarray[0];
			}
			if ($fullskucode == $skucode) $correctitemuid = $itemuid;
		}
	}
	return $correctitemuid;
}

function ashop_saasu_gettaxcodes() {
	global $saasuwsaccesskey, $saasufileid, $saasutaxcode;
	$taxcodeselectstring = "";

	$ch = curl_init();
	$saasuurl = "https://secure.saasu.com/webservices/rest/r1/TaxCodeList?WSAccessKey=$saasuwsaccesskey&FileUid=$saasufileid";
	curl_setopt ($ch, CURLOPT_URL,$saasuurl);
	curl_setopt ($ch, CURLOPT_HEADER, 0);
	curl_setopt($ch, CURLOPT_HTTPGET, 1);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
	curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);
	$result = curl_exec ($ch);
	curl_close ($ch);

	if (strpos($result,"<taxCodeListItem>")) {
		$resultarray = explode("<taxCodeListItem>",$result);
		foreach($resultarray as $partnumber=>$xmlpart) {
			if (strpos($xmlpart,"<code>")) {
				$subresultarray = explode("<code>",$xmlpart);
				$subresultarray = explode("</code>",$subresultarray[1]);
				$taxcode = $subresultarray[0];
				$taxcodeselectstring .= "<option value=\"$taxcode\"";
				if ($taxcode == $saasutaxcode) $taxcodeselectstring .= " selected";
				$taxcodeselectstring .= ">$taxcode</option>";
			}
		}
	}
	return $taxcodeselectstring;
}

function ashop_saasu_getbankaccounts() {
	global $saasuwsaccesskey, $saasufileid, $saasubankaccountid;
	$bankaccountselectstring = "";

	$ch = curl_init();
	$saasuurl = "https://secure.saasu.com/webservices/rest/r1/BankAccountList?WSAccessKey=$saasuwsaccesskey&FileUid=$saasufileid&IsActive=true";
	curl_setopt ($ch, CURLOPT_URL,$saasuurl);
	curl_setopt ($ch, CURLOPT_HEADER, 0);
	curl_setopt($ch, CURLOPT_HTTPGET, 1);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
	curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);
	$result = curl_exec ($ch);
	curl_close ($ch);

	if (strpos($result,"<bankAccountListItem>")) {
		$bankaccountselectstring = "<select name=\"nsaasubankaccountid\">";
		$resultarray = explode("<bankAccountListItem>",$result);
		foreach($resultarray as $partnumber=>$xmlpart) {
			if (strpos($xmlpart,"<bankAccountUid>")) {
				$subresultarray = explode("<bankAccountUid>",$xmlpart);
				$subresultarray = explode("</bankAccountUid>",$subresultarray[1]);
				$bankaccountid = $subresultarray[0];
			}
			if (strpos($xmlpart,"<displayName>")) {
				$subresultarray = explode("<displayName>",$xmlpart);
				$subresultarray = explode("</displayName>",$subresultarray[1]);
				$bankaccountname = $subresultarray[0];
			}
			if ($bankaccountid && $bankaccountname) {
				$bankaccountselectstring .= "<option value=\"$bankaccountid\"";
				if ($bankaccountid == $saasubankaccountid) $bankaccountselectstring .= " selected";
				$bankaccountselectstring .= ">$bankaccountname</option>";
			}
		}
		$bankaccountselectstring .= "</select>";
	}
	return $bankaccountselectstring;
}

function ashop_saasu_getaccounts($type) {
	global $saasuwsaccesskey, $saasufileid;
	$accountselectstring = "";
	$type = urlencode($type);

	$ch = curl_init();
	$saasuurl = "https://secure.saasu.com/webservices/rest/r1/TransactionCategoryList?WSAccessKey=$saasuwsaccesskey&FileUid=$saasufileid&IsActive=true&IsInbuilt=false&Type=$type";
	curl_setopt ($ch, CURLOPT_URL,$saasuurl);
	curl_setopt ($ch, CURLOPT_HEADER, 0);
	curl_setopt($ch, CURLOPT_HTTPGET, 1);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
	curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);
	$result = curl_exec ($ch);
	curl_close ($ch);

	if (strpos($result,"<transactionCategoryListItem>")) {
		$resultarray = explode("<transactionCategoryListItem>",$result);
		foreach($resultarray as $partnumber=>$xmlpart) {
			if (strpos($xmlpart,"<transactionCategoryUid>")) {
				$subresultarray = explode("<transactionCategoryUid>",$xmlpart);
				$subresultarray = explode("</transactionCategoryUid>",$subresultarray[1]);
				$accountid = $subresultarray[0];
			}
			if (strpos($xmlpart,"<name>")) {
				$subresultarray = explode("<name>",$xmlpart);
				$subresultarray = explode("</name>",$subresultarray[1]);
				$accountname = $subresultarray[0];
			}
			if ($accountid && $accountname) {
				$accountselectstring .= "<option value=\"$accountid\">$accountname</option>";
			}
		}
	}
	return $accountselectstring;
}

function ashop_saasu_postcontact($firstname, $lastname, $business, $vat, $email, $phone, $customerid, $address, $zip, $city, $state, $country) {
	global $saasuwsaccesskey, $saasufileid;

	$ch = curl_init();
	$saasuurl = "https://secure.saasu.com/webservices/rest/r1/ContactList?WSAccessKey=$saasuwsaccesskey&FileUid=$saasufileid&GivenName=$firstname&FamilyName=$lastname";
	curl_setopt ($ch, CURLOPT_URL,$saasuurl);
	curl_setopt ($ch, CURLOPT_HEADER, 0);
	curl_setopt($ch, CURLOPT_HTTPGET, 1);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
	curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);
	$result = curl_exec ($ch);
	curl_close ($ch);

	$existingcontactid = "";
	if (strpos($result,"<contactListItem>")) {
		$resultarray = explode("<contactListItem>",$result);
		foreach($resultarray as $partnumber=>$xmlpart) {
			if (strpos($xmlpart,"<emailAddress>")) {
				$subresultarray = explode("<emailAddress>",$xmlpart);
				$subresultarray = explode("</emailAddress>",$subresultarray[1]);
				$thisemail = $subresultarray[0];
			}
			if (strpos($xmlpart,"<street>")) {
				$subresultarray = explode("<street>",$xmlpart);
				$subresultarray = explode("</street>",$subresultarray[1]);
				$thisstreet = $subresultarray[0];
			}
			if (strpos($xmlpart,"<city>")) {
				$subresultarray = explode("<city>",$xmlpart);
				$subresultarray = explode("</city>",$subresultarray[1]);
				$thiscity = $subresultarray[0];
			}
			if (strpos($xmlpart,"<country>")) {
				$subresultarray = explode("<country>",$xmlpart);
				$subresultarray = explode("</country>",$subresultarray[1]);
				$thiscountry = $subresultarray[0];
			}
			if (strpos($xmlpart,"<contactUid>")) {
				$subresultarray = explode("<contactUid>",$xmlpart);
				$subresultarray = explode("</contactUid>",$subresultarray[1]);
				$contactuid = $subresultarray[0];
			}
			if (strpos($xmlpart,"<lastUpdatedUid>")) {
				$subresultarray = explode("<lastUpdatedUid>",$xmlpart);
				$subresultarray = explode("</lastUpdatedUid>",$subresultarray[1]);
				$lastupdateduid = $subresultarray[0];
			}
			if ($thisemail == $email || ($thisstreet == $street && $thiscity == $city && $thiscountry == $country)) {
				$existingcontactid = $contactuid;
				$existingcontactlastupdateduid = $lastupdateduid;
			}
		}
	}

	if ($existingcontactid) {
		// Update existing customer profile...
		$contactrequest = "<?xml version=\"1.0\" encoding=\"utf-8\"?>
		<tasks>
			<updateContact>
			<contact uid=\"$existingcontactid\" lastUpdatedUid=\"$existingcontactlastupdateduid\">
				<salutation></salutation>
				<givenName>$firstname</givenName>
				<familyName>$lastname</familyName>
				<organisationName>$business</organisationName>
				<organisationAbn>$vat</organisationAbn>
				<organisationPosition></organisationPosition>
				<email>$email</email>
				<mainPhone>$phone</mainPhone>
				<mobilePhone></mobilePhone>
				<contactID>$customerid</contactID>
				<tags></tags>
				<postalAddress>
					<street>$address</street>
					<city>$city</city>
					<postCode>$zip</postCode>
					<state>$state</state>
					<country>$country</country>
				</postalAddress>
				<otherAddress>
					<street></street>
					<city></city>
					<state></state>
					<country></country>
				</otherAddress>
				<isActive>true</isActive>
			</contact>
			</updateContact>
		</tasks>";

		$ch = curl_init();
		$saasuurl = "https://secure.saasu.com/webservices/rest/r1/Tasks?WSAccessKey=$saasuwsaccesskey&FileUid=$saasufileid";
		curl_setopt ($ch, CURLOPT_URL,$saasuurl);
		curl_setopt ($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, "$contactrequest");
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
		curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);
		$result = curl_exec ($ch);
		curl_close ($ch);
		return $existingcontactid;
	} else {
		// Store new customer profile...
		$contactrequest = "<?xml version=\"1.0\" encoding=\"utf-8\"?>
		<tasks>
			<insertContact>
			<contact uid=\"0\">
				<salutation></salutation>
				<givenName>$firstname</givenName>
				<familyName>$lastname</familyName>
				<organisationName>$business</organisationName>
				<organisationAbn>$vat</organisationAbn>
				<organisationPosition></organisationPosition>
				<email>$email</email>
				<mainPhone>$phone</mainPhone>
				<mobilePhone></mobilePhone>
				<contactID>$customerid</contactID>
				<tags></tags>
				<postalAddress>
					<street>$address</street>
					<city>$city</city>
					<postCode>$zip</postCode>
					<state>$state</state>
					<country>$country</country>
				</postalAddress>
				<otherAddress>
					<street></street>
					<city></city>
					<state></state>
					<country></country>
				</otherAddress>
				<isActive>true</isActive>
				<acceptDirectDeposit>false</acceptDirectDeposit>
				<directDepositAccountName></directDepositAccountName>
				<directDepositBsb></directDepositBsb>
				<directDepositAccountNumber></directDepositAccountNumber>
				<acceptCheque>false</acceptCheque>
				<customField1></customField1>
				<customField2></customField2>
				<twitterID></twitterID>
				<skypeID></skypeID>
			</contact>
			</insertContact>
		</tasks>";

		$ch = curl_init();
		$saasuurl = "https://secure.saasu.com/webservices/rest/r1/Tasks?WSAccessKey=$saasuwsaccesskey&FileUid=$saasufileid";
		curl_setopt ($ch, CURLOPT_URL,$saasuurl);
		curl_setopt ($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, "$contactrequest");
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
		curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);
		$result = curl_exec ($ch);
		curl_close ($ch);

		if (strpos($result,"insertedEntityUid")) {
			$resultarray = explode("insertedEntityUid=\"",$result);
			$resultarray = explode("\" lastUpdatedUid",$resultarray[1]);
			$newcontactid = $resultarray[0];
			return $newcontactid;
		}
	}
	return "nodata";
}

function ashop_saasu_postinvoice($contactid, $shippingcontactid, $date, $amount, $invoice) {
	global $saasuwsaccesskey, $saasufileid, $saasuitemslist, $saasubankaccountid, $ashopname, $ashopemail;

	$invoicerequest = "<?xml version=\"1.0\" encoding=\"utf-16\"?>
	<tasks xmlns:xsi=\"http://www.w3.org/2001/XMLSchema-instance\" xmlns:xsd=\"http://www.w3.org/2001/XMLSchema\">
		<insertInvoice emailToContact=\"false\">
		<invoice uid=\"0\">
			<transactionType>S</transactionType>
			<date>$date</date>
			<contactUid>$contactid</contactUid>
			<shipToContactUid>$shippingcontactid</shipToContactUid>
			<folderUid>0</folderUid>
			<summary>$ashopname Sale</summary>
			<notes>From AShop</notes>
			<requiresFollowUp>false</requiresFollowUp>
			<dueOrExpiryDate>$date</dueOrExpiryDate>
			<layout>I</layout>
			<status>I</status>
			<invoiceNumber>$invoice</invoiceNumber>
			<purchaseOrderNumber></purchaseOrderNumber>
			<invoiceItems>
				$saasuitemslist
			</invoiceItems>
			<quickPayment>
				<datePaid>$date</datePaid>
				<dateCleared>$date</dateCleared>
				<bankedToAccountUid>$saasubankaccountid</bankedToAccountUid>
				<amount>$amount</amount>
				<reference>$invoice</reference>
			</quickPayment>
			<isSent>true</isSent>
		</invoice>
		<createAsAdjustmentNote>false</createAsAdjustmentNote>
		</insertInvoice>
	</tasks>";

	$ch = curl_init();
	$saasuurl = "https://secure.saasu.com/webservices/rest/r1/Tasks?WSAccessKey=$saasuwsaccesskey&FileUid=$saasufileid";
	curl_setopt ($ch, CURLOPT_URL,$saasuurl);
	curl_setopt ($ch, CURLOPT_HEADER, 0);
	curl_setopt($ch, CURLOPT_POST, 1);
	curl_setopt($ch, CURLOPT_POSTFIELDS, "$invoicerequest");
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
	curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);
	$result = curl_exec ($ch);
	if (!strpos($result, "insertedEntityUid")) {
		if (strpos($result,"<message>")) {
			$resultarray = explode("<message>",$result);
			$resultarray = explode("</message>",$resultarray[1]);
			$saasumessage = $resultarray[0];
			@ashop_mail("$ashopemail","SAASU Invoice Error", "When sending the invoice $invoice to SAASU the following error message was returned: $saasumessage\r\n\r\nYou should check this order manually.");
		}
	}
	curl_close ($ch);
}

function ashop_saasu_additem($productname, $productprice, $inventory, $skucode, $vendorcost, $assetaccount, $incomeaccount, $costofsalesaccount, $purchasetaxcode, $saletaxcode) {
	global $saasuwsaccesskey, $saasufileid, $timezoneoffset, $taxpercentage;

	$inventoryvalue = $productprice*$inventory;

	$itemrequest = "<?xml version=\"1.0\" encoding=\"utf-8\"?>
	<tasks>
		<insertInventoryItem>
		<!-- NOTE: Replaced all Uids with the ones from your file. -->
		<inventoryItem uid=\"0\">
			<code>$skucode</code>
			<description>$productname</description>
			<isActive>true</isActive>
			<notes></notes>
			<isInventoried>true</isInventoried>
			<assetAccountUid>$assetaccount</assetAccountUid>
			<stockOnHand>$inventory</stockOnHand>
			<currentValue>$inventoryvalue</currentValue>
			<isBought>true</isBought>
			<purchaseExpenseAccountUid></purchaseExpenseAccountUid>
			<purchaseTaxCode>$purchasetaxcode</purchaseTaxCode>
			<minimumStockLevel></minimumStockLevel>
			<primarySupplierContactUid></primarySupplierContactUid>
			<primarySupplierItemCode></primarySupplierItemCode>
			<defaultReOrderQuantity></defaultReOrderQuantity>
			<buyingPrice>$vendorcost</buyingPrice>
			<isBuyingPriceIncTax>true</isBuyingPriceIncTax>
			<isSold>true</isSold>
			<saleIncomeAccountUid>$incomeaccount</saleIncomeAccountUid>
			<saleTaxCode>$saletaxcode</saleTaxCode>
			<saleCoSAccountUid>$costofsalesaccount</saleCoSAccountUid>
			<sellingPrice>$productprice</sellingPrice>
			<isSellingPriceIncTax>true</isSellingPriceIncTax>
		</inventoryItem>
		</insertInventoryItem>
	</tasks>";

	$ch = curl_init();
	$saasuurl = "https://secure.saasu.com/webservices/rest/r1/Tasks?WSAccessKey=$saasuwsaccesskey&FileUid=$saasufileid";
	curl_setopt ($ch, CURLOPT_URL,$saasuurl);
	curl_setopt ($ch, CURLOPT_HEADER, 0);
	curl_setopt($ch, CURLOPT_POST, 1);
	curl_setopt($ch, CURLOPT_POSTFIELDS, "$itemrequest");
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
	curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);
	$result = curl_exec ($ch);
	curl_close ($ch);
	if (!strpos($result, "insertedEntityUid")) {
		if (strpos($result,"<message>")) {
			$resultarray = explode("<message>",$result);
			$resultarray = explode("</message>",$resultarray[1]);
			$saasumessage = $resultarray[0];
			@ashop_mail("$ashopemail","SAASU Item Export Error", "When exporting the item $productname to SAASU the following error message was returned: $saasumessage\r\n\r\nYou should add this item manually.");
		}
	} else {
		$resultarray = explode("insertedEntityUid=\"",$result);
		$resultarray = explode("\" lastUpdatedUid",$resultarray[1]);
		$itemuid = $resultarray[0];
		//$taxmultiplier = (100-$taxpercentage)/100;
		//$priceexcltax = $productprice*$taxmultiplier;
		//$totalpriceexcltax = $inventory*$priceexcltax;
		$date = date("Y-m-d", time()+$timezoneoffset);
		$inventoryadjustmentrequest = "<?xml version=\"1.0\" encoding=\"utf-16\"?>
		<tasks xmlns:xsi=\"http://www.w3.org/2001/XMLSchema-instance\" xmlns:xsd=\"http://www.w3.org/2001/XMLSchema\">
			<insertInventoryAdjustment>
				<inventoryAdjustment uid=\"0\">
					<date>$date</date>
					<tags>AShop, shopping cart, $skucode</tags>
					<summary>$productname - from shopping cart</summary>
					<notes>Initial inventory adjustment from AShop</notes>
					<requiresFollowUp>false</requiresFollowUp>
					<items>
						<item>
							<quantity>$inventory</quantity>
							<inventoryItemUid>$itemuid</inventoryItemUid>
							<accountUid>$assetaccount</accountUid>
							<unitPriceExclTax>0</unitPriceExclTax>
							<totalPriceExclTax>0</totalPriceExclTax>
						</item>
					</items>
				</inventoryAdjustment>
			</insertInventoryAdjustment>
		</tasks>";
		
		$ch = curl_init();
		$saasuurl = "https://secure.saasu.com/webservices/rest/r1/Tasks?WSAccessKey=$saasuwsaccesskey&FileUid=$saasufileid";
		curl_setopt ($ch, CURLOPT_URL,$saasuurl);
		curl_setopt ($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, "$inventoryadjustmentrequest");
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
		curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);
		$result = curl_exec ($ch);
		curl_close ($ch);
		if (!strpos($result, "insertedEntityUid")) {
			if (strpos($result,"<message>")) {
				$resultarray = explode("<message>",$result);
				$resultarray = explode("</message>",$resultarray[1]);
				$saasumessage = $resultarray[0];
				@ashop_mail("$ashopemail","SAASU Item Export Error", "When exporting the item $productname to SAASU the following inventory adjustment error message was returned: $saasumessage\r\n\r\nYou should adjust the inventory of this item manually.");
			}
		} else return TRUE;
	}
}

function ashop_saasu_adjustinventory($productname, $inventory, $itemuid, $assetaccount) {
	global $saasuwsaccesskey, $saasufileid, $timezoneoffset, $taxpercentage;

	$date = date("Y-m-d", time()+$timezoneoffset);
	$inventoryadjustmentrequest = "<?xml version=\"1.0\" encoding=\"utf-16\"?>
		<tasks xmlns:xsi=\"http://www.w3.org/2001/XMLSchema-instance\" xmlns:xsd=\"http://www.w3.org/2001/XMLSchema\">
			<insertInventoryAdjustment>
				<inventoryAdjustment uid=\"0\">
					<date>$date</date>
					<tags>AShop, shopping cart, $skucode</tags>
					<summary>$productname - from shopping cart</summary>
					<notes>Initial inventory adjustment from AShop</notes>
					<requiresFollowUp>false</requiresFollowUp>
					<items>
						<item>
							<quantity>$inventory</quantity>
							<inventoryItemUid>$itemuid</inventoryItemUid>
							<accountUid>$assetaccount</accountUid>
							<unitPriceExclTax>0</unitPriceExclTax>
							<totalPriceExclTax>0</totalPriceExclTax>
						</item>
					</items>
				</inventoryAdjustment>
			</insertInventoryAdjustment>
		</tasks>";
		
	$ch = curl_init();
	$saasuurl = "https://secure.saasu.com/webservices/rest/r1/Tasks?WSAccessKey=$saasuwsaccesskey&FileUid=$saasufileid";
	curl_setopt ($ch, CURLOPT_URL,$saasuurl);
	curl_setopt ($ch, CURLOPT_HEADER, 0);
	curl_setopt($ch, CURLOPT_POST, 1);
	curl_setopt($ch, CURLOPT_POSTFIELDS, "$inventoryadjustmentrequest");
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
	curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);
	$result = curl_exec ($ch);
	curl_close ($ch);
	if (!strpos($result, "insertedEntityUid")) {
		if (strpos($result,"<message>")) {
			$resultarray = explode("<message>",$result);
			$resultarray = explode("</message>",$resultarray[1]);
			$saasumessage = $resultarray[0];
			@ashop_mail("$ashopemail","SAASU Item Export Error", "When adjusting the SAASU inventory of the item $productname the following error message was returned: $saasumessage\r\n\r\nYou should adjust the inventory of this item manually.");
		}
	} else return TRUE;
}

function ashop_bitlyshorten($url) {
	$ch = curl_init();
	$bitlyurl = "http://api.bit.ly/shorten?version=2.0.1&longUrl=$url&login=ashopsoftware&apiKey=R_232dbc0723b0129d920e44605aafed5f";
	curl_setopt ($ch, CURLOPT_URL,$bitlyurl);
	curl_setopt ($ch, CURLOPT_HEADER, 0);
	curl_setopt($ch, CURLOPT_HTTPGET, 1);
	curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);
	$result = curl_exec ($ch);
	curl_close ($ch);
	if (strpos($result,"shortUrl")) {
		$resultarray = explode("\"shortUrl\": \"",$result);
		$resultarray = explode("\"}",$resultarray[1]);
		$shorturl = $resultarray[0];
	}
	if(strpos($shorturl,"t.ly")) return $shorturl;
	else return FALSE;
}

// Reverse conversion of special characters to html entities...
function un_html($textstring,$keepdotsandcommas=0) {
	if (!$keepdotsandcommas) {
		$textstring = str_replace(",","",$textstring);
		$textstring = str_replace(".","",$textstring);
	}
	$trans = get_html_translation_table(HTML_ENTITIES, ENT_QUOTES);
	$trans = array_flip($trans);
	$original = strtr($textstring, $trans);
	return $original;
}

// Remove content-type meta header...
function demetafy($textstring) {
	$checklowercase = FALSE;
	$metastop = 0;
	while (!$checklowercase) {
		$metastart = strpos($textstring, "<meta", $metastop);
		if ($metastart === FALSE) $checklowercase = TRUE;
		else {
			$metastop = strpos($textstring, ">", $metastart)+1;
			$metatag = substr($textstring, $metastart, $metastop-$metastart);
			if (stristr($metatag, "charset=")) $textstring = substr_replace($textstring, "",  $metastart, $metastop-$metastart);
		}
	}
	$checkuppercase = FALSE;
	$metastop = 0;
	while (!$checkuppercase) {
		$metastart = strpos($textstring, "<META", $metastop);
		if ($metastart === FALSE) $checkuppercase = TRUE;
		else {
			$metastop = strpos($textstring, ">", $metastart)+1;
			$metatag = substr($textstring, $metastart, $metastop-$metastart);
			if (stristr($metatag, "charset=")) $textstring = substr_replace($textstring, "",  $metastart, $metastop-$metastart);
		}
	}
	return $textstring;
}

// Extract template from HeliCMS...
function ashop_helicmstemplate() {
	global $cmsurl, $ashoppath, $ashopspath;
	
	$updatecache = FALSE;
	if (file_exists("$ashopspath/updates/helitemplate.html")) {
		$lastcached = filemtime("$ashopspath/updates/helitemplate.html");
		$now = time();
		if ($now - $lastcached > 300) $updatecache = TRUE;
	}

	if (!file_exists("$ashopspath/updates/helitemplate.html") || $updatecache) {
		$ch = curl_init();
		$fullhelicmsurl = "$cmsurl/index.php?page=1";
		curl_setopt ($ch, CURLOPT_URL,$fullhelicmsurl);
		curl_setopt ($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_HTTPGET, 1);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
		curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);
		$template = curl_exec ($ch);
		curl_close ($ch);

		$template = str_replace("href=\"css/","href=\"$cmsurl/css/",$template);
		$template = str_replace("src=\"js/","href=\"$cmsurl/js/",$template);
		$template = str_replace("src=\"images/","href=\"$cmsurl/images/",$template);
		$template = str_replace("url(./images/","url($cmsurl/images/",$template);
		$template = str_replace("href=\"./","href=\"$cmsurl/",$template);
		$template = str_replace("href=\"feeds.php","href=\"$cmsurl/feeds.php",$template);
		$template = str_replace("href=\"site_map.php","href=\"$cmsurl/site_map.php",$template);
		$template = str_replace("</head>","<link rel=\"stylesheet\" href=\"<!-- AShopcss -->\" type=\"text/css\" />\r\n</head>",$template);

		$templatearray = explode("<div class=\"pagecontent\">",$template);
		$header = $templatearray[0]."<div class=\"pagecontent\">";
		$footersearch1 = strpos($templatearray[1],"<div");
		$footersearch2 = strpos($templatearray[1],"</div>");
		while ($footersearch1 < $footersearch2) {
			$offset1 = $footersearch1+4;
			$offset2 = $footersearch1+6;
			$footersearch1 = strpos($templatearray[1],"<div");
			$footersearch2 = strpos($templatearray[1],"</div>");
		}
		$footer = substr($templatearray[1],$footersearch2);
		$fulltemplate = "$header
		<!-- AShopstart -->
		<!-- AShopend -->
		$footer";
		$fp = fopen ("$ashopspath/updates/helitemplate.html","w");
		fwrite($fp, $fulltemplate);
		fclose($fp);
	} else {
		$fp = fopen ("$ashopspath/updates/helitemplate.html","r");
		while (!feof ($fp)) $fulltemplate .= fgets($fp, 4096);
		fclose($fp);
	}


	return $fulltemplate;
}

// Parse start and end tags...
function ashop_parsetags($text,$tag,$endtag,$replacement) {
	$timeout = 0;
	while (substr_count($text,$tag) && substr_count($text,$endtag)) {
		if ($timeout > 50) break;
		$start = strpos($text,$tag);
		$end = strpos($text,$endtag)+strlen($endtag);
		$length = $end-$start;
		$text = substr_replace($text,$replacement,$start,$length);
		$timeout++;
	}
	return $text;
}

// Generate and POST JSON affiliate notification...
function ashop_notifyaffiliate($affiliatepass,$affiliateurl,$customerid,$orderid,$commission,$description) {
	global $ashoppath;
	$affiliatekey = md5($affiliatepass.$customerid.$orderid);
	$description = str_replace("\"","",$description);
	$description = str_replace("\r\n","",$description);
	$description = str_replace("\n\r","",$description);
	$description = str_replace("\n","",$description);
	$description = str_replace("\t","",$description);
	$description = str_replace("\r","",$description);
	$description = str_replace("{","",$description);
	$description = str_replace("}","",$description);
	$jsonnotification = "notification={\n\"api_key\": \"$affiliatekey\",\n\"customer_number\": \"$customerid\",\n\"credit\": \"$commission\",\n\"receipt\": \"$orderid\",\n\"items\": \"$description\"\n}";
	$ch = curl_init();
	if (file_exists("$ashoppath/admin/curl.inc.php")) include "$ashoppath/admin/curl.inc.php";
	curl_setopt ($ch, CURLOPT_URL,$affiliateurl);
	curl_setopt($ch, CURLOPT_POST, 1);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonnotification);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
	curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);
	$notification = curl_exec ($ch);
	$error = curl_error($ch);
	$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
	$retries = 0;
	while ($httpCode != 200 && $retries < 3) {
		$notification = curl_exec ($ch);
		$error = curl_error($ch);
		$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		$retries++;
	}
	curl_close ($ch);
	if ($httpCode == 200) return TRUE;
	else return FALSE;
}

// Parse affiliate tags for page replication...
function ashop_parseaffiliatetags($text) {
	global $db, $affiliate, $ashopname, $ashopurl, $bgcolor, $pageheader, $shop;
	// Parse basic template tags first...
	$text = str_replace("<!-- AShopname -->", $ashopname, $text);
	$text = str_replace("<!-- AShoplogo -->", "<img src=\"$ashopurl/images/logo.gif\" alt=\"$ashopname\" border=\"0\">", $text);
	$text = str_replace("<!-- AShopbgcolor -->", $bgcolor, $text);
	$text = str_replace("<!-- AShopmemberheader -->", $pageheader, $text);
	if ($shop > 1) $$text = str_replace("<!-- AShopcss -->", "includes/ashopcss.inc.php?shop=$shop", $text);
	else if ($shop < 0) $text = str_replace("<!-- AShopcss -->", "../includes/ashopcss.inc.php?shop=$shop", $text);
	else $text = str_replace("<!-- AShopcss -->", "includes/ashopcss.inc.php", $text);
	if ((empty($affiliate) || !is_numeric($affiliate)) && !empty($_COOKIE["affiliate"]) && is_numeric($_COOKIE["affiliate"])) $affiliate = $_COOKIE["affiliate"];
	if (!empty($affiliate) && is_numeric($affiliate) && !empty($db)) {
		$result = @mysqli_query($db, "SELECT * FROM affiliate WHERE affiliateid='$affiliate'");
		if (@mysqli_num_rows($result)) {
			$row = @mysqli_fetch_array($result);
			$firstname = $row["firstname"];
			$lastname = $row["lastname"];
			$user = $row["user"];
			$business = $row["business"];
			$email = $row["email"];
			$paypalid = $row["paypalid"];
			$address = $row["address"];
			$state = $row["state"];
			$zip = $row["zip"];
			$city = $row["city"];
			$country = $row["country"];
			$signedup = $row["signedup"];
			$url = $row["url"];
			$phone = $row["phone"];
			$referralcode = $row["referralcode"];
			$extrainfo = $row["extrainfo"];
			$text = ashop_parsetags($text,"<!-- AShop_affiliate_firstname -->","<!-- /AShop_affiliate_firstname -->",$firstname);
			$text = ashop_parsetags($text,"<!-- AShop_affiliate_lastname -->","<!-- /AShop_affiliate_lastname -->",$lastname);
			$text = ashop_parsetags($text,"<!-- AShop_affiliate_user -->","<!-- /AShop_affiliate_user -->",$user);
			$text = ashop_parsetags($text,"<!-- AShop_affiliate_id -->","<!-- /AShop_affiliate_id -->",$affiliate);
			$text = ashop_parsetags($text,"<!-- AShop_affiliate_business -->","<!-- /AShop_affiliate_business -->",$business);
			$text = ashop_parsetags($text,"<!-- AShop_affiliate_email -->","<!-- /AShop_affiliate_email -->",$email);
			$text = ashop_parsetags($text,"<!-- AShop_affiliate_paypalid -->","<!-- /AShop_affiliate_paypalid -->",$paypalid);
			$text = ashop_parsetags($text,"<!-- AShop_affiliate_address -->","<!-- /AShop_affiliate_address -->",$address);
			$text = ashop_parsetags($text,"<!-- AShop_affiliate_state -->","<!-- /AShop_affiliate_state -->",$state);
			$text = ashop_parsetags($text,"<!-- AShop_affiliate_zip -->","<!-- /AShop_affiliate_zip -->",$zip);
			$text = ashop_parsetags($text,"<!-- AShop_affiliate_city -->","<!-- /AShop_affiliate_city -->",$city);
			$text = ashop_parsetags($text,"<!-- AShop_affiliate_country -->","<!-- /AShop_affiliate_country -->",$country);
			$text = ashop_parsetags($text,"<!-- AShop_affiliate_signedup -->","<!-- /AShop_affiliate_signedup -->",$signedup);
			$text = ashop_parsetags($text,"<!-- AShop_affiliate_url -->","<!-- /AShop_affiliate_url -->",$url);
			$text = ashop_parsetags($text,"<!-- AShop_affiliate_phone -->","<!-- /AShop_affiliate_phone -->",$phone);
			$text = ashop_parsetags($text,"<!-- AShop_affiliate_referralcode -->","<!-- /AShop_affiliate_referralcode -->",$referralcode);
			$text = ashop_parsetags($text,"<!-- AShop_affiliate_extrainfo -->","<!-- /AShop_affiliate_extrainfo -->",$extrainfo);

			// Parse custom tags...
			$tagresult = @mysqli_query($db, "SELECT * FROM affiliatetags");
			if (@mysqli_num_rows($tagresult)) while ($tagrow = @mysqli_fetch_array($tagresult)) {
				$tagid = $tagrow["affiliatetagid"];
				$starttag = "<!-- AShop_affiliate_".$tagrow["tagname"]." -->";
				$endtag = str_replace("AShop_","/AShop_",$starttag);
				$affinforesult = @mysqli_query($db, "SELECT * FROM affiliatetaginfo WHERE affiliateid='$affiliate' AND affiliatetagid='$tagid'");
				if (@mysqli_num_rows($affinforesult)) {
					$affinfo = @mysqli_result($affinforesult,0,"value");
					$text = ashop_parsetags($text,"$starttag","$endtag",$affinfo);
				}
			}
		}
	} else {
		$text = str_replace("<!-- AShop_affiliate_firstname -->","",$text);
		$text = str_replace("<!-- /AShop_affiliate_firstname -->","",$text);
		$text = str_replace("<!-- AShop_affiliate_lastname -->","",$text);
		$text = str_replace("<!-- /AShop_affiliate_lastname -->","",$text);
		$text = str_replace("<<!-- AShop_affiliate_user -->","",$text);
		$text = str_replace("<!-- /AShop_affiliate_user -->","",$text);
		$text = str_replace("<<!-- AShop_affiliate_id -->","",$text);
		$text = str_replace("<!-- /AShop_affiliate_id -->","",$text);
		$text = str_replace("<!-- AShop_affiliate_business -->","",$text);
		$text = str_replace("<!-- /AShop_affiliate_business -->","",$text);
		$text = str_replace("<!-- AShop_affiliate_email -->","",$text);
		$text = str_replace("<!-- /AShop_affiliate_email -->","",$text);
		$text = str_replace("<!-- AShop_affiliate_paypalid -->","",$text);
		$text = str_replace("<!-- /AShop_affiliate_paypalid -->","",$text);
		$text = str_replace("<!-- AShop_affiliate_address -->","",$text);
		$text = str_replace("<!-- /AShop_affiliate_address -->","",$text);
		$text = str_replace("<!-- AShop_affiliate_state -->","",$text);
		$text = str_replace("<!-- /AShop_affiliate_state -->","",$text);
		$text = str_replace("<!-- AShop_affiliate_zip -->","",$text);
		$text = str_replace("<!-- /AShop_affiliate_zip -->","",$text);
		$text = str_replace("<!-- AShop_affiliate_city -->","",$text);
		$text = str_replace("<!-- /AShop_affiliate_city -->","",$text);
		$text = str_replace("<!-- AShop_affiliate_country -->","",$text);
		$text = str_replace("<!-- /AShop_affiliate_country -->","",$text);
		$text = str_replace("<!-- AShop_affiliate_signedup -->","",$text);
		$text = str_replace("<!-- /AShop_affiliate_signedup -->","",$text);
		$text = str_replace("<!-- AShop_affiliate_url -->","",$text);
		$text = str_replace("<!-- /AShop_affiliate_url -->","",$text);
		$text = str_replace("<!-- AShop_affiliate_phone -->","",$text);
		$text = str_replace("<!-- /AShop_affiliate_phone -->","",$text);
		$text = str_replace("<!-- AShop_affiliate_referralcode -->","",$text);
		$text = str_replace("<!-- /AShop_affiliate_referralcode -->","",$text);
		$text = str_replace("<!-- AShop_affiliate_extrainfo -->","",$text);
		$text = str_replace("<!-- /AShop_affiliate_extrainfo -->","",$text);
		$text = str_replace("","",$text);
		$text = str_replace("","",$text);

		// Remove custom tags...
		if (!empty($db) && is_resource($db)) {
			$tagresult = @mysqli_query($db, "SELECT * FROM affiliatetags");
			if (@mysqli_num_rows($tagresult)) while ($tagrow = @mysqli_fetch_array($tagresult)) {
				$tagid = $tagrow["affiliatetagid"];
				$starttag = "<!-- AShop_affiliate_".$tagrow["tagname"]." -->";
				$endtag = str_replace("AShop_","/AShop_",$starttag);
				$text = str_replace($starttag,"",$text);
				$text = str_replace($endtag,"",$text);
			}
		}
	}
	return($text);
}
	
// Show the portion of a template that comes before <!-- AShopstart -->...
function ashop_showtemplateheader($templatepath) {
	global $db, $font, $fontsize1, $fontsize2, $fontsize3, $tablesize1, $tablesize2, $ashopname, $ashoptitle, $ashopmetakeywords, $ashopmetadescription, $shop, $ashopurl, $ashopsurl, $ashoppath, $bgcolor, $textcolor, $linkcolor, $ashopuser, $pageheader, $returntotoplink, $cmsurl, $basket, $ashopcurrency, $databaseserver, $databaseuser, $lang, $defaultlanguage, $ashopimage, $device, $ashoptheme, $displaywithtax, $taxpercentage, $decimalchar, $thousandchar, $ashopnewsfeed, $enableproductcount, $curr, $hideemptycategories, $showdecimals;
	if (!isset($lang) || !preg_match("/[a-z]+/", $lang) || strlen($lang) > 2) $lang = $defaultlanguage;
	if (!$ashoptitle) $ashoptitle = $ashopname;
	$template = "";
	if ($ashoptitle) $ashoptitle = strip_tags($ashoptitle);
	if ($ashopmetakeywords) $ashopmetakeywords = strip_tags($ashopmetakeywords);
	if ($ashopmetadescription) $ashopmetadescription = strip_tags($ashopmetadescription);
	if ($cmsurl) $template = ashop_helicmstemplate();
	if (file_exists("$templatepath") || $template) {
		if (!$template) {
			$fp = fopen ("$templatepath","r");
			while (!feof ($fp)) $template .= fgets($fp, 4096);
			fclose($fp);
		}
		$templateheader = explode("<!-- AShopstart -->", $template);
		if (count($templateheader) < 2) {
			if (defined("CHARSET")) echo "<html><head><title>$ashopname</title>\n".CHARSET."</head>";
			else echo "<html><head><title>$ashopname</title><link rel=\"stylesheet\" href=\"$ashopurl/includes/ashopcss.inc.php\" type=\"text/css\"></head>";
			echo "<body bgcolor=\"$bgcolor\" text=\"$textcolor\" link=\"$linkcolor\" alink=\"$linkcolor\" vlink=\"$linkcolor\"><center><p><img src=\"$ashopurl/images/logo.gif\"></p><p><font face=\"$font\" size=\"2\" color=\"#900000\"><b>Error! Incorrectly formatted template file!</b></font></p>";
		} else {
			if (strpos($templateheader[0],"<!-- AShopcart -->") !== false) {
				ob_start();
				// Get subtotal...
				$layout = 4;
				$customerlogin = "off";
				include "includes/topform.inc.php";
				echo "<br><br><div align=\"center\">";
				// Get shopping cart buttons...
				$layout = 5;
				include "includes/topform.inc.php";
				echo "</div>";
				$carthtml = ob_get_contents();
				ob_end_clean();
				$templateheader[0] = str_replace("<!-- AShopcart -->", $carthtml, $templateheader[0]);
			}
			// Create menu...
			if (strpos($templateheader[0],"<!-- AShopmenu -->") !== false) {
				ob_start();
				$tempdir = getcwd();
				// Get menu items...
				chdir($ashoppath);
				include "includes/menu.inc.php";
				chdir($tempdir);
				$menuhtml = ob_get_contents();
				ob_end_clean();
				$templateheader[0] = str_replace("<!-- AShopmenu -->", $menuhtml, $templateheader[0]);
			}
			// Create standard categories list...
			if (strpos($templateheader[0],"<!-- AShopcategories -->") !== false) {
				ob_start();
				$tempdir = getcwd();
				// Get categories...
				$catalog = "index.php";
				chdir($ashoppath);
				include "includes/categories.inc.php";
				chdir($tempdir);
				$categorieshtml = ob_get_contents();
				ob_end_clean();
				$templateheader[0] = str_replace("<!-- AShopcategories -->", $categorieshtml, $templateheader[0]);
			}
			// Create unordered categories list for CSS styling...
			if (strpos($templateheader[0],"<!-- AShopcategorieslist -->") !== false) {
				ob_start();
				$tempdir = getcwd();
				// Get categories...
				$catalog = "index.php";
				$layout = 2;
				chdir($ashoppath);
				include "includes/categories.inc.php";
				$categorieshtml = ob_get_contents();
				ob_end_clean();
				$templateheader[0] = str_replace("<!-- AShopcategorieslist -->", $categorieshtml, $templateheader[0]);
			}
			// Create shopping mall members list...
			if (strpos($templateheader[0],"<!-- AShopmembers -->") !== false) {
				ob_start();
				$tempdir = getcwd();
				// Get shops...
				$layout = 1;
				chdir($ashoppath);
				include "includes/shops.inc.php";
				chdir($tempdir);
				$shopshtml = ob_get_contents();
				ob_end_clean();
				$templateheader[0] = str_replace("<!-- AShopmembers -->", $shopshtml, $templateheader[0]);
			}
			// Create manufacturers list...
			if (strpos($templateheader[0],"<!-- AShopmanufacturers -->") !== false) {
				ob_start();
				$tempdir = getcwd();
				// Get manufacturers...
				$catalog = "index.php";
				$layout = 1;
				chdir($ashoppath);
				include "includes/manufacturers.inc.php";
				chdir($tempdir);
				$manufacturershtml = ob_get_contents();
				ob_end_clean();
				$templateheader[0] = str_replace("<!-- AShopmanufacturers -->", $manufacturershtml, $templateheader[0]);
			}
			// Create unordered categories list for CSS styling...
			if (strpos($templateheader[0],"<!-- AShopmanufacturerslist -->") !== false) {
				ob_start();
				$tempdir = getcwd();
				// Get categories...
				$catalog = "index.php";
				$layout = 2;
				chdir($ashoppath);
				include "includes/manufacturers.inc.php";
				$manufacturershtml = ob_get_contents();
				ob_end_clean();
				$templateheader[0] = str_replace("<!-- AShopmanufacturerslist -->", $manufacturershtml, $templateheader[0]);
			}
			// Create top list and latest additions...
			if (strpos($templateheader[0],"<!-- AShoptopandlatest -->") !== false) {
				$redirect="$ashopurl/index.php";
				ob_start();
				$tempdir = getcwd();
				chdir($ashoppath);
				include "includes/toplist.inc.php";
				chdir($tempdir);
				$resulthtml = ob_get_contents();
				ob_end_clean();
				$templateheader[0] = str_replace("<!-- AShoptopandlatest -->", $resulthtml, $templateheader[0]);
			}
			// Create customer profile links...
			if (strpos($templateheader[0],"<!-- AShopcustomerlinks -->") !== false) {
				$layout = 6;
				ob_start();
				$tempdir = getcwd();
				chdir($ashoppath);
				include "includes/topform.inc.php";
				chdir($tempdir);
				$resulthtml = ob_get_contents();
				ob_end_clean();
				$templateheader[0] = str_replace("<!-- AShopcustomerlinks -->", $resulthtml, $templateheader[0]);
			}
			// Create subtotal box...
			if (strpos($templateheader[0],"<!-- AShopsubtotal -->") !== false) {
				$shop = $currentshop;
				$layout = 4;
				$customerlogin = "off";
				ob_start();
				$tempdir = getcwd();
				chdir($ashoppath);
				include "includes/topform.inc.php";
				chdir($tempdir);
				$resulthtml = ob_get_contents();
				ob_end_clean();
				$templateheader[0] = str_replace("<!-- AShopsubtotal -->", $resulthtml, $templateheader[0]);
			}
			// Create shopping cart buttons...
			if (strpos($templateheader[0],"<!-- AShopcartbuttons -->") !== false) {
				$shop = $currentshop;
				$layout = 5;
				ob_start();
				$tempdir = getcwd();
				chdir($ashoppath);
				include "includes/topform.inc.php";
				chdir($tempdir);
				$resulthtml = ob_get_contents();
				ob_end_clean();
				$templateheader[0] = str_replace("<!-- AShopcartbuttons -->", $resulthtml, $templateheader[0]);
			}
			// Create top list and latest additions...
			if (strpos($templateheader[0],"<!-- AShoptopandlatest -->") !== false) {
				$shop = $currentshop;
				$redirect="$ashopurl/index.php";
				ob_start();
				$tempdir = getcwd();
				chdir($ashoppath);
				include "includes/toplist.inc.php";
				chdir($tempdir);
				$resulthtml = ob_get_contents();
				ob_end_clean();
				$templateheader[0] = str_replace("<!-- AShoptopandlatest -->", $resulthtml, $templateheader[0]);
			}
			// Create only top list...
			if (strpos($templateheader[0],"<!-- AShoptoplist -->") !== false) {
				$shop = $currentshop;
				$redirect="$ashopurl/index.php";
				$layout = 1;
				ob_start();
				$tempdir = getcwd();
				chdir($ashoppath);
				include "includes/toplist.inc.php";
				chdir($tempdir);
				$resulthtml = ob_get_contents();
				ob_end_clean();
				$templateheader[0] = str_replace("<!-- AShoptoplist -->",$resulthtml, $templateheader[0]);
			}
			// Create only top list...
			if (strpos($templateheader[0],"<!-- AShoptopshoplist -->") !== false) {
				$redirect="$ashopurl/index.php";
				$layout = 1;
				ob_start();
				$tempdir = getcwd();
				chdir($ashoppath);
				include "includes/topshops.inc.php";
				chdir($tempdir);
				$resulthtml = ob_get_contents();
				ob_end_clean();
				$templateheader[0] = str_replace("<!-- AShoptopshoplist -->",$resulthtml, $templateheader[0]);
			}
			// Create language selector...
			if (strpos($templateheader[0],"<!-- AShoplanguages -->") !== false) {
				$shop = $currentshop;
				$redirect="index.php";
				ob_start();
				$tempdir = getcwd();
				chdir($ashoppath);
				include "includes/language.inc.php";
				chdir($tempdir);
				$resulthtml = ob_get_contents();
				ob_end_clean();
				$templateheader[0] = str_replace("<!-- AShoplanguages -->", $resulthtml, $templateheader[0]);
			}
			// Create currency selector...
			if (strpos($templateheader[0],"<!-- AShopcurrencies -->") !== false) {
				$shop = $currentshop;
				$redirect="index.php";
				$currencies="usd,cad,aud,eur";
				ob_start();
				$tempdir = getcwd();
				chdir($ashoppath);
				include "includes/currency.inc.php";
				chdir($tempdir);
				$resulthtml = ob_get_contents();
				ob_end_clean();
				$templateheader[0] = str_replace("<!-- AShopcurrencies -->", $resulthtml, $templateheader[0]);
			}
			// Create news feed reader...
			if (strpos($templateheader[0],"<!-- AShopnews -->") !== false) {
				$tempdir = getcwd();
				chdir($ashoppath);
				include "includes/simplepie.inc.php";
				$feed = new SimplePie();
				$feed->set_cache_location("$ashoppath/updates");
				$feed->set_cache_duration(900);
				$feed->set_feed_url($ashopnewsfeed);
				$feed->init();
				ob_start();
				if ($feed->data) {
					$items = $feed->get_items(0,5);
					foreach($items as $item) echo " &nbsp;<img src=\"$ashopurl/images/bullet.gif\" alt=\"o\" /> &nbsp;<a href=\"".$item->get_permalink()."\" target=\"_blank\">".$item->get_title()."</a><br />";
				}
				chdir($tempdir);
				$resulthtml = ob_get_contents();
				ob_end_clean();
				$templateheader[0] = str_replace("<!-- AShopnews -->", $resulthtml, $templateheader[0]);
			}
			// Get product count...
			if (strpos($templateheader[0],"<!-- AShopnumberofproducts -->") !== false && is_resource($db)) {
				if (!empty($shop) && is_numeric($shop) && $shop > 1) $totalproductscount = @mysqli_query($db, "SELECT productid FROM product WHERE userid='$shop' AND (copyof='' OR copyof IS NULL) AND (prodtype != 'content' OR prodtype IS NULL)");
				else $totalproductscount = @mysqli_query($db, "SELECT productid FROM product WHERE (copyof='' OR copyof IS NULL) AND (prodtype != 'content' OR prodtype IS NULL)");
				$totalproductscount = @mysqli_num_rows($totalproductscount);
				$templateheader[0] = str_replace("<!-- AShopnumberofproducts -->", $totalproductscount, $templateheader[0]);
			}
			if (strpos($templateheader[0],"name=\"cart\"")) $returntotoplink = "cart2";
			else $returntotoplink = "cart";
			if (defined("CHARSET") && !$cmsurl) {
				$templateheader[0] = demetafy($templateheader[0]);
				$templateheader[0] = str_replace("</title>", "</title>\n".CHARSET, $templateheader[0]);
			}
			if ($ashopuser && $ashopuser != "ashopadmin" && file_exists("$ashoppath/members/files/$ashopuser/logo.gif")) $templateheader[0] = str_replace("<!-- AShoplogo -->", "<img src=\"$ashopurl/members/files/$ashopuser/logo.gif\" alt=\"$ashopname\" border=\"0\">", $templateheader[0]);
			else $templateheader[0] = str_replace("<!-- AShoplogo -->", "<img src=\"$ashopurl/images/logo.gif\" alt=\"$ashopname\" border=\"0\">", $templateheader[0]);
			$templateheader[0] = str_replace("<!-- AShopimage -->", $ashopimage, $templateheader[0]);
			$templateheader[0] = str_replace("<!-- AShopbgcolor -->", $bgcolor, $templateheader[0]);
			$templateheader[0] = str_replace("<!-- AShopmemberheader -->", $pageheader, $templateheader[0]);
			$templateheader[0] = str_replace("<!-- AShopname -->", $ashopname, $templateheader[0]);
			$templateheader[0] = str_replace("<!-- AShoptitle -->", $ashoptitle, $templateheader[0]);
			$templateheader[0] = str_replace("<!-- AShopURL -->", $ashopurl, $templateheader[0]);
			$templateheader[0] = str_replace("<!-- AShopmetakeywords -->",$ashopmetakeywords,$templateheader[0]);
			$templateheader[0] = str_replace("<!-- AShopmetadescription -->",$ashopmetadescription,$templateheader[0]);
			if ($shop > 1) {
				$templateheader[0] = str_replace("<!-- AShopcss -->", "includes/ashopcss.inc.php?shop=$shop", $templateheader[0]);
				$templateheader[0] = str_replace("<!-- AShopmember -->", "$shop", $templateheader[0]);
			} else if ($shop < 0) $templateheader[0] = str_replace("<!-- AShopcss -->", "../includes/ashopcss.inc.php?shop=$shop", $templateheader[0]);
			else {
				$templateheader[0] = str_replace("<!-- AShopcss -->", "includes/ashopcss.inc.php", $templateheader[0]);
				$templateheader[0] = str_replace("?shop=<!-- AShopmember -->", "", $templateheader[0]);
				$templateheader[0] = str_replace("&shop=<!-- AShopmember -->", "", $templateheader[0]);
				$templateheader[0] = str_replace("shop=<!-- AShopmember -->", "", $templateheader[0]);
				$templateheader[0] = str_replace("<!-- AShopmember -->", "1", $templateheader[0]);
			}
			$templateheader[0] = ashop_parseaffiliatetags($templateheader[0]);
			echo $templateheader[0];
		}
	} else {
		if (defined("CHARSET")) echo "<html><head><title>$ashopname</title>\n".CHARSET."</head>";
		else echo "<html><head><title>$ashopname</title></head>";
		echo "<body bgcolor=\"$bgcolor\" text=\"$textcolor\" link=\"$linkcolor\" alink=\"$linkcolor\" vlink=\"$linkcolor\"><center><p><img src=\"$ashopurl/images/logo.gif\"></p>";
	}
}

// Show the portion of a template that comes before <!-- AShopstart -->...
function ashop_showtemplateheaderssl($templatepath,$logourl) {
	global $db, $font, $fontsize1, $fontsize2, $fontsize3, $tablesize1, $tablesize2, $ashopname, $ashoptitle, $ashopmetakeywords, $ashopmetadescription, $shop, $ashopurl, $ashopsurl, $ashoppath, $bgcolor, $textcolor, $linkcolor, $ashopuser, $pageheader, $returntotoplink, $cmsurl, $basket, $ashopcurrency, $databaseserver, $databaseuser, $lang, $defaultlanguage, $ashopimage, $device, $ashoptheme, $displaywithtax, $taxpercentage, $decimalchar, $thousandchar, $ashopnewsfeed, $enableproductcount, $curr, $hideemptycategories, $showdecimals;
	if (!isset($lang) || !preg_match("/[a-z]+/", $lang) || strlen($lang) > 2) $lang = $defaultlanguage;
	if (!$ashoptitle) $ashoptitle = $ashopname;
	$templateheader = "";
	if ($ashoptitle) $ashoptitle = strip_tags($ashoptitle);
	if ($ashopmetakeywords) $ashopmetakeywords = strip_tags($ashopmetakeywords);
	if ($ashopmetadescription) $ashopmetadescription = strip_tags($ashopmetadescription);
	if ($cmsurl) $template = ashop_helicmstemplate();
	$_SERVER['REQUEST_URI'] = str_replace("/affiliate","",$_SERVER['REQUEST_URI']);
	if (file_exists("$templatepath") || $template) {
		if (!$template) {
			$fp = fopen ("$templatepath","r");
			while (!feof ($fp)) $template .= fgets($fp, 4096);
			fclose($fp);
		}
		$templateheader = explode("<!-- AShopstart -->", $template);
		if ($logourl) $templateheader[0] = str_replace("\"images/logo.gif", "\"$logourl", $templateheader[0]);
		$templateheader[0] = str_replace("\"$ashopurl/images/logo.gif", "\"$logourl", $templateheader[0]);
		if (count($templateheader) < 2) {
			if (defined("CHARSET")) echo "<html><head><title>$ashopname</title>\n".CHARSET."<link rel=\"stylesheet\" href=\"includes/ashopcss.inc.php\" type=\"text/css\"></head>";
			else echo "<html><head><title>$ashopname</title></head>";
			echo "<body bgcolor=\"$bgcolor\" text=\"$textcolor\" link=\"$linkcolor\" alink=\"$linkcolor\" vlink=\"$linkcolor\"><center>
			<p><img src=\"$logourl\"></p><p><font face=\"$font\" size=\"2\" color=\"#900000\"><b>Error! Incorrectly formatted template file!</b></font></p>";
		} else {
			if (strpos($templateheader[0],"<!-- AShopcart -->")) {
				ob_start();
				// Get subtotal...
				$layout = 4;
				$customerlogin = "off";
				include "includes/topform.inc.php";
				echo "<br><br><div align=\"center\">";
				// Get shopping cart buttons...
				$layout = 5;
				include "includes/topform.inc.php";
				echo "</div>";
				$carthtml = ob_get_contents();
				ob_end_clean();
				$templateheader[0] = str_replace("<!-- AShopcart -->", $carthtml, $templateheader[0]);
			}
			// Create menu...
			if (strpos($templateheader[0],"<!-- AShopmenu -->") !== false) {
				ob_start();
				$tempdir = getcwd();
				// Get menu items...
				chdir($ashoppath);
				include "includes/menu.inc.php";
				chdir($tempdir);
				$menuhtml = ob_get_contents();
				ob_end_clean();
				$templateheader[0] = str_replace("<!-- AShopmenu -->", $menuhtml, $templateheader[0]);
			}
			// Create standard categories list...
			if (strpos($templateheader[0],"<!-- AShopcategories -->")) {
				ob_start();
				$tempdir = getcwd();
				// Get categories...
				$catalog = "index.php";
				chdir($ashoppath);
				include "includes/categories.inc.php";
				chdir($tempdir);
				$categorieshtml = ob_get_contents();
				ob_end_clean();
				$templateheader[0] = str_replace("<!-- AShopcategories -->", $categorieshtml, $templateheader[0]);
			}
			// Create unordered categories list for CSS styling...
			if (strpos($template,"<!-- AShopcategorieslist -->")) {
				ob_start();
				$tempdir = getcwd();
				// Get categories...
				$catalog = "index.php";
				$layout = 2;
				chdir($ashoppath);
				include "includes/categories.inc.php";
				$categorieshtml = ob_get_contents();
				ob_end_clean();
				$templateheader[0] = str_replace("<!-- AShopcategorieslist -->", $categorieshtml, $templateheader[0]);
			}
			// Create shopping mall members list...
			if (strpos($templateheader[0],"<!-- AShopmembers -->") !== false) {
				ob_start();
				$tempdir = getcwd();
				// Get shops...
				$layout = 1;
				chdir($ashoppath);
				include "includes/shops.inc.php";
				chdir($tempdir);
				$shopshtml = ob_get_contents();
				ob_end_clean();
				$templateheader[0] = str_replace("<!-- AShopmembers -->", $shopshtml, $templateheader[0]);
			}
			// Create manufacturers list...
			if (strpos($templateheader[0],"<!-- AShopmanufacturers -->")) {
				ob_start();
				$tempdir = getcwd();
				// Get categories...
				$catalog = "index.php";
				$layout = 1;
				chdir($ashoppath);
				include "includes/manufacturers.inc.php";
				chdir($tempdir);
				$manufacturershtml = ob_get_contents();
				ob_end_clean();
				$templateheader[0] = str_replace("<!-- AShopmanufacturers -->", $manufacturershtml, $templateheader[0]);
			}
			// Create unordered categories list for CSS styling...
			if (strpos($template,"<!-- AShopmanufacturerslist -->")) {
				ob_start();
				$tempdir = getcwd();
				// Get categories...
				$catalog = "index.php";
				$layout = 2;
				chdir($ashoppath);
				include "includes/manufacturers.inc.php";
				$manufacturershtml = ob_get_contents();
				ob_end_clean();
				$templateheader[0] = str_replace("<!-- AShopmanufacturerslist -->", $manufacturershtml, $templateheader[0]);
			}
			// Create customer profile links...
			if (strpos($templateheader[0],"<!-- AShopcustomerlinks -->")) {
				$layout = 6;
				ob_start();
				$tempdir = getcwd();
				chdir($ashoppath);
				include "includes/topform.inc.php";
				chdir($tempdir);
				$resulthtml = ob_get_contents();
				ob_end_clean();
				$templateheader[0] = str_replace("<!-- AShopcustomerlinks -->", $resulthtml, $templateheader[0]);
			}
			// Create subtotal box...
			if (strpos($template,"<!-- AShopsubtotal -->") !== false) {
				$shop = $currentshop;
				$layout = 4;
				$customerlogin = "off";
				ob_start();
				$tempdir = getcwd();
				chdir($ashoppath);
				include "includes/topform.inc.php";
				chdir($tempdir);
				$resulthtml = ob_get_contents();
				ob_end_clean();
				$templateheader[0] = str_replace("<!-- AShopsubtotal -->", $resulthtml, $templateheader[0]);
			}
			// Create shopping cart buttons...
			if (strpos($template,"<!-- AShopcartbuttons -->") !== false) {
				$shop = $currentshop;
				$layout = 5;
				ob_start();
				$tempdir = getcwd();
				chdir($ashoppath);
				include "includes/topform.inc.php";
				chdir($tempdir);
				$resulthtml = ob_get_contents();
				ob_end_clean();
				$templateheader[0] = str_replace("<!-- AShopcartbuttons -->", $resulthtml, $templateheader[0]);
			}
			// Create top list and latest additions...
			if (strpos($templateheader[0],"<!-- AShoptopandlatest -->") !== false) {
				$shop = $currentshop;
				$redirect="$ashopurl/index.php";
				ob_start();
				$tempdir = getcwd();
				chdir($ashoppath);
				include "includes/toplist.inc.php";
				chdir($tempdir);
				$resulthtml = ob_get_contents();
				ob_end_clean();
				$templateheader[0] = str_replace("<!-- AShoptopandlatest -->", $resulthtml, $templateheader[0]);
			}
			// Create only top list...
			if (strpos($templateheader[0],"<!-- AShoptoplist -->") !== false) {
				$shop = $currentshop;
				$redirect="$ashopurl/index.php";
				$layout = 1;
				ob_start();
				$tempdir = getcwd();
				chdir($ashoppath);
				include "includes/toplist.inc.php";
				chdir($tempdir);
				$resulthtml = ob_get_contents();
				ob_end_clean();
				$templateheader[0] = str_replace("<!-- AShoptoplist -->",$resulthtml, $templateheader[0]);
			}
			// Create only top list...
			if (strpos($templateheader[0],"<!-- AShoptopshoplist -->") !== false) {
				$redirect="$ashopurl/index.php";
				$layout = 1;
				ob_start();
				$tempdir = getcwd();
				chdir($ashoppath);
				include "includes/topshops.inc.php";
				chdir($tempdir);
				$resulthtml = ob_get_contents();
				ob_end_clean();
				$templateheader[0] = str_replace("<!-- AShoptopshoplist -->",$resulthtml, $templateheader[0]);
			}
			// Create language selector...
			if (strpos($template,"<!-- AShoplanguages -->") !== false) {
				$shop = $currentshop;
				$redirect="index.php";
				ob_start();
				$tempdir = getcwd();
				chdir($ashoppath);
				include "includes/language.inc.php";
				chdir($tempdir);
				$resulthtml = ob_get_contents();
				ob_end_clean();
				$templateheader[0] = str_replace("<!-- AShoplanguages -->", $resulthtml, $templateheader[0]);
			}
			// Create currency selector...
			if (strpos($template,"<!-- AShopcurrencies -->") !== false) {
				$shop = $currentshop;
				$redirect="index.php";
				$currencies="usd,cad,aud,eur";
				ob_start();
				$tempdir = getcwd();
				chdir($ashoppath);
				include "includes/currency.inc.php";
				chdir($tempdir);
				$resulthtml = ob_get_contents();
				ob_end_clean();
				$templateheader[0] = str_replace("<!-- AShopcurrencies -->", $resulthtml, $templateheader[0]);
			}
			// Create news feed reader...
			if (strpos($template,"<!-- AShopnews -->") !== false) {
				$tempdir = getcwd();
				chdir($ashoppath);
				include "includes/simplepie.inc.php";
				$feed = new SimplePie();
				$feed->set_cache_location("$ashoppath/updates");
				$feed->set_cache_duration(900);
				$feed->set_feed_url($ashopnewsfeed);
				$feed->init();
				ob_start();
				if ($feed->data) {
					$items = $feed->get_items(0,5);
					foreach($items as $item) echo " &nbsp;<img src=\"images/bullet.gif\" alt=\"o\" /> &nbsp;<a href=\"".$item->get_permalink()."\" target=\"_blank\">".$item->get_title()."</a><br />";
				}
				chdir($tempdir);
				$resulthtml = ob_get_contents();
				ob_end_clean();
				$templateheader[0] = str_replace("<!-- AShopnews -->", $resulthtml, $templateheader[0]);
			}
			// Get product count...
			if (strpos($templateheader[0],"<!-- AShopnumberofproducts -->") !== false && is_resource($db)) {
				if (!empty($shop) && is_numeric($shop) && $shop > 1) $totalproductscount = @mysqli_query($db, "SELECT productid FROM product WHERE userid='$shop' AND (copyof='' OR copyof IS NULL) AND (prodtype != 'content' OR prodtype IS NULL)");
				else $totalproductscount = @mysqli_query($db, "SELECT productid FROM product WHERE (copyof='' OR copyof IS NULL) AND (prodtype != 'content' OR prodtype IS NULL)");
				$totalproductscount = @mysqli_num_rows($totalproductscount);
				$templateheader[0] = str_replace("<!-- AShopnumberofproducts -->", $totalproductscount, $templateheader[0]);
			}
			if (defined("CHARSET") && !$cmsurl) {
				$templateheader[0] = demetafy($templateheader[0]);
				$templateheader[0] = str_replace("</title>", "</title>\n".CHARSET, $templateheader[0]);
			} else str_replace("CHARSET", "", $templateheader[0]);
			if ($ashopuser && $ashopuser != "ashopadmin" && file_exists("$ashoppath/members/files/$ashopuser/logo.gif")) $templateheader[0] = str_replace("<!-- AShoplogo -->", "<img src=\"$ashopsurl/members/files/$ashopuser/logo.gif\" border=\"0\">", $templateheader[0]);
			else {
				if ($logourl) $templateheader[0] = str_replace("<!-- AShoplogo -->", "<img src=\"$logourl\" border=\"0\">", $templateheader[0]);
				else $templateheader[0] = str_replace("<!-- AShoplogo -->", "<img src=\"$ashopsurl/images/logo.gif\" border=\"0\">", $templateheader[0]);
			}
			$templateheader[0] = str_replace("<!-- AShopbgcolor -->", $bgcolor, $templateheader[0]);
			$templateheader[0] = str_replace("<!-- AShopmemberheader -->", $pageheader, $templateheader[0]);
			$templateheader[0] = str_replace("<!-- AShopname -->", $ashopname, $templateheader[0]);
			$templateheader[0] = str_replace("<!-- AShoptitle -->", $ashoptitle, $templateheader[0]);
			$templateheader[0] = str_replace("<!-- AShopURL -->", $ashopsurl, $templateheader[0]);
			$templateheader[0] = str_replace("<!-- AShopmetakeywords -->",$ashopmetakeywords,$templateheader[0]);
			$templateheader[0] = str_replace("<!-- AShopmetadescription -->",$ashopmetadescription,$templateheader[0]);
			if ($shop > 1) {
				$templateheader[0] = str_replace("<!-- AShopcss -->", "includes/ashopcss.inc.php?shop=$shop", $templateheader[0]);
				$templateheader[0] = str_replace("<!-- AShopmember -->", "$shop", $templateheader[0]);
			} else if ($shop < 0) $templateheader[0] = str_replace("<!-- AShopcss -->", "../includes/ashopcss.inc.php?shop=$shop", $templateheader[0]);
			else {
				$templateheader[0] = str_replace("<!-- AShopcss -->", "includes/ashopcss.inc.php", $templateheader[0]);
				$templateheader[0] = str_replace("?shop=<!-- AShopmember -->", "", $templateheader[0]);
				$templateheader[0] = str_replace("&shop=<!-- AShopmember -->", "", $templateheader[0]);
				$templateheader[0] = str_replace("shop=<!-- AShopmember -->", "", $templateheader[0]);
				$templateheader[0] = str_replace("<!-- AShopmember -->", "1", $templateheader[0]);
			}
			echo $templateheader[0];
		}
	} else {
		if (defined("CHARSET")) echo "<html><head><title>$ashopname</title>\n".CHARSET."</head>";
		else echo "<html><head><title>$ashopname</title></head>";
		echo "<body bgcolor=\"$bgcolor\" text=\"$textcolor\" link=\"$linkcolor\" alink=\"$linkcolor\" vlink=\"$linkcolor\"><center><p><img src=\"$logourl\"></p>";
	}
}

// Show the portion of a template that comes after <!-- AShopend -->...
function ashop_showtemplatefooter($templatepath) {
	global $db, $font, $fontsize1, $fontsize2, $fontsize3, $tablesize1, $tablesize2, $ashopname, $ashoptitle, $ashopmetakeywords, $ashopmetadescription, $shop, $ashopurl, $ashopsurl, $ashoppath, $bgcolor, $textcolor, $linkcolor, $ashopuser, $pagefooter, $returntotoplink, $cmsurl, $basket, $ashopcurrency, $databaseserver, $databaseuser, $lang, $defaultlanguage, $ashopimage, $device, $ashoptheme, $displaywithtax, $taxpercentage, $decimalchar, $thousandchar, $enableproductcount, $hideemptycategories, $showdecimals;
	$template = "";
	if ($cmsurl) $template = ashop_helicmstemplate();
	if (file_exists("$templatepath") || $template) {
		if (!$template) {
			$fp = fopen ("$templatepath", "r");
			while (!feof ($fp)) $template .= fgets($fp, 4096);
			fclose($fp);
		}
		$templatefooter = explode("<!-- AShopend -->", $template);
		if (count($templatefooter) < 2) echo "<p><font face=\"$font\" size=\"2\" color=\"#900000\"><b>Error! Incorrectly formatted template file!</b></font></p>";
		else {
			if (strpos($templatefooter[1],"<!-- AShopcart -->") !== false) {
				ob_start();
				// Get subtotal...
				$layout = 4;
				$customerlogin = "off";
				$fromtemplate = "true";
				include "includes/topform.inc.php";
				print "<br><br><div align=\"center\">";
				// Get shopping cart buttons...
				$layout = 5;
				include "includes/topform.inc.php";
				print "</div>";
				$carthtml = ob_get_contents();
				ob_end_clean();
				$templatefooter[1] = str_replace("<!-- AShopcart -->", $carthtml, $templatefooter[1]);
			}
			// Create menu...
			if (strpos($templatefooter[1],"<!-- AShopmenu -->") !== false) {
				ob_start();
				$tempdir = getcwd();
				// Get menu items...
				chdir($ashoppath);
				include "includes/menu.inc.php";
				chdir($tempdir);
				$menuhtml = ob_get_contents();
				ob_end_clean();
				$templatefooter[1] = str_replace("<!-- AShopmenu -->", $menuhtml, $templatefooter[1]);
			}
			if (strpos($templatefooter[1],"<!-- AShopcategories -->") !== false) {
				ob_start();
				$tempdir = getcwd();
				// Get categories...
				$catalog = "index.php";
				$fromtemplate = "true";
				chdir($ashoppath);
				include "includes/categories.inc.php";
				chdir($tempdir);
				$categorieshtml = ob_get_contents();
				ob_end_clean();
				$templatefooter[1] = str_replace("<!-- AShopcategories -->", $categorieshtml, $templatefooter[1]);
			}
			// Create shopping mall members list...
			if (strpos($templatefooter[1],"<!-- AShopmembers -->") !== false) {
				ob_start();
				$tempdir = getcwd();
				// Get shops...
				$layout = 1;
				chdir($ashoppath);
				include "includes/shops.inc.php";
				chdir($tempdir);
				$shopshtml = ob_get_contents();
				ob_end_clean();
				$templatefooter[1] = str_replace("<!-- AShopmembers -->", $shopshtml, $templatefooter[1]);
			}
			// Create subtotal box...
			if (strpos($templatefooter[1],"<!-- AShopsubtotal -->") !== false) {
				$shop = $currentshop;
				$layout = 4;
				$customerlogin = "off";
				ob_start();
				$tempdir = getcwd();
				chdir($ashoppath);
				include "includes/topform.inc.php";
				chdir($tempdir);
				$resulthtml = ob_get_contents();
				ob_end_clean();
				$templatefooter[1] = str_replace("<!-- AShopsubtotal -->", $resulthtml, $templatefooter[1]);
			}
			// Create shopping cart buttons...
			if (strpos($templatefooter[1],"<!-- AShopcartbuttons -->") !== false) {
				$shop = $currentshop;
				$layout = 5;
				ob_start();
				$tempdir = getcwd();
				chdir($ashoppath);
				include "includes/topform.inc.php";
				chdir($tempdir);
				$resulthtml = ob_get_contents();
				ob_end_clean();
				$templatefooter[1] = str_replace("<!-- AShopcartbuttons -->", $resulthtml, $templatefooter[1]);
			}
			// Create top list and latest additions...
			if (strpos($templatefooter[1],"<!-- AShoptopandlatest -->") !== false) {
				$shop = $currentshop;
				$redirect="$ashopurl/index.php";
				ob_start();
				$tempdir = getcwd();
				chdir($ashoppath);
				include "includes/toplist.inc.php";
				chdir($tempdir);
				$resulthtml = ob_get_contents();
				ob_end_clean();
				$templatefooter[1] = str_replace("<!-- AShoptopandlatest -->", $resulthtml, $templatefooter[1]);
			}
			// Create only top list...
			if (strpos($templatefooter[1],"<!-- AShoptoplist -->") !== false) {
				$shop = $currentshop;
				$redirect="$ashopurl/index.php";
				$layout = 1;
				ob_start();
				$tempdir = getcwd();
				chdir($ashoppath);
				include "includes/toplist.inc.php";
				chdir($tempdir);
				$resulthtml = ob_get_contents();
				ob_end_clean();
				$templatefooter[1] = str_replace("<!-- AShoptoplist -->",$resulthtml, $templatefooter[1]);
			}
			// Create only top list...
			if (strpos($templatefooter[1],"<!-- AShoptopshoplist -->") !== false) {
				$redirect="$ashopurl/index.php";
				$layout = 1;
				ob_start();
				$tempdir = getcwd();
				chdir($ashoppath);
				include "includes/topshops.inc.php";
				chdir($tempdir);
				$resulthtml = ob_get_contents();
				ob_end_clean();
				$templatefooter[1] = str_replace("<!-- AShoptopshoplist -->",$resulthtml, $templatefooter[1]);
			}
			// Create language selector...
			if (strpos($templatefooter[1],"<!-- AShoplanguages -->") !== false) {
				$shop = $currentshop;
				$redirect="index.php";
				ob_start();
				$tempdir = getcwd();
				chdir($ashoppath);
				include "includes/language.inc.php";
				chdir($tempdir);
				$resulthtml = ob_get_contents();
				ob_end_clean();
				$templatefooter[1] = str_replace("<!-- AShoplanguages -->", $resulthtml, $templatefooter[1]);
			}
			// Create currency selector...
			if (strpos($templatefooter[1],"<!-- AShopcurrencies -->") !== false) {
				$shop = $currentshop;
				$redirect="index.php";
				$currencies="usd,cad,aud,eur";
				ob_start();
				$tempdir = getcwd();
				chdir($ashoppath);
				include "includes/currency.inc.php";
				chdir($tempdir);
				$resulthtml = ob_get_contents();
				ob_end_clean();
				$templatefooter[1] = str_replace("<!-- AShopcurrencies -->", $resulthtml, $templatefooter[1]);
			}
			// Get product count...
			if (strpos($templatefooter[1],"<!-- AShopnumberofproducts -->") !== false && is_resource($db)) {
				if (!empty($shop) && is_numeric($shop) && $shop > 1) $totalproductscount = @mysqli_query($db, "SELECT productid FROM product WHERE userid='$shop' AND (copyof='' OR copyof IS NULL) AND (prodtype != 'content' OR prodtype IS NULL)");
				else $totalproductscount = @mysqli_query($db, "SELECT productid FROM product WHERE (copyof='' OR copyof IS NULL) AND (prodtype != 'content' OR prodtype IS NULL)");
				$totalproductscount = @mysqli_num_rows($totalproductscount);
				$templatefooter[1] = str_replace("<!-- AShopnumberofproducts -->", $totalproductscount, $templatefooter[1]);
			}
			$templatefooter[1] = str_replace("<!-- AShopmemberfooter -->", $pagefooter, $templatefooter[1]);
			$templatefooter[1] = str_replace("<!-- AShopname -->", $ashopname, $templatefooter[1]);
			echo $templatefooter[1];
		}
	}
	else echo "</body></html>";
}

// Show the portion of a template that comes between <!-- AShopstart --> and <!-- AShopend -->...
function ashop_showtemplatemiddle($templatepath) {
	global $font, $ashopname, $ashopurl;
	$template = "";
	if (file_exists("$templatepath")) {
		$fp = fopen ("$templatepath","r");
		while (!feof ($fp)) $template .= fgets($fp, 4096);
		fclose($fp);
		$templatemiddle = explode("<!-- AShopstart -->", $template);
		if (count($templatemiddle) < 2) $templateerror = 1;
		$templatemiddle = explode("<!-- AShopend -->", $templatemiddle[1]);
		if (count($templatemiddle) < 2) $templateerror = 1;
		$templatemiddle[0] = str_replace("<!-- AShoplogo -->", "<img src=\"$ashopurl/images/logo.gif\" border=\"0\">", $templatemiddle[0]);
		$templatemiddle[0] = str_replace("<!-- AShopname -->", $ashopname, $templatemiddle[0]);
		if ($templateerror) echo "<p><font face=\"$font\" size=\"2\" color=\"#900000\"><b>Error! Incorrectly formatted template file!</b></font></p>";
		else echo $templatemiddle[0];
	}
}

// Parse a product string into an array...
function ashop_parseproductstring($databaseconnection, $productstring) {
	global $discountall;
	// Set limit to approximately 50 items to avoid DOS attacks...
	if (strlen($productstring) > 800) {
		$productstringend = strpos($productstring,"a",800)+1;
		$productstring = substr($productstring,0,$productstringend);
	}
	$items = explode("a", $productstring);
	$arraycount = 1;
	if ($items[0] && count($items)==1) $arraycount = 0;
	$productnumber = 0;
	for ($i = 0; $i < count($items)-$arraycount; $i++) {
		$thissegment = $items[$i]."a";
		$thisitem = explode("b", $items[$i]);
		$thisquantity = $thisitem[0];
		settype($thisquantity, 'float');
		$prethisproductid = $thisitem[count($thisitem)-1];
		$thisproductid = explode("d", $prethisproductid);
		if (count($thisproductid) == 2) $discounted = "true";
		else {
			$storewidediscount = "false";
			if ($discountall) {
				$storediscountresult = @mysqli_query($databaseconnection, "SELECT * FROM storediscounts WHERE discountid='$discountall'");
				if (@mysqli_num_rows($storediscountresult)) {
					if (@mysqli_result($storediscountresult, 0, "type") == "%") {
						$discounted = "true";
						$storewidediscount = "true";
					}
				} else $discounted = "false";
			} else $discounted = "false";
		}
		$thisproductid = $thisproductid[0];
		settype($thisproductid, 'integer');
		$parameterstring = "";
		$disableshipping = 0;
		$disabletax = 0;
		$disablefulfilment = 0;
		$attributeprice = "";
		$attributepricelevels = "";
		$parameterarray = array();
		$download = "all";
		$type = "";
		$thisskucode = "";
		if (count($thisitem > 2)) {
			$sql = "SELECT * FROM parameters WHERE productid='$thisproductid' ORDER BY parameterid";
			$result = @mysqli_query($databaseconnection, "$sql");
			if (@mysqli_num_rows($result)) {
				for ($j = 0; $j < @mysqli_num_rows($result); $j++) {
					$caption = @mysqli_result($result, $j, "caption");
					$parameterid = @mysqli_result($result, $j, "parameterid");
					$valueid = $thisitem[$j+1];
					$checkvalues = @mysqli_query($databaseconnection, "SELECT * FROM parametervalues WHERE parameterid='$parameterid'");
					$sql = "SELECT * FROM parametervalues WHERE valueid='$valueid' AND parameterid='$parameterid'";
					$subresult = @mysqli_query($databaseconnection, "$sql");
					if (!@mysqli_num_rows($subresult)) {
						$subresult = @mysqli_query($databaseconnection, "SELECT * FROM customparametervalues WHERE valueid='$valueid' AND parameterid='$parameterid'");
						$download = "all";
						$thisdownload = "all";
						$iscustomerparameter = true;
					} else {
						$thisdownload = @mysqli_result($subresult, 0, "download");
						$noshipping = @mysqli_result($subresult, 0, "noshipping");
						$notax = @mysqli_result($subresult, 0, "notax");
						$nofulfilment = @mysqli_result($subresult, 0, "nofulfilment");
						$thisattributeprice = @mysqli_result($subresult, 0, "price");
						if (isset($thisattributeprice)) {
							if (strstr($thisattributeprice,"|")) {
								$thisattributeprices = explode("|",$thisattributeprice);
								if (count($thisattributeprices) > 2) {
									$attributepricelevels = "";
									foreach ($thisattributeprices as $thisattributenumber=>$thisattributeprice) {
										if ($thisattributenumber > 1) $attributepricelevels .= $thisattributeprice."|";
									}
									$attributepricelevels = substr($attributepricelevels,0,-1);
								}
								$thisattributeprice = $thisattributeprices[0];
								$thisattributewsprice = $thisattributeprices[1];
							}
							$attributeprice = $thisattributeprice;
						}
						$iscustomerparameter = false;
						$type .= $valueid."|";
					}
					$value = @mysqli_result($subresult, 0, "value");
					if ($download == "all") $download = $thisdownload;
					if ($noshipping) $disableshipping = 1;
					if ($notax) $disabletax = 1;
					if ($nofulfilment) $disablefulfilment = 1;
					if ($caption && !$value) $parameterstring .= "[$caption: unset]";
					else {
						if (@mysqli_num_rows($checkvalues) > 1 || $iscustomerparameter) $parameterstring .= "[$caption: $value]";
						$parameterarray[] = $value;
					}
				}
			}
		}
		if ($type) {
			$type = substr($type,0,-1);
			$typeresult = @mysqli_query($databaseconnection, "SELECT * FROM productinventory WHERE type='$type' AND productid='$thisproductid'");
			$thisskucode = @mysqli_result($typeresult,0,"skucode");
			$thisinventory = @mysqli_result($typeresult,0,"inventory");
		} else unset($thisinventory);
		if ($thisitem[0] != "sh" && $thisitem[0] != "st" && !strstr($thisitem[0], "so") && $thisitem[0] != "sd") {
			$filesresult = @mysqli_query($databaseconnection, "SELECT * FROM productfiles WHERE productid='$thisproductid' ORDER BY ordernumber");
			$filename = "";
			while($filerow = @mysqli_fetch_array($filesresult)) $filename["{$filerow["fileid"]}"] = $filerow["filename"];
			// Check for floating price...
			$thisendprice = "";
			$thiswinner = 0;
			$thisfporder = 0;
			$fpresult = @mysqli_query($databaseconnection, "SELECT * FROM floatingprice WHERE productid='$thisproductid'");
			if (@mysqli_num_rows($fpresult)) {
				$fprow = @mysqli_fetch_array($fpresult);
				$thisendprice = $fprow["endprice"];
				$thiswinner = $fprow["bidderid"];
				$thisfporder = $fprow["orderid"];
			}
			// Check for related products...
			$upsellresult = @mysqli_query($databaseconnection, "SELECT * FROM relatedproducts WHERE productid='$thisproductid' ORDER BY relationid ASC");
			$upsellcount = 1;
			while($upsellrow = @mysqli_fetch_array($upsellresult)) {
				if ($upsellcount == 1) {
					$upsell1["productid"] = $upsellrow["relatedproductid"];
					$upsell1["priority"] = $upsellrow["priority"];
				}
				if ($upsellcount == 2) {
					$upsell2["productid"] = $upsellrow["relatedproductid"];
					$upsell2["priority"] = $upsellrow["priority"];
				}
				$upsellcount++;
			}
			$result = @mysqli_query($databaseconnection, "SELECT * FROM product WHERE productid='$thisproductid'");
			if (!$thisskucode) $thisskucode = @mysqli_result($result, 0, "skucode");
			if (!isset($thisinventory)) $thisinventory = @mysqli_result($result, 0, "inventory");
			if (!$attributeprice) $productprice = @mysqli_result($result, 0, "price");
			else $productprice = $attributeprice;
			if (!$thisattributewsprice) $wholesaleprice = @mysqli_result($result, 0, "wholesaleprice");
			else $wholesaleprice = $thisattributewsprice;
			$productrecurringprice = @mysqli_result($result, 0, "recurringprice");
			$affiliatecominfo = explode("a",@mysqli_result($result, 0, "affiliatecom"));
			$affiliatecominfo2 = explode("a",@mysqli_result($result, 0, "affiliatecom2"));
			$affiliatetiercominfo = explode("|",@mysqli_result($result, 0, "affiliatetiercom"));
			if (!empty($affiliatetiercominfo) && is_array($affiliatetiercominfo)) {
				$affiliatetier2cominfo = explode("a",$affiliatetiercominfo[0]);
				$affiliatetier2cominfo2 = explode("a",$affiliatetiercominfo[1]);
				$affiliatetierlowerby = $affiliatetiercominfo[2];
			}
			$affiliatewscominfo = explode("a",@mysqli_result($result, 0, "affiliatewscom"));
			if (!$attributepricelevels) $wspricelevels = @mysqli_result($result, 0, "wspricelevels");
			else $wspricelevels = $attributepricelevels;
			$wspricelevels = explode("|",$wspricelevels);
			if (empty($producttierlowerby)) $producttierlowerby = 0;
			// Check if this product's recurring period should override the main setting...
			$thisproductrecurringperiod = @mysqli_result($result, 0, "recurringperiod");
			if (!empty($thisproductrecurringperiod)) {
				$recurringperiodcheck = @mysqli_query($databaseconnection, "SELECT payoptionid FROM payoptions WHERE recurringperiod!='$thisproductrecurringperiod' AND recurringperiod IS NOT NULL AND recurringperiod!=''");
				if (!@mysqli_num_rows($recurringperiodcheck)) $thisproductrecurringperiod = "";
			} else $thisproductrecurringperiod = "";
			$thisproductname = @mysqli_result($result, 0, "name");
			if (!empty($thisproductname)) {
			$productinfo[$productnumber] = array (
				"productid" => $thisproductid,
				"copyof" => @mysqli_result($result, 0, "copyof"),
				"quantity" => $thisquantity,
				"sku" => $thisskucode,
				"useinventory" => @mysqli_result($result, 0, "useinventory"),
				"inventory" => $thisinventory,
				"taxable" => @mysqli_result($result, 0, "taxable"),
				"shipping" => @mysqli_result($result, 0, "shipping"),
				"intshipping" => @mysqli_result($result, 0, "intshipping"),
				"active" => @mysqli_result($result, 0, "active"),
				"name" => $thisproductname,
				"userid" => @mysqli_result($result, 0, "userid"),
				"ebayid" => @mysqli_result($result, 0, "ebayid"),
				"avail" => @mysqli_result($result, 0, "avail"),
				"parameters" => $parameterstring,
				"type" => $type,
				"parametervalues" => $parameterarray,
				"description" => @mysqli_result($result, 0, "description"),
				"detailsurl" => @mysqli_result($result, 0, "detailsurl"),
				"price" => $productprice,
				"recurringprice" => $productrecurringprice,
				"recurringperiod" => $thisproductrecurringperiod,
				"recurringunits" => $thisproductrecurringunits,
				"pricetext" => @mysqli_result($result, 0, "pricetext"),
				"qtytype" => @mysqli_result($result, 0, "qtytype"),
				"qtycategory" => @mysqli_result($result, 0, "qtycategory"),
				"wholesaleprice" => $wholesaleprice,
				"wspricelevels" => $wspricelevels,
				"cost" => @mysqli_result($result, 0, "cost"),
				"filename" => $filename,
				"producttype" => @mysqli_result($result, 0, "prodtype"),
				"subscriptiondir" => @mysqli_result($result, 0, "subscriptiondir"),
				"protectedurl" => @mysqli_result($result, 0, "protectedurl"),
				"discounted" => $discounted,
				"storewidediscount" => $storewidediscount,
				"affiliatecomtype" => $affiliatecominfo[1],
				"affiliatecomtype2" => $affiliatecominfo2[1],
				"affiliatecom" => $affiliatecominfo[0],
				"affiliatecom2" => $affiliatecominfo2[0],
				"affiliatetier2comtype" => $affiliatetier2cominfo[1],
				"affiliatetier2comtype2" => $affiliatetier2cominfo2[1],
				"affiliatetier2com" => $affiliatetier2cominfo[0],
				"affiliatetier2com2" => $affiliatetier2cominfo2[0],
				"affiliatetierlowerby" => $affiliatetierlowerby,
				"affiliaterepeatcommission" => @mysqli_result($result, 0, "affiliaterepeatcommission"),
				"wholesalecomtype" => $affiliatewscominfo[1],
				"wholesalecom" => $affiliatewscominfo[0],
				"download" => $download,
				"disableshipping" => $disableshipping,
				"disabletax" => $disabletax,
				"disablefulfilment" => $disablefulfilment,
				"billtemplate" => @mysqli_result($result, 0, "billtemplate"),
				"qtylimit" => @mysqli_result($result, 0, "qtylimit"),
				"qtytlimit" => @mysqli_result($result, 0, "qtytlimit"),
				"fpendprice" => $thisendprice,
				"fpwinner" => $thiswinner,
				"fporder" => $thisfporder,
				"upsell1" => $upsell1,
				"upsell2" => $upsell2,
				"segment" => $thissegment);
			$productnumber++;
		}
		}
	}
	if (!isset($productinfo)) $productinfo = array();
	return $productinfo;
}

// Parse a product string into an array...
function ashop_quickparseproductstring($databaseconnection, $productstring) {
	// Set limit to approximately 50 items to avoid DOS attacks...
	if (strlen($productstring) > 800) {
		$productstringend = strpos($productstring,"a",800)+1;
		$productstring = substr($productstring,0,$productstringend);
	}
	$items = explode("a", $productstring);
	$arraycount = 1;
	if ($items[0] && count($items)==1) $arraycount = 0;
	for ($i = 0; $i < count($items)-$arraycount; $i++) {
		$thissegment = $items[$i]."a";
		$thisitem = explode("b", $items[$i]);
		$thisquantity = $thisitem[0];
		settype($thisquantity, 'float');
		$prethisproductid = $thisitem[count($thisitem)-1];
		$thisproductid = explode("d", $prethisproductid);
		$thisproductid = $thisproductid[0];
		settype($thisproductid, 'integer');
		if ($thisitem[0] != "sh" && $thisitem[0] != "st" && !strstr($thisitem[0], "so") && $thisitem[0] != "sd") {
			$result = @mysqli_query($databaseconnection, "SELECT * FROM product WHERE productid='$thisproductid'");
			$productprice = @mysqli_result($result, 0, "price");
			$productinfo[$i] = array (
				"productid" => $thisproductid,
				"copyof" => @mysqli_result($result, 0, "copyof"),
				"quantity" => $thisquantity,
				"active" => @mysqli_result($result, 0, "active"),
				"name" => @mysqli_result($result, 0, "name"),
				"userid" => @mysqli_result($result, 0, "userid"),
				"price" => $productprice,
				"segment" => $thissegment);
		}
	}
	if (!isset($productinfo)) $productinfo = array();
	return $productinfo;
}

// Get a Google Checkout notification...
function ashop_googlegetnotification($serialnumber, $gcoid, $gcokey, $testmode) {
	if ($testmode == 1) $googlecourl = "https://sandbox.google.com/checkout/api/checkout/v2/reportsForm/Merchant/$gcoid";
	else $googlecourl = "https://checkout.google.com/api/checkout/v2/reportsForm/Merchant/$gcoid";
	$authkey = base64_encode("$gcoid:$gcokey");
	$googlecoheaders[] = "Authorization: Basic $authkey";
	$googlecoheaders[] = "Content-Type: application/xml;charset=UTF-8";
	$googlecoheaders[] = "Accept: application/xml;charset=UTF-8";
	$googlecorequest = "_type=notification-history-request&serial-number=$serialnumber";
	$ch = curl_init();
	if (file_exists("$ashoppath/admin/curl.inc.php")) include "$ashoppath/admin/curl.inc.php";
	curl_setopt ($ch, CURLOPT_URL,$googlecourl);
	curl_setopt($ch, CURLOPT_HTTPHEADER, $googlecoheaders);
	curl_setopt($ch, CURLOPT_POST, 1);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $googlecorequest);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
	curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);
	$notification = curl_exec ($ch);
	curl_close ($ch);
	return $notification;
}

// Calculate a HMAC SHA1 signature...
function ashop_hmacsha1($data,$key) {
      $blocksize = 64;
      $hashfunc = 'sha1';
      if (strlen($key) > $blocksize) {
        $key = pack('H*', $hashfunc($key));
      }
      $key = str_pad($key, $blocksize, chr(0x00));
      $ipad = str_repeat(chr(0x36), $blocksize);
      $opad = str_repeat(chr(0x5c), $blocksize);
      $hmac = pack(
                    'H*', $hashfunc(
                            ($key^$opad).pack(
                                    'H*', $hashfunc(
                                            ($key^$ipad).$data
                                    )
                            )
                    )
                );
      return $hmac;  
}

// Generate a Google Checkout button...
function ashop_googlecheckoutbutton($databaseconnection, $productstring, $googlemerchantid, $googlekey, $testmode=0, $button=1, $signed=1, $inventorycheck=0) {
	global $ashopcurrency, $affiliate;
	$currency = strtoupper($ashopcurrency);
	// Set limit to approximately 50 items to avoid DOS attacks...
	if (strlen($productstring) > 800) {
		$productstringend = strpos($productstring,"a",800)+1;
		$productstring = substr($productstring,0,$productstringend);
	}
	$items = explode("a", $productstring);
	$arraycount = 1;
	if ($items[0] && count($items)==1) $arraycount = 0;
	if ($inventorycheck) echo "<script language=\"JavaScript\" src=\"includes/prototype.js\" type=\"text/javascript\"></script>
	<script language=\"JavaScript\" type=\"text/javascript\">
	/* <![CDATA[ */
	var instock = 0;
	function checkinventory() {
		var myAjax = new Ajax.Request(
			'includes/inventorycheck.inc.php', 
			{
				method: 'get', 
				parameters: 'mode=js&amp;dummy='+ new Date().getTime(),
				onSuccess: function (reportinventory) {
					if (reportinventory.responseText == '1') window.instock = 1;
					else alert(reportinventory.responseText);
				},
				onFailure: function (reportfailure) {
					alert('Could not check inventory!');
				},
				asynchronous: false
			}
		);
		if (window.instock != 1) return false;
		else return true;
	}
	/* ]]> */
    </script>";
	if ($inventorycheck) $inventorycheckstring = " onsubmit=\"return checkinventory();\"";
	else $inventorycheckstring = "";
	if ($testmode) {
		if ($signed) echo "<form method=\"POST\"$inventorycheckstring action=\"https://sandbox.google.com/checkout/api/checkout/v2/checkout/Merchant/$googlemerchantid\" accept-charset=\"utf-8\">\n";
		else echo "<form method=\"POST\"$inventorycheckstring action=\"https://sandbox.google.com/checkout/api/checkout/v2/checkoutForm/Merchant/$googlemerchantid\" accept-charset=\"utf-8\">\n";
	} else {
		if ($signed) echo "<form method=\"POST\"$inventorycheckstring action=\"https://checkout.google.com/api/checkout/v2/checkout/Merchant/$googlemerchantid\" accept-charset=\"utf-8\">\n";
		else echo "<form method=\"POST\"$inventorycheckstring action=\"https://checkout.google.com/api/checkout/v2/checkoutForm/Merchant/$googlemerchantid\" accept-charset=\"utf-8\">\n";
	}
	$googlecartxml = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<checkout-shopping-cart xmlns=\"http://checkout.google.com/schema/2\">\n<shopping-cart>\n<items>";
	$googlecartshipping = 0;
	$totalamount = 0;
	for ($i = 0; $i < count($items)-$arraycount; $i++) {
		$thissegment = $items[$i]."a";
		$itemnumber = $i+1;
		$thisitem = explode("b", $items[$i]);
		$thisquantity = $thisitem[0];
		settype($thisquantity, 'float');
		$prethisproductid = $thisitem[count($thisitem)-1];
		$thisproductid = explode("d", $prethisproductid);
		$thisproductid = $thisproductid[0];
		settype($thisproductid, 'integer');
		$parameterstring = "";
		$disableshipping = 0;
		$attributeprice = "";
		$parameterarray = array();
		$download = "all";
		if (count($thisitem > 2)) {
			$sql = "SELECT * FROM parameters WHERE productid='$thisproductid' ORDER BY parameterid";
			$result = @mysqli_query($databaseconnection, "$sql");
			if (@mysqli_num_rows($result)) {
				for ($j = 0; $j < @mysqli_num_rows($result); $j++) {
					$caption = @mysqli_result($result, $j, "caption");
					$parameterid = @mysqli_result($result, $j, "parameterid");
					$valueid = $thisitem[$j+1];
					$checkvalues = @mysqli_query($databaseconnection, "SELECT * FROM parametervalues WHERE parameterid='$parameterid'");
					$sql = "SELECT * FROM parametervalues WHERE valueid='$valueid' AND parameterid='$parameterid'";
					$subresult = @mysqli_query($databaseconnection, "$sql");
					if (!@mysqli_num_rows($subresult)) {
						$subresult = @mysqli_query($databaseconnection, "SELECT * FROM customparametervalues WHERE valueid='$valueid' AND parameterid='$parameterid'");
						$download = "all";
						$thisdownload = "all";
						$iscustomerparameter = true;
					} else {
						$thisdownload = @mysqli_result($subresult, 0, "download");
						$noshipping = @mysqli_result($subresult, 0, "noshipping");
						$thisattributeprice = @mysqli_result($subresult, 0, "price");
						if (isset($thisattributeprice)) $attributeprice = $thisattributeprice;
						$iscustomerparameter = false;
					}
					$value = @mysqli_result($subresult, 0, "value");
					if ($download == "all") $download = $thisdownload;
					if ($noshipping) $disableshipping = 1;
					if ($caption && !$value) $parameterstring .= "[$caption: unset]";
					else {
						if (@mysqli_num_rows($checkvalues) > 1 || $iscustomerparameter) $parameterstring .= "[$caption: $value]";
						$parameterarray[] = $value;
					}
				}
			}
		}
		if ($thisitem[0] != "sh" && $thisitem[0] != "st" && !strstr($thisitem[0], "so") && $thisitem[0] != "sd") {
			$result = @mysqli_query($databaseconnection, "SELECT * FROM product WHERE productid='$thisproductid'");
			if (!$attributeprice) $productprice = @mysqli_result($result, 0, "price");
			else $productprice = $attributeprice;
			$totalamount += $productprice;
			$productname = @mysqli_result($result, 0, "name");
			if (!empty($parameterstring)) $productname .= " $parameterstring";
			$productdescription = strip_tags(@mysqli_result($result, 0, "description"));
			$productdescription = substr($productdescription,0,254);
			$shipping = @mysqli_result($result, 0, "shipping");
			if (empty($shipping)) $shipping = @mysqli_result($result, 0, "intshipping");
			$googlecartxml .= "\n<item>\n<item-name>$productname</item-name>\n<item-description>$productdescription</item-description>\n<unit-price currency=\"$currency\">$productprice</unit-price>\n<quantity>$thisquantity</quantity>\n<merchant-item-id>$affiliate-$thissegment</merchant-item-id>";
			if (!$signed) echo "
			<input type=\"hidden\" name=\"item_name_$itemnumber\" value=\"$productname\"/>
			<input type=\"hidden\" name=\"item_description_$itemnumber\" value=\"$productdescription\"/>
			<input type=\"hidden\" name=\"item_quantity_$itemnumber\" value=\"$thisquantity\"/>
			<input type=\"hidden\" name=\"item_price_$itemnumber\" value=\"$productprice\"/>
			<input type=\"hidden\" name=\"item_currency_$itemnumber\" value=\"$currency\"/>
			<input type=\"hidden\" name=\"item_merchant_id_$itemnumber\" value=\"$affiliate-$thissegment\"/>";
			if ($download != "none" && (empty($shipping) || $disableshipping == 1)) {
				if (!$signed) echo "
				<input type=\"hidden\" name=\"shopping-cart.items.item-$itemnumber.digital-content.display-disposition\" value=\"OPTIMISTIC\"/>
				<input type=\"hidden\" name=\"shopping-cart.items.item-$itemnumber.digital-content.email-delivery\" value=\"true\"/>";
				$googlecartxml .= "\n<digital-content>\n<display-disposition>OPTIMISTIC</display-disposition>\n<email-delivery>true</email-delivery>\n</digital-content>";
			} else {
				if (!$signed) echo "
				<input type=\"hidden\" name=\"checkout-flow-support.merchant-checkout-flow-support.shipping-methods.flat-rate-shipping-1.name\" value=\"Shipping\"/>
				<input type=\"hidden\" name=\"checkout-flow-support.merchant-checkout-flow-support.shipping-methods.flat-rate-shipping-1.price\" value=\"$shipping\"/>
				<input type=\"hidden\" name=\"checkout-flow-support.merchant-checkout-flow-support.shipping-methods.flat-rate-shipping-1.price.currency\" value=\"$currency\"/>";
				$googlecartshipping += $shipping;
				$totalamount += $shipping;
			}
			$googlecartxml .= "\n</item>";
		}
	}
	$googlecartxml .= "\n</items>\n<merchant-private-data>\n<merchant-note>".$_SERVER["REMOTE_ADDR"]."</merchant-note>\n</merchant-private-data>\n</shopping-cart>";
	if ($googlecartshipping) $googlecartxml .= "\n<checkout-flow-support>\n<merchant-checkout-flow-support>\n<shipping-methods>\n<flat-rate-shipping name=\"Shipping\"><price currency=\"$currency\">$googlecartshipping</price></flat-rate-shipping>\n</shipping-methods>\n</merchant-checkout-flow-support>\n</checkout-flow-support>";
	else $googlecartxml .= "\n<checkout-flow-support>\n<merchant-checkout-flow-support />\n</checkout-flow-support>";
	$googlecartxml .= "\n</checkout-shopping-cart>";
	if ($signed) {
		$googlecartsignature = ashop_hmacsha1($googlecartxml,$googlekey);
		$googlecartsignature = base64_encode($googlecartsignature);
		$googlecartxml = base64_encode($googlecartxml);
		echo "<input type=\"hidden\" name=\"cart\" value=\"$googlecartxml\" /><input type=\"hidden\" name=\"signature\" value=\"$googlecartsignature\" />";
	}

	if (!empty($totalamount) && $totalamount > 0) {
		if ($button == 1) echo "
		<input type=\"image\" name=\"Google Checkout\" alt=\"Fast checkout through Google\" src=\"http://checkout.google.com/buttons/checkout.gif?merchant_id=$googlemerchantid&w=180&h=46&style=white&variant=text&loc=en_US\" height=\"46\" width=\"180\"/></form>";
		else echo "
		<input type=\"image\" name=\"Google Checkout\" alt=\"Fast checkout through Google\" src=\"http://checkout.google.com/buttons/buy.gif?merchant_id=$googlemerchantid&w=117&h=48&style=white&variant=text&loc=en_US\" height=\"48\" width=\"117\"/></form>";
	} else echo "</form>";
}

// Parse the shipping and handling part of a product string into an array...
function ashop_gethandlingcost($productstring) {
	$items = explode("a", $productstring);
	$arraycount = 1;
	if ($items[0] && count($items)==1) $arraycount = 0;
	for ($i = 0; $i < count($items)-$arraycount; $i++) {
		$thisitem = explode("b", $items[$i]);
		if ($thisitem[0] == "sh") $handlinginfo["shipping"] = $thisitem[1];
		if ($thisitem[0] == "st") $handlinginfo["salestax"] = $thisitem[1];
		if (strstr($thisitem[0], "so")) $handlinginfo[$thisitem[0]] = str_replace("so", "", $thisitem[0]);
		if ($thisitem[0] == "sd") $handlinginfo["discount"] = $thisitem[1];
	}
	return $handlinginfo;
}

// Remove the shipping and handling part of a product string...
function ashop_striphandlingcost($productstring) {
	$newproductstring = "";
	$items = explode("a", $productstring);
	$arraycount = 1;
	if ($items[0] && count($items)==1) $arraycount = 0;
	for ($i = 0; $i < count($items)-$arraycount; $i++) {
		$thisitem = explode("b", $items[$i]);
		if (($thisitem[0] != "sh") && ($thisitem[0] != "st") && (!strstr($thisitem[0], "so")) && ($thisitem[0] != "sd")) $newproductstring .= $items[$i]."a";
	}
	return $newproductstring;
}

// Check if a product is included in a product string...
function ashop_checkproduct($productid, $productstring, $attributecheck="") {
	$totalquantity = 0;
	$items = explode("a", $productstring);
	$arraycount = 1;
	if ($items[0] && count($items)==1) $arraycount = 0;
	for ($i = 0; $i < count($items)-$arraycount; $i++) {
		$thisitem = explode("b", $items[$i]);
		$thisquantity = $thisitem[0];
		$prethisproductid = $thisitem[count($thisitem)-1];
		$thisproductid = explode("d", $prethisproductid);
		$thisproductid = $thisproductid[0];
		if ($thisproductid == $productid && $thisitem[0] != "sh" && $thisitem[0] != "st" && !strstr($thisitem[0], "so") && $thisitem[0] != "sd") {
			if (!empty($attributecheck)) {
				$attributearray = explode(":",$attributecheck);
				$attributenumber = $attributearray[0];
				$attributevalue = $attributearray[1];
				if ($thisitem[$attributenumber] == $attributevalue) $totalquantity += $thisquantity;
			} else $totalquantity += $thisquantity;
		}
	}
	return $totalquantity;
}

// Extract a product string for one member...
function ashop_memberproductstring($databaseconnection, $productstring, $member) {
	$newproductstring = "";
	$items = explode("a", $productstring);
	$arraycount = 1;
	if ($items[0] && count($items)==1) $arraycount = 0;
	for ($i = 0; $i < count($items)-$arraycount; $i++) {
		$thisitem = explode("b", $items[$i]);
		$prethisproductid = $thisitem[count($thisitem)-1];
		$thisproductid = explode("d", $prethisproductid);
		$thisproductid = $thisproductid[0];
		if ($thisitem[0] != "sh" && $thisitem[0] != "st" && !strstr($thisitem[0], "so") && $thisitem[0] != "sd") {
			$result = @mysqli_query($databaseconnection, "SELECT * FROM product WHERE productid='$thisproductid' AND userid='$member'");
			if (@mysqli_num_rows($result)) $newproductstring .= $items[$i]."a";
		}
	}
	return $newproductstring;
}

// Check for duplicate customer input attributes...
function ashop_duplicatecheck($parameterid, $value) {
	global $db;
	$result = @mysqli_query($db, "SELECT * FROM customparametervalues WHERE parameterid='$parameterid' AND value LIKE '$value'");
	return @mysqli_num_rows($result);
}

// Remove unused customer input attributes...
function ashop_cleanattributes($parameterid, $productid) {
	global $db;
	$onehourago = time()+$timezoneoffset-3600;
	$result1 = @mysqli_query($db, "SELECT * FROM customparametervalues WHERE parameterid='$parameterid' AND timstamp<'$onehourago'");
	while ($row = @mysqli_fetch_array($result1)) {
		$parametervalueid = $row["valueid"];
		$result2 = @mysqli_query($db, "SELECT * FROM orders WHERE products LIKE '%b$productid"."a%' AND (products LIKE '$parametervalueid"."b%' OR products LIKE 'b$parametervalueid"."b%') AND date IS NOT NULL AND date != ''");
		if (!@mysqli_num_rows($result2)) @mysqli_query($db, "DELETE FROM customparametervalues WHERE valueid='$parametervalueid'");
		else {
			$orderid = @mysqli_result($result2, 0, "orderid");
			$products = @mysqli_result($result2, 0, "products");
			if (!ashop_checkproduct($productid, $products)) @mysqli_query($db, "DELETE FROM customparametervalues WHERE valueid='$parametervalueid'");
		}
	}
}

// Generate 4 digit numerical random code for TeleSign verification...
function ashop_telesigncode() {
	$num = array(1, 2, 3, 4, 5, 6, 7, 8, 9);
	srand ((double) microtime() * 1000000);
	$pwLength = "4";
	for($i = 1; $i <=$pwLength; $i++) {
		$newcode .= $num[(rand(0,8))];
	}
	return ($newcode);
}

// Request a verification call from TeleSign...
function ashop_telesigncall($telesigncode, $country, $phonenumber) {
	global $telesignid, $telesignauthid, $ashoppath;
	$countrycodes = array ("United States"=>"1", "Albania"=>"355", "Algeria"=>"213", "American Samoa"=>"684", "Andorra"=>"376", "Anguilla"=>"1", "Antigua &amp; Barbuda"=>"1", "Argentina"=>"54", "Aruba"=>"297", "Australia"=>"61", "Austria"=>"43", "Azores"=>"351", "Bahamas"=>"1", "Bahrain"=>"973", "Bangladesh"=>"880", "Barbados"=>"1", "Belgium"=>"32", "Belize"=>"501", "Belarus"=>"375", "Benin"=>"229", "Bermuda"=>"1", "Bolivia"=>"591", "Bonaire"=>"599", "Bosnia"=>"387", "Botswana"=>"267", "Brazil"=>"55", "British Virgin Islands"=>"1", "Brunei"=>"673", "Bulgaria"=>"359", "Burkina Faso"=>"226", "Burundi"=>"257", "Cambodia"=>"855", "Cameroon"=>"237", "Canada"=>"1", "Canary Islands"=>"34", "Cape Verde Islands"=>"238", "Cayman Islands"=>"1", "Central African Republic"=>"236", "Chad"=>"235", "Channel Islands"=>"1", "Chile"=>"56", "China, Peoples Republic of"=>"86", "Colombia"=>"57", "Congo"=>"242", "Cook Islands"=>"682", "Costa Rica"=>"506", "Croatia"=>"385", "Curacao"=>"599", "Cyprus"=>"357", "Czech Republic"=>"420", "Denmark"=>"45", "Djibouti"=>"253", "Dominica"=>"1", "Dominican Republic"=>"1", "Ecuador"=>"593", "Egypt"=>"20", "El Salvador"=>"503", "Equitorial Guinea"=>"240", "Eritrea"=>"291", "Estonia"=>"372", "Ethiopia"=>"251", "Faeroe Islands"=>"298", "Federated States of Micronesia"=>"691", "Fiji"=>"679", "Finland"=>"358", "France"=>"33", "French Guiana"=>"594", "French Polynesia"=>"689", "Gabon"=>"241", "Gambia"=>"220", "Georgia"=>"995", "Germany"=>"49", "Ghana"=>"233", "Gibraltar"=>"350", "Greece"=>"30", "Greenland"=>"299", "Grenada"=>"1", "Guadeloupe"=>"590", "Guam"=>"1", "Guatemala"=>"502", "Guinea"=>"224", "Guinea-Bissau"=>"245", "Guyana"=>"592", "Haiti"=>"509", "Honduras"=>"504", "Hong Kong"=>"852", "Hungary"=>"36", "Iceland"=>"354", "India"=>"91", "Indonesia"=>"62", "Ireland"=>"353", "Israel"=>"972", "Italy"=>"39", "Ivory Coast"=>"225", "Jamaica"=>"1", "Japan"=>"81", "Jordan"=>"962", "Kazakhstan"=>"7", "Kenya"=>"254", "Kiribati"=>"686", "Kosrae"=>"691", "Kuwait"=>"965", "Kyrgyzstan"=>"996", "Laos"=>"856", "Latvia"=>"371", "Lebanon"=>"961", "Lesotho"=>"266", "Liberia"=>"231", "Liechtenstein"=>"423", "Lithuania"=>"370", "Luxembourg"=>"352", "Macau"=>"853", "Macedonia"=>"389", "Madagascar"=>"261", "Madeira"=>"351", "Malawi"=>"265", "Malaysia"=>"60", "Maldives"=>"960", "Mali"=>"223", "Malta"=>"356", "Marshall Islands"=>"692", "Martinique"=>"596", "Mauritania"=>"222", "Mauritius"=>"230", "Mexico"=>"52", "Moldova"=>"373", "Monaco"=>"377", "Montserrat"=>"1", "Morocco"=>"212", "Mozambique"=>"258", "Myanmar"=>"95", "Namibia"=>"264", "Nepal"=>"977", "Netherlands"=>"31", "Netherlands Antilles"=>"599", "New Caledonia"=>"687", "New Zealand"=>"64", "Nicaragua"=>"505", "Niger"=>"227", "Nigeria"=>"234", "Norfolk Island"=>"672", "Northern Mariana Islands"=>"1", "Norway"=>"47", "Oman"=>"968", "Pakistan"=>"92", "Palau"=>"680", "Panama"=>"507", "Papua New Guinea"=>"675", "Paraguay"=>"595", "Peru"=>"51", "Philippines"=>"63", "Poland"=>"48", "Ponape"=>"691", "Portugal"=>"351", "Puerto Rico"=>"1", "Qatar"=>"974", "Republic of Yemen"=>"967", "Reunion"=>"262", "Romania"=>"40", "Rota"=>"1", "Russia"=>"7", "Rwanda"=>"250", "Saba"=>"599", "Saipan"=>"1", "Saudi Arabia"=>"966", "Senegal"=>"221", "Seychelles"=>"248", "Sierra Leone"=>"232", "Singapore"=>"65", "Slovakia"=>"421", "Slovenia"=>"386", "Solomon Islands"=>"677", "South Africa"=>"27", "South Korea"=>"82", "Spain"=>"34", "Sri Lanka"=>"94", "St. Barthelemy"=>"1", "St. Christopher"=>"1", "St. Croix"=>"1", "St. Eustatius"=>"1", "St. John"=>"1", "St. Kitts &amp; Nevis"=>"1", "St. Lucia"=>"1", "St. Maarten"=>"1", "St. Martin"=>"1", "St. Thomas"=>"1", "St. Vincent &amp; the Grenadines"=>"1", "Sudan"=>"249", "Suriname"=>"597", "Swaziland"=>"268", "Sweden"=>"46", "Switzerland"=>"41", "Syria"=>"963", "Tahiti"=>"689", "Taiwan"=>"886", "Tajikistan"=>"992", "Tanzania"=>"255", "Thailand"=>"66", "Tinian"=>"1", "Togo"=>"228", "Tonga"=>"676", "Tortola"=>"1", "Trinidad &amp; Tobago"=>"1", "Truk"=>"691", "Tunisia"=>"216", "Turkey"=>"90", "Turks &amp; Caicos Islands"=>"1", "Tuvalu"=>"688", "Uganda"=>"256", "Ukraine"=>"380", "Union Island"=>"1", "United Arab Emirates"=>"971", "United Kingdom"=>"44", "Uruguay"=>"598", "US Virgin Islands"=>"1", "Uzbekistan"=>"998", "Vanuatu"=>"678", "Venezuela"=>"58", "Vietnam"=>"84", "Virgin Gorda"=>"1", "Wake Island"=>"1", "Wallis &amp; Futuna Islands"=>"681", "Western Samoa"=>"685", "Yap"=>"691", "Yugoslavia"=>"381", "Zaire"=>"243", "Zambia"=>"260", "Zimbabwe"=>"263");
	$countrycode = $countrycodes["$country"];
	$phonenumber = str_replace("-","",$phonenumber);
	$phonenumber = str_replace(" ","",$phonenumber);
	$phonenumber = str_replace("(","",$phonenumber);
	$phonenumber = str_replace(")","",$phonenumber);
	$phonenumber = str_replace("]","",$phonenumber);
	$phonenumber = str_replace("[","",$phonenumber);
	$phonenumber = str_replace("}","",$phonenumber);
	$phonenumber = str_replace("{","",$phonenumber);
	$phonenumber = str_replace(".","",$phonenumber);
	$phonenumber = str_replace(",","",$phonenumber);
	if (substr($phonenumber,0,1) == "0") while (substr($phonenumber,0,1) == "0") {
		$phonenumber = substr($phonenumber,1);
		if (!$phonenumber) break;
	}
	
	$phoneidheaders[] = "Content-Type: text/xml; charset=utf-8";
	$phoneidheaders[] = "SOAPAction: \"https://www.telesign.com/api/RequestPhoneID\"";
	$phoneidrequest = "<?xml version=\"1.0\" encoding=\"utf-8\"?>\n<soap:Envelope xmlns:xsi=\"http://www.w3.org/2001/XMLSchema-instance\"
xmlns:xsd=\"http://www.w3.org/2001/XMLSchema\"\nxmlns:soap=\"http://schemas.xmlsoap.org/soap/envelope/\">\n<soap:Body>\n<RequestPhoneID xmlns=\"https://www.telesign.com/api/\">\n<CustomerID>$telesignid</CustomerID>\n<AuthenticationID>$telesignauthid</AuthenticationID>\n<CountryCode>$countrycode</CountryCode>\n<PhoneNumber>$phonenumber</PhoneNumber>\n</RequestPhoneID>\n</soap:Body>\n</soap:Envelope>";

	$ch = curl_init();
	if (file_exists("$ashoppath/admin/curl.inc.php")) include "$ashoppath/admin/curl.inc.php";
	curl_setopt ($ch, CURLOPT_URL,"https://api.telesign.com/1.x/soap.asmx");
	curl_setopt($ch, CURLOPT_HTTPHEADER, $phoneidheaders);
	curl_setopt($ch, CURLOPT_POST, 1);
	curl_setopt($ch, CURLOPT_POSTFIELDS, "$phoneidrequest");
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
	curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);
	$phoneidresult = curl_exec ($ch);
	curl_close ($ch);
	$phoneidresult = explode("<TypeofPhone>",$phoneidresult);
	$phoneidresult = explode("</TypeofPhone>",$phoneidresult[1]);
	if(!$phoneidresult[0] || ($phoneidresult[0] != "301" && $phoneidresult[0] != "302" && $phoneidresult[0] != "310")) return "2";
	else {
		$callrequestheaders[] = "Content-Type: text/xml; charset=utf-8";
		$callrequestheaders[] = "SOAPAction: \"https://www.telesign.com/api/RequestCALL\"";
		$callrequest = "<?xml version=\"1.0\" encoding=\"utf-8\"?>\n<soap:Envelope xmlns:xsi=\"http://www.w3.org/2001/XMLSchema-instance\"\nxmlns:xsd=\"http://www.w3.org/2001/XMLSchema\"\nxmlns:soap=\"http://schemas.xmlsoap.org/soap/envelope/\">\n<soap:Body>\n<RequestCALL xmlns=\"https://www.telesign.com/api/\">\n<CustomerID>$telesignid</CustomerID>\n<AuthenticationID>$telesignauthid</AuthenticationID>\n<CountryCode>$countrycode</CountryCode>\n<PhoneNumber>$phonenumber</PhoneNumber>\n<VerificationCode>$telesigncode</VerificationCode>\n<Priority>0</Priority>\n<DelayTime>0</DelayTime>\n<RedialCount>4</RedialCount>\n</RequestCALL>\n</soap:Body>\n</soap:Envelope>";

		$ch = curl_init();
		if (file_exists("$ashoppath/admin/curl.inc.php")) include "$ashoppath/admin/curl.inc.php";
		curl_setopt ($ch, CURLOPT_URL,"https://api.telesign.com/1.x/soap.asmx");
		curl_setopt($ch, CURLOPT_HTTPHEADER, $callrequestheaders);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, "$callrequest");
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
		curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);
		$callrequestresult = curl_exec ($ch);
		curl_close ($ch);
		if ($callrequestresult) return "SUCCESS";
		else return 1;
	}
}

// Combine the same products in a shopping cart string...
function ashop_combineproducts($productstring) {
	$parts = explode("a",$productstring);
	if (!isset($newparts)) $newparts = array();
	if ($parts) foreach ($parts as $part) {
		$quantity = substr($part,0,strpos($part,"b"));
		$restpart = substr($part,strpos($part,"b"));
		$restpart = str_replace("d","",$restpart);
		if (isset($newparts["$restpart"])) $newparts["$restpart"] += $quantity;
		else $newparts["$restpart"] = $quantity;
	}
	$newbasket = "";
	if ($newparts) foreach ($newparts as $part=>$quantity) if ($quantity && $part) $newbasket .= "$quantity$part"."a";
	return $newbasket;
}

// Calculate the subtotal of a product...
function ashop_subtotal($databaseconnection, $productid, $subtotalqty, $quantity, $discountcode, $productprice, $qtypricetype) {
	global $discountall, $pricelevel, $percentstorediscount;
	if (!empty($_COOKIE["customersessionid"]) && ashop_is_md5($_COOKIE["customersessionid"])) {
		$customerresult = @mysqli_query($databaseconnection, "SELECT customerid FROM customer WHERE sessionid='{$_COOKIE["customersessionid"]}'");
		$customerid = @mysqli_result($customerresult,0,"customerid");
	}
	if (empty($pricelevel)) $pricelevel = 0;
	if ($qtypricetype == "3" || $qtypricetype == "4") {
		$qtyresult = @mysqli_query($databaseconnection, "SELECT * FROM qtypricelevels WHERE productid='$productid' AND customerlevel='$pricelevel' ORDER BY levelquantity DESC");
		if (@mysqli_num_rows($qtyresult)) {
			$price = 0;
			while ($row = @mysqli_fetch_array($qtyresult)) {
				$qtylevel = $row["levelquantity"];
				$qtyprice = $row["levelprice"];
				if ($subtotalqty > $qtylevel) {
					$qtyabove = $subtotalqty - $qtylevel;
					$subtotalqty = $qtylevel;
					if ($quantity > $qtyabove) {
						$thisqty = $qtyabove;
						$quantity = $quantity - $qtyabove;
					} else {
						$thisqty = $quantity;
						$quantity = 0;
					}
				} else $thisqty = 0;
				if ($thisqty > 0) $price += $thisqty*$qtyprice;
			}
		} else $price = $productprice;
	} else if ($qtypricetype == "1" || $qtypricetype == "2") {
		$qtyresult = @mysqli_query($databaseconnection, "SELECT * FROM qtypricelevels WHERE productid='$productid' AND levelquantity<'$subtotalqty' AND customerlevel='$pricelevel' ORDER BY levelquantity DESC");
		if (@mysqli_num_rows($qtyresult)) {
			$row = @mysqli_fetch_array($qtyresult);
			$qtylevel = $row["levelquantity"];
			$qtyprice = $row["levelprice"];
			$price = $quantity*$qtyprice;
		} else $price = $productprice;
	} else $price = $productprice;
	if (!empty($discountcode)) {
		$discounted = FALSE;
		// Check per product discount...
		$result2 = @mysqli_query($databaseconnection, "SELECT * FROM discount WHERE productid='$productid'");
		if (@mysqli_num_rows($result2)) {
			while ($discountrow = @mysqli_fetch_array($result2)) {
				$discountid = $discountrow["discountid"];
				$checkdiscountcode= $discountrow["code"];
				$discounttype = $discountrow["type"];
				$discountvalue = $discountrow["value"];
				$onetime = $discountrow["onetime"];
				$discountcustomerid = $discountrow["customerid"];
				$discountallowed = FALSE;
				if (empty($discountcustomerid) || $customerid == $discountcustomerid) $discountallowed = TRUE;
				$discountcookiestring = md5($productid.$checkdiscountcode."ashopdiscounts");
				if ($discountallowed && ($discountcode == $discountcookiestring || !$checkdiscountcode)) {
					if ($discounttype == "%") $price = $price - ($price * ($discountvalue/100));
					else if ($discounttype == "$") $price -= $discountvalue;
					$discounted = TRUE;
				}
			}
		}
		// Check per category discount...
		if (!$discounted) {
			$result2 = @mysqli_query($databaseconnection, "SELECT categoryid FROM productcategory WHERE productid='$productid'");
			while ($categoryrow = @mysqli_fetch_array($result2)) {
				$categoryid = $categoryrow["categoryid"];
				$result3 = @mysqli_query($databaseconnection, "SELECT * FROM storediscounts WHERE categoryid='$categoryid'");
				while ($categorydiscountrow = @mysqli_fetch_array($result3)) {
					$discountid = $categorydiscountrow["discountid"];
					$checkdiscountcode= $categorydiscountrow["code"];
					$discounttype = $categorydiscountrow["type"];
					$discountvalue = $categorydiscountrow["value"];
					$discountcookiestring = md5($checkdiscountcode."ashopdiscounts");
					if ($discountcode == $discountcookiestring || !$checkdiscountcode) {
						if ($discounttype == "%") $price = $price - ($price * ($discountvalue/100));
						else if ($discounttype == "$") $price -= $discountvalue;
						$discounted = TRUE;
					}
				}
			}
		}
	}
	if (!@mysqli_num_rows($qtyresult)) $price = $price * $quantity;
	// Apply per order discount...
	if ($discountall) {
		$result2 = @mysqli_query($databaseconnection, "SELECT * FROM storediscounts WHERE discountid='$discountall'");
		$discounttype = @mysqli_result($result2, 0, "type");
		$discountvalue = @mysqli_result($result2, 0, "value");
		if ($discounttype && $discountvalue && $discounttype == "%") {
			$percentstorediscount = $price * ($discountvalue/100);
			$price = $price - $percentstorediscount;
		}
	}
	if ($price < 0) $price = 0;
	return($price);
}

// Remove zero quantity products...
function ashop_nozeroqty($productstring) {
	$newprodstring = "";
	$newbasket = "";
	$productstring = str_replace("-","",$productstring);
	for ($i = 0; $i < strlen($productstring); $i++) {
		$thischar =  substr($productstring,$i,1);
		if ($thischar != "a" && $thischar != "b" && $thischar != "d") if (!is_numeric($thischar)) unset($thischar);
		$newprodstring .= $thischar;
	}
	$productstring = $newprodstring;
	$parts = explode("a",$productstring);
	if ($parts) foreach ($parts as $part) {
		if ($part) {
			if (substr($part,0,2) == "0b") $newbasket .= substr_replace($part,"1b",0,2);
			else $newbasket .= $part."a";
		}
	}
	return $newbasket;
}

// Apply discounts on a product string...
function ashop_applydiscounts($databaseconnection, $productstring) {
	global $tempcookie;
	// Remove any existing discount markers...
	$productstring = str_replace("d","",$productstring);
	// Check all products for discounts...
	$newbasket = "";
	$parts = explode("a",$productstring);
	if ($parts) foreach ($parts as $part) {
		$hasbeendiscounted = FALSE;
		if ($part) {
			$newbasket .= $part;
			$thisitem = explode("b", $part);
			if (($thisitem[0] == "sh") || ($thisitem[0] == "st") || (strstr($thisitem[0], "so")) || ($thisitem[0] == "sd")) $thisisshippingortax = 1;
			else $thisisshippingortax = 0;
			$thisproductid = $thisitem[count($thisitem)-1];
			if (isset($_SESSION) && is_array($_SESSION)) foreach ($_SESSION as $cookiename=>$cookievalue) {
				if (strstr($cookiename,"discount")) {
					$discountid = str_replace("discount","",$cookiename);
					$thisproductdiscount = $cookievalue;
					$sql="SELECT * FROM discount WHERE productid='$thisproductid' AND discountid='$discountid'";
					$result2 = @mysqli_query($databaseconnection, "$sql");
					if (@mysqli_num_rows($result2)) {
						$discountcode = @mysqli_result($result2, 0, "code");
						$onetime = @mysqli_result($result2, 0, "onetime");
						$discountcookiestring = md5($thisproductid.$discountcode."ashopdiscounts");
						if ($thisproductdiscount == $discountcookiestring) {
							$newbasket .= "d";
							$hasbeendiscounted = TRUE;
						}
					} else {
						$sql="SELECT * FROM storediscounts WHERE discountid='$discountid' AND categoryid!='' AND categoryid IS NOT NULL";
						$result2 = @mysqli_query($databaseconnection,"$sql");
						if (@mysqli_num_rows($result2)) {
							$discountcategory = @mysqli_result($result2, 0, "categoryid");
							$result3 = @mysqli_query($databaseconnection,"SELECT * FROM productcategory WHERE productid='$thisproductid' AND categoryid='$discountcategory'");
							if (@mysqli_num_rows($result3)) {
								$discountcode = @mysqli_result($result2, 0, "code");
								$discountcookiestring = md5($discountcode."ashopdiscounts");
								if ($thisproductdiscount == $discountcookiestring) {
									$newbasket .= "d";
									$hasbeendiscounted = TRUE;
								}
							}
						}
					}
				}
			}
			if (isset($tempcookie) && is_array($tempcookie)) foreach ($tempcookie as $cookiename=>$cookievalue) {
				$discountid = str_replace("discount","",$cookiename);
				$thisproductdiscount = $cookievalue;
				$sql="SELECT * FROM discount WHERE productid=$thisproductid AND discountid='$discountid'";
				$result2 = @mysqli_query($databaseconnection, "$sql");
				if (@mysqli_num_rows($result2)) {
					$discountcode= @mysqli_result($result2, 0, "code");
					$onetime = @mysqli_result($result2, 0, "onetime");
					$discountcookiestring = md5($thisproductid.$discountcode."ashopdiscounts");
					if ($thisproductdiscount == $discountcookiestring && !$hasbeendiscounted) $newbasket .= "d";
				} else {
					$sql="SELECT * FROM storediscounts WHERE discountid='$discountid' AND categoryid!='' AND categoryid IS NOT NULL";
					$result2 = @mysqli_query($databaseconnection, "$sql");
					if (@mysqli_num_rows($result2)) {
						$discountcategory = @mysqli_result($result2, 0, "categoryid");
						$result3 = @mysqli_query($databaseconnection, "SELECT * FROM productcategory WHERE productid='$thisproductid' AND categoryid='$discountcategory'");
						if (@mysqli_num_rows($result3)) {
							$discountcode = @mysqli_result($result2, 0, "code");
							$discountcookiestring = md5($discountcode."ashopdiscounts");
							if ($thisproductdiscount == $discountcookiestring && !$hasbeendiscounted) $newbasket .= "d";
						}
					}
				}
			}
			$newbasket .= "a";
		}
	}
	return $newbasket;
}

// Get total quantity of products in product string...
function ashop_totalqty($productstring) {
	$totalquantity = 0;
	$newbasket = "";
	$parts = explode("a",$productstring);
	if ($parts) foreach ($parts as $part) {
		if ($part) {
			$newbasket .= $part;
			$thisitem = explode("b", $part);
			$thisquantity = $thisitem[0];
			if ($thisitem[0] != "sh" && $thisitem[0] != "st" && !strstr($thisitem[0], "so") && $thisitem[0] != "sd") $totalquantity += $thisquantity;
		}
	}
	if (!isset($totalquantity)) $totalquantity = 0;
	return $totalquantity;
}

// Get total quantity of products from a specified category in product string...
function ashop_categoryqty($databaseconnection, $productstring, $categoryid) {
	$quantity = 0;
	$parts = explode("a",$productstring);
	if ($parts) foreach ($parts as $part) {
		$thisitem = explode("b", $part);
		$thisquantity = $thisitem[0];
		$prethisproductid = $thisitem[count($thisitem)-1];
		$thisproductid = explode("d", $prethisproductid);
		$thisproductid = $thisproductid[0];
		$checkresult = @mysqli_query($databaseconnection, "SELECT productid FROM productcategory WHERE productid='$thisproductid' AND categoryid='$categoryid' LIMIT 1");
		if (@mysqli_num_rows($checkresult)) $quantity += $thisquantity;
	}
	return $quantity;
}

// Get the quantity of a specific product in a shopping cart string...
function ashop_getquantity($productid, $productstring) {
	$quantity = 0;
	$parts = explode("a",$productstring);
	if ($parts) foreach ($parts as $part) {
		$thisitem = explode("b", $part);
		$thisquantity = $thisitem[0];
		$prethisproductid = $thisitem[count($thisitem)-1];
		$thisproductid = explode("d", $prethisproductid);
		$thisproductid = $thisproductid[0];
		if ($thisproductid == $productid) $quantity += $thisquantity;
	}
	return $quantity;
}

// Check bid code...
function ashop_checkbidcode($databaseconnection, $bidhash="") {
	global $ashoppath;
	if (!empty($bidhash)) {
		$bidderhash = explode("|",$bidhash);
		$thisbidder = $bidderhash[0];
		$bidderresult = @mysqli_query($databaseconnection, "SELECT * FROM pricebidder WHERE bidderid='$thisbidder'");
		$thisbidcode = @mysqli_result($bidderresult,0,"bidcode");
		$checkcode = md5($ashoppath.$thisbidcode);
		if ($checkcode == $bidderhash[1]) return TRUE;
		else return FALSE;
	} else if (!empty($_COOKIE["customersessionid"]) && preg_match("/^[0-9a-f]{32}$/", $_COOKIE["customersessionid"])) {
		$customerresult = @mysqli_query($databaseconnection, "SELECT customerid FROM customer WHERE sessionid='{$_COOKIE["customersessionid"]}'");
		$customerid = @mysqli_result($customerresult,0,"customerid");
		$bidderresult = @mysqli_query($databaseconnection, "SELECT bidderid FROM pricebidder WHERE bidcode='$customerid' AND customerid='$customerid'");
		if (@mysqli_num_rows($bidderresult)) {
			$bidderid = @mysqli_result($bidderresult,0,"bidderid");
			$_COOKIE["bidderhash"] = $bidderid."|ok";
			return TRUE;
		} else return FALSE;
	} else return FALSE;
}

// Check if bid code matches final bid...
function ashop_checkfinalbid($databaseconnection, $bidderid, $productid) {
	$floatingpriceresult = @mysqli_query($databaseconnection, "SELECT * FROM floatingprice WHERE productid='$productid' AND bidderid='$bidderid' AND endprice IS NOT NULL AND endprice != ''");
	if (@mysqli_num_rows($floatingpriceresult)) return TRUE;
	else return FALSE;
}

// Post to a URL asynchronously...
function ashop_postasync($url, $params=array()) {
	if (!empty($params)) {
		foreach ($params as $key => &$val) {
			if (is_array($val)) $val = implode(',', $val);
			$post_params[] = $key.'='.urlencode($val);
		}
		$post_string = implode('&', $post_params);
	}

    $parts=parse_url($url);

    $fp = fsockopen($parts['host'],
        isset($parts['port'])?$parts['port']:80,
        $errno, $errstr, 30);

    if (!$fp) return FALSE;
	else {
		$out = "POST ".$parts['path']." HTTP/1.1\r\n";
		$out.= "Host: ".$parts['host']."\r\n";
		$out.= "Content-Type: application/x-www-form-urlencoded\r\n";
		$out.= "Content-Length: ".strlen($post_string)."\r\n";
		$out.= "Connection: Close\r\n\r\n";
		if (isset($post_string)) $out.= $post_string;
		fwrite($fp, $out);
		fclose($fp);
	}
}

// Check available unlock key codes...
function ashop_checkfreecodes($databaseconnection, $productid) {
	// Get main code inventory, if one exists...
	$coderesult = @mysqli_query($databaseconnection, "SELECT keyid FROM unlockkeys WHERE productid='$productid' LIMIT 1");
	$codesexist = @mysqli_num_rows($coderesult);
	$codeinventoryresult = @mysqli_query($databaseconnection, "SELECT COUNT(keyid) AS codeinventory FROM unlockkeys WHERE productid='$productid' AND orderid IS NULL OR orderid=''");
	$codeinventory = @mysqli_result($codeinventoryresult,0,"codeinventory");
	$codeproductid = $productid;

	// Check for keypool inventories...
	$fulfilmentresult = @mysqli_query($databaseconnection, "SELECT fulfiloptionid FROM fulfiloptions WHERE method='keypool'");
	if (@mysqli_num_rows($fulfilmentresult)) {
		$fulfilmentid = @mysqli_result($fulfilmentresult,0,"fulfiloptionid");
		$productfulfilmentresult = @mysqli_query($databaseconnection, "SELECT ffproductid FROM product WHERE productid='$productid' AND fulfilment='$fulfilmentid'");
		if (@mysqli_num_rows($productfulfilmentresult)) {
			$fulfilmentproducts = @mysqli_result($productfulfilmentresult,0,"ffproductid");
			$fulfilmentproducts = str_replace("|",",",$fulfilmentproducts);
			$fulfilmentproducts = str_replace(":",",",$fulfilmentproducts);
			$fulfilmentproducts = str_replace(";",",",$fulfilmentproducts);
			if (!empty($fulfilmentproducts)) {
				$fulfilmentproducts = explode(",",$fulfilmentproducts);
				foreach ($fulfilmentproducts as $fulfilmentproductid) {
					$ffcoderesult = @mysqli_query($databaseconnection, "SELECT keyid FROM unlockkeys WHERE productid='$fulfilmentproductid' LIMIT 1");
					$ffcodesexist = @mysqli_num_rows($ffcoderesult);
					$ffcodeinventoryresult = @mysqli_query($databaseconnection, "SELECT COUNT(keyid) AS codeinventory FROM unlockkeys WHERE productid='$fulfilmentproductid' AND orderid IS NULL OR orderid=''");
					$ffcodeinventory = @mysqli_result($ffcodeinventoryresult,0,"codeinventory");
					if ($ffcodesexist) {
						if ($ffcodeinventory < $codeinventory || !$codesexist) {
							$codeinventory = $ffcodeinventory;
							$codeproductid = $fulfilmentproductid;
						}
						$codesexist = 1;
					}
				}
			}
		}
	}
	return $codesexist."|".$codeinventory."|".$codeproductid;
}

// Sign a URL for protected AWS use...
function ashop_getsignedawsurl($resource, $timeout, $keypairid)
{
	global $ashoppath;
	$expires = time() + $timeout; //Time out in seconds
	$json = '{"Statement":[{"Resource":"'.$resource.'","Condition":{"DateLessThan":{"AWS:EpochTime":'.$expires.'}}}]}';		
	
	//Read Cloudfront Private Key Pair
	$fp=fopen("$ashoppath/includes/aws/pk-$keypairid.pem","r"); 
	$priv_key=fread($fp,8192); 
	fclose($fp); 

	//Create the private key
	$key = openssl_get_privatekey($priv_key);
	if(!$key)
	{
		echo "<p>Failed to load private key!</p>";
		return;
	}

	//Sign the policy with the private key
	if(!openssl_sign($json, $signed_policy, $key, OPENSSL_ALGO_SHA1))
	{
		echo '<p>Failed to sign policy: '.openssl_error_string().'</p>';
		return;
	}
	
	//Create url safe signed policy
	$base64_signed_policy = base64_encode($signed_policy);
	$signature = str_replace(array('+','=','/'), array('-','_','~'), $base64_signed_policy);

	//Construct the URL
	$url = $resource.'?Expires='.$expires.'&Signature='.$signature.'&Key-Pair-Id='.$keypairid;
	
	return $url;
}
?>