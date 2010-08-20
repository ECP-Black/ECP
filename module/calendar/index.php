<?php
function kalender() {
	global $db, $monatsnamen, $countries;
	if(!isset($_GET['month'])) $monat = date('m'); else $monat = (int)$_GET['month'];
	if(!isset($_GET['year'])) $jahr = date('Y'); else $jahr = (int)$_GET['year'];
	if($monat > 12) {
		$monat = 1;
		$jahr++;
	}
	if($monat <= 0) {
		$monat = 12;
		$jahr--;
	}
	if($jahr > 2034 OR $jahr < 1970) $jahr = date('Y');
	$tpl = new smarty;
	$wochentag = date('w', mktime(0, 0, 0, $monat, 1, $jahr));
	$woche = (int)date('W',  mktime(0, 0, 0, $monat, 1, $jahr));
	$tagemonat = date("t", mktime(0, 0, 0, $monat, 1, $jahr));
	$wochen = array();
	$wochen[] = array('woche' => $woche, 'akt' => '-1',  'events' => array());
	$start = mktime(0,0,0,$monat, 1, $jahr);
	$ende = mktime(23,59,59,$monat+1, 0, $jahr);
	// Kalander Anlegen //
	if($wochentag==0) {
		if($woche >= 52) $woche = date('W',  mktime(0, 0, 0, $monat, 2, $jahr))-1;
		$wochen[] = array('woche' => ++$woche, 'akt' => '-1', 'events' => array());
		next($wochen);
	}
	
	for($i=1;$i<=$tagemonat;$i++) {
		if($wochentag == 0) {
			$wochen[key($wochen)-1]['tage'][$wochentag] = $i.'.';
			if($i === (int)date('d') AND $monat == date('m') AND $jahr == date('Y'))
			$wochen[key($wochen)-1]['akt'] = date('w');
		} else
		$wochen[key($wochen)]['tage'][$wochentag] = $i.'.';
		$wochentag++;
		if($i === (int)date('d') AND $monat == date('m') AND $jahr == date('Y') AND date('w') != 0)
		$wochen[key($wochen)]['akt'] = date('w');
		if($wochentag > 6 AND $i<$tagemonat) {
			$woche++;
			if($woche >= 52) $woche = date('W',  mktime(0, 0, 0, $monat, $i+2, $jahr));
			$wochen[] = array('woche' => $woche, 'akt' => '-1', 'events' => array());
			$wochentag = 0;
			next($wochen);
		}
	}
	reset($wochen);
	// Kalender anlegen ende
	if(count($wochen[key($wochen)]['tage']) == 0) array_splice($wochen, key($wochen));
	$db->query('SELECT `warID`, '.DB_PRE.'ecp_wars.datum, `result`, `resultscore`, `tname`, `oppname`, `country`, '.DB_PRE.'ecp_wars_opp.homepage, `icon`, `gamename`, `matchtypename`, COUNT(comID) as comments, status 
				FROM '.DB_PRE.'ecp_wars 
				LEFT JOIN '.DB_PRE.'ecp_teams ON '.DB_PRE.'ecp_teams.tID = '.DB_PRE.'ecp_wars.tID 
				LEFT JOIN '.DB_PRE.'ecp_wars_games ON gID = gameID 
				LEFT JOIN '.DB_PRE.'ecp_wars_opp ON oID = oppID 
				LEFT JOIN '.DB_PRE.'ecp_wars_matchtype ON mID = matchtypeID 
				LEFT JOIN '.DB_PRE.'ecp_comments ON (subID = warID AND bereich = "clanwars") 
				WHERE '.DB_PRE.'ecp_wars.datum BETWEEN '.$start.' AND '.$ende.'
				GROUP BY warID
				ORDER BY '.DB_PRE.'ecp_wars.datum ASC');
	$clanwars = array();
	$lastday = 0;
	$lastdatum = 0;
	while($row = $db->fetch_assoc()) {
		$datum = $row['datum'];
		$row['datum'] = date('H:i', $row['datum']);
		($row['resultscore'] == '')  ? $row['resultscore'] = CLANWARS_OPEN : '';
		$row['countryname'] = $countries[$row['country']];
		if($lastday == date('d', $datum)) {
			$clanwars[] = $row;	
		} else {
			if(count($clanwars)) {
				foreach($wochen AS $key =>$value) {
					if($value['woche'] == date('W', $lastdatum)) {
						$wochen[$key]['events'][date('w', $lastdatum)] = kal_make_wars($clanwars);	
						break;
					}
				}
				$clanwars = array();	
			}
			$clanwars[] = $row;
		}
		$lastday = date('d', $datum);
		$lastdatum = $datum;
	}	
	if(count($clanwars)) {
		foreach($wochen AS $key =>$value) {
			if($value['woche'] == date('W', $datum)) {
				$wochen[$key]['events'][date('w', $datum)] = kal_make_wars($clanwars);	
				break;
			}
		}
	}
	//Geburtstage
	$db->query('SELECT username, country, ID, geburtstag, date_format(geburtstag, \'%Y\') AS jahr, date_format(geburtstag, \'%d\') AS tag
                    					FROM 
                    					    '.DB_PRE.'ecp_user
                    					WHERE 
                    					 	geburtstag != "00-00-0000" AND date_format(geburtstag, \'%m\') = '.$monat.' ORDER BY date_format(geburtstag, \'%d\') ASC');
	$birth = array();
	$lastday = 0;
	while($row = $db->fetch_assoc()) {
		$row['alter'] = $jahr - $row['jahr'];
		if($lastday == $row['tag']) {
			$birth[] = $row;	
		} else {
			if(count($birth)) {
				foreach($wochen AS $key =>$value) {
					if($value['woche'] == date('W', mktime(0,0,0,$monat, $lastday, $jahr))) {
						@$wochen[$key]['events'][date('w', mktime(0,0,0,$monat, $lastday, $jahr))] .= kal_make_birthday($birth);	
						break;
					}
				}
				$birth = array();	
			}
			$birth[] = $row;
		}
		$lastday = $row['tag'];
	}
	if(count($birth)) {
		foreach($wochen AS $key =>$value) {
			if($value['woche'] == date('W', mktime(0,0,0,$monat, $lastday, $jahr))) {
				@$wochen[$key]['events'][date('w', mktime(0,0,0,$monat, $lastday, $jahr))] .= kal_make_birthday($birth);
				break;	
			}
		}
	}
	// News einfügen
	$db->query('SELECT `newsID`, a.`userID`, `topicID`, a.`datum`, `headline`,
						`username`, `topicname`, COUNT(comID) AS comments, country
						FROM '.DB_PRE.'ecp_news as a
						LEFT JOIN '.DB_PRE.'ecp_user ON (a.userID = ID)  
						LEFT JOIN '.DB_PRE.'ecp_topics ON (topicID = tID) 
						LEFT JOIN '.DB_PRE.'ecp_comments ON (subID = newsID AND bereich = "news")
						WHERE (lang = "" OR lang LIKE ",%'.LANGUAGE.'%,") AND a.datum < '.time().' AND a.datum BETWEEN '.$start.' AND '.$ende.' AND (access = "" OR '.$_SESSION['access_search'].') GROUP BY newsID ORDER BY datum ASC');		
	$news = array();
	$lastday = 0;
	$lastdatum = 0;
	while($row = $db->fetch_assoc()) {
		$datum = $row['datum'];
		$row['datum'] = date('H:i', $row['datum']);
		$row['countryname'] = $countries[$row['country']];
		if($lastday == date('d', $datum)) {
			$news[] = $row;	
		} else {
			if(count($news)) {
				foreach($wochen AS $key =>$value) {
					if($value['woche'] == date('W', $lastdatum)) {
						@$wochen[$key]['events'][date('w', $lastdatum)] .= kal_make_news($news);
						break;	
					}
				}
				$news = array();	
			}
			$news[] = $row;
		}
		$lastday = date('d', $datum);
		$lastdatum = $datum;
	}	
	if(count($news)) {
		foreach($wochen AS $key =>$value) {
			if($value['woche'] == date('W', $datum)) {
				@$wochen[$key]['events'][date('w', $datum)] .= kal_make_news($news);
				break;	
			}
		}
	}
	//Kalender Einträge hinzufügen
	$db->query('SELECT `calID`, `eventname`, `datum`, `inhalt`, `userID`, `username`, `country` FROM `'.DB_PRE.'ecp_calendar`
						LEFT JOIN '.DB_PRE.'ecp_user ON (userID = ID)  
						WHERE datum BETWEEN '.$start.' AND '.$ende.' AND (access = "" OR '.$_SESSION['access_search'].') ORDER BY datum ASC');		
	$events = array();
	$lastday = 0;
	$lastdatum = 0;
	while($row = $db->fetch_assoc()) {
		$datum = $row['datum'];
		$row['datum'] = date('H:i', $row['datum']);
		$row['countryname'] = @$countries[$row['country']];
		$row['inhalt'] = json_decode($row['inhalt'], true);
		(isset($row['inhalt'][LANGUAGE])) ? $row['inhalt'] = $row['inhalt'][LANGUAGE] :  $row['inhalt'] = $row['inhalt'][DEFAULT_LANG];
		if($lastday == date('d', $datum)) {
			$events[] = $row;	
		} else {
			if(count($events)) {
				foreach($wochen AS $key =>$value) {
					if($value['woche'] == date('W', $lastdatum)) {
						@$wochen[$key]['events'][date('w', $lastdatum)] .= kal_make_events($events);
						break;	
					}
				}
				$events = array();	
			}
			$events[] = $row;
		}
		$lastday = date('d', $datum);
		$lastdatum = $datum;
	}	
	if(count($events)) {
		foreach($wochen AS $key =>$value) {
			if($value['woche'] == date('W', $datum)) {
				@$wochen[$key]['events'][date('w', $datum)] .= kal_make_events($events);
				break;	
			}
		}
	}	
	$tpl->assign('year', $jahr);
	$tpl->assign('monthz', $monat);
	$tpl->assign('month', $monatsnamen[(int)$monat]);
	$tpl->assign('kalender', $wochen);
	ob_start();
	$tpl->display(DESIGN.'/tpl/calendar/calendar.html');
	$content = ob_get_contents();
	ob_end_clean();
	if(isset($_GET['ajax'])) {
		ob_end_clean();
		echo html_ajax_convert($content);
		$db->close();
		die();
	} else {
		main_content(CALENDAR, '<div id="calendar_main">'.$content.'</div>', '',1);		
	}
}
function kal_make_wars($wars, $big=false) {
	$tpl = new smarty;
	($big ?  $tpl->assign('data', $wars) :  $tpl->assign('clanwars', $wars));
	ob_start();
	$tpl->display(DESIGN.'/tpl/calendar/calendar_wars'.($big ? '_mini' : '').'.html');
	$content = ob_get_contents();
	ob_end_clean();
	return $content;
}
function kal_make_birthday($birth, $big =false) {
	$tpl = new smarty;
	($big ?  $tpl->assign('data', $birth) :  $tpl->assign('birth', $birth));
	ob_start();
	$tpl->display(DESIGN.'/tpl/calendar/calendar_birth'.($big ? '_mini' : '').'.html');
	$content = ob_get_contents();
	ob_end_clean();
	return $content;
}
function kal_make_news($news, $big =false) {
	$tpl = new smarty;
	($big ?  $tpl->assign('data', $news) :  $tpl->assign('news', $news));
	ob_start();
	$tpl->display(DESIGN.'/tpl/calendar/calendar_news'.($big ? '_mini' : '').'.html');
	$content = ob_get_contents();
	ob_end_clean();
	return $content;
}
function kal_make_events($events, $big =false) {
	$tpl = new smarty;
	($big ?  $tpl->assign('data', $events) :  $tpl->assign('events', $events));
	ob_start();
	$tpl->display(DESIGN.'/tpl/calendar/calendar_events'.($big ? '_mini' : '').'.html');
	$content = ob_get_contents();
	ob_end_clean();
	return $content;
}
function calendar_view_day($tag, $monat, $jahr) {
	global $db, $countries;
	$start = mktime(0,0,0,$monat, $tag, $jahr);
	$ende = mktime(23,59,59,$monat,$tag,$jahr);
	$db->query('SELECT `warID`, '.DB_PRE.'ecp_wars.datum, `result`, `resultscore`, `tname`, `oppname`, `country`, '.DB_PRE.'ecp_wars_opp.homepage, `icon`, `gamename`, `matchtypename`, COUNT(comID) as comments, status 
				FROM '.DB_PRE.'ecp_wars 
				LEFT JOIN '.DB_PRE.'ecp_teams ON '.DB_PRE.'ecp_teams.tID = '.DB_PRE.'ecp_wars.tID 
				LEFT JOIN '.DB_PRE.'ecp_wars_games ON gID = gameID 
				LEFT JOIN '.DB_PRE.'ecp_wars_opp ON oID = oppID 
				LEFT JOIN '.DB_PRE.'ecp_wars_matchtype ON mID = matchtypeID 
				LEFT JOIN '.DB_PRE.'ecp_comments ON (subID = warID AND bereich = "clanwars") 
				WHERE '.DB_PRE.'ecp_wars.datum BETWEEN '.$start.' AND '.$ende.'
				GROUP BY warID
				ORDER BY '.DB_PRE.'ecp_wars.datum ASC');
	$clanwars = array();
	while($row = $db->fetch_assoc()) {
		$datum = $row['datum'];
		$row['datum'] = date('H:i', $row['datum']);
		($row['resultscore'] == '')  ? $row['resultscore'] = CLANWARS_OPEN : '';
		$row['countryname'] = $countries[$row['country']];
		$clanwars[]	=$row;
	}
	$birth = array();
	$db->query('SELECT username, country, ID, geburtstag, date_format(geburtstag, \'%Y\') AS jahr, date_format(geburtstag, \'%d\') AS tag
                    					FROM 
                    					    '.DB_PRE.'ecp_user
                    					WHERE 
                    					 	geburtstag != "00-00-0000" AND date_format(geburtstag, \'%m\') = '.$monat.' AND date_format(geburtstag, \'%d\') = '.$tag.' ORDER BY date_format(geburtstag, \'%d\') ASC');
	$birth = array();
	while($row = $db->fetch_assoc()) {
		$row['alter'] = $jahr - $row['jahr'];	
		$birth[] = $row;
	}
	$db->query('SELECT `newsID`, a.`userID`, `topicID`, a.`datum`, `headline`,
						`username`, `topicname`, COUNT(comID) AS comments, country
						FROM '.DB_PRE.'ecp_news as a
						LEFT JOIN '.DB_PRE.'ecp_user ON (a.userID = ID)  
						LEFT JOIN '.DB_PRE.'ecp_topics ON (topicID = tID) 
						LEFT JOIN '.DB_PRE.'ecp_comments ON (subID = newsID AND bereich = "news")
						WHERE (lang = "" OR lang LIKE ",%'.LANGUAGE.'%,") AND a.datum < '.time().' AND a.datum BETWEEN '.$start.' AND '.$ende.' AND (access = "" OR '.$_SESSION['access_search'].') GROUP BY newsID ORDER BY datum ASC');		
	$news = array();
	while($row = $db->fetch_assoc()) {
		$datum = $row['datum'];
		$row['datum'] = date('H:i', $row['datum']);
		$row['countryname'] = $countries[$row['country']];	
		$news[] = $row;
	}
	$db->query('SELECT `calID`, `eventname`, `datum`, `inhalt`, `userID`, `username`, `country` FROM `'.DB_PRE.'ecp_calendar`
						LEFT JOIN '.DB_PRE.'ecp_user ON (userID = ID)  
						WHERE datum BETWEEN '.$start.' AND '.$ende.' AND (access = "" OR '.$_SESSION['access_search'].') ORDER BY datum ASC');		
	$events = array();
	$lastday = 0;
	$lastdatum = 0;
	while($row = $db->fetch_assoc()) {
		$datum = $row['datum'];
		$row['datum'] = date('H:i', $row['datum']);
		$row['countryname'] = @$countries[$row['country']];
		$row['inhalt'] = json_decode($row['inhalt'], true);
		(isset($row['inhalt'][LANGUAGE])) ? $row['inhalt'] = $row['inhalt'][LANGUAGE] :  $row['inhalt'] = $row['inhalt'][DEFAULT_LANG];
		$events[] = $row;		
	}	
	ob_start();
	if(count($clanwars)) echo kal_make_wars($clanwars, true);
	if(count($birth)) echo kal_make_birthday($birth, true);
	if(count($news)) echo kal_make_news($news, true);
	if(count($events)) echo kal_make_events($events, true);
	$content = ob_get_contents();
	ob_end_clean();
	main_content(CALENDAR, $content, '',1);		
}
if(isset($_SESSION['rights']['public']['calendar']['view']) OR isset($_SESSION['rights']['superadmin'])) {
	if(isset($_GET['action'])) {
		switch($_GET['action']) {
			case 'viewday':
				calendar_view_day((int)$_GET['tag'],(int)$_GET['month'],(int)$_GET['year']);
			break;
			default:
				kalender();	
		}
	} else {
		kalender();	
	}
} else {
	table(ACCESS_DENIED, NO_ACCESS_RIGHTS);
}
?>