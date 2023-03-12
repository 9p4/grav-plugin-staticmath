<?php
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

	protected $staticmath;
    /**
     * @return array
     *
     * The getSubscribedEvents() gives the core a list of events
     *     that the plugin wants to listen to. The key of each
     *     array section is the event that the plugin listens to
     *     and the value (in the form of an array) contains the
     *     callable (or function) as well as the priority. The
     *     higher the number the higher the priority.
     */
    public static function getSubscribedEvents(): array
    {
        return [
            'onPluginsInitialized' => ['onPluginsInitialized', 0]
            
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
     * Initialize the plugin
     */
    public function onPluginsInitialized(): void
    {
        // Don't proceed if we are in the admin plugin
        if ($this->isAdmin()) {
			$this->active = false;
            $this->enable([
                'onBlueprintCreated' => ['onBlueprintCreated', 0]
            ]);
            return;
        }

		require_once(__DIR__ . '/classes/Staticmath.php');
		$this->staticmath = new Staticmath();

        // Enable the main events we are interested in
        $this->enable([
            // Put your main events here
			'onShortcodesInitialized' => ['onShortcodesInitialized', 0],
			'onMarkdownInitialized' => ['onMarkdownInitialized', 0],
			'onTwigSiteVariables' => ['onTwigSiteVariables', 0]
        ]);
	}

    /**
     *
     * @param Event $event
     */
    public function onBlueprintCreated(Event $event)
    {
        /** @var Blueprints $blueprint */
        $blueprint = $event['blueprint'];

        if ($blueprint->get('form/fields/tabs')) {
            $blueprints = new Blueprints(__DIR__ . '/blueprints');
            $extends = $blueprints->get($this->name);
            $blueprint->extend($extends, true);
        }
    }

	public function onMarkdownInitialized(Event $event) {
        /** @var Page $page */
        $page = $this->grav['page'];

        // Skip if active is set to false
        $config = $this->mergeConfig($page);
        if (!($config->get('enabled') && $config->get('active'))) {
            return;
        }
		$markdown = $event['markdown'];
		$this->staticmath->setupMarkdown($markdown);
	}

	/**
     */
    public function onTwigSiteVariables()
    {
        /** @var Page $page */
        $page = $this->grav['page'];

        // Skip if active is set to false
        $config = $this->mergeConfig($page);
        if (!($config->get('enabled') && $config->get('active'))) {
            return;
        }

        $this->staticmath->enabled(true);

        if ($this->config->get('plugins.staticmath.built_in_css')) {
            $this->grav['assets']->add('plugins://staticmath/assets/css/katex.min.css');
        }
    }
}
