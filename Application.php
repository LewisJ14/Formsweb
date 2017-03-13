<?php
/**
 * @package		Forms
 * @author		<a href='http://www.devfuse.com'>DevFuse</a>
 * @copyright	(c) 2015 DevFuse
 */
 
namespace IPS\form;

/**
 * Form Manager Application Class
 */
class _Application extends \IPS\Application
{    
	/**
	 * Install 'other' items.
	 *
	 * @return void
	 */
	public function installOther()
	{
	    /* Give non guests what permissions they need */
		foreach( \IPS\Member\Group::groups( TRUE, FALSE ) as $group )
		{
		    /* P */
			$group->g_fs_can_attach = TRUE;                      
			$group->save();
            
		    /* VIP */
            if( $group->g_access_cp )
            {
    			$group->g_fs_submit_wait    = 0;
                $group->g_fs_bypass_captcha = TRUE;
                $group->g_fs_view_logs      = TRUE;
                $group->g_fs_moderate_logs  = TRUE;                                      
    			$group->save();
            }            
		}
	}    
        
	/**
	 * [Node] Get Icon for tree
	 *
	 * @note	Return the class for the icon (e.g. 'globe')
	 * @return	string|null
	 */
	protected function get__icon()
	{
		return 'envelope';
	}
    
	/**
	 * Default front navigation
	 *
	 * @code
	 	
	 	// Each item...
	 	array(
			'key'		=> 'Example',		// The extension key
			'app'		=> 'core',			// [Optional] The extension application. If ommitted, uses this application	
			'config'	=> array(...),		// [Optional] The configuration for the menu item
			'title'		=> 'SomeLangKey',	// [Optional] If provided, the value of this language key will be copied to menu_item_X
			'children'	=> array(...),		// [Optional] Array of child menu items for this item. Each has the same format.
		)
	 	
	 	return array(
		 	'rootTabs' 		=> array(), // These go in the top row
		 	'browseTabs'	=> array(),	// These go under the Browse tab on a new install or when restoring the default configuraiton; or in the top row if installing the app later (when the Browse tab may not exist)
		 	'browseTabsEnd'	=> array(),	// These go under the Browse tab after all other items on a new install or when restoring the default configuraiton; or in the top row if installing the app later (when the Browse tab may not exist)
		 	'activityTabs'	=> array(),	// These go under the Activity tab on a new install or when restoring the default configuraiton; or in the top row if installing the app later (when the Activity tab may not exist)
		)
	 * @endcode
	 * @return array
	 */
	public function defaultFrontNavigation()
	{
		return array(
			'rootTabs'		=> array(),
			'browseTabs'	=> array( array( 'key' => 'Form' ) ),
			'browseTabsEnd'	=> array(),
			'activityTabs'	=> array()
		);
	}    
}