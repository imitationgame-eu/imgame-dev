<?php
include_once($_SERVER['DOCUMENT_ROOT'].'/models/uManagement/uProfileM.php');
include_once($_SERVER['DOCUMENT_ROOT'].'/helpers/html/class.htmlBuilder.php');


class uProfileController {
  private $uid;   // id in igUsers
  private $uProfileModel;
  private $htmlBuilder;
  //private $scratchpad;

  public function debug() {
    return $this->uProfileModel->profileAttributes;
  }

  public function getSessionInfo(&$blobType) {
    global $igrtSqli;
    $sqlqry_getsession=sprintf("SELECT * FROM igSessions where hash=\"%s\" LIMIT 1;",hash("md5",$uProfileModel->primary_email));
    $getsessionResult=$igrtSqli->query($sqlqry_getsession);
    if ($getsessionResult->num_rows>0) {
      $row=$getsessionResult->fetch_object();
      $blobType=$row->blobtype;
      $decode=urldecode($row->sessionvalues);
      return unserialize($decode);
    }
  }

  public function storeSessionInfo($blobType) {
    global $igrtSqli;
    $upa=$this->getProfileArray();
    $sessionblob = urlencode(serialize($upa));
    $sqlqry_sessions=sprintf("SELECT * FROM igSessions WHERE hash=\"%s\" LIMIT 1;",hash("md5",$uProfileModel->primaryEmail));
    $sqlhashResult=$igrtSqli->query($sqlqry_sessions);
    if ($sqlhashResult->num_rows > 0) {
      //update
      $row=$sqlhashResult->fetch_object();
      $sqlcmd_replace=sprintf("UPDATE igSessions SET blobtype=%s,sessionvalues=%s WHERE id=\"%s\";",$blobType,$sessionblob,$row->id);
      $igrtSqli->query($sqlcmd_replace);
    }
    else {
      //create
      $sqlcmd_create=sprintf("INSERT into igSessions (hash,blobtype,sessionvalues) VALUES(\"%s\",\"%s\",\"%s\");",hash("md5",$uProfileModel->primary_email),$blobType,$sessionblob);
      $igrtSqli->query($sqlcmd_create);
    }  
  }

  public function buildDynamicForm() {
    $dynamicForm="";
    // firstly, build tabs wrapper & defs
    $dynamicForm.="<div class=\"wrapper\" id=\"admin\">";
    $dynamicForm.="<div class=\"headerDeep\"><h1>User area</h1></div>";
    $dynamicForm.="<div class=\"adminTabs\">";
      // build first tab header (profile)
      $dynamicForm.="<div id=\"tabOne\" class=\"tab\">My details</div>"; 
      $dynamicForm.="<div id=\"forms\" class=\"tabContent\">";
        // build first tab content
        $dynamicForm.=$this->buildProfileForm();
      // build first tab closer
      $dynamicForm.="</div>";        
      // build second tab header (alerts)
      $dynamicForm.="<div id=\"tabTwo\" class=\"tab\">Alerts</div>";   
      $dynamicForm.="<div id=\"alerts\" class=\"tabContent\">";         
        // build second tab 
        $dynamicForm.="alerts content will be injected here.";        
      // build seconds tab closer
      $dynamicForm.="</div>";        
    // build tabs wrapper closer
    $dynamicForm.="</div>";
    return $dynamicForm;
  }

  public function getProfileArray() {
    return $this->uProfileModel->makeProfileArray();
  }

  public function getProfileDetails() {
    global $igrtSqli;
    $sqlqry_universalprofile = sprintf("SELECT t1.id,t1.profileId,t2.fname,t2.mname,t2.sname,t2.dob,t2.gender,t2.activeEmail,t2.profileIsSet,t2.firstlanguage
        FROM igUsers AS t1
        INNER JOIN igProfiles AS t2 ON t2.userId=t1.id
        WHERE t1.id='%s' LIMIT 1;",$this->uid);
    $universalProfileResult = $igrtSqli->query($sqlqry_universalprofile);
    //$debugInfo=$sqlqry_universalprofile."<br/>";
    if ($universalProfileResult->num_rows > 0) {
      //$debugInfo.="found row";
      $row=$universalProfileResult->fetch_object();
      //print_r($row);
      // if ($this->uProfileModel->profileIsSet==0) {$this->uProfileModel->firstLanguage=15;} // default to English if not yet set            
      // instantiate a uProfile class ready for injection into page, and hence later modification
      $this->uProfileModel=new uProfileModel($this->uid,$row->fname,$row->mname,$row->sname,$row->activeEmail,$row->gender,$row->dob,$row->profileIsSet,$row->firstlanguage);
      //create language options in model
      $sqlqry_languagesList=sprintf("SELECT * FROM igLanguages;");
      $languagesResult=$igrtSqli->query($sqlqry_languagesList);
      if ($languagesResult->num_rows > 0) {
        $languages_array=array();
        while ($languageList = $languagesResult->fetch_object()) {
          $languagePair=array(id=>$languageList->id,label=>$languageList->label);
          array_push($languages_array,$languagePair);
        }
      }
      $this->uProfileModel->setLanguagesList($languages_array);
      $sqlqry_dynamicAttributesList = sprintf("SELECT * FROM igProfileAttributes;");
      $dynamicAttributesResult = $igrtSqli->query($sqlqry_dynamicAttributesList);
      $row=$universalProfileResult->fetch_object();
      if ($dynamicAttributesResult->num_rows > 0) {
        // process each additional attribute
        while ($attributeRow=$dynamicAttributesResult->fetch_object()) {
          if ($attributeRow->useInProfile==1) {
            $dataTblName=sprintf("up%sData",$attributeRow->label);
            $optionsTblName=sprintf("up%sOptions",$attributeRow->label);
            $sqlqry_getOptions=sprintf("SELECT * FROM %s;",$optionsTblName);
            // create array in uProfileObj which contains all the possible options
            $optionsResult=$igrtSqli->query($sqlqry_getOptions);
            if ($optionsResult->num_rows >0) {
              $temp_array=array();
              while ($optionsList=$optionsResult->fetch_object()) {
                $optionsPair=array(id=>$optionsList->id,label=>$optionsList->option);
                array_push($temp_array,$optionsPair);
              }
              $this->uProfileModel->setAllAttributes($attributeRow->id, $attributeRow->label, $attributeRow->description, $attributeRow->controltype, $temp_array);
            }
            // if the profile is set, get chosen value, if not, create default value BUT DON'T try putting in table yet 
            if ($this->uProfileModel->profileisSet==1) {
              $sqlqry_getuAttribute=sprintf("SELECT * FROM up%sData WHERE user_id='%s';",$dataTblName,$this->uid);
              $getuAttributeResult=$igrtSqli->query($sqlqry_getuAttribute);
              if ($getuAttributeResult->num_rows >0) {
                $uarow=$getuAttributeResult->fetch_object();
                $this->uProfileModel->setUserAttributes($uarow->option_id,$uarow->option);                              
              }
              else {
                $sqlcmd_insert=sprintf("INSERT INTO up%sData (user_id,option,option_id) VALUES(\"%s\",\"1\",\"%s\");",
                  $this->uid,
                  $this->uProfileModel->profileAttributes[$attributeRow->id-1]["options"][0]["label"]);
                $igrtSqli->query($sqlcmd_insert);                               
              }
            }
            else {
              // use not-yet-set value (normally id=1)
              $this->uProfileModel->setUserAttributes(
                $this->uProfileModel->profileAttributes[$attributeRow->id-1]["options"][0]["id"], 
                $this->uProfileModel->profileAttributes[$attributeRow->id-1]["options"][0]["label"] );
            }                    
          }
        }
      }
      return true;
    }
    else {
      return false;
    }
  }

  public function __construct($id) {
    $this->uid = $id;
    $this->htmlBuilder = new htmlBuilder();
  }   

  // private helper functions


  private function buildProfileForm() {
    $html="";
    $html.="<form id=\"amendProfile\" name=\"amendProfile\" method=\"post\" action=\"index.php\">
                                <input type=\"hidden\" value=\"3\" name=\"action\" />
                                <input type=\"hidden\" value=\"1\" name=\"process\" />";  
    $html.=sprintf("<input type=\"hidden\" value=\"%s\" name=\"id\" />",$this->uProfileModel->id);                        
    $html.="<div class=\"formRow dark\">";
       $html.=$this->htmlBuilder->makeFormDate("dob", "date of birth", "date", $this->uProfileModel->dob);
    $html.="</div>";
    $goList=array();
    $goD=array(id=>0,label=>"not yet selected");
    array_push($goList,$goD);
    $goD=array(id=>1,label=>"female");
    array_push($goList,$goD);
    $goD=array(id=>2,label=>"male");
    array_push($goList,$goD);
    $html.="<div class=\"formRow light\">";            
        $html.=$this->htmlBuilder->makeSelect("genderchoice",$this->uProfileModel->gender,"gender","select",true,$goList);
    $html.="</div>";
    $html.="<div class=\"formRow dark\">";            
        $html.=$this->htmlBuilder->makeSelect("languagechoice",$this->uProfileModel->firstLanguage,"first language","select",true,$this->uProfileModel->languageOptions);
    $html.="</div>";

    // get all additional profile attributes
    $userAttributePtr=0;
    foreach ($this->uProfileModel->profileAttributes as $v) {
      $classColor=$userAttributePtr%2==0?"light":"dark";
      $html.=sprintf("<div class=\"formRow %s\">",$classColor);
      switch ($v["controltype"]) {
        case 1: {
          //dropdown
          $html.=$this->htmlBuilder->makeSelect($v["label"],$this->uProfileModel->userAttributes[$userAttributePtr]["id"],$v["decription"],"select",true,$v["options"]);
          break;
        }
      }
      ++$userAttributePtr;
    }
    $html.="<input type=\"submit\" value=\"Save changes\" class=\"buttonBlue\"/></form>";
    return $html;
  }
    
}

