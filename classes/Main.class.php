<?php
namespace Powerfin;
use \SalesforceRestAPI\SalesforceAPI;
use \SalesforceRestAPI\Job;

class Main {
	private
		$sf,
		$files = [], $users = [];

	public function __construct($timestamp = FALSE) {
		$this->timestamp = $timestamp ?: time();

		try {
			$this->sf = new SalesforceAPI(
				'https://na34.salesforce.com', '38.0',
				PSF_KEY, PSF_SECRET
			);

			$this->sf->login(PSF_USERNAME, PSF_PASSWORD, PSF_TOKEN);

			$this->files = $this->getFiles();
			$this->users = $this->getUsers();
		} catch (Exception $e) {
			wp_die($e->faultstring);
		}
	}

	public function update() {
		$job = $this->sf->createJob(
			Job::OPERATION_UPDATE, 'Account', Job::TYPE_JSON
		);

		try {
			$batch = $this->sf->addBatch($job, $this->users);
			$job = $this->sf->closeJob($job);

			sleep(10);

			$batch = $this->sf->getBatchInfo($job, $batch);
			$result = $this->sf->getBatchResults($job, $batch);
		} catch (Exception $e) {
			wp_die($e->faultstring);
		}
	}

	// -----------------------------------------------

	private function open($options, $url = PSF_URL) {
		$ch = curl_init($url);
		curl_setopt_array($ch, $options);
		$result = curl_exec($ch);
		curl_close($ch);

		if (!$result)
			wp_die('cURL error [@'. $url .']' . curl_errno($ch) . ': ' . htmlspecialchars(curl_error($ch)));

		return $result;
	}

	// -----------------------------------------------

	private function getHtml() {
		if (!($result = get_transient('sfv_html'))):
			$result = $this->open([
				CURLOPT_USERPWD => PSF_HTPASSWD,
				CURLOPT_HTTPAUTH => CURLAUTH_BASIC,
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_VERBOSE => true,
				CURLOPT_STDERR => fopen('php://stderr', 'w'),
				CURLOPT_FRESH_CONNECT => false,
			]);

			set_transient('sfv_html', $result, DAY_IN_SECONDS);
		endif;

		return $result;
	}

	private function getFiles() {
		$files = [];
		$dom = new \DOMDocument;
		$dom->loadHTML($this->getHtml());

		foreach ($dom->getElementsByTagName('li') AS $li):
			$file = substr($li->getElementsByTagName('a')->item(0)->getAttribute('href'), 1);

			if (preg_match('/^(PFP2CPSRECON_'. date('Ym', $this->timestamp) .'.*\.pgp)/', $file)):
				$local = __DIR__ . '/../sheets/' . $file;

				if (!file_exists($local) || !filesize($local)):
					$fp = fopen($local, 'w+');

					$result = $this->open([
						CURLOPT_USERPWD => PSF_HTPASSWD,
						CURLOPT_HTTPAUTH => CURLAUTH_BASIC,
						CURLOPT_BINARYTRANSFER => true,
						CURLOPT_RETURNTRANSFER => false,
						CURLOPT_FILE => $fp,
					], PSF_URL . $file);

					fclose($fp);
				endif;

				$xlsx = str_replace('.pgp', '.xlsx', $local);
				copy($local, $xlsx);

				$files[] = $xlsx;
			endif;
		endforeach;

		return $files;
	}

	// -----------------------------------------------

	private function getUsers() {
		$users = [];

		foreach ($this->files AS $file):
			try {
				$parts = pathinfo($file);
				$reader = new \SpreadsheetReader($file);

				foreach ($reader AS $i => $row):
					if ($row[2] !== 'ACTIVE')
						continue;

					list(
						$name, $id, $status,
						$address, $genKwh, $consKwh,
						$netKwh, $payment, $credit
					) = $row;

					if ($id = $this->getUserId($name))
						$users[] = [
							'Id' => $id,
							date('F_Y', $this->timestamp) . '__c' => $netKwh,
						];
				endforeach;
			} catch (Exception $e) {
				wp_die($e->faultstring);
			}
		endforeach;

		return $users;
	}

	private function getUserId($name) {
		if (!($id = get_transient("sfid_{$name}"))):
			try {
				$result = $this->sf->searchSOQL("
					SELECT Id
					FROM Account
					WHERE Credit_Recipient_Name__c = '{$name}'
				");

				$id = $result['records'][0]['Id'];
				set_transient("sfid_{$name}", $id, YEAR_IN_SECONDS);
			} catch (Exception $e) {}
		endif;

		return $id;
	}
}