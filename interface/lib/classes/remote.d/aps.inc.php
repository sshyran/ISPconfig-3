<?php

/*
Copyright (c) 2007 - 2013, Till Brehm, projektfarm Gmbh
All rights reserved.

Redistribution and use in source and binary forms, with or without modification,
are permitted provided that the following conditions are met:

    * Redistributions of source code must retain the above copyright notice,
      this list of conditions and the following disclaimer.
    * Redistributions in binary form must reproduce the above copyright notice,
      this list of conditions and the following disclaimer in the documentation
      and/or other materials provided with the distribution.
    * Neither the name of ISPConfig nor the names of its contributors
      may be used to endorse or promote products derived from this software without
      specific prior written permission.

THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND
ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED.
IN NO EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT,
INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING,
BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY
OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING
NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE,
EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.

--UPDATED 01.2015--
Created by Dominik Müller <info@profi-webdesign.net>
Copyright (c) Profi Webdesign Dominik Müller

*/

class remoting_aps extends remoting {
	//* Functions for APS
	public function sites_aps_update_package_list($session_id)
	{
		global $app;
	
		if(!$this->checkPerm($session_id, 'sites_aps_update_package')) {
			$this->server->fault('permission_denied', 'You do not have the permissions to access this function.');
			return false;
		}
	
		$app->load('aps_crawler');
		$aps = new ApsCrawler($app, false); // true = Interface mode, false = Server mode
		$aps->startCrawler();
		$aps->parseFolderToDB();
		$aps->fixURLs();
	
		return true;
	}
	
	public function sites_aps_available_packages_list($session_id, $params)
	{
		global $app;
	
		if(!$this->checkPerm($session_id, 'sites_aps_available_packages_list')) {
			$this->server->fault('permission_denied', 'You do not have the permissions to access this function.');
			return false;
		}
	
		$app->load('aps_base');
	
		if (isset($params['all_packages']) && ($params['all_packages'] == true)) {
			$where = '(aps_packages.package_status = '.PACKAGE_ENABLED.' OR aps_packages.package_status = '.PACKAGE_LOCKED.')';
		}
		else {
			$where = 'aps_packages.package_status = '.PACKAGE_ENABLED;
		}
	
		$sql  = 'SELECT * FROM aps_packages WHERE '.$where.' ORDER BY aps_packages.name, aps_packages.version';
		return $app->db->queryAllRecords($sql);
	}
	
	public function sites_aps_get_package_details($session_id, $primary_id)
	{
		global $app;
	
		if(!$this->checkPerm($session_id, 'sites_aps_get_package_details')) {
			$this->server->fault('permission_denied', 'You do not have the permissions to access this function.');
			return false;
		}
	
		$app->load('aps_guicontroller');
		$gui = new ApsGUIController($app);
	
		// Package-ID Check
		if (isset($primary_id))
		{
			$newest_pkg_id = $gui->getNewestPackageID($pkg_id);
			if($newest_pkg_id != 0) $primary_id = $newest_pkg_id;
		}
	
		// Make sure an integer ID is given
		if (!isset($primary_id) || !$gui->isValidPackageID($primary_id, true)) {// always adminflag
			$this->server->fault('package_error', 'The given Package ID is not valid.');
			return false;
		}
	
		// Get package details
		$details = $gui->getPackageDetails($primary_id);
		if (isset($details['error'])) {
			$this->server->fault('package_error', $details['error']);
			return false;
		}
	
		// encode all parts to ensure SOAP-XML-format
		array_walk_recursive($details, function(&$item, &$key) { $item = utf8_encode($item); } );
		// Special handling for license-text because of too much problems with soap-transport
		$details['License content'] = base64_encode($details['License content']);
	
		return $details;
	}
	
	public function sites_aps_get_package_file($session_id, $primary_id, $filename) {
		global $app;
	
		if(!$this->checkPerm($session_id, 'sites_aps_get_package_file')) {
			$this->server->fault('permission_denied', 'You do not have the permissions to access this function.');
			return false;
		}
	
		$app->load('aps_guicontroller');
		$gui = new ApsGUIController($app);
	
		// Package-ID Check
		if (isset($primary_id))
		{
			$newest_pkg_id = $gui->getNewestPackageID($pkg_id);
			if($newest_pkg_id != 0) $primary_id = $newest_pkg_id;
		}
	
		// Make sure an integer ID is given
		if (!isset($primary_id) || !$gui->isValidPackageID($primary_id, true)) {// always adminflag
			$this->server->fault('package_error', 'The given Package ID is not valid.');
			return false;
		}
	
		// Get package details
		$details = $gui->getPackageDetails($primary_id);
		if (isset($details['error'])) {
			$this->server->fault('package_error', $details['error']);
			return false;
		}
	
		// find file in details
		$found = false;
		if (basename($details['Icon']) == $filename) $found = true;
		if (!$found && isset($details['Screenshots']) && is_array($details['Screenshots']))
		foreach ($details['Screenshots'] as $screen) { if (basename($screen['ScreenPath']) == $filename) { $found = true; break; } }
	
		if (!$found) {
			$this->server->fault('package_error', 'File not found in package.');
			return false;
		}
	
		return base64_encode(file_get_contents(ISPC_ROOT_PATH.'/web/sites/aps_meta_packages/'.$details['path'].'/'.$filename));
	}
	
	public function sites_aps_get_package_settings($session_id, $primary_id)
	{
		global $app;
	
		if(!$this->checkPerm($session_id, 'sites_aps_get_package_details')) {
			$this->server->fault('permission_denied', 'You do not have the permissions to access this function.');
			return false;
		}
	
		$app->load('aps_guicontroller');
		$gui = new ApsGUIController($app);
	
		// Package-ID Check
		if (isset($primary_id))
		{
			$newest_pkg_id = $gui->getNewestPackageID($pkg_id);
			if($newest_pkg_id != 0) $primary_id = $newest_pkg_id;
		}
	
		// Make sure an integer ID is given
		if (!isset($primary_id) || !$gui->isValidPackageID($primary_id, true)) {// always adminflag
			$this->server->fault('package_error', 'The given Package ID is not valid.');
			return false;
		}
	
		// Get package settings
		$settings = $gui->getPackageSettings($primary_id);
		if (isset($settings['error'])) {
			$this->server->fault('package_error', $settings['error']);
			return false;
		}
	
		// encode all parts to ensure SOAP-XML-format
		array_walk_recursive($settings, function(&$item, &$key) { $item = utf8_encode($item); } );
	
		return $settings;
	}
	
	public function sites_aps_install_package($session_id, $primary_id, $params)
	{
		global $app;
	
		if(!$this->checkPerm($session_id, 'sites_aps_install_package')) {
			$this->server->fault('permission_denied', 'You do not have the permissions to access this function.');
			return false;
		}
	
		$app->load('aps_guicontroller');
		$gui = new ApsGUIController($app);
	
		// Package-ID Check
		if (isset($primary_id))
		{
			$newest_pkg_id = $gui->getNewestPackageID($primary_id);
			if($newest_pkg_id != 0) $primary_id = $newest_pkg_id;
		}
	
		// Make sure an integer ID is given
		if (!isset($primary_id) || !$gui->isValidPackageID($primary_id, true)) {// always adminflag
			$this->server->fault('package_error', 'The given Package ID is not valid.');
			return false;
		}
	
		// Get package details
		$details = $gui->getPackageDetails($primary_id);
		if (isset($details['error'])) {
			$this->server->fault('package_error', $details['error']);
			return false;
		}
		$settings = $gui->getPackageSettings($primary_id);
		if (isset($settings['error'])) {
			$this->server->fault('package_error', $settings['error']);
			return false;
		}
	
		// Check given Site/VHostDomain
		if (!isset($params['main_domain'])) {
			$this->server->fault('invalid parameters', 'No valid domain given.');
			return false;
		}
	
		$sql = "SELECT * FROM web_domain WHERE domain = '".$app->db->quote($params['main_domain'])."'";
		$domain = $app->db->queryOneRecord($sql);
	
		if (!$domain) {
			$this->server->fault('invalid parameters', 'No valid domain given.');
			return false;
		}
	
		$domains = array($domain['domain']); // Simulate correct Domain-List
		$result = $gui->validateInstallerInput($params, $details, $domains, $settings);
		if(empty($result['error']))
		{
			return $gui->createPackageInstance($result['input'], $primary_id);
		}
		
		$this->server->fault('invalid parameters', implode('<br />', $result['error']));
		return false;
	}
	
	public function sites_aps_instance_get($session_id, $primary_id)
	{
		global $app;
	
		if(!$this->checkPerm($session_id, 'sites_aps_instance_get')) {
			$this->server->fault('permission_denied', 'You do not have the permissions to access this function.');
			return false;
		}
	
		$sql  = "SELECT * FROM aps_instances WHERE id = ".$app->functions->intval($primary_id);
		$result = $app->db->queryOneRecord($sql);
		return $result;
	}
	
	public function sites_aps_instance_settings_get($session_id, $primary_id)
	{
		global $app;
	
		if(!$this->checkPerm($session_id, 'sites_aps_instance_get')) {
			$this->server->fault('permission_denied', 'You do not have the permissions to access this function.');
			return false;
		}
	
		$sql  = "SELECT * FROM aps_instances_settings WHERE instance_id = ".$app->functions->intval($primary_id);
		$result = $app->db->queryAllRecords($sql);
		return $result;
	}
	
	public function sites_aps_instance_delete($session_id, $primary_id, $params = array())
	{
		global $app;
	
		if(!$this->checkPerm($session_id, 'sites_aps_instance_delete')) {
			$this->server->fault('permission_denied', 'You do not have the permissions to access this function.');
			return false;
		}
	
		$app->load('aps_guicontroller');
		$gui = new ApsGUIController($app);
	
		// Check if Instance exists
		$sql  = "SELECT * FROM aps_instances WHERE id = ".$app->functions->intval($primary_id);
		$result = $app->db->queryOneRecord($sql);
	
		if (!$result) {
			$this->server->fault('instance_error', 'No valid instance id given.');
			return false;
		}
	
		$gui->deleteInstance($primary_id, (isset($params['keep_database']) && ($params['keep_database'] === true)));
	
		return true;
	}
}

?>