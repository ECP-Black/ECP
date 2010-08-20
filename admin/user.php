<?php
function admin_user() {
	global $db, $groups;
	$result = $db->query('SELECT userID, endbantime FROM '.DB_PRE.'ecp_user_bans WHERE endbantime < '.time());
	while($row = mysql_fetch_assoc($result)) {
		$db->query('DELETE FROM '.DB_PRE.'ecp_user_bans WHERE userID = '.$row['userID'].' AND endbantime = '.$row['endbantime']);
		if($db->result(DB_PRE.'ecp_user_bans', 'COUNT(userID)', 'userID = '.$row['userID']) == 0) {
			$db->query('UPDATE '.DB_PRE.'ecp_user SET status = 1 WHERE ID = '.$row['userID']);
		}
	}
	$tpl = new smarty;
	$bans = array();
	$db->query('SELECT a.username, b.username as banusername, `userID`, `vonID`, `bantime`, `endbantime`, `grund` FROM '.DB_PRE.'ecp_user_bans LEFT JOIN '.DB_PRE.'ecp_user as a ON (a.ID = userID) LEFT JOIN '.DB_PRE.'ecp_user as b ON (b.ID = vonID) ORDER BY a.username ASC');
	while($row = $db->fetch_assoc()) {
		$row['endbantime'] = date(LONG_DATE, $row['endbantime']);
		$row['gruppen'] = array();
		$db->query('SELECT gID, name FROM '.DB_PRE.'ecp_user_groups LEFT JOIN '.DB_PRE.'ecp_groups ON (gID = groupID) WHERE userID = '.$row['userID'].' ORDER BY name ASC');
		while($sub = $db->fetch_assoc()) {
			array_key_exists($sub['name'], $groups) ? $sub['name'] = $groups[$sub['name']] : '';
			$row['gruppen'][] = $sub;
		}		
		$bans[] = $row;
	}
	$tpl->assign('bans', $bans);
	$inaktiv = array();
	$result = $db->query('SELECT username, registerdate, ID as userID FROM '.DB_PRE.'ecp_user WHERE status = 0 ORDER BY username ASC');
	while($row = mysql_fetch_assoc($result)) {
		$row['registerdate'] = date(LONG_DATE, $row['registerdate']);
		$row['gruppen'] = array();
		$db->query('SELECT gID, name FROM '.DB_PRE.'ecp_user_groups LEFT JOIN '.DB_PRE.'ecp_groups ON (gID = groupID) WHERE userID = '.$row['userID'].' ORDER BY name ASC');
		while($sub = $db->fetch_assoc()) {
			array_key_exists($sub['name'], $groups) ? $sub['name'] = $groups[$sub['name']] : '';
			$row['gruppen'][] = $sub;
		}
		$inaktiv[] = $row;
	}
	$tpl->assign('inaktivs', $inaktiv);
	ob_start();
	$tpl->display(DESIGN.'/tpl/admin/user.html');
	$content = ob_get_contents();
	ob_end_clean();
	main_content(USER, $content, '',1);
}
function admin_user_edit($id) {
		global $db;
		if(isset($_POST['submit'])) {
			if($db->result(DB_PRE.'ecp_user', 'COUNT(ID)', 'username = \''.strsave(htmlspecialchars($_POST['username'])).'\' AND ID != '.$id) OR $_POST['username'] == '') {
				$_POST['username'] = $db->result(DB_PRE.'ecp_user', 'username', 'ID = '.$id);
				table(ERROR, ACCOUNT_ALLREADY_EXIST);
			}
			if($db->result(DB_PRE.'ecp_user', 'COUNT(ID)', 'email = \''.strsave($_POST['username']).'\' AND ID != '.$id) OR !check_email($_POST['email'])) {
				$_POST['email'] = $db->result(DB_PRE.'ecp_user', 'email', 'ID = '.$id);
				if(!check_email($_POST['email'])) 
					table(ERROR, WRONG_EMAIL);
				else
					table(ERROR, EMAIL_ALLREADY_EXIST);
			}
			$geburtstag = explode('.', $_POST['birthday']);
			$sql = sprintf('UPDATE '.DB_PRE.'ecp_user SET
						username = \'%s\',email = \'%s\',country = \'%s\',
						sex = \'%s\',signatur = \'%s\',realname = \'%s\',
						geburtstag = \'%s\',homepage = \'%s\',icq = \'%s\',
						msn = \'%s\',yahoo = \'%s\',skype = \'%s\',xfire = \'%s\',
						clanname = \'%s\',clanirc = \'%s\',clanhomepage = \'%s\',
						clanhistory = \'%s\',cpu = \'%s\',mainboard = \'%s\',
						ram = \'%s\',gkarte = \'%s\',skarte = \'%s\',
						monitor = \'%s\',maus = \'%s\',tastatur = \'%s\',
						mauspad = \'%s\',internet = \'%s\',festplatte = \'%s\',
						headset = \'%s\',aboutme = \'%s\', wohnort = \'%s\', aim = \'%s\'  
					WHERE ID = '.$id,
					strsave(htmlspecialchars(@$_POST['username'])), strsave(@$_POST['email']), strsave(@$_POST['country']), (@$_POST['sex'] == 'male' ? 'male' : 'female'), strsave(comment_save(@$_POST['signatur'])),
					strsave(htmlspecialchars(@$_POST['realname'])), (int)@$geburtstag[2].'-'.(int)@$geburtstag[1].'-'.(int)@$geburtstag[0], strsave(htmlspecialchars(check_url(@$_POST['homepage']))),
					strsave(htmlspecialchars(@$_POST['icq'])),strsave(htmlspecialchars(@$_POST['msn'])),strsave(htmlspecialchars(@$_POST['yahoo'])),
					strsave(htmlspecialchars(@$_POST['skype'])),strsave(htmlspecialchars(@$_POST['xfire'])),strsave(htmlspecialchars(@$_POST['clanname'])),
					strsave(htmlspecialchars(@$_POST['clanirc'])),strsave(htmlspecialchars(check_url(@$_POST['clanhomepage']))),strsave(htmlspecialchars(@$_POST['clanhistory'])),
					strsave(htmlspecialchars(@$_POST['cpu'])),strsave(htmlspecialchars(@$_POST['mainboard'])),strsave(htmlspecialchars(@$_POST['ram'])),
					strsave(htmlspecialchars(@$_POST['gkarte'])),strsave(htmlspecialchars(@$_POST['skarte'])),strsave(htmlspecialchars(@$_POST['monitor'])),
					strsave(htmlspecialchars(@$_POST['maus'])),strsave(htmlspecialchars(@$_POST['tastatur'])),strsave(htmlspecialchars(@$_POST['mauspad'])),
					strsave(htmlspecialchars(@$_POST['internet'])),strsave(htmlspecialchars(@$_POST['festplatte'])),strsave(htmlspecialchars(@$_POST['headset'])),strsave(comment_save(@$_POST['aboutme'])), strsave(htmlspecialchars(@$_POST['wohnort'])), strsave(htmlspecialchars(@$_POST['aim'])));
			if($db->query($sql) AND $db->query('UPDATE '.DB_PRE.'ecp_user_stats SET comments = '.(int)$_POST['comments'].', money = '.(float)$_POST['money'].' WHERE userID = '.$id)) {
				header1('?section=admin&site=user');
			}
		} else {
			$tpl = new smarty;
			$row = $db->fetch_assoc('SELECT `username`, `email`, `country`, `sex`, `signatur`, `realname`, `wohnort`, `geburtstag`, `homepage`, `icq`, `msn`, `yahoo`, `skype`, `xfire`, 
											`clanname`, `clanirc`, `clanhomepage`, `clanhistory`, `cpu`, `mainboard`, `ram`, `gkarte`, `skarte`, `monitor`, `maus`, `tastatur`, `mauspad`, 
											`internet`, `festplatte`, `headset`, `aboutme`, `ondelete`, aim, money, comments FROM '.DB_PRE.'ecp_user LEFT JOIN '.DB_PRE.'ecp_user_stats ON (userID = ID) WHERE ID = '.$id);
			$row['birthday'] = date('d.m.Y', strtotime($row['geburtstag']));
			foreach($row AS $key=>$value) $tpl->assign($key, $value);
			ob_start();
			$tpl->assign('countries', form_country($row['country']));
			$tpl->display(DESIGN.'/tpl/admin/user_edit.html');
			$content = ob_get_contents();
			ob_end_clean();			
			main_content(ACCOUNT_EDIT, $content, '', 1);			
		}	
}
function admin_user_add() {
	global $db;
	if(isset($_POST['submit'])) {
			if($_POST['username'] == '') 
				$error[] = '<li>'.NO_USERNAME;
			if (!check_email($_POST['email'])) 
				$error[] = '<li>'.WRONG_EMAIL;
			if ($_POST['password1'] == '') 
				$error[] = '<li>'.NO_PASSWORD;
			if ($_POST['password1'] != $_POST['password2']) 
				$error[] = '<li>'.DIFFERENT_PW;				
			if (strlen($_POST['password1']) < PW_MIN_LENGTH) 
				$error[] = '<li>'.SHORT_PW.PW_MIN_LENGTH.SHORT_PW_1;		
			if ($_POST['username'] != '' AND $db->result(DB_PRE.'ecp_user', 'COUNT(ID)', 'username = "'.strsave($_POST['username']).'"')) 
				$error[] = '<li>'.ACCOUNT_ALLREADY_EXIST.' '.$_POST['username'];		
			if ($_POST['email'] != '' AND $db->result(DB_PRE.'ecp_user', 'COUNT(ID)', 'email = "'.strsave($_POST['email']).'"')) 
				$error[] = '<li>'.EMAIL_ALLREADY_EXIST.' '.$_POST['email'];
			if (@$_POST['sex'] != 'male'  AND @$_POST['sex'] != 'female') 
				$error[] = '<li>'.CHOOSE_SEX;		
			if(isset($error)) {
				table(ERROR, '<ul>'.implode('</li>', $error).'</ul>');
				$tpl = new smarty;
				$tpl->assign('countries', form_country($_POST['country']));
				ob_start();
				$tpl->display(DESIGN.'/tpl/admin/user_add.html');
				$content = ob_get_contents();
				ob_end_clean();
				main_content(REGISTER, $content, '',1);
			} else {
				$sql = sprintf('INSERT INTO '.DB_PRE.'ecp_user (`username`, `email`, `passwort`, `status`, `registerdate`, country) VALUES (\'%s\', \'%s\', \'%s\', %d, %d, \'%s\');', strsave(htmlspecialchars($_POST['username'])), strsave($_POST['email']), sha1($_POST['password1']), 1, time(), strsave($_POST['country']));
				if($db->query($sql)) {
					$userid = $db->last_id();				
					$db->query('INSERT INTO '.DB_PRE.'ecp_user_config (userID) VALUES ('.$userid.')');
					$db->query('INSERT INTO '.DB_PRE.'ecp_user_stats (userID) VALUES ('.$userid.')');
					update_rank($userid);	
					// Aktivierungscode erstellen
					$db->query('INSERT INTO '.DB_PRE.'ecp_user_groups (userID, gID) VALUES ('.$userid.', 3)');
					// Emailaktivierungstext aus DB holen und Wert einsetzen
					$row = $db->fetch_assoc('SELECT content, content2, options FROM '.DB_PRE.'ecp_texte WHERE lang = "'.LANGUAGE.'" AND name = "USER_ADD"');
					$search = array('{username}', '{clanname}', '{pageurl}', '{password}');
					$replace = array($_POST['username'], CLAN_NAME, SITE_URL, $_POST['password1']);
					$row['content'] = str_replace($search, $replace, $row['content']);
					echo $row['content'];
					if(send_email($_POST['email'], $row['content2'], $row['content'], $row['options'])) {
						table(INFO, REGISTER_SUCCESS3);
					} else {
						table(INFO, NO_EMAIL_SEND2);
					}
				}
			}
	} else {
		$tpl = new smarty;
		$tpl->assign('countries', form_country());
		ob_start();
		$tpl->display(DESIGN.'/tpl/admin/user_add.html');
		$content = ob_get_contents();
		ob_end_clean();
		main_content(REGISTER, $content, '',1);
	}
}
if (!isset($_SESSION['rights']['admin']['user']) AND !isset($_SESSION['rights']['superadmin'])) {
	table(ERROR, NO_ADMIN_RIGHTS);
} else {
	if(isset($_GET['func'])) {
		switch($_GET['func']) {
			case 'edit':
				admin_user_edit((int)$_GET['id']);
			break;
			case 'add':
				admin_user_add();
			break;											
			default:
				admin_user();
		}
	} else {
		admin_user();
	}
}
?>