<?php 
defined('BASEPATH') or exit('No direct script access allowed');

require(APPPATH . 'libraries/REST_Controller.php');

class Users extends REST_Controller
{
  public function __construct()
  {
    @session_start();
    parent::__construct();
    $this->load->library("Server", "server");
    $this->server->require_scope("country");
    $this->load->model('M_Users', 'api');
  }

  public function index_get()
  {
    $country = $this->api->get_all();
    $country ? $this->response($country, 200) : $this->response(NULL, 404);
  }

  public function index_post()
  {
    $res = $this->api->insert();
    $res ? $this->response($res, 200) : $this->response(NULL, 404);
  }
}