<?php
function  survey() {
	global $db;
	$anzahl = $db->result(DB_PRE.'ecp_survey', 'COUNT(surveyID)', 'start <= '.time().' AND (access = "" OR '.$_SESSION['access_search'].')');
	if($anzahl) {
		$limits = get_sql_limit($anzahl, LIMIT_SURVEY);
		$result = $db->query('SELECT `surveyID`, `start`, `ende`, `frage`, sperre, antworten as maxvotes, COUNT(comID) as comments FROM `'.DB_PRE.'ecp_survey` LEFT JOIN '.DB_PRE.'ecp_comments ON (subID = surveyID AND bereich="survey") GROUP BY surveyID ORDER BY ende DESC LIMIT '.$limits[1].', '.LIMIT_SURVEY);
		$umfrage = array();
		while($row = mysql_fetch_assoc($result)) {
			$antworten = array();
			if(isset($_COOKIE['surveys'][$row['surveyID']]) AND $_COOKIE['surveys'][$row['surveyID']]) {
				if(($_COOKIE['surveys'][$row['surveyID']]+$row['sperre']) > time()) {
					$row['abstimmen'] = false;
				} else {
					$row['abstimmen'] = true;
				}
			} elseif(isset($_SESSION['userID'])) {
				$zeit = @$db->result(DB_PRE.'ecp_survey_votes', 'votedatum', 'userID = '.$_SESSION['userID'].' AND surID = '.$row['surveyID'].' ORDER BY votedatum DESC');
				if(((int)$zeit+$row['sperre']) > time()) {
					$row['abstimmen'] = false;
				} else {
					$row['abstimmen'] = true;
				}
			} else {
				$zeit = $db->result(DB_PRE.'ecp_survey_votes', 'votedatum', 'IP = \''.$_SERVER['REMOTE_ADDR'].'\' ORDER BY votedatum DESC');
				if(((int)$zeit+$row['sperre']) > time()) {
					$row['abstimmen'] = false;
				} else {
					$row['abstimmen'] = true;
				}	
			}
			$db->query('SELECT `answerID`, `answer`, `votes` FROM `'.DB_PRE.'ecp_survey_answers` WHERE sID = '.$row['surveyID'].' ORDER BY answerID ASC');
			$gesamt = 0;
			while($sub = $db->fetch_assoc()) {
				$gesamt += $sub['votes'];
				$antworten[] = $sub;
			}
			foreach($antworten AS $key => $value) {
				if($gesamt) {
					$antworten[$key]['prozent'] = round($value['votes']/$gesamt*100,1);
				} else {
					$antworten[$key]['prozent'] = 0;
				}
				$antworten[$key]['votes'] = number_format($value['votes'], 0,'','.');
			}
			$row['gesamt'] = number_format($gesamt, 0,'','.');
			$row['antworten'] = $antworten;
			if($row['start'] > time() OR $row['ende'] < time()) {
				$row['abstimmen'] = false;
			}
			$row['start'] = date(LONG_DATE, $row['start']);
			$row['ende'] = date(LONG_DATE, $row['ende']);			
			$umfrage[] = $row;
		}
		$tpl = new Smarty();
		$tpl->assign('survey', $umfrage);
		ob_start();
		$tpl->display(DESIGN.'/tpl/survey/overview.html');
		$content = ob_get_contents();
		ob_end_clean();
		main_content(SURVEYS, $content, '',1);
	} else {
		table(INFO, NO_ENTRIES);
	}
}
function survey_vote($id) {
	global $db;
	if(isset($_GET['ajax'])) {
		ob_end_clean();
		$db->setMode(0);
	}
	$umfrage = $db->fetch_assoc('SELECT `start`, `ende`, `antworten`, `sperre` FROM '.DB_PRE.'ecp_survey WHERE (access = "" OR '.$_SESSION['access_search'].') AND surveyID = '.$id);
	if(isset($umfrage['antworten'])) {
		$sperre = false;
		if(isset($_COOKIE['surveys'][$id]) AND $_COOKIE['surveys'][$id]) {
			if(($_COOKIE['surveys'][$id]+$umfrage['sperre']) > time()) {
				$zeit = $_COOKIE['surveys'][$id];
				$sperre = true;
			}
		} elseif(isset($_SESSION['userID'])) {
			$zeit = @$db->result(DB_PRE.'ecp_survey_votes', 'votedatum', 'userID = '.$_SESSION['userID'].' AND surID = '.$id.' ORDER BY votedatum DESC LIMIT 1');
			if(((int)$zeit+$umfrage['sperre']) > time()) {
				$sperre = true;
			}
		} else {
			$zeit = $db->result(DB_PRE.'ecp_survey_votes', 'votedatum', 'IP = \''.$_SERVER['REMOTE_ADDR'].'\' AND surID = '.$id.' ORDER BY votedatum DESC LIMIT 1');
			if(((int)$zeit+$umfrage['sperre']) > time()) {
				$sperre = true;
			}
		}		
		if($umfrage['start'] > time() OR $umfrage['ende'] < time()) {
			if(isset($_GET['ajax'])) {
				echo SURVEY_NOT_AKTIV;
			} else {
				table(ERROR, SURVEY_NOT_AKTIV);
				survey();
			}
		} elseif ($sperre) {
			if(isset($_GET['ajax'])) {
				echo str_replace('{zeit}', (($zeit+$umfrage['sperre']-time())/60), SURVEY_RELOAD_LOCK);
			} else {
				table(ERROR, str_replace('{zeit}', (($zeit+$umfrage['sperre']-time())/60), SURVEY_RELOAD_LOCK));
				survey();
			}
		} else {
			if($umfrage['antworten'] == 1) {
				$aid = (int)@$_POST['answer'];
				if($aid) {
					if($db->result(DB_PRE.'ecp_survey_answers', 'COUNT(answerID)', 'sID = '.$id.' AND answerID = '.$aid)) {
						if($db->query('UPDATE '.DB_PRE.'ecp_survey_answers SET votes = votes+1 WHERE sID = '.$id.' AND answerID = '.$aid)) {
							$db->query('INSERT INTO '.DB_PRE.'ecp_survey_votes (`surID`, `userID`, `IP`, `votedatum`) VALUES ('.$id.', '.(int)@$_SESSION['userID'].', \''.$_SERVER['REMOTE_ADDR'].'\', '.time().')');
							setcookie("survey[$id]", time(), (time()+365*86400));
							if(isset($_GET['ajax'])) {
								echo 'ok';
							} else {
								header1('?section=survey');
							}	
						}
					} else {
						if(isset($_GET['ajax'])) {
							echo SURVEY_CHOOSE_EQAL_ID;
						} else {
							table(ERROR, SURVEY_CHOOSE_EQAL_ID);
							survey();
						}	
					}	
				} else {
					if(isset($_GET['ajax'])) {
						echo SURVEY_MAKE_A_CHOOSE;
					} else {
						table(ERROR, SURVEY_MAKE_A_CHOOSE);
						survey();
					}
				}
			} else {
				$db->query('SELECT answerID FROM '.DB_PRE.'ecp_survey_answers WHERE sID = '.$id);
				$answers = array();
				while($row = $db->fetch_assoc()) {
					$answers[] = $row['answerID'];
				}
				$antworten = '';
				foreach($_POST AS $key =>$value) {
					if(strpos($key, 'answer_') !== false) {
						$key = (int)substr($key, strpos($key, '_' )+1);
						if(in_array($key, $answers)) {
							@$antworten .= ' OR answerID = '.$key;
							@$gesamt++;
						}
					}
				}
				if($gesamt > $umfrage['antworten']) {
					if(isset($_GET['ajax'])) {
						echo str_replace('{anzahl}', $umfrage['antworten'], SURVEY_TOO_MANY);
					} else {
						table(ERROR, str_replace('{anzahl}', $umfrage['antworten'], SURVEY_TOO_MANY));
						survey();
					}
				} elseif(strlen($antworten)) {
					if($db->query('UPDATE '.DB_PRE.'ecp_survey_answers SET votes = votes+1 WHERE sID = '.$id.' AND ('.substr($antworten, 4).')')) {
						$db->query('INSERT INTO '.DB_PRE.'ecp_survey_votes (`surID`, `userID`, `IP`, `votedatum`) VALUES ('.$id.', '.(int)@$_SESSION['userID'].', \''.$_SERVER['REMOTE_ADDR'].'\', '.time().')');
						setcookie("survey[$id]", time(), (time()+365*86400));
						if(isset($_GET['ajax'])) {
							echo 'ok';
						} else {
							header1('?section=survey');
						}	
					}
				} else {
					if(isset($_GET['ajax'])) {
						echo SURVEY_MAKE_A_CHOOSE;
					} else {
						table(ERROR, SURVEY_MAKE_A_CHOOSE);
						survey();
					}
				}
			}			
		}
	} else {
		if(isset($_GET['ajax'])) {
			echo NO_ENTRIES_ID;
		} else {
			table(ERROR, NO_ENTRIES_ID);
			survey();
		}
	}
	if(isset($_GET['ajax'])) {
		die();
	}
}
function survey_view($id) {
	global $db;
	$anzahl = $db->result(DB_PRE.'ecp_survey', 'COUNT(surveyID)', 'start <= '.time().' AND (access = "" OR '.$_SESSION['access_search'].') AND surveyID = '.$id);
	if($anzahl) {
		$limits = get_sql_limit($anzahl, LIMIT_SURVEY);
		$result = $db->query('SELECT `surveyID`, `start`, `ende`, `frage`, sperre, antworten as maxvotes, COUNT(comID) as comments FROM `'.DB_PRE.'ecp_survey` LEFT JOIN '.DB_PRE.'ecp_comments ON (subID = surveyID AND bereich="survey") WHERE surveyID = '.$id.' GROUP BY surveyID ORDER BY ende DESC LIMIT '.$limits[1].', '.LIMIT_SURVEY);
		$umfrage = array();
		while($row = mysql_fetch_assoc($result)) {
			$antworten = array();
			if(isset($_COOKIE['surveys'][$row['surveyID']]) AND $_COOKIE['surveys'][$row['surveyID']]) {
				if(($_COOKIE['surveys'][$row['surveyID']]+$row['sperre']) > time()) {
					$row['abstimmen'] = false;
				} else {
					$row['abstimmen'] = true;
				}
			} elseif(isset($_SESSION['userID'])) {
				$zeit = @$db->result(DB_PRE.'ecp_survey_votes', 'votedatum', 'userID = '.$_SESSION['userID'].' AND surID = '.$row['surveyID'].' ORDER BY votedatum DESC');
				if(((int)$zeit+$row['sperre']) > time()) {
					$row['abstimmen'] = false;
				} else {
					$row['abstimmen'] = true;
				}
			} else {
				$zeit = $db->result(DB_PRE.'ecp_survey_votes', 'votedatum', 'IP = \''.$_SERVER['REMOTE_ADDR'].'\' ORDER BY votedatum DESC');
				if(((int)$zeit+$row['sperre']) > time()) {
					$row['abstimmen'] = false;
				} else {
					$row['abstimmen'] = true;
				}	
			}
			$db->query('SELECT `answerID`, `answer`, `votes` FROM `'.DB_PRE.'ecp_survey_answers` WHERE sID = '.$row['surveyID'].' ORDER BY answerID ASC');
			$gesamt = 0;
			while($sub = $db->fetch_assoc()) {
				$gesamt += $sub['votes'];
				$antworten[] = $sub;
			}
			foreach($antworten AS $key => $value) {
				if($gesamt) {
					$antworten[$key]['prozent'] = round($value['votes']/$gesamt*100,1);
				} else {
					$antworten[$key]['prozent'] = 0;
				}
				$antworten[$key]['votes'] = number_format($value['votes'], 0,'','.');
			}
			$row['gesamt'] = number_format($gesamt, 0,'','.');
			$row['antworten'] = $antworten;
			if($row['start'] > time() OR $row['ende'] < time()) {
				$row['abstimmen'] = false;
			}
			$row['start'] = date(LONG_DATE, $row['start']);
			$row['ende'] = date(LONG_DATE, $row['ende']);			
			$umfrage[] = $row;
		}
		$tpl = new Smarty();
		$tpl->assign('survey', $umfrage);
		ob_start();
		$tpl->display(DESIGN.'/tpl/survey/overview.html');
		$content = ob_get_contents();
		ob_end_clean();
		main_content(SURVEY, $content, '',1);
	} else {
		table(INFO, NO_ENTRIES_ID);
	}
}
$conditions = array('LIMIT' 	=> LIMIT_COMMENTS,
					'ORDER'		=> COMMENTS_ORDER,
					'SPAM'		=> SPAM_SURVEY_COMMENTS,
					'section'   => 'survey');
if(isset($_GET['action'])) {
	switch($_GET['action']) {
		case 'viewsurvey':
			if(@$_SESSION['rights']['public']['survey']['com_view'] OR @$_SESSION['rights']['superadmin']) {
				survey_view((int)$_GET['id']);
				$conditions['action'] = 'add';
				$conditions['link'] = '?section=survey&action=viewsurvey&id='.(int)$_GET['id'];
				comments_get('survey', (int)$_GET['id'], $conditions);
			} else
				echo table(ACCESS_DENIED, NO_ACCESS_RIGHTS);
		break;
		case 'vote':
			if(@$_SESSION['rights']['public']['survey']['view'] OR @$_SESSION['rights']['superadmin']) {
				survey_vote((int)$_GET['id']);
			} else
				echo table(ACCESS_DENIED, NO_ACCESS_RIGHTS);
		break;		
		case 'addcomment':
			if(@$_SESSION['rights']['public']['survey']['com_add'] OR @$_SESSION['rights']['superadmin']) {		
				$conditions['action'] = 'add';
				$conditions['link'] = '?section=survey&action=viewsurvey&id='.(int)$_GET['id'];
				comments_add('survey', (int)$_GET['id'], $conditions);
			} else
				echo table(ACCESS_DENIED, NO_ACCESS_RIGHTS);			
		break;
		case 'editcomment':
			$conditions['action'] = 'edit';
			$conditions['link'] = '?section=survey&action=viewsurvey&id='.(int)$_GET['subid'];
			comments_edit('survey', (int)$_GET['subid'], (int)$_GET['id'], $conditions);		
		break;			
		default:
		if(@$_SESSION['rights']['public']['survey']['view'] OR @$_SESSION['rights']['superadmin'])
			survey();
		else
			echo table(ACCESS_DENIED, NO_ACCESS_RIGHTS);
	}
} else {
	if(@$_SESSION['rights']['public']['survey']['view'] OR @$_SESSION['rights']['superadmin'])
		survey();
	else
		echo table(ACCESS_DENIED, NO_ACCESS_RIGHTS);
}
?>