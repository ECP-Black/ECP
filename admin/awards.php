<?php
function admin_awards() {
	global $db;
	$awards = array();
	$db->query('SELECT `awardID`, `eventname`, `eventdatum`, `url`, `platz` FROM '.DB_PRE.'ecp_awards ORDER BY eventdatum DESC');
	while($row = $db->fetch_assoc()) {
		$row['eventdatum'] = date('d.m.Y', $row['eventdatum']);
		$awards[] = $row;
	}
	$tpl = new Smarty();
	$tpl->assign('awards', $awards);
	$tpl->assign('teams', get_teams_form());
	$tpl->assign('games', get_games_form());
	$tpl->assign('lang', get_languages());	
	ob_start();
	$tpl->display(DESIGN.'/tpl/admin/awards.html');
	$content = ob_get_contents();
	ob_end_clean();
	main_content(AWARDS, $content, '',1);
} 
function admin_awards_add() {
	global $db;
	ob_end_clean();
	ajax_convert_array($_POST);
	if(@$_SESSION['rights']['admin']['awards']['add'] OR @$_SESSION['rights']['superadmin']) {	
		$db->setMode(0);
		if($_POST['eventname'] == '' OR !strtotime($_POST['eventdatum']) OR !$_POST['platz'] OR !$_POST['teamID'] OR !$_POST['gID'] OR !$_POST['spieler']) {
			echo NOT_NEED_ALL_INPUTS;
		} else {
			$lang = array();
			foreach($_POST AS $key => $value) {
				if(strpos($key, 'cription_')) {
					$lang[substr($key,strpos($key, '_')+1)] = $value;
				}
			}	
			$players = ',';
			$play = explode(',',$_POST['spieler']);
			foreach($play AS $value) {
				$value = trim($value);
				if($value) {
					$userid = $db->result(DB_PRE.'ecp_user', 'ID', 'username = \''.strsave($value).'\'');
					if($userid)
					@$players .= $userid.',';
				}
			}				
			if($db->query(sprintf('INSERT INTO '.DB_PRE.'ecp_awards (`eventname`, `eventdatum`, `url`, `platz`, `teamID`, `gID`, `preis`, `bericht`, `spieler`, eingetragen) 
									VALUES (\'%s\', %d, \'%s\', %d, %d, %d, \'%s\', \'%s\', \'%s\', %d)', 
									strsave($_POST['eventname']), strtotime($_POST['eventdatum']), strsave(check_url($_POST['url'])), (int)$_POST['platz'], (int)$_POST['teamID'], (int)$_POST['gID'],strsave($_POST['preis']), strsave(json_encode($lang)), @$players, time()))) {
				echo 'ok';
			}
		}
	} else {
		echo html_ajax_convert(NO_ADMIN_RIGHTS);
	}
	die();
}
function admin_awards_edit($id) {
	global $db;
	ob_end_clean();
	$db->setMode(0);
	ajax_convert_array($_POST);
	if(@$_SESSION['rights']['admin']['awards']['edit'] OR @$_SESSION['rights']['superadmin']) {		
		if($_POST['eventname'] == '' OR !strtotime($_POST['eventdatum']) OR !$_POST['platz'] OR !$_POST['teamID'] OR !$_POST['gID'] OR !$_POST['spieler']) {
			echo NOT_NEED_ALL_INPUTS;
		} else {
			$lang = array();
			foreach($_POST AS $key => $value) {
				if(strpos($key, 'cription_')) {
					$lang[substr($key,strpos($key, '_')+1)] = $value;
				}
			}	
			$players = ',';
			$play = explode(',',$_POST['spieler']);
			foreach($play AS $value) {
				$value = trim($value);
				if($value) {
					$userid = @$db->result(DB_PRE.'ecp_user', 'ID', 'username = \''.strsave($value).'\'');
					if($userid)
					$players .= $userid.',';
				}
			}				
			if($db->query(sprintf('UPDATE '.DB_PRE.'ecp_awards SET `eventname` = \'%s\', `eventdatum` = %d, `url` = \'%s\', `platz` = \'%s\', `teamID` = %d, `gID` = %d, `preis` = \'%s\', `bericht` = \'%s\', `spieler` = \'%s\' WHERE awardID = %d', 
									strsave($_POST['eventname']), strtotime($_POST['eventdatum']), strsave(check_url($_POST['url'])), (int)$_POST['platz'], (int)$_POST['teamID'], (int)$_POST['gID'],strsave($_POST['preis']), strsave(json_encode($lang)), @$players, $id))) {
				echo 'ok';
			}
		}
	} else {
		echo html_ajax_convert(NO_ADMIN_RIGHTS);
	}	
	die();
}
function admin_awards_del($id) {
	global $db;
	$db->setMode(0);
	ob_end_clean();
	if(@$_SESSION['rights']['admin']['awards']['del'] OR @$_SESSION['rights']['superadmin']) {		
		if($db->query('DELETE FROM '.DB_PRE.'ecp_awards WHERE awardID = '.$id) AND $db->query('DELETE FROM '.DB_PRE.'ecp_comments WHERE bereich = \'awards\' AND subID ='.$id)) {
			echo 'ok';
		}
	} else {
		echo html_ajax_convert(NO_ADMIN_RIGHTS);
	}	
	die();
}
if (!isset($_SESSION['rights']['admin']['awards']) AND !isset($_SESSION['rights']['superadmin'])) {
	table(ERROR, NO_ADMIN_RIGHTS);
} else {
	if(isset($_GET['func'])) {
		switch($_GET['func']) {
			case 'add':
				admin_awards_add();
				break;
			case 'edit':
				admin_awards_edit((int)$_GET['id']);
				break;
			case 'del':
				admin_awards_del((int)$_GET['id']);
				break;												
			default:
				admin_awards();
		}
	} else {
		admin_awards();
	}
}
?>