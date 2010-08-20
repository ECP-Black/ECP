<?php
function admin_groups() {
	global $db, $groups;
	$db->query('SELECT groupID,	name, admin, public FROM '.DB_PRE.'ecp_groups ORDER BY name');
	$gruppen = array();
	while($row = $db->fetch_assoc()) {
		if(key_exists($row['name'], $groups)) $row['name'] = $groups[$row['name']];
		$gruppen[] = $row;
	}
	$tpl = new smarty;
	$tpl->assign('groups', $gruppen);
	ob_start();
	$tpl->display(DESIGN.'/tpl/admin/groups.html');
	$content = ob_get_contents();
	ob_end_clean();
	main_content(GROUP, $content, '',1);
	
}
function admin_groups_add() {
	global $db;
	if(isset($_SESSION['rights']['admin']['groups']['add']) OR isset($_SESSION['rights']['superadmin'])) {
		if(isset($_POST['submit'])) {
			if($_POST['name'] == '' AND $id > 4) {
				table(ERROR, GROUP_NAME_REQUIRED);
				$tpl = new smarty;
				$files = scan_dir('templates/'.DESIGN.'/tpl/admin/group_forms/',true);
				$admin = '';
				$public = '';
				foreach($_POST AS $key => $value) $tpl->assign($key, $value);
				foreach($files AS $value) {
					$tpltemp = new smarty;
					ob_start();
					$tpltemp->display(DESIGN.'/tpl/admin/group_forms/'.$value);
					$content = ob_get_contents();
					ob_end_clean();		
					if(strpos($value, 'admin') === false) {
						$public .= $content;
					} else {
						$admin .= $content;
					}
				}
				$tpl->assign('admin', $admin);
				$tpl->assign('public', $public);
				ob_start();
				$tpl->display(DESIGN.'/tpl/admin/groups_add.html');
				$content = ob_get_contents();
				ob_end_clean();
				main_content(GROUP_ADD, $content, '',1);				
			} else {		
				$admin = array();
				$public = array();
				foreach($_POST AS $key=>$value) {
					if(strpos($key, 'admin') === 0 ) {	
						$key = substr($key, strpos($key,'_')+1);
						@$admin[substr($key, 0, strpos($key, '_',1))][substr($key, strpos($key, '_',1)+1)] = $value;			
					} elseif (strpos($key, 'public') === 0) {
						$key = substr($key, strpos($key,'_')+1);
						@$public[substr($key, 0, strpos($key, '_',1))][substr($key, strpos($key, '_',1)+1)] = $value;	
					}
				}
				foreach($admin AS $key => $value) {
					@$admins .= ']'.$key.':';
					foreach($value AS $key2 => $value2) {
						$admins .= $key2.'='.$value2.',';
					}
					$admins = substr($admins, 0, strlen($admins)-1);
				}
				$admins = substr($admins, 1);
				foreach($public AS $key => $value) {
					@$publics .= ']'.$key.':';
					foreach($value AS $key2 => $value2) {
						$publics .= $key2.'='.$value2.',';
					}
					$publics = substr($publics, 0, strlen($publics)-1);
				}
				$publics = substr($publics, 1);
				if($db->query('INSERT INTO '.DB_PRE.'ecp_groups (name, admin, public) VALUES (\''.strsave($_POST['name']).'\', \''.strsave($admins).'\', \''.strsave($publics).'\')')) {
					header1('?section=admin&site=groups');	
				}
			}
		} else {
			$tpl = new smarty;
			$files = scan_dir('templates/'.DESIGN.'/tpl/admin/group_forms/',true);
			$admin = '';
			$public = '';
			foreach($files AS $value) {
				$tpltemp = new smarty;
				ob_start();
				$tpltemp->display(DESIGN.'/tpl/admin/group_forms/'.$value);
				$content = ob_get_contents();
				ob_end_clean();		
				if(strpos($value, 'admin') === false) {
					$public .= $content;
				} else {
					$admin .= $content;
				}
			}
			$tpl->assign('admin', $admin);
			$tpl->assign('public', $public);
			ob_start();
			$tpl->display(DESIGN.'/tpl/admin/groups_add.html');
			$content = ob_get_contents();
			ob_end_clean();
			main_content(GROUP_ADD, $content, '',1);	
		}
	} else {
		table(ERROR, NO_ADMIN_RIGHTS);
	}
}
function admin_groups_edit($id) {
	global $db, $groups;
	if(isset($_SESSION['rights']['admin']['groups']['edit']) OR isset($_SESSION['rights']['superadmin'])) {
		if(isset($_POST['submit'])) {
			if($_POST['name'] == '' AND $id > 4) {
				table(ERROR, GROUP_NAME_REQUIRED);
				$tpl = new smarty;
				$tpl->assign('art', 'edit');
				$files = scan_dir('templates/'.DESIGN.'/tpl/admin/group_forms/',true);
				$admin = '';
				$public = '';
				foreach($_POST AS $key => $value) $tpl->assign($key, $value);
				foreach($files AS $value) {
					$tpltemp = new smarty;
					ob_start();
					$tpltemp->display(DESIGN.'/tpl/admin/group_forms/'.$value);
					$content = ob_get_contents();
					ob_end_clean();		
					if(strpos($value, 'admin') === false) {
						$public .= $content;
					} else {
						$admin .= $content;
					}
				}
				$tpl->assign('admin', $admin);
				$tpl->assign('public', $public);
				ob_start();
				$tpl->display(DESIGN.'/tpl/admin/groups_add.html');
				$content = ob_get_contents();
				ob_end_clean();
				main_content(GROUP_EDIT, $content, '',1);	
			} else {
				$admin = array();
				$public = array();
				foreach($_POST AS $key=>$value) {
					if(strpos($key, 'admin') === 0 ) {	
						$key = substr($key, strpos($key,'_')+1);
						@$admin[substr($key, 0, strpos($key, '_',1))][substr($key, strpos($key, '_',1)+1)] = $value;			
					} elseif (strpos($key, 'public') === 0) {
						$key = substr($key, strpos($key,'_')+1);
						@$public[substr($key, 0, strpos($key, '_',1))][substr($key, strpos($key, '_',1)+1)] = $value;	
					}
				}
				foreach($admin AS $key => $value) {
					@$admins .= ']'.$key.':';
					foreach($value AS $key2 => $value2) {
						$admins .= $key2.'='.$value2.',';
					}
					$admins = substr($admins, 0, strlen($admins)-1);
				}
				$admins = substr($admins, 1);
				foreach($public AS $key => $value) {
					@$publics .= ']'.$key.':';
					foreach($value AS $key2 => $value2) {
						$publics .= $key2.'='.$value2.',';
					}
					$publics = substr($publics, 0, strlen($publics)-1);
				}
				$publics = substr($publics, 1);
				if($id > 4) {
					if($db->query('UPDATE '.DB_PRE.'ecp_groups SET name = \''.strsave($_POST['name']).'\', admin = \''.strsave($admins).'\', public = \''.strsave($publics).'\' WHERE groupID = '.$id)) {
						$db->query('UPDATE '.DB_PRE.'ecp_user SET update_rights = 1');
						header1('?section=admin&site=groups');		
					}
				} else {
					if($db->query('UPDATE '.DB_PRE.'ecp_groups SET admin = \''.strsave($admins).'\', public = \''.strsave($publics).'\' WHERE groupID = '.$id)) {
						$db->query('UPDATE '.DB_PRE.'ecp_user SET update_rights = 1');
						header1('?section=admin&site=groups');
					}			
				}	
			}
		} else {
			$tpl = new smarty;
			$files = scan_dir('templates/'.DESIGN.'/tpl/admin/group_forms/',true);
			$admin = '';
			$public = '';
			$tpl->assign('art', 'edit');
			$row = $db->fetch_assoc('SELECT * FROM '.DB_PRE.'ecp_groups WHERE groupID = '.$id);
			$tpl->assign('id', $id);
			if($id > 4) $tpl->assign('name', $row['name']); else $tpl->assign('name', $groups[$row['name']]);
			if($row['admin'] != '') {
				$array = explode(']', $row['admin']);
				foreach($array AS $value) {
					$data = explode(':', $value);
					$name = 'admin_'.$data[0];
					$data = explode(',', $data[1]);
					foreach($data as $key => $value2) {
						$rights[$name][substr($value2,0, strpos($value2, '='))] = (int)substr($value2,strpos($value2, '=')+1);
					}
				}
			}
			if($row['public'] != '') {
				$array = explode(']', $row['public']);
				foreach($array AS $value) {
					$data = explode(':', $value);
					$name = 'public_'.$data[0];
					$data = explode(',', $data[1]);
					foreach($data as $key => $value2) {
						$rights[$name][substr($value2,0, strpos($value2, '='))] = (int)substr($value2,strpos($value2, '=')+1);
					}
				}	
			}	
			foreach($files AS $value) {
				$tpltemp = new smarty;
				$name = substr($value, 0, strpos($value, '.'));
				if(isset($rights[$name])) {
					foreach($rights[$name] AS $key2 => $value2) {
						$tpltemp->assign($name.'_'.$key2, $value2);
					}			
				}
				ob_start();
				$tpltemp->display(DESIGN.'/tpl/admin/group_forms/'.$value);
				$content = ob_get_contents();
				ob_end_clean();		
				if(strpos($value, 'admin') === false) {
					$public .= $content;
				} else {			
					$admin .= $content;
				}
			}
			$tpl->assign('admin', $admin);
			$tpl->assign('public', $public);
			ob_start();
			$tpl->display(DESIGN.'/tpl/admin/groups_add.html');
			$content = ob_get_contents();
			ob_end_clean();
			main_content(GROUP_EDIT, $content, '',1);	
		}
	} else {
		table(ERROR, NO_ADMIN_RIGHTS);
	}
}
if (!isset($_SESSION['rights']['admin']['groups']) AND !isset($_SESSION['rights']['superadmin'])) {
	table(ERROR, NO_ADMIN_RIGHTS);
} else {
	if(isset($_GET['func'])) {
		switch($_GET['func']) {
			case 'add':
				admin_groups_add();
				break;
			case 'edit':
				admin_groups_edit((int)$_GET['id']);
				break;
			case 'del':
				admin_groups_del((int)$_GET['id']);
				break;
			default:
				admin_groups();
		}
	} else {
		admin_groups();
	}
}
?>