<?php
function admin_menu() {
	global $db;
	$tpl = new smarty;
	$db->query('SELECT `menuID`, `name`, `hposi`, `vposi` FROM '.DB_PRE.'ecp_menu WHERE design = \''.DESIGN.'\' ORDER BY vposi ASC');
	$lmenu = array();
	$rmenu = array();
	while($row = $db->fetch_assoc()) {
		if($row['hposi'] == 'l')
		$lmenu[] = $row;
		else 
		$rmenu[] = $row;
	}
	$tpl->assign('lmenu', $lmenu);
	$tpl->assign('rmenu', $rmenu);
	$designs = scan_dir('templates/', true);
	foreach($designs AS $value) {
		if(is_dir('templates/'.$value) AND $value != DESIGN)
		@$options .= '<option value="'.$value.'">'.$value.'</option>';
	}
	$tpl->assign('designs', @$options);
	$languages = get_languages();
	foreach($languages AS $key=>$value) {
		$db->query('SELECT suche, ersetze, linkID FROM '.DB_PRE.'ecp_menu_links WHERE sprache = "'.strsave($value['lang']).'" ORDER BY suche ASC');
		$links = array();
		while($row = $db->fetch_assoc()) {
			$row['ersetze'] = htmlspecialchars($row['ersetze']);
			$links[] =$row;			
		}
		ob_start();
		$tpls = new Smarty;
		$tpls->assign('lang', $value['lang']);
		$tpls->assign('links', $links);
		$tpls->display(DESIGN.'/tpl/admin/menu_links.html');
		$languages[$key]['content'] = ob_get_contents();
		ob_end_clean();
	}
	$tpl->assign('lang', $languages);
	ob_start();
	$tpl->display(DESIGN.'/tpl/admin/menu.html');
	$content = ob_get_contents();
	ob_end_clean();
	main_content(MENU, $content, '',1);
}
function admin_menu_add() {
	global $db;
	if(@$_SESSION['rights']['admin']['menu']['add'] OR @$_SESSION['rights']['superadmin']) {		
		if(isset($_POST['submit'])) {
			if($_POST['name'] == '' OR $_POST['design'] == '') {
				table(ERROR, NOT_NEED_ALL_INPUTS);
				$tpl = new smarty;
				foreach($_POST AS $key => $value) $tpl->assign($key, $value);
	 			$tpl->assign('module', get_module($_POST['modul']));
				$tpl->assign('designs', get_designs($_POST['design']));
				$tpl->assign('access', get_form_rights($_POST['access']));
				$tpl->assign('func', 'add');
				$lang = get_languages();
				(in_array('all', $_POST['language']))? $options = '<option value="all" selected="selected">'.ALL.'</option>' : $options = '<option value="all">'.ALL.'</option>';
				foreach($lang AS $value) {
					$options .= '<option '.((in_array($value['lang'], $_POST['language']))? ' selected="selected"' :  '').'value="'.$value['lang'].'">'.$value['name'].'</option>';
				}
				$tpl->assign('languages', $options);
				ob_start();
				$tpl->display(DESIGN.'/tpl/admin/menu_add_edit.html');
				$content = ob_get_contents();
				ob_end_clean();
				main_content(MENU_ADD, $content, '',1);		
			} else {
				if(in_array('all', $_POST['language'])) $lang = ''; else $lang = ','.implode(',',$_POST['language']).',';
				$sql = sprintf('INSERT INTO '.DB_PRE.'ecp_menu (`name`, `headline`, `inhalt`, `hposi`, `usetpl`, `design`, `access`, `lang`, `modul`) VALUES (\'%s\', \'%s\', \'%s\', \'%s\', %d, \'%s\', \'%s\', \'%s\', \'%s\')', strsave($_POST['name']), strsave($_POST['headline']), strsave($_POST['inhalt']), strsave($_POST['postion']), (int)@$_POST['usetpl'], strsave($_POST['design']), strsave(admin_make_rights($_POST['access'])), strsave($lang), strsave($_POST['modul']));
				if($db->query($sql)) {
					header1('?section=admin&site=menu');
				}
			}
		} else {
			$tpl = new smarty;
			$tpl->assign('func', 'add');
			$tpl->assign('module', get_module());
			$tpl->assign('designs', get_designs());
			$tpl->assign('access', get_form_rights());
			$lang = get_languages();
			$options = '<option value="all" selected="selected">'.ALL.'</option>';
			foreach($lang AS $value) {
				$options .= '<option value="'.$value['lang'].'">'.$value['name'].'</option>';
			}
			$tpl->assign('languages', $options);
			ob_start();
			$tpl->display(DESIGN.'/tpl/admin/menu_add_edit.html');
			$content = ob_get_contents();
			ob_end_clean();
			main_content(MENU_ADD, $content, '',1);		
		}
	} else{
		table(ERROR, NO_ADMIN_RIGHTS);
	}
}
function admin_menu_edit($id) {
	global $db;
	if(@$_SESSION['rights']['admin']['menu']['edit'] OR @$_SESSION['rights']['superadmin']) {		
		if(isset($_POST['submit'])) {
			if($_POST['name'] == '' OR $_POST['design'] == '') {
				table(ERROR, NOT_NEED_ALL_INPUTS);
				$tpl = new smarty;
				foreach($_POST AS $key => $value) $tpl->assign($key, $value);
	 			$tpl->assign('module', get_module($_POST['modul']));
				$tpl->assign('designs', get_designs($_POST['design']));
				$tpl->assign('access', get_form_rights($_POST['access']));
				$tpl->assign('func', 'add');
				$lang = get_languages();
				(in_array('all', $_POST['language']))? $options = '<option value="all" selected="selected">'.ALL.'</option>' : $options = '<option value="all">'.ALL.'</option>';
				foreach($lang AS $value) {
					$options .= '<option '.((in_array($value['lang'], $_POST['language']))? ' selected="selected"' :  '').'value="'.$value['lang'].'">'.$value['name'].'</option>';
				}
				$tpl->assign('languages', $options);
				ob_start();
				$tpl->display(DESIGN.'/tpl/admin/menu_add_edit.html');
				$content = ob_get_contents();
				ob_end_clean();
				main_content(MENU_ADD, $content, '',1);		
			} else {
				if(in_array('all', $_POST['language'])) $lang = ''; else $lang = ','.implode(',',$_POST['language']).',';
				$sql = sprintf('UPDATE '.DB_PRE.'ecp_menu SET `name` = \'%s\', `headline` = \'%s\', `inhalt` = \'%s\', `hposi` = \'%s\', `usetpl` =%d, `design` = \'%s\', `access` = \'%s\', `lang` = \'%s\', `modul` = \'%s\' WHERE menuID = %d', strsave($_POST['name']), strsave($_POST['headline']), strsave($_POST['inhalt']), strsave($_POST['postion']), (int)@$_POST['usetpl'], strsave($_POST['design']), strsave(admin_make_rights($_POST['access'])), strsave($lang), strsave($_POST['modul']), $id);
				if($db->query($sql)) {
					header1('?section=admin&site=menu');
				}
			}
		} else {
			$menu = $db->fetch_assoc('SELECT * FROM '.DB_PRE.'ecp_menu WHERE menuID = '.$id);
			$tpl = new smarty;
			$menu['headline'] =  htmlentities($menu['headline']);
			foreach($menu AS $key =>$value) $tpl->assign($key, $value);
			$tpl->assign('func', 'edit&id='.$id);
			$tpl->assign('module', get_module($menu['modul']));
			$tpl->assign('designs', get_designs($menu['design']));
			$tpl->assign('access', get_form_rights(explode(',',$menu['access'])));
			$lang = get_languages();
			$langs = explode(',', $menu['lang']);
			$options = '<option value="all" '.(count($langs) < 3 ? 'selected="selected"' : '').'>'.ALL.'</option>';
			foreach($lang AS $value) {
				$options .= '<option '.((in_array($value['lang'], $langs))? ' selected="selected"' :  '').'value="'.$value['lang'].'">'.$value['name'].'</option>';
			}
			$tpl->assign('languages', $options);
			ob_start();
			$tpl->display(DESIGN.'/tpl/admin/menu_add_edit.html');
			$content = ob_get_contents();
			ob_end_clean();
			main_content(MENU_EDIT, $content, '',1);		
		}
	} else {
		table(ERROR, NO_ADMIN_RIGHTS);
	}
}
function get_module($sel = '') {
	$modul_name = get_modul_namen();
	$options = '<option value="">'.CHOOSE.'</option>';
	$dir = scan_dir('inc/module/',true);
	foreach($dir AS $value) {
		$options .= '<option '.($sel == $value ? 'selected="selected" ' : '' ).'value="'.$value.'">'.(array_key_exists($value, $modul_name) ? $modul_name[$value] : $value).'</option>';
	}
	return $options;
}
function get_designs($sel = '') {
	$options = '<option value="">'.CHOOSE.'</option>';
	$dir = scan_dir('templates/',true);
	foreach($dir AS $value) {
		if(is_dir('templates/'.$value))
			$options .= '<option '.($sel == $value ? 'selected="selected" ' : '' ).'value="'.$value.'">'.$value.'</option>';
	}
	return $options;	
}
function menu_copy($new) {
	if(@$_SESSION['rights']['admin']['menu']['copy'] OR @$_SESSION['rights']['superadmin']) {		
		if(is_dir('templates/'.$new)) {
			global $db;
			$result = $db->query('SELECT * FROM '.DB_PRE.'ecp_menu WHERE design = \''.DESIGN.'\'');
			while($row = mysql_fetch_assoc($result)) {
				$db->query(sprintf('INSERT INTO '.DB_PRE.'ecp_menu (`name`, `headline`, `inhalt`, `hposi`, `vposi`, `usetpl`, `design`, `access`, `lang`, `modul`) 
									VALUES (\'%s\', \'%s\', \'%s\', \'%s\', %d, %d, \'%s\', \'%s\', \'%s\', \'%s\')', 
									strsave($row['name']), strsave($row['headline']), strsave($row['inhalt']), strsave($row['hposi']), 
									strsave($row['vposi']), strsave($row['usetpl']), strsave($new), strsave($row['access']),
									strsave($row['lang']), strsave($row['modul'])));
			}
			if(!$db->errorNum()) {
				table(INFO, MENU_COPY_SUCCESS);
			}
		} else {
			table(ERROR, FILE_NOT_FOUND);
		}
	} else {
		table(ERROR, NO_ADMIN_RIGHTS);
	}
}
if (!isset($_SESSION['rights']['admin']['menu']) AND !isset($_SESSION['rights']['superadmin'])) {
	table(ERROR, NO_ADMIN_RIGHTS);
} else {
	if(isset($_GET['func'])) {
		switch($_GET['func']) {
			case 'add':
				admin_menu_add();
				break;
			case 'edit':
				admin_menu_edit((int)$_GET['id']);
				break;
			case 'del':
				admin_menu_del((int)$_GET['id']);
				break;
			case 'copy':
				menu_copy($_POST['to']);
				break;				
			default:
				admin_menu();
		}
	} else {
		admin_menu();
	}
}
?>