<?php
include "../admin/ashopconstants.inc.php";
include "../admin/checklicense.inc.php";
include "checklogin.inc.php";
include "emfunc.inc.php";
$pagetitle = "Vendors";
include "template.inc.php";
// Get context help for this page...
$contexthelppage = "vendor";
include "emhelp.inc.php";

// Open database...
$db = @mysqli_connect("$databaseserver", "$databaseuser", "$databasepasswd", "$databasename");

$vendorresult = @mysqli_query($db, "SELECT * FROM emerchant_vendor ORDER BY name");

// Save new vendor...
if ($emerchant_user == "admin" && $create == "true" && $name && $address && $zip && $state && $country && $email) {
	$result = @mysqli_query($db, "SELECT * FROM emerchant_vendor WHERE email='$email'");
	if (@mysqli_num_rows($result)) $errormessage = "A vendor with this e-mail address already exists! Please enter a new e-mail address or select the existing vendor by entering the address in the \"Search by e-mail address\" box.";
	else {
		$result = @mysqli_query($db, "INSERT INTO emerchant_vendor (name, address, city, zip, state, country, email, phone) VALUES ('$name', '$address', '$city', '$zip', '$state', '$country', '$email', '$phone')");
		$id = @mysqli_insert_id($db);
	}
	if ($createpo == "true") {
		header("Location: purchaseorder.php?id=$id");
		exit;
	}
}

// Store edited vendor info...
if ($emerchant_user == "admin" && $update == "true") {
	if ($name && $address && $zip && $state && $country && $email) {
		$result = @mysqli_query($db, "UPDATE emerchant_vendor SET name='$name', address='$address', city='$city', zip='$zip', state='$state', country='$country', email='$email', phone='$phone' WHERE vendorid='$id'");
		if ($createpo_x) {
			header("Location: purchaseorder.php?vendor=$id");
			exit;
		}
		if ($viewhistory_x) {
			header("Location: vendorhistory.php?vendor=$id");
			exit;
		}
	} else $errormessage = "<p><font color=\"#FF0000\"><b>You have forgotten to enter a value for some of the required fields!</b></font></p>";
} else if ($update == "true") {
	if ($createpo_x) {
		header("Location: purchaseorder.php?vendor=$id");
		exit;
	}
	if ($viewhistory_x) {
		header("Location: vendorhistory.php?vendor=$id");
		exit;
	}
}


// Get vendor data for selected vendor(s)...
if ($id) $result = @mysqli_query($db, "SELECT * FROM emerchant_vendor WHERE vendorid='$id'");
else if ($searchvendor == "true" && ($email || $name || $phone)) {
	$sql = "SELECT * FROM emerchant_vendor WHERE ";
	if ($email) $sql .= "email LIKE '%$email%'";
	else if ($name) $sql .= "name LIKE '%$name%'";
	else {
		if (strstr($phone,"-")) {
			$phone = str_replace("(","",$phone);
			$phone = str_replace(")","",$phone);
			$phone = str_replace(".","",$phone);
			$phoneparts = explode("-",$phone);
		} else if (strstr($phone,")")) {
			$phone = str_replace(".","",$phone);
			$phone = str_replace("-","",$phone);
			$phone = str_replace("(","",$phone);
			$phoneparts = explode(")",$phone);
		} else if (strstr($phone,".")) {
			$phone = str_replace("(","",$phone);
			$phone = str_replace(")","",$phone);
			$phone = str_replace("-","",$phone);
			$phone = str_replace(".","",$phone);
			for ($i=0; $i<=strlen($phone); $i++) $phoneparts[0] .= "%".substr($phone,$i,1);
		} else $phoneparts[0] = $phone;
		$sql .= "phone LIKE '%{$phoneparts[0]}";
		if ($phoneparts[1]) $sql .= "_{$phoneparts[1]}%' OR phone LIKE '%{$phoneparts[0]}{$phoneparts[1]}%";
		$sql .= "%'";
	}
	$sql .= " ORDER BY name";
	$result = @mysqli_query($db, $sql);
	$id = @mysqli_result($result,0,"vendorid");
	@mysqli_data_seek($result,0);
	if (@mysqli_num_rows($result) == 1 && $createpo == "true") {
		header("Location: purchaseorder.php?vendor=$id");
		exit;
	}
} else unset($result);
if ($id) $contactsresult = @mysqli_query($db, "SELECT * FROM emerchant_vendcontact WHERE vendorid='$id'");

echo $header;
emerchant_sidebar();
echo "<td valign=\"top\">";
emerchant_topbar("Vendors");
if ($notice) echo "<div align=\"center\" class=\"heading3\"><br><font color=\"#000099\">$notice</font></div>";
if (@mysqli_num_rows($result) > 1) {
	echo "<table width=\"650\" border=\"0\" cellpadding=\"5\" align=\"center\">
        <tr> 
          <td height=\"172\" align=\"center\"> 
            <table width=\"650\" border=\"0\" cellpadding=\"0\">
              <tr> 
                <td colspan=\"4\"> 
                  <p>Select from the following search results...</p>
                </td>
              </tr>
              <tr> 
                
                <td width=\"274\"> 
                  <p><b>Vendor</b></p>
                </td>
                <td width=\"172\"> 
                  <p><b>eMail</b></p>
                </td>
                <td width=\"182\">
                  <p><b>Phone</b></p>
                </td>
              </tr>";
	while ($row = @mysqli_fetch_array($result)) {
		echo "<tr> 
                <td width=\"274\">
                  <p><a href=\"";
	if ($createpo == "true") echo "purchaseorder.php?vendor=".$row["vendorid"];
	else echo "vendor.php?id=".$row["vendorid"];
	echo "\">".$row["name"]."</a></p>
                </td>
                <td width=\"172\">
                  <p>".$row["email"]."</p>
                </td>
                <td width=\"182\">
                  <p>".$row["phone"]."</p>
                </td>
              </tr>";
	}
	echo "</table>
          </td>
        </tr>
      </table>";
} else if (@mysqli_num_rows($result)) {
	$row = @mysqli_fetch_array($result);
	// Removed this for now: <input type=\"image\" name=\"viewhistory\" src=\"images/icon_history.gif\" width=\"15\" height=\"15\" alt=\"View history for ".$row["name"].".\" border=\"0\">
	echo "<center>$errormessage
        <br>
		<form action=\"vendor.php\" method=\"post\"><input type=\"hidden\" name=\"update\" value=\"true\">
        <span class=\"heading2\">Profile of ".$row["name"].", Vendor ID 
        $id</span>&nbsp;<input type=\"image\" name=\"viewhistory\" src=\"images/icon_history.gif\" width=\"15\" height=\"15\" alt=\"View history for ".$row["name"].".\" border=\"0\">&nbsp;<a href=\"javascript:newWindow('vendornote.php?vendor=$id')\"><img src=\"images/icon_vendornote.gif\" width=\"18\" height=\"18\" alt=\"Create a note regarding this vendor.\" border=\"0\"></a>&nbsp;<a href=\"javascript:newWindow('composemessage.php?vendor=$id')\"><img src=\"images/icon_mail.gif\" alt=\"Send mail.\" border=\"0\"></a><br><br>
        <input type=\"hidden\" name=\"id\" value=\"$id\">
          <table width=\"479\" border=\"0\" cellspacing=\"0\" cellpadding=\"1\">
            <tr> 
              <td align=\"right\" class=\"formlabel\" width=\"137\">Name:</td>
              <td width=\"338\"> 
                <input type=text name=\"name\" value=\"".$row["name"]."\" size=40>
              </td>
            </tr>
            <tr> 
              <td align=\"right\" class=\"formlabel\" width=\"137\">Email:</td>
              <td width=\"338\"> 
                <input type=text name=\"email\" value=\"".$row["email"]."\" size=40>
              </td>
            </tr>
            <tr> 
              <td align=\"right\" class=\"formlabel\" width=\"137\">Address:</td>
              <td width=\"338\"> 
                <input type=text name=\"address\" value=\"".$row["address"]."\" size=40>
              </td>
            </tr>
            <tr> 
              <td align=\"right\" class=\"formlabel\" width=\"137\">City:</td>
              <td width=\"338\"> 
                <input type=text name=\"city\" value=\"".$row["city"]."\" size=40>
              </td>
            </tr>
            <tr> 
              <td align=\"right\" class=\"formlabel\" width=\"137\">State/Province:</td>
              <td width=\"338\">
                <select name=\"state\">
                  <option  value=none>choose state";
 		foreach ($americanstates as $longstate => $shortstate) {
			echo "<option  value=$shortstate";
			if ($shortstate == $row["state"]) echo " selected";
			echo ">$longstate\n";
		}

		echo "</select>
              </td>
            </tr>
            <tr> 
              <td align=\"right\" class=\"formlabel\" width=\"137\">Zip:</td>
              <td width=\"338\"> 
                <input type=text name=\"zip\" value=\"".$row["zip"]."\" size=40>
              </td>
            </tr>
            <tr> 
              <td align=\"right\" class=\"formlabel\" width=\"137\">Country:</td>
              <td width=\"338\">
                <select name=\"country\">
                  <option  value=none>choose country";
 		foreach ($countries as $shortcountry => $longcountry) {
			if (strlen($longcountry) > 30) $longcountry = substr($longcountry,0,27)."...";
			echo "<option  value=$shortcountry";
			if ($shortcountry == $row["country"]) echo " selected";
			echo ">$longcountry\n";
		}

		echo "</select>
              </td>
            </tr>
            <tr> 
              <td align=\"right\" class=\"formlabel\" width=\"137\">Phone:</td>
              <td width=\"338\"> 
                <input type=text name=\"phone\" value=\"".$row["phone"]."\" size=40>
              </td>
            </tr>
          </table>
          <table width=\"400\" border=\"0\" cellspacing=\"0\" cellpadding=\"1\">
			<tr>
			  <td width=\"100%\" align=\"right\"><input type=\"image\" src=\"images/button_save.gif\" border=\"0\"></td>
		    </tr>
		  </table>
        </form>
		<b class=\"heading3\">Contacts for this vendor:</b><br><br>";
		if (@mysqli_num_rows($contactsresult)) {
			echo "<table width=\"350\" cellpadding=\"2\" cellspacing=\"0\" border=\"0\">";
			while ($contactrow = @mysqli_fetch_array($contactsresult)) echo "<tr><td class=\"regular\"><a href=\"contacthistory.php?vendorcontact={$contactrow["vendcontactid"]}\"><img src=\"images/icon_history.gif\" border=\"0\"></a> <a href=\"javascript:newWindow('composemessage.php?vendor=$id&vendorcontact={$contactrow["vendcontactid"]}')\"><img src=\"images/icon_mail.gif\" alt=\"Send mail.\" border=\"0\"></a> <b>{$contactrow["firstname"]} {$contactrow["lastname"]}</b></td><td class=\"regular\">Phone: {$contactrow["phone"]} </td><td class=\"regular\">[<a href=\"vendorcontact.php?id={$contactrow["vendcontactid"]}&vendorid=$id\">Edit</a>]</td></tr>";
			echo "</table>";
		}
		echo "<form action=\"vendorcontact.php\" method=\"post\">
		<input type=\"hidden\" name=\"vendorid\" value=\"$id\">
		<input type=\"image\" src=\"images/button_newcontact.gif\">
		</form>
      </center>";
} else if ($searchvendor == "true" && ($email || $name || $phone)) {
	echo "<table width=\"650\" border=\"0\" cellpadding=\"0\" align=\"center\">
        <tr> 
          <td>
              <table width=\"500\" border=\"0\" cellpadding=\"0\" align=\"center\">
                <tr> 
                  <td colspan=\"3\" height=\"30\" class=\"heading2\">No vendor matching the search criteria was found.</td>
                </tr>
			</table>
			</td>
			</tr>";
} else {
	echo "<table width=\"100%\" border=\"0\" cellpadding=\"0\" align=\"center\">
        <tr> 
          <td align=\"center\"><br>
			<table width=\"550\" border=\"0\" cellpadding=\"1\" cellspacing=\"2\" bgcolor=\"#d0d0d0\">
              <tr> 
                <td colspan=\"3\" bgcolor=\"#808080\"> 
                  <p class=\"heading3_wht\">Manage purchase orders. <a href=\"javascript:;\" onMouseOut=\"MM_swapImgRestore()\" onMouseOver=\"MM_swapImage('Image12','','images/contexthelpicon_over.gif',1)\"><img src=\"images/contexthelpicon.gif\" width=\"14\" height=\"15\" border=\"0\" name=\"Image12\" align=\"absmiddle\" onclick=\"return overlib('$tip1');\" onmouseout=\"return nd();\"></a></p>
                </td>
              </tr>
			  <form action=\"purchaseorder.php\" method=\"post\">
              <tr> 
                <td width=\"361\" align=\"right\"> 
                  <p class=\"formlabel\"><a href=\"javascript:;\" onMouseOut=\"MM_swapImgRestore()\" onMouseOver=\"MM_swapImage('Image1','','images/contexthelpicon_over2.gif',1)\"><img src=\"images/contexthelpicon2.gif\" width=\"14\" height=\"15\" border=\"0\" name=\"Image1\" align=\"absmiddle\" onclick=\"return overlib('$tip1a');\" onmouseout=\"return nd();\"></a> Edit purchase order, number:</p>
                </td>
                <td width=\"131\">
                  <input type=\"text\" name=\"edit\">
                </td>
                <td width=\"44\" align=\"center\"><input type=\"image\" src=\"images/button_go.gif\" width=\"27\" height=\"24\" border=\"0\"></td>
              </tr>
			  </form>
		      <form action=\"purchaseorder.php\" method=\"post\">
              <tr> 
                <td align=\"right\" colspan=\"2\"> 
                  <p><span class=\"formlabel\"><a href=\"javascript:;\" onMouseOut=\"MM_swapImgRestore()\" onMouseOver=\"MM_swapImage('Image2','','images/contexthelpicon_over2.gif',1)\"><img src=\"images/contexthelpicon2.gif\" width=\"14\" height=\"15\" border=\"0\" name=\"Image2\" align=\"absmiddle\" onclick=\"return overlib('$tip1b');\" onmouseout=\"return nd();\"></a> Vendor:</font></span> 
                    <b>
                    <select name=\"vendor\">
                      <option  value=all>All";
 		while ($row = @mysqli_fetch_array($vendorresult)) {
			echo "<option  value={$row["vendorid"]}>{$row["name"]}\n";
		}
        echo "</select>
                    </b></p>
                  </td>
                <td width=\"44\" align=\"center\"><img src=\"images/arrow.gif\"></td>
              </tr>
              <tr> 
                <td align=\"right\" colspan=\"2\"> 
                  <p class=\"formlabel\"><a href=\"javascript:;\" onMouseOut=\"MM_swapImgRestore()\" onMouseOver=\"MM_swapImage('Image12','','images/contexthelpicon_over2.gif',1)\"><img src=\"images/contexthelpicon2.gif\" width=\"14\" height=\"15\" border=\"0\" name=\"Image12\" align=\"absmiddle\" onclick=\"return overlib('$tip1g');\" onmouseout=\"return nd();\"></a> Create purchase order:</p>
                </td>
                <td width=\"44\" align=\"center\"><input type=\"image\" src=\"images/button_go.gif\" width=\"27\" height=\"24\" border=\"0\" name=\"newpurchaseorder\"></td>
              </tr>
              <tr> 
                <td width=\"361\" align=\"right\"> 
                  <p class=\"formlabel\"><a href=\"javascript:;\" onMouseOut=\"MM_swapImgRestore()\" onMouseOver=\"MM_swapImage('Image3','','images/contexthelpicon_over2.gif',1)\"><img src=\"images/contexthelpicon2.gif\" width=\"14\" height=\"15\" border=\"0\" name=\"Image3\" align=\"absmiddle\" onclick=\"return overlib('$tip1c');\" onmouseout=\"return nd();\"></a> Status of Order ID:</p>
                </td>
                <td width=\"131\">
                  <input type=\"text\" name=\"order\">
                </td>
                <td width=\"44\" align=\"center\"><input type=\"image\" src=\"images/button_go.gif\" width=\"27\" height=\"24\" border=\"0\" name=\"purchaseorder\"></td>
              </tr>
              <tr> 
                <td align=\"right\" colspan=\"2\"> 
                  <p class=\"formlabel\"><a href=\"javascript:;\" onMouseOut=\"MM_swapImgRestore()\" onMouseOver=\"MM_swapImage('Image4','','images/contexthelpicon_over2.gif',1)\"><img src=\"images/contexthelpicon2.gif\" width=\"14\" height=\"15\" border=\"0\" name=\"Image4\" align=\"absmiddle\" onclick=\"return overlib('$tip1d');\" onmouseout=\"return nd();\"></a> Open orders:</p>
                </td>
                <td width=\"44\" align=\"center\"><input type=\"image\" src=\"images/button_go.gif\" width=\"27\" height=\"24\" border=\"0\" name=\"openorders\"></td>
              </tr>
              <tr> 
                <td align=\"right\" colspan=\"2\"> 
                  <p class=\"formlabel\"><a href=\"javascript:;\" onMouseOut=\"MM_swapImgRestore()\" onMouseOver=\"MM_swapImage('Image5','','images/contexthelpicon_over2.gif',1)\"><img src=\"images/contexthelpicon2.gif\" width=\"14\" height=\"15\" border=\"0\" name=\"Image5\" align=\"absmiddle\" onclick=\"return overlib('$tip1e');\" onmouseout=\"return nd();\"></a> Unshipped purchase orders:</p>
                </td>
                <td width=\"44\" align=\"center\"><input type=\"image\" src=\"images/button_go.gif\" width=\"27\" height=\"24\" border=\"0\" name=\"unshipped\"></td>
              </tr>
              <tr> 
                <td align=\"right\" colspan=\"2\"> 
                  <p class=\"formlabel\"><a href=\"javascript:;\" onMouseOut=\"MM_swapImgRestore()\" onMouseOver=\"MM_swapImage('Image6','','images/contexthelpicon_over2.gif',1)\"><img src=\"images/contexthelpicon2.gif\" width=\"14\" height=\"15\" border=\"0\" name=\"Image6\" align=\"absmiddle\" onclick=\"return overlib('$tip1f');\" onmouseout=\"return nd();\"></a> Shipped purchase orders:</p>
                </td>
                <td width=\"44\" align=\"center\"><input type=\"image\" src=\"images/button_go.gif\" width=\"27\" height=\"24\" border=\"0\" name=\"shipped\"></td>
              </tr>
			  </form>
			</table><br>";
		
	if ($emerchant_user == "admin") {

		// Get the oldest and newest bill...
		$sql = "SELECT MAX(billdate) FROM emerchant_purchaseorder";
		$result = @mysqli_query($db, "$sql");
		$maxdate = @mysqli_result($result, 0, "MAX(billdate)");
		$sql = "SELECT billdate FROM emerchant_purchaseorder WHERE billdate != '' ORDER BY billdate LIMIT 1";
		$result = @mysqli_query($db, "$sql");
		$mindate = @mysqli_result($result, 0, "billdate");
		$oldestarray = explode("-", $mindate);
		$oldest = $oldestarray[0];
		if (!$oldest) $oldest = date("Y", time()+$timezoneoffset);
		$newest = date("Y", time()+$timezoneoffset);
		$fromyears = "";
		$toyears = "";
		for ($thisyear = $oldest; $thisyear<=$newest; $thisyear++) {
			$toyears .= "<option value=\"$thisyear\"";
			if ($thisyear==$newest) $toyears.= " selected";
			$toyears .= ">$thisyear</option>";
			$fromyears .= "<option value=\"$thisyear\">$thisyear</option>";
		}
		
		// Get the current month and day...
		$currentmonth = date("m", time()+$timezoneoffset);
		$currentday = date("d", time()+$timezoneoffset);

		echo "<table width=\"550\" border=\"0\" cellpadding=\"1\" cellspacing=\"2\" bgcolor=\"#d0d0d0\">
              <tr> 
                <td colspan=\"2\" bgcolor=\"#808080\"> 
                  <p class=\"heading3_wht\">Manage vendor bills. <a href=\"javascript:;\" onMouseOut=\"MM_swapImgRestore()\" onMouseOver=\"MM_swapImage('Image7','','images/contexthelpicon_over.gif',1)\"><img src=\"images/contexthelpicon.gif\" width=\"14\" height=\"15\" border=\"0\" name=\"Image7\" align=\"absmiddle\" onclick=\"return overlib('$tip2');\" onmouseout=\"return nd();\"></a></p>
                </td>
              </tr>
		      <form action=\"vendorbills.php\" method=\"post\">
              <tr> 
                <td align=\"right\"> 
                  <p><span class=\"formlabel\">Vendor:</font></span> 
                    <b>
                    <select name=\"vendor\">
                      <option  value=all>All";
		@mysqli_data_seek ($vendorresult, 0);
 		while ($row = @mysqli_fetch_array($vendorresult)) {
			echo "<option  value={$row["vendorid"]}>{$row["name"]}\n";
		}
        echo "</select>
                    </b></p>
                  </td>
                <td width=\"44\" align=\"center\"><img src=\"images/arrow.gif\"></td>
              </tr>
              <tr> 
                <td width=\"506\" align=\"right\" class=\"formlabel\"><select name=\"datetype\"><option value=\"billdate\">Bill date</option><option value=\"paiddate\">Paid date</option></select> From: <select name=\"startyear\">$fromyears</select>
				<select name=\"startmonth\"><option value=\"01\" selected>Jan</option><option value=\"02\">Feb</option><option value=\"03\">Mar</option><option value=\"04\">Apr</option><option value=\"05\">May</option><option value=\"06\">Jun</option><option value=\"07\">Jul</option><option value=\"08\">Aug</option><option value=\"09\">Sep</option><option value=\"10\">Oct</option><option value=\"11\">Nov</option><option value=\"12\">Dec</option></select>
				<select name=\"startday\"><option value=\"01\" selected>1</option>";

				for ($i = 2; $i < 32; $i++) {
					echo "<option value=\"";
					if ($i < 10) echo "0";
					echo "$i\">$i</option>";
				}
				echo "</select>
				&nbsp;To:   
				<select name=\"toyear\">$toyears</select>
				<select name=\"tomonth\">";
				echo "<option value=\"01\""; if ($currentmonth == 1) echo "selected"; echo">Jan</option>";
				echo "<option value=\"02\""; if ($currentmonth == 2) echo "selected"; echo">Feb</option>";
				echo "<option value=\"03\""; if ($currentmonth == 3) echo "selected"; echo">Mar</option>";
				echo "<option value=\"04\""; if ($currentmonth == 4) echo "selected"; echo">Apr</option>";
				echo "<option value=\"05\""; if ($currentmonth == 5) echo "selected"; echo">May</option>";
				echo "<option value=\"06\""; if ($currentmonth == 6) echo "selected"; echo">Jun</option>";
				echo "<option value=\"07\""; if ($currentmonth == 7) echo "selected"; echo">Jul</option>";
				echo "<option value=\"08\""; if ($currentmonth == 8) echo "selected"; echo">Aug</option>";
				echo "<option value=\"09\""; if ($currentmonth == 9) echo "selected"; echo">Sep</option>";
				echo "<option value=\"10\""; if ($currentmonth == 10) echo "selected"; echo">Oct</option>";
				echo "<option value=\"11\""; if ($currentmonth == 11) echo "selected"; echo">Nov</option>";
				echo "<option value=\"12\""; if ($currentmonth == 12) echo "selected"; echo">Dec</option>";
				echo "</select><select name=\"today\">";

				for ($i = 1; $i < 32; $i++) {
					echo "<option value=\"";
					if ($i < 10) echo "0";
					echo "$i\"";
					if ($i == $currentday) echo " selected";
					echo ">$i</option>";
				}
				echo "</select>
                </td>
                <td width=\"44\" align=\"center\">&nbsp;</td>
              </tr>
              <tr> 
                <td align=\"right\" class=\"formlabel\">
                  Status: <select name=\"paidstatus\">
                      <option value=\"all\">All
					  <option value=\"paid\">Paid
					  <option value=\"unpaid\">Unpaid
					</select>
                  &nbsp;Order by: <select name=\"orderby\">
                      <option value=\"billdate\">Date
					  <option value=\"vendorid\">Vendor
					</select>
                  &nbsp;Action: <select name=\"action\">
                      <option value=\"display\">Display
					  <option value=\"download\">Download
					</select>
                  </td>
                <td width=\"44\" align=\"center\"><input type=\"image\" src=\"images/button_go.gif\" width=\"27\" height=\"24\" border=\"0\" name=\"purchaseorder\"></td>
              </tr>
			  </form>
			</table><br>";
	}

	if (!$createpo) {
		// Generate vendor list...
		$vendorlistresult = @mysqli_query($db, "SELECT * FROM emerchant_vendor ORDER BY name");
		while ($vendorlistrow = @mysqli_fetch_array($vendorlistresult)) $vendorlist .= "<option value=\"{$vendorlistrow["vendorid"]}\">{$vendorlistrow["name"]}</option>";
		if ($vendorlist) $vendorlist = "<select name=\"id\">\n<option value=\"0\">select...</option>\n$vendorlist\n</select>";
		echo "<table width=\"550\" border=\"0\" cellpadding=\"1\" cellspacing=\"2\" bgcolor=\"#d0d0d0\">
                <tr>
				  <td colspan=\"3\" bgcolor=\"#808080\"><p class=\"heading3_wht\">Manage
                    vendors.</p></td>
                </tr>";
		if ($vendorlist) echo "
				<form action=\"vendor.php\" method=\"post\">
                <tr>
                  <td width=\"361\" align=\"right\" class=\"formlabel\"><a href=\"javascript:;\" onMouseOut=\"MM_swapImgRestore()\" onMouseOver=\"MM_swapImage('Image8','','images/contexthelpicon_over2.gif',1)\"><img src=\"images/contexthelpicon2.gif\" width=\"14\" height=\"15\" border=\"0\" name=\"Image8\" align=\"absmiddle\" onclick=\"return overlib('$tip3a');\" onmouseout=\"return nd();\"></a> Edit vendor: </td>
                  <td width=\"131\">$vendorlist</td>
                  <td width=\"44\" align=\"center\"><input type=\"image\" src=\"images/button_go.gif\" width=\"27\" height=\"24\" border=\"0\"></td>
                </tr></form>";
		echo "<form action=\"vendor.php\" method=\"post\">
                <tr>
                  <td width=\"361\" align=\"right\" class=\"formlabel\"><a href=\"javascript:;\" onMouseOut=\"MM_swapImgRestore()\" onMouseOver=\"MM_swapImage('Image9','','images/contexthelpicon_over2.gif',1)\"><img src=\"images/contexthelpicon2.gif\" width=\"14\" height=\"15\" border=\"0\" name=\"Image9\" align=\"absmiddle\" onclick=\"return overlib('$tip3b');\" onmouseout=\"return nd();\"></a> Search by e-mail 
                    address: </td>
                  <td width=\"131\"><input type=\"text\" name=\"email\" value=\"$email\"><input type=\"hidden\" name=\"searchvendor\" value=\"true\"></td>
                  <td width=\"44\" align=\"center\"><input type=\"image\" src=\"images/button_go.gif\" width=\"27\" height=\"24\" border=\"0\"></td>
                </tr></form>
				<form action=\"vendor.php\" method=\"post\">
                <tr> 
                  <td width=\"361\" align=\"right\" class=\"formlabel\"><a href=\"javascript:;\" onMouseOut=\"MM_swapImgRestore()\" onMouseOver=\"MM_swapImage('Image10','','images/contexthelpicon_over2.gif',1)\"><img src=\"images/contexthelpicon2.gif\" width=\"14\" height=\"15\" border=\"0\" name=\"Image10\" align=\"absmiddle\" onclick=\"return overlib('$tip3c');\" onmouseout=\"return nd();\"></a> Search by name: </td>
                  <td width=\"131\"><input type=\"text\" name=\"name\"><input type=\"hidden\" name=\"searchvendor\" value=\"true\"></td>
                  <td width=\"44\" align=\"center\"><input type=\"image\" src=\"images/button_go.gif\" width=\"27\" height=\"24\" border=\"0\"></td>
                </tr></form>
				<form action=\"vendor.php\" method=\"post\">
                <tr> 
                  <td width=\"361\" align=\"right\" class=\"formlabel\"><a href=\"javascript:;\" onMouseOut=\"MM_swapImgRestore()\" onMouseOver=\"MM_swapImage('Image11','','images/contexthelpicon_over2.gif',1)\"><img src=\"images/contexthelpicon2.gif\" width=\"14\" height=\"15\" border=\"0\" name=\"Image11\" align=\"absmiddle\" onclick=\"return overlib('$tip3d');\" onmouseout=\"return nd();\"></a> Search by phone number: </td>
                  <td width=\"131\"><input type=\"text\" name=\"phone\"><input type=\"hidden\" name=\"searchvendor\" value=\"true\"></td>
                  <td width=\"44\" align=\"center\"><input type=\"image\" src=\"images/button_go.gif\" width=\"27\" height=\"24\" border=\"0\"></td>
                </tr></form>
              </table>
              <br>";
	}
   if ($emerchant_user == "admin") echo "<div align=\"center\"><a href=\"addvendor.php\"><img src=\"images/button_newvendor.gif\" alt=\"Add New Vendor\" border=\"0\"></a></div>";
   echo "</td>
        </tr>
      </table>";
}

echo "</td>
  </tr>
  <tr> 
    <td align=\"center\" colspan=\"2\"></td>
  </tr>
</table>";
echo $footer;
?>