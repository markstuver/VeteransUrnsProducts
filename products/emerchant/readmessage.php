<?php
$popuplogincheck = TRUE;
if ($_COOKIE["sid"]) {
	unset($_COOKIE["sid"]);
	$sid = $_GET["sid"];
}
include "../admin/checklicense.inc.php";
include "checklogin.inc.php";
if (!class_exists("Mail_mimeDecode")) include "mimeDecode.php";

// Open database...
$db = @mysqli_connect("$databaseserver", "$databaseuser", "$databasepasswd", "$databasename");

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

$params['include_bodies'] = true;
$params['decode_bodies'] = true;
$params['decode_headers'] = true;
$params['input'] = "";
if ($id) $fp = fopen("$ashoppath/emerchant/mail/$msgprefix$id","r");
else $fp = fopen("$ashoppath/emerchant/mail/$msgprefix$vid","r");
if ($fp) {
	while (!feof ($fp)) $params['input'] .= fgets($fp, 4096);
	fclose($fp);
}
$mailmimedecode = new mail_mimedecode($params['input']);
$structure = $mailmimedecode->decode($params);
$charset = $structure->ctype_parameters["charset"];

unset($attachments);

if ($structure->parts) foreach ($structure->parts as $partnumber=>$part) {
	if ($part->ctype_secondary == "html" && $part->disposition != "attachment") $htmlmessage = $part->body;
	else if ($part->ctype_secondary == "plain" && $part->disposition != "attachment") $textmessage = $part->body;
	else if ($part->parts && $part->disposition != "attachment") {
		$partparts = $part->parts;
		if (strstr($part->ctype_primary, "message")) {
			if ($htmlmessage) $htmlmessage .= "<br>-----Original Message-----<br>";
			if ($textmessage) $textmessage .= "\n-----Original Message-----\n";
		}
		if ($partparts) foreach ($partparts as $subpartnumber=>$subpart) {
			if ($subpart->ctype_secondary == "html") {
				if ($structure->ctype_secondary == "mixed") {
					if ($htmlmessage) $htmlmessage .= $subpart->body;
					else if ($textmessage) {
						$tmpmessage = str_replace("<br>\n","\n",$subpart->body);
						$tmpmessage = str_replace("\n<br>","\n",$tmpmessage);
						$tmpmessage = str_replace("<br>","\n",$tmpmessage);
						$tmpmessage = str_replace("<BR>","\n",$tmpmessage);
						$tmpmessage = str_replace("\\'","'",$tmpmessage);
						$textmessage .= strip_tags($tmpmessage);
					} else $htmlmessage = $subpart->body;
				} else {
					if (!$htmlmessage) $htmlmessage = $subpart->body;
					else {
						$attachmentfilename = "message.html";
						$contents = $subpart->body;
						if ($download == $subpartnumber) {
							header("Content-Type: ".$subpart->ctype_primary."/".$subpart->ctype_secondary);
							header ("Content-Disposition: ".$subpart->disposition."; filename=$attachmentfilename");
							echo $contents;
							exit;
						} else {
							if (!$subpart->c_id) $attachments[$subpartnumber] = $attachmentfilename;
							else $embedded[$subpart->c_id] = $subpartnumber;
						}
					}
				}
			}
			else if ($structure->ctype_secondary == "mixed") {
				if ($htmlmessage) {
					$tmpmessage .= str_replace("\r\n","<br>",$subpart->body);
					$htmlmessage .= str_replace("\n","<br>",$tmpmessage);
				} else if ($textmessage) $textmessage .= $subpart->body;
				else $textmessage = $subpart->body;
			}
			else if ($subpart->ctype_secondary == "plain") $textmessage = $subpart->body;
			else {
				$attachmentfilename = $subpart->d_parameters["filename"];
				if (!$attachmentfilename) {
					$attachmentfilename = $subpart->ctype_parameters["name"];
					if (!$attachmentfilename) $attachmentfilename = "attachment.txt";
				}
				$contents = $subpart->body;
				if ($download == "s$subpartnumber") {
					header("Content-Type: ".$subpart->ctype_primary."/".$subpart->ctype_secondary);
					header ("Content-Disposition: ".$subpart->disposition."; filename=$attachmentfilename");
					echo $contents;
					exit;
				} else {
					if (!$subpart->c_id) $attachments["s$subpartnumber"] = $attachmentfilename;
					else $embedded[$subpart->c_id] = $subpartnumber;
				}
			}
		}
	}
	else {
		$attachmentfilename = $part->d_parameters["filename"];
		if (!$attachmentfilename) {
			$attachmentfilename = $part->ctype_parameters["name"];
			if (!$attachmentfilename) $attachmentfilename = "attachment.txt";
		}
		if ($part->ctype_primary == "message") {
			$contents = $part->body;
			$attachpartparts = $part->parts;
			if ($attachpartparts) foreach ($attachpartparts as $subpart) {
				$contents .= $subpart->body;
				$attachpartpartparts = $subpart->parts;
				if ($attachpartpartparts) foreach ($attachpartpartparts as $subsubpart) {
					$contents .= $subsubpart->body;
				}
			}
		} else $contents = $part->body;
		if ($download == $partnumber) {
			header("Content-Type: ".$part->ctype_primary."/".$part->ctype_secondary);
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
	if(!isset($messagerow["replyto"]) && $structure->body && (stristr($params['input'],"Return-Path:") || stristr($params['input'],"Received:") || stristr($params['input'],"From:"))) {
		if ((stristr($structure->body, "<html>") && stristr($structure->body,"</html>")) || (stristr($structure->body, "<body>") && stristr($structure->body, "</body>")) || $structure->ctype_secondary=="html") {
			$htmlmessage = $structure->body;
			$textmessage = "";
		} else {
			$htmlmessage = "";
			$textmessage = $structure->body;
		}
	} else {
		$messagesource = $params['input'];
		if (isset($messagerow["replyto"]) && $messagerow["received"] < $before) $htmlmessage = $messagesource;
		else $textmessage = $messagesource;
	}
}

if ($htmlmessage) {
	if (is_array($embedded)) foreach($embedded as $cid=>$filenumber) $htmlmessage = str_replace("cid:$cid","readmessage.php?id=$id&vid=$vid&download=$filenumber&mailbox=$mailbox",$htmlmessage);
	$message = $htmlmessage;
} else {
	$message = str_replace("<","&lt;",$textmessage);
	$message = str_replace(">","&gt;",$message);
	$message = str_replace("\n","<br>",$message);
}

if (!$charset) $charset = "iso-8859-1";
echo "<html>
<head>
<title>Read Message - $ashopname</title>
<meta http-equiv=\"Content-Type\" content=\"text/html; charset=$charset\">
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
	  if ($mailbox != "archive" && $mailbox != "varchive") {
		  echo "<a href=\"composemessage.php?reply";
		  if ($vid) echo "vendor";
		  echo "={$messagerow["id"]}\"><img src=\"images/icon_reply.gif\" alt=\"Reply to this message.\" border=\"0\"></a>&nbsp;<a href=\"javascript: opener.window.location.href='";
		  if ($vid) echo "vendormail.php";
		  else if ($sid) echo "spam.php";
		  else echo "inquiries.php";
		  echo "?delete={$messagerow["id"]}'; this.close();\"><img src=\"images/icon_trash.gif\" width=\"15\" height=\"15\" alt=\"Delete this message.\" border=\"0\"></a>&nbsp;<a href=\"javascript: opener.window.location.href='";
		  if ($vid) echo "vendormail.php";
		  else if ($sid) echo "spam.php";
		  else echo "inquiries.php";
		  echo "?save={$messagerow["id"]}'; this.close();\"><img src=\"images/icon_save.gif\" alt=\"Save this message\" border=\"0\"></a>";
	  } else echo "&nbsp;";
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
	foreach($attachments as $attachmentnumber=>$filename) echo "<a href=\"readmessage.php?id=$id&vid=$vid&download=$attachmentnumber&mailbox=$mailbox\"><img src=\"images/icon_attachment.gif\" border=\"0\"></a> <a href=\"readmessage.php?id=$id&vid=$vid&download=$attachmentnumber&mailbox=$mailbox\">$filename</a>&nbsp;&nbsp;";
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