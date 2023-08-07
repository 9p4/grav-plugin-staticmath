<?php

namespace Grav\Plugin\Shortcodes;
use Grav\Common\Grav;

use DomainException;
use Exception;
use Thunder\Shortcode\Shortcode\ShortcodeInterface;

class StaticMathShortcode extends Shortcode
{
    public function init()
    {
        $rawHandlers = $this->shortcode->getRawHandlers();

        $rawHandlers->add('tex', function (ShortcodeInterface $sc) {
            $content = $sc->getContent();
			return $this->render($content);
        });
        $rawHandlers->add('texi', function (ShortcodeInterface $sc) {
            $content = $sc->getContent();
			return $this->render($content, true);
        });
    }

	private function render($content, $inline = false) {
		$mode = $inline ? "inline" : "block";
		$staticmath_server = Grav::instance()['config']->get('plugins.staticmath.server');
		$postfield = "mode=" . $mode . "&" . "data=" . urlencode($content);
		$ch = curl_init($staticmath_server);
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
		curl_setopt($ch, CURLOPT_POSTFIELDS, $postfield);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_HTTPHEADER, [
			'Content-Length: ' . strlen($postfield)
		]);
		$result = curl_exec($ch);
		if (!$result) {
			return "<pre>" . $content . "</pre>";
		}
		return $result;
	}
}
