<?php
/**
 * @copyright      2001-2015 Kayako
 * @license        https://www.freebsd.org/copyright/freebsd-license.html
 * @link           https://github.com/kayako/jira-integration
 */

/**
 * Jira issue model
 *
 * @author Mahesh Salaria
 */
class SWIFT_JiraIssue extends SWIFT_Model
{
	const TABLE_NAME = 'jiraissues';
	const PRIMARY_KEY = 'jiraissueid';

	const TABLE_STRUCTURE = "jiraissueid I PRIMARY AUTO NOTNULL,
								ticketid I NOTNULL,
								issueid  I NOTNULL,
								issuekey C(30) DEFAULT '' NOTNULL";

	const INDEX_1 = 'ticketid, issueid';

	/**
	 * Constructor
	 *
	 * @author Mahesh Salaria
	 *
	 * @param object|\SWIFT_Data $_SWIFT_DataObject The SWIFT_Data Object
	 *
	 * @return \SWIFT_JiraIssue "true" on Success, "false" otherwise
	 */
	public function __construct(SWIFT_Data $_SWIFT_DataObject)
	{
		parent::__construct($_SWIFT_DataObject);

		return true;
	}

	/**
	 * Update TABLENAME Record
	 *
	 * @author Mahesh Salaria
	 *
	 * @param string $_PARAM
	 *
	 * @return bool "true" on Success, "false" otherwise
	 * @throws SWIFT_Exception If the Class is not Loaded or If Invalid Data is Provided
	 */
	public function Update()
	{
		if (!$this->GetIsClassLoaded()) {
			throw new SWIFT_Exception(__CLASS__ . ':  ' . SWIFT_CLASSNOTLOADED);
		}

		return true;
	}
}