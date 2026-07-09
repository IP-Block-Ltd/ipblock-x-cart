<?php
/**
 * IP Block Protection - X-Cart 5 module metadata.
 *
 * Module id: IpBlock\Protection (author "IpBlock", name "Protection").
 */

namespace XLite\Module\IpBlock\Protection;

/**
 * Module main class - registers the module with X-Cart and exposes a
 * settings form built from the config options declared in install.yaml.
 */
abstract class Main extends \XLite\Module\AModule
{
    /**
     * Author internal name (matches the Module/IpBlock/ directory).
     */
    public static function getAuthorName()
    {
        return 'IpBlock';
    }

    /**
     * Human-readable module name.
     */
    public static function getModuleName()
    {
        return 'IP Block Protection';
    }

    /**
     * Major version - the X-Cart core line this module targets.
     */
    public static function getMajorVersion()
    {
        return '5.6';
    }

    /**
     * Minor / build version of the module itself.
     */
    public static function getMinorVersion()
    {
        return '0';
    }

    public static function getBuildVersion()
    {
        return '0';
    }

    /**
     * Description shown in the admin Module list.
     */
    public static function getDescription()
    {
        return 'Screens storefront visitors against the ip-block.com service and blocks flagged IP addresses.';
    }

    /**
     * Show the "Settings" link for this module in the admin back office.
     */
    public static function showSettingsForm()
    {
        return true;
    }
}
