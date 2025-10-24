<?php
ini_set('display_errors', 'On');
error_reporting(E_ALL); 
if (!isset($root_path)) { $root_path = $_SERVER['DOCUMENT_ROOT']; }
  include_once $root_path.'/domainSpecific/mySqlObject.php';        
  $exptId=40;
  $clearDataSql = "DELETE FROM dataClassic WHERE exptId='40'";
  $igrtSqli->query($clearDataSql);
  $reset = "UPDATE igActiveClassicUsers SET jState='0', respState='0' WHERE exptId='40'";
  $igrtSqli->query($reset);
  echo "classic 3 role experiment #40 is reset.....";