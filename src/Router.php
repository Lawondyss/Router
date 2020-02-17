<?php
/**
 * @author Ladislav Vondráček <lad.von@gmail.com>
 */

namespace Lawondyss\Sandy\Router;

use Lawondyss\Sandy\Router\Http\Request;
use Lawondyss\Sandy\Router\Http\Response;
use Lawondyss\Sandy\Router\Http\Uri;
use Nette\SmartObject;
use Nette\Utils\Strings;

class Router
{
  use SmartObject;

  public const
    GET = 2,
    POST = 4,
    PUT = 8,
    DELETE = 16,
    HEAD = 32,
    PATCH = 64,
    OPTIONS = 128;

  private const METHODS_MAP = [
    self::GET => Request::GET,
    self::POST => Request::POST,
    self::PUT => Request::PUT,
    self::DELETE => Request::DELETE,
    self::HEAD => Request::HEAD,
    self::PATCH => Request::PATCH,
    self::OPTIONS => Request::OPTIONS,
  ];

  private array $routes = [];

  private string $origin;

  private Response $response;

  private Request $request;


  public function __construct(string $origin = '*')
  {
    $this->origin = $origin;
    $this->response = new Response;
    $this->request = Request::create();
  }


  private function isOptions(): bool
  {
    if (($this->request->method === Request::OPTIONS) && $this->checkOrigin()) {
      $this->response->addHeader('Access-Control-Max-Age', 1728000)
                     ->addHeader('Content-Length', 0);
      $this->response->contentType = 'text/plain';
      $this->response->code = Response::S200_OK;

      return true;
    }

    $this->response->addHeader('Access-Control-Max-Age', 3600)
                   ->addHeader('Access-Control-Allow-Headers', 'Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With');

    return false;
  }


  private function checkOrigin(): bool
  {
    if ($this->origin !== '*' && $this->request->origin === $this->origin) {
      $this->response->addHeader('HTTP/1.1 403 Access Forbidden');
      $this->response->contentType = 'text/plain';
      $this->response->code = Response::S403_FORBIDDEN;

      return false;
    }

    return true;
  }


  public function __destruct()
  {
    $this->response->addHeader('Access-Control-Allow-Origin', $this->origin);
    $this->response->addHeader('Access-Control-Allow-Headers', 'Authorization, Origin, X-Requested-With, Content-Type, Accept');

    if(!$this->isOptions()) {
      $found = false;
      foreach ($this->routes as $flags => $routes) {
        if (($flags & $this->request->flag) === $this->request->flag) {
          foreach ($routes as $mask => $callback) {
            $params = $this->paramsFromUri($mask, $this->request->uri);
            if (isset($params)) {
              $found = true;
              $this->request->addParams($params);
              $callback($this->request, $this->response);
              break;
            }
          }
        }
      }

      if ($found === false) {
        $this->response->code = Response::S404_NOT_FOUND;
      }
    }

    $this->response->addHeader('Access-Control-Allow-Methods', implode(', ', $this->allowedMethods()));
    $this->response->send();
  }


  public function add(int $flags, string $mask, callable $callback): void
  {
    $this->routes[$flags][$mask] = $callback;
  }


  public function get(string $mask, callable $callback): void
  {
    $this->add(self::GET, $mask, $callback);
  }


  public function post(string $mask, callable $callback): void
  {
    $this->add(self::POST, $mask, $callback);
  }


  private function allowedMethods(): array
  {
    $allowMethods = [];
    foreach ($this->routes as $routeFlag => $route) {
      if (in_array($routeFlag, self::METHODS_MAP)) {
        $allowMethods[] = self::METHODS_MAP[$routeFlag];
        continue;
      }

      foreach (self::METHODS_MAP as $methodFlag => $method) {
        if (($routeFlag & $methodFlag) === $methodFlag) {
          $allowMethods[] = $method;
        }
      }
    }

    return $allowMethods;
  }


  private function paramsFromUri(string $mask, Uri $uri): ?array
  {
    if (!$this->hasBrackets($mask) && $mask !== $uri->path) {
      return null;
    }

    $segments = Strings::split($mask, '~\{([^{}:]+)(=[^{}:]*)? *([^{}]*)\}|(\[|\])~');
    $segments = array_values(array_filter($segments, fn($part) => $part !== ''));

    $index = count($segments) - 1;
    $brackets = 0; // level of optionals segments
    $regex = '';

    do {
      $segment = $segments[$index];
      if (strpbrk($segment, '{}') !== false) {
        throw new RouterException(sprintf('Unexpected "%s" in mask "%s"', $segment, $mask));
      }

      if (Strings::startsWith($segment, '/')) {
        $regex = preg_quote($segment, '/') . $regex;
      } elseif ($segment === '[') {
        $brackets++;
        $regex = '(?:' . $regex;
      } elseif ($segment === ']') {
        $brackets--;
        $regex = ')?' . $regex;
      } elseif (Strings::startsWith($segment, ':')) {
        $pattern = Strings::after($segment, ':');
        $name = $segments[--$index];
        $regex = '(?<' . preg_quote($name, '#') . ">$pattern)" . $regex;
      } else {
        $regex = '(?<' . $segment . '>[^' . preg_quote('/', '/') . ']+)' . $regex;
      }
      if ($index === 0) {
        break;
      }
      $index--;
    } while (true);

    if ($brackets !== 0) {
      throw new RouterException(sprintf('Missing "[" or "]" in mask "%s"', $mask));
    }

    $values = Strings::match($uri->path, '~' . $regex . '~');
    $values = array_filter($values, fn($key) => !is_int($key), ARRAY_FILTER_USE_KEY);
    if (!isset($values)) {
      return null;
    }

    return $values;
  }


  private function hasBrackets(string $mask): bool
  {
    return Strings::match($mask, '~\{(.+)\}~') !== null;
  }
}
