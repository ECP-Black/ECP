<?php
	if(@$_SESSION['rights']['public']['teamspeak']['view'] OR @$_SESSION['rights']['superadmin']) {
		global $db;
		include_once('ts2status.php');
		include_once('ts3/TeamSpeak3.php');
		$result = $db->query('SELECT tsID, ip, port, qport, response, datum, serverart FROM '.DB_PRE.'ecp_teamspeak WHERE aktiv = 1 ORDER BY aktiv ASC');
		while($row = mysql_fetch_assoc($result)) {
			if($row['serverart'] == 1) {			//Teamspeak 2
				if($row['datum'] + SERVER_CACHE_REFRESH < time()) {
					
				} else {
					$response = unserialize($row['response']);
				}				
			} elseif ($row['serverart'] == 2) {		//Teamspeak 3
				if($row['datum'] + SERVER_CACHE_REFRESH < time()) {
					$ts3 = TeamSpeak3::factory("serverquery://$row[ip]:$row[qport]/?server_port=$row[port]");
					$server['info'] = $ts3->getInfo();
					$db->query('UPDATE '.DB_PRE.'ecp_teamspeak set datum = '.time().', response = \''.strsave(serialize($server)).'\' WHERE tsID = '.$row['tsID']);
					print_r($server);
				} else {
					$response = unserialize($row['response']);
					print_r($response);
				}	
			}
		}		
	} else
		echo table(ACCESS_DENIED, NO_ACCESS_RIGHTS);
?>