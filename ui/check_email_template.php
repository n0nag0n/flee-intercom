<!doctype html>
<html lang="en">
<html>
	<head>
		<meta charset="utf-8">
		<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
		<title>Flee Intercom HTML Interface</title>
	</head>
	<body>
		<form method="post" action="/checkEmail">
			<input type="hidden" name="api_token" value="<?=$api_token?>">
			<p>Email Address</p>
			<input type="email" name="email" value="<?=$email?>" placeholder="bob@example.com">
			<input type="submit" value="Get Data">
		</form>
		<?=$this->raw($html)?>
	</body>
</html>