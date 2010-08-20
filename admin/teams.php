<?php
function admin_teams () {
	global $db;
	$tpl = new smarty;
	$tpl->assign('lang', get_languages());
	$tpl->assign('groups', get_groups(@(int)$_POST['grID']));
	$bilder = '<option value="">'.NONE.'</option>';
	$pics = scan_dir('images/teams/', true);
	$endungen = array('jpg', 'jpeg', 'JPG', 'JPEG', 'gif', 'GIF', 'PNG', 'png');
	foreach($pics AS $value) {
		if(in_array(substr($value, strrpos($value, '.')+1), $endungen)) {
			$bilder .= '<option value="'.$value.'">'.$value.'</option>';
		}
	}
	$tpl->assign('pics', $bilder);
	ob_start();
	$tpl->display(DESIGN.'/tpl/admin/teams.html');
	$content = ob_get_contents();
	ob_end_clean();
	main_content(TEAMS, $content, '',1);
	get_teams();
}
function get_teams() {
	global $db;
	$tpl = new smarty;
	if(@$_GET['ajax']) ob_end_clean();
	$teams = array();
	$result = $db->query('SELECT tname, tID, info FROM '.DB_PRE.'ecp_teams ORDER BY posi ASC');
	while($row = mysql_fetch_assoc($result)) {
		$members = array();
		$subresult = $db->query('SELECT `username`, `mID`, `userID`, `name`, `aufgabe`, `aktiv`, country FROM '.DB_PRE.'ecp_members LEFT JOIN '.DB_PRE.'ecp_user ON (ID = userID) WHERE teamID = '.$row['tID'].' ORDER BY posi ASC');
		while($subrow = mysql_fetch_assoc($subresult)) {
			($subrow['aktiv']) ? $subrow['aktiv'] = '<span class="member_aktiv" style="cursor:pointer" onclick="member_switch_status('.$row['tID'].', '.$subrow['userID'].');">'.AKTIV.'</span>' : $subrow['aktiv'] = '<span style="cursor:pointer" class="member_inaktiv" onclick="member_switch_status('.$row['tID'].', '.$subrow['userID'].');">'.INAKTIV.'</span>';
			if ($subrow['name'] != '') $subrow['username'] = $subrow['name'];
			$members[] = $subrow;
		}
		$row['members'] = $members;
		$teams[] = $row;
	}	
	$tpl->assign('teams', $teams);
	ob_start();
	$tpl->display(DESIGN.'/tpl/admin/teams_overview.html');
	$content = ob_get_contents();
	ob_end_clean();		
	if(@$_GET['ajax']) { echo html_ajax_convert($content); die(); } 
	main_content(OVERVIEW, '<div id="teams_overview">'.$content.'</div>', '',1);	
}
function teams_add_member($id) {
	ob_end_clean();
	global $db;
	$db->setMode(0);
	ajax_convert_array($_POST);
	if(!isset($_SESSION['rights']['admin']['teams']['add_member']) AND !isset($_SESSION['rights']['superadmin'])) {
		echo NO_ADMIN_RIGHTS;
	} else {
		$userid = @$db->result(DB_PRE.'ecp_user', 'ID', 'username = \''.strsave(htmlspecialchars($_POST['user'])).'\'');
		if($userid) {
			if(@$db->result(DB_PRE.'ecp_members', 'COUNT(mID)', 'userID = '.$userid.' AND teamID = '.$id)) {
				echo USER_ALLREADY_IN_TEAM;
			} else {
				if($db->query(sprintf('INSERT INTO '.DB_PRE.'ecp_members (`userID`, `teamID`, `name`, `aufgabe`, `aktiv`) VALUES (%d, %d, \'%s\', \'%s\', %d )', $userid, $id, strsave($_POST['username']), strsave($_POST['task']), (int)@$_POST['aktiv']))) {
					$gid = $db->result(DB_PRE.'ecp_teams', 'grID', 'tID = '.$id);
					if($gid AND !$db->result(DB_PRE.'ecp_user_groups', 'COUNT(userID)', 'userID = '.$userid.' AND gID = '.$gid)) {
						$db->query('INSERT INTO '.DB_PRE.'ecp_user_groups (userID, gID) VALUES ('.$userid.', '.$gid.')');
					}
					echo 'ok';
				}
			}
		} else {
			echo NO_USER_EXIST;
		}
	}
	die();
}
function get_groups($id) {
	global $db, $groups;
	$gruppen = '<option value="0">'.NONE.'</option>';
	$db->query('SELECT name, groupID FROM '.DB_PRE.'ecp_groups ORDER BY name ASC');
	while($row = $db->fetch_assoc()) {
		($id == $row['groupID']) ? $sub = 'selcted' : $sub = '';
		if(isset($groups[$row['name']])) $row['name'] = $groups[$row['name']];
		$gruppen .= '<option '.$sub.' value="'.$row['groupID'].'">'.$row['name'].'</option>';
	}
	return $gruppen;
}
function admin_teams_add() {
	ob_end_clean();
	global $db;
	ajax_convert_array($_POST);
	if(!isset($_SESSION['rights']['admin']['teams']['add']) AND !isset($_SESSION['rights']['superadmin'])) {
		echo NO_ADMIN_RIGHTS;
	} else {
		if($_POST['name'] == '') {
			echo NOT_NEED_ALL_INPUTS;
		} else {
			$db->setMode(0);
			$lang = array();
			foreach($_POST AS $key => $value) {
				if(strpos($key, 'cription_')) {
					$lang[substr($key,strpos($key, '_')+1)] = $value;
				}
			}			
			$sql = sprintf('INSERT INTO '.DB_PRE.'ecp_teams (`tname`, `tpic`, `grID`, `info`, `cw`, `fightus`, `joinus`) VALUES (\'%s\', \'%s\', %d, \'%s\', %d, %d, %d)',
							strsave($_POST['name']),strsave($_POST['tpic']), (int)$_POST['grID'], strsave(json_encode($lang)), (int)@$_POST['cw'], (int)@$_POST['fightus'], (int)@$_POST['joinus']);
			if($db->query($sql)) {
				echo 'ok';
				die();	
			}
		}
	}
	die();
}
function admin_teams_edit($id) {
	ob_end_clean();
	global $db;
	ajax_convert_array($_POST);
	if(!isset($_SESSION['rights']['admin']['teams']['edit']) AND !isset($_SESSION['rights']['superadmin'])) {
		echo NO_ADMIN_RIGHTS;
	} else {
		ajax_convert_array($_POST);
		if($_POST['name'] == '') {
			echo NOT_NEED_ALL_INPUTS;
		} else {
			$db->setMode(0);
			$lang = array();
			foreach($_POST AS $key => $value) {
				if(strpos($key, 'cription_')) {
					$lang[substr($key,strpos($key, '_')+1)] = $value;
				}
			}			
			$sql = sprintf('UPDATE '.DB_PRE.'ecp_teams SET `tname` = \'%s\', `tpic` = \'%s\', `grID` = %d, `info` = \'%s\', `cw` = %d, `fightus` = %d, joinus = %d WHERE tID = %d ',
							strsave($_POST['name']),strsave($_POST['tpic']), (int)$_POST['grID'], strsave(json_encode($lang)), (int)@$_POST['cw'], (int)@$_POST['fightus'],(int)@$_POST['joinus'], $id);
			if($db->query($sql)) {
				echo 'ok';	
			}
		}
	}
	die();
}
function teams_switch_status($gid, $uid) {
	global $db;
	$db->setMode(0);
	$aktiv = $db->result(DB_PRE.'ecp_members', 'aktiv', 'userID = '.$uid.' AND teamID = '.$gid);
	if($aktiv == 0) $aktiv = 1; else $aktiv = 0;
	if($db->query('UPDATE '.DB_PRE.'ecp_members SET aktiv = '.$aktiv.' WHERE userID = '.$uid.' AND teamID = '.$gid)) {
		$_GET['ajax'] = 1;
		get_teams();
	}
}
function teams_member_del($gid, $uid) {
	global $db;
	$db->setMode(0);
	ob_end_clean();
	if(!isset($_SESSION['rights']['admin']['teams']['del_member']) AND !isset($_SESSION['rights']['superadmin'])) {
		echo NO_ADMIN_RIGHTS;
	} else {
		if($db->query('DELETE FROM '.DB_PRE.'ecp_members WHERE userID = '.$uid.' AND teamID = '.$gid)) {
			$gruppe = $db->result(DB_PRE.'ecp_teams', 'grID', 'tID = '.$gid);
			if($gruppe) {
				$db->query('DELETE FROM '.DB_PRE.'ecp_user_groups WHERE userID = '.$uid.' AND gID = '.$gruppe);
			}			
			echo 'ok';
		}
	}
	die();	
}
function teams_edit_member($id, $uid) {
	ob_end_clean();
	global $db;
	$db->setMode(0);
	ajax_convert_array($_POST);
	if(!isset($_SESSION['rights']['admin']['teams']['edit_member']) AND !isset($_SESSION['rights']['superadmin'])) {
		echo NO_ADMIN_RIGHTS;
	} else {
		if($db->query(sprintf('UPDATE '.DB_PRE.'ecp_members SET `name` = \'%s\', `aufgabe` = \'%s\', `aktiv` =  %d WHERE teamID = %d AND userID = %d', strsave($_POST['username']), strsave($_POST['task']), (int)@$_POST['aktiv'], $id, $uid))) {
			echo 'ok';	
		}			
	}
	die();
}
function admin_teams_del($id) {
	ob_end_clean();
	global $db;
	$db->setMode(0);
	if(!isset($_SESSION['rights']['admin']['teams']['del']) AND !isset($_SESSION['rights']['superadmin'])) {
		echo NO_ADMIN_RIGHTS;
	} else {
		$gid = $db->result(DB_PRE.'ecp_teams', 'grID', 'tID = '.$id);
		if($db->query('DELETE FROM '.DB_PRE.'ecp_teams WHERE tID = '.$id)) {
			if($gid) {
				$result = $db->query('SELECT userID FROM '.DB_PRE.'ecp_members WHERE teamID = '.$id);
				while($row = mysql_fetch_assoc($result)) {
					$db->query('DELETE FROM '.DB_PRE.'ecp_user_groups WHERE userID = '.$row['userID'].' AND gID = '.$gid);
				}
			}			
 			$db->query('DELETE FROM '.DB_PRE.'ecp_members WHERE teamID = '.$id);				
			echo 'ok';	
		}			
	}
	die();
}
if (!isset($_SESSION['rights']['admin']['teams']) AND !isset($_SESSION['rights']['superadmin'])) {
	table(ERROR, NO_ADMIN_RIGHTS);
} else {
	if(isset($_GET['func'])) {
		switch($_GET['func']) {
			case 'add':
				admin_teams_add();
				break;
			case 'edit':
				admin_teams_edit((int)$_GET['id']);
				break;
			case 'del':
				admin_teams_del((int)$_GET['id']);
				break;
			case 'get_teams':
				get_teams();
				break;	
			case 'addmember':
				teams_add_member((int)$_GET['id']);
			break;
			case 'switch_status':
				teams_switch_status((int)$_GET['gid'], (int)$_GET['uid']);
			break;							
			case 'delmember':
				teams_member_del((int)$_GET['gid'], (int)$_GET['uid']);
			break;
			case 'editmember':
				teams_edit_member((int)$_GET['id'], (int)$_GET['uid']);
			break;
			default:
				admin_teams();
		}
	} else {
		admin_teams();
	}
}
?>