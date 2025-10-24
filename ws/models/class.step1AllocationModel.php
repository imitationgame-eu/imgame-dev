<?php
/**
 * Allocations Model - takes the number of Even and Odd judges and makes role allocations
 * @author MartinHall
 */

class roleAllocations {
  public $evenJudges = array();
  public $oddJudges = array();
  public $jCnt;
  public $mappings;
  private $actualJCnt;
  private $allocationGeneration;
  public $ejCnt;
  public $ojCnt;
  public $extra_P;
  public $extra_NP;
  public $extra_J;
  public $E_NP_required;
  public $E_P_required;
  public $O_NP_required;
  public $O_P_required;
  

  public function DisposeJudges() {
    $j = count($this->evenJudges);
    for ($i=0; $i<$j; $i++) {
      unset($this->evenJudges[$i]);
    }
    $this->evenJudges = array_values($this->evenJudges);
    $j = count($this->oddJudges);
    for ($i=0; $i<$j; $i++) {
      unset($this->oddJudges[$i]);
    }
     $this->oddJudges = array_values($this->oddJudges);     
  }

  function InitStructures() {
    for ($i=0; $i<$this->ejCnt; $i++) {
      $JList=array();
      $otherNPList=array();
      $otherPList=array();
      $ownNPList=array();
      $ownPList=array();
      $History=array();
      $ratings=array();
      for ($j=0;$j<$this->extra_J;$j++) {
        $JList[$j]=-1;
      }
      for ($j=0;$j<=$this->extra_NP;$j++) {
        $otherNPList[$j]=-1;
        $ownNPList[$j]=-1;
      }
      for ($j=0;$j<=$this->extra_P;$j++) {
        $otherPList[$j]=-1;
        $ownPList[$j]=-1;
      }
      $EallocationRow=array(
        "jNo" => -1,
        "dataSaved" => -1,
        "judges" => $JList,
        "uid" => -1,                   // will be uid from users Table
        "npUid" => -1,                     // for reconnection
        "pUid" => -1,                      // for reconnection
        "otherNPs" => $otherNPList,     // others are the judge number acting as NP or P
        "ownNPs" => $ownNPList,         // own are the judge number that this J is acting to
        "otherPs" => $otherPList,
        "ownPs" => $ownPList,
        "disconnected"=>false,
        "npDisconnected"=>false,
        "pDisconnected"=>false,
        "email"=>"",
        "jclientConfirmed" => false,
        "jclientId" => -1,
        "npclientId" => -1,
        "pclientId" => -1,
        "jAction" => "init",
        "npAction" => "init",
        "pAction" => "init",
        "ownnpAction" => "init",
        "ownpAction" => "init",
        "doneSent" => "notSent",
        "jDone" => "no",
        "npDone" => "no",
        "pDone" => "no",
        "qNo" => 0,
        "clientReplies" => 0,
        "npR"=>'',
        "pR"=>'',
        "jQ"=>'',
        "jIntention"=> '',
        "jCategory"=> '',
        "jRespondent"=> '',
        "jID"=>0,                       // jID will be the universal id for a participant once logged in
        "ratings"=>$ratings,
        "history"=>$History,
        "timeStamp" => microtime(true),
        "correctCnt"=>0,
        "time"=>''
      );
      array_push($this->evenJudges,$EallocationRow);
    }
    for ($i=0; $i<$this->ojCnt; $i++) {
      $JList=array();
      $otherNPList=array();
      $otherPList=array();
      $ownNPList=array();
      $ownPList=array();
      $History=array();
      $ratings=array();
      for ($j=0;$j<$this->extra_J;$j++) {
        $JList[$j]=-1;
      }
      for ($j=0;$j<=$this->extra_NP;$j++) {
        $otherNPList[$j]=-1;
        $ownNPList[$j]=-1;
      }
      for ($j=0;$j<=$this->extra_P;$j++) {
        $otherPList[$j]=-1;
        $ownPList[$j]=-1;
      }
      $OallocationRow=array(
        "jNo" => -1,
        "dataSaved" => -1,
        "judges" => $JList,
        "uid" => -1,                   // will be uid from users Table
        "npUid"=> -1,                     // for reconnection
        "pUid"=> -1,                      // for reconnection
        "otherNPs" => $otherNPList,     // others are the judge number acting as NP or P
        "ownNPs" => $ownNPList,         // own are the judge number that this J is acting to
        "otherPs" => $otherPList,
        "ownPs" => $ownPList,
        "disconnected"=>false,
        "npDisconnected"=>false,
        "pDisconnected"=>false,
        "email"=>"",
        "jclientConfirmed" => false,
        "jclientId" => -1,
        "npclientId" => -1,
        "pclientId" =>-1,
        "jAction" => "init",
        "npAction" => "init",
        "pAction" => "init",
        "ownnpAction" => "init",
        "ownpAction" => "init",
        "doneSent" => "notSent",
        "jDone" => "no",
        "npDone" => "no",
        "pDone" => "no",
        "qNo" => 0,
        "clientReplies" => 0,
        "npR"=>'',
        "pR"=>'',
        "jQ"=>'',
        "jIntention"=> '',
        "jCategory"=> '',
        "jRespondent"=> '',
        "jID"=>0,                       // jID will be the universal id for a participant once logged in
        "ratings"=>$ratings,
        "history"=>$History,
        "timestamp"=>microtime(true),
        "correctCnt"=>0,
        "time"=>''
      );
      array_push($this->oddJudges,$OallocationRow);
    }
  }

  function populateJudges($mappings) {
    for ($i=0; $i<$this->jCnt; $i++) {
      $oddMapPtr = $i * 2;    // because first mapping now has to be odd
      $evenMapPtr = $oddMapPtr + 1;

      $evenNP = intval(($mappings[$evenMapPtr]['NP'] - 1) / 2);
      $evenP = intval(($mappings[$evenMapPtr]['P'] - 1) / 2);
      $this->evenJudges[$i]["otherNPs"][0]=$evenNP;
      $this->evenJudges[$i]["otherPs"][0]=$evenP;
      $this->evenJudges[$evenNP]["ownNPs"][0]=$i;
      $this->oddJudges[$evenP]["ownPs"][0]=$i;

      $oddNP = intval(($mappings[$oddMapPtr]['NP'] - 1) / 2);
      $oddP = intval(($mappings[$oddMapPtr]['P'] -1) / 2);
      $this->oddJudges[$i]["otherNPs"][0]=$oddNP;
      $this->oddJudges[$i]["otherPs"][0]=$oddP;
      $this->oddJudges[$oddNP]["ownNPs"][0]=$i;
      $this->evenJudges[$oddP]["ownPs"][0]=$i;                           
    }
//    $debug = print_r($this->oddJudges, true);
//    $debug.= print_r($this->evenJudges, true);
//    echo $debug;
  }

  function generateFixed() {
    $dir = realpath(dirname(__FILE__));
    $fn = $dir."/s1.csv";
    $lines = file($fn, FILE_IGNORE_NEW_LINES);
    $defLinePtr = 0;
    $noFound = 0;
    $found = false;
    $minP = 4;  
    $_mappings = [];
    //echo $this->jCnt-$minP;
    while (($defLinePtr < count($lines)) && ($found == false)) {
      if (substr($lines[$defLinePtr],0,4) == 'Two,' ) {
        if ($noFound == $this->jCnt-$minP) {
          $found = true;
          //echo $defLinePtr;
          for ($j=0; $j<$this->actualJCnt; $j++) {
            $lineDetails = explode(',', $lines[$defLinePtr + 1 + $j]);
            if ($this->allocationGeneration == 1) {
              $allocated = ['J'=> $lineDetails[1], 'P'=>$lineDetails[2], 'NP'=>$lineDetails[3]];
            }
            else {
              $allocated = ['J'=> $lineDetails[5], 'P'=>$lineDetails[6], 'NP'=>$lineDetails[7]];              
            }
            array_push($_mappings, $allocated);
          }
        }
        ++$noFound;
      }
      ++$defLinePtr;
    }  
    //echo print_r($_mappings, true); 
    $this->mappings = $_mappings;
    $this->populateJudges($_mappings);
    return true;
  }

  function __construct($_jCnt, $_allocationGeneration) { 
    $this->actualJCnt = $_jCnt * 2;
    $this->jCnt = $_jCnt;
    $this->allocationGeneration = $_allocationGeneration;
    $this->ejCnt = $_jCnt; 
    $this->ojCnt = $_jCnt;
    $this->extra_J=0;
    $this->extra_NP=0;
    $this->extra_P=0;
    $this->E_NP_required=1;
    $this->E_P_required=1;
    $this->O_NP_required=1;
    $this->O_P_required=1;
    $this->InitStructures();
  }

}

