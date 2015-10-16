<?php
$emnoinactivitycheck = "true";
include "../admin/checklicense.inc.php";
include "checklogin.inc.php";
include "emfunc.inc.php";
$pagetitle = "Message History";
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

// Make sure the page isn't stored in the browsers cache...
header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
header("Cache-Control: no-store, no-cache, must-revalidate");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

echo $header;
emerchant_sidebar();
echo "<td valign=\"top\">";
emerchant_topbar("Vendor Message History");

if ($delete) {
	if ($yes) {
		if ($mailservertype == "imap") {
			// Get UID of this message...
			$result = @mysqli_query($db, "SELECT uid FROM emerchant_vendormessages WHERE id='$delete'");
			$deleteuid = @mysqli_result($result,0,"uid");
			if ($deleteuid) {
				// Get mail parameters...
				$result = @mysqli_query($db, "SELECT * FROM emerchant_configuration");
				while ($row = @mysqli_fetch_array($result)) {
					if ($row["confname"] == "pophost2") $hostname = $row["confvalue"];
					if ($row["confname"] == "popuser2") $popuser = $row["confvalue"];
					if ($row["confname"] == "poppass2") $poppass = $row["confvalue"];
					if ($row["confname"] == "popport2") $port = $row["confvalue"];
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
					@imap_delete($popfp,$deleteuid,FT_UID);
					@imap_close($popfp, CL_EXPUNGE);
				}
			}
		}	
		if (file_exists("$ashoppath/emerchant/mail/vend-$delete")) unlink("$ashoppath/emerchant/mail/vend-$delete");
		@mysqli_query($db, "DELETE FROM emerchant_vendormessages WHERE id='$delete'");
		$notice = "Message deleted!";
	} else {
		echo "<div align=\"center\" class=\"heading3\"><br>Delete Message</div>
		<center><p class=\"sm\">Are you sure you want to delete this message?</p>
		<form action=\"vendormessages.php\" method=\"post\"><input type=\"hidden\" name=\"delete\" value=\"$delete\"><input type=\"submit\" name=\"yes\" value=\"Yes\"> <input type=\"button\" name=\"no\" value=\"No\" onClick=\"javascript:history.back()\"></form></center>";
		echo "</table></td></tr></table>$footer";
		exit;
	}
}

$noticecolor = "#000099";
echo "<table width=\"100%\" border=\"0\" cellspacing=\"2\" cellpadding=\"1\" bgcolor=\"#FFFFFF\" bordercolor=\"#FFFFFF\">
              <tr align=\"center\">
			    <td bgcolor=\"#FFFFFF\" width=\"20%\" align=\"left\"><a href=\"vendormail.php?checkmail=true\"><img src=\"images/button_checkmail.gif\" border=\"0\" alt=\"Check for new mail\"></a></td>
				<td bgcolor=\"#FFFFFF\" class=\"heading3\" width=\"40%\" align=\"right\">Choose inbox:</td>
                <td class=\"nav\" width=\"20%\"><a href=\"inquiries.php\" class=\"nav\">Customers</a></td>
                <td class=\"nav\" width=\"20%\"><a href=\"vendormail.php\" class=\"nav\">Vendors</a></td>
              </tr>
              <tr align=\"center\">
			    <td bgcolor=\"#FFFFFF\" width=\"20%\">&nbsp;</td>
				<td bgcolor=\"#FFFFFF\" width=\"40%\">&nbsp;</td>
                <td class=\"nav\" width=\"20%\"><a href=\"messages.php\" class=\"nav\">Message History</a></td>
                <td class=\"nav\" width=\"20%\">Message History</a></td>
              </tr>
            </table>
			<table width=\"99%\" border=\"0\" cellspacing=\"2\" cellpadding=\"1\" bgcolor=\"#d0d0d0\" align=\"center\"><tr><td><table width=\"100%\" border=\"0\" cellspacing=\"0\" cellpadding=\"2\">
        <tr bgcolor=\"#808080\"><td class=\"heading3_wht\">Message History <a href=\"javascript:;\" onMouseOut=\"MM_swapImgRestore()\" onMouseOver=\"MM_swapImage('Image1','','images/contexthelpicon_over.gif',1)\"><img src=\"images/contexthelpicon.gif\" width=\"14\" height=\"15\" border=\"0\" name=\"Image1\" align=\"absmiddle\" onclick=\"return overlib('$tip1');\" onmouseout=\"return nd();\"></a></td><td class=\"heading3_wht\" align=\"right\"><font color=\"#00FF00\">$notice</font></td></tr></table></td></tr></table>
      <table width=\"99%\" border=\"0\" cellspacing=\"2\" cellpadding=\"1\" bgcolor=\"#d0d0d0\" align=\"center\">
        <tr bgcolor=\"#808080\"> 
		  <td width=\"4%\" class=\"heading3_wht\">&nbsp;</td>
          <td width=\"18%\" class=\"heading3_wht\"><b class=\"heading3_wht\">Date</b></td>
          <td width=\"21%\" class=\"heading3_wht\"><b>Vendor</b></td>
          <td class=\"heading3_wht\"><b>Subject</b></td>
          <td width=\"103\" class=\"heading3_wht\">&nbsp; </td>
        </tr>";

$result = @mysqli_query($db, "SELECT * FROM emerchant_vendormessages ORDER BY date DESC");
$numberofrows = intval(@mysqli_num_rows($result));
$displayitems = 13;
$numberofpages = ceil($numberofrows/$displayitems);
if ($resultpage > 1) $startrow = (intval($resultpage)-1) * $displayitems;
else {
	$resultpage = 1;
	$startrow = 0;
}
$startpage = $resultpage - 9;
if ($numberofpages - $resultpage < 10) {
	$pagesleft = $numberofpages - $resultpage;
	$startpage = $startpage - (10 - $pagesleft);
}
if ($startpage < 1) $startpage = 1;
$stoprow = $startrow + $displayitems;
@mysqli_data_seek($result, $startrow);
$thisrow = $startrow;
while (($row = @mysqli_fetch_array($result)) && ($thisrow < $stoprow)) {
	$thisrow++;
	if (isset($row["replyto"])) {
		$noreplyicon = TRUE;
		$tablebgcolor = "#c0c0c0";
	} else {
		$noreplyicon = FALSE;
		$tablebgcolor = "#e0e0e0";
	}
	$vendorresult = @mysqli_query($db, "SELECT * FROM emerchant_vendor WHERE vendorid='".$row["vendorid"]."'");
	if (@mysqli_num_rows($vendorresult)) $vendorrow = @mysqli_fetch_array($vendorresult);
	else unset($vendorrow);
	if ($vendorrow["name"]) $name = $vendorrow["name"];
	else $name = "Unknown";
	if ($row["vendcontactid"]) {
		$vendorcontactresult = @mysqli_query($db, "SELECT * FROM emerchant_vendcontact WHERE vendcontactid='{$row["vendcontactid"]}'");
		if (@mysqli_num_rows($vendorcontactresult)) $vendorcontactrow = @mysqli_fetch_array($vendorcontactresult);
		else unset($vendorcontactrow);
		if ($vendorcontactrow["email"]) $email = $vendorcontactrow["email"];
		else $email = $vendorrow["email"];
	} else $email = $vendorrow["email"];
	echo "<tr bgcolor=\"$tablebgcolor\"> 
	      <td width=\"4%\" class=\"heading3_wht\">";
	if ($row["attachments"]) echo "<img src=\"images/icon_attachments.gif\">";
	else echo "&nbsp;";
	echo "</td>
          <td width=\"18%\" valign=\"top\" class=\"sm\">{$row["date"]}</td>
          <td width=\"21%\" valign=\"top\" class=\"sm\"><a href=\"mailto:$email\">$name</a></td>
          <td valign=\"top\" class=\"sm\"><a href=\"javascript:newWindow('";
		  if ($mailservertype == "imap" && $row["uid"]) echo "readmessageimap.php";
		  else echo "readmessage.php";
		  echo "?id={$row["id"]}&mailbox=varchive')\">{$row["subject"]}</a></td>
          <td width=\"103\" valign=\"top\" class=\"sm\">";
	if (!$noreplyicon) {
		echo "<a href=\"javascript:newWindow('";
		  if ($mailservertype == "imap" && $row["uid"]) echo "composemessageimap.php";
		  else echo "composemessage.php";
		  echo "?replyvendor={$row["id"]}&mailbox=varchive')\"><img src=\"images/icon_reply.gif\" alt=\"Reply to this message.\" border=\"0\"></a>&nbsp;<a href=\"forward.php?id={$row["id"]}&mailbox=varchive\"><img src=\"images/icon_forward.gif\" alt=\"Forward this message.\" border=\"0\"></a>&nbsp;";
	} else echo "<img src=\"images/spacer.gif\" width=\"48\" height=\"1\">&nbsp;";
	echo "<a href=\"vendormessages.php?delete={$row["id"]}\"><img src=\"images/icon_trash.gif\" width=\"15\" height=\"15\" alt=\"Delete this message.\" border=\"0\"></a>";
	if ($vendorrow) echo "&nbsp;<a href=\"vendor.php?id={$vendorrow["vendorid"]}\"><img src=\"images/icon_profile.gif\" alt=\"View vendor profile\" border=\"0\"></a>&nbsp;<a href=\"vendorhistory.php?vendor={$vendorrow["vendorid"]}\"><img src=\"images/icon_history.gif\" alt=\"View history for ".$vendorrow["name"].".\" border=\"0\"></a>";
	echo "</td></tr>";
}
	if ($numberofrows > 5) {
		echo "<table width=\"100%\" border=\"0\" cellpadding=\"5\" cellspacing=\"0\"><tr><td align=\"center\"><font face=\"Arial, Helvetica, sans-serif\" size=\"2\">";
		if ($numberofpages > 1) {
			echo "<b>Page: </b>";
			if ($resultpage > 1) {
				$previouspage = $resultpage-1;
				echo "<<<a href=\"vendormessages.php?resultpage=$previouspage\"><b>Previous</b></a>&nbsp;&nbsp;";
			}
			$page = 1;
			for ($i = $startpage; $i <= $numberofpages; $i++) {
				if ($page > 20) break;
				if ($i != $resultpage) echo "<a href=\"vendormessages.php?resultpage=$i\">";
				echo "$i";
				if ($i != $resultpage) echo "</a>";
				echo "&nbsp;&nbsp;";
				$page++;
			}
			if ($resultpage < $numberofpages) {
				$nextpage = $resultpage+1;
				echo "<a href=\"vendormessages.php?resultpage=$nextpage\"><b>Next</b></a>>>";
			}
		}
		echo "</td></tr></table>";
	}
echo "</table>
      </td>
  </tr>
</table>
$footer";
?>