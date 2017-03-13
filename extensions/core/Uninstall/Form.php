<?php
/**
 * @package		Forms
 * @author		<a href='http://www.devfuse.com'>DevFuse</a>
 * @copyright	(c) 2015 DevFuse
 */

namespace IPS\form\extensions\core\Uninstall;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Uninstall callback
 */
class _Form
{
	/**
	 * Code to execute before the application has been uninstalled
	 *
	 * @param	string	$application	Application directory
	 * @return	array
	 */
	public function preUninstall( $application )
	{
	}

	/**
	 * Code to execute after the application has been uninstalled
	 *
	 * @param	string	$application	Application directory
	 * @return	array
	 */
	public function postUninstall( $application )
	{
	    /* Delete custom field entries */
        \IPS\Db::i()->delete( 'core_sys_lang_words', array( "word_key LIKE 'form_field_%'" ) );
        
	    /* Setup list of our permissions */
	    $ourPermissions = array( 'g_fs_submit_wait', 'g_fs_bypass_captcha', 'g_fs_view_logs', 'g_fs_moderate_logs', 'g_fs_can_attach' );
        
        /* Go through and delete */                      
        foreach( $ourPermissions as $perm )
        {
            if( \IPS\Db::i()->checkForColumn( 'core_groups', $perm ) )
            {
                \IPS\Db::i()->dropColumn( 'core_groups', array( $perm ) );    
            }            
        }        
	}
}