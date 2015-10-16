<?php
if (empty($databaseserver) || empty($databaseuser)) {
	include "../admin/config.inc.php";
}

define('CONSUMER_KEY', 'AMpxRgN4BiD7kZdolGUP5Q');
define('CONSUMER_SECRET', 'Jpy78OWhK3T2qenpUhzzVg8EttByoCMIKfdfKAQ');
define('OAUTH_CALLBACK', "$ashopurl/twitter/callback.php");
?>