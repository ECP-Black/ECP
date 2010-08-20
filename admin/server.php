<?php
function admin_server() {
	global $db;
	$tpl = new smarty;
	ob_start();
	$tpl->assign('games', lgsl_type_list());
	$tpl->display(DESIGN.'/tpl/admin/server.html');
	$content = ob_get_contents();
	ob_end_clean();
	main_content(SERVER, $content, '',1);
	get_server();
}
function get_server() {
	global $db;
	if(!isset($_SESSION['rights']['admin']['server']) AND !isset($_SESSION['rights']['superadmin'])) {
		echo NO_ADMIN_RIGHTS;
	} else {
		$tpl = new smarty;
		if(@$_GET['ajax']) ob_end_clean();
		$server = array();
		$result = $db->query('SELECT `serverID`, `gamename`, response, `gametype`, `aktiv`, `displaymenu`, `ip`, `port`, `queryport`, `stat` FROM '.DB_PRE.'ecp_server ORDER BY posi ASC');
		while($row = mysql_fetch_assoc($result)) {
			$data = unserialize($row['response']);
			$row['path'] = 'images/server/maps/'.$row['gametype'].'/'.$data['s']['game'].'/'.$data['s']['map'].'.jpg';
			$server[] = $row;
		}	
		$tpl->assign('server', $server);
		ob_start();
		$tpl->display(DESIGN.'/tpl/admin/server_overview.html');
		$content = ob_get_contents();
		ob_end_clean();		
		if(@$_GET['ajax']) { echo html_ajax_convert($content); die(); } 
		main_content(OVERVIEW, '<div id="server_overview">'.$content.'</div>', '',1);	
	}
}
function admin_server_add() {
	ob_end_clean();
	global $db;
	ajax_convert_array($_POST);
	if(!isset($_SESSION['rights']['admin']['server']['add']) AND !isset($_SESSION['rights']['superadmin'])) {
		echo NO_ADMIN_RIGHTS;
	} else {
		if($_POST['ip'] == '' OR $_POST['port'] == '' OR $_POST['gametype'] == '') {
			echo NOT_NEED_ALL_INPUTS;
		} else {
			$db->setMode(0);
			$lang = array();
			list($_POST['port'], $_POST['queryport'], $_POST['sport']) = lgsl_port_conversion($_POST['gametype'],  $_POST['port'], $_POST['queryport'], $_POST['sport']);
			$sql = sprintf('INSERT INTO '.DB_PRE.'ecp_server (`gamename`, `gametype`, `passwort`, `aktiv`, `displaymenu`, `ip`, `port`, `queryport`, `sport`, `stat`) 
							VALUES (\'%s\', \'%s\', \'%s\', %d, %d, \'%s\', %d, %d, %d, %d)',
							strsave(@$_POST['gamename']),strsave($_POST['gametype']), strsave(@$_POST['passwort']), 1, (int)@$_POST['displaymenu'], strsave($_POST['ip']), (int)$_POST['port'], ((int)$_POST['queryport'] == 0 ? (int)$_POST['port'] : (int)$_POST['queryport']), (int)$_POST['sport'], (int)@$_POST['stat']);
			if($db->query($sql)) {
				echo 'ok';
			}
		}
	}
	die();
}
function admin_server_edit($id) {
	ob_end_clean();
	global $db;
	ajax_convert_array($_POST);
	if(!isset($_SESSION['rights']['admin']['server']['edit']) AND !isset($_SESSION['rights']['superadmin'])) {
		echo NO_ADMIN_RIGHTS;
	} else {
		if($_POST['ip'] == '' OR $_POST['port'] == '' OR $_POST['gametype'] == '') {
			echo NOT_NEED_ALL_INPUTS;
		} else {
			$db->setMode(0);	
			list($_POST['port'], $_POST['queryport'], $_POST['sport']) = lgsl_port_conversion($_POST['gametype'],  $_POST['port'], $_POST['queryport'], $_POST['sport']);				
			$sql = sprintf('UPDATE '.DB_PRE.'ecp_server SET `gamename` = \'%s\', `gametype` = \'%s\', `passwort` = \'%s\', `displaymenu` = %d, `ip` = \'%s\', `port` = %d, `queryport` = %d, `sport` = %d, `stat` = %d WHERE serverID = %d',
							strsave(@$_POST['gamename']),strsave($_POST['gametype']), strsave(@$_POST['passwort']), (int)@$_POST['displaymenu'], strsave($_POST['ip']), (int)$_POST['port'], ((int)$_POST['queryport'] == 0 ? (int)$_POST['port'] : (int)$_POST['queryport']), (int)$_POST['sport'], (int)@$_POST['stat'], $id);
			if($db->query($sql)) {
				echo 'ok';
			}
		}
	}
	die();
}
function server_switch_aktiv($id) {
	global $db;
	$db->setMode(0);
	$aktiv = $db->result(DB_PRE.'ecp_server', 'aktiv', 'serverID = '.$id);
	if($aktiv == 0) $aktiv = 1; else $aktiv = 0;
	if($db->query('UPDATE '.DB_PRE.'ecp_server SET aktiv = '.$aktiv.' WHERE serverID = '.$id)) {
		$_GET['ajax'] = 1;
		get_server();
	}
}
function server_switch_display($id) {
	global $db;
	$db->setMode(0);
	$aktiv = $db->result(DB_PRE.'ecp_server', 'displaymenu', 'serverID = '.$id);
	if($aktiv == 0) $aktiv = 1; else $aktiv = 0;
	if($db->query('UPDATE '.DB_PRE.'ecp_server SET displaymenu = '.$aktiv.' WHERE serverID = '.$id)) {
		$_GET['ajax'] = 1;
		get_server();
	}
}
function server_switch_stat($id) {
	global $db;
	$db->setMode(0);
	$aktiv = $db->result(DB_PRE.'ecp_server', 'stat', 'serverID = '.$id);
	if($aktiv == 0) $aktiv = 1; else $aktiv = 0;
	if($db->query('UPDATE '.DB_PRE.'ecp_server SET stat = '.$aktiv.' WHERE serverID = '.$id)) {
		$_GET['ajax'] = 1;
		get_server();
	}
}
function admin_server_del($id) {
	ob_end_clean();
	global $db;
	$db->setMode(0);
	if(!isset($_SESSION['rights']['admin']['server']['del']) AND !isset($_SESSION['rights']['superadmin'])) {
		echo NO_ADMIN_RIGHTS;
	} else {
		if($db->query('DELETE FROM '.DB_PRE.'ecp_server WHERE serverID = '.$id) AND $db->query('DELETE FROM '.DB_PRE.'ecp_server_stats WHERE sID = '.$id)) {				
			echo 'ok';	
		}			
	}
	die();
}
if (!isset($_SESSION['rights']['admin']['server']) AND !isset($_SESSION['rights']['superadmin'])) {
	table(ERROR, NO_ADMIN_RIGHTS);
} else {
	if(isset($_GET['func'])) {
		switch($_GET['func']) {
			case 'add':
				admin_server_add();
				break;
			case 'edit':
				admin_server_edit((int)$_GET['id']);
				break;
			case 'del':
				admin_server_del((int)$_GET['id']);
				break;
			case 'get_server':
				get_server();
				break;	
			case 'switch_aktiv':
				server_switch_aktiv((int)$_GET['id']);
			break;	
			case 'switch_display':
				server_switch_display((int)$_GET['id']);
			break;	
			case 'switch_stat':
				server_switch_stat((int)$_GET['id']);
			break;													
			default:
				admin_server();
		}
	} else {
		admin_server();
	}
}
?>