<?php
defined('BASEPATH') or exit('No direct script access allowed');

class M_Utils extends CI_Model
{
  public function __construct()
  {
    parent::__construct();
    $this->load->library("Server", "server");
  }

  public function encoded()
  {
    $key = getReqBody('key');

    return [
      'status'      => true,
      'status_code' => 200,
      'message'     => 'Encoded success',
      'data'        => [
        'encoded' => encode($key)
      ]
    ];
  }

  public function decoded()
  {
    $key = getReqBody('key');

    return [
      'status'      => true,
      'status_code' => 200,
      'message'     => 'Decoded success',
      'data'        => [
        'decoded' => decode($key)
      ]
    ];
  }
}
