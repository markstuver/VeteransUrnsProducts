<?php
// AShop
// Copyright 2002-2015 - All Rights Reserved Worldwide
// http://www.ashopsoftware.com
// This software is licensed per individual site.
// By installing or using this software, you agree to the licensing terms,
// which are located at http://www.ashopsoftware.com/license.htm
// Unauthorized use or distribution of this software 
// is a violation U.S. and international copyright laws.

@set_time_limit(0);
error_reporting (E_ALL ^ E_NOTICE);
// Remind the user to change the old name of the config file for security reasons...
   if (file_exists("admin/config.inc")) {
	   echo "<html><head><title>Security update!</title></head><body bgcolor=\"#FFFFFF\" text=\"#000000\"><center><h2>Security update!</h2><p>For security reasons you should change the name of your <b>config.inc</b> file, which can be found in the admin directory, to: <b>config.inc.php</b></p></body></html>";
	   exit;
   }

// AShop database initialization...

   $updating = TRUE;
   include "admin/version.inc.php";
   include "admin/config.inc.php";
   include "admin/ashopfunc.inc.php";

// Open database...

   $db = @mysqli_connect("$databaseserver", "$databaseuser", "$databasepasswd", "$databasename");
   if (!$db) $error = 1;

// Check if privileges are sufficient...
@mysqli_query($db, "DROP TABLE privtesttable");
@mysqli_query($db, "CREATE TABLE privtesttable (testid int not null, orderid int)");
if (@mysqli_error()) {
	echo "<html><head><title>Database error!</title></head>
         <body bgcolor=\"#FFFFFF\" text=\"#000000\" link=\"#000000\" vlink=\"#000000\" alink=\"#000000\"><table width=\"75%\" border=\"0\" cellspacing=\"0\" cellpadding=\"0\" align=\"center\">
	     <tr bordercolor=\"#000000\" align=\"center\"><td><table width=\"100%\" border=\"0\" cellspacing=\"0\" cellpadding=\"5\">
 		 <tr align=\"center\"><td> <img src=\"admin/images/logo.gif\"><br><hr width=\"50%\" size=\"0\" noshade>
		 </td></tr></table><p><font face=\"Arial, Helvetica, sans-serif\"><p><font size=\"3\"><b>Database error!</b></font>
	     <p><font size=\"2\">The database user does not have privileges to add tables!<br>Ask your hosting provider to give your database user privileges to add and modify database tables!</font></p></font></td></tr></table></body></html>";
	exit;
} else {
	@mysqli_query($db, "ALTER TABLE privtesttable ADD anotherfield VARCHAR(3)");
	if (@mysqli_error()) {
		echo "<html><head><title>Database error!</title></head>
         <body bgcolor=\"#FFFFFF\" text=\"#000000\" link=\"#000000\" vlink=\"#000000\" alink=\"#000000\"><table width=\"75%\" border=\"0\" cellspacing=\"0\" cellpadding=\"0\" align=\"center\">
	     <tr bordercolor=\"#000000\" align=\"center\"><td><table width=\"100%\" border=\"0\" cellspacing=\"0\" cellpadding=\"5\">
 		 <tr align=\"center\"><td> <img src=\"admin/images/logo.gif\"><br><hr width=\"50%\" size=\"0\" noshade>
		 </td></tr></table><p><font face=\"Arial, Helvetica, sans-serif\"><p><font size=\"3\"><b>Database error!</b></font>
	     <p><font size=\"2\">The database user does not have privileges to modify tables!<br>Ask your hosting provider to give your database user privileges to modify the structure of database tables!</font></p></font></td></tr></table></body></html>";
		 @mysqli_query($db, "DROP TABLE privtesttable");
		 exit;
	} else {
		@mysqli_query($db, "DROP TABLE privtesttable");
		if (@mysqli_error()) {
			echo "<html><head><title>Database error!</title></head>
			<body bgcolor=\"#FFFFFF\" text=\"#000000\" link=\"#000000\" vlink=\"#000000\" alink=\"#000000\"><table width=\"75%\" border=\"0\" cellspacing=\"0\" cellpadding=\"0\" align=\"center\">
			<tr bordercolor=\"#000000\" align=\"center\"><td><table width=\"100%\" border=\"0\" cellspacing=\"0\" cellpadding=\"5\">
			<tr align=\"center\"><td> <img src=\"admin/images/logo.gif\"><br><hr width=\"50%\" size=\"0\" noshade>
			</td></tr></table><p><font face=\"Arial, Helvetica, sans-serif\"><p><font size=\"3\"><b>Database error!</b></font>
			<p><font size=\"2\">The database user does not have privileges to delete tables!<br>Ask your hosting provider to give your database user privileges to delete database tables!</font></p></font></td></tr></table></body></html>";
			exit;
		}
	}
}

@mysqli_query($db, "ALTER TABLE affiliate ADD excludecategories VARCHAR(500)");
@mysqli_query($db, "ALTER TABLE affiliate ADD excludeproducts VARCHAR(500)");
@mysqli_query($db, "ALTER TABLE affiliate ADD hideprice INT");

// Redirect user to admin area...

if (!$error) header("Location: admin/index.php");
else header("Location: admin/index.php?error=$error");
?>