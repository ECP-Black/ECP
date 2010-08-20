<?php

/**
 * TeamSpeak 3 PHP Framework
 *
 * $Id: Server.php 2010-01-18 21:54:35 sven $
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
 * Class describing a TeamSpeak 3 virtual server and all it's parameters.
 * 
 * @package  TeamSpeak3_Node_Server
 * @category TeamSpeak3_Node
 */
class TeamSpeak3_Node_Server extends TeamSpeak3_Node_Abstract
{
  /**
   * @ignore 
   */
  protected $channelList = null;
  
  /**
   * @ignore 
   */
  protected $clientList = null;
  
  /**
   * @ignore 
   */
  protected $sgroupList = null;
  
  /**
   * @ignore 
   */
  protected $cgroupList = null;
  
  /**
   * The TeamSpeak3_Node_Server constructor.
   *
   * @param  TeamSpeak3_Node_Host $host
   * @param  array  $info
   * @param  string $index
   * @throws TeamSpeak3_Adapter_ServerQuery_Exception
   * @return TeamSpeak3_Node_Server
   */
  public function __construct(TeamSpeak3_Node_Host $host, array $info, $index = "virtualserver_id")
  {
    $this->parent = $host;
    $this->nodeInfo = $info;
    
    if(!array_key_exists($index, $this->nodeInfo))
    {
      throw new TeamSpeak3_Adapter_ServerQuery_Exception("invalid serverID", 0x400);
    }
    
    $this->nodeId = $this->nodeInfo[$index];
  }
  
  /**
   * Sends a prepared command to the server and returns the result.
   *
   * @param  string $cmd
   * @return TeamSpeak3_Adapter_ServerQuery_Reply
   */
  public function request($cmd)
  {
    if($this->getId() != $this->getParent()->serverSelectedId())
    {
      $this->getParent()->serverSelect($this->getId());
    }
    
    return $this->getParent()->request($cmd);
  }
  
  /**
   * Returns an array filled with TeamSpeak3_Node_Channel objects.
   *
   * @return array
   */
  public function channelList()
  {
    if($this->channelList === null)
    {
      $channels = $this->request("channellist -topic -flags -voice -limits")->toAssocArray("cid");
      
      $this->channelList = array();
      
      foreach($channels as $cid => $channel)
      {
        $this->channelList[$cid] = new TeamSpeak3_Node_Channel($this, $channel);
      }
      
      $this->resetNodeList();
    }
    
    return $this->channelList;
  }
  
  /**
   * Resets the list of channels online.
   *
   * @return void
   */
  public function channelListReset()
  {
    $this->resetNodeList();
    $this->channelList = null;
  }
  
  /**
   * Creates a new channel using given properties and returns the new ID.
   *
   * @param  array $properties
   * @return array
   */
  public function channelCreate(array $properties)
  {
    $this->channelListReset();
    
    $cid = $this->execute("channelcreate", $properties)->toList();
    
    if(!isset($properties["client_flag_permanent"]) && !isset($properties["client_flag_semi_permanent"]))
    {
      $this->getParent()->whoamiSet("client_channel_id", $cid["cid"]);
    }
    
    return $cid["cid"];
  }
  
  /**
   * Deletes the channel specified by $cid.
   *
   * @param  integer $cid
   * @param  boolean $force
   * @return void
   */
  public function channelDelete($cid, $force = FALSE)
  {
    $this->channelListReset();
    
    $this->execute("channeldelete", array("cid" => $cid, "force" => $force));
    
    if($cid == $this->whoamiGet("client_channel_id"))
    {
      $this->getParent()->whoamiReset();
    }
  }
  
  /**
   * Moves the channel specified by $cid to the parent channel specified with $cpid.
   *
   * @param  integer $cid
   * @param  integer $cpid
   * @param  integer $order
   * @return void
   */
  public function channelMove($cid, $cpid, $order = null)
  {
    $this->channelListReset();
    
    $this->execute("channelmove", array("cid" => $cid, "cpid" => $cpid, "order" => $order));
  }
  
  /**
   * Returns a list of permissions defined for a specific channel.
   *
   * @param  integer $cid
   * @return array
   */
  public function channelPermList($cid)
  {
    return $this->execute("channelpermlist", array("cid" => $cid))->toAssocArray("permid");
  }
  
  /**
   * Adds a set of specified permissions to a channel. Multiple permissions can be added by 
   * providing the two parameters of each permission.
   *
   * @param  integer $cid
   * @param  integer $permid
   * @param  integer $permvalue
   * @return void
   */
  public function channelPermAssign($cid, $permid, $permvalue)
  {
    $this->execute("channeladdperm", array("cid" => $cid, "permid" => $permid, "permvalue" => $permvalue));
  }
  
  /**
   * Removes a set of specified permissions from a channel. Multiple permissions can be removed at once.
   *
   * @param  integer $cid
   * @param  integer $permid
   * @return void
   */
  public function channelPermRemove($cid, $permid)
  {
    $this->execute("channeldelperm", array("cid" => $cid, "permid" => $permid));
  }
  
  /**
   * Returns a list of permissions defined for a client in a specific channel.
   *
   * @param  integer $cid
   * @param  integer $cldbid
   * @return array
   */
  public function channelClientPermList($cid, $cldbid)
  {
    return $this->execute("channelclientpermlist", array("cid" => $cid, "cldbid" => $cldbid))->toAssocArray("permid");
  }
  
  /**
   * Adds a set of specified permissions to a client in a specific channel. Multiple permissions can be added by 
   * providing the two parameters of each permission.
   *
   * @param  integer $cid
   * @param  integer $cldbid
   * @param  integer $permid
   * @param  integer $permvalue
   * @return void
   */
  public function channelClientPermAssign($cid, $cldbid, $permid, $permvalue)
  {
    $this->execute("channelclientaddperm", array("cid" => $cid, "cldbid" => $cldbid, "permid" => $permid, "permvalue" => $permvalue));
  }
  
  /**
   * Removes a set of specified permissions from a client in a specific channel. Multiple permissions can be removed at once.
   *
   * @param  integer $cid
   * @param  integer $cldbid
   * @param  integer $permid
   * @return void
   */
  public function channelClientPermRemove($cid, $cldbid, $permid)
  {
    $this->execute("channelclientdelperm", array("cid" => $cid, "cldbid" => $cldbid, "permid" => $permid));
  }
  
  /**
   * Returns a list of files and directories stored in the specified channels file repository.
   *
   * @param  integer $cid
   * @param  string  $cpw
   * @param  string  $path
   * @param  boolean $recursive
   * @return array
   */
  public function channelFileList($cid, $cpw = "", $path = "/", $recursive = FALSE)
  {
    $files = $this->execute("ftgetfilelist", array("cid" => $cid, "cpw" => $cpw, "path" => $path))->toArray();
    $count = count($files);
    
    for($i = 0; $i < $count; $i++)
    {
      $files[$i]["cid"] = $files[0]["cid"];
      $files[$i]["path"] = $files[0]["path"];
      
      if($recursive && $files[$i]["type"] == TeamSpeak3::FILE_TYPE_DIRECTORY)
      {
        $files = array_merge($files, $this->channelFileList($cid, $cpw, $path . $files[$i]["name"], $recursive));
      }
    }

    return $files;
  }
  
  /**
   * Returns detailed information about the specified file stored in a channels file repository.
   *
   * @param  integer $cid
   * @param  string  $cpw
   * @param  string  $path
   * @return array
   */
  public function channelFileInfo($cid, $cpw = "", $name = "/")
  {
    return array_pop($this->execute("ftgetfileinfo", array("cid" => $cid, "cpw" => $cpw, "name" => $name))->toArray());
  }
  
  /**
   * Renames a file in a channels file repository. If the two parameters $tcid and $tcpw are specified, the file 
   * will be moved into another channels file repository.
   *
   * @param  integer $cid
   * @param  string  $cpw
   * @param  string  $oldname
   * @param  string  $newname
   * @param  integer $tcid
   * @param  string  $tcpw
   * @return void
   */
  public function channelFileRename($cid, $cpw = "", $oldname = "/", $newname = "/", $tcid = null, $tcpw = null)
  {
    $this->execute("ftrenamefile", array("cid" => $cid, "cpw" => $cpw, "oldname" => $oldname, "newname" => $newname));
  }
  
  /**
   * Deletes one or more files stored in a channels file repository.
   *
   * @param  integer $cid
   * @param  string  $cpw
   * @param  string  $path
   * @return void
   */
  public function channelFileDelete($cid, $cpw = "", $name = "/")
  {
    $this->execute("ftdeletefile", array("cid" => $cid, "cpw" => $cpw, "name" => $name));
  }
  
  /**
   * Creates new directory in a channels file repository.
   *
   * @param  integer $cid
   * @param  string  $cpw
   * @param  string  $dirname
   * @return void
   */
  public function channelDirCreate($cid, $cpw = "", $dirname = "/")
  {
    $this->execute("ftcreatedir", array("cid" => $cid, "cpw" => $cpw, "dirname" => $dirname));
  }
  
  /**
   * Returns the pathway of a channel which can be used as a clients default channel.
   *
   * @param  integer $cid
   * @return string
   */
  public function channelGetPathway($cid)
  {
    $channel = $this->channelGetById($cid);
    $pathway = $channel["channel_name"];
    
    if($channel["pid"])
    {
      $pathway = $this->channelGetPathway($channel["pid"]) . "/" . $channel["channel_name"];
    }
    
    return $pathway;
  }
  
  /**
   * Returns the TeamSpeak3_Node_Channel object matching the given ID.
   *
   * @param  integer $cid
   * @throws TeamSpeak3_Adapter_ServerQuery_Exception
   * @return TeamSpeak3_Node_Channel
   */
  public function channelGetById($cid)
  {
    if(!array_key_exists((string) $cid, $this->channelList()))
    {
      throw new TeamSpeak3_Adapter_ServerQuery_Exception("invalid channelID", 0x300);
    }
    
    return $this->channelList[(string) $cid];
  }
  
  /**
   * Returns the TeamSpeak3_Node_Channel object matching the given name.
   * 
   * @param  string $name
   * @throws TeamSpeak3_Adapter_ServerQuery_Exception
   * @return TeamSpeak3_Node_Channel
   */
  public function channelGetByName($name)
  {
    foreach($this->channelList() as $channel)
    {
      if($channel["channel_name"] == $name) return $channel;
    }
    
    throw new TeamSpeak3_Adapter_ServerQuery_Exception("invalid channelID", 0x300);
  }
  
  /**
   * Returns an array filled with TeamSpeak3_Node_Client objects.
   *
   * @return array
   */
  public function clientList()
  {
    if($this->clientList === null)
    {
      $clients = $this->request("clientlist -uid -away -voice -info -times -groups")->toAssocArray("clid");
      
      $this->clientList = array();
      
      foreach($clients as $clid => $client)
      {
        if($this->getParent()->getExcludeQueryClients() && $client["client_type"]) continue;
        
        $this->clientList[$clid] = new TeamSpeak3_Node_Client($this, $client);
      }

      uasort($this->clientList, array(__CLASS__, "sortClientList"));
      
      $this->resetNodeList();
    }
    
    return $this->clientList;
  }
  
  /**
   * Resets the list of clients online.
   *
   * @return void
   */
  public function clientListReset()
  {
    $this->resetNodeList();
    $this->clientList = null;
  }
  
  /**
   * Returns a list of clients matching a given name pattern.
   *
   * @param  string $pattern
   * @return array
   */
  public function clientFind($pattern)
  {
    return $this->execute("clientfind", array("pattern" => $pattern))->toAssocArray("clid");
  }
  
  /**
   * Returns a list of client identities known by the virtual server.
   *
   * @param  integer $offset
   * @param  integer $limit
   * @return array
   */
  public function clientListDb($offset = null, $limit = null)
  {
    return $this->execute("clientdblist", array("start" => $offset, "duration" => $limit))->toAssocArray("cldbid");
  }
  
  /**
   * Returns a list of client database IDs matching a given pattern. You can either search for a clients 
   * last known nickname or his unique identity by using the -uid option.
   *
   * @param  string  $pattern
   * @param  boolean $uid
   * @return array
   */
  public function clientFindDb($pattern, $uid = FALSE)
  {
    return array_keys($this->execute("clientdbfind", array("pattern" => $pattern, ($uid) ? "-uid" : null))->toAssocArray("cldbid"));
  }
  
  /**
   * Returns the number of regular clients online.
   *
   * @return integer
   */
  public function clientCount()
  {
    return $this["virtualserver_clientsonline"]-$this["virtualserver_queryclientsonline"];
  }
  
  /**
   * Returns the TeamSpeak3_Node_Client object matching the given ID.
   *
   * @param  integer $clid
   * @throws TeamSpeak3_Adapter_ServerQuery_Exception
   * @return TeamSpeak3_Node_Client
   */
  public function clientGetById($clid)
  {
    if(!array_key_exists($clid, $this->clientList()))
    {
      throw new TeamSpeak3_Adapter_ServerQuery_Exception("invalid clientID", 0x200);
    }
    
    return $this->clientList[intval($clid)];
  }
  
  /**
   * Returns the TeamSpeak3_Node_Client object matching the given name.
   * 
   * @param  string $name
   * @throws TeamSpeak3_Adapter_ServerQuery_Exception
   * @return TeamSpeak3_Node_Client
   */
  public function clientGetByName($name)
  {
    foreach($this->clientList() as $client)
    {
      if($client["client_nickname"] == $name) return $client;
    }
    
    throw new TeamSpeak3_Adapter_ServerQuery_Exception("invalid clientID", 0x200);
  }
  
  /**
   * Returns the TeamSpeak3_Node_Client object matching the given unique identifier.
   * 
   * @param  string $uid
   * @throws TeamSpeak3_Adapter_ServerQuery_Exception
   * @return TeamSpeak3_Node_Client
   */
  public function clientGetByUid($uid)
  {
    foreach($this->clientList() as $uid)
    {
      if($client["client_unique_identifier"] == $uid) return $client;
    }
    
    throw new TeamSpeak3_Adapter_ServerQuery_Exception("invalid clientID", 0x200);
  }
  
  /**
   * Moves a client to another channel.
   *
   * @param  interger $cid
   * @param  string   $cpw
   * @return void
   */
  public function clientMove($clid, $cid, $cpw = null)
  {
    $this->clientListReset();
    
    $this->execute("clientmove", array("clid" => $clid, "cid" => $cid, "cpw" => $cpw));
    
    if($clid == $this->whoamiGet("client_id"))
    {
      $this->getParent()->whoamiSet("client_channel_id", $cid);
    }
  }
  
  /**
   * Kicks one or more clients from their currently joined channel or from the server.
   *
   * @param  integer $clid
   * @param  integer $reasonid
   * @param  string  $reasonmsg
   * @return void
   */
  public function clientKick($clid, $reasonid = TeamSpeak3::KICK_CHANNEL, $reasonmsg = null)
  {
    $this->clientListReset();
    
    $this->execute("clientkick", array("clid" => $clid, "reasonid" => $reasonid, "reasonmsg" => $reasonmsg));
  }

  /**
   * Sends a poke message to a client.
   *
   * @param  interger $msg
   * @return void
   */
  public function clientPoke($clid, $msg)
  {
    $this->execute("clientpoke", array("clid" => $clid, "msg" => $msg));
  }
  
  /**
   * Bans the client specified with ID $clid from the server. Please note that this will create two separate 
   * ban rules for the targeted clients IP address and his unique identifier.
   *
   * @param integer $clid
   * @param integer $timeseconds
   * @param string  $reason
   * @return array
   */
  public function clientBan($clid, $timeseconds = null, $reason = null)
  {
    $this->clientListReset();
    
    $bans = $this->execute("banclient", array("clid" => $clid, "time" => $timeseconds, "banreason" => $reason))->toAssocArray("banid");
    
    return array_keys($bans);
  }
  
  /**
   * Changes the clients properties using given properties.
   *
   * @param  string $cldbid
   * @param  array  $properties
   * @return void
   */
  public function clientModifyDb($cldbid, array $properties)
  {
    $properties["cldbid"] = $cldbid;
    
    $this->execute("clientdbedit", $properties);
  }
  
  /**
   * Deletes a clients properties from the database.
   *
   * @param  string $cldbid
   * @return void
   */
  public function clientDeleteDb($cldbid)
  {
    $this->execute("clientdbedit", array("cldbid" => $cldbid));
  }
  
  /**
   * Sets the channel group of a client to the ID specified.
   *
   * @param  interger $cldbid
   * @param  interger $cid
   * @param  interger $cgid
   * @return void
   */
  public function clientSetChannelGroup($cldbid, $cid, $cgid)
  {
    $this->execute("setclientchannelgroup", array("cldbid" => $cldbid, "cid" => $cid, "cgid" => $cgid));
  }
  
  /**
   * Returns a list of permissions defined for a client.
   *
   * @param  integer $cldbid
   * @return array
   */
  public function clientPermList($cldbid)
  {
    $this->clientListReset();
    
    return $this->execute("clientpermlist", array("cldbid" => $cldbid))->toAssocArray("permid");
  }
  
  /**
   * Adds a set of specified permissions to a client. Multiple permissions can be added by providing 
   * the three parameters of each permission.
   *
   * @param  integer $cldbid
   * @param  integer $permid
   * @param  integer $permvalue
   * @param  integer $permskip
   * @return void
   */
  public function clientPermAssign($cldbid, $permid, $permvalue, $permskip = FALSE)
  {
    $this->execute("clientaddperm", array("cldbid" => $cldbid, "permid" => $permid, "permvalue" => $permvalue, "permskip" => $permskip));
  }
  
  /**
   * Removes a set of specified permissions from a client. Multiple permissions can be removed at once.
   *
   * @param integer $cldbid
   * @param integer $permid
   * @return void
   */
  public function clientPermRemove($cldbid, $permid)
  {
    $this->execute("clientdelperm", array("cldbid" => $cldbid, "permid" => $permid));
  }
  
  /**
   * Returns a list of server groups available.
   *
   * @return array
   */
  public function serverGroupList()
  {
    if($this->sgroupList === null)
    {
      $this->sgroupList = $this->request("servergrouplist")->toAssocArray("sgid");
      
      foreach($this->sgroupList as $sgid => $group)
      {
        $this->sgroupList[$sgid] = new TeamSpeak3_Node_Servergroup($this, $group);
      }
    }
    
    return $this->sgroupList;
  }
  
  /**
   * Resets the list of server groups.
   *
   * @return void
   */
  public function serverGroupListReset()
  {
    $this->sgroupList = null;
  }
  
  /**
   * Creates a new server group using the name specified with $name and returns its ID.
   *
   * @return array
   */
  public function serverGroupCreate($name)
  {
    $this->serverGroupListReset();
    
    $sgid = $this->execute("servergroupadd", array("name" => $name))->toList();
    
    return $sgid["sgid"];
  }
  
  /**
   * Renames the server group specified with $sgid.
   *
   * @param  integer $sgid
   * @param  string $name
   * @return void
   */
  public function serverGroupRename($sgid, $name)
  {
    $this->serverGroupListReset();
    
    $this->execute("servergrouprename", array("sgid" => $sgid, "name" => $name));
  }
  
  /**
   * Deletes the server group specified with $sgid. If $force is set to 1, the server group 
   * will be deleted even if there are clients within.
   *
   * @param  integer $sgid
   * @param  boolean $force
   * @return void
   */
  public function serverGroupDelete($sgid, $force = FALSE)
  {
    $this->serverGroupListReset();
    
    $this->execute("servergroupdel", array("sgid" => $sgid, "force" => $force));
  }
  
  /**
   * Returns the TeamSpeak3_Node_Servergroup object matching the given ID.
   *
   * @param  integer $sgid
   * @throws TeamSpeak3_Adapter_ServerQuery_Exception
   * @return TeamSpeak3_Node_Servergroup
   */
  public function serverGroupGetById($sgid)
  {
    if(!array_key_exists($sgid, $this->serverGroupList()))
    {
      throw new TeamSpeak3_Adapter_ServerQuery_Exception("invalid groupID", 0xA00);
    }
    
    return $this->sgroupList[intval($sgid)];
  }
  
  /**
   * Returns the TeamSpeak3_Node_Servergroup object matching the given name.
   * 
   * @param  string  $name
   * @param  integer $type
   * @throws TeamSpeak3_Adapter_ServerQuery_Exception
   * @return TeamSpeak3_Node_Servergroup
   */
  public function serverGroupGetByName($name, $type = TeamSpeak3::GROUP_DBTYPE_REGULAR)
  {
    foreach($this->serverGroupList() as $group)
    {
      if($group["name"] == $name && $group["type"] == $type) return $group;
    }
    
    throw new TeamSpeak3_Adapter_ServerQuery_Exception("invalid groupID", 0xA00);
  }
  
  /**
   * Returns a list of permissions assigned to the server group specified.
   *
   * @param  integer $sgid
   * @return array
   */
  public function serverGroupPermList($sgid)
  {
    return $this->execute("servergrouppermlist", array("sgid" => $sgid))->toAssocArray("permid");
  }
  
  /**
   * Adds a set of specified permissions to the server group specified. Multiple permissions 
   * can be added by providing the four parameters of each permission in seperate arrays.
   *
   * @param  integer $sgid
   * @param  integer $permid
   * @param  integer $permvalue
   * @param  integer $permnegated
   * @param  integer $permskip
   * @return void
   */
  public function serverGroupPermAssign($sgid, $permid, $permvalue, $permnegated = FALSE, $permskip = FALSE)
  {
    $this->execute("servergroupaddperm", array("sgid" => $sgid, "permid" => $permid, "permvalue" => $permvalue, "permnegated" => $permnegated, "permskip" => $permskip));
  }
  
  /**
   * Removes a set of specified permissions from the server group specified with $sgid. Multiple 
   * permissions can be removed at once.
   *
   * @param  integer $sgid
   * @param  integer $permid
   * @return void
   */
  public function serverGroupPermRemove($sgid, $permid)
  {
    $this->execute("servergroupdelperm", array("sgid" => $sgid, "permid" => $permid));
  }
  
  /**
   * Returns a list of clients assigned to the server group specified.
   *
   * @param  integer $sgid
   * @return array
   */
  public function serverGroupClientList($sgid)
  {
    return $this->execute("servergroupclientlist", array("sgid" => $sgid,  "-names"))->toAssocArray("cldbid");
  }
  
  /**
   * Adds a client to the server group specified. Please note that a client cannot be 
   * added to default groups or template groups.
   *
   * @param  integer $sgid
   * @param  integer $cldbid
   * @return void
   */
  public function serverGroupClientAdd($sgid, $cldbid)
  {
    $this->clientListReset();
    
    $this->execute("servergroupaddclient", array("sgid" => $sgid, "cldbid" => $cldbid));
  }
  
  /**
   * Removes a client from the server group specified.
   *
   * @param  integer $sgid
   * @param  integer $cldbid
   * @return void
   */
  public function serverGroupClientDel($sgid, $cldbid)
  {
    $this->execute("servergroupdelclient", array("sgid" => $sgid, "cldbid" => $cldbid));
  }
  
  /**
   * Returns a list of channel groups available.
   *
   * @return array
   */
  public function channelGroupList()
  {
    if($this->cgroupList === null)
    {
      $this->cgroupList = $this->request("channelgrouplist")->toAssocArray("cgid");
      
      foreach($this->cgroupList as $cgid => $group)
      {
        $this->cgroupList[$cgid] = new TeamSpeak3_Node_Channelgroup($this, $group);
      }
    }
    
    return $this->cgroupList;
  }
  
  /**
   * Resets the list of channel groups.
   *
   * @return void
   */
  public function channelGroupListReset()
  {
    $this->cgroupList = null;
  }
  
  /**
   * Creates a new channel group using the name specified with name and returns its ID.
   *
   * @return array
   */
  public function channelGroupCreate($name)
  {
    $this->serverGroupListReset();
    
    $cgid = $this->execute("channelgroupadd", array("name" => $name))->toList();
    
    return $cgid["cgid"];
  }
  
  /**
   * Renames the channel group specified with $cgid.
   *
   * @param  integer $cgid
   * @param  string  $name
   * @return void
   */
  public function channelGroupRename($cgid, $name)
  {
    $this->serverGroupListReset();
    
    $this->execute("channelgrouprename", array("cgid" => $cgid, "name" => $name));
  }
  
  /**
   * Deletes the channel group specified with $cgid. If $force is set to 1, the channel group 
   * will be deleted even if there are clients within.
   *
   * @param  integer $sgid
   * @param  boolean $force
   * @return void
   */
  public function channelGroupDelete($cgid, $force = FALSE)
  {
    $this->serverGroupListReset();
    
    $this->execute("channelgroupdel", array("cgid" => $cgid, "force" => $force));
  }
  
  /**
   * Returns the TeamSpeak3_Node_Channelgroup object matching the given ID.
   *
   * @param  integer $cgid
   * @throws TeamSpeak3_Adapter_ServerQuery_Exception
   * @return TeamSpeak3_Node_Channelgroup
   */
  public function channelGroupGetById($cgid)
  {
    if(!array_key_exists($cgid, $this->channelGroupList()))
    {
      throw new TeamSpeak3_Adapter_ServerQuery_Exception("invalid groupID", 0xA00);
    }
    
    return $this->cgroupList[intval($cgid)];
  }
  
  /**
   * Returns the TeamSpeak3_Node_Channelgroup object matching the given name.
   * 
   * @param  string  $name
   * @param  integer $type
   * @throws TeamSpeak3_Adapter_ServerQuery_Exception
   * @return TeamSpeak3_Node_Channelgroup
   */
  public function channelGroupGetByName($name, $type = TeamSpeak3::GROUP_DBTYPE_REGULAR)
  {
    foreach($this->channelGroupList() as $group)
    {
      if($group["name"] == $name && $group["type"] == $type) return $group;
    }
    
    throw new TeamSpeak3_Adapter_ServerQuery_Exception("invalid groupID", 0xA00);
  }
  
  /**
   * Returns a list of permissions assigned to the channel group specified.
   *
   * @param  integer $cgid
   * @return array
   */
  public function channelGroupPermList($cgid)
  {
    return $this->execute("channelgrouppermlist", array("cgid" => $cgid))->toAssocArray("permid");
  }
  
  /**
   * Adds a set of specified permissions to the channel group specified. Multiple permissions 
   * can be added by providing the two parameters of each permission in seperate arrays.
   *
   * @param  integer $cgid
   * @param  integer $permid
   * @param  integer $permvalue
   * @return void
   */
  public function channelGroupPermAssign($cgid, $permid, $permvalue)
  {
    $this->execute("channelgroupaddperm", array("cgid" => $cgid, "permid" => $permid, "permvalue" => $permvalue));
  }
  
  /**
   * Removes a set of specified permissions from the channel group specified with $cgid. Multiple 
   * permissions can be removed at once.
   *
   * @param  integer $cgid
   * @param  integer $permid
   * @return void
   */
  public function channelGroupPermRemove($cgid, $permid)
  {
    $this->execute("channelgroupdelperm", array("cgid" => $cgid, "permid" => $permid));
  }
  
  /**
   * Returns all the client and/or channel IDs currently assigned to channel groups. All three 
   * parameters are optional so you're free to choose the most suitable combination for your 
   * requirements.
   *
   * @param  integer $cgid
   * @param  integer $cid
   * @param  integer $clid
   * @return array
   */
  public function channelGroupClientList($cgid = null, $cid = null, $clid = null)
  {
    return $this->execute("channelgroupclientlist", array("cgid" => $cgid, "cid" => $cid, "clid" => $clid))->toAssocArray("cldbid");
  }
  
  /**
   * Initializes a file transfer upload. $clientftfid is an arbitrary ID to identify the file transfer on client-side.
   *
   * @param  integer $clientftfid
   * @param  integer $cid
   * @param  string  $name
   * @param  integer $size
   * @param  string  $cpw
   * @param  boolean $overwrite
   * @param  boolean $resume
   * @return array
   */
  public function transferInitUpload($clientftfid, $cid, $name, $size, $cpw = "", $overwrite = FALSE, $resume = FALSE)
  {
    $upload = $this->execute("ftinitupload", array("clientftfid" => $clientftfid, "cid" => $cid, "name" => $name, "cpw" => $cpw, "size" => $size, "overwrite" => $overwrite, "resume" => $resume))->toList();
    
    if(array_key_exists("status", $upload) && $upload["status"] != 0x00)
    {
      throw new TeamSpeak3_Adapter_ServerQuery_Exception($upload["msg"], $upload["status"]);
    }
    
    return $upload;
  }
  
  /**
   * Initializes a file transfer download. $clientftfid is an arbitrary ID to identify the file transfer on client-side.
   *
   * @param  integer $clientftfid
   * @param  integer $cid
   * @param  string  $name
   * @param  string  $cpw
   * @param  integer $seekpos
   * @return array
   */
  public function transferInitDownload($clientftfid, $cid, $name, $cpw = "", $seekpos = 0)
  {
    $download = $this->execute("ftinitdownload", array("clientftfid" => $clientftfid, "cid" => $cid, "name" => $name, "cpw" => $cpw, "seekpos" => $seekpos))->toList();
    
    if(array_key_exists("status", $download) && $download["status"] != 0x00)
    {
      throw new TeamSpeak3_Adapter_ServerQuery_Exception($download["msg"], $download["status"]);
    }
    
    return $download;
  }
  
  /**
   * Displays a list of running file transfers on the selected virtual server. The output contains the path to 
   * which a file is uploaded to, the current transfer rate in bytes per second, etc.
   *
   * @return array
   */
  public function transferList()
  {
    return $this->request("ftlist")->toAssocArray("serverftfid");
  }
  
  /**
   * Stops the running file transfer with server-side ID $serverftfid.
   *
   * @param  integer $serverftfid
   * @param  boolean $delete
   * @return void
   */
  public function transferStop($serverftfid, $delete = FALSE)
  {
    $this->execute("ftstop", array("serverftfid" => $serverftfid, "delete" => $delete));
  }
  
  /**
   * Changes the virtual server configuration using given properties.
   *
   * @param  array $properties
   * @return void
   */
  public function modify(array $properties)
  {
    $this->execute("serveredit", $properties);
    $this->resetNodeInfo();
  }
  
  /**
   * Sends a text message to all clients on the virtual server.
   *
   * @param  string $msg
   * @return void
   */
  public function message($msg)
  {
    $this->execute("sendtextmessage", array("msg" => $msg, "target" => $this->getId(), "targetmode" => TeamSpeak3::TEXTMSG_SERVER));
  }
  
  /**
   * Returns a list of offline messages you've received. The output contains the senders unique identifier, 
   * the messages subject, etc.
   *
   * @return array
   */
  public function messageList()
  {
    $this->request("messagelist")->toAssocArray("msgid");
  }
  
  /**
   * Sends an offline message to the client specified by $cluid.
   *
   * @param  string $cluid
   * @param  string $subject
   * @param  string $message
   * @return void
   */
  public function messageCreate($cluid, $subject, $message)
  {
    $this->execute("messageadd", array("cluid" => $cluid, "subject" => $subject, "message" => $message));
  }
  
  /**
   * Deletes an existing offline message with ID $msgid from your inbox.
   *
   * @param  integer $msgid
   * @return void
   */
  public function messageDelete($msgid)
  {
    $this->execute("messagedel", array("msgid" => $msgid));
  }
  
  /**
   * Returns an existing offline message with ID $msgid from your inbox.
   *
   * @param  integer $msgid
   * @param  boolean $flag_read
   * @return array
   */
  public function messageRead($msgid, $flag_read = TRUE)
  {
    $msg = $this->execute("messageget", array("msgid" => $msgid))->toList();
    
    if($flag_read)
    {
      $this->execute("messageget", array("msgid" => $msgid, "flag" => $flag_read));
    }
    
    return $msg;
  }
  
  /**
   * Creates and returns snapshot data for the virtual server.
   * 
   * @return TeamSpeak3_Helper_String
   */
  public function snapshotCreate()
  {
    return $this->request("serversnapshotcreate")->toString(FALSE);
  }
  
  /**
   * Deploys snapshot data on the virtual server.
   * 
   * @param  string $data
   * @return TeamSpeak3_Helper_String
   */
  public function snapshotDeploy($data)
  {
    return $this->request("serversnapshotcreate " . $data)->toString();
  }
  
  /**
   * Returns a list of permission tokens available.
   *
   * @return array
   */
  public function tokenList()
  {
    return $this->request("tokenlist")->toAssocArray("token");
  }
  
  /**
   * Creates a new permission token and returns the key.
   *
   * @param  integer $type
   * @param  integer $id1
   * @param  integer $id2
   * @return TeamSpeak3_Helper_String
   */
  public function tokenCreate($type = TeamSpeak3::TOKEN_SERVERGROUP, $id1, $id2 = 0, $description = null, $customset = null)
  {
    $token = $this->execute("tokenadd", array("tokentype" => $type, "tokenid1" => $id1, "tokenid2" => $id2, "tokendescription" => $description, "tokencustomset" => $customset))->toList();
    
    return $token["token"];
  }
  
  /**
   * Deletes a token specified by key $token.
   *
   * @param  string $token
   * @return void
   */
  public function tokenDelete($token)
  {
    $this->execute("tokendelete", array("token" => $token));
  }
  
  /**
   * Use a token key gain access to a server or channel group. Please note that the server will 
   * automatically delete the token after it has been used.
   *
   * @param  string $token
   * @return void
   */
  public function tokenUse($token)
  {
    $this->execute("tokenuse", array("token" => $token));
  }
  
  /**
   * Returns a list of custom client properties specified by $ident.
   *
   * @param  string $ident
   * @param  string $pattern
   * @return array
   */
  public function customSearch($ident, $pattern = "%")
  {
    return $this->request("customsearch")->toAssocArray("cldbid");
  }
  
  /**
   * Returns a list of active bans on the selected virtual server.
   *
   * @return array
   */
  public function banList()
  {
    return $this->request("banlist")->toAssocArray("banid");
  }
  
  /**
   * Deletes all active ban rules from the server.
   *
   * @return void
   */
  public function banListClear()
  {
    $this->request("bandelall");
  }
  
  /**
   * Adds a new ban rule on the selected virtual server. All parameters are optional but at least one 
   * of the following rules must be set: ip, name, or uid.
   *
   * @param  array   $rules
   * @param  integer $seconds
   * @param  string  $reason
   * @return integer
   */
  public function banCreate(array $rules, $timeseconds = null, $reason = null)
  {
    $rules["time"] = $timeseconds;
    $rules["banreason"] = $reason;
    
    $banid = $this->execute("banadd", $rules)->toList();
    
    return $banid["banid"];
  }
  
  /**
   * Deletes the specified ban rule from the server.
   *
   * @param  integer $banid
   * @return void
   */
  public function banDelete($banid)
  {
    $this->execute("bandel", array("banid" => $banid));
  }
  
  /**
   * Returns a list of complaints on the selected virtual server. If $tcldbid is specified, only 
   * complaints about the targeted client will be shown.
   *
   * @param  integer $tcldbid
   * @return array
   */
  public function complaintList($tcldbid = null)
  {
    return $this->execute("complainlist", array("tcldbid" => $tcldbid))->toArray();
  }
  
  /**
   * Deletes all active complaints about the client with database ID $tcldbid from the server.
   *
   * @param  integer $tcldbid
   * @return void
   */
  public function complaintListClear($tcldbid)
  {
    $this->execute("complaindelall", array("tcldbid" => $tcldbid));
  }
  
  /**
   * Submits a complaint about the client with database ID $tcldbid to the server.
   *
   * @param  integer $tcldbid
   * @param  string  $message
   * @return void
   */
  public function complaintCreate($tcldbid, $message)
  {
    $this->execute("complainadd", array("tcldbid" => $tcldbid, "message" => $message));
  }
  
  /**
   * Deletes the complaint about the client with ID $tcldbid submitted by the client with ID $fcldbid from the server.
   *
   * @param  integer $tcldbid
   * @param  integer $fcldbid
   * @return void
   */
  public function complaintDelete($tcldbid, $fcldbid)
  {
    $this->execute("complaindel", array("tcldbid" => $tcldbid, "fcldbid" => $fcldbid));
  }
  
  /**
   * Returns a specified number of entries from the servers log.
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
   * Writes a custom entry into the virtual server log.
   *
   * @param  string  $logmsg
   * @param  integer $loglevel
   * @return void
   */
  public function logAdd($logmsg, $loglevel = TeamSpeak3::LOGLEVEL_INFO)
  {
    $this->execute("logadd", array("logmsg" => $logmsg, "loglevel" => $loglevel));
  }
  
  /**
   * Deletes the virtual server.
   *
   * @return void
   */
  public function delete()
  {
    $this->getParent()->serverDelete($this->getId());
    
    unset($this);
  }
  
  /**
   * Starts the virtual server.
   *
   * @return void
   */
  public function start()
  {
    $this->getParent()->serverStart($this->getId());
  }
  
  /**
   * Stops the virtual server.
   *
   * @return void
   */
  public function stop()
  {
    $this->getParent()->serverStop($this->getId());
  }
  
  /**
   * Changes the properties of your own client connection.
   *
   * @param  array $properties
   * @return void
   */
  public function selfUpdate(array $properties)
  {
    $this->execute("clientupdate", $properties);
    
    foreach($properties as $ident => $value)
    {
      $this->whoamiSet($ident, $value);
    }
  }
  
  /**
   * Updates your own ServerQuery login credentials using a specified username. The password 
   * will be auto-generated.
   *
   * @param  string $username
   * @return TeamSpeak3_Helper_String
   */
  public function selfUpdateLogin($username)
  {
    $password = $this->execute("clientsetserverquerylogin", array("client_login_name" => $username))->toList();
    
    return $password["client_login_password"];
  }
  
  /**
   * Returns an array containing the permission overview of yur own client.
   *
   * @return array
   */
  public function selfPermOverview()
  {
    return $this->execute("permoverview", array("cldbid" => $this->whoamiGet("client_database_id"), "cid" => $this->whoamiGet("client_channel_id"), "permid" => 0))->toArray();
  }
  
  /**
   * @ignore
   */
  protected function fetchNodeList()
  {
    $this->nodeList = array();

    foreach($this->channelList() as $channel)
    {
      if($channel["pid"] == 0)
      {
        $this->nodeList[] = $channel;
      }
    }
  }
  
  /**
   * @ignore
   */
  protected function fetchNodeInfo()
  {
    $this->nodeInfo = array_merge($this->nodeInfo, $this->request("serverinfo")->toList());
  }
  
  /**
   * Internal callback funtion for sorting of client objects.
   *
   * @param  TeamSpeak3_Node_Client $a
   * @param  TeamSpeak3_Node_Client $b
   * @return integer
   */
  public static function sortClientList(TeamSpeak3_Node_Client $a, TeamSpeak3_Node_Client $b)
  {
    if(get_class($a) != get_class($b))
    {
      throw new TeamSpeak3_Adapter_ServerQuery_Exception("invalid parameter", 0x602);
    }
    
    if(!$a instanceof TeamSpeak3_Node_Client)
    {
      throw new TeamSpeak3_Adapter_ServerQuery_Exception("convert error", 0x604);
    }
    
    if($a["client_talk_power"] != $b["client_talk_power"])
    {
      return ($a["client_talk_power"] > $b["client_talk_power"]) ? -1 : 1;
    }
    
    if($a["client_is_talker"] != $b["client_is_talker"])
    {
      return ($a["client_is_talker"] > $b["client_is_talker"]) ? -1 : 1;
    }
        
    return strcmp(strtolower($a["client_nickname"]), strtolower($b["client_nickname"]));
  }
  
  /**
   * Returns a unique identifier for the node which can be used as a HTML property.
   * 
   * @return string
   */
  public function getUniqueId()
  {
    return $this->getParent()->getUniqueId() . "_s" . $this->getId();
  }

  /**
   * Returns the name of a possible icon to display the node object.
   *
   * @return string
   */
  public function getIcon()
  {
    if($this["virtualserver_clientsonline"]-$this["virtualserver_queryclientsonline"] >= $this["virtualserver_maxclients"])
    {
      return "server_full";
    }
    elseif($this["virtualserver_flag_password"])
    {
      return "server_pass";
    }
    else
    {
      return "server_open";
    }
  }
  
  /**
   * Returns a symbol representing the node.
   * 
   * @return string
   */
  public function getSymbol()
  {
    return "$";
  }
  
  /**
   * Returns a string representation of this node.
   *
   * @return string
   */
  public function __toString()
  {
    return (string) $this["virtualserver_name"];
  }
}

