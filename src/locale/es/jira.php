<?php
/**
 * @copyright      2001-2015 Kayako
 * @license        https://www.freebsd.org/copyright/freebsd-license.html
 * @link           https://github.com/kayako/jira-integration
 */

$__LANG = array(
	// ========General ========
	'settings_bj'               => 'JIRA',
	'tabjira'                   => 'JIRA',
	'exportToJIRA'              => 'Export to JIRA',
	'postJIRAComment'           => 'Post JIRA Comment',
	'JIRATicketID'              => 'JIRA Issues',
	'jira_noempty'              => ' can not be empty',
	'jira_sensitive'            => 'Please remember to make sure that you are aware of the security conditions of your JIRA installation before sharing any sensitive information about your customers.',
	'exportedToJIRA'            => 'JIRA issue created from this ticket',
	'jira_linkedtojira'         => 'JIRA issue linked with this ticket',
	'unlinkedFromJIRA'          => 'Ticket unlinked from JIRA issue',
	'jira_post'                 => 'Post',
	'jira_save'                 => 'Save',
	'jira_cancel'               => 'Cancel',
	'jira_comment_posted'       => 'New comment posted on JIRA',
	'jira_confimunlink'         => 'Are you sure want to unlink this issue?',
	'jira_confirmunlinknoissue' => 'There was a problem accessing the linked issue, it might have been deleted by the JIRA admin.\nDo you want to unlink it anyway??',
	'jira_unlinkissue'          => 'Unlink the JIRA issue from the current ticket',
	'jira_user'                 => 'User',
	'jira_newissue'             => 'Create a new JIRA issue',
	'jira_linktoexisting'       => 'Link to an existing JIRA issue',
	'jira_linkedtojira_comment' => 'This issue was linked to the Kayako support ticket #%s by %s',
	'jira_kayakoticket'         => 'Kayako Ticket',
	'jira_ticketclosed'         => 'Resolved',
	'jira_linkedticketclosed'   => 'The linked kayako ticket %s was closed by %s',
	'jira_wait'                 => 'Please wait . . .',

	//Form Fields
	'jira_summary'              => 'Issue summary',
	'jira_summary_desc'         => 'In JIRA, the issue summary is the title of an issue.',
	'jira_project'              => 'Project',
	'jira_project_desc'         => 'Which JIRA project should this issue be created in?',
	'jira_issuetype'            => 'Issue Type',
	'jira_issuetype_desc'       => 'Select which issue type to use for this new issue.',
	'jira_priority'             => 'Issue priority',
	'jira_description'          => 'Description',
	'jira_description_desc'     => 'Provide a description of this issue. ',
	'jira_comment'              => 'Comment',
	'jira_issue_id'             => 'JIRA Issue ID',
	'jira_issue_id_desc'        => 'Which JIRA issue should this ticket be linked to?',
	'jira_comment_visibility'   => 'Viewable by',
	'jira_security_level'       => 'Security Level',
	'jira_notapplicable'        => 'Not Applicable',
	'bj_noproject'              => 'No projects available',

	//Issue History Columns
	'jira_issueid'              => 'Issue ID',
	'jira_updated'              => 'Updated',
	'jira_status'               => 'Status',
	'jira_assignedto'           => 'Assigned To',
	'jira_action'               => 'Action',

	//Errors & Exceptions
	'jira_error'                => 'JIRA Error ',
	'connection_error'          => 'Kayako was not able to connect to the your JIRA installation. Connection Error: Could not connect to ',
	'jira_comment_notposted'    => 'This comment could not be posted to JIRA',
	'jira_noissuefound'         => 'No JIRA issues linked to this ticket have been found',
	'jira_unlinkerror'          => 'An error was uncountered while trying to unlink this issue. Please try again.',
	'jira_noissuekey'           => 'An issue ID wasn\'t provided',
	'jira_issueinvalid'         => 'The issue ID provided is not a valid JIRA issue ID',
	'jira_issuelinkingfail'     => 'The issue linking was not successful',
	'jira_noprojectsloaded'     => 'We could not fetch the list of JIRA projects. Kindly cross check if you have JIRA projects available.',
	'jira_issuetypenotfound'    => 'No JIRA issue types could be fetched this time . . . Please try again',
	'jira_noremotelink'         => 'The remote link does not exist.',
);