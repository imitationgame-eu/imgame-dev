<?php
ini_set('display_errors', 'On');
error_reporting(E_ALL);
$full_ws_path = realpath(dirname(__FILE__));
$root_path = substr($full_ws_path, 0, strlen($full_ws_path)-5); // /tests
include_once $root_path.'/domainSpecific/mySqlObject.php';       

$sql = "SELECT * FROM dataSTEP1 WHERE exptId=260 AND dayNo=1 AND sessionNo=2 ORDER BY jType ASC, jNo ASC, qNo ASC";
$rowResult = $igrtSqli->query($sql);
echo "Budapest Step 1 data<br />";
echo "day 1 session 2<br />(missing rating means the judge was rating when crach occurred<br />";
echo "turn (0=turn, 1=final rating),JType,JNo,QNo,non-Pretender on Left (0 = pretender left, 1 = pretender right),choice (0 = person left, 1 = person on right), confidence<br />";
while ($row = $rowResult->fetch_object() ) {
  $npLeft = $row->npLeft;
  $choice = $row->choice;
  $rating = $row->rating;
  $jType = $row->jType;
  $jNo = $row->jNo;
  $qNo = $row->qNo;
  if ($row->q == 'FINAL') {
    $confidenceInt = substr($rating, 13);
    echo "1, $jType , $jNo, $npLeft, $choice, $confidenceInt <br />";
  }
  else {
    if (substr($rating, 0, 1) == ' ') { $confidenceInt = substr($rating, 9); } else { $confidenceInt = substr($rating, 8); } 
    //$confidenceInt = substr($rating, 9);
    echo "0, $jType , $jNo, $npLeft, $choice, $confidenceInt <br />";   
  }
  
}
$sql = "SELECT * FROM dataSTEP1 WHERE exptId=260 AND dayNo=1 AND sessionNo=4 ORDER BY jType ASC, jNo ASC, qNo ASC";
$rowResult = $igrtSqli->query($sql);
echo "Budapest Step 1 data<br />";
echo "day 1 session 4<br />";
echo "turn (0=turn, 1=final rating),JType,JNo,QNo,non-Pretender on Left (0 = pretender left, 1 = pretender right),choice (0 = person left, 1 = person on right), confidence<br />";
while ($row = $rowResult->fetch_object() ) {
  $npLeft = $row->npLeft;
  $choice = $row->choice;
  $rating = $row->rating;
  $jType = $row->jType;
  $jNo = $row->jNo;
  $qNo = $row->qNo;
  if ($row->q == 'FINAL') {
    $confidenceInt = substr($rating, 13);
    echo "1, $jType , $jNo, $npLeft, $choice, $confidenceInt <br />";
  }
  else {
    if (substr($rating, 0, 1) == ' ') { $confidenceInt = substr($rating, 9); } else { $confidenceInt = substr($rating, 8); } 
    //$confidenceInt = substr($rating, 9);
    echo "0, $jType , $jNo, $npLeft, $choice, $confidenceInt <br />";   
  }
  
}

