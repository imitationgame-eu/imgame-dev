<?php
if (!isset($root_path)) { $root_path = $_SERVER['DOCUMENT_ROOT']; }
if (!isset($domainName)) { $do = $_SERVER['DOCUMENT_ROOT']; }
include_once($root_path."/classes/doPost.php");
include_once($root_path."/classes/mailer.php");
include_once($root_path."/helpers/login/class.UserLogin.php");
include_once($root_path . "/helpers/registration/class.UserRegister.php");
include_once($root_path."/helpers/login/class.PasswordManager.php");


class ProcessController {
  public $responseData;   // any display info to be passed back
  private $postie;    // 
  private $pageLabel;
  private $process;
  private $postAction;
  private $domain;
  private $isPasswordRequest;
  
  // controller for password reset form on main.html
  private function ResetPassword() {
    $pwManager = new PasswordManager();
    $processStatus = $pwManager->processReset();
    $postData = ['process'=>0, 'pageLabel'=>''];
    
    if ($processStatus->passwordresetrequest) {
      // build email and send
      $body = "<p>You have requested a password reset from Imgame.</p>";
      $body.= "<p>Please click on the the link below which will take you to a personalised password reset form. Enter your new password on this page to complete the process.</p>";
      $body.= sprintf("<a href='%s'>password reset</a>", $_SERVER['SERVER_NAME'] . "/index.php?email=".$processStatus->email."&uh=" . $processStatus->usernameSHA256hash . "&ph=". $processStatus->passwordSHA1hash);
      $body.= "<p>Thank you.</p>";
      
      $mailer = new mailer($processStatus->email);
      if ($mailer->SendEmail($body)) {
        $postData['pageLabel'] = '0_0_10';
      }
      else {
        $postData['pageLabel'] = '0_0_11';
      }
    }
    else {
      
      if ($processStatus->passwordresetsuccess) {
        $postData['pageLabel'] = '0_0_3';
      }
      if ($processStatus->emailresetsuccess) {
        $postData['pageLabel'] = '0_0_4';
      }
      if ($processStatus->isTooShort) {
        $postData['pageLabel'] = '0_0_5';
      }
      if ($processStatus->mismatch) {
        $postData['pageLabel'] = '0_0_6';
      }
      if ($processStatus->incorrectFormat) {
        $postData['pageLabel'] = '0_0_7';
      }
      if ($processStatus->emailresetexists) {
        $postData['pageLabel'] = '0_0_8';
      }
      if ($processStatus->emailresetincorrectpassword) {
        $postData['pageLabel'] = '0_0_9';
      }
      if ($processStatus->unknownoperation) {
        $postData['pageLabel'] = '0_0_12';
      }
    }
    $this->responseData = $this->postie->do_curl_post($this->domain, $postData);
  }
  
  // controller to authenticate reset link and to show password change form
  private function DoPasswordResetForm() {
    $pwManager = new PasswordManager();
    $processStatus = $pwManager->processPasswordResetLink();
    $postData = ['process'=>0, 'pageLabel'=>'', 'email' => $processStatus->email];
    
    if ($processStatus->passwordResetLinkSuccess) {
      $postData['pageLabel'] = '0_0_13';  // show the reset form
    }
    else {
      $postData['pageLabel'] = '0_0_14';  // show invalid link
    }
    $this->responseData = $this->postie->do_curl_post($this->domain, $postData);
  }
  
  //controller to action the password reset
  private function ActionPasswordReset() {
    $pwManager = new PasswordManager();
    $processStatus = $pwManager->actionPasswordReset();
    $postData = ['process'=>0, 'pageLabel'=>'', 'email' => $processStatus->email];
    if ($processStatus->passwordActioned) {
      $postData['pageLabel'] = '0_0_15';  // show password success
    }
    else {
      $postData['pageLabel'] = '0_0_16';  // show password fail
    }
    $this->responseData = $this->postie->do_curl_post($this->domain, $postData);
  }
  
  // controller for login form on main.html
  private function Login() {
    $uLoginObj = new UserLogin();
    if ($uLoginObj->Login()) {
      switch ($uLoginObj->permissions) {
        case "0" : {
          // participant
          if ($uLoginObj->exptStage > 0) {
            // must be in an active step
            switch ($uLoginObj->exptStage) {
              case 1 : {
                $postdata=array (
                  'process' => 0,
                  'pageLabel' => '4_0_1',
                  'uid' => $uLoginObj->uid,
                  'exptId' => $uLoginObj->exptId,
                  'dayNo' => $uLoginObj->dayNo,
                  'sessionNo' => $uLoginObj->sessionNo,
                  'jType' => $uLoginObj->jType,
                );
                break;
              }
            }
            // now post the user session info into index.php
            $this->responseData=$this->postie->do_curl_post($this->domain, $postdata);
          }
          else {
            $this->process = 0;        // page
            $this->postAction = '0_0_1';   // go to profile/info page
            $postdata=array (
              'process'=>$this->process,
              'pageLabel'=>$this->postAction,
              'uid'=>$uLoginObj->uid,
              'permissions' => $uLoginObj->permissions,
            );
            $this->responseData = $this->postie->do_curl_post($this->domain, $postdata);
          }
          break;
        }
        case "1024":	// superAdminUser (mh)
        case "512":		// experimenter
        case "256" :  // local organiser
        case "128":		// analyst
        {
          $this->process = 0;       // page
          $this->postAction = '1_0_1';  // admin landing page
          $postdata = array (
            'process'=>$this->process,
            'pageLabel'=>$this->postAction,
            'uid'=>$uLoginObj->uid,
            'permissions' => $uLoginObj->permissions,
            'isLive'=> $uLoginObj->isLive,
            'fName' => $uLoginObj->fname,
            'sName' => $uLoginObj->sname,
            'email' => $uLoginObj->email
          );
          $this->responseData = $this->postie->getPageHtml(true, $postdata);
          break;
        }
      }
    }
    else {
      // loginRetry
      $this->process = 0; // page
      $this->pageLabel = '0_0_2';    // login retry page
      $postdata=array ('process'=>$this->process,'pageLabel'=>$this->pageLabel);
      $this->responseData=$this->postie->do_curl_post($this->domain, $postdata);
    }
  }
  
  // controller for register form on main.html
  private function Register() {
    $uRegisterObj = new userRegister(NULL, NULL); // nulls indicate standard registration, not through webservices
    if ($uRegisterObj->noDuplicate()) {
      $okCreate = $uRegisterObj->createUser();
      if ($okCreate > -1) {
        $uRegisterObj->createProfile();
        $uRegisterObj->attachProfiletoUser();
        $smtp = $uRegisterObj->createActivation();
//        echo $smtp;
//        die();
        $this->process = 2;       // status page
        $this->pageLabel = 4;    // show activation page
      }
      else {
        $this->process = 2;       // status page
        $this->pageLabel = 1;    // register error page - hint about duplicate
      }
    }
    else {
      $this->process = 2;     // status page
      $this->pageLabel = 8;    // register error page
    }
    $postdata = array('process' => $this->process, 'pageLabel' => $this->pageLabel);
    //$this->responseData = $this->postie->do_curl_post($this->domain, $postdata);
    $this->responseData = $this->postie->do_curl_post($this->domain, $postdata);
  }
  
  
  public function __construct() {
    $this->postie = new PostChap();
  }

  public function invoke() {
    $this->domain = $_SERVER['SERVER_NAME'];
    $action = isset($_POST['action']) ? $_POST['action'] : -1;

	  if (isset($_GET['uh']) && isset($_GET['ph'])) {
	  	$action = 3;  //special case of password reset from email link
	  }

    switch ($action) {
      case 0 : { $this->Register() ; break; }
      case 1 : { $this->Login(); break; }
	    case 2 : { $this->ResetPassword(); break;}
	    case 3 : { $this->DoPasswordResetForm(); break;}
	    case 4 : { $this->ActionPasswordreset(); break;}
    }
  }
}


