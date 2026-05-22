<?php

namespace App\Controllers;

abstract class BaseController
{
    protected function jsonResponse($response, $data, int $status = 200)
    {
        $response->getBody()->write(json_encode($data));
        return $response->withHeader('Content-Type', 'application/json')->withStatus($status);
    }
}
