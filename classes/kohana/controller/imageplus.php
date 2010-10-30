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
			
			$this->request->status 		= 200;
			$this->request->headers[] 	= 'Content-Type: ' . $image->type;
			$this->request->response 	= $image->render(null, $dimensions['q']);
		}
		catch(Exception_ImagePlus $e)
		{
			switch($e->getCode())
			{
				case 404:
					$this->request->status = 404;
					$this->request->response = $e->getMessage();
				break;
				
				case 500:
				case 101:
					$this->request->status = 500;
					$this->request->response = $e->getMessage();
				break;
			}
		}
	}

}