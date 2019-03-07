<?php
namespace Filerepo\Lib;

class ImageEdit
{
	static function image2jpegstring($im, $quality = 90)
	{
		if (!$im) return false;
		ob_start();
		imageinterlace($im, 1);
		imagejpeg($im, NULL, $quality);
		$data = ob_get_contents();
		ob_end_clean();

		return $data;
	}

	static function image2pngstring($im)
	{
		if (!$im) return false;
		ob_start();
		imagepng($im, NULL);
		$data = ob_get_contents();
		ob_end_clean();
		return $data;
	}

	static function image2imagewebpstring($im)
	{
		if (!$im) return false;
		ob_start();
		imagewebp($im, NULL);
		$data = ob_get_contents();
		ob_end_clean();
		return $data;
	}

	static function image_resize($img, $width, $height, $crop = false, $transpantarent = false)
	{
		if (!$img) return false;
		$w = imagesx($img);
		$h = imagesy($img);
		if ($width == $w && $height == $h) return $img;
		if ($w < $width or $h < $height) return $img;
		// resize
		if ($crop) {
			$ratio = max($width / $w, $height / $h);
			$h = $height / $ratio;
			$x = ($w - $width / $ratio) / 2;
			$w = $width / $ratio;
		} else {
			$ratio = min($width / $w, $height / $h);
			$width = $w * $ratio;
			$height = $h * $ratio;
			$x = 0;
		}

		$new = imagecreatetruecolor($width, $height);

		// preserve transparency
		if ($transpantarent) {
			imagecolortransparent($new, imagecolorallocatealpha($new, 0, 0, 0, 127));
			imagealphablending($new, false);
			imagesavealpha($new, true);
		}

		imagecopyresampled($new, $img, 0, 0, $x, 0, $width, $height, $w, $h);
		return $new;
	}

	static function makeTrueColor($source)
	{
		if (imageistruecolor($source)) {
			return $source;
		}
		$width = imagesx($source);
		$height = imagesy($source);
		$target = imagecreatetruecolor($width, $height);
		imagecopy($target, $source, 0, 0, 0, 0, $width, $height);
		return $target;
	}

	static function addWatermark($image)
	{
		$image = self::makeTrueColor($image);
		$w = imagesx($image);
		$h = imagesy($image);
		$watermark = imagecreatefrompng(WEBROOT_DIR . \Cake\Core\Configure::readOrFail('watermark_path'));
		$watermark_width = imagesx($watermark);
		$watermark_height = imagesy($watermark);
		imagealphablending($image, true);
		imagesavealpha($image, true);
		imagecopy($image, $watermark, 10, (($h - $watermark_height)) - 10, 0, 0, $watermark_width, $watermark_height);
		imagedestroy($watermark);
		return $image;
	}
}