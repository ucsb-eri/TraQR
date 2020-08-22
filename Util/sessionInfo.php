<?php
require_once(__DIR__ . '/../inc/all.inc.php');
defineRelPath(__DIR__);

$hd = new traqrDoc("SESSION Info");
$hd->htmlBeg();

if ( authorized() ){
    print_pre($_SESSION,"SESSION Info");
}

$hd->htmlEnd();

?>
