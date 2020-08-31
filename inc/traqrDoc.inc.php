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
     }

     function menu(){
         $m = new menu('nav','navlink');
         $m->addMenu('index','Home','/Index.php');
         $m->addItem('index','About','/About/Index.php');
         if ( authorized('TRAQR','admin')){
             $m->addMenu('admin','Admin','/Admin/Index.php');
             $m->addItem('admin','Gen New QRs','/Admin/GenQR.php');
             $m->addSep('admin');
             $m->addItem('admin','Auth Table','/Admin/authMgmt.php');
             $m->addItem('admin','QR Table','/Admin/qrGenMgmt.php');
             $m->addItem('admin','ID Table','/Admin/IdentifierMgmt.php');
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
             $m->addItem('util','Entry Completed','/EntryCompleted.php');
             $m->addItem('util','Auth Testing','/Util/authTesting.php');
         }

         $lm = new menu('nav','navlink');
         if( isset($_SESSION['au_user'])){
             $lm->addMenu('login','User: ' . $_SESSION['au_user'] . ' (' . $_SESSION['au_role'] . ')','');
             $lm->addItem('login','Sign Out','/Logout.php');
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
     function navHTML(){
         $b = '';
         $b .= "<header>\n";
         if ( $this->heading != '') $b .= "<h1>$this->heading</h1>\n";
         $b .= "</header>\n";
         $b .= $this->menu();
         $b .= "<hr>\n";
         return $b;
     }
     ///////////////////////////////////////////////////////////////////////////
     // This was a test of the dropdown menu stuff snagged online before implementing
     // my menu class above.  Kinda junk, but will do for the time being.
     ///////////////////////////////////////////////////////////////////////////
     function whatevs(){
         $b = '  <ul class="nav">
   <li class="dropdown nav">
     <a class="navlink" href="/Index.php" class="dropbtn">Home</a>
     <div class="dropdown-content">
       <a class="navlink" href="#">About</a><br>
     </div>
   </li>
   <li class="dropdown nav">
     <a class="navlink" href="javascript:void(0)" class="dropbtn">Util/Dev/Tools</a>
     <div class="dropdown-content">
       <a class="navlink" href="#">PHPinfo</a><br>
       <a class="navlink" href="#">DB Schema</a><br>
       <a class="navlink" href="#">Report All</a><br>
     </div>
   </li>
   <li class="dropdown nav">
     <a class="navlink" href="javascript:void(0)" class="dropbtn">Admin</a>
     <div class="dropdown-content">
       <a class="navlink" href="#">Report Daily</a><br>
       <a class="navlink" href="#">Report Daily (by ident)</a><br>
       <a class="navlink" href="#">Report All</a><br>
     </div>
   </li>
 </ul>';
         return $b;

     }
     function contentIndex(){
         $b = '';
         $b .= "
         <p>
         This site is a quick proof of concept for generating custom QR codes for Individuals specific to Building and Room.
         <br>
         The idea is to collect the data in a form that is easier to harvest than what we have seen so far in other methods.
         </p>
         <p>
         The Generate Codes link below allows the user to specify up to 7 combinations of Building/Room to produce both INGRESS and EGRESS QR codes for.
         <br>
         Use the nav menu at the top of the page to navigate to the various options and operations.
         </p>
         <p>
         Administrators (checked via IP) will see additional options in the navmenu and be able to look at the logged data.
         </p>
         <p>
         The URL in the QR code is a form submission script that is also part of this site.
         <br>
         When the QR code is scanned and the resulting site visited the form creates a sqlite3 db entry from that information with appropriate timestamps.
         </p>
         ";

         $todo = "
         <p>
         <strong>To Do</strong>
         <ul>
         <li>General
           <ul>
             <li>Authenticate to SSO?</li>
           </ul>
         </li>
         <li>QR code generation
           <ul>
           <li>Input checking on room number(numerical with optionally one trailing alpha)</li>
           <li class=\"done\">Switch to UCSBNetID based (aaron_martin@ucsb.edu)</li>
           </ul>
         </li>
         <li>Data Scanning
           <ul>
             <li class=\"done\">Improve Display (you have been logged)</li>
             <li>Check for existence of user to flash NOT approved (?)</li>
           </ul>
         </li>
         <li>Data Display
           <ul>
             <li>Sortable</li>
             <li class=\"done\">Some data checking (Egress with no ingress, ingress with no egress, who is in the building)</li>
           </ul>
         </li>
         <li>Data Management
           <ul>
             <li>Table with Mapping from UCSBNetID to FirstName, LastName, PhoneNumber (?)</li>
             <li class=\"done\">Some data checking (Egress with no ingress, ingress with no egress, who is in the building)</li>
           </ul>
         </li>
         </ul>
         </p>
         ";

         if (authorized()){
             $b .= "$todo";
         }

         return $b;
     }
}
?>
