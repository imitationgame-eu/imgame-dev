<?php
if (!isset($root_path)) { $root_path = $_SERVER['DOCUMENT_ROOT']; }
include_once $root_path.'/domainSpecific/mySqlObject.php';
include_once $root_path.'/helpers/parseJSON.php';
include_once $root_path.'/classes/doPost.php';

$rawBody = file_get_contents('php://input');
$jSonArray = json_decode($rawBody, true);
$email = $jSonArray["email"];
$exptId = $jSonArray["exptId"];
$iType = $jSonArray["iType"];
$uid = -1;


$getLogin = sprintf("SELECT * FROM step1preLogins WHERE email='%s' AND exptId='%s' AND jType='%s'", $email, $exptId, $iType);
$getLoginResult = $igrtSqli->query($getLogin);
$getLoginRow = $getLoginResult->fetch_object();

$actualJNo = -1;

if (isset($getLoginRow)) {
	$actualJNo = $getLoginRow->jNo;
}
else {
	$getPreviousLogins = sprintf("SELECT * FROM step1preLogins WHERE exptId='%s' AND jType='%s'", $exptId, $iType);
	$getPreviousLoginsResult = $igrtSqli->query($getPreviousLogins);
	$maxJNo = 0;
	while ($getPreviousLoginsRow = $getPreviousLoginsResult->fetch_object()) {
		if ($getPreviousLoginsRow->jNo > $maxJNo) {
			$maxJNo = $getPreviousLoginsRow->jNo;
		}
	}
	$actualJNo = $maxJNo;
	$insertLogin = sprintf("INSERT INTO step1preLogins (exptId,jNo,jType,email) VALUES('%s', '%s', '%s', '%s')", $exptId, $actualJNo, $iType, $email );
	$igrtSqli->query($insertLogin);
}

// now get login info from igActiveStep1Users, specifically uid
$getUID = sprintf("SELECT * FROM igActiveStep1Users WHERE exptId = '%s' AND jType = '%s' AND jNo = '%s'", $exptId, $iType, $actualJNo);
$getUIDResult = $igrtSqli->query($getUID);
$getUIDRow = $getUIDResult->fetch_object();
if (isset($getUIDRow)) {
	$uid = $getUIDRow->uid;
	// now push json of parameters back to the .js that called this webService

	$postdata = [];
	$postdata['process'] = 0;
	$postdata['action'] = -1;
	$postdata['pageLabel'] = '4_0_1';
	$postdata['uid'] = $uid;
	$postdata['jType'] = $iType;

	echo json_encode($postdata);

//	$postie = new PostChap();
//	$reponseData = $postie->do_curl_post($_SERVER['SERVER_NAME'], $postdata);
//	echo $responseData;

}








