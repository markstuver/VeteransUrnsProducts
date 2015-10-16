<?php
$emnoinactivitycheck = "true";
include "../admin/checklicense.inc.php";
include "checklogin.inc.php";
include "emfunc.inc.php";
$pagetitle = "Customer Messages";
include "template.inc.php";
// Get context help for this page...
$contexthelppage = "inquiries";
include "emhelp.inc.php";

// Open database...
$db = @mysqli_connect("$databaseserver", "$databaseuser", "$databasepasswd", "$databasename");

if (extension_loaded("imap")) {
	$result = @mysqli_query($db, "SELECT confvalue FROM emerchant_configuration WHERE confname='mailservertype'");
	$mailservertype = @mysqli_result($result,0,"confvalue");
} else $mailservertype = "pop3";

if ($checkmail == "true") {
	$inbox = 1;
	if ($mailservertype == "imap") include "imap.php";
	else include "pop3.php";
}

// Make sure the page isn't stored in the browsers cache...
header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
header("Cache-Control: no-store, no-cache, must-revalidate");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

echo $header;
emerchant_sidebar();
echo "<td valign=\"top\">";
emerchant_topbar("Customer Messages");

// Get IMAP mail parameters...
if ($mailservertype == "imap") {
	$result = @mysqli_query($db, "SELECT * FROM emerchant_configuration");
	while ($row = @mysqli_fetch_array($result)) {
		if ($row["confname"] == "pophost") $hostname = $row["confvalue"];
		if ($row["confname"] == "popuser") $popuser = $row["confvalue"];
		if ($row["confname"] == "poppass") $poppass = $row["confvalue"];
		if ($row["confname"] == "popport") $port = $row["confvalue"];
	}
	if ($hostname == "mail.yourdomain.com" || !$hostname) $mailnotconfigured = 1;
	else $mailnotconfigured = 0;
}

if ($delete) {
	if ($yes) {
		if ($mailservertype == "imap") {
			// Get UID of this message...
			$result = @mysqli_query($db, "SELECT uid FROM emerchant_inbox WHERE id='$delete'");
			$deleteuid = @mysqli_result($result,0,"uid");
			if ($deleteuid) {
				// Open connection to mail server...
				$authenticated = false;
				if (!$mailnotconfigured) {
					$popfp = imap_open ("{"."$hostname:$port/notls"."}INBOX", "$popuser", "$poppass");
					if($popfp) $authenticated = true;
				}
				if($authenticated) {
					@imap_delete($popfp,$deleteuid,FT_UID);
					@imap_close($popfp, CL_EXPUNGE);
				}
			}
		}
		if (file_exists("$ashoppath/emerchant/mail/in1-$delete")) unlink("$ashoppath/emerchant/mail/in1-$delete");
		@mysqli_query($db, "DELETE FROM emerchant_inbox WHERE id='$delete'");
		$notice = "Message deleted!";
	} else {
		echo "<div align=\"center\" class=\"heading3\"><br>Delete Message</div>
		<center><p class=\"sm\">Are you sure you want to delete this message?</p>
		<form action=\"inquiries.php\" method=\"post\"><input type=\"hidden\" name=\"delete\" value=\"$delete\"><input type=\"submit\" name=\"yes\" value=\"Yes\"> <input type=\"button\" name=\"no\" value=\"No\" onClick=\"javascript:history.back()\"></form></center>";
		echo "</table></td></tr></table>$footer";
		exit;
	}
}

if ($deletemultiple) {
	if ($yes) {
		if ($mailservertype == "imap") {
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
				$popfp = @imap_open ("{"."$hostname:$port/notls"."}INBOX", "$popuser", "$poppass");
				if($popfp) $authenticated = true;
			}
		}
		foreach ($_POST as $key=>$value) {
			$pos = strpos($key, "email");
			if ($pos !== FALSE) {
				$delete = str_replace("email","",$key);
				if ($mailservertype == "imap") {
					// Get UID of this message...
					$result = @mysqli_query($db, "SELECT uid FROM emerchant_inbox WHERE id='$delete'");
					$deleteuid = @mysqli_result($result,0,"uid");
					if($authenticated && $deleteuid) @imap_delete($popfp,$deleteuid,FT_UID);
				}
				if (file_exists("$ashoppath/emerchant/mail/in1-$delete")) unlink("$ashoppath/emerchant/mail/in1-$delete");
				@mysqli_query($db, "DELETE FROM emerchant_inbox WHERE id='$delete'");
			}				
		}
		if ($mailservertype == "imap") @imap_close($popfp, CL_EXPUNGE);
		$notice = "Messages deleted!";
	} else {
		echo "<div align=\"center\" class=\"heading3\"><br>Delete Messages</div>
		<center><p class=\"sm\">Are you sure you want to delete these messages?</p>
		<form action=\"inquiries.php\" method=\"post\">";
		foreach($_POST as $key=>$value) {
			$pos = strpos($key, "email");
			if ($pos !== FALSE) echo "<input type=\"hidden\" name=\"$key\" value=\"$value\">";
		}
		echo "<input type=\"hidden\" name=\"deletemultiple\" value=\"yes\"><input type=\"submit\" name=\"yes\" value=\"Yes\"> <input type=\"button\" name=\"no\" value=\"No\" onClick=\"javascript:history.back()\"></form></center>";
		echo "</table></td></tr></table>$footer";
		exit;
	}
}

if ($save) {
	if ($yes) {
		// Get original message...
		$result = @mysqli_query($db, "SELECT * FROM emerchant_inbox WHERE id='$save'");
		$messagerow = @mysqli_fetch_array($result);

		// Get customer ID or add new customer...
		$result = @mysqli_query($db, "SELECT * FROM customer WHERE email='".rtrim($messagerow["email"])."' OR alternativeemails LIKE '%".rtrim($messagerow["email"])."%'");
		if (@mysqli_num_rows($result)) {
			$customerid = @mysqli_result($result,0,"customerid");
			$returnpath = rtrim($messagerow["returnpath"]);
			if ($returnpath && $returnpath != $messagerow["email"]) {
				$alternativeemails = @mysqli_result($result,0,"alternativeemails");
				if ($returnpath && !@strstr($alternativeemails, $returnpath)) {
					if ($alternativeemails) $newalternativeemails = "$alternativeemails, $returnpath";
					else $newalternativeemails = $returnpath;
					@mysqli_query($db, "UPDATE customer SET alternativeemails='$newalternativeemails' WHERE customerid='$customerid'");
				}
			}
		} else {
			if($messagerow["name"]) {
				$fullname = explode(" ",$messagerow["name"]);
				$customerfirstname = $fullname[0];
				$customerlastname = $fullname[1];
			} else {
				$customerfirstname = "Unknown";
				$customerlastname = "Unknown";
			}
			if ($messagerow["returnpath"] != $messagerow["email"]) $alternativeemails = rtrim($messagerow["returnpath"]);
			else $alternativeemails = "";
			@mysqli_query($db, "INSERT INTO customer (firstname, lastname, email, alternativeemails, address, zip, city, state, country, phone) VALUES ('$customerfirstname', '$customerlastname', '".rtrim($messagerow["email"])."', '$alternativeemails', 'Unknown', 'Unknown', 'Unknown', 'Unknown', 'Unknown', 'Unknown')");
			$customerid = @mysqli_insert_id($db);
		}
		$receiveddate = date("Y-m-d H:i:s", $messagerow["received"]);
		@mysqli_query($db, "INSERT INTO emerchant_messages (uid, customerid, user, date, subject, attachments) VALUES ('{$messagerow["uid"]}', '$customerid', '$emerchant_user', '$receiveddate', '".addslashes($messagerow["subject"])."', '{$messagerow["attachments"]}')");
		$messageid = @mysqli_insert_id($db);
		@mysqli_query($db, "DELETE FROM emerchant_inbox WHERE id='$save'");
		if (file_exists("$ashoppath/emerchant/mail/in1-$save")) rename("$ashoppath/emerchant/mail/in1-$save","$ashoppath/emerchant/mail/cust-$messageid");
		$notice = "Message saved!";
	} else {
		echo "<div align=\"center\" class=\"heading3\"><br>Save Message</div>
		<center><p class=\"sm\">Are you sure you want to save this message?</p>
		<form action=\"inquiries.php\" method=\"post\"><input type=\"hidden\" name=\"save\" value=\"$save\"><input type=\"submit\" name=\"yes\" value=\"Yes\"> <input type=\"button\" name=\"no\" value=\"No\" onClick=\"javascript:history.back()\"></form></center>";
		echo "</table></td></tr></table>$footer";
		exit;
	}
}

if ($savemultiple) {
	if ($yes) {
		foreach ($_POST as $key=>$value) {
			$pos = strpos($key, "email");
			if ($pos !== FALSE) {
				$save = str_replace("email","",$key);
				// Get original message...
				$result = @mysqli_query($db, "SELECT * FROM emerchant_inbox WHERE id='$save'");
				$messagerow = @mysqli_fetch_array($result);

				// Get customer ID or add new customer...
				$result = @mysqli_query($db, "SELECT * FROM customer WHERE email='".rtrim($messagerow["email"])."' OR alternativeemails LIKE '%".rtrim($messagerow["email"])."%'");
				if (@mysqli_num_rows($result)) {
					$customerid = @mysqli_result($result,0,"customerid");
					$returnpath = rtrim($messagerow["returnpath"]);
					if ($returnpath && $returnpath != $messagerow["email"]) {
						$alternativeemails = @mysqli_result($result,0,"alternativeemails");
						if (!$alternativeemails || ($returnpath && !@strstr($alternativeemails, $returnpath))) {
							if ($alternativeemails) $newalternativeemails = "$alternativeemails, $returnpath";
							else $newalternativeemails = $returnpath;
							@mysqli_query($db, "UPDATE customer SET alternativeemails='$newalternativeemails' WHERE customerid='$customerid'");
						}
					}
				} else {
					if($messagerow["name"]) {
						$fullname = explode(" ",$messagerow["name"]);
						$customerfirstname = $fullname[0];
						$customerlastname = $fullname[1];
					} else {
						$customerfirstname = "Unknown";
						$customerlastname = "Unknown";
					}
					if ($messagerow["returnpath"] != $messagerow["email"]) $alternativeemails = rtrim($messagerow["returnpath"]);
					else $alternativeemails = "";
					@mysqli_query($db, "INSERT INTO customer (firstname, lastname, email, alternativeemails, address, zip, city, state, country, phone) VALUES ('$customerfirstname', '$customerlastname', '".rtrim($messagerow["email"])."', '$alternativeemails', 'Unknown', 'Unknown', 'Unknown', 'Unknown', 'Unknown', 'Unknown')");
					$customerid = @mysqli_insert_id($db);
				}
				$receiveddate = date("Y-m-d H:i:s", $messagerow["received"]);
				@mysqli_query($db, "INSERT INTO emerchant_messages (uid, customerid, user, date, subject, attachments) VALUES ('{$messagerow["uid"]}', '$customerid', '$emerchant_user', '$receiveddate', '".addslashes($messagerow["subject"])."', '{$messagerow["attachments"]}')");
				$messageid = @mysqli_insert_id($db);
				@mysqli_query($db, "DELETE FROM emerchant_inbox WHERE id='$save'");
				if (file_exists("$ashoppath/emerchant/mail/in1-$save")) rename("$ashoppath/emerchant/mail/in1-$save","$ashoppath/emerchant/mail/cust-$messageid");
				$notice = "Message saved!";
			}
		}
	} else {
		echo "<div align=\"center\" class=\"heading3\"><br>Save Messages</div>
		<center><p class=\"sm\">Are you sure you want to save these messages?</p>
		<form action=\"inquiries.php\" method=\"post\">";
		foreach($_POST as $key=>$value) {
			$pos = strpos($key, "email");
			if ($pos !== FALSE) echo "<input type=\"hidden\" name=\"$key\" value=\"$value\">";
		}
		echo "<input type=\"hidden\" name=\"savemultiple\" value=\"yes\"><input type=\"submit\" name=\"yes\" value=\"Yes\"> <input type=\"button\" name=\"no\" value=\"No\" onClick=\"javascript:history.back()\"></form></center>";
		echo "</table></td></tr></table>$footer";
		exit;
	}
}


if ($poperror) {
	$notice = $poperror;
	$noticecolor = "#FF0000";
} else $noticecolor = "#000099";
echo "<table width=\"100%\" border=\"0\" cellspacing=\"2\" cellpadding=\"1\" bgcolor=\"#ffffff\" bordercolor=\"#FFFFFF\">
              <tr align=\"center\">
			    <td bgcolor=\"#FFFFFF\" width=\"20%\" align=\"left\"><a href=\"inquiries.php?checkmail=true\"><img src=\"images/button_checkmail.gif\" border=\"0\" alt=\"Check for new mail\"></a></td>
				<td bgcolor=\"#FFFFFF\" class=\"heading3\" width=\"40%\" align=\"right\">Choose inbox:</td>
                <td class=\"nav\" width=\"20%\">Customers</td>
                <td class=\"nav\" width=\"20%\"><a href=\"vendormail.php\" class=\"nav\">Vendors</a></td>
              </tr>
              <tr align=\"center\">
			    <td bgcolor=\"#FFFFFF\" width=\"20%\">&nbsp;</td>
				<td bgcolor=\"#FFFFFF\" width=\"40%\">&nbsp;</td>
                <td class=\"nav\" width=\"20%\"><a href=\"messages.php\" class=\"nav\">Message History</a></td>
                <td class=\"nav\" width=\"20%\"><a href=\"vendormessages.php\" class=\"nav\">Message History</a></td>
              </tr>
            </table><br>
			<table width=\"99%\" border=\"0\" cellspacing=\"2\" cellpadding=\"1\" bgcolor=\"#d0d0d0\" align=\"center\"><tr><td><table width=\"100%\" border=\"0\" cellspacing=\"0\" cellpadding=\"2\">
        <tr bgcolor=\"#808080\"><td class=\"heading3_wht\">New Messages <a href=\"javascript:;\" onMouseOut=\"MM_swapImgRestore()\" onMouseOver=\"MM_swapImage('Image1','','images/contexthelpicon_over.gif',1)\"><img src=\"images/contexthelpicon.gif\" width=\"14\" height=\"15\" border=\"0\" name=\"Image1\" align=\"absmiddle\" onclick=\"return overlib('$tip1');\" onmouseout=\"return nd();\"></a></td><td class=\"heading3_wht\" align=\"right\"><font color=\"#00FF00\">$notice</font></td></tr></table></td></tr></table>
      <script language=\"JavaScript\" type=\"text/javascript\">
<!--
function selectAll()
{
	if (document.inboxform.switchall.checked == true) {
		for (var i = 0; i < document.inboxform.elements.length; i++) {
			if (document.inboxform.elements[i].checked != true) {
				document.inboxform.elements[i].checked = true;
			}
		}
	} else {
		for (var i = 0; i < document.inboxform.elements.length; i++) {
			if (document.inboxform.elements[i].checked == true) {
				document.inboxform.elements[i].checked = false;
			}
		}
	}
}
-->
</script><form method=\"post\" name=\"inboxform\" action=\"inquiries.php\"><table width=\"99%\" border=\"0\" cellspacing=\"2\" cellpadding=\"1\" bgcolor=\"#d0d0d0\" align=\"center\">
        <tr bgcolor=\"#808080\">
		  <td width=\"20\" class=\"heading3_wht\"><input name=\"switchall\" type=\"checkbox\" onClick=\"selectAll()\"></td>
		  <td width=\"12\" class=\"heading3_wht\">&nbsp;</td>
          <td width=\"18%\" class=\"heading3_wht\"><b class=\"heading3_wht\">Received</b></td>
          <td width=\"21%\" class=\"heading3_wht\"><b>From</b></td>
          <td class=\"heading3_wht\"><b>Subject</b></td>
          <td width=\"120\" class=\"heading3_wht\">&nbsp; </td>
        </tr>";

$result = @mysqli_query($db, "SELECT * FROM emerchant_inbox ORDER BY received DESC");
while ($row = @mysqli_fetch_array($result)) {
	// Check if IMAP message exists on the server or if this is a locally saved message...
	$messageid = $row["id"];
	if ($mailservertype == "imap" && !file_exists("$ashoppath/emerchant/mail/in1-$messageid")) {
		if ($hostname == "mail.yourdomain.com" || !$hostname) $mailnotconfigured = 1;
		else $mailnotconfigured = 0;
		$authenticated = false;
		if (!$mailnotconfigured) {
			$popfp = @imap_open ("{"."$hostname:$port/notls"."}INBOX", "$popuser", "$poppass");
			if($popfp) $authenticated = true;
		}
		if ($authenticated) {
			$structure = @imap_fetchstructure($popfp,$row["uid"],FT_UID);
			if (!is_object($structure)) {
				@mysqli_query($db, "DELETE FROM emerchant_inbox WHERE id='$messageid'");
				continue;
			}
		}
	}
	$customerresult = @mysqli_query($db, "SELECT * FROM customer WHERE email='".rtrim($row["email"])."'");
	if (@mysqli_num_rows($customerresult)) $customerrow = @mysqli_fetch_array($customerresult);
	else unset($customerrow);
	if (!empty($affiliateid) && $customerrow["affiliateid"] != $affiliateid) continue;
	$received = date("Y-m-d H:i:s", $row["received"]);
	echo "<tr bgcolor=\"#e0e0e0\">
	      <td width=\"20\" class=\"heading3_wht\"><input type=\"checkbox\" name=\"email{$row["id"]}\"></td>
		  <td width=\"12\" class=\"heading3_wht\">";
	if ($row["attachments"]) echo "<img src=\"images/icon_attachments.gif\">";
	else echo "&nbsp;";
	echo "</td>
          <td width=\"18%\" valign=\"top\" class=\"sm\">$received</td>
          <td width=\"21%\" valign=\"top\" class=\"sm\"><a href=\"mailto:{$row["email"]}\">";
	if ($row["name"]) echo $row["name"];
	else echo rtrim($row["email"]);
	echo "</a></td>
          <td valign=\"top\" class=\"sm\"><a href=\"javascript:newWindow('";
		  if ($mailservertype == "imap" && $row["uid"]) echo "readmessageimap.php";
		  else echo "readmessage.php";
		  echo "?id={$row["id"]}')\">{$row["subject"]}</a></td>
          <td width=\"120\" valign=\"top\" class=\"sm\"><a href=\"javascript:newWindow('"; if ($mailservertype == "imap" && $row["uid"]) echo "composemessageimap.php"; else echo "composemessage.php"; echo "?reply={$row["id"]}')\"><img src=\"images/icon_reply.gif\" alt=\"Reply to this message.\" border=\"0\"></a>&nbsp;<a href=\"forward.php?id={$row["id"]}\"><img src=\"images/icon_forward.gif\" alt=\"Forward this message.\" border=\"0\"></a>&nbsp;<a href=\"inquiries.php?delete={$row["id"]}\"><img src=\"images/icon_trash.gif\" width=\"15\" height=\"15\" alt=\"Delete this message.\" border=\"0\"></a>&nbsp;<a href=\"inquiries.php?save={$row["id"]}\"><img src=\"images/icon_save.gif\" alt=\"Save this message\" border=\"0\"></a>";
	if ($customerrow) echo "&nbsp;<a href=\"customer.php?id={$customerrow["customerid"]}\"><img src=\"images/icon_profile.gif\" alt=\"View customer profile\" border=\"0\"></a>&nbsp;<a href=\"history.php?customer={$customerrow["customerid"]}\"><img src=\"images/icon_history.gif\" alt=\"View history for ".$customerrow["firstname"]." ".$customerrow["lastname"].".\" border=\"0\"></a>";
	echo "</td></tr>";
}

echo "</table><br><input type=\"submit\" name=\"deletemultiple\" value=\"Delete Selected\"> <input type=\"submit\" name=\"savemultiple\" value=\"Save Selected\"></form>
      </td>
  </tr>
</table>
$footer";
?>