<?php
declare( strict_types=1 );

namespace SmartSearchReplace\Support;

use SmartSearchReplace\Engine\ReplaceEngine;
use SmartSearchReplace\Engine\UrlProtector;
use SmartSearchReplace\Engine\Writer;
use SmartSearchReplace\Scope\Discoverer;
use SmartSearchReplace\Scope\Resolver;
use SmartSearchReplace\Scope\ScopeRegistry;

final class EngineFactory {

	private static ?ScopeRegistry $registry = null;

	public static function registry(): ScopeRegistry {
		if ( null === self::$registry ) {
			self::$registry = new ScopeRegistry( new Discoverer() );
		}
		return self::$registry;
	}

	public static function engine(): ReplaceEngine {
		return new ReplaceEngine(
			self::registry(),
			new Resolver(),
			new UrlProtector(),
			new Writer(),
		);
	}
}
