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
//  ThreadMenuRewriter
//
///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

class ThreadMenuRewriter implements MarkupRewriter {

  public function process($content) {
    $this->subMenus = array();

    $content = preg_replace_callback(
      '@<a\s+href="/forums/showthread.php.*?#goto_(.*?)".*?>(.*?)</a>\s+<script\s+type="text/javascript">.*?</script>@',
      array(&$this, 'rewriteThreadMenu'),
      $content
    );

    //print_r($this->subMenus);

    // Unfortunately, each thread popup menu is a bit different so we use separate callbacks for each
    if (isset($this->subMenus['displaymodes'])) {
      $content = preg_replace_callback(
        '@<!-- thread display mode menu -->.*?<!-- / thread display mode menu -->@s',
        array(&$this, 'extractDisplayModeMenu'),
        $content,
        1
      );
    }

    if (isset($this->subMenus['threadtools'])) {
      $content = preg_replace_callback(
        '@<!-- thread tools menu -->.*?<!-- / thread tools menu -->@s',
        array(&$this, 'extractThreadToolsMenu'),
        $content,
        1
      );
    }

    if (isset($this->subMenus['threadsearch'])) {
      $content = preg_replace_callback(
        '@<!-- thread search menu -->.*?<!-- / thread search menu -->@s',
        array(&$this, 'extractThreadSearchMenu'),
        $content,
        1
      );
    }

    if (isset($this->subMenus['threadrating'])) {
      $content = preg_replace_callback(
        '@<!-- thread rating menu -->.*?<!-- / thread rating menu -->@s',
        array(&$this, 'extractRateThreadMenu'),
        $content,
        1
      );
    }

    foreach($this->subMenus as $key => $menuItems) {
      $content = preg_replace(
        '@<!-- MENU_' . $key .  ' -->@',
        $menuItems,
        $content,
        1
      );
    }

    return $content;
  }

  private function rewriteThreadMenu($matches) {
    $this->subMenus[$matches[1]] = $matches[2];
    return sprintf('<!-- MENU_%s -->', $matches[1]);
  }

  private function extractDisplayModeMenu($matches) {
    $content = $matches[0];
    $matches    = array();
    $options    = array();

    // extract selected item
    preg_match_all(
      '@<td .*?>\s*<strong>(.*?)</strong>\s*</td>@',
      $content,
      $matches
    );

    if (isset($matches[1])) {
      $options[] = sprintf("<option disabled>%s</option>", $matches[1][0]);
    }

    $matches = array();

    // extract all links
    preg_match_all(
      '@<a .*?href="(.*?)".*?>(.*?)</a>@',
      $content,
      $matches
    );

    foreach($matches[1] as $index => $href) {
      $options[] = sprintf(
        '<option value="%s">%s</option>',
        $href,
        $matches[2][$index]
      );
    }

    $this->subMenus['displaymodes'] = sprintf('
<select onchange="if(this.value!=\'\'){window.location=this.value;}">
<option selected value="">%s</option>
%s
</select>',
      $this->subMenus['displaymodes'],
      implode("\n", $options)
    );
    return '';
  }

  private function extractThreadToolsMenu($matches) {
    $content = $matches[0];
    $matches    = array();
    $options    = array();

    // extract all links
    preg_match_all(
      '@<a .*?href="(.*?)".*?>(.*?)</a>@',
      $content,
      $matches
    );

    foreach($matches[1] as $index => $href) {
      $options[] = sprintf(
        '<option value="%s">%s</option>',
        $href,
        $matches[2][$index]
      );
    }

    $this->subMenus['threadtools'] = sprintf('
<select onchange="if(this.value!=\'\'){window.location=this.value;}">
<option selected value="">%s</option>
%s
</select>',
      $this->subMenus['threadtools'],
      implode("\n", $options)
    );

    return '';
  }

  private function extractRateThreadMenu($matches) {
    // already rated?
    if (strpos($this->subMenus['threadrating'], 'inlineimg')!==false) {
      return '';
    }

    $content = $matches[0];
    $options = array();
    $hidden  = array();

    // extract all labels
    $matches = array();
    preg_match_all(
      '@<input\s+type="radio".*?value="(\d*)"\s*>(.*?)</label>@',
      $content,
      $matches
    );

    foreach($matches[1] as $index => $href) {
      $options[] = sprintf(
        '<option value="%s">%s</option>',
        $href,
        $matches[2][$index]
      );
    }

    // extract all hidden shizzle
    $matches    = array();
    preg_match_all(
      '@<input\s+type="hidden"\s+name=".*?"\s+value=".*?"\s*>@',
      $content,
      $matches
    );
    $hidden = $matches[0];

    // extract form info
    $matches    = array();
    preg_match(
      '@<form\s+.*?id="(.*?)"\s*>@',
      $content,
      $matches
    );

    // We can't build the form "in place" as it ends up nested inside another form and fails miserably.
    // So leave it where it was and use a bit of JS to submit it.
    $this->subMenus['threadrating'] = sprintf(
      self::$threadRatingJSTemplate,
      $matches[1],
      $this->subMenus['threadrating'],
      implode("\n", $options)
    );

    $form = sprintf('%s%s<input type="hidden" name="vote" value="" id="ratethread_value"><input type="hidden" value="Vote Now"></form>',
      $matches[0],
      implode("\n", $hidden)
    );

    return $form;
  }


  private function extractThreadSearchMenu($matches) {
/*
  <!-- thread search menu -->
  <div class="vbmenu_popup" id="threadsearch_menu" style="display:none">
  <form action="search.php?do=process&amp;searchthreadid=51756" method="post">
    <table cellpadding="4" cellspacing="1" border="0">
    <tr>
      <td class="thead">Search this Thread<a name="goto_threadsearch"></a></td>
    </tr>
    <tr>
      <td class="vbmenu_option" title="nohilite">
        <input type="hidden" name="s" value="" />
        <input type="hidden" name="securitytoken" value="1267644952-68ccd1bca13c1f8234f1a00bf7499b2f2337612c" />
        <input type="hidden" name="do" value="process" />
        <input type="hidden" name="searchthreadid" value="51756" />
        <input type="text" class="bginput" name="query" size="25" /><!-- BEGIN TEMPLATE: gobutton -->
  <input type="submit" class="button" value="Go"  />
  <!-- END TEMPLATE: gobutton --><br />
      </td>
    </tr>
    <tr>
      <td class="vbmenu_option"><a href="search.php?searchthreadid=51756">Advanced Search</a></td>
    </tr>
    </table>
  </form>
  </div>
  <!-- / thread search menu -->
*/

    $content = $matches[0];

    // extract hidden input fields
    $matches    = array();
    preg_match_all(
      '@<input\s+type="hidden".*?>@',
      $content,
      $matches
    );
    $hidden = $matches[0];

    //print_r($hidden);

    // extract form info
    $matches    = array();
    preg_match(
      '@<form\s+(.*?)>@',
      $content,
      $matches
    );

    // We can't build the form "in place" as it ends up nested inside another form and fails miserably.
    // So leave it where it was and use a bit of JS to submit it.

    $form = sprintf('<form id="searchthread_form" %s>%s<input type="hidden" name="query" value="" id="searchthread_value"><input type="hidden" value="Go"></form>',
      $matches[1],
      implode("\n", $hidden)
    );

    // We can't build the form "in place" as it ends up nested inside another form and fails miserably.
    // So leave it where it was and use a bit of JS to submit it.
    $this->subMenus['threadsearch'] = self::$threadSearchJSTemplate;

    return $form;
  }

  private $subMenus;

  // Inline JS to emit to support thread rating menu mechanism for ibrowse
  private static $threadRatingJSTemplate = '
<script type="text/javascript">
<!--
function ratethread() {
  var value = document.getElementById("ratethread_select").value;
  if (value=="") {
    window.alert("Choose a rating first!");
    return;
  }
  if (window.confirm("Are you sure you wish to rate this thread?")) {
    document.getElementById("ratethread_value").value=value;
    document.getElementById("%s").submit();
  }
}
// -->
</script>
<select id="ratethread_select">
<option selected value="">%s</option>
%s
</select>
<input type="button" onclick="ratethread()" value="Go">';

  private static $threadSearchJSTemplate = '
<script type="text/javascript">
<!--
function searchthread() {
  var value = document.getElementById("searchthread_text").value;
  if (value=="") {
    window.alert("No search term entered");
    return;
  }
  document.getElementById("searchthread_value").value=value;
  document.getElementById("searchthread_form").submit();
}
// -->
</script>
<input type="text" id="searchthread_text" size="12">
<input type="button" onclick="searchthread()" value="Find">';
}

///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//  NoQuickReplyRewriter - removes quick reply features
//
///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

class NoQuickReplyRewriter implements MarkupRewriter {
  public function process($content) {
    return preg_replace(
      array(
        '@<img\s+id="progress_[0-9]*" src="http://www.amiga.org/forums/web/misc/progress.gif" alt=""\s*>@',
        '@<a\s+href="(.*?)"\s+.*?><img\s+src="(.*?)"\s+alt="Quick reply to this message" border="0"\s*></a>@',
        '@<!-- quick reply -->.*?<!-- / quick reply -->@s',
      ),
      array(
        '',
        '',//'<a href="${1}" alt="Reply to this"><img src="${2}"></a>',
        '',
      ),
      $content
    );
  }
}

///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//  PostStatusRewriter - declutters the post status section
//
///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

class PostStatusRewriter implements MarkupRewriter {

  public function process($content) {
    return preg_replace_callback(
      '@<!-- status icon and date -->.*?<!-- / status icon and date -->@s',
      array(&$this, 'reworkStatus'),
      $content
    );
  }

  private function reworkStatus($matches) {
    return str_replace(
      array(
        '</a>',
        '<!-- /'
      ),
      array(
        '</a><font size="-1" color="#ffffff">',
        '</font><!-- /'
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

class PostMenuRewriter implements MarkupRewriter {

  public function process($content) {
    $this->postMenu = array();
    // replace the hidden <div> containing the post menu and get the options
    $content = preg_replace_callback(
      '@<!-- post ([0-9]+) popup menu -->.*?<!-- / post [0-9]+ popup menu -->@s',
      array(&$this, 'reworkPostMenu'),
      $content
    );
    // replace the inline <script> that binds the menu to the post and replace with options
    $content = preg_replace_callback(
      '@<script\s+type="text/javascript">\s+vbmenu_register\("postmenu_([0-9]+)",\s+true\);\s+</script>@',
      array(&$this, 'insertMenu'),
      $content
    );
    $this->postMenu = null;
    return $content;
  }

  private function reworkPostMenu($matches) {
    // called in pass 1: grab the post menu options and store them by post Id for pass 2
    $postId = $matches[1];
    $content=str_replace('rel="nofollow"','',$matches[0]);
    $matches=array();
    preg_match_all(
      '@<a href="(.*?)"\s*>(.*?)</a>@',
      $content,
      $matches
    );
    $content = '<br><select onchange="if(this.value!=\'\'){window.location=this.value;}"><option value="" selected>-- choose action --</option>';
    foreach($matches[1] as $n => $url) {

      $value = preg_replace(
        self::$shortenPostMenuSearch,
        self::$shortenPostMenuReplace,
        $matches[2][$n]
      );
      $content .= '<option value="' . $url . '">' . $value . '</option>';
    }
    $content .= '</select>';
    $this->postMenu[$postId] = $content;
    return '';
  }

  private function insertMenu($matches) {
    // called in pass 2: replace the redundant postmenu_XXX inline script with our select list menu
    $postId = $matches[1];
    return $this->postMenu[$postId];
  }

  private $postMenu = array();

  private static $shortenPostMenuSearch = array(
    '@Send a private message@'
  );

  private static $shortenPostMenuReplace = array(
    'Send PM'
  );
}
