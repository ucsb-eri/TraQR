<?php
    require_once(__DIR__ . '/../inc/all.inc.php');
    defineRelPath(__DIR__);

    $hd = new traqrDoc("DB Backup");
    $hd->htmlBeg();

    if ( authorized('TRAQR','admin') ){
        $ce = new traQRpdo(getDSN());
        print $ce->dbBackup();
    }
    else print authFail();

    $hd->htmlEnd();
?>
