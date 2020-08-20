<?php
require_once(__DIR__ . '/../inc/all.inc.php');
defineRelPath(__DIR__);

$hd = new traqrDoc("PHP Info");
$hd->htmlBeg();

if ( authorized() ){
    phpinfo();
}

$hd->htmlEnd();

?>
