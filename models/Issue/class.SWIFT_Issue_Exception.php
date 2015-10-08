<?php
/**
 * ###############################################
 *
 * SWIFT Framework
 * _______________________________________________
 *
 * @author         Varun Shoor
 *
 * @package        SWIFT
 * @copyright      Copyright (c) 2001-2015, Kayako
 * @license        http://www.kayako.com/license
 * @link           http://www.kayako.com
 *
 * ###############################################
 */

/**
 * The Jira Issue Exception Handling Class
 *
 * @author Mahesh Salaria
 */
class SWIFT_Issue_Exception extends SWIFT_Exception
{
	/**
	 * Constructor
	 *
	 * @author Mahesh Salaria
	 *
	 * @param string $_errorMessage The Error Message
	 * @param int    $_errorCode    The Error Code
	 *
	 * @return \SWIFT_Issue_Exception "true" on Success, "false" otherwise
	 */
	public function __construct($_errorMessage, $_errorCode = 0)
	{
		parent::__construct($_errorMessage, $_errorCode);

		return true;
	}

	/**
	 * Destructor
	 *
	 * @author Mahesh Salaria
	 * @return bool "true" on Success, "false" otherwise
	 */
	public function __destruct()
	{
		parent::__destruct();

		return true;
	}
}