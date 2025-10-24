<?php
// -----------------------------------------------------------------------------
// 
// web service to output S2 respondent audit as RTF
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
$fileName = "S2respondentAudit_" . $exptId . "_" . $jType . ".rtf";
$jsonObject = json_decode($jsonData, true);
//$debug = print_r($jsonObject, true);
//echo $debug;
$rtf = new PHPRtfLite();
// create document styles
$headerFont = new PHPRtfLite_Font(16, 'Arial', '#4f6f9f', '#ffffff');
$datasetHeaderFont = new PHPRtfLite_Font(14, 'Arial', '#4f6f9f', '#ffffff');
$S2SectionHeaderFont = new PHPRtfLite_Font(14, 'Arial', '#8f6f9f', '#ffffff');
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
$summarySection->writeText($jsonObject['jType'] == 0 ? "Even judges" : "Odd Judges" . ' - days: '.$jsonObject['dayCnt'] . ' - sessions: '.$jsonObject['sessionCnt'] . '<br />', $headerFont);
$summarySection->writeText('<br />', $headerFont);
$summarySection->writeText('S2 respondents ignored at Step2 review<br />', $S2SectionHeaderFont);
foreach ($jsonObject['ignoredS2Respondents'] as $s2ppt) {
  $summarySection->writeText('s2resp '.$s2ppt['index'].' (d:'.$s2ppt['dayNo'].' s:'.$s2ppt['sessionNo'].' actualJNo:'.$s2ppt["actualJNo"].' jNo:'.$s2ppt['jNo]'].' respNo:'.$s2ppt['respNo'].' reviewedRespNo:'.$s2ppt['reviewedRespNo'].'<br />', $headerFont);
  $summarySection->writeText('Turns=>', $datasetHeaderFont);
 foreach($s2ppt['turns'] as $turn) {
    if ($turn['qNo'] % 2 == 0) {
      $summarySection->writeText('Q'.$turn['qNo'].':'.$turn['q'].'<br /> ', $turnsFontQDark);
      $summarySection->writeText($turn['reply'].'<br />', $turnsFontRDark);            
     }
    else {
      $summarySection->writeText('Q'.$turn['qNo'].':'.$turn['q'].'<br /> ', $turnsFontQLight);
      $summarySection->writeText($turn['reply'].'<br />', $turnsFontQLight);            
    }    
  }
}
$summarySection->insertPageBreak();

$summarySection->writeText('S2 discarded or restarted due to error<br />', $S2SectionHeaderFont);
foreach ($jsonObject['discardedS2Respondents'] as $s2ppt) {
  $summarySection->writeText('s2resp '.$s2ppt['index'].' (d:'.$s2ppt['dayNo'].' s:'.$s2ppt['sessionNo'].' actualJNo:'.$s2ppt["actualJNo"].' jNo:'.$s2ppt['jNo]'].' respNo:'.$s2ppt['respNo'].' reviewedRespNo:'.$s2ppt['reviewedRespNo'].'<br />', $headerFont);
  $summarySection->writeText('date-time started: '.$s2ppt['chrono'].'<br />', $headerFont);
  $summarySection->writeText('Turns=>', $datasetHeaderFont);
 foreach($s2ppt['turns'] as $turn) {
    if ($turn['qNo'] % 2 == 0) {
      $summarySection->writeText('Q'.$turn['qNo'].':'.$turn['q'].'<br /> ', $turnsFontQDark);
      $summarySection->writeText($turn['reply'].'<br />', $turnsFontRDark);            
     }
    else {
      $summarySection->writeText('Q'.$turn['qNo'].':'.$turn['q'].'<br /> ', $turnsFontQLight);
      $summarySection->writeText($turn['reply'].'<br />', $turnsFontQLight);            
    }    
  }
}
$summarySection->insertPageBreak();

$summarySection->writeText('S2 used in Step4<br />', $S2SectionHeaderFont);
foreach ($jsonObject['goodS2Respondents'] as $s2ppt) {
  $summarySection->writeText('s2resp '.$s2ppt['index'].' (d:'.$s2ppt['dayNo'].' s:'.$s2ppt['sessionNo'].' actualJNo:'.$s2ppt["actualJNo"].' jNo:'.$s2ppt['jNo]'].' respNo:'.$s2ppt['respNo'].' reviewedRespNo:'.$s2ppt['reviewedRespNo'].'<br />', $headerFont);
  $summarySection->writeText('Turns=>', $datasetHeaderFont);
 foreach($s2ppt['turns'] as $turn) {
    if ($turn['qNo'] % 2 == 0) {
      $summarySection->writeText('Q'.$turn['qNo'].':'.$turn['q'].'<br /> ', $turnsFontQDark);
      $summarySection->writeText($turn['reply'].'<br />', $turnsFontRDark);            
     }
    else {
      $summarySection->writeText('Q'.$turn['qNo'].':'.$turn['q'].'<br /> ', $turnsFontQLight);
      $summarySection->writeText($turn['reply'].'<br />', $turnsFontQLight);            
    }    
  }
}
$summarySection->insertPageBreak();



// send to browser
$rtf->sendRtf($fileName);

