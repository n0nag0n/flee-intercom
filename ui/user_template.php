<style>

	.flee-intercom-container {
		width: 100%;
		max-width: 700px;
	}

	.flee-intercom-container h1, .flee-intercom-container h2, .flee-intercom-container h3 {
		border-bottom: 1px solid #aaaaaa;
	}

	.flee-intercom-container .conversation-part {
		border-radius: 5px;
		border: 1px solid #aaaaaa;
		padding: 10px;
		margin: 10px 0;
	}

	.flee-intercom-container .conversation-part-lead, .flee-intercom-container .conversation-part-user {
		text-align: left;
		background-color: #AAAAFF;
	}

	.flee-intercom-container .conversation-part-admin, .flee-intercom-container .conversation-part-bot {
		text-align: right;
		background-color: #AAFFAA;
	}
</style>
<div class="flee-intercom-container">
	<h1><?=$user_type?> Info</h1>
	<?php foreach($user['info'] as $key => $value) { if(!$value) continue; ?>
	<p><b><?=ucwords(str_replace('_', ' ', $key))?>:</b> <?=$value?></p>
	<?php } ?>
	<?php if(count($user['conversation_history'])) { ?>

	<h2>Conversation History</h2>
	<div style="padding-left: 40px;">
		<?php foreach($user['conversation_history'] as $conversation) { ?>
			<p><b>Conversation</b> - <i>Started <?=date('n/j/Y g:i a', strtotime($conversation['created_at'].' UTC'))?> UTC, Ended <?=date('n/j/Y g:i a', strtotime($conversation['updated_at'].' UTC'))?> UTC</i></p>
			<?php if(count($conversation['attachments'])) { ?>

			<h3>Attachments</h3>
			<?php foreach($conversation['attachments'] as $attachment) { ?>
				<p><b><?=$attachment['name']?></b> - <i><?=$attachment['filesize']?> Bytes</i> <a href="<?=$attachment['url']?>">Intercom Download URL</a> <a href="<?=$attachment['local_download_url']?>&api_token=<?=$api_token?>">Local URL</a></p>
				<?php if($attachment['is_image']) { ?>
				<div><img style="max-width: 500px; max-height: 500px" src="/downloadFile?id=<?=$attachment['id']?>&unique_filename_hash=<?=$attachment['unique_filename_hash']?>&image=true&api_token=<?=$api_token?>" /></div>
				<?php } ?>
			<?php } ?>

			<?php } ?>
			<div style="padding-left: 40px;">
				<?php foreach($conversation['parts'] as $part) { ?>

				<?php if(count($part['attachments'])) { ?>

				<h3>Attachments</h3>
				<?php foreach($part['attachments'] as $attachment) { ?>
				<p><b><?=$attachment['name']?></b> - <i><?=$attachment['filesize']?> Bytes</i> <a href="<?=$attachment['url']?>">Intercom Download URL</a> <a href="<?=$attachment['local_download_url']?>&api_token=<?=$api_token?>">Local URL</a></p>
				<?php if($attachment['is_image']) { ?>
				<div><img style="max-width: 500px; max-height: 500px" src="/downloadFile?id=<?=$attachment['id']?>&unique_filename_hash=<?=$attachment['unique_filename_hash']?>&image=true&api_token=<?=$api_token?>" /></div>
				<?php } ?>
				<?php } ?>

				<?php } ?>
				
				<?php if($part['subject']) { ?>
				<p><b>Subject: </b> <?=$part['subject']?></p>
				<?php } ?>
				<div class="conversation-part conversation-part-<?=$part['author_type']?>">
					<p><b><?=$part['author']['name']?></b> - <i><?=date('n/j/Y g:i a', strtotime($conversation['updated_at'].' UTC'))?> UTC</i></p>
					<?=$this->raw($part['body'])?>
				</div>
				<?php } ?>
			</div>
			<?php } ?>
		</div>

	<?php } ?>
</div>
