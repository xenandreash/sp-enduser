function Datastore($query)
{
	global $triggerurl;
	$data = cache [
				"ttl_function" => API_ttl,
				"update_function" => API_update,
				"namespace" => "Datastore",
			]
			http($triggerurl . "&type=datastore&$query", ["timeout" => 10, "ssl_default_ca" => true]);
	$list = json_decode($data);
	if (!is_array($list))
		return -1;
	return $list;
}

function API_ttl($new)
{
	if (is_array(json_decode($new)))
		return 300;
	return 60;
}

function API_update($old, $new)
{
	if (is_array(json_decode($new)))
		return $new;
	return $old;
}
