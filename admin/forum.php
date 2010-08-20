<?php
function admin_forum() {
	global $db;
	$anzahl = $db->result(DB_PRE.'ecp_forum_boards', 'COUNT(boardID)', '1');
	if($anzahl) {
		$db->query('SELECT `boardID`, `boardparentID`, `name`, `posi`, `isforum`, `beschreibung`, `closed` FROM '.DB_PRE.'ecp_forum_boards ORDER BY boardparentID, posi ASC');
		$foren = array();
		while($row = $db->fetch_assoc()) {
			if($row['isforum'] == 0) {
				$foren[$row['boardID']] = $row;
			} elseif ($row['isforum'] == 1 AND $row['boardparentID'] == 0) {
				$foren[$row['boardID']] = $row;
			} else {
			    $foren[$row['boardparentID']]['subs'][] = $row;
			}
		}
	}
	$tpl =new Smarty();
	$tpl->assign('foren', @$foren);
	ob_start();
	$tpl->display(DESIGN.'/tpl/admin/forum.html');
	$content = ob_get_contents();
	ob_end_clean();
	main_content(FORUM, $content, '',1);    	
}
function admin_forum_add() {
	global $db;
	if(@$_SESSION['rights']['admin']['forum']['add'] OR @$_SESSION['rights']['superadmin']) {	
		if(isset($_POST['submit'])) {
			if($_POST['name'] == '') {
				table(ERROR, NOT_NEED_ALL_INPUTS);
			} else {
				if($_POST['isforum'] == 0) $_POST['boardparentID'] = 0;
				$sql = sprintf('INSERT INTO '.DB_PRE.'ecp_forum_boards (`boardparentID`, `name`, `beschreibung`, `isforum`, `closed`, `attachments`, `attachmaxsize`, 
																	`rightsread`, `threadopen`, `postcom`, `editcom`, `startsurvey`, `votesurvey`, `attachfiles`, `downloadattch`, 
																	`threadclose`, `threaddel`, `threadmove`, `threadpin`, `editmocom`, `delcom`, `commentsperpost`, `moneyperpost`) VALUES 
																   (%d, \'%s\', \'%s\', %d, %d, %d, %d, \'%s\', \'%s\', \'%s\', \'%s\', \'%s\', \'%s\', \'%s\', \'%s\', \'%s\', \'%s\', \'%s\', \'%s\', \'%s\', \'%s\', %d, %f)',
																   $_POST['boardparentID'], strsave($_POST['name']), strsave($_POST['beschreibung']), $_POST['isforum'], $_POST['closed'], $_POST['attachments'], $_POST['attachmaxsize']*$_POST['modifkator'], 
																   admin_make_rights($_POST['rightsread']), admin_make_rights($_POST['threadopen']), admin_make_rights($_POST['postcom']), admin_make_rights($_POST['editcom']), 
																   admin_make_rights($_POST['startsurvey']), admin_make_rights($_POST['votesurvey']), admin_make_rights($_POST['attachfiles']), admin_make_rights($_POST['downloadattch']), 
																   admin_make_rights($_POST['threadclose']), admin_make_rights($_POST['threaddel']), admin_make_rights($_POST['threadmove']), admin_make_rights($_POST['threadpin']), 
																   admin_make_rights($_POST['editmocom']), admin_make_rights($_POST['delcom']), $_POST['commentsperpost'], str_replace(',', '.', $_POST['moneyperpost']));			
				if($db->query($sql)) {
					header1('?section=admin&site=forum');
				}
			}
		} else {
			$tpl = new smarty;
			$db->query('SELECT groupID, name FROM '.DB_PRE.'ecp_groups ORDER by name ASC');
			$gruppen = array();
			while($row = $db->fetch_assoc()) {
				$gruppen[] =$row;
			}	
			$db->query('SELECT boardID, name FROM '.DB_PRE.'ecp_forum_boards WHERE isforum = 0 ORDER BY name ASC');
			$boards = '';
			while($row = $db->fetch_assoc()) {
				$boards .= '<option value="'.$row['boardID'].'">'.$row['name'].'</option>';
			}
			$tpl->assign('boards', $boards);
			$tpl->assign('rightsread', forum_make_rights($gruppen));		
			$tpl->assign('threadopen', forum_make_rights($gruppen));
			$tpl->assign('postcom', forum_make_rights($gruppen));		
			$tpl->assign('editcom', forum_make_rights($gruppen));
			$tpl->assign('startsurvey', forum_make_rights($gruppen));		
			$tpl->assign('votesurvey', forum_make_rights($gruppen));
			$tpl->assign('attachfiles', forum_make_rights($gruppen, array(1)));		
			$tpl->assign('downloadattch', forum_make_rights($gruppen, array(1)));
			$tpl->assign('threadclose', forum_make_rights($gruppen, array(1)));	
			$tpl->assign('threaddel', forum_make_rights($gruppen, array(1)));
			$tpl->assign('threadmove', forum_make_rights($gruppen, array(1)));		
			$tpl->assign('threadpin', forum_make_rights($gruppen, array(1)));									
			$tpl->assign('editmocom', forum_make_rights($gruppen, array(1)));												
			$tpl->assign('delcom', forum_make_rights($gruppen, array(1)));	
			$tpl->assign('url', 'add');
			ob_start();
			$tpl->display(DESIGN.'/tpl/admin/forum_add_edit.html');
			$content = ob_get_contents();
			ob_end_clean();
			main_content(FORUM_ADD, $content, '',1);  		
		}
	} else {
		table(ERROR, NO_ADMIN_RIGHTS);
	}
}
function admin_forum_edit($id) {
	global $db;
	if(@$_SESSION['rights']['admin']['forum']['edit'] OR @$_SESSION['rights']['superadmin']) {	
		if(isset($_POST['submit'])) {
			if($_POST['name'] == '') {
				table(ERROR, NOT_NEED_ALL_INPUTS);
			} else {
				$sql = sprintf('UPDATE '.DB_PRE.'ecp_forum_boards SET 
										`boardparentID` = %d,
										`name` = \'%s\',
										`beschreibung` = \'%s\',
										`closed` = %d,
										`attachments` = %d,
										`attachmaxsize` = %d,					
										`rightsread` = \'%s\',
										`threadopen` = \'%s\',
										`postcom` = \'%s\',
										`editcom` = \'%s\',
										`startsurvey` = \'%s\',
										`votesurvey` = \'%s\',
										`attachfiles` = \'%s\',
										`downloadattch` = \'%s\',
										`threadclose` = \'%s\',
										`threaddel` = \'%s\',
										`threadmove` = \'%s\',
										`threadpin` = \'%s\',
										`editmocom` = \'%s\',
										`delcom` = \'%s\',
										`commentsperpost` = %d,
										`moneyperpost` = %f
										WHERE boardID = %d',
										$_POST['boardparentID'], strsave($_POST['name']), strsave($_POST['beschreibung']), $_POST['closed'], $_POST['attachments'], $_POST['attachmaxsize']*$_POST['modifkator'], 
										admin_make_rights($_POST['rightsread']), admin_make_rights($_POST['threadopen']), admin_make_rights($_POST['postcom']), admin_make_rights($_POST['editcom']), 
										admin_make_rights($_POST['startsurvey']), admin_make_rights($_POST['votesurvey']), admin_make_rights($_POST['attachfiles']), admin_make_rights($_POST['downloadattch']), 
										admin_make_rights($_POST['threadclose']), admin_make_rights($_POST['threaddel']), admin_make_rights($_POST['threadmove']), admin_make_rights($_POST['threadpin']), 
										admin_make_rights($_POST['editmocom']), admin_make_rights($_POST['delcom']), $_POST['commentsperpost'], str_replace(',', '.', $_POST['moneyperpost']), $id);			
				if($db->query($sql)) {
					header1('?section=admin&site=forum');
				}
			}
		} else {
			$tpl = new smarty;
			$boardinfos = $db->fetch_assoc('SELECT `boardparentID`, `beschreibung`, `name`, `isforum`, `closed`, `commentsperpost`, `moneyperpost`, `attachments`, `attachmaxsize`, `rightsread`, `threadopen`, `postcom`, `editcom`, `startsurvey`, `votesurvey`, `attachfiles`, `downloadattch`, `threadclose`, `threaddel`, `threadmove`, `threadpin`, `editmocom`, `delcom` FROM '.DB_PRE.'ecp_forum_boards WHERE boardID = '.$id);
			$tpl->assign('beschreibung', $boardinfos['beschreibung']);
			$tpl->assign('isforum', $boardinfos['isforum']);
			$tpl->assign('closed', $boardinfos['closed']);
			$tpl->assign('commentsperpost', $boardinfos['commentsperpost']);
			$tpl->assign('moneyperpost', $boardinfos['moneyperpost']);
			$tpl->assign('attachments', $boardinfos['attachments']);
			$tpl->assign('attachmaxsize', $boardinfos['attachmaxsize']);
			$tpl->assign('name', $boardinfos['name']);
			$db->query('SELECT groupID, name FROM '.DB_PRE.'ecp_groups ORDER by name ASC');
			$gruppen = array();
			while($row = $db->fetch_assoc()) {
				$gruppen[] =$row;
			}	
			$db->query('SELECT boardID, name FROM '.DB_PRE.'ecp_forum_boards WHERE isforum = 0 ORDER BY name ASC');
			$boards = '';
			while($row = $db->fetch_assoc()) {
				$boards .= '<option '.($boardinfos['boardparentID'] == $row['boardID'] ? 'selected="selected" ' :'').'value="'.$row['boardID'].'">'.$row['name'].'</option>';
			}
			$tpl->assign('boards', $boards);
			$tpl->assign('rightsread', forum_make_rights($gruppen, explode(',', substr($boardinfos['rightsread'], 1, strlen($boardinfos['rightsread'])-2))));		
			$tpl->assign('threadopen', forum_make_rights($gruppen, explode(',', substr($boardinfos['threadopen'], 1, strlen($boardinfos['threadopen'])-2))));	
			$tpl->assign('postcom', forum_make_rights($gruppen, explode(',', substr($boardinfos['postcom'], 1, strlen($boardinfos['postcom'])-2))));		
			$tpl->assign('editcom', forum_make_rights($gruppen, explode(',', substr($boardinfos['editcom'], 1, strlen($boardinfos['editcom'])-2))));	
			$tpl->assign('startsurvey', forum_make_rights($gruppen, explode(',', substr($boardinfos['startsurvey'], 1, strlen($boardinfos['startsurvey'])-2))));	
			$tpl->assign('votesurvey', forum_make_rights($gruppen, explode(',', substr($boardinfos['votesurvey'], 1, strlen($boardinfos['votesurvey'])-2))));	
			$tpl->assign('attachfiles', forum_make_rights($gruppen, explode(',', substr($boardinfos['attachfiles'], 1, strlen($boardinfos['attachfiles'])-2))));		
			$tpl->assign('downloadattch', forum_make_rights($gruppen, explode(',', substr($boardinfos['downloadattch'], 1, strlen($boardinfos['downloadattch'])-2))));	
			$tpl->assign('threadclose', forum_make_rights($gruppen, explode(',', substr($boardinfos['threadclose'], 1, strlen($boardinfos['threadclose'])-2))));	
			$tpl->assign('threaddel', forum_make_rights($gruppen, explode(',', substr($boardinfos['threaddel'], 1, strlen($boardinfos['threaddel'])-2))));	
			$tpl->assign('threadmove', forum_make_rights($gruppen, explode(',', substr($boardinfos['threadmove'], 1, strlen($boardinfos['threadmove'])-2))));		
			$tpl->assign('threadpin', forum_make_rights($gruppen, explode(',', substr($boardinfos['threadpin'], 1, strlen($boardinfos['threadpin'])-2))));										
			$tpl->assign('editmocom', forum_make_rights($gruppen, explode(',', substr($boardinfos['editmocom'], 1, strlen($boardinfos['editmocom'])-2))));												
			$tpl->assign('delcom', forum_make_rights($gruppen, explode(',', substr($boardinfos['delcom'], 1, strlen($boardinfos['delcom'])-2))));	
			$tpl->assign('url', 'edit&id='.$id);
			ob_start();
			$tpl->display(DESIGN.'/tpl/admin/forum_add_edit.html');
			$content = ob_get_contents();
			ob_end_clean();
			main_content(FORUM_ADD, $content, '',1);  		
		}
	} else {
		table(ERROR, NO_ADMIN_RIGHTS);
	}	
}
function forum_make_rights($array, $makiert = array()) {
	global $groups;
	(count($makiert) AND $makiert[0] != '') ? $sub = '' : $sub = 'selected ';
	$string = '<option '.$sub.'value="all">'.ALL.'</option>';
	foreach($array AS $key => $value) {
		if(isset($groups[$value['name']])) $value['name'] = $groups[$value['name']];
		(in_array($value['groupID'], $makiert)) ? $sub = 'selected ' : $sub = '';
		$string .= '<option '.$sub.'value="'.$value['groupID'].'">'.$value['name'].'</option>';	
	}
	return $string;
}
function admin_forum_delete($id) {
	global $db;
	ob_end_clean();
	$db->setMode(0);
	if(isset($_SESSION['rights']['admin']['forum']['del']) OR isset($_SESSION['rights']['superadmin'])) {
		$result = $db->query('SELECT boardID FROM '.DB_PRE.'ecp_forum_boards WHERE boardparentID = '.$id.' OR boardID = '.$id);
		while($row = mysql_fetch_assoc($result)) {
			$db->query('DELETE FROM '.DB_PRE.'ecp_forum_abo WHERE boID = '.$row['boardID']);
			$db->query('DELETE FROM '.DB_PRE.'ecp_forum_boards WHERE boardID = '.$row['boardID']);
			$db->query('DELETE FROM '.DB_PRE.'ecp_forum_comments WHERE boardID = '.$row['boardID']);
			$db->query('DELETE FROM '.DB_PRE.'ecp_forum_threads WHERE bID = '.$row['boardID']);
			$db->query('DELETE FROM '.DB_PRE.'ecp_forum_ratings WHERE bID = '.$row['boardID']);
			$db->query('DELETE FROM '.DB_PRE.'ecp_forum_ratings WHERE bID = '.$row['boardID']);
			$db->query('SELECT attachID, strname FROM '.DB_PRE.'ecp_forum_attachments WHERE bID = '.$row['boardID']);
			while($sub = $db->fetch_assoc()) {
				@unlink('uploads/forum/'.$sub['attachID'].'_'.$sub['strname']);
			}	
			$db->query('DELETE FROM '.DB_PRE.'ecp_forum_attachments WHERE bID = '.$row['boardID']);		
			$subresult = $db->query('SELECT fsurveyID FROM '.DB_PRE.'ecp_forum_survey WHERE boardID = '.$row['boardID']);
			while($sub = mysql_fetch_array($subresult)) {
				$db->query('DELETE FROM '.DB_PRE.'ecp_forum_survey_answers WHERE fsID = '.$sub['fsurveyID']);
				$db->query('DELETE FROM '.DB_PRE.'ecp_forum_survey_votes WHERE fsurID = '.$sub['fsurveyID']);
			}
			$db->query('DELETE FROM '.DB_PRE.'ecp_forum_survey WHERE boardID = '.$row['boardID']);	
		}
		if(!$db->errorNum()) {
			echo 'ok';
		}
	} else {
		echo  html_ajax_convert(NO_ADMIN_RIGHTS);
	}
	die();
}
if (!isset($_SESSION['rights']['admin']['forum']) AND !isset($_SESSION['rights']['superadmin'])) {
	table(ERROR, NO_ADMIN_RIGHTS);
} else {
	if(isset($_GET['func'])) {
		switch($_GET['func']) {
			case 'add':
				admin_forum_add();
			break;
			case 'edit':
				admin_forum_edit((int)$_GET['id']);
			break;	
			case 'del':
				admin_forum_delete((int)$_GET['id']);
			break;
			case 'close':
				admin_forum_close((int)$_GET['id']);
			break;	
			default:
				admin_forum();
		}
	} else {
		admin_forum();
	}
}
?>