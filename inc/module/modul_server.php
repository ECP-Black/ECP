<?php
if(defined('VERSION')) {
	update_server_cache();
	$db->query('SELECT `gamename`, `gametype`, `posi`, `response` FROM `'.DB_PRE.'ecp_server` WHERE aktiv = 1 AND displaymenu = 1 ORDER BY posi ASC');
	if($db->num_rows()) {
		while($server = $db->fetch_assoc()) {
			$data = unserialize($server['response']);
			$tpl = new smarty;
			if($data['b']['status'] == 0) {
				$tpl->assign('image', 'templates/'.DESIGN.'/images/map_no_response.jpg');
			} else if(file_exists('images/server/maps/'.$server['gametype'].'/'.$data['s']['game'].'/'.strtolower(str_replace(' ', '_',$data['s']['map'])).'.jpg')) {
				$tpl->assign('image', 'images/server/maps/'.$server['gametype'].'/'.$data['s']['game'].'/'.strtolower(str_replace(' ', '_',$data['s']['map'])).'.jpg');
			} else {
				$tpl->assign('image', 'templates/'.DESIGN.'/images/map_no_image.jpg');
			}	
			if(file_exists('images/server/icons/'.$server['gametype'].'/'.strtolower(str_replace(' ', '',$data['s']['game'])).'.gif')) {
				$tpl->assign('icon', 'images/server/icons/'.$server['gametype'].'/'.strtolower(str_replace(' ', '',$data['s']['game'])).'.gif');
			}		
			$tpl->assign('gamename', $server['gamename']);
			$tpl->assign('data', $data);
			$tpl->display(DESIGN.'/tpl/server/mini.html');
			echo '<br />';
		}	
	} else {
		echo NO_ENTRIES;
	}
} else {
	echo 'Kein direktes Aufrufen der Datei!';
}
?>