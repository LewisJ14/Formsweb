<?php
/**
 * @package		Forms
 * @author		<a href='http://www.devfuse.com'>DevFuse</a>
 * @copyright	(c) 2015 DevFuse
 */

namespace IPS\form\modules\admin\form;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * forms
 */
class _forms extends \IPS\Node\Controller
{
	/**
	 * Node Class
	 */
	protected $nodeClass = 'IPS\form\Form';
	
	/**
	 * Execute
	 *
	 * @return	void
	 */
	public function execute()
	{
		\IPS\Dispatcher::i()->checkAcpPermission( 'forms_manage' );
		parent::execute();
	}
    
	/**
	 * Modify root buttons
	 */
	public function _getRootButtons()
	{
		$buttons = parent::_getRootButtons();
	    
        /* Change form title */
		$buttons['add']['title'] = 'add_form';
        
        /* Add quick tags button */
		$buttons['quick_tags']	= array(
			'icon'	=> 'cog',
			'title'	=> 'quick_tags',
			'link'	=> \IPS\Http\Url::internal( "app=form&module=form&controller=forms&do=quicktags" ),
			'data'	=> array( 'ipsDialog' => '', 'ipsDialog-title' => \IPS\Member::loggedIn()->language()->addToStack('quick_tags') )
		);        
            
		return $buttons;
	}  
    
	/**
	 * Setup quick tags page
	 */    
	public function quicktags()
	{
		/* Output */
		\IPS\Output::i()->title		= \IPS\Member::loggedIn()->language()->addToStack( 'quick_tags' );
		\IPS\Output::i()->output 	= \IPS\Theme::i()->getTemplate( 'forms' )->quicktagsList();
	}      
}