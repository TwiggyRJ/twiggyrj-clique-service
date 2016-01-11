<?php
	
	include_once 'includes/base.php';
	$base = new NTD_Base();
	
	$header = isset($_REQUEST["action"]) ? $_REQUEST["action"]:"get";
	
	switch($header)
	{
		case "get":
			if($_SERVER['REQUEST_METHOD']=='GET')
			{
				if ($_GET['search'] == "all")
				{
					$search = $base->search_feed($_GET['search'], "all");
				}
				elseif ($_GET['search'] == "Bar" || $_GET['search'] == "Cafe" || $_GET['search'] == "cafe" || $_GET['search'] == "Café" || $_GET['search'] == "Karoake" || $_GET['search'] == "Karoake Bar" || $_GET['search'] == "Pub" || $_GET['search'] == "Restaurant")
				{
					$search = $base->search_feed($_GET['search'], "type");
				}
				elseif (is_numeric($_GET['search']))
				{
					$search = $base->search_feed_reviews($_GET['search'], "id");
				}
				else
				{
					$search = $base->search_feed($_GET['search'], "name");
				}
			}
			else
			{
				header("HTTP/1.1 400 Bad Request");
			}
			break;
	}
?>