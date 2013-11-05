<?php

require_once('elgg_api.php');

class MoodleElgg extends ElggApi {
    private $token = '';
    private $group_guid = '';
    private $error = '';
    private $elgg_url;

    public function __construct(array $params) {
        $this->elgg_url = $params['elgg_url'] . 'services/api/rest/json/';
        $this->public_key  = $params['public_key'];
        $this->private_key = $params['private_key'];

        $this->getToken();
        if ($this->token != '') {
            $this->group_guid = $this->getGroupGUID();
        }
    }

    /**
     * Get an authentication token from Elgg.
     * 
     * @param  string $username
     * @param  strgin $time
     */
    private function getToken() {
        global $USER;

        $time = microtime(true);

        $username = $USER->username;
        $params = 'method=moodle.get_auth_token&username=' . $username . '&time=' . $time . '&code=' . $this->calculateCode($username, $time);

        $this->token = $this->sendRequest($params);
    }

    /**
     * Send request and handle the result.
     * 
     * @param string $params
     * @param string $method
     */
    public function sendRequest($params) {
        // Use token for authentication if already provided
        if ( !empty($this->token) && strstr($this->elgg_url, 'auth_token') === false ) {
            $params .= '&auth_token=' . $this->token;
        }

        $url = $this->elgg_url . '?' . $params;

        if ( $response = $this->sendApiCall($url, $params) ) {
            if ( $response->status == -1 ) {
                $this->error = $response->message;
                return false;
                
                // The token has expired. Get a new one and the request with new token.
                // @todo Should this be used? May cause infinite loops if an error occurs.
                //$this->getToken();
                //$this->sendRequest($params, $method);
            } else {
                if ( $response->status == 0 ) {
                    // Request was succesfull. Return the requested data.
                    return $response->result;
                } else {
                    $this->error = $response->message;
                    return false;
                }
            }
        } else {
            $this->error = "API call to address $url failed!.";
            return false;
        }
    }

    /**
     * Get the group guid for this group. If the group doesn't exist, it is created.
     * @return mixed Int on success, false on failure.
     */
    public function getGroupGUID() {
        global $COURSE;

        if ( $this->group_guid !== '' ) {
            return $this->group_guid;
        }

        // Get the groupGUID from Elgg
        $params = 'method=moodle.get_groupGUID&shortname=' . $COURSE->shortname;

        return $this->sendRequest($params);
    }

    public function getGroupDiscussions() {
        $params = 'method=moodle.get_group_discussions&group_guid=' . $this->getGroupGUID();

        return $this->sendRequest($params);
    }

    public function getObjects($object_type, $tag) {
        $params = 'method=moodle.get_objects&object_type=' . $object_type . '&tag=' . $tag;

        return $this->sendRequest($params);
    }

    /**
     * Create code used for initial authentication.
     * 
     * @param  float  $time
     * @return string $code
     */
    private function calculateCode($username, $time) {
        $code = md5($username . $time . $this->private_key);
        return $code;
    }

    /**
     * Check if an error has occurred.
     */
    public function hasError() {
        if (empty($this->error)) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * Return possible error message.
     */
    public function getError() {
        return $this->error;
    }
}