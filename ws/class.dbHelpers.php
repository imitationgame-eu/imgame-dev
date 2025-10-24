<?php
/**
 * misc db functions
 *
 * @author mh
 */
class DBHelper {
  private $igrtSqli;

  function getEmailFromUid($uid) {
    $qry=sprintf("SELECT * FROM igUsers WHERE id='%s'",$uid);
    $result=$this->igrtSqli->query($qry);
    if ($this->igrtSqli->affected_rows > 0) {
      $row=$result->fetch_object();
      return $row->email;
    }
    return null;
  }


  function __construct($_igrtSqli) {
    $this->igrtSqli = $_igrtSqli;
  }
}

