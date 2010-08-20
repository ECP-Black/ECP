<?php
if (!isset($_SESSION['rights']['admin']['lotto']) AND !isset($_SESSION['rights']['superadmin'])) {
	table(ERROR, NO_ADMIN_RIGHTS);
} else {
	global $db;
	$config = $db->fetch_assoc('SELECT `lottoon`, `jackpot`, `preis`, `pro4er`, `pro3er`, `pro2er`, `jackpotraise`, free_scheine FROM '.DB_PRE.'ecp_lotto');
	if(isset($_POST['submit'])) {
		if($_POST['jackpot'] == '' OR $_POST['jackpotraise'] == '' OR $_POST['preis'] == '' OR $_POST['pro4er'] == '' OR 
			$_POST['pro3er'] == '' OR $_POST['pro2er'] == '' OR $_POST['free_scheine'] == '') {
			table(ERROR, NOT_NEED_ALL_INPUTS);
			$tpl = new smarty();
			$nr = 0;
			foreach($_POST AS $key => $value)  {
				if(strpos($key, 'ziehung_') !== false) {
					$tage[] = array('wochentag'=>$value, array($_POST['stunde_'.$nr],$_POST['minute_'.$nr]));
					$nr++;
				}
				$tpl->assign($key, $value);
			}
			$tpl->assign('tage', $tage);
			ob_start();
			$tpl->display(DESIGN.'/tpl/admin/lotto.html');
			$content = ob_get_contents();
			ob_end_clean();			
			main_content(LOTTO, $content, '', 1);
		} else {
			$sql = sprintf('UPDATE '.DB_PRE.'ecp_lotto SET `lottoon` = %d, `jackpot` = %f, `preis` = %f, `pro4er` = %d, `pro3er` = %d, `pro2er` = %d, `jackpotraise` = %f, free_scheine = %d', 
						$_POST['lottoon'],str_replace(',', '.', $_POST['jackpot']),str_replace(',', '.', $_POST['preis']),$_POST['pro4er'],$_POST['pro3er'],$_POST['pro2er'],str_replace(',', '.', $_POST['jackpotraise']), (int)$_POST['free_scheine']);
			if($db->query($sql)) {
				$db->query('TRUNCATE TABLE '.DB_PRE.'ecp_lotto_zeiten');
				$nr = 0;
				foreach($_POST AS $key => $value)  {
					if(strpos($key, 'ziehung_') !== false) {
						$minute = (int)$_POST['minute_'.$nr];
						if($minute < 10 AND strlen($minute) == 1) $minute = '0'.$minute; 
						$db->query('INSERT INTO '.DB_PRE.'ecp_lotto_zeiten VALUES ('.(int)$value.', \''.(int)$_POST['stunde_'.$nr].':'.$minute.'\')');
						$nr++;
					}
				}
				if($config['lottoon'] == 1 AND $_POST['lottoon'] == 0) {
					lotto_runde_ende();
				} elseif ($config['lottoon'] == 0 AND $_POST['lottoon'] == 1) {
					lotto_runde_start();
				}
				header1('?section=admin&site=lotto&success=1');
			}
		}
	} else {
		$tpl = new smarty;
		foreach($config AS $key=>$value) $tpl->assign($key, $value);
		$tage = array();
		$db->query('SELECT wochentag, uhrzeit FROM '.DB_PRE.'ecp_lotto_zeiten');
		$ziehungen = $db->num_rows();
		if($ziehungen) {
			while($row = $db->fetch_assoc()) {
				$row['uhrzeit'] = explode(':', $row['uhrzeit']);
				$tage[] = $row;
			}
		} else {
			$tage = array('wochentag'=>'0', 'uhrzeit'=>array('18','00'));
			$db->query('INSERT INTO '.DB_PRE.'ecp_lotto_zeiten VALUES(1, \'18:00\')');
			$ziehungen++;
		}
		$tpl->assign('tage', $tage);
		$tpl->assign('ziehungen', $ziehungen);
		if(isset($_GET['success'])) {
			table(INFO, SUCCESS_EDIT);
		}
		ob_start();
		$tpl->display(DESIGN.'/tpl/admin/lotto.html');
		$content = ob_get_contents();
		ob_end_clean();			
		main_content(LOTTO, $content, '', 1);
	}
}
?>