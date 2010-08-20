<?php
function admin_links() {
	global $db;
	$tpl = new smarty;
	ob_start();
	$tpl->display(DESIGN.'/tpl/admin/links.html');
	$content = ob_get_contents();
	ob_end_clean();
	main_content(SERVER, $content, '',1);
	get_links();
}
function get_links() {
	global $db;
	if(!isset($_SESSION['rights']['admin']['links']) AND !isset($_SESSION['rights']['superadmin'])) {
		echo NO_ADMIN_RIGHTS;
	} else {
		$tpl = new smarty;
		if(@$_GET['ajax']) ob_end_clean();
		$links = array();
		$result = $db->query('SELECT `linkID`, `name`, `url`, `bannerurl`, `beschreibung`, `hits` FROM '.DB_PRE.'ecp_links ORDER BY name ASC');
		while($row = mysql_fetch_assoc($result)) {
			$links[] = $row;
		}	
		$tpl->assign('links', $links);
		ob_start();
		$tpl->display(DESIGN.'/tpl/admin/links_overview.html');
		$content = ob_get_contents();
		ob_end_clean();		
		if(@$_GET['ajax']) { echo html_ajax_convert($content); die(); } 
		main_content(OVERVIEW, '<div id="links_overview">'.$content.'</div>', '',1);	
	}
}
function admin_links_add() {
	ob_end_clean();
	global $db;
	if(!isset($_SESSION['rights']['admin']['links']['add']) AND !isset($_SESSION['rights']['superadmin'])) {
		echo NO_ADMIN_RIGHTS;
	} else {
		if($_POST['name'] == '' OR $_POST['url'] == '') {
			echo NOT_NEED_ALL_INPUTS;
		} else {
			ajax_convert_array($_POST);
			$db->setMode(0);
			$sql = sprintf('INSERT INTO '.DB_PRE.'ecp_links (`name`, `url`, `bannerurl`, `beschreibung`, `hits`, eingetragen) 
							VALUES (\'%s\', \'%s\', \'%s\', \'%s\', %d, %d)',
							strsave($_POST['name']),strsave(check_url($_POST['url'])), strsave(check_url($_POST['bannerurl'])), strsave($_POST['beschreibung']), (int)$_POST['hits'], time());
			if($db->query($sql)) {
				echo 'ok';
			}
		}
	}
	die();
}
function admin_links_edit($id) {
	ob_end_clean();
	global $db;
	if(!isset($_SESSION['rights']['admin']['links']['edit']) AND !isset($_SESSION['rights']['superadmin'])) {
		echo NO_ADMIN_RIGHTS;
	} else {
		if($_POST['name'] == '' OR $_POST['url'] == '') {
			echo NOT_NEED_ALL_INPUTS;
		} else {
			$db->setMode(0);
			ajax_convert_array($_POST);	
			$sql = sprintf('UPDATE '.DB_PRE.'ecp_links SET `name` = \'%s\', `url` = \'%s\', `bannerurl` = \'%s\', `beschreibung` = \'%s\', `hits` = %d WHERE linkID = %d',
							strsave($_POST['name']),strsave(check_url($_POST['url'])), strsave(check_url($_POST['bannerurl'])), strsave($_POST['beschreibung']), (int)$_POST['hits'], $id);
			if($db->query($sql)) {
				echo 'ok';	
			}
		}
	}
	die();
}
if (!isset($_SESSION['rights']['admin']['links']) AND !isset($_SESSION['rights']['superadmin'])) {
	table(ERROR, NO_ADMIN_RIGHTS);
} else {
	if(isset($_GET['func'])) {
		switch($_GET['func']) {
			case 'add':
				admin_links_add();
				break;
			case 'edit':
				admin_links_edit((int)$_GET['id']);
				break;									
			default:
				admin_links();
		}
	} else {
		admin_links();
	}
}
?>