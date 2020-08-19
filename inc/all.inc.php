<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

define('NL',"\n");
define('MAX_BUILDING_ROOM_COMBOS',7);
// trying to make this work for both PHP and HTML access, but that is challanging
// as soon as htmlDoc code at runtime from a subdir, things break.
define('CSSFILE','css/covidqr.css');

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
define('DB','/var/dbs/covidqr.sqlite3');
define('BASEURL',$_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['SERVER_NAME']);

function getDSN(){
    return 'sqlite:' . REL . '/' . DB;
}

require_once(__DIR__ . '/../Config.php');
require_once(__DIR__ . '/utils.inc.php');
require_once(__DIR__ . '/auth.inc.php');
require_once(__DIR__ . '/pdo.inc.php');
require_once(__DIR__ . '/pdo-extended.inc.php');
require_once(__DIR__ . '/traqrDoc.inc.php');
require_once(__DIR__ . '/covidqr-lib.inc.php');

// gonna allow this to be done in covidqr-lib.inc.php because that is what depends on it
//require_once(__DIR__ . '/../ext/phpqrcode/qrlib.php');

?>
