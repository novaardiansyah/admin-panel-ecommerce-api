<?php
defined('BASEPATH') or exit('No direct script access allowed');
date_default_timezone_set('Asia/Jakarta');

use Dotenv\Dotenv;

class Server
{
	function __construct($config = array())
	{
		$dotenv = Dotenv::createImmutable(dirname(__FILE__, 3));
		$dotenv->load();

		$db['default'] = [
			'dsn'          => $_ENV['DB_DSN'],
			'hostname'     => $_ENV['DB_HOST'],
			'username'     => $_ENV['DB_USERNAME'],
			'password'     => $_ENV['DB_PASSWORD'],
			'database'     => $_ENV['DB_NAME'],
			'dbdriver'     => $_ENV['DB_DRIVER'],
			'dbprefix'     => '',
			'pconnect'     => FALSE,
			'db_debug'     => (ENVIRONMENT !== 'production'),
			'cache_on'     => FALSE,
			'cachedir'     => '',
			'char_set'     => 'utf8',
			'dbcollat'     => 'utf8_general_ci',
			'swap_pre'     => '',
			'encrypt'      => FALSE,
			'compress'     => FALSE,
			'stricton'     => FALSE,
			'failover'     => array(),
			'save_queries' => TRUE
		];

		require_once(__DIR__ . '/../libraries/oauth2/src/OAuth2/Autoloader.php');

		$config = $db['default'];

		OAuth2\Autoloader::register();
		$this->storage = new OAuth2\Storage\Pdo(array('dsn' => $config["dsn"], 'username' => $config["username"], 'password' => $config["password"]));
		$this->server = new OAuth2\Server($this->storage, array('allow_implicit' => true));
		$this->request = OAuth2\Request::createFromGlobals();
		$this->response = new OAuth2\Response();
	}

	/**
	 * client_credentials
	 */
	public function client_credentials()
	{
		$this->server->addGrantType(new OAuth2\GrantType\ClientCredentials($this->storage, array(
			"allow_credentials_in_request_body" => true
		)));
		$this->server->handleTokenRequest($this->request)->send();
	}

	/**
	 * password_credentials
	 */
	public function password_credentials()
	{
		$ci = get_instance();
		
		$users = $ci->db->query("SELECT a.username, a.password, a.first_name, a.last_name FROM oauth_users AS a")->result_array();

		$temp_users = [];

		foreach ($users as $key => $value) {
			$temp_users[$value['username']] = [
				'password'   => $value['password'],
				'first_name' => $value['first_name'],
				'last_name'  => $value['last_name']
			];
		}

		$storage = new OAuth2\Storage\Memory(array('user_credentials' => $temp_users));
		$this->server->addGrantType(new OAuth2\GrantType\UserCredentials($storage));
		$this->server->handleTokenRequest($this->request)->send();
	}

	/**
	 * refresh_token
	 */
	public function refresh_token()
	{
		$this->server->addGrantType(new OAuth2\GrantType\RefreshToken($this->storage, array(
			"always_issue_new_refresh_token" => true,
			"unset_refresh_token_after_use" => true,
			"refresh_token_lifetime" => 2419200,
		)));
		$this->server->handleTokenRequest($this->request)->send();
	}

	/**
	 * limit scope
	 */
	public function require_scope($scope = "")
	{
		if (!$this->server->verifyResourceRequest($this->request, $this->response, $scope)) {
			$this->server->getResponse()->send();
			die;
		}
	}

	public function check_client_id()
	{
		if (!$this->server->validateAuthorizeRequest($this->request, $this->response)) {
			$this->response->send();
			die;
		}
	}

	public function authorize($is_authorized)
	{
		$this->server->addGrantType(new OAuth2\GrantType\AuthorizationCode($this->storage));
		$this->server->handleAuthorizeRequest($this->request, $this->response, $is_authorized);
		if ($is_authorized) {
			$code = substr($this->response->getHttpHeader('Location'), strpos($this->response->getHttpHeader('Location'), 'code=') + 5, 40);
			header("Location: " . $this->response->getHttpHeader('Location'));
		}
		$this->response->send();
	}

	public function authorization_code()
	{
		$this->server->addGrantType(new OAuth2\GrantType\AuthorizationCode($this->storage));
		$this->server->handleTokenRequest($this->request)->send();
	}
}
