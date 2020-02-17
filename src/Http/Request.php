<?php
/**
 * @author Ladislav Vondráček <lad.von@gmail.com>
 */

namespace Lawondyss\Sandy\Router\Http;

use Lawondyss\Sandy\Router\Router;
use Nette\SmartObject;

class Request
{
  use SmartObject;

  public const
    GET = 'GET',
    POST = 'POST',
    PUT = 'PUT',
    DELETE = 'DELETE',
    HEAD = 'HEAD',
    PATCH = 'PATCH',
    OPTIONS = 'OPTIONS';

  public string $method;

  public Uri $uri;

  public ?string $origin;

  public array $params = [];

  public int $flag;


  public function __construct(string $method, Uri $uri, ?string $origin)
  {
    $this->method = $method;
    $this->uri = $uri;
    $this->origin = $origin;

    $this->flag = constant(Router::class . '::' . $method);
  }


  public static function create(): self
  {
    return new static($_SERVER['REQUEST_METHOD'] ?? self::GET, Uri::create(), $_SERVER['HTTP_ORIGIN'] ?? null);
  }


  public function addParams(array $params): self
  {
    foreach ($params as $name => $value) {
      $this->params[$name] = $value;
    }

    return $this;
  }


  public function hasParam(string $name): bool
  {
    return isset($this->params[$name]);
  }
}
