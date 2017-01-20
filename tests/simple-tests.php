<?php

include '../vendor/autoload.php';

/** @var \memdevs\tag\PDOTag $tag */
global $tag; // Main reason for introducing this line? Syntax highlighting in PHPStorm. Only worked properly when set as a global here

?>
<html>
<head>
  <meta charset="utf-8"/>
  <style type="text/css">
    body {
      font-family: verdana, arial, helvetica, sans-serif;
      font-size: 12px
    }
  </style>
</head>
<body>
<?php

$mysql_host = "127.0.0.1";
$mysql_db = "tagmodel";
$mysql_user = 'tagmodel_user';
$mysql_pass = '';


$external_id = 2;

$use_aura = false;
if ($use_aura) {
  $pdo = new \Aura\Sql\ExtendedPdo("mysql:host={$mysql_host};dbname={$mysql_db};charset=utf8", $mysql_user, $mysql_pass);
  $tag = new \memdevs\tag\AuraTag($external_id, $pdo);
} else {
  $pdo = new \PDO("mysql:host={$mysql_host};dbname={$mysql_db};charset=utf8", $mysql_user, $mysql_pass);
  $tag = new \memdevs\tag\PDOTag($external_id, $pdo);
  $tag->setTimestampEnabled();
}


checkTag1(); // Integer
checkTag2(); // Null
checkTag3(); // JSON
checkTag4(); // Counter
checkTag5(); // Text - Unique
checkTag6(); // Text - Multiple

print "<p><b>Successful test</b></p>";


/**
 * Check an integer tag
 */
function checkTag1()
{
  global $tag, $external_id;

  try {
    $tag_id = 1;
    $tag->truncate();

    print "<h3>tag {$tag_id}</h3>";

    $tag->addTag($tag_id, 83);
    $tag->addTag($tag_id, 85);
    $tag->addTag($tag_id, 87);
    $tag->addTag($tag_id, 104);

    \Assert\Assertion::eq(
      stripDates($tag->getTags($tag_id)),
      [
        1 => ['tagentry_id' => '1', 'external_id' => $external_id, 'tag_id' => $tag_id, 'tag_value' => '83',],
        2 => ['tagentry_id' => '2', 'external_id' => $external_id, 'tag_id' => $tag_id, 'tag_value' => '85',],
        3 => ['tagentry_id' => '3', 'external_id' => $external_id, 'tag_id' => $tag_id, 'tag_value' => '87',],
        4 => ['tagentry_id' => '4', 'external_id' => $external_id, 'tag_id' => $tag_id, 'tag_value' => '104',],
      ]
    );

    if ($tag->hasTagIdValue($tag_id, 83)) {
      print "<p>Has tag value 83</p>";
    }
    \Assert\Assertion::eq($tag->hasTagIdValue($tag_id, 83), true);

    if ($tag->hasTagIdValue($tag_id, 85)) {
      print "<p>Has tag value 85</p>";
    }
    \Assert\Assertion::eq($tag->hasTagIdValue($tag_id, 85), true);

    if ($tag->hasTagIdValue($tag_id, 87)) {
      print "<p>Has tag value 87</p>";
    }
    \Assert\Assertion::eq($tag->hasTagIdValue($tag_id, 87), true);

    if ($tag->hasTagIdValue($tag_id, 99)) {
      print "<p>Has tag value 99</p>";
    }
    \Assert\Assertion::eq($tag->hasTagIdValue($tag_id, 99), false);

    if ($tag->hasTagIdValue($tag_id, 104)) {
      print "<p>Has tag value 104</p>";
    }
    \Assert\Assertion::eq($tag->hasTagIdValue($tag_id, 104), true);

    print "<p>Deleting 87 and 104</p>";
    $tag->deleteTagIdValue($tag_id, 87);
    $tag->deleteTagIdValue($tag_id, 104);

    var_dump($tag->getTags($tag_id));

    if ($tag->hasTagIdValue($tag_id, 83)) {
      print "<p>Has tag value 83</p>";
    }
    \Assert\Assertion::eq($tag->hasTagIdValue($tag_id, 83), true);

    if ($tag->hasTagIdValue($tag_id, 85)) {
      print "<p>Has tag value 85</p>";
    }
    \Assert\Assertion::eq($tag->hasTagIdValue($tag_id, 85), true);

    if ($tag->hasTagIdValue($tag_id, 87)) {
      print "<p>Has tag value 87</p>";
    }
    \Assert\Assertion::eq($tag->hasTagIdValue($tag_id, 87), false);

    if ($tag->hasTagIdValue($tag_id, 99)) {
      print "<p>Has tag value 99</p>";
    }
    \Assert\Assertion::eq($tag->hasTagIdValue($tag_id, 99), false);

    if ($tag->hasTagIdValue($tag_id, 104)) {
      print "<p>Has tag value 104</p>";
    }
    \Assert\Assertion::eq($tag->hasTagIdValue($tag_id, 104), false);

    print "<p>Deleting 2 existing and 1 non-existing tags</p>";
    \Assert\Assertion::eq($tag->deleteTagIdValue($tag_id, 83), true);
    \Assert\Assertion::eq($tag->deleteTagIdValue($tag_id, 85), true);
    \Assert\Assertion::eq($tag->deleteTagIdValue($tag_id, 99), false);

    print "<p>Checking that all data is deleted</p>";
    \Assert\Assertion::eq($tag->getTags($tag_id), []);

    print "<hr>";
  } catch (Exception $e) {
    oops($e);
  }
}


/**
 * Check a null value tag
 */
function checkTag2()
{
  global $tag, $external_id;

  try {
    $tag_id = 2;
    $tag->truncate();

    print "<h3>tag {$tag_id}</h3>";

    print "<p>Adding tag {$tag_id}</p>";
    $tag->addTag($tag_id);
    \Assert\Assertion::eq(stripDates($tag->getTag($tag_id)), ['tagentry_id' => '1', 'external_id' => $external_id, 'tag_id' => $tag_id, 'tag_value' => null]);
    \Assert\Assertion::eq(stripDates($tag->getTags($tag_id)), [1 => ['tagentry_id' => '1', 'external_id' => $external_id, 'tag_id' => $tag_id, 'tag_value' => null]]);
    \Assert\Assertion::eq($tag->getTagValues($tag_id), [0 => null]);

    print "<p>Adding tag {$tag_id} again</p>";
    $tag->addTag($tag_id);
    \Assert\Assertion::eq(stripDates($tag->getTag($tag_id)), ['tagentry_id' => '1', 'external_id' => $external_id, 'tag_id' => $tag_id, 'tag_value' => null]);
    \Assert\Assertion::eq(stripDates($tag->getTags($tag_id)), [1 => ['tagentry_id' => '1', 'external_id' => $external_id, 'tag_id' => $tag_id, 'tag_value' => null]]);

    var_dump($tag->getTag($tag_id));
    try {
      print "<p>Updating tag {$tag_id} should fail</p>";
      $tag->updateTag($tag_id);
      print "<p><b>Uh, we shouldn't have reached this point!</b></p>";
    } catch (Exception $e) {
      print "<p>Expected Exception! Problems with updating tag {$tag_id}: {$e->getMessage()}</p>";
    }

    print "<p>Retrieving tag {$tag_id}</p>";
    var_dump($tag->getTag($tag_id));
    \Assert\Assertion::eq(stripDates($tag->getTag($tag_id)), ['tagentry_id' => '1', 'external_id' => $external_id, 'tag_id' => $tag_id, 'tag_value' => null]);
    \Assert\Assertion::eq(stripDates($tag->getTags($tag_id)), [1 => ['tagentry_id' => '1', 'external_id' => $external_id, 'tag_id' => $tag_id, 'tag_value' => null]]);

    print "<p>Checking hasTagId({$tag_id})</p>";
    \Assert\Assertion::eq($tag->hasTagId($tag_id), true);
    if ($tag->hasTagId($tag_id)) {
      print "<p>Has tag {$tag_id}</p>";
    }

    $tag->deleteTag($tag_id);
    \Assert\Assertion::eq($tag->hasTagId($tag_id), false);
    if ($tag->hasTagId($tag_id)) {
      print "<p>Oops! Still has tag {$tag_id}</p>";
    } else {
      print "<p>Tag {$tag_id} is now gone</p>";
    }

    print "<p>Checking that all data is deleted</p>";
    \Assert\Assertion::eq($tag->getTag($tag_id), []);

    print "<hr>";
  } catch (Exception $e) {
    oops($e);
  }
}


/**
 * Checking a json tag
 */
function checkTag3()
{
  global $tag;

  try {
    $tag_id = 3;
    $tag->truncate();

    print "<h3>tag {$tag_id}</h3>";

    print "<p>Single string value:</p>";
    $tag->addTag($tag_id, 'test');
    var_dump($tag->getTagValue($tag_id));
    \Assert\Assertion::eq($tag->getTagValue($tag_id), 'test');

    print "<p>One element array:</p>";
    $tag->addTag($tag_id, ['test'], true);
    var_dump($tag->getTagValue($tag_id));
    \Assert\Assertion::eq($tag->getTagValue($tag_id), ['test']);

    print "<p>Much bigger array:</p>";
    $server = $_SERVER;
    $tag->addTag($tag_id, $server, true);
    var_dump($tag->getTagValue($tag_id));
    \Assert\Assertion::eq($tag->getTagValue($tag_id), $server);
    print "<p>Should still have all elements from previous one:</p>";
    $tag->addTag($tag_id, 'test');
    var_dump($tag->getTagValue($tag_id));
    \Assert\Assertion::eq($tag->getTagValue($tag_id), $server);

    print "<p>Checking that all data is deleted</p>";
    $tag->deleteTag($tag_id);
    \Assert\Assertion::eq($tag->getTag($tag_id), []);

    print "<hr>";
  } catch (Exception $e) {
    oops($e);
  }
}


/**
 * Checking a counter tag
 */
function checkTag4()
{
  global $tag;

  try {
    $tag_id = 4;
    $tag->truncate();

    print "<h3>tag {$tag_id}</h3>";
    print "<p>Adding counter as normal tag with a value of 5:</p>";
    $tag->addTag($tag_id, 5);
    var_dump($tag->getTagValue($tag_id));
    \Assert\Assertion::eq($tag->getTagValue($tag_id), 5);

    print "<p>Remove counter:</p>";
    $tag->deleteTag($tag_id);
    try {
      var_dump($tag->getTagValue($tag_id));
      print "<p><b>Uh, we shouldn't have reached this point!</b></p>";
    } catch (Exception $e) {
      \Tracy\Debugger::fireLog($e);
      print "<p>Expected Exception! {$e->getMessage()}</p>";
    }

    print "<p>Increment a non-existent counter to 45</p>";
    $tag->incrementCounter($tag_id, 45);
    var_dump($tag->getTagValue($tag_id));
    \Assert\Assertion::eq($tag->getTagValue($tag_id), 45);

    print "<p>Remove counter:</p>";
    $tag->deleteTag($tag_id);
    try {
      var_dump($tag->getTagValue($tag_id));
      print "<p><b>Uh, we shouldn't have reached this point!</b></p>";
    } catch (Exception $e) {
      \Tracy\Debugger::fireLog($e);
      print "<p>Expected Exception! {$e->getMessage()}</p>";
    }

    print "<p>Set counter to 10:</p>";
    $tag->setCounter($tag_id, 10);
    var_dump($tag->getTagValue($tag_id));
    \Assert\Assertion::eq($tag->getTagValue($tag_id), 10);

    print "<p>Increase by 6 to 16:</p>";
    $tag->incrementCounter($tag_id, 6);
    var_dump($tag->getTagValue($tag_id));
    \Assert\Assertion::eq($tag->getTagValue($tag_id), 16);

    print "<p>Decrease by 3 to 13:</p>";
    $tag->decrementCounter($tag_id, 3);
    var_dump($tag->getTagValue($tag_id));
    \Assert\Assertion::eq($tag->getTagValue($tag_id), 13);

    print "<p>Increase by 1 to 14:</p>";
    $tag->incrementCounter($tag_id);
    var_dump($tag->getTagValue($tag_id));
    \Assert\Assertion::eq($tag->getTagValue($tag_id), 14);

    print "<p>Decrease by 1 to 13:</p>";
    $tag->decrementCounter($tag_id);
    var_dump($tag->getTagValue($tag_id));
    \Assert\Assertion::eq($tag->getTagValue($tag_id), 13);

    print "<p>Reset counter:</p>";
    $tag->resetCounter($tag_id);
    var_dump($tag->getTagValue($tag_id));
    \Assert\Assertion::eq($tag->getTagValue($tag_id), 0);

    print "<p>Checking that all data is deleted</p>";
    $tag->deleteTag($tag_id);
    \Assert\Assertion::eq($tag->getTag($tag_id), []);

    print "<hr>";
  } catch (Exception $e) {
    oops($e);
  }
}


/**
 * Checking a unique text tag
 */
function checkTag5()
{
  global $tag, $external_id;

  try {
    $tag_id = 5;
    $tag->truncate();

    print "<h3>tag {$tag_id}</h3>";

    print "<p>Setting tag</p>";
    $tag->addTag($tag_id, 'This is a test');
    var_dump($tag->getTagValue($tag_id));
    \Assert\Assertion::eq($tag->getTagValue($tag_id), 'This is a test');

    print "<p>Adding tag again - should have same value</p>";
    $tag->addTag($tag_id, 'This is the next test');
    var_dump($tag->getTagValue($tag_id));
    \Assert\Assertion::eq($tag->getTagValue($tag_id), 'This is a test');

    print "<p>Adding tag again - should have different value</p>";
    $tag->addTag($tag_id, 'This is the next test', true);
    var_dump($tag->getTagValue($tag_id));
    \Assert\Assertion::eq(
      stripDates($tag->getTags($tag_id)),
      [1 => ['tagentry_id' => '1', 'external_id' => $external_id, 'tag_id' => '5', 'tag_value' => 'This is the next test']]
    );
    \Assert\Assertion::eq($tag->getTagValue($tag_id), 'This is the next test');
    \Assert\Assertion::eq($tag->getTagValues($tag_id), ['This is the next test'], 'Should only be a single value; multiple found');
    print "<p>Updating tag - should have different value</p>";
    $tag->updateTag($tag_id, 'A new value');
    var_dump($tag->getTagValue($tag_id));
    \Assert\Assertion::eq($tag->getTagValue($tag_id), 'A new value');

    print "<p>Deleting tag</p>";
    $tag->deleteTag($tag_id);
    try {
      var_dump($tag->getTagValue($tag_id));
      print "<p><b>Uh, we shouldn't have reached this point!</b></p>";
    } catch (Exception $e) {
      \Tracy\Debugger::fireLog($e);
      print "<p>Expected Exception! {$e->getMessage()}</p>";
    }

    print "<p>Checking that all data is deleted</p>";
    \Assert\Assertion::eq($tag->getTag($tag_id), []);

    print "<hr>";
  } catch (Exception $e) {
    oops($e);
  }
}


/**
 * Check a non-unique text tag
 */
function checkTag6()
{
  global $tag, $external_id;

  try {
    $tag_id = 6;
    $tag->truncate();

    print "<h3>tag {$tag_id}</h3>";

    print "<p>Adding tag</p>";
    $tag->addTag($tag_id, 'This is a test');
    var_dump($tag->getTags($tag_id));
    \Assert\Assertion::eq(
      stripDates($tag->getTags($tag_id)),
      [
        1 => ['tagentry_id' => '1', 'external_id' => $external_id, 'tag_id' => $tag_id, 'tag_value' => 'This is a test',],
      ]
    );

    print "<p>Adding tag again - as has same value should not be added again but silently ignored</p>";
    $tag->addTag($tag_id, 'This is a test');
    var_dump($tag->getTags($tag_id));
    \Assert\Assertion::eq(
      stripDates($tag->getTags($tag_id)),
      [
        1 => ['tagentry_id' => '1', 'external_id' => $external_id, 'tag_id' => $tag_id, 'tag_value' => 'This is a test',],
      ]
    );

    print "<p>Adding tag again - with a new value</p>";
    $tag->addTag($tag_id, 'This is the next test', true);
    var_dump($tag->getTags($tag_id));
    \Assert\Assertion::eq(
      stripDates($tag->getTags($tag_id)),
      [
        1 => ['tagentry_id' => '1', 'external_id' => $external_id, 'tag_id' => $tag_id, 'tag_value' => 'This is a test',],
        2 => ['tagentry_id' => '2', 'external_id' => $external_id, 'tag_id' => $tag_id, 'tag_value' => 'This is the next test',],
      ]
    );

    print "<p>Adding tag again - should have different value</p>";
    $tag->addTag($tag_id, 'This is the final test', true);
    var_dump($tag->getTags($tag_id));
    \Assert\Assertion::eq(
      stripDates($tag->getTags($tag_id)),
      [
        1 => ['tagentry_id' => '1', 'external_id' => $external_id, 'tag_id' => $tag_id, 'tag_value' => 'This is a test',],
        2 => ['tagentry_id' => '2', 'external_id' => $external_id, 'tag_id' => $tag_id, 'tag_value' => 'This is the next test',],
        3 => ['tagentry_id' => '3', 'external_id' => $external_id, 'tag_id' => $tag_id, 'tag_value' => 'This is the final test',],
      ]
    );

    print "<p>Updating tag - should fail with exception</p>";
    try {
      $tag->updateTag($tag_id, 'A new value');
      print "<p><b>Uh, we shouldn't have reached this point!</b></p>";
    } catch (Exception $e) {
      \Tracy\Debugger::fireLog($e);
      print "<p>Expected Exception! {$e->getMessage()}</p>";
    }

    print "<p>Deleting tag by id - should fail with exception</p>";
    try {
      $tag->deleteTag($tag_id);
      print "<p><b>Uh, we shouldn't have reached this point!</b></p>";
    } catch (Exception $e) {
      \Tracy\Debugger::fireLog($e);
      print "<p>Expected Exception! {$e->getMessage()}</p>";
    }

    print "<p>Deleting tag by id and value - 2 values should remain</p>";
    $tag->deleteTagIdValue($tag_id, 'This is a test');
    var_dump($tag->getTags($tag_id));
    \Assert\Assertion::eq(
      stripDates($tag->getTags($tag_id)),
      [
        2 => ['tagentry_id' => '2', 'external_id' => $external_id, 'tag_id' => $tag_id, 'tag_value' => 'This is the next test',],
        3 => ['tagentry_id' => '3', 'external_id' => $external_id, 'tag_id' => $tag_id, 'tag_value' => 'This is the final test',],
      ]
    );

    print "<p>Deleting tag by id and value - should delete 1 value, leaving 'final'</p>";
    $tag->deleteTagIdValue($tag_id, 'This is the next test');
    var_dump($tag->getTags($tag_id));
    \Assert\Assertion::eq(
      stripDates($tag->getTags($tag_id)),
      [
        3 => ['tagentry_id' => '3', 'external_id' => $external_id, 'tag_id' => $tag_id, 'tag_value' => 'This is the final test',],
      ]
    );

    print "<p>Checking that all data is deleted</p>";
    $tag->deleteTagIdValue($tag_id, 'This is the final test');
    \Assert\Assertion::eq(stripDates($tag->getTags($tag_id)), []);

    print "<hr>";
  } catch (Exception $e) {
    oops($e);
  }
}


/**
 * @param Exception $e
 */
function oops($e)
{
  if ($e instanceof Assert\InvalidArgumentException) {
    die("<strong style='background-color: yellow'>" . $e->getMessage() . "</strong> in line " . $e->getTrace()[1]['line']);
  } else {
    die("<strong style='background-color: yellow'>" . $e->getMessage() . "</strong> in line " . $e->getFile() . ' line ' . $e->getLine());
  }
}


function stripDates($arr)
{
  if (array_key_exists('created_at', $arr) || array_key_exists('updated_at', $arr)) {
    if (array_key_exists('created_at', $arr)) {
      unset($arr['created_at']);
    }
    if (array_key_exists('updated_at', $arr)) {
      unset($arr['updated_at']);
    }
  } else {
    array_walk($arr, function (&$v) {
      if (array_key_exists('created_at', $v)) {
        unset($v['created_at']);
      }
      if (array_key_exists('updated_at', $v)) {
        unset($v['updated_at']);
      }
    });
  }

  return $arr;
}

?>
</body>
</html>
