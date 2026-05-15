<?php

namespace App\Middleware;

use App\Models\Account;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Slim\Psr7\Response as SlimResponse;
use Slim\Routing\RouteContext;

class AccountExistsMiddleware
{
    public function __invoke(Request $request, RequestHandler $handler): Response
    {
        $routeContext = RouteContext::fromRequest($request);
        $route = $routeContext->getRoute();
        
        if (empty($route)) {
            return $handler->handle($request);
        }

        $accountId = $route->getArgument('id');
        
        if ($accountId !== null) {
            $account = Account::find((int) $accountId);
            
            if (!$account) {
                $response = new SlimResponse();
                $response->getBody()->write(json_encode(['error' => 'Account not found']));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
            }
            
            $request = $request->withAttribute('account', $account);
        }

        return $handler->handle($request);
    }
}
