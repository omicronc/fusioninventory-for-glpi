<?php

/*
   ------------------------------------------------------------------------
   FusionInventory
   Copyright (C) 2010-2012 by the FusionInventory Development Team.

   http://www.fusioninventory.org/   http://forge.fusioninventory.org/
   ------------------------------------------------------------------------

   LICENSE

   This file is part of FusionInventory project.

   FusionInventory is free software: you can redistribute it and/or modify
   it under the terms of the GNU Affero General Public License as published by
   the Free Software Foundation, either version 3 of the License, or
   (at your option) any later version.

   FusionInventory is distributed in the hope that it will be useful,
   but WITHOUT ANY WARRANTY; without even the implied warranty of
   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
   GNU Affero General Public License for more details.

   You should have received a copy of the GNU Affero General Public License
   along with Behaviors. If not, see <http://www.gnu.org/licenses/>.

   ------------------------------------------------------------------------

   @package   FusionInventory
   @author    David Durieux
   @co-author
   @copyright Copyright (c) 2010-2012 FusionInventory team
   @license   AGPL License 3.0 or (at your option) any later version
              http://www.gnu.org/licenses/agpl-3.0-standalone.html
   @link      http://www.fusioninventory.org/
   @link      http://forge.fusioninventory.org/projects/fusioninventory-for-glpi/
   @since     2010

   ------------------------------------------------------------------------
 */

define ("PLUGIN_FUSIONINVENTORY_VERSION","0.84+1.0");

define ("PLUGIN_FUSIONINVENTORY_OFFICIAL_RELEASE","0");

include_once(GLPI_ROOT."/inc/includes.php");

// Init the hooks of fusioninventory
function plugin_init_fusioninventory() {
   global $PLUGIN_HOOKS, $CFG_GLPI;
      
   $PLUGIN_HOOKS['csrf_compliant']['fusioninventory'] = true;

   $moduleId = 0;
   if (class_exists('PluginFusioninventoryModule')) { // check if plugin is active
      // ##### 1. (Not required here) #####

      // ##### 2. register class #####

      Plugin::registerClass('PluginFusioninventoryAgent');
      Plugin::registerClass('PluginFusioninventoryConfig');
      Plugin::registerClass('PluginFusioninventoryTask',
              array('addtabon' => array('Computer','Printer','NetworkEquipment','PluginFusioninventoryCredentialIp')));
      Plugin::registerClass('PluginFusioninventoryTaskjob',
              array('addtabon' => array('Computer','Printer','NetworkEquipment','PluginFusioninventoryUnknowndevice')));
      Plugin::registerClass('PluginFusioninventoryTaskjob');
      Plugin::registerClass('PluginFusioninventoryTaskjobstate');
      Plugin::registerClass('PluginFusioninventoryUnknownDevice');
      Plugin::registerClass('PluginFusioninventoryModule');
      Plugin::registerClass('PluginFusioninventoryProfile');
      Plugin::registerClass('PluginFusioninventorySetup');
      Plugin::registerClass('PluginFusioninventoryAgentmodule');
      Plugin::registerClass('PluginFusioninventoryIPRange');
      Plugin::registerClass('PluginFusioninventoryCredential');
      Plugin::registerClass('PluginFusioninventoryLock',
              array('addtabon' => array('Computer','Monitor','Printer','NetworkEquipment')));

      Plugin::registerClass('PluginFusioninventoryInventoryComputerAntivirus',
                 array('addtabon' => array('Computer')));
      Plugin::registerClass('PluginFusioninventoryInventoryComputerComputer',
                 array('addtabon' => array('Computer')));
      Plugin::registerClass('PluginFusioninventoryInventoryComputerInventory');
      Plugin::registerClass('PluginFusioninventoryInventoryComputerLibintegrity',
                 array('addtabon' => array('Computer')));

         //Classes for rulesengine
      Plugin::registerClass('PluginFusioninventoryInventoryRuleEntity');
      Plugin::registerClass('PluginFusioninventoryInventoryRuleEntityCollection',
                            array('rulecollections_types'=>true));
      Plugin::registerClass('PluginFusioninventoryRulematchedlog',
              array('addtabon' => array('PluginFusioninventoryAgent',
                                        'PluginFusioninventoryUnknownDevice',
                                        'Printer',
                                        'NetworkEquipment')));

      //Classes for rulesengine
      Plugin::registerClass('PluginFusioninventoryInventoryRuleImport');
      Plugin::registerClass('PluginFusioninventoryInventoryRuleImportCollection',
                            array('rulecollections_types'=>true));
      Plugin::registerClass('PluginFusioninventoryConstructDevice');
      
      // Networkinventory and networkdiscovery
      Plugin::registerClass('PluginFusioninventorySnmpmodel');
      Plugin::registerClass('PluginFusioninventoryNetworkEquipment',
                  array('addtabon' => array('NetworkEquipment')));
      Plugin::registerClass('PluginFusioninventoryPrinter');
      Plugin::registerClass('PluginFusioninventoryPrinterCartridge');
      Plugin::registerClass('PluginFusioninventoryConfigSecurity');
      Plugin::registerClass('PluginFusioninventoryNetworkPortLog');
      Plugin::registerClass('PluginFusinvsnmpAgentconfig');
      Plugin::registerClass('PluginFusioninventoryNetworkPort',
                            array('classname'=>'glpi_networkports'));
      Plugin::registerClass('PluginFusioninventoryStateDiscovery');
      Plugin::registerClass('PluginFusioninventoryPrinterLogReport');

      
      $CFG_GLPI['glpitablesitemtype']["PluginFusioninventoryPrinterLogReport"] = "glpi_plugin_fusioninventory_printers";


      // ##### 3. get informations of the plugin #####

      $a_plugin = plugin_version_fusioninventory();
      $moduleId = PluginFusioninventoryModule::getModuleId($a_plugin['shortname']);

      // ##### 4. Set in session module_id #####

      $_SESSION["plugin_".$a_plugin['shortname']."_moduleid"] = $moduleId;

      // ##### 5. Set in session XMLtags of methods #####

      $_SESSION['glpi_plugin_fusioninventory']['xmltags']['WAKEONLAN'] = '';
      $_SESSION['glpi_plugin_fusioninventory']['xmltags']['INVENTORY']
                                             = 'PluginFusioninventoryInventoryComputerInventory';
      $_SESSION['glpi_plugin_fusioninventory']['xmltags']['NETWORKDISCOVERY'] 
                                             = 'PluginFusioninventoryCommunicationNetworkDiscovery';
      $_SESSION['glpi_plugin_fusioninventory']['xmltags']['NETWORKINVENTORY'] 
                                             = 'PluginFusioninventoryCommunicationNetworkInventory';


      $PLUGIN_HOOKS['change_profile']['fusioninventory'] = PluginFusioninventoryProfile::changeprofile($moduleId);

      
      if (isset($_SESSION["glpiID"])) {

         $CFG_GLPI["specif_entities_tables"][] = 'glpi_plugin_fusioninventory_ipranges';

         if (Session::haveRight("configuration", "r") || Session::haveRight("profile", "w")) {// Config page
            $PLUGIN_HOOKS['config_page']['fusioninventory'] = 'front/config.form.php?glpi_tab=1';
         }

         $PLUGIN_HOOKS['use_massive_action']['fusioninventory'] = 1;
         
         $PLUGIN_HOOKS['item_add']['fusioninventory'] = array('NetworkPort_NetworkPort'=>'plugin_item_add_fusinvsnmp');

         
         $PLUGIN_HOOKS['pre_item_update']['fusioninventory'] = array('Plugin' => 'plugin_pre_item_update_fusioninventory');
         $PLUGIN_HOOKS['item_update']['fusioninventory'] =
                                 array('Computer'         => 'plugin_item_update_fusioninventory',
                                       'NetworkEquipment' => 'plugin_item_update_fusioninventory',
                                       'Printer'          => 'plugin_item_update_fusioninventory',
                                       'Monitor'          => 'plugin_item_update_fusioninventory',
                                       'Peripheral'       => 'plugin_item_update_fusioninventory',
                                       'Phone'            => 'plugin_item_update_fusioninventory',
                                       'NetworkPort'      => 'plugin_item_update_fusioninventory',
                                       'PluginFusioninventoryInventoryComputerAntivirus' => array('PluginFusioninventoryInventoryComputerAntivirus', 'addhistory'));


         $PLUGIN_HOOKS['pre_item_purge']['fusioninventory'] = array('Computer' =>'plugin_pre_item_purge_fusinvinventory',
                                                                    'NetworkPort_NetworkPort'=>'plugin_pre_item_purge_fusinvsnmp');
         $p = array('NetworkPort_NetworkPort'            => 'plugin_item_purge_fusioninventory',
                    'PluginFusioninventoryTask'          => array('PluginFusioninventoryTask', 'purgeTask'),
                    'PluginFusioninventoryTaskjob'       => array('PluginFusioninventoryTaskjob', 'purgeTaskjob'),
                    'PluginFusioninventoryUnknownDevice' => array('PluginFusioninventoryUnknownDevice', 'purgeUnknownDevice'),
                    'NetworkEquipment'                   => 'plugin_item_purge_fusinvsnmp',
                    'Printer'                            => 'plugin_item_purge_fusinvsnmp',
                    'PluginFusioninventoryUnknownDevice' => 'plugin_item_purge_fusinvsnmp');
         $PLUGIN_HOOKS['item_purge']['fusioninventory'] = $p;

         
         $PLUGIN_HOOKS['item_transfer']['fusioninventory'] = 'plugin_item_transfer_fusioninventory';

         $Plugin = new Plugin();
         if ($Plugin->isActivated('fusioninventory')) {
            if (PluginFusioninventoryProfile::haveRight("fusioninventory", "agents", "r")
               OR PluginFusioninventoryProfile::haveRight("fusioninventory", "remotecontrol","r")
               OR PluginFusioninventoryProfile::haveRight("fusioninventory", "configuration","r")
               OR PluginFusioninventoryProfile::haveRight("fusioninventory", "wol","r")
               OR PluginFusioninventoryProfile::haveRight("fusioninventory", "unknowndevice","r")
               OR PluginFusioninventoryProfile::haveRight("fusioninventory", "task","r")
               ) {

               $PLUGIN_HOOKS['menu_entry']['fusioninventory'] = true;
            }
         }
         
         
         $report_list = array();
         if (PluginFusioninventoryProfile::haveRight("fusioninventory", "reportprinter","r")) {
            $report_list["front/printerlogreport.php"] = __('Printed page counter');

         }
         if (PluginFusioninventoryProfile::haveRight("fusioninventory", "reportnetworkequipment","r")) {
            $report_list["report/switch_ports.history.php"] = __('Switchs ports history');

            $report_list["report/ports_date_connections.php"] = __('Unused switchs ports');

            $report_list["report/not_queried_recently.php"] = __('Number of days since last inventory');

         }
         $PLUGIN_HOOKS['reports']['fusioninventory'] = $report_list;
         

         // Tabs for each type
         $PLUGIN_HOOKS['headings']['fusioninventory'] = 'plugin_get_headings_fusioninventory';
         $PLUGIN_HOOKS['headings_action']['fusioninventory'] = 'plugin_headings_actions_fusioninventory';

         // Icons add, search...
         $PLUGIN_HOOKS['submenu_entry']['fusioninventory']['add']['tasks'] = 'front/task.form.php?add=1';
         $PLUGIN_HOOKS['submenu_entry']['fusioninventory']['search']['tasks'] = 'front/task.php';

         $PLUGIN_HOOKS['submenu_entry']['fusioninventory']['add']['unknown'] = 'front/unknowndevice.form.php?add=1';
         $PLUGIN_HOOKS['submenu_entry']['fusioninventory']['search']['unknown'] = 'front/unknowndevice.php';

         $PLUGIN_HOOKS['submenu_entry']['fusioninventory']['add']['inventoryruleimport']
            = 'front/inventoryruleimport.form.php';
         $PLUGIN_HOOKS['submenu_entry']['fusioninventory']['search']['inventoryruleimport']
            = 'front/inventoryruleimport.php';

         $PLUGIN_HOOKS['submenu_entry']['fusioninventory']['search']['agents'] = 'front/agent.php';

         $PLUGIN_HOOKS['submenu_entry']['fusioninventory']['add']['fusinvinventory-ruleentity']
                        = '../fusioninventory/front/inventoryruleentity.form.php';
         $PLUGIN_HOOKS['submenu_entry']['fusioninventory']['search']['fusinvinventory-ruleentity']
                        = '../fusioninventory/front/inventoryruleentity.php';

         $PLUGIN_HOOKS['submenu_entry']['fusioninventory']['add']['fusinvinventory-blacklist']
                        = '../fusioninventory/front/inventorycomputerblacklist.form.php';
         $PLUGIN_HOOKS['submenu_entry']['fusioninventory']['search']['fusinvinventory-blacklist']
                        = '../fusioninventory/front/inventorycomputerblacklist.php';
         
         $PLUGIN_HOOKS['submenu_entry']['fusioninventory']['add']['models'] = '../fusioninventory/front/snmpmodel.form.php?add=1';
         $PLUGIN_HOOKS['submenu_entry']['fusioninventory']['search']['models'] = '../fusioninventory/front/snmpmodel.php';

         $PLUGIN_HOOKS['submenu_entry']['fusioninventory']['add']['configsecurity'] = '../fusioninventory/front/configsecurity.form.php?add=1';
         $PLUGIN_HOOKS['submenu_entry']['fusioninventory']['search']['configsecurity'] = '../fusioninventory/front/configsecurity.php';


         if (PluginFusioninventoryProfile::haveRight("fusioninventory", "agent","r")) {
            if (PluginFusioninventoryProfile::haveRight("fusioninventory", "agents","w")) {
               $PLUGIN_HOOKS['submenu_entry']['fusioninventory']['search']['agents'] = 'front/agent.php';
            }

            if (PluginFusioninventoryProfile::haveRight("fusioninventory", "configuration", "r")) {// Config page
               $PLUGIN_HOOKS['submenu_entry']['fusioninventory']['config'] = 'front/config.form.php';
            }
         }
         $PLUGIN_HOOKS['submenu_entry']['fusioninventory']
            ["<img  src='".$CFG_GLPI['root_doc']."/plugins/fusioninventory/pics/books.png'
               title='".__('Documentation')."'
               alt='".__('Documentation')."'>"] =
            'front/documentation.php';

         $PLUGIN_HOOKS['webservices']['fusioninventory'] = 'plugin_fusioninventory_registerMethods';

         // Fil ariane
         $PLUGIN_HOOKS['submenu_entry']['fusioninventory']['options']['menu']['title'] = __('Menu');

         $PLUGIN_HOOKS['submenu_entry']['fusioninventory']['options']['menu']['page']  = '/plugins/fusioninventory/front/wizard.php';

         $PLUGIN_HOOKS['submenu_entry']['fusioninventory']['options']['tasks']['title'] = __('Task management');

         $PLUGIN_HOOKS['submenu_entry']['fusioninventory']['options']['tasks']['page']  = '/plugins/fusioninventory/front/task.php';

         $PLUGIN_HOOKS['submenu_entry']['fusioninventory']['options']['taskjob']['title'] = __('Running jobs');

         $PLUGIN_HOOKS['submenu_entry']['fusioninventory']['options']['taskjob']['page']  = '/plugins/fusioninventory/front/taskjob.php';

         $PLUGIN_HOOKS['submenu_entry']['fusioninventory']['options']['agents']['title'] = __('Agents management');

         $PLUGIN_HOOKS['submenu_entry']['fusioninventory']['options']['agents']['page']  = '/plugins/fusioninventory/front/agent.php';

         $PLUGIN_HOOKS['submenu_entry']['fusioninventory']['options']['configuration']['title'] = __('General configuration');

         $PLUGIN_HOOKS['submenu_entry']['fusioninventory']['options']['configuration']['page']  = '/plugins/fusioninventory/front/config.form.php';

         $PLUGIN_HOOKS['submenu_entry']['fusioninventory']['options']['unknown']['title'] = __('Unknown device');

         $PLUGIN_HOOKS['submenu_entry']['fusioninventory']['options']['unknown']['page']  = '/plugins/fusioninventory/front/unknowndevice.php';

         $PLUGIN_HOOKS['submenu_entry']['fusioninventory']['options']['inventoryruleimport']['title'] = __('Equipment import and link rules');

         $PLUGIN_HOOKS['submenu_entry']['fusioninventory']['options']['inventoryruleimport']['page']  = '/plugins/fusioninventory/front/inventoryruleimport.php';

         $PLUGIN_HOOKS['submenu_entry']['fusioninventory']['options']['wizard-start']['title'] = __('Wizard');

         $PLUGIN_HOOKS['submenu_entry']['fusioninventory']['options']['wizard-start']['page']  = '/plugins/fusioninventory/front/wizard.php';

         $PLUGIN_HOOKS['submenu_entry']['fusioninventory']['options']['iprange']['title'] =
            __('IP range configuration');

         $PLUGIN_HOOKS['submenu_entry']['fusioninventory']['options']['iprange']['page']  =
            '/plugins/fusioninventory/front/iprange.php';

         if (PluginFusioninventoryProfile::haveRight("fusioninventory", "iprange","w")) {
            $PLUGIN_HOOKS['submenu_entry']['fusioninventory']['add']['iprange'] =
               '../fusioninventory/front/iprange.form.php?add=1';
            $PLUGIN_HOOKS['submenu_entry']['fusioninventory']['search']['iprange'] =
               '../fusioninventory/front/iprange.php';
         }

         if (PluginFusioninventoryCredential::hasAlLeastOneType()) {
            if (PluginFusioninventoryProfile::haveRight("fusioninventory", "credential","w")) {
               $PLUGIN_HOOKS['submenu_entry']['fusioninventory']['add']['PluginFusioninventoryCredential'] =
                  '../fusioninventory/front/credential.form.php?add=1';
               $PLUGIN_HOOKS['submenu_entry']['fusioninventory']['search']['PluginFusioninventoryCredential'] =
                  '../fusioninventory/front/credential.php';

            }

            if (PluginFusioninventoryProfile::haveRight("fusioninventory", "credential","w")) {
               $PLUGIN_HOOKS['submenu_entry']['fusioninventory']['add']['PluginFusioninventoryCredentialIp'] =
                  '../fusioninventory/front/credentialip.form.php?add=1';
               $PLUGIN_HOOKS['submenu_entry']['fusioninventory']['search']['PluginFusioninventoryCredentialIp'] =
                  '../fusioninventory/front/credentialip.php';

            }
         }
         $PLUGIN_HOOKS['submenu_entry']['fusioninventory']['options']['fusinvinventory-blacklist']['title'] = __('BlackList');

         $PLUGIN_HOOKS['submenu_entry']['fusioninventory']['options']['fusinvinventory-blacklist']['page']  = '/plugins/fusioninventory/front/inventorycomputerblacklist.php';

         $PLUGIN_HOOKS['submenu_entry']['fusioninventory']['options']['fusinvinventory-ruleinventory']['title'] = __('Criteria rules');

         $PLUGIN_HOOKS['submenu_entry']['fusioninventory']['options']['fusinvinventory-ruleinventory']['page']  = '/plugins/fusinvinventory/front/ruleinventory.php';

         $PLUGIN_HOOKS['submenu_entry']['fusioninventory']['options']['fusinvinventory-ruleentity']['title'] = __('Entity rules');

         $PLUGIN_HOOKS['submenu_entry']['fusioninventory']['options']['fusinvinventory-ruleentity']['page']  = '/plugins/fusioninventory/front/inventoryruleentity.php';

         $PLUGIN_HOOKS['submenu_entry']['fusioninventory']['options']['fusinvinventory-importxmlfile']['title'] = __('Import agent XML file');

         $PLUGIN_HOOKS['submenu_entry']['fusioninventory']['options']['fusinvinventory-importxmlfile']['page']  = '/plugins/fusinvinventory/front/importxml.php';
         
         $PLUGIN_HOOKS['submenu_entry']['fusioninventory']['options']['models']['title'] = __('SNMP models');

         $PLUGIN_HOOKS['submenu_entry']['fusioninventory']['options']['models']['page']  = '/plugins/fusioninventory/front/snmpmodel.php';

         $PLUGIN_HOOKS['submenu_entry']['fusioninventory']['options']['configsecurity']['title'] = __('SNMP authentication');

         $PLUGIN_HOOKS['submenu_entry']['fusioninventory']['options']['configsecurity']['page']  = '/plugins/fusioninventory/front/configsecurity.php';

         $PLUGIN_HOOKS['submenu_entry']['fusioninventory']['options']['statediscovery']['title'] = __('Discovery status');

         $PLUGIN_HOOKS['submenu_entry']['fusioninventory']['options']['statediscovery']['page']  = '/plugins/fusioninventory/front/statediscovery.php';
         
         
      }
   } else { // plugin not active, need $moduleId for uninstall check
      include_once(GLPI_ROOT.'/plugins/fusioninventory/inc/module.class.php');
      $moduleId = PluginFusioninventoryModule::getModuleId('fusioninventory');
   }

   // Check for uninstall
   if (isset($_GET['id'])
      && ($_GET['id'] == $moduleId)
         && (isset($_GET['action'])
            && $_GET['action'] == 'uninstall')
               && (strstr($_SERVER['HTTP_REFERER'], "front/plugin.php"))) {

      if (PluginFusioninventoryModule::getAll(true)) {
          Session::addMessageAfterRedirect(__('Other FusionInventory plugins (fusinv...) must be uninstalled before removing the FusionInventory plugin'));

         Html::redirect($CFG_GLPI["root_doc"]."/front/plugin.php");
         exit;
      }
   }


   // Add unknown devices in list of devices with networport
   $CFG_GLPI["netport_types"][] = "PluginFusioninventoryUnknownDevice";

}



// Name and Version of the plugin
function plugin_version_fusioninventory() {
   return array('name'           => 'FusionInventory',
                'shortname'      => 'fusioninventory',
                'version'        => PLUGIN_FUSIONINVENTORY_VERSION,
                'license'        => 'AGPLv3+',
                'oldname'        => 'tracker',
                'author'         =>'<a href="mailto:d.durieux@siprossii.com">David DURIEUX</a>
                                    & FusionInventory team',
                'homepage'       =>'http://forge.fusioninventory.org/projects/fusioninventory-for-glpi/',
                'minGlpiVersion' => '0.84'// For compatibility / no install in version < 0.78
   );
}



// Optional : check prerequisites before install : may print errors or add to message after redirect
function plugin_fusioninventory_check_prerequisites() {
   global $DB;

   if (version_compare(GLPI_VERSION,'0.84','lt') || version_compare(GLPI_VERSION,'0.85','ge')) {
      echo __('Your GLPI version not compatible, require 0.84');

      return false;
   }
   $crontask = new CronTask();
   if ((TableExists("glpi_plugin_fusioninventory_agents")
           AND !FieldExists("glpi_plugin_fusioninventory_agents", "tag"))
        OR ($crontask->getFromDBbyName('PluginFusioninventoryTaskjobstatus', 'cleantaskjob'))) {
      $DB->query("UPDATE `glpi_plugin_fusioninventory_configs` SET `value`='0.80+1.4' WHERE `type`='version'");
      $DB->query("UPDATE `glpi_plugins` SET `version`='0.80+1.4' WHERE `directory` LIKE 'fusi%'");
   }

   return true;
}



function plugin_fusioninventory_check_config() {
   return true;
}



function plugin_fusioninventory_haveTypeRight($type,$right) {
   return true;
}

?>