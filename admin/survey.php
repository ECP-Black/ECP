<?php
function admin_survey() {
	global $db, $groups;
	$anzahl = $db->result(DB_PRE.'ecp_survey', 'COUNT(surveyID)', '1');
	if($anzahl) {
		$limits = get_sql_limit($anzahl, LIMIT_SURVEY);
		$db->query('SELECT `surveyID`, `start`, `ende`, `frage` FROM `'.DB_PRE.'ecp_survey` ORDER BY ende DESC LIMIT '.$limits[1].', '.LIMIT_SURVEY);
		$umfrage = array();
		while($row = $db->fetch_assoc()) {
			if($row['start'] > time()) {
				$row['status']	= PLANNED;
				$row['closed'] = 1;
			} elseif ($row['ende'] < time()) {
				$row['status'] = CLOSED;
				$row['closed'] = 1;
			} else {
				$row['status'] = RUN;
			}
			$row['start'] = date(LONG_DATE, $row['start']);
			$row['ende'] = date(LONG_DATE, $row['ende']);
			$umfrage[] = $row;
		}
	}
	$tpl = new smarty;
	$db->query('SELECT groupID, name FROM '.DB_PRE.'ecp_groups ORDER by name ASC');
	$rights = '<option value="all" selected="selected">'.ALL.'</option>';
	while($row = $db->fetch_assoc()) {
		if(isset($groups[$row['name']])) $row['name'] = $groups[$row['name']];
		$rights .= '<option value="'.$row['groupID'].'">'.$row['name'].'</option>';
	}	
	$tpl->assign('rights', $rights);
	$tpl->assign('anzahl', $anzahl);	
	$tpl->assign('umfrage', @$umfrage);
	$tpl->assign('pages', @$limits[0]);
	ob_start();
	$tpl->display(DESIGN.'/tpl/admin/survey.html');
	$tpl->display(DESIGN.'/tpl/admin/survey_overview.html');
	$content = ob_get_contents();
	ob_end_clean();
	main_content(SURVEY, $content, '',1);    	
}
function admin_survey_add() {
		global $db;
		ob_end_clean();
		ajax_convert_array($_POST);
	if(@$_SESSION['rights']['admin']['awards']['edit'] OR @$_SESSION['rights']['superadmin']) {			
		if($_POST['frage'] == '' OR !strtotime($_POST['start']) OR !strtotime($_POST['ende']) OR $_POST['sperre'] == '' OR (int)$_POST['antworten'] < 1 OR !count($_POST['rights']) OR count($_POST) < 8 OR $_POST['answer_1'] == '') {
			echo NOT_NEED_ALL_INPUTS;
		} elseif (strtotime($_POST['ende']) < time()) {
			echo SURVEY_END_TIME_FALSE;
		} elseif (strtotime($_POST['ende']) < strtotime($_POST['start'])) {
			echo SURVEY_START_BIG_END;		
		} else {
			if(in_array('all', $_POST['rights'])) {
				$rights = '';
			} else {
				$rights = ',';
				foreach($_POST['rights'] AS $key) {
					$rights .= (int)$key.',';
				}
			}		
			$sql = sprintf('INSERT INTO '.DB_PRE.'ecp_survey (`start`, `ende`, `frage`, `antworten`, `sperre`, `access`) VALUES (%d, %d, \'%s\', %d, %d, \'%s\')',  
										strtotime($_POST['start']),strtotime($_POST['ende']),strsave($_POST['frage']), (int)$_POST['antworten'], (int)$_POST['sperre']*(int)$_POST['multi'], strsave($rights));
			if($db->query($sql)) {
				$id = $db->last_id();
				foreach($_POST as $key =>$value) {
					if(strpos($key,'answer_') !== false AND $value != '') {
						$db->query(sprintf('INSERT INTO '.DB_PRE.'ecp_survey_answers (`sID`, `answer`) VALUES (%d, \'%s\')', $id, strsave($value)));
					}
				}
				echo 'ok';
			}
		}
	} else {
		echo html_ajax_convert(NO_ADMIN_RIGHTS);
	}
	die();
}
function admin_survey_edit($id) {
	ob_end_clean();
	ajax_convert_array($_POST);
	if(@$_SESSION['rights']['admin']['awards']['edit'] OR @$_SESSION['rights']['superadmin']) {		
		global $db;
		$db->setMode(0);
		if($_POST['frage'] == '' OR !strtotime($_POST['start']) OR !strtotime($_POST['ende']) OR $_POST['sperre'] == '' OR (int)$_POST['antworten'] < 1 OR !count($_POST['rights']) OR count($_POST) < 8) {
			echo NOT_NEED_ALL_INPUTS;
		} elseif (strtotime($_POST['ende']) < strtotime($_POST['start'])) {
			echo SURVEY_START_BIG_END;		
		} else {
			if(in_array('all', $_POST['rights'])) {
				$rights = '';
			} else {
				$rights = ',';
				foreach($_POST['rights'] AS $key) {
					$rights .= (int)$key.',';
				}
			}		
			$sql = sprintf('UPDATE '.DB_PRE.'ecp_survey SET `start` = %d, `ende` = %d, `frage` = \'%s\', `antworten` =%d, `sperre` = %d, `access` = \'%s\' WHERE surveyID = %d',  
										strtotime($_POST['start']),strtotime($_POST['ende']),strsave($_POST['frage']), (int)$_POST['antworten'], (int)$_POST['sperre']*(int)$_POST['multi'], strsave($rights), $id);
			if($db->query($sql)) {
				foreach($_POST as $key =>$value) {
					if(strpos($key,'answer_') !== false AND $value != '') {
						if(strpos($key, '_old_')) {
							$nr = substr($key, 11);
							$db->query(sprintf('UPDATE '.DB_PRE.'ecp_survey_answers SET `answer` = \'%s\', votes = %d WHERE sID = %d AND answerID = %d', strsave($value), (int)$_POST['votes_'.$nr], $id, (int)$nr));
						} else {
							$db->query(sprintf('INSERT INTO '.DB_PRE.'ecp_survey_answers (`sID`, `answer`) VALUES (%d, \'%s\')', $id, strsave($value)));
						}					
					}
				}
				echo 'ok';
			}
		}
	} else {
		echo html_ajax_convert(NO_ADMIN_RIGHTS);
	}
	die();
}
if (!isset($_SESSION['rights']['admin']['survey']) AND !isset($_SESSION['rights']['superadmin'])) {
	table(ERROR, NO_ADMIN_RIGHTS);
} else {
	if(isset($_GET['func'])) {
		switch($_GET['func']) {
			case 'add':
				admin_survey_add();
			break;
			case 'edit':
				admin_survey_edit((int)$_GET['id']);
			break;	
			case 'del':
				admin_survey_delete((int)$_GET['id']);
			break;
			default:
				admin_survey();
		}
	} else {
		admin_survey();
	}
}
?>