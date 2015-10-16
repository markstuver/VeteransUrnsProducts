<?php
$popuplogincheck = TRUE;
if ($_COOKIE["sid"]) {
	unset($_COOKIE["sid"]);
	$sid = $_GET["sid"];
}
include "../admin/checklicense.inc.php";
include "checklogin.inc.php";

// Open database...
$db = @mysqli_connect("$databaseserver", "$databaseuser", "$databasepasswd", "$databasename");

// Get mail parameters...
if ($mailbox == "varchive" || $vid) {
	$paramresult = @mysqli_query($db, "SELECT * FROM emerchant_configuration");
	while ($paramrow = @mysqli_fetch_array($paramresult)) {
		if ($paramrow["confname"] == "pophost2") $hostname = $paramrow["confvalue"];
		if ($paramrow["confname"] == "popuser2") $popuser = $paramrow["confvalue"];
		if ($paramrow["confname"] == "poppass2") $poppass = $paramrow["confvalue"];
		if ($paramrow["confname"] == "popport2") $port = $paramrow["confvalue"];
	}
} else {
	$paramresult = @mysqli_query($db, "SELECT * FROM emerchant_configuration");
	while ($paramrow = @mysqli_fetch_array($paramresult)) {
		if ($paramrow["confname"] == "pophost") $hostname = $paramrow["confvalue"];
		if ($paramrow["confname"] == "popuser") $popuser = $paramrow["confvalue"];
		if ($paramrow["confname"] == "poppass") $poppass = $paramrow["confvalue"];
		if ($paramrow["confname"] == "popport") $port = $paramrow["confvalue"];
	}
}

if ($mailbox == "archive") {
	$result = @mysqli_query($db, "SELECT * FROM emerchant_messages WHERE id='$id'");
	$msgprefix = "cust-";
} else if ($mailbox == "varchive") {
	$result = @mysqli_query($db, "SELECT * FROM emerchant_vendormessages WHERE id='$id'");
	$msgprefix = "vend-";
} else if ($vid) {
	$result = @mysqli_query($db, "SELECT * FROM emerchant_inbox2 WHERE id='$vid'");
	$msgprefix = "in2-";
} else if ($sid) {
	$result = @mysqli_query($db, "SELECT * FROM emerchant_spam WHERE id='$sid'");
	$msgprefix = "spam-";
	$id = $sid;
} else {
	$result = @mysqli_query($db, "SELECT * FROM emerchant_inbox WHERE id='$id'");
	$msgprefix = "in1-";
}
$messagerow = @mysqli_fetch_array($result);
if ($mailbox == "archive") {
	$result = @mysqli_query($db, "SELECT firstname, lastname, email FROM customer WHERE customerid='{$messagerow["customerid"]}'");
	if (isset($messagerow["replyto"])) {
		$result2 = @mysqli_query($db, "SELECT * FROM emerchant_configuration WHERE confname='customeremail'");
		$messagerow["email"] = @mysqli_result($result2, 0, "confvalue");
		$messagerow["name"] = $ashopname;
	} else {
		$messagerow["email"] = @mysqli_result($result,0,"email");
		$messagerow["name"] = @mysqli_result($result,0,"firstname")." ".@mysqli_result($result,0,"lastname");
	}
	$messagerow["received"] = $messagerow["date"];
} else if ($mailbox == "varchive") {
	$result = @mysqli_query($db, "SELECT name, email FROM emerchant_vendor WHERE vendorid='{$messagerow["vendorid"]}'");
	if (isset($messagerow["replyto"])) {
		$result2 = @mysqli_query($db, "SELECT * FROM emerchant_configuration WHERE confname='vendoremail'");
		$messagerow["email"] = @mysqli_result($result2, 0, "confvalue");
		$messagerow["name"] = $ashopname;
	} else {
		if ($messagerow["vendcontactid"]) {
			$result2 = @mysqli_query($db, "SELECT * FROM emerchant_vendcontact WHERE vendcontactid='{$messagerow["vendcontactid"]}'");
			if (@mysqli_num_rows($result2)) {
				$vendcontactrow = @mysqli_fetch_array($result2);
				$messagerow["email"] = $vendcontactrow["email"];
			} else $messagerow["email"] = @mysqli_result($result,0,"email");
		} else $messagerow["email"] = @mysqli_result($result,0,"email");
		$messagerow["name"] = @mysqli_result($result,0,"name");
	}
	$messagerow["received"] = $messagerow["date"];
} else $messagerow["received"] = date("Y-m-d H:i", $messagerow["received"]);
$switchdate = @mysqli_query($db, "SELECT * FROM emerchant_configuration WHERE confname='switchdate'");
$before = @mysqli_result($switchdate,0,"confvalue");
@mysqli_close($db);

// Open connection to mail server...
if ($hostname == "mail.yourdomain.com" || !$hostname) $mailnotconfigured = 1;
else $mailnotconfigured = 0;
$authenticated = false;
if (!$mailnotconfigured) {
	$popfp = @imap_open ("{"."$hostname:$port/notls"."}INBOX", "$popuser", "$poppass");
	if($popfp) $authenticated = true;
}

// Read message...
if ($authenticated) $structure = @imap_fetchstructure($popfp,$messagerow["uid"],FT_UID);

unset($attachments);

if ($structure->parts) foreach ($structure->parts as $partnumber=>$part) {
    $partbody = @imap_fetchbody($popfp,$messagerow["uid"],$partnumber+1,FT_UID);
	$partdisposition = strtolower($part->disposition);
	$partsubtype = strtolower($part->subtype);
	$attachmentfilename = $part->dparameters[0]->value;
	$attachmentfilename = imap_qprint($attachmentfilename);
	$attachmentfilename = str_replace("=","",$attachmentfilename);
	$attachmentfilename = str_replace("?Q","",$attachmentfilename);
	$attachmentfilename = str_replace("?","",$attachmentfilename);
	$attachmentfilename = str_replace(" ","_",$attachmentfilename);
	$attachmentfilename = str_replace("x-unknown","",$attachmentfilename);
	$attachmentfilename = str_replace("iso-8859-1","",$attachmentfilename);
	if ($attachmentfilename) $partdisposition = "attachment";
	if ($part->encoding == "4") $partbody = @imap_qprint($partbody);
	else if ($part->encoding == "3") $partbody = @imap_base64($partbody);
	if ($partsubtype == "html" && $partdisposition != "attachment") $htmlmessage = $partbody;
	else if ($partsubtype == "plain" && $partdisposition != "attachment") $textmessage = $partbody;
	else if ($part->parts && $partdisposition != "attachment") {
		$partparts = $part->parts;
		if ($partparts) foreach ($partparts as $subpartnumber=>$subpart) {
			$primarypart = $partnumber+1;
			$thissubpart = $subpartnumber+1;
			$subpartbody = @imap_fetchbody($popfp,$messagerow["uid"],$primarypart.".".$thissubpart,FT_UID);
			if ($subpart->encoding == "4") $subpartbody = @imap_qprint($subpartbody);
			else if ($subpart->encoding == "3") $subpartbody = @imap_base64($subpartbody);
			if (strtolower($subpart->subtype) == "html") $htmlmessage = $subpartbody;			
			if (strtolower($subpart->subtype) == "plain") $textmessage = $subpartbody;
		}
	}
	else if ($partdisposition == "attachment") {
		if (!$attachmentfilename) $attachmentfilename = "attachment.txt";
		if ($part->type == "2") {
			$contents = $partbody;
			$attachpartparts = $part->parts;
			if ($attachpartparts) foreach ($attachpartparts as $subpart) {
				$contents .= $subpart->body;
				$attachpartpartparts = $subpart->parts;
				if ($attachpartpartparts) foreach ($attachpartpartparts as $subsubpart) {
					$contents .= $subsubpart->body;
				}
			}
		} else $contents = $partbody;
		if ($download == $partnumber) {
			if ($part->type = "0") $primarytype = "text";
			else if ($part->type = "1") $primarytype = "multipart";
			else if ($part->type = "2") $primarytype = "message";
			else if ($part->type = "3") $primarytype = "application";
			else if ($part->type = "4") $primarytype = "audio";
			else if ($part->type = "5") $primarytype = "image";
			else if ($part->type = "6") $primarytype = "video";
			else if ($part->type = "7") $primarytype = "other";
			header("Content-Type: $primarytype/".strtolower($part->subtype));
			header ("Content-Disposition: ".$part->disposition."; filename=$attachmentfilename");
			echo $contents;
			exit;
		} else {
			if (!$part->c_id) $attachments[$partnumber] = $attachmentfilename;
			else $embedded[$part->c_id] = $partnumber;
		}
	}
}

if (!$htmlmessage && !$textmessage) {
	$subtype = strtolower($structure->subtype);
	if ($subtype == "html") $htmlmessage = @imap_body($popfp,$messagerow["uid"],FT_UID);
	else $textmessage = @imap_body($popfp,$messagerow["uid"],FT_UID);
}

@imap_close($popfp);

if ($htmlmessage) {
	if (is_array($embedded)) foreach($embedded as $cid=>$filenumber) $htmlmessage = str_replace("cid:$cid","readmessageimap.php?id=$id&vid=$vid&download=$filenumber&mailbox=$mailbox",$htmlmessage);
	$message = $htmlmessage;
} else {
	$message = str_replace("<","&lt;",$textmessage);
	$message = str_replace(">","&gt;",$message);
	$message = str_replace("\n","<br>",$message);
}

echo "<html>
<head>
<title>Read Message - $ashopname</title>
<meta http-equiv=\"Content-Type\" content=\"text/html; charset=iso-8859-1\">
<link rel=\"stylesheet\" href=\"emerchant.css\" type=\"text/css\">
<script language=\"javascript\" type=\"text/javascript\">
<!--
self.resizeTo(800, 630);
-->
</script>
</head>
<body bgcolor=\"#FFFFFF\" text=\"#000000\">
  <table width=\"760\" border=\"0\" cellspacing=\"2\" cellpadding=\"1\" align=\"center\">
    <tr> 
      <td class=\"formlabel\" align=\"right\">From: </td>
      <td class=\"sm\">{$messagerow["name"]} &lt;{$messagerow["email"]}&gt;</td>
	  <td width=\"60\" class=\"sm\">";
	  if (!$sid) {
		echo "<a href=\"composemessageimap.php?reply";
		if ($mailbox == "varchive" || $vid) echo "vendor";
		echo "={$messagerow["id"]}";
		if ($mailbox) echo "&mailbox=$mailbox";
		echo "\"><img src=\"images/icon_reply.gif\" alt=\"Reply to this message.\" border=\"0\"></a>&nbsp;";
	  } else echo "&nbsp;";
	  if (!$sid) {
		echo "<a href=\"javascript: opener.window.location.href='";
		if ($vid) echo "vendormail.php";
		else echo "inquiries.php";
		echo "?delete={$messagerow["id"]}'; this.close();\"><img src=\"images/icon_trash.gif\" width=\"15\" height=\"15\" alt=\"Delete this message.\" border=\"0\"></a>&nbsp;";
	  } else echo "&nbsp;";
	  echo "<a href=\"javascript: opener.window.location.href='";
	  if ($vid) echo "vendormail.php";
	  else if ($sid) echo "spam.php";
	  else echo "inquiries.php";
	  echo "?save={$messagerow["id"]}'; this.close();\"><img src=\"images/icon_save.gif\" alt=\"Save this message\" border=\"0\"></a>";
	  echo "</td>
    </tr>
    <tr> 
      <td class=\"formlabel\" align=\"right\">Subject: </td>
      <td class=\"sm\" colspan=\"2\">{$messagerow["subject"]}</td>
    </tr>
    <tr> 
      <td class=\"formlabel\" align=\"right\">Received: </td>
      <td class=\"sm\" colspan=\"2\">{$messagerow["received"]}</td>
    </tr>";
if ($attachments) {
	echo "<tr> 
      <td class=\"formlabel\" align=\"right\">Attachments: </td>
      <td class=\"sm\" colspan=\"2\">";
	foreach($attachments as $attachmentnumber=>$filename) echo "<a href=\"readmessageimap.php?id=$id&vid=$vid&download=$attachmentnumber&mailbox=$mailbox\"><img src=\"images/icon_attachment.gif\" border=\"0\"></a> <a href=\"readmessageimap.php?id=$id&vid=$vid&download=$attachmentnumber&mailbox=$mailbox\">$filename</a>&nbsp;&nbsp;";
	echo "</td></tr>";
}

echo "</table>
  <table width=\"760\" height=\"90%\" border=\"1\" bordercolor=\"#000000\" cellspacing=\"0\" cellpadding=\"5\" align=\"center\">
    <tr align=\"left\"> 
      <td valign=\"top\"><br>$message<br></td>
    </tr>
  </table>
</body>
</html>";
?>