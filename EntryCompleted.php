<?php
    require_once(__DIR__ . '/inc/all.inc.php');
    defineRelPath(__DIR__);

    $hd = new traqrDoc("Data Entry Point");
    $hd->htmlBeg();

    $b = '';
    $b .= "<h2>Thanks for using the scanner!</h2>
    <p>Please, stay safe:</p>
    <ul>
    <li>Wash Hands thoroughly and frequently</li>
    <li>Avoid touching your face with unwashed hands</li>
    <li>Maintain Social Distance when possible</li>
    <li>Wear a mask when unable to Social Distance</li>
    </ul>
    <img src=\"./media/traQR-safety.png\"><br>
    ";
    print $b;


    $hd->htmlEnd();
?>
