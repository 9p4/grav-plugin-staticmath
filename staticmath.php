<?php
namespace Grav\Plugin;

use Composer\Autoload\ClassLoader;
use Grav\Common\Plugin;

/**
 * Class StaticmathPlugin
 * @package Grav\Plugin
 */
class StaticmathPlugin extends Plugin
{
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
            'onPluginsInitialized' => [
                // Uncomment following line when plugin requires Grav < 1.7
                // ['autoload', 100000],
                ['onPluginsInitialized', 0]
            ]
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
            return;
        }

        // Enable the main events we are interested in
        $this->enable([
            // Put your main events here
			'onMarkdownInitialized' => ['onMarkdownInitialized', 0],
        ]);
	}

	public function onPageContentProcessed(Event $e) {
		 /** @var Page $page */
        $page = $e['page'];
        $config = $this->mergeConfig($page);

        $this->active = $config->get('active', true);

        // If the plugin is not active (either global or on page), exit.
        if (!$this->active) return;

        // We now check if we should render the content using ZMD.
        $header = $page->header();
        $should_process_zmarkdown = isset($header->process) && isset($header->process['zmarkdown']) ? (bool) $header->process['zmarkdown'] : null;

	}

	public function onMarkdownInitialized(Event $e) {

	}
}
