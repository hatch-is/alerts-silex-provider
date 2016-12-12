<?php

namespace Alerts;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\ClientException as GuzzleClientException;
use GuzzleHttp\Psr7\Request;
use Symfony\Component\Security\Acl\Exception\Exception;

/**
 * Class Processor
 *
 * @package Alerts
 */
class Processor
{
    protected $endpoint;

    /** @var  Filter */
    protected $filter;

    public function __construct($endpoint, $filter)
    {
        if (null === $endpoint) {
            throw new Exception(
                'Alerts service: endpoint is null'
            );
        }

        $this->filter = $filter;
        $this->endpoint = $endpoint;
    }

    public function getAlerts($userId, $filter = [])
    {
        $client = new GuzzleClient();
        $url = sprintf('/alerts?user=%s', $userId);
        if(!empty($filter)) {
            $parsedQuery = $this->filter->parse($filter);
            $url .= '&' . http_build_query($parsedQuery);
        }
        $request = new Request(
            'get',
            $this->getPath(
                $url
            )
        );
        $response = $this->send($client, $request);
        return $response;
    }

    public function getById($id)
    {
        $client = new GuzzleClient();
        $request = new Request(
            'get',
            $this->getPath(
                sprintf('/alerts/%s', $id)
            )
        );
        $response = $this->send($client, $request);
        return $response;
    }

    public function getUnread($userId)
    {
        $client = new GuzzleClient();
        $request = new Request(
            'get',
            $this->getPath(
                sprintf('/alerts/unread?user=%s', $userId)
            )
        );
        $response = $this->send($client, $request);
        return $response;
    }

    public function markAlertsAsReadByDateTime($dateTime, $userId)
    {
        $client = new GuzzleClient();
        $request = new Request(
            'post',
            $this->getPath('/alerts/read/date'),
            ['content-type' => 'application/json'],
            json_encode(
                [
                    'dateTime' => $dateTime,
                    'user'     => $userId
                ]
            )
        );
        $response = $this->send($client, $request);
        return $response;
    }

    public function markAlertsAsRead($alerts, $userId)
    {
        $client = new GuzzleClient();
        $request = new Request(
            'post',
            $this->getPath('/alerts/read'),
            ['content-type' => 'application/json'],
            json_encode(
                [
                    'alerts' => $alerts,
                    'user'   => $userId
                ]
            )
        );
        $response = $this->send($client, $request);
        return $response;
    }

    public function getSegmentationCount($segmentation)
    {
        $client = new GuzzleClient();
        $request = new Request(
            'post',
            $this->getPath('/segments/count'),
            ['content-type' => 'application/json'],
            json_encode($segmentation)
        );
        $response = $this->send($client, $request);
        return $response;
    }

    protected function getPath($path)
    {
        return $this->endpoint . $path;
    }

    /**
     * @param GuzzleClient $client
     * @param Request      $request
     *
     * @return \Psr\Http\Message\StreamInterface
     * @throws \Exception
     */
    public function send(GuzzleClient $client, Request $request)
    {
        try {
            $response = $client->send($request);
            $data = [
                'body'       => json_decode($response->getBody(), true),
                'headers'    => [],
                'statusCode' => $response->getStatusCode()
            ];

            if (!empty($total = $response->getHeader('X-Total-Count'))) {
                $data['headers']['X-Total-Count'] = $total;
            }
            if (!empty($rate = $response->getHeader('X-Ratelimit-Remaining'))) {
                $data['headers']['X-Ratelimit-Remaining'] = $rate;
            }
            return $data;
        } catch (GuzzleClientException $e) {
            $message = $this->formatErrorMessage($e);
            throw new \Exception(json_encode($message), 0, $e);
        }
    }

    /**
     * @param GuzzleClientException $httpException
     *
     * @return array
     */
    protected function formatErrorMessage($httpException)
    {
        $message = [
            'message'  => 'Something bad happened with alerts service',
            'request'  => [
                'headers' => $httpException->getRequest()->getHeaders(),
                'body'    => $httpException->getRequest()->getBody()
            ],
            'response' => [
                'headers' => $httpException->getResponse()->getHeaders(),
                'body'    => $httpException->getResponse()->getBody()
                    ->getContents(),
                'status'  => $httpException->getResponse()->getStatusCode()
            ]
        ];

        return $message;
    }
}
