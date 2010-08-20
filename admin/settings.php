<?php
if (!isset($_SESSION['rights']['admin']['settings']['edit']) AND !isset($_SESSION['rights']['superadmin'])) {
	table(ERROR, NO_ADMIN_RIGHTS);
} else {
	function admin_settings() {
		global $db, $countries;		
		if(isset($_POST['submit'])) {
			unset($_POST['submit']);
			$_POST['SITE_URL'] = (strrpos($_POST['SITE_URL'], '/') !== (strlen($_POST['SITE_URL'])-1) ? check_url($_POST['SITE_URL'].'/') : check_url($_POST['SITE_URL']));
			$sql = 'UPDATE '.DB_PRE.'ecp_settings SET ';
			foreach($_POST AS $key=>$value) {
				$sql .= $key.' = "'.strsave($value).'", ';
			}
			$sql = substr($sql, 0, strlen($sql)-2);
			if($db->query($sql)) {
				header('Location: ?section=admin&site=settings');
			}
		} else {
			$dir = scan_dir('templates', true);
			$designs = '';
			foreach($dir AS $value) {
				if(is_dir('templates/'.$value)) {
					$designs .= '<option '.($value == DESIGN ? 'selected="selected"' : '').' value="'.$value.'">'.$value.'</option>';
				}
			}
			$tpl = new smarty;
			$tpl->assign('designs', $designs);
			$tpl->assign('langs', get_languages());
			$dir = scan_dir('module', true);
			$start = '';
			foreach($dir AS $value) {
				if(is_dir('module/'.$value)) {
					$start .= '<option '.('modul|'.$value == STARTSEITE ? 'selected="selected"' : '').' value="modul|'.$value.'">'.$value.'</option>';
				}
			}
			$start .= '<option value="">-----'.OWN_SITES.'----</option>';
			$db->query('SELECT headline, cmsID FROM '.DB_PRE.'ecp_cms ORDER BY headline ASC');
			while($row = $db->fetch_assoc()) {
				$title = json_decode($row['headline'], true);
				(isset($title[LANGUAGE]) ? $title=$title[LANGUAGE] : $title=$title[DEFAULT_LANG]);
				$start .= '<option '.('cms|'.$row['cmsID'] == STARTSEITE ? 'selected="selected"' : '').' value="cms|'.$row['cmsID'].'">'.$title.'</option>';
			}
			$tpl->assign('startseite', $start);
			ob_start();
			$tpl->display(DESIGN.'/tpl/admin/settings.html');
			$content = ob_get_contents();
			ob_end_clean();
			main_content(SETTINGS, $content, '',1);		
		}
	} 
	admin_settings();
}
?>