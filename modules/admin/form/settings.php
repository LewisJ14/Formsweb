<?php
/**
 * @package		Forms
 * @author		<a href='http://www.devfuse.com'>DevFuse</a>
 * @copyright	(c) 2015 DevFuse
 */

namespace IPS\form\modules\admin\form;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * settings
 */
class _settings extends \IPS\Dispatcher\Controller
{
	/**
	 * Execute
	 *
	 * @return	void
	 */
	public function execute()
	{
		\IPS\Dispatcher::i()->checkAcpPermission( 'settings_manage' );
		parent::execute();
	}

	/**
	 * ...
	 *
	 * @return	void
	 */
	protected function manage()
	{
		\IPS\Output::i()->title = \IPS\Member::loggedIn()->language()->addToStack('settings');

		$form = new \IPS\Helpers\Form;

        /* Form Settings */
        $form->addHeader( 'form_settings' );
        $form->add( new \IPS\Helpers\Form\Node( 'form_landing_page', \IPS\Settings::i()->form_landing_page ?: NULL, FALSE, array( 'class' => 'IPS\form\Form', 'subnodes' => FALSE, 'multiple' => FALSE, 'zeroVal' => true, 'zeroVal' => 'form_list' ), NULL, NULL, NULL, 'form_landing_page' ) );
        $form->add( new \IPS\Helpers\Form\YesNo( 'fm_require_form', \IPS\Settings::i()->fm_require_form, FALSE, array(), NULL, NULL, NULL, 'fm_require_form' ) );
        $form->add( new \IPS\Helpers\Form\YesNo( 'fm_legacy_form', \IPS\Settings::i()->fm_legacy_form, FALSE, array(), NULL, NULL, NULL, 'fm_legacy_form' ) );
        $form->add( new \IPS\Helpers\Form\YesNo( 'fm_system_pms', \IPS\Settings::i()->fm_system_pms, FALSE, array(), NULL, NULL, NULL, 'fm_system_pms' ) );
        $form->add( new \IPS\Helpers\Form\YesNo( 'form_disableContactLink', \IPS\Settings::i()->form_disableContactLink, FALSE, array(), NULL, NULL, NULL, 'form_disableContactLink' ) );
        $form->add( new \IPS\Helpers\Form\Member( 'fm_guest_override', ( \IPS\Settings::i()->fm_guest_override ) ? \IPS\Member::load( \IPS\Settings::i()->fm_guest_override ) : \IPS\Member::loggedIn(), FALSE, array(), NULL, NULL, NULL, 'fm_guest_override' ) ); 

        /* Log Settings */
        $form->addHeader( 'log_settings' );
        $form->add( new \IPS\Helpers\Form\Number( 'fm_logs_per_page', \IPS\Settings::i()->fm_logs_per_page, FALSE, array(), NULL, NULL, NULL, 'fm_logs_per_page' ) );
        $form->add( new \IPS\Helpers\Form\Number( 'fm_log_preview', \IPS\Settings::i()->fm_log_preview, FALSE, array( 'unlimited' => 0, 'unlimitedLang' => 'disable' ), NULL, NULL, \IPS\Member::loggedIn()->language()->addToStack('lines'), 'fm_log_preview' ) );
        $form->add( new \IPS\Helpers\Form\Select( 'fm_logs_default_sort_field', \IPS\Settings::i()->fm_logs_default_sort_field, FALSE, array( 'options' => array( 'log_date' => 'sort_log_date', 'log_form_id' => 'sort_log_form', 'member_name' => 'sort_member_name' ) ), NULL, NULL, NULL, 'fm_logs_default_sort_field' ) );
        $form->add( new \IPS\Helpers\Form\Select( 'fm_logs_default_sort_order', \IPS\Settings::i()->fm_logs_default_sort_order, FALSE, array( 'options' => array( 'desc' => 'sort_desc', 'asc' => 'sort_asc' ) ), NULL, NULL, NULL, 'fm_logs_default_sort_order' ) );
          
		if ( $values = $form->values() )
		{
			$form->saveAsSettings();
		}

		\IPS\Output::i()->output = $form;
	}
}