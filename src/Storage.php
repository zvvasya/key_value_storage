<?php

namespace Zvvasya\KeyValueStorage;

use function rand;

class Storage {
	private string $dbLocation = '';
	private string $dbDataFile = '';

	public function __construct( string $dbLocation, IndexService $indexService ) {
		$this->dbLocation   = $dbLocation;
		$this->dbDataFile   = $this->dbLocation . '/data.db';
		$this->indexService = $indexService;

		if ( ! file_exists( $this->dbDataFile ) ) {
			file_put_contents( $this->dbDataFile, '' );
		}
	}

	public function add( string $key, string $value ) {
		//lock
		clearstatcache();
		$offset = filesize( $this->dbLocation . '/data.db' );
		$this->indexService->add( $key, $offset, strlen( $value ) );
		$this->write( $value );
		//unlock
	}

	public function update( string $key, string $value ) {
		$this->add( $key, $value );
	}

	public function delete( string $key ) {
		$this->indexService->clear( $key );
	}

	public function get( string $key ): ?string {
		$contents = null;
		$record   = $this->indexService->getInfo( $key );
		if ( $record ) {
			$fp = fopen( $this->dbDataFile, "rb+" );
			if ( $fp ) {
				fseek( $fp, $record->offset );
				$contents = fgets( $fp, $record->length + 1 );
			}
			fclose( $fp );
		}

		return $contents;
	}

	public function write( string $value ): ?string {
		//		$count = 0;
		//		$timeout_secs = 10; //number of seconds of timeout
		//		$got_lock = true;
		//		while (!flock($fp, LOCK_EX | LOCK_NB, $wouldblock)) {
		//		    if ($wouldblock && $count++ < $timeout_secs) {
		//		        sleep(1);
		//		    } else {
		//		        $got_lock = false;
		//		        break;
		//		    }
		//		}
		//		if ($got_lock) {
		//		    // Do stuff with file
		//		}
		$result = true;
		$fp     = fopen( $this->dbDataFile, 'ab' );
		if ( $fp ) {
			if ( fwrite( $fp, $value ) === false ) {
				echo "Не могу произвести запись в файл ($this->dbDataFile)";
				$result = false;
			}
			fclose( $fp );
		}

		return $result;
	}

	public function optimization() {
		// lock
		$tmp_db = $this->dbLocation . '/' . random_int( 1000, 2000 ) . '_data.db';
		$fp     = fopen( $this->dbDataFile, "rb+" );
		$fpnew  = fopen( $tmp_db, "wb+" );
		if ( $fp ) {
			foreach ( $this->indexService->iterate() as $record ) {
				fseek( $fp, $record->offset );
				$value = fgets( $fp, $record->length + 1 );
				clearstatcache();
				$offset = filesize( $tmp_db );
				print_r( [ $record->key, $offset, $record->length ] );
				$this->indexService->add( $record->key, $offset, $record->length );
				fwrite( $fpnew, $value );
			}
		}
		fclose( $fp );
		fclose( $fpnew );
		// unlock
	}

	public function list(): iterable {
		foreach ( $this->indexService->iterate() as $record ) {
			yield [ $record->key, $record->length, $this->get( $record->key ) ];
		}
	}
}
