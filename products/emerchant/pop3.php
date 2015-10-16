<?php
// Mail fetcher for eMerchant...
@set_time_limit(120);
if (!class_exists("Mail_mimeDecode")) include "mimeDecode.php";

// Open database...
$popdb = @mysqli_connect("$databaseserver", "$databaseuser", "$databasepasswd", "$databasename");

// Check mail lock...
unset($maillock);
$result = @mysqli_query($popdb, "SELECT * FROM emerchant_user WHERE maillock='true'");
if (@mysqli_num_rows($result)) $maillock = TRUE;
else @mysqli_query($popdb, "UPDATE emerchant_user SET maillock='true' WHERE sessionid = '$sesid'");

// Get mail parameters...
$result = @mysqli_query($popdb, "SELECT * FROM emerchant_configuration");
while ($row = @mysqli_fetch_array($result)) {
	if ($row["confname"] == "spamprotection") $spamprotection = $row["confvalue"];
	if ($inbox == 1) {
		if ($row["confname"] == "pophost") $hostname = $row["confvalue"];
		if ($row["confname"] == "popuser") $popuser = $row["confvalue"];
		if ($row["confname"] == "poppass") $poppass = $row["confvalue"];
		if ($row["confname"] == "popport") $port = $row["confvalue"];
		$inboxtablename = "emerchant_inbox";
	} else if ($inbox == 2) {
		if ($row["confname"] == "pophost2") $hostname = $row["confvalue"];
		if ($row["confname"] == "popuser2") $popuser = $row["confvalue"];
		if ($row["confname"] == "poppass2") $poppass = $row["confvalue"];
		if ($row["confname"] == "popport2") $port = $row["confvalue"];
		$inboxtablename = "emerchant_inbox2";
	}
}

if ($hostname == "mail.yourdomain.com" || !$hostname) $mailnotconfigured = 1;
else $mailnotconfigured = 0;

if ( ! function_exists('dopopcommand') ) {
	function dopopcommand($command, $result="") {
		global $popfp;
		if ($popfp) {
			@fputs($popfp, "$command\r\n");
			$result = @fgets($popfp, 4096);
			if (preg_match("/^(\+OK)/i", $result)) return true;
			else return false;
		} else return false;
	}
}

// Open connection to mail server...
if (!$maillock && !$mailnotconfigured) {
	unset($poperror);
	$popfp = @fsockopen($hostname,$port, $errno, $errstr);
	$greeting = @fgets($popfp, 4096);
	if(GetType($greeting)!="string" OR strtok($greeting," ")!="+OK") {
		@fclose($popfp);
		$poperror = "Error! No POP3 greeting!";
		$mailnotconfigured = 1;
	}
	$greeting=strtok("\r\n");
}

if (!$maillock && !$mailnotconfigured) {
	if(dopopcommand("USER $popuser")) if(dopopcommand("PASS $poppass")) $authenticated = true;
	else $authenticated = false;
}
if($authenticated && !$poperror && !$maillock && !$mailnotconfigured) {
	dopopcommand("UIDL");
	unset($messages);
	while (!feof($popfp)) {
		$line = fgets($popfp, 4096);
		if (trim($line) == ".") break;
		$part = explode (" ", $line);
		$part[1] = preg_replace("/[<>]/","",$part[1]);
		$messages[] = $part[0];
	}

	if($messages) foreach($messages as $msg) {
		dopopcommand("RETR $msg");
		$fp2 = @fopen ("$ashoppath/emerchant/mail/new", "w");
		for ($message="";;) {
			$line = fgets($popfp, 4096);			
			if (trim($line) == "." || feof($popfp)) break;
			if (substr($line, 0, 2) == "..") $line = substr($line,1);
			if ($fp2) fwrite($fp2, $line);
			$message .= $line;
		}
		fclose($fp2);
		$msgfilesize = filesize("$ashoppath/emerchant/mail/new");

		// Avoid running out of memory, just discard too large messages...
		if ($msgfilesize > 10000000) {
			unlink("$ashoppath/emerchant/mail/new");
			dopopcommand("DELE $msg");
		} else {
			$params['include_bodies'] = true;
			$params['decode_bodies'] = true;
			$params['decode_headers'] = true;
			$params['input'] = $message;
			$mailmimedecode = new mail_mimedecode($params['input']);
			$structure = $mailmimedecode->decode($params);
			
			// Check for attachments...
			$attachments = 0;
			if ($structure->parts) foreach ($structure->parts as $partnumber=>$part) if ($part->disposition == "attachment") $attachments = 1;

			if (is_array($structure->headers['return-path'])) {
				$returnpath = $structure->headers['return-path'][0];
				$returnpath = str_replace("<","",$returnpath);
				$returnpath = str_replace(">","",$returnpath);
			} else if ($structure->headers['return-path']) {
				$returnpath = $structure->headers['return-path'];
				$returnpath = str_replace("<","",$returnpath);
				$returnpath = str_replace(">","",$returnpath);
			} else $returnpath = "";
			$returnpath = str_replace("'","\'",$returnpath);
			
			if (is_array($structure->headers['from'])) $fromfield = explode("<",$structure->headers['from'][0]);
			else $fromfield = explode("<",$structure->headers['from']);
			if ($fromfield[1]) {
				$fromaddress = rtrim(str_replace(">","",$fromfield[1]));
				$fromname = rtrim(str_replace("\"","",$fromfield[0]));
				if (!$fromname) $fromname = "Unknown";
			} else {
				$fromaddress = rtrim($fromfield[0]);
				$fromname = "Unknown";
			}
			if ($fromname == "Unknown") {
				$nameresult = @mysqli_query($popdb, "SELECT * FROM customer WHERE email='$fromaddress'");
				if (@mysqli_num_rows($nameresult)) $fromname = @mysqli_result($nameresult,0,"firstname")." ".@mysqli_result($nameresult,0,"lastname");
			}
			if (!$fromaddress) $fromaddress = "Unknown";
			$fromaddress = str_replace("'","\'",$fromaddress);
			$fromname = str_replace("'","\'",$fromname);
			
			// Activate spam protection...
			$thisisspam = FALSE;
			if ($spamprotection) {
				if ($fromaddress == "Unknown") $thisisspam = TRUE;
				if ($returnpath && $returnpath != $fromaddress) {
					$checkspamresulta = @mysqli_query($popdb, "SELECT * FROM customer WHERE email='$returnpath' OR alternativeemails LIKE '%$returnpath%'");
					$checkspamresultb = @mysqli_query($popdb, "SELECT * FROM emerchant_inbox WHERE returnpath='$returnpath' OR email='$returnpath'");
					$checkspamresultc = @mysqli_query($popdb, "SELECT * FROM emerchant_inbox2 WHERE returnpath='$returnpath' OR email='$returnpath'");
					$returnpathparts = explode("@",$returnpath);
					$checkspamresultd = @mysqli_query($popdb, "SELECT * FROM emerchant_domains WHERE domain='{$returnpathparts[1]}'");
					if (!@mysqli_num_rows($checkspamresultd) && substr_count($returnpathparts[1],".") > 1) {
						while (substr_count($returnpathparts[1],".") > 1) $returnpathparts[1] = substr($returnpathparts[1],strpos($returnpathparts[1],".")+1);
						$checkspamresultd = @mysqli_query($popdb, "SELECT * FROM emerchant_domains WHERE domain='{$returnpathparts[1]}'");
					}
					if (!@mysqli_num_rows($checkspamresulta) && !@mysqli_num_rows($checkspamresultb) && !@mysqli_num_rows($checkspamresultc) && !@mysqli_num_rows($checkspamresultd)) $thisisspam = TRUE;
				}
				$checkspamresult1 = @mysqli_query($popdb, "SELECT * FROM customer WHERE email='$fromaddress'");
				$checkspamresult2 = @mysqli_query($popdb, "SELECT * FROM emerchant_inbox WHERE email='$fromaddress'");
				$checkspamresult3 = @mysqli_query($popdb, "SELECT * FROM emerchant_inbox2 WHERE email='$fromaddress'");
				$emailparts = explode("@",$fromaddress);
				$checkspamresult5 = @mysqli_query($popdb, "SELECT * FROM emerchant_domains WHERE domain='{$emailparts[1]}'");
				if (!@mysqli_num_rows($checkspamresult5) && substr_count($emailparts[1],".") > 1) {
					while (substr_count($emailparts[1],".") > 1) $emailparts[1] = substr($emailparts[1],strpos($emailparts[1],".")+1);
					$checkspamresult5 = @mysqli_query($popdb, "SELECT * FROM emerchant_domains WHERE domain='{$emailparts[1]}'");
				}
				if (!@mysqli_num_rows($checkspamresult1) && !@mysqli_num_rows($checkspamresult2) && !@mysqli_num_rows($checkspamresult3) && !@mysqli_num_rows($checkspamresult5)) $thisisspam = TRUE;
			}
			
			$receivedarray = $structure->headers['received'];
			if (is_array($receivedarray)) $received = $receivedarray[0];
			else $received = $receivedarray;
			$received = explode("; ",$received);
			if ($received[1]) $received = (strtotime($received[1]))+$timezoneoffset;
			else $received = $timezoneoffset;
			if (!$received) $received = time()+$timezoneoffset;
			$subject = $structure->headers['subject'];
			if (!$subject) $subject = "No subject";
			
			if ($received && $subject && $fromname && $fromaddress && $message) {
				if (strtoupper($subject) == "REMOVE") {
					@mysqli_query($popdb, "UPDATE customer SET allowemail='0' WHERE email='$fromaddress'");
					unlink("$ashoppath/emerchant/mail/new");
					dopopcommand("DELE $msg");
				} else if (!$thisisspam) {
					@mysqli_query($popdb, "INSERT INTO $inboxtablename (received, subject, name, email, returnpath, attachments) VALUES ('$received', '".addslashes($subject)."', '".addslashes($fromname)."', '$fromaddress', '$returnpath', '$attachments')");
					if (@mysqli_affected_rows() == -1) $poperror = "Error! Could not store incoming message in database! MySQL said: ".@mysqli_error();
					else {
						$mailid = @mysqli_insert_id($popdb);
						rename("$ashoppath/emerchant/mail/new","$ashoppath/emerchant/mail/in$inbox-$mailid");
						dopopcommand("DELE $msg");
					}
				} else {
					@mysqli_query($popdb, "INSERT INTO emerchant_spam (received, subject, name, email, returnpath, attachments, source) VALUES ('$received', '".addslashes($subject)."', '".addslashes($fromname)."', '$fromaddress', '$returnpath', '$attachments', '$inbox')");
					if (@mysqli_affected_rows() == -1) $poperror = "Error! Could not store incoming message in database! MySQL said: ".@mysqli_error();
					else {
						$mailid = @mysqli_insert_id($popdb);
						rename("$ashoppath/emerchant/mail/new","$ashoppath/emerchant/mail/spam-$mailid");
						dopopcommand("DELE $msg");
					}
				}
			} else $poperror = "Error! Could not parse message!";
		}
	}

	// Close pop session...
	dopopcommand("QUIT");

} else if (!$maillock && !$mailnotconfigured) $poperror = "Error! Authentication failed!";

// Remove mail lock...
@mysqli_query($popdb, "UPDATE emerchant_user SET maillock=NULL WHERE sessionid = '$sesid'");

// Close connection to mail server...
if($popfp) @fclose($popfp);
@mysqli_close($popdb);
?>