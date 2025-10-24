<?php
if (!isset($root_path)) { $root_path = $_SERVER['DOCUMENT_ROOT']; }
if (!isset($_GET['exptId'])) { die('no exptId given'); }
$exptId=$_GET['exptId'];
include_once $root_path.'/domainSpecific/mySqlObject.php';       
include_once $root_path.'/helpers/parseJSON.php';
include_once $root_path.'/helpers/models/class.experimentModel.php';

$experiments = [];
$eModel = new experimentModel($exptId);
$title = $eModel->title;
$days = [];
$dsSql = "SELECT noDays,noSessions FROM edExptStatic_refactor WHERE exptId='$exptId'";
$dsResult = $igrtSqli->query($dsSql);
//echo $daySql.'<br/>';
if ($dsResult) {
  $dsRow = $dsResult->fetch_object();
  $noDays = $dsRow->noDays;
  $noSessions = $dsRow->noSessions;
  for ($dayNo=1; $dayNo<=$noDays; $dayNo++) {
    $sessions = [];
    for ($sessionNo=1; $sessionNo<=$noSessions; $sessionNo++) {
      $groups = [];
      $groupSql = sprintf("SELECT DISTINCT(groupNo) FROM dataClassic WHERE exptId='$exptId'"
          . "AND dayNo='%s' AND sessionNo='%s' ORDER BY groupNo ASC", 
          $dayNo, $sessionNo);
        //echo $groupSql.'<br/>';
      $groupResult = $igrtSqli->query($groupSql);
      if ($groupResult) {
        while ($groupRow = $groupResult->fetch_object()) {
          $groupNo = $groupRow->groupNo;
          $turns = [];           
          $turnsSql = sprintf("SELECT * FROM dataClassic WHERE "
              . "exptId='$exptId' AND dayNo='%s' AND sessionNo='%s' AND groupNo='%s' ORDER BY qNo ASC",
              $dayNo, $sessionNo, $groupNo);
            //echo $turnsSql.'<br/>';
          $turnResult = $igrtSqli->query($turnsSql);
          if ($turnResult) {
            $turnNo = 1;
            while ($turnRow = $turnResult->fetch_object()) {
              $owner = $turnRow->owner;
              //$npLeft = $turnRow->npLeft;
              $turn = [
                'owner'=> $turnRow->owner,
                'npLeft'=> $turnRow->npLeft,
                'jQ'=> $turnRow->jQ,
                'npA'=> $turnRow->npA,
                'pA'=> $turnRow->pA,
                'choice'=> $turnRow->choice,
                'confidence'=> $turnRow->confidence,
                'reason'=> $turnRow->reason,
                'turnNo'=> $turnNo++
              ];
              array_push($turns, $turn);
            }
          }
          $group = [
            'goupNo'=> $groupNo,
            'turns'=> $turns
          ];
          array_push($groups, $group);
        }
      }
      $session = [
        'sessionNo'=> $sessionNo,
        'groups'=> $groups
      ];
      array_push($sessions, $session);
    }
    $day = [
      'dayNo'=> $dayNo,
      'sessions'=> $sessions
    ];
    array_push($days, $day);
  }
}
$dayArray = ['exptId'=>$exptId, 'title'=>$title, 'days'=>$days];
array_push($experiments, $dayArray);

// temporarily push out html, but eventually send as JSON for ko-js
// JSON is built and validated at end of file
$html = '';
for ($e=0; $e<count($experiments); $e++) {
  $html.= '<div data-role="collapsible">';
    $html.= '<h2>'. $experiments[$e]['exptId']. ' - ' . $experiments[$e]['title']. '</h2>';
    $html.= '<div>';
      for ($i=0; $i<count($experiments[$e]['days']); $i++) {
        $html.= '<h2>Day ' . $experiments[$e]['days'][$i]['dayNo'] . '</h2>';
        for ($j=0; $j<count($experiments[$e]['days'][$i]['sessions']); $j++) {
          $html.= '<h3>Session '. $experiments[$e]['days'][$i]['sessions'][$j]['sessionNo'] . '</h3>';
          for ($k=0; $k<count($experiments[$e]['days'][$i]['sessions'][$j]['groups']); $k++) { 
            $html.= '<h4>Group # ' . $experiments[$e]['days'][$i]['sessions'][$j]['groups'][$k]['groupNo'] . '</h4>';
            for ($m=0; $m<count($experiments[$e]['days'][$i]['sessions'][$j]['groups'][$k]['turns']); $m++) {
              $choice = $experiments[$e]['days'][$i]['sessions'][$j]['groups'][$k]['turns'][$m]['choice'];
              $npLeft = $experiments[$e]['days'][$i]['sessions'][$j]['groups'][$k]['turns'][$m]['npLeft'];
							if ($eModel->choosingNP == 1) {
								$correct = ($choice != $npLeft) ? "correct" : "incorrect";
							}
							else {
								$correct = ($choice == $npLeft) ? "correct" : "incorrect";
							}
              $html.= "<table width='100%'><tr><td><b>" . $experiments[$e]['days'][$i]['sessions'][$j]['groups'][$k]['turns'][$m]['jQ'] . "<b></td></tr></table>";
              $html.= "<table width='100%'><tr>";
                $html.= "<td color='green'>" . $experiments[$e]['days'][$i]['sessions'][$j]['groups'][$k]['turns'][$m]['npA'] . "</td>";
                $html.= "<td color='red'>" . $experiments[$e]['days'][$i]['sessions'][$j]['groups'][$k]['turns'][$m]['pA'] . "</td>";
              $html.= "</tr></table>";
              $html.= "<table width='100%'><tr>";
                $html.= "<td>" . $correct . "</td>";
                $html.= "<td>" . $experiments[$e]['days'][$i]['sessions'][$j]['groups'][$k]['turns'][$m]['confidence'] . "</td>";
                $html.= "<td width='100%'>" . $experiments[$e]['days'][$i]['sessions'][$j]['groups'][$k]['turns'][$m]['reason'] . "</td>";
              $html.= "</tr></table>";
            }
          }   
        }
      }
    $html.= '</div>';
  $html.= '</div>';  
}
echo $html;


//echo print_r($days, true);
// build json
//$json = "{";
//  $json.= "\"days\":[";
//    for ($i=0; $i<count($days); $i++) {
//      if ($i>0) { $json.=","; }
//      $json.= "{";
//        $json.= "\"dayNo\":\"". $days[$i]['dayNo']. "\",";
//        $json.= "\"sessions\":[";
//        for ($j=0; $j<count($days[$i]['sessions']); $j++) {
//          if ($j>0) { $json.= ","; }
//          $json.= "{";
//            $json.= "\"sessionNo\":\"". $days[$i]['sessions'][$j]['sessionNo']. "\",";
//            $json.= "\"groups\":[";
//            for ($k=0; $k<count($days[$i]['sessions'][$j]['groups']); $k++) {
//              if ($k>0) { $json.=","; }
//              $json.= "{";
//                $json.= "\"groupNo\":\"". $days[$i]['sessions'][$j]['groups'][$k]['groupNo'] . "\",";
//                $json.= "\"turns\":[";
//                  for ($m=0; $m<count($days[$i]['sessions'][$j]['groups'][$k]['turns']); $m++) {
//                    $choice = $days[$i]['sessions'][$j]['groups'][$k]['turns'][$m]['choice'];
//                    $npLeft = $days[$i]['sessions'][$j]['groups'][$k]['turns'][$m]['npLeft'];
//                    $correct = ($choice == $npLeft) ? "correct" : "incorrect";
//                    if ($m>0) { $json.=","; }
//                    $json.= "{";
//                      $json.="\"turnNo\":\"". $days[$i]['sessions'][$j]['groups'][$k]['turns'][$m]['turnNo']. "\",";
//                      $json.="\"jQ\":". JSONparse($days[$i]['sessions'][$j]['groups'][$k]['turns'][$m]['jQ']). ",";
//                      $json.="\"npA\":". JSONparse($days[$i]['sessions'][$j]['groups'][$k]['turns'][$m]['npA']). ",";
//                      $json.="\"pA\":". JSONparse($days[$i]['sessions'][$j]['groups'][$k]['turns'][$m]['pA']). ",";
//                      $json.="\"reason\":". JSONparse($days[$i]['sessions'][$j]['groups'][$k]['turns'][$m]['reason']). ",";
//                      $json.="\"confidence\":\"". $days[$i]['sessions'][$j]['groups'][$k]['turns'][$m]['confidence']. "\",";
//                      $json.="\"correct\":\"". $correct . "\"";
//                    $json.= "}";
//                  }
//                $json.= "]";
//              $json.= "}";
//            }
//            $json.= "]";
//          $json.= "}";
//        }
//        $json.="]";
//      $json.= "}";
//    }
//  $json.= "]";
//$json.= "}";
//echo $json;
