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

// Get your client all nicely setup
$client = new IntercomClient($config['intercom_access_token']);

$resp = $client->users->scrollUsers([]);

$count = 1;
while (!empty($resp->scroll_param) && count($resp->users) > 0) {
	echo "USERS PAGE $count: " . count($resp->users) . "\n";
	$count = ++$count;

	foreach($resp->users as $user) {
		$user_check = $db->fetchField("SELECT COUNT(*) FROM users WHERE intercom_id = ?", [ $user->id ]);
		if(!empty($user_check)) {
			echo "Duplicate user {$user->email} - SKIPPING\n";
			continue;
		}

		$db->execute("INSERT INTO users SET
			intercom_id = ?,
			email = ?,
			phone = ?,
			name = ?,
			pseudonym = ?,
			app_id = ?,
			referrer = ?
		", [
			$user->id,
			$user->email,
			$user->phone,
			$user->name,
			$user->pseudonym,
			$user->app_id,
			$user->referrer
		]);
	}

	$resp = $client->users->scrollUsers(["scroll_param" => $resp->scroll_param]);
}

$resp = $client->admins->getAdmins([]);

foreach($resp->admins as $admin) {
	$user_check = $db->fetchField("SELECT COUNT(*) FROM admins WHERE intercom_id = ?", [ $admin->id ]);
	if(!empty($user_check)) {
		echo "Duplicate admin {$admin->email} - SKIPPING\n";
		continue;
	}

	$db->execute("INSERT INTO admins SET
		intercom_id = ?,
		email = ?,
		name = ?,
		team_id = ?
	", [
		$admin->id,
		$admin->email,
		$admin->name,
		join(',', $admin->team_ids)
	]);
}

$resp = $client->leads->scrollLeads([]);

$count = 1;
while (!empty($resp->scroll_param) && count($resp->contacts) > 0) {
	echo "LEADS PAGE $count: " . count($resp->contacts) . "\n";
	$count = ++$count;

	foreach($resp->contacts as $user) {
		$user_check = $db->fetchField("SELECT COUNT(*) FROM leads WHERE intercom_id = ? AND intercom_user_id = ?", [ $user->id, $user->user_id ]);
		if(!empty($user_check)) {
			echo "Duplicate user {$user->email} - SKIPPING\n";
			continue;
		}

		$db->execute("INSERT INTO leads SET
			intercom_id = ?,
			intercom_user_id = ?,
			email = ?,
			phone = ?,
			name = ?,
			pseudonym = ?
		", [
			$user->id,
			$user->user_id,
			utf8_encode($user->email),
			$user->phone,
			utf8_encode($user->name),
			$user->pseudonym
		]);
	}

	$resp = $client->leads->scrollLeads(["scroll_param" => $resp->scroll_param]);
}

$conv_resp = $client->conversations->getConversations([]);

$count = 1;
while(count($conv_resp->conversations) > 0) {
	echo "CONVERSATIONS PAGE $count: " . count($conv_resp->conversations) . "\n";
	++$count;
	foreach($conv_resp->conversations as $conversation) {

		// start with the conversation parent element
		$conversation_check = $db->fetchField("SELECT COUNT(*) FROM conversations WHERE intercom_id = ?", [ $conversation->id ]);
		if(!empty($conversation_check)) {
			echo "Duplicate conversation {$conversation->id} - SKIPPING\n";
			continue;
		}

		$customer_reply_url = $conversation->customer_first_reply->url ?? '';

		$db->execute("INSERT INTO conversations SET
			intercom_id = ?,
			created_at = ?,
			updated_at = ?,
			initiated_from_url = ?,
			assigned_to_type = ?,
			assigned_to_id = ?
		", [
			$conversation->id,
			date('Y-m-d H:i:s', $conversation->created_at),
			($conversation->updated_at ? date('Y-m-d H:i:s', $conversation->updated_at) : null),
			$customer_reply_url,
			$conversation->assignee->type,
			$conversation->assignee->id
		]);

		$conversation_id = $db->lastInsertId();
		echo "	CONVERSATION ID: {$conversation_id}\n";

		// pull out the tags
		if(count($conversation->tags->tags)) {
			echo "	CONVERSATION TAGS: " . count($conversation->tags->tags) . "\n";
			foreach($conversation->tags->tags as $tag) {

				$tag_check = $db->fetchField("SELECT COUNT(*) FROM conversation_tags WHERE conversation_id = ? AND tag_intercom_id = ?", [ $conversation_id, $tag->id ]);
				if(!empty($tag_check)) {
					echo "	Duplicate conversation tag {$tag->id} - SKIPPING\n";
					continue;
				}
				
				$db->execute("INSERT INTO conversation_tags SET
					conversation_id = ?,
					tag_intercom_id = ?,
					name = ?,
					applied_at = ?,
					applied_by_type = ?,
					applied_by_id = ?
				", [
					$conversation_id,
					$tag->id,
					utf8_encode($tag->name),
					date('Y-m-d H:i:s', $tag->applied_at),
					$tag->applied_by->type,
					$tag->applied_by->id
				]);
			}
		}

		// get all assigned people to the conversation
		if(count($conversation->customers)) {
			echo "	CONVERSATION CUSTOMERS: " . count($conversation->customers) . "\n";
			foreach($conversation->customers as $customer) {

				$customer_check = $db->fetchField("SELECT COUNT(*) FROM conversation_customers WHERE conversation_id = ? AND customer_id = ?", [ $conversation_id, $customer->id ]);
				if(!empty($customer_check)) {
					echo "	Duplicate conversation customer {$customer->id} - SKIPPING\n";
					continue;
				}

				$db->execute("INSERT INTO conversation_customers SET
					conversation_id = ?,
					customer_type = ?,
					customer_id = ?
				", [
					$conversation_id,
					$customer->type,
					$customer->id,
				]);
			}
		}

		// rip through any attachments attached to the main part of the conversation
		if(count($conversation->conversation_message->attachments)) {
			echo "	CONVERSATION ATTACHMENTS: " . count($conversation->conversation_message->attachments) . "\n";
			foreach($conversation->conversation_message->attachments as $attachment) {

				// use this as we aren't given a unique file id identifier...
				$unique_filename_hash = hash('sha256', $attachment->name);
				$attachment_check = $db->fetchField("SELECT COUNT(*) FROM conversation_attachments WHERE conversation_id = ? AND unique_filename_hash = ?", [ $conversation_id, $unique_filename_hash ]);
				if(!empty($attachment_check)) {
					echo "	Duplicate conversation attachment {$attachment->name} - SKIPPING\n";
					continue;
				}

				$db->execute("INSERT INTO conversation_attachments SET
					conversation_id = ?,
					unique_filename_hash = ?,
					attached_by_type = ?,
					attached_by_id = ?,
					type = ?,
					name = ?,
					url = ?,
					content = ?,
					content_type = ?,
					filesize = ?,
					width = ?,
					height = ?
				", [
					$conversation_id,
					$unique_filename_hash,
					$conversation->conversation_message->author->type,
					$conversation->conversation_message->author->id,
					$attachment->type,
					utf8_encode($attachment->name),
					$attachment->url,
					file_get_contents($attachment->url),
					$attachment->content_type,
					$attachment->filesize,
					$attachment->width,
					$attachment->height,
				]);
			}
		}

		// now we rip through all the conversation parts.

		$part_resp = $client->conversations->getConversation($conversation->id);
		echo "	CONVERSATION PARTS: " . count($part_resp->conversation_parts->conversation_parts) . "\n";
		if(isset($part_resp->conversation_parts->conversation_parts) && count($part_resp->conversation_parts->conversation_parts)) {
			foreach($part_resp->conversation_parts->conversation_parts as $part) {
				
				$part_check = $db->fetchField("SELECT COUNT(*) FROM conversation_parts WHERE conversation_id = ? AND intercom_id = ?", [ $conversation_id, $part->id ]);
				if(!empty($part_check)) {
					echo "	Duplicate conversation part {$part->id} - SKIPPING\n";
					continue;
				}

				$subject = isset($part->subject) ? $part->subject : '';
				$assigned_to_type = isset($part->assigned_to) && is_array($part->assigned_to) && count($part->assigned_to) ? $part->assigned_to->type : '';
				$assigned_to_id = isset($part->assigned_to) && is_array($part->assigned_to) && count($part->assigned_to) ? $part->assigned_to->id : '';

				$db->execute("INSERT INTO conversation_parts SET
					conversation_id = ?,
					intercom_id = ?,
					created_at = ?,
					updated_at = ?,
					assigned_to_type = ?,
					assigned_to_id = ?,
					author_type = ?,
					author_id = ?,
					subject = ?,
					body = ?
				", [
					$conversation_id,
					$part->id,
					date('Y-m-d H:i:s', $part->created_at),
					($part->updated_at ? date('Y-m-d H:i:s', $part->updated_at) : null),
					$assigned_to_type,
					$assigned_to_id,
					$part->author->type,
					$part->author->id,
					utf8_encode($subject),
					utf8_encode($part->body)
				]);

				$conversation_part_id = $db->lastInsertId();
				echo "	CONVERSATION PART ID: {$conversation_part_id}\n";

				// rip through any attachments attached to the main part of the conversation
				if(count($part->attachments)) {
					echo "		CONVERSATION PART ATTACHMENTS: " . count($part->attachments) . "\n";
					foreach($part->attachments as $attachment) {

						// use this as we aren't given a unique file id identifier...
						$unique_filename_hash = hash('sha256', $attachment->name);
						$attachment_check = $db->fetchField("SELECT COUNT(*) FROM conversation_part_attachments WHERE conversation_part_id = ? AND unique_filename_hash = ?", [ $conversation_part_id, $unique_filename_hash ]);
						if(!empty($attachment_check)) {
							echo "		Duplicate conversation attachment {$attachment->name} - SKIPPING\n";
							continue;
						}

						$db->execute("INSERT INTO conversation_attachments SET
							conversation_part_id = ?,
							unique_filename_hash = ?,
							attached_by_type = ?,
							attached_by_id = ?,
							type = ?,
							name = ?,
							url = ?,
							content = ?,
							content_type = ?,
							filesize = ?,
							width = ?,
							height = ?
						", [
							$conversation_part_id,
							$unique_filename_hash,
							$conversation->conversation_message->author->type,
							$conversation->conversation_message->author->id,
							$attachment->type,
							utf8_encode($attachment->name),
							$attachment->url,
							file_get_contents($attachment->url),
							$attachment->content_type,
							$attachment->filesize,
							$attachment->width,
							$attachment->height,
						]);
					}
				}

			}
		}
	}





	$conv_resp = $client->nextPage($conv_resp->pages);

}