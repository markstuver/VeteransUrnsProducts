<?php
include "../admin/checklicense.inc.php";
include "checklogin.inc.php";
include "emfunc.inc.php";
$pagetitle = "Merge Customer Profiles";
include "template.inc.php";
// Get context help for this page...
$contexthelppage = "mergeprofiles";
include "emhelp.inc.php";

// Open database...
$db = @mysqli_connect("$databaseserver", "$databaseuser", "$databasepasswd", "$databasename");

// Create list of selected customer profiles...
if (is_array($_POST)) foreach($_POST as $key=>$value) if (strstr($key,"merge") && $value == "on") $profiles[] = str_replace("merge","",$key);

// Merge customer profiles...
if ($mergeto && is_array($profiles)) {
	$result = @mysqli_query($db, "SELECT * FROM customer WHERE customerid='$mergeto'");
	$alternativeemails = @mysqli_result($result,0,"alternativeemails");
	if ($alternativeemails) $alternativeemails .= ", ";
	$alternativephones = @mysqli_result($result,0,"alternativephones");
	if ($alternativephones) $alternativephones .= ", ";
	foreach($profiles as $customernumber=>$customerid) {
		if ($customerid != $mergeto) {
			$result = @mysqli_query($db, "SELECT * FROM customer WHERE customerid='$customerid'");
			$email = @mysqli_result($result, 0, "email");
			$phone = @mysqli_result($result, 0, "phone");
			if ($email && !strstr($alternativeemails,$email)) $alternativeemails .= "$email, ";
			if ($phone && !strstr($alternativephones,$phone)) $alternativephones .= "$phone, ";
			@mysqli_query($db, "UPDATE orders SET customerid='$mergeto' WHERE customerid='$customerid'");
			@mysqli_query($db, "UPDATE emerchant_comments SET customerid='$mergeto' WHERE customerid='$customerid'");
			@mysqli_query($db, "UPDATE emerchant_messages SET customerid='$mergeto' WHERE customerid='$customerid'");
			@mysqli_query($db, "UPDATE emerchant_quotes SET customerid='$mergeto' WHERE customerid='$customerid'");
			@mysqli_query($db, "UPDATE emerchant_purchaseorder SET customerid='$mergeto' WHERE customerid='$customerid'");
			@mysqli_query($db, "UPDATE memberorders SET customerid='$mergeto' WHERE customerid='$customerid'");
			@mysqli_query($db, "DELETE FROM customer WHERE customerid='$customerid'");
			@mysqli_query($db, "DELETE FROM shipping WHERE customerid='$customerid'");
		}
	}
	$alternativeemails = substr($alternativeemails,0,-2);
	$alternativephones = substr($alternativephones,0,-2);
	@mysqli_query($db, "UPDATE customer SET alternativeemails='$alternativeemails', alternativephones='$alternativephones' WHERE customerid='$mergeto'");
	header("Location: customer.php?id=$mergeto&notice=Merge Complete!");
	exit;
}

echo $header;
emerchant_sidebar();
echo "<td valign=\"top\">";
emerchant_topbar("Customers");
if ($notice) echo "<div align=\"center\" class=\"heading3\"><br><font color=\"#000099\">$notice</font></div>";
echo "<form action=\"mergeprofiles.php\" method=\"post\"><table width=\"400\" border=\"0\" cellpadding=\"5\" align=\"center\">";

// Get customer data for selected profiles...
if (is_array($profiles)) foreach ($profiles as $customernumber=>$customerid) {
	if ($customerid) $result = @mysqli_query($db, "SELECT * FROM customer WHERE customerid='$customerid'");
	if (@mysqli_num_rows($result)) {
		$row = @mysqli_fetch_array($result);
		echo "<tr> 
          <td width=\"22\"><input type=\"radio\" name=\"mergeto\" value=\"$customerid\"><input type=\"hidden\" name=\"merge$customerid\" value=\"on\"></td>
		  <td><p>{$row["firstname"]} {$row["lastname"]} ({$row["email"]})<br>{$row["address"]}<br>{$row["city"]}, {$row["state"]} {$row["zip"]}<br>{$row["country"]}<br>Phone: {$row["phone"]}</p></td></tr>";
	}
}

echo "</table><br><center><input type=\"submit\" value=\"Merge into selected profile\"></center></form>
</td>
  </tr>
  <tr> 
    <td align=\"center\" colspan=\"2\"></td>
  </tr>
</table>";
echo $footer;
?>