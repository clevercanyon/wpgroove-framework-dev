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
namespace WP_Groove\Framework_Dev\Toolchain\Composer\Hooks;

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
use Aws\S3\S3Client;
use Aws\Exception\AwsException;

// </editor-fold>

/**
 * On `post-update-cmd` hook.
 *
 * @since 2021-12-15
 */
class Post_Update_Cmd_Handler extends \Clever_Canyon\Utilities\OOP\Version_1_0_0\Abstracts\A6t_CLI_Tool {
	/**
	 * Project.
	 *
	 * @since 2021-12-15
	 */
	protected Project $project;

	/**
	 * Version.
	 */
	protected const VERSION = '1.0.0';

	/**
	 * Tool name.
	 */
	protected const NAME = 'Hook/Post_Update_Cmd_Handler';

	/**
	 * Constructor.
	 *
	 * @param string|array|null $args_to_parse Optional custom args to parse instead of `$_SERVER['argv']`.
	 *                                         If not given, defaults internally to `$_SERVER['argv']`.
	 */
	public function __construct( /* string|array|null */ $args_to_parse = 'update' ) {
		parent::__construct( $args_to_parse );
		$this->add_commands( [ 'update' => [] ] );

		if ( U\Env::var( 'COMPOSER_DEV_MODE' ) ) {
			U\Env::config_debugging_mode();
			$this->route_request();
		}
	}

	/**
	 * Command: `update`.
	 *
	 * @since 2021-12-15
	 */
	protected function update() : void {
		try {
			$this->project = new Project( getcwd() );
			$this->maybe_run_wp_project_sub_composer_updates();

			$this->maybe_symlink_wp_plugin_locally();
			$this->maybe_symlink_wp_theme_locally();

			$this->maybe_sync_wp_plugin_headers();
			$this->maybe_sync_wp_theme_headers();

			$this->maybe_compile_wp_plugin_svn_repo();
			$this->maybe_compile_wp_theme_svn_repo();

			$this->maybe_compile_wp_plugin_zip();
			$this->maybe_compile_wp_theme_zip();

			$this->maybe_s3_upload_wp_plugin_zip();
			$this->maybe_s3_upload_wp_theme_zip();

		} catch ( \Throwable $throwable ) {
			U\CLI::error( $throwable->getMessage() );
			U\CLI::exit_status( 1 );
		}
	}

	/**
	 * Maybe run WordPress project sub-Composer updates.
	 *
	 * @since 2021-12-15
	 *
	 * @throws Exception Whenever any failure occurs.
	 */
	protected function maybe_run_wp_project_sub_composer_updates() : void {
		if ( ! $this->project->is_wp_project() ) {
			return; // Not applicable.
		}
		if ( $this->project->has_file( 'trunk/composer.json' ) ) {
			U\CLI::run( [ 'composer', 'update' ], U\Dir::join( $this->project->dir, '/trunk' ) );
		}
	}

	/**
	 * Maybe symlink WordPress plugin locally.
	 *
	 * @since 2021-12-15
	 *
	 * @throws Exception Whenever any failure occurs.
	 */
	protected function maybe_symlink_wp_plugin_locally() : void {
		if ( ! $this->project->is_wp_plugin() ) {
			return; // Not applicable.
		}
		if ( ! $local_wp_public_html_dir = $this->project->local_wp_public_html_dir() ) {
			return; // Not possible.
		}
		$plugin           = $this->project->wp_plugin_data();
		$local_plugin_dir = U\Dir::join( $local_wp_public_html_dir, '/wp-content/plugins/' . $plugin->slug );

		if ( U\Fs::path_exists( $local_plugin_dir ) ) {
			return; // Do not overwrite.
		}
		if ( ! is_writable( U\Dir::name( $local_plugin_dir ) ) ) {
			throw new Exception( 'Failed to symlink local WordPress plugin directory. Directory not writable: ' . U\Dir::name( $local_plugin_dir ) );
		}
		if ( ! symlink( $plugin->dir, $local_plugin_dir ) ) {
			throw new Exception( 'Failed to symlink local WordPress plugin directory: ' . $local_plugin_dir );
		}
	}

	/**
	 * Maybe symlink WordPress theme locally.
	 *
	 * @since 2021-12-15
	 *
	 * @throws Exception Whenever any failure occurs.
	 */
	protected function maybe_symlink_wp_theme_locally() : void {
		if ( ! $this->project->is_wp_theme() ) {
			return; // Not applicable.
		}
		if ( ! $local_wp_public_html_dir = $this->project->local_wp_public_html_dir() ) {
			return; // Not possible.
		}
		$theme           = $this->project->wp_theme_data();
		$local_theme_dir = U\Dir::join( $local_wp_public_html_dir, '/wp-content/themes/' . $theme->slug );

		if ( U\Fs::path_exists( $local_theme_dir ) ) {
			return; // Do not overwrite.
		}
		if ( ! is_writable( U\Dir::name( $local_theme_dir ) ) ) {
			throw new Exception( 'Failed to symlink local WordPress theme directory. Directory not writable: ' . U\Dir::name( $local_theme_dir ) );
		}
		if ( ! symlink( $theme->dir, $local_theme_dir ) ) {
			throw new Exception( 'Failed to symlink local WordPress theme directory: ' . $local_theme_dir );
		}
	}

	/**
	 * Maybe sync WordPress plugin headers.
	 *
	 * @since 2021-12-15
	 *
	 * @throws Exception Whenever any failure occurs.
	 */
	protected function maybe_sync_wp_plugin_headers() : void {
		if ( ! $this->project->is_wp_plugin() ) {
			return; // Not applicable.
		}
		$plugin           = $this->project->wp_plugin_data();
		$local_wp_version = $this->project->local_wp_version();

		if ( $local_wp_version && version_compare( $local_wp_version, $plugin->headers->tested_up_to_wp_version, '>' ) ) {
			$plugin->headers->tested_up_to_wp_version = $local_wp_version;
		}
		// The existence of these files already confimred by {@see Project::wp_plugin_data()}.

		$plugin_file_contents        = file_get_contents( $plugin->file );
		$plugin_readme_file_contents = file_get_contents( $plugin->readme_file );

		foreach ( $plugin->headers->_map as $_prop => $_header ) {
			$plugin_file_contents        = preg_replace( '/^(\h*\*\h*)?' . U\Str::esc_reg( $_header ) . '\:\h*.*$/umi', '${1}' . $_header . ': ' . $plugin->headers->{$_prop}, $plugin_file_contents );
			$plugin_readme_file_contents = preg_replace( '/^(\h*)?' . U\Str::esc_reg( $_header ) . '\:\h*.*$/umi', '${1}' . $_header . ': ' . $plugin->headers->{$_prop}, $plugin_readme_file_contents );
		}
		$plugin_file_contents = preg_replace( '/^(\h*)["\'][^"\']*["\']\h*(,)?\h*\/\/\h*@slug\h*$/uim', '${1}' . "'" . U\Str::esc_sq( $plugin->slug ) . "'" . '${2} // @slug', $plugin_file_contents );
		$plugin_file_contents = preg_replace( '/^(\h*)["\'][^"\']*["\']\h*(,)?\h*\/\/\h*@name\h*$/uim', '${1}' . "'" . U\Str::esc_sq( $plugin->headers->name ) . "'" . '${2} // @name', $plugin_file_contents );
		$plugin_file_contents = preg_replace( '/^(\h*)["\'][^"\']*["\']\h*(,)?\h*\/\/\h*@version\h*$/uim', '${1}' . "'" . U\Str::esc_sq( $plugin->headers->version ) . "'" . '${2} // @version', $plugin_file_contents );

		if ( false === file_put_contents( $plugin->file, $plugin_file_contents ) ) {
			throw new Exception( 'Unable to update plugin file when syncing versions: ' . $plugin->file );
		}
		if ( false === file_put_contents( $plugin->readme_file, $plugin_readme_file_contents ) ) {
			throw new Exception( 'Unable to update plugin readme file when syncing versions: ' . $plugin->readme_file );
		}
	}

	/**
	 * Maybe sync WordPress theme headers.
	 *
	 * @since 2021-12-15
	 *
	 * @throws Exception Whenever any failure occurs.
	 */
	protected function maybe_sync_wp_theme_headers() : void {
		if ( ! $this->project->is_wp_theme() ) {
			return; // Not applicable.
		}
		$theme            = $this->project->wp_theme_data();
		$local_wp_version = $this->project->local_wp_version();

		if ( $local_wp_version && version_compare( $local_wp_version, $theme->headers->tested_up_to_wp_version, '>' ) ) {
			$theme->headers->tested_up_to_wp_version = $local_wp_version;
		}
		// The existence of these files already confimred by {@see Project::wp_theme_data()}.

		$theme_file_contents           = file_get_contents( $theme->file );
		$theme_functions_file_contents = file_get_contents( $theme->functions_file );
		$theme_style_file_contents     = file_get_contents( $theme->style_file );
		$theme_readme_file_contents    = file_get_contents( $theme->readme_file );

		foreach ( $theme->headers->_map as $_prop => $_header ) {
			$theme_file_contents           = preg_replace( '/^(\h*\*\h*)?' . U\Str::esc_reg( $_header ) . '\:\h*.*$/umi', '${1}' . $_header . ': ' . $theme->headers->{$_prop}, $theme_file_contents );
			$theme_functions_file_contents = preg_replace( '/^(\h*\*\h*)?' . U\Str::esc_reg( $_header ) . '\:\h*.*$/umi', '${1}' . $_header . ': ' . $theme->headers->{$_prop}, $theme_functions_file_contents );
			$theme_style_file_contents     = preg_replace( '/^(\h*\*\h*)?' . U\Str::esc_reg( $_header ) . '\:\h*.*$/umi', '${1}' . $_header . ': ' . $theme->headers->{$_prop}, $theme_style_file_contents );
			$theme_readme_file_contents    = preg_replace( '/^(\h*)?' . U\Str::esc_reg( $_header ) . '\:\h*.*$/umi', '${1}' . $_header . ': ' . $theme->headers->{$_prop}, $theme_readme_file_contents );
		}
		$theme_file_contents = preg_replace( '/^(\h*)["\'][^"\']*["\']\h*(,)?\h*\/\/\h*@slug\h*$/uim', '${1}' . "'" . U\Str::esc_sq( $theme->slug ) . "'" . '${2} // @slug', $theme_file_contents );
		$theme_file_contents = preg_replace( '/^(\h*)["\'][^"\']*["\']\h*(,)?\h*\/\/\h*@name\h*$/uim', '${1}' . "'" . U\Str::esc_sq( $theme->headers->name ) . "'" . '${2} // @name', $theme_file_contents );
		$theme_file_contents = preg_replace( '/^(\h*)["\'][^"\']*["\']\h*(,)?\h*\/\/\h*@version\h*$/uim', '${1}' . "'" . U\Str::esc_sq( $theme->headers->version ) . "'" . '${2} // @version', $theme_file_contents );

		$theme_functions_file_contents = preg_replace( '/^(\h*)["\'][^"\']*["\']\h*(,)?\h*\/\/\h*@slug\h*$/uim', '${1}' . "'" . U\Str::esc_sq( $theme->slug ) . "'" . '${2} // @slug', $theme_functions_file_contents );
		$theme_functions_file_contents = preg_replace( '/^(\h*)["\'][^"\']*["\']\h*(,)?\h*\/\/\h*@name\h*$/uim', '${1}' . "'" . U\Str::esc_sq( $theme->headers->name ) . "'" . '${2} // @name', $theme_functions_file_contents );
		$theme_functions_file_contents = preg_replace( '/^(\h*)["\'][^"\']*["\']\h*(,)?\h*\/\/\h*@version\h*$/uim', '${1}' . "'" . U\Str::esc_sq( $theme->headers->version ) . "'" . '${2} // @version', $theme_functions_file_contents );

		if ( false === file_put_contents( $theme->file, $theme_file_contents ) ) {
			throw new Exception( 'Unable to update theme file when syncing versions: ' . $theme->file );
		}
		if ( false === file_put_contents( $theme->functions_file, $theme_functions_file_contents ) ) {
			throw new Exception( 'Unable to update theme functions file when syncing versions: ' . $theme->functions_file );
		}
		if ( false === file_put_contents( $theme->style_file, $theme_style_file_contents ) ) {
			throw new Exception( 'Unable to update theme style file when syncing versions: ' . $theme->style_file );
		}
		if ( false === file_put_contents( $theme->readme_file, $theme_readme_file_contents ) ) {
			throw new Exception( 'Unable to update theme readme file when syncing versions: ' . $theme->readme_file );
		}
	}

	/**
	 * Maybe compile WordPress plugin's SVN repo.
	 *
	 * @since 2021-12-15
	 *
	 * @throws Exception Whenever any failure occurs.
	 */
	protected function maybe_compile_wp_plugin_svn_repo() : void {
		if ( ! $this->project->is_wp_plugin() ) {
			return; // Not applicable.
		}
		$plugin                           = $this->project->wp_plugin_data();
		$plugin_svn_comp_dir_prune_config = $this->project->distro_prune_config();
		$plugin_svn_comp_dir              = U\Dir::join( $this->project->dir, '/._x/svn-comp' );
		$plugin_svn_repo_dir              = U\Dir::join( $this->project->dir, '/._x/svn-repo' );

		if ( ! U\Fs::copy( $this->project->dir, $plugin_svn_comp_dir, false, true ) ) {
			throw new Exception( 'Failed to create project SVN-comp directory.' );
		}

		if ( ! U\Fs::delete( U\Dir::join( $plugin_svn_comp_dir, '/vendor' ) ) ) {
			throw new Exception( 'Prior to running `composer update`, failed to delete project SVN-comp /vendor directory.' );
		}
		if ( ! U\Fs::delete( U\Dir::join( $plugin_svn_comp_dir, '/trunk/vendor' ) ) ) {
			throw new Exception( 'Prior to running `composer update`, failed to delete project SVN-comp /trunk/vendor directory.' );
		}

		if ( 0 !== U\CLI::run( [ 'composer', 'update', '--no-dev', '--optimize-autoloader' ], $plugin_svn_comp_dir, false ) ) {
			throw new Exception( 'Failed to run `composer update --no-dev --optimize-autoloader` in SVN-comp directory.' );
		}
		if ( 0 !== U\CLI::run( [ 'composer', 'update', '--no-dev', '--optimize-autoloader' ], U\Dir::join( $plugin_svn_comp_dir, '/trunk' ), false ) ) {
			throw new Exception( 'Failed to run `composer update --no-dev --optimize-autoloader` in SVN-comp /trunk directory.' );
		}

		if ( ! U\Dir::prune( $plugin_svn_comp_dir, $plugin_svn_comp_dir_prune_config[ 'prune' ], $plugin_svn_comp_dir_prune_config[ 'prune_exceptions' ] ) ) {
			throw new Exception( 'Failed to prune project SVN-comp directory.' );
		}
		if ( ! U\Fs::copy( U\Dir::join( $plugin_svn_comp_dir, '/*' ), $plugin_svn_repo_dir ) ) {
			throw new Exception( 'Failed to copy contents of pruned SVN-comp directory into SVN directory.' );
		}

		if ( ! U\Fs::copy( U\Dir::join( $plugin_svn_comp_dir, '/trunk' ), U\Dir::join( $plugin_svn_comp_dir, '/tags/' . $plugin->headers->version ), false, true ) ) {
			throw new Exception( 'Failed to create tags/' . $plugin->headers->version . ' in project SVN-comp directory.' );
		}
		if ( ! U\Fs::copy( U\Dir::join( $plugin_svn_comp_dir, '/tags/' . $plugin->headers->version ), U\Dir::join( $plugin_svn_repo_dir, '/tags/' . $plugin->headers->version ) ) ) {
			throw new Exception( 'Failed to copy tags/' . $plugin->headers->version . ' in project SVN-comp directory into SVN directory.' );
		}
	}

	/**
	 * Maybe compile WordPress theme's SVN repo.
	 *
	 * @since 2021-12-15
	 *
	 * @throws Exception Whenever any failure occurs.
	 */
	protected function maybe_compile_wp_theme_svn_repo() : void {
		if ( ! $this->project->is_wp_theme() ) {
			return; // Not applicable.
		}
		$theme                           = $this->project->wp_theme_data();
		$theme_svn_comp_dir_prune_config = $this->project->distro_prune_config();
		$theme_svn_comp_dir              = U\Dir::join( $this->project->dir, '/._x/svn-comp' );
		$theme_svn_repo_dir              = U\Dir::join( $this->project->dir, '/._x/svn-repo' );

		if ( ! U\Fs::copy( $this->project->dir, $theme_svn_comp_dir, false, true ) ) {
			throw new Exception( 'Failed to create project SVN-comp directory.' );
		}

		if ( ! U\Fs::delete( U\Dir::join( $theme_svn_comp_dir, '/vendor' ) ) ) {
			throw new Exception( 'Prior to running `composer update`, failed to delete project SVN-comp /vendor directory.' );
		}
		if ( ! U\Fs::delete( U\Dir::join( $theme_svn_comp_dir, '/trunk/vendor' ) ) ) {
			throw new Exception( 'Prior to running `composer update`, failed to delete project SVN-comp /trunk/vendor directory.' );
		}

		if ( 0 !== U\CLI::run( [ 'composer', 'update', '--no-dev', '--optimize-autoloader' ], $theme_svn_comp_dir, false ) ) {
			throw new Exception( 'Failed to run `composer update --no-dev --optimize-autoloader` in SVN-comp directory.' );
		}
		if ( 0 !== U\CLI::run( [ 'composer', 'update', '--no-dev', '--optimize-autoloader' ], U\Dir::join( $theme_svn_comp_dir, '/trunk' ), false ) ) {
			throw new Exception( 'Failed to run `composer update --no-dev --optimize-autoloader` in SVN-comp /trunk directory.' );
		}

		if ( ! U\Dir::prune( $theme_svn_comp_dir, $theme_svn_comp_dir_prune_config[ 'prune' ], $theme_svn_comp_dir_prune_config[ 'prune_exceptions' ] ) ) {
			throw new Exception( 'Failed to prune project SVN-comp directory.' );
		}
		if ( ! U\Fs::copy( U\Dir::join( $theme_svn_comp_dir, '/*' ), $theme_svn_repo_dir ) ) {
			throw new Exception( 'Failed to copy contents of pruned SVN-comp directory into SVN directory.' );
		}

		if ( ! U\Fs::copy( U\Dir::join( $theme_svn_comp_dir, '/trunk' ), U\Dir::join( $theme_svn_comp_dir, '/tags/' . $theme->headers->version ), false, true ) ) {
			throw new Exception( 'Failed to create tags/' . $theme->headers->version . ' in project SVN-comp directory.' );
		}
		if ( ! U\Fs::copy( U\Dir::join( $theme_svn_comp_dir, '/tags/' . $theme->headers->version ), U\Dir::join( $theme_svn_repo_dir, '/tags/' . $theme->headers->version ) ) ) {
			throw new Exception( 'Failed to copy tags/' . $theme->headers->version . ' in project SVN-comp directory into SVN directory.' );
		}
	}

	/**
	 * Maybe compile WordPress plugin zip.
	 *
	 * @since 2021-12-15
	 *
	 * @throws Exception Whenever any failure occurs.
	 */
	protected function maybe_compile_wp_plugin_zip() : void {
		if ( ! $this->project->is_wp_plugin() ) {
			return; // Not applicable.
		}
		$plugin                  = $this->project->wp_plugin_data();
		$plugin_svn_repo_tag_dir = U\Dir::join( $this->project->dir, '/._x/svn-repo/tags/' . $plugin->headers->version );

		if ( ! is_dir( $plugin_svn_repo_tag_dir ) ) {
			throw new Exception( 'Failed to zip plugin SVN-repo directory. Missing `' . $plugin_svn_repo_tag_dir . '`.' );
		}
		$plugin_zip_basename = $plugin->slug . '-v' . $plugin->headers->version . '.zip';
		$plugin_zip_path     = U\Dir::join( $this->project->dir, '/._x/zips/' . $plugin_zip_basename );

		if ( ! U\Fs::zip( $plugin_svn_repo_tag_dir . '->' . $plugin->slug, $plugin_zip_path ) ) {
			throw new Exception( 'Failed to zip plugin SVN-repo directory.' );
		}
	}

	/**
	 * Maybe compile WordPress theme zip.
	 *
	 * @since 2021-12-15
	 *
	 * @throws Exception Whenever any failure occurs.
	 */
	protected function maybe_compile_wp_theme_zip() : void {
		if ( ! $this->project->is_wp_theme() ) {
			return; // Not applicable.
		}
		$theme                  = $this->project->wp_theme_data();
		$theme_svn_repo_tag_dir = U\Dir::join( $this->project->dir, '/._x/svn-repo/tags/' . $theme->headers->version );

		if ( ! is_dir( $theme_svn_repo_tag_dir ) ) {
			throw new Exception( 'Failed to zip theme SVN-repo directory. Missing `' . $theme_svn_repo_tag_dir . '`.' );
		}
		$theme_zip_basename = $theme->slug . '-v' . $theme->headers->version . '.zip';
		$theme_zip_path     = U\Dir::join( $this->project->dir, '/._x/zips/' . $theme_zip_basename );

		if ( ! U\Fs::zip( $theme_svn_repo_tag_dir . '->' . $theme->slug, $theme_zip_path ) ) {
			throw new Exception( 'Failed to zip theme SVN-repo directory.' );
		}
	}

	/**
	 * Maybe upload a plugin zip to AWS S3.
	 *
	 * @since 2021-12-15
	 *
	 * @throws Exception On any failure.
	 * @throws \Throwable On some failures.
	 */
	protected function maybe_s3_upload_wp_plugin_zip() : void {
		if ( ! $this->project->is_wp_plugin() ) {
			return; // Not applicable.
		}
		$plugin              = $this->project->wp_plugin_data();
		$plugin_zip_basename = $plugin->slug . '-v' . $plugin->headers->version . '.zip';
		$plugin_zip_path     = U\Dir::join( $this->project->dir, '/._x/zips/' . $plugin_zip_basename );

		if ( ! is_file( $plugin_zip_path ) ) {
			throw new Exception( 'Missing `' . $plugin_zip_path . '`.' );
		}
		$plugin_s3_zip_hash           = $this->project->s3_hash_hmac_sha256( $plugin->unbranded_slug . $plugin->headers->version );
		$plugin_s3_zip_file_subpath   = 'cdn/product/' . $plugin->unbranded_slug . '/zips/' . $plugin_s3_zip_hash . '/' . $plugin_zip_basename;
		$plugin_s3_index_file_subpath = 'cdn/product/' . $plugin->unbranded_slug . '/data/index.json';

		$s3 = new S3Client( $this->project->s3_bucket_config() );

		// Get index w/ tagged versions.

		try {
			$_s3r            = $s3->getObject( [
				'Bucket' => $this->project->s3_bucket(),
				'Key'    => $plugin_s3_index_file_subpath,
			] );
			$plugin_s3_index = U\Str::json_decode( (string) $_s3r->get( 'Body' ) );

			if ( ! is_object( $plugin_s3_index ) || ! isset( $plugin_s3_index->versions->tags, $plugin_s3_index->versions->stable_tag ) ) {
				throw new Exception( 'Unable to retrieve valid JSON data from `' . U\Dir::join( 's3://' . $this->project->s3_bucket(), '/' . $plugin_s3_index_file_subpath ) . '`.' );
			}
			if ( ! is_object( $plugin_s3_index->versions->tags ) || ! is_string( $plugin_s3_index->versions->stable_tag ) ) {
				throw new Exception( 'Unable to retrieve valid JSON data from `' . U\Dir::join( 's3://' . $this->project->s3_bucket(), '/' . $plugin_s3_index_file_subpath ) . '`.' );
			}
		} catch ( \Throwable $throwable ) {
			if ( ! $throwable instanceof AwsException ) {
				throw $throwable; // Problem.
			}
			if ( 'NoSuchKey' !== $throwable->getAwsErrorCode() ) {
				throw $throwable; // Problem.
			}
			$plugin_s3_index = (object) [
				'versions' => (object) [
					'tags'       => (object) [],
					'stable_tag' => '',
				],
			]; // No index file yet, we'll create below.
		}

		// Upload zip file.
		// Throws exception on failure, which we intentionally do not catch.

		$s3->putObject( [
			'SourceFile' => $plugin_zip_path,
			'Bucket'     => $this->project->s3_bucket(),
			'Key'        => $plugin_s3_zip_file_subpath,
		] );

		// Update index w/ tagged versions.
		// Throws exception on failure, which we intentionally do not catch.

		$plugin_s3_index->versions->tags = (array) $plugin_s3_index->versions->tags;
		$plugin_s3_index->versions->tags = array_merge( $plugin_s3_index->versions->tags, [ $plugin->headers->version => time() ] );

		uksort( $plugin_s3_index->versions->tags, 'version_compare' ); // Example: <https://3v4l.org/QitGb>.
		$plugin_s3_index->versions->tags = array_reverse( $plugin_s3_index->versions->tags );

		$plugin_s3_index->versions->stable_tag = $plugin->headers->stable_tag;

		$s3->putObject( [
			'Body'   => U\Str::json_encode( $plugin_s3_index ),
			'Bucket' => $this->project->s3_bucket(),
			'Key'    => $plugin_s3_index_file_subpath,
		] );
	}

	/**
	 * Maybe upload a theme zip to AWS S3.
	 *
	 * @since 2021-12-15
	 *
	 * @throws Exception Whenever any failure occurs.
	 * @throws \Throwable On some failures.
	 */
	protected function maybe_s3_upload_wp_theme_zip() : void {
		if ( ! $this->project->is_wp_theme() ) {
			return; // Not applicable.
		}
		$theme              = $this->project->wp_theme_data();
		$theme_zip_basename = $theme->slug . '-v' . $theme->headers->version . '.zip';
		$theme_zip_path     = U\Dir::join( $this->project->dir, '/._x/zips/' . $theme_zip_basename );

		if ( ! is_file( $theme_zip_path ) ) {
			throw new Exception( 'Missing `' . $theme_zip_path . '`.' );
		}
		$theme_s3_zip_hash           = $this->project->s3_hash_hmac_sha256( $theme->unbranded_slug . $theme->headers->version );
		$theme_s3_zip_file_subpath   = 'cdn/product/' . $theme->unbranded_slug . '/zips/' . $theme_s3_zip_hash . '/' . $theme_zip_basename;
		$theme_s3_index_file_subpath = 'cdn/product/' . $theme->unbranded_slug . '/data/index.json';

		$s3 = new S3Client( $this->project->s3_bucket_config() );

		// Get index w/ tagged versions.

		try {
			$_s3r           = $s3->getObject( [
				'Bucket' => $this->project->s3_bucket(),
				'Key'    => $theme_s3_index_file_subpath,
			] );
			$theme_s3_index = U\Str::json_decode( (string) $_s3r->get( 'Body' ) );

			if ( ! is_object( $theme_s3_index ) || ! isset( $theme_s3_index->versions->tags, $theme_s3_index->versions->stable_tag ) ) {
				throw new Exception( 'Unable to retrieve valid JSON data from `' . U\Dir::join( 's3://' . $this->project->s3_bucket(), '/' . $theme_s3_index_file_subpath ) . '`.' );
			}
			if ( ! is_object( $theme_s3_index->versions->tags ) || ! is_string( $theme_s3_index->versions->stable_tag ) ) {
				throw new Exception( 'Unable to retrieve valid JSON data from `' . U\Dir::join( 's3://' . $this->project->s3_bucket(), '/' . $theme_s3_index_file_subpath ) . '`.' );
			}
		} catch ( \Throwable $throwable ) {
			if ( ! $throwable instanceof AwsException ) {
				throw $throwable; // Problem.
			}
			if ( 'NoSuchKey' !== $throwable->getAwsErrorCode() ) {
				throw $throwable; // Problem.
			}
			$theme_s3_index = (object) [
				'versions' => (object) [
					'tags'       => (object) [],
					'stable_tag' => '',
				],
			]; // No index file yet, we'll create below.
		}

		// Upload zip file.
		// Throws exception on failure, which we intentionally do not catch.

		$s3->putObject( [
			'SourceFile' => $theme_zip_path,
			'Bucket'     => $this->project->s3_bucket(),
			'Key'        => $theme_s3_zip_file_subpath,
		] );

		// Update index w/ tagged versions.
		// Throws exception on failure, which we intentionally do not catch.

		$theme_s3_index->versions->tags = (array) $theme_s3_index->versions->tags;
		$theme_s3_index->versions->tags = array_merge( $theme_s3_index->versions->tags, [ $theme->headers->version => time() ] );

		uksort( $theme_s3_index->versions->tags, 'version_compare' ); // Example: <https://3v4l.org/QitGb>.
		$theme_s3_index->versions->tags = array_reverse( $theme_s3_index->versions->tags );

		$theme_s3_index->versions->stable_tag = $theme->headers->stable_tag;

		$s3->putObject( [
			'Body'   => U\Str::json_encode( $theme_s3_index ),
			'Bucket' => $this->project->s3_bucket(),
			'Key'    => $theme_s3_index_file_subpath,
		] );
	}
}
