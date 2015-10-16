<?php
//foreach ($_SERVER as $key=>$value) echo "$key = $value<br>";
$ipnumber = $_SERVER["REMOTE_ADDR"];
$username = $_SERVER["PHP_AUTH_USER"];
$password = $_SERVER["PHP_AUTH_PW"];
$logintime = date("Y-m-d H:i:s", time()+$timezoneoffset);
echo "$logintime: access by $username, identified with: $password from IP: $ipnumber";
?>