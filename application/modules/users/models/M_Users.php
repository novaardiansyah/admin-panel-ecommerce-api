<?php
defined('BASEPATH') or exit('No direct script access allowed');

class M_Users extends CI_Model
{
  public function __construct()
  {
    parent::__construct();
  }

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

  public function login()
  {
    $clientId     = getReqBody('clientId', '', $_POST);
    $clientSecret = getReqBody('clientSecret', '', $_POST);

    $username   = getReqBody('username', '', $_POST);
    $password   = getReqBody('password', '', $_POST);
    $rememberMe = getReqBody('rememberMe', '', $_POST);

    $user = $this->db->query("SELECT a.id, a.roleId, a.companyId, a.name, a.username, a.email, a.phone, a.address, a.password, a.isActive, a.isDeleted FROM users AS a WHERE a.username = ?", [$username])->row();

    if (empty($user)) return responseModelApi(['status' => false, 'message' => 'Username or password is wrong.']);

    if (!password_verify($password, $user->password)) return responseModelApi(['status' => false, 'message' => 'Username or password is wrong.']);
    
    if ($user->isActive == 0) return responseModelApi(['status' => false, 'message' => 'This account has not been activated.']);
    
    if ($user->isDeleted == 1) return responseModelApi(['status' => false, 'message' => 'This account has been deleted.']);

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

    $credential = $this->password_credentials(['clientId' => $clientId, 'clientSecret' => $clientSecret]);
    $credential = arrayToObject($credential);
    
    if ($credential->status == false) return $credential;

    $response['token'] = $credential->data;

    return responseModelApi(['status' => true, 'message' => 'Login Successfully, Please wait...'], $response);
  }

  private function password_credentials($data = [])
  {
    $clientId     = decode(getReqBody('clientId', '', $data));
    $clientSecret = decode(getReqBody('clientSecret', '', $data));

    $oauth_user = $this->db->query("SELECT a.username, a.password, a.first_name, a.last_name, b.client_id, b.client_secret, b.grant_types,b.scope FROM oauth_users AS a 
      INNER JOIN oauth_clients AS b ON a.username = b.user_id
    WHERE b.client_id = ? AND b.client_secret = ?", [$clientId, $clientSecret])->row();

    if (empty($oauth_user)) return arrayToObject(['status' => false, 'status_code' => 401, 'message' => 'Client ID or Client Secret is wrong.', 'data' => null]);

    $req = [
      'grant_type'    => 'password',
      'username'      => $oauth_user->username,
      'password'      => $oauth_user->password,
      'client_id'     => $oauth_user->client_id,
      'client_secret' => $oauth_user->client_secret,
      'scope'         => $oauth_user->scope
    ];

    $req = requestApi(base_url('oauth2/PasswordCredentials'), 'POST', $req);
    $req = arrayToObject($req);

    if (!isset($req->access_token)) return ['status' => false, 'status_code' => 401, 'message' => 'Client ID or Client Secret is wrong.', 'data' => null];

    return ['status' => true, 'status_code' => 200, 'message' => 'Token generated successfully.', 'data' => $req];
  }

  public function store_server_log()
  {
    $log_ip_address = getReqBody('log_ip_address', [], $_POST);
    $log_user_agent = getReqBody('log_user_agent', [], $_POST);
    $log_platform   = getReqBody('log_platform', [], $_POST);
    $log_data       = getReqBody('log_data', [], $_POST);

    $alreadyStored = [];

    foreach ($log_data as $key => $value) {
      $value = arrayToObject($value);

      $data = [
        'ip_address'   => $log_ip_address,
        'user_agent'   => $log_user_agent,
        'platform'     => $log_platform,
        'OccurringUrl' => $value->url,
        'OccurringAt'  => $value->entryAt,
        'response'     => json_encode($value->error)
      ];

      $this->db->insert('server_logs', $data);
      array_push($alreadyStored, $data);
    }

    return ['status' => true, 'status_code' => 200, 'message' => 'Log stored successfully.', 'data' => $alreadyStored];
  }
}
