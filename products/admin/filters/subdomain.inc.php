<?php
####################################################################################
##                                                                                ##
##                      Sample Attribute Filter for Subdomains                    ##
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

// Remove unwanted text...
$filter_attributevalue = strtolower($filter_attributevalue);
$filter_attributevalue = str_replace("www.", "", $filter_attributevalue);
$filter_attributevalue = str_replace("http://", "", $filter_attributevalue);
if (strpos($filter_attributevalue, ".")) $filter_attributevalue = substr($filter_attributevalue, 0, strpos($filter_attributevalue, "."));
if (strpos($filter_attributevalue, "/")) $filter_attributevalue = substr($filter_attributevalue, 0, strpos($filter_attributevalue, "/"));

// Clean out old unused attribute values...
ashop_cleanattributes($filter_attributeid, $filter_productid);

// Check for duplicates...
if (ashop_duplicatecheck($filter_attributeid, $filter_attributevalue)) {
	header('Content-type: text/plain');
	echo "0|The subdomain $filter_attributevalue is already taken.";
	exit;
}

// Check for non alpha-numeric characters...
preg_match_all('/(?:([a-z0-9]+)|.)/i', $filter_attributevalue, $matches);
if ($filter_attributevalue != strtolower(implode('', $matches[1]))) {
	header('Content-type: text/plain');
	echo "0|The subdomain you entered contains characters that can not be used in a subdomain.";
	exit;
}
?>