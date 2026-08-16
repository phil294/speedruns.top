<?
declare(strict_types=1);

function scale_image_to_max_dimension(string $binary_image_data, int $max_dimension): string {
	$source_image = imagecreatefromstring($binary_image_data);
	if ($source_image === false) {
		throw new InvalidArgumentException('uploaded file is not a valid image');
	}
	imagepalettetotruecolor($source_image);
	imagealphablending($source_image, false);
	imagesavealpha($source_image, true);

	$original_width = imagesx($source_image);
	$original_height = imagesy($source_image);
	$scale_factor = min(1.0, $max_dimension / max($original_width, $original_height));
	$scaled_width = (int) round($original_width * $scale_factor);
	$scaled_height = (int) round($original_height * $scale_factor);

	$scaled_image = imagescale($source_image, $scaled_width, $scaled_height);
	imagealphablending($scaled_image, false);
	imagesavealpha($scaled_image, true);
	ob_start();
	imagepng($scaled_image);
	return (string) ob_get_clean();
}
