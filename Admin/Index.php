<?php
    require_once(__DIR__ . '/../inc/all.inc.php');
    defineRelPath(__DIR__);

    $hd = new traqrDoc("Utils Page");
    //$hd->css(CSSFILE);

    $hd->htmlBeg();

    if ( authorized() ){
        $b = '';

        //if (defined('REL')) $b .= "REL == " . REL . "<br>\n";
        $b .= '<section>' . NL;
        $b .= "<p>This section houses the primary administrative tools</p>\n";
        $b .= "<ul>\n";
        $b .= "<li>" . $hd->relUrl('Admin/ReportDailyByIdent.php','Report Daily Breakdown (by Ident)') . "</li>\n";
        $b .= "<li>" . $hd->relUrl('Admin/ReportDaily.php','Report Daily Breakdown') . "</li>\n";
        $b .= "<li>" . $hd->relUrl('Admin/ReportAll.php','Report All Data') . "</li>\n";
        $b .= "<li>" . $hd->relUrl('Admin/qrGenMgmt.php','QR Code Management - regeneration') . "</li>\n";
        $b .= "<li>" . $hd->relUrl('Admin/IdentifierMgmt.php','Identifier Management ') . "</li>\n";
        $b .= "</ul>\n";

        $b .= "<p>This section houses a collection of administrative utils, some of which may be in dev themselves</p>\n";
        $b .= "<ul>\n";
        $b .= "<li>" . $hd->relUrl('Admin/MergeRecords.php','Merge Records - in dev, hardwired records at the moment') . "</li>\n";
        $b .= "<li>" . $hd->relUrl('Admin/emailAddresses.php','generate email addresses from data') . "</li>\n";
        $b .= "<li>" . $hd->relUrl('ClearTestEntries.php','Clear Entries for *@test.ucsb.edu') . "</li>\n";
        $b .= "<li>" . $hd->relUrl('Admin/Login.php','Login to appropriate realm for this site') . "</li>\n";
        $b .= "<li>" . $hd->relUrl('Admin/Logout.php','Logout') . "</li>\n";
        $b .= "</ul>\n";


        $b .= "<p>This section houses a collection of mainly dev utils, some of which may be in dev themselves</p>\n";
        $b .= "<ul>\n";
        $b .= "<li>" . $hd->relUrl('util/phpinfo.php','PHP Info') . "</li>\n";
        $b .= "<li>" . $hd->relUrl('util/dbSchema.php','DB Schema') . "</li>\n";
        $b .= "<li>" . $hd->relUrl('Enter.php','Entry Script (for Testing)') . "</li>\n";
        $b .= "<li>" . $hd->relUrl('About/Index.php','About Page') . "</li>\n";
        $b .= "</ul>\n";

        $b .= "<p>User: " . $_SERVER['PHP_AUTH_USER'] . "</p>\n";

        $b .= '</section>' . NL;
        print $b;
    }
    else print authFail();

    $hd->htmlEnd();
?>
