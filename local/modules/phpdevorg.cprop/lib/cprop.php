<?php
use Bitrix\Main\Localization\Loc;
Loc::loadMessages(__FILE__);

class CIBlockPropertyCProp
{
    private static $showedCss = false;
    private static $showedJs = false;

    public static function GetUserTypeDescription()
    {
        return array(
            'PROPERTY_TYPE' => 'S',
            'USER_TYPE' => 'ComplexProperty',
            'DESCRIPTION' => Loc::getMessage('IEX_CPROP_DESC'),
            'GetPropertyFieldHtml' => array(__CLASS__,  'GetPropertyFieldHtml'),
            'ConvertToDB' => array(__CLASS__, 'ConvertToDB'),
            'ConvertFromDB' => array(__CLASS__,  'ConvertFromDB'),
            'GetSettingsHTML' => array(__CLASS__, 'GetSettingsHTML'),
            'PrepareSettings' => array(__CLASS__, 'PrepareUserSettings'),
            'GetLength' => array(__CLASS__, 'GetLength'),
            'GetPublicViewHTML' => array(__CLASS__, 'GetPublicViewHTML')
        );
    }

    public static function GetPropertyFieldHtml($arProperty, $value, $strHTMLControlName)
    {
        $hideText = Loc::getMessage('IEX_CPROP_HIDE_TEXT');
        $clearText = Loc::getMessage('IEX_CPROP_CLEAR_TEXT');

        self::showCss();
        self::showJs();

        if (!empty($arProperty['USER_TYPE_SETTINGS'])) {
            $arFields = self::prepareSetting($arProperty['USER_TYPE_SETTINGS']);
        } else {
            return '<span>' . Loc::getMessage('IEX_CPROP_ERROR_INCORRECT_SETTINGS') . '</span>';
        }

        $result = '';
        $result .= '<div class="mf-gray"><a class="cl mf-toggle">' . $hideText . '</a>';
        if ($arProperty['MULTIPLE'] === 'Y') {
            $result .= ' | <a class="cl mf-delete">' . $clearText . '</a></div>';
        }
        $result .= '<table class="mf-fields-list active">';


        foreach ($arFields as $code => $arItem) {
            if ($arItem['TYPE'] === 'string') {
                $result .= self::showString($code, $arItem['TITLE'], $value, $strHTMLControlName);
            } else if ($arItem['TYPE'] === 'file') {
                $result .= self::showFile($code, $arItem['TITLE'], $value, $strHTMLControlName);
            } else if ($arItem['TYPE'] === 'text') {
                $result .= self::showTextarea($code, $arItem['TITLE'], $value, $strHTMLControlName);
            } else if ($arItem['TYPE'] === 'date') {
                $result .= self::showDate($code, $arItem['TITLE'], $value, $strHTMLControlName);
            } else if ($arItem['TYPE'] === 'element') {
                $result .= self::showBindElement($code, $arItem['TITLE'], $value, $strHTMLControlName);
            }
            else if ($arItem['TYPE'] === 'html') {
                $result .= self::showHtml($code, $arItem['TITLE'], $value, $strHTMLControlName);
            }
        }


        $result .= '</table>';

        return $result;
    }

    public static function GetPublicViewHTML($arProperty, $value, $strHTMLControlName)
    {
        return $value;
    }

public static function GetSettingsHTML($arProperty, $strHTMLControlName, &$arPropertyFields)
{
    $btnAdd = Loc::getMessage('IEX_CPROP_SETTING_BTN_ADD');
    $settingsTitle = Loc::getMessage('IEX_CPROP_SETTINGS_TITLE');

    $arPropertyFields = array(
        'USER_TYPE_SETTINGS_TITLE' => $settingsTitle,
        'HIDE' => array('ROW_COUNT', 'COL_COUNT', 'DEFAULT_VALUE', 'SEARCHABLE', 'SMART_FILTER', 'WITH_DESCRIPTION', 'FILTRABLE', 'MULTIPLE_CNT', 'IS_REQUIRED'),
        'SET' => array(
            'MULTIPLE_CNT' => 1,
            'SMART_FILTER' => 'N',
            'FILTRABLE' => 'N',
        ),
    );

    self::showCssForSetting();
    self::showJsForSetting($strHTMLControlName["NAME"]);
    $result = '<tr><td colspan="2" align="center">
        <table id="many-fields-table" class="many-fields-table internal">         
            <tr valign="top" class="heading mf-setting-title">
               <td>XML_ID</td>
               <td>' . Loc::getMessage('IEX_CPROP_SETTING_FIELD_TITLE') . '</td>
               <td>' . Loc::getMessage('IEX_CPROP_SETTING_FIELD_SORT') . '</td>
               <td>' . Loc::getMessage('IEX_CPROP_SETTING_FIELD_TYPE') . '</td>
            </tr>';

    $arSetting = self::prepareSetting($arProperty['USER_TYPE_SETTINGS']);

    if (!empty($arSetting)) {
        foreach ($arSetting as $code => $arItem) {
            $result .= '
               <tr valign="top">
                  <td><input type="text" class="inp-code" size="20" value="' . htmlspecialcharsbx($code) . '"></td>
                  <td><input type="text" class="inp-title" size="35" name="' . $strHTMLControlName["NAME"] . '[' . $code . '_TITLE]" value="' . htmlspecialcharsbx($arItem['TITLE']) . '"></td>
                  <td><input type="text" class="inp-sort" size="5" name="' . $strHTMLControlName["NAME"] . '[' . $code . '_SORT]" value="' . $arItem['SORT'] . '"></td>
                  <td>
                     <select class="inp-type" name="' . $strHTMLControlName["NAME"] . '[' . $code . '_TYPE]">
                        ' . self::getOptionList($arItem['TYPE']) . '
                     </select>                               
                  </td>
               </tr>';
        }
    }

    $result .= '
           <tr valign="top">
              <td><input type="text" class="inp-code" size="20"></td>
              <td><input type="text" class="inp-title" size="35"></td>
              <td><input type="text" class="inp-sort" size="5" value="500"></td>
              <td><select class="inp-type">' . self::getOptionList() . '</select></td>
           </tr>
        </table>  
        <div style="text-align: center; margin-top: 10px;">
           <input type="button" value="' . $btnAdd . '" onclick="addNewRows()">
        </div>
        </td></tr>';

    return $result;
}
    public static function PrepareUserSettings($arProperty)
    {
        $result = [];
        if (!empty($arProperty['USER_TYPE_SETTINGS'])) {
            foreach ($arProperty['USER_TYPE_SETTINGS'] as $code => $value) {
                $result[$code] = $value;
            }
        }
        return $result;
    }

    public static function GetLength($arProperty, $arValue)
    {
        $arFields = self::prepareSetting(unserialize($arProperty['USER_TYPE_SETTINGS']));

        $result = false;
        foreach ($arValue['VALUE'] as $code => $value) {
            if ($arFields[$code]['TYPE'] === 'file') {
                if (!empty($value['name']) || (!empty($value['OLD']) && empty($value['DEL']))) {
                    $result = true;
                    break;
                }
            } else {
                if (!empty($value)) {
                    $result = true;
                    break;
                }
            }
        }
        return $result;
    }

public static function ConvertToDB($arProperty, $arValue)
{
    if (!isset($arValue['VALUE']) || !is_array($arValue['VALUE'])) {
        return ['VALUE' => ''];
    }

    $data = $arValue['VALUE'];
    $json = json_encode(
        $data,
        JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE
    );

    return ['VALUE' => is_string($json) ? $json : ''];
}

public static function ConvertFromDB($arProperty, $arValue)
{
    $result = ['VALUE' => []];

    if (!empty($arValue['VALUE']) && is_string($arValue['VALUE'])) {
        $decoded = json_decode($arValue['VALUE'], true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            $result['VALUE'] = $decoded;
        }
    }

    return $result;
}
    //Internals

    private static function showString($code, $title, $arValue, $strHTMLControlName)
    {
        $result = '';

        $v = !empty($arValue['VALUE'][$code]) ? $arValue['VALUE'][$code] : '';
        $result .= '<tr>
                    <td align="right">' . $title . ': </td>
                    <td><input type="text" value="' . $v . '" name="' . $strHTMLControlName['VALUE'] . '[' . $code . ']"/></td>
                </tr>';

        return $result;
    }

    private static function showFile($code, $title, $arValue, $strHTMLControlName)
    {
        $result = '';

        if (!empty($arValue['VALUE'][$code]) && !is_array($arValue['VALUE'][$code])) {
            $fileId = $arValue['VALUE'][$code];
        } else if (!empty($arValue['VALUE'][$code]['OLD'])) {
            $fileId = $arValue['VALUE'][$code]['OLD'];
        } else {
            $fileId = '';
        }

        if (!empty($fileId)) {
            $arPicture = CFile::GetByID($fileId)->Fetch();
            if ($arPicture) {
                $strImageStorePath = COption::GetOptionString('main', 'upload_dir', 'upload');
                $sImagePath = '/' . $strImageStorePath . '/' . $arPicture['SUBDIR'] . '/' . $arPicture['FILE_NAME'];
                $fileType = self::getExtension($sImagePath);

                if (in_array($fileType, ['png', 'jpg', 'jpeg', 'gif'])) {
                    $content = '<img src="' . $sImagePath . '">';
                } else {
                    $content = '<div class="mf-file-name">' . $arPicture['FILE_NAME'] . '</div>';
                }

                $result = '<tr>
                        <td align="right" valign="top">' . $title . ': </td>
                        <td>
                            <table class="mf-img-table">
                                <tr>
                                    <td>' . $content . '<br>
                                        <div>
                                            <label><input name="' . $strHTMLControlName['VALUE'] . '[' . $code . '][DEL]" value="Y" type="checkbox"> ' . Loc::getMessage("IEX_CPROP_FILE_DELETE") . '</label>
                                            <input name="' . $strHTMLControlName['VALUE'] . '[' . $code . '][OLD]" value="' . $fileId . '" type="hidden">
                                        </div>
                                    </td>
                                </tr>
                            </table>                      
                        </td>
                    </tr>';
            }
        } else {
            $result .= '<tr>
                    <td align="right">' . $title . ': </td>
                    <td><input type="file" value="" name="' . $strHTMLControlName['VALUE'] . '[' . $code . ']"/></td>
                </tr>';
        }

        return $result;
    }

    public static function showTextarea($code, $title, $arValue, $strHTMLControlName)
    {
        $result = '';

        $v = !empty($arValue['VALUE'][$code]) ? $arValue['VALUE'][$code] : '';
        $result .= '<tr>
                    <td align="right" valign="top">' . $title . ': </td>
                    <td><textarea rows="8" name="' . $strHTMLControlName['VALUE'] . '[' . $code . ']">' . $v . '</textarea></td>
                </tr>';

        return $result;
    }
    private static function showHtml($code, $title, $arValue, $strHTMLControlName)
    {
        $fieldName = $strHTMLControlName['VALUE'].'['.$code.']';
        $fieldValue = isset($arValue['VALUE'][$code]) ? $arValue['VALUE'][$code] : '';

        ob_start();
        ?>
        <tr>
            <td align="right" valign="top"><?=htmlspecialcharsbx($title)?>:</td>
            <td>
                <?
                if (\CModule::IncludeModule("fileman")) {
                    \CFileMan::AddHTMLEditorFrame(
                        $fieldName,              // Имя поля
                        $fieldValue,             // Значение
                        $fieldName."_TYPE",      // Имя поля типа
                        "html",                  // Тип по умолчанию
                        array(
                            'height' => 200,
                            'width' => '100%'
                        ),
                        "N",                     // Показывать "Y/N"
                        0,
                        "",
                        "",
                        false,                   // arIBlock
                        true,                    // bFull (Полный режим)
                        false,                   // bShow
                        array(                   // Доп. настройки тулбара
                            'toolbarConfig' => array(
                                'Bold', 'Italic', 'Underline', 'Strike',
                                'CreateLink', 'DeleteLink',
                                'Image', 'Video',
                                'BackColor', 'ForeColor',
                                'JustifyLeft', 'JustifyCenter', 'JustifyRight', 'JustifyFull',
                                'InsertOrderedList', 'InsertUnorderedList',
                                'Source'
                            )
                        )
                    );
                } else {
                    echo '<textarea name="'.$fieldName.'" rows="10" cols="50">'.htmlspecialcharsbx($fieldValue).'</textarea>';
                }
                ?>
            </td>
        </tr>
        <?
        $html = ob_get_contents();
        ob_end_clean();

        return $html;
    }
    public static function showDate($code, $title, $arValue, $strHTMLControlName)
    {
        $result = '';

        $v = !empty($arValue['VALUE'][$code]) ? $arValue['VALUE'][$code] : '';
        $result .= '<tr>
                        <td align="right" valign="top">' . $title . ': </td>
                        <td>
                            <table>
                                <tr>
                                    <td style="padding: 0;">
                                        <div class="adm-input-wrap adm-input-wrap-calendar">
                                            <input class="adm-input adm-input-calendar" type="text" name="' . $strHTMLControlName['VALUE'] . '[' . $code . ']" size="23" value="' . $v . '">
                                            <span class="adm-calendar-icon"
                                                  onclick="BX.calendar({node: this, field:\'' . $strHTMLControlName['VALUE'] . '[' . $code . ']\', form: \'\', bTime: true, bHideTime: false});"></span>
                                        </div>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>';

        return $result;
    }

    public static function showBindElement($code, $title, $arValue, $strHTMLControlName)
    {
        $result = '';

        $v = !empty($arValue['VALUE'][$code]) ? $arValue['VALUE'][$code] : '';

        $elUrl = '';
        if (!empty($v)) {
            $arElem = \CIBlockElement::GetList([], ['ID' => $v], false, ['nPageSize' => 1], ['ID', 'IBLOCK_ID', 'IBLOCK_TYPE_ID', 'NAME'])->Fetch();
            if (!empty($arElem)) {
                $elUrl .= '<a target="_blank" href="/bitrix/admin/iblock_element_edit.php?IBLOCK_ID=' . $arElem['IBLOCK_ID'] . '&ID=' . $arElem['ID'] . '&type=' . $arElem['IBLOCK_TYPE_ID'] . '">' . $arElem['NAME'] . '</a>';
            }
        }

        $result .= '<tr>
                    <td align="right">' . $title . ': </td>
                    <td>
                        <input name="' . $strHTMLControlName['VALUE'] . '[' . $code . ']" id="' . $strHTMLControlName['VALUE'] . '[' . $code . ']" value="' . $v . '" size="8" type="text" class="mf-inp-bind-elem">
                        <input type="button" value="..." onClick="jsUtils.OpenWindow(\'/bitrix/admin/iblock_element_search.php?lang=ru&IBLOCK_ID=0&n=' . $strHTMLControlName['VALUE'] . '&k=' . $code . '\', 900, 700);">&nbsp;
                        <span>' . $elUrl . '</span>
                    </td>
                </tr>';

        return $result;
    }

    private static function showCss()
    {
        if (!self::$showedCss) {
            self::$showedCss = true;
?>
            <style>
                .cl {
                    cursor: pointer;
                }

                .mf-gray {
                    color: #797777;
                }

                .mf-fields-list {
                    display: none;
                    padding-top: 10px;
                    margin-bottom: 10px !important;
                    margin-left: -300px !important;
                    border-bottom: 1px #e0e8ea solid !important;
                }

                .mf-fields-list.active {
                    display: block;
                }

                .mf-fields-list td {
                    padding-bottom: 5px;
                }

                .mf-fields-list td:first-child {
                    width: 300px;
                    color: #616060;
                }

                .mf-fields-list td:last-child {
                    padding-left: 5px;
                }

                .mf-fields-list input[type="text"] {
                    width: 350px !important;
                }

                .mf-fields-list textarea {
                    min-width: 350px;
                    max-width: 650px;
                    color: #000;
                }

                .mf-fields-list img {
                    max-height: 150px;
                    margin: 5px 0;
                }

                .mf-img-table {
                    background-color: #e0e8e9;
                    color: #616060;
                    width: 100%;
                }

                .mf-fields-list input[type="text"].adm-input-calendar {
                    width: 170px !important;
                }

                .mf-file-name {
                    word-break: break-word;
                    padding: 5px 5px 0 0;
                    color: #101010;
                }

                .mf-fields-list input[type="text"].mf-inp-bind-elem {
                    width: unset !important;
                }
            </style>
        <?
        }
    }

private static function showJs()
{
    if (!self::$showedJs) {
        self::$showedJs = true;
        $showText = Loc::getMessage('IEX_CPROP_SHOW_TEXT');
        $hideText = Loc::getMessage('IEX_CPROP_HIDE_TEXT');
        ?>
        <script>
        BX.ready(function(){
            BX.bindDelegate(document.body, "click", {tagName: "a", className: "mf-toggle"}, function(e){
                e.preventDefault();
                var table = BX.findChild(this.parentNode.parentNode, {tagName: "table", className: "mf-fields-list"}, true);
                if (table) {
                    BX.toggleClass(table, "active");
                    if (BX.hasClass(table, "active")) {
                        this.innerHTML = '<?php echo $hideText; ?>';
                    } else {
                        this.innerHTML = '<?php echo $showText; ?>';
                    }
                }
            });

            BX.bindDelegate(document.body, "click", {tagName: "a", className: "mf-delete"}, function(e){
                e.preventDefault();
                var tr = this.parentNode.parentNode;
                // Очистить все input
                var inputs = BX.findChildren(tr, {tagName: "input"}, true);
                for (var i = 0; i < inputs.length; i++) {
                    if (inputs[i].type === "text") inputs[i].value = "";
                    if (inputs[i].type === "checkbox") inputs[i].checked = true;
                }
                var textareas = BX.findChildren(tr, {tagName: "textarea"}, true);
                for (var i = 0; i < textareas.length; i++) {
                    textareas[i].value = "";
                }
                BX.addClass(tr, "bx-hidden"); // Скрыть плавно
            });
        });
        </script>
        <?php
    }
}
private static function showJsForSetting($inputName)
{
    ?>
    <script>
    function addNewRows() {
        var table = document.getElementById("many-fields-table");
        var row = table.insertRow(-1);
        row.innerHTML = 
            '<td><input type="text" class="inp-code" size="20"></td>' +
            '<td><input type="text" class="inp-title" size="35"></td>' +
            '<td><input type="text" class="inp-sort" size="5" value="500"></td>' +
            '<td><select class="inp-type"><?php echo addslashes(self::getOptionList()); ?></select></td>';
    }
    </script>
    <?php
}
    private static function showCssForSetting()
    {
        if (!self::$showedCss) {
            self::$showedCss = true;
        ?>
            <style>
                .many-fields-table {
                    margin: 0 auto;
                    /*display: inline;*/
                }

                .mf-setting-title td {
                    text-align: center !important;
                    border-bottom: unset !important;
                }

                .many-fields-table td {
                    text-align: center;
                }

                .many-fields-table>input,
                .many-fields-table>select {
                    width: 90% !important;
                }

                .inp-sort {
                    text-align: center;
                }

                .inp-type {
                    min-width: 125px;
                }
            </style>
<?
        }
    }

    private static function prepareSetting($arSetting)
{
    if (!is_array($arSetting) && is_string($arSetting) && $arSetting !== '') {
        $decoded = json_decode($arSetting, true);
        if (is_array($decoded)) {
            $arSetting = $decoded;
        }
    }

    $arResult = [];

    $first = is_array($arSetting) ? reset($arSetting) : null;
    if (is_array($first) && (isset($first['TYPE']) || isset($first['NAME']) || isset($first['TITLE']))) {
        foreach ($arSetting as $code => $field) {
            if (!is_array($field)) continue;

            $arResult[$code] = [
                'TITLE' => (string)($field['TITLE'] ?? $field['NAME'] ?? $code),
                'TYPE'  => (string)($field['TYPE'] ?? 'string'),
                'SORT'  => (int)($field['SORT'] ?? 500),
            ];
        }
    }
    else {
        foreach ((array)$arSetting as $key => $value) {
            if (strpos($key, '_TITLE') !== false) {
                $code = str_replace('_TITLE', '', $key);
                $arResult[$code]['TITLE'] = $value;
            } elseif (strpos($key, '_SORT') !== false) {
                $code = str_replace('_SORT', '', $key);
                $arResult[$code]['SORT'] = (int)$value;
            } elseif (strpos($key, '_TYPE') !== false) {
                $code = str_replace('_TYPE', '', $key);
                $arResult[$code]['TYPE'] = $value;
            }
        }
    }

    uasort($arResult, function($a, $b){
        $sa = (int)($a['SORT'] ?? 500);
        $sb = (int)($b['SORT'] ?? 500);
        return $sa <=> $sb;
    });

    return $arResult;
}

    private static function getOptionList($selected = 'string')
    {
        $result = '';
        $arOption = [
            'string' => Loc::getMessage('IEX_CPROP_FIELD_TYPE_STRING'),
            'file' => Loc::getMessage('IEX_CPROP_FIELD_TYPE_FILE'),
            'text' => Loc::getMessage('IEX_CPROP_FIELD_TYPE_TEXT'),
            'date' => Loc::getMessage('IEX_CPROP_FIELD_TYPE_DATE'),
            'element' => Loc::getMessage('IEX_CPROP_FIELD_TYPE_ELEMENT'),
            'html' => Loc::getMessage('IEX_CPROP_FIELD_TYPE_HTML')
        ];

        foreach ($arOption as $code => $name) {
            $s = '';
            if ($code === $selected) {
                $s = 'selected';
            }

            $result .= '<option value="' . $code . '" ' . $s . '>' . $name . '</option>';
        }

        return $result;
    }

    private static function prepareFileToDB($arValue)
    {
        $result = false;

        if (!empty($arValue['DEL']) && $arValue['DEL'] === 'Y' && !empty($arValue['OLD'])) {
            CFile::Delete($arValue['OLD']);
        } else if (!empty($arValue['OLD'])) {
            $result = $arValue['OLD'];
        } else if (!empty($arValue['name'])) {
            $result = CFile::SaveFile($arValue, 'vote');
        }

        return $result;
    }

    private static function getExtension($filePath)
    {
        return array_pop(explode('.', $filePath));
    }
}
