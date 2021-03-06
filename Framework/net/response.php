<?php
/**
 * ClickBlocks.PHP v. 1.0
 *
 * Copyright (C) 2014  SARITASA LLC
 * http://www.saritasa.com
 *
 * This framework is free software. You can redistribute it and/or modify
 * it under the terms of either the current ClickBlocks.PHP License
 * viewable at theclickblocks.com) or the License that was distributed with
 * this file.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY, without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 *
 * You should have received a copy of the ClickBlocks.PHP License
 * along with this program.
 *
 * @copyright  2007-2014 SARITASA LLC <info@saritasa.com>
 * @link       http://www.saritasa.com
 */
 
namespace ClickBlocks\Net;

/**
 * Response Class provides easier interaction with variables of the current HTTP response and to build a new response.
 *
 * @version 1.0.0
 * @package cb.net
 */
class Response
{
  /**
   * Error message templates throwing by Response class.
   */
  const ERR_RESPONSE_1 = 'Invalid HTTP version. Must be only 1.0 or 1.1';
  const ERR_RESPONSE_2 = 'Cannot set response status. Status code "[{var}]" is not a valid HTTP response code.';
  
  /**
   * The instance of this class.
   * 
   * @var ClickBlocks\Net\Response $instance
   * @access private
   */           
  private static $instance = null;

  /**
   * HTTP status codes.
   *
   * @var array $codes
   * @access protected
   * @static
   */
  protected static $codes = [100 => 'Continue',
                             101 => 'Switching Protocols',
                             102 => 'Processing',
                             103 => 'Checkpoint',
                             122 => 'Request-URI too long',
                             200 => 'OK',
                             201 => 'Created',
                             202 => 'Accepted',
                             203 => 'Non-Authoritative Information',
                             204 => 'No Content',
                             205 => 'Reset Content',
                             206 => 'Partial Content',
                             207 => 'Multi-Status',
                             208 => 'Already Reported',
                             226 => 'IM Used',
                             300 => 'Multiple Choices',
                             301 => 'Moved Permanently',
                             302 => 'Found',
                             303 => 'See Other',
                             304 => 'Not Modified',
                             305 => 'Use Proxy',
                             306 => 'Switch Proxy',
                             307 => 'Temporary Redirect',
                             308 => 'Resume Incomplete',
                             400 => 'Bad Request',
                             401 => 'Unauthorized',
                             402 => 'Payment Required',
                             403 => 'Forbidden',
                             404 => 'Not Found',
                             405 => 'Method Not Allowed',
                             406 => 'Not Acceptable',
                             407 => 'Proxy Authentication Required',
                             408 => 'Request Timeout',
                             409 => 'Conflict',
                             410 => 'Gone',
                             411 => 'Length Required',
                             412 => 'Precondition Failed',
                             413 => 'Request Entity Too Large',
                             414 => 'Request-URI Too Long',
                             415 => 'Unsupported Media Type',
                             416 => 'Requested Range Not Satisfiable',
                             417 => 'Expectation Failed',
                             418 => 'I\'m a teapot',
                             420 => 'Enhance Your Calm',
                             422 => 'Unprocessable Entity',
                             423 => 'Locked',
                             424 => 'Failed Dependency',
                             425 => 'Unordered Collection',
                             426 => 'Upgrade Required',
                             428 => 'Precondition Required',
                             429 => 'Too Many Requests',
                             431 => 'Request Header Fields Too Large',
                             444 => 'No Response',
                             449 => 'Retry With',
                             450 => 'Blocked by Windows Parental Controls',
                             499 => 'Client Closed Request',
                             500 => 'Internal Server Error',
                             501 => 'Not Implemented',
                             502 => 'Bad Gateway',
                             503 => 'Service Unavailable',
                             504 => 'Gateway Timeout',
                             505 => 'HTTP Version Not Supported',
                             506 => 'Variant Also Negotiates',
                             507 => 'Insufficient Storage',
                             508 => 'Loop Detected',
                             509 => 'Bandwidth Limit Exceeded',
                             510 => 'Not Extended',
                             511 => 'Network Authentication Required',
                             598 => 'Network read timeout error',
                             599 => 'Network connect timeout error'];

  /**
   * Instance of ClickBlocks\Net\Headers class.
   *
   * @var ClickBlocks\Net\Headers $headers
   * @access public
   */
  public $headers = null;
  
  /**
   * Version of HTTP protocol.
   *
   * @var string $version
   * @access public
   */
  public $version = null;
  
  /**
   * Status of HTTP response.
   *
   * @var integer $status
   * @access public
   */
  public $status = null;
  
  /**
   * Body of HTTP resposne.
   *
   * @var mixed $body
   * @access public
   */
  public $body = null;
  
  /**
   * Returns an instance of this class.
   * 
   * @return Response
   * @access public
   * @static
   */
  public static function getInstance()
  {
    if (self::$instance === null) self::$instance = new self();
    return self::$instance;
  }
  
  /**
   * Clones an object of this class. The private method '__clone' doesn't allow to clone an instance of the class.
   * 
   * @access private
   */
  private function __clone(){}
  
  /**
   * Constructor. Initializes all properties of the class with values from PHP's super globals.
   *
   * @access public
   */
  private function __construct()
  {
    $this->reset();
  }
  
  /**
   * Returns an HTTP status message by its code. 
   * If such message doesn't exist the method returns FALSE.
   * 
   * @param integer $status
   * @return string | boolean
   * @access public
   * @static
   */
  public static function getMessage($status)
  {
    return isset(static::$codes[$status]) ? static::$codes[$status] : false;
  }
  
  /**
   * Marks the response as "private".
   * It makes the response ineligible for serving other clients.
   *
   * @access public
   */
  public function markAsPrivate()
  {
    $this->headers->removeCacheControlDirective('public');
    $this->headers->setCacheControlDirective('private');
  }

  /**
   * Marks the response as "public".
   * It makes the response eligible for serving other clients.
   *
   * @access public
   */
  public function markAsPublic()
  {
    $this->headers->setCacheControlDirective('public');
    $this->headers->removeCacheControlDirective('private');
  }
  
  /**
   * Modifies the response so that it conforms to the rules defined for a 304 status code.
   * This sets the status, removes the body, and discards any headers that MUST NOT be included in 304 responses.
   *
   * @access public
   */
  public function markAsNotModified()
  {
    $this->status = 304;
    $this->body = null;
    foreach (['Allow', 'Content-Encoding', 'Content-Language', 'Content-Length', 'Content-MD5', 'Content-Type', 'Last-Modified'] as $header) $this->headers->remove($header);
  }
  
  /**
   * Returns the content type and/or response charset.
   *
   * @param boolean $withCharset - if TRUE the method returns an array of the following structure ['type' => ..., 'charset' => ...], otherwise only content type will be returned.
   * @return string
   * @access public
   */
  public function getContentType($withCharset = false)
  {
    return $this->headers->getContentType($withCharset);
  }
  
  /**
   * Sets content type header. You can use content type alias instead of some HTTP headers (that are determined by $contentTypeMap property).
   *
   * @param string $type - content type or its alias.
   * @param string $charset - the content charset.
   * @access public
   */
  public function setContentType($type, $charset = null)
  {
    $this->headers->setContentType($type, $charset);
  }
  
  /**
   * Returns the response charset.
   * If the Content-Type header with charset is not set the method returns NULL.
   *
   * @return string
   * @access public
   */
  public function getCharset()
  {
    return $this->headers->getCharset();
  }
  
  /**
   * Sets the response charset.
   *
   * @param string $charset - the response charset.
   * @access public
   */
  public function setCharset($charset = 'UTF-8')
  {
    $this->headers->setCharset($charset);
  }
  
  /**
   * Returns the "Date" header of the response as a DateTime instance.
   * If the "Date" header is not set or not parseable the method returns a DateTime object that represents the current time.
   *
   * @return \DateTime
   * @access public
   */
  public function getDate()
  {
    if (false !== $date = $this->headers->getDate('Date')) return $date;
    return new \DateTime('now', new \DateTimeZone('UTC'));
  }
  
  /**
   * Sets the "Date" header value.
   *
   * @param \DateTime | string $date - the "Date" header value.
   * @access public
   */
  public function setDate($date = 'now')
  {
    $this->headers->setDate('Date', $date);
  }
  
  /**
   * Returns the value of the "Expires" header as a DateTime instance.
   *
   * @return \DateTime
   * @access public
   */
  public function getExpires()
  {
    if (false !== $date = $this->headers->getDate('Expires')) return $date;
    return \DateTime::createFromFormat(DATE_RFC2822, 'Sun, 3 Jan 1982 21:30:00 +0000');
  }
  
  /**
   * Sets the "Expires" header value.
   * Passing null as value will remove the header.
   *
   * @param \DateTime | string $date - the "Expires" header value.
   * @access public
   */
  public function setExpires($date = null)
  {
    if ($date === null) $this->headers->remove('Expires');
    else $this->headers->setDate('Expires', $date);
  }
  
  /**
   * Returns the Last-Modified HTTP header as a DateTime instance.
   * The method returns FALSE if the header does not exist.
   *
   * @return \DateTime | boolean.
   */
  public function getLastModified()
  {
    return $this->headers->getDate('Last-Modified');
  }
  
  /**
   * Sets the Last-Modified HTTP header with a DateTime instance.
   * Passing null as value will remove the header.
   *
   * @param \DateTime | string $date - a \DateTime instance or null to remove the header.
   * @access public
   */
  public function setLastModified($date = null)
  {
    if ($date === null) $this->headers->remove('Last-Modified');
    else $this->headers->setDate('Last-Modified', $date);
  }
  
  /**
   * Returns amount of time (in seconds) since the response (or its revalidation) was generated at the origin server.
   *
   * @return integer
   * @access public
   */
  public function getAge()
  {
    if (false !== $age = $this->headers->get('Age')) return (int)$age;
    return max(time() - $this->getDate()->format('U'), 0);
  }
  
  /**
   * Returns the number of seconds after the time specified in the response's Date
   * header when the response should no longer be considered fresh.
   *
   * @return integer
   * @access public
   */
  public function getMaxAge()
  {
    if ($this->headers->hasCacheControlDirective('s-maxage')) return (int)$this->headers->getCacheControlDirective('s-maxage');
    if ($this->headers->hasCacheControlDirective('max-age')) return (int)$this->headers->getCacheControlDirective('max-age');
    return $this->getExpires()->format('U') - $this->getDate()->format('U');
  }
  
  /**
   * Sets the number of seconds after which the response should no longer be considered fresh.
   * This methods sets the Cache-Control max-age directive.
   *
   * @param integer $value - the number of seconds.
   * @access public
   */
  public function setMaxAge($value)
  {
    $this->headers->setCacheControlDirective('max-age', (int)$value);
  }
  
  /**
   * Sets the number of seconds after which the response should no longer be considered fresh by shared caches.
   * This methods sets the Cache-Control s-maxage directive.
   *
   * @param integer $value - the number of seconds.
   * @access public
   */
  public function setSharedMaxAge($value)
  {
    $this->markAsPublic();
    $this->headers->setCacheControlDirective('s-maxage', (int)$value);
  }
  
  /**
   * Returns the response's time-to-live in seconds.
   *
   * @return integer
   */
  public function getTTL()
  {
    return $this->getMaxAge() - $this->getAge();
  }
  
  /**
   * Sets the response's time-to-live for shared caches.
   * This method adjusts the Cache-Control/s-maxage directive.
   *
   * @param integer $value - the number of seconds.
   * @access public
   */
  public function setTTL($value)
  {
    $this->setSharedMaxAge($this->getAge() + $value);
  }
  
  /**
   * Sets the response's time-to-live for private/client caches.
   * This method adjusts the Cache-Control/max-age directive.
   *
   * @param integer $value - the number of seconds.
   * @access public
   */
  public function setClientTTL($value)
  {
    $this->setMaxAge($this->getAge() + $value);
  }
  
  /**
   * Returns the literal value of the ETag HTTP header.
   *
   * @return string
   * @access public
   */
  public function getEtag()
  {
    return $this->headers->get('ETag');
  }

  /**
   * Sets the ETag value.
   *
   * @param string $etag - the ETag unique identifier or null to remove the header.
   * @param boolean $weak - determines whether you want a weak ETag or not.
   *
   * @access public
   */
  public function setEtag($etag = null, $weak = false)
  {
    if ($etag == null) $this->headers->remove('ETag');
    else
    {
      if (strlen($etag) == 0 || $etag[0] != '"') $etag = '"' . $etag . '"';
      $this->headers->set('ETag', ($weak ? 'W/' : '') . $etag);
    }
  }
  
  /**
   * Initializes all properties of the class with values from PHP's super globals.
   *
   * @access public
   */
  public function reset()
  {
    $this->headers = Headers::getResponseHeaders();
    $this->status = 200;
    $this->version = '1.1';
    $this->body = null;
  }
  
  /**
   * Clears all variables of the current HTTP response.
   * This method removes all HTTP headers and body, sets HTTP protocol version to '1.1' and status code to 200.
   *
   * @return self
   * @access public
   */
  public function clean()
  {
    $this->headers->clean();
    $this->version = '1.1';
    $this->status = 200;
    $this->body = null;
  }
  
  /**
   * Sets cache expire for a response.
   * If cache expire is FALSE or equals 0 then no cache will be set.
   * 
   * @param integer | boolean $expires - new cache expire time in seconds.
   * @access public   
   */
  public function cache($expires) 
  {
    if ($expires === false || (int)$expires <= 0) 
    {
      $this->headers->merge(['Expires' => 'Sun, 3 Jan 1982 21:30:00 GMT',
                             'Cache-Control' => 'no-store, no-cache, must-revalidate, post-check=0, pre-check=0, max-age=0',
                             'Pragma' => 'no-cache']);
    }
    else
    {
      $expires = (int)$expires;
      $this->headers->set('Expires', gmdate('D, d M Y H:i:s', $expires) . ' GMT');
      $this->headers->set('Cache-Control', 'max-age=' . ($expires - time()));
    }
  }
  
  /**
   * Performs an HTTP redirect. Execution of this method halts the script execution. 
   *
   * @param string $url - redirect URL.
   * @param integer $status - redirect HTTP status code.
   * @access public
   */
  public function redirect($url, $status = 302)
  {
    $this->status = (int)$status;
    $this->headers->set('Location', $url);
    $this->send();
    exit;
  }
  
  /**
   * Stops the script execution with some message and HTTP status code.
   *
   * @param integer $status - status of an HTTP response.
   * @param string $message - some text.
   * @access public
   */
  public function stop($status = 500, $message = null)
  {
    if ($message !== null) $this->body = $message;
    $this->status = (int)$status;
    $this->send();
    exit;
  }
  
  /**
   * Downloads the given file.
   *
   * @param string $path - the full path to the downloading file.
   * @param string $filename - the name of the downloading file.
   * @param string $contentType - the mime type of the downloading file.
   * @param boolean $deleteAfterDownload - determines whether the file should be deleted after download.
   * @access public
   */
  public function download($path, $filename = null, $contentType = null, $deleteAfterDownload = false)
  {
    if (!$filename) $filename = basename($path);
    if (!$contentType) $contentType = function_exists('mime_content_type') ? mime_content_type($path) : 'application/octet-stream';
    $this->body = null;
    $this->cache(false);
    $this->setContentType($contentType);
    $this->headers->removeCacheControlDirective('private');
    $this->headers->set('Content-Disposition', 'attachment; filename="' . $filename . '"');
    $this->headers->set('Content-Transfer-Encoding', 'binary');
    $this->headers->set('Content-Length', filesize($path));
    $this->send();
    readfile($path);
    if ($deleteAfterDownload) unlink($path);
    exit;
  }
  
  /**
   * Sends all HTTP response headers.
   *
   * @throws ClickBlocks\Core\Exception with token ERR_RESPONSE_1 if new version value doesn't equal '1.0' or '1.1'
   * @throws ClickBlocks\Core\Exception with token ERR_RESPONSE_2 if a such status code doesn't exist.
   * @access public
   */
  public function send()
  {  
    $request = Request::getInstance();
    if ($this->headers->has('Transfer-Encoding')) $this->headers->remove('Content-Length');
    if ($request->method == 'HEAD' || $this->status >= 100 && $this->status < 200 || $this->status == 204 || $this->status == 304) $this->body = null;
    if (empty($this->version)) $this->version = empty($request->server['SERVER_PROTOCOL']) || $request->server['SERVER_PROTOCOL'] != 'HTTP/1.0' ? '1.1' : '1.0';
    if ($this->version != '1.0' && $this->version != '1.1') throw new Core\Exception($this, 'ERR_RESPONSE_1');
    if (empty($this->status)) $this->status = 200;
    if (!isset(self::$codes[$this->status])) throw new Core\Exception($this, 'ERR_RESPONSE_2', $this->status);
    if (!headers_sent())
    {
      $headers = $this->headers->getHeaders();
      header('HTTP/' . $this->version . ' ' . $this->status . ' ' . self::$codes[$this->status]);
      foreach ($headers as $name => $value) header($name . ': ' . $value);
    }
    \CB::setOutput($this->body);
  }
}