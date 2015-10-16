<?php
// Mail cleaner for eMerchant...

include "../admin/config.inc.php";

// Open database...
$db = @mysqli_connect("$databaseserver", "$databaseuser", "$databasepasswd", "$databasename");

$result = @mysqli_query($db, "SELECT * FROM emerchant_inbox");
while ($row = @mysqli_fetch_array($result)) {
	if (($row["subject"] ==  "failure notice" || $row["subject"] == "Undelivered Mail Returned to Sender" || $row["subject"] == "Delivery Status Notification (Failure)" || $row["subject"] == "Returned mail: User unknown") && ($row["name"] == "Unknown" || $row["name"] == "Mail Delivery System" || $row["name"] == "Mail Delivery Subsystem")) {
		@mysqli_query($db, "DELETE FROM emerchant_inbox WHERE id='{$row["id"]}'");
		unlink("$ashoppath/emerchant/mail/in1-{$row["id"]}");
		@mysqli_query($db, "UPDATE customer SET allowemail='0' WHERE email='{$row["email"]}'");
	} else if (strtoupper($row["subject"]) == "REMOVE") {
		@mysqli_query($db, "DELETE FROM emerchant_inbox WHERE id='{$row["id"]}'");
		unlink("$ashoppath/emerchant/mail/in1-{$row["id"]}");
		@mysqli_query($db, "UPDATE customer SET allowemail='0' WHERE email='{$row["email"]}'");
	}		
}

// Close database connection...
@mysqli_close($popdb);
header("Location: index.php");
?>