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
//  Curl - wrapper class for php curl function library
//  Generally a fluent interface.
//
///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

class Curl {

  const
    C_TIMEOUT     = 30,
    C_MAXREDIRECT = 4
  ;

  public function __construct($followlocation=true, $timeOut=self::C_TIMEOUT, $maxRedirecs=self::C_MAXREDIRECT, $binaryTransfer=false, $noBody=false) {
    $this->followlocation = $followlocation;
    $this->timeout        = $timeOut;
    $this->maxRedirects   = $maxRedirecs;
    $this->noBody         = $noBody;
    $this->binaryTransfer = $binaryTransfer;
  }


  public final function useAuth($use) {
    $this->authentication = $use ? 1:0; // seems boolean for this didn't work with curl
    return $this;
  }

  public final function setName($name) {
    $this->auth_name = $name;
    return $this;
  }

  public final function setPass($pass) {
    $this->auth_pass = $pass;
    return $this;
  }

  public final function setBinary($binary) {
    $this->binaryTransfer = $binary;
    return $this;
  }

  public final function setReferer($referer) {
    $this->referer = $referer;
    return $this;
  }

  public final function setCookiFileLocation($path) {
    $this->cookieFileLocation = $path;
    return $this;
  }

  public final function setPostData($postFields) {
    $this->post = true;
    $this->postFields = $postFields;
    return $this;
  }

  public final function setUserAgent($userAgent) {
    $this->useragent = $userAgent;
    return $this;
  }

  public function execute($url) {
    $this->cookieFileLocation  = tempnam(dirname(__FILE__).'/cookies/', 'cookie_');

    if ($this->cookieData) {
      file_put_contents($this->cookieFileLocation, $this->cookieData);
    }
    $curlHandle = curl_init();

    curl_setopt_array(
      $curlHandle,
      array(
        CURLOPT_URL,$url,
        CURLOPT_HTTPHEADER     => array('Expect:'),
        CURLOPT_TIMEOUT        => $this->timeout,
        CURLOPT_MAXREDIRS      => $this->maxRedirects,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_COOKIEJAR      => $this->cookieFileLocation,
        CURLOPT_COOKIEFILE     => $this->cookieFileLocation,
        CURLOPT_ENCODING       => '',
        CURLOPT_USERAGENT      => $this->useragent,
        CURLOPT_REFERER        => $this->referer,
        CURLOPT_HEADER         => false,
        CURLOPT_HEADERFUNCTION => array(&$this, 'parseHeader'),
      )
    );

    if ($this->authentication == 1) {
      curl_setopt($curlHandle, CURLOPT_USERPWD, $this->auth_name.':'.$this->auth_pass);
    }

    if ($this->post) {
      curl_setopt_array(
        $curlHandle,
        array(
          CURLOPT_POST        => true,
          CURLOPT_POSTFIELDS  => http_build_query($this->postFields)
        )
      );
    }

    if ($this->noBody) {
      curl_setopt($curlHandle,CURLOPT_NOBODY,true);
    }

    //if ($this->binaryTransfer) {
      //curl_setopt($curlHandle,CURLOPT_BINARYTRANSFER,true);
    //}

    $this->webpage = curl_exec($curlHandle);
    $this->status  = curl_getinfo($curlHandle,CURLINFO_HTTP_CODE);
    curl_close($curlHandle);

    // ok this is a bit lame but I couldn't figure out how to reliably capture the cookie data
    // directly, even using a callback
    $this->cookieData = file_get_contents($this->cookieFileLocation);
    unlink($this->cookieFileLocation);
  }

  public final function getHeaderData() {
    return $this->headerData;
  }

  public final function getCookieData() {
    return $this->cookieData;
  }

  public final function setCookieData($data) {
    $this->cookieData = $data;
  }

  public final function getHttpStatus() {
    return $this->status;
  }

  public final function getContent() {
    return $this->webpage;
  }

  public function __tostring() {
    return $this->webpage;
  }

  private function parseHeader($curlHandle, $string) {
    $len = strlen($string);
    $this->headerData[] = $string;
    return $len;
  }

  private
    $useragent          = 'Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1',
    $cookieFileLocation = './cookie.txt',
    $referer            = "http://www.google.com",
    $cookieData         = null,
    $authentication     = 0,
    $auth_name          = '',
    $auth_pass          = '',
    $followlocation,
    $timeout,
    $maxRedirects,
    $post,
    $postFields,
    $session,
    $webpage,
    $headerData,
    $noBody,
    $status,
    $binaryTransfer

  ;
}
