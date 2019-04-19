<?php
/**
 * Copyright: Deux Huit Huit 2019
 * License: MIT, see the LICENSE file
 */

if (!defined("__IN_SYMPHONY__")) die("<h2>Error</h2><p>You cannot directly access this file</p>");

require_once(EXTENSIONS . '/cloudflare_videos/fields/field.cloudflare_video.php');

class extension_cloudflare_videos extends Extension
{
    /**
     * Name of the extension
     * @var string
     */
    const EXT_NAME = 'Field: Cloudflare Video';

    private static $assetsLoaded = false;

    /**
     * Loads the assets required for the publish view
     *
     * @return void
     */
    public static function loadAssetsOnce()
    {
        if (self::$assetsLoaded) {
            return;
        }
        if (class_exists('Administration', false) &&
            Administration::instance() instanceof Administration &&
            Administration::instance()->Page instanceof HTMLPage
        ) {
            $page = Administration::instance()->Page;

            $page->addScriptToHead('https://unpkg.com/tus-js-client@1.7.1/dist/tus.min.js');
            $page->addScriptToHead(URL . '/extensions/cloudflare_videos/assets/cloudflare_videos.publish.js');

            self::$assetsLoaded = true;
        }
    }

    /* ********* INSTALL/UPDATE/UNINSTALL ******* */

    /**
     * Creates the table needed for the settings of the field
     */
    public function install()
    {
        return FieldCloudflare_Video::createFieldTable();
    }

    /**
     * Creates the table needed for the settings of the field
     */
    public function update($previousVersion = false)
    {
        $ret = true;
        return $ret;
    }

    /**
     *
     * Drops the table needed for the settings of the field
     */
    public function uninstall()
    {
        return FieldCloudflare_Video::deleteFieldTable();
    }
}
