<?php
// -----------------------------------------------------------------------------
// web service to list completed STEP 1 sessions for injection into
// Luke Guppy's Knockout-JS Step1 reviewer
// -----------------------------------------------------------------------------
if (!isset($root_path)) { $root_path = $_SERVER['DOCUMENT_ROOT']; }
require_once($root_path.'/helpers/html/class.htmlBuilder.php');
$permissions=$_GET['permissions'];
$uid=$_GET['uid'];

include_once $root_path.'/domainSpecific/mySqlObject.php';      

if ($permissions>=128) {
  $sqlGetComplete="SELECT * FROM edSessions WHERE step1Complete='1' ORDER by exptId DESC, dayNo ASC, sessionNo ASC";    
  $completeResult=$igrtSqli->query($sqlGetComplete);
  $htmlBuilder = new htmlBuilder();
  $html .= "<div class=\"currentExperiments active\">";
  $html .= "<h2 class=\"closed\">completed Step1 sessions</h2><div>";
  if ($completeResult) {
    $html .= "<table><tr><th>title</th><th>day</th><th>session</th><th width='25%'>Odd players</th><th width='25%'>Even players</th></tr>";
    while ($row = $completeResult->fetch_object()) {
      $exptId = $row->exptId;
      $dayNo = $row->dayNo;
      $sessionNo = $row->sessionNo;
      // get expt title, and attach to day, session etc
      $titleSql = sprintf("SELECT * FROM igExperiments WHERE exptId='%s' AND injectedFlag=0 AND inActive=0", $exptId);
      $titleResult = $igrtSqli->query($titleSql);
      if ($titleResult) {
        $titleRow = $titleResult->fetch_object();
        $title = $titleRow->title;
        $oddS1Label = $titleRow->oddS1Label;
        $evenS1Label = $titleRow->evenS1Label;
        $canList = true;
      }
      else {
        $title = 'not defined';
        $canList = false;
      }
      if ($canList) {
        $html .= "<tr>";
        $html .= sprintf("<td>%s</td><td>%s</td><td>%s</td>", $title, $dayNo, $sessionNo);

        $buttonAction = 0;  //get from original data
        $buttonLegend = "review $oddS1Label";
        $buttonLabel = "first review - will create mirrored data set";
        if ($row->step1OddMarked) {
          $buttonAction = 1;   //get from reviewed data
          $buttonLegend = "Re-edit $oddS1Label review";
          $buttonLabel = "warning: re-editing this data set could affect any started Step2 data sets";
        } 
        $buttonId = sprintf("reviewB_%s_%s_%s_%s_1", $buttonAction, $exptId, $dayNo, $sessionNo);
        $html .= "<td>".$buttonLabel.$htmlBuilder->makeButton($buttonId, $buttonLegend, "button")."</td>";

        $buttonAction = 0;  //get from original data
        $buttonLegend = "review $evenS1Label";
        $buttonLabel = "first review - will create mirrored data set";
        if ($row->step1EvenMarked) {
          $buttonAction = 1;   //get from reviewed data
          $buttonLegend = "Re-edit $evenS1Label review";
          $buttonLabel = "warning: re-editing this data set may affect any started Step2 data sets";
        } 
        $buttonId = sprintf("reviewB_%s_%s_%s_%s_0", $buttonAction, $exptId, $dayNo, $sessionNo);
        $html .= "<td>".$buttonLabel.$htmlBuilder->makeButton($buttonId, $buttonLegend, "button")."</td>";

        $html .= "</tr>";        
      }
    }
    $html .= "</table>";
  }
  else {
    $html .= "<p>No completed Step1 sessions exist.</p>";
  }
  $html .= '</div>';
  // now get partial sessions
  $sql = "SELECT * FROM edSessions WHERE step1Complete='0' ORDER BY exptId DESC, dayNo ASC, sessionNo ASC";
  $exptIdList = $igrtSqli->query($sql);  
  $html .= "<h2 class=\"closed\">partial Step1 sessions</h2><div>";
  if ($exptIdList) {
    $html .= "<table><tr><th>title</th><th>day</th><th>session</th><th width='25%'>Odd players</th><th width='25%'>Even players</th></tr>";
    while ($row = $exptIdList->fetch_object()) {
      $exptId = $row->exptId;
      $dayNo = $row->dayNo;
      $sessionNo = $row->sessionNo;
      $dataExists = sprintf("SELECT * FROM dataSTEP1 WHERE exptId='%s' AND dayNo='%s' and sessionNo='%s'", $exptId, $dayNo, $sessionNo);
      $der = $igrtSqli->query($dataExists);
      if ($der) {
          // get expt title, and attach to day, session etc
          $titleSql = sprintf("SELECT * FROM igExperiments WHERE exptId='%s' AND injectedFlag=0 AND inActive=0", $exptId);
          $titleResult = $igrtSqli->query($titleSql);
          if ($titleResult) {
            $titleRow = $titleResult->fetch_object();
            $title = $titleRow->title;
            $oddS1Label = $titleRow->oddS1Label;
            $evenS1Label = $titleRow->evenS1Label;
            $canList = true;
          }
          else {
            $title = 'not defined';
            $canList = false;
          }
          if ($canList) {
            $html .= "<tr>";
            $html .= sprintf("<td>%s</td><td>%s</td><td>%s</td>",$title, $dayNo, $sessionNo);

            $buttonAction = 2;  //get partial data
            $buttonLegend = "review $oddS1Label";
            $buttonLabel = "first review - will create mirrored data set";
            if ($row->step1OddMarked) {
              $buttonAction = 1;   //get from reviewed data
              $buttonLegend = "Re-edit $oddS1Label review";
              $buttonLabel = "warning: re-editing this data set may affect any started Step2 data sets";
            } 
            $buttonId = sprintf("reviewB_%s_%s_%s_%s_1", $buttonAction, $exptId, $dayNo, $sessionNo);
            $html .= "<td>".$buttonLabel.$htmlBuilder->makeButton($buttonId, $buttonLegend, "button")."</td>";

            $buttonAction = 2;  //get partial data
            $buttonLegend = "review $evenS1Label";
            $buttonLabel = "first review - will create mirrored data set";
            if ($row->step1EvenMarked) {
              $buttonAction = 1;   //get from reviewed data
              $buttonLegend = "Re-edit $evenS1Label review";
              $buttonLabel = "warning: re-editing this data set may affect Step2 data sets";
            } 
            $buttonId = sprintf("reviewB_%s_%s_%s_%s_0", $buttonAction, $exptId, $dayNo, $sessionNo);
            $html .= "<td>".$buttonLabel.$htmlBuilder->makeButton($buttonId, $buttonLegend, "button")."</td>";

            $html .= "</tr>";            
          }
      }
    }
    $html .= "</table>";    
  }
  else {
    $html .= "<p>no incomplete Step1 sessions exist.</p>";
  }
  $html .= '</div>';
  echo $html;
}
