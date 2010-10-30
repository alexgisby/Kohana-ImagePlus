<?php defined('SYSPATH') or die('No direct script access.');

/*
 * Initialises the Routes used by ImagePlus. Feel free to change.
 */

Route::set('imageplus-thumbnail', kohana::config('imageplus')->route_path . '/<filepath>', array(
		'filepath' => 	'[a-z0-9\-_./]+\.(jpe?g|png|gif)',
	))
	->defaults(array(
		'controller'	=> 'imageplus',
		'action'		=> 'thumbnail',
	));