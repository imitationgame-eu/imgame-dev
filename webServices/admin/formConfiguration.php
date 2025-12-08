<?php
// -----------------------------------------------------------------------------
// 
// web service to support AJAX calls generated from step form
// configuration pages
// 
// uses class.stepFormsHandler.php which is used at this configuration stage
// and also at runtime where it builds the form
// 
// -----------------------------------------------------------------------------
ini_set('display_errors', 'On');
error_reporting(E_ALL);
if (!isset($root_path)) { $root_path = $_SERVER['DOCUMENT_ROOT']; }
require_once($root_path.'/helpers/admin/class.stepFormsConfigurator.php');
include_once($root_path."/classes/doPost.php");
$permissions = $_GET['permissions'];
$messageType = $_GET['messageType'];
$formName = $_GET['formName'];
$userId = $_GET['userId'];
$exptId = $_GET['exptId'];
$content = $_GET['content'];

//  function rePost() {
//    global $permissions, $uid, $messageType, $formName, $exptId;
//    // form structure has changed, or this is the first load,
//    // so send original post request for full reload
//    $postdata=array (
//      'process' => 0,
//      'action' => '1_2_5',
//      'uid' => $uid,
//      'exptId' => $exptId,
//      'permissions' => $permissions,
//      'messageType' => $messageType,
//      'buttonId' => 'config_'.$formName,
//    );
//    $this->postie->do_curl_post($this->domain, $postdata);
//  }

  function processMessage($_messageType, $formName, $_content, $exptId) {
    $formConfigurator = new stepFormsConfigurator($exptId, $formName);
    $html = '';
//    switch ($_messageType) {
//      case "stepFormConfig" : {
//        rePost();
//        break;
//      }
//      case "formCheck": {
//        $html = $formConfigurator->ProcessFormCheck($_content[0], $_content[1]);
//        break;
//      }
//      case "addOption": {
//        $optionType = $_content[0]; // unused
//        $pageNo = $_content[1];
//        $qNo = $_content[2];
//        $optionNo = $_content[3];
//        $html = $formConfigurator->AddOption($optionType, $pageNo, $qNo, $optionNo);
//        break;
//      }
//      case "delOption": {
//        $optionType = $_content[0]; // unused
//        $pageNo = $_content[1];
//        $qNo = $_content[2];
//        $optionNo = $_content[3];
//        $html = $formConfigurator->DelOption($optionType, $pageNo, $qNo, $optionNo);
//        break;
//      }
//      case "addQuestion" : {
//        $pageNo = $_content[0];
//        $qNo = $_content[1];
//        $html = $formConfigurator->AddQuestion($pageNo, $qNo);
//        break;
//      }
//      case "cloneQuestion" : {
//        $pageNo = $_content[0];
//        $qNo = $_content[1];
//        $html = $formConfigurator->CloneQuestion($pageNo, $qNo);  // add to end of list to avoid renumbering
//        break;
//      }
//      case "delQuestion" : {
//        $pageNo = $_content[0];
//        $qNo = $_content[1];
//        $html = $formConfigurator->DelQuestion($pageNo, $qNo);
//        break;
//      }
//      case "addPage" : {
//        $pageNo = $_content[0];
//        $html = $formConfigurator->AddPage($pageNo);
//        break;
//      }
//      case "clonePage" : {
//        $pageNo = $_content[0];
//        $html = $formConfigurator->ClonePage($pageNo);  // add to end of list to avoid renumbering
//        break;
//      }
//      case "delPage" : {
//        $pageNo = $_content[0];
//        $html = $formConfigurator->DelPage($pageNo);
//        break;
//      }
//    }
    return $html;
  }

//ensure admin
if ($permissions >= 128) {
  $retMsg = processMessage($messageType, $formName, $content, $exptId);
  //$retMsg= "<message><messageType>$messageType</messageType><content>$content</content><exptId>$exptId</exptId></message>";
  echo $retMsg;
}
