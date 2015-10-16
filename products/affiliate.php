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

// Parse RSS encoded URL...
if (strpos($id,"|")) {
	$redirect = strtolower(substr($id,strpos($id,"|")+1));
	$redirect = str_replace("redirect=","",$redirect);
	$id = substr($id,0,strpos($id,"|"));
}

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
include "language/$lang/affiliate.inc.php";

// Get member template path if no theme is used...
if ($shop && $shop != "1" && $usethemetemplates != "true" && file_exists("$ashoppath/members/files/$ashopuser/catalogue.html")) $templatepath = "/members/files/$ashopuser";

// Get PAP parameters if applicable...
if ($pappath && file_exists("$pappath/accounts/settings.php")) {
	$fp = fopen ("$pappath/accounts/settings.php", "r");
	while (!feof($fp)) {
		$buffer = fgets($fp,128);
		if (strpos($buffer, "DB_HOSTNAME") == 0 && is_integer(strpos($buffer, "DB_HOSTNAME"))) {
			$paphost = str_replace("DB_HOSTNAME=","",$buffer);
			$paphost = trim($paphost);
		}
		if (strpos($buffer, "DB_USERNAME") == 0 && is_integer(strpos($buffer, "DB_USERNAME"))) {
			$papuser = str_replace("DB_USERNAME=","",$buffer);
			$papuser = trim($papuser);
		}
		if (strpos($buffer, "DB_PASSWORD") == 0 && is_integer(strpos($buffer, "DB_PASSWORD"))) {
			$pappass = str_replace("DB_PASSWORD=","",$buffer);
			$pappass = trim($pappass);
		}
		if (strpos($buffer, "DB_DATABASE") == 0 && is_integer(strpos($buffer, "DB_DATABASE"))) {
			$papname = str_replace("DB_DATABASE=","",$buffer);
			$papname = trim($papname);
		}
		if (strpos($buffer, "serverName") == 0 && is_integer(strpos($buffer, "serverName"))) {
			$papdomain = str_replace("serverName=","",$buffer);
			$papdomain = trim($papdomain);
		}
		if (strpos($buffer, "baseServerUrl") == 0 && is_integer(strpos($buffer, "baseServerUrl"))) {
			$papuri = str_replace("baseServerUrl=","",$buffer);
			$papuri = trim($papuri);
		}
		$papurl = "http://".$papdomain.$papuri;
	}
	fclose ($fp);
	$papdb = @mysqli_connect("$paphost", "$papuser", "$pappass", "$papname");
	$result = @mysqli_query($papdb, "SELECT authid FROM qu_g_users WHERE roleid='pap_merc'");
	$papadminuserid = @mysqli_result($result,0,"authid");
	$result = @mysqli_query($papdb, "SELECT username,rpassword FROM qu_g_authusers WHERE authid='$papadminuserid'");
	$papadminusername = @mysqli_result($result,0,"username");
	$papadminpassword = @mysqli_result($result,0,"rpassword");
	@mysqli_close($papdb);

	// Start a PAP API session...
	if (!empty($id) || !empty($referrer)) {
		require_once "$pappath/api/PapApi.class.php";
		$papsession = new Gpf_Api_Session("$papurl/scripts/server.php");
		$papsession->login($papadminusername, $papadminpassword);
	}
}

// Open database...
   $db = @mysqli_connect("$databaseserver", "$databaseuser", "$databasepasswd", "$databasename");
   if (!$db) $error = 1;

   $redirect = str_replace("|","&",$redirect);

    // Set current date and time...
	$date = date("Y-m-d H:i:s", time()+$timezoneoffset);

	// Initialize variables...
	$papaffid = "";

	// Validate variables...
	if (isset($id) && !is_numeric($id) && empty($papsession)) unset($id);
	if (isset($referrer) && !is_numeric($referrer)) {
		$referrer = stripslashes($referrer);
		$referrer = @mysqli_real_escape_string($db, $referrer);
		$referrer = strtolower($referrer);
		$referrer = str_replace("\'","",$referrer);
		$referrer = str_replace("\"","",$referrer);
		$referrer = str_replace("/","",$referrer);
		$referrer = str_replace("\n","",$referrer);
		$referrer = str_replace(";","",$referrer);
		$referrer = str_replace("select","",$referrer);
		$referrer = str_replace("insert","",$referrer);
		$referrer = str_replace("update","",$referrer);
		$referrer = str_replace("delete","",$referrer);
		$referrer = str_replace("create","",$referrer);
		$referrer = str_replace("modify","",$referrer);
		$referrer = str_replace("password","",$referrer);
		$referrer = str_replace("user","",$referrer);
		$referrer = str_replace("concat","",$referrer);
		$referrer = str_replace("from","",$referrer);
		$referrer = str_replace("username","",$referrer);
	}

   // Check if affiliateID is in the database...
   if($referrer) {
	   if (!empty($pappath) && !empty($papurl) && !empty($papadminusername) && !empty($papadminpassword)) {
		   $papaffid = "";
		   //try { 
			   $papaffiliate = new Pap_Api_Affiliate($papsession); 
		   //} catch (Exception $e) { }
		   if (is_object($papaffiliate)) {
			   $papaffiliate->setRefid($referrer);
			   //try {
				   $papaffiliate->load();
				   $papaffid = $papaffiliate->getRefid();
				   $id = $papaffid;
			   //} catch (Exception $e) { }
		   }
		   if (empty($papaffid)) {
			   if ($lang != $defaultlanguage && file_exists("$ashoppath$templatepath/cart-$lang.html")) ashop_showtemplateheader("$ashoppath$templatepath/cart-$lang.html");
			   else ashop_showtemplateheader("$ashoppath$templatepath/cart.html");
			   echo "<table class=\"ashopmessagetable\">
			   <tr align=\"center\"><td><br><br><p><span class=\"ashopmessageheader\">".INCORRECT."</span></p>
			   <p><span class=\"ashopmessage\">".TRYAGAIN."</span></p>
			   <p><form action=\"affiliate.php\" method=\"post\"><input type=\"text\" name=\"referrer\" size=\"15\"><input type=\"submit\" 
			   value=\"Submit\"></form></p>
			   <p><span class=\"ashopmessage\">".STRAIGHTTO."
			   <a href=\"";
			   if ($redirect) echo $redirect;
			   else echo $affiliateredirect;
			   echo "\">$ashopname</a></span></td></tr></table>";
			   if ($lang != $defaultlanguage && file_exists("$ashoppath$templatepath/cart-$lang.html")) ashop_showtemplatefooter("$ashoppath$templatepath/cart-$lang.html");
			   else ashop_showtemplatefooter("$ashoppath$templatepath/cart.html");
			   exit;
		   }
	   } else {
		   $result = @mysqli_query($db, "SELECT affiliateid FROM affiliate WHERE referralcode='$referrer'");
		   if (!@mysqli_num_rows($result)) {
			   if ($lang != $defaultlanguage && file_exists("$ashoppath$templatepath/cart-$lang.html")) ashop_showtemplateheader("$ashoppath$templatepath/cart-$lang.html");
			   else ashop_showtemplateheader("$ashoppath$templatepath/cart.html");
			   echo "<table class=\"ashopmessagetable\">
			   <tr align=\"center\"><td><br><br><p><span class=\"ashopmessageheader\">".INCORRECT."</span></p>
			   <p><span class=\"ashopmessage\">".TRYAGAIN."</span></p>
			   <p><form action=\"affiliate.php\" method=\"post\"><input type=\"text\" name=\"referrer\" size=\"15\"><input type=\"submit\" 
			   value=\"Submit\"></form></p>
			   <p><span class=\"ashopmessage\">".STRAIGHTTO."
			   <a href=\"";
			   if ($redirect) echo $redirect;
			   else echo $affiliateredirect;
			   echo "\">$ashopname</a></span></td></tr></table>";
			   if ($lang != $defaultlanguage && file_exists("$ashoppath$templatepath/cart-$lang.html")) ashop_showtemplatefooter("$ashoppath$templatepath/cart-$lang.html");
			   else ashop_showtemplatefooter("$ashoppath$templatepath/cart.html");
			   exit;
		   }
	   }
   } else {
	   if (!empty($pappath) && !empty($papurl) && !empty($papadminusername) && !empty($papadminpassword) && !empty($papsession)) {
		   $papaffid = "";
		   $papaffiliate = new Pap_Api_Affiliate($papsession);
		   $papaffiliate->setRefid($id);
		   //try {
			   $papaffiliate->load();
			   $papaffid = $papaffiliate->getRefid();
			   $id = $papaffid;
		   //} catch (Exception $e) { }
	   } else $result = @mysqli_query($db, "SELECT affiliateid FROM affiliate WHERE affiliateid='$id'");
   }
   if (@mysqli_num_rows($result) == 0 && empty($papaffid)) {
	   if ($lang != $defaultlanguage && file_exists("$ashoppath$templatepath/cart-$lang.html")) ashop_showtemplateheader("$ashoppath$templatepath/cart-$lang.html");
	   else ashop_showtemplateheader("$ashoppath$templatepath/cart.html");
	   echo "<table class=\"ashopmessagetable\">
	     <tr align=\"center\"><td><br><br><p><span class=\"ashopmessageheader\">".WEREYOUREFERRED."</span></p>
	     <p><span class=\"ashopmessage\">".ENTERREFERRAL."</span></p>
		 <p><form action=\"affiliate.php\" method=\"post\"><input type=\"text\" name=\"referrer\" size=\"15\"><input type=\"hidden\" name=\"redirect\" value=\"$redirect\"><input type=\"submit\" value=\"Submit\"></form></p>
		 <p><span class=\"ashopmessage\">".STRAIGHTTO."  
		 <a href=\"";
	   if ($redirect) echo $redirect;
	   else echo $affiliateredirect;
	   echo "\">$ashopname</a></span></td></tr></table>";
		 if ($lang != $defaultlanguage && file_exists("$ashoppath$templatepath/cart-$lang.html")) ashop_showtemplatefooter("$ashoppath$templatepath/cart-$lang.html");
		 else ashop_showtemplatefooter("$ashoppath$templatepath/cart.html");
	   exit;
   } else {
	   if (empty($papaffid)) {
		   if(!$id) $id = @mysqli_result($result, 0, "affiliateid");
		   $sql="SELECT clicks FROM affiliate WHERE affiliateid='$id'";
		   $result = @mysqli_query($db, "$sql");
		   $clicks = @mysqli_result($result, 0, "clicks");
		   $clicks++;
		   $sql = "UPDATE affiliate SET clicks='$clicks', lastdate='$date' WHERE affiliateid='$id'";
		   $result = @mysqli_query($db, "$sql");
		   $httpreferer = $_SERVER["HTTP_REFERER"];
		   if(substr($httpreferer,0,strlen($ashopurl)) == $ashopurl) $httpreferer = "";
		   if(substr($httpreferer,0,strlen($ashopsurl)) == $ashopsurl) $referer = "";
		   $httpreferer = @mysqli_real_escape_string($db, $httpreferer);
		   if(!empty($httpreferer)) {
			   $result = @mysqli_query($db, "SELECT clicks FROM affiliatereferer WHERE affiliateid='$id' AND referer='$httpreferer'");
			   if (@mysqli_num_rows($result)) {
				   $refererclicks = @mysqli_result($result,0,"clicks");
				   $refererclicks++;
				   @mysqli_query($db, "UPDATE affiliatereferer SET clicks='$refererclicks' WHERE affiliateid='$id' AND referer='$httpreferer'");
			   } else @mysqli_query($db, "INSERT INTO affiliatereferer (affiliateid,referer,clicks) VALUES ('$id','$httpreferer','1')");
		   }
	   }

	 // Set referral discount cookies...
	 if (!empty($papaffid)) $result = @mysqli_query($db, "SELECT * FROM storediscounts WHERE affiliate='$papaffid'");
	 else $result = @mysqli_query($db, "SELECT * FROM storediscounts WHERE code='$referrer'");
	 if (@mysqli_num_rows($result)) {
		 $discountall = @mysqli_result($result, 0, "discountid");
		 if (!empty($discountall)) setcookie("discountall","$discountall");
	 }

	 // Set tracking cookie...
	 $year = date("Y",time());
	 $year += 20;
	 setcookie("affiliate","$id", mktime(0,0,0,12,1,$year), "/");

	 // Set referral date cookie for time based referral...
	 if (!empty($referrallength) && is_numeric($referrallength)) {
		 $referraldate = date("Ymd",time());
		 setcookie("referral","$referraldate", mktime(0,0,0,12,1,$year), "/");
	 }

	 if (!empty($papaffid)) {
		 $_GET["a_aid"] = $papaffid;
		 $clickTracker = new Pap_Api_ClickTracker($papsession);
		 //try {  
			 $clickTracker->track();
			 $clickTracker->saveCookies();
		 //} catch (Exception $e) { }
	 }
	 if (!$error) {
		 if ($redirect) {
			 if ($referrer) {
				 if ($lang != $defaultlanguage && file_exists("$ashoppath$templatepath/cart-$lang.html")) ashop_showtemplateheader("$ashoppath$templatepath/cart-$lang.html");
				 else ashop_showtemplateheader("$ashoppath$templatepath/cart.html");
				 echo "<table class=\"ashopmessagetable\"><tr align=\"center\"><td><br><br><span class=\"ashopmessageheader\">".THANKYOU."</span><br><br><span class=\"ashopmessage\">".REDIRECTED."
$ashopname.</span><br><meta http-equiv=\"Refresh\" content=\"3; URL=$redirect\"><br><br><span class=\"ashopmessage\">".IFNOREDIRECT."<a href=\"$redirect\">".HERE."</a>.</span></td></tr></table>";
				 if ($lang != $defaultlanguage && file_exists("$ashoppath$templatepath/cart-$lang.html")) ashop_showtemplatefooter("$ashoppath$templatepath/cart-$lang.html");
				 else ashop_showtemplatefooter("$ashoppath$templatepath/cart.html");
				 exit;
			 } else if (strstr($SERVER_SOFTWARE, "IIS") || !empty($papaffid)) {
				 echo "<html><head><meta http-equiv=\"Refresh\" content=\"0; URL=$redirect\"></head></html>";
				 exit;
			 } else header("Location: $redirect");
		 } else {
			 if ($referrer) {
				 if ($lang != $defaultlanguage && file_exists("$ashoppath$templatepath/cart-$lang.html")) ashop_showtemplateheader("$ashoppath$templatepath/cart-$lang.html");
				 else ashop_showtemplateheader("$ashoppath$templatepath/cart.html");
				 echo "<table class=\"ashopmessagetable\"><tr align=\"center\"><td><br><br><span class=\"ashopmessageheader\">".THANKYOU."</span><br><br><span class=\"ashopmessage\">".REDIRECTED." 
$ashopname.</span><br></center><meta http-equiv=\"Refresh\" content=\"3; URL=$affiliateredirect\"><br><br><span class=\"ashopmessage\">".IFNOREDIRECT."<a href=\"$affiliateredirect\">".HERE."</a>.</span></td></tr></table>";
				 if ($lang != $defaultlanguage && file_exists("$ashoppath$templatepath/cart-$lang.html")) ashop_showtemplatefooter("$ashoppath$templatepath/cart-$lang.html");
				 else ashop_showtemplatefooter("$ashoppath$templatepath/cart.html");
				 exit;
			 } else if (strstr($SERVER_SOFTWARE, "IIS") || !empty($papaffid)) {
				 echo "<html><head><meta http-equiv=\"Refresh\" content=\"0; URL=$affiliateredirect\"></head></html>";
				 exit;
			 } else header("Location: $affiliateredirect");
		 }
	 } else {
		 if (strstr($SERVER_SOFTWARE, "IIS")) {
			 echo "<html><head><meta http-equiv=\"Refresh\" content=\"0; URL=index.php?error=$error\"></head></html>";
			 exit;
		 } else header("Location: index.php?error=$error");
	 }
   }
?>