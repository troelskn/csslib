<?php
/**
 * Smarty plugin
 * @package Smarty
 * @subpackage plugins
 */
require_once 'csslib/csslib.inc.php';

/**
 * Smarty {cssinline} plugin
 *
 * Type:     function<br>
 * Name:     cssinline<br>
 * Purpose:  Registers a CSS file to inline into the output document.<br>
 *           Note that the document is parsed as HTML and rewritten, which may change the markup.<br>
 * Parameters:
 *  - stylesheet:  string;   a filesystem-path, e.g. "/foo/foobar.css" or
 *                           an URL (if curl-wrappers are enabled), e.g. "http://example.org/foobar.css"
 * @param array
 * @param Smarty
 * @return null
 */
function smarty_function_cssinline($params, $smarty) {
  static $filter_instances = array();
  if (empty($params['stylesheet'])) {
    $smarty->_trigger_fatal_error("[plugin/cssinline] parameter 'stylesheet' cannot be empty");
    return;
  }
  if (!isset($filter_instances[$params['stylesheet']])) {
    $filter_instances[$params['stylesheet']] = new smarty_CssInlinerFilter($params['stylesheet']);
  }
  $smarty->register_outputfilter(array($filter_instances[$params['stylesheet']], 'filter'));
}

/**
 * Helper for {smarty_function_cssinline}
 */
class smarty_CssInlinerFilter {
  protected $path_to_css;
  protected $charset;
  protected $inliner;
  function __construct($path_to_css, $charset = 'iso-8859-1') {
    $this->path_to_css = $path_to_css;
    $this->charset = $charset;
  }
  function filter($tpl_output, $smarty) {
    $document = new DomDocument('1.0', $this->charset);
    $document->loadHtml($tpl_output);
    $this->getInliner()->apply($document);
    return $document->saveHtml();
  }
  protected function getInliner() {
    if (!$this->inliner) {
      $css_source = file_get_contents($this->path_to_css);
      $css_parser = new csslib_CssParser();
      $this->inliner = new csslib_DomCssInliner($css->parseString($css_source));
    }
    return $this->inliner;
  }
}
