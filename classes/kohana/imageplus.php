<?php defined('SYSPATH') or die('No direct script access.');

/**
 * ImagePlus is an extension to the standard Kohana Image library that deals with serving thumbnails etc.
 *
 * @package 	ImagePlus
 * @author 		Alex Gisby
 * @requires	Kohana-Image
 */

class Kohana_ImagePlus
{
	
	/**
	 * Creates a thumbnail of an image. A thumbnail is broadly described as being smaller than the source image and the process weighted as such.
	 *
	 * The dimensions array should look like this: (any value can be ommitted)
	 *
	 * 		array(
	 *			'w'		=> width value, default is null, determine automatically.
	 *			'h'		=> height value, default is null, determine automatically.
	 * 			'q'		=> quality value, default is 50. Min is 0, max is 100. Greater quality = greater filesize.
	 * 		)
	 *
	 * @param 	string 	Filename of the image to resize
	 * @param 	array 	Dimensions to resize to. (Reference, feel free to use later)
	 * @param 	bool 	Whether to maintain aspect ratio or not.
	 * @param 	bool 	Upscale or not.
	 * @return 	Image	The resulting thumbnail, instance of the Kohana Image class.
	 */
	public static function thumbnail($filename, array &$dimensions, $maintain_aspect = true, $upscale = false)
	{
		// Validate the image:
		ImagePlus::validate_image($filename);
		
		if(kohana::config('imageplus')->cache_images)
		{
			// Check we have the Cache.
			ImagePlus::cache_checkdir();
		}
		
		list($src_width, $src_height, $src_type, $src_attr) = getimagesize($filename);
		
		// Sort out the target dimensions;
		if(!array_key_exists('w', $dimensions)) 	$dimensions['w'] = null;
		if(!array_key_exists('h', $dimensions))		$dimensions['h'] = null;
		if(!array_key_exists('q', $dimensions)) 	$dimensions['q'] = kohana::config('imageplus')->default_quality;
		
		// Load up the image ready for manipulation.
		$image = Image::factory($filename);
		
		// Now we check the cache to see if this image already exists:
		if(kohana::config('imageplus')->cache_images && ImagePlus::cache_exists($image, $dimensions))
		{
			$image = ImagePlus::cache_read($image, $dimensions);
			$dimensions['q'] = 100;	// This seems odd, but considering we have it saved as a quality already, the output needs to be full quality.
		}
		else
		{
			// Check if we should upscale or not:
			if(!$upscale)
			{
				if(($dimensions['w'] == null && $dimensions['h'] == null) || ($dimensions['w'] != null && $dimensions['w'] > $src_width && $dimensions['h'] != null && $dimensions['h'] > $src_height))
				{
					// No need to upscale. Return the image as-is.
					return $image;
				}
			}
		
			$image->resize($dimensions['w'], $dimensions['h']);
			if(kohana::config('imageplus')->cache_images)
			{
				$image = ImagePlus::cache_image($image, $dimensions);
			}
		}
		
		return $image;
	}
	
	
	/**
	 * Validates an image, checks that it exists, is of the correct type and such.
	 *
	 * @param 	string 	Path to the file
	 * @throws 	Exception_ImagePlus
	 */
	public static function validate_image($image)
	{
		// Firstly, see if this exists:
		if(!file_exists($image))
		{
			throw new Exception_ImagePlus('File: ' . $image . ' not found.', 404);
		}
		
		// Use getimagesize to check it is who it says it is:
		$info = getimagesize($image);
		if($info[0] == 0 || $info[1] == 0)
		{
			throw new Exception_ImagePlus('Image appears to be of zero size: ' . $image, 500);
		}
		
		if(!in_array($info['mime'], array('image/jpeg', 'image/png', 'image/gif')))
		{
			throw new Exception_ImagePlus('Image type not supported: ' . $info['type'] . ' ' . $image, 500);
		}
		
		$ext = ImagePlus::extension_from_filepath($image);
		if(($ext == 'jpg' || $ext == 'jpeg') && $info['mime'] != 'image/jpeg')
		{
			throw new Exception_ImagePlus('Image mime-type does not agree with extension: ' . $image, 500);
		}
		
		if($ext == 'png' && $info['mime'] != 'image/png')
		{
			throw new Exception_ImagePlus('Image mime-type does not agree with extension: ' . $image, 500);
		}
		
		if($ext == 'gif' && $info['mime'] != 'image/gif')
		{
			throw new Exception_ImagePlus('Image mime-type does not agree with extension: ' . $image, 500);
		}
	}
	
	
	/**
	 * Checks that the cache is available and writeable.
	 *
	 * @return 	bool
	 */
	protected static function cache_checkdir()
	{
		$cache_dir = kohana::$cache_dir . '/' . kohana::config('imageplus')->cache_dir;
		
		// Try and remake / repermission the directory:
		if(!file_exists($cache_dir) || !is_writable($cache_dir))
		{
			@mkdir($cache_dir);
			@chmod($cache_dir, 0777);
			
			// Make sure that it worked:
			if(!file_exists($cache_dir))	throw new Exception_ImagePlus('Could not create cache directory: ' . $cache_dir);
			if(!is_writable($cache_dir))	throw new Exception_ImagePlus('Cache directory not writable: ' . $cache_dir);
		}
		
		return true;
	}
	
	
	/**
	 * Sees if there is an image in the cache that is this file and dimensions etc
	 *
	 * @param 	Image 	The original image object
	 * @param 	array 	The dimensions of the image.
	 * @return 	bool
	 */
	protected static function cache_exists(Image $image, array $dimensions)
	{
		$token 	= sha1($image->file . $dimensions['w'] . $dimensions['h'] . $dimensions['q']);
		$subdir = kohana::$cache_dir . '/' . kohana::config('imageplus')->cache_dir . '/' . substr($token, 0, 4);
		
		$ext = ImagePlus::extension_from_filepath($image->file);
		$cache_filename = $subdir . '/' . $token . '.' . $ext;
		
		if(file_exists($cache_filename))
		{
			// Ok, there is a cache file, now just to be very sneaky, check the modified times of the image and the cache:
			$mtime_file 	= filemtime($image->file);
			$mtime_cache 	= filemtime($cache_filename);
			
			if($mtime_cache > $mtime_file)
			{
				return true;
			}
		}
		
		return false;
	}
	
	/**
	 * Reads an image from the cache.
	 *
	 * @param 	Image 	The source image
	 * @param 	array 	The dimensions to use
	 * @return 	Image
	 */
	protected static function cache_read(Image $image, array $dimensions)
	{
		$token 	= sha1($image->file . $dimensions['w'] . $dimensions['h'] . $dimensions['q']);
		$subdir = kohana::$cache_dir . '/' . kohana::config('imageplus')->cache_dir . '/' . substr($token, 0, 4);
		
		$ext = ImagePlus::extension_from_filepath($image->file);
		$cache_filename = $subdir . '/' . $token . '.' . $ext;
		
		return Image::factory($cache_filename);
	}
	
	
	/**
	 * Caches an image into the setup cache directory
	 *
	 * @param 	Image 	Image to cache (not pre-saved)
	 * @param 	array 	The dimensions this image will be.
	 * @return 	Image
	 */
	protected static function cache_image(Image $image, array $dimensions)
	{
		// Generate a token for this:
		$token 	= sha1($image->file . $dimensions['w'] . $dimensions['h'] . $dimensions['q']);
				
		// Grab a few letters from the front to make as the directory:
		$subdir = kohana::$cache_dir . '/' . kohana::config('imageplus')->cache_dir . '/' . substr($token, 0, 4);
		
		// Make that cache dir:
		@mkdir($subdir);
		@chmod($subdir, 0777);
		
		if(!file_exists($subdir))	throw new Exception_ImagePlus('Could not create cache directory: ' . $subdir);
		if(!is_writable($subdir))	throw new Exception_ImagePlus('Cache directory not writable: ' . $subdir);
		
		// Actually write the image to the filename:
		$ext = ImagePlus::extension_from_filepath($image->file);
		$cache_filename = $subdir . '/' . $token . '.' . $ext;
		
		$image->save($cache_filename, $dimensions['q']);
		
		return $image;
	}
	
	
	/**
	 * Returns the extension from the filepath.
	 *
	 * @param 	string 	The path
	 * @return 	string	The extension
	 */
	public static function extension_from_filepath($path)
	{
		$path = rtrim($path, '.');
		$parts = explode('.', $path);
		$parts = array_reverse($parts);
		
		if(count($parts) >= 2)
		{
			return $parts[0];
		}
		else
		{
			return '';
		}
	}
	
}