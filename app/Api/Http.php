<?php
declare(strict_types=1);

namespace App\Api;

use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Promise\EachPromise;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\RequestOptions;
use Illuminate\Support\Facades\Config;
use JetBrains\PhpStorm\ArrayShape;

class Http
{
    private CONST ERROR_UNEXPECTED_RESPONSE = 'Unexpected response from the API';
    private CONST ERROR_CONNECTION = 'There was an error connecting to the API';
    private CONST ERROR_BAD_RESPONSE = 'There was an error interpreting the request';
    private CONST ERROR_BAD_REQUEST = 'There was an error making a request to the API';
    private CONST ERROR_API = 'There was an error connecting to the API';
    private CONST ERROR_AUTHENTICATION = 'Authentication error';

    private static Client $client;

    private static array $headers = [];

    private static Http $instance;
    private static array $error;

    protected static function instance(): Http
    {
        if (isset(self::$instance) === false) {
            self::$instance = new static();
        }

        return self::$instance;
    }

    public static function get(string $uri): ?array
    {
        $result = null;

        if (self::$client !== null) {
            try {
                $response = self::$client->request(
                    'GET',
                    $uri,
                    [RequestOptions::HTTP_ERRORS => false]
                );

                $status_code = $response->getStatusCode();
                if ($status_code === 200) {
                    $result = [
                        'body' => json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR),
                        'headers' => $response->getHeaders()
                    ];
                } else {
                    self::setError(
                        $uri,
                        $status_code,
                        self::ERROR_UNEXPECTED_RESPONSE,
                        null
                    );
                }
            } catch (ConnectException $e) {
                self::setError(
                    $uri,
                    503,
                    self::ERROR_CONNECTION,
                    $e
                );
            } catch (ClientException $e) {
                self::setError(
                    $uri,
                    400,
                    self::ERROR_BAD_RESPONSE,
                    $e
                );
            } catch (Exception $e) {
                self::setError(
                    $uri,
                    500,
                    self::ERROR_API,
                    $e
                );
            }
        }

        return $result;
    }

    public static function getError(): array
    {
        return self::$error;
    }

    public static function getAsyncRequest(
        array $requests,
        int $concurrency = 4
    ): ?array
    {
        $async_results = [];

        $promises = (static function () use ($requests, &$async_results) {
            foreach ($requests as $index => $request) {
                yield self::$client->requestAsync(
                    'GET',
                    $request['uri'],
                    [RequestOptions::HTTP_ERRORS => false]
                );
            }
        })();

        $eachPromise = new EachPromise($promises, [
            'concurrency' => $concurrency,
            'fulfilled' => static function (Response $response, $index) use (&$async_results) {
                if ($response->getStatusCode() === 200) {
                    $async_results[$index]['response']['body'] = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
                    $async_results[$index]['response']['headers'] = $response->getHeaders();
                }
            },
            'rejected' => static function ($reason) {
                // handle promise rejected here, add call to setError later
            }
        ]);

        try {
            $eachPromise->promise()->wait();
        } catch (Exception $e) {
            self::setError(
                'Unable to complete all requests' . var_export($requests),
                400,
                self::ERROR_BAD_REQUEST,
                $e
            );

            return null;
        }

        return $async_results;
    }

    public static function post(string $uri, array $payload): ?array
    {
        $result = null;

        if (self::$client !== null) {

            try {
                $response = self::$client->request(
                    'POST',
                    $uri,
                    [
                        RequestOptions::JSON => $payload,
                        RequestOptions::HTTP_ERRORS => false
                    ]
                );

                $status_code = $response->getStatusCode();

                if ($status_code === 201 || $status_code === 204 || $status_code === 422) {
                    $result = [
                        'status' => $status_code,
                        'content' => ($status_code !== 204 ? json_decode($response->getBody()->getContents(), true) : null)
                    ];
                } else if ($status_code === 401 || $status_code === 403) {
                    self::setError(
                        $uri,
                        $status_code,
                        self::ERROR_AUTHENTICATION,
                        null
                    );
                } else {
                    self::setError(
                        $uri,
                        $status_code,
                        $response->getBody()->getContents(),
                        null
                    );
                }
            } catch (ConnectException $e) {
                self::setError(
                    $uri,
                    503,
                    self::ERROR_CONNECTION,
                    $e
                );
            } catch (ClientException $e) {
                self::setError(
                    $uri,
                    400,
                    self::ERROR_BAD_RESPONSE,
                    $e
                );
            } catch (Exception $e) {
                self::setError(
                    $uri,
                    500,
                    self::ERROR_API,
                    $e
                );
            }
        }

        return $result;
    }

    public static function request(string $bearer = null): Http
    {
        self::instance();

        self::$headers = self::defaultHeaders();
        if ($bearer !== null) {
            self::$headers['Authorization'] = 'Bearer ' . $bearer;
        }

        self::connect();

        return self::$instance;
    }

    private static function setError(
        string $uri,
        int $status_code,
        string $message,
        ?Exception $e
    ): void {
        self::$error = [
            'uri' => $uri,
            'status_code' => $status_code,
            'message' => $message,
            'exception' => [
                'message' => ($e !== null) ? $e->getMessage() : null,
                'trace' => ($e !== null) ? $e->getTraceAsString() : null
            ]
        ];
    }

    private static function connect(): void
    {
        $config = Config::get('app.config');

        $base_uri = $config['api_url'];
        if ($config['dev'] === true) {
            $base_uri = $config['api_url_dev'];
        }

        self::$client = new Client([
            'base_uri' => $base_uri,
            RequestOptions::HEADERS => self::$headers
        ]);
    }

    #[ArrayShape(['Accept' => "string", 'Content-Type' => "string"])]
    private static function defaultHeaders(): array
    {
        return [
            'Accept' => 'application/json',
            'Content-Type' => 'application/json'
        ];
    }
}
