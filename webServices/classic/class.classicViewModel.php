<?php

class classicViewModel {
  private $exptId;
  private $igrtSqli;
  private $experimentVM;
  private $htmlBuilder;
  public $exptDef;
  // judge members
  public $jQ;
  public $lContent;
  public $rContent;
  public $useLikert;
  public $useExtraLikert;
  public $useReasons;
  public $useReasonFinalRating;
  // resp members
  public $recentQ,$recentA;


  // judge helpers
  function getJudgeFinalRatingHtml() {
//        $html=$this->htmlBuilder->makeJudgeFinalChoice("judgesFinalChoice",$this->exptDef->labelChoiceFinalRating,"finalJudgement");
//        if ($this->exptDef->useReasonFinalRating)
//        {
//            $html.=$this->htmlBuilder->makeJudgeFinalReason("judgesMainReason",$this->exptDef->labelReasonFinalRating);           
//        }
//        return $html;
  }

  function getJudgeRatingHtml() {
//        $html=$this->htmlBuilder->makeJudgeChoice("jRating",$this->exptDef->labelChoice,"judgement");
//        if ($this->exptDef->useLikert)
//        {
//            $html.=$this->htmlBuilder->makeJudgeLikert($this->exptDef->instLikert,$this->exptDef->labelLikert);            
//        }
//        if ($this->exptDef->useExtraLikert)
//        {
//            $html.=$this->htmlBuilder->makeExtraJudgeLikert($this->exptDef->instExtraLikert,$this->exptDef->labelExtraLikert);                        
//        }
//        if ($this->exptDef->useReasons)
//        {
//            $html.=$this->htmlBuilder->makeJudgeReason("jReason",$this->exptDef->labelReasons);           
//        }
//        return $html;
  }

  function getJCurrentValues($uid) {
//        global $igrtSqli;
//        // get np and p replies
//        $sql=sprintf("SELECT * FROM dataClassic WHERE id='%s' ORDER BY insertDT DESC",$uid);
//        $result=$igrtSqli->query($sql);
//        $row=$igrtSqli->fetch_object(); // most recent is top of the list
//        $this->jQ=$row->jQ;
//        if ($uid%2==0) {
//            $this->lContent=$row->npA;
//            $this->rContent=$row->pA;
//        }
//        else {
//            $this->lContent=$row->pA;
//            $this->rContent=$row->npA;              
//        }
//        // set values for optional rating parameters required for client-side use and validation
//        $this->useLikert=($this->exptDef->useLikert)?1:0;
//        $this->useExtraLikert=($this->exptDef->useExtraLikert)?1:0;
//        $this->useReasons=($this->exptDef->useReasons)?1:0; 
//        $this->useReasonFinalRating=($this->exptDef->useReasonFinalRating)?1:0;             
  }

  function buildFinalJRatingHtml($uid) {

  }

  function getJHistory($uid) {
//        global $igrtSqli;
//        $j_html='<div></div>';
//        $reverseHistory=array();
//        $sql=sprintf("SELECT * FROM dataClassic WHERE id='%s' ORDER BY insertDT ASC",$uid);
//        $result=$igrtSqli->query($sql);
//        if ($igrtSqli->affected_rows > 0) {
//            while ($row=$result->fetch_object()) {
//                $det=array(
//                    'jQuestion'=>$row->jQ,
//                    'npReply'=>$row->npA,
//                    'pReply'=>$row->pA,
//                );
//                array_push($reverseHistory,$det);
//            }               
//        }
//        $lastNumber=count($reverseHistory);
//        if ($uid%2==0)      // counter-balance left/right responses
//        {
//            foreach ($reverseHistory as $v)
//            {
//                $j_html.='<div class="previousQuestion"><p>.....................................................................................................................................................................</p>';
//                $j_html.=sprintf("<p><span>Question %s</span>%s</p>",$lastNumber,$v["jQuestion"]);
//                $j_html.=sprintf("<div class=\"responseOne\"><h3>Response 1: </h3><p>%s</p></div>",$v["npReply"]);
//                $j_html.=sprintf("<div class=\"responseTwo\"><h3>Response 2: </h3><p>%s</p></div>",$v["pReply"]);
//                $j_html.='</div>';
//                --$lastNumber;
//            }
//        }
//        else
//        {
//            foreach ($reverseHistory as $v)
//            {
//                $j_html.='<div class="previousQuestion"><p>.....................................................................................................................................................................</p>';
//                $j_html.=sprintf("<p><span>Question %s</span>%s</p>",$lastNumber,$v["jQuestion"]);
//                $j_html.=sprintf("<div class=\"responseOne\"><h3>Response 1: </h3><p>%s</p></div>",$v["pReply"]);
//                $j_html.=sprintf("<div class=\"responseTwo\"><h3>Response 2: </h3><p>%s</p></div>",$v["npReply"]);
//                $j_html.='</div>';
//                --$lastNumber;
//            }
//        }
//        return $j_html;            
  }

  function __construct($_exptId, $_igrtSqli, $_htmlBuilder, $_experimentVM) {
    $this->exptId=$_exptId;
    $this->igrtSqli=$_igrtSqli;
    $this->experimentVM=$_experimentVM;
    $this->exptDef=$_experimentVM->getExptDetails($_exptId);
    $this->htmlBuilder=$_htmlBuilder;
  }
}

