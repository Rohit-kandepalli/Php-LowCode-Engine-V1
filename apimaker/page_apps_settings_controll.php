<?php

if( $_POST['action'] == "get_new_key" ){
	json_response([
		"status"=>"success",
		"key"=>$mongodb_con->generate_id()
	]);
	exit;
}

if( $_POST['action'] == "app_update_name" ){
	$t = validate_token("app_update.". $config_param1, $_POST['token']);
	if( $t != "OK" ){
		json_response("fail", $t);
	}
	$name = strtolower(trim($_POST['app']['app']));
	$des = trim($_POST['app']['des']);
	if( !preg_match("/^[a-z][a-z0-9\-]{3,25}$/", $name) ){
		json_response(['status'=>"fail", "error"=>"Name invalid"]);
	}
	if( !preg_match("/^[A-Za-z0-9\.\,\-\ \_\(\)\[\]\ \@\#\!\&\r\n\t]{4,50}$/", $des) ){
		json_response(['status'=>"fail", "error"=>"Description invalid"]);
	}
	$res = $mongodb_con->find_one( $config_global_apimaker['config_mongo_prefix'] . "_apps", [
		'app'=>$name,
		'_id'=>['!=', $config_param1]
	]);
	if( $res['data'] ){
		json_response(['status'=>"fail", "error"=>"An app already exists with same name"]);
	}

	$res = $mongodb_con->update_one( $config_global_apimaker['config_mongo_prefix'] . "_apps",[
		'_id'=>$config_param1
	],[
		'app'=>$name,
		'des'=>$des,
	]);
	json_response($res);

	exit;
}

if( $_POST['action'] == "settings_load_pages" ){
	$t = validate_token("getpages.". $config_param1, $_POST['token']);
	if( $t != "OK" ){
		json_response("fail", $t);
	}
	$res = $mongodb_con->find( $config_global_apimaker['config_mongo_prefix'] . "_pages", [
		'app_id'=>$config_param1
	],[
		'sort'=>['name'=>1],
		'limit'=>200,
		'projection'=>['version_id'=>1,'name'=>1]
	]);
	json_response($res);
}

if( $_POST['action'] == "app_save_custom_settings" ){

	$settings = $_POST['settings'];

	if( !isset($settings['host']) ){
		json_response("fail", "Incorrect data. Host settings missing.");
	}
	if( gettype($settings['host']) != "boolean" ){
		json_response("fail", "Incorrect data");
	}
	if( $settings['host'] ){
		if( !isset($settings['domains']) ){
			json_response("fail", "Incorrect data. Domain settings missing.");
		}
		foreach( $settings['domains'] as $i=>$j ){
			if( !preg_match("/^(https\:\/\/www\.|http\:\/\/www\.|https\:\/\/|http\:\/\/)(localhost|[0-9\.]{7,15}|[a-z0-9\-\.]{3,200}\.[a-z\.]{2,10})[\:0-9]*\/[a-z0-9\.\-\_\.\/]*/i", $j['url']) ){
				json_response("fail", "Incorrect url " . $j['url'] );
			}
			$v = parse_url($settings['domains'][ $i ]['url']);
			if( isset( $v['port'] ) ){
				$settings['domains'][ $i ]['domain'] = $v['host'] . ":" . $v['port'];
			}else{
				$settings['domains'][ $i ]['domain'] = $v['host'];
			}
			$settings['domains'][ $i ]['path'] = $v['path'];
		}
		if( !isset($settings['keys']) ){
			json_response("fail", "Incorrect data. Key settings missing.");
		}
		foreach( $settings['keys'] as $i=>$j ){
			if( !isset($j['key']) ){
				json_response("fail", "Incorrect key" );
			}
			if( !preg_match("/^[a-f0-9]{24}$/", $j['key'] ) ){
				json_response("fail", "Incorrect key: " . $j['key'] );
			}
			if( !isset($j['ips_allowed']) || !is_array($j['ips_allowed']) ){
				json_response("fail", "Incorrect Ip settings" );
			}
			if( sizeof($j['ips_allowed']) == 0 ){
				json_response("fail", "Incorrect IP settings");
			}
			foreach( $j['ips_allowed'] as $ipi=>$ipv ){
				if( $ipv['ip'] == "*" || $ipv['ip'] == "0.0.0.0/0" ){}else{
				if( !preg_match("/^[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\/(8|16|24|32)$/", $ipv['ip'] ) ){
					json_response("fail", "Incorrect IP format: " . $ipv['ip']);
				}}
				if( $ipv['action'] != "Allow" && $ipv['action'] != "Reject" ){
					json_response("fail", "Incorrect Action: " . $ipv['action']);
				}
			}
		}
	}else{
		$settings['domains'] = [];$settings['keys'] = [];
	}

	$res = $mongodb_con->update_one( $config_global_apimaker['config_mongo_prefix'] . "_apps", [
		'_id'=>$config_param1
	], [
		'settings.domains'=>$settings['domains'],
		'settings.keys'=>$settings['keys'],
		'settings.host'=>$settings['host'],
		'last_updated'=>date("Y-m-d H:i:s")
	]);
	if( $res['status'] == "fail" ){
		json_response( $res );
	}

	update_app_pages( $config_param1 );

	json_response([
		"status"=>"success",
	]);
	exit;
}

if( $_POST['action'] == "app_save_cloud_settings" ){

	$settings = $_POST['settings'];

	if( !isset($settings['cloud']) ){
		json_response("fail", "Incorrect data. Cloud settings missing.");
	}
	if( gettype($settings['cloud']) != "boolean" ){
		json_response("fail", "Incorrect cloud data");
	}
	if( $settings['cloud'] ){
		if( !preg_match("/^[a-zA-Z0-9\-\.]{2,25}$/i", $settings['cloud-subdomain']) ){
			json_response("fail", "Incorrect cloud subdomain " . $settings['cloud-subdomain'] );
		}
		if( isset($config_global_apimaker['config_cloud_domains']) ){
			if( !in_array( $settings['cloud-domain'], $config_global_apimaker['config_cloud_domains'] ) ){
				json_response("fail", "Incorrect cloud domain " . $settings['cloud-domain'] );
			}
		}
		if( trim($settings['cloud-enginepath']) != "" ){
			if( !preg_match("/^[a-z][a-zA-Z0-9\-\.\/\_\%]{2,250}$/i", $settings['cloud-enginepath']) ){
				json_response("fail", "Incorrect cloud engine path" . $settings['cloud-enginepath'] );
			}
		}
	}

	$cloud_record = false;
	$alias_record = false;
	if( isset($settings['cloud']) && $settings['cloud'] ){
		$res = $mongodb_con->find_one( $config_global_apimaker['config_mongo_prefix'] . "_cloud_domains", [
			'_id'=>$settings['cloud-subdomain'].".".$settings['cloud-domain']
		]);
		if( $res['data'] ){
			$cloud_record = true;
			if( $res['data']['app_id'] != $config_param1 ){
				json_response(['status'=>"fail", "error"=>"Cloud domain already in use"]);
			}
		}
	}
	if( isset($settings['alias']) && $settings['alias'] ){
		$res = $mongodb_con->find_one( $config_global_apimaker['config_mongo_prefix'] . "_cloud_domains", [
			'_id'=>$settings['alias-domain']
		]);
		if( $res['data'] ){
			$alias_record = true;
			if( $res['data']['app_id'] != $config_param1 ){
				json_response(['status'=>"fail", "error"=>"Alias domain already in use"]);
			}
		}
	}

	$res = $mongodb_con->update_one( $config_global_apimaker['config_mongo_prefix'] . "_apps", [
		'_id'=>$config_param1
	], [
		'settings.cloud'=>$settings['cloud'],
		'settings.cloud-subdomain'=>$settings['cloud-subdomain'],
		'settings.cloud-domain'=>$settings['cloud-domain'],
		'settings.cloud-enginepath'=>$settings['cloud-enginepath'],
		'settings.alias'=>$settings['alias'],
		'settings.alias-domain'=>$settings['alias-domain'],
		'last_updated'=>date("Y-m-d H:i:s")
	]);
	//update_app_last_change_date( $config_param1 );

	if( $res['status'] != "success" ){
		json_response( $res );
	}

	if( $cloud_record ){
		$res = $mongodb_con->update_one( $config_global_apimaker['config_mongo_prefix'] . "_cloud_domains", [
			'_id'=>$settings['cloud-subdomain'].".".$settings['cloud-domain']
		], [
			'app_id'=>$config_param1,
		]);
	}else{
		$res = $mongodb_con->insert( $config_global_apimaker['config_mongo_prefix'] . "_cloud_domains", [
			'_id'=>$settings['cloud-subdomain'].".".$settings['cloud-domain'],
			'app_id'=>$config_param1,
		]);
	}
	if( $alias_record ){
		$res = $mongodb_con->update_one( $config_global_apimaker['config_mongo_prefix'] . "_cloud_domains", [
			'_id'=>$settings['alias-domain']
		], [
			'app_id'=>$config_param1,
		]);
	}else{
		$res = $mongodb_con->insert( $config_global_apimaker['config_mongo_prefix'] . "_cloud_domains", [
			'_id'=>$settings['alias-domain'],
			'app_id'=>$config_param1,
		]);
	}

	update_app_pages( $config_param1 );

	json_response([
		"status"=>"success",
	]);
	exit;
}

if( $_POST['action'] == "app_save_other_settings" ){

	$homepage = $_POST['homepage'];

	if( !isset($homepage) ){
		json_response("fail", "Incorrect data. Page settings missing.");
	}
	if( !preg_match("/^([a-f0-9]{24})\:([a-f0-9]{24})$/", $homepage['v'], $m) ){
		json_response("fail", "Incorrect data. Incorrect format.");
	}
	//print_r($m);exit;
	$res = $mongodb_con->find_one( $config_global_apimaker['config_mongo_prefix'] . "_pages", [
		'_id'=>$m[1]
	]);
	if( !$res['data'] ){
		json_response("fail", "Page not found.");
	}
	$res = $mongodb_con->find_one( $config_global_apimaker['config_mongo_prefix'] . "_pages_versions", [
		'_id'=>$m[2],
		'page_id'=>$m[1]
	]);
	if( !$res['data'] ){
		json_response("fail", "Page Version not found.");
	}

	$res = $mongodb_con->update_one( $config_global_apimaker['config_mongo_prefix'] . "_apps", [
		'_id'=>$config_param1
	], [
		'settings.homepage'=>$homepage,
		'last_updated'=>date("Y-m-d H:i:s")
	]);
	if( $res['status'] == "fail" ){
		json_response( $res );
	}

	update_app_pages( $config_param1 );

	json_response([
		"status"=>"success",
	]);
	exit;
}



function update_global_settings(){
		$global_settings = [];
	foreach( $settings['keys'] as $ki=>$kd ){
		$global_settings[ $kd['key'] ] = [
			'ips'=>[],
			'domains'=>[]
		];
		foreach( $kd['ips_allowed'] as $iv=>$ip ){
			$global_settings[ $kd['key'] ][ 'ips' ][ $ip['ip'] ] = $ip['action'];
		}
		foreach( $settings['domains'] as $i=>$dv ){
			$global_settings[ $kd['key'] ][ 'domains' ][ $dv['domain'] ][ $dv['path'] ] = ["ok"=>1];
		}
	}
	//print_pre( $global_settings );exit;

	foreach( $global_settings as $key=>$st ){
		$res = $mongodb_con->update_one( $config_global_apimaker['config_mongo_prefix'] . "_settings", [
			'_id'=>"app_key_".$key
		], $st, ['upsert'=>true]);
		if( $res['status'] == "fail" ){
			json_response( $res );
		}
	}
}

//print_r( $res );
if( !$app['settings'] ){
	$settings = [
		"host"=>false,
		"domains"=>[
			[
				"domain"=>$_SERVER['HTTP_HOST'],
				"url"=>"http://".$_SERVER['HTTP_HOST']."/engine/",
				"path"=>"/engine/"
			]
		],
		"keys"=>[
			[
				"key"=>$mongodb_con->generate_id(),
				"ips_allowed"=>[
					["ip"=>"*", "action"=>"Allow"],
					["ip"=>"127.0.0.1/32", "action"=>"Allow"],
					["ip"=>"10.10.10.0/24", "action"=>"Allow"],
					["ip"=>"10.10.0.0/16", "action"=>"Allow"],
					["ip"=>"10.0.0.0/16", "action"=>"Reject"]
				],
			]
		],
		"cloud"=>false,
		"cloud-subdomain"=>$app['app'],
		"cloud-enginepath"=>"",
		"cloud-domain"=>isset($config_global_apimaker['config_cloud_default_domain'])?$config_global_apimaker['config_cloud_default_domain']:"",
		"alias"=>false,
		"alias-domain"=>"www.example.com",
		"homepage"=> ['t'=>"page", 'v'=>""],
	];
}else{
	$settings = $app['settings'];
}

$loc = [
	"./config_global_engine.php",
	"../config_global_engine.php",
	"../../config_global_engine.php",
	"/tmp/config_global_engine.php",
];

$engined = "";
$enginep = "";
foreach( $loc as $i=>$j ){
	if( file_exists($j) ){
		$enginep = $j;
		$engined = file_get_contents($j);
		break;
	}
}