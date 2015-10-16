<?php
$emnoinactivitycheck = "true";
include "../admin/checklicense.inc.php";
include "checklogin.inc.php";
include "emfunc.inc.php";
$pagetitle = "Spam";
include "template.inc.php";
// Get context help for this page...
$contexthelppage = "spam";
include "emhelp.inc.php";

// Open database...
$db = @mysqli_connect("$databaseserver", "$databaseuser", "$databasepasswd", "$databasename");

if (extension_loaded("imap")) {
	$result = @mysqli_query($db, "SELECT confvalue FROM emerchant_configuration WHERE confname='mailservertype'");
	$mailservertype = @mysqli_result($result,0,"confvalue");
} else $mailservertype = "pop3";

if ($empty == "true") {
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
	$result = @mysqli_query($db, "SELECT * FROM emerchant_spam");
	while ($messagerow = @mysqli_fetch_array($result)) {
		if ($mailservertype == "imap") {
			$deleteuid = $messagerow["uid"];
			if($authenticated && $deleteuid) @imap_delete($popfp,$deleteuid,FT_UID);
		}
		if (file_exists("$ashoppath/emerchant/mail/spam-{$messagerow["id"]}")) unlink ("$ashoppath/emerchant/mail/spam-{$messagerow["id"]}");
	}
	if ($mailservertype == "imap") @imap_close($popfp, CL_EXPUNGE);		
	@mysqli_query($db, "DELETE FROM emerchant_spam");
	if (@mysqli_affected_rows($db)) $notice = "All spam messages have been removed!";
	else $notice = "Nothing to remove!";
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
emerchant_topbar("Spam Messages");

if ($save) {
	if ($yes) {
		// Get original message...
		$result = @mysqli_query($db, "SELECT * FROM emerchant_spam WHERE id='$save'");
		$messagerow = @mysqli_fetch_array($result);
		@mysqli_query($db, "INSERT INTO emerchant_inbox (uid, received, name, email, returnpath, subject, attachments) VALUES ('{$messagerow["uid"]}', '{$messagerow["received"]}', '{$messagerow["name"]}', '{$messagerow["email"]}', '{$messagerow["returnpath"]}', '".addslashes($messagerow["subject"])."', '{$messagerow["attachments"]}')");
		$messageid = @mysqli_insert_id($db);
		@mysqli_query($db, "DELETE FROM emerchant_spam WHERE id='$save'");
		if (file_exists("$ashoppath/emerchant/mail/spam-$save")) rename("$ashoppath/emerchant/mail/spam-$save","$ashoppath/emerchant/mail/in1-$messageid");
		$notice = "Message saved!";
	} else {
		echo "<div align=\"center\" class=\"heading3\"><br>Move Message To Inbox</div>
		<center><p class=\"sm\">Are you sure you want to keep this message?</p>
		<form action=\"spam.php\" method=\"post\"><input type=\"hidden\" name=\"save\" value=\"$save\"><input type=\"submit\" name=\"yes\" value=\"Yes\"> <input type=\"button\" name=\"no\" value=\"No\" onClick=\"javascript:history.back()\"></form></center>";
		echo "</table></td></tr></table>$footer";
		exit;
	}
}
if ($poperror) {
	$notice = $poperror;
	$noticecolor = "#FF0000";
} else $noticecolor = "#000099";
echo "<table width=\"100%\" border=\"0\" cellspacing=\"2\" cellpadding=\"1\" bgcolor=\"#FFFFFF\" bordercolor=\"#FFFFFF\">
              <tr align=\"center\">
			    <td bgcolor=\"#FFFFFF\" align=\"center\"><a href=\"spam.php?empty=true\"><img src=\"images/button_empty.gif\" border=\"0\" alt=\"Empty spam mail\"></a></td>
              </tr>
            </table><br>
			<table width=\"99%\" border=\"0\" cellspacing=\"2\" cellpadding=\"1\" bgcolor=\"#d0d0d0\" align=\"center\"><tr><td><table width=\"100%\" border=\"0\" cellspacing=\"0\" cellpadding=\"2\">
        <tr bgcolor=\"#808080\"><td class=\"heading3_wht\">Spam Messages <a href=\"javascript:;\" onMouseOut=\"MM_swapImgRestore()\" onMouseOver=\"MM_swapImage('Image1','','images/contexthelpicon_over.gif',1)\"><img src=\"images/contexthelpicon.gif\" width=\"14\" height=\"15\" border=\"0\" name=\"Image1\" align=\"absmiddle\" onclick=\"return overlib('$tip1');\" onmouseout=\"return nd();\"></a></td><td class=\"heading3_wht\" align=\"right\"><font color=\"#00FF00\">$notice</font></td></tr></table></td></tr></table>
      <table width=\"99%\" border=\"0\" cellspacing=\"2\" cellpadding=\"1\" bgcolor=\"#d0d0d0\" align=\"center\">
        <tr bgcolor=\"#808080\">
		  <td width=\"2%\" class=\"heading3_wht\">&nbsp;</td>
          <td width=\"18%\" class=\"heading3_wht\"><b class=\"heading3_wht\">Received</b></td>
          <td width=\"21%\" class=\"heading3_wht\"><b>From</b></td>
          <td width=\"52%\" class=\"heading3_wht\"><b>Subject</b></td>
          <td width=\"7%\" class=\"heading3_wht\">&nbsp;</td>
        </tr>";

$result = @mysqli_query($db, "SELECT * FROM emerchant_spam ORDER BY received DESC");
while ($row = @mysqli_fetch_array($result)) {
	$received = date("Y-m-d H:i:s", $row["received"]);
	echo "<tr bgcolor=\"#e0e0e0\">
	      <td width=\"2%\" class=\"heading3_wht\">";
	if ($row["attachments"]) echo "<img src=\"images/icon_attachments.gif\">";
	else echo "&nbsp;";
	echo "</td>	
          <td width=\"18%\" valign=\"top\" class=\"sm\">$received</td>
          <td width=\"21%\" valign=\"top\" class=\"sm\"><a href=\"mailto:{$row["email"]}\">";
	if ($row["name"]) echo $row["name"];
	else echo rtrim($row["email"]);
	echo "</a></td>
          <td width=\"52%\" valign=\"top\" class=\"sm\"><a href=\"javascript:newWindow('";
		  if ($mailservertype == "imap" && $row["uid"]) echo "readmessageimap.php";
		  else echo "readmessage.php";
		  echo "?sid={$row["id"]}')\">{$row["subject"]}</a></td>
          <td width=\"7%\" valign=\"top\" class=\"sm\" align=\"center\"><a href=\"spam.php?save={$row["id"]}\"><img src=\"images/icon_save.gif\" alt=\"Move this message to the Inbox\" border=\"0\"></a></td></tr>";
}

echo "</table>
      </td>
  </tr>
</table>
$footer";
?>