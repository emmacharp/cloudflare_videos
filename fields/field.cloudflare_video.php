<?php
/**
 * Copyright: Deux Huit Huit 2019
 * License: MIT, see the LICENSE file
 */

if (!defined('__IN_SYMPHONY__')) die('<h2>Symphony Error</h2><p>You cannot directly access this file</p>');

/**
 *
 * Field class that will represent relationships between entries
 * @author Deux Huit Huit
 *
 */
class FieldCloudflare_Video extends Field
{
    /**
     *
     * Name of the field table
     *  @var string
     */
    const FIELD_TBL_NAME = 'tbl_fields_cloudflare_video';

    /**
     *
     * Constructor for the Entry_Relationship Field object
     */
    public function __construct()
    {
        // call the parent constructor
        parent::__construct();
        // set the name of the field
        $this->_name = __('Cloudflare video');
        // permits to make it required
        $this->_required = true;
        // permits the make it show in the table columns
        $this->_showcolumn = true;
        // permits association
        $this->_showassociation = true;
        // set as not required by default
        $this->set('required', 'no');
    }

    public function isRequired()
    {
        return $this->get('required') === 'yes';
    }

    public function isSortable()
    {
        return false;
    }

    public function canFilter()
    {
        return false;
    }

    public function canPublishFilter()
    {
        return false;
    }

    public function canImport()
    {
        return false;
    }

    public function canPrePopulate()
    {
        return false;
    }

    public function mustBeUnique()
    {
        return false;
    }

    public function allowDatasourceOutputGrouping()
    {
        return false;
    }

    public function requiresSQLGrouping()
    {
        return false;
    }

    public function allowDatasourceParamOutput()
    {
        return false;
    }

    /* ********** INPUT AND FIELD *********** */


    /**
     *
     * Validates input
     * Called before <code>processRawFieldData</code>
     * @param $data
     * @param $message
     * @param $entry_id
     */
    public function checkPostFieldData($data, &$message, $entry_id=null)
    {
        $message = null;
        $required = $this->isRequired();

        if ($required && (!is_array($data) || count($data) == 0 || strlen($data['video_url']) < 1)) {
            $message = __("'%s' is a required field.", array($this->get('label')));
            return self::__MISSING_FIELDS__;
        }

        return self::__OK__;
    }

    /**
     *
     * Process data before saving into database.
     *
     * @param array $data
     * @param int $status
     * @param boolean $simulate
     * @param int $entry_id
     *
     * @return Array - data to be inserted into DB
     */
    public function processRawFieldData($data, &$status, &$message = null, $simulate = false, $entry_id = null)
    {
        $status = self::__OK__;

        if (!is_array($data) && !is_string($data)) {
            return null;
        }

        $row = array(
            'video_url' => $data['video_url'],
            'meta' => $data['meta'],
        );

        // return row
        return $row;
    }

    /* ******* DATA SOURCE ******* */

    /**
     * Appends data into the XML tree of a Data Source
     * @param $wrapper
     * @param $data
     */
    public function appendFormattedElement(XMLElement &$wrapper, $data, $encode = false, $mode = null, $entry_id = null)
    {
        if (!is_array($data) || empty($data)) {
            return;
        }

        $root = new XMLElement($this->get('element_name'));

        $wrapper->appendChild($root);
    }

    /* ********* UI *********** */

    /**
     *
     * Builds the UI for the field's settings when creating/editing a section
     * @param XMLElement $wrapper
     * @param array $errors
     */
    public function displaySettingsPanel(XMLElement &$wrapper, $errors=null)
    {
        /* first line, label and such */
        parent::displaySettingsPanel($wrapper, $errors);
        /* second line, footer */
        $this->appendStatusFooter($wrapper);
    }

    /**
     *
     * Builds the UI for the publish page
     * @param XMLElement $wrapper
     * @param mixed $data
     * @param mixed $flagWithError
     * @param string $fieldnamePrefix
     * @param string $fieldnamePostfix
     */
    public function displayPublishPanel(XMLElement &$wrapper, $data = null, $flagWithError = null, $fieldnamePrefix = null, $fieldnamePostfix = null, $entry_id = null)
    {
        if (!$data || !isset($data['video_url'])) {
            $data = [
                'video_url' => null,
                'meta' => null,
            ];
        }

        $label = Widget::Label($this->get('label'));
        $isRequired = $this->isRequired();
        $meta = @json_decode($data['meta']);
        $hasVideo = !empty($data['video_url']) && $meta;

        if(!$isRequired) {
            $label->appendChild(new XMLElement('i', __('Optional')));
        }

        // Load assets
        extension_cloudflare_videos::loadAssetsOnce();

        // Add settings
        $config = Symphony::Configuration()->get('cloudflare_videos');
        if (empty($config) || empty($config['api-key']) || empty($config['zone-id']) || empty($config['email'])) {
            $flagWithError = __('Cloudflare video configuration is invalid');
        } else {
            $wrapper->setAttribute('data-cf-api-key', $config['api-key']);
            $wrapper->setAttribute('data-cf-zone-id', $config['zone-id']);
            $wrapper->setAttribute('data-cf-email', $config['email']);
        }

        // Create UI
        $panel = new XMLElement('div');
        $uploadClass = $hasVideo ? 'irrelevant' : 'active';
        $panel->setAttribute('class', "js-cf-video-upload $uploadClass");
        // File input
        $input = Widget::Input('fields'.$fieldnamePrefix.'['.$this->get('element_name').'][video]'.$fieldnamePostfix);
        $input->setAttribute('type', 'file');
        $input->setAttribute('accept', 'video/mp4, video/m4v, video/webm, video/mov, video/quicktime');
        $input->setAttribute('class', 'js-cf-video-file');
        if ($hasVideo) {
            $input->setAttribute('disabled', 'disabled');
        }
        $panel->appendChild($input);
        // Video url hidden
        $input = Widget::Input('fields'.$fieldnamePrefix.'['.$this->get('element_name').'][video_url]'.$fieldnamePostfix);
        $input->setAttribute('type', 'hidden');
        $input->setAttribute('class', 'js-cf-video-url');
        if ($hasVideo) {
            $input->setAttribute('value', $data['video_url']);
        }
        $panel->appendChild($input);
        // Video meta hidden
        $input = Widget::Input('fields'.$fieldnamePrefix.'['.$this->get('element_name').'][meta]'.$fieldnamePostfix);
        $input->setAttribute('type', 'hidden');
        $input->setAttribute('class', 'js-cf-video-meta');
        if ($hasVideo) {
            $input->setAttribute('value', $data['meta']);
        }
        $panel->appendChild($input);
        // Add panel
        $label->appendChild($panel);
        // Progress panel
        $panel = new XMLElement('div');
        $panel->setAttribute('class', 'js-cf-video-progress');
        $panel->setAttribute('style', 'height: 10px; background-color: blue; width: 0%');
        // Add panel
        $label->appendChild($panel);
        // Player panel
        $panel = new XMLElement('div');
        $playerClass = !$hasVideo ? 'irrelevant' : 'active';
        $panel->setAttribute('class', "js-cf-video-player $playerClass");
        if ($hasVideo) {
            $playerElement = new XMLElement('stream');
            $playerElement->setAttributeArray([
                'src' => $meta->uid,
                'controls' => 'controls',
                'height' => '240px',
                'width' => '480px',
            ]);
            $panel->appendChild($playerElement);
            $playerScript = new XMLElement('script');
            $playerScript->setAttributeArray([
                'data-cfasync' => 'false',
                'defer' => 'defer',
                'src' => "https://embed.cloudflarestream.com/embed/r4xu.fla9.latest.js?video={$meta->uid}"
            ]);
            $panel->appendChild($playerScript);
        }
        // Add panel
        $label->appendChild($panel);

        // label error management
        if ($flagWithError != null) {
            $wrapper->appendChild(Widget::Error($label, $flagWithError));
        } else {
            $wrapper->appendChild($label);
        }
    }

    /**
     *
     * Return a plain text representation of the field's data
     * @param array $data
     * @param int $entry_id
     */
    public function prepareTextValue($data, $entry_id = null)
    {
        if ($entry_id == null || !is_array($data) || empty($data)) {
            return '';
        }
        return $data['video_url'];
    }

    /**
     *
     * Build the UI for the table view
     * @param Array $data
     * @param XMLElement $link
     * @param int $entry_id
     * @return string - the html of the link
     */
    public function prepareTableValue($data, XMLElement $link = null, $entry_id = null)
    {
        $video_url = $data['video_url'];
        $meta = $data['meta'];
        $textValue = $this->prepareTextValue($data, $entry_id);
        $value = null;

        // no url = early exit
        if (empty($video_url) || !($meta = @json_decode($meta))) {
            return null;
        }

        // no thumbnail
        if (empty($meta->thumbnail)) {
            // if not use the title or the url as value
            $value = $textValue;
        } else {
            // create a image
            //$img_path = URL . '/image/1/0/40/1/' . $meta->thumbnail;
            $img_path = $meta->thumbnail;

            $value = '<img src="' . $img_path .'" alt="" height="40" />';
        }

        // does this cell serve as a link ?
        if (!!$link) {
            // if so, set our html as the link's value
            $link->setValue($value);

        } else {
            // if not, wrap our html with a external link to the resource url
            $link = new XMLElement('a',
                $value,
                ['href' => $meta->preview, 'target' => '_blank', 'title' => $textValue]
            );
        }

        // returns the link's html code
        return $link->generate();
    }

    /* ********* SQL Data Definition ************* */

    /**
     *
     * Creates table needed for entries of individual fields
     */
    public function createTable()
    {
        $id = $this->get('id');

        return Symphony::Database()->create("tbl_entries_data_$id")
            ->fields([
                'id' => [
                    'type' => 'int(11)',
                    'auto' => true
                ],
                'entry_id' => 'int(11)',
                'video_url' => 'varchar(512)',
                'meta' => 'text',
            ])->keys([
                'id' => 'primary',
                'entry_id' => 'unique',
            ])->execute()->success();
    }

    /**
     * Creates the table needed for the settings of the field
     */
    public static function createFieldTable()
    {
        return Symphony::Database()->create(self::FIELD_TBL_NAME)
            ->ifNotExists()
            ->fields([
                'id' => [
                    'type' => 'int(11)',
                    'auto' => true
                ],
                'field_id' => 'int(11)',
            ])->keys([
                'id' => 'primary',
                'field_id' => 'unique',
            ])->execute()->success();
    }

    /**
     *
     * Drops the table needed for the settings of the field
     */
    public static function deleteFieldTable()
    {
        return Symphony::Database()->drop(self::FIELD_TBL_NAME)->ifExists()->execute()->success();
    }
}
