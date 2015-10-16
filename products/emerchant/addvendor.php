<?php
include "../admin/ashopconstants.inc.php";
include "../admin/checklicense.inc.php";
include "checklogin.inc.php";
include "emfunc.inc.php";
$pagetitle = "Vendors";
include "template.inc.php";

// Open database...
$db = @mysqli_connect("$databaseserver", "$databaseuser", "$databasepasswd", "$databasename");

echo $header;
emerchant_sidebar();
echo "<td valign=\"top\">";
emerchant_topbar("Vendors");

echo "<table width=\"100%\" border=\"0\" cellpadding=\"0\" align=\"center\">
        <tr> 
          <td align=\"center\"><br><form action=\"vendor.php\" method=\"post\">
            <table width=\"550\" border=\"0\" cellpadding=\"1\" cellspacing=\"2\" bgcolor=\"#d0d0d0\">
              <tr> 
                <td colspan=\"4\" bgcolor=\"#808080\"> 
                  <p class=\"heading3_wht\">Add a new vendor record...</p></td>
			  </tr>
			  <tr> 
                <td width=\"290\" align=\"right\" class=\"formlabel\"> Name: </td>
                <td width=\"195\"> 
                  <p> 
                    <input type=\"text\" name=\"name\" value=\"$name\" size=\"30\">
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
                <td width=\"290\" align=\"right\" class=\"formlabel\"> State/Province: 
                </td>
                <td width=\"195\"> 
                <select name=\"state\">
                  <option  value=none>choose state";
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
			if (strlen($longcountry) > 30) $longcountry = substr($longcountry,0,27)."...";
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
            </table><input type=\"hidden\" name=\"create\" value=\"true\">
			</form>
          </td>
        </tr>
      </table>
    </td>
  </tr>
</table>$footer";
@mysqli_close($db);
?>