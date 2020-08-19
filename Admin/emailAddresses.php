<?php
    require_once(__DIR__ . '/../inc/all.inc.php');
    defineRelPath(__DIR__);

    $hd = new traqrDoc("Email Addresses Page");
    $hd->css(CSSFILE);

    $hd->htmlBeg();

    if ( authorized() ){
        $b = '';
        $ce = new covidqrEntryNew('sqlite:' . REL . '/' . DB);
        $b .= $ce->generateEmailAddresses();
        print $b;
    }
    else print authFail();

    $hd->htmlEnd();
?>
