<?php
$emnoinactivitycheck = "true";
include "../admin/checklicense.inc.php";
include "checklogin.inc.php";
include "emfunc.inc.php";
$pagetitle = "Vendor eMail";
include "template.inc.php";
// Get context help for this page...
$contexthelppage = "vendormail";
include "emhelp.inc.php";

// Open database...
$db = @mysqli_connect("$databaseserver", "$databaseuser", "$databasepasswd", "$databasename");

if (extension_loaded("imap")) {
	$result = @mysqli_query($db, "SELECT confvalue FROM emerchant_configuration WHERE confname='mailservertype'");
	$mailservertype = @mysqli_result($result,0,"confvalue");
} else $mailservertype = "pop3";

if ($checkmail == "true") {
	$inbox = 2;
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
emerchant_topbar("Vendor eMail");

// Get IMAP mail parameters...
if ($mailservertype == "imap") {
	$result = @mysqli_query($db, "SELECT * FROM emerchant_configuration");
	while ($row = @mysqli_fetch_array($result)) {
		if ($row["confname"] == "pophost2") $hostname = $row["confvalue"];
		if ($row["confname"] == "popuser2") $popuser = $row["confvalue"];
		if ($row["confname"] == "poppass2") $poppass = $row["confvalue"];
		if ($row["confname"] == "popport2") $port = $row["confvalue"];
	}
	if ($hostname == "mail.yourdomain.com" || !$hostname) $mailnotconfigured = 1;
	else $mailnotconfigured = 0;
}

if ($delete) {
	if ($yes) {
		if ($mailservertype == "imap") {
			// Get UID of this message...
			$result = @mysqli_query($db, "SELECT uid FROM emerchant_inbox2 WHERE id='$delete'");
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
		if (file_exists("$ashoppath/emerchant/mail/in2-$delete")) unlink("$ashoppath/emerchant/mail/in2-$delete");
		@mysqli_query($db, "DELETE FROM emerchant_inbox2 WHERE id='$delete'");
		$notice = "Message deleted!";
	} else {
		echo "<div align=\"center\" class=\"heading3\"><br>Delete Message</div>
		<center><p class=\"sm\">Are you sure you want to delete this message?</p>
		<form action=\"vendormail.php\" method=\"post\"><input type=\"hidden\" name=\"delete\" value=\"$delete\"><input type=\"submit\" name=\"yes\" value=\"Yes\"> <input type=\"button\" name=\"no\" value=\"No\" onClick=\"javascript:history.back()\"></form></center>";
		echo "</table></td></tr></table>$footer";
		exit;
	}
}

if ($save) {
	// Get original message...
	$result = @mysqli_query($db, "SELECT * FROM emerchant_inbox2 WHERE id='$save'");
	$messagerow = @mysqli_fetch_array($result);

	// Create vendor list...
	$result = @mysqli_query($db, "SELECT * FROM emerchant_vendor ORDER BY name");
	$vendorselectstring = "<select name=\"vendorid\"><option value=\"0\"";
	if (!$vendor) $vendorselectstring .= " selected";
	$vendorselectstring .= ">Select vendor...</option>";
	while ($row = @mysqli_fetch_array($result)) {
		$vendorselectstring .= "<option value=\"{$row["vendorid"]}\"";
		if ($vendor == $row["vendorid"]) $vendorselectstring .= " selected";
		$vendorselectstring .= ">{$row["name"]}</option>";
	}
	$vendorselectstring .= "</select>";

	if ($yes) {
		// Get vendor ID or add new vendor contact...
		$result = @mysqli_query($db, "SELECT * FROM emerchant_vendcontact WHERE email='".rtrim($messagerow["email"])."'");
		$result2 = @mysqli_query($db, "SELECT * FROM emerchant_vendor WHERE email='".rtrim($messagerow["email"])."'");
		if (@mysqli_num_rows($result)) {
			$vendorid = @mysqli_result($result,0,"vendorid");
			$vendorcontactid = @mysqli_result($result,0,"vendcontactid");
		} else if (@mysqli_num_rows($result2)) {
			$vendorid = @mysqli_result($result2,0,"vendorid");
			$vendorcontactid = "";
		} else {
			if (!$vendorid) {
				echo "<div align=\"center\" class=\"heading3\"><br>Save Message</div>
				<center>
					<div class=\"heading3\"><br><font color=\"#FF0000\">You must select a vendor!</font></div>
					<p class=\"sm\">Are you sure you want to save this message?</p>
				<form action=\"vendormail.php\" method=\"post\"><p class=\"formlabel\">Vendor: $vendorselectstring</p>
						<input type=\"hidden\" name=\"save\" value=\"$save\"><input type=\"submit\" name=\"yes\" value=\"Yes\"> <input type=\"button\" name=\"no\" value=\"No\" onClick=\"javascript:history.back()\"></form></center>";
				echo "</table></td></tr></table>$footer";
				exit;
			}
			if($messagerow["name"]) {
				$fullname = explode(" ",$messagerow["name"]);
				$vendorfirstname = $fullname[0];
				$vendorlastname = $fullname[1];
			} else {
				$vendorfirstname = "Unknown";
				$vendorlastname = "Unknown";
			}
			@mysqli_query($db, "INSERT INTO emerchant_vendcontact (vendorid, firstname, lastname, email, phone, title) VALUES ('$vendorid', '$vendorfirstname', '$vendorlastname', '".rtrim($messagerow["email"])."', 'Unknown', 'Unknown')");
			$vendorcontactid = @mysqli_insert_id($db);
		}
		$receiveddate = date("Y-m-d H:i:s", $messagerow["received"]);
		@mysqli_query($db, "INSERT INTO emerchant_vendormessages (uid, vendorid, vendcontactid, user, date, subject, attachments) VALUES ('{$messagerow["uid"]}', '$vendorid', '$vendorcontactid', '$emerchant_user', '$receiveddate', '".addslashes($messagerow["subject"])."', '{$messagerow["attachments"]}')");
		$messageid = @mysqli_insert_id($db);
		@mysqli_query($db, "DELETE FROM emerchant_inbox2 WHERE id='$save'");
		if (file_exists("$ashoppath/emerchant/mail/in2-$save")) rename("$ashoppath/emerchant/mail/in2-$save","$ashoppath/emerchant/mail/vend-$messageid");
		$notice = "Message saved!";
	} else {
		$result = @mysqli_query($db, "SELECT * FROM emerchant_vendcontact WHERE email='".rtrim($messagerow["email"])."'");
		$result2 = @mysqli_query($db, "SELECT * FROM emerchant_vendor WHERE email='".rtrim($messagerow["email"])."'");
		echo "<div align=\"center\" class=\"heading3\"><br>Save Message</div>
		<center><p class=\"sm\">Are you sure you want to save this message?</p>
		<form action=\"vendormail.php\" method=\"post\">";
		if (!@mysqli_num_rows($result) && !@mysqli_num_rows($result2)) echo "<p class=\"formlabel\">Vendor: $vendorselectstring</p>";
		echo "<input type=\"hidden\" name=\"save\" value=\"$save\"><input type=\"submit\" name=\"yes\" value=\"Yes\"> <input type=\"button\" name=\"no\" value=\"No\" onClick=\"javascript:history.back()\"></form></center>";
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
			    <td bgcolor=\"#FFFFFF\" width=\"20%\" align=\"left\"><a href=\"vendormail.php?checkmail=true\"><img src=\"images/button_checkmail.gif\" border=\"0\" alt=\"Check for new mail\"></a></td>
				<td bgcolor=\"#FFFFFF\" class=\"heading3\" width=\"40%\" align=\"right\">Choose inbox:</td>
                <td class=\"nav\" width=\"20%\"><a href=\"inquiries.php\" class=\"nav\">Customers</a></td>
                <td class=\"nav\" width=\"20%\">Vendors</td>
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
      <table width=\"99%\" border=\"0\" cellspacing=\"2\" cellpadding=\"1\" bgcolor=\"#d0d0d0\" align=\"center\">
        <tr bgcolor=\"#808080\">
		  <td width=\"4%\" class=\"heading3_wht\">&nbsp;</td>
          <td width=\"18%\" class=\"heading3_wht\"><b class=\"heading3_wht\">Received</b></td>
          <td width=\"21%\" class=\"heading3_wht\"><b>From</b></td>
          <td class=\"heading3_wht\"><b>Subject</b></td>
          <td width=\"120\" class=\"heading3_wht\">&nbsp; </td>
        </tr>";

$result = @mysqli_query($db, "SELECT * FROM emerchant_inbox2 ORDER BY received DESC");
while ($row = @mysqli_fetch_array($result)) {
	// Check if IMAP message exists on the server...
	if ($mailservertype == "imap") {
		$vid = $row["id"];
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
				@mysqli_query($db, "DELETE FROM emerchant_inbox2 WHERE id='$vid'");
				continue;
			}
		}
	}
	unset($vendorrow);
	$received = date("Y-m-d H:i:s", $row["received"]);
	echo "<tr bgcolor=\"#e0e0e0\">
	      <td width=\"4%\" class=\"heading3_wht\">";
	if ($row["attachments"]) echo "<img src=\"images/icon_attachments.gif\">";
	else echo "&nbsp;";
	echo "</td> 
          <td width=\"18%\" valign=\"top\" class=\"sm\">$received</td>
          <td width=\"21%\" valign=\"top\" class=\"sm\"><a href=\"mailto:{$row["email"]}\">";
	if ($row["name"]) echo $row["name"];
	else echo rtrim($row["email"]);
	$vendorresult = @mysqli_query($db, "SELECT * FROM emerchant_vendcontact WHERE email='".rtrim($row["email"])."'");
	if (@mysqli_num_rows($vendorresult)) $vendorrow = @mysqli_fetch_array($vendorresult);
	else {
		$vendorresult = @mysqli_query($db, "SELECT * FROM emerchant_vendor WHERE email='".rtrim($row["email"])."'");
		if (@mysqli_num_rows($vendorresult)) $vendorrow = @mysqli_fetch_array($vendorresult);
	}
	echo "</a></td>
          <td valign=\"top\" class=\"sm\"><a href=\"javascript:newWindow('";
		  if ($mailservertype == "imap" && $row["uid"]) echo "readmessageimap.php";
		  else echo "readmessage.php";
		  echo "?vid={$row["id"]}')\">{$row["subject"]}</a></td>
          <td width=\"120\" valign=\"top\" class=\"sm\"><a href=\"javascript:newWindow('"; if ($mailservertype == "imap" && $row["uid"]) echo "composemessageimap.php"; else echo "composemessage.php"; echo "?replyvendor={$row["id"]}')\"><img src=\"images/icon_reply.gif\" alt=\"Reply to this message.\" border=\"0\"></a>&nbsp;<a href=\"forward.php?vid={$row["id"]}\"><img src=\"images/icon_forward.gif\" alt=\"Forward this message.\" border=\"0\"></a>&nbsp;<a href=\"vendormail.php?delete={$row["id"]}\"><img src=\"images/icon_trash.gif\" width=\"15\" height=\"15\" alt=\"Delete this message.\" border=\"0\"></a>&nbsp;<a href=\"vendormail.php?save={$row["id"]}\"><img src=\"images/icon_save.gif\" alt=\"Save this message\" border=\"0\"></a>";
	if ($vendorrow) echo "&nbsp;<a href=\"vendor.php?id={$vendorrow["vendorid"]}\"><img src=\"images/icon_profile.gif\" alt=\"View vendor profile\" border=\"0\"></a>&nbsp;<a href=\"vendorhistory.php?vendor={$vendorrow["vendorid"]}\"><img src=\"images/icon_history.gif\" alt=\"View history for ".$vendorrow["name"].".\" border=\"0\"></a>";
	echo "</td></tr>";
}

echo "</table>
      </td>
  </tr>
</table>
$footer";
?>