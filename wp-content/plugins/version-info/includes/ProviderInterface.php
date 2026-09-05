<?php // phpcs:disable WordPress.Files.FileName -- PSR-4 class file naming; renaming would break the autoloader.


namespace GauchoPlugins\VersionInfo;

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

/**
 * Contract implemented by every System Resources data provider.
 */
interface ProviderInterface {

	// phpcs:disable WordPress.NamingConventions.ValidFunctionName.MethodNameInvalid -- established camelCase provider API since 2.0; renaming would break every implementation.

	/**
	 * Returns the human-readable provider name.
	 */
	public function getName();

	/**
	 * Returns the provider key.
	 */
	public function getKey();

	/**
	 * Whether this provider can collect data on the current host.
	 */
	public function isAvailable();

	// phpcs:enable WordPress.NamingConventions.ValidFunctionName.MethodNameInvalid

	/**
	 * Collects the provider's data.
	 *
	 * @return array<string, mixed>
	 */
	public function collect();
}
