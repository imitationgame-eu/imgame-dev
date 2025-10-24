<?php
function JSONparse($text) {
  // Damn pesky carriage returns...
  $text = str_replace("\r\n", "\n", $text);
  $text = str_replace("\r", "\n", $text);

  // JSON requires new line characters be escaped
  $text = str_replace('\n', "\\n", $text);
  $text = stripDoubleApostrophes($text);
//  $text = str_replace("'", "\'", $text);
  // encode for " character
  $text = urldecode($text);
  $text = json_encode($text, JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS);
  switch (json_last_error()) {
      case JSON_ERROR_NONE:
          //echo ' - No errors';
      break;
      case JSON_ERROR_DEPTH:
          $text = ' - Maximum stack depth exceeded';
      break;
      case JSON_ERROR_STATE_MISMATCH:
          $text = ' - Underflow or the modes mismatch';
      break;
      case JSON_ERROR_CTRL_CHAR:
          $text = ' - Unexpected control character found';
      break;
      case JSON_ERROR_SYNTAX:
          $text = ' - Syntax error, malformed JSON';
      break;
      case JSON_ERROR_UTF8:
          $text = ' - Malformed UTF-8 characters, possibly incorrectly encoded';
      break;
      default:
          $text = ' - Unknown error';
      break;
  }
  return $text;
}

function stripDoubleApostrophes($text) {
 while (strpos($text, "''") > 0) {
   $text = str_replace("''", "'", $text);
 }
 return $text;
}

function makeParaArray($text) {
  // Damn pesky carriage returns...
  $text = str_replace("\r\n", "\n", $text);
  $text = str_replace("\r", "\n", $text);
  return explode("\n", $text);
}

function explodeParas($text) {
  $qHtml = '';
  $qParas = explode('\n', $text);
  foreach($qParas as $q) {
    $qHtml.="<p>".$q."</p>";
  }
  return $qHtml;
}

function CSVparse($text) {
  // Damn pesky carriage returns...
  $text = str_replace("\r\n", "\n", $text);
  $text = str_replace("\r", "\n", $text);

  // JSON requires new line characters be escaped
  $text = str_replace("\n", "\\n", $text);

  // manual encode for , character
  $text = str_replace(",", "\,", $text);
  // encode for " character
  $text = json_encode($text);
  return $text;
}

