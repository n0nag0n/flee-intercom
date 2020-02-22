<?php

use Intercom\IntercomClient;

require_once(__DIR__.'/vendor/autoload.php');
require_once(__DIR__.'/database_wrapper.php');

ini_set('display_errors', 1);

// Get your config
$config = require_once(__DIR__.'/config.php');

// Get a PDO instance ready for ya
$db = new Database('mysql:host='.$config['db_connection']['host'].';port='.$config['db_connection']['port'].';dbname='.$config['db_connection']['database'].';charset=UTF8', $config['db_connection']['username'], $config['db_connection']['password'], [
	PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
	PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES \'UTF8\'',
	PDO::ATTR_EMULATE_PREPARES => false,
	PDO::ATTR_STRINGIFY_FETCHES => false,
	PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
]);

$admin = $db->fetchRow("SELECT * FROM admins LIMIT 1");

if(!$admin['id']) {
	die('You must have an admin in the database first before you can snooze and unsnooze conversations.'."\n");
	exit(1);
}

// Get your client all nicely setup
$client = new IntercomClient($config['intercom_access_token']);

$conversations_statement = $db->prepare("SELECT intercom_id FROM conversations");

while($rs = $conversations_statement->fetch()) {
	$conversation_intercom_id = $rs['intercom_id'];

	$client->conversations->replyToConversation($conversation_intercom_id, [
		'type' => 'admin',
		'admin_id' => $admin['intercom_id'],
		'message_type' => 'open'
	]);

	$client->conversations->replyToConversation($conversation_intercom_id, [
		'type' => 'admin',
		'admin_id' => $admin['intercom_id'],
		'message_type' => 'close'
	]);
}

echo "PROCESS COMPLETE. THANKS FOR USING IT!\n\n";

exit(0);