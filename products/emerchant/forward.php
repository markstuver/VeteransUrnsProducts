<?php
$emnoinactivitycheck = "true";
include "../admin/checklicense.inc.php";
include "checklogin.inc.php";
include "emfunc.inc.php";
$pagetitle = "Forward Message";
include "template.inc.php";
// Get context help for this page...
$contexthelppage = "forward";
include "emhelp.inc.php";

// Open database...
$db = @mysqli_connect("$databaseserver", "$databaseuser", "$databasepasswd", "$databasename");

if (extension_loaded("imap")) {
	$result = @mysqli_query($db, "SELECT confvalue FROM emerchant_configuration WHERE confname='mailservertype'");
	$mailservertype = @mysqli_result($result,0,"confvalue");
} else $mailservertype = "pop3";

echo $header;
emerchant_sidebar();
echo "<td valign=\"top\">";
emerchant_topbar("Forward Message");

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
	$messagerow["email"] = @mysqli_result($result,0,"email");
	$messagerow["name"] = @mysqli_result($result,0,"firstname")." ".@mysqli_result($result,0,"lastname");
} else if ($mailbox == "varchive") {
	$result = @mysqli_query($db, "SELECT name, email FROM emerchant_vendor WHERE vendorid='{$messagerow["vendorid"]}'");
	if ($messagerow["vendcontactid"]) {
		$result2 = @mysqli_query($db, "SELECT * FROM emerchant_vendcontact WHERE vendcontactid='{$messagerow["vendcontactid"]}'");
		if (@mysqli_num_rows($result2)) {
			$vendcontactrow = @mysqli_fetch_array($result2);
			$messagerow["email"] = $vendcontactrow["email"];
		} else $messagerow["email"] = @mysqli_result($result,0,"email");
	} else $messagerow["email"] = @mysqli_result($result,0,"email");
	$messagerow["name"] = @mysqli_result($result,0,"name");
}

if ($to) {
	if ($mailservertype == "imap") {
		// Get UID of this message...
		if ($vid) $sql = "SELECT uid FROM emerchant_inbox2 WHERE id='$vid'";
		else if ($sid) $sql = "SELECT uid FROM emerchant_spam WHERE id='$sid'";
		else $sql = "SELECT uid FROM emerchant_inbox WHERE id='$id'";
		$result = @mysqli_query($db, $sql);
		$forwarduid = @mysqli_result($result,0,"uid");
		if ($forwarduid) {
			// Get mail parameters...
			$result = @mysqli_query($db, "SELECT * FROM emerchant_configuration");
			while ($row = @mysqli_fetch_array($result)) {
				if ($row["confname"] == "pophost") $hostname = $row["confvalue"];
				if ($row["confname"] == "popuser") $popuser = $row["confvalue"];
				if ($row["confname"] == "poppass") $poppass = $row["confvalue"];
				if ($row["confname"] == "popport") $port = $row["confvalue"];
			}
			if ($hostname == "mail.yourdomain.com" || !$hostname) $mailnotconfigured = 1;
			else $mailnotconfigured = 0;
			// Open connection to mail server...
			$authenticated = false;
			if (!$mailnotconfigured) {
				$popfp = imap_open ("{"."$hostname:$port/notls"."}INBOX", "$popuser", "$poppass");
				if($popfp) $authenticated = true;
			}
			if($authenticated) {
				$fullheaders = @imap_fetchbody($popfp, $forwarduid, 0, FT_UID);
				$headersarray = explode("\r\n",$fullheaders);
				if (!is_array($headersarray)) $headersarray = explode("\n",$fullheaders);
				$headers = "";
				foreach ($headersarray as $input) if (!strstr($input,"From:") && !strstr($input,"Cc:") && !strstr($input,"Delivered-To:") && !strstr($input,"Return-Path:") && !strstr($input,"To:") && !strstr($input,"Subject:")) $headers .= $input."\n";
				$headers = trim($headers);
				$msg = @imap_body($popfp, $forwarduid,FT_UID);
				$msg = str_replace("\r\n","\n",$msg);
			}
		}
	} 
	if (!$forwarduid) {
		if ($vid && file_exists("$ashoppath/emerchant/mail/$msgprefix$vid")) $fp = fopen("$ashoppath/emerchant/mail/$msgprefix$vid","r");
		else if ($sid && file_exists("$ashoppath/mail/$msgprefix$sid")) $fp = fopen("$ashoppath/mail/$msgprefix$sid","r");
		else if (file_exists("$ashoppath/emerchant/mail/$msgprefix$id")) $fp = fopen("$ashoppath/emerchant/mail/$msgprefix$id","r");
		else {
			echo "<div align=\"center\" class=\"heading3\"><br><font color=\"#000099\">Message could not be found!</font></div>";
			exit;
		}
		
		if ($fp) {
			while (!feof ($fp)) {
				$input = fgets($fp, 4096);
				if (!strstr($input,"From:") && !strstr($input,"Cc:") && !strstr($input,"Delivered-To:") && !strstr($input,"Return-Path:") && !strstr($input,"To:") && !strstr($input,"Subject:")) $msgsrc .= str_replace("\r\n","\n",$input);
			}
			fclose($fp);
		}
		$headers = substr($msgsrc,0,strpos($msgsrc,"\n\n"));
		$msg = substr($msgsrc,strpos($msgsrc,"\n\n"));
	}
	
	if ($messagerow["name"]) $headers = "From: {$messagerow["name"]}<{$messagerow["email"]}>\r\nReturn-Path: {$messagerow["name"]}<{$messagerow["email"]}>\r\n".$headers;
	else $headers = "From: {$messagerow["email"]}\r\n".$headers;
	@mail($to,$_POST["nsubject"],$msg,$headers);
	$notice = "Message sent!";
	if (!empty($history) && is_numeric($history)) echo "<script language=\"JavaScript\">window.location.href='history.php?customer=$history&notice=$notice';</script>";
	else echo "<div align=\"center\" class=\"heading3\"><br><font color=\"#000099\">$notice</font></div>";
} else {
	echo "<div align=\"center\" class=\"heading3\"><br>
        <span class=\"heading2\">Forward this message...</span><br>
	  <table width=\"525\" border=\"0\" cellspacing=\"2\" cellpadding=\"1\" align=\"center\"><form action=\"forward.php\" method=\"post\">
        <tr> 
		  <td class=\"sm\" align=\"right\">To: </td><td><input type=\"text\" size=\"35\" name=\"to\" value=\"$ashopemail\"></td>
		</tr>
        <tr> 
		  <td class=\"sm\" align=\"right\">Subject: </td><td><input type=\"text\" size=\"73\" name=\"nsubject\" value=\"Fw: {$messagerow["subject"]}\"></td>
		</tr>
		<tr><td colspan=\"2\" align=\"right\"><input type=\"submit\" name=\"add\" value=\"Send\"></td></tr><input type=\"hidden\" name=\"mailbox\" value=\"$mailbox\"><input type=\"hidden\" name=\"history\" value=\"$history\"><input type=\"hidden\" name=\"id\" value=\"$id\"><input type=\"hidden\" name=\"vid\" value=\"$vid\"><input type=\"hidden\" name=\"sid\" value=\"$sid\"></form>
	  </table><br>$footer";
}
@mysqli_close($db);
?>