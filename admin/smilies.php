<?php
function admin_smilies() {
	global $db;
	$tpl = new smarty;
	ob_start();
	$tpl->display(DESIGN.'/tpl/admin/smilies.html');
	$content = ob_get_contents();
	ob_end_clean();
	main_content(SMILIES, $content, '',1);
	get_smilies();
}
function get_smilies() {
	global $db;
	if(!isset($_SESSION['rights']['admin']['smilies']) AND !isset($_SESSION['rights']['superadmin'])) {
		echo NO_ADMIN_RIGHTS;
	} else {
		$tpl = new smarty;
		if(@$_GET['ajax']) ob_end_clean();
		$smilies = array();
		$result = $db->query('SELECT ID, filename, bedeutung FROM '.DB_PRE.'ecp_smilies ORDER BY ID ASC');
		while($row = mysql_fetch_assoc($result)) {
			$smilies[] = $row;
		}	
		$tpl->assign('smilies', $smilies);
		ob_start();
		$tpl->display(DESIGN.'/tpl/admin/smilies_overview.html');
		$content = ob_get_contents();
		ob_end_clean();		
		if(@$_GET['ajax']) { echo html_ajax_convert($content); die(); } 
		main_content(OVERVIEW, '<div id="smilie_overview">'.$content.'</div>', '',1);	
	}
}
function admin_smilies_add() {
	global $db;
	if(!isset($_SESSION['rights']['admin']['smilies']['add']) AND !isset($_SESSION['rights']['superadmin'])) {
		echo NO_ADMIN_RIGHTS;		
	} else {
		if(@$_FILES['smilie']['tmp_name'] == '') {
			table(ERROR, NOT_NEED_ALL_INPUTS);
			$tpl = new smarty;
			ob_start();
			$tpl->display(DESIGN.'/tpl/admin/smilies.html');
			$content = ob_get_contents();
			ob_end_clean();
			main_content(SMILIES, $content, '',1);
			get_smilies();	
		} elseif ($_FILES['smilie']['type'] != 'image/jpg' AND  $_FILES['smilie']['type'] != 'image/gif'AND $_FILES['smilie']['type'] != 'image/png' AND $_FILES['smilie']['type'] != 'image/jpeg')	{
			table(ERROR, WRONG_FILE_TYPE);
			$tpl = new smarty;
			ob_start();
			$tpl->display(DESIGN.'/tpl/admin/smilies.html');
			$content = ob_get_contents();
			ob_end_clean();
			main_content(SMILIES, $content, '',1);
			get_smilies();	
		} else {
			ajax_convert_array($_POST);
			ajax_convert_array($_FILES);
			if(move_uploaded_file($_FILES['smilie']['tmp_name'], 'images/smilies/'.str_replace(' ', '_',$_FILES['smilie']['name']))) {
				umask(0);
				chmod('images/smilies/'.str_replace(' ', '_',$_FILES['smilie']['name']), CHMOD);
				$sql = sprintf('INSERT INTO '.DB_PRE.'ecp_smilies (`bedeutung`, `filename`) 
								VALUES (\'%s\', \'%s\')',
								strsave($_POST['bedeutung']),strsave(str_replace(' ', '_',$_FILES['smilie']['name'])));
				if($db->query($sql)) {
					header1('?section=admin&site=smilies');
				}
			}
		}
	}
}
function admin_smilies_edit($id) {
	ob_end_clean();
	global $db;
	if(!isset($_SESSION['rights']['admin']['smilies']['edit']) AND !isset($_SESSION['rights']['superadmin'])) {
		echo NO_ADMIN_RIGHTS;
	} else {
		$db->setMode(0);
		ajax_convert_array($_POST);	
		$sql = sprintf('UPDATE '.DB_PRE.'ecp_smilies SET `bedeutung` = \'%s\'WHERE ID = %d',
						strsave($_POST['bedeutung']), $id);
		if($db->query($sql)) {
			echo 'ok';	
		}
	}
	die();
}
if (!isset($_SESSION['rights']['admin']['smilies']) AND !isset($_SESSION['rights']['superadmin'])) {
	table(ERROR, NO_ADMIN_RIGHTS);
} else {
	if(isset($_GET['func'])) {
		switch($_GET['func']) {
			case 'add':
				admin_smilies_add();
				break;
			case 'edit':
				admin_smilies_edit((int)$_GET['id']);
				break;	
			case 'get_smilies':
				get_smilies();
				break;											
			default:
				admin_smilies();
		}
	} else {
		admin_smilies();
	}
}
?>