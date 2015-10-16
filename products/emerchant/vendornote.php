<?php
$popuplogincheck = TRUE;
include "../admin/checklicense.inc.php";
include "checklogin.inc.php";

// Open database...
$db = @mysqli_connect("$databaseserver", "$databaseuser", "$databasepasswd", "$databasename");

if ($vendorcontact) {
	$result = @mysqli_query($db, "SELECT * FROM emerchant_vendcontact WHERE vendcontactid='$vendorcontact'");
	$vendorcontactrow = @mysqli_fetch_array($result);
	$vendor = $vendorcontactrow["vendorid"];
}

if ($vendor) $vendorid = $vendor;
$result = @mysqli_query($db, "SELECT * FROM emerchant_vendor WHERE vendorid='$vendorid'");
$vendorrow = @mysqli_fetch_array($result);

if ($subject && $reference && $comment && $vendorid) {
	$modifieddate = date("Y-m-d H:i:s", time()+$timezoneoffset);
	if ($id) $result = @mysqli_query($db, "UPDATE emerchant_vendorcomments SET vendorid='$vendorid', vendcontactid='$vendorcontactid', date='$modifieddate', subject='$subject', reference='$reference', comment='$comment' WHERE id='$id'");
	else $result = @mysqli_query($db, "INSERT INTO emerchant_vendorcomments (vendorid, vendcontactid, date, subject, reference, comment) VALUES ('$vendorid', '$vendorcontactid', '$modifieddate', '$subject', '$reference', '$comment')");
	echo "<html><head>\n<script language=\"JavaScript\">";
	if ($refresh = "true") echo "opener.location.reload();";
	echo "this.close();
	</script>\n</head></html>";
	exit;
}

if ($id) {
	$result = @mysqli_query($db, "SELECT * FROM emerchant_vendorcomments WHERE id='$id'");
	$row = @mysqli_fetch_array($result);
	$vendorid = $row["vendorid"];
	$modifieddate = $row["date"];
	$subject = $row["subject"];
	$reference = $row["reference"];
	$comment = $row["comment"];
}

echo "<html><head><title>";
if ($id) echo "View/Edit Vendor Note";
else echo "Create Vendor Note";
echo " - $ashopname</title><meta http-equiv=\"Content-Type\" content=\"text/html\" charset=\"iso-8859-1\">
<link rel=\"stylesheet\" href=\"emerchant.css\" type=\"text/css\">
</head>
<body text=\"#000000\" topmargin=\"0\">
<div align=\"center\">
  <form name=\"form1\" method=\"post\" action=\"vendornote.php\">
  <input type=\"hidden\" name=\"refresh\" value=\"$refresh\">
    <br>
    <table width=\"580\" border=\"0\" cellspacing=\"2\" cellpadding=\"1\" align=\"center\" bgcolor=\"#d0d0d0\">
      <tr bgcolor=\"#808080\"> 
        <td class=\"formlabel\" colspan=\"2\" align=\"right\"> 
          <div align=\"center\" class=\"heading3_wht\">Create or edit notes regarding 
            vendor: {$vendorrow["name"]}, Vendor ID $vendorid";
if ($vendorcontact) echo "<br>Contact: {$vendorcontactrow["firstname"]} {$vendorcontactrow["lastname"]}";
echo "</div>
        </td>
      </tr>";
if ($modifieddate) echo "<tr><td class=\"formlabel\" bgcolor=\"#d0d0d0\" width=\"115\" align=\"right\">Date Modified: </td><td class=\"sm\" bgcolor=\"#d0d0d0\" width=\"455\">$modifieddate</td></tr>";

echo "<tr><td class=\"formlabel\" bgcolor=\"#c0c0c0\" align=\"right\" width=\"115\">Subject: </td><td class=\"sm\" bgcolor=\"#c0c0c0\" width=\"455\"> 
          <input type=\"text\" name=\"subject\" value=\"$subject\" maxlength=\"80\" size=\"60\">
        </td>
      </tr>
      <tr bgcolor=\"#d0d0d0\"> 
        <td class=\"formlabel\" align=\"right\" width=\"115\">Reference: </td>
        <td class=\"sm\" width=\"455\"> 
          <input type=\"text\" name=\"reference\" value=\"$reference\" maxlength=\"80\" size=\"60\">
        </td>
      </tr>
      <tr align=\"center\"> 
        <td colspan=\"2\" height=\"353\"><b><span class=\"formlabel\">Comments</span></b><br>
          <textarea name=\"comment\" cols=\"65\" rows=\"20\">$comment</textarea>
        </td>
      </tr>
      <tr align=\"center\"> 
        <td colspan=\"2\">
		  <input type=\"hidden\" name=\"vendorid\" value=\"$vendorid\">
		  <input type=\"hidden\" name=\"vendorcontactid\" value=\"$vendorcontact\">
		  <input type=\"hidden\" name=\"id\" value=\"$id\">
          <input type=\"submit\" name=\"Submit\" value=\"Save Changes\">
          <input type=\"button\" onClick=\"window.close()\" name=\"Submit\" value=\"Cancel/Exit\">
        </td>
      </tr>
    </table>
  </form>
</div>
</body>
</html>";
?>