<?php

namespace Grav\Plugin;
use Grav\Common\Grav;

class Staticmath
{
	public function parseLatex(string $tex) {
		$staticmath_server = Grav::instance()['config']->get('plugins.staticmath.server');
		$postfield = "data=" . $tex;
		$ch = curl_init($staticmath_server);
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
		curl_setopt($ch, CURLOPT_POSTFIELDS, $postfield);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Length: ' . strlen($postfield)
        ]);
		$result = curl_exec($ch);
		if (!$result) {
			return "<pre>" . $tex . "</pre>";
		}
		return $result;
	}

	public function parsePage($content)
    {
		$delimiters = Grav::instance()['config']->get('plugins.staticmath.delimiters', []);
		// First, do the blocks
		foreach ($delimiters['block'] as $begin => $end) {
			$regex = '/' . preg_quote($begin) . '(.*?)' . preg_quote($end) . '/sm';
			// As long as we can still match a block, keep on going
			while (preg_match($regex, $content, $matches)) {
				// $matches is an array of matches, but the second element contains the "inner" portion
				// Parse that "inner" portion
				$parsed = $this->parseLatex($matches[1]);
				// Now replace that extracted bit with the parsed data
				$content = preg_replace($regex, $parsed, $content, 1);
			}
		}

		foreach ($delimiters['inline'] as $begin => $end) {
			$regex = '/' . preg_quote($begin) . '(.*?)' . preg_quote($end) . '/';
			// As long as we can still match an inline section, keep on going
			while(preg_match($regex, $content, $matches)) {
				// $matches is an array of matches, but the second element contains the "inner" portion
				// Parse that "inner" portion
				$parsed = $this->parseLatex($matches[1]);
				// Now replace that extracted bit with the parsed data
				$content = preg_replace($regex, $parsed, $content, 1);
			}
		}

		return $content;

    }
}

?>
