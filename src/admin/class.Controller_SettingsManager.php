<?php
/**
 * @copyright      2001-2015 Kayako
 * @license        https://www.freebsd.org/copyright/freebsd-license.html
 * @link           https://github.com/kayako/jira-integration
 */

/**
 * The JIRA Settings Manager class
 *
 * @author Abhinav Kumar
 */
class Controller_SettingsManager extends Controller_admin
{
	// Core Constants
	const MENU_ID = 111;
	const NAVIGATION_ID = 1;

	/**
	 * Constructor
	 *
	 * @author Abhinav Kumar
	 * @return \Controller_SettingsManager
	 */
	public function __construct()
	{
		parent::__construct();

		$this->Language->Load('jira');
		$this->Language->Load('settings');

		return true;
	}

	/**
	 * Destructor
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
	 * Render the settings
	 *
	 * @author Abhinav Kumar
	 *
	 * @throws SWIFT_Exception If class is not loaded
	 * @return bool
	 */
	public function Index()
	{
		$_SWIFT = SWIFT::GetInstance();

		if (!$this->GetIsClassLoaded()) {
			throw new SWIFT_Exception(SWIFT_CLASSNOTLOADED);
		}

		$this->UserInterface->Header($this->Language->Get('jira') . ' > ' . $this->Language->Get('settings'), self::MENU_ID, self::NAVIGATION_ID);

		if ($_SWIFT->Staff->GetPermission('admin_canupdatesettings') == '0') {
			$this->UserInterface->DisplayError($this->Language->Get('titlenoperm'), $this->Language->Get('msgnoperm'));
		} else {
			$this->UserInterface->Start(get_class($this), '/JIRA/SettingsManager/Index', SWIFT_UserInterface::MODE_INSERT, false);
			$this->SettingsManager->Render($this->UserInterface, SWIFT_SettingsManager::FILTER_NAME, array('settings_bj'));
			$this->UserInterface->End();
		}

		$this->UserInterface->Footer();

		return true;
	}
}