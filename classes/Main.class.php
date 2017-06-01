<?php
namespace Powerfin;

use Powerfin\Files;
use Powerfin\SF;

class Main {
	protected function open($options, $url) {
		$ch = curl_init($url ?: PSF_URL);

		curl_setopt_array($ch, array_merge([
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_VERBOSE => true,
			CURLOPT_STDERR, fopen('php://stderr', 'w'),
			CURLOPT_FRESH_CONNECT => false,
		], $options));

		$result = curl_exec($ch);
		curl_close($ch);

		if (!$result)
			wp_die('cURL error ' . curl_errno($ch) . ': ' . htmlspecialchars(curl_error($ch)));

		return $result;
	}

	public function execute() {
		$files = (new Files)->getFileList();

		if (count($files) > 0)
			(new SF())->import($files);
	}
}