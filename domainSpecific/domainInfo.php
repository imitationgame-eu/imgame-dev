<?php
  // domainName can  be used in many places
  if (!isset($domainName))
    $domainName = $_SERVER['SERVER_NAME'];

  // root path used for all require_once and include_once directives
  if (!isset($root_path))
    $root_path = $_SERVER['DOCUMENT_ROOT'];
  
  // used to configure location of mySQL connection string on production (outside webserver root)
  $configurationPath = '/var/www/config';
  
  // used as do-not-reply address in any system-generated email (e.g. user registration)
  $systemFrom = 'do-not-reply@'.$domainName;

