<?php
////////////////////////////////////////////////////////////////////////////////
function print_pre($var,$label){
    print "<hr>";
    print "$label";
    print "<pre>";
    print_r($var);
    print "</pre>";
}
////////////////////////////////////////////////////////////////////////////////
function genUUID($id,$bldg,$room){
    $mergedStr = "$id, $bldg, $room";
    return md5($mergedStr);
}
////////////////////////////////////////////////////////////////////////////////
function seconds2hr($secs){
    return sprintf('%d:%02d:%02d',($secs/3600),(($secs%3600)/60),($secs%60));
}
/**
 * GZIPs a file on disk (appending .gz to the name)
 *
 * From http://stackoverflow.com/questions/6073397/how-do-you-create-a-gz-file-using-php
 * Based on function by Kioob at:
 * http://www.php.net/manual/en/function.gzwrite.php#34955
 *
 * @param string $source Path to file that should be compressed
 * @param integer $level GZIP compression level (default: 9)
 * @return string New filename (with .gz appended) if success, or false if operation fails
 */
function gzCompressFile($source, $level = 9){
    $dest = $source . '.gz';
    $mode = 'wb' . $level;
    $error = false;
    if ($fp_out = gzopen($dest, $mode)) {
        if ($fp_in = fopen($source,'rb')) {
            while (!feof($fp_in))
                gzwrite($fp_out, fread($fp_in, 1024 * 512));
            fclose($fp_in);
        } else {
            $error = true;
        }
        gzclose($fp_out);
    } else {
        $error = true;
    }
    if ($error)
        return false;
    else
        return $dest;
}
////////////////////////////////////////////////////////////////////////////////
function alertBanner($class = 'failure',$mesg = 'Fatal Error'){
    if ( $class == '') $class = 'failure';
    $b = "<div class=\"alertBanner $class\">";
    $b .= "$mesg";
    $b .= '</div>';
    return $b;
}
////////////////////////////////////////////////////////////////////////////////
function readCSV($csvfile,$header = TRUE){
    $data = array();
    if ( ! file_exists($csvfile)) return $data;
    if (($handle = fopen($csvfile, "r")) !== FALSE) {
        if (($headerData = fgetcsv($handle, 8000, ",")) !== FALSE){
            $headerFields = count($headerData);
        }
        else    return $data;
        // we could try to validate data here, compare number of fields for header and see if datarow len matches...
        while (($datarow = fgetcsv($handle, 8000, ",")) !== FALSE) {
            $num = count($datarow);
            $data[] = array_combine($headerData,$datarow);
        }
        fclose($handle);
    }
    return $data;
}
////////////////////////////////////////////////////////////////////////////////
function endsWith($string, $endString) { 
    $len = strlen($endString);
    if ($len == 0) {
        return true;
    }
    return (substr($string, -$len) === $endString);
}




?>
