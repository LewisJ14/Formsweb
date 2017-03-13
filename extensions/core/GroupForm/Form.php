<?php
/**
 * @package		Forms
 * @author		<a href='http://www.devfuse.com'>DevFuse</a>
 * @copyright	(c) 2015 DevFuse
 */

namespace IPS\form\extensions\core\GroupForm;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Admin CP Group Form
 */
class _Form
{
	/**
	 * Process Form
	 *
	 * @param	\IPS\Form\Tabbed		$form	The form
	 * @param	\IPS\Member\Group		$group	Existing Group
	 * @return	void
	 */
	public function process( &$form, $group )
	{		
        /* Forms Permissions */
		$form->addHeader( 'forms_permissions' );
		$form->add( new \IPS\Helpers\Form\Number( 'g_fs_submit_wait', $group->g_fs_submit_wait, NULL, array( 'unlimited' => 0, 'unlimitedLang' => 'g_fs_submit_wait_unlimitedLang' ), NULL, NULL, \IPS\Member::loggedIn()->language()->addToStack('g_fs_submit_wait_seconds') ) );
		$form->add( new \IPS\Helpers\Form\YesNo( 'g_fs_bypass_captcha', $group->g_fs_bypass_captcha ) );
		$form->add( new \IPS\Helpers\Form\YesNo( 'g_fs_view_logs', $group->g_fs_view_logs ) );   
        $form->add( new \IPS\Helpers\Form\YesNo( 'g_fs_moderate_logs', $group->g_fs_moderate_logs ) );
        $form->add( new \IPS\Helpers\Form\YesNo( 'g_fs_can_attach', $group->g_fs_can_attach ) );         
	}
	
	/**
	 * Save
	 *
	 * @param	array				$values	Values from form
	 * @param	\IPS\Member\Group	$group	The group
	 * @return	void
	 */
	public function save( $values, &$group )
	{
	    /* Setup list of our permissions */
	    $ourPermissions = array( 'g_fs_submit_wait', 'g_fs_bypass_captcha', 'g_fs_view_logs', 'g_fs_moderate_logs', 'g_fs_can_attach' );
        
        /* Go through and save */                      
        foreach( $ourPermissions as $perm )
        {
            $group->$perm = (int) $values[ $perm ];   
        }	
	}
}