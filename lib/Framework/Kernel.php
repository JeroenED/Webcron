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
    private string $cacheDir;
    private Router $router;
    private ?Connection $dbCon = NULL;

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
    }

    /**
     * @param string $templateDir
     */
    public function setTemplateDir(string $templateDir): void
    {
        $this->templateDir = $templateDir;
    }

    /**
     * @return string
     */
    public function getCacheDir(): string
    {
        return $this->cacheDir;
    }

    /**
     * @param string $cacheDir
     */
    public function setCacheDir(string $cacheDir): void
    {
        $this->cacheDir = $cacheDir;
    }

    /**
     * @return Router
     */
    public function getRouter(): Router
    {
        return $this->router;
    }

    public function handle(): Response
    {
        $this->router = new Router();
        $this->router->parseRoutes($this->getConfigDir(), 'routes.yaml');
        $request = $this->parseRequest();

        if($request->isSecure()) {
            ini_set('session.cookie_httponly', true);
            ini_set('session.cookie_secure', true);
        }

        session_start();
        return $this->router->route($request, $this);
    }

    public function parseDotEnv(string $path): void
    {
        $dotenv = new Dotenv();
        $dotenv->loadEnv($path);
    }

    private function parseRequest(): Request
    {
        Request::setTrustedProxies(explode(',', $_ENV['TRUSTED_PROXIES']), Request::HEADER_X_FORWARDED_FOR | Request::HEADER_X_FORWARDED_HOST | Request::HEADER_X_FORWARDED_PORT | Request::HEADER_X_FORWARDED_PROTO);
        $request = Request::createFromGlobals();
        return $request;
    }

    public function getNewDbCon(): Connection {
        $this->dbCon = DriverManager::getConnection(['url' => $_ENV['DATABASE']]);
        return $this->dbCon;
    }

    public function getDbCon(): Connection
    {
        if(is_null($this->dbCon)) $this->dbCon = DriverManager::getConnection(['url' => $_ENV['DATABASE']]);
        return $this->dbCon;
    }
}