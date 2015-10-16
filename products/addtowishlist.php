<?php
// AShop
// Copyright 2014 - AShop Software - http://www.ashopsoftware.com
//
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; either version 2
// of the License, or (at your option) any later version.
//
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with this program; if not, see: http://www.gnu.org/licenses/.

include "admin/config.inc.php";
include "admin/ashopconstants.inc.php";
include "admin/ashopfunc.inc.php";

// Initialize variables...
if (!isset($lang)) $lang = "";
if (!isset($usethemebuttons)) $usethemebuttons = "";
if (!isset($usethemetemplates)) $usethemetemplates = "";
if (!isset($price)) $price = "";
if (!isset($confirmaddtocart)) $confirmaddtocart = "";
if (!isset($yesplease_x)) $yesplease_x = "";
if (!isset($noplease_x)) $noplease_x = "";
if (!isset($resultpage)) $resultpage = 1;
if (!isset($attribute)) $attribute = "";

// Validate return URL...
if (!empty($returnurl) && !ashop_is_url($returnurl)) $returnurl = "";

// Validate customer session cookie...
if (!ashop_is_md5($_COOKIE["customersessionid"])) $_COOKIE["customersessionid"] = "";

// Determine if a product ID should be passed back to the catalogue/search script...
if (strstr($item, "s")) {
	$returnproductid = TRUE;
	$item = str_replace("s","",$item);
} else $returnproductid = FALSE;

// Validate variables...
if (isset($item) && !is_numeric($item)) $item = 0;
if (!empty($confirmaddtocart) && $confirmaddtocart != "no" && $confirmaddtocart != "yes") $confirmaddtocart = "";
if (isset($_GET["confirmaddtocart"]) && $_GET["confirmaddtocart"] != "no" && $_GET["confirmaddtocart"] != "yes") $_GET["confirmaddtocart"] = "";
if (isset($_POST["confirmaddtocart"]) && $_POST["confirmaddtocart"] != "no" && $_POST["confirmaddtocart"] != "yes") $_POST["confirmaddtocart"] = "";
if (isset($shop) && !is_numeric($shop)) $shop = 1;

// Apply selected theme...
$buttonpath = "";
if ($ashoptheme && $ashoptheme != "none" && file_exists("$ashoppath/themes/$ashoptheme/theme.cfg.php")) include "themes/$ashoptheme/theme.cfg.php";
if ($usethemebuttons == "true") $buttonpath = "themes/$ashoptheme/";
if ($lang && is_array($themelanguages)) {
	if (!in_array("$lang",$themelanguages)) unset($lang);
}

// Include language file...
if (!isset($lang) || !preg_match("/[a-z]+/", $lang) || strlen($lang) > 2) $lang = $defaultlanguage;
include "language/$lang/wishlist.inc.php";

// Connect to database...
$db = @mysqli_connect("$databaseserver", "$databaseuser", "$databasepasswd", "$databasename");

// Get the products name from the database...
$productresult = @mysqli_query($db, "SELECT * FROM product WHERE productid='$item'");
$copyof = @mysqli_result($productresult, 0, "copyof");
if (is_numeric($copyof)) $item = $copyof;
$name = @mysqli_result($productresult, 0, "name");

// Get parameters for this product...
$sql = "SELECT * FROM parameters WHERE productid='$item' ORDER BY parameterid";
$paramresult = @mysqli_query($db, "$sql");
if (@mysqli_num_rows($paramresult)) $checkparams = FALSE;
else $checkparams = TRUE;

  // Check if confirmation should be bypassed...
  if (isset($_GET["confirmaddtocart"])) $confirmaddtocart = $_GET["confirmaddtocart"];
  if (isset($_POST["confirmaddtocart"])) $confirmaddtocart = $_POST["confirmaddtocart"];
  if ($confirmaddtocart == "no") {
	  if ($checkparams) {
		  unset($bypassnoconfirm);
		  $yesplease_x = "1";
	  } else $bypassnoconfirm = "1";
  }
  
  if ($yesplease_x) {
	// Get any parameter values and store in basket cookiestring...
	$parameterstring = "";
	$type = "";
	if (@mysqli_num_rows($paramresult)) {
		for ($i = 0; $i < @mysqli_num_rows($paramresult); $i++) {
			$parameterid = @mysqli_result($paramresult, $i, "parameterid");
			$parametername = strtolower(@mysqli_result($paramresult, $i, "caption"));
			if ($parameterid == $selectedattribute[0]) $thisparameter = $selectedattribute[1];
			else eval ("\$thisparameter = \$parameter$parameterid;");
			$subresult = @mysqli_query($db, "SELECT * FROM parametervalues WHERE parameterid='$parameterid'");
			if (!@mysqli_num_rows($subresult)) {
				if (file_exists("$ashoppath/admin/filters/$parametername.inc.php")) {
					$filter_attributeid = $parameterid;
					$filter_attributename = @mysqli_result($paramresult, $i, "caption");
					$filter_productid = $item;
					$filter_attributevalue = $thisparameter;
					include "$ashoppath/admin/filters/$parametername.inc.php";
					$parameterid = $filter_attributeid;
					$item = $filter_productid;
					$thisparameter = $filter_attributevalue;
				}
				$now = time()+$timezoneoffset;
				$thisparameter = str_replace("'","&#39;",$thisparameter);
				$thisparameter = str_replace("\"","&quot;",$thisparameter);
				@mysqli_query($db, "INSERT INTO customparametervalues (parameterid, value, timestamp) VALUES ('$parameterid', '$thisparameter', '$now')");
				if (@mysqli_affected_rows($db) == 1) $thisparameter = @mysqli_insert_id($db);
			} else if (@mysqli_num_rows($subresult) > 1) $type .= $thisparameter."|";
			$parameterstring .= $thisparameter."b";
		}
	}

	// Add the item to this customer's wishlist...
	if (!empty($_COOKIE["customersessionid"])) {
		$customerresult = @mysqli_query($db, "SELECT customerid FROM customer WHERE sessionid='{$_COOKIE["customersessionid"]}'");
		$customerid = @mysqli_result($customerresult,0,"customerid");
		if (is_numeric($customerid)) {
			$wishlistresult = @mysqli_query($db, "SELECT * FROM savedcarts WHERE customerid='$customerid'");
			if (@mysqli_num_rows($wishlistresult)) {
				$wishlist = @mysqli_result($wishlistresult,0,"productstring");
				$newwishlistitem = "1b{$parameterstring}{$item}a";
				if (!strstr($wishlist,"a".$newwishlistitem) && substr($wishlist,0,strlen($newwishlistitem)) != $newwishlistitem) {
					$wishlist .= "1b{$parameterstring}{$item}a";
					@mysqli_query($db, "UPDATE savedcarts SET productstring='$wishlist' WHERE customerid='$customerid'");
				}
			} else {
				$wishlist = "1b{$parameterstring}{$item}a";
				@mysqli_query($db, "INSERT INTO savedcarts (customerid, cartname, productstring) VALUES ('$customerid','wishlist','$wishlist')");
			}
		}
	}
	if ($returnproductid) $productidstring = "&product=$item";
	if ($confirmaddtocart == "no" && !$bypassnoconfirm && !$oldpopupstyle) {
		if ((isset($returnurl) && $returnurl == "") || substr($returnurl, 0, 1) == "?") if (file_exists("$ashoppath/index.php")) $returnurl = "index.php".$returnurl;
		if ($returnurl) {
			$returnurl = str_replace("|","&",$returnurl);
			if (ini_get('magic_quotes_gpc')) $returnurl = stripslashes($returnurl);
			if (strstr($returnurl,"msg=")) {
				$returnurlpart1 = substr($returnurl,0,strpos($returnurl,"?"));
				$returnurlpart2 = "?";
				$fields = explode("&",substr($returnurl,strpos($returnurl,"?")+1));
				$fieldnumber = 0;
				if (is_array($fields)) foreach ($fields as $fieldpart) {
					$fieldpartarray = explode("=",$fieldpart);
					$fieldname = $fieldpartarray[0];
					$fieldvalue = $fieldpartarray[1];
					if ($fieldname != "msg") {
						if ($fieldnumber == "0") $returnurlpart2 .= "$fieldname=$fieldvalue";
						else $returnurlpart2 .= "&$fieldname=$fieldvalue";
						$fieldnumber++;
					}
				}
				$returnurl = $returnurlpart1.$returnurlpart2;
			}
			if (strstr($returnurl,"?")) {
				if (strstr($SERVER_SOFTWARE, "IIS")) {
					echo "<html><head><meta http-equiv=\"Refresh\" content=\"0; URL=$returnurl&msg=".urlencode($name)." ".urlencode(HASBEENADDED)."$productidstring\"></head></html>";
					exit;
				} else header("Location: $returnurl&msg=".urlencode($name)." ".HASBEENADDED.$productidstring);
			} else {
				if (strstr($SERVER_SOFTWARE, "IIS")) {
					echo "<html><head><meta http-equiv=\"Refresh\" content=\"0; URL=$returnurl?msg=".urlencode($name)." ".HASBEENADDED."$productidstring\"></head></html>";
					exit;
				} else header("Location: $returnurl?msg=".urlencode($name)." ".HASBEENADDED.$productidstring);
			}
		} else if ($searchstring) {
			if (strstr($SERVER_SOFTWARE, "IIS")) {
					echo "<html><head><meta http-equiv=\"Refresh\" content=\"0; URL=index.php?cat=$cat&exp=$exp&searchstring=$searchstring&shop=$shop&msg=".urlencode($name)." ".HASBEENADDED."$productidstring\"></head></html>";
					exit;
			} else header("Location: index.php?cat=$cat&exp=$exp&searchstring=$searchstring&shop=$shop&msg=".urlencode($name)." ".HASBEENADDED.$productidstring);
		} else {
			if (strstr($SERVER_SOFTWARE, "IIS")) {
				echo "<html><head><meta http-equiv=\"Refresh\" content=\"0; URL=index.php?cat=$cat&exp=$exp&shop=$shop&resultpage=$resultpage&msg=".urlencode($name)." ".HASBEENADDED."$productidstring\"></head></html>";
				exit;
			} else {
				header("Location: index.php?cat=$cat&exp=$exp&shop=$shop&resultpage=$resultpage&msg=".urlencode($name)." ".HASBEENADDED.$productidstring);
			}
		}
		exit;
	}

    echo "<html><head>\n".CHARSET."\n<script language=\"JavaScript\">";
	$name = str_replace("'", "&#039;", $name);
	echo "
	currentlocation = opener.window.location.href;
	i = currentlocation.indexOf('#');
	if (i>0) opener.window.location.href=currentlocation.substring(0,i)+'#cart';
	else opener.window.location.href=currentlocation+'#cart';
	if (opener.document.getElementById('confirmmsg')) opener.document.getElementById('confirmmsg').innerHTML = '$name ".HASBEENADDED."';
	this.close();
	</script>\n</head></html>";
	exit;
  }

  if ($noplease_x) {
	echo "<html><head><script
language=\"javascript\">this.close();</script></head></html>";
  }

  if (@mysqli_num_rows($productresult) == 0) {
	echo "<html><head><title>".ERROR."</title>\n".CHARSET."<style type=\"text/css\"><!-- .fontsize1 { font-size: {$fontsize1}px}\n.fontsize2 { font-size: {$fontsize2}px}\n.fontsize3 { font-size: {$fontsize3}px}--></style></head>
	<body bgcolor=\"$bgcolor\" text=\"$textcolor\" link=\"$linkcolor\"><font face=\"$font\"><h2><span class=\"fontsize3\">".NOTFOUND."</span></h2><h3><span class=\"fontsize2\">".NOTINCATALOGUE."</span></h3></font></body></html>";
    exit();
  } else {
	$quantity = 1;
    echo "
	<html>
	<head><title>".ADD.": $name ".TOWISHLIST."</title>".CHARSET."
	<style type=\"text/css\">
	<!--
		  .fontsize1 { font-size: {$fontsize1}px}
	      .fontsize2 { font-size: {$fontsize2}px}
		  .fontsize3 { font-size: {$fontsize3}px}
	-->
	</style>
	<link rel=\"stylesheet\" href=\"includes/ashopcss.inc.php\" type=\"text/css\" />
	</head>
	<body bgcolor=\"$bgcolor\" text=\"$textcolor\" link=\"$linkcolor\">
	<center>
	<font face=\"$font\" size=\"3\"><span class=\"fontsize3\">";

	// Check if there are parameters for the product and list them...
	if (@mysqli_num_rows($paramresult)) {
		echo "<b>".SELECTOPTIONS."</b><br>
<table>
<tr><td><span class=\"fontsize2\">
\"$name\"
</span></td></tr></table>
	<form action=\"addtowishlist.php\" method=\"get\">";
		for ($i = 0; $i < @mysqli_num_rows($paramresult); $i++) {
			$parameterid = @mysqli_result($paramresult, $i, "parameterid");
			$caption = @mysqli_result($paramresult, $i, "caption");
			$inputrows = @mysqli_result($paramresult, $i, "inputrows");
			$subresult = @mysqli_query($db, "SELECT * FROM parametervalues WHERE parameterid=$parameterid ORDER BY valueid");
			if (@mysqli_num_rows($subresult) > 1) {
				echo "<table width=\"100%\" cellpadding=\"0\" cellspacing=\"0\" border=\"0\"><tr><td valign=\"top\" align=\"center\"><font size=\"2\"><span class=\"fontsize2\">$caption: ";
				if ($parameterid != $selectedattribute[0]) echo "<select name=\"parameter$parameterid\" style=\"font-size: {$fontsize2}px\">";
				else echo "<input type=\"hidden\" name=\"parameter$parameterid\" value=\"{$selectedattribute[1]}\">";
				for ($j = 0; $j < @mysqli_num_rows($subresult); $j++) {
					$valueid = @mysqli_result($subresult, $j, "valueid");
					$value = @mysqli_result($subresult, $j, "value");
					$value = substr($value,0,30);
					if ($parameterid != $selectedattribute[0]) echo "<option value=\"$valueid\">$value";
					else if ($valueid == $selectedattribute[1]) echo $value;
				}
				echo "</span></font></select></td></tr></table><br>";
			} else if (@mysqli_num_rows($subresult) == 1) {
				$valueid = @mysqli_result($subresult, 0, "valueid");
				echo "<input name=\"parameter$parameterid\" type=\"hidden\" value=\"$valueid\">";
			} else {
				if ($inputrows <= 1) echo "<table width=\"80%\" cellpadding=\"0\" cellspacing=\"0\" border=\"0\"><tr><td align=\"left\"><font size=\"2\"><span class=\"fontsize2\">$caption:<br><input type=\"text\" size=\"30\" name=\"parameter$parameterid\"></span></font></td></tr></table><br>";
				else echo "<table width=\"80%\" cellpadding=\"0\" cellspacing=\"0\" border=\"0\"><tr><td align=\"left\"><font size=\"2\"><span class=\"fontsize2\">$caption:<br><textarea name=\"parameter$parameterid\" cols=\"30\" rows=\"$inputrows\"></textarea></span></font></td></tr></table><br>";
			}
		}
	} else {
		echo "<b>".ADD."</b><br>";
		if ($displaylicenseerror) echo "<font face=\"$font\" size=\"2\" color=\"$alertcolor\"><span class=\"fontsize2\">".TOCOMPLETEORDER."</span></font>";
		echo "
		<table>
<tr><td align=\"left\" valign=\"top\"><span class=\"fontsize2\">
\"$name\"
</span></td></tr></table>
	<form action=\"addtowishlist.php\" method=\"get\">";
	}
	if ($resultpage) echo "<input type=\"hidden\" name=\"resultpage\" value=\"$resultpage\">";
	echo "<input type=\"hidden\" name=\"item\" value=\"$item\">
	    <input type=\"hidden\" name=\"quantity\" value=\"$quantity\">
	    <input type=\"hidden\" name=\"confirmaddtocart\" value=\"$confirmaddtocart\">
    <center><input type=\"image\" border=\"0\" class=\"ashopbutton\" name=\"yesplease\" src=\"{$buttonpath}images/wishlist-$lang.png\" alt=\"".PUTINCART."\">
	<input type=\"image\" border=\"0\" class=\"ashopbutton\" name=\"noplease\" src=\"{$buttonpath}images/cancel-$lang.png\" alt=\"".CANCEL."\"></center>
	</form></span>
	</font></center>
	</body>
	</html>";
  }
?>