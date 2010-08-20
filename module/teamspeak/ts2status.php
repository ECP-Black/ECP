<?php
/* 
 Copyright (C) 2004  Niklas Håkansson <niklas.hk@telia.com>
 
 This script is free software; you can redistribute it and/or
 modify it under the terms of the GNU Lesser General Public.
 
 If you want to change the HTML layout then only alter in functions
 getTSInfo() and setTSUsers(). No other functions need to altered.
*/

include "common.php";

/*****************************************************
* Telnet Connection								      
*****************************************************/
function TSConn($ip,$port,$tPort)
{
	$result = "";
	$err = array();
	if(strlen($ip)>4 and strlen($tPort)>2 and strlen($port)>2) {
		$fp = fsockopen($ip, $tPort, $errno, $errstr, 1);	
		stream_set_timeout($fp, 0, 1000000);		
		if($fp) {
			fputs($fp, "sel ".$port."\n");
			fputs($fp, "si\n");
			fputs($fp, "quit\n");
			
			while(!feof($fp)) {
				$out .= fgets($fp, 1024);						
			}
			
			$out   	= str_replace("[TS]", "", $out);			
			$out   	= str_replace("\n", "", $out);		
			$data  	= explode("\t", $out);
			$err 	= explode(",", $data[0]);			
		} 				
	}	
	if(strlen($err[0])>6) $result = "true";			
	return $result;	
}

/*****************************************************
* Basic Information - HTML						      
*****************************************************/
function getTSInfo($ip,$port,$tPort)
{
	$out = "";
	$fp = fsockopen($ip, $tPort, $errno, $errstr, 30);
	if($fp) {
		fputs($fp, "sel ".$port."\n");
		fputs($fp, "si\n");
		fputs($fp, "quit\n");
		while(!feof($fp)) {
			$out .= fgets($fp, 1024);
		}		
		
		$out   	= str_replace("[TS]", "", $out);
		$out   	= str_replace("OK", "", $out);
		$out 	= trim($out);
		$data['send'] = goodsize((float)substr($out,indexOf($out,"server_bytessend=")));
		$data['recv'] = goodsize((float)substr($out,indexOf($out,"server_bytesreceived=")));
		$name=substr($out,indexOf($out,"server_name="),strlen($out));
		$name=substr($name,0,indexOf($name,"server_platform=")-strlen("server_platform="));
		$data['name'] = $name;
		$os=substr($out,indexOf($out,"server_platform="),strlen($out));
		$os=substr($os,0,indexOf($os,"server_welcomemessage=")-strlen("server_welcomemessage="));
		$data['os'] = $os;
		$uptime=substr($out,indexOf($out,"server_uptime="),strlen($out));
		$uptime=substr($uptime,0,indexOf($uptime,"server_currrentusers=")-strlen("server_currrentusers="));
		$data['uptime'] = goodtime((int)$uptime, 1);
		$cAmount=substr($out,indexOf($out,"server_currentchannels="),strlen($out));
		$cAmount=substr($cAmount,0,indexOf($cAmount,"server_bwinlastsec=")-strlen("server_bwinlastsec="));		
		$data['cAmount'] = $cAmount;
		$user=substr($out,indexOf($out,"server_currentusers="),strlen($out));
		$user=substr($user,0,indexOf($user,"server_currentchannels=")-strlen("server_currentchannels="));		
		$data['user'] = $user;
		$max=substr($out,indexOf($out,"server_maxusers="),strlen($out));
		$max=substr($max,0,indexOf($max,"server_allow_codec_celp51=")-strlen("server_allow_codec_celp51="));
		$data['max'] = $max;	
		/* Alter HTML above */				
		
		fclose($fp);
		return @$data;
	} 
}

/*****************************************************
* User information								      
*****************************************************/
function getTSUsers($ip,$port,$tPort)
{
	$uArray 	= array();
	$innerArray = array();
	$out		= "";
	$j			= 0; 
	$k			= 0;
	
	$fp = fsockopen($ip, $tPort, $errno, $errstr, 30);
	if($fp) {
		fputs($fp, "pl ".$port."\n");		
		fputs($fp, "quit\n");
		while(!feof($fp)) {
			$out .= fgets($fp, 1024);
		}		
		$out   = str_replace("[TS]", "", $out);
		$out   = str_replace("loginname", "loginname\t", $out);
		$data 	= explode("\t", $out);					
		
		for($i=0;$i<count($data);$i++) {
			$innerArray[$j] = $data[$i];
			if($j>=15)
			{
				$uArray[$k]=$innerArray;
				$j = 0;
				$k = $k+1;
			} else {
				$j++;
			}			
		}			
		fclose($fp);	
	} 	
	return setTSUsers($uArray,$ip,$port,$tPort);		
}

/*****************************************************
* User information								      
*****************************************************/
function getTSChannelUsers($ip,$port,$tPort)
{
	$uArray 	= array();
	$innerArray = array();
	$out		= "";
	$j			= 0; 
	$k			= 0;
	
	$fp = fsockopen($ip, $tPort, $errno, $errstr, 30);
	if($fp) {
		fputs($fp, "pl ".$port."\n");		
		fputs($fp, "quit\n");
		while(!feof($fp)) {
			$out .= fgets($fp, 1024);
		}
		$out   = str_replace("[TS]", "", $out);
		$out   = str_replace("loginname", "loginname\t", $out);		
		$data 	= explode("\t", $out);
		$num 	= count($data);				
		
		for($i=0;$i<count($data);$i++) {
			$innerArray[$j] = $data[$i];			
			if($j>=15)
			{
				$uArray[$k]=$innerArray;
				$j = 0;
				$k = $k+1;
			} else {
				$j++;
			}			
		}			
		fclose($fp);	
	} 	
	 return $uArray;		
}

/*****************************************************
* User information - HTML						      
*****************************************************/
function setTSUsers($uArray,$ip,$port,$tPort)
{
	$array = array();
	for($i=1;$i<count($uArray);$i++) {
		$innerArray=$uArray[$i];
		$subarray = array();
		$subarray['icon'] = setUserStatus($innerArray[12]);
		$subarray['name'] = "<strong>".removeChar($innerArray[14])."</strong>&nbsp;(".setPPriv($innerArray[11])."".setCPriv($innerArray[10]).")";
		$subarray['channel'] = getChannelName($innerArray[1],$ip,$port,$tPort);
		$subarray['ping'] = format_nr($innerArray[7], 0);
		$subarray['loggedin'] = goodtime((int)$innerArray[8], ((int)$innerArray[8] > 86400 ? 1 : 3));
		$subarray['idle'] = goodtime((int)$innerArray[9]);
		$array[] = $subarray;
	}	
	return $array;
}

/*****************************************************
* Get all channels								      
*****************************************************/
function getChannels($ip,$port,$tPort)
{
	$cArray 	= array();
	$out		= "";
	$j			= 0; 
	$k			= 0;
	$fp = fsockopen($ip, $tPort, $errno, $errstr, 30);
	if($fp) {
		fputs($fp, "cl ".$port."\n");		
		fputs($fp, "quit\n");
		while(!feof($fp)) {
			$out .= fgets($fp, 1024);
		}
		$out   = str_replace("[TS]", "", $out);
		$out   = str_replace("\n", "\t", $out);			
		$data 	= explode("\t", $out);
		$num 	= count($data);				
		
		for($i=0;$i<count($data);$i++) {
			if($i>=10) {
				$innerArray[$j] = $data[$i];
				if($j>=8)
				{
					$cArray[$k]=$innerArray;
					$j = 0;
					$k = $k+1;
				} else {
					$j++;
				}
			}			
		}			
		fclose($fp);	
	} 	

	return $cArray;
}

/*****************************************************
* Set used ID:s								    	  
*****************************************************/
function usedID($usedArray,$id)
{		
	$ok = true;
	for($i=0;$i<count($usedArray);$i++)
	{	
		if($usedArray[$i]==$id) {
			$ok = false;			
		}		
	}
	return $ok;
}

/*****************************************************
* Get channel name								      
*****************************************************/
function getChannelName($id,$ip,$port,$tPort)
{		
	$name = "Uknown";
	$cArray = getChannels($ip,$port,$tPort);	
	
	for($i=0;$i<count($cArray);$i++)
	{
		$innerArray=$cArray[$i];		
		if($innerArray[0]==$id)
			$name = removeChar($innerArray[5]);	
	}		
	return $name;
}

/*****************************************************
* Channel sorting by name		 				      
*****************************************************/
function newSort($cArray) 
{	
	$tmpArray = array();
	$newArray = array();
	for($i=0;$i<count($cArray);$i++)
	{	
		$innerArray = $cArray[$i];
		$tmpArray[count($tmpArray)] = $innerArray[5];		
	}
	sort($tmpArray);
	
	for($i=0;$i<count($tmpArray);$i++)
	{		
		for($j=0;$j<count($cArray);$j++)
		{			
			$innerArray = $cArray[$j];			
			if($tmpArray[$i] == $innerArray[5])
			{				
				$thisArray[0] = $innerArray[0];	
				$thisArray[1] = $innerArray[5];
				$thisArray[2] = $innerArray[2];
				$thisArray[3] = $innerArray[3];
				$newArray[count($newArray)] = $thisArray;
			}			
		
		}
	}
	return $newArray; 
}


/*****************************************************
* Channel and user info			 				      
*****************************************************/
function getTSChannelInfo($ip,$port,$tPort)
{		
	$uArray 	= getTSChannelUsers($ip,$port,$tPort);		
	$pcArray 	= array();
	$ccArray	= array();
	$thisArray	= array();
	$listArray	= array();
	$usedArray	= array();	
	$cArray		= getChannels($ip,$port,$tPort);
	$z			= 0;
	$x			= 0;
	for($i=0;$i<count($cArray);$i++)
	{		
		$innerArray=$cArray[$i];		
		$listArray[$i]=$innerArray[3];						
	}	
	sort($listArray);
	$cArray = newSort($cArray);	
		
	for($i=0;$i<count($listArray);$i++)
	{			
		for($j=0;$j<count($cArray);$j++)
		{
			$innArray=$cArray[$j];			
						
			if($innArray[3]==$listArray[$i] and usedID($usedArray,$innArray[0]))
			{	
				if($innArray[2]==-1)
				{					
					$thisArray[0] = $innArray[0];	
					$thisArray[1] = $innArray[1];
					$thisArray[2] = $innArray[2];
					$pcArray[$z] = $thisArray;
					$usedArray[count($usedArray)] = $innArray[0];	 
					$z++;
				} 
				else
				{
					$thisArray[0] = $innArray[0];	
					$thisArray[1] = $innArray[1];
					$thisArray[2] = $innArray[2];
					$ccArray[$x] = $thisArray;
					$usedArray[count($usedArray)] = $innArray[0];	 
					$x++;				
				} 			
			}			
		}	
	}	
	$channels = array();
	for($i=0;$i<count($pcArray);$i++) {
		$innerArray=$pcArray[$i];	
		$schannel = array();
		for($j=0;$j<count($ccArray);$j++) {
			$innerCCArray=$ccArray[$j];
			if($innerArray[0]==$innerCCArray[2]) {
				$players = array();	
				for($p=1;$p<count($uArray);$p++) {
					$innerUArray=$uArray[$p];		
					if($innerCCArray[0]==$innerUArray[1]) {
						$players[] = array('icon' => setUserStatus($innerUArray[12]), 'name' => "<a href=\"#\" class=\"wrapCell\" onclick=\"return load_teamspeak_info(this,".$innerUArray[0].",2)\">&nbsp;".removeChar($innerUArray[14])."&nbsp;(".setPPriv($innerUArray[11])."".setCPriv($innerUArray[10]).")&nbsp;</a>");
					}		
				}		
				$schannel[] = array('players' => $players, 'name' =>"<a href=\"#\" class=\"wrapCell\" onclick=\"return load_teamspeak_info(this,".$innerCCArray[0].",1)\">&nbsp;".removeChar($innerCCArray[1])."&nbsp;</a>");
			}	
		}
		$players = array();
		for($k=1;$k<count($uArray);$k++) {
			$innerUArray=$uArray[$k];		
			if($innerArray[0]==$innerUArray[1]) {		
				$players[] = array('icon' => setUserStatus($innerUArray[12]), 'name' => "<a href=\"#\" class=\"wrapCell\" onclick=\"return load_teamspeak_info(this,".$innerUArray[0].",2)\">&nbsp;".removeChar($innerUArray[14])."&nbsp;(".setPPriv($innerUArray[11])."".setCPriv($innerUArray[10]).")&nbsp;</a>");	
			}			
		}
		$channels[] = array('subs' => $schannel,'players' => $players, 'name' => "<a href=\"#\" class=\"wrapCell\" onclick=\"return load_teamspeak_info(this,".$innerArray[0].",1)\">&nbsp;".removeChar($innerArray[1])."&nbsp;".getFlags($innerArray[0],$ip,$port,$tPort)."</a>");	
	}
	return $channels;
}

/*****************************************************
* Channel flags					 				      
*****************************************************/
function getFlags($cid,$ip,$port,$tPort)
{
	$out 	= "";
	$flag   = "U";
	$cArray 	= array();
	$j = 0;
	$k = 0;
	
	$fp = fsockopen($ip, $tPort, $errno, $errstr, 30);
	if($fp) {
		fputs($fp, "cl ".$port."\n");
		fputs($fp, "si\n");
		fputs($fp, "quit\n");
		while(!feof($fp)) {
			$out .= fgets($fp, 1024);
		}
		fclose($fp);
		$out   = str_replace("[TS]", "", $out);
		$out   = str_replace("\n", "\t", $out);		
		
		$data 	= explode("\t", $out);
		$num 	= count($data);				
		
		for($i=0;$i<count($data);$i++) {
			if($i>=10) {
				$innerArray[$j] = $data[$i];
				if($j>=8)
				{
					$cArray[$k]=$innerArray;
					$j = 0;
					$k = $k+1;
				} else {
					$j++;
				}
			}			
		}
		
		for($i=0;$i<count($cArray);$i++) {
			$innArray = $cArray[$i];			
			if($cid==$innArray[0]) 
			{
				$cid = setChannelFlags($innArray[6]);
			}		
		}			
	}
	
	return $cid;
}


/*****************************************************
* Channel default info			 				      
*****************************************************/
function defaultInfo($ip,$tPort,$port)
{
	$out = "";
	$html = "";	
	
	$fp = fsockopen($ip, $tPort, $errno, $errstr, 30);
	if($fp) {
		fputs($fp, "sel ".$port."\n");
		fputs($fp, "si\n");
		fputs($fp, "quit\n");
		while(!feof($fp)) {
			$out .= fgets($fp, 1024);
		}
		
		$out   	= str_replace("[TS]", "", $out);
		$out   	= str_replace("OK", "", $out);
		$out 	= trim($out);
		
		$name=substr($out,indexOf($out,"server_name="),strlen($out));
		$name=substr($name,0,indexOf($name,"server_platform=")-strlen("server_platform="));
		
		$os=substr($out,indexOf($out,"server_platform="),strlen($out));
		$os=substr($os,0,indexOf($os,"server_welcomemessage=")-strlen("server_welcomemessage="));
		
		$tsType=substr($out,indexOf($out,"server_clan_server="),strlen($out));
		$tsType=substr($tsType,0,indexOf($tsType,"server_udpport=")-strlen("server_udpport="));			
		
		$welcomeMsg=substr($out,indexOf($out,"server_welcomemessage="),strlen($out));
		$welcomeMsg=substr($welcomeMsg,0,indexOf($welcomeMsg,"server_webpost_linkurl=")-strlen("server_webpost_linkurl="));
				
		
		if($tsType[0]==1) $tsTypeText = "Freeware Clan Server";
		else $tsTypeText = "Freeware Public Server";		

		$html = "<tr><td class=\"boldbread\">Server:</td></tr>\n";
		$html .= "<tr><td class=\"bread\">".$name."<br><br></td></tr>\n";
		$html .= "<tr><td class=\"boldbread\">Server IP:</td></tr>\n";
		$html .= "<tr><td class=\"bread\">".$ip.":".$port."<br><br></td></tr>\n";
		$html .= "<tr><td class=\"boldbread\">Version:</td></tr>\n";
		$html .= "<tr><td class=\"bread\">".@getTSVersion($ip,$tPort,$port)."<br><br></td></tr>\n";
		$html .= "<tr><td class=\"boldbread\">Type:</td></tr>\n";
		$html .= "<tr><td class=\"bread\">".$tsTypeText."<br><br></td></tr>\n";
		$html .= "<tr><td class=\"boldbread\">Welcome Message:</td></tr>\n";
		$html .= "<tr><td class=\"bread\">".$welcomeMsg."<br><br></td></tr>";
		
		fclose($fp);
	}
	return $html;
}

/*****************************************************
* Channel info					 				      
*****************************************************/
function channelInfo($ip,$tPort,$port,$cID,$joinButton)
{
	$cArray		= getChannels($ip,$port,$tPort);
	$uArray 	= getTSChannelUsers($ip,$port,$tPort);
	$html 		= "";
	$cUser		= 0;
	$ok 		= false;	
	
	for($i=0;$i<count($cArray);$i++)
	{
		$innArray = $cArray[$i];
		if($innArray[0]==$cID)
		{
			$codec  = $innArray[1];
			$max	= $innArray[4];
			$name 	= $innArray[5];				
			$topic 	= $innArray[8];
			$ok = true; 
		}
	}
	
	for($i=0;$i<count($uArray);$i++)
	{
		$innArray = $uArray[$i];
		if($innArray[1]==$cID) $cUser++;		
	}	
	if($ok) 
	{
		$html = "<tr><td class=\"boldbread\">Channel:</td></tr>\n";
		$html .= "<tr><td class=\"bread\">".removeChar($name)."<br><br></td></tr>\n";
		$html .= "<tr><td class=\"boldbread\">Topic:</td></tr>\n";
		$html .= "<tr><td class=\"bread\">".removeChar($topic)."<br><br></td></tr>\n";
		$html .= "<tr><td class=\"boldbread\">User in channel:</td></tr>\n";
		$html .= "<tr><td class=\"bread\">".$cUser."/".removeChar($max)."<br><br></td></tr>\n";
		$html .= "<tr><td class=\"boldbread\">Codec:</td></tr>\n";
		$html .= "<tr><td class=\"bread\">".getCodec($codec)."<br><br></td></tr>\n";
		$name = str_replace("'","¶",$name);
		if($joinButton)	$html .= "<tr><td><br><input type=\"button\" class=\"button\" onclick=\"ts_login('".removeChar($name)."', '$ip', '$port');\" value=\"Join Channel\"></td></tr>\n";
	} else {
		$html = "<tr><td class=\"boldbread\">Channel is deleted!</td></tr>\n";
	}
	
	return $html;	
}

/*****************************************************
* User info					 				      	  
*****************************************************/
function userInfo($ip,$tPort,$port,$cID)
{	
	$uArray 	= getTSChannelUsers($ip,$port,$tPort);
	$html 		= "";
	$cUser		= 0;
	$ok 		= false;	
	
	for($i=0;$i<count($uArray);$i++)
	{
		$innArray = $uArray[$i];
		if($innArray[0]==$cID) 
		{			
			$cpriv	= $innArray[10];
			$ppriv	= $innArray[11]; 
			$status = $innArray[12];
			$name 	= $innArray[14];
			$ok = true; 
		}		
	}	
	
	if($ok) 
	{
		$html = "<tr><td class=\"boldbread\">Player:</td></tr>\n";
		$html .= "<tr><td class=\"bread\">".removeChar($name)."<br><br></td></tr>\n";
		$html .= "<tr><td class=\"boldbread\">Global flags:</td></tr>\n";
		$html .= "<tr><td class=\"bread\">".setPPrivText($ppriv)."<br><br></td></tr>\n";
		$html .= "<tr><td class=\"boldbread\">Channel Privileges:</td></tr>\n";
		$html .= "<tr><td class=\"bread\">".setCPrivText($cpriv)."<br><br></td></tr>\n";
		$html .= "<tr><td class=\"boldbread\">Player Attributes:</td></tr>\n";
		$html .= "<tr><td class=\"bread\">".setUserStatusText($status)."<br><br></td></tr>\n";
	} else {
		$html = "<tr><td class=\"boldbread\">User is offline!</td></tr>\n";
	}
	return $html;	
}

/*****************************************************
* Get TS version				 				      
*****************************************************/
function getTSVersion($ip,$tPort,$port)
{
	$out = "";
	$fp = fsockopen($ip, $tPort, $errno, $errstr, 30);
	if($fp) {
		fputs($fp, "sel ".$port."\n");
		fputs($fp, "ver\n");
		fputs($fp, "quit\n");
		while(!feof($fp)) {
			$out .= fgets($fp, 1024);
		}
		$out   	= str_replace("[TS]", "", $out);
		$out   	= str_replace("OK", "", $out);
		$out   	= str_replace("\n", "", $out);		
		$data  	= explode(" ", $out);
		
		fclose($fp);				
	}
	return $data[0];
}

?>