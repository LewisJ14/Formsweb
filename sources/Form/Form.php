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
 * Form Node
 */
class _Form extends \IPS\Node\Model implements \IPS\Node\Permissions
{
	protected static $multitons;    
    public static $databaseColumnId = 'form_id';
	protected static $databaseIdFields = array('form_id');    
	public static $databaseTable = 'form_forms';
	public static $databasePrefix = '';
	public static $databaseColumnOrder = 'position';
	public static $databaseColumnParent = 'parent_id';
	public static $nodeTitle = 'module__form_form';
	public static $subnodeClass = 'IPS\form\Form\Field';       
			
	/**
	 * @brief	[Node] ACP Restrictions
	 * @code
	 	array(
	 		'app'		=> 'core',				// The application key which holds the restrictrions
	 		'module'	=> 'foo',				// The module key which holds the restrictions
	 		'map'		=> array(				// [Optional] The key for each restriction - can alternatively use "prefix"
	 			'add'			=> 'foo_add',
	 			'edit'			=> 'foo_edit',
	 			'permissions'	=> 'foo_perms',
	 			'delete'		=> 'foo_delete'
	 		),
	 		'all'		=> 'foo_manage',		// [Optional] The key to use for any restriction not provided in the map (only needed if not providing all 4)
	 		'prefix'	=> 'foo_',				// [Optional] Rather than specifying each  key in the map, you can specify a prefix, and it will automatically look for restrictions with the key "[prefix]_add/edit/permissions/delete"
	 * @encode
	 */
	protected static $restrictions = array(
		'app'		=> 'form',
		'module'	=> 'form',
		'all'       => 'forms_manage'
	);
	
	/**
	 * @brief	[Node] App for permission index
	 */
	public static $permApp = 'form';
	
	/**
	 * @brief	[Node] Type for permission index
	 */
	public static $permType = 'form';
	
	/**
	 * @brief	The map of permission columns
	 */
	public static $permissionMap = array(
		'view' 			=> 'view',
		'add'			=> 2,
		'read'			=> 3
	);  

	/**
	 * @brief	[Node] Title prefix.  If specified, will look for a language key with "{$key}_title" as the key
	 */
	public static $titleLangPrefix = 'form_form_';
	
	/**
	 * @brief	[Node] Moderator Permission
	 */
	public static $modPerm = 'form_forms';
	
	/**
	 * @brief	[Node] Prefix string that is automatically prepended to permission matrix language strings
	 */
	public static $permissionLangPrefix = 'perm_form_';
    
	/**
	 * [Node] Get whether or not this node is enabled
	 *
	 * @note	Return value NULL indicates the node cannot be enabled/disabled
	 * @return	bool|null
	 */
	protected function get__enabled()
	{
		return $this->open;
	} 
    
	/**
	 * Get form options
	 */
	protected function get__options()
	{
	    $options = NULL;
       
        /* Decode form options */
	    if( $this->options )
        {
            $options = json_decode( $this->options );
        }

		return $options;
	}     
	
	/**
	 * [Node] Get Node Description
	 *
	 * @return	string|null
	 */
	protected function get__description()
	{
        return \IPS\Member::loggedIn()->language()->addToStack( static::$titleLangPrefix . $this->form_id . '_desc' );
	}   
    
  	/**
	 * [Node] Get buttons to display in tree
	 * Example code explains return value
	 *
	 * @code
	 	array(
	 		array(
	 			'icon'	=>	array(
	 				'icon.png'			// Path to icon
	 				'core'				// Application icon belongs to
	 			),
	 			'title'	=> 'foo',		// Language key to use for button's title parameter
	 			'link'	=> \IPS\Http\Url::internal( 'app=foo...' )	// URI to link to
	 			'class'	=> 'modalLink'	// CSS Class to use on link (Optional)
	 		),
	 		...							// Additional buttons
	 	);
	 * @endcode
	 * @param	string	$url	Base URL
	 * @param	bool	$subnode	Is this a subnode?
	 * @return	array
	 */
	public function getButtons( $url, $subnode=FALSE )
	{
		/* Get normal buttons */
		$buttons	= parent::getButtons( $url );
        
		$buttons['members']	= array(
			'icon'	=> 'eye',
			'title'	=> 'form_preview_form',
			'link'	=> $this->url(),
		);     
                    
		return $buttons;
	}    
    
	/**
	 * Load and check permissions
	 *
	 * @param	mixed	$id		ID
	 * @param	string	$perm	Permission Key
	 * @return	static
	 * @throws	\OutOfRangeException
	 */
	public static function _loadAndCheckPerms( $id, $perm='view' )
	{
		$node = parent::loadAndCheckPerms( $id, $perm );
		
		if ( !$node->open and !\IPS\Member::loggedIn()->isAdmin() )
		{
			throw new \OutOfRangeException;
		}
		
		return $node;
	}
    
	/**
	 * [Node] Get Form Rules
	 *
	 * @return	string|null
	 */
	public function formTranslate( $field='', $swapTags=FALSE )
	{
        /* Get a translatable field */
		try
		{
		    /* Lets try this for now */
		    $translate = preg_replace('/<p[^>]*>(.*)<\/p[^>]*>/i', '$1', \IPS\Member::loggedIn()->language()->get( 'form_form_' . $this->form_id . '_'.$field ) );

            /* Swap out tags? */
            if( $swapTags )
            {
                $tags = self::returnTagValues( $this ); 
                
                /* Swap out our tags */
        		foreach( $tags as $key => $value )
        		{
                    $translate = preg_replace( $key, $value, $translate );            
        		} 
            }
            
			return $translate;
		}
		catch( \UnderflowException $e )
		{
			return '';
		}
	}    
	
	/**
	 * [Node] Add/Edit Form
	 *
	 * @param	\IPS\Helpers\Form	$form	The form
	 * @return	void
	 */
	public function form( &$form )
	{		
        /* Decode json fields */        
        $this->options        = json_decode( $this->options ); 
        $this->pm_settings    = json_decode( $this->pm_settings ); 
        $this->email_settings = json_decode( $this->email_settings ); 
        $this->topic_settings = json_decode( $this->topic_settings ); 
        
		/* Get field options */         
		$customFields = \IPS\form\Form\Field::roots( NULL, NULL, array( 'field_form_id=?', $this->form_id ) );
        $fieldOptions = array();
        
		foreach ( $customFields as $field )
		{
			$fieldOptions[ $field->_id ] = $field->_title;
		}                

        /* Display form */       
		$form->addTab( 'form_settings_tab' );
		$form->addHeader( 'form_settings_header' );
		$form->add( new \IPS\Helpers\Form\Translatable( 'form_name', NULL, TRUE, array( 'app' => 'form', 'key' => ( $this->form_id ? "form_form_{$this->form_id}" : NULL ) ) ) );
		$form->add( new \IPS\Helpers\Form\Translatable( 'form_description', NULL, FALSE, array(
			'app'		=> 'form',
			'key'		=> ( $this->form_id ? "form_form_{$this->form_id}_desc" : NULL ),
			'editor'	=> array(
				'app'			=> 'form',
				'key'			=> 'Forms',
				'autoSaveKey'	=> ( $this->form_id ? "form-form-{$this->form_id}-desc" : "form-new-form-desc" ),
				'attachIds'		=> $this->form_id ? array( $this->form_id, NULL, 'description' ) : NULL, 'minimize' => 'form_description_placeholder'
			)
		), NULL, NULL, NULL, 'description' ) );
        
  		$form->add( new \IPS\Helpers\Form\Translatable( 'form_rules', NULL, FALSE, array(
			'app'		=> 'form',
			'key'		=> ( $this->form_id ? "form_form_{$this->form_id}_rules" : NULL ),
			'editor'	=> array(
				'app'			=> 'form',
				'key'			=> 'Forms',
				'autoSaveKey'	=> ( $this->form_id ? "form-form-{$this->form_id}-rules" : "form-new-form-rules" ),
				'attachIds'		=> $this->form_id ? array( $this->form_id, NULL, 'form_rules' ) : NULL, 'minimize' => 'form_rules_placeholder'
			)
		), NULL, NULL, NULL, 'form_rules' ) );         
        
		$form->add( new \IPS\Helpers\Form\YesNo( 'form_enable_rss', ( isset( $this->options->enable_rss ) AND $this->options->enable_rss ) ? TRUE : FALSE, FALSE, array() ) );
		$form->add( new \IPS\Helpers\Form\YesNo( 'form_disable_captcha', ( isset( $this->options->disable_captcha ) AND $this->options->disable_captcha ) ? TRUE : FALSE, FALSE, array() ) );
        
		$form->addHeader( 'form_logs_header' );
		$form->add( new \IPS\Helpers\Form\Number( 'form_submit_wait', ( isset( $this->options->submit_wait ) AND $this->options->submit_wait ) ? (int) $this->options->submit_wait : 0, FALSE, array( 'unlimited' => 0, 'unlimitedLang' => 'form_use_group_perms' ), NULL, NULL, array(
			'preUnlimited'	=> \IPS\Member::loggedIn()->language()->addToStack( "form_submit_wait_seconds" ),
		), 'submit_wait' ) ); 
 		$form->add( new \IPS\Helpers\Form\Translatable( 'form_log_message', NULL, FALSE, array(
			'app'		=> 'form',
			'key'		=> ( $this->form_id ? "form_form_{$this->form_id}_log" : NULL ),
			'editor'	=> array(
				'app'			=> 'form',
				'key'			=> 'Forms',
				'autoSaveKey'	=> ( $this->form_id ? "form-form-{$this->form_id}-log" : "form-new-form-log" ),
				'attachIds'		=> $this->form_id ? array( $this->form_id, NULL, 'log_message' ) : NULL, 'minimize' => 'form_log_message_placeholder'
			)
		), NULL, NULL, NULL, 'log_message' ) );  

       	$form->addHeader( 'form_confirmation_header' );    
        $form->add( new \IPS\Helpers\Form\Select( 'form_confirm_type', ( isset( $this->options->confirm_type ) AND $this->options->confirm_type ) ? $this->options->confirm_type : 1, FALSE, array( 'options' => array( 1 => 'form_message', 2 => 'form_redirect_url' ), 'toggles' => array( 1 => array( 'confirm_data_message' ), 2 => array( 'confirm_data_url' ) ) ) ) );     
        $form->add( new \IPS\Helpers\Form\Url( 'form_confirm_data_url', ( isset( $this->options->confirm_type ) AND $this->options->confirm_type == 2 ) ? $this->options->confirm_data : '', FALSE, array(), NULL, NULL, NULL, 'confirm_data_url' ) );
        $form->add( new \IPS\Helpers\Form\TextArea( 'form_confirm_data_message', ( isset( $this->options->confirm_type ) AND isset( $this->options->confirm_data ) AND $this->options->confirm_type == 1 ) ? $this->options->confirm_data : '', FALSE, array(), NULL, NULL, NULL, 'confirm_data_message' ) );
        $form->add( new \IPS\Helpers\Form\Select( 'form_confirm_email', ( isset( $this->options->confirm_email ) AND $this->options->confirm_email ) ? (int) $this->options->confirm_email : 0, FALSE, array( 'options' => $fieldOptions, 'unlimited' => 0, 'unlimitedLang' => 'form_confirm_email_unlimitedLang' ) ) );     
        $form->add( new \IPS\Helpers\Form\YesNo( 'form_confirm_pm', ( isset( $this->options->confirm_pm ) AND $this->options->confirm_pm ) ? (int) $this->options->confirm_pm : 0, FALSE, array() ) );     

        $form->addTab( 'form_pm_tab' );
        $form->addHeader( 'form_pm_header' );
		$form->add( new \IPS\Helpers\Form\YesNo( 'form_pm_enable', ( isset( $this->pm_settings->enable ) AND $this->pm_settings->enable ) ? TRUE : FALSE, FALSE, array( 'togglesOn' => array( 'pm_send_own', 'pm_receiver', 'pm_subject', 'pm_message' ) ) ) );  
		$form->add( new \IPS\Helpers\Form\YesNo( 'form_pm_send_own', ( isset( $this->pm_settings->send_own ) AND $this->pm_settings->send_own ) ? TRUE : FALSE, FALSE, array( 'togglesOff' => array( 'pm_sender' ) ), NULL, NULL, NULL, 'pm_send_own' ) );  	    
        $form->add( new \IPS\Helpers\Form\Member( 'form_pm_sender', ( isset( $this->pm_settings->sender ) AND $this->pm_settings->sender ) ? \IPS\Member::load( $this->pm_settings->sender ) : \IPS\Member::loggedIn(), FALSE, array(), NULL, NULL, NULL, 'pm_sender' ) );
        $form->add( new \IPS\Helpers\Form\Member( 'form_pm_receiver', ( isset( $this->pm_settings->receiver ) AND $this->pm_settings->receiver ) ? array_map( array( 'IPS\Member', 'load' ), explode( ',', $this->pm_settings->receiver ) ) : \IPS\Member::loggedIn(), FALSE, array( 'multiple' => 30 ), NULL, NULL, NULL, 'pm_receiver' ) );
        $form->add( new \IPS\Helpers\Form\Translatable( 'form_pm_subject', NULL, FALSE, array( 'app' => 'form', 'key' => ( $this->form_id ? "form_form_{$this->form_id}_pm_subject" : NULL ) ), NULL, NULL, NULL, 'pm_subject' ) );
 		$form->add( new \IPS\Helpers\Form\Translatable( 'form_pm_message', $this->form_id ? $this->pm_message : '', FALSE, array(
			'app'		=> 'form',
			'key'		=> ( $this->form_id ? "form_form_{$this->form_id}_pm_message" : NULL ),
			'editor'	=> array(
				'app'			=> 'form',
				'key'			=> 'Forms',
				'autoSaveKey'	=> ( $this->form_id ? "form-form-{$this->form_id}-pmmessage" : "form-new-form-pmmessage" ),
				'attachIds'		=> $this->form_id ? array( $this->form_id, NULL, 'pm_message' ) : NULL, 'minimize' => 'form_pm_message_placeholder'
			)
		), NULL, NULL, NULL, 'pm_message' ) );

        $form->addTab( 'form_email_tab' );
        $form->addHeader( 'form_email_header' );
		$form->add( new \IPS\Helpers\Form\YesNo( 'form_email_enable', ( isset( $this->email_settings->enable ) AND $this->email_settings->enable ) ? TRUE : FALSE, FALSE, array( 'togglesOn' => array( 'email_send_own', 'email_receiver', 'email_subject', 'email_message' ) ) ) );  
		$form->add( new \IPS\Helpers\Form\YesNo( 'form_email_send_own', ( isset( $this->email_settings->send_own ) AND $this->email_settings->send_own ) ? TRUE : FALSE, FALSE, array( 'togglesOff' => array( 'email_sender' ) ), NULL, NULL, NULL, 'email_send_own' ) );  	    
        $form->add( new \IPS\Helpers\Form\Member( 'form_email_sender', ( isset( $this->email_settings->sender ) AND $this->email_settings->sender ) ? \IPS\Member::load( $this->email_settings->sender ) : \IPS\Member::loggedIn(), FALSE, array(), NULL, NULL, NULL, 'email_sender' ) );
        $form->add( new \IPS\Helpers\Form\Member( 'form_email_receiver', ( isset( $this->email_settings->receiver ) AND $this->email_settings->receiver ) ? array_map( array( 'IPS\Member', 'load' ), explode( ',', $this->email_settings->receiver ) ) : \IPS\Member::loggedIn(), FALSE, array( 'multiple' => 5 ), NULL, NULL, NULL, 'email_receiver' ) );
        $form->add( new \IPS\Helpers\Form\Translatable( 'form_email_subject', NULL, FALSE, array( 'app' => 'form', 'key' => ( $this->form_id ? "form_form_{$this->form_id}_email_subject" : NULL ) ), NULL, NULL, NULL, 'email_subject' ) );
  		$form->add( new \IPS\Helpers\Form\Translatable( 'form_email_message', NULL, FALSE, array(
			'app'		=> 'form',
			'key'		=> ( $this->form_id ? "form_form_{$this->form_id}_email_message" : NULL ),
			'editor'	=> array(
				'app'			=> 'form',
				'key'			=> 'Forms',
				'autoSaveKey'	=> ( $this->form_id ? "form-form-{$this->form_id}-emailmessage" : "form-new-form-emailmessage" ),
				'attachIds'		=> $this->form_id ? array( $this->form_id, NULL, 'email_message' ) : NULL, 'minimize' => 'form_email_message_placeholder'
			)
		), NULL, NULL, NULL, 'email_message' ) );

		if ( \IPS\Application::appIsEnabled( 'forums' ) )
		{
			$form->addTab( 'form_topic_tab' );
            $form->addHeader( 'form_topic_header' );
			$form->add( new \IPS\Helpers\Form\YesNo( 'form_topic_enable', ( isset( $this->topic_settings->enable ) AND $this->topic_settings->enable ) ? TRUE : FALSE, FALSE, array( 'togglesOn' => array( 'topic_create_own', 'topic_forum', 'topic_title', 'topic_post', 'topic_redirect' ) ) ) );      
		    $form->add( new \IPS\Helpers\Form\YesNo( 'form_topic_create_own', ( isset( $this->topic_settings->create_own ) AND $this->topic_settings->create_own ) ? TRUE : FALSE, FALSE, array( 'togglesOff' => array( 'topic_author' ) ), NULL, NULL, NULL, 'topic_create_own' ) );  	        
		    $form->add( new \IPS\Helpers\Form\YesNo( 'form_topic_redirect', ( isset( $this->topic_settings->redirect ) AND $this->topic_settings->redirect ) ? TRUE : FALSE, FALSE, array(), NULL, NULL, NULL, 'topic_redirect' ) );  	              
            $form->add( new \IPS\Helpers\Form\Member( 'form_topic_author', ( isset( $this->topic_settings->author ) AND $this->topic_settings->author ) ? \IPS\Member::load( $this->topic_settings->author ) : \IPS\Member::loggedIn(), FALSE, array(), NULL, NULL, NULL, 'topic_author' ) );
 			$form->add( new \IPS\Helpers\Form\Node( 'form_topic_forum', ( isset( $this->topic_settings->forum ) AND $this->topic_settings->forum ) ? $this->topic_settings->forum : NULL, FALSE, array( 'class' => 'IPS\forums\Forum', 'permissionCheck' => function ( $forum ) { return $forum->sub_can_post and !$forum->redirect_url; } ), NULL, NULL, NULL, 'topic_forum' ) );
            $form->add( new \IPS\Helpers\Form\Translatable( 'form_topic_title', NULL, FALSE, array( 'app' => 'form', 'key' => ( $this->form_id ? "form_form_{$this->form_id}_topic_title" : NULL ) ), NULL, NULL, NULL, 'topic_title' ) );
      		$form->add( new \IPS\Helpers\Form\Translatable( 'form_topic_post', NULL, FALSE, array(
    			'app'		=> 'form',
    			'key'		=> ( $this->form_id ? "form_form_{$this->form_id}_topic_post" : NULL ),
    			'editor'	=> array(
    				'app'			=> 'form',
    				'key'			=> 'Forms',
    				'autoSaveKey'	=> ( $this->form_id ? "form-form-{$this->form_id}-topicpost" : "form-new-form-topicpost" ),
    				'attachIds'		=> $this->form_id ? array( $this->form_id, NULL, 'topic_post' ) : NULL, 'minimize' => 'form_topic_post_placeholder'
    			)
    		), NULL, NULL, NULL, 'topic_post' ) );          
		}
	}
    
	/**
	 * [Node] Format form values from add/edit form for save
	 *
	 * @param	array	$values	Values from the form
	 * @return	array
	 */
	public function formatFormValues( $values )
	{
	    /* Remove form prefix */
 		foreach( $values as $k => $v )
		{
			if( mb_substr( $k, 0, 5 ) === 'form_' )
			{
				unset( $values[ $k ] );
				$values[ mb_substr( $k, 5 ) ] = $v;
			}
		}	   
       
	    /* Claim attachments */
		if ( !$this->form_id )
		{
			$this->save();
			\IPS\File::claimAttachments( 'form-new-form-desc', $this->form_id, NULL, 'description', TRUE );            
			\IPS\File::claimAttachments( 'form-new-form-rules', $this->form_id, NULL, 'form_rules', TRUE );
			\IPS\File::claimAttachments( 'form-new-form-log', $this->form_id, NULL, 'log_message', TRUE );
			\IPS\File::claimAttachments( 'form-new-form-pmmessage', $this->form_id, NULL, 'pm_message', TRUE );
			\IPS\File::claimAttachments( 'form-new-form-emailmessage', $this->form_id, NULL, 'email_message', TRUE );
			\IPS\File::claimAttachments( 'form-new-form-topicpost', $this->form_id, NULL, 'topic_post', TRUE );
		}
        
        /* Save custom lang */        
		foreach ( array( 'name'          => "form_form_{$this->form_id}",
                         'description'   => "form_form_{$this->form_id}_desc", 
                         'rules'         => "form_form_{$this->form_id}_rules",
                         'log_message'   => "form_form_{$this->form_id}_log",
                         'pm_subject'    => "form_form_{$this->form_id}_pm_subject",                          
                         'pm_message'    => "form_form_{$this->form_id}_pm_message", 
                         'email_subject' => "form_form_{$this->form_id}_email_subject",
                         'email_message' => "form_form_{$this->form_id}_email_message",
                         'topic_title'   => "form_form_{$this->form_id}_topic_title",
                         'topic_post'    => "form_form_{$this->form_id}_topic_post" ) as $fieldKey => $langKey )
		{
			if ( array_key_exists( $fieldKey, $values ) )
			{
				\IPS\Lang::saveCustom( 'form', $langKey, $values[ $fieldKey ] );
				
				if ( $fieldKey === 'name' )
				{
					$this->name_seo = \IPS\Http\Url::seoTitle( $values[ $fieldKey ][ \IPS\Lang::defaultLanguage() ] );
				}
				
				unset( $values[ $fieldKey ] );
			}
		}  
        
        /* Check topic forum */
		if( isset( $values['topic_enable'] ) AND $values['topic_enable'] AND isset( $values['topic_forum'] ) )
		{
			$values['topic_forum'] = $values['topic_forum'] ? intval( $values['topic_forum']->id ) : 0;
		}    
               
        /* Check member settings */
		foreach ( array( 'pm_sender', 'pm_receiver', 'email_sender', 'email_receiver', 'topic_author' ) as $memberField )
        {
            /* Multiple members or single? */
            if( is_array( $values[ $memberField ] ) AND count( $values[ $memberField ] ) )
            {
                foreach( $values[ $memberField ] as $member )
                {
                    $memberList[ $memberField ][ $member->member_id ] = $member->member_id;
                }
                
                $values[ $memberField ] = implode( ",", $memberList[ $memberField ] );
            }
            else
            {
                $values[ $memberField ] = $values[ $memberField ] ? intval( $values[ $memberField ]->member_id ) : 0;    
            }
        }
        
        /* Setup confirm message/url switch */
        $values['confirm_data'] = ( $values['confirm_type'] == 1 ) ? $values['confirm_data_message'] : (string) $values['confirm_data_url'];
        
        /* Setup options settings */
		foreach ( array( 'enable_rss', 'disable_captcha', 'confirm_type', 'confirm_data', 'confirm_email', 'confirm_pm', 'submit_wait' ) as $optionField )
        {            
            if( !in_array( $optionField, array( 'confirm_data', 'confirm_email' ) ) )
            {
                $values['options'][ $optionField ] = intval( $values[ $optionField ] );    
            }
            else
            {
                $values['options'][ $optionField ] = $values[ $optionField ];    
            }

            unset( $values[ $optionField ] );
            unset( $values['confirm_data_message'], $values['confirm_data_url'] );
        }      

        /* Setup pm settings */
		foreach ( array( 'pm_enable', 'pm_send_own', 'pm_sender', 'pm_receiver' ) as $pmField )
        {
            $values['pm_settings'][ preg_replace( "/^pm_/", "", $pmField ) ] = $values[ $pmField ];
            unset( $values[ $pmField ] );
        }     
        
        /* Setup email settings */
		foreach ( array( 'email_enable', 'email_send_own', 'email_sender', 'email_receiver' ) as $emailField )
        {
            $values['email_settings'][ preg_replace( "/^email_/", "", $emailField ) ] = $values[ $emailField ];
            unset( $values[ $emailField ] );
        }          
        
        /* Setup topic settings */
		foreach ( array( 'topic_enable', 'topic_create_own', 'topic_redirect', 'topic_author', 'topic_forum' ) as $topicField )
        {           
            $values['topic_settings'][ preg_replace( "/^topic_/", "", $topicField ) ] = $values[ $topicField ];
            unset( $values[ $topicField ] );
        }      
        
        /* Encode json fields */
        $values['options']        = json_encode( $values['options'] ); 
        $values['pm_settings']    = json_encode( $values['pm_settings'] );             
        $values['email_settings'] = json_encode( $values['email_settings'] ); 
        $values['topic_settings'] = json_encode( $values['topic_settings'] );         

		/* Send to parent */
		return $values;
	}

	/**
	 * @brief	Cached URL
	 */
	protected $_url	= NULL;
	
	/**
	 * @brief	URL Base
	 */
	public static $urlBase = 'app=form&module=forms&controller=index&do=viewform&id=';
	
	/**
	 * @brief	URL Base
	 */
	public static $urlTemplate = 'forms_form';
	
	/**
	 * @brief	SEO Title Column
	 */
	public static $seoTitleColumn = 'name_seo';
    
	/**
	 * Get custom fields
	 *
	 * @return	array
	 */
	public function customFields()
	{
		return \IPS\form\Form\Field::roots( NULL, NULL, $this->form_id );
	}    

	/**
	 * Delete Record
	 *
	 * @return	void
	 */
	public function delete()
	{
		\IPS\File::unclaimAttachments( 'form_forms', $this->form_id );
		parent::delete();
        
		foreach ( array( 'form_name'     => "form_form_{$this->form_id}",
                         'description'   => "form_form_{$this->form_id}_desc", 
                         'form_rules'    => "form_form_{$this->form_id}_rules",
                         'log_message'   => "form_form_{$this->form_id}_log",
                         'pm_message'    => "form_form_{$this->form_id}_pm_message", 
                         'email_message' => "form_form_{$this->form_id}_email_message",
                         'topic_post'    => "form_form_{$this->form_id}_topic_post" ) as $fieldKey => $langKey )
		{        
			\IPS\Lang::deleteCustom( 'form', $langKey );
		}
	} 
    
	/**
	 * Retrieve the tags that are used in alerts
	 *
	 * @return	array 	An array of tags in foramt of 'tag' => 'explanation text'
	 */
	public static function getTags()
	{
		/* Setup tags */
		$tags = array(
			'{member_name}'   => \IPS\Member::loggedIn()->language()->addToStack('formtag__member_name'),
			'{member_id}'	  => \IPS\Member::loggedIn()->language()->addToStack('formtag__member_id'),
			'{member_email}'  => \IPS\Member::loggedIn()->language()->addToStack('formtag__member_email'),
			'{member_ip}'	  => \IPS\Member::loggedIn()->language()->addToStack('formtag__member_ip'),
			'{board_name}'    => \IPS\Member::loggedIn()->language()->addToStack('formtag__board_name'),
			'{board_url}'	  => \IPS\Member::loggedIn()->language()->addToStack('formtag__board_url'),
			'{form_id}'	      => \IPS\Member::loggedIn()->language()->addToStack('formtag__form_id'),
			'{form_name}'	  => \IPS\Member::loggedIn()->language()->addToStack('formtag__form_name'),  
			'{form_url}'      => \IPS\Member::loggedIn()->language()->addToStack('formtag__form_url'),
			'{log_id}'	      => \IPS\Member::loggedIn()->language()->addToStack('formtag__log_id'),
			'{log_url}'	      => \IPS\Member::loggedIn()->language()->addToStack('formtag__log_url'),
			'{logs_url}'	  => \IPS\Member::loggedIn()->language()->addToStack('formtag__logs_url'),
            '{date}'          => \IPS\Member::loggedIn()->language()->addToStack('formtag__date'),
            '{time}'          => \IPS\Member::loggedIn()->language()->addToStack('formtag__time'),
			'{field_name_x}'  => \IPS\Member::loggedIn()->language()->addToStack('formtag__field_name_x'),
			'{field_value_x}' => \IPS\Member::loggedIn()->language()->addToStack('formtag__field_value_x'),  
			'{field_list}'	  => \IPS\Member::loggedIn()->language()->addToStack('formtag__field_list'),                                             
		);

		return $tags;
	}     
    
	/**
	 * Return tag values
	 *
	 * @param	NULL|\IPS\Member	$member	Member object
     * @param	NULL|\IPS\Topic	$topic	Topic object
	 * @return	array
	 */
	public static function returnTagValues( $container=NULL, $log=NULL, $fieldValues=array() )
	{     
	    /* Member tags */
		$tags['/{member_name}/']  = \IPS\Member::loggedIn()->name;
		$tags['/{member_id}/']	  = \IPS\Member::loggedIn()->member_id;
		$tags['/{member_email}/'] = \IPS\Member::loggedIn()->email;
        $tags['/{member_ip}/']	  = \IPS\Member::loggedIn()->ip_address;
       
        /* Board tags */
		$tags['/{board_name}/']	= \IPS\Settings::i()->board_name;
		$tags['/{board_url}/']	= \IPS\Http\Url::internal( 'app=forums', 'front', 'form' );
        $tags['/{date}/']	    = \IPS\DateTime::create()->localeDate();
        $tags['/{time}/']	    = \IPS\DateTime::create()->localeTime();

        /* Form tags */
 		$tags['/{form_id}/']   = ( $container ) ? $container->form_id : '';
		$tags['/{form_name}/'] = ( $container ) ? $container->_title : '';
		$tags['/{form_url}/']  = ( $container ) ? "<a href='".$container->url()."'>".$container->_title."</a>" : '';

        /* Log tags */
 		$tags['/{log_id}/']	  = ( $log ) ? $log->id : '';
		$tags['/{log_url}/']  = ( $log ) ? $log->url( NULL, TRUE ) : '';   
        $tags['/{logs_url}/'] = \IPS\Http\Url::internal( 'app=form&module=forms&controller=logs', 'front', 'form_logs' );
        
        $fieldList = '';

		/* Get forms custom fields */ 
        if( $container )
        {
     		$customFields = \IPS\form\Form\Field::roots( NULL, NULL, array( 'field_form_id=?', $container->form_id ) );
            
            if( count( $customFields ) )
            {
        		foreach( $customFields as $field )
        		{  
        		    /* Parse lang for now, look for better way */
        		    $_name = $field->_title;
                    \IPS\Member::loggedIn()->language()->parseOutputForDisplay( $_name ); 
                    
                    /* Parse field value */
                    if( isset( $fieldValues[ 'form_field_' . $field->id ] ) AND $fieldValues[ 'form_field_' . $field->id ] )
                    {
                        $_value = $fieldValues[ 'form_field_' . $field->id ];
                        
                        /* Apply this certain display fixes */
            			if( $field->type == 'Date' )
            			{
            				 $_display = $field->displayValue( $_value->getTimestamp() );
            			}
            			else if( $field->type == 'Member' )
            			{
            				 $_display = $field->displayValue( $field->buildHelper()->stringValue( $_value ) );
            			} 
            			else if( $field->type == 'Rating' )
            			{
            				 $_display = $_value;
            			}
            			else if( $field->type == 'YesNo' OR $field->type == 'Checkbox' )
            			{
            			     $_display = \IPS\Member::loggedIn()->language()->get('yes');
            			}                        
             			else if( $field->type == 'Select' OR $field->type == 'CheckboxSet' )
            			{
            				 $_display = $field->displayValue( $field->buildHelper()->stringValue( $_value ) );
            			}                        
            			else if( $field->type == 'Poll' )
            			{
            				 $_display = NULL;
            			}  
            			else if( $field->type == 'Email' )
            			{
            				 $_display = $_value;
            			}                            
            			else if( $field->type == 'Address' )
            			{
            				  $_display = $field->displayValue( $field->buildHelper()->stringValue( $_value ) );
            			}                                                                                            
                        else
                        {
                            $_display = $field->displayValue( $_value );     
                        }     
                    }
                    else
                    {
                        if( $field->type == 'YesNo' OR $field->type == 'Checkbox' )
            			{
            			     $_display = \IPS\Member::loggedIn()->language()->get('no');
            			} 
                        else
                        {
                            $_display = '';                            
                        }                      
                    }

                    /* Setup field tags */
                    $tags['/{field_name_'.$field->id.'}/']  = $_name;
                    $tags['/{field_value_'.$field->id.'}/'] = $_display; 
 
                    /* Setup field list */
                    $fieldList .= "<strong>{$_name}:</strong> ";
                    $fieldList .= $_display."<br />";  
                }                
            }
        }    
        
        /* Setup field list tag */
		$tags['/{field_list}/'] = ( isset( $fieldList ) AND $fieldList ) ? $fieldList : ''; 
                
		return $tags;
	}    
     
}