<?php

/**
 * TeamSpeak 3 PHP Framework
 *
 * $Id: Servergroup.php 2010-01-18 21:54:35 sven $
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
 * Class describing a TeamSpeak 3 server group and all it's parameters.
 * 
 * @package  TeamSpeak3_Node_Servergroup
 * @category TeamSpeak3_Node
 */
class TeamSpeak3_Node_Servergroup extends TeamSpeak3_Node_Abstract
{
  /**
   * The TeamSpeak3_Node_Servergroup constructor.
   *
   * @param  TeamSpeak3_Node_Server $server
   * @param  array  $info
   * @param  string $index
   * @throws TeamSpeak3_Node_Exception
   * @return TeamSpeak3_Node_Servergroup
   */
  public function __construct(TeamSpeak3_Node_Server $host, array $info, $index = "sgid")
  {
    $this->parent = $host;
    $this->nodeInfo = $info;

    if(!array_key_exists($index, $this->nodeInfo))
    {
      throw new TeamSpeak3_Node_Exception("invalid groupID", 0xA00);
    }

    $this->nodeId = $this->nodeInfo[$index];
  }
  
  /**
   * Renames the server group specified.
   *
   * @param  string  $name
   * @return void
   */
  public function rename($name)
  {
    return $this->getParent()->$this->serverGroupRename($this->getId(), $name);
  }
  
  /**
   * Deletes the server group. If $force is set to 1, the server group will be 
   * deleted even if there are clients within.
   *
   * @param  boolean $force
   * @return void
   */
  public function delete($force = FALSE)
  {
    $this->getParent()->$this->serverGroupDelete($this->getId(), $force);
    
    unset($this);
  }
  
  /**
   * Returns a list of permissions assigned to the server group.
   *
   * @return array
   */
  public function permList()
  {
    return $this->getParent()->serverGroupPermList($this->getId());
  }
  
  /**
   * Adds a set of specified permissions to the server group. Multiple permissions 
   * can be added by providing the four parameters of each permission in seperate arrays.
   *
   * @param  integer $permid
   * @param  integer $permvalue
   * @param  integer $permnegated
   * @param  integer $permskip
   * @return void
   */
  public function permAssign($permid, $permvalue, $permnegated = FALSE, $permskip = FALSE)
  {
    return $this->getParent()->serverGroupPermAssign($this->getId(), $permid, $permvalue, $permnegated, $permskip);
  }
  
  /**
   * Adds a permission to the server group.
   *
   * @param  string $permname
   * @param  integer $permvalue
   * @param  integer $permnegated
   * @param  integer $permskip
   * @return void
   */
  public function permAssignByName($permname, $permvalue, $permnegated = FALSE, $permskip = FALSE)
  {
    $permid = $this->getParent()->permissionGetIdByName($permname);
    
    return $this->permAssign($permid, $permvalue, $permnegated, $permskip);
  }
  
  /**
   * Removes a set of specified permissions from the server group. Multiple 
   * permissions can be removed at once.
   *
   * @param  integer $permid
   * @return void
   */
  public function permRemove($permid)
  {
    return $this->getParent()->serverGroupPermRemove($this->getId(), $permid);
  }
  
  /**
   * Removes a permission from the server group.
   *
   * @param  string $permname
   * @return void
   */
  public function permRemoveByName($permname)
  {
    $permid = $this->getParent()->permissionGetIdByName($permname);
    
    return $this->permRemove($permid);
  }
  
  /**
   * Returns a list of clients assigned to the server group specified.
   *
   * @return array
   */
  public function clientList()
  {
    return $this->getParent()->serverGroupClientList($this->getId());
  }
  
  /**
   * Adds a client to the server group specified. Please note that a client cannot be 
   * added to default groups or template groups.
   *
   * @param  integer $cldbid
   * @return void
   */
  public function clientAdd($cldbid)
  {
    return $this->getParent()->serverGroupClientAdd($this->getId(), $cldbid);
  }
  
  /**
   * Removes a client from the server group.
   *
   * @param  integer $cldbid
   * @return void
   */
  public function clientDel($cldbid)
  {
    return $this->getParent()->serverGroupClientDel($this->getId(), $cldbid);
  }
  
  /**
   * @ignore
   */
  protected function fetchNodeList()
  {
    $this->nodeList = array();
    
    foreach($this->getParent()->clientList() as $client)
    {
      if(in_array($this->getId(), explode(",", $client["client_servergroups"])))
      {
        $this->nodeList[] = $client;
      }
    }
  }
  
  /**
   * Returns a unique identifier for the node which can be used as a HTML property.
   * 
   * @return string
   */
  public function getUniqueId()
  {
    return $this->getParent()->getUniqueId() . "_sg" . $this->getId();
  }
  
  /**
   * Returns the name of a possible icon to display the node object.
   *
   * @return string
   */
  public function getIcon()
  {
    return "group_server";
  }
  
  /**
   * Returns a symbol representing the node.
   * 
   * @return string
   */
  public function getSymbol()
  {
    return "&sect;";
  }
  
  /**
   * Returns a string representation of this node.
   *
   * @return string
   */
  public function __toString()
  {
    return (string) $this["name"];
  }
}

