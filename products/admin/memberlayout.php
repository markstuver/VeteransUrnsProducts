<?php
// AShop
// Copyright 2002-2014 - All Rights Reserved Worldwide
// http://www.ashopsoftware.com
// This software is licensed per individual site.
// By installing or using this software, you agree to the licensing terms,
// which are located at http://www.ashopsoftware.com/license.htm
// Unauthorized use or distribution of this software
// is a violation U.S. and international copyright laws.

include "checklicense.inc.php";
include "checklogin.inc.php";

if ($cancel) {
	header("Location: index.php");
	exit;
}
include "template.inc.php";
// Get language module...
include "language/$adminlang/members.inc.php";
include "ashopconstants.inc.php";

if ($userid == "1") {
	header("Location: configure.php?param=layout");
	exit;
}

if (!$membershops) {
	header("Location: editmember.php");
	exit;
}

// Handle uploaded logo file...
if (is_uploaded_file($imgfile)) {
	$fileinfo = pathinfo("$imgfile_name");
	$extension = $fileinfo["extension"];
	if ($extension == "gif") {
		move_uploaded_file($imgfile, "$ashoppath/members/files/$username/logo.gif");
		@chmod("$ashoppath/members/files/$username/logo.gif", 0777);
	}
}

// Open database connection if needed...
if ($changeconfig) {
	$db = @mysqli_connect("$databaseserver", "$databaseuser", "$databasepasswd", "$databasename");
}

if (strpos($header, "body") != 0) {
	$newheader = substr($header,1,strpos($header, "body")+3);
	$newheader .= " onUnload=\"closemessage()\" ".substr($header,strpos($header, "body")+4,strlen($header));
} else {
	$newheader = substr($header,1,strpos($header, "BODY")+3);
	$newheader .= " onUnload=\"closemessage()\" ".substr($header,strpos($header, "BODY")+4,strlen($header));
}

if (!$changeconfig) {
        echo "$newheader<script language=\"JavaScript\">
		function uploadmessage() 
		{
		  if (document.configurationform.imgfile.value != '') w = window.open('uploadmessage.html','_blank','toolbar=no,location=no,width=350,height=150');
	    }
        function closemessage()
        {
       	  if (typeof w != 'undefined') w.close();
        }
		function colorpicker(formname,fieldname) 
		{
		  w = window.open('colors.php?form='+formname+'&field='+fieldname,'_blank','toolbar=no,location=no,width=450,height=100');
	    }
		function fontselect(formname,fieldname) 
		{
		  w = window.open('fonts.php?form='+formname+'&field='+fieldname,'_blank','toolbar=no,location=no,width=350,height=200');
	    }
        </script>
<div class=\"heading\">".LAYOUT."</div>
        <table align=\"center\" cellpadding=\"10\"><tr><td>
        <form action=\"memberlayout.php?changeconfig=1\" method=\"post\" name=\"configurationform\" enctype=\"multipart/form-data\">
		<table width=\"600\" align=\"center\" border=\"0\" cellspacing=\"0\" cellpadding=\"3\" bgcolor=\"#F0F0F0\">";
}

if (!$changeconfig) {
		// Get context help for this page...
		$contexthelppage = "layout";
		include "help.inc.php"; 
		echo "<input type=\"hidden\" name=\"param\" value=\"layout\">
	<tr><td colspan=\"2\" class=\"formtitle\">".DEFAULTLOGOIMAGE." 
<a href=\"javascript:;\" onMouseOut=\"MM_swapImgRestore()\" onMouseOver=\"MM_swapImage('Image1','','images/contexthelpicon_over.gif',1)\"><img src=\"images/contexthelpicon.gif\" width=\"14\" height=\"15\" border=\"0\" name=\"Image1\" align=\"absmiddle\" onclick=\"return overlib('$tip1');\" onmouseout=\"return nd();\"></a></td></tr>
        <tr><td align=\"right\" class=\"formlabel\" width=\"400\">".UPLOADLOGOIMAGE.":</td><td><input type=\"file\" name=\"imgfile\" size=\"20\"></td></tr>
	<tr bgcolor=\"#D0D0D0\"><td class=\"formtitle\" colspan=\"2\">".THEMESELECTION." 
<a href=\"javascript:;\" onMouseOut=\"MM_swapImgRestore()\" onMouseOver=\"MM_swapImage('Image2','','images/contexthelpicon_over.gif',1)\"><img src=\"images/contexthelpicon.gif\" width=\"14\" height=\"15\" border=\"0\" name=\"Image2\" align=\"absmiddle\" onclick=\"return overlib('$tip2');\" onmouseout=\"return nd();\"></a></td></tr>
		<tr bgcolor=\"#D0D0D0\"><td align=\"right\" class=\"formlabel\">".THEME.":</td><td><select name=\"nashoptheme\"><option value=\"none\"";
		if ($ashoptheme == "none") echo " selected";
		echo ">".NONE."</option>";
		$findfile = opendir("$ashoppath/themes");
		while ($founddir = readdir($findfile)) {
			if (is_dir("$ashoppath/themes/$founddir") && $founddir != "." && $founddir != ".." && !strstr($founddir, "CVS") && substr($founddir, 0, 1) != "_") {
				echo "<option value=\"$founddir\"";
				$fp = fopen ("$ashoppath/themes/$founddir/theme.cfg.php","r");
				if ($fp) {
					while (!feof ($fp)) {
						$fileline = fgets($fp, 4096);
						if (strstr($fileline,"\$themename")) $themenamestring = $fileline;
					}
					fclose($fp);
					eval ($themenamestring);
				}
				if ($ashoptheme == $founddir) echo " selected";
				echo ">$themename</option>";
			}
		}
		echo "</select></td></tr>
	<tr><td class=\"formtitle\">".PAGEBODYCOLORS." 
<a href=\"javascript:;\" onMouseOut=\"MM_swapImgRestore()\" onMouseOver=\"MM_swapImage('Image3','','images/contexthelpicon_over.gif',1)\"><img src=\"images/contexthelpicon.gif\" width=\"14\" height=\"15\" border=\"0\" name=\"Image3\" align=\"absmiddle\" onclick=\"return overlib('$tip3');\" onmouseout=\"return nd();\"></a></td></tr>
<tr><td align=\"right\" class=\"formlabel\">".BACKGROUNDCOLOR.":</td><td><input type=\"text\" name=\"nbgcolor\" size=\"15\" value=\"$bgcolor\"><a href=\"javascript:colorpicker('configurationform','nbgcolor')\"><img src=\"images/colorpicker.gif\" border=\"0\" align=\"absmiddle\" width=\"20\" height=\"20\"></a><script language=\"JavaScript\">document.configurationform.nbgcolor.focus();</script></td></tr>
        <tr><td align=\"right\" class=\"formlabel\">".TEXTCOLOR.":</td><td><input type=\"text\" name=\"ntextcolor\" size=\"15\" value=\"$textcolor\"><a href=\"javascript:colorpicker('configurationform','ntextcolor')\"><img src=\"images/colorpicker.gif\" border=\"0\" align=\"absmiddle\" width=\"20\" height=\"20\"></a></td></tr>
        <tr><td align=\"right\" class=\"formlabel\">".LINKCOLOR.":</td><td><input type=\"text\" name=\"nlinkcolor\" size=\"15\" value=\"$linkcolor\"><a href=\"javascript:colorpicker('configurationform','nlinkcolor')\"><img src=\"images/colorpicker.gif\" border=\"0\" align=\"absmiddle\" width=\"20\" height=\"20\"></a></td></tr>
        <tr><td align=\"right\" class=\"formlabel\">".ALERTCOLOR.":</td><td><input type=\"text\" name=\"nalertcolor\" size=\"15\" value=\"$alertcolor\"><a href=\"javascript:colorpicker('configurationform','nalertcolor')\"><img src=\"images/colorpicker.gif\" border=\"0\" align=\"absmiddle\" width=\"20\" height=\"20\"></a></td></tr>
		<tr><td align=\"right\" class=\"formlabel\">".HEADERBACKGROUNDCOLOR.":</td><td><input type=\"text\" name=\"ncatalogheader\" size=\"15\" value=\"$catalogheader\"><a href=\"javascript:colorpicker('configurationform','ncatalogheader')\"><img src=\"images/colorpicker.gif\" border=\"0\" align=\"absmiddle\" width=\"20\" height=\"20\"></a></td></tr>
		<tr><td align=\"right\" class=\"formlabel\">".HEADERTEXTCOLOR.":</td><td><input type=\"text\" name=\"ncatalogheadertext\" size=\"15\" value=\"$catalogheadertext\"><a href=\"javascript:colorpicker('configurationform','ncatalogheadertext')\"><img src=\"images/colorpicker.gif\" border=\"0\" align=\"absmiddle\" width=\"20\" height=\"20\"></a></td></tr>
	<tr bgcolor=\"#D0D0D0\"><td width=\"44%\" class=\"formtitle\" colspan=\"2\">".FORMSCOLORS." 
<a href=\"javascript:;\" onMouseOut=\"MM_swapImgRestore()\" onMouseOver=\"MM_swapImage('Image4','','images/contexthelpicon_over.gif',1)\"><img src=\"images/contexthelpicon.gif\" width=\"14\" height=\"15\" border=\"0\" name=\"Image4\" align=\"absmiddle\" onclick=\"return overlib('$tip4');\" onmouseout=\"return nd();\"></a></td></tr>
        <tr bgcolor=\"#D0D0D0\"><td align=\"right\" class=\"formlabel\">".FORMSBACKGROUNDCOLOR.":</td><td><input type=\"text\" name=\"nformsbgcolor\" size=\"15\" value=\"$formsbgcolor\"><a href=\"javascript:colorpicker('configurationform','nformsbgcolor')\"><img src=\"images/colorpicker.gif\" border=\"0\" align=\"absmiddle\" width=\"20\" height=\"20\"></a></td></tr>
        <tr bgcolor=\"#D0D0D0\"><td align=\"right\" class=\"formlabel\">".FORMSTEXTCOLOR.":</td><td><input type=\"text\" name=\"nformstextcolor\" size=\"15\" value=\"$formstextcolor\"><a href=\"javascript:colorpicker('configurationform','nformstextcolor')\"><img src=\"images/colorpicker.gif\" border=\"0\" align=\"absmiddle\" width=\"20\" height=\"20\"></a></td></tr>
		<tr bgcolor=\"#D0D0D0\"><td align=\"right\" class=\"formlabel\">".FORMSBORDERCOLOR.":</td><td><input type=\"text\" name=\"nformsbordercolor\" size=\"15\" value=\"$formsbordercolor\"><a href=\"javascript:colorpicker('configurationform','nformsbordercolor')\"><img src=\"images/colorpicker.gif\" border=\"0\" align=\"absmiddle\" width=\"20\" height=\"20\"></a></td></tr>
	<tr><td class=\"formtitle\">".PRODUCTLAYOUT."
<a href=\"javascript:;\" onMouseOut=\"MM_swapImgRestore()\" onMouseOver=\"MM_swapImage('Image5','','images/contexthelpicon_over.gif',1)\"><img src=\"images/contexthelpicon.gif\" width=\"14\" height=\"15\" border=\"0\" name=\"Image5\" align=\"absmiddle\" onclick=\"return overlib('$tip5');\" onmouseout=\"return nd();\"></a></td></tr>
        <tr><td align=\"right\" class=\"formlabel\">".ITEMBORDERCOLOR.":</td><td><input type=\"text\" name=\"nitembordercolor\" size=\"15\" value=\"$itembordercolor\"><a href=\"javascript:colorpicker('configurationform','nitembordercolor')\"><img src=\"images/colorpicker.gif\" border=\"0\" align=\"absmiddle\" width=\"20\" height=\"20\"></a></td></tr>
        <tr><td align=\"right\" class=\"formlabel\">".ITEMBORDERWIDTH.":</td><td><input type=\"text\" name=\"nitemborderwidth\" size=\"15\" value=\"$itemborderwidth\"></td></tr>
        <tr><td align=\"right\" class=\"formlabel\">".ITEMBACKGROUNDCOLOR.":</td><td><input type=\"text\" name=\"nitembgcolor\" size=\"15\" value=\"$itembgcolor\"><a href=\"javascript:colorpicker('configurationform','nitembgcolor')\"><img src=\"images/colorpicker.gif\" border=\"0\" align=\"absmiddle\" width=\"20\" height=\"20\"></a></td></tr>
        <tr><td align=\"right\" class=\"formlabel\">".ITEMTEXTCOLOR.":</td><td><input type=\"text\" name=\"nitemtextcolor\" size=\"15\" value=\"$itemtextcolor\"><a href=\"javascript:colorpicker('configurationform','nitemtextcolor')\"><img src=\"images/colorpicker.gif\" border=\"0\" align=\"absmiddle\" width=\"20\" height=\"20\"></a></td></tr>
	<tr bgcolor=\"#D0D0D0\"><td class=\"formtitle\" colspan=\"2\">".CATEGORYCOLORS." 
<a href=\"javascript:;\" onMouseOut=\"MM_swapImgRestore()\" onMouseOver=\"MM_swapImage('Image6','','images/contexthelpicon_over.gif',1)\"><img src=\"images/contexthelpicon.gif\" width=\"14\" height=\"15\" border=\"0\" name=\"Image6\" align=\"absmiddle\" onclick=\"return overlib('$tip6');\" onmouseout=\"return nd();\"></a></td></tr>
        <tr bgcolor=\"#D0D0D0\"><td align=\"right\" class=\"formlabel\">".CATEGORYCOLOR.":</td><td><input type=\"text\" name=\"ncategorycolor\" size=\"15\" value=\"$categorycolor\"><a href=\"javascript:colorpicker('configurationform','ncategorycolor')\"><img src=\"images/colorpicker.gif\" border=\"0\" align=\"absmiddle\" width=\"20\" height=\"20\"></a></td></tr>
        <tr bgcolor=\"#D0D0D0\"><td align=\"right\" class=\"formlabel\">".CATEGORYTEXTCOLOR.":</td><td><input type=\"text\" name=\"ncategorytextcolor\" size=\"15\" value=\"$categorytextcolor\"><a href=\"javascript:colorpicker('configurationform','ncategorytextcolor')\"><img src=\"images/colorpicker.gif\" border=\"0\" align=\"absmiddle\" width=\"20\" height=\"20\"></a></td></tr>
        <tr bgcolor=\"#D0D0D0\"><td align=\"right\" class=\"formlabel\">".SELECTEDCATEGORYCOLOR.":</td><td><input type=\"text\" name=\"nselectedcategory\" size=\"15\" value=\"$selectedcategory\"><a href=\"javascript:colorpicker('configurationform','nselectedcategory')\"><img src=\"images/colorpicker.gif\" border=\"0\" align=\"absmiddle\" width=\"20\" height=\"20\"></a></td></tr>
        <tr bgcolor=\"#D0D0D0\"><td align=\"right\" class=\"formlabel\">".SELECTEDCATEGORYTEXTCOLOR.":</td><td><input type=\"text\" name=\"nselectedcategorytext\" size=\"15\" value=\"$selectedcategorytext\"><a href=\"javascript:colorpicker('configurationform','nselectedcategorytext')\"><img src=\"images/colorpicker.gif\" border=\"0\" align=\"absmiddle\" width=\"20\" height=\"20\"></a></td></tr>
	<tr><td class=\"formtitle\">".OTHERSETTINGS." 
</td></tr>
        <tr><td align=\"right\" class=\"formlabel\"><a href=\"javascript:;\" onMouseOut=\"MM_swapImgRestore()\" onMouseOver=\"MM_swapImage('Image7','','images/contexthelpicon_over.gif',1)\"><img src=\"images/contexthelpicon.gif\" width=\"14\" height=\"15\" border=\"0\" name=\"Image7\" align=\"absmiddle\" onclick=\"return overlib('$tip7');\" onmouseout=\"return nd();\"></a> ".FONT.":</td><td><input type=\"text\" name=\"nfont\" size=\"25\" value=\"$font\"><a href=\"javascript:fontselect('configurationform','nfont')\"><img src=\"images/fontselect.gif\" border=\"0\" align=\"absmiddle\" width=\"20\" height=\"20\"></a></td></tr>
		<tr><td align=\"right\" class=\"formlabel\">".SMALLTEXTSIZE.":</td><td class=\"formlabel\"><input type=\"text\" name=\"nfontsize1\" size=\"5\" value=\"$fontsize1\"> pixels</td></tr>
        <tr><td align=\"right\" class=\"formlabel\">".REGULARTEXTSIZE.":</td><td class=\"formlabel\"><input type=\"text\" name=\"nfontsize2\" size=\"5\" value=\"$fontsize2\"> pixels</td></tr>
        <tr><td align=\"right\" class=\"formlabel\">".LARGETEXTSIZE.":</td><td class=\"formlabel\"><input type=\"text\" name=\"nfontsize3\" size=\"5\" value=\"$fontsize3\"> pixels</td></tr>
        <tr><td align=\"right\" class=\"formlabel\">".REGULARTABLESIZE.":</td><td class=\"formlabel\"><input type=\"text\" name=\"ntablesize2\" size=\"5\" value=\"$tablesize2\"> pixels</td></tr>
        <tr><td align=\"right\" class=\"formlabel\">".LARGETABLESIZE.":</td><td class=\"formlabel\"><input type=\"text\" name=\"ntablesize1\" size=\"5\" value=\"$tablesize1\"> pixels</td></tr>
		<tr><td align=\"right\" class=\"formlabel\">".PAGEHEADER.":</td><td><textarea name=\"npageheader\" cols=\"30\" rows=\"5\">$pageheader</textarea></td></tr><tr><td align=\"right\" class=\"formlabel\">".PAGEFOOTER.":</td><td><textarea name=\"npagefooter\" cols=\"30\" rows=\"5\">$pagefooter</textarea></td></tr>
		<tr><td align=\"right\" class=\"formlabel\">".METAKEYWORDS.":</td><td><textarea name=\"nmetakeywords\" cols=\"30\" rows=\"5\">$ashopmetakeywords</textarea></td></tr>
		<tr><td align=\"right\" class=\"formlabel\">".METADESCRIPTION.":</td><td><textarea name=\"nmetadescription\" cols=\"30\" rows=\"5\">$ashopmetadescription</textarea></td></tr>
	    <tr bgcolor=\"#F0F0F0\"><td>&nbsp;</td><td align=\"right\"><input type=\"hidden\" name=\"cancel\" value=\"\"><input type=\"button\" value=\"".CANCEL."\" onClick=\"document.configurationform.cancel.value='true';document.configurationform.submit();\"> <input type=\"submit\" value=\"".SUBMIT."\" onClick=\"uploadmessage()\"></td></tr></table></form></table>$footer";
} else {
		@mysqli_query($db, "UPDATE user SET theme='$nashoptheme', bgcolor='$nbgcolor', textcolor='$ntextcolor', linkcolor='$nlinkcolor', formsbgcolor='$nformsbgcolor', formstextcolor='$nformstextcolor', itembordercolor='$nitembordercolor', itembgcolor='$nitembgcolor', itemtextcolor='$nitemtextcolor', categorycolor='$ncategorycolor', categorytextcolor='$ncategorytextcolor', selectedcategory='$nselectedcategory', selectedcategorytext='$nselectedcategorytext', font='$nfont', pageheader='$npageheader', pagefooter='$npagefooter', metakeywords='$nmetakeywords', metadescription='$nmetadescription', alertcolor='$nalertcolor', catalogheader='$ncatalogheader', catalogheadertext='$ncatalogheadertext', formsbordercolor='$nformsbordercolor', itemborderwidth='$nitemborderwidth',	fontsize1='$nfontsize1', fontsize2='$nfontsize2', fontsize3='$nfontsize3', tablesize1='$ntablesize1', tablesize2='$ntablesize2' WHERE userid='$userid'");
	@mysqli_close($db);
	header("Location: index.php");
}

if (!$changeconfig) {

} else {

}
?>