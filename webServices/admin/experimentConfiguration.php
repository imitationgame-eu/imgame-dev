<?php
// -----------------------------------------------------------------------------
// 
// web service to support AJAX calls generated from experiment creation and
// configuration pages
// 
// -----------------------------------------------------------------------------
//ini_set('display_errors', 'Off');
//error_reporting(E_ALL);


if (!isset($root_path)) { $root_path = $_SERVER['DOCUMENT_ROOT']; }
require_once($root_path.'/helpers/admin/class.experimentConfigurator.php');
$permissions = $_GET['permissions'];
$uid = $_GET['uid'];
$messageType = $_GET['messageType'];
$content = $_GET['content'];
$exptId = isset($_GET['exptId']) ? $_GET['exptId'] : -1;
//echo $messageType; die;

  function processMessage($_messageType, $_content, $uid, $exptId) {
    $exptConfigurator = new experimentConfigurator($uid, $exptId);
    switch ($_messageType) {
      case "adminHubItems" : {
        //$exptConfigurator->debugLog("started debugging");
        $json = $exptConfigurator->getAdminHubItems();
        break;
      }
      case "exptControls" : {
        $json = $exptConfigurator->getExperimentControls($exptId, $_messageType);
        break;
      }
      case "deleteExpt": {
        $json = $exptConfigurator->deleteExperiment($_content);
        break;
      }
      case "cloneExpt": {
        $cloneExptId = $_content[0];
        $newName = $_content[1];
        $json = $exptConfigurator->cloneExperiment($uid, $cloneExptId, $newName);
        break;
      }
      case "exptSection": {
        $sectionNo = $_content;
        $json = $exptConfigurator->getExperimentSection($exptId, $sectionNo);
        break;
      }
      case "listStep1Users" : {
        $json = $exptConfigurator->getStep1Users($exptId);
        break;
      }
      case "shuffleStatus" : {
        $json = $exptConfigurator->getShuffleStatus($exptId);
        break;
      }
      case "snowShuffleStatus" : {
        $json = $exptConfigurator->getSnowShuffleStatus($exptId);
        break;
      }
      case "leShuffleStatus" : {
        $json = $exptConfigurator->getLEShuffleStatus($exptId);
        break;
      }
      case "tbtShuffleStatus" : {
        $json = $exptConfigurator->getTBTShuffleStatus($exptId);
        break;
      }
      
      
      
      case "createExpt": {
        $json = $exptConfigurator->createExperiment($uid, $_content);
        break;
      }
//      case "newLocation": {
//        $exptConfigurator->addLocation($exptId, $_content);
//        $json = $exptConfigurator->editExperimentStep1($exptId); //effectively a reload
//        break;
//      }
//      case "newSubject": {
//        $exptConfigurator->addSubject($exptId, $_content);
//        $json = $exptConfigurator->editExperimentStep1($exptId); //effectively a reload
//        break;
//      }
      case "ecText": {
        $json = $exptConfigurator->updateFieldValue($exptId, $_content[0], $_content[1]);
        break;
      }
      case "ecSelect": {
        $json = $exptConfigurator->updateParameterValue($exptId, 'select', $_content[0], $_content[1]);
        break;
      }
      case "ecCheck": {
        $json =$exptConfigurator->updateParameterValue($exptId, 'check', $_content[0], $_content[1]);
        break;
      }
      case "getS1ContentSummary": {
        $cDef = $exptConfigurator->getContentHtml($exptId);        
        $json = sprintf("<message><messageType>contentSummary</messageType><content><![CDATA[%s]]></content></message>", $cDef);        
        break;
      }
      // step2 & 4 configuration messages
      case "stepConfigText": {
        $json = $exptConfigurator->stepUpdateFieldValue($exptId, $_content[0], $_content[1]);
        break;
      }
      // game content messages
      case "cdText": {
        // send text directly to contentDef object
        $id = $_content[0];
        $value = $_content[1];
        $json = $exptConfigurator->saveContentValue($exptId, $id, $value);
        break;
      }
      case "saveContentData": { 
        $json = $exptConfigurator->createUsersPage($exptId);
        // mark as ready once any users have been created
        $exptConfigurator->markExptAsReadyStep1($exptId);
        break;
      }
      // experiment config messages
      case "configConnect": {
        $json = $exptConfigurator->addAdmin($uid);
        break;
      }
      case "toggleActiveStatus": {
        $json = $exptConfigurator->toggleActiveStatus($uid, $exptId, $_content[0]);
        break;
      }
      case "s1config": {
        $json = $exptConfigurator->editExperimentStep1($exptId);
        break;
      }
      case "s2config": {
        $json = $exptConfigurator->editStep2Configuration($exptId);
        break;
      }
      case "s4config": {
        $json = $exptConfigurator->editStep4Configuration($exptId);
        break;
      }
      // step1 form list
      case "s1forms": {
        $json = $exptConfigurator->editStep1Forms($exptId);
        break;
      }
      // form-use message
      case "stepFormToggleCheck": {
        $json = $exptConfigurator->toggleUseForm($exptId, $_content[0], $_content[1]);
        break;
      }
      default : {
        $json = "<message><messageType>blank</messageType><content>0</content></message>";
      }
    }
    return $json;
  }

//ensure admin
if ($permissions >= 128) {
  echo processMessage($messageType, $content, $uid, $exptId);
}
