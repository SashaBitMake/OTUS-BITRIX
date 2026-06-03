/**
 * Скрипт кастомизации интерфейса модуля Учета Рабочего Времени.
 * Перехватывает асинхронные запросы Битрикс24 и выводит модальное окно подтверждения.
 *
 */
(function() {
    'use strict';

    function initTimemanInterceptor() {
        if (typeof BX === 'undefined' || !BX.ajax || BX.ajax.isStrictTimeManHooked) {
            return;
        }

        let originalAjax = BX.ajax;

        // Перехватываем корневой метод AJAX-запросов Битрикса
        BX.ajax = function(config) {
            if (config && config.url) {
                let url = config.url;
                const urlLower = url.toLowerCase();

                if (urlLower.indexOf('timeman.php') !== -1) {
                    
                    // 1. Фильтруем признак ВОЗОБНОВЛЕНИЯ 
                    let isReopen = urlLower.indexOf('action=reopen') !== -1 || 
                                   urlLower.indexOf('action=resume') !== -1;
                    
                    if (config.data) {
                        if (typeof config.data === 'string') {
                            let dataLower = config.data.toLowerCase();
                            if (dataLower.includes('action=reopen') || dataLower.includes('action=resume')) {
                                isReopen = true;
                            }
                        } else if (typeof config.data === 'object') {
                            if (config.data.action === 'reopen' || config.data.ACTION === 'REOPEN' ||
                                config.data.action === 'resume' || config.data.ACTION === 'RESUME') {
                                isReopen = true;
                            }
                        }
                    }

                    // 2. Фильтруем признак НАЧАЛА НОВОГО рабочего дня
                    let isOpenNewDay = false;
                    if (!isReopen) {
                        isOpenNewDay = urlLower.indexOf('action=open') !== -1 || urlLower.indexOf('action=start') !== -1;
                        
                        if (config.data && typeof config.data === 'object') {
                            if (config.data.action === 'open' || config.data.ACTION === 'OPEN' ||
                                config.data.action === 'start' || config.data.ACTION === 'START') {
                                isOpenNewDay = true;
                            }
                        } else if (config.data && typeof config.data === 'string') {
                            let dataLower = config.data.toLowerCase();
                            if (dataLower.includes('action=open') || dataLower.includes('action=start')) {
                                isOpenNewDay = true;
                            }
                        }
                    }

                    // 3. Обработка вызова окна
                    if (isOpenNewDay || isReopen) {

                        let popupTitle = isReopen ? BX.message('TIMEMAN_CONFIRM_REOPEN_TITLE') : BX.message('TIMEMAN_CONFIRM_START_TITLE');
                        let popupText = isReopen ? BX.message('TIMEMAN_CONFIRM_REOPEN_TEXT') : BX.message('TIMEMAN_CONFIRM_START_TEXT');

                        return new Promise(function(resolve, reject) {
                            
                            showConfirmPopup(
                                popupTitle,
                                popupText,
                                function() {
                                    let originalSuccess = config.onsuccess;
                                    config.onsuccess = function(response) {
                                        if (typeof originalSuccess === 'function') originalSuccess(response);
                                        resolve(response);
                                    };
                                    originalAjax.call(BX.ajax, config);
                                }, 
                                function() {
                                    config.url = url.replace(/action=open/i, 'action=status')
                                                    .replace(/action=start/i, 'action=status')
                                                    .replace(/action=reopen/i, 'action=status');

                                    if (config.data) {
                                        if (typeof config.data === 'string') {
                                            config.data = config.data.replace(/action=open/i, 'action=status')
                                                                     .replace(/action=start/i, 'action=status')
                                                                     .replace(/action=reopen/i, 'action=status');
                                        } else if (typeof config.data === 'object') {
                                            if (config.data.action) config.data.action = 'status';
                                            if (config.data.ACTION) config.data.ACTION = 'status';
                                        }
                                    }

                                    let originalSuccess = config.onsuccess;
                                    config.onsuccess = function(response) {
                                        if (typeof originalSuccess === 'function') originalSuccess(response);
                                        resolve(response);

                                        setTimeout(function() {
                                            document.querySelectorAll('.ui-btn-wait, .bx-tm-loading, .tm-btn-load').forEach(function(el) {
                                                el.classList.remove('ui-btn-wait', 'bx-tm-loading', 'tm-btn-load');
                                                if (el.style) el.style.background = '';
                                            });

                                            if (typeof BX.Main !== 'undefined' && BX.Main.filterManager) {
                                                const filterId = Object.keys(BX.Main.filterManager.data)[0];
                                                if (filterId) {
                                                    let currentFilter = BX.Main.filterManager.getById(filterId);
                                                    if (currentFilter && typeof currentFilter.onCustomEntityBlur === 'function') {
                                                        currentFilter.onCustomEntityBlur();
                                                    }
                                                }
                                            }

                                            if (typeof BX.TimeMan !== 'undefined') {
                                                if (typeof BX.TimeMan.show === 'function') {
                                                    BX.TimeMan.show();
                                                } else if (BX.TimeMan.Grid && typeof BX.TimeMan.Grid.openMenu === 'function') {
                                                    BX.TimeMan.Grid.openMenu();
                                                } else {
                                                    const openBtn = document.querySelector('.timeman-container, #bx_top_panel_timeman_container');
                                                    if (openBtn) openBtn.click();
                                                }
                                            }

                                            if (typeof BX.onCustomEvent === 'function') {
                                                BX.onCustomEvent('TimeManMenuLayout');
                                                BX.onCustomEvent('onTimeManDataNeedUpdate');
                                            }
                                        }, 10);
                                    };

                                    originalAjax.call(BX.ajax, config);
                                }
                            );
                        });
                    }
                }
            }

            return originalAjax.apply(BX.ajax, arguments);
        };

        for (let prop in originalAjax) {
            if (originalAjax.hasOwnProperty(prop) && prop !== 'isStrictTimeManHooked') {
                BX.ajax[prop] = originalAjax[prop];
            }
        }

        BX.ajax.isStrictTimeManHooked = true;
    }

    /**
     * Создает и отображает штатное модальное окно Битрикса (BX.PopupWindow).
     *
     * @param {string} title Заголовок окна
     * @param {string} htmlContent HTML-содержимое окна
     * @param {function} onConfirmCallback Функция при согласии пользователя
     * @param {function} onCancelCallback Функция при отмене/закрытии окна
     */
    function showConfirmPopup(title, htmlContent, onConfirmCallback, onCancelCallback) {
        const popupId = "timeman_strict_confirm_popup";
        let existingPopup = BX.PopupWindowManager.create(popupId);
        if (existingPopup) { 
            existingPopup.destroy(); 
        }

        let popup = BX.PopupWindowManager.create(popupId, null, {
            titleBar: title,
            content: '<div style="padding: 25px 15px; font-size: 15px; text-align: center; color: #333;">' + htmlContent + '</div>',
            autoHide: false,
            lightShadow: true,
            closeByEsc: false,
            overlay: { backgroundColor: '#222', opacity: '50' },
            buttons: [
                new BX.PopupWindowButton({
                    text: BX.message('TIMEMAN_CONFIRM_BTN_CONTINUE'),
                    className: "ui-btn ui-btn-success ui-btn-md", 
                    events: { 
                        click: function() { 
                            popup.close(); 
                            onConfirmCallback(); 
                        } 
                    }
                }),
                new BX.PopupWindowButton({
                    text: BX.message('TIMEMAN_CONFIRM_BTN_CANCEL'),
                    className: "ui-btn ui-btn-link ui-btn-md",
                    events: { 
                        click: function() { 
                            popup.close(); 
                            onCancelCallback(); 
                        } 
                    }
                })
            ]
        });
        popup.show();
    }

    initTimemanInterceptor();
    if (typeof BX !== 'undefined') {
        BX.ready(initTimemanInterceptor);
    }
})();