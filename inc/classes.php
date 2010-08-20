<?php
    class db {
    	var $_conid = false;
    	var $_sql = '';
    	var $_resultID = false;
    	var $_count = 0;
    	var $_mode = 1;
    	var $_errors = 0;
    	
    	function db ($_mode = 1) {
    		$this->_conid = mysql_pconnect(MYSQL_HOST, MYSQL_USER, MYSQL_PASS);
    		$this->_mode = $_mode;
    		if(!$this->_conid) {
    			die(mysql_error());
    		} else {
    			if(!mysql_select_db(MYSQL_DATABASE)) {
    				die(mysql_error());
    			}
    		}
    	}
		function query($sql) {
			$this->_sql = $sql;
			$this->_count++;
			$this->_resultID = mysql_query($this->_sql,$this->_conid);
			if(!$this->_resultID) {
				$this->error();
			} else {
				return $this->_resultID;
			}
		}
		function num_rows() {
			if($this->_resultID) {
				return mysql_num_rows($this->_resultID);
			}
		}
		function result ($table, $feld, $where, $row = 0) {
			$this->query('SELECT '.$feld.' FROM '.$table.' WHERE '.$where);
			if($this->_resultID)
			    return @mysql_result($this->_resultID, $row);
		}
		function fetch_assoc($sql = '') {
			if($sql) {
				$this->query($sql);
				if($this->_resultID) 
					return mysql_fetch_assoc($this->_resultID);				
			} else {
				if($this->_resultID) 
					return mysql_fetch_assoc($this->_resultID);
			}
		}
		function last_id() {
			return mysql_insert_id($this->_conid);
		}
    	function error($art = FALSE) {
    		if($this->_mode) {
	            $str  = "<div class=\"sql_error\">SQL-Query: ".$this->_sql."<br />";
	            $str .= "Response:".mysql_error()."<br />";
	            $str .= "Error Code: ".mysql_errno().'</div>';
	        	table('SQL-Error', $str);
    		} else {
    			echo mysql_error();
    			echo "\nSQL:".$this->_sql;
    		}
    		$this->_errors++;
    	}
    	function echosql() {
    		echo $this->_sql;
    	}
    	function close() {
    		mysql_close($this->_conid);
    	}
    	function number_querys() {
    		return $this->_count;
    	}
    	function setMode($mode) {
    		$this->_mode = $mode;
    		$this->_errors = 0;
    	}
    	function errorNum() {
    		return $this->_errors;
    	}
    	function affekt_rows() {
    		return mysql_affected_rows(@$this->_conid);
    	}
    };

/*
* cPHPezMail Version 1.2 (2005-09-09 12:55 pm +7 GMT)
* COPYRIGHT 2004-2005 CHARIN NAWARITLOHA.
* Contact: inews@charinnawaritloha.net
*/
class cPHPezMail
{
	var $aHeader;
	var $aMessage;
	var $aPOSTFileAttach;
	var $aLocalFileAttach;
	var $sFrom;
	var $aTo;
	var $sMimeBoundary;
	var $sAltBoundary;
	var $sBodyText;
	var $sBodyHTML;
	var $sSubject;
	var $sCharset;
	var $nEncoding;
	var $sTempFileName;
	var $aMimeType;
	var $sDefaultMimeType;
	
	function cPHPezMail()
	{
		$this->aLocalFileAttach = array();
		$this->aPOSTFileAttach = array();
		$this->aHeader = array();
		$this->aMessage = array();
		$this->sMimeBoundary = '==Multipart_Boundary_X'. md5(time()) .'X';
		$this->sAltBoundary = '==Alternative_Boundary_X'. md5(time()) .'X';
		$this->aTo = array();
		$this->aCc = array();
		$this->aBcc = array();
		$this->sFrom = '';
		$this->sBodyHTML = '';
		$this->sBodyText = '';
		$this->sSubject = '';
		$this->sCharset = 'iso-8859-1';
		$this->nEncoding = 7;
		$this->sTempFileName = 'uploads/forum' . md5(time()) . '.tmp';
		$this->sDefaultMimeType = 'application/octet-stream';
		$this->aMimeType = array (
			'ai' => 'application/postscript',
			'aif' => 'audio/x-aiff',
			'aifc' => 'audio/x-aiff',
			'aiff' => 'audio/x-aiff',
			'asc' => 'text/plain',
			'au' => 'audio/basic',
			'avi' => 'video/x-msvideo',
			'bcpio' => 'application/x-bcpio',
			'bin' => 'application/octet-stream',
			'bmp' => 'image/bmp',
			'cdf' => 'application/x-netcdf',
			'cgm' => 'image/cgm',
			'class' => 'application/octet-stream',
			'cpio' => 'application/x-cpio',
			'cpt' => 'application/mac-compactpro',
			'csh' => 'application/x-csh',
			'css' => 'text/css',
			'dcr' => 'application/x-director',
			'dir' => 'application/x-director',
			'djv' => 'image/vnd.djvu',
			'djvu' => 'image/vnd.djvu',
			'dll' => 'application/octet-stream',
			'dms' => 'application/octet-stream',
			'doc' => 'application/msword',
			'dtd' => 'application/xml-dtd',
			'dvi' => 'application/x-dvi',
			'dxr' => 'application/x-director',
			'eps' => 'application/postscript',
			'etx' => 'text/x-setext',
			'exe' => 'application/octet-stream',
			'ez' => 'application/andrew-inset',
			'gif' => 'image/gif',
			'gram' => 'application/srgs',
			'grxml' => 'application/srgs+xml',
			'gtar' => 'application/x-gtar',
			'gzip' => 'application/x-gzip',
			'hdf' => 'application/x-hdf',
			'hqx' => 'application/mac-binhex40',
			'htm' => 'text/html',
			'html' => 'text/html',
			'ice' => 'x-conference/x-cooltalk',
			'ico' => 'image/x-icon',
			'ics' => 'text/calendar',
			'ief' => 'image/ief',
			'ifb' => 'text/calendar',
			'iges' => 'model/iges',
			'igs' => 'model/iges',
			'jpe' => 'image/jpeg',
			'jpeg' => 'image/jpeg',
			'jpg' => 'image/jpeg',
			'js' => 'application/x-javascript',
			'kar' => 'audio/midi',
			'latex' => 'application/x-latex',
			'lha' => 'application/octet-stream',
			'lzh' => 'application/octet-stream',
			'm3u' => 'audio/x-mpegurl',
			'man' => 'application/x-troff-man',
			'mathml' => 'application/mathml+xml',
			'me' => 'application/x-troff-me',
			'mesh' => 'model/mesh',
			'mid' => 'audio/midi',
			'midi' => 'audio/midi',
			'mov' => 'video/quicktime',
			'movie' => 'video/x-sgi-movie',
			'mp2' => 'audio/mpeg',
			'mp3' => 'audio/mpeg',
			'mpe' => 'video/mpeg',
			'mpeg' => 'video/mpeg',
			'mpg' => 'video/mpeg',
			'mpga' => 'audio/mpeg',
			'ms' => 'application/x-troff-ms',
			'msh' => 'model/mesh',
			'mxu' => 'video/vnd.mpegurl',
			'nc' => 'application/x-netcdf',
			'oda' => 'application/oda',
			'ogg' => 'application/ogg',
			'pbm' => 'image/x-portable-bitmap',
			'pdb' => 'chemical/x-pdb',
			'pdf' => 'application/pdf',
			'pgm' => 'image/x-portable-graymap',
			'pgn' => 'application/x-chess-pgn',
			'png' => 'image/png',
			'pnm' => 'image/x-portable-anymap',
			'ppm' => 'image/x-portable-pixmap',
			'ppt' => 'application/vnd.ms-powerpoint',
			'ps' => 'application/postscript',
			'qt' => 'video/quicktime',
			'ra' => 'audio/x-realaudio',
			'ram' => 'audio/x-pn-realaudio',
			'ras' => 'image/x-cmu-raster',
			'rdf' => 'application/rdf+xml',
			'rgb' => 'image/x-rgb',
			'rm' => 'audio/x-pn-realaudio',
			'roff' => 'application/x-troff',
			'rpm' => 'audio/x-pn-realaudio-plugin',
			'rtf' => 'text/rtf',
			'rtx' => 'text/richtext',
			'sgm' => 'text/sgml',
			'sgml' => 'text/sgml',
			'sh' => 'application/x-sh',
			'shar' => 'application/x-shar',
			'silo' => 'model/mesh',
			'sit' => 'application/x-stuffit',
			'skd' => 'application/x-koan',
			'skm' => 'application/x-koan',
			'skp' => 'application/x-koan',
			'skt' => 'application/x-koan',
			'smi' => 'application/smil',
			'smil' => 'application/smil',
			'snd' => 'audio/basic',
			'so' => 'application/octet-stream',
			'spl' => 'application/x-futuresplash',
			'src' => 'application/x-wais-source',
			'sv4cpio' => 'application/x-sv4cpio',
			'sv4crc' => 'application/x-sv4crc',
			'svg' => 'image/svg+xml',
			'swf' => 'application/x-shockwave-flash',
			't' => 'application/x-troff',
			'tar' => 'application/x-tar',
			'tcl' => 'application/x-tcl',
			'tex' => 'application/x-tex',
			'texi' => 'application/x-texinfo',
			'texinfo' => 'application/x-texinfo',
			'tif' => 'image/tiff',
			'tiff' => 'image/tiff',
			'tr' => 'application/x-troff',
			'tsv' => 'text/tab-separated-values',
			'txt' => 'text/plain',
			'ustar' => 'application/x-ustar',
			'vcd' => 'application/x-cdlink',
			'vrml' => 'model/vrml',
			'vxml' => 'application/voicexml+xml',
			'wav' => 'audio/x-wav',
			'wbmp' => 'image/vnd.wap.wbmp',
			'wbxml' => 'application/vnd.wap.wbxml',
			'wml' => 'text/vnd.wap.wml',
			'wmlc' => 'application/vnd.wap.wmlc',
			'wmls' => 'text/vnd.wap.wmlscript',
			'wmlsc' => 'application/vnd.wap.wmlscriptc',
			'wrl' => 'model/vrml',
			'xbm' => 'image/x-xbitmap',
			'xht' => 'application/xhtml+xml',
			'xhtml' => 'application/xhtml+xml',
			'xls' => 'application/vnd.ms-excel',
			'xml' => 'application/xml',
			'xpm' => 'image/x-xpixmap',
			'xsl' => 'application/xml',
			'xslt' => 'application/xslt+xml',
			'xwd' => 'image/x-xwindowdump',
			'xyz' => 'chemical/x-xyz',
			'zip' => 'application/zip');

		//If you want to make default value TO DO here
		//make default header
		$this->AddHeader('MIME-Version', '1.0');
	}
	
	function SetFrom($str_Email, $str_ScreenName='')
	{
		if($str_ScreenName)
			$this->sFrom = "$str_ScreenName <$str_Email>";
		else
			$this->sFrom = "$str_Email";
	}

	function AddTo($str_Email, $str_ScreenName='')
	{
		if($str_ScreenName)
			$this->aTo[] = "$str_ScreenName <$str_Email>";
		else
			$this->aTo[] = "$str_Email";
	}
	
	function AddCc($str_Email, $str_ScreenName='')
	{
		if($str_ScreenName)
			$this->aCc[] = "$str_ScreenName <$str_Email>";
		else
			$this->aCc[] = "$str_Email";
	}

	function AddBcc($str_Email, $str_ScreenName='')
	{
		if($str_ScreenName)
			$this->aBcc[] = "$str_ScreenName <$str_Email>";
		else
			$this->aBcc[] = "$str_Email";
	}

	function AddHeader($str_Header, $str_Value='')
	{
		$this->aHeader[] = $str_Header . ': ' . $str_Value;
	}

	function SetSubject($str_Subject)
	{
		$this->sSubject = $str_Subject;
	}
	
	function SetBodyText($str_Text)
	{
		$this->sBodyText = $str_Text;
	}
	
	function SetBodyHTML($str_HTML)
	{
		$this->sBodyHTML = $str_HTML;
	}
	
	function AddAttachPOSTFile($array_POSTFile)
	{
		$this->aPOSTFileAttach[] = $array_POSTFile;
	}
	
	function AddAttachLocalFile($str_LocalFile, $str_MimeType='')
	{
		if(!$str_MimeType)
		{
			//Auto detect mime type from file extention
			preg_match("/\.[^.]+$/", $str_LocalFile, $aExt);
			$sExt = strtolower(str_replace('.', '', $aExt[0]));
			if($sExt)
			{
				if(isset($this->aMimeType[$sExt]))
					$str_MimeType = $this->aMimeType[$sExt];
				else
					$str_MimeType = $this->sDefaultMimeType;
			}
			else
				$str_MimeType = $this->sDefaultMimeType;
		}
	
		$aLocalFile = array();
		$aLocalFile['tmp_name'] = $str_LocalFile;
		$aLocalFile['name'] = preg_replace("/[^\/]*\//", '', $str_LocalFile);
		$aLocalFile['type'] = $str_MimeType;
		$aLocalFile['size'] = filesize($str_LocalFile);
		$this->aLocalFileAttach[] = $aLocalFile;
	}

	function SetCharset($str_Charset)
	{
		$this->sCharset = $str_Charset;
	}
	
	function SetEncodingBit($int_Encoding)
	{
		$this->nEncoding = $int_Encoding;
	}

	//Generate Header for EML format
	function ExportEML()
	{
		$aHeaderTemp = $this->aHeader;	
		$this->AddHeader('To', implode(', ', $this->aTo));
		$this->AddHeader('Subject', $this->sSubject);
		$this->AddHeader('Date', date('r'));
		$sEmailHeader = $this->_MakeHeader();
		$sEmailBody = $this->_MakeMessage();
		$sEMail = $sEmailHeader . "\r\n\r\n" . $sEmailBody;
		$this->aHeader = $aHeaderTemp;
		return $sEMail;
	}
	
	//Send E-mail
	function Send()
	{
		$sTo = implode(', ', $this->aTo);
		$bResponse = mail($sTo, $this->sSubject, $this->_MakeMessage(), $this->_MakeHeader());
		return $bResponse;
	}
	
	function _MakeHeader()
	{
		$aHeader = $this->aHeader;
		//$aHeader[] = "X-Mailer: cPHPezMail,1.2";
		$aHeader[] = 'From: ' . $this->sFrom;
		if($this->aCc)
			$aHeader[] = 'Cc: ' . implode(', ', $this->aCc);
		if($this->aBcc)
			$aHeader[] = 'Bcc: ' . implode(', ', $this->aBcc);
		
		if($this->sBodyHTML || $this->aPOSTFileAttach || $this->aLocalFileAttach) //Check for multipart format
			$aHeader[] = "Content-Type: multipart/mixed;\r\n boundary=\"{$this->sMimeBoundary}\"";
		else
			$aHeader[] = "Content-Type: text/plain; charset={$this->sCharset}";

		return implode("\r\n", $aHeader);
	}

	
	function _MakeMessage()
	{
		$sMessage = '';
		if($this->sBodyHTML || $this->aPOSTFileAttach || $this->aLocalFileAttach) //Check for multipart format
		{
			//Start Multipart Format
			$sMessage .= "This is a multi-part message in MIME format.\r\n";
			
			if($this->sBodyText || $this->sBodyHTML)
			{
				//Open Alternative Part
				$sMessage .= "--{$this->sMimeBoundary}\r\n";
				$sMessage .= "Content-Type: multipart/alternative;\r\n boundary=\"{$this->sAltBoundary}\"\r\n\r\n";
			}
			
			if($this->sBodyText)
			{
				//Plain Text Message
				$sMessage .= "--{$this->sAltBoundary}\r\n";
				$sMessage .= "Content-Type: text/plain; charset={$this->sCharset}\r\nContent-Transfer-Encoding: {$this->nEncoding}bit\r\n\r\n";
				$sMessage .= rtrim($this->sBodyText);
				$sMessage .= "\r\n";
			}

			if($this->sBodyHTML)
			{
				//HTML Message
				$sMessage .= "--{$this->sAltBoundary}\r\n";
				$sMessage .= "Content-Type: text/html; charset={$this->sCharset}\r\nContent-Transfer-Encoding: {$this->nEncoding}bit\r\n\r\n";
				$sMessage .= rtrim($this->sBodyHTML);
				$sMessage .= "\r\n";
			}
			
			if($this->sBodyText || $this->sBodyHTML)
				//Close Alternative Part
				$sMessage .= "--{$this->sAltBoundary}--\r\n\r\n";

			if($this->aPOSTFileAttach)
			{
				//Attach POST Files
				foreach($this->aPOSTFileAttach as $aPOSTFile)
				{					
					if(!$aPOSTFile['size'])
						continue;
						
					if(!is_uploaded_file($aPOSTFile['tmp_name']))
						continue;
						
					if(copy($aPOSTFile['tmp_name'], $this->sTempFileName))
					{
						$fpAttachFile = fopen($this->sTempFileName, 'rb');
						if(!$fpAttachFile)
							continue;

						$sFileData = fread($fpAttachFile, $aPOSTFile['size']);
						fclose($fpAttachFile);
						@unlink($this->sTempFileName);

						$sFileData = chunk_split(base64_encode($sFileData));
						$sMessage .= "--{$this->sMimeBoundary}\r\n";
						$sMessage .= "Content-Type: {$aPOSTFile['type']};\r\n name=\"{$aPOSTFile['name']}\"\r\n";
						$sMessage .= "Content-Transfer-Encoding: base64\r\n";
						$sMessage .= "Content-Disposition: attachment;\r\n filename=\"{$aPOSTFile['name']}\"\r\n\r\n";

						$sMessage .= $sFileData;
					}
				}
			}
			
			if($this->aLocalFileAttach)
			{
				//Attach Local Files
				foreach($this->aLocalFileAttach as $aLocalFile)
				{
					if(!$aLocalFile['size'])
						continue;
						
					$fpAttachFile = fopen($aLocalFile['tmp_name'], 'rb');
					if(!$fpAttachFile)
						continue;

					$sFileData = fread($fpAttachFile, $aLocalFile['size']);
					fclose($fpAttachFile);

					$sFileData = chunk_split(base64_encode($sFileData));
					$sMessage .= "--{$this->sMimeBoundary}\r\n";
					$sMessage .= "Content-Type: {$aLocalFile['type']};\r\n name=\"{$aLocalFile['name']}\"\r\n";
					$sMessage .= "Content-Transfer-Encoding: base64\r\n";
					$sMessage .= "Content-Disposition: attachment;\r\n filename=\"{$aLocalFile['name']}\"\r\n\r\n";

					$sMessage .= $sFileData;
				}
			}

			
			//Close Message
			$sMessage .= "--{$this->sMimeBoundary}--\r\n";
		}
		else
		{
			//Start Plain Text Format
			$sMessage .= $this->sBodyText;
		}
		return $sMessage;
	}
}
class SecureSession
{

  // Include browser name in fingerprint?
  var $check_browser = true;

  // How many numbers from IP use in fingerprint?
  var $check_ip_blocks = 3;

  // Control word - any word you want.
  var $secure_word = 'SECURESTAFF';

  // Regenerate session ID to prevent fixation attacks?
  var $regenerate_id = false;


  // Call this when init session.
  function Open()
  {
    $_SESSION['ss_fprint'] = $this->_Fingerprint();
    $this->_RegenerateId();
  }

  // Call this to check session.
  function Check()
  {
    $this->_RegenerateId();
    return (isset($_SESSION['ss_fprint'])
      && $_SESSION['ss_fprint'] == $this->_Fingerprint());
  }

  // Internal function. Returns MD5 from fingerprint.
  function _Fingerprint()
  {
    $fingerprint = $this->secure_word;
    if ($this->check_browser)
    {
      $fingerprint .= $_SERVER['HTTP_USER_AGENT'];
    }
    if ($this->check_ip_blocks)
    {
      $num_blocks = abs(intval($this->check_ip_blocks));
      if ($num_blocks > 4)
      {
        $num_blocks = 4;
      } 
      $blocks = explode('.', $_SERVER['REMOTE_ADDR']);
      for ($i=0; $i<$num_blocks; $i++)
      {
        $fingerprint .= $blocks[$i] . '.';
      }
    }
    return md5($fingerprint);
  }

  // Internal function. Regenerates session ID if possible.
  function _RegenerateId()
  {
    if ($this->regenerate_id && function_exists('session_regenerate_id'))
    {
      session_regenerate_id();
    }
  }

}

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * Converts to and from JSON format.
 *
 * JSON (JavaScript Object Notation) is a lightweight data-interchange
 * format. It is easy for humans to read and write. It is easy for machines
 * to parse and generate. It is based on a subset of the JavaScript
 * Programming Language, Standard ECMA-262 3rd Edition - December 1999.
 * This feature can also be found in  Python. JSON is a text format that is
 * completely language independent but uses conventions that are familiar
 * to programmers of the C-family of languages, including C, C++, C#, Java,
 * JavaScript, Perl, TCL, and many others. These properties make JSON an
 * ideal data-interchange language.
 *
 * This package provides a simple encoder and decoder for JSON notation. It
 * is intended for use with client-side Javascript applications that make
 * use of HTTPRequest to perform server communication functions - data can
 * be encoded into JSON notation for use in a client-side javascript, or
 * decoded from incoming Javascript requests. JSON format is native to
 * Javascript, and can be directly eval()'ed with no further parsing
 * overhead
 *
 * All strings should be in ASCII or UTF-8 format!
 *
 * LICENSE: Redistribution and use in source and binary forms, with or
 * without modification, are permitted provided that the following
 * conditions are met: Redistributions of source code must retain the
 * above copyright notice, this list of conditions and the following
 * disclaimer. Redistributions in binary form must reproduce the above
 * copyright notice, this list of conditions and the following disclaimer
 * in the documentation and/or other materials provided with the
 * distribution.
 *
 * THIS SOFTWARE IS PROVIDED ``AS IS'' AND ANY EXPRESS OR IMPLIED
 * WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF
 * MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN
 * NO EVENT SHALL CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT,
 * INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING,
 * BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS
 * OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND
 * ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR
 * TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE
 * USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH
 * DAMAGE.
 *
 * @category
 * @package     Services_JSON
 * @author      Michal Migurski <mike-json@teczno.com>
 * @author      Matt Knapp <mdknapp[at]gmail[dot]com>
 * @author      Brett Stimmerman <brettstimmerman[at]gmail[dot]com>
 * @copyright   2005 Michal Migurski
 * @version     CVS: $Id: JSON.php,v 1.31 2006/06/28 05:54:17 migurski Exp $
 * @license     http://www.opensource.org/licenses/bsd-license.php
 * @link        http://pear.php.net/pepr/pepr-proposal-show.php?id=198
 */

/**
 * Marker constant for Services_JSON::decode(), used to flag stack state
 */
define('SERVICES_JSON_SLICE',   1);

/**
 * Marker constant for Services_JSON::decode(), used to flag stack state
 */
define('SERVICES_JSON_IN_STR',  2);

/**
 * Marker constant for Services_JSON::decode(), used to flag stack state
 */
define('SERVICES_JSON_IN_ARR',  3);

/**
 * Marker constant for Services_JSON::decode(), used to flag stack state
 */
define('SERVICES_JSON_IN_OBJ',  4);

/**
 * Marker constant for Services_JSON::decode(), used to flag stack state
 */
define('SERVICES_JSON_IN_CMT', 5);

/**
 * Behavior switch for Services_JSON::decode()
 */
define('SERVICES_JSON_LOOSE_TYPE', 16);

/**
 * Behavior switch for Services_JSON::decode()
 */
define('SERVICES_JSON_SUPPRESS_ERRORS', 32);

/**
 * Converts to and from JSON format.
 *
 * Brief example of use:
 *
 * <code>
 * // create a new instance of Services_JSON
 * $json = new Services_JSON();
 *
 * // convert a complexe value to JSON notation, and send it to the browser
 * $value = array('foo', 'bar', array(1, 2, 'baz'), array(3, array(4)));
 * $output = $json->encode($value);
 *
 * print($output);
 * // prints: ["foo","bar",[1,2,"baz"],[3,[4]]]
 *
 * // accept incoming POST data, assumed to be in JSON notation
 * $input = file_get_contents('php://input', 1000000);
 * $value = $json->decode($input);
 * </code>
 */
class Services_JSON
{
   /**
    * constructs a new JSON instance
    *
    * @param    int     $use    object behavior flags; combine with boolean-OR
    *
    *                           possible values:
    *                           - SERVICES_JSON_LOOSE_TYPE:  loose typing.
    *                                   "{...}" syntax creates associative arrays
    *                                   instead of objects in decode().
    *                           - SERVICES_JSON_SUPPRESS_ERRORS:  error suppression.
    *                                   Values which can't be encoded (e.g. resources)
    *                                   appear as NULL instead of throwing errors.
    *                                   By default, a deeply-nested resource will
    *                                   bubble up with an error, so all return values
    *                                   from encode() should be checked with isError()
    */
    function Services_JSON($use = 0)
    {
        $this->use = $use;
    }

   /**
    * convert a string from one UTF-16 char to one UTF-8 char
    *
    * Normally should be handled by mb_convert_encoding, but
    * provides a slower PHP-only method for installations
    * that lack the multibye string extension.
    *
    * @param    string  $utf16  UTF-16 character
    * @return   string  UTF-8 character
    * @access   private
    */
    function utf162utf8($utf16)
    {
        // oh please oh please oh please oh please oh please
        if(function_exists('mb_convert_encoding')) {
            return mb_convert_encoding($utf16, 'UTF-8', 'UTF-16');
        }

        $bytes = (ord($utf16{0}) << 8) | ord($utf16{1});

        switch(true) {
            case ((0x7F & $bytes) == $bytes):
                // this case should never be reached, because we are in ASCII range
                // see: http://www.cl.cam.ac.uk/~mgk25/unicode.html#utf-8
                return chr(0x7F & $bytes);

            case (0x07FF & $bytes) == $bytes:
                // return a 2-byte UTF-8 character
                // see: http://www.cl.cam.ac.uk/~mgk25/unicode.html#utf-8
                return chr(0xC0 | (($bytes >> 6) & 0x1F))
                     . chr(0x80 | ($bytes & 0x3F));

            case (0xFFFF & $bytes) == $bytes:
                // return a 3-byte UTF-8 character
                // see: http://www.cl.cam.ac.uk/~mgk25/unicode.html#utf-8
                return chr(0xE0 | (($bytes >> 12) & 0x0F))
                     . chr(0x80 | (($bytes >> 6) & 0x3F))
                     . chr(0x80 | ($bytes & 0x3F));
        }

        // ignoring UTF-32 for now, sorry
        return '';
    }

   /**
    * convert a string from one UTF-8 char to one UTF-16 char
    *
    * Normally should be handled by mb_convert_encoding, but
    * provides a slower PHP-only method for installations
    * that lack the multibye string extension.
    *
    * @param    string  $utf8   UTF-8 character
    * @return   string  UTF-16 character
    * @access   private
    */
    function utf82utf16($utf8)
    {
        // oh please oh please oh please oh please oh please
        if(function_exists('mb_convert_encoding')) {
            return mb_convert_encoding($utf8, 'UTF-16', 'UTF-8');
        }

        switch(strlen($utf8)) {
            case 1:
                // this case should never be reached, because we are in ASCII range
                // see: http://www.cl.cam.ac.uk/~mgk25/unicode.html#utf-8
                return $utf8;

            case 2:
                // return a UTF-16 character from a 2-byte UTF-8 char
                // see: http://www.cl.cam.ac.uk/~mgk25/unicode.html#utf-8
                return chr(0x07 & (ord($utf8{0}) >> 2))
                     . chr((0xC0 & (ord($utf8{0}) << 6))
                         | (0x3F & ord($utf8{1})));

            case 3:
                // return a UTF-16 character from a 3-byte UTF-8 char
                // see: http://www.cl.cam.ac.uk/~mgk25/unicode.html#utf-8
                return chr((0xF0 & (ord($utf8{0}) << 4))
                         | (0x0F & (ord($utf8{1}) >> 2)))
                     . chr((0xC0 & (ord($utf8{1}) << 6))
                         | (0x7F & ord($utf8{2})));
        }

        // ignoring UTF-32 for now, sorry
        return '';
    }

   /**
    * encodes an arbitrary variable into JSON format
    *
    * @param    mixed   $var    any number, boolean, string, array, or object to be encoded.
    *                           see argument 1 to Services_JSON() above for array-parsing behavior.
    *                           if var is a strng, note that encode() always expects it
    *                           to be in ASCII or UTF-8 format!
    *
    * @return   mixed   JSON string representation of input var or an error if a problem occurs
    * @access   public
    */
    function encode($var)
    {
        switch (gettype($var)) {
            case 'boolean':
                return $var ? 'true' : 'false';

            case 'NULL':
                return 'null';

            case 'integer':
                return (int) $var;

            case 'double':
            case 'float':
                return (float) $var;

            case 'string':
                // STRINGS ARE EXPECTED TO BE IN ASCII OR UTF-8 FORMAT
                $ascii = '';
                $strlen_var = strlen($var);

               /*
                * Iterate over every character in the string,
                * escaping with a slash or encoding to UTF-8 where necessary
                */
                for ($c = 0; $c < $strlen_var; ++$c) {

                    $ord_var_c = ord($var{$c});

                    switch (true) {
                        case $ord_var_c == 0x08:
                            $ascii .= '\b';
                            break;
                        case $ord_var_c == 0x09:
                            $ascii .= '\t';
                            break;
                        case $ord_var_c == 0x0A:
                            $ascii .= '\n';
                            break;
                        case $ord_var_c == 0x0C:
                            $ascii .= '\f';
                            break;
                        case $ord_var_c == 0x0D:
                            $ascii .= '\r';
                            break;

                        case $ord_var_c == 0x22:
                        case $ord_var_c == 0x2F:
                        case $ord_var_c == 0x5C:
                            // double quote, slash, slosh
                            $ascii .= '\\'.$var{$c};
                            break;

                        case (($ord_var_c >= 0x20) && ($ord_var_c <= 0x7F)):
                            // characters U-00000000 - U-0000007F (same as ASCII)
                            $ascii .= $var{$c};
                            break;

                        case (($ord_var_c & 0xE0) == 0xC0):
                            // characters U-00000080 - U-000007FF, mask 110XXXXX
                            // see http://www.cl.cam.ac.uk/~mgk25/unicode.html#utf-8
                            $char = pack('C*', $ord_var_c, ord($var{$c + 1}));
                            $c += 1;
                            $utf16 = $this->utf82utf16($char);
                            $ascii .= sprintf('\u%04s', bin2hex($utf16));
                            break;

                        case (($ord_var_c & 0xF0) == 0xE0):
                            // characters U-00000800 - U-0000FFFF, mask 1110XXXX
                            // see http://www.cl.cam.ac.uk/~mgk25/unicode.html#utf-8
                            $char = pack('C*', $ord_var_c,
                                         ord($var{$c + 1}),
                                         ord($var{$c + 2}));
                            $c += 2;
                            $utf16 = $this->utf82utf16($char);
                            $ascii .= sprintf('\u%04s', bin2hex($utf16));
                            break;

                        case (($ord_var_c & 0xF8) == 0xF0):
                            // characters U-00010000 - U-001FFFFF, mask 11110XXX
                            // see http://www.cl.cam.ac.uk/~mgk25/unicode.html#utf-8
                            $char = pack('C*', $ord_var_c,
                                         ord($var{$c + 1}),
                                         ord($var{$c + 2}),
                                         ord($var{$c + 3}));
                            $c += 3;
                            $utf16 = $this->utf82utf16($char);
                            $ascii .= sprintf('\u%04s', bin2hex($utf16));
                            break;

                        case (($ord_var_c & 0xFC) == 0xF8):
                            // characters U-00200000 - U-03FFFFFF, mask 111110XX
                            // see http://www.cl.cam.ac.uk/~mgk25/unicode.html#utf-8
                            $char = pack('C*', $ord_var_c,
                                         ord($var{$c + 1}),
                                         ord($var{$c + 2}),
                                         ord($var{$c + 3}),
                                         ord($var{$c + 4}));
                            $c += 4;
                            $utf16 = $this->utf82utf16($char);
                            $ascii .= sprintf('\u%04s', bin2hex($utf16));
                            break;

                        case (($ord_var_c & 0xFE) == 0xFC):
                            // characters U-04000000 - U-7FFFFFFF, mask 1111110X
                            // see http://www.cl.cam.ac.uk/~mgk25/unicode.html#utf-8
                            $char = pack('C*', $ord_var_c,
                                         ord($var{$c + 1}),
                                         ord($var{$c + 2}),
                                         ord($var{$c + 3}),
                                         ord($var{$c + 4}),
                                         ord($var{$c + 5}));
                            $c += 5;
                            $utf16 = $this->utf82utf16($char);
                            $ascii .= sprintf('\u%04s', bin2hex($utf16));
                            break;
                    }
                }

                return '"'.$ascii.'"';

            case 'array':
               /*
                * As per JSON spec if any array key is not an integer
                * we must treat the the whole array as an object. We
                * also try to catch a sparsely populated associative
                * array with numeric keys here because some JS engines
                * will create an array with empty indexes up to
                * max_index which can cause memory issues and because
                * the keys, which may be relevant, will be remapped
                * otherwise.
                *
                * As per the ECMA and JSON specification an object may
                * have any string as a property. Unfortunately due to
                * a hole in the ECMA specification if the key is a
                * ECMA reserved word or starts with a digit the
                * parameter is only accessible using ECMAScript's
                * bracket notation.
                */

                // treat as a JSON object
                if (is_array($var) && count($var) && (array_keys($var) !== range(0, sizeof($var) - 1))) {
                    $properties = array_map(array($this, 'name_value'),
                                            array_keys($var),
                                            array_values($var));

                    foreach($properties as $property) {
                        if(Services_JSON::isError($property)) {
                            return $property;
                        }
                    }

                    return '{' . join(',', $properties) . '}';
                }

                // treat it like a regular array
                $elements = array_map(array($this, 'encode'), $var);

                foreach($elements as $element) {
                    if(Services_JSON::isError($element)) {
                        return $element;
                    }
                }

                return '[' . join(',', $elements) . ']';

            case 'object':
                $vars = get_object_vars($var);

                $properties = array_map(array($this, 'name_value'),
                                        array_keys($vars),
                                        array_values($vars));

                foreach($properties as $property) {
                    if(Services_JSON::isError($property)) {
                        return $property;
                    }
                }

                return '{' . join(',', $properties) . '}';

            default:
                return ($this->use & SERVICES_JSON_SUPPRESS_ERRORS)
                    ? 'null'
                    : new Services_JSON_Error(gettype($var)." can not be encoded as JSON string");
        }
    }

   /**
    * array-walking function for use in generating JSON-formatted name-value pairs
    *
    * @param    string  $name   name of key to use
    * @param    mixed   $value  reference to an array element to be encoded
    *
    * @return   string  JSON-formatted name-value pair, like '"name":value'
    * @access   private
    */
    function name_value($name, $value)
    {
        $encoded_value = $this->encode($value);

        if(Services_JSON::isError($encoded_value)) {
            return $encoded_value;
        }

        return $this->encode(strval($name)) . ':' . $encoded_value;
    }

   /**
    * reduce a string by removing leading and trailing comments and whitespace
    *
    * @param    $str    string      string value to strip of comments and whitespace
    *
    * @return   string  string value stripped of comments and whitespace
    * @access   private
    */
    function reduce_string($str)
    {
        $str = preg_replace(array(

                // eliminate single line comments in '// ...' form
                '#^\s*//(.+)$#m',

                // eliminate multi-line comments in '/* ... */' form, at start of string
                '#^\s*/\*(.+)\*/#Us',

                // eliminate multi-line comments in '/* ... */' form, at end of string
                '#/\*(.+)\*/\s*$#Us'

            ), '', $str);

        // eliminate extraneous space
        return trim($str);
    }

   /**
    * decodes a JSON string into appropriate variable
    *
    * @param    string  $str    JSON-formatted string
    *
    * @return   mixed   number, boolean, string, array, or object
    *                   corresponding to given JSON input string.
    *                   See argument 1 to Services_JSON() above for object-output behavior.
    *                   Note that decode() always returns strings
    *                   in ASCII or UTF-8 format!
    * @access   public
    */
    function decode($str)
    {
        $str = $this->reduce_string($str);

        switch (strtolower($str)) {
            case 'true':
                return true;

            case 'false':
                return false;

            case 'null':
                return null;

            default:
                $m = array();

                if (is_numeric($str)) {
                    // Lookie-loo, it's a number

                    // This would work on its own, but I'm trying to be
                    // good about returning integers where appropriate:
                    // return (float)$str;

                    // Return float or int, as appropriate
                    return ((float)$str == (integer)$str)
                        ? (integer)$str
                        : (float)$str;

                } elseif (preg_match('/^("|\').*(\1)$/s', $str, $m) && $m[1] == $m[2]) {
                    // STRINGS RETURNED IN UTF-8 FORMAT
                    $delim = substr($str, 0, 1);
                    $chrs = substr($str, 1, -1);
                    $utf8 = '';
                    $strlen_chrs = strlen($chrs);

                    for ($c = 0; $c < $strlen_chrs; ++$c) {

                        $substr_chrs_c_2 = substr($chrs, $c, 2);
                        $ord_chrs_c = ord($chrs{$c});

                        switch (true) {
                            case $substr_chrs_c_2 == '\b':
                                $utf8 .= chr(0x08);
                                ++$c;
                                break;
                            case $substr_chrs_c_2 == '\t':
                                $utf8 .= chr(0x09);
                                ++$c;
                                break;
                            case $substr_chrs_c_2 == '\n':
                                $utf8 .= chr(0x0A);
                                ++$c;
                                break;
                            case $substr_chrs_c_2 == '\f':
                                $utf8 .= chr(0x0C);
                                ++$c;
                                break;
                            case $substr_chrs_c_2 == '\r':
                                $utf8 .= chr(0x0D);
                                ++$c;
                                break;

                            case $substr_chrs_c_2 == '\\"':
                            case $substr_chrs_c_2 == '\\\'':
                            case $substr_chrs_c_2 == '\\\\':
                            case $substr_chrs_c_2 == '\\/':
                                if (($delim == '"' && $substr_chrs_c_2 != '\\\'') ||
                                   ($delim == "'" && $substr_chrs_c_2 != '\\"')) {
                                    $utf8 .= $chrs{++$c};
                                }
                                break;

                            case preg_match('/\\\u[0-9A-F]{4}/i', substr($chrs, $c, 6)):
                                // single, escaped unicode character
                                $utf16 = chr(hexdec(substr($chrs, ($c + 2), 2)))
                                       . chr(hexdec(substr($chrs, ($c + 4), 2)));
                                $utf8 .= $this->utf162utf8($utf16);
                                $c += 5;
                                break;

                            case ($ord_chrs_c >= 0x20) && ($ord_chrs_c <= 0x7F):
                                $utf8 .= $chrs{$c};
                                break;

                            case ($ord_chrs_c & 0xE0) == 0xC0:
                                // characters U-00000080 - U-000007FF, mask 110XXXXX
                                //see http://www.cl.cam.ac.uk/~mgk25/unicode.html#utf-8
                                $utf8 .= substr($chrs, $c, 2);
                                ++$c;
                                break;

                            case ($ord_chrs_c & 0xF0) == 0xE0:
                                // characters U-00000800 - U-0000FFFF, mask 1110XXXX
                                // see http://www.cl.cam.ac.uk/~mgk25/unicode.html#utf-8
                                $utf8 .= substr($chrs, $c, 3);
                                $c += 2;
                                break;

                            case ($ord_chrs_c & 0xF8) == 0xF0:
                                // characters U-00010000 - U-001FFFFF, mask 11110XXX
                                // see http://www.cl.cam.ac.uk/~mgk25/unicode.html#utf-8
                                $utf8 .= substr($chrs, $c, 4);
                                $c += 3;
                                break;

                            case ($ord_chrs_c & 0xFC) == 0xF8:
                                // characters U-00200000 - U-03FFFFFF, mask 111110XX
                                // see http://www.cl.cam.ac.uk/~mgk25/unicode.html#utf-8
                                $utf8 .= substr($chrs, $c, 5);
                                $c += 4;
                                break;

                            case ($ord_chrs_c & 0xFE) == 0xFC:
                                // characters U-04000000 - U-7FFFFFFF, mask 1111110X
                                // see http://www.cl.cam.ac.uk/~mgk25/unicode.html#utf-8
                                $utf8 .= substr($chrs, $c, 6);
                                $c += 5;
                                break;

                        }

                    }

                    return $utf8;

                } elseif (preg_match('/^\[.*\]$/s', $str) || preg_match('/^\{.*\}$/s', $str)) {
                    // array, or object notation

                    if ($str{0} == '[') {
                        $stk = array(SERVICES_JSON_IN_ARR);
                        $arr = array();
                    } else {
                        if ($this->use & SERVICES_JSON_LOOSE_TYPE) {
                            $stk = array(SERVICES_JSON_IN_OBJ);
                            $obj = array();
                        } else {
                            $stk = array(SERVICES_JSON_IN_OBJ);
                            $obj = new stdClass();
                        }
                    }

                    array_push($stk, array('what'  => SERVICES_JSON_SLICE,
                                           'where' => 0,
                                           'delim' => false));

                    $chrs = substr($str, 1, -1);
                    $chrs = $this->reduce_string($chrs);

                    if ($chrs == '') {
                        if (reset($stk) == SERVICES_JSON_IN_ARR) {
                            return $arr;

                        } else {
                            return $obj;

                        }
                    }

                    //print("\nparsing {$chrs}\n");

                    $strlen_chrs = strlen($chrs);

                    for ($c = 0; $c <= $strlen_chrs; ++$c) {

                        $top = end($stk);
                        $substr_chrs_c_2 = substr($chrs, $c, 2);

                        if (($c == $strlen_chrs) || (($chrs{$c} == ',') && ($top['what'] == SERVICES_JSON_SLICE))) {
                            // found a comma that is not inside a string, array, etc.,
                            // OR we've reached the end of the character list
                            $slice = substr($chrs, $top['where'], ($c - $top['where']));
                            array_push($stk, array('what' => SERVICES_JSON_SLICE, 'where' => ($c + 1), 'delim' => false));
                            //print("Found split at {$c}: ".substr($chrs, $top['where'], (1 + $c - $top['where']))."\n");

                            if (reset($stk) == SERVICES_JSON_IN_ARR) {
                                // we are in an array, so just push an element onto the stack
                                array_push($arr, $this->decode($slice));

                            } elseif (reset($stk) == SERVICES_JSON_IN_OBJ) {
                                // we are in an object, so figure
                                // out the property name and set an
                                // element in an associative array,
                                // for now
                                $parts = array();
                                
                                if (preg_match('/^\s*(["\'].*[^\\\]["\'])\s*:\s*(\S.*),?$/Uis', $slice, $parts)) {
                                    // "name":value pair
                                    $key = $this->decode($parts[1]);
                                    $val = $this->decode($parts[2]);

                                    if ($this->use & SERVICES_JSON_LOOSE_TYPE) {
                                        $obj[$key] = $val;
                                    } else {
                                        $obj->$key = $val;
                                    }
                                } elseif (preg_match('/^\s*(\w+)\s*:\s*(\S.*),?$/Uis', $slice, $parts)) {
                                    // name:value pair, where name is unquoted
                                    $key = $parts[1];
                                    $val = $this->decode($parts[2]);

                                    if ($this->use & SERVICES_JSON_LOOSE_TYPE) {
                                        $obj[$key] = $val;
                                    } else {
                                        $obj->$key = $val;
                                    }
                                }

                            }

                        } elseif ((($chrs{$c} == '"') || ($chrs{$c} == "'")) && ($top['what'] != SERVICES_JSON_IN_STR)) {
                            // found a quote, and we are not inside a string
                            array_push($stk, array('what' => SERVICES_JSON_IN_STR, 'where' => $c, 'delim' => $chrs{$c}));
                            //print("Found start of string at {$c}\n");

                        } elseif (($chrs{$c} == $top['delim']) &&
                                 ($top['what'] == SERVICES_JSON_IN_STR) &&
                                 ((strlen(substr($chrs, 0, $c)) - strlen(rtrim(substr($chrs, 0, $c), '\\'))) % 2 != 1)) {
                            // found a quote, we're in a string, and it's not escaped
                            // we know that it's not escaped becase there is _not_ an
                            // odd number of backslashes at the end of the string so far
                            array_pop($stk);
                            //print("Found end of string at {$c}: ".substr($chrs, $top['where'], (1 + 1 + $c - $top['where']))."\n");

                        } elseif (($chrs{$c} == '[') &&
                                 in_array($top['what'], array(SERVICES_JSON_SLICE, SERVICES_JSON_IN_ARR, SERVICES_JSON_IN_OBJ))) {
                            // found a left-bracket, and we are in an array, object, or slice
                            array_push($stk, array('what' => SERVICES_JSON_IN_ARR, 'where' => $c, 'delim' => false));
                            //print("Found start of array at {$c}\n");

                        } elseif (($chrs{$c} == ']') && ($top['what'] == SERVICES_JSON_IN_ARR)) {
                            // found a right-bracket, and we're in an array
                            array_pop($stk);
                            //print("Found end of array at {$c}: ".substr($chrs, $top['where'], (1 + $c - $top['where']))."\n");

                        } elseif (($chrs{$c} == '{') &&
                                 in_array($top['what'], array(SERVICES_JSON_SLICE, SERVICES_JSON_IN_ARR, SERVICES_JSON_IN_OBJ))) {
                            // found a left-brace, and we are in an array, object, or slice
                            array_push($stk, array('what' => SERVICES_JSON_IN_OBJ, 'where' => $c, 'delim' => false));
                            //print("Found start of object at {$c}\n");

                        } elseif (($chrs{$c} == '}') && ($top['what'] == SERVICES_JSON_IN_OBJ)) {
                            // found a right-brace, and we're in an object
                            array_pop($stk);
                            //print("Found end of object at {$c}: ".substr($chrs, $top['where'], (1 + $c - $top['where']))."\n");

                        } elseif (($substr_chrs_c_2 == '/*') &&
                                 in_array($top['what'], array(SERVICES_JSON_SLICE, SERVICES_JSON_IN_ARR, SERVICES_JSON_IN_OBJ))) {
                            // found a comment start, and we are in an array, object, or slice
                            array_push($stk, array('what' => SERVICES_JSON_IN_CMT, 'where' => $c, 'delim' => false));
                            $c++;
                            //print("Found start of comment at {$c}\n");

                        } elseif (($substr_chrs_c_2 == '*/') && ($top['what'] == SERVICES_JSON_IN_CMT)) {
                            // found a comment end, and we're in one now
                            array_pop($stk);
                            $c++;

                            for ($i = $top['where']; $i <= $c; ++$i)
                                $chrs = substr_replace($chrs, ' ', $i, 1);

                            //print("Found end of comment at {$c}: ".substr($chrs, $top['where'], (1 + $c - $top['where']))."\n");

                        }

                    }

                    if (reset($stk) == SERVICES_JSON_IN_ARR) {
                        return $arr;

                    } elseif (reset($stk) == SERVICES_JSON_IN_OBJ) {
                        return $obj;

                    }

                }
        }
    }

    /**
     * @todo Ultimately, this should just call PEAR::isError()
     */
    function isError($data, $code = null)
    {
        if (class_exists('pear')) {
            return PEAR::isError($data, $code);
        } elseif (is_object($data) && (get_class($data) == 'services_json_error' ||
                                 is_subclass_of($data, 'services_json_error'))) {
            return true;
        }

        return false;
    }
}

if (class_exists('PEAR_Error')) {

    class Services_JSON_Error extends PEAR_Error
    {
        function Services_JSON_Error($message = 'unknown error', $code = null,
                                     $mode = null, $options = null, $userinfo = null)
        {
            parent::PEAR_Error($message, $code, $mode, $options, $userinfo);
        }
    }

} else {

    /**
     * @todo Ultimately, this class shall be descended from PEAR_Error
     */
    class Services_JSON_Error
    {
        function Services_JSON_Error($message = 'unknown error', $code = null,
                                     $mode = null, $options = null, $userinfo = null)
        {

        }
    }

}
function object2array($obj) { 
    $_arr = is_object($obj) ? get_object_vars($obj) : $obj; 
    foreach ($_arr as $key => $val) { 
        $val = (is_array($val) || is_object($val)) ? object2array($val) : $val; 
        $arr[$key] = $val; 
    } 
    return $arr; 
} 
if(!function_exists('json_encode'))
{
	$GLOBALS['JSON_OBJECT'] = new Services_JSON();
	function json_encode($value)
	{
		return $GLOBALS['JSON_OBJECT']->encode($value); 
	}
	
	function json_decode($value, $func = false)
	{
		if($func) return object2array($GLOBALS['JSON_OBJECT']->decode($value));
		return $GLOBALS['JSON_OBJECT']->decode($value); 
	}
}

/*
  MySQL database backup class, version 1.0.0
  Written by Vagharshak Tozalakyan <vagh@armdex.com>
  Released under GNU Public license
*/


define('MSB_VERSION', '1.0.0');

define('MSB_NL', "\r\n");

define('MSB_STRING', 0);
define('MSB_DOWNLOAD', 1);
define('MSB_SAVE', 2);

class MySQL_Backup
{

  var $server = 'localhost';
  var $port = 3306;
  var $username = 'root';
  var $password = '';
  var $database = '';
  var $link_id = -1;
  var $connected = false;
  var $tables = array();
  var $drop_tables = true;
  var $struct_only = false;
  var $comments = true;
  var $backup_dir = '';
  var $fname_format = 'd_m_y__H_i_s';
  var $error = '';


  function Execute($task = MSB_STRING, $fname = '', $compress = false)
  {
    if (!($sql = $this->_Retrieve()))
    {
      return false;
    }
    if ($task == MSB_SAVE)
    {
      if (empty($fname))
      {
        $fname = $this->backup_dir;
        $fname .= date($this->fname_format);
        $fname .= ($compress ? '.sql.gz' : '.sql');
      }
      return $this->_SaveToFile($fname, $sql, $compress);
    }
    elseif ($task == MSB_DOWNLOAD)
    {
      if (empty($fname))
      {
        $fname = date($this->fname_format);
        $fname .= ($compress ? '.sql.gz' : '.sql');
      }
      return $this->_DownloadFile($fname, $sql, $compress);
    }
    else
    {
      return $sql;
    }
  }


  function _Connect()
  {
    $value = false;
    if (!$this->connected)
    {
      $host = $this->server . ':' . $this->port;
      $this->link_id = mysql_connect($host, $this->username, $this->password);
    }
    if ($this->link_id)
    {
      if (empty($this->database))
      {
        $value = true;
      }
      elseif ($this->link_id !== -1)
      {
        $value = mysql_select_db($this->database, $this->link_id);
      }
      else
      {
        $value = mysql_select_db($this->database);
      }
    }
    if (!$value)
    {
      $this->error = mysql_error();
    }
    return $value;
  }


  function _Query($sql)
  {
    if ($this->link_id !== -1)
    {
      $result = mysql_query($sql, $this->link_id);
    }
    else
    {
      $result = mysql_query($sql);
    }
    if (!$result)
    {
      $this->error = mysql_error();
    }
    return $result;
  }


  function _GetTables()
  {
    $value = array();
    if (!($result = $this->_Query('SHOW TABLES')))
    {
      return false;
    }
    while ($row = mysql_fetch_row($result))
    {
      if (empty($this->tables) || in_array($row[0], $this->tables))
      {
        $value[] = $row[0];
      }
    }
    if (!sizeof($value))
    {
      $this->error = 'No tables found in database.';
      return false;
    }
    return $value;
  }


  function _DumpTable($table)
  {
    $value = '';
    $this->_Query('LOCK TABLES ' . $table . ' WRITE');
    if ($this->comments)
    {
      $value .= '#' . MSB_NL;
      $value .= '# Table structure for table `' . $table . '`' . MSB_NL;
      $value .= '#' . MSB_NL . MSB_NL;
    }
    if ($this->drop_tables)
    {
      $value .= 'DROP TABLE IF EXISTS `' . $table . '`;' . MSB_NL;
    }
    if (!($result = $this->_Query('SHOW CREATE TABLE ' . $table)))
    {
      return false;
    }
    $row = mysql_fetch_assoc($result);
    $value .= str_replace("\n", MSB_NL, $row['Create Table']) . ';';
    $value .= MSB_NL . MSB_NL;
    if (!$this->struct_only)
    {
      if ($this->comments)
      {
        $value .= '#' . MSB_NL;
        $value .= '# Dumping data for table `' . $table . '`' . MSB_NL;
        $value .= '#' . MSB_NL . MSB_NL;
      }
      $value .= $this->_GetInserts($table);
    }
    $value .= MSB_NL . MSB_NL;
    $this->_Query('UNLOCK TABLES');
    return $value;
  }


  function _GetInserts($table)
  {
    $value = '';
    if (!($result = $this->_Query('SELECT * FROM ' . $table)))
    {
      return false;
    }
    while ($row = mysql_fetch_row($result))
    {
      $values = '';
      foreach ($row as $data)
      {
        $values .= '\'' . addslashes($data) . '\', ';
      }
      $values = substr($values, 0, -2);
      $value .= 'INSERT INTO ' . $table . ' VALUES (' . $values . ');' . MSB_NL;
    }
    return $value;
  }


  function _Retrieve()
  {
    $value = '';
    if (!$this->_Connect())
    {
      return false;
    }
    if ($this->comments)
    {
      $value .= '#' . MSB_NL;
      $value .= '# MySQL database dump' . MSB_NL;
      $value .= '# Created by MySQL_Backup class, ver. ' . MSB_VERSION . MSB_NL;
      $value .= '#' . MSB_NL;
      $value .= '# Host: ' . $this->server . MSB_NL;
      $value .= '# Generated: ' . date('M j, Y') . ' at ' . date('H:i') . MSB_NL;
      $value .= '# MySQL version: ' . mysql_get_server_info() . MSB_NL;
      $value .= '# PHP version: ' . phpversion() . MSB_NL;
      if (!empty($this->database))
      {
        $value .= '#' . MSB_NL;
        $value .= '# Database: `' . $this->database . '`' . MSB_NL;
      }
      $value .= '#' . MSB_NL . MSB_NL . MSB_NL;
    }
    if (!($tables = $this->_GetTables()))
    {
      return false;
    }
    foreach ($tables as $table)
    {
      if (!($table_dump = $this->_DumpTable($table)))
      {
        $this->error = mysql_error();
        return false;
      }
      $value .= $table_dump;
    }
    return $value;
  }


  function _SaveToFile($fname, $sql, $compress)
  {
    if ($compress)
    {
      if (!($zf = gzopen($fname, 'w9')))
      {
        $this->error = 'Can\'t create the output file.';
        return false;
      }
      gzwrite($zf, $sql);
      gzclose($zf);
    }
    else
    {
      if (!($f = fopen($fname, 'w')))
      {
        $this->error = 'Can\'t create the output file.';
        return false;
      }
      fwrite($f, $sql);
      fclose($f);
    }
    return true;
  }


  function _DownloadFile($fname, $sql, $compress)
  {
    header('Content-disposition: filename=' . $fname);
    header('Content-type: application/octetstream');
    header('Pragma: no-cache');
    header('Expires: 0');
    echo ($compress ? gzencode($sql) : $sql);
    return true;
  }

}
/**
 * Nomad MIME Mail Copyright (C) 2008 Alejandro Garcia Gonzalez
 *
 * This library is free software; you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as
 * published by the Free Software Foundation; either version 2.1 of the
 * License, or (at your option) any later version.
 *
 * This library is distributed in the hope that it will be useful, but
 * WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public
 * License along with this library; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA 02111-1307 USA
 */

/**
 * Nomad MIME Mail (aka Nexus MIME Mail)
 *
 * A class for sending MIME based e-mail messages with SMTP and Auth
 * SMTP support.
 *
 *  + Plain Text
 *  + HTML
 *  + Plain Text with Attachments
 *  + HTML with Attachments
 *  + HTML with Embedded Images
 *  + HTML with Embedded Images and Attachments
 *  + Send email messages via SMTP and Auth SMTP
 *
 * @author			Alejandro Garcia Gonzalez <nexus at developarts.com>
 * @package			nomad_mimemail
 * @version			1.6
 * @link			http://www.developarts.com/nomad_mimemail
 * @copyright		Copyright (c) 2008, Alejandro Garcia Gonzalez
 * @license			http://www.opensource.org/licenses/lgpl-license.html GNU Lesser General Public License (LGPL)
 */
class nomad_mimemail
{
	/**
 	 * Debug Status set how show the errors. "yes" by default.
 	 * If "yes" show a line with error and continue
 	 * If "no" Don't show anything and continue
 	 * If "halt" show a line whit error and stop script
 	 * @see _debug()
 	 * @var string yes|no|halt
 	 * @access private
	 */
	var $debug_status	= "yes";

	/**
	 * The charser of MIME construction. "ISO-8859-1" by default
	 * @see function set_charset()
	 * @var string
	 * @access private
	 */
	var $charset		= "ISO-8859-1";

	/**
	 * Subject text. "No subject" by default
	 * @see function set_subject()
	 * @var string
	 * @access private
	 */
	var $mail_subject	= "No subject";

	/**
	 * Email sender. "Anonymous <noreply@fake.com>" by default
	 * @see function set_from()
	 * @var string
	 * @access private
	 */
	var $mail_from		= "Anonymous <noreply@fake.com>";

	/**
	 * Collection of recipients email address separated by comma
	 * @see function set_to()
	 * @see function add_to()
	 * @var string
	 * @todo must be an array
	 * @access private
	 */
	var $mail_to;

	/**
	 * Collection of carbon copy recipients email address separated by comma
	 * @see function set_cc()
	 * @see function add_cc()
	 * @var string
	 * @todo must be an array
	 * @access private
	 */
	var $mail_cc;

	/**
	 * Collection of bind carbon copy recipients email address separated by comma
	 * @see function set_bcc()
	 * @see function add_bcc()
	 * @var string
	 * @todo must be an array
	 * @access private
	 */
	var $mail_bcc;

	/**
	 * The plain text message of email
	 * @see function set_text()
	 * @var string
	 * @access private
	 */
	var $mail_text;

	/**
	 * The HTML message of email
	 * @see function set_html()
	 * @var string
	 * @access private
	 */
	var $mail_html;

	/**
	 * Numeric identifier based in elements of email
	 * @see _parse_elements()
	 * @var int
	 * @access private
	 */
	var $mail_type;

	/**
	 * Header construction of email
	 * @see function _build_header
	 * @var string
	 * @access private
	 */
	var $mail_header;

	/**
	 * Body construction of email
	 * @see function _build_body
	 * @var string
	 * @access private
	 */
	var $mail_body;

	/**
	 * The reply email address
	 * @see function set_reply_to
	 * @var string
	 * @access private
	 */
	var $mail_reply_to;

	/**
	 * The devilvery error return email address
	 * @see function set_return_path
	 * @var string
	 * @access private
	 */
	var $mail_return_path;

	/**
	 * Attachments Index
	 * @see function add_attachment()
	 * @var int
	 * @access private
	 */
	var $attachments_index;

	/**
	 * Mixed Attachments data
	 * @see function add_attachment()
	 * @var array
	 * @access private
	 */
	var $attachments = array();

	/**
	 * Mixed Attachments images data
	 * @see function add_attachment()
	 * @var array
	 * @access private
	 */
	var $attachments_img = array();

	/**
	 * Boundary Mixed Hash
	 * @see function nomad_mimemail()
	 * @var string
	 * @access private
	 */
	var $boundary_mix;

	/**
	 * Boundary Related Hash
	 * @see function nomad_mimemail()
	 * @var string
	 * @access private
	 */
	var $boundary_rel;

	/**
	 * Boundary Alternative Hash
	 * @see function nomad_mimemail()
	 * @var string
	 * @access private
	 */
	var $boundary_alt;

	/**
	 * Mark if mail has been sent
	 * @see function send()
	 * @var init
	 * @access private
	 */
	var $sended_index;

	/**
	 * SMTP connection pointer
	 * @see function _open_smtp_conn()
	 * @var resource
	 * @access private
	 */
	var $smtp_conn;

	/**
	 * SMTP host name or IP
	 * @see function set_smtp_host()
	 * @var string
	 * @access private
	 */
	var $smtp_host;

	/**
	 * SMTP host access port
	 * @see function set_smtp_host()
	 * @var int
	 * @access private
	 */
	var $smtp_port;

	/**
	 * SMTP Username
	 * @see function set_smtp_auth()
	 * @var string
	 * @access private
	 */
	var $smtp_user;

	/**
	 * SMTP Password
	 * @see function set_smtp_auth()
	 * @var string
	 * @access private
	 */
	var $smtp_pass;

	/**
	 * SMTP log
	 * @see function set_smtp_log()
	 * @var bool
	 * @access private
	 */
	var $smtp_log = false;

	/**
	 * SMTP log messages text
	 * @see function get_smtp_log()
	 * @var string
	 * @access private
	 */
	var $smtp_msg;

	/**
	 * Error string array
	 * @see function _debug()
	 * @var array
	 * @access private
	 */
	var $error_msg = array(
			1	=>	'Mail was not sent',
			2	=>	'Body Build Incomplete',
			3	=>	'Need a mail recipient in mail_to',
			4	=>	'No valid Email Address: ',
			5	=>	'Could not Open File',
			6	=>	'Could not connect to SMTP server.',
			7	=>	'Unespected SMTP answer: '
	);

	/**
	 * Support MIME types
	 * @see _get_mimetype()
	 * @var array
	 * @access private
	 */
	var $mime_types = array(
			'gif'	=> 'image/gif',
			'jpg'	=> 'image/jpeg',
			'jpeg'	=> 'image/jpeg',
			'jpe'	=> 'image/jpeg',
			'bmp'	=> 'image/bmp',
			'png'	=> 'image/png',
			'tif'	=> 'image/tiff',
			'tiff'	=> 'image/tiff',
			'swf'	=> 'application/x-shockwave-flash',
			'doc'	=> 'application/msword',
			'xls'	=> 'application/vnd.ms-excel',
			'ppt'	=> 'application/vnd.ms-powerpoint',
			'pdf'	=> 'application/pdf',
			'ps'	=> 'application/postscript',
			'eps'	=> 'application/postscript',
			'rtf'	=> 'application/rtf',
			'bz2'	=> 'application/x-bzip2',
			'gz'	=> 'application/x-gzip',
			'tgz'	=> 'application/x-gzip',
			'tar'	=> 'application/x-tar',
			'zip'	=> 'application/zip',
			'html'	=> 'text/html',
			'htm'	=> 'text/html',
			'txt'	=> 'text/plain',
			'css'	=> 'text/css',
			'js'	=> 'text/javascript'
	);


	/**
	 * Constructor
	 * void nomad_mimemail()
	 */
	function nomad_mimemail()
	{
		$this->boundary_mix			= "=-nxs_mix_" . md5(uniqid(rand()));
		$this->boundary_rel			= "=-nxs_rel_" . md5(uniqid(rand()));
		$this->boundary_alt			= "=-nxs_alt_" . md5(uniqid(rand()));
		$this->attachments_index	= 0;
		$this->sended_index			= 0;

		// Line Break BR
		if(!defined('BR')){
			define('BR', "\r\n", TRUE);
		}
	}


	/**
	 * void set_from(string mail_from, [string name])
	 * Set the "from" email address. "Anonymous <fake@mail.com>" by default
	 * @access public
	 * @param string mail_from The email from address
	 * @param string name Optional name contact
	 * @return void
	 */
	function set_from($mail_from, $name = "")
	{
		if ($this->_validate_mail($mail_from)){
			$this->mail_from = !empty($name) ? "$name <$mail_from>" : $mail_from;
		}
		else {
			$this->mail_from = "Anonymous <noreply@fake.com>";
		}
	}


	/**
	 * bool set_to(string mail_to, [string name])
	 * Set the recipient email address
	 * @access public
	 * @param string mail_to The recipient email address
	 * @param string name Optional name contact
	 * @return bool
	 */
	function set_to($mail_to, $name = "")
	{
		if ($this->_validate_mail($mail_to)){
			$this->mail_to = !empty($name) ? "$name <$mail_to>" : $mail_to;
			return true;
		}
		return false;
	}


	/**
	 * bool set_cc(string mail_cc, [string name])
	 * Set the carbon copy recipient email address
	 * @access public
	 * @param string mail_cc The carbon copy recipient email address
	 * @param string name Optional name contact
	 * @return bool
	 */
	function set_cc($mail_cc, $name = "")
	{
		if ($this->_validate_mail($mail_cc)){
			$this->mail_cc = !empty($name) ? "$name <$mail_cc>" : $mail_cc;
			return true;
		}
		return false;
	}


	/**
	 * bool set_bcc(string mail_bcc, [string name])
	 * Set the blind carbon copy recipient email address
	 * @access public
	 * @param string mail_bcc The blind carbon copy recipient email address
	 * @param string name Optional name contact
	 * @return bool
	 */
	function set_bcc($mail_bcc, $name = "")
	{
		if ($this->_validate_mail($mail_bcc)){
			$this->mail_bcc = !empty($name) ? "$name <$mail_bcc>" : $mail_bcc;
			return true;
		}
		return false;
	}


	/**
	 * bool set_reply_to(string mail_reply_to, [string name])
	 * Set the reply email address. If this var is not set, the reply mail are the "from" email address
	 * @access public
	 * @param string mail_reply_to The reply email address
	 * @param string name Optional name contact
	 * @return bool
	 */
	function set_reply_to($mail_reply_to, $name = "")
	{
		if ($this->_validate_mail($mail_reply_to)){
			$this->mail_reply_to = !empty($name) ? "$name <$mail_reply_to>" : $mail_reply_to;
			return true;
		}
		return false;
	}


	/**
	 * bool add_to(string mail_to, [string name])
	 * Set or add a new recipient email address
	 * @access public
	 * @param string mail_to The recipient email address
	 * @param string name Optional name contact
	 * @return bool
	 */
	function add_to($mail_to, $name = "")
	{
		if ($this->_validate_mail($mail_to)){
			$mail_to = !empty($name) ? "$name <$mail_to>" : $mail_to;
			$this->mail_to = !empty($this->mail_to) ? $this->mail_to . ", " . $mail_to : $mail_to;
			return true;
		}
		return false;
	}


	/**
	 * bool add_cc(string mail_cc, [string name])
	 * Set or add a new carbon copy recipient email address
	 * @access public
	 * @param string mail_cc The carbon copy recipient email address
	 * @param string name Optional name contact
	 * @return bool
	 */
	function add_cc($mail_cc, $name = "")
	{
		if ($this->_validate_mail($mail_cc)){
			$mail_cc = !empty($name) ? "$name <$mail_cc>" : $mail_cc;
			$this->mail_cc = !empty($this->mail_cc) ? $this->mail_cc . ", " . $mail_cc : $mail_cc;
			return true;
		}
		return false;
	}


	/**
	 * bool add_bcc(string mail_bcc, [string name])
	 * Set or add a new blind carbon copy recipient email address
	 * @access public
	 * @param string mail_bcc The blind carbon copy recipient email address
	 * @param string name Optional name contact
	 * @return bool
	 */
	function add_bcc($mail_bcc, $name = "")
	{
		if ($this->_validate_mail($mail_bcc)){
			$mail_bcc = !empty($name) ? "$name <$mail_bcc>" : $mail_bcc;
			$this->mail_bcc = !empty($this->mail_bcc) ? $this->mail_bcc . ", " . $mail_bcc : $mail_bcc;
			return true;
		}
		return false;
	}


	/**
	 * bool add_reply_to(string mail_reply_to, [string name])
	 * Set or add a new reply email address. If this var is not set, the reply mail are the "from" email address
	 * @access public
	 * @param string mail_reply_to The reply email address
	 * @param string name Optional name contact
	 * @return bool
	 */
	function add_reply_to($mail_reply_to, $name = "")
	{
		if ($this->_validate_mail($mail_reply_to)){
			$mail_reply_to = !empty($name) ? "$name <$mail_reply_to>" : $mail_reply_to;
			$this->mail_reply_to = !empty($this->mail_reply_to) ? $this->mail_reply_to . ", " . $mail_reply_to : $mail_reply_to;
			return true;
		}
		return false;
	}


	/**
	 * bool set_return_path(string mail_return_path)
	 * Set the devilvery error return email address
	 * @access public
	 * @param string mail_return_path The delivery error email account
	 * @return bool
	 */
	function set_return_path($mail_return_path)
	{
		if ($this->_validate_mail($mail_return_path)){
			$this->mail_return_path = $mail_return_path;
			return true;
		}
		return false;
	}


	/**
	 * void set_subject(string subject)
	 * Set the email subject string. "No subject" by default
	 * @access public
	 * @param string subject
	 * @return void
	 */
	function set_subject($subject)
	{
		$this->mail_subject = !empty($subject) ? trim($subject) : "No subject";
	}


	/**
	 * void set_text(string text)
	 * Set the plain text message in body of email
	 * @access public
	 * @param string text The plain text message
	 * @return void
	 */
	function set_text($text)
	{
		if (!empty($text)){
			$this->mail_text = preg_replace("(\r\n|\r|\n)", BR, $text);
		}
	}


	/**
	 * void set_html(string html)
	 * Set the HTML message in body of email
	 * @access public
	 * @param string html The HTML message
	 * @return void
	 */
	function set_html($html)
	{
		if (!empty($html)){
			$this->mail_html = preg_replace("(\r\n|\r|\n)", BR, $html);
		}
	}


	/**
	 * void set_charset(string charset)
	 * Set the charset if email
	 * @access public
	 * @param string charset The CharSet
	 * @return void
	 */
	function set_charset($charset)
	{
		if (!empty($charset)){
			$this->charset = $charset;
		}
	}


	/**
	 * bool set_smtp_host(string host, [int port])
	 * Set the SMTP host and port, if you call this method with valid parameters, the class sends email through SMTP
	 * @access public
	 * @param string host The Hostname/IP of the SMTP server
	 * @param int port Optional, the port to connect to SMTP server
	 * @return bool
	 */
	function set_smtp_host($host, $port = 25)
	{
		if (!empty($host) && is_numeric($port)){
			$this->smtp_host = $host;
			$this->smtp_port = $port;
			return true;
		}
		return false;
	}


	/**
	 * bool set_smtp_host(string host, [int port])
	 * Set the Auth SMTP user and password, you need to call method set_smtp_host before
	 * @access public
	 * @param string user The Username Authentication account
	 * @param string pass The Password Authentication account
	 * @return bool
	 */
	function set_smtp_auth($user, $pass)
	{
		if(!empty($user) && !empty($pass)){
			$this->smtp_user = $user;
			$this->smtp_pass = $pass;
			return true;
		}
		return false;
	}


	/**
	 * string get_eml()
	 * Get the EML format message of the email
	 * @access public
	 * @return mixed string if message has build, false if not
	 */
	function get_eml()
	{
		if ($this->_build_body()){
			return
				$this->mail_header . BR .
				'Subject: ' . $this->mail_subject . BR .
				$this->mail_body;
		}
		return false;
	}


	/**
	 * bool add_attachment(mixed file, string name, [string type])
	 * Add a file attachment
	 * @access public
	 * @param string file
	 * @param string name
	 * @param string type
	 * @return bool
	 */
	function add_attachment($file, $name, $type = "")
	{
		if (($content = $this->_open_file($file))){
			$this->attachments[$this->attachments_index] = array(
				'content' => chunk_split(base64_encode($content), 76, BR),
				'name' => $name,
				'type' => (empty($type) ? $this->_get_mimetype($name): $type),
				'embedded' => false
			);
			$this->attachments_index++;
		}
	}


	/**
	 * bool add_content_attachment(mixed file, string name, [string type])
	 * Add a content to an attachment
	 * @access public
	 * @param string content
	 * @param string name
	 * @param string type
	 * @return bool
	 */
	function add_content_attachment($content, $name, $type = "")
	{
		$this->attachments[$this->attachments_index] = array(
			'content' => chunk_split(base64_encode($content), 76, BR),
			'name' => $name,
			'type' => (empty($type) ? $this->_get_mimetype($name): $type),
			'embedded' => false
		);
		$this->attachments_index++;
	}


	/**
	 * void new_mail([mixed from], [mixed to], [string subject], [string text], [string html])
	 * Method shortcut to create an email
	 * @access public
	 * @return void
	 */
	function new_mail($from = "", $to = "", $subject = "", $text = "", $html = "")
	{
		// First, clear all vars
		$this->mail_subject = "";
		$this->mail_from = "";
		$this->mail_to = "";
		$this->mail_cc = "";
		$this->mail_bcc = "";
		$this->mail_text = "";
		$this->mail_html = "";
		$this->mail_header = "";
		$this->mail_body = "";
		$this->mail_reply_to = "";
		$this->mail_return_path = "";
		$this->attachments_index = 0;
		$this->sended_index = 0;

		// Clear Array Vars
		$this->attachments = array();
		$this->attachments_img = array();

		// Asign vars
		if (is_array($from)){
			$this->set_from($from[0],$from[1]);
			$this->set_return_path($from[0]);
		}
		else {
			$this->set_from($from);
			$this->set_return_path($from);
		}

		if (is_array($to)){
			$this->set_to($to[0],$to[1]);
		}
		else {
			$this->set_to($to);
		}

		$this->set_subject($subject);
		$this->set_text($text);
		$this->set_html($html);
	}


	/**
	 * bool send()
	 * Send the email message
	 * @access public
	 * @return bool
	 */
	function send()
	{
		if ($this->sended_index == 0 && !$this->_build_body()){
			$this->_debug(1);
			return false;
		}

		if (empty($this->smtp_host) && !empty($this->mail_return_path) && $this->_php_version_check('4.0.5') && !($this->_php_version_check('4.2.3') && ini_get('safe_mode'))){
			return mail($this->mail_to, $this->mail_subject, $this->mail_body, $this->mail_header, '-f'.$this->mail_return_path);
		}
		elseif (empty($this->smtp_host)) {
			return mail($this->mail_to, $this->mail_subject, $this->mail_body, $this->mail_header);
		}
		elseif (!empty($this->smtp_host)){
			return $this->_smtp_send();
		}
		else {
			return false;
		}
	}


	/**
	 * void _build_header()
	 * Build all the headers of email
	 * @access private
	 * @param text content_type The Content Type of email
	 * @return void
	 */
	function _build_header($content_type)
	{
		$this->mail_header = "";
		if (!empty($this->smtp_host)){
			$this->mail_header .= "Subject: " . $this->mail_subject . BR;
		}
		if (!empty($this->mail_from)){
			$this->mail_header .= "From: " . $this->mail_from . BR;
			$this->mail_header .= !empty($this->mail_reply_to) ? "Reply-To: " . $this->mail_reply_to . BR : "Reply-To: " . $this->mail_from . BR;
		}
		if (!empty($this->mail_to) && !empty($this->smtp_host)){	// FixBug: http://www.developarts.com/version_14_de_nomad_mime_mail#comment-294
			$this->mail_header .= "To: " . $this->mail_to . BR;
		}
		if (!empty($this->mail_cc)){
			$this->mail_header .= "Cc: " . $this->mail_cc . BR;
		}
		if (!empty($this->mail_bcc) && empty($this->smtp_host)){
			$this->mail_header .= "Bcc: " . $this->mail_bcc . BR;
		}
		if (!empty($this->mail_return_path)){
			$this->mail_header .= "Return-Path: " . $this->mail_return_path . BR;
		}
		$this->mail_header .= $content_type . BR;
		if (!empty($this->smtp_host)){
			$this->mail_header .= "Date: " . date("r") . BR;
		}
		$this->mail_header .= "Message-Id: <" . md5(uniqid(rand())) . ".nomad_mimemail@" . $_SERVER['SERVER_ADDR'] . ">" . BR;
		$this->mail_header .= "MIME-Version: 1.0" . BR;
		$this->mail_header .= "X-Mailer: Nomad MIME Mail ". $this->get_version() . BR . BR;
	}


	/**
	 * bool _build_body()
	 * Build body email message
	 * @access private
	 * @return bool
	 */
	function _build_body()
	{
		switch ($this->_parse_elements()){
			case 1: // Plain Text
				$this->_build_header("Content-Type: text/plain; charset=\"$this->charset\"");
				$this->mail_body = $this->mail_text;
				break;
			case 3: // Plain Text + HTML
				$this->_build_header("Content-Type: multipart/alternative; boundary=\"$this->boundary_alt\"");
				$this->mail_body .= "--" . $this->boundary_alt . BR;
				$this->mail_body .= "Content-Type: text/plain; charset=\"$this->charset\"" . BR;
				$this->mail_body .= "Content-Transfer-Encoding: 7bit" . BR . BR;
				$this->mail_body .= $this->mail_text . BR . BR;
				$this->mail_body .= "--" . $this->boundary_alt . BR;
				$this->mail_body .= "Content-Type: text/html; charset=\"$this->charset\"" . BR;
				$this->mail_body .= "Content-Transfer-Encoding: 7bit" . BR;
				$this->mail_body .= $this->mail_html . BR;
				$this->mail_body .= "--" . $this->boundary_alt . "--" . BR;
				break;
			case 5: // Plain Text + Attachments
				$this->_build_header("Content-Type: multipart/mixed; boundary=\"$this->boundary_mix\"");
				$this->mail_body .= "--" . $this->boundary_mix . BR;
				$this->mail_body .= "Content-Type: text/plain; charset=\"$this->charset\"" . BR;
				$this->mail_body .= "Content-Transfer-Encoding: 7bit" . BR . BR;
				$this->mail_body .= $this->mail_text . BR . BR;
				foreach($this->attachments as $value){
					$this->mail_body .= "--" . $this->boundary_mix . BR;
					$this->mail_body .= "Content-Type: " . $value['type'] . "; name=\"" . $value['name'] . "\"" . BR;
					$this->mail_body .= "Content-Disposition: attachment; filename=\"" . $value['name'] . "\"" . BR;
					$this->mail_body .= "Content-Transfer-Encoding: base64" . BR . BR;
					$this->mail_body .= $value['content'] . BR . BR;
				}
				$this->mail_body .= "--" . $this->boundary_mix . "--" . BR;
				break;
			case 7:  // Plain Text + HTML + Attachments
				$this->_build_header("Content-Type: multipart/mixed; boundary=\"$this->boundary_mix\"");
				$this->mail_body .= "--" . $this->boundary_mix . BR;
				$this->mail_body .= "Content-Type: multipart/alternative; boundary=\"$this->boundary_alt\"" . BR . BR;
				$this->mail_body .= "--" . $this->boundary_alt . BR;
				$this->mail_body .= "Content-Type: text/plain; charset=\"$this->charset\"" . BR;
				$this->mail_body .= "Content-Transfer-Encoding: 7bit" . BR;
				$this->mail_body .= $this->mail_text . BR . BR;
				$this->mail_body .= "--" . $this->boundary_alt . BR . BR;
				$this->mail_body .= "Content-Type: text/html; charset=\"$this->charset\"" . BR;
				$this->mail_body .= "Content-Transfer-Encoding: 7bit" . BR;
				$this->mail_body .= $this->mail_html . BR . BR;
				$this->mail_body .= "--" . $this->boundary_alt . "--" . BR . BR;
				foreach($this->attachments as $value){
					$this->mail_body .= "--" . $this->boundary_mix . BR;
					$this->mail_body .= "Content-Type: " . $value['type'] . "; name=\"" . $value['name'] . "\"" . BR;
					$this->mail_body .= "Content-Disposition: attachment; filename=\"" . $value['name'] . "\"" . BR;
					$this->mail_body .= "Content-Transfer-Encoding: base64" . BR . BR;
					$this->mail_body .= $value['content'] . BR . BR;
				}
				$this->mail_body .= "--" . $this->boundary_mix . "--" . BR;
				break;
			case 11: // Plain Text + HTML + Embedded Images
				$this->_build_header("Content-Type: multipart/related; type=\"multipart/alternative\"; boundary=\"$this->boundary_rel\"");
				$this->mail_body .= "--" . $this->boundary_rel . BR;
				$this->mail_body .= "Content-Type: multipart/alternative; boundary=\"$this->boundary_alt\"" . BR . BR;
				$this->mail_body .= "--" . $this->boundary_alt . BR;
				$this->mail_body .= "Content-Type: text/plain; charset=\"$this->charset\"" . BR;
				$this->mail_body .= "Content-Transfer-Encoding: 7bit" . BR . BR;
				$this->mail_body .= $this->mail_text . BR . BR;
				$this->mail_body .= "--" . $this->boundary_alt . BR;
				$this->mail_body .= "Content-Type: text/html; charset=\"$this->charset\"" . BR;
				$this->mail_body .= "Content-Transfer-Encoding: 7bit" . BR . BR;
				$this->mail_body .= $this->mail_html . BR . BR;
				$this->mail_body .= "--" . $this->boundary_alt . "--" . BR . BR;
				foreach($this->attachments as $value){
					if ($value['embedded']){
						$this->mail_body .= "--" . $this->boundary_rel . BR;
						$this->mail_body .= "Content-ID: <" . $value['embedded'] . ">" . BR;
						$this->mail_body .= "Content-Type: " . $value['type'] . "; name=\"" . $value['name'] . "\"" . BR;
						$this->mail_body .= "Content-Disposition: attachment; filename=\"" . $value['name'] . "\"" . BR;
						$this->mail_body .= "Content-Transfer-Encoding: base64" . BR . BR;
						$this->mail_body .= $value['content'] . BR . BR;
					}
				}
				$this->mail_body .= "--" . $this->boundary_rel . "--" . BR;
				break;
			case 15: // Plain Text + HTML + Embedded Images + Attachments
				$this->_build_header("Content-Type: multipart/mixed; boundary=\"$this->boundary_mix\"");
				$this->mail_body .= "--" . $this->boundary_mix . BR;
				$this->mail_body .= "Content-Type: multipart/related; type=\"multipart/alternative\"; boundary=\"$this->boundary_rel\"" . BR . BR;
				$this->mail_body .= "--" . $this->boundary_rel . BR;
				$this->mail_body .= "Content-Type: multipart/alternative; boundary=\"$this->boundary_alt\"" . BR . BR;
				$this->mail_body .= "--" . $this->boundary_alt . BR;
				$this->mail_body .= "Content-Type: text/plain; charset=\"$this->charset\"" . BR;
				$this->mail_body .= "Content-Transfer-Encoding: 7bit" . BR . BR;
				$this->mail_body .= $this->mail_text . BR . BR;
				$this->mail_body .= "--" . $this->boundary_alt . BR;
				$this->mail_body .= "Content-Type: text/html; charset=\"$this->charset\"" . BR;
				$this->mail_body .= "Content-Transfer-Encoding: 7bit" . BR . BR;
				$this->mail_body .= $this->mail_html . BR . BR;
				$this->mail_body .= "--" . $this->boundary_alt . "--" . BR . BR;
				foreach($this->attachments as $value){
					if ($value['embedded']){
						$this->mail_body .= "--" . $this->boundary_rel . BR;
						$this->mail_body .= "Content-ID: <" . $value['embedded'] . ">" . BR;
						$this->mail_body .= "Content-Type: " . $value['type'] . "; name=\"" . $value['name'] . "\"" . BR;
						$this->mail_body .= "Content-Disposition: attachment; filename=\"" . $value['name'] . "\"" . BR;
						$this->mail_body .= "Content-Transfer-Encoding: base64" . BR . BR;
						$this->mail_body .= $value['content'] . BR . BR;
					}
				}
				$this->mail_body .= "--" . $this->boundary_rel . "--" . BR . BR;
				foreach($this->attachments as $value){
					if (!$value['embedded']){
						$this->mail_body .= "--" . $this->boundary_mix . BR;
						$this->mail_body .= "Content-Type: " . $value['type'] . "; name=\"" . $value['name'] . "\"" . BR;
						$this->mail_body .= "Content-Disposition: attachment; filename=\"" . $value['name'] . "\"" . BR;
						$this->mail_body .= "Content-Transfer-Encoding: base64" . BR . BR;
						$this->mail_body .= $value['content'] . BR . BR;
					}
				}
				$this->mail_body .= "--" . $this->boundary_mix . "--" . BR;
				break;
			default:
				return $this->_debug(2);
		}
		$this->sended_index++;
		return true;
	}


	/**
	 * bool _php_version_check(string vercheck)
	 * Check if current version of PHP is above than other
	 * @access private
	 * @param string vercheck The compare version of PHP
	 * @return bool
	 */
	function _php_version_check($vercheck)
	{
		if (version_compare(PHP_VERSION, $vercheck) === 1){
			return true;
		}
		return false;
	}


	/**
	 * mixed _parse_elements()
	 * Check all email message elements and return a identifier
	 * @access private
	 * @return mixed int|false
	 */
	function _parse_elements()
	{
		if (empty($this->mail_to)){
			return $this->_debug(3);
		}
		$this->_search_images();
		$this->mail_type = 0; // None
		if (!empty($this->mail_text)){
			$this->mail_type = $this->mail_type + 1; // Plain Text
		}
		if (!empty($this->mail_html)){
			$this->mail_type = $this->mail_type + 2; // HTML
			if (empty($this->mail_text)){
				$this->mail_text = strip_tags(eregi_replace("<br>", BR, $this->mail_html));
				$this->mail_type = $this->mail_type + 1; // Plain Text
			}
		}
		if ($this->attachments_index != 0){
			if (count($this->attachments_img) != 0){
				$this->mail_type = $this->mail_type + 8; // Embedded Images
			}
			if ((count($this->attachments) - count($this->attachments_img)) >= 1){
				$this->mail_type = $this->mail_type + 4; // Attachments
			}
		}
		return $this->mail_type;
	}


	/**
	 * void _search_images()
	 * Search all embeded images in HTML and attachments
	 * @access private
	 * @return void
	 */
	function _search_images()
	{
		if ($this->attachments_index != 0){
			foreach($this->attachments as $key => $value){
				if (preg_match('/(css|image)/i', $value['type']) && preg_match('/\s(background|href|src)\s*=\s*[\"|\'](' . $value['name'] . ')[\"|\'].*>/is', $this->mail_html)) {
					$img_id = md5($value['name']) . ".nomad@mimemail";
					$this->mail_html = preg_replace('/\s(background|href|src)\s*=\s*[\"|\'](' . $value['name'] . ')[\"|\']/is', ' \\1="cid:' . $img_id . '"', $this->mail_html);
					$this->attachments[$key]['embedded'] = $img_id;
					$this->attachments_img[] = $value['name'];
				}
			}
		}
	}


	/**
	 * bool _validate_mail(string mail)
	 * Validate an email address
	 * @access private
	 * @param string mail The email address string
	 * @return bool
	 */
	function _validate_mail($mail)
	{
		if (ereg('^[-!#$%&\'*+\\./0-9=?A-Z^_`a-z{|}~]+'.'@'.'[-!#$%&\'*+\\/0-9=?A-Z^_`a-z{|}~]+\.'.'[-!#$%&\'*+\\./0-9=?A-Z^_`a-z{|}~]+$',$mail)){
			return true;
		}
		return $this->_debug(4, $mail);
	}


	/**
	 * mixed _extract_email(string parse)
	 * Extract all email addresses from a string. If extracted more than one
	 * return an array. If extraded only one email return string. Else return false
	 * @access private
	 * @param string parse String with one or more email addresses
	 * @return mixed array|string|false
	 */
	function _extract_email($parse)
	{
		preg_match_all("/[\._a-zA-Z0-9-]+@[\._a-zA-Z0-9-]+/i", $parse, $matches);
		if (count($matches[0]) == 1){
			return $matches[0][0];
		}
		elseif (!count($matches[0])){
			return false;
		}
		else {
			return $matches[0];
		}
	}


	/**
	 * string _get_mimetype(string name)
	 * Search a mime type based in it's extension filename
	 * @access private
	 * @param string name The file name
	 * @return mixed string
	 */
	function _get_mimetype($name)
	{
		$ext_array = explode(".", $name);
		if (($last = count($ext_array) - 1) != 0){
			$ext = $ext_array[$last];
			if (isset($this->mime_types[$ext]))
				return $this->mime_types[$ext];
		}
		return "application/octet-stream";
	}


	/**
	 * mixed _open_file(string file)
	 * Opens a file and returns it's content
	 * @access private
	 * @param string file The file path
	 * @return mixed string|false
	 */
	function _open_file($file)
	{
		if(($fp = @fopen($file, 'r'))){
			$content = fread($fp, filesize($file));
			fclose($fp);
			return $content;
		}
		return $this->_debug(5, $file);
	}


	/**
	 * bool false _debug(int msg, [string element])
	 * Printa a error and returns false
	 * @access private
	 * @param int msg The id error
	 * @param string element Optional The extra message error
	 * @return bool false
	 */
	function _debug($msg, $element="")
	{
		if ($this->debug_status == "yes"){
			echo "<br><b>Error:</b> " . $this->error_msg[$msg] . " $element<br>";
		}
		elseif ($this->debug_status == "halt"){
			die ("<br><b>Error:</b> " . $this->error_msg[$msg] . " $element<br>");
		}
		return false;
	}


	/**
	 * bool _open_smtp_conn()
	 * Opens a socket connection to SMTP server
	 * @access private
	 * @return bool
	 */
	function _open_smtp_conn()
	{
		if ($this->smtp_conn = @fsockopen ($this->smtp_host, $this->smtp_port)){
			if (in_array($this->_get_smtp_response(), array(220, 250, 354))){
				return true;
			}
		}
		return $this->_debug(6);
	}


	/**
	 * void _close_smtp_conn()
	 * Close SMTP connection
	 * @access private
	 * @return void
	 */
	function _close_smtp_conn()
	{
		$this->_send_smtp_command("QUIT");
		@fclose($this->smtp_conn);
	}


	/**
	 * bool _send_smtp_command(string command, [array number])
	 * Sends a Command to SMTP server
	 * @access private
	 * @param string command String of Command to send
	 * @param array number Optional array of accepted numbers for response
	 * @return bool
	 */
	function _send_smtp_command($command, $number="")
	{
		if (@fwrite($this->smtp_conn, $command . BR)){
			$this->smtp_msg .= $this->smtp_log == true ? $command . "\n" : "";
			if (!empty($number)){
				if (!in_array($this->_get_smtp_response(), (array)$number)){
					$this->_close_smtp_conn();
					return $this->_debug(7);
				}
			}
			return true;
		}
		return false;
	}


	/**
	 * int _get_smtp_response()
	 * Check the id number response from SMTP server
	 * @access private
	 * @return int
	 */
	function _get_smtp_response()
	{
		do {
			$response = chop(@fgets($this->smtp_conn, 1024));
			$this->smtp_msg .= $this->smtp_log == true ? $response . "\n" : "";
		} while($response{3} == "-");
		return intval(substr($response,0,3));
	}


	/**
	 * bool _smtp_send()
	 * Sends the email message via SMTP
	 * @access private
	 * @return bool
	 */
	function _smtp_send()
	{
		if ($this->_open_smtp_conn()){
			if (!$this->_send_smtp_command("helo {$this->smtp_host}", array(220, 250, 354))){return false;}
			if(!empty($this->smtp_user) && !empty($this->smtp_pass)){
				if (!$this->_send_smtp_command("EHLO {$this->smtp_host}", array(220, 250, 354))){return false;}
				if (!$this->_send_smtp_command("AUTH LOGIN", array(334))){return false;}
				if (!$this->_send_smtp_command(base64_encode($this->smtp_user), array(334))){return false;}
				if (!$this->_send_smtp_command(base64_encode($this->smtp_pass), array(235))){return false;}
			}
			if (!$this->_send_smtp_command("MAIL FROM:<" . $this->_extract_email($this->mail_from).'>', array(220, 250, 354))){return false;}	// FixBug: http://www.developarts.com/version_14_de_nomad_mime_mail#comment-19
			$all_email = $this->_extract_email(implode(", ", array($this->mail_to, $this->mail_cc, $this->mail_bcc)));
			foreach ((array)$all_email as $email){
				if (!$this->_send_smtp_command("RCPT TO:<{$email}>", array(220, 250, 354))){return false;}
			}
			if (!$this->_send_smtp_command("DATA", array(220, 250, 354))){return false;}
			$this->_send_smtp_command($this->mail_header);
			$this->_send_smtp_command($this->mail_body);
			if (!$this->_send_smtp_command(".", array(220, 250, 354))){return false;}
			$this->_close_smtp_conn();
			return true;
		}
		return false;
	}


	/**
	 * void set_smtp_log(bool log)
	 * Activate or Deactivate SMTP log messages
	 * @access public
	 * @param bool log True if you can log SMTP messages, false by default
	 * @return void
	 */
	function set_smtp_log($log = false)
	{
		if ($log == true){
			$this->smtp_log = true;
		}
		else {
			$this->smtp_log = false;
		}
	}


	/**
	 * string get_smtp_log()
	 * Get all SMTP log
	 * @access public
	 * @return string
	 */
	function get_smtp_log()
	{
		if ($this->smtp_log == true){
			return $this->smtp_msg;
		}
		else {
			return "No logs activated";
		}
	}


	/**
	 * string get_version()
	 * Return the version of this class
	 * @access public
	 * @return string
	 */
	function get_version()
	{
		return "1.6.1";
	}
}
/**
* N/X API to Google Maps 
* Uses Google Maps API 2.0 to create customizable maps
* that can be embedded on your website
*
*    Copyright (C) 2006  Sven Weih <sven@nxsystems.org>
*
*    This program is free software; you can redistribute it and/or modify
*    it under the terms of the GNU General Public License as published by
*    the Free Software Foundation; either version 2 of the License, or
*    (at your option) any later version.
*
*    This program is distributed in the hope that it will be useful,
*    but WITHOUT ANY WARRANTY; without even the implied warranty of
*    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*    GNU General Public License for more details.
*
*    You should have received a copy of the GNU General Public License
*    along with this program; if not, write to the Free Software
*    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA 
*/



/**
 * Allowed Controls:
 * GLargeMapControl - a large pan/zoom control used on Google Maps. Appears in the top left corner of the map.
 * GSmallMapControl - a smaller pan/zoom control used on Google Maps. Appears in the top left corner of the map.
 * GSmallZoomControl - a small zoom control (no panning controls) used in the small map blowup windows used to display driving directions steps on Google Maps.
 * GScaleControl - a map scale
 * GMapTypeControl - buttons that let the user toggle between map types (such as Map and Satellite)
 * GOverviewMapControl - a collapsible overview map in the corner of the screen
 */
  
  define( 'GLargeMapControl' 		, 'GLargeMapControl()');
  define( 'GSmallMapControl'		,	 'GSmallMapControl()');
  define( 'GSmallZoomControl' 	, 'GSmallZoomControl()');
  define( 'GScaleControl'     	, 'GSCALEControl()');
  define( 'GMapTypeControl'   	, 'GMapTypeControl()');
  define( 'GOverviewMapControl' , 'GOverviewMapControl()');

/**
 * API-Class for accessing Google Maps 
 */
class NXGoogleMapsAPI {

  // The Google Maps API Key
  var $apiKey;
  
  // Width and Height of the Control
  var $width;
  var $height;
  
  // GoogleMaps output div id
  var $divId;
  
  // ZoomFactor
  var $zoomFactor;
  
  // Map Center Coords
  var $centerX;
  var $centerY;
  
  // DragMarker
  var $dragX;
  var $dragY;
  
  // Address Array
  var $addresses;
  
  // GeoPoint Array
  var $geopoints;
  
  // Arrays with the controls that will be displayed
  var $controls;
  

  /**
   * Constructor
   *
   * @param string $apiKey The Google Maps API-Key for your domain.
   */
  function NXGoogleMapsAPI($apiKey="") {
    $this->apiKey = $apiKey;
    if ($this->apiKey == "") 
      $this->apiKey = GOOGLE_API_KEY;
    $this->_initialize();
  }
  
  
  /**
   * Add an address-marker to the map. The address is resolved by the webbrowser.
   * with the Google Geocoder.
   *
   * @param string address which should be add. test with google maps
   * @param string HTML-Code which will be displayed when the user clicks the address
   * @param boolean Set the Center to this point(true) or not (false)
   */
  function addAddress($address, $htmlinfo, $setCenter=true) {
    $ar = array(addSlashes($address), addSlashes($htmlinfo), $setCenter);
    array_push($this->addresses, $ar);	
  }
  
  /**
   * Add a dragable marker to the map. Only one Drag-Marker is allowed!
   *
   * @param integer $longitude Longitude of the point
   * @param integer $latitude  Lattitude of the point
   */
  function addDragMarker($longitude, $latitude) {
    $this->dragX = $longitude;
    $this->dragY = $latitude;	
  }
  
  /**
   * Add a geopoint to the map. 
   *
   * @param integer Longitude of the point
   * @param integer Latitude of the point
   * @param string HTML-Code which will be displayed when the user clicks the address
   * @param boolean Set the Center to this point(true) or not (false)
   */  
  function addGeoPoint($longitude, $latitude, $htmlinfo, $setCenter) {
    $ar = array($longitude, $latitude, addSlashes($htmlinfo), $setCenter);
    array_push($this->geopoints, $ar);	
  }
  
  /**
   * Adds a control to the map
   *
   * @param control Control-Type. Allowed are the constants 
   * GLargeMapControl - a large pan/zoom control used on Google Maps. Appears in the top left corner of the map.
   * GSmallMapControl - a smaller pan/zoom control used on Google Maps. Appears in the top left corner of the map.
   * GSmallZoomControl - a small zoom control (no panning controls) used in the small map blowup windows used to display driving directions steps on Google Maps.
   * GScaleControl - a map scale
   * GMapTypeControl - buttons that let the user toggle between map types (such as Map and Satellite)
   * GOverviewMapControl - a collapsible overview map in the corner of the screen
   *      
   */      
  function addControl($control) {
  	array_push($this->controls, $control);
  }
  
  /**
   * Set the ZoomFactor
   * The ZoomFactor is a value between 0 and 17
   *
   * @param integer $zoomFactor Value of the Zoom-Factor
   */
  function setZoomFactor($zoomFactor) {
  	 if ($zoomFactor > -1 && $zoomFactor < 18) {
  	   $this->zoomFactor = $zoomFactor;
  	 }
  }
  
  /**
   * Set the width of the map
   *
   * @param integer $width The Height in pixels
   */
  function setWidth($width) {
    $this->width = $width;
  }
  
  /**
   * Set the height of the map
   *
   * @param integer $height The Height in pixels
   */
  function setHeight($height) {
  	  $this->height = $height;
  }
  
  /**
   * Center the map to the coordinates
   *
   * @param integer $x Longitude
   * @param integer $y Latitude
   */
  function setCenter($x, $y) {
  	$this->centerX = $x;
  	$this->centerY = $y;
  }
  
  
  /**
   * Returns the HTML-Code, which must be placed within the <HEAD>-Tags of your page.
   *
   * @returns string The Code for the <Head>-Tag
   */
  function getHeadCode() {
  	$out = '
 <style type="text/css">
  	 v\:* {
       behavior:url(#default#VML);
     }
    </style>
    <script src="http://maps.google.com/maps?file=api&v=2&key='.$this->apiKey.'"  type="text/javascript"></script>';
   $out.= $this->_getGMapInitCode();
   return $out;
  }
  
  /**
   * Get the BodyCode and draw the map.
   *
   * @returns string Returns the code which is to be placed wight the <body>-tags.
   */
  function getBodyCode() {
  	$out = '<div id="'.$this->divId.'" style="width:'.$this->width.'px;height:'.$this->height.'px;"></div>';  	
  	return $out;
  }
  
  /**
   * Get the code, which must be passed to the <body>-attribute onLoad.
   *
   * @returns string The onload Code
   */
  function getOnLoadCode() {
  	$out = "initNXGMap(document.getElementById('$this->divId'));";
  	return $out;
  }
  
  
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//////////// Internal functions /////////////////////////////////////////////////////////////////////////////////  
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////  
  
  /** 
   * Compiles the Javascript to initialize the map.
   * Is automatically called, so do not call yourself.
   */
  function _getGMapInitCode() {
  	$out = '
<script type="text/javascript">
//<![CDATA[
var map = null;
var geocoder = null;
var center = null;
var updateX = null;
var updateY = null;
var marker = null;';
      
			 // Add Geopoints Array
			 $out.="\n";
      if ( count($this->geopoints) > 0 ) {      	
      	$out.= 'var geopoints = new Array(';
      	for ($i=0; $i < count($this->geopoints); $i++) {
      	  	$out.= ' new Array('.$this->geopoints[$i][0].','.$this->geopoints[$i][1].' ,"'.$this->geopoints[$i][2].'", ';
      	  	// move to this address?
      	  	if ($this->geopoints[$i][3]) {
      	  		$out.='true';
      	  	} else {
      	  		$out.='false';
      	  	}
      	  	$out.=')';
      	  	if ($i < (count($this->geopoints)-1)) $out.=', ';
      	}
      	$out.=");\n";
      } else {
      	  $out.="var geopoints = new Array();\n";
      }
      
			 // Add Addresses Array      
      $out.="\n";
      if ( count($this->addresses) > 0 ) {      	
      	$out.= 'var addresses = new Array(';
      	for ($i=0; $i < count($this->addresses); $i++) {
      	  	$out.= ' new Array("'.$this->addresses[$i][0].'", "'.$this->addresses[$i][1].'", ';
      	  	// move to this address?
      	  	if ($this->addresses[$i][2]) {
      	  		$out.='true';
      	  	} else {
      	  		$out.='false';
      	  	}
      	  	$out.=')';
      	  	if ($i < (count($this->addresses)-1)) $out.=', ';
      	}
      	$out.=");\n";
      } else {
      	  $out.="var addresses = new Array();\n";
      }
    	
    	// Draw standard js-functions and initialization code.
    	$out.='
function showAddresses() {
  for (i=0; i < addresses.length; i++) {
 	  	showAddress(addresses[i][0], addresses[i][1], addresses[i][2]);
 	  	
  }	
}
    	
function showAddress(address, htmlInfo, moveToPoint) {
 if (geocoder) {
   geocoder.getLatLng(
     address,
     function(point) {
       if (!point) {
         //alert("Location not found:" + address);
       } else {              
         if (moveToPoint) {
           map.setCenter(point, '.$this->zoomFactor.');
         }
         var marker = new GMarker(point);
         map.addOverlay(marker);
         if (htmlInfo != "") {
           GEvent.addListener(marker, "mouseover", function() {
              marker.openInfoWindowHtml(htmlInfo);
           });  
           GEvent.addListener(marker, "click", function() {
              marker.openInfoWindowHtml(htmlInfo);
           });             
           GEvent.addListener(marker, "mouseout", function() {
              marker.closeInfoWindow();
           });                       
         }
       }
     }
   );
  }
}

function showGeopoints() {
  for (i=0; i < geopoints.length; i++) {
 	  	showGeopoint(geopoints[i][0], geopoints[i][1], geopoints[i][2], geopoints[i][3]);		
  }	
}

function showGeopoint(longitude, latitude, htmlInfo, moveToPoint) {
  if (moveToPoint) {
    map.setCenter(new GLatLng(longitude, latitude), '.$this->zoomFactor.');
  }
  var marker = new GMarker(new GLatLng(longitude, latitude));
  map.addOverlay(marker);
  if (htmlInfo != "") {
    GEvent.addListener(marker, "click", function() {
      marker.openInfoWindowHtml(htmlInfo);
    });
    GEvent.addListener(marker, "mouseover", function() {
      $("map_tip").set("html", htmlInfo);
      $("map_info").style.display = "block";
    });
    GEvent.addListener(marker, "mouseout", function() {
      $("map_info").style.display = "none";
    }); 
     }   
}

function moveToGeopoint(index) {
	map.panTo(new GLatLng(geopoints[index][0], geopoints[index][1]));
}

function moveToAddress(index) {
  moveToAddressEx(addresses[index][0]); 
}

function moveToAddressEx(addressString) {
  if (geocoder) { 
   geocoder.getLatLng(
     addressString,
     function(point) {       
       if (!point) {
         alert("Location not found:" + addressString);
       } else {                                    
          center = point;
          map.panTo(point);           
       }
     });    
  }
}

function moveToAddressDMarker(addressString) {
  if (geocoder) { 
   geocoder.getLatLng(
     addressString,
     function(point) {       
       if (!point) {
         alert("Location not found:" + addressString);
       } else {                                    
          center = point;
          setZoomFactor(14);
          map.panTo(point);  
          addDragableMarker();         
       }
     });    
  }
}

function setZoomFactor(factor) {
	  map.setZoom(factor);
}

function addDragableMarker() {
  if (!marker) {
    marker = new GMarker(center, {draggable: true});
    map.addOverlay(marker);
       
    GEvent.addListener(marker, "dragend", function() {      
      var tpoint =  marker.getPoint();      
      document.getElementById(updateX).value = tpoint.lat();
      document.getElementById(updateY).value = tpoint.lng();              
  });

  } else {
  	marker.setPoint(center);  	 
  }
  
  var tpoint =  marker.getPoint();      
  document.getElementById(updateX).value = tpoint.lat();
  document.getElementById(updateY).value = tpoint.lng();              
}
    	
function initNXGMap(mapElement) {
 	if (GBrowserIsCompatible()) {
		map = new GMap2(mapElement);        
		geocoder = new GClientGeocoder();';
      
      // Add controls to the map
   
      if (count($this->controls) > 0) {
        for ($i=0; $i<count($this->controls); $i++) {
          $out.=" map.addControl(new ".$this->controls[$i].");\n";
        }
      }
      
      // Center the map
      if (($this->centerX != -1000) && ($this->centerY != -1000)) {      	
      	$out.= '    map.setCenter(new GLatLng('.$this->centerX.', '.$this->centerY.'), '.$this->zoomFactor.');'."\n";      	
      } else {
      	$out.= '    map.setCenter(new GLatLng(0,0),1);'."\n";      	
      }
      
      $out.='updateX="coordX"; updateY="coordY";';
      
      // Draw Dragmarker
      if (($this->dragX != 1000) && ($this->dragY != -1000)) {
      	$out.='
      	  center = new GLatLng('.$this->dragY.','.$this->dragX.');
      	  map.setCenter(center, '.$this->zoomFactor.');
      	  marker = new GMarker(center, {draggable: true});
    			map.addOverlay(marker);
       
    			GEvent.addListener(marker, "dragend", function() {      
      			var tpoint =  marker.getPoint();      
      			document.getElementById(updateX).value = tpoint.lat();
      			document.getElementById(updateY).value = tpoint.lng();              
  				});
      	';
      }
      
      // Add AddressPoints
			$out.="    showAddresses();\n";  
			// Add GeoPoints
			$out.="    showGeopoints();\n";
          
     $out.="\n";     
     $out.=' 	}
    	}
     //]]>
  	 </script>';
  	return $out;
  }
  
  /**
   * Initializes the standard values of the class. 
   * Is automatically called by the constructor.
   */
  function _initialize() {
  	$this->width 			= 800;
  	$this->height 		= 600;
  	$this->divId    	= 'map';
  	$this->zoomFactor = 14;
  	$this->centerX    = -1000;
  	$this->centerY    = -1000;
  	$this->dragX			= -1000;
  	$this->dragY			= -1000;
  	$this->addresses  = array();
  	$this->geopoints  = array();
  	$this->controls   = array();
  }

}
?>