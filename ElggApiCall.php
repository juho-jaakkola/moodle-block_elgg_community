<?php

/**
 * This class sends Elgg API calls with optional HMAC authentication.
 *
 * The methods in the class have been reverse-engineered
 * from the Elgg 1.8 API engine.
 *
 * @see http://elgg.org/ (source file: elgg-1.8.x/engine/lib/web_services.php)
 */
class ElggApiCall {
    /**
     * Send api call.
     *
     * @param string $url          URL of the endpoint.
     * @param array  $call         Associated array of "variable" => "value"
     * @param string $method       GET or POST
     * @param string $post_data    The post data
     * @param string $content_type The content type
     */
    public function sendApiCall(array $keys, $url, array $call, $method = 'GET', $post_data = '', $content_type = 'application/octet-stream') {
        $headers = array();
        $encoded_params = array();

        $method = strtoupper($method);
        switch (strtoupper($method)) {
            case 'GET' :
            case 'POST' :
                break;
            default:
                throw new Exception("Call method $method is not implemented");
        }

        // Time
        $time = time();

        // Nonce
        $nonce = uniqid('');

        // URL encode all the parameters
        foreach ($call as $k => $v) {
            $encoded_params[] = urlencode($k) . '=' . urlencode($v);
        }

        $params = implode('&', $encoded_params);

        // Put together the query string
        $url = $url . "?" . $params;

        // Construct headers
        $posthash = "";
        if ($method == 'POST') {
            $posthash = $this->calculate_posthash($post_data, 'md5');
        }
        if ((isset($keys['public'])) && (isset($keys['private']))) {
            $headers['X-Elgg-apikey'] = $keys['public'];
            $headers['X-Elgg-time'] = $time;
            $headers['X-Elgg-nonce'] = $nonce;
            $headers['X-Elgg-hmac-algo'] = 'sha1';
            $headers['X-Elgg-hmac'] = $this->calculate_hmac(
                'sha1',
                $time,
                $nonce,
                $keys['public'],
                $keys['private'],
                $params,
                $posthash
            );
        }

        if ($method == 'POST') {
            $headers['X-Elgg-posthash'] = $posthash;
            $headers['X-Elgg-posthash-algo'] = 'md5';
            
            $headers['Content-type'] = $content_type;
            $headers['Content-Length'] = strlen($post_data);
        }

        // Opt array
        $http_opts = array(
            'method' => $method,
            'header' => $this->serialise_api_headers($headers)
        );

        if ($method == 'POST') {
            $http_opts['content'] = $post_data;
        }

        $opts = array('http' => $http_opts);

        // Send context
        $context = stream_context_create($opts);

        // Send the query and get the result. Suppress possible warning.
        $results = @file_get_contents($url, false, $context);

        if (!$results) {
            $message = get_string("bad_url", 'block_elgg_community');
            throw new Exception($message);
        }

        return $results;
    }

    /**
     * Calculate the HMAC for the http request.
     * This function signs an api request using the information provided. The signature returned
     * has been base64 encoded and then url encoded.
     *
     * @param string $algo The HMAC algorithm used
     * @param string $time String representation of unix time
     * @param string $api_key Your api key
     * @param string $secret Your private key
     * @param string $get_variables URLEncoded string representation of the get variable parameters, eg "method=user&guid=2"
     * @param string $post_hash Optional sha1 hash of the post data.
     * @return string The HMAC signature
     */
    private function calculate_hmac($algo, $time, $nonce, $api_key, $secret_key, $get_variables, $post_hash = "") {
        $ctx = hash_init($this->map_api_hash($algo), HASH_HMAC, $secret_key);

        hash_update($ctx, trim($time));
        hash_update($ctx, trim($nonce));
        hash_update($ctx, trim($api_key));
        hash_update($ctx, trim($get_variables));
        if (trim($post_hash)!="") {
            hash_update($ctx, trim($post_hash));
        }

        return urlencode(base64_encode(hash_final($ctx, true)));
    }

    /**
     * Map various algorithms to their PHP equivs.
     * This also gives us an easy way to disable algorithms.
     *
     * @param string $algo The algorithm
     * @return string The php algorithm
     */
    private function map_api_hash($algo) {
        $algo = strtolower($algo);
        $supported_algos = array(
            "md5" => "md5",
            "sha" => "sha1", // alias for sha1
            "sha1" => "sha1",
            "sha256" => "sha256"
        );

        if (array_key_exists($algo, $supported_algos)) {
            return $supported_algos[$algo];
        }

        throw new Exception("Algorithm $algo is not supported");
    }

    /**
     * Calculate a hash for some post data.
     *
     * @todo Work out how to handle really large bits of data.
     *
     * @param string $postdata string The post data.
     * @param string $algo The algorithm used.
     * @return string The hash.
     */
    private function calculate_posthash($postdata, $algo) {
        $ctx = hash_init($this->map_api_hash($algo));

        hash_update($ctx, $postdata);

        return hash_final($ctx);
    }

    /**
     * Utility function to serialise a header array into its text representation.
     *
     * @param array $headers The array of headers "key" => "value"
     * @return string
     */
    private function serialise_api_headers(array $headers) {
        $headers_str = "";

        foreach ($headers as $k => $v) {
            $headers_str .= trim($k) . ": " . trim($v) . "\r\n";
        }

        return trim($headers_str);
    }
}