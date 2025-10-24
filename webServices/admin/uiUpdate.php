<?php
ini_set('display_errors', 'On');
error_reporting(E_ALL);
if (!isset($root_path)) { $root_path = $_SERVER['DOCUMENT_ROOT']; }
require_once($root_path.'/domainSpecific/mySqlObject.php');
$permissions = $_GET['permissions'];
$uid = $_GET['uid'];
$controlName = $_GET['controlName'];
$content = $_GET['content'];

  function processMessage($_uid, $_controlName, $_content) {
    global $igrtSqli;
    $controlDetails = explode('_', $_controlName);
    switch ($controlDetails[0]) {
    	// id is of form tl_x
			case 'tl': {
				$sql = sprintf("UPDATE ui_topLevelSectionsUserStatus SET isClosed='%s' WHERE accordionId='%s' AND uid='%s'",$_content[0], $controlDetails[1], $_uid);
				break;
			}
			case 'sl1': {
				// id is of form sll_x_y
				switch ($controlDetails[1]) {
					case '1':
						$sql = sprintf("UPDATE ui_experimentCategoriesUserStatus SET isClosed='%s' WHERE categoryId='%s' AND userId='%s'",$_content[0], $controlDetails[2], $_uid);
						break;
					case '2':
						$sql = sprintf("UPDATE ui_sectionCategoriesUserStatus SET isClosed='%s' WHERE sectionId='%s' AND userId='%s'",$_content[0], $controlDetails[2], $_uid);
						break;
				}
				break;
			}
	    case 'egm': {
		    $sql = sprintf("UPDATE ui_exptGroup2UserMappings SET isClosed='%s' WHERE groupId='%s' AND uid='%s'",$_content[0], $controlDetails[1], $_uid);
	    	break;
	    }
		}
    //echo $sql;
    $igrtSqli->query($sql);
  }

//ensure admin
if ($permissions >= 128) {
  processMessage($uid, $controlName, $content);
}
