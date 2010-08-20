<?php
function server($id=0) {
	global $db, $player_field_list;
	update_server_cache();
	if($id)
		$result = $db->query('SELECT * FROM '.DB_PRE.'ecp_server WHERE aktiv = 1 AND serverID = '.$id);
	else 
		$result = $db->query('SELECT * FROM '.DB_PRE.'ecp_server WHERE aktiv = 1 ORDER BY posi ASC');
	if($db->num_rows()) {
		$server = array();
		while($row = mysql_fetch_assoc($result)) {
			$spe = array();
			$data = lgsl_server_html(unserialize($row['response']));
			$tpl = new smarty;
			if($row['stat']) $tpl->assign('serverstats', true);
			if($data['b']['status'] == 0) {
				$tpl->assign('image', 'templates/'.DESIGN.'/images/map_no_response.jpg');
			} else if(file_exists('images/server/maps/'.$row['gametype'].'/'.$data['s']['game'].'/'.strtolower(str_replace(' ', '_',$data['s']['map'])).'.jpg')) {
				$tpl->assign('image', 'images/server/maps/'.$row['gametype'].'/'.$data['s']['game'].'/'.strtolower(str_replace(' ', '_',$data['s']['map'])).'.jpg');
			} else {
				$tpl->assign('image', 'templates/'.DESIGN.'/images/map_no_image.jpg');
			}
			$tpl->assign('nextupdate', ($row['datum']+SERVER_CACHE_REFRESH-time()).' '.SECONDS);			
			$row['datum'] = date('d.m.Y H:i:s', $row['datum']);
			$speicher = '<select size="1" name="settings">';			
			foreach($row AS $key=>$value) {
				$tpl->assign($key, $value);
			}
			foreach($data['e'] as $key=>$value) {
				$tpl->assign($key, $value);
				$speicher .= "<option>$key => ".check_str_length($value, 40)."</option>";
			}
			foreach($data['s'] as $key=>$value) {
				$tpl->assign($key, $value);
			}						
			$tpl->assign('settings', $speicher.'</select>');			
			if($row['gametype'] == 'halflife' OR $row['gametype'] == 'source') {
				IF(isset($data['e']['cm_nextmap'])) {
					$tpl->assign('nextmap', '('.SERVER_NEXT_MAP.': '.$data['e']['cm_nextmap'].')');
				} elseif (isset($data['e']['amx_nextmap'])) {
				    $tpl->assign('nextmap', '('.SERVER_NEXT_MAP.': '.$data['e']['amx_nextmap'].')');
				} elseif (isset($data['e']['mani_nextmap'])) {
					$tpl->assign('nextmap', '('.SERVER_NEXT_MAP.': '.$data['e']['mani_nextmap'].')');
				}
				IF(isset($data['e']['cm_timeleft'])) {
				    $tpl->assign('timeleft', '('.SERVER_TIME_LEFT.': '.$data['e']['cm_timeleft'].')');
				} elseif (isset($data['e']['amx_timeleft'])) {
				    $tpl->assign('timeleft', '('.SERVER_TIME_LEFT.': '.$data['e']['amx_timeleft'].')');
				}	
				$tpl->assign('plys', order_players($data['p']));					
				$tpl->assign('lang', $player_field_list);
				ob_start();
				//echo 'images/server/maps/'.$row['gametype'].'/'.$data['s']['game'].'/'.strtolower(str_replace(' ', '_',$data['s']['map'])).'.jpg';
				$tpl->display(DESIGN.'/tpl/server/halflife.html');
				$content = ob_get_contents().'</div>';
				ob_end_clean();
			} else {		
				  ob_start();
				  //echo 'images/server/maps/'.$row['gametype'].'/'.$data['s']['game'].'/'.strtolower(str_replace(' ', '_',$data['s']['map'])).'.jpg';
				  $tpl->display(DESIGN.'/tpl/server/other.html');
				  $output = ob_get_contents();
				  ob_end_clean();
				  error_reporting(1);
				  $data['p'] = order_players($data['p']);			
				  if (!$data['p'])
				  {
				    @$output .= "
				    <table cellpadding='4' cellspacing='2' style='margin:auto'>
				      <tr>
				        <td> ".SERVER_NO_PLAYERS." </td>
				      </tr>
				    </table>
				
				    <div style='height:20px'><br /></div>";
				  }
				  else
				  {
				    $used_field_list = array();
				
				    foreach ($player_field_list as $field => $title)
				    {
				      foreach ($data['p'] as $player)
				      {
				        if (isset($player[$field]))
				        {
				          $used_field_list[$field] = $title;
				        }
				      }
				    }
				
				    @$output .= "
				    <table cellpadding='1' cellspacing='1' style='width:100%;margin:auto'>
				      <tr style=''>";
				
				      foreach ($used_field_list as $field => $title)
				      {
				        $output .= "
				        <td> <b>{$title}</b> </td>";
				      }
				
				      $output .= "
				      </tr>";
					  $i = 0;
				      foreach ($data['p'] as $player_key => $player)
				      {
				        $output .= "
				        <tr class='".((++$i)%2 ? 'row_odd' : 'row_even')."'>";
				
				        foreach ($used_field_list as $field => $title)
				        {
				          $output .= "<td> {$player[$field]} </td>";
				        }
				
				        $output .= "
				        </tr>";
				      }
				
				    $output .= "
				    </table>
				
				    <div style='height:20px'><br /></div>";
				  }
				  error_reporting(E_ALL);
				$content = $output.'</div>';
				
			}
			$spe['headline'] = ($data['b']['status'] == 0) ? $row['ip'].':'.$row['port'].' '.SERVER_OFFLINE : $data['s']['name'];
			$spe['content'] = $content;
			$server[] = $spe;
		}	
		if(@$_GET['ajax']) {
			ob_end_clean();
			echo html_ajax_convert($content);
			die();
		} else {
			$tpl = new smarty;
			$tpl->assign('server', $server);
			ob_start();
			$tpl->display(DESIGN.'/tpl/server/overview.html');
			$content = ob_get_contents();
			ob_end_clean();
			main_content(SERVER, $content, '',1);
		}
	} else {
		table(INFO, NO_ENTRIES);
	}
}
function order_players($array) {
  $players = array();
  foreach($array AS $value) {  	  
 	  $players[str_pad($value['score'], 5, "00", STR_PAD_LEFT).'_'.$value['name']] = $value;  	
  }
  krsort($players);
  return $players;
}
if(isset($_GET['action'])) {
	switch($_GET['action']) {
		default:
		if(@$_SESSION['rights']['public']['server']['view'] OR @$_SESSION['rights']['superadmin'])
			server((int)@$_GET['id']);
		else
			echo table(ACCESS_DENIED, NO_ACCESS_RIGHTS);
	}
} else {
	if(@$_SESSION['rights']['public']['server']['view'] OR @$_SESSION['rights']['superadmin'])
		server((int)@$_GET['id']);
	else
		echo table(ACCESS_DENIED, NO_ACCESS_RIGHTS);
}
?>