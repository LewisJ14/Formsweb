<?php
/**
 * @package		Forms
 * @author		<a href='http://www.devfuse.com'>DevFuse</a>
 * @copyright	(c) 2015 DevFuse
 */

namespace IPS\form\extensions\core\FrontNavigation;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Front Navigation Extension: Form
 */
class _Form extends \IPS\core\FrontNavigation\FrontNavigationAbstract
{
	/**
	 * Get Type Title which will display in the AdminCP Menu Manager
	 *
	 * @return	string
	 */
	public static function typeTitle()
	{
		return \IPS\Member::loggedIn()->language()->addToStack('frontnavigation_form');
	}     
    
	/**
	 * Allow multiple instances?
	 *
	 * @return	string
	 */
	public static function allowMultiple()
	{
		return TRUE;
	}
    
	/**
	 * Get configuration fields
	 *
	 * @param	array	$configuration	The existing configuration, if editing an existing item
	 * @param	int		$id				The ID number of the existing item, if editing
	 * @return	array
	 */
	public static function configuration( $existingConfiguration, $id = NULL )
	{
		$forms = array();
		foreach( new \IPS\Patterns\ActiveRecordIterator( \IPS\Db::i()->select( '*', 'form_forms' ), 'IPS\form\Form' ) as $form )
		{
			$forms[ $form->_id ] = $form->_title;
		}
		
		return array(
			new \IPS\Helpers\Form\Select( 'menu_content_form', isset( $existingConfiguration['menu_content_form'] ) ? $existingConfiguration['menu_content_form'] : NULL, FALSE, array( 'options' => $forms, 'unlimited' => 0, 'unlimitedLang' => 'menu_content_form_unlimitedLang' ), NULL, NULL, NULL, 'menu_content_form' ),
			new \IPS\Helpers\Form\Radio( 'menu_title_form_type', isset( $existingConfiguration['menu_title_form_type'] ) ? $existingConfiguration['menu_title_form_type'] : 0, NULL, array( 'options' => array( 0 => 'menu_title_form_inherit', 1 => 'menu_title_form_custom' ), 'toggles' => array( 1 => array( 'menu_title_form' ) ) ), NULL, NULL, NULL, 'menu_title_form_type' ),
			new \IPS\Helpers\Form\Translatable( 'menu_title_form', NULL, NULL, array( 'app' => 'form', 'key' => $id ? "form_menu_title_{$id}" : NULL ), NULL, NULL, NULL, 'menu_title_form' ),
		);
	}   
    
	/**
	 * Parse configuration fields
	 *
	 * @param	array	$configuration	The values received from the form
	 * @return	array
	 */
	public static function parseConfiguration( $configuration, $id )
	{
		if ( $configuration['menu_title_form_type'] )
		{
			\IPS\Lang::saveCustom( 'form', "form_menu_title_{$id}", $configuration['menu_title_form'] );
		}
		else
		{
			\IPS\Lang::deleteCustom( 'form', "form_menu_title_{$id}" );
		}
		
		unset( $configuration['menu_title_form'] );
		
		return $configuration;
	}         
    
	/**
	 * Can access?
	 *
	 * @return	bool
	 */
	public function canView()
	{       
	    /* Can view form? */
	    if( isset( $this->configuration['menu_content_form'] ) AND $this->configuration['menu_content_form'] )
        {
            /* Permission to view form? */
    		try
    		{
    			$form = \IPS\form\Form::loadAndCheckPerms( $this->configuration['menu_content_form'] );
    		}
    		catch ( \OutOfRangeException $e )
    		{
    			return FALSE;
    		}                       
        }
        
	    /* Default view check */
		if( parent::canView() )
		{
			return TRUE;
		}        
        		
		return FALSE;        	   
	}
	
	/**
	 * Get Title
	 *
	 * @return	string
	 */
	public function title()
	{
	    /* Default title */
	    if( !isset( $this->configuration['menu_content_form'] ) OR !$this->configuration['menu_content_form'] )
        {
            return \IPS\Member::loggedIn()->language()->addToStack('frontnavigation_form');    
        }
       
        /* Customized form menu */
		if( isset( $this->configuration['menu_title_form_type'] ) AND $this->configuration['menu_title_form_type'] )
		{
			return \IPS\Member::loggedIn()->language()->addToStack( "form_menu_title_{$this->id}" );
		}
		else
		{
			return \IPS\Member::loggedIn()->language()->addToStack( "form_form_{$this->configuration['menu_content_form']}" );
		}	   
	}
	
	/**
	 * Get Link
	 *
	 * @return	\IPS\Http\Url
	 */
	public function link()
	{
	    /* Return form link */
	    if( isset( $this->configuration['menu_content_form'] ) AND $this->configuration['menu_content_form'] )
        {
            return \IPS\form\Form::load( $this->configuration['menu_content_form'] )->url();
        }	   
       
        /* Return default link */
		return \IPS\Http\Url::internal( "app=form&module=forms&controller=index", 'front', 'form' );
	}
	
	/**
	 * Is Active?
	 *
	 * @return	bool
	 */
	public function active()
	{
	    /* Is in form app? */
	    if( \IPS\Dispatcher::i()->application->directory == 'form' )
        {
    	    /* Return form link */
    	    if( isset( $this->configuration['menu_content_form'] ) AND $this->configuration['menu_content_form'] != \IPS\Request::i()->id )
            {
                return false;
            }  
            
            return true;              
        }
        
        return false;
	}
}