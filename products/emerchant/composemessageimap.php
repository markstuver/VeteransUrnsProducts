<?php
$popuplogincheck = TRUE;
$emnoinactivitycheck = "true";
if ($_COOKIE["sid"]) {
	unset($_COOKIE["sid"]);
	$sid = $_GET["sid"];
}
include "../admin/checklicense.inc.php";
include "checklogin.inc.php";
if (!class_exists("Mail_mimeDecode")) include "mimeDecode.php";
$date = date("Y-m-d H:i:s", time()+$timezoneoffset);

// Open database...
$db = @mysqli_connect("$databaseserver", "$databaseuser", "$databasepasswd", "$databasename");

// Get sender mail address...
if ($reply || $customer) $result = @mysqli_query($db, "SELECT * FROM emerchant_configuration WHERE confname='customeremail'");
else $result = @mysqli_query($db, "SELECT * FROM emerchant_configuration WHERE confname='vendoremail'");
$ashopemail = @mysqli_result($result, 0, "confvalue");

if ($reply) {
	// Get mail parameters...
	$paramresult = @mysqli_query($db, "SELECT * FROM emerchant_configuration");
	while ($paramrow = @mysqli_fetch_array($paramresult)) {
		if ($paramrow["confname"] == "pophost") $hostname = $paramrow["confvalue"];
		if ($paramrow["confname"] == "popuser") $popuser = $paramrow["confvalue"];
		if ($paramrow["confname"] == "poppass") $poppass = $paramrow["confvalue"];
		if ($paramrow["confname"] == "popport") $port = $paramrow["confvalue"];
	}
	if ($mailbox == "archive") {
		$result = @mysqli_query($db, "SELECT * FROM emerchant_messages WHERE id='$reply'");
		$messagerow = @mysqli_fetch_array($result);
		$result = @mysqli_query($db, "SELECT * FROM customer WHERE customerid='{$messagerow["customerid"]}'");
		$messagerow["email"] = @mysqli_result($result,0,"email");
		$msgprefix = "cust-";
		$messagerow["received"] = strtotime($messagerow["date"]);
	} else {
		$result = @mysqli_query($db, "SELECT * FROM emerchant_inbox WHERE id='$reply'");
		$messagerow = @mysqli_fetch_array($result);
		$msgprefix = "in1-";
	}
} else if ($replyvendor) {
	// Get mail parameters...
	$paramresult = @mysqli_query($db, "SELECT * FROM emerchant_configuration");
	while ($paramrow = @mysqli_fetch_array($paramresult)) {
		if ($paramrow["confname"] == "pophost2") $hostname = $paramrow["confvalue"];
		if ($paramrow["confname"] == "popuser2") $popuser = $paramrow["confvalue"];
		if ($paramrow["confname"] == "poppass2") $poppass = $paramrow["confvalue"];
		if ($paramrow["confname"] == "popport2") $port = $paramrow["confvalue"];
	}
	if ($mailbox == "varchive") {
		$result = @mysqli_query($db, "SELECT * FROM emerchant_vendormessages WHERE id='$replyvendor'");
		$messagerow = @mysqli_fetch_array($result);
		$msgprefix = "vend-";
		$messagerow["received"] = strtotime($messagerow["date"]);
		$vendor = $messagerow["vendorid"];
		$vendorcontact = $messagerow["vendcontactid"];
		if ($vendorcontact) {
			$vendorresult = @mysqli_query($db, "SELECT * FROM emerchant_vendcontact WHERE vendcontactid='$vendorcontact'");
			$messagerow["email"] = @mysqli_result($vendorresult,0,"email");
		} else {
			$vendorresult = @mysqli_query($db, "SELECT * FROM emerchant_vendor WHERE vendorid='$vendor'");
			$messagerow["email"] = @mysqli_result($vendorresult,0,"email");
		}
	} else {
		$result = @mysqli_query($db, "SELECT * FROM emerchant_inbox2 WHERE id='$replyvendor'");
		$messagerow = @mysqli_fetch_array($result);
		$msgprefix = "in2-";
		$vendorresult = @mysqli_query($db, "SELECT * FROM emerchant_vendcontact WHERE email='{$messagerow["email"]}'");
		if (@mysqli_num_rows($vendorresult)) $vendor = @mysqli_result($vendorresult,0,"vendorid");
		else {
			$vendorresult = @mysqli_query($db, "SELECT * FROM emerchant_vendor WHERE email='{$messagerow["email"]}'");
			if (@mysqli_num_rows($vendorresult)) $vendor = @mysqli_result($vendorresult,0,"vendorid");
		}
	}
} else if ($customer) {
	$result = @mysqli_query($db, "SELECT * FROM customer WHERE customerid='$customer'");
	$messagerow = @mysqli_fetch_array($result);
} else if ($vendor) {
	if ($vendorcontact) $result = @mysqli_query($db, "SELECT * FROM emerchant_vendcontact WHERE vendcontactid='$vendorcontact'");
	else $result = @mysqli_query($db, "SELECT * FROM emerchant_vendor WHERE vendorid='$vendor'");
	$messagerow = @mysqli_fetch_array($result);
}

// Create vendor list...
if ($replyvendor || $vendor) {
	$result = @mysqli_query($db, "SELECT * FROM emerchant_vendor ORDER BY name");
	$vendorselectstring = "<select name=\"vendor\"><option value=\"0\"";
	if (!$vendor) $vendorselectstring .= " selected";
	$vendorselectstring .= ">Select vendor...</option>";
	while ($row = @mysqli_fetch_array($result)) {
		$vendorselectstring .= "<option value=\"{$row["vendorid"]}\"";
		if ($vendor == $row["vendorid"]) $vendorselectstring .= " selected";
		$vendorselectstring .= ">{$row["name"]}</option>";
	}
	$vendorselectstring .= "</select></td></tr><tr><td class=\"formlabel\" bgcolor=\"#d0d0d0\" align=\"right\" valign=\"top\">&nbsp;</td><td class=\"sm\" bgcolor=\"#d0d0d0\">";
}

// Generate and send message...
if ($_POST["recipient"] && $_POST["message"]) {
	if (ini_get('magic_quotes_gpc')) $_POST["message"] = stripslashes($_POST["message"]);
	if (!$_POST["subject"]) $_POST["subject"] = "$ashopname";
	$headers = "From: ".un_html($ashopname,1)."<$ashopemail>\n";
	if ($_POST["ccrecipient"]) $headers .= "CC: {$_POST["ccrecipient"]}\n";
	if ($_POST["bccrecipient"]) $headers .= "BCC: {$_POST["bccrecipient"]}\n";
	$headers .= "X-Sender: <$ashopemail>\nX-Mailer: PHP\nX-Priority: 3\nReturn-Path: <$ashopemail>\nMIME-Version: 1.0\n";

	// Make sure the message isn't magically escaped...
	if (get_magic_quotes_gpc()) {
		$mailsubject = stripslashes($_POST["subject"]);
		$mailmessage = stripslashes($_POST["message"]);
	} else {
		$mailsubject = $_POST["subject"];
		$mailmessage = $_POST["message"];
	}
	// Convert line breaks to make the message readable in regular email clients...
	$mailmessage = str_replace("\r\n","\n",$mailmessage);
	$mailmessage = str_replace("\n\r","\n",$mailmessage);
	$mailmessage = str_replace("\n","\n\r",$mailmessage);

	// Handle attachments...
	if ($attachment) {
		$uploadfilename = preg_replace("/%28|%29|%2B/","",urlencode(basename($attachment_name)));
		$uploadfilename = preg_replace("/%E5|%E4/","a",$uploadfilename);
		$uploadfilename = preg_replace("/%F6/","o",$uploadfilename);
		$uploadfilename = preg_replace("/%C5|%C4/","A",$uploadfilename);
		$uploadfilename = preg_replace("/%D6/","O",$uploadfilename);
		$uploadfilename = preg_replace("/\+\+\+|\+\+/","+",$uploadfilename);
		if (file_exists("$ashoppath/emerchant/mail/attachment")) unlink("$ashoppath/emerchant/mail/attachment");
		@move_uploaded_file($attachment, "$ashoppath/emerchant/mail/attachment");
		$fp = fopen("$ashoppath/emerchant/mail/attachment","r");
		if ($fp) {
			while (!feof ($fp)) $mimeattachment .= fgets($fp, 4096);
			fclose($fp);
			unlink("$ashoppath/emerchant/mail/attachment");
			if ($mimeattachment) $mimeattachment = chunk_split(base64_encode($mimeattachment));
			$mime_boundary = "<<<:" . md5(uniqid(mt_rand(), 1));
			$headers.= "Content-Type: multipart/mixed;\n";
			$headers.= " boundary=\"".$mime_boundary."\"\n";
			$content = "This is a multi-part message in MIME format.\n\n";
			$content.= "--".$mime_boundary."\n";
			$content.= "Content-Type: text/plain; charset=\"iso-8859-1\"\n";
			$content.= "Content-Transfer-Encoding: 7bit\n\n";
			$content.= $mailmessage."\n";
			$content.= "--".$mime_boundary."\n";
			$content.= "Content-Disposition: attachment; filename=\"".$uploadfilename."\"\n";
			$content.= "Content-Type: Application/Octet-Stream;\n";
			$content.= "Content-Transfer-Encoding: base64\n\n";
			$content.= $mimeattachment."\n\n--".$mime_boundary."\"\n";
		}
	} else $content = $mailmessage;	
	
	// Send it...
	@mail($_POST["recipient"],$mailsubject,$content,"$headers");

	// Get vendor ID or add new vendor/contact...
	if ($vendor) {
		$result = @mysqli_query($db, "SELECT * FROM emerchant_vendor WHERE vendorid='$vendor'");
		if ($recipient != @mysqli_result($result,0,"email")) {
			$result = @mysqli_query($db, "SELECT * FROM emerchant_vendcontact WHERE email='$recipient' && vendorid='$vendor'");
			if (@mysqli_num_rows($result)) {
				$vendorcontact = @mysqli_result($result,0,"vendcontactid");
			} else {
				if($messagerow["name"]) {
					$fullname = explode(" ",$messagerow["name"]);
					$vendorfirstname = $fullname[0];
					$vendorlastname = $fullname[1];
				} else {
					$vendorfirstname = "Unknown";
					$vendorlastname = "Unknown";
				}
				@mysqli_query($db, "INSERT INTO emerchant_vendcontact (vendorid, firstname, lastname, email, title, phone) VALUES ('$vendor', '$vendorfirstname', '$vendorlastname', '{$messagerow["email"]}', 'Unknown', 'Unknown')");
				$vendorcontact = @mysqli_insert_id($db);
			}
		}
	} else {


		// Get customer ID or add new customer...
		$result = @mysqli_query($db, "SELECT * FROM customer WHERE email='$recipient'");
		if (@mysqli_num_rows($result)) $customerid = @mysqli_result($result,0,"customerid");
		else {
			if($messagerow["name"]) {
				$fullname = explode(" ",$messagerow["name"]);
				$customerfirstname = $fullname[0];
				$customerlastname = $fullname[1];
			} else {
				$customerfirstname = "Unknown";
				$customerlastname = "Unknown";
			}
			@mysqli_query($db, "INSERT INTO customer (firstname, lastname, email, address, zip, city, state, country, phone) VALUES ('$customerfirstname', '$customerlastname', '{$messagerow["email"]}', 'Unknown', 'Unknown', 'Unknown', 'Unknown', 'Unknown', 'Unknown')");
			$customerid = @mysqli_insert_id($db);
		}
	}

	if (($isreply || $isvendorreply) && $mailbox != "archive") {
		// Store original message in database...
		$receiveddate = date("Y-m-d H:i:s", $messagerow["received"]);
		if ($isvendorreply) {
			@mysqli_query($db, "INSERT INTO emerchant_vendormessages (uid, vendorid, vendcontactid, user, date, subject) VALUES ('{$messagerow["uid"]}', '$vendor', '$vendorcontact', '$emerchant_user', '$receiveddate', '".@mysqli_real_escape_string($db, $messagerow["subject"])."')");
			$originalmessage = @mysqli_insert_id($db);
			@mysqli_query($db, "DELETE FROM emerchant_inbox2 WHERE id='{$messagerow["id"]}'");
		}
		else {
			@mysqli_query($db, "INSERT INTO emerchant_messages (uid, customerid, user, date, subject) VALUES ('{$messagerow["uid"]}', '$customerid', '$emerchant_user', '$receiveddate', '".@mysqli_real_escape_string($db, $messagerow["subject"])."')");
			$originalmessage = @mysqli_insert_id($db);
			@mysqli_query($db, "DELETE FROM emerchant_inbox WHERE id='{$messagerow["id"]}'");
		}
	} else if (($isreply || $isvendorreply) && $mailbox == "archive") {
		if ($isvendorreply) $originalmessage = $replyvendor;
		else $originalmessage = $reply;
	}

	// Store message in database...
	if ($vendor) {
		if (empty($originalmessage) || !is_numeric($originalmessage)) $originalmessage = "0";
		@mysqli_query($db, "INSERT INTO emerchant_vendormessages (vendorid, vendcontactid, user, replyto, date, subject) VALUES ('$vendor', '$vendorcontact', '$emerchant_user', '$originalmessage', '$date', '".@mysqli_real_escape_string($db, $_POST["subject"])."')");
		$messageid = @mysqli_insert_id($db);
		$fp = @fopen ("$ashoppath/emerchant/mail/vend-$messageid", "w");
		if ($fp) {
			fwrite($fp, $mailmessage);
			fclose($fp);
		}
	} else {
		if (empty($originalmessage) || !is_numeric($originalmessage)) $originalmessage = "0";
		@mysqli_query($db, "INSERT INTO emerchant_messages (customerid, user, replyto, date, subject) VALUES ('$customerid', '$emerchant_user', '$originalmessage', '$date', '".@mysqli_real_escape_string($db, $_POST["subject"])."')");
		$messageid = @mysqli_insert_id($db);
		$fp = @fopen ("$ashoppath/emerchant/mail/cust-$messageid", "w");
		if ($fp) {
			fwrite($fp, $mailmessage);
			fclose($fp);
		}
	}

	@mysqli_close($db);
	echo "<html><head><title>Compose Message - $ashopname</title>";
	if ($isreply) {
		if ($mailbox == "archive") {
			if (!empty($history) && is_numeric($history)) echo "<script language=\"JavaScript\">opener.window.location.href='history.php?customer=$history&notice=Message Sent!';</script>";
			else echo "<script language=\"JavaScript\">opener.window.location.href='messages.php?notice=Message Sent!';</script>";
		} else echo "<script language=\"JavaScript\">opener.window.location.href='inquiries.php?notice=Message Sent!';</script>";
	}
	else if ($isvendorreply) {
		if ($mailbox == "archive") echo "<script language=\"JavaScript\">opener.window.location.href='vendormessages.php?notice=Message Sent!';</script>";
		else echo "<script language=\"JavaScript\">opener.window.location.href='vendormail.php?notice=Message Sent!';</script>";
	}
	else echo "<script language=\"JavaScript\">
	if (opener.window.location.pathname.indexOf('customer.php') != -1) opener.window.location.href='http://'+opener.window.location.hostname+opener.window.location.pathname+'?id=$customer&notice=Message Sent!';
	if (opener.window.location.pathname.indexOf('quote.php') != -1) if (opener.document.getElementById('notice')) opener.document.getElementById('notice').innerHTML = 'Message sent';
	if (opener.window.location.pathname.indexOf('purchaseorder.php') != -1) if (opener.document.getElementById('notice')) opener.document.getElementById('notice').innerHTML = 'Message sent';
	if (opener.window.location.pathname.indexOf('history.php') != -1) opener.window.location.href='http://'+opener.window.location.hostname+opener.window.location.pathname+'?customer=$customer&notice=Message Sent!';
	if (opener.window.location.pathname.indexOf('vendorhistory.php') != -1) opener.window.location.href='http://'+opener.window.location.hostname+opener.window.location.pathname+'?vendor=$vendor&notice=Message Sent!';
	</script>";
	echo "</head><body onLoad=\"this.close()\"></body></html>";
	exit;
}

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
	if ($attachmentfilename) $partdisposition = "attachment";
	if ($part->encoding == "4") $partbody = @imap_qprint($partbody);
	else if ($part->encoding == "3") $partbody = @imap_base64($partbody);
	if ($partsubtype == "plain" && $partdisposition != "attachment") $textmessage = $partbody;
	else if ($part->parts && $partdisposition != "attachment") {
		$partparts = $part->parts;
		if ($partparts) foreach ($partparts as $subpartnumber=>$subpart) {
			$primarypart = $partnumber+1;
			$thissubpart = $subpartnumber+1;
			$subpartbody = @imap_fetchbody($popfp,$messagerow["uid"],$primarypart.".".$thissubpart,FT_UID);
			if ($subpart->encoding == "4") $subpartbody = @imap_qprint($subpartbody);
			else if ($subpart->encoding == "3") $subpartbody = @imap_base64($subpartbody);
			if (strtolower($subpart->subtype) == "plain") $textmessage = $subpartbody;
		}
	}
}
if (!$textmessage) $textmessage = @imap_body($popfp,$messagerow["uid"],FT_UID);
@imap_close($popfp);

echo "<html>
<head>
<title>Compose Message - $ashopname</title>
<meta http-equiv=\"Content-Type\" content=\"text/html; charset=iso-8859-1\">
<link rel=\"stylesheet\" href=\"emerchant.css\" type=\"text/css\">
<script language=\"javascript\" type=\"text/javascript\">
<!--
self.resizeTo(800, 630);
-->
</script>
</head>

<body bgcolor=\"#FFFFFF\" text=\"#000000\">
<form method=\"post\" action=\"composemessageimap.php\" enctype=\"multipart/form-data\"> 
  <table width=\"760\" height=\"100%\" border=\"0\" cellspacing=\"2\" cellpadding=\"1\" align=\"center\" bgcolor=\"#d0d0d0\">
    <tr> 
      <td class=\"heading3_wht\" colspan=\"2\" align=\"center\" bgcolor=\"#808080\">Compose 
        Message </td>
    </tr>
    <tr> 
      <td class=\"formlabel\" bgcolor=\"#d0d0d0\" align=\"right\">To: </td>
      <td class=\"sm\" bgcolor=\"#d0d0d0\">";
if ($vendorselectstring) echo "$vendorselectstring";
echo "<input type=\"text\" name=\"recipient\" maxlength=\"80\" size=\"60\" value=\"{$messagerow["email"]}\">
      </td>
    </tr>
    <tr> 
      <td class=\"formlabel\" bgcolor=\"#c0c0c0\" align=\"right\">CC: </td>
      <td class=\"sm\" bgcolor=\"#c0c0c0\"><input type=\"text\" name=\"ccrecipient\" maxlength=\"80\" size=\"60\" value=\"{$messagerow["cc"]}\">
      </td>
    </tr>
    <tr> 
      <td class=\"formlabel\" bgcolor=\"#d0d0d0\" align=\"right\">BCC: </td>
      <td class=\"sm\" bgcolor=\"#d0d0d0\"><input type=\"text\" name=\"bccrecipient\" maxlength=\"80\" size=\"60\" value=\"{$messagerow["bcc"]}\">
      </td>
    </tr>		
    <tr> 
      <td class=\"formlabel\" bgcolor=\"#c0c0c0\" align=\"right\">Subject: </td>
      <td class=\"sm\" bgcolor=\"#c0c0c0\"> 
        <input type=\"text\" name=\"subject\" maxlength=\"80\" size=\"60\" value=\"";
if ($reply || $replyvendor)	echo "Re: {$messagerow["subject"]}";
echo "\">
      </td>
    </tr>
    <tr> 
      <td class=\"formlabel\" bgcolor=\"#d0d0d0\" align=\"right\">Attachment: </td>
      <td class=\"sm\" bgcolor=\"#d0d0d0\"><input type=\"file\" name=\"attachment\" size=\"60\">
      </td>
    </tr>		
    <tr align=\"center\"> 
      <td colspan=\"2\"><b><span class=\"formlabel\">Message</span></b><br>
        <textarea name=\"message\" cols=\"85\" rows=\"";
		if ($replyvendor || $vendor || $vendorcontact) echo "17";
		else echo "18";
		echo "\">";
if ($reply || $replyvendor) echo "\n\n\n\n-----Original Message, received ".date("Y-m-d H:i", $messagerow["received"])."-----\n\n$textmessage";
else if ($_POST["message"]) {
	if (get_magic_quotes_gpc()) $_POST["message"] = stripslashes($_POST["message"]);
	echo $_POST["message"];
}
echo "</textarea>
      </td>
    </tr>
    <tr align=\"center\"> 
      <td colspan=\"2\">";
if ($reply) echo "<input type=\"hidden\" name=\"isreply\" value=\"true\">";
if ($replyvendor) echo "<input type=\"hidden\" name=\"isvendorreply\" value=\"true\">";
echo "<input type=\"hidden\" name=\"reply\" value=\"$reply\">
	    <input type=\"hidden\" name=\"replyvendor\" value=\"$replyvendor\">
		<input type=\"hidden\" name=\"customer\" value=\"$customer\">
		<input type=\"hidden\" name=\"vendorcontact\" value=\"$vendorcontact\">
		<input type=\"hidden\" name=\"mailbox\" value=\"$mailbox\">
		<input type=\"hidden\" name=\"history\" value=\"$history\">
        <input type=\"submit\" name=\"Submit\" value=\"Send Message\">
        <input type=\"button\" onClick=\"window.close()\" value=\"Cancel/Exit\">
      </td>
    </tr>
  </table>
</form>
</body>
</html>";
?>