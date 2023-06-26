<?php
// *	@source		See SOURCE.txt for source and other copyright.
// *	@license	GNU General Public License version 3; see LICENSE.txt

class ControllerSettingEcos extends Controller {
	
	public function update_tables() {
		$this->load->model('setting/ecos');
		$this->model_setting_ecos->update_tables();
	}

	public function reset_watermark() {
		$this->load->model('setting/ecos');
		$this->model_setting_ecos->watermark_install();
	}

	public function deldir($dirname){
		if(file_exists($dirname)) {
			if(is_dir($dirname)){
				$dir=opendir($dirname);
				while(($filename=readdir($dir)) !== false){
					if($filename!="." && $filename!=".."){
						$file=$dirname."/".$filename;
						$this->deldir($file); 
					}
				}
				closedir($dir);
				rmdir($dirname);
			} else {
				@unlink($dirname);
			}
		}
	}

	public function reset_imgcache() {
		$imgfiles = glob(DIR_IMAGE . 'cache/*');
			
		if (!empty($imgfiles)) {
			foreach($imgfiles as $imgfile){
				$this->deldir($imgfile);
			}
		}

		//Image Caching
		$query = $this->db->query("SELECT `image` FROM " . DB_PREFIX . "product WHERE `image` <> '' UNION ALL SELECT `image` FROM " . DB_PREFIX . "product_image WHERE `image` <> '' GROUP BY `image`");
		$images = array_column($query->rows, 'image');

		$resizes = array(
            'popup' => array(
                'width' => $this->config->get('theme_' . $this->config->get('config_theme') . '_image_popup_width'),
                'height' => $this->config->get('theme_' . $this->config->get('config_theme') . '_image_popup_height')
            ),
            'thumb' => array(
                'width' => $this->config->get('theme_' . $this->config->get('config_theme') . '_image_thumb_width'),
                'height' => $this->config->get('theme_' . $this->config->get('config_theme') . '_image_thumb_height')
            ),
            'additional' => array(
                'width' => $this->config->get('theme_' . $this->config->get('config_theme') . '_image_additional_width'),
                'height' => $this->config->get('theme_' . $this->config->get('config_theme') . '_image_additional_height')
            ),
            // 'related' => array(
            //     'width' => $this->config->get('theme_' . $this->config->get('config_theme') . '_image_related_width'),
            //     'height' => $this->config->get('theme_' . $this->config->get('config_theme') . '_image_related_height')
            // ),
            // 'option_value' => array(
            //     'width' => 50,
            //     'height' => 50
            // )
        );

		$this->load->model('setting/ecos');
        $enable_watermark = $this->config->get('config_watermark_status');

        if ($enable_watermark) {
			$watermark = new Image(DIR_IMAGE . $this->config->get('config_watermark_image'));
			imagealphablending($watermark->getImage(), false);
			imagesavealpha($watermark->getImage(), true);
			imagefilter($watermark->getImage(), IMG_FILTER_COLORIZE, 0, 0, 0, (int) 127 * (1 - $this->config->get('config_watermark_opacity')));
			$watermark_settings = $this->model_setting_ecos->watermark_get_setting();
			$resize_first = $watermark_settings['config_watermark_resize_first'];
			$pos_x = $watermark_settings['config_watermark_pos_x'];
			$pos_y = $watermark_settings['config_watermark_pos_y'];
		}

        foreach ($resizes as $resize) {
        	if ($enable_watermark) {
        		if ($resize_first) {
        			$watermark->resize($resize['width'] - ($pos_x * 2), $resize['height'], NULL, 1);
        		}
        	}
            foreach ($images as $image) {
                $this->resize($image, $resize['width'], $resize['height'], $watermark, $watermark_settings);
            }
        }
    }

    public function resize($filename, $width, $height, $watermark, $watermark_settings) {
        if (!is_file(DIR_IMAGE . $filename) || substr(str_replace('\\', '/', realpath(DIR_IMAGE . $filename)), 0, strlen(DIR_IMAGE)) != str_replace('\\', '/', DIR_IMAGE)) {
			return;
		}

		$extension_info = $this->config->get('config_watermark_extension');
		switch ($extension_info) {
			case 1:
				$extension = "jpg";
				break;
			case 2:
				$extension = "png";
				break;
			default:
				$extension = pathinfo($filename, PATHINFO_EXTENSION);
				break;
		}

		//$extension = pathinfo($filename, PATHINFO_EXTENSION);

		$image_old = $filename;
		$image_new = 'cache/' . utf8_substr($filename, 0, utf8_strrpos($filename, '.')) . '-' . (int)$width . 'x' . (int)$height . '.' . $extension;

		if (!is_file(DIR_IMAGE . $image_new) || (filemtime(DIR_IMAGE . $image_old) > filemtime(DIR_IMAGE . $image_new))) {
			list($width_orig, $height_orig, $image_type) = getimagesize(DIR_IMAGE . $image_old);
				 
			if (!in_array($image_type, array(IMAGETYPE_PNG, IMAGETYPE_JPEG, IMAGETYPE_GIF))) { 
				return DIR_IMAGE . $image_old;
			}
						
			$path = '';

			$directories = explode('/', dirname($image_new));

			foreach ($directories as $directory) {
				$path = $path . '/' . $directory;

				if (!is_dir(DIR_IMAGE . $path)) {
					@mkdir(DIR_IMAGE . $path, 0777);
				}
			}

			if ($width_orig != $width || $height_orig != $height) {
				$image = new Image(DIR_IMAGE . $image_old, $extension);
				$image->resize($width, $height);
				if ($watermark_settings['config_watermark_status']) {
					if ($watermark_settings['config_watermark_resize_first']) {
						$image->watermark_resized($watermark, $watermark_settings['config_watermark_pos_y_center'], abs($watermark_settings['config_watermark_pos_x']), $watermark_settings['config_watermark_pos_y'], true, true);
					} else {
						$image->watermark($watermark, $watermark_settings, true);
					}
				}
				$image->save(DIR_IMAGE . $image_new);
			} else {
				copy(DIR_IMAGE . $image_old, DIR_IMAGE . $image_new);
			}
		}
    }
}
