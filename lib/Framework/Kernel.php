<?php

namespace JeroenED\Framework;


use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use http\Exception\InvalidArgumentException;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Routing\Loader\YamlFileLoader;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\Matcher\UrlMatcher;
use Symfony\Component\Dotenv\Dotenv;

class Kernel
{
    private string $configDir;
    private string $projectDir;
    private string $templateDir;

    /**
     * @return string
     */
    public function getConfigDir(): string
    {
        return $this->configDir;
    }

    /**
     * @param string $configDir
     */
    public function setConfigDir(string $configDir): void
    {
        $this->configDir = $configDir;
    }

    /**
     * @return string
     */
    public function getProjectDir(): string
    {
        return $this->projectDir;
    }

    /**
     * @param string $projectDir
     */
    public function setProjectDir(string $projectDir): void
    {
        $this->projectDir = $projectDir;
    }

    /**
     * @return string
     */
    public function getTemplateDir(): string
    {
        return $this->templateDir;
    }/**
     * @param string $templateDir
     */
    public function setTemplateDir(string $templateDir): void
    {
        $this->templateDir = $templateDir;
    }

    public function handle(): Response
    {
        $this->parseDotEnv($this->getProjectDir() . '/.env');
        $routes = $this->parseRoutes($this->getConfigDir(), '/routes.yaml');
        $request = $this->parseRequest();
        return $this->createResponse($request, $routes);
    }

    private function parseDotEnv(string $path): void
    {
        $dotenv = new Dotenv();
        $dotenv->loadEnv($path);
    }

    private function parseRoutes(string $dir, string $file): RouteCollection
    {
        $routeloader = new YamlFileLoader(new FileLocator($dir));
        return $routeloader->load($file);
    }

    private function parseRequest(): Request
    {
        return Request::createFromGlobals();
    }

    public function getDbCon(): Connection
    {
        return DriverManager::getConnection(['url' => $_ENV['DATABASE']]);
    }

    private function createResponse($request, $routes): Response
    {
        $requestContext = RequestContext::fromUri($request->getUri());
        $matcher = new UrlMatcher($routes, $requestContext);
        $method = $matcher->match($request->getPathInfo());
        $controller = explode('::', $method['_controller']);
        $controllerObj = new ('\\' . $controller[0])($request, $this);
        $action = $controller[1];
        $response = $controllerObj->$action();

        if ($response instanceof Response) {
            return $response;
        } else {
            throw new InvalidArgumentException();
        }
    }
}