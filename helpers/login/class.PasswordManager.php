<?php
if (!isset($root_path)) { $root_path = $_SERVER['DOCUMENT_ROOT']; }
require_once $root_path.'/domainSpecific/mySqlObject.php';

class PasswordManager {
	public function __construct() {

	}

	public function processReset() {
		global $igrtSqli;
		$lowerCase = range('a', 'z');
		$upperCase = range('A', 'Z');
		$numbers = range('0', '9');

		$status = new stdClass();
		$status->isTooShort = false;
		$status->incorrectFormat = false;
		$status->mismatch = false;
		$status->passwordresetsuccess = false;
		$status->emailresetsuccess = true;
		$status->emailresetincorrectpassword = false;
		$status->emailresetexists = false;
		$status->passwordresetrequest = false;
		$status->unknownoperation = false;
		$status->email = "";
		$status->usernameSHA265hash = "";
		$status->passwordSHA1hash = "";

		$email = $_POST['usernameReset'];
		$newpw1 = $_POST['newpw1'];
		$newpw2 = $_POST['newpw2'];
		$currentpw = $_POST['currentpw'];
		$newUserName = $_POST['newUserName'];

		if (strlen($currentpw) !=0 && strlen($email) !=0 && strlen($newpw1) == 0 && strlen($newpw2) == 0 && strlen($newUserName) ) {
			$status->unknownoperation = true;
			return $status;
		}

		if (strlen($currentpw) == 0 && strlen($newpw1) == 0 && strlen($newpw2) == 0) {
			// password reset, send email with encrypted link
			$status->passwordresetrequest = true;
			$status->email = $email;
			$status->usernameSHA256hash = hash('sha256', $email);
			$getPWhash = sprintf("SELECT * FROM igUsers WHERE email='%s'", $email);
			$getHashresult = $igrtSqli->query($getPWhash);
			$idObject = $getHashresult->fetch_object();
			$status->passwordSHA1hash = $idObject->password;
			return $status;
		}

		if (strlen($newUserName) > 3 &&strlen($newpw1) == 0 && strlen($newpw2) == 0) {
			// email reset

			// check new email doesn't already exist.
			$newID = sprintf("SELECT * FROM igUsers WHERE email='%s'", $newUserName);
			$newIDResult = $igrtSqli->query($newID);
			$newIDObject = $newIDResult->fetch_object();
			if (is_null($newIDObject)) {
				// can reset!
				$pwHash = hash('sha1', $currentpw);
				$getID = sprintf("SELECT * FROM igUsers WHERE email='%s' AND password='%s'", $email, $pwHash);
				$idResult = $igrtSqli->query($getID);
				$idobject = $idResult->fetch_object($idResult);
				if (!is_null($idobject)){
					// correct password supplied and so can update
					$status->emailresetsuccess = true;
					$updateUser = sprintf("UPDATE igUsers SET email='%s' WHERE id='%s'", $newUserName, $idobject->id);
					$igrtSqli->query($updateUser);
				}
				else {
					$status->emailresetincorrectpassword = true;
				}
			}
			else {
				$status->emailresetexists = true;
			}
		}

		if (strlen($newpw1)>7) {
			if ($newpw1 != $newpw2) {
				$status->mismatch = true;
			}
			else {
				$hasLower = false;
				$hasUpper = false;
				$hasNumeric = false; //
				$characters = str_split($newpw1);

				foreach ($characters as $character) {
					if (in_array($character, $lowerCase)) {
						$hasLower = true;
					}
					if (in_array($character, $upperCase)) {
						$hasUpper = true;
					}
					if (in_array($character, $numbers)) {
						$hasNumeric = true;
					}
				}

				if ($hasLower && $hasUpper && $hasNumeric) {
					$hash_str = hash('sha1',$newpw1);
					$updateUser = sprintf("UPDATE igUsers SET password='%s' WHERE email='%s'", $hash_str, $email);
					$igrtSqli->query($updateUser);
					$status->passwordsuccess = true;
				}
				else {
					$status->incorrectFormat = true;
				}

			}
		}
		else {
			// new length too short
			$status->isTooShort = true;
		}
		return $status;
	}

	// authenticate against the parameters sent from email link
	public function processPasswordResetLink() {
		global $igrtSqli;

		$email = $_GET['email'];

		$processStatus = new stdClass();
		$processStatus->passwordResetLinkSuccess = false;
		$processStatus->email = $email;

		$usernameSHA256hash = hash('sha256', $email);
		$sentHash = $_GET['uh'];
		if ($usernameSHA256hash == $sentHash) {
			$getUser = sprintf("SELECT * FROM igUsers WHERE email = '%s' AND password = '%s'", $email, $_GET['ph']);
			$userResult = $igrtSqli->query($getUser);
			$userObject = $userResult->fetch_object();
			if (!is_null($userObject)) {
				if ($userObject->email == $email)
					$processStatus->passwordResetLinkSuccess = true;
			}
		}
		return $processStatus;
	}

	public function actionPasswordReset() {
		global $igrtSqli;
		$lowerCase = range('a', 'z');
		$upperCase = range('A', 'Z');
		$numbers = range('0', '9');

		$newpw1 = $_POST['newpw1'];
		$newpw2 = $_POST['newpw2'];
		$email = $_POST['hiddenEmail'];

		$status = new stdClass();
		$status->passwordActioned = false;
		$status->passwordMismatch = false;
		$status->email = $email;

		if ($newpw1 != $newpw2) {
			$status->passwordMismatch = true;
			return $status;
		}

		$hasLower = false;
		$hasUpper = false;
		$hasNumeric = false; //
		$characters = str_split($newpw1);

		foreach ($characters as $character) {
			if (in_array($character, $lowerCase)) {
				$hasLower = true;
			}
			if (in_array($character, $upperCase)) {
				$hasUpper = true;
			}
			if (in_array($character, $numbers)) {
				$hasNumeric = true;
			}
		}

		if ($hasLower && $hasUpper && $hasNumeric) {
			$hash_str = hash('sha1',$newpw1);
			$updateUser = sprintf("UPDATE igUsers SET password='%s' WHERE email='%s'", $hash_str, $email);
			$igrtSqli->query($updateUser);
			$status->passwordActioned = true;
		}
		return $status;

	}
 
 
}
