<?php

///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//  Amiga.org classic browser proxy project
//
//  (c) Karl Churchill 2010
//
///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

require_once('../include.main.php');

class AttachmentHeaderProcessor implements HeaderProcessor {
  public function process($headerData) {
    foreach($headerData as $header) {
      if (strpos($header, 'Content')!==false) {
        header($header);
      }
    }
  }
};

if (isset($_GET['attachmentid'])) {
  PageRequest::getInstance()
    ->addHeaderProcessor(new AttachmentHeaderProcessor())
    ->setBinary(true)
    ->skipRewrite(true);
}

PageRequest::getInstance()->process();
