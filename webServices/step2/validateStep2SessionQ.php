<?php
// -----------------------------------------------------------------------------
// 
// web service to validate user-responses for Q in step2
// 
// -----------------------------------------------------------------------------
$full_ws_path=realpath(dirname(__FILE__));
$root_path=substr($full_ws_path, 0, strlen($full_ws_path)-18);  // 19 /webServices/step2
include_once $root_path.'/domainSpecific/mySqlObject.php';      // gives us $igrtSqli for all db operations in the CLI scripts
require_once($root_path.'/helpers/class.step2Manager.php');

  function checkValue($qNo, $qType) {
    global $qValues;
    foreach ($qValues as $q) {
      if ($q['qNo'] == $qNo) {
        if ($qType == 4) {
          // special validation for date values
          $Slashcomponents = explode('/', $q['value']);
          $Dashcomponents = explode('-', $q['value']);
          // month is received as y/m/d
          // checkdate requires m/d/y
          if ((count($Slashcomponents) == 3) || (count($Dashcomponents) == 3) ) {
            $comp = (count($Slashcomponents) == 3) ? $Slascomponents : $Dashcomponents;
            return (checkdate( (int)$comp[1] , (int)$comp[2] , (int)$comp[0])); 
          }
          else {
            return false;
          }
        }
        else {
          if ($q['value'] == '') { return false; }     
        }
      }
    }
    return true;
  }
  
  function addChecked($ifd) {
    $tempFD = array();
    foreach ($ifd as $q) {
      if ($q['qType'] == 0) {
        $q['checked'] = 0;  // cb doesn't need validation
      }
      else {
        $q['checked'] = 255;
      }
      array_push($tempFD, $q);
    }
    return array_values($tempFD);
}


$qValues = array();
$prepost = null;
$exptId = null;
$validated = true;

foreach ($_GET as $key=>$value) {
  switch ($key) {
    case "stage" : {
      $prepost = ($value == "pre") ? 0 : 1;
      break;
    }
    case "exptId" : {
      $exptId = $value;
      break;
    }
    default : {
      $cid = explode('_', $key);
      $qNo = (int) $cid[2];
      $formData = array("qNo" => $qNo, "key" => $key, "value" => $value);
      array_push($qValues, $formData);      
    }
  }
}

$step2ManageController = new step2Manager();
$formDef = $step2ManageController->getQDef($exptId, $prepost);
$formDef = addChecked($formDef);

$fCount = count($formDef);
for ($i=0; $i<$fCount; $i++) {
  switch($formDef[$i]['qType']) {
    case 0 : {
      // no validation required on cb
      break;
    }
    default : {
      $qNo = $formDef[$i]['qNo'];
      $qType = $formDef[$i]['qType'];
      if (checkValue($qNo, $qType)) { $formDef[$i]['checked'] = 0; }      
      break;
    }
  }
}


$validated = true;
foreach ($formDef as $q) {
  if ($q['checked'] == 255) { $validated = false; }
}
  
$html = ($validated === false) ? "not validated" : "validated";
//$html .= print_r($formDef, true);
//$html .= print_r($qValues, true);
echo $html;
