<?php
if (!isset($root_path)) { $root_path = $_SERVER['DOCUMENT_ROOT']; }
require_once $root_path.'/domainSpecific/mySqlObject.php';

class UserLogin {
	public $inActiveExperiment;
	public $permissions;
	public $email;
	public $uid;
	public $restartUID;
	public $userToken;
	public $exptId;
	public $dayNo;
	public $sessionNo;
	public $jType;
	public $fname;
	public $sname;
	public $exptType;   // classic or multi
	public $exptStage;  // Step1 etc, 0 == an unassigned user, so move to profile page
	public $isLive;     // is on live environment?
	private $pw;
  
  
  //// This login method may be for expt steps ???????

	public function Login() {
		global $igrtSqli;
		// NB, although this code is usable for admin logins, an AJAX web service is used in classic.php
		$hash_str = hash('sha1', $this->pw);
		if ($this->userToken != '') {
			// userToken is simple token created once a user has been logged-in and avoids re-sending password
			// should extend this to sessions once https enabled.
			$tokens = explode('_', $this->userToken);
			$sqlQry_Login=sprintf("SELECT * FROM igUsers WHERE email='%s' AND id='%s' AND permissions='%s'", $this->email, $this->uid, $this->permissions);
		}
		else {
			$sqlQry_Login=sprintf("SELECT * FROM igUsers WHERE email='%s' AND password='%s'", $this->email, $hash_str);
		}
		$loginResult = $igrtSqli->query($sqlQry_Login);
		if ($loginResult->num_rows > 0) {
			$row = $loginResult->fetch_object();
			$this->uid = $row->id;
			$this->permissions = $row->permissions;
			//$this->permissions = 256;
			$profileQry = sprintf("SELECT * FROM igProfiles WHERE userId='%s'", $row->id);
			$profileResult = $igrtSqli->query($profileQry);
			if ($profileResult->num_rows > 0) {
				$profileRow = $profileResult->fetch_object();
				$this->fname = $profileRow->fname;
				$this->sname = $profileRow->sname;
			}
			$liveEnvironmentQry = "SELECT * FROM igEnvironmentStatus";
			$liveEnvironmentResult = $igrtSqli->query($liveEnvironmentQry);
			$liveEnvironmentRow = $liveEnvironmentResult->fetch_object();
			$this->isLive = $liveEnvironmentRow->isLive;
			// if restartUID exists insert/update into pre-step1 survey<->step1 user matching table
			if ($this->restartUID > '') {
				$sql = sprintf("SELECT * FROM s1surveyMapping WHERE step1UID='%s'", $this->uid);
				$result = $igrtSqli->query($sql);
				if ($result->num_rows > 0) {
					$row = $result->fetch_object();
					$update = sprintf("UPDATE s1surveyMapping SET restartUID='%s' WHERE id='%s'", $this->restartUID, $row->id);
					$igrtSqli->query($update);
				}
				else {
					$insert = sprintf("INSERT INTO s1surveyMapping (step1UID, restartUID) VALUES ('%s', '%s')", $this->uid, $this->restartUID);
					$igrtSqli->query($insert);
				}
			}


			$isActive = false;
			// see if in active session, and set parameters accordingly
			$sqlQry_Session=sprintf("SELECT * FROM igActiveStep1Users WHERE uid='%s'", $this->uid);
			$activeResult=$igrtSqli->query($sqlQry_Session);
			if ($activeResult->num_rows > 0) {
				$sessionRow = $activeResult->fetch_object();
				$this->exptId = $sessionRow->exptId;
				$this->dayNo = $sessionRow->day;
				$this->sessionNo = $sessionRow->session;
				$this->jType = $sessionRow->jType;
				$this->exptType = "multi";
				$this->exptStage = 1;
				$isActive=true;
			}
			if (!$isActive) {
				// no active sessions. so profile instead...
				$this->exptStage = 0;
			}
			return true;
		}
		else {
			return false;
		}
	}

	public function __construct() {
		global $igrtSqli;
		//ctor escapes strings from $_POST to avoid injection
		$this->email = $igrtSqli->real_escape_string($_POST['username']);
		$this->pw = $igrtSqli->real_escape_string($_POST['password']);
		$this->restartUID = isset($_POST['restartUID']) ? $_POST['restartUID'] : '';
		$this->userToken = isset($_POST['userToken']) ? $_POST['userToken'] : '';
		$this->exptStage = 0; // profile by default
		$this->exptType = '';
	}

}