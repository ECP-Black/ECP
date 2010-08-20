<?php
if (!isset($_SESSION['rights']['admin']['texte']['edit']) AND !isset($_SESSION['rights']['superadmin'])) {
	table(ERROR, NO_ADMIN_RIGHTS);
} else {
	function admin_texte() {
		global $db, $countries;		
		if(isset($_POST['submit'])) {
			foreach($_POST AS $key =>$value) {
				if(strpos($key, '_h_')) {
					$lang = substr($key, 0, strpos($key, '_'));
					$name = substr($key, strpos($key, '_')+3);
					$sql = sprintf('UPDATE '.DB_PRE.'ecp_texte SET content = \'%s\', content2 = \'%s\' WHERE name= \'%s\' AND lang = \'%s\';', strsave($_POST[$lang.'_'.$name]), strsave($value), strsave($name), strsave($lang));	
					$db->query($sql);
				}
				
			}
			header('Location: ?section=admin&site=texte');
		} else {
			$tpl = new smarty;
			
			$lang = get_languages();
			$db->query('SELECT * FROM '.DB_PRE.'ecp_texte ORDER BY lang ASC');

			while($row = $db->fetch_assoc()) {
				foreach($lang AS $key=>$value) {
					if($value['lang'] == $row['lang']) {
						$lang[$key]['data'][$row['name']] = htmlspecialchars($row['content']);
						$lang[$key]['headline'][$row['name']] = htmlspecialchars($row['content2']);
					}
				}
			}
			$tpl->assign('lang', $lang);
			ob_start();
			$tpl->display(DESIGN.'/tpl/admin/texte.html');
			$content = ob_get_contents();
			ob_end_clean();
			main_content(TEXTE, $content, '',1);		
		}
	} 
	admin_texte();
}
?>