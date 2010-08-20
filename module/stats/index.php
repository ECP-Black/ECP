<?php
	if(isset($_SESSION['rights']['public']['stats']['view']) OR @$_SESSION['rights']['superadmin']) {
		global $installed, $monatsnamen, $groups;
		$row = $db->fetch_assoc('SELECT SUM(visits) as visits, SUM(hits) as hits, SUM(userhits) as userhits FROM '.DB_PRE.'ecp_stats_jahr');
		$search = array('{tage}','{datum}','{vtage}','{gvisits}','{ghits}','{avgvisits}','{avghits}',
						'{avghitsperuser}','{datemonathits}','{monathits}','{datemonatvisit}','{monatvisit}',
						'{datetaghits}','{taghits}','{datetagvisit}','{tagvisit}','{datestundethits}',
						'{stundethits}','{datestundevisit}','{stundevisit}');
		$tage = (time()-$installed)/86400;
		$mhits = $db->fetch_assoc('SELECT monat, jahr, hits FROM '.DB_PRE.'ecp_stats_monat ORDER BY hits DESC LIMIT 1');
		$mvisits = $db->fetch_assoc('SELECT monat, jahr, visits FROM '.DB_PRE.'ecp_stats_monat ORDER BY visits DESC LIMIT 1');
		$dhits = $db->fetch_assoc('SELECT tag, monat, jahr, hits FROM '.DB_PRE.'ecp_stats_tag ORDER BY hits DESC LIMIT 1');
		$dvisits = $db->fetch_assoc('SELECT tag, monat, jahr, visits FROM '.DB_PRE.'ecp_stats_tag ORDER BY visits DESC LIMIT 1');
		$shits = $db->fetch_assoc('SELECT stunde, tag, monat, jahr, hits FROM '.DB_PRE.'ecp_stats_stunde ORDER BY hits DESC LIMIT 1');
		$svisits = $db->fetch_assoc('SELECT stunde, tag, monat, jahr, visits FROM '.DB_PRE.'ecp_stats_stunde ORDER BY visits DESC LIMIT 1');		
		$replace = array(floor($tage), 
						date(LONG_DATE, $installed), 
						$db->result(DB_PRE.'ecp_stats_tag', 'COUNT(visits)', 'visits != 0'), 
						format_nr($row['visits']), 
						format_nr($row['hits']),
						format_nr($row['visits']/$tage,2),
						format_nr($row['hits']/$tage,2),
						format_nr($row['hits']/$row['visits'],2),
						'<a href="#" onclick="load_year('.$mhits['jahr'].');return false;">'.$monatsnamen[$mhits['monat']].' '.$mhits['jahr'].'</a>',
						format_nr($mhits['hits']),
						'<a href="#" onclick="load_year('.$mvisits['jahr'].');return false;">'.$monatsnamen[$mvisits['monat']].' '.$mvisits['jahr'].'</a>',
						format_nr($mvisits['visits']),
						'<a href="#" onclick="load_month('.$dhits['jahr'].','.$dhits['monat'].');return false;">'.$dhits['tag'].'. '.$monatsnamen[$dhits['monat']].' '.$dhits['jahr'].'</a>',
						format_nr($dhits['hits']),
						'<a href="#" onclick="load_month('.$dvisits['jahr'].','.$dvisits['monat'].');return false;">'.$dvisits['tag'].'. '.$monatsnamen[$dvisits['monat']].' '.$dvisits['jahr'].'</a>',
						format_nr($dvisits['visits']),
						'<a href="#" onclick="load_day('.$shits['jahr'].','.$shits['monat'].','.$shits['tag'].');return false;">'.$shits['tag'].'. '.$monatsnamen[$shits['monat']].' '.$shits['jahr'].' '.$shits['stunde'].':00 - '.$shits['stunde'].':59</a>',
						format_nr($shits['hits']),
						'<a href="#" onclick="load_day('.$svisits['jahr'].','.$svisits['monat'].','.$svisits['tag'].');return false;">'.$svisits['tag'].'. '.$monatsnamen[$svisits['monat']].' '.$svisits['jahr'].' '.$svisits['stunde'].':00 - '.$svisits['stunde'].':59</a>',
						format_nr($svisits['visits']),
					);
		$tpl = new smarty;
		$tpl->assign('webstats', str_replace($search, $replace, WEB_STATS_TXT));
		$tpl->assign('jahr', date('Y'));
		$tpl->assign('monat', date('m'));
		$tpl->assign('tag', date('d'));
		$tpl->assign('installed', date('d.m.Y', $installed));
		$tpl->assign('tage', floor($tage));
		$tpl->assign('visits', format_nr($row['visits']));
		$tpl->assign('hits', format_nr($row['hits']));
		$tpl->assign('messages',  format_nr(mysql_result($db->query('SHOW TABLE STATUS LIKE "%ecp_messages"'),0, 'Auto_increment')-1));
		$tpl->assign('awards', $db->result(DB_PRE.'ecp_awards', 'COUNT(awardID)', '1'));
		$tpl->assign('news', format_nr($db->result(DB_PRE.'ecp_news', 'COUNT(newsID)', '1')));
		$tpl->assign('surveys', format_nr($db->result(DB_PRE.'ecp_survey', 'COUNT(surveyID)', '1')));
		$tpl->assign('clanwars', format_nr($db->result(DB_PRE.'ecp_wars', 'COUNT(warID)', '1')));
		$tpl->assign('comments', format_nr($db->result(DB_PRE.'ecp_comments', 'COUNT(comID)', 'bereich != "guestbook"')));
		$tpl->assign('members', format_nr($db->result(DB_PRE.'ecp_user', 'COUNT(ID)', '1')));
		$row = $db->fetch_assoc('SELECT username, ID, registerdate FROM '.DB_PRE.'ecp_user ORDER BY registerdate DESC LIMIT 1');
		$tpl->assign('lastmember', '<a href="?section=user&id='.$row['ID'].'">'.$row['username'].'</a> ('.date('d.m.Y', $row['registerdate']).')');
		$row = $db->fetch_assoc('SELECT SUM(traffic) as traffic, COUNT(*) as anzahl FROM '.DB_PRE.'ecp_downloads');
		$tpl->assign('downloads', format_nr($row['anzahl']));
		$tpl->assign('traffic', goodsize($row['traffic']));
		$tpl->assign('money', format_nr($db->result(DB_PRE.'ecp_user_stats', 'SUM(money)', '1')));
		$tpl->assign('members', format_nr($db->result(DB_PRE.'ecp_user', 'COUNT(ID)', '1')));
		$row = $db->fetch_assoc('SELECT SUM(images) as images, COUNT(*) as gallery FROM '.DB_PRE.'ecp_gallery');
		$tpl->assign('images', format_nr($row['images']));
		$tpl->assign('galleries', format_nr($row['gallery']));
		$row = $db->fetch_assoc('SELECT SUM(threads) as threads, SUM(posts) AS posts FROM '.DB_PRE.'ecp_forum_boards');
		$tpl->assign('threads', format_nr($row['threads']));
		$tpl->assign('posts', format_nr($row['posts']));		
				
		ob_start();	
		$tpl->display(DESIGN.'/tpl/stats/overview.html');
		$content = ob_get_contents();
		ob_end_clean();			
		main_content(STATS, $content, '', 1);		
	} else {
		table(ACCESS_DENIED, NO_ACCESS_RIGHTS);
	}
?>