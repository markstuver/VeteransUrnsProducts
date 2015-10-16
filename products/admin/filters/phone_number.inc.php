<?php
####################################################################################
##                                                                                ##
##                    Sample Attribute Filter for Phone Numbers                   ##
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

// Check for non alpha-numeric characters...
preg_match_all('/(?:([-()0-9]+)|.)/i', $filter_attributevalue, $matches);
if (!$filter_attributevalue || $filter_attributevalue != strtolower(implode('', $matches[1]))) {
	header('Content-type: text/plain');
	echo "0|You must enter a valid phone number.";
	exit;
}
?>