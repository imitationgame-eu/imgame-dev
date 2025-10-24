<?php
// -----------------------------------------------------------------------------
// 
// web service to output complete experiment details as RTF
// 
// -----------------------------------------------------------------------------
if (!isset($root_path)) { $root_path = $_SERVER['DOCUMENT_ROOT']; }
include_once $root_path.'/domainSpecific/mySqlObject.php';      
include_once $root_path.'/helpers/parseJSON.php'; 
require_once $root_path.'/helpers/rtf/PHPRtfLite.php';
PHPRtfLite::registerAutoloader();
$permissions = $_POST['permissions'];
$uid = $_POST['uid'];
$exptId = $_POST['exptId'];
$jType = $_POST['jType'];
$jsonData = $_POST['jsonData'];
$fileName = "expt_" . $exptId . "_" . $jType . ".rtf";
$jsonObject = json_decode($jsonData, true);
//$debug = print_r($jsonObject, true);
//echo $debug;
$rtf = new PHPRtfLite();
// create document styles
$headerFont = new PHPRtfLite_Font(16, 'Arial', '#4f6f9f', '#ffffff');
$datasetHeaderFont = new PHPRtfLite_Font(14, 'Arial', '#4f6f9f', '#ffffff');
$datasetSummaryFont = new PHPRtfLite_Font(12, 'Arial', '#1f2f4f', '#ffffff');
$turnsFontQDark = new PHPRtfLite_Font(12, 'Arial', '#3f5f9f', '#dfdfdf');
$turnsFontRDark = new PHPRtfLite_Font(12, 'Arial', '#4f7faf', '#cfcfcf' );
$turnsFontQLight = new PHPRtfLite_Font(12, 'Arial', '#4f6faf', '#efefef' );
$turnsFontRLight = new PHPRtfLite_Font(12, 'Arial', '#5f8fbf', '#dfdfdf' );
$discardedFont = new PHPRtfLite_Font(12, 'Arial', '#af8f8f', '#ffffff' );
$playerFont = new PHPRtfLite_Font(12, 'Arial', '#0f8f8f', '#ffffff' );
// summary section
$summarySection = $rtf->addSection();
$summarySection->writeText($jsonObject['exptTitle'].' (#'.$jsonObject['exptId'].')<br />', $headerFont);
$summarySection->writeText('mean pass rate: '. $jsonObject['meanPassRate'].'<br />', $headerFont);
//$step1Section = $rtf->addSection();

foreach ($jsonObject['completeQsets'] as $qs) {
  $summarySection->writeText('qSet: '.$qs['igrNo'].' (d:'.$qs['dayNo'].' s:'.$qs['sessionNo'].' s1jNo:'.$qs["step1jNo"].')<br />', $headerFont);
  $summarySection->writeText('Step1 details<br />', $datasetHeaderFont);
  $summarySection->writeText('crude step1 pass rate: '.$qs['s1passRate'].'<br />', $datasetSummaryFont);
  $summarySection->writeText('step4 pass rate: '.$qs['s4passRate'].'<br />', $datasetSummaryFont);
  $summarySection->writeText('final choice: '.$qs['finalCorrect'].'<br />', $datasetSummaryFont);
  $summarySection->writeText('final confidence: '.$qs['finalConfidence'].'<br />', $datasetSummaryFont);
  $summarySection->writeText('final reason: '.$qs['finalReason'].'<br />', $datasetSummaryFont);
  $summarySection->writeText('<br />turns<br />', $datasetHeaderFont);
  foreach($qs['questions'] as $turn) {
    if ($turn['s1CanUse'] == 0) { 
      $summarySection->writeText('DISCARDED TURN:<br /> ', $discardedFont);
    }
    $summarySection->writeText('Judge Q:'.$turn['qNo'].'<br /> ', $playerFont);
    if ($turn['qNo'] % 2 == 0) {
      $summarySection->writeText($turn['q'].'<br />', $turnsFontQDark);      
      $summarySection->writeText('NP response<br /> ', $playerFont);
      $summarySection->writeText($turn['npr'].'<br />', $turnsFontRDark);            
      $summarySection->writeText('P response<br /> ', $playerFont);
      $summarySection->writeText($turn['pr'].'<br />', $turnsFontRDark);      
      $summarySection->writeText(($turn['s1correct']==1 ? 'correct' : 'incorrect').'<br />', $turnsFontRDark);      
      $summarySection->writeText('confidence:'.$turn['s1confidence'].'<br />', $turnsFontRDark);      
      $summarySection->writeText('reason:'.$turn['s1reason'].'<br />', $turnsFontRDark);      
     }
    else {
      $summarySection->writeText($turn['q'].'<br />', $turnsFontQLight);            
      $summarySection->writeText('NP response<br /> ', $playerFont);
      $summarySection->writeText($turn['npr'].'<br />', $turnsFontRLight);            
      $summarySection->writeText('P response<br /> ', $playerFont);
      $summarySection->writeText($turn['pr'].'<br />', $turnsFontRLight);      
      $summarySection->writeText(($turn['s1correct']==1 ? 'correct' : 'incorrect').'<br />', $turnsFontRLight);      
      $summarySection->writeText('confidence:'.$turn['s1confidence'].'<br />', $turnsFontRLight);      
      $summarySection->writeText('reason:'.$turn['s1reason'].'<br />', $turnsFontRLight);      
    }    
  }
  $summarySection->writeText('<br />step2 pretenders<br />', $datasetHeaderFont);  
  foreach($qs['step2Pretenders'] as $s2p) {
    $summarySection->writeText('logicalP:'.$s2p['logicalP'].' actualP:'.$s2p['respNo'].'<br />', $datasetHeaderFont);  
    foreach ($s2p['answers'] as $ans) {
      if ($ans['canUse'] == 0) {
        $summarySection->writeText('DISCARDED s2 turn:<br /> ', $discardedFont);        
      }
      if ($ans['qNo'] % 2 == 0) {
        $summarySection->writeText($ans['qNo'].': '.$ans['pr'].'<br />', $turnsFontQDark);              
      }
      else {
        $summarySection->writeText($ans['qNo'].': '.$ans['pr'].'<br />', $turnsFontQLight);                      
      }      
    }
    if ($s2p["s4jNo1"] == -1) {
      $summarySection->writeText('step4 first half judge not complete<br />', $datasetHeaderFont);        
    }
    else {
      $summarySection->writeText('P'.$s2p['logicalP'].':1 s4jNo:'.$s2p['s4jNo1'].'<br />', $datasetHeaderFont);        
      $summarySection->writeText('judgement:'.($s2p['correct1']==1 ? 'correct' : 'incorrect').'<br />', $playerFont);      
      $summarySection->writeText('confidence:'.$s2p['confidence1'].'<br />', $playerFont);      
      $summarySection->writeText('reason:'.$s2p['reason1'].'<br />', $playerFont);            
    }
    if ($s2p["s4jNo2"] == -1) {
      $summarySection->writeText('step4 second half judge not complete<br />', $datasetHeaderFont);        
    }
    else {
      $summarySection->writeText('P'.$s2p['logicalP'].':2 s4jNo:'.$s2p['s4jNo2'].'<br />', $datasetHeaderFont);        
      $summarySection->writeText('judgement:'.($s2p['correct2']==1 ? 'correct' : 'incorrect').'<br />', $playerFont);      
      $summarySection->writeText('confidence:'.$s2p['confidence2'].'<br />', $playerFont);      
      $summarySection->writeText('reason:'.$s2p['reason2'].'<br />', $playerFont);            
    }
  }
  $summarySection->insertPageBreak();
}


// send to browser
$rtf->sendRtf($fileName);

