<?php
/**
 * @brief		Rules extension: Forms
 * @package		Rules for IPS Social Suite
 * @since		18 Nov 2015
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\form\extensions\rules\Definitions;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * @brief	Rules definitions extension: Forms
 */
class _Forms
{

	/**
	 * @brief	The default option group title to list events, conditions, and actions from this class
	 */
	public $defaultGroup = 'Forms';

	/**
	 * Triggerable Events
	 *
	 * Define the events that can be triggered by your application
	 *
	 * @return 	array		Array of event definitions
	 */
	public function events()
	{
		$events = array();
		$lang = \IPS\Member::loggedIn()->language();
		
		foreach( \IPS\form\Form::roots( NULL ) as $form )
		{	
			$event_key = 'form_submitted_' . $form->_id;
			
			/* Event & Log Title */
			$form_title = $lang->addToStack( 'form_rules_submitted', FALSE, array( 'sprintf' => array( $form->_title ) ) );
			$log_title = $lang->addToStack( 'form_rules_submitted_log', FALSE, array( 'sprintf' => array( $form->_title ) ) );
			$lang->parseOutputForDisplay( $form_title );
			$lang->parseOutputForDisplay( $log_title );
			$lang->words[ 'form_Forms_event_' . $event_key ] = $form_title;
			$lang->words[ 'form_Forms_event_' . $event_key . '_log' ] = $log_title;
			
			$events[ $event_key ] = array
			( 
				'arguments' => array
				( 
					'log' => array
					(
						'argtype' 	=> 'object',
						'class'		=> '\IPS\form\Log',
						'nullable'	=> FALSE,
					),
				),
			);
			
			foreach( \IPS\form\Form\Field::roots( NULL, NULL, array( 'field_form_id=?', $form->_id ) ) as $field )
			{
				$argNullable = ! $field->required;
				$argClass = NULL;
			
				switch( $field->type )
				{
					case 'Address':
					
						$argType = 'object';
						$argClass = 'IPS\GeoLocation';
						break;
						
					case 'Checkbox':
					case 'YesNo':
					
						$argType = 'bool';
						break;
					
					case 'Code':
					case 'Codemirror':
					case 'Color':
					case 'Editor':
					case 'Email':
					case 'Password':
					case 'Text':
					case 'TextArea':
					case 'Radio':
					case 'Item':
					case 'Tel':
					
						$argType = 'string';
						break;

					case 'Select':
					
						$argType = $field->multiple ? 'array' : 'string';
						break;
						
					case 'CheckboxSet':
					
						$argType = 'array';
						break;
						
					case 'Date':
					
						$argType = 'object';
						$argClass = 'IPS\DateTime';
						break;
						
					case 'Member':
					
						$argType = 'object';
						$argClass = 'IPS\Member';
						break;
						
					case 'Number':
					case 'Rating':
					
						$argType = 'int';
						break;
						
					case 'Poll':
					
						$argType = 'object';
						$argClass = 'IPS\Poll';
						break;
					
					case 'Upload':
						
						$argType = $field->multiple ? 'array' : 'object';
						$argClass = 'IPS\File';
						break;
					
					case 'Url':
					
						$argType = 'object';
						$argClass = 'IPS\Http\Url';
						break;
					
					default:
						
						$argType = 'mixed';
						$argClass = NULL;
				}
			
				$events[ $event_key ][ 'arguments' ][ 'field_' . $field->_id ] = array
				(
					'argtype' => $argType,
					'class' => $argClass,
					'nullable' => $argNullable,
				);
				
				/* Field name language */
				$title = $field->_title;
				$lang->parseOutputForDisplay( $title );
				$lang->words[ 'form_Forms_event_' . $event_key . '_field_' . $field->_id ] = $title;
			}
		}
		
		return $events;
	}
	
	/**
	 * Conditional Operations
	 *
	 * You can define your own conditional operations which can be
	 * added to rules as conditions.
	 *
	 * @return 	array		Array of conditions definitions
	 */
	public function conditions()
	{
		$conditions = array
		(
		
		);
		
		return $conditions;
	}

	/**
	 * Triggerable Actions
	 *
	 * @return 	array		Array of action definitions
	 */
	public function actions()
	{
		$actions = array
		(

		);
		
		return $actions;
	}
	
}