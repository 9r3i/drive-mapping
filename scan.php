<?php
/* unlimited time and timezone */
set_time_limit(false);
date_default_timezone_set('Asia/Jakarta');
/* get global argument vector */
global $argv;
/* check argument 1 and directory */
if(!isset($argv[1])||!is_dir($argv[1])){
  echo "Error: Require a valid directory.\r\n";
  goto DONE;
}
/* get directory */
$dir=$argv[1];
/* get exclude items - larger than 2 argument */
$exclude=null;
if(count($argv)>2){
  $exclude=array_slice($argv,2);
}
/* scan the directory */
global $fscanned,$fdir,$fdscan;
$fscanned=0;$fdscan=[];
$startingTime=microtime(true);
$fdir=str_replace('\\','/',$dir);
$fdir.=substr($fdir,-1)!='/'?'/':'';
$data=scan($dir,$exclude);
echo "\r\n";
/* check scanned data */
if(!is_object($data)){
  echo "Error: Failed to scan directory.\r\n";
  goto DONE;
}
/* write output into the file as json */
$fn='scanned-'.preg_replace('/[^a-z0-9-]/i','-',$dir).'-'.date('ymd-Hi').'.json';
$json=json_encode($data);
$size=@file_put_contents($fn,$json);
/* check file */
if($size==false){
  echo "Error: Failed to write file.\r\n";
  goto DONE;
}
/* result output */
$microtime=substr(preg_replace('/^\d+\./','',microtime(true)-$startingTime),0,3);
$spendingTime=timerSecondToHour(floor(microtime(true)-$startingTime));
echo "Size: ".number_format($size/1024,0,'.','')." KB\r\n";
echo "Time: {$spendingTime}.{$microtime}\r\n";

/* end as done */
DONE:


/* scan recursively
 * @parameters:
 *   $d = string of directory
 *   $l = array of excluded items; default: null
 * @return: array of directory data
 */
function scan($d=null,$l=null){
  $res=(object)[
    'name'=>basename($d),
    'size'=>0,
    'file'=>0,
    'dir'=>0,
    'data'=>[],
    'error'=>false,
  ];
  if(!is_string($d)||!is_dir($d)){
    $res->error='Error: Invalid directory, '.$d;
    return $res;
  }
  $d=str_replace('\\','/',$d);
  $d.=substr($d,-1)!='/'?'/':'';
  $s=@array_diff(@scandir($d),['.','..']);
  $l=is_array($l)?$l:[];
  if(!is_array($s)){
    $res->error='Error: Failed to scan directory, '.basename($d);
    return $res;
  }$temp=[];
  global $fscanned,$fdir,$fdscan;
  $fdscan[]=$d;
  $ldr=['-','/','|','\\'];
  foreach($s as $f){
    $fscanned++;
    $fstext=substr($d.$f,strlen($fdir));
    if(in_array($fstext,$l)
      ||(!is_dir($d.$f)&&!is_file($d.$f))
      ||in_array($d.$f,$fdscan)){continue;}
    if(strlen($fstext)>60){
      $before=substr(substr($fstext,0,strpos($fstext,'/',3)),0,15);
      $after=preg_replace('/^[^\/]+\//','',substr($fstext,-45));
      $fstext=$before.'~...'.$after;
    }printf('%-77s',substr("\r".$fscanned.' '.$ldr[$fscanned%count($ldr)].' '.$fstext,0,77));
    if(is_dir($d.$f)){
      $t=scan($d.$f,$l);
      if(!$t){continue;}
      $res->size+=$t->size;
      $res->file+=$t->file;
      $res->dir+=$t->dir;
      $res->dir+=1;
      $res->data[]=$t;
    }else{
      $s=fsize($d.$f);
      $res->file+=1;
      $res->size+=$s;
      $temp[]=(object)['name'=>$f,'size'=>$s];
    }
  }$res->data=array_merge($res->data,$temp);
  return $res;
}
/* filesize */
function fsize($f=null){
  if(!is_string($f)){return 0;}
  if(is_dir($f)){return @\filesize($f);}
  if(!is_file($f)){return 0;}
  $t=@\filesize($f);
  if(PHP_INT_SIZE===8){
    return $t;
  }elseif($t>0
    &&($i=@\fopen($f,'rb'))
    &&is_resource($i)
    &&fseek($i,0,SEEK_END)===0
    &&ftell($i)==$t
    &&fclose($i)){
    return $t;
  }elseif(strtoupper(substr(PHP_OS,0,3))==="WIN"){
    @exec('for %I in ("'.$f.'") do @echo %~zI',$o);
    return floatval(@$o[0]);
  }elseif(strtoupper(substr(PHP_OS,0,5))==="LINUX"
    ||strtoupper(substr(PHP_OS,0,6))==="DARWIN"){
    @exec('stat -c%s '.$f,$o);
    return floatval(@$o[0]);
  }return 0;
}

/* readable timer */
function timerSecondToHour($r=0){
  $r=intval($r);
  $s=$r%60;
  $m=floor($r/60)%60;
  $h=floor($r/3600)%24;
  $d=floor($r/(3600*24));
  if($d){
    return sprintf('%d days, %02d:%02d:%02d',$d,$h,$m,$s);
  }return sprintf('%02d:%02d:%02d',$h,$m,$s);
}


