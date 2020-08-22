<?php
// function logout() {
//     header('WWW-Authenticate: Basic realm="Test Authentication System"');
//     header('HTTP/1.0 401 Unauthorized');
//     echo "You must enter a valid login ID and password to access this resource\n";
//     exit;
// }
require_once(__DIR__ . '/inc/all.inc.php');
defineRelPath(__DIR__);
$ta = new traqrAuth();
print $ta->unsetSessionAuth();
header("Location: " . $_SERVER['HTTP_REFERER']);

// //$cmgr = new traQRMgr(7);
// $hd = new traqrDoc("Login");
// $hd->htmlBeg();
//
// //$ta->checkPost();
//
// print '<section>' . NL;
//
//
// print '</section>' . NL;
//
// //print '<a href="Logout.php">Logout</a><br>';
// $hd->htmlEnd();

// if (isset($_SERVER['PHP_AUTH_USER'])){
//     header('HTTP/1.0 401 Unauthorized');
//     print "Some Logout Content????<br>\n";
// }
?>
