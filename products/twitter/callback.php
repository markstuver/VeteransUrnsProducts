<?php
/* Start session and load lib */
session_start();
require_once('twitteroauth/autoload.php');
use Abraham\TwitterOAuth\TwitterOAuth;
require_once('config.php');

// Connect to database...
$db = @mysqli_connect("$databaseserver", "$databaseuser", "$databasepasswd", "$databasename");

/* If the oauth_token is old redirect to the connect page. */
if (isset($_REQUEST['oauth_token']) && $_SESSION['oauth_token'] !== $_REQUEST['oauth_token']) {
  $_SESSION['oauth_status'] = 'oldtoken';
  header('Location: ./clearsessions.php');
}

/* Create TwitteroAuth object with app key/secret and token key/secret from default phase */
$connection = new TwitterOAuth(CONSUMER_KEY, CONSUMER_SECRET, $_SESSION['oauth_token'], $_SESSION['oauth_token_secret']);

/* Request access tokens from twitter */
$access_token = $connection->oauth("oauth/access_token", array("oauth_verifier" => $_REQUEST['oauth_verifier']));

/* Save the access tokens. Normally these would be saved in a database for future use. */
$twittertoken = $access_token['oauth_token'];
$twittertokensecret = $access_token['oauth_token_secret'];
@mysqli_query($db,"UPDATE preferences SET prefvalue='$twittertoken' WHERE prefname='twitteruser'");
@mysqli_query($db,"UPDATE preferences SET prefvalue='$twittertokensecret' WHERE prefname='twitterpass'");

/* Remove no longer needed request tokens */
unset($_SESSION['oauth_token']);
unset($_SESSION['oauth_token_secret']);

/* If HTTP response is 200 continue otherwise send to connect page to retry */
if ($connection->getLastHttpCode() == 200) {
  /* The user has been verified and the access tokens can be saved for future use */
  header('Location: ../admin/sendtweet.php');
} else {
  /* Save HTTP status for error dialog on connnect page.*/
  header('Location: ./clearsessions.php');
}
