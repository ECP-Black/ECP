<?php
function admin_fightus () {
	global $db;
	$tpl = new smarty;
	$db->query('SELECT tname, gamename, icon, matchtypename, a.homepage, `fightusID`, a.clanname, `wardatum`, `bearbeitet`, `vonID`, username FROM '.DB_PRE.'ecp_fightus as a LEFT JOIN '.DB_PRE.'ecp_teams ON (teamID = tID) LEFT JOIN '.DB_PRE.'ecp_wars_games ON (gID=gameID) LEFT JOIN '.DB_PRE.'ecp_wars_matchtype ON (mID= matchtypeID) LEFT JOIN '.DB_PRE.'ecp_user ON (ID=vonID) ORDER BY bearbeitet ASC, wardatum ASC');
	$fightus = array();
	while($row = $db->fetch_assoc()) {
		$row['wardatum'] = date(SHORT_DATE, $row['wardatum']);
		$fightus[] =$row;
	}
	$tpl->assign('fightus', $fightus);
	ob_start();
	$tpl->display(DESIGN.'/tpl/admin/fightus.html');
	$content = ob_get_contents();
	ob_end_clean();
	main_content(FIGHTUS, $content, '',1);
}
function admin_fightus_del($id) {
	global $db;
	$db->setMode(0);
	ob_end_clean();
	if(!isset($_SESSION['rights']['admin']['fightus']['del']) AND !isset($_SESSION['rights']['superadmin'])) {
		table(ERROR, NO_ADMIN_RIGHTS);
	} else {
		if($db->query('DELETE FROM '.DB_PRE.'ecp_fightus WHERE fightusID = '.$id)) {
			echo 'ok';
		}
	}
	die();
}
if (!isset($_SESSION['rights']['admin']['fightus']) AND !isset($_SESSION['rights']['superadmin'])) {
	table(ERROR, NO_ADMIN_RIGHTS);
} else {
	if(isset($_GET['func'])) {
		switch($_GET['func']) {
			case 'del':
				admin_fightus_del((int)$_GET['id']);
			break;			
			default:
				admin_fightus();
		}
	} else {
		admin_fightus();
	}
}
?>