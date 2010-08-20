<?php
if(defined('VERSION')) {
	$tpls =new smarty();
	$db->query('SELECT uID FROM '.DB_PRE.'ecp_online WHERE lastklick > '.(time()-SHOW_USER_ONLINE));
	while($row1 = $db->fetch_assoc()) { 
		if($row1['uID'] == 0) {
			@$guest++;
		} else {
			@$users++;
		}
		@$gesamt++;
	}	
	$tpls->assign('gesamt', format_nr(@$gesamt));
	$tpls->assign('guests', format_nr(@$guest));
	$tpls->assign('users',  format_nr(@$users));
	$tpls->display(DESIGN.'/tpl/modul/user_online.html');
} else {
	echo 'Kein direktes Aufrufen der Datei!';
}
?>