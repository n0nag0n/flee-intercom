Flee Intercom!
====

Tired of Intercom jacking up the price on you and want to go somewhere else, but need to drag your sorry data with you? Look no further, I'm here to help. This will help you export your data from Intercom so you can use it in your own personal database, or import it to another chat tool of your choice.

What's this for....exactly?
----
This will pull back all users, and all conversations attached to those users and stick it in a MySQL database for you to use later when you need to take it and put it somewhere else. This is just boilerplate code you for. You'll need to change what fields you want to save in the code after you clone/fork/copy this repo.

Requirements
----
- MySQL 5.5+
  - **Make sure to set your max_allowed_packet higher than the default of 16MB or your import mail fail. Go for 100MB for kicks.**
- PHP 7+ with cURL and JSON extensions loaded (maybe some others)
- Half a brain

Installation
----
- Get your database up and running. 
  - The schema is located in `initial_db_schema.sql`. You can simply run `mysql < initial_db_schema.sql` to get your database setup. 
  - You'll then need to setup db permissions for your MySQL user. 
- Take the `config.sample.php` file and rename it to `config.php` with your appropriate values.
- Run `composer install` to get the right packages to make this kitty purr.

Usage
----
Assuming you've set up your database permissions correctly, you can then run: 

```bash
php rip_that_data_back_from_them.php
``` 

and off it will go :)

There is another script called `unsnooze_and_snooze.php` that will go through all the conversations you've pulled back and unsnooze them and then snooze them. This is used in some cases with integrations you may have with Intercom where you need conversations to unsnooze and snooze in order for a integration to trigger.

This is currently based on Intercom v1.4 API

