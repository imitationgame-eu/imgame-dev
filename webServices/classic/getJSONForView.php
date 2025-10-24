<?php
if (!isset($root_path)) { $root_path = $_SERVER['DOCUMENT_ROOT']; }
if (!isset($_GET['exptId'])) { die('no exptId given'); }
$exptId=$_GET['exptId'];
$dayNo=$_GET["dayNo"];
$sessionNo=$_GET["sessionNo"];
include_once $root_path.'/domainSpecific/mySqlObject.php';       
include_once $root_path.'/helpers/parseJSON.php';
include_once $root_path.'/helpers/models/class.experimentModel.php';

$experiments = [];
$eModel = new experimentModel($exptId);
$title = $eModel->title;
$days = [];
for ($dayPtr=$dayNo; $dayPtr<=$dayNo+1; $dayPtr++) {
	$sessions = [];
	for ($sessionPtr=$sessionNo; $sessionPtr<=$sessionNo; $sessionPtr++) {
		$owners = [];
		$groupSql = sprintf("SELECT DISTINCT(owner) FROM dataClassic WHERE exptId='$exptId'"
			. "AND dayNo='%s' AND sessionNo='%s' ORDER BY owner ASC",
			$dayNo, $sessionNo);
		//echo $groupSql.'<br/>';
		$groupResult = $igrtSqli->query($groupSql);
		if ($groupResult) {
			while ($groupRow = $groupResult->fetch_object()) {
				$owner = $groupRow->owner;
				$getUserIdSql = sprintf("SELECT * FROM igUsers WHERE id='%s'", $owner);
				$userResult = $igrtSqli->query($getUserIdSql);
				$userRow = $userResult->fetch_object();
				$userId = $userRow->email;
				$turns = [];
				$turnsSql = sprintf("SELECT * FROM dataClassic WHERE "
					. "exptId='$exptId' AND dayNo='%s' AND sessionNo='%s' AND owner='%s' ORDER BY qNo ASC",
					$dayNo, $sessionNo, $owner);
				//echo $turnsSql.'<br/>';
				$turnResult = $igrtSqli->query($turnsSql);
				if ($turnResult) {
					$turnNo = 1;
					while ($turnRow = $turnResult->fetch_object()) {
						$owner = $turnRow->owner;
						//$npLeft = $turnRow->npLeft;
						$turn = [
							'owner'=> $owner,
							'npLeft'=> $turnRow->npLeft,
							'jQ'=> $turnRow->jQ,
							'npA'=> $turnRow->npA,
							'pA'=> $turnRow->pA,
							'choice'=> $turnRow->choice,
							'confidence'=> $turnRow->confidence,
							'reason'=> $turnRow->reason,
							'turnNo'=> $turnNo++
						];
						array_push($turns, $turn);
					}
				}
				$ownergroup = [
					'owner'=> $owner,
					'email'=> $userId,
					'turns'=> $turns
				];
				array_push($owners, $ownergroup);
			}
		}
		$session = [
			'sessionNo'=> $sessionNo,
			'owners'=> $owners
		];
		array_push($sessions, $session);
	}
	$day = [
		'dayNo'=> $dayNo,
		'sessions'=> $sessions
	];
	array_push($days, $day);
}
$dayArray = ['exptId'=>$exptId, 'title'=>$title, 'dayNo'=>$dayNo, 'sessionNo'=>$sessionNo, 'days'=>$days];
array_push($experiments, $dayArray);

// make JSON

echo json_encode($dayArray);
//foreach ($experiments as $experiment) {
//	foreach ($experiment['days'] as $day) {
//		foreach ($day['sessions'] as $session) {
//			foreach ($session['owners'] as $owner) {
//				foreach ($owner['turns'] as $turn) {
//					$choice = $turn['choice'];
//					$npLeft = $turn['npLeft'];
//					if ($eModel->choosingNP == 1) {
//						$correct = ($choice != $npLeft) ? "correct" : "incorrect";
//					}
//					else {
//						$correct = ($choice == $npLeft) ? "correct" : "incorrect";
//					}
//					$rowArray = [
//						$exptId,
//						$title,
//						$day["dayNo"],
//						$session["sessionNo"],
//						$owner["email"],
//						$turn['turnNo'],
//						$npLeft,
//						$eModel->choosingNP,
//						$choice,
//						$correct,
//						$turn['confidence'],
//						$turn['jQ'],
//						$turn['npA'],
//						$turn['pA'],
//						$turn['reason']
//					];
//					//fputcsv($fileBody, $rowArray);
//				}
//			}
//		}
//	}
//}
//

//fclose($fileBody);




