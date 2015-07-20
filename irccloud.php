<?php
/*
 * irccloud-247
 * @author Weidi Zhang
 * @license CC BY-NC-SA 4.0 (SEE: LICENSE file)
 */

class IRCCloud247
{
	private $user;
	private $pass;
	
	private $session;

	public function __construct($user, $pass) {
		$this->user = $user;
		$this->pass = $pass;
	}
	
	public function login() {
		$this->_log("Getting auth form token");
		$getToken = $this->_fetch("https://www.irccloud.com/chat/auth-formtoken", array("content-length: 0"), true);
		$getToken = json_decode($getToken, true);
		if ($getToken["success"]) {
			$token = $getToken["token"];
			$this->_log("Form token = " . $token);
			
			$getSession = $this->_fetch("https://www.irccloud.com/chat/login", array(
				"content-type: application/x-www-form-urlencoded",
				"x-auth-formtoken: " . $token
			), true, array(
				"email" => $this->user,
				"password" => $this->pass,
				"token" => $token
			));			
			$getSession = json_decode($getSession, true);
			
			if ($getSession["success"]) {
				$session = $getSession["session"];
				$this->_log("Login session = " . $session);
				
				$this->session = $session;
			}
			else {
				$this->_log("success = false when logging in");
			}
		}
		else {
			$this->_log("success = false when getting auth form token");
		}
	}
	
	public function run() {
		if ($this->session) {
			$this->_log("Starting to keep connection to irccloud alive");
			
			while (true) {
				$this->_log("Fetching stream");
				$stream = $this->_fetch("https://www.irccloud.com/chat/stream", array(
					"Connection: keep-alive",
					"Accept-Encoding: gzip,deflate,sdch",
					"User-Agent: Mozilla/5.0 (Windows NT 6.1; WOW64; rv:38.0) Gecko/20100101 Firefox/38.0",
					"Cookie: session=" . $this->session,
					"Host: www.irccloud.com"
				));
				$this->_log("Sleeping 10 minutes");
				sleep(10 * 60);
			}
		}
		else {
			$this->_log("Not logged into irccloud, cannot run");
		}
	}
	
	private function _log($msg) {
		echo "[" . date("m/d/Y h:i:s A") . "] " . $msg . "\n";
	}
	
	private function _fetch($url, $headers = array(), $shouldPost = false, $post = array()) {
		$ch = curl_init($url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($ch, CURLOPT_TIMEOUT, 15);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
		
		if ($shouldPost) {
			curl_setopt($ch, CURLOPT_POST, true);
			curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post));
		}
		
		if (count($headers) > 0) {
			curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		}
		
		$data = curl_exec($ch);
		return $data;
	}
}

$user = "your@email.org";
$pass = "hunter2";

$irccloud = new IRCCloud247($user, $pass);
$irccloud->login();
$irccloud->run();
?>