<?php
/**
 * sets and displays step2 balancer parameters and values
 *
 * @author mh
 */

if (!isset($root_path)) { $root_path = $_SERVER['DOCUMENT_ROOT']; }
include_once $root_path.'/helpers/html/class.htmlBuilder.php';
include_once $root_path.'/helpers/debug/class.debugLogger.php';



class step2BalancerController {
  private $igrtSqli;
  private $htmlBuilder;
  private $tabIndex;
  
  function logInfo($module, $msg) { 
    $sql = sprintf("INSERT INTO sysdiags_debug (chrono, module, msg) VALUES(NOW(), '%s', '%s')", $module, $this->igrtSqli->real_escape_string($msg));
    $this->igrtSqli->query($sql);    
  }
   
  function setupBalancer($exptId, $jType, $respNo) {
    $flagName = ($jType == 0) ? "step2EvenConfigured" : "step2OddConfigured";
    
    $sql = sprintf("SELECT * FROM igExperiments WHERE exptId='%s' AND %s=1", $exptId, $flagName);
    $sqlResult = $this->igrtSqli->query($sql);
    if ($this->igrtSqli->affected_rows > 0) {
      // re-configuration
      $clearQry = sprintf("DELETE FROM wt_Step2Balancer WHERE exptId='%s' AND jType='%s'", $exptId, $jType);
      $this->igrtSqli->query($clearQry);
    }
    // find # of datasets for the expt and jType and create appropriate entries

    // find distinct # of days in expt
    $ddSql = sprintf("SELECT DISTINCT dayNo FROM md_dataStep1reviewed WHERE exptId='%s' ORDER BY dayNo ASC", $exptId);
    $this->igrtSqli->query($ddSql);
    $dayResult = $this->igrtSqli->query($ddSql);
    $step2dsPtr = 0;
    while ($dayRow = $dayResult->fetch_object()) {
      $dayNo = $dayRow->dayNo;
      $dsSql = sprintf("SELECT DISTINCT sessionNo FROM md_dataStep1reviewed WHERE exptId='%s' AND dayNo='%s' ORDER BY sessionNo ASC", $exptId, $dayNo);
      $sessionResult = $this->igrtSqli->query($dsSql);
      while ($sessionRow = $sessionResult->fetch_object()) {
        $sessionNo = $sessionRow->sessionNo;
        $maxSql = sprintf("SELECT MAX(jNo) as maxjNo FROM md_dataStep1reviewed WHERE exptId='%s' AND dayNo='%s' AND sessionNo='%s' AND jType='%s'", $exptId, $dayNo, $sessionNo, $jType);
        $meResult = $this->igrtSqli->query($maxSql);
        $meRow = $meResult->fetch_object();
        $maxJNo = $meRow->maxjNo;
        // get relevant discard info
        $discardSql = sprintf("SELECT * FROM wt_Step1Discards WHERE exptId='%s' AND dayNo='%s' AND sessionNo='%s'", $exptId, $dayNo, $sessionNo);
        $discardResult = $this->igrtSqli->query($discardSql);
        $discardRow = $discardResult->fetch_object();
        if ($jType == 0) {
          $jDiscard = $discardRow->evenDiscards;         
        }
        else {
          $jDiscard = $discardRow->oddDiscards;          
        }
        for ($i=0; $i<=$maxJNo; $i++) {
          // check whether this judge discarded and don't insert in balancer if discarded
          $dMarker = pow(2, $i);
          $discardValue = (($jDiscard & $dMarker) == $dMarker) ? 1 : 0;
          if (($jDiscard & $dMarker) != $dMarker) {
            ++$step2dsPtr;
            $label = sprintf("s2_%s_%s_%s", $exptId, $jType, $step2dsPtr);
            $insSql = sprintf("INSERT INTO wt_Step2Balancer (exptId, jType, jNo, respCount, respMax, actualJNo, dayNo, sessionNo, label) "
                . "VALUES('%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s')",
                $exptId, $jType, $i, 0, $respNo, $step2dsPtr, $dayNo, $sessionNo, $label);
            $this->igrtSqli->query($insSql);
          }
        }
      }
    } 
    // register in igExperiments
    $updateQry = sprintf("UPDATE igExperiments SET %s=1 WHERE exptId='%s'", $flagName, $exptId);
    $this->igrtSqli->query($updateQry);
    $respMaxRowExists = sprintf("SELECT * FROM wt_Step2BalancerRespMax WHERE exptId='%s'", $exptId);
    $this->igrtSqli->query($respMaxRowExists);
    if ($this->igrtSqli->affected_rows > 0) {
      // update
      if ($jType == 0) {
        $updateQry = sprintf("UPDATE wt_Step2BalancerRespMax SET evenRespMax='%s' WHERE exptId='%s'", $respNo, $exptId);
      }
      else {
        $updateQry = sprintf("UPDATE wt_Step2BalancerRespMax SET oddRespMax='%s' WHERE exptId='%s'", $respNo, $exptId);            
      }
      $this->igrtSqli->query($updateQry);
    }
    else {
      //insert
      if ($jType == 0) {
        $insertQry = sprintf("INSERT INTO wt_Step2BalancerRespMax (exptId, evenRespMax) VALUES('%s', '%s')", $exptId, $respNo);
      }
      else {
        $insertQry = sprintf("INSERT INTO wt_Step2BalancerRespMax (exptId, oddRespMax) VALUES('%s', '%s')", $exptId, $respNo);
      }
      $this->igrtSqli->query($insertQry);          
    }
  }
  
  function buildHtmlFromSessionList($s2Dataset, $sectionTitle, $sessionPara, $isActiveSet) {
    $html = "<div class=\"currentExperiments active\"><h2>$sectionTitle</h2>";
    $html.= "<div>";
    $html.= "<p>$sessionPara</p>";
    $html.= "<table><tr><th>title</th><th>max # Odd P</th><th>Odd Action</th><th>max # Even P</th><th>Even Action</th></tr>";
    $evenCounts = array();
    $oddCounts = array();
    foreach ($s2Dataset as $s2ds) {
      $exptId = $s2ds['exptId']; 
      $hasOdd = isset($s2ds['hasOdd']) ? $s2ds['hasOdd'] : 0;
      $hasEven = isset($s2ds['hasEven']) ? $s2ds['hasEven'] : 0;
      $titleSql = sprintf("SELECT * FROM igExperiments WHERE exptId='%s'", $exptId);
      $titleResult = $this->igrtSqli->query($titleSql);
      if ($this->igrtSqli->affected_rows > 0) {
        $titleRow = $titleResult->fetch_object();
        $title = $titleRow->title;
        $oddS1Label = $titleRow->oddS1Label;
        $evenS1Label = $titleRow->evenS1Label;
      }
      else {
        $title = 'not defined';
      }
    $s2RespQry = "SELECT * FROM wt_Step2BalancerRespMax WHERE exptId=$exptId";
    $s2RespResult = $this->igrtSqli->query($s2RespQry);
    if ($this->igrtSqli->affected_rows > 0) {
      $s2RespRow = $s2RespResult->fetch_object();
      $oddRespMax = $s2RespRow->oddRespMax;
      $evenRespMax = $s2RespRow->evenRespMax;
    }
    else {
      $oddRespMax = 20;
      $evenRespMax = 20;        
    }
      // get day and session split, and s1 judge set within each for summary status
      $qryDS = "SELECT * FROM edSessions WHERE exptId=$exptId ORDER BY dayNo ASC, sessionNo ASC";
      $dsResult = $this->igrtSqli->query($qryDS);
      while ($dsRow = $dsResult->fetch_object()) {
        $dayNo = $dsRow->dayNo;
        $sessionNo = $dsRow->sessionNo;
        $evenCnt = $this->getExptJudgeCount($exptId, 0, $dayNo, $sessionNo);
        $oddCnt = $this->getExptJudgeCount($exptId, 1, $dayNo, $sessionNo);
        $tmpE = array('exptId' => $exptId, 'title' => $title, 'dayNo' => $dayNo, 'sessionNo' =>$sessionNo, 'count' => $evenCnt);
        $tmpO = array('exptId' => $exptId, 'title' => $title, 'dayNo' => $dayNo, 'sessionNo' =>$sessionNo, 'count' => $oddCnt);
        array_push($evenCounts, $tmpE);
        array_push($oddCounts, $tmpO);        
      } 
      $rowCnt = count($evenCounts); // $evenCounts and oddCounts should have identical structure, eg. by exptId
      $exptE = array();
      for ($i=0; $i<$rowCnt; $i++) {
        $_exptId = $evenCounts[$i]['exptId'];
        $_title = $evenCounts[$i]['title'];
        $ptr = $this->placeInArray('exptId', $_exptId, $exptE);
        if ($ptr == -1) {
          $newMember = array('exptId' => $_exptId, 'title' => $_title, 'nodes' => array());
          $newNode = array('dayNo' => $evenCounts[$i]['dayNo'], 'sessionNo' => $evenCounts[$i]['sessionNo'], 'count' => $evenCounts[$i]['count'], 'ptr'=> $ptr);
          array_push($newMember['nodes'], $newNode);
          array_push($exptE, $newMember);
        }
        else {
          $newNode = array('dayNo' => $evenCounts[$i]['dayNo'], 'sessionNo' => $evenCounts[$i]['sessionNo'], 'count' => $evenCounts[$i]['count'], 'ptr'=> $ptr);
          array_push($exptE[$ptr]['nodes'], $newNode);
        }
      }
      $exptO = array();
      $rowCnt = count($oddCounts); // $evenCounts and oddCounts should have identical structure, eg. by exptId
      for ($i=0; $i<$rowCnt; $i++) { 
        $_exptId = $oddCounts[$i]['exptId'];
        $_title = $oddCounts[$i]['title'];
        $ptr = $this->placeInArray('exptId', $_exptId, $exptO);
        if ($ptr == -1) {
          $newMember = array('exptId' => $_exptId, 'title' => $_title, 'nodes' => array());
          $newNode = array('dayNo' => $oddCounts[$i]['dayNo'], 'sessionNo' => $oddCounts[$i]['sessionNo'], 'count' => $oddCounts[$i]['count'], 'ptr'=> $ptr);
          array_push($newMember['nodes'], $newNode);
          array_push($exptO, $newMember);
        }
        else {
          $newNode = array('dayNo' => $oddCounts[$i]['dayNo'], 'sessionNo' => $oddCounts[$i]['sessionNo'], 'count' => $oddCounts[$i]['count'], 'ptr'=> $ptr);
          array_push($exptO[$ptr]['nodes'], $newNode);
        }
      }
      $tableRowCnt = count($exptE);
      $optionList = array();
      for ($i=1; $i<100; $i++) {
        $temp = array('id' => $i, 'label' => sprintf("%s", $i));
        array_push($optionList, $temp);
      }
      for ($i=0; $i<$tableRowCnt; $i++) {
        $summaryTxtE = '';
        $summaryTxtO = '';
        $totalE = 0;
        $totalO = 0;
        $exptId = $exptE[$i]['exptId'];
        $title = $exptE[$i]['title'];
        foreach ($exptE[$i]['nodes'] as $node) {
          $summaryTxtE.= sprintf("%sJ: d%s s%s<br />", $node['count'], $node['dayNo'], $node['sessionNo']);
          $totalE += $node['count'];
        }
        $summaryTxtE.= sprintf("%s tot J ", $totalE);

        foreach ($exptO[$i]['nodes'] as $node) {
          $summaryTxtO.= sprintf("%sJ: d%s s%s<br />", $node['count'], $node['dayNo'], $node['sessionNo']);
          $totalO += $node['count'];
        }
        $summaryTxtO.= sprintf("%s tot J", $totalO);
      }
      $html.= "<tr>";
      $html.= sprintf("<td>%s</td>", $title);

      $buttonLegend = "configure $evenS1Label";
      $selectId = sprintf("s2SelectB_%s_1", $exptId);
      $buttonId = sprintf("s2configB_%s_1", $exptId);
      $html.= "<td>$summaryTxtO</td>";
      $html.= "<td>";
      if ($isActiveSet == 0) {
        if ($hasOdd == 1) { 
          $buttonLegend = "reconfigure $evenS1Label";          
        }
        $html.= $this->htmlBuilder->makeSelect($selectId, "", "number", true, $optionList, $this->tabIndex++, $oddRespMax, null, null);
        $html.= "<br />".$this->htmlBuilder->makeButton($buttonId, $buttonLegend, "button");
        $html.= "<br />$evenS1Label pretending to be $oddS1Label";
      }
      else {
        $html.= "<b>started: $evenS1Label pretending to be $oddS1Label</b>";
      }
      $html.= "</td>";

      $buttonLegend = "configure $oddS1Label";
      $selectId = sprintf("s2SelectB_%s_0", $exptId);
      $buttonId = sprintf("s2configB_%s_0", $exptId);
      $html.= "<td>$summaryTxtE</td>";
      $html.= "<td>";
      if ($isActiveSet == 0) {
        if ($hasEven == 1) { 
          $buttonLegend = "reconfigure $oddS1Label";
        }
        $html.= $this->htmlBuilder->makeSelect($selectId, "", "number", true, $optionList, $this->tabIndex++, $evenRespMax, null, null);
        $html.= "<br />".$this->htmlBuilder->makeButton($buttonId, $buttonLegend, "button"); 
        $html.= "<br />$oddS1Label pretending to be $evenS1Label";
      }
      else {
        $html.= "<b>started: $oddS1Label pretending to be $evenS1Label</b>";
      }
      $html.= "</td>";
      $html.= "</tr>";
    }
    $html .= "</table></div></div>";
    return $html;
  }
    
  function getExptDataList() {
    $html = '';
    // find SET A: each expt that has at least one step1 session marked
    $s1ReviewedQry = "SELECT DISTINCT(exptId) FROM edSessions WHERE step1EvenMarked=1 OR step1OddMarked=1 ORDER BY exptId DESC";
    $s1ReviewedResult = $this->igrtSqli->query($s1ReviewedQry);
    $s1ReviewedArray = array();
    if ($this->igrtSqli->affected_rows > 0) {
      while ($s1ReviewedRow = $s1ReviewedResult->fetch_object()) {
        $exptId = $s1ReviewedRow->exptId;
        array_push($s1ReviewedArray, array('exptId' => $exptId));
      }
    }
    
    // find SET B: one step1 marked, one configured, at least one started - TODO: 1 started may be problematical as one half mighht be configured later than 
    // the other
    $s2StartedArray = array();
    $s2StatusQry = "SELECT DISTINCT(t1.exptId) AS exptId FROM edSessions AS t1 JOIN wt_Step2Balancer AS t2 "
        . "WHERE (t1.step1EvenMarked=1 OR t1.step1OddMarked=1) AND t2.respCount>0 AND t1.exptId=t2.exptId ORDER BY exptId DESC";
    $s2StatusResult = $this->igrtSqli->query($s2StatusQry);
    if ($this->igrtSqli->affected_rows > 0) {
      while ($s2StatusRow = $s2StatusResult->fetch_object()) {
        $exptId = $s2StatusRow->exptId;
        array_push($s2StartedArray, array('exptId' => $exptId));
      }
    }
    
    // find SET C: each expt that has at least one step1 session marked, 
    // at least one step2 configured and at least some not started
    $s2ConfiguredArray = array();
    $s2StatusQry = "SELECT DISTINCT(t1.exptId) AS exptId FROM edSessions AS t1 JOIN wt_Step2Balancer AS t2 "
        . "WHERE (t1.step1EvenMarked=1 OR t1.step1OddMarked=1) AND t2.respCount=0 AND t1.exptId=t2.exptId ORDER BY exptId DESC";
    $s2StatusResult = $this->igrtSqli->query($s2StatusQry);
    if ($this->igrtSqli->affected_rows > 0) {
      while ($s2StatusRow = $s2StatusResult->fetch_object()) {
        $exptId = $s2StatusRow->exptId;
        array_push($s2ConfiguredArray, array('exptId' => $exptId));
      }
    }
    
    $s2NotStartedArray = array();
    foreach ($s2ConfiguredArray as $es) {
      $isStarted = false;
      foreach ($s2StartedArray as $started) {
        if ($es['exptId'] == $started['exptId']) {
          $isStarted = true;
        }
      }
      if ($isStarted == false) {
        $e_qry = sprintf("SELECT * FROM wt_Step2Balancer WHERE exptId='%s' AND jType=0", $es['exptId']);
        $this->igrtSqli->query($e_qry);
        $hasEven = ($this->igrtSqli->affected_rows > 0) ? 1 : 0;
        $o_qry = sprintf("SELECT * FROM wt_Step2Balancer WHERE exptId='%s' AND jType=1", $es['exptId']);
        $this->igrtSqli->query($o_qry);
        $hasOdd = ($this->igrtSqli->affected_rows > 0) ? 1 : 0;
        array_push($s2NotStartedArray, array('exptId' => $es['exptId'], 'hasOdd' => $hasOdd, 'hasEven' => $hasEven));
      }
    }
    // remove started or configured from list of all marked
    $s2markedOnlyArray = array();
    foreach ($s1ReviewedArray as $re) {
      $markedOnly = true;
      foreach ($s2ConfiguredArray as $ci) {
        if ($re['exptId'] == $ci['exptId']) { $markedOnly = false; }
      }
      foreach ($s2StartedArray as $si) {
        if ($re['exptId'] == $si['exptId']) { $markedOnly = false; }
      }
      if ($markedOnly == true) {
        array_push($s2markedOnlyArray, $re);
      }
    }
    
    $html = $this->buildHtmlFromSessionList($s2markedOnlyArray, "eligible experiments with no Step2 configuration", "These experiments have been reviewed but not configured.", 0);
    $html.= $this->buildHtmlFromSessionList($s2NotStartedArray, "experiments partially or fully configured", "These experiments have been partially or fully configured. Partial configuration may be deliberate in assymetical experiments.", 0);
    $html.= $this->buildHtmlFromSessionList($s2StartedArray, "experiments configured and started","These experiments should not be reconfigured as they have been started.", 1);
    return $html;
  }
  
 
  //--------------------------------------------------------------------------
  // helpers
  //--------------------------------------------------------------------------   

  function getExptJudgeCount($exptId, $jType, $dayNo, $sessionNo) {
    $discardQry = sprintf("SELECT * FROM wt_Step1Discards WHERE "
        . "exptId='%s' AND dayNo='%s' AND sessionNo='%s'",
        $exptId, $dayNo, $sessionNo);
    $discardResult = $this->igrtSqli->query($discardQry);
    if ($this->igrtSqli->affected_rows > 0) {
      $discardRow = $discardResult->fetch_object();
      $discardValue = ($jType == 0) ? $discardRow->evenDiscards : $discardRow->oddDiscards;
    }
    else {
      $discardValue = 0;
    }
    $getQry = sprintf("SELECT DISTINCT(jNo) FROM md_dataStep1reviewed "
        . "WHERE exptId='%s' AND jType='%s' AND dayNo='%s' AND sessionNo='%s'",
        $exptId, $jType, $dayNo, $sessionNo);
    $qryResult = $this->igrtSqli->query($getQry);
    $includedJCnt = 0;
    while ($qryRow = $qryResult->fetch_object()) {
      $comparand = pow(2, $qryRow->jNo);
      if (($discardValue & $comparand) != $comparand) {
        ++$includedJCnt;
      }
    }   
    return $includedJCnt;
  }
  
  function placeInArray($key, $needle, $haystack) {
    $i = 0;
    foreach ($haystack as $straw) {
      if ($straw[$key] == $needle) { return $i ; }
      ++$i;
    }
    return -1;
  }

  //--------------------------------------------------------------------------
  // constructor and initialisation
  //--------------------------------------------------------------------------   
    
  function __construct($_igrtSqli) {
    $this->igrtSqli = $_igrtSqli;
    $this->htmlBuilder = new htmlBuilder();
    $this->tabIndex = 1;   // 
  }
}
