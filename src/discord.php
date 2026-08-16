<?
declare(strict_types=1);

function discord_authorize_url(): string {
	return 'https://discord.com/api/oauth2/authorize?' . http_build_query([
		'client_id' => DISCORD_CLIENT_ID,
		'redirect_uri' => DISCORD_REDIRECT_URI,
		'response_type' => 'code',
		'scope' => 'identify',
	]);
}

function discord_fetch_user_for_code(string $authorization_code): array {
	$token_response = discord_api_request('https://discord.com/api/oauth2/token', [
		'client_id' => DISCORD_CLIENT_ID,
		'client_secret' => DISCORD_CLIENT_SECRET,
		'grant_type' => 'authorization_code',
		'code' => $authorization_code,
		'redirect_uri' => DISCORD_REDIRECT_URI,
	]);

	$curl_handle = curl_init('https://discord.com/api/users/@me');
	curl_setopt_array($curl_handle, [
		CURLOPT_RETURNTRANSFER => true,
		CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $token_response['access_token']],
	]);
	$response_body = curl_exec($curl_handle);
	curl_close($curl_handle);

	return json_decode((string) $response_body, true);
}

function discord_api_request(string $url, array $post_fields): array {
	$curl_handle = curl_init($url);
	curl_setopt_array($curl_handle, [
		CURLOPT_RETURNTRANSFER => true,
		CURLOPT_POST => true,
		CURLOPT_POSTFIELDS => http_build_query($post_fields),
	]);
	$response_body = curl_exec($curl_handle);
	curl_close($curl_handle);
	return json_decode((string) $response_body, true);
}
