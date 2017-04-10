<?php
namespace Karogis\GeoServer;

/**
* GeoServer PHP REST API
*/
class GeoServer
{
    protected function exec($request, array $args = [])
    {
        $header = isset($args['header']) ? $args['header'] : [];
        $successCode = isset($args['successCode']) ? $args['successCode'] : 200;
        $method = isset($args['method']) ? $args['method'] : 'GET';
        $data = isset($args['data']) ? $args['data'] : null;

        $service = config('geoserver.url');
        $url = $service ."rest/". $request;
        $ch = curl_init($url);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $passwordStr = config('geoserver.username').':'.config('geoserver.password');
        curl_setopt($ch, CURLOPT_USERPWD, $passwordStr);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);

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

    protected function execFeature($request, $name=null)
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

    protected function getRest($url, $decode=false)
    {
        $header = ['Accept: application/json'];

        $result = $this->exec($url, compact('header'));

        if (!$result)
            return false;

        switch ($decode) {
            case 'array':
                return json_decode($result, true);
                break;

            case 'object':
                return json_decode($result);
                break;

            default:
                return $result;
                break;
        }
    }

    public function getLayer($layer=null, $decode='object')
    {
        if (is_null($layer)) {
            $url = "layers.json";
        } else {
            $url = "layers/$layer.json";
        }

        return $this->getRest($url, $decode);
    }

    public function getWorkspace($workspace=null, $decode='object')
    {
        if (is_null($workspace)) {
            $url = "workspaces.json";
        } else {
            $url = "workspaces/$workspace.json";
        }

        return $this->getRest($url, $decode);
    }

    public function getFeatureInfo($request = 'GetCapabilities', $name = null)
    {
        return $this->execFeature($request, $name);
    }

    public function getStyle($workspace, $style = null, $decode='object')
    {
        if (is_null($style)) {
            $url = "workspaces/$workspace/styles.json";
        } else {
            $url = "workspaces/$workspace/styles/$style.sld";
            $decode = false;
        }

        return $this->getRest($url, $decode);
    }
}
