<?php

namespace Tests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Symfony\Component\HttpFoundation\Request as SymfonyRequest;

trait ValidatesRequests
{
    protected function createRequest(string $requestClass, $headers = [])
    {
        $symfonyRequest = SymfonyRequest::create(
            $this->prepareUrlForRequest('/test/route'),
            'POST',
            [],
            $this->prepareCookiesForRequest(),
            [],
            array_replace($this->serverVariables, $this->transformHeadersToServerVars($headers))
        );

        $formRequest = FormRequest::createFrom(
            Request::createFromBase($symfonyRequest),
            new $requestClass
        )->setContainer($this->app);

        $route = new Route('POST', '/test/route', fn () => null);
        $route->parameters = [];
        $formRequest->setRouteResolver(fn () => $route);

        return new TestFormRequest($formRequest);
    }
}
