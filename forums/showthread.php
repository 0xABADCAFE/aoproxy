<?php

///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//  Amiga.org classic browser proxy project
//
//  (c) Karl Churchill 2010
//
///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

require_once('../include.main.php');
require_once('../classes.markuprewriter.thread.php');

PageRequest::getInstance()
  ->addRewriters(
    array(
      new ThreadMenuRewriter(),
      new NoQuickReplyRewriter(),
      new PostStatusRewriter(),
      new PostMenuRewriter()
    )
  )
  ->process();
