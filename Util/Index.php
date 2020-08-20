<?php
    require_once(__DIR__ . '/../inc/all.inc.php');
    defineRelPath(__DIR__);

    $hd = new traqrDoc("Utils Page");
    $hd->htmlBeg();

    if ( authorized() ){
        $b = '';

        //if (defined('REL')) $b .= "REL == " . REL . "<br>\n";
        $b .= '<section>' . NL;

        $b .= "<p>This section houses a collection of mainly dev utils, some of which may be in dev themselves</p>\n";
        $b .= "<ul>\n";
        $b .= "<li>" . $hd->relUrl('Util/phpinfo.php','PHP Info') . "</li>\n";
        $b .= "<li>" . $hd->relUrl('ClearTestEntries.php','Clear Entries for *@test.ucsb.edu') . "</li>\n";
        $b .= "<li>" . $hd->relUrl('Util/dbSchema.php','DB Schema') . "</li>\n";
        $b .= "<li>" . $hd->relUrl('Enter.php','Entry Script (for Testing)') . "</li>\n";
        $b .= "<li>" . $hd->relUrl('About/Index.php','About Page') . "</li>\n";
        $b .= "<li><a href=\"http://traqr.eri.ucsb.edu/Enter.php?sd_uuid=\">Test link with UCSBNetID in it</li>\n";

        $b .= "</ul>\n";

        $b .= '</section>' . NL;
        print $b;
    }
    else print authFail();

    $hd->htmlEnd();
?>
