<?PHP
/* 
 Copyright (C) 2004  Niklas Håkansson <niklas.hk@telia.com>
 
 This script is free software; you can redistribute it and/or
 modify it under the terms of the GNU Lesser General Public. 
*/
/*****************************************************
* Set Channel flags								      
*****************************************************/
function setChannelFlags($num)
{
	switch ($num) {
		case "0" :
		return " (R)";
		
		case "1" :
		return " (U)";
		
		case "2" :
		return " (RM)";
		
		case "3" :
		return " (UM)";
		
		case "4" :
		return " (RP)";
		
		case "5" :
		return " (UP)";
		
		case "6" :
		return " (RMP)";
		
		case "7" :
		return " (UMP)";
		
		case "8" :
		return " (RS)";
		
		case "9" :
		return " (US)";
		
		case "10" :
		return " (RMS)";
		
		case "11" :
		return " (UMS)";
		
		case "12" :
		return " (RPS)";
		
		case "13" :
		return " (UPS)";
		
		case "14" :
		return " (RMPS)";
		
		case "15" :
		return " (UMPS)";
		
		case "16" :
		return " (RD)";
		
		case "18" :
		return " (RMD)";
		
		case "20" :
		return " (RPD)";
		
		case "22" :
		return " (RMPD)";
		
		case "24" :
		return " (RSD)";
		
		case "26" :
		return " (RMSD)";
		
		case "28" :
		return " (RPSD)";
		
		case "30" :
		return " (RMPSD)";
	}
}

/*****************************************************
* Set User Status								      
*****************************************************/
function setUserStatus($img)
{
	switch ($img) {
		case "1" : //Channel Commander
		$img = "<img src=\"templates/".DESIGN."/images/teamspeak/tsicons/ccommander.gif\" width=\"16\" height=\"16\" border=\"0\">"; 
   		break;
		
		case "3" : 
		$img = "<img src=\"templates/".DESIGN."/images/teamspeak/tsicons/ccommander.gif\" width=\"16\" height=\"16\" border=\"0\">"; 
		break;	
		
		case "5" : 
		$img = "<img src=\"templates/".DESIGN."/images/teamspeak/tsicons/ccommander.gif\" width=\"16\" height=\"16\" border=\"0\">"; 
		break;
		
		case "7" : 
		$img = "<img src=\"templates/".DESIGN."/images/teamspeak/tsicons/ccommander.gif\" width=\"16\" height=\"16\" border=\"0\">"; 
		break;		
		
		case "8" : //Away
		$img = "<img src=\"templates/".DESIGN."/images/teamspeak/tsicons/away.gif\" width=\"16\" height=\"16\" border=\"0\">";
		break;
		
		case "9" : 
		$img = "<img src=\"templates/".DESIGN."/images/teamspeak/tsicons/away.gif\" width=\"16\" height=\"16\" border=\"0\">";
		break;
		
		case "10" : 
		$img = "<img src=\"templates/".DESIGN."/images/teamspeak/tsicons/away.gif\" width=\"16\" height=\"16\" border=\"0\">";
		break;
		
		case "11" : 
		$img = "<img src=\"templates/".DESIGN."/images/teamspeak/tsicons/away.gif\" width=\"16\" height=\"16\" border=\"0\">";
		break;
		
		case "12" : 
		$img = "<img src=\"templates/".DESIGN."/images/teamspeak/tsicons/away.gif\" width=\"16\" height=\"16\" border=\"0\">";
		break;
		
		case "13" : 
		$img = "<img src=\"templates/".DESIGN."/images/teamspeak/tsicons/away.gif\" width=\"16\" height=\"16\" border=\"0\">";
		break;
		
		case "14" : 
		$img = "<img src=\"templates/".DESIGN."/images/teamspeak/tsicons/away.gif\" width=\"16\" height=\"16\" border=\"0\">";
		break;
		
		case "15" : 
		$img = "<img src=\"templates/".DESIGN."/images/teamspeak/tsicons/away.gif\" width=\"16\" height=\"16\" border=\"0\">";
		break;			
		
		case "16" : //Microphone Muted
		$img = "<img src=\"templates/".DESIGN."/images/teamspeak/tsicons/muted.gif\" width=\"16\" height=\"16\" border=\"0\">";
		break;
		
		case "17" : 
		$img = "<img src=\"templates/".DESIGN."/images/teamspeak/tsicons/muted.gif\" width=\"16\" height=\"16\" border=\"0\">";
		break;
		
		case "18" : 
		$img = "<img src=\"templates/".DESIGN."/images/teamspeak/tsicons/muted.gif\" width=\"16\" height=\"16\" border=\"0\">";
		break;
		
		case "19" : 
		$img = "<img src=\"templates/".DESIGN."/images/teamspeak/tsicons/muted.gif\" width=\"16\" height=\"16\" border=\"0\">";
		break;
		
		case "20" : 
		$img = "<img src=\"templates/".DESIGN."/images/teamspeak/tsicons/muted.gif\" width=\"16\" height=\"16\" border=\"0\">";
		break;
		
		case "21" : 
		$img = "<img src=\"templates/".DESIGN."/images/teamspeak/tsicons/muted.gif\" width=\"16\" height=\"16\" border=\"0\">";
		break;
		
		case "22" : 
		$img = "<img src=\"templates/".DESIGN."/images/teamspeak/tsicons/muted.gif\" width=\"16\" height=\"16\" border=\"0\">";
		break;
		
		case "23" : 
		$img = "<img src=\"templates/".DESIGN."/images/teamspeak/tsicons/muted.gif\" width=\"16\" height=\"16\" border=\"0\">";
		break;		
		
		case "24" : 
		$img = "<img src=\"templates/".DESIGN."/images/teamspeak/tsicons/away.gif\" width=\"16\" height=\"16\" border=\"0\">";
		break;
		
		case "25" : 
		$img = "<img src=\"templates/".DESIGN."/images/teamspeak/tsicons/away.gif\" width=\"16\" height=\"16\" border=\"0\">";
		break;
		
		case "26" : 
		$img = "<img src=\"templates/".DESIGN."/images/teamspeak/tsicons/away.gif\" width=\"16\" height=\"16\" border=\"0\">";
		break;
		
		case "27" : 
		$img = "<img src=\"templates/".DESIGN."/images/teamspeak/tsicons/away.gif\" width=\"16\" height=\"16\" border=\"0\">";
		break;
		
		case "28" : 
		$img = "<img src=\"templates/".DESIGN."/images/teamspeak/tsicons/away.gif\" width=\"16\" height=\"16\" border=\"0\">";
		break;
		
		case "29" : 
		$img = "<img src=\"templates/".DESIGN."/images/teamspeak/tsicons/away.gif\" width=\"16\" height=\"16\" border=\"0\">";
		break;
		
		case "30" : 
		$img = "<img src=\"templates/".DESIGN."/images/teamspeak/tsicons/away.gif\" width=\"16\" height=\"16\" border=\"0\">";
		break;
		
		case "31" : 
		$img = "<img src=\"templates/".DESIGN."/images/teamspeak/tsicons/away.gif\" width=\"16\" height=\"16\" border=\"0\">";
		break;		
		
		case "32" : //Sound Muted
		$img = "<img src=\"templates/".DESIGN."/images/teamspeak/tsicons/smuted.gif\" width=\"16\" height=\"16\" border=\"0\">";
		break;
		
		case "33" : 
		$img = "<img src=\"templates/".DESIGN."/images/teamspeak/tsicons/smuted.gif\" width=\"16\" height=\"16\" border=\"0\">";
		break;
		
		case "34" : 
		$img = "<img src=\"templates/".DESIGN."/images/teamspeak/tsicons/smuted.gif\" width=\"16\" height=\"16\" border=\"0\">";
		break;
		
		case "35" : 
		$img = "<img src=\"templates/".DESIGN."/images/teamspeak/tsicons/smuted.gif\" width=\"16\" height=\"16\" border=\"0\">";
		break;
		
		case "36" : 
		$img = "<img src=\"templates/".DESIGN."/images/teamspeak/tsicons/smuted.gif\" width=\"16\" height=\"16\" border=\"0\">";
		break;
		
		case "37" : 
		$img = "<img src=\"templates/".DESIGN."/images/teamspeak/tsicons/smuted.gif\" width=\"16\" height=\"16\" border=\"0\">";
		break;
		
		case "38" : 
		$img = "<img src=\"templates/".DESIGN."/images/teamspeak/tsicons/smuted.gif\" width=\"16\" height=\"16\" border=\"0\">";
		break;
		
		case "39" : 
		$img = "<img src=\"templates/".DESIGN."/images/teamspeak/tsicons/smuted.gif\" width=\"16\" height=\"16\" border=\"0\">";
		break;		
		
		case "40" : 
		$img = "<img src=\"templates/".DESIGN."/images/teamspeak/tsicons/away.gif\" width=\"16\" height=\"16\" border=\"0\">";
		break;
		
		case "41" : 
		$img = "<img src=\"templates/".DESIGN."/images/teamspeak/tsicons/away.gif\" width=\"16\" height=\"16\" border=\"0\">";
		break;
		
		case "42" : 
		$img = "<img src=\"templates/".DESIGN."/images/teamspeak/tsicons/away.gif\" width=\"16\" height=\"16\" border=\"0\">";
		break;
		
		case "43" : 
		$img = "<img src=\"templates/".DESIGN."/images/teamspeak/tsicons/away.gif\" width=\"16\" height=\"16\" border=\"0\">";
		break;
		
		case "44" : 
		$img = "<img src=\"templates/".DESIGN."/images/teamspeak/tsicons/away.gif\" width=\"16\" height=\"16\" border=\"0\">";
		break;
		
		case "45" : 
		$img = "<img src=\"templates/".DESIGN."/images/teamspeak/tsicons/away.gif\" width=\"16\" height=\"16\" border=\"0\">";
		break;
		
		case "46" : 
		$img = "<img src=\"templates/".DESIGN."/images/teamspeak/tsicons/away.gif\" width=\"16\" height=\"16\" border=\"0\">";
		break;
		
		case "47" : 
		$img = "<img src=\"templates/".DESIGN."/images/teamspeak/tsicons/away.gif\" width=\"16\" height=\"16\" border=\"0\">";
		break;		
		
		case "48" : 
		$img = "<img src=\"templates/".DESIGN."/images/teamspeak/tsicons/smuted.gif\" width=\"16\" height=\"16\" border=\"0\">";
		break;
		
		case "49" : 
		$img = "<img src=\"templates/".DESIGN."/images/teamspeak/tsicons/smuted.gif\" width=\"16\" height=\"16\" border=\"0\">";
		break;
		
		case "50" : 
		$img = "<img src=\"templates/".DESIGN."/images/teamspeak/tsicons/smuted.gif\" width=\"16\" height=\"16\" border=\"0\">";
		break;
		
		case "51" : 
		$img = "<img src=\"templates/".DESIGN."/images/teamspeak/tsicons/smuted.gif\" width=\"16\" height=\"16\" border=\"0\">";
		break;
		
		case "52" : 
		$img = "<img src=\"templates/".DESIGN."/images/teamspeak/tsicons/smuted.gif\" width=\"16\" height=\"16\" border=\"0\">";
		break;
		
		case "53" : 
		$img = "<img src=\"templates/".DESIGN."/images/teamspeak/tsicons/smuted.gif\" width=\"16\" height=\"16\" border=\"0\">";
		break;
		
		case "54" : 
		$img = "<img src=\"templates/".DESIGN."/images/teamspeak/tsicons/smuted.gif\" width=\"16\" height=\"16\" border=\"0\">";
		break;
		
		case "55" : 
		$img = "<img src=\"templates/".DESIGN."/images/teamspeak/tsicons/smuted.gif\" width=\"16\" height=\"16\" border=\"0\">";
		break;
		
		case "56" : 
		$img = "<img src=\"templates/".DESIGN."/images/teamspeak/tsicons/smuted.gif\" width=\"16\" height=\"16\" border=\"0\">";
		break;
		
		case "57" : 
		$img = "<img src=\"templates/".DESIGN."/images/teamspeak/tsicons/smuted.gif\" width=\"16\" height=\"16\" border=\"0\">";
		break;		
		
		case "58" : 
		$img = "<img src=\"templates/".DESIGN."/images/teamspeak/tsicons/away.gif\" width=\"16\" height=\"16\" border=\"0\">";
		break;
		
		case "59" : 
		$img = "<img src=\"templates/".DESIGN."/images/teamspeak/tsicons/away.gif\" width=\"16\" height=\"16\" border=\"0\">";
		break;		
		
		case "60" : 
		$img = "<img src=\"templates/".DESIGN."/images/teamspeak/tsicons/away.gif\" width=\"16\" height=\"16\" border=\"0\">";
		break;
		
		case "61" : 
		$img = "<img src=\"templates/".DESIGN."/images/teamspeak/tsicons/away.gif\" width=\"16\" height=\"16\" border=\"0\">";
		break;		
		
		default :
   		$img = "<img src=\"templates/".DESIGN."/images/teamspeak/tsicons/user.gif\" width=\"16\" height=\"16\" border=\"0\">";
		break;		
		
	}			 
	return $img;		
}

/*****************************************************
* Set Channel Privileges						      
*****************************************************/
function setCPriv($str)
{
	switch ($str) {		 	
		case "1" : //Channel Admin
		$str = "&nbsp;CA";  
	   	break;
		
		case "2" : //Channel Ops
		$str = "&nbsp;O";  
	   	break;
		
		case "3" : //Channel Admin & Ops 
		$str = "&nbsp;CA&nbsp;O";  
	   	break;
		
		case "4" : //Voice
		$str = "&nbsp;V";  
	   	break;
		
		case "5" : //Channel Admin & Voice
		$str = "&nbsp;CA&nbsp;V";  
	   	break;
		
		case "6" : //Ops & Voice
		$str = "&nbsp;O&nbsp;V";  
	   	break;
		
		case "7" : //Channel Admin & Ops & Voiced 
		$str = "&nbsp;CA&nbsp;O&nbsp;V";  
	   	break;
		
		case "8" : //Auto Ops 
		$str = "&nbsp;AO";  
	   	break;
		
		case "9" : //Channel Admin & Auto Ops 
		$str = "&nbsp;CA&nbsp;AO";  
	   	break;
		
		case "10" : //Channel Admin & Auto Ops 
		$str = "&nbsp;AO&nbsp;O";  
	   	break;
		
		case "11" : //Channel Admin & Auto Ops & Ops
		$str = "&nbsp;CA&nbsp;AO&nbsp;O";  
	   	break;
		
		case "12" : //Auto Ops & Voiced
		$str = "&nbsp;AO&nbsp;V";  
	   	break;
		
		case "13" : //Channel Admin & Auto Ops & Voiced
		$str = "&nbsp;CA&nbsp;AO&nbsp;V";  
	   	break;	
		
		case "14" : //Auto Ops & Ops & Voiced
		$str = "&nbsp;AO&nbsp;O&nbsp;V";  
	   	break;
		
		case "15" : //Channel Admin & Auto Ops & Ops & Voiced
		$str = "&nbsp;CA&nbsp;AO&nbsp;O&nbsp;V";  
	   	break;
		
		case "16" : //Auto Voice
		$str = "&nbsp;AV";  
	   	break;
		
		case "17" : //Channel Admin & Auto Voice
		$str = "&nbsp;CA&nbsp;AV";  
	   	break;
		
		case "18" : //Auto Voice & Ops
		$str = "&nbsp;AV&nbsp;O";  
	   	break;
		
		case "19" : //Channel Admin & Auto Voice & Ops
		$str = "&nbsp;CA&nbsp;AV&nbsp;O";  
	   	break;
		
		case "20" : //Auto Voice & Voice 
		$str = "&nbsp;AV&nbsp;V";  
	   	break;
		
		case "21" : //Channel Admin & Auto Voice & Voice 
		$str = "&nbsp;CA&nbsp;AV&nbsp;V";  
	   	break;
		
		case "22" : //Auto Voice & Ops & Voice 
		$str = "&nbsp;AV&nbsp;O&nbsp;V";  
	   	break;
		
		case "23" : //Channel Admin & Auto Voice & Ops & Voice 
		$str = "&nbsp;CA&nbsp;AV&nbsp;O&nbsp;V";  
	   	break;
		
		case "24" : //Auto Ops & Auto Voice
		$str = "&nbsp;AO&nbsp;AV";  
	   	break;
		
		case "25" : //Channel Admin & Auto Ops & Auto Voice 
		$str = "&nbsp;CA&nbsp;AO&nbsp;AV";  
	   	break;
		
		case "26" : //Auto Ops & Auto Voice & Ops 
		$str = "&nbsp;AO&nbsp;AV&nbsp;O";  
	   	break;
		
		case "27" : //Channel Admin & Auto Ops & Auto Voice & Ops 
		$str = "&nbsp;CA&nbsp;AO&nbsp;AV&nbsp;O";  
	   	break;
		
		case "28" : //Auto Ops & Auto Voice & Voice 
		$str = "&nbsp;AO&nbsp;AV&nbsp;V";  
	   	break;
		
		case "29" : //Channel Admin & Auto Ops & Auto Voice & Voice 
		$str = "&nbsp;CA&nbsp;AO&nbsp;AV&nbsp;V";  
	   	break;
		
		case "30" : //Auto Ops & Auto Voice & Ops & Voiced
		$str = "&nbsp;AO&nbsp;AV&nbsp;O&nbsp;V";  
	   	break;
		
		case "31" : //Channel Admin & Auto Ops & Auto Voice & Ops & Voiced
		$str = "&nbsp;CA&nbsp;AO&nbsp;AV&nbsp;O&nbsp;V";  
	   	break;
		
		default :
	   	$str = "";
	   	break;	
	}
	
	return $str;
}

/*****************************************************
* Capital letter								      
*****************************************************/
function strCapToUpper($str)
{
	$str = trim($str);
	$fstr = strtoupper(substr($str,0,1));
	$lstr = substr($str,1,strlen($str));	
	return $fstr.$lstr;
}

/*****************************************************
* Remove character								      
*****************************************************/
function removeChar($str)
{
	$str = str_replace('"', '', $str);
	return $str;
}

/*****************************************************
* Replace character								      
*****************************************************/
function replaceChar($str)
{
	$str = str_replace("'", "&#39;", $str);
	return $str;
}

/*****************************************************
* Time Convert Function 						      
*****************************************************/
function time_convert($time)
{ 
	$hours = floor($time/3600);
	$minutes = floor(($time%3600)/60);
	$seconds = floor(($time%3600)%60);
	
	if($hours>0) $time = $hours."h ".$minutes."m ".$seconds."s";
	else if($minutes>0) $time = $minutes."m ".$seconds."s";
	else $time = $seconds."s";
	 
  	return $time;
} 

/*****************************************************
* Get Codec			 				      			  
*****************************************************/
function getCodec($codec)
{
	switch ($codec) {		 	
		case "0" : 
		$codec = "CELP 5.2 Kbit";  
	   	break;
		
		case "1" : 
		$codec = "CELP 6.3 Kbit";  
	   	break;
		
		case "2" : 
		$codec = "GSM 14.8 Kbit";  
	   	break;
		
		case "3" : 
		$codec = "GSM 16.4 Kbit";  
	   	break;
		
		case "4" : 
		$codec = "Windows CELP 5.2 Kbit";  
	   	break;			
		
		case "5" : 
		$codec = "Speex 3.4 Kbit";  
	   	break;
		
		case "6" : 
		$codec = "Speex 5.2 Kbit";  
	   	break;
		
		case "7" : 
		$codec = "Speex 7.2 Kbit";  
	   	break;		
		
		case "8" : 
		$codec = "Speex 9.3 Kbit";  
	   	break;		
		
		case "9" : 
		$codec = "Speex 12.3 Kbit";  
	   	break;
		
		case "10" : 
		$codec = "Speex 16.3 Kbit";  
	   	break;
		
		case "11" : 
		$codec = "Speex 19.5 Kbit";  
	   	break;	
		
		case "12" : 
		$codec = "Speex 25.9 Kbit";  
	   	break;			
		
		default :
	    $codec = "";
	   	break;
	}	
		
	return $codec;
}

/*****************************************************
* Set Player Privileges							      
*****************************************************/
function setPPriv($str)
{
	switch ($str) {	
	   	case "5" : //Server Admin
		$str = "R&nbsp;SA";  
	   	break;
	
	   	case "4" : //Registered
	    $str = "R"; 
	   	break;
		
	   	default :
	   	$str = "U";
	   	break;   
  	}
   
	return $str;
}

/*****************************************************
* Set Player Privileges	Text						  
*****************************************************/
function setPPrivText($str)
{
	switch ($str) {	
	   	case "5" : //Server Admin
		$str = "Server Administrator<br>Registered";  
	   	break;
	
	   	case "4" : //Registered
	    $str = "Registered"; 
	   	break;
		
	   	default :
	   	$str = "None";
	   	break;   
  	}
   
	return $str;
}

/*****************************************************
* Set Channel Privileges Text					      
*****************************************************/
function setCPrivText($str)
{
	switch ($str) {		 	
		case "1" : //Channel Admin
		$str = "Channel Admin";  
	   	break;
		
		case "2" : //Channel Ops
		$str = "Channel Ops";  
	   	break;
		
		case "3" : //Channel Admin & Ops 
		$str = "Channel Admin<br>Ops";  
	   	break;
		
		case "4" : //Voice
		$str = "Voice";  
	   	break;
		
		case "5" : //Channel Admin & Voice
		$str = "Channel Admin<br>Voice";  
	   	break;
		
		case "6" : //Ops & Voice
		$str = "Ops<br>Voice";  
	   	break;
		
		case "7" : //Channel Admin & Ops & Voiced 
		$str = "Channel Admin<br>Ops<br>Voiced";  
	   	break;
		
		case "8" : //Auto Ops 
		$str = "Auto Ops";  
	   	break;
		
		case "9" : //Channel Admin & Auto Ops 
		$str = "Channel Admin<br>Auto Ops";  
	   	break;
		
		case "10" : //Auto Ops & Auto Ops 
		$str = "Auto Ops<br>Ops";  
	   	break;
		
		case "11" : //Channel Admin & Auto Ops & Operator
		$str = "Channel Admin<br>Auto Ops<br>Ops";  
	   	break;
		
		case "12" : //Auto Ops & Voiced
		$str = "Auto Ops<br>Voiced";  
	   	break;
		
		case "13" : //Channel Admin & Auto Ops & Voiced
		$str = "Channel Admin<br>Auto Ops<br>Voiced";  
	   	break;	
		
		case "14" : //Auto Ops & Ops & Voiced
		$str = "Auto Ops<br>Ops<br>Voiced";  
	   	break;
		
		case "15" : //Channel Admin & Auto Ops & Ops & Voiced
		$str = "Channel Admin<br>Auto Ops<br>Ops<br>Voiced";  
	   	break;
		
		case "16" : //Auto Voice
		$str = "Auto Voice";  
	   	break;
		
		case "17" : //Channel Admin & Auto Voice
		$str = "Channel Admin<br>Auto Voice";  
	   	break;
		
		case "18" : //Auto Voice & Ops
		$str = "Auto Voice<br>Ops";  
	   	break;
		
		case "19" : //Channel Admin & Auto Voice & Ops
		$str = "Channel Admin<br>Auto Voice<br>Ops";  
	   	break;
		
		case "20" : //Auto Voice & Voice 
		$str = "Auto Voice<br>Voice";  
	   	break;
		
		case "21" : //Channel Admin & Auto Voice & Voice 
		$str = "Channel Admin<br>Auto Voice<br>Voice";  
	   	break;
		
		case "22" : //Auto Voice & Ops & Voice 
		$str = "Auto Voice<br>Ops<br>Voice";  
	   	break;
		
		case "23" : //Channel Admin & Auto Voice & Ops & Voice 
		$str = "Channel Admin<br>Auto Voice<br>Ops<br>Voice";  
	   	break;
		
		case "24" : //Auto Ops & Auto Voice
		$str = "Auto Ops<br>Auto Voice";  
	   	break;
		
		case "25" : //Channel Admin & Auto Ops & Auto Voice 
		$str = "Channel Admin<br>Auto Ops<br>Auto Voice";  
	   	break;
		
		case "26" : //Auto Ops & Auto Voice & Ops 
		$str = "Auto Ops<br>Auto Voice<br>Ops";  
	   	break;
		
		case "27" : //Channel Admin & Auto Ops & Auto Voice & Ops 
		$str = "Channel Admin<br>Auto Ops<br>Auto Voice<br>Ops";  
	   	break;
		
		case "28" : //Auto Ops & Auto Voice & Voice 
		$str = "Auto Ops<br>Auto Voice<br>Voice";  
	   	break;
		
		case "29" : //Channel Admin & Auto Ops & Auto Voice & Voice 
		$str = "Channel Admin<br>Auto Ops<br>Auto Voice<br>Voice";  
	   	break;
		
		case "30" : //Auto Ops & Auto Voice & Ops & Voiced
		$str = "Auto Ops<br>Auto Voice<br>Ops<br>Voiced";  
	   	break;
		
		case "31" : //Channel Admin & Auto Ops & Auto Voice & Ops & Voiced
		$str = "Channel Admin<br>Auto Ops<br>Auto Voice<br>Ops<br>Voiced";  
	   	break;
		
		default :
	   	$str = "None";
	   	break;	
	}
	
	return $str;
}

/*****************************************************
* Set User Status Text								      
*****************************************************/
function setUserStatusText($str)
{
	switch ($str) {
		case "1" : 
		$str = "Channel Commander"; 
   		break;
		
		case "2" : 
		$str = "Requests Voice";
		break;
		
		case "3" : 
		$str = "Requests Voice<br>Channel Commander";
		break;
		
		case "4" : 
		$str = "Doesnt Accept Whispers";
		break;
		
		case "5" : 
		$str = "Does not accept whispers<br>Channel Commander";
		break;
		
		case "6" : 
		$str = "Requests Voice<br>Does not accept whispers";
		break;
		
		case "7" : 
		$str = "Requests Voice<br>Does not accept whispers<br>Channel Commander";
		break;
		
		case "8" : 
		$str = "Player is away";
		break;
		
		case "9" : 
		$str = "Player is away<br>Channel Commander";
		break;
		
		case "10" : 
		$str = "Requests Voice<br>Player is away";
		break;
		
		case "11" : 
		$str = "Requests Voice<br>Player is away<br>Channel Commander";
		break;
		
		case "12" : 
		$str = "Does not accept whispers<br>Player is away";
		break;
		
		case "13" : 
		$str = "Does not accept whispers<br>Player is away<br>Channel Commander";
		break;
		
		case "14" : 
		$str = "Requests Voice<br>Does not accept whispers<br>Player is away";
		break;
		
		case "15" : 
		$str = "Requests Voice<br>Does not accept whispers<br>Player is away<br>Channel Commander";
		break;	
		
		case "16" : 
		$str = "Microphone Muted";
		break;
		
		case "17" : 
		$str = "Microphone Muted<br>Channel Commander";
		break;
		
		case "18" : 
		$str = "Requests Voice<br>Microphone muted";
		break;
		
		case "19" : 
		$str = "Requests Voice<br>Microphone muted<br>Channel Commander";
		break;
		
		case "20" : 
		$str = "Does not accept whispers<br>Microphone muted";
		break;
		
		case "21" : 
		$str = "Does not accept whispers<br>Microphone Muted<br>Channel Commander";
		break;
		
		case "22" : 
		$str = "Requests Voice<br>Does not accept whispers<br>Microphone muted";
		break;
		
		case "23" : 
		$str = "Requests Voice<br>Does not accept whispers<br>Microphone muted<br>Channel Commander";
		break;
		
		case "24" : 
		$str = "Player is away<br>Microphone Muted";
		break;
		
		case "25" : 
		$str = "Player is away<br>Microphone Muted<br>Channel Commander";
		break;
		
		case "26" : 
		$str = "Requests Voice<br>Player is away<br>Microphone muted";
		break;
		
		case "27" : 
		$str = "Requests Voice<br>Player is away<br>Microphone muted<br>Channel Commander";
		break;
		
		case "28" : 
		$str = "Does not accept whispers<br>Player is away<br>Microphone Muted";
		break;
		
		case "29" : 
		$str = "Does not accept whispers<br>Player is away<br>Microphone Muted<br>Channel Commander";
		break;
		
		case "30" : 
		$str = "Requests Voice<br>Does not accept whispers<br>Player is away<br>Microphone muted";
		break;
		
		case "31" : 
		$str = "Requests Voice<br>Does not accept whispers<br>Player is away<br>Microphone muted<br>Channel Commander";
		break;
		
		case "32" : 
		$str = "Speaker/Headphone muted";
		break;
		
		case "33" : 
		$str = "Speaker/Headphone muted<br>Channel Commander";
		break;
		
		case "34" : 
		$str = "Requests Voice<br>Speaker/Headphone muted";
		break;
		
		case "35" : 
		$str = "Requests Voice<br>Speaker/Headphone muted<br>Channel Commander";
		break;
		
		case "36" : 
		$str = "Does not accept whispers<br>Speaker/Headphone muted";
		break;
		
		case "37" : 
		$str = "Does not accept whispers<br>Speaker/Headphone muted<br>Channel Commander";
		break;
		
		case "38" : 
		$str = "Requests Voice<br>Does not accept whispers<br>Speaker/Headphone muted";
		break;
		
		case "39" : 
		$str = "Requests Voice<br>Does not accept whispers<br>Speaker/Headphone muted<br>Channel Commander";
		break;
		
		case "40" : 
		$str = "Player is away<br>Speaker/Headphone muted";
		break;
		
		case "41" : 
		$str = "Player is away<br>Speaker/Headphone muted<br>Channel Commander";
		break;
		
		case "42" : 
		$str = "Requests Voice<br>Player is away<br>Speaker/Headphone muted";
		break;
		
		case "43" : 
		$str = "Requests Voice<br>Player is away<br>Speaker/Headphone muted<br>Channel Commander";
		break;
		
		case "44" : 
		$str = "Does not accept whispers<br>Player is away<br>Speaker/Headphone muted";
		break;
		
		case "45" : 
		$str = "Does not accept whispers<br>Player is away<br>Speaker/Headphone muted<br>Channel Commander";
		break;
		
		case "46" : 
		$str = "Requests Voice<br>Does not accept whispers<br>Player is away<br>Speaker/Headphone muted";
		break;
		
		case "47" : 
		$str = "Requests Voice<br>Does not accept whispers<br>Player is away<br>Speaker/Headphone muted<br>Channel Commander";
		break;
		
		case "48" : 
		$str = "Microphone muted<br>Speaker/Headphone muted";
		break;
		
		case "49" : 
		$str = "Microphone muted<br>Speaker/Headphone muted<br>Channel Commander";
		break;
		
		case "50" : 
		$str = "Requests Voice<br>Microphone muted<br>Speaker/Headphone muted";
		break;
		
		case "51" : 
		$str = "Requests Voice<br>Microphone muted<br>Speaker/Headphone muted<br>Channel Commander";
		break;
		
		case "52" : 
		$str = "Does not accept whispers<br>Microphone muted<br>Speaker/Headphone muted";
		break;
		
		case "53" : 
		$str = "Does not accept whispers<br>Microphone muted<br>Speaker/Headphone muted<br>Channel Commander";
		break;
		
		case "54" : 
		$str = "Requests Voice<br>Does not accept whispers<br>Microphone muted<br>Speaker/Headphone muted";
		break;
		
		case "55" : 
		$str = "Requests Voice<br>Does not accept whispers<br>Microphone muted<br>Speaker/Headphone muted<br>Channel Commander";
		break;
		
		case "56" : 
		$str = "Player is away<br>Microphone muted<br>Speaker/Headphone muted";
		break;
		
		case "57" : 
		$str = "Player is away<br>Microphone muted<br>Speaker/Headphone muted<br>Channel Commander";
		break;
		
		case "58" : 
		$str = "Requests Voice<br>Player is away<br>Microphone muted<br>Speaker/Headphone muted";
		break;
		
		case "59" : 
		$str = "Requests Voice<br>Player is away<br>Microphone muted<br>Speaker/Headphone muted<br>Channel Commander";
		break;		
		
		case "60" : 
		$str = "Does not accept whispers<br>Player is away<br>Microphone muted<br>Speaker/Headphone muted<br>Microphone muted";
		break;
		
		case "61" : 
		$str = "Does not accept whispers<br>Player is away<br>Microphone muted<br>Speaker/Headphone muted<br>Microphone muted<br>Channel Commander";
		break;
		
		default :
   		$str = "None";
		break;
	}
			 
	return $str;		
}

/*************************************
* Create an IndexOf function - Niklas 
* @Returns end position				  
*************************************/
function indexOf($str,$strChar)
{
	if(strlen(strchr($str,$strChar))>0) {
		$position_num = strpos($str,$strChar) + strlen($strChar);		
		return $position_num;
	} else {
		return -1;
	}
}


?>
