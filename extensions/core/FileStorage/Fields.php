<?php
/**
 * @package		Forms
 * @author		<a href='http://www.devfuse.com'>DevFuse</a>
 * @copyright	(c) 2015 DevFuse
 */

namespace IPS\form\extensions\core\FileStorage;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * File Storage Extension: Fields
 */
class _Fields
{
	/**
	 * Count stored files
	 *
	 * @return	int
	 */
	public function count()
	{
		$count = 0;
		
		foreach( \IPS\Db::i()->select( '*', 'form_fields', array( 'field_type=?', 'Upload' ) ) AS $field )
		{
			$count += \IPS\Db::i()->select( 'COUNT(*)', 'form_values', array( "value_value<>? OR value_value IS NOT NULL", '' ) )->first();
		}
		
		return $count;
	}
	
	/**
	 * Move stored files
	 *
	 * @param	int			$offset					This will be sent starting with 0, increasing to get all files stored by this extension
	 * @param	int			$storageConfiguration	New storage configuration ID
	 * @param	int|NULL	$oldConfiguration		Old storage configuration ID
	 * @throws	\Underflowexception				When file record doesn't exist. Indicating there are no more files to move
	 * @return	void								FALSE when there are no more files to move
	 */
	public function move( $offset, $storageConfiguration, $oldConfiguration=NULL )
	{
        if( !\IPS\Db::i()->select( 'COUNT(*)', 'form_fields', array( 'field_type=?', 'Upload' ) )->first() )
        {
            throw new \Underflowexception;
        }

		foreach( \IPS\Db::i()->select( '*', 'form_fields', array( 'field_type=?', 'Upload' ) ) AS $field )
		{
			$cfield	= \IPS\Db::i()->select( '*', 'form_values', array( "value_value<>? OR value_value IS NOT NULL", '' ), 'value_log_id', array( $offset, 1 ) )->first();
			
			try
			{
				$file = \IPS\File::get( $oldCinfiguration ?: 'form_fields', $cfield[ 'value_value' ] )->move( $storageConfiguration );
				
				if ( (string) $file != $cfield[ 'value_value' ] )
				{
					\IPS\Db::i()->update( 'form_values', array( "value_value=?", (string) $file ), array( 'value_log_id=?', $cfield['value_log_id'] ) );
				}
			}
			catch( \Exception $e )
			{
				/* Any issues are logged */
			}
		}
	}
	
	/**
	 * Fix all URLs
	 *
	 * @param	int			$offset					This will be sent starting with 0, increasing to get all files stored by this extension
	 * @return void
	 */
	public function fixUrls( $offset )
	{
		if( !\IPS\Db::i()->select( 'COUNT(*)', 'form_fields', array( 'field_type=?', 'Upload' ) )->first() )
        {
            throw new \Underflowexception;
        }

		foreach( \IPS\Db::i()->select( '*', 'form_fields', array( 'field_type=?', 'Upload' ) ) AS $field )
		{
			$cfield	= \IPS\Db::i()->select( '*', 'form_values', array( "value_value<>? OR value_value IS NOT NULL", '' ), 'value_log_id', array( $offset, 1 ) )->first();
			
			if ( $new = \IPS\File::repairUrl( $cfield[ 'value_value' ] ) )
			{
				\IPS\Db::i()->update( 'form_values', array( "value_value=?", $new ), array( 'value_log_id=?', $cfield['value_log_id'] ) );
			}
		}
	}
	
	/**
	 * Check if a file is valid
	 *
	 * @param	\IPS\Http\Url	$file		The file to check
	 * @return	bool
	 */
	public function isValidFile( $file )
	{
		$valid = FALSE;
		foreach( \IPS\Db::i()->select( '*', 'form_fields', array( 'field_type=?', 'Upload' ) ) AS $field )
		{
			try
			{
				\IPS\Db::i()->select( '*', 'form_values', array( "value_value=?", (string) $file ) )->first();
				
				$valid = TRUE;
				break;
			}
			catch( \UnderflowException $e ) {}
		}
		
		return $valid;
	}

	/**
	 * Delete all stored files
	 *
	 * @return	void
	 */
	public function delete()
	{
		foreach( \IPS\Db::i()->select( '*', 'form_fields', array( 'field_type=?', 'Upload' ) ) AS $field )
		{
			try
			{
				foreach( \IPS\Db::i()->select( '*', 'form_values', array( "value_value<>? OR value_value IS NOT NULL", '' ) ) as $cfield )
				{
					\IPS\File::get( 'core_FileField', $cfield[ 'value_value' ] )->delete();
				}
			}
			catch( \Exception $e ){}
		}
	}
}