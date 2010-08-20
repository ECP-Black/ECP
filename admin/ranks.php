<?php
function admin_ranks() {
	global $db;
	$tpl = new smarty;
	ob_start();
	$tpl->display(DESIGN.'/tpl/admin/ranks.html');
	$content = ob_get_contents();
	ob_end_clean();
	main_content(RANGS, $content, '',1);
	get_ranks();
}
function get_ranks() {
	global $db;
	if(!isset($_SESSION['rights']['admin']['ranks']) AND !isset($_SESSION['rights']['superadmin'])) {
		echo NO_ADMIN_RIGHTS;
	} else {
		$tpl = new smarty;
		if(@$_GET['ajax']) ob_end_clean();
		$ranks = array();
		$result = $db->query('SELECT rankID, rankname, iconname, abposts, fest, money FROM '.DB_PRE.'ecp_ranks ORDER BY fest DESC, abposts, rankname');
		while($row = mysql_fetch_assoc($result)) {
			$row['abposts'] = format_nr($row['abposts'], 0);
			$row['money'] = format_nr($row['money'], 2);
			$ranks[] = $row;
		}	
		$tpl->assign('ranks', $ranks);
		ob_start();
		$tpl->display(DESIGN.'/tpl/admin/ranks_overview.html');
		$content = ob_get_contents();
		ob_end_clean();		
		if(@$_GET['ajax']) { echo html_ajax_convert($content); die(); } 
		main_content(OVERVIEW, '<div id="ranks_overview">'.$content.'</div>', '',1);	
	}
}
function admin_ranks_add() {
	global $db;
	if(!isset($_SESSION['rights']['admin']['ranks']['add']) AND !isset($_SESSION['rights']['superadmin'])) {
		echo NO_ADMIN_RIGHTS;		
	} else {
		if(@$_FILES['rank']['tmp_name'] == '' OR $_POST['rankname'] == '') {
			table(ERROR, NOT_NEED_ALL_INPUTS);
			$tpl = new smarty;
			ob_start();
			$tpl->display(DESIGN.'/tpl/admin/ranks.html');
			$content = ob_get_contents();
			ob_end_clean();
			main_content(RANGS, $content, '',1);
			get_ranks();	
		} elseif ($_FILES['rank']['type'] != 'image/jpg' AND  $_FILES['rank']['type'] != 'image/gif'AND $_FILES['rank']['type'] != 'image/png' AND $_FILES['rank']['type'] != 'image/jpeg')	{
			table(ERROR, WRONG_FILE_TYPE);
			$tpl = new smarty;
			ob_start();
			$tpl->display(DESIGN.'/tpl/admin/ranks.html');
			$content = ob_get_contents();
			ob_end_clean();
			main_content(RANGS, $content, '',1);
			get_ranks();	
		} else {
			if(move_uploaded_file($_FILES['rank']['tmp_name'], 'images/ranks/'.str_replace(' ', '_',$_FILES['rank']['name']))) {
				umask(0);
				chmod('images/ranks/'.str_replace(' ', '_',$_FILES['rank']['name']), CHMOD);
				$sql = sprintf('INSERT INTO '.DB_PRE.'ecp_ranks (`rankname`, `iconname`, abposts, fest, money) 
								VALUES (\'%s\', \'%s\', %d, %d, %f)',
								strsave($_POST['rankname']),strsave(str_replace(' ', '_',$_FILES['rank']['name'])), (int)@$_POST['abposts'], (int)@$_POST['fest'], (float)str_replace(',','.', @$_POST['money']));
				if($db->query($sql)) {
					header1('?section=admin&site=ranks');
				}
			}
		}
	}
}
function admin_ranks_edit($id) {
	ob_end_clean();
	global $db;
	if(!isset($_SESSION['rights']['admin']['ranks']['edit']) AND !isset($_SESSION['rights']['superadmin'])) {
		echo NO_ADMIN_RIGHTS;
	} else {
		$db->setMode(0);
		ajax_convert_array($_POST);	
		$sql = sprintf('UPDATE '.DB_PRE.'ecp_ranks SET `rankname` = \'%s\',`abposts` = %d,`fest` = %d, money = %f WHERE rankID = %d',
						strsave($_POST['rankname']), (int)@$_POST['abposts'], (int)@$_POST['fest'], (float)str_replace(',','.', @$_POST['money']), $id);
		if($db->query($sql)) {
			echo 'ok';	
			update_all_ranks();
		}
	}
	die();
}
if (!isset($_SESSION['rights']['admin']['ranks']) AND !isset($_SESSION['rights']['superadmin'])) {
	table(ERROR, NO_ADMIN_RIGHTS);
} else {
	if(isset($_GET['func'])) {
		switch($_GET['func']) {
			case 'add':
				admin_ranks_add();
				break;
			case 'edit':
				admin_ranks_edit((int)$_GET['id']);
				break;	
			case 'get_ranks':
				get_ranks();
				break;											
			default:
				admin_ranks();
		}
	} else {
		admin_ranks();
	}
}
?>