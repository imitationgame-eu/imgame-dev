<?php
/**
*/
ini_set('display_errors', 'On');
error_reporting(E_ALL);
if (!isset($root_path)) { $root_path = $_SERVER['DOCUMENT_ROOT']; }
include_once $root_path.'/domainSpecific/mySqlObject.php';
include_once $root_path.'/helpers/parseJSON.php';
//include_once $root_path.'/helpers/admin/class.userManagement.php';
include_once $root_path.'/helpers/admin/enums.php';

class systemConfigurator {
	private $uid;
	private $permissions;

	// <editor-fold defaultstate="collapsed" desc=" ctor and public interfaces">

	public function GetTopics($returnType){
		global $igrtSqli;
		$tObject = new stdClass();
		$tObject->topics = [];
		$tObject->canEdit = $this->permissions > canModifyLevel;

		$tSql = "SELECT * FROM igTopics order by label";
		$tResult = $igrtSqli->query($tSql);
		while ($tRow = $tResult->fetch_object())
		{
			$topic = new stdClass();
			$topic->id  = $tRow->id;
			$topic->label = $tRow->label;
			array_push($tObject->topics, $topic);
		}
		return $returnType == returnAsJSON ? json_encode($tObject) : $tObject;
	}

	public function CreateNewTopic($content) {

	}

	public function GetLocations($returnType) {
		global $igrtSqli;
		$locationsObject = new stdClass();
		$locationsObject->locations = [];
		$locationsObject->canEdit = $this->permissions > canModifyLevel;

		$locationsSql = "SELECT * FROM igLocations order by label";
		$locationsResult = $igrtSqli->query($locationsSql);
		while ($locationRow = $locationsResult->fetch_object())
		{
			$location = new stdClass();
			$location->id  = $locationRow->id;
			$location->label = $locationRow->label;
			array_push($locationsObject->locations, $location);
		}
		return $returnType == returnAsJSON ? json_encode($locationsObject) : $locationsObject;
	}

	public function CreateNewLocation($content) {
		global $igrtSqli;
		$status = new stdClass();
		$status->messageType = 'newLocation';
		$status->status = '';
		$status->payload = new stdClass();
		$locationsSql = sprintf("SELECT * FROM igLocations where label='%s'", $content);
		$locationsResult = $igrtSqli->query($locationsSql);
		$locationRow = $locationsResult->fetch_object();
		if ($locationRow != null) {
			// already exists - send back error
			$status->status = 'exists';
		}
		else {
			$insertSql = sprintf("INSERT INTO igLocations (label) VALUES('%s')", $content);
			$igrtSqli->query($insertSql);
			$locationsResult = $igrtSqli->query($locationsSql);
			$locationRow = $locationsResult->fetch_object();
			if ($locationRow == null) {
				// could not insert
				$status->status = 'failure';
			}
			else
			{
				$status->status = 'ok';
				$status->payload->id = $locationRow->id;
				$status->payload->label = $locationRow->label;
			}
		}
		return json_encode($status);
	}

	public function __construct($uid, $permissions) {
		$this->uid = $uid;
		$this->permissions = $permissions;
	}

// </editor-fold>

}