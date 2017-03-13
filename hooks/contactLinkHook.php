//<?php

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	exit;
}

class form_hook_contactLinkHook extends _HOOK_CLASS_
{

/* !Hook Data - DO NOT REMOVE */
public static function hookData() {
 return array_merge_recursive( array (
  'footer' => 
  array (
    0 => 
    array (
      'selector' => '#elFooterLinks > li > a[data-ipsdialog]',
      'type' => 'replace',
      'content' => '{{if settings.form_disableContactLink}}<a href=\'{url="app=core&module=contact&controller=contact" seoTemplate="contact"}\' data-ipsdialog data-ipsDialog-remoteSubmit data-ipsDialog-flashMessage=\'{lang="contact_sent_blurb"}\' data-ipsdialog-title="{lang="contact"}">{lang=\'contact\'}</a>{{else}}<a href=\'{url="app=form" seoTemplate="form"}\'>{lang=\'contact\'}</a>{{endif}}',
    ),
  ),
), parent::hookData() );
}
/* End Hook Data */


}
