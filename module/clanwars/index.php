<?php
function clanwars() {
	global $db, $countries;
	ob_start();
	$tpl = new smarty;
	$tpl->assign('win',0);
	$tpl->assign('draw',0);
	$tpl->assign('loss',0);
	$tpl->assign('games', get_games_form((int)@$_GET['gameID']));
	$tpl->assign('teams', get_teams_form((int)@$_GET['teamID']));
	$tpl->assign('matchtypes', get_matchtype_form((int)@$_GET['matchtypeID']));
	$tpl->assign('xonx', get_xonx_form(@$_GET['xonx']));
	if(@$_GET['gameID']) $where = ' AND gID = '.(int)$_GET['gameID']; else $_GET['gameID'] = 0;
	if(@$_GET['teamID']) @$where .= ' AND '.DB_PRE.'ecp_wars.tID = '.(int)$_GET['teamID'];  else $_GET['teamID'] = 0;
	if(@$_GET['matchtypeID']) @$where .= ' AND mID = '.(int)$_GET['matchtypeID'];  else $_GET['matchtypeID'] = 0;
	if(@$_GET['xonx']) @$where .= ' AND xonx = \''.strsave($_GET['xonx']).'\'';
	switch(@$_GET['sortby']) {
		case 'opp':
			$orderby = ' oppname ';
		break;
		case 'matchtype':
			$orderby = ' matchtypename ';
		break;		
		case 'team':
			$orderby = ' tname ';
		break;				
		default: 
			$orderby = DB_PRE.'ecp_wars.datum ';
	}
	switch(@$_GET['art']) {
		case 'asc': 
			$orderby .='ASC ';
		break;
		default:
			$orderby .='DESC ';
	}
	$db->query('SELECT COUNT(result) as val, result FROM '.DB_PRE.'ecp_wars WHERE status = 1 '.@$where.' GROUP BY result');
	while($row = $db->fetch_assoc()) {
		$tpl->assign($row['result'], $row['val']);
		@$gesamt += $row['val'];
	}
	$tpl->assign('anzahl', (int)@$gesamt);
	if((int)@$gesamt) {
		ob_start();
		$limit = get_sql_limit($gesamt, LIMIT_CLANWARS);
		$db->query('SELECT `warID`, '.DB_PRE.'ecp_wars.datum, `result`, `resultscore`, `tname`, `oppname`, `country`, '.DB_PRE.'ecp_wars_opp.homepage, `icon`, `gamename`, `matchtypename`, COUNT(comID) as comments 
					FROM '.DB_PRE.'ecp_wars 
					LEFT JOIN '.DB_PRE.'ecp_teams ON '.DB_PRE.'ecp_teams.tID = '.DB_PRE.'ecp_wars.tID 
					LEFT JOIN '.DB_PRE.'ecp_wars_games ON gID = gameID 
					LEFT JOIN '.DB_PRE.'ecp_wars_opp ON oID = oppID 
					LEFT JOIN '.DB_PRE.'ecp_wars_matchtype ON mID = matchtypeID 
					LEFT JOIN '.DB_PRE.'ecp_comments ON (subID = warID AND bereich = "clanwars") 
					WHERE status = 1 '.@$where.' 
					GROUP BY warID
					ORDER BY '.$orderby.'
					LIMIT '.$limit[1].','.LIMIT_CLANWARS);
		$clanwars = array();
		while($row = $db->fetch_assoc()) {
			$row['datum'] = date('d.m.y', $row['datum']);
			$row['countryname'] = $countries[$row['country']];
			$clanwars[] = $row;
		}
		$tplcw = new smarty;
		if($limit[0] > 1)
		$tplcw->assign('seiten', makepagelink_ajax('?section=clanwars&gameID='.$_GET['gameID'].'&teamID='.$_GET['teamID'].'&matchtypeID='.$_GET['matchtypeID'].'&xonx='.$_GET['xonx'].'&sortby='.$_GET['sortby'].'&art='.$_GET['art'].'', 'return load_wars('.$_GET['gameID'].', '.$_GET['teamID'].', '.$_GET['matchtypeID'].', \''.$_GET['xonx'].'\', \''.$_GET['sortby'].'\', \''.$_GET['art'].'\', {nr});', @$_GET['page'], $limit[0]));
		$tplcw->assign('clanwars', $clanwars);
		$tplcw->display(DESIGN.'/tpl/clanwars/overview.html');
		$content = ob_get_contents();
		ob_end_clean();
		$tpl->assign('clanwars', @$content);
	}
	$tpl->display(DESIGN.'/tpl/clanwars/head.html');
	$content = ob_get_contents();
	ob_end_clean();
	main_content(CLANWARS, $content, '', 1);	
}
function clanwars_view($id) {
	global $db, $countries;
	$db->query('SELECT `warID`, '.DB_PRE.'ecp_wars.datum, `result`, `resultscore`, `tname`, `oppname`, `country`, '.DB_PRE.'ecp_wars_opp.homepage, `icon`, `gamename`, `matchtypename`, COUNT(comID) as comments, matchtypename, oppshort, ownplayers, oppplayers, xonx, matchlink, report
					FROM '.DB_PRE.'ecp_wars 
					LEFT JOIN '.DB_PRE.'ecp_teams ON '.DB_PRE.'ecp_teams.tID = '.DB_PRE.'ecp_wars.tID 
					LEFT JOIN '.DB_PRE.'ecp_wars_games ON gID = gameID 
					LEFT JOIN '.DB_PRE.'ecp_wars_opp ON oID = oppID 
					LEFT JOIN '.DB_PRE.'ecp_wars_matchtype ON mID = matchtypeID 
					LEFT JOIN '.DB_PRE.'ecp_comments ON (subID = warID AND bereich = "clanwars") 
					WHERE warID = '.$id.'
					GROUP BY warID');
	if($db->num_rows()) {
		$tpl = new smarty;
		$row = $db->fetch_assoc();
		$report = json_decode($row['report'], true);
		if(isset($report[LANGUAGE])) {
			$row['report'] = $report[LANGUAGE]; 
		} else  {
			$row['report'] = @$report['de'];
		}			
		$row['datum'] = date(LONG_DATE, $row['datum']);
		$row['countryname'] = $countries[$row['country']];
		$own = explode(',', $row['ownplayers']);
		foreach($own AS $value) {
			if($value) {
				@$search .= ' OR ID = '.(int)$value;
			}
		}
		$db->query('SELECT username, ID FROM '.DB_PRE.'ecp_user WHERE ID = 0'.@$search.' ORDER BY username ASC');
		while($subrow = $db->fetch_assoc()) {
			@$players .= '<a href="?section=user&id='.$subrow['ID'].'">'.htmlspecialchars($subrow['username']).'</a>, ';
		}
		$row['ownplayers'] = substr(@$players, 0, strlen(@$players)-2);	
		foreach($row AS $key => $value) $tpl->assign($key, $value);
		$result = $db->query('SELECT scoreID, locationname, ownscore, oppscore FROM '.DB_PRE.'ecp_wars_scores LEFT JOIN '.DB_PRE.'ecp_wars_locations ON lID = locationID WHERE wID = '.$id);
		$locations = array();
		while($s = mysql_fetch_assoc($result)) {
			if(file_exists('images/maps/'.$s['locationname'].'.jpg')) $s['exist'] = true;
			if($s['ownscore'] > $s['oppscore']) {
				$s['own_result'] = 'win';
				$s['opp_result'] = 'loss';
			} elseif ($s['ownscore'] < $s['oppscore']) {
				$s['own_result'] = 'loss';
				$s['opp_result'] = 'win';
			} else {
				$s['own_result'] = 'draw';
				$s['opp_result'] = 'draw';
			}
			$screens = array();
			$i= 0;
			$db->query('SELECT filename FROM '.DB_PRE.'ecp_wars_screens WHERE sID = '.$s['scoreID'].' AND wID = '.$id);
			while($pic = $db->fetch_assoc()) {
				$pic['i'] = @++$i;
				$screens[] = $pic;
			}
			$s['screens'] =  $screens;
			$locations[] = $s;
			@$maps .= ', '.$s['locationname'];
		}
		$tpl->assign('locations', substr($maps, 2));
		$tpl->assign('maps', $locations);
		ob_start();
		$tpl->display(DESIGN.'/tpl/clanwars/detail.html');
		$content = ob_get_contents();
		ob_end_clean();
		main_content(CLANWAR_DETAIL, $content, '', 1);	
		if(@$_SESSION['rights']['public']['clanwars']['com_view'] OR @$_SESSION['rights']['superadmin']) {
			$conditions = array('LIMIT' 	=> LIMIT_COMMENTS,
					'ORDER'		=> COMMENTS_ORDER,
					'SPAM'		=> SPAM_CLANWARS_COMMENTS,
					'section'   => 'clanwars');			
			$conditions['action'] = 'add';
			$conditions['link'] = '?section=clanwars&action=view&id='.$id;
			comments_get('clanwars', $id, $conditions);
		} else
			echo table(ACCESS_DENIED, NO_RIGHTS_READ_COMMENT);				
	} else {
		table(ERROR, NO_ENTRIES_ID);
	}
}
function clanwars_next($id) {
	global $db;
	$row = $db->fetch_assoc('SELECT `warID` , '.DB_PRE.'ecp_wars.`tID` , `gID` , `datum` , `xonx` , `oID` , `oppname`, `oppshort`, `homepage`, `country`, tname, gamename, icon, tname, matchtypename, meldefrist, server, pw, livestream, hinweise
									FROM `'.DB_PRE.'ecp_wars` 
									LEFT JOIN `'.DB_PRE.'ecp_wars_games` ON ( gameID = gID ) 
									LEFT JOIN `'.DB_PRE.'ecp_wars_matchtype` ON ( matchtypeID = mID ) 
									LEFT JOIN `'.DB_PRE.'ecp_teams` ON ( '.DB_PRE.'ecp_teams.tID = '.DB_PRE.'ecp_wars.tID ) 
									LEFT JOIN `'.DB_PRE.'ecp_wars_opp` ON ( oppID = oID ) 
									WHERE warID = '.$id.'
									GROUP BY warID');
	if($row['warID']) {
		$tpl = new Smarty();
		$row['datum'] = date(LONG_DATE, $row['datum']);
		if($row['meldefrist'] > time()) $tpl->assign('aktiv', 1);
		$tpl->assign('id', $id);
		$lang = json_decode($row['hinweise'], true);
		$row['hinweise'] = (@$lang[LANGUAGE] == '' ? @$lang[DEFAULT_LANG] : @$lang[LANGUAGE]);
		$row['meldefrist'] = date(LONG_DATE, $row['meldefrist']);
		$db->query('SELECT locationname FROM '.DB_PRE.'ecp_wars_scores LEFT JOIN '.DB_PRE.'ecp_wars_locations ON (lID = locationID) WHERE wID = '.$id);
		while($sub = $db->fetch_assoc()) {
			@$row['maps'] .= ', '.$sub['locationname'];
		}
		@$row['maps'] = substr(@$row['maps'], 2);
		foreach($row AS $key =>$value) $tpl->assign($key, $value);
		if(isset($_SESSION['userID'])) {
			$db->query('SELECT userID, a.status, username, meldedatum FROM '.DB_PRE.'ecp_wars_teilnehmer as a LEFT JOIN '.DB_PRE.'ecp_user ON ID = userID WHERE warID ='.$id.' ORDER BY username ASC');
			while($row = $db->fetch_assoc()) {
				if($row['userID'] == $_SESSION['userID'])  $tpl->assign('teilnehmer', true);
				$row['datum'] = date(SHORT_DATE, $row['meldedatum']);
				@$players[] = $row;
			}
			$tpl->assign('players', @$players);
		}
		ob_start();
		$tpl->display(DESIGN.'/tpl/clanwars/view_next.html');
		$content = ob_get_contents();
		ob_end_clean();
		main_content(NEXT_WAR, $content, '', 1);	
	} else {
		table(ERROR, NO_ENTRIES_ID);
	}
}
function clanwars_next_part($id, $mode) {
	global $db;
	if($db->result(DB_PRE.'ecp_wars_teilnehmer', 'COUNT(userID)', 'userID = '.$_SESSION['userID'].' AND warID = '.$id)) {
		if($db->query('UPDATE '.DB_PRE.'ecp_wars_teilnehmer SET status = '.$mode.', meldedatum = '.time().' WHERE warID = '.$id.' AND userID = '.$_SESSION['userID'])) {
			header1('?section=clanwars&action=nextwar&id='.$id);
		}
	} else {
		table(ERROR, NO_ACCESS_RIGHTS);
	}
}
$conditions = array('LIMIT' 	=> LIMIT_COMMENTS,
				'ORDER'		=> COMMENTS_ORDER,
				'SPAM'		=> SPAM_CLANWARS_COMMENTS,
				'section'   => 'clanwars');		
if(isset($_GET['action'])) {
	switch($_GET['action']) {
		case 'view':
			if(@$_SESSION['rights']['public']['clanwars']['details'] OR @$_SESSION['rights']['superadmin']) 
				clanwars_view((int)$_GET['id']);
			else
				echo table(ACCESS_DENIED, NO_ACCESS_RIGHTS);	
		break; 	
		case 'nextwar':
			if(@$_SESSION['rights']['public']['clanwars']['view_next'] OR @$_SESSION['rights']['superadmin']) 
				clanwars_next((int)$_GET['id']);
			else
				echo table(ACCESS_DENIED, NO_ACCESS_RIGHTS);	
		break; 	
		case 'setpart':
			if(@$_SESSION['rights']['public']['clanwars']['view_next'] OR @$_SESSION['rights']['superadmin']) 
				clanwars_next_part((int)$_GET['id'], (int)$_GET['mode']);
			else
				echo table(ACCESS_DENIED, NO_ACCESS_RIGHTS);	
		break; 				
		case 'addcomment':
			if(@$_SESSION['rights']['public']['clanwars']['com_add'] OR @$_SESSION['rights']['superadmin']) {		
				$conditions['action'] = 'add';
				$conditions['link'] = '?section=clanwars&action=view&id='.(int)$_GET['id'];
				comments_add('clanwars', (int)$_GET['id'], $conditions);
			} else
				echo table(ACCESS_DENIED, NO_ACCESS_RIGHTS);			
		break;		
		case 'editcomment':
			$conditions['action'] = 'edit';
			$conditions['link'] = '?section=clanwars&action=view&id='.(int)$_GET['subid'];
			comments_edit('clanwars', (int)$_GET['subid'], (int)$_GET['id'], $conditions);		
		break;					
		default:
			if(@$_SESSION['rights']['public']['clanwars']['view'] OR @$_SESSION['rights']['superadmin'])
				clanwars();
			else
				echo table(ACCESS_DENIED, NO_ACCESS_RIGHTS);
	}
} else {
	if(@$_SESSION['rights']['public']['clanwars']['view'] OR @$_SESSION['rights']['superadmin'])
		clanwars();
	else
		echo table(ACCESS_DENIED, NO_ACCESS_RIGHTS);
}
?>