<?php

/**
 * TeamSpeak 3 PHP Framework
 *
 * $Id: Abstract.php 2010-01-18 21:54:35 sven $
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
 * Abstract class describing a TeamSpeak 3 node and all it's parameters.
 * 
 * @package  TeamSpeak3_Node_Abstract
 * @category TeamSpeak3_Node
 */
abstract class TeamSpeak3_Node_Abstract implements RecursiveIterator, ArrayAccess, Countable
{
  /**
   * @ignore 
   */
  protected $parent = null;

  /**
   * @ignore 
   */
  protected $server = null;
  
  /**
   * @ignore 
   */
  protected $nodeId = 0x00;

  /**
   * @ignore 
   */
  protected $nodeList = null;

  /**
   * @ignore
   */
  protected $nodeInfo = array();
  
  /**
   * Sends a prepared command to the server and returns the result.
   *
   * @param  string $cmd
   * @return TeamSpeak3_Adapter_ServerQuery_Reply
   */
  public function request($cmd)
  {
    return $this->getParent()->request($cmd);
  }
  
  /**
   * Uses given parameters and returns a prepared ServerQuery command.
   *
   * @param  string $cmd
   * @param  array $params
   * @return TeamSpeak3_Helper_String
   */
  public function prepare($cmd, array $params = array())
  {
    return $this->getParent()->prepare($cmd, $params);
  }
  
  /**
   * Prepares and executes a ServerQuery command and returns the result.
   *
   * @param  string $cmd
   * @param  array $params
   * @return TeamSpeak3_Adapter_ServerQuery_Reply
   */
  public function execute($cmd, array $params = array())
  {
    $cmd = $this->prepare($cmd, $params);
    
    return $this->request($cmd);
  }

  /**
   * Returns the parent object of the current node.
   * 
   * @throws TeamSpeak3_Node_Exception
   * @return mixed
   */
  public function getParent()
  {
    return $this->parent;
  }
  
  /**
   * Returns the primary ID of the current node.
   *
   * @return integer
   */
  public function getId()
  {
    return $this->nodeId;
  }

  /**
   * Returns a unique identifier for the node which can be used as a HTML property.
   * 
   * @return string
   */
  abstract public function getUniqueId();
  
  /**
   * Returns the name of a possible icon to display the node object.
   *
   * @return string
   */
  abstract public function getIcon();
  
  /**
   * Returns a symbol representing the node.
   * 
   * @return string
   */
  abstract public function getSymbol();
  
  /**
   * Returns the HTML code to display a TeamSpeak 3 viewer.
   *
   * @param  TeamSpeak3_Viewer_Interface $viewer
   * @return string
   */
  public function getViewer(TeamSpeak3_Viewer_Interface $viewer)
  {
    $html = $viewer->fetchObject($this);
    
    $iterator = new RecursiveIteratorIterator($this, RecursiveIteratorIterator::SELF_FIRST);
    
    foreach($iterator as $node)
    {
      $siblings = array();
      
      for($level = 0; $level < $iterator->getDepth(); $level++)
      {
        $siblings[] = ($iterator->getSubIterator($level)->hasNext()) ? 1 : 0;
      }
      
      $siblings[] = (!$iterator->getSubIterator($level)->hasNext()) ? 1 : 0;
      
      $html .= $viewer->fetchObject($node, $siblings);
    }
    
    return $html;
  }
  
  /**
   * Returns all information available on this node.
   *
   * @return array
   */
  public function getInfo()
  {
    $this->fetchNodeInfo();
    
    return $this->nodeInfo;
  }
  
  /**
   * Returns a string representation of this node.
   *
   * @return string
   */
  public function __toString()
  {
    return get_class($this);
  }
  
  /**
   * Returns a string representation of this node.
   *
   * @return string
   */
  public function toString()
  {
    return $this->__toString();
  }
  
  /**
   * Returns an assoc array filled with current node info properties.
   *
   * @return array
   */
  public function toArray()
  {
    return $this->nodeList;
  }
  
  /**
   * Called whenever we're using an unknown method.
   *
   * @param  string $name
   * @param  array  $args
   * @throws TeamSpeak3_Adapter_ServerQuery_Exception
   * @return void
   */
  public function __call($name, array $args)
  {
    if($this->getParent() instanceof TeamSpeak3_Node_Abstract)
    {
      return call_user_func_array(array($this->getParent(), $name), $args);
    }
    
    throw new TeamSpeak3_Node_Exception("node method '" . $name . "()' does not exist");
  }

  /**
   * @ignore
   */
  protected function fetchNodeList()
  {
    $this->nodeList = array();
  }

  /**
   * @ignore
   */
  protected function fetchNodeInfo()
  {
    return;
  }
  
  /**
   * @ignore
   */
  protected function resetNodeInfo()
  {
    $this->nodeInfo = array();
  }

  /**
   * @ignore
   */
  protected function verifyNodeList()
  {
    if($this->nodeList === null)
    {
      $this->fetchNodeList();
    }
  }
  
  /**
   * @ignore
   */
  protected function resetNodeList()
  {
    $this->nodeList = null;
  }
  
  /**
   * @ignore
   */
  public function count()
  {
    $this->verifyNodeList();

    return count($this->nodeList);
  }

  /**
   * @ignore 
   */
  public function current()
  {
    $this->verifyNodeList();

    return current($this->nodeList);
  }

  /**
   * @ignore 
   */
  public function getChildren()
  {
    $this->verifyNodeList();

    return $this->current();
  }

  /**
   * @ignore 
   */    
  public function hasChildren()
  {
    $this->verifyNodeList();

    return $this->current()->count() > 0;
  }
  
  /**
   * @ignore 
   */
  public function hasNext()
  {
    $this->verifyNodeList();
    
    return $this->key()+1 < $this->count();
  }

  /**
   * @ignore 
   */    
  public function key()
  {
    $this->verifyNodeList();

    return key($this->nodeList);
  }
  
  /**
   * @ignore 
   */    
  public function valid()
  {
    $this->verifyNodeList();

    return $this->key() !== null;
  }

  /**
   * @ignore 
   */    
  public function next()
  {
    $this->verifyNodeList();

    return next($this->nodeList);
  }

  /**
   * @ignore 
   */   
  public function rewind()
  {
    $this->verifyNodeList();

    return reset($this->nodeList);
  }

  /**
   * @ignore 
   */    
  public function offsetExists($offset)
  {
    return array_key_exists($offset, $this->nodeInfo) ? TRUE : FALSE;
  }

  /**
   * @ignore 
   */
  public function offsetGet($offset)
  {
    if(!$this->offsetExists($offset))
    {
      $this->fetchNodeInfo();
    }

    return ($this->offsetExists($offset)) ? $this->nodeInfo[$offset] : null;
  }

  /**
   * @ignore 
   */    
  public function offsetSet($offset, $value)
  {
    if(method_exists($this, "modify"))
    {
      return $this->modify(array($offset => $value));
    }
    
    throw new TeamSpeak3_Node_Exception("node '" . get_class($this) . "' is read only");
  }

  /**
   * @ignore 
   */    
  public function offsetUnset($offset)
  {
    unset($this->nodeInfo[$offset]);
  }
}
