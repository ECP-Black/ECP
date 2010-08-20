<?php
	if(@$_SESSION['rights']['public']['membermap']['view'] OR @$_SESSION['rights']['superadmin']) {
		$api = new NXGoogleMapsAPI();
		// setup the visual design of the control
		$api->setWidth(GOOGLE_MAP_WIDTH);
		$api->setHeight(GOOGLE_MAP_HEIGHT);
		$api->setZoomFactor(6);
		$api->addControl(GLargeMapControl);
		$api->addControl(GMapTypeControl);
		$api->addControl(GOverviewMapControl);
		
		// add an address. the address is geocoded in the webbrowser, not by the server!
		global $db;
		$result = $db->query('SELECT wohnort, country, username, user_pic, koord, ID, COUNT( koord ) as anzahl FROM '.DB_PRE.'ecp_user WHERE koord != "" AND wohnort != "" GROUP BY koord ORDER BY ID ASC ');
		while($row = mysql_fetch_assoc($result)) {
			if($row['anzahl'] > 1) {
				$db->query('SELECT wohnort, country, username, user_pic, koord, ID FROM '.DB_PRE.'ecp_user WHERE koord = "'.$row['koord'].'" ORDER BY ID ASC');
				$html = '';
				$anzahl = 0;
				while($subrow = $db->fetch_assoc()) {
					$html .= ($anzahl != 0 ? '<hr />' : '' ).'<img src="images/flaggen/'.$subrow['country'].'.gif" /> <strong><a href="?section=user&id='.$subrow['ID'].'" target="_blank">'.$subrow['username'].'</a></strong><br />'.$subrow['wohnort'].'<br /><img src="'.($subrow['user_pic'] != '' ? 'images/user/'.$subrow['ID'].'_'.$subrow['user_pic'] : 'templates/'.DESIGN.'/images/nopic.png').'" alt="" title="'.strsave($subrow['username']).'" style="max-width: 150px" />'; 
					$anzahl++;
					if ($subrow['user_pic'] !='') 
						$bilder[] = 'images/user/'.$subrow['ID'].'_'.$subrow['user_pic'];			
					
				}
				$koord = explode(',',$row['koord']);
				$api->addGeoPoint((float)$koord['0'], (float)$koord['1'], $html, (isset($first) ? false : true));				
				$first = false;						
			} else {
				$koord = explode(',',$row['koord']);
				$api->addGeoPoint((float)$koord['0'], (float)$koord['1'], '<img src="images/flaggen/'.$row['country'].'.gif" /> <strong><a href="?section=user&id='.$row['ID'].'" target="_blank">'.$row['username'].'</a></strong><br />'.$row['wohnort'].'<br /><img src="'.($row['user_pic'] != '' ? 'images/user/'.$row['ID'].'_'.$row['user_pic'] : 'templates/'.DESIGN.'/images/nopic.png').'" alt="" title="'.strsave($row['username']).'" style="max-width: 150px" />', (isset($first) ? false : true));
				if ($row['user_pic'] !='') 
					$bilder[] = 'images/user/'.$row['ID'].'_'.$row['user_pic'];
				$first = false;
			}
		}
		ob_start();
		echo $api->getHeadCode().'<script type="text/javascript">		
			window.addEvents({
				"domready" : function() { 
					var info = new Element(\'div\', {
					    \'id\': \'map_info\',
					    \'html\': \'<div class="tip-top"><div class="tip"><div id="map_tip" class="tip-text"></div></div><div class="tip-bottom"></div></div>\',
					    \'styles\': {
					        \'display\': \'none\',
					        \'position\': \'absolute\',
					        \'z-index\': 9,
					        \'top:\': \'0px\',
					        \'left:\': \'0px\'
					    }
					});	
					info.inject(document.body, \'top\');
					$("map").addEvent("mousemove", function(e) {
						$("map_info").style.top = (e.page.y + 20) + "px"; 
						$("map_info").style.left = (e.page.x + 10) + "px"; 
					});
				},
				"load" : function() { 
					'.$api->getOnLoadCode().' 
					new Asset.images([\''.implode('\',\'',@$bilder).'\']);
				} 
			});		
		</script>';
		echo $api->getBodyCode();
		$content = ob_get_contents();
		ob_end_clean();
		main_content(MEMBER_MAP, $content, '', 1);		
	} else {
		echo table(ACCESS_DENIED, NO_ACCESS_RIGHTS);
	}
?>