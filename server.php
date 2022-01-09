<?php

require "vendor/autoload.php";

use Amp\ByteStream\ResourceOutputStream;
use Amp\Http\Server\Request;
use Amp\Http\Server\RequestHandler\CallableRequestHandler;
use Amp\Http\Server\Response;
use Amp\Http\Server\Router;
use Amp\Http\Status;
use Amp\Log\StreamHandler;
use Amp\Socket;
use Monolog\Logger;

// Run this script, then visit http://localhost:1337/ or https://localhost:1338/ in your browser.

Amp\Loop::run( static function () {

	$indexService = new \Zvvasya\KeyValueStorage\IndexService( 'db' );
	$storage      = new \Zvvasya\KeyValueStorage\Storage( 'db', $indexService );

	$servers    = [
		Socket\Server::listen( "0.0.0.0:1337" ),
		Socket\Server::listen( "[::]:1337" ),
	];
	$logHandler = new StreamHandler( new ResourceOutputStream( STDOUT ) );
	$logger     = new Logger( 'server' );
	$logger->pushHandler( $logHandler );

	$router = new Router;

	$router->addRoute( 'GET', '/', new CallableRequestHandler( function () {
		return new Response( Status::OK, [ 'content-type' => 'text/plain' ], 'key storage' );
	} ) );

	$router->addRoute( 'GET', '/value/{key}', new CallableRequestHandler( function ( Request $request ) use ( $storage ) {
		$args = $request->getAttribute( Router::class );

		if ( $storage->get( $args['key'] ) === null ) {
			return new Response( Status::NOT_FOUND, [ 'content-type' => 'text/plain' ], $storage->get( $args['key'] ) );
		}

		return new Response( Status::OK, [ 'content-type' => 'text/plain' ], $storage->get( $args['key'] ) );
	} ) );

	$router->addRoute( 'GET', '/value/{key}/{value}', new CallableRequestHandler( function ( Request $request ) use ( $storage ) {
		$args = $request->getAttribute( Router::class );
		$storage->add( $args['key'], $args['value']  );

		return new Response( Status::OK, [ 'content-type' => 'text/plain' ], $storage->get( $args['key'] ) );
	} ) );

	$server = new Amp\Http\Server\Server( $servers, $router, $logger );

	yield $server->start();

	// Stop the server when SIGINT is received (this is technically optional, but it is best to call Server::stop()).
	Amp\Loop::onSignal( \SIGINT, static function ( string $watcherId ) use ( $server ) {
		Amp\Loop::cancel( $watcherId );
		yield $server->stop();
	} );
} );


//
//
//
//
//$storage->add('user', 'value');
