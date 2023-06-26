<?php
/**
 * @package		OpenCart
 * @author		Daniel Kerr
 * @copyright	Copyright (c) 2005 - 2017, OpenCart, Ltd. (https://www.opencart.com/)
 * @license		https://opensource.org/licenses/GPL-3.0
 * @link		https://www.opencart.com
*/

/**
* Image class
*/
class Image {
	private $file;
	private $image;
	private $width;
	private $height;
	private $bits;
	private $mime;
	private $new_extension;

	/**
	 * Constructor
	 *
	 * @param	string	$file
	 *
 	*/
	public function __construct($file, $new_extension = NULL) {
		if (!extension_loaded('gd')) {
			exit('Error: PHP GD is not installed!');
		}
		
		if (file_exists($file)) {
			$this->file = $file;

			$info = getimagesize($file);

			$this->width  = $info[0];
			$this->height = $info[1];
			$this->bits = isset($info['bits']) ? $info['bits'] : '';
			$this->mime = isset($info['mime']) ? $info['mime'] : '';

			if ($this->mime == 'image/gif') {
				$this->image = imagecreatefromgif($file);
			} elseif ($this->mime == 'image/png') {
				$this->image = imagecreatefrompng($file);
			} elseif ($this->mime == 'image/jpeg') {
				$this->image = imagecreatefromjpeg($file);
			}

			if (isset($new_extension)) {
				$this->new_extension = $new_extension;
			}
		} else {
			exit('Error: Could not load image ' . $file . '!');
		}
	}
	
	/**
     * 
	 * 
	 * @return	string
     */
	public function getFile() {
		return $this->file;
	}

	/**
     * 
	 * 
	 * @return	array
     */
	public function getImage() {
		return $this->image;
	}
	
	/**
     * 
	 * 
	 * @return	string
     */
	public function getWidth() {
		return $this->width;
	}
	
	/**
     * 
	 * 
	 * @return	string
     */
	public function getHeight() {
		return $this->height;
	}
	
	/**
     * 
	 * 
	 * @return	string
     */
	public function getBits() {
		return $this->bits;
	}
	
	/**
     * 
	 * 
	 * @return	string
     */
	public function getMime() {
		return $this->mime;
	}
	
	/**
     * 
     *
     * @param	string	$file
	 * @param	int		$quality
     */
	public function save($file, $progressive_jpeg = true, $quality = 85) {
		$info = pathinfo($file);

		$extension = strtolower($info['extension']);

		if (is_resource($this->image)) {
			if ($extension == 'jpeg' || $extension == 'jpg') {
				//imagejpeg($this->image, $file, $quality);

				//fix for black background when converting png to jpg
				$width = imagesx($this->image);
				$height = imagesy($this->image);
				$output = imagecreatetruecolor($width, $height);
				$white = imagecolorallocate($output, 255, 255, 255);
				imagefilledrectangle($output, 0, 0, $width, $height, $white);
				imagecopy($output, $this->image, 0, 0, 0, 0, $width, $height);
				//progressive jpeg
				if ($progressive_jpeg) {
					imageinterlace($output, true);
				}
				imagejpeg($output, $file, $quality);
				imagedestroy($output);
			} elseif ($extension == 'png') {
				imagepng($this->image, $file);
			} elseif ($extension == 'gif') {
				imagegif($this->image, $file);
			} elseif ($extension == 'webp') {
				imagewebp($this->image, $file, $quality);
			}

			imagedestroy($this->image);
		}
	}
	
	/**
     * 
     *
     * @param	int	$width
	 * @param	int	$height
	 * @param	string	$default
     */
	public function resize($width = 0, $height = 0, $default = '', $mode = 0) {
		if (!$this->width || !$this->height) {
			return;
		}

		$xpos = 0;
		$ypos = 0;
		$scale = 1;

		$scale_w = $width / $this->width;
		$scale_h = $height / $this->height;

		if ($default == 'w') {
			$scale = $scale_w;
		} elseif ($default == 'h') {
			$scale = $scale_h;
		} else {
			$scale = min($scale_w, $scale_h);
		}

		if ($mode == 2) {
			$scale = max($scale_w, $scale_h);
		}

		if ($scale == 1 && $scale_h == $scale_w && $this->mime != 'image/png') {
			return;
		}

		$new_width = (int)($this->width * $scale);
		$new_height = (int)($this->height * $scale);
		$xpos = (int)(($width - $new_width) / 2);
		$ypos = (int)(($height - $new_height) / 2);

		$image_old = $this->image;
		switch ($mode) {
			case 1: $this->image = imagecreatetruecolor($new_width, $new_height); break;
			default: $this->image = imagecreatetruecolor($width, $height); break;
		}

		if ($this->mime == 'image/png') {
			imagealphablending($this->image, false);
			imagesavealpha($this->image, true);
			$background = imagecolorallocatealpha($this->image, 255, 255, 255, 127);
			imagecolortransparent($this->image, $background);
		} else {
			$background = imagecolorallocate($this->image, 255, 255, 255);
		}

		switch ($mode) {
			case 1: 
				imagefilledrectangle($this->image, 0, 0, $new_width, $new_height, $background);
				imagecopyresampled($this->image, $image_old, 0, 0, 0, 0, $new_width, $new_height, $this->width, $this->height);
				$this->width = $new_width;
				$this->height = $new_height;
				break;
			default: 
				imagefilledrectangle($this->image, 0, 0, $width, $height, $background);
				imagecopyresampled($this->image, $image_old, $xpos, $ypos, 0, 0, $new_width, $new_height, $this->width, $this->height);
				$this->width = $width;
				$this->height = $height;
				break;
		}

		imagedestroy($image_old);
	}
	
	/**
     * 
     *
     * @param	string	$watermark
	 * @param	string	$position
     */
	public function watermark($watermark, $settings = array(), $keep_watermark = false) {
		if (isset($settings['config_watermark_pos_x'])) {
			if ($settings['config_watermark_pos_x_center']) {
				$watermark_pos_x = intval(($this->width - $watermark->getWidth()) / 2) + $settings['config_watermark_pos_x'];
			} else {
				$watermark_pos_x = $settings['config_watermark_pos_x'] >= 0 ? $settings['config_watermark_pos_x'] : ($this->width - $watermark->getWidth() + $settings['config_watermark_pos_x']);
			}
		} else {
			$watermark_pos_x = 0;
		}

		if (isset($settings['config_watermark_pos_y'])) {
			if ($settings['config_watermark_pos_y_center']) {
				$watermark_pos_y = intval(($this->height - $watermark->getHeight()) / 2) + $settings['config_watermark_pos_y'];
			} else {
				$watermark_pos_y = $settings['config_watermark_pos_y'] >= 0 ? $settings['config_watermark_pos_y'] : ($this->height - $watermark->getHeight() + $settings['config_watermark_pos_y']);
			}
		} else {
			$watermark_pos_y = 0;
		}

		
		imagealphablending( $this->image, true );
		imagesavealpha( $this->image, true );
		imagecopy($this->image, $watermark->getImage(), $watermark_pos_x, $watermark_pos_y, 0, 0, $watermark->getWidth(), $watermark->getHeight());

		if (!$keep_watermark) {
			imagedestroy($watermark->getImage());
		}
	}

	public function watermark_resized($watermark, $middle = 1, $pos_x = 0, $pos_y = 0, $no_resize = false, $keep_watermark = false) {
		if (!$no_resize) {
			$watermark->resize($this->width - ($pos_x * 2), $this->height, NULL, 1);
		}
		$watermark_pos_x = $pos_x;
		if ($middle) {
			$watermark_pos_y = intval(($this->height - $watermark->getHeight()) / 2) + $pos_y;
		} else {
			$watermark_pos_y = $pos_y >= 0 ? $pos_y : ($this->height - $watermark->getHeight() + $pos_y);
		}
		
		imagealphablending( $this->image, true );
		imagesavealpha( $this->image, true );
		imagecopy($this->image, $watermark->getImage(), $watermark_pos_x, $watermark_pos_y, 0, 0, $watermark->getWidth(), $watermark->getHeight());

		if (!$keep_watermark) {
			imagedestroy($watermark->getImage());
		}
	}
	
	/**
     * 
     *
     * @param	int		$top_x
	 * @param	int		$top_y
	 * @param	int		$bottom_x
	 * @param	int		$bottom_y
     */
	public function crop($top_x, $top_y, $bottom_x, $bottom_y) {
		$image_old = $this->image;
		$this->image = imagecreatetruecolor($bottom_x - $top_x, $bottom_y - $top_y);

		imagecopy($this->image, $image_old, 0, 0, $top_x, $top_y, $this->width, $this->height);
		imagedestroy($image_old);

		$this->width = $bottom_x - $top_x;
		$this->height = $bottom_y - $top_y;
	}
	
	/**
     * 
     *
     * @param	int		$degree
	 * @param	string	$color
     */
	public function rotate($degree, $color = 'FFFFFF') {
		$rgb = $this->html2rgb($color);

		$this->image = imagerotate($this->image, $degree, imagecolorallocate($this->image, $rgb[0], $rgb[1], $rgb[2]));

		$this->width = imagesx($this->image);
		$this->height = imagesy($this->image);
	}
	
	/**
     * 
     *
     */
	public function filter() {
        $args = array($this->image);
        $args += func_get_args();

        call_user_func_array('imagefilter', $args);
	}
	
	/**
     * 
     *
     * @param	string	$text
	 * @param	int		$x
	 * @param	int		$y 
	 * @param	int		$size
	 * @param	string	$color
     */
	private function text($text, $x = 0, $y = 0, $size = 5, $color = '000000') {
		$rgb = $this->html2rgb($color);

		imagestring($this->image, $size, $x, $y, $text, imagecolorallocate($this->image, $rgb[0], $rgb[1], $rgb[2]));
	}
	
	/**
     * 
     *
     * @param	object	$merge
	 * @param	object	$x
	 * @param	object	$y
	 * @param	object	$opacity
     */
	private function merge($merge, $x = 0, $y = 0, $opacity = 100) {
		imagecopymerge($this->image, $merge->getImage(), $x, $y, 0, 0, $merge->getWidth(), $merge->getHeight(), $opacity);
	}
	
	/**
     * 
     *
     * @param	string	$color
	 * 
	 * @return	array
     */
	private function html2rgb($color) {
		if ($color[0] == '#') {
			$color = substr($color, 1);
		}

		if (strlen($color) == 6) {
			list($r, $g, $b) = array($color[0] . $color[1], $color[2] . $color[3], $color[4] . $color[5]);
		} elseif (strlen($color) == 3) {
			list($r, $g, $b) = array($color[0] . $color[0], $color[1] . $color[1], $color[2] . $color[2]);
		} else {
			return false;
		}

		$r = hexdec($r);
		$g = hexdec($g);
		$b = hexdec($b);

		return array($r, $g, $b);
	}
}