<?php
function admin_downloads() {
	global $db;
	$tpl = new smarty;
	$tpl->assign('lang', get_languages());
	$tpl->assign('rights', get_form_rights(@$_POST['rights']));
	$tpl->assign('kate', download_get_cate(@$_POST['subID']));
	$db->query('SELECT name, dID FROM '.DB_PRE.'ecp_downloads ORDER BY name ASC');
	$dl = '<option value="0">'.CHOOSE.'</option>';
	while($row = $db->fetch_assoc()) {
		$dl .= '<option value="'.$row['dID'].'">'.$row['name'].'</option>';
	}
	$tpl->assign('dls', $dl);
	//foreach($_POST AS $key=>$value) $tpl->assign($key, $value);
	ob_start();
	$tpl->display(DESIGN.'/tpl/admin/downloads.html');
	$content = ob_get_contents();
	ob_end_clean();
	main_content(DOWNLOADS, $content, '',1);
}
function download_get_cate($id = 0) {
	global $db;
	$result = $db->query('SELECT a.kname, a.kID, a.subkID, COUNT(b.kID) as subs
					FROM '.DB_PRE.'ecp_downloads_kate a 
					LEFT JOIN '.DB_PRE.'ecp_downloads_kate b ON a.kID = b.subkID
					WHERE a.subkID = 0					
					GROUP BY a.kID
					ORDER BY a.kname ASC');
	$kate = '<option value="0"></option>';
	$array = array();
	while($row = mysql_fetch_assoc($result)) {
		($id == $row['kID']) ? $sel = 'selected' : $sub = '';
		$kate .= '<option '.$sub.' value="'.$row['kID'].'">'.$row['kname'].'</option>';
		if($row['subs']) {
			$kate .= getSubDL($row['kID'], 0, $id);
		}
	}
	return $kate;
}
function download_get_cate_ajax($id = 0) {
	global $db;
	$result = $db->query('SELECT a.kname, a.kID, a.subkID, COUNT(b.kID) as subs
					FROM '.DB_PRE.'ecp_downloads_kate a 
					LEFT JOIN '.DB_PRE.'ecp_downloads_kate b ON a.kID = b.subkID
					WHERE a.subkID = 0					
					GROUP BY a.kID
					ORDER BY a.kname ASC');
	$kate = '<option value="0"></option>';
	$array = array(array('value'=>0, 'name'=> CHOOSE, 'selected'=>false));
	while($row = mysql_fetch_assoc($result)) {
		($id == $row['kID']) ? $sel = 'selected' : $sub = '';
		$array[] = array('value'=>$row['kID'], 'name'=> $row['kname'], 'selected'=>(($id == $row['kID']) ? true : false));
		if($row['subs']) {
			$spe =  getSubDL_ajax($row['kID'], 0, $id);
			foreach($spe as $value) $array[] = $value;
		}
	}
	html_convert_array($array);
	echo json_encode($array);	
}
function admin_downloads_addkate() {
	global $db;
	ob_end_clean();
	$db->setMode(0);
	ajax_convert_array($_POST);
	if(@$_SESSION['rights']['admin']['downloads']['kate_add'] OR @$_SESSION['rights']['superadmin']) {	
		if($_POST['kname'] == '') {
			// Info hier einfügen
			admin_downloads();
		} else {
			$lang = array();
			foreach($_POST AS $key => $value) {
				if(strpos($key, 'cription_')) {
					$lang[substr($key,strpos($key, '_')+1)] = $value;
				}
			}
			$sql = 'INSERT INTO '.DB_PRE.'ecp_downloads_kate (`subkID`, `kname`, `beschreibung`, `access`) VALUES ('.(int)$_POST['subID'].', \''.strsave($_POST['kname']).'\', \''.strsave(json_encode($lang)).'\',\''.strsave(admin_make_rights($_POST['rights'])).'\')';
			if($db->query($sql)) {
				echo 'ok';
			}
		}
	} else {
		echo NO_ADMIN_RIGHTS;
	}
	die();
}
function getSubDL($oberkat, $ebene, $id) {
	global $db;
	$result = $db->query('SELECT a.kname, a.kID, a.subkID, COUNT(b.kID) as subs
					FROM '.DB_PRE.'ecp_downloads_kate a 
					LEFT JOIN '.DB_PRE.'ecp_downloads_kate b ON a.kID = b.subkID
					WHERE a.subkID= '.$oberkat.'					
					GROUP BY a.kID
					ORDER BY a.kname ASC');
	$menu = '';
	$subs = '';
	for($i=0;$i<$ebene; $i++) $subs .= '&nbsp;&nbsp;';
	$subs .= '|- ';
	$ebene++;
	while($row = mysql_fetch_assoc($result)) {
		($id == $row['kID']) ? $sel = 'selected' : $sub = '';
		$menu .= '<option '.$sub.' value="'.$row['kID'].'">'.$subs.$row['kname'].'</option>';
		if($row['subs']) {
			$menu .= getSubDL($row['kID'], $ebene, $id);
		}
	}
	return $menu;
}
function getSubDL_ajax($oberkat, $ebene, $id) {
	global $db;
	$result = $db->query('SELECT a.kname, a.kID, a.subkID, COUNT(b.kID) as subs
					FROM '.DB_PRE.'ecp_downloads_kate a 
					LEFT JOIN '.DB_PRE.'ecp_downloads_kate b ON a.kID = b.subkID
					WHERE a.subkID= '.$oberkat.'					
					GROUP BY a.kID
					ORDER BY a.kname ASC');
	$array = array();
	$subs = '';
	for($i=0;$i<$ebene; $i++) $subs .= '&nbsp;&nbsp;';
	$subs .= '|- ';
	$ebene++;
	while($row = mysql_fetch_assoc($result)) {
		($id == $row['kID']) ? $sel = 'selected' : $sub = '';
		$array[] = array('value'=>$row['kID'], 'name'=> $subs.$row['kname'], 'selected'=>(($id == $row['kID']) ? true : false));
		if($row['subs']) {
			$spe = getSubDL_ajax($row['kID'], $ebene, $id);
			foreach($spe as $value) $array[] = $value;
		}
	}	
	return $array;
}
function admin_downloads_add() {
	global $db;
	ob_end_clean();
	ajax_convert_array($_POST);
	if(@$_SESSION['rights']['admin']['downloads']['add'] OR @$_SESSION['rights']['superadmin']) {
		if($_POST['name'] == '' OR $_POST['url'] == '' OR $_POST['size'] == '' OR !$_POST['cID']) {
			echo html_ajax_convert(NOT_NEED_ALL_INPUTS);
		} else {
			$lang = array();
			foreach($_POST AS $key => $value) {
				if(strpos($key, 'cription_')) {
					$lang[substr($key,strpos($key, '_',14)+1)] = $value;
				}
			}
			$sql = sprintf('INSERT INTO '.DB_PRE.'ecp_downloads (`cID`, `name`, `url`, `userID`, `info`, `homepage`, `version`, `size`, `downloads`, `datum`, `access`)
					 VALUES (%d, \'%s\', \'%s\', %d, \'%s\', \'%s\', \'%s\', %d, %d, %d, \'%s\')', 
					 @$_POST['cID'], strsave(@$_POST['name']), strsave(@$_POST['url']), $_SESSION['userID'], strsave(json_encode($lang)), strsave(check_url(@$_POST['homepage'])), strsave(@$_POST['version']), ((int)@$_POST['size']*@$_POST['modifkator']), (int)@$_POST['downloads'], time(), admin_make_rights(@$_POST['rights']));
			$db->setMode(0);
			if($db->query($sql)) {
				echo 'ok';	
			}
		}
		die();
	} else {
		echo html_ajax_convert(NO_ADMIN_RIGHTS);
	}
}
function admin_downloads_edit($id) {
	global $db;
	ob_end_clean();
	if($_POST['name'] == '' OR $_POST['url'] == '' OR $_POST['size'] == '' OR !$_POST['cID']) {
		echo html_ajax_convert(NOT_NEED_ALL_INPUTS);
	} else {
		ajax_convert_array($_POST);
		$lang = array();
		foreach($_POST AS $key => $value) {
			if(strpos($key, 'cription_')) {
				$lang[substr($key,strpos($key, '_',14)+1)] = $value;
			}
		}
		$sql = sprintf('UPDATE '.DB_PRE.'ecp_downloads  SET 
						`cID` = %d, 
						`name` = \'%s\', 
						`url` = \'%s\', 
						`userID` =  %d, 
						`info` = \'%s\', 
						`homepage` = \'%s\', 
						`version` = \'%s\', 
						`size` = %d,
						`downloads` = %d,
						`access`  = \'%s\'
				 WHERE dID = %d', 
				 $_POST['cID'], strsave($_POST['name']), strsave($_POST['url']), $_SESSION['userID'], strsave(json_encode($lang)), strsave(check_url($_POST['homepage'])), strsave($_POST['version']), ((int)$_POST['size']*$_POST['modifkator']), (int)$_POST['downloads'], admin_make_rights($_POST['rights']), (int)$_GET['id']);
		$db->setMode(0);
		if($db->query($sql)) {
			echo 'ok';	
		}
	}
	die();
}
function admin_downloads_kate_del($id) {
	global $db;
	if(@$_SESSION['rights']['admin']['downloads']['del'] OR @$_SESSION['rights']['superadmin']) {
		ob_end_clean();
		$db->query('SELECT kID FROM '.DB_PRE.'ecp_downloads_kate WHERE kID = '.$id);
		if($db->num_rows()) {
			del_sub_kate($id);
			echo 'ok';
			die();					
		} else {
			echo htmlentities(NO_ENTRIES_ID);
		}
	} else {
		echo htmlentities(NO_ADMIN_RIGHTS);    	
	}	
}
function del_sub_kate($id) {
	global $db;
	$result = $db->query('SELECT dID FROM '.DB_PRE.'ecp_downloads WHERE cID = '.$id);
	while($row = mysql_fetch_assoc($result)) { 
		$db->query('DELETE FROM '.DB_PRE.'ecp_comments WHERE subID = '.$row['dID'].' AND bereich = "downloads"');
		$db->query('DELETE FROM '.DB_PRE.'ecp_downloads WHERE dID = '.$row['dID']);
	}
	$db->query('DELETE FROM '.DB_PRE.'ecp_downloads_kate WHERE kID = '.$id);
	$result = $db->query('SELECT a.kID, COUNT(b.kID) as subs
					FROM '.DB_PRE.'ecp_downloads_kate a 
					LEFT JOIN '.DB_PRE.'ecp_downloads_kate b ON a.kID = b.subkID
					WHERE a.subkID= '.$id.'					
					GROUP BY a.kID');
	while($row = mysql_fetch_assoc($result)) {
		$result1 = $db->query('SELECT dID FROM '.DB_PRE.'ecp_downloads WHERE cID = '.$row['kID']);
		while($row1 = mysql_fetch_assoc($result1)) { 
			$db->query('DELETE FROM '.DB_PRE.'ecp_comments WHERE subID = '.$row1['dID'].' AND bereich = "downloads"');
			$db->query('DELETE FROM '.DB_PRE.'ecp_downloads WHERE dID = '.$row1['dID']);
		}
		$db->query('DELETE FROM '.DB_PRE.'ecp_downloads_kate WHERE kID = '.$row['kID']);		
		if($row['subs']) {
			del_sub_kate($row['kID']);
		}
	}
}
function admin_downloads_getdls ($ajax = false) {
	global $db;
	$db->query('SELECT name, dID FROM '.DB_PRE.'ecp_downloads ORDER BY name ASC');
	if($ajax == false) {
		echo '<option value="0">'.CHOOSE.'</option>';
		while($row = $db->fetch_assoc()) {
			echo '<option value="'.$row['dID'].'">'.$row['name'].'</option>';
		}	
	} else {
		$array = array(array('value'=>0, 'name'=> CHOOSE, 'selected'=>false));
		while($row = $db->fetch_assoc()) {
			$array[] = array('value'=>$row['dID'], 'name'=> $row['name'], 'selected'=>false);
		}
		html_convert_array($array);
		echo json_encode($array);
	}
}
function admin_downloads_kate_edit($id) {
	global $db;
	ob_end_clean();
	ajax_convert_array($_POST);
	if($_POST['kname'] == '') {
		echo NOT_NEED_ALL_INPUTS;
	} else {
		$lang = array();
		foreach($_POST AS $key => $value) {
			if(strpos($key, 'cription_')) {
				$lang[substr($key,strpos($key, '_')+1)] = $value;
			}
		}
		$sql = sprintf('UPDATE '.DB_PRE.'ecp_downloads_kate SET 
						`subkID` = %d, 
						`kname` = \'%s\', 
						`beschreibung` = \'%s\', 
						`access` = \'%s\'
				 WHERE kID = %d', 
				 (int)$_POST['subID'], strsave($_POST['kname']), strsave(json_encode($lang)), strsave(admin_make_rights($_POST['rights'])), $id);
		$db->setMode(0);
		if($db->query($sql)) {
			echo 'ok';	
		}
	}
	die();
}
if (!isset($_SESSION['rights']['admin']['downloads']) AND !isset($_SESSION['rights']['superadmin'])) {
	table(ERROR, NO_ADMIN_RIGHTS);
} else {
	if(isset($_GET['func'])) {
		switch($_GET['func']) {
			case 'add':
				admin_downloads_add();
				break;
			case 'editdl':
				admin_downloads_edit((int)$_GET['id']);
				break;
			case 'del':
				admin_downloads_del((int)$_GET['id']);
				break;
			case 'addkate':
				admin_downloads_addkate();
				break;
			case 'delkate':
				admin_downloads_kate_del((int)$_GET['id']);
				break;	
			case 'editkate':
				admin_downloads_kate_edit((int)$_GET['id']);
				break;					
			case 'getkates':
				ob_end_clean();
				download_get_cate_ajax();
				die();
				break;	
			case 'getdls':
				ob_end_clean();
				admin_downloads_getdls(true);
				die();
				break;												
			default:
				admin_downloads();
		}
	} else {
		admin_downloads();
	}
}
?>