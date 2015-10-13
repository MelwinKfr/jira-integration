<?php
/**
 * @copyright      2001-2015 Kayako
 * @license        https://www.freebsd.org/copyright/freebsd-license.html
 * @link           https://github.com/kayako/jira-integration
 */

/**
 * JIRA Bridge
 * Handles all the JIRA interactions to and from Kayako Fusion
 *
 * @author Abhinav Kumar <abhinav.kumar@kayako.com>
 */
class SWIFT_JIRABridge extends SWIFT_Library
{
	/*
	 * =============================================
	 * Access Details
	 * =============================================
	 */

	/**
	 * JIRA Url
	 * e.g. http://localhost:8080
	 *
	 * @var string
	 */
	private $_url;

	/**
	 * user name to access JIRA
	 * e. g. admin
	 *
	 * @var admin
	 */
	private $_userName;

	/**
	 * password to access JIRA
	 *
	 * @var password
	 */
	private $_password;

	/**
	 * Authentication token
	 *
	 * @var string
	 */
	private $_authToken;

	/**
	 * Timeout in seconds to connect to the JIRA webservice
	 *
	 * @var type
	 */
	private $_connectionTimeout;

	/*
	 * =============================================
	 * Project Details
	 * =============================================
	 */

	/**
	 * Default project key to post issues in
	 * e.g. TEST
	 *
	 * @var string
	 */
	private $_projectKey;

	/*
	 * =============================================
	 * Bridge Details
	 * =============================================
	 */

	/**
	 * HTTP Client for connecting & making the API requests to the JIRA web service
	 *
	 * @var \SWIFT_HTTPClient
	 */
	private $Client;

	/**
	 * Table name in the Kayako DB
	 * default : TABLE_PREFIX . jira_issues
	 *
	 * @var string
	 */
	public static $_tableName = 'jiraissues';

	/**
	 * Single Instance to be used for Singleton JIRABridge
	 *
	 * @var \SWIFT_JIRABridge
	 */
	private static $_Instance = null;

	/**
	 * JIRA create issue meta data
	 *
	 * @var mixed
	 */
	private static $_Meta;

	/**
	 * The last error message
	 *
	 * @var string
	 */
	private $_error = 'No Error';

	/**
	 * The default constructor
	 * Reads & initializes the module settings from SWIFT
	 * Prepares the basic JIRA authentication
	 *
	 * @author Abhinav Kumar <abhinav.kumar@kayako.com>
	 * @return \SWIFT_JIRABridge on success and 'FALSE' otherwise
	 */
	public function __construct()
	{
		parent::__construct();

		$_SWIFT = SWIFT::GetInstance();

		if (!$_SWIFT->Settings->Get('bj_isenabled')) {
			return false;
		}

		$this->_url               = $_SWIFT->Settings->Get('bj_jiraurl');
		$this->_userName          = $_SWIFT->Settings->Get('bj_username');
		$this->_password          = $_SWIFT->Settings->Get('bj_password');
		$this->_connectionTimeout = $_SWIFT->Settings->Get('bj_timeout') ? $_SWIFT->Settings->Get('bj_timeout') : 1;

		$this->_projectKey = strtoupper($_SWIFT->Settings->Get('bj_defaultProject'));

		$this->_authToken = base64_encode($this->_userName . ':' . $this->_password);

		$this->Load->Library('HTTP:HTTPClient', [], true, 'jira');
		$this->Load->Library('HTTP:HTTPAdapter_Curl', [], true, 'jira');
		$this->Load->Library('JIRA:JIRAComment', false, false);
		$this->Load->LoadApp('Ticket:Ticket', APP_TICKETS);

		$this->Client = new SWIFT_HTTPClient($this->_url);

		$_Adapter = new SWIFT_HTTPAdapter_Curl();

		$_Adapter->AddOption('CURLOPT_CONNECTTIMEOUT', $this->_connectionTimeout);
		$_Adapter->AddOption('CURLOPT_TIMEOUT', $this->_connectionTimeout);

		$_Adapter->SetEncoding(SWIFT_HTTPBase::RESPONSETYPE_JSON);

		$this->Client->SetAdapter($_Adapter);

		$this->Client->SetHeaders('Authorization', 'Basic ' . $this->_authToken);
		$this->Client->SetHeaders('Accept', 'application/json');
		$this->Client->SetHeaders('Content-Type', 'application/json');
	}

	/**
	 * The default destructor
	 *
	 * @author Abhinav Kumar <abhinav.kumar@kayako.com>
	 * @return bool 'TRUE' on success & 'FALSE' otherwise
	 */
	public function __destruct()
	{
		return parent::__destruct();
	}

	/**
	 * Creates an JIRA Issue with the provided associated array $_data
	 * prepares the 'POST' body for the request & finally fires the REST request
	 * More Info on the REST API : https://developer.atlassian.com/display/JIRADEV/JIRA+REST+API+Example+-+Create+Issue
	 *
	 * @param array $_data an associative array of issue parametres
	 *
	 * @author Abhinav Kumar
	 *
	 * @throws SWIFT_Exception
	 * @return \SWIFT_JIRAIssue on success and 'FALSE' otherwise
	 */
	public function CreateIssue($_data)
	{
		if (!$this->GetIsClassLoaded()) {
			throw new SWIFT_Exception(__CLASS__ . '  ' . SWIFT_CLASSNOTLOADED);
		}

		if (array_key_exists('project', $_data) && $_data['project'] != '') {
			$_projectKey = $_data['project'];
		} else {
			$_projectKey = $this->_projectKey;
		}

		$this->Client->setUri($this->_url . 'rest/api/latest/issue');

		$_fields = [
			'project'     => [
				'key' => $_projectKey
			],
			'summary'     => $_data['summary'],
			'description' => $_data['description'],
			'issuetype'   => [
				'id' => $_data['issueType']
			],
			'priority'    => [
				'id' => $_data['priority']
			]
			//,'labels'      => $_labels
		];

		if (array_key_exists('security', $_data) && !empty($_data['security'])) {
			$_fields['security'] = [
				'id' => $_data['security']
			];
		}

		$this->Client->SetParameterPost('fields', $_fields);

		//Body prepared . . .time to fire the Request
		$_Response = $this->Client->Request(SWIFT_HTTPBase::POST, $this->_connectionTimeout);

		//Check if the response is not an error
		if ($_Response !== false) {
			if ($_Response->isSuccessful()) {
				//seems good so far . . .readh the response body
				$_Decoded = json_decode($_Response->getBody());

				if ($_Decoded) {
					if (isset($_Decoded->id) && isset($_Decoded->key)) {

						$_data['id']  = $_Decoded->id;
						$_data['key'] = $_Decoded->key;

						//We are almost there . . . time to create a local record for Ticket <->Issue reference
						$this->Load->Library('JIRA:JIRAIssueManager', false, false, 'jira');
						$_SWIFT = SWIFT::GetInstance();
						$_SWIFT->Database->AutoExecute(TABLE_PREFIX . self::$_tableName, [
							'ticketid' => $_SWIFT->Database->Escape($_data['kayakoTicketId']),
							'issueid'  => $_SWIFT->Database->Escape($_data['id']),
							'issuekey' => $_SWIFT->Database->Escape($_data['key'])
						], 'INSERT');

						$_SWIFTTicketObject = SWIFT_Ticket::GetObjectOnID($_data['kayakoTicketId']);

						if ($_SWIFTTicketObject && $_SWIFTTicketObject instanceof SWIFT_Ticket && $_SWIFTTicketObject->GetIsClassLoaded()) {
							$_title         = $_SWIFTTicketObject->GetTicketDisplayID();
							$_ticketSummary = $_SWIFTTicketObject->GetProperty('subject');
						} else {
							$_title         = $this->Language->Get('jira_kayakoticket');
							$_ticketSummary = '';
						}

						$_ticketURL = SWIFT::Get('basename') . '/Tickets/Ticket/View/' . $_data['kayakoTicketId'];

						$_postLink = $this->PostRemoteLink($_data['key'], $_ticketURL, $_title, $_ticketSummary);
						if (!$_SWIFT->Settings->Get('bj_jiraissuelinking') || is_bool($_postLink)) {

							//Finally, create and return a new SWIFT_JIRAIssueManger object
							return new SWIFT_JIRAIssueManager($_data);
						} else {
							if (!is_bool($_postLink)) {
								SWIFT::Notify(SWIFT::NOTIFICATION_ERROR, strip_tags($this->Language->Get('jira_error') . $_postLink));
							} else {
								$this->SetErrorMessage($this->Language->Get('jira_issuelinkingfail'));
							}
						}
					} else {
						$this->SetErrorMessage($this->Language->Get('jira_error'));
					}
				} else {
					$this->SetErrorMessage($this->Language->Get('jira_error'));
				}
			} else {
				$this->SetErrorMessage($this->parseErrorMessage($_Response));
			}
		}

		return false;
	}

	/**
	 * GetInstance method for ensuring a single instance per application is created
	 * Tests the connection and returns 'FALSE' if the connection is lost
	 *
	 * @author Abhinav Kumar
	 * @return mixed SWIFT_JIRABridge on success and 'FALSE' otherwise
	 */
	public static function GetInstance()
	{
		if (!self::$_Instance) {
			self::$_Instance = new SWIFT_JIRABridge();
		}

		return self::$_Instance;
	}

	/**
	 * Calls the JIRA REST API and checks if an issue is still active in JIRA
	 * If an issue is not found on JIRA deletes the corresponding entry from the
	 * Kayako database.
	 *
	 * @author Abhinav Kumar
	 *
	 * @param string $_issueKey
	 *
	 * @throws SWIFT_Exception
	 * @return bool
	 */
	public function IsIssueValid($_issueKey = null)
	{
		if (!$this->GetIsClassLoaded()) {
			throw new SWIFT_Exception(__CLASS__ . '  ' . SWIFT_CLASSNOTLOADED);
		}

		if ($_issueKey === null) {
			return false;
		}

		if (!$this->Client) {
			return false;
		}

		$this->Client->setUri($this->_url . 'rest/api/latest/issue/' . $_issueKey);

		$_Response = $this->Client->Request(SWIFT_HTTPBase::GET, $this->_connectionTimeout);

		if (!$_Response || !$_Response->isSuccessful()) {
			return false;
		}

		$_Decoded = json_decode($_Response->getBody());

		if (isset($_Decoded->errorMessages)) {

			/**
			 * Issue seems to have been deleted
			 * Delete from kayako database as well & return FALSE
			 */
			$_Sql = 'DELETE FROM ' . TABLE_PREFIX . self::$_tableName .
				' WHERE issueid = ' . $this->Database->Escape($_issueKey);

			$this->Database->Execute($_Sql);

			return false;
		}

		return true;
	}


	/**
	 * Calls the JIRA REST API and checks if an issue is still active in JIRA
	 * If an issue is not found on JIRA deletes the corresponding entry from the
	 * Kayako database.
	 *
	 * @author Abhinav Kumar
	 *
	 * @param string $_projectKey
	 *
	 * @throws SWIFT_Exception
	 * @return boolean 'TRUE' if the issue is still active and 'FALSE' otherwise
	 */
	public function IsProjectValid($_projectKey = null)
	{
		if (!$this->GetIsClassLoaded()) {
			throw new SWIFT_Exception(__CLASS__ . '  ' . SWIFT_CLASSNOTLOADED);
		}

		if (null == $_projectKey) {
			return false;
		}

		if (!$this->Test()) {
			return false;
		}

		$this->Client->setUri($this->_url . 'rest/api/latest/project/' . $_projectKey);

		$_Response = $this->Client->Request(SWIFT_HTTPBase::GET, $this->_connectionTimeout);
		if ($_Response && $_Response->isSuccessful()) {
			return true;
		}

		return false;
	}

	/**
	 * Parses a JSON decoded response &
	 * Converts that into an \SWIFT_JIRAIssue
	 *
	 * @author Abhinav Kumar
	 *
	 * @param \stdClass $_JSONDecoded - Decoded PHP object
	 *
	 * @throws SWIFT_Exception
	 * @return \SWIFT_JIRAIssue
	 */
	public function ParseResponse($_JSONDecoded)
	{
		if (!$this->GetIsClassLoaded()) {
			throw new SWIFT_Exception(__CLASS__ . '  ' . SWIFT_CLASSNOTLOADED);
		}

		$_Data = [];

		$_dataStore = $this->Database->QueryFetch("SELECT ticketid FROM " . TABLE_PREFIX . self::$_tableName . " WHERE issueid = '" .
			intval($_JSONDecoded->id) . "'");

		$_Data['id']             = $_JSONDecoded->id;
		$_Data['key']            = $_JSONDecoded->key;
		$_Data['project']        = $_JSONDecoded->fields->project->key;
		$_Data['status']         = $_JSONDecoded->fields->status->name;
		$_Data['commentsCount']  = $_JSONDecoded->fields->comment->total;
		$_Data['issueType']      = $_JSONDecoded->fields->issuetype->name;
		$_Data['summary']        = $_JSONDecoded->fields->summary;
		$_Data['kayakoTicketId'] = $_dataStore['ticketid'];
		$_Data['priority']       = $_JSONDecoded->fields->priority->name;
		$_Data['assignee']       = isset($_JSONDecoded->fields->assignee->displayName) ? $_JSONDecoded->fields->assignee->displayName : "Unassigned";
		$_Data['reporter']       = $_JSONDecoded->fields->reporter->name;
		$_Data['description']    = $_JSONDecoded->fields->description;
		$_Data['labels']         = $_JSONDecoded->fields->labels;
		$_Data['created']        = strtotime($_JSONDecoded->fields->created);
		$_Data['updated']        = strtotime($_JSONDecoded->fields->updated);

		$this->Load->Library('JIRA:JIRAIssueManager', false, false, 'jira');

		return new SWIFT_JIRAIssueManager($_Data);
	}

	/**
	 * Calls the JIRA REST Api and fetches the issue details
	 *
	 * @author Abhinav Kumar
	 *
	 * @param string $_issueID
	 *
	 * @throws SWIFT_Exception
	 * @return \SWIFT_JIRAIssue on success and 'FALSE' otherwise
	 */
	public function Get($_issueID)
	{
		if (!$this->GetIsClassLoaded()) {
			throw new SWIFT_Exception(__CLASS__ . '  ' . SWIFT_CLASSNOTLOADED);
		}

		if (empty($_issueID) || !$this->IsIssueValid($_issueID)) {
			return false;
		}

		$_apiURL = $this->_url . 'rest/api/latest/issue/' . $_issueID;

		$this->Client->setUri($_apiURL);

		$_Response = $this->Client->Request(SWIFT_HTTPBase::GET, $this->_connectionTimeout);

		if ($_Response && $_Response->isSuccessful()) {
			$_Decoded = json_decode($_Response->getBody());

			return $this->ParseResponse($_Decoded);
		}

		return false;
	}

	/**
	 * Fetches ONE JIRA Issue based on passed parameter from the local database
	 *
	 * @author Abhinav Kumar <abhinav.kumar@kayako.com>
	 *
	 * @param string $_param
	 * @param string $_value
	 *
	 * @throws SWIFT_Exception
	 * @return \SWIFT_JIRAIssue on success and 'FALSE' otherwise
	 */
	public function GetIssueBy($_param, $_value)
	{
		if (!$this->GetIsClassLoaded()) {
			throw new SWIFT_Exception(__CLASS__ . '  ' . SWIFT_CLASSNOTLOADED);
		}

		$_query = "SELECT * FROM " . TABLE_PREFIX . self::$_tableName . " WHERE " . $this->Database->Escape($_param) . " = '" .
			$this->Database->Escape($_value) . "'";

		$_DataStore = $this->Database->QueryFetch($_query);
		$this->Load->Library('JIRA:JIRAIssueManager', false, false, 'jira');
		if ($this->IsIssueValid($_DataStore['issueid'])) {
			return $this->Get($_DataStore['issueid']);
		}

		return false;
	}

	/**
	 * Fetches All the JIRA Issue based on passed parameter from the local database
	 *
	 * @author Abhinav Kumar
	 *
	 * @param string $_param
	 * @param string $_value
	 *
	 * @throws SWIFT_Exception
	 * @return mixed an array of \SWIFT_JIRAIssue on success and 'FALSE' otherwise
	 */
	public function GetIssuesBy($_param, $_value)
	{
		if (!$this->GetIsClassLoaded()) {
			throw new SWIFT_Exception(__CLASS__ . '  ' . SWIFT_CLASSNOTLOADED);
		}

		$_Query     = "SELECT * FROM " . TABLE_PREFIX . self::$_tableName . " WHERE " . $this->Database->Escape($_param) . " = '" .
			$this->Database->Escape($_value) . "'";
		$_DataStore = $this->Database->QueryFetchAll($_Query);

		$_issues = [];

		$this->Load->Library('JIRA:JIRAIssueManager', false, false, 'jira');

		foreach ($_DataStore as $_Data) {
			if ($this->IsIssueValid($_Data['issuekey'])) {
				$_issues[] = $this->Get($_Data['issuekey']);
			}
		}

		if (_is_array($_issues)) {
			return $_issues;
		} else {
			return false;
		}
	}

	/**
	 * Fetches the available issue types from JIRA REST API
	 *
	 * @author Abhinav Kumar
	 *
	 * @throws SWIFT_Exception
	 * @return mixed json decoded object on success and 'FALSE' otherwise
	 */
	public function GetIssueTypes()
	{
		if (!$this->GetIsClassLoaded()) {
			throw new SWIFT_Exception(__CLASS__ . '  ' . SWIFT_CLASSNOTLOADED);
		}

		$this->Client->setUri($this->_url . 'rest/api/latest/issuetype');

		$_Response = $this->Client->Request(SWIFT_HTTPBase::GET, $this->_connectionTimeout);

		if ($_Response && $_Response->isSuccessful()) {
			return json_decode($_Response->getBody());
		}

		return false;
	}

	/**
	 * Fetches the available issue types from JIRA REST API
	 *
	 * @author Abhinav Kumar
	 *
	 * @param $_project
	 *
	 * @throws SWIFT_Exception
	 * @return mixed json decoded object on success and 'FALSE' otherwise
	 */
	public function GetIssueTypesByProject($_project)
	{
		if (!$this->GetIsClassLoaded()) {
			throw new SWIFT_Exception(__CLASS__ . '  ' . SWIFT_CLASSNOTLOADED);
		}

		if (empty($_project)) {
			throw new SWIFT_Exception('Project ' . $this->Language->Get('jira_noempty'));
		}

		if (!empty($_project) && $this->isProjectValid($_project)) {
			$_apiURL = $this->_url . 'rest/api/latest/project/' . $_project;
			$this->Client->setUri($_apiURL);

			$_Response = $this->Client->Request(SWIFT_HTTPBase::GET, $this->_connectionTimeout);

			if ($_Response && $_Response->isSuccessful()) {
				$_Decoded = json_decode($_Response->getBody());
				if ($_Decoded && isset($_Decoded->issueTypes)) {
					return $_Decoded->issueTypes;
				}
			}

			return false;
		}

		return false;
	}

	/**
	 * Fetches the available security levels from JIRA REST API
	 *
	 * @author Abhinav Kumar
	 *
	 * @param string $_projectKey
	 * @param string $_issueType
	 *
	 * @throws SWIFT_Exception
	 * @return mixed json decoded object on success and 'FALSE' otherwise
	 */
	public function GetSecurityLevelsByProject($_projectKey, $_issueType)
	{
		if (!$this->GetIsClassLoaded()) {
			throw new SWIFT_Exception(__CLASS__ . '  ' . SWIFT_CLASSNOTLOADED);
		}

		$_SWIFT = SWIFT::GetInstance();

		if (empty($_projectKey)) {
			throw new SWIFT_Exception('Project Key ' . $_SWIFT->Language->Get('jira_noempty'));
		}

		if (!$this->IsProjectValid($_projectKey)) {
			throw new SWIFT_Exception($_projectKey . ' is not a valid project');
		}

		$_CreateMeta = $this->GetCreateMeta();

		if ($_CreateMeta && _is_array($_CreateMeta) && array_key_exists($_projectKey, $_CreateMeta)) {
			if (array_key_exists('security', $_CreateMeta[$_projectKey])) {
				return $_CreateMeta[$_projectKey]['security'];
			}

			return false;
		}

		return false;
	}

	/**
	 * Fetches the available issue priorities from JIRA REST API
	 *
	 * @author Abhinav Kumar
	 *
	 * @throws SWIFT_Exception
	 * @return mixed json decoded object on success and 'FALSE' otherwise
	 */

	public function GetPriorities()
	{
		if (!$this->GetIsClassLoaded()) {
			throw new SWIFT_Exception(__CLASS__ . '  ' . SWIFT_CLASSNOTLOADED);
		}
		$_prioritiesContainer = [];

		$this->Client->setUri($this->_url . 'rest/api/latest/priority');

		$_Response = $this->Client->Request(SWIFT_HTTPBase::GET, $this->_connectionTimeout);
		if ($_Response && $_Response->isSuccessful()) {

			$_PrioritiesDecoded = json_decode($_Response->getBody());
			if ($_PrioritiesDecoded && _is_array($_PrioritiesDecoded)) {
				foreach ($_PrioritiesDecoded as $_Priority) {
					$_prioritiesContainer[] = [
						'title' => $_Priority->name,
						'value' => $_Priority->id
					];
				}
			}
		}

		return $_prioritiesContainer;
	}


	/**
	 * Fetches the available projects from JIRA REST API
	 *
	 * @author Abhinav Kumar
	 *
	 * @throws SWIFT_Exception
	 * @return mixed json decoded object on success and 'FALSE' otherwise
	 */
	public function GetProjects()
	{
		if (!$this->GetIsClassLoaded()) {
			throw new SWIFT_Exception(__CLASS__ . '  ' . SWIFT_CLASSNOTLOADED);
		}

		$this->Client->setUri($this->_url . 'rest/api/latest/project');

		$_Response = $this->Client->Request(SWIFT_HTTPBase::GET, $this->_connectionTimeout);
		if ($_Response && $_Response->isSuccessful()) {
			$_JIRAProjects = json_decode($_Response->getBody());

			if (_is_array($_JIRAProjects)) {
				$_Projects = [];
				foreach ($_JIRAProjects as $_Project) {
					$_Project    = [
						'title' => $_Project->name,
						'value' => $_Project->key
					];
					$_Projects[] = $_Project;
				}

				return $_Projects;
			}
		} else {
			return $this->SetErrorMessage('No Projects found');
		}

		return false;
	}

	/**
	 * Not Implemented yet
	 *
	 * @return boolean
	 */
	public function GetReporters()
	{
		return false;
		$this->Client->setUri($this->_url . 'rest/api/latest/project');

		$_Response = $this->Client->Request(SWIFT_HTTPBase::GET, $this->_connectionTimeout);

		if ($_Response && $_Response->isSuccessful()) {
			return json_decode($_Response->getBody());
		}

		return false;
	}

	/**
	 * Reads and parses an error message returned from an JIRA REST API Request
	 *
	 * @author Abhinav Kumar
	 *
	 * @param SWIFT_HTTPResponse $_Response
	 *
	 * @return string the error message or FALSE(bool) if no error is read
	 */
	protected function parseErrorMessage(SWIFT_HTTPResponse $_Response)
	{
		$_Decoded = json_decode($_Response->getBody());

		$_parsedErrors = '';

		if ($_Decoded) {
			if (isset($_Decoded->errors)) {
				$_errors = $_Decoded->errors;
				foreach ($_errors as $_Key => $_Val) {
					$_parsedErrors .= $_Val . PHP_EOL;
				}
			}
			if (isset($_Decoded->errorMessages)) {
				foreach ($_Decoded->errorMessages as $_errorMessage) {
					$_parsedErrors .= $_errorMessage . PHP_EOL;
				}
			}

			return $_parsedErrors;
		}

		return false;
	}

	/**
	 * Unlinks a JIRA Issue from a Kayako Support Ticket
	 *
	 * @author Abhinav Kumar
	 *
	 * @param string $_issueKey The issue key to unlink
	 * @param int    $_ticketID
	 *
	 * @throws SWIFT_Exception
	 * @return bool
	 */
	public function UnlinkIssue($_issueKey, $_ticketID)
	{
		if (!$this->GetIsClassLoaded()) {
			throw new SWIFT_Exception(__CLASS__ . ' - ' . SWIFT_CLASSNOTLOADED);
		}

		if ($_issueKey) {
			$_JIRAIssue = $this->GetIssueBy('issuekey', $_issueKey);

			if ($_JIRAIssue) {
				$_SWIFT              = SWIFT::GetInstance();
				$_SWIFT_TicketObject = SWIFT_Ticket::GetObjectOnID($_ticketID);
				$_JIRAComment        = new SWIFT_JIRAComment();
				$_JIRAComment->SetBody(sprintf($this->Language->Get('unlinkedFromJIRA'), $_SWIFT_TicketObject->GetTicketDisplayID(), $_SWIFT->Staff->GetProperty('fullname')));
				if ($_JIRAIssue->PostComment($_JIRAComment) !== false) {
					if ($this->RemoveRemoteLink($_issueKey, $_ticketID)) {
						/**
						 * Comment posted & Remote link removed
						 * Delete from kayako database as well & return TRUE
						 */
						$_query = 'DELETE FROM ' . TABLE_PREFIX . self::$_tableName
							. ' WHERE issuekey=\'' . $_issueKey . '\'';

						if ($this->Database->Execute($_query) !== false) {
							return true;
						}
					}
				} else {
					$this->SetErrorMessage($this->Language->Get('jira_error') . ' ' . $this->Language->Get('jira_comment_notposted'));

					return false;
				}
			} else {
				$this->SetErrorMessage($this->Language->Get('jira_noissuekey'));

				return false;
			}
		} else {
			$this->SetErrorMessage($this->Language->Get('jira_noissuekey'));

			return false;
		}
	}

	/**
	 * Posts a comment to JIRA
	 *
	 * @author Abhinav Kumar
	 *
	 * @param SWIFT_JIRAComment $_Comment
	 *
	 * @return bool
	 * @throws SWIFT_Exception
	 */
	public function PostComment(SWIFT_JIRAComment $_Comment = null)
	{
		if (!$this->GetIsClassLoaded()) {
			throw new SWIFT_Exception(__CLASS__ . ' - ' . SWIFT_CLASSNOTLOADED);
		}

		if ($_Comment) {
			if (!is_string($_Comment->GetBody())) {
				throw new SWIFT_Exception(SWIFT_INVALIDDATA);
			}

			$_JIRAIssue = $_Comment->GetIssue();

			$_commentBody = $_Comment->GetBody();

			$_visibility = $_Comment->GetVisibility();

			if ($_JIRAIssue) {
				$_apiURL = $this->_url . 'rest/api/latest/issue/' . $_JIRAIssue->GetKey() . '/comment';

				$this->Client->setUri($_apiURL);

				$this->Client->SetParameterPost('body', $_commentBody);

				if ($_visibility && _is_array($_visibility) && array_key_exists('type', $_visibility) && array_key_exists('value', $_visibility)) {
					$this->Client->SetParameterPost('visibility', $_visibility);
				}

				$_Response = $this->Client->Request(SWIFT_HTTPBase::POST, $this->_connectionTimeout);

				if ($_Response && $_Response->isSuccessful()) {
					$_ResponseDecoded = json_decode($_Response->getBody());

					return $_ResponseDecoded->id;
				}

				return false;
			}
			$this->_error = $this->Language->Get('jira_noissuefound');

			return false;
		}

		return false;
	}

	public function GetCreateMeta()
	{
		$_SWIFT = SWIFT::GetInstance();

		$_CreateMetaCached = $_SWIFT->Cache->Get('jiracreatemeta');

		if ($_CreateMetaCached) {
			return $_CreateMetaCached;
		}

		$_queryArray = [
			'expand' => 'projects.issuetypes.fields'
		];

		$_query = http_build_query($_queryArray);

		$_apiURL = $this->_url . 'rest/api/2/issue/createmeta?' . $_query;

		$this->Client->setUri($_apiURL);

		$_Response = $this->Client->Request(SWIFT_HTTPBase::GET, $this->_connectionTimeout);

		if ($_Response && $_Response->isSuccessful()) {
			$_CreateMeta          = json_decode($_Response->getBody());
			$_CreateMetaContainer = $_ProjectCreateMeta = [];
			foreach ($_CreateMeta->projects as $_Project) {

				foreach ($_Project->issuetypes as $_issueType) {

					$_ProjectCreateMeta['issuetype'][$_issueType->id] = $_issueType->name;
					if (isset($_issueType->fields->security->allowedValues) && _is_array($_issueType->fields->security->allowedValues)) {

						foreach ($_issueType->fields->security->allowedValues as $_securityLevel) {
							$_ProjectCreateMeta['security'][$_securityLevel->id] = $_securityLevel->name;
						}
					}
				}
				$_CreateMetaContainer[$_Project->key] = $_ProjectCreateMeta;
			}

			if (_is_array($_CreateMetaContainer)) {
				$_SWIFT->Cache->Update('jiracreatemeta', $_CreateMetaContainer);

				return $_CreateMetaContainer;
			}
		}

		return false;
	}

	/**
	 * Fetches the updated Meta from the JIRA API
	 *
	 * @author Abhinav Kumar
	 *
	 * @param string $_projectKey
	 * @param string $_issueType
	 *
	 * @throws SWIFT_Exception
	 * @return bool 'TRUE' on success & 'FALSE' otherwise
	 */
	public function GetCreateMetaByProject($_projectKey, $_issueType = '')
	{
		$_SWIFT = SWIFT::GetInstance();

		if (empty($_projectKey)) {
			throw new SWIFT_Exception('Project Key: ' . $_SWIFT->Language->Get('jira_noempty'));
		}

		if (!$this->IsProjectValid($_projectKey)) {
			throw new SWIFT_Exception('Project ' . $_projectKey . ' doesn\'t exist');
		}

		$_queryArray = [
			'projectKeys' => $_projectKey,
			'expand'      => 'projects.issuetypes.fields'
		];

		if (!empty($_issueType)) {
			$_queryArray['issuetypeIds'] = $_issueType;
		}

		$_query = http_build_query($_queryArray);

		$_apiURL = $this->_url . 'rest/api/2/issue/createmeta?' . $_query;

		$this->Client->setUri($_apiURL);

		$_Response = $this->Client->Request(SWIFT_HTTPBase::GET, $this->_connectionTimeout);

		if ($_Response && $_Response->isSuccessful()) {
			return json_decode($_Response->getBody());
		}

		//}
		return $_SWIFT->Cache->Get('jiracreatemeta');
	}

	/**
	 * Returns the last error
	 *
	 * @author Abhinav Kumar <abhinav.kumar@kayako.com>
	 * @return string
	 */
	public function GetErrorMessage()
	{
		return $this->_error;
	}

	/**
	 * Sets an error message
	 *
	 * @param string $_error The error message to set
	 *
	 * @author Abhinav Kumar
	 * @return \SWIFT_JIRABridge
	 */
	public function SetErrorMessage($_error)
	{
		if (is_string($_error) && $_error != '') {
			$this->_error = $_error;

			return $this;
		}

		return true;
	}

	/**
	 * Fetches all comments by a parameter
	 *
	 * @author Abhinav Kumar
	 *
	 * @param string $_param The parameter to filter comments by
	 * @param mixed  $_value The parameter value
	 *
	 * @return \SWIFT_JIRAComment|boolean \SWIFT_JIRAComment on success and 'FALSE' otherwise
	 */
	public function FetchAllCommentsBy($_param = null, $_value = null)
	{
		if ($_param && $_value) {

			if ($_param == 'issuekey') {

				$_apiURL = $this->_url . 'rest/api/latest/issue/' . $_value . '/comment';

				$this->Client->setUri($_apiURL);

				$_Response = $this->Client->Request(SWIFT_HTTPBase::GET, $this->_connectionTimeout);

				if ($_Response && $_Response->isSuccessful()) {
					$_ResponseDecoded = json_decode($_Response->getBody())->comments;

					$_CommentsContainer = [];

					foreach ($_ResponseDecoded as $_Response) {

						$_JIRAComment = new SWIFT_JIRAComment();
						$_JIRAComment->ParseJSON($_Response);

						if ($_JIRAComment) {
							$_CommentsContainer[] = $_JIRAComment;
						}
					}

					return $_CommentsContainer;
				}

				return false;
			}
		}

		return false;
	}

	/**
	 * Tests connection to JIRA
	 * Can/Should be used before every operation
	 *
	 * @author Abhinav Kumar <abhinav.kumar@kayako.com>
	 *
	 * @return boolean 'TRUE' if the connection is successful, 'FALSE' otherwise
	 */
	public function Test()
	{
		if (!$this->GetIsClassLoaded()) {
			throw new SWIFT_Exception(__CLASS__ . '  ' . SWIFT_CLASSNOTLOADED);
		}

		$_SWIFT = SWIFT::GetInstance();

		if (!$_SWIFT->Settings->Get('bj_isenabled')) {
			return false;
		}

		$this->Client->setUri($this->_url);

		$_Response = $this->Client->Request(SWIFT_HTTPBase::GET, $this->_connectionTimeout);

		if ($_Response && ($_Response->isSuccessful() || $_Response->isRedirect())) {
			return true;
		}

		return false;
	}

	/**
	 * Links a ticket with an existing JIRA issue
	 *
	 * @author Abhinav Kumar
	 *
	 * @param string $_ticketID The kayako ticket id
	 * @param string $_JIRAIssueID
	 * @param array  $_data
	 *
	 * @return boolean
	 */
	public function LinkIssue($_ticketID, $_JIRAIssueID, $_data = [])
	{
		if ($_JIRAIssueID && $this->IsIssueValid($_JIRAIssueID)
			&& $_ticketID
		) {

			$_JIRAIssueManager = $this->Get($_JIRAIssueID);

			if ($_JIRAIssueManager && $_JIRAIssueManager instanceof SWIFT_JIRAIssueManager) {
				if (array_key_exists('description', $_data) && $_data['description'] != '') {

					$_JIRAComment = new SWIFT_JIRAComment();
					$_JIRAComment->SetBody($_data['description']);
					$_JIRAComment->SetIssue($_JIRAIssueManager);

					$this->PostComment($_JIRAComment);
				}
				//We are almost there . . . time to create a local record for Ticket <->Issue reference
				$this->Load->Library('JIRA:JIRAIssueManager', false, false, 'jira');
				$_SWIFT   = SWIFT::GetInstance();
				$_updated = $_SWIFT->Database->AutoExecute(TABLE_PREFIX . self::$_tableName, [
					'ticketid' => $_SWIFT->Database->Escape($_ticketID),
					'issueid'  => $_SWIFT->Database->Escape($_JIRAIssueManager->GetId()),
					'issuekey' => $_SWIFT->Database->Escape($_JIRAIssueID)
				], 'INSERT');
				if ($_updated) {
					$_SWIFTTicketObject = SWIFT_Ticket::GetObjectOnID($_ticketID);
					if ($_SWIFTTicketObject && $_SWIFTTicketObject instanceof SWIFT_Ticket && $_SWIFTTicketObject->GetIsClassLoaded()) {
						$_title         = $_SWIFTTicketObject->GetTicketDisplayID();
						$_ticketSummary = $_SWIFTTicketObject->GetProperty('subject');
					} else {
						$_title         = $this->Language->Get('jira_kayakoticket');
						$_ticketSummary = '';
					}

					$_ticketURL = SWIFT::Get('basename') . '/Tickets/Ticket/View/' . $_ticketID;

					if ($_SWIFT->Settings->Get('bj_jiraissuelinking')) {

						$_postLink = $this->PostRemoteLink($_JIRAIssueID, $_ticketURL, $_title, $_ticketSummary);

						if (!is_bool($_postLink)) {
							SWIFT::Notify(SWIFT::NOTIFICATION_ERROR, strip_tags($this->Language->Get('jira_error') . $_postLink));

							return false;
						}
					}
				}
			} else {
				$this->SetErrorMessage($this->Language->Get('jira_noissuefound'));

				return false;
			}
		} else {
			$this->SetErrorMessage($this->Language->Get('jira_noissuefound'));

			return false;
		}

		return true;
	}

	/**
	 * Posts a remote link to JIRA
	 *
	 * @author Abhinav Kumar
	 *
	 * @param string $_issueKey  The JIRA issue key to the send to the request to
	 * @param string $_ticketURL Kayako ticket URL for backlinking
	 * @param string $_title     Remote link title
	 * @param string $_summary   Remote link summary
	 * @param int    $_status    Kayako ticket status
	 *
	 * @return bool "true" on Success, "false" otherwise
	 * @throws SWIFT_Exception If the Class is not Loaded
	 */
	public function PostRemoteLink($_issueKey, $_ticketURL, $_title = '', $_summary = '', $_status = 1)
	{
		if (!$this->GetIsClassLoaded()) {
			throw new SWIFT_Exception(SWIFT_CLASSNOTLOADED);
		}

		if (empty($_issueKey) || empty($_ticketURL)) {
			throw new SWIFT_Exception(SWIFT_INVALIDDATA);
		}

		if (!$this->IsIssueValid($_issueKey)) {
			throw new SWIFT_Exception('Invalid Issue');
		}
		$_SWIFT = SWIFT::GetInstance();

		$_apiURL = $this->_url . 'rest/api/latest/issue/' . $_issueKey . '/remotelink';

		$this->Client->SetURI($_apiURL);

		$_globalID = 'system=' . $_ticketURL;

		$objectPayload = [
			'url'   => $_ticketURL,
			'title' => $_title,
			'icon'  => [
				'url16x16' => SWIFT::Get('swiftpath') . '/favicon.ico',
				'title'    => SWIFT_PRODUCT
			]
		];

		if ($_SWIFT->Settings->Get('bj_includesubjectinlink')) {
			$objectPayload['summary'] = $_summary;
		}

		$_ticketStatusClosed = false;
		$_ticketStatusCache  = $_SWIFT->Cache->Get('statuscache');

		if (_is_array($_ticketStatusCache)) {
			foreach ($_SWIFT->Cache->Get('statuscache') as $_ticketStatus) {
				if ($_ticketStatus['markasresolved']) {
					$_ticketStatusClosed = $_ticketStatus['ticketstatusid'];
				}
			}
		}

		if ($_ticketStatusClosed && $_status == $_ticketStatusClosed) {
			$objectPayload['status'] = [
				'resolved' => true,
				'icon'     => [
					'url16x16' => SWIFT::Get('swiftpath') . '__modules/jira/resources/resolved.png',
					'title'    => $this->Language->Get('jira_ticketclosed'),
					'link'     => $_ticketURL
				]
			];
		}

		$this->Client->SetParameterPost('globalId', $_globalID);
		$this->Client->SetParameterPost('object', $objectPayload);

		$_Response = $this->Client->Request(SWIFT_HTTPBase::POST, $this->_connectionTimeout);

		if ($_Response && $_Response->isSuccessful()) {
			return true;
		} else {
			$_ResponseDecoded   = json_decode($_Response->getBody());
			$_responseContainer = get_object_vars($_ResponseDecoded);

			return $_responseContainer['errorMessages'][0];
		}
	}

	/**
	 * Removes a remote issue link
	 *
	 * @author Abhinav Kumar
	 *
	 * @param string   $_issueKey The JIRA issue key
	 * @param string | $_ticketID The Kayako ticket ID/Key
	 *
	 * @return bool "true" on Success, "false" otherwise
	 * @throws SWIFT_Exception If the Class is not Loaded
	 */
	public function RemoveRemoteLink($_issueKey, $_ticketID)
	{
		if (!$this->GetIsClassLoaded()) {
			throw new SWIFT_Exception(SWIFT_CLASSNOTLOADED);
		}

		$_ticketURL = SWIFT::Get('basename') . '/Tickets/Ticket/View/' . $_ticketID;

		$_globalID = 'system=' . $_ticketURL;

		$_apiURL = $this->_url . 'rest/api/latest/issue/' . $_issueKey . '/remotelink';

		$this->Client->SetURI($_apiURL);

		$this->Client->SetParameterGet('globalId', $_globalID);

		$_Response = $this->Client->Request(SWIFT_HTTPBase::DELETE);

		if ($_Response->getResponseCode() == 204) {
			return true;
		} else {
			echo $_Response->getResponseCode(), ' : ', $_Response->getRawData();

			return false;
		}
	}

	/**
	 * Get project Roles
	 *
	 * @author Abhinav Kumar
	 *
	 * @param string $_projectKey
	 *
	 * @throws SWIFT_Exception
	 * @return bool "true" on Success, "false" otherwise
	 */
	public function GetProjectRoles($_projectKey)
	{
		if (!$this->GetIsClassLoaded()) {
			throw new SWIFT_Exception(__CLASS__ . ' : ' . SWIFT_CLASSNOTLOADED);
		}

		if (empty($_projectKey) || !is_string($_projectKey)) {
			throw new SWIFT_Exception('Project Key can not be empty');
		}

		$_apiURL = $this->_url . 'rest/api/latest/project/' . $_projectKey . '/role';
		$this->Client->SetURI($_apiURL);

		$_Response = $this->Client->Request(SWIFT_HTTPBase::GET, $this->_connectionTimeout);

		if ($_Response && $_Response->isSuccessful()) {
			$_RolesDecoded = json_decode($_Response->getBody());

			return get_object_vars($_RolesDecoded);
		}

		return false;
	}

	/**
	 * Helper Function returns the ticket URL for a given interface
	 *
	 * @author Abhinav Kumar
	 *
	 * @param string $_ticketID  The ticket id/key
	 * @param int    $_interface The interface
	 *
	 * @return bool "true" on Success, "false" otherwise
	 * @throws SWIFT_Exception If the Class is not Loaded
	 */
	public function GetTicketURL($_ticketID, $_interface = SWIFT_Interface::INTERFACE_STAFF)
	{
		if (!$this->GetIsClassLoaded()) {
			throw new SWIFT_Exception(SWIFT_CLASSNOTLOADED);
		}

		if ($_interface == SWIFT_Interface::INTERFACE_STAFF) {
			return SWIFT::Get('basename') . '/Tickets/Ticket/View/' . $_ticketID;
		}

		return true;
	}

	/**
	 * This function will be used to fetch all remote links related to a JIRA issue w.r.t ticket id
	 *
	 * @author Amarjeet Kaur
	 *
	 * @param string $_issueKey
	 * @param string $_ticketID
	 *
	 * @return bool
	 * @throws SWIFT_Exception If the Class is not Loaded
	 */
	public function GetRemoteLinks($_issueKey, $_ticketID)
	{
		if (!$this->GetIsClassLoaded()) {
			throw new SWIFT_Exception(__CLASS__ . ':  ' . SWIFT_CLASSNOTLOADED);
		}

		if (!empty($_issueKey) && !empty($_ticketID)) {
			$_apiURL = $this->_url . 'rest/api/latest/issue/' . $_issueKey . '/remotelink';

			$_ticketURL = SWIFT::Get('basename') . '/Tickets/Ticket/View/' . $_ticketID;

			$_globalID = 'system=' . $_ticketURL;

			$this->Client->SetURI($_apiURL);

			$this->Client->SetParameterGet('globalId', $_globalID);

			$_Response = $this->Client->Request(SWIFT_HTTPBase::GET, $this->_connectionTimeout);

			if ($_Response->getResponseCode() == 404) {
				$_ResponseDecoded = json_decode($_Response->getBody());

				return get_object_vars($_ResponseDecoded);
			}
		}

		return true;
	}
}