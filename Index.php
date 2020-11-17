<?php
    require_once(__DIR__ . '/inc/all.inc.php');
    defineRelPath(__DIR__);

    //$cmgr = new traQRMgr(7);
    $hd = new traqrDoc("ERI TraQR Portal");
    $hd->htmlBeg();
?>

<section>
    <p>
    The goal of this site is to help traq building occupancy during UCSB's staged ramp-ups,
    using QR codes for Individuals specific to Building and Room.
    <br>
    The idea is to collect the data in a form that is easier to harvest than what we have seen so far in other methods.
    </p>
    <p>
    Visitors to this site will have only limited functionality; basically limited to the INGRESS and EGRESS scanning capability and a few information pages.
    </p>
    <p>
    Administrators (checked via IP or by logging in at the upper right of the page) will have access to additional administrative/reporting/management options via the nav menu at the top of the page.
    </p>
    <p>
    When the QR code is scanned and the resulting site visited the form creates a sqlite3 db entry from that information with appropriate timestamps.
    </p>
</section>

<?php
    $hd->htmlEnd();
?>
