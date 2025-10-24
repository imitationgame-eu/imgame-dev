<?php
ob_start();
ob_clean();
ini_set('display_errors', 'On');
error_reporting(E_ALL);
$rURI = $_SERVER['REQUEST_URI'];
$clean = explode('?XDEBUG', $rURI);
$rURI = $clean[0];

$isIndex = substr($rURI, 0, 10);
// process a page or post request    
include_once($_SERVER['DOCUMENT_ROOT'].'/domainSpecific/mySqlObject.php');
include_once($_SERVER['DOCUMENT_ROOT'].'/domainSpecific/domainInfo.php');
include_once($_SERVER['DOCUMENT_ROOT'].'/controllers/pageController.php');
include_once($_SERVER['DOCUMENT_ROOT'] . '/controllers/processController.php');
include_once($_SERVER['DOCUMENT_ROOT'] . '/controllers/staticPageController.php');

if ($isIndex == '/index.php') {

	$process = isset($_POST['process']) ? $_POST['process'] : 0;

	if (!empty($_GET['uh']) && !empty($_GET['ph']) && $process == 0) {
		$process = 1; // special case of password reset link from email
	}

  if ($process > 0) {
    // 1 indicates data to be processed and then state inferred by creating a new post to index.php - login, register, password management
    // 2 indicates status result of an action (e.g. activation, password change etc)
    switch ($process) {
      case 1:
        $processController=new ProcessController();
        $processController->invoke();
        $pageHtml = $processController->responseData;
        break;
      case 2:
        $statusPageController = new StaticPageController($_POST['pageLabel']);
        $statusPageController->invoke();
        $pageHtml = $statusPageController->responseData;
        break;
    }
    
  }
  else {
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
}
else {
  $uri = trim($rURI,'/'); //
  // process a rewritten clean URL -   use $rURI to see what the request is
  $params = explode('_', $uri);
  switch ($params[0]) {
	  case 'ps1' : {
	  	$pageLabel = '9_9_9';
	  	break;
	  }
    case 's1' : {
      // step1 demo shortcut
      $pageLabel = '4_0_2';
      break;
    }
    case 's2' : {
      $pageLabel = '5_0_2';  // go to real Step2 start page            
      break;
    }
    case 'is2' : {
      $pageLabel = '5_0_3';  // go to inverted Step2 start page            
      break;
    }
    case 's4' : {
      $pageLabel = '6_0_2';  // go to Step4 start page                        
      break;
    }
    case 'nes4' : {
      $pageLabel = '6_0_3';  // go to null experiment Step4 start page                        
      break;
    }
    case 'les4' : {
      $pageLabel = '6_0_4';  // go to linked-experiment Step4 start page                        
      break;
    }
    case 'les4single' : {
      $pageLabel = '6_0_5'; // turn-by-turn version of the linked-experiment step4
      break;
    }
    case 's4single' : {
      $pageLabel = '6_0_6'; // turn-by-turn version step4
      break;
    }
    case 'sf' : {
      $pageLabel = '7_0_1'; // any step form
      break;
    }
    default : {
      $pageLabel = '0_0_0';
    }
  }
  $pageController = new PageController($pageLabel);
  $pageHtml = $pageController->invoke(null, null, null, null, null, null, null, null, $params);          
}  

echo $pageHtml;
ob_end_flush();


