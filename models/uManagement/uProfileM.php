<?php
//data class which represents the user profile data
//referenced by controllers/uManagement/uProfileController.php
class uProfileModel {
  public $id;
  public $fname;
  public $mname;
  public $sname;
  public $dob;
  public $primaryEmail;
  public $gender;
  public $profileIsSet;
  public $firstLanguage;
  public $userAttributes;         // array of user-selections of all dynamic attributes
  public $profileAttributes;      // array of all possible attributes and values
  public $languageOptions;        // standard list of language options

  public function makeProfileArray() {
    $profileArray=array(
      'id'=>$this->id,
      'fname'=>$this->fname,
      'mname'=>$this->mname,
      'sname'=>$this->sname,
      'dob'=>$this->dob,
      'primary_email'=>$this->primaryEmail,
      'gender'=>$this->gender,
      'profileisSet'=>$this->profileIsSet,
      'firstLanguage'=>$this->firstLanguage,
      'userAttributes'=>$this->userAttributes,
      'profileAttributes'=>$this->profileAttributes,
      'languageOptions'=>$this->languageOptions
    );
    return $profileArray;
  }

  public function setLanguagesList($languages) {
    $this->languageOptions=$languages;
  }

  public function setUserAttributes($option_id,$option) {
    $array_row=array(
      id=>$option_id,
      label=>$option
    );
    array_push($this->userAttributes,$array_row);        
  }

  public function setAllAttributes($aid,$attributeName,$attributeDesc,$controlType, $options) {
    $array_row=array(
      "idptr"=>$aid,
      "controltype"=>$controlType,
      "name"=>$attributeName,
      "desc"=>$attributeDesc,
      "options"=>$options
    );
    array_push($this->profileAttributes,$array_row);
  }

  public function __construct($pid,$pfname,$pmname,$psname,$pprimary_email,$pgender,$pdob,$pprofileIsSet,$pfirstlanguage) {
  $this->id=$pid;
  $this->fname=$pfname;
  $this->mname=$pmname;
  $this->sname=$psname;
  $this->dob=$pdob;
  $this->primaryEmail=$pprimary_email;
  $this->gender=$pgender;
  $this->profileIsSet=$pprofileIsSet;
  $this->firstLanguage=$pfirstlanguage;
  $this->profileAttributes=array();
  $this->userAttributes=array();
  }
}

