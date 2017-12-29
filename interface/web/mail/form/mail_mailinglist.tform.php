<?php

/*
	Form Definition

	Tabledefinition

	Datatypes:
	- INTEGER (Forces the input to Int)
	- DOUBLE
	- CURRENCY (Formats the values to currency notation)
	- VARCHAR (no format check, maxlength: 255)
	- TEXT (no format check)
	- DATE (Dateformat, automatic conversion to timestamps)

	Formtype:
	- TEXT (Textfield)
	- TEXTAREA (Textarea)
	- PASSWORD (Password textfield, input is not shown when edited)
	- SELECT (Select option field)
	- RADIO
	- CHECKBOX
	- CHECKBOXARRAY
	- FILE

	VALUE:
	- Wert oder Array

	Hint:
	The ID field of the database table is not part of the datafield definition.
	The ID field must be always auto incement (int or bigint).

	Search:
	- searchable = 1 or searchable = 2 include the field in the search
	- searchable = 1: this field will be the title of the search result
	- searchable = 2: this field will be included in the description of the search result


*/

$app->uses('getconf');
$mlManager = $app->getconf->get_server_config($conf['server_id'], 'mail')['mailinglist_manager'];

$form["title"]    = "Mailing List";
$form["description"]  = "";
$form["name"]    = "mail_mailinglist";
$form["action"]   = "mail_mailinglist_edit.php";
$form["db_table"]  = "mail_mailinglist";
$form["db_table_idx"] = "mailinglist_id";
$form["db_history"]  = "yes";
$form["tab_default"] = "mailinglist";
$form["list_default"] = "mail_mailinglist_list.php";
$form["auth"]   = 'yes'; // yes / no

$form["auth_preset"]["userid"]  = 0; // 0 = id of the user, > 0 id must match with id of current user
$form["auth_preset"]["groupid"] = 0; // 0 = default groupid of the user, > 0 id must match with groupid of current user
$form["auth_preset"]["perm_user"] = 'riud'; //r = read, i = insert, u = update, d = delete
$form["auth_preset"]["perm_group"] = 'riud'; //r = read, i = insert, u = update, d = delete
$form["auth_preset"]["perm_other"] = ''; //r = read, i = insert, u = update, d = delete

$form["tabs"]['mailinglist'] = array (
	'title'  => "Mailing List",
	'width'  => 100,
	'template'  => "templates/mail_mailinglist_edit.htm",
	'fields'  => array (
		//#################################
		// Begin Datatable fields
		//#################################
		'server_id' => array (
			'datatype' => 'INTEGER',
			'formtype' => 'SELECT',
			'default' => '',
			'datasource' => array (  'type' => 'SQL',
				'querystring' => 'SELECT server_id,server_name FROM server WHERE mail_server = 1 AND mirror_server_id = 0 AND {AUTHSQL} ORDER BY server_name',
				'keyfield'=> 'server_id',
				'valuefield'=> 'server_name'
			),
			'value' => ''
		),
		'domain' => array (
			'datatype' => 'VARCHAR',
			'formtype' => 'TEXT',
			'filters'   => array( 0 => array( 'event' => 'SAVE',
					'type' => 'IDNTOASCII'),
				1 => array( 'event' => 'SHOW',
					'type' => 'IDNTOUTF8'),
				2 => array( 'event' => 'SAVE',
					'type' => 'TOLOWER')
			),
			'validators' => array (  0 => array ( 'type' => 'NOTEMPTY',
					'errmsg'=> 'domain_error_empty'),
				1 => array ( 'type' => 'REGEX',
					'regex' => '/^[\w\.\-]{2,255}\.[a-zA-Z\-]{2,10}$/',
					'errmsg'=> 'domain_error_regex'),
			),
			'default' => '',
			'value'  => '',
			'width'  => '30',
			'maxlength' => '255',
			'searchable' => 2
		),
		'listname' => array (
			'datatype' => 'VARCHAR',
			'formtype' => 'TEXT',
			'validators' => array (  0 => array ( 'type' => 'NOTEMPTY',
					'errmsg'=> 'listname_error_empty'),
				1 => array ( 'type' => 'UNIQUE',
					'errmsg'=> 'listname_error_unique'),
			),
			'filters'   => array(
					0 => array( 'event' => 'SAVE',
					'type' => 'STRIPTAGS'),
					1 => array( 'event' => 'SAVE',
					'type' => 'STRIPNL')
			),
			'default' => '',
			'value'  => '',
			'width'  => '30',
			'maxlength' => '255',
			'searchable' => 1
		),
		'email' => array (
			'datatype' => 'VARCHAR',
			'formtype' => 'TEXT',
			'filters'   => array( 0 => array( 'event' => 'SAVE',
					'type' => 'IDNTOASCII'),
				1 => array( 'event' => 'SHOW',
					'type' => 'IDNTOUTF8'),
				2 => array( 'event' => 'SAVE',
					'type' => 'TOLOWER')
			),
			'validators' => array (  0 => array ( 'type' => 'ISEMAIL',
					'errmsg'=> 'email_error_isemail'),
			),
			'default' => '',
			'value'  => '',
			'width'  => '30',
			'maxlength' => '255',
			'searchable' => 2
		),
		'password' => array (
			'datatype' => 'VARCHAR',
			'formtype' => 'PASSWORD',
			'validators' => array(
				0 => array(
					'type' => 'CUSTOM',
					'class' => 'validate_password',
					'function' => 'password_check',
					'errmsg' => 'weak_password_txt'
				)
			),
			'encryption'=> 'CLEARTEXT',
			'default' => '',
			'value'  => '',
			'width'  => '30',
			'maxlength' => '255'
		),
		//#################################
		// ENDE Datatable fields
		//#################################
	)
);

if($mlManager == 'mlmmj') {
	$form["tabs"]['options'] = array (
		'title'  => "Options",
		'width'  => 100,
		'template'  => "templates/mail_mailinglist_options.htm",
		'fields'  => array (
			//#################################
			// Begin Datatable fields
			//#################################
			'admins' => array (
				'datatype' => 'TEXT',
				'formtype' => 'TEXTAREA',
				'validators' => array (
					0 => array ( 'type' => 'ISEMAIL',
						'allowempty' => 'y',
						'separator' => "\n",
						'errmsg'=> 'email_error_isemail'),
				),
				'cols'  => '30',
				'rows'  => '5'
			),
			'subject_prefix' => array (
				'datatype' => 'VARCHAR',
				'formtype' => 'TEXT',
				'default' => '',
				'value'  => '',
				'width'  => '30',
				'maxlength' => '255',
			),
			'mail_footer' => array (
				'datatype' => 'TEXT',
				'formtype' => 'TEXTAREA',
				'cols'  => '30',
				'rows'  => '5'
			),
			'archive' => array (
				'datatype' => 'VARCHAR',
				'formtype' => 'CHECKBOX',
				'default' => 'n',
				'value'  => array(0 => 'n', 1 => 'y')
			),
		)
	);

	$form["tabs"]['privacy'] = array (
		'title'  => "Privacy",
		'width'  => 100,
		'template'  => "templates/mail_mailinglist_privacy.htm",
		'fields'  => array (
			//#################################
			// Begin Datatable fields
			//#################################
			'list_type' => array (
				'datatype' => 'VARCHAR',
				'formtype' => 'RADIO',
				'regex'  => '',
				'errmsg' => '',
				'default' => 'user',
				'value'  => array ('open' => 'open_list_txt', 'closed' => 'closed_list_txt'),
				'width'  => '30',
				'maxlength' => '255',
				'rows'  => '',
				'cols'  => ''
			),
			'subscribe_policy' => array (
				'datatype'      => 'VARCHAR',
				'formtype'      => 'SELECT',
				'default'       => 'confirm',
				'value'         => array(
					'disabled' => 'sub_disabled_txt',
					'confirm' => 'sub_confirm_txt',
					'approval' => 'sub_approval_txt',
					'both' => 'sub_both_txt',
					'none' => 'sub_none_txt'),
			),
			'posting_policy' => array (
				'datatype'      => 'VARCHAR',
				'formtype'      => 'SELECT',
				'default'       => 'confirm',
				'value'         => array(
					'closed' => 'post_closed_txt',
					'moderated' => 'post_moderated_txt',
					'free' => 'post_free_txt'),
			),
		)
	);

	$form["tabs"]['digest'] = array (
		'title'  => "Digest",
		'width'  => 100,
		'template'  => "templates/mail_mailinglist_digest.htm",
		'fields'  => array (
			//#################################
			// Begin Datatable fields
			//#################################
			'digesttext' => array (
				'datatype' => 'VARCHAR',
				'formtype' => 'CHECKBOX',
				'default' => 'n',
				'value'  => array(0 => 'n', 1 => 'y')
			),
			'digestsub' => array (
				'datatype' => 'VARCHAR',
				'formtype' => 'CHECKBOX',
				'default' => 'n',
				'value'  => array(0 => 'n', 1 => 'y')
			),
			'digestinterval' => array (
			'datatype' => 'VARCHAR',
			'formtype' => 'TEXT',
			'default' => '7',
			'validators' => array(0 => array('type' => 'ISINT'),
				array('type'=>'RANGE', 'range'=>'1:60')
			),
			'value' => '',
        'width' => '15'
		),
			'digestmaxmails' => array (
			'datatype' => 'VARCHAR',
			'formtype' => 'TEXT',
			'default' => '50',
			'validators' => array(0 => array('type' => 'ISINT'),
				array('type'=>'RANGE', 'range'=>'10:100')
			),
			'value' => '',
        'width' => '15'
		),
		)
	);

}
?>
