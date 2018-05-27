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
//  Configuration stuff
//
///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

interface Config {

  const
    PROXY_URI        = 'aoproxy.extropia.co.uk',
    PROXY_DISCLAIMER = '<strong>Important:</strong> This is a CSS-stripped, markup re-factored version of <a href="http://www.amiga.org">Amiga.org</a> intended to better support old browsers running on classic Amiga systems. If you are using a modern browser, please visit the original site. Many features are not supportable on older browsers and are removed.<br>
<strong>Disclaimer:</strong> This site is not endorsed by Amiga.org or it\'s owners. This site is a custom proxy that sits between your computer and Amiga.org. Other than a temporary session cookie, no data is stored here. By logging in through this site, you consent to allowing your username and password hash to be submitted through this proxy. This site is still under development and will have bugs galore. No warranty of any kind is provided or implied. Use at your own risk (or upgrade your browser...)',
    TARGET_DOCTYPE = '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">',
    TITLE_SUFFIX   = ' [Old Browser Edition]'
  ;
}

interface SessionConfig {
  const
    SESSION_HIDE_DISCLAIMER = 'shd',
    SESSION_USE_SMALL       = 'sus'
  ;
}
