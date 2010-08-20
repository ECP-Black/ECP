<?php
	include('lang/'.LANGUAGE.'.php');
	global $db;
	if(isset($_SESSION['userID']) AND (isset($_SESSION['rights']['admin']) OR isset($_SESSION['rights']['superadmin']))) {
		if(!isset($_SESSION['admin_verify'])) {
			if(isset($_POST['passwort'])) {
				if($db->result(DB_PRE.'ecp_user', 'COUNT(ID)', 'ID = '.$_SESSION['userID'].' AND passwort = \''.sha1($_POST['passwort']).'\'')) {
				    $_SESSION['admin_verify'] = 1;	
				    header1('?section=admin');
				} else {
					table(ERROR, WRONG_PW);
					$tpl = new smarty;
					ob_start();
					$tpl->display(DESIGN.'/tpl/admin/verify.html');
					$content = ob_get_contents();
					ob_end_clean();
					main_content(SECURITY, $content, '',1);
				}
			} else {
				$tpl = new smarty;
				ob_start();
				$tpl->display(DESIGN.'/tpl/admin/verify.html');
				$content = ob_get_contents();
				ob_end_clean();
				main_content(SECURITY, $content, '',1);
			}		
		} else {
			$tpl = new smarty;
			ob_start();
			$tpl->display(DESIGN.'/tpl/admin/navi.html');
			$content = ob_get_contents();
			ob_end_clean();
			main_content(ADMIN_MENU, $content, '',1);				
			if(isset($_GET['site'])) {
				preg_match('/\w+/', $_GET['site'], $erg);
				if(file_exists('admin/'.$erg[0].'.php')) {
					include('admin/'.$erg[0].'.php');
				} else {
					table(ERROR, SITE_NOT_EXSISTS);
				}
			} else {
				$tpl = new smarty;
				ob_start();
				$json = @get_contents('http://www.easy-clanpage.de/version_check.php');
				if($json) {
					$json = json_decode($json, true);					
					$tpl->assign('online', $json['version']);
					if($json['version'] != VERSION) {
						$tpl->assign('version_color', 'version_old');
					} else {
						$tpl->assign('version_color', 'version_new');
					}
					foreach($json['news'] as $key => $value) $json['news'][$key]['date'] = date(LONG_DATE, $value['date']);					
					$tpl->assign('news', $json['news']);
					$tpl->display(DESIGN.'/tpl/admin/admin_start.html');
					$content = ob_get_contents();					
				} else {
					//$content = VERSION_MANUELL;
					$tpl->display(DESIGN.'/tpl/admin/admin_start_nocontent.html');
					$content = ob_get_contents();		
				}
				ob_end_clean();				
				main_content(ADMINISTRATION, $content, '',1);				
			}
		}
	} else {
		table(ACCESS_DENIED, NO_ADMIN_RIGHTS);
	}
?>