<?php
// each of the various roles includes access to roles below it
$authFlags = array(
    ''      => 0x00,
    'none'  => 0x01,
    'user'  => 0x02,
    'data'  => 0x04,
    'admin' => 0x08,
    'dev'   => 0x10,
    'root'  => 0x20,
);
$authMasks = array(
    ''      => 0x00,
    'none'  => 0x01,
    'user'  => 0x03,
    'data'  => 0x07,
    'admin' => 0x0f,
    'dev'   => 0x1f,
    'root'  => 0x3f,
);
////////////////////////////////////////////////////////////////////////////////
// Note, the PHP_AUTH_USER is not set unless you are within one of the privileged directories...
function authorized($mode = 'DEFAULT',$authLevelRequired = 'admin'){
    switch ($mode) {
        case 'IP':
            return authorizedByIP();
            break;
        case 'DEFAULT':
        case 'TRAQR':
            return authorizedByTraqrInternal($authLevelRequired);
            break;
        default:
            return false;
            break;
    }
    return false;
}
////////////////////////////////////////////////////////////////////////////////
function authorizedByIP($authLevelRequired = ''){
    foreach($GLOBALS['authorizedIPs'] as $aip){
        //print "aIP: $aip, thisIP: {$_SERVER['REMOTE_ADDR']}<br>";
        if ($aip == $_SERVER['REMOTE_ADDR']) {
            //print "Match!!<br>";
            return TRUE;
        }
    }
    return FALSE;
}
////////////////////////////////////////////////////////////////////////////////
function authorizedByTraqrInternal($authLevelRequired = ''){
    global $authFlags;
    global $authMasks;
    //print "authLevelRequired: $authLevelRequired<br>\n";
    if ($authLevelRequired == '') return false;

    // check against hardwired IPs first, but need to cycle through
    if (array_key_exists('authorizedIPs',$GLOBALS)){
        foreach( array_keys($GLOBALS['authorizedIPs']) as $role){
            if ( ($authMasks[$role] & $authFlags[$authLevelRequired]) > 0 ){
                // print_pre($GLOBALS['authorizedIPs'][$role],"checking IP list for {$_SERVER['REMOTE_ADDR']} for required auth level: $authLevelRequired");
                if( in_array($_SERVER['REMOTE_ADDR'],$GLOBALS['authorizedIPs'][$role])){
                    $_SESSION['au_role'] = $role;
                    return true;
                }
            }
        }
    }

    // now check against any login role
    if (! isset($_SESSION)) return false;
    if (! array_key_exists('au_role',$_SESSION)) return false;
    $role = $_SESSION['au_role'];
    if (! array_key_exists($role,$authFlags))              return false;   // have an invalid role designation
    if (! array_key_exists($authLevelRequired,$authFlags)) return false;   // have an invalid role designation

    $maskCheck = $authMasks[$role] & $authFlags[$authLevelRequired];
    //print sprintf('maskCheck: 0x%0x - 0x%0x & 0x%0x',$maskCheck,$authMasks[$role],$authFlags[$authLevelRequired]) . "<br>\n";
    return ($maskCheck > 0) ? true : false;
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
