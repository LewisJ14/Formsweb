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

class _Alerts
{
	/**
	 * Busy
	 */   
	public function busy()
	{              
	}     
    
	/**
	 * Create topic
	 */      
	public static function _createTopic( $settings, $member=NULL, $form=NULL, $tags=NULL )
	{
        /* Forums enabled? */
		if ( ! \IPS\Application::appIsEnabled( 'forums' ) )
		{
			return;
		} 
        
        /* Topic alert enabled */
        if( !$settings OR !$settings->enable )
        {
            return;   
        }
        
        /* Check who is topic author */
        if( $settings->create_own AND $member->member_id )
        {
            $authorID = $member->member_id;
        }
        else if( $settings->create_own AND !$member->member_id )
        {
            $authorID = (int) \IPS\Settings::i()->fm_guest_override;
        }        
        else
        {
            $authorID = $settings->author;    
        }   
        
        /* Set topic author */
		try
		{
			$topicAuthor = \IPS\Member::load( $authorID );
		}
		catch( \OutOfRangeException $ex )
		{
            return; 
		} 
        
        /* What do you think your doing? */
        if( !$topicAuthor->member_id )
        {
            return;
        }          
        
		/* Fetch the forum */
		try
		{
			$forum = \IPS\forums\Forum::load( intval( $settings->forum ) );
		}
		catch( \OutOfRangeException $ex )
		{
            return; 
		}        

		/* Format post content */
        $topicTitle  = \IPS\Member::loggedIn()->language()->addToStack( "form_form_{$form->form_id}_topic_title" );
        $postContent = \IPS\Member::loggedIn()->language()->addToStack( "form_form_{$form->form_id}_topic_post" ); // \IPS\Theme::i()->getTemplate( 'global', 'autowelcome', 'global' )->topic( $member );
        \IPS\Member::loggedIn()->language()->parseOutputForDisplay( $topicTitle );
        \IPS\Member::loggedIn()->language()->parseOutputForDisplay( $postContent );
        
        /* Swap out our tags */
        if( $tags )
        {
    		foreach( $tags as $key => $value )
    		{
                \IPS\Member::loggedIn()->language()->parseOutputForDisplay( $value );
                
    			$postContent = preg_replace( $key, $value, $postContent );
                $topicTitle  = preg_replace( $key, $value, $topicTitle );
    		}            
        }
        
		/* Create topic */
		$topic = \IPS\forums\Topic::createItem( $topicAuthor, $topicAuthor->ip_address, \IPS\DateTime::ts( time() ), $forum, FALSE );
		$topic->title = $topicTitle;
		$topic->topic_archive_status = \IPS\forums\Topic::ARCHIVE_EXCLUDE;
 		$topic->save();
		
		/* Create post */
		$post = \IPS\forums\Topic\Post::create( $topic, $postContent, TRUE, NULL, NULL, $topicAuthor );
		$topic->topic_firstpost = $post->pid;
		$topic->save();            

        return $topic->tid;
	}
    
	/**
	 * Send email
	 */   
	public static function _sendEmail( $settings, $member=NULL, $form=NULL, $tags=NULL )
	{
        /* Email alert enabled */
        if( !$settings OR !$settings->enable OR !$settings->receiver )
        {
            return;   
        }    
        
        /* Setup email body */
        $emailSubject = \IPS\Member::loggedIn()->language()->addToStack( "form_form_{$form->form_id}_email_subject" );        
        $emailContent = \IPS\Member::loggedIn()->language()->addToStack( "form_form_{$form->form_id}_email_message" );
        \IPS\Member::loggedIn()->language()->parseOutputForDisplay( $emailSubject );
        \IPS\Member::loggedIn()->language()->parseOutputForDisplay( $emailContent );
                        
        /* Swap out our tags */
        if( $tags )
        {
    		foreach( $tags as $key => $value )
    		{
                \IPS\Member::loggedIn()->language()->parseOutputForDisplay( $value );		  
              
                $emailSubject = preg_replace( $key, $value, $emailSubject );
                $emailContent = preg_replace( $key, $value, $emailContent );
    		}            
        }        
        
        /* Check who is email sender */
        if( $settings->send_own AND $member->member_id )
        {
            $senderID = $member->member_id;
        }
        else if( $settings->send_own AND !$member->member_id )
        {
            $senderID = (int) \IPS\Settings::i()->fm_guest_override;
        }        
        else
        {
            $senderID = $settings->sender;    
        }   
        
        /* Set email sender */
		try
		{
			$emailSender   = \IPS\Member::load( $senderID );
            
            if( !$emailSender->member_id )
            {
                return;
            }             
		}
		catch( \OutOfRangeException $ex )
		{
            return; 
		} 

        /* Go through each email receiver and send */
        foreach( explode( ',', $settings->receiver ) as $receiver )
        {
    		try
    		{
                $emailReceiver = \IPS\Member::load( $receiver );
    		}
    		catch( \OutOfRangeException $ex )
    		{
                continue; 
    		}             
            
            /* Setup email send */ 
            $email = \IPS\Email::buildFromContent( $emailSubject, $emailContent, NULL, \IPS\Email::TYPE_TRANSACTIONAL );

            /* Now send */
    		$email->send( $emailReceiver, array(), array(), $emailSender->email, $emailSender->name ); 
        }     
	}  
    
	/**
	 * Send confirm email
	 */   
	public static function _sendConfirmEmail( $confirmEmailAddress=NULL, $settings, $member=NULL, $form=NULL, $tags=NULL )
	{
        /* Have email address to use? */
        if( !$confirmEmailAddress )
        {
            return; 
        }     
        
        /* Set email sender */
		try
		{
			$emailSender = \IPS\Member::load( (int) $settings->sender );
		}
		catch( \OutOfRangeException $ex )
		{
            return; 
		}   
        
        /* Setup email send */ 
		$email = \IPS\Email::buildFromTemplate( 'form', 'confirmEmail', array(), \IPS\Email::TYPE_TRANSACTIONAL );

        /* Now send */
		$email->send( $confirmEmailAddress, array(), array(), ( $emailSender->email ) ? $emailSender->email : NULL );               
	}   
    
	/**
	 * Send confirm pm
	 */   
	public static function _sendConfirmPM( $settings, $member=NULL, $form=NULL )
	{
        /* Create conversation */        
        $conversation = \IPS\core\Messenger\Conversation::createItem( $member, $member->ip_address, \IPS\DateTime::ts( time() ) );
        $conversation->title = \IPS\Member::loggedIn()->language()->get( "form_confirm_pm_subject" );
        $conversation->is_system = TRUE;
        $conversation->save();

 		/* Authorize everyone */     
        $c_members = array();  
        $c_members[] = $member->member_id;
		$conversation->authorize( $c_members ); 
        
        /* Add message */ 
		$message = \IPS\core\Messenger\Message::create( $conversation, \IPS\Member::loggedIn()->language()->get( "form_confirm_pm_message" ), TRUE, NULL, NULL, $member );
		$conversation->first_msg_id = $message->id;
		$conversation->save(); 
        
        /* Send notification */    
		$notification = new \IPS\Notification( \IPS\Application::load('core'), 'private_message_added', $conversation, array( $conversation, $member ) );
		$notification->send();              
	}      

	/**
	 * Send pm
	 */      
	public static function _sendPM( $settings=NULL, $member=NULL, $form=NULL, $tags=NULL )
	{
        /* PM alert enabled */
        if( !$settings OR !$settings->enable )
        {
            return;   
        }   
        
        /* Setup pm title and msg */
        $msgTitle = \IPS\Member::loggedIn()->language()->addToStack( "form_form_{$form->form_id}_pm_subject" );
        $msgPost  = \IPS\Member::loggedIn()->language()->addToStack( "form_form_{$form->form_id}_pm_message" );
        \IPS\Member::loggedIn()->language()->parseOutputForDisplay( $msgTitle );        
        \IPS\Member::loggedIn()->language()->parseOutputForDisplay( $msgPost );  

        /* Swap out our tags */
        if( $tags )
        {
    		foreach( $tags as $key => $value )
    		{
                \IPS\Member::loggedIn()->language()->parseOutputForDisplay( $value );		  
              
                $msgTitle = preg_replace( $key, $value, $msgTitle );
                $msgPost  = preg_replace( $key, $value, $msgPost );            
    		}            
        }
        
        /* Check who is pm sender */
        if( $settings->send_own AND $member->member_id )
        {
            $senderID = $member->member_id;
        }
        else if( $settings->send_own AND !$member->member_id )
        {
            $senderID = (int) \IPS\Settings::i()->fm_guest_override;
        }        
        else
        {
            $senderID = $settings->sender;    
        }       
        
        /* Set pm sender */
		try
		{
			$pmSender   = \IPS\Member::load( $senderID );

            /* What do you think your doing? */
            if( !$pmSender->member_id )
            {
                return;
            } 
		}
		catch( \OutOfRangeException $ex )
		{
            return; 
		} 
        
        /* Go through each pm receiver and send */
        foreach( explode( ',', $settings->receiver ) as $receiver )
        {
    		try
    		{
                $pmReceiver = \IPS\Member::load( $receiver );
                
                if( !$pmReceiver->member_id )
                {
                    continue;
                }                
    		}
    		catch( \OutOfRangeException $ex )
    		{
                continue; 
    		}             
            
            /* Create conversation */        
            $conversation = \IPS\core\Messenger\Conversation::createItem( $pmSender, $pmSender->ip_address, \IPS\DateTime::ts( time() ) );
            $conversation->title = $msgTitle;
            $conversation->is_system = TRUE;
            $conversation->save();

     		/* Authorize everyone */     
            $c_members = array();  
    		$c_members[] = $pmReceiver->member_id;
            $c_members[] = $pmSender->member_id;
    		$conversation->authorize( $c_members ); 
            
            /* Add message */ 
    		$message = \IPS\core\Messenger\Message::create( $conversation, $msgPost, TRUE, NULL, NULL, $pmSender );
    		$conversation->first_msg_id = $message->id;
    		$conversation->save(); 
            
            /* Send notification */    
    		$notification = new \IPS\Notification( \IPS\Application::load('core'), 'private_message_added', $conversation, array( $conversation, $pmSender ) );
    		$notification->send(); 
        }                      
	}                 
}
