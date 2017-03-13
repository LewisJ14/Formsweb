<?php
/**
 * @package		Forms
 * @author		<a href='http://www.devfuse.com'>DevFuse</a>
 * @copyright	(c) 2015 DevFuse
 */
 
namespace IPS\form\Form;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Package
 */
class _Field extends \IPS\CustomField
{
	/**
	 * @brief	[ActiveRecord] Multiton Store
	 */
	protected static $multitons;
	
	/**
	 * @brief	[ActiveRecord] Database Table
	 */
	public static $databaseTable = 'form_fields';
	
	/**
	 * @brief	[ActiveRecord] Database Prefix
	 */
	public static $databasePrefix = 'field_';
    
	/**
	 * @brief	[Node] Parent Node ID Database Column
	 */
	public static $parentNodeColumnId = 'form_id';  
    
	/**
	 * @brief	[Node] Parent Node Class
	 */
	public static $parentNodeClass = 'IPS\form\Form';      
		
	/**
	 * @brief	[Node] Order Database Column
	 */
	public static $databaseColumnOrder = 'position';
	
	/**
	 * @brief	[Node] Node Title
	 */
	public static $nodeTitle = 'form_fields';
	
	/**
	 * @brief	[CustomField] Title/Description lang prefix
	 */
	protected static $langKey = 'form_field';
	
	/**
	 * @brief	[Node] ACP Restrictions
	 */
	protected static $restrictions = array(
		'app'		=> 'form',
		'module'	=> 'form',
		'all'       => 'forms_manage'
	);

	/**
	 * @brief	[CustomField] Editor Options
	 */
	public static $editorOptions = array( 'app' => 'form', 'key' => 'Fields' );
	
	/**
	 * @brief	[CustomField] FileStorage Extension for Upload fields
	 */
	public static $uploadStorageExtension = 'Fields';
    
	/**
	 * @brief	[CustomField] Column Map
	 */
	public static $databaseColumnMap = array(
		'content'  => 'options',
		'not_null' => 'required',
        'group_id' => 'form_id',
	);  
    
	/**
	 * [Node] Get description
	 *
	 * @return	string
	 */
	protected function get__description()
	{
		return \IPS\Member::loggedIn()->language()->addToStack('quick_tags').": {field_name_".$this->id."} ".\IPS\Member::loggedIn()->language()->addToStack('form_and')." {field_value_".$this->id."}";
	}       
	
	/**
	 * [Node] Add/Edit Form
	 *
	 * @param	\IPS\Helpers\Form	$form	The form
	 * @return	void
	 */
	public function form( &$form )
	{
		parent::form( $form );
        
        /* Add default field value */
        $form->add( new \IPS\Helpers\Form\Text( 'field_value', ( $this->id ) ? $this->value : '' ) );  
        
        /* Override description field with rte support */
        $form->elements['']['pf_desc'] = new \IPS\Helpers\Form\Translatable( 'pf_desc', NULL, FALSE, array( 'app' => 'core', 'key' => ( $this->id ? static::$langKey . '_' . $this->id . '_desc' : NULL ), 'editor' => array( 'app' => 'form', 'key' => 'Fields', 'autoSaveKey' => $this->id ? "form-field-{$this->id}" : "form-new-field", 'attachIds' => $this->id ? array( $this->id, NULL, 'description' ) : NULL ) ) );              
        
        /* Remove uneeded fields */
		unset( $form->elements[''][1] );
		unset( $form->elements['']['pf_search_type'] );
		unset( $form->elements['']['pf_format'] );
	}
	
	/**
	 * [Node] Format form values from add/edit form for save
	 *
	 * @param	array	$values	Values from the form
	 * @return	array
	 */
	public function formatFormValues( $values )
	{
		if ( !$this->id )
		{
			$this->save();
			\IPS\File::claimAttachments( 'form-new-field', $this->id, NULL, 'description', TRUE );
		}	   
       
	    /* Save form id */
	    if( isset( $values['pf_group_id'] ) )
        {
            $values['form_id'] = $values['pf_group_id']->form_id;
            unset( $values['pf_group_id'] );
        }
        
        /* Save default field value */
		if( isset( $values['field_value'] ) AND $values['field_value'] )
		{
			$values['field_value'] = $values['field_value'];
		}        

		return parent::formatFormValues( $values );
	}
    
	/**
	 * Build Form Helper
	 *
	 * @param	mixed	$value	The value
	 * @return \IPS\Helpers\Form\FormAbstract
	 */
	public function buildHelper( $value=NULL, $customValidationCode=NULL )
	{
	    /* Add default field value */
		if( $this->value )
		{
    		/* Get tag values */
    		$tags = \IPS\form\Form::returnTagValues();		  
          
            /* Swap out our tags */
    		foreach( $tags as $key => $value )
    		{
                $this->value = preg_replace( $key, $value, $this->value );
    		}           
          
			$value = $this->value;
		}
		
		return parent::buildHelper( $value );
	}    
}