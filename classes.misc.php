<?php

///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//  Amiga.org classic browser proxy project
//
//  (c) Karl Churchill 2010
//
///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

class Session implements SessionConfig {

  public static function getVar($name, $defaultValue) {
    return isset($_SESSION[$name]) ? $_SESSION[$name] : $defaultValue;
  }

  public static function setVar($name, $value) {
    $_SESSION[$name] = $value;
    $_SESSION['updateUserCookies'] = 1;
  }

  public static function init() {
    @session_start();
    if ( !isset($_SESSION[self::SESSION_HIDE_DISCLAIMER]) ) {
      if ( !isset($_COOKIE[self::SESSION_HIDE_DISCLAIMER])) {
        $_SESSION[self::SESSION_HIDE_DISCLAIMER] = 0;
        setcookie(self::SESSION_HIDE_DISCLAIMER, 0, time()+86400*365, '/', '.'.Config::PROXY_URI);
      }
      else {
        $_SESSION[self::SESSION_HIDE_DISCLAIMER] = (intval($_COOKIE[self::SESSION_HIDE_DISCLAIMER])!=0);
      }
    }

    if ( !isset($_SESSION[self::SESSION_USE_SMALL]) ) {
      if ( !isset($_COOKIE[self::SESSION_USE_SMALL])) {
        $_SESSION[self::SESSION_USE_SMALL] = 0;
        setcookie(self::SESSION_USE_SMALL, 0, time()+86400*365, '/', '.'.Config::PROXY_URI);
      }
      else {
        $_SESSION[self::SESSION_USE_SMALL] = (intval($_COOKIE[self::SESSION_USE_SMALL])!=0);
      }
    }

    if (isset($_SESSION['updateUserCookies'])) {
      setcookie(self::SESSION_HIDE_DISCLAIMER, $_SESSION[self::SESSION_HIDE_DISCLAIMER], time()+86400*365, '/', '.'.Config::PROXY_URI);
      setcookie(self::SESSION_USE_SMALL,       $_SESSION[self::SESSION_USE_SMALL], time()+86400*365, '/', '.'.Config::PROXY_URI);
      unset($_SESSION['updateUserCookies']);
    }
  }
}


///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//  Timer - simple start/stop/elapsed stopwatch for benchmarking
//
///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

class Timer {

  public function __construct() {
    $this->set();
  }

  public final function set() {
    $this->mark = microtime(true);
  }

  public final function elapsed() {
    return microtime(true) - $this->mark;
  }

  public final function lastInterval() {
    $mark       = microtime(true);
    $interval   = $mark - $this->mark;
    $this->mark = $mark;
    return $interval;
  }

  private $mark;
}
