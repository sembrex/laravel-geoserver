<?php
namespace Karogis\GeoServer;

/**
* GeoServer PHP REST API
*/
class GeoServer
{
	private function exec($request, array $header, $successCode=200, $method='GET', $data='')
	{
	    $service = config('geoserver.url');
	    $url = $service ."rest/". $request;
	    $ch = curl_init($url);

	    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	    $passwordStr = config('geoserver.username').':'.config('geoserver.password');
	    curl_setopt($ch, CURLOPT_USERPWD, $passwordStr);
	    curl_setopt($ch, CURLOPT_HTTPHEADER, $header);

	    $successCode = 200;

	    if ($method == 'POST') {
    		curl_setopt($ch, CURLOPT_POST, True);
    		curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
	    }

	    $result = curl_exec($ch);

	    $info = curl_getinfo($ch);
	    if ($info['http_code'] != $successCode) {
	      $msgStr = "# Unsuccessful request to ";
	      $msgStr .= $url." [". $info['http_code']. "]\n";
	      logger("[GeoServer]: $msgStr");
	    } else {
	      $msgStr = "# Successful request to ".$url."\n";
	      logger("[GeoServer]: $msgStr");
	    }

	    curl_close($ch);
	    return $result;
	}

	private function execFeature($request, $name=null)
	{
	    $service = config('geoserver.url');
	    $params = [
	    	'service' => 'wfs',
	    	'version' => '1.1.0',
	    	'request' => $request,
	    	'outputFormat' => 'application/json',
	    ];

	    if (!is_null($name)) {
	    	$params['typeNames'] = $name;
	    }

	    $url = $service ."wfs?". http_build_query($params);
	    $ch = curl_init($url);

	    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Accept: application/json']);

	    $successCode = 200;

	    $result = curl_exec($ch);

	    $info = curl_getinfo($ch);
	    if ($info['http_code'] != $successCode) {
	      $msgStr = "# Unsuccessful request to ";
	      $msgStr .= $url." [". $info['http_code']. "]\n";
	      logger("[GeoServer]: $msgStr");
	    } else {
	      $msgStr = "# Successful request to ".$url."\n";
	      logger("[GeoServer]: $msgStr");
	    }

	    curl_close($ch);
	    return $result;
	}

	private function getRest($url, $output='json', $decode=false)
	{
		switch ($output) {
			case 'xml':
				$header = ['Accept: application/xml'];
				break;

			default:
				$header = ['Accept: application/json'];
				break;
		}

		$result = $this->exec($url, $header);

		if (!$result)
			return false;

		if ($output == 'json') {
			if ($decode == 'object')
				return json_decode($result);
			elseif ($decode == 'array')
				return json_decode($result, true);
		}

		return $result;
	}

	public function getLayer($layer=null, $output='json', $decode=false)
	{
		if (is_null($layer)) {
			$url = "layers.$output";
		} else {
			$url = "layers/$layer.$output";
		}

		return $this->getRest($url, $output, $decode);
	}

	public function getWorkspace($workspace=null, $output='json', $decode=false)
	{
		if (is_null($workspace)) {
			$url = "workspaces.$output";
		} else {
			$url = "workspaces/$workspace.$output";
		}

		return $this->getRest($url, $output, $decode);
	}

	public function getFeatureInfo($request = 'GetCapabilities', $name = null)
	{
		return $this->execFeature($request, $name);
	}
}
