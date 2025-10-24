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
require_once($root_path.'/helpers/admin/class.systemConfigurator.php');
include_once $root_path.'/helpers/admin/enums.php';

$permissions = $_GET['permissions'];
$uid = $_GET['uid'];
$messageType = $_GET['messageType'];
$content = $_GET['content'];

  function processMessage($_messageType, $uid, $permissions, $content) {
    $systemConfigurator = new systemConfigurator($uid, $permissions);
    switch ($_messageType) {
	    case 'getLocations' : {
		    $json = $systemConfigurator->GetLocations(returnAsJSON);
		    break;
	    }
	    case 'newLocation' : {
		    $json = $systemConfigurator->CreateNewLocation($content);
		    break;
	    }
	    case 'getTopics' : {
		    $json = $systemConfigurator->GetTopics(returnAsJSON);
		    break;
	    }
	    case 'newTopic' : {
		    $json = $systemConfigurator->CreateNewTopic($content);
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
  echo processMessage($messageType, $uid, $permissions, $content);
}
