<?php
/**
 * @copyright      2001-2015 Kayako
 * @license        https://www.freebsd.org/copyright/freebsd-license.html
 * @link           https://github.com/kayako/jira-integration
 */

/**
 * Main class for JIRA Comment
 *
 * @author Abhinav Kumar
 */
class SWIFT_JIRAComment extends SWIFT_Library
{
	/**
	 * Comment ID
	 *
	 * @var int
	 */
	private $_ID;

	/**
	 * Comment Author
	 *
	 * @var string
	 */
	private $_author;

	/**
	 * Comment Body
	 *
	 * @var string
	 */
	private $_body;

	/**
	 * Comment Visibility
	 *
	 * @var type
	 */
	private $_visibility;

	/**
	 * Comment Creation Date/time
	 *
	 * @var int
	 */
	private $_created;

	/**
	 * Comment Creation Date/time
	 *
	 * @var int
	 */
	private $_updated;

	/**
	 * Associated JIRA issue
	 *
	 * @var \SWIFT_JIRAIssue
	 */
	private $_JIRAIssue;

	/**
	 * Fetch the comment id
	 *
	 * @author Abhinav Kumar
	 *
	 * @return int the comment id
	 * @throws SWIFT_Exception If the Class is not Loaded
	 */
	public function GetID()
	{
		if (!$this->GetIsClassLoaded()) {
			throw new SWIFT_Exception(SWIFT_CLASSNOTLOADED);
		}

		return $this->_ID;
	}

	/**
	 * Fetch the author
	 *
	 * @author Abhinav Kumar
	 *
	 * @return string the comment author
	 * @throws SWIFT_Exception If the Class is not Loaded
	 */
	public function GetAuthor()
	{
		if (!$this->GetIsClassLoaded()) {
			throw new SWIFT_Exception(SWIFT_CLASSNOTLOADED);
		}

		return $this->_author;
	}

	/**
	 * Fetch the body
	 *
	 * @author Abhinav Kumar
	 *
	 * @return string the comment body
	 * @throws SWIFT_Exception If the Class is not Loaded
	 */
	public function GetBody()
	{
		if (!$this->GetIsClassLoaded()) {
			throw new SWIFT_Exception(SWIFT_CLASSNOTLOADED);
		}

		return $this->_body;
	}

	/**
	 * Fetch the body
	 *
	 * @author Abhinav Kumar
	 *
	 * @return string the comment body
	 * @throws SWIFT_Exception If the Class is not Loaded
	 */
	public function GetRawBody()
	{
		if (!$this->GetIsClassLoaded()) {
			throw new SWIFT_Exception(SWIFT_CLASSNOTLOADED);
		}

		$_staffGroupCache = $this->Cache->Get('staffgroupcache');
		$_staffGroups     = array();

		foreach ($_staffGroupCache as $_staffGroupID => $_staffGroupContainer) {
			$_staffGroups[] = $_staffGroupContainer['title'];
		}

		$_staffGroups = implode('|', $_staffGroups);


		if (preg_match('/^.*\(' . $_staffGroups . '\)/i', $this->_body)) {
			$_breakPosition = strpos($this->_body, PHP_EOL);

			return substr($this->_body, $_breakPosition + 1);
		}

		return $this->_body;
	}

	/**
	 * Get Visibility
	 *
	 * @author Varun Shoor
	 *
	 * @throws SWIFT_Exception
	 * @return bool "true" on Success, "false" otherwise
	 */
	public function GetVisibility()
	{
		if (!$this->GetIsClassLoaded()) {
			throw new SWIFT_Exception(SWIFT_CLASSNOTLOADED);
		}

		if (isset($this->_visibility)) {
			return $this->_visibility;
		}

		return false;
	}

	/**
	 * Fetch the creation date
	 *
	 * @author Abhinav Kumar
	 *
	 * @return int timestamp of comment creation
	 * @throws SWIFT_Exception If the Class is not Loaded
	 */
	public function GetCreated()
	{
		if (!$this->GetIsClassLoaded()) {
			throw new SWIFT_Exception(SWIFT_CLASSNOTLOADED);
		}

		return $this->_created;
	}

	/**
	 * Fetch the updation date
	 *
	 * @author Abhinav Kumar
	 *
	 * @return int timestamp of comment updation
	 * @throws SWIFT_Exception If the Class is not Loaded
	 */
	public function GetUpdated()
	{
		if (!$this->GetIsClassLoaded()) {
			throw new SWIFT_Exception(SWIFT_CLASSNOTLOADED);
		}

		return $this->_updated;
	}

	/**
	 * Fetch the JIRA Issue
	 *
	 * @author Abhinav Kumar
	 *
	 * @return \SWIFT_JIRAIssue on success or 'false' otherwise
	 * @throws SWIFT_Exception If the Class is not Loaded
	 */
	public function GetIssue()
	{
		if (!$this->GetIsClassLoaded()) {
			throw new SWIFT_Exception(SWIFT_CLASSNOTLOADED);
		}

		return $this->_JIRAIssue;
	}

	/**
	 * Sets the comment author
	 *
	 * @author Abhinav Kumar <abhinav.kumar@kayako.com>
	 *
	 * @param string $_author The comment author
	 *
	 * @return \SWIFT_JIRAComment or success and 'false' otherwise
	 * @throws SWIFT_Exception If the Class is not Loaded
	 */
	public function SetAuthor($_author)
	{
		if (!$this->GetIsClassLoaded()) {
			throw new SWIFT_Exception(SWIFT_CLASSNOTLOADED);
		}

		if (is_string($_author)) {
			$this->_author = $_author;
		}

		return $this;
	}

	/**
	 * Sets the comment body
	 *
	 * @author Abhinav Kumar <abhinav.kumar@kayako.com>
	 *
	 * @param string $_body The comment body
	 *
	 * @return \SWIFT_JIRAComment or success and 'false' otherwise
	 * @throws SWIFT_Exception If the Class is not Loaded
	 */
	public function SetBody($_body)
	{
		if (!$this->GetIsClassLoaded()) {
			throw new SWIFT_Exception(SWIFT_CLASSNOTLOADED);
		}

		if (is_string($_body)) {
			$this->_body = $_body;
		}

		return $this;
	}

	/**
	 * Sets the JIRA Issue
	 *
	 * @author Abhinav Kumar
	 *
	 * @param SWIFT_JIRAIssueManager $_JIRAIssue
	 *
	 * @throws SWIFT_Exception
	 * @return \SWIFT_JIRAComment or success and 'false' otherwise
	 */
	public function SetIssue(SWIFT_JIRAIssueManager $_JIRAIssue = null)
	{
		if (!$this->GetIsClassLoaded()) {
			throw new SWIFT_Exception(SWIFT_CLASSNOTLOADED);
		}

		if ($_JIRAIssue) {
			$this->_JIRAIssue = $_JIRAIssue;
		}

		return $this;
	}

	/**
	 * Set Visibility
	 *
	 * @author Varun Shoor
	 *
	 * @param string $_visibility
	 *
	 * @return bool "true" on Success, "false" otherwise
	 * @throws SWIFT_Exception If the Class is not Loaded
	 */
	public function SetVisibility($_visibility)
	{
		if (!$this->GetIsClassLoaded()) {
			throw new SWIFT_Exception(SWIFT_CLASSNOTLOADED);
		}

		if ($_visibility && _is_array($_visibility) && array_key_exists('type', $_visibility) && array_key_exists('value', $_visibility)) {
			$this->_visibility = $_visibility;

			return $this;
		}

		return false;
	}

	/**
	 * Parses a JSONObject & sets the comment parameters
	 *
	 * @author Abhinav Kumar <abhinav.kumar@kayako.com>
	 *
	 * @param string $_JSONObject The JSONObject to parse
	 *
	 * @return \SWIFT_JIRAComment on success
	 */
	public function ParseJSON($_JSONObject = null)
	{
		if ($_JSONObject) {
			if (isset($_JSONObject->id)) {
				$this->_ID = $_JSONObject->id;
			}

			if (isset($_JSONObject->author->name)) {
				$this->_author = $_JSONObject->author->displayName;
			}

			if (isset($_JSONObject->body)) {
				$this->_body = $_JSONObject->body;
			}

			if (isset($_JSONObject->created)) {
				$this->_created = strtotime($_JSONObject->created);
			}

			if (isset($_JSONObject->updated)) {
				$this->_updated = strtotime($_JSONObject->updated);
			}

			return $this;
		}

		return false;
	}
}