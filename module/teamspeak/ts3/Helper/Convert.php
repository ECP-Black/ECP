<?php

/**
 * TeamSpeak 3 PHP Framework
 *
 * $Id: Convert.php 2010-01-18 21:54:35 sven $
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
 * Helper class for data conversion.
 * 
 * @package  TeamSpeak3_Helper_Convert
 * @category TeamSpeak3_Helper
 */
class TeamSpeak3_Helper_Convert
{
  /**
   * Converts bytes to a human readable value.
   *
   * @param  integer $bytes
   * @return string
   */
  public static function bytes($bytes)
  {
    $kbytes = sprintf("%.02f", $bytes/1024);
    $mbytes = sprintf("%.02f", $kbytes/1024);
    $gbytes = sprintf("%.02f", $mbytes/1024);
    $tbytes = sprintf("%.02f", $gbytes/1024);

    if($gbytes >= 1) return $gbytes." GB";
    if($mbytes >= 1) return $mbytes." MB";
    if($kbytes >= 1) return $kbytes." KB";
    return $bytes." B";
  }

  /**
   * Converts seconds/milliseconds to a human readable value.
   *
   * @param  integer $seconds
   * @param  boolean $is_ms
   * @return string
   */
  public static function seconds($seconds, $is_ms = TRUE)
  {
    if($is_ms) $seconds = $seconds/1000;
    
    return sprintf("%dD %02d:%02d:%02d", $seconds/60/60/24, ($seconds/60/60)%24, ($seconds/60)%60, $seconds%60);
  }

  /**
   * Converts a given codec ID to a human readable name.
   *
   * @param  integer $codec
   * @return string
   */
  public static function codec($codec)
  {
    if($codec == TeamSpeak3::CODEC_SPEEX_NARROWBAND) return "Speex Narrowband (8 kHz)";
    if($codec == TeamSpeak3::CODEC_SPEEX_WIDEBAND) return "Speex Wideband (16 kHz)";
    if($codec == TeamSpeak3::CODEC_SPEEX_ULTRAWIDEBAND) return "Speex Ultra-Wideband (32 kHz)";
    if($codec == TeamSpeak3::CODEC_CELT_MONO) return "CELT Mono (48 kHz)";
    return "Unknown";
  }
}
