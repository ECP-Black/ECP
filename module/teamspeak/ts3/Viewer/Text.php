<?php

/**
 * TeamSpeak 3 PHP Framework
 *
 * $Id: Text.php 2010-01-18 21:54:35 sven $
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
 * Renders nodes used in TeamSpeak 3 text viewers.
 * 
 * @package  TeamSpeak3_Viewer_Text
 * @category TeamSpeak3_Viewer
 */
class TeamSpeak3_Viewer_Text implements TeamSpeak3_Viewer_Interface
{
  /**
   * Returns the HTML code needed to display this node in a TeamSpeak 3 viewer.
   *
   * @param  TeamSpeak3_Node_Abstract $node
   * @return string
   */
  public function fetchObject(TeamSpeak3_Node_Abstract $node, array $siblings = array())
  {
    $prefix = "";
    
    if(count($siblings))
    {
      $last = array_pop($siblings);
            
      foreach($siblings as $level) $prefix .= ($level) ? "&#9474;" : "&nbsp;";
      
      $prefix .= ($last) ? "&#9492;" : "&#9500;";
    }
    
    return $prefix . $node->getSymbol() . " " . htmlspecialchars($node) . "\n";
  }
}
