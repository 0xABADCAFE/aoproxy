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
//  LinksNavigationRewriter - declutters the post status section
//
///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

class LinksNavigationRewriter implements MarkupRewriter {

  private $subNav;

  public function process($content) {
    $this->subNav = array();

//     return preg_replace(
//       '@href\s*=\s*"/gallery/images/@',
//       'href="http://www.amiga.org/gallery/images/',
//       $content
//     );

    // this just reworks for the main navigation

/*
  <a href="misc.php?do=mystuff">My Stuff</a></span> <script type="text/javascript"> vbmenu_register("mystuff"); </script>

  <font size="-1"><a href="misc.php?do=mystuff">My Stuff</a></font>
  <script type="text/javascript"> vbmenu_register("mystuff"); </script>
*/

    $content = preg_replace_callback(
      '@<a\s+href="(.*?)">(.*?)</a></.*?>\s+<script\s+type="text/javascript">\s+vbmenu_register\("(.*?)"\);\s+</script>@',
      array(&$this, 'rewriteNavigation'),
      $content,
      5
    );

    // process the submenus we've started a <select> list for
    $numSubNav = count($this->subNav);
    if ($numSubNav > 0) {
      $content = preg_replace_callback(
        '@<div\s+class\s*=\s*"vbmenu_popup"\s+id="(' . implode('|', array_keys($this->subNav)) . ')_menu".*?>\s*<table .*?</table>\s*</div>@s',
        array(&$this, 'extractSubMenus'),
        $content,
        $numSubNav
      );
    }


    // remove the rest - to do - attach as option lists
    $content = preg_replace(
      array(
      '@<div\s+class="vbmenu_popup"\s+id="(linkbit|ratemenu)_\d+_[a-z_]*menu".*?>\s*<table .*?</table>\s*</div>@s',
      ),
      array(
        "<!-- sub menu was here -->",
      ),
      $content
    );

    // bind the extracted sub menus to their parent select list
    foreach($this->subNav as $key => $menuItems) {
      $content = preg_replace(
        '@<!-- MENU_' . $key .  ' -->@',
        $menuItems,
        $content,
        1
      );
    }

    return $content;
  }

  private function rewriteNavigation($matches) {

/*
    [0] => <a href="browselinks.php?do=newlinks">What's New</a></span> <script type="text/javascript"> vbmenu_register("whatsnew"); </script>
    [1] => browselinks.php?do=newlinks
    [2] => What's New
    [3] => whatsnew
*/

    $this->subNav[$matches[3]] = '<!-- -->';

    return sprintf(
      "\n<select name=\"%s\" onchange=\"window.location=value;\">\n<option selected value=\"\">%s</option>\n<!-- MENU_%s --></select></span>\n",
      $matches[3],
      $matches[2],
      $matches[3]
    );
  }

  private function extractSubMenus($matches) {
    $navBlock = $matches[0];
    $subMenu  = $matches[1];

    // extract all links
    $matches = array();
    preg_match_all(
      '@<a .*?href="(.*?)".*?>(.*?)</a>@',
      $navBlock,
      $matches
    );

    foreach($matches[1] as $index => $href) {
      if ($href[0] != '#') {
        // base redirection doesn't seem to work in ibrowse
        if ($href[0] != '/') {
          $href = '/links/'.$href;
        }

        $matches[0][$index] = sprintf(
          "<option value=\"%s\">%s</option>\n",
          $href,
          $matches[2][$index]
        );
      } else {
        $matches[0][$index] = '';
      }
    }
    $this->subNav[$subMenu] = implode('', $matches[0]);
    return '';
  }

}

class LinksCategoryImageRewriter implements MarkupRewriter {

  public function process($content) {
    return str_replace(
      array("forums/images/caticon/", "forums/images/heart.gif"),
      array("links/images/caticon/", "links/images/heart.gif"),
      $content
    );
  }
};


