<?php
ini_set('display_errors', 'On');
error_reporting(E_ALL);
if (!isset($root_path)) { $root_path = $_SERVER['DOCUMENT_ROOT']; }
include_once $root_path.'/domainSpecific/mySqlObject.php';


class userManagement {

	private $uid;

	// gets information about other system users but NOT current user
	public function GetSystemUsers() {
		global $igrtSqli;
		$users = [];
		$sql = "SELECT * FROM igrt.igUsers where 
email NOT LIKE '%@imgame%'
AND email NOT LIKE '%@redweb%'
AND email NOT LIKE '%@s1%'
AND email NOT LIKE '%@classic%'
AND email > ''";
		$result = $igrtSqli->query($sql);
		while ($row = $result->fetch_object()) {
			array_push($users, ['id' => $row->id, 'permissions' => $row->permissions, 'email' => $row->email]);
		}
		return $users;
	}

	// gets information about logged-in user
	public function GetUserPermissions() {
		global $igrtSqli;
		$userPermissions = new stdClass();
		$userQuery = sprintf("SELECT * FROM igUsers WHERE id='%s'", $this->uid);
		$userResult = $igrtSqli->query($userQuery);
		if ($userResult->num_rows > 0) {
			$userRow = $userResult->fetch_object();
			//$userRow->permissions = 128;
			$userPermissions->isSuperUser = $userRow->permissions == 1024 ? true : false;
		}
		$userPermissions->permissions = $userRow->permissions;
		// get experiments solely owned by this user
		$userExptQuery = sprintf("SELECT * FROM igExperiments WHERE ownerId='%s'", $this->uid);
		$userExptResult = $igrtSqli->query($userExptQuery);
		$userPermissions->ownedExperiments = [];
		if ($userExptResult) {
			while ($userExptRow = $userExptResult->fetch_object()) {
				array_push($userPermissions->ownedExperiments, $userExptRow->exptId);
			}
		}
		// get experiments this user has individual mapping for
		$userMappingQuery = sprintf("SELECT * FROM igExperimentUserMembers WHERE userId='%s'", $this->uid);
		$userMappingResult = $igrtSqli->query($userMappingQuery);
		$userPermissions->userExperimentMappings = [];
		if ($userMappingResult) {
			while ($userMappingRow = $userMappingResult->fetch_object()) {
				array_push($userPermissions->userExperimentMappings, $userMappingRow->exptId);
			}
		}
		// get groups this user belongs to and de-reference experiments
		$userGroupQuery = sprintf("SELECT * FROM igExperimentGroupMembers WHERE userId='%s'", $this->uid);
		$userGroupResult = $igrtSqli->query($userGroupQuery);
		$userPermissions->groupOwnedExperiments = [];
		if ($userGroupResult) {
			while ($groupMembership = $userGroupResult->fetch_object()) {
				$userPermissions->userGroupMemberships[]= $groupMembership->groupId;
				$groupExperimentQuery = sprintf("SELECT * FROM igExperimentGroupMappings WHERE groupId='%s'", $groupMembership->groupId);
				$groupExperimentResult = $igrtSqli->query($groupExperimentQuery);
				while ($groupExperimentRow = $groupExperimentResult->fetch_object()) {
					if (!in_array($groupExperimentRow->exptId, $userPermissions->groupOwnedExperiments)) {
						array_push($userPermissions->groupOwnedExperiments, $groupExperimentRow->exptId);
					}
				}
			}
		}
		return $userPermissions;
	}

	public function __construct($_uid) {
		$this->uid = $_uid;
	}


}