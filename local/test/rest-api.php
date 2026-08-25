<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/header.php';

use Bitrix\Main\UI\Extension;

global $APPLICATION;
$APPLICATION->SetTitle('Тест REST-методов otus.book.*');

Extension::load(['restclient']);
?>
<style>
    .otus-test-wrap { max-width: 760px; }
    .otus-test-block { border: 1px solid #d8dce1; border-radius: 6px; padding: 16px; margin-bottom: 20px; }
    .otus-test-block h3 { margin-top: 0; }
    .otus-test-block label { display: block; margin: 8px 0 4px; font-size: 13px; color: #555; }
    .otus-test-block input[type="text"],
    .otus-test-block input[type="number"] {
        width: 100%; box-sizing: border-box; padding: 6px 8px; margin-bottom: 4px;
    }
    .otus-test-block button {
        margin-top: 10px; padding: 6px 16px; cursor: pointer;
    }
    .otus-test-result {
        background: #1e1e1e; color: #d4d4d4; padding: 10px; border-radius: 4px;
        white-space: pre-wrap; word-break: break-word; font-size: 12px; margin-top: 10px;
        max-height: 300px; overflow: auto;
    }
    .otus-test-result.is-error { color: #ff6b6b; }
</style>

<div class="otus-test-wrap">

    <!-- ADD -->
    <div class="otus-test-block">
        <h3>otus.book.add</h3>
        <form id="OTUS_ADD_FORM">
            <label>TITLE (обязательное)</label>
            <input type="text" name="TITLE" required value="Мастер и Маргарита">

            <label>AUTHOR</label>
            <input type="text" name="AUTHOR" value="Булгаков">

            <label>YEAR</label>
            <input type="number" name="YEAR" value="1967">

            <label>ISBN</label>
            <input type="text" name="ISBN" value="978-5-17-000000-0">

            <button type="submit">Добавить</button>
        </form>
        <div class="otus-test-result" id="OTUS_ADD_RESULT">результат появится здесь</div>
    </div>

    <!-- GET -->
    <div class="otus-test-block">
        <h3>otus.book.get</h3>
        <form id="OTUS_GET_FORM">
            <label>ID</label>
            <input type="number" name="id" required placeholder="например, 1">
            <button type="submit">Получить</button>
        </form>
        <div class="otus-test-result" id="OTUS_GET_RESULT">результат появится здесь</div>
    </div>

    <!-- UPDATE -->
    <div class="otus-test-block">
        <h3>otus.book.update</h3>
        <form id="OTUS_UPDATE_FORM">
            <label>ID (обязательное)</label>
            <input type="number" name="id" required placeholder="например, 1">

            <label>TITLE (оставьте пустым, если не меняете)</label>
            <input type="text" name="TITLE">

            <label>AUTHOR</label>
            <input type="text" name="AUTHOR">

            <label>YEAR</label>
            <input type="number" name="YEAR">

            <label>ISBN</label>
            <input type="text" name="ISBN">

            <button type="submit">Обновить</button>
        </form>
        <div class="otus-test-result" id="OTUS_UPDATE_RESULT">результат появится здесь</div>
    </div>

    <!-- DELETE -->
    <div class="otus-test-block">
        <h3>otus.book.delete</h3>
        <form id="OTUS_DELETE_FORM">
            <label>ID</label>
            <input type="number" name="id" required placeholder="например, 1">
            <button type="submit">Удалить</button>
        </form>
        <div class="otus-test-result" id="OTUS_DELETE_RESULT">результат появится здесь</div>
    </div>

    <!-- LIST -->
    <div class="otus-test-block">
        <h3>otus.book.list</h3>
        <label>limit</label>
        <input type="number" id="OTUS_LIST_LIMIT" value="20">
        <button id="OTUS_LIST_BTN">Получить список</button>
        <div class="otus-test-result" id="OTUS_LIST_RESULT">результат появится здесь</div>
    </div>

</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {

        function showResult(elId, response) {
            var el = document.getElementById(elId);
            if (response.error()) {
                el.classList.add('is-error');
                el.textContent = 'ERROR: ' + JSON.stringify(response.data(), null, 2);
            } else {
                el.classList.remove('is-error');
                el.textContent = JSON.stringify(response.data(), null, 2);
            }
        }

        function formToFields(form, only) {
            var fields = {};
            new FormData(form).forEach(function (value, key) {
                if (value === '') {
                    return;
                }
                fields[key] = value;
            });
            return fields;
        }

        document.getElementById('OTUS_ADD_FORM').addEventListener('submit', function (e) {
            e.preventDefault();
            var fields = formToFields(e.target);
            BX.rest.callMethod('otus.book.add', {BOOK: fields}, function (response) {
                showResult('OTUS_ADD_RESULT', response);
            });
        });

        document.getElementById('OTUS_GET_FORM').addEventListener('submit', function (e) {
            e.preventDefault();
            var id = e.target.querySelector('[name="id"]').value;
            BX.rest.callMethod('otus.book.get', {id: id}, function (response) {
                showResult('OTUS_GET_RESULT', response);
            });
        });

        document.getElementById('OTUS_UPDATE_FORM').addEventListener('submit', function (e) {
            e.preventDefault();
            var id = e.target.querySelector('[name="id"]').value;
            var fields = formToFields(e.target);
            delete fields.id;
            BX.rest.callMethod('otus.book.update', {id: id, FIELDS: fields}, function (response) {
                showResult('OTUS_UPDATE_RESULT', response);
            });
        });

        document.getElementById('OTUS_DELETE_FORM').addEventListener('submit', function (e) {
            e.preventDefault();
            var id = e.target.querySelector('[name="id"]').value;
            if (!confirm('Удалить книгу #' + id + '?')) {
                return;
            }
            BX.rest.callMethod('otus.book.delete', {id: id}, function (response) {
                showResult('OTUS_DELETE_RESULT', response);
            });
        });

        document.getElementById('OTUS_LIST_BTN').addEventListener('click', function () {
            var limit = document.getElementById('OTUS_LIST_LIMIT').value || 20;
            BX.rest.callMethod('otus.book.list', {
                order: {ID: 'DESC'},
                limit: parseInt(limit, 10)
            }, function (response) {
                showResult('OTUS_LIST_RESULT', response);
            });
        });

    });
</script>
<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/footer.php';