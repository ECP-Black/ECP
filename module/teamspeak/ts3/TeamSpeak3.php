<?php

/**
 * TeamSpeak 3 PHP Framework
 *
 * $Id: TeamSpeak3.php 2010-01-18 21:54:35 sven $
 * 
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @package   TeamSpeak3
 * @version   1.0.22-beta
 * @author    Sven 'ScP' Paulsen
 * @copyright Copyright (c) 2010 by Planet TeamSpeak. All rights reserved.
 */

/**
 * Factory class for TeamSpeak 3 PHP Framework objects.
 * 
 * @package  TeamSpeak3
 * @category TeamSpeak3
 */
class TeamSpeak3
{
  /**
   * TeamSpeak 3 protocol welcome message.
   */
  const READY = "TS3";

  /**
   * TeamSpeak 3 protocol error message prefix.
   */
  const ERROR = "error";

  /**
   * TeamSpeak 3 protocol event message prefix.
   */
  const EVENT = "notify";

  /**
   * TeamSpeak 3 PHP Framework version.
   */
  const LIB_VERSION = "1.0.22-beta";
  
  /**
   * TeamSpeak 3 protocol seperators.
   */
  const SEPERATOR_LINE = "\n";
  const SEPERATOR_LIST = "|";
  const SEPERATOR_CELL = " ";
  const SEPERATOR_PAIR = "=";
    
  /**
   * TeamSpeak 3 log levels.
   */
  const LOGLEVEL_ERROR   = 0x01; // 1: everything that is really bad
  const LOGLEVEL_WARNING = 0x02; // 2: everything that might be bad
  const LOGLEVEL_DEBUG   = 0x03; // 3: output that might help find a problem
  const LOGLEVEL_INFO    = 0x04; // 4: informational output
  
  /**
   * TeamSpeak 3 token types.
   */
  const TOKEN_SERVERGROUP  = 0x00; // 0: server group token (id1={groupID} id2=0)
  const TOKEN_CHANNELGROUP = 0x01; // 1: channel group token (id1={groupID} id2={channelID})
  
  /**
   * TeamSpeak 3 codec identifiers.
   */
  const CODEC_SPEEX_NARROWBAND    = 0x00; // 0: speex narrowband     (mono, 16bit, 8kHz)
  const CODEC_SPEEX_WIDEBAND      = 0x01; // 1: speex wideband       (mono, 16bit, 16kHz)
  const CODEC_SPEEX_ULTRAWIDEBAND = 0x02; // 2: speex ultra-wideband (mono, 16bit, 32kHz)
  const CODEC_CELT_MONO           = 0x03; // 3: celt mono            (mono, 16bit, 48kHz)
  
  /**
   * TeamSpeak 3 kick reason types.
   */
  const KICK_CHANNEL = 0x04; // 4: kick client from channel
  const KICK_SERVER  = 0x05; // 5: kick client from server
  
  /**
   * TeamSpeak 3 text message target modes.
   */
  const TEXTMSG_CLIENT  = 0x01; // 1: target is a client
  const TEXTMSG_CHANNEL = 0x02; // 2: target is a channel
  const TEXTMSG_SERVER  = 0x03; // 3: target is a virtual server
  
  /**
   * TeamSpeak 3 log levels.
   */
  const HOSTMSG_LOG       = 0x01; // 1: display message in chatlog
  const HOSTMSG_MODAL     = 0x02; // 2: display message in modal dialog
  const HOSTMSG_MODALQUIT = 0x03; // 3: display message in modal dialog and close connection
  
  /**
   * TeamSpeak 3 permission group database types.
   */
  const GROUP_DBTYPE_TEMPLATE    = 0x00; // 0: template group     (used for new virtual servers)
  const GROUP_DBTYPE_REGULAR     = 0x01; // 1: regular group      (used for regular clients)
  const GROUP_DBTYPE_SERVERQUERY = 0x02; // 2: global query group (used for ServerQuery clients)
  
  /**
   * TeamSpeak 3 permission types.
   */
  const PERM_TYPE_SERVERGROUP   = 0x00; // 0: server group permission
  const PERM_TYPE_CLIENT        = 0x01; // 1: client specific permission
  const PERM_TYPE_CHANNEL       = 0x02; // 2: channel specific permission
  const PERM_TYPE_CHANNELGROUP  = 0x03; // 3: channel group permission
  const PERM_TYPE_CHANNELCLIENT = 0x04; // 4: channel-client specific permission
  
  /**
   * TeamSpeak 3 file types.
   */
  const FILE_TYPE_DIRECTORY = 0x00; // 0: file is directory
  const FILE_TYPE_REGULAR   = 0x01; // 1: file is regular

  /**
   * Stores an array containing various chars which needs to be escaped while communicating with
   * a TeamSpeak 3 Server.
   *
   * @var array
   */
  private static $escape_patterns = array(
    "\\" => "\\\\", // backslash
    "/"  => "\\/",  // slash
    " "  => "\\s",  // whitespace
    "|"  => "\\p",  // pipe
    ";"  => "\\;",  // semicolon
    "\a" => "\\a",  // bell
    "\b" => "\\b",  // backspace
    "\f" => "\\f",  // formfeed
    "\n" => "\\n",  // newline
    "\r" => "\\r",  // carriage return
    "\t" => "\\t",  // horizontal tab
    "\v" => "\\v"   // vertical tab
  );

  /**
   * Factory for TeamSpeak3_Adapter_Abstract classes. $uri must be formatted as
   * "<adapter>://<user>:<pass>@<host>:<port>/<options>". All parameters except 
   * adapter, host and port are optional.
   * 
   * Supported Options:
   *   - timeout
   *   - nickname
   *   - server_id|server_uid|server_port|server_name
   *   - channel_id|channel_name
   *   - client_id|client_uid|client_name
   * 
   * Examples:
   *   - serverquery://127.0.0.1:10011/
   *   - serverquery://127.0.0.1:10011/?server_port=9987&channel_id=1
   *   - filetransfer://127.0.0.1:30011/
   * 
   * @param  string $uri
   * @throws TeamSpeak3_Exception
   * @return TeamSpeak3_Adapter_Abstract|TeamSpeak3_Node_Abstract
   */
  public static function factory($uri)
  {
    self::init();
    
    $uri = new TeamSpeak3_Helper_Uri($uri);
    
    $adapter = self::getAdapterName($uri->getScheme());
    $options = array("host" => $uri->getHost(), "port" => $uri->getPort(), "timeout" => intval($uri->getQueryVar("timeout", 10)));
    
    self::loadClass($adapter);
    
    $object = new $adapter(new TeamSpeak3_Transport_TCP($options));
    
    if($object instanceof TeamSpeak3_Adapter_ServerQuery)
    {
      $node = $object->getHost();
      
      if($uri->hasUser() && $uri->hasPass())
      {
        $node->login($uri->getUser(), $uri->getPass());
      }
      
      /* option to pre-define nickname */
      if($uri->hasQueryVar("nickname"))
      {
        $node->setPredefinedQueryName($uri->getQueryVar("nickname"));
      }
      
      /* option to hide ServerQuery clients */
      if($uri->getFragment() == "no_query_clients")
      {
        $node->setExcludeQueryClients(TRUE);
      }
      
      /* access server node object */
      if($uri->hasQueryVar("server_id"))
      {
        $node = $node->serverGetById($uri->getQueryVar("server_id"));
      }
      elseif($uri->hasQueryVar("server_uid"))
      {
        $node = $node->serverGetByUid($uri->getQueryVar("server_uid"));
      }
      elseif($uri->hasQueryVar("server_port"))
      {
        $node = $node->serverGetByPort($uri->getQueryVar("server_port"));
      }
      elseif($uri->hasQueryVar("server_name"))
      {
        $node = $node->serverGetByName($uri->getQueryVar("server_name"));
      }
      
      if($node instanceof TeamSpeak3_Node_Server)
      {
        /* access channel node object */
        if($uri->hasQueryVar("channel_id"))
        {
          $node = $node->channelGetById($uri->getQueryVar("channel_id"));
        }
        elseif($uri->hasQueryVar("channel_name"))
        {
          $node = $node->channelGetByName($uri->getQueryVar("channel_name"));
        }
        
        /* access client node object */
        if($uri->hasQueryVar("client_id"))
        {
          $node = $node->clientGetById($uri->getQueryVar("client_id"));
        }
        if($uri->hasQueryVar("client_uid"))
        {
          $node = $node->clientGetByUid($uri->getQueryVar("client_uid"));
        }
        elseif($uri->hasQueryVar("client_name"))
        {
          $node = $node->clientGetByName($uri->getQueryVar("client_name"));
        }
      }
      
      return $node;
    }
    
    return $object;
  }

  /**
   * Loads a class from a PHP file. The filename must be formatted as "$class.php".
   * 
   * include() is not prefixed with the @ operator because if the file is loaded and 
   * contains a parse error, execution will halt silently and this is difficult to debug.
   *
   * @param  string $class
   * @throws LogicException
   * @return void
   */
  private static function loadClass($class)
  {
    if(class_exists($class, FALSE) || interface_exists($class, FALSE))
    {
      return;
    }

    if(preg_match('/[^a-z0-9\\/\\\\_.-]/i', $class))
    {
      throw new LogicException("illegal characters in classname '" . $class . "'");
    }

    $file = self::getFilePath($class) . ".php";

    if(!file_exists($file) || !is_readable($file))
    {
      throw new LogicException("file '" . $file . "' does not exist or is not readable");
    }

    if(class_exists($class, FALSE) || interface_exists($class, FALSE))
    {
      throw new LogicException("class '" . $class . "' does not exist");
    }
    
    return include_once($file);
  }
  
  /**
   * Generates a file path by $name.
   *
   * @param  string $name
   * @return string
   */
  private static function getFilePath($name)
  {
    $path = str_replace("_", DIRECTORY_SEPARATOR, $name);
    $path = str_replace(__CLASS__, dirname(__FILE__), $path);
    
    return $path;
  }
  
  /**
   * Returns the name of an adapter class by a given name.
   *
   * @param  string $name
   * @param  string $namespace
   * @throws TeamSpeak3_Exception
   * @return string
   */
  private static function getAdapterName($name, $namespace = "TeamSpeak3_Adapter_")
  {
    $path = self::getFilePath($namespace);
    $scan = scandir($path);
    
    foreach($scan as $node)
    {
      $file = TeamSpeak3_Helper_String::factory($node)->toLower();
      
      if($file->startsWith($name) && $file->endsWith(".php"))
      {
        return $namespace . str_replace(".php", "", $node);
      }
    }
    
    throw new TeamSpeak3_Adapter_Exception("adapter '" . $name . "' does not exist");
  }

  /**
   * spl_autoload() suitable implementation for supporting class autoloading.
   *
   * @param  string $class
   * @return boolean
   */
  public static function autoload($class)
  {
    try
    {
      self::loadClass($class);

      return TRUE;
    }
    catch(Exception $e)
    {
      return FALSE;
    }
  }

  /**
   * Checks for required PHP features, enables autoloading and starts the default profiler.
   *
   * @throws LogicException
   * @return void
   */
  public static function init()
  {
    if(version_compare(phpversion(), "5.2.1") == -1)
    {
      throw new LogicException("this particular software cannot be used with the installed version of PHP");
    }
    
    if(!function_exists("stream_socket_client"))
    {
      throw new LogicException("network functions are not available in this PHP installation");
    }

    if(!function_exists("spl_autoload_register"))
    {
      throw new LogicException("autoload functions are not available in this PHP installation");
    }

    spl_autoload_register(array(__CLASS__, "autoload"));
    
    TeamSpeak3_Helper_Profiler::start();
  }
  
  /**
   * Returns an assoc array containing all escape patterns available on a TeamSpeak 3
   * Server.
   *
   * @return array
   */
  public static function getEscapePatterns()
  {
    return self::$escape_patterns;
  }

  /**
   * Debug helper function. This is a wrapper for var_dump() that adds the <pre /> tags, 
   * cleans up newlines and indents, and runs htmlentities() before output.
   *
   * @param  mixed  $var
   * @param  bool   $echo
   * @return string
   */
  public static function dump($var, $echo = TRUE)
  {
    ob_start();
    var_dump($var);
    
    $output = ob_get_clean();
    $output = preg_replace("/\]\=\>\n(\s+)/m", "] => ", $output);
    
    if(PHP_SAPI == "cli")
    {
      $output = PHP_EOL . PHP_EOL . $output . PHP_EOL;
    }
    else
    {
      $output = "<pre>" . htmlspecialchars($output, ENT_QUOTES) . "</pre>";
    }

    if($echo) echo($output);
    
    return $output;
  }
}
