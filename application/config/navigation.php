<?php

$config['navigation_header'] = array(
	'*' => array(
		'FH-Complete' => site_url(''),
		'Vilesci' => base_url('vilesci'),
		'CIS' => CIS_ROOT
	)
);

$config['navigation_menu'] = array();

$config['navigation_menu']['Vilesci/index'] = array(
	'Dashboard' => array(
		'link' => '#',
		'description' => 'Dashboard',
		'icon' => 'dashboard'
	),
	'Lehre' => array(
		'link' => '#',
		'icon' => 'graduation-cap',
		'description' => 'Lehre',
		'expand' => true,
		'children'=> array(
			'CIS' => array(
				'link' => CIS_ROOT,
				'icon' => '',
				'description' => 'CIS',
				'expand' => true
			),
			'Infocenter' => array(
				'link' => site_url('system/infocenter/InfoCenter'),
				'icon' => 'info',
				'description' => 'Infocenter',
				'expand' => true
			),
		)
	),
	'Administration' => array(
		'link' => '#',
		'icon' => 'gear',
		'description' => 'Administration',
		'expand' => false,
		'children'=> array(
			'Vilesci' => array(
				'link' => base_url('vilesci'),
				'icon' => '',
				'description' => 'Vilesci',
				'expand' => true
			),
			'Extensions' => array(
				'link' => site_url('system/extensions/Manager'),
				'icon' => 'cubes',
				'description' => 'Extensions Manager',
				'expand' => true
			)
		)
	)
);


$config['navigation_menu']['system/infocenter/InfoCenter/index'] = array(
	'freigegeben' => array(
		'link' => site_url('system/infocenter/InfoCenter/freigegeben'),
		'description' => 'Freigegeben',
		'icon' => 'thumbs-up'
	)
);

$config['navigation_menu']['system/infocenter/InfoCenter/freigegeben'] = array(
	'back' => array(
		'link' => site_url('system/infocenter/InfoCenter/index'),
		'description' => 'Home',
		'icon' => 'angle-left'
	)
);
