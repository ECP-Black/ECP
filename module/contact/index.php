<?php
	if(@$_SESSION['rights']['public']['contact']['view'] OR @$_SESSION['rights']['superadmin']) {
		if(isset($_POST['name'])) {
			if($_POST['name'] == '' OR $_POST['subject'] == '' OR $_POST['comment'] == '' OR $_POST['email'] == '' OR !check_email($_POST['email']) OR $_POST['captcha']== '') {
				table(ERROR, NOT_NEED_ALL_INPUTS);
				$tpl = new smarty;	
				ob_start();
				$tpl->display(DESIGN.'/tpl/contact/contact.html');
				$content = ob_get_contents();
				ob_end_clean();
				main_content(CONTACT, $content, '',1);					
			} elseif (strtolower($_POST['captcha']) != strtolower($_SESSION['captcha'])) {
				table(ERROR, CAPTCHA_WRONG);
				$tpl = new smarty;	
				ob_start();
				$tpl->display(DESIGN.'/tpl/contact/contact.html');
				$content = ob_get_contents();
				ob_end_clean();
				main_content(CONTACT, $content, '',1);					
			} else {
				unset($_SESSION['captcha']);
				if(send_email(SITE_EMAIL, $_POST['subject'], $_POST['comment']."\n\n\n\n Send from IP: $_SERVER[REMOTE_ADDR]", 0, $_POST['email'], $_POST['name'])) {
					if(send_email($_POST['email'], CONTACT_VALID, CONTACT_MSG, 0, SITE_EMAIL, CLAN_NAME)) {
						table(INFO, CONTACT_SUCCESS);
					}
				} else {
					table(ERROR, CONTACT_FAILED);
				}
			}
		} else {
			$tpl = new smarty;	
			ob_start();
			$tpl->display(DESIGN.'/tpl/contact/contact.html');
			$content = ob_get_contents();
			ob_end_clean();
			main_content(CONTACT, $content, '',1);				
		}
	} else
		echo table(ACCESS_DENIED, NO_ACCESS_RIGHTS);
?>