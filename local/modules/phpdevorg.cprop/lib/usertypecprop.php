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


    function GetSettingsHTML($arUserField, $arHtmlControl, $bVarsFromForm)
    {
        $val = '';
        if ($bVarsFromForm) {
            $val = $GLOBALS[$arHtmlControl["NAME"]]['SCHEMA'];
        } elseif (isset($arUserField["SETTINGS"]["SCHEMA"])) {
            $val = $arUserField["SETTINGS"]["SCHEMA"];
            if (is_array($val)) $val = json_encode($val, JSON_UNESCAPED_UNICODE);
        }

        return '
        <tr>
            <td valign="top">Схема полей (JSON):</td>
            <td>
                <textarea name="'.$arHtmlControl["NAME"].'[SCHEMA]" cols="60" rows="10">'.htmlspecialcharsbx($val).'</textarea>
                <br><small>Пример: {"title":{"NAME":"Заголовок","TYPE":"string"}, "desc":{"NAME":"Описание","TYPE":"html"}}</small>
            </td>
        </tr>';
    }
    
    function PrepareSettings($arUserField)
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
