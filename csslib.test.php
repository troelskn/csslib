<?php
require_once 'simpletest/unit_tester.php';
if (realpath($_SERVER['PHP_SELF']) == __FILE__) {
  error_reporting(E_ALL | E_STRICT);
  require_once 'simpletest/autorun.php';
}

require_once 'lib/csslib.inc.php';

class csslib_TestOfDomCssQuery extends UnitTestCase {
  function test_select_by_node_type() {
    $doc = new DomDocument();
    $doc->loadHtml('<div>first</div><span>second</span><div>third</div>');
    $q = new csslib_DomCssQuery($doc);
    $this->assertEqual(
      $doc->saveXml($q->find('div', $doc)),
      '<div>first</div>');
    $this->assertEqual(
      $doc->saveXml($q->find('span', $doc)),
      '<span>second</span>');
  }
  function test_select_by_id() {
    $doc = new DomDocument();
    $doc->loadHtml('<div>first</div><div id="foo">second</div><div>third</div>');
    $q = new csslib_DomCssQuery($doc);
    $this->assertEqual(
      $doc->saveXml($q->find('#foo', $doc)),
      '<div id="foo">second</div>');
  }
  function test_select_by_class() {
    $doc = new DomDocument();
    $doc->loadHtml('<div>first</div><div class="foo">second</div><div class="foo bar">third</div>');
    $q = new csslib_DomCssQuery($doc);
    $this->assertEqual(
      $doc->saveXml($q->find('.foo', $doc)),
      '<div class="foo">second</div>');
    $this->assertEqual(
      $doc->saveXml($q->find('.bar', $doc)),
      '<div class="foo bar">third</div>');
  }
  function test_by_position_in_hierarchy() {
    $doc = new DomDocument();
    $doc->loadHtml('<b>first</b><div><span><b>second</b></span></div>');
    $q = new csslib_DomCssQuery($doc);
    $this->assertEqual(
      $doc->saveXml($q->find('div b', $doc)),
      '<b>second</b>');
    $this->assertNull(
      $q->find('div > b', $doc));
    $this->assertEqual(
      $doc->saveXml($q->find('div > span > b', $doc)),
      '<b>second</b>');
  }
  function test_select_by_attribute() {
    $doc = new DomDocument();
    $doc->loadHtml('<ul><li>first</li><li title="foo">second</li><li title="bar">third</li></ul>');
    $q = new csslib_DomCssQuery($doc);
    $this->assertEqual(
      $doc->saveXml($q->find('li[title]', $doc)),
      '<li title="foo">second</li>');
    $this->assertEqual(
      $doc->saveXml($q->find('li[title="bar"]', $doc)),
      '<li title="bar">third</li>');
    $this->assertEqual(
      $doc->saveXml($q->find('li[title=bar]', $doc)),
      '<li title="bar">third</li>');
  }
  function test_select_first_and_last_child() {
    $doc = new DomDocument();
    $doc->loadHtml('<ul><li>first</li><li>second</li><li>third</li></ul>');
    $q = new csslib_DomCssQuery($doc);
    $this->assertEqual(
      $doc->saveXml($q->find('li:first-child', $doc)),
      '<li>first</li>');
    $this->assertEqual(
      $doc->saveXml($q->find('li:last-child', $doc)),
      '<li>third</li>');
  }
  function test_select_immediate_sibling() {
    $doc = new DomDocument();
    $doc->loadHtml('<div><b>first</b><i>second</i><b>third</b></div>');
    $q = new csslib_DomCssQuery($doc);
    $this->assertEqual(
      $doc->saveXml($q->find('i + b', $doc)),
      '<b>third</b>');
  }
  function test_ignores_link_pseudo_classes() {
    $doc = new DomDocument();
    $doc->loadHtml('<ul><a>first</a><a>second</a><a>third</a></ul>');
    $q = new csslib_DomCssQuery($doc);
    $this->assertEqual(
      $doc->saveXml($q->find('a:link', $doc)),
      '<a>first</a>');
    $this->assertEqual(
      $doc->saveXml($q->find('a:hover', $doc)),
      '<a>first</a>');
    $this->assertEqual(
      $doc->saveXml($q->find('a:visit', $doc)),
      '<a>first</a>');
    $this->assertEqual(
      $doc->saveXml($q->find('a:active', $doc)),
      '<a>first</a>');
  }
  function test_ignores_namespaces() {
    $doc = new DomDocument();
    $doc->loadXml('<ul xmlns:x="foo" xmlns:y="bar"><x:a>first</x:a><y:a>second</y:a><a>third</a></ul>');
    $q = new csslib_DomCssQuery($doc);
    $result = $q->query('ul a', $doc);
    $this->assertEqual($result->length, 3);
    $this->assertEqual(
      $doc->saveXml($result->item(0)),
      '<x:a>first</x:a>');
    $this->assertEqual(
      $doc->saveXml($result->item(1)),
      '<y:a>second</y:a>');
    $this->assertEqual(
      $doc->saveXml($result->item(2)),
      '<a>third</a>');
  }
}
