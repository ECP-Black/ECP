<?php
function admin_server() {
	global $db;
	$tpl = new smarty;
	$db->query('SELECT `ID`, `verwendung`, `intervall`, `betrag`, `nextbuch` FROM '.DB_PRE.'ecp_clankasse_auto');
	$auto = array();
	while($row = $db->fetch_assoc()) {
		$row['nextbuch'] = date(LONG_DATE, $row['nextbuch']);
		$row['betrag'] = number_format($row['betrag'], 2, ',','.');
		$auto[] = $row;
	}
	$tpl->assign('auto', $auto);
	ob_start();
	$tpl->display(DESIGN.'/tpl/admin/clankasse_auto_overview.html');
	$content = ob_get_contents();
	ob_end_clean();
	$tpl->assign('overview', $content);	
	$db->query('SELECT a.*, b.username FROM '.DB_PRE.'ecp_clankasse_transaktion as a LEFT JOIN '.DB_PRE.'ecp_user as b ON b.ID = vonuser ORDER BY datum DESC');
	$buchung = array();
	while($row = $db->fetch_assoc()) {
		$row['datum'] = date(LONG_DATE, $row['datum']);
		if($row['vonuser']) $row['verwendung'] .= ' '.FROM.' '.$row['username'];
		$row['geld'] = number_format($row['geld'], 2, ',','.');
		$buchung[] = $row;
	}
	$tpl->assign('buchung', $buchung);
	ob_start();
	$tpl->display(DESIGN.'/tpl/admin/clankasse_trans_overview.html');
	$content = ob_get_contents();
	ob_end_clean();
	$tpl->assign('buch_overview', $content);	
	$db->query('SELECT username, userID, verwendung, monatgeld FROM '.DB_PRE.'ecp_clankasse_member LEFT JOIN '.DB_PRE.'ecp_user ON userID = ID ORDER BY username ASC');
	$user = array();
	while($row = $db->fetch_assoc()) {
		$row['geld'] = number_format($row['monatgeld'], 2, ',','.');
		$user[] = $row;
	}
	$tpl->assign('user', $user);
	ob_start();
	$tpl->display(DESIGN.'/tpl/admin/clankasse_user_overview.html');
	$content = ob_get_contents();
	ob_end_clean();		
	$tpl->assign('user_trans', $content);	
	$konto = $db->fetch_assoc('SELECT * FROM '.DB_PRE.'ecp_clankasse');
	$konto['kontostand'] = number_format($konto['kontostand'], 2, ',', '');
	foreach($konto AS $key=>$value) {
		$tpl->assign($key, $value);
	}
	$tpl->assign('options', get_options(date('m'), date('Y')));
	ob_start();
	$tpl->display(DESIGN.'/tpl/admin/clankasse.html');
	$content = ob_get_contents();
	ob_end_clean();
	main_content(FINANCES, $content, '',1);
}
function get_options($smonat, $sjahr) {
   $jahr = date('Y', time())-2;
   $string = '<option value="0">'.CHOOSE.'</option>';
   $endjahr = $jahr+3;
   for($jahr; $jahr<=$endjahr; $jahr++) {
      for($monat = 1; $monat<=12; $monat++) {
        IF($monat == $smonat AND $jahr == $sjahr) $sub = 'selected="selected"'; else $sub = '';
        @$string .= '<option '.$sub.' value="'.$jahr.'/'.$monat.'">'.$monat.'/'.$jahr.'</option>';
      }
   }
  return $string;
 }
if (!isset($_SESSION['rights']['admin']['clankasse']) AND !isset($_SESSION['rights']['superadmin'])) {
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
			default:
				admin_server();
		}
	} else {
		admin_server();
	}
}
?>