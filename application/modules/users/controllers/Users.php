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
    $this->load->model('M_Users', 'api');
  }

  public function index_get()
  {
    $this->server->require_scope("country");
    $country = $this->api->get_all();
    $country ? $this->response($country, 200) : $this->response(NULL, 404);
  }

  public function index_post()
  {
    $this->server->require_scope("admin");
    $res = $this->api->insert();
    $res ? $this->response($res, 200) : $this->response(NULL, 404);
  }

  public function login_post()
  {
    $res = $this->api->login();
    $res ? $this->response($res, 200) : $this->response(NULL, 404);
  }

  public function store_server_log_post()
  {
    $this->server->require_scope("admin");
    $res = $this->api->store_server_log();
    $res ? $this->response($res, 200) : $this->response(NULL, 404);
  }
}