<?php

// A small instagram API client
class InstagramAPIClient
{
    private $client_id;
    private $api_url = 'https://api.instagram.com/v1';

    public function __construct($client_id) {
        $this->client_id = $client_id;
    }

    public function get($type, $params=array()) {
        $url = $this->api_url;

        // Get the appropriate URL for each type of resource
        switch ($type) {
            case '/media/popular':
                $url = $this->appendClientID($url.$type);
            break;
            default:
                return false;
            break;
        }

        try {
            // Fetch the data and JSON decode it
            $data = $this->fetchData($url);
            return $this->parseData($data);
        }
        catch (Exception $e) {
            return false;
        }
    }

    private function appendClientID($url) {
        return $url."?client_id={$this->client_id}";
    }

    private function fetchData($url) {
        if (function_exists('curl_init')) {
            // If the server has CURL installed
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_TIMEOUT, 20);
            $result = curl_exec($ch);
            curl_close($ch); 
            return $result;
        } else {
            // The server does not have CURL installed
            // Use an alternative method
            return file_get_contents($url);
        }
    }

    public function parseData($data) {
        try {
            return json_decode($data);
        }
        catch (Exception $e) {
            return $data;
        }
    }
}

?>