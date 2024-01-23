<?php

if( $_POST['action'] == "login" ){

	if( !$_POST['token'] ){
		json_response([
			"status"=>"fail",
			"error"=>"Token not found"
		]);
	}
	$token_status = validate_token(  "login", $_POST['token'] );

	if( $token_status != "OK" ){
		json_response([
			"status"=>"TokenError",
			"error"=>$token_status
		]);
	}

	if( $_POST['captcha'] != $_SESSION['login_captcha'] || $_POST['captcha_code'] != $_SESSION['login_code'] ){
		json_response([
			"status"=>"fail",
			"error"=>"Incorrect Code",
			//"session"=>$_SESSION
		]);
	}

	$usr = $_POST['user'];
	$pass = $_POST['pass'];
	$pass = substr($pass, 10, 160);
	$pass = str_replace("<?=session_id() ?>", "", $pass);
	try{
		$pass = base64_decode($pass);
		$res = $mongodb_con->find_one( $config_global_apimaker['config_mongo_prefix'] . "_users", [
			"username"=>$usr,
			"password"=>pass_hash($pass)
		]);
		if( $res['data'] ){
			$_SESSION['apimaker_login_ok'] = true;
			$_SESSION['apimaker_login_id'] = $res['data']['_id'];
			session_regenerate_id();
			$res = $mongodb_con->update_one( $config_global_apimaker['config_mongo_prefix'] . "_tmp", [
				"_id"=>"login_session"
			],[
				"_id"=>"login_session",
				"value"=>session_id()
			],['upsert'=>true] );
			json_response([
				"status"=>"success",
			]);
		}else{
			json_response([
				"status"=>"fail",
				"error"=>"incorrect"
			]);
		}
	}catch(Exception $ex){
		json_response([
			"status"=>"fail",
			"error"=>$ex->getMessage()
		]);
	}
	exit;
}