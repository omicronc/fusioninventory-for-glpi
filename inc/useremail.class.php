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

/** @file
* @brief
*/

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

/**
 * UserEmail class
**/
class UserEmail  extends CommonDBChild {

   // From CommonDBTM
   public $auto_message_on_action = false;

   // From CommonDBChild
   static public $itemtype        = 'User';
   static public $items_id        = 'users_id';
   public $dohistory              = true;


   static function getTypeName($nb=0) {
      return _n('Email', 'Emails', $nb);
   }


   /**
    * Get default email for user. If no default email get first one
    *
    * @param $users_id user ID
    *
    * @return default email, empty if no email set
   **/
   static function getDefaultForUser($users_id) {
      global $DB;

      // Get default one
      foreach ($DB->request("glpi_useremails",
                            "`users_id` = '$users_id' AND `is_default` = '1'") as $data) {
         return $data['email'];
      }

      // Get first if not default set
      foreach ($DB->request("glpi_useremails",
                            "`users_id` = '$users_id' AND `is_default` = '0'") as $data) {
         return $data['email'];
      }
      return '';
   }


   /**
    * Get all emails for user.
    *
    * @param $users_id user ID
    *
    * @return array of emails
   **/
   static function getAllForUser($users_id) {
      global $DB;

      $emails = array();

      // Get default one
      foreach ($DB->request("glpi_useremails", "`users_id` = '$users_id'") as $data) {
         $emails[] = $data['email'];
      }

      return $emails;
   }


   /**
    * is an email of the user
    *
    * @param $users_id           user ID
    * @param $email     string   email to check user ID
    *
    * @return boolean is this email set for the user ?
   **/
   static function isEmailForUser($users_id, $email) {
      global $DB;

      foreach ($DB->request("glpi_useremails",
                            "`users_id` = '$users_id' AND `email` = '$email'") as $data) {
         return true;
      }
      return false;
   }


   /**
    * Show emails of a user
    *
    * @param $user User object
    *
    * @return nothing
   **/
   static function showForUser(User $user) {
      global $DB, $CFG_GLPI;

      $users_id = $user->getID();

      if (!$user->can($users_id,'r')
          && ($users_id != Session::getLoginUserID())) {
         return false;
      }
      $canedit = ($user->can($users_id,"w") || ($users_id == Session::getLoginUserID()));

      // To be sure not to load bad datas from glpi_useremails table
      if ($users_id == 0) {
         $users_id = -99;
      }

      $count = 0;

      // Display emails
      foreach ($DB->request("glpi_useremails", array('users_id' => $users_id,
                                                     'ORDER'    => 'email')) as $data) {
         if ($count) {
            echo '<br>';
         }
         $count++;

         echo "<input title='".__s('Default email')."' type='radio' name='_default_email'
                value='".$data['id']."'".
                ($canedit?' ':' disabled').($data['is_default'] ? ' checked' : ' ').">&nbsp;";
         if (!$canedit || $data['is_dynamic']) {
            echo "<input type='hidden' name='_useremails[".$data['id']."]' value='".$data['email']."'>";
            printf(__('%1$s %2$s'), $data['email'], "<span class='b'>(". __('D').")</span>");
         } else {
            echo "<input type='text' size=30 name='_useremails[".$data['id']."]'
                   value='".$data['email']."' >";
         }

         if (!NotificationMail::isUserAddressValid($data['email'])) {
            echo "<span class='red'>&nbsp;".__('Invalid email address')."</span>";
         }
      }

      if ($canedit) {
         echo "<div id='emailadd$users_id'>";
         // No email display field
         if ($count == 0) {
            echo "<input type='text' size='40' name='_useremails[-100]' value=''>";
         }
         echo "</div>";
      }

   }


   /**
    * @param $user
   **/
   static function showAddEmailButton(User $user) {
      global $CFG_GLPI;

      $users_id = $user->getID();
      if (!$user->can($users_id,'r') && ($users_id != Session::getLoginUserID())) {
         return false;
      }
      $canedit = ($user->can($users_id,"w") || ($users_id == Session::getLoginUserID()));

      if ($canedit) {

         echo "&nbsp;<script type='text/javascript'>var nbemails=1; </script>";
         echo "<span id='addemailbutton'><img title=\"".__s('Add')."\" alt=\"".__s('Add').
               "\" onClick=\"var row = Ext.get('emailadd$users_id');
                             row.createChild('<input type=\'text\' size=\'40\' ".
                                               "name=\'_useremails[-'+nbemails+']\'><br>');
                             nbemails++;\"
               class='pointer' src='".$CFG_GLPI["root_doc"]."/pics/add_dropdown.png'></span>";
      }
   }


   function prepareInputForAdd($input) {

      // Check email validity
      if (!isset($input['email']) || empty($input['email'])) {
         return false;
      }

      // First email is default
      if (countElementsInTable($this->getTable(), "`users_id` = '".$input['users_id']."'") == 0) {
         $input['is_default'] = 1;
      }

      return parent::prepareInputForAdd($input);
   }

   function getNameField() {
      return 'email';
   }

   function post_updateItem($history=1) {
      global $DB;

      // if default is set : unsed others for the users
      if (in_array('is_default', $this->updates)
          && ($this->input["is_default"] == 1)) {
         $query = "UPDATE ". $this->getTable()."
                   SET `is_default` = '0'
                   WHERE `id` <> '".$this->input['id']."'
                         AND `users_id` = '".$this->fields['users_id']."'";
         $DB->query($query);
      }

      parent::post_updateItem($history);
   }


   function post_addItem() {
      global $DB;

      // if default is set : unset others for the users
      if (isset($this->fields['is_default']) && ($this->fields["is_default"] == 1)) {
         $query = "UPDATE ". $this->getTable()."
                   SET `is_default` = '0'
                   WHERE `id` <> '".$this->fields['id']."'
                         AND `users_id` = '".$this->fields['users_id']."'";
         $DB->query($query);
      }

      parent::post_addItem();

   }


   function post_deleteFromDB() {
      global $DB;

      // if default is set : set default to another one
      if ($this->fields["is_default"] == 1) {
         $query = "UPDATE `". $this->getTable()."`
                   SET `is_default` = '1'
                   WHERE `id` <> '".$this->fields['id']."'
                         AND `users_id` = '".$this->fields['users_id']."'
                   LIMIT 1";
         $DB->query($query);
      }

      parent::post_deleteFromDB();
   }

}
?>