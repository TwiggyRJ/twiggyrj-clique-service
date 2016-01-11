<?php

	include_once 'includes/base.php';
	$base = new User();
	$header = isset($_REQUEST["action"]) ? $_REQUEST["action"]:"get";
	
	switch($header)
	{
		case "post":
			if($_SERVER['REQUEST_METHOD']=='POST')
			{
				if (isset($_SERVER['PHP_AUTH_USER']) && isset($_SERVER['PHP_AUTH_PW']) && isset($_POST['name']))
				{
					$user = $_SERVER["PHP_AUTH_USER"];
					$pass = $_SERVER["PHP_AUTH_PW"];
					$userdata = $base->reg_user($user, $pass, $_POST['name']);
				}else
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