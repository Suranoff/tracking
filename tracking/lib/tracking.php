<?php
namespace Tracking;

use Bitrix\Main,
    Bitrix\Main\Localization\Loc;
Loc::loadMessages(__FILE__);

/**
 * Class TrackingTable
 *
 * Fields:
 * <ul>
 * <li> id int mandatory
 * <li> handbook_name string(100) mandatory
 * <li> event int mandatory
 * <li> data string mandatory
 * </ul>
 *
 * @package Tracking
 **/

class TrackingTable extends Main\Entity\DataManager
{
    /**
     * Returns DB table name for entity.
     *
     * @return string
     */
    public static function getTableName()
    {
        return 'tracking';
    }

    /**
     * Returns entity map definition.
     *
     * @return array
     */
    public static function getMap()
    {
        return array(
            'id' => array(
                'data_type' => 'integer',
                'primary' => true,
                'autocomplete' => true,
                'title' => Loc::getMessage('_ENTITY_ID_FIELD'),
            ),
            'handbook_name' => array(
                'data_type' => 'string',
                'required' => true,
                'validation' => array(__CLASS__, 'validateHandbookName'),
                'title' => Loc::getMessage('_ENTITY_HANDBOOK_NAME_FIELD'),
            ),
            'handbook_element' => array(
                'data_type' => 'integer',
                'required' => true,
                'title' => Loc::getMessage('_ENTITY_HANDBOOK_ELEMENT_FIELD'),
            ),
            'event' => array(
                'data_type' => 'integer',
                'required' => true,
                'title' => Loc::getMessage('_ENTITY_EVENT_FIELD'),
            ),
            'data' => array(
                'data_type' => 'text',
                'required' => true,
                'title' => Loc::getMessage('_ENTITY_DATA_FIELD'),
            ),
        );
    }

    /**
     * Returns validators for handbook_name field.
     *
     * @return array
     */
    public static function validateHandbookName()
    {
        return array(
            new Main\Entity\Validator\Length(null, 100),
        );
    }

    /**
     * Returns validators for handbook_element field.
     *
     * @return array
     */
    public static function validateHandbookElement()
    {
        return array(
            new Main\Entity\Validator\Length(null, 100),
        );
    }
}