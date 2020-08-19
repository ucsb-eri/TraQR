<?php
////////////////////////////////////////////////////////////////////////////////
function print_pre($var,$label){
    print "<hr>";
    print "$label";
    print "<pre>";
    print_r($var);
    print "</pre>";
}
function genUUID($id,$bldg,$room){
    $mergedStr = "$id, $bldg, $room";
    return md5($mergedStr);
}

?>
