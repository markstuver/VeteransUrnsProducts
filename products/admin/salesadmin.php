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

error_reporting(E_ALL ^ E_NOTICE);

include "config.inc.php";
include "ashopfunc.inc.php";
if ($noinactivitycheck == "false") {
	if ($msg) $noinactivitycheck = "true";
	else $noinactivitycheck = "false";
}

include "checklogin.inc.php";
include "ashopconstants.inc.php";

// Validate variables...
if (!is_numeric($resultpage)) unset($resultpage);
if (!is_numeric($admindisplayitems)) unset($admindisplayitems);
else {
	$c_admindisplayitems = $admindisplayitems;
	if (!$p3psent) header('P3P: CP="IDC DSP COR CURa ADMa OUR IND PHY ONL COM STA"');
	$p3psent = TRUE;
	setcookie("c_admindisplayitems","$admindisplayitems");
}
if (!is_numeric($c_admindisplayitems)) unset($c_admindisplayitems);
$namefilter = str_replace("<","",$namefilter);
$namefilter = str_replace(">","",$namefilter);
$emailfilter = str_replace("<","",$emailfilter);
$emailfilter = str_replace(">","",$emailfilter);

if (!$dmshowcustomers && $userid != "1") {
	header("Location: salesreport.php");
	exit;
}

include "template.inc.php";
// Get language module...
include "language/$adminlang/customers.inc.php";

// Get context help for this page...
$contexthelppage = "salesadmin";
include "help.inc.php";

// Check if party planner is available...
if (file_exists("$ashoppath/customerparties.php")) $partyplanner = TRUE;
else $partyplanner = FALSE;

// Open database...
$db = @mysqli_connect("$databaseserver", "$databaseuser", "$databasepasswd", "$databasename");

// Pause/resume a mailing...
if ($pause) @mysqli_query($db, "UPDATE mailing SET paused='1' WHERE type='customer'");
else if ($resume) @mysqli_query($db, "UPDATE mailing SET paused=NULL WHERE type='customer'");

// Check if a mailing is being sent...
$mailingresult = @mysqli_query($db, "SELECT * FROM mailing WHERE type='customer'");
$mailinginprogress = @mysqli_num_rows($mailingresult);
/*
if (!empty($mailinginprogress)) {
	$mailingid = @mysqli_result($mailingresult,0,"mailingid");
	$paused = @mysqli_result($mailingresult,0,"paused");
	$sentresult = @mysqli_query($db, "SELECT * FROM maillog WHERE mailingid='$mailingid'");
	$totalsent = @mysqli_num_rows($sentresult);

	if ($paused) $pauseresumeform = "<p><form action=\"salesadmin.php\" method=\"post\"><input type=\"submit\" name=\"resume\" value=\"".RESUME."\"></form></p>";
	else $pauseresumeform = "<p><form action=\"salesadmin.php\" method=\"post\"><input type=\"submit\" name=\"pause\" value=\"".PAUSE."\"></form></p>";

	echo "$header
<div class=\"heading\">".MAILINGINPROGRESS."</div><center><br><br><br>
<script language=\"JavaScript\" src=\"../includes/prototype.js\" type=\"text/javascript\"></script>
<script language=\"JavaScript\" type=\"text/javascript\">
function reportprogress(ajaxRequest) {
	parameters = ajaxRequest.responseText;
	parametersarray = parameters.split('|');
	sent = parseInt(parametersarray[0]);
	total = parseInt(parametersarray[1]);
	sentmsgs = sent;
	totalmsgs = total;
	if (sent == -1) $('mailingprogress').update('".MESSAGESENT." <a href=\"../previews/'+logfile+'\" target=\"_blank\">".VIEWLOG."</a>');
	$('sentmails').update(sent);
	$('totalmails').update(total);
}

function setlogfile(ajaxRequest) {
	templog = ajaxRequest.responseText;
	if (templog) logfile = templog;
}

function checkprogress() {
	var myAjax = new Ajax.Request(
		'mailstatus.php', 
		{
			method: 'get',
			parameters: 'mailingtype=customer&dummy='+ new Date().getTime(), 
			onSuccess: reportprogress
		}
	);
}

function startmailing() {
	var myAjax = new Ajax.Request(
		'mailcustomer.php', 
		{
			method: 'get',
			parameters: 'mailall=true&dummy='+ new Date().getTime(),
			onSuccess: setlogfile
		}
	);
}
window.setInterval(\"checkprogress()\",3000);
</script>

	<div id=\"mailingprogress\" class=\"confirm\">".SENT.": <span id=\"sentmails\">0</span> ".THEWORDOF.": <span id=\"totalmails\"></span> ".MESSAGES.".$pauseresumeform</div>
<script language=\"JavaScript\" type=\"text/javascript\">
var logfile = '';
var totalmsgs = 0;
var checkmsgs = 0;
checkprogress();
startmailing();
function unstall() {
	if (totalmsgs == checkmsgs && totalmsgs != -1) startmailing();
	else checkmsgs = totalmsgs;
}
window.setInterval(\"unstall()\",15000);
</script>
</td></tr></table></center>$footer
	";
	exit;
}*/

// Check if an eMerchant session is active...
$emcheckresult = @mysqli_query($db, "SELECT activity FROM emerchant_user WHERE username='admin'");
$emactivity = @mysqli_result($emcheckresult, 0, "activity");
$emactivity = explode(" ",$emactivity);
$date = date("Y/m/d", time()+$timezoneoffset);
if ($emactivity[0] == $date) $emactive = TRUE;
else $emactive = FALSE;

// Check if penny auctions are used...
$bidderresult = @mysqli_query($db, "SELECT productid FROM floatingprice LIMIT 1");
if (@mysqli_num_rows($bidderresult)) $auctionbidderslink = " [<a href=\"bidderadmin.php\" class=\"sm\">".AUCTIONBIDDERS."</a>]";
else $auctionbidderslink = "";

echo "$header
<script language=\"JavaScript\" type=\"text/javascript\">
<!--
function newWindow(newContent)
{
	winContent = window.open(newContent, 'c572bf4', 'top=109,width=800,height=550, toolbar=no,scrollbars=yes, resizable=no');
	x = (screen.availWidth-800)/2;
	y = (screen.availHeight-550)/2;
	winContent.moveTo(x,y);
}
function selectAll()
{
	if (document.customermailform.switchall.checked == true) {
		for (var i = 0; i < document.customermailform.elements.length; i++) {
			if (document.customermailform.elements[i].checked != true) {
				document.customermailform.elements[i].checked = true;
			}
		}
	} else {
		for (var i = 0; i < document.customermailform.elements.length; i++) {
			if (document.customermailform.elements[i].checked == true) {
				document.customermailform.elements[i].checked = false;
			}
		}
	}
}
-->
</script>
<div class=\"heading\">".CUSTOMERSANDMESSAGING." <a href=\"$help1\" target=\"_blank\"><img src=\"images/icon_helpsm.gif\" width=\"15\" height=\"15\" border=\"0\"></a></div><center>";
if ($userid == "1") {

	if ($mailinginprogress) echo "<p><b>".MAILINGINPROGRESS."</b> <input type=\"button\" value=\"".VIEWSTATUS."\" onclick=\"document.location.href='mailingadmin.php?type=customer'\"></p>";

	if ($wholesalecatalog) {
		if (file_exists("$ashoppath/emerchant/quote.php")) {
			if (!$recurring) echo "<span class=\"formtitle\">".ALLCUSTOMERS." [<a href=\"wssalesadmin.php\" class=\"sm\">".WHOLESALECUSTOMERS."</a>] [<a href=\"salesadmin.php?recurring=true\" class=\"sm\">".RECURRINGBILLINGCUSTOMERS."</a>]$auctionbidderslink</span><br>";
			else echo "<span class=\"formtitle\">[<a href=\"salesadmin.php\" class=\"sm\">".ALLCUSTOMERS."</a>] [<a href=\"wssalesadmin.php\" class=\"sm\">".WHOLESALECUSTOMERS."</a>] ".RECURRINGBILLINGCUSTOMERS."$auctionbidderslink</span><br>";
		} else echo "<span class=\"formtitle\">".ALLCUSTOMERS." [<a href=\"wssalesadmin.php\" class=\"sm\">".WHOLESALECUSTOMERS."</a>]$auctionbidderslink</span><br>";
	} else {
		if (file_exists("$ashoppath/emerchant/quote.php")) {
			if (!$recurring) echo "<span class=\"formtitle\">".ALLCUSTOMERS." [<a href=\"salesadmin.php?recurring=true\" class=\"sm\">".RECURRINGBILLINGCUSTOMERS."</a>]$auctionbidderslink</span><br>";
			else echo "<span class=\"formtitle\">[<a href=\"salesadmin.php\" class=\"sm\">".ALLCUSTOMERS."</a>] ".RECURRINGBILLINGCUSTOMERS."$auctionbidderslink</span><br>";
		} else echo "<br>";
	}
	echo "<br>";
}
if (!empty($klarnaerror)) echo "<br><span class=\"error\">$klarnaerror</span></br>";
if ($msg == "googlechargeerror") echo "<br><span class=\"error\">".GOOGLECHARGEERROR."</span></br>";
if ($msg == "sent") {
	echo "<br><span class=\"confirm\">".MESSAGESENT;
	if ($log) echo " <a href=\"../previews/$log\" target=\"_blank\">".VIEWLOG."</a>";
	echo "</span><br>";
}
if ($activate == "true") {
	echo "<br><span class=\"confirm\">".ORDERACTIVATIONCOMPLETED."</span><br>";
	unset($activate);
} else if ($activate) echo "<br><span class=\"confirm\">".ORDERACTIVATIONCOMPLETED." ".YOUCANSENDPAYMENTNOTIFICATION."</span><br>";
if ((!empty($discount) && is_numeric($discount)) || (!empty($pdiscount) && is_numeric($pdiscount))) echo "<br><span class=\"confirm\">".PERSONALDISCOUNTADDED."</span><br>";
echo "<table width=\"700\"><tr><td><form action=\"salesadmin.php?resultpage=$resultpage&admindisplayitems=$admindisplayitems\" method=\"post\" name=\"customerfilterform\" style=\"margin-bottom: 0px;\"><span class=\"text\">".FILTERBYNAME.": <input type=\"text\" name=\"namefilter\" value=\"$namefilter\" size=\"10\"> ".ANDOREMAIL.": <input type=\"text\" name=\"emailfilter\" value=\"$emailfilter\" size=\"10\"> <input type=\"submit\" value=\"".FILTER."\"></span></form></td>";
if (!$recurring) echo "<td align=\"right\"><span class=\"text\">".EXPORTTOCSV.": </span></td><td><form action=\"exportcustomers.php\" method=\"post\" style=\"margin-bottom: 0px;\"><input type=\"submit\" value=\"".DOWNLOAD."\"></form></td>";
echo "</tr></table><br>";
if (!$mailinginprogress) echo "<form action=\"mailcustomer.php\" method=\"post\" name=\"customermailform\" style=\"margin-bottom: 0px;\">";
echo "
      <table width=\"80%\" border=\"0\" cellspacing=\"1\" cellpadding=\"0\" align=\"center\" bgcolor=\"#D0D0D0\">
      <tr class=\"reporthead\">";
if (!$mailinginprogress) echo "<td align=\"left\"><input type=\"checkbox\" name=\"switchall\" onClick=\"selectAll();\"></td>";
echo "<td align=\"left\">".IDNAME."</td><td align=\"left\">".EMAIL."</td>";
if ($partyplanner) echo "<td width=\"130\" nowrap align=\"left\">".PARTIES."</td>";
echo "<td width=\"90\" align=\"center\">".ACTION."</td></tr>";

// Get order information if a reminder should be sent...
if ($remind) {
	$result = @mysqli_query($db, "SELECT * FROM orders WHERE orderid='$remind'");
	$remindcustomer = @mysqli_result($result, 0, "customerid");
	$remindduedate = @mysqli_result($result, 0 , "duedate");
	$remindorderdate = @mysqli_result($result, 0, "date");
	$remindbilldate = @mysqli_result($result, 0, "billdate");
	$reminddescription = @mysqli_result($result, 0, "description");
	// Check for non manual payment options...
	$result = @mysqli_query($db, "SELECT * FROM payoptions WHERE gateway != 'manual'");
	if (@mysqli_num_rows($result)) $remindchangepay = TRUE;
	else $remindchangepay = FALSE;
}

// Get order information if an activation message should be sent...
if ($activate) {
	$result = @mysqli_query($db, "SELECT * FROM orders WHERE orderid='$activate'");
	$activateinvoice = @mysqli_result($result, 0, "invoiceid");
	$activatecustomer = @mysqli_result($result, 0, "customerid");
	$activateorderdate = @mysqli_result($result, 0, "date");
	$activatedescription = @mysqli_result($result, 0, "description");
	$activateamount = @mysqli_result($result, 0, "price");
	if (empty($activateamount)) $activateamount = 0;
	$activatetax = @mysqli_result($result, 0, "tax");
	if (empty($activatetax)) $activatetax = 0;
	$activateshipping = @mysqli_result($result, 0, "shipping");
	if (empty($activateshipping)) $activateshipping = 0;
	$activatesubtotal = $activateamount - $activatetax - $activateshipping;
	if (empty($activatesubtotal)) $activatesubtotal = 0;
}

// Get discount information if a personal storewide discount message should be sent...
if (!empty($discount) && is_numeric($discount)) {
	$result = @mysqli_query($db, "SELECT * FROM storediscounts WHERE discountid='$discount'");
	$discountcustomer = @mysqli_result($result,0,"customerid");
	$discountcode = @mysqli_result($result,0,"code");
	$discounttype = @mysqli_result($result,0,"type");
	$discountvalue = @mysqli_result($result,0,"value");
	if ($discounttype == "$") $discountvalue = $currencysymbols[$ashopcurrency]["pre"].$discountvalue.$currencysymbols[$ashopcurrency]["post"];
	else $discountvalue = $discountvalue."%";
}

// Get discount information if a personal per product discount message should be sent...
if (!empty($pdiscount) && is_numeric($pdiscount)) {
	$discount = $pdiscount;
	$result = @mysqli_query($db, "SELECT * FROM discount WHERE discountid='$discount'");
	$discountcustomer = @mysqli_result($result,0,"customerid");
	$discountcode = @mysqli_result($result,0,"code");
	$discounttype = @mysqli_result($result,0,"type");
	$discountvalue = @mysqli_result($result,0,"value");
	if ($discounttype == "$") $discountvalue = $currencysymbols[$ashopcurrency]["pre"].$discountvalue.$currencysymbols[$ashopcurrency]["post"];
	else $discountvalue = $discountvalue."%";
}

// Get customer information from database...
if ($userid > 1) {
	$sql = "SELECT DISTINCT customer.customerid, customer.* FROM customer, orders WHERE customer.firstname != '' AND customer.email != '' AND orders.customerid=customer.customerid AND orders.userid LIKE '%|$userid|%'";
	if ($namefilter) {
		if (strstr($namefilter," ")) {
			$namefilters = explode(" ",$namefilter);
			$sql .= " AND (";
			$namefiltercounter = 1;
			foreach ($namefilters as $namefilter) {
				if ($namefiltercounter > 1) $sql .= " AND ";
				$sql .= "(firstname  LIKE '%$namefilter%' OR lastname LIKE '%$namefilter%')";
				$namefiltercounter++;
			}
		} else $sql .= " AND ((firstname  LIKE '%$namefilter%' OR lastname LIKE '%$namefilter%')";
		if ($emailfilter) $sql .= " OR (email LIKE '%$emailfilter%' OR username LIKE '%$emailfilter%')";
		$sql .= ")";
	} else if ($emailfilter) $sql .= " AND (email LIKE '%$emailfilter%' OR username LIKE '%$emailfilter%')";
	$sql .= " ORDER BY customerid";
} else {
	if ($recurring) $sql = "SELECT DISTINCT customer.customerid, customer.* FROM customer, orders, emerchant_bills WHERE customer.firstname != '' AND customer.email != '' AND orders.customerid=customer.customerid AND orders.orderid=emerchant_bills.orderid AND emerchant_bills.recurring != ''";
	else $sql = "SELECT * FROM customer WHERE customer.firstname != '' AND customer.email != '' AND customer.password != '' AND customer.password IS NOT NULL";
	if ($namefilter) {
		if (strstr($namefilter," ")) {
			$namefilters = explode(" ",$namefilter);
			$sql .= " AND (";
			$namefiltercounter = 1;
			foreach ($namefilters as $namefilter) {
				if ($namefiltercounter > 1) $sql .= " AND ";
				$sql .= "(firstname  LIKE '%$namefilter%' OR lastname LIKE '%$namefilter%')";
				$namefiltercounter++;
			}
		} else $sql .= " AND ((firstname  LIKE '%$namefilter%' OR lastname LIKE '%$namefilter%')";
		if ($emailfilter) $sql .= " OR (email LIKE '%$emailfilter%' OR username LIKE '%$emailfilter%')";
		$sql .= ")";
	} else if ($emailfilter) $sql .= " AND (email LIKE '%$emailfilter%' OR username LIKE '%$emailfilter%')";
	if ($remind) $sql .= " AND customerid='$remindcustomer'";
	else if ($activate) $sql .= " AND customerid='$activatecustomer'";
	else if ($discount) $sql .= " AND customerid='$discountcustomer'";
	$sql .= " ORDER BY customerid ASC";
}
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
$startpage = $resultpage - 9;
if ($numberofpages - $resultpage < 10) {
	$pagesleft = $numberofpages - $resultpage;
	$startpage = $startpage - (10 - $pagesleft);
}
if ($startpage < 1) $startpage = 1;
$stoprow = $startrow + $admindisplayitems;
@mysqli_data_seek($result, $startrow);
$thisrow = $startrow;
while (($row = @mysqli_fetch_array($result)) && ($thisrow < $stoprow)) {
    $firstname = $row["firstname"];
    $lastname = $row["lastname"];
    $customerid = $row["customerid"];
    $email = $row["email"];
	$allowemail = $row["allowemail"];
	$password = $row["password"];
	$thisrow++;
	echo "<tr class=\"reportline\">";
	if (!$mailinginprogress) {
		echo "<td width=\"20\" align=\"left\">";
		if ($allowemail || $activate) {
			echo "<input type=\"checkbox\" name=\"customer$customerid\"";
			if ($remind || $activate || $discount || $pdiscount) echo " checked";
			echo ">";
		} else echo "&nbsp;";
		echo "</td>";
	}
	echo "<td nowrap align=\"left\"><a href=\"editcustomer.php?customerid=$customerid\">$customerid</a>, $firstname $lastname</td><td align=\"left\">";
	if ($emactive) echo "<a href=\"javascript:newWindow('../emerchant/composemessage.php?customer=$customerid')\">$email</a>";
	else echo "<a href=\"mailto:$email\">$email</a>";
	if ($partyplanner) {
		$activepartiesresult = @mysqli_query($db, "SELECT partyid FROM party WHERE customerid='$customerid' AND (ended='' OR ended='0' OR ended IS NULL) AND approved='1' AND approved IS NOT NULL");
		$activeparties = @mysqli_num_rows($activepartiesresult);
		$endedpartiesresult = @mysqli_query($db, "SELECT partyid FROM party WHERE customerid='$customerid' AND ended='1' AND ended IS NOT NULL");
		$endedparties = @mysqli_num_rows($endedpartiesresult);
		if ($activeparties > 0 || $endedparties > 0) {
			echo "<td align=\"left\">$activeparties ".ACTIVE.", $endedparties ".ENDED." &nbsp;&nbsp;<a href=\"viewparties.php?customerid=$customerid\"><img src=\"images/icon_affiliatereports.gif\" alt=\"".VIEWPARTIES."\" title=\"".VIEWPARTIES."\"></a></td>";
		} else echo "<td>&nbsp;</td>";
	}
	echo "</td><td width=\"90\" nowrap align=\"center\"><a href=\"editcustomer.php?customerid=$customerid\"><img src=\"images/icon_profile.gif\" alt=\"".PROFILEFOR." $customerid\" title=\"".PROFILEFOR." $customerid\" border=\"0\"></a>&nbsp;<a href=\"salesreport.php?customerid=$customerid&generate=true\"><img src=\"images/icon_history.gif\" alt=\"".SALESHISTORYFOR." $customerid\" title=\"".SALESHISTORYFOR." $customerid\" border=\"0\"></a>";
	if ($userid == "1") echo "&nbsp;<a href=\"editstorediscounts.php?customerid=$customerid\"><img src=\"images/icon_discount.gif\" alt=\"".PERSONALDISCOUNTSFOR." $customerid\" title=\"".PERSONALDISCOUNTSFOR." $customerid\" border=\"0\"></a>&nbsp;<a href=\"editcustomer.php?customerid=$customerid&remove=True\"><img src=\"images/icon_trash.gif\" alt=\"".DELETECUSTOMER." $customerid ".FROMDB."\" title=\"".DELETECUSTOMER." $customerid ".FROMDB."\" border=\"0\"></a>";
	echo "</td></tr>";
}

echo "</table>\n";
if ($numberofrows > 5) {
	echo "<table width=\"100%\" border=\"0\" cellpadding=\"5\" cellspacing=\"0\"><tr><td align=\"center\"><font face=\"Arial, Helvetica, sans-serif\" size=\"2\">";
	if ($numberofpages > 1) {
		echo "<b>".PAGE.": </b>";
		if ($resultpage > 1) {
			if ($resultpage > 10) echo "<a href=\"salesadmin.php?recurring=$recurring&resultpage=1&admindisplayitems=$admindisplayitems&namefilter=$namefilter&emailfilter=$emailfilter\"><b>".FIRSTPAGE."</b></a> ";
			$previouspage = $resultpage-1;
			echo "<<<a href=\"salesadmin.php?recurring=$recurring&resultpage=$previouspage&admindisplayitems=$admindisplayitems&namefilter=$namefilter&emailfilter=$emailfilter\"><b>".PREVIOUS."</b></a>&nbsp;&nbsp;";
		}
		$page = 1;
		for ($i = $startpage; $i <= $numberofpages; $i++) {
			if ($page > 20) break;
			if ($i != $resultpage) echo "<a href=\"salesadmin.php?recurring=$recurring&resultpage=$i&admindisplayitems=$admindisplayitems&namefilter=$namefilter&emailfilter=$emailfilter\">";
			else echo "<span style=\"font-size: 18px;\">";
			echo "$i";
			if ($i != $resultpage) echo "</a>";
			else echo "</span>";
			echo "&nbsp;&nbsp;";
			$page++;
		}
		if ($resultpage < $numberofpages) {
			$nextpage = $resultpage+1;
			echo "<a href=\"salesadmin.php?recurring=$recurring&resultpage=$nextpage&admindisplayitems=$admindisplayitems&namefilter=$namefilter&emailfilter=$emailfilter\"><b>".NEXTPAGE."</b></a>>>";
		}
		if ($resultpage < ($numberofpages - 10)) echo " <a href=\"salesadmin.php?recurring=$recurring&resultpage=$numberofpages&admindisplayitems=$admindisplayitems&namefilter=$namefilter&emailfilter=$emailfilter\"><b>".LASTPAGE."</b></a> &nbsp;&nbsp;";
	}
	echo " ".DISPLAY.": <select name=\"admindisplayitems\" onChange=\"document.location.href='salesadmin.php?recurring=$recurring&resultpage=$resultpage&namefilter=$namefilter&emailfilter=$emailfilter&admindisplayitems='+customermailform.admindisplayitems.value;\"><option value=\"$numberofrows\">".SELECT."</option><option value=\"5\">5</option><option value=\"10\">10</option><option value=\"20\">20</option><option value=\"40\">40</option><option value=\"$numberofrows\">".ALL."</option></select> ".CUSTOMERS2."</td></tr></table>
	";
}

if (!$mailinginprogress) {
	echo "<table align=\"center\" cellpadding=\"10\"><tr class=\"formtitle\"><td><tr><td align=\"center\"><p>".SUBJECT.": <input type=\"text\" name=\"subject\" size=\"40\"";
	if ($remind) echo " value=\"".REMINDERTOPAY." $remind\"";
	else if ($activate) echo " value=\"".PAYMENTFORORDERID.": $activateinvoice ".HASBEENRECEIVED."\"";
	else if ($discount) echo " value=\"".YOURPERSONALDISCOUNT." $ashopname\"";
	echo "></p><p>".MESSAGE.":<br><textarea name=\"message\" cols=\"60\" rows=\"10\">";
	if ($remind) {
		echo DEAR." $firstname $lastname,\n\n".THISISAREMINDERTOPAY." $remind.\n";
		if ($remindbilldate && $remindduedate) echo DUEDATE.": $remindduedate\n\n";
		if ($remindbilldate) echo "\n".TOVIEWINVOICEDETAILS."\n$ashopurl/payment.php?invoice=$remind\n";
		else if ($remindchangepay) echo "\n".TOVIEWANDCHANGE."\n$ashopurl/payment.php?invoice=$remind\n\n";
		else echo "\n".ORDERDESCRIPTION.": $reminddescription\n\n";
		echo "\n".PLEASECONTACTUSFORASSISTANCE."\n\n".THANKYOU."\n$ashopname\n$ashopaddress\n$ashopphone";
	} else if ($activate) {
		echo DEAR." $firstname $lastname,\n\n".WEHAVENOWRECEIVEDPAYMENT." $activateinvoice.\n".ORDERDESCRIPTION.":\n$activatedescription\n\n".PRICE.": ".$currencysymbols[$ashopcurrency]["pre"].number_format($activatesubtotal,2,'.','').$currencysymbols[$ashopcurrency]["post"]."\n".TAX.": ".$currencysymbols[$ashopcurrency]["pre"].number_format($activatetax,2,'.','').$currencysymbols[$ashopcurrency]["post"]."\n".SHIPPING.": ".$currencysymbols[$ashopcurrency]["pre"].number_format($activateshipping,2,'.','').$currencysymbols[$ashopcurrency]["post"]."\n".TOTALAMOUNT.": ".$currencysymbols[$ashopcurrency]["pre"].number_format($activateamount,2,'.','').$currencysymbols[$ashopcurrency]["post"]."\n\n".THANKYOU."\n$ashopname\n$ashopaddress";
		if ($ashopphone) echo "\n$ashopphone";
	} else if ($discount) {
		echo DEAR." $firstname $lastname,\n\n".WEHAVECREATEDPERSONALDISCOUNT."\n$ashopurl.\n\n".IFYOUENTERTHECODE.": $discountcode\n".WHENCHECKINGOUT."\n$discountvalue ".DISCOUNT."\n\n".GREETINGSFROM."\n$ashopname";
	}
	echo "</textarea><br><span class=\"sm\">[".SUPPORTSCODES.": %firstname%, %lastname%, %email%, %address%, %state%,<br>%zip%, %city%, %country%, %phone%]</p><p><input type=\"radio\" name=\"mailformat\" value=\"html\"";
	if ($prefcusmailformat == "html") echo " checked";
	echo "> ".HTMLFORMAT." <input type=\"radio\" name=\"mailformat\" value=\"text\"";
	if ($prefcusmailformat == "text" || !$prefcusmailformat) echo "checked";
	echo "> ".PLAINTEXT."</p><p><input type=\"hidden\" name=\"resultpage\" value=\"$resultpage\"><input type=\"hidden\" name=\"displayitems\" value=\"$admindisplayitems\"><input type=\"hidden\" name=\"emailfilter\" value=\"$emailfilter\"><input type=\"hidden\" name=\"namefilter\" value=\"$namefilter\"><input type=\"hidden\" name=\"recurring\" value=\"$recurring\"><input type=\"submit\" class=\"widebutton\" name=\"mail\" value=\"".MAILTOSELECTED."\">";
	if ($remind) echo "<input type=\"hidden\" name=\"msg\" value=\"remindersent\">";
	else if ($activate) echo "<input type=\"hidden\" name=\"msg\" value=\"activated\">";
	if ($salesreport) echo "<input type=\"hidden\" name=\"salesreport\" value=\"$salesreport\">";
	if (!$remind && !$activate && $userid == "1") echo " <input type=\"submit\" class=\"widebutton\" name=\"mailall\" value=\"".MAILTOALL."\">";
	echo "</p></form></td></tr></table>";
}
echo "</center>$footer";
?>