<?php

/**
 * TeamSpeak 3 PHP Framework
 *
 * $Id: Html.php 2010-01-18 21:54:35 sven $
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
 * Renders nodes used in TeamSpeak 3 html viewers.
 * 
 * @package  TeamSpeak3_Viewer_Html
 * @category TeamSpeak3_Viewer
 */
class TeamSpeak3_Viewer_Html implements TeamSpeak3_Viewer_Interface
{
  /**
   * Stores the URI path where the images used by the viewer can be found.
   *
   * @var string
   */
  protected $imagepath = null;
  
  /**
   * The TeamSpeak3_Viewer_Html constructor.
   *
   * @param  string $iconpath
   * @return void
   */
  public function __construct($imagepath = "images/viewer/")
  {
    $this->imagepath = $imagepath;
  }
  
  /**
   * Returns the HTML code needed to display this node in a TeamSpeak 3 viewer.
   *
   * @param  TeamSpeak3_Node_Abstract $node
   * @return string
   */
  public function fetchObject(TeamSpeak3_Node_Abstract $node, array $siblings = array())
  {
    $prefix = "";
    $suffix = "";
    $detail = "";

    if(count($siblings))
    {
      $last = array_pop($siblings);

      foreach($siblings as $level) $prefix .=  ($level) ? $this->getImage("16x16_tree_line.gif") : $this->getImage("16x16_tree_blank.png");

      $prefix .= ($last) ? $this->getImage("16x16_tree_end.gif") : $this->getImage("16x16_tree_mid.gif");
    }

    if($node instanceof TeamSpeak3_Node_Server)
    {
      $detail .= "ID: " . $node->getId() . " | Clients: " . $node->clientCount() . "/" . $node["virtualserver_maxclients"] . " | Uptime: " . TeamSpeak3_Helper_Convert::seconds($node["virtualserver_uptime"]);
    }
    elseif($node instanceof TeamSpeak3_Node_Channel)
    {
      $suffix .= $this->fetchSuffixChannel($node);
      $detail .= "ID: " . $node->getId() . " | Codec: " . TeamSpeak3_Helper_Convert::codec($node["channel_codec"]) . " | Quality: " . $node["channel_codec_quality"];
    }
    elseif($node instanceof TeamSpeak3_Node_Client)
    {
      $suffix .= $this->fetchSuffixClient($node);
      $detail .= "ID: " . $node->getId() . " | Version: " . $node["client_version"] . " | Platform: " . $node["client_platform"];
    }

    return "<div id='" . $node->getUniqueId() . "'>" . $prefix . $this->getImage("16x16_" . $node->getIcon() . ".png") . " <span title='" . $detail . "'>" . htmlspecialchars($node) . " " . $suffix . "</span></div>\n";
  }

  /**
   * Returns the HTML code to display channel node status icons.
   *
   * @param  TeamSpeak3_Node_Channel $channel
   * @return string
   */
  protected function fetchSuffixChannel(TeamSpeak3_Node_Channel $channel)
  {
    $html = "";
    
    if($channel["channel_flag_default"])
    {
      $html .= $this->getImage("16x16_channel_flag_default.png", "Default Channel");
    }

    if($channel["channel_flag_password"])
    {
      $html .= $this->getImage("16x16_channel_flag_password.png", "Password-protected");
    }

    if($channel["channel_codec"] == 3)
    {
      $html .= $this->getImage("16x16_channel_flag_music.png", "Music Codec");
    }

    if($channel["channel_needed_talk_power"])
    {
      $html .= $this->getImage("16x16_channel_flag_moderated.png", "Moderated");
    }
    
    return $html;
  }
  
  /**
   * Returns the HTML code to display channel node status icons.
   *
   * @param  TeamSpeak3_Node_Client $client
   * @return string
   */
  protected function fetchSuffixClient(TeamSpeak3_Node_Client $client)
  {
    $html = "";
    
    if($client["client_is_talker"])
    {
      $html .= $this->getImage("16x16_client_talker.png", "Talk Power granted");
    }
    
    /* allowed icon IDs */
    $icon = array(100, 200, 300);
    
    foreach($client->memberOf() as $group)
    {
      if($group["iconid"] && in_array($group["iconid"], $icon))
      {
        $html .= $this->getImage("16x16_group_icon_" . $group["iconid"] . ".png", $group);
      }
    }
    
    return $html;
  }
  
  /**
   * Returns the HTML code to display an image tag.
   *
   * @param  string $name
   * @param  string $text
   * @return string
   */
  protected function getImage($name, $text = "")
  {
    return "<img src='" . $this->imagepath . $name . "' title='" . $text . "' alt='' align='top' />";
  }
}
