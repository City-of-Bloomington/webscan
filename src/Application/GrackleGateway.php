<?php
/**
 * @copyright 2026 City of Bloomington, Indiana
 * @license https://www.gnu.org/licenses/agpl.txt GNU/AGPL, see LICENSE
 */
declare (strict_types=1);
namespace Application;

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\Utils;

class GrackleGateway
{
    private string $server;
    private Client $client;
    private string $jwt;

    public function __construct(array $config)
    {
        $this->server = $config['server'];
        $this->client = new Client(['base_uri'=>$config['server']]);
        $this->jwt    = $config['api_key'];
    }

    public function licenses(): array
    {
        $res  = $this->client->get($this->server.'/licenses', ['headers' => $this->headers()]);
        $json = (string)$res->getBody();
        return json_decode($json, true);
    }

    public function crawls(): array
    {
        $res  = $this->client->get($this->server.'/crawls', ['headers' => $this->headers()]);
        $json = (string)$res->getBody();
        return json_decode($json, true);
    }

    public function scan(string $file): array
    {
        $res = $this->client->post($this->server.'/scans/sync', [
            'headers'   => $this->headers(),
            'multipart' => [
                ['name'=>'autoDelete', 'contents'=>'true'],
                ['name'=>'type', 'contents'=>'pdfua1'],
                ['name'=>'file', 'contents'=>Utils::tryFopen($file, 'r')]
            ]
        ]);
        $json = (string)$res->getBody();
        return json_decode($json, true);
    }

    public function headers(): array
    {
        return [
            'Authorization' => 'Bearer '.$this->jwt,
            'Accept'        => 'application/json'
        ];
    }
}
