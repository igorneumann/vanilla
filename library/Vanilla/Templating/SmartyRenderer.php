<?php
/**
 * Smart abstraction layer.
 *
 * @author Mark O'Sullivan <markm@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 * @package Core
 * @since 2.0
 */

namespace Vanilla\Templating;
use Smarty as BaseSmarty;
use Gdn_Controller;
use Gdn_Theme;
use Gdn_DataSet;
use Gdn_Upload;
use UserModel;
use Locale;
use Vanilla\Formatting\FormatUtility;

/**
 * Vanilla implementation of Smarty templating engine.
 */
class SmartyRenderer {

    /** @var \Gdn_Session */
    private $session;

    /** @var \Gdn_Locale */
    private $locale;

    /** @var \Gdn_Request */
    private $request;

    /** @var \Gdn_PluginManager */
    private $pluginManager;

    /** @var BaseSmarty The smarty object used for the template. */
    private $smartyInstance = null;

    /**
     * SmartyRenderer constructor.
     */
    public function __construct(\Gdn_Session $session, \Gdn_Locale $locale, \Gdn_Request $request, \Gdn_PluginManager $pluginManager) {
        $this->session = $session;
        $this->locale = $locale;
        $this->request = $request;
        $this->pluginManager = $pluginManager;
    }

    /**
     * Initialize the SmartyRenderer instance.
     *
     * @param string $path The path to the view's file.
     * @param Gdn_Controller $controller The controller that is rendering the view.
     *
     * @throws \SmartyException If there is an error configuring the security policy.
     */
    public function init($path, $controller) {
        $smarty = $this->smarty();

        // Get a friendly name for the controller.
        $controllerName = get_class($controller);
        if (stringEndsWith($controllerName, 'Controller', true)) {
            $controllerName = substr($controllerName, 0, -10);
        }

        // Get an ID for the body.
        $methodName = FormatUtility::alphaNumeric($controller->RequestMethod);
        $bodyIdentifier = strtolower($controller->ApplicationFolder.'_'.$controllerName.'_'.$methodName);

        // Assign some information about the user.
        $localeData = $this->getLocaleData();
        $controllerData = $this->getControllerData($controller);

        // We are falling back to false for when no user was found for compatibility with existing themes.
        $userData = $this->getUserData();
        $userData = count($this->getUserData()) === 0 ? false : $userData;

        // 2016-07-07 Linc: Request used to return blank for homepage.
        // Now it returns defaultcontroller. This restores BC behavior.
        $isHomepage = val('isHomepage', $controllerData);
        $path = ($isHomepage) ? "" : $this->request->path();

        // Push the data into Smarty.
        $smarty->assign('CurrentLocale', $localeData);
        $smarty->assign('Assets', (array)$controller->Assets);
        $smarty->assign('Path', $path);
        $smarty->assign('Homepage', $isHomepage); // true/false
        $smarty->assign('User', $userData);
        $smarty->assign('BodyID', htmlspecialchars($bodyIdentifier));

        // Assign the controller data last so the controllers override any default data.
        $smarty->assign($controllerData);

        $security = new SmartySecurityPolicy($smarty);
        $smarty->enableSecurity($security);
    }

    /**
     * Render the given view.
     *
     * @param string $path The path to the view's file.
     * @param Gdn_Controller $controller The controller that is rendering the view.
     *
     * @throws \SmartyException If there is an error in the rendering.
     */
    public function render($path, $controller) {
        $smarty = $this->smarty();
        $this->init($path, $controller);
        $compileID = $smarty->compile_id;
        if (defined('CLIENT_NAME')) {
            $compileID = CLIENT_NAME;
        }

        $smarty->setTemplateDir(dirname($path));
        $smarty->display($path, null, $compileID);
    }

    /**
     * Get the static
     *
     * @return BaseSmarty The smarty object used for rendering.
     *
     * @throws
     */
    public function smarty() {
        if (is_null($this->smartyInstance)) {
            $smarty = new \SmartyBC();

            $smarty->setCacheDir(PATH_CACHE.'/SmartyRenderer/cache');
            $smarty->setCompileDir(PATH_CACHE.'/SmartyRenderer/compile');
            $smarty->addPluginsDir(PATH_LIBRARY.'/SmartyPlugins');

            // We have to use the plugin manager directory instead of Pluggable->fireEvent() because we don't fire
            // We aren't extending the Smarty instance itself. We also pass it as an Arg for compatibility reasons.
            $this->pluginManager->callEventHandlers($smarty, 'Gdn_Smarty', 'Init');
            $this->smartyInstance = $smarty;
        }
        return $this->smartyInstance;
    }

    /**
     * See if the provided template causes any errors.
     *
     * @param string $path Path of template file to test.
     * @return boolean TRUE if template loads successfully.
     */
    public function testTemplate($path) {
        $smarty = $this->smarty();
        try {
            $this->init($path, Gdn::controller());
        } catch (\SmartyException $ex) {
            return false;
        }
        $compileID = $smarty->compile_id;
        if (defined('CLIENT_NAME')) {
            $compileID = CLIENT_NAME;
        }

        try {
            $result = $smarty->fetch($path, null, $compileID);
            $return = ($result == '' || strpos($result, '<title>Fatal Error</title>') > 0 || strpos($result, '<h1>Something has gone wrong.</h1>') > 0) ? false : true;
        } catch (\Exception $ex) {
            $return = false;
        }
        return $return;
    }

    /**
     * Get data about the current locale. Requires Intl PHP extension for detailed data.
     *
     * @return array
     */
    private function getLocaleData(): array {
        // Set the current locale for themes to take advantage of.
        $locale = $this->locale->Locale;
        $currentLocale = [
            'Key' => $locale,
            'Lang' => str_replace('_', '-', $this->locale->language(true)) // mirrors html5 lang attribute
        ];
        if (class_exists('Locale')) {
            $currentLocale['Language'] = Locale::getPrimaryLanguage($locale);
            $currentLocale['Region'] = Locale::getRegion($locale);
            $currentLocale['DisplayName'] = Locale::getDisplayName($locale, $locale);
            $currentLocale['DisplayLanguage'] = Locale::getDisplayLanguage($locale, $locale);
            $currentLocale['DisplayRegion'] = Locale::getDisplayRegion($locale, $locale);
        }

        return $currentLocale;
    }

    /**
     * Get the data from the controller's Data array.
     *
     * @param Gdn_Controller $controller The current controller.
     *
     * @return array
     */
    private function getControllerData($controller): array {
        // Make sure that any DataSets use arrays instead of objects.
        foreach ($controller->Data as $key => $value) {
            if ($value instanceof Gdn_DataSet) {
                $controller->Data[$key] = $value->resultArray();
            } elseif ($value instanceof \stdClass) {
                $controller->Data[$key] = (array)$value;
            }
        }

        $bodyClass = val('CssClass', $controller->Data, '');
        $sections = Gdn_Theme::section(null, 'get');
        if (is_array($sections)) {
            foreach ($sections as $section) {
                $bodyClass .= ' Section-'.$section;
            }
        }

        $controller->Data['BodyClass'] = $bodyClass;
        return $controller->Data;
    }

    /**
     * Get user data to inject into the template.
     *
     * @return array
     */
    private function getUserData(): array {
        if ($this->session->isValid()) {
            $user = [
                'Name' => htmlspecialchars($this->session->User->Name),
                'Photo' => '',
                'CountNotifications' => (int)val('CountNotifications', $this->session->User, 0),
                'CountUnreadConversations' => (int)val('CountUnreadConversations', $this->session->User, 0),
                'SignedIn' => true];

            $photo = $this->session->User->Photo;
            if ($photo) {
                if (!isUrl($photo)) {
                    $photo = Gdn_Upload::url(changeBasename($photo, 'n%s'));
                }
            } else {
                $photo = UserModel::getDefaultAvatarUrl($this->session->User);
            }
            $user['Photo'] = $photo;
        } else {
            $user = [];
        }

        return $user;
    }
}
