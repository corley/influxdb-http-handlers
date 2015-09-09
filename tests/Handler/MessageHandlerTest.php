<?php
namespace InfluxDB\Handler;

use GuzzleHttp\HandlerStack;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\Psr7\Response;

class MessageHandlerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider data
     */
    public function testQueryWithNoData($request, $response)
    {
        $mock = new MockHandler([
            new Response(200, ["Content-Type" => "application/json"], $request),
        ]);
        $handler = HandlerStack::create($mock);
        $handler->push(message_handler());
        $client = new Client(['handler' => $handler]);

        $this->assertEquals(
            $response,
            (string)$client->request('GET', '/query?q=select+*+from+mem%2Ccpu&db=mydb')->getBody()
        );
    }

    public function data()
    {
        return [
            [
                '{"results":[{}]}',
                'false'
            ],
            [
                '{"results":[{"error":"database not found: mydb2"}]}',
                'false'
            ],
            [
                '{"results":[{"series":[{"name":"cpu","columns":["time","value1","value2","valueS"],"values":[["2015-09-09T20:42:07.927267636Z",1,2,"string"],["2015-09-09T20:42:51.332853369Z",2,4,"another-one"]]}]}]}',
                '{"cpu":[{"time":"2015-09-09T20:42:07.927267636Z","value1":1,"value2":2,"valueS":"string"},{"time":"2015-09-09T20:42:51.332853369Z","value1":2,"value2":4,"valueS":"another-one"}]}'
            ],
            [
                '{"results":[{"series":[{"name":"cpu","columns":["time","free","value1","value2","valueS"],"values":[["2015-09-09T20:42:07.927267636Z",null,1,2,"string"],["2015-09-09T20:42:51.332853369Z",null,2,4,"another-one"]]},{"name":"mem","columns":["time","free","value1","value2","valueS"],"values":[["2015-09-10T22:36:06.19172263Z","1M",null,null,null]]}]}]}',
                '{"cpu":[{"time":"2015-09-09T20:42:07.927267636Z","free":null,"value1":1,"value2":2,"valueS":"string"},{"time":"2015-09-09T20:42:51.332853369Z","free":null,"value1":2,"value2":4,"valueS":"another-one"}],"mem":[{"time":"2015-09-10T22:36:06.19172263Z","free":"1M","value1":null,"value2":null,"valueS":null}]}'
            ],
        ];
    }
}
