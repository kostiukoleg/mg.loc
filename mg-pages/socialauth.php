<?php
$s = file_get_contents('http://ulogin.ru/token.php?token=' . $_POST['token'] . '&host=' . $_SERVER['HTTP_HOST']);
$user = json_decode($s, true);
//var_dump($user);
//$user['network'] - соц. сеть, через которую авторизовался пользователь
//$user['identity'] - уникальная строка определяющая конкретного пользователя соц. сети
//$user['first_name'] - имя пользователя
//$user['last_name'] - фамилия пользователя

$hash = md5($user['identity']);

try {
    $res = DB::query('
      SELECT *
      FROM `'.PREFIX.'user`
      WHERE ulogin_hash = "%s"
    ', $hash);

    if ($row = DB::fetchObject($res)) {
	  $ex_user = $row;
    }
	
	if (!isset($ex_user)) {
		$userData = array(
			'ulogin_hash' => $hash,
			'pass' => md5(microtime(true)),
			'email' => $user['email'],
			'role' => 2,
			'name' => $user['first_name'],
			'sname' => $user['last_name'],
			'address' => '',
			'phone' => '',
			'activity' => 1,
		);
		
		USER::add($userData);
		$uid = DB::insertId();
		$userData = USER::getUserById($uid);
	}
	else {
		$uid = $ex_user->id;
		$userData = $ex_user;
	}

	$_SESSION['userAuthDomain'] = $_SERVER['SERVER_NAME'];
	$_SESSION['user'] = $userData;
	
	header('Location: '.SITE);
}
catch (Exception $error) {
	$userData = USER::getUserInfoByEmail($user['email']);
	$_SESSION['userAuthDomain'] = $_SERVER['SERVER_NAME'];
	$_SESSION['user'] = $userData;
	
	header('Location: '.SITE);
}
?>