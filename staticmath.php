<?php
/**
 * Grav StaticMath plugin v1.0.1
 *
 * This plugin renders math server-side and displays it to the client with
 * Katex.
 *
 * Based on the code from the Grav MathJax plugin: https://github.com/sommerregen/grav-plugin-mathjax
 *
 * @package		StaticMath
 * @version		1.0.1
 * @link		<https://sr.ht/~fd/grav-plugin-staticmath>
 * @author		Ersei Saggi <contact@ersei.net>
 * @copyright	2023, Ersei Saggi
 * @license		<http://opensource.org/licenses/MIT>		MIT
 */
namespace Grav\Plugin;

use Composer\Autoload\ClassLoader;
use Grav\Common\Plugin;
use RocketTheme\Toolbox\Event\Event;
use Grav\Common\Page\Page;
use Grav\Common\Data\Blueprints;

/**
 * Class StaticmathPlugin
 * @package Grav\Plugin
 */
class StaticmathPlugin extends Plugin
{

	/**
	 * Instance of StaticMath class
	 *
	 * @var Grav\Plugin\StaticMath
	 */
	protected $staticmath;

	/**
	 * @return array
	 *
	 * The getSubscribedEvents() gives the core a list of events
	 *	 that the plugin wants to listen to. The key of each
	 *	 array section is the event that the plugin listens to
	 *	 and the value (in the form of an array) contains the
	 *	 callable (or function) as well as the priority. The
	 *	 higher the number the higher the priority.
	 */
	public static function getSubscribedEvents(): array
	{
		return [
            'onPluginsInitialized' => ['onPluginsInitialized', 0],
            'onGetPageBlueprints' => ['onGetPageBlueprints', 0]
        ];
	}

	/**
	 * Composer autoload
	 *
	 * @return ClassLoader
	 */
	public function autoload(): ClassLoader
	{
		return require __DIR__ . '/vendor/autoload.php';
	}

	/**
     * Register shortcodes
     */
    public function onShortcodeHandlers()
    {
        $this->grav['shortcode']->registerAllShortcodes(__DIR__ . '/shortcodes');
    }

	/**
	 * Initialize the plugin
	 */
	public function onPluginsInitialized(): void
	{
		// Don't proceed if we are in the admin plugin
		if ($this->isAdmin()) {
			$this->active = false;
			return;
		}

        $this->enable([
            'onShortcodeHandlers' => ['onShortcodeHandlers', 0],
            'onPageInitialized' => ['onPageInitialized', 0]
        ]);
	}

	/**
	 * Initialize Twig configuration and filters.
	 */
	public function onPageInitialized()
	{
		/** @var Page $page */
		$page = $this->grav['page'];

		// Skip if active is set to false
		$config = $this->mergeConfig($page);
		if (!($config->get('enabled') && $config->get('active'))) {
			return;
		}

		if ($this->config->get('plugins.staticmath.built_in_css')) {
			$this->grav['assets']->add('plugins://staticmath/assets/css/katex.min.css');
		}
	}
}
