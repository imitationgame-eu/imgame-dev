<?php

namespace mail;

require_once $_SERVER['DOCUMENT_ROOT'].'/thirdparties/PHPMailer-master/src/PHPMailer.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;

class Emailer
{
  //private $mail;
  
  //private $_systemFrom;
  function __construct() {
//    $_systemFrom = $systemFrom;
//    $mail = new PHPMailer();
  }
  
  function sendEmail($address, $subject, $bodyHtml, $from) {
    if ($mail = new PHPMailer()) {
      $mail->isSMTP();
      //$mail->SMTPDebug = SMTP::DEBUG_SERVER;
      $mail->SMTPAutoTLS = false;
      $mail->Host = '';
      $mail->SMTPAuth = true;
      $mail->Username = '';
      $mail->Password = '';
      $mail->addAddress($address);
      $mail->setFrom($from);
      $mail->Subject = $subject;
      $mail->msgHTML($bodyHtml);
      if ($mail->send())
        return true;
      else {
        $debug = $mail->ErrorInfo;
        return $debug;
      }
    }
    else {
      //
      return false;
    }
    
  }
  
  
  
}