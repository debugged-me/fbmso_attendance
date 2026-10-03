<?php
defined('BASEPATH') OR exit('No direct script access allowed');

echo "\nERROR" . (isset($status_code) && $status_code ? ' ' . (int)$status_code : '') . ': ',
	$heading,
	"\n\n",
	$message,
	"\n\n";
