<?php
//ini_set('display_errors', 'Off');
//error_reporting(E_ALL);
if (!isset($root_path)) { $root_path = $_SERVER['DOCUMENT_ROOT']; }
require_once($root_path.'/domainSpecific/mySqlObject.php');

global $igrtSqli;
$doc = new documentation();

$docHeadersQry = "select * from doc_guide_headers order by id ASC";
$docHeadersResult = $igrtSqli->query($docHeadersQry);
while ($docHeaderRow = $docHeadersResult->fetch_object()) {
  $header = new header();
  $header->headerID = $docHeaderRow->id;
  $header->headerText = $docHeaderRow->headerText;
  $header->sections=[];
  $doc->headers[] = $header;
  
  // get sections for this header
  $sectionNo = 0;
  $sectionsQry = sprintf("SELECT * FROM doc_guide_sections WHERE parentID='%s' ORDER BY id ASC", $docHeaderRow->id);
  $sectionResult = $igrtSqli->query($sectionsQry);
  while ($sectionRow = $sectionResult->fetch_object()) {
    $section = new section();
    $section->sectionID = $sectionRow->id;
    $section->sectionNo = $sectionNo++;
    $section->accordionHeader = $sectionRow->accordionHeader;
    $section->accordionDescription = $sectionRow->accordionDescription;
    $section->content = $sectionRow->content;
    $header->sections[] = $section;
  }
}

echo json_encode($doc);

class documentation {
  public $headers;
}

class header {
  public $headerID;
  public $headerText;
  
  public $sections;
}

class section {
  public $sectionID;
  public $sectionNo;
  public $accordionHeader;
  public $accordionDescription;
  public $content;
}






