<?php
require_once(__DIR__ . '/inc/all.inc.php');
defineRelPath(__DIR__);

$ta = new traqrAuth();
$ta->checkPost();

//$cmgr = new traQRMgr(7);
$hd = new traqrDoc("Login");
$hd->htmlBeg();

print '<section>' . NL;

print $ta->loginForm();

print '</section>' . NL;

//print '<a href="Logout.php">Logout</a><br>';
$hd->htmlEnd();

// if (isset($_SERVER['PHP_AUTH_USER'])){
//     header('HTTP/1.0 401 Unauthorized');
//     print "Some Logout Content????<br>\n";
// }
?>
