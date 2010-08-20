<?php
function admin_cms() {
	global $db;
	$tpl = new Smarty();
	$tpl->assign('cms', get_cms());	
	$tpl->assign('lang', get_languages());	
	$tpl->assign('rights', get_form_rights(@$_POST['rights']));
	ob_start();
	$tpl->display(DESIGN.'/tpl/admin/cms.html');
	$content = ob_get_contents();
	ob_end_clean();
	main_content(OWN_SITES, $content, '',1);
} 
function get_cms() {
	global $db;
	$cms = array();
	$db->query('SELECT `cmsID`, `headline`, `views` FROM '.DB_PRE.'ecp_cms ORDER BY headline ASC');
	while($row = $db->fetch_assoc()) {
		$row['headline'] = json_decode($row['headline'], true);
		(isset($row['headline'][LANGUAGE])) ? $row['headline'] = $row['headline'][LANGUAGE] : $row['headline'] = $row['headline'][DEFAULT_LANG];
		$cms[] = $row;
	}
	$tpl = new Smarty();
	$tpl->assign('cms', $cms);
	ob_start();
	$tpl->display(DESIGN.'/tpl/admin/cms_overview.html');
	$content = ob_get_contents();
	ob_end_clean();
	if(isset($_GET['ajax'])) {
		ob_end_clean(); 
		echo html_ajax_convert($content); 
		die();
	}
	return $content;
}
function admin_cms_add() {
	global $db;
	ob_end_clean();
	ajax_convert_array($_POST);
	if(@$_SESSION['rights']['admin']['cms']['add'] OR @$_SESSION['rights']['superadmin']) {	
		$db->setMode(0);
		$lang = array();
		foreach($_POST AS $key => $value) {
			if(strpos($key, 'cription_')) {
				$lang[substr($key,strpos($key, '_')+1)] = $value;
			}
		}
		$head = array();
		foreach($_POST AS $key => $value) {
			if(strpos($key, 'eadline_')) {
				$head[substr($key,strpos($key, '_')+1)] = $value;
			}
		}							
		if($db->query(sprintf('INSERT INTO '.DB_PRE.'ecp_cms (`headline`, `content`, `access`) 
								VALUES (\'%s\', \'%s\', \'%s\')', 
								strsave(json_encode($head)), strsave(json_encode($lang)), strsave(admin_make_rights($_POST['rights']))))) {
			echo 'ok';
		}
	} else {
		echo html_ajax_convert(NO_ADMIN_RIGHTS);
	}
	die();
}
function admin_cms_edit($id) {
	global $db;
	ob_end_clean();
	$db->setMode(0);
	ajax_convert_array($_POST);
	if(@$_SESSION['rights']['admin']['cms']['edit'] OR @$_SESSION['rights']['superadmin']) {		
		$lang = array();
		foreach($_POST AS $key => $value) {
			if(strpos($key, 'cription_')) {
				$lang[substr($key,strpos($key, '_')+1)] = $value;
			}
		}
		$head = array();
		foreach($_POST AS $key => $value) {
			if(strpos($key, 'eadline_')) {
				$head[substr($key,strpos($key, '_')+1)] = $value;
			}
		}							
		if($db->query(sprintf('UPDATE '.DB_PRE.'ecp_cms SET `headline` = \'%s\', `content` = \'%s\', `access` = \'%s\' WHERE cmsID = %d', 
								strsave(json_encode($head)), strsave(json_encode($lang)), strsave(admin_make_rights($_POST['rights'])), $id))) {
			echo 'ok';
		}
	} else {
		echo html_ajax_convert(NO_ADMIN_RIGHTS);
	}	
	die();
}
if (!isset($_SESSION['rights']['admin']['cms']) AND !isset($_SESSION['rights']['superadmin'])) {
	table(ERROR, NO_ADMIN_RIGHTS);
} else {
	if(isset($_GET['func'])) {
		switch($_GET['func']) {
			case 'add':
				admin_cms_add();
				break;
			case 'edit':
				admin_cms_edit((int)$_GET['id']);
				break;	
			case 'getcms':
				get_cms();
			break;										
			default:
				admin_cms();
		}
	} else {
		admin_cms();
	}
}
?>