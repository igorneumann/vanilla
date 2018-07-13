<?php
/**
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GPL-2.0
 */

namespace Vanilla\Legacy;

/**
 * An autoloader for class aliases.
 *
 * This allows us move around classes and make aliases without needing to load extra things.
 */
final class AliasAutoloader {
    const ALIASES = [
        // Core framework aliases.
        'Gdn_Pluggable' => \Vanilla\Pluggable::class,
        // Formatting Aliases
        'Gdn_Format' => \Vanilla\Formatting\FormatUtility::class,
        'BBCode' => \Vanilla\Formatting\BBCodeFormatter::class,
        'Emoji' => \Vanilla\Formatting\EmojiInterpreter::class,
        'VanillaHtmlFormatter' => \Vanilla\Formatting\HTMLFormatter::class,
        'MarkdownVanilla' => \Vanilla\Formatting\MarkdownFormatter::class,
        // Templating Aliases
        'Gdn_Smarty' => \Vanilla\Templating\SmartyRenderer::class,
        'SmartySecurityVanilla' => \Vanilla\Templating\SmartySecurityPolicy::class,
    ];

    /**
     * Autoloading function.
     *
     * If there a hardcoded alias, dynamically generate its alias (which will also autoload the original class).
     *
     * @param string $className The class name to try and load.
     */
    public static function autoload(string $className) {
        if (isset(self::ALIASES[$className])) {
            $orig = self::ALIASES[$className];
            trigger_error("$className is deprecated. Use $orig instead", E_USER_DEPRECATED);
            class_alias($orig, $className, true);
        }
    }
}
