<?php
if(defined('VERSION')) {
	if(@$_SESSION['rights']['public']['calendar']['view'] OR @$_SESSION['rights']['superadmin']) {
		echo '<div id="calendar_mini">'.calendar_mini().'</div>';
	} else {
		echo NO_ACCESS_RIGHTS;
	}
} else {
	echo 'Kein direktes Aufrufen der Datei!';
}
?>