<?php
/**
 * Step forms controller
 * runtime controller class for step forms
 * @author MartinHall
 */
ini_set('display_errors', 'On');
error_reporting(E_ALL);
if (!isset($root_path)) { $root_path = $_SERVER['DOCUMENT_ROOT']; }
include_once $root_path.'/helpers/models/class.experimentModel.php';
include_once $root_path.'/domainSpecific/mySqlObject.php';
require_once($root_path.'/helpers/forms/class.stepFormsHandler.php');

class stepFormRuntimeController {
  private $formName;
  private $jType;
  private $exptId;
  private $formType;
  public $debug;
  public $formDef;  // public during debug
  
// <editor-fold defaultstate="collapsed" desc=" interface functions">

  function getStepFormRuntimeSettings() {
    $stepFormsHandler = new stepFormsHandler(null, $this->exptId, $this->formType);
    $stepFormsHandler->setJType($this->jType);
    $this->formDef = $stepFormsHandler->getForm();
    $xml = sprintf("<message>"
        . "<messageType>initForm</messageType>"
        . "<useJTypeSelector>%s</useJTypeSelector>"
        . "<isEligible0>%s</isEligible0>"
        . "<isEligible1>%s</isEligible1>"
        . "<jType0>%s</jType0>"
        . "<jType1>%s</jType1>"
        . "<cntActivePagesOdd>%s</cntActivePagesOdd>"
        . "<cntActivePagesEven>%s</cntActivePagesEven>"
        . "<useEligibility>%s</useEligibility>"
        . "<useRecruitment>%s</useRecruitment>"
        . "<allowNullRecruitmentCode>%s</allowNullRecruitmentCode>"
        . "</message>",
        $this->formDef['eligibilityQ']['qUseJTypeSelector'],
        $this->formDef['eligibilityQ']['options'][0]['isEligibleResponse'],
        isset($this->formDef['eligibilityQ']['options'][1]['isEligibleResponse']) ? $this->formDef['eligibilityQ']['options'][1]['isEligibleResponse'] : 0,
        $this->formDef['eligibilityQ']['options'][0]['jType'],
        isset($this->formDef['eligibilityQ']['options'][1]['jType']) ? $this->formDef['eligibilityQ']['options'][1]['jType'] : 0,
        $this->formDef['cntActivePagesEven'],
        $this->formDef['cntActivePagesOdd'],
        $this->formDef['useEligibilityQ'],
        $this->formDef['useRecruitmentCode'],
        $this->formDef['allowNullRecruitmentCode']        
        );
    return $xml;
  }
  
// </editor-fold>
  
// <editor-fold defaultstate="collapsed" desc=" helpers">

  function getFormName() {
    global $igrtSqli;
    $qry = sprintf("SELECT * FROM fdStepFormsNames WHERE formType='%s'", $this->formType);
    $result = $igrtSqli->query($qry);
    if ($result) {
      $row = $result->fetch_object();
      return $row->formName;
    }
    return 'not set';
  }

// </editor-fold>  

// <editor-fold defaultstate="collapsed" desc=" constructor">

  function __construct($exptId, $formType, $jType) {
    $this->exptId = $exptId;
    $this->formType = $formType;
    $this->jType = $jType;
    $this->formName = $this->getFormName(); // might not be necessary
  }
  
// </editor-fold>

}

