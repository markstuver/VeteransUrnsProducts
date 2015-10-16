<?php header('Content-type: text/css'); ?>

@charset "utf-8";
@import "../css/jquery-ui.min.css";
@import "../css/theme/jquery-ui.theme.min.css";
/* CSS Document */
<?php
if (empty($databaseserver) || empty($databaseuser)) include "../admin/config.inc.php";

// Apply selected theme...
if (!empty($ashoptheme) && $ashoptheme != "none" && file_exists("$ashoppath/themes/$ashoptheme/theme.cfg.php")) include "../themes/$ashoptheme/theme.cfg.php";

$salefontsize = $fontsize2+2;

echo "
#productdetails { background: $itembgcolor; }

.ui-widget { font-family: $font; font-size: {$fontsize2}px; }

.ui-widget-header { border-top: none; border-left: none; border-right: none; border-bottom: 1px solid $itembordercolor; background: $itembgcolor; border-radius: 0px; }

.ui-widget-content a { color: $textcolor; }

.ui-widget-content .ui-state-default { color: $categorytextcolor; border: 1px solid $itembordercolor; border-top-left-radius: 4px; border-top-right-radius: 4px; background: $categorycolor; background-image: none; }

.ui-widget-content .ui-state-hover { color: $selectedcategorytext; border: 1px solid $itembordercolor; border-top-left-radius: 4px; border-top-right-radius: 4px; background-color: $selectedcategory; background-image: none; }

.ui-widget-content .ui-state-active { border: 1px solid $itembordercolor; background: $itembgcolor; border-top-left-radius: 4px; border-top-right-radius: 4px; background-image: none; }

.ui-widget-content { border: none; }

.ui-effects-transfer { border: 2px solid black; }

.ashopcategoriesbox { width: 180px; vertical-align: top; padding: 3px; }

.ashopcategoriestable { width: 100%; text-align: left; }

.ashopcategoriesheader { background-color: $catalogheader; padding: 5px; font-family: $font; font-size: {$fontsize2}px; color: $catalogheadertext; font-weight: bold; }

.ashopcategoriescontent { background-color: $categorycolor; border: 1px solid $catalogheader; font-family: $font; font-size: {$fontsize2}px; color: $categorytextcolor; padding: 0px; }

.ashopselectedcategory { background-color: $selectedcategory; width: 100%; padding: 2px; border: none; }

.ashopselectedcategorytext { font-family: $font; font-size: {$fontsize2}px; color: $selectedcategorytext; font-weight: bold; }

.ashopcategory { background-color: $categorycolor; width: 100%; padding: 2px; border: none; }

.ashopcategorytext { font-family: $font; font-size: {$fontsize2}px; color: $categorytextcolor; }

.ashopselectedsubsubcategory { background-color: $selectedcategory; width: 100%; padding: 2px; border: none; }

.ashopselectedsubsubcategorytext { font-family: $font; font-size: {$fontsize2}px; color: $selectedcategorytext; font-weight: bold; font-style: italic; }

.ashopsubsubcategory { background-color: $categorycolor; width: 100%; padding: 2px; border: none; }

.ashopsubsubcategorytext { font-family: $font; font-size: {$fontsize2}px; color: $categorytextcolor; font-style: italic; }

.ashopdirectorytable { width: 100%; border: 1px solid #000; text-align: left; margin-top: 10px; }

.ashopdirectoryitem { background-color: $bgcolor; font-family: $font; font-size: {$fontsize2}px; color: $textcolor; }

.ashopdirectoryitem a:hover { color: #888; }

.ashopdirectorysubitem { font-family: $font; font-size: {$fontsize1}px; color: $textcolor; }

.ashopdirectorysubitem:hover { color: #888; }

.ashopdirectorydescription { margin-top: 4px; font-family: $font; font-size: {$fontsize2}px; color: $textcolor; }

.ashopboxtable { width: 100%; text-align: left; }

.ashopboxheader { background-color: $catalogheader; padding: 5px; font-family: $font; font-size: {$fontsize2}px; color: $catalogheadertext; font-weight: bold; }

.ashopboxcontent { background-color: $categorycolor; border: 1px solid $catalogheader; font-family: $font; font-size: {$fontsize2}px; color: $categorytextcolor; padding: 4px; }

.ashopboxcontent a { color: $categorytextcolor; text-decoration: none; }

.ashopboxcontent a:hover { text-decoration: underline; }

.ashoptoplisttable { width: 100%; text-align: left; }

.ashoptoplistheader { background-color: $catalogheader; padding: 5px; font-family: $font; font-size: {$fontsize2}px; color: $catalogheadertext; font-weight: bold; }

.ashoplatesttable { width: 100%; text-align: left; }

.ashoplatestheader { background-color: $catalogheader; padding: 5px; font-family: $font; font-size: {$fontsize2}px; color: $catalogheadertext; font-weight: bold; }

.ashopsearchfield { border: 2px solid $itembordercolor; background: $itembgcolor; width: 125px; height: 16px; font: 11px $font; color: $itemtextcolor; text-align: left; }

.ashopnewsletterfield { border: 2px solid $itembordercolor; background: $itembgcolor; width: 200px; height: 16px; font: 11px $font; color: $itemtextcolor; text-align: left; }

.ashopcodefield { border: 2px solid $itembordercolor; background: $itembgcolor; width: 120px; height: 16px; font: 11px $font; color: $itemtextcolor; text-align: left; }

.ashopsubtotalfield { border: 2px solid $itembordercolor; background: $itembgcolor; width: 70px; height: 16px; font: 11px $font; color: $itemtextcolor; text-align: center; }

.ashoptopform { background-color: $catalogheader; padding: 2px; border-style:none; border-collapse: collapse; width: 100%; }

.ashoptopformtext { font-family: $font; font-size: {$fontsize2}px; color: $catalogheadertext; font-weight: bold; }

.ashopsubtotaltext { font-family: $font; font-size: {$fontsize2}px; color: $catalogheadertext; font-weight: bold; }

.ashopsubtotaltext2 { font-family: $font; font-size: {$fontsize2}px; color: $categorytextcolor; font-weight: bold; }

.ashopconfirmmessage { margin-top: 3px; margin-bottom: 3px; background-color: $categorycolor; font-family: $font; font-size: {$fontsize1}px; color: #006600; }

.ashoppageheader { background-color: $selectedcategory; padding: 5px; width: 100%; border-style: none; }

.ashoppageheadertext1 { margin: 0; display: inline; font-family: $font; font-size: {$fontsize3}px; color: $selectedcategorytext; font-weight: bold; }

.ashoppageheadertext2 { font-family: $font; font-size: {$fontsize2}px; color: $selectedcategorytext; }

.ashopsortorderselector { font-family: $font; font-size: {$fontsize2}px; }

.ashopitemsframe { width: 100%; padding: 0px; vertical-align: top; text-align: left; }

.ashopitembox { background-color: $itembgcolor; padding: 0px; border: none; border-bottom: {$itemborderwidth}px solid $itembordercolor; vertical-align: top; }

.ashopitemdetailsbox { background-color: $itembgcolor; width: 100%;  border: none; border-top: 1px solid $itembordercolor; vertical-align: top; }

.ashopitembackground { }

.ashopitemboxcondensed { background-color: $itembgcolor; width: 100%; padding: 0px; border-style: none; border-collapse: collapse; }

.ashoppictureselector { background-color: $itembgcolor; width: 100%; border: 1px solid $itembordercolor; margin-top: 15px; }

.ashopproductsmalltext { font-family: $font; font-size: {$fontsize1}px; color: $itemtextcolor; }

.ashopproductname { font-family: $font; font-size: {$fontsize3}px; color: $itemtextcolor; font-weight: bold; }

.ashopproductinfo { font-family: $font; font-size: {$fontsize2}px; color: $alertcolor; }

.ashopproductwishlist { font-family: $font; font-size: {$fontsize2}px; color: $itemtextcolor; line-height: 20px; }

.ashopproducttext { font-family: $font; font-size: {$fontsize2}px; color: $itemtextcolor; }

.ashopproductprice { font-family: $font; font-size: 18px; color: $itemtextcolor; }

.ashopproductoutofstock { font-family: $font; font-size: {$fontsize2}px; color: $alertcolor; font-weight: bold; }

.ashopproductlowstock { font-family: $font; font-size: {$fontsize2}px; color: #BB5500; }

.ashopproductinstock { font-family: $font; font-size: {$fontsize2}px; color: #00AA00; }

.ashopproductsale { font-family: $font; font-size: {$salefontsize}px; color: $alertcolor; font-weight: bold; }

.ashopproductlabel { float: left; min-width: 54px; font-family: $font; font-size: {$fontsize2}px; color: $itemtextcolor; font-weight: bold; }

.ashopproductbid { font-family: $font; font-size: 20px; color: $itemtextcolor; font-weight: bold; }

.ashopbidbutton { margin-top: 0px; }

.ashopproductagreementheader { font-family: $font; font-size: {$fontsize3}px; color: $textcolor; }

.ashopproductagreement { font-family: $font; font-size: {$fontsize2}px; color: $textcolor; }

.ashopproducttabheader { width: 100%; border-bottom: 1px solid $itembordercolor; font-weight: bold; }

.ashopproducttabreview { width: 100%; border-bottom: 1px solid $itembordercolor; margin-top: 5px; }

.ashopdiscountfield { border: 1px solid $itembordercolor; background: $bgcolor; width: 77px; height: 14px; font: 10px verdana, arial, helvetica; color: $textcolor; text-align: center; vertical-align: text-bottom; }

.ashopdiscountemailfield { border: 1px solid $itembordercolor; background: $bgcolor; width: 110px; height: 18px; font: 9px verdana, arial, helvetica; color: $textcolor; text-align: center; vertical-align: text-bottom; }

.ashopquantityfield { border: 1px solid $itembordercolor; padding: 3px 0px 0px 0px; background: $bgcolor; width: 32px; height: 16px; font: 11px verdana, arial, helvetica; color: $textcolor; text-align: center; vertical-align: text-bottom; }

.ashopquantityselect { border: 1px solid $itembordercolor; padding: 0; background: $bgcolor; width: 40px; font: 11px verdana, arial, helvetica; color: $textcolor; text-align: center; vertical-align: text-bottom; }

.ashoptellafriendfield { border: 1px solid $itembordercolor; padding: 3px 0px 0px 0px; background: $bgcolor; height: 16px; font: 11px verdana, arial, helvetica; color: $textcolor; text-align: left; vertical-align: text-bottom; }

.ashoprsssubscribe { font-family: $font; font-size: 9px; color: $textcolor; text-decoration: none; vertical-align: bottom }

.ashoppagestable { background-color: $categorycolor; padding: 5px; width: 100%; border-style: none; }

.ashoppageslist, .ashoppageslist a { font-family: $font; font-size: {$fontsize2}px; color: $categorytextcolor; font-weight: bold; text-decoration: none; }

.ashopalert { font-family: $font; font-size: {$fontsize2}px; color: $alertcolor; font-weight: bold; }

.ashopmessagetable { padding: 0px; width: 75%; border-style: none; }

.ashopmessageheader { font-family: $font; font-size: {$fontsize3}px; color: $textcolor; font-weight: bold; }

.ashopmessage { font-family: $font; font-size: {$fontsize3}px; color: $textcolor; }

.ashopcartframe1 { padding: 5px; width: {$tablesize1}px; border-style: none; }

.ashopcarttext { font-family: $font; font-size: {$fontsize2}px; color: $textcolor; font-weight: bold; }

.ashopcartframe2 { background-color: $formsbgcolor; padding: 5px; width: 100%; border-style: none; }

.ashopcartframe3 { padding: 15px; width: {$tablesize1}px; border-style: none; }

.ashoptableheader { background-image: url(../images/tabletopbg.png); background-repeat: repeat-x; }

.ashopcarttable { padding: 5px; width: 100%; border-style: solid; border-width: 1px; border-collapse: collapse; border-color: $formsbordercolor; }

.ashopcarttable td { border: none; border-bottom: 1px solid #EEE; }

.ashopcartlabel { font-family: $font; font-size: {$fontsize2}px; color: $formstextcolor; font-weight: bold; }

.ashopcartcontents { font-family: $font; font-size: {$fontsize2}px; color: $formstextcolor; }

.ashopcartcontentsmall { font-family: $font; font-size: {$fontsize1}px; color: $formstextcolor; }

.ashopcarttotals { font-family: $font; font-size: {$fontsize2}px; color: $categorytextcolor; }

.ashopcheckoutframe { padding: 5px; width: {$tablesize1}px; border-style: none; }

.ashopcheckouttext1 { font-family: $font; font-size: {$fontsize2}px; color: $textcolor; font-weight: bold; }

.ashopcheckouttext2 { font-family: $font; font-size: {$fontsize2}px; color: $textcolor; }

.ashopcheckouttable { background-color: $formsbgcolor; padding: 0; width: {$tablesize2}px; border-style: solid; border-width: 1px; border-color: $formsbordercolor; }

.ashopdiscounttable { background-color: $formsbgcolor; padding: 5px; width: {$tablesize2}px; border-style: solid; border-width: 1px; border-color: $formsbordercolor; }

.ashopdiscounttext { font-family: $font; font-size: {$fontsize2}px; color: $formstextcolor; }

.ashopvirtualcashtable { background-color: $formsbgcolor; padding: 5px; width: {$tablesize2}px; border-style: solid; border-width: 1px; border-color: $formsbordercolor; }

.ashopvirtualcashtext { font-family: $font; font-size: {$fontsize2}px; color: $formstextcolor; }

.ashoppartiestable { background-color: $formsbgcolor; padding: 5px; width: {$tablesize2}px; border-style: solid; border-width: 1px; border-color: $formsbordercolor; margin-bottom: 10px; }

.ashoppartiestext { font-family: $font; font-size: {$fontsize2}px; color: $formstextcolor; }

.ashoppartiestext2 { font-family: $font; font-size: {$fontsize3}px; color: $formstextcolor; }

.ashopcheckoutcontents { font-family: $font; font-size: {$fontsize2}px; color: $formstextcolor; }

.ashopcheckoutagreement { font-family: $font; font-size: {$fontsize2}px; color: $textcolor; }

.ashopcheckoutagreementtable { padding: 10px; width: 100%; border-style: none; }

.ashoporderformframe { padding: 2px; width: {$tablesize2}px; border-style: none; }

.ashoporderformbox { background-color: $formsbgcolor; padding: 2px; width: {$tablesize2}px; border-style: solid; border-width: 1px; border-color: $formsbordercolor; }

.ashoporderformheader { font-family: $font; font-size: {$fontsize3}px; color: $textcolor; font-weight: bold; }

.ashoporderformtext1 { font-family: $font; font-size: {$fontsize2}px; color: $textcolor; font-weight: bold; }

.ashoporderformtext2 { font-family: $font; font-size: {$fontsize2}px; color: $textcolor; }

.ashoporderformlabel { font-family: $font; font-size: {$fontsize2}px; color: $formstextcolor; }

.ashoporderformfield { text-align: left; }

.ashoporderformnotice { font-family: $font; font-size: {$fontsize1}px; color: $formstextcolor; }

.ashoplanguageselectionbox { background-color: $formsbgcolor; padding: 2px; width: 300px; border-style: solid; border-width: 1px; border-color: $formsbordercolor; }

.ashoplanguageselection { font-family: $font; font-size: {$fontsize3}px; color: $formstextcolor; }

.ashopdeliveryheader { font-family: $font; font-size: {$fontsize3}px; color: $textcolor; font-weight: bold; }

.ashopdeliverytext1 { font-family: $font; font-size: {$fontsize2}px; color: $textcolor; font-weight: bold; }

.ashopdeliverytext2 { font-family: $font; font-size: {$fontsize2}px; color: $textcolor; }

.ashopdeliverycontactframe { padding: 2px; width: 60%; border-style: none; }

.ashopdownloadframe { padding: 2px; width: {$tablesize1}px; border-style: none; }

.ashopshippingerror { font-family: $font; font-size: {$fontsize2}px; color: $alertcolor; font-weight: bold; }

.ashopshippingframe { padding: 15px; width: {$tablesize2}px; border-style: none; }

.ashopshippingbox { background-color: $formsbgcolor; width: {$tablesize2}px; border-style: solid; border-width: 1px; border-color: $formsbordercolor; }

.ashopshippingtext1 { font-family: $font; font-size: {$fontsize2}px; color: $textcolor; }

.ashopshippingtext2 { font-family: $font; font-size: {$fontsize2}px; color: $formstextcolor; }

.ashopshippingnotice { font-family: $font; font-size: {$fontsize1}px; color: $formstextcolor; }

.ashopthankyouframe { padding: 2px; width: {$tablesize1}px; border-style: none; }

.ashopthankyouheader { font-family: $font; font-size: {$fontsize3}px; color: $textcolor; font-weight: bold; }

.ashopthankyoutext1 { font-family: $font; font-size: {$fontsize2}px; color: $textcolor; font-weight: bold; }

.ashopthankyoutext2 { font-family: $font; font-size: {$fontsize2}px; color: $textcolor; }

.ashopcustomerloginframe { padding: 2px; width: 400px; border-style: none; }

.ashopcustomersignupframe { padding: 2px; width: {$tablesize2}px; border-style: none; }

.ashopcustomersignupbox { background-color: $formsbgcolor; padding: 2px; width: {$tablesize2}px; border-style: solid; border-width: 1px; border-color: $formsbordercolor; }

.ashopcustomercodebox { background-color: #D0D0D0; padding: 5px; width: 530px; border-style: none; }

.ashopcustomerheader { font-family: $font; font-size: {$fontsize3}px; color: $textcolor; font-weight: bold; }

.ashopcustomertext1 { font-family: $font; font-size: {$fontsize2}px; color: $textcolor; font-weight: bold; }

.ashopcustomertext2 { font-family: $font; font-size: {$fontsize2}px; color: $textcolor; }

.ashopcustomertext3 { font-family: $font; font-size: {$fontsize2}px; color: $formstextcolor; }

.ashopcustomertext4 { font-family: $font; font-size: {$fontsize1}px; color: $textcolor; }

.ashopcustomertext4 a { text-decoration: underline; color: $textcolor; }

.ashopcustomertext4 a:hover { text-decoration: none; }

.ashopcustomertext5 { font-family: $font; font-size: {$fontsize2}px; color: $catalogheadertext; text-decoration: none; }

.ashopcustomertext5 a { text-decoration: underline; color: $catalogheadertext; }

.ashopcustomertext5 a:hover { text-decoration: none; }

.ashopcustomertext6 { font-family: $font; font-size: {$fontsize2}px; color: $formstextcolor; }

.ashopcustomertext7 { font-family: $font; font-size: {$fontsize2}px; color: $formstextcolor; }

.ashopcustomertext8 { font-family: $font; font-size: {$fontsize1}px; color: $textcolor; }

.ashopcustomeralert { font-family: $font; font-size: {$fontsize2}px; color: $alertcolor; font-weight: bold; }

.ashopcustomeralert2 { font-family: $font; font-size: {$fontsize2}px; color: #009900; font-weight: bold; }

.ashopcustomerfield { text-align: left; }

.ashopcustomernotice { font-family: $font; font-size: {$fontsize1}px; color: $formstextcolor; }

.ashopcustomerhistoryframe { padding: 2px; width: {$tablesize1}px; border-style: none; }

.ashopcustomerhistoryheader { font-family: $font; font-size: {$fontsize3}px; color: $textcolor; font-weight: bold; }

.ashopcustomerhistorybox { padding: 5px; width: 100%; border-style: solid; border-width: 1px; border-collapse: collapse; border-color: $formsbordercolor; }

.ashopcustomerhistorybox td { border: none; border-bottom: 1px solid #EEE; padding: 5px; }

.ashopcustomerhistoryrow { background-image: url(../images/tabletopbg.png); background-repeat: repeat-x; }

.ashopcustomerhistorytext1 { font-family: $font; font-size: {$fontsize2}px; color: $textcolor; font-weight: bold; }

.ashopcustomerhistorytext2 { font-family: $font; font-size: {$fontsize2}px; color: $alertcolor; }

.ashopaffiliatebutton { width: 100px; text-decoration: none; }

.ashopaffiliatebuttonlarge { width: 150px; text-decoration: none; }

.ashopaffiliatebuttonsmall { width: 82px; text-decoration: none; }

.ashopaffiliateloginframe { padding: 2px; width: 430px; border: none; }

.ashopaffiliatesignupframe { padding: 2px; width: {$tablesize2}px; border-style: none; }

.ashopaffiliatesignupbox { background-color: $formsbgcolor; padding: 2px; width: {$tablesize2}px; border-style: solid; border-width: 1px; border-color: $formsbordercolor; }

.ashopaffiliatecodeframe { padding: 2px; width: 730px; border-style: none; }

.ashopaffiliatecategoriesbox { width: 200px; border-style: none; vertical-align: top; text-align: left; }

.ashopaffiliatecategoriestable { width: 100%; border: 1px solid $catalogheader; }

.ashopaffiliatecategoriesheader { background-color: $catalogheader; padding: 5px; font-family: $font; font-size: {$fontsize2}px; color: $catalogheadertext; font-weight: bold; }

.ashopaffiliateselectedcategory { background-color: $selectedcategory; font-family: $font; font-size: {$fontsize2}px; color: $selectedcategorytext; font-weight: bold; }

.ashopaffiliatecategory { background-color: $categorycolor; font-family: $font; font-size: {$fontsize2}px; color: $categorytextcolor; }

.ashopaffiliatecodebox { background-color: #D0D0D0; padding: 5px; width: 530px; border-style: none; }

.ashopaffiliateheader { font-family: $font; font-size: {$fontsize3}px; color: $textcolor; font-weight: bold; }

.ashopaffiliatetext1 { font-family: $font; font-size: {$fontsize2}px; color: $textcolor; font-weight: bold; }

.ashopaffiliatetext2 { font-family: $font; font-size: {$fontsize2}px; color: $textcolor; }

.ashopaffiliatetext3 { font-family: $font; font-size: {$fontsize2}px; color: $formstextcolor; }

.ashopaffiliatefield { text-align: left; }

.ashopaffiliatenotice { font-family: $font; font-size: {$fontsize1}px; color: $formstextcolor; }

.ashopaffiliatemessagesbox { background-color: #D0D0D0; padding: 2px; width: 600px; border-style: solid; border-width: 1px; border-color: $formsbordercolor; }

.ashopaffiliatemessagesrow { background-color: #808080; }

.ashopaffiliatemessagestext1 { font-family: $font; font-size: {$fontsize2}px; color: $catalogheadertext; font-weight: bold; }

.ashopaffiliatemessagestext2 { font-family: $font; font-size: {$fontsize2}px; color: $formstextcolor; }

.ashopaffiliatemessagestext3 { font-family: $font; font-size: {$fontsize2}px; color: $formstextcolor; font-weight: bold; }

.ashopaffiliatemessagebox { background-color: $bgcolor; padding: 2px; width: 500px; border-style: solid; border-width: 1px; border-color: $formsbordercolor; }

.ashopaffiliatemessagetext1 { font-family: $font; font-size: {$fontsize2}px; color: $catalogheadertext; font-weight: bold; }

.ashopaffiliatemessagetext2 { font-family: $font; font-size: {$fontsize2}px; color: $alertcolor; }

.ashopaffiliatehistorybox { background-color: #D0D0D0; padding: 2px; width: 450px; border-style: solid; border-width: 1px; border-color: $formsbordercolor; }

.ashopaffiliatehistoryrow { background-color: #808080; }

.ashopaffiliatehistorytext1 { font-family: $font; font-size: {$fontsize2}px; color: $catalogheadertext; font-weight: bold; }

.ashopaffiliatehistorytext2 { font-family: $font; font-size: {$fontsize2}px; color: $alertcolor; }

.ashopaffiliatepartiesbox { background-color: #D0D0D0; padding: 2px; width: 650px; border-style: solid; border-width: 1px; border-color: $formsbordercolor; }

.ashopaffiliatepartiesrow { background-color: #808080; }

.ashopaffiliatepartiestext1 { font-family: $font; font-size: {$fontsize2}px; color: $catalogheadertext; font-weight: bold; }

.ashopaffiliatepartiestext2 { font-family: $font; font-size: {$fontsize2}px; color: #666; }

.ashopaffiliateleadsbox { background-color: #D0D0D0; padding: 2px; width: 600px; border-style: solid; border-width: 1px; border-color: $formsbordercolor; }

.ashopaffiliateleadsrow { background-color: #808080; }

.ashopaffiliateleadstext1 { font-family: $font; font-size: {$fontsize2}px; color: $catalogheadertext; font-weight: bold; }

.ashopmallcategories { border: 2px solid #bbbbbb; background: #ffffff; width: 98%; font: 11px verdana, arial, helvetica; color: #000000; text-align: left; }

.ashopmallsearch { border: 2px solid #bbbbbb; background: #ffffff; width: 95%; height: 20px; font: 11px verdana, arial, helvetica; color: #000000; text-align: left; }

.ashopmallbox { width: 100%; background-color: $itembgcolor; padding: 0px; border-style: none; vertical-align: top; text-align: left; }

.ashopmallname, .ashopmallname a { font-family: $font; font-size: {$fontsize3}px; color: $itemtextcolor; font-weight: bold; text-decoration: none; }

.ashopmalltext, .ashopmalltext a { font-family: $font; font-size: {$fontsize2}px; color: $itemtextcolor; text-decoration: none; }

.ashopmallsignupframe { padding: 2px; width: {$tablesize2}px; border-style: none; }

.ashopmallsignupbox { background-color: $formsbgcolor; padding: 2px; width: {$tablesize2}px; border-style: solid; border-width: 1px; border-color: $formsbordercolor; }

.ashopmallsignupheader { font-family: $font; font-size: {$fontsize3}px; color: $textcolor; font-weight: bold; }

.ashopmallsignuptext1 { font-family: $font; font-size: {$fontsize2}px; color: $textcolor; font-weight: bold; }

.ashopmallsignuptext2 { font-family: $font; font-size: {$fontsize2}px; color: $textcolor; }

.ashopmallsignuptext3 { font-family: $font; font-size: {$fontsize2}px; color: $formstextcolor; }

.ashopmallsignupfield { text-align: left; }

.ashopmallsignupnotice { font-family: $font; font-size: {$fontsize1}px; color: $formstextcolor; }

.ashopsignupframe { padding: 2px; width: {$tablesize2}px; border-style: none; }

.ashopsignupbox { background-color: $formsbgcolor; padding: 2px; width: {$tablesize2}px; border-style: solid; border-width: 1px; border-color: $formsbordercolor; }

.ashopsignupcodebox { background-color: #D0D0D0; padding: 5px; width: 530px; border-style: none; }

.ashopsignupheader { font-family: $font; font-size: {$fontsize3}px; color: $textcolor; font-weight: bold; }

.ashopsignupheader2 { font-family: $font; font-size: {$fontsize3}px; color: $textcolor; font-weight: bold; }

.ashopwholesalesignupframe { padding: 2px; width: {$tablesize2}px; border-style: none; }

.ashopwholesalesignupbox { background-color: $formsbgcolor; padding: 2px; width: {$tablesize2}px; border-style: solid; border-width: 1px; border-color: $formsbordercolor; }

.ashopwholesalesignupheader { font-family: $font; font-size: {$fontsize3}px; color: $textcolor; font-weight: bold; }

.ashopwholesalesignuptext1 { font-family: $font; font-size: {$fontsize2}px; color: $textcolor; font-weight: bold; }

.ashopwholesalesignuptext2 { font-family: $font; font-size: {$fontsize2}px; color: $textcolor; }

.ashopwholesalesignuptext3 { font-family: $font; font-size: {$fontsize2}px; color: $formstextcolor; }

.ashopwholesalesignupfield { text-align: left; }

.ashopwholesalesignupnotice { font-family: $font; font-size: {$fontsize1}px; color: $formstextcolor; }

.ashopwholesaleloginframe { padding: 2px; width: 400px; border-style: none; }

.ashopwholesaleheader { font-family: $font; font-size: {$fontsize3}px; color: $textcolor; font-weight: bold; }

.ashopwholesaletext1 { font-family: $font; font-size: {$fontsize2}px; color: $textcolor; font-weight: bold; }

.ashopwholesaletext2 { font-family: $font; font-size: {$fontsize2}px; color: $textcolor; }

.ashopwholesaletext3 { font-family: $font; font-size: {$fontsize2}px; color: $formstextcolor; }

.ashopbutton { background-color: #a3a3a3; }

.ashopbutton:hover { background-color: #ddd; }

.ashopvideostreamplaylist { float: right; }

.ashopvideostreamplaylist ul li { font-size: {$fontsize3}px; color: $textcolor; list-style-type: none; text-align: left; padding: 0; width: 500px; border: 1px solid $itembordercolor; }

.ashopvideostreamplaylist ul li a { display: block; width: 488px; padding-top: 6px; padding-bottom: 6px; padding-left: 6px; padding-right: 6px; text-decoration: none; }

.ashopvideostreamplaylist ul li a:hover { background-color: #B0B0B0; display: block; }
";
?>