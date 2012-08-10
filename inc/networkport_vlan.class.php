<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2012 by the INDEPNET Development Team.

 http://indepnet.net/   http://glpi-project.org
 -------------------------------------------------------------------------

 LICENSE

 This file is part of GLPI.

 GLPI is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; either version 2 of the License, or
 (at your option) any later version.

 GLPI is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with GLPI. If not, see <http://www.gnu.org/licenses/>.
 --------------------------------------------------------------------------
 */

// ----------------------------------------------------------------------
// Original Author of file: Remi Collet
// Purpose of file:
// ----------------------------------------------------------------------

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}
class NetworkPort_Vlan extends CommonDBRelation {

   // From CommonDBRelation
   public $itemtype_1 = 'NetworkPort';
   public $items_id_1 = 'networkports_id';

   public $itemtype_2 = 'Vlan';
   public $items_id_2 = 'vlans_id';


   /**
    * Get search function for the class
    *
    * @return array of search option
   **/
   function getSearchOptions() {

      $tab = parent::getSearchOptions();
      return $tab;
   }


   /**
    * @param $ID
   **/
   function unassignVlanbyID($ID) {
      global $DB;

      $query = "SELECT *
                FROM `glpi_networkports_vlans`
                WHERE `id` = '$ID'";
      if ($result = $DB->query($query)) {
         $data = $DB->fetch_assoc($result);

         // Delete VLAN
         $query = "DELETE
                   FROM `glpi_networkports_vlans`
                   WHERE `id` = '$ID'";
         $DB->query($query);

         // Delete Contact VLAN if set
         $np = new NetworkPort();
         if ($contact_id = $np->getContact($data['networkports_id'])) {
            $query = "DELETE
                      FROM `glpi_networkports_vlans`
                      WHERE `networkports_id` = '$contact_id'
                            AND `vlans_id` = '" . $data['vlans_id'] . "'";
            $DB->query($query);
         }
      }
   }


   /**
    * @param $portID
    * @param $vlanID
   **/
   function unassignVlan($portID, $vlanID) {
      global $DB;

      $ok = true;
      $query = "DELETE
                FROM `glpi_networkports_vlans`
                WHERE `networkports_id` = '$portID'
                      AND `vlans_id` = '$vlanID'";
      if (!$DB->query($query)) {
         $ok = false;
      }

      // Delete Contact VLAN if set
      $np = new NetworkPort();
      if ($contact_id=$np->getContact($portID)) {
         $query = "DELETE
                   FROM `glpi_networkports_vlans`
                   WHERE `networkports_id` = '$contact_id'
                         AND `vlans_id` = '$vlanID'";
         if (!$DB->query($query)) {
            $ok = false;
         }
      }
      return $ok;
   }


   /**
    * @param $port
    * @param $vlan
    * @param $tagged
   **/
   function assignVlan($port, $vlan, $tagged) {
      global $DB;

      $ok = true;
      $query = "INSERT INTO `glpi_networkports_vlans`
                       (`networkports_id`,`vlans_id`,`tagged`)
                VALUES ('$port','$vlan','$tagged')";
      if (!$DB->query($query)) {
         $ok = false;
      }

      $np = new NetworkPort();
      if ($contact_id=$np->getContact($port)) {
         if ($np->getFromDB($contact_id)) {
            $vlans = self::getVlansForNetworkPort($port);
            if (!in_array($vlan,$vlans)) {
               $query = "INSERT INTO `glpi_networkports_vlans`
                                (`networkports_id`,`vlans_id`,`tagged`)
                         VALUES ('$contact_id','$vlan','$tagged')";
               if (!$DB->query($query)) {
                  $ok = false;
               }
            }
         }
      }
      return $ok;
   }


   /**
    * @since version 0.84
    *
    * @param $itemtype
    * @param $base                  HTMLTableBase object
    * @param $super                 HTMLTableSuperHeader object (default NULL)
    * @param $father                HTMLTableHeader object (default NULL)
    * @param $options      array
   **/
   static function getHTMLTableHeader($itemtype, HTMLTableBase $base,
                                      HTMLTableSuperHeader $super=NULL,
                                      HTMLTableHeader $father=NULL, array $options=array()) {

      $column_name = __CLASS__;

      if (isset($options['dont_display'][$column_name])) {
         return;
      }

      $base->addHeader($column_name, Vlan::getTypeName(), $super, $father);
   }


   /**
    * @since version 0.84
    *
    * @param $row                HTMLTableRow object
    * @param $item               CommonDBTM object (default NULL)
    * @param $father             HTMLTableCell object (default NULL)
    * @param $options   array
   **/
   static function getHTMLTableCellsForItem(HTMLTableRow $row=NULL, CommonDBTM $item=NULL,
                                            HTMLTableCell $father=NULL, array $options=array()) {
      global $DB, $CFG_GLPI;

      $column_name = __CLASS__;

      if (isset($options['dont_display'][$column_name])) {
         return;
      }

      if (empty($item)) {
         if (empty($father)) {
            return;
         }
         $item = $father->getItem();
      }

      $canedit = (isset($options['canedit']) && $options['canedit']);

      $query = "SELECT `glpi_networkports_vlans`.*,
                       `glpi_vlans`.`tag` AS vlantag,
                       `glpi_vlans`.`comment` AS vlancomment
                FROM `glpi_networkports_vlans`
                LEFT JOIN `glpi_vlans`
                        ON (`glpi_networkports_vlans`.`vlans_id` = `glpi_vlans`.`id`)
                WHERE `networkports_id` = '".$item->getID()."'";

      foreach ($DB->request($query) as $line) {
         if (isset($line["tagged"]) && ($line["tagged"] == 1)) {
            $content = sprintf(__('%1$s - %2$s'),
                               Dropdown::getDropdownName("glpi_vlans", $line["vlans_id"]),
                               __('Tagged'));
         } else {
            $content = sprintf(__('%1$s - %2$s'),
                               Dropdown::getDropdownName("glpi_vlans", $line["vlans_id"]),
                               __('Untagged'));
         }
         $content .= Html::showToolTip(sprintf(__('%1$s: %2$s'),
                                               __('ID TAG'), $line['vlantag'])."<br>".
                                       sprintf(__('%1$s: %2$s'),
                                               __('Comments'), $line['vlancomment']),
                                       array('display' => false));
         if ($canedit) {
            $content .= "<a href='" . $CFG_GLPI["root_doc"] .
                         "/front/networkport.form.php?unassign_vlan=" . "unassigned&amp;id=" .
                         $line["id"] . "'>";
            $content .= "<img src=\"" . $CFG_GLPI["root_doc"] . "/pics/delete.png\" alt=\"" .
                          __s('Dissociate') . "\" title=\"" . __s('Dissociate') . "\"></a>";
         }

         $this_cell = $row->addCell($row->getHeaderByName($column_name), $content, $father);
      }
   }


   /**
    * @param $ID
    * @param $canedit
    * @param $withtemplate
   **/
   static function showForNetworkPort($ID, $canedit, $withtemplate) {
      global $DB, $CFG_GLPI;

      $used = array();

      $query = "SELECT `glpi_networkports_vlans`.*,
                       `glpi_vlans`.`tag` AS vlantag,
                       `glpi_vlans`.`comment` AS vlancomment
                FROM `glpi_networkports_vlans`
                LEFT JOIN `glpi_vlans`
                        ON (`glpi_networkports_vlans`.`vlans_id` = `glpi_vlans`.`id`)
                WHERE `networkports_id` = '$ID'";

      $result = $DB->query($query);
      if ($DB->numrows($result) > 0) {
         echo "\n<table>";
         while ($line = $DB->fetch_assoc($result)) {
            $used[$line["vlans_id"]] = $line["vlans_id"];
            echo "<tr><td>";
            if ((isset($line["tagged"])) && ($line["tagged"] == 1)) {
               printf(__('%1$s - %2$s'),
                      Dropdown::getDropdownName("glpi_vlans", $line["vlans_id"]), __('Tagged'));
            } else {
               printf(__('%1$s - %2$s'),
                      Dropdown::getDropdownName("glpi_vlans", $line["vlans_id"]), __('Untagged'));
            }
            Html::showToolTip(sprintf(__('%1$s: %2$s'), __('ID TAG'),  $line['vlantag'])."<br>".
                              sprintf(__('%1$s: %2$s'), __('Comments'),  $line['vlancomment']));


            echo "</td>\n<td>";
            if ($canedit) {
               echo "<a href='" . $CFG_GLPI["root_doc"] . "/front/networkport.form.php?unassign_vlan=".
                     "unassigned&amp;id=" . $line["id"] . "'>";
               echo "<img src=\"" . $CFG_GLPI["root_doc"] . "/pics/delete.png\" alt=\"" .
                     __s('Dissociate') . "\" title=\"" . __s('Dissociate') . "\"></a>";
            } else {
               echo "&nbsp;";
            }
            echo "</td></tr>\n";
         }
         echo "</table>";
      } else {
         echo "&nbsp;";
      }
      return $used;
   }


   /**
    * @param $ID
   **/
   static function showForNetworkPortForm ($ID) {
      global $DB, $CFG_GLPI;

      $port = new NetworkPort();

      if ($ID
          && $port->can($ID,'w')) {

         echo "\n<div class='center'>";
         echo "<form method='post' action='".$CFG_GLPI["root_doc"]."/front/networkport.form.php'>";
         echo "<input type='hidden' name='networkports_id' value='$ID'>\n";

         echo "<table class='tab_cadre'>";
         echo "<tr><th colspan='2'>" . Vlan::getTypeName() . "</th></tr>\n";

         echo "<tr class='tab_bg_2'><td colspan='2'>";
         $used = self::showForNetworkPort($ID, true,0);
         echo "</td></tr>\n";

         echo "<tr class='tab_bg_2'><td>".__('Associate a VLAN');
         Vlan::dropdown(array('used' => $used));
         echo "</td><td rowspan='2'>";
         echo "<input type='submit' name='assign_vlan' value=\""._sx('button','Associate')."\"
                class='submit'>";
         echo "</td></tr>";

         echo "<tr class='tab_bg_2'><td>";
         echo __('Tagged')."&nbsp;<input type='checkbox' name='tagged' value='1'>";
         echo "</td></tr>";

         echo "</table>";
         Html::closeForm();
      }
   }


   /**
    * @param $portID
   **/
   static function getVlansForNetworkPort($portID) {
      global $DB;

      $vlans = array();
      $query = "SELECT `vlans_id`
                FROM `glpi_networkports_vlans`
                WHERE `networkports_id` = '$portID'";
      foreach ($DB->request($query) as $data) {
         $vlans[$data['vlans_id']] = $data['vlans_id'];
      }

      return $vlans;
   }


   function getTabNameForItem(CommonGLPI $item, $withtemplate=0) {

      if (!$withtemplate) {
         switch ($item->getType()) {
            case 'NetworkPort' :
               if ($_SESSION['glpishow_count_on_tabs']) {
                  return self::createTabEntry(Vlan::getTypeName(),
                                              countElementsInTable($this->getTable(),
                                                                   "networkports_id
                                                                        = '".$item->getID()."'"));
               }
               return Vlan::getTypeName();
         }
      }
      return '';
   }


   static function displayTabContentForItem(CommonGLPI $item, $tabnum=1, $withtemplate=0) {

      if ($item->getType()=='NetworkPort') {
         self::showForNetworkPortForm($item->getID());
      }
      return true;
   }

}
?>