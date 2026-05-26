<?php

namespace App\Iblock\Properties;

use Bitrix\Main\Localization\Loc;
use App\Models\Lists\DoktorsPropertyValuesTable;

Loc::loadMessages(__FILE__);

/**
 * Пользовательское свойство инфоблока для бронирования процедур врача.
 * 
 * Class DoctorBookingProperty
 * @package App\Iblock\Properties
 */
class DoctorBookingProperty
{
    /**
     * Описание свойства для регистрации в системе.
     * 
     * @return array
     */
    public static function GetUserTypeDescription(): array
    {
        return [
            'PROPERTY_TYPE' => 'S',
            'USER_TYPE' => 'doctor_booking',
            'DESCRIPTION' => Loc::getMessage('DOCTOR_BOOKING_PROP_DESC') ?: 'Бронирование процедур (Кастомное)',
            'GetPropertyFieldHtml' => [__CLASS__, 'GetPropertyFieldHtml'],
            'GetPublicViewHTML' => [__CLASS__, 'GetPublicViewHTML'],
            'GetAdminListViewHTML' => [__CLASS__, 'GetPublicViewHTML'],
        ];
    }

    /**
     * Отображение в административной панели.
     * 
     * @param array $arProperty
     * @param array $value
     * @param array $strHTMLControlName
     * @return string
     */
    public static function GetPropertyFieldHtml(array $arProperty, array $value, array $strHTMLControlName): string
    {
        return '<input type="text" name="' . htmlspecialcharsbx($strHTMLControlName['VALUE']) . '" value="' . htmlspecialcharsbx($value['VALUE']) . '" size="30" />' .
               '<small style="display:block;color:gray;">' . Loc::getMessage('DOCTOR_BOOKING_ADMIN_HELP') . '</small>';
    }

    /**
     * Отображение свойства в публичной части сайта.
     * 
     * @param array $arProperty
     * @param array $value
     * @param array $strHTMLControlName
     * @return string
     */
    public static function GetPublicViewHTML(array $arProperty, array $value, array $strHTMLControlName): string
    {
        \CJSCore::Init(['popup', 'ajax']);

        $doctorId = 0;
        if (!empty($value['IBLOCK_ELEMENT_ID'])) {
            $doctorId = (int)$value['IBLOCK_ELEMENT_ID'];
        } elseif (!empty($value['ELEMENT_ID'])) {
            $doctorId = (int)$value['ELEMENT_ID'];
        }

        if (!$doctorId && !empty($strHTMLControlName['VALUE'])) {
            if (preg_match('/(?:FIELDS|PROPERTY|DATA)\[(\d+)\]/i', $strHTMLControlName['VALUE'], $matches)) {
                $doctorId = (int)$matches[1];
            }
        }

        if (!$doctorId && isset($GLOBALS['CURRENT_DOCTOR_ID'])) {
            $doctorId = (int)$GLOBALS['CURRENT_DOCTOR_ID'];
        }

        if (!$doctorId && isset($_REQUEST['ID'])) {
            $doctorId = (int)$_REQUEST['ID'];
        }

        if (!$doctorId) {
            return '<span class="booking-empty-msg">' . Loc::getMessage('DOCTOR_BOOKING_ERROR_ID') . '</span>';
        }

        $propertyCode = 'PROCED_IF'; 

        try {
            $allProps = DoktorsPropertyValuesTable::getProperties();
            if (!isset($allProps[$propertyCode])) {
                foreach ($allProps as $code => $prop) {
                    if ($prop['PROPERTY_TYPE'] === 'E' && mb_strpos(mb_strtolower($prop['NAME']), 'процедур') !== false) {
                        $propertyCode = $code;
                        break;
                    }
                }
            }
        } catch (\Exception $e) {
        }

        $procedureIds = [];
        $proceduresList = [];

        try {
            $doctorData = DoktorsPropertyValuesTable::getList([
                'select' => [$propertyCode],
                'filter' => ['=IBLOCK_ELEMENT_ID' => $doctorId]
            ])->fetch();

            if (!empty($doctorData[$propertyCode]) && is_array($doctorData[$propertyCode])) {
                $procedureIds = $doctorData[$propertyCode];
            }
        } catch (\Exception $e) {
        }

        if (empty($procedureIds)) {
            if (\CModule::IncludeModule('iblock')) {
                $dbProps = \CIBlockElement::GetProperty(
                    DoktorsPropertyValuesTable::IBLOCK_ID,
                    $doctorId,
                    [],
                    ['CODE' => $propertyCode]
                );
                while ($arProp = $dbProps->Fetch()) {
                    if (!empty($arProp['VALUE'])) {
                        $procedureIds[] = $arProp['VALUE'];
                    }
                }
            }
        }

        if (!empty($procedureIds)) {
            try {
                $dbElements = \Bitrix\Iblock\ElementTable::getList([
                    'select' => ['NAME'],
                    'filter' => [
                        '=ID' => $procedureIds,
                        '=ACTIVE' => 'Y'
                    ]
                ]);
                while ($element = $dbElements->fetch()) {
                    $proceduresList[] = $element['NAME'];
                }
            } catch (\Exception $e) {
            }
        }

        if (empty($proceduresList)) {
            return '<span class="booking-empty-msg">' . Loc::getMessage('DOCTOR_BOOKING_NO_PROCEDURES') . '</span>';
        }

        $html = '';

        static $assetsForced = false;
        if (!$assetsForced) {
            $html .= '
            <script>
            window.openDoctorBookingPopup = function (button, event) {
                if (event) {
                    event.preventDefault();
                    event.stopPropagation();
                }

                var procedureName = button.getAttribute("data-procedure");
                var container = button.closest(".doctor-booking-container");
                var doctorId = container ? container.getAttribute("data-doctor-id") : null;
                
                if (!doctorId) {
                    alert("Ошибка: не удалось определить ID врача.");
                    return;
                }

                var formHtml = \'<div class="booking-popup-form">\' +
                    \'<form id="doctor-booking-ajax-form">\' +
                        \'<div class="booking-form-group">\' +
                            \'<label for="patient_fio">ФИО Пациента <span class="booking-required">*</span>:</label>\' +
                            \'<input type="text" id="patient_fio" name="patient_fio" required class="booking-input" placeholder="Иванов Иван Иванович">\' +
                        \'</div>\' +
                        \'<div class="booking-form-group">\' +
                            \'<label for="booking_time">Дата и время записи <span class="booking-required">*</span>:</label>\' +
                            \'<input type="datetime-local" id="booking_time" name="booking_time" required class="booking-input">\' +
                        \'</div>\' +
                        \'<div id="booking-error-msg" class="booking-alert booking-alert-danger" style="display: none;"></div>\' +
                        \'<div id="booking-success-msg" class="booking-alert booking-alert-success" style="display: none;"></div>\' +
                    \'</form>\' +
                \'</div>\';

                var bookingPopup = BX.PopupWindowManager.create("doctor-booking-popup-window", null, {
                    content: formHtml,
                    width: 450,
                    closeIcon: true,
                    titleBar: "Запись на процедуру: " + procedureName,
                    closeByEsc: true,
                    overlay: {
                        backgroundColor: "#000",
                        opacity: "40"
                    },
                    buttons: [
                        new BX.PopupWindowButton({
                            text: "Забронировать",
                            className: "ui-btn ui-btn-success popup-window-button-accept",
                            events: {
                                click: function () {
                                    var patientFio = BX("patient_fio").value.trim();
                                    var bookingTime = BX("booking_time").value;
                                    var errorBox = BX("booking-error-msg");
                                    var successBox = BX("booking-success-msg");
                                    
                                    errorBox.style.display = "none";
                                    successBox.style.display = "none";

                                    if (!patientFio || !bookingTime) {
                                        errorBox.innerText = "Пожалуйста, заполните все обязательные поля.";
                                        errorBox.style.display = "block";
                                        return;
                                    }

                                    BX.addClass(this.buttonNode, "ui-btn-clock");

                                    BX.ajax({
                                        url: "/local/ajax/booking.php",
                                        method: "POST",
                                        data: {
                                            sessid: BX.bitrix_sessid(),
                                            doctor_id: doctorId,
                                            procedure: procedureName,
                                            patient_fio: patientFio,
                                            booking_time: bookingTime
                                        },
                                        dataType: "json",
                                        onsuccess: function (response) {
                                            BX.removeClass(this.buttonNode, "ui-btn-clock");
                                            if (response.status === "success") {
                                                successBox.innerText = response.message;
                                                successBox.style.display = "block";
                                                
                                                BX("doctor-booking-ajax-form").reset();
                                                setTimeout(function () {
                                                    bookingPopup.close();
                                                    window.location.reload();
                                                }, 2000);
                                            } else {
                                                errorBox.innerText = response.message || "Произошла непредвиденная ошибка.";
                                                errorBox.style.display = "block";
                                            }
                                        },
                                        onfailure: function () {
                                            BX.removeClass(this.buttonNode, "ui-btn-clock");
                                            errorBox.innerText = "Ошибка отправки формы.";
                                            errorBox.style.display = "block";
                                        }
                                    });
                                }
                            }
                        }),
                        new BX.PopupWindowButton({
                            text: "Отмена",
                            className: "ui-btn ui-btn-link",
                            events: {
                                click: function () {
                                    this.popupWindow.close();
                                }
                            }
                        })
                    ],
                    events: {
                        onPopupClose: function () {
                            this.destroy();
                        }
                    }
                });

                bookingPopup.show();
            };
            </script>';
            $assetsForced = true;
        }

        $html .= '<div class="doctor-booking-container" data-doctor-id="' . $doctorId . '">';
        $html .= '<div class="booking-procedures-list">';
        foreach ($proceduresList as $procedure) {
            $html .= '<button type="button" 
                        class="btn-booking-procedure ui-btn ui-btn-xs ui-btn-primary" 
                        data-procedure="' . htmlspecialcharsbx($procedure) . '" 
                        onclick="if(window.openDoctorBookingPopup){ window.openDoctorBookingPopup(this, event); } else { alert(\'Ресурсы загружаются. Пожалуйста, подождите...\'); }">';
            $html .= htmlspecialcharsbx($procedure);
            $html .= '</button>';
        }
        $html .= '</div>';
        $html .= '</div>';

        return $html;
    }
}