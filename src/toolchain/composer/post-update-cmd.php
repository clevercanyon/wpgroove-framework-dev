#!/usr/bin/env php
<?php
/** WP Groove™ <https://wpgroove.com>
 *  _       _  ___       ___
 * ( )  _  ( )(  _`\    (  _`\
 * | | ( ) | || |_) )   | ( (_) _ __   _      _    _   _    __  ™
 * | | | | | || ,__/'   | |___ ( '__)/'_`\  /'_`\ ( ) ( ) /'__`\
 * | (_/ \_) || |       | (_, )| |  ( (_) )( (_) )| \_/ |(  ___/
 * `\___x___/'(_)       (____/'(_)  `\___/'`\___/'`\___/'`\____)
 */
namespace WP_Groove\Framework_Dev\Toolchain\Composer;

/**
 * Dependencies.
 *
 * @since 1.0.0
 */
use Clever_Canyon\Utilities\OOPs\Version_1_0_0 as U;
use WP_Groove\Framework\Utilities\OOPs\Version_1_0_0 as UU;

use Clever_Canyon\Utilities_Dev\Toolchain\Common\{ Utilities as Common };
use Clever_Canyon\Utilities_Dev\Toolchain\Composer\{ Project };
use WP_Groove\Framework_Dev\Toolchain\Composer\{ Utilities };

use Clever_Canyon\Utilities_Dev\Toolchain\Composer\Hooks\{ Post_Update_Cmd_Handler as CC_Post_Update_Cmd_Handler };
use WP_Groove\Framework_Dev\Toolchain\Composer\Hooks\{ Post_Update_Cmd_Handler };

/**
 * Dev mode only.
 *
 * @since 1.0.0
 */
if ( ! getenv( 'COMPOSER_DEV_MODE' ) ) {
	exit( 'Dev mode only.' );
}

/**
 * Handles `post-update-cmd` hook.
 *
 * @since 1.0.0
 */
require_once getcwd() . '/vendor/autoload.php';
new CC_Post_Update_Cmd_Handler();
new Post_Update_Cmd_Handler();
