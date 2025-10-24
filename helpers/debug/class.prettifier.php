<?php
/**
 * prettify print_r output 
 * @author mh
 */

class prettifier {
  private $source;
      
  function __construct($_source) {
    $this->source = $_source;
  } 
  
  function chunk($delimiter, $source) {
    $firstpos = strpos($source, $delimiter);
    $lChunk = substr($source, 0, $firstpos);
    $rChunk = substr($source, $firstpos + 2);
    $ret = [$lChunk, $rChunk];
    return $ret;
  }
  
  function isArray($source) {
    return (substr($source, 0 ,7) == ' Array ') ? true : false; 
  }
  
  function getObjectChunks($source) {
    $firstpos = strpos($source, '[');
    $lChunk = substr($source, 0, $firstpos);
    $rChunk = substr($source, $firstpos);
    $ret = [$lChunk, $rChunk];
    return $ret;
  }
  
  function hasSubArray(&$source) {
    $firstPos = strpos($source, '=> Array');
    if (!$firstPos) {
      return false;
    }
    else {
      $openPos = strpos($source, '(');
      $closePos = strpos($source, ')');
      $arrayValue = substr($source, $openPos, $closePos-$openPos);
      $residue = substr($source, $closePos+1);
      $source = $residue;
      return $arrayValue;
    }
  }
  
  function getArrayChunks(&$source) {
    $ret = [];
    while ($subArray = $this->hasSubArray($source)) {
      array_push($ret, $subArray);
    }
  }
  
  function getObject(&$residue) {
    $chunks = $this->chunk('=>', $residue);
    $html = $chunks[0].' : ';
    if ($this->isArray($chunks[1])) {
      $objectChunks = $this->getArrayChunks($chunks[1]);
    }
    else {
      $objectChunks = $this->getObjectChunks($chunks[1]);
    }    
    $html.= $objectChunks[0];
    $residue = $objectChunks[1];
//    echo $html.'<br/>';
    return $html.'<br/>';
  }
  
  function getHTML() {
    $html = '';
    $split = explode('Object', $this->source );
    $objectName = $split[0];
    $residue = substr($split[1], 2);
    while (strlen($residue) > 0) { $html.= $this->getObject($residue); }
    return $objectName.'<br/>'.$html;
  }

}





    
