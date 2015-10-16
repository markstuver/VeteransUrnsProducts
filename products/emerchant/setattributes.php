<?php
include "../admin/config.inc.php";
include "../admin/ashopfunc.inc.php";
include "checklogin.inc.php";
include "emfunc.inc.php";
$pagetitle = "Set Product Attributes";
include "template.inc.php";
  
// Open database...
$db = @mysqli_connect("$databaseserver", "$databaseuser", "$databasepasswd", "$databasename");

  if ($setattribute) {
	// Get any parameter values and store in basket cookiestring...
	$parameterstring = "";
	$sql = "SELECT * FROM parameters WHERE productid=$item ORDER BY parameterid";
	$result = @mysqli_query($db, "$sql");
	if (@mysqli_num_rows($result)) {
		for ($i = 0; $i < @mysqli_num_rows($result); $i++) {
			$parameterid = @mysqli_result($result, $i, "parameterid");
			eval ("\$thisparameter = \$parameter$parameterid;");
			$subresult = @mysqli_query($db, "SELECT * FROM parametervalues WHERE parameterid='$parameterid'", $db);
			if (!@mysqli_num_rows($subresult)) {
				@mysqli_query($db, "INSERT INTO customparametervalues (parameterid, value) VALUES ('$parameterid', '$thisparameter')", $db);
				if (@mysqli_affected_rows($db) == 1) $thisparameter = @mysqli_insert_id($db);
			}
			$parameterstring .= $thisparameter."b";
		}
	}

	$productwithattributes = "{$quantity}b$parameterstring{$item}a";

   echo "<html><head>\n<script language=\"JavaScript\">
	opener.document.productselection.products.value = '$products$productwithattributes';
	opener.document.productselection.attributeset.value = 'true';
	opener.document.productselection.submit();
	this.close();
	</script>\n</head></html>";
	exit;
  }

  // Get the products name and price from the database...
  $sql="SELECT name FROM product WHERE productid='$item'";
  $result = @mysqli_query($db, "$sql");
  if (@mysqli_num_rows($result) == 0) {
	echo "<html><head><title>Error! The selected item is not in the catalogue!</title></head>
	<body bgcolor=\"$bgcolor\" text=\"$textcolor\" link=\"$linkcolor\"><font face=\"$font\"><h2>Item Not Found</h2><h3>The selected item is not found in the catalogue.</h3></font></body></html>";
    exit();
  } else {
	$name = @mysqli_result($result, 0, "name");
    echo "
	<html>
	<head><title>Set attributes for Qty: $quantity Item: $name</title>
		<script language=\"JavaScript\">
	     function showlicense(product)
	     {
		    window.open(\"license.php?\"+product,\"_blank\",\"toolbar=no, location=no, scrollbars=yes, width=500, height=600\")
	     }
      </script>
	</head>
	<body bgcolor=\"$bgcolor\" text=\"$textcolor\" link=\"$linkcolor\">
	<center>
	<font face=\"$font\" size=\"2\">";

	// Check if there are parameters for the product and list them...
	$sql = "SELECT * FROM parameters WHERE productid=$item ORDER BY parameterid";
	$result = @mysqli_query($db, "$sql");
	if (@mysqli_num_rows($result)) {
		echo "<b>Select Options</b><br>
<table>
<tr><td>\"$name\", Qty: $quantity<br>
</td></tr></table>
	<form action=\"setattributes.php\" method=\"post\">";
		for ($i = 0; $i < @mysqli_num_rows($result); $i++) {
			$parameterid = @mysqli_result($result, $i, "parameterid");
			$caption = @mysqli_result($result, $i, "caption");
			$subresult = @mysqli_query($db, "SELECT * FROM parametervalues WHERE parameterid=$parameterid ORDER BY valueid");
			if (@mysqli_num_rows($subresult)) {
				echo "<table width=\"100%\" cellpadding=\"0\" cellspacing=\"0\" border=\"0\"><tr><td valign=\"top\" align=\"center\"><font size=\"1\">$caption: <select name=\"parameter$parameterid\">";
				for ($j = 0; $j < @mysqli_num_rows($subresult); $j++) {
					$valueid = @mysqli_result($subresult, $j, "valueid");
					$value = @mysqli_result($subresult, $j, "value");
					echo "<option value=\"$valueid\">$value";
				}
				echo "</select></font></td></tr></table>";
			} else echo "<table width=\"80%\" cellpadding=\"0\" cellspacing=\"0\" border=\"0\"><tr><td align=\"left\">$caption:<br><input type=\"text\" size=\"30\" name=\"parameter$parameterid\"></td></tr></table><br>";
		}
	} else echo "<script language=\"javascript\">window.close();</script>";

	echo "<input type=\"hidden\" name=\"item\" value=\"$item\">
	    <input type=\"hidden\" name=\"quantity\" value=\"$quantity\">
	    <input type=\"hidden\" name=\"item\" value=\"$item\">
		<input type=\"hidden\" name=\"products\" value=\"$products\"><br>
    <center><input type=\"submit\" name=\"setattribute\" value=\"Submit\">
	<input type=\"button\" value=\"Cancel/Exit\" onClick=\"window.close()\"></center>
	</form>
	</font></center>
	</body>
	</html>";
  }
@mysqli_close($db);
?>