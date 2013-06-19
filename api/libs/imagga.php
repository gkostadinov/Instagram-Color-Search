<?php

// Copyright 2008-2013 Imagga Bulgaria. All Rights Reserved.
// Last modified on Apr 06, 2013
//
// +---------------------------------------------------------------------------+
// | Imagga Color API PHP5 Client                                              |
// +---------------------------------------------------------------------------+
// | Copyright (c) 2008-2013 Imagga Ltd.                                       |
// | All rights reserved.                                                      |
// |                                                                           |
// | Redistribution and use in source and binary forms, with or without        |
// | modification, are permitted provided that the following conditions        |
// | are met:                                                                  |
// |                                                                           |
// | 1. Redistributions of source code must retain the above copyright         |
// |    notice, this list of conditions and the following disclaimer.          |
// | 2. Redistributions in binary form must reproduce the above copyright      |
// |    notice, this list of conditions and the following disclaimer in the    |
// |    documentation and/or other materials provided with the distribution.   |
// |                                                                           |
// | THIS SOFTWARE IS PROVIDED BY THE AUTHOR ``AS IS'' AND ANY EXPRESS OR      |
// | IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES |
// | OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED.   |
// | IN NO EVENT SHALL THE AUTHOR BE LIABLE FOR ANY DIRECT, INDIRECT,          |
// | INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT  |
// | NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, |
// | DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY     |
// | THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT       |
// | (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF  |
// | THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.         |
// +---------------------------------------------------------------------------+
// | For help with this library, contact api@imagga.com                        |
// +---------------------------------------------------------------------------+

class ColorAPIClient
{
    private $api_key;
    private $api_secret;
	private $api_endpoint;
	
    public function get_api_url()
    {
        return 'http://' . $this->api_endpoint;
    }
	
    public function __construct($api_key, $api_secret, $api_endpoint)
    {
        $this->api_key = $api_key;
        $this->api_secret = $api_secret;
		$this->api_endpoint = $api_endpoint;
        $this->server_addr = $this->get_api_url() . '/colorsearchserver.php';
		$this->server_upload_addr = self::get_api_url() . '/uploadforprocessing.php';
    }
	
	// $imagesToProcess is an array of public image URLs to be processed for top-colors extraction,
	// up to 10 URLs can be submitted for processing in a single call.
	// You get a result array with entries in the same order the URL have been given for processing,
	// each entry contains the URL and an object with the top colors info.
	// If there is an error with processing the image instead of an array with regions you get the string "ERROR loading URL"
    public function colors_by_urls($imagesToProcess, $extract_overall_colors = 1, $extract_object_colors = 1)
    {
	    $ids = array();
        $urls = array();
        $cnt = count($imagesToProcess);
        for ($i = 0; $i < $cnt; $i++)
        {
            $ids[] = isset($imagesToProcess[$i]['id']) ? intval($imagesToProcess[$i]['id']) : 0;
            $urls[] = $imagesToProcess[$i]['url'];
        }
	
        return $this->call_method(
          'imagga.colorsearch.extract',
          array(
            'urls' => $urls,
			'ids' => $ids,
			'extract_overall_colors' => $extract_overall_colors,
			'extract_object_colors' => $extract_object_colors,
          )
        );
    }
	
	
	public function colors_by_uploadcode($uploadCode, $deleteAfterwards, $id = 0, $extract_overall_colors = 1, $extract_object_colors = 1)
    {
	    return $this->call_method(
          'imagga.colorsearch.extract',
          array(
            'upload_code' => $uploadCode,			
			'delete_afterwards' => $deleteAfterwards,
			'ids' => array($id),
			'extract_overall_colors' => $extract_overall_colors,
			'extract_object_colors' => $extract_object_colors,
          )
        );
    }
	
	
	// this function gets an array with multiple entries, each containg array with 'percent', 'r', 'g', 'b' elements.
	// the sum of the percents should be 100
	// the result contains entries with 'id' and 'dist', sorted by 'dist'
	public function rank_similar_color(&$color_combination, $maxDist = 3000, $maxCount = 1000)
    {	
		$color_vector = array();
		$cnt = count($color_combination);
		for ($i = 0; $i < $cnt; $i++)
		{
			$color_vector[] = $color_combination[$i]['percent'];
			$color_vector[] = $color_combination[$i]['r'];
			$color_vector[] = $color_combination[$i]['g'];
			$color_vector[] = $color_combination[$i]['b'];
		}
        $result =& $this->call_method('imagga.colorsearch.rank', array(
            'color_vector' => $color_vector,
			'type' => 'overall',
            'dist' => $maxDist,
            'count' => $maxCount)
        );
        return is_array($result) || is_object($result) ? $result : false;
    }
	
	// this function gets an array with multiple entries, each containg array with 'percent', 'r', 'g', 'b' elements.
	// the sum of the percents should be 100
	// the result contains entries with 'id' and 'dist', sorted by 'dist'
	public function rank_similar_color_object(&$color_combination, $maxDist = 3000, $maxCount = 1000)
    {
		$color_vector = array();
		$cnt = count($color_combination);
		for ($i = 0; $i < $cnt; $i++)
		{
			$color_vector[] = $color_combination[$i]['percent'];
			$color_vector[] = $color_combination[$i]['r'];
			$color_vector[] = $color_combination[$i]['g'];
			$color_vector[] = $color_combination[$i]['b'];
		}
        $result =& $this->call_method('imagga.colorsearch.rank', array(
            'color_vector' => $color_vector,
			'type' => 'object',
            'dist' => $maxDist,
            'count' => $maxCount)
        );
        return is_array($result) || is_object($result) ? $result : false;
    }
	
	// this function gets an array with multiple entries, each containg array with 'percent', 'r', 'g', 'b' elements.
	// the sum of the percents should be 100
	// the result contains entries with 'id' and 'dist', sorted by 'dist'
	public function rank_similar_color_background(&$color_combination, $maxDist = 3000, $maxCount = 1000)
    {
		$color_vector = array();
		$cnt = count($color_combination);
		for ($i = 0; $i < $cnt; $i++)
		{
			$color_vector[] = $color_combination[$i]['percent'];
			$color_vector[] = $color_combination[$i]['r'];
			$color_vector[] = $color_combination[$i]['g'];
			$color_vector[] = $color_combination[$i]['b'];
		}	
        $result =& $this->call_method('imagga.colorsearch.rank', array(
            'color_vector' => $color_vector,
			'type' => 'background',
            'dist' => $maxDist,
            'count' => $maxCount)
        );
        return is_array($result) || is_object($result) ? $result : false;
    }
	
	
	
	// If parameters are left as default 0 the API usage since the beginnig of current months is returned,
	// Else - the usage for the period specified by start_time and end_time UNIX timestamp format (seconds since 1970)
	// This method returns an object showing the usage of all services, not only the color API
	public function get_api_usage($start_time = 0, $end_time = 0)
    {
        return $this->call_method(
          'imagga.usage.get',
          array(
            'start' => $start_time,
            'end' => $end_time,
          )
        );
    }
 	
	
	public function upload_for_processing($localFileName)
    {
        $post_data['file'] = "@{$localFileName}";
		 
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $this->server_upload_addr);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_VERBOSE, 1);
		$response = curl_exec($ch);
		
		//echo "response:'$response'";
		$result = json_decode($response);
		return $result;
    }
	
//--------------------------------------------- UTILITY FUNCTIONS ---------------------------------------------//
	

  public function & call_method($method, $params)
  {
    $response = $this->post_request($method, $params);
    //echo "RESPONSE: '$response'";
		
    $result = json_decode($response);

    if (is_array($result) && isset($result['error_code']))
    {
      throw new ColorAPIClientException($result['error_msg'], $result['error_code']);
    }
	
	if (is_object($result) && isset($result->error_code))
	{
      throw new ColorAPIClientException($result->error_message, $result->error_code);
    }
	
    return $result;
  }

  public static function generate_sig($params_array, $secret)
  {
    $str = '';
	
	ksort($params_array);
    // Note: here we assume that the signature parameter is NOT already included in $params_array.
    foreach ($params_array as $k=>$v) {
      $str .= "$k=$v";
    }
    $str .= $secret;

    return md5($str);
  }

  private function create_post_string($method, $params)
  {
    $params['method'] = $method;
    $params['api_key'] = $this->api_key;
    if (!isset($params['v']))
    {
      $params['v'] = '1.0';
    }
    $post_params = array();
    foreach ($params as $key => &$val)
    {
      if (is_array($val)) $val = implode(',', $val);
      $post_params[] = $key . '=' . urlencode($val);
    }
    $secret = $this->api_secret;
    $post_params[] = 'sig=' . self::generate_sig($params, $secret);
    return implode('&', $post_params);
  }

  public function post_request($method, $params)
  {
    $post_string = $this->create_post_string($method, $params);
	
	if (function_exists('curl_init'))
    {
      // Use CURL if installed...
      $ch = curl_init();
      curl_setopt($ch, CURLOPT_URL, $this->server_addr);
      curl_setopt($ch, CURLOPT_POSTFIELDS, $post_string);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
      curl_setopt($ch, CURLOPT_USERAGENT, 'Color & Pattern API PHP5 Client 1.0 (curl) ' . phpversion());
      $result = curl_exec($ch);
      curl_close($ch);
    }
    else
    {
      // Non-CURL based version...
      $context =
        array('http' =>
              array('method' => 'POST',
                    'header' => 'Content-type: application/x-www-form-urlencoded'."\r\n".
                                'User-Agent: Color & Pattern API PHP5 Client 1.0 (non-curl) '.phpversion()."\r\n".
                                'Content-length: ' . strlen($post_string),
                    'content' => $post_string));
      $contextid = stream_context_create($context);
      $sock = fopen($this->server_addr, 'r', false, $contextid);
      if ($sock)
      {
        $result='';
        while (!feof($sock))
          $result.=fgets($sock, 4096);
        fclose($sock);
      }
    }
    return $result;
  }

}

class ColorAPIClientException extends Exception
{
}

?>