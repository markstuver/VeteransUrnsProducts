<?php
// AShop
// Copyright 2015 - AShop Software - http://www.ashopsoftware.com
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
// Get context help for this page...
$contexthelppage = "editcontent";
include "help.inc.php";
include "template.inc.php";
// Get language module...
include "language/$adminlang/editcontent.inc.php";

   $db = @mysqli_connect("$databaseserver", "$databaseuser", "$databasepasswd", "$databasename");

if (!$description) {
	$description = SAMPLECONTENTITEM;

  $sql="SELECT name FROM category WHERE categoryid = $cat";
  $result = @mysqli_query($db,$sql);
  $categoryname = @mysqli_result($result, $j, "name");
  echo "$header";
	  if (is_dir("$ashoppath/admin/ckeditor") && file_exists("$ashoppath/admin/ckeditor/ckeditor.js")) {
		  echo "
<script type=\"text/javascript\" src=\"ckeditor/ckeditor.js\"></script>
";
	  }
	  echo "
        <div class=\"heading\">";
		if ($firstpage == "true") echo ADDFIRSTPAGE;
		else if ($aboutpage == "true") echo ADDABOUTPAGE;
		else if ($termspage == "true") echo ADDTERMSPAGE;
		else if ($privacypage == "true") echo ADDPRIVACYPAGE;
		else if ($cat == "pages") echo ADDPAGE;
		else echo ADDCONTENTTOCATEGORY.": $categoryname";
		echo "</div>
        <table cellpadding=\"10\" align=\"center\"><tr><td align=\"center\"><form action=\"addcontent.php\" method=\"post\" name=\"productform\">
       <table width=\"700\" border=\"0\" cellspacing=\"0\" cellpadding=\"5\" bgcolor=\"#F0F0F0\">";
	   if ($cat != "pages") {
		   echo "<tr><td align=\"right\" class=\"formlabel\" width=\"120\"><a href=\"javascript:;\" onMouseOut=\"MM_swapImgRestore()\" onMouseOver=\"MM_swapImage('Image5','','images/contexthelpicon_over.gif',1)\"><img src=\"images/contexthelpicon.gif\" width=\"14\" height=\"15\" border=\"0\" name=\"Image5\" align=\"absmiddle\" onclick=\"return overlib('$tip5');\" onmouseout=\"return nd();\"></a> ".CATALOGSTATUS.":</td><td class=\"formlabel\"><input type=\"checkbox\" name=\"active\" checked> ".ACTIVE;
		   if($wholesalecatalog) echo " (".RETAIL.") <input type=\"checkbox\" name=\"wholesale\" checked> ".WHOLESALE;
		   echo "</td></tr>";
	   } else {
		   // Generate Digital Mall member list if needed...
		   if ($userid == "1" && file_exists("$ashoppath/members/index.php") && $digitalmall != "OFF") {
			   $memberlist = "<select name=\"memberid\"><option value=\"1\">".ADMINISTRATOR;
			   $result = @mysqli_query($db,"SELECT * FROM user WHERE userid>1 ORDER BY shopname");
			   while ($row = @mysqli_fetch_array($result)) {
				   $memberlist .= "<option value=\"{$row["userid"]}\"";
				   if ($row["userid"] == $parentowner) $memberlist .= " selected";
				   $memberlist .= ">{$row["shopname"]}
				   ";
			   }
			   $memberlist .= "</select>
			   ";
		   }

		   // Generate language list...
		   $languagelist = "<select name=\"nlanguage\"><option value=\"any\">".ANY;
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
			   foreach ($languages as $langmodule=>$langname) $languagelist .= "<option value=\"$langmodule\">$langname</option>";
		   }
		   echo "
		   <script language=\"JavaScript\" type=\"text/javascript\">
		   function updatefields() {
			   if (document.getElementById('typeselector').value == 'generic') {
				   document.getElementById('caption').style.display = '';
				   document.getElementById('url').style.display = 'none';
				   document.getElementById('contentbox').style.display = '';
			   } else if (document.getElementById('typeselector').value == 'link') {
				   document.getElementById('caption').style.display = '';
				   document.getElementById('url').style.display = '';
				   document.getElementById('contentbox').style.display = 'none';
			   } else {
				   document.getElementById('caption').style.display = 'none';
				   document.getElementById('url').style.display = 'none';
				   document.getElementById('contentbox').style.display = '';
			   }
		   }
		   </script>
		   <tr><td align=\"right\" class=\"formlabel\" width=\"120\">".PAGETYPE.":</td><td align=\"left\"><select id=\"typeselector\" name=\"pagetype\" onChange=\"updatefields();\"><option value=\"generic\">".GENERIC."</option><option value=\"link\">".EXTERNALLINK."</option><option value=\"firstpage\">".WELCOMEMESSAGE."</option><option value=\"firstpagemobile\">".WELCOMEMESSAGEMOBILE."</option><option value=\"about\">".ABOUTUS."</option><option value=\"terms\">".TERMS."</option><option value=\"privacy\">".PRIVACY."</option></select></td></tr>";
		   if ($userid == "1" && file_exists("$ashoppath/members/index.php") && $digitalmall != "OFF") echo "<tr><td align=\"right\" class=\"formlabel\" width=\"120\">".OWNER.":</td><td align=\"left\">$memberlist</td></tr>";
		   echo "<tr><td align=\"right\" class=\"formlabel\" width=\"120\">".LANGUAGE.":</td><td align=\"left\">$languagelist</td></tr>
		   <tr id=\"caption\"><td align=\"right\" class=\"formlabel\" width=\"120\">".CAPTION.":</td><td align=\"left\"><input type=\"text\" name=\"ncaption\" size=\"70\"></td></tr>
		   <tr id=\"url\" style=\"display: none;\"><td align=\"right\" class=\"formlabel\" width=\"120\">".URL.":</td><td><input type=\"text\" name=\"nurl\" size=\"70\"></td></tr>	   
		   ";

	   }
	   if (!is_dir("$ashoppath/admin/ckeditor") || !file_exists("$ashoppath/admin/ckeditor/ckeditor.js")) echo "<tr id=\"contentbox\"><td align=\"right\" class=\"formlabel\" valign=\"top\"><br><br><a href=\"javascript:;\" onMouseOut=\"MM_swapImgRestore()\" onMouseOver=\"MM_swapImage('Image9','','images/contexthelpicon_over.gif',1)\"><img src=\"images/contexthelpicon.gif\" width=\"14\" height=\"15\" border=\"0\" name=\"Image9\" align=\"absmiddle\" onclick=\"return overlib('$tip9');\" onmouseout=\"return nd();\"></a> ".DESCRIPTION.":</td><td align=\"left\"><br>
	   ";
	   else echo "<tr id=\"contentbox\"><td colspan=\"2\" align=\"center\"><br>";
	   echo "<textarea name=\"description\" id=\"id_description\" class=\"ckeditor\" cols=\"";
	   if (is_dir("$ashoppath/admin/ckeditor") && file_exists("$ashoppath/admin/ckeditor/ckeditor.js")) echo "80";
	   else echo "67";
	   echo "\" rows=\"22\">$description</textarea></td></tr>
	   <tr><td>&nbsp;</td><td align=\"right\"><input type=\"hidden\" name=\"cat\" value=\"$cat\"><input type=\"hidden\" name=\"resultpage\" value=\"$resultpage\"><input type=\"hidden\" name=\"search\" value=\"$search\"><input type=\"hidden\" name=\"firstpage\" value=\"$firstpage\"><input type=\"hidden\" name=\"aboutpage\" value=\"$aboutpage\"><input type=\"hidden\" name=\"termspage\" value=\"$termspage\"><input type=\"hidden\" name=\"privacypage\" value=\"$privacypage\"><input type=\"submit\" value=\"".SUBMIT."\" name=\"submitbutton\">&nbsp;&nbsp;&nbsp;</td></tr></table></form></td></tr></table>";
	   echo $footer;
} else {
   if ($pagetype == "firstpage") $productname = "AShopFirstPage";
   else if ($pagetype == "firstpagemobile") $productname = "AShopFirstPageMobile";
   else if ($pagetype == "about") $productname = "AShopAboutPage";
   else if ($pagetype == "terms") $productname = "AShopTermsPage";
   else if ($pagetype == "privacy") $productname = "AShopPrivacyPage";
   else if ($pagetype == "generic" || $pagetype == "link") $productname = $ncaption;
   else $productname = "AShopContent";
   if ($pagetype == "link") $description = "";
   if (!isset($memberid) || !is_numeric($memberid)) $memberid = $userid;
   $sql="INSERT INTO product (name,description, userid, prodtype, detailsurl, language) VALUES ('$productname','$description','$memberid','content','$nurl','$nlanguage')";
   $result = @mysqli_query($db,$sql);
   $product_id = @mysqli_insert_id($db);
   $sql="UPDATE product SET ordernumber='$product_id'";
   if ($userid != "1" && $memberactivate) $active = "on";
   if ($active == "on") $sql.=", active='1'";
   else $sql.=", active='0'";
   if ($wholesale == "on") $sql.=", wholesaleactive='1'";
   else $sql.=", wholesaleactive='0'";
   $sql.=" WHERE productid='$product_id'";
   $result = @mysqli_query($db,$sql);

   if (isset($cat) && is_numeric($cat)) {
	   $sql="INSERT INTO productcategory (productid,categoryid) VALUES ($product_id,$cat)";
	   $result = @mysqli_query($db,$sql);
   }

   if ($userid != "1" && !$memberactivate) {
	   $result = @mysqli_query($db,"SELECT prefvalue FROM preferences WHERE prefname='ashopname'");
	   $ashopname = @mysqli_result($result, 0, "prefvalue");
	   $result = @mysqli_query($db,"SELECT * FROM user WHERE userid='$userid'");
	   $membershopname = @mysqli_result($result, 0, "shopname");
	   $membershopemail = @mysqli_result($result, 0, "email");
	   $message="<html><head><title>$ashopname - ".MEMBERCONTENTACTIVATION."</title></head><body><font face=\"$font\"><p>".MEMBER." <b>$userid: $membershopname</b> ".HASADDEDNEW." <a href=\"$ashopurl/admin/login.php?prodactivate=$product_id\">".VERIFYCONTENT."</a></p></font></body></html>";
	   $headers = "From: ".un_html($membershopname)."<$membershopemail>\nX-Sender: <$membershopemail>\nX-Mailer: PHP\nX-Priority: 3\nReturn-Path: <$membershopemail>\nMIME-Version: 1.0\nContent-Type: text/html; charset=iso-8859-1\n";

	   @ashop_mail("$ashopemail",un_html($ashopname)." - ".MEMBERCONTENTACTIVATION,"$message","$headers");
   }

   if ($error) header ("Location: editcatalogue.php?cat=$cat&error=$error&resultpage=$resultpage&search=$search");
   else header ("Location: editcatalogue.php?cat=$cat&pid=$pid&search=$search&resultpage=$resultpage");
}
?>