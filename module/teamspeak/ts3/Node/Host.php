<?php

/**
 * TeamSpeak 3 PHP Framework
 *
 * $Id: Host.php 2010-01-18 21:54:35 sven $
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
 * Class describing a TeamSpeak 3 server instance and all it's parameters.
 * 
 * @package  TeamSpeak3_Node_Host
 * @category TeamSpeak3_Node
 */
class TeamSpeak3_Node_Host extends TeamSpeak3_Node_Abstract
{
  /**
   * @ignore 
   */
  protected $whoami = null;

  /**
   * @ignore 
   */
  protected $version = null;

  /**
   * @ignore 
   */
  protected $serverList = null;

  /**
   * @ignore 
   */
  protected $permissionList = null;

  /**
   * @ignore 
   */
  protected $predefined_query_name = null;

  /**
   * @ignore 
   */
  protected $exclude_query_clients = null;

  /**
   * The TeamSpeak3_Node_Host constructor.
   *
   * @param  TeamSpeak3_Adapter_ServerQuery $squery
   * @return TeamSpeak3_Node_Host
   */
  public function __construct(TeamSpeak3_Adapter_ServerQuery $squery)
  {
    $this->parent = $squery;
  }

  /**
   * Returns the primary ID of the selected virtual server.
   *
   * @return integer
   */
  public function serverSelectedId()
  {
    return $this->whoamiGet("virtualserver_id");
  }

  /**
   * Displays the servers version information including platform and build number.
   *
   * @return array
   */
  public function version()
  {
    if($this->version === null)
    {
      $this->version = $this->request("version")->toList();
    }

    return $this->version;
  }

  /**
   * Selects a virtual server by ID to allow further interaction.
   *
   * @param  integer $sid
   * @return void
   */
  public function serverSelect($sid)
  {
    $this->execute("use", array("sid" => $sid));

    if($this->predefined_query_name !== null)
    {
      $this->execute("clientupdate", array("client_nickname" => (string) $this->predefined_query_name));
    }

    $this->whoamiReset();
  }

  /**
   * Alias for serverSelect()
   *
   * @param  integer $sid
   * @return void
   */
  public function serverSelectById($sid)
  {
    $this->serverSelect($sid);
  }

  /**
   * Selects a virtual server by UDP port to allow further interaction.
   *
   * @param  integer $port
   * @return void
   */
  public function serverSelectByPort($port)
  {
    $this->execute("use", array("port" => $port));

    if($this->predefined_query_name !== null)
    {
      $this->execute("clientupdate", array("client_nickname" => (string) $this->predefined_query_name));
    }

    $this->whoamiReset();
  }

  /**
   * Deselects the active virtual server.
   *
   * @return void
   */
  public function serverDeselect()
  {
    $this->serverSelect(0);
  }

  /**
   * Returns the ID of a virtual server matching the given port.
   *
   * @param  integer $port
   * @return integer
   */
  public function serverIdGetByPort($port)
  {
    $sid = $this->execute("serveridgetbyport", array("virtualserver_port" => $port))->toList();

    return $sid["server_id"];
  }

  /**
   * Returns the port of a virtual server matching the given ID.
   *
   * @param  integer $sid
   * @return integer
   */
  public function serverGetPortById($sid)
  {
    if(!array_key_exists($sid, $this->serverList()))
    {
      throw new TeamSpeak3_Adapter_ServerQuery_Exception("invalid serverID", 0x400);
    }

    return $this->serverList[$sid]["virtualserver_port"];
  }

  /**
   * Returns the TeamSpeak3_Node_Server object matching the given ID.
   *
   * @param  integer $sid
   * @throws TeamSpeak3_Adapter_ServerQuery_Exception
   * @return TeamSpeak3_Node_Server
   */
  public function serverGetById($sid)
  {
    $this->serverSelectById($sid);

    return new TeamSpeak3_Node_Server($this, array("virtualserver_id" => intval($sid)));
  }

  /**
   * Returns the TeamSpeak3_Node_Server object matching the given port number.
   * 
   * @param  integer $port
   * @throws TeamSpeak3_Adapter_ServerQuery_Exception
   * @return TeamSpeak3_Node_Server
   */
  public function serverGetByPort($port)
  {
    $this->serverSelectByPort($port);
    
    return new TeamSpeak3_Node_Server($this, array("virtualserver_id" => $this->serverSelectedId()));
  }

  /**
   * Returns the first TeamSpeak3_Node_Server object matching the given name.
   * 
   * @param  string $name
   * @throws TeamSpeak3_Adapter_ServerQuery_Exception
   * @return TeamSpeak3_Node_Server
   */
  public function serverGetByName($name)
  {
    foreach($this->serverList() as $server)
    {
      if($server["virtualserver_name"] == $name) return $server;
    }

    throw new TeamSpeak3_Adapter_ServerQuery_Exception("invalid serverID", 0x400);
  }

  /**
   * Returns the first TeamSpeak3_Node_Server object matching the given unique identifier.
   * 
   * @param  string $uid
   * @throws TeamSpeak3_Adapter_ServerQuery_Exception
   * @return TeamSpeak3_Node_Server
   */
  public function serverGetByUid($uid)
  {
    foreach($this->serverList() as $server)
    {
      if($server["virtualserver_unique_identifier"] == $uid) return $server;
    }

    throw new TeamSpeak3_Adapter_ServerQuery_Exception("invalid serverID", 0x400);
  }

  /**
   * Creates a new virtual server using given properties and returns an assoc
   * array containing the new ID and initial admin token.
   *
   * @param  array $properties
   * @return array
   */
  public function serverCreate(array $properties)
  {
    $this->serverListReset();

    return $this->execute("servercreate", $properties)->toList();
  }

  /**
   * Deletes the virtual server specified by ID.
   *
   * @param  integer $sid
   * @return void
   */
  public function serverDelete($sid)
  {
    $this->serverListReset();

    $this->execute("serverdelete", array("sid" => $sid));
  }

  /**
   * Starts the virtual server specified by ID.
   *
   * @param  integer $sid
   * @return void
   */
  public function serverStart($sid)
  {
    if($sid == $this->serverSelectedId())
    {
      $this->serverDeselect();
    }
    
    $this->serverListReset();

    $this->execute("serverstart", array("sid" => $sid));
  }

  /**
   * Stops the virtual server specified by ID.
   *
   * @param  integer $sid
   * @return void
   */
  public function serverStop($sid)
  {
    if($sid == $this->serverSelectedId())
    {
      $this->serverDeselect();
    }
    
    $this->serverListReset();

    $this->execute("serverstop", array("sid" => $sid));
  }

  /**
   * Stops the entire TeamSpeak 3 Server instance by shutting down the process.
   *
   * @return void
   */
  public function serverStopProcess()
  {
    $this->execute("serverprocessstop");
  }

  /**
   * Returns an array filled with TeamSpeak3_Node_Server objects.
   *
   * @return array
   */
  public function serverList()
  {
    if($this->serverList === null)
    {
      $servers = $this->request("serverlist -uid")->toAssocArray("virtualserver_id");

      $this->serverList = array();

      foreach($servers as $sid => $server)
      {
        $this->serverList[$sid] = new TeamSpeak3_Node_Server($this, $server);
      }

      $this->resetNodeList();
    }

    return $this->serverList;
  }

  /**
   * Resets the list of virtual servers.
   *
   * @return void
   */
  public function serverListReset()
  {
    $this->resetNodeList();
    $this->serverList = null;
  }

  /**
   * Displays a list of IP addresses used by the server instance on multi-homed machines.
   *
   * @return array
   */
  public function bindingList()
  {
    return $this->request("bindinglist")->toArray();
  }

  /**
   * Displays a list of permissions available on the server instance.
   *
   * @return array
   */
  public function permissionList()
  {
    if($this->permissionList === null)
    {
      $this->permissionList = $this->request("permissionlist")->toAssocArray("permname");
    }

    return $this->permissionList;
  }

  /**
   * Displays the IDs of all clients, channels or groups using the permission with the
   * specified ID.
   *
   * @param  integer $permid
   * @return array
   */
  public function permissionFind($permid)
  {
    return $this->execute("permfind", array("permid" => $permid))->toArray();
  }

  /**
   * Returns the ID of the permission matching the given name.
   *
   * @param  string $name
   * @return integer
   */
  public function permissionGetIdByName($name)
  {
    if(!array_key_exists($name, $this->permissionList()))
    {
      throw new TeamSpeak3_Adapter_ServerQuery_Exception("invalid permission ID", 0xA02);
    }

    return $this->permissionList[$name]["permid"];
  }

  /**
   * Changes the server instance configuration using given properties.
   *
   * @param  array $properties
   * @return void
   */
  public function modify(array $properties)
  {
    $this->execute("instanceedit", $properties);
    $this->resetNodeInfo();
  }

  /**
   * Sends a text message to all clients on all virtual servers in the TeamSpeak 3 Server instance.
   *
   * @param  string $msg
   * @return void
   */
  public function message($msg)
  {
    $this->execute("gm", array("msg" => $msg));
  }

  /**
   * Displays a specified number of entries from the servers log.
   * 
   * @param  integer $limitcount
   * @param  string  $comparator
   * @param  string  $timestamp
   * @return array
   */
  public function logView($limitcount = 30, $comparator = null, $timestamp = null)
  {
    return $this->execute("logview", array("limitcount" => $limitcount, "comparator" => $comparator, "timestamp" => $timestamp))->toArray();
  }

  /**
   * Writes a custom entry into the server instance log.
   *
   * @param  string  $logmsg
   * @param  integer $loglevel
   * @return void
   */
  public function logAdd($logmsg, $loglevel = TeamSpeak3::LOGLEVEL_INFO)
  {
    $sid = $this->serverSelectedId();

    $this->serverDeselect();
    $this->execute("logadd", array("logmsg" => $logmsg, "loglevel" => $loglevel));
    $this->serverSelect($sid);
  }

  /**
   * Authenticates with the TeamSpeak 3 Server instance using given ServerQuery login credentials.
   *
   * @param  string $username
   * @param  string $password
   * @return void
   */
  public function login($username, $password)
  {
    $this->execute("login", array("client_login_name" => $username, "client_login_password" => $password));
    $this->whoamiReset();
  }

  /**
   * Deselects the active virtual server and logs out from the server instance.
   *
   * @return void
   */
  public function logout()
  {
    $this->request("logout");
    $this->whoamiReset();
  }

  /**
   * Displays information about your current ServerQuery connection.
   *
   * @return array
   */
  public function whoami()
  {
    if($this->whoami === null)
    {
      $this->whoami = $this->request("whoami")->toList();
    }

    return $this->whoami;
  }

  /**
   * Returns a single value from the current ServerQuery connection info.
   *
   * @param  string $ident
   * @param  mixed  $default
   * @return mixed
   */
  public function whoamiGet($ident, $default = null)
  {
    if(array_key_exists($ident, $this->whoami()))
    {
      return $this->whoami[$ident];
    }

    return $default;
  }

  /**
   * Sets a single value in the current ServerQuery connection info.
   *
   * @param  string $ident
   * @param  mixed  $value
   * @return mixed
   */
  public function whoamiSet($ident, $value = null)
  {
    $this->whoami();

    $this->whoami[$ident] = (is_numeric($value)) ? intval($value) : TeamSpeak3_Helper_String::factory($value);
  }

  /**
   * Resets the current ServerQuery connection info.
   *
   * @return void
   */
  public function whoamiReset()
  {
    $this->whoami = null;
  }

  /**
   * Returns the hostname or IPv4 address the adapter is connected to.
   *
   * @return string
   */
  public function getAdapterHost()
  {
    return $this->getParent()->getTransportHost();
  }

  /**
   * Returns the network port the adapter is connected to.
   *
   * @return string
   */
  public function getAdapterPort()
  {
    return $this->getParent()->getTransportPort();
  }

  /**
   * @ignore
   */
  protected function fetchNodeList()
  {
    $servers = $this->serverList();

    foreach($servers as $server)
    {
      $this->nodeList[] = $server;
    }
  }

  /**
   * @ignore
   */
  protected function fetchNodeInfo()
  {
    $info1 = $this->request("hostinfo")->toList();
    $info2 = $this->request("instanceinfo")->toList();

    $this->nodeInfo = array_merge($this->nodeInfo, $info1, $info2);
  }

  /**
   * Sets a pre-defined nickname for ServerQuery clients which will be used automatically 
   * after selecting a virtual server.
   *
   * @param  string $name
   * @return void
   */
  public function setPredefinedQueryName($name = null)
  {
    $this->predefined_query_name = $name;
  }

  /**
   * Returns the pre-defined nickname for ServerQuery clients which will be used automatically 
   * after selecting a virtual server.
   *
   * @return string
   */
  public function getPredefinedQueryName()
  {
    return $this->predefined_query_name;
  }

  /**
   * Sets the option to decide whether ServerQuery clients should be excluded from node
   * lists or not.
   *
   * @param  boolean $exclude
   * @return void
   */
  public function setExcludeQueryClients($exclude = FALSE)
  {
    $this->exclude_query_clients = $exclude;
  }

  /**
   * Returns the option to decide whether ServerQuery clients should be excluded from node
   * lists or not.
   *
   * @return boolean
   */
  public function getExcludeQueryClients()
  {
    return $this->exclude_query_clients;
  }
  
  /**
   * Returns the underlying TeamSpeak3_Adapter_ServerQuery object.
   *
   * @return TeamSpeak3_Adapter_ServerQuery
   */
  public function getAdapter()
  {
    return $this->getParent();
  }

  /**
   * Returns a unique identifier for the node which can be used as a HTML property.
   * 
   * @return string
   */
  public function getUniqueId()
  {
    return "ts3_h";
  }

  /**
   * Returns the name of a possible icon to display the node object.
   *
   * @return string
   */
  public function getIcon()
  {
    return "host";
  }

  /**
   * Returns a symbol representing the node.
   * 
   * @return string
   */
  public function getSymbol()
  {
    return "+";
  }

  /**
   * Returns a string representation of this node.
   *
   * @return string
   */
  public function __toString()
  {
    return (string) $this->getAdapterHost();
  }
}

