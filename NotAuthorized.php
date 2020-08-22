<?php
// likely not needed anymore
    require_once(__DIR__ . '/inc/all.inc.php');
    $hd = new traqrDoc("Not Authorized");
    $hd->htmlBeg();

    print '<section>' . NL;

    print "<h2>Sorry the visiting IP is not authorized for administrative functions</h2>";

    print '</section>' . NL;
    $hd->htmlEnd();
?>
