<?php
require_once(__DIR__ . '/../inc/all.inc.php');
defineRelPath(__DIR__);

$hd = new traqrDoc("SESSION Info");
$hd->htmlBeg();

?>
<p>
    This page will have various sections representing the different roles available within TraQR.<br>
    Each section will cover one role.  <br>
    The heading should be viewable by anyone but the content below should be restricted to certain roles.
<p>

<?php
if ( authorized() ){
    print_pre($_SESSION,"SESSION Info");
}

foreach(array_keys($authFlags) as $role){
    print "<hr>\n";
    print "<section>\n";
    print ($role == '') ? "<h3>BLANK (should never match)</h3>\n" : "<h3>$role</h3>\n" ;
    if (authorized('TRAQR',$role)){
        print "<p>\n";
        print "This is the content that role: $role should be able to see";
        print "</p>\n";
    }
    print "</section>\n";
}

$hd->htmlEnd();

?>
