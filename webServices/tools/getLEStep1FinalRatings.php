<?php
// -----------------------------------------------------------------------------
// 
// web service to retrieve raw data from completed sessions
// 
// -----------------------------------------------------------------------------

if (!isset($root_path)) { $root_path = $_SERVER['DOCUMENT_ROOT']; }
$permissions=255;
$uid=28;




if (($uid==28) && ($permissions>=128)) {
  header('Content-type: text/html charset=utf-8');

  $exptIds = [327,328,329,330];
  
  //echo 'data extract';
  include_once $root_path.'/domainSpecific/mySqlObject.php'; 
  for ($i=0; $i<4; $i++) {
    $exptId = $exptIds[$i];
    echo 'exptId: '.$exptId.'<br/>';
    for ($jType=0; $jType<2; $jType++) {
      $jTypeStr = $jType == 0 ? "Even interrogators" : "Odd interrogators";
      echo $jTypeStr.'<br/>';
      $gameIdQry = sprintf("SELECT DISTINCT(jNo) AS jNo FROM dataSTEP1 WHERE exptId='%s' AND jType='%s' AND dayNo='1' AND sessionNo=1 ORDER BY jNo ASC", $exptId, $jType);
      $gameIdResult = $igrtSqli->query($gameIdQry);
      if ($gameIdResult) {
        while ($jRow = $gameIdResult->fetch_object()) {
          $jNo = $jRow->jNo; 
          echo "I#: ".($jNo + 1).', ';
          $dataQry = sprintf("SELECT * FROM dataSTEP1 WHERE exptId='%s' AND jType='%s' AND dayNo='1' AND sessionNo=1 AND jNo='%s' AND q='FINAL'", $exptId, $jType, $jNo);
          $dataResult = $igrtSqli->query($dataQry);
          while ($dataRow = $dataResult->fetch_object()) {
            $npLeft = $dataRow->npLeft;
            $choice = $dataRow->choice;
            $confidence = substr($dataRow->rating, -1);
            $reason = $dataRow->reason;
            $correct = ($npLeft != $choice) ? 1 : 0;
            echo 'correct: '.$correct.', confidence: '.$confidence.', reason:'.$reason.'<br/>';
          }
        }
      }
    }
  }
}
else {
  echo 'not authorised';
}

