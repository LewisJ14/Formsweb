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
 * Logs
 */
class _logs extends \IPS\Dispatcher\Controller
{
	/**
	 * Execute
	 *
	 * @return	void
	 */
	public function execute()
	{
		parent::execute();
        
        /* Can view logs? */
		if( !\IPS\Member::loggedIn()->group['g_fs_view_logs'] )
		{
			\IPS\Output::i()->error( 'view_logs_error', '', 404, '' );
		}        
        
		/* You must purchase copyright removal before removing */
        if( !\IPS\Settings::i()->devfuse_copy_num && !\IPS\Request::i()->isAjax() )
        {
            \IPS\Output::i()->output .= "<div style='clear:both;text-align:center;position:absolute;bottom:15px;width:95%;'><a href='http://www.devfuse.com/' class='ipsType_light ipsType_smaller'>IP.Board Forms by DevFuse</a></div>";    
        }         
	}    
    
	/**
	 * Log List
	 *
	 * @return	void
	 */
	protected function manage()
	{
	    /* Setup table */
		$table = new \IPS\Helpers\Table\Content( 'IPS\form\Log', \IPS\Http\Url::internal( 'app=form&module=forms&controller=logs', 'front', 'form_logs' ), array(), NULL, FALSE, 'read' );
		$table->rowsTemplate = array( \IPS\Theme::i()->getTemplate( 'logs' ), 'logRow' );
		$table->classes[] = 'ipsDataList_large';
        $table->limit = ( \IPS\Settings::i()->fm_logs_per_page ) ? (int) \IPS\Settings::i()->fm_logs_per_page : 20;
        
		/* Set default sort */
		if( !\IPS\Request::i()->sortby )
		{
			$table->sortBy = 'log_date';
		}     
        
        /* Filter by form */
		$filters = array();
		foreach ( \IPS\form\Form::roots() as $form )
		{
		    $title = $form->_title;
		    \IPS\Member::loggedIn()->language()->parseOutputForDisplay( $title );
			$filters[ $title ] = array('log_form_id=?', $form->_id );
		}
		$table->filters = $filters;            

        /* Sorting table */
        $table->sortOptions = array( 'sort_date' => 'log_date', 'sort_member_name' => 'log_member_name' );
        $table->sortBy = ( $table->sortBy AND in_array( $table->sortBy, array( 'log_date', 'log_member_name' ) ) ) ? $table->sortBy: 'log_date';
       	$table->sortDirection = $table->sortDirection ?: 'desc'; 

        /* Print out logs */
        \IPS\Output::i()->breadcrumb[] = array( \IPS\Http\Url::internal( 'app=form&module=forms&controller=logs', 'front', 'form_logs' ), \IPS\Member::loggedIn()->language()->addToStack( 'module__forms_logs' ) );
		\IPS\Output::i()->title = \IPS\Member::loggedIn()->language()->addToStack('module__forms_logs');
		\IPS\Output::i()->output = \IPS\Theme::i()->getTemplate('logs')->logList( (string) $table );       
	}
    
	/**
	 * View Log
	 *
	 * @return	void
	 */
	protected function view()
	{
	    /* Get log entry */
		try
		{
			$log = \IPS\form\Log::load( \IPS\Request::i()->id );
			
			if ( !$log->canView( \IPS\Member::loggedIn() ) )
			{
				\IPS\Output::i()->error( 'node_error', '', 404, '' );
			}
		}
		catch ( \OutOfRangeException $e )
		{
			\IPS\Output::i()->error( 'node_error', '', 404, '' );
		}	   

        /* Print log entry */
		\IPS\Output::i()->title  = \IPS\Member::loggedIn()->language()->addToStack('form_log_id').$log->id;
		\IPS\Output::i()->output = \IPS\Theme::i()->getTemplate( 'logs' )->logEntry( $log );     
	}
    
	/**
	 * Reply to log
	 *
	 * @return	void
	 */
	public function reply()
	{		
		//\IPS\Session::i()->csrfCheck();
		
		try
		{
			$log = \IPS\form\Log::load( \IPS\Request::i()->id );
		}
		catch( \OutOfRangeException $e )
		{
			\IPS\Output::i()->error( 'node_error', '', 404, '' );
		}

        /* Setup reply form */
		$form = new \IPS\Helpers\Form( 'reply', 'send_reply' );
		$form->class = 'ipsForm_vertical';
		$form->add( new \IPS\Helpers\Form\Email( 'reply_sender', \IPS\Member::loggedIn()->email, TRUE, array() ) );
        $form->add( new \IPS\Helpers\Form\Email( 'reply_receiver', ( $log->member_email ) ? $log->member_email : '', TRUE, array() ) );
        $form->add( new \IPS\Helpers\Form\Text( 'reply_subject', ( $log->form_id ) ? \IPS\Member::loggedIn()->language()->addToStack('re').': '.$log->container()->_title : '', TRUE, array() ) );
		$form->add( new \IPS\Helpers\Form\Editor( 'reply_message',  ( $log->message ) ? '<blockquote data-ipsquote="" data-cite="'.\IPS\Member::loggedIn()->language()->addToStack('quote').'" class="ipsQuote"><p>'.$log->message.'</p></blockquote><br>' : '', TRUE, array( 'app' => 'form', 'key' => 'Forms', 'minimize' => 'reply_message_placeholder', 'autoSaveKey' => "reply-{$log->id}-note" ) ) );

		/* Handle submissions */
		if ( $values = $form->values() )
		{
            /* Setup email send */ 
            $email = \IPS\Email::buildFromContent( $values['reply_subject'], $values['reply_message'], NULL, \IPS\Email::TYPE_TRANSACTIONAL );

            /* Now send */
    		$email->send( $values['reply_receiver'], array(), array(), $values['reply_sender'] ); 		   
          
			/* Boink */
			if ( \IPS\Request::i()->isAjax() )
			{
				\IPS\Output::i()->json( 'ok' );
			}
			else
			{
				\IPS\Output::i()->redirect( \IPS\Http\Url::internal( 'app=form&module=forms&controller=logs' ) );
			}	  
		}        
        
        /* Print form */
        \IPS\Output::i()->title  = \IPS\Member::loggedIn()->language()->addToStack('reply_log_id') . $log->id;
		\IPS\Output::i()->output = $form->customTemplate( array( call_user_func_array( array( \IPS\Theme::i(), 'getTemplate' ), array( 'logs', 'form' ) ), 'replyForm' ) );
	}    
    
	/**
	 * Delete log
	 *
	 * @return	void
	 */
	public function delete()
	{		
		\IPS\Session::i()->csrfCheck();
		
		try
		{
			$log = \IPS\form\Log::load( \IPS\Request::i()->id );
		}
		catch( \OutOfRangeException $e )
		{
			\IPS\Output::i()->error( 'node_error', '', 404, '' );
		}

		$log->delete();
		
		if ( \IPS\Request::i()->isAjax() )
		{
			\IPS\Output::i()->json( array( 'OK' ) );
		}
		else
		{
			\IPS\Output::i()->redirect( \IPS\Http\Url::internal( "app=form&module=forms&controller=logs", 'front', 'form_logs' ) );
		}
	}        
}