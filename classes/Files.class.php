<?php
namespace Powerfin;

class Files extends \Powerfin\Main {
	private $files = [];

	public function getFileList() {
		if (!($result = get_transient('sfv_html'))):
			$result = $this->open([
				CURLOPT_USERPWD => PSF_HTPASSWD,
				CURLOPT_HTTPAUTH => CURLAUTH_BASIC,
			]);

			set_transient('sfv_html', $result, DAY_IN_SECONDS);
		endif;

		$dom = new \DOMDocument;
		$dom->loadHTML($result);

		foreach ($dom->getElementsByTagName('li') AS $li):
			$file = substr($li->getElementsByTagName('a')->item(0)->getAttribute('href'), 1);

			if (preg_match('/^(PFP2CPSRECON_.*\.pgp)/', $file)):
				$fullPath = $url . $file;
				array_push($this->files, $fullPath);
			endif;
		endforeach;

		return $this->files;
	}
}