<?php
	if(isset($_GET['changelang'])) {
		if(file_exists('inc/language/'.$_GET['changelang'].'.php')) {
			setcookie('lang', $_GET['changelang'], time()+365*86400);
			$_SESSION['lang'] = $_GET['changelang'];
			header1('?'.str_replace('&changelang='.$_GET['changelang'], '', $_SERVER['QUERY_STRING']));
		}
	}
	//------------------------------ Autologin Check START ---------------------------------------------//
	if(!isset($_SESSION['userID']) AND isset($_COOKIE['userID'])) {
		$data = $db->fetch_assoc('SELECT status, ID, username, email, passwort, lastforum FROM '.DB_PRE.'ecp_user WHERE ID = '.(int)$_COOKIE['userID']);
		if(isset($data['username']) AND sha1($data['passwort']) == $_COOKIE['passwort']) {
			if($data['status'] == 0) {
				update_rights();	
			} elseif ($data['status'] == 2) {
				setcookie('userID', '', time()-60000, '/');
				setcookie('passwort', '', time()-60000, '/');			
				session_destroy();				
				$ban = $db->fetch_assoc('SELECT username, vonID, grund, bantime, endbantime FROM '.DB_PRE.'ecp_user_bans LEFT JOIN '.DB_PRE.'ecp_user ON (ID = vonID) WHERE userID = '.$data['ID']);
				$search = array('{bantime}', '{banuser}', '{endbantime}');
				$repalce = array(date(LONG_DATE, $ban['bantime']), '<a href="?section=user&id='.$ban['vonID'].'">'.$ban['username'].'</a>', date(LONG_DATE, $ban['endbantime']));
				$bantxt = str_replace($search, $repalce, BANNED);
				table(ACCESS_DENIED, $bantxt.$ban['grund']);
				die();
			} elseif ($data['status'] == 1) {
				$_SESSION['userID'] = $data['ID'];
				$_SESSION['username'] = $data['username'];
				$_SESSION['email'] = $data['email'];
				$_SESSION['lastforum']['time'] = $data['lastforum'];
				$db->query('UPDATE '.DB_PRE.'ecp_user SET lastlogin = '.time().' WHERE ID = '.$data['ID']);
				update_rights();
				header1('?section=account');
			} 
		} else {
			setcookie('userID', '', time()-60000, '/');
			setcookie('passwort', '', time()-60000, '/');
			update_rights();
		}
	} else {
		if(isset($_SESSION['userID'])) {
			if($db->result(DB_PRE.'ecp_user', 'update_rights', 'ID = '.$_SESSION['userID'])) {
				update_rights();
			}
		} else {
			$ses = $db->result(DB_PRE.'ecp_online', 'SIDDATA', 'SID = \''.session_id().'\' AND uID != 0');
			if($ses != '') {
				$_SESSION = unserialize($ses);
			} else {
				update_rights();
			}
		}
	}
	if(!isset($_SESSION['access_search'])) {
		update_rights();
	}
	//*------------------------------ Autologin Check ENDE ---------------------------------------------//
	//------------------------------ SUB-Domain Check START ---------------------------------------------//
	if(!isset($_SESSION['siteurl'])) {
		$_SESSION['siteurl'] = $_SERVER['SERVER_NAME'];
	} else {
		if($_SESSION['siteurl'] != $_SERVER['SERVER_NAME']) {
			foreach($_SESSION AS $key=>$value) unset($_SESSION[$key]);
			session_destroy();
		}
	}
	//------------------------------ SUB-Domain Check ENDE ---------------------------------------------//
	$row = $db->fetch_assoc('SELECT installed, ende, status FROM '.DB_PRE.'ecp_stats LEFT JOIN '.DB_PRE.'ecp_lotto_runden as a ON (zahl1 = 0) LEFT JOIN '.DB_PRE.'ecp_user ON (ID = '.(int)@$_SESSION['userID'].') ORDER BY a.ende DESC LIMIT 1');
	if(isset($_SESSION['userID']) AND $row['status'] == 2) {
		setcookie('userID', '', time()-60000, '/');
		setcookie('passwort', '', time()-60000, '/');			
		session_destroy();				
		$ban = $db->fetch_assoc('SELECT username, vonID, grund, bantime, endbantime FROM '.DB_PRE.'ecp_user_bans LEFT JOIN '.DB_PRE.'ecp_user ON (ID = vonID) WHERE userID = '.$_SESSION['userID']);
		$search = array('{bantime}', '{banuser}', '{endbantime}');
		$repalce = array(date(LONG_DATE, $ban['bantime']), '<a href="?section=user&id='.$ban['vonID'].'">'.$ban['username'].'</a>', date(LONG_DATE, $ban['endbantime']));
		$bantxt = str_replace($search, $repalce, BANNED);
		echo $bantxt.$ban['grund'];
		die();
	} elseif (isset($_SESSION['userID']) AND $row['status'] == null) {
		setcookie('userID', '', time()-60000, '/');
		setcookie('passwort', '', time()-60000, '/');			
		session_destroy();	
		header1('');
	}
	$installed = $row['installed'];
	if($row['ende'] != null AND $row['ende'] < time()) {
		lotto_runde_ende();
		lotto_runde_start();
	}
	//
	
	//------------------------------ User Online updaten START ---------------------------------------------//
	$db->query('DELETE FROM '.DB_PRE.'ecp_online WHERE betretten < '.(time()-ONLINE_RELOAD)); //Alte Einträge löschen
	if(isset($_SESSION['userID'])) {
		if($db->result(DB_PRE.'ecp_online', 'COUNT(uID)', 'uID = \''.$_SESSION['userID'].'\' OR SID = \''.session_id().'\'')) {
			$db->query('UPDATE '.DB_PRE.'ecp_online SET lastklick = '.time().', forum = '.((@$_GET['section'] == 'forum') ? 1 : 0 ).', fboardID = '.@(int)$_GET['boardID'].', fthreadID = '.@(int)$_GET['threadID'].', SIDDATA = \''.strsave(serialize($_SESSION)).'\' WHERE uID ='.$_SESSION['userID'].' OR SID = \''.session_id().'\' LIMIT 1');
			$eingetragen = true;
		} else {
			$db->query(sprintf('INSERT INTO '.DB_PRE.'ecp_online (`uID`, `betretten`, `lastklick`, `IP`, `forum`, `fboardID`, `fthreadID`, SID, SIDDATA) VALUES (%d, %d, %d, \'%s\', %d, %d, %d, \'%s\', \'%s\')',$_SESSION['userID'], time(), time(), $_SERVER['REMOTE_ADDR'], ((@$_GET['section'] == 'forum') ? 1 : 0 ), @(int)$_GET['boardID'], @(int)$_GET['threadID'], session_id(), strsave(serialize($_SESSION))));
			$eingetragen = false;
		}
		if((@$_SESSION['lastklick'] + USER_KLICK_RELOAD/1000) < get_mircotime()) {
			$_SESSION['lastklick'] = get_mircotime();
			$db->query('UPDATE '.DB_PRE.'ecp_user_stats set clicks= clicks+1 WHERE userID = '.$_SESSION['userID']);
		}
	} else {
		if($db->result(DB_PRE.'ecp_online', 'COUNT(IP)', 'SID = \''.session_id().'\'')) {
			$db->query('UPDATE '.DB_PRE.'ecp_online SET lastklick = '.time().', forum = '.((@$_GET['section'] == 'forum') ? 1 : 0 ).', fboardID = '.@(int)$_GET['boardID'].', fthreadID = '.@(int)$_GET['threadID'].', uID = 0 WHERE SID = \''.session_id().'\' LIMIT 1');
			$eingetragen = true;
		} else {
			$db->query(sprintf('INSERT INTO '.DB_PRE.'ecp_online (`betretten`, `lastklick`, `IP`, `forum`, `fboardID`, `fthreadID`, SID) VALUES (%d, %d, \'%s\', %d, %d, %d, \'%s\')', time(), time(), $_SERVER['REMOTE_ADDR'], ((@$_GET['section'] == 'forum') ? 1 : 0 ), @(int)$_GET['boardID'], @(int)$_GET['threadID'], session_id()));
			$eingetragen = false;
		}		
	}
	//------------------------------ User Online updaten ENDE ----------------------------------------------//
	//------------------------------ Webstats updaten START ------------------------------------------------//
	$agent=$_SERVER['HTTP_USER_AGENT']; 
	$os   ="Unbekannt"; 
	
	if   (strstr($agent, "Windows 98") !== false)      $os="Windows 98"; 
	elseif (strstr($agent, "NT 4.0") !== false)        $os="Windows NT "; 
	elseif (strstr($agent, "NT 5.1") !== false)        $os="Windows XP"; 
	elseif (strstr($agent, "Mac") !== false)           $os="Mac OS"; 
	elseif (strstr($agent, "Linux") !== false)         $os="Linux"; 
	elseif (strstr($agent, "Unix") !== false)          $os="Unix"; 
	elseif (strstr($agent, "NT 6.0") !== false)        $os="Windows Vista"; 
	
	//Browser ermitteln 
	if (strpos($agent, "Mozilla/5.0") !== false) { 
	$browser = "Mozilla"; 
	} 
	if (strpos($agent, "Mozilla/4") !== false) { 
	$browser = "Netscape"; 
	} 
	if (strpos($agent, "Mozilla/3") !== false) { 
	$browser = "Netscape"; 
	} 
	if (strpos($agent, "Firefox")  !== false || strpos($agent, "Firebird")  !== false) { 
	$browser = "Firefox"; 
	} 
	if (strpos($agent, "MSIE 7.0") !== false) { 
	$browser = "IE 7"; 
	} 
	if (strpos($agent, "MSIE 6.0") !== false) { 
	$browser = "IE 6"; 
	} 
	if (strpos($agent, "MSIE 5.0") !== false) { 
	$browser = "IE 5"; 
	} 
	if (strpos($agent, "Netscape") !== false) { 
	$browser = "Netscape"; 
	} 
	if (strpos($agent, "Camino") !== false) { 
	$browser = "Camino"; 
	} 
	if (strpos($agent, "Galeon") !== false) { 
	$browser = "Galeon"; 
	} 
	if (strpos($agent, "Konqueror") !== false) { 
	$browser = "Konqueror"; 
	} 
	if (strpos($agent, "Safari") !== false) { 
	$browser = "Safari"; 
	} 
	if (strpos($agent, "OmniWeb") !== false) { 
	$browser = "OmniWeb"; 
	} 
	if (strpos($agent, "Opera") !== false) { 
	$browser = "Opera"; 
	}
	if((eregi("bot", getenv("HTTP_USER_AGENT"))) || (ereg("Google", getenv("HTTP_USER_AGENT"))) || (ereg("Slurp", getenv("HTTP_USER_AGENT"))) || (ereg("Scooter", getenv("HTTP_USER_AGENT"))) || (eregi("Spider", getenv("HTTP_USER_AGENT"))) || (eregi("Infoseek", getenv("HTTP_USER_AGENT")))) $browser = "Bot";	
	if (!isset($browser)) { 
		$browser = "Unbekannt"; 
	}
	
    $dot = date("d-m-Y-H");
    $now = explode ("-",$dot);
    $nowHour = $now[3];
    $nowYear = $now[2];
    $nowMonth = $now[1];
    $nowDate = $now[0];
	$db->query('SELECT jahr from '.DB_PRE.'ecp_stats_jahr WHERE jahr = '.$nowYear);
    $jml = $db->num_rows();
    if ($jml <= 0) {
        $db->query('INSERT INTO '.DB_PRE.'ecp_stats_jahr (jahr) VALUES (\''.$nowYear.'\')');
        for ($i=1;$i<=12;$i++) {
	        $db->query('INSERT INTO '.DB_PRE.'ecp_stats_monat (`jahr`, `monat`) VALUES (\''.$nowYear.'\',\''.$i.'\')');
	        if ($i == 1) $TotalDay = 31;
	        if ($i == 2) {
	            if (($nowYear % 4) !== 0) {
		            $TotalDay = 28;
                } else {
		            $TotalDay = 29;
                }
            }
	        if ($i == 3) $TotalDay = 31;
	        if ($i == 4) $TotalDay = 30;
	        if ($i == 5) $TotalDay = 31;
	        if ($i == 6) $TotalDay = 30;
	        if ($i == 7) $TotalDay = 31;
	        if ($i == 8) $TotalDay = 31;
	        if ($i == 9) $TotalDay = 30;
	        if ($i == 10) $TotalDay = 31;
	        if ($i == 11) $TotalDay = 30;
	        if ($i == 12) $TotalDay = 31;
	        for ($k=1;$k<=$TotalDay;$k++) {
	            $db->query('INSERT INTO '.DB_PRE.'ecp_stats_tag (`jahr`, `monat`, `tag`) VALUES (\''.$nowYear.'\',\''.$i.'\',\''.$k.'\')');
            }
        }
    }

    $resulthour = $db->query('SELECT stunde from '.DB_PRE.'ecp_stats_stunde WHERE (jahr = '.$nowYear.') and (monat = '.$nowMonth.') and (tag = '.$nowDate.')');
    if ($db->num_rows() <= 0) {
        for ($z = 0;$z<=23;$z++) {
	        $db->query('INSERT INTO '.DB_PRE.'ecp_stats_stunde (`jahr`, `monat`, `tag`, `stunde`) VALUES (\''.$nowYear.'\',\''.$nowMonth.'\',\''.$nowDate.'\',\''.$z.'\')');
        }
        nulluhr();
    }
    IF(isset($_SESSION['userID'])) {
        $extra = ", userhits= userhits+1";
    }
    IF ($eingetragen) {
        $db->query('UPDATE '.DB_PRE.'ecp_stats_browser SET  hits=hits+1 WHERE (type = \'browser\' AND variable = \''.$browser.'\') OR (type = \'os\' AND variable = \''.$os.'\');');
        $db->query('UPDATE '.DB_PRE.'ecp_stats_jahr SET hits=hits+1'.@$extra.' WHERE jahr='.$nowYear.'');
        $db->query('UPDATE '.DB_PRE.'ecp_stats_monat SET hits=hits+1'.@$extra.' WHERE (jahr='.$nowYear.') and (monat='.$nowMonth.')');
        $db->query('UPDATE '.DB_PRE.'ecp_stats_tag SET hits=hits+1'.@$extra.' WHERE (jahr='.$nowYear.') and (monat='.$nowMonth.') and (tag='.$nowDate.')');
        $db->query('UPDATE '.DB_PRE.'ecp_stats_stunde SET hits=hits+1'.@$extra.' WHERE (jahr='.$nowYear.') and (monat='.$nowMonth.') and (tag='.$nowDate.') and (stunde='.$nowHour.')');
    } else {
        $db->query('UPDATE '.DB_PRE.'ecp_stats_browser SET hits=hits+1, visits=visits+1 WHERE (type = \'browser\' AND variable = \''.$browser.'\') OR (type = \'os\' AND variable = \''.$os.'\');');
        $db->query('UPDATE '.DB_PRE.'ecp_stats_jahr SET hits=hits+1, visits=visits+1'.@$extra.' WHERE jahr='.$nowYear.'');
        $db->query('UPDATE '.DB_PRE.'ecp_stats_monat SET hits=hits+1, visits=visits+1'.@$extra.' WHERE (jahr='.$nowYear.') and (monat='.$nowMonth.')');
        $db->query('UPDATE '.DB_PRE.'ecp_stats_tag SET hits=hits+1, visits=visits+1'.@$extra.' WHERE (jahr='.$nowYear.') and (monat='.$nowMonth.') and (tag='.$nowDate.')');
        $db->query('UPDATE '.DB_PRE.'ecp_stats_stunde SET hits=hits+1, visits=visits+1'.@$extra.' WHERE (jahr='.$nowYear.') and (monat='.$nowMonth.') and (tag='.$nowDate.') and (stunde='.$nowHour.')');
    }
    //------------------------------ Webstats updaten ENDE -------------------------------------------------//
    $result = $db->query('SELECT serverID, response FROM '.DB_PRE.'ecp_server WHERE aktiv = 1 AND stat = 1 AND ((SELECT datum FROM '.DB_PRE.'ecp_server_stats WHERE '.DB_PRE.'ecp_server_stats.sID = serverID ORDER BY datum DESC LIMIT 1) < '.(time() - (SERVER_LOG_INTERVALL * 60)).' OR (SELECT datum FROM '.DB_PRE.'ecp_server_stats WHERE '.DB_PRE.'ecp_server_stats.sID = serverID ORDER BY datum DESC LIMIT 1) is null)');
    if($db->num_rows()) {
    	update_server_cache(true);
    	while($row = mysql_fetch_assoc($result)) {
    		$row['response'] = unserialize($row['response']);
    		if($db->result(DB_PRE.'ecp_server_stats', 'COUNT(sID)', 'sID = '.$row['serverID'].' AND datum > '.(time() - (SERVER_LOG_INTERVALL * 60))) == 0)
    			$db->query('INSERT INTO '.DB_PRE.'ecp_server_stats (`sID`, `datum`, `players`) VALUES ('.$row['serverID'].', '.time().', '.(int)@$row['response']['s']['players'].');');
    	}
    }
    if(!@$ajax AND defined('VERSION')) make_menus();
?>