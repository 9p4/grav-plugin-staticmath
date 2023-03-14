<?php

namespace Grav\Plugin;
use Grav\Common\Grav;

class Staticmath
{
	protected $hashes = [];
	protected $id;
	protected $markdown;

	/**
	 * Parse LaTeX code and convert it into KaTeX-rendered HTML
	 *
	 * @param string $tex The LaTeX code to be parsed
	 * @param string $mode The mode the server should respond in (inline or block)
	 * @return string The formatted HTML
	 */
	public function parseLatex(string $tex, string $mode) {
		$staticmath_server = Grav::instance()['config']->get('plugins.staticmath.server');
		$postfield = "mode=" . $mode . "&" . "data=" . urlencode($tex);
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

	/**
	 * Parse a raw page using Regex and black magic
	 * 
	 * @param string $content Page content
	 * @return string Page content with rendered math
	 */
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
				$parsed = $this->parseLatex($matches[1], "block");
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
				$parsed = $this->parseLatex($matches[1], "inline");
				// Now replace that extracted bit with the parsed data
				$content = preg_replace($regex, $parsed, $content, 1);
			}
		}

		return $content;

	}

	/**
	 * Parse TeX properly in Markdown
	 * 
	 * @param  object $markdown the markdown parser object
	 */
	public function setupMarkdown($markdown)
	{
		$this->markdown = $markdown;
		$delimiters = Grav::instance()['config']->get('plugins.staticmath.delimiters', []);
		foreach ($delimiters['block'] as $begin => $end) {
			$markdown->addBlockType($begin[0], 'Latex', true, true, 0);
		}

		$markdown->blockLatex = function($line, $block = null) use ($delimiters) {
			foreach ($delimiters['block'] as $begin => $end) {
				if (preg_match('/^(' . preg_quote($begin) . ')[ ]*$/', $line['text'], $matches)) {
					$block = [
						'begin' => $begin,
						'end' => $end,
						'element' => [
							'name' => 'div',
							'text' => [],
						]
					];

					return $block;
				}
			}
		};

		$markdown->blockLatexContinue = function($line, $block)
		{
			if (isset($block['complete'])) {
				return;
			}

			if (preg_match('/^'. preg_quote($block['end']) . '[ ]*$/', $line['text'])) {
				$block['complete'] = true;
				return $block;
			}

			$block['element']['text'][] = $line['body'];
			return $block;
		};

		$markdown->blockLatexComplete = function($block)
		{
			$text = $this->parseLatex(implode("\n", $block['element']['text']), "block");

			$this->id(time() . md5($text));
			$block['element']['text'] = $text;
			$block['markup'] = $this->hash($block['element'], $text);
			return $block;
		};

		/**
		 * Markdown inline
		 */

		// Add Latex inline environment to Markdown parser
		foreach ($delimiters['inline'] as $begin => $end) {
			$markdown->addInlineType($begin[0], 'Latex', 0);
		}

		$markdown->inlineLatex = function($excerpt) use ($delimiters)
		{
			foreach ($delimiters['inline'] as $begin => $end) {
				$begin = preg_quote($begin);
				$end = preg_quote($end);

				if (preg_match('/^(' . $begin . ')[ ]*(.+?)[ ]*(' . $end . ')/s', $excerpt['text'], $matches))
				{
					$text = $this->parseLatex(preg_replace("/[\pZ\pC]+/u", ' ', $matches[2]), 'inline');
					$block = [
						'extent' => strlen($matches[0]),
						'begin' => $matches[1],
						'end' => $matches[3],
						'element' => [
							'name' => 'span'
						],
						'text' => $text
					];

					$this->id(time() . md5($text));
					$block['element']['text'] = $text;
					$block['markup'] = $this->hash($block['element'], $text);
					return $block;
				}
			}
		};
	}

	/**
	 * Hash a given text.
	 *
	 * Called whenever a tag must be hashed when a function insert an
	 * atomic element in the text stream. Passing $text to through this
	 * function gives a unique text-token which will be reverted back when
	 * calling normalize.
	 *
	 * @param  string $text The text to be hashed
	 * @param  string $type The type (category) the text should be saved
	 *
	 * @return string	   Return a unique text-token which will be
	 *					  reverted back when calling normalize.
	 */
	protected function hash($block, $text = '')
	{
		static $counter = 0;

		// String that will replace the tag
		$key = implode('::', array('staticmath', $this->id(), ++$counter, 'M'));

		// Render markdown block
		$html = $text;
		$this->hashes[$key] = [
			'raw' => $text,
			'html' => $html,
		];

		return $key;
	}

	/**
	 * Gets and sets the identifier for hashing.
	 *
	 * @param  string $var the identifier
	 *
	 * @return string	  the identifier
	 */
	public function id($var = null)
	{
		if ($var !== null) {
			$this->id = $var;
		}
		return $this->id;
	}

	/**
	 * Normalize content i.e. replace all hashes with their corresponding
	 * math formula
	 *
	 * @param  string $content The content to be processed
	 *
	 * @return string		  The processed content
	 */
	public function normalize($content, $type = 'html')
	{
		$hashes = array_keys($this->hashes);
		$text = array_column(array_values($this->hashes), $type);

		// Fast replace hashes with their corresponding math formula
		$content = str_replace($hashes, $text, $content);

		// Return normalized content
		return $content;
	}
}

?>
