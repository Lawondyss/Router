<?php
/**
 * @author Ladislav Vondráček <lad.von@gmail.com>
 */

namespace Lawondyss\Sandy\Router\Http;

use Lawondyss\Sandy\Router\UriException;
use Nette\SmartObject;
use Nette\Utils\ArrayHash;

class Uri
{
  use SmartObject;

  public string $scheme;

  public string $host;

  public ?int $port;

  public ?string $user;

  public ?string $password;

  public string $path;

  public ArrayHash $query;

  public ?string $fragment = null;


  public static function create(): self
  {
    $uri = ($_SERVER['HTTP_HOST'] ?? '') . ($_SERVER['REQUEST_URI'] ?? '/');
    if (parse_url($uri) === false) {
      throw new UriException(sprintf('URL is malformed, given: %s', $uri));
    }

    parse_str(parse_url($uri, PHP_URL_QUERY) ?: '', $query);

    $obj = new static;
    $obj->scheme = parse_url($uri, PHP_URL_SCHEME) ?? 'http';
    $obj->host = parse_url($uri, PHP_URL_HOST) ?? '';
    $obj->port = parse_url($uri, PHP_URL_PORT);
    $obj->user = parse_url($uri, PHP_URL_USER);
    $obj->password = parse_url($uri, PHP_URL_PASS);
    $obj->path = parse_url($uri, PHP_URL_PATH);
    $obj->query = ArrayHash::from($query);
    $obj->fragment = parse_url($uri, PHP_URL_FRAGMENT);

    return $obj;
  }


  public function hasQuery(?string $name): bool
  {
    return isset($this->query->$name);
  }
}
