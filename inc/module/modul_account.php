<?php
	if(isset($_SESSION['userID'])) {
		$tpl1 = new smarty();
		$tpl1->assign('anzahl', $db->result(DB_PRE.'ecp_messages', 'COUNT(msgID)', 'touser='.$_SESSION['userID'].' AND readed = 0 AND del = 0'));
		$tpl1->display(DESIGN.'/tpl/account/account_menu_mini.html');
	} else {
		$tpl1 = new smarty();
		$tpl1->display(DESIGN.'/tpl/account/login_mini.html');		
	}
?>