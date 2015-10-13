<?php
/**
 * @copyright      2001-2015 Kayako
 * @license        https://www.freebsd.org/copyright/freebsd-license.html
 * @link           https://github.com/kayako/jira-integration
 */

/**
 * The Issue History Controller
 * Handles all the issue(s) logged per ticet
 *
 * @author Abhinav Kumar
 */
class Controller_IssueHistory extends Controller_staff
{
	// Core Constants
	const MENU_ID = 111;
	const NAVIGATION_ID = 1;

	/**
	 * Constructor
	 *
	 * @author Abhinav Kumar
	 * @return \Controller_IssueHistory "true" on Success, "false" otherwise
	 */
	public function __construct()
	{
		parent::__construct();

		$this->Load->Library('JIRA:JIRABridge', false, false, 'jira');
		$this->Load->Library('JIRA:JIRAComment', false, false);

		return true;
	}

	/**
	 * The default destructor
	 *
	 * @author Abhinav Kumar
	 * @return bool "true" on Success, "false" otherwise
	 */
	public function __destruct()
	{
		parent::__destruct();

		return true;
	}

	/**
	 * Fetches bugs history per client
	 *
	 * @author Abhinav Kumar
	 *
	 * @param int $_ticketID
	 *
	 * @throws SWIFT_Exception
	 * @return bool "true" on Success, "false" otherwise
	 */
	public function History($_ticketID)
	{
		$_JIRABridge = SWIFT_JIRABridge::GetInstance();

		if (!$this->GetIsClassLoaded()) {
			throw new SWIFT_Exception(SWIFT_CLASSNOTLOADED);
		} else if (!$_JIRABridge || !$_JIRABridge instanceof SWIFT_JIRABridge || !$_JIRABridge->GetIsClassLoaded()) {
			throw new SWIFT_Exception(SWIFT_INVALIDDATA);
		}

		$_ticketID = (int) $_ticketID;

		$_issuesContainer = $_JIRABridge->GetIssuesBy('ticketid', $_ticketID);

		if (_is_array($_issuesContainer)) {
			$this->View->RenderHistoryTab($_issuesContainer);
		} else {
			echo $this->Language->Get('jira_noissuefound');
		}

		return true;
	}
}