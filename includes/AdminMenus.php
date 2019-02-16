<?php

namespace LicenseManager;

use \LicenseManager\Enums\LicenseStatusEnum;

defined('ABSPATH') || exit;

if (class_exists('AdminMenus', false)) {
    return new AdminMenus();
}

/**
 * Setup menus in WP admin.
 *
 * @version 1.0.0
 * @since 1.0.0
 */
class AdminMenus
{
    const LICENSES_PAGE       = 'license_manager';
    const ADD_IMPORT_PAGE     = 'license_manager_add_import';
    const GENERATORS_PAGE     = 'license_manager_generators';
    const ADD_GENERATOR_PAGE  = 'license_manager_generators_add';
    const EDIT_GENERATOR_PAGE = 'license_manager_generators_edit';
    const SETTINGS_PAGE       = 'license_manager_settings';

    /**
     * @var \LicenseManager\Crypto
     */
    private $crypto;

    /**
     * Class constructor.
     */
    public function __construct(
        \LicenseManager\Crypto $crypto
    ) {
        $this->crypto = $crypto;

        // Plugin pages.
        add_action('admin_menu', array($this, 'createPluginPages'), 9);
        add_action('admin_init', array($this, 'initSettingsAPI'));
    }

    public function createPluginPages()
    {
        // Licenses List Page
        add_menu_page(
            __('License Manager', 'lima'),
            __('License Manager', 'lima'),
            'manage_options',
            self::LICENSES_PAGE,
            array($this, 'licensesPage'),
            'dashicons-lock',
            10
        );
        add_submenu_page(
            self::LICENSES_PAGE,
            __('License Manager', 'lima'),
            __('Licenses', 'lima'),
            'manage_options',
            self::LICENSES_PAGE,
            array($this, 'licensesPage')
        );
        // Add/Import Page
        add_submenu_page(
            self::LICENSES_PAGE,
            __('License Manager - Import', 'lima'),
            __('Import', 'lima'),
            'manage_options',
            self::ADD_IMPORT_PAGE,
            array($this, 'licensesAddImportPage')
        );
        // Generators List Page
        add_submenu_page(
            self::LICENSES_PAGE,
            __('License Manager - Generators', 'lima'),
            __('Generators', 'lima'),
            'manage_options',
            self::GENERATORS_PAGE,
            array($this, 'generatorsPage')
        );
        // Add Generator Page
        add_submenu_page(
            self::LICENSES_PAGE,
            __('License Manager - Add New Generator', 'lima'),
            __('Add New Generator', 'lima'),
            'manage_options',
            self::ADD_GENERATOR_PAGE,
            array($this, 'generatorsAddPage')
        );
        // Edit Generator Page
        add_submenu_page(
            null,
            __('License Manager - Edit Generator', 'lima'),
            __('Edit Generator', 'lima'),
            'manage_options',
            self::EDIT_GENERATOR_PAGE,
            array($this, 'generatorsEditPage')
        );
        // Settings Page
        add_submenu_page(
            self::LICENSES_PAGE,
            __('License Manager - Settings', 'lima'),
            __('Settings', 'lima'),
            'manage_options',
            self::SETTINGS_PAGE,
            array($this, 'settingsPage')
        );
    }

    public function licensesPage()
    {
        $licenses = new \LicenseManager\Lists\LicensesList($this->crypto);

        add_screen_option(
            'per_page',
            array(
                'label'   => 'Licenses per page',
                'default' => 20,
                'option'  => 'licenses_per_page'
            )
        );

        include LM_TEMPLATES_DIR . 'licenses-page.php';
    }

    public function licensesAddImportPage()
    {
        $products = new \WP_Query(
            array(
                'post_type'      => 'product',
                'posts_per_page' => -1
            )
        );

        include LM_TEMPLATES_DIR . 'licenses-add-import-page.php';
    }

    public function settingsPage()
    {
        include LM_TEMPLATES_DIR . 'settings-page.php';
    }

    public function generatorsPage()
    {
        $generators = new \LicenseManager\Lists\GeneratorsList();

        add_screen_option(
            'per_page',
            array(
                'label'   => 'Generators per page',
                'default' => 5,
                'option'  => 'generators_per_page'
            )
        );

        include LM_TEMPLATES_DIR . 'generators-page.php';
    }

    public function generatorsAddPage()
    {
        include LM_TEMPLATES_DIR . 'generators-add-new.php';
    }

    public function generatorsEditPage()
    {
        if (!array_key_exists('edit', $_GET) && !array_key_exists('id', $_GET)) {
            return;
        }

        if (!$generator = Database::getGenerator(absint($_GET['id']))) {
           return;
        }

        $products = apply_filters('lima_get_assigned_products', array('generator_id' => absint($_GET['id'])));

        include LM_TEMPLATES_DIR . 'generators-edit.php';
    }

    public function initSettingsAPI()
    {
        new Settings();
    }

}