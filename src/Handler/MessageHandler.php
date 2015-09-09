<?php
namespace InfluxDB\Handler;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use GuzzleHttp\Psr7\Response;

function message_handler()
{
    return function (callable $handler) {
        return function (RequestInterface $request, array $options) use ($handler) {
            $promise = $handler($request, $options);
            return $promise->then(
                function (ResponseInterface $response) use ($request) {
                    $body = json_decode($response->getBody(), true);
                    $parsed = false;
                    if (array_key_exists("series", $body["results"][0])) {
                        $parsed = [];
                        foreach ($body["results"][0]["series"] as $results) {
                            $name = $results["name"];
                            $parsed[$name] = [];
                            foreach ($results["values"] as $values) {
                                $parsed[$name][] = array_combine($results["columns"], $values);
                            }
                        }
                    }
                    return new Response($response->getStatusCode(), $response->getHeaders(), json_encode($parsed));
                }
            );
        };
    };
}
