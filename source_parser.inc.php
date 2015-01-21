<?php

// Include QueryPath.
require_once drupal_get_path('module', 'migrate_wild') . '/querypath-2.1.2/src/QueryPath/QueryPath.php';
require_once drupal_get_path('module', 'migrate_wild') . '/querypath-2.1.2/src/QueryPath/Extension/QPXML.php';

class SourceParser {

    protected $id;
    protected $html;
    protected $qp;

    /**
     * Constructor.
     *
     * @param $id
     *  The filename, e.g. pm7205.html
     * @param $html
     *  The full HTML data as loaded from the file.
     */
    public function __construct($id, $html,$migration) {
        $this->id = $id;
        $this->html = $html;
        $this->migration = $migration;

        $this->stripstuff();
        $this->charTransform();
        $this->fixEncoding();
        $this->wrapHTML();
        $this->rewriteSrc($id);
        $this->rewriteHref($id);
        $this->initQP();
        $this->stripComments();
        
    }

    protected function stripstuff() {

        $this->html = preg_replace('/<\!-- analytics.*\/script>/s', '', $this->html);
        //$this->html = preg_replace('/<div class="title">([^<>]*)<\/div>/s', '<h1 class="import">$1</h1>', $this->html);
        $this->html = preg_replace('/<span[^<>]*>/','<span>',$this->html);
        $this->html = preg_replace('/<span>/','',$this->html);
        $this->html = preg_replace('/<\/span>/','',$this->html);

        include_once('simple_html_dom.php');
        $html = $this->html;
        
        $x = str_get_html($html);
        
        foreach($x->find('div[class*="col-*"]') as $e) {
            $e->class = '';
        }
        foreach($x->find('style') as $e) {
            $e->outertext = '';
        }
        foreach($x->find('nav') as $e) {
            $e->outertext = '';
        }
        foreach($x->find('footer') as $e) {
            $e->outertext = '';
        }
        foreach($x->find('div[class*="page-break"]') as $e) {
            $e->outertext = '<hr/>';
        }
        foreach($x->find('div[class*="title"]') as $e) {
            $in = $e->innertext;
            if (trim($e->innertext) == ''){
                $e->outertext = null;
            } else {
                $e->outertext = '<h1 class="import">'.$in.' </h1>';
            }
        }
        
        foreach($x->find('div') as $e) {
            if(trim($e->innertext) == '') {
                $e->outertext = '';
            }
        }
        
        $this->html = $x;
    }
    
    public function poststrip() {
    }

    /**
     * Replace characters.
     */
    protected function charTransform() {
        // We need to strip the Windows CR characters, because otherwise we end up
        // with &#13; in the output.
        // http://technosophos.com/content/querypath-whats-13-end-every-line
        $this->html = str_replace(chr(13), '', $this->html);
    }

    /**
     * Deal with encodings.
     */
    protected function fixEncoding() {
        // If the content is not UTF8, we assume it's WINDOWS-1252. This fixes
        // bogus character issues. Technically it could be ISO-8859-1 but it's safe
        // to convert this way.
        // http://en.wikipedia.org/wiki/Windows-1252
        $enc = mb_detect_encoding($this->html, 'UTF-8', TRUE);
        if (!$enc) {
            $this->html = mb_convert_encoding($this->html, 'UTF-8', 'WINDOWS-1252');
        }
    }

    /**
     * Wrap an HTML fragment in the correct head/meta tags so that UTF-8 is
     * correctly detected, and for the parsers and tidiers.
     */
    protected function wrapHTML() {       
        // We add surrounding <html> and <head> tags.
    //    $html = '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">';
     //   $html .= '<html xmlns="http://www.w3.org/1999/xhtml"><head><meta http-equiv="Content-Type" content="text/html; charset=utf-8" /></head><body>';
        $html = $this->html;
      //  $html .= '</body></html>';
        // Specify configuration
        $config = array(
           'indent'         => false,
           'output-xhtml'   => true,
            'clean'         => true,
           'wrap'           => 400);

        // Tidy
        $tidy = new tidy;
        $tidy->parseString($html, $config, 'utf8');
        $tidy->cleanRepair();

        $this->html = $tidy;
    }

    protected function rewriteSrc($id) {
        //change the local links and files 
        $path_parts = pathinfo($id);
        $html = $this->html;
        $html = str_ireplace('src="http', 'src-protect="http', $html); // stop ext links being rewriten
        
        $html = str_ireplace('src="/media/', 'src="/sites/wild.local/files/media/', $html); // 
        $html = str_ireplace('src="/static/', 'src="/sites/wild.local/files/static/', $html); // 

        $html = str_replace('src-protect=', 'src=', $html); // rewrite protected srcs

        $this->html = $html;
    }

    protected function rewriteHref($id) {

    }

    /**
     * Create the QueryPath object.
     */
    protected function initQP() {
        $qp_options = array(
            'convert_to_encoding' => 'utf-8',
            'convert_from_encoding' => 'utf-8',
            'strip_low_ascii' => FALSE,
        );
        $this->qp = htmlqp($this->html, NULL, $qp_options);
    }

    /**
     * Remove the comments from the HTML.
     */
    protected function stripComments() {
//    foreach ($this->qp->top()->xpath('//comment()')->get() as $comment) {
//      $comment->parentNode->removeChild($comment);
//    }
    }
    
    /**
     * Return the HTML.
     */
    public function getBody() {
        $body = $this->qp->top('body')->innerHTML();
        $body = trim($body);       
        return $body;
    }

    public function getTitle($basedir, $file) {
        $title = "";
        // check h1 if non existent use .title.txt in same directory 
        $title = $this->qp->top('h1')->innerHTML();
        if ($title == "") {
            $titlefile = dirname($basedir . $file) . ".title.txt";
            if (file_exists($titlefile)) {
                $title = file_get_contents($titlefile);
            }
        }
        $title = trim(strip_tags($title)); //remove html crud
        if ($title == "") {
            $title = $this->qp->top('h2')->innerHTML();
        }
        $title = trim(strip_tags($title)); //remove html crud
        if ($title == "") {
            $wwwroot = str_replace('/local/httpd/sites/htdocs-maths', '', $basedir);
            $title = $file;
        }
        //echo "'".$title."'\n";
        


        return $title;
    }


}