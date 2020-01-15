Intercom Flee
====

Tired of Intercom jacking up the price on you and want to go somewhere else, but need to drag your sorry data with you? Look no further, I'm here to help.

What's this for....exactly?
----
This will pull back all users, and all conversations attached to those users and stick it in a MySQL database for you to use later when you need to take it and put it somewhere else. This is just boilerplate code you for. You'll need to change what fields you want to save in the code after you clone/fork/copy this repo.

Requirements
----
- MySQL 5.5+
- PHP 7+
- Half a brain

Installation
----
Get your database up and running. The schema is located in `initial_db_schema.sql`. Take the `config.sample.php` file and rename it to `config.php` with your appropriate values.

Usage
----
Assuming you've set up your database permissions correctly, you can then run: 

```php
php rip_that_data_back_from_them.php
``` 

and off it will go :)

This is currently based on Intercom v1.4 API

