<?php
/**
 * @package		Forms
 * @author		<a href='http://www.devfuse.com'>DevFuse</a>
 * @copyright	(c) 2015 DevFuse
 */

namespace IPS\form\modules\front\forms;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * index
 */
class _index extends \IPS\Dispatcher\Controller
{
	/**
	 * Execute
	 *
	 * @return	void
	 */

       			
    
	/**
	 * Form Index
	 *
	 * @return	void
	 */
	protected function manage()
	{
        /* Require form url? */
	    if( \IPS\Settings::i()->fm_require_form )
        {
            \IPS\Output::i()->error( 'form_url_required', '', 403, '' );
        }
       
        /* Skip the rest and just show legacy form? */
        if( \IPS\Settings::i()->fm_legacy_form )
        {
    	    /* Set up the step array */
    		$steps = array();
            		
            /* Setup first basic step */		
    		$steps['form_subject'] = function( $data )
    		{
          		/* Basic form */
        		$form = new \IPS\Helpers\Form( 'subject', 'continue' );
        		$form->class = ' ipsForm_fullWidth';
                    
                /* Build subject list */
        		foreach( \IPS\form\Form::roots() as $subjects )
        		{
        			$formOptions[ $subjects->form_id ] = $subjects->_title;
        		}             
                
                /* Add goal select */
        		$form->add( new \IPS\Helpers\Form\Node( 'subject', NULL, TRUE, array(
        			'url'					=> \IPS\Http\Url::internal( 'app=form&module=forms&controller=index', 'front', 'form' ),
        			'class'					=> 'IPS\form\Form',
                    'permissionCheck'		=> 'add',
                    'subnodes'              => FALSE
        		) ) );                
                
    			if( $values = $form->values() )
    			{
    			    /* Check required fields */
                    if( !$values['subject'] )
                    {
                        $form->error = \IPS\Member::loggedIn()->language()->addToStack( 'subject_required' );
                    } 
                            
                    /* Any form errors? */
                    if( isset( $form->error ) AND $form->error )
                    {
                        return $form;   
                    }
    
    				return $values;
    			}            
    			
                return \IPS\Output::i()->output = $form;
    		};
    		
            /* Now lets get more info */
    		$steps['form_contents'] = function ( $data )
    		{
    		    /* We are we headed next? */
    			$formURL = ( isset( $data['subject'] ) AND $data['subject'] ) ? $data['subject']->url() : \IPS\Http\Url::internal( "app=form&module=forms&controller=index", 'front', 'form' )->setQueryString(array( '_new' => '1' ) ); 
                
                /* Redirect to form page */
                \IPS\Output::i()->redirect( $formURL );
    		};
            
    		/* Display */   
    		\IPS\Output::i()->title = \IPS\Member::loggedIn()->language()->addToStack('__app_form');
    		\IPS\Output::i()->output = \IPS\Theme::i()->getTemplate('forms')->legacyForm( (string) new \IPS\Helpers\Wizard( $steps, \IPS\Http\Url::internal( "app=form&module=forms&controller=index", 'front', 'form' ), FALSE ) );             
            return;
        }
        
        /* Show a list of forms */
        if( !\IPS\Settings::i()->form_landing_page )
        {
            $this->formlist();            
        }
        /* Show a selected form */
        else
        {
            $this->viewform( \IPS\Settings::i()->form_landing_page );  
        }        	       
	}
    
	/**
	 * View form
	 *
	 * @return	void
	 */
	protected function viewform( $formID=0, $returnForm=FALSE )
	{
	    /* User or system provided form id? */
	    $formID = ( $formID ) ? $formID : \IPS\Request::i()->id;
       
        /* Get form */
		try
		{
			$container = \IPS\form\Form::loadAndCheckPerms( $formID );
		}
        /* Form not loaded, show form list instead. */
		catch ( \OutOfRangeException $e )
		{
			return $this->formlist();
		}
        
	    /* Make sure we can restart form wizard if needed */
	    if( \IPS\Settings::i()->fm_legacy_form && isset( \IPS\Output::i()->breadcrumb['module'] ) AND \IPS\Output::i()->breadcrumb['module'] )
	    {
		    \IPS\Output::i()->breadcrumb['module'][0] = \IPS\Output::i()->breadcrumb['module'][0].'?_new=1';	    
	    }        

        /* Show form */
		\IPS\Output::i()->title = $container->_title;
		\IPS\Output::i()->breadcrumb[] = array( NULL, $container->_title );
		\IPS\Output::i()->output = \IPS\Theme::i()->getTemplate('logs')->create( $container, \IPS\form\Log::create( $container ) ); 
        
        return \IPS\Output::i()->output;
	}     
    
	/**
	 * Show form list
	 *
	 * @return	void
	 */
	protected function formlist()
	{
		\IPS\Output::i()->title	 = \IPS\Member::loggedIn()->language()->addToStack('__app_form');
		\IPS\Output::i()->output = \IPS\Theme::i()->getTemplate( 'forms' )->formList();
	} 
    
	/**
	 * Show confirmation message
	 *
	 * @return	void
	 */
	protected function confirmation()
	{
        /* Show confirmation page */
		\IPS\Output::i()->title	 = \IPS\Member::loggedIn()->language()->addToStack('form_message_sent');
		\IPS\Output::i()->output = \IPS\Theme::i()->getTemplate( 'logs' )->confirmationPage( \IPS\form\Log::confirmation( (int) \IPS\Request::i()->id ) );
	}       
}
