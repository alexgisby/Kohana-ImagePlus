<?php defined('SYSPATH') or die('No direct script access.');

/**
 * ImagePlus controller, this is what you can call to do auto-thumbnailing.
 *
 * @package 	ImagePlus
 * @category  	Controllers
 * @author 		Alex Gisby
 */

class Kohana_Controller_ImagePlus extends Controller {

	/**
	 * This action creates a thumbnail, caches it and serves it up.
	 */
	public function action_thumbnail()
	{
		$filename = $this->request->param('filepath');
		
		// Work out what the image is, what we're resizing to, and then pass it off to the ImagePlus class.
		// The last three parts of the filename are always it's dimensions and quality.
		$fileparts = explode('.', $this->request->param('filepath'));
		$fileparts = array_reverse($fileparts);
		
		$ext = array_shift($fileparts);
		
		// Loop through the other parts to try and find size and quality identifiers:
		$dimensions = array();
		foreach($fileparts as $part)
		{
			if(preg_match('/(?P<quantifier>w|h|q)(?<value>[0-9]{1,4})/', $part, $matches))
			{
				$dimensions[$matches['quantifier']] = $matches['value'];
				
				// Remove this part from the filename:
				$filename = str_replace('.' . $matches[0], '', $filename);
			}
		}
		
		// Set about thumbnailing this!
		try
		{
			$image = ImagePlus::thumbnail($filename, $dimensions, 1);
			
			if(version_compare(kohana::VERSION, '3.1', '>='))
			{
				$this->response->headers('Content-Type', $image->mime);
				$this->response->headers('Cache-Control', 'max-age=' . 608400 . ', must-revalidate');
				$this->response->body($image->render(null, $dimensions['q']));
			}
			else
			{
				$this->request->headers[] 	= 'Content-Type: ' . $image->type;
				$this->request->headers[] 	= 'Cache-Control: max-age=' . 608400 . ', must-revalidate';
				$this->request->response 	= $image->render(null, $dimensions['q']);
			}
		}
		catch(Exception_ImagePlus $e)
		{
			$status = 500;
			
			switch($e->getCode())
			{
				case 404:
					$status = 404;
				break;
				
				case 500:
				case 101:
					$status = 500;
				break;
			}
			
			if(version_compare(kohana::VERSION, '3.1', '>='))
			{
				$this->response->status($status);
				$this->response->body($e->getMessage());
			}
			else
			{
				$this->request->status = $status;
				$this->request->response = $e->getMessage();
			}
		}
	}

}