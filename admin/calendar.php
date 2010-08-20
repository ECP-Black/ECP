<?php
function admin_calendar() {
	global $db, $countries;
	$tpl = new smarty;
	$tpl->assign('events', get_events());
	$tpl->assign('lang', get_languages());
	$tpl->assign('rights', get_form_rights());
	ob_start();
	$tpl->display(DESIGN.'/tpl/admin/calendar.html');
	$content = ob_get_contents();
	ob_end_clean();
	main_content(CALENDAR, $content, '',1);
}
function admin_calendar_edit($id) {
	global $db;
	ob_end_clean();
	ajax_convert_array($_POST);
	if(@$_SESSION['rights']['admin']['calendar']['edit'] OR @$_SESSION['rights']['superadmin']) {	
		$db->setMode(0);
		$lang = array();
		foreach($_POST AS $key => $value) {
			if(strpos($key, 'cription_')) {
				$lang[substr($key,strpos($key, '_')+1)] = $value;
			}
		}						
		if($db->query(sprintf('UPDATE '.DB_PRE.'ecp_calendar SET `eventname` = \'%s\', `inhalt` = \'%s\', `access` = \'%s\', `datum` = %d, userID = %d WHERE calID = %d',
								strsave($_POST['eventname']), strsave(json_encode($lang)), strsave(admin_make_rights($_POST['rights'])), strtotime($_POST['datum']), $_SESSION['userID'], (int)$_GET['id']))) {
			echo 'ok';
		}
	} else {
		echo html_ajax_convert(NO_ADMIN_RIGHTS);
	}
	die();	
}
function admin_calendar_add() {
	global $db;
	ob_end_clean();
	ajax_convert_array($_POST);
	if(@$_SESSION['rights']['admin']['calendar']['add'] OR @$_SESSION['rights']['superadmin']) {	
		$db->setMode(0);
		$lang = array();
		foreach($_POST AS $key => $value) {
			if(strpos($key, 'cription_')) {
				$lang[substr($key,strpos($key, '_')+1)] = $value;
			}
		}						
		if($db->query(sprintf('INSERT INTO '.DB_PRE.'ecp_calendar (`eventname`, `inhalt`, `access`, `datum`, `userID`) 
								VALUES (\'%s\', \'%s\', \'%s\', %d, %d)', 
								strsave($_POST['eventname']), strsave(json_encode($lang)), strsave(admin_make_rights($_POST['rights'])), strtotime($_POST['datum']), $_SESSION['userID']))) {
			echo 'ok';
		}
	} else {
		echo html_ajax_convert(NO_ADMIN_RIGHTS);
	}
	die();
}
function get_events() {
	global $db;
	$events = array();
	$db->query('SELECT calID, eventname, datum FROM '.DB_PRE.'ecp_calendar ORDER BY datum DESC');
	while($row = $db->fetch_assoc()) {
		$row['datum'] = date(LONG_DATE, $row['datum']);
		$events[] = $row;
	}
	$tpl = new smarty;
	$tpl->assign('events', $events);	
	ob_start();
	$tpl->display(DESIGN.'/tpl/admin/calendar_overview.html');
	$content = ob_get_contents();
	ob_end_clean();
	if(isset($_GET['ajax'])) {
		ob_end_clean();
		echo html_ajax_convert($content);
		die();
	} else {
		return $content;
	}
}
if (!isset($_SESSION['rights']['admin']['calendar']) AND !isset($_SESSION['rights']['superadmin'])) {
	table(ERROR, NO_ADMIN_RIGHTS);
} else {
	if(isset($_GET['func'])) {
		switch($_GET['func']) {
			case 'edit':
				admin_calendar_edit((int)$_GET['id']);
			break;
			case 'add':
				admin_calendar_add();
			break;
			case 'getevents':
				get_events();
			break;															
			default:
				admin_calendar();
		}
	} else {
		admin_calendar();
	}
}
?>