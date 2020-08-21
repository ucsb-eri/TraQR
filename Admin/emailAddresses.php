<?php
    require_once(__DIR__ . '/../inc/all.inc.php');
    defineRelPath(__DIR__);

    $hd = new traqrDoc("Email Addresses Page");
    $hd->htmlBeg();

    if ( authorized() ){
        $b = '';
        $ce = new traQRpdo(getDSN());
        $b .= $ce->generateEmailAddresses();
        print $b;
    }
    else print authFail();

    $hd->htmlEnd();
?>
