<?php
  $filecontents=file_get_contents(sprintf("%s/%s",$_SERVER['DOCUMENT_ROOT'],"views/classic/classic3role.html"));
  echo $filecontents;

