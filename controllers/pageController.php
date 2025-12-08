<?php
include_once($_SERVER['DOCUMENT_ROOT'].'/config/pageDefinitions.php');
include_once($_SERVER['DOCUMENT_ROOT'].'/helpers/viewBuilder/class.viewBuilder.php');

class PageController {
  private $pageLabel;
  private $sectionNo;
  public $responseData;

  public function __construct($pageLabel = NULL, $sectionNo = NULL) {
    $this->responseData = "";
    $this->pageLabel = $pageLabel;
    $this->sectionNo = $sectionNo;
  }
    
  private function getMappingsPtr($pageLabel) {
    global $pageLabelMappings;
    $i = 0;
    while ($i < count($pageLabelMappings)) {
      if ($pageLabelMappings[$i]['pageLabel'] == $pageLabel) {
        return $i;
      }
      ++$i;
    }
    return -1;
  }
  
  public function invoke($uid, $permissions, $fName, $sName, $email, $referer, $lastChild, $exptId = NULL, $parameters = NULL) {
    global $pageLabelMappings;
    $mappingsPtr = $this->getMappingsPtr($this->pageLabel);
    if ($mappingsPtr > -1) {
      $mappingDetail = $pageLabelMappings[$mappingsPtr];
    }
    else {
      $mappingDetail = $pageLabelMappings[0]; // default to home
    }
    // mainBody is only used in JQM runtime steps and forms
    $mainBody = ""; 
    // switch statement below builds hidden content div - common across all pages
    // which can be overridden in some specific pages
    // which is then wrapped in $mappingDetails files
    $hiddenContents = "<div id=\"hiddenHolder\" style=\"display: none;\">";
    $hiddenContents.= sprintf("<div id=\"hiddenReferer\">%s</div>", $referer);
    $hiddenContents.= sprintf("<div id=\"hiddenChild\">%s</div>", $lastChild);
    $hiddenContents.= sprintf("<div id=\"hiddenUID\">%s</div>",$uid);
    $hiddenContents.= sprintf("<div id=\"hiddenfName\">%s</div>",$fName);
    $hiddenContents.= sprintf("<div id=\"hiddensName\">%s</div>",$sName);
    $hiddenContents.= sprintf("<div id=\"hiddenEmail\">%s</div>",$email);
    $hiddenContents.= sprintf("<div id=\"hiddenPageLabel\">%s</div>", $this->pageLabel);
    $hiddenContents.= sprintf("<div id=\"hiddenSectionNo\">%s</div>", $this->sectionNo);
    $hiddenPermissions = isset($permissions) ? sprintf("<div id=\"hiddenPermissions\">%s</div>",$permissions) : "";  // permissions might be set explicitly later in friendly URL e.g. step form
    $hiddenContents.= $hiddenPermissions;
    $hiddenExptId = isset($exptId) ? sprintf("<div id=\"hiddenExptId\">%s</div>", $exptId) : "";  // exptId might exist in $parameters, so will get created div later
    $hiddenContents.= $hiddenExptId;
    $pageTitle = isset($mappingDetail['pageTitle']) ? $mappingDetail['pageTitle'] : "unset title";
    $hiddenContents.= sprintf("<div id=\"hiddenPageTitle\">%s</div>", $pageTitle);
    switch ($this->pageLabel) {
	    case '0_0_0' : {
		    $restartUID = is_null($parameters) ? 0 : $parameters[0];
		    $hiddenContents.= sprintf("<div id=\"hiddenRestartUID\">%s</div>", $restartUID);
		    break;
	    }

	    // password reset form
	    case '0_0_13' : {
		    $hiddenContents.= sprintf("<input type=\"hidden\" value=\"%s\"  name=\"hiddenEmail\" />", $email);
		    //var_dump($hiddenContents);
		    break;
	    }
	    // pre-Step1 selector as used for DK April 2020
      	    case '9_9_9' : {
		    $exptId = $parameters[1];
	      $dayNo = isset($parameters[2]) ? $parameters[2] : 1;
	      $sessionNo = isset($parameters[3]) ? $parameters[3] : 1;
		    $hiddenContents.= sprintf("<div id=\"hiddenExptId\">%s</div>", $exptId);
		    $hiddenContents.= sprintf("<div id=\"hiddenDayNo\">%s</div>", $dayNo);
		    $hiddenContents.= sprintf("<div id=\"hiddenSessionNo\">%s</div>", $sessionNo);
		    $hiddenContents.= sprintf("<div id=\"hiddenPermissions\">%s</div>", 255);
		    $viewBuilder = new viewBuilderClass();
		    $mainBody = $viewBuilder->makePreStep1Selector($exptId);
		    break;
	    }


      // Step2 runtime with jType specified
      case '5_0_2' : {
        $exptId = $parameters[1];
        $jType = $parameters[2];
        $restartUID = isset($parameters[3]) ? $parameters[3] : -1;
        $respId = 'na'; // obtained during init params
        $hiddenContents.= sprintf("<div id=\"hiddenExptId\">%s</div>", $exptId);
        $hiddenContents.= sprintf("<div id=\"hiddenJType\">%s</div>", $jType);
        $hiddenContents.= sprintf("<div id=\"hiddenRestartUID\">%s</div>", $restartUID);
        $hiddenContents.= sprintf("<div id=\"hiddenRespId\">%s</div>", $respId);
        $hiddenContents.= sprintf("<div id=\"hiddenPermissions\">%s</div>", 255);
        $viewBuilder = new viewBuilderClass();
        $mainBody = $viewBuilder->makeStep2View($exptId, $jType);
        break;
      }
      // inverted Step2 runtime 
      case '5_0_3' : {
        $exptId = $parameters[1];
        $jType = $parameters[2];
        $restartUID = $parameters[3];
        $respId = 'na'; // obtained during init params
        $hiddenContents.= sprintf("<div id=\"hiddenExptId\">%s</div>", $exptId);
        $hiddenContents.= sprintf("<div id=\"hiddenJType\">%s</div>", $jType);
        $hiddenContents.= sprintf("<div id=\"hiddenRestartUID\">%s</div>", $restartUID);
        $hiddenContents.= sprintf("<div id=\"hiddenRespId\">%s</div>", $respId);
        $hiddenContents.= sprintf("<div id=\"hiddenPermissions\">%s</div>", 255);
        $viewBuilder = new viewBuilderClass();
        $mainBody = $viewBuilder->makeInvertedStep2View($exptId, $jType);
        break;
      }
      // Step4 runtime 
      case '6_0_2' : {
        $exptId = $parameters[1];
        $jType = $parameters[2];
        $s4jNo = $parameters[3];          
        $hiddenContents.= sprintf("<div id=\"hiddenExptId\">%s</div>", $exptId);
        $hiddenContents.=sprintf("<div id=\"hiddenS4jNo\">%s</div>", $s4jNo);
        $hiddenContents.=sprintf("<div id=\"hiddenJType\">%s</div>", $jType);
        $hiddenContents.= sprintf("<div id=\"hiddenPermissions\">%s</div>", 255);
        break;
      } 
      // Null experiment Step4 runtime 
      case '6_0_3' : {
        $exptId = $parameters[1];
        $jType = $parameters[2];
        $s4jNo = $parameters[3];          
        $hiddenContents.= sprintf("<div id=\"hiddenExptId\">%s</div>", $exptId);
        $hiddenContents.=sprintf("<div id=\"hiddenS4jNo\">%s</div>", $s4jNo);
        $hiddenContents.=sprintf("<div id=\"hiddenJType\">%s</div>", $jType);
        $hiddenContents.= sprintf("<div id=\"hiddenPermissions\">%s</div>", 255);
        break;
      } 
      // Linked experiment (327, 328, 329, 330) Step4 runtime 
      case '6_0_4' : {
        $s4jNo = $parameters[1];          
        $hiddenContents.=sprintf("<div id=\"hiddenS4jNo\">%s</div>", $s4jNo);
        $hiddenContents.= sprintf("<div id=\"hiddenPermissions\">%s</div>", 255);
        break;
      } 
      // TBT version of Step4  (originally Linked experiment (327, 328, 329, 330) Step4 runtime 
      // but modified for expt 332 - Kasia's injected Step2 into Step2
      case '6_0_5' : {
        if (count($parameters) == 3) {
          $s4jNo = $parameters[2];          
          $exptId = $parameters[1];                    
        }
        else {
          $s4jNo = $parameters[1];          
          $exptId = 328;
        }
        $hiddenContents.=sprintf("<div id=\"hiddenS4jNo\">%s</div>", $s4jNo);
        $hiddenContents.=sprintf("<div id=\"hiddenExptId\">%s</div>", $exptId);
        $hiddenContents.= sprintf("<div id=\"hiddenPermissions\">%s</div>", 255);
        break;
      } 
      case '6_0_6' : {
        // proper generic TBT step4
        $s4jNo = $parameters[2];          
        $exptId = $parameters[1];                    
        $hiddenContents.=sprintf("<div id=\"hiddenS4jNo\">%s</div>", $s4jNo);
        $hiddenContents.=sprintf("<div id=\"hiddenExptId\">%s</div>", $exptId);
        $hiddenContents.= sprintf("<div id=\"hiddenPermissions\">%s</div>", 255);
        break;
      } 
      // any step form/survey
      case '7_0_1' : {
        $exptId = $parameters[1];
        $formType = $parameters[2];
        $jType = isset($parameters[3]) ? $parameters[3] : -1;
        $restartUID = isset($parameters[4]) ? $parameters[4] : -1;
        $respId = isset($parameters[5]) ? $parameters[5] : -1;  // passed from Step2 or inverted Step2
        $viewBuilder = new viewBuilderClass();
        $mainBody = $viewBuilder->makeStepFormView($formType, $exptId, $jType); // build static jqm html
        $hiddenContents.= sprintf("<div id=\"hiddenExptId\">%s</div>", $exptId);
        $hiddenContents.= sprintf("<div id=\"hiddenFormType\">%s</div>", $formType);
        $hiddenContents.= sprintf("<div id=\"hiddenJType\">%s</div>", $jType);
        $hiddenContents.= sprintf("<div id=\"hiddenPermissions\">%s</div>", 255);
        $hiddenContents.= sprintf("<div id=\"hiddenRestartUID\">%s</div>", $restartUID);
        $hiddenContents.= sprintf("<div id=\"hiddenRespId\">%s</div>", $respId);
        break;
      } 

      case '8_3_0' :  // step 1 load datasets for download
      case '3_3_1' :  // step 1 load datasets for review
      {
        $hiddenContents.= sprintf("<div id=\"hiddenJType\">%s</div>",$_POST['jType']);           
        $hiddenContents.= sprintf("<div id=\"hiddenDayNo\">%s</div>",$_POST['dayNo']);
        $hiddenContents.= sprintf("<div id=\"hiddenSessionNo\">%s</div>",$_POST['sessionNo']);           
        break;
      }
      case '5_3_0' :  // monitor Step2 respondent take-up detail page     
      case '5_3_1' :  // load step2 datasets  for edit/review    
      case '5_3_2' :  // monitor inverted-Step2 respondent take-up detail page
      case '5_3_3' :  // load inverted step2 dataset for review
      case '6_3_0' :  // monitor step4 judgesets progress
      case '8_3_0' :  // raw output of step1 data 
      case '8_3_1' :  // step2 answer sets 
      case '8_3_2' :  // inverted step2 answer sets 
      case '8_3_4' :  // step4 quant download
      case '8_3_5' :  // step4 qual download 
      case '8_3_6' :  // ne quant download
      case '8_3_7' :  // ne qual download 
      case '3_2_5' :  // Step2 respondents grouped by QS
      case '3_2_6' :  // inverted Step2 respondents grouped by QS
      case '3_2_4' :  // audit report: Step2 respondents
      case '3_2_2' :  // character encoded raw step4 transcripts
      {
        $hiddenContents.= sprintf("<div id=\"hiddenJType\">%s</div>",$_POST['jType']);           
        break;
      }
      
      case '1_3_1' :  // step form clone     
	      $viewBuilder = new viewBuilderClass();
	      $mainBody = $viewBuilder->makeStepFormCloneView($exptId, $_POST['formType']);
	      $hiddenContents.= sprintf("<div id=\"hiddenFormType\">%s</div>",$_POST['formType']);
	      $hiddenContents.= sprintf("<div id=\"hiddenActiveControl\">%s</div>",$viewBuilder->formActiveControl);
	      break;
	    case '1_3_2' :  // step form definition
      {
        $viewBuilder = new viewBuilderClass();
        //$mainBody = $viewBuilder->makeStepFormConfigurationView($exptId, $_POST['formType']);                           // jqm static page
        
        $hiddenContents.= sprintf("<div id=\"hiddenFormType\">%s</div>",$_POST['formType']);           
        $hiddenContents.= sprintf("<div id=\"hiddenActiveControl\">%s</div>",$viewBuilder->formActiveControl);                   
        break;
      }

	    // Step Form - ineligible page
	    case '7_2_1' : {
		    $hiddenContents.= sprintf("<div id=\"hiddenMsg\">%s</div>",$_POST['ineligibleMsg']);
		    break;
	    }

      // Step survey/form status view page
      case '7_2_2' : {
        $hiddenContents.= sprintf("<div id=\"hiddenExptType\">%s</div>", $_POST['exptType']);           
        $hiddenContents.= sprintf("<div id=\"hiddenExptTitle\">%s</div>", $_POST['exptTitle']);           
        $hiddenContents.= sprintf("<div id=\"hiddenFormType\">%s</div>", $_POST['formType']);           
        break;
      }
	    // Step Form - data detail page
	    case '7_3_1' : {
		    $hiddenContents.= sprintf("<div id=\"hiddenFormType\">%s</div>",trim($_POST['formType']));
		    break;
	    }

	    // classic step1 CSV download
			case '8_3_8_0' : {
				$hiddenContents.= sprintf("<div id=\"hiddenDayNo\">%s</div>",$_POST['dayNo']);
				$hiddenContents.= sprintf("<div id=\"hiddenSessionNo\">%s</div>",$_POST['sessionNo']);
				break;
			}
			// classic step1 transcript display
			case '8_3_8_1' : {
				$hiddenContents.= sprintf("<div id=\"hiddenDayNo\">%s</div>",$_POST['dayNo']);
				$hiddenContents.= sprintf("<div id=\"hiddenSessionNo\">%s</div>",$_POST['sessionNo']);
				break;
			}

      // clone data into injected experiment (S1-only or S1&S2)
//      case '1_2_9' : {
//        $hiddenContents.= sprintf("<div id=\"hiddenButtonId\">%s</div>",$_POST['buttonId']);           
//        break;
//      }

 
    }
    $hiddenContents.= "</div>";
    if (isset($mappingDetail['header'])) {
      $this->responseData.= file_get_contents(sprintf("%s/%s",$_SERVER['DOCUMENT_ROOT'], $mappingDetail['header']));              
    }
    $this->responseData.= $hiddenContents;
    $this->responseData.= $mainBody;  // normally only used by JQM runtime steps and surveys and survey configuration
    $this->responseData.= file_get_contents(sprintf("%s/%s",$_SERVER['DOCUMENT_ROOT'], $mappingDetail['main']));   
    return $this->responseData;
  }
}
    

