<?php
function admin_topics() {
	global $db;
	$topics = array();
	$db->query('SELECT `tID`, `topicname`, `beschreibung`, `topicbild` FROM '.DB_PRE.'ecp_topics ORDER BY topicname ASC');
	while($row = $db->fetch_assoc()) {
		$topics[] = $row;
	}
	$tpl = new Smarty();
	$tpl->assign('topics', $topics);
	$tpl->assign('pics', get_topic_pics());
	ob_start();
	$tpl->display(DESIGN.'/tpl/admin/topics.html');
	$content = ob_get_contents();
	ob_end_clean();
	main_content(TOPICS, $content, '',1);
} 
function admin_topics_add() {
	global $db;
	ob_end_clean();
	$db->setMode(0);
	ajax_convert_array($_POST);
	if(@$_SESSION['rights']['admin']['topics']['add'] OR @$_SESSION['rights']['superadmin']) {
		if($_POST['topicname'] == '' OR !$_POST['topicbild']) {
			echo NOT_NEED_ALL_INPUTS;
		} else {
			if($db->query(sprintf('INSERT INTO '.DB_PRE.'ecp_topics (`topicname`, `beschreibung`, `topicbild`) VALUES (\'%s\', \'%s\',\'%s\')', strsave($_POST['topicname']), strsave($_POST['beschreibung']), strsave($_POST['topicbild'])))) {
				echo 'ok';
			}
		}
	} else {
		echo html_ajax_convert(NO_ADMIN_RIGHTS);
	}
	die();
}
function admin_topics_edit($id) {
	global $db;
	ob_end_clean();
	$db->setMode(0);
	ajax_convert_array($_POST);
	if(@$_SESSION['rights']['admin']['topics']['edit'] OR @$_SESSION['rights']['superadmin']) {
		if($_POST['topicname'] == '' OR !$_POST['topicbild']) {
			echo NOT_NEED_ALL_INPUTS;
		} else {
			if($db->query(sprintf('UPDATE '.DB_PRE.'ecp_topics SET `topicname` = \'%s\', `beschreibung` = \'%s\', `topicbild` = \'%s\' WHERE tID = %d', strsave($_POST['topicname']), strsave($_POST['beschreibung']), strsave($_POST['topicbild']), $id))) {
				echo 'ok';
			}
		}
	} else {
		echo html_ajax_convert(NO_ADMIN_RIGHTS);
	}
	die();
}
function admin_topics_del($id) {
	global $db;
	$db->setMode(0);
	ob_end_clean();
	if(@$_SESSION['rights']['admin']['topics']['edit'] OR @$_SESSION['rights']['superadmin']) {	
		$db->query('SELECT newsID FROM '.DB_PRE.'ecp_news WHERE topicID = '.$id);
		if($db->num_rows()) {
			$sql = 'DELETE FROM '.DB_PRE.'ecp_comments WHERE bereich = \'news\' AND (';
			while($row = $db->fetch_assoc()) {
				$sql .= 'subID = '.$row['newsID'].' OR ';
			}
			$db->query(substr($sql, 0, strlen($sql)-3).')');
			$db->query('DELETE FROM '.DB_PRE.'ecp_news WHERE topicID = '.$id);
		}
		if($db->query('DELETE FROM '.DB_PRE.'ecp_topics WHERE tID = '.$id)) {
			echo 'ok';
		}
	} else {
		echo html_ajax_convert(NO_ADMIN_RIGHTS);
	}
	die();
}
function get_topic_pics($pic = '') {
	$pics = '<option value="0">'.CHOOSE.'</option>';
	$folder = scan_dir('images/topics/', true);
	foreach($folder AS $value) {
		($pic == $value) ? $sub = 'selected' : $sub = '';
		$pics .= '<option '.$sub.' value="'.$value.'">'.$value.'</option>';
	}
	return $pics;
}
if (!isset($_SESSION['rights']['admin']['topics']) AND !isset($_SESSION['rights']['superadmin'])) {
	table(ERROR, NO_ADMIN_RIGHTS);
} else {
	if(isset($_GET['func'])) {
		switch($_GET['func']) {
			case 'add':
				admin_topics_add();
				break;
			case 'edit':
				admin_topics_edit((int)$_GET['id']);
				break;
			case 'del':
				admin_topics_del((int)$_GET['id']);
				break;												
			default:
				admin_topics();
		}
	} else {
		admin_topics();
	}
}
?>