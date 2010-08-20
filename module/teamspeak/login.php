<?PHP
/* 
 Copyright (C) 2004  Niklas Håkansson <niklas.hk@telia.com>
 
 This script is free software; you can redistribute it and/or
 modify it under the terms of the GNU Lesser General Public. 
*/

$ip = $_GET['ip'];
$port = $_GET['port'];
if ($_POST)
{
   	$ok 				= false; 
	$nickname 			= $_POST['nickname'];
	$reg				= $_POST['reg'];
	if($reg=="re") 
	$loginname 			= $_POST['loginname'];
	else
	$loginname			= "";
   	$password 			= $_POST['password'];
	$autoLog			= $_POST['autoLog'];
	$channel		 	= $_POST['channel'];
	$channel			= str_replace("¶","'",$channel);
   	$channelpassword 	= $_POST['channelpassword'];
   	$time 				= time(); 
   
   	$cookie_data =  $nickname.'¶'.$reg.'¶'.$loginname.'¶'.$password.'¶'.$autoLog;
	if($autoLog=='true') setcookie ("PHPTS2Connect",$cookie_data, time()+60*60*24*30);
	else {
		if (isset($_COOKIE["PHPTS2Connect"])) 
			setcookie ("PHPTS2Connect", "", $time - 3600);   	
	}
} else if ($_GET) {
	$ok 		= true; 
	$channel	= $_GET['cName'];
	$nickname 	= "";
	$reg 		= "";
   	$loginname 	= ""; 
   	$password 	= "";
	$autoLog 	= false;  

	if (isset($_COOKIE["PHPTS2Connect"]))
	{
		$cookie_info = explode("¶", $_COOKIE['PHPTS2Connect']);
		$nickname 	= $cookie_info[0]; 
		$reg 		= $cookie_info[1]; 
   		$loginname 	= $cookie_info[2]; 
   		$password 	= $cookie_info[3];
		$autoLog	= $cookie_info[4];
	}  	
} else {
	$ok = false;
}
?>
<html>
<head>
	<title>Login</title>	
</head>
<style type="text/css">
html, body {
	background-color:#3c3c3c;
	margin:0;
	padding:0;
	font-family:"Trebuchet MS", Arial, Verdana, Tahoma,  Helvetica, sans-serif;
	font-size:11px;
	color:#636363;
}
A.wrapCell
{	
	font-family: Verdana, Arial,Helvetica, sans-serif;
	font-size: 10px;
	color: #000000;
	text-decoration: none;
	height : 14px;
}
.whiteBoldBread
{
	font-family: Verdana, Arial,Helvetica, sans-serif;
	font-size: 10px;
	font-weight: bold;	
	color: #FFFFFF;
}
TD.bgFrame 
{
	background-color: #000000;
}
TD.bgDarkFrame
{
	background-color: #DDDCDC;
}
TD.bgFrameGrey
{
	background-color: #ECEBEC;
}
.bread
{
	font-family: Verdana, Arial,Helvetica, sans-serif;
	font-size: 10px;
}
.loginbread
{
	font-family: Verdana, Arial,Helvetica, sans-serif;
	font-size: 10px;
}
.boldbread
{
	font-family: Verdana, Arial,Helvetica, sans-serif;
	font-size: 10px;
	font-weight: bold;
}
.headerBread
{
	font-family: Verdana, Arial,Helvetica, sans-serif;
	font-size: 11px;
	font-weight: bold;
}
.smallheaderBread
{
	font-family: Verdana, Arial,Helvetica, sans-serif;
	font-size: 10px;
	font-weight: bold;
}
.input
{
	font-family: Verdana,arial, Helvetica, sans-serif;
	font-size:10px;
	background-color : white;
	border: 1px solid;	
}
.logininput
{
	font-family: Verdana,arial, Helvetica, sans-serif;
	font-size:10px;
	background-color : white;
	border: 1px solid;	
}
.button
{
	font-family: Verdana,arial, Helvetica, sans-serif;
	font-size:10px;
	background-color : #C0C0C0;
	border: 1px solid;	
}	
}
</style>
<?if(isset($_POST['channel'])) {?>
<iframe src="teamspeak://<?=$ip?>:<?=$port?>/nickname=<?=$nickname?>?loginname=<?=$loginname?>?password=<?=$password?>?channel=<?=$channel?>?channelpassword=<?=$channelpassword?>" height="0" width="0"></iframe>
<center><input type="button" onclick="window.close();" value="Close" class="button"></center>
<?}?>
<script language="javascript">
/*********************************************************
* IE or NS/Mozilla
*********************************************************/
function isIE()
{
	var version=navigator.appVersion;
	var browser=navigator.appName;
	if (browser.indexOf("Netscape")!=-1)
		Ok = false; 
	else if(browser.indexOf("Microsoft")!=-1)
		Ok = true;
	return Ok;	
}	
/*********************************************************
* Hide Layer
*********************************************************/
function hide(id)
{
	if (document.all) //Explorer 4,5
	{	
		id.style.display='none';
	}
	else if (document.layers)		//Netscape 4
	{
		document.layers[id].document.layers[id].visibility="hidden";
	}
	else if (document.getElementById)	//Netscape 6
	{
		document.getElementById(id).style.visibility = "hidden";		
	}
}
/*********************************************************
* Show Layer
*********************************************************/
function show(id)
{	
	if(document.all)
	{
		if(id.style.display == 'none')
			id.style.display='';
		else
			id.style.display = 'none';			
	}
	else if (document.layers)		//Netscape 4
	{
		document.layers[id].document.layers[id].visibility="visible";
	}
	else if (document.getElementById)	//Netscape 6
	{
		document.getElementById(id).style.visibility = "visible";
	}				
}

function dis()
{
	if(!isIE())	{
		hide('lname');
		hide('linput');
	} else {	
		hide(lname);
		hide(linput);
	}
}

function en()
{
	if(!isIE())	{
		show('lname');
		show('linput');
	} else {	
		show(lname);
		show(linput);
	}
}

function doSubmit()
{
	if (document.frm.auto.checked)
		document.frm.autoLog.value="true";
	if(document.frm.nickname.value.length>0)
		document.frm.submit();
}
</script>
<body bottommargin="0" leftmargin="0" marginheight="0" marginwidth="0" rightmargin="0" topmargin="0">
<table cellpadding="2" cellspacing="0" border="1" bordercolorlight="#c0c0c0" bordercolordark="#dedede" width="200">
<tr>
	<td bgcolor="#dcdcdc">
	<table cellpadding="0" cellspacing="0" align="center">
	<?if($ok) {?>
	<form name="frm" action="" method="post">
	<input type="hidden" name="autoLog" value="false">
	<input type="hidden" name="channel" value="<?=$channel?>">
	<tr>
		<td class="bread" colspan="3">Nickname:</td>	
	</tr>
	<tr>
		<td colspan="2"><input type="text" name="nickname" class="input" style="width:180px;" value="<?=$nickname?>"></td>
	</tr>
	<tr><td height="5"><spacer type="block" width="1" height="5"></td></tr>
	<tr>
		<td colspan="2">
		<table cellpadding="0" cellspacing="0" width="100%">
		<tr>
			<td>
			<?if($reg=="an" or $reg=="") {?>
			<input type="radio" name="reg" value="an" onclick="dis();" checked>
			<?} else {?>
			<input type="radio" name="reg" value="an" onclick="dis();">
			<?}?>
			</td>
			<td class="bread">Anonymous</td>
			<td>
			<?if($reg=="an" or $reg=="") {?>
			<input type="radio" name="reg" value="re" onclick="en();">
			<?} else {?>
			<input type="radio" name="reg" value="re" onclick="en();" checked>
			<?}?>
			</td>
			<td class="bread">Registered</td>
		</tr>
		</table>
		</td>
	</tr>
	<tr><td height="5"><spacer type="block" width="1" height="5"></td></tr>
	<tr>
		<td colspan="2"><div class="bread" id="lname">Login name:</div></td>	
	</tr>
	<tr>
		<td colspan="2"><div class="bread" id="linput"><input type="text" name="loginname" class="logininput" style="width:180px;" value="<?=$loginname?>"></div></td>
	</tr>
	<tr>
		<td class="bread" colspan="2">Password:</td>	
	</tr>
	<tr>
		<td colspan="2"><input type="password" name="password" class="input" style="width:180px;" value="<?=$password?>"></td>
	</tr>
	<tr>
		<td class="bread" colspan="2">Channel password:</td>	
	</tr>
	<tr>
		<td colspan="2"><input type="password" name="channelpassword" class="input" style="width:180px;"></td>
	</tr>
	<tr><td height="5"><spacer type="block" width="1" height="5"></td></tr>
	<tr>
		<td colspan="2">
		<table cellpadding="0" cellspacing="0" width="100%">
		<tr>
			<td>
			<?if($autoLog) {?>
			<input type="checkbox" name="auto" checked>
			<?} else {?>
			<input type="checkbox" name="auto">
			<?}?>
			</td>
			<td class="bread" width="88%">Remeber me</td>		
		</tr>
		</table>
		</td>
	</tr>
	<tr><td height="5"><spacer type="block" width="1" height="5"></td></tr>
	<tr><td height="10"><spacer type="block" width="1" height="10"></td></tr>
	<tr>
		<td width="50%" align="right"><input type="button" onclick="javascript:doSubmit();" value="Connect" class="button">&nbsp;</td>
		<td width="50%" align="left">&nbsp;<input type="button" onclick="window.close();" value="Cancel" class="button"></td>	
	</tr>
	</form>
	<?} else {?>
	<tr>
		<td class="bread" colspan="3">TeamSpeak2....</td>	
	</tr>
	<?}?>
	</table>	
	</td>
</tr>
</table>
<?if($ok) {
if($reg=="an" or $reg=="") {?>
<script>
dis();
</script>
<?}
}?>
</body>
</html>
