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
        $this->set('path', null);
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

        if (!!is_string($data['video']) && !empty($entry_id)) {
            $row = Symphony::Database()
                        ->select()
                        ->from('sym_entries_data_' . $this->get('id'))
                        ->where(['entry_id' => $entry_id])
                        ->execute()
                        ->next();

            unset($row['id']);
            unset($row['entry_id']);

            return $row;
        } else {
            $abs_path = DOCROOT . '/' . trim($this->get('path'), '/');
            $filename = uniqid() . '-' . $data['video']['name'];
            $success = General::uploadFile($abs_path, $filename, $data['video']['tmp_name']);

            if (!$success) {
                $status = self::__ERROR__;
            }
        }

        $row = array(
            'video_url' => '',
            'meta' => '{}',
            'uploaded' => 'no',
            'processed' => 'no',
            'file' => $filename,
            'size' => $data['video']['size'],
            'mimetype' => $data['video']['type'],
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
        if (!is_array($data) || empty($data) || (empty($data['video_url']) || empty($data['meta']))) {
            return;
        }

        // todo append the original too

        $metas = json_decode($data['meta'], JSON_FORCE_OBJECT);

        if ($metas['readyToStream'] !== true || $metas['status']['state'] !== 'ready') {
            $config = Symphony::Configuration()->get('cloudflare_videos');
            $ch = new Gateway();
            $ch->init($data['video_url']);
            $ch->setopt('HTTPHEADER', array(
                'X-Auth-Key: ' . $config['api-key'],
                'X-Auth-Email: ' . $config['email'],
            ));
            $ch->setopt('CONTENTTYPE', 'application/json');
            $result = json_decode($ch->exec(), JSON_FORCE_OBJECT);

            if ($result['success'] === true) {
                $metas = $result['result'];
            }
        }

        $root = new XMLElement('cloudflare');

        foreach ($metas as $key => $value) {
            $root->appendChild(new XMLElement($key, $value));
        }

        $root->appendChild(new XMLElement('api-url', $data['video_url']));
        $wrapper->appendChild(new XMLElement($this->get('element_name'), $root));
    }

    public function setFromPOST(Array $settings = array())
    {
        parent::setFromPOST($settings);

        $new_settings = array();
        $new_settings['path'] = $settings['path'];

        $this->setArray($new_settings);
    }

    public function commit()
    {
        // if the default implementation works...
        if(!parent::commit()) return false;

        $id = $this->get('id');

        // exit if there is no id
        if($id == false) return false;

        // declare an array contains the field's settings
        $settings = array(
            'path' => $this->get('path')
        );

        return FieldManager::saveSettings($id, $settings);
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

        $ignore = array(
            '/workspace/events',
            '/workspace/data-sources',
            '/workspace/text-formatters',
            '/workspace/pages',
            '/workspace/utilities'
        );

        $directories = General::listDirStructure(WORKSPACE, null, true, DOCROOT, $ignore);

        $label = Widget::Label(__('Destination Directory'));

        $options = array();
        $options[] = array('/workspace', false, '/workspace');

        if (!empty($directories) && is_array($directories)) {
            foreach ($directories as $d) {
                $d = '/' . trim($d, '/');

                if (!in_array($d, $ignore)) {
                    $options[] = array($d, ($this->get('path') == $d), $d);
                }
            }
        }

        $label->appendChild(Widget::Select('fields[' . $this->get('sortorder') . '][path]', $options));
        $wrapper->appendChild($label);


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
                'file' => null,
                'video_url' => null,
                'meta' => null,
            ];
        }

        $label = Widget::Label($this->get('label'), null, 'file');
        $isRequired = $this->isRequired();
        $meta = @json_decode($data['meta'], JSON_FORCE_OBJECT);
        $hasVideo = !empty($data['video_url']) && $meta;

        if(!$isRequired) {
            $label->appendChild(new XMLElement('i', __('Optional')));
        }

        // Load assets
        extension_cloudflare_videos::loadAssetsOnce();

        $removeCtn = new XMLElement('div', null, array('class' => 'cloudflare-remove-ctn js-cloudflare-video-remove-ctn'));
        $removeCtn->appendChild(new XMLElement('em', __('Remove Video'), array('class' => 'js-cloudflare-video-remove')));

        $uploadCtn = new XMLElement('div', null, array('cloudflare-upload-ctn js-cloudflare-video-upload-ctn'));
        $input = Widget::Input('fields' . $fieldnamePrefix . '[' . $this->get('element_name') . '][video]' . $fieldnamePostfix);
        $input->setAttribute('accept', 'video/mp4, video/m4v, video/webm, video/mov, video/quicktime');
        $input->setAttribute('type', !empty($data['file']) ? 'hidden' : 'file');
        $input->setAttribute('value', !empty($data['file']) ? $data['file'] : null);

        $uploadCtn->appendChild($input);

        $stateCtn = new XMLElement('div', null, array('class'=> 'cloudflare-state-ctn js-cloudflare-video-state-ctn'));

        if ($meta['readyToStream'] === true) {
            $playerElement = new XMLElement('stream');
            $playerElement->setAttributeArray([
                'src' => $meta['uid'],
                'controls' => 'controls'
            ]);
            $playerScript = new XMLElement('script');
            $playerScript->setAttributeArray([
                'data-cfasync' => 'false',
                'defer' => 'defer',
                'src' => 'https://embed.cloudflarestream.com/embed/r4xu.fla9.latest.js?video=' . $meta['uid']
            ]);
            $stateCtn->appendChild($playerElement);
            $stateCtn->appendChild($playerScript);
        } else if ($data['uploaded'] === 'yes') {
            $stateCtn->appendChild('<img src="' . $meta['thumbnail'] . '" /><p>' . __('The video is uploaded to Cloudflare and is processing.') . '</p>');
        } else {
            $stateCtn->appendChild('<p>' . __('The video will be uploaded to Cloudflare soon. You can save the entry.') . '</p>');
        }

        $ctn = new XMLElement('div', null, array('class' => 'cloudflare-ctn'));

        $ctn->appendChild($removeCtn);
        $ctn->appendChild($uploadCtn);
        $ctn->appendChild($stateCtn);
        $label->appendChild($ctn);

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
                'video_url' => [
                    'type' => 'varchar(512)',
                    'null' => true,
                ],
                'meta' => 'text',
                'uploaded' => [
                    'type' => 'enum',
                    'values' => ['yes', 'no'],
                    'default' => 'no',
                ],
                'processed' => [
                    'type' => 'enum',
                    'values' => ['yes', 'no'],
                    'default' => 'no',
                ],
                'file' => [
                    'type' => 'varchar(255)',
                    'null' => true,
                ],
                'size' => [
                    'type' => 'int(11)',
                    'null' => true,
                ],
                'mimetype' => [
                    'type' => 'varchar(100)',
                    'null' => true,
                ]
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
                'path' => [
                    'type' => 'varchar(255)',
                    'null' => true,
                ]
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
