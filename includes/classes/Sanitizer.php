<?php

namespace bKash\PGW\DC;

class Sanitizer {
	
	public static function hasPostField( string $key ): bool {
		return filter_has_var( INPUT_POST, $key );
	}

	public static function safePostValue( string $key ): string {
		return sanitize_text_field( filter_input( INPUT_POST, $key, FILTER_SANITIZE_SPECIAL_CHARS ) );
	}

	public static function hasGetField( string $key ): bool {
		return filter_has_var( INPUT_GET, $key );
	}

	public static function safeGetValue( string $key ): string {
		return sanitize_text_field( filter_input( INPUT_GET, $key, FILTER_SANITIZE_SPECIAL_CHARS ) );
	}

	public static function hasServerField( string $key ): string {
		return filter_has_var( INPUT_SERVER, $key );
	}

	public static function safeServerValue( string $key ): string {
		return sanitize_text_field( filter_input( INPUT_SERVER, $key, FILTER_SANITIZE_SPECIAL_CHARS ) );
	}

	public static function safeString( string $value ): string {
		return sanitize_text_field( $value );
	}

	public static function safeSqlString( string $value ): string {
		return sanitize_text_field( esc_sql( $value ) );
	}
}
