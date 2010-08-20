<?php
if(defined('VERSION')) {
	if(@$_SESSION['rights']['public']['survey']['view'] OR @$_SESSION['rights']['superadmin']) {
		$db->query('SELECT surveyID, frage, antworten as maxvotes, sperre FROM '.DB_PRE.'ecp_survey WHERE start <= '.time().' AND ende > '.time().' AND (access = "" OR '.$_SESSION['access_search'].') ORDER BY rand() DESC LIMIT 1');
		if($db->num_rows()) {
			$umf = $db->fetch_assoc();
			$tpls = new smarty();
			$antworten = array();
			if(isset($_COOKIE['surveys'][$umf['surveyID']]) AND $_COOKIE['surveys'][$umf['surveyID']]) {
				if(($_COOKIE['surveys'][$umf['surveyID']]+$umf['sperre']) > time()) {
					$umf['abstimmen'] = false;
				} else {
					$umf['abstimmen'] = true;
				}
			} elseif(isset($_SESSION['userID'])) {
				$zeit = @$db->result(DB_PRE.'ecp_survey_votes', 'votedatum', 'userID = '.$_SESSION['userID'].' AND surID = '.$umf['surveyID'].' ORDER BY votedatum DESC');
				if(((int)$zeit+$umf['sperre']) > time()) {
					$umf['abstimmen'] = false;
				} else {
					$umf['abstimmen'] = true;
				}
			} else {
				$zeit = $db->result(DB_PRE.'ecp_survey_votes', 'votedatum', 'IP = \''.$_SERVER['REMOTE_ADDR'].'\' ORDER BY votedatum DESC');
				if(((int)$zeit+$umf['sperre']) > time()) {
					$umf['abstimmen'] = false;
				} else {
					$umf['abstimmen'] = true;
				}	
			}
			$db->query('SELECT `answerID`, `answer`, `votes` FROM `'.DB_PRE.'ecp_survey_answers` WHERE sID = '.$umf['surveyID'].' ORDER BY answerID ASC');
			$sgesamt = 0;
			while($sub = $db->fetch_assoc()) {
				$sgesamt += $sub['votes'];
				$antworten[] = $sub;
			}
			foreach($antworten AS $key => $value) {
				if($sgesamt) {
					$antworten[$key]['prozent'] = round($value['votes']/$sgesamt*100,1);
				} else {
					$antworten[$key]['prozent'] = 0;
				}
				$antworten[$key]['votes'] = number_format($value['votes'], 0,'','.');
			}
			$umf['gesamt'] = number_format($sgesamt, 0,'','.');
			$umf['antworten'] = $antworten;			
			foreach($umf as $key => $value) $tpls->assign($key, $value);
			$tpls->display(DESIGN.'/tpl/modul/survey.html');
		} else {
			echo NO_ENTRIES;
		}
	} else {
		echo NO_ACCESS_RIGHTS;
	}
} else {
	echo 'Kein direktes Aufrufen der Datei!';
}
?>