<?php


namespace JeroenED\Framework;


use Symfony\Component\Config\FileLocator;
use Symfony\Component\Filesystem\Exception\InvalidArgumentException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGenerator;
use Symfony\Component\Routing\Loader\YamlFileLoader;
use Symfony\Component\Routing\Matcher\UrlMatcher;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\RouteCollection;

class Router
{
    private RouteCollection $routes;
    private RequestContext $requestContext;

    public function route(Request $request, Kernel $kernel): Response
    {
        $requestContext = new RequestContext();
        $this->requestContext = $requestContext->fromRequest($request);
        $matcher = new UrlMatcher($this->routes, $this->requestContext);
        $method = $matcher->match($request->getPathInfo());
        $controller = explode('::', $method['_controller']);
        $controllerObj = new ('\\' . $controller[0])($request, $kernel);
        $action = $controller[1];
        unset($method['_controller']);
        unset($method['_route']);
        $response = $controllerObj->$action(...$method);

        if ($response instanceof Response) {
            $response->headers->add([
                "Content-Security-Policy" => "default-src 'none'; font-src 'self'; style-src 'self'; script-src 'self'; connect-src 'self'; img-src 'self' data:; form-action 'self'; require-trusted-types-for 'script'; frame-ancestors 'none'; base-uri 'none'"
                ]);
            return $response;
        } else {
            throw new InvalidArgumentException();
        }
    }

    public function parseRoutes(string $dir, string $file): void
    {
        $routeloader = new YamlFileLoader(new FileLocator($dir));
        $this->routes =  $routeloader->load($file);
    }

    public function getUrlForRoute(string $route, array $params = []): string
    {
        $matcher = new UrlGenerator($this->routes, $this->requestContext);
        return $matcher->generate($route, $params, UrlGenerator::ABSOLUTE_URL);
    }
}