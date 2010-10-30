<?php defined('SYSPATH') or die('No direct script access.');

return array(

	/**
	 * The cache directory that ImagePlus can use. Don't worry about creating it, ImagePlus can do that and will attempt to chmod it too.
	 */
	'cache_dir'	=> 'imageplus',
	
	
	/**
	 * Whether or not to cache the images. Strongly, strongly advise keeping this turned on!
	 */
	'cache_images'	=> true,
	
	/**
	 * The default quality setting, min = 1, max = 100
	 */
	'default_quality'	=> 70,
	
	/**
	 * Route path, where the path for ImagePlus should point (default is thumb, so url's look like /thumb/{src})
	 */
	'route_path'	=> 'thumb',
	
	
);