<?php
/**
 * @copyright      2001-2015 Kayako
 * @license        https://www.freebsd.org/copyright/freebsd-license.html
 * @link           https://github.com/kayako/jira-integration
 */

/**
 * The base class for all SWIFT_HTTP based library
 *
 * @author Abhinav Kumar
 */
class SWIFT_HTTPBase extends SWIFT_Library
{

	//HTTP Version
	const HTTP_OLD = '1.0';
	const HTTP_NEW = '1.1';

	//Method Types
	const GET    = 'GET';
	const POST   = 'POST';
	const PUT    = 'PUT';
	const DELETE = 'DELETE';
	const HEAD   = 'HEAD';

	//Response Codes
	const HTTP_OK                  = '200';
	const HTTP_MOVED_PERMANENTLY   = '301';
	const HTTP_BAD_REQUEST         = '400';
	const HTTP_FORBIDDEN           = '403';
	const HTTP_NOT_FOUND           = '404';
	const HTTP_SERVER_ERROR        = '500';
	const HTTP_SERVICE_UNAVAILABLE = '503';

	//Encoding Types
	const ENC_FORMDATA   = 'multipart/form-data';
	const ENC_URLENCODED = 'application/x-www-form-urlencoded';
	const ENC_PLAIN      = 'text/plain';

	//Response Types
	const RESPONSETYPE_XML  = 'xml';
	const RESPONSETYPE_JSON = 'json';

	//Authentication methods
	const AUTH_BASIC = 'basic';

	/**
	 * Checks if a Request Type is valid
	 *
	 * @param string $_Method
	 *
	 * @return bool
	 */
	public function isValidMethod($_Method)
	{
		$_Method = strtoupper($_Method);
		if ($_Method == self::GET || $_Method == self::POST || $_Method == self::PUT || $_Method == self::DELETE) {

			return true;
		}

		return false;
	}
}