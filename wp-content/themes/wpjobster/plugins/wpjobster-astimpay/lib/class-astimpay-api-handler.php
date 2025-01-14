<?php

// If this file is called firectly, abort!!!
defined('ABSPATH') or die('Direct access is not allowed.');

/**
 * Sends API requests to AstimPay Gateway.
 */

class AstimPay_Gateway_API_Handler
{
	/** @var string AstimPay Gateway API url. */
	public static $api_url;


	/** @var string AstimPay Gateway API key. */
	public static $api_key;

	/**
	 * Get the response from an API request.
	 * @param  string $endpoint
	 * @param  array  $params
	 * @param  string $method
	 * @param  string $type
	 * @return array
	 */
	public static function send_request($params = array(), $method = 'POST', $type = 'API')
	{
		$args = array(
			'method'  => $method,
			'headers' => array(
				'API-KEY' => self::$api_key,
				'Content-Type' => 'application/json'
			)
		);

		
		if ($type == 'API') {
			$url = self::normalizeBaseURL('checkout-v1');
		} else {
			$url = self::normalizeBaseURL('verify-payment');
		}

		if (in_array($method, array('POST', 'PUT'))) {
			$args['body'] = json_encode($params);
		} else {
			$url = add_query_arg($params, $url);
		}
		$response = wp_remote_request(esc_url_raw($url), $args);

		if (is_wp_error($response)) {
			return array(false, $response->get_error_message());
		} else {
			return json_decode($response['body']);
		}
	}

	/**
	 * Normalize BAse URL
	 * @param string $endpoint API Endpoint
	 * @return string
	*/
	private static function normalizeBaseURL($endpoint)
    {
        $baseURL = rtrim(self::$api_url, '/');
        $apiSegmentPosition = strpos($baseURL, '/api');

        if ($apiSegmentPosition !== false) {
            $baseURL = substr($baseURL, 0, $apiSegmentPosition + 4); // Include '/api'
        }

		$endpoint = ltrim($endpoint, '/');
        return $baseURL . '/' . $endpoint;
    }


	/**
	 * Create a new charge request.
	 * @param  int    $amount Total Amount of Product
	 * @param  string $currency Payment Currency
	 * @param  array  $full_name User Full Name
	 * @param  array  $email User Email
	 * @param  srring $metadata Metadata for extra validation
	 * @param  string $redirect Redirect URL
	 * @param  string $cancel Cancel URl
	 * @param  string $webhook_url Webhook URL
	 * @return array
	 */
	public static function create_payment($amount = null, $currency = null, $full_name = null, $email = null, $metadata = null, $redirect = null, $cancel = null, $webhook_url = null, $exchange_rate = 95)
	{

		if (is_null($currency)) {
			return array(false, 'Missing currency.');
		}

		$args['amount'] = !empty($amount) ? $amount : '0';

		if ($currency !== "BDT") {
			$args['amount'] = $amount * $exchange_rate;
		}

		$args['full_name'] = !empty($full_name) ? $full_name : 'Unknown';

		$args['email'] = !empty($email) ? $email : 'unknown@gmail.com';

		if (!is_null($metadata)) {
			$args['metadata'] = $metadata;
		}
		if (!is_null($redirect)) {
			$args['redirect_url'] = $redirect;
		}
		$args['return_type'] = 'GET';

		if (!is_null($cancel)) {
			$args['cancel_url'] = $cancel;
		}

		if (!is_null($webhook_url)) {
			$args['webhook_url'] = $webhook_url;
		}

		$result = self::send_request($args, 'POST', 'API');

		return $result;
	}

	/**
	 * Verify Payment.
	 * @param  string $invoice_id Invoice ID
	 * @return array
	 */
	public static function verify_payment($invoice_id)
	{
		if (!is_null($invoice_id)) {
			$args['invoice_id'] = $invoice_id;
		}

		$result = self::send_request($args, 'POST', 'VERIFY');

		return $result;
	}
}