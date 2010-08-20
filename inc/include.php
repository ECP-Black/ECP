<?php
	require('smarty/Smarty.class.php');
	include_once('db.daten.php');
	include_once('classes.php');
	include_once('constant.php');	
	// Sprache prüfen
	include('templates/'.DESIGN.'/design.php');
	include('language/'.LANGUAGE.'.php');	
	require_once ('xmail/MAIL.php');	
	include_once('functions.php');
	include('lgsl_protocol.php');
?>