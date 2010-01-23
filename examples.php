<?php
require_once 'lib/csslib.inc.php';

$css = new csslib_CssGrammarParser();
echo $css->stripComments('
/**
  * Foobar
  */
blah
');
echo "\n---\n";
var_dump($css->parseRules('
.foo {
  font-size: 12px;
}
.foo, bar:hover {
  color: red;
  border: 1px solid yellow;
}
'));
echo "\n---\n";
$css = new csslib_CssParser();
var_dump($css->parseString('
.foo {
  font-size: 12px;
}
.foo, bar:hover {
  color: red;
  border: 1px solid yellow;
}
'));
echo "\n---\n";

// http://www.w3schools.com/css/demo_default.htm
$css_code = "
body
{
font-size:75%;
font-family:verdana,arial,'sans serif';
background-image:url('gradient.png');
background-repeat:repeat-x;
background-color:#FFFFF0;
color:#000080;
margin:70px;
}

h1 {font-size:200%;}
h2 {font-size:140%;}
h3 {font-size:110%;}

th {background-color:#ADD8E6;}

ul {list-style:circle;}
ol {list-style:upper-roman;}

a:link {color:#000080;}
a:hover {color:red;}
";
$html_code = '
<html>
<head>

<link rel="stylesheet" type="text/css" href="style3.css">

</head>

<body>
<h1>Heading 1</h1>
<p>This is some text in a paragraph.</p>
<p>This is another paragraph.</p>
<hr />

<h2>Heading 2</h2>
<table border="1" width="100%">
<tr>
	<th align="left">Name</th>
	<th align="left">E-mail</th>
	<th align="left">Phone</th>
</tr>
<tr>
	<td width="25%">Doe, John</td>

	<td width="25%">jdoe@example.com</td>
	<td width="25%">555-789-7222</td>
</tr>
<tr>
	<td width="25%">Smith, Eva</td>
	<td width="25%">esmith@example.com</td>
	<td width="25%">555-324-3693</td>

</tr>
</table>
<br />
<hr />

<h3>Heading 3</h3>
<p>Visit our <a href="http://www.w3schools.com/">Home Page</a> or our <a href="http://www.w3schools.com/css/">CSS Tutorial</a>.</p>
<p>What you should already know:</p>
<ol>

	<li>HTML</li>
	<li>XHTML</li>
</ol>

<p>Favorite drinks:</p>
<ul>
	<li>Smoothie</li>
	<li>Green tea</li>

  	<li>Coffee</li>
</ul>

</body>
</html>
';

$css = new csslib_CssParser();
$inliner = new csslib_DomCssInliner($css->parseString($css_code));
$document = new DomDocument();
$document->loadHtml($html_code);
$inliner->apply($document);
echo $document->saveHtml();
echo "\n---\n";