<?php
function admin_teamspeak() {
	global $db;
	$tpl = new smarty;
	ob_start();
	$tpl->display(DESIGN.'/tpl/admin/teamspeak.html');
	$content = ob_get_contents();
	ob_end_clean();
	main_content(VOICE_TOOL, $content, '',1);
	get_server();
}
function get_server() {
	global $db;
	if(!isset($_SESSION['rights']['admin']['teamspeak']) AND !isset($_SESSION['rights']['superadmin'])) {
		echo NO_ADMIN_RIGHTS;
	} else {
		$tpl = new smarty;
		if(@$_GET['ajax']) ob_end_clean();
		$server = array();
		$result = $db->query('SELECT tsID, ip, port, qport, response, serverart, posi, aktiv FROM '.DB_PRE.'ecp_teamspeak ORDER BY posi ASC');
		while($row = mysql_fetch_assoc($result)) {
			$server[] = $row;
		}	
		$tpl->assign('server', $server);
		ob_start();
		$tpl->display(DESIGN.'/tpl/admin/teamspeak_overview.html');
		$content = ob_get_contents();
		ob_end_clean();		
		if(@$_GET['ajax']) { echo html_ajax_convert($content); die(); } 
		main_content(OVERVIEW, '<div id="server_overview">'.$content.'</div>', '',1);	
	}
}
function admin_teamspeak_add() {
	ob_end_clean();
	global $db;
	ajax_convert_array($_POST);
	if(!isset($_SESSION['rights']['admin']['teamspeak']['add']) AND !isset($_SESSION['rights']['superadmin'])) {
		echo NO_ADMIN_RIGHTS;
	} else {
		if($_POST['ip'] == '' OR $_POST['port'] == '' OR $_POST['serverart'] == '') {
			echo NOT_NEED_ALL_INPUTS;
		} else {
			$db->setMode(0);
			$lang = array();
			$sql = sprintf('INSERT INTO '.DB_PRE.'ecp_teamspeak (`ip`, `port`, `qport`, `serverart`) 
							VALUES (\'%s\', %d, %d, %d)',
							strsave(@$_POST['ip']), (int)$_POST['port'], (int)$_POST['qport'], (int)$_POST['serverart']);
			if($db->query($sql)) {
				echo 'ok';
			}
		}
	}
	die();
}
function admin_teamspeak_edit($id) {
	ob_end_clean();
	global $db;
	ajax_convert_array($_POST);
	if(!isset($_SESSION['rights']['admin']['teamspeak']['edit']) AND !isset($_SESSION['rights']['superadmin'])) {
		echo NO_ADMIN_RIGHTS;
	} else {
		if($_POST['ip'] == '' OR $_POST['port'] == '' OR $_POST['serverart'] == '') {
			echo NOT_NEED_ALL_INPUTS;
		} else {
			$db->setMode(0);				
			$sql = sprintf('UPDATE '.DB_PRE.'ecp_teamspeak SET `ip` = "%s", `port` = %d, `qport` = %d, `serverart` = %d WHERE tsID = %d',
							strsave(@$_POST['ip']), (int)$_POST['port'], (int)$_POST['qport'], (int)$_POST['serverart'], $id);
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
	$aktiv = $db->result(DB_PRE.'ecp_teamspeak', 'aktiv', 'tsID = '.$id);
	if($aktiv == 0) $aktiv = 1; else $aktiv = 0;
	if($db->query('UPDATE '.DB_PRE.'ecp_teamspeak SET aktiv = '.$aktiv.' WHERE tsID = '.$id)) {
		$_GET['ajax'] = 1;
		get_server();
	}
}
function admin_teamspeak_del($id) {
	ob_end_clean();
	global $db;
	$db->setMode(0);
	if(!isset($_SESSION['rights']['admin']['teamspeak']['del']) AND !isset($_SESSION['rights']['superadmin'])) {
		echo NO_ADMIN_RIGHTS;
	} else {
		if($db->query('DELETE FROM '.DB_PRE.'ecp_teamspeak WHERE tsID = '.$id)) {				
			echo 'ok';	
		}			
	}
	die();
}
if (!isset($_SESSION['rights']['admin']['teamspeak']) AND !isset($_SESSION['rights']['superadmin'])) {
	table(ERROR, NO_ADMIN_RIGHTS);
} else {
	if(isset($_GET['func'])) {
		switch($_GET['func']) {
			case 'add':
				admin_teamspeak_add();
				break;
			case 'edit':
				admin_teamspeak_edit((int)$_GET['id']);
				break;
			case 'del':
				admin_teamspeak_del((int)$_GET['id']);
				break;
			case 'get_server':
				get_server();
				break;	
			case 'switch_aktiv':
				server_switch_aktiv((int)$_GET['id']);
			break;													
			default:
				admin_teamspeak();
		}
	} else {
		admin_teamspeak();
	}
}
?>