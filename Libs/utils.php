<?php


function tree(&$role) {
    $role->permissions;
    foreach($role->children as $children) {
      tree($children);  
    }
}

// function uuid($serverID=1)
// {
//     $t=explode(" ",microtime());
//     return sprintf( "%04x-%08s-%08s-%04s-%04x%04x",
//         $serverID,
//         clientIPToHex(),
//         substr("00000000".dechex($t[1]),-8),   // get 8HEX of unixtime
//         substr("0000".dechex(round($t[0]*65536)),-4), // get 4HEX of microtime
//         mt_rand(0,0xffff), mt_rand(0,0xffff));
// }

function uuid($data=null)
{
    if(empty($data)) $data = random_bytes(16);
    assert(strlen($data) == 16);

    $data[6] = chr(ord($data[6]) & 0x0f | 0x40); // set version to 0100
    $data[8] = chr(ord($data[8]) & 0x3f | 0x80); // set bits 6-7 to 10

    return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
  // echo guidv4(openssl_random_pseudo_bytes(16));
  // With PHP 7, generating random byte sequences is even simpler using random_bytes():
  // echo guidv4(random_bytes(16));
}


function short_uuid() {
  return uniqid();
}

function clientIPToHex($ip="") {
    $hex="";
    if($ip=="") $ip=getEnv("REMOTE_ADDR");
    $part=explode(".", $ip);
    for ($i=0; $i<=count($part)-1; $i++) {
        $hex.=substr("0".dechex($part[$i]),-2);
    }
    return $hex;
}

function clientIPFromHex($hex) {
    $ip="";
    if(strlen($hex)==8) {
        $ip.=hexdec(substr($hex,0,2)).".";
        $ip.=hexdec(substr($hex,2,2)).".";
        $ip.=hexdec(substr($hex,4,2)).".";
        $ip.=hexdec(substr($hex,6,2));
    }
    return $ip;
}

if ( ! function_exists( 'implodeKV' ) && ! function_exists('consolelog') ) {

    function implodeKV($glueKV, $gluePair, $KVarray)  {
        if( is_object($KVarray) ) {
           $KVarray = json_decode(json_encode($KVarray),TRUE);
        }
        $t = array();
        foreach($KVarray as $key=>$val) {
            if(is_array($val)){
               $val = implodeKV(':',',',$val);
            }else if( is_object($val)){
                $val = json_decode(json_encode($val),TRUE);
                $val = implodeKV(':',',',$val);
            }

            if(is_int($key)){
                $t[] = $val;
            } else {
                $t[] = $key . $glueKV . $val;
            }
        }
        return implode($gluePair, $t);
    }


function consolelog($status = 200)  {
    if( DEBUG ){
        
        $lists = func_get_args();
        $status = '';
        $status = implodeKV( ':' , ' ' , $lists);
  
       if(isset($_SERVER["REMOTE_ADDR"]) && !empty($_SERVER["REMOTE_ADDR"])){
          $raddr =$_SERVER["REMOTE_ADDR"];
       } else {
          $raddr = '127.0.0.1';
       }

       if(isset($_SERVER["REMOTE_PORT"]) && !empty($_SERVER["REMOTE_PORT"])){
          $rport = $_SERVER["REMOTE_PORT"];
       } else {
          $rport = '8000';
       }

       if(isset($_SERVER["REQUEST_URI"]) && !empty($_SERVER["REQUEST_URI"])){
          $ruri = $_SERVER["REQUEST_URI"];
       } else {
          $ruri = '/console';
       }

       file_put_contents("php://stdout",
           sprintf("[%s] %s:%s [%s]:%s \n",
               date("D M j H:i:s Y"),
               $raddr,$rport,
               $status,
               $ruri
               )
           );
   }

  }  // end-of-consolelog

} // end-of-check funtion exist

function toObj($var){
    return  json_decode(json_encode($var),FALSE);
}

function toArray($var){
    return  json_decode(json_encode($var),TRUE);
}

function converdate($date){
    if($date == 'Present') return $date;
    $de = new DateTime($date);
    return $de->format('M d, Y');    
}


// merge $a -> $b  or $a/$b
function merge($a,$b) {
  return $obj_merged = (object) array_merge((array) $b, (array) $a);
  // foreach($a as $k => $v) {
  //   dump($k,$v);
  //   $b->$k = $v;
  // } 
  // return $b;
}

function nonce(){
  $nonce = microtime() . rand(0,100000);
  return $nonce;
}

if (!function_exists('getallheaders')) {
    function getallheaders() {
    $headers = [];
    foreach ($_SERVER as $name => $value) {
        if (substr($name, 0, 5) == 'HTTP_') {
            $headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
        }
    }
    return $headers;
    }
}