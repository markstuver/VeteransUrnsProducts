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
include "admin/ashopfunc.inc.php";
include "admin/ashopconstants.inc.php";

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
include "language/$lang/bidregister.inc.php";

// Get member template path if no theme is used...
if ($shop && $shop != "1" && $usethemetemplates != "true" && file_exists("$ashoppath/members/files/$ashopuser/catalogue.html")) $templatepath = "/members/files/$ashopuser";

// Open database...
$errorcheck = ashop_opendatabase();
if ($errorcheck) $error = $errorcheck;

// Set current date and time...
$date = date("Y-m-d H:i:s", time()+$timezoneoffset);

// Set and validate input variables...
$bidcode = $_POST["bidcode"];
$bidcode = stripslashes($bidcode);
$bidcode = @mysqli_real_escape_string($db, $bidcode);
$bidcode = str_replace("\'","",$bidcode);
$bidcode = str_replace("\"","",$bidcode);
$bidcode = str_replace("/","",$bidcode);
$bidcode = str_replace("\n","",$bidcode);
$bidcode = str_replace(";","",$bidcode);
$bidcode = str_replace("select","",$bidcode);
$bidcode = str_replace("insert","",$bidcode);
$bidcode = str_replace("update","",$bidcode);
$bidcode = str_replace("delete","",$bidcode);
$bidcode = str_replace("create","",$bidcode);
$bidcode = str_replace("modify","",$bidcode);
$bidcode = str_replace("password","",$bidcode);
$bidcode = str_replace("user","",$bidcode);
$bidcode = str_replace("concat","",$bidcode);
$bidcode = str_replace("from","",$bidcode);
$bidcode = str_replace("username","",$bidcode);
$screenname = $_POST["screenname"];
$screenname = stripslashes($screenname);
$screenname = @mysqli_real_escape_string($db, $screenname);
$screenname = str_replace("\'","",$screenname);
$screenname = str_replace("\"","",$screenname);
$screenname = str_replace("/","",$screenname);
$screenname = str_replace("\n","",$screenname);
$screenname = str_replace(";","",$screenname);
$screenname = str_replace("select","",$screenname);
$screenname = str_replace("insert","",$screenname);
$screenname = str_replace("update","",$screenname);
$screenname = str_replace("delete","",$screenname);
$screenname = str_replace("create","",$screenname);
$screenname = str_replace("modify","",$screenname);
$screenname = str_replace("password","",$screenname);
$screenname = str_replace("user","",$screenname);
$screenname = str_replace("concat","",$screenname);
$screenname = str_replace("from","",$screenname);
$screenname = str_replace("username","",$screenname);
if (isset($productid) && !is_numeric($productid)) $productid = 0;
if (!ashop_is_md5($_COOKIE["customersessionid"])) $_COOKIE["customersessionid"] = "";

// Check if the bidder is an existing logged in customer...
if (!empty($_COOKIE["customersessionid"])) {
	$customerresult = @mysqli_query($db, "SELECT * FROM customer WHERE sessionid='{$_COOKIE["customersessionid"]}'");
	$customerid = @mysqli_result($customerresult,0,"customerid");
}

// Handle standard auction bidding...
if (!empty($productid)) {
	// Check that the customer is logged in...
	if (empty($_COOKIE["customersessionid"])) {
		if ($lang != $defaultlanguage && file_exists("$ashoppath$templatepath/cart-$lang.html")) ashop_showtemplateheader("$ashoppath$templatepath/cart-$lang.html");
		else ashop_showtemplateheader("$ashoppath$templatepath/cart.html");
		echo "<table class=\"ashopmessagetable\">
		<tr align=\"center\"><td><br><br><p><span class=\"ashopmessageheader\">".NOTLOGGEDIN."</span></p>
		<p><span class=\"ashopmessage\">".LOGIN." <a href=\"login.php\">".HERE."</a> ".ORSIGNUP." <a href=\"signupform.php\">".HERE."</a>.</span></p></td></tr></table>";
		if ($lang != $defaultlanguage && file_exists("$ashoppath$templatepath/cart-$lang.html")) ashop_showtemplatefooter("$ashoppath$templatepath/cart-$lang.html");
		else ashop_showtemplatefooter("$ashoppath$templatepath/cart.html");
		exit;
	}
	// Check that the customer exists...
	if (!@mysqli_num_rows($customerresult)) {
		if ($lang != $defaultlanguage && file_exists("$ashoppath$templatepath/cart-$lang.html")) ashop_showtemplateheader("$ashoppath$templatepath/cart-$lang.html");
		else ashop_showtemplateheader("$ashoppath$templatepath/cart.html");
		echo "<table class=\"ashopmessagetable\">
		<tr align=\"center\"><td><br><br><p><span class=\"ashopmessageheader\">".NOSUCHPROFILE."</span></p>
		<p><span class=\"ashopmessage\">".LOGIN." <a href=\"login.php\">".HERE."</a> ".ORSIGNUP." <a href=\"signupform.php\">".HERE."</a>.</span></p></td></tr></table>";
		if ($lang != $defaultlanguage && file_exists("$ashoppath$templatepath/cart-$lang.html")) ashop_showtemplatefooter("$ashoppath$templatepath/cart-$lang.html");
		else ashop_showtemplatefooter("$ashoppath$templatepath/cart.html");
		exit;
	} else {
		$firstname = @mysqli_result($customerresult,0,"firstname");
		$lastname = @mysqli_result($customerresult,0,"lastname");
		$email = @mysqli_result($customerresult,0,"email");
		$bidderresult = @mysqli_query($db, "SELECT screenname FROM pricebidder WHERE bidcode='$customerid' OR customerid='$customerid'");
		$currentscreenname = @mysqli_result($bidderresult,0,"screenname");
		if (empty($screenname)) {
			if (!empty($currentscreenname)) $screenname = $currentscreenname;
			else if (!empty($lastname)) {
				if (!empty($firstname)) $screenname = $firstname." ".$lastname;
				else $screenname = $lastname;
			} else $screenname = $firstname;
		}
	}
	// Check that the auction exists...
	$floatingpriceresult = @mysqli_query($db, "SELECT * FROM floatingprice WHERE productid='$productid' AND type='standard'");
	$productresult = @mysqli_query($db, "SELECT * FROM product WHERE productid='$productid'");
	if (!@mysqli_num_rows($floatingpriceresult) || !@mysqli_num_rows($productresult)) {
		if ($lang != $defaultlanguage && file_exists("$ashoppath$templatepath/cart-$lang.html")) ashop_showtemplateheader("$ashoppath$templatepath/cart-$lang.html");
		else ashop_showtemplateheader("$ashoppath$templatepath/cart.html");
		echo "<table class=\"ashopmessagetable\">
		<tr align=\"center\"><td><br><br><p><span class=\"ashopmessageheader\">".NOSUCHAUCTION."</span></p>
		<p><span class=\"ashopmessage\"><form action=\"bidregister.php\" method=\"post\" name=\"bidform\"><input type=\"hidden\" name=\"productid\" value=\"$productid\"><input type=\"hidden\" name=\"catalog\" value=\"$catalog\"><input type=\"hidden\" name=\"cat\" value=\"$cat\"></form><a href=\"javascript:bidform.submit()\">".TRYAGAINLINK."</a></span></p></td></tr></table>";
		if ($lang != $defaultlanguage && file_exists("$ashoppath$templatepath/cart-$lang.html")) ashop_showtemplatefooter("$ashoppath$templatepath/cart-$lang.html");
		else ashop_showtemplatefooter("$ashoppath$templatepath/cart.html");
		exit;
	}
	// Check that the auction has not ended...
	$endprice = @mysqli_result($floatingpriceresult,0,"endprice");
	if ($endprice) {
		if ($lang != $defaultlanguage && file_exists("$ashoppath$templatepath/cart-$lang.html")) ashop_showtemplateheader("$ashoppath$templatepath/cart-$lang.html");
		else ashop_showtemplateheader("$ashoppath$templatepath/cart.html");
		echo "<table class=\"ashopmessagetable\">
		<tr align=\"center\"><td><br><br><p><span class=\"ashopmessageheader\">".AUCTIONHASENDED."</span></p>
		</td></tr></table>";
		if ($lang != $defaultlanguage && file_exists("$ashoppath$templatepath/cart-$lang.html")) ashop_showtemplatefooter("$ashoppath$templatepath/cart-$lang.html");
		else ashop_showtemplatefooter("$ashoppath$templatepath/cart.html");
		exit;
	}

	// Get product details...
	$productname = @mysqli_result($productresult,0,"name");
	$producturl = @mysqli_result($productresult,0,"detailsurl");
	$starttime = @mysqli_result($floatingpriceresult,0,"starttime");
	$startprice = @mysqli_result($floatingpriceresult,0,"startprice");
	$priceincrement = @mysqli_result($floatingpriceresult,0,"priceincrement");
	$numberofbids = @mysqli_result($floatingpriceresult,0,"bids");
	$currentbidder = @mysqli_result($floatingpriceresult,0,"bidderid");
	if ($numberofbids > 0) $numberofbids++;
	$minimumbid = $startprice+($priceincrement*$numberofbids);
	$minimumbid = round($minimumbid,2);
	if ($numberofbids == 0) $numberofbids = 1;

	// Check if a bid has been submitted...
	if (!empty($newbid)) {
		if (!empty($thousandchar)) $newbid = str_replace($thousandchar,"",$newbid);
		if (!empty($decimalchar) && $decimalchar != ".") $newbid = str_replace($decimalchar,".",$newbid);
	}
	if (!empty($newbid) && is_numeric($newbid)) {
		$newbid = round($newbid,2);
		if ($newbid < $minimumbid) {
			if ($lang != $defaultlanguage && file_exists("$ashoppath$templatepath/cart-$lang.html")) ashop_showtemplateheader("$ashoppath$templatepath/cart-$lang.html");
			else ashop_showtemplateheader("$ashoppath$templatepath/cart.html");
			echo "<table class=\"ashopmessagetable\">
			<tr align=\"center\"><td><br><br><p><span class=\"ashopmessageheader\">".BELOWMINIMUM."</span></p>
			<p><span class=\"ashopmessage\"><form action=\"bidregister.php\" method=\"post\" name=\"bidform\"><input type=\"hidden\" name=\"productid\" value=\"$productid\"><input type=\"hidden\" name=\"catalog\" value=\"$catalog\"><input type=\"hidden\" name=\"cat\" value=\"$cat\"></form><a href=\"javascript:bidform.submit()\">".TRYAGAINLINK."</a></span></p></td></tr></table>";
			if ($lang != $defaultlanguage && file_exists("$ashoppath$templatepath/cart-$lang.html")) ashop_showtemplatefooter("$ashoppath$templatepath/cart-$lang.html");
			else ashop_showtemplatefooter("$ashoppath$templatepath/cart.html");
			exit;
		}
		// Check if this customer has activated a bid code...
		$verifiedbidder = FALSE;
		if (!empty($_COOKIE["bidderhash"])) {
			$verifiedbidder = ashop_checkbidcode($db, $_COOKIE["bidderhash"]);
			if ($verifiedbidder == TRUE) {
				$bidderhash = explode("|",$_COOKIE["bidderhash"]);
				$bidderid = $bidderhash[0];
				@mysqli_query($db, "UPDATE pricebidder SET customerid='$customerid' WHERE bidderid='$bidderid'");
			}
		}
		if (empty($bidderid) || !is_numeric($bidderid)) {
			// Check if this customer is registered as an auction bidder...
			$bidderresult = @mysqli_query($db, "SELECT * FROM pricebidder WHERE bidcode='$customerid' OR customerid='$customerid'");
			if (!@mysqli_num_rows($bidderresult)) {
				@mysqli_query($db, "INSERT INTO pricebidder (bidcode,screenname,customerid) VALUES ('$customerid','$screenname','$customerid')");
				$bidderid = @mysqli_insert_id($db);
				$thisbidcode = $customerid;
			} else {
				$bidderid = @mysqli_result($bidderresult,0,"bidderid");
				$thisbidcode = @mysqli_result($bidderresult,0,"bidcode");
				$thisscreenname = @mysqli_result($bidderresult,0,"screenname");
				if ($screenname != $thisscreenname) @mysqli_query($db, "UPDATE pricebidder SET screenname='$screenname' WHERE bidderid='$bidderid'");
			}
		} else @mysqli_query($db, "UPDATE pricebidder SET screenname='$screenname' WHERE bidderid='$bidderid'");
		$previousbid = $startprice+(($numberofbids-1)*$priceincrement);
		$totalbidsamount = $numberofbids*$priceincrement;
		$newstartprice = $newbid-$totalbidsamount;
		$newstarttime = time();
		if (empty($starttime)) $sql = "UPDATE floatingprice SET startprice='$newstartprice',bids='$numberofbids',bidderid='$bidderid',starttime='$newstarttime' WHERE productid='$productid'";
		else $sql = "UPDATE floatingprice SET startprice='$newstartprice',bids='$numberofbids',bidderid='$bidderid' WHERE productid='$productid'";
		@mysqli_query($db, $sql);
		$hashstring = md5($ashoppath.$thisbidcode);
		if (!$verifiedbidder) {
			if (!$p3psent) header('P3P: CP="IDC DSP COR CURa ADMa OUR IND PHY ONL COM STA"');
			$p3psent = TRUE;
			setcookie("bidderhash","{$bidderid}|{$hashstring}", mktime(0,0,0,12,1,2020));
		}

		// Send a message to the previous bidder that he/she has been outbid...
		$oldbidderresult = @mysqli_query($db, "SELECT customerid FROM pricebidder WHERE bidderid='$currentbidder'");
		$oldcustomerid = @mysqli_result($oldbidderresult,0,"customerid");
		$oldbidderresult = @mysqli_query($db, "SELECT firstname, lastname, email FROM customer WHERE customerid='$oldcustomerid'");
		$messagefile = "";
		$messagetemplate = "";
		if (file_exists("$ashoppath/templates/messages/outbidmessage-$lang.html")) $messagefile = "$ashoppath/templates/messages/outbidmessage-$lang.html";
		else if (file_exists("$ashoppath/templates/messages/outbidmessage.html")) $messagefile = "$ashoppath/templates/messages/outbidmessage.html";
		if (!empty($messagefile) && $bidderid != $currentbidder) {
			$fp = @fopen("$messagefile","r");
			if ($fp) {
				while (!feof ($fp)) $messagetemplate .= fgets($fp, 4096);
				fclose($fp);
			}
			if (!empty($messagetemplate)) {
				$email = @mysqli_result($oldbidderresult,0,"email");
				$firstname = @mysqli_result($oldbidderresult,0,"firstname");
				$lastname = @mysqli_result($oldbidderresult,0,"lastname");
				$date = date("Y-m-d H:i:s", time()+$timezoneoffset);
				$currentbid = $currencysymbols[$ashopcurrency]["pre"].$newbid.$currencysymbols[$ashopcurrency]["post"];
				$previousbid = $currencysymbols[$ashopcurrency]["pre"].$previousbid.$currencysymbols[$ashopcurrency]["post"];
				$message = str_replace("%ashopname%",$ashopname,$messagetemplate);
				if (!empty($producturl)) $message = str_replace("%itemurl%",$producturl,$message);
				else $message = str_replace("%itemurl%","$ashopurl/product.php?product=$productid",$message);
				$message = str_replace("%firstname%",$firstname,$message);
				$message = str_replace("%lastname%",$lastname,$message);
				$message = str_replace("%email%",$email,$message);
				$message = str_replace("%date%",$date,$message);
				$message = str_replace("%previousbid%",$previousbid,$message);
				$message = str_replace("%currentbid%",$currentbid,$message);
				$message = str_replace("%itemname%",$productname,$message);

				$headers = "From: ".un_html($ashopname)."<$ashopemail>\nX-Sender: <$ashopemail>\nX-Mailer: PHP\nX-Priority: 3\nReturn-Path: <$ashopemail>\nMIME-Version: 1.0\nContent-Type: text/html; charset=iso-8859-1\n";
				@ashop_mail("$email",un_html($ashopname)." ".OUTBIDSUBJECT,"$message","$headers");
			}
		}
		if (!empty($cat) && is_numeric($cat)) {
			if ($catalog) $producturl = $catalog."?cat=$cat";
			else $producturl = "$ashopurl/index.php?cat=$cat";
		}
		if (empty($producturl)) $producturl = "$ashopurl/product.php?product=$productid";

		// Tell the customer that the bid has been registered...
		if ($lang != $defaultlanguage && file_exists("$ashoppath$templatepath/cart-$lang.html")) ashop_showtemplateheader("$ashoppath$templatepath/cart-$lang.html");
		else ashop_showtemplateheader("$ashoppath$templatepath/cart.html");
		echo "<table class=\"ashopmessagetable\">
		<tr align=\"center\"><td><br><br><p><span class=\"ashopmessageheader\">".BIDREGISTERED."</span></p>
		<p><span class=\"ashopmessage\"><< <a href=\"$producturl\">".GOBACKTO." $productname</a></span></p></td></tr></table>";
		if ($lang != $defaultlanguage && file_exists("$ashoppath$templatepath/cart-$lang.html")) ashop_showtemplatefooter("$ashoppath$templatepath/cart-$lang.html");
		else ashop_showtemplatefooter("$ashoppath$templatepath/cart.html");
		exit;
	} else {
		// Let the customer enter a bid...
		$minimumbid = number_format($minimumbid,$showdecimals,$decimalchar,$thousandchar);
		if ($lang != $defaultlanguage && file_exists("$ashoppath$templatepath/cart-$lang.html")) ashop_showtemplateheader("$ashoppath$templatepath/cart-$lang.html");
		else ashop_showtemplateheader("$ashoppath$templatepath/cart.html");
		echo "<table class=\"ashopmessagetable\" style=\"width: 345px;\">
		<tr align=\"left\"><td><br><br><p><span class=\"ashopmessageheader\">".CONFIRMBID.": <strong>";
		if (!empty($producturl)) echo "<a href=\"$producturl\">$productname</a>";
		else echo $productname;
		echo "</strong></span></p>
		 <span class=\"ashopmessage\"><form action=\"bidregister.php\" method=\"post\">";
		 if (empty($currentscreenname)) echo ENTERSCREENNAME.": <input type=\"text\" name=\"screenname\" size=\"25\" value=\"$screenname\"><br /><br />";
		 echo YOURBID.": ".$currencysymbols[$ashopcurrency]["pre"]."<input type=\"text\" name=\"newbid\" value=\"$minimumbid\" size=\"10\" style=\"vertical-align: text-bottom;\">".$currencysymbols[$ashopcurrency]["post"]." <input type=\"image\" src=\"images/bid-$lang.png\" name=\"bid\" class=\"ashopbutton\" style=\"vertical-align: text-bottom;\"><input type=\"hidden\" name=\"productid\" value=\"$productid\"><input type=\"hidden\" name=\"catalog\" value=\"$catalog\"><input type=\"hidden\" name=\"cat\" value=\"$cat\"></form></span></td></tr><tr><td><span class=\"ashopaffiliatenotice\" style=\"padding-left: 60px;\">(".MINIMUM." ".$currencysymbols[$ashopcurrency]["pre"].$minimumbid.$currencysymbols[$ashopcurrency]["post"].")
		 </span></td></tr></table>";
		 if ($lang != $defaultlanguage && file_exists("$ashoppath$templatepath/cart-$lang.html")) ashop_showtemplatefooter("$ashoppath$templatepath/cart-$lang.html");
		 else ashop_showtemplatefooter("$ashoppath$templatepath/cart.html");
		 exit;
	}
exit;
}

// Check if bid code is in the database...
if($bidcode) {
	$result = @mysqli_query($db, "SELECT * FROM pricebidder WHERE bidcode='$bidcode'");
	if (!@mysqli_num_rows($result)) {
		if ($lang != $defaultlanguage && file_exists("$ashoppath$templatepath/cart-$lang.html")) ashop_showtemplateheader("$ashoppath$templatepath/cart-$lang.html");
		else ashop_showtemplateheader("$ashoppath$templatepath/cart.html");
		echo "<table class=\"ashopmessagetable\">
		<tr align=\"center\"><td><br><br><p><span class=\"ashopmessageheader\">".INCORRECT."</span></p>
		<p><span class=\"ashopmessage\">".TRYAGAIN."</span></p>
		<p><form action=\"bidregister.php\" method=\"post\"><span class=\"ashopmessage\">".ENTERBIDCODE."</span>: <input type=\"text\" name=\"bidcode\" size=\"30\"><br><span class=\"ashopmessage\">".ENTERSCREENNAME."</span>: <input type=\"text\" name=\"screenname\" size=\"15\"><input type=\"submit\" value=\"Submit\"></form></p></td></tr></table>";
		if ($lang != $defaultlanguage && file_exists("$ashoppath$templatepath/cart-$lang.html")) ashop_showtemplatefooter("$ashoppath$templatepath/cart-$lang.html");
		else ashop_showtemplatefooter("$ashoppath$templatepath/cart.html");
		exit;
	} else {
		// Bid code is available...
		$refill = FALSE;
		$redirect = "$ashopurl/index.php";
		$bidderid = @mysqli_result($result,0,"bidderid");
		$numberofbids = @mysqli_result($result,0,"numberofbids");
		$refilled = @mysqli_result($result,0,"refill");
		if (!$screenname) $screenname = "Anonymous";

		// Check if the bid code should be used as a refill...
		if (isset($_COOKIE["bidderhash"]) && !$refilled) {
			$bidderhasharray = explode("|",$_COOKIE["bidderhash"]);
			$refillbidderid = $bidderhasharray[0];
			if ($refillbidderid) $checkrefillresult = @mysqli_query($db, "SELECT * FROM pricebidder WHERE bidderid='$refillbidderid'");
			if (@mysqli_num_rows($checkrefillresult) && $refillbidderid != $bidderid) {
				$refillrow = @mysqli_fetch_array($checkrefillresult);
				$refillbidcode = $refillrow["bidcode"];
				if ($bidderhasharray[1] == md5($ashoppath.$refillbidcode)) {
					$refill = TRUE;
					@mysqli_query($db, "UPDATE pricebidder SET refill='$refillbidderid', customerid='$customerid' WHERE bidderid='$bidderid'");
					$numberofbids += $refillrow["numberofbids"];
					@mysqli_query($db, "UPDATE pricebidder SET numberofbids='$numberofbids', screenname='$screenname', customerid='$customerid' WHERE bidderid='$refillbidderid'");
				}
			}
		}

		// Use the previous bid code if this bid code has been used as a refill...
		if ($refilled) {
			$result = @mysqli_query($db, "SELECT * FROM pricebidder WHERE bidderid='$refilled'");
			if (@mysqli_num_rows($result)) {
				$bidderid = @mysqli_result($result,0,"bidderid");
				$bidcode = @mysqli_result($result,0,"bidcode");
				$numberofbids = @mysqli_result($result,0,"numberofbids");
			} else {
				$bidderid = "";
				$bidcode = "";
			}
		} 
		
		// Set the bidder cookie...
		if (!$refill) {
			// Set screenname and cookie ...			
			@mysqli_query($db, "UPDATE pricebidder SET screenname='$screenname', customerid='$customerid' WHERE bidderid='$bidderid'");
			$hashstring = md5($ashoppath.$bidcode);
			if (!$p3psent) header('P3P: CP="IDC DSP COR CURa ADMa OUR IND PHY ONL COM STA"');
			$p3psent = TRUE;
			setcookie("bidderhash","{$bidderid}|{$hashstring}", mktime(0,0,0,12,1,2020));
		}

		if ($lang != $defaultlanguage && file_exists("$ashoppath$templatepath/cart-$lang.html")) ashop_showtemplateheader("$ashoppath$templatepath/cart-$lang.html");
		else ashop_showtemplateheader("$ashoppath$templatepath/cart.html");
		echo "<table class=\"ashopmessagetable\"><tr align=\"center\"><td><br><br><span class=\"ashopmessageheader\">".WELCOME." $screenname!</span><br><br><span class=\"ashopmessage\">".CANPLACE.": $numberofbids ".BIDS."<br>".REDIRECTED."
		$ashopname.</span><br><meta http-equiv=\"Refresh\" content=\"10; URL=$redirect\"><br><br><span class=\"ashopmessage\">".IFNOREDIRECT."<a href=\"$redirect\">".HERE."</a>.</span></td></tr></table>";
		if ($lang != $defaultlanguage && file_exists("$ashoppath$templatepath/cart-$lang.html")) ashop_showtemplatefooter("$ashoppath$templatepath/cart-$lang.html");
		else ashop_showtemplatefooter("$ashoppath$templatepath/cart.html");
		exit;	
	}
} else {
	// Let the visitor enter a bid code...
	if ($lang != $defaultlanguage && file_exists("$ashoppath$templatepath/cart-$lang.html")) ashop_showtemplateheader("$ashoppath$templatepath/cart-$lang.html");
	else ashop_showtemplateheader("$ashoppath$templatepath/cart.html");
	echo "<table class=\"ashopmessagetable\">
	     <tr align=\"center\"><td><br><br><p><span class=\"ashopmessageheader\">".REGISTERBIDDER."</span></p>
		 <p><form action=\"bidregister.php\" method=\"post\"><span class=\"ashopmessage\">".ENTERBIDCODE."</span>: <input type=\"text\" name=\"bidcode\" size=\"30\"><br><span class=\"ashopmessage\">".ENTERSCREENNAME."</span>: <input type=\"text\" name=\"screenname\" size=\"15\"><input type=\"submit\" value=\"Submit\"></form></p></td></tr></table>";
	if ($lang != $defaultlanguage && file_exists("$ashoppath$templatepath/cart-$lang.html")) ashop_showtemplatefooter("$ashoppath$templatepath/cart-$lang.html");
	else ashop_showtemplatefooter("$ashoppath$templatepath/cart.html");
	exit;
}
@mysqli_close($db);
?>