<?php
function admin_games() {
	$tpl = new smarty();
	$tpl->assign('icons', get_icons());
	$tpl->assign('games', get_games());
	ob_start();
	$tpl->display(DESIGN.'/tpl/admin/games.html');
	$content = ob_get_contents();
	ob_end_clean();
	main_content(GAMES, $content, '',1);
}
function get_icons() {
	$files = scan_dir('images/games', true);
	$string = '<option value="0">'.CHOOSE.'</option>';
	foreach($files AS $value) {
		$string .= '<option value="'.$value.'">'.$value.'</option>';
	}
	return $string;
}
function admin_games_add() {
	ob_end_clean();
	global $db;
	$db->setMode(0);
	ajax_convert_array($_POST);
	if(@$_SESSION['rights']['admin']['clanwars']['games_add'] OR @$_SESSION['rights']['superadmin']) {	
		if($_POST['name'] == '' OR $_POST['icon'] == '' OR $_POST['short'] == '') {
			echo NOT_NEED_ALL_INPUTS;
		} else {
			$sql = sprintf('INSERT INTO '.DB_PRE.'ecp_wars_games (`gamename`, `gameshort`, `icon`, `fightus`) VALUES (\'%s\', \'%s\', \'%s\', %d)', strsave($_POST['name']), strsave($_POST['short']),strsave($_POST['icon']), (int)@$_POST['fightus']);
			if($db->query($sql)) {
				echo 'ok';
			}
		}
	} else  {
		echo html_ajax_convert(NO_ADMIN_RIGHTS);
	}
	die();
}
function admin_games_edit($id) {
	global $db;
	$db->setMode(0);
	ob_end_clean();
	ajax_convert_array($_POST);
	if(@$_SESSION['rights']['admin']['clanwars']['games_edit'] OR @$_SESSION['rights']['superadmin']) {		
		if($_POST['name'] == '' OR $_POST['icon'] == '' OR $_POST['short'] == '') {
			echo NOT_NEED_ALL_INPUTS;
		} else {
			$sql = sprintf('UPDATE '.DB_PRE.'ecp_wars_games SET `gamename` = \'%s\', `gameshort` = \'%s\', `icon` = \'%s\', `fightus` = %d  WHERE gameID = %d', strsave($_POST['name']), strsave($_POST['short']),strsave($_POST['icon']), (int)@$_POST['fightus'], $id);
			if($db->query($sql)) {
				echo 'ok';
				die();
			}
		}
	} else  {
		echo html_ajax_convert(NO_ADMIN_RIGHTS);
	}
	die();
}
function get_games() {
	global $db;
	$db->query('SELECT gamename, gameID, gameshort, icon FROM '.DB_PRE.'ecp_wars_games ORDER BY gamename');
	$games = array();
	while($row = $db->fetch_assoc()) {
		$games[] = $row;
	}
	$tpl = new smarty;
	$tpl->assign('games', $games);
	ob_start();
	$tpl->display(DESIGN.'/tpl/admin/games_overview.html');
	$content = ob_get_contents();
	ob_end_clean();
	return $content;
}
function get_maps() {
	global $db;
	$anzahl = $db->result(DB_PRE.'ecp_wars_locations', 'COUNT(locationID)', '1');
	$limit = get_sql_limit($anzahl,20);
	$db->query('SELECT locationname, locationID, gamename, icon FROM '.DB_PRE.'ecp_wars_locations LEFT JOIN '.DB_PRE.'ecp_wars_games ON gID = gameID ORDER BY gamename, locationname LIMIT '.$limit[1].', 20');
	$maps = array();
	while($row = $db->fetch_assoc()) {
		$maps[] = $row;
	}
	$tpl = new smarty;
	$tpl->assign('anzahl',$anzahl);
	$tpl->assign('maps', $maps);
	if($limit[0] > 1)
	$tpl->assign('seiten', makepagelink_ajax('#', 'return load_content(\'maps\', \'ajax_checks.php?func=admin&site=get_maps&page={nr}\');', @$_GET['page'], $limit[0]));
	ob_start();
	$tpl->display(DESIGN.'/tpl/admin/games_maps.html');
	$content = ob_get_contents();
	ob_end_clean();
	return $content;
}
function admin_games_maps() {
	$tpl = new smarty();
	$tpl->assign('games', get_games_form());
	$tpl->assign('maps', get_maps());
	ob_start();
	$tpl->display(DESIGN.'/tpl/admin/maps.html');
	$content = ob_get_contents();
	ob_end_clean();
	main_content(MAPS, $content, '',1);
}
function admin_games_map_add() {
	global $db;
	$db->setMode(0);
	ob_end_clean();	
	ajax_convert_array($_POST);
	if(@$_SESSION['rights']['admin']['clanwars']['maps_add'] OR @$_SESSION['rights']['superadmin']) {		
		if($_POST['name'] == '' OR !$_POST['gameid']) {
			echo NOT_NEED_ALL_INPUTS;
		} else {
			if($db->query(sprintf('INSERT INTO '.DB_PRE.'ecp_wars_locations (locationname, gID) VALUES (\'%s\', %d)', strsave($_POST['name']), $_POST['gameid']))) {
				echo 'ok';
			}
		}
	} else {
		echo html_ajax_convert(NO_ADMIN_RIGHTS);
	}
	die();
}
function admin_games_map_edit($id) {
	global $db;
	$db->setMode(0);
	ob_end_clean();	
	ajax_convert_array($_POST);
	if(@$_SESSION['rights']['admin']['clanwars']['maps_edit'] OR @$_SESSION['rights']['superadmin']) {	
		if($_POST['name'] == '' OR !$_POST['gameid']) {
			echo NOT_NEED_ALL_INPUTS;
		} else {
			if($db->query(sprintf('UPDATE '.DB_PRE.'ecp_wars_locations SET locationname = \'%s\', gID =  %d WHERE locationID = %d', strsave($_POST['name']), $_POST['gameid'], $id))) {
				echo 'ok';
			}
		}
	} else {
		echo html_ajax_convert(NO_ADMIN_RIGHTS);
	}
	die();
}
if (!isset($_SESSION['rights']['admin']['clanwars']) AND !isset($_SESSION['rights']['superadmin'])) {
	table(ERROR, NO_ADMIN_RIGHTS);
} else {
	if(isset($_GET['func'])) {
		switch($_GET['func']) {
			case 'add':
				admin_games_add();
				break;
			case 'edit':
				admin_games_edit((int)$_GET['id']);
				break;
			case 'del':
				admin_games_del((int)$_GET['id']);
				break;
			case 'maps':
				admin_games_maps();
				break;
			case 'addmap':
				admin_games_map_add();
				break;
			case 'editmap':
				admin_games_map_edit((int)$_GET['id']);
				break;
			default:
				admin_games();
		}
	} else {
		admin_games();
	}
}
?>