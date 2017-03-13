<?php


namespace IPS\form\setup\upg_100001;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * 1.1.0 Beta 1 Upgrade Code
 */
class _Upgrade
{
	/**
	 * Main changes
	 */
	public function step1()
	{
        \IPS\Db::i()->renameTable( 'form_fields_values', 'form_values' );	
                
        /* Re-enable app again */
        \IPS\Db::i()->update( 'core_applications', array( 'app_enabled' => 1 ), array( 'app_directory=?', 'form' ) ); 
        
        /* Remove old IPB 3.4.x groups */
        \IPS\Db::i()->dropColumn( 'core_groups', array( 'g_fs_view_offline', 'g_fs_allow_attach' ) );
        
        return TRUE;            
	}    
 
	/**
	 * Column modifications
	 */
	public function step2()
	{
        /* Field column changes */
		\IPS\Db::i()->addColumn( 'form_fields', array(
			'name'			=> 'field_multiple',
			'type'			=> 'tinyint',
			'length'		=> 1,
            'unsigned'      => true,            
			'default'		=> 0
		) );
		\IPS\Db::i()->addColumn( 'form_fields', array(
			'name'			=> 'field_max_input',
			'type'			=> 'smallint',
			'length'		=> 6,         
			'default'		=> 0
		) );
		\IPS\Db::i()->addColumn( 'form_fields', array(
			'name'			=> 'field_input_format',
			'type'			=> 'text',        
			'null'		    => TRUE
		) );
        
        /* Log column changes */        
		\IPS\Db::i()->changeColumn( 'form_logs', 'member_id', array(
			'name'			=> 'log_member_id',
			'type'			=> 'int',
			'length'		=> 10,
			'default'		=> 0
		) );      
		\IPS\Db::i()->changeColumn( 'form_logs', 'member_name', array(
			'name'			=> 'log_member_name',
			'type'			=> 'varchar',
			'length'		=> 255
		) ); 
		\IPS\Db::i()->changeColumn( 'form_logs', 'member_email', array(
			'name'			=> 'log_member_email',
			'type'			=> 'varchar',
			'length'		=> 255
		) );               
		\IPS\Db::i()->changeColumn( 'form_logs', 'ip_address', array(
			'name'			=> 'log_ip_address',
			'type'			=> 'varchar',
			'length'		=> 46,
            'default'       => ''
		) ); 
		\IPS\Db::i()->changeColumn( 'form_logs', 'message', array(
			'name'			=> 'log_message',
			'type'			=> 'text',
            'null'          => TRUE,
            'default'       => NULL
		) );       
        
        /* Index changes */
		\IPS\Db::i()->addIndex( 'form_fields', array(
			'type'			=> 'key',
			'name'			=> 'field_position',
			'columns'		=> array( 'field_position' )
		) );
		\IPS\Db::i()->addIndex( 'form_forms', array(
			'type'			=> 'key',
			'name'			=> 'position',
			'columns'		=> array( 'parent_id', 'position' )
		) );
		\IPS\Db::i()->addIndex( 'form_logs', array(
			'type'			=> 'key',
			'name'			=> 'log_date',
			'columns'		=> array( 'log_date' )
		) );  
		\IPS\Db::i()->addIndex( 'form_logs', array(
			'type'			=> 'key',
			'name'			=> 'log_form_id',
			'columns'		=> array( 'log_form_id' )
		) );                             
        
        /* Remove non lang columns */        
		\IPS\Db::i()->dropColumn( 'form_fields', array( 'field_extras', 'field_data' ) );
		\IPS\Db::i()->dropColumn( 'form_logs', array( 'has_attach' ) );
                      
        return TRUE;                            
	}
    
	/**
	 * Update custom field type
	 */
	public function step3()
	{
		foreach( \IPS\Db::i()->select( '*', 'form_fields' ) as $field )
		{
			$update	= array();

			switch( $field['field_type'] )
			{
				case 'input':
					$update['field_type']	= 'Text';
				break;
                
				case 'dropdown':
					$update['field_type']	= 'Select';
				break;
                
				case 'multiselect':
					$update['field_type']	  = 'Select';
                    $update['field_multiple'] = TRUE;
				break; 
                
				case 'radiobutton':
					$update['field_type']	= 'Radio';
				break;       
                                        
				case 'checkbox':
					$update['field_type']	  = 'CheckboxSet';
                    $update['field_multiple'] = TRUE;
				break;                       
                       
 				case 'textarea':
					$update['field_type']	= 'TextArea';
				break; 
                                     
				case 'editor':
					$update['field_type']	= 'Editor';
				break;   
 
 				case 'password':
					$update['field_type']	= 'Password';
				break;               
			}

			if( count( $update ) )
			{
				\IPS\Db::i()->update( 'form_fields', $update, 'field_id=' . $field['field_id'] );
			}
		}

		return TRUE;                         
	} 
    
	/**
	 * Custom title for this step
	 *
	 * @return string
	 */
	public function step3CustomTitle()
	{
		return "Fixing custom fields";
	}       
    
	/**
	 * Serialize to JSON
	 */
	public function step4()
	{
        /* Convert forms */
		foreach( \IPS\Db::i()->select( '*', 'form_forms' ) as $form )
		{
            $formSave['options']	    = json_encode( \unserialize( $form['options'] ) );
            $formSave['pm_settings']	= json_encode( \unserialize( $form['pm_settings'] ) );
            $formSave['email_settings']	= json_encode( \unserialize( $form['email_settings'] ) );
            $formSave['topic_settings']	= json_encode( \unserialize( $form['topic_settings'] ) );

			\IPS\Db::i()->update( 'form_forms', $formSave, array( 'form_id=?', $form['form_id'] ) );
		}
        
	    /* Convert fields */
        foreach( \IPS\Db::i()->select( '*', 'form_fields' ) as $field )
        {
       	    if( $field['field_options'] )
		    {
			    $fieldOptions = \unserialize( $field['field_options'] );
			    
			    if( is_array( $fieldOptions ) AND count( $fieldOptions ) )
			    {
				    $fieldSave['field_options'] = json_encode( array_values( $fieldOptions ) );				    
			    }
			    else
			    {
				    $fieldSave['field_options'] = '';    
			    }			    
		    }
		    else
		    {
			    $fieldSave['field_options'] = '';    
		    }

            \IPS\Db::i()->update( 'form_fields', $fieldSave, array( 'field_id=?', $field['field_id'] ) );
        }          
        
        return TRUE;                        
	}  
    
	/**
	 * Custom lang setup
	 */
	public function step5()
	{
        /* Change app name */
        \IPS\Lang::saveCustom( 'form', '__app_form', 'Forms' );	   	   
       
        /* Convert form lang */
		foreach( \IPS\Db::i()->select( '*', 'form_forms' ) as $form )
		{
		    /* Basic fields */
			\IPS\Lang::saveCustom( 'form', "form_form_{$form['form_id']}", $form['form_name'] );
			\IPS\Lang::saveCustom( 'form', "form_form_{$form['form_id']}_desc", $form['description'] );
			\IPS\Lang::saveCustom( 'form', "form_form_{$form['form_id']}_rules", $form['form_rules'] );
            
            /* Decode the new json fields */
            $form['options'] = json_decode( $form['options'], TRUE );
            $form['pm_settings'] = json_decode( $form['pm_settings'], TRUE );
            $form['topic_settings'] = json_decode( $form['topic_settings'], TRUE );
            $form['email_settings'] = json_decode( $form['email_settings'], TRUE );
            
            /* Save lang for json encoded settings */
            if( isset( $form['options']['log_message'] ) AND $form['options']['log_message'] )
            {
                \IPS\Lang::saveCustom( 'form', "form_form_{$form['form_id']}_log", $form['options']['log_message'] );                
            }
            if( isset( $form['pm_settings']['subject'] ) AND $form['pm_settings']['subject'] )
            {   
                \IPS\Lang::saveCustom( 'form', "form_form_{$form['form_id']}_pm_subject", $form['pm_settings']['subject'] );
                \IPS\Lang::saveCustom( 'form', "form_form_{$form['form_id']}_pm_message", $form['pm_settings']['message'] );   
            }
            if( isset( $form['topic_settings']['title'] ) AND $form['topic_settings']['title'] )
            {                     
                \IPS\Lang::saveCustom( 'form', "form_form_{$form['form_id']}_topic_title", $form['topic_settings']['title'] );
                \IPS\Lang::saveCustom( 'form', "form_form_{$form['form_id']}_topic_post", $form['topic_settings']['post'] );
            }
            if( isset( $form['email_settings']['subject'] ) AND $form['email_settings']['subject'] )
            {            
                \IPS\Lang::saveCustom( 'form', "form_form_{$form['form_id']}_email_subject", $form['email_settings']['subject'] );
                \IPS\Lang::saveCustom( 'form', "form_form_{$form['form_id']}_email_message", $form['email_settings']['message'] ); 
            }                   
		}

        /* Now drop all these old fields */
		\IPS\Db::i()->dropColumn( 'form_forms', array( 'form_name', 'description', 'info', 'form_rules' ) ); 
        
        return TRUE;                         
	} 
    
	/**
	 * Custom title for this step
	 *
	 * @return string
	 */
	public function step5CustomTitle()
	{
		return "Fixing form language";
	}         
    
	/**
	 * Custom lang setup
	 */
	public function step6()
	{
        /* Convert field lang */
		foreach( \IPS\Db::i()->select( '*', 'form_fields' ) as $field )
		{
			\IPS\Lang::saveCustom( 'core', "form_field_{$field['field_id']}", $field['field_title'] ); 
            
            if( $field['field_text'] )
            {
                \IPS\Lang::saveCustom( 'core', "form_field_{$field['field_id']}_desc", $field['field_text'] );    
            }       
		}

		\IPS\Db::i()->dropColumn( 'form_fields', array( 'field_title', 'field_name', 'field_text' ) );
        
        return TRUE;                         
	}
    
	/**
	 * Custom title for this step
	 *
	 * @return string
	 */
	public function step6CustomTitle()
	{
		return "Fixing field language";
	} 
    
	/**
	 * Convert form settings
	 */
	public function step7()
	{
        /* Convert form settings */
		foreach( \IPS\Db::i()->select( '*', 'form_forms' ) as $form )
		{
            /* Decode the new json fields */
            $form['options'] = json_decode( $form['options'], TRUE );
            $form['pm_settings'] = json_decode( $form['pm_settings'], TRUE );
            $form['topic_settings'] = json_decode( $form['topic_settings'], TRUE );
            $form['email_settings'] = json_decode( $form['email_settings'], TRUE );
            
            /* Adjust pm settings */
            if( $form['pm_settings']['sender'] == '-1' )
            {
                $form['pm_settings']['sender']   = 0;
                $form['pm_settings']['send_own'] = 1;        
            } 

            /* Adjust email settings */
            if( $form['email_settings']['sender'] == '-1' )
            {
                $form['email_settings']['sender']   = 0;
                $form['email_settings']['send_own'] = 1; 
                
                unset( $form['email_settings']['receiver_type'], $form['email_settings']['groups'] );       
            } 
            if( isset( $form['email_settings']['receiver'] ) AND $form['email_settings']['receiver'] )
            {
                $member = \IPS\Member::load( $form['email_settings']['receiver'], 'email' )->member_id;
                $form['email_settings']['receiver'] = ( $member ) ? $member : 0;        
            }
            
            /* Encode our settings again */
            $fieldSave['pm_settings'] = json_encode( $form['pm_settings'] );
            $fieldSave['email_settings'] = json_encode( $form['email_settings'] );  
            $fieldSave['topic_settings'] = json_encode( $form['topic_settings'] );
            
            /* Save everything */
            \IPS\Db::i()->update( 'form_forms', $fieldSave, array( 'form_id=?', $form['form_id'] ) );             
		}
        
        return TRUE;                         
	}
    
	/**
	 * Custom title for this step
	 *
	 * @return string
	 */
	public function step7CustomTitle()
	{
		return "Fixing form settings";
	}        
    
	/**
	 * Rebuild post content
	 */
	public function step8()
	{ 
        /* Rebuild our form lang */
        \IPS\Task::queue( 'core', 'RebuildNonContentPosts', array( 'extension' => 'form_Forms' ), 3 ); 	   
       
        /* Rebuild log contents */
        try
		{
			\IPS\Task::queue( 'core', 'RebuildPosts', array( 'class' => 'IPS\form\Log' ), 3 );
		}
		catch( \OutOfRangeException $ex ) { }
        
        return TRUE;
    }
    
	/**
	 * Custom title for this step
	 *
	 * @return string
	 */
	public function step8CustomTitle()
	{
		return "Building content";
	}         
}