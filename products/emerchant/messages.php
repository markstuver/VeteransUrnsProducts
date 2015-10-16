<?php
@set_time_limit(0);
$emnoinactivitycheck = "true";
include "../admin/checklicense.inc.php";
include "checklogin.inc.php";
include "emfunc.inc.php";
if ($showsearch) $pagetitle = "Search Results";
else $pagetitle = "Message History";
include "template.inc.php";
// Get context help for this page...
$contexthelppage = "inquiries";
include "emhelp.inc.php";

// Open database...
$db = @mysqli_connect("$databaseserver", "$databaseuser", "$databasepasswd", "$databasename");

// Get searchstring for search results...
if ($showsearch) $result = @mysqli_query($db, "SELECT * FROM emerchant_searches WHERE id='$showsearch'");
$searchstring = @mysqli_result($result, 0, "searchstring");
$searchtype = @mysqli_result($result, 0, "searchtype");
$datecriteria = @mysqli_result($result, 0, "datecriteria");

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
if ($showsearch) emerchant_topbar("Search Results");
else emerchant_topbar("Customer Message History");

if ($delete) {
	if ($yes) {
		if ($mailservertype == "imap") {
			// Get UID of this message...
			$result = @mysqli_query($db, "SELECT uid FROM emerchant_messages WHERE id='$delete'");
			$deleteuid = @mysqli_result($result,0,"uid");
			if ($deleteuid) {
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
				if($authenticated) {
					@imap_delete($popfp,$deleteuid,FT_UID);
					@imap_close($popfp, CL_EXPUNGE);
				}
			}
		}
		if (file_exists("$ashoppath/emerchant/mail/cust-$delete")) unlink("$ashoppath/emerchant/mail/cust-$delete");
		@mysqli_query($db, "DELETE FROM emerchant_messages WHERE id='$delete'");
		$notice = "Message deleted!";
		if (!empty($history) && is_numeric($history)) echo "<script language=\"JavaScript\">window.location.href='history.php?customer=$history&notice=$notice';</script>";
	} else {
		echo "<div align=\"center\" class=\"heading3\"><br>Delete Message</div>
		<center><p class=\"sm\">Are you sure you want to delete this message?</p>
		<form action=\"messages.php\" method=\"post\"><input type=\"hidden\" name=\"delete\" value=\"$delete\"><input type=\"hidden\" name=\"history\" value=\"$history\"><input type=\"submit\" name=\"yes\" value=\"Yes\"> <input type=\"button\" name=\"no\" value=\"No\" onClick=\"javascript:history.back()\"></form></center>";
		echo "</table></td></tr></table>$footer";
		exit;
	}
}

$noticecolor = "#000099";

if ($error == "incompletesearch") {
	$noticecolor = "#FF8888";
	$notice = "Required search field missing!";
}

echo "<table width=\"100%\" border=\"0\" cellspacing=\"2\" cellpadding=\"1\" bgcolor=\"#FFFFFF\" bordercolor=\"#FFFFFF\">
              <tr align=\"center\">
			    <td bgcolor=\"#FFFFFF\" width=\"20%\" align=\"left\"><a href=\"inquiries.php?checkmail=true\"><img src=\"images/button_checkmail.gif\" border=\"0\" alt=\"Check for new mail\"></a></td>
				<td bgcolor=\"#FFFFFF\" class=\"heading3\" width=\"40%\" align=\"right\">Choose inbox:</td>
                <td class=\"nav\" width=\"20%\"><a href=\"inquiries.php\" class=\"nav\">Customers</a></td>
                <td class=\"nav\" width=\"20%\"><a href=\"vendormail.php\" class=\"nav\">Vendors</a></td>
              </tr>
              <tr align=\"center\">
			    <td bgcolor=\"#FFFFFF\" width=\"20%\">&nbsp;</td>
				<td bgcolor=\"#FFFFFF\" width=\"40%\">&nbsp;</td>";
if ($showsearch) echo "<td class=\"nav\" width=\"20%\"><a href=\"messages.php\" class=\"nav\">Message History</a></td>";
else echo "<td class=\"nav\" width=\"20%\">Message History</td>";
echo "<td class=\"nav\" width=\"20%\"><a href=\"vendormessages.php\" class=\"nav\">Message History</a></td>
              </tr>
            </table>
			<form action=\"search.php\" method=\"post\" style=\"margin-bottom: 0px;\">
			<table width=\"99%\" border=\"0\" cellspacing=\"2\" cellpadding=\"2\" align=\"center\"><tr><td class=\"nav\" width=\"100%\" nowrap>Search for: <input type=\"text\" name=\"searchstring\" value=\"$searchstring\" size=\"30\"> in: <select name=\"searchtype\"><option value=\"subject\""; if ($searchtype == "subject") echo " selected"; echo ">Subject</option><option value=\"message\""; if ($searchtype == "message") echo " selected"; echo ">Message text</option><option value=\"both\""; if ($searchtype == "both") echo " selected"; echo ">Both</option></select> of messages dated: <select name=\"datecriteria\"><option value=\"today\""; if ($datecriteria == "today") echo " selected"; echo ">Today</option><option value=\"last7days\""; if ($datecriteria == "last7days") echo " selected"; echo ">Last 7 days</option><option value=\"last30days\""; if ($datecriteria == "last30days") echo " selected"; echo ">Last 30 days</option><option value=\"last3months\""; if ($datecriteria == "last3months") echo " selected"; echo ">Last 3 months</option><option value=\"thisyear\""; if ($datecriteria == "thisyear") echo " selected"; echo ">This year</option><option value=\"lastyear\""; if ($datecriteria == "lastyear") echo " selected"; echo ">Last year</option><option value=\"anytime\""; if ($datecriteria == "anytime") echo " selected"; echo ">Anytime</option> <input type=\"image\" src=\"images/button_search.gif\" border=\"0\" align=\"absbottom\"></td></tr></table></form>
			<table width=\"99%\" border=\"0\" cellspacing=\"2\" cellpadding=\"1\" bgcolor=\"#d0d0d0\" align=\"center\"><tr><td><table width=\"100%\" border=\"0\" cellspacing=\"0\" cellpadding=\"2\">
        <tr bgcolor=\"#808080\"><td class=\"heading3_wht\">Message History <a href=\"javascript:;\" onMouseOut=\"MM_swapImgRestore()\" onMouseOver=\"MM_swapImage('Image1','','images/contexthelpicon_over.gif',1)\"><img src=\"images/contexthelpicon.gif\" width=\"14\" height=\"15\" border=\"0\" name=\"Image1\" align=\"absmiddle\" onclick=\"return overlib('$tip1');\" onmouseout=\"return nd();\"></a></td><td class=\"heading3_wht\" align=\"right\"><font color=\"$noticecolor\">$notice</font></td></tr></table></td></tr></table>
      <table width=\"99%\" border=\"0\" cellspacing=\"2\" cellpadding=\"1\" bgcolor=\"#d0d0d0\" align=\"center\">
        <tr bgcolor=\"#808080\"> 
		  <td width=\"4%\" class=\"heading3_wht\">&nbsp;</td>
          <td width=\"18%\" class=\"heading3_wht\"><b class=\"heading3_wht\">Date</b></td>
          <td width=\"21%\" class=\"heading3_wht\"><b>Customer</b></td>
          <td class=\"heading3_wht\"><b>Subject</b></td>
          <td width=\"103\" class=\"heading3_wht\">&nbsp; </td>
        </tr>";
if ($showsearch) $result = @mysqli_query($db, "SELECT * FROM emerchant_searchresult, emerchant_messages WHERE emerchant_searchresult.searchid='$showsearch' AND emerchant_searchresult.messageid=emerchant_messages.id ORDER BY emerchant_messages.date DESC");
else $result = @mysqli_query($db, "SELECT * FROM emerchant_messages ORDER BY date DESC");
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
		$byuser = $row["user"];
	} else {
		$noreplyicon = FALSE;
		$tablebgcolor = "#e0e0e0";
		$byuser = "";
	}
	$customerresult = @mysqli_query($db, "SELECT * FROM customer WHERE customerid='".$row["customerid"]."'");
	if (@mysqli_num_rows($customerresult)) $customerrow = @mysqli_fetch_array($customerresult);
	else unset($customerrow);
	if (!empty($affiliateid) && $customerrow["affiliateid"] != $affiliateid) continue;
	if ($customerrow["firstname"] && $customerrow["lastname"]) $name = $customerrow["firstname"]." ".$customerrow["lastname"];
	else if ($customerrow["firstname"]) $name = $customerrow["firstname"];
	else if ($customerrow["lastname"]) $name = $customerrow["lastname"];
	else $name = "Unknown";
	echo "<tr bgcolor=\"$tablebgcolor\"> 
	      <td width=\"4%\" class=\"heading3_wht\">";
	if ($row["attachments"]) echo "<img src=\"images/icon_attachments.gif\">";
	else echo "&nbsp;";
	echo "</td>
          <td width=\"18%\" valign=\"top\" class=\"sm\">{$row["date"]}</td>
          <td width=\"21%\" valign=\"top\" class=\"sm\"><a href=\"mailto:{$customerrow["email"]}\">$name</a></td>
          <td valign=\"top\" class=\"sm\">";
		  if (!empty($byuser)) echo "$byuser: ";
		  echo "
		  <a href=\"javascript:newWindow('";
		  if ($mailservertype == "imap" && $row["uid"]) echo "readmessageimap.php";
		  else echo "readmessage.php";
		  echo "?id={$row["id"]}&mailbox=archive')\">{$row["subject"]}</a></td>
          <td width=\"103\" valign=\"top\" class=\"sm\">";
	if (!$noreplyicon) {
		echo "<a href=\"javascript:newWindow('";
		  if ($mailservertype == "imap" && $row["uid"]) echo "composemessageimap.php";
		  else echo "composemessage.php";
		  echo "?reply={$row["id"]}&mailbox=archive')\"><img src=\"images/icon_reply.gif\" alt=\"Reply to this message.\" border=\"0\"></a>&nbsp;<a href=\"forward.php?id={$row["id"]}&mailbox=archive\"><img src=\"images/icon_forward.gif\" alt=\"Forward this message.\" border=\"0\"></a>&nbsp;";
	} else echo "<img src=\"images/spacer.gif\" width=\"48\" height=\"1\">&nbsp;";
	echo "<a href=\"messages.php?delete={$row["id"]}\"><img src=\"images/icon_trash.gif\" width=\"15\" height=\"15\" alt=\"Delete this message.\" border=\"0\"></a>";
	if ($customerrow) echo "&nbsp;<a href=\"customer.php?id={$customerrow["customerid"]}\"><img src=\"images/icon_profile.gif\" alt=\"View customer profile\" border=\"0\"></a>&nbsp;<a href=\"history.php?customer={$customerrow["customerid"]}\"><img src=\"images/icon_history.gif\" alt=\"View history for ".$customerrow["firstname"]." ".$customerrow["lastname"].".\" border=\"0\"></a>";
	echo "</td></tr>";
}
	if ($numberofrows > 5) {
		echo "<table width=\"100%\" border=\"0\" cellpadding=\"5\" cellspacing=\"0\"><tr><td align=\"center\"><font face=\"Arial, Helvetica, sans-serif\" size=\"2\">";
		if ($numberofpages > 1) {
			echo "<b>Page: </b>";
			if ($resultpage > 1) {
				$previouspage = $resultpage-1;
				echo "<<<a href=\"messages.php?resultpage=$previouspage&showsearch=$showsearch\"><b>Previous</b></a>&nbsp;&nbsp;";
			}
			$page = 1;
			for ($i = $startpage; $i <= $numberofpages; $i++) {
				if ($page > 20) break;
				if ($i != $resultpage) echo "<a href=\"messages.php?resultpage=$i&showsearch=$showsearch\">";
				echo "$i";
				if ($i != $resultpage) echo "</a>";
				echo "&nbsp;&nbsp;";
				$page++;
			}
			if ($resultpage < $numberofpages) {
				$nextpage = $resultpage+1;
				echo "<a href=\"messages.php?resultpage=$nextpage&showsearch=$showsearch\"><b>Next</b></a>>>";
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