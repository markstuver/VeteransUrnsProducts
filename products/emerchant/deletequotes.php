<?php
include "../admin/ashopconstants.inc.php";
include "../admin/checklicense.inc.php";
include "checklogin.inc.php";
include "emfunc.inc.php";
$pagetitle = "Delete Quotes";
include "template.inc.php";

// Open database...
$db = @mysqli_connect("$databaseserver", "$databaseuser", "$databasepasswd", "$databasename");

// Delete quotes...
if ($delete_x && $year && $month && $day) {
	if ($yes) {
		$beforedate = "$year-$month-$day 00:00:00";
		if (!empty($affiliateid)) $result = @mysqli_query($db, "DELETE FROM emerchant_quotes WHERE date<'$beforedate' AND user='$emerchant_user'");
		else $result = @mysqli_query($db, "DELETE FROM emerchant_quotes WHERE date<'$beforedate'");
		header("Location: viewquotes.php?notice=Quotes Successfully Deleted");
		exit;
    }
	elseif ($no) header("Location: viewquotes.php");
	else {
		echo $header;
		emerchant_sidebar();
		echo "<td valign=\"top\">";
		emerchant_topbar($pagetitle);
		echo "<table width=\"650\" border=\"0\" cellpadding=\"5\" align=\"center\">
        <tr> 
          <td height=\"172\" align=\"center\">
		  <table width=\"600\" border=\"0\" cellpadding=\"0\"><tr><td>
		<div class=\"heading3\"><br>Delete unconverted quotes</div>
        <p class=\"warning\">Are you sure?</p>
		<form action=\"deletequotes.php\" method=\"post\">
		<table width=\"500\" border=\"0\" cellspacing=\"0\" cellpadding=\"3\">
		<tr>
        <td width=\"100%\" valign=\"top\"><input type=\"submit\" name=\"yes\" value=\"Yes\">
		<input type=\"submit\" name=\"no\" value=\"No \"></td>
		</tr></table><input type=\"hidden\" name=\"year\" value=\"$year\"><input type=\"hidden\" name=\"month\" value=\"$month\"><input type=\"hidden\" name=\"day\" value=\"$day\"><input type=\"hidden\" name=\"delete_x\" value=\"1\"></form>
		</td></tr></table>
		$footer";
		exit;
	}
} 

// Get current date...
$date = explode("-",date("Y-m-d", time()+$timezoneoffset));
$year = $date[0];
$month = $date[1];
$day = $date[2];

echo $header;
emerchant_sidebar();
echo "<td valign=\"top\">";
emerchant_topbar("Delete Quotes");
echo "<table width=\"650\" border=\"0\" cellpadding=\"5\" align=\"center\">
        <tr> 
          <td height=\"172\" align=\"center\">
		  <table width=\"600\" border=\"0\" cellpadding=\"0\"><tr><td>
			<div class=\"heading3\"><br>Delete unconverted quotes created before...</div><form action=\"deletequotes.php\" method=\"post\" name=\"deleteform\" style=\"margin-bottom: 0px;\"><input type=\"text\" name=\"year\" value=\"$year\" size=\"4\"> <select name=\"month\">
			<option value=\"01\""; if ($month == "01") echo " selected"; echo ">Jan</option>
			<option value=\"02\""; if ($month == "02") echo " selected"; echo ">Feb</option>
			<option value=\"03\""; if ($month == "03") echo " selected"; echo ">Mar</option>
			<option value=\"04\""; if ($month == "04") echo " selected"; echo ">Apr</option>
			<option value=\"05\""; if ($month == "05") echo " selected"; echo ">May</option>
			<option value=\"06\""; if ($month == "06") echo " selected"; echo ">Jun</option>
			<option value=\"07\""; if ($month == "07") echo " selected"; echo ">Jul</option>
			<option value=\"08\""; if ($month == "08") echo " selected"; echo ">Aug</option>
			<option value=\"09\""; if ($month == "09") echo " selected"; echo ">Sep</option>
			<option value=\"10\""; if ($month == "10") echo " selected"; echo ">Oct</option>
			<option value=\"11\""; if ($month == "11") echo " selected"; echo ">Nov</option>
			<option value=\"12\""; if ($month == "12") echo " selected"; echo ">Dec</option>
			</select> <input type=\"text\" name=\"day\" value=\"$day\" size=\"4\"><input type=\"image\" src=\"images/button_delete.gif\" alt=\"Delete Quotes\" name=\"delete\" align=\"top\"></div></form></td></tr></table>
			<br><br></td></tr></table></td></tr><tr><td align=\"center\" colspan=\"2\"></td></tr></table>$footer";
?>