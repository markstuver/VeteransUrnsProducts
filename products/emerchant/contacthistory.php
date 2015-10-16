<?php
include "../admin/checklicense.inc.php";
include "checklogin.inc.php";
include "emfunc.inc.php";
$pagetitle = "Vendor Contact History";
include "template.inc.php";

// Open database...
$db = @mysqli_connect("$databaseserver", "$databaseuser", "$databasepasswd", "$databasename");

unset($history);

$result = @mysqli_query($db, "SELECT * FROM emerchant_vendcontact WHERE vendcontactid='$vendorcontact'");
$vendorcontactrow = @mysqli_fetch_array($result);
$vendor = $vendorcontactrow["vendorid"];

$result = @mysqli_query($db, "SELECT * FROM emerchant_vendor WHERE vendorid='$vendor'");
$vendorrow = @mysqli_fetch_array($result);

$result = @mysqli_query($db, "SELECT * FROM emerchant_vendormessages WHERE vendcontactid='$vendorcontact'");
while ($row = @mysqli_fetch_array($result)) $history[$row["date"]] = "Message: <a href=\"javascript:newWindow('readmessage.php?id={$row["id"]}&mailbox=varchive')\">{$row["subject"]}</a>";

$result = @mysqli_query($db, "SELECT * FROM emerchant_vendorcomments WHERE vendcontactid='$vendorcontact'");
while ($row = @mysqli_fetch_array($result)) $history[$row["date"]] = "Comment: <a href=\"javascript:newWindow('vendornote.php?vendor=$vendor&id={$row["id"]}')\">{$row["subject"]}</a>";

if($history) krsort($history);

echo $header;
emerchant_sidebar();
echo "<td valign=\"top\">";
emerchant_topbar("Vendor Contact History");
if ($notice) echo "<div align=\"center\" class=\"heading3\"><br><font color=\"#000099\">$notice</font></div>";
echo "<div align=\"center\" class=\"heading3\"><br>
        <span class=\"heading2\">History for {$vendorcontactrow["firstname"]} {$vendorcontactrow["lastname"]}, vendor: <a href=\"vendor.php?id=$vendor\">".$vendorrow["name"]."</a></span><a href=\"vendorcontact.php?id=$vendorcontact&vendorid=$vendor\"><img src=\"images/icon_profile.gif\" width=\"15\" height=\"15\" alt=\"Edit vendor contact information for {$vendorcontactrow["firstname"]} {$vendorcontactrow["lastname"]}\" border=\"0\"></a>&nbsp;<a href=\"javascript:newWindow('vendornote.php?vendorcontact=$vendorcontact&refresh=true')\"><img src=\"images/icon_customernote.gif\" width=\"15\" height=\"15\" alt=\"Create a note regarding this vendor contact.\" border=\"0\"></a>&nbsp;<a href=\"javascript:newWindow('composemessage.php?vendor=$vendor&vendorcontact=$vendorcontact')\"><img src=\"images/icon_mail.gif\" alt=\"Send mail.\" border=\"0\"></a></div><br>
      <table width=\"85%\" border=\"0\" cellspacing=\"2\" cellpadding=\"1\" bgcolor=\"#d0d0d0\" align=\"center\">
        <tr bgcolor=\"#808080\"> 
          <td width=\"100\" class=\"heading3_wht\"><b class=\"heading3_wht\">Date</b></td>
          <td class=\"heading3_wht\"><b>Item</b></td>
        </tr>";

if ($history) foreach ($history as $date=>$content) {
	$dateandtime = explode(" ", $date);
	echo "<tr bgcolor=\"#e0e0e0\"> 
          <td width=\"100\" class=\"sm\">$dateandtime[0]</td>
          <td class=\"sm\">$content</td>
        </tr>";
}

echo "</table>
      </td>
  </tr>
</table>
$footer";
?>