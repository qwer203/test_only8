<?php

class CUserTypeCprop extends CUserTypeString
{
    public static function GetUserTypeDescription()
    {
        return array(
            "USER_TYPE_ID" => "cprop_uf",
            "CLASS_NAME" => "CUserTypeCprop",
            "DESCRIPTION" => "Комплексное свойство (CProp + HTML)",
            "BASE_TYPE" => "string",
        );
    }

    
    function GetEditFormHTML($arUserField, $arHtmlControl)
    {
        $values = [];
        if ($arUserField['VALUE']) {
            $values = json_decode(htmlspecialchars_decode($arUserField['VALUE']), true);
            if (!$values) $values = [];
        }

        $schema = [];
        if (isset($arUserField['SETTINGS']['SCHEMA'])) {
             $schema = $arUserField['SETTINGS']['SCHEMA'];
        }
        
        if (!is_array($schema) && !empty($schema)) {
             $decoded = json_decode($schema, true);
             if (is_array($decoded)) $schema = $decoded;
        }

        if (empty($schema) || !is_array($schema)) {
            return "Схема полей не настроена (проверьте JSON в настройках поля)";
        }

        $html = '<table class="cprop-uf-table" style="width:100%; border-collapse:collapse;">';
        
        foreach ($schema as $fieldCode => $field) {
            $fieldName = $arHtmlControl["NAME"] . '[' . $fieldCode . ']';
            $val = isset($values[$fieldCode]) ? $values[$fieldCode] : '';

            $html .= '<tr><td style="padding:5px; width:150px; vertical-align:top;">' . htmlspecialcharsbx($field['NAME']) . ':</td><td style="padding:5px;">';

            if (isset($field['TYPE']) && $field['TYPE'] == 'html') {
                ob_start();
                if (\CModule::IncludeModule("fileman")) {
                    \CFileMan::AddHTMLEditorFrame(
                        $fieldName,
                        $val,
                        $fieldName."_TYPE",
                        "html",
                        ['height' => 200, 'width' => '100%']
                    );
                } else {
                    echo '<textarea name="'.$fieldName.'" rows="10" cols="50">'.htmlspecialcharsbx($val).'</textarea>';
                }
                $html .= ob_get_clean();
            } else {
                $html .= '<input type="text" name="'.$fieldName.'" value="'.htmlspecialcharsbx($val).'" style="width:100%">';
            }
            $html .= '</td></tr>';
        }
        $html .= '</table>';

        return $html;
    }


    function OnBeforeSave($arUserField, $value)
    {
        if (is_array($value)) {
            return json_encode($value, JSON_UNESCAPED_UNICODE);
        }
        return $value;
    }


public function getSettingsHtml($userField, $additionalParameters, $varsFromForm)
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

    $result = '<tr><td colspan="2" align="center">
        <table id="many-fields-table" class="many-fields-table internal">         
            <tr valign="top" class="heading mf-setting-title">
                <td>XML_ID</td>
                <td>' . Loc::getMessage('IEX_CPROP_SETTING_FIELD_TITLE') . '</td>
                <td>' . Loc::getMessage('IEX_CPROP_SETTING_FIELD_SORT') . '</td>
                <td>' . Loc::getMessage('IEX_CPROP_SETTING_FIELD_TYPE') . '</td>
            </tr>';

    $arSetting = self::prepareSetting($arProperty['USER_TYPE_SETTINGS']);
    $rowIndex = 0;

    if (!empty($arSetting)) {
        foreach ($arSetting as $code => $arItem) {
            $rowIndex++;
            $result .= '
            <tr valign="top" data-row="' . $rowIndex . '">
                <td><input type="text" id="code_' . $rowIndex . '" class="inp-code" name="ROW_' . $rowIndex . '_CODE" size="20" value="' . htmlspecialcharsbx($code) . '"></td>
                <td><input type="text" id="title_' . $rowIndex . '" class="inp-title" size="35" name="' . $strHTMLControlName["NAME"] . '[' . $code . '_TITLE]" value="' . htmlspecialcharsbx($arItem['TITLE']) . '"></td>
                <td><input type="text" id="sort_' . $rowIndex . '" class="inp-sort" size="5" name="' . $strHTMLControlName["NAME"] . '[' . $code . '_SORT]" value="' . $arItem['SORT'] . '"></td>
                <td>
                    <select id="type_' . $rowIndex . '" class="inp-type" name="' . $strHTMLControlName["NAME"] . '[' . $code . '_TYPE]">
                        ' . self::getOptionList($arItem['TYPE']) . '
                    </select>                               
                </td>
            </tr>';
        }
    }

    
    $rowIndex++;
    $result .= '
        <tr valign="top" data-row="' . $rowIndex . '">
            <td><input type="text" id="code_' . $rowIndex . '" class="inp-code" name="ROW_' . $rowIndex . '_CODE" size="20"></td>
            <td><input type="text" id="title_' . $rowIndex . '" class="inp-title" size="35" name="ROW_NEW_TITLE"></td>
            <td><input type="text" id="sort_' . $rowIndex . '" class="inp-sort" size="5" name="ROW_NEW_SORT" value="500"></td>
            <td><select id="type_' . $rowIndex . '" class="inp-type" name="ROW_NEW_TYPE">' . self::getOptionList() . '</select></td>
        </tr>
        </table>';

    $result .= '
        <div style="text-align: center; margin: 10px 0;">
            <input type="button" id="cprop-add-field-btn" value="' . $btnAdd . '" style="margin-right: 10px;">
            <input type="button" id="cprop-remove-field-btn" value="Удалить поле">
        </div>

        <script>
        (function(){
            var table = document.getElementById("many-fields-table");
            var btnAdd = document.getElementById("cprop-add-field-btn");
            var btnRemove = document.getElementById("cprop-remove-field-btn");
            var rowCounter = ' . $rowIndex . ';

            function addNewRow() {
                rowCounter++;
                var row = table.insertRow(-1);
                row.setAttribute("data-row", rowCounter);
                row.innerHTML = 
                    "<td><input type=\\"text\\" id=\\"code_" + rowCounter + "\\" class=\\"inp-code\\" name=\\"ROW_" + rowCounter + "_CODE\\" size=\\"20\\"></td>" +
                    "<td><input type=\\"text\\" id=\\"title_" + rowCounter + "\\" class=\\"inp-title\\" size=\\"35\\"></td>" +
                    "<td><input type=\\"text\\" id=\\"sort_" + rowCounter + "\\" class=\\"inp-sort\\" size=\\"5\\" value=\\"500\\"></td>" +
                    "<td><select id=\\"type_" + rowCounter + "\\" class=\\"inp-type\\">' . addslashes(self::getOptionList()) . '</select></td>";
                updateNames(row);
            }

            function removeRow() {
                var rows = table.rows;
                if (rows.length > 2) { // Оставляем заголовок + 1 строку
                    table.deleteRow(-1);
                }
            }

            function updateNames(row) {
                var codeInput = row.querySelector(".inp-code");
                var code = codeInput.value;
                var titleInput = row.querySelector(".inp-title");
                var sortInput = row.querySelector(".inp-sort");
                var typeSelect = row.querySelector(".inp-type");
                
                var inputName = "' . addslashes($strHTMLControlName["NAME"]) . '";
                
                if (code.length > 0) {
                    titleInput.name = inputName + "[" + code + "_TITLE]";
                    sortInput.name = inputName + "[" + code + "_SORT]";
                    typeSelect.name = inputName + "[" + code + "_TYPE]";
                } else {
                    titleInput.removeAttribute("name");
                    sortInput.removeAttribute("name");
                    typeSelect.removeAttribute("name");
                }
            }

            // ✅ События
            if (btnAdd) btnAdd.onclick = function(){ addNewRow(); return false; };
            if (btnRemove) btnRemove.onclick = function(){ removeRow(); return false; };

            table.addEventListener("input", function(e) {
                if (e.target.classList.contains("inp-code")) {
                    updateNames(e.target.closest("tr"));
                } else if (e.target.classList.contains("inp-sort")) {
                    e.target.value = e.target.value.replace(/[^0-9]/g, "");
                }
            });
        })();
        </script>';

    $result .= '</td></tr>';

    return $result;
}
    
    function prepareSettings($userField)
    {
        $schema = [];
        if(isset($arUserField['SETTINGS']['SCHEMA'])) {
            $schema = $arUserField['SETTINGS']['SCHEMA'];
        }
        
        if (!is_array($schema)) {
            $decoded = json_decode($schema, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $schema = $decoded;
            } else {
                $schema = [];
            }
        }
        return array("SCHEMA" => $schema);
    }
}
?>
