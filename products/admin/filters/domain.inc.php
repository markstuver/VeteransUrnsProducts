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

// Remove unwanted text...
$filter_attributevalue = strtolower($filter_attributevalue);
$filter_attributevalue = str_replace("www.", "", $filter_attributevalue);
$filter_attributevalue = str_replace("http://", "", $filter_attributevalue);

// Check for non alpha-numeric characters...
if (!preg_match("/^[\.\-0-9a-zA-Z]*$/", $filter_attributevalue)) {
	header('Content-type: text/plain');
	echo "0|The domain you entered contains characters that can not be used in a domain name.";
	exit;
}
?>