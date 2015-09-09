<?php
namespace InfluxDB\Handler;

use GuzzleHttp\HandlerStack;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\Psr7\Response;

class ExceptionHandlerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @expectedException UnexpectedValueException
     */
    public function testQueryWithNoData()
    {
        $mock = new MockHandler([
            new Response(
                200,
                ["Content-Type" => "application/json"],
                '{"results":[{"error":"database not found: mydb2"}]}'
            ),
        ]);
        $handler = HandlerStack::create($mock);
        $handler->push(exception_handler());
        $client = new Client(['handler' => $handler]);

        $client->request('GET', '/query?q=select+*+from+mem%2Ccpu&db=mydb')->getBody();
    }
}

