<?php
include_once($_SERVER['DOCUMENT_ROOT'].'/domainSpecific/mySqlObject.php');
include_once($_SERVER['DOCUMENT_ROOT'].'/controllers/pageController.php');
include_once($_SERVER['DOCUMENT_ROOT'] . '/controllers/processController.php');

$process = isset($_POST['process']) ? $_POST['process'] : 0;

//if (!empty($_GET['uh']) && !empty($_GET['ph']) && $process == 0) {
//  $process = 1; // special case of password reset link from email
//}

if ($process == 0) {
  if (isset($_GET['restartUID'])) {
    // token passed from pre-Step1 survey needs to be stored
    $pageLabel = '0_0_0';
    $sectionNo = '-1';
    $params = [];
    array_push($params, $_GET['restartUID']);
  }
  else {
    $pageLabel = isset($_POST['pageLabel']) ? $_POST['pageLabel'] : '0_0_0';
    $sectionNo = isset($_POST['sectionNo']) ? $_POST['sectionNo'] : -1;
    $params = null;
  }
  $pageController = new PageController($pageLabel, $sectionNo);
  $uid = isset($_POST['uid']) ? $_POST['uid'] : 0;
  $permissions = isset($_POST['permissions']) ? $_POST['permissions'] : 0;
  $fn = isset($_POST['fName']) ? $_POST['fName'] : 0;
  $sn = isset($_POST['sName']) ? $_POST['sName'] : 0;
  $email = isset($_POST['email']) ? $_POST['email'] : 0;
  if ($sectionNo > -1) {
    $referer = '1_1_1';   // subsections always referred from admin hub
    //echo 'subsection';
  }
  else {
    $referer = isset($_POST['referer']) ? $_POST['referer'] : '0_0_0';
    //echo 'normal referer: '.$referer;
  }
  $lastChild = isset($_POST['lastChild']) ? $_POST['lastChild'] : 'unset';
  // buttonId can be array of values, an expt ptr, or a button ID depending on source
  $exptId = isset($_POST['exptId']) ? $_POST['exptId'] : -1;
  $pageHtml = $pageController->invoke($uid, $permissions, $fn, $sn, $email, $referer, $lastChild, $exptId, $params);
  
}


//  xdebug_break();
//  var_dump($_POST);
//  var_dump(xdebug_get_headers());
  echo $pageHtml;
