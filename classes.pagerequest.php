<?php

///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//  Amiga.org classic browser proxy project
//
//  (c) Karl Churchill 2010
//
///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////


///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//  PageRequest - Main page request processor. Singleton class, manages a Curl instance and a list of MarkupRewiters
//
///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

interface HeaderProcessor {
  public function process($headerData);
};

class PageRequest {

  const SESSION_COOKIEDATA = 'MMM_COOKIES';

  private $noRewrite       = false;

  public static function getInstance() {
    if (!self::$instance) {
      self::$instance = new PageRequest();
    }
    return self::$instance;
  }

  public final function addRewriters($rewriters) {
    foreach($rewriters as $rewriter) {
      $this->addRewriter($rewriter); // fatal error if not a MarkupRewriter derivative
    }
    return $this;
  }

  public final function addRewriter(MarkupRewriter $rewriter) {
    $this->rewriters[] = $rewriter;
    return $this;
  }

  public final function addHeaderProcessor(HeaderProcessor $processor) {
    $this->headerProcessors[] = $processor;
    return $this;
  }

  public final function skipRewrite($mode) {
    $this->noRewrite = $mode;
    return $this;
  }

  public final function setBinary($mode) {
    $this->curl->setBinary($mode);
    return $this;
  }

  public final function process() {
    $timer       = new Timer();
    $targetURL   = preg_replace('@^/@', 'http://amiga.org/', $_SERVER['REQUEST_URI']);

    // set up a sensible HTTP_REFERER for cURL, as the forum uses it for redirect on login
    if (!isset($_SERVER['HTTP_REFERER'])) {
      $this->curl->setReferer('http://' . Config::PROXY_URI . '/index.php');
    } else {
      $this->curl->setReferer($_SERVER['HTTP_REFERER']);
    }

    // fetch the page and check if there were any redirects to handle.
    // cURL's own redirect handler fails miserably when php safe mode enabled or
    // base redirect is in effect.
    $this->curl->execute($targetURL);
    if (!($this->checkRedirects())) {
      foreach($this->headerProcessors as $processor) {
        $processor->process($this->curl->getHeaderData());
      }
      if ($this->noRewrite) {
        print($this->curl->getContent());
        ob_flush();
      }
      else {
        // rewrite and push out the content. Overall time includes data output
        $loadTime = $timer->lastInterval();
        printf("<!-- %s : retrieved in %0.3fs -->\n", $targetURL, $loadTime);
        print($this->rewriteContent());
        $processTime = $timer->lastInterval();
        printf("\n<!-- %s : processed in %0.3fs -->\n", $targetURL, $processTime);
        ob_flush();
      }
    }
    // last, but not least, save the cookie data from the forun into our session
    Session::setVar(self::SESSION_COOKIEDATA, $this->curl->getCookieData());
  }

  private function checkRedirects() {
    // step over the headers. We're really only interested in the last instance of a Location: redirect
    $redirect = null;
    foreach($this->curl->getHeaderData() as $header) {
      if (strncmp($header, 'Location:', 9)==0) {
        $redirect = $header;
      }
    }
    if ($redirect) {
      $redirect = preg_replace('@http://(www\.|)amiga\.org/@', '/', $redirect);
      header($redirect);
      return true;
    }
    return false;
  }

  private function rewriteContent() {
    // Step through our list of rewriters, passing the retrieved content through each in turn.
    // Output the time spent in each one, just in case we end up writing a lemon implementation.
    $content  = $this->curl->getContent();
    $timer    = new Timer();
    print ("<!--\n");
    foreach($this->rewriters as $writer) {
      $in     = strlen($content);
      $result = $writer->process($content);
      $out    = strlen($result);
      printf(
        "%s:Bytes in: %d, out: %d, took %0.3f s\n",
        get_class($writer),
        $in,
        $out,
        $timer->lastInterval()
      );

      // Shouldn't happen any more but during early testing, a few regex operations catastrophically
      // backtracked, which resulted in an empty string.
      if ($out>0) {
        $content = $result;
      } else {
        throw new PCREError('No data return from ' . get_class($writer) . '->process()');
      }
    }
    print (" -->\n");

    // One final pass that isn't worth using a rewriter for:
    return preg_replace(
      array(
        '@>\s+<@',
      ),
      array(
        ">\n<",
      ),
      $content
    );
 }

  private function __construct() {
    $this->curl = new Curl(true);

    //$this->curl->setUserAgent($_SERVER['HTTP_USER_AGENT']);

    if (count($_POST)>0) {
      foreach($_POST as $key => $value){
        if (!is_array($value)) {
          $_POST[$key] = stripslashes($value);
        }
      }
      $this->curl->setPostData($_POST);
    }

    if (($cookieData = Session::getVar(self::SESSION_COOKIEDATA, false))!==false) {
      $this->curl->setCookieData($cookieData);
    }
  }

  private static $instance  = null;
  private
    $curl             = null,
    $rewriters        = array(),
    $headerProcessors = array()
  ;
}
