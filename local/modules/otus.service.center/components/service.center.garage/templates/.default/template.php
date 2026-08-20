<?php

declare(strict_types=1);

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

/**
 * Шаблон: грид + кнопка добавления + JS для CRUD (создание/редактирование/удаление).
 *
 * @var array $arResult
 */
\CJSCore::Init(['core', 'ajax', 'popup']);

if ($arResult['ERRORS'] !== []) {
    ShowError(implode('<br>', $arResult['ERRORS']));
}

global $APPLICATION;
?>

<div style="margin-bottom:12px;">
    <button type="button" class="ui-btn ui-btn-primary" onclick="otusScAddCar()">
        <?= Loc::getMessage('OTUS_SC_GARAGE_BTN_ADD') ?>
    </button>
</div>

<?php
$APPLICATION->IncludeComponent(
    'bitrix:main.ui.grid',
    '',
    [
        'GRID_ID' => $arResult['GRID_ID'],
        'COLUMNS' => $arResult['COLUMNS'],
        'ROWS' => $arResult['ROWS'],
        'SHOW_TABLE_CHECKBOX' => 'N',
        'SHOW_ROW_CHECKBOX' => 'N',
        'SHOW_SETTINGS' => 'N',
        'AJAX_MODE' => 'N',
    ]
);
?>
<script>
BX.message({
    "OTUS_SC_GARAGE_LOADING": "Загрузка…",
    "OTUS_SC_GARAGE_LOAD_ERROR": "Не удалось загрузить историю",
    "OTUS_SC_GARAGE_CLOSE": "Закрыть",
    "OTUS_SC_GARAGE_ADD_TITLE": "Добавить автомобиль",
    "OTUS_SC_GARAGE_EDIT_TITLE": "Редактировать автомобиль",
    "OTUS_SC_GARAGE_SAVE": "Сохранить",
    "OTUS_SC_GARAGE_CANCEL": "Отмена",
    "OTUS_SC_GARAGE_DELETE_CONFIRM": "Удалить автомобиль?",
    "OTUS_SC_GARAGE_DELETE": "Удалить",
    "OTUS_SC_GARAGE_BRAND": "Марка",
    "OTUS_SC_GARAGE_MODEL": "Модель",
    "OTUS_SC_GARAGE_NUMBER": "Гос. номер",
    "OTUS_SC_GARAGE_YEAR": "Год выпуска",
    "OTUS_SC_GARAGE_COLOR": "Цвет",
    "OTUS_SC_GARAGE_MILEAGE": "Пробег (км)",
    "OTUS_SC_GARAGE_SAVE_ERROR": "Ошибка сохранения",
    "OTUS_SC_GARAGE_DELETE_ERROR": "Ошибка удаления"
});

BX.ready(function () {
    'use strict';

    var currentPopup = null;
    var historyAjaxUrl = '/local/components/otus/service.center.garage.history/ajax.php?sessid=<?= bitrix_sessid() ?>';
    var carAddAjaxUrl = '/local/components/otus/service.center.garage/ajax.php?sessid=<?= bitrix_sessid() ?>';
    var contactId = <?= (int) $arResult['CONTACT_ID'] ?>;
    var carsData = <?= json_encode($arResult['CARS_DATA'] ?? [], JSON_UNESCAPED_UNICODE) ?>;

    function centerPopup(popup) {
        var el = popup && popup.popupContainer;

        if (!el) {
            return;
        }

        var w = el.offsetWidth;
        var h = el.offsetHeight;

        el.style.position = 'fixed';
        el.style.left = Math.max(10, Math.round((window.innerWidth - w) / 2)) + 'px';
        el.style.top = Math.max(10, Math.round((window.innerHeight - h) / 2)) + 'px';
    }

    function buildCarForm(data) {
        data = data || {};

        return BX.create('DIV', {
            style: {padding: '15px', minWidth: '400px'},
            children: [
                BX.create('DIV', {
                    style: {marginBottom: '10px'},
                    children: [
                        BX.create('LABEL', {text: BX.message('OTUS_SC_GARAGE_BRAND') + ':'}),
                        BX.create('INPUT', {
                            attrs: {type: 'text', name: 'BRAND', value: data.BRAND || ''},
                            style: {width: '100%', marginTop: '4px', padding: '6px'}
                        })
                    ]
                }),
                BX.create('DIV', {
                    style: {marginBottom: '10px'},
                    children: [
                        BX.create('LABEL', {text: BX.message('OTUS_SC_GARAGE_MODEL') + ':'}),
                        BX.create('INPUT', {
                            attrs: {type: 'text', name: 'MODEL', value: data.MODEL || ''},
                            style: {width: '100%', marginTop: '4px', padding: '6px'}
                        })
                    ]
                }),
                BX.create('DIV', {
                    style: {marginBottom: '10px'},
                    children: [
                        BX.create('LABEL', {text: BX.message('OTUS_SC_GARAGE_NUMBER') + ':'}),
                        BX.create('INPUT', {
                            attrs: {type: 'text', name: 'NUMBER', value: data.NUMBER || ''},
                            style: {width: '100%', marginTop: '4px', padding: '6px'}
                        })
                    ]
                }),
                BX.create('DIV', {
                    style: {marginBottom: '10px'},
                    children: [
                        BX.create('LABEL', {text: BX.message('OTUS_SC_GARAGE_YEAR') + ':'}),
                        BX.create('INPUT', {
                            attrs: {type: 'number', name: 'YEAR', value: data.YEAR || new Date().getFullYear()},
                            style: {width: '100%', marginTop: '4px', padding: '6px'}
                        })
                    ]
                }),
                BX.create('DIV', {
                    style: {marginBottom: '10px'},
                    children: [
                        BX.create('LABEL', {text: BX.message('OTUS_SC_GARAGE_COLOR') + ':'}),
                        BX.create('INPUT', {
                            attrs: {type: 'text', name: 'COLOR', value: data.COLOR || ''},
                            style: {width: '100%', marginTop: '4px', padding: '6px'}
                        })
                    ]
                }),
                BX.create('DIV', {
                    style: {marginBottom: '10px'},
                    children: [
                        BX.create('LABEL', {text: BX.message('OTUS_SC_GARAGE_MILEAGE') + ':'}),
                        BX.create('INPUT', {
                            attrs: {type: 'number', name: 'MILEAGE', value: data.MILEAGE || 0},
                            style: {width: '100%', marginTop: '4px', padding: '6px'}
                        })
                    ]
                })
            ]
        });
    }

    function collectForm(form) {
        var data = {};
        var inputs = form.querySelectorAll('input');

        for (var i = 0; i < inputs.length; i++) {
            data[inputs[i].name] = inputs[i].value;
        }

        return data;
    }

    function submitCarForm(form, carId, onSuccess) {
        var data = collectForm(form);

        data.action = carId > 0 ? 'car_update' : 'car_add';
        data.CLIENT_ID = contactId;

        if (carId > 0) {
            data.ID = carId;
        }

        BX.ajax({
            url: carAddAjaxUrl,
            method: 'POST',
            dataType: 'json',
            data: data,
            onsuccess: function (response) {
                if (response && response.ok) {
                    onSuccess && onSuccess(response);
                } else {
                    alert(BX.message('OTUS_SC_GARAGE_SAVE_ERROR') + ': '
                        + (((response && response.errors) || []).join(', ') || 'пустой ответ сервера'));
                }
            },
            onfailure: function (data) {
                alert(BX.message('OTUS_SC_GARAGE_SAVE_ERROR') + ': '
                    + String(data || '').slice(0, 300));
            }
        });
    }

    window.otusScAddCar = function () {
        var form = buildCarForm();

        var popup = new BX.PopupWindow('otus_sc_add_car', null, {
            titleBar: BX.message('OTUS_SC_GARAGE_ADD_TITLE'),
            content: form,
            autoHide: false,
            closeByEsc: true,
            zIndex: 1200,
            buttons: [
                new BX.PopupWindowButton({
                    text: BX.message('OTUS_SC_GARAGE_SAVE'),
                    className: 'ui-btn ui-btn-primary',
                    events: {
                        click: function () {
                            submitCarForm(form, 0, function () {
                                popup.close();
                                location.reload();
                            });
                        }
                    }
                }),
                new BX.PopupWindowButton({
                    text: BX.message('OTUS_SC_GARAGE_CANCEL'),
                    className: 'ui-btn ui-btn-link',
                    events: {click: function () { popup.close(); }}
                })
            ]
        });

        popup.show();
        centerPopup(popup);
    };

    window.otusScEditCar = function (carId) {
        var car = carsData[carId];

        if (!car) {
            return;
        }

        var form = buildCarForm(car);

        var popup = new BX.PopupWindow('otus_sc_edit_car_' + carId, null, {
            titleBar: BX.message('OTUS_SC_GARAGE_EDIT_TITLE'),
            content: form,
            autoHide: false,
            closeByEsc: true,
            zIndex: 1200,
            buttons: [
                new BX.PopupWindowButton({
                    text: BX.message('OTUS_SC_GARAGE_SAVE'),
                    className: 'ui-btn ui-btn-primary',
                    events: {
                        click: function () {
                            submitCarForm(form, carId, function () {
                                popup.close();
                                location.reload();
                            });
                        }
                    }
                }),
                new BX.PopupWindowButton({
                    text: BX.message('OTUS_SC_GARAGE_CANCEL'),
                    className: 'ui-btn ui-btn-link',
                    events: {click: function () { popup.close(); }}
                })
            ]
        });

        popup.show();
        centerPopup(popup);
    };

    window.otusScDeleteCar = function (carId) {
        if (!confirm(BX.message('OTUS_SC_GARAGE_DELETE_CONFIRM'))) {
            return;
        }

        BX.ajax({
            url: carAddAjaxUrl,
            method: 'POST',
            dataType: 'json',
            data: {action: 'car_delete', ID: carId},
            onsuccess: function (response) {
                if (response && response.ok) {
                    location.reload();
                } else {
                    alert(BX.message('OTUS_SC_GARAGE_DELETE_ERROR') + ': '
                        + (((response && response.errors) || []).join(', ') || 'пустой ответ сервера'));
                }
            },
            onfailure: function (data) {
                alert(BX.message('OTUS_SC_GARAGE_DELETE_ERROR') + ': '
                    + String(data || '').slice(0, 300));
            }
        });
    };

    BX.bindDelegate(document, 'click', {className: 'otus-sc-history-link'}, function (event) {
        event.preventDefault();

        var link = event.target.closest('.otus-sc-history-link');

        if (!link) {
            return;
        }

        var carId = parseInt(link.getAttribute('data-car-id'), 10);
        var carTitle = link.getAttribute('data-car-title') || '';

        if (currentPopup) {
            currentPopup.destroy();
            currentPopup = null;
        }

        currentPopup = new BX.PopupWindow('otus_sc_history_popup', null, {
            lightShadow: true,
            autoHide: false,
            closeByEsc: true,
            zIndex: 1200,
            titleBar: carTitle,
            content: BX.create('DIV', {
                props: {className: 'otus-sc-history-content'},
                text: BX.message('OTUS_SC_GARAGE_LOADING'),
            }),
            buttons: [
                new BX.PopupWindowButton({
                    text: BX.message('OTUS_SC_GARAGE_CLOSE'),
                    className: 'ui-btn ui-btn-primary',
                    events: {
                        click: function () {
                            if (currentPopup) {
                                currentPopup.close();
                            }
                        },
                    },
                }),
            ],
            events: {
                onAfterPopupClose: function () {
                    currentPopup = null;
                },
            },
        });

        currentPopup.show();
        centerPopup(currentPopup);

        BX.ajax({
            url: historyAjaxUrl,
            method: 'POST',
            dataType: 'html',
            data: {car_id: carId},
            onsuccess: function (html) {
                if (currentPopup) {
                    currentPopup.setContent(BX.create('DIV', {
                        props: {className: 'otus-sc-history-content'},
                        html: html,
                    }));

                    centerPopup(currentPopup);
                }
            },
            onfailure: function () {
                if (currentPopup) {
                    currentPopup.setContent(BX.create('DIV', {
                        props: {className: 'otus-sc-history-content'},
                        text: BX.message('OTUS_SC_GARAGE_LOAD_ERROR'),
                    }));
                }
            },
        });
    });
});
</script>