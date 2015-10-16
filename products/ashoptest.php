<?php
@set_time_limit(0);
if (isset($_POST["action"])) $action = $_POST["action"];
else $action = "";
if (!isset($REQUEST_URI) and isset($_SERVER['SCRIPT_NAME']))
{
    $REQUEST_URI = $_SERVER['SCRIPT_NAME'];
    if (isset($_SERVER['QUERY_STRING']) and
!empty($_SERVER['QUERY_STRING']))
        $REQUEST_URI .= '?' . $_SERVER['QUERY_STRING'];
}

if (strtolower(substr(php_uname(),0,3)) == "win") $extension = "dll";
else $extension = "so";
$current_ion_file="ioncube_loader_".strtolower(substr(php_uname(),0,3))."_".substr(phpversion(),0,3).".{$extension}";
$ashoppath = getcwd();
if (!file_exists("$ashoppath/ioncube/$current_ion_file")) {
	// Get ioncube loader for current php version...
	$ionquerystring = "ion=".phpversion()."&os=".strtolower(substr(php_uname(),0,3));
	$header = "POST /getion.php HTTP/1.0\r\nHost: www.ashopsoftware.com\r\nContent-Type: application/x-www-form-urlencoded\r\nContent-Length: ".strlen ($ionquerystring)."\r\n\r\n";
	$fp = @fsockopen ("www.ashopsoftware.com", 80, $errno, $errstr, 10);
	if ($fp) {
		if (is_dir("$ashoppath/ioncube")) {
			$ionfp = @fopen ("$ashoppath/ioncube/$current_ion_file", "w");
			if ($ionfp) {
				fputs ($fp, $header . $ionquerystring);
				$endheader = false;
				$contentcheck = false;
				while (!feof($fp)) {
					while (!$endheader) if (fgets($fp) == "\r\n") $endheader = true;
					$res = fread ($fp, 8192);
					if (!$res && !$contentcheck) {
						echo "IonCube error! No valid loader available!";
						exit;
					} else $contentcheck = true;
					fwrite($ionfp, $res);
				}
				fclose($ionfp);
				@chmod("$ashoppath/ioncube/$current_ion_file", 0777);
			} else {
				// Dynamic IonCube loading usually never works anymore and is rarely needed so these conditions have been commented out:
				//echo "Could not store the new IonCube loader! Check permissions on the ioncube directory.";
				//exit;
			}
		} else {
			//echo "The directory named \"ioncube\" does not exist! Please create it and set permissions to 777.";
			//exit;
		}
		fclose($fp);
	} else {
		//echo "Could not connect to AShop Software to get new IonCube loader!";
		//exit;
	}
}


if (ini_get("register_globals") != 1 || !get_magic_quotes_gpc()) {
	foreach ($_SERVER as $key => $value) {
		if (!get_magic_quotes_gpc() && !empty($value) && !is_array($value)) $value = addslashes($value);
		eval("if(!isset(\$$key)) \$$key = \"$value\";");
	}
	foreach ($_COOKIE as $key => $value) {
		if (!get_magic_quotes_gpc()) $value = addslashes($value);
		eval("if(!isset(\$$key)) \$$key = \"$value\";");
	}
	foreach ($_POST as $key => $value) {
		if (!get_magic_quotes_gpc()) $value = addslashes($value);
		eval("if(!isset(\$$key)) \$$key = \"$value\";");
	}
	foreach ($_GET as $key => $value) {
		if (!get_magic_quotes_gpc()) $value = addslashes($value);
		eval("if(!isset(\$$key)) \$$key = \"$value\";");
	}
}
if ($action == "fsockopentest") {
	echo "SUCCESS";
	exit;
}

if ($action == "iontest") {
	if(!extension_loaded('ionCube Loader')) {
		if (strtolower(substr(php_uname(),0,3)) == "win") $extension = "dll";
		else $extension = "so";
		$ion_file="ioncube_loader_".strtolower(substr(php_uname(),0,3))."_".substr(phpversion(),0,3).".{$extension}";
		$ion_cwd=getcwd();
		if($ion_cwd[1]==":")$ion_cwd=substr($ion_cwd,2);
		$ion_try=str_repeat("../",12).$ion_cwd."//";
		$timeout = 100;
		while((strlen($ion_try)>0) && !extension_loaded('ionCube Loader') && $timeout > 0){
			$ion_try=substr($ion_try,0,strlen($ion_try)-1);
			@dl($ion_try."ioncube/".$ion_file);
			$timeout--;
		}
	}
	if (extension_loaded('ionCube Loader') && function_exists("ioncube_loader_version")) {
		$ioncube_loader_version = ioncube_loader_version();
		echo "SUCCESS:$ioncube_loader_version";
		exit;
	} else exit;
}

echo "<html><head><title>AShop Compatibility Test</title></head><body bgcolor=\"#FFFFFF\" textcolor=\"#000000\"><br><br><h3>AShop Compatibility Test</h3><ul>";

// Check if configuration can be read...
if (!function_exists('ini_get')) { echo "<li>Configuration cannot be read! Further testing is not possible!</li>"; exit; }

// Check PHP version...
$phpversion = phpversion();
if ($phpversion < "5.0.0") echo "<li>Too Old Version of PHP - <font color=\"#FF0000\">FAILED</font></li>";
else {
	echo "<li>At least version 5.0.0 of PHP (You have version $phpversion";
	if (php_sapi_name() == "cgi") echo ", CGI";
	if (strstr(php_sapi_name(), "apache")) echo ", Apache Module";
	if (php_sapi_name() == "isapi") echo ", ISAPI Module";
	echo ") - <font color=\"#009900\">PASSED</font></li>";
}

// Check Safe Mode...
$safemode = ini_get("safe_mode");
if ($safemode) echo "<li>Safe Mode = On - <font color=\"#FF0000\">FAILED</font></li>";
else echo "<li>Safe Mode = Off - <font color=\"#009900\">PASSED</font></li>";

// Check File Uploads...
$fileuploads = ini_get("file_uploads");
$uploadsizelimit = ini_get("upload_max_filesize");
if (!$fileuploads) echo "<li>File Uploads = Off - <font color=\"#FF0000\">FAILED</font></li>";
else echo "<li>File Uploads = On (Size limit: $uploadsizelimit) - <font color=\"#009900\">PASSED</font></li>";

// Check MySQL...
if (!function_exists('mysqli_query')) echo "<li>No MySQL Functions - <font color=\"#FF0000\">FAILED</font></li>";
else echo "<li>MySQL Functions Available - <font color=\"#009900\">PASSED</font></li>";

// Check Curl...
if (function_exists('curl_version')) {
	$curlversion = curl_version();
	if ((!is_array($curlversion) && strstr($curlversion, "SSL")) || (is_array($curlversion) && (strstr($curlversion["ssl_version"], "SSL") || strstr($curlversion["ssl_version"], "NSS")))) echo "<li>Curl With SSL Available - <font color=\"#009900\">PASSED</font></li>";
	else echo "<li>No Curl with SSL - <font color=\"#FF0000\">Some payment gateways won't be supported!</font></li>";
} else echo "<li>No Curl with SSL - <font color=\"#FF0000\">Some payment gateways won't be supported!</font></li>";

// Check Error Reporting...
if (ini_get('display_errors')) {
	if (ini_get('error_reporting') & E_NOTICE) echo "<li>Error Reporting Level Too High - <font color=\"#FF0000\">FAILED</font></li>";
	else echo "<li>Run-time notices = Off - <font color=\"#009900\">PASSED</font></li>";
} else echo "<li>Run-time notices = Off - <font color=\"#009900\">PASSED</font></li>";

// Check fsockopen()...
if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == "on") $url = "https://";
else $url = "http://";
$url .= $HTTP_HOST.$REQUEST_URI;
$querystring = "action=fsockopentest";
if (@strpos($url, "/", 8)) {
	$urlpath = "/".substr($url, strpos($url, "/", 8)+1);
	$urldomain = substr($url, 0, strpos($url, "/", 8));
} else {
	$urlpath = "/";
	$urldomain = $url;
}
$urldomain = str_replace("http://", "", $urldomain);
$postheader = "POST $urlpath HTTP/1.0\r\nHost: $urldomain\r\nContent-Type: application/x-www-form-urlencoded\r\nContent-Length: ".strlen ($querystring)."\r\n\r\n";
$fp = @fsockopen ($urldomain, 80, $errno, $errstr, 10);
$res = "";
if ($fp) {
	fputs ($fp, $postheader.$querystring);
	while (!feof($fp)) $res .= fgets ($fp, 1024);
	fclose ($fp);
}
if (strstr($res,"SUCCESS")) echo "<li>The fsockopen-function is working - <font color=\"#009900\">PASSED</font></li>";
else echo "<li>The fsockopen-function isn't working, error: $errno $errstr - <font color=\"#FF0000\">FAILED</font></li>";

// Check IonCube encryption...
ob_start();
phpinfo(8);
$phpinfo=ob_get_contents();
ob_end_clean();
if (!stristr($phpinfo,"ioncube")) {
	$querystring = "action=iontest";
	$postheader = "POST $urlpath HTTP/1.0\r\nHost: $urldomain\r\nContent-Type: application/x-www-form-urlencoded\r\nContent-Length: ".strlen ($querystring)."\r\n\r\n";
	$fp = @fsockopen ($urldomain, 80, $errno, $errstr, 10);
	$res = "";
	if ($fp) {
		fputs ($fp, $postheader.$querystring);
		while (!feof($fp) && !strstr($res,"SUCCESS")) $res = fgets ($fp, 1024);
		fclose ($fp);
	}
} else {
	if (extension_loaded('ionCube Loader') && function_exists("ioncube_loader_version")) $res = "SUCCESS:".ioncube_loader_version();
}
if (strstr($res,"SUCCESS")) {
	$ioncubeversion = explode(":",$res);
	if (isset($ioncubeversion[1])) $ioncubeversion = $ioncubeversion[1];
	else $ioncubeversion = "";
	if (stristr($phpinfo,"ioncube")) $ioncubeversion .= " is installed for the whole server in php.ini.";
	if ($ioncubeversion >= "3.1") echo "<li>IonCube decryption is working.<br>IonCube loader version $ioncubeversion - <font color=\"#009900\">PASSED</font></li>";
	else echo "<li>IonCube decryption is working but your IonCube loader is too old.<br>You have version: $ioncubeversion but you need at least version 3.1 - <font color=\"#FF0000\">FAILED</font></li>";
}
else {
	// Check Dynamic Extension Loading...
	$enabledl = ini_get("enable_dl");
	if (!$enabledl) echo "<li>IonCube decryption is not available and could not be dynamically<br>loaded since the dl() function is disabled - <font color=\"#FF0000\">FAILED</font></li>";
	else echo "<li>IonCube decryption isn't working - <font color=\"#FF0000\">FAILED</font></li>";
}

// Check if license key verification server is accessible...
$licquerystring = "test";
$header = "POST /checkkey.php HTTP/1.0\r\nHost: www.ashopsoftware.com\r\nContent-Type: application/x-www-form-urlencoded\r\nContent-Length: ".strlen ($licquerystring)."\r\n\r\n";
$fp = @fsockopen ("www.ashopsoftware.com", 80, $errno, $errstr, 60);
$licensechecked = FALSE;
if ($fp) {
	@stream_set_timeout($fp, 60);
	fputs ($fp, $header . $licquerystring);
	while (!feof($fp)) {
		$res = fgets ($fp, 1024);
		if (strcmp ($res, "INVALID") == 0) $licensechecked = TRUE;
	}
	fclose ($fp);
}
if ($licensechecked) echo "<li>The AShop Software license server can be reached - <font color=\"#009900\">PASSED</font></li>";
else echo "<li>The AShop Software license server cannot be reached, error: $errno $errstr - <font color=\"#FF0000\">FAILED</font></li>";
echo "</ul>";
echo "<p><a href=\"http://www.ashopsoftware.com/ashop-software-requirements.htm\" target=\"_blank\">AShop 
  Software Requirements</a></p>
<p><a href=\"http://www.ashopsoftware.com/ioncube-copy-protection.htm\" target=\"_blank\">About 
  IonCube Decryption</a></p>
<p><a href=\"http://www.ashopsoftware.com/tech-service-request.htm\" target=\"_blank\">Request 
  Technical Support</a></p></body></html>";
?>