<?php
defined('BASEPATH') or exit('No direct script access allowed');

class M_Menus extends CI_Model
{
  public function __construct()
  {
    parent::__construct();
  }

  public function insert($data = [])
  {
    $send = [
      'name'      => getReqBody('name', '', $_POST),
      'url'       => getReqBody('url', '', $_POST),
      'icon'      => getReqBody('icon', '', $_POST),
      'isParent'  => getReqBody('isParent', 0, $_POST),
      'isActive'  => getReqBody('isActive', 1, $_POST),
      'isDeleted' => getReqBody('isDeleted', 0, $_POST),
      'createdBy' => getReqBody('createdBy', 1, $_POST)
    ];
    return $send;
    // var_dump(responseModelApi(['status' => true, 'message' => 'Store data success'], $send));
  }
}
