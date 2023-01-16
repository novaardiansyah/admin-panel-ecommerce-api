<?php
defined('BASEPATH') or exit('No direct script access allowed');

use Dotenv\Dotenv;

function getTimes($data = 'now', $format = 'Y-m-d H:i:s')
{
  date_default_timezone_set('Asia/Jakarta');
  return date($format, strtotime($data));
}

function format_date($data, $format = 'Y-m-d H:i:s')
{
  date_default_timezone_set('Asia/Jakarta');

  if (strpos($data, '/') !== false) {
    $data = str_replace('/', '-', $data);
  }

  return date_format(date_create($data), $format);
}

function random_tokens($length, $type = null, $seconds = false)
{
  $token = "";

  $lower_case = 'abcdefghijklmnopqrstuvwxyz';
  $upper_case = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
  $numbers    = '0123456789';

  if ($type == null) {
    $type = ['lowercase', 'uppercase', 'numeric'];
  }

  $final_string = '';

  if (in_array('lowercase', $type)) {
    $final_string .= $lower_case;
  }

  if (in_array('uppercase', $type)) {
    $final_string .= $upper_case;
  }

  if (in_array('numeric', $type)) {
    $final_string .= $numbers;
  }

  $max = strlen($final_string);

  for ($i = 0; $i < $length; $i++) {
    $token .= $final_string[crypto_rand_secure(0, $max - 1)];
  }

  if ($seconds) {
    $token .= getTimes('now', 's');
  }

  return $token;
}

function crypto_rand_secure($min, $max)
{
  $range = $max - $min;
  if ($range < 1) return $min; // not so random...

  $log    = ceil(log($range, 2));
  $bytes  = (int) ($log / 8) + 1;    // length in bytes
  $bits   = (int) $log + 1;          // length in bits
  $filter = (int) (1 << $bits) - 1;  // set all lower bits to 1

  do {
    $rnd = hexdec(bin2hex(openssl_random_pseudo_bytes($bytes)));
    $rnd = $rnd & $filter; // discard irrelevant bits
  } while ($rnd > $range);

  return $min + $rnd;
}

function arrayToObject($array)
{
  if (!is_array($array)) {
    return $array;
  }

  $object = new stdClass();
  if (is_array($array) && count($array) > 0) {
    foreach ($array as $name => $value) {
      $name = trim($name);
      if (!empty($name)) {
        $object->$name = arrayToObject($value);
      }
    }
    return $object;
  } else {
    return FALSE;
  }
}

function encode($value)
{
  if (!$value) return false;

  $ci = get_instance();

  $dotenv = Dotenv::createImmutable(dirname(__FILE__, 3));
  $dotenv->load();

  $key       = sha1($_ENV['ENCODED_KEY']);
  $strLen    = strlen($value);
  $keyLen    = strlen($key);
  $j         = 0;
  $crypttext = '';

  for ($i = 0; $i < $strLen; $i++) {
    $ordStr = ord(substr($value, $i, 1));
    if ($j == $keyLen) {
      $j = 0;
    }
    $ordKey = ord(substr($key, $j, 1));
    $j++;
    $crypttext .= strrev(base_convert(dechex($ordStr + $ordKey), 16, 36));
  }

  return base64_encode($crypttext);
}

function decode($value)
{
  if (!$value) return false;

  $ci = get_instance();

  $dotenv = Dotenv::createImmutable(dirname(__FILE__, 3));
  $dotenv->load();

  $value       = base64_decode($value);
  $key         = sha1($_ENV['ENCODED_KEY']);
  $strLen      = strlen($value);
  $keyLen      = strlen($key);
  $j           = 0;
  $decrypttext = '';

  for ($i = 0; $i < $strLen; $i += 2) {
    $ordStr = hexdec(base_convert(strrev(substr($value, $i, 2)), 36, 16));
    if ($j == $keyLen) {
      $j = 0;
    }
    $ordKey = ord(substr($key, $j, 1));
    $j++;
    $decrypttext .= chr($ordStr - $ordKey);
  }

  return $decrypttext;
}

function requestApi($url, $method = 'POST', $data = [], $contentType = 'form-urlencoded')
{
  $ci = get_instance();
  
  $send = [];
  $data = array_merge($data, $send);
  
  $curl = curl_init();

  $params = [
    CURLOPT_URL            => $url,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_ENCODING       => '',
    CURLOPT_MAXREDIRS      => 10,
    CURLOPT_TIMEOUT        => 0,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
    CURLOPT_CUSTOMREQUEST  => $method
  ];

  if ($method == 'POST' || $method == 'PUT' || $method == 'DELETE' || $method == 'PATCH') {
    $params[CURLOPT_POSTFIELDS] = $data;

    if ($contentType == 'form-data') {
      $params[CURLOPT_HTTPHEADER] = [
        "Content-Type: multipart/form-data",
        "cache-control: no-cache"
      ];
    } else if ($contentType == 'form-urlencoded') {
      $params[CURLOPT_HTTPHEADER] = [
        "Content-Type: application/x-www-form-urlencoded",
        "cache-control: no-cache"
      ];

      $params[CURLOPT_POSTFIELDS] = http_build_query($data);
    } else {
      $params[CURLOPT_HTTPHEADER] = [
        "Content-Type: application/json",
        "cache-control: no-cache"
      ];
    }
  }

  curl_setopt_array($curl, $params);

  $error = curl_error($curl);
  
  if ($error) {
    curl_close($curl);
    return ['status' => false, 'status_code' => 500, 'message' => $error];
  }
  
  $response = curl_exec($curl);
  $response = json_decode($response, FALSE);

  curl_close($curl);

  return $response;
}

function cleanInput($input)
{
  $search = array(
    '@<script[^>]*?>.*?</script>@si',   // Strip out javascript
    '@<[\/\!]*?[^<>]*?>@si',            // Strip out HTML tags
    '@<style[^>]*?>.*?</style>@siU',    // Strip style tags properly
    '@<![\s\S]*?--[ \t\n\r]*>@'         // Strip multi-line comments
  );

  $output = preg_replace($search, '', $input);
  return $output;
}

function getReqBody($key = 'key', $default = null, $data = [])
{
  $ci = get_instance();

  $res = $default;

  if (isset($data[$key])) {
    $res = $data[$key];
  } else if (isset($_POST[$key])) {
    $res = $_POST[$key];
  } else if (isset($_GET[$key])) {
    $res = $_GET[$key];
  }

  // * if $res == string
  if (is_string($res)) {
    $res = cleanInput($res);
    $res = trim($res);
  }

  return $res;
}

function responseModelApi($params = [], $data = [])
{
  $ci = get_instance();
  
  $params = arrayToObject($params);

  if ($params->status == true)
  {
    $res = [
      'status'      => true,
      'status_code' => isset($params->status_code) ? $params->status_code : 200,
      'status_text' => isset($params->status_text) ? $params->status_text : 'OK',
      'message'     => $params->message,
      'data'        => $data,
      'error'       => NULL
    ];
  } else {
    $res = [
      'status'      => false,
      'status_code' => isset($params->status_code) ? $params->status_code : 400,
      'status_text' => isset($params->status_text) ? $params->status_text : 'Bad Request',
      'message'     => $params->message,
      'data'        => NULL,
      'error'       => $data
    ];
  }


  return $res;
}