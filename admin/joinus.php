<?php
function admin_joinus () {
	global $db, $countries;
	$tpl = new smarty;
	$db->query('SELECT tname, `joinID`, `name`, b.username, b.email, b.icq, b.msn, `age`, b.country, `teamID`, `comment`, `IP`, `datum`, `closed`, `closedby`, a.username as closedby_username FROM '.DB_PRE.'ecp_joinus as b LEFT JOIN '.DB_PRE.'ecp_teams ON (teamID = tID) LEFT JOIN '.DB_PRE.'ecp_user as a ON (ID=closedby) ORDER BY closed ASC, datum ASC');
	$joinus = array();
	while($row = $db->fetch_assoc()) {
		$row['datum'] = date(SHORT_DATE, $row['datum']);
		if($row['joinID'] == (int)@$_GET['id']) $spe = $row;
		$joinus[] =$row;
	}
	if(@$spe) {
		ob_start();
		$tpl1 = new Smarty();
		foreach($spe AS $key=>$value) {
			$tpl1->assign($key, $value);
		}
		$tpl1->assign('countryname', $countries[$spe['country']]);
		$tpl1->assign('id', $row['joinID']);
		$tpl1->display(DESIGN.'/tpl/admin/joinus_view.html');
		$tpl->assign('details', ob_get_contents());
		ob_end_clean();
	}
	$tpl->assign('joinus', $joinus);
	ob_start();
	$tpl->display(DESIGN.'/tpl/admin/joinus.html');
	$content = ob_get_contents();
	ob_end_clean();
	main_content(JOINUS, $content, '',1);
}
function admin_joinus_del($id) {
	global $db;
	$db->setMode(0);
	ob_end_clean();
	if(!isset($_SESSION['rights']['admin']['joinus']['del']) AND !isset($_SESSION['rights']['superadmin'])) {
		table(ERROR, NO_ADMIN_RIGHTS);
	} else {
		if($db->query('DELETE FROM '.DB_PRE.'ecp_joinus WHERE joinID = '.$id)) {
			echo 'ok';
		}
	}
	die();
}
if (!isset($_SESSION['rights']['admin']['joinus']) AND !isset($_SESSION['rights']['superadmin'])) {
	table(ERROR, NO_ADMIN_RIGHTS);
} else {
	if(isset($_GET['func'])) {
		switch($_GET['func']) {
			case 'del':
				admin_joinus_del((int)$_GET['id']);
			break;			
			default:
				admin_joinus();
		}
	} else {
		admin_joinus();
	}
}
?>