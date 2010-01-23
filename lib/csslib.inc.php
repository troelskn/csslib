<?php

/**
 * A CSS parser.
 * This parses the source and computes the rules, according to specifications.
 * The main entrypoint is `parseString`
 */
class csslib_CssParser {
  protected $grammar_parser;
  protected $definitions = array();
  function __construct() {
    $this->grammar_parser = new csslib_CssGrammarParser();
  }
  /**
   * Parses a string into an array of definitions.
   *
   * The result is a struct resembling:
   *
   *     array(
   *       selector(string) => array(
   *           name(string) => value(string),
   *           name(string) => value(string),
   *           ..
   *       )
   *     )
   */
  function parseString($input) {
    foreach ($this->grammar_parser->parseString($input) as $block) {
      $definitions = $this->expandProperties($block['definitions']);
      foreach ($block['selectors'] as $selector) {
        if (isset($this->definitions[$selector])) {
          $this->definitions[$selector] = array_merge($this->definitions[$selector], $definitions);
        } else {
          $this->definitions[$selector] = $definitions;
        }
      }
    }
    return $this->sortBySpecificity($this->definitions);
  }
  function sortBySpecificity($definitions) {
    $keys = array_keys($definitions);
    usort($keys, array($this, '_compare'));
    $result = array();
    foreach ($keys as $key) {
      $result[$key] = $definitions[$key];
    }
    return $result;
  }
  function _compare($a, $b) {
    $spec_a = $this->getSpecificity($a);
    $spec_b = $this->getSpecificity($b);
    if ($spec_a === $spec_b) {
        return 0;
    }
    return ($spec_a > $spec_b) ? -1 : 1;
  }
  function getSpecificity($selector) {
    $value = 0;
    foreach (explode(' ', $selector) as $token) {
      $token = trim($token);
      if ($token[0] === '#') {
        $value += 100;
      } else if ($token[0] === '.') {
        $value += 10;
      } else if (preg_match('~^\w+#\w+$~', $token)) {
        $value += 101;
      } else if (preg_match('~^\w+\.\w+$~', $token)) {
        $value += 11;
      } else {
        $value += 1;
      }
    }
    return $value;
  }
  function expandProperties($properties) {
    $result = array();
    foreach ($properties as $property => $value) {
      switch ($property) {
      case 'background':
        foreach ($this->expandValues($value, 'background', array('color', 'image', 'repeat', 'attachment', 'position')) as $k => $v) {
          $result[$k] = $v;
        }
        break;
      case 'border':
      case 'border-bottom':
      case 'border-top':
      case 'border-left':
      case 'border-right':
        foreach ($this->expandValues($value, $property, array('width', 'style', 'color')) as $k => $v) {
          $result[$k] = $v;
        }
        break;
      case 'outline':
        foreach ($this->expandValues($value, 'outline', array('color', 'style', 'width')) as $k => $v) {
          $result[$k] = $v;
        }
        break;
      case 'font':
        foreach ($this->expandValues($value, 'font', array('style', 'variant', 'weight', 'size', 'family')) as $k => $v) {
          if ($k === 'font-size') {
            $parts = explode('/', $v);
            if (count($parts) === 2) {
              $v = $parts[0];
              $result['line-height'] = $parts[1];
            }
          }
          $result[$k] = $v;
        }
        break;
      case 'list-style':
        foreach ($this->expandValues($value, 'list-style', array('type', 'position', 'image')) as $k => $v) {
          $result[$k] = $v;
        }
        break;
      case 'margin':
      case 'padding':
        foreach ($this->expandBoxmodel($value, $property) as $k => $v) {
          $result[$k] = $v;
        }
        break;
      default:
        $result[$property] = $value;
        break;
      }
    }
    return $result;
  }
  function expandValues($input, $main_name, $sub_names = array()) {
    $result = array();
    $values = explode(' ', $input);
    for ($i=0,$l=count($sub_names); $i < $l; ++$i) {
      $value = trim($values[$i]);
      if ($value) {
        $result[$main_name.'-'.$sub_names[$i]] = $value;
      }
    }
    return $result;
  }
  function expandBoxmodel($input, $main_name) {
    $values = array_map('trim', explode(' ', $input));
    $result = array();
    if (count($values) === 1) {
      $result[$main_name.'-top'] = $values[0];
      $result[$main_name.'-bottom'] = $values[0];
      $result[$main_name.'-right'] = $values[0];
      $result[$main_name.'-left'] = $values[0];
    } else if (count($values) === 2) {
      $result[$main_name.'-top'] = $values[0];
      $result[$main_name.'-bottom'] = $values[0];
      $result[$main_name.'-right'] = $values[1];
      $result[$main_name.'-left'] = $values[1];
    } else if (count($values) === 3) {
      $result[$main_name.'-top'] = $values[0];
      $result[$main_name.'-bottom'] = $values[2];
      $result[$main_name.'-right'] = $values[1];
      $result[$main_name.'-left'] = $values[1];
    } else {
      $result[$main_name.'-top'] = $values[0];
      $result[$main_name.'-right'] = $values[1];
      $result[$main_name.'-bottom'] = $values[2];
      $result[$main_name.'-left'] = $values[3];
    }
    return $result;
  }
}

/**
 * A CSS grammar parser.
 * This parser returns the lexical structure of the source. It will not do clever stuff like expanding short-hand properties etc. Use `csslib_CssParser` for that.
 * The main entrypoint is `parseString`
 */
class csslib_CssGrammarParser {
  /**
   * Parses a string into an array of rules structs.
   *
   * The result is a struct resembling:
   *
   *     array(
   *       array(
   *         'selectors' => array(selector(string), selector(string) ..),
   *         'definitions' => array(
   *           name(string) => value(string),
   *           name(string) => value(string),
   *           ..
   *         )
   *       )
   *     )
   */
  function parseString($input) {
    return $this->parseRules($this->stripComments($input));
  }
  function stripComments($input) {
    return preg_replace(
      '~//.*~', '',
      preg_replace('~/\*[\s\S]*?\*/~', '', $input));
  }
  function parseRules($input) {
    $tokens = preg_split('~(\{[\s\S]*?\})~', $input, 0, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);
    $result = array();
    $buffer = null;
    foreach ($tokens as $token) {
      $token = trim($token);
      if ($token) {
        if ($token[0] === '{') {
          $definitions = $this->parseDefinitions(trim($token, '{}'));
          $selectors = array();
          foreach (explode(',', $buffer) as $selector) {
            $selector = trim($selector);
            if ($selector) {
              $selectors[] = $selector;
            }
          }
          $result[] = array('selectors' => $selectors, 'definitions' => $definitions);
        } else {
          $buffer = $token;
        }
      }
    }
    return $result;
  }
  function parseDefinitions($input) {
    $lines = array();
    foreach (explode(';', $input) as $line) {
      $line = trim($line);
      if ($line) {
        list($property, $value) = explode(':', $line, 2);
        $lines[trim(strtolower($property))] = trim($value);
      }
    }
    return $lines;
  }
}

/**
 * CSS2 selector.
 * Probably has some edge-cases that are uncovered.
 * Based on: http://plasmasturm.org/log/444/
 */
class csslib_DomCssQuery {
  protected $xpath;
  function __construct(DomNode $node) {
    $this->xpath = new DomXPath($node instanceOf DomDocument ? $node : $node->ownerDocument);
  }
  function find($selector, $node = null) {
    return $this->query($selector, $node)->item(0);
  }
  function query($selector, $node = null) {
    return $node
      ? $this->xpath->query($this->cssSelectorToXpath($selector), $node)
      : $this->xpath->query($this->cssSelectorToXpath($selector));
  }
  function cssSelectorToXpath($selector) {
    $all = array();
    foreach (explode(',', $selector) as $single_selector) {
      $all[] = $this->singleCssSelectorToXpath($single_selector);
    }
    return implode(' | ', $all);
  }
  function singleCssSelectorToXpath($selector) {
    $selector = trim($selector);
    $tokens = array();
    $separator = null;
    foreach (preg_split('~\s+~', $selector) as $token) {
      if ($token == '>') {
        $separator = '/';
      } else if ($token == '+') {
        // I'm not sure this is bullet proof ..
        $separator = '/following-sibling::*[1]/self::';
      } else {
        $axis = "";
        if (preg_match('~^(.*)#(\w+)$~', $token, $reg)) { // id
          $axis .= "[@id='".$reg[2]."']";
          $token = $reg[1];
        }
        if (preg_match('~^(.*)\.(\w+)$~', $token, $reg)) { // class
          $axis .= "[contains(concat(' ', @class, ' '), concat(' ', '".$reg[2]."', ' '))]";
          $token = $reg[1];
        }
        if (preg_match('/^(\w+)\[([^\]]+)~=([^\]]+)\]$/', $token, $reg)) { // attr ~=
          $value = $this->maybeEscapeString($reg[3]);
          $axis .= '[contains(concat(" ", @'.$reg[2].', " " ), concat(" ", '.$value.', " " ))]';
          $token = $reg[1];
        }
        if (preg_match('/^(\w+)\[([^\]]+)\|=([^\]]+)\]$/', $token, $reg)) { // attr |=
          $value = $this->maybeEscapeString($reg[3]);
          $axis .= '[@'.$reg[2].'='.$value.' or starts-with(@'.$reg[2].', concat( '.$value.', "-" ))]';
          $token = $reg[1];
        }
        if (preg_match('/^(\w+)\[([^\]]+)=([^\]]+)\]$/', $token, $reg)) { // attr =
          $value = $this->maybeEscapeString($reg[3]);
          $axis .= '[@'.$reg[2].'='.$value.']';
          $token = $reg[1];
        }
        if (preg_match('~^(\w+)\[([^\]]+)\]$~', $token, $reg)) { // attr
          $axis .= "[@".$reg[2]."]";
          $token = $reg[1];
        }
        if (preg_match('~^(\w+):first-child$~', $token, $reg)) { // first-child
          $token = '*[1]/self::' . $reg[1];
        } else if (preg_match('~^(\w+):last-child$~', $token, $reg)) { // last-child
          $token = '*[last()]/self::' . $reg[1];
        } else if (preg_match('~^(\w+):(link|hover|visit|active)$~', $token, $reg)) { // link pseudo-classes
          $token = $reg[1];
        } else if (!preg_match('~^[\w*]*$~', $token)) {
          throw new Exception("Invalid or unsupported CSS syntax near '$token'");
        }
        // No more matching on token beyond this point!
        if ($separator) {
          $tokens[] = $separator;
        }
        $tokens[] = ($token ? $token : '*') . $axis;
        $separator = '//';
      }
    }
    return '//' . implode('', $tokens);
  }
  function maybeEscapeString($input) {
    return ($input[0] === "'" || $input[0] === '"') ? $input : '"' . $input . '"';
  }
}

/**
 * Takes a CSS ruleset and applies each of them to a document, thereby inlining all styles.
 * You can parse the CSS with `csslib_CssParser`.
 */
class csslib_DomCssInliner {
  protected $rules;
  /**
   * Takes an associative array as returned by `csslib_CssParser`.
   */
  function __construct($rules) {
    $this->rules = $rules;
  }
  /**
   * Inlines all styles on the given document.
   */
  function apply(DomDocument $document) {
    $query = new csslib_DomCssQuery($document);
    foreach ($this->rules as $selector => $properties) {
      foreach ($query->query($selector) as $node) {
        $this->mergeStyle($node, $properties);
      }
    }
  }
  function mergeStyle($node, $properties) {
    $styles = $this->parseDefinitions($node->getAttribute('style'));
    $node->setAttribute('style', $this->definitionsToString(array_merge($styles, $properties)));
  }
  function parseDefinitions($input) {
    $lines = array();
    foreach (explode(';', $input) as $line) {
      $line = trim($line);
      if ($line) {
        list($property, $value) = explode(':', $line, 2);
        $lines[trim(strtolower($property))] = trim($value);
      }
    }
    return $lines;
  }
  function definitionsToString($properties) {
    $tuples = array();
    foreach ($properties as $k => $v) {
      $tuples[] = $k.":".$v;
    }
    return implode(';', $tuples);
  }
}