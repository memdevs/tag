# Tag handling

A "tag" is a piece of information that is typically attached to another record.

For those familiar with Infusionsoft tags, that's basically it.

However, these tags are a bit more advanced.

## Version numbering

This project will be maintained using Semantic versioning. Having said that, I am not expecting any
BC breaking issues when promoting from 0.x to 1.x.

This library is currently being used in production in several systems, without any flaws, however
I have very specific use cases. I would expect this to be promoted to 1.x in its current state
if the library becomes a little bit popular and no issues are raised.

## Introduction

Let's say you have a Person record with contact information in your CRM.

They enquire about your Product X, and you want to make a note of that somewhere, and
be later able to search for anyone that requested information.

You can create a tag, let's call it 'enquiries', and want to store a record of requests of
all different products in that tag.

Each tag has a numeric ID, so let's say the 'enquiries' tag has ID 10.

Each product or type of request must also have a numeric ID, so let's Product X is ID 20.

You can now create a tag 10 with value of 20. The PDOTag class will allow you to associate a tag
with an external ID, in this case the ID of your Person record, let's say 5.

In our sample table, 'tagdefinition', we have previously defined tag 1 as an integer tag as being non-unique.

```
$mysql_host = "127.0.0.1";
$mysql_db = "tagmodel";
$mysql_user = 'tagmodel_user';
$mysql_pass = '';
$pdo = new \PDO("mysql:host={$mysql_host};dbname={$mysql_db};charset=utf8", $mysql_user, $mysql_pass);

$external_id = 5;
$tag_id = 10;
$product_x = 20;

$tag = new \memdevs\tag\PDOTag($external_id, $pdo);
$tag->addTag($tag_id, $product_x);

$success = $tag->hasTagIdValue($tag_id, $product_x); // Will return true
```

Using the sample database you get this:

![MySQL Tag Record](https://i.gyazo.com/86ddec59bf7b92894beab39bdd6982ef.png)

The Person now enquiries about Product Y which has ID 25. You add another tag to get:

```
$product_y = 25;
$tag->addTag($tag_id, $product_x);

$success = $tag->hasTagIdValue($tag_id, $product_x); // Will return true
$success = $tag->hasTagIdValue($tag_id, $product_y); // Will return true
```

![MySQL Tag Record](https://i.gyazo.com/6f065976ebe5e73eaf93a6b3ff96d3ad.png)

They are no longer interested in Product X, so you can remove the tag.
As tag 1 'enquiries' have more than 1 value/records, you need to identify exactly which
one to remove. You CAN delete tag 1, but that would remove all values.

```
$tag->deleteTagIdValue($tag_id, $product_x);

$success = $tag->hasTagIdValue($tag_id, $product_x); // Will return false
$success = $tag->hasTagIdValue($tag_id, $product_y); // Will return true
```

What if they filled out a request form, with information field, and you need to keep the
information handy? You can create multiple tables to handle different form data, or even
a "form_responses" table to keep them in. Or you can attach the information as a json tag
(tag type 'j') which can store any kind of json structure.

Using the sample database, tag 3:

```
$tag_id_json = 3;
$data = $_POST;
$tag->addTag($tag_id, $data);

$submitted_data = $tag->getTagValue($tag_id_json);
```

The main limitation on this is 1 tag ID = 1 entry in the current implementation. What that means
is that you cannot use a general "form tag" to hold various entries; instead, each form would have a unique
tag to hold the data.

## Tag Types

* n - no tag data attached
* i - integer data
* t - text data
* j - json formatted data; will be returned as an array to the caller
* c - counter - can be set, reset, incremented and decremented

i and t (integer and text) can be unique or have multiple values.

In the PDOTag and AuraTAG classes, a timed tag is implemented by using an integer data field to
store a unix timestamp. The timed tag can be set, reset, incremented and decremented. However,
it will only return a tag if the time has not expired if using ```hasTimedTag($tag_id)```.

This timed tag can be used to track time expiring offers, for instance. When you present the offer
and the Person has 8 hours to make a decision, simply set the timed tag. If the timer expires, the
offer is not available. If ```hasTimedTag($tag_id)``` returns a value, the offer is valid.

## Note
Race conditions can exist, especially for the counters. This means that after reading
the counter, it's incremented and written back. During this time it's very feasible that
another process will read the counter, and they will both update to the same count.

TODO: Add a function for incrementing the value rather than read, add, then write back.

In the meantime, please ensure you are implementing proper locking mechanisms for incrementing
counters where this is important.

## License

MIT license. Copyright (C) Steve Eriksen 2017
