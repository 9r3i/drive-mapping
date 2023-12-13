<?php
header('content-type:text/plain');
$scan=@scandir('.');
$scan=is_array($scan)?$scan:[];
$output=[];
foreach($scan as $file){
  if(preg_match('/\.json$/i',$file)){
    $output[]=$file;
  }
}
echo json_encode($output);
