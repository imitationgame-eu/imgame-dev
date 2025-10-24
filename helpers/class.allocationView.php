<?php
/**
 * Allocation view - 
 * top-level controller to allocate respondents to slots
 * @author MartinHall
 */
if (!isset($root_path)) { $root_path = $_SERVER['DOCUMENT_ROOT']; }
include_once $root_path.'/helpers/html/class.htmlBuilder.php';
include_once $root_path.'/helpers/forms/class.formBuilder.php';


class allocationViewModel {
  private $htmlBuilder;   // control builder
  private $formBuilder;   // form builder
  private $igrtSqli;
  private $uid;

  private $_tabIndex = 1;
  
  
  
  //--------------------------------------------------------------------------
  // allocation view connect returns current state view 
  //--------------------------------------------------------------------------
  
  function getExperimentListHtml($stepId) {
    return $this->formBuilder->getExptListView($stepId);
  }
        
  function getInitialViewHtml($stepId, $exptId) {
    return $this->formBuilder->getAllocationView($stepId, $exptId);    
 }
 
  function allocateReg($stepId, $exptId, $uid, $dayNo = NULL, $sessionNo = NULL) {
    switch ($stepId) {
      case 1 : { 
        $updateQry = sprintf("UPDATE sysallocs_Step1RespList SET isAllocated='1', dayNo='%s', sessionNo='%s' WHERE uid='%s' AND exptId='%s'", $dayNo, $sessionNo, $uid, $exptId);
        $this->igrtSqli->query($updateQry);
        $updateQry = sprintf("INSERT INTO igActiveStep1Users (uid, exptId, day, session, jType) VALUES('%s','%s','%s','%s','na')", $uid, $exptId, $dayNo, $sessionNo);
        $this->igrtSqli->query($updateQry);
        break; 
      }
      case 2 : { 
        $updateQry = sprintf("UPDATE sysallocs_Step2RespondentList SET isAllocated='1' WHERE uid='%s' AND exptId='%s' ", $uid, $exptId);
        $this->igrtSqli->query($updateQry);
        $updateQry = sprintf("INSERT INTO igActiveStep2Users (uid, exptId) VALUES('%s','%s')", $uid, $exptId);
        $this->igrtSqli->query($updateQry);
        break; 
      }
      case 4 : { 
        $updateQry = sprintf("UPDATE sysallocs_Step4RespondentList SET isAllocated='1' WHERE uid='%s' AND exptId='%s'", $uid, $exptId);
        $this->igrtSqli->query($updateQry);
        $updateQry = sprintf("INSERT INTO igActiveStep4Users (uid, exptId, jType) VALUES('%s','%s','na')", $uid, $exptId);
        $this->igrtSqli->query($updateQry);
       break; 
      }
    }
  }

  function deallocateReg($stepId, $exptId, $uid) {
    switch ($stepId) {
      case 1 : { 
        $updateQry = sprintf("UPDATE sysallocs_Step1RespList SET isAllocated='0', dayNo='-1', sessionNo='-1', jType='na' WHERE uid='%s' AND exptId='%s'", $uid, $exptId);
        $this->igrtSqli->query($updateQry);
        $updateQry = sprintf("DELETE FROM igActiveStep1Users WHERE uid='%s' AND exptId='%s'", $uid, $exptId);
        $this->igrtSqli->query($updateQry);
        break; 
      }
      case 2 : { 
        $updateQry = sprintf("UPDATE sysallocs_Step2RespondentList SET isAllocated='0' WHERE uid='%s' AND exptId='%s' ", $uid, $exptId);
        $this->igrtSqli->query($updateQry);
        $updateQry = sprintf("DELETE FROM igActiveStep2Users WHERE uid='%s' AND exptId='%s'", $uid, $exptId);
        $this->igrtSqli->query($updateQry);
        $updateQry = sprintf("DELETE FROM dataSTEP2 WHERE uid='%s' AND exptId='%s'", $uid, $exptId);
        $this->igrtSqli->query($updateQry);
        break; 
      }
      case 4 : { 
        $updateQry = sprintf("UPDATE sysallocs_Step4RespondentList SET isAllocated='0' WHERE uid='%s' AND exptId='%s'", $uid, $exptId);
        $this->igrtSqli->query($updateQry);
        $updateQry = sprintf("DELETE FROM igActiveStep4Users WHERE uid='%s' AND exptId='%s'", $uid, $exptId);
        $this->igrtSqli->query($updateQry);
        break; 
      }
    }    
  }
  
  function toggleJ($stepId, $exptId, $uid, $jType=NULL, $jNo=NULL, $dayNo=NULL, $sessionNo=NULL) {
    switch ($stepId) {
      case 1 : { 
        $jQry = sprintf("SELECT * FROM sysallocs_Step1RespList WHERE uid='%s'", $uid);
        $jResult = $this->igrtSqli->query($jQry);
        $jRow = $jResult->fetch_object();
        $jType = ($jRow->jType == "X") ? "Y" : "X";
        $updateQry = sprintf("UPDATE sysallocs_Step1RespList SET jType='%s' WHERE uid='%s' AND exptId='%s'", $jType, $uid, $exptId);
        $this->igrtSqli->query($updateQry);
        $updateQry = sprintf("UPDATE igActiveStep1Users SET jType='%s' WHERE uid='%s' AND exptId='%s'", $jType, $uid, $exptId);
        $this->igrtSqli->query($updateQry);
        break; 
      }
      case 2 : { 
        $jQry = sprintf("SELECT * FROM sysallocs_Step2JudgeMappings WHERE uid='%s' AND exptId='%s' AND jType='%s' AND jNo='%s' AND dayNo='%s' AND sessionNo='%s'", $uid, $exptId, $jType, $jNo, $dayNo, $sessionNo);
        $jResult = $this->igrtSqli->query($jQry);
        if ($this->igrtSqli->affected_rows == 0) {
          // doesn't exist so create
          $insertQry = sprintf("INSERT INTO sysallocs_Step2JudgeMappings (uid, jType, jNo, exptId, dayNo, sessionNo) VALUES('%s', '%s', '%s', '%s', '%s', '%s')", $uid, $jType, $jNo, $exptId, $dayNo, $sessionNo );
          $this->igrtSqli->query($insertQry);
          // add the questions for this combo into dataSTEP2 and update total # of questions in igActiveStep2Users
          $numJType = ($jType=='x')? 1 : 0;
          $qQry = sprintf("SELECT * FROM md_dataStep1reviewed WHERE exptId='%s' AND jType='%s' AND jNo='%s' AND dayNo='%s' AND sessionNo='%s' AND canUse='1'", $exptId, $numJType, $jNo, $dayNo, $sessionNo);
          $qResult = $this->igrtSqli->query($qQry);
          //return $qQry;
          if ($this->igrtSqli->affected_rows > 0) {
            $qCnt = 0;
            while ($dataRow = $qResult->fetch_object()) {
              ++$qCnt;
              $insertQry = sprintf("INSERT INTO dataSTEP2 (uid, exptId, dayNo, sessionNo, jType, jNo, qNo, question) VALUES('%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s')",
                      $uid, $exptId, $dayNo, $sessionNo, $numJType, $jNo, $qCnt, $dataRow->q);
              $this->igrtSqli->query($insertQry);
            }
            // update overall QNo in igActiveStep2Users
            $cqCntQry = sprintf("SELECT * FROM igActiveStep2Users WHERE uid='%s'", $uid);
            $newValue = $qCnt;
            $existingValue = 0;
            $cqCntResult = $this->igrtSqli->query($cqCntQry);
            if ($this->igrtSqli->affected_rows > 0) {
              $cqCntRow = $cqCntResult->fetch_object();
              $existingValue = $cqCntRow->totalQ;
            }
            $newValue += $existingValue;
            $updateCntQry = sprintf("UPDATE igActiveStep2Users SET totalQ='%s' WHERE uid='%s'", $newValue, $uid);
            $this->igrtSqli->query($updateCntQry);
          }
        }
        else {
          $delQry = sprintf("DELETE FROM sysallocs_Step2JudgeMappings WHERE uid='%s' AND jType='%s' AND jNo='%s' AND exptId='%s' AND dayNo='%s' AND sessionNo='%s'", $uid, $jType, $jNo, $exptId, $dayNo, $sessionNo );
          $this->igrtSqli->query($delQry);
          $numJType = ($jType=='x')? 1 : 0;
          $delQry = sprintf("DELETE FROM dataSTEP2 WHERE uid='%s' AND jType='%s' AND jNo='%s' AND exptId='%s' AND dayNo='%s' AND sessionNo='%s'", $uid, $numJType, $jNo, $exptId, $dayNo, $sessionNo );
          $this->igrtSqli->query($delQry);
        }
        //return $qCnt.'__'.$qQry.$insertQry;
        break; 
      }
      case 4 : { 
        $jQry = sprintf("SELECT * FROM sysallocs_Step4RespondentList WHERE uid='%s'", $uid);
        $jResult = $this->igrtSqli->query($jQry);
        $jRow = $jResult->fetch_object();
        $jType = ($jRow->jType == "X") ? "Y" : "X";
        $updateQry = sprintf("UPDATE sysallocs_Step4RespondentList SET jType='%s' WHERE uid='%s' AND exptId='%s'", $jType, $uid, $exptId);
        $this->igrtSqli->query($updateQry);
        $updateQry = sprintf("UPDATE igActiveStep4Users SET jType='%s' WHERE uid='%s' AND exptId='%s'", $jType, $uid, $exptId);
        $this->igrtSqli->query($updateQry);
       break; 
      }
    }
    //return $updateQry;
  }
     
                  
  //--------------------------------------------------------------------------
  // html builders
  //--------------------------------------------------------------------------
   
  // makeFormSingle($id, $label, $class, $tabIndex=null, $content=null, $validation=null)  
  // makeFormEmail($id, $placeholder, $label, $tabIndex=null, $content=null, $validation=null)
  // makeFormDate($id, $label, $class, $tabIndex=null, $content=null, $validation=null)
  // makeFormRadio($id, $name, $option, $tabIndex=null, $content=null)
  // makeSelect($id, $label, $class, $enabled, $optionList, $tabIndex=null, $content=null, $validation=null)
  // makeCheckBox($id, $checked, $class, $type, $name, $value, $label, $enabled, $tabIndex=null, $content=null, $validation=null)
  // makeButton($id, $text, $class, $label=null, $tabIndex=null, $type=null)
  
     
  //--------------------------------------------------------------------------
  // constructor and initialisation
  //--------------------------------------------------------------------------   
    
  function __construct($_uid, $_igrtSqli) {
    $this->uid = $_uid;
    $this->igrtSqli = $_igrtSqli;
    $this->htmlBuilder = new htmlBuilder(); 
    $this->formBuilder = new formBuilder(_uid, $_igrtSqli, $this->htmlBuilder);
    $this->_tabIndex = 1;   
  }
}

