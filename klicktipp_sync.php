<?php
/**
 * Comdion GmbH
 *
 * Klicktipp-Sync:
 * Main class file with functions for user management.
 */

// Disallow direct access to this file
defined('_JEXEC') or die;

// Get Klicktipp API
require_once('klicktipp.api.inc');

class plgUserKlicktipp_Sync extends JPlugin
{
    /**
     * Called when a new user is created or updated.
     */
    function onUserAfterSave($user, $isnew, $success, $msg)
    {
        if(empty($this->params->get('klicktipp_username', '')) || empty($this->params->get('klicktipp_password', ''))
            || empty($this->params->get('klicktipp_tag', ''))) return true;

        JLog::addLogger(['text_file' => 'klicktipp_sync.log.php', 'text_file_no_php' => false], JLog::ALL, ['klicktipp_sync']);

        // Login to Klicktipp
        $connector = new KlicktippConnector();
        $loggedIn = $connector->login($this->params->get('klicktipp_username', ''), $this->params->get('klicktipp_password', ''));

        if(!$loggedIn) {
            JLog::add('API connection: Auth fail!', JLog::ERROR, 'klicktipp_sync');
            return true;
        }

        JLog::add('API connection established', JLog::DEBUG, 'klicktipp_sync');

        // Get all Klicktipp-Tags
        $tags = $connector->tag_index();

        $websiteUserTagId = -1;

        // Find ID for predefined tag
        foreach ($tags as $tagId => $tagName) {
            if ($tagName === $this->params->get('klicktipp_tag', null)) {
                $websiteUserTagId = $tagId;
            }
        }

        // If tag is still -1 -> tag doesn't exist
        if ($websiteUserTagId == -1) {
            JLog::add('Tag does not exist!', JLog::DEBUG, 'klicktipp_sync');
            return false;
        }

        JLog::add('WebsiteUser tag ID found: ' . $websiteUserTagId, JLog::DEBUG, 'klicktipp_sync');
        JLog::add('New user E-Mail: ' . $user['email'], JLog::DEBUG, 'klicktipp_sync');

        // Get full name
        $fullName = $user['name'];

        // Use last part of full name as last name
        $nameArray = explode(' ', $fullName);
        $lastName = array_pop($nameArray);
        $firstName = implode(' ', $nameArray);
        JLog::add('User | ' . $firstName . ' | ' . $lastName, JLog::DEBUG, 'klicktipp_sync');

        // Get fields array for POST request
        $fields = ['fieldFirstName' => $firstName, 'fieldLastName' => $lastName];

        $userObj = JFactory::getUser($user['id']);

        $updated = false;
        $klicktippUserId = $userObj->getParam('klicktipp_id');
        if($isnew || is_null($klicktippUserId)) {
            // User isn't in Klicktipp yet
            JLog::add('User is new or not in Klicktipp!', JLog::DEBUG, 'klicktipp_sync');
            $subscriber = $connector->subscribe($user['email'], 0, $websiteUserTagId, $fields, '');
            $userObj->setParam('klicktipp_id', $subscriber->id);
            $userObj->save();
        }
        else
        {
            $updated = true;
            // User registered in Klicktipp
            JLog::add('User available for update. Klicktipp-ID: ' . $klicktippUserId, JLog::DEBUG, 'klicktipp_sync');
            $subscriber = $connector->subscriber_update($klicktippUserId, $fields, $user['email'], '');
        }

        // Logout
        $connector->logout();

        if($subscriber) {
            // Subscriber insert successful
            if(!$updated) JLog::add('Subscriber inserted (new ID): ' . $subscriber->id, JLog::INFO, 'klicktipp_sync');
            if($updated) JLog::add('Subscriber updated (ID): ' . $klicktippUserId, JLog::INFO, 'klicktipp_sync');
        }
        else {
            JLog::add('Klicktipp-Insert failed!', JLog::ERROR, 'klicktipp_sync');
        }

        return boolval($subscriber);
    }

    /**
     * Called when a user is deleted.
     */
    function onUserAfterDelete($user, $success, $msg)
    {
        if(empty($this->params->get('klicktipp_username', '')) || empty($this->params->get('klicktipp_password', ''))
            || empty($this->params->get('klicktipp_tag', ''))) return true;

        $userObj = JFactory::getUser($user['id']);
        JLog::addLogger(['text_file' => 'klicktipp_sync.log.php', 'text_file_no_php' => false], JLog::ALL, ['klicktipp_sync']);

        $klicktippUserId = $userObj->getParam('klicktipp_id');

        // Check for Klicktipp registration
        if(is_null($klicktippUserId)) {
            JLog::add('Delete: User has no Klicktipp-ID!', JLog::DEBUG, 'klicktipp_sync');
            return true;
        }

        // Login to Klicktipp
        $connector = new KlicktippConnector();
        $loggedIn = $connector->login($this->params->get('klicktipp_username', ''), $this->params->get('klicktipp_password', ''));

        if(!$loggedIn) {
            JLog::add('API connection: Auth fail!', JLog::ERROR, 'klicktipp_sync');
            return true;
        }

        JLog::add('API connection established', JLog::DEBUG, 'klicktipp_sync');

        // Delete user from Klicktipp
        $subscriber = $connector->subscriber_delete($klicktippUserId);
        JLog::add('User deleted - Klicktipp-ID: ' . $klicktippUserId, JLog::INFO, 'klicktipp_sync');

        // Logout
        $connector->logout();

        return true;
    }
}
