<?php
////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////
class htmlDoc {
    function __construct($title){
        $this->rel = '';
        $this->title = $title;
        $this->options = array();
        $this->head = array();
        $this->setDefaultOptions();
        $this->cssFiles = array();

        // this is not a totally horrible setup, but would still like to
        // figure out something to sort this out
        if( defined('REL')) $this->rel = REL;
        // $this->rel = $this->findRelPath('css');
    }
    function setDefaultOptions(){
        $this->options['lang'] = 'en';
        $this->options['heading'] = 'USE_TITLE';
        $this->options['target'] = '';
        $this->options['nav'] = TRUE;
        $this->options['print'] = TRUE;
        $this->options['embed-styles'] = FALSE;

        $this->meta('charset','utf-8');
        if (defined('BASEURL')){
            $this->head[] = "<link rel=\"icon\" href=\"" . BASEURL . "/favicon.ico?vers=1\" />\n";
        }
    }
    function setOption($k,$v){
        $this->options[$k] = $v;
    }
    function relURL($path,$label,$class = ''){
        return "<a class=\"$class\" href=\"" .$this->rel . $path . "\">$label</a>";
    }
    function navItem($path,$label){
        //$this->rel = $this->findRelPath($path);
        //print "rel: {$this->rel}<br>\n";
        return $this->relURL($path,$label,'nav');
        //"<a class=\"nav\" href=\"" .$this->rel . $path . "\">$label</a>";
    }
    ////////////////////////////////////////////////////////////////////////////
    function htmlBeg($options = array()){
        // prep some stuff before we start building the html buffer
        // set possible heading from title
        $this->heading = ( $this->options['heading'] == "USE_TITLE" ) ? $this->title : $this->options['heading'];

        // deal with either embedding styles in html (ie: for email) or create the head link references
        $cssBuf = '';
        if( isset( $this->cssFiles) && is_array($this->cssFiles)){
            if( $this->options['embed-styles']){
                $cssBuf .= "<!-- new embed setup! -->\n<style>\n";
                foreach($this->cssFiles as $cssFile){
                    $css = $this->rel . $cssFile;
                    //$this->rel = $this->findRelPath($cssFile);
                    $cssBuf .= file_get_contents($css);
                }
                $cssBuf .= "</style>\n";
            }
            else {
                foreach($this->cssFiles as $cssFile){
                    $css = $this->rel . $cssFile;
                    //$this->rel = $this->findRelPath($cssFile);
                    $mtime = filemtime($css);
                    $this->head[] = '<link rel="stylesheet" type="text/css" href="' . $css .'?ds=' . $mtime . '">' . NL ;
                }
            }
        }

        $lang = (isset($this->options['lang']) && $this->options['lang'] != '') ? $this->options['lang'] : 'en';

        $b = '';
        $b .= "<!DOCTYPE html>\n";
        $b .= "<html lang=\"$lang\">\n" ;
        $b .= '<head>' . NL;
        $b .= '<meta charset="utf-8" />' . NL;
        $b .= "<title>$this->title</title>\n";
        $b .= "<!-- New Comment Added -->\n";


        if( isset( $this->head) && is_array($this->head)){
            foreach( $this->head as $v ){
                // should handle cases of other types of meta tags (http-equiv), but don't yet
                $b .= "$v\n";
            }
        }

        // handle all metatags
        // this is the old mode and not very complete, works for keywords
        if( isset( $this->metatags) && is_array($this->metatags)){
            foreach( $this->metatags as $k => $v ){
                // should handle cases of other types of meta tags (http-equiv), but don't yet
                $b .= "\n<meta name=\"$k\" content=\"$v\"></meta>\n";
            }
        }

        if( isset( $this->metatag) && is_array($this->metatag)){
            foreach($this->metatag as $metaarray){
                $buf .= "<meta ";
                foreach($metaarray as $k => $v){
                    $b .= "$k=\"$v\" ";

                }
                $b .= ">\n";
            }
        }
        $b .= $cssBuf;
        $b .= "</head>\n";
        $b .= "<body>\n";

        if ( $this->options['nav'] ){
            $b .= $this->navHTML();
        }

        if ( $this->options['print'] ) {  print  $b;  }
        else                           {  return $b;  }
    }
    // stub
    function navHTML(){

    }
    function htmlEnd($printBuffer = TRUE){
        $b = '';
        $b .= '<footer>' . NL;
        $b .= '</footer>' . NL;
        $b .= '</body>' . NL;
        $b .= '</html>' . NL;

        if ( $this->options['print'] ) { print  $b; }
        else                           { return $b; }
    }
    function css($file){
        $this->cssFiles[] = $file;
    }
    function js($file){
        //$this->head[] = "<script language='JavaScript' src='$file'></script>";
        $this->head[] = "<script src=\"" . REL . "$file\"></script>";
    }
    function head($line){
        $this->head[] = $line;
    }
    // this method should just wrap a meta tag around the provided argument, append to a buffer to add to html later
    function meta($type,$content){
        if ( ! isset($this->metatags[$type])) $this->metatags[$type] = "";
        $this->metatags[$type] .= $content;
    }
 }
 ?>
