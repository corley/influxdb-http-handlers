<?php
namespace InfluxDB\Handler;

use GuzzleHttp\HandlerStack;
use GuzzleHttp\Handler\CurlHandler;
use GuzzleHttp\Client as HttpClient;
use InfluxDB\Client;
use InfluxDB\Options;
use InfluxDB\Adapter\GuzzleAdapter;

class IntegrationTest extends \PHPUnit_Framework_TestCase
{
    private $options;
    private $client;

    public function testIntegrationResponseHandler()
    {
        $options = $this->getOptions();
        $options->setDatabase("mydb");
        $client = $this->getClient();

        $client->createDatabase("mydb");

        $client->mark([
            "time" => "2015-09-10T23:20:35Z",
            "points" => [
                [
                    "measurement" => "cpu",
                    "fields" => [
                        "value" => "OK",
                        "hello" => 2,
                    ],
                ],
                [
                    "measurement" => "mem",
                    "fields" => [
                        "value" => "KO",
                        "hello" => 4,
                    ],
                ],
            ],
        ]);

        $stack = new HandlerStack();
        $stack->setHandler(new CurlHandler());
        $stack->push(\InfluxDB\Handler\message_handler()); // Push the response handler

        $http = new HttpClient(['handler' => $stack]);

        $options = new Options();
        $options->setDatabase("mydb");
        $adapter = new GuzzleAdapter($http, $options);

        $client = new Client($adapter);

        $response = $client->query("SELECT * FROM cpu,mem");

        $this->assertEquals([
            "cpu" => [
                [
                    "value" => "OK",
                    "hello" => 2,
                    "time" => "2015-09-10T23:20:35Z",
                ],
            ],
            "mem" => [
                [
                    "value" => "KO",
                    "hello" => 4,
                    "time" => "2015-09-10T23:20:35Z",
                ],
            ],
        ], $response);
    }

    public function setUp()
    {
        $options = $this->options = new Options();
        $guzzleHttp = new HttpClient();
        $adapter = new GuzzleAdapter($guzzleHttp, $options);
        $client = $this->client = new Client($adapter);
        $this->dropAll();
    }
    public function tearDown()
    {
        $this->dropAll();
    }
    private function dropAll()
    {
        $databases = $this->getClient()->getDatabases();
        if (array_key_exists("values", $databases["results"][0]["series"][0])) {
            foreach ($databases["results"][0]["series"][0]["values"] as $database) {
                $this->getClient()->deleteDatabase($database[0]);
            }
        }
    }

    public function getOptions()
    {
        return $this->options;
    }
    public function getClient()
    {
        return $this->client;
    }
}
