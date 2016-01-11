<?php
	include_once 'includes/base.php';
	$base = new User();
	
	$header = isset($_REQUEST["action"]) ? $_REQUEST["action"]:"get"; 
	
	switch($header)
	{
		
		case "get":
			
			if($_SERVER['REQUEST_METHOD']=='GET')
			{
				if (isset($_SERVER['PHP_AUTH_USER']) && isset($_SERVER['PHP_AUTH_PW']))
				{
					$user = $_SERVER["PHP_AUTH_USER"];
					$pass = $_SERVER["PHP_AUTH_PW"];
					$userdata = $base->login_user($user, $pass);
				}
				else
				{
					header("HTTP/1.1 400 Bad Request");
				}
			}
			else
			{
				header("HTTP/1.1 400 Bad Request");
			}
			break;
			
	}	
?>