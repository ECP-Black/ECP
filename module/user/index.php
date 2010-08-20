<?php
$conditions = array('LIMIT' 	=> LIMIT_COMMENTS,
					'ORDER'		=> 'DESC',
					'SPAM'		=> SPAM_USER_GB_COMMENTS,
					'section'   => 'user');	
function user_details($id) {
	if(@$_SESSION['rights']['public']['user']['view'] OR @$_SESSION['rights']['superadmin']) {
		global $db, $countries, $groups;
		$data = $db->fetch_assoc('SELECT ID, `username`, `country`, `sex`, `signatur`, `realname`, `wohnort`, `geburtstag`, `homepage`, `icq`, `msn`, `yahoo`, `skype`, `xfire`, `aim`,
											`clanname`, `clanirc`, `clanhomepage`, `clanhistory`, `cpu`, `mainboard`, `ram`, `gkarte`, `skarte`, `monitor`, `maus`, `tastatur`, `mauspad`, 
											`internet`, `festplatte`, `headset`, `aboutme`, lastlogin, registerdate, uID AS online, user_pic FROM '.DB_PRE.'ecp_user
											LEFT JOIN '.DB_PRE.'ecp_online ON (uID = '.$id.' AND lastklick > '.(time()-SHOW_USER_ONLINE).')
											WHERE ID = '.$id.' GROUP BY ID');
		if(isset($data['username'])) {
			if(isset($_SESSION['userID']) AND $_SESSION['userID'] != $id) {
				$db->query('UPDATE '.DB_PRE.'ecp_user_lastvisits SET datum = '.time().' WHERE userID = '.$id.' AND visitID = '.$_SESSION['userID']);
				if($db->affekt_rows() == 0) {
					$db->query('INSERT INTO '.DB_PRE.'ecp_user_lastvisits (userID, visitID, datum) VALUES ('.$id.', '.$_SESSION['userID'].', '.time().')');
				}
				$anzahl = $db->result(DB_PRE.'ecp_user_lastvisits', 'COUNT(userID)', 'userID = '.$id);
				if($anzahl > 10) {
					$db->query('DELETE FROM '.DB_PRE.'ecp_user_lastvisits WHERE userID ='.$id.' LIMIT '.($anzahl-10));
				}
			}
			if(!isset($_SESSION['profil'][$id]) AND @$_SESSION['userID'] != $id) {
				$db->query('UPDATE '.DB_PRE.'ecp_user_stats SET profilhits = profilhits + 1 WHERE userID = '.$id);
				$_SESSION['profil'][$id] = true;
			}
			$tpl = new smarty;
			$data['ID'] = format_nr($data['ID']);
			$data['homepage'] = check_url_length($data['homepage']);
			$data['clanhomepage'] = check_url_length($data['clanhomepage']);
			$data['registerdate'] = date(LONG_DATE, $data['registerdate']);
			if($data['lastlogin'] == 0) {
				$data['lastlogin'] = NEVER_LOGGED_IN;
 			} else
				$data['lastlogin'] = date(LONG_DATE, $data['lastlogin']);
			$data['countryname'] = $countries[$data['country']];
			if($data['geburtstag'] == '0000-00-00') $data['geburtstag'] = '';
			if($data['geburtstag']) {
            	$birthday = explode('-', $data['geburtstag']);
            	$data['geburtstag'] = $birthday[2].'.'.$birthday[1].'.'.$birthday[0];
            	$alter = alter($birthday[2], $birthday[1], $birthday[0]);
            	IF(date('m') == $birthday[1] AND date('d') < $birthday[2]) $alter -=1;
            	$next = @mktime(0,0,0,$birthday[1],$birthday[2],$birthday[0] + $alter + 1) - time();
            	$tpl->assign('alter', $alter);
            	IF (date('m') == $birthday[1] AND date('d') == $birthday[2]) {
	                $tpl->assign('next', BIRTH_TODAY);
            	} else {
                	$tpl->assign('next', round(($next+86400)/60/60/24).' '.DAYS);
            	}			
			}
			$data['icqtrim'] = str_replace('-', '',$data['icq']);
			$data['sextext'] = ($data['sex'] == 'male') ? MALE : FEMALE;
			foreach($data AS $key =>$value) {
				$tpl->assign($key, $value);
			}
			ob_start();
			$conditions = array('LIMIT' 	=> LIMIT_COMMENTS,
								'ORDER'		=> 'DESC',
								'SPAM'		=> SPAM_USER_GB_COMMENTS,
								'section'   => 'user');			
			$conditions['action'] = 'add';
			$conditions['link'] = '?section=user&view=gb&id='.$id;
			comments_get('user', $id, $conditions, 0, 0);
			$content = ob_get_contents();
			$tpl->assign('guestbook', $content);
			ob_end_clean();
			$db->query('SELECT buddyID, username, country, uID as online, user_pic, lastlogin, sex FROM '.DB_PRE.'ecp_buddy LEFT JOIN '.DB_PRE.'ecp_user ON (buddyID = ID) LEFT JOIN '.DB_PRE.'ecp_online ON (uID = buddyID AND lastklick > '.(time()-SHOW_USER_ONLINE).') WHERE userID = '.$id.' GROUP BY buddyID ORDER BY online DESC,username ASC');
			$buddy = array();	
			while($row = $db->fetch_assoc()) {
				if($row['lastlogin'] == 0) {
					$row['lastlogin'] = NEVER_LOGGED_IN;
	 			} else				
				$row['lastlogin'] = date(SHORT_DATE, $row['lastlogin']);
				$buddy[] = $row;
			}		
			$tpl->assign('buddies', $buddy);
			$last = array();
			$db->query('SELECT visitID, username, country, datum, uID as online FROM '.DB_PRE.'ecp_user_lastvisits LEFT JOIN '.DB_PRE.'ecp_user ON visitID = ID LEFT JOIN '.DB_PRE.'ecp_online ON (uID = visitID AND lastklick > '.(time()-SHOW_USER_ONLINE).') WHERE userID = '.$id.' GROUP BY visitID ORDER BY datum DESC');
			while($row = $db->fetch_assoc()) {
				$row['countryname'] = $countries[$row['country']];
				$row['time'] = goodtime(time()-$row['datum'], 4);
				$last[] = $row;
			}		
			$tpl->assign('last', $last);
			$user = $db->fetch_assoc('SELECT `registerdate`, rankname, `clicks`, `logins`, `comments`, a.money, iconname, `msg_s`, `msg_r`, `profilhits`, `scheine`, `2er`, `3er`, `4er`, COUNT(b.scheinID) as scheine FROM '.DB_PRE.'ecp_user LEFT JOIN '.DB_PRE.'ecp_user_stats as a ON (a.userID = ID) LEFT JOIN '.DB_PRE.'ecp_ranks ON (rID = rankID) LEFT JOIN '.DB_PRE.'ecp_lotto_scheine as b ON (b.userID = ID) WHERE ID = '.$id.' GROUP BY ID');
			$db->query('SELECT SUM(gewinn) as gewinn, art FROM '.DB_PRE.'ecp_lotto_gewinner WHERE userID = '.$id.' GROUP BY art');
			$user['wonmoney'] = 0;
			$user['2ermoney'] = 0;
			$user['3ermoney'] = 0;
			$user['4ermoney'] = 0;
			while($row = $db->fetch_assoc()) {
				$user['wonmoney'] += $row['gewinn'];
				$user[$row['art'].'ermoney'] = $row['gewinn'];
			}
			$user['runden'] = $db->result(DB_PRE.'ecp_lotto_scheine', 'COUNT(DISTINCT(rundenID)) as runden', 'userID = '.$id);
			$user['gesamtrunden'] = mysql_result($db->query('SHOW TABLE STATUS LIKE "'.DB_PRE.'ecp_lotto_runden"'),0, 'Auto_increment')-1;
			$user['tage'] = ceil((time() - $user['registerdate'])/86400);
			$user['teilqoute'] = format_nr($user['runden']/($user['gesamtrunden'] == 0 ? 1 : $user['gesamtrunden'])*100,2);
			$user['scheinrunde'] = format_nr($user['scheine']/($user['runden'] == 0 ? 1 : $user['runden']),2);
			$user['winscheine'] = format_nr($user['2er']+$user['3er']+$user['4er']);
			$user['winqoute'] = format_nr($user['winscheine']/($user['scheine'] == 0 ? 1 : $user['scheine'])*100,2);
			$user['registerdate'] = date(LONG_DATE, $user['registerdate']);
			$user['2erpro'] = format_nr($user['2er']/($user['winscheine'] == 0 ? 1 : $user['winscheine'])*100,2);
			$user['3erpro'] = format_nr($user['3er']/($user['winscheine'] == 0 ? 1 : $user['winscheine'])*100,2);
			$user['4erpro'] = format_nr($user['4er']/($user['winscheine'] == 0 ? 1 : $user['winscheine'])*100,2);
			$user['2ermpro'] = format_nr($user['2ermoney']/($user['wonmoney'] == 0 ? 1 : $user['wonmoney'])*100,2);
			$user['3ermpro'] = format_nr($user['3ermoney']/($user['wonmoney'] == 0 ? 1 : $user['wonmoney'])*100,2);
			$user['4ermpro'] = format_nr($user['4ermoney']/($user['wonmoney'] == 0 ? 1 : $user['wonmoney'])*100,2);
			foreach($user AS $key=>$value) { 
				if($key == 'clicks' OR $key == 'comments' OR $key == 'gesamtrunden' OR $key == 'runden' OR $key == 'msg_s' OR $key == 'msg_r' OR $key == 'profilhits' OR $key == 'scheine' OR $key == '2er' OR $key == '3er' OR $key == '4er') $value = format_nr($value);
				if($key == 'money' OR $key == 'wonmoney' OR $key == '2ermoney' OR $key == '3ermoney' OR $key == '4ermoney') $value = format_nr($value, 2);
				$tpl->assign($key, $value);
			}
			$db->query('SELECT `awardID`, `eventname`, `eventdatum`, `url`, `platz`, `teamID`, `gID`, `preis`, tname, icon, gamename, COUNT(comID) as comments FROM `'.DB_PRE.'ecp_awards` LEFT JOIN '.DB_PRE.'ecp_teams ON tID = teamID LEFT JOIN '.DB_PRE.'ecp_wars_games ON gameID = gID LEFT JOIN '.DB_PRE.'ecp_comments ON (bereich = "awards" AND subID = awardID) WHERE spieler LIKE "%,'.$id.',%" GROUP BY awardID ORDER BY eventdatum DESC');
			$awards = array();
			while($row = $db->fetch_assoc()) {
				$row['eventdatum'] = date('d.m.Y', $row['eventdatum']);
				$awards[] = $row;
			}
			$tpl->assign('awards', $awards);	
			$tpl->assign('award', count($awards));
			$db->query('SELECT `warID`, '.DB_PRE.'ecp_wars.datum, `result`, `resultscore`, `tname`, `oppname`, `country`, '.DB_PRE.'ecp_wars_opp.homepage, `icon`, `gamename`, `matchtypename`, COUNT(comID) as comments 
						FROM '.DB_PRE.'ecp_wars 
						LEFT JOIN '.DB_PRE.'ecp_teams ON '.DB_PRE.'ecp_teams.tID = '.DB_PRE.'ecp_wars.tID 
						LEFT JOIN '.DB_PRE.'ecp_wars_games ON gID = gameID 
						LEFT JOIN '.DB_PRE.'ecp_wars_opp ON oID = oppID 
						LEFT JOIN '.DB_PRE.'ecp_wars_matchtype ON mID = matchtypeID 
						LEFT JOIN '.DB_PRE.'ecp_comments ON (subID = warID AND bereich = "clanwars") 
						WHERE status = 1 AND ownplayers LIKE "%,'.$id.',%"
						GROUP BY warID
						ORDER BY datum DESC');
			$clanwars = array();
			while($row = $db->fetch_assoc()) {
				$row['datum'] = date('d.m.y', $row['datum']);
				$row['countryname'] = $countries[$row['country']];
				$clanwars[] = $row;
			}	
			$tpl->assign('clanwars', $clanwars);		
			$tpl->assign('clanwar', count($clanwars));
			ob_start();
			$tpl->display(DESIGN.'/tpl/user/user_stats.html');
			$content = ob_get_contents();
			ob_end_clean();	
			$tpl->assign('stats', $content);
			ob_start();
			$db->query('SELECT gID, name FROM `'.DB_PRE.'ecp_user_groups` LEFT JOIN `'.DB_PRE.'ecp_groups` ON (gID = groupID) WHERE userID = '.$id.' ORDER BY name ASC');
			$gruppen = array();
			while($row = $db->fetch_assoc()) {
				if(array_key_exists($row['name'], $groups)) $row['name'] = $groups[$row['name']];
				$gruppen[] = $row;
			}
			$tpl->assign('gruppen', $gruppen);
			$tpl->display(DESIGN.'/tpl/user/user_details.html');
			$content = ob_get_contents();
			ob_end_clean();
			main_content(USER_PROFIL, $content, '',1);
		} else {
			table(ERROR, NO_ENTRIES_ID);
		}
	} else {
		table(ERROR, ACCESS_DENIED);
	}
}
function user_view_online() {
	global $db, $countries;
	$db->query('SELECT username, country, uID, lastklick, betretten FROM '.DB_PRE.'ecp_online LEFT JOIN '.DB_PRE.'ecp_user ON (uID = ID) WHERE uID != 0 AND lastklick > '.(time()-SHOW_USER_ONLINE).' ORDER BY username ASC');
	if($db->num_rows()) {
		$user = array();
		while($row = $db->fetch_assoc()) {
			$row['betretten'] =  goodtime(time()-$row['betretten']);
			$row['lastklick'] =  goodtime(time()-$row['lastklick']);
			$row['countryname'] = $countries[$row['country']];
			$user[] = $row;
		}
		$tpl = new Smarty();
		$tpl->assign('user', $user);
		ob_start();
		$tpl->display(DESIGN.'/tpl/user/user_online.html');
		$content = ob_get_contents();
		ob_end_clean();
		main_content(USER_ONLINE, $content, '',1);				
	} else {
		table(ERROR, NO_ENTRIES);
	}
}
function user_liste() {
	global $db, $countries;
	$tpl = new smarty();
	$anzahl = $db->result(DB_PRE.'ecp_user', 'COUNT(ID)', '1');
	$limits = get_sql_limit($anzahl, LIMIT_MEMBERS);
	$erlaubt = array('username', 'registerdate', 'lastlogin', 'geburtstag', 'online', 'sex', 'rangname');
	if(isset($_GET['orderby'])) {
		if(!in_array($_GET['orderby'], $erlaubt)) $_GET['orderby'] = 'username';
		($_GET['order'] == 'DESC') ? '' : $_GET['order'] = 'ASC';
		if($_GET['orderby'] == 'geburtstag') ($_GET['order'] == 'DESC') ? $_GET['order']  = 'ASC' : $_GET['order'] = 'DESC';
		if($_GET['orderby'] == 'rangname')  {
			($_GET['order'] == 'ASC') ? $_GET['orderby'] = 'fest ASC, abposts ASC' :  $_GET['orderby'] = 'fest DESC, abposts DESC'; 
			$_GET['order'] = '';
		}
	} else {
		$_GET['orderby'] = 'username';
		$_GET['order'] = 'ASC';
	}
	$db->query('SELECT geburtstag, xfire, icq, sex, registerdate, clanname, homepage, lastlogin, wohnort, user_pic, `ID`, username, country, uID as online, rankname, iconname FROM '.DB_PRE.'ecp_user LEFT JOIN '.DB_PRE.'ecp_ranks ON (rID = rankID) LEFT JOIN '.DB_PRE.'ecp_online ON (uID = ID AND lastklick > '.(time()- SHOW_USER_ONLINE).') GROUP BY ID ORDER BY '.strsave($_GET['orderby']).' '.strsave($_GET['order']).' LIMIT '.$limits[1].','.LIMIT_MEMBERS);	
	$user = array();
	while($row = $db->fetch_assoc()) {
		($row['lastlogin']) ? $row['lastlogin'] = date(LONG_DATE, $row['lastlogin']) : $row['lastlogin'] = NEVER_LOGGED_IN;
		$row['registerdate2'] = date('d.m.Y', $row['registerdate']);
		$row['registerdate'] = date(LONG_DATE, $row['registerdate']);
		if($row['geburtstag'] == '0000-00-00') $row['geburtstag'] = '';
		if($row['geburtstag']) {
		   	$birthday = explode('-', $row['geburtstag']);
		   	$row['geburtstag'] = $birthday[2].'.'.$birthday[1].'.'.$birthday[0];
		   	$alter = alter($birthday[2], $birthday[1], $birthday[0]);
		  	IF(date('m') == $birthday[1] AND date('d') < $birthday[2]) $alter -=1;
		   	$next = @mktime(0,0,0,$birthday[1],$birthday[2],$birthday[0] + $alter + 1) - time();
		   	$row['alter'] =  $alter;
		}
		$row['countryname'] = $countries[$row['country']];
		$row['icqtrim'] = str_replace('-', '',$row['icq']);			
		$user[] = $row;	
	}
	$tpl->assign('anzahl', $anzahl);
		if($limits[0] > 1)
		$tpl->assign('seiten', makepagelink_ajax('?section=user&action=list&orderby='.$_GET['orderby'].'&order='.$_GET['order'],'return load_user(\'orderby='.$_GET['orderby'].'&order='.$_GET['order'].'&page={nr}\');',@$_GET['page'], $limits[0]));
	$tpl->assign('user', $user);
	ob_start();
	$tpl->display(DESIGN.'/tpl/user/user_list.html');
	$content = ob_get_contents();
	ob_end_clean();
	main_content(USER_LIST, $content, '',1);	
}
if(isset($_GET['action'])) {
	switch($_GET['action']) {
		case 'viewonline':
			user_view_online();
		break;
		case 'list':
			if(@$_SESSION['rights']['public']['user']['list'] OR @$_SESSION['rights']['superadmin']) {
				user_liste();
			} else {
				table(ERROR, ACCESS_DENIED);
			}
		break;
		case 'addcomment':
			$conditions['action'] = 'add';
			$conditions['link'] = '?section=user&view=gb&id='.(int)$_GET['id'];
			comments_add('user', (int)$_GET['id'], $conditions);		
		break;
		case 'editcomment':
			$conditions['action'] = 'edit';
			$conditions['link'] = '?section=user&view=gb&id='.(int)$_GET['subid'];
			comments_edit('user', (int)$_GET['subid'], (int)$_GET['id'], $conditions);		
		break;						
		default:
			table(ERROR, NO_FUNKTION_CHOOSE);
	}
} elseif (isset($_GET['id'])) {
	user_details((int)$_GET['id']);
} else {
	table(ERROR, NO_FUNKTION_CHOOSE);
}
?>
