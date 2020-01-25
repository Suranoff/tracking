<?php
namespace Tracking;

use Bitrix\Main,
    Bitrix\Main\Localization\Loc;
Loc::loadMessages(__FILE__);

/**
 * Class LogTable
 *
 * Fields:
 * <ul>
 * <li> id int mandatory
 * <li> add int mandatory
 * <li> update int mandatory
 * <li> delete int mandatory
 * </ul>
 *
 * @package Tracking
 **/

class LogTable extends Main\Entity\DataManager
{
    /**
     * Returns DB table name for entity.
     *
     * @return string
     */
    public static function getTableName()
    {
        return 'log';
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
            'add' => array(
                'data_type' => 'integer',
                'required' => true,
                'title' => Loc::getMessage('_ENTITY_ADD_FIELD'),
            ),
            'update' => array(
                'data_type' => 'integer',
                'required' => true,
                'title' => Loc::getMessage('_ENTITY_UPDATE_FIELD'),
            ),
            'delete' => array(
                'data_type' => 'integer',
                'required' => true,
                'title' => Loc::getMessage('_ENTITY_DELETE_FIELD'),
            ),
            'errors' => array(
                'data_type' => 'text',
                'title' => Loc::getMessage('_ENTITY_ERRORS_FIELD'),
            ),
        );
    }
}