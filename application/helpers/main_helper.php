<?php
defined('BASEPATH') or exit('No direct script access allowed');

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

function arrayToObject($data)
{
  if (!is_array($data)) {
    return $data;
  }

  return json_decode(json_encode($data), true);
}

function getReqBody($key = 'key', $default = null, $data = [])
{
  $ci = get_instance();

  $res = $default;

  if (isset($data[$key])) {
    $res = $data[$key];
  } else if ($ci->input->post($key)) {
    $res = $ci->input->post($key);
  } else if ($ci->input->get($key)) {
    $res = $ci->input->get($key);
  }

  // * if $res == string
  if (is_string($res)) {
    $res = trim($res);
  }

  // * if $res == array
  if (is_array($res)) {
    $res = array_map('trim', $res);
    $res = arrayToObject($res);
  }

  return $res;
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