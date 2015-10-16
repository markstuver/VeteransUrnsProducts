<?php
// AShop
// Copyright 2015 - AShop Software - http://www.ashopsoftware.com
//
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; either version 2
// of the License, or (at your option) any later version.
//
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with this program; if not, see: http://www.gnu.org/licenses/.

include "config.inc.php";
include "ashopfunc.inc.php";
include "checklogin.inc.php";
include "ashopconstants.inc.php";
include "template.inc.php";
// Get language module...
include "language/$adminlang/editcatalog.inc.php";
include "../twitter/twitteroauth/autoload.php";
use Abraham\TwitterOAuth\TwitterOAuth;

if (empty($twitteruser) || empty($twitterpass)) {
	session_start();
	$connection = new TwitterOAuth('AMpxRgN4BiD7kZdolGUP5Q', 'Jpy78OWhK3T2qenpUhzzVg8EttByoCMIKfdfKAQ');
	$request_token = $connection->oauth('oauth/request_token', array('oauth_callback' => "$ashopurl/twitter/callback.php"));
	$_SESSION['oauth_token'] = $token = $request_token['oauth_token'];
	$_SESSION['oauth_token_secret'] = $request_token['oauth_token_secret'];
	switch ($connection->getLastHttpCode()) {
		case 200:
			$url = $connection->url('oauth/authorize', array('oauth_token' => $request_token['oauth_token']));
			header('Location: ' . $url); 
			break;
		default:
			echo COULDNOTCONNECT;
	}
	exit;
}


// Get context help for this page...
$contexthelppage = "twitter";
include "help.inc.php";

// Connect to database...
$db = @mysqli_connect("$databaseserver", "$databaseuser", "$databasepasswd", "$databasename");

// Get product information...
$result = @mysqli_query($db, "SELECT * FROM product WHERE productid='$productid'");
$productname = @mysqli_result($result, 0, "name");
$url = @mysqli_result($result, 0, "detailsurl");
if (!$url) {
	$url = "$ashopurl/index.php?product=$productid";
}
$result = @mysqli_query($db, "SELECT * FROM discount WHERE productid='$productid' ORDER BY discountid DESC LIMIT 1");
$discountcode = @mysqli_result($result, 0, "code");

// Shorten the URL...
$shorturl = ashop_bitlyshorten($url);

// Print product flags form...
if (!$tweet) {
  echo "<html><head><title>".PRODUCTTWITTER."</title></head><body bgcolor=\"#66ccee\" text=\"#196384\" link=\"#196384\"><center><img src=\"images/twitterlogo.gif\" align=\"left\"><form action=\"sendtweet.php\" method=\"post\"><font face=\"Arial, Helvetica, sans-serif\" size=\"2\"><b>".ANNOUNCEMENTTYPE.": <select name=\"tweettype\"><option value=\"new\">".NEWPRODUCT."</option><option value=\"update\">".PRODUCTUPDATE."</option>";
  if ($discountcode) echo "<option value=\"discount\">".DISCOUNTCODE."</option>";
  echo "</select><br><br><input type=\"hidden\" name=\"productid\" value=\"$productid\"><input type=\"submit\" name=\"tweet\" value=\"".PUBLISH."\" style=\"background-color: #297394; color: #FFF; font-weight: bold;\"></form><br><font size=\"2\"><a href=\"javascript:this.close()\">".CLOSETHISWINDOW."</a></font></b></font><br></center></body></html>";
} else {
	$connection = new TwitterOAuth('AMpxRgN4BiD7kZdolGUP5Q', 'Jpy78OWhK3T2qenpUhzzVg8EttByoCMIKfdfKAQ', $twitteruser, $twitterpass);
	$content = $connection->get('account/verify_credentials');
	if ($tweettype == "new") $user_text = $twittermessages["new"];
	else if ($tweettype == "update") $user_text = $twittermessages["update"];
	else if ($tweettype == "discount") $user_text = $twittermessages["discount"];
	$user_text = str_replace("%productname%",$productname,$user_text);
	$user_text = str_replace("%shorturl%",$shorturl,$user_text);
	$user_text = str_replace("%discountcode%",$discountcode,$user_text);
	$connection->post('statuses/update', array('status' => $user_text));
	echo "<html><head><title>".PRODUCTTWEETSENT."</title></head><body bgcolor=\"#66ccee\" text=\"#196384\" link=\"#196384\"><center><img src=\"images/twitterlogo.gif\" align=\"left\"><font face=\"Arial, Helvetica, sans-serif\" size=\"2\"><b>".THEFOLLOWINGHASBEENSENT.":<br><br><p style=\"background-color: #FFFFFF;\"><i>$user_text</span></i></p><br><font size=\"2\"><a href=\"javascript:this.close()\">".CLOSETHISWINDOW."</a></font></b></font></center></body></html>";
}
?>