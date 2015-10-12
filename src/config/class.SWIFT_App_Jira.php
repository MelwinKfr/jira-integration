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
 * The Jira App Library
 *
 * @author Mahesh Salaria
 */
class SWIFT_App_jira extends SWIFT_App
{

	/**
	 * Constructor
	 *
	 * @author Mahesh Salaria
	 *
	 * @param string $_appName
	 *
	 * @return \SWIFT_App_jira
	 */
	public function __construct($_appName)
	{
		parent::__construct($_appName);

		return true;
	}

	/**
	 * Destructor
	 *
	 * @author Mahesh Salaria
	 * @return bool
	 */
	public function __destruct()
	{
		parent::__destruct();

		return true;
	}

	/**
	 * Initialize the App
	 *
	 * @author Mahesh Salaria
	 *
	 * @return bool
	 * @throws SWIFT_Exception If the Class is not Loaded
	 */
	public function Initialize()
	{
		parent::Initialize();

		return true;
	}
}