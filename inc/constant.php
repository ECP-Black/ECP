<?php
	// Sprache auswählen
	if(isset($_SESSION['lang'])) {
		if(file_exists('inc/language/'.$_SESSION['lang'].'.php')) {
			define('LANGUAGE', $_SESSION['lang']);
		} 
	} elseif (isset($_COOKIE['lang'])) {
		if(file_exists('inc/language/'.$_COOKIE['lang'].'.php')) {
			define('LANGUAGE', $_COOKIE['lang']);
		}
	} 
    // Datenbank Objekt anlegen
    $db = new db();
	// Alle Einstellungen der Seite aus der Datenbank holen und als Konstanten definieren.
	$db->query('SELECT * FROM '.DB_PRE.'ecp_settings');
	foreach ($db->fetch_assoc() as $key => $value) {
		if($key == 'LANGUAGE') define('DEFAULT_LANG', $value);
		@define($key, $value);	
	}
	define('CHMOD', 0775);
  	
?>