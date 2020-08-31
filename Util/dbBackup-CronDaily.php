<?php
    require_once(__DIR__ . '/../inc/all.inc.php');
    defineRelPath(__DIR__);

    $hd = new traqrDoc("DB Backup");
    $hd->htmlBeg();

    // this is designed specifically for running via cron on local system
    // apache auth needs to allow server to run script (Require local) and
    // the check is a double check on that and to insure that we cannot execute
    // this via a remote web request
    if ( $_SERVER['SERVER_ADDR'] == $_SERVER['REMOTE_ADDR']){
        $ce = new traQRpdo(getDSN());
        print $ce->dbBackup(true);
    }
    else print authFail();

    $hd->htmlEnd();
?>
