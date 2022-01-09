<?php

namespace Zvvasya\KeyValueStorage;

class IndexService {

	private string $dir;
	private string $file;
	/** @var IndexInfo[] */
	private array $data = [];

	public function __construct( $dir ) {
		$this->dir  = $dir;
		$this->file = $this->dir . '/data.index';

		if ( ! file_exists( $this->file ) ) {
			file_put_contents( $this->file, '' );
		}

		$this->load();
	}

	public function __destruct() {
		$this->save();
	}

	public function add( string $key, int $offset, int $length ) {
		return $this->data[ $key ] = new IndexInfo( $key, $offset, $length );
	}

	public function clear( string $key ) {
		unset( $this->data[ $key ] );
	}

	public function getInfo( string $key ): ?IndexInfo {

		return $this->data[ $key ] ?? null;
	}

	/**
	 * @return IndexInfo
	 */
	public function iterate(): iterable {
		yield $this->data;
	}

	private function save() {
		$fp = fopen( $this->file, 'wb+' );

		foreach ( $this->data as $fields ) {
			fputcsv( $fp, $fields->toArray() );
		}

		fclose( $fp );
	}

	private function load() {
		// Read file from beginning.
		$fp = fopen( $this->file, 'rb+' );

		while ( ! feof( $fp ) && ( $line = fgetcsv( $fp ) ) !== false ) {
			$this->data[ $line[0] ] = new IndexInfo( $line[0], $line[1], $line[2] );
		}
	}
}
