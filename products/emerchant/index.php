<?php
####################################################################################
##                                                                                ##
##				Change the redirect URL below to modify the			              ##
##              start page of your eMerchant...									  ##
##                                                                                ##
####################################################################################

$redirecturl = 'inquiries.php';

####################################################################################
##                                                                                ##
##                           Do not edit below this.                              ##
##                                                                                ##
####################################################################################

include "../admin/checklicense.inc.php";
include "checklogin.inc.php";

// Open database...
$db = @mysqli_connect("$databaseserver", "$databaseuser", "$databasepasswd", "$databasename");

if (extension_loaded("imap")) {
	$result = @mysqli_query($db, "SELECT confvalue FROM emerchant_configuration WHERE confname='mailservertype'");
	$mailservertype = @mysqli_result($result,0,"confvalue");
} else $mailservertype = "pop3";

if ($checkmail == "true") {
	if ($mailservertype == "imap") {
		$inbox = 1;
		include "imap.php";
		$inbox = 2;
		include "imap.php";
	} else {
		$inbox = 1;
		include "pop3.php";
		$inbox = 2;
		include "pop3.php";
	}
}

header("Location: $redirecturl");
?>