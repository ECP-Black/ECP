<?php
function  lotto() {
	global $db;
	$lotto = $db->fetch_assoc('SELECT `lottoon`, `jackpot`, `preis`, `pro4er`, `pro3er`, `pro2er`, `jackpotraise`, `free_scheine`, ende, COUNT(scheinID) as scheine, a.rundenID FROM '.DB_PRE.'ecp_lotto, '.DB_PRE.'ecp_lotto_runden as a LEFT JOIN '.DB_PRE.'ecp_lotto_scheine as b ON (a.rundenID = b.rundenID) GROUP BY a.rundenID ORDER BY ende DESC LIMIT 1');
	if($lotto['lottoon']) {
		$tpl = new smarty;
		foreach($lotto AS $key=>$value)  {
			if($key == 'jackpot' OR $key == 'preis' OR $key == 'jackpotraise') {
				$value = format_nr($value, 2);
			}
			if($key == 'ende') $value = switch_wday_lang(date('w', $value)).', '.date(LONG_DATE, $value);
			$tpl->assign($key, $value);
		}
		$tpl->assign('money4er', format_nr($lotto['jackpot']/100*$lotto['pro4er'], 2));
		$tpl->assign('money3er', format_nr($lotto['jackpot']/100*$lotto['pro3er'], 2));
		$tpl->assign('money2er', format_nr($lotto['jackpot']/100*$lotto['pro2er'], 2));
		$last = $db->fetch_assoc('SELECT `rundenID`, `auszahlung`, ende, (`4er`+`3er`+`2er`) AS scheine , zahl1, zahl2, zahl3, zahl4 FROM '.DB_PRE.'ecp_lotto_runden ORDER BY ende DESC LIMIT 1,1');
		if(isset($last['ende'])) {
			$tpl->assign('lastdatum', date(LONG_DATE, $last['ende']));
			$tpl->assign('zahlen', $last['zahl1'].', '.$last['zahl2'].', '.$last['zahl3'].', '.$last['zahl4']);
			$tpl->assign('wins', str_replace(array('{scheine}', '{money}'), array(format_nr($last['scheine']), format_nr($last['auszahlung'],2)), LOTTO_WINS));
			$tpl->assign('rundenID', $last['rundenID']);
		}
		if(isset($_SESSION['userID'])) {
			$user = $db->fetch_assoc('SELECT money, scheine FROM '.DB_PRE.'ecp_user_stats WHERE userID = '.$_SESSION['userID']);
			if(floor($user['money']/$lotto['preis']) >= 1 OR $user['scheine'] < $lotto['free_scheine'])
				$tpl->assign('aktiv', 1);
			$tpl->assign('info', str_replace(array('{money}', '{scheine}'), array(format_nr($user['money'],2).' '.VIRTUELL_MONEY_UNIT, format_nr(floor($user['money']/$lotto['preis'])+(($lotto['free_scheine']-$user['scheine'] > 0 ? $lotto['free_scheine']-$user['scheine'] : 0)))), LOTTO_INFO));
			$scheine = array();
			$db->query('SELECT zahl1, zahl2, zahl3, zahl4 FROM '.DB_PRE.'ecp_lotto_scheine WHERE rundenID = '.$lotto['rundenID'].' AND userID = '.$_SESSION['userID']);
			if($db->num_rows()) {
				while($row = $db->fetch_assoc()) {
					$scheine[] = $row;
				}
				$tpls = new Smarty();
				$tpls->assign('scheine', $scheine);
				ob_start();
				$tpls->display(DESIGN.'/tpl/lotto/scheine.html');
				$content = ob_get_contents();
				ob_end_clean();		
				$tpl->assign('user_scheine', $content);
			}
		}
		ob_start();
		$tpl->display(DESIGN.'/tpl/lotto/main.html');
		$content = ob_get_contents();
		ob_end_clean();			
		main_content(LOTTO, $content, '', 1);
	} else {
		table(INFO, LOTTO_DEAKT);
	}
}
function lotto_winlist($id) {
	global $db, $countries;
	$runde = $db->fetch_assoc('SELECT `anfang`, `ende`, `rundenjackpot`, `auszahlung`, a.zahl1, a.zahl2, a.zahl3, a.zahl4, `4er`, `3er`, `2er`, `geld4er`, `geld3er`, `geld2er`, COUNT(scheinID) as scheine FROM '.DB_PRE.'ecp_lotto_runden as a LEFT JOIN '.DB_PRE.'ecp_lotto_scheine as b ON (a.rundenID = b.rundenID) WHERE a.rundenID = '.$id.' GROUP BY a.rundenID');
	if(isset($runde['rundenjackpot'])) {
		$tpl = new smarty();
		$runde['anfang'] = date(LONG_DATE, $runde['anfang']);
		$runde['ende'] = date(LONG_DATE, $runde['ende']);
		$runde['winner'] = format_nr($runde['4er']+$runde['3er']+$runde['2er']);
		$runde['geld4eruser'] = format_nr($runde['geld4er']/($runde['4er'] == 0 ? 1 : $runde['4er']),2);
		$runde['geld3eruser'] = format_nr($runde['geld3er']/($runde['3er'] == 0 ? 1 : $runde['3er']),2);
		$runde['geld2eruser'] = format_nr($runde['geld2er']/($runde['2er'] == 0 ? 1 : $runde['2er']),2);
		$runde['quote'] =  format_nr($runde['winner']/($runde['scheine'] == 0 ? 1 : $runde['scheine'])*100, 1);
		foreach($runde AS $key=>$value) {
			if($key == 'rundenjackpot' OR $key == 'auszahlung' OR $key == 'geld4er' OR $key == 'geld3er' OR $key == 'geld2er') $value = format_nr($value, 2);
			$tpl->assign($key, $value);
		}
		if($runde['winner']) {
			$db->query('SELECT gewinn, art, COUNT(scheinID) as scheine, username, a.userID, country, uID as online FROM '.DB_PRE.'ecp_lotto_gewinner as a LEFT JOIN '.DB_PRE.'ecp_user ON (a.userID = ID) LEFT JOIN '.DB_PRE.'ecp_online ON (uID = a.userID) LEFT JOIN '.DB_PRE.'ecp_lotto_scheine as b ON (rundenID = '.$id.' AND b.userID = a.userID) WHERE a.rID = '.$id.' GROUP BY gewinnID ORDER BY art DESC, username ASC');
			$gewinner = array();
			while($row = $db->fetch_assoc()) {
				$row['gewinn'] = format_nr($row['gewinn'],2);
				$row['countryname'] = $countries[$row['country']];
				$gewinner[] = $row;
			}
			$tpl->assign('gewinner', $gewinner);
		}
		ob_start();
		$tpl->display(DESIGN.'/tpl/lotto/winlist.html');
		$content = ob_get_contents();
		ob_end_clean();			
		main_content(LOTTO_ROUND, $content, '', 1);
	} else {
		table(ERROR, NO_ENTRIES_ID);
	}
}
function lotto_view_all() {
	global $db;
	$db->query('SELECT a.rundenID, `ende`, `rundenjackpot`, `auszahlung`, a.zahl1, a.zahl2, a.zahl3, a.zahl4, `4er`, `3er`, `2er`, COUNT(scheinID) as scheine FROM '.DB_PRE.'ecp_lotto_runden as a LEFT JOIN '.DB_PRE.'ecp_lotto_scheine as b ON (a.rundenID = b.rundenID) WHERE a.zahl1 != 0 GROUP BY a.rundenID ORDER BY ende DESC');
	$runden = array();
	while($row = $db->fetch_assoc()) {
		$row['ende'] = date(LONG_DATE, $row['ende']);
		$row['auszahlung'] = format_nr($row['auszahlung'],2);
		$row['rundenjackpot'] = format_nr($row['rundenjackpot'],2);
		$row['winner'] = format_nr($row['4er']+$row['3er']+$row['2er']);
		$runden[] = $row;
	}
	$tpl = new Smarty();
	$tpl->assign('runden', $runden);
	ob_start();
	$tpl->display(DESIGN.'/tpl/lotto/viewall.html');
	$content = ob_get_contents();
	ob_end_clean();			
	main_content(LOTTO, $content, '', 1);
}
if(isset($_GET['action'])) {
	switch($_GET['action']) {
		case 'winlist':
			if(@$_SESSION['rights']['public']['lotto']['view'] OR @$_SESSION['rights']['superadmin'])
				lotto_winlist((int)$_GET['runde']);
			else
				echo table(ACCESS_DENIED, NO_ACCESS_RIGHTS);
		break;
		case 'viewall':
			if(@$_SESSION['rights']['public']['lotto']['view'] OR @$_SESSION['rights']['superadmin'])
				lotto_view_all();
			else
				echo table(ACCESS_DENIED, NO_ACCESS_RIGHTS);
		break;		
		default:
			if(@$_SESSION['rights']['public']['lotto']['view'] OR @$_SESSION['rights']['superadmin'])
				lotto();
			else
				echo table(ACCESS_DENIED, NO_ACCESS_RIGHTS);
	}
} else {
	if(@$_SESSION['rights']['public']['lotto']['view'] OR @$_SESSION['rights']['superadmin'])
		lotto();
	else
		echo table(ACCESS_DENIED, NO_ACCESS_RIGHTS);
}
?>