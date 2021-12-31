#!/usr/bin/env php
<?php
/**
 * WP Groove™ {@see https://wpgroove.com}
 *  _       _  ___       ___
 * ( )  _  ( )(  _`\    (  _`\
 * | | ( ) | || |_) )   | ( (_) _ __   _      _    _   _    __  ™
 * | | | | | || ,__/'   | |___ ( '__)/'_`\  /'_`\ ( ) ( ) /'__`\
 * | (_/ \_) || |       | (_, )| |  ( (_) )( (_) )| \_/ |(  ___/
 * `\___x___/'(_)       (____/'(_)  `\___/'`\___/'`\___/'`\____)
 */
// <editor-fold desc="Strict types, namespace, use statements, and other headers.">

/**
 * Declarations & namespace.
 *
 * @since 2021-12-25
 */
declare( strict_types = 1 ); // ｡･:*:･ﾟ★.
namespace WP_Groove\Framework_Dev\Toolchain\Composer;

/**
 * Utilities.
 *
 * @since 2021-12-15
 */
use Clever_Canyon\Utilities\STC\{Version_1_0_0 as U};
use Clever_Canyon\Utilities\OOP\Version_1_0_0\{Offsets, Generic, Error, Exception, Fatal_Exception};
use Clever_Canyon\Utilities\OOP\Version_1_0_0\Abstracts\{A6t_Base, A6t_Offsets, A6t_Generic, A6t_Error, A6t_Exception};
use Clever_Canyon\Utilities\OOP\Version_1_0_0\Interfaces\{I7e_Base, I7e_Offsets, I7e_Generic, I7e_Error, I7e_Exception};

/**
 * WP Groove utilities.
 *
 * @since 2021-12-15
 */
use WP_Groove\Framework\Utilities\STC\{Version_1_0_0 as UU};
use WP_Groove\Framework\Plugin\Version_1_0_0\Abstracts\{AA6t_Plugin};
use WP_Groove\Framework\Utilities\OOP\Version_1_0_0\Abstracts\{AA6t_App};

/**
 * Toolchain.
 *
 * @since 2021-12-15
 */
use Clever_Canyon\Utilities_Dev\Toolchain\{Tools as T};
use Clever_Canyon\Utilities_Dev\Toolchain\Composer\{Project};

/**
 * File-specific.
 *
 * @since 2021-12-15
 */
use Clever_Canyon\Utilities_Dev\Toolchain\Composer\Hooks\{Post_Update_Cmd_Handler as CC_Post_Update_Cmd_Handler};
use WP_Groove\Framework_Dev\Toolchain\Composer\Hooks\{Post_Update_Cmd_Handler};

// </editor-fold>

/**
 * Dev mode only.
 *
 * @since 2021-12-15
 */
if ( ! getenv( 'COMPOSER_DEV_MODE' ) ) {
	exit( 'Dev mode only.' );
}

/**
 * Handles `post-update-cmd` hook.
 *
 * @since 2021-12-15
 */
require_once getcwd() . '/vendor/autoload.php';
U\Env::config_debugging_mode();
error_log( '111111111111111111111111' );
new CC_Post_Update_Cmd_Handler();
error_log( '222222222222222222222222' );
new Post_Update_Cmd_Handler();
error_log( '333333333333333333333333' );
