<?php
/**
 * Provides functions to manage images thumbnails generation.
 * 
 * @author Enisseo
 */

/**
 * @var string IMAGE_MODE_CROP the image is cropped if needed.
 */
define('IMAGE_MODE_CROP', 'crop');

/**
 * @var string IMAGE_MODE_ADJUST the image is resized preserving its ratio and full visibility.
 */
define('IMAGE_MODE_ADJUST', 'adjust');

/**
 * @var string IMAGE_MODE_DISTORT the image is distorted to match the exact size.
 */
define('IMAGE_MODE_DISTORT', 'distort');

/**
 * @var string IMAGE_MODE_FORCE the image is distorted to match the exact size, even when the original image has inferior width and height.
 */
define('IMAGE_MODE_FORCE', 'force');

/**
 * @var string IMAGE_MODE_CROP_FORCE the image is resized and cropped to match the exact size, even when the original image has inferior width and height.
 */
define('IMAGE_MODE_CROP_FORCE', 'crop_force');


/**
 * @var string IMAGE_MODE_BLUR the image is blurred.
 */
define('IMAGE_MODE_BLUR', 'blur');

/**
 * @var string IMAGE_MASK_OPAQUE the mask is added as a layer of the image.
 */
define('IMAGE_MASK_OPAQUE', 'opaque');

/**
 * @var string IMAGE_MASK_ALPHA the mask is used like a grey-mask layer, each pixel of the base image has an opacity corresponding to the grey level of the mask (0 = black, 1 = white).
 */
define('IMAGE_MASK_ALPHA', 'alpha');

/**
 * Generates an image with maximum size, optional resize mode and masks.
 *
 * @staticvar string $cacheDir the cache folder where images are stored.
 * @param string $src the path to the image on the server.
 * @param int $width the maximum width, leave 0 to keep proportional.
 * @param int $height the maximum height, leave 0 to keep proportional.
 * @param string $mode the resize mode, see IMAGE_MODE_* constants for the detail.
 * @param array $masks the masks, as an array with each cell is a descriptive array of the mask (src=path to the mask, mode=IMAGE_MASK_* mode).
 * @param array $data the data that will be returned by the function (width and height of the generated image).
 * @return string the path of the generated image.
 */
function image($src, $width = 0, $height = 0, $mode = IMAGE_MODE_ADJUST, $masks = array(), &$data = array())
{
	$sourceDir = defined('IMAGE_SOURCE_FOLDER')? IMAGE_SOURCE_FOLDER: '';
	$cacheDir = defined('IMAGE_CACHE_FOLDER')? IMAGE_CACHE_FOLDER: ('cache' . DIRECTORY_SEPARATOR);
	$urlDir = defined('IMAGE_URL_BASE')? IMAGE_URL_BASE: ('cache' . DIRECTORY_SEPARATOR);

	if (!empty($masks))
	{
		if (!is_array($masks))
		{
			$masks = array($masks);
		}
		$newMasks = array();
		foreach ($masks as $mask)
		{
			if (!is_array($mask))
			{
				$newMasks[] = array(
					'src' => $mask,
					'mode' => IMAGE_MASK_OPAQUE,
					'slices' => array(0, 0, 0, 0),
				);
			}
			else
			{
				$newMasks[] = $mask;
			}
		}
		$masks = $newMasks;
	}

	// final image path
	$fileHash = substr(md5($width . '&' . $height . '&' . $mode . '&' . serialize($masks)), 0, 8);
	$fileName = preg_replace('#^(.*?)((?:\.\w+)?$)#', '\1-' . $fileHash . '\2', basename($src));
	$filePath = $cacheDir . $fileName;
	$fileUrl = $urlDir . $fileName;
	
	$sourceFilePath = $sourceDir . $src;

	if (file_exists($filePath))
	{
		$statSource = stat($sourceFilePath);
		$statCache = stat($filePath);
		if ($statSource['mtime'] < $statCache['mtime'])
		{
			$maskNotChanged = true;
			if (!empty($masks))
			{
				foreach ($masks as $mask)
				{
					$statMask = stat($sourceDir . $mask['src']);
					$maskNotChanged &= $statMask['mtime'] < $statCache['mtime'];
				}
			}
			if ($maskNotChanged)
			{
				$imageInfo = getimagesize($filePath);
				$data['width'] = $imageInfo[0];
				$data['height'] = $imageInfo[1];
				$data['path'] = $filePath;
				$data['url'] = $fileUrl;
				return $fileName;
			}
		}
	}

	// get original image size
	$imageInfo = getimagesize($sourceFilePath);
	$imageWidth = $imageInfo[0];
	$imageHeight = $imageInfo[1];
	$imageType = strtolower(isset($imageInfo['mime'])? preg_replace('#^[^/]+/#', '', $imageInfo['mime']): preg_replace('#^.*\.(\w+)$#', '\1', $src));

	if (($width <= 0 || $width >= $imageWidth) && ($height <= 0 || $height >= $imageHeight) && $mode != IMAGE_MODE_FORCE && $mode != IMAGE_MODE_CROP_FORCE)
	{
		$width = $imageWidth;
		$height = $imageHeight;
	}

	// new image
	$sourceImage = null;
	$finalImage = null;
	switch ($imageType)
	{
		case 'png':
			$sourceImage = imagecreatefrompng($sourceFilePath);
			break;
		case 'gif':
			$sourceImage = imagecreatefromgif($sourceFilePath);
			break;
		case 'jpg':
		case 'jpeg':
			$sourceImage = imagecreatefromjpeg($sourceFilePath);
			break;
	}

	if (is_null($sourceImage))
	{
		$data['error'] = 'image type not supported';
		return '';
	}

	if ($width <= 0 && $height < $imageHeight)
	{
		$width = round($height * $imageWidth / $imageHeight);
	}
	elseif ($height <= 0 && $width < $imageWidth)
	{
		$height = round($width * $imageHeight / $imageWidth);
	}
	else
	{
		switch ($mode)
		{
			case IMAGE_MODE_DISTORT:
			case IMAGE_MODE_FORCE:
			case IMAGE_MODE_CROP:
			case IMAGE_MODE_CROP_FORCE:
				break;
			case IMAGE_MODE_ADJUST:
			default:
				if (($width / $height) > ($imageWidth / $imageHeight))
				{
					$width = $height * $imageWidth / $imageHeight;
				}
				else
				{
					$height = $width * $imageHeight / $imageWidth;
				}
				break;
		}
	}

	$finalImage = imagecreatetruecolor($width, $height);
	imagealphablending($finalImage, false);
	imagesavealpha($finalImage, true);

	switch ($mode)
	{
		case IMAGE_MODE_CROP:
		case IMAGE_MODE_CROP_FORCE:
			if (($width / $height) > ($imageWidth / $imageHeight))
			{
				$imageHeightCropped = $imageWidth * $height / $width;
				imagecopyresampled($finalImage, $sourceImage, 0, 0, 0, round(($imageHeight - $imageHeightCropped) / 2), $width, $height, $imageWidth, $imageHeightCropped);
			}
			else
			{
				$imageWidthCropped = $imageHeight * $width / $height;
				imagecopyresampled($finalImage, $sourceImage, 0, 0, round(($imageWidth - $imageWidthCropped) / 2), 0, $width, $height, $imageWidthCropped, $imageHeight);
			}
			break;
		case IMAGE_MODE_BLUR:
			imagecopyresampled($finalImage, $sourceImage, 0, 0, 0, 0, $width, $height, $imageWidth, $imageHeight);
			imagefilter($finalImage, IMG_FILTER_GAUSSIAN_BLUR);
			imagefilter($finalImage, IMG_FILTER_GAUSSIAN_BLUR);
			imagefilter($finalImage, IMG_FILTER_GAUSSIAN_BLUR);
			imagefilter($finalImage, IMG_FILTER_GAUSSIAN_BLUR);
			break;
		case IMAGE_MODE_FORCE:
		case IMAGE_MODE_DISTORT:
		case IMAGE_MODE_ADJUST:
		default:
			imagecopyresampled($finalImage, $sourceImage, 0, 0, 0, 0, $width, $height, $imageWidth, $imageHeight);
			break;
	}

	// Add masks
	if (!empty($masks))
	{
		foreach ($masks as $mask)
		{
			$maskSrc = @$mask['src'];
			$maskMode = @$mask['mode'];
			$maskSlices = @$mask['slices'];
			$maskFilePath = $sourceDir . $maskSrc;

			// Get the resized mask
			$maskImage = null;
			switch ($maskMode)
			{
				case IMAGE_MASK_OPAQUE:
				case IMAGE_MASK_ALPHA:
					$maskInfo = getimagesize($maskFilePath);
					$maskWidth = $maskInfo[0];
					$maskHeight = $maskInfo[1];
					$maskType = strtolower(isset($maskInfo['mime'])? preg_replace('#^[^/]+/#', '', $maskInfo['mime']): preg_replace('#^.*\.(\w+)$#', '\1', $maskSrc));

					$sourceMaskImage = null;
					switch ($maskType)
					{
						case 'png':
							$sourceMaskImage = imagecreatefrompng($maskFilePath);
							break;
						case 'gif':
							$sourceMaskImage = imagecreatefromgif($maskFilePath);
							break;
						case 'jpg':
						case 'jpeg':
							$sourceMaskImage = imagecreatefromjpeg($maskFilePath);
							break;
					}

					if (is_null($sourceMaskImage))
					{
						$data['error'] = 'mask image type not supported';
						return '';
					}

					$maskFinalWidth = $width;
					$maskFinalHeight = $height;
					if ($width <= 0 && $height < $maskHeight)
					{
						$maskFinalWidth = round($height * $maskWidth / $maskHeight);
					}
					elseif ($height <= 0 && $width < $maskWidth)
					{
						$maskFinalHeight = round($width * $maskHeight / $maskWidth);
					}
					else
					{
						switch ($mode)
						{
							case IMAGE_MODE_DISTORT:
							case IMAGE_MODE_CROP:
							case IMAGE_MODE_FORCE:
							case IMAGE_MODE_CROP_FORCE:
								break;
							case IMAGE_MODE_ADJUST:
							default:
								if (($width / $height) > ($imageWidth / $imageHeight))
								{
									$maskFinalWidth = $height * $imageWidth / $imageHeight;
								}
								else
								{
									$maskFinalHeight = $width * $imageHeight / $imageWidth;
								}
								break;
						}
					}

					$maskImage = imagecreatetruecolor($maskFinalWidth, $maskFinalHeight);
					imagealphablending($maskImage, false);
					imagesavealpha($maskImage, true);

					switch ($mode)
					{
						case IMAGE_MODE_CROP:
						case IMAGE_MODE_CROP_FORCE:
							if (($maskFinalWidth / $maskFinalHeight) > ($maskWidth / $maskHeight))
							{
								$maskHeightCropped = $maskWidth * $maskFinalHeight / $maskFinalWidth;
								imagecopyresampled($maskImage, $sourceMaskImage, 0, 0, 0, round(($maskHeight - $maskHeightCropped) / 2), $maskFinalWidth, $maskFinalHeight, $maskWidth, $maskHeightCropped);
							}
							else
							{
								$maskWidthCropped = $maskHeight * $maskFinalWidth / $maskFinalHeight;
								imagecopyresampled($maskImage, $sourceMaskImage, 0, 0, round(($maskWidth - $maskWidthCropped) / 2), 0, $maskFinalWidth, $maskFinalHeight, $maskWidthCropped, $maskHeight);
							}
							break;
						case IMAGE_MODE_DISTORT:
						case IMAGE_MODE_ADJUST:
						case IMAGE_MODE_FORCE:
						default:
							imagecopyresampled($maskImage, $sourceMaskImage, 0, 0, 0, 0, $maskFinalWidth, $maskFinalHeight, $maskWidth, $maskHeight);
							break;
					}
					break;
			}

			// Apply mask
			switch ($maskMode)
			{
				case IMAGE_MASK_OPAQUE:
					for ($x = imagesx($finalImage) - 1; $x >= 0; $x--)
					{
						for ($y = imagesy($finalImage) - 1; $y >= 0; $y--)
						{
							$imageColor = imagecolorat($finalImage, $x, $y);
							$maskColor = imagecolorat($maskImage, $x, $y);
							$alphaImage = ($imageColor >> 24) % 256;
							$alphaMask = ($maskColor >> 24) % 256;
							$alphaRatioMask = (1 - ($alphaMask / 127));
							$alphaRatio = (1 - $alphaRatioMask) * (1 - ($alphaImage / 127));
							$alpha = (1 - ($alphaRatio + $alphaRatioMask)) * 127;
							$red = (($imageColor >> 16) % 256) * $alphaRatio + (($maskColor >> 16) % 256) * $alphaRatioMask;
							$green = (($imageColor >> 8) % 256) * $alphaRatio + (($maskColor >> 8) % 256) * $alphaRatioMask;
							$blue = (($imageColor >> 0) % 256) * $alphaRatio + (($maskColor >> 0) % 256) * $alphaRatioMask;
							$color = ($alpha << 24) | ($red << 16) | ($green << 8) | $blue;
							imagesetpixel($finalImage, $x, $y, $color);
						}
					}
					break;
				case IMAGE_MASK_ALPHA:
					for ($x = imagesx($finalImage) - 1; $x >= 0; $x--)
					{
						for ($y = imagesy($finalImage) - 1; $y >= 0; $y--)
						{
							$maskColor = imagecolorat($maskImage, $x, $y);
							$imageColor = imagecolorat($finalImage, $x, $y);
							$maxColor = max(($maskColor >> 16) % 256, ($maskColor >> 8) % 256, $maskColor % 256);
							$minColor = min(($maskColor >> 16) % 256, ($maskColor >> 8) % 256, $maskColor % 256);
							$alphaRatio = (($maxColor + $minColor) / 2) / 256;
							$alphaRatioMask = 1 - $alphaRatio;
							$alpha = 127 * $alphaRatioMask;
							$color = ($imageColor % 0x1000000) | ($alpha << 24);
							imagesetpixel($finalImage, $x, $y, $color);
						}
					}
					$imageType = 'png'; // Force for alpha transparency
					break;
			}
		}
	}

	switch ($imageType)
	{
		case 'png':
			imagepng($finalImage, $filePath);
			break;
		case 'gif':
			imagegif($finalImage, $filePath);
			break;
		case 'jpg':
		case 'jpeg':
			imageinterlace($finalImage, imageinterlace($sourceImage));
			imagejpeg($finalImage, $filePath, 100);
			break;
	}
	imagedestroy($finalImage);

	$data['width'] = $width;
	$data['height'] = $height;
	$data['path'] = $filePath;
	$data['url'] = $fileUrl;

	return $fileName;
}

/**
 * Returns an HTML-formatted image, optionnally resized.
 *
 * @param <type> $src
 * @param <type> $width
 * @param <type> $height
 * @param <type> $mode
 * @param <type> $html
 * @return <type>
 */
function htmlimage($src, $width = 0, $height = 0, $mode = IMAGE_MODE_ADJUST, $masks = array(), $html = '')
{
	$data = array();
	$fileSrc = image($src, $width, $height, $mode, $masks, $data);

	if (is_array($html))
	{
		$attrs = '';
		foreach ($html as $attr => $value)
		{
			$attrs .= ' ' . html($attr) . '="' . str_replace('"', '\\"', html($value)) . '"';
		}
		$html = $attrs;
	}

	return sprintf('<img src="%s" width="%d" height="%d" %s />', $data['url'], $data['width'], $data['height'], is_string($html)? $html: '');
}

// As seen on http://stackoverflow.com/questions/3657023/how-to-detect-shot-angle-of-photo-and-auto-rotate-for-website-display-like-desk
/**
 * Correct image orientation
 */
function imageautorotate($filepath)
{
	if (function_exists('exif_read_data'))
	{
		$exif = @exif_read_data($filepath);
		if (!empty($exif) && !empty($exif['Orientation']))
		{
			$deg = 0;
			$mirror = false;
			switch ($exif['Orientation'])
			{
	            case 2: $mirror = true; break;
	            case 3: $deg = 180; break;
	            case 4: $deg = 180; $mirror = true; break;
	            case 5: $deg = 270; $mirror = true; break;
	            case 6: $deg = 270; break;
	            case 7: $deg = 90; $mirror = true; break;
	            case 8: $deg = 90; break;
			}
			
			if ($deg || $mirror)
			{
				$img = imagecreatefromjpeg($filepath);
	            if ($deg)
	            {
	            	$img = imagerotate($img, $deg, 0);
				}
				//if ($mirror) $img = _mirrorImage($img);
				imagejpeg($img, $filepath, 95);
			}
		}
	}
}