<?php
/**
 * @author     Nick Pope <nick@nickpope.me.uk>
 * @copyright  Copyright © 2010, Nick Pope
 * @license    http://www.apache.org/licenses/LICENSE-2.0  Apache License v2.0
 * @package    Twitter
 */

require_once 'Regex.php';

/**
 * Twitter HitHighlighter Class
 *
 * Performs "hit highlighting" on tweets that have been auto-linked already.
 * Useful with the results returned from the search API.
 *
 * Originally written by {@link http://github.com/mikenz Mike Cochrane}, this
 * is based on code by {@link http://github.com/mzsanford Matt Sanford} and
 * heavily modified by {@link http://github.com/ngnpope Nick Pope}.
 *
 * @author     Nick Pope <nick@nickpope.me.uk>
 * @copyright  Copyright © 2010, Nick Pope
 * @license    http://www.apache.org/licenses/LICENSE-2.0  Apache License v2.0
 * @package    Twitter
 */
class Twitter_HitHighlighter extends Twitter_Regex {

  /**
   * The tag to surround hits with.
   *
   * @var  string
   */
  protected $tag = 'em';

  /**
   * Provides fluent method chaining.
   *
   * @param  string  $tweet        The tweet to be hit highlighted.
   * @param  bool    $full_encode  Whether to encode all special characters.
   *
   * @see  __construct()
   *
   * @return  Twitter_HitHighlighter
   */
  public static function create($tweet, $full_encode = false) {
    return new self($tweet, $full_encode);
  }

  /**
   * Reads in a tweet to be parsed and hit highlighted.
   *
   * We take this opportunity to ensure that we escape user input.
   *
   * @see  htmlspecialchars()
   *
   * @param  string  $tweet        The tweet to be hit highlighted.
   * @param  bool    $escape       Whether to escape the tweet (default: true).
   * @param  bool    $full_encode  Whether to encode all special characters.
   */
  public function __construct($tweet, $escape = true, $full_encode = false) {
    if ($escape) {
      if ($full_encode) {
        parent::__construct(htmlentities($tweet, ENT_QUOTES, 'UTF-8', false));
      } else {
        parent::__construct(htmlspecialchars($tweet, ENT_QUOTES, 'UTF-8', false));
      }
    } else {
      parent::__construct($tweet);
    }
  }

  /**
   * Set the highlighting tag to surround hits with.  The default tag is 'em'.
   *
   * @return  string  The tag name.
   */
  public function getTag() {
    return $this->tag;
  }

  /**
   * Set the highlighting tag to surround hits with.  The default tag is 'em'.
   *
   * @param  string  $v  The tag name.
   *
   * @return  Twitter_HitHighlighter  Fluid method chaining.
   */
  public function setTag($v) {
    $this->tag = $v;
    return $this;
  }

  /**
   * Hit highlights the tweet.
   *
   * @param  array  $hits  An array containing the start and end index pairs
   *                       for the highlighting.
   *
   * @return  string  The hit highlighted tweet.
   */
  public function addHitHighlighting(array $hits) {
    if (empty($hits)) return $this->tweet;
    $tweet = '';
    $tags = array('<'.$this->tag.'>', '</'.$this->tag.'>');
    # Check whether we can simply replace or whether we need to chunk...
    if (strpos($this->tweet, '<') === false) {
      $ti = 0; // tag increment (for added tags)
      $tweet = $this->tweet;
      foreach ($hits as $hit) {
        $tweet = self::mb_substr_replace($tweet, $tags[0], $hit[0] + $ti, 0);
        $ti += mb_strlen($tags[0]);
        $tweet = self::mb_substr_replace($tweet, $tags[1], $hit[1] + $ti, 0);
        $ti += mb_strlen($tags[1]);
      }
    } else {
      $chunks = preg_split('/[<>]/iu', $this->tweet);
      $chunk = $chunks[0];
      $chunk_index = 0;
      $chunk_cursor = 0;
      $offset = 0;
      $start_in_chunk = false;
      # Flatten the multidimensional hits array:
      $hits_flat = array();
      foreach ($hits as $hit) $hits_flat = array_merge($hits_flat, $hit);
      # Loop over the hit indices:
      for ($index = 0; $index < count($hits_flat); $index++) {
        $hit = $hits_flat[$index];
        $tag = $tags[$index % 2];
        $placed = false;
        while ($chunk !== null && $hit >= ($i = $offset + mb_strlen($chunk))) {
          $tweet .= mb_substr($chunk, $chunk_cursor);
          if ($start_in_chunk && $hit === $i) {
            $tweet .= $tag;
            $placed = true;
          }
          if (isset($chunks[$chunk_index+1])) $tweet .= '<' . $chunks[$chunk_index+1] . '>';
          $offset += mb_strlen($chunk);
          $chunk_cursor = 0;
          $chunk_index += 2;
          $chunk = (isset($chunks[$chunk_index]) ? $chunks[$chunk_index] : null);
          $start_in_chunk = false;
        }
        if (!$placed && $chunk !== null) {
          $hit_spot = $hit - $offset;
          $tweet .= mb_substr($chunk, $chunk_cursor, $hit_spot - $chunk_cursor) . $tag;
          $chunk_cursor = $hit_spot;
          $start_in_chunk = ($index % 2 === 0);
          $placed = true;
        }
        # Ultimate fallback - hits that run off the end get a closing tag:
        if (!$placed) $tweet .= $tag;
      }
      if ($chunk !== null) {
        if ($chunk_cursor < mb_strlen($chunk)) {
          $tweet .= mb_substr($chunk, $chunk_cursor);
        }
        for ($index = $chunk_index + 1; $index < count($chunks); $index++) {
          $tweet .= ($index % 2 === 0 ? $chunks[$index] : '<' . $chunks[$index] . '>');
        }
      }
    }
    return $tweet;
  }

  /**
   * A multibyte-aware substring replacement function.
   *
   * @param  string  $string       The string to modify.
   * @param  string  $replacement  The replacement string.
   * @param  int     $start        The start of the replacement.
   * @param  int     $length       The number of characters to replace.
   * @param  string  $encoding     The encoding of the string.
   *
   * @return  string  The modified string.
   *
   * @see http://www.php.net/manual/en/function.substr-replace.php#90146
   */
  protected static function mb_substr_replace($string, $replacement, $start, $length = null, $encoding = null) {
    if (extension_loaded('mbstring') === true) {
      $string_length = (is_null($encoding) === true) ? mb_strlen($string) : mb_strlen($string, $encoding);
      if ($start < 0) {
        $start = max(0, $string_length + $start);
      } else if ($start > $string_length) {
        $start = $string_length;
      }
      if ($length < 0) {
        $length = max(0, $string_length - $start + $length);
      } else if ((is_null($length) === true) || ($length > $string_length)) {
        $length = $string_length;
      }
      if (($start + $length) > $string_length) {
        $length = $string_length - $start;
      }
      if (is_null($encoding) === true) {
        return mb_substr($string, 0, $start) . $replacement . mb_substr($string, $start + $length, $string_length - $start - $length);
      }
      return mb_substr($string, 0, $start, $encoding) . $replacement . mb_substr($string, $start + $length, $string_length - $start - $length, $encoding);
    }
    return (is_null($length) === true) ? substr_replace($string, $replacement, $start) : substr_replace($string, $replacement, $start, $length);
  }

}
