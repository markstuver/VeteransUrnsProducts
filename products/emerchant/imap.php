<?php
// Mail fetcher for eMerchant...
@set_time_limit(120);

// Open database...
$popdb = @mysqli_connect("$databaseserver", "$databaseuser", "$databasepasswd", "$databasename");

// Check mail lock...
unset($maillock);
$result = @mysqli_query($db, "SELECT * FROM emerchant_user WHERE maillock='true'",$popdb);
if (@mysqli_num_rows($result)) $maillock = TRUE;
else @mysqli_query($db, "UPDATE emerchant_user SET maillock='true' WHERE sessionid = '$sesid'",$popdb);

// Get mail parameters...
$result = @mysqli_query($db, "SELECT * FROM emerchant_configuration",$popdb);
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

// Open connection to mail server...
if (!$maillock && !$mailnotconfigured) {
	unset($poperror);
	$popfp = @imap_open ("{"."$hostname:$port/notls"."}INBOX", "$popuser", "$poppass");
	if($popfp) $authenticated = true;
	else $authenticated = false;
}

if($authenticated && !$poperror && !$maillock && !$mailnotconfigured) {
	// Get last fetched message number...
	if ($inbox == "1") {
		$result = @mysqli_query($db, "SELECT uid FROM emerchant_inbox WHERE uid!='' AND uid IS NOT NULL ORDER BY uid DESC LIMIT 1");
		$messagestartinbox = @mysqli_result($result, 0, "uid");
		$result = @mysqli_query($db, "SELECT uid FROM emerchant_messages WHERE uid!='' AND uid IS NOT NULL ORDER BY uid DESC LIMIT 1");
		$messagestartarchive = @mysqli_result($result, 0, "uid");
		$result = @mysqli_query($db, "SELECT uid FROM emerchant_spam WHERE uid!='' AND uid IS NOT NULL AND source!='2' ORDER BY uid DESC LIMIT 1");
		$messagestartspam = @mysqli_result($result, 0, "uid");
		if ($messagestartinbox >= $messagestartarchive && $messagestartinbox >= $messagestartspam) $messagestart = $messagestartinbox;
		else if ($messagestartarchive >= $messagestartinbox && $messagestartinbox >= $messagestartspam) $messagestart = $messagestartarchive;
		else $messagestart = $messagestartspam;
		$messagestart = @imap_msgno($popfp, $messagestart);
		if ($messagestart) $messagestart++;
		else $messagestart = 1;
	} else if ($inbox == "2") {
		$result = @mysqli_query($db, "SELECT uid FROM emerchant_inbox2 WHERE uid!='' AND uid IS NOT NULL ORDER BY uid DESC LIMIT 1");
		$messagestartinbox = @mysqli_result($result, 0, "uid");
		$result = @mysqli_query($db, "SELECT uid FROM emerchant_vendormessages WHERE uid!='' AND uid IS NOT NULL ORDER BY uid DESC LIMIT 1");
		$messagestartarchive = @mysqli_result($result, 0, "uid");
		$result = @mysqli_query($db, "SELECT uid FROM emerchant_spam WHERE uid!='' AND uid IS NOT NULL AND source='2' ORDER BY uid DESC LIMIT 1");
		$messagestartspam = @mysqli_result($result, 0, "uid");
		if ($messagestartinbox >= $messagestartarchive && $messagestartinbox >= $messagestartspam) $messagestart = $messagestartinbox;
		else if ($messagestartarchive >= $messagestartinbox && $messagestartinbox >= $messagestartspam) $messagestart = $messagestartarchive;
		else $messagestart = $messagestartspam;
		$messagestart = @imap_msgno($popfp, $messagestart);
		if ($messagestart) $messagestart++;
		else $messagestart = 1;
	}
	
	$numberofmessages = imap_num_msg($popfp);

	if($numberofmessages) for($messageid=$messagestart; $messageid<=$numberofmessages; $messageid++) {
		$mailuid = imap_uid($popfp, $messageid);
		$mailheader = imap_header($popfp, $messageid);
		$structure = imap_fetchstructure($popfp, $messageid);

		// Check for attachments...
		$attachments = 0;
		if ($structure->parts) foreach ($structure->parts as $partnumber=>$part) if ($part->dparameters[0]->value) $attachments = 1;

		$returnpathobj = $mailheader->return_path;
		$returnpath = $returnpathobj->mailbox."@".$returnpathobj->host;

		$fromfield = $mailheader->fromaddress;
		$from = $mailheader->from;
		foreach ($from as $id => $object) {
			$fromname = $object->personal;
			$fromaddress = $object->mailbox . "@" . $object->host;
		}
		if (!$fromname) $fromname = "Unknown";

		if ($fromname == "Unknown") {
			$nameresult = @mysqli_query($db, "SELECT * FROM customer WHERE email='$fromaddress'",$popdb);
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
				$checkspamresulta = @mysqli_query($db, "SELECT * FROM customer WHERE email='$returnpath' OR alternativeemails LIKE '%$returnpath%'",$popdb);
				$checkspamresultb = @mysqli_query($db, "SELECT * FROM emerchant_inbox WHERE returnpath='$returnpath' OR email='$returnpath'",$popdb);
				$checkspamresultc = @mysqli_query($db, "SELECT * FROM emerchant_inbox2 WHERE returnpath='$returnpath' OR email='$returnpath'",$popdb);
				$returnpathparts = explode("@",$returnpath);
				$checkspamresultd = @mysqli_query($db, "SELECT * FROM emerchant_domains WHERE domain='{$returnpathparts[1]}'");
				if (!@mysqli_num_rows($checkspamresultd) && substr_count($returnpathparts[1],".") > 1) {
					while (substr_count($returnpathparts[1],".") > 1) $returnpathparts[1] = substr($returnpathparts[1],strpos($returnpathparts[1],".")+1);
					$checkspamresultd = @mysqli_query($db, "SELECT * FROM emerchant_domains WHERE domain='{$returnpathparts[1]}'");
				}
				if (!@mysqli_num_rows($checkspamresulta) && !@mysqli_num_rows($checkspamresultb) && !@mysqli_num_rows($checkspamresultc) && !@mysqli_num_rows($checkspamresultd)) $thisisspam = TRUE;
			}
			$checkspamresult1 = @mysqli_query($db, "SELECT * FROM customer WHERE email='$fromaddress'",$popdb);
			$checkspamresult2 = @mysqli_query($db, "SELECT * FROM emerchant_inbox WHERE email='$fromaddress'",$popdb);
			$checkspamresult3 = @mysqli_query($db, "SELECT * FROM emerchant_inbox2 WHERE email='$fromaddress'",$popdb);
			$emailparts = explode("@",$fromaddress);
			$checkspamresult5 = @mysqli_query($db, "SELECT * FROM emerchant_domains WHERE domain='{$emailparts[1]}'");
			if (!@mysqli_num_rows($checkspamresult5) && substr_count($emailparts[1],".") > 1) {
				while (substr_count($emailparts[1],".") > 1) $emailparts[1] = substr($emailparts[1],strpos($emailparts[1],".")+1);
				$checkspamresult5 = @mysqli_query($db, "SELECT * FROM emerchant_domains WHERE domain='{$emailparts[1]}'");
			}
			if (!@mysqli_num_rows($checkspamresult1) && !@mysqli_num_rows($checkspamresult2) && !@mysqli_num_rows($checkspamresult3) && !@mysqli_num_rows($checkspamresult5)) $thisisspam = TRUE;
		}

		$received = time()+$timezoneoffset;
		$subject = $mailheader->subject;
		if (!$subject) $subject = "No subject";

		if ($received && $subject && $fromname && $fromaddress) {
			if (strtoupper($subject) == "REMOVE") @mysqli_query($db, "UPDATE customer SET allowemail='0' WHERE email='$fromaddress'",$popdb);
			else if (!$thisisspam) {
				@mysqli_query($db, "INSERT INTO $inboxtablename (uid, received, subject, name, email, returnpath, attachments) VALUES ('$mailuid', '$received', '".addslashes($subject)."', '".addslashes($fromname)."', '$fromaddress', '$returnpath', '$attachments')",$popdb);
				if (@mysqli_affected_rows() == -1) $poperror = "Error! Could not store incoming message in database! MySQL said: ".@mysqli_error();
				else $mailid = @mysqli_insert_id($db);
			} else {
				@mysqli_query($db, "INSERT INTO emerchant_spam (uid, received, subject, name, email, returnpath, attachments, source) VALUES ('$mailuid', '$received', '".addslashes($subject)."', '".addslashes($fromname)."', '$fromaddress', '$returnpath', '$attachments', '$inbox')",$popdb);
				if (@mysqli_affected_rows() == -1) $poperror = "Error! Could not store incoming message in database! MySQL said: ".@mysqli_error();
				else $mailid = @mysqli_insert_id($db);
			}
		} else $poperror = "Error! Could not parse message!";
		unset($mailheader);
		unset($structure);
	}

	// Close pop session...
	@imap_close($popfp);

} else if (!$maillock && !$mailnotconfigured) $poperror = "Error! POP3 authentication failed!";

// Remove mail lock...
@mysqli_query($db, "UPDATE emerchant_user SET maillock=NULL WHERE sessionid = '$sesid'",$popdb);
?>