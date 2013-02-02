<?php

require("codebird.php");
require("config.php");

Codebird::setConsumerKey($consumer_key, $consumer_secret);
$cb = Codebird::getInstance();
$cb->setToken($access_token, $access_token_secret);
$cb->setReturnFormat(CODEBIRD_RETURNFORMAT_ARRAY);

$all_users = array();

foreach ($all_lists as $list) {

	echo $list."\n";

	list($list_user, $list_name) = explode("/", $list);

	$cursor = "-1";

	$params = array(
		"owner_screen_name" => $list_user,
		"slug" => $list_name,
		"include_entities" => "false",
		"skip_status" => "true"
	);

	// проходим все страницы списка (по 20 юзеров на странице)
	do {

		$params["cursor"] = $cursor;

		$reply = $cb->lists_members($params);

		foreach ($reply["users"] as $user) {
			array_push($all_users, $user);
		}

		$cursor = $reply["next_cursor"];

	} while ($cursor);

}

// удаляем дубликаты из общего массива
$all_users_unique = array();

foreach ($all_users as $user) {
	$screen_name = $user["screen_name"];
	$all_users_unique[$screen_name] = $user;
}

$all_users = $all_users_unique;

// сортируем по дате (на самом деле по id)
// функция для сортировки многомерных массивов по их элементам найдена где-то в сети
function array_orderby() {
	$args = func_get_args();
	$data = array_shift($args);

	foreach ($args as $n => $field) {
		if (is_string($field)) {
			$tmp = array();
			foreach ($data as $key => $row) $tmp[$key] = $row[$field];
			$args[$n] = $tmp;
		}
	}

	$args[] = &$data;
	call_user_func_array('array_multisort', $args);

	return array_pop($args);
}

$all_users = array_orderby($all_users, "id", SORT_ASC);

// выводим

$output = "";
$i = 1;
$output_index_format = "%0".strlen(count($all_users))."d";

foreach ($all_users as $user => $data) {
	$register_date = $data["created_at"];
	$register_date_unix = strtotime($register_date);
	$register_date = strftime("%d.%m.%Y", $register_date_unix);

	$output .= sprintf($output_index_format, $i).". ".$data["id"]." — ".$register_date." — ".$user." — ".$data["location"]."\n";

	$i++;
}

file_put_contents("output.txt", $output);

?>