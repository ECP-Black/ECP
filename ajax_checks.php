<?php
//header("Content-type: text/html; charset=UTF-8");
error_reporting(0);
$ajax = 1;
//ini_set('session.use_trans_sid', 0);
session_start();
//ob_start();
require('inc/smarty/Smarty.class.php');
require('inc/db.daten.php');
require('inc/classes.php');
require('inc/constant.php');
$db->setMode(0);
if(count($_SESSION) == 0) {
	$_SESSION = unserialize($db->result(DB_PRE.'ecp_online', 'SIDDATA', 'SID = \''.session_id().'\' AND uID != 0'));
}
require('inc/functions.php');
require('inc/language/'.LANGUAGE.'.php');
require('inc/checks.php');
require('templates/'.DESIGN.'/design.php');
ajax_convert_array($_POST);
ajax_convert_array($_FILES);
switch(@$_GET['func']) {
	case 'check_username':
		if(isset($_GET['mode']))
		echo $db->result(DB_PRE.'ecp_user', 'COUNT(ID)', 'ID != '.(int)$_SESSION['userID'].' AND username = "'.strsave(htmlspecialchars($_GET['username'])).'"');
		else
		echo $db->result(DB_PRE.'ecp_user', 'COUNT(ID)', 'username = "'.strsave(htmlspecialchars($_GET['username'])).'"');
		break;
	case 'check_email':
		if(isset($_GET['mode']))
		echo $db->result(DB_PRE.'ecp_user', 'COUNT(ID)', 'ID != '.(int)$_SESSION['userID'].' AND email = "'.strsave($_GET['email']).'"');
		else
		echo $db->result(DB_PRE.'ecp_user', 'COUNT(ID)', 'email = "'.strsave($_GET['email']).'"');
		break;
	case 'check_login':
		echo $db->result(DB_PRE.'ecp_user', 'COUNT(ID)', 'username = \''.strsave(htmlspecialchars($_POST['username'])).'\' AND passwort = \''.sha1($_POST['passwort']).'\'');
		break;
	case 'read_all':
		if(isset($_SESSION['userID'])) {
			if($db->query('UPDATE '.DB_PRE.'ecp_messages SET readed = 1 WHERE touser = '.$_SESSION['userID'])) {
				echo 'ok';
			}
		}
		break;
	case 'make_user_tiny':
		if($_POST['username'] != '') {
			$user = explode(',', $_POST['username']);
			$string = '';
			foreach($user AS $value) {
				if(trim($value) != '') {
					$row = $db->fetch_assoc('SELECT ID, username, country FROM '.DB_PRE.'ecp_user WHERE username = \''.strsave(trim(htmlspecialchars($value))).'\'');
					if(isset($row['username'])) {
						$string .= ', <img src="images/flaggen/'.$row['country'].'.gif" alt="" title="'.$countries[$row['country']].'" /> <a href="?section=user&amp;id='.$row['ID'].'">'.$row['username'].'</a>';
					}
				}
			}
			echo html_ajax_convert(substr($string, 2));
		}
		break;
	case 'submit_lottoschein':
		if(isset($_SESSION['userID'])) {
			if(@$_SESSION['rights']['public']['lotto']['ticket'] OR @$_SESSION['rights']['superadmin']) {
				$lotto = $db->fetch_assoc('SELECT `lottoon`, `preis`, `free_scheine`, rundenID FROM '.DB_PRE.'ecp_lotto, '.DB_PRE.'ecp_lotto_runden ORDER BY ende DESC LIMIT 1');
				$user = $db->fetch_assoc('SELECT money, scheine FROM '.DB_PRE.'ecp_user_stats WHERE userID = '.$_SESSION['userID']);
				if($lotto['lottoon']) {
					if($user['money'] >= $lotto['preis'] OR $user['scheine'] < $lotto['free_scheine']) {
						if(count($_POST['lottozahlen']) == 4) {
							$zahlen = array();
							foreach($_POST['lottozahlen'] AS $val) {
								if((int)$val < 1 OR (int)$val > 24) {
									echo json_encode(array('error'=> html_ajax_convert(LOTTO_1_TO_24))); $db->close(); die();
								} elseif (in_array((int)$val, $zahlen)) {
									echo json_encode(array('error'=> html_ajax_convert(LOTTO_NO_DUPLIZE))); $db->close(); die();
								} else {
									$zahlen[] = (int)$val;
								}
							}
							sort($zahlen);
							if($db->query(sprintf('INSERT INTO '.DB_PRE.'ecp_lotto_scheine (`userID`, `rundenID`, `datum`, `zahl1`, `zahl2`, `zahl3`, `zahl4`) VALUES (%d, %d, %d, %d, %d, %d, %d)', $_SESSION['userID'], $lotto['rundenID'], time(), $zahlen[0],$zahlen[1],$zahlen[2],$zahlen[3]))) {
								$db->query('UPDATE '.DB_PRE.'ecp_user_stats SET scheine = scheine + 1 '.($user['scheine'] < $lotto['free_scheine'] ? '' : ', money = money - '.$lotto['preis']).' WHERE userID = '.$_SESSION['userID']);
								$db->query('UPDATE '.DB_PRE.'ecp_lotto SET jackpot = jackpot + preis');
								$lotto = $db->fetch_assoc('SELECT jackpot, preis, free_scheine, pro4er, pro3er, pro2er, COUNT(scheinID) as scheine, a.rundenID FROM '.DB_PRE.'ecp_lotto, '.DB_PRE.'ecp_lotto_runden as a LEFT JOIN '.DB_PRE.'ecp_lotto_scheine as b ON (a.rundenID = b.rundenID) GROUP BY a.rundenID ORDER BY ende DESC LIMIT 1');
								$user = $db->fetch_assoc('SELECT money, scheine FROM '.DB_PRE.'ecp_user_stats WHERE userID = '.$_SESSION['userID']);
								$scheine = array();
								$db->query('SELECT zahl1, zahl2, zahl3, zahl4 FROM '.DB_PRE.'ecp_lotto_scheine WHERE rundenID = '.$lotto['rundenID'].' AND userID = '.$_SESSION['userID']);
								if($db->num_rows()) {
									while($row = $db->fetch_assoc()) {
										$scheine[] = $row;
									}
									$tpls = new Smarty();
									$tpls->assign('scheine', $scheine);
									ob_start();
									$tpls->display(DESIGN.'/tpl/lotto/scheine.html');
									$content = ob_get_contents();
									ob_end_clean();
								}
								$scheine = @$content;
								echo html_ajax_convert(json_encode(array('jackpot' 			=> format_nr($lotto['jackpot'],2),
								'scheine' 			=> format_nr($lotto['scheine']),
								'lotto_scheine' 	=> $scheine,
								'money4er'			=> format_nr($lotto['jackpot']/100*$lotto['pro4er'], 2).' '.VIRTUELL_MONEY_UNIT,
								'money3er'			=> format_nr($lotto['jackpot']/100*$lotto['pro3er'], 2).' '.VIRTUELL_MONEY_UNIT,
								'money2er'			=> format_nr($lotto['jackpot']/100*$lotto['pro2er'], 2).' '.VIRTUELL_MONEY_UNIT,
								'money' 			=> format_nr($user['money'],2).' '.VIRTUELL_MONEY_UNIT,
								'schein_left'		=> format_nr(floor($user['money']/$lotto['preis'])+(($lotto['free_scheine']-$user['scheine'] > 0 ? $lotto['free_scheine']-$user['scheine'] : 0)))
								)));
							}
						} else {
							echo json_encode(array('error'=> html_ajax_convert(LOTTO_CHOOSE_4)));
						}
					} else {
						echo json_encode(array('error'=> html_ajax_convert(LOTTO_NOT_ENOUGH_MONEY)));
					}
				} else {
					echo json_encode(array('error'=> html_ajax_convert(LOTTO_DEAKT)));
				}
			} else {
				echo json_encode(array('error'=> html_ajax_convert(NO_ACCESS_RIGHTS)));
			}
		} else {
			echo json_encode(array('error'=> html_ajax_convert(NOT_LOGGED_IN)));
		}
		break;
	case 'new_msg':
		if(isset($_SESSION['userID'])) {
			$_GET['id'] = str_replace('.', '', $_GET['id']);
			if($_SESSION['userID'] == (int)$_GET['id']) {
				echo html_ajax_convert(MSG_NOT_TO_YOURSELF);
			} else {
				$user = $db->fetch_assoc('SELECT country, username FROM '.DB_PRE.'ecp_user WHERE ID = '.(int)$_GET['id']);
				if(isset($user['username'])) {
					$tpl = new smarty;
					$tpl->assign('id', (int)$_GET['id']);
					$tpl->assign('country', $user['country']);
					$tpl->assign('username', $user['username']);
					$tpl->assign('url', 'ajax_checks.php?func=sendmsg&id='.(int)$_GET['id']);
					ob_start();
					$tpl->display(DESIGN.'/tpl/account/message_touser.html');
					$content = ob_get_contents();
					ob_end_clean();
					echo html_ajax_convert($content);
				} else {
					echo html_ajax_convert(USER_NOT_FOUND);
				}
			}
		} else {
			echo html_ajax_convert(NOT_LOGGED_IN);
		}
		break;
	case 'sendmsg':
		if(isset($_SESSION['userID'])) {
			if($_POST['message_'.(int)$_GET['id']] == '') {
				echo html_ajax_convert(NOT_NEED_ALL_INPUTS);
			} else {
				$last = @$db->result(DB_PRE.'ecp_messages', 'datum', 'touser = '.(int)$_GET['id'].' AND fromuser = '.$_SESSION['userID']);
				if(($last+SPAM_MESSAGE) > time()) {
					echo html_ajax_convert(str_replace(array('{sek}', '{zeit}'), array(SPAM_MESSAGE, ($last+SPAM_MESSAGE-time())), SPAM_PROTECTION_MSG));
				} else {
					if((int)$_GET['id'] == $_SESSION['userID']) {
						echo html_ajax_convert(MSG_NOT_TO_YOURSELF);
					} elseif((int)$_GET['id'] AND message_send((int)$_GET['id'], $_SESSION['userID'], $_POST['title'], $_POST['message_'.(int)$_GET['id']])) {
						echo 'ok';
					} else {
						echo html_ajax_convert(ERROR, USER_NOT_FOUND);
					}
				}
			}
		} else {
			echo html_ajax_convert(NOT_LOGGED_IN);
		}
		break;
	case 'del_buddy':
		if(isset($_SESSION['userID'])) {
			if($db->query('DELETE FROM '.DB_PRE.'ecp_buddy WHERE userID= '.$_SESSION['userID'].' AND buddyID = '.(int)$_GET['id'])) {
				echo 'ok';
			}
		} else {
			echo html_ajax_convert(NOT_LOGGED_IN);
		}
		break;
	case 'get_user_list':
		if(@$_SESSION['rights']['public']['user']['list'] OR @$_SESSION['rights']['superadmin']) {
			$tpl = new smarty();
			$anzahl = $db->result(DB_PRE.'ecp_user', 'COUNT(ID)', '1');
			$limits = get_sql_limit($anzahl, LIMIT_MEMBERS);
			$erlaubt = array('username', 'registerdate', 'lastlogin', 'geburtstag', 'online', 'sex', 'rangname');
			if(isset($_GET['orderby'])) {
				if(!in_array($_GET['orderby'], $erlaubt)) $_GET['orderby'] = 'username';
				($_GET['order'] == 'DESC') ? '' : $_GET['order'] = 'ASC';
				if($_GET['orderby'] == 'geburtstag') ($_GET['order'] == 'DESC') ? $_GET['order']  = 'ASC' : $_GET['order'] = 'DESC';
				if($_GET['orderby'] == 'rangname')  {
				($_GET['order'] == 'ASC') ? $_GET['orderby'] = 'fest ASC, abposts ASC' :  $_GET['orderby'] = 'fest DESC, abposts DESC';
				$_GET['order'] = '';
				}
			}
			$db->query('SELECT geburtstag, xfire,  sex, icq, registerdate, clanname, homepage, lastlogin, wohnort, user_pic, `ID`, username, country, uID as online, rankname, iconname FROM '.DB_PRE.'ecp_user LEFT JOIN '.DB_PRE.'ecp_ranks ON (rID = rankID) LEFT JOIN '.DB_PRE.'ecp_online ON (uID = ID AND lastklick > '.(time()- SHOW_USER_ONLINE).') GROUP BY ID ORDER BY '.strsave($_GET['orderby']).' '.strsave($_GET['order']).' LIMIT '.$limits[1].','.LIMIT_MEMBERS);
			$user = array();
			while($row = $db->fetch_assoc()) {
			($row['lastlogin']) ? $row['lastlogin'] = date(LONG_DATE, $row['lastlogin']) : $row['lastlogin'] = NEVER_LOGGED_IN;
			$row['registerdate2'] = date('d.m.Y', $row['registerdate']);
			$row['registerdate'] = date(LONG_DATE, $row['registerdate']);
			if($row['geburtstag'] == '0000-00-00') $row['geburtstag'] = '';
			if($row['geburtstag']) {
				$birthday = explode('-', $row['geburtstag']);
				$row['geburtstag'] = $birthday[2].'.'.$birthday[1].'.'.$birthday[0];
				$alter = alter($birthday[2], $birthday[1], $birthday[0]);
				IF(date('m') == $birthday[1] AND date('d') < $birthday[2]) $alter -=1;
				$next = @mktime(0,0,0,$birthday[1],$birthday[2],$birthday[0] + $alter + 1) - time();
				$row['alter'] =  $alter;
			}
			$row['countryname'] = $countries[$row['country']];
			$row['icqtrim'] = str_replace('-', '',$row['icq']);
			$user[] = $row;
			}
			$tpl->assign('anzahl', $anzahl);
			if($limits[0] > 1)
			$tpl->assign('seiten', makepagelink_ajax('?section=user&action=list&orderby='.$_GET['orderby'].'&order='.$_GET['order'],'return load_user(\'orderby='.$_GET['orderby'].'&order='.$_GET['order'].'&page={nr}\');',@$_GET['page'], $limits[0]));
			$tpl->assign('user', $user);
			$tpl->assign('ajax', 1);
			ob_start();
			$tpl->display(DESIGN.'/tpl/user/user_list.html');
			$content = ob_get_contents();
			ob_end_clean();
			echo html_ajax_convert($content);
		} else {
			echo html_ajax_convert(NO_ACCESS_RIGHTS);
		}
		break;
	case 'account_del_msg':
		if(isset($_SESSION['userID'])) {
			$msg = $db->fetch_assoc('SELECT fromuser, touser FROM '.DB_PRE.'ecp_messages WHERE msgID = '.(int)$_GET['id']);
			if(isset($msg['fromuser'])) {
				if($msg['fromuser'] == $_SESSION['userID']) {
					if($db->query('UPDATE '.DB_PRE.'ecp_messages SET fromdel =1 WHERE msgID = '.(int)$_GET['id'])) {
						echo 'ok';
					}
				} elseif ($msg['touser'] == $_SESSION['userID']) {
					if($db->query('UPDATE '.DB_PRE.'ecp_messages SET del =1 WHERE msgID = '.(int)$_GET['id'])) {
						echo 'ok';
					}
				} else {
					echo html_ajax_convert(NO_ACCESS_RIGHTS);
				}
			} else {
				echo html_ajax_convert(NO_ENTRIES_ID);
			}
		}
		break;
	case 'check_captcha':
		if(strtolower($_SESSION['captcha']) != strtolower($_GET['code'])) {
			echo 0;
			unset($_SESSION['captcha']);
		} else {
			echo 1;
		}
		break;
	case 'get_ranks': 
		$db->query('SELECT rankname,iconname,abposts,fest,money FROM '.DB_PRE.'ecp_ranks ORDER BY fest, abposts, money ASC');
		$ranks = array();
		while($row = $db->fetch_assoc()) {
			$row['abposts'] = format_nr($row['abposts']);
			$row['money'] = format_nr($row['money'],2);
			$ranks[] = $row;
		}
		$tpl = new smarty();
		$tpl->assign('ranks', $ranks);
		ob_start();
		$tpl->display(DESIGN.'/tpl/ranks.html');
		$content = ob_get_contents();
		ob_end_clean();
		echo html_ajax_convert($content);
		break;
	case 'check_captcha_mini':
		if(strtolower($_SESSION['captcha_mini']) != strtolower($_GET['code'])) {
			echo 0;
			unset($_SESSION['captcha_mini']);
		} else {
			echo 1;
		}
		break;
	case 'get_shouts_mini':
		if(@$_SESSION['rights']['public']['shoutbox']['view'] OR @$_SESSION['rights']['superadmin']) {
			$anzahl = $db->result(DB_PRE.'ecp_comments', 'COUNT(comID)', 'bereich="shoutbox"');
			$tpl =new smarty();
			if($anzahl) {
				$db->query('SELECT comID, country, username, userID, author, datum, beitrag FROM '.DB_PRE.'ecp_comments LEFT JOIN '.DB_PRE.'ecp_user ON userID = ID WHERE bereich="shoutbox" ORDER BY datum DESC LIMIT '.LIMIT_SHOUTBOX_MINI);
				while($row = $db->fetch_assoc()) {
					$row['nr'] = $anzahl--;
					$row['countryname'] = @$countries[$row['country']];
					$row['datum'] = date(SHORT_DATE, $row['datum']);
					$row['beitrag'] = wordwrap($row['beitrag'], 25, '<br />', 1);
					$shouts[] = $row;
				}
				$tpl->assign('shoutbox', $shouts);
			}
			ob_start();
			$tpl->display(DESIGN.'/tpl/shoutbox/mini_ajax.html');
			$content = ob_get_contents();
			ob_end_clean();
			echo html_ajax_convert($content);
		} else {
			echo html_ajax_convert(NO_ACCESS_RIGHTS);
		}
		break;
	case 'add_buddy':
		if(isset($_SESSION['userID']) AND (@$_SESSION['rights']['public']['user']['view'] OR @$_SESSION['rights']['superadmin']) AND (int)$_GET['id']) {
			if($_SESSION['userID'] == (int)$_GET['id']) {
				echo 3;
			} else {
				if($db->result(DB_PRE.'ecp_buddy', 'COUNT(userID)', 'userID = '.$_SESSION['userID'].' AND buddyID = '.(int)$_GET['id'])) {
					echo 2;
				} else {
					if($db->query('INSERT INTO '.DB_PRE.'ecp_buddy (userID, buddyID) VALUES ('.$_SESSION['userID'].', '.(int)$_GET['id'].')')) {
						echo 1;
					}
				}
			}
		} else {
			echo html_ajax_convert(NO_ACCESS_RIGHTS);
		}
		break;
	case 'upload_forum_files':
		if($db->result(DB_PRE.'ecp_forum_boards', 'COUNT(boardID)', 'closed = 0 AND (attachfiles = "" OR '.str_replace('access', 'attachfiles', $_SESSION['access_search']).') AND boardID = '.(int)$_GET['boardID']) == 1) {		
			if(isset($_SESSION['forum']['attach'][(int)$_GET['boardID']]) AND ($_SESSION['forum']['attach'][(int)$_GET['boardID']] == $_GET['rand']) AND $db->result(DB_PRE.'ecp_forum_attachments', 'COUNT(attachID)', 'bID = '.(int)$_GET['boardID'].' AND validation = \''.strsave($_GET['rand']).'\'') <= $db->result(DB_PRE.'ecp_forum_boards', 'attachments', 'boardID = '.(int)$_GET['boardID'])) {
				$mine = getMimeType($_FILES['Filedata']['tmp_name'], $_FILES['Filedata']['name']);
				if($_FILES['Filedata']['size'] > $db->result(DB_PRE.'ecp_forum_boards', 'attachmaxsize', 'boardID = '.(int)$_GET['boardID'])) {
					$error = FORUM_FILE_SIZE_TO_BIG;
				} elseif ($mine != 'application/zip' AND $mine != 'application/x-rar-compressed' AND $mine != 'image/bmp' AND $mine != 'image/gif' AND $mine != 'image/jpeg' AND $mine != 'image/png' AND $mine != 'application/pdf' AND $mine != 'text/plain' AND $mine != 'text/css' AND $mine != 'text/html') {
					$error = WRONG_FILE_TYPE.' '.$mine;
				} else {
					$sha1 = sha1_file($_FILES['Filedata']['tmp_name']);
					if($db->query(sprintf('INSERT INTO '.DB_PRE.'ecp_forum_attachments (`bID`, `userID`, `name`, `size`, `strname`, `validation`, uploadzeit, IP) VALUES (%d, %d, \'%s\', %d, \'%s\', \'%s\', %d, \'%s\')', (int)$_GET['boardID'], @(int)$_SESSION['userID'], strsave($_FILES['Filedata']['name']), (int)$_FILES['Filedata']['size'], $sha1, strsave($_GET['rand']), time(), $_SERVER['REMOTE_ADDR']))) {
						move_uploaded_file($_FILES['Filedata']['tmp_name'], 'uploads/forum/'.$db->last_id().'_'.$sha1);
						umask(0);
						chmod('uploads/forum/'.$db->last_id().'_'.$sha1, CHMOD);
					} else {
						$error = 'Datei konnte nicht verschoben werden.';
					}
				}
			} else {
				$error = ERROR_FORUM_UPLOAD;
			}
		} else {
			$error = ACCESS_DENIED;
		}
		if(isset($error)) {
			echo html_ajax_convert(json_encode(array('result'=>'failed', 'error'=>$error)));
		} else {
			echo html_ajax_convert(json_encode(array('result'=>'success', 'size'=>str_replace('{datei}', $_FILES['Filedata']['name'], UPLOAD_SUCCESS))));
		}			
		break;
	case "getcomments":
		$conditions = array('LIMIT' 	=> LIMIT_COMMENTS,
		'ORDER'		=> ($_GET['bereich'] == 'guestbook' ? 'DESC' : COMMENTS_ORDER),
		'section'   => $_GET['bereich']);
		$conditions['action'] = 'add';
		$conditions['link'] = '';
		comments_get($_GET['bereich'], (int)$_GET['id'], $conditions, 1);
		break;
	case "get_user_messages":
		if(isset($_SESSION['userID'])) {
			if($_GET['mode'] == 'in') {
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
				ob_start();
				$tpl->display(DESIGN.'/tpl/account/messages_ajax_in.html');
				$content = ob_get_contents();
				ob_end_clean();
				echo html_ajax_convert($content);
			} else {
				$tpl = new Smarty();
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
				$tpl->display(DESIGN.'/tpl/account/messages_ajax_out.html');
				$content = ob_get_contents();
				ob_end_clean();
				echo html_ajax_convert($content);
			}
		} else {
			echo html_ajax_convert(NO_ACCESS_RIGHTS);
		}
		break;
	case "comments_del":
		$com = $db->fetch_assoc('SELECT `subID`, `bereich`, `userID` FROM '.DB_PRE.'ecp_comments WHERE comID = '.(int)$_GET['id']);
		if(isset($com['subID'])) {
			switch($com['bereich']) {
				case 'awards':
					$rechte = (@$_SESSION['userID'] AND (@$_SESSION['rights']['superadmin'] OR @$_SESSION['rights']['admin']['awards']['com_del'] OR ($_SESSION['userID'] == $com['userID'] AND @$_SESSION['rights']['public']['awards']['com_del'])) ? true : false);
					break;
				case 'clanwars':
					$rechte = (@$_SESSION['userID'] AND (@$_SESSION['rights']['superadmin'] OR @$_SESSION['rights']['admin']['clanwars']['com_del'] OR ($_SESSION['userID'] == $com['userID'] AND @$_SESSION['rights']['public']['clanwars']['com_del'])) ? true : false);
					break;
				case 'downloads':
					$rechte = (@$_SESSION['userID'] AND (@$_SESSION['rights']['superadmin'] OR @$_SESSION['rights']['admin']['downloads']['com_del'] OR ($_SESSION['userID'] == $com['userID'] AND @$_SESSION['rights']['public']['downloads']['com_del'])) ? true : false);
					break;
				case 'gallery':
					$rechte = (@$_SESSION['userID'] AND (@$_SESSION['rights']['superadmin'] OR @$_SESSION['rights']['admin']['gallery']['com_del'] OR ($_SESSION['userID'] == $com['userID'] AND @$_SESSION['rights']['public']['gallery']['com_del'])) ? true : false);
					break;
				case 'gb_com':
					$rechte = (@$_SESSION['userID'] AND (@$_SESSION['rights']['superadmin'] OR @$_SESSION['rights']['admin']['guestbook']['com_del'] OR ($_SESSION['userID'] == $com['userID'] AND @$_SESSION['rights']['public']['guestbook']['com_del'])) ? true : false);
					break;
				case 'news':
					$rechte = (@$_SESSION['userID'] AND (@$_SESSION['rights']['superadmin'] OR @$_SESSION['rights']['admin']['news']['com_del'] OR ($_SESSION['userID'] == $com['userID'] AND @$_SESSION['rights']['public']['news']['com_del'])) ? true : false);
					break;
				case 'survey':
					$rechte = (@$_SESSION['userID'] AND (@$_SESSION['rights']['superadmin'] OR @$_SESSION['rights']['admin']['survey']['com_del'] OR ($_SESSION['userID'] == $com['userID'] AND @$_SESSION['rights']['public']['survey']['com_del'])) ? true : false);
					break;
				case 'user':
					$rechte = (@$_SESSION['userID'] AND (@$_SESSION['rights']['superadmin'] OR @$_SESSION['rights']['admin']['user']['com_del'] OR ($_SESSION['userID'] == $com['userID'] AND @$_SESSION['rights']['public']['user']['com_del'])) ? true : false);
					break;
				case 'guestbook':
					$rechte = (@$_SESSION['userID'] AND (@$_SESSION['rights']['superadmin'] OR @$_SESSION['rights']['admin']['guestbook']['del']) ? true : false);
					break;					
				default:
					$rechte = false;
			}
			if($rechte) {
				if($db->query('DELETE FROM '.DB_PRE.'ecp_comments WHERE comID = '.(int)$_GET['id'])) {
					echo 'ok';
				}
			} else {
				echo html_ajax_convert(NO_ACCESS_RIGHTS);
			}
		} else {
			echo html_ajax_convert(NO_ENTRIES_ID);
		}
		break;
	case 'comments_forum_del':
		$thread = $db->fetch_assoc('SELECT attachs, comID, `threadID`, `bID`, `threadname`, `vonID`, z.posts, `sticky`, z.closed,
												`closedmsg`, `fsurveyID`, `rating`, `ratingvotes`, a.boardparentID, a.name, a.isforum, a.closed as forumclosed,
												 a.rightsread, a.postcom, a.editcom, a.votesurvey, a.downloadattch, a.threadclose, 
												a.threaddel, a.threadmove, a.threadpin, a.editmocom, a.delcom, b.rightsread as parentRead, b.name as boardparentName 
									FROM '.DB_PRE.'ecp_forum_comments LEFT JOIN '.DB_PRE.'ecp_forum_threads as z ON (z.threadID = tID) LEFT JOIN '.DB_PRE.'ecp_forum_boards AS a ON (bID = a.boardID) LEFT JOIN '.DB_PRE.'ecp_forum_boards AS b ON (b.boardID = a.boardparentID) WHERE comID = '.(int)$_GET['id']);
		if(find_access($thread['rightsread']) AND find_access($thread['parentRead']) AND $thread['isforum'] AND find_access($thread['delcom'])) {
			if($thread['attachs']) {
				$result = $db->query('SELECT attachID, strname FROM '.DB_PRE.'ecp_forum_attachments WHERE bID = '.$thread['bID'].' AND tID = '.$thread['threadID']);
				while($row = mysql_fetch_assoc($result)) {
					@unlink('uploads/forum/'.$row['attachID'].'_'.$row['strname']);
					$db->query('DELETE FROM '.DB_PRE.'ecp_forum_attachments WHERE attachID = '.$row['attachID']);
				}
				if($db->result(DB_PRE.'ecp_forum_attachments', 'COUNT(attachID)', 'tID ='.$thread['threadID']) == 0) {
					$db->query('UPDATE '.DB_PRE.'ecp_forum_threads SET anhaenge = 0 WHERE threadID= '.$thread['threadID']);
				}			
			}
			if($thread['posts'] <= 0) {
				if($thread['fsurveyID']) {
					$db->query('DELETE FROM '.DB_PRE.'ecp_forum_survey WHERE fsurveyID = '.$thread['fsurveyID']);
					$db->query('DELETE FROM '.DB_PRE.'ecp_forum_survey_answers WHERE fsID = '.$thread['fsurveyID']);
					$db->query('DELETE FROM '.DB_PRE.'ecp_forum_survey_votes WHERE fsurID = '.$thread['fsurveyID']);
				}
				$db->query('DELETE FROM '.DB_PRE.'ecp_forum_abo WHERE thID = '.$thread['threadID']);
				$db->query('DELETE FROM '.DB_PRE.'ecp_forum_ratings WHERE tID = '.$thread['threadID']);
				if($db->query('DELETE FROM '.DB_PRE.'ecp_forum_comments WHERE tID = '.$thread['threadID'])) {
					if($db->query('DELETE FROM '.DB_PRE.'ecp_forum_threads WHERE threadID = '.$thread['threadID'])) {
						$last = $db->fetch_assoc('SELECT userID,postname,adatum, tID FROM '.DB_PRE.'ecp_forum_comments WHERE boardID = '.$thread['bID'].' ORDER BY adatum DESC LIMIT 1');
						$db->query('UPDATE '.DB_PRE.'ecp_forum_boards SET threads = threads - 1, `posts` = posts - 1, `lastpostuserID` =  '.(int)$last['userID'].', `lastpostuser` = \''.$last['postname'].'\', `lastpost` = '.(int)$last['adatum'].', lastthreadID = '.(int)$last['tID'].' WHERE (boardID = '.$thread['bID'].' OR boardID = '.$thread['boardparentID'].')');
						echo 'ok';
					}
			}
			} else {
				if($db->query('DELETE FROM '.DB_PRE.'ecp_forum_comments WHERE comID = '.$thread['comID'])) {
					$db->query('UPDATE '.DB_PRE.'ecp_forum_threads SET posts = posts -1 WHERE threadID = '.$thread['threadID']);
					$db->query('UPDATE '.DB_PRE.'ecp_forum_boards SET posts = posts -1 WHERE boardID = '.$thread['bID'].' OR boardID = '.$thread['boardparentID']);
					$last = $db->fetch_assoc('SELECT userID,postname,adatum, tID FROM '.DB_PRE.'ecp_forum_comments WHERE boardID = '.$thread['bID'].' ORDER BY adatum DESC LIMIT 1');
					$db->query('UPDATE '.DB_PRE.'ecp_forum_boards SET `lastpostuserID` =  '.(int)$last['userID'].', `lastpostuser` = \''.$last['postname'].'\', `lastpost` = '.(int)$last['adatum'].', lastthreadID = '.(int)$last['tID'].' WHERE (boardID = '.$thread['bID'].' OR boardID = '.$thread['boardparentID'].')');					
					echo 'ok';
				}				
			}
		} else {
			echo html_ajax_convert(ACCESS_DENIED);
		}
		break;		
	case 'get_forum_comments':
		$thread = $db->fetch_assoc('SELECT `threadID`, `bID`, `threadname`, `vonID`, '.DB_PRE.'ecp_forum_threads.posts, `sticky`, '.DB_PRE.'ecp_forum_threads.closed,
												`closedmsg`, `fsurveyID`, `rating`, `ratingvotes`, a.boardparentID, a.name, a.isforum, a.closed as forumclosed,
												 a.rightsread, a.postcom, a.editcom, a.votesurvey, a.downloadattch, a.threadclose, 
												a.threaddel, a.threadmove, a.threadpin, a.editmocom, a.delcom, b.rightsread as parentRead, b.name as boardparentName FROM '.DB_PRE.'ecp_forum_threads LEFT JOIN '.DB_PRE.'ecp_forum_boards AS a ON (bID = a.boardID) LEFT JOIN '.DB_PRE.'ecp_forum_boards AS b ON (b.boardID = a.boardparentID) WHERE threadID = '.(int)$_GET['threadID'].' AND bID = '.(int)$_GET['boardID']);
		if(find_access($thread['rightsread']) AND find_access($thread['parentRead']) AND $thread['isforum']) {
			$comments = array();
			$id = (int)$_GET['threadID'];
			$bid = (int)$_GET['boardID'];
			$db->query('UPDATE '.DB_PRE.'ecp_forum_threads SET views = views + 1 WHERE threadID = '.$id);
			$limits = get_sql_limit($thread['posts']+1, LIMIT_FORUM_COMMENTS);
			$result = $db->query('SELECT `comID`, '.DB_PRE.'ecp_forum_comments.userID, a.rID, rankname, iconname, `postname`, `adatum`, `comment`, `edits`, `editdatum`, `edituserID`, '.DB_PRE.'ecp_forum_comments.IP, `attachs`, a.username, a.sex, a.signatur, a.country, comments, d.money, a.avatar, b.username as editfrom, lastklick as online
								FROM `'.DB_PRE.'ecp_forum_comments` 
								LEFT JOIN '.DB_PRE.'ecp_user as a ON ('.DB_PRE.'ecp_forum_comments.userID = a.ID)
								LEFT JOIN '.DB_PRE.'ecp_user as b ON ('.DB_PRE.'ecp_forum_comments.edituserID = b.ID)
								LEFT JOIN '.DB_PRE.'ecp_user_stats as d ON ('.DB_PRE.'ecp_forum_comments.userID = d.userID)
								LEFT JOIN '.DB_PRE.'ecp_ranks ON (a.rID = rankID)
								LEFT JOIN '.DB_PRE.'ecp_online ON (uID = '.DB_PRE.'ecp_forum_comments.userID AND lastklick > '.(time()-SHOW_USER_ONLINE).')
								WHERE boardID = '.$bid.' AND tID = '.$id.'
								GROUP BY comID
								ORDER BY adatum '.(@$_GET['order'] == 'DESC' ?  'DESC' : 'ASC').'
								LIMIT '.$limits[1].', '.LIMIT_FORUM_COMMENTS);
			while($row = mysql_fetch_assoc($result)) {
				$row['adatum'] = forum_make_date($row['adatum']);
				$row['nr'] = ++$limits[1];
				$row['comment'] = bb_code($row['comment']);	
				$row['comments'] = format_nr($row['comments']);
				$row['countryname'] = @$countries[$row['country']];
				$row['quote'] = $row['comment'];
				($row['sex'] == 'male')? $row['sextext'] = MALE : $row['sextext'] = FEMALE;
				if($row['edits']) {
					$row['edit'] = str_replace(array('{anzahl}', '{von}', '{last}'), array($row['edits'], '<a href="?section=user&id='.$row['edituserID'].'">'.$row['editfrom'].'</a>', date(LONG_DATE, $row['editdatum'])), COMMENT_EDIT_TXT);
				}
				if($row['attachs']) {
					$anhaenge = array();
					$db->query('SELECT `attachID`, `name`, `size`, `downloads` FROM `'.DB_PRE.'ecp_forum_attachments` WHERE `bID` = '.$bid.' AND `tID` = '.$id.' AND `mID` = '.$row['comID']);
					while($sub = $db->fetch_assoc()) {
						$sub['size'] = goodsize($sub['size']);
						$anhaenge[] = $sub;
					}
					$row['attchs'] = $anhaenge;
				}
				$comments[] = $row;
			}

			$tage = ((time()-$installed)/86400);
			$db->query('SELECT uID, username FROM '.DB_PRE.'ecp_online LEFT JOIN '.DB_PRE.'ecp_user ON (ID=uID) WHERE forum = 1 AND fboardID = '.$bid.' AND fthreadID = '.$id.' AND lastklick > '.(time()-SHOW_USER_ONLINE).' ORDER BY username ASC');
			$members = 0;
			$guests = 0;
			$member = '';
			while($row = $db->fetch_assoc()) {
				if($row['uID']) {
					$members++;
					$member .= ', <a href="?section=user&id=' .$row['uID'].'">'.$row['username'].'</a>';
				} else {
					$guests++;
				}
			}
			$tpl = new smarty;
			if($limits[0]) {
				$seiten = makepagelink_ajax('?section=forum&action=thread&boardID='.$bid.'&threadID='.$id, 'return load_forum_com_page('.$id.', '.$bid.', {nr}, \''.(@$_GET['order'] == 'DESC' ?  'DESC' : 'ASC').'\');', $_GET['page'], $limits[0]);
				$tpl->assign('seiten',$seiten);
			}
			$tpl->assign('ajax', 1);
			$tpl->assign('order', @$_GET['order']);
			$tpl->assign('vonID', $thread['vonID']);
			$tpl->assign('postcom', find_access($thread['postcom']));
			$tpl->assign('editcom', find_access($thread['editcom']));
			$tpl->assign('threadclose', find_access($thread['threadclose']));
			$tpl->assign('threaddel', find_access($thread['threaddel']));
			$tpl->assign('threadmove', find_access($thread['threadmove']));
			$tpl->assign('threadpin', find_access($thread['threadpin']));
			$tpl->assign('editmocom', find_access($thread['editmocom']));
			$tpl->assign('delcom', find_access($thread['delcom']));
			$tpl->assign('closed', $thread['closed']);
			$tpl->assign('bclosed', $thread['forumclosed']);
			$tpl->assign('comments', @$comments);
			$tpl->assign('path', '<a href="?section=forum">'.FORUM.'</a> <img src="templates/'.DESIGN.'/images/pfeil_o.gif" alt="" /> '.($thread['boardparentID'] ? '<a href="?section=forum&action=subboard&boardID='.$thread['boardparentID'].'">'.$thread['boardparentName'].'</a> <img src="templates/'.DESIGN.'/images/pfeil_o.gif" alt="" /> ' : '').'<a href="?section=forum&amp;action=board&amp;boardID='.$bid.'">'.$thread['name'].'</a> <img src="templates/'.DESIGN.'/images/pfeil_o.gif" alt="" /> '.($thread['closed'] ? '<img src="templates/'.DESIGN.'/images/forum_icon_thread_closed.png" alt="'.FORUM_THREAD_CLOSED.'" title="'.FORUM_THREAD_CLOSED.'" /> ' : '').$thread['threadname']);
			$tpl->assign('members', substr($member, 2));
			$tpl->assign('thread',1);
			$tpl->assign('quote', true);
			$tpl->assign('online', str_replace(array('{members}', '{guests}'), array(format_nr($members), format_nr($guests)), FORUM_ONLINE_THREAD));
			ob_start();
			if ($thread['fsurveyID']) {
				$tpl->assign('umfrage',1 );
				if(isset($_SESSION['userID'])) {
					$umfrage = $db->fetch_assoc('SELECT `ende`, `frage`, `antworten`, COUNT(voteID) AS anzahl FROM `'.DB_PRE.'ecp_forum_survey` LEFT JOIN '.DB_PRE.'ecp_forum_survey_votes ON (fsurID = '.$thread['fsurveyID'].' AND userID = '.(int)@$_SESSION['userID'].') WHERE fsurveyID = '.$thread['fsurveyID'].' AND boardID = '.$bid.' AND threadID = '.$id.' GROUP BY fsurveyID');
				} else {
					$umfrage = $db->fetch_assoc('SELECT `ende`, `frage`, `antworten`, COUNT(voteID) AS anzahl FROM `'.DB_PRE.'ecp_forum_survey` LEFT JOIN '.DB_PRE.'ecp_forum_survey_votes ON (fsurID = '.$thread['fsurveyID'].' AND IP = \''.$_SERVER['REMOTE_ADDR'].'\') WHERE fsurveyID = '.$thread['fsurveyID'].' AND boardID = '.$bid.' AND threadID = '.$id.' GROUP BY fsurveyID');
				}
				$tpl->assign('antworten', $umfrage['antworten']);
				$tpl->assign('frage', $umfrage['frage']);
				if($umfrage['ende']) $tpl->assign('ende', date(LONG_DATE, $umfrage['ende']));
				$db->query('SELECT `answerID`, `answer`, `votes` FROM '.DB_PRE.'ecp_forum_survey_answers WHERE fsID = '.$thread['fsurveyID'].' ORDER BY answerID ASC');
				$gesamt = 0;
				$antworten = array();
				while($row = $db->fetch_assoc()) {
					$gesamt += $row['votes'];
					$antworten[] = $row;
				}
				foreach($antworten AS $key => $value) {
					if($gesamt) {
						$antworten[$key]['prozent'] = round($value['votes']/$gesamt*100,1);
					} else {
						$antworten[$key]['prozent'] = 0;
					}
					$antworten[$key]['votes'] = number_format($value['votes'], 0,'','.');
				}
				$tpl->assign('answers', $antworten);
				$tpl->assign('fsurveyID', $thread['fsurveyID']);
				$tpl->assign('id', $id);
				$tpl->assign('bid', $bid);
				$tpl->assign('gesamt', number_format($gesamt, 0,'','.'));
				if($umfrage['anzahl'] OR isset($_COOKIE['forum']['survey_'.$thread['fsurveyID']]) OR !find_access($thread['votesurvey']) OR $thread['closed']) {
					$tpl->assign('abstimmen', false);
				} else {
					$tpl->assign('abstimmen', true);
				}
			}
			$tpl->display(DESIGN.'/tpl/forum/thread_comments.html');
			$content = ob_get_contents();
			ob_end_clean();
			echo html_ajax_convert($content);
		} else {
			echo html_ajax_convert(ACCESS_DENIED);
		}
		break;
	case 'get_galleries':
		if(@$_SESSION['rights']['public']['gallery']['view'] OR @$_SESSION['rights']['superadmin']) {
			$kate = $db->fetch_assoc('SELECT katename, galleries FROM '.DB_PRE.'ecp_gallery_kate WHERE (access = "" OR '.$_SESSION['access_search'].') AND kateID = '.(int)$_GET['id']);
			if(isset($kate['katename'])) {
				$limits = get_sql_limit($kate['galleries'], LIMIT_GALLERY);
				$gallery = array();
				$result = $db->query('SELECT `galleryID`, `name`, a.userID, `folder`, `images`, `datum`, username FROM '.DB_PRE.'ecp_gallery as a LEFT JOIN '.DB_PRE.'ecp_user ON ID=a.userID WHERE cID = '.(int)$_GET['id'].' AND (access = "" OR '.$_SESSION['access_search'].') ORDER BY datum DESC LIMIT '.$limits[1].','.LIMIT_GALLERY);
				while($row = mysql_fetch_assoc($result)) {
					$row = array_merge($row, $db->fetch_assoc('SELECT imageID, filename FROM '.DB_PRE.'ecp_gallery_images WHERE gID = '.$row['galleryID'].' ORDER BY rand() LIMIT 1'));
					$row['datum'] = date('d.m.Y', $row['datum']);
					$gallery[] = $row;
				}
				$tpl = new smarty;
				if($limits[0] > 1)
					$tpl->assign('seiten', makepagelink_ajax('?section=gallery&action=kate&id='.$_GET['id'], 'return load_kate_page('.$_GET['id'].', {nr});', @$_GET['page'], $limits[0]));
				$tpl->assign('gallery', $gallery);
				ob_start();
				$tpl->display(DESIGN.'/tpl/gallery/gallery.html');
				$content = ob_get_contents();
				ob_end_clean();
				echo html_ajax_convert($content);
			} else {
				echo html_ajax_convert(NO_ENTRIES_ID);
			}
		} else {
			echo html_ajax_convert(NO_ACCESS_RIGHTS);
		}
		break;
	case 'get_links':
		if(@$_SESSION['rights']['public']['links']['view'] OR @$_SESSION['rights']['superadmin']) {
			$tpl = new smarty;
			$anzahl = $db->result(DB_PRE.'ecp_links', 'COUNT(linkID)', '1');
			if($anzahl) {
				$limits = get_sql_limit($anzahl, LIMIT_LINKS);
				$links = array();
				$db->query('SELECT * FROM '.DB_PRE.'ecp_links ORDER BY name ASC LIMIT '.$limits[1].','.LIMIT_LINKS);
				while($row = $db->fetch_assoc()) {
					$row['hits'] = format_nr($row['hits'], 0);
					$links[] = $row;
				}
				$tpl->assign('links', $links);
				if($limits[0] > 1)
					$tpl->assign('seiten', makepagelink_ajax('?section=links', 'return load_links({nr});', @$_GET['page'], $limits[0]));
				ob_start();
				$tpl->display(DESIGN.'/tpl/links/links.html');
				$content = ob_get_contents();
				ob_end_clean();
				echo html_ajax_convert($content);
			} else {
				echo NO_ENTRIES;
			}
		} else {
			echo htmlentities(NO_ACCESS_RIGHTS);
		}
		break;
	case 'get_pic':
		if(@$_SESSION['rights']['public']['gallery']['view'] OR @$_SESSION['rights']['superadmin']) {
			$image = $db->fetch_assoc('SELECT gID, katename, b.access as kateacces, a.access as access, name, images, cID, folder, filename, uploaded, c.beschreibung, klicks, c.userID, username FROM '.DB_PRE.'ecp_gallery_images AS c LEFT JOIN '.DB_PRE.'ecp_gallery as a ON (gID = galleryID) LEFT JOIN '.DB_PRE.'ecp_user ON ID=c.userID LEFT JOIN '.DB_PRE.'ecp_gallery_kate as b ON (cID = kateID) WHERE imageID = '.(int)$_GET['id']);
			if(isset($image['uploaded']) AND find_access($image['access']) AND find_access($image['kateacces'])) {
				if(!isset($_SESSION['gallery'][(int)$_GET['id']])) {
					$db->query('UPDATE '.DB_PRE.'ecp_gallery_images SET klicks=klicks+1 WHERE imageID = '.(int)$_GET['id']);
					$_SESSION['gallery'][(int)$_GET['id']] = true;
				}
				$tpl = new smarty;
				$image['uploaded'] = date(LONG_DATE, $image['uploaded']);
				$tpl->assign('pfad', '<a href="?section=gallery">'.GALLERY.'</a> <img src="templates/'.DESIGN.'/images/pfeil_o.gif" alt="" /> <a href="?section=gallery&action=kate&id='.$image['cID'].'">'.$image['katename'].'</a> <img src="templates/'.DESIGN.'/images/pfeil_o.gif" alt="" /> <a href="?section=gallery&action=gallery&id='.$image['gID'].'">'.$image['name'].'</a>');
				$tpl->assign('vorID', @$db->result(DB_PRE.'ecp_gallery_images', 'imageID', 'gID = '.$image['gID'].' AND imageID < '.(int)$_GET['id'].' ORDER BY imageID DESC LIMIT 1'));
				$tpl->assign('nachID', @$db->result(DB_PRE.'ecp_gallery_images', 'imageID', 'gID = '.$image['gID'].' AND imageID > '.(int)$_GET['id'].' ORDER BY imageID ASC LIMIT 1'));
				foreach($image AS $key=>$value) $tpl->assign($key, $value);
				ob_start();
				$tpl->display(DESIGN.'/tpl/gallery/view_pic.html');
				$content = ob_get_contents();
				ob_end_clean();
				echo html_ajax_convert($content);
			} else {
				table(ERROR, NO_ENTRIES_ID);
			}
		} else {
			echo htmlentities(NO_ACCESS_RIGHTS);
		}
		break;
	case 'get_pictures':
		if(@$_SESSION['rights']['public']['gallery']['view'] OR @$_SESSION['rights']['superadmin']) {
			$gallery = $db->fetch_assoc('SELECT katename, b.access, name, images, cID, folder, userID, datum, username FROM '.DB_PRE.'ecp_gallery as a LEFT JOIN '.DB_PRE.'ecp_user ON ID=userID LEFT JOIN '.DB_PRE.'ecp_gallery_kate as b ON (cID = kateID) WHERE (a.access = "" OR '.str_replace('access', 'a.access', $_SESSION['access_search']).') AND galleryID = '.(int)$_GET['id']);
			if(isset($gallery['name']) AND find_access($gallery['access'])) {
				$limits = get_sql_limit($gallery['images'], LIMIT_GALLERY_PICS);
				$pics = array();
				$result = $db->query('SELECT imageID, filename, klicks, COUNT(comID) as comments FROM '.DB_PRE.'ecp_gallery_images as a LEFT JOIN '.DB_PRE.'ecp_comments ON (subID=imageID AND bereich="gallery") WHERE gID = '.(int)$_GET['id'].' GROUP BY imageID ORDER BY imageID ASC LIMIT '.$limits[1].','.LIMIT_GALLERY_PICS);
				while($row = mysql_fetch_assoc($result)) {
					$row['klicks'] = format_nr($row['klicks'], 0);
					$pics[] = $row;
				}
				$tpl = new smarty;
				if($limits[0] > 1)
					$tpl->assign('seiten', makepagelink_ajax('?section=gallery&action=gallery&id='.$_GET['id'], 'return load_gallery_page('.$_GET['id'].', {nr});', @$_GET['page'], $limits[0]));
				$tpl->assign('folder', $gallery['folder']);
				$tpl->assign('pics',$pics);
				ob_start();
				$tpl->display(DESIGN.'/tpl/gallery/pictures_ajax.html');
				$content = ob_get_contents();
				ob_end_clean();
				echo html_ajax_convert($content);
			} else {
				table(ERROR, NO_ENTRIES_ID);
			}
		} else {
			echo htmlentities(NO_ACCESS_RIGHTS);
		}
		break;
	case 'shout_page':
		if(@$_SESSION['rights']['public']['shoutbox']['view'] OR @$_SESSION['rights']['superadmin']) {
			$tpl = new smarty;
			$anzahl = $db->result(DB_PRE.'ecp_comments', 'COUNT(comID)', 'bereich="shoutbox"');
			if($anzahl) {
				$limits = get_sql_limit($anzahl, LIMIT_SHOUTBOX);
				$shouts = array();
				$db->query('SELECT comID, country, username, userID, author, datum, beitrag FROM '.DB_PRE.'ecp_comments LEFT JOIN '.DB_PRE.'ecp_user ON userID = ID WHERE bereich="shoutbox" ORDER BY datum DESC LIMIT '.$limits[1].','.LIMIT_SHOUTBOX);
				$anzahl -= $limits[1];
				while($row = $db->fetch_assoc()) {
					$row['nr'] = format_nr($anzahl--, 0);
					$row['countryname'] = @$countries[$row['country']];
					$row['datum'] = date(LONG_DATE, $row['datum']);
					$shouts[] = $row;
				}
				$tpl->assign('shoutbox', $shouts);
				if($limits[0] > 1)
					$tpl->assign('seiten', makepagelink_ajax('?section=shoutbox', 'return load_shout_page({nr});', @$_GET['page'], $limits[0]));
				ob_start();
				$tpl->display(DESIGN.'/tpl/shoutbox/shoutbox.html');
				$content = ob_get_contents();
				ob_end_clean();
				echo html_ajax_convert($content);
			} else {
				echo html_ajax_convert(NO_ENTRIES);
			}
		} else {
			echo html_ajax_convert(NO_ACCESS_RIGHTS);
		}
		break;
	case 'server_stats': 
		if(@$_SESSION['rights']['public']['server']['view'] OR @$_SESSION['rights']['superadmin']) {
			$db->query('SELECT datum, players FROM '.DB_PRE.'ecp_server_stats WHERE sID = '.(int)$_GET['id'].' ORDER BY datum DESC LIMIT '.SERVER_MAX_LOG);
			while($row = $db->fetch_assoc()) {
				@$daten.= date('Y-m-d H:i:s', $row['datum']).';'.$row['players']."\n";
			}
			$tpl = new Smarty();
			$tpl->assign('daten', substr($daten, 0, strlen($daten)-1));			
			$tpl->display(DESIGN.'/tpl/server/stats.xml');
		} else {
			echo html_ajax_convert(NO_ACCESS_RIGHTS);
		}
	break;
	case 'clankasse':
		if(@$_SESSION['rights']['public']['clankasse']['view'] OR @$_SESSION['rights']['superadmin']) {
			$anzahl = $db->result(DB_PRE.'ecp_clankasse_transaktion','COUNT(ID)', '1');
			$limits = get_sql_limit($anzahl, LIMIT_CLANKASSE_TRANS);
			$db->query('SELECT a.*, b.username, c.username as buchusername FROM '.DB_PRE.'ecp_clankasse_transaktion as a LEFT JOIN '.DB_PRE.'ecp_user as b ON b.ID = vonuser LEFT JOIN '.DB_PRE.'ecp_user as c ON c.ID = userID ORDER BY datum DESC LIMIT '.$limits[1].','. LIMIT_CLANKASSE_TRANS);
			$buchung = array();
			$tpl = new smarty;
			while($row = $db->fetch_assoc()) {
				$row['datum'] = date(LONG_DATE, $row['datum']);
				if($row['vonuser']) $row['verwendung'] .= ' '.FROM.' '.$row['username'];
				$row['geld'] = number_format($row['geld'], 2, ',','.');
				$buchung[] = $row;
			}
			if($limits[0] > 1)
				$tpl->assign('seiten', makepagelink_ajax('?section=clankasse', 'return load_clankasse_page({nr});', @$_GET['page'], $limits[0]));
			$tpl->assign('buchung', $buchung);
			ob_start();
			$tpl->display(DESIGN.'/tpl/clankasse/buchungen.html');
			$content = ob_get_contents();
			ob_end_clean();
			echo html_ajax_convert($content);
		} else {
			echo htmlentities(NO_ACCESS_RIGHTS);
		}
		break;
	case 'clankasse_overview':
		if(@$_SESSION['rights']['public']['clankasse']['view'] OR @$_SESSION['rights']['superadmin']) {
			$monat = (int)$_GET['monat'];
			$jahr = (int)$_GET['jahr'];
			$monate = array();
			$tpl = new Smarty();
			if($monat > 6) {
				$tpl->assign('vmonat', $monat-6);
				$tpl->assign('vjahr', $jahr);
			} else {
				$diff = $monat - 6;
				$tpl->assign('vmonat', 12+$diff);
				$tpl->assign('vjahr', $jahr-1);
			}
			$tpl->assign('startm', $monat);
			$tpl->assign('startj', $jahr);
			for($i = 0; $i<6; $i++) {
				$monate[$jahr.'_'.$monat]['datum'] = $monat++.'/'.$jahr;
				if($monat == 13) {
					$monat = 1; $jahr++;
				}
			}
			$tpl->assign('nmonat', $monat);
			$tpl->assign('njahr', $jahr);
			$db->query('SELECT username, userID, verwendung, monatgeld FROM '.DB_PRE.'ecp_clankasse_member LEFT JOIN '.DB_PRE.'ecp_user ON userID = ID ORDER BY username ASC');
			$user = array();
			while($row = $db->fetch_assoc()) {
				$row['geld'] = number_format($row['monatgeld'], 2, ',','.');
				$user[] = $row;
			}
			$tpl->assign('user', $user);
			$db->query('SELECT geld, verwendung, vonuser FROM '.DB_PRE.'ecp_clankasse_transaktion WHERE vonuser != 0 AND verwendung LIKE "%/%"');
			while($row = $db->fetch_assoc()) {
				$monat = explode('/', $row['verwendung']);
				if(isset($monate[$monat[0].'_'.$monat[1]])) {
					$monate[$monat[0].'_'.$monat[1]][$row['vonuser']]['geld'] = $row['geld'];
				}
			}
			$tpl->assign('user', $user);
			$tpl->assign('monate', $monate);
			ob_start();
			$tpl->display(DESIGN.'/tpl/clankasse/overview.html');
			$content = ob_get_contents();
			ob_end_clean();
			echo html_ajax_convert($content);
		} else {
			echo htmlentities(NO_ACCESS_RIGHTS);
		}
		break;
	case 'calendar':
		if(@$_SESSION['rights']['public']['calendar']['view'] OR @$_SESSION['rights']['superadmin']) {
			echo html_ajax_convert(calendar_mini());
		} else {
			echo htmlentities(NO_ACCESS_RIGHTS);
		}
		break;
	case 'get_survey':
		if(@$_SESSION['rights']['public']['survey']['view'] OR @$_SESSION['rights']['superadmin']) {
			if($db->result(DB_PRE.'ecp_survey', 'COUNT(surveyID)', 'surveyID = '.(int)$_GET['id'].' AND (access = "" OR '.$_SESSION['access_search'].')')) {
				$tpl = new smarty;
				$tpl->assign('id', (int)$_GET['id']);
				$db->query('SELECT `answerID`, `answer`, `votes` FROM `'.DB_PRE.'ecp_survey_answers` WHERE sID = '.(int)$_GET['id'].' ORDER BY answerID ASC');
				$gesamt = 0;
				$antworten = array();
				while($sub = $db->fetch_assoc()) {
					$gesamt += $sub['votes'];
					$antworten[] = $sub;
				}
				foreach($antworten AS $key => $value) {
					if($gesamt) {
						$antworten[$key]['prozent'] = round($value['votes']/$gesamt*100,1);
					} else {
						$antworten[$key]['prozent'] = 0;
					}
					$antworten[$key]['votes'] = number_format($value['votes'], 0,'','.');
				}
				ob_start();
				$tpl->assign('antworten', $antworten);
				if(isset($_GET['mini'])) {
					$tpl->assign('frage', $db->result(DB_PRE.'ecp_survey', 'frage', 'surveyID = '.(int)$_GET['id']));
					$tpl->display(DESIGN.'/tpl/modul/survey_result.html');
				} else
				$tpl->display(DESIGN.'/tpl/survey/result.html');
				$content = ob_get_contents();
				ob_end_clean();
				echo html_ajax_convert($content);
			} else {
				echo htmlentities(NO_ACCESS_RIGHTS);
			}
		} else {
			echo htmlentities(NO_ACCESS_RIGHTS);
		}
		break;
	case 'get_forum_survey':
		$survey = $db->fetch_assoc('SELECT a.fsurveyID,boardID,a.threadID,ende,frage,antworten,vonID FROM '.DB_PRE.'ecp_forum_survey as a LEFT JOIN '.DB_PRE.'ecp_forum_threads as b ON (b.threadID = a.threadID) WHERE a.fsurveyID = '.(int)$_GET['id']);
		$sub = @$db->result(DB_PRE.'ecp_forum_boards', 'boardparentID', 'boardID = '.$survey['boardID'].' AND (rightsread = "" OR '.str_replace('access', 'rightsread', $_SESSION['access_search']).')');
		if($sub === '0' OR $db->result(DB_PRE.'ecp_forum_boards', 'COUNT(boardID)', 'boardID = '.(int)$sub.' AND (rightsread = "" OR '.str_replace('access', 'rightsread', $_SESSION['access_search']).')')) {
			$tpl = new smarty;
			$array = $db->fetch_assoc('SELECT editcom, editmocom, delcom FROM '.DB_PRE.'ecp_forum_boards WHERE boardID = '.$survey['boardID']);
			$tpl->assign('delcom', find_access($array['delcom']));
			$tpl->assign('editmocom', find_access($array['editmocom']));
			$tpl->assign('editcom', find_access($array['editcom']));
			$tpl->assign('vonID', $survey['vonID']);
			$tpl->assign('frage', $survey['frage']);
			$tpl->assign('fsurveyID', $survey['fsurveyID']);
			if($survey['ende']) $tpl->assign('ende', date(LONG_DATE, $survey['ende']));
			$tpl->assign('id', (int)$_GET['id']);
			$db->query('SELECT `answerID`, `answer`, `votes` FROM `'.DB_PRE.'ecp_forum_survey_answers` WHERE fsID = '.(int)$_GET['id'].' ORDER BY answerID ASC');
			$gesamt = 0;
			$antworten = array();
			while($sub = $db->fetch_assoc()) {
				$gesamt += $sub['votes'];
				$antworten[] = $sub;
			}
			foreach($antworten AS $key => $value) {
				if($gesamt) {
					$antworten[$key]['prozent'] = round($value['votes']/$gesamt*100,1);
				} else {
					$antworten[$key]['prozent'] = 0;
				}
				$antworten[$key]['votes'] = number_format($value['votes'], 0,'','.');
			}
			ob_start();
			$tpl->assign('gesamt', number_format($gesamt, 0,'','.'));
			$tpl->assign('antworten', $antworten);
			$tpl->display(DESIGN.'/tpl/forum/result.html');
			$content = ob_get_contents();
			ob_end_clean();
			echo html_ajax_convert($content);
		} else {
			echo html_ajax_convert(NO_ACCESS_RIGHTS);
		}
		break;
	case 'search_member':
		echo '[';
		if($_POST['username'] != '') {
			$db->query('SELECT username FROM '.DB_PRE.'ecp_user WHERE username LIKE \'%'.strsave(htmlspecialchars($_POST['username'])).'%\' LIMIT 10');
			$i = 0;
			while($row = $db->fetch_assoc()) {
				if($i++ != 0) echo ',';
				echo html_ajax_convert('"'.html_entity_decode($row['username']).'"');
			}
		}
		echo ']';
		break;
	case 'search_dl':
		echo '[';
		if($_POST['name'] != '') {
			$db->query('SELECT name, kname FROM '.DB_PRE.'ecp_downloads a LEFT JOIN '.DB_PRE.'ecp_downloads_kate ON cID = kID WHERE name LIKE \'%'.strsave($_POST['name']).'%\' AND (a.access = "" OR '.str_replace('access', 'a.access', $_SESSION['access_search']).') LIMIT 10');
			$i = 0;
			while($row = $db->fetch_assoc()) {
				if($i++ != 0) echo ',';
				echo html_ajax_convert('["'.htmlentities($row['name']).'", "'.htmlentities($row['kname']).'"]');
			}
		}
		echo ']';
		break;
	case 'get_clanwars':
		if(@$_SESSION['rights']['public']['clanwars']['view'] OR @$_SESSION['rights']['superadmin']) {
			$tpl = new smarty;
			$tpl->assign('win',0);
			$tpl->assign('draw',0);
			$tpl->assign('loss',0);
			if(@$_GET['gameID']) $where = ' AND gID = '.(int)$_GET['gameID'];
			if(@$_GET['teamID']) @$where .= ' AND '.DB_PRE.'ecp_wars.tID = '.(int)$_GET['teamID'];
			if(@$_GET['matchtypeID']) @$where .= ' AND mID = '.(int)$_GET['matchtypeID'];
			if(@$_GET['xonx']) @$where .= ' AND xonx = \''.strsave($_GET['xonx']).'\'';
			switch(@$_GET['sortby']) {
				case 'opp':
					$orderby = ' oppname ';
					break;
				case 'matchtype':
					$orderby = ' matchtypename ';
					break;
				case 'team':
					$orderby = ' tname ';
					break;
				default:
					$orderby = DB_PRE.'ecp_wars.datum ';
			}
			switch(@$_GET['art']) {
				case 'asc':
					$orderby .='ASC ';
					break;
				default:
					$orderby .='DESC ';
			}
			$db->query('SELECT COUNT(result) as val, result FROM '.DB_PRE.'ecp_wars WHERE status = 1 '.@$where.' GROUP BY result');
			while($row = $db->fetch_assoc()) {
				$tpl->assign($row['result'], $row['val']);
				@$gesamt += $row['val'];
			}
			$tpl->assign('anzahl', (int)@$gesamt);
			ob_start();
			$tpl->display(DESIGN.'/tpl/clanwars/head_ajax.html');
			$inhalt['score'] = html_ajax_convert(ob_get_contents());
			ob_end_clean();
			if((int)@$gesamt) {
				ob_start();
				$limit = get_sql_limit($gesamt, LIMIT_CLANWARS);
				$db->query('SELECT `warID`, '.DB_PRE.'ecp_wars.datum, `result`, `resultscore`, `tname`, `oppname`, `country`, '.DB_PRE.'ecp_wars_opp.homepage, `icon`, `gamename`, `matchtypename`, COUNT(comID) as comments
							FROM '.DB_PRE.'ecp_wars 
							LEFT JOIN '.DB_PRE.'ecp_teams ON '.DB_PRE.'ecp_teams.tID = '.DB_PRE.'ecp_wars.tID 
							LEFT JOIN '.DB_PRE.'ecp_wars_games ON gID = gameID 
							LEFT JOIN '.DB_PRE.'ecp_wars_opp ON oID = oppID 
							LEFT JOIN '.DB_PRE.'ecp_wars_matchtype ON mID = matchtypeID 
							LEFT JOIN '.DB_PRE.'ecp_comments ON (subID = warID AND bereich = "clanwars") 
							WHERE status = 1 '.@$where.' 
							GROUP BY warID
							ORDER BY '.$orderby.'
							LIMIT '.$limit[1].','.LIMIT_CLANWARS);
				$clanwars = array();
				while($row = $db->fetch_assoc()) {
					$row['datum'] = date('d.m.y', $row['datum']);
					$row['countryname'] = $countries[$row['country']];
					$clanwars[] = $row;
				}
				$tplcw = new smarty;
				if($limit[0] > 1)
				$tplcw->assign('seiten', makepagelink_ajax('?section=clanwars&gameID='.$_GET['gameID'].'&teamID='.$_GET['teamID'].'&matchtypeID='.$_GET['matchtypeID'].'&xonx='.$_GET['xonx'].'&sortby='.$_GET['sortby'].'&art='.$_GET['art'].'', 'return load_wars('.$_GET['gameID'].', '.$_GET['teamID'].', '.$_GET['matchtypeID'].', \''.$_GET['xonx'].'\', \''.$_GET['sortby'].'\', \''.$_GET['art'].'\', {nr});', @$_GET['page'], $limit[0]));
				$tplcw->assign('clanwars', $clanwars);
				$tplcw->display(DESIGN.'/tpl/clanwars/overview.html');
				$inhalt['clanwars'] = html_ajax_convert(ob_get_contents());
				ob_end_clean();
			} else {
				$inhalt['clanwars'] = html_ajax_convert(NO_ENTRIES);
			}
			echo json_encode($inhalt);
		} else {
			echo json_encode(array('error' => html_ajax_convert(NO_ACCESS_RIGHTS)));
		}
		break;
	case 'get_rand_pic':
		get_random_pic();
		break;
	case 'del_comment':
		if($_GET['bereich'] == 'forum') {
			$array = $db->fetch_assoc('SELECT tID, a.boardID, delcom, attachs, boardparentID FROM '.DB_PRE.'ecp_forum_comments as a LEFT JOIN '.DB_PRE.'ecp_forum_boards as b ON (a.boardID = b.boardID) WHERE comID = '.(int)$_GET['id']);
			if(isset($array['delcom']) AND find_access($array['delcom'])) {
				if($array['attachs']) {
					$result = $db->query('SELECT attachID, strname FROM '.DB_PRE.'ecp_forum_attachments WHERE bID = '.$array['boardID'].' AND tID = '.$array['tID'].' AND mID = '.(int)$_GET['id']);
					while($row = mysql_fetch_assoc($result)) {
						if (unlink('uploads/forum/'.$row['attachID'].'_'.$row['strname'])) {
							$db->query('DELETE FROM '.DB_PRE.'ecp_forum_attachments WHERE attachID = '.$row['attachID']);
						}

					}
					if($db->result(DB_PRE.'ecp_forum_attachments', 'COUNT(attachID)', 'tID ='.$array['tID']) == 0) {
						$db->query('UPDATE '.DB_PRE.'ecp_forum_threads SET anhaenge = 0 WHERE threadID= '.$array['tID']);
					}
				}
				if($db->query('DELETE FROM '.DB_PRE.'ecp_forum_comments WHERE comID = '.(int)$_GET['id'])) {
					$last = $db->fetch_assoc('SELECT userID,postname,adatum FROM '.DB_PRE.'ecp_forum_comments WHERE tID = '.$array['tID'].' ORDER BY adatum DESC LIMIT 1');
					$db->query('UPDATE '.DB_PRE.'ecp_forum_threads SET `posts` = posts -1, `lastuserID` =  '.$last['userID'].', `lastusername` = \''.$last['postname'].'\', `lastreplay` = '.$last['adatum'].' WHERE threadID = '.$array['tID']);
					$last = $db->fetch_assoc('SELECT userID,postname,adatum, tID FROM '.DB_PRE.'ecp_forum_comments WHERE boardID = '.$array['boardID'].' ORDER BY adatum DESC LIMIT 1');
					$db->query('UPDATE '.DB_PRE.'ecp_forum_boards SET `posts` = posts -1, `lastpostuserID` =  '.$last['userID'].', `lastpostuser` = \''.$last['postname'].'\', `lastpost` = '.$last['adatum'].', lastthreadID = '.$last['tID'].' WHERE (boardID = '.$array['boardID'].' OR boardID = '.$array['boardparentID'].')');
					echo 'ok';
				}
			} else {
				echo html_ajax_convert(ACCESS_DENIED);
			}
		} else {

		}
		break;
	case 'del_attach':
		$comID = $db->result(DB_PRE.'ecp_forum_attachments', 'mID', 'attachID ='.(int)$_GET['id']);
		$array = $db->fetch_assoc('SELECT tID, userID, a.boardID, editcom, editmocom, delcom, attachs, boardparentID FROM '.DB_PRE.'ecp_forum_comments as a LEFT JOIN '.DB_PRE.'ecp_forum_boards as b ON (a.boardID = b.boardID) WHERE comID = '.$comID);
		if((isset($array['delcom']) AND find_access($array['delcom'])) OR (isset($array['editcom']) AND find_access($array['editcom']) AND $array['userID'] ==  @$_SESSION['userID']) OR (isset($array['editmocom']) AND find_access($array['editmocom']))) {
			@unlink('uploads/forum/'.(int)$_GET['id'].'_'.$db->result(DB_PRE.'ecp_forum_attachments', 'strname', 'attachID = '.(int)$_GET['id']));
			$db->query('DELETE FROM '.DB_PRE.'ecp_forum_attachments WHERE attachID = '.(int)$_GET['id']);
			if($db->result(DB_PRE.'ecp_forum_attachments', 'COUNT(attachID)', 'tID ='.$array['tID']) == 0) {
				$db->query('UPDATE '.DB_PRE.'ecp_forum_threads SET anhaenge = 0 WHERE threadID= '.$array['tID']);
				$db->query('UPDATE '.DB_PRE.'ecp_forum_comments SET attachs = 0 WHERE comID= '.$comID);
			} elseif ($db->result(DB_PRE.'ecp_forum_attachments', 'COUNT(attachID)', 'tID ='.$array['tID'].' AND mID = '.$comID) == 0) {
				$db->query('UPDATE '.DB_PRE.'ecp_forum_comments SET attachs = 0 WHERE comID= '.$comID);
			}
			echo 'ok';
		} else {
			echo html_ajax_convert(ACCESS_DENIED);
		}
		break;
	case 'forum_sticky':
		$board = $db->fetch_assoc('SELECT sticky, threadpin FROM '.DB_PRE.'ecp_forum_threads LEFT JOIN '.DB_PRE.'ecp_forum_boards ON (boardID = bID) WHERE threadID = '.(int)$_GET['id']);
		if(isset($board['threadpin']) AND find_access($board['threadpin'])) {
			if($board['sticky'] == 1) {
				if($db->query('UPDATE '.DB_PRE.'ecp_forum_threads SET sticky = 0 WHERE threadID = '.(int)$_GET['id'])) {
					echo 0;
				}
			} else {
				if($db->query('UPDATE '.DB_PRE.'ecp_forum_threads SET sticky = 1 WHERE threadID = '.(int)$_GET['id'])) {
					echo 1;
				}
			}
		} else {
			echo html_ajax_convert(ACCESS_DENIED);
		}
		break;
	case 'forum_close':
		$board = $db->fetch_assoc('SELECT a.closed, threadclose FROM '.DB_PRE.'ecp_forum_threads AS a LEFT JOIN '.DB_PRE.'ecp_forum_boards ON (boardID = bID) WHERE threadID = '.(int)$_GET['id']);
		if(isset($board['threadclose']) AND find_access($board['threadclose'])) {
			if($board['closed'] == 1) {
				if($db->query('UPDATE '.DB_PRE.'ecp_forum_threads SET closed = 0 WHERE threadID = '.(int)$_GET['id'])) {
					echo 0;
				}
			} else {
				if($db->query('UPDATE '.DB_PRE.'ecp_forum_threads SET closed = 1 WHERE threadID = '.(int)$_GET['id'])) {
					echo 1;
				}
			}
		} else {
			echo html_ajax_convert(ACCESS_DENIED);
		}
		break;
	case 'del_thread':
		$array = $db->fetch_assoc('SELECT a.posts, bID, threaddel, anhaenge, fsurveyID, boardparentID FROM '.DB_PRE.'ecp_forum_threads as a LEFT JOIN '.DB_PRE.'ecp_forum_boards ON (bID = boardID) WHERE threadID = '.(int)$_GET['id']);
		if(isset($array['threaddel']) AND find_access($array['threaddel'])) {
			if($array['anhaenge']) {
				$result = $db->query('SELECT attachID, strname FROM '.DB_PRE.'ecp_forum_attachments WHERE bID = '.$array['bID'].' AND tID = '.(int)$_GET['id']);
				while($row = mysql_fetch_assoc($result)) {
					@unlink('uploads/forum/'.$row['attachID'].'_'.$row['strname']);
					$db->query('DELETE FROM '.DB_PRE.'ecp_forum_attachments WHERE attachID = '.$row['attachID']);
				}
			}
			if($array['fsurveyID']) {
				$db->query('DELETE FROM '.DB_PRE.'ecp_forum_survey WHERE fsurveyID = '.$array['fsurveyID']);
				$db->query('DELETE FROM '.DB_PRE.'ecp_forum_survey_answers WHERE fsID = '.$array['fsurveyID']);
				$db->query('DELETE FROM '.DB_PRE.'ecp_forum_survey_votes WHERE fsurID = '.$array['fsurveyID']);
			}
			$db->query('DELETE FROM '.DB_PRE.'ecp_forum_abo WHERE thID = '.(int)$_GET['id']);
			$db->query('DELETE FROM '.DB_PRE.'ecp_forum_ratings WHERE tID = '.(int)$_GET['id']);
			if($db->query('DELETE FROM '.DB_PRE.'ecp_forum_comments WHERE tID = '.(int)$_GET['id'].' AND boardID = '.$array['bID'])) {
				if($db->query('DELETE FROM '.DB_PRE.'ecp_forum_threads WHERE threadID = '.(int)$_GET['id'])) {
					$last = $db->fetch_assoc('SELECT userID,postname,adatum, tID FROM '.DB_PRE.'ecp_forum_comments WHERE boardID = '.$array['bID'].' ORDER BY adatum DESC LIMIT 1');
					$db->query('UPDATE '.DB_PRE.'ecp_forum_boards SET threads = threads - 1, `posts` = posts - '.($array['posts']+1).', `lastpostuserID` =  '.(int)$last['userID'].', `lastpostuser` = \''.$last['postname'].'\', `lastpost` = '.(int)$last['adatum'].', lastthreadID = '.(int)$last['tID'].' WHERE (boardID = '.$array['bID'].' OR boardID = '.$array['boardparentID'].')');
					echo 'ok';
				}
			}
		} else {
			echo html_ajax_convert(ACCESS_DENIED);
		}
		break;
	case 'thread_move':
		$array = $db->fetch_assoc('SELECT vonID, username, country, a.posts, bID, threadmove, anhaenge, fsurveyID, boardparentID FROM '.DB_PRE.'ecp_forum_threads as a LEFT JOIN '.DB_PRE.'ecp_forum_boards ON (bID = boardID) LEFT JOIN '.DB_PRE.'ecp_user ON vonID = ID  WHERE threadID = '.(int)$_GET['id']);
		if(isset($array['threadmove']) AND find_access($array['threadmove'])) {
			if(isset($_GET['newboard']) AND $db->result(DB_PRE.'ecp_forum_boards', 'COUNT(boardID)', 'isforum = 1 AND boardID = '.(int)$_GET['newboard']) AND $db->result(DB_PRE.'ecp_forum_boards', 'COUNT(boardID)', 'boardID = '.(int)$_GET['newboard'].' AND (rightsread = "" OR '.str_replace('access', 'rightsread', $_SESSION['access_search']).')')) {
				if((int)$_GET['newboard'] != 0) {
					if($array['anhaenge']) {
						$db->query('UPDATE '.DB_PRE.'ecp_forum_attachments SET bID = '.(int)$_GET['newboard'].' WHERE tID = '.(int)$_GET['id']);
					}
					if($array['fsurveyID']) {
						$db->query('UPDATE '.DB_PRE.'ecp_forum_survey SET boardID  = '.(int)$_GET['newboard'].' WHERE threadID = '.(int)$_GET['id']);
					}
					if((int)$_GET['msguser'] == 1 AND $array['vonID'] != 0) {
						$text = $db->fetch_assoc('SELECT * FROM '.DB_PRE.'ecp_texte WHERE name = "THREAD_MOVE" AND lang = \''.$array['country'].'\'');
						if(!isset($text['name'])) {
							$text = $db->fetch_assoc('SELECT * FROM '.DB_PRE.'ecp_texte WHERE name = "THREAD_MOVE" AND lang = \'de\'');
						}
						$search = array('{username}', '{link}');
						$replace = array($db->result(DB_PRE.'ecp_user', 'username', 'ID = '.$array['vonID']), '<a href="?section=forum&action=thread&threadID='.(int)$_GET['id'].'&boardID='.(int)$_GET['newboard'].'">?section=forum&action=thread&threadID='.(int)$_GET['id'].'&boardID='.(int)$_GET['newboard'].'</a>');
						message_send($array['vonID'], 0, $text['content2'], str_replace($search, $replace, $text['content']), 0, 1);
					}
					$db->query('UPDATE '.DB_PRE.'ecp_forum_abo SET boID = '.(int)$_GET['newboard'].' WHERE thID = '.(int)$_GET['id']);
					$db->query('UPDATE '.DB_PRE.'ecp_forum_ratings SET bID = '.(int)$_GET['newboard'].' WHERE tID = '.(int)$_GET['id']);
					if($db->query('UPDATE '.DB_PRE.'ecp_forum_comments SET boardID = '.(int)$_GET['newboard'].' WHERE tID = '.(int)$_GET['id'])) {
						if($db->query('UPDATE '.DB_PRE.'ecp_forum_threads SET bID = '.(int)$_GET['newboard'].' WHERE threadID = '.(int)$_GET['id'])) {
							$last = $db->fetch_assoc('SELECT userID,postname,adatum, tID FROM '.DB_PRE.'ecp_forum_comments WHERE boardID = '.$array['bID'].' ORDER BY adatum DESC LIMIT 1');
							$db->query('UPDATE '.DB_PRE.'ecp_forum_boards SET threads = threads - 1, `posts` = posts - '.($array['posts']+1).', `lastpostuserID` =  '.(int)$last['userID'].', `lastpostuser` = \''.$last['postname'].'\', `lastpost` = '.(int)$last['adatum'].', lastthreadID = '.(int)$last['tID'].' WHERE (boardID = '.$array['bID'].' OR boardID = '.$array['boardparentID'].')');
							$last = $db->fetch_assoc('SELECT userID,postname,adatum, tID FROM '.DB_PRE.'ecp_forum_comments WHERE boardID = '.(int)$_GET['newboard'].' ORDER BY adatum DESC LIMIT 1');
							$db->query('UPDATE '.DB_PRE.'ecp_forum_boards SET threads = threads + 1, `posts` = posts + '.($array['posts']+1).', `lastpostuserID` =  '.(int)$last['userID'].', `lastpostuser` = \''.$last['postname'].'\', `lastpost` = '.(int)$last['adatum'].', lastthreadID = '.(int)$last['tID'].' WHERE (boardID = '.(int)$_GET['newboard'].' OR boardID = '.$db->result(DB_PRE.'ecp_forum_boards', 'boardparentID', 'boardID = '.(int)$_GET['newboard']).')');
							echo 'ok';
						}
					}
				} else {
					echo html_ajax_convert(FORUM_CHOOSE_NEW_BOARD);
				}
			} else {
				$tpl = new smarty;
				$tpl->assign('id', (int)$_GET['id']);
				$db->query('SELECT `boardID`, `boardparentID`, `name`, `isforum`
										FROM '.DB_PRE.'ecp_forum_boards 
										WHERE (rightsread = "" OR '.str_replace('access', 'rightsread', $_SESSION['access_search']).') AND boardID != '.$array['bID'].'
										ORDER BY boardparentID, posi ASC');
				$boards = array();
				while($row = $db->fetch_assoc()) {
					if($row['isforum'] == 0) {
						$boards[$row['boardID']]['name'] = $row['name'];
						$boards[$row['boardID']]['isforum'] = false;
					} elseif ($row['isforum'] == 1 AND $row['boardparentID'] == 0) {
						$boards[$row['boardID']]['name'] = $row['name'];
						$boards[$row['boardID']]['isforum'] = true;
					} else {
						$boards[$row['boardparentID']]['subs'][$row['boardID']]['name'] = $row['name'];
					}
				}
				$links = '<option value="-1">'.CHOOSE.'</option>';
				foreach($boards AS $key=>$value) {
					$links .= '<option value="'.(@$value['isforum'] ? $key : '-1').'">'.$value['name'].'</option>';
					if(isset($value['subs'])) {
						foreach($value['subs'] AS $key1=>$value1) {
							$links .= '<option value="'.$key1.'">|- '.$value1['name'].'</option>';
						}
					}
				}
				$tpl->assign('select', $links);
				ob_start();
				$tpl->display(DESIGN.'/tpl/admin/forum_change_board.html');
				$content = ob_get_contents();
				ob_end_clean();
				echo html_ajax_convert($content);
			}
		} else {
			echo html_ajax_convert(ACCESS_DENIED);
		}
		break;
	case 'thread_survey_edit':
		$array = $db->fetch_assoc('SELECT ende, frage, antworten, c.threadID, bID, vonID, a.boardID, a.boardparentID, a.rightsread, a.editcom, a.editmocom, a.delcom, b.rightsread as parentRead FROM '.DB_PRE.'ecp_forum_survey AS s LEFT JOIN '.DB_PRE.'ecp_forum_threads AS c ON (c.threadID = s.threadID) LEFT JOIN '.DB_PRE.'ecp_forum_boards AS a ON (s.boardID = a.boardID) LEFT JOIN '.DB_PRE.'ecp_forum_boards AS b ON (a.boardparentID = b.boardID) WHERE s.fsurveyID = '.(int)$_GET['id']);
		if((isset($array['rightsread']) AND find_access($array['rightsread']) AND find_access($array['parentRead'])) AND ((isset($array['delcom']) AND find_access($array['delcom'])) OR (isset($array['editcom']) AND find_access($array['editcom']) AND $array['userID'] ==  @$_SESSION['userID']) OR (isset($array['editmocom']) AND find_access($array['editmocom'])))) {
			if(isset($_POST['frage'])) {
				if($_POST['frage'] == '') {
					echo NOT_NEED_ALL_INPUTS;
				} else {
					if($db->query('UPDATE '.DB_PRE.'ecp_forum_survey SET frage = \''.strsave(comment_save($_POST['frage'])).'\', ende = '.(int)@strtotime($_POST['ende']).', antworten = '.(int)$_POST['antworten'].' WHERE fsurveyID = '.(int)$_GET['id'])) {
						echo 'ok';
					}
				}
			} else {
				ob_start();
				$tpl = new Smarty();
				$tpl->assign('id', (int)$_GET['id']);
				$tpl->assign('frage', $array['frage']);
				if($array['ende'] != 0) $tpl->assign('ende', date('Y-m-d H:i:s', $array['ende']));
				$tpl->assign('antworten', $array['antworten']);
				$tpl->display(DESIGN.'/tpl/forum/survey_edit.html');
				$content = ob_get_contents();
				ob_end_clean();
				echo html_ajax_convert($content);
			}
		} else {
			echo html_ajax_convert(ACCESS_DENIED);
		}
		break;
	case 'thread_answer_add':
		$array = $db->fetch_assoc('SELECT ende, frage, antworten, c.threadID, bID, vonID, a.boardID, a.boardparentID, a.rightsread, a.editcom, a.editmocom, a.delcom, b.rightsread as parentRead FROM '.DB_PRE.'ecp_forum_survey AS s LEFT JOIN '.DB_PRE.'ecp_forum_threads AS c ON (c.threadID = s.threadID) LEFT JOIN '.DB_PRE.'ecp_forum_boards AS a ON (s.boardID = a.boardID) LEFT JOIN '.DB_PRE.'ecp_forum_boards AS b ON (a.boardparentID = b.boardID) WHERE s.fsurveyID = '.(int)$_GET['id']);
		if((isset($array['rightsread']) AND find_access($array['rightsread']) AND find_access($array['parentRead'])) AND ((isset($array['delcom']) AND find_access($array['delcom'])) OR (isset($array['editcom']) AND find_access($array['editcom']) AND $array['userID'] ==  @$_SESSION['userID']) OR (isset($array['editmocom']) AND find_access($array['editmocom'])))) {
			if(isset($_POST['answer'])) {
				if($_POST['answer'] == '') {
					echo NOT_NEED_ALL_INPUTS;
				} else {
					if($db->query('INSERT INTO '.DB_PRE.'ecp_forum_survey_answers (fsID, answer) VALUES ('.(int)$_GET['id'].', \''.strsave(htmlspecialchars($_POST['answer'])).'\')')) {
						echo 'ok';
					}
				}
			} else {
				ob_start();
				$tpl = new Smarty();
				$tpl->assign('id', (int)$_GET['id']);
				$tpl->display(DESIGN.'/tpl/forum/answer_add.html');
				$content = ob_get_contents();
				ob_end_clean();
				echo html_ajax_convert($content);
			}
		} else {
			echo html_ajax_convert(ACCESS_DENIED);
		}
		break;
	case 'thread_answer_edit':
		$array = $db->fetch_assoc('SELECT answer, fsID, c.threadID, bID, vonID, a.boardID, a.boardparentID, a.rightsread, a.editcom, a.editmocom, a.delcom, b.rightsread as parentRead FROM '.DB_PRE.'ecp_forum_survey_answers  LEFT JOIN '.DB_PRE.'ecp_forum_survey AS s ON (fsID = s.fsurveyID) LEFT JOIN '.DB_PRE.'ecp_forum_threads AS c ON (c.threadID = s.threadID) LEFT JOIN '.DB_PRE.'ecp_forum_boards AS a ON (s.boardID = a.boardID) LEFT JOIN '.DB_PRE.'ecp_forum_boards AS b ON (a.boardparentID = b.boardID) WHERE answerID = '.(int)$_GET['id']);
		if((isset($array['rightsread']) AND find_access($array['rightsread']) AND find_access($array['parentRead'])) AND ((isset($array['delcom']) AND find_access($array['delcom'])) OR (isset($array['editcom']) AND find_access($array['editcom']) AND $array['userID'] ==  @$_SESSION['userID']) OR (isset($array['editmocom']) AND find_access($array['editmocom'])))) {
			if(isset($_POST['answer'])) {
				if($_POST['answer'] == '') {
					echo NOT_NEED_ALL_INPUTS;
				} else {
					if($db->query('UPDATE '.DB_PRE.'ecp_forum_survey_answers SET answer = \''.strsave(htmlspecialchars($_POST['answer'])).'\' WHERE answerID = '.(int)$_GET['id'])) {
						echo 'ok';
					}
				}
			} else {
				ob_start();
				$tpl = new Smarty();
				$tpl->assign('id', (int)$_GET['id']);
				$tpl->assign('sid', $array['fsID']);
				$tpl->assign('answer', $array['answer']);
				$tpl->display(DESIGN.'/tpl/forum/answer_edit.html');
				$content = ob_get_contents();
				ob_end_clean();
				echo html_ajax_convert($content);
			}
		} else {
			echo html_ajax_convert(ACCESS_DENIED);
		}
		break;
	case 'thread_answer_del':
		$array = $db->fetch_assoc('SELECT fsID, c.threadID, bID, vonID, a.boardID, a.boardparentID, a.rightsread, a.editcom, a.editmocom, a.delcom, b.rightsread as parentRead FROM '.DB_PRE.'ecp_forum_survey_answers  LEFT JOIN '.DB_PRE.'ecp_forum_survey AS s ON (fsID = s.fsurveyID) LEFT JOIN '.DB_PRE.'ecp_forum_threads AS c ON (c.threadID = s.threadID) LEFT JOIN '.DB_PRE.'ecp_forum_boards AS a ON (s.boardID = a.boardID) LEFT JOIN '.DB_PRE.'ecp_forum_boards AS b ON (a.boardparentID = b.boardID) WHERE answerID = '.(int)$_GET['id']);
		if((isset($array['rightsread']) AND find_access($array['rightsread']) AND find_access($array['parentRead'])) AND ((isset($array['delcom']) AND find_access($array['delcom'])) OR (isset($array['editcom']) AND find_access($array['editcom']) AND $array['userID'] ==  @$_SESSION['userID']) OR (isset($array['editmocom']) AND find_access($array['editmocom'])))) {
			if($db->query('DELETE FROM '.DB_PRE.'ecp_forum_survey_answers WHERE answerID = '.(int)$_GET['id']).' AND fsID = '.$array['fsID']) {
				echo 'ok';
			}
		} else {
			echo html_ajax_convert(ACCESS_DENIED);
		}
		break;
	case 'thread_survey_del':
		$array = $db->fetch_assoc('SELECT ende, frage, antworten, c.threadID, bID, vonID, a.boardID, a.boardparentID, a.rightsread, a.editcom, a.editmocom, a.delcom, b.rightsread as parentRead FROM '.DB_PRE.'ecp_forum_survey AS s LEFT JOIN '.DB_PRE.'ecp_forum_threads AS c ON (c.threadID = s.threadID) LEFT JOIN '.DB_PRE.'ecp_forum_boards AS a ON (s.boardID = a.boardID) LEFT JOIN '.DB_PRE.'ecp_forum_boards AS b ON (a.boardparentID = b.boardID) WHERE s.fsurveyID = '.(int)$_GET['id']);
		if((isset($array['rightsread']) AND find_access($array['rightsread']) AND find_access($array['parentRead'])) AND ((isset($array['delcom']) AND find_access($array['delcom'])) OR (isset($array['editcom']) AND find_access($array['editcom']) AND $array['userID'] ==  @$_SESSION['userID']) OR (isset($array['editmocom']) AND find_access($array['editmocom'])))) {
			if($db->query('DELETE FROM '.DB_PRE.'ecp_forum_survey WHERE fsurveyID = '.(int)$_GET['id'])) {
				if($db->query('DELETE FROM '.DB_PRE.'ecp_forum_survey_answers WHERE fsID = '.(int)$_GET['id'])) {
					if($db->query('DELETE FROM '.DB_PRE.'ecp_forum_survey_votes WHERE fsurID = '.(int)$_GET['id'])) {
						$db->query('UPDATE '.DB_PRE.'ecp_forum_threads SET fsurveyID = 0 WHERE threadID = '.$array['threadID']);
						echo 'ok';
					}
				}
			}
		} else {
			echo html_ajax_convert(ACCESS_DENIED);
		}
		break;
	case 'forum_abo':
		$array = $db->fetch_assoc('SELECT bID, a.boardparentID, a.rightsread, b.rightsread as parentRead FROM '.DB_PRE.'ecp_forum_threads LEFT JOIN '.DB_PRE.'ecp_forum_boards AS a ON (bID = a.boardID) LEFT JOIN '.DB_PRE.'ecp_forum_boards AS b ON (a.boardparentID = b.boardID) WHERE threadID = '.(int)$_GET['id']);
		if((isset($array['rightsread']) AND find_access($array['rightsread']) AND find_access($array['parentRead'])) AND isset($_SESSION['userID'])) {
			if($db->result(DB_PRE.'ecp_forum_abo', 'COUNT(aboID)', 'userID = '.$_SESSION['userID'].' AND thID = '.(int)$_GET['id'])) {
				if($db->query('DELETE FROM '.DB_PRE.'ecp_forum_abo WHERE thID = '.(int)$_GET['id'].' AND userID = '.$_SESSION['userID'])) {
					echo 0;
				}
			} else {
				if($db->query('INSERT INTO '.DB_PRE.'ecp_forum_abo (`thID`, `boID`, `userID`) VALUES ('.(int)$_GET['id'].', '.$array['bID'].', '.$_SESSION['userID'].')')) {
					echo 1;
				}
			}
		} else {
			echo htmlentities(ACCESS_DENIED);
		}
		break;
	case 'thread_vote':
		$thread = $db->fetch_assoc('SELECT `threadID`, `bID`, a.isforum, a.closed as forumclosed,
												 a.rightsread, b.rightsread as parentRead FROM '.DB_PRE.'ecp_forum_threads LEFT JOIN '.DB_PRE.'ecp_forum_boards AS a ON (bID = a.boardID) LEFT JOIN '.DB_PRE.'ecp_forum_boards AS b ON (b.boardID = a.boardparentID) WHERE threadID = '.(int)$_GET['id']);
		if(find_access($thread['rightsread']) AND find_access($thread['parentRead']) AND $thread['isforum'] AND isset($_SESSION['userID'])) {
			if($db->result(DB_PRE.'ecp_forum_ratings', 'COUNT(rateID)', 'userID = '.$_SESSION['userID'].' AND tID = '.(int)$_GET['id'])) {
				echo html_ajax_convert(FORUM_RATING_ALLREADY);
			} else {
				if((int)$_GET['wert'] < 1 OR (int)$_GET['wert'] > 5) {
					echo html_ajax_convert(FORUM_RATING_WRONG);
				} else {
					if($db->query('INSERT INTO '.DB_PRE.'ecp_forum_ratings (`userID`, `tID`, `bID`, `wert`) VALUES ('.$_SESSION['userID'].', '.(int)$_GET['id'].', '.$thread['bID'].', '.(int)$_GET['wert'].')')) {
						$array = $db->fetch_assoc('SELECT COUNT(rateID) as anzahl, AVG(wert) as mittel FROM '.DB_PRE.'ecp_forum_ratings WHERE tID = '.(int)$_GET['id']);
						echo get_forum_rating($array['mittel']);
						$db->query('UPDATE '.DB_PRE.'ecp_forum_threads SET  rating = '.$array['mittel'].', ratingvotes = '.$array['anzahl'].' WHERE threadID = '.(int)$_GET['id']);

					}
				}
			}
		} else {
			echo html_ajax_convert(NO_ACCESS_RIGHTS);
		}
		break;
	case 'get_webstats':
		switch($_GET['mode']) {
			case 'browser_hits':
				$db->query('SELECT `variable`, `hits` FROM '.DB_PRE.'ecp_stats_browser WHERE type="browser" ORDER BY hits DESC');				
				echo'<?xml version="1.0" encoding="UTF-8"?>				
<pie>
';
				while($row = $db->fetch_assoc()) {
					if($row['variable'] == 'Unbekannt') $row['variable'] = OTHER;
					echo '<slice title="'.$row['variable'].'">'.$row['hits'].'</slice>';
				}
echo '
</pie>';				
			break;
			case 'browser_visits':
				$db->query('SELECT `variable`, `visits` FROM '.DB_PRE.'ecp_stats_browser WHERE type="browser" ORDER BY visits DESC');				
				echo'<?xml version="1.0" encoding="UTF-8"?>				
<pie>
';
				while($row = $db->fetch_assoc()) {
					if($row['variable'] == 'Unbekannt') $row['variable'] = OTHER;
					echo '<slice title="'.$row['variable'].'">'.$row['visits'].'</slice>';
				}
echo '
</pie>';						
			break;
			case 'os_hits':
				$db->query('SELECT `variable`, `hits` FROM '.DB_PRE.'ecp_stats_browser WHERE type="os" ORDER BY hits DESC');				
				echo'<?xml version="1.0" encoding="UTF-8"?>				
<pie>
';
				while($row = $db->fetch_assoc()) {
					if($row['variable'] == 'Unbekannt') $row['variable'] = OTHER;
					echo '<slice title="'.$row['variable'].'">'.$row['hits'].'</slice>';
				}
echo '
</pie>';						
			break;
			case 'os_visits':	
				$db->query('SELECT `variable`, `visits` FROM '.DB_PRE.'ecp_stats_browser WHERE type="os" ORDER BY visits DESC');				
				echo'<?xml version="1.0" encoding="UTF-8"?>				
<pie>
';
				while($row = $db->fetch_assoc()) {
					if($row['variable'] == 'Unbekannt') $row['variable'] = OTHER;
					echo '<slice title="'.$row['variable'].'">'.$row['visits'].'</slice>';
				}
echo '
</pie>';					
			break;
			case 'user_hits':
				$row = $db->fetch_assoc('SELECT SUM(hits) as hits, SUM(userhits) as userhits FROM '.DB_PRE.'ecp_stats_jahr');				
				echo'<?xml version="1.0" encoding="UTF-8"?>				
<pie>
  <slice title="'.$groups['visitor'].'">'.($row['hits']-$row['userhits']).'</slice>
  <slice title="'.$groups['comMember'].'">'.$row['userhits'].'</slice>
</pie>';
			break;											
			case 'year':
				$jahr = (int)$_GET['year'];		
				$monate = '';
				$hits = '';
				$visits = '';
				$db->query('SELECT monat, hits, visits FROM '.DB_PRE.'ecp_stats_monat WHERE jahr = '.$jahr.' ORDER BY monat ASC');
				$i = 0;
				while($row = $db->fetch_assoc()) {
					$monate .= '<value xid="'.$i.'">'.html_ajax_convert($monatsnamen[$row['monat']]).'</value>';
					$hits .= '<value xid="'.$i.'" url="javascript: load_month('.$jahr.', '.$row['monat'].');">'.$row['hits'].'</value>';
					$visits .= '<value xid="'.$i.'" url="javascript: load_month('.$jahr.', '.$row['monat'].');">'.$row['visits'].'</value>';
					$i++;
				}				
				echo '<?xml version="1.0" encoding="UTF-8"?>
	<chart>
	  <labels>                                                    <!-- LABELS -->
	    <label>
	      <x></x>                                                 <!-- [0] (Number / Number% / !Number) -->
	      <y>40</y>                                               <!-- [0] (Number / Number% / !Number) -->
	      <rotate></rotate>                                       <!-- [false] (true / false) -->
	      <width></width>                                         <!-- [] (Number / Number%) if empty, will stretch from left to right untill label fits -->
	      <align>center</align>                                   <!-- [left] (left / center / right) -->  
	      <text_color></text_color>                               <!-- [text_color] (hex color code) button text color -->
	      <text_size></text_size>                                 <!-- [text_size](Number) button text size --> 
	      <text>
	        <![CDATA[<b>'.$jahr.'</b>]]>
	      </text> 	            
	    </label>    
	  </labels>	
	<series>
		'.$monate.'
	</series>
	<graphs>
		<graph gid="1" title="'.HITS.'">
			'.$hits.'
		</graph>
		<graph gid="2" title="'.VISITS.'">
			'.$visits.'
		</graph>		
	</graphs>
</chart>';				
			break;
			case 'month':
				$monat = (int)$_GET['month'];
				$jahr = (int)$_GET['year'];		
				$tag = '';
				$hits = '';
				$visits = '';
				$db->query('SELECT tag,	hits, visits FROM '.DB_PRE.'ecp_stats_tag WHERE jahr = '.$jahr.' AND monat = '.$monat.' ORDER BY tag ASC');
				$i = 0;
				while($row = $db->fetch_assoc()) {
					$tag .= '<value xid="'.$i.'">'.$row['tag'].'</value>';
					$hits .= '<value xid="'.$i.'" url="javascript: load_day('.$jahr.', '.$monat.', '.$row['tag'].');">'.$row['hits'].'</value>';
					$visits .= '<value xid="'.$i.'" url="javascript: load_day('.$jahr.', '.$monat.', '.$row['tag'].');">'.$row['visits'].'</value>';
					$i++;
				}				
				echo '<?xml version="1.0" encoding="UTF-8"?>
	<chart>
	  <labels>                                                    <!-- LABELS -->
	    <label>
	      <x></x>                                                 <!-- [0] (Number / Number% / !Number) -->
	      <y>40</y>                                               <!-- [0] (Number / Number% / !Number) -->
	      <rotate></rotate>                                       <!-- [false] (true / false) -->
	      <width></width>                                         <!-- [] (Number / Number%) if empty, will stretch from left to right untill label fits -->
	      <align>center</align>                                   <!-- [left] (left / center / right) -->  
	      <text_color></text_color>                               <!-- [text_color] (hex color code) button text color -->
	      <text_size></text_size>                                 <!-- [text_size](Number) button text size --> 
	      <text>
	        <![CDATA[<b>'.html_ajax_convert($monatsnamen[$monat]).' '.$jahr.'</b>]]>
	      </text> 	            
	    </label>    
	  </labels>	
	<series>
		'.$tag.'
	</series>
	<graphs>
		<graph gid="1" title="'.HITS.'">
			'.$hits.'
		</graph>
		<graph gid="2" title="'.VISITS.'">
			'.$visits.'
		</graph>		
	</graphs>
</chart>';
			break;
			case 'day':
				$monat = (int)$_GET['month'];
				$jahr = (int)$_GET['year'];		
				$tag = (int)$_GET['day'];		
				$stunde = '';
				$hits = '';
				$visits = '';
				$db->query('SELECT stunde, hits, visits FROM '.DB_PRE.'ecp_stats_stunde WHERE jahr = '.$jahr.' AND monat = '.$monat.' AND tag = '.$tag.' ORDER BY stunde ASC');
				$i = 0;
				while($row = $db->fetch_assoc()) {
					$stunde .= '<value xid="'.$i.'">'.$row['stunde'].':00</value>';
					$hits .= '<value xid="'.$i.'">'.$row['hits'].'</value>';
					$visits .= '<value xid="'.$i.'">'.$row['visits'].'</value>';
					$i++;
				}				
				echo '<?xml version="1.0" encoding="UTF-8"?>
	<chart>
	  <labels>                                                    <!-- LABELS -->
	    <label>
	      <x></x>                                                 <!-- [0] (Number / Number% / !Number) -->
	      <y>40</y>                                               <!-- [0] (Number / Number% / !Number) -->
	      <rotate></rotate>                                       <!-- [false] (true / false) -->
	      <width></width>                                         <!-- [] (Number / Number%) if empty, will stretch from left to right untill label fits -->
	      <align>center</align>                                   <!-- [left] (left / center / right) -->  
	      <text_color></text_color>                               <!-- [text_color] (hex color code) button text color -->
	      <text_size></text_size>                                 <!-- [text_size](Number) button text size --> 
	      <text>
	        <![CDATA[<b>'.$tag.' '.html_ajax_convert($monatsnamen[$monat]).' '.$jahr.'</b>]]>
	      </text> 	            
	    </label>    
	  </labels>	  
	<series>
		'.$stunde.'
	</series>
	<graphs>
		<graph gid="1" title="'.HITS.'">
			'.$hits.'
		</graph>
		<graph gid="2" title="'.VISITS.'">
			'.$visits.'
		</graph>		
	</graphs>
</chart>';				
			break;
			default: 
				$jahre = '';
				$hits = '';
				$visits = '';
				$db->query('SELECT jahr, hits, visits FROM '.DB_PRE.'ecp_stats_jahr ORDER BY jahr ASC');
				$i = 0;
				while($row = $db->fetch_assoc()) {
					$jahre .= '<value xid="'.$i.'">'.$row['jahr'].'</value>';
					$hits .= '<value xid="'.$i.'" url="javascript: load_year('.$row['jahr'].');">'.$row['hits'].'</value>';
					$visits .= '<value xid="'.$i.'" url="javascript: load_year('.$row['jahr'].');">'.$row['visits'].'</value>';
					$i++;
				}				
				echo '<?xml version="1.0" encoding="UTF-8"?>
	<chart>	
	<series>
		'.$jahre.'
	</series>
	<graphs>
		<graph gid="1" title="'.HITS.'">
			'.$hits.'
		</graph>
		<graph gid="2" title="'.VISITS.'">
			'.$visits.'
		</graph>		
	</graphs>
</chart>';				
		}
	break;
	case 'admin':
		include_once('admin/lang/'.LANGUAGE.'.php');
		if((isset($_SESSION['rights']['superadmin']) AND $_SESSION['rights']['superadmin']) OR isset($_SESSION['rights']['admin'])) {
			if(!@$_SESSION['admin_verify']) {
				echo html_ajax_convert('Bitte erst ins Adminmenu einloggen!');
			} else {
				switch($_GET['site']) {
					case 'news':
						switch($_GET['action']) {
							case 'del':
								if(@$_SESSION['rights']['admin']['news']['del'] OR @$_SESSION['rights']['superadmin']) {
									$id = (int)$_GET['id'];
									if($db->result(DB_PRE.'ecp_news', 'COUNT(newsID)', 'newsID = '.$id)) {
										if($db->query('DELETE FROM '.DB_PRE.'ecp_comments WHERE subID = '.$id.' AND bereich = "news"')){
											if($db->query('DELETE FROM '.DB_PRE.'ecp_news WHERE newsID = '.$id)) {
												echo 'ok';
											}
										}
									} else {
										echo NO_ENTRIES_ID;
									}
								} else {
									echo NO_ADMIN_RIGHTS;
								}
								break;
						}
						break;
					case 'dlupload':
						if(@$_SESSION['rights']['admin']['downloads']['upload'] OR @$_SESSION['rights']['superadmin']) {
							if(($_GET['folder'] == '' OR is_dir('downloads'.$_GET['folder'])) AND count($_FILES)) {
								foreach($_FILES AS $key=>$value) {
									if($_FILES[$key]['name'] == '') continue;
									$_FILES[$key]['name'] = str_replace(array(' '), array('_'),$_FILES[$key]['name']);
									if(file_exists(str_replace('//', '/', 'downloads/'.$_GET['folder'].'/'.$_FILES[$key]['name']))) {
										$error = FILE_EXIST.'('.$_FILES[$key]['name'].')';
									} else {
										if(move_uploaded_file($_FILES[$key]['tmp_name'], str_replace('//', '/', 'downloads/'.$_GET['folder'].'/'.$_FILES[$key]['name']))) {
											chmod(str_replace('//', '/', 'downloads'.$_GET['folder'].'/'.$_FILES[$key]['name']), CHMOD);
										} else {
											$error = 'Upload nicht möglich';
										}
									}
								}
							} else {
								$error = FILE_NOT_FOUND;
							}
						} else {
							$error = NO_ADMIN_RIGHTS;
						}
						if(UPLOAD_METHOD == 'Flash') {
							if(isset($error)) {
								echo html_ajax_convert(json_encode(array('result'=>'failed', 'error'=>$error)));
							} else {
								echo html_ajax_convert(json_encode(array('result'=>'success', 'size'=>'')));
							}
						} else {
							if(isset($error)) {
								echo $error.'<br /><a href="index.php?section=admin&site=downloads">Back to Page</a>';
							} else {
								header1('index.php?section=admin&site=downloads&show=true');
							}
						}
						break;
					case 'createfolder':
						if(@$_SESSION['rights']['admin']['downloads']['create_dir'] OR @$_SESSION['rights']['superadmin']) {
							if( preg_match( '/[^a-zA-Z0-9_\-]/', $_POST['name'] ) OR $_POST['name'] == '') {
								echo html_ajax_convert(INVALID_FOLDER_NAME);
							} else {
								if(is_dir('downloads'.$_POST['dir'].'/'.$_POST['name'])) {
									echo html_ajax_convert(FOLDER_ALLREADY_EXIST);
								} else {
									umask(0);
									if(mkdir('downloads'.$_POST['dir'].'/'.$_POST['name'], 0777)) {
										echo 'ok';
									} else {
										echo html_ajax_convert(ADD_FOLDER_ERROR);
									}
								}
							}
						} else {
							echo html_ajax_convert(NO_ADMIN_RIGHTS);
						}
						break;
					case 'delfile':
						if(@$_SESSION['rights']['admin']['downloads']['delfile'] OR @$_SESSION['rights']['superadmin']) {
							if(is_file('downloads'.$_POST['dir'].$_POST['name'])) {
								if(unlink('downloads'.$_POST['dir'].$_POST['name'])) {
									echo 'ok';
								} else {
									echo ERROR_FILE_DELETE;
								}
							} else {
								echo FILE_DOESNT_EXIST;
							}
						} else {
							echo NO_ADMIN_RIGHTS;
						}
						break;
					case 'getmembers':
						if(@$_SESSION['rights']['admin']['groups']['add_m'] OR @$_SESSION['rights']['admin']['news']['del_m'] OR @$_SESSION['rights']['superadmin']) {
							$id = (int)$_GET['gid'];
							$anzahl = $db->result(DB_PRE.'ecp_user_groups', 'COUNT(userID)', 'gID = '.$id);
							$seiten = get_sql_limit($anzahl, LIMIT_MEMBERS);
							$db->query('SELECT username, userID FROM '.DB_PRE.'ecp_user_groups LEFT JOIN '.DB_PRE.'ecp_user ON (userID = ID) WHERE gID = '.$id.' ORDER BY username LIMIT '.$seiten[1].', '.LIMIT_MEMBERS);
							$user = array();
							while($row = $db->fetch_assoc()) {
								$user[] = $row;
							}
							$tpl = new smarty;
							$tpl->assign('id', $id);
							if($seiten[0] > 1)
							$tpl->assign('seiten', makepagelink_ajax('#', 'return load_member('.$id.', {nr});', @$_GET['page'], $seiten[0]));
							$tpl->assign('user', @$user);
							ob_start();
							$tpl->display(DESIGN.'/tpl/admin/groups_members.html');
							$content = ob_get_contents();
							ob_end_clean();
							echo html_ajax_convert($content);
						} else {
							echo NO_ADMIN_RIGHTS;
						}
						break;
					case 'addmember':
						if(@$_SESSION['rights']['admin']['groups']['add_m'] OR @$_SESSION['rights']['superadmin']) {
							$gid = (int)$_GET['gid'];
							if($db->result(DB_PRE.'ecp_groups', 'COUNT(groupID)', 'groupID = '.$gid)) {
								$user = explode(',',$_POST['users']);
								foreach($user AS $value) {
									$value = trim($value);
									if($value == '') continue;
									$id = @$db->result(DB_PRE.'ecp_user', 'ID', 'username = \''.strsave($value).'\'');
									if($id != '' AND !$db->result(DB_PRE.'ecp_user_groups', 'COUNT(userID)', 'gID = '.$gid.' AND userID = '.$id)) {
										$db->query('INSERT INTO '.DB_PRE.'ecp_user_groups (userID, gID) VALUES ('.$id.', '.$gid.')');
										$db->query('UPDATE '.DB_PRE.'ecp_user SET update_rights = 1 WHERE ID = '.$id);
									}
								}
								echo 'ok';
							} else {
								echo html_ajax_convert(GROUP_DONT_EXIST);
							}
						} else {
							echo html_ajax_convert(NO_ADMIN_RIGHTS);
						}
						break;
					case 'delmember':
						if(@$_SESSION['rights']['admin']['groups']['del_m'] OR @$_SESSION['rights']['superadmin']) {
							if($db->query('DELETE FROM '.DB_PRE.'ecp_user_groups WHERE gID = '.(int)$_GET['gid'].' AND userID = '.(int)$_GET['id'])) {
								$db->query('UPDATE '.DB_PRE.'ecp_user SET update_rights = 1 WHERE ID = '.(int)$_GET['id']);
								echo 'ok';
							}
						} else {
							echo html_ajax_convert(NO_ADMIN_RIGHTS);
						}
						break;
					case 'delgroup':
						if(@$_SESSION['rights']['admin']['groups']['del'] OR @$_SESSION['rights']['superadmin']) {
							if((int)$_GET['gid'] > 4) {
								if($db->query('DELETE FROM '.DB_PRE.'ecp_user_groups WHERE gID = '.(int)$_GET['gid']) AND $db->query('DELETE FROM '.DB_PRE.'ecp_groups WHERE groupID = '.(int)$_GET['gid'])) {
									echo 'ok';
									$db->query('UPDATE '.DB_PRE.'ecp_user SET update_rights = 1');
								} else {
									echo 'fehler';
								}
							} else {
								echo html_ajax_convert(GROUP_NO_DEL_1234);
							}
						} else {
							echo html_ajax_convert(NO_ADMIN_RIGHTS);
						}
						break;
					case 'getdlordner':
						if(@$_SESSION['rights']['admin']['downloads']['viewfiles'] OR @$_SESSION['rights']['superadmin']) {
							if(isset($_POST['dir'])) {
								echo html_ajax_convert(get_ordner_inhalt('downloads', $_POST['dir']));
							} else {
								echo html_ajax_convert(get_ordner_inhalt('downloads', ''));
							}
						} else {
							echo html_ajax_convert(NO_ADMIN_RIGHTS);
						}
						break;
					case 'getdl':
						if(@$_SESSION['rights']['admin']['downloads']['edit'] OR @$_SESSION['rights']['superadmin']) {
							$db->query('SELECT * FROM '.DB_PRE.'ecp_downloads WHERE dID = '.(int)$_GET['id']);
							if($db->num_rows()) {
								$row = $db->fetch_assoc();
								$row['info'] = json_decode($row['info'], true);
								if(!count($row['info'])) $row['info'] = array();
								html_convert_array($row);
								echo json_encode ($row);
							} else {
								echo '{"error" : "'.html_ajax_convert(NO_ENTRIES_ID).'"}';
							}
						} else {
							echo '{"error" : "'.html_ajax_convert(NO_ADMIN_RIGHTS).'"}';
						}
						break;
					case 'getkate':
						if(@$_SESSION['rights']['admin']['downloads']['kate_edit'] OR @$_SESSION['rights']['superadmin']) {
							$db->query('SELECT * FROM '.DB_PRE.'ecp_downloads_kate WHERE kID = '.(int)$_GET['id']);
							if($db->num_rows()) {
								$row = $db->fetch_assoc();
								$row['beschreibung'] = json_decode($row['beschreibung'], true);
								if(!count($row['beschreibung'])) $row['beschreibung'] = array();
								html_convert_array($row);
								echo json_encode($row);
							} else {
								echo '{"error" : "'.html_ajax_convert(NO_ENTRIES_ID).'"}';
							}
						} else {
							echo '{"error" : "'.html_ajax_convert(NO_ADMIN_RIGHTS).'"}';
						}
						break;
					case 'del_dl':
						if(@$_SESSION['rights']['admin']['downloads']['del'] OR @$_SESSION['rights']['superadmin']) {
							$db->query('SELECT dID FROM '.DB_PRE.'ecp_downloads WHERE dID = '.(int)$_GET['id']);
							if($db->num_rows()) {
								if($db->query('DELETE FROM '.DB_PRE.'ecp_downloads WHERE dID = '.(int)$_GET['id'])) {
									echo 'ok';
								}
							} else {
								echo html_ajax_convert(NO_ENTRIES_ID);
							}
						} else {
							echo html_ajax_convert(NO_ADMIN_RIGHTS);
						}
						break;
					case 'getkate':
						if(@$_SESSION['rights']['admin']['downloads']['kate_edit'] OR @$_SESSION['rights']['superadmin']) {
							$db->query('SELECT * FROM '.DB_PRE.'ecp_downloads_kate WHERE kID = '.(int)$_GET['id']);
							if($db->num_rows()) {
								$row = $db->fetch_assoc();
								html_convert_array($row);
								$row['beschreibung'] = json_decode($row['beschreibung'], true);
								if(!count($row['beschreibung'])) $row['beschreibung'] = array();
								echo json_encode ($row);
							} else {
								echo '{"error" : "'.html_ajax_convert(NO_ENTRIES_ID).'"}';
							}
						} else {
							echo '{"error" : "'.html_ajax_convert(NO_ADMIN_RIGHTS).'"}';
						}
						break;
					case 'team_upload_form':
						if(@$_SESSION['rights']['admin']['teams']['add'] OR @$_SESSION['rights']['superadmin']) {
							$tpl = new smarty;
							$tpl->assign('sid', session_name().'='.session_id());
							$tpl->assign('url', 'ajax_checks.php?func=admin&site=team_upload');
							ob_start();
							$tpl->display(DESIGN.'/tpl/admin/upload'.(UPLOAD_METHOD == 'old' ? '_old' : '').'.html');
							$content = ob_get_contents();
							ob_end_clean();
							echo html_ajax_convert($content);
						} else {
							echo html_ajax_convert(NO_ADMIN_RIGHTS);
						}
						break;
					case 'team_upload':
						ob_start();
						if(@$_SESSION['rights']['admin']['teams']['add'] OR @$_SESSION['rights']['superadmin']) {
							if(isset($_FILES['Filedata'])) {
								$_FILES['Filedata']['name'] = str_replace(array(' '), array('_'),$_FILES['Filedata']['name']);
								$type = strtolower(substr($_FILES['Filedata']['name'], strrpos($_FILES['Filedata']['name'], '.')+1));
								if($type != 'gif' AND $type != 'png' AND $type != 'jpeg' AND $type != 'jpg') {
									$error = WRONG_FILE_TYPE;
								} else {
									if(file_exists('images/teams/'.$_FILES['Filedata']['name'])) {
										$error = FILE_EXIST;	
									} else {
										if(move_uploaded_file($_FILES['Filedata']['tmp_name'], 'images/teams/'.$_FILES['Filedata']['name'])) {
											chmod('images/teams/'.$_FILES['Filedata']['name'], CHMOD);
										} else {
											$error = 'Datei konnte nicht verschoben werden.';
										}
									}
								}
							} else {
								$error = FILE_NOT_FOUND;
							}
						} else {
							$error =  NO_ADMIN_RIGHTS;
						}
						if(UPLOAD_METHOD == 'Flash') {
							if(isset($error)) {
								echo html_ajax_convert(json_encode(array('result'=>'failed', 'error'=>$error)));
							} else {
								echo html_ajax_convert(json_encode(array('result'=>'success', 'size'=>str_replace('{datei}', $_FILES['Filedata']['name'], UPLOAD_SUCCESS))));
							}						
						} else {
							if(isset($error)) {
								echo $error.'<br /><a href="?section=admin&site=teams">Back to page</a>';
							} else {
								header1('?section=admin&site=teams');
							}	
						}
						break;
					case 'team_pics':
						if(@$_SESSION['rights']['admin']['teams']['add'] OR @$_SESSION['rights']['superadmin']) {
							echo '<option value="-1">'.NONE.'</option>';
							$pics = scan_dir('images/teams/', true);
							$endungen = array('jpg', 'jpeg', 'JPG', 'JPEG', 'gif', 'GIF', 'PNG', 'png');
							foreach($pics AS $value) {
								if(in_array(substr($value, strrpos($value, '.')+1), $endungen)) {
									echo '<option value="'.$value.'">'.$value.'</option>';
								}
							}
						} else {
							echo html_ajax_convert(NO_ADMIN_RIGHTS);
						}
						break;
					case 'team_add_member':
						if(@$_SESSION['rights']['admin']['teams']['add_member'] OR @$_SESSION['rights']['superadmin']) {
							$tpl = new smarty;
							$tpl->assign('id', (int)$_GET['id']);
							ob_start();
							$tpl->display(DESIGN.'/tpl/admin/teams_add_member.html');
							$content = ob_get_contents();
							ob_end_clean();
							echo html_ajax_convert($content);
						} else {
							echo html_ajax_convert(NO_ADMIN_RIGHTS);
						}
						break;
					case 'team_edit_member':
						if(@$_SESSION['rights']['admin']['teams']['edit_member'] OR @$_SESSION['rights']['superadmin']) {
							$row = $db->fetch_assoc('SELECT name, aufgabe, aktiv FROM '.DB_PRE.'ecp_members WHERE teamID = '.(int)$_GET['id'].' AND userID = '.(int)$_GET['uid']);
							$tpl = new smarty;
							$tpl->assign('id', (int)$_GET['id']);
							foreach($row AS $key=>$value) $tpl->assign($key, $value);
							ob_start();
							$tpl->display(DESIGN.'/tpl/admin/teams_edit_member.html');
							$content = ob_get_contents();
							ob_end_clean();
							echo html_ajax_convert($content);
						} else {
							echo html_ajax_convert(NO_ADMIN_RIGHTS);
						}
						break;
					case 'get_team':
						if(@$_SESSION['rights']['admin']['teams']['edit'] OR @$_SESSION['rights']['superadmin']) {
							$db->query('SELECT tname, tpic, grID, info, cw, joinus, fightus FROM '.DB_PRE.'ecp_teams WHERE tID = '.(int)$_GET['id']);
							if($db->num_rows()) {
								$row = $db->fetch_assoc();
								$row['info'] = json_decode($row['info'], true);
								if(!count($row['info'])) $row['info'] = array();
								html_convert_array($row);
								echo json_encode ($row);
							} else {
								echo '{"error" : "'.htmlentities(NO_ENTRIES_ID).'"}';
							}
						} else {
							echo '{"error" : "'.htmlentities(NO_ADMIN_RIGHTS).'"}';
						}
						break;
					case 'team_order':
						if(@$_SESSION['rights']['admin']['teams'] OR @$_SESSION['rights']['superadmin']) {
							$tid = (int)$_GET['id'];
							$user = explode(',', $_GET['order']);
							foreach($user AS $key => $value) {
								if((int)$value)
								$db->query('UPDATE '.DB_PRE.'ecp_members SET posi = '.(int)$key.' WHERE userID = '.(int)$value.' AND teamID = '.$tid);
							}
							echo 'ok';
						} else {
							echo html_ajax_convert(NO_ADMIN_RIGHTS);
						}
						break;
					case 'team_order_team':
						if(@$_SESSION['rights']['admin']['teams'] OR @$_SESSION['rights']['superadmin']) {
							$order = explode(',', $_GET['order']);
							$posi = 0;
							foreach($order AS $key => $value) {
								if((int)$value) {
									$db->query('UPDATE '.DB_PRE.'ecp_teams SET posi = '.$posi.' WHERE tID = '.(int)$value);
									$posi++;
								}
							}
							echo 'ok';
						} else {
							echo html_ajax_convert(NO_ADMIN_RIGHTS);
						}
						break;
					case 'server_order':
						if(@$_SESSION['rights']['admin']['server'] OR @$_SESSION['rights']['superadmin']) {
							$ids = explode(',', $_GET['order']);
							foreach($ids AS $key => $value) {
								if((int)$value)
								$db->query('UPDATE '.DB_PRE.'ecp_server SET posi = '.(int)$key.' WHERE serverID = '.(int)$value);
							}
							echo 'ok';
						} else {
							echo html_ajax_convert(NO_ADMIN_RIGHTS);
						}
						break;
					case 'ts_order':
						if(@$_SESSION['rights']['admin']['server'] OR @$_SESSION['rights']['superadmin']) {
							$ids = explode(',', $_GET['order']);
							foreach($ids AS $key => $value) {
								if((int)$value)
								$db->query('UPDATE '.DB_PRE.'ecp_teamspeak SET posi = '.(int)$key.' WHERE tsID = '.(int)$value);
							}
							echo 'ok';
						} else {
							echo html_ajax_convert(NO_ADMIN_RIGHTS);
						}
						break;						
					case 'get_server':
						if(@$_SESSION['rights']['admin']['server']['edit'] OR @$_SESSION['rights']['superadmin']) {
							$db->query('SELECT `gamename`, `gametype`, `passwort`, `aktiv`, `displaymenu`, `ip`, `port`, `queryport`, `sport`, stat FROM '.DB_PRE.'ecp_server WHERE serverID = '.(int)$_GET['id']);
							if($db->num_rows()) {
								$row = $db->fetch_assoc();
								html_convert_array($row);
								echo json_encode ($row);
							} else {
								echo '{"error" : "'.html_ajax_convert(NO_ENTRIES_ID).'"}';
							}
						} else {
							echo '{"error" : "'.html_ajax_convert(NO_ADMIN_RIGHTS).'"}';
						}
						break;
					case 'get_ts':
						if(@$_SESSION['rights']['admin']['teamspeak']['edit'] OR @$_SESSION['rights']['superadmin']) {
							$db->query('SELECT tsID, ip, port, qport, serverart FROM '.DB_PRE.'ecp_teamspeak WHERE tsID = '.(int)$_GET['id']);
							if($db->num_rows()) {
								$row = $db->fetch_assoc();
								html_convert_array($row);
								echo json_encode ($row);
							} else {
								echo '{"error" : "'.html_ajax_convert(NO_ENTRIES_ID).'"}';
							}
						} else {
							echo '{"error" : "'.html_ajax_convert(NO_ADMIN_RIGHTS).'"}';
						}
						break;	
					case 'get_games':
						if(@$_SESSION['rights']['admin']['clanwars']['games_add'] OR @$_SESSION['rights']['admin']['clanwars']['games_edit'] OR @$_SESSION['rights']['superadmin']) {
							$db->query('SELECT gamename, gameID, gameshort, icon FROM '.DB_PRE.'ecp_wars_games ORDER BY gamename');
							$games = array();
							while($row = $db->fetch_assoc()) {
								$games[] = $row;
							}
							$tpl = new smarty;
							$tpl->assign('games', $games);
							ob_start();
							$tpl->display(DESIGN.'/tpl/admin/games_overview.html');
							$content = ob_get_contents();
							ob_end_clean();
							echo html_ajax_convert($content);
						} else {
							echo html_ajax_convert(NO_ADMIN_RIGHTS);
						}
						break;
					case 'edit_games':
						if(@$_SESSION['rights']['admin']['clanwars']['games_edit'] OR @$_SESSION['rights']['superadmin']) {
							$row = $db->fetch_assoc('SELECT gamename, gameshort, icon, fightus FROM '.DB_PRE.'ecp_wars_games WHERE gameID = '.(int)$_GET['id']);
							html_convert_array($row);
							echo json_encode($row);
						} else {
							echo json_encode(array('error' => html_ajax_convert(NO_ADMIN_RIGHTS)));
						}
						break;
					case 'edit_map':
						if(@$_SESSION['rights']['admin']['clanwars']['maps_edit'] OR @$_SESSION['rights']['superadmin']) {
							$row = $db->fetch_assoc('SELECT locationname, gID FROM '.DB_PRE.'ecp_wars_locations WHERE locationID = '.(int)$_GET['id']);
							html_convert_array($row);
							echo json_encode($row);
						} else {
							echo json_encode(array('error' => html_ajax_convert(NO_ADMIN_RIGHTS)));
						}
						break;
					case 'get_maps':
						if(@$_SESSION['rights']['admin']['clanwars']['maps_add'] OR @$_SESSION['rights']['admin']['clanwars']['maps_edit'] OR @$_SESSION['rights']['superadmin']) {
							$anzahl = $db->result(DB_PRE.'ecp_wars_locations', 'COUNT(locationID)', '1');
							$limit = get_sql_limit($anzahl,20);
							$db->query('SELECT locationname, locationID, gamename, icon FROM '.DB_PRE.'ecp_wars_locations LEFT JOIN '.DB_PRE.'ecp_wars_games ON gID = gameID ORDER BY gamename, locationname LIMIT '.$limit[1].', 20');
							$maps = array();
							while($row = $db->fetch_assoc()) {
								$maps[] = $row;
							}
							$tpl = new smarty;
							$tpl->assign('maps', $maps);
							$tpl->assign('anzahl',$anzahl);
							if($limit[0] > 1)
								$tpl->assign('seiten', makepagelink_ajax('#', 'return load_content(\'maps\', \'ajax_checks.php?func=admin&site=get_maps&page={nr}\');', @$_GET['page'], $limit[0]));
							ob_start();
							$tpl->display(DESIGN.'/tpl/admin/games_maps.html');
							$content = ob_get_contents();
							ob_end_clean();
							echo html_ajax_convert($content);
						} else {
							echo html_ajax_convert(NO_ADMIN_RIGHTS);
						}
						break;
					case 'get_matchtype':
						if(@$_SESSION['rights']['admin']['clanwars']['matchtype_add'] OR @$_SESSION['rights']['admin']['clanwars']['matchtype_edit'] OR @$_SESSION['rights']['superadmin']) {
							$db->query('SELECT matchtypeID, matchtypename, fightus FROM '.DB_PRE.'ecp_wars_matchtype ORDER BY matchtypename');
							$match = array();
							while($row = $db->fetch_assoc()) {
								$match[] = $row;
							}
							$tpl = new smarty;
							$tpl->assign('matchtype', $match);
							ob_start();
							$tpl->display(DESIGN.'/tpl/admin/matchtype_overview.html');
							$content = ob_get_contents();
							ob_end_clean();
							echo html_ajax_convert($content);
						} else {
							echo html_ajax_convert(NO_ADMIN_RIGHTS);
						}
						break;
					case 'edit_matchtype':
						if(@$_SESSION['rights']['admin']['clanwars']['matchtype_edit'] OR @$_SESSION['rights']['superadmin']) {
							$row = $db->fetch_assoc('SELECT matchtypename, fightus FROM '.DB_PRE.'ecp_wars_matchtype WHERE matchtypeID = '.(int)$_GET['id']);
							html_convert_array($row);
							echo json_encode($row);
						} else {
							echo json_encode(array('error' => html_ajax_convert(NO_ADMIN_RIGHTS)));
						}
						break;
					case 'get_war_maps':
						if(@$_SESSION['rights']['admin']['clanwars'] OR @$_SESSION['rights']['superadmin']) {
							$db->query('SELECT locationID, locationname FROM '.DB_PRE.'ecp_wars_locations WHERE gID = '.(int)$_GET['id'].' ORDER BY locationname ASC');
							$array = array(array('value'=>0, 'name'=> CHOOSE, 'selected'=>false));
							while($row = $db->fetch_assoc()) {
								$array[] = array('value'=>$row['locationID'], 'name'=> $row['locationname'], 'selected'=>false);
							}
							html_convert_array($array);
							echo json_encode($array);
						} else {
							echo html_ajax_convert(NO_ADMIN_RIGHTS);
						}
						break;
					case 'get_opp':
						if(@$_SESSION['rights']['admin']['clanwars'] OR @$_SESSION['rights']['superadmin']) {
							echo json_encode($db->fetch_assoc('SELECT oppname, oppshort, homepage, country FROM '.DB_PRE.'ecp_wars_opp WHERE oppID = '.(int)$_GET['id']));
						} else {
							echo html_ajax_convert(NO_ADMIN_RIGHTS);
						}
						break;
					case 'edit_opp':
						if(@$_SESSION['rights']['admin']['clanwars']['edit_opp'] OR @$_SESSION['rights']['superadmin']) {
							if(isset($_POST['oppname'])) {
								if($db->query(sprintf('UPDATE '.DB_PRE.'ecp_wars_opp SET oppname = \'%s\', oppshort = \'%s\', homepage = \'%s\', country = \'%s\' WHERE oppID = %d', strsave($_POST['oppname']), strsave($_POST['oppshort']), strsave($_POST['homepage']), strsave($_POST['country']), (int)$_GET['id']))) {
									echo 'ok';
								}
							} else {
								$row = $db->fetch_assoc('SELECT * FROM '.DB_PRE.'ecp_wars_opp WHERE oppID = '.(int)$_GET['id']);
								if(isset($row['oppname'])) {
									$tpl = new smarty();
									$tpl->assign('id', $_GET['id']);
									$tpl->assign('countries', form_country($row['country']));
									foreach($row AS $key => $value) $tpl->assign($key, $value);
									ob_start();
									$tpl->display(DESIGN.'/tpl/admin/clanwars_opp_edit.html');
									$content = ob_get_contents();
									ob_end_clean();
									echo html_ajax_convert($content);
								} else {
									echo html_ajax_convert(NO_ENTRIES_ID);
								}
							}
						} else {
							echo html_ajax_convert(NO_ADMIN_RIGHTS);
						}
						break;
					case 'cw_upload_screens':
						if(@$_SESSION['rights']['admin']['clanwars']['screens'] OR @$_SESSION['rights']['superadmin']) {
							if(count($_FILES)) {						
								foreach($_FILES AS $key => $data) {
									if($_FILES[$key]['name'] == '') continue;
									$_FILES[$key]['name'] = str_replace(array(' '), array('_'),$_FILES[$key]['name']);
									$type = strtolower(substr($_FILES[$key]['name'], strrpos($_FILES[$key]['name'], '.')+1));
									if($type != 'jpeg' AND $type != 'jpg') {
										$error = WRONG_FILE_TYPE;
										//die(ERROR.': '.WRONG_FILE_TYPE);
									} else {
										$id = (int)$_GET['scoreid'];
										$wID = (int)$_GET['id'];
										$dataname = $wID.'_'.get_random_string(32,2).'.jpg';
										if(move_uploaded_file($_FILES[$key]['tmp_name'], 'images/screens/'.$dataname)) {
											chmod('images/screens/'.$dataname, CHMOD);
											$db->query(sprintf('INSERT INTO '.DB_PRE.'ecp_wars_screens (wID, sID, filename, uploaddate) VALUES (%d, %d, \'%s\', %d)', $wID, $id, $dataname, time()));
											$bild = getimagesize('images/screens/'.$dataname);
											if($bild[0] > CW_SCREEN_SIZE) {
												// Bildgröße ändern
												resize_picture('images/screens/'.$dataname, CW_SCREEN_SIZE, 'images/screens/'.$dataname, 100);
											}
										} else {
											$error = FILE_NOT_FOUND;
											//die('Error: 404 File not Found' );
										}
									}
								}
								if(UPLOAD_METHOD == 'Flash') {
									if(isset($error)) {
										echo html_ajax_convert(json_encode(array('result'=>'failed', 'error'=>$error)));
									} else {
										echo html_ajax_convert(json_encode(array('result'=>'success', 'size'=>str_replace('{datei}', $_FILES[$key]['name'], UPLOAD_SUCCESS))));
									}								
								} else {
									if(isset($error)) {
										echo $error.'<br /><a href="?section=admin&site=clanwars">Back to Page</a>'; 
									} else {
										header1('index.php?section=admin&site=clanwars');
									}
								}
							} else {
								$result = $db->query('SELECT scoreID, locationname FROM '.DB_PRE.'ecp_wars_scores LEFT JOIN '.DB_PRE.'ecp_wars_locations ON (lID = locationID) WHERE wID = '.(int)$_GET['id']);
								$maps = array();
								while($row = mysql_fetch_assoc($result)) {
									$db->query('SELECT screenID, filename, uploaddate FROM '.DB_PRE.'ecp_wars_screens WHERE sID = '.$row['scoreID']);
									$i = 1;
									while($sub = $db->fetch_assoc()) {
										$sub['i'] = $i++;
										$sub['uploaddate'] = date(SHORT_DATE, $sub['uploaddate']);
										$sub['size'] = goodsize(filesize('images/screens/'.$sub['filename']));
										$row['screens'][] = $sub;
									}
									$maps[] =$row;
								}
								$tpl = new smarty;
								$tpl->assign('maps', $maps);
								$tpl->assign('sid', session_name().'='.session_id());
								$tpl->assign('id', (int)$_GET['id']);
								ob_start();
								$tpl->display(DESIGN.'/tpl/admin/clanwars_upload_screens'.(UPLOAD_METHOD == 'old' ? '_old' : '').'.html');
								$content = ob_get_contents();
								ob_end_clean();
								echo html_ajax_convert($content);
							}
						} else {
							echo html_ajax_convert(NO_ADMIN_RIGHTS);
						}
						break;
					case 'cw_screen_delete':
						if(@$_SESSION['rights']['admin']['clanwars']['screens'] OR @$_SESSION['rights']['superadmin']) {
							$filename = $db->result(DB_PRE.'ecp_wars_screens', 'filename', 'screenID = '.(int)$_GET['id']);
							if(unlink('images/screens/'.$filename) AND $db->query('DELETE FROM '.DB_PRE.'ecp_wars_screens WHERE screenID = '.(int)$_GET['id'])) {
								echo 'ok';
							}
						} else {
							echo html_ajax_convert(NO_ADMIN_RIGHTS);
						}
						break;
					case 'get_wars':
						if(@$_SESSION['rights']['admin']['clanwars'] OR @$_SESSION['rights']['superadmin']) {
							$tpl = new smarty;
							$anzahl = $db->result(DB_PRE.'ecp_wars', 'COUNT(warID)', 'status = 1');
							$limit = get_sql_limit($anzahl, ADMIN_ENTRIES);
							if($limit[0] > 1)
							$tpl->assign('seiten', makepagelink_ajax('#', 'return load_cws({nr});', @$_GET['page'], $limit[0]));
							$tpl->assign('anzahl', $anzahl);
							$db->query('SELECT `warID` , '.DB_PRE.'ecp_wars.`tID` , `gID` , `datum` , `xonx` , `oID` , oppname, tname, gamename, icon
									FROM `'.DB_PRE.'ecp_wars` 
									LEFT JOIN `'.DB_PRE.'ecp_wars_games` ON ( gameID = gID ) 
									LEFT JOIN `'.DB_PRE.'ecp_teams` ON ( '.DB_PRE.'ecp_teams.tID = '.DB_PRE.'ecp_wars.tID ) 
									LEFT JOIN `'.DB_PRE.'ecp_wars_opp` ON ( oppID = oID ) 
									WHERE status = 1
									GROUP BY warID
									ORDER BY datum DESC 
									LIMIT '.$limit[1].' ,'.ADMIN_ENTRIES);
							$wars = array();
							while($row = $db->fetch_assoc()) {
								$row['datum'] = date(SHORT_DATE, $row['datum']);
								$wars[] = $row;
							}
							$tpl->assign('clanwars', $wars);
							ob_start();
							$tpl->display(DESIGN.'/tpl/admin/clanwars.html');
							$content = ob_get_contents();
							ob_end_clean();
							echo html_ajax_convert($content);
						} else {
							echo html_ajax_convert(NO_ADMIN_RIGHTS);
						}
						break;
					case 'clanwar_delete':
						if(@$_SESSION['rights']['admin']['clanwars']['del'] OR @$_SESSION['rights']['superadmin']) {
							if(clanwar_delete((int)$_GET['id'])) {
								echo 'ok';
							}
						} else {
							echo html_ajax_convert(NO_ADMIN_RIGHTS);
						}
						break;
					case 'game_delete':
						if(@$_SESSION['rights']['admin']['games']['del'] OR @$_SESSION['rights']['superadmin']) {
							$result = $db->query('SELECT warID FROM '.DB_PRE.'ecp_wars WHERE gID = '.(int)$_GET['id']);
							while($row = mysql_fetch_assoc($result)) {
								clanwar_delete($row['warID']);
							}
							if($db->query('DELETE FROM '.DB_PRE.'ecp_wars_games WHERE gameID = '.(int)$_GET['id'])) {
								if($db->query('DELETE FROM '.DB_PRE.'ecp_wars_locations WHERE gID = '.(int)$_GET['id']));
								echo 'ok';
							}
						} else {
							echo html_ajax_convert(NO_ADMIN_RIGHTS);
						}
						break;
					case 'map_delete':
						if(@$_SESSION['rights']['admin']['maps']['del'] OR @$_SESSION['rights']['superadmin']) {
							$result = $db->query('SELECT DISTINCT(wID) FROM '.DB_PRE.'ecp_wars_scores WHERE lID = '.(int)$_GET['id']);
							while($row = mysql_fetch_assoc($result)) {
								clanwar_delete($row['wID']);
							}
							if($db->query('DELETE FROM '.DB_PRE.'ecp_wars_locations WHERE locationID = '.(int)$_GET['id'])) {
								echo 'ok';
							}
						} else {
							echo html_ajax_convert(NO_ADMIN_RIGHTS);
						}
						break;
					case 'matchtype_delete':
						if(@$_SESSION['rights']['admin']['matchtype']['del'] OR @$_SESSION['rights']['superadmin']) {
							$result = $db->query('SELECT warID FROM '.DB_PRE.'ecp_wars WHERE mID = '.(int)$_GET['id']);
							while($row = mysql_fetch_assoc($result)) {
								clanwar_delete($row['warID']);
							}
							if($db->query('DELETE FROM '.DB_PRE.'ecp_wars_matchtype WHERE matchtypeID = '.(int)$_GET['id'])) {
								echo 'ok';
							}
						} else {
							echo html_ajax_convert(NO_ADMIN_RIGHTS);
						}
						break;
					case 'topics_overview':
						if(@$_SESSION['rights']['admin']['topics'] OR @$_SESSION['rights']['superadmin']) {
							$topics = array();
							$db->query('SELECT `tID`, `topicname`, `beschreibung`, `topicbild` FROM '.DB_PRE.'ecp_topics ORDER BY topicname ASC');
							while($row = $db->fetch_assoc()) {
								$topics[] = $row;
							}
							$tpl = new Smarty();
							$tpl->assign('topics', $topics);
							ob_start();
							$tpl->display(DESIGN.'/tpl/admin/topics_overview.html');
							$content = ob_get_contents();
							ob_end_clean();
							echo html_ajax_convert($content);
						} else {
							echo htmlentities(NO_ADMIN_RIGHTS);
						}
						break;
					case 'awards_overview':
						if(@$_SESSION['rights']['admin']['awards'] OR @$_SESSION['rights']['superadmin']) {
							$awards = array();
							$db->query('SELECT `awardID`, `eventname`, `eventdatum`, `url`, `platz` FROM '.DB_PRE.'ecp_awards ORDER BY eventdatum DESC');
							while($row = $db->fetch_assoc()) {
								$row['eventdatum'] = date('d.m.Y', $row['eventdatum']);
								$awards[] = $row;
							}
							$tpl = new Smarty();
							$tpl->assign('awards', $awards);
							ob_start();
							$tpl->display(DESIGN.'/tpl/admin/awards_overview.html');
							$content = ob_get_contents();
							ob_end_clean();
							echo html_ajax_convert($content);
						} else {
							echo html_ajax_convert(NO_ADMIN_RIGHTS);
						}
						break;
					case 'get_topic':
						if(@$_SESSION['rights']['admin']['topics']['edit'] OR @$_SESSION['rights']['superadmin']) {
							$row = $db->fetch_assoc('SELECT `tID`, `topicname`, `beschreibung`, `topicbild` FROM '.DB_PRE.'ecp_topics WHERE tID = '.(int)$_GET['id']);
							html_convert_array($row);
							echo json_encode($row);
						} else {
							echo json_encode(array('error', html_ajax_convert(NO_ADMIN_RIGHTS)));
						}
						break;
					case 'get_award':
						if(@$_SESSION['rights']['admin']['awards']['edit'] OR @$_SESSION['rights']['superadmin']) {
							$row = $db->fetch_assoc('SELECT * FROM '.DB_PRE.'ecp_awards WHERE awardID = '.(int)$_GET['id']);
							$row['eventdatum'] =  date('Y-m-d H:i:s', $row['eventdatum']);
							$row['bericht'] = json_decode($row['bericht'], true);
							if(!count($row['bericht'])) $row['bericht'] = array();
							$own = explode(',', $row['spieler']);
							foreach($own AS $value) {
								if($value) {
									@$search .= ' OR ID = '.(int)$value;
								}
							}
							$db->query('SELECT username FROM '.DB_PRE.'ecp_user WHERE ID = 0'.@$search.' ORDER BY username ASC');
							while($row1 = $db->fetch_assoc()) {
								html_convert_array($row1);
								@$players .= htmlspecialchars($row1['username']).', ';
							}
							$row['spieler']  = substr(@$players, 0, strlen(@$players)-2);
							echo json_encode($row);
						} else {
							echo json_encode(array('error', html_ajax_convert(NO_ADMIN_RIGHTS)));
						}
						break;
					case 'fightus_view':
						if(@$_SESSION['rights']['admin']['fightus']['view'] OR @$_SESSION['rights']['superadmin']) {
							$row = $db->fetch_assoc('SELECT tname, gamename, icon, matchtypename, a.clanname, a.homepage, a.email, a.icq, a.skype, a.msn, `datum`, `wardatum`, `serverip`, a.info, `bearbeitet`, `vonID`, `IP`, username FROM '.DB_PRE.'ecp_fightus as a LEFT JOIN '.DB_PRE.'ecp_teams ON (teamID = tID) LEFT JOIN '.DB_PRE.'ecp_wars_games ON (gID=gameID) LEFT JOIN '.DB_PRE.'ecp_wars_matchtype ON (mID= matchtypeID) LEFT JOIN '.DB_PRE.'ecp_user ON (ID=vonID) WHERE fightusID = '.(int)$_GET['id']);
							if($row['clanname']) {
								$tpl = new smarty;
								$tpl->assign('id', (int)$_GET['id']);
								$row['datum'] = date(LONG_DATE, $row['datum']);
								$row['wardatum'] = date(LONG_DATE, $row['wardatum']);
								foreach($row AS $key => $value)
								$tpl->assign($key, $value);
								ob_start();
								$tpl->display(DESIGN.'/tpl/admin/fightus_view.html');
								$content = ob_get_contents();
								ob_end_clean();
								echo html_ajax_convert($content);
							} else {
								echo html_ajax_convert(NO_ENTRIES_ID);
							}
						} else {
							echo htmlentities(NO_ADMIN_RIGHTS);
						}
						break;
					case 'fightus_finish':
						if(@$_SESSION['rights']['admin']['fightus']['close'] OR @$_SESSION['rights']['superadmin']) {
							if($db->query('UPDATE '.DB_PRE.'ecp_fightus SET vonID = '.$_SESSION['userID'].', bearbeitet = 1 WHERE fightusID = '.(int)$_GET['id'])) {
								echo html_ajax_convert('<a href="?section=user&id='.$_SESSION['userID'].'">'.$_SESSION['username'].'</a>');
							}
						} else {
							echo html_ajax_convert(NO_ADMIN_RIGHTS);
						}
						break;
					case 'joinus_view':
						if(@$_SESSION['rights']['admin']['joinus']['view'] OR @$_SESSION['rights']['superadmin']) {
							$row = $db->fetch_assoc('SELECT tname, `joinID`, `name`, b.username, b.email, b.icq, b.msn, `age`, b.country, `teamID`, `comment`, `IP`, `datum`, `closed`, `closedby`, a.username as closedby_username FROM '.DB_PRE.'ecp_joinus as b LEFT JOIN '.DB_PRE.'ecp_teams ON (teamID = tID) LEFT JOIN '.DB_PRE.'ecp_user as a ON (ID=closedby) WHERE joinID = '.(int)$_GET['id']);
							if($row['name']) {
								$tpl = new smarty;
								$tpl->assign('id', (int)$_GET['id']);
								$row['datum'] = date(LONG_DATE, $row['datum']);
								$row['countryname'] = $countries[$row['country']];
								$row['comment'] = stripslashes($row['comment']);
								foreach($row AS $key => $value)
								$tpl->assign($key, $value);
								ob_start();
								$tpl->display(DESIGN.'/tpl/admin/joinus_view.html');
								$content = ob_get_contents();
								ob_end_clean();
								echo html_ajax_convert($content);
							} else {
								echo html_ajax_convert(NO_ENTRIES_ID);
							}
						} else {
							echo html_ajax_convert(NO_ADMIN_RIGHTS);
						}
						break;
					case 'joinus_finish':
						if(@$_SESSION['rights']['admin']['joinus']['close'] OR @$_SESSION['rights']['superadmin']) {
							if($db->query('UPDATE '.DB_PRE.'ecp_joinus SET closedby = '.$_SESSION['userID'].', closed = 1 WHERE joinID = '.(int)$_GET['id'])) {
								echo html_ajax_convert('<a href="?section=user&id='.$_SESSION['userID'].'">'.$_SESSION['username'].'</a>');
							}
						} else {
							echo html_ajax_convert(NO_ADMIN_RIGHTS);
						}
						break;
					case 'getsurveys':
						if(@$_SESSION['rights']['admin']['survey'] OR @$_SESSION['rights']['superadmin']) {
							$anzahl = $db->result(DB_PRE.'ecp_survey', 'COUNT(surveyID)', '1');
							if($anzahl) {
								$limits = get_sql_limit($anzahl, LIMIT_SURVEY);
								$db->query('SELECT `surveyID`, `start`, `ende`, `frage` FROM `'.DB_PRE.'ecp_survey` ORDER BY ende DESC LIMIT '.$limits[1].', '.LIMIT_SURVEY);
								$umfrage = array();
								while($row = $db->fetch_assoc()) {
									if($row['start'] > time()) {
										$row['status']	= PLANNED;
										$row['closed'] = 1;
									} elseif ($row['ende'] < time()) {
										$row['status'] = CLOSED;
										$row['closed'] = 1;
									} else {
										$row['status'] = RUN;
									}
									$row['start'] = date(LONG_DATE, $row['start']);
									$row['ende'] = date(LONG_DATE, $row['ende']);
									$umfrage[] = $row;
								}
							}
							$tpl = new smarty;
							$tpl->assign('anzahl', $anzahl);
							$tpl->assign('umfrage', @$umfrage);
							$tpl->assign('pages', @$limits[0]);
							ob_start();
							$tpl->display(DESIGN.'/tpl/admin/survey_overview.html');
							$content = ob_get_contents();
							ob_end_clean();
							echo html_ajax_convert($content);
						} else {
							echo html_ajax_convert(NO_ADMIN_RIGHTS);
						}
						break;
					case 'closesurvey':
						if(@$_SESSION['rights']['admin']['survey']['close'] OR @$_SESSION['rights']['superadmin']) {
							if($db->result(DB_PRE.'ecp_survey', 'start', 'surveyID = '.(int)$_GET['id']) > time()) {
								echo SURVEY_NOT_STARTED;
							} else  {
								if($db->query('UPDATE '.DB_PRE.'ecp_survey SET ende = '.(time()-1).' WHERE surveyID = '.(int)$_GET['id'])) {
									echo 'ok';
								}
							}
						} else {
							echo html_ajax_convert(NO_ADMIN_RIGHTS);
						}
						break;
					case 'survey_delete':
						if(@$_SESSION['rights']['admin']['survey']['del'] OR @$_SESSION['rights']['superadmin']) {
							if($db->query('DELETE FROM '.DB_PRE.'ecp_survey WHERE surveyID = '.(int)$_GET['id'])) {
								if($db->query('DELETE FROM '.DB_PRE.'ecp_survey_answers WHERE sID = '.(int)$_GET['id'])) {
									$db->query('DELETE FROM '.DB_PRE.'ecp_survey_votes WHERE surID = '.(int)$_GET['id']);
									$db->query('DELETE FROM '.DB_PRE.'ecp_comments WHERE bereich = "survey" AND subID = '.(int)$_GET['id']);
									echo 'ok';
								}
							}
						} else {
							echo html_ajax_convert(NO_ADMIN_RIGHTS);
						}
						break;
					case 'survey_answer_delete':
						if(@$_SESSION['rights']['admin']['survey']['edit'] OR @$_SESSION['rights']['superadmin']) {
							if($db->query('DELETE FROM '.DB_PRE.'ecp_survey_answers WHERE sID  = '.(int)$_GET['surid'].' AND answerID = '.(int)$_GET['id'])) {
								echo 'ok';
							}
						} else {
							echo html_ajax_convert(NO_ADMIN_RIGHTS);
						}
						break;
					case 'get_survey':
						if(@$_SESSION['rights']['admin']['survey']['edit'] OR @$_SESSION['rights']['superadmin']) {
							$row = $db->fetch_assoc('SELECT * FROM '.DB_PRE.'ecp_survey WHERE surveyID = '.(int)$_GET['id']);
							$row['start'] =  date('Y-m-d H:i:s', $row['start']);
							$row['ende'] =  date('Y-m-d H:i:s', $row['ende']);
							$db->query('SELECT * FROM '.DB_PRE.'ecp_survey_answers WHERE sID ='.(int)$_GET['id'].' ORDER BY answerID ASC');
							$antworten = array();
							while($sub = $db->fetch_assoc()) {
								html_convert_array($sub);
								$antworten[] = $sub;
							}
							$row['answers'] = $antworten;
							echo json_encode($row);
						} else {
							echo json_encode(array('error', htmlentities(NO_ADMIN_RIGHTS)));
						}
						break;
					case 'forum_order':
						if(@$_SESSION['rights']['admin']['forum'] OR @$_SESSION['rights']['superadmin']) {
							foreach($_POST AS $key=>$value) {
								$db->query('UPDATE '.DB_PRE.'ecp_forum_boards SET posi = '.(int)$key.' WHERE boardID = '.(int)$value);
							}
						} else {
							echo html_ajax_convert(NO_ADMIN_RIGHTS);
						}
						break;
					case 'set_kontodaten':
						if(@$_SESSION['rights']['admin']['clankasse']['konto'] OR @$_SESSION['rights']['superadmin']) {
							if($db->query(sprintf('UPDATE '.DB_PRE.'ecp_clankasse SET `empfaenger` = \'%s\', `kontonummer` = \'%s\', `bankleitzahl` = \'%s\', `IBAN` = \'%s\', `SWIFT` = \'%s\', `institut` = \'%s\', `kontostand` = %f', strsave($_POST['empfaenger']), strsave($_POST['kontonummer']), strsave($_POST['bankleitzahl']), strsave($_POST['IBAN']), strsave($_POST['SWIFT']), strsave($_POST['institut']), (float)str_replace(',', '.', $_POST['kontostand'])))) {
								echo 'ok';
							}
						} else {
							echo html_ajax_convert(NO_ADMIN_RIGHTS);
						}
						break;
					case 'add_auto':
						if(@$_SESSION['rights']['admin']['clankasse']['add_auto'] OR @$_SESSION['rights']['superadmin']) {
							$nextbuch = strtotime((int)$_POST['tagmonat']. '. '.date('M'));
							if($nextbuch < time()) $nextbuch = strtotime((int)$_POST['tagmonat']. '. '.date('M', strtotime('next month')));
							if($db->query(sprintf('INSERT INTO '.DB_PRE.'ecp_clankasse_auto (`verwendung`, `intervall`, `betrag`, `nextbuch`, `tagmonat`) VALUES (\'%s\', %d, %f, %d, %d)', strsave($_POST['verwendung']), (int)$_POST['intervall'], (float)str_replace(',', '.', $_POST['betrag']), $nextbuch, (int)$_POST['tagmonat']))) {
								echo 'ok';
							}
						} else {
							echo html_ajax_convert(NO_ADMIN_RIGHTS);
						}
						break;
					case 'get_auto':
						if(@$_SESSION['rights']['admin']['clankasse']['add_auto'] OR @$_SESSION['rights']['admin']['clankasse']['edit_auto'] OR @$_SESSION['rights']['superadmin']) {
							$tpl = new smarty;
							$db->query('SELECT `ID`, `verwendung`, `intervall`, `betrag`, `nextbuch` FROM '.DB_PRE.'ecp_clankasse_auto');
							$auto = array();
							while($row = $db->fetch_assoc()) {
								$row['nextbuch'] = date(LONG_DATE, $row['nextbuch']);
								$row['betrag'] = number_format($row['betrag'], 2, ',','.');
								$auto[] = $row;
							}
							$tpl->assign('auto', $auto);
							ob_start();
							$tpl->display(DESIGN.'/tpl/admin/clankasse_auto_overview.html');
							$content = ob_get_contents();
							ob_end_clean();
							echo html_ajax_convert($content);
						} else {
							echo html_ajax_convert(NO_ADMIN_RIGHTS);
						}
						break;
					case 'get_autobuch':
						if(@$_SESSION['rights']['admin']['clankasse']['edit_auto'] OR @$_SESSION['rights']['superadmin']) {
							$row = $db->fetch_assoc('SELECT * FROM '.DB_PRE.'ecp_clankasse_auto WHERE ID = '.(int)$_GET['id']);
							html_convert_array($row);
							echo json_encode($row);
						} else {
							echo json_encode(array('error', htmlentities(NO_ADMIN_RIGHTS)));
						}
						break;
					case 'edit_auto':
						if(@$_SESSION['rights']['admin']['clankasse']['edit_auto'] OR @$_SESSION['rights']['superadmin']) {
							if($db->query(sprintf('UPDATE '.DB_PRE.'ecp_clankasse_auto SET `verwendung` = \'%s\', `intervall` = %d, `betrag` = %f, `tagmonat` = %d WHERE ID = %d', strsave($_POST['verwendung']), (int)$_POST['intervall'], (float)str_replace(',', '.', $_POST['betrag']), (int)$_POST['tagmonat'], (int)$_GET['id']))) {
								echo 'ok';
							}
						} else {
							echo html_ajax_convert(NO_ADMIN_RIGHTS);
						}
						break;
					case 'del_auto':
						if(@$_SESSION['rights']['admin']['clankasse']['del_auto'] OR @$_SESSION['rights']['superadmin']) {
							if($db->query('DELETE FROM '.DB_PRE.'ecp_clankasse_auto WHERE ID = '.(int)$_GET['id'])) {
								echo 'ok';
							}
						} else {
							echo html_ajax_convert(NO_ADMIN_RIGHTS);
						}
						break;
					case 'add_buch':
						if(@$_SESSION['rights']['admin']['clankasse']['add_buch'] OR @$_SESSION['rights']['superadmin']) {
							if($_POST['art'] == '+' AND $_POST['userID'] != '') {
								$userid = @$db->result(DB_PRE.'ecp_user', 'ID', 'username = \''.strsave($_POST['userID']).'\'');
								if(!$userid) { $db->close();  die(USER_NOT_FOUND); }
								$_POST['verwendung_buch'] == '' ? $_POST['verwendung_buch'] = $_POST['verwendungby'] : '';
							}
							$_POST['art'] == '+' ? '' : $_POST['art'] = '-';
							if($db->query(sprintf('INSERT INTO '.DB_PRE.'ecp_clankasse_transaktion (`geld`, `verwendung`, `datum`, `userID`, `vonuser`) VALUES (%f, \'%s\', %d, %d, %d)', (float)$_POST['art'].str_replace(',', '.', $_POST['betrag_buch']), strsave($_POST['verwendung_buch']), time(), $_SESSION['userID'], @$userid))) {
								$db->query('UPDATE '.DB_PRE.'ecp_clankasse SET kontostand = kontostand '.$_POST['art'].(float)str_replace(',', '.', $_POST['betrag_buch']));
								echo 'ok';
							}
						} else {
							echo html_ajax_convert(NO_ADMIN_RIGHTS);
						}
						break;
					case 'get_buch':
						if(@$_SESSION['rights']['admin']['clankasse'] OR @$_SESSION['rights']['superadmin']) {
							$tpl = new smarty;
							$db->query('SELECT a.*, b.username FROM '.DB_PRE.'ecp_clankasse_transaktion as a LEFT JOIN '.DB_PRE.'ecp_user as b ON b.ID = vonuser ORDER BY datum DESC');
							$buchung = array();
							while($row = $db->fetch_assoc()) {
								$row['datum'] = date(LONG_DATE, $row['datum']);
								if($row['vonuser']) $row['verwendung'] .= ' '.FROM.' '.$row['username'];
								$row['geld'] = number_format($row['geld'], 2, ',','.');
								$buchung[] = $row;
							}
							$tpl->assign('buchung', $buchung);
							ob_start();
							$tpl->display(DESIGN.'/tpl/admin/clankasse_trans_overview.html');
							$content = ob_get_contents();
							ob_end_clean();
							echo html_ajax_convert($content);
						} else {
							echo html_ajax_convert(NO_ADMIN_RIGHTS);
						}
						break;
					case 'get_kontostand':
						if(@$_SESSION['rights']['admin']['clankasse'] OR @$_SESSION['rights']['superadmin']) {
							echo number_format($db->result(DB_PRE.'ecp_clankasse', 'kontostand', '1'), '2', ',', '');
						} else {
							echo html_ajax_convert(NO_ADMIN_RIGHTS);
						}
						break;
					case 'del_buch':
						if(@$_SESSION['rights']['admin']['clankasse']['del_buch'] OR @$_SESSION['rights']['superadmin']) {
							$beitrag = @$db->result(DB_PRE.'ecp_clankasse_transaktion', 'geld', 'ID = '.(int)$_GET['id']);
							if($db->query('DELETE FROM '.DB_PRE.'ecp_clankasse_transaktion WHERE ID = '.(int)$_GET['id'])) {
								if(isset($_GET['with'])) $db->query('UPDATE '.DB_PRE.'ecp_clankasse SET kontostand = kontostand - '.(float)$beitrag);
								echo 'ok';
							}
						} else {
							echo html_ajax_convert(NO_ADMIN_RIGHTS);
						}
						break;
					case 'add_user_buch':
						if(@$_SESSION['rights']['admin']['clankasse']['add_user'] OR @$_SESSION['rights']['superadmin']) {
							$userid = @$db->result(DB_PRE.'ecp_user', 'ID', 'username = \''.strsave(htmlspecialchars($_POST['username'])).'\'');
							if(!$userid) { $db->close(); die(USER_NOT_FOUND); }
							if($db->query(sprintf('INSERT INTO '.DB_PRE.'ecp_clankasse_member (`monatgeld`, `verwendung`, `userID`) VALUES (%f, \'%s\', %d)', (float)str_replace(',', '.', $_POST['betrag_user']), strsave($_POST['userverwendung']), @$userid))) {
								echo 'ok';
							}
						} else {
							echo html_ajax_convert(NO_ADMIN_RIGHTS);
						}
						break;
					case 'edit_userbuch':
						if(@$_SESSION['rights']['admin']['clankasse']['edit_user'] OR @$_SESSION['rights']['superadmin']) {
							if($db->query(sprintf('UPDATE '.DB_PRE.'ecp_clankasse_member SET `verwendung` = \'%s\', `monatgeld` = %f WHERE userID = %d', strsave($_POST['userverwendung']), (float)str_replace(',', '.', $_POST['betrag_user']), (int)$_GET['id']))) {
								echo 'ok';
							}
						} else {
							echo html_ajax_convert(NO_ADMIN_RIGHTS);
						}
						break;
					case 'get_userbuch':
						if(@$_SESSION['rights']['admin']['clankasse']['edit_user'] OR @$_SESSION['rights']['superadmin']) {
							$row = $db->fetch_assoc('SELECT username, userID, verwendung, monatgeld FROM '.DB_PRE.'ecp_clankasse_member LEFT JOIN '.DB_PRE.'ecp_user ON userID = ID WHERE userID = '.(int)$_GET['id']);
							html_convert_array($row);
							echo json_encode($row);
						} else {
							echo json_encode(array('error', htmlentities(NO_ADMIN_RIGHTS)));
						}
						break;
					case 'get_clank_user':
						if(@$_SESSION['rights']['admin']['clankasse'] OR @$_SESSION['rights']['superadmin']) {
							$tpl = new smarty;
							$db->query('SELECT username, userID, verwendung, monatgeld FROM '.DB_PRE.'ecp_clankasse_member LEFT JOIN '.DB_PRE.'ecp_user ON userID = ID ORDER BY username ASC');
							$user = array();
							while($row = $db->fetch_assoc()) {
								$row['geld'] = number_format($row['monatgeld'], 2, ',','.');
								$user[] = $row;
							}
							$tpl->assign('user', $user);
							ob_start();
							$tpl->display(DESIGN.'/tpl/admin/clankasse_user_overview.html');
							$content = ob_get_contents();
							ob_end_clean();
							echo html_ajax_convert($content);
						} else {
							echo html_ajax_convert(NO_ADMIN_RIGHTS);
						}
						break;
					case 'del_user_buch':
						if(@$_SESSION['rights']['admin']['clankasse']['del_user'] OR @$_SESSION['rights']['superadmin']) {
							if($db->query('DELETE FROM '.DB_PRE.'ecp_clankasse_member WHERE userID = '.(int)$_GET['id'])) {
								echo 'ok';
							}
						} else {
							echo html_ajax_convert(NO_ADMIN_RIGHTS);
						}
						break;
					case 'buch_monat_user':
						if(@$_SESSION['rights']['admin']['clankasse']['add_buch'] OR @$_SESSION['rights']['superadmin']) {
							$geld = @$db->result(DB_PRE.'ecp_clankasse_member', 'monatgeld', 'userID = '.(int)$_GET['id']);
							if($geld) {
								$monat = explode('/', $_GET['monat']);
								$monat = $monat[1].'/'.$monat[0];
								if($db->query(sprintf('INSERT INTO '.DB_PRE.'ecp_clankasse_transaktion (geld,verwendung,datum,userID,vonuser) VALUES (%f, \'%s\', %d, %d, %d)',$geld, strsave($monat), time(), $_SESSION['userID'], (int)$_GET['id'])) AND $db->query('UPDATE '.DB_PRE.'ecp_clankasse SET kontostand = kontostand + '.$geld)) {
									$monate = array();
									$monat = (int)$_GET['startm'];
									$jahr = (int)$_GET['startj'];
									$tpl = new Smarty();
									if($monat > 6) {
										$tpl->assign('vmonat', $monat-6);
										$tpl->assign('vjahr', $jahr);
									} else {
										$diff = $monat - 6;
										$tpl->assign('vmonat', 12+$diff);
										$tpl->assign('vjahr', $jahr-1);
									}
									$tpl->assign('startm', $monat);
									$tpl->assign('startj', $jahr);
									for($i = 0; $i<6; $i++) {
										$monate[$jahr.'_'.$monat]['datum'] = $monat++.'/'.$jahr;
										if($monat == 13) {
											$monat = 1; $jahr++;
										}
									}
									$tpl->assign('nmonat', $monat);
									$tpl->assign('njahr', $jahr);
									$db->query('SELECT username, userID, verwendung, monatgeld FROM '.DB_PRE.'ecp_clankasse_member LEFT JOIN '.DB_PRE.'ecp_user ON userID = ID ORDER BY username ASC');
									$user = array();
									while($row = $db->fetch_assoc()) {
										$row['geld'] = number_format($row['monatgeld'], 2, ',','.');
										$user[] = $row;
									}
									$tpl->assign('user', $user);
									$db->query('SELECT geld, verwendung, vonuser FROM '.DB_PRE.'ecp_clankasse_transaktion WHERE vonuser != 0 AND verwendung LIKE "%/%"');
									while($row = $db->fetch_assoc()) {
										$monat = explode('/', $row['verwendung']);
										if(isset($monate[$monat[0].'_'.$monat[1]])) {
											$monate[$monat[0].'_'.$monat[1]][$row['vonuser']]['geld'] = $row['geld'];
										}
									}
									$tpl->assign('user', $user);
									$tpl->assign('monate', $monate);
									ob_start();
									$tpl->display(DESIGN.'/tpl/clankasse/overview.html');
									$content = ob_get_contents();
									ob_end_clean();
									echo html_ajax_convert($content);
								}
							} else {
								echo html_ajax_convert(USER_NOT_FOUND);
							}

						} else {
							echo html_ajax_convert(NO_ADMIN_RIGHTS);
						}
						break;
					case 'order_menu':
						if(@$_SESSION['rights']['admin']['menu'] OR @$_SESSION['rights']['superadmin']) {
							$right = false;
							foreach($_POST AS $key => $value) {
								if(@$right) {
									$db->query('UPDATE '.DB_PRE.'ecp_menu SET `hposi` = "r", `vposi` = '.(int)$value.' WHERE menuID = '.(int)$key);
								} else {
									if($key == 'right') {
										$right=true; continue;
									} else {
										$db->query('UPDATE '.DB_PRE.'ecp_menu SET `hposi` = "l", `vposi` = '.(int)$value.' WHERE menuID = '.(int)$key);
									}
								}
							}
						} else {
							echo html_ajax_convert(NO_ADMIN_RIGHTS);
						}
						break;
					case 'del_menu':
						if(@$_SESSION['rights']['admin']['menu']['del'] OR @$_SESSION['rights']['superadmin']) {
							if($db->query('DELETE FROM '.DB_PRE.'ecp_menu WHERE menuID = '.(int)$_GET['id'])) {
								echo 'ok';
							}
						} else {
							echo htmlentities(NO_ADMIN_RIGHTS);
						}
						break;
					case 'del_menu_link':
						if(@$_SESSION['rights']['admin']['menu']['del_link'] OR @$_SESSION['rights']['superadmin']) {
							if($db->query('DELETE FROM '.DB_PRE.'ecp_menu_links WHERE linkID = '.(int)$_GET['id'])) {
								echo 'ok';
							}
						} else {
							echo htmlentities(NO_ADMIN_RIGHTS);
						}
						break;
					case 'get_menu_link':
						if(@$_SESSION['rights']['admin']['menu'] OR @$_SESSION['rights']['superadmin']) {
							$row = $db->fetch_assoc('SELECT * FROM '.DB_PRE.'ecp_menu_links WHERE linkID = '.(int)$_GET['id']);
							$row['ersetze'] = html_ajax_convert($row['ersetze']);
							echo json_encode($row);
						} else {
							echo html_ajax_convert(NO_ADMIN_RIGHTS);
						}
						break;
					case 'add_menu_link':
						if(@$_SESSION['rights']['admin']['menu']['add_link'] OR @$_SESSION['rights']['superadmin']) {
							if($_POST['suche'] == '' OR $_POST['ersetze'] == '') {
								echo NOT_NEED_ALL_INPUTS;
							} else {
								if($db->query(sprintf('INSERT INTO '.DB_PRE.'ecp_menu_links (suche, ersetze, sprache) VALUES (\'%s\', \'%s\', \'%s\')', strsave($_POST['suche']), strsave($_POST['ersetze']), strsave($_GET['lang'])))) {
									echo 'ok';
								}
							}
						} else {
							echo html_ajax_convert(NO_ADMIN_RIGHTS);
						}
						break;
					case 'edit_menu_link':
						if(@$_SESSION['rights']['admin']['menu']['edit_link'] OR @$_SESSION['rights']['superadmin']) {
							if($_POST['suche'] == '' OR $_POST['ersetze'] == '') {
								echo NOT_NEED_ALL_INPUTS;
							} else {
								if($db->query(sprintf('UPDATE '.DB_PRE.'ecp_menu_links SET suche = \'%s\', ersetze = \'%s\' WHERE linkID = %d', strsave($_POST['suche']), strsave($_POST['ersetze']), (int)$_GET['id']))) {
									echo 'ok';
								}
							}
						} else {
							echo html_ajax_convert(NO_ADMIN_RIGHTS);
						}
						break;
					case 'get_menu_links':
						if(@$_SESSION['rights']['admin']['menu'] OR @$_SESSION['rights']['superadmin']) {
							$db->query('SELECT suche, ersetze, linkID FROM '.DB_PRE.'ecp_menu_links WHERE sprache = "'.strsave($_GET['lang']).'" ORDER BY suche ASC');
							$links = array();
							while($row = $db->fetch_assoc()) {
								$row['ersetze'] = htmlspecialchars($row['ersetze']);
								$links[] =$row;
							}
							ob_start();
							$tpls = new Smarty;
							$tpls->assign('lang', $_GET['lang']);
							$tpls->assign('links', $links);
							ob_start();
							$tpls->display(DESIGN.'/tpl/admin/menu_links.html');
							$content = ob_get_contents();
							ob_end_clean();
							echo html_ajax_convert($content);
						} else {
							echo htmlentities(NO_ADMIN_RIGHTS);
						}
						break;
					case 'get_link':
						if(@$_SESSION['rights']['admin']['links']['edit'] OR @$_SESSION['rights']['superadmin']) {
							$db->query('SELECT * FROM '.DB_PRE.'ecp_links WHERE linkID = '.(int)$_GET['id']);
							if($db->num_rows()) {
								$row = $db->fetch_assoc();
								foreach($row AS $key=>$value) {
									if(is_string($value))
									$row[$key] = html_ajax_convert($value);
								}
								echo json_encode ($row);
							} else {
								echo '{"error" : "'.html_ajax_convert(NO_ENTRIES_ID).'"}';
							}
						} else {
							echo '{"error" : "'.html_ajax_convert(NO_ADMIN_RIGHTS).'"}';
						}
						break;
					case 'del_link':
						if(@$_SESSION['rights']['admin']['links']['del'] OR @$_SESSION['rights']['superadmin']) {
							if($db->query('DELETE FROM '.DB_PRE.'ecp_links WHERE linkID = '.(int)$_GET['id'])) {
								echo 'ok';
							}
						} else {
							echo htmlentities(NO_ADMIN_RIGHTS);
						}
						break;
					case 'get_links':
						if(@$_SESSION['rights']['admin']['links'] OR @$_SESSION['rights']['superadmin']) {
							$db->query('SELECT * FROM '.DB_PRE.'ecp_links ORDER BY name ASC');
							$links = array();
							while($row = $db->fetch_assoc()) {
								$links[] =$row;
							}
							ob_start();
							$tpls = new Smarty;
							$tpls->assign('links', $links);
							ob_start();
							$tpls->display(DESIGN.'/tpl/admin/links_overview.html');
							$content = ob_get_contents();
							ob_end_clean();
							echo html_ajax_convert($content);
						} else {
							echo htmlentities(NO_ADMIN_RIGHTS);
						}
						break;
					case 'gallery_create_folder':
						if(@$_SESSION['rights']['admin']['gallery']['create_folder'] OR @$_SESSION['rights']['superadmin']) {
							umask(0);
							IF(mkdir('images/gallery/'.$_POST['dirname'],CHMOD)) {
								umask(0);
								IF(mkdir('images/gallery/'.$_POST['dirname'].'/thumbs',CHMOD)) {
									echo 'ok';
								} else {
									rmdir('images/gallery/'.$_POST['dirname']);
									echo html_ajax_convert(GALLERY_MAKE_MANUELL);
								}
							}
						} else {
							echo htmlentities(NO_ADMIN_RIGHTS);
						}
						break;
					case 'get_gallery_kate':
						if(@$_SESSION['rights']['admin']['gallery']['kate_edit'] OR @$_SESSION['rights']['superadmin']) {
							$db->query('SELECT * FROM '.DB_PRE.'ecp_gallery_kate WHERE kateID = '.(int)$_GET['id']);
							if($db->num_rows()) {
								$row = $db->fetch_assoc();
								$row['beschreibung'] = json_decode($row['beschreibung'], true);
								if(!count($row['beschreibung'])) $row['beschreibung'] = array();
								foreach($row AS $key=>$value) {
									if(is_string($value))
									$row[$key] = html_ajax_convert($value);
								}
								echo json_encode($row);
							} else {
								echo '{"error" : "'.html_ajax_convert(NO_ENTRIES_ID).'"}';
							}
						} else {
							echo '{"error" : "'.html_ajax_convert(NO_ADMIN_RIGHTS).'"}';
						}
						break;
					case 'get_gallery':
						if(@$_SESSION['rights']['admin']['gallery']['edit'] OR @$_SESSION['rights']['superadmin']) {
							$db->query('SELECT * FROM '.DB_PRE.'ecp_gallery WHERE galleryID = '.(int)$_GET['id']);
							if($db->num_rows()) {
								$row = $db->fetch_assoc();
								foreach($row AS $key=>$value) {
									if(is_string($value))
									$row[$key] = html_ajax_convert($value);
								}
								echo json_encode($row);
							} else {
								echo '{"error" : "'.htmlentities(NO_ENTRIES_ID).'"}';
							}
						} else {
							echo '{"error" : "'.htmlentities(NO_ADMIN_RIGHTS).'"}';
						}
						break;
					case 'get_gallery_kates':
						if(@$_SESSION['rights']['admin']['gallery'] OR @$_SESSION['rights']['superadmin']) {
							$db->query('SELECT kateID, katename, galleries FROM '.DB_PRE.'ecp_gallery_kate ORDER BY katename ASC');
							$kate = array();
							while($row = $db->fetch_assoc()) {
								$kate[] =$row;
							}
							ob_start();
							$tpls = new Smarty;
							$tpls->assign('kate', $kate);
							ob_start();
							$tpls->display(DESIGN.'/tpl/admin/gallery_kate_overview.html');
							$content = ob_get_contents();
							ob_end_clean();
							echo html_ajax_convert($content);
						} else {
							echo html_ajax_convert(NO_ADMIN_RIGHTS);
						}
						break;
					case 'get_galleries':
						if(@$_SESSION['rights']['admin']['gallery'] OR @$_SESSION['rights']['superadmin']) {
							$db->query('SELECT galleryID, folder, name, images, katename FROM '.DB_PRE.'ecp_gallery LEFT JOIN '.DB_PRE.'ecp_gallery_kate ON cID = kateID ORDER BY name ASC');
							$gallery = array();
							while($row = $db->fetch_assoc()) {
								$gallery[] =$row;
							}
							ob_start();
							$tpls = new Smarty;
							$tpls->assign('gallery', $gallery);
							ob_start();
							$tpls->display(DESIGN.'/tpl/admin/gallery_overview.html');
							$content = ob_get_contents();
							ob_end_clean();
							echo html_ajax_convert($content);
						} else {
							echo html_ajax_convert(NO_ADMIN_RIGHTS);
						}
						break;
					case 'gallery_get_pics':
						if(@$_SESSION['rights']['admin']['gallery'] OR @$_SESSION['rights']['superadmin']) {
							$gallery = $db->fetch_assoc('SELECT name, folder, images FROM '.DB_PRE.'ecp_gallery WHERE galleryID = '.(int)$_GET['id']);
							if(isset($gallery['name'])) {
								if($gallery['images']) {
									//$limits = get_sql_limit($gallery['images'], LIMIT_GALLERY_PICS);
									$db->query('SELECT * FROM '.DB_PRE.'ecp_gallery_images WHERE gID = '.(int)$_GET['id'].' ORDER BY imageID ASC'); //LIMIT '.$limits[1].','.LIMIT_GALLERY_PICS
									$pics = array();
									while($row = $db->fetch_assoc()) {
										$row['uploaded'] = date(SHORT_DATE, $row['uploaded']);
										$pics[] = $row;
									}
									$tpl = new smarty();
									$tpl->assign('pics', $pics);
									$tpl->assign('folder', $gallery['folder']);
									ob_start();
									$tpl->display(DESIGN.'/tpl/admin/gallery_view_overview.html');
									$content = ob_get_contents();
									ob_end_clean();
									echo html_ajax_convert($content);
								}
							} else {
								html_ajax_convert(NO_ENTRIES_ID);
							}
						} else {
							echo html_ajax_convert(NO_ADMIN_RIGHTS);
						}
						break;
					case 'upload_gallery':
						if(@$_SESSION['rights']['admin']['gallery']['add'] OR @$_SESSION['rights']['admin']['gallery']['edit'] OR @$_SESSION['rights']['superadmin']) {
							if(count($_FILES)) {
								$gallery = $db->fetch_assoc('SELECT name, folder, images, cID FROM '.DB_PRE.'ecp_gallery WHERE galleryID = '.(int)$_GET['id']);
								if(isset($gallery['name'])) {
									if(($gallery['folder'] != '' AND is_dir('images/gallery/'.$gallery['folder']) AND is_dir('images/gallery/'.$gallery['folder'].'/thumbs'))) {
										foreach($_FILES AS $key=>$value) {
											if($_FILES[$key]['name'] == '') continue;
											if(getMimeType($_FILES[$key]['tmp_name'], $_FILES[$key]['name']) == 'image/jpeg') {
												$sha1 = sha1_file($_FILES[$key]['tmp_name']);
												if($db->query('INSERT INTO '.DB_PRE.'ecp_gallery_images (`gID`, `filename`, `uploaded`, `userID`) VALUES ('.(int)$_GET['id'].', \''.$sha1.'.jpg\', '.time().', '.(int)$_SESSION['userID'].')')) {
													$id = $db->last_id();
													$db->query('UPDATE '.DB_PRE.'ecp_gallery SET images = images + 1 WHERE galleryID= '.(int)$_GET['id']);
													if(move_uploaded_file($_FILES[$key]['tmp_name'], 'images/gallery/'.$gallery['folder'].'/'.$id.'_'.$sha1.'.jpg')) {
														umask(0);
														chmod('images/gallery/'.$gallery['folder'].'/'.$id.'_'.$sha1.'.jpg', CHMOD);
														$size = getimagesize('images/gallery/'.$gallery['folder'].'/'.$id.'_'.$sha1.'.jpg');
														if($size[0] > GALLERY_THUMB_SIZE) {
															resize_picture('images/gallery/'.$gallery['folder'].'/'.$id.'_'.$sha1.'.jpg',GALLERY_THUMB_SIZE, 'images/gallery/'.$gallery['folder'].'/thumbs/'.$id.'_'.$sha1.'.jpg', 100, 0);
														} else {
															copy('images/gallery/'.$gallery['folder'].'/'.$id.'_'.$sha1.'.jpg', 'images/gallery/'.$gallery['folder'].'/thumbs/'.$id.'_'.$sha1.'.jpg');
															umask(0);
															chmod('images/gallery/'.$gallery['folder'].'/thumbs/'.$id.'_'.$sha1.'.jpg', CHMOD);
														}
														if($size[0] > GALLERY_PIC_SIZE) {
															resize_picture('images/gallery/'.$gallery['folder'].'/'.$id.'_'.$sha1.'.jpg', GALLERY_PIC_SIZE, 'images/gallery/'.$gallery['folder'].'/'.$id.'_'.$sha1.'.jpg', 100);
														}
													} else {
														@$error .= 'Datei konnte nicht verschoben werden.('.$_FILES[$key]['name'].')<br />';
													}
												}
											} else {
												@$error .= WRONG_FILE_TYPE.' ('.$_FILES[$key]['name'].' : '.getMimeType($_FILES[$key]['tmp_name'], $_FILES[$key]['name']).')<br />';
											}
										}
									} else {
										$error = FILE_NOT_FOUND;
									}
								} else {
									$error = FILE_EXIST;
								}
							} else {
								$error = 'Es wurde keine Datei hochgeladen.';
							}
						} else {
							$error = NO_ADMIN_RIGHTS;
						}
						if(UPLOAD_METHOD == 'Flash') {
							if(isset($error)) {
								echo html_ajax_convert(json_encode(array('result'=>'failed', 'error'=>$error)));
							} else {
								echo html_ajax_convert(json_encode(array('result'=>'success', 'size'=>str_replace('{datei}', $_FILES['Filedata']['name'], UPLOAD_SUCCESS))));
							}	
						} else {
						if(isset($error)) {
								echo $error.'<br /><a href="index.php?section=admin&site=gallery&func=viewgallery&id='.(int)$_GET['id'].'">Back to Page</a>';
							} else {
								header1('index.php?section=admin&site=gallery&func=viewgallery&id='.(int)$_GET['id']);
							}	
						}
						break;
					case 'gallery_set_text':
						if(@$_SESSION['rights']['admin']['gallery']['edit'] OR @$_SESSION['rights']['superadmin']) {
							if($db->query('UPDATE '.DB_PRE.'ecp_gallery_images SET beschreibung = \''.strsave($_POST['msg']).'\' WHERE imageID = '.(int)$_GET['pid'])) {
								echo 'ok';
							}
						} else {
							echo html_ajax_convert(NO_ADMIN_RIGHTS);
						}
						break;
					case 'del_gallery_pic':
						if(@$_SESSION['rights']['admin']['gallery']['edit'] OR @$_SESSION['rights']['superadmin']) {
							$data = $db->fetch_assoc('SELECT imageID, filename, gID, folder FROM '.DB_PRE.'ecp_gallery_images LEFT JOIN '.DB_PRE.'ecp_gallery ON gID= galleryID WHERE imageID= '.(int)$_GET['id']);
							if(isset($data['filename'])) {
								@unlink('images/gallery/'.$data['folder'].'/'.$data['imageID'].'_'.$data['filename']);
								@unlink('images/gallery/'.$data['folder'].'/thumbs/'.$data['imageID'].'_'.$data['filename']);
								if($db->query('DELETE FROM '.DB_PRE.'ecp_gallery_images WHERE imageID = '.$data['imageID']) AND $db->query('UPDATE '.DB_PRE.'ecp_gallery SET images = images - 1 WHERe galleryID = '.$data['gID'])) {
									$db->query('DELETE FROM '.DB_PRE.'ecp_comments WHERE bereich ="gallery" AND subID= '.$data['imageID']);
									echo 'ok';
								}
							} else {
								echo html_ajax_convert(NO_ENTRIES_ID);
							}
						} else {
							echo html_ajax_convert(NO_ADMIN_RIGHTS);
						}
						break;
					case 'del_gallery':
						if(@$_SESSION['rights']['admin']['gallery']['del'] OR @$_SESSION['rights']['superadmin']) {
							$data = $db->fetch_assoc('SELECT cID, folder FROM '.DB_PRE.'ecp_gallery WHERE galleryID= '.(int)$_GET['id']);
							$result = $db->query('SELECT filename, imageID FROM '.DB_PRE.'ecp_gallery_images WHERE gID = '.(int)$_GET['id']);
							while($row = mysql_fetch_assoc($result)) {
								@unlink('images/gallery/'.$data['folder'].'/'.$row['imageID'].'_'.$row['filename']);
								@unlink('images/gallery/'.$data['folder'].'/thumbs/'.$row['imageID'].'_'.$row['filename']);
								$db->query('DELETE FROM '.DB_PRE.'ecp_comments WHERE bereich ="gallery" AND subID= '.$row['imageID']);
							}
							$db->query('DELETE FROM '.DB_PRE.'ecp_gallery_images WHERE gID = '.(int)$_GET['id']);
							if($db->query('DELETE FROM '.DB_PRE.'ecp_gallery WHERE galleryID = '.(int)$_GET['id'])) {
								$db->query('UPDATE '.DB_PRE.'ecp_gallery_kate SET galleries = galleries - 1 WHERE kateID = '.$data['cID']);
								echo 'ok';
							}
						} else {
							echo html_ajax_convert(NO_ADMIN_RIGHTS);
						}
						break;
					case 'del_gallery_kate':
						if(@$_SESSION['rights']['admin']['gallery']['kate_del'] OR @$_SESSION['rights']['superadmin']) {
							$result = $db->query('SELECT folder, galleryID FROM '.DB_PRE.'ecp_gallery WHERE cID = '.(int)$_GET['id']);
							while($row = mysql_fetch_assoc($result)) {
								$result2 = $db->query('SELECT imageID, filename FROM '.DB_PRE.'ecp_gallery_images WHERE gID = '.$row['galleryID']);
								while($sub = mysql_fetch_assoc($result2)) {
									@unlink('images/gallery/'.$row['folder'].'/'.$sub['imageID'].'_'.$sub['filename']);
									@unlink('images/gallery/'.$row['folder'].'/thumbs/'.$sub['imageID'].'_'.$sub['filename']);
									$db->query('DELETE FROM '.DB_PRE.'ecp_comments WHERE bereich ="gallery" AND subID= '.$sub['imageID']);
								}
								$db->query('DELETE FROM '.DB_PRE.'ecp_gallery_images WHERE gID = '.$row['galleryID']);
							}
							if($db->query('DELETE FROM '.DB_PRE.'ecp_gallery WHERE cID = '.(int)$_GET['id']) AND $db->query('DELETE FROM '.DB_PRE.'ecp_gallery_kate WHERE kateID = '.(int)$_GET['id'])) {
								echo 'ok';
							}
						} else {
							echo html_ajax_convert(NO_ADMIN_RIGHTS);
						}
						break;
					case 'smilie_edit':
						if(@$_SESSION['rights']['admin']['smilies']['edit'] OR @$_SESSION['rights']['superadmin']) {
							$data = $db->fetch_assoc('SELECT bedeutung FROM '.DB_PRE.'ecp_smilies WHERE ID = '.(int)$_GET['id']);
							$tpl = new smarty();
							$tpl->assign('id', (int)$_GET['id']);
							$tpl->assign('bedeutung', $data['bedeutung']);
							ob_start();
							$tpl->display(DESIGN.'/tpl/admin/smilies_edit.html');
							$content = ob_get_contents();
							ob_end_clean();
							echo html_ajax_convert($content);
						} else {
							echo html_ajax_convert(NO_ADMIN_RIGHTS);
						}
						break;
					case 'del_smilie':
						if(@$_SESSION['rights']['admin']['smilies']['del'] OR @$_SESSION['rights']['superadmin']) {
							unlink('images/smilies/'.$db->result(DB_PRE.'ecp_smilies', 'filename', 'ID = '.(int)$_GET['id']));
							if($db->query('DELETE FROM '.DB_PRE.'ecp_smilies WHERE ID = '.(int)$_GET['id'])) {
								echo 'ok';
							}
						} else {
							echo html_ajax_convert(NO_ADMIN_RIGHTS);
						}
						break;
					case 'del_shout':
						if(@$_SESSION['rights']['admin']['shoutbox']['del'] OR @$_SESSION['rights']['superadmin']) {
							if($db->query('DELETE FROM '.DB_PRE.'ecp_comments WHERE bereich = "shoutbox" AND comID = '.(int)$_GET['id'])) {
								echo 'ok';
							}
						} else {
							echo html_ajax_convert(NO_ADMIN_RIGHTS);
						}
						break;
					case 'ts_edit':
						if(@$_SESSION['rights']['admin']['teamspeak']['edit'] OR @$_SESSION['rights']['superadmin']) {
							if($db->query('UPDATE '.DB_PRE.'ecp_teamspeak SET ip = \''.strsave($_POST['ip']).'\', port = '.(int)$_POST['port'].', qport = '.(int)$_POST['qport'])) {
								echo 'ok';
							}
						} else {
							echo html_ajax_convert(NO_ADMIN_RIGHTS);
						}
						break;
					case 'get_rank':
						if(@$_SESSION['rights']['admin']['ranks']['edit'] OR @$_SESSION['rights']['superadmin']) {
							$db->query('SELECT * FROM '.DB_PRE.'ecp_ranks WHERE rankID = '.(int)$_GET['id']);
							if($db->num_rows()) {
								$row = $db->fetch_assoc();
								html_convert_array($row);
								echo json_encode($row);
							} else {
								echo '{"error" : "'.htmlentities(NO_ENTRIES_ID).'"}';
							}
						} else {
							echo '{"error" : "'.htmlentities(NO_ADMIN_RIGHTS).'"}';
						}
						break;
					case 'del_rank':
						if(@$_SESSION['rights']['admin']['ranks']['del'] OR @$_SESSION['rights']['superadmin']) {
							@unlink('images/ranks/'.$db->result(DB_PRE.'ecp_ranks', 'iconname', 'rankID = '.(int)$_GET['id']));
							if($db->query('DELETE FROM '.DB_PRE.'ecp_ranks WHERE rankID = '.(int)$_GET['id'])) {
								update_all_ranks();
								echo 'ok';
							}
						} else {
							echo html_ajax_convert(NO_ADMIN_RIGHTS);
						}
						break;
					case 'user_add_group':
						if(@$_SESSION['rights']['admin']['groups']['add_m'] OR @$_SESSION['rights']['superadmin']) {
							if(isset($_GET['gid'])) {
								if($db->result(DB_PRE.'ecp_user_groups', 'COUNT(userID)', 'gID = '.(int)$_GET['gid'].' AND userID = '.(int)$_GET['id'])) {
									echo USER_ALLREADY_IN_GROUP;
								} else {
									if($db->query('INSERT INTO '.DB_PRE.'ecp_user_groups (userID, gID) VALUES ('.(int)$_GET['id'].', '.(int)$_GET['gid'].')')) {
										$db->query('UPDATE '.DB_PRE.'ecp_user SET update_rights = 1 WHERE ID = '.(int)$_GET['id']);
										echo 'ok';
									}
								}
							} else {
								$db->query('SELECT gID FROM `'.DB_PRE.'ecp_user_groups` WHERE userID = '.(int)$_GET['id']);
								$gruppen = array();
								while($row = $db->fetch_assoc()) {
									$gruppen[] = $row['gID'];
								}
								$gruppe = array();
								$db->query('SELECT groupID, name, admin FROM '.DB_PRE.'ecp_groups ORDER BY name ASC');
								while($row = $db->fetch_assoc()) {
									if(in_array($row['groupID'], $gruppen)) continue;
									if(array_key_exists($row['name'], $groups)) $row['name'] = $groups[$row['name']];
									$gruppe[] = $row;
								}
								$tpl = new smarty();
								$tpl->assign('id', (int)$_GET['id']);
								$tpl->assign('gruppe', $gruppe);
								$tpl->assign('username', $db->result(DB_PRE.'ecp_user', 'username', 'ID = '.(int)$_GET['id']));
								ob_start();
								$tpl->display(DESIGN.'/tpl/admin/user_add_group.html');
								$content = ob_get_contents();
								ob_end_clean();
								echo html_ajax_convert($content);
							}
						} else {
							echo html_ajax_convert(NO_ADMIN_RIGHTS);
						}
						break;
					case 'user_add_team':
						if(@$_SESSION['rights']['admin']['teams']['add_member'] OR @$_SESSION['rights']['superadmin']) {
							if(isset($_POST['teamID'])) {
								if($db->result(DB_PRE.'ecp_members', 'COUNT(userID)', 'teamID = '.(int)$_POST['teamID'].' AND userID = '.(int)$_GET['id'])) {
									echo USER_ALLREADY_IN_TEAM;
								} else {
									if($db->query('INSERT INTO '.DB_PRE.'ecp_members (`userID`, `teamID`, `name`, `aufgabe`, `aktiv`, posi) VALUES ('.(int)$_GET['id'].', '.(int)$_POST['teamID'].', \''.strsave(htmlspecialchars($_POST['username'])).'\', \''.strsave($_POST['task']).'\', '.(int)@$_POST['aktiv'].', 99)')) {
										echo 'ok';
									}
								}
							} else {
								$db->query('SELECT teamID FROM `'.DB_PRE.'ecp_members` WHERE userID = '.(int)$_GET['id']);
								$gruppen = array();
								while($row = $db->fetch_assoc()) {
									$gruppen[] = $row['teamID'];
								}
								$gruppe;
								$db->query('SELECT tID, tname FROM '.DB_PRE.'ecp_teams ORDER BY tname ASC');
								while($row = $db->fetch_assoc()) {
									if(in_array($row['tID'], $gruppen)) continue;
									@$teams .= '<option value="'.$row['tID'].'">'.$row['tname'].'</option>';
								}
								$tpl = new smarty();
								$tpl->assign('id', (int)$_GET['id']);
								$tpl->assign('teams', @$teams);
								ob_start();
								$tpl->display(DESIGN.'/tpl/admin/team_add_user.html');
								$content = ob_get_contents();
								ob_end_clean();
								echo html_ajax_convert($content);
							}
						} else {
							echo html_ajax_convert(NO_ADMIN_RIGHTS);
						}
						break;
					case 'user_change_pw':
						if(@$_SESSION['rights']['admin']['user']['change_pw'] OR @$_SESSION['rights']['superadmin']) {
							if(isset($_POST['password1'])) {
								if($_POST['password1'] != $_POST['password2']) {
									echo DIFFERENT_PW;
								} else {
									if($db->query('UPDATE '.DB_PRE.'ecp_user SET passwort = \''.sha1($_POST['password1']).'\' WHERE ID = '.(int)$_GET['id'])) {
										echo 'ok';
									}
								}
							} else {
								$tpl = new smarty();
								$tpl->assign('id', (int)$_GET['id']);
								ob_start();
								$tpl->display(DESIGN.'/tpl/admin/user_change_pw.html');
								$content = ob_get_contents();
								ob_end_clean();
								echo html_ajax_convert($content);
							}
						} else {
							echo html_ajax_convert(NO_ADMIN_RIGHTS);
						}
						break;
					case 'user_ban':
						if(@$_SESSION['rights']['admin']['user']['ban'] OR @$_SESSION['rights']['superadmin']) {
							if(isset($_POST['grund'])) {
								if($_POST['grund'] == '') {
									echo html_ajax_convert(NOT_NEED_ALL_INPUTS);
								}else if (strtotime($_POST['ende']) < time()) {
									echo html_ajax_convert(DATE_HISTORY);
								} else {
									if($db->query('INSERT INTO '.DB_PRE.'ecp_user_bans (`userID`, `vonID`, `bantime`, `endbantime`, `grund`) VALUES ('.(int)$_GET['id'].', '.$_SESSION['userID'].', '.time().', '.strtotime($_POST['ende']).', \''.strsave($_POST['grund']).'\')') AND $db->query('UPDATE '.DB_PRE.'ecp_user SET status = 2 WHERE ID = '.(int)$_GET['id'])) {
										echo 'ok';
									}
								}
							} else {
								$tpl = new smarty();
								$tpl->assign('id', (int)$_GET['id']);
								ob_start();
								$tpl->display(DESIGN.'/tpl/admin/user_ban.html');
								$content = ob_get_contents();
								ob_end_clean();
								echo html_ajax_convert($content);
							}
						} else {
							echo html_ajax_convert(NO_ADMIN_RIGHTS);
						}
						break;
					case 'del_user':
						if(@$_SESSION['rights']['admin']['user']['del'] OR @$_SESSION['rights']['superadmin']) {
							if(delete_user((int)$_GET['id'])) {
								echo 'ok';
							}
						} else {
							echo html_ajax_convert(NO_ADMIN_RIGHTS);
						}
						break;
					case 'del_ban':
						if(@$_SESSION['rights']['admin']['user']['del_ban'] OR @$_SESSION['rights']['superadmin']) {
							if($db->query('DELETE FROM '.DB_PRE.'ecp_user_bans WHERE userID = '.(int)$_GET['id'].' AND bantime = '.(int)$_GET['zeit'])) {
								echo 'ok';
							}
						} else {
							echo html_ajax_convert(NO_ADMIN_RIGHTS);
						}
						break;
					case 'user_aktiv':
						if(@$_SESSION['rights']['admin']['user']['aktiv'] OR @$_SESSION['rights']['superadmin']) {
							if($db->query('UPDATE '.DB_PRE.'ecp_user SET status = 1 WHERE ID = '.(int)$_GET['id'])) {
								echo 'ok';
							}
						} else {
							echo html_ajax_convert(NO_ADMIN_RIGHTS);
						}
						break;
					case 'change_rank':
						if(@$_SESSION['rights']['admin']['user']['change_rang'] OR @$_SESSION['rights']['superadmin']) {
							if(isset($_POST['newrang'])) {
								if((int)$_POST['newrang'] == 0) {
									$db->query('UPDATE '.DB_PRE.'ecp_user SET rID = 0 WHERE ID = '.(int)$_GET['id']);
									update_rank((int)$_GET['id']);
									if($db->errorNum() == 0) {
										echo 'ok';
									}
								} else {
									if($db->query('UPDATE '.DB_PRE.'ecp_user SET rID = '.(int)$_POST['newrang'].' WHERE ID = '.(int)$_GET['id'])) {
										echo 'ok';
									}
								}
							} else {
								$tpl = new smarty();
								$tpl->assign('id', (int)$_GET['id']);
								$db->query('SELECT rankname, rankID FROM '.DB_PRE.'ecp_ranks WHERE fest = 1 ORDER BY rankname ASC');
								while($row = $db->fetch_assoc()) {
									@$option .= '<option value="'.$row['rankID'].'">'.$row['rankname'].'</option>';
								}
								$tpl->assign('ranks', @$option);
								ob_start();
								$tpl->display(DESIGN.'/tpl/admin/user_change_rang.html');
								$content = ob_get_contents();
								ob_end_clean();
								echo html_ajax_convert($content);
							}
						} else {
							echo html_ajax_convert(NO_ADMIN_RIGHTS);
						}
						break;
					case 'find_user':
						if(@$_SESSION['rights']['admin']['user'] OR @$_SESSION['rights']['superadmin']) {
							$tpl = new smarty();
							switch($_POST['suchart']) {
								case 'username':
									$result = $db->query('SELECT username, ID, registerdate, email, country, uID as online FROM '.DB_PRE.'ecp_user LEFT JOIN '.DB_PRE.'ecp_online ON (uID = ID AND lastklick > '.(time()-SHOW_USER_ONLINE).') WHERE username LIKE "%'.strsave($_POST['suche']).'%" ORDER BY username ASC');
									break;
								case 'email':
									$result = $db->query('SELECT username, ID, registerdate, email, country, uID as online FROM '.DB_PRE.'ecp_user LEFT JOIN '.DB_PRE.'ecp_online ON (uID = ID AND lastklick > '.(time()-SHOW_USER_ONLINE).') WHERE email LIKE "%'.strsave($_POST['suche']).'%" ORDER BY username ASC');
									break;
								case 'ID':
									$result = $db->query('SELECT username, ID, registerdate, email, country, uID as online FROM '.DB_PRE.'ecp_user LEFT JOIN '.DB_PRE.'ecp_online ON (uID = ID AND lastklick > '.(time()-SHOW_USER_ONLINE).') WHERE ID = '.(int)$_POST['suche'].' ORDER BY username ASC');
									break;
								default:
									$result = $db->query('SELECT username, ID, registerdate, email, country, uID as online FROM '.DB_PRE.'ecp_user LEFT JOIN '.DB_PRE.'ecp_online ON (uID = ID AND lastklick > '.(time()-SHOW_USER_ONLINE).') WHERE username LIKE "%'.strsave($_POST['suche']).'%" ORDER BY username ASC');
							}
							$user = array();
							while($row = mysql_fetch_assoc($result)) {
								$row['registerdate'] = date(SHORT_DATE, $row['registerdate']);
								$row['gruppen'] = array();
								$db->query('SELECT gID, name FROM '.DB_PRE.'ecp_user_groups LEFT JOIN '.DB_PRE.'ecp_groups ON (gID = groupID) WHERE userID = '.$row['ID'].' ORDER BY name ASC');
								while($sub = $db->fetch_assoc()) {
									array_key_exists($sub['name'], $groups) ? $sub['name'] = $groups[$sub['name']] : '';
									$row['gruppen'][] = $sub;
								}
								$user[] = $row;
							}
							$tpl->assign('user', @$user);
							ob_start();
							$tpl->display(DESIGN.'/tpl/admin/user_list.html');
							$content = ob_get_contents();
							ob_end_clean();
							echo html_ajax_convert($content);
						} else {
							echo html_ajax_convert(NO_ADMIN_RIGHTS);
						}
						break;
					case 'get_cms':
						if(@$_SESSION['rights']['admin']['cms']['edit'] OR @$_SESSION['rights']['superadmin']) {
							$db->query('SELECT headline, access, content FROM '.DB_PRE.'ecp_cms WHERE cmsID = '.(int)$_GET['id']);
							if($db->num_rows()) {
								$row = $db->fetch_assoc();
								$row['headline'] = json_decode($row['headline'], true);
								if(!count($row['headline'])) $row['headline'] = array();
								$row['content'] = json_decode($row['content'], true);
								if(!count($row['content'])) $row['content'] = array();
								html_convert_array($row);
								echo json_encode($row);
							} else {
								echo '{"error" : "'.html_ajax_convert(NO_ENTRIES_ID).'"}';
							}
						} else {
							echo '{"error" : "'.html_ajax_convert(NO_ADMIN_RIGHTS).'"}';
						}
						break;
					case 'del_cms':
						if(@$_SESSION['rights']['admin']['cms']['del'] OR @$_SESSION['rights']['superadmin']) {
							if($db->query('DELETE FROM '.DB_PRE.'ecp_cms WHERE cmsID = '.(int)$_GET['id'])) {
								echo 'ok';
							}
						} else {
							echo html_ajax_convert(NO_ADMIN_RIGHTS);
						}
						break;
					case 'get_event':
						if(@$_SESSION['rights']['admin']['calendar']['edit'] OR @$_SESSION['rights']['superadmin']) {
							$db->query('SELECT eventname, access, inhalt, datum FROM '.DB_PRE.'ecp_calendar WHERE calID = '.(int)$_GET['id']);
							if($db->num_rows()) {
								$row = $db->fetch_assoc();
								$row['datum'] = date('Y-m-d H:i:s', $row['datum']);
								$row['inhalt'] = json_decode($row['inhalt'], true);
								if(!count($row['inhalt'])) $row['inhalt'] = array();
								html_convert_array($row);
								echo json_encode($row);
							} else {
								echo '{"error" : "'.html_ajax_convert(NO_ENTRIES_ID).'"}';
							}
						} else {
							echo '{"error" : "'.html_ajax_convert(NO_ADMIN_RIGHTS).'"}';
						}
						break;
					case 'del_cal':
						if(@$_SESSION['rights']['admin']['calendar']['del'] OR @$_SESSION['rights']['superadmin']) {
							if($db->query('DELETE FROM '.DB_PRE.'ecp_calendar WHERE calID = '.(int)$_GET['id'])) {
								echo 'ok';
							}
						} else {
							echo html_ajax_convert(NO_ADMIN_RIGHTS);
						}
						break;
					default:
						echo html_ajax_convert(NO_FUNKTION_CHOOSE);
				}

			}
		} else {
			echo NO_ADMIN_RIGHTS;
		}
		break;
	default:
		echo html_ajax_convert(NO_FUNKTION_CHOOSE);
}
$db->close();

?>