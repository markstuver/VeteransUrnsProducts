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

unset($shop);
unset($userid);
include "admin/config.inc.php";
include "admin/ashopfunc.inc.php";

// Apply selected theme...
$buttonpath = "";
$templatepath = "/templates";
if ($ashoptheme && $ashoptheme != "none" && file_exists("$ashoppath/themes/$ashoptheme/theme.cfg.php")) include "themes/$ashoptheme/theme.cfg.php";
if ($usethemebuttons == "true") $buttonpath = "themes/$ashoptheme/";
if ($usethemetemplates == "true") $templatepath = "/themes/$ashoptheme";
if ($lang && is_array($themelanguages)) {
	if (!in_array("$lang",$themelanguages)) unset($lang);
}

// Include language file...
if (!isset($lang) || !preg_match("/[a-z]+/", $lang) || strlen($lang) > 2) $lang = $defaultlanguage;
include "language/$lang/deliver.inc.php";

// Get the path to the logo image for error messages...
if ($ashopuser && file_exists("$ashoppath/members/files/$ashopuser/logo.gif")) $ashoplogopath = "$ashoppath/members/files/$ashopuser";
else $ashoplogopath = "images";

// Check if a mobile device is being used...
$device = ashop_mobile();

if (!$password || !$email) {

	if ($lang != $defaultlanguage && file_exists("$ashoppath$templatepath/delivery-$lang.html")) ashop_showtemplateheader("$ashoppath$templatepath/delivery-$lang.html");
	else ashop_showtemplateheader("$ashoppath$templatepath/delivery.html");

	echo "<br><center><span class=\"ashopdeliveryheader\">".DELIVERY."</span>
<p><span class=\"ashopdeliverytext2\">".DOWNLOADPRODUCTS."<br><br>".PROBLEMS."</span></p>
<table class=\"ashopdeliverycontactframe\">";
  if ($ashopphone) echo "<tr><td align=\"right\" width=\"150\" nowrap><span class=\"ashopdeliverytext1\">".PHONE."</span></td><td align=\"left\"><span class=\"ashopdeliverytext2\">$ashopphone</span></td></tr>";
  if ($ashopemail) {
	  $safedisplayemail = str_replace("@","<img src=\"images/at.gif\" align=\"absbottom\">",$ashopemail);
	  echo "<tr><td align=\"right\"><span class=\"ashopdeliverytext1\">".EMAILASHOP."</span></td><td align=\"left\"><span class=\"ashopdeliverytext2\">$safedisplayemail</span></td></tr>";
  }
  if ($ashopaddress) echo "<tr><td align=\"right\" valign=\"top\"><span class=\"ashopdeliverytext1\">".MAIL."</span></td>
<td align=\"left\"><span class=\"ashopdeliverytext2\">$ashopaddress</span></td></tr>";
echo "</table><br />
    <form method=\"post\" action=\"deliver.php\"";
if ($device == "mobile") echo " data-ajax=\"false\"";
echo ">
    <table width=\"350\" border=\"0\" cellspacing=\"0\" cellpadding=\"0\">
      <tr> 
        <td align=\"left\"><span class=\"ashopdeliverytext2\">".EMAILLOGIN.":</span></td>
        <td align=\"left\"><span class=\"ashopdeliverytext2\"><input type=\"text\" name=\"email\" size=\"30\"></span></td>";
if ($device != "mobile") echo "
        <td>&nbsp;</td>";
echo "      </tr>
      <tr> 
        <td align=\"left\"><span class=\"ashopdeliverytext2\">".PASSWORD.":</span></td>
        <td align=\"left\"><span class=\"ashopdeliverytext2\"><input type=\"password\" name=\"password\" size=\"20\"></span></td>
        ";
if ($device == "mobile") echo "</tr><tr><td colspan=\"2\"><input type=\"submit\" name=\"login\" data-role=\"button\" value=\"".LOGIN."\" />";
else echo "<td align=\"left\"><span class=\"ashopdeliverytext2\"><input type=\"image\" border=\"0\" src=\"{$buttonpath}images/login-$lang.png\" class=\"ashopbutton\" alt=\"".LOGIN."\" /></span>";
echo "</td>
      </tr>
    </table>
    </form>
    </center>";
	if ($lang != $defaultlanguage && file_exists("$ashoppath$templatepath/delivery-$lang.html")) ashop_showtemplatefooter("$ashoppath$templatepath/delivery-$lang.html");
	  else ashop_showtemplatefooter("$ashoppath$templatepath/delivery.html");
    exit;
}

// Open database...
$db = @mysqli_connect("$databaseserver", "$databaseuser", "$databasepasswd", "$databasename");

// Check login information...
$isgift = "false";
$isupdate = "false";
$sql="SELECT customer.firstname, customer.lastname, orders.products, orders.orderid, orders.date, orders.paid, orders.payoptionid, orders.userid, orders.description FROM customer, orders WHERE orders.password='$password' AND (customer.email='$email' OR customer.alternativeemails LIKE '%$email%') AND orders.customerid=customer.customerid";
$result = @mysqli_query($db, "$sql");
if (@mysqli_num_rows($result) == 0) {
	$sql="SELECT * FROM updates WHERE password='$password'";
	$result2 = @mysqli_query($db, "$sql");
	if (@mysqli_num_rows($result2) == 0) {
		if ($lang != $defaultlanguage && file_exists("$ashoppath$templatepath/delivery-$lang.html")) ashop_showtemplateheader("$ashoppath$templatepath/delivery-$lang.html");
		else ashop_showtemplateheader("$ashoppath$templatepath/delivery.html");
		echo "<table class=\"ashopmessagetable\">
		<tr align=\"center\"><td><br><br><p><span class=\"ashopmessageheader\">".PASSINCORRECT."</span></p>
		<p><span class=\"ashopmessage\"><a href=\"javascript:history.back()\">".TRYAGAIN."</a></span></p></td></tr></table>";
		if ($lang != $defaultlanguage && file_exists("$ashoppath$templatepath/delivery-$lang.html")) ashop_showtemplatefooter("$ashoppath$templatepath/delivery-$lang.html");
		else ashop_showtemplatefooter("$ashoppath$templatepath/delivery.html");
		exit;
	} else {
		$updateproductid = @mysqli_result($result2, 0, "productid");
		$sql = "SELECT * FROM customer, orders WHERE (orders.products LIKE '%b$updateproductid"."a%' OR orders.products LIKE '%b$updateproductid"."d%') AND orders.customerid = customer.customerid AND customer.email = '$email' AND orders.paid != ''";
		$result3 = mysqli_query($db, "$sql");
		if (@mysqli_num_rows($result3) == 0) {
			if ($lang != $defaultlanguage && file_exists("$ashoppath$templatepath/delivery-$lang.html")) ashop_showtemplateheader("$ashoppath$templatepath/delivery-$lang.html");
			else ashop_showtemplateheader("$ashoppath$templatepath/delivery.html");
			echo "<table class=\"ashopmessagetable\">
			<tr align=\"center\"><td><br><br><p><span class=\"ashopmessageheader\">".PASSINCORRECT."</span></p>
			<p><span class=\"ashopmessage\"><a href=\"javascript:history.back()\"";
			if ($device == "mobile") echo " data-ajax=\"false\"";
			echo ">".TRYAGAIN."</a></span></p></td></tr></table>";
			if ($lang != $defaultlanguage && file_exists("$ashoppath$templatepath/delivery-$lang.html")) ashop_showtemplatefooter("$ashoppath$templatepath/delivery-$lang.html");
			else ashop_showtemplatefooter("$ashoppath$templatepath/delivery.html");
			exit;
		} else {
			$alloweddownloaddays = @mysqli_result($result2, 0, "alloweddays");
			$updatecaption = @mysqli_result($result2, 0, "title");
			$isupdate = "true";
		}
	}
}

// Check if this is a gift...
$description = explode("|",@mysqli_result($result,0,"description"));
if ($description[0] == "gift") {
	$isgift = "true";
	$giftdate = date("Y-m-d H:i:s", $description[1]);
}

// Check allowed download time...
if ($alloweddownloaddays) {
	$payoption = @mysqli_result($result, 0, "payoptionid");
	$result4 = @mysqli_query($db, "SELECT deliverpending FROM payoptions WHERE payoptionid='$payoption'");
	$deliverpending = @mysqli_result($result4, 0, "deliverpending");
	if ($isgift == "true") $orderdate = explode(" ",$giftdate);
	else if ($isupdate == "false" && $deliverpending) $orderdate = explode(" ",@mysqli_result($result, 0, "date"));
	else if ($isupdate == "false") $orderdate = explode(" ",@mysqli_result($result, 0, "paid"));
	else $orderdate = explode(" ",@mysqli_result($result2, 0, "date"));
	$orderdate = explode ("-",$orderdate[0]);
	$orderedtimestamp = mktime(0,0,0,$orderdate[1],$orderdate[2],$orderdate[0]);
	if ((floor((time()+$timezoneoffset)/86400))*86400 - $orderedtimestamp > 86400 * ($alloweddownloaddays+1)) {
		if ($lang != $defaultlanguage && file_exists("$ashoppath$templatepath/delivery-$lang.html")) ashop_showtemplateheader("$ashoppath$templatepath/delivery-$lang.html");
		else ashop_showtemplateheader("$ashoppath$templatepath/delivery.html");
		echo "<table class=\"ashopmessagetable\">
		<tr align=\"center\"><td><br><br><p><span class=\"ashopmessageheader\">".TIMEEXPIRED."</span></p>
		<p><span class=\"ashopmessage\"><a href=\"javascript:history.back()\"";
		if ($device == "mobile") echo " data-ajax=\"false\"";
		echo ">".TRYAGAIN."</a></span></p></td></tr></table>";
		if ($lang != $defaultlanguage && file_exists("$ashoppath$templatepath/delivery-$lang.html")) ashop_showtemplatefooter("$ashoppath$templatepath/delivery-$lang.html");
		else ashop_showtemplatefooter("$ashoppath$templatepath/delivery.html");
		exit;
	}
}

// Display download page or start download...
if (@mysqli_num_rows($result) || @mysqli_num_rows($result2)) {
  if ($isupdate == "false") {
	  $orderid = @mysqli_result($result, 0, "orderid");
	  $firstname = @mysqli_result($result, 0, "firstname");
	  $lastname = @mysqli_result($result, 0, "lastname");
	  $products = ashop_striphandlingcost(@mysqli_result($result, 0, "products"));
  } else {
	  $products = "1b$updateproductid"."a";
	  $filename = @mysqli_result($result2, 0, "filename");
  }
  $items = ashop_parseproductstring($db, $products);
  if ($file) {
	  $fileidresult = @mysqli_query($db, "SELECT fileid, storage FROM productfiles WHERE id='$file'");
	  $fileid = @mysqli_result($fileidresult,0,"fileid");
	  $storage = @mysqli_result($fileidresult,0,"storage");
	  $notallowed = "false";
	  if ($alloweddownloads && $isupdate == "false") {
		  $downloadsresult = @mysqli_query($db, "SELECT * FROM orderdownloads WHERE fileid='$fileid' AND orderid='$orderid'");
		  $thisdownloads = @mysqli_result($downloadsresult, 0, "downloads");
		  if ($thisdownloads >= $alloweddownloads) $notallowed = "true";
	  }
	  $fileinfo = pathinfo($filename);
	  $extension = strtolower($fileinfo["extension"]);
	  if (($extension == "mp4" || $extensions == "flv") && file_exists("$ashoppath/includes/aws/aws-config.php") && $storage == "1") $downloadscript = "viewstream.php";
	  else $downloadscript = "download.php";
	  if (!$p3psent) header('P3P: CP="IDC DSP COR CURa ADMa OUR IND PHY ONL COM STA"');
	  $p3psent = TRUE;
	  setcookie("fileid","$file");
	  setcookie("password","$password");
	  setcookie("email","$email");
	  if ($notallowed == "false") { 
		  if (strstr($HTTP_USER_AGENT,"Opera")) {
			  if (strstr($SERVER_SOFTWARE, "IIS")) {
				  echo "<html><head><meta http-equiv=\"Refresh\" content=\"0; URL=$downloadscript?dorefresh=true&filename=$filename\"></head></html>";
			  } else header("Location: $downloadscript?dorefresh=true&filename=$filename"); 
			  exit; 
		  } else if (strstr($HTTP_USER_AGENT,"Netscape/7.0")) {
			  if (strstr($SERVER_SOFTWARE, "IIS")) {
				  echo "<html><head><meta http-equiv=\"Refresh\" content=\"0; URL=$downloadscript/?$filename\"></head></html>";
			  } else header("Location: $downloadscript/?$filename"); 
			  exit; 
		  } else { 
			  if (strstr($SERVER_SOFTWARE, "IIS")) {
				  echo "<html><head><meta http-equiv=\"Refresh\" content=\"0; URL=$downloadscript?$filename\"></head></html>";
			  } else header("Location: $downloadscript?$filename");
			  exit;
		  }
	  } else { 
		  if (strstr($SERVER_SOFTWARE, "IIS")) {
				  echo "<html><head><meta http-equiv=\"Refresh\" content=\"0; URL=$downloadscript?notallowed=true\"></head></html>";
		  } else header("Location: $downloadscript?notallowed=true");
		  exit; 
	  }
  } else {
	if ($lang != $defaultlanguage && file_exists("$ashoppath$templatepath/delivery-$lang.html")) ashop_showtemplateheader("$ashoppath$templatepath/delivery-$lang.html");
	else ashop_showtemplateheader("$ashoppath$templatepath/delivery.html");
    echo "<table class=\"ashopdownloadframe\">
	  <tr align=\"left\"><td><p align=\"center\"><span class=\"ashopmessageheader\">".DELIVERY."</span></p>";
	if ($isupdate != "true") {
		echo "<p><span class=\"ashopdeliverytext2\">".WELCOME;
		if ($firstname && $lastname) echo " $firstname $lastname";
		echo "!</span></p>";
	}
	  $downloadstodisplay = 0;
	  if ($items) foreach ($items as $productnumber => $thisproduct) {
		  $awsviewshown = FALSE;
		  $alreadyshown = FALSE;
		  $attributedownload = $thisproduct["download"];
		  $name = $thisproduct["name"];
		  $prodtype = $thisproduct["producttype"];
		  if ($isupdate == "false") $filenames = $thisproduct["filename"];
		  if ($isupdate == "false" && $filenames && $attributedownload != "none") foreach ($filenames as $fileid => $filename) {
			  if ($attributedownload == $fileid || $attributedownload == "all") {
				  $fileidresult = @mysqli_query($db, "SELECT * FROM productfiles WHERE fileid='$fileid' AND productid='{$thisproduct["productid"]}'");
				  $fid = @mysqli_result($fileidresult,0,"id");
				  $storage = @mysqli_result($fileidresult,0,"storage");
				  $downloadsresult = @mysqli_query($db, "SELECT * FROM orderdownloads WHERE orderid='$orderid' AND fileid='$fileid'");
				  $downloadsperitem = @mysqli_result($downloadsresult,0,"downloads");
				  $fileinfo = pathinfo($filename);
				  $extension = strtolower($fileinfo["extension"]);
				  if ($downloadsperitem < $alloweddownloads || !$alloweddownloads) {
					  if (($extension == "mp4" || $extensions == "flv") && file_exists("$ashoppath/includes/aws/aws-config.php") && $storage == "1") {
						  if (!$awsviewshown) {
							  $awsviewshown = TRUE;
							  echo "<p><span class=\"ashopdeliverytext2\">".COPY."&quot;$name";
							  echo "&quot; ".CANBEVIEWED.":</span></p><p align=\"center\"><span class=\"ashopdeliverytext2\"><form action=\"deliver.php\" method=\"post\"";
							  if ($device == "mobile") echo " data-ajax=\"false\"";
							  echo "><input type=\"hidden\" name=\"file\" value=\"$fid\"><input type=\"hidden\" name=\"email\" value=\"$email\"><input type=\"hidden\" name=\"password\" value=\"$password\"><input type=\"hidden\" name=\"filename\" value=\"$filename\"><input type=\"submit\" value=\"".VIEW."\"></form></span></p>";
							  $downloadstodisplay++;
						  }
					  } else {
						  echo "<p><span class=\"ashopdeliverytext2\">".COPY."&quot;$name";
						  if (count($filenames) > 1 && ($attributedownload == "all" || $isgift)) echo " ($filename)";
						  echo "&quot; ".CAN.":</span></p><p align=\"center\"><span class=\"ashopdeliverytext2\"><form action=\"deliver.php\" method=\"post\"";
						  if ($device == "mobile") echo " data-ajax=\"false\"";
						  echo "><input type=\"hidden\" name=\"file\" value=\"$fid\"><input type=\"hidden\" name=\"email\" value=\"$email\"><input type=\"hidden\" name=\"password\" value=\"$password\"><input type=\"hidden\" name=\"filename\" value=\"$filename\"><input type=\"submit\" value=\"".DOWNLOAD."\"></form></span></p>";
						  $downloadstodisplay++;
					  }
				  } else if ($downloadsperitem >= $alloweddownloads && !$alreadyshown) {
					  if (($extension == "mp4" || $extensions == "flv") && file_exists("$ashoppath/includes/aws/aws-config.php") && $storage == "1") $alreadyshown = TRUE;
					  echo "<p><span class=\"ashopdeliverytext2\">".ALREADY." &quot;$name&quot; ".ALLOWED."</span></p>";
				  }
			  }
		  } else if ($isupdate == "true") {
			  echo "<p><span class=\"ashopdeliverytext2\">";
			  if ($updatecaption) echo $updatecaption;
			  else echo UPDATECOPY."&quot;$name&quot; ".CAN.":";
			  echo "</span></p><p align=\"center\"><span class=\"ashopdeliverytext2\"><form action=\"deliver.php\" method=\"post\"";
			  if ($device == "mobile") echo " data-ajax=\"false\"";
			  echo "><input type=\"hidden\" name=\"file\" value=\"$updateproductid\"><input type=\"hidden\" name=\"email\" value=\"$email\"><input type=\"hidden\" name=\"password\" value=\"$password\"><input type=\"hidden\" name=\"filename\" value=\"$filename\"><input type=\"hidden\" name=\"isupdate\" value=\"true\"><input type=\"submit\" value=\"Download\"></form></span></p>";
			  $downloadstodisplay++;
		  }
	  }
	  $limiteddays = "$alloweddownloaddays";
	  $unlimiteddays = "unlimited";
	  if ($downloaddays = ( $alloweddownloaddays > 0 ? $limiteddays : $unlimiteddays ));

	  $limiteddownloads = "$alloweddownloads";
	  $unlimiteddownloads = "unlimited";
	  if ($downloadtimes = ( $alloweddownloads > 0 ? $limiteddownloads : $unlimiteddownloads ));
	  if ($isupdate == "true") $downloadtimes = "unlimited";
	  if ($downloadstodisplay) echo "<span class=\"ashopdeliverytext2\"><p>".WHENCLICK."<b>".SAVE."</b>".BROWSE."</p><p>".VALID."$downloadtimes ".PER." $downloaddays".CONNECTION."</p>";
	  echo "<p>".CONTACT."<a href='mailto:$ashopemail'>".ADMIN."</a>.</p></span></td></tr></table>";
	  if ($lang != $defaultlanguage && file_exists("$ashoppath$templatepath/delivery-$lang.html")) ashop_showtemplatefooter("$ashoppath$templatepath/delivery-$lang.html");
	  else ashop_showtemplatefooter("$ashoppath$templatepath/delivery.html");
  }
} else {
	if ($lang != $defaultlanguage && file_exists("$ashoppath$templatepath/delivery-$lang.html")) ashop_showtemplateheader("$ashoppath$templatepath/delivery-$lang.html");
	else ashop_showtemplateheader("$ashoppath$templatepath/delivery.html");
	echo "<table class=\"ashopmessagetable\">
	<tr align=\"center\"><td><br><br><p><span class=\"ashopmessageheader\">".WRONGDELIV."</span></p>
	<p><span class=\"ashopmessage\">".NOACCESS."</span></p>
	<p><span class=\"ashopmessage\"><a href=\"javascript:history.back()\"";
	if ($device == "mobile") echo " data-ajax=\"false\"";
	echo ">".TRYAGAIN."</a></span></p></td></tr></table>";
	if ($lang != $defaultlanguage && file_exists("$ashoppath$templatepath/delivery-$lang.html")) ashop_showtemplatefooter("$ashoppath$templatepath/delivery-$lang.html");
	else ashop_showtemplatefooter("$ashoppath$templatepath/delivery.html");
}
// Close database connection...
@mysqli_close($db);
?>