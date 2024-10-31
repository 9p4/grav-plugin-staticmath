<?php
/**
 * Grav StaticMath plugin v2.0.1
 *
 * This plugin renders math server-side and displays it to the client with
 * Temml.
 *
 * Based on the code from the Grav MathJax plugin: https://github.com/sommerregen/grav-plugin-mathjax
 *
 * @version		2.0.1
 *
 * @link		<https://sr.ht/~fd/grav-plugin-staticmath>
 *
 * @author		Ersei Saggi <contact@ersei.net>
 * @copyright	2024, Ersei Saggi
 * @license		<http://opensource.org/licenses/MIT>		MIT
 */

namespace Grav\Plugin;

use Composer\Autoload\ClassLoader;
use Grav\Common\Grav;
use Grav\Common\Page\Page;
use Grav\Common\Plugin;
use RocketTheme\Toolbox\Event\Event;

/**
 * Class StaticmathPlugin
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
            'onGetPageBlueprints' => ['onGetPageBlueprints', 0],
            'onMarkdownInitialized' => ['onMarkdownInitialized', 0],
        ];
    }

    /**
     * Composer autoload
     */
    public function autoload(): ClassLoader
    {
        return require __DIR__.'/vendor/autoload.php';
    }

    /**
     * Register shortcodes
     */
    public function onShortcodeHandlers()
    {
        $this->grav['shortcode']->registerAllShortcodes(__DIR__.'/shortcodes');
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
            'onPageInitialized' => ['onPageInitialized', 0],
        ]);
    }

    public function onMarkdownInitialized(Event $event)
    {
        $markdown = $event['markdown'];

        $page = $this->grav['page'];
        $config = $this->mergeConfig($page);
        if (! ($config->get('enabled') && $config->get('active'))) {
            return;
        }
        $markdown->addBlockType('$', 'Staticmath', true, false);
        $markdown->addInlineType('$', 'Staticmath');

        $markdown->blockStaticmath = function ($Line) {
            if (preg_match('/^\$\$$/', $Line['text'], $matches)) {
                $Block = [
                    'element' => [
                        'name' => 'div',
                        'handler' => 'lines',
                        'text' => [],
                    ],
                ];

                return $Block;
            }
        };

        $markdown->blockStaticmathContinue = function ($Line, array $Block) {
            if (isset($Block['interrupted'])) {
                return;
            }
            if (! preg_match('/^\$\$$/', $Line['text'])) {
                $Block['element']['text'][] = $Line['text'];
            } else {
                $text = implode(
                    "\n",
                    $Block['element']['text']
                );

                $Block['element']['text'] = (array) $this->render($text);
            }

            return $Block;
        };

        $markdown->inlineStaticmath = function ($Line) {
            if (preg_match('/\$(.+?)\$/', $Line['text'], $matches)) {
                $Block = [
                    'extent' => strlen($matches[0]),
                    'element' => [
                        'name' => 'span',
                        'handler' => 'lines',
                        'text' => (array) $this->render($matches[1], true),
                    ],
                ];

                return $Block;
            }
        };
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
        if (! ($config->get('enabled') && $config->get('active'))) {
            return;
        }

        if ($this->config->get('plugins.staticmath.built_in_css')) {
            $this->grav['assets']->add('plugins://staticmath/assets/css/Temml-Latin-Modern.css');
        }
    }

    public function onGetPageBlueprints($event)
    {
        $types = $event->types;
        $types->scanBlueprints('plugin://staticmath/blueprints');
    }

    private function render($content, $inline = false)
    {
        $mode = $inline ? 'inline' : 'block';
        $staticmath_server = Grav::instance()['config']->get('plugins.staticmath.server');
        $postfield = 'mode='.urlencode($mode).'&data='.urlencode($content);
        $ch = curl_init($staticmath_server);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postfield);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Length: '.strlen($postfield),
        ]);
        $result = curl_exec($ch);
        if (! $result) {
            return '<pre>'.$content.'</pre>';
        }

        return $result;
    }
}
