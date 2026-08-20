/**
 * Кастомная секция "Автомобиль" в форме сделки (только сервисная воронка,
 */
(function () {
    'use strict';

    var AJAX_BASE = '/local/components/otus/service.center.garage/ajax.php';
    var ENDPOINT_CARS     = AJAX_BASE + '?action=cars_list';
    var ENDPOINT_DEAL     = AJAX_BASE + '?action=deal_info';
    var ENDPOINT_CONTACTS = AJAX_BASE + '?action=contacts_list';
    var ENDPOINT_CHECK    = AJAX_BASE + '?action=deal_check';
    var ENDPOINT_ADD      = AJAX_BASE + '?action=car_add';
    var ENDPOINT_PRESAVE  = AJAX_BASE + '?action=deal_presave';

    function ajaxJson(url, callback) {
        BX.ajax({
            url: url, method: 'GET', dataType: 'json',
            onsuccess: function (response) { callback(response); },
            onfailure: function () { callback(null); },
        });
    }

    function findUfInput() {
        return document.querySelector('input[name="UF_CRM_OTUS_SC_CAR"]')
            || document.querySelector('input[id*="UF_CRM_OTUS_SC_CAR"]');
    }

    function findCaption() {
        var nodes = document.querySelectorAll('div,span,label');
        for (var i = 0; i < nodes.length; i++) {
            var el = nodes[i];
            if (el.children.length === 0 && el.textContent.trim() === BX.message('OTUS_SC_CAR_FIELD_CAPTION')) {
                return el;
            }
        }
        return null;
    }

    function getDealId() {
        var match = location.pathname.match(/\/crm\/deal\/details\/(\d+)/);
        return match ? parseInt(match[1], 10) : 0;
    }

    function cleanLabel(label) { return (label || '').replace(/,\s*$/, ''); }

    function hideRawValue(caption, value) {
        if (!value) { return; }
        var scope = caption;
        for (var up = 0; up < 3; up++) {
            if (!scope.parentElement) { break; }
            scope = scope.parentElement;
        }
        var nodes = scope.querySelectorAll('*');
        for (var i = 0; i < nodes.length; i++) {
            var node = nodes[i];
            if (node.children.length === 0
                && node.textContent.trim() === String(value)
                && !node.closest('.otus-sc-car-section')) {
                node.style.display = 'none';
            }
        }
    }

    function showError(title, message) {
        var popup = new BX.PopupWindow('otus_sc_car_error', null, {
            titleBar: title,
            content: BX.create('DIV', {style: {padding: '15px', maxWidth: '420px'}, text: message}),
            autoHide: false, closeByEsc: true, zIndex: 3000,
        });
        popup.setButtons([new BX.PopupWindowButton({
            text: BX.message('OTUS_SC_CAR_ERROR_CLOSE'), className: 'ui-btn ui-btn-primary',
            events: {click: function () { popup.close(); }},
        })]);
        popup.show();
    }

    function escapeHtml(s) {
        var d = document.createElement('div');
        d.textContent = s == null ? '' : String(s);
        return d.innerHTML;
    }

    function showDuplicate(duplicate) {
        var url = '/crm/deal/details/' + duplicate.id + '/';
        var popup = new BX.PopupWindow('otus_sc_car_error', null, {
            titleBar: BX.message('OTUS_SC_CAR_DUPLICATE_TITLE'),
            content: BX.create('DIV', {
                style: {padding: '15px', maxWidth: '420px'},
                html: BX.message('OTUS_SC_CAR_DUPLICATE')
                    .replace('#ID#', '<a href="' + url + '" target="_blank" style="color:#006cc0;">#' + duplicate.id + '</a>')
                    .replace('#TITLE#', escapeHtml(duplicate.title)),
            }),
            autoHide: false,
            closeByEsc: true,
            zIndex: 3000,
        });
        popup.setButtons([
            new BX.PopupWindowButton({
                text: BX.message('OTUS_SC_CAR_ERROR_CLOSE'),
                className: 'ui-btn ui-btn-primary',
                events: {
                    click: function () {
                        popup.close();
                    },
                },
            }),
        ]);
        popup.show();
    }

    function applyTitle(label) {
        var titleInput = document.querySelector('input[name="TITLE"]');
        if (titleInput && label) {
            titleInput.value = label;
            if (typeof BX.fireEvent === 'function') { BX.fireEvent(titleInput, 'change'); }
        }
    }

    function attach() {
        var ctx = window.OTUS_SC_CTX;
        if (!ctx || ctx.service <= 0 || ctx.category !== ctx.service) { return; }

        var caption = findCaption();
        if (!caption) { return; }

        var ufInput = findUfInput();
        var mode = ufInput ? 'edit' : 'view';

        if (caption.getAttribute('data-otus-sc-mode') === mode) { return; }
        caption.setAttribute('data-otus-sc-mode', mode);

        var valueNode = caption.nextElementSibling;
        if (valueNode && valueNode !== ufInput) { valueNode.style.display = 'none'; }
        if (ufInput) { ufInput.style.display = 'none'; }

        var labelStyle = 'font-size:12px;color:#80868d;margin-bottom:4px;';
        var section = document.createElement('div');
        section.className = 'otus-sc-car-section';
        section.style.cssText = 'margin:8px 0;padding:10px;border:1px solid #dfe4ea;border-radius:6px;background:#f8f9fa;';

        var contactLabel = document.createElement('div'); contactLabel.style.cssText = labelStyle; contactLabel.textContent = BX.message('OTUS_SC_CAR_CONTACT_LABEL');
        var contactSelect = document.createElement('select'); contactSelect.style.cssText = 'width:100%;margin-bottom:10px;';
        var carLabel = document.createElement('div'); carLabel.style.cssText = labelStyle; carLabel.textContent = BX.message('OTUS_SC_CAR_FIELD_CAPTION');
        var carSelect = document.createElement('select'); carSelect.style.cssText = 'width:100%;';

        section.appendChild(contactLabel); section.appendChild(contactSelect);
        section.appendChild(carLabel);     section.appendChild(carSelect);

        if (mode === 'view') { contactSelect.disabled = true; carSelect.disabled = true; }
        caption.parentNode.insertBefore(section, caption.nextSibling);

        function mirrorContact(contactId) {
            var input = document.querySelector('input[name="CONTACT_ID"]');
            if (!input) {
                input = document.createElement('input');
                input.type = 'hidden'; input.name = 'CONTACT_ID';
                section.appendChild(input);
            }
            input.value = String(contactId || 0);
            if (typeof BX.fireEvent === 'function') { BX.fireEvent(input, 'change'); }
        }

        function fillOptions(select, items, placeholder) {
            select.innerHTML = '';
            select.add(new Option(placeholder, '0'));
            (items || []).forEach(function (item) { select.add(new Option(item.name, String(item.id))); });
        }

        function loadCars(contactId, selectedCarId) {
            if (!contactId) { fillOptions(carSelect, [], BX.message('OTUS_SC_CAR_NOT_SET')); return; }
            ajaxJson(ENDPOINT_CARS + '&sessid=' + BX.bitrix_sessid() + '&contact_id=' + contactId, function (response) {
                var cars = (response && response.cars)
                    ? response.cars.map(function (car) { return {id: car.id, name: cleanLabel(car.label)}; })
                    : [];
                fillOptions(carSelect, cars, BX.message('OTUS_SC_CAR_NOT_SET'));
                carSelect.value = String(selectedCarId || 0);
            });
        }

        function applyCar(carId) {
            if (ufInput) {
                ufInput.value = String(carId || 0);
                if (typeof BX.fireEvent === 'function') { BX.fireEvent(ufInput, 'change'); }
            }
            hideRawValue(caption, carId);
        }

        function checkDuplicate(carId) {
            if (!carId) { return; }
            ajaxJson(ENDPOINT_CHECK + '&sessid=' + BX.bitrix_sessid() + '&car_id=' + carId + '&deal_id=' + getDealId(),
                function (response) {
                    if (response && response.duplicate) {
                        showDuplicate(response.duplicate);
                        carSelect.value = '0'; applyCar(0);
                    }
                });
        }

        function openAddCarPopup() {
            var contactId = parseInt(contactSelect.value, 10);
            if (!contactId) { showError(BX.message('OTUS_SC_CAR_ERROR_TITLE'), BX.message('OTUS_SC_CAR_NO_CONTACT')); return; }
            var fields = ['BRAND', 'MODEL', 'NUMBER', 'YEAR', 'COLOR', 'MILEAGE'];
            var inputs = {};
            var content = BX.create('DIV', {style: {width: '420px'}});
            fields.forEach(function (name) {
                var row = BX.create('DIV', {style: {marginBottom: '6px'}});
                row.appendChild(BX.create('LABEL', {style: {display: 'inline-block', width: '90px'}, text: BX.message('OTUS_SC_CAR_F_' + name)}));
                inputs[name] = BX.create('INPUT', {attrs: {type: 'text'}, style: {width: '280px'}});
                row.appendChild(inputs[name]); content.appendChild(row);
            });
            var popup = new BX.PopupWindow('otus_sc_car_add', null, {
                titleBar: BX.message('OTUS_SC_CAR_ADD_TITLE'), content: content, closeByEsc: true, zIndex: 3000,
                buttons: [
                    new BX.PopupWindowButton({
                        text: BX.message('OTUS_SC_CAR_ADD_BTN'), className: 'ui-btn ui-btn-success',
                        events: {click: function () {
                            var data = {sessid: BX.bitrix_sessid(), CONTACT_ID: contactId};
                            fields.forEach(function (name) { data[name] = inputs[name].value; });
                            BX.ajax({
                                url: ENDPOINT_ADD + '&sessid=' + BX.bitrix_sessid(),
                                method: 'POST', dataType: 'json', data: data,
                                onsuccess: function (response) {
                                    if (response && response.ok) {
                                        popup.close();
                                        loadCars(contactId, response.car.id);
                                        applyCar(response.car.id);
                                        applyTitle(cleanLabel(response.car.label));
                                    } else {
                                        alert((response && response.errors && response.errors[0]) || 'Ошибка добавления');
                                    }
                                },
                                onfailure: function () { alert('Ошибка добавления'); },
                            });
                        }},
                    }),
                    new BX.PopupWindowButton({
                        text: BX.message('OTUS_SC_CAR_ERROR_CLOSE'), className: 'ui-btn ui-btn-link',
                        events: {click: function () { popup.close(); }},
                    }),
                ],
            });
            popup.show();
        }

        if (mode === 'edit') {
            var btnRow = document.createElement('div'); btnRow.style.cssText = 'margin-top:8px;';
            var btnAddCar = document.createElement('a');
            btnAddCar.href = 'javascript:void(0)';
            btnAddCar.className = 'ui-btn ui-btn-light-btn ui-btn-sm';
            btnAddCar.textContent = '+ ' + BX.message('OTUS_SC_CAR_ADD_BTN_TITLE');
            BX.bind(btnAddCar, 'click', function (event) { event.preventDefault(); openAddCarPopup(); });
            btnRow.appendChild(btnAddCar); section.appendChild(btnRow);
        }

        BX.bind(contactSelect, 'change', function () {
            var contactId = parseInt(contactSelect.value, 10);
            mirrorContact(contactId); loadCars(contactId, 0); applyCar(0);
        });

        BX.bind(carSelect, 'change', function () {
            var carId = parseInt(carSelect.value, 10);
            var selectedLabel = carId > 0 ? carSelect.options[carSelect.selectedIndex].text : '';
            applyCar(carId); applyTitle(selectedLabel); checkDuplicate(carId);
        });

        ajaxJson(ENDPOINT_CONTACTS + '&sessid=' + BX.bitrix_sessid(), function (response) {
            var contacts = (response && response.contacts) ? response.contacts : [];
            fillOptions(contactSelect, contacts, BX.message('OTUS_SC_CAR_CONTACT_NOT_SET'));
            var dealId = getDealId();
            if (dealId > 0) {
                ajaxJson(ENDPOINT_DEAL + '&sessid=' + BX.bitrix_sessid() + '&deal_id=' + dealId, function (data) {
                    var contactId = data ? data.contactId : 0;
                    var carId = data ? data.carId : 0;
                    contactSelect.value = String(contactId || 0);
                    hideRawValue(caption, carId); loadCars(contactId, carId);
                });
            } else {
                var dealContactInput = document.querySelector('input[name="CONTACT_ID"]');
                var contactId = (dealContactInput && dealContactInput.value) ? parseInt(dealContactInput.value, 10) : 0;
                contactSelect.value = String(contactId || 0);
                loadCars(contactId, ufInput ? (parseInt(ufInput.value, 10) || 0) : 0);
            }
        });
    }

    var saveValidated = false;

    function onSaveClick(event) {
        var target = event.target;
        var btn = target && target.closest ? target.closest('button, .ui-btn, input[type="submit"]') : null;
        if (!btn) { return; }
        var text = (btn.textContent || btn.value || '').trim().toLowerCase();
        if (text.indexOf('сохранить') === -1) { return; }

        var section = document.querySelector('.otus-sc-car-section');
        if (!section) { return; }
        if (saveValidated) { saveValidated = false; return; }

        event.preventDefault(); event.stopPropagation(); event.stopImmediatePropagation();

        var selects = section.querySelectorAll('select');
        var contactId = selects[0] ? parseInt(selects[0].value, 10) : 0;
        var carId     = selects[1] ? parseInt(selects[1].value, 10) : 0;

        if (!contactId || !carId) {
            showError(BX.message('OTUS_SC_CAR_ERROR_TITLE'), BX.message('OTUS_SC_CAR_ERROR_REQUIRED'));
            return;
        }

        BX.ajax({
            url: ENDPOINT_PRESAVE + '&sessid=' + BX.bitrix_sessid(),
            method: 'POST', dataType: 'json',
            data: {contact_id: contactId, car_id: carId},
            onsuccess: function () {
                ajaxJson(ENDPOINT_CHECK + '&sessid=' + BX.bitrix_sessid() + '&car_id=' + carId + '&deal_id=' + getDealId(),
                    function (response) {
                        if (response && response.duplicate) { showDuplicate(response.duplicate); return; }
                        saveValidated = true;
                        btn.click();
                    });
            },
            onfailure: function () {
                showError('Ошибка', 'Не удалось сохранить выбор автомобиля.');
            }
        });
    }

    function bindSaveIntercept(doc) {
        try { doc.addEventListener('click', onSaveClick, true); } catch (e) {}
    }

    BX.ready(function () {
        attach();
        bindSaveIntercept(document);
        if (window.parent && window.parent.document && window.parent.document !== document) { bindSaveIntercept(window.parent.document); }
        if (window.top && window.top.document && window.top.document !== document
            && (!window.parent || window.top.document !== window.parent.document)) { bindSaveIntercept(window.top.document); }
        if (typeof MutationObserver !== 'undefined') {
            new MutationObserver(function () { attach(); }).observe(document.body, {childList: true, subtree: true});
        } else {
            setInterval(attach, 700);
        }
    });
})();