<?php

namespace Vanilla\Templating;
use Smarty_Security as BaseSmartySecurityPolicy;
use Smarty;
/**
 * Vanilla implementation of SmartyRenderer security policy.
 */
class SmartySecurityPolicy extends BaseSmartySecurityPolicy {

    const ADDITIONAL_ALLOWED_FUNCTIONS = [
        'array', // Yes, SmartyRenderer really blocks this.
        'category',
        'categoryUrl',
        'checkPermission',
        'commentUrl',
        'discussionUrl',
        'inSection',
        'inCategory',
        'ismobile',
        'multiCheckPermission',
        'getValue',
        'setValue',
        'url',
        'useragenttype',
        'userUrl',
    ];

    private $normalizedAllowedFunctions = [];

    /**
     * @param Smarty $smarty
     */
    public function __construct($smarty)
    {
        parent::__construct($smarty);
        $this->normalizedAllowedFunctions = array_map(
            'strtolower',
            array_merge(
                $this->php_functions,
                self::ADDITIONAL_ALLOWED_FUNCTIONS
            )
        );

        $this->php_handling = Smarty::PHP_REMOVE;
        $this->allow_constants = false;
        $this->allow_super_globals = false;
        $this->streams = null;

        $this->php_modifiers = array_merge(
            $this->normalizedAllowedFunctions,
            ['sprintf']
        );
    }

    /**
     * Check if PHP function is trusted.
     *
     * @param string $function_name
     * @param object $compiler compiler object
     *
     * @return boolean true if function is trusted
     * @throws \SmartyCompilerException if php function is not trusted
     */
    public function isTrustedPhpFunction($function_name, $compiler) {
        $normalizedFunctionName = strtolower($function_name);
        if (in_array($normalizedFunctionName, $this->normalizedAllowedFunctions)) {
            return true;
        }

        $compiler->trigger_template_error("PHP function '{$function_name}' not allowed by security setting");
        return false; // should not, but who knows what happens to the compiler in the future?
    }
}
