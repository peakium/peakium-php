<?php

// Peakium
require(dirname(__FILE__) . '/Peakium/JSON.php');
require(dirname(__FILE__) . '/Peakium/Set.php');
require(dirname(__FILE__) . '/Peakium/Util.php');
require(dirname(__FILE__) . '/Peakium/Version.php');

// Operations
require(dirname(__FILE__) . '/Peakium/ApiOperations/Create.php');
require(dirname(__FILE__) . '/Peakium/ApiOperations/Delete.php');
require(dirname(__FILE__) . '/Peakium/ApiOperations/List.php');
require(dirname(__FILE__) . '/Peakium/ApiOperations/Update.php');

// Resources
require(dirname(__FILE__) . '/Peakium/PeakiumObject.php');
require(dirname(__FILE__) . '/Peakium/ListObject.php');
require(dirname(__FILE__) . '/Peakium/ApiResource.php');
require(dirname(__FILE__) . '/Peakium/ApiResources/Customer.php');
require(dirname(__FILE__) . '/Peakium/ApiResources/Event.php');
require(dirname(__FILE__) . '/Peakium/ApiResources/EventWebhook.php');
require(dirname(__FILE__) . '/Peakium/ApiResources/Gateway.php');
require(dirname(__FILE__) . '/Peakium/ApiResources/Invoice.php');
require(dirname(__FILE__) . '/Peakium/ApiResources/PaymentSession.php');
require(dirname(__FILE__) . '/Peakium/ApiResources/SubmissionForm.php');
require(dirname(__FILE__) . '/Peakium/ApiResources/Subscription.php');
require(dirname(__FILE__) . '/Peakium/ApiResources/Webhook.php');

// Errors
require(dirname(__FILE__) . '/Peakium/Errors/PeakiumError.php');
require(dirname(__FILE__) . '/Peakium/Errors/ApiError.php');
require(dirname(__FILE__) . '/Peakium/Errors/ApiConnectionError.php');
require(dirname(__FILE__) . '/Peakium/Errors/AuthenticationError.php');
require(dirname(__FILE__) . '/Peakium/Errors/InvalidRequestError.php');
require(dirname(__FILE__) . '/Peakium/Errors/Extras/ArgumentError.php');
require(dirname(__FILE__) . '/Peakium/Errors/Extras/FailedConnectionError.php');
require(dirname(__FILE__) . '/Peakium/Errors/Extras/NoMethodError.php');
require(dirname(__FILE__) . '/Peakium/Errors/Extras/NotImplementedError.php');
require(dirname(__FILE__) . '/Peakium/Errors/Extras/SSLCertificateNotVerifiedError.php');

class Peakium
{
	public static $api_key;
	public static $api_base = 'https://secure.peakium.com/api';
	public static $api_version = null;
	public static $verify_ssl_certs = true;
	public static $ssl_bundle_path = 'data/ca-certificates.crt';
	const VERSION = '0.0.0';

	public static function api_url($endpoint_url='')
	{
		return self::$api_base . $endpoint_url;
	}

	public static function request($method, $endpoint_url, $api_key, $params=array(), $headers=array())
	{
		if (!$api_key && !($api_key = self::$api_key))
		{
			throw new \Peakium\AuthenticationError('No API key provided. ' .
				'Set your API key using "\Peakium::$api_key = \'<API-KEY>\';". ' .
				'You can generate API keys from the Peakium web interface. ' .
				'Go to https://manage.peakium.com/.');
		}

		if (preg_match('/\s/', $api_key))
		{
			throw new \Peakium\AuthenticationError('Your API key is invalid, as it contains ' .
		        'whitespace. (HINT: You can double-check your API key from the ' .
        		'Peakium web interface. Go to https://manage.peakium.com/.)');
    	}

		$request_opts = array('verify_ssl' => false);

		$ssl_bundle_path = dirname(__FILE__) . '/' . self::$ssl_bundle_path;
		if (self::ssl_preflight_passed($ssl_bundle_path))
		{
			$request_opts['verify_ssl'] = static::$verify_ssl_certs;
			$request_opts['ssl_ca_file'] = $ssl_bundle_path;
		}

		$params = \Peakium\Util::objects_to_ids($params);
		$url = self::api_url($endpoint_url);

		switch($method)
		{
			case 'get':
			case 'head':
			case 'delete':
				if (!empty($params))
					$url .= (strpos($endpoint_url, '?') ? '&' : '?') . self::uri_encode($params);
				$payload = null;
				break;
			default:
				if (is_string($params))
					$payload = $params;
				else
					$payload = self::uri_encode($params);
		}

		$request_opts['headers'] = array_unique(array_merge(self::request_headers($api_key), $headers));
		$request_opts['method'] = $method;
		$request_opts['open_timeout'] = 30;
		$request_opts['payload'] = $payload;
		$request_opts['url'] = $url;
		$request_opts['timeout'] = 80;

		try
		{
			$response = self::execute_request($request_opts);
		}
		catch(\Peakium\NoMethodError $e)
		{
			if (preg_match('/\WRequestFailed\W/', $e->getMessage()))
			{
				$e = new \Peakium\ApiConnectionError('Unexpected HTTP response code');
				self::handle_restclient_error($e);
			}
			else
			{
				throw $e;
			}
		}
		catch(\Peakium\PeakiumError $e)
		{
			if ($rcode = $e->http_code AND $rbody = $e->http_body)
				self::handle_api_error($rcode, $rbody);
			else
				self::handle_restclient_error($e);
		}
		catch(\Peakium\ApiConnectionError $e)
		{
			self::handle_restclient_error($e);
		}

		return array(self::parse($response), $api_key);
	}

	protected static $no_bundle = false;
	protected static $no_verify = false;

	private static function ssl_preflight_passed($ssl_bundle_path)
	{
		if (!self::$verify_ssl_certs && !self::$no_verify)
		{
      		error_log("WARNING: Running without SSL cert verification. " .
				"Execute '\Peakium::\$verify_ssl_certs = true;' to enable verification.");

	        self::$no_verify = true;
	    }
	    elseif (!\Peakium\Util::file_readable($ssl_bundle_path) && !self::$no_bundle)
	    {
	    	error_log(sprintf("WARNING: Running without SSL cert verification " .
				"because %s isn't readable", $ssl_bundle_path));

			self::$no_bundle = true;
	    }

		return !(self::$no_verify || self::$no_bundle);
	}

	private static function user_agent()
	{
		if (!isset(self::$uname))
			$uname = php_uname();

	    $lang_version = phpversion();

		return array(
			'bindings_version' => \Peakium\Version::VERSION,
			'lang' => 'php',
			'lang_version' => $lang_version,
			'platform' => PHP_OS,
			'publisher' => 'peakium',
			'uname' => $uname,
		);
	}

	public static function uri_encode($params, $prefix = null)
	{
		if (!is_array($params))
			return $params;

		$uri_encoded_array = array();
		foreach ($params as $k => $v) {
			if ($v === null)
				continue;

			if ($prefix)
				$k = $prefix . '[' . $k . ']';

			if (is_array($v)) {
				$uri_encoded_array[] = self::uri_encode($v, $k);
			} else {
				$uri_encoded_array[] = urlencode($k) . '=' . urlencode($v);
			}
		}

		return implode("&", $uri_encoded_array);
	}

	private static function request_headers($api_key)
	{
		$headers = array(
			'user_agent' => 'Peakium/v1 PHPBindings/' . \Peakium\Version::VERSION,
			'authorization' => 'Bearer ' . $api_key,
			'content_type' => 'application/x-www-form-urlencoded',
		);

		if (self::$api_version)
			$header['peakium_version'] = self::$api_version;

		try
		{
			$headers['x_peakium_client_user_agent'] = \Peakium\JSON::dump(self::user_agent());
		}
		catch(\Exception $e)
		{
			$headers['x_peakium_client_raw_user_agent'] = implode(' ', self::user_agent());
			$headers['error'] = "{$e->getMessage()} [{$e->getFile()}] ({$e->getLine()})";
		}

		return $headers;
	}

	private static function execute_request($opts)
	{
		return self::_curl_request($opts);
	}

	private static function parse($response)
	{
		try
		{
			return \Peakium\Util::symbolize_names(\Peakium\JSON::load($response['body']));
		}
		catch(\Exception $e)
		{
			throw self::general_api_error($response['code'], $response['body']);
		}
	}

	private static function general_api_error($rcode, $rbody)
	{
		return new \Peakium\APIError(sprintf("Invalid response object from API: %s " .
				"(HTTP response code was %s)", $rcode, $rbody));
	}

	private static function handle_api_error($rcode, $rbody)
	{
		try
		{
			$error_obj = \Peakium\JSON::load($rbody);
			$error_obj = \Peakium\Util::symbolize_names($error_obj);
			if (isset($error_obj->error))
				$error = $error_obj->error;
			else
				throw new \Peakium\PeakiumError();
		}
		catch(\Peakium\PeakiumError $e)
		{
			throw self::general_api_error($rcode, $rbody);
		}

		switch($rcode):
			case 400:
			case 404:
				throw self::invalid_request_error($error, $rcode, $rbody, $error_obj);
			case 401:
				throw self::authentication_error($error, $rcode, $rbody, $error_obj);
			default:
				throw self::api_error($error, $rcode, $rbody, $error_obj);
		endswitch;
	}

	private static function invalid_request_error($error, $rcode, $rbody, $error_obj)
	{
		return new \Peakium\InvalidRequestError($error->message, isset($error->param) ? $error->param : null, $rcode,
													$rbody, $error_obj);
	}

	private static function authentication_error($error, $rcode, $rbody, $error_obj)
	{
		return new \Peakium\InvalidRequestError($error->message, $rcode, $rbody, $error_obj);
	}

	private static function api_error($error, $rcode, $rbody, $error_obj)
	{
		return new \Peakium\APIError($error->message, $rcode, $rbody, $error_obj);
	}

	private static function handle_restclient_error(\Exception $e)
	{
		switch(get_class($e)):
			case '\Peakium\FailedConnectionError':
				$message = sprintf("Could not connect to Peakium (%s). " .
					"Please check your internet connection and try again. " .
					"If this problem persists, let us know at contact@peakium.com.",
					self::$api_base);
				break;

			case '\Peakium\SSLCertificateNotVerifiedError':
				$message = "Could not verify Peakium's SSL certificate. " .
					"Please make sure that your network is not intercepting certificates. " .
					"(Try going to https://secure.peakium.com/v1 in your browser.) " .
					"If this problem persists, let us know at contact@peakium.com.";
				break;

			default:
				$message = "Unexpected error communicating with Peakium. " .
					"If this problem persists, let us know at contact@peakium.com.";
		endswitch;

		throw new \Peakium\APIConnectionError($message . "\n\n(Network error: " . $e->getMessage() . ")");
	}

	public static function _curl_request($opts)
	{
		$curl = curl_init();
		$url = $opts['url'];
		switch($opts['method']):
			case 'get':
				$curl_opts[CURLOPT_HTTPGET] = 1;
				$url .= '?' . $opts['payload'];
				break;
			case 'post':
				$curl_opts[CURLOPT_POST] = 1;
				$curl_opts[CURLOPT_POSTFIELDS] = $opts['payload'];
				break;
			case 'delete':
				$curl_opts[CURLOPT_CUSTOMREQUEST] = 'DELETE';
				$url .= '?' . $opts['payload'];
				break;
			default:
				throw new ApiError(sprintf('Unrecognized method %s', $opts['method']));
		endswitch;

		$curl_opts[CURLOPT_RETURNTRANSFER] = true;
		$curl_opts[CURLOPT_URL] = $url;
		$curl_opts[CURLOPT_CONNECTTIMEOUT] = $opts['open_timeout'];
		$curl_opts[CURLOPT_TIMEOUT] = $opts['timeout'];
		$curl_opts[CURLOPT_HTTPHEADER] = self::_convert_headers_to_curl_format($opts['headers']);
		$curl_opts[CURLOPT_SSL_VERIFYPEER] = $opts['verify_ssl'];

		curl_setopt_array($curl, $curl_opts);
		$rbody = curl_exec($curl);

		$errno = curl_errno($curl);

		if ($errno == CURLE_SSL_CACERT ||
			$errno == CURLE_SSL_PEER_CERTIFICATE ||
			$errno == 77 // CURLE_SSL_CACERT_BADFILE (constant not defined in PHP though)
		) {
			array_push($opts['headers'], self::_convert_headers_to_curl_format(array('x_peakium_client_info' => '{"ca":"using Peakium-supplied CA bundle"}')));
			curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
			curl_setopt($curl, CURLOPT_CAINFO, $opts['ssl_ca_file']);

			$rbody = curl_exec($curl);
		}

		if ($rbody === false) {
			$errno = curl_errno($curl);
			$message = curl_error($curl);
			curl_close($curl);
			self::_handle_curl_error($errno, $message);
		}

		$rcode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
		curl_close($curl);

		if ($rcode < 200 || $rcode >= 300) {
			throw new \Peakium\ApiConnectionError(null, $rcode, $rbody);
		}

		return array('body' => $rbody, 'code' => $rcode);
	}

	public static function _handle_curl_error($errno, $message)
	{
		switch ($errno):
			case CURLE_COULDNT_CONNECT:
			case CURLE_COULDNT_RESOLVE_HOST:
			case CURLE_OPERATION_TIMEOUTED:
				throw new \Peakium\FailedConnectionError($message, $errno);
				break;
			case CURLE_SSL_CACERT:
			case CURLE_SSL_PEER_CERTIFICATE:
				throw new \Peakium\SSLCertificateNotVerifiedError($message, $errno);
				break;
			default:
				throw new \Peakium\ApiConnectionError($message, $errno);
		endswitch;
	}

	public static function _convert_headers_to_curl_format($mixed)
	{
		switch (gettype($mixed)):
			case 'string':
				return implode('-', array_map('ucfirst', explode('_', $mixed)));
			case 'array':
				$fixed = array();
				foreach($mixed as $key => $value)
					$fixed[] = self::_convert_headers_to_curl_format($key) . ': ' . $value;

				return $fixed;
			default:
				return $mixed;
		endswitch;
	}
}
