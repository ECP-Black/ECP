<?php
function clankasse() {
	global $db;
	$tpl = new smarty;
	$konto = $db->fetch_assoc('SELECT * FROM '.DB_PRE.'ecp_clankasse');
	$konto['kontostand'] = number_format($konto['kontostand'], 2, ',', '');
	foreach($konto AS $key=>$value) {
		$tpl->assign($key, $value);
	}
	ob_start();
	$anzahl = $db->result(DB_PRE.'ecp_clankasse_transaktion','COUNT(ID)', '1');
	$limits = get_sql_limit($anzahl, LIMIT_CLANKASSE_TRANS);
	$db->query('SELECT a.*, b.username, c.username as buchusername FROM '.DB_PRE.'ecp_clankasse_transaktion as a LEFT JOIN '.DB_PRE.'ecp_user as b ON b.ID = vonuser LEFT JOIN '.DB_PRE.'ecp_user as c ON c.ID = userID ORDER BY datum DESC LIMIT '.$limits[1].','. LIMIT_CLANKASSE_TRANS);
	$buchung = array();
	while($row = $db->fetch_assoc()) {
		$row['datum'] = date(LONG_DATE, $row['datum']);
		if($row['vonuser']) $row['verwendung'] .= ' '.FROM.' '.$row['username'];
		$row['geld'] = number_format($row['geld'], 2, ',','.');
		$buchung[] = $row;
	}
	if($limits[0] > 1)
	$tpl->assign('seiten', makepagelink_ajax('?section=clankasse', 'return load_clankasse_page({nr});', @$_GET['page'], $limits[0]));
	$tpl->assign('buchung', $buchung);	
	$tpl->display(DESIGN.'/tpl/clankasse/kontodaten.html');
	$content = ob_get_contents();
	ob_end_clean();
	main_content(FINANCES, $content, '',1);
	if(date('m') > 3) 
		clankasse_buchungen(date('m')-2, date('Y'));
	elseif (date('m') == 2) 
		clankasse_buchungen(12, date('Y')-1);
	elseif (date('m') == 1) 
		clankasse_buchungen(11, date('Y')-1);
	else 
		clankasse_buchungen(1, date('Y'));
}
function clankasse_buchungen($monat, $jahr) {
	global $db;
	$monate = array();
	$tpl = new Smarty();
	if($monat > 6) {
		$tpl->assign('vmonat', $monat-6);
		$tpl->assign('vjahr', $jahr);	
	} else {
		$diff = $monat - 6;
		$tpl->assign('vmonat', 12+$diff);
		$tpl->assign('vjahr', $jahr-1);
	}
	$tpl->assign('startm', $monat);
	$tpl->assign('startj', $jahr);	
	for($i = 0; $i<6; $i++) {
		$monate[$jahr.'_'.$monat]['datum'] = $monat++.'/'.$jahr;
		if($monat == 13) {
			$monat = 1; $jahr++;
		}
	}
	$tpl->assign('nmonat', $monat);
	$tpl->assign('njahr', $jahr);
	$db->query('SELECT username, userID, verwendung, monatgeld FROM '.DB_PRE.'ecp_clankasse_member LEFT JOIN '.DB_PRE.'ecp_user ON userID = ID ORDER BY username ASC');
	$user = array();
	while($row = $db->fetch_assoc()) {
		$row['geld'] = number_format($row['monatgeld'], 2, ',','.');
		$user[] = $row;
	}
	$tpl->assign('user', $user);	
	$db->query('SELECT geld, verwendung, vonuser FROM '.DB_PRE.'ecp_clankasse_transaktion WHERE vonuser != 0 AND verwendung LIKE "%/%"');
	while($row = $db->fetch_assoc()) {
		$monat = explode('/', $row['verwendung']);
		if(isset($monate[$monat[0].'_'.$monat[1]])) {
			$monate[$monat[0].'_'.$monat[1]][$row['vonuser']]['geld'] = $row['geld'];
		}
	}
	$tpl->assign('user', $user);		
	$tpl->assign('monate', $monate);
	ob_start();
	$tpl->display(DESIGN.'/tpl/clankasse/overview.html');
	$content = ob_get_contents();
	ob_end_clean();
	main_content(OVERVIEW, '<div id="clankasse_overview">'.$content.'</div>', '',1);		
}
if(isset($_GET['action'])) {
	switch($_GET['action']) {
		default:
		if(@$_SESSION['rights']['public']['clankasse']['view'] OR @$_SESSION['rights']['superadmin'])
			clankasse();
		else
			echo table(ACCESS_DENIED, NO_ACCESS_RIGHTS);
	}
} else {
	if(@$_SESSION['rights']['public']['clankasse']['view'] OR @$_SESSION['rights']['superadmin'])
		clankasse();
	else
		echo table(ACCESS_DENIED, NO_ACCESS_RIGHTS);
}
?>