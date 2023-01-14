<?php
defined('BASEPATH') or exit('No direct script access allowed');

class M_Users extends CI_Model
{
  public function get_all()
  {
    return $this->db->get('country')->result_array();
  }

  public function insert()
  {
    $password = getReqBody('password');
    $queue    = random_tokens(10, null, true);

    if ($password) {
      $password = password_hash($password, PASSWORD_DEFAULT);
    }

    $data = [
      'queue'     => $queue,
      'username'  => getReqBody('username'),
      'password'  => $password,
      'roleId'    => getReqBody('roleId'),
      'companyId' => getReqBody('companyId'),
      'name'      => getReqBody('name'),
      'email'     => getReqBody('email'),
      'phone'     => getReqBody('phone'),
      'address'   => getReqBody('address'),
      'isActive'  => 0,
      'isDeleted' => 0
    ];

    $this->db->insert('users', $data);
    
    $user = [];
    if ($this->db->affected_rows() > 0) {
      $user = $this->db->query("SELECT a.id, a.roleId, a.companyId, a.name, a.username, a.email, a.phone, a.address FROM users AS a WHERE a.queue = ?", [$queue])->row();
    }

    if (!(empty($user))) {
      $response = [
        'id'        => $user->id,
        'username'  => $user->username,
        'roleId'    => $user->roleId,
        'companyId' => $user->companyId,
        'name'      => $user->name,
        'email'     => $user->email,
        'phone'     => $user->phone,
        'address'   => $user->address
      ];

      return [
        'status'      => true,
        'status_code' => 200,
        'message'     => 'Insert success',
        'data'        => $response
      ];
    }

    return [
      'status'      => false,
      'status_code' => 401,
      'message'     => 'Insert failed',
      'data'        => null
    ];
  }
}
