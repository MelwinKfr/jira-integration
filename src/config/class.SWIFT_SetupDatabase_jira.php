<?php
/**
 * @copyright      2001-2015 Kayako
 * @license        https://www.freebsd.org/copyright/freebsd-license.html
 * @link           https://github.com/kayako/jira-integration
 */

/**
 * The Main Installer, Installs the module and performs the initial (db) setup
 *
 * @author Abhinav Kumar
 */
class SWIFT_SetupDatabase_jira extends SWIFT_SetupDatabase
{
	/**
	 *  The default constructor
	 *
	 * @author Abhinav Kumar
	 * @return \SWIFT_SetupDatabase_jira
	 */
	public function __construct()
	{
		parent::__construct('jira');

		return true;
	}

	/**
	 * The default destructor
	 *
	 * @author Abhinav Kumar
	 * @return bool
	 */
	public function __destruct()
	{
		parent::__destruct();

		return true;
	}


	/**
	 * Function that does the heavy execution
	 * Calls the parent::Install method for the doing the work
	 * Imports module settings to the SWIFT database
	 *
	 * @author Abhinav Kumar
	 *
	 * @param int $_pageIndex
	 *
	 * @return bool
	 */
	public function Install($_pageIndex)
	{
		parent::Install($_pageIndex);

		$this->ImportSettings();

		return true;
	}

	/**
	 * Uninstalls the module
	 *
	 * @author Abhinav Kumar
	 * @return bool
	 */
	public function Uninstall()
	{
		parent::Uninstall();

		return true;
	}

	/**
	 * Upgrades the Module
	 * Imports/Updates module settings to the SWIFT database
	 *
	 * @author Abhinav Kumar
	 *
	 * @param bool $_isForced
	 *
	 * @return bool
	 */
	public function Upgrade($_isForced = false)
	{
		parent::Upgrade($_isForced);

		$this->ImportSettings();

		return true;
	}

	/**
	 * Imports the settings into SWIFT's database
	 *
	 * @author Abhinav Kumar
	 *
	 * @throws SWIFT_Exception If Class is not Loaded
	 * @return bool
	 */
	private function ImportSettings()
	{
		if (!$this->GetIsClassLoaded()) {
			throw new SWIFT_Exception(SWIFT_CLASSNOTLOADED);
		}

		$this->Load->Library('Settings:SettingsManager');
		$this->SettingsManager->Import('./' . SWIFT_APPSDIRECTORY . '/jira/config/settings.xml');

		return true;
	}
}