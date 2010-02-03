CSS parsing and CSS selectors for PHP
===

CSSlib provides a couple of utilities for working with CSS. Including a CSS parser, a CSS2-selector and a CSS inliner.

The library consists of three main components:

* CSS Selector
* CSS Parser
* CSS Inliner

License
---

CSSlib is licensed under MIT License.

Installation
---

You can install CSSlib through [pearhub.org](http://pearhub.org/). Just type:

    pear channel-discover pearhub.org
    pear install pearhub/csslib

CSS Selector
---

You can use `csslib_DomCssQuery` to query in a `DomDocument`, using CSS2 syntax, rather than XPath. Usage is similar:

    $query = new csslib_DomCssQuery($document);
    foreach ($query->select('.foo') as $node) {
      var_dump($node);
    }

CSS Parser
---

The CSS parser reads CSS and combines rules into a normalised computed style sheet.

     $css = new csslib_CssParser();
     var_dump($css->parseString('.foo { color: red } .foo { margin: 1px }'));

###Output

    array(1) {
      [".foo"]=>
      array(5) {
        ["color"]=>
        string(3) "red"
        ["margin-top"]=>
        string(3) "1px"
        ["margin-bottom"]=>
        string(3) "1px"
        ["margin-right"]=>
        string(3) "1px"
        ["margin-left"]=>
        string(3) "1px"
      }
    }

CSS Inliner
---

The CSS inliner can take a CSS document and apply all the rules as inline styles to a HTML document. This is useful for generating HTML-emails, where you can't use external styles.

    // First parse the CSS file
    $css = new csslib_CssParser();
    $inliner = new csslib_DomCssInliner($css->parseString(file_get_contents('style.css')));
    // Then load the HTML file
    $document = new DomDocument();
    $document->loadHtml('test.html');
    // Inline the style to the document
    $inliner->apply($document);
    // And display it
    echo $document->saveHtml();
