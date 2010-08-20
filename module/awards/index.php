<?php
function awards() {
	global $db;
	$anzahl = $db->result(DB_PRE.'ecp_awards', 'COUNT(awardID)', '1');
	if($anzahl) {
		$db->query('SELECT `awardID`, `eventname`, `eventdatum`, `url`, `platz`, `teamID`, `gID`, `preis`, tname, icon, gamename, COUNT(comID) as comments FROM `'.DB_PRE.'ecp_awards` LEFT JOIN '.DB_PRE.'ecp_teams ON tID = teamID LEFT JOIN '.DB_PRE.'ecp_wars_games ON gameID = gID LEFT JOIN '.DB_PRE.'ecp_comments ON (bereich = "awards" AND subID = awardID) GROUP BY awardID ORDER BY eventdatum DESC');
		$platz = array(0,0,0,0,0);
		$awards = array();
		while($row = $db->fetch_assoc()) {
			$row['eventdatum'] = date('d.m.Y', $row['eventdatum']);
			@$platz[$row['platz']-1]++;
			$awards[] = $row;
		}
		$tpl = new smarty;
		$tpl->assign('awards', $awards);
		$tpl->assign('anzahl', $anzahl);
		$tpl->assign('platz1', $platz[0]);
		$tpl->assign('platz2', $platz[1]);
		$tpl->assign('platz3', $platz[2]);
		$tpl->assign('platz1pro', round($platz[0]/$anzahl*100,1));
		$tpl->assign('platz2pro', round($platz[1]/$anzahl*100,1));
		$tpl->assign('platz3pro', round($platz[2]/$anzahl*100,1));		
		ob_start();
		$tpl->display(DESIGN.'/tpl/awards/awards.html');
		$content = ob_get_contents();
		ob_end_clean();
		main_content(AWARDS, $content, '',1);
	} else {
		table(INFO, NO_ENTRIES);
	}
}
function awards_view($id) {
	global $db;
	$row = $db->fetch_assoc('SELECT `awardID`, `eventname`, `eventdatum`, `url`, `platz`, `teamID`, `gID`, `preis`, spieler, bericht, tname, icon, gamename FROM `'.DB_PRE.'ecp_awards` LEFT JOIN '.DB_PRE.'ecp_teams ON tID = teamID LEFT JOIN '.DB_PRE.'ecp_wars_games ON gameID = gID WHERE awardID = '.$id);
	if(@$row['eventname']) {
		$tpl= new smarty;
		$row['eventdatum'] = date('d.m.Y', $row['eventdatum']);
		$report = json_decode($row['bericht'], true);
		$spieler = explode(',', $row['spieler']);
		$row['preis'] = htmlentities($row['preis'], ENT_QUOTES, "UTF-8");
		$row['eventname'] = htmlentities($row['eventname'], ENT_QUOTES, "UTF-8");
		foreach($spieler AS $value) {
			if((int)$value) {
				@$ids .= ' OR ID = '.$value;
			}
		}
		$db->query('SELECT username, ID FROM '.DB_PRE.'ecp_user WHERE ID = 0'.@$ids);
		while($sub = $db->fetch_assoc()) {
			@$players .= ', <a href="?section=user&id='.$sub['ID'].'" >'.$sub['username'].'</a>';
		}
		$tpl->assign('players', substr(@$players, 2));
		if(isset($report[LANGUAGE])) {
			$row['bericht'] = $report[LANGUAGE]; 
		} else  {
			$row['bericht'] = @$report['de'];
		}	
		foreach($row AS $key => $value) {
			$tpl->assign($key, $value);
		}
		ob_start();
		$tpl->display(DESIGN.'/tpl/awards/view.html');
		$content = ob_get_contents();
		ob_end_clean();
		main_content(AWARDS, $content, '',1);
	} else {
		table(ERROR, NO_ENTRIES_ID);
	}
	
}
$conditions = array('LIMIT' 	=> LIMIT_COMMENTS,
					'ORDER'		=> COMMENTS_ORDER,
					'SPAM'		=> SPAM_AWARDS_COMMENTS,
					'section'   => 'awards');
if(isset($_GET['action'])) {
	switch($_GET['action']) {
		case 'view':
			if(@$_SESSION['rights']['public']['awards']['view'] OR @$_SESSION['rights']['superadmin']) {
				awards_view((int)$_GET['id']);
				$conditions['action'] = 'add';
				$conditions['link'] = '?section=news&action=comments&id='.(int)$_GET['id'];
				comments_get('awards', (int)$_GET['id'], $conditions);
			} else
				echo table(ACCESS_DENIED, NO_ACCESS_RIGHTS);
		break;
		case 'addcomment':
			if(@$_SESSION['rights']['public']['awards']['com_add'] OR @$_SESSION['rights']['superadmin']) {		
				$conditions['action'] = 'add';
				$conditions['link'] = '?section=awards&action=view&id='.(int)$_GET['id'];
				comments_add('awards', (int)$_GET['id'], $conditions);
			} else
				echo table(ACCESS_DENIED, NO_ACCESS_RIGHTS);			
		break;
		case 'editcomment':
			$conditions['action'] = 'edit';
			$conditions['link'] = '?section=awards&action=view&id='.(int)$_GET['subid'];
			comments_edit('awards', (int)$_GET['subid'], (int)$_GET['id'], $conditions);		
		break;			
		default:
		if(@$_SESSION['rights']['public']['awards']['view'] OR @$_SESSION['rights']['superadmin'])
			awards();
		else
			echo table(ACCESS_DENIED, NO_ACCESS_RIGHTS);
	}
} else {
	if(@$_SESSION['rights']['public']['awards']['view'] OR @$_SESSION['rights']['superadmin'])
		awards();
	else
		echo table(ACCESS_DENIED, NO_ACCESS_RIGHTS);
}
?>