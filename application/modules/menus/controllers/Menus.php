<?php 
defined('BASEPATH') or exit('No direct script access allowed');

require(APPPATH . 'libraries/REST_Controller.php');

class Menus extends REST_Controller
{
  public function __construct()
  {
    @session_start();
    parent::__construct();
    $this->load->library("Server", "server");
    $this->load->model('M_Menus', 'api');
  }

  public function index_get()
  {
    $this->server->require_scope('admin');
    $res = $this->api->index_get();
    $res ? $this->response($res, 200) : $this->response(NULL, 404);
  }
}