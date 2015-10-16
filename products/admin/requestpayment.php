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
include "template.inc.php";

// Open database...
$db = @mysqli_connect("$databaseserver", "$databaseuser", "$databasepasswd", "$databasename");
@mysqli_query($db, "UPDATE user SET requestpayment='1' WHERE userid='$userid'");
@mysqli_close($db);

echo "$header
<table bgcolor=\"#$adminpanelcolor\" height=\"50\" width=\"100%\"><tr valign=\"middle\" align=\"center\"><td class=\"heading1\" colspan=\"2\">Manage Sales</td></tr>
  <tr align=\"center\" class=\"nav\">  
    <td width=\"50%\" nowrap><a href=\"salesadmin.php\" class=\"nav\">Customers and Messaging</a></td>
    <td width=\"50%\" nowrap><a href=\"salesreport.php\" class=\"nav\">Sales Reports</a></td>
<tr>
</table>
</td></tr></table>
<center><p align=\"center\" class=\"confirm\">Payment request has been received!</p></center>$footer";
?>