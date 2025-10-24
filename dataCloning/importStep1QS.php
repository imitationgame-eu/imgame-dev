<?php
ini_set('display_errors', 'On');
error_reporting(E_ALL);
if (!isset($root_path)) { $root_path = $_SERVER['DOCUMENT_ROOT']; }
include_once $root_path.'/domainSpecific/mySqlObject.php'; 
$exptId = 276;
$lines = file('files/EnglishOfPolish.txt', FILE_IGNORE_NEW_LINES);
$lineCnt = count($lines);
$datalines = array();
for ($i=0; $i<$lineCnt; $i++) {
  if (isset($temp)) { unset($temp); }
  $temp = array("count" => strlen($lines[$i]), "data"=>$lines[$i]);
  array_push($datalines, $temp);
}
// echo print_r($datalines, true);

$jType = 0;
$jNo = -1;
$uid = -1;
$dayNo = -1;
$sessionNo = -1;
$qNo = -1;
$process = true;
if ($process) {
  for ($i=0; $i<count($datalines); $i++) {
    $ss = substr($datalines[$i]['data'], 0, 2);
    switch ($ss) {
      case '"I' : {
        ++$jNo;
        echo 'processing judge # '.$jNo.' of type '.$jType.'<br />';
        $qNo = 0;
        break;
      }
      case '"Q' : {
        $qDetails = explode('<:>', $datalines[$i]['data']);
        //$q = substr($qDetails[1], 1);
        $q = $qDetails[1];
        $q = trim($q, '"');
        ++$qNo;
        $insertSql = sprintf("INSERT INTO md_dataStep1reviewed (exptId, dayNo, sessionNo, jType, jNo, qNo, q, reviewed, canUse)"
            . " VALUES('%s','1','1','%s','%s','%s','%s','1','1')",
            $exptId, $jType, $jNo, $qNo, $igrtSqli->real_escape_string($q));
        //echo $insertSql.'<br />';
        $igrtSqli->query($insertSql);
        break;
      }
      case '"N' : {
        $npDetails = explode('<:>', $datalines[$i]['data']);
        //$np = substr($npDetails[1], 1);
        $np = $npDetails[1];
        //$np = trim($q, '"');
        $updateSql = sprintf("UPDATE md_dataStep1reviewed SET npr='%s' WHERE exptId='%s' "
            . "AND dayNo='1' AND sessionNo='1' AND jType='%s' AND jNo='%s' AND qNo='%s'",
            $igrtSqli->real_escape_string($np), $exptId, $jType, $jNo, $qNo);
        //echo $updateSql.'<br />';
        $igrtSqli->query($updateSql);
        break;
      }
    }
  }
}

echo "done!";

