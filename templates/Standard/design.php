<?php
	define('GOOGLE_MAP_WIDTH',  544);
	define('GOOGLE_MAP_HEIGHT', 800);
	function table($headline, $content) {
		$tpl = new smarty;
		$tpl->assign('headline', 'templates/'.DESIGN.'/picture.php?text='.base64_encode('» '.$headline));
		$tpl->assign('content', $content);
		$tpl->display(DESIGN.'/tpl/table.html');
	}
	function menu($headline, $content) {
		$tpl = new smarty;
		$tpl->assign('headline', $headline);
		$tpl->assign('content', $content);
		$tpl->display(DESIGN.'/tpl/menu.html');
	}
	function main_content($headline, $content, $footer = '', $mode = 0) {
		$tpl = new smarty;
		IF($mode == 1)
		    $tpl->assign('headline', 'templates/'.DESIGN.'/picture.php?text='.base64_encode('» '.$headline));
		else
			$tpl->assign('headline', $headline);
		$tpl->assign('content', $content);			
		$tpl->assign('footer', $footer);	
		$tpl->assign('mode', $mode);
		$tpl->display(DESIGN.'/tpl/content.html');
	}
?>