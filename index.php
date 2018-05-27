<?php

///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//  Amiga.org classic browser proxy project
//
//  (c) Karl Churchill 2010
//
///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

require_once('include.main.php');

///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//  FrontPageModulesRewriter - refactors specific items on the front page.
//
///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

class FrontPageModulesRewriter implements MarkupRewriter {

  public function process($markupText) {
    $markupText = preg_replace(
      array(
        // removes the Advanced stats from the front page which simply won't work in ibrowse.
        // Alas, this isn't the easiest block to remove as it has no outer container
        '@<tbody\s+id="collapseobj_cyb_fh_stats".*?>.*?</tbody>@s',
        '@<thead>[\n\s]*<tr valign="top">[\n\s]*<td .*?>Top 5 Stats <.*?[\n\s]*</td>[\n\s]*</tr>[\n\s]*</thead>@s',
        '@<script\s+language\s*=\s*"JavaScript"\s+type\s*=\s*"text/javascript">[\n\s]*<!--[\n\s]*function Cas_.*?</script>@s',
        // tag cloud
        //'@<(a\s+href="/forums/tags.php.*?")\s+class="tagcloudlink level(\d)">(.*?)</a>@',
        // social groups
        '@(<div\s+class="smallfont">)\x95@'
      ),
      array(
        // Advanced stats
        '',
        '',
        '',
        // tag cloud
        //'<${1}><font size="+${2}">${3}</font></a>',
        // social groups
        '${1}'
      ),
      $markupText
    );
    return preg_replace_callback(
      '@<(a\s+href="/forums/tags.php.*?")\s+class="tagcloudlink level(\d)">(.*?)</a>@',
      array(&$this, 'rewriteTagSize'),
      $markupText
    );
  }


  private function rewriteTagSize($matches) {
    $size = intval($matches[2]) - 2;
    if ($size > 0) {
      $size = "+" . $size;
    }
    return sprintf('<%s><font size="%s">%s</font></a>', $matches[1], $size, $matches[3]);
  }
}

PageRequest::getInstance()
  ->addRewriter( new FrontPageModulesRewriter() )
  ->process();


