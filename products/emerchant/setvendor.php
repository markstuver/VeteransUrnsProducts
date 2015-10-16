<?php
include "../admin/ashopconstants.inc.php";
include "../admin/checklicense.inc.php";
include "checklogin.inc.php";
include "emfunc.inc.php";
$pagetitle = "Select Vendor";
include "template.inc.php";

// Open database...
$db = @mysqli_connect("$databaseserver", "$databaseuser", "$databasepasswd", "$databasename");

if (!$cat) {
	$sql = "SELECT categoryid FROM category WHERE userid='1' ORDER BY ordernumber";
	$result = @mysqli_query($db, $sql);
	$numberofcategories = @mysqli_num_rows($result);
	$cat = @mysqli_result($result, 0, "categoryid");
}

echo $header;
emerchant_sidebar();
echo "<td valign=\"top\">";
emerchant_topbar("Select Vendor");
echo "<table width=\"100%\" border=\"0\" cellspacing=\"0\" cellpadding=\"0\" align=\"center\">
<tr><td><div align=\"center\" class=\"heading3\"><br>";

echo "<table width=\"100%\" border=\"0\" cellspacing=\"0\" cellpadding=\"5\"><tr><td width=\"150\" valign=\"top\">
	  <p class=\"category\">Select Category<br>
	  <table width=\"100%\" border=\"1\" cellspacing=\"0\" cellpadding=\"1\" bordercolor=\"#FFFFFF\">";

// List categories...
    if ($cat) {
       $sql="SELECT grandparentcategoryid, parentcategoryid from category WHERE categoryid = $cat AND userid='1' ORDER BY ordernumber";
       $result = @mysqli_query($db, $sql);
       $grandparent = @mysqli_result($result, 0, "grandparentcategoryid");
       $parent = @mysqli_result($result, 0, "parentcategoryid");
    }
    $sql="SELECT categoryid, name, ordernumber FROM category WHERE grandparentcategoryid = categoryid AND userid='1' ORDER BY ordernumber";
    $result = @mysqli_query($db, $sql);
    for ($i = 0; $i < @mysqli_num_rows($result); $i++) {
      $categoryname = @mysqli_result($result, $i, "name");
      $categoryid = @mysqli_result($result, $i, "categoryid");
	  $catordernumber = @mysqli_result($result, $i, "ordernumber");
	  if ($categoryid == $cat) $cellcolor = "#F0F0F0";
	  else $cellcolor = "#D0D0D0";
      echo "<tr><td bgcolor=\"$cellcolor\" valign=\"top\"><a href=\"setvendor.php?cat=$categoryid\" class=\"category\">$categoryname</a><br><span=\"smaller\"></span></td></tr>";
      if (($categoryid == $cat) || ($categoryid == $grandparent)) {
         $subsql="SELECT categoryid, name, ordernumber FROM category WHERE grandparentcategoryid = $categoryid AND categoryid != grandparentcategoryid AND parentcategoryid = categoryid AND userid='1' ORDER BY ordernumber";
         $subresult = @mysqli_query($db, $subsql);
         for ($j = 0; $j < @mysqli_num_rows($subresult); $j++) {
            $subcategoryname = @mysqli_result($subresult, $j, "name");
            $subcategoryid = @mysqli_result($subresult, $j, "categoryid");
			$suborderno = @mysqli_result($subresult, $j, "ordernumber");
			if ($subcategoryid == $cat) $cellcolor = "#F0F0F0";
 		    else $cellcolor = "#D0D0D0";
            echo "<tr><td bgcolor=\"$cellcolor\"><img src=\"../admin/images/icon_subcategory.gif\" alt=\"Subcategory of $categoryname\"><a href=\"setvendor.php?cat=$subcategoryid\" class=\"category\">$subcategoryname</a><br><img src=\"../admin/images/10pxl.gif\"></span></td></tr>";
			$previoussuborderno = $suborderno;
			$previoussubcategoryid = $subcategoryid;
			if ($subcategoryid == $parent || $subcategoryid == $cat) {
				$subsubsql="SELECT categoryid, name, ordernumber FROM category WHERE parentcategoryid = $subcategoryid AND parentcategoryid != categoryid AND userid='1' ORDER BY ordernumber";
				$subsubresult = @mysqli_query($db, $subsubsql);
				for ($k = 0; $k < @mysqli_num_rows($subsubresult); $k++) {
					$subsubcategoryname = @mysqli_result($subsubresult, $k, "name");
					$subsubcategoryid = @mysqli_result($subsubresult, $k, "categoryid");
					$subsuborderno = @mysqli_result($subsubresult, $k, "ordernumber");
					if ($subsubcategoryid == $cat) $cellcolor = "#F0F0F0";
					else $cellcolor = "#D0D0D0";
					echo "<tr><td bgcolor=\"$cellcolor\">&nbsp;&nbsp;<img src=\"../admin/images/icon_subcategory.gif\" alt=\"Subcategory of $subcategoryname\"><a href=\"setvendor.php?cat=$subsubcategoryid\" class=\"category\">$subsubcategoryname</a><br><img src=\"../admin/images/10pxl.gif\"></span></td></tr>";
					$previoussubsuborderno = $subsuborderno;
					$previoussubsubcategoryid = $subsubcategoryid;
				}
			}
         }
      }
	  $catpreviousorderno = $catordernumber;
	  $previouscategoryid = $categoryid;
    }
echo "</table></p></td><td valign=\"top\">";

// Show category name and description...
  if ($cat) {
    $sql="SELECT name, description FROM category WHERE categoryid = $cat AND userid='1'";
    $result = @mysqli_query($db, $sql);
    $categoryname = @mysqli_result($result, 0, "name");
    $categorydescr = @mysqli_result($result, 0, "description");
	echo "<p><table width=\"100%\" bgcolor=\"#FFFFFF\" border=\"0\" cellpadding=\"1\" cellspacing=\"0\"><tr><td><span class=\"heading4\">$categoryname:</span><span class=\"text\"> $categorydescr</span></td></tr></table>";
  }

  
// List products belonging to this category...
    $sql="SELECT product.* from productcategory, product WHERE productcategory.categoryid = $cat AND product.productid = productcategory.productid AND product.userid='1' ORDER BY product.ordernumber";
    $result = @mysqli_query($db, $sql);
	$numberofrows = intval(@mysqli_num_rows($result));
	if (!$admindisplayitems) {
		if ($c_admindisplayitems) $admindisplayitems = $c_admindisplayitems;
		else $admindisplayitems = 10;
	}
	$numberofpages = ceil($numberofrows/$admindisplayitems);
	if ($resultpage > 1) $startrow = (intval($resultpage)-1) * $admindisplayitems;
	else {
		$resultpage = 1;
		$startrow = 0;
	}
	$stoprow = $startrow + $admindisplayitems;
	@mysqli_data_seek($result, $startrow);
	$thisrow = $startrow;
    while (($row = @mysqli_fetch_array($result)) && ($thisrow < $stoprow)) {
	  $thisrow++;
	  $productid = $row["productid"];
      $productname = $row["name"];
      $description = $row["description"];
      $price = $row["price"];
	  $productstatus = $row["active"];

      echo "<table border=\"1\" cellspacing=\"0\" cellpadding=\"0\" align=\"center\" width=\"100%\"><tr>";
	  if (file_exists("$ashoppath/prodimg/$productid.gif")) echo "<td width=\"110\" align=\"center\" valign=\"middle\"><img src=\"../prodimg/$productid.gif\" width=\"100\" height=\"100\"></td>";
	  elseif (file_exists("$ashoppath/prodimg/$productid.jpg")) echo "<td width=\"110\" align=\"center\" valign=\"middle\"><img src=\"../prodimg/$productid.jpg\" width=\"100\" height=\"100\"></td>";
	  echo	"<td><table border=\"0\" cellspacing=\"0\" cellpadding=\"2\" align=\"center\" width=\"100%\"><tr>
	        <td><table border=\"0\" cellspacing=\"0\" cellpadding=\"2\" width=\"100%\"><tr><td width=\"90%\" class=\"productname\">$productid: $productname$retailstatus$wholesalestatus";
	  echo  "</td></tr></table></td></tr>
            <tr><td class=\"text\">$description</td></tr>
            <tr><td><span class=\"formtitle\">Price: </span>
            <span class=\"text\">".$currencysymbols[$ashopcurrency]["pre"]."$price ".$currencysymbols[$ashopcurrency]["post"]."</span></td></tr>
	  </td></tr></table></td></tr></table>";
	  $previousorderno = $ordernumber;
	  $previousprodid = $productid;
    }
	if ($numberofrows > 5) {
		echo "<table width=\"100%\" border=\"0\" cellpadding=\"5\" cellspacing=\"0\"><tr><td align=\"center\"><font face=\"Arial, Helvetica, sans-serif\" size=\"2\">";
		if ($numberofpages > 1) {
			echo "<b>Page: </b>";
			if ($resultpage > 1) {
				$previouspage = $resultpage-1;
				echo "<<<a href=\"editcatalogue.php?cat=$cat&resultpage=$previouspage\"><b>Previous</b></a>&nbsp;&nbsp;";
			}
			for ($i = 1; $i <= $numberofpages; $i++) {
				if ($i != $resultpage) echo "<a href=\"editcatalogue.php?cat=$cat&resultpage=$i\">";
				echo "$i";
				if ($i != $resultpage) echo "</a>";
				echo "&nbsp;&nbsp;";
			}
			if ($resultpage < $numberofpages) {
				$nextpage = $resultpage+1;
				echo "<a href=\"editcatalogue.php?cat=$cat&resultpage=$nextpage\"><b>Next</b></a>>>";
			}
		}
		echo " <form action=\"editcatalogue.php\" method=\"POST\" name=\"displayform\"><input type=\"hidden\" name=\"cat\" value=\"$cat\">Display: <select name=\"admindisplayitems\" onChange=\"displayform.submit();\"><option value=\"$numberofrows\">Select...</option><option value=\"5\">5</option><option value=\"10\">10</option><option value=\"20\">20</option><option value=\"40\">40</option><option value=\"$numberofrows\">All</option></select> items</form></td></tr></table>";
	}

@mysqli_close($db);
echo "</td>
</tr>
</table><br><br>
</center></td></tr></table>
$footer";
?>