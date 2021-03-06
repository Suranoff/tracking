<?
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_before.php"); // первый общий пролог

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\HttpApplication;
use Bitrix\Main\Loader;
use Bitrix\Main\Config\Option;
use Bitrix\Highloadblock;
use Tracking\TrackingTable;
use Tracking\Event;
use Tracking\LogTable;

if (!$USER->IsAdmin()) {
    $APPLICATION->AuthForm(GetMessage("ACCESS_DENIED"));
}

Loc::loadMessages(__FILE__);
Loader::includeModule('tracking');
Loader::includeModule('iblock');
Loader::includeModule('highloadblock');
CJSCore::Init(array("jquery"));

// установим заголовок страницы
$APPLICATION->SetTitle(Loc::getMessage("ADMIN_IMPORT_TITLE"));

$request = HttpApplication::getInstance()->getContext()->getRequest();
$aTabs = array(
  array("DIV" => "edit1", "TAB" => Loc::getMessage('ADMIN_EXPORT'),),
  array("DIV" => "edit2", "TAB" => Loc::getMessage('ADMIN_IMPORT')),
);
$tabControl = new CAdminTabControl("tabControl", $aTabs);

// Обработка экспорта и импорта
if($request['ajax'] == 'y') {
    $actionType = $request['action_type'];

    if ($actionType == 'export') { // экспорт
        $page = $_SESSION['tracking_export']['page'] ?: 1;
        $nextPage = $page + 1;
        $handbookName = $request['handbook_export'];
        $rows = TrackingTable::getList([
            'filter' => ['handbook_name' => $handbookName],
            'order' => ['id' => 'asc'],
        ])->fetchAll();

        $changesByHandbooksChank = array_chunk($rows, Option::get('tracking', 'step'));
        if ($page == 1) {file_put_contents(TRACKING_EXPORT_FILE, '', LOCK_EX);}
        foreach ($changesByHandbooksChank[$page-1] as $changeItem) {
            $_SESSION['tracking_export']['logs'][$changeItem['event']][] = $changeItem;
            file_put_contents(TRACKING_EXPORT_FILE, implode(TRACKING_CSV_DELIMETR, $changeItem).PHP_EOL, FILE_APPEND | LOCK_EX);
        }

        $pages = count($changesByHandbooksChank);
        $_SESSION['tracking_export']['page'] = $nextPage;
        $_SESSION['tracking_export']['pages'] = $pages;

        if ($nextPage > $pages) {
            if (Option::get('tracking', 'logs')) {
                LogTable::add([
                    'add' => (int)count(array_unique($_SESSION['tracking_export']['logs'][Event::ADD])),
                    'delete' => (int)count(array_unique($_SESSION['tracking_export']['logs'][Event::DELETE])),
                    'update' => (int)count(array_unique($_SESSION['tracking_export']['logs'][Event::UPDATE])),
                    'errors' => Bitrix\Main\Web\Json::encode($_SESSION['tracking_export']['errors'])
                ]);
            }
            unset($_SESSION['tracking_export']);
            $complete = true;
        }

        $APPLICATION->RestartBuffer();
        CAdminMessage::ShowMessage(array(
            "MESSAGE"=> Loc::getMessage('ADMIN_IMPORT_TITLE'),
            "DETAILS"=> "#PROGRESS_BAR#",
            "HTML"=>true,
            "TYPE"=>"PROGRESS",
            "PROGRESS_TOTAL" => 100,
            "PROGRESS_VALUE" => round($page/$pages*100),
        ));

        if ($complete) {
            CAdminMessage::ShowNote(Loc::getMessage('ADMIN_END_EXPORT'));
            ?><div style="margin-bottom: 30px;"><a target="_blank" href="<?=str_replace($_SERVER["DOCUMENT_ROOT"], "", TRACKING_EXPORT_FILE)?>"><?=Loc::getMessage('ADMIN_EXPORT_LINK')?></a></div><?
        }
        die;

    } else if ($actionType == 'import') { // импорт
        $page = $_SESSION['tracking_import']['page'] ?: 1;
        $nextPage = $page + 1;
        $allErrors = $errorsAr = [];
        $handbookImportName = $request['handbook_import'];

        $hlblock = Highloadblock\HighloadBlockTable::getList([
            'filter' => ['=NAME' => $handbookImportName]
        ])->fetch();

        $hl = Highloadblock\HighloadBlockTable::compileEntity($hlblock)->getDataClass();

        $file = $_FILES['file'];
        move_uploaded_file( $file['tmp_name'], TRACKING_IMPORT_FILE );
        $csv = file_get_contents(TRACKING_IMPORT_FILE);
        $csvAr = explode(PHP_EOL, $csv);
        $csvArChank = array_chunk($csvAr, Option::get('tracking', 'step'));

        foreach ($csvArChank[$page-1] as $changeLine) {
            $errorUpdate = '';
            if (!$changeLine) {continue;}
            $changeAr = explode(TRACKING_CSV_DELIMETR, $changeLine);
            if ($changeAr[4]) {
                $changeAr[4] = Bitrix\Main\Web\Json::decode($changeAr[4]);
            }
            if ($changeAr[3] == Event::UPDATE) {
                if (!$hl::getById($changeAr[2])->fetch()) {
                    $errorUpdate = Loc::getMessage('ADMIN_ELEMENT_NOT_FOUND').$changeAr[2];
                }
                $result = $hl::update($changeAr[2], $changeAr[4]);
            } else if ($changeAr[3] == Event::DELETE) {
                if (!$hl::getById($changeAr[2])->fetch()) {
                    $errorUpdate = Loc::getMessage('ADMIN_ELEMENT_NOT_FOUND_DEL').$changeAr[2];
                }
                $result = $hl::delete($changeAr[2]);
            } else if ($changeAr[3] == Event::ADD) {
                $result = $hl::add($changeAr[4]);
            }

            if (!$result->isSuccess()) {
                foreach ($result->getErrorMessages() as $error) {
                    $_SESSION['tracking_import']['errors'][] = $error;
                }
            }
            if ($errorUpdate) {
                $_SESSION['tracking_import']['errors'][] = $errorUpdate;
            }
            if ($result->isSuccess() && !$errorUpdate) {
                $_SESSION['tracking_import']['logs'][$changeAr[3]][] = $changeAr[2];
            }
        }

        $allErrors = $_SESSION['tracking_import']['errors'];

        $pages = count($csvArChank);
        $_SESSION['tracking_import']['page'] = $nextPage;
        $_SESSION['tracking_import']['pages'] = $pages;

        if ($nextPage > $pages) {
            if (Option::get('tracking', 'logs')) {
                LogTable::add([
                    'add' => (int)count(array_unique($_SESSION['tracking_import']['logs'][Event::ADD])),
                    'delete' => (int)count(array_unique($_SESSION['tracking_import']['logs'][Event::DELETE])),
                    'update' => (int)count(array_unique($_SESSION['tracking_import']['logs'][Event::UPDATE])),
                    'errors' => Bitrix\Main\Web\Json::encode($_SESSION['tracking_import']['errors'])
                ]);
            }
            $asd = $_SESSION['tracking_import']['logs'];
            unset($_SESSION['tracking_import']);
            $complete = true;
        }

        $APPLICATION->RestartBuffer();
        CAdminMessage::ShowMessage(array(
            "MESSAGE"=> Loc::getMessage('ADMIN_IMPORT_TITLE'),
            "DETAILS"=> "#PROGRESS_BAR#",
            "HTML"=>true,
            "TYPE"=>"PROGRESS",
            "PROGRESS_TOTAL" => 100,
            "PROGRESS_VALUE" => round($page/$pages*100),
        ));

        if ($complete) {
            CAdminMessage::ShowNote(Loc::getMessage('ADMIN_END_IMPORT'));
            ?><div style="margin-bottom: 30px;"><?
            foreach ($allErrors as $error) {
                ?><div><?=$error?></div><?
            }
            ?></div><?
        }
        die;
    }
}


// ******************************************************************** //
//                ВЫБОРКА И ПОДГОТОВКА ДАННЫХ ФОРМЫ                     //
// ******************************************************************** //

$handbooks = $handbookNames = $hls = [];

$hlblocks = Bitrix\Highloadblock\HighloadBlockTable::getList();
while ($hl = $hlblocks->fetch()) {
    $hls[$hl['TABLE_NAME']] = $hl['NAME'];
}

$properties = CIBlockProperty::GetList([],["USER_TYPE"=>"directory"]);
while ($prop_fields = $properties->GetNext()) {
    $handbooks[$hls[$prop_fields['USER_TYPE_SETTINGS']['TABLE_NAME']]] = $prop_fields['NAME'];
    $handbookNames[] = $prop_fields['NAME'];
}

$handbooksSelect = array(
    "REFERENCE" => $handbookNames,
    "REFERENCE_ID" => array_keys($handbooks)
);

//разделить подготовку данных и вывод
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_after.php");

?>

<div class="progress_bar_tracking"></div>
<form id="tracking_admin_form" method="POST" Action="<?echo $APPLICATION->GetCurPage()?>" ENCTYPE="multipart/form-data" name="post_form">
    <input type="hidden" name="action_type" value="export">
<?// проверка идентификатора сессии ?>
<?=bitrix_sessid_post();?>
<?
// отобразим заголовки закладок
$tabControl->Begin();
?>
<?
$tabControl->BeginNextTab();
?>
    <tr>
        <td width="40%"><?=Loc::getMessage("ADMIN_HANDBOOK_SELECT_EXPORT")?></td>
        <td width="60%"><?=SelectBoxFromArray("handbook_export", $handbooksSelect);?></td>
    </tr>
<?
$tabControl->BeginNextTab();
?>
    <tr>
        <td width="40%"><?=Loc::getMessage("ADMIN_HANDBOOK_SELECT_IMPORT")?></td>
        <td width="60%"><?=SelectBoxFromArray("handbook_import", $handbooksSelect);?></td>
    </tr>
    <tr>
        <td><?=Loc::getMessage("ADMIN_FILE_IMPORT")?></td>
        <td><input type="file" name="tracking_import_file" value="" /></td>
    </tr>
<?
$tabControl->Buttons();
?>
    <input id="export_button" class="adm-btn-save" type="submit" name="export" value="<?=Loc::GetMessage("ADMIN_EXPORT"); ?>" />
    <input id="import_button" class="adm-btn-save hidden" type="submit" name="import" value="<?=Loc::GetMessage("ADMIN_IMPORT"); ?>" />
</form>
<?
$tabControl->End();
?>

<?
$tabControl->ShowWarnings("post_form", $message);
?>

<script>
    $(function () {
        $('#tabControl_tabs .adm-detail-tab').on('click', function () {
            var index = $(this).index();
            $('.adm-btn-save').addClass('hidden');
            $('.adm-btn-save:eq('+index+')').removeClass('hidden');
            if (index == 1) {
                $('#tracking_admin_form').find('[name=action_type]').val('import');
            } else {
                $('#tracking_admin_form').find('[name=action_type]').val('export');
            }
        });

        $('#tracking_admin_form').on('submit', function (event) {
            event.preventDefault();
            ActionGo();
            return false;
        });
    });

    var files;
    $('[name=tracking_import_file]').on('change', function(){
        files = this.files;
    });

    function ActionGo() {
        var $form = $('#tracking_admin_form');
        var actionType = $form.find('[name=action_type]').val();
        var data = {};
        var handbook_import = '';

        $('.progress_bar_tracking').html('');

        if (actionType == 'export') {
            var data = $form.serialize();
        } else {
            if ($('[name=tracking_import_file]').val() == '') {
                alert('Файл для импорта обязателен');
                return false;
            }
            var data = new FormData();
            data.append('file', files[0] );
            handbook_import = $form.find('[name=handbook_import]').val();
        }
        $('.adm-btn-save').addClass('disabled');
        SendAjax(data, actionType, handbook_import);
    }

    function SendAjax(data, actionType, handbook_import) {
        var params = {};
        params = {
            url: '?ajax=y&action_type='+actionType+'&handbook_import='+handbook_import,
            type: 'POST',
            data: data,
            success: function(html){
                var $procent = $(html).find('.adm-progress-bar-inner-text').text();
                $('.progress_bar_tracking').html(html);
                if ($procent == '100%') {
                    $('.adm-btn-save').removeClass('disabled');
                } else {
                    setTimeout(function () {
                        SendAjax(data, actionType, handbook_import);
                    }, 1000);
                }
            },
            error: function( jqXHR, status, errorThrown ){
                alert('Ошибка запроса');
            }
        };
        if (actionType == 'import') {
            params.processData = false;
            params.contentType = false;
        }
        $.ajax(params);
    }
</script>
<style>
    .adm-detail-title-setting, .hidden {
        display: none !important;
    }
    .disabled {
        pointer-events: none;
        opacity: 0.7;
    }
</style>
<?
// завершение страницы
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_admin.php");
?>