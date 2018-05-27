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
//  MessageEditorRewriter - declutters the post status section
//
///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

class MessageEditorRewriter implements MarkupRewriter {

/*
  <textarea name="message" id="vB_Editor_001_textarea" rows="10" cols="60" style="display:block; width:540px; height:250px" tabindex="1" dir="ltr"></textarea>
*/

  public function process($content) {
    return preg_replace_callback(
      '@<textarea\s+name="message".*?>.*?</textarea>@s',
      array(&$this, 'reworkTextArea'),
      $content
    );
  }

  private function reworkTextArea($matches) {
    return preg_replace(
      array(
        '@rows\s*=\s*"\d+"@',
        '@cols\s*=\s*"\d+"@'
      ),
      array(
        'rows="20"',
        'cols="70"'
      ),
      $matches[0]
    );
  }
}

///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//  PostMenuRewriter - transforms the post popup menu into a select list
//
///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

// class PostMenuRewriter implements MarkupRewriter {
//
//   public function process($content) {
//     $this->postMenu = array();
//     // replace the hidden <div> containing the post menu and get the options
//     $content = preg_replace_callback(
//       '@<!-- post ([0-9]+) popup menu -->.*?<!-- / post [0-9]+ popup menu -->@s',
//       array(&$this, 'reworkPostMenu'),
//       $content
//     );
//     // replace the inline <script> that binds the menu to the post and replace with options
//     $content = preg_replace_callback(
//       '@<script\s+type="text/javascript">\s+vbmenu_register\("postmenu_([0-9]+)",\s+true\);\s+</script>@',
//       array(&$this, 'insertMenu'),
//       $content
//     );
//     $this->postMenu = null;
//     return $content;
//   }
//
//   private function reworkPostMenu($matches) {
//     // called in pass 1: grab the post menu options and store them by post Id for pass 2
//     $postId = $matches[1];
//     $content=str_replace('rel="nofollow"','',$matches[0]);
//     $matches=array();
//     preg_match_all(
//       '@<a href="(.*?)"\s*>(.*?)</a>@',
//       $content,
//       $matches
//     );
//     $content = '<select onchange="if(this.value!=\'\'){window.location=this.value;}"><option value="" selected>-- choose action --</option>';
//     foreach($matches[1] as $n => $url) {
//       $content .= '<option value="' . $url . '">' . $matches[2][$n] . '</option>';
//     }
//     $content .= '</select>';
//     $this->postMenu[$postId] = $content;
//     return '';
//   }
//
//   private function insertMenu($matches) {
//     // called in pass 2: replace the redundant postmenu_XXX inline script with our select list menu
//     $postId = $matches[1];
//     return $this->postMenu[$postId];
//   }
//
//   private $postMenu = array();
// }
