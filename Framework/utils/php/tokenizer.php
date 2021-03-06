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

namespace ClickBlocks\Utils\PHP;

/**
 * The class allows to iterate tokens of php-code.
 *
 * @version 1.0.0
 * @package cb.utils.php
 */
class Tokenizer implements \Iterator
{
  /**
   * Source php-code.
   *
   * @var string $source
   * @access protected
   */
  protected $source = null;
  
  /**
   * Length of the source code.
   *
   * @var integer $length
   * @access protected
   */
  protected $length = null;
  
  /**
   * The current token.
   *
   * @var mixed $token
   * @access protected
   */
  protected $token = null;
  
  /**
   * Position of the current token.
   *
   * @var integer $pos
   * @access protected
   */
  protected $pos = -1;
  
  /**
   * Array of tokens representing heredoc string or double quoted string.
   *
   * @var array $tokens
   * @access private
   */
  private $tokens = [];
  
  /**
   * The number of processed code lines.
   *
   * @var integer $line
   * @access private
   */
  private $line = 1;
  
  /**
   * Position in source code string.
   *
   * @var integer $seek
   * @access private
   */
  private $seek = 0;
  
  /**
   * Returns the number of tokens.
   *
   * @return integer
   * @access public
   * @static
   */
  public static function getTokenCount()
  {
    return T_NS_SEPARATOR - T_REQUIRE_ONCE + 1;
  }
  
  /**
   * Returns TRUE if the both tokens are equal and FALSE otherwise.
   *
   * @param mixed $token1 - the first token for comparison.
   * @param mixed $token2 - the second token for comparison.
   * @return boolean
   * @static
   */
  public static function isEqual($token1, $token2)
  {
    return is_array($token1) && is_array($token2) && $token1[0] == $token2[0] && $token1[1] === $token2[1] || $token1 === $token2;
  }
  
  /**
   * Returns TRUE if the given token is not a comment, whitespace character or an open/close tag. Otherwise, it returns FALSE.
   *
   * @param mixed $token - the token info or token ID.
   * @return boolean
   * @access public
   * @static
   */
  public static function isSemanticToken($token)
  {
    if (is_array($token)) $token = $token[0];
    return $token != T_WHITESPACE && $token != T_DOC_COMMENT && $token != T_COMMENT && $token != T_OPEN_TAG && $token != T_OPEN_TAG_WITH_ECHO && $token != T_CLOSE_TAG;
  }
  
  /**
   * Parses string into tokens. 
   * Returns an array of token identifiers.
   *
   * @param string $code - php-code string.
   * @return array
   * @access public
   * @static
   */
  public static function parse($code)
  {
    if (strtolower(substr($code, 0, 5)) == '<?php' || in_array(substr($code, 0, 2), ['<?', '<%']))
    {
      $tokens = @token_get_all($code);
    }
    else
    {
      $tokens = @token_get_all('<?php ' . $code);
      array_shift($tokens);
    }
    return $tokens;
  }

  /**
   * Constructor. Sets source PHP code to tokenize.
   *
   * @param string $source - PHP code string or file path.
   * @access public
   */
  public function __construct($source)
  {
    $this->source = is_file($source) ? file_get_contents($source) : $source;
    $this->length = strlen($this->source);
    $this->reset();
  }
  
  /**
   * Sets internal pointer to the first character of source code.
   *
   * @access public
   */
  public function reset()
  {
    $this->pos = -1;
    $this->seek = 0;
    $this->line = 1;
    $this->tokens = [];
    $this->next();
  }
  
  /**
   * Returns the current token and move forward to next one.
   * Returns FALSE if the internal pointer points beyond the end of the tokens list.
   *
   * @return mixed
   * @access public
   */
  public function token()
  {
    $token = $this->token;
    $this->next();
    return $token;
  }
  
  /**
   * Returns the current token.
   * Returns FALSE if the internal pointer points beyond the end of the tokens list.
   *
   * @return mixed
   * @access public
   */
  public function current()
  {
    return $this->token;
  }
  
  /**
   * Return the position of the current token.
   * Returns positive integer on success, or -1 on failure.
   *
   * @return integer
   * @access public
   */
  public function key()
  {
    return $this->pos;
  }
  
  /**
   * Checks if current position is valid. 
   * Returns TRUE on success or FALSE on failure.
   *
   * @return boolean
   * @access public
   */
  public function valid()
  {
    return $this->pos > -1;
  }
  
  /**
   * Rewinds the Tokenizer to the first token.
   *
   * @access public
   */
  public function rewind()
  {
    $this->reset();
  }
  
  /**
   * Moves forward to the next token.
   *
   * @access public
   */
  public function next()
  {
    if ($this->seek >= $this->length)
    {
      $this->token = false;
      $this->pos = -1;
      return;
    }
    if (count($this->tokens)) $token = array_shift($this->tokens);
    else
    {
      $token = $this->read();
      if ($token == '"') $token = $this->extractDoubleQuotedString();
      else if (is_array($token) && $token[0] == T_START_HEREDOC) $token = $this->extractHeredocString($token);
    }
    if (is_array($token)) 
    {
      $this->seek += strlen($token[1]);
      $token[2] = $this->line;
      $this->line += substr_count($token[1], "\n");
    }
    else 
    {
      $this->seek += strlen($token);
    }
    $this->token = $token;
    $this->pos++;
  }
  
  /**
   * Extracts and parses heredoc string.
   *
   * @param array $token
   * @return mixed
   * @access private
   */
  private function extractHeredocString(array $token)
  {
    $doc = trim(substr($token[1], 3), "\" \t\n\r\0\x0B");
    if (preg_match('/\s{1}"?' . preg_quote($doc, '/') . '"?;?\s{1}/', $this->source, $matches, PREG_OFFSET_CAPTURE, $this->seek + strlen($token[1])))
    {
      $portion = substr($this->source, $this->seek, $matches[0][1] + strlen($matches[0][0]) - $this->seek);
    }
    else
    {
      $portion = substr($this->source, $this->seek);
    }
    $this->tokens = static::parse($portion);
    array_pop($this->tokens);
    return array_shift($this->tokens);
  }
  
  /**
   * Extracts ans parses double quoted string.
   *
   * @return mixed
   * @access private
   */
  private function extractDoubleQuotedString()
  {
    if (preg_match('/""|".*?[^\\\]{1}(?:\\\\\\\\)*"/s', $this->source, $matches, PREG_OFFSET_CAPTURE, $this->seek))
    {
      $portion = substr($this->source, $this->seek, $matches[0][1] + strlen($matches[0][0]) - $this->seek);
    }
    else
    {
      $portion = substr($this->source, $this->seek);
    }
    $this->tokens = static::parse($portion);
    return array_shift($this->tokens);
  }
  
  /**
   * Returns the next token from the source code.
   *
   * @return mixed
   * @access private   
   */
  private function read()
  {
    $token = ''; $n = 0;
    do
    {
      $n += 8;
      $old = $token;
      $tokens = static::parse(substr($this->source, $this->seek, $n));
      $token = $tokens[0];
    }
    while ($old != $token);
    return $token;
  }
}