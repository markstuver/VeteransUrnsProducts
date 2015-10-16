<?php
include "../admin/ashopconstants.inc.php";
include "../admin/checklicense.inc.php";
include "../admin/customers.inc.php";
include "checklogin.inc.php";
include "emfunc.inc.php";
$pagetitle = "Find or Create Customer";
include "template.inc.php";
// Get context help for this page...
$contexthelppage = "customer";
include "emhelp.inc.php";

// Open database...
$db = @mysqli_connect("$databaseserver", "$databaseuser", "$databasepasswd", "$databasename");

// Get quick quote info...
if ($quickquote) {
	$result = @mysqli_query($db, "SELECT * FROM emerchant_quotes WHERE id='$quickquote'");
	$qqrow = @mysqli_fetch_array($result);
	if (!$zip) $zip = $qqrow["qqzip"];
	if (!$state) $state = $qqrow["qqstate"];
}

// Save new customer...
if ($create == "true" && $firstname && $lastname && $address && $zip && $state && $country && $email) {
	if ($allowemail == "on") $allowemail = 1;
	else $allowemail = 0;
	$result = @mysqli_query($db, "SELECT * FROM customer WHERE email='$email'");
	if (@mysqli_num_rows($result)) $errormessage = "A customer with this e-mail address already exists! Please enter a new e-mail address or select the existing customer by entering the address in the \"Search by e-mail address\" box.";
	else {
		function makePassword() {
			$alphaNum = array(2, 3, 4, 5, 6, 7, 8, 9, a, b, c, d, e, f, g, h, i, j, k, m, n, p, q, r, s, t, u, v, w, x, y, z);
			srand ((double) microtime() * 1000000);
			$pwLength = "7"; // this sets the limit on how long the password is.
			for($i = 1; $i <=$pwLength; $i++) {
				$newPass .= $alphaNum[(rand(0,31))];
			}
			return ($newPass);
		}
		$password = makePassword();
		// Encrypt password if encryption key is available...
		if (!empty($customerencryptionkey) && !empty($password)) $customerpassword = ashop_encrypt($password, $customerencryptionkey);
		else $customerpassword = $password;
		$result = @mysqli_query($db, "INSERT INTO customer (businessname, firstname, lastname, address, city, zip, state, country, email, username, password, phone, allowemail, affiliateid) VALUES ('$businessname', '$firstname', '$lastname', '$address', '$city', '$zip', '$state', '$country', '$email', '$nusername', '$customerpassword', '$phone', '$allowemail', '$affiliateid')");
		$id = @mysqli_insert_id($db);
		@mysqli_query($db, "INSERT INTO shipping (customerid, shippingbusiness, shippingfirstname, shippinglastname, shippingaddress, shippingaddress2, shippingcity, shippingzip, shippingstate, shippingcountry, vat) values ('$id', '$business', '$firstname', '$lastname', '$address', '', '$city', '$zip', '$state', '$country', '')");		
	}
	if ($createquote == "true") {
		header("Location: customershipping.php?id=$id");
		exit;
	}
}

// Get shipping info, if available...
$shippingresult = @mysqli_query($db, "SELECT * FROM shipping WHERE customerid='$id'");

// Store edited customer info...
if ($update == "true") {
	if ($firstname && $lastname && $address && $zip && $state && $country && $email) {
		if ($allowemail == "on") $allowemail = 1;
		else $allowemail = 0;
		if (!empty($salesrep)) $soaffiliate = $salesrep;
		else if (!empty($affiliateid) && empty($soaffiliate)) $soaffiliate = $affiliateid;
		// Encrypt password if encryption key is available...
		if (!empty($customerencryptionkey) && !empty($password)) $customerpassword = ashop_encrypt($password, $customerencryptionkey);
		else $customerpassword = $password;
		if (!empty($customerpassword)) $result = @mysqli_query($db, "UPDATE customer SET businessname='$businessname', username='$nusername', password='$customerpassword', firstname='$firstname', lastname='$lastname', address='$address', city='$city', zip='$zip', state='$state', country='$country', email='$email', phone='$phone', allowemail='$allowemail', alternativeemails='$alternativeemails', alternativephones='$alternativephones', affiliateid='$soaffiliate' WHERE customerid='$id'");
		else $result = @mysqli_query($db, "UPDATE customer SET businessname='$businessname', username='$nusername', firstname='$firstname', lastname='$lastname', address='$address', city='$city', zip='$zip', state='$state', country='$country', email='$email', phone='$phone', allowemail='$allowemail', alternativeemails='$alternativeemails', alternativephones='$alternativephones', affiliateid='$soaffiliate' WHERE customerid='$id'");
		if ($shippingfirstname && $shippinglastname && $shippingaddress && $shippingzip && $shippingstate && $shippingcountry) {
			if (@mysqli_num_rows($shippingresult)) @mysqli_query($db, "UPDATE shipping SET shippingbusiness='$shippingbusiness', shippingfirstname='$shippingfirstname', shippinglastname='$shippinglastname', shippingaddress='$shippingaddress', shippingaddress2='$shippingaddress2', shippingcity='$shippingcity', shippingzip='$shippingzip', shippingstate='$shippingstate', shippingcountry='$shippingcountry', vat='$vat' WHERE customerid='$id'");
			else @mysqli_query($db, "INSERT INTO shipping (customerid, shippingbusiness, shippingfirstname, shippinglastname, shippingaddress, shippingaddress2, shippingcity, shippingzip, shippingstate, shippingcountry, vat) values ('$id', '$shippingbusiness', '$shippingfirstname', '$shippinglastname', '$shippingaddress', '$shippingaddress2', '$shippingcity', '$shippingzip', '$shippingstate', '$shippingcountry', '$vat')");
		} else {
			if (@mysqli_num_rows($shippingresult)) @mysqli_query($db, "UPDATE shipping SET shippingfirstname='$firstname', shippinglastname='$lastname', shippingaddress='$address', shippingcity='$city', shippingzip='$zip', shippingstate='$state', shippingcountry='$country' WHERE customerid='$id'");
			else @mysqli_query($db, "INSERT INTO shipping (customerid, shippingfirstname, shippinglastname, shippingaddress, shippingcity, shippingzip, shippingstate, shippingcountry) values ('$id', '$firstname', '$lastname', '$address', '$city', '$zip', '$state', '$country')");
		}
		if ($createquote_x) {
			header("Location: quote.php?customer=$id");
			exit;
		}
		if ($viewhistory_x) {
			header("Location: history.php?customer=$id");
			exit;
		}
	} else {
		if ($createquote_x) {
			header("Location: quote.php?customer=$id");
			exit;
		} else if ($viewhistory_x) {
			header("Location: history.php?customer=$id");
			exit;
		} else $errormessage = "<p><font color=\"#FF0000\"><b>You have forgotten to enter a value for some of the required fields!</b></font></p>";
	}
}

// Get customer data for selected customer(s)...
if ($id) $result = @mysqli_query($db, "SELECT * FROM customer WHERE customerid='$id'");
else if ($searchcustomer == "true" && ($email || $name || $phone || $letter || $business)) {
	$phone = str_replace(" ","",$phone);
	$sql = "SELECT * FROM customer";
	if ($letter != "all") $sql .= " WHERE ";
	if ($email) $sql .= "(email LIKE '%$email%' OR alternativeemails LIKE '$email' OR alternativeemails LIKE '%, $email' OR alternativeemails LIKE '$email,%')";
	else if ($name) {
		$names = explode(" ",$name);
		$sql .= "(firstname LIKE '%{$names[0]}%' OR lastname LIKE '%{$names[0]}%'";
		if ($names[1]) $sql .= " OR lastname LIKE '%{$names[0]}%'";
		$sql .= ")";
	} else if ($phone) {
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
		$sql .= "(REPLACE(phone,' ','') LIKE '%{$phoneparts[0]}";
		if ($phoneparts[1]) $sql .= "_{$phoneparts[1]}%' OR REPLACE(phone,' ','') LIKE '%{$phoneparts[0]}{$phoneparts[1]}%";
		$sql .= "%' OR REPLACE(alternativephones,' ','') LIKE '%{$phoneparts[0]}";
		if ($phoneparts[1]) $sql .= "_{$phoneparts[1]}%' OR REPLACE(alternativephones,' ','') LIKE '%{$phoneparts[0]}{$phoneparts[1]}%";
		$sql .= "%')";
	} else if ($letter != "all") $sql .= "lastname LIKE '$letter%'";
	if ($letter != "all" && !empty($affiliateid)) $sql .= " AND affiliateid='$affiliateid'";
	else if (!empty($affiliateid)) $sql .= " WHERE affiliateid='$affiliateid'";
	$sql .= " ORDER BY lastname, firstname";
	if ($business) {
		$sql = "SELECT customer.*, shipping.shippingbusiness FROM customer LEFT JOIN shipping ON (customer.customerid=shipping.customerid) WHERE (customer.businessname LIKE '%$business%' OR shipping.shippingbusiness LIKE '%$business%')";
		if (!empty($affiliateid)) $sql .= " AND customer.affiliateid='$affiliateid'";
		$sql .= " ORDER BY customer.lastname, customer.firstname";
	}
	$result = @mysqli_query($db, $sql);
	unset($email);
	unset($phone);
	$id = @mysqli_result($result,0,"customerid");
	@mysqli_data_seek($result,0);
	if (@mysqli_num_rows($result) == 1 && $createquote == "true") {
		header("Location: quote.php?customer=$id");
		exit;
	}
	if (!@mysqli_num_rows($result)) $notice = "No customers found!";
} else unset($result);

// Update displayed shipping info...
$shippingresult = @mysqli_query($db, "SELECT * FROM shipping WHERE customerid='$id'");

echo $header;
emerchant_sidebar();
echo "<td valign=\"top\">";
emerchant_topbar("Customers");
if ($notice) echo "<div align=\"center\" class=\"heading3\"><br><font color=\"#000099\">$notice</font></div>";
if (@mysqli_num_rows($result) > 1) {
	echo "<form action=\"mergeprofiles.php\" method=\"post\"><table width=\"650\" border=\"0\" cellpadding=\"5\" align=\"center\">
        <tr> 
          <td height=\"172\" align=\"center\"> 
            <table width=\""; if ($business) echo "750"; else echo "650"; echo "\" border=\"0\" cellpadding=\"0\">
              <tr> 
                <td colspan=\"4\"> 
                  <p>Select from the following search results...</p>
                </td>
              </tr>
              <tr>
			    <td width=\"22\">&nbsp;</td>                
                <td width=\""; if ($business) echo "204"; else echo "274"; echo "\"> 
                  <p><b>Customer</b></p>
                </td>";
				if ($business) echo "<td width=\"170\"><p><b>Business</b></p>";
				echo "
                <td width=\"172\"> 
                  <p><b>Email</b></p>
                </td>
                <td width=\"182\">
                  <p><b>Phone</b></p>
                </td>
              </tr>";
	while ($row = @mysqli_fetch_array($result)) {
		echo "<tr>
		        <td width=\"22\"><input type=\"checkbox\" name=\"merge{$row["customerid"]}\"></td>
                <td width=\""; if ($business) echo "204"; else echo "274"; echo "\">
                  <p><a href=\"";
	if ($createquote == "true") echo "quote.php?customer=".$row["customerid"];
	else {
		echo "customer.php?id=".$row["customerid"];
		if ($quickquote) echo "&quickquote=$quickquote";
	}
	echo "\">".$row["firstname"]." ".$row["lastname"]."</a></p>
                </td>";
				if ($business) {
					echo "<td width=\"170\"><p><a href=\"";
	if ($createquote == "true") echo "quote.php?customer=".$row["customerid"];
	else {
		echo "customer.php?id=".$row["customerid"];
		if ($quickquote) echo "&quickquote=$quickquote";
	}
	if (!empty($row["businessname"])) $displaybusiness = $row["businessname"];
	else $displaybusiness = $row["shippingbusiness"];
	echo "\">".$displaybusiness."</a></p></td>";
				}
	echo "
                <td width=\"172\">
                  <p>".$row["email"]."</p>
                </td>
                <td width=\"182\">
                  <p>".$row["phone"]."</p>
                </td>
              </tr>";
	}
	echo "</table><br><input type=\"submit\" value=\"Merge selected profiles\">
          </td>
        </tr>
      </table></form>";
} else if (@mysqli_num_rows($result)) {
	$row = @mysqli_fetch_array($result);
	if (!isset($allowemail)) $allowemail = $row["allowemail"];
	if (!$businessname) $businessname = $row["businessname"];
	if (!$firstname) $firstname = $row["firstname"];
	if (!$lastname) $lastname = $row["lastname"];
	if (!$nusername) $nusername = $row["username"];
	if (!$password) $password = $row["password"];
	if (!$email) $email = $row["email"];
	if (!$alternativeemails) $alternativeemails = $row["alternativeemails"];
	if (!$address) $address = $row["address"];
	if (!$city) $city = $row["city"];
	if (!$state) $state = $row["state"];
	if (!$country) $country = $row["country"];
	if (!$zip) $zip = $row["zip"];
	if (!$phone) $phone = $row["phone"];
	if (!$alternativephones) $alternativephones = $row["alternativephones"];
	if (!$soaffiliate) $soaffiliate = $row["affiliateid"];
	$srow = @mysqli_fetch_array($shippingresult);
	if (!$vat) $vat = $srow["vat"];
	if (!$shippingbusiness) $shippingbusiness = $srow["shippingbusiness"];
	if (!$shippingfirstname) $shippingfirstname = $srow["shippingfirstname"];
	if (!$shippinglastname) $shippinglastname = $srow["shippinglastname"];
	if (!$shippingaddress) $shippingaddress = $srow["shippingaddress"];
	if (!$shippingaddress2) $shippingaddress2 = $srow["shippingaddress2"];
	if (!$shippingcity) $shippingcity = $srow["shippingcity"];
	if (!$shippingzip) $shippingzip = $srow["shippingzip"];
	if (!$shippingstate) $shippingstate = $srow["shippingstate"];
	if (!$shippingcountry) $shippingcountry = $srow["shippingcountry"];
	if (!$shippingphone) $shippingphone = $srow["shippingphone"];
	if (!$vat) $vat = $srow["vat"];
	if (!empty($soaffiliate) && is_numeric($soaffiliate)) {
		$affiliateresult = @mysqli_query($db, "SELECT * FROM affiliate WHERE affiliateid='$soaffiliate'");
		$affiliatefirstname = @mysqli_result($affiliateresult,0,"firstname");
		$affiliatelastname = @mysqli_result($affiliateresult,0,"lastname");
		$affiliateemail = @mysqli_result($affiliateresult,0,"email");
		$affiliatedescription = "$affiliatefirstname $affiliatelastname ($affiliateemail)";
	} else $affiliatedescription = "";

	// Generate list of sales reps...
	$salesrepslist = "";
	$salesrep = "";
	$salesrepsresult = @mysqli_query($db, "SELECT affiliate.affiliateid, affiliate.user FROM affiliate, emerchant_user WHERE affiliate.user=emerchant_user.username");
	if (@mysqli_num_rows($salesrepsresult)) {
		$salesrepslist = "<select name=\"salesrep\">\n<option value=\"\"></option>";
		while ($salesreprow = @mysqli_fetch_array($salesrepsresult)) {
			$salesrepslist .= "<option value=\"{$salesreprow["affiliateid"]}\"";
			if ($salesreprow["affiliateid"] == $soaffiliate) {
				$salesrepslist .= " selected";
				$salesrep = $soaffiliate;
			}
			$salesrepslist .= ">{$salesreprow["user"]}</option>\n";
		}
		$salesrepslist .= "</select>";
	}

	echo "<center>$errormessage
        <br>
		<form action=\"customer.php\" method=\"post\"><input type=\"hidden\" name=\"quickquote\" value=\"$quickquote\"><input type=\"hidden\" name=\"update\" value=\"true\">
        <span class=\"heading2\">Profile of $firstname $lastname, Customer ID 
        $id</span> <input type=\"image\" name=\"createquote\" src=\"images/icon_quote-order.gif\" width=\"18\" height=\"18\" alt=\"Create a new quote or order for this customer.\" border=\"0\">&nbsp;<input type=\"image\" name=\"viewhistory\" src=\"images/icon_history.gif\" width=\"15\" height=\"15\" alt=\"View history for $firstname $lastname\" border=\"0\">&nbsp;<a href=\"javascript:newWindow('customernote.php?customer=$id')\"><img src=\"images/icon_customernote.gif\" width=\"15\" height=\"15\" alt=\"Create a note regarding this customer.\" border=\"0\"></a>&nbsp;<a href=\"javascript:newWindow('composemessage.php?customer=$id')\"><img src=\"images/icon_mail.gif\" alt=\"Send mail.\" border=\"0\"></a><br><br>
        <input type=\"hidden\" name=\"id\" value=\"$id\">
          <b class=\"heading3\">Billing Information</b> 
          <table width=\"479\" border=\"0\" cellspacing=\"0\" cellpadding=\"1\">
            <tr> 
              <td align=\"right\" class=\"formlabel\" width=\"137\">Username:</td>
              <td width=\"338\"> 
                <input type=text name=\"nusername\" value=\"$nusername\" size=40>
              </td>
            </tr>
            <tr> 
              <td align=\"right\" class=\"formlabel\" width=\"137\">Password:</td>
              <td width=\"338\"> 
                <input type=text name=\"password\" value=\"\" size=40>
              </td>
            </tr>";
			if (empty($affiliateid)) {
				if (empty($salesrep)) {
					echo "
					<tr> 
						<td align=\"right\" class=\"formlabel\" width=\"137\">Referred by affiliate:</td>
						<td width=\"338\"> 
							<input type=text name=\"soaffiliate\" value=\"$soaffiliate\" size=4><span class=\"sm\"> $affiliatedescription</span>
						</td>
					</tr>";
					if (!empty($salesrepslist)) echo "
					<tr> 
						<td align=\"right\" class=\"formlabel\" width=\"137\">Assigned to:</td>
						<td width=\"338\"> 
							$salesrepslist
						</td>
					</tr>";
				} else echo "
					<tr> 
						<td align=\"right\" class=\"formlabel\" width=\"137\">Assigned to:</td>
						<td width=\"338\"> 
							$salesrepslist
						</td>
					</tr>";
			}
			if (!empty($businessname)) echo "
            <tr> 
              <td align=\"right\" class=\"formlabel\" width=\"137\">Business name:</td>
              <td width=\"338\"> 
                <input type=text name=\"businessname\" value=\"$businessname\" size=40>
              </td>
            </tr>";
			else echo "
            <tr> 
              <td align=\"right\" class=\"formlabel\" width=\"137\">Business name:</td>
              <td width=\"338\"> 
                <input type=text name=\"shippingbusiness\" value=\"$shippingbusiness\" size=40>
              </td>
            </tr>";
			echo "
            <tr> 
              <td align=\"right\" class=\"formlabel\" width=\"137\">First name:</td>
              <td width=\"338\"> 
                <input type=text name=\"firstname\" value=\"$firstname\" size=40>
              </td>
            </tr>
            <tr> 
              <td align=\"right\" class=\"formlabel\" width=\"137\">Last name:</td>
              <td width=\"338\"> 
                <input type=text name=\"lastname\" value=\"$lastname\" size=40>
              </td>
            </tr>
            <tr> 
              <td align=\"right\" class=\"formlabel\" width=\"137\">Main email:</td>
              <td width=\"338\"> 
                <input type=text name=\"email\" value=\"$email\" size=40>
              </td>
            </tr>
            <tr> 
              <td align=\"right\" class=\"formlabel\" width=\"137\">Alternative email:</td>
              <td width=\"338\"> 
                <textarea name=\"alternativeemails\" cols=\"30\" rows=\"3\">$alternativeemails</textarea>
              </td>
            </tr>
            <tr> 
              <td align=\"right\" class=\"formlabel\" width=\"137\">Address:</td>
              <td width=\"338\"> 
                <input type=text name=\"address\" value=\"$address\" size=40>
              </td>
            </tr>
            <tr> 
              <td align=\"right\" class=\"formlabel\" width=\"137\">City:</td>
              <td width=\"338\"> 
                <input type=text name=\"city\" value=\"$city\" size=40>
              </td>
            </tr>
            <tr> 
              <td align=\"right\" class=\"formlabel\" width=\"137\">State/Province:</td>
              <td width=\"338\">
			    <input type=text name=\"state\" value=\"$state\" size=40>
              </td>
            </tr>
            <tr> 
              <td align=\"right\" class=\"formlabel\" width=\"137\">Zip:</td>
              <td width=\"338\"> 
                <input type=text name=\"zip\" value=\"$zip\" size=40>
              </td>
            </tr>
            <tr> 
              <td align=\"right\" class=\"formlabel\" width=\"137\">Country:</td>
              <td width=\"338\">
			    <input type=text name=\"country\" value=\"$country\" size=40>
              </td>
            </tr>
            <tr> 
              <td align=\"right\" class=\"formlabel\" width=\"137\">Phone:</td>
              <td width=\"338\"> 
                <input type=text name=\"phone\" value=\"$phone\" size=40>
              </td>
            </tr>
            <tr> 
              <td align=\"right\" class=\"formlabel\" width=\"137\">Alternative phone:</td>
              <td width=\"338\"> 
                <textarea name=\"alternativephones\" cols=\"30\" rows=\"3\">$alternativephones</textarea>
              </td>
            </tr>
            <tr> 
              <td align=\"right\" class=\"formlabel\" width=\"137\">VAT number:</td>
              <td width=\"338\"> 
                <input type=text name=\"vat\" value=\"$vat\" size=40>
              </td>
            </tr>
          </table>
          <b><br>
          <span class=\"heading3\">Shipping Information</span></b> 
          <table width=\"473\" border=\"0\" cellspacing=\"0\" cellpadding=\"1\">
            <tr> 
              <td align=\"right\" class=\"formlabel\" width=\"133\">First name:</td>
              <td width=\"336\"> 
                <input type=text name=\"shippingfirstname\" value=\"$shippingfirstname\" size=40>
              </td>
            </tr>
            <tr> 
              <td align=\"right\" class=\"formlabel\" width=\"133\">Last name:</td>
              <td width=\"336\"> 
                <input type=text name=\"shippinglastname\" value=\"$shippinglastname\" size=40>
              </td>
            </tr>
            <tr> 
              <td align=\"right\" class=\"formlabel\" width=\"133\">Address:</td>
              <td width=\"336\"> 
                <input type=text name=\"shippingaddress\" value=\"$shippingaddress\" size=40>
              </td>
            </tr>
            <tr> 
              <td align=\"right\" class=\"formlabel\" width=\"133\">Address 2:</td>
              <td width=\"336\"> 
                <input type=text name=\"shippingaddress2\" value=\"$shippingaddress2\" size=40>
              </td>
            </tr>
            <tr> 
              <td align=\"right\" class=\"formlabel\" width=\"133\">City:</td>
              <td width=\"336\"> 
                <input type=text name=\"shippingcity\" value=\"$shippingcity\" size=40>
              </td>
            </tr>
            <tr> 
              <td align=\"right\" class=\"formlabel\" width=\"133\">State/Province:</td>
              <td width=\"336\"> 
                <select name=\"shippingstate\">
                  <option  value=none>choose state";
 		foreach ($americanstates as $longstate => $shortstate) {
			echo "<option  value=$shortstate";
			if ($shortstate == $shippingstate) echo " selected";
			echo ">$longstate\n";
		}

		echo "</select>
              </td>
            </tr>
            <tr> 
              <td align=\"right\" class=\"formlabel\" width=\"133\">Zip:</td>
              <td width=\"336\"> 
                <input type=text name=\"shippingzip\" value=\"$shippingzip\" size=40>
              </td>
            </tr>
            <tr> 
              <td align=\"right\" class=\"formlabel\" width=\"133\">Country:</td>
              <td width=\"336\"> 
                <select name=\"shippingcountry\">
                  <option value=none>choose country";
 		foreach ($countries as $shortcountry => $longcountry) {
			if (strlen($longcountry) > 30) $longcountry = substr($longcountry,0,27)."...";
			echo "<option  value=$shortcountry";
			if ($shortcountry == $shippingcountry) echo " selected";
			echo ">$longcountry\n";
		}

		echo "</select>
              </td>
            </tr>
            <tr> 
              <td align=\"center\" class=\"sm\" colspan=\"2\"><br>Sending email to this customer is allowed: <input type=\"checkbox\" name=\"allowemail\""; if ($allowemail == "1") echo "checked"; echo "><br><br></td>
            </tr>
            <tr align=\"center\"> 
              <td colspan=\"2\"> 
                <table width=\"100%\" border=\"0\" cellspacing=\"0\" cellpadding=\"0\">
                  <tr> 
                    <td width=\"33%\" align=\"right\"><input type=\"image\" src=\"images/button_save.gif\" border=\"0\"></td>";
			  if ($quickquote) echo "
			        <td width=\"33%\" align=\"center\" >&nbsp;</td>
			        <td width=\"33%\"><a href=\"quote.php?quickquote=$quickquote&customer=$id\"><img src=\"images/button_select.gif\" width=\"98\" height=\"24\" border=\"0\"></a> 
                    </td>";
			  else echo "
                    <td width=\"33%\" align=\"center\" ><input type=\"image\" name=\"createquote\" src=\"images/button_createquote.gif\" width=\"98\" height=\"25\" border=\"0\"></td>
                    <td width=\"33%\"><a href=\"javascript:newWindow('customernote.php?customer=$id')\"><img src=\"images/button_addcomment.gif\" width=\"98\" height=\"24\" border=\"0\"></a> 
                    </td>";
		echo "
                  </tr>
                </table>
              </td>
            </tr>
          </table>
        </form>
      </center>";
} else if ($searchcustomer == "true" && ($email || $name || $phone || $letter)) {
	echo "<table width=\"650\" border=\"0\" cellpadding=\"0\" align=\"center\">
        <tr> 
          <td>
              <table width=\"500\" border=\"0\" cellpadding=\"0\" align=\"center\">
                <tr> 
                  <td colspan=\"3\" height=\"30\" class=\"heading2\">No customer matching the search criteria was found.</td>
                </tr>
			</table>
			</td>
			</tr>";
} else {
	echo "<table width=\"100%\" border=\"0\" cellpadding=\"0\" align=\"center\">
        <tr> 
          <td align=\"center\">";
		if ($errormessage) echo "<p class=\"sm\"><font color=\"#FF0000\">$errormessage</font></p>";
		echo "<br>";
		if (!$quickquote) {
			echo "
            <table width=\"550\" border=\"0\" cellpadding=\"1\" cellspacing=\"2\" bgcolor=\"#d0d0d0\">
              <tr> 
                <td colspan=\"3\" bgcolor=\"#808080\"> 
                  <p class=\"heading3_wht\">Create a quote.</p>
                </td>
              </tr>
		      <form action=\"quote.php\" method=\"post\"><input type=\"hidden\" name=\"quickquote\" value=\"true\">
              <tr> 
                <td colspan=\"2\" align=\"right\"> 
                  <p><span class=\"formlabel\"><a href=\"javascript:;\" onMouseOut=\"MM_swapImgRestore()\" onMouseOver=\"MM_swapImage('Image1','','images/contexthelpicon_over2.gif',1)\"><img src=\"images/contexthelpicon2.gif\" width=\"14\" height=\"15\" border=\"0\" name=\"Image1\" align=\"absmiddle\" onclick=\"return overlib('$tip1');\" onmouseout=\"return nd();\"></a> Quick Quote - <font class=\"sm\" face=\"Arial, Helvetica, sans-serif\">State(USA):</font></span> 
                    <b>
                    <select name=\"deststate\">
                      <option  value=none>choose state
					  <option  value=\"other\">Not USA";
				  foreach ($americanstates as $longstate => $shortstate) {
					  echo "<option  value=$shortstate";
					  if ($shortstate == $srow["shippingstate"]) echo " selected";
					  echo ">$longstate\n";
				  }
				  echo "</select>
                    </b><font class=\"formlabel\" face=\"Arial, Helvetica, sans-serif\">Zip:</font><b> 
                    <input type=text name=\"destzip\" size=10 value=\"\">
                    </b></p>
                  </td>
                <td width=\"44\" align=\"center\"><input type=\"image\" src=\"images/button_go.gif\" width=\"27\" height=\"24\" border=\"0\"></td>
              </tr>
			  </form>
			  <form action=\"quote.php\" method=\"post\">
              <tr> 
                <td width=\"361\" align=\"right\"> 
                  <p class=\"formlabel\">Edit quote. Quote number:</p>
                </td>
                <td width=\"131\">
                  <input type=\"text\" name=\"edit\">
                </td>
                <td width=\"44\" align=\"center\"><input type=\"image\" src=\"images/button_go.gif\" width=\"27\" height=\"24\" border=\"0\"></td>
              </tr>
			  </form>
			</table>";
		}
		echo "
			<br>
			<table width=\"550\" border=\"0\" cellpadding=\"1\" cellspacing=\"2\" bgcolor=\"#d0d0d0\">
                <tr> 
                  <td colspan=\"3\" bgcolor=\"#808080\"> 
                  <p class=\"heading3_wht\">Find an existing customer.</p></td>
                </tr>
                <tr> 
                  <td colspan=\"3\" class=\"formlabel\" align=\"center\">List contacts: <a href=\"customer.php?letter=all&searchcustomer=true\">All</a> <a href=\"customer.php?letter=a&searchcustomer=true\">A</a> <a href=\"customer.php?letter=b&searchcustomer=true\">B</a> <a href=\"customer.php?letter=c&searchcustomer=true\">C</a> <a href=\"customer.php?letter=d&searchcustomer=true\">D</a> <a href=\"customer.php?letter=e&searchcustomer=true\">E</a> <a href=\"customer.php?letter=f&searchcustomer=true\">F</a> <a href=\"customer.php?letter=g&searchcustomer=true\">G</a> <a href=\"customer.php?letter=h&searchcustomer=true\">H</a> <a href=\"customer.php?letter=i&searchcustomer=true\">I</a> <a href=\"customer.php?letter=j&searchcustomer=true\">J</a> <a href=\"customer.php?letter=k&searchcustomer=true\">K</a> <a href=\"customer.php?letter=l&searchcustomer=true\">L</a> <a href=\"customer.php?letter=m&searchcustomer=true\">M</a> <a href=\"customer.php?letter=n&searchcustomer=true\">N</a> <a href=\"customer.php?letter=o&searchcustomer=true\">O</a> <a href=\"customer.php?letter=p&searchcustomer=true\">P</a> <a href=\"customer.php?letter=q&searchcustomer=true\">Q</a> <a href=\"customer.php?letter=r&searchcustomer=true\">R</a> <a href=\"customer.php?letter=s&searchcustomer=true\">S</a> <a href=\"customer.php?letter=t&searchcustomer=true\">T</a> <a href=\"customer.php?letter=u&searchcustomer=true\">U</a> <a href=\"customer.php?letter=v&searchcustomer=true\">V</a> <a href=\"customer.php?letter=w&searchcustomer=true\">W</a> <a href=\"customer.php?letter=x&searchcustomer=true\">X</a> <a href=\"customer.php?letter=y&searchcustomer=true\">Y</a> <a href=\"customer.php?letter=z&searchcustomer=true\">Z</a><br><br></td>
                </tr>
				<form action=\"customer.php\" method=\"post\"><input type=\"hidden\" name=\"quickquote\" value=\"$quickquote\">
                <tr>
                  <td width=\"361\" align=\"right\" class=\"formlabel\"><a href=\"javascript:;\" onMouseOut=\"MM_swapImgRestore()\" onMouseOver=\"MM_swapImage('Image2','','images/contexthelpicon_over2.gif',1)\"><img src=\"images/contexthelpicon2.gif\" width=\"14\" height=\"15\" border=\"0\" name=\"Image2\" align=\"absmiddle\" onclick=\"return overlib('$tip2a');\" onmouseout=\"return nd();\"></a> Search by e-mail: </td>
                  <td width=\"131\"><input type=\"text\" name=\"email\" value=\"$email\"><input type=\"hidden\" name=\"searchcustomer\" value=\"true\"></td>
                  <td width=\"44\" align=\"center\"><input type=\"image\" src=\"images/button_go.gif\" width=\"27\" height=\"24\" border=\"0\"></td>
                </tr></form>
				<form action=\"customer.php\" method=\"post\"><input type=\"hidden\" name=\"quickquote\" value=\"$quickquote\">
                <tr> 
                  <td width=\"361\" align=\"right\" class=\"formlabel\"><a href=\"javascript:;\" onMouseOut=\"MM_swapImgRestore()\" onMouseOver=\"MM_swapImage('Image3','','images/contexthelpicon_over2.gif',1)\"><img src=\"images/contexthelpicon2.gif\" width=\"14\" height=\"15\" border=\"0\" name=\"Image3\" align=\"absmiddle\" onclick=\"return overlib('$tip2b');\" onmouseout=\"return nd();\"></a> Search by name: </td>
                  <td width=\"131\"><input type=\"text\" name=\"name\"><input type=\"hidden\" name=\"searchcustomer\" value=\"true\"></td>
                  <td width=\"44\" align=\"center\"><input type=\"image\" src=\"images/button_go.gif\" width=\"27\" height=\"24\" border=\"0\"></td>
                </tr></form>
				<form action=\"customer.php\" method=\"post\"><input type=\"hidden\" name=\"quickquote\" value=\"$quickquote\">
                <tr> 
                  <td width=\"361\" align=\"right\" class=\"formlabel\"><a href=\"javascript:;\" onMouseOut=\"MM_swapImgRestore()\" onMouseOver=\"MM_swapImage('Image4','','images/contexthelpicon_over2.gif',1)\"><img src=\"images/contexthelpicon2.gif\" width=\"14\" height=\"15\" border=\"0\" name=\"Image4\" align=\"absmiddle\" onclick=\"return overlib('$tip2c');\" onmouseout=\"return nd();\"></a> 
                  Search by phone number: </td>
                  <td width=\"131\" align=\"center\"><input type=\"text\" name=\"phone\"><input type=\"hidden\" name=\"searchcustomer\" value=\"true\"></td>
                  <td width=\"44\" align=\"center\"><input type=\"image\" src=\"images/button_go.gif\" width=\"27\" height=\"24\" border=\"0\"></td>
                </tr></form>
				<form action=\"customer.php\" method=\"post\"><input type=\"hidden\" name=\"quickquote\" value=\"$quickquote\">
                <tr> 
                  <td width=\"361\" align=\"right\" class=\"formlabel\"><a href=\"javascript:;\" onMouseOut=\"MM_swapImgRestore()\" onMouseOver=\"MM_swapImage('Image6','','images/contexthelpicon_over2.gif',1)\"><img src=\"images/contexthelpicon2.gif\" width=\"14\" height=\"15\" border=\"0\" name=\"Image6\" align=\"absmiddle\" onclick=\"return overlib('$tip2d');\" onmouseout=\"return nd();\"></a> 
                  Search by business name: </td>
                  <td width=\"131\" align=\"center\"><input type=\"text\" name=\"business\"><input type=\"hidden\" name=\"searchcustomer\" value=\"true\"></td>
                  <td width=\"44\" align=\"center\"><input type=\"image\" src=\"images/button_go.gif\" width=\"27\" height=\"24\" border=\"0\"></td>
                </tr></form>
              </table>
              <br>
			<form action=\"customer.php\" method=\"post\"><input type=\"hidden\" name=\"quickquote\" value=\"$quickquote\">
            <table width=\"550\" border=\"0\" cellpadding=\"1\" cellspacing=\"2\" bgcolor=\"#d0d0d0\">
              <tr> 
                <td colspan=\"4\" bgcolor=\"#808080\"> 
                  <p class=\"heading3_wht\">Add a new customer record. <a href=\"javascript:;\" onMouseOut=\"MM_swapImgRestore()\" onMouseOver=\"MM_swapImage('Image5','','images/contexthelpicon_over.gif',1)\"><img src=\"images/contexthelpicon.gif\" width=\"14\" height=\"15\" border=\"0\" name=\"Image5\" align=\"absmiddle\" onclick=\"return overlib('$tip3');\" onmouseout=\"return nd();\"></a></p></td>
			  </tr>
		      <tr> 
                <td width=\"290\" align=\"right\" class=\"formlabel\"> First Name: </td>
                <td width=\"195\"> 
                  <p> 
                    <input type=\"text\" name=\"firstname\" value=\"$firstname\" size=\"30\">
                  </p>
                </td>
				<td width=\"64\" align=\"center\">&nbsp;</td>
              </tr>
              <tr> 
                <td width=\"290\" align=\"right\" class=\"formlabel\"> Last Name: </td>
                <td width=\"195\"> 
                  <p> 
                    <input type=\"text\" name=\"lastname\" value=\"$lastname\" size=\"30\">
                  </p>
                </td>
				<td width=\"64\" align=\"center\">&nbsp;</td>
              </tr>
              <tr> 
                <td width=\"290\" align=\"right\" class=\"formlabel\"> Business name: </td>
                <td width=\"195\"> 
                  <p> 
                    <input type=\"text\" name=\"business\" value=\"$business\" size=\"30\">
                  </p>
                </td>
				<td width=\"64\" align=\"center\">&nbsp;</td>
              </tr>
              <tr> 
                <td width=\"290\" align=\"right\" class=\"formlabel\"> Address: </td>
                <td width=\"195\"> 
                  <p> 
                    <input type=\"text\" name=\"address\" value=\"$address\" size=\"30\">
                  </p>
                </td>
				<td width=\"64\" align=\"center\">&nbsp;</td>
              </tr>
              <tr> 
                <td width=\"290\" align=\"right\" class=\"formlabel\"> City: </td>
                <td width=\"195\"> 
                  <p> 
                    <input type=\"text\" name=\"city\" value=\"$city\" size=\"30\">
                  </p>
                </td>
				<td width=\"64\" align=\"center\">&nbsp;</td>
              </tr>
              <tr> 
                <td height=\"3\" width=\"290\" align=\"right\" class=\"formlabel\"> State/Province: 
                </td>
                <td height=\"3\" width=\"195\"> 
                <select name=\"state\">
                  <option  value=none>choose state<option value=\"n/a\">n/a";
 		foreach ($americanstates as $longstate => $shortstate) {
			echo "<option  value=$shortstate";
			if ($shortstate == $state) echo " selected";
			echo ">$longstate\n";
		}

		echo "</select>
                </td>
				<td width=\"64\" align=\"center\">&nbsp;</td>
              </tr>
              <tr> 
                <td width=\"290\" align=\"right\" class=\"formlabel\"> Zip/Postal Code: 
                </td>
                <td width=\"195\"> 
                  <p> 
                    <input type=\"text\" name=\"zip\" value=\"$zip\" size=\"30\">
                  </p>
                </td>
				<td width=\"64\" align=\"center\">&nbsp;</td>
              </tr>
              <tr> 
                <td width=\"290\" align=\"right\" class=\"formlabel\"> Country: 
                </td>
                <td width=\"195\"> 
                <select name=\"country\">
                  <option  value=none>choose country";
 		foreach ($countries as $shortcountry => $longcountry) {
			echo "<option  value=$shortcountry";
			if ($shortcountry == $country) echo " selected";
			echo ">$longcountry\n";
		}

		echo "</select>
                </td>
				<td width=\"64\" align=\"center\">&nbsp;</td>
              </tr>
              <tr> 
                <td width=\"290\" align=\"right\" class=\"formlabel\"> Phone: </td>
                <td width=\"195\"> 
                  <p> 
                    <input type=\"text\" name=\"phone\" value=\"$phone\" size=\"30\">
                  </p>
                </td>
				<td width=\"64\" align=\"center\">&nbsp;</td>
              </tr>
              <tr> 
                <td width=\"290\" align=\"right\" class=\"formlabel\"> E-Mail: </td>
                <td width=\"195\"> 
                  <p> 
                    <input type=\"text\" name=\"email\" value=\"$email\" size=\"30\">
				  </p>
				</td>
				<td width=\"64\" align=\"center\"><input type=\"image\" name=\"submit\" src=\"images/button_save.gif\" border=\"0\"></td>
              </tr>
            </table>";
		if ($createquote) echo "<input type=\"hidden\" name=\"createquote\" value=\"true\">";
		echo "<input type=\"hidden\" name=\"create\" value=\"true\">
			</form>
          </td>
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