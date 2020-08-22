<?php
// Note, the PHP_AUTH_USER is not set unless you are within one of the privileged directories...
function authorized(){
    foreach($GLOBALS['authorizedIPs'] as $aip){
        //print "aIP: $aip, thisIP: {$_SERVER['REMOTE_ADDR']}<br>";
        if ($aip == $_SERVER['REMOTE_ADDR']) {
            //print "Match!!<br>";
            return TRUE;
        }
    }
    return FALSE;
}
// likely to not get used
// function authCheck(){
//     if ( ! authorized()){
//         $url = $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['SERVER_NAME'] . '/' . 'NotAuthorized.php';
//         header( "Location: $url" );
//     }
// }
function authFail(){
    return "<p><strong>IP ({$_SERVER['REMOTE_ADDR']}) Not Authorized to View this Content.</strong></p>\n";
}
?>
