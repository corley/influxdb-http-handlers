<?php
namespace InfluxDB\Handler;

use GuzzleHttp\HandlerStack;
use GuzzleHttp\Handler\CurlHandler;
use GuzzleHttp\Client as HttpClient;
use InfluxDB\Client;
use InfluxDB\Options;
use InfluxDB\Adapter\GuzzleAdapter;
use InfluxDB\Adapter\Http;
use InfluxDB\Manager;
use InfluxDB\Query\CreateDatabase;
use InfluxDB\Query\DeleteDatabase;
use InfluxDB\Query\GetDatabases;

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

        $options = new Http\Options();
        $options->setDatabase("mydb");

        $reader = new Http\Reader($http, $options);
        $writer = new Http\Writer($http, $options);

        $client = new Client($reader, $writer);

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
        $options = $this->options = new Http\Options();
        $guzzleHttp = new HttpClient();
        $writer = new Http\Writer($guzzleHttp, $options);
        $reader = new Http\Reader($guzzleHttp, $options);
        $client = new Client($reader, $writer);

        $this->client = new Manager($client);
        $this->client->addQuery(new CreateDatabase());
        $this->client->addQuery(new DeleteDatabase());
        $this->client->addQuery(new GetDatabases());

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
