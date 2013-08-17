<?php
/**
*
* @author PoPoutdoor
*
* @package RSS_input
* @version $Id:$
* @copyright (c) 2008-2013 PoPoutdoor
* @license http://opensource.org/licenses/gpl-license.php GNU Public License
*
*/

/**
* @package module_install
*/
class acp_find_info
{
	function module()
	{
		return array(
			'filename'	=> 'acp_find',
			'title'		=> 'ACP_FIND',
			'version'	=> '1.1.0',
			'modes'		=> array(
				'manage' => array('title' => 'ACP_FIND', 'auth' => 'acl_a_board', 'cat' => array('ACP_BOARD_CONFIGURATION')),
			),
		);
	}

	function install()
	{
	}

	function uninstall()
	{
	}
}

?>
