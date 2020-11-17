#!/usr/bin/php
<?php
date_default_timezone_set('America/Los_Angeles');
$ctime = time();
if( isset($_SERVER['argv'][1]) ){
  $file = $_SERVER['argv'][1];
  if( file_exists($file)){
    $ctime = filemtime($file);
  }
}
$str = strftime('%Y.%m.%d',$ctime);
print "$str\n";

?>
