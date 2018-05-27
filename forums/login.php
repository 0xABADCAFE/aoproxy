<?php

///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//  Amiga.org classic browser proxy project
//
//  (c) Karl Churchill 2010
//
///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

require_once('../include.main.php');

class RefererCatcher implements MarkupRewriter {
  public function process($markupText) {
    return str_replace(
      array(
        //'www.google.com',
        'URL=http://amiga.org',
        'window.location = "http://amiga.org/'
      ),
      array(
        //Config::PROXY_URI,
        'URL=http://' . Config::PROXY_URI,
        'window.location = "http://' . Config::PROXY_URI
      ),
      $markupText
    );
  }
}

PageRequest::getInstance()
  ->addRewriter(new RefererCatcher())
  ->process();

// logged out? - destructify (bush) the session
if (isset($_GET['do']) && $_GET['do'] == 'logout') {
  @session_destroy();
}
