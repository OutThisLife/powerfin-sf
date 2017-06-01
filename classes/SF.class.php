<?php
namespace Powerfin;

class SF extends \Powerfin\Main {
	protected $sf, $job;

	public function __construct() {
		try {
			$this->sf = new \SalesforceRestAPI\SalesforceAPI(
				'https://na34.salesforce.com', '32.0',
				PSF_KEY, PSF_SECRET
			);

			$this->sf->login(PSF_USERNAME, PSF_PASSWORD, PSF_TOKEN);

			return $this->sf;
		} catch (Exception $e) {
			wp_die($e->faultstring);
		}
	}

	protected function import($files) {
		$data = [];
		$reader = new \SpreadsheetReader(dirname(__FILE__) . '/../sample.xlsx');

		foreach ($reader AS $i => $row):
			if ($row[2] !== 'ACTIVE')
				continue;

			$data[] = [
				'Name' => $row[0],
				'question5__c' => $row[1],
				'Premise_Status' => $row[2],
				'BillingAddress' => $row[3],
				'Generated_kWh' => $row[4],
				'Consumed_kWh' => $row[5],
				'Net_kWh' => $row[6],
				'PowerfinPayment' => $row[7],
				'Host_Credit' => $row[8],
			];
		endforeach;

		// TODO: update account
		die(print_r($data));

		exit;
	}
}