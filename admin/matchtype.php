<?php
function admin_matchtype() {
	$tpl = new smarty();
	$tpl->assign('matchtype', get_matchtypes());
	ob_start();
	$tpl->display(DESIGN.'/tpl/admin/matchtype.html');
	$content = ob_get_contents();
	ob_end_clean();
	main_content(MATCHTYPE, $content, '',1);
}
function admin_matchtype_add() {
	ob_end_clean();
	global $db;
	$db->setMode(0);
	ajax_convert_array($_POST);
	if(@$_SESSION['rights']['admin']['clanwars']['matchtype_add'] OR @$_SESSION['rights']['superadmin']) {
		if($_POST['name'] == '') {
			echo NOT_NEED_ALL_INPUTS;
		} else {
			$sql = sprintf('INSERT INTO '.DB_PRE.'ecp_wars_matchtype (`matchtypename`, `fightus`) VALUES (\'%s\', %d)', strsave($_POST['name']), (int)@$_POST['fightus']);
			if($db->query($sql)) {
				echo 'ok';
			}
		}
	} else {
		echo html_ajax_convert(NO_ADMIN_RIGHTS);
	}
	die();
}
function admin_matchtype_edit($id) {
	global $db;
	$db->setMode(0);
	ob_end_clean();
	ajax_convert_array($_POST);
	if(@$_SESSION['rights']['admin']['clanwars']['matchtype_edit'] OR @$_SESSION['rights']['superadmin']) {
		if($_POST['name'] == '') {
			echo NOT_NEED_ALL_INPUTS;
		} else {
			$sql = sprintf('UPDATE '.DB_PRE.'ecp_wars_matchtype SET `matchtypename` = \'%s\', `fightus` = %d WHERE matchtypeID= %d', strsave($_POST['name']), (int)@$_POST['fightus'], $id);
			if($db->query($sql)) {
				echo 'ok';
			}
		}
	} else {
		echo html_ajax_convert(NO_ADMIN_RIGHTS);
	}
	die();
}
function get_matchtypes() {
	global $db;
	$db->query('SELECT matchtypeID, matchtypename, fightus FROM '.DB_PRE.'ecp_wars_matchtype ORDER BY matchtypename');
	$match = array();
	while($row = $db->fetch_assoc()) {
		$match[] = $row;
	}
	$tpl = new smarty;
	$tpl->assign('matchtype', $match);
	ob_start();
	$tpl->display(DESIGN.'/tpl/admin/matchtype_overview.html');
	$content = ob_get_contents();
	ob_end_clean();
	return $content;
}
if (!isset($_SESSION['rights']['admin']['clanwars']) AND !isset($_SESSION['rights']['superadmin'])) {
	table(ERROR, NO_ADMIN_RIGHTS);
} else {
	if(isset($_GET['func'])) {
		switch($_GET['func']) {
			case 'add':
				admin_matchtype_add();
				break;
			case 'edit':
				admin_matchtype_edit((int)$_GET['id']);
				break;
			case 'del':
				admin_matchtype_del((int)$_GET['id']);
				break;
		}
	} else {
		admin_matchtype();
	}
}
?>