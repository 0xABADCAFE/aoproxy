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
//  MarkupRewriter - top level interface for page content processing
//
///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

interface MarkupRewriter {
  public function process($content);
}

///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//  PCREError - when thrown, associates the error of the last pcre error with the standard Exception
//
///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

class PCREError extends Exception {

  public function __construct($message) {
    $message .= ': ';
    switch (preg_last_error()) {
      case PREG_NO_ERROR:
        $message .= "PREG_NO_ERROR (diagnostic not returned)\n";
        break;
      case PREG_INTERNAL_ERROR:
        $message .= "PREG_INTERNAL_ERROR\n";
        break;
      case PREG_BACKTRACK_LIMIT_ERROR:
        $message .= "PREG_BACKTRACK_LIMIT_ERROR\n";
        break;
      case PREG_RECURSION_LIMIT_ERROR:
        $message .= "PREG_RECURSION_LIMIT_ERROR\n";
        break;
      case PREG_BAD_UTF8_ERROR:
        $message .= "PREG_BAD_UTF8_ERROR\n";
        break;
      case PREG_BAD_UTF8_OFFSET_ERROR:
        $message .= "PREG_BAD_UTF8_OFFSET_ERROR\n";
        break;
    }
    parent::__construct($message);
  }
}

///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//  DegradeXHTMLRewriter - degrades XHTML features and fixes various HTML issues found in main page
//
///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

class DegradeXHTMLRewriter implements MarkupRewriter {

  public function process($content) {
    // Convert doctype from XHTML strict to HTML4 transitional
    // Convert <tag /> to <tag >
    // Remove XML namespace info from <html>
    // Alter page title to alert user
    // Fix various common markup issues

    $content = preg_replace(
      array(
        '@<!DOCTYPE.*?>@',
        '@<html .*?>@',
        '@<(meta|base|link|img|input|hr)\s*(.*?)/>@',
        '@<(br|hr)\s*/>@',
        '@</title>@',

        // broken stuff:
        '@(<hr.*?>)</a>@',  // Site navigation block is littered with these
        '@<(a|i)/>@',       // Fair few of these, should be </...>
        '@<(/*)b>@',

        // tidy
        //'@>\s+@',
        //'@\s+<@'
      ),
      array (
        Config::TARGET_DOCTYPE,
        '<html>',
        '<${1} ${2}>',
        '<${1}>',
        Config::TITLE_SUFFIX . '</title>',

        // broken stuff:
        '</a>${1}',
        '</${1}>',
        '<${1}strong>',
        //'> ',
        //' <'
      ),
      $content
    );

    // fix all images that have no alt tag
    $content = preg_replace_callback(
      '@<img\s+.*?>@',
      array(&$this, 'enforceImageAltText'),
      $content
    );

    return $content;
  }

  private function enforceImageAltText($matches) {
    $imageTag = $matches[0];
    if (!preg_match('@\s+alt\s*=@', $imageTag)) {
      $imageTag = substr($imageTag, 0, -1) . ' alt="">';
    }
    return $imageTag;
  }
}

///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//  JavascriptRewriter - remove or rework unsupportable script stuff
//
///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

class JavascriptRewriter implements MarkupRewriter {

  public function process($content) {
    $content = preg_replace(
      array(
        // yui
        // <script type="text/javascript" src="http://www.amiga.org/forums/clientscript/yui/yahoo-dom-event/yahoo-dom-event.js?v=384"></script>
        // <script type="text/javascript" src="http://www.amiga.org/forums/clientscript/yui/connection/connection-min.js?v=384"></script>
        '@<script\s+type="text/javascript"\s+src="http://www.amiga.org/forums/clientscript/yui/.*?js\?v=\d+"></script>@',

        // <!-- lightbox scripts -->
        // <script type="text/javascript" src="http://www.amiga.org/forums/clientscript/vbulletin_lightbox.js?v=384"></script>
        '@<script\s+type="text/javascript"\s+src="http://www.amiga.org/forums/clientscript/vbulletin_lightbox.js\?v=\d+"></script>@',
        '@<!--\s+lightbox\s+scripts\s+-->.*?<!--\s+/\s+lightbox\s+scripts\s+-->@s',

        // vbam
        '@<script\s+type="text/javascript"\s+src="http://www.amiga.org/forums/clientscript/vbam.js\?v=\d+"></script>@',
      ),
      array(
        '<!-- no YUI -->',
        '<!-- no lightbox -->',
        '<!-- no lightbox -->',
        '<!-- no vbam -->'
      ),
      $content
    );
    return $content;
  }
}

///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//  LinkRewriter - reworks selected references to amiga.org to local site and vice versa
//
///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////


class LinkRewriter implements MarkupRewriter {

  public function process($content) {
    // Convert all un/single quoted href/src/rel to double quoted
    // Re-point <base href=...> to proxy URL
    // Re-point fully qualified href/action from amiga.org to proxy
    // Re-point local href for image, script etc to point back to original site

    return preg_replace(
      array(
        '@(href|rel|src)\s*=\s*[\'"]*([^\'">]+)[\'"]*@',
        '@<base\s+href\s*=\s*"http://(www\.|)amiga\.org/@',
        '@(href|action)\s*=\s*"http://(www\.|)amiga\.org/@',
        '@src\s*=\s*"/*(web|images|clientscript|image.php)@',
        '@src\s*=\s*"/@'
      ),
      array(
        '${1}="${2}"',
        '<base href="http://' . Config::PROXY_URI . '/',
        '${1}="/',
        'src="http://www.amiga.org/forums/${1}',
        'src="http://www.amiga.org/'
      ),
      $content
    );
  }
}

///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//  NavRewriter - rewrites main navigation block for access in old browser
//
///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

class MainNavigationRewriter implements MarkupRewriter, SessionConfig {

  private $subNav;
  private $notifications;
  private $notificationMenu = false;

  public function __construct() {
    if (isset($_GET[self::SESSION_HIDE_DISCLAIMER])) {
      $state = intval($_GET[self::SESSION_HIDE_DISCLAIMER]) ? 1 : 0;
      Session::setVar(self::SESSION_HIDE_DISCLAIMER, $state);
    }
    if (isset($_GET[self::SESSION_USE_SMALL])) {
      $state = intval($_GET[self::SESSION_USE_SMALL]) ? 1 : 0;
      Session::setVar(self::SESSION_USE_SMALL, $state);
    }

  }

  public function process($content) {
    $this->subNav       = array();
    $this->notifcations = array();

    // pull out any notifications
    $content = preg_replace_callback(
      '@<div\s+class="vbmenu_popup"\s+id="notifications_menu".*?>.*?</div>@s',
      array(&$this, 'extractNotifications'),
      $content
    );

    // rework the login box (if needed)
    $content = preg_replace_callback(
      '@<div\s+id="forms">.*?</div>@s',
      array(&$this, 'rewriteLoginBox'),
      $content,
      1
    );

    // rework the user box - from div to closing td
    $content = preg_replace_callback(
      '@<div\s+id="userbox">.*?</td>@s',
      array(&$this, 'rewriteUserBox'),
      $content,
      1
    );

    // this just reworks for the main navigation
    $content = preg_replace_callback(
      '@<div\s+id="nav">.*?</div>@s',
      array(&$this, 'rewriteMainNavigation'),
      $content,
      1
    );

    // process the submenus we've started a <select> list for
    $numSubNav = count($this->subNav);
    if ($numSubNav>0) {
      $content = preg_replace_callback(
        '@<div\s+class\s*=\s*"vbmenu_popup"\s+id="(' . implode('|', array_keys($this->subNav)) . ')_menu".*?>\s*<table .*?</table>\s*</div>@s',
        array(&$this, 'extractSubMenus'),
        $content,
        $numSubNav
      );
    }

    // remove the rest
    $content = preg_replace(
      array(
      '@<div\s+class="vbmenu_popup"\s+id="(vbbloglinks|community|navbar_search|usercptools|pagenav)_menu".*?>\s*<table .*?</table>\s*</div>@s',
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

    if (Session::getVar(self::SESSION_USE_SMALL, 0)) {
      $content = preg_replace(
        array(
          '@<img\s+src="http://www\.amiga\.org/forums/images/headers/header_ao_logo\.jpg"\s+.*?>@',
          '@background=".*?/forums/images/headers/header_ao_bg\.jpg"@'
        ),
        array(
          '<img src="/res/sm_header_ao_logo.jpg" border="0" alt="amiga.org" width="252" height="56">',
          'background="http://' . Config::PROXY_URI . '/res/sm_header_ao_bg.gif"'
        ),
        $content
      );
    }

    return $content;
  }

  private function rewriteLoginBox($matches) {
    $block = str_replace(
      array(
        '</form>',
        '<input type="checkbox"',
        '</div>'
      ),
      array(
        '',
        '<br><input type="checkbox"',
        '</form></div>'
      ),
      $matches[0]
    );

/*
  <div id="forms">
    <form action="login.php?do=login" method="post" onSubmit="md5hash(vb_login_password, vb_login_md5password, vb_login_md5password_utf, 0)">
    <script type="text/javascript" src="http://www.amiga.org/forums/clientscript/vbulletin_md5.js?v=384"></script>
    <input name="vb_login_username" id="navbar_username" accesskey="u" tabindex="101" value="User Name" onFocus="if (this.value == 'User Name') this.value = '';" type="text" class="form" >
    <input name="vb_login_password" id="navbar_password" tabindex="102" type="password" class="form" >
    <input name="LOGIN" type="submit" value="LOGIN" >
    <input type="checkbox" name="cookieuser" value="1" tabindex="103" id="cb_cookieuser_navbar" accesskey="c" >
    <input type="hidden" name="s" value="" >
    <input type="hidden" name="securitytoken" value="guest" >
    <input type="hidden" name="do" value="login" >
    <input type="hidden" name="vb_login_md5password" >
    <input type="hidden" name="vb_login_md5password_utf" >
    </form>
    <a href="register.php" rel="nofollow">Register</a> or have you <a href="login.php?do=lostpw">forgotten your password</a>?
  </div>
*/
    $block = preg_replace(
      array(
        '@<a(.*?)>(.*?)</a>@',
        '@</a>(.*?)\s*<@'
      ),
      array(
        '<a${1}><font size="-1" color="#ffffff"><b>${2}</b></font></a> ',
        '</a><font size="-1" color="#ffffff">${1}</font> <'
      ),
      $block
    );

    return $block;
  }

  private function rewriteUserBox($matches) {
    $navBlock = preg_replace(
      array(
        '@\s+@',
        '@<!--.*?-->@',
        '@(<div.*?>)@',
        '@(<a\s+.*?>)@',

        '@(</(div|a)>)@',
        '@\s+<div>\s+@' // rogue unmatched div exists in the user box, replace it with a break
      ),
      array(
        ' ',
        '',
        '${1}<font size="-1" color="#ffffff">',
        '${1}<font color="#ffffff">',
        '</font>${1}',
        '<br>'
      ),
      $matches[0]
    );

    if (count($this->notifications) > 0) {
      $notificationMenu = sprintf(
        "\n<select name=\"notifications\" onchange=\"window.location=value;\">\n<option selected value=\"\">Your Notifications</option>\n%s</select>\n",
        implode("\n", $this->notifications)
      );

      if (Session::getVar(self::SESSION_USE_SMALL, 0)) {
        $navBlock = preg_replace(
          '@<span\s+id="notifications".*?</span>@s',
          '',
          $navBlock
        );
        $this->notificationMenu = $notificationMenu;
      }
      else {
        $navBlock = preg_replace(
          '@<span\s+id="notifications".*?</span>@s',
          $notificationMenu,
          $navBlock
        );
      }
    }

    return $navBlock;
  }

  private function rewriteMainNavigation($matches) {
    // first, tidy up the main navigation div to make processing less awkward
    $navBlock = preg_replace(
      array(
        '@\s+@',
        '@<!--.*?-->@',
        '@</font>\s*</li>@' // original menu has an unclosed <a> tag
      ),
      array(
        ' ',
        '',
        '</font></a>'
      ),
      $matches[0]
    );

    // extract all links
    $matches = array();
    preg_match_all(
      '@<a .*?href="(.*?)".*?>(.*?)</a>@',
      $navBlock,
      $matches
    );

    if (Session::getVar(self::SESSION_USE_SMALL, 0)) {
      $normal = array();
      $select = array();

      if ($this->notificationMenu) {
        $select[] = $this->notificationMenu;
      }

      // rebuild links, transforming those that have a popup into a select
      foreach($matches[1] as $index => $href) {
        if (strpos($href, 'nojs=')!==false) {
          $subMenu = substr($href, strpos($href, '#')+1);
          $select[] = sprintf(
            "\n<select name=\"%s\" onchange=\"window.location=value;\">\n<option selected value=\"\">%s</option>\n<!-- MENU_%s --></select>\n",
            $subMenu,
            $matches[2][$index],
            $subMenu
          );
          $this->subNav[$subMenu] = '';
        } else {
          $normal[] = sprintf(
            "<a href=\"%s\"><font color=\"#ffffff\" size=\"-1\" face=\"helvetica\"><b>%s</b></font></a>",
            $href,
            $matches[2][$index]
          );
        }
      }

      $result = '<div id="nav"><form name="submenu_wrapper" method="POST" action="" onsubmit="return false;">
<table border="0" cellspacing="0" width="100%" background="http://www.amiga.org/forums/web/misc/nav.gif">
<tr>
<td height="30" align="center">' . implode("</td>\n<td nowrap align=\"center\">", $normal) . '</td>
</tr>
</table>
<table border="0" cellspacing="0" width="100%" background="http://www.amiga.org/forums/web/misc/nav.gif">
<tr>
<td height="30"></td><td width="100" align="right">' . implode("</td>\n<td nowrap width=\"100\" align=\"right\">", $select) . '</td>
</tr>
</table>

</form>
</div>';

    }
    else {

      // rebuild links, transforming those that have a popup into a select
      foreach($matches[1] as $index => $href) {
        if (strpos($href, 'nojs=')!==false) {
          $subMenu = substr($href, strpos($href, '#')+1);
          $matches[0][$index] = sprintf(
            "\n<select name=\"%s\" onchange=\"window.location=value;\">\n<option selected value=\"\">%s</option>\n<!-- MENU_%s --></select>\n",
            $subMenu,
            $matches[2][$index],
            $subMenu
          );
          $this->subNav[$subMenu] = '';
        } else {
          $matches[0][$index] = sprintf(
            "<a href=\"%s\"><font color=\"#ffffff\" size=\"-1\" face=\"helvetica\"><b>%s</b></font></a>",
            $href,
            $matches[2][$index]
          );
        }
      }

      $result = '<div id="nav"><form name="submenu_wrapper" method="POST" action="" onsubmit="return false;">
<table border="0" cellspacing="0" width="100%" background="http://www.amiga.org/forums/web/misc/nav.gif">
<tr>
<td height="30" align="center">' . implode("</td>\n<td nowrap align=\"center\">", $matches[0]) . '</td>
</tr>
</table></form>
</div>';

    }

    $state = Session::getVar(self::SESSION_USE_SMALL, 0);
    $query = preg_replace('@&*' . self::SESSION_USE_SMALL . '=\d+@', '', $_SERVER['QUERY_STRING']);
    if ($query=="") {
      $query = sprintf('?%s=%d', self::SESSION_USE_SMALL, 1-$state);
    }
    else {
      $query = sprintf('?%s&amp;%s=%d', $query, self::SESSION_USE_SMALL, 1-$state);
    }

    if ($state) {
      $result .= '<a href="' . $query . '"><font size="-1" face="helvetica"><b>Bigger screen?</b></font></a>';
    }
    else {
      $result .= '<a href="' . $query . '"><font size="-1" face="helvetica"><b>640 wide screen?</b></font></a>';
    }

    $state = Session::getVar(self::SESSION_HIDE_DISCLAIMER, 0);
    $query = preg_replace('@&*' . self::SESSION_HIDE_DISCLAIMER . '=\d+@', '', $_SERVER['QUERY_STRING']);
    if ($query=="") {
      $query = sprintf('?%s=%d', self::SESSION_HIDE_DISCLAIMER, 1-$state);
    }
    else {
      $query = sprintf('?%s&amp;%s=%d', $query, self::SESSION_HIDE_DISCLAIMER, 1-$state);
    }

    if ($state) {
      $result .= ' | <a href="' . $query . '"><font size="-1" face="helvetica"><b>Important info / Disclaimer</b></font></a>';
    }
    else {
      $result .= '<div><font size="-1" face="helvetica">' . Config::PROXY_DISCLAIMER . ' <a href="' . $query . '">(ok, hide this stuff)<a></font></div>';
    }
    return $result;
  }

  private function extractSubMenus($matches) {
    $subMenu  = $matches[1];
    $navBlock = preg_replace(
      array(
        '@\s+@',
        '@<!--.*?-->@',
      ),
      array(
        ' ',
        ''
      ),
      $matches[0]
    );
    // extract all links
    $matches = array();
    preg_match_all(
      '@<a .*?href="(.*?)".*?>(.*?)</a>@',
      $navBlock,
      $matches
    );

    foreach($matches[1] as $index => $href) {
      if ($href[0]!='#') {
        // base redirection doesn't seem to work in ibrowse
        if ($href[0]!='/') {
          $href = '/forums/'.$href;
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

  private function extractNotifications($matches) {
    // extract all links from notification block
    $navBlock = $matches[0];
    $matches  = array();
    preg_match_all(
      '@<a href="(.*?)".*?>(.*?)</a>@',
      $navBlock,
      $matches
    );

    for ($i=0; $i<count($matches[1]); $i+=2) {
      $href = $matches[1][$i];
      // base redirection doesn't seem to work in ibrowse
      if ($href[0]!='/') {
        $href = '/forums/'.$href;
      }

      $this->notifications[] = sprintf(
        '<option value="%s">%s (%d)</option>',
        $href,
        $matches[2][$i],
        $matches[2][$i+1]
      );
    }
    return '';
  }

}

///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//  CSSRewriter - removes CSS, replaces certain styles with old HTML equivalent (where possible)
//
///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////


class CSSRewriter implements MarkupRewriter {

  public function process($content) {
    return preg_replace(array_keys(self::$cssReplaceMents), array_values(self::$cssReplaceMents), $content);
  }

  private static $cssReplaceMents = array(

    '@<table\s+align="center"\s+class="page"\s+cellspacing="0"\s+cellpadding="0"\s+width="100%">@' => '<table align="center" class="page" cellspacing="0" cellpadding="8" width="100%">',
    '@<td\s+class="divider">\s*</td>@' => '<td width="16"></td>',

    '@<link\s+rel="stylesheet".*?>@' => '',

    '@<style.*?</style>@s'      => '',
    '@style=".*?"@'             => '',

    '@<span\s+class="smallfont">(.*?)</span>@' => '<font size="-1">${1}</font>',

    '@class=("|\')thead("|\')@' => 'bgcolor="#3C6495"',
    '@class=("|\')(tcat|vbmenu_control)("|\')@'  => 'background="http://www.amiga.org/forums/images/bluegradientbar.jpg"',

    '@class=("|\')alt1("|\')@'  => 'bgcolor="#f0f0f0"',
    '@class=("|\')alt2("|\')@'  => 'bgcolor="#ffffff"',
    '@class=("\').*?("\')@'     => '',

    '@background="/@'           => 'background="http://www.amiga.org/',

    '@name\s*=\s*""\s+type\s*=\s*"submit"\s+class="login"\s+value\s*=\s*""@' => 'name="LOGIN" type="submit" value="LOGIN"'
  );
}

///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//  CommonBlockRewriter - removes CSS, replaces certain styles with old HTML equivalent (where possible)
//
///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

class CommonBlockRewriter implements MarkupRewriter {

  public function process($content) {
    $content = preg_replace_callback(
      '@<td\s+(.{0,20}class="tcat".*?)>(.{0,400}?)</td>@s',
      array(&$this, 'rewriteTCat'),
      $content
    );

    $content = preg_replace_callback(
      '@<span class="smallfont">[\n\s]+(<strong>.*?</strong>)[\n\s]+</span>@s',
      array(&$this, 'rewriteBlockText'),
      $content
    );

    $content = preg_replace(
      array(
        '@<body(.*?)>@',
        '@(<strong>La[s]+test Threads</strong>)@',
        '@<td\s+(.*?class="(thead|vbmenu_control)"\s*.*?)>(.*?)</td>@',
      ),
      array(
        '<body ${1} bgcolor="#ffffff">',
        '<font color="#ffffff"><strong>Latest Threads</strong></font>',
        '<td ${1}><font color="#ffffff">${3}</font></td>'
      ),
      $content
    );

    return $content;
  }

  private function rewriteBlockText($matches) {
    $link = preg_replace(
      array(
        '@(<a\s+href\s*=\s*".*?">)@',
        '@</a>@'
      ),
      array(
        '${1}<font color="#ffffff">',
        '</font></a>'
      ),
      $matches[1]
    );

    return '<font size="-1" color="#ffffff">'.$link.'</font>';
  }

  private function rewriteTCat($matches) {
    //printf("<!-- tcat: \n%s\n-->\n", htmlentities(print_r($matches, 1)));

    return '<td ' . $matches[1] . '><font color="#ffffff"><b>' . preg_replace(
      '@<(a|div)\s+(.*?)>(.*?)</(a|div)>@',
      '<${1} ${2}><font color="#ffffff">${3}</font></${4}>',
      $matches[2]
    ) . '</b></font></td>';
  }
}

///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//  NoLoginRewriter - removes login form
//
///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

class NoLoginRewriter implements MarkupRewriter {

  public function process($content) {
    return $content;
  }
}

/*
<table width="100%" border="0" cellspacing="0" cellpadding="0">
<tr>
<td background="http://www.amiga.org/forums/images/headers/bar_bg.gif">&nbsp;</td>
<td background="http://www.amiga.org/forums/images/headers/bar_bg.gif">&nbsp;</td>
<td background="http://www.amiga.org/forums/images/headers/bar_bg.gif">&nbsp;</td>
</tr>
</table>
*/
