<?php
require_once "functions.php";
include "config.php";
include dirname(__FILE__) . "/../../../../wp-config.php";

require_once dirname( __FILE__ ) . "/../classes/WCAPI/includes.php";

$db = new mysqli(DB_HOST,DB_USER,DB_PASSWORD,DB_NAME);
$db->set_charset( DB_CHARSET );


$mapper = new Mapper( $db );

$Header("General Mapper Setup");
notEqual($mapper->connection,null);
shouldBeTypeOf($mapper->errors, 'array');
hasAtMost( $mapper->details, 0);
equal( $mapper->table_prefix, '');

$mapper->table_prefix = $table_prefix;

equal( $mapper->table_prefix, $table_prefix );
equal( $mapper->self, $mapper, '$self should equal $this');

$Header("Testing Create Functionality");

$old_self = $mapper;
$mock = new Mock();
$mock->whenAttr('table_prefix','mock_');
$mapper->setSelf($mock);

$model = new \WCAPI\Product();

$mapper->create($model,array());

$mock->hasReceived('table_prefix');

