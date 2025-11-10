<?php
// -----------------------------------------------------------------------------
// 
// web service to support AJAX calls generated from experiment creation and
// configuration registrationViews
// 
// -----------------------------------------------------------------------------
ini_set('display_errors', 'On');
error_reporting(E_ALL);
if (!isset($root_path)) { $root_path = $_SERVER['DOCUMENT_ROOT']; }
require_once($root_path.'/helpers/admin/class.rgConfigurator.php');
include_once $root_path.'/helpers/admin/enums.php';

$permissions = $_GET['permissions'];
$uid = $_GET['uid'];
$messageType = $_GET['messageType'];
$content = $_GET['content'];
$rgID = isset($_GET['rgID']) ? $_GET['rgID'] : -1;



  function processMessage($_messageType, $uid, $rgID, $permissions, $content) {
    $rgConfigurator = new rgConfigurator($uid, $rgID, $permissions);
    switch ($_messageType) {
			// get RG data
	    case 'rgExperimentGroups' : {
		    $json = $rgConfigurator->GetExperimentGroups(returnAsJSON);
		    break;
	    }
	    case "rgExperimentMappings" : {
		    $json = $rgConfigurator->GetFilteredGroupExperiments(returnAsJSON);
		    break;
	    }
	    case "userGroupsPermissions" : {
				$ugp = new stdClass();
				$ugp->groupsMembership = $rgConfigurator->GetAllGroupMemberships(returnAsObject);
				$ugp->users = $rgConfigurator->userManager->getSystemUsers();
				$ugp->currentUser = $rgConfigurator->userManager->getUserPermissions();
				$ugp->exptUsers = $rgConfigurator->GetExperimentMemberships( returnAsObject);
				$json = json_encode($ugp);
		    break;
	    }

			// RG data changes
	    case 'groupNameUpdate': {
		    $json = $rgConfigurator->UpdateGroupName($content);
		    break;
	    }
	    case 'groupNameInsert': {
		    $json = $rgConfigurator->InsertGroupName($content);
		    break;
	    }
	    case "mappingUpdate" : {
		    $json = $rgConfigurator->UpdateMapping($content);
		    break;
	    }
	    case "memberUpdate" : {
		    $json = $rgConfigurator->UpdateMembership($content);
		    break;
	    }
	    case "exptUpdate" : {
	    	$json = $rgConfigurator->UpdateExperimentUserMapping($content);
	    	break;
	    }
      case "roleChange" : {
        $json = $rgConfigurator->ChangeRole($content);
        break;
      }
      

      default : {
        $json = '{"status": false}';
      }
    }
    return $json;
  }

//ensure admin
if ($permissions >= 128) {
  echo processMessage($messageType, $uid, $rgID, $permissions, $content);
}
