<?php
####################################################################################
##                                                                                ##
##                      Sample Attribute Filter for Domains                       ##
##                 Filename = [attribute name (lower case)].inc.php			      ##
##                                                                                ##
##                               Available variables:                             ##
##                                                                                ##
##                    $filter_attributeid, $filter_attributename,                 ##
##                    $filter_attributevalue, $filter_productid,				  ##
##                                                                                ##
##                               Available functions:                             ##
##                                                                                ##
##             ashop_duplicatecheck($filter_attributeid, $filter_attributevalue)  ##
##                                                                                ##
##                    Checks for duplicate attribute values.					  ##
##                    Returns the number of duplicates.							  ##
##                                                                                ##
##                                                                                ##
##             ashop_cleanattributes($filter_attributeid, $filter_productid)	  ##
##                                                                                ##
##                    Removes unused attribute values.							  ##
##                                                                                ##
####################################################################################

$serverdefs= array(
	"com" => array("whois.crsnic.net","No match for"),
	"net" => array("whois.crsnic.net","No match for"),				
	"org" => array("whois.pir.org","NOT FOUND"),					
	"biz" => array("whois.biz","Not found"),					
	"info" => array("whois.afilias.net","NOT FOUND"),					
	"co.uk" => array("whois.nic.uk","No match"),					
	"co.ug" => array("wawa.eahd.or.ug","No entries found"),	
	"or.ug" => array("wawa.eahd.or.ug","No entries found"),
	"ac.ug" => array("wawa.eahd.or.ug","No entries found"),
	"ne.ug" => array("wawa.eahd.or.ug","No entries found"),
	"sc.ug" => array("wawa.eahd.or.ug","No entries found"),
	"nl" 	=> array("whois.domain-registry.nl","not a registered domain"),
	"ro" => array("whois.rotld.ro","No entries found for the selected"),
	"com.au" => array("whois.ausregistry.net.au","No data Found"),
	"ca" => array("whois.cira.ca", "AVAIL"),
	"org.uk" => array("whois.nic.uk","No match"),
	"name" => array("whois.nic.name","No match"),
	"us" => array("whois.nic.us","Not Found"),
	"ws" => array("whois.website.ws","No Match"),
	"be" => array("whois.ripe.net","No entries"),
	"com.cn" => array("whois.cnnic.cn","no matching record"),
	"net.cn" => array("whois.cnnic.cn","no matching record"),
	"org.cn" => array("whois.cnnic.cn","no matching record"),
	"no" => array("whois.norid.no","no matches"),
	"se" => array("whois.nic-se.se","No data found"),
	"nu" => array("whois.nic.nu","NO MATCH for"),
	"com.tw" => array("whois.twnic.net","No such Domain Name"),
	"net.tw" => array("whois.twnic.net","No such Domain Name"),
	"org.tw" => array("whois.twnic.net","No such Domain Name"),
	"cc" => array("whois.nic.cc","No match"),
	"nl" => array("whois.domain-registry.nl","is free"),
	"pl" => array("whois.dns.pl","No information about"),
	"pt" => array("whois.ripe.net","No entries found"),
	"de" => array("whois.denic.de","not found in database"),
	"in" => array("whois.inregistry.net","NOT FOUND"),
	"eu" => array("whois.eu","FREE"),
	"za.org" => array("whois.za.net","No such domain"),
	"za.net" => array("whois.za.net","No such domain"),
	"tv" => array("tvwhois.verisign-grs.com","No match for")
);


function domainavailable($domain,$ext)
{
    global $nomatch,$server;
    $output="";
    if(($sc = fsockopen($server,43))==false){return 2;}
    if($ext == "co.uk")
           fputs($sc,"$domain.$ext\r\n");
    elseif($ext == "nl") 
	   fputs($sc, "is $domain.$ext\r\n");
    else
           fputs($sc,"$domain.$ext\n");

    while(!feof($sc)){$output.=fgets($sc,128);}
    fclose($sc);
    //compare what has been returned by the server
    if (stristr($output,$nomatch)){
		return TRUE;
    }else{
        return FALSE;
    }
}


// Remove unwanted text...
$filter_attributevalue = strtolower($filter_attributevalue);
$filter_attributevalue = str_replace("www.", "", $filter_attributevalue);
$filter_attributevalue = str_replace("http://", "", $filter_attributevalue);
$filter_domain = substr($filter_attributevalue,0,strpos($filter_attributevalue,"."));
$filter_ext = substr($filter_attributevalue,strpos($filter_attributevalue,".")+1);

// Check for non alpha-numeric characters...
if (!preg_match("/^[\.\-0-9a-zA-Z]*$/", $filter_attributevalue)) {
	header('Content-type: text/plain');
	echo "0|The domain you entered contains characters that can not be used in a domain name.";
	exit;
}

// Check if the domain is available...
$available = FALSE;
if ($serverdefs[$filter_ext]){
	$server = $serverdefs[$filter_ext][0];
	$nomatch = $serverdefs[$filter_ext][1];
	$available = domainavailable($filter_domain,$filter_ext);
}

// Report if the domain is not available...
if (!$available) {
	header('Content-type: text/plain');
	echo "0|The domain you entered is already taken.";
	exit;
}
?>