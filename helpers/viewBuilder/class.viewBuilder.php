<?php
/**
 * Build JQM views (runtime forms and step views) 
 * for injection between header and footer
 *
 * @author mh
 */
//if (!isset($root_path)) {
//  $full_ws_path=realpath(dirname(__FILE__));
//  $root_path=substr($full_ws_path, 0, strlen($full_ws_path)-19);
//}
if (!isset($root_path)) { $root_path = $_SERVER['DOCUMENT_ROOT']; }
include_once $root_path.'/domainSpecific/mySqlObject.php';
include_once $root_path.'/helpers/models/class.experimentModel.php';
include_once $root_path.'/helpers/forms/class.stepFormsHandler.php';
//include_once $root_path.'/kint/Kint.class.php';
include_once $root_path.'/helpers/parseJSON.php';

class viewBuilderClass {
  
  // <editor-fold defaultstate="collapsed" desc=" members">
  private $formType;
  private $exptId;
  private $jType;
  private $formDef;
  private $eModel;
  private $formName;
  private $selectControlList = [];
  private $allControlList = [];
  public $formActiveControl;
  // </editor-fold>
  
  // <editor-fold defaultstate="collapsed" desc=" private generic helpers">
  
  private function getSelectControlList() {
    return [
	    ['cValue'=>5,'cLabel'=>'radiobutton'],
      ['cValue'=>6,'cLabel'=>'select'],
      ['cValue'=>7,'cLabel'=>'slider']      
    ];
  }
  
  private function getAllControlList() {
    global $igrtSqli;
    $sql = "SELECT * FROM igControlTypes ORDER BY cValue ASC";
    $result = $igrtSqli->query($sql);
    $controlList = [];
    if ($result->num_rows>0) {
      while ($row = $result->fetch_object()) {
        array_push($controlList, ['cValue'=> $row->cValue, 'cLabel'=>$row->cLabel]);
      }
    }
    return $controlList;
  }
  
  // e.g. for filter question
  private function isSelector($qType) {
    for ($i=0; $i<count($this->selectControlList); $i++) {
      if ($qType == $this->selectControlList[$i]['cValue']) { return true; }
    }
    return false;
  }

  // question that needs to show the options
  private function needsOptions($qType) {
    switch ($qType) {
	    case 0:
	    case 5:
	    case 6:
	    case 7:
	    case 8:
	    {
	    	return true;
	    }
	    default:
	    	return false;
    }
  }

  private function makeSelectElement($label, $id, $question, $bindField = null) {
	  $element = '';
	  $element.= sprintf("<label class=\"select\" for=\"%s\">%s</label>", $id, $label);
	  $element.= sprintf("<select name=\"%s\" id=\"%s\" value=\"\" data-native-menu=\"false\">", $id, $id);
	  $element.= "<option>".$label."</option>";
	  foreach($question['options'] as $option) {
	  	$element.= sprintf("<option value='\"%\"'>%s</option>", $option['id'], $option['label']);
	  }
	  $element.= "</select>";
	  return $element;
  }

  private function makeDateElement($label, $id, $bindField = null) {
	  $element = '';
	  $element.= sprintf("<label for=\"%s\">%s</label>", $id, $label);
	  $element.= sprintf("<input name=\"%s\" id=\"%s\" value=\"\" type=\"date\" data-clear-btn=\"true\">", $id, $id);
	  return $element;
  }

  private function makeEmailElement($label, $id, $bindField = null) {
	  $element = '';
	  $element.= sprintf("<label for=\"%s\">%s</label>", $id, $label);
	  $element.= sprintf("<input name=\"%s\" id=\"%s\" value=\"\" type=\"email\" data-clear-btn=\"true\">", $id, $id);
	  return $element;
  }

  private function makeCheckboxElement($name, $id, $label, $bindField = null) {
    $element = '';
    $element.= sprintf("<input name=\"%s\" id=\"%s\" type=\"checkbox\">", $name, $id);
    $element.= sprintf("<label id=\"%s\" for=\"%s\">%s</label>", 'label_' .$id, $id, $label);
    return $element;
  }

  private function makeRadioElement($name, $id, $label, $bindField = null) {
    $element = '<label>';
    if (is_null($bindField)) {
      $element.= sprintf("<input type=\"radio\" name=\"%s\" "
          . "id=\"%s\" value=\"%s\"/>",
          $name, $id, $label);     
    }
    else {
      $element.= sprintf("<input type=\"radio\" name=\"%s\" "
          . "id=\"%s\" value=\"%s\" data-bind=\"checked: %s\"/>",
          $name, $id, $label, $bindField);      
    }
    $element.= $label . "</label>";
    return $element;
  }
  
  private function makeButtonElement($label, $id, $bindField = null) {
    if (is_null($bindField)) {
      $element = sprintf("<input type=\"button\" id=\"%s\" value=\"%s\" />", $id, $label);            
    }
    else {
      $element = sprintf("<input type=\"button\" id=\"%s\" onclick=\"%s()\" value=\"%s\" />", $id, $bindField, $label);      
    }
    return $element;
  }
  
  private function makeTextAreaElement($label, $id, $bindField = null) {
    $element = "<h4>" . $label . "</h4>";
    if (is_null($bindField)) {
      $element.= sprintf("<textarea name=\"%s\" id=\"%s\" value=\"\"></textarea>", $id, $id); 
    }
    else {
      $element.= sprintf("<textarea name=\"%s\" id=\"%s\" onkeyup=\"%s()\" value=\"\"></textarea>", $id, $id, $bindField);       
    }
    return $element;
  }

  private function makeNumericElement($label, $id, $max, $bindField = null) {
    $element = "<h4>" . $label . "</h4>";
    if (is_null($bindField)) {
      $element.= sprintf("<input type=\"number\" name=\"%s\" min=\"1\" max=\"%s\" id=\"%s\" value=\"1\" />", $id, $max, $id);
    }
    else {
      $element.= sprintf("<input type=\"number\" name=\"%s\" min=\"1\" max=\"%s\" id=\"%s\" onkeyup=\"%s()\" value=\"\" />", $id, $max, $id, $bindField);
    }
    return $element;
  }

  private function makeSliderElement($label, $id, $question, $bindField = null) {
	  $element = '';
	  $element.= sprintf("<label for=\"%s\">%s</label>", $id, $label);
	  $element.= sprintf("<input name=\"%s\" id=\"%s\" type=\"range\" min=\"0\" max=\"%s\" value=\"0\" data-highlight=\"true\">", $id, $id, $question['qContinuousSliderMax']);
	  return $element;
  }

  private function isPreStepForm() {
  	return ($this->formType == 2 || $this->formType ==6 || $this->formType == 12 || $this->formType == 10);
  }
  
  // </editor-fold>
  
  // <editor-fold defaultstate="collapsed" desc=" runtime step-form helpers">
  
  private function buildJQMStepFormQuestion($logicalPageNo, $logicalQNo, $question, $isFilterPage, $isFilterQ) {
    $view = "";
    $qpf = "response_";
    if ($isFilterPage) {
	    $qpf = $isFilterQ ? "fq_" : "fqresponse_";
    }
    switch ($question['qType']) {
      case 0: {
        // cb
	      $view.= "<fieldset data-role=\"controlgroup\" ><div id=\"q_".$logicalPageNo."_".$logicalQNo."_".$question['qMandatory']."_0\">";
	      $view.= "<legend>". $question['qLabel']. "</legend>";
	      for ($i=0; $i<count($question['options']); $i++) {
		      $view.= $this->makeCheckBoxElement($qpf ."cb_".$logicalPageNo."_".$logicalQNo, $qpf."cb_".$logicalPageNo."_".$logicalQNo . "_" . $i , $question['options'][$i]['label'], "");
	      }
	      $view.= "</div></fieldset>";
        break;
      }
      case 1:
      case 2: {
        // single line or multi line edit are same in JQM
        $view.= "<fieldset><div id=\"q_".$logicalPageNo."_".$logicalQNo."_".$question['qMandatory']."_2\">";
          $view.= $this->makeTextAreaElement($question['qLabel'], $qpf."ta_".$logicalPageNo. "_" .$logicalQNo , "");
        $view.= "</div></fieldset>";
        break;
      }
      case 3: {
        // email
	      $view.= $this->makeEmailElement($question['qLabel'], $qpf."email_".$logicalPageNo. "_" .$logicalQNo , "");
        break;
      }
      case 4: {
        // datetime
	      $view.= $this->makeDateElement($question['qLabel'], $qpf."date_".$logicalPageNo. "_" .$logicalQNo);
        break;
      }
      case 5: {
        // radio button
          $view.= "<fieldset data-role=\"controlgroup\" data-type=\"horizontal\"><div id=\"q_".$logicalPageNo."_".$logicalQNo."_".$question['qMandatory']."_5\">";
          $view.= "<legend>". $question['qLabel']. "</legend>";
          for ($i=0; $i<count($question['options']); $i++) {
            $view.= $this->makeRadioElement($qpf ."rb_".$logicalPageNo."_".$logicalQNo, $qpf."rb_".$logicalPageNo."_".$logicalQNo . "_" . $i , $question['options'][$i]['label'], "");
          }
          $view.= "</div></fieldset>";
        break;
      }
      case 6: {
        // select
	      $view.= $this->makeSelectElement($question['qLabel'], $qpf."select_".$logicalPageNo. "_" .$logicalQNo , $question);
        break;
      }
      case 7:
      case 8: {
        // slider
	      $view.= $this->makeSliderElement($question['qLabel'], $qpf."slider_".$logicalPageNo. "_" .$logicalQNo , $question);
        break;
      }
      case 9: {
        // radio button grid
        break;
      }
      case 10: {
        // numeric text input
        $view.= "<fieldset><div id=\"q_".$logicalPageNo."_".$logicalQNo."_".$question['qMandatory']."_10\">";
          $view.= $this->makeNumericElement($question['qLabel'], $qpf."numeric_".$logicalPageNo. "_" .$logicalQNo , $question['qContinuousSliderMax']);
          $view.= "</div></fieldset>";
        break;
      }
    }
    return $view;
  }
  
  private function buildJQMStepFormPage($defPageNo, $logicalPageNo) {
    $page = $this->formDef['pages'][$defPageNo];
    $logicalQNo = 0;
    $view = sprintf("<div id=\"page_%s\" style=\"display: none;\">", $logicalPageNo);
      $view.= sprintf("<h2>%s</h2>", $page['pageTitle']);
      $view.= explodeParas($page['pageInst']);
      if ($page['useFilter'] == 1) {
        $view.= sprintf("<div id=\"filterSection_%s\">", $logicalPageNo);
          $view.= $this->buildJQMStepFormQuestion($logicalPageNo, $logicalQNo++, $page['questions'][0], true, true);
          $view.= $this->makeButtonElement($page['pageButtonLabel'], "fsb_$logicalPageNo", ""); 
        $view.= "</div>"; 
        // now make N sub-questions representing each filtered section which can be made visible when filter selection confirmed
        for ($i=0; $i<count($page['questions'][0]['options']); $i++) {
          $view.= sprintf("<div id=\"filterResponseSection_%s_%s\" style=\"display: none;\">", $logicalPageNo, $i);
            for ($j=1; $j<count($page['questions']); $j++) {
              if ($page['questions'][$j]['qContingentValue'] == $i) {
                $view.= $this->buildJQMStepFormQuestion($logicalPageNo, $logicalQNo++, $page['questions'][$j], true, false);
              }
            }
          $view.= "</div>";
        }
        $view.= sprintf("<div id=\"fButtonSection_%s\" style=\"display: none\">", $logicalPageNo);
          $view.= $this->makeButtonElement($page['pageButtonLabel'], "frsb_".$logicalPageNo, ""); 
        $view.= "</div>";       
      }
      else {
        $view.= sprintf("<div id=\"nonFilterSection_%s\">", $logicalPageNo);
        for ($i=0; $i<count($page['questions']); $i++) {
          $view.= $this->buildJQMStepFormQuestion($logicalPageNo, $logicalQNo++, $page['questions'][$i], false, false);
        }
        $view.= $this->makeButtonElement($page['pageButtonLabel'], "nfsb_$logicalPageNo", "");         
        $view.= "</div>";
      }
    $view.= "</div>";
    return $view;
  }

  private function buildJQMStepFormHeader() {
    $view = "<div data-role=\"page\" data-theme=\"b\">";
    $view.= "<div data-role=\"header\" data-position=\"inline\">";
      $view.= "<h1>". $this->formDef['formTitle']."</h1>";
    $view.= "</div>";
    return $view;
  }

  private function buildJQMStepFormControls($jType) {
    $view = "<div data-role=\"content\" data-theme=\"c\">";
    $view.= "<div id='container'>";

	  $view.= "<div id=\"introPageSectionTop\"data-theme=\"c\">";
	  $view.= "<h3>" . $this->formDef['introPageTitle'] . "</h3>";
	  $view.= explodeParas($this->formDef['introPageMessage']);
	  $view.= "</div>";

	  $view.= "<div id=\"instructionSection\">";
	  $view.= explodeParas($this->formDef['formInst']);
	  $view.= "</div>";

	  if ($this->formDef['useRecruitmentCode'] == 1) {
		  $view.= "<div id=\"recruitmentCodeSection\" >";
		  $view.= "<legend>". $this->formDef['recruitmentCodeMessage']. "</legend>";
		  $view.= $this->makeRadioElement("rec", "rec_0", $this->formDef['recruitmentCodeYesLabel'], "");
		  $view.= $this->makeRadioElement("rec", "rec_1", $this->formDef['recruitmentCodeNoLabel'], "");
		  $view.= $this->makeTextAreaElement($this->formDef['recruitmentCodeLabel'], "recruitmentCodeTA", "processRecruitmentCode");
		  $view.= $this->makeButtonElement($this->formDef['introPageButtonLabel'], "processRecruitmentB", "processRecruitment");
		  $view.= "</div>";
	  }

	  if ($this->formDef['useEligibilityQ'] == 1) {
      //make eligibility section
      $view.= "<div id=\"eligibilitySection\" style=\"display: none;\">";

      $view.= "<fieldset data-role=\"controlgroup\" data-bind=\"value: eligibilityQAnswerText\" data-type=\"horizontal\">";
      $view.= "<legend>". $this->formDef['eligibilityQ']['qLabel']. "</legend>";
      for ($i=0; $i<count($this->formDef['eligibilityQ']['options']); $i++) {
      	$jType = $this->formDef['eligibilityQ']['options'][$i]['jType'];
        $view.= $this->makeRadioElement("eq", "eq_".$jType, $this->formDef['eligibilityQ']['options'][$i]['label']);  // binding in js so no bindField
      }
      $view.= "</fieldset>";

		  $view.= "<div id=\"introPageSectionBottom\">";
		  $view.= $this->makeButtonElement($this->formDef['introPageButtonLabel'], "processEligibilityB", "processEligibility");
		  $view.= "</div>";

		  $view.= "</div>";

		  $view.= "<div id=\"ineligibilitySection\" style=\"display: none;\">";
		  $view.= "<h3>". $this->formDef['eligibilityQ']['qNonEligibleMsg']. "</h3>";

		  $view.= "</div>";
	  }

	  if ($this->formDef['useRecruitmentCode'] == 0 && $this->formDef['useEligibilityQ'] == 0) {
        $view.= "<div id=\"introPageSectionBottom\">";
          $view.= $this->makeButtonElement($this->formDef['introPageButtonLabel'], "processAcceptB", "processAccept");
        $view.= "</div>";
	  }

    $view.= "<div id=\"pageSections\" style=\"display: none;\">";
    $logicalPageNo = -1;
    for ($i=0; $i<count($this->formDef['pages']); $i++) {
    	if ($this->formDef['pages'][$i]['contingentPage'] == "0") {
		    ++$logicalPageNo;
		    $view.= $this->buildJQMStepFormPage($i, $logicalPageNo);
	    }
    	else {
    		if ($this->formDef['pages'][$i]['contingentValue'] == $jType) {
			    ++$logicalPageNo;
			    $view.= $this->buildJQMStepFormPage($i, $logicalPageNo);
		    }
	    }
    }
    $view.= "</div>";

	  $view.= "<div id=\"finalPageSectionEligible\" style=\"display: none;\">";
	  $view.= explodeParas($this->formDef['finalMsg']);
	  if ($this->isPreStepForm()) {
		  $view.= $this->makeButtonElement($this->formDef['finalButtonLabel'], "processFinalB", "processFinalStatus");
	  }
	  $view.= "</div>";

	  $view.= "<div id=\"finalPageSectionInEligible\" style=\"display: none;\">";
	  $view.= explodeParas($this->formDef['eligibilityQ']['qNonEligibleMsg']);
	  $view.= "</div>";

    $view.= "</div>";
    $view.= "</div>";
    return $view;
  }
  
  private function buildJQMStepFormFooter() {
    $view = "</div>"; // end of page div opened in header
    return $view;
  }
  
  private function buildStepFormView($jType) {
    $view = $this->buildJQMStepFormHeader();
    $view.= $this->buildJQMStepFormControls($jType);
    $view.= $this->buildJQMStepFormFooter();
    return $view;
  }

	// </editor-fold>

  // <editor-fold defaultstate="collapsed" desc=" step form configuration helpers">
  
  private function buildJQMFlipSwitch($id, $class, $onText, $offText, $isOn, $label) {
    $view = "";
	  $preFix = "<div data-role='fieldcontain' id=$id>";
	  if ($label>"") {
		  $preFix.= sprintf("<label for\"%s\">%s</label>", $id, $label);
	  }
	  $postFix = "</div>";
    //$view.= sprintf("<select id=\"%s\" class=\"%s\" data-role=\"slider\" />", $id, $class);
    $view.= sprintf("<select class=\"%s\" data-role=\"slider\" />", $class);
    $view.= sprintf("<option value=\"0\" %s>%s</option>", $isOn==0?"selected":"", $offText );
    $view.= sprintf("<option value=\"1\" %s>%s</option>",$isOn==1?"selected":"", $onText );
    $view.= "</select>";
    return $preFix.$view.$postFix;
  }
  
  private function buildJQMTextArea($label, $id, $class, $value) {
    $view = "";
    $preFix = "";
    $postFix = "";
    if ($label>"") {
      $preFix = "<div data-role=\"fieldcontain\">";
      $preFix.= sprintf("<label for\"%s\">%s</label>", $id, $label);
      $postFix = "</div>";
    }
    $view.= sprintf("<input type=\"text\" id=\"%s\" class=\"%s\" value=\"%s\" />", $id, $class, $value);
    return $preFix.$view.$postFix;
  }
  
  private function buildJQMSelect($label, $id, $class, $options, $selectedValue, $isDisabled = false ) {
    $view = "";
    $preFix = "";
    $postFix = "";
    if ($label>"") {
      $preFix = "<div data-role=\"fieldcontain\">";
      $preFix.= sprintf("<label for\"%s\">%s</label>", $id, $label);
      $postFix = "</div>";
    }
    $class.= ($isDisabled ? " ui-disabled" : "");
    $view.= sprintf("<select name=\"%s\" id=\"%s\" class=\"%s\">", $id, $id, $class);
    for ($i=0; $i<count($options); $i++) {
    	$value = isset($options[$i]['cValue']) ? $options[$i]['cValue'] : $options[$i]['id'];
    	$label = isset($options[$i]['cLabel']) ? $options[$i]['cLabel'] : $options[$i]['label'];
      $view.= sprintf("<option value=\"%s\" %s>%s</option>", $value, $value == $selectedValue?"selected":"", $label);
    }
    $view.= "</select>";
    return $preFix.$view.$postFix;
  }

  private function buildJQMNumeric($label, $id, $class, $max, $currentValue, $isDisabled) {
	  $view = "";
	  $preFix = "";
	  $postFix = "";
	  if ($label>"") {
		  $preFix = "<div data-role=\"fieldcontain\">";
		  $preFix.= sprintf("<label for\"%s\">%s</label>", $id, $label);
		  $postFix = "</div>";
	  }
	  $class.= ($isDisabled ? " ui-disabled" : "");
		$view.=	sprintf("<input type=\"number\" id=\"%s\" name=\"%s\" class=\"%s\" min=\"0\" max=\"%s\" value=\"%s\" />",$id, $id, $class, $max, $currentValue);
	  return $preFix.$view.$postFix;
  }

	private function buildJQMButton($id, $theme, $icon, $label, $isDisabled) {
		return sprintf("<a href=\"#\" id=\"%s\" data-theme=\"%s\" data-icon=\"%s\" data-role=\"button\" %s >%s</a>", $id, $theme, $icon, $isDisabled?"class=\"ui-disabled optionsButton\"":"class=\"optionsButton\"", $label);
	}

  private function buildJQMAddQuestionButton($id, $isDisabled) {
    $view = sprintf("<a href=\"#\" id=\"%s\" data-role=\"button\" %s >add</a>", $id, $isDisabled?"class=\"ui-disabled optionsButton\"":"class=\"optionsButton\"");
    return $view;
  }
  
  private function buildJQMDeleteQuestionButton($id, $isDisabled) {
    $view = sprintf("<a href=\"#\" id=\"%s\" data-role=\"button\" %s >del</a>", $id, $isDisabled?"class=\"ui-disabled optionsButton\"":"class=\"optionsButton\"");
    return $view;
  }

  private function buildJQMAddOptionButton($id, $isDisabled) {
    $view = sprintf("<a href=\"#\" id=\"%s\" data-role=\"button\" %s >add</a>", $id, $isDisabled?"class=\"ui-disabled optionsButton\"":"class=\"optionsButton\"");
    return $view;
  }
  
  private function buildJQMDeleteOptionButton($id, $isDisabled) {
    $view = sprintf("<a href=\"#\" id=\"%s\" data-role=\"button\" %s >del</a>", $id, $isDisabled?"class=\"ui-disabled optionsButton\"":"class=\"optionsButton\"");
    return $view;
  }
  
  private function buildJQMOptionControlBlock($idBase, $optionCount) {
    $view = "<div data-role=\"controlgroup\" data-type=\"horizontal\">";
      $view.= $this->buildJQMAddOptionButton($idBase."_add_".$optionCount, false);
      $view.= $this->buildJQMDeleteOptionButton($idBase."_del_".$optionCount, $optionCount==0?true:false);
    $view.= "</div>";
    return $view;
  }

	private function buildJQMQuestionControlBlock($idBase, $qNo) {
		$view = "<div data-role=\"controlgroup\" data-type=\"horizontal\">";
		$view.= $this->buildJQMAddQuestionButton($idBase."_add_".$qNo, false);
		$view.= $this->buildJQMDeleteQuestionButton($idBase."_del_".$qNo, $qNo==0?true:false);
		$view.= "</div>";
		return $view;
	}

	private function buildJQMQuestionOptionsBlock($label, $pNo, $qNo, $qType, $options, $accordionClosed) {
  	$view = sprintf("<div id=\"%s\" style=\"display: %s \">", 'qoBlock_' . $pNo . '_' . $qNo, $this->needsOptions($qType) ? "block" : "none");
		$accordionId = "qOptionsAccordion_".$pNo."_".$qNo;
		$view.= sprintf("<div data-role=\"collapsible\" id=\"%s\" class=\"accordionControl\" data-collapsed=\"%s\">", $accordionId, $accordionClosed==1?"true":"false");
		$view.= sprintf("<h3>%s</h3>", $label);
		foreach ($options as $i=>$option) {
			$view.= "<div class=\"ui-grid-a\">";

			$view.= "<div class=\"ui-block-a\">";
			$view.= $this->buildJQMTextArea("", "qoTA_".$pNo."_".$qNo."_".$i, "classTA", $option['label']);
			$view.= "</div>";

			$view.= "<div class=\"ui-block-b\">";
			$view.= $this->buildJQMOptionControlBlock("qoB_".$pNo."_".$qNo, $i);
			$view.= "</div>";

			$view.= "</div>";
		}
		$view.= "</div>";
		$view.= "</div>";
		return $view;
	}

	private function buildJQMRadioGridBlock($pNo, $qNo, $q) {
  	$view = '';
//  	$view.= sprintf("<div id=\"%s\" style=\"display: %s \">", 'gridBlock_' . $pNo . '_' . $qNo, $q['qType'] == 9 ? "block" : "none");
//
//		// column headers
//		$view.= "<div class=\"ui-grid-d\">";
//		// header row
//		$view.= "<div class=\"ui-block-a\">column 1</div>";
//		$view.= "<div class=\"ui-block-b\">column 2</div>";
//		$view.= "<div class=\"ui-block-c\">column 3</div>";
//		$view.= "<div class=\"ui-block-d\">column 4</div>";
//		$view.= "<div class=\"ui-block-e\">column 5</div>";
//		// header row titles
//		$view.= "<div class=\"ui-block-a\">".sprintf("<input type=\"text\" id=\"%s\" class=\"%s\" value=\"%s\" />",   "colTitle_" . $pNo . '_'. $qNo . '_0', "", $q['gridColumns'][0]['label'])."</div>";
//		$view.= "<div class=\"ui-block-b\">".sprintf("<input type=\"text\" id=\"%s\" class=\"%s\" value=\"%s\" />", "colTitle_" . $pNo . '_'. $qNo . '_1', "", $q['gridColumns'][1]['label'])."</div>";
//		$view.= "<div class=\"ui-block-c\">".sprintf("<input type=\"text\" id=\"%s\" class=\"%s\" value=\"%s\" />", "colTitle_" . $pNo . '_'. $qNo . '_2', "", $q['gridColumns'][2]['label'])."</div>";
//		$view.= "<div class=\"ui-block-d\">".sprintf("<input type=\"text\" id=\"%s\" class=\"%s\" value=\"%s\" />", "colTitle_" . $pNo . '_'. $qNo . '_3', "", $q['gridColumns'][3]['label'])."</div>";
//		$view.= "<div class=\"ui-block-e\">".sprintf("<input type=\"text\" id=\"%s\" class=\"%s\" value=\"%s\" />", "colTitle_" . $pNo . '_'. $qNo . '_4', "", $q['gridColumns'][4]['label'])."</div>";
//		$view.= "</div>";
//
//		// row questions
//		$view.= "<div class=\"ui-grid-b\">";
//		$view.= "<div class=\"ui-block-a\">row 1 question</div>";
//		$view.= "<div class=\"ui-block-b\">".sprintf("<input type=\"text\" id=\"%s\" class=\"%s\" value=\"%s\" />", "rowQ_" . $pNo . '_'. $qNo. '_0', "", $q['gridRows'][0]['label'])."</div>";
//		$view.= "</div>";
//
//		$view.= "<div class=\"ui-grid-b\">";
//		$view.= "<div class=\"ui-block-a\">row 2 question</div>";
//		$view.= "<div class=\"ui-block-b\">".sprintf("<input type=\"text\" id=\"%s\" class=\"%s\" value=\"%s\" />", "rowQ_" . $pNo . '_'. $qNo. '_1', "", $q['gridRows'][1]['label'])."</div>";
//		$view.= "</div>";
//
//		$view.= "<div class=\"ui-grid-b\">";
//		$view.= "<div class=\"ui-block-a\">row 3 question</div>";
//		$view.= "<div class=\"ui-block-b\">".sprintf("<input type=\"text\" id=\"%s\" class=\"%s\" value=\"%s\" />", "rowQ_" . $pNo . '_'. $qNo. '_3', "", $q['gridRows'][2]['label'])."</div>";
//		$view.= "</div>";
//
//		$view.= "<div class=\"ui-grid-b\">";
//		$view.= "<div class=\"ui-block-a\">row 4 question</div>";
//		$view.= "<div class=\"ui-block-b\">".sprintf("<input type=\"text\" id=\"%s\" class=\"%s\" value=\"%s\" />", "rowQ_" . $pNo . '_'. $qNo. '_4', "", $q['gridRows'][3]['label'])."</div>";
//		$view.= "</div>";
//
//		$view.= "<div class=\"ui-grid-b\">";
//		$view.= "<div class=\"ui-block-a\">row 5 question</div>";
//		$view.= "<div class=\"ui-block-b\">".sprintf("<input type=\"text\" id=\"%s\" class=\"%s\" value=\"%s\" />", "rowQ_" . $pNo . '_'. $qNo. '_5', "", $q['gridRows'][4]['label'])."</div>";
//		$view.= "</div>";
//
//		$view.= "</div>";
  	return $view;
	}

	private function buildRecruitmentSection() {
    $view = sprintf("<div id=\"recruitmentAccordion\" data-role=\"collapsible\" data-collapsed=\"%s\">", $this->formDef['recruitmentAccordionClosed'] == 1? "true":"false");
      $view.= "<h3>Recruitment code options</h3>";
      $view.= $this->buildJQMFlipSwitch("urFS", "classFS", "yes", "no",  $this->formDef['useRecruitmentCode'], "use recruitment code question");
      $view.= $this->buildJQMTextArea("recruitment question", "TA_recQ", "classTA", $this->formDef['recruitmentCodeMessage']);
      $view.= $this->buildJQMTextArea("no code label", "TA_recNo", "classTA", $this->formDef['recruitmentCodeNoLabel']);
      $view.= $this->buildJQMTextArea("has code label", "TA_recYes", "classTA", $this->formDef['recruitmentCodeYesLabel']);
      $view.= $this->buildJQMTextArea("code box label", "TA_recCode", "classTA", $this->formDef['recruitmentCodeLabel']);
    $view.= "</div>";
    return $view;    
  }

	private function buildJQMeqOptionsBlock($label, $idBase, $options, $accordionClosed, $isJSelector) {
		$eligibilityJChoices = [
			['cValue'=>0, 'cLabel'=>$this->eModel->evenS1Label],
			['cValue'=>1, 'cLabel'=>$this->eModel->oddS1Label],
			['cValue'=>2, 'cLabel'=>'not eligible']
		];
		$view = sprintf("<div data-role=\"collapsible\" id=\"%s\" class=\"accordionControl\" data-collapsed=\"%s\">", $idBase."Accordion", $accordionClosed==1?"true":"false");
		$view.= sprintf("<h3>%s</h3>", $label);
		for ($i=0; $i<count($options); $i++) {
			$view.= "<div class=\"ui-grid-b\">";
			$view.= "<div class=\"ui-block-a\">";
			$view.= $this->buildJQMTextArea("", $idBase."_label_".$i, "classTA", $options[$i]['label']);
			$view.= "</div>";
			$view.= "<div class=\"ui-block-b\">";
			$view.= $this->buildJQMSelect("", $idBase."_jType_".$i, "selector", $eligibilityJChoices, $options[$i]['jType'], $isJSelector);
			$view.= "</div>";
			$view.= "<div class=\"ui-block-c\">";
			$view.= $this->buildJQMOptionControlBlock($idBase, $i);
			$view.= "</div>";
			$view.= "</div>";
		}
		$view.= "</div>";
		return $view;
	}

  private function buildEligibilitySection() {
    $view = sprintf("<div id=\"eqAccordion\" data-role=\"collapsible\" data-collapsed=\"%s\">", $this->formDef['eligibilityQ']['qAccordionClosed'] == 1? "true":"false");
      $view.= "<h3>Eligibility question</h3>";
      $view.= $this->buildJQMFlipSwitch("ueFS", "classFS", "yes", "no",  $this->formDef['useEligibilityQ'], "use eligibility question");
      $view.= $this->buildJQMTextArea("eligibility question", "TA_eq", "classTA", $this->formDef['eligibilityQ']['qLabel']);
      $view.= $this->buildJQMSelect("question type", "eqType", "selector", $this->selectControlList, $this->formDef['eligibilityQ']['qType']);
      $view.= $this->buildJQMFlipSwitch("jTypeSelectorFS", "classFS", "Yes", "No", $this->formDef['eligibilityQ']['qUseJTypeSelector'], "use eligibility question as j-type selector");            
      $view.= $this->buildJQMeqOptionsBlock("eligibility question options", "eqOptions", $this->formDef['eligibilityQ']['options'], $this->formDef['eligibilityQ']['qOptionsAccordionClosed'], $this->formDef['eligibilityQ']['qUseJTypeSelector'] ==0 ? true : false);
	  $view.= $this->buildJQMTextArea("ineligibility message", "TA_enem", "classTA", $this->formDef['eligibilityQ']['qNonEligibleMsg']);
    $view.= "</div>";
    return $view;
  }
  
  private function buildStartSection() {
  	$view = '';
  	//$view.= "<div onclick=\"spAccordionClick()\">"; class="accordionControl"
    $view.= sprintf("<div id=\"spAccordion\" data-role=\"collapsible\" data-collapsed=\"%s\">", $this->formDef['introAccordionClosed'] == 1 ? "true":"false");
    $view.= "<h3>Start page</h3>";
    $view.= $this->buildJQMFlipSwitch("useIntroPage", "classFS", "yes", "no",  $this->formDef['useIntroPage'], "use a start page");
    $view.= $this->buildJQMTextArea("start page title", "TA_spt", "classTA", $this->formDef['introPageTitle']);
    $view.= $this->buildJQMTextArea("start page message", "TA_spi", "classTA", $this->formDef['introPageMessage']);
	  $view.= $this->buildJQMTextArea("start page button label", "TA_spb", "classTA", $this->formDef['introPageButtonLabel']);
    $view.= $this->buildRecruitmentSection();
    $view.= $this->buildEligibilitySection();
    $view.= "</div>";
    //$view.= "</div>";
    return $view;
  }

  private function buildJQMQuestion($pNo, $qNo, $q) {
  	$pageHasFilterQuestion = $this->formDef['pages'][$pNo]['useFilter'] == 1 ? true : false;
    $view = sprintf("<div class=\"accordionControl\" id=\"qAccordion_%s_%s\" data-role=\"collapsible\" data-collapsed=\"%s\">", $pNo, $qNo, $q['qAccordionClosed'] == 1 ? "true":"false");
    $view.= sprintf("<h3>Question %s</h3>", $qNo);

	  $view.= $this->buildJQMQuestionControlBlock("qB_".$pNo, $qNo);
	  $view.= $this->buildJQMFlipSwitch("qMandatory_".$pNo."_".$qNo, "classFS", "yes", "no",  $q['qMandatory'], "mandatory?");
	  $view.= $this->buildJQMTextArea("question text", "qTA_".$pNo."_".$qNo, "classTA", $q['qLabel']);

    $view.= "<div class=\"ui-grid-b\">";
    //row 1
	  $view.= "<div class=\"ui-block-a\">";
	  $view.= "control type";
	  $view.= "</div>";
	  if (!$pageHasFilterQuestion || $qNo > 0) {
		  $view.= "<div class=\"ui-block-b\">";
		  $view.= "max value";
		  $view.= "</div>";
		  $view.= "<div class=\"ui-block-c\">";
		  $view.= "filter response";
		  $view.= "</div>";
	  }
	  //row 2
	  $view.= "<div class=\"ui-block-a\">";
	  $view.= $this->buildJQMSelect("", "qType_".$pNo."_".$qNo, "selector", $this->allControlList, $q['qType']);
	  $view.= "</div>";
	  if (!$pageHasFilterQuestion || $qNo > 0) {
		  $view.= "<div class=\"ui-block-b\">";
		  $view.= $this->buildJQMNumeric("", "qMax_".$pNo."_".$qNo, "", 100, $q['qContinuousSliderMax'], false);
		  $view.= "</div>";
		  $contingentOptions =[];
		  array_push($contingentOptions, ["cValue" => -1, "cLabel" => 'not yet selected']);
		  foreach ($this->formDef['pages'][$pNo]['questions'][0]['options'] as $filterOption) {
			  $selectOption = ["cValue" => $filterOption['id'], 'cLabel'=> $filterOption['label']];
			  array_push($contingentOptions, $selectOption);
		  }
		  $view.= "<div class=\"ui-block-c\">";
		  $view.= $this->buildJQMSelect("", "filterR_".$pNo."_".$qNo, "selector", $contingentOptions, $q['qContingentValue']);
		  $view.= "</div>";
	  }
	  // row 3
	  $view.= "<div class=\"ui-block-a\">";
	  $view.= '&nbsp;';
	  $view.= "</div>";
	  if (!$pageHasFilterQuestion || $qNo>0) {
		  $view.= "<div class=\"ui-block-b\">";
		  $view.= '(numeric/sliders only)';
		  $view.= "</div>";
		  $view.= "<div class=\"ui-block-c\">";
		  $view.= '(if filter page)';
		  $view.= "</div>";
	  }

	  $view.= "</div>";
    // always build an options block, even if the qType isn't a selector - hide/show in JS
	  $view.= $this->buildJQMQuestionOptionsBlock("options", $pNo, $qNo, $q['qType'], $q['options'], $q['optionsAccordionClosed']);
	  // always build a grid control block, but only hide/show as used in JS
	  $view.= $this->buildJQMRadioGridBlock($pNo, $qNo, $q);

	  $view.= "</div>";
    return $view;

  }
  
  private function buildPageSection($pageNo) {
    $page = $this->formDef['pages'][$pageNo];
    //echo print_r($this->formDef, true);
    $view = sprintf("<div id=\"pageAccordion_%s\" data-role=\"collapsible\" data-collapsed=\"%s\">", $pageNo, $page['pageAccordionClosed'] == 1 ? "true":"false");
    $view.= sprintf("<h3>Page %s</h3>", $pageNo);
    if ($pageNo > 0) {
      $view.= $this->buildJQMButton("del_page_".$pageNo, 'b','delete',  "delete this page", false);
    }
	  $view.= $this->buildJQMFlipSwitch("ignorePage_".$pageNo, "classFS", "yes", "no",  $page['ignorePage'] , "ignore page");
	  $view.= $this->buildJQMFlipSwitch("isContingentPage_".$pageNo, "classFS", "yes", "no",  $page['contingentPage'], "is contingent page?");

	  $contingentVisible = $page['contingentPage'] == 1 ? "block" : "none";
	  $contingentID = 'contingent_' . $pageNo;
	  $view.= sprintf("<div id=\"%s\" style = \"display: %s\">", $contingentID, $contingentVisible);
	  $view.= $this->buildJQMSelect("contingency match", "contingencyMatch_".$pageNo, "selector", $this->formDef['judgeTypeOptions'], $page['contingentValue']);
	  $view.= "</div>";

	  $eligibilityVisible = $this->formDef['useEligibilityQ'] == 1 ? "block" : "none";
	  $eligibilityID = 'eligibility_' . $pageNo;
	  $view.= sprintf("<div id=\"%s\" style = \"display: %s\">", $eligibilityID, $eligibilityVisible);
	  $view.= $this->buildJQMSelect("eligibility match", "eqMatch_".$pageNo, "selector", $this->formDef['eligibilityQ']['options'], $page['contingentValue']);
	  $view.= "</div>";

	  $view.= $this->buildJQMTextArea("page title", "pageTitleTA_".$pageNo, "classTA", $page['pageTitle']);
    $view.= $this->buildJQMTextArea("page instruction", "pageInstTA_".$pageNo, "classTA", $page['pageInst']);
    $view.= $this->buildJQMFlipSwitch("q0Filter_".$pageNo, "classFS", "yes", "no",  $page['useFilter'], "use a filter question");
//	  $view.= $this->buildJQMFilterResponseQuestion($pageNo);
	  for ($i=0; $i<count($page['questions']); $i++) {
		  $view.= $this->buildJQMQuestion($pageNo, $i, $page['questions'][$i]);
	  }
    $view.= "</div>";    
    return $view;
  }
  
  private function buildEndSection() {
    $view = sprintf("<div id=\"epAccordion\" data-role=\"collapsible\" data-collapsed=\"%s\">", $this->formDef['finalAccordionClosed'] == 1 ? "true":"false");
    $view.= "<h3>Final page</h3>";
	  $view.= $this->buildJQMFlipSwitch("useFinalPage", "classFS", "yes", "no",  $this->formDef['useFinalPage'], "use a final page");
	  $view.= $this->buildJQMTextArea("final message", "TA_fm", "classTA", $this->formDef['finalMsg']);
	  $view.= $this->buildJQMTextArea("final button label", "TA_fb", "classTA", $this->formDef['finalButtonLabel']);
    $view.= "</div>";
    return $view;
  }

	private function buildJQMStepFormConfigurationHeader() {
		$view = "<div data-role=\"page\" data-theme=\"a\">";
		$view.= "<div data-role=\"header\" data-position=\"inline\">";
		$view.= "<h1>".$this->exptId." - ".$this->formName."</h1>";
		$view.= "</div>";
		return $view;
	}

	private function buildJQMStepFormConfigurationControls() {
  	$view = '';
	  $view.= "<div data-role=\"content\" data-theme=\"a\">";
	  $view.= "<div id='container'>";
		$view.= "<p>Any action that alters the form structure (adding a page, adding a question etc) will save and reload the form. Better to make any text changes on existing pages first and then add new structure as required.</p>";
		$view.= "<p>Clicking the 'save and reload' button in the footer will also do this - this is useful to save the open/closed status of the collapsibles in the page. </p>";
	  $view.= $this->buildJQMFlipSwitch("dcFS", "classFS", "yes", "no", $this->formDef['definitionComplete'], "definition complete?");
		$view.= $this->buildJQMTextArea("form title", "TA_ft", "classTA", $this->formDef['formTitle']);
		$view.= $this->buildJQMTextArea("form instruction", "TA_fi", "classTA", $this->formDef['formInst']);
	  $view.= $this->buildStartSection();

		$view.= sprintf("<div id=\"pagesAccordion\" data-role=\"collapsible\" data-collapsed=\"%s\">",$this->formDef['pagesAccordionClosed']==1 ? "true" : "false");
		$view.= "<h3>Pages (not necessary if an eligibility form)</h3>";
		$view.= "<div>";
		$view.= "<p>Note: page numbers are only necessary for this configuration section, the actual virtual page number used when the form is run is decided by the internal logic. If no pages are required then switch off any pages in the definition.</p>";
		for ($i=0; $i<count($this->formDef['pages']); $i++) {
      $view.= $this->buildPageSection($i);
    }
		$view.= "<div data-role=\"controlgroup\" data-type=\"horizontal\">";
		$view.= $this->buildJQMButton('addPage', 'b','check','add new page',false);
		$view.= "</div>";

		$view.= "</div>";
    $view.= "</div>";

    $view.= $this->buildEndSection();

	  $view.= "</div>";
	  $view.= "</div>";
	  return $view;
  }
  
  private function buildJQMStepFormConfigurationFooter() {
    $view = "<div data-role=\"footer\" data-position=\"inline\" data-tap-toggle=\"false\">";
      $view.= "<div data-role=\"navbar\">";
        $view.= "<ul>";
	  $view.= "<li><a id=\"backB\" href=\"#\" data-icon=\"carat-l\">back</a></li>";
	  $view.= "<li><a id=\"saveB\" href=\"#\" data-icon=\"refresh\">save form and reload</a></li>";
        $view.= "</ul>";
      $view.= "</div>";
    $view.= "</div>";
    $view.= "</div>"; // end of page div opened in header
    return $view;
  }
  
  private function buildStepFormConfigurationView() {
  	$view = '';
	  $view.= $this->buildJQMStepFormConfigurationHeader();
	  $view.= $this->buildJQMStepFormConfigurationControls();
	  $view.= $this->buildJQMStepFormConfigurationFooter();
    return $view;
  }

	private function buildJQMStepFormCloneHeader() {
		$view = "<div data-role=\"header\" data-position=\"inline\">";
		$view.= "<h1>Cloning ".$this->formName." from expt #" . $this->exptId . "</h1>";
		$view.= "</div>";
		return $view;
	}

	private function buildJQMStepFormCloneControls() {
		global $igrtSqli;
		$view = '<div id="statusMsg">Cloned data saved</div>';
		$view.= "<div data-role=\"content\" data-theme=\"a\">";
		$view.= "<div id='container'>";
		$exptDefs = [];
		$formTypes = $this->getFormTypes();
		$getExperimentsQry = "SELECT * FROM igExperiments ORDER BY exptId DESC";
		$exptResult = $igrtSqli->query($getExperimentsQry);
		while ($exptRow = $exptResult->fetch_object()) {
			$exptId = $exptRow->exptId;
			$exptTitle = $exptRow->title;
			$cloneFormsAccordionOpen = $exptRow->cloneFormsAccordionOpen;
			$formsList = [];
			for ($i=0; $i<count($formTypes); $i++) {
				$tempValue = array('partPopulated' => 0, 'definitionComplete'=>0, 'formName'=>$formTypes[$i]->formName, 'formType'=>$formTypes[$i]->formType);
				$getStatusQry = sprintf("SELECT * FROM fdStepForms WHERE exptId='%s' AND formType='%s'", $exptId, $i);
				$getStatusResult = $igrtSqli->query($getStatusQry);
				if ($getStatusResult->num_rows > 0) {
					$tempValue['partPopulated'] = 1;
					$getStatusRow = $getStatusResult->fetch_object();
					$tempValue['definitionComplete'] = $getStatusRow->definitionComplete;
				}
				array_push($formsList, $tempValue);
			}
			$tempExpt = array(
				'exptId'=>$exptId,
				'exptTitle'=>$exptTitle,
				'cloneFormsAccordionOpen'=>$cloneFormsAccordionOpen,
				'formsList'=>$formsList);
			array_push($exptDefs, $tempExpt);
		}
		foreach ($exptDefs as $exptDef) {
			$view.= '<div data-role="collapsible" data-collapsed="true">';
			$view.= '<h3>Experiment #'.$exptDef['exptId'].'-'.$exptDef['exptTitle']. '</h3>';
			$view.= '<div>';
			foreach($exptDef['formsList'] as $formDef) {
				$view.= $this->buildJQMFlipSwitch('fs_'.$exptDef['exptId'].'_'.$formDef['formType'], 'clone_fs', 'yes', 'no', 0, 'clone to '.$formDef['formName'].'?');
			}
			$view.= '</div>';
			$view.= '</div>';
		}
		$view.= '</div>';
		$view.= '</div>';
		return $view;
	}

	private function buildStepFormCloneView() {
	  $view = '';
	  $view.= $this->buildJQMStepFormCloneHeader();
	  $view.= $this->buildJQMStepFormCloneControls();
	  return $view;
  }

	private function getFormTypes() {
		global $igrtSqli;
		$getTypeSql = "SELECT * FROM fdStepFormsNames ORDER BY formType ASC";
		$getTypeResult = $igrtSqli->query($getTypeSql);
		$retArray = [];
		while ($getTypeRow = $getTypeResult->fetch_object()) {
			array_push($retArray, $getTypeRow);
		}
		return $retArray;
	}

	private function getFormName($id) {
		$formDefs = $this->getFormTypes();
		foreach ($formDefs as $formDef) {
			if ($formDef->formType == $id ) { return $formDef->formName; }
		}
	}

  // </editor-fold>
  
  // <editor-fold defaultstate="collapsed" desc=" step2-specific runtime helpers">

	private function buildJQMInvertedStep2Header() {
		$view = "<div data-role=\"page\" data-theme=\"e\">";
		$view.= "<div data-role=\"header\" data-position=\"inline\">";
		$view.= "<h1>Imgame NP step2</h1>";
		$view.= "</div>";
		return $view;
	}

	private function buildJQMInvertedStep2FormControls() {
		$view = "<div data-role=\"content\">";
		$view.= "<div id=\"container\" style=\"display: none;\">";
		$view.= "<div id=\"finalMsg\" style=\"display: none;\"><h2>" . $this->eModel->step2_invertedFinalMsg ."</h2></div>";
		$view.= "<div id=\"closedMsg\" style=\"display: none;\"><h2>" . $this->eModel->step2_invertedClosedMsg ."</h2></div>";
		$view.= "<div id=\"qaSection\">";
		$view.= "<h2 id=\"qText\">question text injected here</h2>";
		$view.= "<textarea name=\"answerTA\" id=\"answerTA\" value=\"\"></textarea>";
		$view.= $this->makeButtonElement($this->eModel->step2_invertedSendBLabel, "processAnswer", "");
		if ($this->eModel->useIS2CharacterLimit) {
			$view.= "<h4>";
			$view.= $this->eModel->istep2_ReplyLimitGuidance > '' ? $this->eModel->istep2_ReplyLimitGuidance : "reply length guidance not set.";
			$view.= "</h4>";
		}
		$view.= "</div>";
		if ($this->eModel->useIS2NPAlignment == 1) {
			$view.= "<div id=\"alignmentSection\">";
			$view.= "<div id=\"ynSection\">";
			$view.= "<fieldset data-role=\"controlgroup\" data-type=\"horizontal\">";
			$view.= "<legend>". $this->eModel->iS2NPAlignmentLabel. "</legend>";
			$view.= $this->makeRadioElement("alignedRB", "iS2ayB", $this->eModel->iS2NPYesLabel, "");
			$view.= $this->makeRadioElement("alignedRB", "iS2anB", $this->eModel->iS2NPNoLabel, "");
			$view.= "</fieldset>";
			$view.= $this->makeButtonElement($this->eModel->iS2ContinueLabel, "alignmentProceed", "");
			$view.= "</div>";
			$view.= "<div id=\"correctedAnswerSection\">";
			$view.= "<h2 id=\"caText\">" . $this->eModel->iS2CorrectedAnswerLabel . "</h2>";
			$view.= "<textarea name=\"canswerTA\" id=\"canswerTA\" value=\"\"></textarea>";
			$view.= $this->makeButtonElement($this->eModel->step2_invertedSendBLabel, "cprocessAnswer", "");
			$view.= "<h4>". $this->eModel->istep2_ReplyLimitGuidance."</h4>";
			$view.= "</div>";
			$view.= "</div>";
		}
		$view.= "</div>";
		$view.= "</div>";
		return $view;
	}

	private function buildJQMInvertedStep2Footer() {
		$view = "</div>"; // end of page div opened in header
		return $view;
	}

	private function buildInvertedStep2View() {
		$view = $this->buildJQMInvertedStep2Header();
		$view.= $this->buildJQMInvertedStep2FormControls();
		$view.= $this->buildJQMInvertedStep2Footer();
		return $view;
	}

	private function buildJQMStep2Header() {
		$view = "<div data-role=\"page\" data-theme=\"a\">";
		$view.= "<div data-role=\"header\" data-position=\"inline\">";
		$view.= "<h1>Imgame P step2</h1>";
		$view.= "</div>";
		return $view;
	}

	private function buildJQMStep2FormControls() {
		$view = "<div data-role=\"content\">";
		$view.= "<div id=\"container\" style=\"display: none;\">";
		$view.= "<div id=\"finalMsg\" style=\"display: none;\"><h2>" . $this->eModel->step2_finalMsg ."</h2></div>";
		$view.= "<div id=\"closedMsg\" style=\"display: none;\"><h2>" . $this->eModel->step2_closedMsg ."</h2></div>";
		$view.= "<div id=\"qaSection\" class = \"s2Section\">";
		$view.= "<h2 id=\"qText\">question text injected here</h2>";
		$view.= "<textarea name=\"answerTA\" id=\"answerTA\" value=\"\"></textarea>";
		$view.= $this->makeButtonElement($this->eModel->step2_sendBLabel, "processAnswer", "");
		$view.= "<h4>". $this->eModel->step2_ReplyLimitGuidance."</h4>";
		$view.= "</div>";
		if ($this->eModel->useS2PAlignment == 1) {
			$view.= "<div id=\"alignmentSection\">";
			$view.= "<div id=\"ynSection\">";
			$view.= "<fieldset data-role=\"controlgroup\" data-type=\"horizontal\">";
			$view.= "<legend>". $this->eModel->s2PAlignmentLabel. "</legend>";
			$view.= $this->makeRadioElement("alignedRB", "s2ayB", $this->eModel->s2PYesLabel, "");
			$view.= $this->makeRadioElement("alignedRB", "s2anB", $this->eModel->s2PNoLabel, "");
			$view.= "</fieldset>";
			$view.= $this->makeButtonElement($this->eModel->s2ContinueLabel, "alignmentProceed", "");
			$view.= "</div>";
			$view.= "<div id=\"correctedAnswerSection\">";
			$view.= "<h2 id=\"caText\">" . $this->eModel->s2CorrectedAnswerLabel . "</h2>";
			$view.= "<textarea name=\"canswerTA\" id=\"canswerTA\" value=\"\"></textarea>";
			$view.= $this->makeButtonElement($this->eModel->step2_sendBLabel, "cprocessAnswer", "");
			$view.= "<h4>". $this->eModel->step2_ReplyLimitGuidance."</h4>";
			$view.= "</div>";
			$view.= "</div>";
		}
		$view.= "</div>";
		$view.= "</div>";
		return $view;
	}

	private function buildJQMStep2Footer() {
		$view = "</div>"; // end of page div opened in header
		return $view;
	}

  private function buildStep2View() {
    $view = $this->buildJQMStep2Header();
    $view.= $this->buildJQMStep2FormControls();
    $view.= $this->buildJQMStep2Footer();
    return $view;
  }
  
  // </editor-fold>

	// <editor-fold defaultstate="collapsed" desc=" pre-step1 selector runtime">

	private function buildJQMPreStep1Header() {
		$view = "<div data-role=\"page\" data-theme=\"a\">";
		$view.= "<div data-role=\"header\" data-position=\"inline\">";
		$view.= "<h1>Imgame step1 selector</h1>";
		$view.= "</div>";
		return $view;
	}

	private function buildJQMPreStep1FormControls() {
		$view = "<div data-role=\"content\">";
		$view.= "<div id=\"container\" style=\"display: none;\">";
		$view.= "<div id=\"introMsg\"><h2>Experiment selector</h2><p>Please enter the details below and click 'send'. The experiment does not use or store your email address but simply uses it to ensure that everyone in the experiment is unique.</p></div>";
		$view.= "<div id=\"qaSection\" class = \"s2Section\">";
		$view.= "<fieldset data-role=\"controlgroup\" data-type=\"horizontal\">";
		$view.= "<legend>Please enter your email </legend>";
		$view.= "<textarea name=\"emailTA\" id=\"emailTA\" value=\"\"></textarea>";
		$view.= "<fieldset data-role=\"controlgroup\" data-type=\"horizontal\">";
		$view.= "<legend>Please select your category</legend>";
		$view.= $this->makeRadioElement("typeRB", "s1OddB", $this->eModel->evenS1Label, "");
		$view.= $this->makeRadioElement("typeRB", "s1EvenB", $this->eModel->oddS1Label, "");
		$view.= "</fieldset>";
		$view.= $this->makeButtonElement("send", "processLogin", "");
		$view.= "</div>";
		$view.= "</div>";
		$view.= "</div>";
		return $view;
	}

	private function buildJQMPreStep1Footer() {
		$view = "</div>"; // end of page div opened in header
		return $view;
	}


	private function buildPreStep1Selector() {
		$view = $this->buildJQMPreStep1Header();
		$view.= $this->buildJQMPreStep1FormControls();
		$view.= $this->buildJQMPreStep1Footer();
		return $view;
	}



	// </editor-fold>
  
  // <editor-fold defaultstate="collapsed" desc=" public interfaces">

	public function makePreStep1Selector($exptId) {
		$this->eModel = new experimentModel($exptId);
		return $this->buildPreStep1Selector();
	}
  
  public function makeStep2View($exptId, $jType) {
    $this->eModel = new experimentModel($exptId);
    return $this->buildStep2View();
  }

	public function makeInvertedStep2View($exptId, $jType) {
		$this->eModel = new experimentModel($exptId);
		return $this->buildInvertedStep2View();
	}

	public function makeStepFormView($formType, $exptId, $jType) {
    $stepFormsHandler = new stepFormsHandler(null, $exptId, $formType);
    $this->formType = $formType;
    $this->exptId = $exptId;
    $this->jType = $jType;
    $this->formDef = $stepFormsHandler->getForm();
    return $this->buildStepFormView($jType);
  }
  
  public function makeStepFormConfigurationView($exptId, $formType) {
    $this->formName = $this->getFormName($formType);
    $stepFormsHandler = new stepFormsHandler(null, $exptId, $formType);
    $this->formType = $formType;
    $this->exptId = $exptId;
    $this->jType = -1;
    $this->formDef = $stepFormsHandler->getForm();
    $this->formActiveControl = $this->formDef['currentFocusControlId'];
    $this->eModel = new experimentModel($this->exptId);
    return $this->buildStepFormConfigurationView();
  }

  public function makeStepFormCloneView($exptId, $formType) {
	  $this->formName = $this->getFormName($formType);
	  $stepFormsHandler = new stepFormsHandler(null, $exptId, $formType);
	  $this->formType = $formType;
	  $this->exptId = $exptId;
	  $this->jType = -1;
	  return $this->buildStepFormCloneView();
  }
  
  public function __construct() {
    $this->selectControlList = $this->getSelectControlList();
    $this->allControlList = $this->getAllControlList();    
  }
  
  // </editor-fold>

}
