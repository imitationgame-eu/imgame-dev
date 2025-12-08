<?php
/**
 * Experiment Configuration
 * top-level controller to create/delete experiments
 * @author MartinHall
 * used mainly in AJAX pages, but also used in Step1 admin pages to
 * provide experiment status details
 */
ini_set('display_errors', 'On');
error_reporting(E_ALL);
if (!isset($root_path)) { $root_path = $_SERVER['DOCUMENT_ROOT']; }
include_once $root_path.'/domainSpecific/mySqlObject.php';
include_once $root_path.'/helpers/models/class.experimentModel.php';
include_once $root_path.'/helpers/forms/class.stepFormsHandler.php';
include_once $root_path.'/helpers/parseJSON.php';
include_once $root_path.'/helpers/admin/class.metadataConverter.php';
include_once $root_path.'/helpers/admin/class.userManagement.php';
include_once $root_path.'/helpers/step3/class.shuffleController.php';
include_once $root_path.'/helpers/html/class.htmlBuilder.php';

class experimentConfigurator {
  private $formControlSelectOptions = []; 
  private $metadataConverter;
  private $eModel;
  private $exptId;
  private $uid;
  private $htmlBuilder; // currently still used in Step1 controller
  private $_tabIndex;
  private $userPermissions;
  
  function debugLog($logEntry) {
    global $igrtSqli;
    $insertQry = sprintf("INSERT INTO debugLog (Message) VALUES('%s')", $logEntry);
    $igrtSqli->query($insertQry);
  }

// <editor-fold defaultstate="collapsed" desc=" top level experiment controls and helpers">

	function populateExperimentGroupMappings() {
		global $igrtSqli;
		$getExptQry = "SELECT * FROM igExperiments";
		$exptResult = $igrtSqli->query($getExptQry);
		while ($exptRow = $exptResult->fetch_object()) {
			$insertQry = sprintf("INSERT INTO igExperimentGroupMappings (groupId, exptId) VALUES ('1', '%s')", $exptRow->exptId );
			$igrtSqli->query($insertQry);
		}
	}

	function getTopLevelControlsJSON($tlRow) {
		global $igrtSqli;
		$json = '';
		$count = 0;
		switch ($tlRow->id) {
			case 1: {
				$categoriesQry = sprintf("SELECT * FROM ui_experimentCategories WHERE parentId='%s'", $tlRow->id);
        //$this->debugLog($categoriesQry);
				$categoriesResult = $igrtSqli->query($categoriesQry);
				if ($categoriesResult->num_rows > 0) {
					while ($categoryRow = $categoriesResult->fetch_object()) {
						// get accordion statuses
						$isClosed = true;
						$accordionQry = sprintf("SELECT * FROM ui_experimentCategoriesUserStatus WHERE categoryId='%s' AND userId='%s'", $categoryRow->id, $this->uid);
            //$this->debugLog($accordionQry);
            $accordionResult = $igrtSqli->query($accordionQry);
						if ($accordionResult->num_rows == 0) {
							// insert new record for this user/category
							$insertQry = sprintf("INSERT INTO ui_experimentCategoriesUserStatus (categoryId, userId, isClosed) VALUES ('%s', '%s', '1')", $categoryRow->id, $this->uid);
							$igrtSqli->query($insertQry);
						}
						else {
							$accordionRow = $accordionResult->fetch_object();
							$isClosed = $accordionRow->isClosed;
						}
						if ($count > 0) { $json.=","; }
						$json.= "{";
						$json.= "\"hasItems\":true,";
						$json.= "\"id\":\"sl1_1_".$categoryRow->id."\",";
						$json.= "\"accordionClosed\":\"". $isClosed . "\",";
						$json.= "\"accordionFieldname\":\"" . $categoryRow->controlName . "\",";
						$json.= "\"categoryLabel\":\"" . $categoryRow->controlLabel . "\",";
						$json.= "\"hasDescription\":false,";
						$json.= "\"descriptionLabel\":\"\",";
						// build json for actual controls
						$json.= "\"items\":[";
						$experimentList = $this->getExperimentListArray($categoryRow->isInactive, $categoryRow->isInjected);
						$json.= $this->makeExperimentSelectorJSON($experimentList);
						$json.= "]";
						$json.= "}";
						++$count;
					}
				}
				break;
			}
			case 2: {
				// get accordion statuses
				$sectionsQry = sprintf("SELECT * FROM ui_sectionCategories ");
        //echo $sectionsQry;
				$sectionsResult = $igrtSqli->query($sectionsQry);
				if ($sectionsResult->num_rows>0) {
					while ($sectionRow = $sectionsResult->fetch_object()) {
						$isClosed = true;
						$accordionQry = sprintf("SELECT * FROM ui_sectionCategoriesUserStatus WHERE sectionId='%s' AND userId='%s'", $sectionRow->id, $this->uid);
						//echo $accordionQry;
            $accordionResult = $igrtSqli->query($accordionQry);
						if ($accordionResult->num_rows == 0) {
							// insert new record for this user/category
							$insertQry = sprintf("INSERT INTO ui_sectionCategoriesUserStatus (sectionId, userId, isClosed) VALUES ('%s', '%s', '1')", $sectionRow->id, $this->uid);
							$igrtSqli->query($insertQry);
						}
						else {
							$accordionRow = $accordionResult->fetch_object();
							$isClosed = $accordionRow->isClosed;
						}
						if ($count > 0) { $json.=","; }
						$json.= "{";
						$json.= "\"hasItems\":true,";
						$json.= "\"id\":\"sl1_2_".$sectionRow->id."\",";
						$json.= "\"accordionClosed\":\"". $isClosed . "\",";
						$json.= "\"accordionFieldname\":\"" . $sectionRow->controlName . "\",";
						$json.= "\"categoryLabel\":\"" . $sectionRow->controlLabel . "\",";
						$json.= "\"hasDescription\":false,";
						$json.= "\"descriptionLabel\":\"\",";
						// build json for actual controls
						$json.= "\"items\":[";
						$docSectionList = $this->getSystemSectionList($sectionRow->id);
						$json.= $this->makeSectionJSON($docSectionList);
						$json.= "]";
						$json.= "}";
						++$count;
					}
				}
				break;
			}
//      case 3:
//        // get accordion statuses
//        $docSectionsQry = sprintf("SELECT * FROM ui_documentationCategories ");
//        $docSectionsResult = $igrtSqli->query($docSectionsQry);
//        if ($docSectionsResult->num_rows>0) {
//          while ($docSectionRow = $docSectionsResult->fetch_object()) {
//            if ($count > 0) { $json.=","; }
//            $json.= "{";
//            $json.= "\"hasItems\":false,";
//            $json.= "\"id\":\"doc_".$docSectionRow->id."\",";
//            $json.= "\"accordionClosed\":\"true\",";
//            $json.= "\"accordionFieldname\":\"" . $docSectionRow->controlName . "\",";
//            $json.= "\"categoryLabel\":\"" . $docSectionRow->controlLabel . "\",";
//            $json.= "\"hasDescription\":false,";
//            $json.= "\"descriptionLabel\":\"\",";
//            // build json for actual controls
//            $json.= "\"items\":[";
//            $json.= "]";
//            $json.= "}";
//            ++$count;
//          }
//        }
//        break;
    }

		return $json;
	}
  

	function makeExperimentSelectorJSON($experimentList) {
		$json = "";
		for ($i=0; $i<count($experimentList); $i++) {
			$experiment = $experimentList[$i];
			if ($i>0) { $json.= ","; }
			$json.= "{";
			$json.= "\"isExperiment\": 1,";
			$json.= "\"exptId\":" . $experiment['exptId'] . ",";
			$json.= "\"exptTitle\":" . JSONparse($experiment['exptTitle']) . ",";
			$json.= "\"hasDescription\":false,";
			$json.= "\"description\":" . JSONparse($experiment['description']);
			$json.= "}";
		}
		return $json;
	}

	function getAdminHubItems() {
		global $igrtSqli;
		//$this->populateExperimentGroupMappings();
		// get tabs info
		$json = "{\"tabs\":[";
		$topLevelQry = "SELECT * FROM ui_topLevelSections ORDER BY id ASC";
    //$this->debugLog($topLevelQry);
		$tlResult = $igrtSqli->query($topLevelQry);
		$count = -1;
		while ($tlRow = $tlResult->fetch_object()) {
			$accordionQry = sprintf("SELECT * FROM ui_topLevelSectionsUserStatus WHERE accordionId='%s' AND uid='%s'", $tlRow->id, $this->uid);
      //$this->debugLog($accordionQry);
      //echo $accordionQry;
			$accordionResult = $igrtSqli->query($accordionQry);
			$isClosed = 1;
			if ($accordionResult->num_rows == 0) {
				// make a record for this user
				$insert = sprintf("INSERT INTO ui_topLevelSectionsUserStatus (uid, accordionId, isClosed) VALUES ('%s', '%s', '1')", $this->uid, $tlRow->id );
				$igrtSqli->query($insert);
			}
			else {
				$accordionRow = $accordionResult->fetch_object();
				$isClosed = $accordionRow->isClosed;
			}
			if (++$count > 0) { $json.=","; }
			$json.= "{";
			$json.= "\"tabName\":\"" . $tlRow->controlLabel . "\",";
			$json.= "\"level\":\"0\",";
			$json.= "\"id\":\"tl_" . $tlRow->id . "\",";
			$json.= "\"accordionClosed\":" . $isClosed . ",";
			$json.= "\"accordionFieldname\":\"" . $tlRow->controlName . "\",";
			$json.= "\"categories\":[";
			$json.= $this->getTopLevelControlsJSON($tlRow);
			$json.= "]";
			$json.= "}";
		}
		$json.= "]}";
		return $json;
	}

	function getSystemSectionList($categoryId) {
		global $igrtSqli;
		$sectionList = [];
		$sectionQry = sprintf("SELECT * FROM ui_sectionControlKeys WHERE sectionControlHeaderKey='%s' ORDER BY displayOrder ASC", $categoryId);
		//echo $sectionQry;
    $sectionResult = $igrtSqli->query($sectionQry);
		while ($sectionRow = $sectionResult->fetch_object()) {
			array_push($sectionList, $sectionRow);
		}
		return $sectionList;
	}

	function makeSectionJSON($sectionList) {
		$json = "";
		for ($i= 0; $i<count($sectionList); $i++) {
			$section = $sectionList[$i];
			if ($i>0) { $json.= ","; }
			$json.= "{";
			$json.= "\"isExperiment\": 0,";
			$json.= "\"sectionId\":" . $section->id . ",";
			$json.= "\"pageLabel\":" . JSONparse($section->pageLabel) . ",";
			$json.= "\"label\":" . JSONparse($section->label);
			$json.= "}";
		}
		return $json;
	}

	function getExperimentListArray($isInactive, $isInjected) {
		global $igrtSqli;
		$userPermissions = $this->userPermissions;
		$exptList = [];
		$sqlqry_exptList=sprintf("SELECT * "
			. "FROM igExperiments as t1 JOIN edContentDefs_refactor as t2 JOIN edExptStatic_refactor as t3 "
			. "WHERE t1.isInactive='%s' AND t1.isInjected='%s' "
			. "AND t1.exptId=t2.exptId AND t3.exptId=t2.exptId ORDER BY t1.exptId DESC",
			$isInactive, $isInjected);
    //echo $sqlqry_exptList;
		$exptResult = $igrtSqli->query($sqlqry_exptList);
		if ($exptResult->num_rows>0) {
			while ($row = $exptResult->fetch_object()) {
				$canList = false;
				if ($userPermissions->isSuperUser){ $canList = true; }
				if (count($userPermissions->ownedExperiments) > 0 && in_array($row->exptId, $userPermissions->ownedExperiments)) { $canList = true; }
				if (count($userPermissions->userExperimentMappings) > 0 && in_array($row->exptId, $userPermissions->userExperimentMappings)) { $canList = true; }
				if (count($userPermissions->groupOwnedExperiments) > 0 && in_array($row->exptId, $userPermissions->groupOwnedExperiments)) { $canList = true; }
				if ($canList) {
					$eModel = $this->getExperimentStatusAndUI($row);
					array_push($exptList, $eModel);
				}
			}
		}
		return $exptList;
	}

	function getUserPermissions() {
		$userManager = new userManagement($this->uid);
		return $userManager->getUserPermissions();
	}

  function isVisible($controlName) {
    return true;
  }
  
  function getHeadersStates() {
    global $igrtSqli;
    $getStates = sprintf("SELECT * FROM ui_experimentControlHeaders WHERE uid='%s' AND exptId='%s'", $this->uid, $this->exptId);
    $result = $igrtSqli->query($getStates);
    if ($result->num_rows > 0) {
      $row = $result->fetch_object();
      return [
        'operationGroupClosed'=> $row->operationGroupClosed,
        'experimentOverviewClosed'=> $row->experimentOverviewClosed,
        's1SettingsLabelsClosed'=> $row->s1SettingsLabelsClosed,
        's2SettingsLabelsClosed'=> $row->s2SettingsLabelsClosed,
        'iS2SettingsLabelsClosed'=> $row->iS2SettingsLabelsClosed,
        's4SettingsLabelsClosed'=> $row->s4SettingsLabelsClosed,
        'surveysClosed'=> $row->surveysClosed,
        's1ControlClosed'=> $row->s1ControlClosed,
        's2ControlClosed'=> $row->s2ControlClosed,
        's3ControlClosed'=> $row->s3ControlClosed,
        's4ControlClosed'=> $row->s4ControlClosed,
        'toolsClosed'=> $row->toolsClosed        
      ];
    }
    else {
      $insert = sprintf("INSERT INTO ui_experimentControlHeaders (uid, exptId) VALUES('%s', '%s')", $this->uid, $this->exptId);
      $igrtSqli->query($insert);
      return [
        'operationGroupClosed'=> 1,
        'experimentOverviewClosed'=> 1,
        's1SettingsLabelsClosed'=> 1,
        's2SettingsLabelsClosed'=> 1,
        'iS2SettingsLabelsClosed'=>1 ,
        's4SettingsLabelsClosed'=> 1,
        'surveysClosed'=> 1,
        's1ControlClosed'=> 1,
        's2ControlClosed'=> 1,
        's3ControlClosed'=> 1,
        's4ControlClosed'=> 1,
        'toolsClosed'=> 1
      ];
    }
  }

// </editor-fold>

// <editor-fold defaultstate="collapsed" desc=" helpers for once an experiment has been selected">

	function getExperimentControlHeadersJSON($exptId, $userPermissions, $headerStates) {
		global $igrtSqli;
		$json = "";
		$getHeaderKeys = "SELECT * FROM ui_experimentControlHeaderKeys ORDER BY id ASC";
		$hkResult = $igrtSqli->query($getHeaderKeys);
		if ($hkResult->num_rows > 0) {
			$hkList = [];
			while ($hkRow = $hkResult->fetch_object()) {
				$hkItem = [
					'headerName'=> $hkRow->controlName,
					'index'=> $hkRow->id,
					'label'=> $hkRow->label,
					'controlStatus'=> $headerStates["$hkRow->controlName"],
					'items'=>[]
				];
				$getControlKeys = sprintf("SELECT * FROM ui_experimentControlKeys WHERE experimentControlHeaderKey='%s' ORDER BY id ASC", $hkRow->id);
				$ckResult = $igrtSqli->query($getControlKeys);
				if ($ckResult->num_rows > 0) {
					while ($ckRow = $ckResult->fetch_object()) {
						$canShow = false;
						if ($userPermissions->isSuperUser || $userPermissions->permissions >= 512) { $canShow = true; }
						if (!$canShow) {
							if (in_array($exptId, $userPermissions->userExperimentMappings) || (in_array($exptId, $userPermissions->groupOwnedExperiments)) || (in_array($exptId, $userPermissions->ownedExperiments))) {
								if ($userPermissions->permissions >= $ckRow->userPermissionLevel) { $canShow = true; }
							}
						}
						if ($canShow) {
							$ckItem = [
								'id'=> $ckRow->id,
								'label'=> $ckRow->label,
								'controlName'=> $ckRow->controlName,
								'isSubsection'=> $ckRow->isSubsection,
								'sectionNo'=> $ckRow->sectionNo,
								'pageLabel'=> $ckRow->pageLabel,
								'contingentControl'=> $ckRow->contingentControl
							];
							array_push($hkItem['items'], $ckItem);
						}
					}
				}
				array_push($hkList, $hkItem);
			}
			for ($i=0; $i<count($hkList); $i++) {
				if ($i > 0) { $json.=","; }
				$json.= "{";
				$json.= "\"headerName\":" . JSONparse($hkList[$i]['headerName']) . ",";
				$json.= "\"index\":" . $hkList[$i]['index'] . ",";
				$json.= "\"exptId\":" . $this->exptId . ",";
				$json.= "\"controlHeaderLabel\":" . JSONparse($hkList[$i]['label']) . ",";
				$json.= "\"headerClosed\":" . $hkList[$i]['controlStatus'] . ",";
				$json.= "\"hasItems\":" . (count($hkList[$i]['items']) > 0 ? "true" : "false") . ",";
				$json.= "\"experimentControls\": [";
				for ($j=0; $j<count($hkList[$i]['items']); $j++) {
					if ($hkList[$i]['items'][$j]['contingentControl'] == 1) {
						$visible = $this->isVisible($hkList[$i]['items'][$j]['controlName']);	// TODO isVisible returns true every time - what are we trying to achieve here?
					}
					else {
						$visible = true;
					}
					if ($visible) {
						if ($j > 0) { $json.= ","; }
						$json.= "{";
						$json.= "\"pageLabel\":" . JSONparse($hkList[$i]['items'][$j]['pageLabel']) . ",";
						$json.= "\"isSubsection\":" . $hkList[$i]['items'][$j]['isSubsection'] . ",";
						$json.= "\"exptId\":" . $this->exptId . ",";
						$json.= "\"sectionNo\":" . $hkList[$i]['items'][$j]['sectionNo'] . ",";
						$json.= "\"controlName\":" . JSONparse($hkList[$i]['items'][$j]['controlName']) . ",";
						$json.= "\"experimentControlLabel\":" . JSONparse($hkList[$i]['items'][$j]['label']);
						$json.= "}";
					}
				}
				$json.= "]";
				$json.= "}";
			}
		}
		return $json;
	}

	function makeExperimentJSON($experiment, $messageType = NULL) {
  	// use the userPermissions to put differing levels of access into the Json that goes to the expt configurator
		$userPermissions = $this->getUserPermissions();
		$hasClonePermissions = $this->getClonePermissions($experiment['exptId'], $userPermissions);
		$headersStates = $this->getHeadersStates();
		$json = "{";
		//$json.= "\"isExperiment\": 1,";
		$json.= "\"exptId\":" . $experiment['exptId'] . ",";
		$json.= "\"exptTitle\":" . JSONparse($experiment['exptTitle']) . ",";
		$json.= "\"description\":" . JSONparse($experiment['description']) . ",";
		$json.= "\"isInjected\":" . $experiment['isInjected']. ",";
		$json.= "\"injectedS1\":" . $experiment['injectedS1']. ",";
		$json.= "\"injectedS2\":" . $experiment['injectedS2']. ",";
		$json.= "\"s1srcExpt\":" . JSONparse($experiment['s1srcExpt']) . ",";
		$json.= "\"s2srcExpt\":" . JSONparse($experiment['s2srcExpt']) . ",";
		$json.= "\"s2invertedsrcExpt\":" . JSONparse($experiment['s2invertedsrcExpt']) . ",";
		$json.= "\"isInactive\":" . $experiment['isInactive']. ",";
		$json.= "\"hasClonePermissions\":" . $hasClonePermissions . ",";
		$json.= "\"hasCloneAllPermissions\":" . $hasClonePermissions . ",";
		$json.= "\"hasDeletePermissions\":" . $hasClonePermissions . ",";
		$json.= "\"hasActivatePermissions\":" . $hasClonePermissions . ",";
		$messageTypeValue = isset($messageType) ? $messageType : "messageType not set";
		$json.= "\"messageType\":\"" . $messageTypeValue . "\",";
		$json.= "\"controlName\": \"operationGroupClosed\",";
		$json.= "\"controlClosed\":" . $headersStates['operationGroupClosed']. ",";
		$json.= "\"experimentControlHeaders\": [";
		$json.= $this->getExperimentControlHeadersJSON($experiment['exptId'], $userPermissions, $headersStates);
		$json.= "]";
		$json.= "}";
		return $json;
	}

  function getExperimentControls($exptId, $messageType) {
    global $igrtSqli;
    $sqlqry_exptList=sprintf("SELECT * "
        . "FROM igExperiments as t1 JOIN edContentDefs_refactor as t2 JOIN edExptStatic_refactor as t3 "
        . "WHERE t1.exptId='%s' AND t1.exptId=t2.exptId AND t3.exptId=t2.exptId ORDER BY t1.exptId DESC", 
        $exptId);
    $exptResult=$igrtSqli->query($sqlqry_exptList);
    if ($exptResult->num_rows >0) {
      $row = $exptResult->fetch_object();
      $eModel = $this->getExperimentStatusAndUI($row);
    }    
    $json = "{\"experiments\":[";
    $json.= $this->makeExperimentJSON($eModel, $messageType);
    $json.= "]}";
    return $json;
  }

  function getExperimentStatusAndUI($overview) {
    $exptArray = [];
    $exptArray['isInactive'] = $overview->isInactive;
    $exptArray['exptId'] = $overview->exptId;
    $exptArray['exptTitle'] = $overview->title;
    $exptArray['description'] = $overview->description;
    $exptArray['isInjected'] = $overview->isInjected;
    $exptArray['injectedS1'] = $overview->injectedS1Flag > -1 ? $overview->injectedS1Flag : -1 ;
    $exptArray['injectedS2'] = $overview->injectedS2Flag > -1 ? $overview->injectedS2Flag : -1 ;       
    $exptArray['s1srcExpt'] = $overview->s1srcExptId > -1 ? "$overview->s1srcExptId" : "no source";
    $exptArray['s2srcExpt'] = $overview->s2srcExptId > -1 ? "$overview->s2srcExptId" : "no source";
    $exptArray['s2invertedsrcExpt'] = $overview->s2invertedsrcExptId > -1 ? "$overview->s2invertedsrcExptId" : "no source";
    $exptArray['canClone'] = $overview->canClone;
    return $exptArray;    
  }

	function getClonePermissions($exptId, $userPermissions) {
		if ($userPermissions->permissions == 128) { return "0"; }
		if ($userPermissions->permissions >= 1024) { return "1"; }
		if (in_array($exptId, $userPermissions->ownedExperiments)) { return "1"; }
		if (in_array($exptId, $userPermissions->userExperimentMappings)) {
			// check for permissions - if LO, cannot clone from individual mappings
			if ($userPermissions->permissions >= 256) { return "1"; }
		}
		if (in_array($exptId, $userPermissions->groupOwnedExperiments)) {
			// check for permissions - if LO, cannot clone from group mappings
			if ($userPermissions->permissions >= 256) { return "1"; }
		}
		return "0";
	}

// </editor-fold>
 
// <editor-fold defaultstate="collapsed" desc="  step1 listener helpers">
  
  function isStep1Active($step1SessionControllers, $exptId, $dayNo, $sessionNo) {
    $s1cCnt = count($step1SessionControllers);
    for ($s1cPtr = 0; $s1cPtr < $s1cCnt; $s1cPtr++) {
      if ( ($step1SessionControllers[$s1cPtr]['exptId'] == $exptId) &&
           ($step1SessionControllers[$s1cPtr]['dayNo'] == $dayNo) &&
           ($step1SessionControllers[$s1cPtr]['sessionNo'] == $sessionNo) ) { return $s1cPtr; }
    }
    return -1;
  }

  function getStep1ExperimentListHtml($uid, $permissions, $step1SessionControllers) {
    global $igrtSqli;
    $html="";
    if ($permissions >= 255) {
      $sqlQry_step1List="SELECT * FROM igExperiments WHERE isInactive='0' AND isInjected='0'  ORDER BY exptId DESC";
      //echo $sqlQry_step1List;
    }
    $listResults = $igrtSqli->query($sqlQry_step1List);
    if ($listResults->num_rows > 0) {
      while ($row = $listResults->fetch_object()) {
        $exptId = $row->exptId;
          $html.= sprintf("<div class=\"currentExperiments active\"><h2>%s</h2>", $row->title);
          $html.= "<p>4-8 judges can only use g1 allocations block</p>";
          $html.= "<table><tr><th>day</th><th>session</th><th>desc/time</th><th>done?</th><th>judges per role</th><th>g1 allocations</th><th>g2 allocations</th><th>automated run</th></tr>";
          $sqlDaySession = sprintf("SELECT * FROM edExptStatic_refactor WHERE exptId='%s'", $exptId);
          //echo $sqlDaySession;
          $DaySessionResult = $igrtSqli->query($sqlDaySession);
          if ($DaySessionResult->num_rows > 0) {
            $daySessRow = $DaySessionResult->fetch_object();
            // get dayNo & sessionNo & jCnt for this experiment from edExptStatic_refactor
            $jCnt = $daySessRow->noJudges;
            for ($i=1; $i<=$daySessRow->noDays; $i++) {
              for ($j=1; $j<=$daySessRow->noSessions; $j++) {
                $isActive = $this->isStep1Active($step1SessionControllers, $exptId, $i , $j);               
                $sqlQry_sessions=sprintf("SELECT * FROM edSessions WHERE exptId='%s' AND dayNo='%s' AND sessionNo='%s'", $exptId, $i, $j);
                $sessionResult=$igrtSqli->query($sqlQry_sessions);
                if ($sessionResult->num_rows > 0) {
                  $sessionRow=$sessionResult->fetch_object();
                  $html.="<tr>";
                  $html.=sprintf("<td>%s</td><td>%s</td><td>%s</td>", $i, $j, $sessionRow->time);
                  if ($sessionRow->step1Complete == 1) {
                    $html.= sprintf("<td>yes</td><td>%s</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td>", $sessionRow->iActualCnt);
                  }
                  else {
                    if ($isActive > -1) {
                      $html.= "<td>ACTIVE</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td>";     
                    }
                    else {
                      $html.= "<td>no</td>";
                      $selectId = sprintf("jNo%sD%sS%s", $exptId, $i, $j);
                      $selectValue = $jCnt ; // number defined in expt def is full-cohort
                      $idPairList=[];
                      for ($k=4;$k<65;$k++) {
                        $idPair=array('id'=>$k,'label'=>$k);
                        array_push($idPairList,$idPair);
                      } 
                      $html.= "<td>".$this->htmlBuilder->makeSelect($selectId,"","number",true, $idPairList, $this->_tabIndex++, $selectValue)."</td>";
                      $buttonId = sprintf("initB_%s_%s_%s_1_0", $exptId, $i, $j);  // 1_0 = allocation generation 1 and normal validation
                      $html.= "<td>".$this->htmlBuilder->makeButton($buttonId, "init g1", "button", '', $this->_tabIndex++)."</td>";
                      $buttonId = sprintf("initB_%s_%s_%s_2_0", $exptId, $i, $j);  // 2_0 = allocation generation 2 and normal validation
                      $html.= "<td>".$this->htmlBuilder->makeButton($buttonId, "init g2", "button", '', $this->_tabIndex++)."</td>";
                      $buttonId = sprintf("initB_%s_%s_%s_1_1", $exptId, $i, $j);  // 1_0 = allocation generation 1 and no validation (for use with scripts)
                      $html.= "<td>".$this->htmlBuilder->makeButton($buttonId, "run scripts", "button", '', $this->_tabIndex++)."</td>";
                    }
                  }                            
                  $html.="</tr>";               
                }
              }  
            }          
          }
          $html.="</table></div>";
      }
    }
    return $html;
  }

// </editor-fold>
 
// <editor-fold defaultstate="collapsed" desc=" expt config wizard - step1 logins stage">

  function markExptAsReadyStep1($exptId) {
    $eModel = new experimentModel($exptId);
    $eModel->markReadyStep1();      
  }
   
// </editor-fold>
  
// <editor-fold defaultstate="collapsed" desc=" expt config wizard - experiment summary and global settings">
      
  function addLocation($exptId, $newLocation) {
    global $igrtSqli;
    // see if it exists
    $sqlQry_locationExists=sprintf("SELECT * FROM igLocations WHERE label='%s'",$newLocation);
    $locExistsResult=$igrtSqli->query($sqlQry_locationExists);
    if ($locExistsResult->num_rows > 0) {
        // find a way to alert the user that their location already exists?
    }
    else {
      $sqlCmd_addLocation=sprintf("INSERT INTO igLocations (label) VALUES(\"%s\")",$newLocation);
      $igrtSqli->query($sqlCmd_addLocation);           
      $locId = $igrtSqli->insert_id;
      $sqlSetLocation = sprintf("UPDATE edExptStatic_refactor SET location='%s' WHERE exptId='%s'", $locId, $exptId);
      $igrtSqli->query($sqlSetLocation);
    }
  }

  function addSubject($exptId, $newSubject) {
    global $igrtSqli;
    // see if it exists
    $sqlSubjectExists = sprintf("SELECT * FROM igProfileAttributes WHERE label='%s'", $newSubject);
    $subExistsResult = $igrtSqli->query($sqlSubjectExists);
    if ($subExistsResult->num_rows > 0) {
        // find a way to alert the user that the subject already exists?
    }
    else {
      $tblName = preg_replace('/\s+/', '', $newSubject);
      $sqlAddSubject = sprintf("INSERT INTO igProfileAttributes (label, tblName) VALUES('%s', '%s')", $newSubject, $tblName);
      $igrtSqli->query($sqlAddSubject);
      //echo $sqlAddSubject;
      $subId = $igrtSqli->insert_id;
      $sqlSetSubject = sprintf("UPDATE edExptStatic_refactor SET exptSubject='%s' WHERE exptId='%s'", $subId, $exptId);
      $igrtSqli->query($sqlSetSubject);
    }
  }
        
  function nextStage($uid, $exptId) {
    $xml = $this->getFormsStructure($uid, $exptId);
    return $xml;
  }
     
  function stepUpdateFieldValue($exptId, $cId, $cValue) {
    global $igrtSqli;
    $updateSql = sprintf("UPDATE edRespContent SET %s='%s' WHERE exptId='%s'", $cId, $igrtSqli->real_escape_string($cValue), $exptId);
    $igrtSqli->query($updateSql);
    return "<message><messageType>NOOP</messageType></message>"; // Non operational message
  }
  
  function getStep2FormsHtml($eModel) {
    $html ="<div class=\"currentExperiments\"><br /><h2 class=\"closed\">Forms</h2>";
    $html.= "<div class=\"adminSection\" id=\"s2forms\" style=\"display : none\">";
    $html.= "<p>Note: a separate consent form can be used in conjunction with a recruitment form, or can be defined as a field in a pre-Step2 form if used. </p>";
      $html.= "<div class=\"formRow light \">";
        $html.=$this->htmlBuilder->makeCheckBox("step2ConsentForm", $eModel->step2ConsentForm, "checkboxButton", "checkbox", "#", "#", "Use consent form", true, $this->_tabIndex++);         
        $html.= $this->htmlBuilder->makeALink("config_step2ConsentForm", "configure", true);      
        $html.= $this->htmlBuilder->makeALink("clone_step2ConsentForm", "clone", $eModel->step2ConsentFormReady);      
        $previewLink = sprintf("/sf_%s_4_0_0",$eModel->exptId);
        $html.= $this->htmlBuilder->makeALink($previewLink, "preview", $eModel->step2ConsentFormReady, $previewLink);      
      $html.="</div>"; 
      $html.= "<div class=\"formRow dark \">";
        $html.=$this->htmlBuilder->makeCheckBox("step2RecruitForm", $eModel->step2RecruitForm, "checkboxButton", "checkbox", "#", "#", "Use recruitment form", true, $this->_tabIndex++);         
        $html.= $this->htmlBuilder->makeALink("config_step2RecruitForm", "configure", true);        
        $html.= $this->htmlBuilder->makeALink("clone_step2RecruitForm", "clone", $eModel->step2RecruitFormReady);
        $previewLink = sprintf("/sf_%s_5_0_0",$eModel->exptId);
        $html.= $this->htmlBuilder->makeALink($previewLink, "preview", $eModel->step2RecruitFormReady, $previewLink);      
      $html.="</div>"; 
      $html.= "<div class=\"formRow light \">";
        $html.=$this->htmlBuilder->makeCheckBox("step2PreForm", $eModel->step2PreForm, "checkboxButton", "checkbox", "#", "#", "Use pre-Step2 form", true, $this->_tabIndex++);         
        $html.= $this->htmlBuilder->makeALink("config_step2PreForm", "configure", true);      
        $html.= $this->htmlBuilder->makeALink("clone_step2PreForm", "clone", $eModel->step2PreFormReady);      
        $previewLink = sprintf("/sf_%s_6_0_0",$eModel->exptId);
        $html.= $this->htmlBuilder->makeALink($previewLink, "preview", $eModel->step2PreFormReady, $previewLink);      
      $html.="</div>"; 
      $html.= "<div class=\"formRow dark \">";
        $html.=$this->htmlBuilder->makeCheckBox("step2PostForm", $eModel->step2PostForm, "checkboxButton", "checkbox", "#", "#", "Use post-Step2 form", true, $this->_tabIndex++);         
        $html.= $this->htmlBuilder->makeALink("config_step2PostForm", "configure", true);      
        $html.= $this->htmlBuilder->makeALink("clone_step2PostForm", "clone", $eModel->step2PostFormReady);      
        $previewLink = sprintf("/sf_%s_7_0_0",$eModel->exptId);
        $html.= $this->htmlBuilder->makeALink($previewLink, "preview", $eModel->step2PostFormReady, $previewLink);      
      $html.="</div>"; 
      $html.= "<div class=\"formRow light \">";
        $html.=$this->htmlBuilder->makeCheckBox("step2PreInvert", $eModel->step2PreInvert, "checkboxButton", "checkbox", "#", "#", "Use pre- Inverted Step2 form", true, $this->_tabIndex++);         
        $html.= $this->htmlBuilder->makeALink("config_step2PreInvert", "configure", true);      
        $html.= $this->htmlBuilder->makeALink("clone_step2PreInvert", "clone", $eModel->step2PreInvertReady);      
        $previewLink = sprintf("/sf_%s_12_0_0",$eModel->exptId);
        $html.= $this->htmlBuilder->makeALink($previewLink, "preview", $eModel->step2PreInvertReady, $previewLink);      
      $html.="</div>"; 
      $html.= "<div class=\"formRow dark \">";
        $html.=$this->htmlBuilder->makeCheckBox("step2PostInvert", $eModel->step2PostInvert, "checkboxButton", "checkbox", "#", "#", "Use post- Inverted Step2 form", true, $this->_tabIndex++);         
        $html.= $this->htmlBuilder->makeALink("config_step2PostInvert", "configure", true);      
        $html.= $this->htmlBuilder->makeALink("clone_step2PostInvert", "clone", $eModel->step2PostInvertReady);      
        $previewLink = sprintf("/sf_%s_13_0_0",$eModel->exptId);
        $html.= $this->htmlBuilder->makeALink($previewLink, "preview", $eModel->step2PostInvertReady, $previewLink);      
      $html.="</div>"; 
    $html.= "</div>";
    $html.="</div>";
    return $html;
  }

  function getStep4FormsHtml($eModel) {
    $html ="<div class=\"currentExperiments\"><br /><h2 class=\"closed\">Forms</h2>";
    $html.= "<div class=\"adminSection\" id=\"s4forms\" style=\"display : none\">";
    $html.= "<p>Note: a separate consent form can be used in conjunction with a recruitment form, or can be defined as a field in a pre-Step4 form if used. </p>";
      $html.= "<div class=\"formRow light \">";
        $html.=$this->htmlBuilder->makeCheckBox("step4ConsentForm", $eModel->step4ConsentForm, "checkboxButton", "checkbox", "#", "#", "Use consent form", true, $this->_tabIndex++);         
        $html.= $this->htmlBuilder->makeALink("config_step4ConsentForm", "configure", true);      
        $html.= $this->htmlBuilder->makeALink("clone_step4ConsentForm", "clone", $eModel->step4ConsentFormReady);      
        $previewLink = sprintf("/sf_%s_8_0_0",$eModel->exptId);
        $html.= $this->htmlBuilder->makeALink($previewLink, "preview", $eModel->step4ConsentFormReady, $previewLink);      
      $html.="</div>"; 
       $html.= "<div class=\"formRow dark \">";
        $html.=$this->htmlBuilder->makeCheckBox("step4RecruitForm", $eModel->step4RecruitForm, "checkboxButton", "checkbox", "#", "#", "Use recruitment form", true, $this->_tabIndex++);         
        $html.= $this->htmlBuilder->makeALink("config_step4RecruitForm", "configure", true);      
        $html.= $this->htmlBuilder->makeALink("clone_step4RecruitForm", "clone", $eModel->step4RecruitFormReady);      
        $previewLink = sprintf("/sf_%s_9_0_0",$eModel->exptId);
        $html.= $this->htmlBuilder->makeALink($previewLink, "preview", $eModel->step4RecruitFormReady, $previewLink);      
      $html.="</div>"; 
      $html.= "<div class=\"formRow light \">";
        $html.=$this->htmlBuilder->makeCheckBox("step4PreForm", $eModel->step4PreForm, "checkboxButton", "checkbox", "#", "#", "Use pre-Step4 form", true, $this->_tabIndex++);         
        $html.= $this->htmlBuilder->makeALink("config_step4PreForm", "configure", true);      
        $html.= $this->htmlBuilder->makeALink("clone_step4PreForm", "clone", $eModel->step4PreFormReady);      
        $previewLink = sprintf("/sf_%s_9_0_0",$eModel->exptId);
        $html.= $this->htmlBuilder->makeALink($previewLink, "preview", $eModel->step4PreFormReady, $previewLink);      
      $html.="</div>"; 
      $html.= "<div class=\"formRow dark \">";
        $html.=$this->htmlBuilder->makeCheckBox("step4PostForm", $eModel->step4PostForm, "checkboxButton", "checkbox", "#", "#", "Use post-Step4 form", true, $this->_tabIndex++);         
        $html.= $this->htmlBuilder->makeALink("config_step4PostForm", "configure");      
        $html.= $this->htmlBuilder->makeALink("clone_step4PostForm", "clone", $eModel->step4PostFormReady);      
        $previewLink = sprintf("/sf_%s_10_0_0",$eModel->exptId);
        $html.= $this->htmlBuilder->makeALink($previewLink, "preview", $eModel->step4PostFormReady, $previewLink);      
      $html.="</div>"; 
    $html.= "</div>";
    $html.="</div>";
    return $html;
  }
    
  function getStep1FormsHtml($eModel) {
    $html ="<div class=\"currentExperiments\"><br /><h2 class=\"open\">Step1 Forms</h2>";
    $html.= "<div class=\"adminSection\" id=\"s1forms\" style=\"display : block\">";
    $html.= "<p>Note: a separate consent form can be used in conjunction with a recrutiment form, or can be defined as a field in a pre-Step1 form if used. </p>";
      $html.= "<div class=\"formRow light \">";
        $html.=$this->htmlBuilder->makeCheckBox("step1ConsentForm", $eModel->step1ConsentForm, "checkboxButton", "checkbox", "#", "#", "Use consent form", true, $this->_tabIndex++);         
        $html.= $this->htmlBuilder->makeALink("config_step1ConsentForm", "configure", true);      
        $html.= $this->htmlBuilder->makeALink("clone_step1ConsentForm", "clone", $eModel->step1ConsentFormReady);      
        $previewLink = sprintf("/sf_%s_0_0_0",$eModel->exptId);
        $html.= $this->htmlBuilder->makeALink($previewLink, "preview", $eModel->step1ConsentFormReady, $previewLink);      
      $html.="</div>"; 
      $html.= "<div class=\"formRow dark \">";
        $html.=$this->htmlBuilder->makeCheckBox("step1RecruitForm", $eModel->step1RecruitForm, "checkboxButton", "checkbox", "#", "#", "Use recruitment form", true, $this->_tabIndex++);         
        $html.= $this->htmlBuilder->makeALink("config_step1RecruitForm", "configure", true);      
        $html.= $this->htmlBuilder->makeALink("clone_step1RecruitForm", "clone", $eModel->step1RecruitFormReady);      
        $previewLink = sprintf("/sf_%s_1_0_0",$eModel->exptId);
        $html.= $this->htmlBuilder->makeALink($previewLink, "preview", $eModel->step1RecruitFormReady, $previewLink);      
      $html.="</div>"; 
      $html.= "<div class=\"formRow light \">";
        $html.=$this->htmlBuilder->makeCheckBox("step1PreForm", $eModel->step1PreForm, "checkboxButton", "checkbox", "#", "#", "Use pre-Step1 form", true, $this->_tabIndex++);         
        $html.= $this->htmlBuilder->makeALink("config_step1PreForm", "configure", true);      
        $html.= $this->htmlBuilder->makeALink("clone_step1PreForm", "clone", $eModel->step1PreFormReady);      
        $previewLink = sprintf("/sf_%s_3_0_0",$eModel->exptId);
        $html.= $this->htmlBuilder->makeALink($previewLink, "preview", $eModel->step1PreFormReady, $previewLink);      
      $html.="</div>"; 
      $html.= "<div class=\"formRow dark \">";
        $html.=$this->htmlBuilder->makeCheckBox("step1PostForm", $eModel->step1PostForm, "checkboxButton", "checkbox", "#", "#", "Use post-Step1 form", true, $this->_tabIndex++);         
        $html.= $this->htmlBuilder->makeALink("config_step1PostForm", "configure", true);      
        $html.= $this->htmlBuilder->makeALink("clone_step1PostForm", "clone", $eModel->step1PostFormReady);      
        $previewLink = sprintf("/sf_%s_4_0_0",$eModel->exptId);
        $html.= $this->htmlBuilder->makeALink($previewLink, "preview", $eModel->step1PostFormReady, $previewLink);      
      $html.="</div>"; 
    $html.= "</div>";
    $html.="</div>";
    $xml=sprintf("<message><messageType>step1Forms</messageType>
      <step>Step1</step><exptTitle>%s</exptTitle><step1FormsDetail><![CDATA[%s]]></step1FormsDetail></message>",
      $eModel->title, $html);
    return $xml;
  }
  
  function toggleUseForm($exptId, $formName, $uValue) {
    global $igrtSqli;
    $setValue = ($uValue == 'true')? 1 : 0;
    $uSql = sprintf("UPDATE edExptStatic_refactor SET %s='%s' WHERE exptId='%s'", $formName, $setValue, $exptId);
    $igrtSqli->query($uSql);    
    return "<message><messageType>blank</messageType><content>0</content></message>"; 
  }

  function sendInConfigurationStep1Details($eModel, $cType, $cId) {
    // send back different message type once editing experiment details
    $title = $eModel->title;
    // build the form html to send back to client
    // get admin section html  
    $html = $this->getAdminSectionHtml($eModel);
    $html.= $this->getSessionSectionHtml($eModel);
    $html.= $this->getRatingsSectionHtml($eModel); 
    $html.= $this->getFinalRatingsSectionHtml($eModel);
    $eModel->saveConfigToDb();
    $xml=sprintf("<message><messageType>editedStep1Detail</messageType>
                  <stage>%s</stage><cType>%s</cType><focusId>%s</focusId>
                  <exptTitle>%s</exptTitle><exptDetail><![CDATA[%s]]></exptDetail></message>",
                  "step1", $cType, $cId, $title, $html);
    return $xml;
  }   
    
// </editor-fold>
 
// <editor-fold defaultstate="collapsed" desc=" experiment section helpers">
  
  function makeInterrogatorsJSON($daysArray) {
    $json = "{";
    $json.= "\"days\":[";
    for ($i=0; $i<count($daysArray); $i++) {
      $day = $daysArray[$i];
      if ($i>0) { $json.= ','; }
      $json.= "{";
        $json.= "\"dayLabel\":\"Day ".($i+1)."\","; 
        $json.= "\"sessions\":[";
        for ($j=0; $j<count($day['sessions']); $j++) {
          $session = $day['sessions'][$j];
          if ($j>0) { $json.=","; }
          $json.= "{";
            $json.= "\"sessionLabel\":\"Session ".($j+1)."\","; 
            $json.= "\"users\":[";
            if ($this->eModel->isClassic == 1) {
              for ($k=0; $k<count($session['users']); $k++) {
                if ($k>0) { $json.=","; }
                $json.= "{";
                  $json.= "\"isClassic\":\"" . $this->eModel->isClassic  . "\",";
                  $json.= "\"login\":\"".$session['users'][$k]['login']."\",";
                  $json.= "\"role\":\"".$session['users'][$k]['role']."\",";
                  $json.= "\"pw\":\"". $session['users'][$k]['pw']. "\",";
                  $json.= "\"oddPlayerLabel\":\"p\",";
                  $json.= "\"evenPlayerLabel\":\"p\",";
                  $json.= "\"oddPlayerLogin\":\"p\",";
                  $json.= "\"oddPlayerPassword\":\"p\",";
                  $json.= "\"evenPlayerLogin\":\"p\",";
                  $json.= "\"evenPlayerPassword\":\"p\"";
                $json.= "}";
              }              
            }
            else {
              for ($k=0; $k<count($session['users']); $k++) {
                if ($k>0) { $json.=","; }
                $json.= "{";
                  $json.= "\"isClassic\":\"" . $this->eModel->isClassic  . "\",";
                  $json.= "\"login\":\"p\",";
                  $json.= "\"role\":\"p\",";
                  $json.= "\"pw\":\"p\",";
                  $json.= "\"oddPlayerLabel\":\"p".($k*2 + 1)."\",";
                  $json.= "\"evenPlayerLabel\":\"p".(($k+1)*2)."\",";
                  $json.= "\"oddPlayerLogin\":\"".$session['users'][$k]['oddLogin']."\",";
                  $json.= "\"oddPlayerPassword\":\"".$session['users'][$k]['oddPassword']."\",";
                  $json.= "\"evenPlayerLogin\":\"".$session['users'][$k]['evenLogin']."\",";
                  $json.= "\"evenPlayerPassword\":\"".$session['users'][$k]['evenPassword']."\"";
                $json.= "}";
              }              
            }
          $json.= "]}"; // close users
        }
      $json.= "]}"; // close sessions
    }
    $json.= "]}"; //close days
    return $json;
  }
  
  function getLogin($uid) {
    global $igrtSqli;
    $uidQry = sprintf("SELECT email FROM igUsers WHERE id='%s'", $uid);
    $uidResult = $igrtSqli->query($uidQry);
    if ($uidResult->num_rows>0) {
      $uidRow = $uidResult->fetch_object();
      return $uidRow->email;
    }
    return 'error getting login';
  }
  
  function getFormDataCount($dataTableName, $formType) {
    global $igrtSqli;
    $qry = sprintf("SELECT * FROM %s WHERE formType = '%s'", $dataTableName, $formType);
    $result = $igrtSqli->query($qry);
    return $result ? $result->num_rows : 0;
  }
  
  function getDataSummary() {
    global $igrtSqli;
    $ds = [
      'hasData' => false,
      'step1PreForm' => 0,
      'step1PostForm' => 0,
      'step2PreForm' => 0,
      'step2PostForm' => 0,
      'step2PreInvert' => 0,
      'step2PostInvert' => 0,
      'step4PreForm' => 0,
      'step4PostForm' => 0      
    ];
    $dataTableName = "zz_json_" . $this->exptId;
    $dataExistsQry = sprintf("SELECT count((1)) as ct  FROM INFORMATION_SCHEMA.TABLES where table_schema ='igrt' and table_name='%s'", $dataTableName); 
    $dataExistsResult = $igrtSqli->query($dataExistsQry);
    if ($dataExistsResult->num_rows >0) {
      $dataExistsRow = $dataExistsResult->fetch_object();
      if ($dataExistsRow->ct > 0) {
        $ds['hasData'] = true;
        // get info for each form/survey
        $ds['step1PreForm'] = $this->getFormDataCount($dataTableName, 2);
        $ds['step1PostForm'] = $this->getFormDataCount($dataTableName, 3);
        $ds['step2PreForm'] = $this->getFormDataCount($dataTableName, 6);
        $ds['step2PostForm'] = $this->getFormDataCount($dataTableName, 7);
        $ds['step2PreInvert'] = $this->getFormDataCount($dataTableName, 12);
        $ds['step2PostInvert'] = $this->getFormDataCount($dataTableName, 13);
        $ds['step4PreForm'] = $this->getFormDataCount($dataTableName, 10);
        $ds['step4PostForm'] = $this->getFormDataCount($dataTableName, 11);
      }
    }
    return $ds;
  }
  
  function getStepCategories($step, &$noCategories) {
    global $igrtSqli;
    $retArray = [];
    $categoriesQry = sprintf("SELECT * FROM edAlignmentControlLabels WHERE exptId='%s' "
        . "AND step='%s' ORDER BY displayOrder ASC", $this->exptId, $step);
    $categoriesResult = $igrtSqli->query($categoriesQry);
    $noCategoriesCheck = $categoriesResult ? $categoriesResult->num_rows : 0;
    if ($noCategoriesCheck == 0) {
      // create default items
      if ($noCategories == 0) { $noCategories = 3; }  // use sensible default
      for ($i=0; $i<$noCategories; $i++) {
        $labelValue = $i+1;
        $labelValue = "category".$labelValue;
        $insertQry = sprintf("INSERT INTO edAlignmentControlLabels (exptId,step,label,displayOrder)"
            . " VALUES('%s','%s','%s','%s')", $this->exptId, $step, $labelValue, $i);
        $igrtSqli->query($insertQry);
      }
    }
    else {
      if ($noCategoriesCheck != $noCategories) { $noCategories = $noCategoriesCheck; }
    }
    while ($categoriesRow = $categoriesResult->fetch_object()) {
      $subItemFieldValues = [
        'textValue'=> $categoriesRow->Label,
        'subItemLabel'=> "category # ".($categoriesRow->displayOrder + 1),
        'dimension1'=> $categoriesRow->displayOrder,
        'dimension2'=> 'unset'
      ];
      array_push($retArray, $subItemFieldValues);
    }  
    return $retArray;
  }

  function makeStepFormTypesJSON(&$index) {
		$json = '';
	  $json.= $this->makeFormFieldJSON($index, "pageWarning", "", "", "pre-Step1 form code = 2", "", "", "",  "", "", "[]", false, [], [], "", "", "") . ",";
	  $json.= $this->makeFormFieldJSON($index, "pageWarning", "", "", "post-Step1 form code = 3", "", "", "",  "", "", "[]", false, [], [], "", "", "") . ",";
	  $json.= $this->makeFormFieldJSON($index, "pageWarning", "", "", "pre-Step2 form code = 6", "", "", "",  "", "", "[]", false, [], [], "", "", "") . ",";
	  $json.= $this->makeFormFieldJSON($index, "pageWarning", "", "", "post-Step2 form code = 7", "", "", "",  "", "", "[]", false, [], [], "", "", "") . ",";
	  $json.= $this->makeFormFieldJSON($index, "pageWarning", "", "", "pre-inverted Step2 form code = 12", "", "", "",  "", "", "[]", false, [], [], "", "", "") . ",";
	  $json.= $this->makeFormFieldJSON($index, "pageWarning", "", "", "post-inverted Step2 form code = 13", "", "", "",  "", "", "[]", false, [], [], "", "", "") . ",";
	  $json.= $this->makeFormFieldJSON($index, "pageWarning", "", "", "pre-Step4 form code = 10", "", "", "",  "", "", "[]", false, [], [], "", "", "") . ",";
	  $json.= $this->makeFormFieldJSON($index, "pageWarning", "", "", "post-Step4 form code = 11", "", "", "",  "", "", "[]", false, [], [], "", "", "") . ",";
		return $json;
  }

  function makeFormFieldJSON(&$index, $controlType, $tblName, $tblFieldName, $legend, 
      $booleanValue, $itemValue, $selectedItem, $dataOnText, $dataOffText, $optionsJSON, 
      $isSubItem,  $subItemFields, $subItemFieldValues, $buttonTarget, $buttonLegend, $formType) {
    $json = "{";
      $json.= "\"index\":".$index.",";
      $json.= "\"controlType\":\"".$controlType."\",";
      $json.= "\"isSubItem\":" . ($isSubItem ? "true" : "false") . ",";
      $json.= "\"tblName\":\"".$tblName."\",";
      $json.= "\"tblFieldName\":\"".$tblFieldName."\",";
      $json.= "\"legend\":".JSONparse($legend).",";
      $booleanStr = $booleanValue > "" ? $booleanValue : "\"\"";
      $json.= "\"booleanValue\":".$booleanStr. ",";
      $json.= "\"RawBooleanValue\":".$booleanStr . ",";
      $json.= "\"itemValue\":".JSONparse($itemValue).",";
      $json.= "\"selectedItem\":\"".$selectedItem."\",";
      $json.= "\"prevSelectedItem\":\"".$selectedItem."\",";
      $json.= "\"dataOffText\":\"".$dataOffText."\",";
      $json.= "\"dataOnText\":\"".$dataOnText."\",";
      $json.= "\"options\":".$optionsJSON.",";
      $dim1Name = $isSubItem ? $subItemFields['dimension1Name'] : "";
      $dim2Name = $isSubItem ? $subItemFields['dimension2Name'] : "";
      $dim1Value = $isSubItem ? $subItemFieldValues['dimension1'] : "";
      $dim2Value = $isSubItem ? $subItemFieldValues['dimension2'] : "";
      $json.= "\"dimension1Name\":\"".$dim1Name."\",";
      $json.= "\"dimension2Name\":\"".$dim2Name."\",";
      $json.= "\"dimension1Value\":\"".$dim1Value."\",";
      $json.= "\"dimension2Value\":\"".$dim2Value."\",";
      $json.= "\"buttonTarget\":\"".$buttonTarget."\",";
      $json.= "\"buttonLegend\":\"".$buttonLegend."\",";
      $isFormSelector = $formType > "" ? 1 : 0;
      $json.= "\"isFormSelector\":".$isFormSelector.",";
      $json.= "\"formType\":".JSONparse($formType);      
    $json.= "}";
    ++$index;
    return $json;
  }
    
  function getExptSummary() {
    $subjectsJSON = $this->metadataConverter->getJSONArray("subject");
    $countriesJSON = $this->metadataConverter->getJSONArray("country");
    $languagesJSON = $this->metadataConverter->getJSONArray("language");
    $locationsJSON = $this->metadataConverter->getJSONArray("location");
    $description = $this->eModel->description;
    $oddS1Label = $this->eModel->oddS1Label;
    $evenS1Label = $this->eModel->evenS1Label;
    $subject = $this->metadataConverter->getLabelFromId("subject", $this->eModel->exptSubject);
    $country = $this->metadataConverter->getLabelFromId("country", $this->eModel->country);
    $language = $this->metadataConverter->getLabelFromId("language", $this->eModel->language);
    $location = $this->metadataConverter->getLabelFromId("location", $this->eModel->location);
    $index = 0;
    $json = "{\"sectionTitle\": \"Experiment summary\",\"sectionName\": \"summary\",\"exptId\":".$this->exptId.",\"formFields\":[";
    $json.= $this->makeFormFieldJSON($index, "text", "edContentDefs_refactor", "description", "description", "", $description, "", "", "", "[]", false, [], [], "", "", "", "") . ",";
	  $json.= $this->makeFormFieldJSON($index, "checkbox", "edExptStatic_refactor", "isClassic", "3P classic", $this->eModel->isClassic, "", "", "yes", "no", "[]", false, [], [], "", "", "", "") . ",";
	  $json.= $this->makeFormFieldJSON($index, "text", "edContentDefs_refactor", "oddS1Label", "odd S1 label ", "", $oddS1Label, "", "", "", "[]", false, [], [], "", "", "", "") . ",";
    $json.= $this->makeFormFieldJSON($index, "text", "edContentDefs_refactor", "evenS1Label", "even S1 Label" ,"", $evenS1Label, "", "", "", "[]", false, [], [], "", "", "", "") . ",";
    $json.= $this->makeFormFieldJSON($index, "select", "edExptStatic_refactor", "exptSubject", "subject", "", "", $subject, "", "", $subjectsJSON, false, [], [], "", "", "", "") . ",";
    $json.= $this->makeFormFieldJSON($index, "select", "edExptStatic_refactor", "country", "country", "", "", $country, "", "", $countriesJSON, false, [], [], "", "", "", "") . ",";
    $json.= $this->makeFormFieldJSON($index, "select", "edExptStatic_refactor", "language", "language", "", "", $language, "", "", $languagesJSON, false, [], [], "", "", "", "") . ",";
    $json.= $this->makeFormFieldJSON($index, "select", "edExptStatic_refactor", "location", "location", "", "", $location, "", "", $locationsJSON, false, [], [], "", "", "", "");
    $json.= "]}";
    return $json;
  } 
  
  function getStep1Sessions() {
    global $igrtSqli;
    $judgeSelectJSON = $this->metadataConverter->getSelectJSONArray(4,128);
    $daySelectJSON = $this->metadataConverter->getSelectJSONArray(1,10);
    $sessionSelectJSON = $this->metadataConverter->getSelectJSONArray(1,10);
    $qcJSON = $this->metadataConverter->getSelectJSONArray(1,10);
		$characterLimitSelectJSON = $this->metadataConverter->getSpacedSelectJSONArray(5000);

    $json = "{\"sectionTitle\": \"step 1 sessions\",\"sectionName\": \"s1sessionsusers\",\"exptId\":".$this->exptId.",\"formFields\":[";
      $pageWarning = "warning: changing values in this page and then clicking 'save and operate on changes' in the navigation footer will regenerate the step1 users. "
          . "Note also, the type of step1 users generated will depend on the `classic 3-P` setting in the experiment overview section. "
          . "You MUST view the generated users after any regeneration to be able to keep them or pass them to a Local Organiser.";
      $s1usersSetMessage = $this->eModel->s1usersSet == 1 ? "(step1 users have already been generated)" : "(no step1 users have yet been generated)";
      $index = 0; // incremented in makeXXXJSON methods
      $json.= $this->makeFormFieldJSON($index, "pageWarning", "", "", $pageWarning, "", "", "",  "", "", "[]", false, [], [], "", "", "") . ",";
      //$json.= $this->makeFormFieldJSON($index, "checkbox", "edExptStatic_refactor", "isClassic", "3P classic", $this->eModel->isClassic, "", "", "yes", "no", "[]", false, [], [], "", "", "", "") . ",";
		$json.= $this->makeFormFieldJSON($index, "checkbox", "edExptStatic_refactor", "useS1MinQuestionLimit", "use min question-length", $this->eModel->useS1MinQuestionLimit, "", "", "yes", "no", "[]", false, [], [], "", "", "", "") . ",";
		$json.= $this->makeFormFieldJSON($index, "select", "edExptStatic_refactor", "s1MinQuestionLimit", "min question-length", "", "", $this->eModel->s1MinQuestionLimit, "", "", $characterLimitSelectJSON, false, [], [], "", "", "", "") . ",";
		$json.= $this->makeFormFieldJSON($index, "text", "edContentDefs_refactor", "s1QuestionMinLabel", "min question-length guidance", "", $this->eModel->s1QuestionMinLabel, "", "", "", "[]", false, [], [], "", "", "", "") . ",";
		$json.= $this->makeFormFieldJSON($index, "checkbox", "edExptStatic_refactor", "randomiseSideS1", "randomise S1 NP side", $this->eModel->randomiseSideS1, "", "", "yes", "no", "[]", false, [], [], "", "", "", "") . ",";
      $json.= $this->makeFormFieldJSON($index, "checkbox", "edExptStatic_refactor", "s1barbilliardControl", "experimenter can force end", $this->eModel->s1barbilliardControl, "", "", "yes", "no", "[]", false, [], [], "", "", "", "") . ",";
      $json.= $this->makeFormFieldJSON($index, "select", "edExptStatic_refactor", "s1QuestionCountAlternative", "min # of turns if not forced end", "", "", $this->eModel->s1QuestionCountAlternative, "", "", $qcJSON, false, [], [], "", "", "", "") . ",";
      $json.= $this->makeFormFieldJSON($index, "select", "edExptStatic_refactor", "noJudges", "# of odd or even interrogators per session", "", "", $this->eModel->noJudges, "", "", $judgeSelectJSON, false, [], [], "", "", "", "") . ",";
      $json.= $this->makeFormFieldJSON($index, "select", "edExptStatic_refactor", "noDays", "# of days", "", "", $this->eModel->noDays, "", "", $daySelectJSON, false, [], [], "", "", "", "") . ",";
      $json.= $this->makeFormFieldJSON($index, "select", "edExptStatic_refactor", "noSessions", "# of sessions per day", "", "", $this->eModel->noSessions, "", "", $sessionSelectJSON, false, [], [], "", "", "", "") . ",";
      $subItemFields = array(
        'dimension1Name' => 'dayNo',
        'dimension2Name' => 'sessionNo'
      );
      $dsSql = sprintf("SELECT * FROM edSessions WHERE exptId='%s' ORDER By dayNo ASC, sessionNo ASC", $this->exptId);
      $dsResult = $igrtSqli->query($dsSql);
      $subItems = [];
      if ($dsResult->num_rows > 0) {
        while ($dsRow = $dsResult->fetch_object()) {
          $subItemFieldValues = array(
            'textValue' => $dsRow->time,
            'subItemLabel' => "Day ".$dsRow->dayNo." - Session ".$dsRow->sessionNo,
            'dimension1'=>$dsRow->dayNo,
            'dimension2'=>$dsRow->sessionNo
          );
          array_push($subItems, $subItemFieldValues);
        }
      }
      for ($i=0; $i<count($subItems); $i++) {
        $subItemLabel = $subItems[$i]['subItemLabel'];
        $textValue = $subItems[$i]['textValue'];
        $json.= $this->makeFormFieldJSON($index, "text", "edSessions", "time", $subItemLabel, "", $textValue, "", "", "", "[]", true, $subItemFields, $subItems[$i], "", "", "", "").",";
      }
      $json.= $this->makeFormFieldJSON($index, "button", "", "", $s1usersSetMessage, "", "", "", "", "", "[]", false, [], [], "1_3_0", "view users", "", "");
    $json.= "]}";
    return $json;    
  }
  
  function getStep1InterrogatorRating() {
    global $igrtSqli;
    $noSliderPoints = $this->eModel->noLikert;
    $sliderLabelsQry = sprintf("SELECT * FROM edLabels WHERE exptId='%s' AND whichLikert=0 ORDER BY confidenceValue ASC", $this->exptId);
    $sliderLabelsResult = $igrtSqli->query($sliderLabelsQry);
    $noSliderCheck = $sliderLabelsResult ? $sliderLabelsResult->num_rows : 0;
    if ($noSliderCheck == 0) {
      // houston, so create 4 defaults
      $insertSql = sprintf("INSERT INTO edLabels (exptId, whichLikert, label, confidenceValue) "
          . "VALUES ('%s', 0, 'I am pretty unsure', 1),"
          . "('%s', 0 ,'I am more unsure than sure', 2),"
          . "('%s', 0 ,'I am more sure than unsure', 3),"
          . "('%s', 0 ,'I am pretty sure', 4)",
          $this->exptId, $this->exptId, $this->exptId, $this->exptId);
      $igrtSqli->query($insertSql);
      $sliderLabelsResult = $igrtSqli->query($sliderLabelsQry);
    }
    else {
      if ($noSliderCheck != $noSliderPoints) { $noSliderPoints = $noSliderCheck; }
    }
    $sliderArray = [];
    while ($sliderLabelsRow = $sliderLabelsResult->fetch_object()) {
      $subItemFieldValues = array(
        'textValue'=> $sliderLabelsRow->label,
        'subItemLabel'=> "slider point # ".$sliderLabelsRow->confidenceValue,
        'dimension1'=> $sliderLabelsRow->confidenceValue,
        'dimension2'=> 0  // whichLikert = 0
      );
      array_push($sliderArray, $subItemFieldValues);
    }        
    $sliderPointsSelectJSON = $this->metadataConverter->getSelectJSONArray(2,10);
    $characterLimitSelectJSON = $this->metadataConverter->getSpacedSelectJSONArray(5000);
    $feedbackTimeSelectJSON = $this->metadataConverter->getmsSpacedSelectJSONArray(10000);
    $json = "{\"sectionTitle\": \"Step1 I Options\",\"sectionName\": \"s1interrogatorOptions\",\"exptId\":".$this->exptId.",\"formFields\":[";
      $pageWarning = "warning: changing the number of slider points in this page will only be actuated by clicking 'save and operate on changes' in the navigation footer.";
      $index = 0; // incremented in makeXXXJSON methods
      $json.= $this->makeFormFieldJSON($index, "text", "edContentDefs_refactor", "jRatingTitle", "instruction for rating screen", "", $this->eModel->jRatingTitle, "", "", "", "[]", false, [], [], "", "", "", "") . ",";
      $json.= $this->makeFormFieldJSON($index, "checkbox", "edExptStatic_refactor", "useRating", "get judgement from interrogator", $this->eModel->useRating, "", "", "yes", "no", "[]", false, [], [], "", "", "", "") . ",";
      $json.= $this->makeFormFieldJSON($index, "checkbox", "edExptStatic_refactor", "choosingNP", "choose NP rather than P (the label for choice control should reflect this)", $this->eModel->choosingNP, "", "", "yes", "no", "[]", false, [], [], "", "", "", "") . ",";
      $json.= $this->makeFormFieldJSON($index, "text", "edContentDefs_refactor", "labelChoice", "label for choice control", "", $this->eModel->labelChoice, "", "", "", "[]", false, [], [], "", "", "", "") . ",";
      $json.= $this->makeFormFieldJSON($index, "checkbox", "edExptStatic_refactor", "useReasons", "get reasons from interrogator", $this->eModel->useReasons, "", "", "yes", "no", "[]", false, [], [], "", "", "", "") . ",";
      $json.= $this->makeFormFieldJSON($index, "text", "edContentDefs_refactor", "labelReasons", "label for interrogator reason", "", $this->eModel->labelReasons, "", "", "", "[]", false, [], [], "", "", "", "") . ",";
      $json.= $this->makeFormFieldJSON($index, "checkbox", "edExptStatic_refactor", "useReasonCharacterLimit", "enforce a minimum character limit for the interrogator reason", $this->eModel->useReasonCharacterLimit, "", "", "yes", "no", "[]", false, [], [], "", "", "", "") . ",";
      $json.= $this->makeFormFieldJSON($index, "select", "edExptStatic_refactor", "reasonCharacterLimitValue", "minimum character limit", "", "", $this->eModel->reasonCharacterLimitValue, "", "", $characterLimitSelectJSON, false, [], [], "", "", "", "") . ",";
      $json.= $this->makeFormFieldJSON($index, "text", "edContentDefs_refactor", "reasonGuidance", "guidance message about character limit to interrogator", "", $this->eModel->reasonGuidance, "", "", "", "[]", false, [], [], "", "", "", "") . ",";
      $json.= $this->makeFormFieldJSON($index, "checkbox", "edExptStatic_refactor", "s1giveFeedback", "give performance feedback (each turn)", $this->eModel->s1giveFeedback, "", "", "yes", "no", "[]", false, [], [], "", "", "", "") . ",";
      $json.= $this->makeFormFieldJSON($index, "select", "edExptStatic_refactor", "s1feedbackTime", "feedback display time (mS)", "", "", $this->eModel->s1feedbackTime, "", "", $feedbackTimeSelectJSON, false, [], [], "", "", "", "")."," ;
      $json.= $this->makeFormFieldJSON($index, "text", "edContentDefs_refactor", "s1correctFB", "correct feedback message", "", $this->eModel->s1correctFB, "", "", "", "[]", false, [], [], "", "", "", "") . ",";
      $json.= $this->makeFormFieldJSON($index, "text", "edContentDefs_refactor", "s1incorrectFB", "incorrect feedback message", "", $this->eModel->s1incorrectFB, "", "", "", "[]", false, [], [], "", "", "", "") . ",";
      $json.= $this->makeFormFieldJSON($index, "checkbox", "edExptStatic_refactor", "s1runningScore", "display runnning score (competitive condition)", $this->eModel->s1runningScore, "", "", "yes", "no", "[]", false, [], [], "", "", "", "") . ",";
      $json.= $this->makeFormFieldJSON($index, "text", "edContentDefs_refactor", "s1runningScoreLabel", "running score pre-score label", "", $this->eModel->s1runningScoreLabel, "", "", "", "[]", false, [], [], "", "", "", "") . ",";
      $json.= $this->makeFormFieldJSON($index, "text", "edContentDefs_refactor", "s1runningScoreDividerLabel", "running score post-score label", "", $this->eModel->s1runningScoreDividerLabel, "", "", "", "[]", false, [], [], "", "", "", "") . ",";
      $json.= $this->makeFormFieldJSON($index, "checkbox", "edExptStatic_refactor", "useLikert", "use confidence slider", $this->eModel->useLikert, "", "", "yes", "no", "[]", false, [], [], "", "", "", "") . ",";
      $json.= $this->makeFormFieldJSON($index, "select", "edExptStatic_refactor", "noLikert", "no of confidence slider values", "", "", $noSliderPoints, "", "", $sliderPointsSelectJSON, false, [], [], "", "", "", "")."," ;
      $subItemFields = array(
        'dimension1Name' => 'confidenceValue',
        'dimension2Name' => 'whichLikert'
      );
      $lastFF = count($sliderArray);
      for ($i=0; $i<$lastFF; $i++) {
        $subItemLabel = $sliderArray[$i]['subItemLabel'];
        $textValue = $sliderArray[$i]['textValue'];
        $json.= $this->makeFormFieldJSON($index, "text", "edLabels", "label", $subItemLabel, "", $textValue, "", "", "", "[]", true, $subItemFields, $sliderArray[$i], "", "", "", "").",";
      }
      $json.= $this->makeFormFieldJSON($index, "pageWarning", "", "", $pageWarning, "", "", "",  "", "", "[]", false, [], [], "", "", "", "");
    $json.= "]}";
    return $json;
  }
  
  function getStep1InterrogatorFinalRating() {
    global $igrtSqli;
    $noSliderPoints = $this->eModel->noFinalLikert;
    $sliderLabelsQry = sprintf("SELECT * FROM edLabels WHERE exptId='%s' AND whichLikert=2 ORDER BY confidenceValue ASC", $this->exptId);
    $sliderLabelsResult = $igrtSqli->query($sliderLabelsQry);
    $noSliderCheck = $sliderLabelsResult ? $sliderLabelsResult->num_rows : 0;
    if ($noSliderCheck == 0) {
      // houston, so create 4 defaults
      $insertSql = sprintf("INSERT INTO edLabels (exptId, whichLikert, label, confidenceValue) "
          . "VALUES ('%s', 2, 'I am pretty unsure', 1),"
          . "('%s', 2 ,'I am more unsure than sure', 2),"
          . "('%s', 2 ,'I am more sure than unsure', 3),"
          . "('%s', 2 ,'I am pretty sure', 4)",
          $this->exptId, $this->exptId, $this->exptId, $this->exptId);
      $igrtSqli->query($insertSql);
      $sliderLabelsResult = $igrtSqli->query($sliderLabelsQry);
    }
    else {
      if ($noSliderCheck != $noSliderPoints) { $noSliderPoints = $noSliderCheck; }
    }
    $sliderArray = [];
    while ($sliderLabelsRow = $sliderLabelsResult->fetch_object()) {
      $subItemFieldValues = array(
        'textValue'=> $sliderLabelsRow->label,
        'subItemLabel'=> "slider point # ".$sliderLabelsRow->confidenceValue,
        'dimension1'=> $sliderLabelsRow->confidenceValue,
        'dimension2'=> 2  // whichLikert = 2
      );
      array_push($sliderArray, $subItemFieldValues);
    }        
    $sliderPointsSelectJSON = $this->metadataConverter->getSelectJSONArray(2,10);
    $percentSelectJSON = $this->metadataConverter->getSelectJSONArray(1,100);
    $characterLimitSelectJSON = $this->metadataConverter->getSpacedSelectJSONArray(5000);
    $json = "{\"sectionTitle\": \"Step1 I final options\",\"sectionName\": \"s1interrogatorFinalOptions\",\"exptId\":".$this->exptId.",\"formFields\":[";
      $pageWarning = "warning: changing the number of slider points in this page will only be actuated by clicking 'save and operate on changes' in the navigation footer.";
      $index = 0; // incremented in makeXXXJSON methods
      $json.= $this->makeFormFieldJSON($index, "checkbox", "edExptStatic_refactor", "useFinalRating", "get final P judgement from interrogator", $this->eModel->useFinalRating, "", "", "yes", "no", "[]", false, [], [], "", "", "", "") . ",";
      $json.= $this->makeFormFieldJSON($index, "text", "edContentDefs_refactor", "jFinalRatingTitle", "instruction for final rating screen", "", $this->eModel->jFinalRatingTitle, "", "", "", "[]", false, [], [], "", "", "", "") . ",";
      $json.= $this->makeFormFieldJSON($index, "text", "edContentDefs_refactor", "labelChoiceFinalRating", "label for final choice control", "", $this->eModel->labelChoiceFinalRating, "", "", "", "[]", false, [], [], "", "", "", "") . ",";
      $json.= $this->makeFormFieldJSON($index, "checkbox", "edExptStatic_refactor", "useReasonFinalRating", "get final reasons from interrogator", $this->eModel->useReasonFinalRating, "", "", "yes", "no", "[]", false, [], [], "", "", "", "") . ",";
      $json.= $this->makeFormFieldJSON($index, "text", "edContentDefs_refactor", "labelReasonFinalRating", "label for interrogator final reason", "", $this->eModel->labelReasonFinalRating, "", "", "", "[]", false, [], [], "", "", "", "") . ",";
      $json.= $this->makeFormFieldJSON($index, "checkbox", "edExptStatic_refactor", "useReasonCharacterLimitF", "enforce a minimum character limit for the interrogator final reason", $this->eModel->useReasonCharacterLimitF, "", "", "yes", "no", "[]", false, [], [], "", "", "", "") . ",";
      $json.= $this->makeFormFieldJSON($index, "select", "edExptStatic_refactor", "reasonCharacterLimitValueF", "minimum character limit", "", "", $this->eModel->reasonCharacterLimitValueF, "", "", $characterLimitSelectJSON, false, [], [], "", "", "", "") . ",";
      $json.= $this->makeFormFieldJSON($index, "text", "edContentDefs_refactor", "reasonGuidanceF", "guidance message about character limit to interrogator", "", $this->eModel->reasonGuidanceF, "", "", "", "[]", false, [], [], "", "", "", "") . ",";
      $json.= $this->makeFormFieldJSON($index, "checkbox", "edExptStatic_refactor", "s1giveFeedbackFinal", "give win/lose feedback (final judging)", $this->eModel->s1giveFeedbackFinal, "", "", "yes", "no", "[]", false, [], [], "", "", "", "") . ",";
      $json.= $this->makeFormFieldJSON($index, "select", "edExptStatic_refactor", "s1PercentForWinFeedbackFinal", "win calculation percentage", "", "", $this->eModel->s1PercentForWinFeedbackFinal, "", "", $percentSelectJSON, false, [], [], "", "", "", "") . ",";
      $json.= $this->makeFormFieldJSON($index, "text", "edContentDefs_refactor", "s1WinFeedbackLabel", "winner feedback", "", $this->eModel->s1WinFeedbackLabel, "", "", "", "[]", false, [], [], "", "", "", "") . ",";
      $json.= $this->makeFormFieldJSON($index, "text", "edContentDefs_refactor", "s1LoseFeedbackLabel", "loser feedback", "", $this->eModel->s1LoseFeedbackLabel, "", "", "", "[]", false, [], [], "", "", "", "") . ",";
      $json.= $this->makeFormFieldJSON($index, "text", "edContentDefs_refactor", "instFinalLikert", "instruction for final slider", "", $this->eModel->instFinalLikert, "", "", "", "[]", false, [], [], "", "", "", "") . ",";
      $json.= $this->makeFormFieldJSON($index, "checkbox", "edExptStatic_refactor", "useFinalLikert", "use final confidence slider", $this->eModel->useFinalLikert, "", "", "yes", "no", "[]", false, [], [], "", "", "", "") . ",";
      $json.= $this->makeFormFieldJSON($index, "select", "edExptStatic_refactor", "noFinalLikert", "no of confidence slider values", "", "", $noSliderPoints, "", "", $sliderPointsSelectJSON, false, [], [], "", "", "", "")."," ;
      $subItemFields = array(
        'dimension1Name' => 'confidenceValue',
        'dimension2Name' => 'whichLikert'
      );
      $lastFF = count($sliderArray);
      for ($i=0; $i<$lastFF; $i++) {
        $subItemLabel = $sliderArray[$i]['subItemLabel'];
        $textValue = $sliderArray[$i]['textValue'];
        $json.= $this->makeFormFieldJSON($index, "text", "edLabels", "label", $subItemLabel, "", $textValue, "", "", "", "[]", true, $subItemFields, $sliderArray[$i], "", "", "", "").",";
      }
      $json.= $this->makeFormFieldJSON($index, "pageWarning", "", "", $pageWarning, "", "", "",  "", "", "[]", false, [], [], "", "", "", "");
    $json.= "]}";
    return $json;		
  }
    
  function getStep1InterrogatorAlignment() {
    global $igrtSqli;
    $s1NoCategories = $this->eModel->s1NoCategories;
    $sql = sprintf("SELECT * FROM edAlignmentControlLabels WHERE exptId='%s' AND step=1 ORDER BY displayOrder ASC", $this->exptId);
    $result = $igrtSqli->query($sql);
    $noCategoriesCheck = $result ? $result->num_rows: 0;
    if ($noCategoriesCheck == 0) {
      // houston, so create 3 defaults
      $insertSql = sprintf("INSERT INTO edAlignmentControlLabels (exptId, step, label, displayOrder) "
          . "VALUES ('%s', 1,'content', 1),"
          . "('%s', 1, 'form', 2),"
          . "('%s', 1, 'grammar', 3)",
          $this->exptId, $this->exptId, $this->exptId);
      $igrtSqli->query($insertSql);
      $result = $igrtSqli->query($sql);
    }
    else {
      if ($noCategoriesCheck != $s1NoCategories) { $s1NoCategories = $noCategoriesCheck; }
    }
    $s1Categories = [];
    while ($row = $result->fetch_object()) {
      $subItemFieldValues = array(
        'textValue'=> $row->label,
        'subItemLabel'=> "category # ".$row->displayOrder,
        'dimension1'=> $row->displayOrder,
        'dimension2'=> 1
      );
      array_push($s1Categories, $subItemFieldValues);
    }        
    $categoryNoSelectJSON = $this->metadataConverter->getSelectJSONArray(1,10);
    $characterLimitSelectJSON = $this->metadataConverter->getSpacedSelectJSONArray(5000);
    $json = "{\"sectionTitle\": \"Step1 I alignment\",\"sectionName\": \"s1interrogatorAlignment\",\"exptId\":".$this->exptId.",\"formFields\":[";
      $pageWarning = "warning: changing the number of category items in this page will only be actuated by clicking 'save and operate on changes' in the navigation footer.";
      $index = 0; // incremented in makeXXXJSON methods
      $json.= $this->makeFormFieldJSON($index, "checkbox", "edExptStatic_refactor", "useS1Intention", "get interrogator question intention(iqi)", $this->eModel->useS1Intention, "", "", "yes", "no", "[]", false, [], [], "", "", "", "") . ",";
		$json.= $this->makeFormFieldJSON($index, "text", "edContentDefs_refactor", "s1IntentionLabel", "instruction for iqi", "", $this->eModel->s1IntentionLabel, "", "", "", "[]", false, [], [], "", "", "", "") . ",";
      $json.= $this->makeFormFieldJSON($index, "checkbox", "edExptStatic_refactor", "useS1IntentionMin", "use character minimum for iqi", $this->eModel->useS1IntentionMin, "", "", "yes", "no", "[]", false, [], [], "", "", "", "") . ",";
      $json.= $this->makeFormFieldJSON($index, "select", "edExptStatic_refactor", "s1IntentionMin", "min intention character limit", "", "", $this->eModel->s1IntentionMin, "", "", $characterLimitSelectJSON, false, [], [], "", "", "", "") . ",";
      $json.= $this->makeFormFieldJSON($index, "text", "edContentDefs_refactor", "s1IntentionMinLabel", "guidance on ", "", $this->eModel->s1IntentionMinLabel, "", "", "", "[]", false, [], [], "", "", "", "") . ",";
      $json.= $this->makeFormFieldJSON($index, "checkbox", "edExptStatic_refactor", "useS1AlignmentControl", "get alignment rating from interrogator (iar)", $this->eModel->useS1AlignmentControl, "", "", "yes", "no", "[]", false, [], [], "", "", "", "") . ",";
      $json.= $this->makeFormFieldJSON($index, "checkbox", "edExptStatic_refactor", "useS1AlignmentAsRB", "use Radiobuttons for iar (MUST be used if a classic experiment)", $this->eModel->useS1AlignmentAsRB, "", "", "yes", "no", "[]", false, [], [], "", "", "", "") . ",";
      $json.= $this->makeFormFieldJSON($index, "text", "edContentDefs_refactor", "s1AlignmentLabel", "alignment instruction", "", $this->eModel->s1AlignmentLabel, "", "", "", "[]", false, [], [], "", "", "", "") . ",";
      $json.= $this->makeFormFieldJSON($index, "checkbox", "edExptStatic_refactor", "useS1AlignmentNoneLabel", "use alignment label 1 ", $this->eModel->useS1AlignmentNoneLabel, "", "", "yes", "no", "[]", false, [], [], "", "", "", "") . ",";
      $json.= $this->makeFormFieldJSON($index, "checkbox", "edExptStatic_refactor", "appendITypetoS1AlignmentNoneLabel", "append i-type to alignment label 1 ", $this->eModel->appendITypetoS1AlignmentNoneLabel, "", "", "yes", "no", "[]", false, [], [], "", "", "", "") . ",";
      $json.= $this->makeFormFieldJSON($index, "text", "edContentDefs_refactor", "s1AlignmentNoneLabel", "alignment label 1", "", $this->eModel->s1AlignmentNoneLabel, "", "", "", "[]", false, [], [], "", "", "", "") . ",";
      $json.= $this->makeFormFieldJSON($index, "checkbox", "edExptStatic_refactor", "useS1AlignmentPartlyLabel", "use alignment label 2 ", $this->eModel->useS1AlignmentPartlyLabel, "", "", "yes", "no", "[]", false, [], [], "", "", "", "") . ",";
      $json.= $this->makeFormFieldJSON($index, "checkbox", "edExptStatic_refactor", "appendITypetoS1AlignmentPartlyLabel", "append i-type to alignment label 2 ", $this->eModel->appendITypetoS1AlignmentPartlyLabel, "", "", "yes", "no", "[]", false, [], [], "", "", "", "") . ",";
      $json.= $this->makeFormFieldJSON($index, "text", "edContentDefs_refactor", "s1AlignmentPartlyLabel", "alignment label 2", "", $this->eModel->s1AlignmentPartlyLabel, "", "", "", "[]", false, [], [], "", "", "", "") . ",";
      $json.= $this->makeFormFieldJSON($index, "checkbox", "edExptStatic_refactor", "useS1AlignmentMostlyLabel", "use alignment label 3 ", $this->eModel->useS1AlignmentMostlyLabel, "", "", "yes", "no", "[]", false, [], [], "", "", "", "") . ",";
      $json.= $this->makeFormFieldJSON($index, "checkbox", "edExptStatic_refactor", "appendITypetoS1AlignmentMostlyLabel", "append i-type to alignment label 3 ", $this->eModel->appendITypetoS1AlignmentMostlyLabel, "", "", "yes", "no", "[]", false, [], [], "", "", "", "") . ",";
      $json.= $this->makeFormFieldJSON($index, "text", "edContentDefs_refactor", "s1AlignmentMostlyLabel", "alignment label 3", "", $this->eModel->s1AlignmentMostlyLabel, "", "", "", "[]", false, [], [], "", "", "", "") . ",";
      $json.= $this->makeFormFieldJSON($index, "checkbox", "edExptStatic_refactor", "useS1AlignmentCompletelyLabel", "use alignment label 4 ", $this->eModel->useS1AlignmentCompletelyLabel, "", "", "yes", "no", "[]", false, [], [], "", "", "", "") . ",";
      $json.= $this->makeFormFieldJSON($index, "checkbox", "edExptStatic_refactor", "appendITypetoS1AlignmentCompletelyLabel", "append i-type to alignment label 4 ", $this->eModel->appendITypetoS1AlignmentCompletelyLabel, "", "", "yes", "no", "[]", false, [], [], "", "", "", "") . ",";
      $json.= $this->makeFormFieldJSON($index, "text", "edContentDefs_refactor", "s1AlignmentCompletelyLabel", "alignment label 4", "", $this->eModel->s1AlignmentCompletelyLabel, "", "", "", "[]", false, [], [], "", "", "", "") . ",";
      $json.= $this->makeFormFieldJSON($index, "checkbox", "edExptStatic_refactor", "useS1AlignmentExtraLabel", "use alignment label 5 ", $this->eModel->useS1AlignmentExtraLabel, "", "", "yes", "no", "[]", false, [], [], "", "", "", "") . ",";
      $json.= $this->makeFormFieldJSON($index, "checkbox", "edExptStatic_refactor", "appendITypetoS1AlignmentExtraLabel", "append i-type to alignment label 5 ", $this->eModel->appendITypetoS1AlignmentExtraLabel, "", "", "yes", "no", "[]", false, [], [], "", "", "", "") . ",";
      $json.= $this->makeFormFieldJSON($index, "text", "edContentDefs_refactor", "s1AlignmentExtraLabel", "alignment label 5", "", $this->eModel->s1AlignmentExtraLabel, "", "", "", "[]", false, [], [], "", "", "", "") . ",";
      $json.= $this->makeFormFieldJSON($index, "checkbox", "edExptStatic_refactor", "useS1QCategoryControl", "get rating categories from interrogator (irc)", $this->eModel->useS1QCategoryControl, "", "", "yes", "no", "[]", false, [], [], "", "", "", "") . ",";
      $json.= $this->makeFormFieldJSON($index, "text", "edContentDefs_refactor", "s1CategoryLabel", "instruction for irc", "", $this->eModel->s1CategoryLabel, "", "", "", "[]", false, [], [], "", "", "", "") . ",";
      $json.= $this->makeFormFieldJSON($index, "select", "edExptStatic_refactor", "s1NoCategories", "no of irc categories", "", "", $s1NoCategories , "", "", $categoryNoSelectJSON, false, [], [], "", "", "", "")."," ;
      $subItemFields = array(
        'dimension1Name' => 'displayOrder',
        'dimension2Name' => 'step'
      );
      $lastFF = count($s1Categories);
      for ($i=0; $i<$lastFF; $i++) {
        $subItemLabel = $s1Categories[$i]['subItemLabel'];
        $textValue = $s1Categories[$i]['textValue'];
        $json.= $this->makeFormFieldJSON($index, "text", "edAlignmentControlLabels", "label", $subItemLabel, "", $textValue, "", "", "", "[]", true, $subItemFields, $s1Categories[$i], "", "", "", "").",";
      }
      $json.= $this->makeFormFieldJSON($index, "pageWarning", "", "", $pageWarning, "", "", "",  "", "", "[]", false, [], [], "", "", "", "");
    $json.= "]}";
    return $json;
  }
  
  function getStep1iContent() {
    $json = "{\"sectionTitle\": \"Step1 I content\",\"sectionName\": \"step1iContent\",\"exptId\":".$this->exptId.",\"formFields\":[";
      $pageWarning = "Note: try to keep labels and tooltips as short as possible. Messages can be longer instructions. "
          . "The history controls may not be used if the Step1 using randomised sides for the Pretender on each turn. "
          . "Some dialogue box content is unnecessary now the 'bar-billiards' control is used by the session admin.";
      $index = 0; // incremented in makeXXXJSON methods
      $json.= $this->makeFormFieldJSON($index, "pageWarning", "", "", $pageWarning, "", "", "",  "", "", "[]", false, [], [], "", "", "", "") . ",";
      $json.= $this->makeFormFieldJSON($index, "text", "edContentDefs_refactor", "jTab", "interrogator tab label", "", $this->eModel->jTab, "", "", "", "[]", false, [], [], "", "", "", "") . ",";
      $json.= $this->makeFormFieldJSON($index, "text", "edContentDefs_refactor", "jTabUnconnected", "interrogator tab unconnected tooltext", "", $this->eModel->jTabUnconnected, "", "", "", "[]", false, [], [], "", "", "", "") . ",";
      $json.= $this->makeFormFieldJSON($index, "text", "edContentDefs_refactor", "jTabWaiting", "interrogator tab waiting tooltext", "", $this->eModel->jTabWaiting, "", "", "", "[]", false, [], [], "", "", "", "") . ",";
      $json.= $this->makeFormFieldJSON($index, "text", "edContentDefs_refactor", "jTabActive", "interrogator tab active tooltext", "", $this->eModel->jTabActive, "", "", "", "[]", false, [], [], "", "", "", "") . ",";
      $json.= $this->makeFormFieldJSON($index, "text", "edContentDefs_refactor", "jTabRating", "interrogator tab choosing tooltext", "", $this->eModel->jTabRating, "", "", "", "[]", false, [], [], "", "", "", "") . ",";
      $json.= $this->makeFormFieldJSON($index, "text", "edContentDefs_refactor", "jTabDone", "interrogator tab finished tooltext", "", $this->eModel->jTabDone, "", "", "", "[]", false, [], [], "", "", "", "") . ",";
      $json.= $this->makeFormFieldJSON($index, "text", "edContentDefs_refactor", "jWaitingToStart", "step1 waiting for start signal message", "", $this->eModel->jWaitingToStart, "", "", "", "[]", false, [], [], "", "", "", "") . ",";
      $json.= $this->makeFormFieldJSON($index, "text", "edContentDefs_refactor", "jPleaseAsk", "interrogator question box instruction message", "", $this->eModel->jPleaseAsk, "", "", "", "[]", false, [], [], "", "", "", "") . ",";
      $json.= $this->makeFormFieldJSON($index, "text", "edContentDefs_refactor", "jAskButton", "interrogator send question label", "", $this->eModel->jAskButton, "", "", "", "[]", false, [], [], "", "", "", "") . ",";
      $json.= $this->makeFormFieldJSON($index, "text", "edContentDefs_refactor", "jWaitingForReplies", "interrogator waiting for replies message", "", $this->eModel->jWaitingForReplies, "", "", "", "[]", false, [], [], "", "", "", "") . ",";
      $json.= $this->makeFormFieldJSON($index, "text", "edContentDefs_refactor", "jHistoryTitle", "interrogator history control label", "", $this->eModel->jHistoryTitle, "", "", "", "[]", false, [], [], "", "", "", "") . ",";
      $json.= $this->makeFormFieldJSON($index, "text", "edContentDefs_refactor", "jRatingTitle", "interrogator choice heading message", "", $this->eModel->jRatingTitle, "", "", "", "[]", false, [], [], "", "", "", "") . ",";
      $json.= $this->makeFormFieldJSON($index, "text", "edContentDefs_refactor", "jRatingYourQuestion", "jRatingYourQuestion confidence message", "", $this->eModel->jRatingYourQuestion, "", "", "", "[]", false, [], [], "", "", "", "") . ",";
      $json.= $this->makeFormFieldJSON($index, "text", "edContentDefs_refactor", "jRatingQ", "jRatingQ final-choice heading message", "", $this->eModel->jRatingQ, "", "", "", "[]", false, [], [], "", "", "", "") . ",";
      $json.= $this->makeFormFieldJSON($index, "text", "edContentDefs_refactor", "jRatingR1", "interrogator choice respondent1 label", "", $this->eModel->jRatingR1, "", "", "", "[]", false, [], [], "", "", "", "") . ",";
      $json.= $this->makeFormFieldJSON($index, "text", "edContentDefs_refactor", "jRatingR2", "interrogator choice respondent2 label", "", $this->eModel->jRatingR2, "", "", "", "[]", false, [], [], "", "", "", "") . ",";
      $json.= $this->makeFormFieldJSON($index, "text", "edContentDefs_refactor", "jAskAnotherB", "interrogator another-Q label", "", $this->eModel->jAskAnotherB, "", "", "", "[]", false, [], [], "", "", "", "") . ",";
      $json.= $this->makeFormFieldJSON($index, "text", "edContentDefs_refactor", "jNoMoreB", "interrogator no-more-Q label", "", $this->eModel->jNoMoreB, "", "", "", "[]", false, [], [], "", "", "", "") . ",";
      $json.= $this->makeFormFieldJSON($index, "text", "edContentDefs_refactor", "jSaveFinalB", "interrogator save-final-choice button label", "", $this->eModel->jSaveFinalB, "", "", "", "[]", false, [], [], "", "", "", "") . ",";
      $json.= $this->makeFormFieldJSON($index, "text", "edContentDefs_refactor", "jFinalMsg", "interrogator final-choice heading message", "", $this->eModel->jFinalMsg, "", "", "", "[]", false, [], [], "", "", "", "") . ",";
      $json.= $this->makeFormFieldJSON($index, "text", "edContentDefs_refactor", "jConfirmHead", "confirm pop-up header label", "", $this->eModel->jConfirmHead, "", "", "", "[]", false, [], [], "", "", "", "") . ",";
      $json.= $this->makeFormFieldJSON($index, "text", "edContentDefs_refactor", "jConfirmBody", "confirm pop-up body label", "", $this->eModel->jConfirmBody, "", "", "", "[]", false, [], [], "", "", "", "") . ",";
      $json.= $this->makeFormFieldJSON($index, "text", "edContentDefs_refactor", "jConfirmOK", "confirm pop-up OK label", "", $this->eModel->jConfirmOK, "", "", "", "[]", false, [], [], "", "", "", "") . ",";
      $json.= $this->makeFormFieldJSON($index, "text", "edContentDefs_refactor", "jConfirmCancel", "confirm pop-up CANCEL label", "", $this->eModel->jConfirmCancel, "", "", "", "[]", false, [], [], "", "", "", "");
      
    $json.= "]}";
    return $json;    
  }
  
  function getStep1rContent() {
    $json = "{\"sectionTitle\": \"Step1 R content\",\"sectionName\": \"step1rContent\",\"exptId\":".$this->exptId.",\"formFields\":[";
      $pageWarning = "Note: try to keep labels and tooltips as short as possible. Messages can be longer instructions. "
          . "The history controls may not be used if the Step1 using randomised sides for the Pretender on each turn. "
          . "Some dialogue box content is unnecessary now the 'bar-billiards' control is used by the session admin.";
      $index = 0; // incremented in makeXXXJSON methods
      $json.= $this->makeFormFieldJSON($index, "pageWarning", "", "", $pageWarning, "", "", "",  "", "", "[]", false, [], [], "", "", "", "") . ",";
      $json.= $this->makeFormFieldJSON($index, "text", "edContentDefs_refactor", "npTab", "NP tab label", "", $this->eModel->npTab, "", "", "", "[]", false, [], [], "", "", "", "") . ",";
      $json.= $this->makeFormFieldJSON($index, "text", "edContentDefs_refactor", "pTab", "P tab label", "", $this->eModel->pTab, "", "", "", "[]", false, [], [], "", "", "", "") . ",";
      $json.= $this->makeFormFieldJSON($index, "text", "edContentDefs_refactor", "rTabInactive", "tab inactive tooltext", "", $this->eModel->rTabInactive, "", "", "", "[]", false, [], [], "", "", "", "") . ",";
      $json.= $this->makeFormFieldJSON($index, "text", "edContentDefs_refactor", "rTabActive", "tab active tooltext", "", $this->eModel->rTabActive, "", "", "", "[]", false, [], [], "", "", "", "") . ",";
      $json.= $this->makeFormFieldJSON($index, "text", "edContentDefs_refactor", "rTabWaiting", "tab waiting tooltext", "", $this->eModel->rTabWaiting, "", "", "", "[]", false, [], [], "", "", "", "") . ",";
      $json.= $this->makeFormFieldJSON($index, "text", "edContentDefs_refactor", "rTabDone", "tab done tooltext", "", $this->eModel->rTabDone, "", "", "", "[]", false, [], [], "", "", "", "") . ",";
      $json.= $this->makeFormFieldJSON($index, "text", "edContentDefs_refactor", "rWaitFirst", "wait for first question message", "", $this->eModel->rWaitFirst, "", "", "", "[]", false, [], [], "", "", "", "") . ",";
      $json.= $this->makeFormFieldJSON($index, "text", "edContentDefs_refactor", "rWaitNext", "wait for next question message", "", $this->eModel->rWaitNext, "", "", "", "[]", false, [], [], "", "", "", "") . ",";
      $json.= $this->makeFormFieldJSON($index, "text", "edContentDefs_refactor", "rHistoryTitle", "histry section label", "", $this->eModel->rHistoryTitle, "", "", "", "[]", false, [], [], "", "", "", "") . ",";
      $json.= $this->makeFormFieldJSON($index, "text", "edContentDefs_refactor", "rCurrentQ", "question label", "", $this->eModel->rCurrentQ, "", "", "", "[]", false, [], [], "", "", "", "") . ",";
      $json.= $this->makeFormFieldJSON($index, "text", "edContentDefs_refactor", "rInstruction", "response instruction", "", $this->eModel->rInstruction, "", "", "", "[]", false, [], [], "", "", "", "") . ",";
      $json.= $this->makeFormFieldJSON($index, "text", "edContentDefs_refactor", "rSendB", "send button label", "", $this->eModel->rSendB, "", "", "", "[]", false, [], [], "", "", "", "") . ",";
      $json.= $this->makeFormFieldJSON($index, "text", "edContentDefs_refactor", "rGuidanceHeader", "guidance header label", "", $this->eModel->rGuidanceHeader, "", "", "", "[]", false, [], [], "", "", "", "") . ",";
      $json.= $this->makeFormFieldJSON($index, "text", "edContentDefs_refactor", "rYourAnswer", "your answer label", "", $this->eModel->rYourAnswer, "", "", "", "[]", false, [], [], "", "", "", "") . ",";
      $json.= $this->makeFormFieldJSON($index, "text", "edContentDefs_refactor", "rFinalMsg", "done message", "", $this->eModel->rFinalMsg, "", "", "", "[]", false, [], [], "", "", "", "") . ",";
      $json.= $this->makeFormFieldJSON($index, "text", "edContentDefs_refactor", "npGuidance", "guidance to NP", "", $this->eModel->npGuidance, "", "", "", "[]", false, [], [], "", "", "", "") . ",";
      $json.= $this->makeFormFieldJSON($index, "text", "edContentDefs_refactor", "pGuidance", "guidance to P", "", $this->eModel->pGuidance, "", "", "", "[]", false, [], [], "", "", "", "");
    $json.= "]}";
    return $json;        
  }

  function getStep2Content() {
    $json = "{\"sectionTitle\": \"P step2 content\",\"sectionName\": \"step2Content\",\"exptId\":".$this->exptId.",\"formFields\":[";
      $pageWarning = "Note: try to keep labels and tooltips as short as possible. Messages can be longer instructions. "
          . "Some values in this page may not be used, depending on how surveys are configured.";
      $index = 0; // incremented in makeXXXJSON methods
      $json.= $this->makeFormFieldJSON($index, "pageWarning", "", "", $pageWarning, "", "", "",  "", "", "[]", false, [], [], "", "", "") . ",";
      $json.= $this->makeFormFieldJSON($index, "text", "edContentDefs_refactor", "step2_startBLabel", "start button label", "", $this->eModel->step2_startBLabel, "", "", "", "[]", false, [], [], "", "", "", "") . ",";
      $json.= $this->makeFormFieldJSON($index, "text", "edContentDefs_refactor", "step2_replyMsg", "reply instruction", "", $this->eModel->step2_replyMsg, "", "", "", "[]", false, [], [], "", "", "", "") . ",";
      $json.= $this->makeFormFieldJSON($index, "text", "edContentDefs_refactor", "step2_sendBLabel", "send button label", "", $this->eModel->step2_sendBLabel, "", "", "", "[]", false, [], [], "", "", "", "") . ",";
      $json.= $this->makeFormFieldJSON($index, "text", "edContentDefs_refactor", "step2_endBLabel", "end pretending phase button label", "", $this->eModel->step2_endBLabel, "", "", "", "[]", false, [], [], "", "", "", "") . ",";
      $json.= $this->makeFormFieldJSON($index, "text", "edContentDefs_refactor", "step2_finalMsg", "end of pretending phase message", "", $this->eModel->step2_finalMsg, "", "", "", "[]", false, [], [], "", "", "", "") . ",";
      $json.= $this->makeFormFieldJSON($index, "text", "edContentDefs_refactor", "step2_closedMsg", "closed message", "", $this->eModel->step2_closedMsg, "", "", "", "[]", false, [], [], "", "", "", "");
    $json.= "]}";
    return $json;    
  }

  function getStep2PAlignment() {
    $characterLimitSelectJSON = $this->metadataConverter->getSpacedSelectJSONArray(5000);
    $json = "{\"sectionTitle\": \"Step2 alignment\",\"sectionName\": \"s2PAlignment\",\"exptId\":".$this->exptId.",\"formFields\":[";
      $index = 0; // incremented in makeXXXJSON methods
      $json.= $this->makeFormFieldJSON($index, "checkbox", "edExptStatic_refactor", "useS2CharacterLimit", "use character minimum for P reply", $this->eModel->useS2CharacterLimit, "", "", "yes", "no", "[]", false, [], [], "", "", "", "") . ",";
      $json.= $this->makeFormFieldJSON($index, "select", "edExptStatic_refactor", "s2CharacterLimitValue", "minimum reply character limit", "", "", $this->eModel->s2CharacterLimitValue, "", "", $characterLimitSelectJSON, false, [], [], "", "", "", "") . ",";
      $json.= $this->makeFormFieldJSON($index, "text", "edContentDefs_refactor", "step2_ReplyLimitGuidance", "reply minimum guidance", "", $this->eModel->step2_ReplyLimitGuidance, "", "", "", "[]", false, [], [], "", "", "", ""). ",";
      $json.= $this->makeFormFieldJSON($index, "checkbox", "edExptStatic_refactor", "useS2PAlignment", "get s2 P alignment rating", $this->eModel->useS2PAlignment, "", "", "yes", "no", "[]", false, [], [], "", "", "", "") . ",";
      $json.= $this->makeFormFieldJSON($index, "text", "edContentDefs_refactor", "s2PAlignmentLabel", "alignment question", "", $this->eModel->s2PAlignmentLabel, "", "", "", "[]", false, [], [], "", "", "", "") . ",";
      $json.= $this->makeFormFieldJSON($index, "text", "edContentDefs_refactor", "s2PYesLabel", "yes button label", "", $this->eModel->s2PYesLabel, "", "", "", "[]", false, [], [], "", "", "", "") . ",";
      $json.= $this->makeFormFieldJSON($index, "text", "edContentDefs_refactor", "s2PNoLabel", "no button label", "", $this->eModel->s2PNoLabel, "", "", "", "[]", false, [], [], "", "", "", "") . ",";
      $json.= $this->makeFormFieldJSON($index, "text", "edContentDefs_refactor", "s2CorrectedAnswerLabel", "corrected answer instruction", "", $this->eModel->s2CorrectedAnswerLabel, "", "", "", "[]", false, [], [], "", "", "", "") . ",";
      $json.= $this->makeFormFieldJSON($index, "text", "edContentDefs_refactor", "s2ContinueLabel", "continue button label", "", $this->eModel->s2ContinueLabel, "", "", "", "[]", false, [], [], "", "", "", "");
    $json.= "]}";
    return $json;    
  }
  
  function getStep2Balancer() {
    global $igrtSqli;
    $sql = sprintf("SELECT * FROM wt_Step2BalancerRespMax WHERE exptId='%s'", $this->exptId);
    $result = $igrtSqli->query($sql);
    if ($result->num_rows >0) {
      $row = $result->fetch_object();
      $evenNo = $row->evenRespMax;
      $oddNo = $row->oddRespMax;
    }
    else {
      $evenNo = 1;
      $oddNo = 1;
    }
    $sql = sprintf("SELECT * FROM dataSTEP2 WHERE exptId='%s' AND jType=0", $this->exptId);
    $result = $igrtSqli->query($sql);
    $hasEven = $result ? true : false;
    $sql = sprintf("SELECT * FROM dataSTEP2 WHERE exptId='%s' AND jType=1", $this->exptId);
    $result = $igrtSqli->query($sql);
    $hasOdd = $result ? true : false;
    $selectJSON = $this->metadataConverter->getSelectJSONArray(1,50);
    $json = "{\"sectionTitle\": \"Step2 P balancer\",\"sectionName\": \"s2Balancer\",\"exptId\":".$this->exptId.",\"formFields\":[";
      $pageWarning = "Note: the balancer values are actuated when pressing `save and operate on changes`";
      $oddStatusMsg = $this->eModel->evenS1Label.' pretending as '.$this->eModel->oddS1Label.' . ';
      $oddStatusMsg.= $this->eModel->step2OddConfigured == 1 ? "Odd S2 balancer is configured. " : "Odd S2 balancer is not configured. ";
      $oddStatusMsg.= $hasOdd ? "Odd data collection has started - try to avoid alterations." : "No odd data yet collected. ";
      $evenStatusMsg = $this->eModel->oddS1Label.' pretending as '.$this->eModel->evenS1Label.' . ';
      $evenStatusMsg.= $this->eModel->step2EvenConfigured == 1 ? "Even S2 balancer is configured. " : "Even S2 balancer is not configured. ";
      $evenStatusMsg.= $hasEven ? "Even data collection has started - try to avoid alterations. " : "No even data yet collected. ";
      $index = 0; // incremented in makeXXXJSON methods
      $json.= $this->makeFormFieldJSON($index, "pageWarning", "", "", $pageWarning, "", "", "",  "", "", "[]", false, [], [], "", "", "", "") . ",";
      $json.= $this->makeFormFieldJSON($index, "select", "wt_Step2BalancerRespMax", "oddRespMax", "max odd #", "", "", $oddNo, "", "", $selectJSON, false, [], [], "", "", "", "") . ",";
      $json.= $this->makeFormFieldJSON($index, "pageWarning", "", "", $oddStatusMsg, "", "", "",  "", "", "[]", false, [], [], "", "", "", "") . ",";
      $json.= $this->makeFormFieldJSON($index, "select", "wt_Step2BalancerRespMax", "evenRespMax", "max even #", "", "", $evenNo, "", "", $selectJSON, false, [], [], "", "", "", ""). ",";
      $json.= $this->makeFormFieldJSON($index, "pageWarning", "", "", $evenStatusMsg, "", "", "",  "", "", "[]", false, [], [], "", "", "", "");
    $json.= "]}";
    return $json;    
  }

  function getIStep2Content() {
    $json = "{\"sectionTitle\": \"NP (inverted) step2 content\",\"sectionName\": \"iStep2Content\",\"exptId\":".$this->exptId.",\"formFields\":[";
      $pageWarning = "Note: try to keep labels and tooltips as short as possible. Messages can be longer instructions. "
          . "Some values in this page may not be used, depending on how surveys are configured.";
      $index = 0; // incremented in makeXXXJSON methods
      $json.= $this->makeFormFieldJSON($index, "pageWarning", "", "", $pageWarning, "", "", "",  "", "", "[]", false, [], [], "", "", "") . ",";
      $json.= $this->makeFormFieldJSON($index, "text", "edContentDefs_refactor", "step2_invertedStartBLabel", "start button label", "", $this->eModel->step2_invertedStartBLabel, "", "", "", "[]", false, [], [], "", "", "", "") . ",";
      $json.= $this->makeFormFieldJSON($index, "text", "edContentDefs_refactor", "step2_invertedReplyMsg", "reply instruction", "", $this->eModel->step2_invertedReplyMsg, "", "", "", "[]", false, [], [], "", "", "", "") . ",";
      $json.= $this->makeFormFieldJSON($index, "text", "edContentDefs_refactor", "step2_invertedSendBLabel", "send button label", "", $this->eModel->step2_invertedSendBLabel, "", "", "", "[]", false, [], [], "", "", "", "") . ",";
      $json.= $this->makeFormFieldJSON($index, "text", "edContentDefs_refactor", "step2_invertedEndBLabel", "end non-pretending phase button label", "", $this->eModel->step2_invertedEndBLabel, "", "", "", "[]", false, [], [], "", "", "", "") . ",";
      $json.= $this->makeFormFieldJSON($index, "text", "edContentDefs_refactor", "step2_invertedFinalMsg", "end of non-pretending phase message", "", $this->eModel->step2_invertedFinalMsg, "", "", "", "[]", false, [], [], "", "", "", "") . ",";
      $json.= $this->makeFormFieldJSON($index, "text", "edContentDefs_refactor", "step2_invertedClosedMsg", "closed message", "", $this->eModel->step2_invertedClosedMsg, "", "", "", "[]", false, [], [], "", "", "", "");
    $json.= "]}";
    return $json;    
  }
  
  function getIStep2NPAlignment() {
    $characterLimitSelectJSON = $this->metadataConverter->getSpacedSelectJSONArray(5000);
    $json = "{\"sectionTitle\": \"NP (inverted) Step2 alignment\",\"sectionName\": \"iS2NPAlignment\",\"exptId\":".$this->exptId.",\"formFields\":[";
      $index = 0; // incremented in makeXXXJSON methods
      $json.= $this->makeFormFieldJSON($index, "checkbox", "edExptStatic_refactor", "useIS2CharacterLimit", "use character minimum for NP reply (and corrected reply if alignment used)", $this->eModel->useIS2CharacterLimit, "", "", "yes", "no", "[]", false, [], [], "", "", "", "") . ",";
      $json.= $this->makeFormFieldJSON($index, "select", "edExptStatic_refactor", "iS2CharacterLimitValue", "minimum reply character limit", "", "", $this->eModel->iS2CharacterLimitValue, "", "", $characterLimitSelectJSON, false, [], [], "", "", "", "") . ",";
      $json.= $this->makeFormFieldJSON($index, "text", "edContentDefs_refactor", "istep2_ReplyLimitGuidance", "reply minimum guidance", "", $this->eModel->istep2_ReplyLimitGuidance, "", "", "", "[]", false, [], [], "", "", "", ""). ",";
      $json.= $this->makeFormFieldJSON($index, "checkbox", "edExptStatic_refactor", "useIS2NPAlignment", "get inverted s2 NP alignment rating", $this->eModel->useIS2NPAlignment, "", "", "yes", "no", "[]", false, [], [], "", "", "", "") . ",";
      $json.= $this->makeFormFieldJSON($index, "text", "edContentDefs_refactor", "iS2NPAlignmentLabel", "alignment question", "", $this->eModel->iS2NPAlignmentLabel, "", "", "", "[]", false, [], [], "", "", "", "") . ",";
      $json.= $this->makeFormFieldJSON($index, "text", "edContentDefs_refactor", "iS2NPYesLabel", "yes button label", "", $this->eModel->iS2NPYesLabel, "", "", "", "[]", false, [], [], "", "", "", "") . ",";
      $json.= $this->makeFormFieldJSON($index, "text", "edContentDefs_refactor", "iS2NPNoLabel", "no button label", "", $this->eModel->iS2NPNoLabel, "", "", "", "[]", false, [], [], "", "", "", "") . ",";
      $json.= $this->makeFormFieldJSON($index, "text", "edContentDefs_refactor", "iS2CorrectedAnswerLabel", "corrected reply instruction", "", $this->eModel->iS2CorrectedAnswerLabel, "", "", "", "[]", false, [], [], "", "", "", "") . ",";
      $json.= $this->makeFormFieldJSON($index, "text", "edContentDefs_refactor", "iS2ContinueLabel", "continue button label", "", $this->eModel->iS2ContinueLabel, "", "", "", "[]", false, [], [], "", "", "", "");
    $json.= "]}";
    return $json;    
  }

  function getIStep2Balancer() {
    global $igrtSqli;
    $sql = sprintf("SELECT * FROM wt_Step2BalancerRespMax WHERE exptId='%s'", $this->exptId);
    $result = $igrtSqli->query($sql);
    if ($result->num_rows>0) {
      $row = $result->fetch_object();
      $evenNo = $row->invertedEvenRespMax;
      $oddNo = $row->invertedOddRespMax;
    }
    else {
      $evenNo = 1;
      $oddNo = 1;
    }
    $sql = sprintf("SELECT * FROM dataSTEP2inverted WHERE exptId='%s' AND jType=0", $this->exptId);
    $result = $igrtSqli->query($sql);
    $hasEven = $result ? true : false;
    $sql = sprintf("SELECT * FROM dataSTEP2inverted WHERE exptId='%s' AND jType=1", $this->exptId);
    $result = $igrtSqli->query($sql);
    $hasOdd = $result ? true : false;
    $selectJSON = $this->metadataConverter->getSelectJSONArray(1,50);
    $json = "{\"sectionTitle\": \"Step2 NP (inverted) balancer\",\"sectionName\": \"iS2Balancer\",\"exptId\":".$this->exptId.",\"formFields\":[";
      $pageWarning = "Note: the balancer values are actuated when pressing `save and operate on changes`";
      $oddStatusMsg = $this->eModel->oddS1Label.' answering naturally. ';
      $oddStatusMsg.= $this->eModel->step2InvertedOddConfigured == 1 ? "Odd S2 balancer is configured. " : "Odd S2 balancer is not configured. ";
      $oddStatusMsg.= $hasOdd ? "Odd data collection has started - try to avoid alterations." : "No odd data yet collected";
      $evenStatusMsg = $this->eModel->evenS1Label.' answering naturally. ';
      $evenStatusMsg.= $this->eModel->step2InvertedEvenConfigured == 1 ? "Even S2 balancer is configured. " : "Even S2 balancer is not configured. ";
      $evenStatusMsg.= $hasEven ? "Even data collection has started - try to avoid alterations." : "No even data yet collected";
      $index = 0; // incremented in makeXXXJSON methods
      $json.= $this->makeFormFieldJSON($index, "pageWarning", "", "", $pageWarning, "", "", "",  "", "", "[]", false, [], [], "", "", "", "") . ",";
      $json.= $this->makeFormFieldJSON($index, "select", "wt_Step2BalancerRespMax", "invertedOddRespMax", "max odd #", "", "", $oddNo, "", "", $selectJSON, false, [], [], "", "", "", "") . ",";
      $json.= $this->makeFormFieldJSON($index, "pageWarning", "", "", $oddStatusMsg, "", "", "",  "", "", "[]", false, [], [], "", "", "", "") . ",";
      $json.= $this->makeFormFieldJSON($index, "select", "wt_Step2BalancerRespMax", "invertedEvenRespMax", "max even #", "", "", $evenNo, "", "", $selectJSON, false, [], [], "", "", "", "") . ",";
      $json.= $this->makeFormFieldJSON($index, "pageWarning", "", "", $evenStatusMsg, "", "", "",  "", "", "[]", false, [], [], "", "", "", "");
    $json.= "]}";
    return $json;        
  }

  function getStep4Content() {
    $characterLimitSelectJSON = $this->metadataConverter->getSpacedSelectJSONArray(5000);
    $json = "{\"sectionTitle\": \"Step4 content\",\"sectionName\": \"step4Content\",\"exptId\":".$this->exptId.",\"formFields\":[";
      $pageWarning = "Note: try to keep labels and tooltips as short as possible. Messages can be longer instructions. "
          . "Some values in this page may not be used, depending on how surveys are configured.";
      $index = 0; // incremented in makeXXXJSON methods
      $json.= $this->makeFormFieldJSON($index, "pageWarning", "", "", $pageWarning, "", "", "",  "", "", "[]", false, [], [], "", "", "") . ",";
      $json.= $this->makeFormFieldJSON($index, "text", "edContentDefs_refactor", "step4_startMsg", "start message", "", $this->eModel->step4_startMsg, "", "", "", "[]", false, [], [], "", "", "", "") . ",";
      $json.= $this->makeFormFieldJSON($index, "text", "edContentDefs_refactor", "step4_startBLabel", "start button label", "", $this->eModel->step4_startBLabel, "", "", "", "[]", false, [], [], "", "", "", "") . ",";
      $json.= $this->makeFormFieldJSON($index, "text", "edContentDefs_refactor", "step4_judgeNumberMsg", "judge number message", "", $this->eModel->step4_judgeNumberMsg, "", "", "", "[]", false, [], [], "", "", "", "") . ",";
      $json.= $this->makeFormFieldJSON($index, "text", "edContentDefs_refactor", "step4_nextBLabel", "next button label", "", $this->eModel->step4_nextBLabel, "", "", "", "[]", false, [], [], "", "", "", "") . ",";
      $json.= $this->makeFormFieldJSON($index, "text", "edContentDefs_refactor", "step4_endBLabel", "end judging phase button label", "", $this->eModel->step4_endBLabel, "", "", "", "[]", false, [], [], "", "", "", "") . ",";
      $json.= $this->makeFormFieldJSON($index, "text", "edContentDefs_refactor", "step4_finalMsg", "end of judging phase message", "", $this->eModel->step4_finalMsg, "", "", "", "[]", false, [], [], "", "", "", "") . ",";
      $json.= $this->makeFormFieldJSON($index, "text", "edContentDefs_refactor", "step4_closedMsg", "closed message", "", $this->eModel->step4_closedMsg, "", "", "", "[]", false, [], [], "", "", "", "");
    $json.= "]}";
    return $json;        
  }
    
  function getStep4JudgeAlignment() {
    global $igrtSqli;
    $s4NoCategories = $this->eModel->s4NoCategories;
    $sql = sprintf("SELECT * FROM edAlignmentControlLabels WHERE exptId='%s' AND step=4 ORDER BY displayOrder ASC", $this->exptId);
    $result = $igrtSqli->query($sql);
    $noCategoriesCheck = $result ? $result->num_rows : 0;
    if ($noCategoriesCheck == 0) {
      // houston, so create 3 defaults
      $insertSql = sprintf("INSERT INTO edAlignmentControlLabels (exptId, step, label, displayOrder) "
          . "VALUES ('%s', 4,'content', 1),"
          . "('%s', 4, 'form', 2),"
          . "('%s', 4, 'grammar', 3)",
          $this->exptId, $this->exptId, $this->exptId);
      $igrtSqli->query($insertSql);
      $result = $igrtSqli->query($sql);
    }
    else {
      if ($noCategoriesCheck != $s4NoCategories) { $s4NoCategories = $noCategoriesCheck; }
    }
    $categoryItems = [];
    while ($row = $result->fetch_object()) {
      $subItemFieldValues = array(
        'textValue'=> $row->label,
        'subItemLabel'=> "category # ".$row->displayOrder,
        'dimension1'=> $row->displayOrder,
        'dimension2'=> 4
      );
      array_push($categoryItems, $subItemFieldValues);
    }        
    $categoryNoSelectJSON = $this->metadataConverter->getSelectJSONArray(1, 10);
    $characterLimitSelectJSON = $this->metadataConverter->getSpacedSelectJSONArray(5000);
    $json = "{\"sectionTitle\": \"Step4 J alignment\",\"sectionName\": \"s4JudgeAlignment\",\"exptId\":".$this->exptId.",\"formFields\":[";
      $pageWarning = "warning: changing the number of category items in this page will only be actuated by clicking 'save and operate on changes' in the navigation footer.";
      $index = 0; // incremented in makeXXXJSON methods
      $json.= $this->makeFormFieldJSON($index, "checkbox", "edExptStatic_refactor", "useS4CharacterLimit", "use character minimum for judge reason", $this->eModel->useS4CharacterLimit, "", "", "yes", "no", "[]", false, [], [], "", "", "", "") . ",";
      $json.= $this->makeFormFieldJSON($index, "select", "edExptStatic_refactor", "s4CharacterLimitValue", "minimum characters for reason", "", "", $this->eModel->s4CharacterLimitValue, "", "", $characterLimitSelectJSON, false, [], [], "", "", "", "") . ",";
      $json.= $this->makeFormFieldJSON($index, "text", "edContentDefs_refactor", "step4_reasonGuidance", "reason instruction (mention character limit if used)", "", $this->eModel->step4_reasonGuidance, "", "", "", "[]", false, [], [], "", "", "", "") . ",";
	  $json.= $this->makeFormFieldJSON($index, "checkbox", "edExptStatic_refactor", "s4RandomiseSide", "randomise P/NP side", $this->eModel->s4RandomiseSide, "", "", "yes", "no", "[]", false, [], [], "", "", "", "") . ",";
      $json.= $this->makeFormFieldJSON($index, "checkbox", "edExptStatic_refactor", "useS4IndividualTurn", "use single turns (implies using jqi or alignment)", $this->eModel->useS4IndividualTurn, "", "", "yes", "no", "[]", false, [], [], "", "", "", "") . ",";
      $json.= $this->makeFormFieldJSON($index, "checkbox", "edExptStatic_refactor", "useS4Intention", "get judge question intention(jqi)", $this->eModel->useS4Intention, "", "", "yes", "no", "[]", false, [], [], "", "", "", "") . ",";
      $json.= $this->makeFormFieldJSON($index, "checkbox", "edExptStatic_refactor", "useS4IntentionMin", "use character minimum for jqi", $this->eModel->useS4IntentionMin, "", "", "yes", "no", "[]", false, [], [], "", "", "", "") . ",";
      $json.= $this->makeFormFieldJSON($index, "select", "edExptStatic_refactor", "s4IntentionMin", "minimum jqi character limit ", "", "", $this->eModel->s4IntentionMin, "", "", $characterLimitSelectJSON, false, [], [], "", "", "", "") . ",";
      $json.= $this->makeFormFieldJSON($index, "text", "edContentDefs_refactor", "step4IntentionLimitGuidance", "guidance for intention minimum", "", $this->eModel->step4IntentionLimitGuidance, "", "", "", "[]", false, [], [], "", "", "", "") . ",";      
      $json.= $this->makeFormFieldJSON($index, "text", "edContentDefs_refactor", "s4IntentionLabel", "instruction for jqi", "", $this->eModel->s4IntentionLabel, "", "", "", "[]", false, [], [], "", "", "", "") . ",";      
      $json.= $this->makeFormFieldJSON($index, "checkbox", "edExptStatic_refactor", "useS4AlignmentControl", "get alignment rating from judge (jar)", $this->eModel->useS4AlignmentControl, "", "", "yes", "no", "[]", false, [], [], "", "", "", "") . ",";
      $json.= $this->makeFormFieldJSON($index, "text", "edContentDefs_refactor", "s4AlignmentLabel", "instruction for jar", "", $this->eModel->s4AlignmentLabel, "", "", "", "[]", false, [], [], "", "", "", "") . ",";
      $json.= $this->makeFormFieldJSON($index, "text", "edContentDefs_refactor", "s4AlignmentNoneLabel", "no alignment label", "", $this->eModel->s4AlignmentNoneLabel, "", "", "", "[]", false, [], [], "", "", "", "") . ",";
      $json.= $this->makeFormFieldJSON($index, "text", "edContentDefs_refactor", "s4AlignmentPartlyLabel", "part alignment label", "", $this->eModel->s4AlignmentPartlyLabel, "", "", "", "[]", false, [], [], "", "", "", "") . ",";
      $json.= $this->makeFormFieldJSON($index, "text", "edContentDefs_refactor", "s4AlignmentMostlyLabel", "mostly alignment label", "", $this->eModel->s4AlignmentMostlyLabel, "", "", "", "[]", false, [], [], "", "", "", "") . ",";
      $json.= $this->makeFormFieldJSON($index, "text", "edContentDefs_refactor", "s4AlignmentCompletelyLabel", "complete alignment label", "", $this->eModel->s4AlignmentCompletelyLabel, "", "", "", "[]", false, [], [], "", "", "", "") . ",";
      $json.= $this->makeFormFieldJSON($index, "checkbox", "edExptStatic_refactor", "useS4QCategoryControl", "get rating categories from judge (jrc)", $this->eModel->useS4QCategoryControl, "", "", "yes", "no", "[]", false, [], [], "", "", "", "") . ",";
      $json.= $this->makeFormFieldJSON($index, "text", "edContentDefs_refactor", "s4QCategoryLabel", "instruction for jrc", "", $this->eModel->s4QCategoryLabel, "", "", "", "[]", false, [], [], "", "", "", "") . ",";
      $json.= $this->makeFormFieldJSON($index, "select", "edExptStatic_refactor", "s4NoCategories", "no of jrc categories", "", "", $s4NoCategories, "", "", $categoryNoSelectJSON, false, [], [], "", "", "", "")."," ;
      $subItemFields = array(
        'dimension1Name' => 'displayOrder',
        'dimension2Name' => 'step'
      );
      $lastFF = count($categoryItems);
      for ($i=0; $i<$lastFF; $i++) {
        $subItemLabel = $categoryItems[$i]['subItemLabel'];
        $textValue = $categoryItems[$i]['textValue'];
        $json.= $this->makeFormFieldJSON($index, "text", "edAlignmentControlLabels", "label", $subItemLabel, "", $textValue, "", "", "", "[]", true, $subItemFields, $categoryItems[$i], "", "", "", "").",";
      }
      $json.= $this->makeFormFieldJSON($index, "pageWarning", "", "", $pageWarning, "", "", "",  "", "", "[]", false, [], [], "", "", "", "");
    $json.= "]}";
    return $json;
  }
    
  function getSurveys() {
    $json = "{\"sectionTitle\": \"Survey settings\",\"sectionName\": \"surveys\",\"exptId\":".$this->exptId.",\"formFields\":[";
      $index = 0; // incremented in makeXXXJSON methods
      $pageWarning = "warning: if you select or de-select a form/survey you must click 'save and operate on changes' in the navigation footer BEFORE cloning or configuring any form/survey.";
      $json.= $this->makeStepFormTypesJSON($index);
      $json.= $this->makeFormFieldJSON($index, "pageWarning", "", "", $pageWarning, "", "", "",  "", "", "[]", false, [], [], "", "", "") . ",";
      
      
      $json.= $this->makeFormFieldJSON($index, "checkbox", "edExptStatic_refactor", "step1PreForm", "use pre-step1 survey", $this->eModel->step1PreForm, "", "", "yes", "no", "[]", false, [], [], "", "", "") . ",";
      $json.= $this->makeFormFieldJSON($index, "button", "", "", "clone pre-step1 survey", "", "", "", "", "", "[]", false, [], [], "1_3_1_2", "clone", "2") . ",";
      $json.= $this->makeFormFieldJSON($index, "button", "", "", "configure pre-step1 survey", "", "", "", "", "", "[]", false, [], [], "1_3_2_2", "configure", "2") . ",";
      $json.= $this->makeFormFieldJSON($index, "checkbox", "edExptStatic_refactor", "step1PostForm", "use post-step1 survey", $this->eModel->step1PostForm, "", "", "yes", "no", "[]", false, [], [], "", "", "") . ",";
      $json.= $this->makeFormFieldJSON($index, "button", "", "", "clone post-step1 survey", "", "", "", "", "", "[]", false, [], [], "1_3_1_3", "clone", "3") . ",";
      $json.= $this->makeFormFieldJSON($index, "button", "", "", "configure post-step1 survey", "", "", "", "", "", "[]", false, [], [], "1_3_2_3", "configure", "3") . ",";

      $json.= $this->makeFormFieldJSON($index, "checkbox", "edExptStatic_refactor", "step2PreForm", "use pre-step2 survey", $this->eModel->step2PreForm, "", "", "yes", "no", "[]", false, [], [], "", "", "") . ",";
      $json.= $this->makeFormFieldJSON($index, "button", "", "", "clone pre-step2 survey", "", "", "", "", "", "[]", false, [], [], "1_3_1_6", "clone", "6") . ",";
      $json.= $this->makeFormFieldJSON($index, "button", "", "", "configure pre-step2 survey", "", "", "", "", "", "[]", false, [], [], "1_3_2_6", "configure", "6") . ",";
      $json.= $this->makeFormFieldJSON($index, "checkbox", "edExptStatic_refactor", "step2PostForm", "use post-step2 survey", $this->eModel->step2PostForm, "", "", "yes", "no", "[]", false, [], [], "", "", "") . ",";
      $json.= $this->makeFormFieldJSON($index, "button", "", "", "clone post-step2 survey", "", "", "", "", "", "[]", false, [], [], "1_3_1_7", "clone", "7") . ",";
      $json.= $this->makeFormFieldJSON($index, "button", "", "", "configure post-step2 survey", "", "", "", "", "", "[]", false, [], [], "1_3_2_7", "configure", "7") . ",";

      $json.= $this->makeFormFieldJSON($index, "checkbox", "edExptStatic_refactor", "step2PreInvert", "use inv pre-step2 survey", $this->eModel->step2PreInvert, "", "", "yes", "no", "[]", false, [], [], "", "", "") . ",";
      $json.= $this->makeFormFieldJSON($index, "button", "", "", "clone inv pre-step2 survey", "", "", "", "", "", "[]", false, [], [], "1_3_1_12", "clone", "12") . ",";
      $json.= $this->makeFormFieldJSON($index, "button", "", "", "configure inv pre-step2 survey", "", "", "", "", "", "[]", false, [], [], "1_3_2_12", "configure", "12") . ",";
      $json.= $this->makeFormFieldJSON($index, "checkbox", "edExptStatic_refactor", "step2PostInvert", "use inv post-step2 survey", $this->eModel->step2PostInvert, "", "", "yes", "no", "[]", false, [], [], "", "", "") . ",";
      $json.= $this->makeFormFieldJSON($index, "button", "", "", "clone inv post-step2 survey", "", "", "", "", "", "[]", false, [], [], "1_3_1_13", "clone", "13") . ",";
      $json.= $this->makeFormFieldJSON($index, "button", "", "", "configure inv post-step2 survey", "", "", "", "", "", "[]", false, [], [], "1_3_2_13", "configure", "13") . ",";

     $json.= $this->makeFormFieldJSON($index, "checkbox", "edExptStatic_refactor", "step4PreForm", "use pre-step4 survey", $this->eModel->step4PreForm, "", "", "yes", "no", "[]", false, [], [], "", "", "") . ",";
      $json.= $this->makeFormFieldJSON($index, "button", "", "", "clone pre-step4 survey", "", "", "", "", "", "[]", false, [], [], "1_3_1_10", "clone", "10") . ",";
      $json.= $this->makeFormFieldJSON($index, "button", "", "", "configure pre-step4 survey", "", "", "", "", "", "[]", false, [], [], "1_3_2_10", "configure", "10") . ",";
      $json.= $this->makeFormFieldJSON($index, "checkbox", "edExptStatic_refactor", "step4PostForm", "use post-step4 survey", $this->eModel->step4PostForm, "", "", "yes", "no", "[]", false, [], [], "", "", "") . ",";
      $json.= $this->makeFormFieldJSON($index, "button", "", "", "clone post-step4 survey", "", "", "", "", "", "[]", false, [], [], "1_3_1_11", "clone", "11") . ",";
      $json.= $this->makeFormFieldJSON($index, "button", "", "", "configure post-step4 survey", "", "", "", "", "", "[]", false, [], [], "1_3_2_11", "configure", "11");
      $json.= "]}";
    return $json;    
  }
  
  function getSurveysReview() {
    $dataSummary = $this->getDataSummary();
    $json = "{\"sectionTitle\": \"Survey reviews\",\"sectionName\": \"surveyReviews\",\"exptId\":".$this->exptId.",\"formFields\":[";
    $targetLabel = "7_3_1";
    if ($dataSummary['hasData']) {
      // at least some of our forms have data
      $index = 0; // incremented in makeXXXJSON methods
	    if ($dataSummary['step1PreForm'] > 0) {
        $json.= $this->makeFormFieldJSON($index, "button", "", "", "view pre-step1 responses", "", "", "", "", "", "[]", false, [], [], $targetLabel."_2", "view", "2", "") . ",";        
      }
      else {
        $json.= $this->makeFormFieldJSON($index, "pageWarning", "", "", "No pre-step1 responses", "", "", "",  "", "", "[]", false, [], [], "", "", "", "") . ",";        
      }
      if ($dataSummary['step1PostForm'] > 0) {
        $json.= $this->makeFormFieldJSON($index, "button", "", "", "view post-step1 responses", "", "", "", "", "", "[]", false, [], [],$targetLabel."_3", "view", "3", "") . ",";        
      }
      else {
        $json.= $this->makeFormFieldJSON($index, "pageWarning", "", "", "No post-step1 responses", "", "", "",  "", "", "[]", false, [], [], "", "", "", "") . ",";        
      }
      
      if ($dataSummary['step2PreForm'] > 0) {
        $json.= $this->makeFormFieldJSON($index, "button", "", "", "view pre-step2 responses", "", "", "", "", "", "[]", false, [], [], $targetLabel."_6", "view", "6", "") . ",";        
      }
      else {
        $json.= $this->makeFormFieldJSON($index, "pageWarning", "", "", "No pre-step2 responses", "", "", "",  "", "", "[]", false, [], [], "", "", "", "") . ",";        
      }
      if ($dataSummary['step2PostForm'] > 0) {
        $json.= $this->makeFormFieldJSON($index, "button", "", "", "view post-step2 responses", "", "", "", "", "", "[]", false, [], [], $targetLabel."_7", "view", "7", "") . ",";        
      }
      else {
        $json.= $this->makeFormFieldJSON($index, "pageWarning", "", "", "No post-step2 responses", "", "", "",  "", "", "[]", false, [], [], "", "", "", "") . ",";        
      }

      if ($dataSummary['step2PreInvert'] > 0) {
        $json.= $this->makeFormFieldJSON($index, "button", "", "", "view inv pre-step2 responses", "", "", "", "", "", "[]", false, [], [], $targetLabel."_12", "view", "12", "") . ",";        
      }
      else {
        $json.= $this->makeFormFieldJSON($index, "pageWarning", "", "", "No inv pre-step2 responses", "", "", "",  "", "", "[]", false, [], [], "", "", "", "") . ",";        
      }
      if ($dataSummary['step2PostInvert'] > 0) {
        $json.= $this->makeFormFieldJSON($index, "button", "", "", "view inv post-step2 responses", "", "", "", "", "", "[]", false, [], [], $targetLabel."_13", "view", "13", "") . ",";        
      }
      else {
        $json.= $this->makeFormFieldJSON($index, "pageWarning", "", "", "No inv post-step2 responses", "", "", "",  "", "", "[]", false, [], [], "", "", "", "") . ",";        
      }

      if ($dataSummary['step4PreForm'] > 0) {
        $json.= $this->makeFormFieldJSON($index, "button", "", "", "view pre-step4 responses", "", "", "", "", "", "[]", false, [], [], $targetLabel."_10", "view", "10", "") . ",";        
      }
      else {
        $json.= $this->makeFormFieldJSON($index, "pageWarning", "", "", "No pre-step4 responses", "", "", "",  "", "", "[]", false, [], [], "", "", "", "") . ",";        
      }
      if ($dataSummary['step4PostForm'] > 0) {
        $json.= $this->makeFormFieldJSON($index, "button", "", "", "view post-step4 responses", "", "", "", "", "", "[]", false, [], [], $targetLabel."_11  ", "view", "11", "");        
      }
      else {
        $json.= $this->makeFormFieldJSON($index, "pageWarning", "", "", "No post-step4 responses", "", "", "",  "", "", "[]", false, [], [], "", "", "", "");        
      }
    }
    else {
      // none of the forms have data
      $pageWarning = "None of the forms in the experiment have been activated, or none of them have any data.";
      $json.= $this->makeFormFieldJSON($index, "pageWarning", "", "", $pageWarning, "", "", "",  "", "", "[]", false, [], [], "", "", "", "");
    }
    $json.= "]}";
    return $json;    
  }
  
  function getStep1Review() {
    global $igrtSqli;
    $json = "{\"sectionTitle\": \"Step1 review\",\"sectionName\": \"s1Review\",\"exptId\":".$this->exptId.",\"formFields\":[";
    $index = 0; // incremented in makeXXXJSON methods
    $qry = sprintf("SELECT * FROM edSessions WHERE exptId='%s' ORDER BY dayNo ASC, sessionNo ASC", $this->exptId);
    $result = $igrtSqli->query($qry);
    $evenShown = false;
    $oddShown = false;
    if ($result->num_rows > 0) {
      while ($row = $result->fetch_object()) {
        $dayNo = $row->dayNo;
        $sessionNo = $row->sessionNo;
        // note that injected experiments may have data in the reviewed data tables but not the raw data tables
        $oddMarkedDataQry = sprintf("SELECT * FROM md_dataStep1reviewed WHERE exptId='%s' AND jType=1 AND dayNo='%s' AND sessionNo='%s'", $this->exptId, $row->dayNo, $row->sessionNo);
        $oResult = $igrtSqli->query($oddMarkedDataQry);
        $hasOddMarked = $oResult ? true : false;
        $evenMarkedDataQry = sprintf("SELECT * FROM md_dataStep1reviewed WHERE exptId='%s' AND jType=0 AND dayNo='%s' AND sessionNo='%s'", $this->exptId, $row->dayNo, $row->sessionNo);
        $eResult = $igrtSqli->query($evenMarkedDataQry);
        $hasEvenMarked = $eResult ? true : false;
        $oddRawDataQry = sprintf("SELECT * FROM dataSTEP1 WHERE exptId='%s' AND jType=1 AND dayNo='%s' AND sessionNo='%s'", $this->exptId, $row->dayNo, $row->sessionNo);
        $oResult = $igrtSqli->query($oddRawDataQry);
        $hasOddRaw = $oResult ? true : false;
        $evenRawDataQry = sprintf("SELECT * FROM dataSTEP1 WHERE exptId='%s' AND jType=0 AND dayNo='%s' AND sessionNo='%s'", $this->exptId, $row->dayNo, $row->sessionNo);
        $eResult = $igrtSqli->query($evenRawDataQry);
        $hasEvenRaw = $eResult ? true : false;
        //$json.=$oddMarkedDataQry.';'.$oddRawDataQry.';'.$evenMarkedDataQry.';'.$evenRawDataQry.';';
        if (($hasEvenRaw) || ($hasEvenMarked)) {
          if ($evenShown || $oddShown) { $json.=","; }
          $evenShown = true;
          $evenMsg = (($row->step1EvenMarked) && ($hasEvenMarked)) ? "re-review even interrogator question sets" : "first review even interrogator question sets";
          $json.= $this->makeFormFieldJSON($index, "pageWarning", "", "", $evenMsg, "", "", "",  "", "", "[]", false, [], [], "", "", "", "") . ",";
          $json.= $this->makeFormFieldJSON($index, "button", "", "", "", "", "", "", "", "", "[]", false, [], [], "3_3_1_0_".$row->dayNo."_".$row->sessionNo, "even review day".$row->dayNo." session ".$row->sessionNo,"", "", "");        
        }
        if (($hasOddRaw) || ($hasOddMarked)) {
          if ($evenShown || $oddShown) { $json.=","; }
          $oddShown = true;
          $oddMsg = (($row->step1OddMarked) && ($hasOddMarked)) ? "re-review odd interrogator question sets" : "first review odd interrogator question sets";
          $json.= $this->makeFormFieldJSON($index, "pageWarning", "", "", $oddMsg, "", "", "",  "", "", "[]", false, [], [], "", "", "", "") . ",";
          $json.= $this->makeFormFieldJSON($index, "button", "", "", "", "", "", "", "", "", "[]", false, [], [], "3_3_1_1_".$row->dayNo."_".$row->sessionNo, "odd review day".$row->dayNo." session ".$row->sessionNo, "", "");
        }
      }
    }
    $json.= "]}";
    return $json;    
  }
  
  function getStep2pMonitor() {
    global $igrtSqli;
    $json = "{\"sectionTitle\": \"Step2 P monitor\",\"sectionName\": \"s2pMonitor\",\"exptId\":".$this->exptId.",\"formFields\":[";
    $index = 0; // incremented in makeXXXJSON methods
    $erQry = sprintf("SELECT * FROM dataSTEP2 WHERE exptId='%s' AND jType=0", $this->exptId);
    $erResult = $igrtSqli->query($erQry);
    $hasEvenRaw = $erResult ? true : false;
    $orQry = sprintf("SELECT * FROM dataSTEP2 WHERE exptId='%s' AND jType=1", $this->exptId);
    $orResult = $igrtSqli->query($orQry);
    $hasOddRaw = $orResult ? true : false;
    if ($hasEvenRaw) {
      $evenMsg = "monitor even S2 Pretenders";
      $json.= $this->makeFormFieldJSON($index, "pageWarning", "", "", $evenMsg, "", "", "",  "", "", "[]", false, [], [], "", "", "", "") . ",";
      $json.= $this->makeFormFieldJSON($index, "button", "", "", "", "", "", "", "", "", "[]", false, [], [], "5_3_0_0", "even P monitor","", "", ""). ",";        
    }
    else {
      $evenMsg = "no data collection started for even S2 Pretenders";
      $json.= $this->makeFormFieldJSON($index, "pageWarning", "", "", $evenMsg, "", "", "",  "", "", "[]", false, [], [], "", "", "", "") . ",";     
    }
    if ($hasOddRaw) {
      $oddMsg = "monitor odd S2 Pretenders";
      $json.= $this->makeFormFieldJSON($index, "pageWarning", "", "", $oddMsg, "", "", "",  "", "", "[]", false, [], [], "", "", "", "") . ",";
      $json.= $this->makeFormFieldJSON($index, "button", "", "", "", "", "", "", "", "", "[]", false, [], [], "5_3_0_1", "odd P monitor", "", "");
    }
    else {
      $oddMsg = "no data collection started for odd S2 Pretenders";
      $json.= $this->makeFormFieldJSON($index, "pageWarning", "", "", $oddMsg, "", "", "",  "", "", "[]", false, [], [], "", "", "", "");     
    }
    $json.= "]}";
    return $json;      
  }
  
  function getStep2pReview() {
    global $igrtSqli;
    $json = "{\"sectionTitle\": \"Step2 P review\",\"sectionName\": \"s2pReview\",\"exptId\":".$this->exptId.",\"formFields\":[";
    $index = 0; // incremented in makeXXXJSON methods
    $erQry = sprintf("SELECT * FROM dataSTEP2 WHERE exptId='%s' AND jType=0", $this->exptId);
    $erResult = $igrtSqli->query($erQry);
    $hasEvenRaw = $erResult ? true : false;
    $emQry = sprintf("SELECT * FROM md_dataStep2reviewed WHERE exptId='%s' AND jType=0", $this->exptId);
    $emResult = $igrtSqli->query($emQry);
    $hasEvenMarked = $emResult? true : false;
    $orQry = sprintf("SELECT * FROM dataSTEP2 WHERE exptId='%s' AND jType=1", $this->exptId);
    $orResult = $igrtSqli->query($orQry);
    $hasOddRaw = $orResult ? true : false;
    $omQry = sprintf("SELECT * FROM md_dataStep2reviewed WHERE exptId='%s' AND jType=1", $this->exptId);
    $omResult = $igrtSqli->query($omQry);
    $hasOddMarked = $omResult ? true : false;
    //$json.=$emQry.';'.$erQry.';'.$omQry.';'.$orQry.';';
    if (($hasEvenRaw) || ($hasEvenMarked)) {
      $evenMsg = ($hasEvenMarked) ? "re-review even S2 Pretenders" : "first review even S2 Pretenders";
      $json.= $this->makeFormFieldJSON($index, "pageWarning", "", "", $evenMsg, "", "", "",  "", "", "[]", false, [], [], "", "", "", "") . ",";
      $json.= $this->makeFormFieldJSON($index, "button", "", "", "", "", "", "", "", "", "[]", false, [], [], "5_3_1_0", "even P review","", "", ""). ",";        
    }
    else {
      $evenMsg = "no data for even S2 Pretenders";
      $json.= $this->makeFormFieldJSON($index, "pageWarning", "", "", $evenMsg, "", "", "",  "", "", "[]", false, [], [], "", "", "", "") . ",";     
    }
    if (($hasOddRaw) || ($hasOddMarked)) {
      $oddMsg = ($hasOddMarked) ? "re-review odd S2 Pretenders" : "first review odd S2 Pretenders";
      $json.= $this->makeFormFieldJSON($index, "pageWarning", "", "", $oddMsg, "", "", "",  "", "", "[]", false, [], [], "", "", "", "") . ",";
      $json.= $this->makeFormFieldJSON($index, "button", "", "", "", "", "", "", "", "", "[]", false, [], [], "5_3_1_1", "odd P review", "", "");
    }
    else {
      $oddMsg = "no data for odd S2 Pretenders";
      $json.= $this->makeFormFieldJSON($index, "pageWarning", "", "", $oddMsg, "", "", "",  "", "", "[]", false, [], [], "", "", "", "");      
    }
    $json.= "]}";
    return $json;    
  }
  
  function getStep2npMonitor() {
    global $igrtSqli;
    $json = "{\"sectionTitle\": \"Step2 NP monitor\",\"sectionName\": \"s2npMonitor\",\"exptId\":".$this->exptId.",\"formFields\":[";
    $index = 0; // incremented in makeXXXJSON methods
    $erQry = sprintf("SELECT * FROM dataSTEP2inverted WHERE exptId='%s' AND jType=0", $this->exptId);
    $erResult = $igrtSqli->query($erQry);
    $hasEvenRaw = $erResult ? true : false;
    $orQry = sprintf("SELECT * FROM dataSTEP2inverted WHERE exptId='%s' AND jType=1", $this->exptId);
    $orResult = $igrtSqli->query($orQry);
    $hasOddRaw = $orResult ? true : false;
    if ($hasEvenRaw) {
      $evenMsg = "monitor even iS2 Non-Pretenders";
      $json.= $this->makeFormFieldJSON($index, "pageWarning", "", "", $evenMsg, "", "", "",  "", "", "[]", false, [], [], "", "", "", "") . ",";
      $json.= $this->makeFormFieldJSON($index, "button", "", "", "", "", "", "", "", "", "[]", false, [], [], "5_3_2_0", "even NP monitor","", "", ""). ",";        
    }
    else {
      $evenMsg = "no data collection started for even iS2 Non-Pretenders";
      $json.= $this->makeFormFieldJSON($index, "pageWarning", "", "", $evenMsg, "", "", "",  "", "", "[]", false, [], [], "", "", "", "") . ",";     
    }
    if ($hasOddRaw) {
      $oddMsg = "monitor odd iS2 Pretenders";
      $json.= $this->makeFormFieldJSON($index, "pageWarning", "", "", $oddMsg, "", "", "",  "", "", "[]", false, [], [], "", "", "", "") . ",";
      $json.= $this->makeFormFieldJSON($index, "button", "", "", "", "", "", "", "", "", "[]", false, [], [], "5_3_2_1", "odd NP monitor", "", "");
    }
    else {
      $oddMsg = "no data collection started for odd iS2 Pretenders";
      $json.= $this->makeFormFieldJSON($index, "pageWarning", "", "", $oddMsg, "", "", "",  "", "", "[]", false, [], [], "", "", "", "");     
    }
    $json.= "]}";
    return $json;        
  }
  
  function getStep2npReview() {
    global $igrtSqli;
    $json = "{\"sectionTitle\": \"Inv Step2 NP review\",\"sectionName\": \"is2npReview\",\"exptId\":".$this->exptId.",\"formFields\":[";
    $index = 0; // incremented in makeXXXJSON methods
    $erQry = sprintf("SELECT * FROM dataSTEP2inverted WHERE exptId='%s' AND jType=0", $this->exptId);
    $erResult = $igrtSqli->query($erQry);
    $hasEvenRaw = $erResult ? true : false;
    $emQry = sprintf("SELECT * FROM md_invertedStep2reviewed WHERE exptId='%s' AND jType=0", $this->exptId);
    $emResult = $igrtSqli->query($emQry);
    $hasEvenMarked = $emResult ? true : false;
    $orQry = sprintf("SELECT * FROM dataSTEP2inverted WHERE exptId='%s' AND jType=1", $this->exptId);
    $orResult = $igrtSqli->query($orQry);
    $hasOddRaw = $orResult ? true : false;
    $omQry = sprintf("SELECT * FROM md_invertedStep2reviewed WHERE exptId='%s' AND jType=1", $this->exptId);
    $omResult = $igrtSqli->query($omQry);
    $hasOddMarked = $omResult ? true : false;
    //$json.=$emQry.';'.$erQry.';'.$omQry.';'.$orQry.';';
    if (($hasEvenRaw) || ($hasEvenMarked)) {
      $evenMsg = ($hasEvenMarked) ? "re-review even iS2 Non-Pretenders" : "first review even iS2 Non-Pretenders";
      $json.= $this->makeFormFieldJSON($index, "pageWarning", "", "", $evenMsg, "", "", "",  "", "", "[]", false, [], [], "", "", "", "") . ",";
      $json.= $this->makeFormFieldJSON($index, "button", "", "", "", "", "", "", "", "", "[]", false, [], [], "5_3_3_0", "even NP review","", "", ""). ",";        
    }
    else {
      $evenMsg = "no data for even iS2 Non-Pretenders";
      $json.= $this->makeFormFieldJSON($index, "pageWarning", "", "", $evenMsg, "", "", "",  "", "", "[]", false, [], [], "", "", "", "") . ",";     
    }
    if (($hasOddRaw) || ($hasOddMarked)) {
      $oddMsg = ($hasOddMarked) ? "re-review odd iS2 Non-Pretenders" : "first review odd iS2 Non-Pretenders";
      $json.= $this->makeFormFieldJSON($index, "pageWarning", "", "", $oddMsg, "", "", "",  "", "", "[]", false, [], [], "", "", "", "") . ",";
      $json.= $this->makeFormFieldJSON($index, "button", "", "", "", "", "", "", "", "", "[]", false, [], [], "5_3_3_1", "odd NP review", "", "");
    }
    else {
      $oddMsg = "no data for odd iS2 Non-Pretenders";
      $json.= $this->makeFormFieldJSON($index, "pageWarning", "", "", $oddMsg, "", "", "",  "", "", "[]", false, [], [], "", "", "", "");      
    }
    $json.= "]}";
    return $json;        
  }
    
  function getStep3Datasource() {
    $useOddInvertedS2 = $this->eModel->useOddInvertedS2;
    $useEvenInvertedS2 = $this->eModel->useEvenInvertedS2;
    $json = "{\"sectionTitle\": \"Step3 P datasource\",\"sectionName\": \"s3Datasource\",\"exptId\":".$this->exptId.",\"formFields\":[";
      $pageWarning = "Inverted S2 responses can be used in Step4 to replace the original NP responses in Step1";
      $index = 0; // incremented in makeXXXJSON methods
      $json.= $this->makeFormFieldJSON($index, "pageWarning", "", "", $pageWarning, "", "", "",  "", "", "[]", false, [], [], "", "", "", "") . ",";
      $json.= $this->makeFormFieldJSON($index, "checkbox", "edExptStatic_refactor", "useOddInvertedS2", "use odd inverted NP ", $useOddInvertedS2, "", "", "yes", "no", "[]", false, [], [], "", "", "", "") . ",";
      $json.= $this->makeFormFieldJSON($index, "checkbox", "edExptStatic_refactor", "useEvenInvertedS2", "use even inverted NP ", $useEvenInvertedS2, "", "", "yes", "no", "[]", false, [], [], "", "", "", "") . "";
    $json.= "]}";
    return $json;    
  }
  
  function getStep3Shuffle() {
    global $igrtSqli;
    $sql = sprintf("SELECT * FROM wt_Step4JudgeCounts WHERE exptId='%s'", $this->exptId);
    $result = $igrtSqli->query($sql);
    if ($result->num_rows > 0) {
      $row = $result->fetch_object();
      $evenS4JudgeCount = $row->evenS4JudgeCount;
      $oddS4JudgeCount = $row->oddS4JudgeCount;
    }
    else {
      $evenS4JudgeCount = 1;
      $oddS4JudgeCount = 1;
    }
    $sql = sprintf("SELECT * FROM wt_Step4datasets WHERE exptId='%s' AND jType=0", $this->exptId);
    $result = $igrtSqli->query($sql);
    $evenShuffledMsg = $result ? "Even datasets have been shuffled" : "Even datasets have not been shuffled";
    $sql = sprintf("SELECT * FROM wt_Step4datasets WHERE exptId='%s' AND jType=0 AND rated=1", $this->exptId);
    $result = $igrtSqli->query($sql);
    $evenShuffledMsg.= $result ? "warning: even data collection has started " : "";
    $sql = sprintf("SELECT * FROM wt_Step4datasets WHERE exptId='%s' AND jType=1", $this->exptId);
    $result = $igrtSqli->query($sql);
    $oddShuffledMsg = $result ? "Odd datasets have been shuffled" : "Odd datasets have not been shuffled";
    $sql = sprintf("SELECT * FROM wt_Step4datasets WHERE exptId='%s' AND jType=1 AND rated=1", $this->exptId);
    $result = $igrtSqli->query($sql);
    $oddShuffledMsg.= $result ? "warning: odd data collection has started " : "";
    $selectJSON = $this->metadataConverter->getSelectJSONArray(1,200);
    $json = "{\"sectionTitle\": \"Step3 shuffle\",\"sectionName\": \"s3Shuffle\",\"exptId\":".$this->exptId.",\"formFields\":[";
      $pageWarning = "Note: the shuffle is performed when pressing `save and operate on changes`";
      $index = 0; // incremented in makeXXXJSON methods
      $json.= $this->makeFormFieldJSON($index, "pageWarning", "", "", $pageWarning, "", "", "",  "", "", "[]", false, [], [], "", "", "", "") . ",";
      $json.= $this->makeFormFieldJSON($index, "select", "wt_Step4JudgeCounts", "oddS4JudgeCount", "max odd #", "", "", $oddS4JudgeCount, "", "", $selectJSON, false, [], [], "", "", "", "") . ",";
      $json.= $this->makeFormFieldJSON($index, "pageWarning", "", "", $oddShuffledMsg, "", "", "",  "", "", "[]", false, [], [], "", "", "", "") . ",";
      $json.= $this->makeFormFieldJSON($index, "select", "wt_Step4JudgeCounts", "evenS4JudgeCount", "max even #", "", "", $evenS4JudgeCount, "", "", $selectJSON, false, [], [], "", "", "", ""). ",";
      $json.= $this->makeFormFieldJSON($index, "pageWarning", "", "", $evenShuffledMsg, "", "", "",  "", "", "[]", false, [], [], "", "", "", "");
    $json.= "]}";
    return $json;    
  }
  
  function getStep3SnowShuffle() {
    // initially, this snow shuffle is for a particular null experiment
    // but could be generalised later.
    global $igrtSqli;
    $sql = sprintf("SELECT * FROM wt_Step4JudgeCounts WHERE exptId='%s'", $this->exptId);
    $result = $igrtSqli->query($sql);
    if ($result->num_rows > 0) {
      $row = $result->fetch_object();
      $evenS4JudgeCount = $row->evenS4JudgeCount;
      $oddS4JudgeCount = $row->oddS4JudgeCount;
    }
    else {
      $evenS4JudgeCount = 1;
      $oddS4JudgeCount = 1;
    }
    $sql = sprintf("SELECT * FROM wt_Step4datasets WHERE exptId='%s' AND jType=0", $this->exptId);
    $result = $igrtSqli->query($sql);
    $evenShuffledMsg = $result ? "Even datasets have been shuffled" : "Even datasets have not been shuffled";
    $sql = sprintf("SELECT * FROM wt_Step4datasets WHERE exptId='%s' AND jType=0 AND rated=1", $this->exptId);
    $result = $igrtSqli->query($sql);
    $evenShuffledMsg.= $result ? "warning: even data collection has started " : "";
    $sql = sprintf("SELECT * FROM wt_Step4datasets WHERE exptId='%s' AND jType=1", $this->exptId);
    $result = $igrtSqli->query($sql);
    $oddShuffledMsg = $result ? "Odd datasets have been shuffled" : "Odd datasets have not been shuffled";
    $sql = sprintf("SELECT * FROM wt_Step4datasets WHERE exptId='%s' AND jType=1 AND rated=1", $this->exptId);
    $result = $igrtSqli->query($sql);
    $oddShuffledMsg.= $result ? "warning: odd data collection has started " : "";
    $selectJSON = $this->metadataConverter->getSelectJSONArray(1,200);
    $json = "{\"sectionTitle\": \"snow shuffle\",\"sectionName\": \"snowShuffle\",\"exptId\":".$this->exptId.",\"formFields\":[";
      $pageWarning = "Note: the shuffle is performed when pressing `save and operate on changes`";
      $index = 0; // incremented in makeXXXJSON methods
      $json.= $this->makeFormFieldJSON($index, "pageWarning", "", "", $pageWarning, "", "", "",  "", "", "[]", false, [], [], "", "", "", "") . ",";
      $json.= $this->makeFormFieldJSON($index, "select", "wt_Step4JudgeCounts", "oddS4JudgeCount", "max odd #", "", "", $oddS4JudgeCount, "", "", $selectJSON, false, [], [], "", "", "", "") . ",";
      $json.= $this->makeFormFieldJSON($index, "pageWarning", "", "", $oddShuffledMsg, "", "", "",  "", "", "[]", false, [], [], "", "", "", "") . ",";
      $json.= $this->makeFormFieldJSON($index, "select", "wt_Step4JudgeCounts", "evenS4JudgeCount", "max even #", "", "", $evenS4JudgeCount, "", "", $selectJSON, false, [], [], "", "", "", ""). ",";
      $json.= $this->makeFormFieldJSON($index, "pageWarning", "", "", $evenShuffledMsg, "", "", "",  "", "", "[]", false, [], [], "", "", "", "");
    $json.= "]}";
    return $json;        
  }
  
  function getStep3LEShuffle() {
    // the linked Shuffle is used in experiments 327<->328 and 329<->330 where Step2 is bypassed,
    // and instead Step1 data is mixed from 2 experiments to form a Step4
    global $igrtSqli;
    $sql = sprintf("SELECT * FROM wt_Step4JudgeCounts WHERE exptId='%s'", $this->exptId);
    $result = $igrtSqli->query($sql);
    if ($result->num_rows > 0) {
      $row = $result->fetch_object();
      $evenS4JudgeCount = $row->evenS4JudgeCount;
      $oddS4JudgeCount = $row->oddS4JudgeCount;
    }
    else {
      $evenS4JudgeCount = 1;
      $oddS4JudgeCount = 1;
    }    
    // use wt_LinkedStep4datasets table
    $sql = sprintf("SELECT * FROM wt_LinkedStep4datasets WHERE exptId='%s' AND jType=0", $this->exptId);
    $result = $igrtSqli->query($sql);
    $evenShuffledMsg = $result ? "Even datasets have been shuffled" : "Even datasets have not been shuffled";
    $sql = sprintf("SELECT * FROM wt_LinkedStep4datasets WHERE exptId='%s' AND jType=0 AND rated=1", $this->exptId);
    $result = $igrtSqli->query($sql);
    $evenShuffledMsg.= $result ? "warning: even data collection has started " : "";
    $sql = sprintf("SELECT * FROM wt_LinkedStep4datasets WHERE exptId='%s' AND jType=1", $this->exptId);
    $result = $igrtSqli->query($sql);
    $oddShuffledMsg = $result ? "Odd datasets have been shuffled" : "Odd datasets have not been shuffled";
    $sql = sprintf("SELECT * FROM wt_LinkedStep4datasets WHERE exptId='%s' AND jType=1 AND rated=1", $this->exptId);
    $result = $igrtSqli->query($sql);
    $oddShuffledMsg.= $result ? "warning: odd data collection has started " : "";
    $selectJSON = $this->metadataConverter->getSelectJSONArray(1,200);
    $json = "{\"sectionTitle\": \"linked experiment shuffle\",\"sectionName\": \"leShuffle\",\"exptId\":".$this->exptId.",\"formFields\":[";
      $pageWarning = "Note: the shuffle is performed when pressing `save and operate on changes`";
      $index = 0; // incremented in makeXXXJSON methods
      $json.= $this->makeFormFieldJSON($index, "pageWarning", "", "", $pageWarning, "", "", "",  "", "", "[]", false, [], [], "", "", "", "") . ",";
      $json.= $this->makeFormFieldJSON($index, "select", "wt_Step4JudgeCounts", "oddS4JudgeCount", "max odd #", "", "", $oddS4JudgeCount, "", "", $selectJSON, false, [], [], "", "", "", "") . ",";
      $json.= $this->makeFormFieldJSON($index, "pageWarning", "", "", $oddShuffledMsg, "", "", "",  "", "", "[]", false, [], [], "", "", "", "") . ",";
      $json.= $this->makeFormFieldJSON($index, "select", "wt_Step4JudgeCounts", "evenS4JudgeCount", "max even #", "", "", $evenS4JudgeCount, "", "", $selectJSON, false, [], [], "", "", "", ""). ",";
      $json.= $this->makeFormFieldJSON($index, "pageWarning", "", "", $evenShuffledMsg, "", "", "",  "", "", "[]", false, [], [], "", "", "", "");
    $json.= "]}";
    return $json;        
  }

  function getStep3LinkedTBTShuffle() {
    // the linked TBT Shuffle is used in experiments 327<->328 and 329<->330 where Step2 is bypassed,
    // and instead Step1 data is mixed from 4 experiments to form a Step4
    // and this is a special case where reflexive (turn-by-turn) judging is used
    global $igrtSqli;
    $sql = sprintf("SELECT * FROM wt_Step4JudgeCounts WHERE exptId='%s'", $this->exptId);
    $result = $igrtSqli->query($sql);
    if ($result->num_rows > 0) {
      $row = $result->fetch_object();
      $evenS4JudgeCount = $row->evenS4JudgeCount;
      $oddS4JudgeCount = $row->oddS4JudgeCount;
    }
    else {
      $evenS4JudgeCount = 1;
      $oddS4JudgeCount = 1;
    }    
    // use wt_LinkedStep4datasets table
    $sql = sprintf("SELECT * FROM wt_LinkedTBTStep4datasets WHERE exptId='%s' AND jType=0", $this->exptId);
    $result = $igrtSqli->query($sql);
    $evenShuffledMsg = $result ? "Even datasets have been shuffled" : "Even datasets have not been shuffled";
    $sql = sprintf("SELECT * FROM wt_LinkedTBTStep4datasets WHERE exptId='%s' AND jType=0 AND rated=1", $this->exptId);
    $result = $igrtSqli->query($sql);
    $evenShuffledMsg.= $result ? "warning: even data collection has started " : "";
    $sql = sprintf("SELECT * FROM wt_LinkedTBTStep4datasets WHERE exptId='%s' AND jType=1", $this->exptId);
    $result = $igrtSqli->query($sql);
    $oddShuffledMsg = $result ? "Odd datasets have been shuffled" : "Odd datasets have not been shuffled";
    $sql = sprintf("SELECT * FROM wt_LinkedTBTStep4datasets WHERE exptId='%s' AND jType=1 AND rated=1", $this->exptId);
    $result = $igrtSqli->query($sql);
    $oddShuffledMsg.= $result ? "warning: odd data collection has started " : "";
    $selectJSON = $this->metadataConverter->getSelectJSONArray(1,200);
    $json = "{\"sectionTitle\": \"linked TBT experiment shuffle\",\"sectionName\": \"tbtShuffle\",\"exptId\":".$this->exptId.",\"formFields\":[";
      $pageWarning = "Note: the shuffle is performed when pressing `save and operate on changes`";
      $index = 0; // incremented in makeXXXJSON methods
      $json.= $this->makeFormFieldJSON($index, "pageWarning", "", "", $pageWarning, "", "", "",  "", "", "[]", false, [], [], "", "", "", "") . ",";
      $json.= $this->makeFormFieldJSON($index, "select", "wt_Step4JudgeCounts", "oddS4JudgeCount", "max odd #", "", "", $oddS4JudgeCount, "", "", $selectJSON, false, [], [], "", "", "", "") . ",";
      $json.= $this->makeFormFieldJSON($index, "pageWarning", "", "", $oddShuffledMsg, "", "", "",  "", "", "[]", false, [], [], "", "", "", "") . ",";
      $json.= $this->makeFormFieldJSON($index, "select", "wt_Step4JudgeCounts", "evenS4JudgeCount", "max even #", "", "", $evenS4JudgeCount, "", "", $selectJSON, false, [], [], "", "", "", ""). ",";
      $json.= $this->makeFormFieldJSON($index, "pageWarning", "", "", $evenShuffledMsg, "", "", "",  "", "", "[]", false, [], [], "", "", "", "");
    $json.= "]}";
    return $json;        
  }
  
  function getStep4Monitor() {
    global $igrtSqli;
    $json = "{\"sectionTitle\": \"Step4 monitor\",\"sectionName\": \"s4Monitor\",\"exptId\":".$this->exptId.",\"formFields\":[";
    $index = 0; // incremented in makeXXXJSON methods
    $erQry = sprintf("SELECT * FROM wt_Step4datasets WHERE exptId='%s' AND jType=0", $this->exptId);
    $erResult = $igrtSqli->query($erQry);
    $hasEvenRaw = $erResult ? true : false;
    $orQry = sprintf("SELECT * FROM wt_Step4datasets WHERE exptId='%s' AND jType=1", $this->exptId);
    $orResult = $igrtSqli->query($orQry);
    $hasOddRaw = $orResult ? true : false;
    if ($hasEvenRaw) {
      $evenMsg = "monitor even S4 judges";
      $json.= $this->makeFormFieldJSON($index, "pageWarning", "", "", $evenMsg, "", "", "",  "", "", "[]", false, [], [], "", "", "", "") . ",";
      $json.= $this->makeFormFieldJSON($index, "button", "", "", "", "", "", "", "", "", "[]", false, [], [], "6_3_0_0", "even S4 monitor","", "", ""). ",";        
    }
    else {
      $evenMsg = "no data collection started for even S4 judges";
      $json.= $this->makeFormFieldJSON($index, "pageWarning", "", "", $evenMsg, "", "", "",  "", "", "[]", false, [], [], "", "", "", "") . ",";     
    }
    if ($hasOddRaw) {
      $oddMsg = "monitor odd S4 judges";
      $json.= $this->makeFormFieldJSON($index, "pageWarning", "", "", $oddMsg, "", "", "",  "", "", "[]", false, [], [], "", "", "", "") . ",";
      $json.= $this->makeFormFieldJSON($index, "button", "", "", "", "", "", "", "", "", "[]", false, [], [], "6_3_0_1", "odd S4 monitor", "", "");
    }
    else {
      $oddMsg = "no data collection started for odd S4 judges";
      $json.= $this->makeFormFieldJSON($index, "pageWarning", "", "", $oddMsg, "", "", "",  "", "", "[]", false, [], [], "", "", "", "");     
    }
    $json.= "]}";
    return $json;        
  }
  
  function getNEMonitor() {
    global $igrtSqli;
    $json = "{\"sectionTitle\": \"NE monitor\",\"sectionName\": \"neMonitor\",\"exptId\":".$this->exptId.",\"formFields\":[";
    $index = 0; // incremented in makeXXXJSON methods
    $erQry = sprintf("SELECT * FROM ne_Step4datasets WHERE exptId='%s' AND jType=0", $this->exptId);
    $erResult = $igrtSqli->query($erQry);
    $hasEvenRaw = $erResult ? true : false;
    $orQry = sprintf("SELECT * FROM ne_Step4datasets WHERE exptId='%s' AND jType=1", $this->exptId);
    $orResult = $igrtSqli->query($orQry);
    $hasOddRaw = $orResult ? true : false;
    if ($hasEvenRaw) {
      $evenMsg = "monitor even NE judges";
      $json.= $this->makeFormFieldJSON($index, "pageWarning", "", "", $evenMsg, "", "", "",  "", "", "[]", false, [], [], "", "", "", "") . ",";
      $json.= $this->makeFormFieldJSON($index, "button", "", "", "", "", "", "", "", "", "[]", false, [], [], "6_3_1_0", "even NE monitor","", "", ""). ",";        
    }
    else {
      $evenMsg = "no data collection started for even NE judges";
      $json.= $this->makeFormFieldJSON($index, "pageWarning", "", "", $evenMsg, "", "", "",  "", "", "[]", false, [], [], "", "", "", "") . ",";     
    }
    if ($hasOddRaw) {
      $oddMsg = "monitor odd NE judges";
      $json.= $this->makeFormFieldJSON($index, "pageWarning", "", "", $oddMsg, "", "", "",  "", "", "[]", false, [], [], "", "", "", "") . ",";
      $json.= $this->makeFormFieldJSON($index, "button", "", "", "", "", "", "", "", "", "[]", false, [], [], "6_3_1_1", "odd NE monitor", "", "");
    }
    else {
      $oddMsg = "no data collection started for odd NE judges";
      $json.= $this->makeFormFieldJSON($index, "pageWarning", "", "", $oddMsg, "", "", "",  "", "", "[]", false, [], [], "", "", "", "");     
    }
    $json.= "]}";
    return $json;            
  }

  function getLEMonitor() {
    global $igrtSqli;
    $json = "{\"sectionTitle\": \"LE monitor\",\"sectionName\": \"leMonitor\",\"exptId\":".$this->exptId.",\"formFields\":[";
    $index = 0; // incremented in makeXXXJSON methods
    $erQry = sprintf("SELECT * FROM wt_LinkedStep4datasets WHERE exptId='%s' AND jType=0", $this->exptId);
    $erResult = $igrtSqli->query($erQry);
    $hasEvenRaw = $erResult ? true : false;
    if ($hasEvenRaw) {
      $evenMsg = "monitor even LE judges";
      $json.= $this->makeFormFieldJSON($index, "pageWarning", "", "", $evenMsg, "", "", "",  "", "", "[]", false, [], [], "", "", "", "") . ",";
      $json.= $this->makeFormFieldJSON($index, "button", "", "", "", "", "", "", "", "", "[]", false, [], [], "6_3_2_0", "even LE monitor","", "", ""). "";        
    }
    else {
      $evenMsg = "no shuffle info, so no data collection started for even LE judges";
      $json.= $this->makeFormFieldJSON($index, "pageWarning", "", "", $evenMsg, "", "", "",  "", "", "[]", false, [], [], "", "", "", "") . "";
    }
    $json.= "]}";
    return $json;            
  }

  function getTBTMonitor() {
    global $igrtSqli;
    $json = "{\"sectionTitle\": \"TBT monitor\",\"sectionName\": \"tbtMonitor\",\"exptId\":".$this->exptId.",\"formFields\":[";
    $index = 0; // incremented in makeXXXJSON methods
    $erQry = sprintf("SELECT * FROM wt_LinkedStep4datasets WHERE exptId='%s' AND jType=0", $this->exptId);
    $erResult = $igrtSqli->query($erQry);
    $hasEvenRaw = $erResult ? true : false;
    if ($hasEvenRaw) {
      $evenMsg = "monitor even LE judges";
      $json.= $this->makeFormFieldJSON($index, "pageWarning", "", "", $evenMsg, "", "", "",  "", "", "[]", false, [], [], "", "", "", "") . ",";
      $json.= $this->makeFormFieldJSON($index, "button", "", "", "", "", "", "", "", "", "[]", false, [], [], "6_3_3_0", "even LE-TBT monitor","", "", ""). "";        
    }
    else {
      $evenMsg = "no shuffle info, so no data collection started for even LE judges";
      $json.= $this->makeFormFieldJSON($index, "pageWarning", "", "", $evenMsg, "", "", "",  "", "", "[]", false, [], [], "", "", "", "") . "";
    }
    $json.= "]}";
    return $json;            
  }

	function getStep1Dialogues() {
		global $igrtSqli;
		$json = "{\"sectionTitle\": \"Step1 dialogues\",\"sectionName\": \"s1Dialogues\",\"exptId\":".$this->exptId.",\"formFields\":[";
		$index = 0; // incremented in makeXXXJSON methods
		$dayList = [];
		$dQry = sprintf("SELECT DISTINCT(dayNo) FROM edSessions WHERE exptId='%s'", $this->exptId);
		$dResult = $igrtSqli->query($dQry);
		if ($dResult->num_rows >0) {
			while ($dRow = $dResult->fetch_object()) {
				$dayNo = $dRow->dayNo;
				$sQry = sprintf("SELECT DISTINCT(sessionNo) FROM edSessions WHERE exptId='%s' AND dayNo='%s'", $this->exptId, $dayNo);
				$sResult = $igrtSqli->query($sQry);
				$sessionList = [];
				if ($sResult->num_rows >0) {
					while ($sRow = $sResult->fetch_object()) {
						$sessionNo = $sRow->sessionNo;
						$sessionStatus = ['hasEvenMarked'=>false, 'hasOddMarked'=>false, 'hasEvenUnmarked'=>false, 'hasOddUnmarked'=>false];
						$markedQry = sprintf("SELECT * FROM md_dataStep1reviewed WHERE exptId='%s' AND dayNo='%s' AND sessionNo='%s' AND jType=0", $this->exptId, $dayNo, $sessionNo);
						$mResult = $igrtSqli->query($markedQry);
						if ($mResult->num_rows > 0) {
							$sessionStatus['hasEvenMarked'] = true;
						}
						else {
							$unmarkedQry = sprintf("SELECT * FROM dataSTEP1 WHERE exptId='%s' AND dayNo='%s' AND sessionNo='%s' AND jType=0", $this->exptId, $dayNo, $sessionNo);
							$uResult = $igrtSqli->query($unmarkedQry);
							if ($uResult->num_rows > 0) { $sessionStatus['hasEvenUnmarked'] = true; }
						}
						$markedQry = sprintf("SELECT * FROM md_dataStep1reviewed WHERE exptId='%s' AND dayNo='%s' AND sessionNo='%s' AND jType=1", $this->exptId, $dayNo, $sessionNo);
						$mResult = $igrtSqli->query($markedQry);
						if ($mResult->num_rows >0) {
							$sessionStatus['hasOddMarked'] = true;
						}
						else {
							$unmarkedQry = sprintf("SELECT * FROM dataSTEP1 WHERE exptId='%s' AND dayNo='%s' AND sessionNo='%s' AND jType=1", $this->exptId, $dayNo, $sessionNo);
							$uResult = $igrtSqli->query($unmarkedQry);
							if ($uResult->num_rows > 0) { $sessionStatus['hasOddUnmarked'] = true; }
						}
						array_push($sessionList, $sessionStatus);
					}
				}
				else {
					array_push($sessionList, []);
				}
				array_push($dayList, ['sessionList'=>$sessionList]);
			}
		}
		for ($i=0; $i<count($dayList); $i++) {
			$day = $dayList[$i];
			$displayD = $i + 1;
			if (count($day['sessionList']) > 0) {
				for ($j=0; $j<count($day['sessionList']); $j++) {
					$displayS = $j + 1;
					$session = $day['sessionList'][$j];
					if ($session['hasEvenMarked'] || $session['hasEvenUnmarked']) {
						$evenMsg = $session['hasEvenMarked'] ? "download reviewed E day:$displayD session:$displayS" : "download unreviewed E day:$displayD session:$displayS";
						$json.= $this->makeFormFieldJSON($index, "pageWarning", "", "", $evenMsg, "", "", "",  "", "", "[]", false, [], [], "", "", "", "") . ",";
						$json.= $this->makeFormFieldJSON($index, "button", "", "", "", "", "", "", "", "", "[]", false, [], [], "8_3_0_0_".$displayD ."_".$displayS, "even download","", "", ""). ",";
					}
					else {
						$evenMsg = "no datasets for even (day:$i session:$j)";
						$json.= $this->makeFormFieldJSON($index, "pageWarning", "", "", $evenMsg, "", "", "",  "", "", "[]", false, [], [], "", "", "", "") . ",";
					}
					if ($session['hasOddMarked'] || $session['hasOddUnmarked']) {
						$oddMsg = $session['hasOddMarked'] ? "download reviewed O day:$displayD session:$displayS" : "download unreviewed O day:$displayD session:$displayS";
						$json.= $this->makeFormFieldJSON($index, "pageWarning", "", "", $oddMsg, "", "", "",  "", "", "[]", false, [], [], "", "", "", "") . ",";
						$json.= $this->makeFormFieldJSON($index, "button", "", "", "", "", "", "", "", "", "[]", false, [], [], "8_3_0_1_".$displayD ."_".$displayS, "odd download", "", "") . ",";
					}
					else {
						$oddMsg = "no datasets for odd (day:$i session:$j)";
						$json.= $this->makeFormFieldJSON($index, "pageWarning", "", "", $oddMsg, "", "", "",  "", "", "[]", false, [], [], "", "", "", "") . ",";
					}
				}
			}
			else {
				$sessionMsg = "no sessions in day ".($i+1);
				$json.= $this->makeFormFieldJSON($index, "pageWarning", "", "", $sessionMsg, "", "", "",  "", "", "[]", false, [], [], "", "", "", "") . ",";
			}
		}
		$json.= $this->makeFormFieldJSON($index, "pageWarning", "", "", "end of day/session list", "", "", "",  "", "", "[]", false, [], [], "", "", "", "");
		$json.= "]}";
		return $json;

	}

  function getStep2pAnswerSets() {
    global $igrtSqli;
    $json = "{\"sectionTitle\": \"Step2 P answer sets\",\"sectionName\": \"s2pAS\",\"exptId\":".$this->exptId.",\"formFields\":[";
    $index = 0; // incremented in makeXXXJSON methods
    $erQry = sprintf("SELECT * FROM dataSTEP2 WHERE exptId='%s' AND jType=0", $this->exptId);
    $erResult = $igrtSqli->query($erQry);
    $hasEvenRaw = $erResult ? true : false;
    $emQry = sprintf("SELECT * FROM md_dataStep2reviewed WHERE exptId='%s' AND jType=0", $this->exptId);
    $emResult = $igrtSqli->query($emQry);
    $hasEvenMarked = $emResult ? true : false;
    $orQry = sprintf("SELECT * FROM dataSTEP2 WHERE exptId='%s' AND jType=1", $this->exptId);
    $orResult = $igrtSqli->query($orQry);
    $hasOddRaw = $orResult ? true : false;
    $omQry = sprintf("SELECT * FROM md_dataStep2reviewed WHERE exptId='%s' AND jType=1", $this->exptId);
    $omResult = $igrtSqli->query($omQry);
    $hasOddMarked = $omResult ? true : false;
    //$json.=$emQry.';'.$erQry.';'.$omQry.';'.$orQry.';';
    if (($hasEvenRaw) || ($hasEvenMarked)) {
      $evenMsg = ($hasEvenMarked) ? "show marked even S2 Pretenders" : "show unmarked even S2 Pretenders";
      $json.= $this->makeFormFieldJSON($index, "pageWarning", "", "", $evenMsg, "", "", "",  "", "", "[]", false, [], [], "", "", "", "") . ",";
      $json.= $this->makeFormFieldJSON($index, "button", "", "", "", "", "", "", "", "", "[]", false, [], [], "8_3_1_0", "even P answer sets","", "", ""). ",";        
    }
    else {
      $evenMsg = "no data for even S2 Pretenders";
      $json.= $this->makeFormFieldJSON($index, "pageWarning", "", "", $evenMsg, "", "", "",  "", "", "[]", false, [], [], "", "", "", "") . ",";     
    }
    if (($hasOddRaw) || ($hasOddMarked)) {
      $oddMsg = ($hasOddMarked) ? "show marked odd S2 Pretenders" : "show unmarked odd S2 Pretenders";
      $json.= $this->makeFormFieldJSON($index, "pageWarning", "", "", $oddMsg, "", "", "",  "", "", "[]", false, [], [], "", "", "", "") . ",";
      $json.= $this->makeFormFieldJSON($index, "button", "", "", "", "", "", "", "", "", "[]", false, [], [], "8_3_1_1", "odd P answer sets", "", "");
    }
    else {
      $oddMsg = "no data for even S2 Pretenders";
      $json.= $this->makeFormFieldJSON($index, "pageWarning", "", "", $oddMsg, "", "", "",  "", "", "[]", false, [], [], "", "", "", "");      
    }
    $json.= "]}";
    return $json;    
  }
  
  function getStep2npAnswerSets() {
    global $igrtSqli;
    $json = "{\"sectionTitle\": \"Inv Step2 NP answer sets\",\"sectionName\": \"is2npAS\",\"exptId\":".$this->exptId.",\"formFields\":[";
    $index = 0; // incremented in makeXXXJSON methods
    $erQry = sprintf("SELECT * FROM dataSTEP2inverted WHERE exptId='%s' AND jType=0", $this->exptId);
    $erResult = $igrtSqli->query($erQry);
    $hasEvenRaw = $erResult ? true : false;
    $emQry = sprintf("SELECT * FROM md_invertedStep2reviewed WHERE exptId='%s' AND jType=0", $this->exptId);
    $emResult = $igrtSqli->query($emQry);
    $hasEvenMarked = $emResult ? true : false;
    $orQry = sprintf("SELECT * FROM dataSTEP2inverted WHERE exptId='%s' AND jType=1", $this->exptId);
    $orResult = $igrtSqli->query($orQry);
    $hasOddRaw = $orResult ? true : false;
    $omQry = sprintf("SELECT * FROM md_invertedStep2reviewed WHERE exptId='%s' AND jType=1", $this->exptId);
    $omResult = $igrtSqli->query($omQry);
    $hasOddMarked = $omResult ? true : false;
    //$json.=$emQry.';'.$erQry.';'.$omQry.';'.$orQry.';';
    if (($hasEvenRaw) || ($hasEvenMarked)) {
      $evenMsg = ($hasEvenMarked) ? "show marked even iS2 Non-Pretenders" : "show unmarked even iS2 Non-Pretenders";
      $json.= $this->makeFormFieldJSON($index, "pageWarning", "", "", $evenMsg, "", "", "",  "", "", "[]", false, [], [], "", "", "", "") . ",";
      $json.= $this->makeFormFieldJSON($index, "button", "", "", "", "", "", "", "", "", "[]", false, [], [], "5_3_3_0", "even NP answer sets","", "", ""). ",";        
    }
    else {
      $evenMsg = "no data for even iS2 Non-Pretenders";
      $json.= $this->makeFormFieldJSON($index, "pageWarning", "", "", $evenMsg, "", "", "",  "", "", "[]", false, [], [], "", "", "", "") . ",";     
    }
    if (($hasOddRaw) || ($hasOddMarked)) {
      $oddMsg = ($hasOddMarked) ? "show marked odd iS2 Non-Pretenders" : "show unmarked odd iS2 Non-Pretenders";
      $json.= $this->makeFormFieldJSON($index, "pageWarning", "", "", $oddMsg, "", "", "",  "", "", "[]", false, [], [], "", "", "", "") . ",";
      $json.= $this->makeFormFieldJSON($index, "button", "", "", "", "", "", "", "", "", "[]", false, [], [], "5_3_3_1", "odd NP answer sets", "", "");
    }
    else {
      $oddMsg = "no data for odd iS2 Non-Pretenders";
      $json.= $this->makeFormFieldJSON($index, "pageWarning", "", "", $oddMsg, "", "", "",  "", "", "[]", false, [], [], "", "", "", "");      
    }
    $json.= "]}";
    return $json;        
  }
  
  function getAuditReport() {
    global $igrtSqli;
    $json = "{\"sectionTitle\": \"Audit report\",\"sectionName\": \"auditReport\",\"exptId\":".$this->exptId.",\"formFields\":[";
    $index = 0; // incremented in makeXXXJSON methods
    $pptQry = sprintf("SELECT * FROM wt_Step2pptStatus WHERE exptId='%s' AND jType='0' AND (discarded=1 OR finished=1)", $this->exptId);
    $pptResult = $igrtSqli->query($pptQry);
    $hasEven = $pptResult ? true : false;
    $pptQry = sprintf("SELECT * FROM wt_Step2pptStatus WHERE exptId='%s' AND jType='1' AND (discarded=1 OR finished=1)", $this->exptId);
    $pptResult = $igrtSqli->query($pptQry);
    $hasOdd = $pptResult ? true : false;
    if ($hasEven) {
      $evenMsg = "even S2 audit data available";
      $json.= $this->makeFormFieldJSON($index, "pageWarning", "", "", $evenMsg, "", "", "",  "", "", "[]", false, [], [], "", "", "", "") . ",";
      $json.= $this->makeFormFieldJSON($index, "button", "", "", "", "", "", "", "", "", "[]", false, [], [], "8_3_3_0", "even audit report","", "", ""). ",";        
    }
    else {
      $evenMsg = "no data for even S2 Pretenders";
      $json.= $this->makeFormFieldJSON($index, "pageWarning", "", "", $evenMsg, "", "", "",  "", "", "[]", false, [], [], "", "", "", "") . ",";     
    }
    if ($hasOdd) {
      $oddMsg = "odd S2 audit data available";
      $json.= $this->makeFormFieldJSON($index, "pageWarning", "", "", $oddMsg, "", "", "",  "", "", "[]", false, [], [], "", "", "", "") . ",";
      $json.= $this->makeFormFieldJSON($index, "button", "", "", "", "", "", "", "", "", "[]", false, [], [], "8_3_3_1", "odd audit report", "", "");
    }
    else {
      $oddMsg = "no data for odd S2 Pretenders";
      $json.= $this->makeFormFieldJSON($index, "pageWarning", "", "", $oddMsg, "", "", "",  "", "", "[]", false, [], [], "", "", "", "");      
    }
    $json.= "]}";
    return $json;    
  }
  
  function getStep4QuantitativeData() {
    global $igrtSqli;
    $json = "{\"sectionTitle\": \"Step4 quantitative data\",\"sectionName\": \"s4Quant\",\"exptId\":".$this->exptId.",\"formFields\":[";
    $index = 0; // incremented in makeXXXJSON methods
    $erQry = sprintf("SELECT * FROM dataSTEP4 WHERE exptId='%s' AND jType=0", $this->exptId);
    $erResult = $igrtSqli->query($erQry);
    $hasEvenRaw = $erResult ? true : false;
    $orQry = sprintf("SELECT * FROM dataSTEP4 WHERE exptId='%s' AND jType=1", $this->exptId);
    $orResult = $igrtSqli->query($orQry);
    $hasOddRaw = $orResult ? true : false;
    if ($hasEvenRaw) {
      $evenMsg = "download even S4 judges";
      $json.= $this->makeFormFieldJSON($index, "pageWarning", "", "", $evenMsg, "", "", "",  "", "", "[]", false, [], [], "", "", "", "") . ",";
      $json.= $this->makeFormFieldJSON($index, "button", "", "", "", "", "", "", "", "", "[]", false, [], [], "8_3_4_0", "even S4 data","", "", ""). ",";        
    }
    else {
      $evenMsg = "no data collection started for even S4 judges";
      $json.= $this->makeFormFieldJSON($index, "pageWarning", "", "", $evenMsg, "", "", "",  "", "", "[]", false, [], [], "", "", "", "") . ",";     
    }
    if ($hasOddRaw) {
      $oddMsg = "download odd S4 judges";
      $json.= $this->makeFormFieldJSON($index, "pageWarning", "", "", $oddMsg, "", "", "",  "", "", "[]", false, [], [], "", "", "", "") . ",";
      $json.= $this->makeFormFieldJSON($index, "button", "", "", "", "", "", "", "", "", "[]", false, [], [], "8_3_4_1", "odd S4 data", "", "");
    }
    else {
      $oddMsg = "no data collection started for odd S4 judges";
      $json.= $this->makeFormFieldJSON($index, "pageWarning", "", "", $oddMsg, "", "", "",  "", "", "[]", false, [], [], "", "", "", "");     
    }
    $json.= "]}";
    return $json;    
  }
  
  function getStep4QualitativeData() {
    global $igrtSqli;
    $json = "{\"sectionTitle\": \"Step4 qualitative data\",\"sectionName\": \"s4Qual\",\"exptId\":".$this->exptId.",\"formFields\":[";
    $index = 0; // incremented in makeXXXJSON methods
    $erQry = sprintf("SELECT * FROM dataSTEP4 WHERE exptId='%s' AND jType=0", $this->exptId);
    $erResult = $igrtSqli->query($erQry);
    $hasEvenRaw = $erResult ? true : false;
    $orQry = sprintf("SELECT * FROM dataSTEP4 WHERE exptId='%s' AND jType=1", $this->exptId);
    $orResult = $igrtSqli->query($orQry);
    $hasOddRaw = $orResult ? true : false;
    if ($hasEvenRaw) {
      $evenMsg = "download even S4 judges";
      $json.= $this->makeFormFieldJSON($index, "pageWarning", "", "", $evenMsg, "", "", "",  "", "", "[]", false, [], [], "", "", "", "") . ",";
      $json.= $this->makeFormFieldJSON($index, "button", "", "", "", "", "", "", "", "", "[]", false, [], [], "8_3_5_0", "even S4 data","", "", ""). ",";        
    }
    else {
      $evenMsg = "no data collection started for even S4 judges";
      $json.= $this->makeFormFieldJSON($index, "pageWarning", "", "", $evenMsg, "", "", "",  "", "", "[]", false, [], [], "", "", "", "") . ",";     
    }
    if ($hasOddRaw) {
      $oddMsg = "download odd S4 judges";
      $json.= $this->makeFormFieldJSON($index, "pageWarning", "", "", $oddMsg, "", "", "",  "", "", "[]", false, [], [], "", "", "", "") . ",";
      $json.= $this->makeFormFieldJSON($index, "button", "", "", "", "", "", "", "", "", "[]", false, [], [], "8_3_5_1", "odd S4 data", "", "");
    }
    else {
      $oddMsg = "no data collection started for odd S4 judges";
      $json.= $this->makeFormFieldJSON($index, "pageWarning", "", "", $oddMsg, "", "", "",  "", "", "[]", false, [], [], "", "", "", "");     
    }
    $json.= "]}";
    return $json;    
  }
  
  function getNEQuantitativeData() {
    global $igrtSqli;
    $json = "{\"sectionTitle\": \"NE quantitative data\",\"sectionName\": \"neQuant\",\"exptId\":".$this->exptId.",\"formFields\":[";
    $index = 0; // incremented in makeXXXJSON methods
    $erQry = sprintf("SELECT * FROM ne_dataSTEP4 WHERE exptId='%s' AND jType=0", $this->exptId);
    $erResult = $igrtSqli->query($erQry);
    $hasEvenRaw = $erResult ? true : false;
    $orQry = sprintf("SELECT * FROM ne_dataSTEP4 WHERE exptId='%s' AND jType=1", $this->exptId);
    $orResult = $igrtSqli->query($orQry);
    $hasOddRaw = $orResult ? true : false;
    if ($hasEvenRaw) {
      $evenMsg = "download even S4 judges";
      $json.= $this->makeFormFieldJSON($index, "pageWarning", "", "", $evenMsg, "", "", "",  "", "", "[]", false, [], [], "", "", "", "") . ",";
      $json.= $this->makeFormFieldJSON($index, "button", "", "", "", "", "", "", "", "", "[]", false, [], [], "8_3_6_0", "even S4 data","", "", ""). ",";        
    }
    else {
      $evenMsg = "no data collection started for even S4 judges";
      $json.= $this->makeFormFieldJSON($index, "pageWarning", "", "", $evenMsg, "", "", "",  "", "", "[]", false, [], [], "", "", "", "") . ",";     
    }
    if ($hasOddRaw) {
      $oddMsg = "download odd S4 judges";
      $json.= $this->makeFormFieldJSON($index, "pageWarning", "", "", $oddMsg, "", "", "",  "", "", "[]", false, [], [], "", "", "", "") . ",";
      $json.= $this->makeFormFieldJSON($index, "button", "", "", "", "", "", "", "", "", "[]", false, [], [], "8_3_6_1", "odd S4 data", "", "");
    }
    else {
      $oddMsg = "no data collection started for odd S4 judges";
      $json.= $this->makeFormFieldJSON($index, "pageWarning", "", "", $oddMsg, "", "", "",  "", "", "[]", false, [], [], "", "", "", "");     
    }
    $json.= "]}";
    return $json;    
  }
  
  function getNEQualitativeData() {
    global $igrtSqli;
    $json = "{\"sectionTitle\": \"NE qualitative data\",\"sectionName\": \"neQual\",\"exptId\":".$this->exptId.",\"formFields\":[";
    $index = 0; // incremented in makeXXXJSON methods
    $erQry = sprintf("SELECT * FROM ne_dataSTEP4 WHERE exptId='%s' AND jType=0", $this->exptId);
    $erResult = $igrtSqli->query($erQry);
    $hasEvenRaw = $erResult ? true : false;
    $orQry = sprintf("SELECT * FROM ne_dataSTEP4 WHERE exptId='%s' AND jType=1", $this->exptId);
    $orResult = $igrtSqli->query($orQry);
    $hasOddRaw = $orResult ? true : false;
    if ($hasEvenRaw) {
      $evenMsg = "download even S4 judges";
      $json.= $this->makeFormFieldJSON($index, "pageWarning", "", "", $evenMsg, "", "", "",  "", "", "[]", false, [], [], "", "", "", "") . ",";
      $json.= $this->makeFormFieldJSON($index, "button", "", "", "", "", "", "", "", "", "[]", false, [], [], "8_3_7_0", "even S4 data","", "", ""). ",";        
    }
    else {
      $evenMsg = "no data collection started for even S4 judges";
      $json.= $this->makeFormFieldJSON($index, "pageWarning", "", "", $evenMsg, "", "", "",  "", "", "[]", false, [], [], "", "", "", "") . ",";     
    }
    if ($hasOddRaw) {
      $oddMsg = "download odd S4 judges";
      $json.= $this->makeFormFieldJSON($index, "pageWarning", "", "", $oddMsg, "", "", "",  "", "", "[]", false, [], [], "", "", "", "") . ",";
      $json.= $this->makeFormFieldJSON($index, "button", "", "", "", "", "", "", "", "", "[]", false, [], [], "8_3_7_1", "odd S4 data", "", "");
    }
    else {
      $oddMsg = "no data collection started for odd S4 judges";
      $json.= $this->makeFormFieldJSON($index, "pageWarning", "", "", $oddMsg, "", "", "",  "", "", "[]", false, [], [], "", "", "", "");     
    }
    $json.= "]}";
    return $json;    
  }

	function getClassicStep1Dialogues() {
		global $igrtSqli;
		$json = "{\"sectionTitle\": \"Classic Step1 dialogues\",\"sectionName\": \"classicS1Dialogues\",\"exptId\":".$this->exptId.",\"formFields\":[";
		$index = 0; // incremented in makeXXXJSON methods
		$dayList = [];
		$dQry = sprintf("SELECT DISTINCT(dayNo) FROM edSessions WHERE exptId='%s'", $this->exptId);
		$dResult = $igrtSqli->query($dQry);
		if ($dResult->num_rows>0) {
			while ($dRow = $dResult->fetch_object()) {
				$dayNo = $dRow->dayNo;
				$sQry = sprintf("SELECT DISTINCT(sessionNo) FROM edSessions WHERE exptId='%s' AND dayNo='%s'", $this->exptId, $dayNo);
				$sResult = $igrtSqli->query($sQry);
				$sessionList = [];
				if ($sResult->num_rows>0) {
					while ($sRow = $sResult->fetch_object()) {
						$sessionNo = $sRow->sessionNo;
						$hasOwnersQry = sprintf("SELECT DISTINCT(owner) as owner FROM dataClassic WHERE exptId='%s' AND dayNo='%s' AND sessionNo='%s' ORDER BY owner ASC", $this->exptId, $dayNo, $sessionNo);
						$mResult = $igrtSqli->query($hasOwnersQry);
						$sessionStatus = $mResult ? true : false;
						array_push($sessionList, $sessionStatus);
					}
				}
				else {
					array_push($sessionList, []);
				}
				array_push($dayList, ['sessionList'=>$sessionList]);
			}
		}
		for ($i=0; $i<count($dayList); $i++) {
			$day = $dayList[$i];
			$displayD = $i + 1;
			if (count($day['sessionList']) > 0) {
				for ($j=0; $j<count($day['sessionList']); $j++) {
					$displayS = $j + 1;
					$sessionStatus = $day['sessionList'][$j];
					if ($sessionStatus) {
						$pageMsg = "download qualitative CSV or quantitative Transcript for day:$displayD session:$displayS";
						$json .= $this->makeFormFieldJSON($index, "pageWarning", "", "", $pageMsg, "", "", "", "", "", "[]", false, [], [], "", "", "", "") . ",";
						$json .= $this->makeFormFieldJSON($index, "button", "", "", "", "", "", "", "", "", "[]", false, [], [], "8_3_8_0_" . $displayD . "_" . $displayS, "download CSV", "", "", "") . ",";
						$json .= $this->makeFormFieldJSON($index, "button", "", "", "", "", "", "", "", "", "[]", false, [], [], "8_3_8_1_" . $displayD . "_" . $displayS, "view transcript", "", "", "") . ",";
					}
				}
			}
			else {
				$sessionMsg = "no sessions in day ".($i+1);
				$json.= $this->makeFormFieldJSON($index, "pageWarning", "", "", $sessionMsg, "", "", "",  "", "", "[]", false, [], [], "", "", "", "") . ",";
			}
		}
		$json.= $this->makeFormFieldJSON($index, "pageWarning", "", "", "end of day/session list", "", "", "",  "", "", "[]", false, [], [], "", "", "", "");
		$json.= "]}";
		return $json;
	}

	function getExperimenterGroupOperations() {
		global $igrtSqli;
		$json = "{\"sectionTitle\": \"Experimenter group operations\",\"sectionName\": \"egOperations\",\"exptId\":".$this->exptId.",\"formFields\":[";
		$index = 0; // incremented in makeXXXJSON methods

		$egsQry = "SELECT * FROM igExperimentGroups";
		$egsResult = $igrtSqli->query($egsQry);
		while ($row = $egsResult->fetch_object()) {

			$mappingsQry = sprintf("SELECT * FROM igExperimentGroupMappings WHERE exptID='%s' AND groupId='%s'", $this->exptId, $row->id);
			$mappingsResult = $igrtSqli->query($mappingsQry);
			$mappingsRow = $mappingsResult->fetch_object();
			$isSelected = $mappingsRow == null ? false : true;

			$json.= $this->makeFormFieldJSON($index, "checkbox", "igExperimentGroupMappings", "groupId", "member of " . $row->GroupName, $isSelected, "", "", "yes", "no", "[]", false, [], [], "", "", "", "") . ",";
		}

		$json.= $this->makeFormFieldJSON($index, "pageWarning", "", "", "assign the experiment to the correct groups and then use 'save and operate on changes'", "", "", "",  "", "", "[]", false, [], [], "", "", "", "");

		$json.= "]}";
		return $json;
	}

	// </editor-fold>
 
// <editor-fold defaultstate="collapsed" desc=" experiment definitions & controls">

  function createExperiment($uid, $exptName) {
    global $igrtSqli;
    $sqlCmd_createExpt=sprintf("INSERT INTO igExperiments (title, ownerId) VALUES('%s','%s')", $igrtSqli->real_escape_string($exptName), $uid);
    $igrtSqli->query($sqlCmd_createExpt);
    $exptId = $igrtSqli->insert_id;
    $sqlCmd_createExpt=sprintf("INSERT INTO edExptStatic_refactor (exptId, owner) VALUES('%s','%s')", $exptId, $uid);
    $igrtSqli->query($sqlCmd_createExpt);   
    return $this->sendExptSummary($uid);
  }
  
  function cloneExperiment($uid, $exptId, $newName) {
    global $igrtSqli;    
    $qry ="CALL cloneExperiment($uid,$exptId,'$newName',@neid)";
    $igrtSqli->query($qry);
    $sr2 = $igrtSqli->query("SELECT @neid as myVAR");
    if ($sr2->num_rows >0) {
      $row = $sr2->fetch_object();
      $newExptId = $row->myVAR;   
    }
    $json = "{";
    if ($newExptId < 0) {
      $json.= "\"statusMsg\":\"duplicateName\",";
      $json.= "\"newExptId\":\"-1\"";
    } 
    else {
      $contentQry = "CALL populateContent($exptId)";
      $igrtSqli->query($contentQry);
      $json.= "\"statusMsg\":\"success\",";
      $json.= "\"newExptId\":\"". $newExptId."\"";
    }
    $json.= "}";
    return $json;
  }

  function deleteExperiment($exptId) {
    global $igrtSqli;
    $qry=sprintf("CALL deleteExperiment(%s,@delCnt,@aRows)", $exptId);
    $igrtSqli->query($qry);
    $ndelUsers = 0;
    $sr = $igrtSqli->query("SELECT @delCnt as myVAR");
    if ($sr->num_rows>0) {
      $row = $sr->fetch_object();
      $ndelUsers = $row->myVAR;
    }
    $nArchivedRows = 0;
    $sr2 = $igrtSqli->query("SELECT @aRows as myVAR");
    if ($sr2->num_rows>0) {
      $row = $sr2->fetch_object();
      $nArchivedRows = $row->myVAR;
    }
    $json = "{";
      $json.= "\"deletedUserCount\":\"".$ndelUsers."\",";
      $json.= "\"archivedRowCount\":\"".$nArchivedRows."\"";
    $json.= "}";
    return $json;
  }
           
  function getExperimentSection($exptId, $sectionNo) {
    switch ($sectionNo) {
      case 0 : {
        return $this->getExptSummary();
        break;
      }
      case 1 : {
        return $this->getStep1Sessions();
        break;
      }
      case 2 : {
        return $this->getStep1InterrogatorRating();
        break;
      }
      case 3 : {
        return $this->getStep1InterrogatorFinalRating();
        break;
      }      
      case 4 : {
        return $this->getStep1InterrogatorAlignment();
        break;
      }      
      case 5 : {
        return $this->getStep1iContent();
        break;
      }      
      case 6 : {
        return $this->getStep1rContent();
        break;
      }      
      case 7 : {
        return $this->getStep2Content();
        break;
      }      
      case 8 : {
        return $this->getStep2PAlignment();
        break;
      }      
      case 9 : {
        return $this->getStep2Balancer();
        break;
      }      
      case 10 : {
        return $this->getIStep2Content();
        break;
      }      
      case 11 : {
        return $this->getIStep2NPAlignment();
        break;
      }      
      case 12 : {
        return $this->getIStep2Balancer();
        break;
      }      
      case 13 : {
        return $this->getStep4Content();        
        break;
      }      
      case 14 : {
        return $this->getStep4JudgeAlignment();
        break;
      }      
      case 15 : {
        return $this->getSurveys();        
        break;
      }      
      case 16 : {
        return $this->getSurveysReview();
        break;
      }      
      case 17 : {
        return $this->getStep1Review();
        break;
      }      
      case 18 : {
        return $this->getStep2pMonitor();
        break;
      }      
      case 19 : {
        return $this->getStep2pReview();
        break;
      }      
      case 20 : {
        return $this->getStep2npMonitor();
        break;
      }      
      case 21 : {
        return $this->getStep2npReview();
        break;
      }      
      case 22 : {
        return $this->getStep3Datasource();
        break;
      }      
      case 23 : {
        return $this->getStep3Shuffle();
        break;
      }      
      case 24 : {
        return $this->getStep3SnowShuffle();
        break;
      }      
      case 25 : {
        return $this->getStep4Monitor();
        break;
      }      
      case 26 : {
        return $this->getStep1Dialogues();
        break;
      }      
      case 27 : {
        return $this->getStep2pAnswerSets();
        break;
      }      
      case 28 : {
        return $this->getStep2npAnswerSets();
        break;
      }      
      case 29 : {
        return $this->getAuditReport();
        break;
      }      
      case 30 : {
        return $this->getStep4QuantitativeData();
        break;
      }      
      case 31 : {
        return $this->getStep4QualitativeData();
        break;
      }      
      case 32 : {
        return $this->getNEMonitor();
        break;
      }      
      case 33 : {
        return $this->getNEQuantitativeData();
        break;
      }      
      case 34 : {
        return $this->getNEQualitativeData();
        break;
      } 
      case 35 : {
        return $this->getStep3LEShuffle();
        break;
      }
      case 36 : {
        return $this->getLEMonitor();
        break;
      }
      case 37 : {
        return $this->getStep3LinkedTBTShuffle();
        break;
      }
			case 38 : {
				return $this->getTBTMonitor();
				break;
			}
	    case 39 : {
		    return $this->getClassicStep1Dialogues();
		    break;
	    }
	    case 40 : {
		    return $this->getExperimenterGroupOperations();
		    break;
	    }
      default: {
        return "";
        break;
      }
    }
  }
  
  function getStep1Users($exptId) {
    global $igrtSqli;
    $dsQry = sprintf("SELECT * FROM edExptStatic_refactor WHERE exptId='%s'", $exptId);
    $dsResult = $igrtSqli->query($dsQry);
    $daysArray = [];
    for ($i=1; $i<=$this->eModel->noDays; $i++) {
      $sessionsArray = [];
      for ($j=1; $j<=$this->eModel->noSessions; $j++) {
        if ($this->eModel->isClassic == 1) {
          //echo 'doing classic';
          $cQry = sprintf("SELECT igActiveClassicUsers.*, igActiveClassicUsersPW.plainText FROM igActiveClassicUsers JOIN igActiveClassicUsersPW "
              . "WHERE igActiveClassicUsers.exptId='%s' AND igActiveClassicUsers.dayNo='%s' AND igActiveClassicUsers.sessionNo='%s' "
              . "AND igActiveClassicUsers.uid=igActiveClassicUsersPW.uid ", $exptId, $i, $j);
          //echo $cQry;
          $cResult = $igrtSqli->query($cQry);
          $userArray = [];
          if ($cResult->num_rows > 0) {
            //echo 'got classic users';
            while ($cRow = $cResult->fetch_object()) {
              $userDef = [
                'login'=>$this->getLogin($cRow->uid),
                'pw'=> $cRow->plainText,
                'role'=> $cRow->role
              ];
              array_push($userArray, $userDef);
            }
          }
        }
        else {
          //echo 'doing non-classic';
          $jQry = sprintf("SELECT igActiveStep1Users.*, igActiveStep1UsersPW.plainText FROM igActiveStep1Users JOIN igActiveStep1UsersPW "
              . "WHERE igActiveStep1Users.exptId='%s' AND igActiveStep1Users.day='%s' AND igActiveStep1Users.session='%s' "
              . "AND igActiveStep1Users.uid=igActiveStep1UsersPW.uid "
              . "ORDER BY igActiveStep1Users.jType ASC, igActiveStep1Users.jNo ASC", $exptId, $i, $j);
          //echo $jQry;
          $jResult = $igrtSqli->query($jQry);
          if ($jResult->num_rows > 0) {
            //echo 'got non-classic users';
            $jCnt = $jResult->num_rows / 2;
            $userArray = [];
            for ($k=0; $k<$jCnt; $k++) {
              array_push($userArray, array('oddLogin'=>'', 'oddPassword'=>'', 'evenLogin'=>'', 'evenPassword'=>''));
            }
            while ($jRow = $jResult->fetch_object()) {
              if ($jRow->jType == 0) {
                $userArray[$jRow->jNo]['evenLogin'] = $this->getLogin($jRow->uid);
                $userArray[$jRow->jNo]['evenPassword'] = $jRow->plainText;             
              }
              else {
                $userArray[$jRow->jNo]['oddLogin'] = $this->getLogin($jRow->uid);
                $userArray[$jRow->jNo]['oddPassword'] = $jRow->plainText;                           
              }
            }            
          }
        }
        array_push($sessionsArray, array('dayNo'=> $i, 'sessionNo'=>$j, 'users'=>$userArray));
      }
      array_push($daysArray, array('dayNo'=>$i, 'sessions'=>$sessionsArray));
    }
//    echo print_r($daysArray);
    return $this->makeInterrogatorsJSON($daysArray);    
  }
  
  function getShuffleStatus($exptId) {
    global $igrtSqli;
    $shuffler = new shuffleControllerClass($igrtSqli, $exptId,  NULL, NULL);
    return $shuffler->getShuffleJSON();
  }
  
  function getLEShuffleStatus($exptId) {
    global $igrtSqli;
    $shuffler = new shuffleControllerClass($igrtSqli, $exptId,  NULL, NULL);
    return $shuffler->getLEShuffleJSON();    
  }

  function getTBTShuffleStatus($exptId) {
    global $igrtSqli;
    $shuffler = new shuffleControllerClass($igrtSqli, $exptId,  NULL, NULL);
    return $shuffler->getTBTShuffleJSON();    
  }
  
  function getSnowShuffleStatus($exptId) {
    global $igrtSqli;
    $shuffler = new shuffleControllerClass($igrtSqli, $exptId,  NULL, NULL);
    return $shuffler->getSnowShuffleJSON();    
  }
  
  function editExperimentStep1($exptId) {
    return $this->getStep1Summary($this->eModel);
  }
    
  function editStep1Forms($exptId) {
    return $this->getStep1FormsHtml($this->eModel);
  }

// </editor-fold>
  
// <editor-fold defaultstate="collapsed" desc=" ctor">

  function getControlItems() {
    global $igrtSqli;
    // get all control values  
    $controlQry = "SELECT * FROM igControlTypes";
    $controlResults = $igrtSqli->query($controlQry);
    if ($controlResults->num_rows>0) {
      while ($row = $controlResults->fetch_object()) {
        $controlDetail = array(
          'id' => $row->cValue,
          'label' => $row->cLabel
        );
        array_push($this->formControlSelectOptions, $controlDetail);
      }
    }
  }
  
  function __construct($uid, $exptId) {
    $this->getControlItems();
    $this->metadataConverter = new metadataConverter();
    if ($exptId > -1) {
      $this->eModel = new experimentModel($exptId);
    }
    $this->exptId = $exptId;
    $this->uid = $uid;
    $this->htmlBuilder = new htmlBuilder(); // currently still used for Step1 controller
    $this->_tabIndex = 0;
	  $this->userPermissions = $this->getUserPermissions();

  }

// </editor-fold>
 
}

