<?php
include "../admin/checklicense.inc.php";
include "checklogin.inc.php";
include "emfunc.inc.php";
$pagetitle = "Vendor Contact";
include "template.inc.php";

// Open database...
$db = @mysqli_connect("$databaseserver", "$databaseuser", "$databasepasswd", "$databasename");

// Save new vendor contact...
if ($create == "true" && $firstname && $lastname && $email && $vendorid) {
	$result = @mysqli_query($db, "SELECT * FROM emerchant_vendcontact WHERE email='$email' && vendorid='$vendorid'");
	if (@mysqli_num_rows($result)) $errormessage = "A contact with this e-mail address already exists! Please enter a new e-mail address or select the existing contact on the previous page.";
	else {
		$result = @mysqli_query($db, "INSERT INTO emerchant_vendcontact (firstname, lastname, title, email, phone, vendorid) VALUES ('$firstname', '$lastname', '$title', '$email', '$phone', '$vendorid')");
		$id = @mysqli_insert_id($db);
		header("Location: vendor.php?id=$vendorid");
		exit;
	}
}

// Store edited vendor info...
if ($update == "true") {
	if ($firstname && $lastname && $email && $vendorid) {
		$result = @mysqli_query($db, "UPDATE emerchant_vendcontact SET firstname='$firstname', lastname='$lastname', title='$title', email='$email', phone='$phone', vendorid='$vendorid' WHERE vendcontactid='$id'");
		header("Location: vendor.php?id=$vendorid");
		exit;
	} else $errormessage = "<p><font color=\"#FF0000\"><b>You have forgotten to enter a value for some of the required fields!</b></font></p>";
}

// Get data for selected vendor and contact...
if ($vendorid) {
	$vendorresult = @mysqli_query($db, "SELECT * FROM emerchant_vendor WHERE vendorid='$vendorid'");
	$vrow = @mysqli_fetch_array($vendorresult);
	if ($id) $result = @mysqli_query($db, "SELECT * FROM emerchant_vendcontact WHERE vendcontactid='$id'");
} else {
	$errormessage = "<p><font color=\"#FF0000\"><b>No vendor! You must select a vendor for which this person is a contact.</b></font></p>";
}

echo $header;
emerchant_sidebar();
echo "<td valign=\"top\">";
emerchant_topbar("Vendor Contact");
if ($notice) echo "<div align=\"center\" class=\"heading3\"><br><font color=\"#000099\">$notice</font></div>";
if ($id && @mysqli_num_rows($result)) {
	$row = @mysqli_fetch_array($result);
	echo "<center>$errormessage
        <br>
		<form action=\"vendor.php\" method=\"post\">
        <span class=\"heading2\">Contact for vendor <a href=\"vendor.php?id={$vrow["vendorid"]}\">{$vrow["name"]}</a></span> <a href=\"contacthistory.php?vendorcontact=$id\"><img src=\"images/icon_history.gif\" alt=\"View history for {$row["firstname"]} {$row["lastname"]}\" border=\"0\"></a>&nbsp;<a href=\"javascript:newWindow('vendornote.php?vendorcontact=$id')\"><img src=\"images/icon_vendornote.gif\" width=\"18\" height=\"18\" alt=\"Create a note regarding this vendor.\" border=\"0\"></a>&nbsp;<a href=\"javascript:newWindow('composemessage.php?vendor=$vendorid&vendorcontact=$id')\"><img src=\"images/icon_mail.gif\" alt=\"Send mail.\" border=\"0\"></a><br><br></form>
        <form action=\"vendorcontact.php\" method=\"post\">
		<input type=\"hidden\" name=\"update\" value=\"true\">
		<input type=\"hidden\" name=\"vendorid\" value=\"$vendorid\">
        <input type=\"hidden\" name=\"id\" value=\"$id\">
          <table width=\"479\" border=\"0\" cellspacing=\"0\" cellpadding=\"1\">
            <tr> 
              <td align=\"right\" class=\"formlabel\" width=\"137\">Firstname:</td>
              <td width=\"338\"> 
                <input type=text name=\"firstname\" value=\"".$row["firstname"]."\" size=40>
              </td>
            </tr>
            <tr> 
              <td align=\"right\" class=\"formlabel\" width=\"137\">Lastname:</td>
              <td width=\"338\"> 
                <input type=text name=\"lastname\" value=\"".$row["lastname"]."\" size=40>
              </td>
            </tr>
            <tr> 
              <td align=\"right\" class=\"formlabel\" width=\"137\">Title:</td>
              <td width=\"338\"> 
                <input type=text name=\"title\" value=\"".$row["title"]."\" size=40>
              </td>
            </tr>
            <tr> 
              <td align=\"right\" class=\"formlabel\" width=\"137\">Email:</td>
              <td width=\"338\"> 
                <input type=text name=\"email\" value=\"".$row["email"]."\" size=40>
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
      </center>";
} else {
	echo "<center>$errormessage
        <br>
		<form action=\"vendor.php\" method=\"post\">
        <span class=\"heading2\">Add a contact for vendor ".$vrow["name"]."</span> <input type=\"image\" name=\"createpo\" src=\"images/icon_quote-order.gif\" width=\"18\" height=\"18\" alt=\"Create a new purchase order for this vendor.\" border=\"0\">&nbsp;<input type=\"image\" name=\"viewhistory\" src=\"images/icon_history.gif\" width=\"15\" height=\"15\" alt=\"View history for ".$vrow["name"].".\" border=\"0\">&nbsp;<a href=\"javascript:newWindow('vendornote.php?vendor=$vendorid')\"><img src=\"images/icon_vendornote.gif\" width=\"18\" height=\"18\" alt=\"Create a note regarding this vendor.\" border=\"0\"></a>&nbsp;<a href=\"javascript:newWindow('composemessage.php?vendor=$vendorid')\"><img src=\"images/icon_mail.gif\" alt=\"Send mail.\" border=\"0\"></a><br><br></form>
		<form action=\"vendorcontact.php\" method=\"post\">
		<input type=\"hidden\" name=\"create\" value=\"true\">
        <input type=\"hidden\" name=\"vendorid\" value=\"$vendorid\">
          <table width=\"479\" border=\"0\" cellspacing=\"0\" cellpadding=\"1\">
            <tr> 
              <td align=\"right\" class=\"formlabel\" width=\"137\">Firstname:</td>
              <td width=\"338\"> 
                <input type=text name=\"firstname\" value=\"$firstname\" size=40>
              </td>
            </tr>
            <tr> 
              <td align=\"right\" class=\"formlabel\" width=\"137\">Lastname:</td>
              <td width=\"338\"> 
                <input type=text name=\"lastname\" value=\"$lastname\" size=40>
              </td>
            </tr>
            <tr> 
              <td align=\"right\" class=\"formlabel\" width=\"137\">Title:</td>
              <td width=\"338\"> 
                <input type=text name=\"title\" value=\"$title\" size=40>
              </td>
            </tr>
            <tr> 
              <td align=\"right\" class=\"formlabel\" width=\"137\">Email:</td>
              <td width=\"338\"> 
                <input type=text name=\"email\" value=\"$email\" size=40>
              </td>
            </tr>
            <tr> 
              <td align=\"right\" class=\"formlabel\" width=\"137\">Phone:</td>
              <td width=\"338\"> 
                <input type=text name=\"phone\" value=\"$phone\" size=40>
              </td>
            </tr>
          </table>
          <table width=\"400\" border=\"0\" cellspacing=\"0\" cellpadding=\"1\">
			<tr>
			  <td width=\"100%\" align=\"right\"><input type=\"image\" src=\"images/button_save.gif\" border=\"0\"></td>
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