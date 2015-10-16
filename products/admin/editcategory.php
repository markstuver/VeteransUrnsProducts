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

include "config.inc.php";
include "ashopfunc.inc.php";
include "checklogin.inc.php";
include "template.inc.php";
// Get language module...
include "language/$adminlang/editcategory.inc.php";

if ($userid != "1" && !$membershops) {
	header("Location: index.php");
	exit;
}

   $db = @mysqli_connect("$databaseserver", "$databaseuser", "$databasepasswd", "$databasename");
   $sql="SELECT * FROM category WHERE categoryid = '$cat'";
   $result = @mysqli_query($db, $sql);
   $categoryname = @mysqli_result($result, 0, "name");
   $categorydescr = @mysqli_result($result, 0, "description");
   $catowner = @mysqli_result($result, 0, "userid");
   $catlanguage = @mysqli_result($result, 0, "language");
   $grandparentcategory = @mysqli_result($result, 0, "grandparentcategoryid");
   $parentcategory = @mysqli_result($result, 0, "parentcategoryid");
   if ($grandparentcategory == $cat && $parentcategory == $cat) $istopcat = TRUE;
   else {
	   $istopcat = FALSE;
	   if ($parentcategory != $cat) $parentresult = @mysqli_query($db, "SELECT name FROM category WHERE categoryid = '$parentcategory'");
	   else $parentresult = @mysqli_query($db, "SELECT name FROM category WHERE categoryid = '$grandparentcategory'");
	   $parentcategoryname = @mysqli_result($parentresult, 0, "name");
   }
   $categorymemberclone = @mysqli_result($result, 0, "memberclone");
   $catlayout = @mysqli_result($result, 0, "productlayout");

if ($remove && $cat) {
	if ($yes) {
	   $sql="SELECT categoryid FROM category WHERE parentcategoryid=$cat OR grandparentcategoryid=$cat OR categoryid=$cat";
	   $result = @mysqli_query($db, $sql);
	   for ($i = 0; $i < @mysqli_num_rows($result); $i++) {
   		   $categoryid = @mysqli_result($result, $i, "categoryid");
		   $sql="DELETE FROM category WHERE categoryid=$categoryid";
		   $deleteresult = @mysqli_query($db, $sql);
	       $sql="SELECT product.productid FROM product, productcategory WHERE productcategory.categoryid=$categoryid AND product.productid=productcategory.productid";
           $subresult = @mysqli_query($db, $sql);
           for ($j = 0; $j < @mysqli_num_rows($subresult); $j++) {
			   $productid = @mysqli_result($subresult, $j, "productid");
			   @mysqli_query($db, "DELETE FROM productcategory WHERE categoryid='$categoryid' AND productid='$productid'");
			   $checkcategories = @mysqli_query($db, "SELECT * FROM productcategory WHERE productid=$productid");
			   if (!@mysqli_num_rows($checkcategories)) {
				   $sql="DELETE FROM product WHERE productid=$productid";
				   $deleteresult = @mysqli_query($db, $sql);
				   $sql="DELETE FROM productcategory WHERE productid=$productid";
				   $deleteresult = @mysqli_query($db, $sql);
				   $sql="DELETE FROM discount WHERE productid=$productid";
				   $deleteresult = @mysqli_query($db, $sql);
				   $sql="DELETE FROM unlockkeys WHERE productid=$productid";
				   $deleteresult = @mysqli_query($db, $sql);
				   $sql="DELETE FROM packages WHERE productid=$productid";
				   $deleteresult = @mysqli_query($db, $sql);
				   $sql="DELETE FROM zonerates WHERE productid=$productid";
				   $deleteresult = @mysqli_query($db, $sql);
				   $sql="DELETE FROM quantityrates WHERE productid=$productid";
				   $deleteresult = @mysqli_query($db, $sql);
				   $sql="DELETE FROM flagvalues WHERE productid=$productid";
				   $deleteresult = @mysqli_query($db, $sql);
				   $sql="DELETE FROM qtypricelevels WHERE productid=$productid";
				   $deleteresult = @mysqli_query($db, $sql);
				   $sql="DELETE FROM productinventory WHERE productid=$productid";
				   $deleteresult = @mysqli_query($db, $sql);
				   $paramresult = @mysqli_query($db, "SELECT * FROM parameters WHERE productid='$productid'");
				   while ($paramrow = @mysqli_fetch_array($paramresult)) {
					   $parameterid = $row["parameterid"];
					   @mysqli_query($db, "DELETE FROM parametervalues WHERE parameterid='$parameterid'");
					   @mysqli_query($db, "DELETE FROM customparametervalues WHERE parameterid='$parameterid'");
				   }
				   @mysqli_query($db, "DELETE FROM parameters WHERE productid='$productid'");
				   if (file_exists("$ashoppath/prodimg/$productid.gif")) unlink("$ashoppath/prodimg/$productid.gif");
				   if (file_exists("$ashoppath/prodimg/b$productid.gif")) unlink("$ashoppath/prodimg/b$productid.gif");
				   if (file_exists("$ashoppath/prodimg/m$productid.gif")) unlink("$ashoppath/prodimg/m$productid.gif");
				   if (file_exists("$ashoppath/prodimg/p$productid.gif")) unlink("$ashoppath/prodimg/p$productid.gif");
				   if (file_exists("$ashoppath/prodimg/$productid.jpg")) unlink("$ashoppath/prodimg/$productid.jpg");
				   if (file_exists("$ashoppath/prodimg/b$productid.jpg")) unlink("$ashoppath/prodimg/b$productid.jpg");
				   if (file_exists("$ashoppath/prodimg/m$productid.jpg")) unlink("$ashoppath/prodimg/m$productid.jpg");
				   if (file_exists("$ashoppath/prodimg/p$productid.jpg")) unlink("$ashoppath/prodimg/p$productid.jpg");
				   $picturenumber = 1;
				   while (file_exists("$ashoppath/prodimg/{$productid}_{$picturenumber}.gif") || file_exists("$ashoppath/prodimg/{$productid}_{$picturenumber}.jpg")) {
					   if (file_exists("$ashoppath/prodimg/{$productid}_{$picturenumber}.gif")) unlink("$ashoppath/prodimg/{$productid}_{$picturenumber}.gif");
					   if (file_exists("$ashoppath/prodimg/b{$productid}_{$picturenumber}.gif")) unlink("$ashoppath/prodimg/b{$productid}_{$picturenumber}.gif");
					   if (file_exists("$ashoppath/prodimg/m{$productid}_{$picturenumber}.gif")) unlink("$ashoppath/prodimg/m{$productid}_{$picturenumber}.gif");
					   if (file_exists("$ashoppath/prodimg/p{$productid}_{$picturenumber}.gif")) unlink("$ashoppath/prodimg/p{$productid}_{$picturenumber}.gif");
					   if (file_exists("$ashoppath/prodimg/{$productid}_{$picturenumber}.jpg")) unlink("$ashoppath/prodimg/{$productid}_{$picturenumber}.jpg");
					   if (file_exists("$ashoppath/prodimg/b{$productid}_{$picturenumber}.jpg")) unlink("$ashoppath/prodimg/b{$productid}_{$picturenumber}.jpg");
					   if (file_exists("$ashoppath/prodimg/m{$productid}_{$picturenumber}.jpg")) unlink("$ashoppath/prodimg/m{$productid}_{$picturenumber}.jpg");
					   if (file_exists("$ashoppath/prodimg/p{$productid}_{$picturenumber}.jpg")) unlink("$ashoppath/prodimg/p{$productid}_{$picturenumber}.jpg");
					   $picturenumber++;
				   }
				   if (is_dir("$ashoppath/previews/$productid")) {
					   $findfile = opendir("$ashoppath/previews/$productid");
					   while (false !== ($foundfile = readdir($findfile))) { 
						   if($foundfile && $foundfile != "." && !strstr($foundfile,"..")) unlink("$ashoppath/previews/$productid/$foundfile");
						   unset($foundfile);
					   }
					   closedir($findfile);
					   rmdir("$ashoppath/previews/$productid");
					   unset($findfile);
				   }
				   $filesresult = @mysqli_query($db, "SELECT * FROM productfiles WHERE productid='$productid'");
				   while ($filesrow = @mysqli_fetch_array($filesresult)) {
					   $filesresult2 = @mysqli_query($db, "SELECT * FROM productfiles WHERE fileid='{$filesrow["fileid"]}' AND productid!='$productid'");
					   if (!@mysqli_num_rows($filesresult2)) {
						   if (file_exists("$ashopspath/products/{$filesrow["fileid"]}")) unlink("$ashopspath/products/{$filesrow["fileid"]}");
					   }
					   @mysqli_query($db, "DELETE FROM productfiles WHERE productid='$productid'");
				   }
				   $updatesresult = @mysqli_query($db, "SELECT * FROM updates WHERE productid='$productid'");
				   while ($updatesrow = @mysqli_fetch_array($updatesresult)) if (file_exists("$ashopspath/updates/$productid")) unlink("$ashopspath/updates/$productid");
				   @mysqli_query($db, "DELETE FROM updates WHERE productid='$productid'");

				   // If there are copies of this product, the first copy should become the original...
				   $copyofresult = @mysqli_query($db, "SELECT * FROM product WHERE copyof='$productid' ORDER BY productid ASC LIMIT 1");
				   $firstcopy = @mysqli_result($copyofresult, 0, "productid");
				   @mysqli_query($db, "UPDATE product SET copyof=NULL WHERE productid='$firstcopy'");
				   @mysqli_query($db, "UPDATE product SET copyof='$firstcopy' WHERE copyof='$productid'");
			   }
		   }
	   }
	   header("Location: editcatalogue.php");
	}
    elseif ($no) header("Location: editcatalogue.php");
    else echo "$header
        <div class=\"heading\">".REMOVEACATEGORY."</div><table cellpadding=\"10\" align=\"center\"><tr><td>
        <p>".AREYOUSUREREMOVE." $categoryname ".ANDALLPRODUCTS."</p>
		<form action=\"editcategory.php\" method=\"post\">
		<table align=\"center\" width=\"440\" border=\"0\" cellspacing=\"0\" cellpadding=\"5\">
		<tr>
        <td width=\"100%\" align=\"center\" valign=\"top\"><input type=\"submit\" name=\"yes\" value=\"".YES."\">
		<input type=\"submit\" name=\"no\" value=\"".NO."\"></td>
		</tr></table><input type=\"hidden\" name=\"cat\" value=\"$cat\">
		<input type=\"hidden\" name=\"remove\" value=\"True\"></form>
        </center></td></tr></table>$footer";
} 

elseif (!$name && !$description) {
	// Generate Digital Mall member list if needed...
	if ($membershops && $userid == "1" && file_exists("$ashoppath/members/index.php") && $digitalmall != "OFF") {
		$memberlist = "<select name=\"memberid\"><option value=\"1\"";
		if ($catowner == "1") $memberlist .= " selected";
		$memberlist .= ">".ADMINISTRATOR;
		$result = @mysqli_query($db, "SELECT * FROM user WHERE userid>1 ORDER BY shopname");
		while ($row = @mysqli_fetch_array($result)) {
			$memberlist .= "<option value=\"{$row["userid"]}\"";
			if ($catowner == $row["userid"]) $memberlist .= " selected";
			$memberlist .= ">{$row["shopname"]}";
		}
		$memberlist .= "</select>";
	} else $memberlist = "";

	// Generate language list if needed...
	if ($istopcat) {
		$languagelist = "<select name=\"nlanguage\"><option value=\"any\"";
		if ($catlanguage == "any") $languagelist .= " selected";
		$languagelist .= ">".ANY;
		$findfile = opendir("$ashoppath/language");
		while ($foundfile = readdir($findfile)) {
			if($foundfile && $foundfile != "." && $foundfile != ".." && is_dir("$ashoppath/language/$foundfile") && !strstr($foundfile, "CVS") && substr($foundfile, 0, 1) != "_" && file_exists("$ashoppath/language/$foundfile/lang.cfg.php")) {
				$fp = fopen ("$ashoppath/language/$foundfile/lang.cfg.php","r");
				while (!feof ($fp)) {
					$fileline = fgets($fp, 4096);
					if (strstr($fileline,"\$langname")) $langnamestring = $fileline;
				}
				fclose($fp);
				eval ($langnamestring);
				$languages["$foundfile"] = $langname;
			}
		}
		if (is_array($languages)) {
			natcasesort($languages);
			foreach ($languages as $langmodule=>$langname) {
				$languagelist .= "<option value=\"$langmodule\"";
				if ($langmodule == $catlanguage) $languagelist .= " selected";
				$languagelist .= ">$langname</option>";
			}
		}
	}

	echo "$header
	  	<script language=\"JavaScript\">
		function uploadmessage() 
		{
		  if (document.productform.importfile.value != '') {
			  w = window.open('uploadmessage.html','_blank','toolbar=no,location=no,width=350,height=150');
		  }
	    }
        function closemessage()
        {
       	  if (typeof w != 'undefined') w.close();
        }
		</script>
        <div class=\"heading\">".EDITACATEGORY."</div><table cellpadding=\"10\" align=\"center\"><tr><td>
        <p>".EDITTHECATEGORY." $categoryname ".BYENTERINGNEWDATA."</p>
        <form action=\"editcategory.php\" method=\"post\">
		<table width=\"500\" border=\"0\" cellspacing=\"0\" cellpadding=\"5\">";
		if ($parentcategory != $cat || $grandparentcategory != $cat) echo "<tr><td align=\"right\" class=\"formlabel\">".TYPE.":</td><td class=\"formlabel\"><input type=\"radio\" name=\"cattype\" value=\"top\"> ".TOPCATEGORY." <input type=\"radio\" name=\"cattype\" value=\"\" checked> ".SUBCATEGORYTO." $parentcategoryname</td></tr>";
        echo "
        <tr><td align=\"right\" class=\"formlabel\">".NAME.":</td><td><input type=\"text\" name=\"name\" size=\"35\" value=\"".htmlentities(stripslashes($categoryname), ENT_QUOTES)."\"></td></tr>";
		if ($userid == "1" && file_exists("$ashoppath/members/index.php") && $digitalmall != "OFF") {
			if ($membershops && $memberlist) echo "<tr><td align=\"right\" class=\"formlabel\">".OWNER.":</td><td>$memberlist</td></tr>";
			else echo "<input type=\"hidden\" name=\"memberid\" value=\"1\">";
			if ($catowner == "1") {
				echo "<tr><td>&nbsp;</td><td class=\"formlabel\"><input type=\"checkbox\" name=\"memberclone\"";
				if ($categorymemberclone) echo " checked";
				echo "> ".MAKETHISCATEGORYAVAILABLE;
			}
		}
		if ($istopcat) echo "<tr><td align=\"right\" class=\"formlabel\">".LANGUAGE.":</td><td>$languagelist</td></tr>";
		echo "
		<tr><td align=\"right\" class=\"formlabel\"><font face=\"Arial, Helvetica, sans-serif\">".PRODUCTLAYOUT.":</font></td><td>
		<select name=\"layout\"><option value=\"0\""; if ($catlayout == "0") echo " selected"; echo ">".USEDEFAULT."</option><option value=\"1\""; if ($catlayout == "1") echo " selected"; echo ">".STANDARD."</option><option value=\"2\""; if ($catlayout == "2") echo " selected"; echo ">".CONDENSED."</option><option value=\"3\""; if ($catlayout == "3") echo " selected"; echo ">".LINKS."</option></select>
		</td></tr>
        <tr><td align=\"right\" class=\"formlabel\">".DESCRIPTION.":</td><td class=\"formlabel\"><textarea name=\"description\" cols=\"45\" rows=\"3\">$categorydescr</textarea></td></tr>
        <tr><td>&nbsp;</td><td align=\"right\"><input type=\"hidden\" name=\"cat\" value=\"$cat\"><input type=\"submit\" value=\"".SUBMIT."\"></td></tr></form></table>
		</table></td></tr></table></center></td></tr></table>$footer";
} else {
   if ($memberid) $user = $memberid;
   else $user = $userid;
   if ($memberclone == "on") $memberclone = 1;
   else $memberclone = NULL;
   if (empty($layout) || !is_numeric($layout)) $layout = 0;
   $sql="UPDATE category SET name='$name', description='$description', userid='$user', language='$nlanguage', memberclone='$memberclone', productlayout='$layout'";
   if ($cattype == "top") $sql .= ", parentcategoryid=categoryid, grandparentcategoryid=categoryid";
   $sql .= " WHERE categoryid='$cat'";
   $result = @mysqli_query($db, $sql);

   // Change the owner of this category and all subcategories and products it contains...
   if ($memberid && $catowner != $memberid) {
	   $result = @mysqli_query($db, "SELECT * FROM productcategory WHERE categoryid='$cat'");
	   while ($row = @mysqli_fetch_array($result)) @mysqli_query($db, "UPDATE product SET userid='$memberid' WHERE productid='{$row["productid"]}'");
	   $result = @mysqli_query($db, "SELECT * FROM category WHERE grandparentcategoryid='$cat' OR parentcategoryid='$cat'");
	   while ($row = @mysqli_fetch_array($result)) {
		   @mysqli_query($db, "UPDATE category SET userid='$memberid' WHERE categoryid='{$row["categoryid"]}'");
		   $result2 = @mysqli_query($db, "SELECT * FROM productcategory WHERE categoryid='{$row["categoryid"]}'");
		   while ($row2 = @mysqli_fetch_array($result2)) @mysqli_query($db, "UPDATE product SET userid='$memberid' WHERE productid='{$row2["productid"]}'");
	   }
   }

   header("Location: editcatalogue.php");
}
?>