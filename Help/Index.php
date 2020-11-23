<?php
    require_once(__DIR__ . '/../inc/all.inc.php');
    defineRelPath(__DIR__);

    $hd = new traqrDoc("Help Index");
    $hd->htmlBeg();
?>
<section>
    <h2>Introduction/History</h2>
    <p>We have not yet determined exactly where the documentation will actually reside.
        For the time being, much of the documentation will sit on the github repo.
    </p>
    <ul>
        <li><a href="https://github.com/ucsb-eri/TraQR/wiki">https://github.com/ucsb-eri/TraQR/wiki</a></li>
    </ul>
</section>

<?php

    $hd->htmlEnd();
?>
