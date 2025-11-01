<?php
if (!isset($root_path)) { $root_path = $_SERVER['DOCUMENT_ROOT']; }
	require_once $root_path.'/thirdparties/PHPMailer-master/src/Exception.php';
	require_once $root_path.'/thirdparties/PHPMailer-master/src/PHPMailer.php';
	require_once $root_path.'/thirdparties/PHPMailer-master/src/SMTP.php';

class mailer
{
	private $mail;

	public function __construct($to) {
		$this->mail = new PHPMailer\PHPMailer\PHPMailer();
		$this->mail->IsSMTP();
		$this->mail->Mailer = "smtp";
		//$this->mail->SMTPDebug = 1;
		$this->mail->SMTPAuth = TRUE;
		$this->mail->SMTPSecure = "tls";
		$this->mail->Port = 587;
		$this->mail->Host = "smtp.gmail.com";
		$this->mail->Username = "";
		$this->mail->Password = "";

		$this->mail->IsHTML(true);
		$this->mail->AddAddress($to, "Imgame user");
		$this->mail->SetFrom("donotreply@".$_SERVER['SERVER_NAME'], "Imgame password reset service");
		$this->mail->Subject = "Imgame password reset service";
	}

	public function SendEmail($body) {
		$this->mail->MsgHTML($body);

		if (!$this->mail->Send()) {
			return false;
		}
		else {
			return true;
		}
	}
}