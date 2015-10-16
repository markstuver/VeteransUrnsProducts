<?php
include "../admin/checklicense.inc.php";
include "checklogin.inc.php";
include "emfunc.inc.php";
$pagetitle = "Vendor History";
include "template.inc.php";

// Open database...
$db = @mysqli_connect("$databaseserver", "$databaseuser", "$databasepasswd", "$databasename");

if (extension_loaded("imap")) {
	$result = @mysqli_query($db, "SELECT confvalue FROM emerchant_configuration WHERE confname='mailservertype'");
	$mailservertype = @mysqli_result($result,0,"confvalue");
} else $mailservertype = "pop3";

unset($history);

$result = @mysqli_query($db, "SELECT * FROM emerchant_vendor WHERE vendorid='$vendor'");
$vendorrow = @mysqli_fetch_array($result);

$result = @mysqli_query($db, "SELECT * FROM emerchant_vendormessages WHERE vendorid='$vendor' ORDER BY date,id DESC");
while ($row = @mysqli_fetch_array($result)) {
	if ($mailservertype == "imap" && $row["uid"]) $readmessagescript = "readmessageimap.php";
	else $readmessagescript = "readmessage.php";
	$history[$row["date"]][] = "Message: <a href=\"javascript:newWindow('$readmessagescript?id={$row["id"]}&mailbox=varchive')\">{$row["subject"]}</a>";
}

$result = @mysqli_query($db, "SELECT * FROM emerchant_vendorcomments WHERE vendorid='$vendor' ORDER BY date,id DESC");
while ($row = @mysqli_fetch_array($result)) $history[$row["date"]][] = "Comment: <a href=\"javascript:newWindow('vendornote.php?vendor=$vendor&id={$row["id"]}')\">{$row["subject"]}</a>";

$result = @mysqli_query($db, "SELECT * FROM emerchant_purchaseorder WHERE vendorid='$vendor' ORDER BY date,purchaseorderid DESC");
while ($row = @mysqli_fetch_array($result)) {
	if ($row["billdate"]) $history[$row["billdate"]][] = "Bill: {$row["purchaseorderid"]} - <a href=\"editpostatus.php?bill=true&po={$row["purchaseorderid"]}\">Edit</a>";
	$history[$row["date"]][] = "Purchase Order: {$row["purchaseorderid"]} - <a href=\"purchaseorder.php?edit={$row["purchaseorderid"]}\">Edit</a>";
}

if($history) krsort($history);

echo $header;
emerchant_sidebar();
echo "<td valign=\"top\">";
emerchant_topbar("Vendor History");
if ($notice) echo "<div align=\"center\" class=\"heading3\"><br><font color=\"#000099\">$notice</font></div>";
echo "<div align=\"center\" class=\"heading3\"><br>
        <span class=\"heading2\">History for ".$vendorrow["name"].", Vendor ID 
        $vendor</span><a href=\"vendor.php?id=$vendor\"><img src=\"images/icon_profile.gif\" width=\"15\" height=\"15\" alt=\"Edit vendor information for ".$vendorrow["name"].".\" border=\"0\"></a>&nbsp;<img src=\"images/icon_history.gif\" width=\"15\" height=\"15\" alt=\"View history for  ".$vendorrow["name"].".\" border=\"0\">&nbsp;<a href=\"javascript:newWindow('vendornote.php?vendor=$vendor&refresh=true')\"><img src=\"images/icon_customernote.gif\" width=\"15\" height=\"15\" alt=\"Create a note regarding this vendor.\" border=\"0\"></a>&nbsp;<a href=\"javascript:newWindow('composemessage.php?vendor=$vendor')\"><img src=\"images/icon_mail.gif\" alt=\"Send mail.\" border=\"0\"></a></div><br>
      <table width=\"85%\" border=\"0\" cellspacing=\"2\" cellpadding=\"1\" bgcolor=\"#d0d0d0\" align=\"center\">
        <tr bgcolor=\"#808080\"> 
          <td width=\"100\" class=\"heading3_wht\"><b class=\"heading3_wht\">Date</b></td>
          <td class=\"heading3_wht\"><b>Item</b></td>
        </tr>";

if ($history) foreach ($history as $date=>$contentarray) {
	$dateandtime = explode(" ", $date);
	foreach ($contentarray as $contentnumber=>$content) {
		echo "<tr bgcolor=\"#e0e0e0\"> 
		<td width=\"100\" class=\"sm\">$dateandtime[0]</td>
		  <td class=\"sm\">$content</td>
        </tr>";
	}
}

echo "</table>
      </td>
  </tr>
</table>
$footer";
?>