# Image Plus

ImagePlus handles some basic tasks like serving up thumbnails from a URL and the like.

## Usage

	html::thumbnail(src, array('w' => xx, 'h' => xx, 'q' => xx), attributes array)
	
The classes are all documented so fire up the userguide module and take a peek.

If you extend the html class in your application, be sure to make it inherit from imageplus_html so you get the thumbnail methods added to the HTML helper.
