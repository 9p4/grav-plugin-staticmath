- [Grav StaticMath Plugin](https://git.sr.ht/~fd/grav-plugin-staticmath)
- [StaticMath Server](https://git.sr.ht/~fd/staticmath-server)
- [Issues](https://todo.sr.ht/~fd/grav-plugin-staticmath)
- [Mailing List](https://lists.sr.ht/~fd/grav-plugin-staticmath)

# Grav StaticMath Plugin

The **StaticMath** Plugin is an extension for [Grav CMS](https://github.com/getgrav/grav). This plugin converts LaTeX to static math (with some CSS) using server-side [KaTeX](https://katex.org).

## Installation

Installing the StaticMath plugin can be done in one of three ways: The GPM (Grav Package Manager) installation method lets you quickly install the plugin with a simple terminal command, the manual method lets you do so via a zip file, and the admin method lets you do so via the Admin Plugin.

### Server (Required)

Install the [StaticMath server](https://git.sr.ht/~fd/staticmath-server) first.

### GPM Installation (Preferred)

To install the plugin via the [GPM](https://learn.getgrav.org/cli-console/grav-cli-gpm), through your system's terminal (also called the command line), navigate to the root of your Grav-installation, and enter:

    bin/gpm install staticmath

This will install the staticmath plugin into your `/user/plugins`-directory within Grav. Its files can be found under `/your/site/grav/user/plugins/staticmath`.

### Manual Installation

To install the plugin manually, download the zip-version of this repository and unzip it under `/your/site/grav/user/plugins`. Then rename the folder to `staticmath`. You can find these files on [GitHub](https://github.com//grav-plugin-staticmath) or via [GetGrav.org](https://getgrav.org/downloads/plugins).

You should now have all the plugin files under

    /your/site/grav/user/plugins/staticmath
	
### Admin Plugin

If you use the Admin Plugin, you can install the plugin directly by browsing the `Plugins`-menu and clicking on the `Add` button.

## Configuration

Before configuring this plugin, you should copy the `user/plugins/staticmath/staticmath.yaml` to `user/config/plugins/staticmath.yaml` and only edit that copy.

Here is the default configuration and an explanation of available options:

```yaml
enabled: true
built_in_css: true # Uses built-in KaTeX CSS
active: false # Whether the plugin is active on a page
output: "htmlAndMathml" # Set output mode. Can be either "html", "htmlAndMathml", or "mathml"
server: "http://localhost:3000" # The location of the StaticMath server
```

Note that if you use the Admin Plugin, a file with your configuration named staticmath.yaml will be saved in the `user/config/plugins/`-folder once the configuration is saved in the Admin.

## Usage

Wherever you want LaTeX code in your server, use the delimiters set in the configuration, like so:

```markdown
[tex]
\text{This is a block of LaTeX code}
[/tex]

And [texi]\text{this}[/texi] is inline LaTeX code.
```

## Credits

Much thanks to [KaTeX](https://katex.org) for rendering the math, the [Grav MathJax Plugin](https://github.com/Sommerregen/grav-plugin-mathjax) for giving me a base to build off of, and the [Grav ZMarkdown Plugin](https://github.com/AmauryCarrade/grav-plugin-zmarkdown-engine) to give me pointers for how to do networking in PHP.
