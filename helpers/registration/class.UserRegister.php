<?php
global $root_path;
require_once $root_path.'/domainSpecific/mySqlObject.php';
require_once $root_path.'/domainSpecific/domainInfo.php';
require_once $root_path.'/helpers/mail/class.Emailer.php';

class userRegister {
  //global $igrtSqli;
	private $email;
	private $pw;
	private $userId;
	private $profileId;

	public $sqlLog="";

	public function noDuplicate() {
		global $igrtSqli;
		$sqlqry_uexists=sprintf("SELECT * FROM igUsers WHERE email='%s'", $this->email);
		$this->sqlLog.=$sqlqry_uexists.'<br/>';
		$uexists_result=$igrtSqli->query($sqlqry_uexists);
		if ($uexists_result->num_rows > 0) {
			$row=$uexists_result->fetch_object();
			$this->userId=$row->id;     // set it, but probably won't use it
			// email is not unique
			return false;
		}
		else {
			return true;
		}
	}

	public function createUser() {
		global $igrtSqli;
		$this->userId = -1;
		$hash_str = hash('sha1',$this->pw);
		$sqlcmd_CreateUser=sprintf("INSERT INTO igUsers (permissions,activated,registrationDT,email,password,testUser) 
            VALUES ('0','0',NOW(),'%s','%s','0')", $this->email, $hash_str);
		$this->sqlLog.=$sqlcmd_CreateUser.'<br/>';
		$igrtSqli->query($sqlcmd_CreateUser);
		//get id from igUsers ready to push to igProfiles
		$sqlqry_getID=sprintf("SELECT * FROM igUsers WHERE email='%s' LIMIT 1",  $this->email);
		$this->sqlLog.= $sqlqry_getID.'<br/>';
		$getIDresult=$igrtSqli->query($sqlqry_getID);
		$idobject=$getIDresult->fetch_object();
		$this->userId=$idobject->id;
		return $this->userId;
	}

	public function createProfile() {
		global $igrtSqli;
		$fname = isset($_POST['fName']) ? $igrtSqli->real_escape_string($_POST['fName']) : 'not set';
		$sname = isset($_POST['lName']) ? $igrtSqli->real_escape_string($_POST['lName']) : 'not set';
		// attach new Profile record to new User
		$sqlcmd_CreateProfile=sprintf("INSERT INTO igProfiles (userId,fname,sname,activeEmail,profileIsSet) 
            VALUES ('%s','%s','%s','%s','0')",
			$this->userId,
			$fname,
			$sname,
			$this->email);
		$this->sqlLog.= $sqlcmd_CreateProfile.'<br/>';
		$igrtSqli->query($sqlcmd_CreateProfile);
		$sqlqry_getprofileID=sprintf("SELECT * FROM igProfiles WHERE activeEmail='%s' LIMIT 1",  $this->email);
		$this->sqlLog.= $sqlqry_getprofileID.'<br/>';
		$getProfileIDresult=$igrtSqli->query($sqlqry_getprofileID);
		$pIDobject=$getProfileIDresult->fetch_object();
		$this->profileId=$pIDobject->id;
	}

	public function attachProfiletoUser() {
		global $igrtSqli;
		// update new User with profileID
		$sqlcmd_updateID=sprintf("UPDATE igUsers SET profileId='%s' WHERE id='%s'",$this->profileId,$this->userId);
		$this->sqlLog.= $sqlcmd_updateID.'<br/>';
		$igrtSqli->query($sqlcmd_updateID);
	}

	public function createActivation() {
		global $igrtSqli;
    global $systemFrom;
    global $domainName;
		// create an activation record in the activations table
		$hash_str=hash("md5", $this->email);
		$b64_str=base64_encode($hash_str);
    
    // find any existing registration attempt
    $findQry = sprintf("SELECT * FROM igActivations where activationCode = '%s'", $b64_str);
    $findResult = $igrtSqli->query($findQry);
    if ($findResult->num_rows == 0) {
      // create
      		$sqlcmd_CreateActivation=sprintf("INSERT INTO igActivations (userId,activationCode) VALUES ('%s','%s')",$this->userId,$b64_str);
      		//$this->sqlLog.= $sqlcmd_CreateActivation.'<br/>';
      		$igrtSqli->query($sqlcmd_CreateActivation);
    }
    else {
      // update
      $sqlcmd_UpdateActivation = sprintf("UPDATE igActivations SET userId = '%s' WHERE activationCode='%s'", $this->userId, $b64_str);
      $igrtSqli->query($sqlcmd_UpdateActivation);
    }
    
    // send email
		$subject = "Your registration on the imgame server needs activation";
		$body = "<html><head><title>imgame registration</title></head><body>";
		$body.= "<p>Thank you for registering with the imgame server. To activate your account you must click on the link below. This confirms that the email address you used to register actually belongs to you.</p>";
		$body.= "<p><a href='";
		$body.= sprintf("http://%s/helpers/registration/activate.php?act=%s'>click here to activate</a></p>", $domainName, $b64_str);
		$body.= "<p>Thank you - the imitation game team.</p>";
		$body.= "</body></html>";
  
    $emailer = new \mail\Emailer();
    $status = $emailer->sendEmail($this->email, $subject, $body, $systemFrom);
    return $status;
 	}
  
  
  
  public function __construct($_email = NULL, $_pw = NULL) {
		//ctor escapes strings from $_POST to avoid injection
		global $igrtSqli;
		if ($_email === NULL ) {
			$this->email = $igrtSqli->real_escape_string($_POST['regUserName']);
			$this->pw = $igrtSqli->real_escape_string($_POST['pw1']);
		}
		else {
			$this->email = $igrtSqli->real_escape_string($_email);
			$this->pw = $igrtSqli->real_escape_string($_pw);
		}
	}

}
