<?php

namespace Zvvasya\KeyValueStorage;

class IndexInfo {
	public $key;
	public $offset;
	public $length;

	public function __construct( $key, $offset, $length ) {
		$this->key    = $key;
		$this->offset = $offset;
		$this->length = $length;
	}

	public function toArray(): array {
		return [
			$this->key,
			$this->offset,
			$this->length,
		];
	}
}
