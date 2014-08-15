Infusionsoft sync
=================

Use this library to add two-way syncing between your application and
[Infusionsoft CRM](help.infusionsoft.com/developers/api-basics).

Features
========

- Configure field names
- Using custom field names if necessary
- Configure tag names
- Optimized to work with big data sets

How it works
============

This library realizes 2 methods to help to sync your application with
Infusionsoft API: `pull()` and `push()`

`pull()` goes to remote API, fetches all contacts and returns them to you. You
can then use this data to sync it with your application storage

`push()` will allow you to put your local users to remote. This method is smart
enought to update contacts if they're already present at Infusionsoft and to
skip if nothing was changed.

Under the hood there is a `pull()` method call inside the `push()` method. This
is done to minify amount of API calls and to speed-up the process on big data
sets. However when you push one contact `pull()` is skipped to prevent
redundancy.

Examples
========

To get list of contacts from Infusionsoft API


``` php
<?php
use Wildsurfer\Infusionsoft;

$options = array(
    'appname' => 'myappname',
    'apikey' => 'asdU234YGUYG1234gjhg',
    'tags' => array(
        '1',
        '2',
        '3'
    ),
    'fields' = array(
        'Email',
        'FirstName',
        'LastName'
    )
);

$sync = new Sync($options);

$infusionsoftContacts = $sync->pull();

```

To push your application users to Infusionsoft API


``` php

$myUsers = ... // Fetch data from your storage

$toBeSyncedUsers = new ContactCollection();

foreach($myUsers as $user) {
    $array = someFunctionToMapYourFieldNamesToInfusionsoftFieldNames($user);
    $toBeSyncedUsers->create(new Contact($array));
}

$sync->push($toBeSyncedUsers);


```

Install with Composer
=====================

``` js
  {
      require: {
          "wildsurfer/infusionsoft": "dev-master"
      }
  }
```
