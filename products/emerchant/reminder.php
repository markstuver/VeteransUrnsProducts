<?php
$popuplogincheck = TRUE;
include "../admin/checklicense.inc.php";
include "checklogin.inc.php";

// Open database...
$db = @mysqli_connect("$databaseserver", "$databaseuser", "$databasepasswd", "$databasename");

if ($subject && $reminder) {
	if ($Submit == "Delete") @mysqli_query($db, "DELETE FROM emerchant_reminders WHERE id='$id'");
	else {
		if ($id) $result = @mysqli_query($db, "UPDATE emerchant_reminders SET duedate='$duedate', subject='$subject', reference='$reference', reminder='$reminder' , username='$username' WHERE id='$id'");
		else $result = @mysqli_query($db, "INSERT INTO emerchant_reminders (duedate, subject, reference, reminder, username) VALUES ('$duedate', '$subject', '$reference', '$reminder', '$username')");
	}
	echo "<html><head>\n<script language=\"JavaScript\">
	opener.location.reload();
	this.close();
	</script>\n</head></html>";
	exit;
}

if ($id) {
	$result = @mysqli_query($db, "SELECT * FROM emerchant_reminders WHERE id='$id'");
	$row = @mysqli_fetch_array($result);
	$duedate = $row["duedate"];
	$subject = $row["subject"];
	$reference = $row["reference"];
	$reminder = $row["reminder"];
	$username = $row["username"];
}

if ($emerchant_user == "admin") {
	$userlist = "\n<select name=\"username\"><option value=\"\"";
	if ($username == "") $userlist .= " selected";
	$userlist .= ">All Users</option>";
	$result = @mysqli_query($db, "SELECT username FROM emerchant_user ORDER BY username ASC");
	while ($row = @mysqli_fetch_array($result)) {
		$userlist .= "\n<option value=\"{$row["username"]}\"";
		if ($username == $row["username"]) $userlist .= " selected";
		$userlist .= ">{$row["username"]}</option>";
	}
	$userlist .= "\n</select>";
}

echo "<html><head><title>";
if ($id) echo "View/Edit Reminder";
else echo "Create Reminder";
echo " - $ashopname</title>
<meta http-equiv=\"Content-Type\" content=\"text/html; charset=iso-8859-1\">
<link rel=\"stylesheet\" href=\"emerchant.css\" type=\"text/css\">
</head>
<body text=\"#000000\" topmargin=\"0\">
<div align=\"center\">
  <form name=\"form1\" method=\"post\" action=\"reminder.php\">
    <br>
    <table width=\"580\" border=\"0\" cellspacing=\"2\" cellpadding=\"1\" align=\"center\" bgcolor=\"#d0d0d0\">
      <tr bgcolor=\"#808080\"> 
        <td class=\"formlabel\" colspan=\"2\" align=\"right\"> 
          <div align=\"center\" class=\"heading3_wht\">";
if ($id) echo "View/Edit Reminder";
else echo "Create Reminder";
echo "</div>
        </td>
      </tr>
      <tr> 
        <td class=\"formlabel\" bgcolor=\"#d0d0d0\" width=\"95\" align=\"right\">Due Date: 
        </td>
        <td class=\"sm\" bgcolor=\"#d0d0d0\" width=\"495\"> 
          <input type=\"text\" name=\"duedate\" maxlength=\"20\" size=\"20\" value=\"$duedate\">
        </td>
      </tr>";
if ($emerchant_user == "admin") echo "<tr> 
        <td class=\"formlabel\" bgcolor=\"#d0d0d0\" width=\"95\" align=\"right\">Assign to: 
        </td>
		<td class=\"sm\" bgcolor=\"#d0d0d0\" width=\"495\">$userlist</td>
		</tr>";
else echo "<input type=\"hidden\" name=\"username\" value=\"$emerchant_user\">";
echo "
      <tr> 
        <td class=\"formlabel\" bgcolor=\"#c0c0c0\" align=\"right\" width=\"95\">Subject: 
        </td>
        <td class=\"sm\" bgcolor=\"#c0c0c0\" width=\"495\"> 
          <input type=\"text\" name=\"subject\" maxlength=\"80\" size=\"60\" value=\"$subject\">
        </td>
      </tr>
      <tr bgcolor=\"#d0d0d0\"> 
        <td class=\"formlabel\" align=\"right\" width=\"95\">Reference: </td>
        <td class=\"sm\" width=\"495\">";
		if (substr($reference,0,7) == "http://") echo "<a href=\"$reference\" target=\"_blank\">$reference</a>";
		else echo "<input type=\"text\" name=\"reference\" maxlength=\"80\" size=\"60\" value=\"$reference\">";
		echo "
        </td>
      </tr>
      <tr align=\"center\"> 
        <td colspan=\"2\" height=\"353\"><b><span class=\"formlabel\">Reminder</span></b><br>
          <textarea name=\"reminder\" cols=\"65\" rows=\"20\">$reminder</textarea>
        </td>
      </tr>
      <tr align=\"center\"> 
        <td colspan=\"2\">
          <input type=\"submit\" name=\"Submit\" value=\"Save Changes\">";
if ($id) echo "<input type=\"hidden\" name=\"id\" value=\"$id\"> <input type=\"submit\" name=\"Submit\" value=\"Delete\">";
echo " <input type=\"button\" name=\"Cancel\" onClick=\"window.close()\" value=\"Cancel/Exit\">
        </td>
      </tr>
    </table>
  </form>
</div>
</body>
</html>";
?>