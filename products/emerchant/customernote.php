<?php
$popuplogincheck = TRUE;
include "../admin/checklicense.inc.php";
include "checklogin.inc.php";

// Open database...
$db = @mysqli_connect("$databaseserver", "$databaseuser", "$databasepasswd", "$databasename");

if ($customer) $customerid = $customer;
$result = @mysqli_query($db, "SELECT * FROM customer WHERE customerid='$customerid'");
$customerrow = @mysqli_fetch_array($result);

if ($subject && $reference && $comment && $customerid) {
	$modifieddate = date("Y-m-d H:i:s", time()+$timezoneoffset);
	if ($id) $result = @mysqli_query($db, "UPDATE emerchant_comments SET customerid='$customerid', date='$modifieddate', subject='$subject', reference='$reference', comment='$comment' WHERE id='$id'");
	else $result = @mysqli_query($db, "INSERT INTO emerchant_comments (customerid, date, subject, reference, comment) VALUES ('$customerid', '$modifieddate', '$subject', '$reference', '$comment')");
	if ($reminder == "on") $result = @mysqli_query($db, "INSERT INTO emerchant_reminders (subject, reference, reminder) VALUES ('$subject', '$reference', '$comment')");
	echo "<html><head>\n<script language=\"JavaScript\">";
	if ($refresh = "true") echo "opener.location.reload();";
	echo "this.close();
	</script>\n</head></html>";
	exit;
}

if ($id) {
	$result = @mysqli_query($db, "SELECT * FROM emerchant_comments WHERE id='$id'");
	$row = @mysqli_fetch_array($result);
	$customerid = $row["customerid"];
	$modifieddate = $row["date"];
	$subject = $row["subject"];
	$reference = $row["reference"];
	$comment = $row["comment"];
}

echo "<html><head><title>";
if ($id) echo "View/Edit Customer Note";
else echo "Create Customer Note";
echo " - $ashopname</title><meta http-equiv=\"Content-Type\" content=\"text/html\" charset=\"iso-8859-1\">
<link rel=\"stylesheet\" href=\"emerchant.css\" type=\"text/css\">
</head>
<body text=\"#000000\" topmargin=\"0\">
<div align=\"center\">
  <form name=\"form1\" method=\"post\" action=\"customernote.php\">
  <input type=\"hidden\" name=\"refresh\" value=\"$refresh\">
    <br>
    <table width=\"580\" border=\"0\" cellspacing=\"2\" cellpadding=\"1\" align=\"center\" bgcolor=\"#d0d0d0\">
      <tr bgcolor=\"#808080\"> 
        <td class=\"formlabel\" colspan=\"2\" align=\"right\"> 
          <div align=\"center\" class=\"heading3_wht\">Create or edit notes regarding 
            customer: {$customerrow["firstname"]} {$customerrow["lastname"]}</div>
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
          <input type=\"text\" name=\"reference\" value=\"";
		  if ($reference) echo $reference;
		  else echo "$ashopurl/emerchant/history.php?customer=$customerid";
		  echo "\" size=\"60\">
        </td>
      </tr>";
if (!$id) echo "
      <tr bgcolor=\"#d0d0d0\"> 
        <td class=\"formlabel\" align=\"right\" width=\"115\">&nbsp;</td>
        <td class=\"sm\" width=\"455\"><input type=\"checkbox\" name=\"reminder\"> Add a reminder.
        </td>
      </tr>";
echo "
      <tr align=\"center\"> 
        <td colspan=\"2\" height=\"353\"><b><span class=\"formlabel\">Comments</span></b><br>
          <textarea name=\"comment\" cols=\"65\" rows=\"20\">$comment</textarea>
        </td>
      </tr>
      <tr align=\"center\"> 
        <td colspan=\"2\">
		  <input type=\"hidden\" name=\"customerid\" value=\"$customerid\">
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