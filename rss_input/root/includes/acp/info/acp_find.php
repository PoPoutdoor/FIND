<?php
/**
*
* @author PoPoutdoor
*
* @package RSS_input
* @version $Id:$
* @copyright (c) 2008-2011 PoPoutdoor
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
			'version'	=> '1.0.1',
			'modes'		=> array(
			'feed_import' => array('title' => 'ACP_FIND', 'auth' => 'acl_a_board'),
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
