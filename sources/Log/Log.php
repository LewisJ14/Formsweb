<?php
/**
 * @package		Forms
 * @author		<a href='http://www.devfuse.com'>DevFuse</a>
 * @copyright	(c) 2015 DevFuse
 */
 
namespace IPS\form;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Log Model
 */
class _Log extends \IPS\Content\Item implements \IPS\Content\Permissions, \IPS\Content\Searchable
{
	/**
	 * @brief	Multiton Store
	 */
	protected static $multitons;
	
	/**
	 * @brief	Application
	 */
	public static $application = 'form';
	
	/**
	 * @brief	Module
	 */
	public static $module = 'forms';
	
	/**
	 * @brief	Database Table
	 */
	public static $databaseTable = 'form_logs';
	
	/**
	 * @brief	Database Prefix
	 */
	public static $databasePrefix = 'log_';    
    
	/**
	 * @brief	Icon
	 */
	public static $icon = 'comment';    
    
	/**
	 * @brief	Node Class
	 */
	public static $containerNodeClass = 'IPS\form\Form';    
	
	/**
	 * @brief	Database Column Map
	 */
	public static $databaseColumnMap = array(
		'title'	    => 'member_name',
        'author'	=> 'member_id',
        'container'	=> 'form_id',
        'date'      => 'date',
		'content'	=> 'message',        
	);	    
    
	/**
	 * @brief	Title
	 */
	public static $title = 'form_logs';
	
	/**
	 * @brief	Form language prefix
	 */
	public static $formLangPrefix = 'log_';
    
    
	/**
	 * @brief	Hover preview
	 */
	public $tableHoverUrl = TRUE;
    	
	/**
	 * Should posting this increment the poster's post count?
	 *
	 * @param	\IPS\Node\Model|NULL	$container	Container
	 * @return	void
	 */
	public static function incrementPostCount( \IPS\Node\Model $container = NULL )
	{
		return FALSE;
	}
	
	/**
	 * Set Default Values
	 *
	 * @return	void
	 */
	public function setDefaultValues()
	{
		$this->date       = time();
        $this->ip_address = \IPS\Member::loggedIn()->ip_address;
	}
		
	/**
	 * Get department
	 *
	 * @return	\IPS\nexus\Support\Department
	 */
	public function get_form()
	{
		try
		{
			return \IPS\form\Form::load( $this->_data['form_id'] );
		}
		catch ( \OutOfRangeException $e ) { }	   
	}
	
	/**
	 * Set department
	 *
	 * @param	\IPS\nexus\Support\Department
	 * @return	void
	 */
	public function set_form( Department $department )
	{
		$this->_data['form_id'] = $form->id;
	}
    
	/**
	 * Get container
	 *
	 * @return	\IPS\Node\Model
	 * @note	Certain functionality requires a valid container but some areas do not use this functionality (e.g. messenger)
	 * @note	Some functionality refers to calls to the container when managing comments (e.g. deleting a comment and decrementing content counts). In this instance, load the parent items container.
	 * @throws	\OutOfRangeException|\BadMethodCallException
	 */
	public function container()
	{
		$container = NULL;
        
        if( !$this->mapped('container') )
        {
            return $container;    
        }
		
		try
		{
			$container = \IPS\form\Form::load( $this->mapped('container') );
		}
		catch( \BadMethodCallException $e ) {}
		
		return $container;
	}    
		
	/**
	 * Can a given member create this type of content?
	 *
	 * @param	\IPS\Member	$member		The member
	 * @param	int			$container	Container (e.g. forum) ID, if appropriate
	 * @param	bool		$showError	If TRUE, rather than returning a boolean value, will display an error
	 * @return	bool
	 */
	public static function canCreate( \IPS\Member $member, \IPS\Node\Model $container=NULL, $showError=FALSE )
	{
		/* Can we access the module? */
		if ( !parent::canCreate( $member, $container, $showError ) )
		{
			return FALSE;
		}
        
        /* Calculate either form or group wait time */
        $submitWait = (int) ( isset( $container->_options->submit_wait ) AND $container->_options->submit_wait ) ? (int) $container->_options->submit_wait : (int) \IPS\Member::loggedIn()->group['g_fs_submit_wait'];

        /* Do we need to stop? */
		if ( $submitWait )
		{	  
		    /* Last message date */
            $messagesSentToday = \IPS\Db::i()->select( 'COUNT(*) AS count, MAX(log_date) AS max', 'form_logs', array( 'log_ip_address=? AND log_date>?', $member->ip_address, \IPS\DateTime::create()->sub( new \DateInterval( 'PT'.$submitWait.'S' ) )->getTimeStamp() ) )->first();

			if( $messagesSentToday['count'] )
			{
			    /* Next try? */
				$next        = \IPS\DateTime::ts( $messagesSentToday['max'] )->add( new \DateInterval( 'PT'.$submitWait.'S' ) );
                $secondsDiff = $next->getTimestamp() - time();
                
                /* Make relative to wait time */
                if( $secondsDiff <= 3600 )
                {
                    $errorMessage = \IPS\Member::loggedIn()->language()->addToStack( 'form_submit_wait_minutes', FALSE, array( 'sprintf' => array( $next->diff( new \IPS\DateTime )->i ) ) );                          
                }
                else if( $secondsDiff <= 86400 )
                {
                    $errorMessage = \IPS\Member::loggedIn()->language()->addToStack( 'form_submit_wait_hours', FALSE, array( 'sprintf' => array( $next->diff( new \IPS\DateTime )->h, $next->diff( new \IPS\DateTime )->i ) ) );     
                }                
                else
                {
                    $errorMessage = \IPS\Member::loggedIn()->language()->addToStack( 'form_submit_wait_days', FALSE, array( 'sprintf' => array( $next->diff( new \IPS\DateTime )->d, $next->diff( new \IPS\DateTime )->h, $next->diff( new \IPS\DateTime )->i ) ) );   
                }                

				if ( $showError )
				{
				    \IPS\Output::i()->error( $errorMessage, '', 429, '', array( 'Retry-After' => $next->format('r') ) );
				}
				
				return FALSE;
			}
        }
        
		return TRUE;
	}        
        
	/**
	 * Get elements for add/edit form
	 *
	 * @param	\IPS\Content\Item|NULL	$item		The current item if editing or NULL if creating
	 * @param	\IPS\Node\Model|NULL	$container	Container (e.g. forum), if appropriate
	 * @return	array
	 */
	public static function formElements( $item=NULL, \IPS\Node\Model $container=NULL )
	{		
		/* Basic elements */
		$return = parent::formElements( $item, $container );  

        unset( $return['title'] );

		/* Custom Fields */         
		$customFields = \IPS\form\Form\Field::roots( NULL, NULL, array( 'field_form_id=?', $container->form_id ) );
		
		foreach ( $customFields as $field )
		{
			$return[] = $field->buildHelper( $field->value, $container );
		}

        /* Remove captcha for bypass groups */
        if( \IPS\Member::loggedIn()->group['g_fs_bypass_captcha'] OR ( isset( $container->_options->disable_captcha ) AND $container->_options->disable_captcha ) )
        {
            unset( $return['captcha'] );    
        }        
        
        /* Add captcha for required groups */
        if( \IPS\Settings::i()->bot_antispam_type !== 'none' AND ( !\IPS\Member::loggedIn()->group['g_fs_bypass_captcha'] && ( isset( $container->_options->disable_captcha ) AND !$container->_options->disable_captcha ) ) )
        {
            /* Add capctcha if not present */
            if( !isset( $return['captcha'] ) )
            {
                $return['captcha'] = new \IPS\Helpers\Form\Captcha;     
            }                         
        }
        
        /* Move captcha to bottom for guests */
        if( !\IPS\Member::loggedIn()->member_id AND isset( $return['captcha'] ) )
        {
            $return['captcha'] = array_shift( $return );    
        }
 
		/* Return */
		return $return;
	}
    
	/**
	 * Process created object BEFORE the object has been created
	 *
	 * @param	array	$values	Values from form
	 * @return	void
	 */
	protected function processBeforeCreate( $values )
	{
		$this->post_key	= isset( $values['post_key'] ) ? $values['post_key'] : md5( uniqid() );

		parent::processBeforeCreate( $values );
	}    
		
	/**
	 * Process create/edit form
	 *
	 * @param	array				$values	Values from form
	 * @return	void
	 */
	public function processForm( $values )
	{
		parent::processForm( $values );
   
        /* Save author */
        $this->member_id = (int) \IPS\Member::loggedIn()->member_id;    
 
        if( $this->member_id )
        {
            $this->member_name  = \IPS\Member::loggedIn()->name;  
            $this->member_email = \IPS\Member::loggedIn()->email;  
        }   
        else
        {
            $this->member_name  = \IPS\Member::loggedIn()->language()->get('guest');  
            $this->member_email = '';             
        }    
        
        /* Save message now */
        $this->save();

		/* Get tag values */
		$tags = \IPS\form\Form::returnTagValues( $this->container(), $this, $values );

        /* Setup log contents */
        $logContent = $this->container()->formTranslate( 'log' );
                
        /* Swap out our tags */
		foreach( $tags as $key => $value )
		{
		    \IPS\Member::loggedIn()->language()->parseOutputForDisplay( $value );
            $logContent = preg_replace( $key, $value, $logContent );
		}  
        
        /* Get alert settings */
        $pmSettings    = json_decode( $this->container()->pm_settings ); 
        $topicSettings = json_decode( $this->container()->topic_settings );
        $emailSettings = json_decode( $this->container()->email_settings );        
               
        /* Send out alerts */
        \IPS\form\Alerts::_sendPM( $pmSettings, \IPS\Member::loggedIn(), $this->container(), $tags );
        \IPS\form\Alerts::_sendEmail( $emailSettings, \IPS\Member::loggedIn(), $this->container(), $tags );       
        
        /* Create topic alert */
        if( $topic = \IPS\form\Alerts::_createTopic( $topicSettings, \IPS\Member::loggedIn(), $this->container(), $tags ) )
        {
            /* Save topic id */
            $this->topic = $topic;
        }           

        /* Assign final log and save */
        $this->message = $logContent;   
        $this->save();                
	}
    
	/**
	 * Process created object AFTER the object has been created
	 *
	 * @param	\IPS\Content\Comment|NULL	$comment	The first comment
	 * @param	array						$values		Values from form
	 * @return	void
	 */
	protected function processAfterCreate( $comment, $values )
	{
		parent::processAfterCreate( $comment, $values );

		/* Parse custom fields */         
		$customFields = \IPS\form\Form\Field::roots( NULL, NULL, array( 'field_form_id=?', $this->container()->form_id ) );
		
		/* Init rules argument list */
		$rulesArgs = array( $this );
		
		foreach( $customFields as $field )
		{
			$rulesArgs[] = $values[ "form_field_{$field->id}" ];
			
			/* Delete old attachments */
			if ( $field->buildHelper() instanceof \IPS\Helpers\Form\Editor )
			{
				$field->claimAttachments( $this->id );
			}
            
			/* Insert form values */
			\IPS\Db::i()->insert( 'form_values', array(
				'value_field_id'	=> $field->_id,
				'value_member_id'	=> (int) \IPS\Member::loggedIn()->member_id,
				'value_form_id'		=> $this->container()->_id,
				'value_log_id'		=> $this->id,
				'value_value'		=> $field->buildHelper()->stringValue( $values[ "form_field_{$field->id}" ] ),                                
			)	);                                   
		}

		/* Send confirm email */
		if( isset( $this->container()->_options->confirm_email ) AND isset( $values[ "form_field_".$this->container()->_options->confirm_email ] ) )
		{
		    $confirmEmailAddress = $values[ "form_field_".$this->container()->_options->confirm_email ];    
		}
		else
		{
		    if( isset( $this->container()->_options->confirm_email ) AND $this->container()->_options->confirm_email )
		    {
                $confirmEmailAddress = \IPS\Member::loggedIn()->email;    
		    }
		}
		
		if( isset( $confirmEmailAddress ) AND $confirmEmailAddress )
		{
		    $emailSettings = json_decode( $this->container()->email_settings ); 
		    \IPS\form\Alerts::_sendConfirmEmail( $confirmEmailAddress, $emailSettings, \IPS\Member::loggedIn(), $this->container() );             
		}
        
		/* Send confirm pm */
		if( \IPS\Member::loggedIn()->member_id AND isset( $this->container()->_options->confirm_pm ) AND $this->container()->_options->confirm_pm )
		{
		    $pmSettings = json_decode( $this->container()->pm_settings ); 
		    \IPS\form\Alerts::_sendConfirmPM( $pmSettings, \IPS\Member::loggedIn(), $this->container() );    
		}        

		/* Trigger Rules */
		if ( \IPS\Application::appIsEnabled( 'rules' ) )
		{
			$event = \IPS\rules\Event::load( 'form', 'Forms', 'form_submitted_' . $this->container()->_id );
			call_user_func_array( array( $event, 'trigger' ), $rulesArgs );
		}
		
	}    
	
	/**
	 * Can view?
	 *
	 * @param	\IPS\Member|NULL	$member	The member to check for or NULL for the currently logged in member
	 * @return	bool
	 */
	public function canView( $member=NULL )
	{
	    /* Check news entry has category */
		if ( !parent::canView( $member ) )
		{
			return FALSE;
		}
        
        $member = $member ?: \IPS\Member::loggedIn();
        
        /* Group can view? */
        if( $member->group['g_fs_view_logs'] )
        {
            return true;
        }

		return false;
	}
    
	/**
	 * Can delete?
	 *
	 * @param	\IPS\Member|NULL	$member	The member to check for (NULL for currently logged in member)
	 * @return	bool
	 */
	public function canDelete( $member=NULL )
	{
		$member = $member ?: \IPS\Member::loggedIn();

        /* Can delete log? */
        if( $member->group['g_fs_moderate_logs'] )
        {
            return TRUE;   
        }
		
		return FALSE;
	}   
    
	/**
	 * Columns needed to query for search result / stream view
	 *
	 * @return	array
	 */
	public static function basicDataColumns()
	{
	    /* Remove 'title' */
		$return = array( static::$databasePrefix . static::$databaseColumnId, static::$databasePrefix . static::$databaseColumnMap['author'] );

		return $return;
	}             
	
	/**
	 * Send notifications
	 *
	 * @return	void
	 */
	public function sendNotifications()
	{
	}
    
	/**
	 * Return the filters that are available for selecting table rows
	 *
	 * @return	array
	 */
	public static function getTableFilters()
	{
		return array();
	}    

 	/**
	 * @brief	Cached URL
	 */
	protected $_url	= NULL;
	
	/**
	 * @brief	URL Base
	 */
	public static $urlBase = 'app=form&module=forms&controller=logs&id=';
	
	/**
	 * @brief	URL Base
	 */
	public static $urlTemplate = NULL;
	
	/**
	 * @brief	SEO Title Column
	 */
	public static $seoTitleColumn = NULL; 

	/**
	 * Get URL
	 *
	 * @param	string|NULL		$action		Action
	 * @return	\IPS\Http\Url
	 */
	public function url( $action=NULL, $forceURL=NULL )
	{
		$_key        = md5( $action );
        $redirectURL = \IPS\Http\Url::internal( "app=form", 'front', 'form' ); /* Default to homepage */
        
        /* Force different direction if form */
        if( \IPS\Request::i()->form_submitted AND !$forceURL )
        {
            /* Get form options */
            $options = json_decode( $this->container()->options, TRUE );  
            
            /* Redirect to message page */
            if( isset( $options['confirm_type'] ) AND $options['confirm_type'] == 1 )
            {
                $redirectURL = \IPS\Http\Url::internal( "app=form&controller=index&do=confirmation&id=".$this->id, "front", "form_confirmation" );   
            }             
            
            /* Redirect to entered url */
            if( isset( $options['confirm_type'] ) AND $options['confirm_type'] == 2 )
            {
                $redirectURL = \IPS\Http\Url::external( $options['confirm_data'] );   
            }    

            /* Just double check we good to go */
            if( $redirectURL )
            {
                return $redirectURL;       
            }
        }
        
        /* Normal url request */
		if( !isset( $this->_url[ $_key ] ) )
		{
			$this->_url[ $_key ] = \IPS\Http\Url::internal( "app=form&controller=logs&id=".$this->id );
		
			if ( $action )
			{
				$this->_url[ $_key ] = $this->_url[ $_key ]->setQueryString( 'do', $action );
			}
		}
	
		return $this->_url[ $_key ];
	}
    
	/**
	 * Confirmation Page
	 *
	 * @return	void
	 */
	public static function confirmation( $logID=NULL )
	{
	    /* Try to get log */
		try
		{
			$obj = static::load( $logID );
		}
		catch( \OutOfRangeException $e ) 
        {
            \IPS\Output::i()->error( 'no_log_id', '', 403, '' );     
		}	   
       
        /* Why the hell are you doing? */
        if( $obj->author()->member_id != \IPS\Member::loggedIn()->member_id )
        {
            \IPS\Output::i()->error( 'no_log_id', '', 403, '' );    
        }
        
        /* Get topic settings and check if can redirect there? */
        $topicSettings = json_decode( $obj->container()->topic_settings );
        
        if( $topicSettings->redirect AND $obj->topic() )
        {
            \IPS\Output::i()->redirect( $obj->topic()->url() );   
        }
        
        /* Get form options */
        $options = json_decode( $obj->container()->options, TRUE ); 
        
		/* Get tag values */
		$tags = \IPS\form\Form::returnTagValues( $obj->container(), $obj );
                
        if( isset( $options['confirm_data'] ) AND $options['confirm_data'] )
        {
            /* Swap out our tags */
    		foreach( $tags as $key => $value )
    		{
    		    \IPS\Member::loggedIn()->language()->parseOutputForDisplay( $value );
                $options['confirm_data'] = preg_replace( $key, $value, $options['confirm_data'] );
    		}            
        }
        else
        {
            /* Just show default message */
            return NULL;
        }        

        /* Return a message */
        return $options['confirm_data'];  
	}  
    
	/**
	 * Get Log Topic
	 * @return	\IPS\forums\Topic|NULL
	 */
	public function topic()
	{
		if ( \IPS\Application::appIsEnabled('forums') and $this->topic )
		{
			try
			{
				return \IPS\forums\Topic::load( $this->topic );
			}
			catch ( \OutOfRangeException $e )
			{
				return NULL;
			}
		}
		
		return NULL;
	}         

	/**
	 * Delete
	 *
	 * @return	void
	 */
	public function delete()
	{
		parent::delete();
	}
}