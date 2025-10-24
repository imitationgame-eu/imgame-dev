<?php
  ini_set('display_errors', 'On');
  error_reporting(E_ALL); 
if (!isset($root_path)) { $root_path = $_SERVER['DOCUMENT_ROOT']; }
  include_once $root_path.'/domainSpecific/mySqlObject.php';     
  $html = "no data";
  $sql = "SELECT * FROM dataSTEP1_301 WHERE id=0 ORDER BY uid ASC";
  $result = $igrtSqli->query($sql);
  if ($result) {
    $html = "";
    while ($row = $result->fetch_object()) {
      $choice = $row->choice;
      $rating = $row->rating;
      $reason = $row->reason;
      $uid = $row->uid;
      $emailNo = $uid - 25291 + 3033;
      $email = $emailNo . '@s1.com';
      $html.= $email.'<br/>';
      $npLeft = ($uid %2 == 0) ? 1 : 0;
      $correct = ($npLeft == $choice) ? 1 : 0;
      $html.= 'correct: '. $correct . '<br/>';
      $html.= 'confidence: '. $rating . '<br/>';
      $html.= 'reason: '. $reason . '<br/>';
      $html.= '<hr/>';
    }
  }
  echo $html;
  

