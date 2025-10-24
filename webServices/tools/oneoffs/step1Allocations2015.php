<?php
// <editor-fold defaultstate="collapsed" desc=" functions">
  function showMappings($sessionNo, $mappings) {
    echo 'session:'.$sessionNo.' - player count:'.count($mappings).'<br/>';
    for ($i=0; $i<count($mappings); $i++) {
      echo $mappings[$i]['J'].' : '.$mappings[$i]['NP'].' : '.$mappings[$i]['P'].'<br/>';
    }
  }
  
  function checkMappings($mappings1, $mappings2) {
    $cntMappings1 = count($mappings1);
    $cntMappings2 = count($mappings2);
    if ($cntMappings1 != $cntMappings2) {
      echo 'houston<br/>';
    }
    else {
      $interaction1 = array();
      $interaction2 = array();
      $bothSessions = array();
      for ($i=0; $i<$cntMappings1; $i++) {
        array_push($interaction1, array());
        array_push($interaction2, array());
        array_push($bothSessions, array());
        for ($j=0; $j<$cntMappings1; $j++) {
          array_push($interaction1[$i], 0);
          array_push($interaction2[$i], 0);
          array_push($bothSessions[$i], 0);
        }
      }
      for ($i=0; $i<$cntMappings1; $i++) {
        ++$interaction1[$mappings1[$i]['J']][$mappings1[$i]['NP']];
        ++$interaction1[$mappings1[$i]['NP']][$mappings1[$i]['J']];
        ++$interaction1[$mappings1[$i]['J']][$mappings1[$i]['P']];
        ++$interaction1[$mappings1[$i]['P']][$mappings1[$i]['J']];
        ++$interaction1[$mappings1[$i]['P']][$mappings1[$i]['NP']];
        ++$interaction1[$mappings1[$i]['NP']][$mappings1[$i]['P']];
      }
      for ($i=0; $i<$cntMappings2; $i++) {
        ++$interaction2[$mappings2[$i]['J']][$mappings2[$i]['NP']];
        ++$interaction2[$mappings2[$i]['NP']][$mappings2[$i]['J']];
        ++$interaction2[$mappings2[$i]['J']][$mappings2[$i]['P']];
        ++$interaction2[$mappings2[$i]['P']][$mappings2[$i]['J']];
        ++$interaction2[$mappings2[$i]['P']][$mappings2[$i]['NP']];
        ++$interaction2[$mappings2[$i]['NP']][$mappings2[$i]['P']];
      }
      for ($i=0; $i<$cntMappings1; $i++) {
        for ($j=0; $j<$cntMappings2; $j++) {
          $bothSessions[$i][$j] = $interaction1[$i][$j] + $interaction2[$i][$j];
        }
      }
      return array('m1' => $interaction1, 'm2' => $interaction2, 'both' => $bothSessions);
    }
  }
  
  function inValid($interactions) {
    for ($i=0; $i<count($interactions); $i++) {
      $dim2 = $interactions[$i];
      for ($j=0; $j<count($dim2); $j++) {
        if ($dim2[$j] > 1) { return true; }
      }
    }
    return false;
  }
  
  function showCounts($interactions) {
    $dim = count($interactions);
    echo '_ ';
    for ($i=0; $i<$dim; $i++) {
      echo $i.' ';
    }
    echo '<br/>';
    for ($i=0; $i<$dim; $i++) {
      echo $i.' ';
      for ($j=0; $j<$dim; $j++) {
        echo $interactions[$i][$j].' ';
      }
      echo '<br/>';
    }
  }

// </editor-fold>
    

ini_set('display_errors', 'On');
error_reporting(E_ALL);
if (!isset($root_path)) { $root_path = $_SERVER['DOCUMENT_ROOT']; }
include_once $root_path.'/domainSpecific/mySqlObject.php';
include_once($root_path.'/ws/models/class.step1AllocationModel.php');



for ($i=3; $i<30; $i++) {
  $roles1 = new roleAllocations($i, 1);
  if ($roles1->generateFixed()) {
    showMappings(1, $roles1->mappings);
  }
  $roles2 = new roleAllocations($i, 2);
  if ($roles2->generateFixed()) {
    showMappings(2, $roles2->mappings);
  }
  $checkCounts = checkMappings($roles1->mappings, $roles2->mappings);
  //echo print_r($checkCounts, true);
  echo inValid($checkCounts['m1']) ? "session 1 breaks constraints<br/>" : "session 1 valid<br/>"  ;
  if (inValid($checkCounts['m1'])) { showCounts($checkCounts['m1']); }
  echo inValid($checkCounts['m2']) ? "session 2 breaks constraints<br/>" : "session 2 valid<br/>" ;
  echo inValid($checkCounts['both']) ? "repeat sessions break constraints<br/>" : "repeat sessions valid<br/>"  ;
}