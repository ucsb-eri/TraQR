<?php
    require_once(__DIR__ . '/inc/all.inc.php');
    defineRelPath(__DIR__);

    $hd = new traqrDoc("Entry Completed");
    $hd->htmlBeg();

    $b = '';
    $b .= "<section class=\"entry-completed\"><h2 >Thanks for using the scanner!</h2>
    <p class=\"entry-completed\"><strong>Please, stay safe:</strong></p>
    <ul class=\"entry-completed\">
    <li>Wash Hands thoroughly and frequently</li>
    <li>Avoid touching your face with unwashed hands</li>
    <li>Maintain Social Distance when possible</li>
    <li>Wear a mask when unable to Social Distance</li>
    </ul>
    <img src=\"./media/traQR-safety.png\"><br>
    </section>
    ";
    print $b;


    $hd->htmlEnd();
?>
