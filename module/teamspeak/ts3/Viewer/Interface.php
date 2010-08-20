<?php

/**
 * TeamSpeak 3 PHP Framework
 *
 * $Id: Interface.php 2010-01-18 21:54:35 sven $
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
 * Interface describing a TeamSpeak 3 viewer.
 * 
 * @package  TeamSpeak3_Viewer_Interface
 * @category TeamSpeak3_Viewer
 */
interface TeamSpeak3_Viewer_Interface
{
  /**
   * Returns the HTML code needed to display this node in a TeamSpeak 3 viewer.
   *
   * @param  TeamSpeak3_Node_Abstract $node
   * @return string
   */
  public function fetchObject(TeamSpeak3_Node_Abstract $node, array $siblings = array());
}
