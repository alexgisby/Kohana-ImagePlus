<?php defined('SYSPATH') or die('No direct script access.');

/**
 * ImagePlus HTML helper. Extends the Kohana_HTML class with a function to generate thumbnails.
 *
 * @package 	ImagePlus
 * @category  	Helpers
 * @author 		Alex Gisby
 */

class ImagePlus_HTML extends Kohana_HTML
{
	/**
	 * Generates a URL for resizing images on the fly using ImagePlus
	 *
	 * @param 	string 	The source image path
	 * @param	array 	Dimensions to resize to
	 * @param 	array 	HTML attributes to add to the <img> tag.
	 * @return 	string
	 */
	public static function thumbnail($src, array $dimensions, array $attr = array())
	{
		$ext = ImagePlus::extension_from_filepath($src);
		$src = rtrim($src, '.' . $ext);
		
		// Append the dimensions:
		foreach($dimensions as $key => $value)
		{
			if(in_array($key, array('w', 'h', 'q')))
			{
				$src .= '.' . $key . $value;
			}
		}
		
		$src .= '.' . $ext;
		$src = url::site(Route::get('imageplus-thumbnail')->uri(array('filepath' => $src)));
		
		$tag = '<img src="' . $src . '"' . HTML::attributes($attr) . ' />';
		return $tag;
	}
}