<?php
	include_once 'includes/base.php';
	$base = new NTD_Base();
	$id = "id";
	if (isset($_GET['search_id'])){
		$search = $base->search_feed($_GET['search_id'], $id);
	}
?>