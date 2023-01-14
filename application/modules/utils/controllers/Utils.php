<?php
defined('BASEPATH') or exit('No direct script access allowed');

require(APPPATH . 'libraries/REST_Controller.php');

class Utils extends REST_Controller
{
  public function __construct()
  {
    @session_start();
    parent::__construct();
    $this->load->library("Server", "server");
    $this->load->model('M_Utils', 'api');
  }

  public function encoded_post()
  {
    $this->server->require_scope("admin");
    $list = $this->api->encoded();
    $list ? $this->response($list, 200) : $this->response(NULL, 404);
  }

  public function decoded_post()
  {
    $this->server->require_scope("admin");
    $list = $this->api->decoded();
    $list ? $this->response($list, 200) : $this->response(NULL, 404);
  }
}
