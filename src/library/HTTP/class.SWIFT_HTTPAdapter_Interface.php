<?php
/**
 * @copyright      2001-2015 Kayako
 * @license        https://www.freebsd.org/copyright/freebsd-license.html
 * @link           https://github.com/kayako/jira-integration
 */

/**
 * Base Interface to be implemented to all HTTP Adapters
 *
 * @author Abhinav Kumar
 */
interface SWIFT_HTTPAdapter_Interface
{
	/**
	 * Makes an HTTP GET Request
	 */
	public function Get($_URL, $_vars = array());

	/**
	 * Makes an HTTP POST Request
	 */
	public function Post($_URL, $_vars = array());

	/**
	 * Makes an HTTP PUT Request
	 */
	public function Put($_URL, $_vars = array());

	/**
	 * Makes an HTTP DELETE Request
	 */
	public function Delete($_URL, $_vars = array());

	/**
	 * Makes an HTTP request based on the specified $_Method
	 * to an $_URL with an optional array of string of $_vars
	 *
	 * @param string $_method
	 * @param string $_URL
	 * @param array  $_vars
	 *
	 * @return \SWIFT_HTTPResponse on success or 'false' otherwise
	 */
	public function Request($_method, $_URL, $_vars = array());
}