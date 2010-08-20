<?php
function admin_clanwars() {
	global $db;
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
	$nextwars = array();
	$db->query('SELECT `warID` , '.DB_PRE.'ecp_wars.`tID` , `gID` , `datum` , `xonx` , `oID` , oppname, tname, gamename, icon
				FROM `'.DB_PRE.'ecp_wars` 
				LEFT JOIN `'.DB_PRE.'ecp_wars_games` ON ( gameID = gID ) 
				LEFT JOIN `'.DB_PRE.'ecp_teams` ON ( '.DB_PRE.'ecp_teams.tID = '.DB_PRE.'ecp_wars.tID ) 
				LEFT JOIN `'.DB_PRE.'ecp_wars_opp` ON ( oppID = oID ) 
				WHERE status = 0
				GROUP BY warID
				ORDER BY datum DESC 
				LIMIT '.$limit[1].' ,'.ADMIN_ENTRIES);
	while($row = $db->fetch_assoc()) {
		$row['datum'] = date(SHORT_DATE, $row['datum']);
		$nextwars[] = $row;
	}	
	$tpl->assign('nextwars', $nextwars);	
	ob_start();
	$tpl->display(DESIGN.'/tpl/admin/clanwars.html');
	$content = ob_get_contents();
	ob_end_clean();
	main_content(CLANWARS, $content, '',1);	
}
function get_opps($id = 0) {
	global $db;
	$db->query('SELECT oppID, oppname FROM '.DB_PRE.'ecp_wars_opp ORDER BY oppname ASC');
	$str = '<option value="0">'.CHOOSE.'</option>';
	while($row = $db->fetch_assoc()) {
		($row['oppID'] == $id) ? $sub = 'selected="selected"' : $sub = '';
		$str .= '<option '.$sub.' value="'.$row['oppID'].'">'.htmlspecialchars($row['oppname']).'</option>';
	}
	return $str;
}
function admin_clanwars_add($id = 0) {
	if(@$_SESSION['rights']['admin']['clanwars']['add'] OR @$_SESSION['rights']['superadmin']) {	
		global $db;		
		if(isset($_POST['datum'])) {
			if(!$_POST['oppID']) {
				$sql = sprintf('INSERT INTO '.DB_PRE.'ecp_wars_opp (`oppname`, `oppshort`, `homepage`, `country`) VALUES (\'%s\', \'%s\',\'%s\',\'%s\')', strsave($_POST['oppname']), strsave($_POST['oppshort']), strsave(check_url($_POST['homepage'])), strsave($_POST['country']));
			}
			if($_POST['oppID'] OR $db->query($sql)) {
				!$_POST['oppID'] ? $oppid = $db->last_id() : $oppid = (int)$_POST['oppID'];
				$lang = array();
				foreach($_POST AS $key => $value) {
					if(strpos($key, 'cription_')) {
						$lang[substr($key,strpos($key, '_')+1)] = $value;
					}
				}
				$players = ',';
				$play = explode(',',$_POST['ownplayers']);
				foreach($play AS $value) {
					$value = trim($value);
					if($value) {
						$userid = $db->result(DB_PRE.'ecp_user', 'ID', 'username = \''.strsave($value).'\'');
						if($userid)
							$players .= $userid.',';
					}
				}
				$sql = sprintf('INSERT INTO '.DB_PRE.'ecp_wars (`tID`, `mID`, `gID`, `datum`, `xonx`, `report`, `ownplayers`, `oppplayers`, `oID`, `matchlink`, `resultbylocations`, `status`) VALUES 
																(%d, %d, %d, %d, \'%s\',\'%s\',\'%s\',\'%s\',%d, \'%s\', %d, %d)',
																(int)$_POST['teamID'],(int)$_POST['matchtypeID'],(int)$_POST['gameID'],strtotime($_POST['datum']), (int)$_POST['xonx1'].'on'.(int)$_POST['xonx2'], strsave(json_encode($lang)), strsave($players), strsave($_POST['oppplayers']), $oppid, strsave(check_url($_POST['matchlink'])), (int)@$_POST['winbymaps'], 1);
				if($db->query($sql)) {
					$warid = $db->last_id();
					foreach($_POST AS $key=>$value) {
						if(strpos($key, 'map_') !== false) {
							@$i++;
							if((int)@$_POST['winbymaps']) {
								if((int)$_POST['score_'.$i.'_own'] >  (int)$_POST['score_'.$i.'_opp']) {
									$own++;
								} elseif ((int)$_POST['score_'.$i.'_own'] <  (int)$_POST['score_'.$i.'_opp']) {
									$opp++;
								} else {
									$opp++;
									$own++;
								}
							} else {
								$own += (int)$_POST['score_'.$i.'_own'];
								$opp += (int)$_POST['score_'.$i.'_opp'];								
							}							
							$db->query(sprintf('INSERT INTO '.DB_PRE.'ecp_wars_scores (`wID`, `lID`, `ownscore`, `oppscore`) VALUES (%d, %d, %d, %d)', $warid, (int)$value, (int)$_POST['score_'.$i.'_own'], (int)$_POST['score_'.$i.'_opp']));
							
						}
					}
					if($own == $opp) {
						$db->query('UPDATE '.DB_PRE.'ecp_wars SET result = "draw", resultscore = \''.$own.':'.$opp.'\' WHERE warID = '.$warid);
					} elseif ($own < $opp) {
						$db->query('UPDATE '.DB_PRE.'ecp_wars SET result = "loss", resultscore = \''.$own.':'.$opp.'\' WHERE warID = '.$warid);
					} else {
						$db->query('UPDATE '.DB_PRE.'ecp_wars SET result = "win", resultscore = \''.$own.':'.$opp.'\' WHERE warID = '.$warid);
					}					
					header1('?section=admin&site=clanwars');
				}
			}
		} else {
			$tpl = new smarty;	
			if($id != 0) {
				$fight = $db->fetch_assoc('SELECT `gID`, `mID`, `teamID`, `clanname`, `homepage`, `wardatum` FROM '.DB_PRE.'ecp_fightus WHERE fightusID = '.$id);
				$opp = $db->fetch_assoc('SELECT * FROM '.DB_PRE.'ecp_wars_opp WHERE oppname = \''.strsave($fight['oppname']).'\'');
				if(is_array($opp)) {
					$tpl->assign('opps', get_opps($opp['oppID']));
					$tpl->assign('homepage', $opp['homepage']);
					$tpl->assign('oppname', $opp['oppname']);
					$tpl->assign('oppshort', $opp['oppshort']);
					$tpl->assign('countries', form_country($opp['country']));	
				} else {
					$tpl->assign('opps', get_opps());	
					$tpl->assign('countries', form_country());						
					$tpl->assign('homepage', $fight['homepage']);
					$tpl->assign('oppname', $fight['clanname']);
				}
				$tpl->assign('datum', date('Y-m-d H:i', $fight['wardatum']));
				$tpl->assign('games',get_games_form($fight['gID']));
				$tpl->assign('teams',get_teams_form($fight['teamID']));
				$tpl->assign('matchtype',get_matchtype_form($fight['mID']));
			} else {
				$tpl->assign('countries', form_country());					
				$tpl->assign('opps', get_opps());
				$tpl->assign('games',get_games_form());
				$tpl->assign('teams',get_teams_form());
				$tpl->assign('matchtype',get_matchtype_form());				
			}		
			$tpl->assign('maps', array(array('i' => 1)));			
			$tpl->assign('lang', get_languages());
			$tpl->assign('func', 'add');
			ob_start();
			$tpl->display(DESIGN.'/tpl/admin/clanwars_add.html');
			$content = ob_get_contents();
			ob_end_clean();
			main_content(CLANWARS, $content, '',1);		
		}
	} else {
		table(ERROR, NO_ADMIN_RIGHTS);
	}
}
function admin_clanwars_edit($id) {
	if(@$_SESSION['rights']['admin']['clanwars']['edit'] OR @$_SESSION['rights']['superadmin']) {	
		global $db;
		if(isset($_POST['datum'])) {
			if(!$_POST['oppID']) {
				$sql = sprintf('INSERT INTO '.DB_PRE.'ecp_wars_opp (`oppname`, `oppshort`, `homepage`, `country`) VALUES (\'%s\', \'%s\',\'%s\',\'%s\')', strsave($_POST['oppname']), strsave($_POST['oppshort']), strsave($_POST['homepage']), strsave($_POST['country']));
			} else {
				$sql = sprintf('UPDATE '.DB_PRE.'ecp_wars_opp SET `oppname` = \'%s\', `oppshort` = \'%s\', `homepage` = \'%s\', `country` = \'%s\' WHERE oppID = %d', strsave($_POST['oppname']), strsave($_POST['oppshort']), strsave(check_url($_POST['homepage'])), strsave($_POST['country']), @$_POST['oppID']);
			}
			if($db->query($sql)) {
				!$_POST['oppID'] ? $oppid = $db->last_id() : $oppid = (int)$_POST['oppID'];
				$lang = array();
				foreach($_POST AS $key => $value) {
					if(strpos($key, 'cription_')) {
						$lang[substr($key,strpos($key, '_')+1)] = $value;
					}
				}
				$players = ',';
				$play = explode(',',$_POST['ownplayers']);
				foreach($play AS $value) {
					$value = trim($value);
					if($value) {
						$userid = $db->result(DB_PRE.'ecp_user', 'ID', 'username = \''.strsave($value).'\'');
						if($userid)
							$players .= $userid.',';
					}
				}
				$sql = sprintf('UPDATE '.DB_PRE.'ecp_wars SET `tID` = %d, `mID` = %d,`gID` = %d,`datum` = %d,`xonx` = \'%s\',`report` = \'%s\',`ownplayers` = \'%s\',`oppplayers` = \'%s\',`oID` = %d,`matchlink` = \'%s\',`resultbylocations` = %d WHERE warID = %d', 
																(int)$_POST['teamID'],(int)$_POST['matchtypeID'],(int)$_POST['gameID'],strtotime($_POST['datum']), (int)$_POST['xonx1'].'on'.(int)$_POST['xonx2'], strsave(json_encode($lang)), strsave($players), strsave($_POST['oppplayers']), $oppid, strsave(check_url($_POST['matchlink'])), (int)@$_POST['winbymaps'], $id);
				if($db->query($sql)) {
					$db->query('SELECT scoreID FROM '.DB_PRE.'ecp_wars_scores WHERE wID = '.$id.' ORDER BY scoreID ASC');
					while($row = $db->fetch_assoc()) {
						$ids[] = $row['scoreID'];
					}
					$own = 0;
					$opp = 0;
					foreach($_POST AS $key=>$value) {
						if(strpos($key, 'map_') !== false) {
							@$i++;
							if((int)@$_POST['winbymaps']) {
								if((int)$_POST['score_'.$i.'_own'] >  (int)$_POST['score_'.$i.'_opp']) {
									$own++;
								} elseif ((int)$_POST['score_'.$i.'_own'] <  (int)$_POST['score_'.$i.'_opp']) {
									$opp++;
								} else {
									$opp++;
									$own++;
								}
							} else {
								$own += (int)$_POST['score_'.$i.'_own'];
								$opp += (int)$_POST['score_'.$i.'_opp'];								
							}
							if(isset($ids[$i-1])) {
								$db->query(sprintf('UPDATE '.DB_PRE.'ecp_wars_scores SET `lID` = %d, `ownscore` = %d, `oppscore` = %d WHERE scoreID = %d', (int)$value, (int)$_POST['score_'.$i.'_own'], (int)$_POST['score_'.$i.'_opp'], $ids[$i-1]));	
							} else {
								$db->query(sprintf('INSERT INTO '.DB_PRE.'ecp_wars_scores (`wID`, `lID`, `ownscore`, `oppscore`) VALUES (%d, %d, %d, %d)', $id, (int)$value, (int)$_POST['score_'.$i.'_own'], (int)$_POST['score_'.$i.'_opp']));
							}							
						}
					}
					if($own == $opp) {
						$db->query('UPDATE '.DB_PRE.'ecp_wars SET result = "draw", resultscore = \''.$own.':'.$opp.'\' WHERE warID = '.$id);
					} elseif ($own < $opp) {
						$db->query('UPDATE '.DB_PRE.'ecp_wars SET result = "loss", resultscore = \''.$own.':'.$opp.'\' WHERE warID = '.$id);
					} else {
						$db->query('UPDATE '.DB_PRE.'ecp_wars SET result = "win", resultscore = \''.$own.':'.$opp.'\' WHERE warID = '.$id);
					}
					while(isset($ids[$i])) {
						if($db->result(DB_PRE.'ecp_wars_screens', 'COUNT(screenID)', 'sID = '.$ids[$i])) {
							$db->query('SELECT filename FROM '.DB_PRE.'ecp_wars_screens WHERE sID = '.$ids[$i]);
							while($row = $db->fetch_assoc()) {
								@unlink('images/screens/'.$row['filename']);
							}
							$db->query('DELETE FROM '.DB_PRE.'ecp_wars_screens WHERE sID = '.$ids[$i]);
						}
						$db->query('DELETE FROM '.DB_PRE.'ecp_wars_scores WHERE scoreID = '.$ids[$i]);
						@$i++;
					}
					header1('?section=admin&site=clanwars');
				}
			}
		} else {
			$data = $db->fetch_assoc('SELECT `tID`, `mID`, `gID`, `datum`, `xonx`, `report`, `ownplayers`, `oppplayers`, `oID`, `matchlink`, `resultbylocations`, `oppname`, `oppshort`, `homepage`, `country` FROM '.DB_PRE.'ecp_wars LEFT JOIN '.DB_PRE.'ecp_wars_opp ON (oppID = oID) WHERE warID = '.$id);
			$tpl = new smarty;	
			foreach($data AS $key=>$value) $tpl->assign($key, $value);
			$tpl->assign('opps', get_opps($data['oID']));
			$tpl->assign('countries', form_country($data['country']));
			$tpl->assign('games',get_games_form($data['gID']));
			$tpl->assign('teams',get_teams_form($data['tID']));
			$tpl->assign('matchtype',get_matchtype_form($data['mID']));
			$tpl->assign('lang', get_languages(json_decode($data['report'], true)));
			$tpl->assign('func', 'edit&id='.$id);
			$tpl->assign('datum', date('Y-m-d H:i:s', $data['datum']));
			$xonx = explode('on', $data['xonx']);
			$tpl->assign('xonx1', $xonx[0]);
			$tpl->assign('xonx2', $xonx[1]);			
			$result = $db->query('SELECT `scoreID`, `lID`, `ownscore`, `oppscore` FROM '.DB_PRE.'ecp_wars_scores WHERE wID = '.$id.' ORDER BY scoreID ASC');
			$maps = array();
			while($row = mysql_fetch_assoc($result)) {
				$row['i'] = @++$i;
				$db->query('SELECT locationID, locationname FROM '.DB_PRE.'ecp_wars_locations WHERE gID = '.$data['gID']);
				while($subrow = $db->fetch_assoc()) {
					($subrow['locationID'] == $row['lID']) ? $sub = 'selected="selected"' : $sub = '';
					@$row['maps'] .= '<option '.$sub.' value="'.$subrow['locationID'].'">'.htmlspecialchars($subrow['locationname']).'</option>';
				}
				$maps[] = $row;
			}
			$tpl->assign('maps', $maps);
			$own = explode(',', $data['ownplayers']);
			foreach($own AS $value) {
				if($value) {
					@$search .= ' OR ID = '.(int)$value;
				}
			}
			$db->query('SELECT username FROM '.DB_PRE.'ecp_user WHERE ID = 0'.$search.' ORDER BY username ASC');
			while($row = $db->fetch_assoc()) {
				@$players .= htmlspecialchars($row['username']).', ';
			}
			$tpl->assign('ownplayers', substr($players, 0, strlen($players)-2));
			ob_start();
			$tpl->display(DESIGN.'/tpl/admin/clanwars_add.html');
			$content = ob_get_contents();
			ob_end_clean();
			main_content(CLANWARS_EDIT, $content, '',1);		
		}
	} else {
		echo NO_ADMIN_RIGHTS;
	}
}
function admin_clanwars_addnext($id = 0) {
	if(@$_SESSION['rights']['admin']['clanwars']['add_next'] OR @$_SESSION['rights']['superadmin']) {	
		global $db;		
		if(isset($_POST['datum'])) {
			if(!$_POST['oppID']) {
				$sql = sprintf('INSERT INTO '.DB_PRE.'ecp_wars_opp (`oppname`, `oppshort`, `homepage`, `country`) VALUES (\'%s\', \'%s\',\'%s\',\'%s\')', strsave($_POST['oppname']), strsave($_POST['oppshort']), strsave(check_url($_POST['homepage'])), strsave($_POST['country']));
			}
			if($_POST['oppID'] OR $db->query($sql)) {
				!$_POST['oppID'] ? $oppid = $db->last_id() : $oppid = (int)$_POST['oppID'];
				$lang = array();
				foreach($_POST AS $key => $value) {
					if(strpos($key, 'cription_')) {
						$lang[substr($key,strpos($key, '_')+1)] = $value;
					}
				}
				$players = array();
				foreach($_POST['players'] AS $value) {
					$value = trim($value);
					if(strpos($value, 'team_') !== false) {
						$db->query('SELECT userID FROM '.DB_PRE.'ecp_members WHERE teamID = '.(int)substr($value, strpos($value, '_')+1));
						while($row = $db->fetch_assoc()) {
							if(!in_array($row['userID'], $players)) 
								$players[] = $row['userID'];
						}
					} elseif (strpos($value, 'member_') !== false) {
						$id = substr($value, strpos($value, '_')+1);
						if(!in_array($id, $players)) $players[] = $id;
					}
				}
				$sql = sprintf('INSERT INTO '.DB_PRE.'ecp_wars (`tID`, `mID`, `gID`, `datum`, `xonx`, hinweise, `oID`, `matchlink`, `resultbylocations`, `status`, `server`, `livestream`, `pw`, `meldefrist`) VALUES 
																(%d, %d, %d, %d, \'%s\',\'%s\', %d, \'%s\', %d, %d, \'%s\', \'%s\', \'%s\', %d)',
																(int)$_POST['teamID'],(int)$_POST['matchtypeID'],(int)$_POST['gameID'],strtotime($_POST['datum']), (int)$_POST['xonx1'].'on'.(int)$_POST['xonx2'], strsave(json_encode($lang)), $oppid, strsave(check_url($_POST['matchlink'])), (int)@$_POST['winbymaps'], 0, strsave($_POST['server']), strsave($_POST['livestream']), strsave($_POST['pw']), strtotime($_POST['meldefrist']));
				if($db->query($sql)) {
					$warid = $db->last_id();
					foreach($_POST AS $key=>$value) {
						if(strpos($key, 'map_') !== false) {
							@$i++;
							if((int)@$_POST['winbymaps']) {
								if((int)$_POST['score_'.$i.'_own'] >  (int)$_POST['score_'.$i.'_opp']) {
									$own++;
								} elseif ((int)$_POST['score_'.$i.'_own'] <  (int)$_POST['score_'.$i.'_opp']) {
									$opp++;
								} else {
									$opp++;
									$own++;
								}
							} else {
								$own += (int)$_POST['score_'.$i.'_own'];
								$opp += (int)$_POST['score_'.$i.'_opp'];								
							}							
							$db->query(sprintf('INSERT INTO '.DB_PRE.'ecp_wars_scores (`wID`, `lID`, `ownscore`, `oppscore`) VALUES (%d, %d, %d, %d)', $warid, (int)$value, (int)$_POST['score_'.$i.'_own'], (int)$_POST['score_'.$i.'_opp']));
							
						}
					}
					if(count($players)) {
						$text = $db->fetch_assoc('SELECT `content`, `content2`  FROM '.DB_PRE.'ecp_texte WHERE name = "NEXT_WAR_MSG" AND lang = "'.DEFAULT_LANG.'"');
						if($_POST['messagemode'] == 1) {
							foreach($players AS $value) {
								$db->query('INSERT INTO '.DB_PRE.'ecp_wars_teilnehmer (warID, userID) VALUES ('.$warid.', '.(int)$value.')');
								message_send($value, $_SESSION['userID'], $text['content2'], str_replace('{link}', '<a href="'.SITE_URL.'?section=clanwars&action=nextwar&id='.$warid.'">'.SITE_URL.'?section=clanwars&action=nextwar&id='.$warid.'</a>', $text['content']), 0,1);
							}
						} elseif ($_POST['messagemode'] == 2) {
							foreach($players AS $value) {
								$db->query('INSERT INTO '.DB_PRE.'ecp_wars_teilnehmer (warID, userID) VALUES ('.$warid.', '.(int)$value.')');
								send_email($db->result(DB_PRE.'ecp_user', 'email', 'ID = '.(int)$value), $text['content2'], str_replace('{link}', SITE_URL.'?section=clanwars&action=nextwar&id='.$warid, $text['content']), 1);
							}
						} elseif ($_POST['messagemode'] == 3) {
							foreach($players AS $value) {
								$db->query('INSERT INTO '.DB_PRE.'ecp_wars_teilnehmer (warID, userID) VALUES ('.$warid.', '.(int)$value.')');
								message_send($value, $_SESSION['userID'], $text['content2'], str_replace('{link}', '<a href="'.SITE_URL.'?section=clanwars&action=nextwar&id='.$warid.'">'.SITE_URL.'?section=clanwars&action=nextwar&id='.$warid.'</a>', $text['content']), 0,1);
								send_email($db->result(DB_PRE.'ecp_user', 'email', 'ID = '.(int)$value), $text['content2'], str_replace('{link}', SITE_URL.'?section=clanwars&action=nextwar&id='.$warid, $text['content']), 1);
							}
						} else {
							foreach($players AS $value) {
								$db->query('INSERT INTO '.DB_PRE.'ecp_wars_teilnehmer (warID, userID) VALUES ('.$warid.', '.(int)$value.')');
							}
						}
					}
					header1('?section=admin&site=clanwars');
				}
			}
		} else {
			$tpl = new smarty;	
			if($id != 0) {
				$fight = $db->fetch_assoc('SELECT `gID`, `mID`, `serverip`, `teamID`, `clanname`, `homepage`, `wardatum` FROM '.DB_PRE.'ecp_fightus WHERE fightusID = '.$id);
				$opp = $db->fetch_assoc('SELECT * FROM '.DB_PRE.'ecp_wars_opp WHERE oppname = \''.strsave($fight['oppname']).'\'');
				if(is_array($opp)) {
					$tpl->assign('opps', get_opps($opp['oppID']));
					$tpl->assign('homepage', $opp['homepage']);
					$tpl->assign('oppname', $opp['oppname']);
					$tpl->assign('oppshort', $opp['oppshort']);
					$tpl->assign('countries', form_country($opp['country']));	
				} else {
					$tpl->assign('opps', get_opps());	
					$tpl->assign('countries', form_country());						
					$tpl->assign('homepage', $fight['homepage']);
					$tpl->assign('oppname', $fight['clanname']);
				}
				$tpl->assign('server', $fight['serverip']);
				$tpl->assign('datum', date('Y-m-d H:i', $fight['wardatum']));
				$tpl->assign('games',get_games_form($fight['gID']));
				$tpl->assign('teams',get_teams_form($fight['teamID']));
				$tpl->assign('matchtype',get_matchtype_form($fight['mID']));
			} else {
				$tpl->assign('countries', form_country());					
				$tpl->assign('opps', get_opps());
				$tpl->assign('games',get_games_form());
				$tpl->assign('teams',get_teams_form());
				$tpl->assign('matchtype',get_matchtype_form());				
			}				
			$tpl->assign('maps', array(array('i' => 1)));
			$tpl->assign('lang', get_languages());
			$tpl->assign('members', get_cw_members());
			$tpl->assign('func', 'addnext');
			ob_start();
			$tpl->display(DESIGN.'/tpl/admin/clanwars_next.html');
			$content = ob_get_contents();
			ob_end_clean();
			main_content(NEXTWARS_ADD, $content, '',1);		
		}
	} else {
		echo NO_ADMIN_RIGHTS;
	}
}
function get_cw_members($array = array()) {
	global $db;
	$option = '';
	$result = $db->query('SELECT tname, tID FROM '.DB_PRE.'ecp_teams ORDER BY tname ASC');
	while($row = mysql_fetch_assoc($result)) {
		$option .= '<option value="">---------------------------------------------------------</option>';
		$option .= '<option value="team_'.$row['tID'].'">'.TEAM.' '.$row['tname'].'</option>';
		$option .= '<option value="">---------------------------------------------------------</option>';
		$db->query('SELECT username, userID, name FROM '.DB_PRE.'ecp_members LEFT JOIN '.DB_PRE.'ecp_user ON userID = ID WHERE teamID = '.$row['tID'].' ORDER BY name ASC');
		while($sub = $db->fetch_assoc()) {
			$option .= '<option'.(in_array($sub['userID'], $array) ? ' selected="selected"' : '').' value="member_'.$sub['userID'].'">- '.($sub['name'] ? $sub['name'] : $sub['username']).'</option>';
		}
	}
	return $option;
}
function admin_clanwars_finish($id) {
	if(@$_SESSION['rights']['admin']['clanwars']['finish'] OR @$_SESSION['rights']['superadmin']) {	
		global $db;
		if(isset($_POST['datum'])) {
			if(!$_POST['oppID']) {
				$sql = sprintf('INSERT INTO '.DB_PRE.'ecp_wars_opp (`oppname`, `oppshort`, `homepage`, `country`) VALUES (\'%s\', \'%s\',\'%s\',\'%s\')', strsave($_POST['oppname']), strsave($_POST['oppshort']), strsave($_POST['homepage']), strsave($_POST['country']));
			} else {
				$sql = sprintf('UPDATE '.DB_PRE.'ecp_wars_opp SET `oppname` = \'%s\', `oppshort` = \'%s\', `homepage` = \'%s\', `country` = \'%s\' WHERE oppID = %d', strsave($_POST['oppname']), strsave($_POST['oppshort']), strsave(check_url($_POST['homepage'])), strsave($_POST['country']), @$_POST['oppID']);
			}
			if($db->query($sql)) {
				!$_POST['oppID'] ? $oppid = $db->last_id() : $oppid = (int)$_POST['oppID'];
				$lang = array();
				foreach($_POST AS $key => $value) {
					if(strpos($key, 'cription_')) {
						$lang[substr($key,strpos($key, '_')+1)] = $value;
					}
				}
				$players = ',';
				$play = explode(',',$_POST['ownplayers']);
				foreach($play AS $value) {
					$value = trim($value);
					if($value) {
						$userid = $db->result(DB_PRE.'ecp_user', 'ID', 'username = \''.strsave($value).'\'');
						if($userid)
							$players .= $userid.',';
					}
				}
				$sql = sprintf('UPDATE '.DB_PRE.'ecp_wars SET `tID` = %d, `mID` = %d,`gID` = %d,`datum` = %d,`xonx` = \'%s\',`report` = \'%s\',`ownplayers` = \'%s\',`oppplayers` = \'%s\',`oID` = %d,`matchlink` = \'%s\',`resultbylocations` = %d, status = 1 WHERE warID = %d', 
																(int)$_POST['teamID'],(int)$_POST['matchtypeID'],(int)$_POST['gameID'],strtotime($_POST['datum']), (int)$_POST['xonx1'].'on'.(int)$_POST['xonx2'], strsave(json_encode($lang)), strsave($players), strsave($_POST['oppplayers']), $oppid, strsave(check_url($_POST['matchlink'])), (int)@$_POST['winbymaps'], $id);
				if($db->query($sql)) {
					$db->query('DELETE FROM '.DB_PRE.'ecp_wars_teilnehmer WHERE warID  = '.$id);
					$db->query('SELECT scoreID FROM '.DB_PRE.'ecp_wars_scores WHERE wID = '.$id.' ORDER BY scoreID ASC');
					while($row = $db->fetch_assoc()) {
						$ids[] = $row['scoreID'];
					}
					$own = 0;
					$opp = 0;
					foreach($_POST AS $key=>$value) {
						if(strpos($key, 'map_') !== false) {
							@$i++;
							if((int)@$_POST['winbymaps']) {
								if((int)$_POST['score_'.$i.'_own'] >  (int)$_POST['score_'.$i.'_opp']) {
									$own++;
								} elseif ((int)$_POST['score_'.$i.'_own'] <  (int)$_POST['score_'.$i.'_opp']) {
									$opp++;
								} else {
									$opp++;
									$own++;
								}
							} else {
								$own += (int)$_POST['score_'.$i.'_own'];
								$opp += (int)$_POST['score_'.$i.'_opp'];								
							}
							if(isset($ids[$i-1])) {
								$db->query(sprintf('UPDATE '.DB_PRE.'ecp_wars_scores SET `lID` = %d, `ownscore` = %d, `oppscore` = %d WHERE scoreID = %d', (int)$value, (int)$_POST['score_'.$i.'_own'], (int)$_POST['score_'.$i.'_opp'], $ids[$i-1]));	
							} else {
								$db->query(sprintf('INSERT INTO '.DB_PRE.'ecp_wars_scores (`wID`, `lID`, `ownscore`, `oppscore`) VALUES (%d, %d, %d, %d)', $id, (int)$value, (int)$_POST['score_'.$i.'_own'], (int)$_POST['score_'.$i.'_opp']));
							}							
						}
					}
					if($own == $opp) {
						$db->query('UPDATE '.DB_PRE.'ecp_wars SET result = "draw", resultscore = \''.$own.':'.$opp.'\' WHERE warID = '.$id);
					} elseif ($own < $opp) {
						$db->query('UPDATE '.DB_PRE.'ecp_wars SET result = "loss", resultscore = \''.$own.':'.$opp.'\' WHERE warID = '.$id);
					} else {
						$db->query('UPDATE '.DB_PRE.'ecp_wars SET result = "win", resultscore = \''.$own.':'.$opp.'\' WHERE warID = '.$id);
					}
					while(isset($ids[$i])) {
						if($db->result(DB_PRE.'ecp_wars_screens', 'COUNT(screenID)', 'sID = '.$ids[$i])) {
							$db->query('SELECT filename FROM '.DB_PRE.'ecp_wars_screens WHERE sID = '.$ids[$i]);
							while($row = $db->fetch_assoc()) {
								@unlink('images/screens/'.$row['filename']);
							}
							$db->query('DELETE FROM '.DB_PRE.'ecp_wars_screens WHERE sID = '.$ids[$i]);
						}
						$db->query('DELETE FROM '.DB_PRE.'ecp_wars_scores WHERE scoreID = '.$ids[$i]);
						@$i++;
					}
					header1('?section=admin&site=clanwars');
				}
			}
		} else {
			$data = $db->fetch_assoc('SELECT `tID`, `mID`, `gID`, `datum`, `xonx`, `report`, `ownplayers`, `oppplayers`, `oID`, `matchlink`, `resultbylocations`, `oppname`, `oppshort`, `homepage`, `country` FROM '.DB_PRE.'ecp_wars LEFT JOIN '.DB_PRE.'ecp_wars_opp ON (oppID = oID) WHERE warID = '.$id);
			$tpl = new smarty;	
			foreach($data AS $key=>$value) $tpl->assign($key, $value);
			$tpl->assign('opps', get_opps($data['oID']));
			$tpl->assign('countries', form_country($data['country']));
			$tpl->assign('games',get_games_form($data['gID']));
			$tpl->assign('teams',get_teams_form($data['tID']));
			$tpl->assign('matchtype',get_matchtype_form($data['mID']));
			$tpl->assign('lang', get_languages(json_decode($data['report'], true)));
			$tpl->assign('func', 'finish&id='.$id);
			$tpl->assign('datum', date('Y-m-d H:i:s', $data['datum']));
			$xonx = explode('on', $data['xonx']);
			$tpl->assign('xonx1', $xonx[0]);
			$tpl->assign('xonx2', $xonx[1]);			
			$result = $db->query('SELECT `scoreID`, `lID`, `ownscore`, `oppscore` FROM '.DB_PRE.'ecp_wars_scores WHERE wID = '.$id.' ORDER BY scoreID ASC');
			$maps = array();
			while($row = mysql_fetch_assoc($result)) {
				$row['i'] = @++$i;
				$db->query('SELECT locationID, locationname FROM '.DB_PRE.'ecp_wars_locations WHERE gID = '.$data['gID']);
				while($subrow = $db->fetch_assoc()) {
					($subrow['locationID'] == $row['lID']) ? $sub = 'selected="selected"' : $sub = '';
					@$row['maps'] .= '<option '.$sub.' value="'.$subrow['locationID'].'">'.htmlspecialchars($subrow['locationname']).'</option>';
				}
				$maps[] = $row;
			}
			$tpl->assign('maps', $maps);
			
			$db->query('SELECT username FROM '.DB_PRE.'ecp_wars_teilnehmer LEFT JOIN '.DB_PRE.'ecp_user ON (ID = userID) WHERE warID = '.$id.' ORDER BY username ASC');
			while($row = $db->fetch_assoc()) {
				@$players .= htmlspecialchars($row['username']).', ';
			}
			$tpl->assign('ownplayers', substr($players, 0, strlen($players)-2));
			ob_start();
			$tpl->display(DESIGN.'/tpl/admin/clanwars_add.html');
			$content = ob_get_contents();
			ob_end_clean();
			main_content(FINISH, $content, '',1);		
		}
	} else {
		table(ERROR,NO_ADMIN_RIGHTS);
	}
}
function admin_clanwars_editnext($id) {
	if(@$_SESSION['rights']['admin']['clanwars']['edit_next'] OR @$_SESSION['rights']['superadmin']) {	
		global $db;
		if(isset($_POST['datum'])) {
			if(!$_POST['oppID']) {
				$sql = sprintf('INSERT INTO '.DB_PRE.'ecp_wars_opp (`oppname`, `oppshort`, `homepage`, `country`) VALUES (\'%s\', \'%s\',\'%s\',\'%s\')', strsave($_POST['oppname']), strsave($_POST['oppshort']), strsave($_POST['homepage']), strsave($_POST['country']));
			} else {
				$sql = sprintf('UPDATE '.DB_PRE.'ecp_wars_opp SET `oppname` = \'%s\', `oppshort` = \'%s\', `homepage` = \'%s\', `country` = \'%s\' WHERE oppID = %d', strsave($_POST['oppname']), strsave($_POST['oppshort']), strsave(check_url($_POST['homepage'])), strsave($_POST['country']), @$_POST['oppID']);
			}
			if($db->query($sql)) {
				!$_POST['oppID'] ? $oppid = $db->last_id() : $oppid = (int)$_POST['oppID'];
				$lang = array();
				foreach($_POST AS $key => $value) {
					if(strpos($key, 'cription_')) {
						$lang[substr($key,strpos($key, '_')+1)] = $value;
					}
				}
				$players = array();
				foreach($_POST['players'] AS $value) {
					$value = trim($value);
					if(strpos($value, 'team_') !== false) {
						$db->query('SELECT userID FROM '.DB_PRE.'ecp_members WHERE teamID = '.(int)substr($value, strpos($value, '_')+1));
						while($row = $db->fetch_assoc()) {
							if(!in_array($row['userID'], $players)) 
								$players[] = $row['userID'];
						}
					} elseif (strpos($value, 'member_') !== false) {
						$ids = substr($value, strpos($value, '_')+1);
						if(!in_array($ids, $players)) $players[] = $ids;
					}
				}	
							
				$sql = sprintf('UPDATE '.DB_PRE.'ecp_wars SET 
								`tID` = %d,  
								`mID` = %d,  
								`gID` = %d,  
								`datum` = %d,  
								`xonx` = \'%s\', 
								hinweise = \'%s\', 
								`oID` = %d, 
								`matchlink` = \'%s\', 
								`resultbylocations` = %d, 
								`server` = \'%s\',
								`livestream` = \'%s\',
								`pw` = \'%s\',
								`meldefrist` = %d
								 WHERE warID = %d',
								(int)$_POST['teamID'],
								(int)$_POST['matchtypeID'],
								(int)$_POST['gameID'],
								strtotime($_POST['datum']), 
								(int)$_POST['xonx1'].'on'.(int)$_POST['xonx2'], 
								strsave(json_encode($lang)), 
								$oppid, 
								strsave(check_url($_POST['matchlink'])), 
								(int)@$_POST['winbymaps'], 
								strsave($_POST['server']), 
								strsave($_POST['livestream']), 
								strsave($_POST['pw']), 
								strtotime($_POST['meldefrist']), $id);
				if($db->query($sql)) {
					//$db->query('DELETE FROM '.DB_PRE.'ecp_wars_teilnehmer WHERE warID  = '.$id);
					$aktive = array();
					$db->query('SELECT userID FROM '.DB_PRE.'ecp_wars_teilnehmer WHERE warID  = '.$id);
					while($row = $db->fetch_assoc()) {
						$aktive[$row['userID']] = true;
					}
					$db->query('SELECT scoreID FROM '.DB_PRE.'ecp_wars_scores WHERE wID = '.$id.' ORDER BY scoreID ASC');
					$ids = array();
					while($row = $db->fetch_assoc()) {
						$ids[] = $row['scoreID'];
					}
					$own = 0;
					$opp = 0;
					foreach($_POST AS $key=>$value) {
						if(strpos($key, 'map_') !== false) {
							@$i++;
							if((int)@$_POST['winbymaps']) {
								if((int)$_POST['score_'.$i.'_own'] >  (int)$_POST['score_'.$i.'_opp']) {
									$own++;
								} elseif ((int)$_POST['score_'.$i.'_own'] <  (int)$_POST['score_'.$i.'_opp']) {
									$opp++;
								} else {
									$opp++;
									$own++;
								}
							} else {
								$own += (int)$_POST['score_'.$i.'_own'];
								$opp += (int)$_POST['score_'.$i.'_opp'];								
							}
							if(isset($ids[$i-1])) {
								$db->query(sprintf('UPDATE '.DB_PRE.'ecp_wars_scores SET `lID` = %d, `ownscore` = %d, `oppscore` = %d WHERE scoreID = %d', (int)$value, (int)$_POST['score_'.$i.'_own'], (int)$_POST['score_'.$i.'_opp'], $ids[$i-1]));	
							} else {
								$db->query(sprintf('INSERT INTO '.DB_PRE.'ecp_wars_scores (`wID`, `lID`, `ownscore`, `oppscore`) VALUES (%d, %d, %d, %d)', $id, (int)$value, (int)$_POST['score_'.$i.'_own'], (int)$_POST['score_'.$i.'_opp']));
							}							
						}
					}
					if(count($players)) {
						$text = $db->fetch_assoc('SELECT `content`, `content2`  FROM '.DB_PRE.'ecp_texte WHERE name = "NEXT_WAR_MSG" AND lang = "'.DEFAULT_LANG.'"');
						if($_POST['messagemode'] == 1) {
							foreach($players AS $value) {
								if(!isset($aktive[(int)$value])) {
									$db->query('INSERT INTO '.DB_PRE.'ecp_wars_teilnehmer (warID, userID) VALUES ('.$id.', '.(int)$value.')');
									message_send($value, 0, $text['content2'], str_replace('{link}', '<a href="'.SITE_URL.'?section=clanwars&action=nextwar&id='.$id.'">'.SITE_URL.'?section=clanwars&action=nextwar&id='.$id.'</a>', $text['content']), 0,1);
								} else {
									$aktive[(int)$value] = false;
								}
							}
						} elseif ($_POST['messagemode'] == 2) {
							foreach($players AS $value) {
								if(!isset($aktive[(int)$value])) {
									$db->query('INSERT INTO '.DB_PRE.'ecp_wars_teilnehmer (warID, userID) VALUES ('.$id.', '.(int)$value.')');
									send_email($db->result(DB_PRE.'ecp_user', 'email', 'ID = '.(int)$value), $text['content2'], str_replace('{link}', SITE_URL.'?section=clanwars&action=nextwar&id='.$id, $text['content']), 1);
								} else {
									$aktive[(int)$value] = false;
								}								
							}
						} elseif ($_POST['messagemode'] == 3) {
							foreach($players AS $value) {
								if(!isset($aktive[(int)$value])) {
									$db->query('INSERT INTO '.DB_PRE.'ecp_wars_teilnehmer (warID, userID) VALUES ('.$id.', '.(int)$value.')');
									message_send($value, 0, $text['content2'], str_replace('{link}', '<a href="'.SITE_URL.'?section=clanwars&action=nextwar&id='.$id.'">'.SITE_URL.'?section=clanwars&action=nextwar&id='.$id.'</a>', $text['content']), 0,1);
									send_email($db->result(DB_PRE.'ecp_user', 'email', 'ID = '.(int)$value), $text['content2'], str_replace('{link}', SITE_URL.'?section=clanwars&action=nextwar&id='.$id, $text['content']), 1);
								} else {
									$aktive[(int)$value] = false;
								}									
							}
						} else {
							foreach($players AS $value) {
								if(!isset($aktive[(int)$value])) {
									$db->query('INSERT INTO '.DB_PRE.'ecp_wars_teilnehmer (warID, userID) VALUES ('.$id.', '.(int)$value.')');
								} else {
									$aktive[(int)$value] = false;
								}								
							}
						}
					}
					foreach($aktive AS $key=>$value) if($value == true) $db->query('DELETE FROM '.DB_PRE.'ecp_wars_teilnehmer WHERE userID = '.$key.' AND warID = '.$id);
					header1('?section=admin&site=clanwars');
				}
			}
		} else {
			$data = $db->fetch_assoc('SELECT `tID`, `mID`, `gID`, `datum`, `xonx`, `hinweise`, `server`, `pw`, meldefrist, livestream, `oID`, `matchlink`, `resultbylocations`, `oppname`, `oppshort`, `homepage`, `country` FROM '.DB_PRE.'ecp_wars LEFT JOIN '.DB_PRE.'ecp_wars_opp ON (oppID = oID) WHERE warID = '.$id);
			$tpl = new smarty;	
			foreach($data AS $key=>$value) $tpl->assign($key, $value);
			$tpl->assign('opps', get_opps($data['oID']));
			$tpl->assign('countries', form_country($data['country']));
			$tpl->assign('games',get_games_form($data['gID']));
			$tpl->assign('teams',get_teams_form($data['tID']));
			$tpl->assign('matchtype',get_matchtype_form($data['mID']));
			$tpl->assign('lang', get_languages(json_decode($data['hinweise'], true)));
			$tpl->assign('func', 'editnext&id='.$id);
			$tpl->assign('datum', date('Y-m-d H:i:s', $data['datum']));
			$tpl->assign('meldefrist', date('Y-m-d H:i:s', $data['meldefrist']));
			$xonx = explode('on', $data['xonx']);
			$tpl->assign('xonx1', $xonx[0]);
			$tpl->assign('xonx2', $xonx[1]);			
			$result = $db->query('SELECT `scoreID`, `lID`, `ownscore`, `oppscore` FROM '.DB_PRE.'ecp_wars_scores WHERE wID = '.$id.' ORDER BY scoreID ASC');
			$maps = array();
			while($row = mysql_fetch_assoc($result)) {
				$row['i'] = @++$i;
				$db->query('SELECT locationID, locationname FROM '.DB_PRE.'ecp_wars_locations WHERE gID = '.$data['gID']);
				while($subrow = $db->fetch_assoc()) {
					($subrow['locationID'] == $row['lID']) ? $sub = 'selected="selected"' : $sub = '';
					@$row['maps'] .= '<option '.$sub.' value="'.$subrow['locationID'].'">'.htmlspecialchars($subrow['locationname']).'</option>';
				}
				$maps[] = $row;
			}
			$tpl->assign('maps', $maps);
			$db->query('SELECT userID FROM '.DB_PRE.'ecp_wars_teilnehmer WHERE warID = '.$id);
			$players = array();
			while($row = $db->fetch_assoc()) {
				$players[] = $row['userID'];
			}
			$tpl->assign('members', get_cw_members($players));
			ob_start();
			$tpl->display(DESIGN.'/tpl/admin/clanwars_next.html');
			$content = ob_get_contents();
			ob_end_clean();
			main_content(CLANWARS_EDIT, $content, '',1);		
		}
	} else {
		table(ERROR, NO_ADMIN_RIGHTS);
	}
}

if (!isset($_SESSION['rights']['admin']['clanwars']) AND !isset($_SESSION['rights']['superadmin'])) {
	table(ERROR, NO_ADMIN_RIGHTS);
} else {
	if(isset($_GET['func'])) {
		switch($_GET['func']) {
			case 'add':
				admin_clanwars_add();
				break;
			case 'edit':
				admin_clanwars_edit((int)$_GET['id']);
				break;
			case 'del':
				admin_clanwars_del((int)$_GET['id']);
				break;	
			case 'addnext':
				admin_clanwars_addnext();
			break;
			case 'finish':
				admin_clanwars_finish((int)$_GET['id']);
			break;	
			break;
			case 'fightusadd':
				admin_clanwars_add((int)$_GET['id']);
			break;	
			case 'fightusnext':
				admin_clanwars_addnext((int)$_GET['id']);
			break;	
			case 'editnext':
				admin_clanwars_editnext((int)$_GET['id']);
			break;		
			default:
				admin_clanwars();		
		}
	} else {
		admin_clanwars();
	}
} 
?>
