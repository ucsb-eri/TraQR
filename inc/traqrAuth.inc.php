<?php
////////////////////////////////////////////////////////////////////////////////
class traqrAuth {
    function __construct(){
        $this->data = array();
        $this->dbFields = array('au_user','au_hash','au_role');
        $this->db = new traqrPDO(getDSN());
    }
    ///////////////////////////////////////////////////////////////////////////
    function unsetSessionAuth(){
        foreach($this->dbFields as $f) unset($_SESSION[$f]);
    }
    ///////////////////////////////////////////////////////////////////////////
    function setSessionAuth($pwe){
        foreach($this->dbFields as $f){
            if( array_key_exists($f,$pwe) && $pwe[$f] != '') $_SESSION[$f] = $pwe[$f];
            else return false;
        }
        return true;
    }
    ///////////////////////////////////////////////////////////////////////////
    function loginSessionDataExists(){
        // first see if the required session vars are set
        foreach($this->dbFields as $f){
            if( array_key_exists($f,$_SESSION) && $_SESSION[$f] != '') {
                //print "_SESSION[$f] exists and is not empty<br>";
            }
            else return FALSE;
        }
        return TRUE;
    }
    ///////////////////////////////////////////////////////////////////////////
    function loginSessionDataValidates(){
        $entries = $this->db->getKeyedHash('au_id',"SELECT * FROM auth WHERE au_user = ? AND au_md5 = ?;",array($_SESSION['au_user'],$_SESSION['au_md5']));
        if (count($entries) > 0){
            $pwe = array_shift($entries);
            foreach($this->dbFields as $f){
                if( array_key_exists($f,$pwe) && $_SESSION[$f] == $pwe[$f]) {}
                else return FALSE;
            }
            return TRUE;
        }

    }
    ///////////////////////////////////////////////////////////////////////////
    function loggedIn(){
        // need to check the auth info
        if ( ! $this->loginSessionDataExists()) {
            //print "session data missing<br>";
            return FALSE;
        }
        // now we validate the info
        if ( $this->loginSessionDataValidates()) return TRUE;
        return FALSE;
    }
    ///////////////////////////////////////////////////////////////////////////
    function checkPost(){
        //print_pre($_POST,__CLASS__ . '::' . __METHOD__ . " POST values");
        $f = 'au_user';
        if ( array_key_exists($f,$_POST)){
            $this->data[$f] = preg_replace('/[^a-zA-Z0-9]/','',trim(filter_input(INPUT_POST,$f,FILTER_SANITIZE_STRING)));
        }
        else return;

        //         $f = 'au_md5';
        //         if ( array_key_exists($f,$_POST)){
        //             $this->data[$f] = md5(trim(filter_input(INPUT_POST,$f,FILTER_SANITIZE_STRING)));
        // //            $this->data[$f] = md5(trim(filter_input(INPUT_POST,$f,FILTER_SANITIZE_STRING)));
        //         }
        //         else return;
                // $f = 'passwd';
        $f = 'password';
        if ( array_key_exists($f,$_POST)){
            $uepw = str_replace('+','.',trim(filter_input(INPUT_POST,$f,FILTER_SANITIZE_STRING)));
        }
        else return;

        //print_pre($this->data,__CLASS__ . '::' . __METHOD__ . " data values");

    // //    $dbhash = $this->db->fetchValNew("SELECT au_hash FROM auth WHERE au_user = ?;",array($this->data['au_user']));
    //     $entries = $this->db->getKeyedHash('au_id',"SELECT * FROM auth WHERE au_user = ? AND au_md5 = ?;",array($this->data['au_user'],$this->data['au_md5']));
    //     if( count($entries) > 0){
    //         $pwe = array_shift($entries);
    //     }
    //     // if empty, then invalid user
    //     if( $hash == ''){
    //         $valid = FALSE;
    //     }
    //     else {
    //         $checkhash = crypt($passwd,$dbhash);
    //         if( $checkhash == $dbhash ){
    //             $valid = TRUE;
    //             //$this->setSessionAuth(array('au_user' => $this->data['au_user'],'au_hash' => "$checkhash"));
    //         }
    //         else {
    //             $valid = FALSE;
    //         }
    //     }
    //     if ( $valid )   $this->setSessionAuth(array('au_user' => $this->data['au_user'],'au_hash' => "$checkhash"));
    //     else            $this->unsetSessionAuth();

        // so this should only happen if we have a POST submission
        if( array_key_exists('au_user',$this->data)){
            $entries = $this->db->getKeyedHash('au_id',"SELECT * FROM auth WHERE au_user = ?;",array($this->data['au_user']));
            if( count($entries) > 0){
                print_pre($entries,"auth entries");
                $pwe = array_shift($entries);
                print_pre($pwe,"pwe auth entry");
                //$checkhash = crypt($uepw,$pwe['au_hash']);
                // print_pre($checkhash,"checkhash");
                // print_pre($pwe['au_hash'],"dbhash");
                if( password_verify($uepw,$pwe['au_hash']) ){
                    $valid = TRUE;
                    //$this->setSessionAuth(array('au_user' => $this->data['au_user'],'au_hash' => "$checkhash"));
                }
                else {
                    $valid = FALSE;
                }
                //$valid = $this->setSessionAuth($pwe);
                // session_write_close();   // we may need to do this as the script may be ending prematurely by header call.
                // header("Location: " . "/Login.php");
                //$valid = TRUE;
            }
            else $valid = FALSE;

            if( $valid ) $this->setSessionAuth($pwe);
            else         $this->unsetSessionAuth();
        }
        else   $this->unsetSessionAuth();




        // so this should only happen if we have a POST submission
        // if( array_key_exists('au_user',$this->data) && array_key_exists('au_md5',$this->data)){
        //     $entries = $this->db->getKeyedHash('au_id',"SELECT * FROM auth WHERE au_user = ? AND au_md5 = ?;",array($this->data['au_user'],$this->data['au_md5']));
        //     if( count($entries) > 0){
        //         //print_pre($entries,"auth entries");
        //         $pwe = array_shift($entries);
        //         //print_pre($pwe,"auth entry");
        //         $valid = $this->setSessionAuth($pwe);
        //         // session_write_close();   // we may need to do this as the script may be ending prematurely by header call.
        //         // header("Location: " . "/Login.php");
        //         $valid = TRUE;
        //     }
        //     else $valid = FALSE;
        //
        //     if( $valid ) $this->setSessionAuth($pwe);
        //     else $this->unsetSessionAuth();
        // }
        // else   $this->unsetSessionAuth();
    }
    ///////////////////////////////////////////////////////////////////////////
    function loginForm(){
        $b = '';
        if ( $this->loggedIn() ){
            $b .= "<h3>Login Successful!  You may login as a different user if you want with the form below</h3>";
        }
        $b .= "<form action=\"Login.php\" method=\"POST\">\n";
        $b .= "<input type=\"text\" size=\"24\" name=\"au_user\" placeholder=\"Username\">\n";
        $b .= "</input>\n";
        $b .= "<input type=\"password\" size=\"24\" name=\"password\" placeholder=\"Password\">\n";
        $b .= "</input>\n";
        $b .= "<input type=\"submit\" size=\"24\" value=\"Login\" name=\"LOGIN_SUBMIT\" >\n";
        $b .= "</input>\n";
        $b .= "</form>\n";
        return $b;
    }
}
?>
