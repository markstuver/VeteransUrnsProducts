<?php
include "../admin/ashopconstants.inc.php";
include "../admin/checklicense.inc.php";
include "checklogin.inc.php";
include "emfunc.inc.php";
$pagetitle = "View Quotes";
include "template.inc.php";

// Open database...
$db = @mysqli_connect("$databaseserver", "$databaseuser", "$databasepasswd", "$databasename");

// Handle removal of quotes...
if ($delete) {
	if ($yes) {
       $sql="DELETE FROM emerchant_quotes WHERE id='$delete'";
	   if (!empty($affiliateid)) $sql .= " AND user='$emerchant_user'";
       $result = @mysqli_query($db, $sql);
	   header("Location: viewquotes.php?notice=removed");
    }
	elseif ($no) header("Location: viewquotes.php");
	else {
		echo $header;
		emerchant_sidebar();
		echo "<td valign=\"top\">";
		emerchant_topbar("Delete Quote");
		echo "<p class=\"formlabel\" align=\"center\">Delete quote</p>
        <p class=\"warning\" align=\"center\">Are you sure that you want to delete the quote number $delete from the database?</p>
		<form action=\"viewquotes.php\" method=\"post\">
		<table width=\"500\" border=\"0\" cellspacing=\"0\" cellpadding=\"3\" align=\"center\">
		<tr>
        <td width=\"100%\" align=\"center\" valign=\"top\"><input type=\"submit\" name=\"yes\" value=\"Yes\">
		<input type=\"submit\" name=\"no\" value=\"No \"></td>
		</tr></table><input type=\"hidden\" name=\"delete\" value=\"$delete\"></form>
		</td></tr></table>$footer";
	}
} 

echo $header;
emerchant_sidebar();
echo "<td valign=\"top\">";
emerchant_topbar("View Quotes");
if ($notice) echo "<br><div align=\"center\" class=\"heading3\"><br><font color=\"#000099\">$notice</font></div>";
if (!empty($affiliateid)) $result = @mysqli_query($db, "SELECT * FROM emerchant_quotes AND user='$emerchant_user' ORDER BY date");
else $result = @mysqli_query($db, "SELECT * FROM emerchant_quotes ORDER BY date");

echo "<table width=\"650\" border=\"0\" cellpadding=\"5\" align=\"center\">
        <tr> 
          <td align=\"left\"><br><div class=\"formlabel\">Unconverted quotes...</div><br>
            <table width=\"100%\" border=\"0\" cellspacing=\"2\" cellpadding=\"1\" bgcolor=\"#d0d0d0\" align=\"center\">
              <tr bgcolor=\"#808080\">
                <td width=\"80\" class=\"heading3_wht\"> 
                  <b>Date</b>
                </td>
                <td width=\"200\" class=\"heading3_wht\"> 
                  <b>Customer</b>
                </td>
                <td class=\"heading3_wht\">
                  <b>Products</b>
                </td>
				<td width=\"40\" class=\"heading3_wht\">&nbsp;</td>
              </tr>";
	while ($row = @mysqli_fetch_array($result)) {
		$date = explode(" ", $row["date"]);
		$qqzip = $row["qqzip"];
		$qqstate = $row["qqstate"];
		$result2 = @mysqli_query($db, "SELECT * FROM customer WHERE customerid='{$row["customerid"]}'");
		$customerrow = @mysqli_fetch_array($result2);
		$parsed_products = ashop_parseproductstring($db, $row["products"]);
		unset($productdescription);
		if ($parsed_products) {
			foreach ($parsed_products as $productnumber => $thisproduct) $productdescription .= $thisproduct["quantity"]." ".$thisproduct["name"]."<br>";
			$productdescription = substr_replace($productdescription, "", -2);
		} else $productdescription = "Custom item(s)";
		echo "<tr bgcolor=\"#e0e0e0\"> 
                <td width=\"80\" valign=\"top\" class=\"sm\">
					{$date[0]}
                </td>";
		if (is_array($customerrow)) echo "<td width=\"150\" valign=\"top\" class=\"sm\">
					<a href=\"customer.php?id={$row["customerid"]}\">{$customerrow["firstname"]} {$customerrow["lastname"]}</a>
                </td>";
		else echo "<td width=\"150\" valign=\"top\" class=\"sm\">$qqstate $qqzip</td>";
		echo "<td class=\"sm\">
					$productdescription
                </td>
				<td width=\"90\" align=\"center\" valign=\"top\" class=\"sm\">
					[<a href=\"quote.php?edit={$row["id"]}\">Edit</a>] [<a href=\"viewquotes.php?delete={$row["id"]}\">Delete</a>]
				</td>
              </tr>";
	}
	echo "</table>
          </td>
        </tr>
      </table>";
echo "</td>
  </tr>
  <tr> 
    <td align=\"center\" colspan=\"2\"></td>
  </tr>
</table>";
echo $footer;
?>