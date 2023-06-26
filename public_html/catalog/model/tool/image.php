<?php
class ModelToolImage extends Model {

	private $setting_data = array();

	private $valid_extensions_from = array(
		'jpg' => true,
		'jpeg' => true,
		'png' => true
	);

	public function is_valid_extension_from($filename) {
		$extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
		return isset($this->valid_extensions_from[$extension]);
	}

	public function get_new_extension($filename) {
		$extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
		if (isset($this->valid_extensions_from[$extension])) {
			$extension_info = $this->config->get('config_watermark_extension');
			switch ($extension_info) {
				case 1:
					return "jpg";
					break;
				case 2:
					return "png";
					break;
				case 3:
					return "webp";
					break;
				default:
					return $extension;
					break;
			}
		} else {
			return $extension;
		}
	}

	public function get_new_extension_filename($filename) {
		$extension = $this->get_new_extension($filename);
		return utf8_substr($filename, 0, utf8_strrpos($filename, '.')) . "." . $extension;
	}

	public function watermark_get_setting($store_id = 0) {
		if (empty($this->setting_data)) {
			$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "setting WHERE store_id = '" . (int)$store_id . "' AND `code` = 'config' AND `key` LIKE 'config_watermark%'");

			foreach ($query->rows as $result) {
				$this->setting_data[$result['key']] = $result['value'];
			}
		}
		return $this->setting_data;
	}

	public function save_image($filename_old, $filename_new = false) {
		if (!$filename_new) {
			$filename_new = $this->get_cache_filename($filename_old);
		}

		$extension = strtolower(pathinfo($filename_new, PATHINFO_EXTENSION));

		$image_old = $filename_old;
		$image_new = $filename_new;

		$path = '';

		$directories = explode('/', dirname($image_new));

		foreach ($directories as $directory) {
			$path = $path . '/' . $directory;

			if (!is_dir(DIR_IMAGE . $path)) {
				@mkdir(DIR_IMAGE . $path, 0777);
			}
		}

		$image = new Image(DIR_IMAGE . $image_old, $extension);
		$image->save(DIR_IMAGE . $image_new, $this->config->get('config_watermark_progressive_jpeg'));
	}
	
	public function resize($filename, $width, $height, $mode = 0, $enable_watermark = true) {
		$no_placeholder = true;
		if (!is_file(DIR_IMAGE . $filename) || substr(str_replace('\\', '/', realpath(DIR_IMAGE . $filename)), 0, strlen(DIR_IMAGE)) != str_replace('\\', '/', DIR_IMAGE) || !filesize(DIR_IMAGE . $filename)) {
			$filename = "placeholder.png";
			$no_placeholder = false;
		}

		$extension = $this->get_new_extension($filename);

		$image_old = $filename;
		$image_new = 'cache/' . utf8_substr($filename, 0, utf8_strrpos($filename, '.')) . '-' . (int)$width . 'x' . (int)$height . '.' . $extension;

		if (!is_file(DIR_IMAGE . $image_new) || (filemtime(DIR_IMAGE . $image_old) > filemtime(DIR_IMAGE . $image_new))) {
			list($width_orig, $height_orig, $image_type) = getimagesize(DIR_IMAGE . $image_old);
				 
			if (!in_array($image_type, array(IMAGETYPE_PNG, IMAGETYPE_JPEG))) { 
				if ($this->request->server['HTTPS']) {
					return $this->config->get('config_ssl') . 'image/' . $image_old;
				} else {
					return $this->config->get('config_url') . 'image/' . $image_old;
				}
			}

			$enable_watermark = $this->config->get('config_watermark_status');

			if ($enable_watermark && $no_placeholder) {
				$watermark = new Image(DIR_IMAGE . $this->config->get('config_watermark_image'));
				imagealphablending($watermark->getImage(), false);
				imagesavealpha($watermark->getImage(), true);
				imagefilter($watermark->getImage(), IMG_FILTER_COLORIZE, 0, 0, 0, (int) 127 * (1 - $this->config->get('config_watermark_opacity')));
				$pos_x = $this->config->get('config_watermark_pos_x');
				$pos_y = $this->config->get('config_watermark_pos_y');
			}
						
			$path = '';

			$directories = explode('/', dirname($image_new));

			foreach ($directories as $directory) {
				$path = $path . '/' . $directory;

				if (!is_dir(DIR_IMAGE . $path)) {
					@mkdir(DIR_IMAGE . $path, 0777);
				}
			}

			$image = new Image(DIR_IMAGE . $image_old, $extension);
			$image->resize($width, $height, '', $mode);
			if ($enable_watermark && $no_placeholder) {
				if ($this->config->get('config_watermark_resize_first')) {
					$image->watermark_resized($watermark, $this->config->get('config_watermark_pos_y_center'), abs($pos_x), $pos_y);
				} else {
					$image->watermark($watermark, $this->watermark_get_setting());
				}
			}
			$image->save(DIR_IMAGE . $image_new, $this->config->get('config_watermark_progressive_jpeg'));
		}
		
		$image_new = str_replace(' ', '%20', $image_new);  // fix bug when attach image on email (gmail.com). it is automatic changing space " " to +
		
		return $this->get_image_address($image_new);
	}

	public function get_cache_filename($filename) {
		if (!$this->is_valid_extension_from($filename)) {
			return $filename;
		}

		return 'cache/' . $this->get_new_extension_filename($filename);
	}

	public function check_image($filename) {
		return !(!is_file(DIR_IMAGE . $filename) || substr(str_replace('\\', '/', realpath(DIR_IMAGE . $filename)), 0, strlen(DIR_IMAGE)) != str_replace('\\', '/', DIR_IMAGE) || !filesize(DIR_IMAGE . $filename));
	}

	public function check_cached_image($filename) {
		$filename = $this->get_cache_filename($filename);
		return $this->check_image($filename);
	}

	public function get_image_address($filename) {
		if ($this->request->server['HTTPS']) {
			return $this->config->get('config_ssl') . 'image/' . $filename;
		} else {
			return $this->config->get('config_url') . 'image/' . $filename;
		}
	}

	public function compare_extensions($filename1, $filename2) {
		return (strtolower(pathinfo($filename1, PATHINFO_EXTENSION)) === strtolower(pathinfo($filename2, PATHINFO_EXTENSION)));
	}

	public function get_image($filename, $original_image = false) {
		if (!$this->check_image($filename)) {
			return;
		}

		if ($original_image || !$this->is_valid_extension_from($filename)) {
			return $this->get_image_address($filename);
		}

		// Get new cached filename
		$cached_filename = $this->get_cache_filename($filename);

		if ($this->compare_extensions($filename, $cached_filename)) {
			return $this->get_image_address($filename);
		}

		// Check if it exists, save if not
		if (!$this->check_image($cached_filename)) {
			$this->save_image($filename, $cached_filename);
		}

		// Return new address of image
		return $this->get_image_address($cached_filename);
	}
}
