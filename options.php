<?php
	session_start();

	if($_SESSION["auth"]!="OK")
	{
		header("location: .");
		die();
	}
	
	include "./includes/tpl/options.php";
