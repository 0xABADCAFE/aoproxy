<?php

///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//  Amiga.org classic browser proxy project
//
//  (c) Karl Churchill 2010
//
///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

// bounce anything that doesn't appear to be amigoid or linux/mac
if (!preg_match('@amiga|morphos|aros|linux|android@i', $_SERVER['HTTP_USER_AGENT'])) {
  header('Location: http://amiga.org/' . $_SERVER['REQUEST_URI']);
  die();
}

// enable output buffering and turn on gz compression module. Since we are only sending PHP via the proxy, this is
// sufficient, we don't need to consider apache's mod_compression

if (!ob_start("ob_gzhandler")) {
  ob_start();
}

error_reporting(E_ALL|E_STRICT);
//session_start();

require_once('config.php');
require_once('classes.misc.php');
require_once('classes.curl.php');
require_once('classes.markuprewriter.php');
require_once('classes.pagerequest.php');

Session::init();

// add the default rewriters

class HeadIFrameEliminator implements MarkupRewriter {
  public function process($content) {
    return preg_replace(
      array(
        '@^<iframe.*?>\s+</iframe>@',
        '@http://amiga.org/forums/search.php@'
      ),
      array(
        '',
        'http://aoproxy.extropia.co.uk/forums/search.php'
      ),
      $content
    );
  }
}

PageRequest::getInstance()->addRewriters(
  array(
    new HeadIFrameEliminator(),
    new DegradeXHTMLRewriter(),
    new LinkRewriter(),
    new MainNavigationRewriter(),
    new CommonBlockRewriter(),
    new CSSRewriter(),
    new JavascriptRewriter()
  )
);


