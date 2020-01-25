<?php
define('TRACKING_EXPORT_FILE', $_SERVER['DOCUMENT_ROOT'].getLocalPath('modules/tracking/files').'/export.csv');
define('TRACKING_IMPORT_FILE', $_SERVER['DOCUMENT_ROOT'].getLocalPath('modules/tracking/files').'/import.csv');
define('TRACKING_CSV_DELIMETR', '^^^');
Bitrix\Main\Loader::registerAutoLoadClasses(
    "tracking",
    array(
        "Tracking\\TrackingTable" => "lib/tracking.php",
        "Tracking\\LogTable" => "lib/log.php",
        "Tracking\\Event" => "lib/event.php",
    )
);