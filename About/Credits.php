<?php
    require_once(__DIR__ . '/../inc/all.inc.php');
    defineRelPath(__DIR__);

    $hd = new traqrDoc("Credits");
    $hd->htmlBeg();
?>
<section>
    <h2>Development Team</h2>
    <ul>
        <li>Aaron Martin - Initial Creation and Development Lead</li>
    </ul>
</section>
<section>
    <h2>Technologies</h2>
    <p>Want to keep the site relatively simple and self-contained</p>
    <ul>
        <li>PHP-7.3+</li>
        <li>Javascript</li>
        <li>CSS</li>
    </ul>
</section>

<?php

    $hd->htmlEnd();
?>
