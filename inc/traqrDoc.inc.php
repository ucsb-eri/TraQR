<?php
require_once(__DIR__ . '/htmlDoc.inc.php');
////////////////////////////////////////////////////////////////////////////////
class menuItem {
    function __construct($liclass,$aclass,$label,$link = 'javascript:void(0)'){
        $this->liclass = $liclass;
        $this->aclass = $aclass;
        $this->label = $label;
        $this->link = $link;
        $this->menuSubItems = array();
    }
    function addItem($label,$link = '#'){
        $this->menuSubItems[] = "<a class=\"{$this->aclass}\" href=\"$link\">$label</a><br>\n";
    }
    function addSep(){
        $this->menuSubItems[] = "<hr class=\"{$this->aclass}\">\n";
    }
    function html(){
        $b = '';
        $b .= '<li class="dropdown nav">' . "\n";
        $b .= "<a class=\"dropbtn $this->aclass\" href=\"$this->link\">$this->label</a>\n";
        $b .= "<div class=\"dropdown-content\">\n";
        foreach($this->menuSubItems as $msi){
            $b .= $msi;
        }
        $b .= "</div>\n";
        $b .= "</li>\n";
        return $b;
    }
    function preFlightChecks(){
        $b = '';
        if (! file_exists(REL . '/' . DB)){
            $b .= $this->alertBanner('failure','DB file does NOT exist, so site will not operate correctly.  See installation instructions.');
            //$b .= "NO DB File!<br>\n";
        }
        if (! is_writable(REL . '/var')){
            $b .= $this->alertBanner('failure','var not writable!  In a shell, as root: navigate to run directory and run "make perms"');
        }
        return "$b";
    }
}
////////////////////////////////////////////////////////////////////////////////
class menu {
    function __construct($liclass,$aclass){
        $this->liclass = $liclass;
        $this->aclass = $aclass;
        $this->menuItems = array();
    }
    function addMenu($key,$label,$link = 'javascript:void(0)'){
        $this->menuItems[$key] = new menuItem($this->liclass,$this->aclass,$label,$link);
    }
    function addItem($key,$label,$link = 'javascript:void(0)'){
        $this->menuItems[$key]->addItem($label,$link);
    }
function addSep($key /* ,$label,$link = 'javascript:void(0)' */){
        $this->menuItems[$key]->addSep();
    }
    function html(){
        $b = '<li class="dropdown nav">';
        foreach($this->menuItems as $mi){
            $b .= $mi->html();
        }
        $b .= ' </li>';
        return $b;
    }
}
////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////
class traqrDoc extends htmlDoc {
     function __construct($title){
         parent::__construct($title);

         // Add in our default css styles
         $this->css('css/traqr.css');
         $this->css('css/traqr-data.css');
         $this->css('css/nav.css');
         $this->js('js/scripts.js');
     }
     ///////////////////////////////////////////////////////////////////////////
     function version(){
         $b = "";
         $b = "<strong>Version: </strong>";
         $b .= $GLOBALS['version'];
         return "$b";
     }
     ///////////////////////////////////////////////////////////////////////////
     function menu(){
         $m = new menu('nav','navlink');
         $m->addMenu('index','Home','/Index.php');
         $m->addItem('index','About','/About/Index.php');
         $m->addItem('index','Credits','/About/Credits.php');
         if ( authorized('TRAQR','admin')){
             $m->addItem('index','Todo','/About/Todo.php');
             $m->addMenu('admin','Admin','/Admin/Index.php');
             $m->addItem('admin','Initial Identity','/Admin/InitialIdentityEntry.php');
             //$m->addItem('admin','Gen New QRs','/Admin/GenQR.php');
             $m->addSep('admin');
             $m->addItem('admin','Auth Table','/Admin/authMgmt.php');
             $m->addItem('admin','QR Table','/Admin/qrGenMgmt.php');
             $m->addItem('admin','ID Table','/Admin/IdentifierMgmt.php');
             $m->addItem('admin','Upload Import CSV','/Admin/UploadFile.php');
             $m->addItem('admin','Proc Uploaded CSV','/Admin/ProcessUploadedCSV.php');
             $m->addSep('admin');
             $m->addItem('admin','Report All','/Admin/ReportAll.php');
             $m->addItem('admin','Report Daily','/Admin/ReportDaily.php');
             $m->addItem('admin','Report Daily (ident)','/Admin/ReportDailyByIdent.php');
         }
         if ( authorized('TRAQR','dev')){
             $m->addMenu('util','Utils/Dev/Tools','/Util/Index.php');
             $m->addItem('util','PHP Info','/Util/phpinfo.php');
             $m->addItem('util','Session Info','/Util/sessionInfo.php');
             $m->addItem('util','DB Schema','/Util/dbSchema.php');
             $m->addItem('util','DB Backup','/Util/dbBackup.php');
             $m->addItem('util','Entry Completed','/Safety.php');
             $m->addItem('util','Auth Testing','/Util/authTesting.php');
         }
         $m->addMenu('help','Help','/Help/Index.php');
         $m->addItem('help','Quick Start','/About/QuickStart.php');

         $lm = new menu('nav','navlink');
         if( isset($_SESSION['au_user'])){
             $lm->addMenu('login','User: ' . $_SESSION['au_user'] . ' (' . $_SESSION['au_role'] . ')','');
             $lm->addItem('login','Sign Out','/Logout.php');
         }
         elseif(authorizedByTraqrInternal('user')){
             $lm->addMenu('login','IP: ' . $_SERVER['REMOTE_ADDR'] . ' (' . $_SESSION['au_role'] . ')','');
         }
         elseif (isset($_SESSION['au_role'])){
             // want to catch the case of an IP authorized
             $lm->addMenu('login','User: ' . 'IP-ACL' . ' (' . $_SESSION['au_role'] . ')','');

         }
         else {
             $lm->addMenu('login','Login','/Login.php');
         }
        // $linfo =  (isset($_SESSION['au_user'])) ? "Logged in as: " . $_SESSION['au_user'] : "<a href=\"/Login.php\">Login</a>";
    //     $linfo .=  (isset($_SERVER['REMOTE_USER'])) ? "RU: " . $_SERVER['REMOTE_USER'] : " (Login Link)";
         $l = '';
         $l .= "<div class=\"login-info\">";
         $l .= $lm->html();
         $l .= "</div>\n";
         //print "$l<br>\n";

         return "<nav>\n" . $m->html() . $l . "</nav>\n";
     }
     ///////////////////////////////////////////////////////////////////////////
     function navHTML(){
         $b = '';
         $b .= "<header>\n";
         if ( $this->heading != '') $b .= "<h1>$this->heading</h1>\n";
         $b .= "</header>\n";
         $b .= $this->menu();
         $b .= "<hr>\n";
         return $b;
     }
}
?>
