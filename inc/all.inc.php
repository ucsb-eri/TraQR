<?php
session_start();

error_reporting(E_ALL);
ini_set('display_errors', 1);

define('NL',"\n");

// The entries in this next section are vaules that might change per install
define('MAX_BUILDING_ROOM_COMBOS',7);
define('INVALIDATE_CONFIRM_SECONDS',10);

// trying to deal with the classic PHP vs HTML access problem
// defineRelPath needs to be run as soon after this file is required but in the
// actual script (since it may be at a different directory level)
function defineRelPath($dir){
    foreach(array('./','../','../../','../../../') as $rel){
        // $srp = realpath($_SERVER['DOCUMENT_ROOT']);
        // $irp = realpath($dir . '/' . $rel);
        // print "srp: $srp, irp: $irp<br>\n";
        if( realpath($_SERVER['DOCUMENT_ROOT']) == realpath($dir . '/' . $rel) ){
            if ( ! defined('REL')) define('REL',"$rel");
            break;
        }
        //else define('REL',"./");
    }
}
// DB needs to be used in conjuntion with REL
define('DB','/var/dbs/traqr.sqlite3');
define('BKDIR','/var/bks/');
define('BASEURL',$_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['SERVER_NAME']);

function getDSN(){
    return 'sqlite:' . REL . '/' . DB;
}

require_once(__DIR__ . '/../config.php');
require_once(__DIR__ . '/../version.php');
require_once(__DIR__ . '/utils.inc.php');
require_once(__DIR__ . '/auth.inc.php');
require_once(__DIR__ . '/traqrAuth.inc.php');
require_once(__DIR__ . '/pdo.inc.php');
require_once(__DIR__ . '/traQRpdo.inc.php');
require_once(__DIR__ . '/traqrDoc.inc.php');
require_once(__DIR__ . '/traqrMgr.inc.php');
require_once(__DIR__ . '/traqrCode.inc.php');

?>
