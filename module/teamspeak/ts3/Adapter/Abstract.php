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
 * Provides low-level methods for concrete adapters to communicate with a TeamSpeak 3 Server.
 * 
 * @package  TeamSpeak3_Adapter_Abstract
 * @category TeamSpeak3_Adapter
 */
abstract class TeamSpeak3_Adapter_Abstract
{
  /**
   * Stores an TeamSpeak3_Transport_Abstract object.
   *
   * @var TeamSpeak3_Transport_Abstract
   */
  protected $transport = null;
  
  /**
   * The TeamSpeak3_Adapter_Abstract constructor.
   *
   * @param  TeamSpeak3_Transport_Abstract $transport
   * @throws TeamSpeak3_Adapter_Exception
   * @return TeamSpeak3_Adapter_Abstract
   */
  abstract public function __construct(TeamSpeak3_Transport_Abstract $transport);
  
  /**
   * The TeamSpeak3_Adapter_Abstract destructor.
   *
   * @return void
   */
  abstract public function __destruct();
  
  /**
   * Returns the profiler timer used for this connection.
   *
   * @return TeamSpeak3_Helper_Profiler_Timer
   */
  public function getProfiler()
  {
    return TeamSpeak3_Helper_Profiler::get(spl_object_hash($this));
  }
  
  /**
   * Returns the transport object used for the connection.
   *
   * @return TeamSpeak3_Transport_Abstract
   */
  public function getTransport()
  {
    return $this->transport;
  }
  
  /**
   * Returns the hostname or IPv4 address the transport is connected to.
   *
   * @return string
   */
  public function getTransportHost()
  {
    $config = $this->getTransport()->getConfig();
    
    return $config["host"];
  }
  
  /**
   * Returns the port number of the server the transport is connected to.
   *
   * @return string
   */
  public function getTransportPort()
  {
    $config = $this->getTransport()->getConfig();
    
    return $config["port"];
  }
}
