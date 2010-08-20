<?php
function forum() {
	global $db, $installed;
	if(isset($_SESSION['userID'])) $db->query('UPDATE '.DB_PRE.'ecp_user SET lastforum = '.time().' WHERE ID = '.$_SESSION['userID']);
	$db->query('SELECT `boardID`, `boardparentID`, `name`, `posi`, `isforum`, `beschreibung`, '.DB_PRE.'ecp_forum_boards.closed, '.DB_PRE.'ecp_forum_boards.threads, 
							'.DB_PRE.'ecp_forum_boards.posts, lastpost, lastthreadID, lastpostuser, lastpostuserID, username, threadname 
							FROM '.DB_PRE.'ecp_forum_boards 
							LEFT JOIN '.DB_PRE.'ecp_user ON ID = lastpostuserID 
							LEFT JOIN '.DB_PRE.'ecp_forum_threads ON (threadID = lastthreadID) 
							WHERE rightsread = "" OR '.str_replace('access', 'rightsread', $_SESSION['access_search']).'
							ORDER BY boardparentID, posi ASC');
	if($db->num_rows()) {
		$foren = array();
		$posts = 0;
		$threads = 0;
		while($row = $db->fetch_assoc()) {
			if(isset($_SESSION['userID'])) {
				if(isset($_SESSION['lastforum']['boards'][$row['boardID']]['new']) AND $_SESSION['lastforum']['boards'][$row['boardID']]['new']) {
					$row['new'] = true;
				} else {
					if (!isset($_SESSION['lastforum']['boards'][$row['boardID']]['new']) AND $_SESSION['lastforum']['time'] < $row['lastpost']) {
						$row['new'] = true;
					} else {
						if (isset($_SESSION['lastforum']['boards'][$row['boardID']]['time']) AND $_SESSION['lastforum']['boards'][$row['boardID']]['time'] < $row['lastpost']) {
							$row['new'] = true;
						}	
					}
				}			
			}
			$row['lastpost'] = forum_make_date($row['lastpost']);
			if($row['isforum'] == 0) {
				$foren[$row['boardID']] = $row;
				$posts += $row['posts'];
				$threads += $row['threads'];				
			} elseif ($row['isforum'] == 1 AND $row['boardparentID'] == 0) {
				$foren[$row['boardID']] = $row;
				$posts += $row['posts'];
				$threads += $row['threads'];
			} else {
				if(isset($foren[$row['boardparentID']])) 
			    	$foren[$row['boardparentID']]['subs'][] = $row;			    
			}
		}
		$tpl = new Smarty();
		$tpl->assign('foren', $foren);
		ob_start();
		$tage = ((time()-$installed)/86400);
		$db->query('SELECT uID, username FROM '.DB_PRE.'ecp_online LEFT JOIN '.DB_PRE.'ecp_user ON (ID=uID) WHERE forum = 1  AND lastklick > '.(time()-SHOW_USER_ONLINE).' ORDER BY username ASC');
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
		$tpl->assign('members', substr($member, 2));
		$tpl->assign('stats', str_replace(array('{days}', '{posts}', '{threads}', '{postsperday}', '{postsperthread}'), array(format_nr($tage), format_nr($posts), format_nr($threads), format_nr($posts/(($tage) ? $tage : 1)), format_nr($posts/(($threads) ? $threads : 1))), FORUM_STATS));
		$tpl->assign('online', str_replace(array('{members}', '{guests}'), array(format_nr($members), format_nr($guests)), FORUM_ONLINE_FORUM));
		$tpl->display(DESIGN.'/tpl/forum/board_head.html');
		$tpl->display(DESIGN.'/tpl/forum/board_overview.html');
		$tpl->display(DESIGN.'/tpl/forum/board_footer.html');
		$content = ob_get_contents();
		ob_end_clean();
		main_content(FORUM, $content, '',1);    	
	} else {
		table(ERROR, NO_ENTRIES);
	}
}
function forum_subboard($id) {
	global $db, $installed;
	if($db->result(DB_PRE.'ecp_forum_boards', 'COUNT(boardID)', 'boardID = '.$id.' AND (rightsread = "" OR '.str_replace('access', 'rightsread', $_SESSION['access_search']).')')) {
		$db->query('SELECT `boardID`, `boardparentID`, `name`, `posi`, `isforum`, `beschreibung`, '.DB_PRE.'ecp_forum_boards.closed, '.DB_PRE.'ecp_forum_boards.threads, 
							'.DB_PRE.'ecp_forum_boards.posts, lastpost, lastthreadID, lastpostuser, lastpostuserID, username, threadname 
							FROM '.DB_PRE.'ecp_forum_boards 
							LEFT JOIN '.DB_PRE.'ecp_user ON ID = lastpostuserID 
							LEFT JOIN '.DB_PRE.'ecp_forum_threads ON (threadID = lastthreadID) 
							WHERE (boardID = '.$id.' OR  boardparentID = '.$id.') AND (rightsread = "" OR '.str_replace('access', 'rightsread', $_SESSION['access_search']).')
							ORDER BY boardparentID, posi ASC');		
		$foren = array();
		$posts = 0;
		$threads= 0;
		$ids = 'AND (';
		while($row = $db->fetch_assoc()) {
			if(isset($_SESSION['userID'])) {
				if(isset($_SESSION['lastforum']['boards'][$row['boardID']]['new']) AND $_SESSION['lastforum']['boards'][$row['boardID']]['new']) {
					$row['new'] = true;
				} else {
					if (!isset($_SESSION['lastforum']['boards'][$row['boardID']]['new']) AND $_SESSION['lastforum']['time'] < $row['lastpost']) {
						$row['new'] = true;
					} else {
						if (isset($_SESSION['lastforum']['boards'][$row['boardID']]['time']) AND $_SESSION['lastforum']['boards'][$row['boardID']]['time'] < $row['lastpost']) {
							$row['new'] = true;
						}	
					}
				}			
			}			
			$row['lastpost'] = forum_make_date($row['lastpost']);
			if($row['isforum'] == 0) {
				$foren[$row['boardID']] = $row;
				$posts += $row['posts'];
				$threads += $row['threads'];				
				$boardname = $row['name'];
			} else {
				if(isset($foren[$row['boardparentID']])) 
			    	$foren[$row['boardparentID']]['subs'][] = $row;			    
			}
			$ids .= 'fboardID = '.$row['boardID'].' OR ';
			
		}
		$ids = substr($ids, 0, strlen($ids)-4).')';
		$tpl = new Smarty();
		$tpl->assign('foren', $foren);
		ob_start();
		$tage = ((time()-$installed)/86400);
		$db->query('SELECT uID, username FROM '.DB_PRE.'ecp_online LEFT JOIN '.DB_PRE.'ecp_user ON (ID=uID) WHERE forum = 1 '.$ids.' AND lastklick > '.(time()-SHOW_USER_ONLINE).' ORDER BY username ASC');
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
		$tpl->assign('forenlinks', forum_get_fast_links());
		$tpl->assign('path', '<a href="?section=forum">'.FORUM.'</a> <img src="templates/'.DESIGN.'/images/pfeil_o.gif" alt="" /> '.$boardname);
		$tpl->assign('members', substr($member, 2));
		$tpl->assign('stats', str_replace(array('{days}', '{posts}', '{threads}', '{postsperday}', '{postsperthread}'), array(format_nr($tage), format_nr($posts), format_nr($threads), format_nr($posts/(($tage) ? $tage : 1)), format_nr($posts/(($threads) ? $threads : 1))), FORUM_STATS));
		$tpl->assign('online', str_replace(array('{members}', '{guests}'), array(format_nr($members), format_nr($guests)), FORUM_ONLINE_BOARD));	
		$tpl->display(DESIGN.'/tpl/forum/board_head.html');
		$tpl->display(DESIGN.'/tpl/forum/board_overview.html');
		$tpl->display(DESIGN.'/tpl/forum/board_footer.html');
		$content = ob_get_contents();
		ob_end_clean();
		main_content(FORUM, $content, '',1);   		
	} else {
		table(ERROR, NO_ENTRIES_ID);
	}	
}
function forum_board($id) {
	global $db, $installed;
	$board = $db->fetch_assoc('SELECT `boardID`,`boardparentID`, `name`, `beschreibung`, '.DB_PRE.'ecp_forum_boards.closed, '.DB_PRE.'ecp_forum_boards.threads, 
							'.DB_PRE.'ecp_forum_boards.posts, threadopen, threadclose, threaddel, threadmove, threadpin
							FROM '.DB_PRE.'ecp_forum_boards 
							WHERE boardID = '.$id.' AND (rightsread = "" OR '.str_replace('access', 'rightsread', $_SESSION['access_search']).')');
	
	if(isset($board['boardID']) AND (($board['boardparentID'] == 0) ? true : $db->result(DB_PRE.'ecp_forum_boards','COUNT(boardID)', 'boardID = '.$board['boardparentID'].' AND (rightsread = "" OR '.str_replace('access', 'rightsread', $_SESSION['access_search']).')'))) {	
		$foren = array();
		if($board['threads'] != 0) {
			$limits = get_sql_limit($board['threads'], LIMIT_THREADS);
			$db->query('SELECT `bID`, aboID, `threadID`, `datum`, `threadname`, `preview`, `vonID`, `vonname`, `views`, `posts`, `lastuserID`, `lastusername`, `lastreplay`, `sticky`, `closed`, `fsurveyID`, `anhaenge`, `rating`, `ratingvotes`, a.username AS vonusername, b.username AS lastuserIDname
								FROM `'.DB_PRE.'ecp_forum_threads` 
								LEFT JOIN '.DB_PRE.'ecp_user AS a ON (vonID = a.ID)
								LEFT JOIN '.DB_PRE.'ecp_user AS b ON (lastuserID = b.ID)
								LEFT JOIN '.DB_PRE.'ecp_forum_abo ON (thID = threadID AND userID = '.(int)@$_SESSION['userID'].')
								WHERE bID = '.$id.'
								ORDER BY sticky DESC, lastreplay DESC
								LIMIT '.$limits[1].', '.LIMIT_THREADS);
			$threads = array();
			$_SESSION['lastforum']['boards'][$id]['new'] = 0;
			while($row = $db->fetch_assoc()) {
				if(isset($_SESSION['userID'])) {
					if(isset($_SESSION['lastforum'][$row['threadID']]) AND $_SESSION['lastforum'][$row['threadID']] < $row['lastreplay']) {
						$row['new'] = true;
						$_SESSION['lastforum']['boards'][$id]['new']++;
					} else {
						if (@$_SESSION['lastforum'][$row['threadID']] < $row['lastreplay'] AND $_SESSION['lastforum']['time'] < $row['lastreplay']) {
							$row['new'] = true;
							$_SESSION['lastforum']['boards'][$id]['new']++;
						}
					}			
				}				
				$row['lastreplay'] = forum_make_date($row['lastreplay']);
				$row['datum'] = forum_make_date($row['datum']);
				$row['bewertung'] = ($row['ratingvotes'] != 0 ? str_replace(array('{anzahl}', '{avg}'), array(format_nr($row['ratingvotes']), format_nr($row['rating'],2)), FORUM_RATING_VAL) : FORUM_NO_RATINGS);
				$row['bewertungbild'] = 'rating_'.str_replace('.', '_', get_forum_rating($row['rating']));
				$threads[] = $row;
			}
		} 
		$_SESSION['lastforum']['boards'][$id]['time'] = time();
		$tage = ((time()-$installed)/86400);
		$db->query('SELECT uID, username FROM '.DB_PRE.'ecp_online LEFT JOIN '.DB_PRE.'ecp_user ON (ID=uID) WHERE forum = 1 AND fboardID = '.$id.' AND lastklick > '.(time()-SHOW_USER_ONLINE).' ORDER BY username ASC');
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
		$tpl->assign('forenlinks', forum_get_fast_links());
		if($limits[0] > 1)
		$tpl->assign('seiten', makepagelink_ajax('?section=forum&action=board&boardID='.$id, '' , @$_GET['page'], $limits[0]));
		$tpl->assign('newtopic', find_access($board['threadopen']));
		$tpl->assign('closed', $board['closed']);
		$tpl->assign('threads', @$threads);
		$tpl->assign('path', '<a href="?section=forum">'.FORUM.'</a> <img src="templates/'.DESIGN.'/images/pfeil_o.gif" alt="" /> '.($board['boardparentID'] ? '<a href="?section=forum&action=subboard&boardID='.$board['boardparentID'].'">'.$db->result(DB_PRE.'ecp_forum_boards', 'name', 'boardID = '.$board['boardparentID']).'</a> <img src="templates/'.DESIGN.'/images/pfeil_o.gif" alt="" /> ' : '').$board['name']);
		$tpl->assign('members', substr($member, 2));
		$tpl->assign('stats', str_replace(array('{days}', '{posts}', '{threads}', '{postsperday}', '{postsperthread}'), array(format_nr($tage), format_nr($board['posts']), format_nr($board['threads']), format_nr($board['posts']/(($tage) ? $tage : 1)), format_nr($board['posts']/(($board['threads']) ? $board['threads'] : 1))), FORUM_STATS));
		$tpl->assign('online', str_replace(array('{members}', '{guests}'), array(format_nr($members), format_nr($guests)), FORUM_ONLINE_BOARD));	
		ob_start();
		$tpl->display(DESIGN.'/tpl/forum/board_head.html');
		$tpl->display(DESIGN.'/tpl/forum/board_threads.html');
		$tpl->display(DESIGN.'/tpl/forum/board_footer.html');
		$content = ob_get_contents();
		ob_end_clean();
		main_content(FORUM, $content, '',1);   		
	} else {
		table(ERROR, ACCESS_DENIED);
	}		
}
function forum_new_thread($bid) {
	global $db;
	$board = $db->fetch_assoc('SELECT `boardID`,`boardparentID`, `name`, '.DB_PRE.'ecp_forum_boards.closed, threadopen, startsurvey, attachfiles, attachments, attachmaxsize, `commentsperpost`, `moneyperpost`
							FROM '.DB_PRE.'ecp_forum_boards 
							WHERE boardID = '.$bid.' AND isforum = 1 AND (rightsread = "" OR '.str_replace('access', 'rightsread', $_SESSION['access_search']).') AND (threadopen = "" OR '.str_replace('access', 'threadopen', $_SESSION['access_search']).')');
	if(isset($board['boardID']) AND (($board['boardparentID'] == 0) ? true : $db->result(DB_PRE.'ecp_forum_boards','COUNT(boardID)', 'boardID = '.$board['boardparentID'].' AND (rightsread = "" OR '.str_replace('access', 'rightsread', $_SESSION['access_search']).')'))) {	
		if($board['closed']) {
			table(INFO, FORUM_BOARD_CLOSED);
		} else {		
			if(isset($_POST['name'])) {
				if(isset($_SESSION['userID'])) {
					$last = (int)@$db->result(DB_PRE.'ecp_forum_threads', 'datum', 'vonID = '.$_SESSION['userID'].' ORDER BY datum DESC LIMIT 1');
				} else {
					$last = (int)@$db->result(DB_PRE.'ecp_forum_comments', 'adatum', 'IP = \''.$_SESSION['userID'].'\' ORDER BY adatum DESC LIMIT 1');
					if(isset($_COOKIE['lastthread'])) {
						if($last < $_COOKIE['lastthread']) {
							$last = $_COOKIE['lastthread'];
						}
					} 
				}
				if($_POST['name'] == '' OR $_POST['comment'] == '') {
					table(ERROR, NOT_NEED_ALL_INPUTS);
					$tpl = new smarty;
					ob_start();
					$tpl->assign('umfrage', find_access($board['startsurvey']));
					foreach($_POST AS $Key =>$value) {
						$tpl->assign($Key, $value);
					}
					if($board['attachments'] AND $board['attachmaxsize']) {
						$rand = $_GET['rand'];
						$tpl->assign('attach', find_access($board['attachfiles']));
						$tpl->assign('maxsize', $board['attachmaxsize']);
						$tpl->assign('rand', $rand);
						$tpl->assign('sid', session_name().'='.session_id());	
						$tpl->assign('maxuploads', $board['attachments']);
						$tpl->assign('uploadinfo', str_replace(array('{anzahl}', '{max}'), array($board['attachments'], goodsize($board['attachmaxsize'])), FORUM_ATTACH_INFO));
						$_SESSION['forum']['attach'][$bid] = $rand;
					}
					$tpl->display(DESIGN.'/tpl/forum/new_thread'.((UPLOAD_METHOD == 'old' AND $board['attachments'] AND $board['attachmaxsize']) ? '_old' : '').'.html');
					$content = ob_get_contents();
					ob_end_clean();
					main_content(FORUM_NEW_TOPIC, $content, '',1);   
				} elseif (!isset($_SESSION['userID']) AND (strtolower($_SESSION['captcha']) != strtolower($_POST['captcha']) OR $_SESSION['captcha'] == '')) {
					table(ERROR, CAPTCHA_WRONG);	
					$tpl = new smarty;
					ob_start();
					$tpl->assign('umfrage', find_access($board['startsurvey']));
					foreach($_POST AS $Key =>$value) {
						$tpl->assign($Key, $value);
					}
					if($board['attachments'] AND $board['attachmaxsize']) {
						$rand = $_GET['rand'];
						$tpl->assign('attach', find_access($board['attachfiles']));
						$tpl->assign('maxsize', $board['attachmaxsize']);
						$tpl->assign('rand', $rand);
						$tpl->assign('sid', session_name().'='.session_id());	
						$tpl->assign('maxuploads', $board['attachments']);
						$tpl->assign('uploadinfo', str_replace(array('{anzahl}', '{max}'), array($board['attachments'], goodsize($board['attachmaxsize'])), FORUM_ATTACH_INFO));
						$_SESSION['forum']['attach'][$bid] = $rand;
					}
					$tpl->display(DESIGN.'/tpl/forum/new_thread'.((UPLOAD_METHOD == 'old' AND $board['attachments'] AND $board['attachmaxsize']) ? '_old' : '').'.html');
					$content = ob_get_contents();
					ob_end_clean();
					main_content(FORUM_NEW_TOPIC, $content, '',1);  										
				} elseif($last > time()-SPAM_FORUM_THREAD) {
					table(SPAM_PROTECTION, str_replace(array('{sek}', '{zeit}'), array(SPAM_FORUM_THREAD, ($last+SPAM_FORUM_THREAD-time())), SPAM_PROTECTION_MSG));
					$tpl = new smarty;
					ob_start();
					$tpl->assign('umfrage', find_access($board['startsurvey']));
					foreach($_POST AS $Key =>$value) {
						$tpl->assign($Key, $value);
					}
					if($board['attachments'] AND $board['attachmaxsize']) {
						$rand = $_GET['rand'];
						$tpl->assign('attach', find_access($board['attachfiles']));
						$tpl->assign('maxsize', $board['attachmaxsize']);
						$tpl->assign('rand', $rand);
						$tpl->assign('sid', session_name().'='.session_id());	
						$tpl->assign('maxuploads', $board['attachments']);
						$tpl->assign('uploadinfo', str_replace(array('{anzahl}', '{max}'), array($board['attachments'], goodsize($board['attachmaxsize'])), FORUM_ATTACH_INFO));
						$_SESSION['forum']['attach'][$bid] = $rand;
					}
					$tpl->display(DESIGN.'/tpl/forum/new_thread'.((UPLOAD_METHOD == 'old' AND $board['attachments'] AND $board['attachmaxsize']) ? '_old' : '').'.html');
					$content = ob_get_contents();
					ob_end_clean();
					main_content(FORUM_NEW_TOPIC, $content, '',1);  									
				} else {
					if($db->query(sprintf('INSERT INTO '.DB_PRE.'ecp_forum_threads (`bID`, `datum`, `threadname`, `preview`, `vonID`, vonname, `lastuserID`, lastusername, `lastreplay`) VALUES (%d, %d, \'%s\', \'%s\', %d, \'%s\', %d, \'%s\', %d)', 
										(int)$_GET['boardID'], time(), strsave(htmlspecialchars($_POST['name'])), make_forum_pre($_POST['comment']), (int)$_SESSION['userID'], strsave(htmlspecialchars(@$_POST['username'])), (int)$_SESSION['userID'], strsave(htmlspecialchars(@$_POST['username'])), time()))) {
						$thread = $db->last_id();
						if($db->query(sprintf('INSERT INTO '.DB_PRE.'ecp_forum_comments (`tID`, `boardID`, `userID`, `postname`, `adatum`, `comment`, `IP`) VALUES (%d, %d, %d, \'%s\', %d, \'%s\', \'%s\')', $thread, (int)$_GET['boardID'], @(int)$_SESSION['userID'], strsave(htmlspecialchars(@$_POST['username'])),time(), strsave(comment_save($_POST['comment'])), $_SERVER['REMOTE_ADDR']))) {																			
							$comid = $db->last_id();
							if(isset($_SESSION['userID'])) {
								$db->query('UPDATE '.DB_PRE.'ecp_user_stats SET comments = comments + '.$board['commentsperpost'].', money = money + '.$board['moneyperpost'].' WHERE userID = '.$_SESSION['userID']);
								update_rank($_SESSION['userID']);
							}
							$db->query('UPDATE '.DB_PRE.'ecp_forum_boards SET threads = threads +1, posts = posts +1, lastpost = '.time().', lastthreadID = '.$thread.', lastpostuserID = '.(int)@$_SESSION['userID'].', lastpostuser = \''.strsave(htmlspecialchars(@$_POST['username'])).'\' WHERE boardID = '.(int)$_GET['boardID'].' OR boardID = '.$board['boardparentID']);
							if(find_access($board['attachfiles'])) {
								if(UPLOAD_METHOD == 'old') {
									$maxattach = $board['attachments'];
									foreach($_FILES AS $key=>$value) {
										if($_FILES[$key] == '' OR $maxattach <= 0 OR $_FILES[$key]['size'] > $board['attachmaxsize']) continue;
										$mine = getMimeType($_FILES[$key]['tmp_name'], $_FILES[$key]['name']);
										if($mine == 'application/zip' OR $mine == 'application/x-rar-compressed' OR $mine == 'image/bmp' OR $mine == 'image/gif' OR $mine == 'image/jpeg' OR $mine == 'image/png' OR $mine == 'application/pdf' OR $mine == 'text/plain' OR $mine == 'text/css' OR $mine == 'text/html') {
											$sha1 = sha1_file($_FILES[$key]['tmp_name']);
											if($db->query(sprintf('INSERT INTO '.DB_PRE.'ecp_forum_attachments (`bID`, `userID`, `name`, `size`, `strname`, uploadzeit, IP, tID, mID) VALUES (%d, %d, \'%s\', %d, \'%s\', %d, \'%s\', %d, %d)', $bid, @(int)$_SESSION['userID'], strsave($_FILES[$key]['name']), (int)$_FILES[$key]['size'], $sha1, time(), $_SERVER['REMOTE_ADDR'], $thread, $comid))) {
												move_uploaded_file($_FILES[$key]['tmp_name'], 'uploads/forum/'.$db->last_id().'_'.$sha1);
												umask(0);
												chmod('uploads/forum/'.$db->last_id().'_'.$sha1, CHMOD);
												$db->query('UPDATE '.DB_PRE.'ecp_forum_threads SET anhaenge = 1 WHERE threadID = '.$thread);
												$db->query('UPDATE '.DB_PRE.'ecp_forum_comments SET attachs = 1 WHERE comID = '.$comid);											
											} 
											$maxattach--;
										} 																		
									}
								} else {
									$db->query(sprintf('UPDATE '.DB_PRE.'ecp_forum_attachments SET `tID` = %d, `mID` = %d WHERE validation = \'%s\' AND bID = %d', $thread, $comid, strsave($_GET['rand']),$bid));
									if($db->affekt_rows()) {
										$db->query('UPDATE '.DB_PRE.'ecp_forum_threads SET anhaenge = 1 WHERE threadID = '.$id);
										$db->query('UPDATE '.DB_PRE.'ecp_forum_comments SET attachs = 1 WHERE comID = '.$comid);
									}
								}
							}
							if(find_access($board['startsurvey'])) {
								if(@$_POST['frage'] != '' AND $_POST['answer_1'] != '') {
									if($db->query(sprintf('INSERT INTO '.DB_PRE.'ecp_forum_survey (`boardID`, `threadID`, `comID`, `ende`, `frage`, `antworten`) VALUES (%d, %d, %d, %d, \'%s\', %d)', (int)$_GET['boardID'], $thread, $comid, strtotime($_POST['ende']), strsave(htmlspecialchars($_POST['frage'])), (int)$_POST['antworten']))) {
										$sid = $db->last_id();
										$db->query('UPDATE '.DB_PRE.'ecp_forum_threads SET fsurveyID = '.$sid.' WHERE threadID = '.$thread);
										foreach($_POST as $key =>$value) {
											if(strpos($key,'answer_') !== false AND $value != '') {
												$db->query(sprintf('INSERT INTO '.DB_PRE.'ecp_forum_survey_answers (`fsID`, `answer`) VALUES (%d, \'%s\')', $sid, strsave(htmlspecialchars($value))));
											}
										}										
									} 
								}
							}
							setcookie('lastthread', time(), (time()+365*86400));
							unset($_SESSION['forum']['attach'][$bid]);
							header1('?section=forum&action=thread&boardID='.$bid.'&threadID='.$thread);
						}
					}
				}
			} else {
				$tpl = new smarty;
				ob_start();
				$tpl->assign('umfrage', find_access($board['startsurvey']));
				if($board['attachments'] AND $board['attachmaxsize']) {
					$rand =get_random_string(16, 2);
					$tpl->assign('attach', find_access($board['attachfiles']));
					$tpl->assign('maxsize', $board['attachmaxsize']);
					$tpl->assign('rand', $rand);
					$tpl->assign('sid', session_name().'='.session_id());	
					$tpl->assign('maxuploads', $board['attachments']);
					$tpl->assign('uploadinfo', str_replace(array('{anzahl}', '{max}'), array($board['attachments'], goodsize($board['attachmaxsize'])), FORUM_ATTACH_INFO));
					$_SESSION['forum']['attach'][$bid] = $rand;
				}
				$tpl->display(DESIGN.'/tpl/forum/new_thread'.((UPLOAD_METHOD == 'old' AND $board['attachments'] AND $board['attachmaxsize']) ? '_old' : '').'.html');
				$content = ob_get_contents();
				ob_end_clean();
				main_content(FORUM_NEW_TOPIC, $content, '',1);   						
			}
		}
	} else {
		table(ERROR, ACCESS_DENIED);	
	}
}
function forum_thread($bid, $id, $order = 'ASC', $quote = false) {
	global $db, $installed, $countries;
	$thread = $db->fetch_assoc('SELECT `threadID`, `bID`, `threadname`, `vonID`, '.DB_PRE.'ecp_forum_threads.posts, `sticky`, '.DB_PRE.'ecp_forum_threads.closed, 
											`fsurveyID`, `rating`, `ratingvotes`, a.boardparentID, a.name, a.isforum, a.closed as forumclosed,
											 a.rightsread, a.postcom, a.editcom, a.votesurvey, a.downloadattch, a.threadclose, 
											a.threaddel, a.threadmove, a.threadpin, a.editmocom, a.delcom, b.rightsread as parentRead, b.name as boardparentName FROM '.DB_PRE.'ecp_forum_threads LEFT JOIN '.DB_PRE.'ecp_forum_boards AS a ON (bID = a.boardID) LEFT JOIN '.DB_PRE.'ecp_forum_boards AS b ON (b.boardID = a.boardparentID) WHERE threadID = '.$id.' AND bID = '.$bid);
	if(isset($thread['threadID']) AND find_access($thread['rightsread']) AND find_access($thread['parentRead']) AND $thread['isforum']) {
		$comments = array();
		$db->query('UPDATE '.DB_PRE.'ecp_forum_threads SET views = views + 1 WHERE threadID = '.$id);
		$limits = get_sql_limit(($thread['posts']+1), LIMIT_FORUM_COMMENTS);
		$result = $db->query('SELECT `comID`, '.DB_PRE.'ecp_forum_comments.userID, `postname`, a.rID, rankname, iconname, `adatum`, `comment`, `edits`, `editdatum`, `edituserID`, '.DB_PRE.'ecp_forum_comments.IP, `attachs`, a.username, a.sex, a.signatur, a.country, comments, d.money, a.avatar, b.username as editfrom, lastklick as online 
							FROM `'.DB_PRE.'ecp_forum_comments` 
							LEFT JOIN '.DB_PRE.'ecp_user as a ON ('.DB_PRE.'ecp_forum_comments.userID = a.ID)
							LEFT JOIN '.DB_PRE.'ecp_user as b ON ('.DB_PRE.'ecp_forum_comments.edituserID = b.ID)
							LEFT JOIN '.DB_PRE.'ecp_user_stats as d ON ('.DB_PRE.'ecp_forum_comments.userID = d.userID)
							LEFT JOIN '.DB_PRE.'ecp_ranks ON (a.rID = rankID)
							LEFT JOIN '.DB_PRE.'ecp_online ON (uID = '.DB_PRE.'ecp_forum_comments.userID AND lastklick > '.(time()-SHOW_USER_ONLINE).')
							WHERE boardID = '.$bid.' AND tID = '.$id.'
							GROUP BY comID
							ORDER BY adatum '.$order.'
							LIMIT '.$limits[1].', '.LIMIT_FORUM_COMMENTS);
		while($row = mysql_fetch_assoc($result)) {			
			if(isset($_SESSION['userID'])) {
				if(isset($_SESSION['lastforum'][$id]) AND $_SESSION['lastforum'][$id] < $row['adatum']) { 
					$row['new'] = true;
				} elseif (!isset($_SESSION['lastforum'][$id]) AND $_SESSION['lastforum']['time'] < $row['adatum']) {
					$row['new'] = true;
				}
			}
			$row['adatum'] = forum_make_date($row['adatum']);			
			$row['nr'] = ++$limits[1];
			$row['comments'] = format_nr($row['comments']);
			$row['countryname'] = @$countries[$row['country']];
			$row['quote'] = $row['comment'];
			$row['comment'] = bb_code($row['comment']);			
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
		if(isset($_SESSION['userID'])) {
			if(!isset($_SESSION['lastforum'][$id])) @$_SESSION['lastforum']['boards'][$bid]['new']--;
			$_SESSION['lastforum']['boards'][$bid]['time'] = time();
			$_SESSION['lastforum'][$id] = time();
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
		$tpl->assign('bewertung', ($thread['ratingvotes'] != 0 ? str_replace(array('{anzahl}', '{avg}'), array(format_nr($thread['ratingvotes']), format_nr($thread['rating'],2)), FORUM_RATING_VAL) : FORUM_NO_RATINGS));
		if(!@$_SESSION['userID'] OR $db->result(DB_PRE.'ecp_forum_ratings', 'COUNT(rateID)', 'userID = '.$_SESSION['userID'].' AND tID = '.$id)) {
			$tpl->assign('rating', get_forum_rating($thread['rating']));	
		}	
		if($limits[0] > 1) {
			$seiten = makepagelink_ajax('?section=forum&action=thread&boardID='.$bid.'&threadID='.$id, 'return load_forum_com_page('.$id.', '.$bid.', {nr}, \''.$order.'\');', @$_GET['page'], $limits[0]);
			$tpl->assign('seiten',$seiten);
		}
		$tpl->assign('order', $order);
		$tpl->assign('vonID', $thread['vonID']);
		$tpl->assign('sticky',	$thread['sticky']);
		$tpl->assign('forenlinks', forum_get_fast_links());
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
		$tpl->assign('name', $thread['threadname']);
		$tpl->assign('abo', $db->result(DB_PRE.'ecp_forum_abo', 'COUNT(aboID)', 'userID = '.(int)@$_SESSION['userID'].' AND thID = '.$id));
		$tpl->assign('path', '<a href="?section=forum">'.FORUM.'</a> <img src="templates/'.DESIGN.'/images/pfeil_o.gif" alt="" /> '.($thread['boardparentID'] ? '<a href="?section=forum&action=subboard&boardID='.$thread['boardparentID'].'">'.$thread['boardparentName'].'</a> <img src="templates/'.DESIGN.'/images/pfeil_o.gif" alt="" /> ' : '').'<a href="?section=forum&amp;action=board&amp;boardID='.$bid.'">'.$thread['name'].'</a> <img src="templates/'.DESIGN.'/images/pfeil_o.gif" alt="" /> <img src="templates/'.DESIGN.'/images/forum_thread_pin.png" id="pin_icon" alt="'.FORUM_STICKY.'" title="'.FORUM_STICKY.'"'.($thread['sticky'] ? '' : ' style="display: none;"').' /> <img src="templates/'.DESIGN.'/images/forum_icon_thread_closed.png" id="closed_icon" alt="'.FORUM_THREAD_CLOSED.'" title="'.FORUM_THREAD_CLOSED.'"'.($thread['closed'] ? '' : ' style="display: none;"').' /> '.$thread['threadname']);
		$tpl->assign('members', substr($member, 2));
		$tpl->assign('thread',1);
		$tpl->assign('quote', $quote);
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
			if($umfrage['anzahl'] OR isset($_COOKIE['forum']['survey_'.$thread['fsurveyID']]) OR !find_access($thread['votesurvey']) OR $thread['closed'] OR ($umfrage['ende'] != 0 AND $umfrage['ende'] < time())) {
				$tpl->assign('abstimmen', false);
			} else {
				$tpl->assign('abstimmen', true);
			}			
		}
		$tpl->display(DESIGN.'/tpl/forum/board_head.html');
		$tpl->display(DESIGN.'/tpl/forum/thread_comments.html');
		$tpl->display(DESIGN.'/tpl/forum/board_footer.html');
		$content = ob_get_contents();
		ob_end_clean();
		main_content(FORUM, $content, '',1);  
	} else {
		table(ERROR, ACCESS_DENIED);
	}
}
function forum_survey_vote($id) {
	global $db;
	if(isset($_GET['ajax'])) {
		ob_end_clean();
		$db->setMode(0);
	}
	if(isset($_SESSION['userID'])) {
		$survey = $db->fetch_assoc('SELECT threadID, boardID, `ende`, `frage`, `antworten`, COUNT(voteID) AS anzahl FROM `'.DB_PRE.'ecp_forum_survey` LEFT JOIN '.DB_PRE.'ecp_forum_survey_votes ON (fsurID = '.$id.' AND userID = '.(int)@$_SESSION['userID'].') WHERE fsurveyID = '.$id.' GROUP BY fsurveyID');
	} else {
		$survey = $db->fetch_assoc('SELECT threadID, boardID, `ende`, `frage`, `antworten`, COUNT(voteID) AS anzahl FROM `'.DB_PRE.'ecp_forum_survey` LEFT JOIN '.DB_PRE.'ecp_forum_survey_votes ON (fsurID = '.$id.' AND IP = \''.$_SERVER['REMOTE_ADDR'].'\') WHERE fsurveyID = '.$id.' GROUP BY fsurveyID');				
	}
	$thread = $db->fetch_assoc('SELECT `threadID`, `bID`, '.DB_PRE.'ecp_forum_threads.closed, a.rightsread, a.votesurvey, b.rightsread as parentRead FROM '.DB_PRE.'ecp_forum_threads LEFT JOIN '.DB_PRE.'ecp_forum_boards AS a ON (bID = a.boardID) LEFT JOIN '.DB_PRE.'ecp_forum_boards AS b ON (b.boardID = a.boardparentID) WHERE threadID = '.$survey['threadID'].' AND bID = '.$survey['boardID']);
	if(find_access($thread['rightsread']) AND find_access($thread['parentRead']) AND find_access($thread['votesurvey'])) {
		if($survey['anzahl'] OR isset($_COOKIE['forum']['survey_'.$id]) OR !find_access($thread['votesurvey']) OR ($survey['ende'] != 0 AND $survey['ende'] < time())) {
			if(isset($_GET['ajax'])) {
				echo SURVEY_NOT_AKTIV;
			} else {
				table(ERROR, SURVEY_NOT_AKTIV);
				survey();
			}
		} else {
			if($survey['antworten'] == 1) {
				$aid = (int)@$_POST['answer'];
				if($aid) {
					if($db->result(DB_PRE.'ecp_forum_survey_answers', 'COUNT(answerID)', 'fsID = '.$id.' AND answerID = '.$aid)) {
						if($db->query('UPDATE '.DB_PRE.'ecp_forum_survey_answers SET votes = votes+1 WHERE fsID = '.$id.' AND answerID = '.$aid)) {
							$db->query('INSERT INTO '.DB_PRE.'ecp_forum_survey_votes (`fsurID`, `userID`, `IP`, `votedatum`) VALUES ('.$id.', '.(int)@$_SESSION['userID'].', \''.$_SERVER['REMOTE_ADDR'].'\', '.time().')');
							setcookie("forum[survey_$id]", time(), (time()+365*86400));
							if(isset($_GET['ajax'])) {
								echo 'ok';
							} else {
								header1('?section=forum&action=thread&boardID='.$survey['boardID'].'&threadID='.$survey['threadID']);
							}	
						}
					} else {
						if(isset($_GET['ajax'])) {
							echo SURVEY_CHOOSE_EQAL_ID;
						} else {
							table(ERROR, SURVEY_CHOOSE_EQAL_ID);
							forum_thread($survey['boardID'], $survey['threadID']);
						}	
					}	
				} else {
					if(isset($_GET['ajax'])) {
						echo SURVEY_MAKE_A_CHOOSE;
					} else {
						table(ERROR, SURVEY_MAKE_A_CHOOSE);
						forum_thread($survey['boardID'], $survey['threadID']);
					}
				}
			} else {
				$db->query('SELECT answerID FROM '.DB_PRE.'ecp_forum_survey_answers WHERE fsID = '.$id);
				$answers = array();
				while($row = $db->fetch_assoc()) {
					$answers[] = $row['answerID'];
				}
				$antworten = '';
				foreach($_POST AS $key =>$value) {
					if(strpos($key, 'answer_') !== false) {
						$key = (int)substr($key, strpos($key, '_' )+1);
						if(in_array($key, $answers)) {
							@$antworten .= ' OR answerID = '.$key;
							@$gesamt++;
						}
					}
				}
				if($gesamt > $survey['antworten']) {
					if(isset($_GET['ajax'])) {
						echo str_replace('{anzahl}', $survey['antworten'], SURVEY_TOO_MANY);
					} else {
						table(ERROR, str_replace('{anzahl}', $survey['antworten'], SURVEY_TOO_MANY));
						forum_thread($survey['boardID'], $survey['threadID']);
					}
				} elseif(strlen($antworten)) {
					if($db->query('UPDATE '.DB_PRE.'ecp_forum_survey_answers SET votes = votes+1 WHERE fsID = '.$id.' AND ('.substr($antworten, 4).')')) {
						$db->query('INSERT INTO '.DB_PRE.'ecp_forum_survey_votes (`fsurID`, `userID`, `IP`, `votedatum`) VALUES ('.$id.', '.(int)@$_SESSION['userID'].', \''.$_SERVER['REMOTE_ADDR'].'\', '.time().')');
						setcookie("forum[survey_$id]", time(), (time()+365*86400));
						if(isset($_GET['ajax'])) {
							echo 'ok';
						} else {
							header1('?section=forum&action=thread&boardID='.$survey['boardID'].'&threadID='.$survey['threadID']);
						}	
					}
				} else {
					if(isset($_GET['ajax'])) {
						echo SURVEY_MAKE_A_CHOOSE;
					} else {
						table(ERROR, SURVEY_MAKE_A_CHOOSE);
						forum_thread($survey['boardID'], $survey['threadID']);
					}
				}
			}			
		}
	}
	if(isset($_GET['ajax'])) {
		die();
	}
}
function forum_get_file($id, $bid, $tid, $cid) {
	global $db;
	$thread = $db->fetch_assoc('SELECT a.rightsread, a.downloadattch, b.rightsread as parentRead FROM '.DB_PRE.'ecp_forum_boards as a LEFT JOIN '.DB_PRE.'ecp_forum_boards AS b ON (b.boardID = a.boardparentID) WHERE a.boardID = '.$bid);
	if(find_access($thread['rightsread']) AND find_access($thread['parentRead']) AND find_access($thread['downloadattch'])) {
		$file = $db->fetch_assoc('SELECT name, size, strname FROM '.DB_PRE.'ecp_forum_attachments WHERE bID = '.$bid.' AND tID = '.$tid.' AND mID = '.$cid.' AND attachID = '.$id);
		if(isset($file['name']) AND file_exists('uploads/forum/'.$id.'_'.$file['strname'])) {
			$db->query('UPDATE '.DB_PRE.'ecp_forum_attachments SET downloads = downloads +1 WHERE attachID ='.$id);
			error_reporting(0);
			ob_end_clean();
			header("Content-Type: ".getMimeType('uploads/forum/'.$id.'_'.$file['strname'], $file['name']));
			header("Content-Disposition: attachment; filename=".$file['name']);
			header("Content-Length: ".$file['size']);
			readfile('uploads/forum/'.$id.'_'.$file['strname']);
			die();
		} else {
			table(ERROR, FILE_NOT_FOUND);
			forum_thread($bid, $id);
		}
	} else {
		table(ERROR, ACCESS_DENIED);
		forum_thread($bid, $tid);
	}
}
function forum_new_replay($bid, $id) {
	global $db;
	$thread = $db->fetch_assoc('SELECT `threadID`, `bID`, `threadname`, a.boardparentID, '.DB_PRE.'ecp_forum_threads.closed, 
									    a.rightsread, a.commentsperpost, a.moneyperpost, a.boardparentID, a.name, a.attachments, a.attachmaxsize, a.postcom, a.attachfiles, b.rightsread as parentRead FROM '.DB_PRE.'ecp_forum_threads LEFT JOIN '.DB_PRE.'ecp_forum_boards AS a ON (bID = a.boardID) LEFT JOIN '.DB_PRE.'ecp_forum_boards AS b ON (b.boardID = a.boardparentID) WHERE threadID = '.$id.' AND bID = '.$bid);
	if(find_access($thread['rightsread']) AND find_access($thread['parentRead']) AND find_access($thread['postcom'])) {
		if($thread['closed']) {
			table(INFO, FORUM_THREAD_CLOSED);
		} else {		
			if(isset($_POST['comment'])) {
				if(isset($_SESSION['userID'])) {
					$last = (int)@$db->result(DB_PRE.'ecp_forum_comments', 'adatum', 'userID = '.$_SESSION['userID'].' AND tID = '.$id.' ORDER BY adatum DESC LIMIT 1');
				} else {
					$last = (int)@$db->result(DB_PRE.'ecp_forum_comments', 'adatum', 'IP = \''.$_SESSION['userID'].'\'  AND tID = '.$id.'  ORDER BY adatum DESC LIMIT 1');
					if(isset($_COOKIE['lastcomment'])) {
						if($last < $_COOKIE['lastcomment']) {
							$last = $_COOKIE['lastcomment'];
						}
					} 
				}				
				if($_POST['comment'] == '') {
					table(ERROR, NOT_NEED_ALL_INPUTS);
					$tpl = new smarty;
					ob_start();
					foreach($_POST AS $Key =>$value) {
						$tpl->assign($Key, $value);
					}
					if($thread['attachments'] AND $thread['attachmaxsize']) {
						$rand =get_random_string(16, 2);
						$tpl->assign('attach', find_access($thread['attachfiles']));
						$tpl->assign('maxsize', $thread['attachmaxsize']);
						$tpl->assign('rand', $rand);
						$tpl->assign('sid', session_name().'='.session_id());	
						$tpl->assign('maxuploads', $thread['attachments']);
						$tpl->assign('uploadinfo', str_replace(array('{anzahl}', '{max}'), array($thread['attachments'], goodsize($thread['attachmaxsize'])), FORUM_ATTACH_INFO));
						$_SESSION['forum']['attach'][$bid] = $rand;
					}
					$tpl->assign('quote', true);
					$tpl->display(DESIGN.'/tpl/forum/comments_add_edit'.((UPLOAD_METHOD == 'old' AND $thread['attachments'] AND $thread['attachmaxsize']) ? '_old' : '').'.html');
					$content = ob_get_contents();
					ob_end_clean();
					main_content(FORUM_POST_REPLAY, $content, '',1);   	
				} elseif (!isset($_SESSION['userID']) AND (strtolower($_SESSION['captcha']) != strtolower($_POST['captcha']) OR $_SESSION['captcha'] == '')) {
					table(ERROR, CAPTCHA_WRONG);
					$tpl = new smarty;
					ob_start();
					foreach($_POST AS $Key =>$value) {
						$tpl->assign($Key, $value);
					}
					if($thread['attachments'] AND $thread['attachmaxsize']) {
						$rand =get_random_string(16, 2);
						$tpl->assign('attach', find_access($thread['attachfiles']));
						$tpl->assign('maxsize', $thread['attachmaxsize']);
						$tpl->assign('rand', $rand);
						$tpl->assign('sid', session_name().'='.session_id());	
						$tpl->assign('maxuploads', $thread['attachments']);
						$tpl->assign('uploadinfo', str_replace(array('{anzahl}', '{max}'), array($thread['attachments'], goodsize($thread['attachmaxsize'])), FORUM_ATTACH_INFO));
						$_SESSION['forum']['attach'][$bid] = $rand;
					}
					$tpl->assign('quote', true);
					$tpl->display(DESIGN.'/tpl/forum/comments_add_edit'.((UPLOAD_METHOD == 'old' AND $thread['attachments'] AND $thread['attachmaxsize']) ? '_old' : '').'.html');
					$content = ob_get_contents();
					ob_end_clean();
					main_content(FORUM_POST_REPLAY, $content, '',1);  
				} elseif($last > time()-SPAM_FORUM_COMMENTS) {
					table(SPAM_PROTECTION, str_replace(array('{sek}', '{zeit}'), array(SPAM_FORUM_COMMENTS, ($last+SPAM_FORUM_COMMENTS-time())), SPAM_PROTECTION_MSG));
					$tpl = new smarty;
					ob_start();
					foreach($_POST AS $Key =>$value) {
						$tpl->assign($Key, $value);
					}
					if($thread['attachments'] AND $thread['attachmaxsize']) {
						$rand =get_random_string(16, 2);
						$tpl->assign('attach', find_access($thread['attachfiles']));
						$tpl->assign('maxsize', $thread['attachmaxsize']);
						$tpl->assign('rand', $rand);
						$tpl->assign('quote', true);
						$tpl->assign('sid', session_name().'='.session_id());	
						$tpl->assign('maxuploads', $thread['attachments']);
						$tpl->assign('uploadinfo', str_replace(array('{anzahl}', '{max}'), array($thread['attachments'], goodsize($thread['attachmaxsize'])), FORUM_ATTACH_INFO));
						$_SESSION['forum']['attach'][$bid] = $rand;
					}
					$tpl->assign('quote', true);
					$tpl->display(DESIGN.'/tpl/forum/comments_add_edit'.((UPLOAD_METHOD == 'old' AND $thread['attachments'] AND $thread['attachmaxsize']) ? '_old' : '').'.html');
					$content = ob_get_contents();
					ob_end_clean();
					main_content(FORUM_POST_REPLAY, $content, '',1);  						
				} else {
					if($db->query(sprintf('INSERT INTO '.DB_PRE.'ecp_forum_comments (`tID`, `boardID`, `userID`, `postname`, `adatum`, `comment`, `IP`) VALUES (%d, %d, %d, \'%s\', %d, \'%s\', \'%s\')', $id, $thread['bID'], @(int)$_SESSION['userID'], strsave(htmlspecialchars(@$_POST['username'])),time(), strsave(comment_save($_POST['comment'])), $_SERVER['REMOTE_ADDR']))) {																			
						$comid = $db->last_id();
						if(isset($_SESSION['userID'])) {
							$db->query('UPDATE '.DB_PRE.'ecp_user_stats SET comments = comments + '.$thread['commentsperpost'].', money = money + '.$thread['moneyperpost'].' WHERE userID = '.$_SESSION['userID']);
							update_rank($_SESSION['userID']);
						}
						$db->query('UPDATE '.DB_PRE.'ecp_forum_boards SET posts = posts +1, lastpost = '.time().', lastthreadID = '.$id.', lastpostuserID = '.(int)@$_SESSION['userID'].', lastpostuser = \''.strsave(htmlspecialchars(@$_POST['username'])).'\' WHERE boardID = '.$thread['bID'].' OR boardID = '.$thread['boardparentID']);
						$db->query('UPDATE '.DB_PRE.'ecp_forum_threads SET posts = posts +1, lastreplay = '.time().', lastuserID = '.(int)@$_SESSION['userID'].', lastusername = \''.strsave(htmlspecialchars(@$_POST['username'])).'\' WHERE threadID = '.$id);
						if(find_access($thread['attachfiles'])) {
							if(UPLOAD_METHOD == 'old') {
								$maxattach = $thread['attachments'];
								foreach($_FILES AS $key=>$value) {
									if($_FILES[$key] == '' OR $maxattach <= 0 OR $_FILES[$key]['size'] > $thread['attachmaxsize']) continue;
									$mine = getMimeType($_FILES[$key]['tmp_name'], $_FILES[$key]['name']);
									if($mine == 'application/zip' OR $mine == 'application/x-rar-compressed' OR $mine == 'image/bmp' OR $mine == 'image/gif' OR $mine == 'image/jpeg' OR $mine == 'image/png' OR $mine == 'application/pdf' OR $mine == 'text/plain' OR $mine == 'text/css' OR $mine == 'text/html') {
										$sha1 = sha1_file($_FILES[$key]['tmp_name']);
										if($db->query(sprintf('INSERT INTO '.DB_PRE.'ecp_forum_attachments (`bID`, `userID`, `name`, `size`, `strname`, uploadzeit, IP, tID, mID) VALUES (%d, %d, \'%s\', %d, \'%s\', %d, \'%s\', %d, %d)', $bid, @(int)$_SESSION['userID'], strsave($_FILES[$key]['name']), (int)$_FILES[$key]['size'], $sha1, time(), $_SERVER['REMOTE_ADDR'], $id, $comid))) {
											move_uploaded_file($_FILES[$key]['tmp_name'], 'uploads/forum/'.$db->last_id().'_'.$sha1);
											umask(0);
											chmod('uploads/forum/'.$db->last_id().'_'.$sha1, CHMOD);
											$db->query('UPDATE '.DB_PRE.'ecp_forum_threads SET anhaenge = 1 WHERE threadID = '.$id);
											$db->query('UPDATE '.DB_PRE.'ecp_forum_comments SET attachs = 1 WHERE comID = '.$comid);											
										} 
										$maxattach--;
									} 																		
								}
							} else {
								$db->query(sprintf('UPDATE '.DB_PRE.'ecp_forum_attachments SET `tID` = %d, `mID` = %d WHERE validation = \'%s\' AND bID = %d', $id, $comid, strsave($_GET['rand']),$bid));
								if($db->affekt_rows()) {
									$db->query('UPDATE '.DB_PRE.'ecp_forum_threads SET anhaenge = 1 WHERE threadID = '.$id);
									$db->query('UPDATE '.DB_PRE.'ecp_forum_comments SET attachs = 1 WHERE comID = '.$comid);
								}
							}
						}
						$db->query('SELECT * FROM '.DB_PRE.'ecp_texte WHERE name = "THREAD_ABO"');
						$text = array();
						while($row = $db->fetch_assoc()) {
							$text[$row['lang']] = $row;								
						}
						$anzahl = $db->result(DB_PRE.'ecp_forum_comments', 'COUNT(comID)', 'tID = '.$id.' AND boardID ='.$bid);
					 	$link = SITE_URL.'?section=forum&action=thread&boardID='.$bid.'&threadID='.$id.'&page='.(ceil(($anzahl-1)/LIMIT_FORUM_COMMENTS)+1).'#com_'.$comid;
						$result = $db->query('SELECT country, username, email, threadname FROM '.DB_PRE.'ecp_forum_abo LEFT JOIN '.DB_PRE.'ecp_user ON (userID = ID) LEFT JOIN '.DB_PRE.'ecp_forum_threads ON (threadID = thID) WHERE userID != '.(int)@$_SESSION['userID'].' AND thID = '.$id.' AND boID = '.$bid);
						while($row = $db->fetch_assoc()) {
							$search = array('{username}', '{link}', '{threadname}');
							$replace = array($row['username'], $link, $row['threadname']);
							if(!isset($text[$row['country']]))	$row['country'] = 'de';
							send_email($row['email'], $text[$row['country']]['content2'], str_replace($search, $replace,  $text[$row['country']]['content']),0);						
						}
						unset($_SESSION['forum']['attach'][$bid]);
						setcookie('lastcomment', time(), (time()+365*86400));
						forum_goto_last($bid, $id);
					}
				}
			} else {
				$tpl = new smarty;
				ob_start();
				if($thread['attachments'] AND $thread['attachmaxsize']) {
					$rand =get_random_string(16, 2);
					$tpl->assign('attach', find_access($thread['attachfiles']));
					$tpl->assign('maxsize', $thread['attachmaxsize']);
					$tpl->assign('rand', $rand);
					$tpl->assign('sid', session_name().'='.session_id());	
					$tpl->assign('maxuploads', $thread['attachments']);
					$tpl->assign('uploadinfo', str_replace(array('{anzahl}', '{max}'), array($thread['attachments'], goodsize($thread['attachmaxsize'])), FORUM_ATTACH_INFO));
					$_SESSION['forum']['attach'][$bid] = $rand;
				}
				$tpl->display(DESIGN.'/tpl/forum/comments_add_edit'.((UPLOAD_METHOD == 'old' AND $thread['attachments'] AND $thread['attachmaxsize']) ? '_old' : '').'.html');
				$content = ob_get_contents();
				ob_end_clean();
				main_content(FORUM_POST_REPLAY, $content, '',1);   
				forum_thread($bid, $id, 'DESC', true);						
			}
		}
	} else {
		table(ERROR, ACCESS_DENIED);	
	}
}
function forum_edit_replay($id, $bid, $tid) {
	global $db;
	$thread = $db->fetch_assoc('SELECT `threadID`, `bID`, `threadname`, a.boardparentID, '.DB_PRE.'ecp_forum_threads.closed,userID, comment, attachs,postname, adatum, 
									    a.editcom,a.editmocom,a.rightsread, a.commentsperpost, a.moneyperpost, a.boardparentID, 
									    a.name, a.attachments, a.attachmaxsize, a.postcom, a.attachfiles, b.rightsread as parentRead 
									    FROM '.DB_PRE.'ecp_forum_threads 
									    LEFT JOIN '.DB_PRE.'ecp_forum_boards AS a ON (bID = a.boardID) 
									    LEFT JOIN '.DB_PRE.'ecp_forum_boards AS b ON (b.boardID = a.boardparentID) 
									    LEFT JOIN '.DB_PRE.'ecp_forum_comments ON (comID = '.$id.')
									    WHERE threadID = '.$tid.' AND bID = '.$bid);
	if(@$_SESSION['userID'] AND find_access($thread['rightsread']) AND find_access($thread['parentRead']) AND ((find_access($thread['editcom']) AND $_SESSION['userID'] == $thread['userID']) OR find_access($thread['editmocom'])) AND $db->errorNum() == 0) {
		if(isset($_POST['comment'])) {
			if($_POST['comment'] == '' OR (isset($_POST['username']) AND $_POST['username'] == '') OR (isset($_POST['title']) AND $_POST['title'] == '')) {
				table(ERROR, NOT_NEED_ALL_INPUTS);
				$tpl = new smarty;
				$tpl->assign('func', 'edit');
				$tpl->assign('func2', '&comID='.$id);
				$tpl->assign('comment', $_POST['comment']);
				if($db->result(DB_PRE.'ecp_forum_comments', 'COUNT(comID)', 'tID = '.$tid.' AND adatum < '.$thread['adatum'].' ORDER BY adatum ASC') == 0) {
					$tpl->assign('title', $thread['threadname']);
				}
				if($thread['userID'] == 0) {
					$tpl->assign('username', $thread['postname']);
				}
				ob_start();
				if($thread['attachments'] AND $thread['attachmaxsize']) {
					$attachs = $db->result(DB_PRE.'ecp_forum_attachments', 'COUNT(attachID)', 'mID = '.$id.' AND tID = '.$tid);
					if($thread['attachments'] > $attachs) {
						$rand =get_random_string(16, 2);
						$tpl->assign('attach', find_access($thread['attachfiles']));
						$tpl->assign('maxsize', $thread['attachmaxsize']);
						$tpl->assign('rand', $rand);
						$tpl->assign('sid', session_name().'='.session_id());	
						$tpl->assign('maxuploads', ($thread['attachments'] - $attachs));
						$tpl->assign('uploadinfo', str_replace(array('{anzahl}', '{max}'), array(($thread['attachments'] - $attachs), goodsize($thread['attachmaxsize'])), FORUM_ATTACH_INFO));
						$_SESSION['forum']['attach'][$bid] = $rand;
					}
				}
				$tpl->assign('quote', true);
				$tpl->display(DESIGN.'/tpl/forum/comments_add_edit'.((UPLOAD_METHOD == 'old' AND $thread['attachments'] AND $thread['attachmaxsize']) ? '_old' : '').'.html');
				$content = ob_get_contents();
				ob_end_clean();
				main_content(FORUM_POST_EDIT, $content, '',1);   	  	 	
			} else {
				if($db->query(sprintf('UPDATE '.DB_PRE.'ecp_forum_comments SET postname = \'%s\', comment = \'%s\', edits =edits +1, editdatum = %d, edituserID = %d WHERE comID = %d', strsave(htmlspecialchars(@$_POST['username'])), strsave(comment_save($_POST['comment'])), time(), @(int)$_SESSION['userID'], $id))) {																			
					if(find_access($thread['attachfiles'])) {
						if(UPLOAD_METHOD == 'old') {
							$maxattach = $thread['attachments']-$db->result(DB_PRE.'ecp_forum_attachments', 'COUNT(attachID)', 'bID = '.$bid.' AND mID = '.$id);
							foreach($_FILES AS $key=>$value) {
								if($_FILES[$key] == '' OR $maxattach <= 0 OR $_FILES[$key]['size'] > $thread['attachmaxsize']) continue;
								$mine = getMimeType($_FILES[$key]['tmp_name'], $_FILES[$key]['name']);
								if($mine == 'application/zip' OR $mine == 'application/x-rar-compressed' OR $mine == 'image/bmp' OR $mine == 'image/gif' OR $mine == 'image/jpeg' OR $mine == 'image/png' OR $mine == 'application/pdf' OR $mine == 'text/plain' OR $mine == 'text/css' OR $mine == 'text/html') {
									$sha1 = sha1_file($_FILES[$key]['tmp_name']);
									if($db->query(sprintf('INSERT INTO '.DB_PRE.'ecp_forum_attachments (`bID`, `userID`, `name`, `size`, `strname`, uploadzeit, IP, tID, mID) VALUES (%d, %d, \'%s\', %d, \'%s\', %d, \'%s\', %d, %d)', $bid, @(int)$_SESSION['userID'], strsave($_FILES[$key]['name']), (int)$_FILES[$key]['size'], $sha1, time(), $_SERVER['REMOTE_ADDR'], $tid, $id))) {
										move_uploaded_file($_FILES[$key]['tmp_name'], 'uploads/forum/'.$db->last_id().'_'.$sha1);
										umask(0);
										chmod('uploads/forum/'.$db->last_id().'_'.$sha1, CHMOD);
										$db->query('UPDATE '.DB_PRE.'ecp_forum_threads SET anhaenge = 1 WHERE threadID = '.$tid);
										$db->query('UPDATE '.DB_PRE.'ecp_forum_comments SET attachs = 1 WHERE comID = '.$id);											
									} 
									$maxattach--;
								} 																		
							}
						} else {
							$db->query(sprintf('UPDATE '.DB_PRE.'ecp_forum_attachments SET `tID` = %d, `mID` = %d WHERE validation = \'%s\' AND bID = %d', $id, $comid, strsave($_GET['rand']),$bid));
							if($db->affekt_rows()) {
								$db->query('UPDATE '.DB_PRE.'ecp_forum_threads SET anhaenge = 1 WHERE threadID = '.$id);
								$db->query('UPDATE '.DB_PRE.'ecp_forum_comments SET attachs = 1 WHERE comID = '.$comid);
							}
						}
					}
					if($db->result(DB_PRE.'ecp_forum_comments', 'COUNT(comID)', 'tID = '.$tid.' AND adatum < '.$thread['adatum'].' ORDER BY adatum ASC') == 0) {
						$db->query('UPDATE '.DB_PRE.'ecp_forum_threads SET threadname = \''.strsave(htmlspecialchars($_POST['title'])).'\', vonname = \''.strsave(htmlspecialchars(@$_POST['username'])).'\' WHERE threadID = '.$tid);
					}
					$last = $db->fetch_assoc('SELECT userID,postname,adatum, tID FROM '.DB_PRE.'ecp_forum_comments WHERE boardID = '.$bid.' ORDER BY adatum DESC LIMIT 1');
					$db->query('UPDATE '.DB_PRE.'ecp_forum_boards SET `lastpostuserID` =  '.(int)$last['userID'].', `lastpostuser` = \''.$last['postname'].'\', `lastpost` = '.(int)$last['adatum'].', lastthreadID = '.(int)$last['tID'].' WHERE (boardID = '.$bid.' OR boardID = '.$thread['boardparentID'].')');					 						
					$last = $db->fetch_assoc('SELECT userID,postname,adatum FROM '.DB_PRE.'ecp_forum_comments WHERE tID = '.$tid.' ORDER BY adatum DESC LIMIT 1');
					$db->query('UPDATE '.DB_PRE.'ecp_forum_threads SET `lastuserID` =  '.$last['userID'].', `lastusername` = \''.$last['postname'].'\', `lastreplay` = '.$last['adatum'].' WHERE threadID = '.$tid);
					unset($_SESSION['forum']['attach'][$bid]);
					$anzahl = $db->result(DB_PRE.'ecp_forum_comments', 'COUNT(comID)', 'tID = '.$tid.' AND boardID ='.$bid.' AND adatum < '.$thread['adatum']);
					header1('?section=forum&action=thread&boardID='.$bid.'&threadID='.$tid.'&page='.(ceil(($anzahl-1)/LIMIT_FORUM_COMMENTS)+1).'#com_'.$id);
				}
			}
		} else {
			$tpl = new smarty;
			$tpl->assign('comment', htmlspecialchars($thread['comment']));
			$tpl->assign('func', 'edit');
			$tpl->assign('func2', '&comID='.$id);
			if($db->result(DB_PRE.'ecp_forum_comments', 'COUNT(comID)', 'tID = '.$tid.' AND adatum < '.$thread['adatum'].' ORDER BY adatum ASC') == 0) {
				$tpl->assign('title', $thread['threadname']);
			}
			if($thread['userID'] == 0) {
				$tpl->assign('username', $thread['postname']);
			}
			ob_start();
			if($thread['attachments'] AND $thread['attachmaxsize']) {
				$attachs = $db->result(DB_PRE.'ecp_forum_attachments', 'COUNT(attachID)', 'mID = '.$id.' AND tID = '.$tid);
				if($thread['attachments'] > $attachs) {
					$rand =get_random_string(16, 2);
					$tpl->assign('attach', find_access($thread['attachfiles']));
					$tpl->assign('maxsize', $thread['attachmaxsize']);
					$tpl->assign('rand', $rand);
					$tpl->assign('sid', session_name().'='.session_id());	
					$tpl->assign('maxuploads', ($thread['attachments'] - $attachs));
					$tpl->assign('uploadinfo', str_replace(array('{anzahl}', '{max}'), array(($thread['attachments'] - $attachs), goodsize($thread['attachmaxsize'])), FORUM_ATTACH_INFO));
					$_SESSION['forum']['attach'][$bid] = $rand;
				}
			}
			$tpl->assign('quote', true);
			$tpl->display(DESIGN.'/tpl/forum/comments_add_edit'.((UPLOAD_METHOD == 'old' AND $thread['attachments'] AND $thread['attachmaxsize']) ? '_old' : '').'.html');
			$content = ob_get_contents();
			ob_end_clean();
			main_content(FORUM_POST_EDIT, $content, '',1);   						
		}
	} else {
		table(ERROR, ACCESS_DENIED);	
	}
}
function forum_goto_last($bid,$id) {
	global $db;
	$anzahl = $db->result(DB_PRE.'ecp_forum_comments', 'COUNT(comID)', 'tID = '.$id.' AND boardID ='.$bid);
	$cid = $db->result(DB_PRE.'ecp_forum_comments', 'comID', 'tID = '.$id.' AND boardID ='.$bid.' ORDER BY adatum DESC LIMIT 1');
	header1('?section=forum&action=thread&boardID='.$bid.'&threadID='.$id.'&page='.(ceil($anzahl/LIMIT_FORUM_COMMENTS)).'#com_'.$cid);
}
function forum_goto_new($bid,$id) {
	global $db;
	if(isset($_SESSION['userID'])) {
		if(isset($_SESSION['lastforum'][$id])) {
			$last = $_SESSION['lastforum'][$id];
		} else {
			$last = $_SESSION['lastforum']['time'];
		}
		$anzahl = $db->result(DB_PRE.'ecp_forum_comments', 'COUNT(comID)', 'tID = '.$id.' AND boardID ='.$bid.' AND adatum < '.$last);
		$cid = $db->result(DB_PRE.'ecp_forum_comments', 'comID', 'tID = '.$id.' AND boardID ='.$bid.' AND adatum > '.$last.' ORDER BY adatum ASC LIMIT 1');
		header1('?section=forum&action=thread&boardID='.$bid.'&threadID='.$id.'&page='.(ceil(($anzahl+1)/LIMIT_FORUM_COMMENTS)).'#com_'.$cid);
	} else {
		forum_goto_last($bid, $id);
	}
}
function forum_get_fast_links() {
	global $db;
	$db->query('SELECT `boardID`, `boardparentID`, `name`, `isforum`
							FROM '.DB_PRE.'ecp_forum_boards 
							WHERE rightsread = "" OR '.str_replace('access', 'rightsread', $_SESSION['access_search']).'
							ORDER BY boardparentID, posi ASC');
	$boards = array();
	while($row = $db->fetch_assoc()) {
		if($row['isforum'] == 0 OR $row['boardparentID'] == 0) {
			$boards[$row['boardID']]['name'] = $row['name'];
			$boards[$row['boardID']]['isforum'] = $row['isforum'];
		} else {
			$boards[$row['boardparentID']]['subs'][$row['boardID']]['name'] = $row['name'];			    
		}
	}
	$links = '<option value="index">'.FORUM_BOARD_INDEX.'</option><option value="-1">------------------------</option>';
	foreach($boards AS $key=>$value) {
		$links .= '<option '.(@$_GET['boardID'] == $key ? 'selected="selected"' : '').'value="'.$key.($value['isforum'] == 1 ? '' : '_sub').'">'.$value['name'].'</option>';
		if(isset($value['subs'])) {
			foreach($value['subs'] AS $key1=>$value1) {
				$links .= '<option '.(@$_GET['boardID'] == $key1 ? 'selected="selected"' : '').'value="'.$key1.'">|- '.$value1['name'].'</option>';
			}
		}
	}
	return $links;
}
function forum_search($id) {
	global $db;
	if($id) {
        $sql = 'SELECT `tID`, `bID`, `comID`, com.userID, `postname`, `adatum`, `comment`, u1.username, 
                      				`edits`, `editdatum`, `edituserID`, com.IP, `attachs`, `datum`, 
                       				`threadname`, `vonID`, `vonname`, `views`, c.posts, `lastuserID`, 
                       				`lastusername`, `lastreplay`, `sticky`, c.closed, `fsurveyID`, 
                       				`anhaenge`, `rating`, `ratingvotes`, a.boardparentID, 
                       				a.name, a.rightsread, b.rightsread as parentRead, b.name as boardparentname, 
                       				u1.sex, u1.signatur, u1.country, comments, money, u1.avatar, u2.username as editfrom, lastklick as online  
                       				FROM '.DB_PRE.'ecp_forum_comments as com 
									LEFT JOIN '.DB_PRE.'ecp_user as u1 ON (com.userID = u1.ID)
									LEFT JOIN '.DB_PRE.'ecp_user as u2 ON (com.edituserID = u2.ID)
									LEFT JOIN '.DB_PRE.'ecp_user_stats ON (com.userID = '.DB_PRE.'ecp_user_stats.userID)
									LEFT JOIN '.DB_PRE.'ecp_online ON (uID = com.userID AND lastklick > '.(time()-SHOW_USER_ONLINE).')                       				
                       				LEFT JOIN '.DB_PRE.'ecp_forum_threads AS c ON (tID = threadID) 
                       				LEFT JOIN '.DB_PRE.'ecp_forum_boards AS a ON (a.boardID = bID) 
                       				LEFT JOIN '.DB_PRE.'ecp_forum_boards AS b ON (a.boardparentID = b.boardID) 
                        			WHERE (a.rightsread = "" OR '.str_replace('access', 'a.rightsread', $_SESSION['access_search']).') AND (a.boardparentID = 0 OR b.rightsread = "" OR '.str_replace('access', 'b.rightsread', $_SESSION['access_search']).')
                        			AND com.userID = '.$id.' GROUP BY comID';
		$db->query($sql);
		if($db->num_rows()) {
			if($db->query(sprintf('INSERT INTO '.DB_PRE.'ecp_forum_search
												(`userID`, `IP`, `SID`, `datum`, `stichwort`, `suchart`, `fromusername`, 
												`usersuchart`, `foren`, `alterart`, `altervalue`, `sortart`, `sortorder`, `sqlquery`, viewas) VALUES  (
												%d, \'%s\', \'%s\', %d, \'%s\', %d, \'%s\', %d, \'%s\', \'%s\', %d, \'%s\', \'%s\', \'%s\', \'%s\' )',
							@$_SESSION['userID'], strsave($_SERVER['REMOTE_ADDR']), session_id(), time(), '',
							1, $db->result(DB_PRE.'ecp_user', 'username', 'ID ='.$id), 1, '', 
							'=>', 0, 'adatum', 'DESC', strsave($sql), 'comments'))) {
				header1('?section=forum&action=searchresults&id='.$db->last_id());
			}
		} else {
			table(ERROR, FORUM_SEARCH_NO_RESULTS);
			unset($_POST['submit']);
			forum_search(0);
		}
} else {
		if(isset($_POST['submit'])) {
			$_POST['username'] = str_replace(array('&feld&', '&feld2&'), '', $_POST['username']);
			$_POST['stichwort'] = str_replace(array('&feld&', '&feld2&'), '', $_POST['stichwort']);
			IF($_POST['stichwort'] == '' AND $_POST['username'] == '') {
				table(ERROR, NOT_NEED_ALL_INPUTS);
				unset($_POST['submit']);
				forum_search(0);
			} else if (strlen($_POST['stichwort']) < 3 AND $_POST['username'] == '') {
				table(ERROR, FORUM_SEARCH_MIN_3_CHARS);
				unset($_POST['submit']);
				forum_search(0);				
			} else {
			   if(count(@$_POST['foren'])) {
					foreach($_POST['foren'] AS $value) {
						@$boards .= ' OR a.boardID = '.(int)$value.' OR a.boardparentID = '.(int)$value;
					}
					$boardids .= ','.(int)$value;
					$boards = '('.substr($boards, 4).') AND  ';
			   }
			   @$boards .= '(a.rightsread = "" OR '.str_replace('access', 'a.rightsread', $_SESSION['access_search']).') AND (a.boardparentID = 0 OR b.rightsread = "" OR '.str_replace('access', 'b.rightsread', $_SESSION['access_search']).') AND';
			   ($_POST['alterart'] == '>=') ? '' : $_POST['alterart'] = '<=';
			   ($_POST['sortorder'] == 'DESC') ? '' : $_POST['sortorder'] = 'ASC';
			   switch($_POST['sortart']) {
			   		case 'adatum':
			   		break;
			   		case 'threadname':
			   		break;
			   		case 'posts':
			   			$_POST['sortart'] = 'c.posts';
			   		break;
			   		case 'views':
			   		break;
			   		case 'datum':
			   		break;
			   		case 'name':
			   		break;	
			   		case 'rating':
			   		break;
			   		default:
			   			$_POST['sortart'] = 'adatum';
			   }
               IF($_POST['altervalue'] >= 1 AND $_POST['suchart'] == 1) {
                   $addsearch = ' AND adatum '.$_POST['alterart'].' ';
                   $addsearch .= time()-((int)$_POST['altervalue']*86400);
                } else if ($_POST['altervalue'] >= 1) {
                   $addsearch = ' AND datum '.$_POST['alterart'].' ';
                   $addsearch .= time()-((int)$_POST['altervalue']*86400);                	
                }
                if($_POST['username'] == '') {
	                IF(strpos($_POST['stichwort'],' AND ')) {
	                	foreach(explode(' AND ',$_POST['stichwort']) AS $value) {
	                       @$suchstring .= ' AND &feld& LIKE \'%'.mysql_real_escape_string($value).'%\'';
	                    }
	                    $suchstring = '('.substr($suchstring,5).')';
	                } elseif (strpos($_POST['stichwort'],' OR ')) {
	                    foreach(explode(' OR ',$_POST['stichwort']) AS $value) {
	                        @$suchstring .= ' OR &feld& LIKE \'%'.mysql_real_escape_string($value).'%\'';
	                    }
	                    $suchstring = '('.substr($suchstring,4).')';
	                } else {                      
	                    foreach(explode(' ',$_POST['stichwort']) AS $value) {
	                        if($value != '')
	                     	@$suchstring .= ' OR &feld& LIKE \'%'.mysql_real_escape_string($value).'%\'';
	                    }
	                    $suchstring = '('.substr($suchstring,4).')';
	                }
				} else {
	                IF(strpos($_POST['username'],' AND ')) {
	                	foreach(explode(' AND ',$_POST['stichwort']) AS $value) {
	                       @$suchstring .= ' AND (&feld& LIKE \'%'.mysql_real_escape_string($value).'%\' OR &feld2& LIKE \'%'.mysql_real_escape_string($value).'%\') ';
	                    }
	                    $suchstring = '('.substr($suchstring,5).')';
	                } elseif (strpos($_POST['username'],' OR ')) {
	                    foreach(explode(' OR ',$_POST['stichwort']) AS $value) {
	                        @$suchstring .= ' OR &feld& LIKE \'%'.mysql_real_escape_string($value).'%\' OR &feld2& LIKE \'%'.mysql_real_escape_string($value).'%\'';
	                    }
	                    $suchstring = '('.substr($suchstring,4).')';
	                } else {                      
	                    foreach(explode(' ',$_POST['username']) AS $value) {
	                        if($value != '')
	                     	@$suchstring .= ' OR &feld& LIKE \'%'.mysql_real_escape_string($value).'%\' OR &feld2& LIKE \'%'.mysql_real_escape_string($value).'%\'';
	                    }
	                    $suchstring = '('.substr($suchstring,4).')';
	                }
				}
                IF($_POST['suchart'] == 1 AND $_POST['username'] == '' AND $_POST['viewas'] == 'comments')  {
                    $suchstring = str_replace('&feld&', 'comment', $suchstring);
                    $sql = 'SELECT `tID`, `bID`, `comID`, com.userID, `postname`, `adatum`, `comment`, u1.username, 
                      				`edits`, `editdatum`, `edituserID`, com.IP, `attachs`, `datum`, 
                       				`threadname`, `vonID`, `vonname`, `views`, c.posts, `lastuserID`, 
                       				`lastusername`, `lastreplay`, `sticky`, c.closed, `fsurveyID`, 
                       				`anhaenge`, `rating`, `ratingvotes`, a.boardparentID, 
                       				a.name, a.rightsread, b.rightsread as parentRead, b.name as boardparentname, 
                       				u1.sex, u1.signatur, u1.country, comments, money, u1.avatar, u2.username as editfrom, lastklick as online  
                       				FROM '.DB_PRE.'ecp_forum_comments as com 
									LEFT JOIN '.DB_PRE.'ecp_user as u1 ON (com.userID = u1.ID)
									LEFT JOIN '.DB_PRE.'ecp_user as u2 ON (com.edituserID = u2.ID)
									LEFT JOIN '.DB_PRE.'ecp_user_stats ON (com.userID = '.DB_PRE.'ecp_user_stats.userID)
									LEFT JOIN '.DB_PRE.'ecp_online ON (uID = com.userID AND lastklick > '.(time()-SHOW_USER_ONLINE).')                       				
                       				LEFT JOIN '.DB_PRE.'ecp_forum_threads AS c ON (tID = threadID) 
                       				LEFT JOIN '.DB_PRE.'ecp_forum_boards AS a ON (a.boardID = bID) 
                       				LEFT JOIN '.DB_PRE.'ecp_forum_boards AS b ON (a.boardparentID = b.boardID) 
                       				WHERE '.@$boards.$suchstring.@$addsearch.' GROUP BY comID';
                } elseif ($_POST['suchart'] == 1 AND $_POST['username'] == '' AND $_POST['viewas'] == 'topic') {
                   $suchstring = str_replace('&feld&', 'comment', $suchstring);
                   $sql = 'SELECT `threadID`, `bID`, `datum`, `threadname`, `vonID`, `vonname`, `views`, c.posts, `lastuserID`, u1.username, u2.username as lastuserIDname,
                       				`lastusername`, `lastreplay`, `sticky`, c.closed, `fsurveyID`, 
                        				`anhaenge`, `rating`, `ratingvotes`, a.boardparentID, 
                        				a.name, a.rightsread, b.rightsread as parentRead, b.name as boardparentname 
                       					FROM '.DB_PRE.'ecp_forum_comments 
                       					LEFT JOIN '.DB_PRE.'ecp_forum_threads AS c ON (tID = threadID) 
                        				LEFT JOIN '.DB_PRE.'ecp_user as u1 ON (vonID = u1.ID) 
                        				LEFT JOIN '.DB_PRE.'ecp_user as u2 ON (lastuserID = u2.ID)
                        				LEFT JOIN '.DB_PRE.'ecp_forum_boards AS a ON (a.boardID = bID) 
                        				LEFT JOIN '.DB_PRE.'ecp_forum_boards AS b ON (a.boardparentID = b.boardID) 
                        				WHERE '.@$boards.$suchstring.@$addsearch. ' GROUP BY threadID ';                  
                } elseif ($_POST['username'] == '') {
                   $suchstring = str_replace('&feld&', 'threadname', $suchstring);
                   $sql = 'SELECT `threadID`, `bID`, `datum`, `threadname`, `vonID`, `vonname`, `views`, c.posts, `lastuserID`, u1.username, u2.username as lastuserIDname,
                       				`lastusername`, `lastreplay`, `sticky`, c.closed, `fsurveyID`, 
                        				`anhaenge`, `rating`, `ratingvotes`, a.boardparentID, 
                        				a.name, a.rightsread, b.rightsread as parentRead, b.name as boardparentname 
                        				FROM '.DB_PRE.'ecp_forum_threads AS c 
                        				LEFT JOIN '.DB_PRE.'ecp_user as u1 ON (vonID = u1.ID) 
                        				LEFT JOIN '.DB_PRE.'ecp_user as u2 ON (lastuserID = u2.ID)
                        				LEFT JOIN '.DB_PRE.'ecp_forum_boards AS a ON (a.boardID = bID) 
                        				LEFT JOIN '.DB_PRE.'ecp_forum_boards AS b ON (a.boardparentID = b.boardID) 
                        				WHERE '.@$boards.$suchstring.@$addsearch.' GROUP BY threadID';
                }
                IF($_POST['usersuchart'] == 1 AND $_POST['username'] != '')  {
                    $suchstring = str_replace(array('&feld&', '&feld2&'), array('u1.username', 'postname'), $suchstring);
                    $sql = 'SELECT `tID`, `bID`, `comID`, com.userID, `postname`, `adatum`, `comment`, u1.username,
                      				`edits`, `editdatum`, `edituserID`, com.IP, `attachs`, `datum`, 
                       				`threadname`, `vonID`, `vonname`, `views`, c.posts, `lastuserID`, 
                       				`lastusername`, `lastreplay`, `sticky`, c.closed, `fsurveyID`, 
                       				`anhaenge`, `rating`, `ratingvotes`, a.boardparentID, 
                       				a.name, a.rightsread, b.rightsread as parentRead, b.name as boardparentname,
                       				u1.sex, u1.signatur, u1.country, comments, money, u1.avatar, u2.username as editfrom, lastklick as online  
                       				FROM '.DB_PRE.'ecp_forum_comments as com
									LEFT JOIN '.DB_PRE.'ecp_user as u1 ON (com.userID = u1.ID)
									LEFT JOIN '.DB_PRE.'ecp_user as u2 ON (com.edituserID = u2.ID)
									LEFT JOIN '.DB_PRE.'ecp_user_stats ON (com.userID = '.DB_PRE.'ecp_user_stats.userID)
									LEFT JOIN '.DB_PRE.'ecp_online ON (uID = com.userID AND lastklick > '.(time()-SHOW_USER_ONLINE).')     
                       				LEFT JOIN '.DB_PRE.'ecp_forum_threads AS c ON (tID = threadID) 
                       				LEFT JOIN '.DB_PRE.'ecp_forum_boards AS a ON (a.boardID = bID) 
                       				LEFT JOIN '.DB_PRE.'ecp_forum_boards AS b ON (a.boardparentID = b.boardID) 
                       				WHERE '.@$boards.$suchstring.@$addsearch.' GROUP BY comID';
                } elseif ($_POST['username'] != '') {
                   $suchstring = str_replace(array('&feld&', '&feld2&'), array('u1.username', 'vonname'), $suchstring);
                   $sql = 'SELECT `threadID`, `bID`, `datum`, `threadname`, `vonID`, `vonname`, `views`, c.posts, `lastuserID`, u1.username, u2.username as lastuserIDname,
                       				`lastusername`, `lastreplay`, `sticky`, c.closed, `fsurveyID`, 
                        				`anhaenge`, `rating`, `ratingvotes`, a.boardparentID, 
                        				a.name, a.rightsread, b.rightsread as parentRead, b.name as boardparentname 
                        				FROM '.DB_PRE.'ecp_forum_threads AS c 
                        				LEFT JOIN '.DB_PRE.'ecp_user as u1 ON (vonID = u1.ID) 
                        				LEFT JOIN '.DB_PRE.'ecp_user as u2 ON (lastuserID = u2.ID)                        				
                        				LEFT JOIN '.DB_PRE.'ecp_forum_boards AS a ON (a.boardID = bID) 
                        				LEFT JOIN '.DB_PRE.'ecp_forum_boards AS b ON (a.boardparentID = b.boardID) 
                        				WHERE '.@$boards.$suchstring.@$addsearch.' GROUP BY threadID';
                }     
                $db->query($sql);
				if($db->num_rows()) {
					if($db->query(sprintf('INSERT INTO '.DB_PRE.'ecp_forum_search 
											(`userID`, `IP`, `SID`, `datum`, `stichwort`, `suchart`, `fromusername`, 
											`usersuchart`, `foren`, `alterart`, `altervalue`, `sortart`, `sortorder`, `sqlquery`, viewas) VALUES  (
											%d, \'%s\', \'%s\', %d, \'%s\', %d, \'%s\', %d, \'%s\', \'%s\', %d, \'%s\', \'%s\', \'%s\', \'%s\' )',
											@$_SESSION['userID'], strsave($_SERVER['REMOTE_ADDR']), session_id(), time(), strsave($_POST['stichwort']),
											(int)$_POST['suchart'], strsave($_POST['username']), (int)$_POST['usersuchart'], strsave(substr(@$boardids, 1)), strsave($_POST['alterart']), (int)$_POST['altervalue'], strsave($_POST['sortart']), strsave($_POST['sortorder']), strsave($sql), ($_POST['viewas'] == 'topic' ? 'topic' : 'comments')))
											) {
						header1('?section=forum&action=searchresults&id='.$db->last_id());
					}
				} else {
					table(ERROR, FORUM_SEARCH_NO_RESULTS);
					unset($_POST['submit']);
					forum_search(0);
				}
			}
		} else {
			$tpl = new smarty;
			$db->query('SELECT `boardID`, `boardparentID`, `name`, `isforum`
									FROM '.DB_PRE.'ecp_forum_boards 
									WHERE rightsread = "" OR '.str_replace('access', 'rightsread', $_SESSION['access_search']).'
									ORDER BY boardparentID, posi ASC');				
			$boards = array();
			while($row = $db->fetch_assoc()) {
				if($row['isforum'] == 0 OR $row['boardparentID'] == 0) {
					$boards[$row['boardID']]['name'] = $row['name'];
					$boards[$row['boardID']]['isforum'] = $row['isforum'];
				} else {
					$boards[$row['boardparentID']]['subs'][$row['boardID']]['name'] = $row['name'];			    
				}
			}
			$links = '';
			foreach($boards AS $key=>$value) {
				$links .= '<option '.(@$_GET['boardID'] == $key ? 'selected="selected"' : '').'value="'.$key.($value['isforum'] == 1 ? '' : '_sub').'">-'.$value['name'].'</option>';
				if(isset($value['subs'])) {
					foreach($value['subs'] AS $key1=>$value1) {
						$links .= '<option '.(@$_GET['boardID'] == $key1 ? 'selected="selected"' : '').'value="'.$key1.'">|- '.$value1['name'].'</option>';
					}
				}
			}
			$tpl->assign('foren', $links);
			$tpl->assign('path', '<a href="?section=forum">'.FORUM.'</a> <img src="templates/'.DESIGN.'/images/pfeil_o.gif" alt="" /> '.SEARCH);
			ob_start();
			$tpl->display(DESIGN.'/tpl/forum/board_head.html');
			$tpl->display(DESIGN.'/tpl/forum/search.html');
			echo '</div>';
			$content = ob_get_contents();
			ob_end_clean();
			main_content(FORUM_SEARCH, $content, '',1);   		
		}
	}
}
function forum_search_results($id) {
	global $db;
	if($id) {
		$search = $db->fetch_assoc('SELECT * FROM '.DB_PRE.'ecp_forum_search WHERE (SID = \''.session_id().'\' OR userID = '.(int)@$_SESSION['userID'].') AND searchID = '.$id);
		if(isset($search['searchID'])) {
			if($search['stichwort'] != '') {
				if($search['viewas'] == 'comments') {
					$db->query($search['sqlquery'].' ORDER BY '.$search['sortart'].' '.$search['sortorder']);
					$anzahl = $db->num_rows();
					$limits = get_sql_limit($anzahl, LIMIT_FORUM_COMMENTS);
					$result = $db->query($search['sqlquery'].' ORDER BY '.$search['sortart'].' '.$search['sortorder'].' LIMIT '.$limits[1].', '.LIMIT_FORUM_COMMENTS);				
					$comments = array();
					while($row = mysql_fetch_assoc($result)) {
						$row['adatum'] = forum_make_date($row['adatum']);
						$row['nr'] = ++$limits[1];
						$row['countryname'] = @$countries[$row['country']];
						($row['sex'] == 'male')? $row['sextext'] = MALE : $row['sextext'] = FEMALE;
						if($row['edits']) {
							$row['edit'] = str_replace(array('{anzahl}', '{von}', '{last}'), array($row['edits'], '<a href="?section=user&id='.$row['edituserID'].'">'.$row['editfrom'].'</a>', date(LONG_DATE, $row['editdatum'])), COMMENT_EDIT_TXT);
						}
						if($row['attachs']) {
							$anhaenge = array();
							$db->query('SELECT `attachID`, `name`, `size`, `downloads` FROM `'.DB_PRE.'ecp_forum_attachments` WHERE `bID` = '.$row['bID'].' AND `tID` = '.$row['tID'].' AND `mID` = '.$row['comID']);
							while($sub = $db->fetch_assoc()) {
								$sub['size'] = goodsize($sub['size']);
								$anhaenge[] = $sub;
							}
							$row['attchs'] = $anhaenge;
						}
						$comments[] = $row;
					}
					$tpl = new smarty;
					$tpl->assign('comments', $comments);
					$tpl->assign('words', str_replace(array('AND', 'OR'), array('<strong>AND</strong>', '<strong>OR</strong>'), $search['stichwort']));		
					if($limits[0] != 1) 
						$tpl->assign('seiten', '<span class="klammer">[</span> '.PAGES.': '.$anzahl.' <span class="klammer">|</span> '.makepagelink('?section=forum&action=searchresults&id='.$id, ((int)@$_GET['page'] == 0 ? 1 : (int)@$_GET['page']), $limits[0]). ' <span class="klammer">]</span>');
					$tpl->assign('path', '<a href="?section=forum">'.FORUM.'</a> <img src="templates/'.DESIGN.'/images/pfeil_o.gif" alt="" /> '.SEARCH);
					ob_start();
					$tpl->display(DESIGN.'/tpl/forum/board_head.html');
					$tpl->display(DESIGN.'/tpl/forum/search_results_comments.html');
					echo '</div>';
					$content = ob_get_contents();
					ob_end_clean();
					main_content(FORUM_SEARCH_RESULTS, $content, '',1);  
				} else {
					if($search['sortart'] == 'adatum') {
						$search['sortart'] = 'lastreplay';
					}
					$db->query($search['sqlquery']);
					$anzahl = $db->num_rows();
					$limits = get_sql_limit($anzahl, LIMIT_THREADS);
					$result = $db->query($search['sqlquery'].' ORDER BY '.$search['sortart'].' '.$search['sortorder'].' LIMIT '.$limits[1].', '.LIMIT_THREADS);							
					$threads = array();
					while($row = mysql_fetch_assoc($result)) {
						$row['lastreplay'] = forum_make_date($row['lastreplay']);
						$row['datum'] = forum_make_date($row['datum']);
						$row['bewertung'] = ($row['ratingvotes'] != 0 ? str_replace(array('{anzahl}', '{avg}'), array(format_nr($row['ratingvotes']), format_nr($row['rating'],2)), FORUM_RATING_VAL) : FORUM_NO_RATINGS);
						$row['bewertungbild'] = 'rating_'.str_replace('.', '_', get_forum_rating($row['rating']));
						$threads[] = $row;					
					}
					$tpl = new smarty;
					$tpl->assign('threads', $threads);
					$tpl->assign('words', str_replace(array('AND', 'OR'), array('<strong>AND</strong>', '<strong>OR</strong>'), $search['stichwort']));		
					if($limits[0] != 1) 
						$tpl->assign('seiten', '<span class="klammer">[</span> '.PAGES.': '.$anzahl.' <span class="klammer">|</span> '.makepagelink('?section=forum&action=searchresults&id='.$id, ((int)@$_GET['page'] == 0 ? 1 : (int)@$_GET['page']), $limits[0]). ' <span class="klammer">]</span>');
					$tpl->assign('path', '<a href="?section=forum">'.FORUM.'</a> <img src="templates/'.DESIGN.'/images/pfeil_o.gif" alt="" /> '.SEARCH);
					ob_start();
					$tpl->display(DESIGN.'/tpl/forum/board_head.html');
					$tpl->display(DESIGN.'/tpl/forum/search_results_boards.html');
					echo '</div>';
					$content = ob_get_contents();
					ob_end_clean();
					main_content(FORUM_SEARCH_RESULTS, $content, '',1);   						
				}
			} else {
				if($search['usersuchart'] == 1) {
					if($search['sortart'] == 'adatum') $search['sortart'] == 'datum';				
					$db->query($search['sqlquery'].' ORDER BY '.$search['sortart'].' '.$search['sortorder']);
					$anzahl = $db->num_rows();
					$limits = get_sql_limit($anzahl, LIMIT_FORUM_COMMENTS);
					$result = $db->query($search['sqlquery'].' ORDER BY '.$search['sortart'].' '.$search['sortorder'].' LIMIT '.$limits[1].', '.LIMIT_FORUM_COMMENTS);				
					$comments = array();
					while($row = mysql_fetch_assoc($result)) {
						$row['adatum'] = forum_make_date($row['adatum']);
						$row['nr'] = ++$limits[1];
						$row['countryname'] = @$countries[$row['country']];
						($row['sex'] == 'male')? $row['sextext'] = MALE : $row['sextext'] = FEMALE;
						if($row['edits']) {
							$row['edit'] = str_replace(array('{anzahl}', '{von}', '{last}'), array($row['edits'], '<a href="?section=user&id='.$row['edituserID'].'">'.$row['editfrom'].'</a>', date(LONG_DATE, $row['editdatum'])), COMMENT_EDIT_TXT);
						}
						if($row['attachs']) {
							$anhaenge = array();
							$db->query('SELECT `attachID`, `name`, `size`, `downloads` FROM `'.DB_PRE.'ecp_forum_attachments` WHERE `bID` = '.$row['bID'].' AND `tID` = '.$row['tID'].' AND `mID` = '.$row['comID']);
							while($sub = $db->fetch_assoc()) {
								$sub['size'] = goodsize($sub['size']);
								$anhaenge[] = $sub;
							}
							$row['attchs'] = $anhaenge;
						}
						$comments[] = $row;
					}
					$tpl = new smarty;
					$tpl->assign('search' , 'username');
					$tpl->assign('username', $search['fromusername']);					
					$tpl->assign('comments', $comments);
					if($limits[0] != 1) 
						$tpl->assign('seiten', '<span class="klammer">[</span> '.PAGES.': '.$anzahl.' <span class="klammer">|</span> '.makepagelink('?section=forum&action=searchresults&id='.$id, ((int)@$_GET['page'] == 0 ? 1 : (int)@$_GET['page']), $limits[0]). ' <span class="klammer">]</span>');
					$tpl->assign('path', '<a href="?section=forum">'.FORUM.'</a> <img src="templates/'.DESIGN.'/images/pfeil_o.gif" alt="" /> '.SEARCH);
					ob_start();
					$tpl->display(DESIGN.'/tpl/forum/board_head.html');
					$tpl->display(DESIGN.'/tpl/forum/search_results_comments.html');
					echo '</div>';
					$content = ob_get_contents();
					ob_end_clean();
					main_content(FORUM_SEARCH_RESULTS, $content, '',1); 
				} else {
					if($search['sortart'] == 'adatum') {
						$search['sortart'] = 'lastreplay';
					}
					$db->query($search['sqlquery']);
					$anzahl = $db->num_rows();
					$limits = get_sql_limit($anzahl, LIMIT_THREADS);
					$result = $db->query($search['sqlquery'].' ORDER BY '.$search['sortart'].' '.$search['sortorder'].' LIMIT '.$limits[1].', '.LIMIT_THREADS);							
					$threads = array();
					while($row = mysql_fetch_assoc($result)) {
						$row['lastreplay'] = forum_make_date($row['lastreplay']);
						$row['datum'] = forum_make_date($row['datum']);
						$row['bewertung'] = ($row['ratingvotes'] != 0 ? str_replace(array('{anzahl}', '{avg}'), array(format_nr($row['ratingvotes']), format_nr($row['rating'],2)), FORUM_RATING_VAL) : FORUM_NO_RATINGS);
						$row['bewertungbild'] = 'rating_'.str_replace('.', '_', get_forum_rating($row['rating']));
						$threads[] = $row;					
					}
					$tpl = new smarty;
					$tpl->assign('search' , 'username');
					$tpl->assign('username', $search['fromusername']);
					$tpl->assign('threads', $threads);
					if($limits[0] != 1) 
						$tpl->assign('seiten', '<span class="klammer">[</span> '.PAGES.': '.$anzahl.' <span class="klammer">|</span> '.makepagelink('?section=forum&action=searchresults&id='.$id, ((int)@$_GET['page'] == 0 ? 1 : (int)@$_GET['page']), $limits[0]). ' <span class="klammer">]</span>');
					$tpl->assign('path', '<a href="?section=forum">'.FORUM.'</a> <img src="templates/'.DESIGN.'/images/pfeil_o.gif" alt="" /> '.SEARCH);
					ob_start();
					$tpl->display(DESIGN.'/tpl/forum/board_head.html');
					$tpl->display(DESIGN.'/tpl/forum/search_results_boards.html');
					echo '</div>';
					$content = ob_get_contents();
					ob_end_clean();
					main_content(FORUM_SEARCH_RESULTS, $content, '',1);   						
				}				  								
			}
		} else {
			table(ERROR, FORUM_SEARCH_NOT_YOURS);
		}
	}
}
function forum_mark_all() {
	global $db;
	$_SESSION['lastforum']['time'] = time();
	if(isset($_SESSION['userID']))
		$db->query('UPDATE '.DB_PRE.'ecp_user SET lastforum = '.time().' WHERE ID = '.$_SESSION['userID']);
	if(isset($_SERVER['HTTP_REFERER']))	{
		header('Location: '.$_SERVER['HTTP_REFERER']);
	} else {
		header1('?section=forum');
	}
}
if(@$_SESSION['rights']['public']['forum']['view'] OR @$_SESSION['rights']['superadmin']) {
	if(isset($_GET['action'])) {
		switch ($_GET['action']) {
			case 'subboard':
				forum_subboard((int)$_GET['boardID']);
			break;
			case 'board':
				forum_board((int)$_GET['boardID']);
			break;
			case 'newtopic':
				forum_new_thread((int)$_GET['boardID']);
			break;		
			case 'thread':
				forum_thread((int)$_GET['boardID'], (int)$_GET['threadID']);
			break;		
			case 'getfile':
				forum_get_file((int)$_GET['attachID'], (int)$_GET['boardID'], (int)$_GET['threadID'], (int)$_GET['comID']);
			break;			
			case 'survey_vote': 
				forum_survey_vote((int)$_GET['id']);
			break;
			case 'replay': 
				forum_new_replay((int)$_GET['boardID'], (int)$_GET['threadID']);
			break;	
			case 'gotolast': 
				forum_goto_last((int)$_GET['boardID'], (int)$_GET['threadID']);
			break;
			case 'gotonew': 
				forum_goto_new((int)$_GET['boardID'], (int)$_GET['threadID']);
			break;					
			case 'editreplay': 
				forum_edit_replay((int)$_GET['comID'], (int)$_GET['boardID'], (int)$_GET['threadID']);
			break;
			case 'search': 
				forum_search((int)@$_GET['userID']);
			break;	
			case 'searchresults':
				forum_search_results((int)$_GET['id']);
			break;	
			case 'all_readed':
				forum_mark_all();
			break;
			default:
				forum();
		}
	} else {
		forum();
	}
} else {
	table(ERROR, ACCESS_DENIED);
}
?>