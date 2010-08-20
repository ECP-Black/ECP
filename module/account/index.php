<?php
	function account() {
		global $db;
		if(isset($_SESSION['userID'])) {
			$tpl = new smarty;
			$last = $db->result(DB_PRE.'ecp_user', 'laststart', 'ID = '.$_SESSION['userID']);
			$tpl->assign('text', str_replace(array('{zeit}', '{username}'), array(date(LONG_DATE, $last), $_SESSION['username']), ACCOUNT_START_TEXT));
			$tpl->assign('awards', $db->result(DB_PRE.'ecp_awards', 'COUNT(awardID)', 'eingetragen > '.$last));
			$tpl->assign('downloads', $db->result(DB_PRE.'ecp_downloads', 'COUNT(dID)', 'datum > '.$last));
			$tpl->assign('clanwars', $db->result(DB_PRE.'ecp_wars', 'COUNT(warID)', 'status = 1 AND datum > '.$last));
			$tpl->assign('threads', $db->result(DB_PRE.'ecp_forum_threads', 'COUNT(threadID)', 'datum > '.$last));
			$tpl->assign('fcomments', $db->result(DB_PRE.'ecp_forum_comments', 'COUNT(comID)', 'adatum > '.$last));
			$tpl->assign('galleries', $db->result(DB_PRE.'ecp_gallery', 'COUNT(galleryID)', '(access = "" OR '.$_SESSION['access_search'].') AND datum > '.$last));
			$tpl->assign('links', $db->result(DB_PRE.'ecp_links', 'COUNT(linkID)', 'eingetragen > '.$last));
			$tpl->assign('messages', $db->result(DB_PRE.'ecp_messages', 'COUNT(msgID)', 'touser = '.$_SESSION['userID'].' AND readed = 0 AND datum > '.$last));
			$tpl->assign('news', $db->result(DB_PRE.'ecp_news', 'COUNT(newsID)', '(access = "" OR '.$_SESSION['access_search'].') AND (lang = "" OR lang = "'.LANGUAGE.'") AND datum < '.time().' AND datum > '.$last));
			$tpl->assign('shoutbox', $db->result(DB_PRE.'ecp_comments', 'COUNT(comID)', 'bereich = "shoutbox" AND datum > '.$last));
			$tpl->assign('surveys', $db->result(DB_PRE.'ecp_survey', 'COUNT(surveyID)', '(access = "" OR '.$_SESSION['access_search'].') AND start > '.$last.' AND start < '.time()));
			ob_start(); 
			$tpl->display(DESIGN.'/tpl/account/account_start.html');
			$content = ob_get_contents();
			ob_end_clean();
			main_content(OVERVIEW, $content, '',1);
		} else {
			// Login Formular
			$login = new smarty;
			ob_start();
			$login->display(DESIGN.'/tpl/account/account_login.html');
			$content = ob_get_contents();
			ob_end_clean();
			main_content(LOGIN, $content, '',1);
			// Anmeldeformular
			$tpl = new smarty;
			$tpl->assign('countries', form_country());
			ob_start();
			$tpl->display(DESIGN.'/tpl/account/account_register.html');
			$content = ob_get_contents();
			ob_end_clean();
			main_content(REGISTER, $content, '',1);
		}
	}
	function account_register() {
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
			if (strtolower($_SESSION['captcha']) != strtolower($_POST['captcha']))	
				$error[] = '<li>'.CAPTCHA_WRONG;
			if(isset($error)) {
				table(ERROR, '<ul>'.implode('</li>', $error).'</ul>');
				$tpl = new smarty;
				$tpl->assign('countries', form_country($_POST['country']));
				ob_start();
				$tpl->display(DESIGN.'/tpl/account/account_register.html');
				$content = ob_get_contents();
				ob_end_clean();
				main_content(REGISTER, $content, '',1);
			} else {
				$sql = sprintf('INSERT INTO '.DB_PRE.'ecp_user (`username`, `email`, `passwort`, `status`, `registerdate`, country) VALUES (\'%s\', \'%s\', \'%s\', %d, %d, \'%s\');', strsave(htmlspecialchars($_POST['username'])), strsave($_POST['email']), sha1($_POST['password1']), (SEND_ACCOUNT_CODE) ? 0 : 1, time(), strsave($_POST['country']));
				if($db->query($sql)) {
					$userid = $db->last_id();				
					$db->query('INSERT INTO '.DB_PRE.'ecp_user_config (userID) VALUES ('.$userid.')');
					$db->query('INSERT INTO '.DB_PRE.'ecp_user_stats (userID) VALUES ('.$userid.')');
					$db->query('INSERT INTO '.DB_PRE.'ecp_user_groups (userID, gID) VALUES ('.$userid.', 3)');						
					update_rank($userid);					
					if(SEND_ACCOUNT_CODE) {
						// Aktivierungscode erstellen
						$code = get_random_string(8, 2);
						$db->query('INSERT INTO '.DB_PRE.'ecp_user_codes (userID, code, art) VALUES ('.$userid.', "'.$code.'", "aktiv")');
						// Emailaktivierungstext aus DB holen und Wert einsetzen
						$row = $db->fetch_assoc('SELECT content, content2, options FROM '.DB_PRE.'ecp_texte WHERE lang = "'.LANGUAGE.'" AND name = "REGISTER_EMAIL"');
						$search = array('{username}', '{clanname}', '{pageurl}', '{aktivcode}', '{aktivlink}');
						$replace = array($_POST['username'], CLAN_NAME, SITE_URL, $code, SITE_URL.'?section=account&action=open&id='.$userid.'&key='.$code);
						$row['content'] = str_replace($search, $replace, $row['content']);
						if(send_email($_POST['email'], $row['content2'], $row['content'], $row['options'])) {
							table(INFO, REGISTER_SUCCESS);
						} else {
							table(INFO, NO_EMAIL_SEND);
						}
					} else {
						table(INFO, REGISTER_SUCCESS2);
					}
				}
			}
		} else {
			$tpl = new smarty;
			$tpl->assign('countries', form_country());
			ob_start();
			$tpl->display(DESIGN.'/tpl/account/account_register.html');
			$content = ob_get_contents();
			ob_end_clean();
			main_content(REGISTER, $content, '',1);
		}
	}
	function account_login() {
		global $db;
		if($_GET['mini']) {
			$_POST['username'] = $_POST['mini_username'];
			$_POST['password'] = $_POST['mini_passwort'];
		}
		if($_POST['username'] == '' OR $_POST['password'] == '') {
			$tpl = new smarty;
			$tpl->assign('error', NO_LOGIN_DATA);
			ob_start();
			$tpl->display(DESIGN.'/tpl/account/account_login.html');
			$content = ob_get_contents();
			ob_end_clean();
			main_content(LOGIN, $content, '',1);
		} else {
			$sql = 'SELECT ID, status, username, email, lastforum FROM '.DB_PRE.'ecp_user WHERE username = "'.strsave(htmlspecialchars($_POST['username'])).'" AND passwort = \''.sha1($_POST['password']).'\'';
			$row = $db->fetch_assoc($sql);
			if($row['ID']) {
				/* 	Status checken
					Status 0: Unaktivierter Account
					Status 1: Aktivierter Account
					Status 2: Gebannter Account
				*/
				if($row['status'] == 0) {
					account_aktiv($row['ID']);	
				} elseif ($row['status'] == 2) {
					$ban = $db->fetch_assoc('SELECT username, vonID, grund, bantime, endbantime FROM '.DB_PRE.'ecp_user_bans LEFT JOIN '.DB_PRE.'ecp_user ON (ID = vonID) WHERE userID = '.$row['ID'].' ORDER BY endbantime DESC LIMIT 1');
					if($ban['endbantime'] < time()) {
						$db->query('UPDATE '.DB_PRE.'ecp_user SET status = 1 WHERE ID = '.$row['ID']);
						$db->query('DELETE FROM '.DB_PRE.'ecp_user_bans WHERE userID = '.$row['ID']);
						$_SESSION['userID'] = $row['ID'];
						$_SESSION['username'] = $row['username'];
						$_SESSION['email'] = $row['email'];
						$_SESSION['lastforum']['time'] = $row['lastforum'];
						$db->query('UPDATE '.DB_PRE.'ecp_user_stats SET logins = logins + 1 WHERE userID = '.$row['ID']);
						$db->query('UPDATE '.DB_PRE.'ecp_online SET uID = '.$row['ID'].' WHERE IP = \''.$_SERVER['REMOTE_ADDR'].'\' AND uID = 0 LIMIT 1');
						$db->query('UPDATE '.DB_PRE.'ecp_user SET lastlogin = '.time().' WHERE ID = '.$row['ID']);
						// Falls Admin kein PW für Adminbereich
						$_SESSION['admin_verify'] = 1;
						if(isset($_POST['autologin'])) {
							setcookie('userID', $row['ID'], time()+(365*86400),  '/');
							setcookie('passwort', sha1(sha1($_POST['password'])), time()+(365*86400), '/');
						}
						update_rights();
						header1('?section=account');						
					}
					$search = array('{bantime}', '{banuser}', '{endbantime}');
					$repalce = array(date(LONG_DATE, $ban['bantime']), '<a href="?section=user&id='.$ban['vonID'].'">'.$ban['username'].'</a>', date(LONG_DATE, $ban['endbantime']));
					$bantxt = str_replace($search, $repalce, BANNED);
					table(ACCESS_DENIED, $bantxt.$ban['grund']);
				} elseif ($row['status'] == 1) {
					$_SESSION['userID'] = $row['ID'];
					$_SESSION['username'] = $row['username'];
					$_SESSION['email'] = $row['email'];
					$_SESSION['lastforum']['time'] = $row['lastforum'];
					$db->query('UPDATE '.DB_PRE.'ecp_user_stats SET logins = logins + 1 WHERE userID = '.$row['ID']);
					$db->query('UPDATE '.DB_PRE.'ecp_online SET uID = '.$row['ID'].' WHERE SID = \''.session_id().'\' AND uID = 0 LIMIT 1');
					$db->query('UPDATE '.DB_PRE.'ecp_user SET lastlogin = '.time().' WHERE ID = '.$row['ID']);
					// Falls Admin kein PW für Adminbereich
					$_SESSION['admin_verify'] = 1;
					if(isset($_POST['autologin'])) {
						setcookie('userID', $row['ID'], time()+(365*86400),  '/');
						setcookie('passwort', sha1(sha1($_POST['password'])), time()+(365*86400), '/');
					}
					update_rights();
					header1('?section=account');
				} else {
					header1('?section=account');
				}
			} else {
				$tpl = new smarty;
				$tpl->assign('error', WRONG_LOGIN_DATA);
				ob_start();
				$tpl->display(DESIGN.'/tpl/account/account_login.html');
				$content = ob_get_contents();
				ob_end_clean();
				main_content(LOGIN, $content, '',1);		
			}
		}
	}
	function account_aktiv($id, $code = '') {
		global  $db;
		if($id) {
			$status = $db->result(DB_PRE.'ecp_user', 'status', 'ID = '.$id);
			if($status == 0) {
				if($code != '')	{
					if($db->result(DB_PRE.'ecp_user_codes', 'COUNT(userID)', 'userID = '.$id.' AND code = \''.$code.'\' AND art = "aktiv"')) {
						$db->query('UPDATE '.DB_PRE.'ecp_user SET status = 1, lastlogin = '.time().' WHERE ID = '.$id);
						$db->query('DELETE FROM '.DB_PRE.'ecp_user_codes WHERE userID = '.$id.' AND art = "aktiv"');
						$row = $db->fetch_assoc('SELECT username, email FROM '.DB_PRE.'ecp_user WHERE ID = '.$id);
						$_SESSION['lastforum']['time'] = 0;
						$_SESSION['email'] = $row['email'];
						$_SESSION['username'] = $row['username'];
						$_SESSION['userID'] = $id;
						update_rights();
						header1('?section=account');
					} else {
						table(ERROR, WRONG_AKTIV_CODE);
						account_aktiv($id);
					}
				} else {
					$tpl = new smarty;
					$tpl->assign('userID', $id);
					$tpl->assign('error', WRONG_LOGIN_DATA);
					ob_start();
					$tpl->display(DESIGN.'/tpl/account/account_open.html');
					$content = ob_get_contents();
					ob_end_clean();			
					main_content(AKTIV_ACCOUNT, $content, '', 1);
				}
			} else {
				table(ERROR, NO_AKTIV_USER);
			}
		} else {
			table(ERROR, NO_AKTIV_USER);
		}
	}
	function account_logout() {
		global $db;
		$db->query('DELETE FROM '.DB_PRE.'ecp_online WHERE uID = '.$_SESSION['userID'].' OR SID = \''.session_id().'\'');
		foreach($_SESSION AS $key => $value) unset($_SESSION[$key]);
        setcookie('userID', '', time() - 316224000, '/');
        setcookie('passwort', '', time() - 316224000, '/');
        setcookie(session_name(), '', time() - 316224000, '/');
		if(session_destroy())
			header1('?section=account');
	}
	function account_send_pw() {
		if(isset($_POST['submit'])) {
			global $db;
			if($_POST['username'] == '' OR $_POST['email'] == '' OR !check_email($_POST['email']) OR $_POST['captcha'] == '') {
				table(ERROR, NOT_NEED_ALL_INPUTS);
				$tpl = new smarty();
				ob_start();
				$tpl->display(DESIGN.'/tpl/account/send_pw.html');
				$content = ob_get_contents();
				ob_end_clean();			
				main_content(ACCOUNT_SEND_PW, $content, '', 1);
			} elseif(!$db->result(DB_PRE.'ecp_user', 'COUNT(ID)', 'username = \''.strsave(htmlspecialchars($_POST['username'])).'\' AND email = \''.strsave($_POST['email']).'\'')) {
				table(ERROR, ACCOUNT_ERROR_SEND_PW);
				$tpl = new smarty();
				ob_start();
				$tpl->display(DESIGN.'/tpl/account/send_pw.html');
				$content = ob_get_contents();
				ob_end_clean();			
				main_content(ACCOUNT_SEND_PW, $content, '', 1);
			} elseif (strtolower($_POST['captcha']) != strtolower($_SESSION['captcha'])) {
				table(ERROR, CAPTCHA_WRONG);
				$tpl = new smarty();
				ob_start();
				$tpl->display(DESIGN.'/tpl/account/send_pw.html');
				$content = ob_get_contents();
				ob_end_clean();			
				main_content(ACCOUNT_SEND_PW, $content, '', 1);
			} else {
				$userid = $db->result(DB_PRE.'ecp_user', 'ID', 'username = \''.strsave(htmlspecialchars($_POST['username'])).'\' AND email = \''.strsave($_POST['email']).'\'');
				$db->query('DELETE FROM '.DB_PRE.'ecp_user_codes WHERE art="lost_pw" AND userID = '.$userid);
				$string = get_random_string(10, 2);
				$db->query('SELECT * FROM '.DB_PRE.'ecp_texte WHERE name = "ACCOUNT_SEND_PW"');
				$text = array();
				while($row = $db->fetch_assoc()) {
					$text[$row['lang']] = $row;								
				}
				$search = array('{username}', '{code}', '{link}');
				$replace = array($_POST['username'], $string, SITE_URL.'?section=account&action=change_pw&userid='.$userid.'&code='.$string);
				if(!isset($text[LANGUAGE]))	$lang = 'de'; else $lang = LANGUAGE;					
								
				if(send_email($_POST['email'], $text[$lang]['content2'], str_replace($search, $replace, $text[$lang]['content'])) AND $db->query('INSERT INTO '.DB_PRE.'ecp_user_codes (userID, code, art) VALUES ('.$userid.', \''.strsave($string).'\', \'lost_pw\')')) {	
					table(INFO, ACCOUNT_SUCCESS_SEND);
				} else {
					table(ERROR, EMAIL_NOT_SEND);
				}
			}
			unset($_SESSION['captcha']);
		} else {
			$tpl = new smarty();
			ob_start();
			$tpl->display(DESIGN.'/tpl/account/send_pw.html');
			$content = ob_get_contents();
			ob_end_clean();			
			main_content(ACCOUNT_SEND_PW, $content, '', 1);
		}
	}
	function account_change_pw($id, $code) {
		global $db;
		if($db->result(DB_PRE.'ecp_user_codes', 'COUNT(userID)', 'art="lost_pw" AND userID = '.$id.' AND code = \''.$code.'\'')) {
			if(isset($_POST['submit'])) {
				if($_POST['password1'] != $_POST['password2']) {
				 	table(ERROR, DIFFERENT_PW);	
					$tpl = new smarty();
					ob_start();
					$tpl->display(DESIGN.'/tpl/account/change_pw.html');
					$content = ob_get_contents();
					ob_end_clean();			
					main_content(ACCOUNT_SEND_PW, $content, '', 1);		
				} elseif (strlen($_POST['password1']) < PW_MIN_LENGTH) {
				 	table(ERROR, SHORT_PW.PW_MIN_LENGTH.SHORT_PW_1);	
					$tpl = new smarty();
					ob_start();
					$tpl->display(DESIGN.'/tpl/account/change_pw.html');
					$content = ob_get_contents();
					ob_end_clean();			
					main_content(ACCOUNT_SEND_PW, $content, '', 1);				 	
				} else {
					if($db->query('UPDATE '.DB_PRE.'ecp_user SET passwort = \''.sha1($_POST['password1']).'\' WHERE ID = '.$id)) {
						table(INFO, ACCOUNT_PW_SUCCESS);
						$db->query('DELETE FROM '.DB_PRE.'ecp_user_codes WHERE art="lost_pw" AND userID = '.$id.' AND code = \''.$code.'\'');
					}
				}
			} else {
				$tpl = new smarty();
				ob_start();
				$tpl->display(DESIGN.'/tpl/account/change_pw.html');
				$content = ob_get_contents();
				ob_end_clean();			
				main_content(ACCOUNT_SEND_PW, $content, '', 1);
			}
		} else {
			table(ERROR, ACCOUNT_WRONG_CODE);
		}
	}
	function account_user_pics() {
		global $db;
		$data = $db->fetch_assoc('SELECT avatar, user_pic FROM '.DB_PRE.'ecp_user WHERE ID= '.$_SESSION['userID']);
		$tpl = new Smarty();
		$tpl->assign('avatar', $data['avatar']);
		$tpl->assign('pic', $data['user_pic']);
		$tpl->assign('usermsg', str_replace(array('{maxkb}', '{maxx}', '{maxy}'), array(goodsize(USER_PIC_MAX), USER_PIC_X, USER_PIC_Y), ACCOUNT_MAX_PIC_SIZE));
		$tpl->assign('avatarmsg', str_replace(array('{maxkb}', '{maxx}', '{maxy}'), array(goodsize(AVATAR_MAX_SIZE), AVATAR_MAX_X, AVATAR_MAX_Y), ACCOUNT_MAX_PIC_SIZE));
		ob_start();
		$tpl->display(DESIGN.'/tpl/account/upload.html');
		$content = ob_get_contents();
		ob_end_clean();			
		main_content(ACCOUNT_PICTURES, $content, '', 1);
	}
	function account_del_avatar() {
		global $db;
		$pic = @$db->result(DB_PRE.'ecp_user', 'avatar', 'ID= '.$_SESSION['userID']);
		if($pic != '') {
			unlink('images/avatar/'.$_SESSION['userID'].'_'.$pic);
			if($db->query('UPDATE '.DB_PRE.'ecp_user SET avatar = "" WHERE ID = '.$_SESSION['userID'])) {
				header1('?section=account&action=avatar');
			}
		} 
		header1('?section=account&action=avatar');
	}
	function account_del_userpic() {
		global $db;
		$pic = @$db->result(DB_PRE.'ecp_user', 'user_pic', 'ID= '.$_SESSION['userID']);
		if($pic != '') {
			unlink('images/user/'.$_SESSION['userID'].'_'.$pic);
			if($db->query('UPDATE '.DB_PRE.'ecp_user SET user_pic = "" WHERE ID = '.$_SESSION['userID'])) {
				header1('?section=account&action=avatar');
			}
		} 
		header1('?section=account&action=avatar');
	}	
	function account_avatar_upload() {
		global $db;
		if($db->result(DB_PRE.'ecp_user', 'avatar', 'ID = '.$_SESSION['userID']) != '') {
			table(ERROR, ACCOUNT_AVATAR_EXIST);
			account_user_pics();
		} else {
			if($_FILES['avatar']['size'] > AVATAR_MAX_SIZE) {
				table(ERROR, str_replace('{oversize}', goodsize($_FILES['avatar']['size']-AVATAR_MAX_SIZE), ACCOUNT_AVATAR_TO_BIG));
				account_user_pics();
			} elseif (count(getimagesize($_FILES['avatar']['tmp_name']))< 2) {
				table(ERROR, ACCOUNT_WRONG_FILE_TYPE);
				account_user_pics();
			} else {
				$size = getimagesize($_FILES['avatar']['tmp_name']);
				$xmore = $size[0]-AVATAR_MAX_X;
				$ymore = $size[1]-AVATAR_MAX_Y;
				$sha1 = sha1_file($_FILES['avatar']['tmp_name']).'.'.str_replace('image/','', $size['mime']);
				if(($xmore > 0 OR $ymore > 0) AND ($size['mime'] == 'image/jpeg' OR $size['mime'] == 'image/pjpeg' OR $size['mime'] == 'image/jpg')) {
					if($xmore > $ymore)  {
						resize_picture($_FILES['avatar']['tmp_name'], AVATAR_MAX_X, 'images/avatar/'.$_SESSION['userID'].'_'.$sha1, 85, 1);
					} else {
						resize_picture($_FILES['avatar']['tmp_name'], AVATAR_MAX_Y, 'images/avatar/'.$_SESSION['userID'].'_'.$sha1, 85, 0);
					}
					if($db->query('UPDATE '.DB_PRE.'ecp_user SET avatar = \''.strsave($sha1).'\' WHERE ID = '.$_SESSION['userID'])) {
						header1('?section=account&action=avatar');
					}					
				} elseif ($size[0] > AVATAR_MAX_X OR $size[1] > AVATAR_MAX_Y) {
					table(ERROR, ACCOUNT_AVATAR_TO_BIG2);
					account_user_pics();
				} else {
					if(move_uploaded_file($_FILES['avatar']['tmp_name'], 'images/avatar/'.$_SESSION['userID'].'_'.$sha1)) {
						umask(0);
						chmod('images/avatar/'.$_SESSION['userID'].'_'.$sha1, CHMOD);
						if($db->query('UPDATE '.DB_PRE.'ecp_user SET avatar = \''.strsave($sha1).'\' WHERE ID = '.$_SESSION['userID'])) {
							header1('?section=account&action=avatar');
						}
					}
				}
			}
		}
	}
	function account_user_upload() {
		global $db;
		if($db->result(DB_PRE.'ecp_user', 'user_pic', 'ID = '.$_SESSION['userID']) != '') {
			table(ERROR, ACCOUNT_USERPIC_EXIST);
			account_user_pics();
		} else {
			if($_FILES['user']['size'] > USER_PIC_MAX) {
				table(ERROR, str_replace('{oversize}', goodsize($_FILES['user']['size']-USER_PIC_MAX), ACCOUNT_USERPIC_TO_BIG));
				account_user_pics();
			} elseif (count(getimagesize($_FILES['user']['tmp_name']))< 2) {
				table(ERROR, ACCOUNT_WRONG_FILE_TYPE);
				account_user_pics();
			} else {
				$size = getimagesize($_FILES['user']['tmp_name']);
				print_r($size);
				$xmore = $size[0]-USER_PIC_X;
				$ymore = $size[1]-USER_PIC_Y;
				$sha1 = sha1_file($_FILES['user']['tmp_name']).'.'.str_replace('image/','', $size['mime']);
				if(($xmore > 0 OR $ymore > 0) AND ($size['mime'] == 'image/jpeg' OR $size['mime'] == 'image/pjpeg' OR $size['mime'] == 'image/jpg')) {
					if($xmore > $ymore)  {
						resize_picture($_FILES['user']['tmp_name'], USER_PIC_X, 'images/user/'.$_SESSION['userID'].'_'.$sha1, 85, 1);
					} else {
						resize_picture($_FILES['user']['tmp_name'], USER_PIC_Y, 'images/user/'.$_SESSION['userID'].'_'.$sha1, 85, 0);
					}
					if($db->query('UPDATE '.DB_PRE.'ecp_user SET user_pic = \''.strsave($sha1).'\' WHERE ID = '.$_SESSION['userID'])) {
						header1('?section=account&action=avatar');
					}					
				} elseif ($size[0] > USER_PIC_X OR $size[1] > USER_PIC_Y) {
					table(ERROR, ACCOUNT_USERPIC_TO_BIG2);
					account_user_pics();
				} else {
					if(move_uploaded_file($_FILES['user']['tmp_name'], 'images/user/'.$_SESSION['userID'].'_'.$sha1)) {
						umask(0);
						chmod('images/user/'.$_SESSION['userID'].'_'.$sha1, CHMOD);
						if($db->query('UPDATE '.DB_PRE.'ecp_user SET user_pic = \''.strsave($sha1).'\' WHERE ID = '.$_SESSION['userID'])) {
							header1('?section=account&action=avatar');
						}
					}
				}
			}
		}
	}	
	function account_edit() {
		global $db;
		if(isset($_POST['submit'])) {
			if($db->result(DB_PRE.'ecp_user', 'COUNT(ID)', 'username = \''.strsave(htmlspecialchars($_POST['username'])).'\' AND ID != '.$_SESSION['userID']) OR $_POST['username'] == '') {
				$_POST['username'] = $db->result(DB_PRE.'ecp_user', 'username', 'ID = '.$_SESSION['userID']);
				table(ERROR, ACCOUNT_ALLREADY_EXIST);
			}
			if($db->result(DB_PRE.'ecp_user', 'COUNT(ID)', 'email = \''.strsave($_POST['username']).'\' AND ID != '.$_SESSION['userID']) OR !check_email($_POST['email'])) {
				$_POST['email'] = $db->result(DB_PRE.'ecp_user', 'email', 'ID = '.$_SESSION['userID']);
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
						headset = \'%s\',aboutme = \'%s\', wohnort = \'%s\', aim = \'%s\', koord = \'%s\'  
					WHERE ID = '.$_SESSION['userID'],
					strsave(htmlspecialchars(@$_POST['username'])), strsave(@$_POST['email']), strsave(@$_POST['country']), (@$_POST['sex'] == 'male' ? 'male' : 'female'), strsave(comment_save(@$_POST['signatur'])),
					strsave(htmlspecialchars(@$_POST['realname'])), (int)@$geburtstag[2].'-'.(int)@$geburtstag[1].'-'.(int)@$geburtstag[0], strsave(htmlspecialchars(check_url(@$_POST['homepage']))),
					strsave(htmlspecialchars(@$_POST['icq'])),strsave(htmlspecialchars(@$_POST['msn'])),strsave(htmlspecialchars(@$_POST['yahoo'])),
					strsave(htmlspecialchars(@$_POST['skype'])),strsave(htmlspecialchars(@$_POST['xfire'])),strsave(htmlspecialchars(@$_POST['clanname'])),
					strsave(htmlspecialchars(@$_POST['clanirc'])),strsave(htmlspecialchars(check_url(@$_POST['clanhomepage']))),strsave(htmlspecialchars(@$_POST['clanhistory'])),
					strsave(htmlspecialchars(@$_POST['cpu'])),strsave(htmlspecialchars(@$_POST['mainboard'])),strsave(htmlspecialchars(@$_POST['ram'])),
					strsave(htmlspecialchars(@$_POST['gkarte'])),strsave(htmlspecialchars(@$_POST['skarte'])),strsave(htmlspecialchars(@$_POST['monitor'])),
					strsave(htmlspecialchars(@$_POST['maus'])),strsave(htmlspecialchars(@$_POST['tastatur'])),strsave(htmlspecialchars(@$_POST['mauspad'])),
					strsave(htmlspecialchars(@$_POST['internet'])),strsave(htmlspecialchars(@$_POST['festplatte'])),strsave(htmlspecialchars(@$_POST['headset'])),strsave(comment_save(@$_POST['aboutme'])), strsave(htmlspecialchars(@$_POST['wohnort'])), strsave(htmlspecialchars(@$_POST['aim'])), strsave(htmlspecialchars(@$_POST['koord'])));
					$_SESSION['username'] = htmlspecialchars($_POST['username']);
					$_SESSION['email'] = $_POST['email'];
			if($db->query($sql)) {
				if($_POST['password1'] != '') {
					if($_POST['password1'] != $_POST['password2']) {
						table(ERROR, DIFFERENT_PW);
					} elseif (strlen($_POST['password1']) < PW_MIN_LENGTH) {
						table(ERROR, SHORT_PW.PW_MIN_LENGTH.SHORT_PW_1);
					} elseif ($db->result(DB_PRE.'ecp_user','passwort', 'ID = '.$_SESSION['userID']) != sha1($_POST['password'])) {
						table(ERROR, WRONG_OLD_PW);
					} else {
						$db->query('UPDATE '.DB_PRE.'ecp_user SET passwort = \''.strsave(sha1($_POST['password1'])).'\' WHERE ID = '.$_SESSION['userID']);
						table(INFO, PW_SUCCESS_CHANGE);
					}
				}
				table(INFO, ACCOUNT_EDIT_SUCCESS);
				unset($_POST['submit']);
				account_edit();
			}
		} else {
			$tpl = new smarty;
			$row = $db->fetch_assoc('SELECT `username`, `email`, `country`, `sex`, `signatur`, `realname`, `wohnort`, `geburtstag`, `homepage`, `icq`, `msn`, `yahoo`, `skype`, `xfire`, 
											`clanname`, `clanirc`, `clanhomepage`, `clanhistory`, `cpu`, `mainboard`, `ram`, `gkarte`, `skarte`, `monitor`, `maus`, `tastatur`, `mauspad`, 
											`internet`, `festplatte`, `headset`, `aboutme`, `ondelete`, aim, koord FROM '.DB_PRE.'ecp_user WHERE ID = '.$_SESSION['userID']);
			if($row['ondelete'])
				table(INFO, str_replace('{zeit}', date('d.m.Y H:i', $row['ondelete']), ACCOUNT_DELETE_ON));
			//$row['birthday'] = date('d.m.Y', strtotime($row['geburtstag']));
			$geb = explode('-', $row['geburtstag']);
			$row['birthday'] = "$geb[2].$geb[1].$geb[0]";
			foreach($row AS $key=>$value) $tpl->assign($key, $value);
			ob_start();
			$tpl->assign('countries', form_country($row['country']));
			$tpl->display(DESIGN.'/tpl/account/account_edit.html');
			$content = ob_get_contents();
			ob_end_clean();			
			main_content(ACCOUNT_EDIT, $content, '', 1);			
		}
	}
	function account_del_account() {
		global $db;
		if($db->result(DB_PRE.'ecp_user', 'ondelete', 'ID = '.$_SESSION['userID'])) {
			if($db->query('UPDATE '.DB_PRE.'ecp_user SET ondelete = 0 WHERE ID = '.$_SESSION['userID'])) {
				$db->query('DELETE FROM '.DB_PRE.'ecp_user_codes WHERE userID = '.$_SESSION['userID'].' AND art = \'account_del\'');
				table(INFO, ACCOUNT_DELETE_REMOVE);
			}
		} else {
			if(isset($_GET['agree'])) {
				$str = get_random_string(10, 2);
				$db->query('DELETE FROM '.DB_PRE.'ecp_user_codes WHERE userID = '.$_SESSION['userID'].' AND art = \'account_del\'');
				if(send_email($_SESSION['email'], ACCOUNT_DELETE, CONFIRM_LINK.': '.SITE_URL.'?section=account&action=confirmdel&code='.$str.'&id='.$_SESSION['userID'])) {
					if($db->query('INSERT INTO '.DB_PRE.'ecp_user_codes (`userID`, `code`, `art`) VALUES (\''.$_SESSION['userID'].'\', \''.strsave($str).'\', \'account_del\')')) {
						table(INFO, EMAIL_SEND_SUCCESS);
					}
				} else {
					if($db->query('UPDATE '.DB_PRE.'ecp_user SET ondelete = '.strtotime('tomorrow 23:59').' WHERE ID = '.$_SESSION['userID'])) {
						table(INFO, str_replace('{zeit}', date('d.m.Y H:i', strtotime('tomorrow 23:59')), ACCOUNT_DELETE_ON));
					}
				}
			} else {
				table(INFO, ACCOUNT_DEL_QUEST);
			}
		}
	}
	function account_del_confirm($id, $code) {
		global $db;
		if($db->result(DB_PRE.'ecp_user_codes', 'COUNT(userID)', 'userID = '.$id.' AND code = \''.$code.'\' AND art = \'account_del\'')) {
			if($db->query('UPDATE '.DB_PRE.'ecp_user SET ondelete = '.strtotime('tomorrow 23:59').' WHERE ID = '.$id)) {
				table(INFO, str_replace('{zeit}', date('d.m.Y H:i', strtotime('tomorrow 23:59')), ACCOUNT_DELETE_ON));
			}				
		} else {
			table(ERROR, ACCOUNT_WRONG_CODE);
		}
	}
	function account_msgbox() {
		global $db;
		$tpl = new Smarty();
		$anzahl = $db->result(DB_PRE.'ecp_messages', 'COUNT(msgID)', 'touser = '.$_SESSION['userID'].' AND del = 0');
		if($anzahl) {
			$limits = get_sql_limit($anzahl, LIMIT_MESSAGES);
			$db->query('SELECT `msgID`, `fromuser`, `title`, `datum`, `readed`, username, country FROM '.DB_PRE.'ecp_messages LEFT JOIN '.DB_PRE.'ecp_user ON (ID = fromuser) WHERE del = 0 AND touser = '.$_SESSION['userID'].'  ORDER BY datum DESC LIMIT '.$limits[1].','.LIMIT_MESSAGES);
			$msgin = array();
			while($row = $db->fetch_assoc()) {
				$row['datum'] = date(LONG_DATE, $row['datum']);
				$msgin[] = $row;
			}
			$tpl->assign('messages', $msgin);
			if($limits[0] > 1)
				$tpl->assign('seiten', makepagelink_ajax('#', 'return load_msges({nr}, \'in\');', @$_GET['page'], $limits[0]));						
		}
		$anzahl = $db->result(DB_PRE.'ecp_messages', 'COUNT(msgID)', 'fromuser = '.$_SESSION['userID'].' AND fromdel = 0');
		if($anzahl) {
			$limits = get_sql_limit($anzahl, LIMIT_MESSAGES);
			$db->query('SELECT `msgID`, `touser`, `title`, `datum`, `readed`, username, country FROM '.DB_PRE.'ecp_messages LEFT JOIN '.DB_PRE.'ecp_user ON (ID = touser) WHERE fromdel = 0 AND fromuser = '.$_SESSION['userID'].'  ORDER BY datum DESC LIMIT '.$limits[1].','.LIMIT_MESSAGES);
			$msgout = array();
			while($row = $db->fetch_assoc()) {
				$row['datum'] = date(LONG_DATE, $row['datum']);
				$msgout[] =$row;
			}
			$tpl->assign('messagesout', $msgout);
			if($limits[0] > 1)
				$tpl->assign('seitenout', makepagelink_ajax('#', 'return load_msges({nr}, \'out\');', @$_GET['page'], $limits[0]));								
		}
		ob_start();
		$tpl->display(DESIGN.'/tpl/account/messages.html');
		$content = ob_get_contents();
		ob_end_clean();			
		main_content(MESSAGES, $content, '', 1);
	}	
	function account_msg_massdel() {
		global $db;
		if(count($_POST['delmsg'])) {
			foreach($_POST['delmsg'] AS $value) {
				@$ids .= ' OR msgID = '.(int)$value;
			}
			$result = $db->query('SELECT msgID, fromuser, touser FROM '.DB_PRE.'ecp_messages WHERE '.substr($ids, 4));
			while($row = mysql_fetch_assoc($result)) {
				if($row['fromuser'] == $_SESSION['userID']) {
					$db->query('UPDATE '.DB_PRE.'ecp_messages SET fromdel =1 WHERE msgID = '.$row['msgID']);
				} elseif ($row['touser'] == $_SESSION['userID']) {
					$db->query('UPDATE '.DB_PRE.'ecp_messages SET del =1 WHERE msgID = '.$row['msgID']);
				}
			}
		} 
		account_msgbox();
	}
	function account_msg_read($id) {
		global $db;
		$msg = $db->fetch_assoc('SELECT `touser`, `fromuser`, `title`, `msg`, `del`, `fromdel`, `datum`, `readed` FROM '.DB_PRE.'ecp_messages WHERE msgID = '.$id);
		if(isset($msg['touser'])) {
			if($msg['touser'] == $_SESSION['userID']) {
				if(!$msg['readed']) $db->query('UPDATE '.DB_PRE.'ecp_messages SET readed = 1 WHERE msgID = '.$id);
				$tpl = new smarty;
				$tpl->assign('in', 1);
				$tpl->assign('id', $id);
				$tpl->assign('userid', $msg['fromuser']);
				$tpl->assign('msg', bb_code(nl2br($msg['msg'])));
				$tpl->assign('del', $msg['del']);
				$tpl->assign('datum', date(LONG_DATE, $msg['datum']));
				if($msg['fromuser']) {
					$row = @$db->fetch_assoc('SELECT username, country FROM '.DB_PRE.'ecp_user WHERE ID= '.$msg['fromuser']);
					$tpl->assign('username', $row['username']);
					$tpl->assign('country', $row['country']);
				}
				$tpl->assign('title', $msg['title']);
				ob_start();
				$tpl->display(DESIGN.'/tpl/account/message_read.html');
				$content = ob_get_contents();
				ob_end_clean();			
				main_content(MESSAGE, $content, '', 1);						
			} elseif ($msg['fromuser'] == $_SESSION['userID']) {
				$tpl = new smarty;
				$tpl->assign('id', $id);
				$tpl->assign('msg', bb_code(nl2br($msg['msg'])));
				$tpl->assign('userid', $msg['touser']);
				$tpl->assign('fromdel', $msg['fromdel']);
				$tpl->assign('datum', date(LONG_DATE, $msg['datum']));
				$row = @$db->fetch_assoc('SELECT username, country FROM '.DB_PRE.'ecp_user WHERE ID= '.$msg['touser']);
				$tpl->assign('username', $row['username']);
				$tpl->assign('country', $row['country']);				
				$tpl->assign('title', $msg['title']);
				ob_start();
				$tpl->display(DESIGN.'/tpl/account/message_read.html');
				$content = ob_get_contents();
				ob_end_clean();			
				main_content(MESSAGE, $content, '', 1);
			} else {
				table(ERROR, NO_ACCESS_RIGHTS);
				account_msgbox();
			}
		} else {
			table(ERROR, NO_ENTRIES_ID);
			account_msgbox();
		}
	}
	function account_del_msg($id) {
		global $db;
		$msg = $db->fetch_assoc('SELECT fromuser, touser FROM '.DB_PRE.'ecp_messages WHERE msgID = '.$id);
		if(isset($msg['fromuser'])) {
			if($msg['fromuser'] == $_SESSION['userID']) {
				if($db->query('UPDATE '.DB_PRE.'ecp_messages SET fromdel =1 WHERE msgID = '.$id)) {
					account_msgbox();
				}
			} elseif ($msg['touser'] == $_SESSION['userID']) {
				if($db->query('UPDATE '.DB_PRE.'ecp_messages SET del =1 WHERE msgID = '.$id)) {
					account_msgbox();
				}					
			} else {
				table(ERROR, NO_ACCESS_RIGHTS);
				account_msgbox();
			}
		} else {
			table(EROR, NO_ENTRIES_ID);
			account_msgbox();
		}	
	}
	function account_new_msg() {
		global $db;
		if(isset($_POST['submit'])) {
			if($_POST['message'] == '' OR $_POST['username'] == '') {
				table(ERROR, NOT_NEED_ALL_INPUTS);
				$tpl = new smarty;
				ob_start();
				$tpl->display(DESIGN.'/tpl/account/message_add.html');
				$content = ob_get_contents();
				ob_end_clean();			
				main_content(MESSAGE_NEW, $content, '', 1);
			} else {
				$id = $db->result(DB_PRE.'ecp_user', 'ID', 'username = \''.strsave($_POST['username']).'\'');
				$last = @$db->result(DB_PRE.'ecp_messages', 'datum', 'touser = '.$id.' AND fromuser = '.$_SESSION['userID']);
				if(($last+SPAM_MESSAGE) > time()) {
					table(SPAM_PROTECTION, str_replace(array('{sek}', '{zeit}'), array(SPAM_MESSAGE, ($last+SPAM_MESSAGE-time())), SPAM_PROTECTION_MSG));
					$tpl = new smarty;
					ob_start();
					$tpl->display(DESIGN.'/tpl/account/message_add.html');
					$content = ob_get_contents();
					ob_end_clean();			
					main_content(MESSAGE_NEW, $content, '', 1);
				} else {
					if($id == $_SESSION['userID']) {
						table(ERROR, MSG_NOT_TO_YOURSELF);
						$tpl = new smarty;
						ob_start();
						$tpl->display(DESIGN.'/tpl/account/message_add.html');
						$content = ob_get_contents();
						ob_end_clean();			
						main_content(MESSAGE_NEW, $content, '', 1);					
					} elseif($id AND message_send($id, $_SESSION['userID'], $_POST['title'], $_POST['message'])) {
						table(INFO, MSG_SUCCESS_SEND);
						account_msgbox();
					} else {
						table(ERROR, USER_NOT_FOUND);	
						$tpl = new smarty;
						ob_start();
						$tpl->display(DESIGN.'/tpl/account/message_add.html');
						$content = ob_get_contents();
						ob_end_clean();			
						main_content(MESSAGE_NEW, $content, '', 1);					
					}
				}
			}
		} else {
			$tpl = new smarty;
			ob_start();
			$tpl->display(DESIGN.'/tpl/account/message_add.html');
			$content = ob_get_contents();
			ob_end_clean();			
			main_content(MESSAGE_NEW, $content, '', 1);
		}
	}
	function account_buddy() {
		global $db;
		$db->query('SELECT buddyID, username, country, uID as online, user_pic, lastlogin, sex FROM '.DB_PRE.'ecp_buddy LEFT JOIN '.DB_PRE.'ecp_user ON (buddyID = ID) LEFT JOIN '.DB_PRE.'ecp_online ON (uID = buddyID AND lastklick > '.(time()-SHOW_USER_ONLINE).') WHERE userID = '.$_SESSION['userID'].' GROUP BY buddyID ORDER BY online DESC,username ASC');
		$own = array();
		$other = array();		
		while($row = $db->fetch_assoc()) {
			if($row['lastlogin'] == 0) {
				$row['lastlogin'] = NEVER_LOGGED_IN;
 			} else
			$row['lastlogin'] = date(SHORT_DATE, $row['lastlogin']);
			$own[] = $row;
		}
		$db->query('SELECT userID, buddyID, username, country, uID as online, user_pic, lastlogin, sex FROM '.DB_PRE.'ecp_buddy LEFT JOIN '.DB_PRE.'ecp_user ON (userID = ID) LEFT JOIN '.DB_PRE.'ecp_online ON (uID = userID AND lastklick > '.(time()-SHOW_USER_ONLINE).') WHERE buddyID = '.$_SESSION['userID'].' GROUP BY userID ORDER BY online DESC, username ASC');		
		while($row = $db->fetch_assoc()) {
			if($row['lastlogin'] == 0) {
				$row['lastlogin'] = NEVER_LOGGED_IN;
 			} else			
			$row['lastlogin'] = date(SHORT_DATE, $row['lastlogin']);
			$other[] = $row;
		}		
		$tpl = new smarty;
		$tpl->assign('own', $own);
		$tpl->assign('other', $other);
		ob_start();
		$tpl->display(DESIGN.'/tpl/account/buddyliste.html');
		$content = ob_get_contents();
		ob_end_clean();			
		main_content(BUDDYLIST, $content, '', 1);			
	}
	function account_stats() {
		global $db, $countries;
		$tpl = new smarty;
		$user = $db->fetch_assoc('SELECT `registerdate`, rankname, `clicks`, `logins`, `comments`, a.money, iconname, `msg_s`, `msg_r`, `profilhits`, `scheine`, `2er`, `3er`, `4er`, COUNT(b.scheinID) as scheine FROM '.DB_PRE.'ecp_user LEFT JOIN '.DB_PRE.'ecp_user_stats as a ON (a.userID = ID) LEFT JOIN '.DB_PRE.'ecp_ranks ON (rID = rankID) LEFT JOIN '.DB_PRE.'ecp_lotto_scheine as b ON (b.userID = ID) WHERE ID = '.$_SESSION['userID'].' GROUP BY ID');
		$db->query('SELECT SUM(gewinn) as gewinn, art FROM '.DB_PRE.'ecp_lotto_gewinner WHERE userID = '.$_SESSION['userID'].' GROUP BY art');
		$user['wonmoney'] = 0;
		$user['2ermoney'] = 0;
		$user['3ermoney'] = 0;
		$user['4ermoney'] = 0;
		while($row = $db->fetch_assoc()) {
			$user['wonmoney'] += $row['gewinn'];
			$user[$row['art'].'ermoney'] = $row['gewinn'];
		}
		$user['runden'] = $db->result(DB_PRE.'ecp_lotto_scheine', 'COUNT(DISTINCT(rundenID)) as runden', 'userID = '.$_SESSION['userID']);
		$user['gesamtrunden'] = mysql_result($db->query('SHOW TABLE STATUS LIKE "'.DB_PRE.'ecp_lotto_runden"'),0, 'Auto_increment')-1;
		$user['tage'] = ceil((time() - $user['registerdate'])/86400);
		$user['teilqoute'] = format_nr($user['runden']/($user['gesamtrunden'] == 0 ? 1 : $user['gesamtrunden'])*100,2);
		$user['scheinrunde'] = format_nr($user['scheine']/($user['runden'] == 0 ? 1 : $user['runden']),2);
		$user['winscheine'] = format_nr($user['2er']+$user['3er']+$user['4er']);
		$user['winqoute'] = format_nr($user['winscheine']/($user['scheine'] == 0 ? 1 : $user['scheine'])*100,2);
		$user['registerdate'] = date(LONG_DATE, $user['registerdate']);
		$user['2erpro'] = format_nr($user['2er']/($user['winscheine'] == 0 ? 1 : $user['winscheine'])*100,2);
		$user['3erpro'] = format_nr($user['3er']/($user['winscheine'] == 0 ? 1 : $user['winscheine'])*100,2);
		$user['4erpro'] = format_nr($user['4er']/($user['winscheine'] == 0 ? 1 : $user['winscheine'])*100,2);
		$user['2ermpro'] = format_nr($user['2ermoney']/($user['wonmoney'] == 0 ? 1 : $user['wonmoney'])*100,2);
		$user['3ermpro'] = format_nr($user['3ermoney']/($user['wonmoney'] == 0 ? 1 : $user['wonmoney'])*100,2);
		$user['4ermpro'] = format_nr($user['4ermoney']/($user['wonmoney'] == 0 ? 1 : $user['wonmoney'])*100,2);
		foreach($user AS $key=>$value) { 
			if($key == 'clicks' OR $key == 'comments' OR $key == 'gesamtrunden' OR $key == 'runden' OR $key == 'msg_s' OR $key == 'msg_r' OR $key == 'profilhits' OR $key == 'scheine' OR $key == '2er' OR $key == '3er' OR $key == '4er') $value = format_nr($value);
			if($key == 'money' OR $key == 'wonmoney' OR $key == '2ermoney' OR $key == '3ermoney' OR $key == '4ermoney') $value = format_nr($value, 2);
			$tpl->assign($key, $value);
		}
		$db->query('SELECT `awardID`, `eventname`, `eventdatum`, `url`, `platz`, `teamID`, `gID`, `preis`, tname, icon, gamename, COUNT(comID) as comments FROM `'.DB_PRE.'ecp_awards` LEFT JOIN '.DB_PRE.'ecp_teams ON tID = teamID LEFT JOIN '.DB_PRE.'ecp_wars_games ON gameID = gID LEFT JOIN '.DB_PRE.'ecp_comments ON (bereich = "awards" AND subID = awardID) WHERE spieler LIKE "%,'.$_SESSION['userID'].',%" GROUP BY awardID ORDER BY eventdatum DESC');
		$awards = array();
		while($row = $db->fetch_assoc()) {
			$row['eventdatum'] = date('d.m.Y', $row['eventdatum']);
			$awards[] = $row;
		}
		$tpl->assign('awards', $awards);	
		$tpl->assign('award', count($awards));
		$db->query('SELECT `warID`, '.DB_PRE.'ecp_wars.datum, `result`, `resultscore`, `tname`, `oppname`, `country`, '.DB_PRE.'ecp_wars_opp.homepage, `icon`, `gamename`, `matchtypename`, COUNT(comID) as comments 
					FROM '.DB_PRE.'ecp_wars 
					LEFT JOIN '.DB_PRE.'ecp_teams ON '.DB_PRE.'ecp_teams.tID = '.DB_PRE.'ecp_wars.tID 
					LEFT JOIN '.DB_PRE.'ecp_wars_games ON gID = gameID 
					LEFT JOIN '.DB_PRE.'ecp_wars_opp ON oID = oppID 
					LEFT JOIN '.DB_PRE.'ecp_wars_matchtype ON mID = matchtypeID 
					LEFT JOIN '.DB_PRE.'ecp_comments ON (subID = warID AND bereich = "clanwars") 
					WHERE status = 1 AND ownplayers LIKE "%,'.$_SESSION['userID'].',%"
					GROUP BY warID
					ORDER BY datum DESC');
		$clanwars = array();
		while($row = $db->fetch_assoc()) {
			$row['datum'] = date('d.m.y', $row['datum']);
			$row['countryname'] = $countries[$row['country']];
			$clanwars[] = $row;
		}	
		$tpl->assign('clanwars', $clanwars);		
		$tpl->assign('clanwar', count($clanwars));
		ob_start();
		$tpl->display(DESIGN.'/tpl/user/user_stats.html');
		$content = ob_get_contents();
		ob_end_clean();			
		main_content(STATS, $content, '', 1);		
	}
	function account_last_visit() {
		global $db;
		if($db->query('UPDATE '.DB_PRE.'ecp_user SET laststart = '.time().' WHERE ID = '.$_SESSION['userID'])) {
			header1('?section=account');
		}
	}
	// Funktionen nur für Angemelde User //
$conditions = array('LIMIT' 	=> LIMIT_COMMENTS,
					'ORDER'		=> 'DESC',
					'SPAM'		=> SPAM_USER_GB_COMMENTS,
					'section'   => 'account');	
	if(isset($_GET['action']) AND isset($_SESSION['userID'])) {
		switch($_GET['action']) {
			case 'logout': 
				account_logout();
			break;
			case 'lastvisit': 
				account_last_visit();
			break;			
			case 'buddy': 
				account_buddy();
			break;			
			case 'avatar': 
				account_user_pics();
			break;
			case 'delavatar': 
				account_del_avatar();
			break;	
			case 'user_avatar':
				account_avatar_upload();
			break;
			case 'user_pic':
				account_user_upload();
			break;	
			case 'edit':
				account_edit();
			break;
			case 'msgbox':
				account_msgbox();
			break;
			case 'delaccount':
				account_del_account();
			break;								
			case 'deluserpic':
				account_del_userpic();
			break;
			case 'massdel':
				account_msg_massdel();
			break;
			case 'readmsg':
				account_msg_read((int)$_GET['id']);
			break;						
			case 'confirmdel':
				account_del_confirm((int)$_GET['id'], strsave($_GET['code']));
			break;
			case 'delmsg':
				account_del_msg((int)$_GET['id']);
			break;		
			case 'newmsg':
				account_new_msg();
			break;	
			case 'stats':
				account_stats();
			break;			
			case 'guestbook':
	            $conditions['action'] = 'add';
	            $conditions['link'] = '?section=account&action=guestbook';
	            comments_get('user', $_SESSION['userID'], $conditions, 0,1, "user");
	         break;
	         case 'addcomment':
	            $conditions['action'] = 'add';
	            $conditions['link'] = '?section=account&action=guestbook';
	            comments_add('user', $_SESSION['userID'], $conditions, "user");      
	         break;
	         case 'editcomment':
	            $conditions['action'] = 'edit';
	            $conditions['link'] = '?section=account&action=guestbook';
	            comments_edit('user', $_SESSION['userID'], (int)$_GET['id'], $conditions, "user");      
	         break;				
			default:
				account();
		}	
	// Funktionen für nicht angemelde User //
	} elseif (isset($_GET['action'])) {
		switch($_GET['action']) {
			case 'register':
				account_register();
			break;
			case 'login':
				account_login();
			break;	
			case 'open':
				account_aktiv((int)$_GET['id'], strsave(@$_GET['key']));
			break;	
			case 'sendpw':
				account_send_pw();
			break;
			case 'confirmdel':
				account_del_confirm((int)$_GET['id'], strsave($_GET['code']));
			break;			
			case 'change_pw':
				account_change_pw((int)$_GET['userid'], strsave($_GET['code']));
			break;						
			default:
				account();
		}
	// Aufruf bei keiner action Angabe //
	} else {
		account();	
	}
?>
