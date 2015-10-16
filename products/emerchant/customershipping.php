<?php
include "../admin/ashopconstants.inc.php";
include "../admin/checklicense.inc.php";
include "checklogin.inc.php";
include "emfunc.inc.php";
$pagetitle = "Find or Create Customer";
include "template.inc.php";

// Open database...
$db = @mysqli_connect("$databaseserver", "$databaseuser", "$databasepasswd", "$databasename");

// Get shipping info, if available...
$shippingresult = @mysqli_query($db, "SELECT * FROM shipping WHERE customerid='$id'");

// Store edited customer info...
if ($update) {
	if ($shippingfirstname && $shippinglastname && $shippingaddress && $shippingzip && $shippingstate && $shippingcountry) {
		if (@mysqli_num_rows($shippingresult)) @mysqli_query($db, "UPDATE shipping SET shippingfirstname='$shippingfirstname', shippinglastname='$shippinglastname', shippingaddress='$shippingaddress', shippingaddress2='$shippingaddress2', shippingcity='$shippingcity', shippingzip='$shippingzip', shippingstate='$shippingstate', shippingcountry='$shippingcountry', vat='$vat' WHERE customerid='$id'");
		else @mysqli_query($db, "INSERT INTO shipping (customerid, shippingfirstname, shippinglastname, shippingaddress, shippingaddress2, shippingcity, shippingzip, shippingstate, shippingcountry, vat) values ('$id', '$shippingfirstname', '$shippinglastname', '$shippingaddress', '$shippingaddress2', '$shippingcity', '$shippingzip', '$shippingstate', '$shippingcountry', '$vat')");
		header("Location: quote.php?customer=$id");
		exit;
	} else $errormessage = "<p><font color=\"#FF0000\"><b>You have forgotten to enter a value for some of the required fields!</b></font></p>";
}

// Get customer data for selected customer(s)...
if ($id) $result = @mysqli_query($db, "SELECT * FROM customer WHERE customerid='$id'");

// Update displayed shipping info...
$shippingresult = @mysqli_query($db, "SELECT * FROM shipping WHERE customerid='$id'");

echo $header;
emerchant_sidebar();
echo "<td valign=\"top\">";
emerchant_topbar("Find or Create Customer");

if (@mysqli_num_rows($result)) {
	$row = @mysqli_fetch_array($result);
	$srow = @mysqli_fetch_array($shippingresult);
	echo "<center>$errormessage
		  <script language=\"JavaScript\">
			  function cpfrombilling()
			  {
					if (document.shippingform.copyfrombilling.checked) {
						document.shippingform.shippingfirstname.value = '{$row["firstname"]}';
						document.shippingform.shippinglastname.value = '{$row["lastname"]}';
						document.shippingform.shippingaddress.value = '{$row["address"]}';
						document.shippingform.shippingzip.value = '{$row["zip"]}';
						document.shippingform.shippingcity.value = '{$row["city"]}';
						document.shippingform.shippingstate.value = '{$row["state"]}';
						document.shippingform.shippingcountry.value = '{$row["country"]}';
					} else {
						document.shippingform.shippingfirstname.value = '';
						document.shippingform.shippinglastname.value = '';
						document.shippingform.shippingaddress.value = '';
						document.shippingform.shippingzip.value = '';
						document.shippingform.shippingcity.value = '';
						document.shippingform.shippingstate.value = '';
						document.shippingform.shippingcountry.value = '';
					}
			  }
		  </script>
		<br>
		<form action=\"customershipping.php\" method=\"post\" name=\"shippingform\"><input type=\"hidden\" name=\"update\" value=\"true\">
        <span class=\"heading2\">Profile of ".$row["firstname"]." ".$row["lastname"].", Customer ID 
        $id</span> <input type=\"image\" name=\"createquote\" src=\"images/icon_quote-order.gif\" width=\"18\" height=\"18\" alt=\"Create a new quote or order for this customer.\" border=\"0\">&nbsp;<input type=\"image\" name=\"viewhistory\" src=\"images/icon_history.gif\" width=\"15\" height=\"15\" alt=\"View history for ".$row["firstname"]." ".$row["lastname"].".\" border=\"0\">&nbsp;<a href=\"javascript:newWindow('customernote.php?customer=$id')\"><img src=\"images/icon_customernote.gif\" width=\"15\" height=\"15\" alt=\"Create a note regarding this customer.\" border=\"0\"></a>&nbsp;<a href=\"javascript:newWindow('composemessage.php?customer=$id')\"><img src=\"images/icon_mail.gif\" alt=\"Send mail.\" border=\"0\"></a><br><br>
        <input type=\"hidden\" name=\"id\" value=\"$id\">
          <b><br>
          <span class=\"heading3\">Shipping Information</span></b> 
          <table width=\"473\" border=\"0\" cellspacing=\"0\" cellpadding=\"1\">
            <tr> 
              <td align=\"right\" class=\"formlabel\" width=\"133\">Copy from billing:</td>
              <td width=\"336\"> 
                <input type=checkbox name=\"copyfrombilling\" onClick=\"cpfrombilling()\">
              </td>
            </tr>
            <tr> 
              <td align=\"right\" class=\"formlabel\" width=\"133\">First name:</td>
              <td width=\"336\"> 
                <input type=text name=\"shippingfirstname\" value=\"".$srow["shippingfirstname"]."\" size=40>
              </td>
            </tr>
            <tr> 
              <td align=\"right\" class=\"formlabel\" width=\"133\">Last name:</td>
              <td width=\"336\"> 
                <input type=text name=\"shippinglastname\" value=\"".$srow["shippinglastname"]."\" size=40>
              </td>
            </tr>
            <tr> 
              <td align=\"right\" class=\"formlabel\" width=\"133\">Address:</td>
              <td width=\"336\"> 
                <input type=text name=\"shippingaddress\" value=\"".$srow["shippingaddress"]."\" size=40>
              </td>
            </tr>
            <tr> 
              <td align=\"right\" class=\"formlabel\" width=\"133\">Address 2:</td>
              <td width=\"336\"> 
                <input type=text name=\"shippingaddress2\" value=\"".$srow["shippingaddress2"]."\" size=40>
              </td>
            </tr>
            <tr> 
              <td align=\"right\" class=\"formlabel\" width=\"133\">City:</td>
              <td width=\"336\"> 
                <input type=text name=\"shippingcity\" value=\"".$srow["shippingcity"]."\" size=40>
              </td>
            </tr>
            <tr> 
              <td align=\"right\" class=\"formlabel\" width=\"133\">State/Province:</td>
              <td width=\"336\"> 
                <select name=\"shippingstate\">
                  <option  value=none>choose state";
 		foreach ($americanstates as $longstate => $shortstate) {
			echo "<option  value=$shortstate";
			if ($shortstate == $srow["shippingstate"]) echo " selected";
			echo ">$longstate\n";
		}

		echo "</select>
              </td>
            </tr>
            <tr> 
              <td align=\"right\" class=\"formlabel\" width=\"133\">Zip:</td>
              <td width=\"336\"> 
                <input type=text name=\"shippingzip\" value=\"".$srow["shippingzip"]."\" size=40>
              </td>
            </tr>
            <tr> 
              <td align=\"right\" class=\"formlabel\" width=\"133\">Country:</td>
              <td width=\"336\"> 
                <select name=\"shippingcountry\">
                  <option  value=none>choose country";
 		foreach ($countries as $shortcountry => $longcountry) {
			if (strlen($longcountry) > 30) $longcountry = substr($longcountry,0,27)."...";
			echo "<option  value=$shortcountry";
			if ($shortcountry == $srow["shippingcountry"]) echo " selected";
			echo ">$longcountry\n";
		}

		echo "</select>
              </td>
            </tr>
              <tr align=\"center\"> 
                <td colspan=\"2\"> 
                  <table width=\"60%\" border=\"0\" cellspacing=\"0\" cellpadding=\"0\">
                    <tr> 
                      <td width=\"100%\" align=\"right\"><input type=\"image\" name=\"submit\" src=\"images/button_save.gif\" border=\"0\"></td>
                    </tr>
                  </table>
                </td>
              </tr>
          </table>
        </form>
      </center>";
} 
echo "</td>
  </tr>
  <tr> 
    <td align=\"center\" colspan=\"2\"></td>
  </tr>
</table>";
echo $footer;
?>