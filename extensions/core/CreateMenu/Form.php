<?php
/**
 * @package		Forms
 * @author		<a href='http://www.devfuse.com'>DevFuse</a>
 * @copyright	(c) 2015 DevFuse
 */

namespace IPS\form\extensions\core\CreateMenu;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Create Menu Extension: Form
 */
class _Form
{
	/**
	 * Get Items
	 *
	 * @return	array example: return array( '{example_key}' => { instance of \IPS\Http\Url::internal } ) );
	 */
	public function getItems()
	{
		if ( \IPS\form\Log::canCreate( \IPS\Member::loggedIn() ) )
		{
			return array( 'form_submit' => array( 'link' => \IPS\Http\Url::internal( "app=form&module=forms", 'front', 'form' ) ) );
		}

		return array();
	}
}