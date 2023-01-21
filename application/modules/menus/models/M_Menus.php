<?php
defined('BASEPATH') or exit('No direct script access allowed');

class M_Menus extends CI_Model
{
  function index_get()
  {
    $res = $this->db->query("SELECT a.id, a.name, a.url, a.icon, a.isParent, a.createdAt FROM menus AS a ORDER BY a.id DESC")->result();

    if (empty($res)) return responseModelApi(['status' => false, 'message' => 'Data not found']);
    return responseModelApi(['status' => true, 'message' => 'Data found'], $res);
  }
}
