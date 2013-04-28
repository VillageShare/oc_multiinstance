<?php

	require_once '../../../3rdparty/phpass/PasswordHash.php';

	function getPasswordHash($password, $salt) {
			$forcePortable=(CRYPT_BLOWFISH!=1);
                        $hasher=new PasswordHash(8, $forcePortable);
			return $hasher->HashPassword($password.$salt);
	}
	
	if (php_sapi_name() == 'cli') {
		if (count($argv) < 3) {
			echo "Bad parameter count.  Should be <password> <salt>";
		}
		echo getPasswordHash($argv[1], $argv[2]) . "\n";
	}
