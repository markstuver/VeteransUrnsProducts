<?php
$sessiondb = @mysqli_connect("$databaseserver", "$databaseuser", "$databasepasswd", "$databasename");

$date = date("Y/m/d H:i:s");
$sql = "SELECT * FROM emerchant_user WHERE sessionid = '$sesid'";
$result = @mysqli_query($sessiondb, $sql);
$emerchant_user = @mysqli_result($result,0,"username");
$activity = @mysqli_result($result,0,"activity");
if ($activity) $activitytime = strtotime($activity);
else $activitytime = 0;
$mailcheck = @mysqli_result($result,0,"mailcheck");
$inactivitytime = (strtotime($date) - $activitytime)/60;
$mailchecktime = (strtotime($date) - $mailcheck)/60;
if (!empty($sesid) && @mysqli_num_rows($result) == 1) {
	$sql = "UPDATE emerchant_user SET activity = '$date' WHERE sessionid = '$sesid'";
	@mysqli_query($sessiondb, $sql);
	$affiliateresult = @mysqli_query($sessiondb, "SELECT affiliateid FROM affiliate WHERE user='$emerchant_user'");
	$affiliateid = @mysqli_result($affiliateresult,0,"affiliateid");
	@mysqli_close($sessiondb);
} else {
    @mysqli_close($sessiondb);
	if (!$p3psent) header('P3P: CP="IDC DSP COR CURa ADMa OUR IND PHY ONL COM STA"');
	$p3psent = TRUE;
	SetCookie("sesid");
	if ($popuplogincheck) {
		echo "<html><head><title>$ashopname</title><script language=\"JavaScript\">opener.window.location.href='login.php';</script></head><body onLoad=\"this.close()\"></body></html>";
		exit;
	} else {
		if (strstr($SERVER_SOFTWARE, "IIS")) {
			echo "<html><head><meta http-equiv=\"Refresh\" content=\"0; URL=login.php\"></head></html>";
			exit;
		} else {
			header("Location: login.php");
			exit;
		}
	}
}
?>