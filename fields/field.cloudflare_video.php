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
        $this->_name = __('Entry Relationship');
        // permits to make it required
        $this->_required = true;
        // permits the make it show in the table columns
        $this->_showcolumn = true;
        // permits association
        $this->_showassociation = true;
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

        if ($required && (!is_array($data) || count($data) == 0 || strlen($data['video']) < 1)) {
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
        $entries = null;

        if (!is_array($data) && !is_string($data)) {
            return null;
        }

        $row = array(
            'video' => $entries
        );

        // return row
        return $row;
    }

    /**
     * This function permits parsing different field settings values
     *
     * @param array $settings
     *	the data array to initialize if necessary.
        */
    public function setFromPOST(Array $settings = array())
    {
        // call the default behavior
        parent::setFromPOST($settings);

        // declare a new setting array
        $new_settings = array();

        // set new settings
        //$new_settings['show_header'] = $settings['show_header'] == 'yes' ? 'yes' : 'no';

        // save it into the array
        $this->setArray($new_settings);
    }

    /**
     *
     * Validates the field settings before saving it into the field's table
     */
    public function checkFields(Array &$errors, $checkForDuplicates = true)
    {
        $parent = parent::checkFields($errors, $checkForDuplicates);
        if ($parent != self::__OK__) {
            return $parent;
        }

        return (!empty($errors) ? self::__ERROR__ : self::__OK__);
    }

    /**
     *
     * Save field settings into the field's table
     */
    public function commit()
    {
        // if the default implementation works...
        if (!parent::commit()) {
            return false;
        }

        $id = $this->get('id');

        // exit if there is no id
        if($id == false) {
            return false;
        }

        // declare an array contains the field's settings
        $settings = array(

        );

        return FieldManager::saveSettings($id, $settings);
    }

    /**
     *
     * This function allows Fields to cleanup any additional things before it is removed
     * from the section.
     * @return boolean
     */
    public function tearDown()
    {
        return parent::tearDown();
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
        $label = Widget::Label($this->get('label'));

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
        return $data;
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
