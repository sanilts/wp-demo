<?php
/**
 * Requires at least: 5.9.0
 * Requires PHP:      7.2
 * Version:           231129
 */
if ( ! function_exists( 'is_plugin_active' ) ) {
    require_once ABSPATH . 'wp-admin/includes/plugin.php';
}
if ( ! function_exists( 'wp_get_current_user' ) ) {
    require_once( ABSPATH . 'wp-includes/pluggable.php' );
}

return [
    'is_plugin_installed' => function ( $plugin ): bool {
        $installed_plugins = get_plugins();
        return isset( $installed_plugins[ $plugin ] );
    },
    'json_response' => function ( $data ) {
        return [
            'response' => [ 'code' => 200, 'message' => 'OK' ],
            'body'     => json_encode( $data )
        ];
    },
    'private_property' => function ( object $object, string $property ) {
        $reflectionProperty = new \ReflectionProperty( get_class( $object ), $property );
        $reflectionProperty->setAccessible( true );
        return $reflectionProperty->getValue( $object );
    },
    'serialize_response' => function ( $data ): array {
        return [
            'response' => [ 'code' => 200, 'message' => 'OK' ],
            'body'     => serialize( $data )
        ];
    }
];