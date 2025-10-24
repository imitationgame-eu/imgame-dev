<?php
class PostChap {
  /**
   * gets the page html from the generator
   * @param $data
   * @return bool|string
   */
  public function getPageHtml($xdebug, $data) {
    // add xdebug post parameter if debugging required
    if ($xdebug) {
      $data['XDEBUG_TRIGGER'] ='mh';
      $data['XDEBUG_SESSION_START'] = 'mh';
    }
    //open connection
    $ch = curl_init();
    //escape and build safe query
    $postdata=http_build_query($data);
    $domain = $_SERVER['SERVER_NAME'];
    //$url = 'http://'.$domain.'/webServices/api/pageBuilder.php';
    $url = 'http://localhost/webServices/api/pageBuilder.php';
    //set the url, number of POST vars, POST data
    curl_setopt($ch, CURLOPT_URL, $url);
    //curl_setopt($ch, CURLOPT_HTTPHEADER, array("Cookie: XDEBUG_SESSION_START=mh"));
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postdata);
//    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
//    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/x-www-form-urlencoded'));
    curl_setopt($ch, CURLOPT_VERBOSE, true);
    if (defined('CURLOPT_IPRESOLVE') && defined('CURL_IPRESOLVE_V4')){
      curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
    }
    
    if ($domain == "ig2.com") {
      $resolve = array(sprintf("%s:%d:%s", $url, 80, "192.168.1.134")); //
      curl_setopt($ch, CURLOPT_RESOLVE, $resolve);
    }
    //execute post
    $result = curl_exec($ch);
    $error = curl_error($ch);
    
    if (!$result) {
      $error = curl_error($ch);
    }
    //close connection
    curl_close($ch);
    return $result;
    
  }

  public function do_curl_post($url, $data) {
    //open connection
    $ch = curl_init();
    //escape and build safe query
    $postdata=http_build_query($data);
    //set the url, number of POST vars, POST data
    curl_setopt($ch, CURLOPT_URL, $url.'/index.php'); //"150.214.29.10/index.php"
    curl_setopt($ch, CURLOPT_POST, 1);  // 1 == POST
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postdata);
	  if ($url == "ig2.com") {
		  $resolve = array(sprintf("%s:%d:%s", $url, 80, "192.168.1.134")); //
		  curl_setopt($ch, CURLOPT_RESOLVE, $resolve);
	  }
	  //execute post
    $result = curl_exec($ch);
    if (!$result) {
      $error = curl_error($ch);
    }
    //close connection
    curl_close($ch);
    return $result;   
  }

  public function __construct() {
    // nothing specific
  }
}

