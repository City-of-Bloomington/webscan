<?php
/**
 * @copyright 2026 City of Bloomington, Indiana
 * @license https://www.gnu.org/licenses/agpl.txt GNU/AGPL, see LICENSE
 */
declare (strict_types=1);
namespace Application;

class WaveGateway
{
    private string $server;
    private string $api_key;

    public function __construct(array $config)
    {
        $this->server  = 'https://wave.webaim.org/api/request';
        $this->api_key = $config['api_key'];
    }

    public function scan(string $url)
    {
        $u = $this->server.'?'.http_build_query([
            'key' => $this->api_key,
            'url' => $url
        ], '', '&');
        return self::get($u);
    }

    private static function get(string $url): array
    {
        $request = curl_init($url);
        curl_setopt($request, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($request, CURLOPT_FOLLOWLOCATION, true);
        $res     = curl_exec($request);
        return $res ? json_decode($res, true) : [];
    }
}
