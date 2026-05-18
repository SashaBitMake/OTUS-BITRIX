CREATE TABLE IF NOT EXISTS otus_tab_crm_data (
    ID INT NOT NULL AUTO_INCREMENT,
    ENTITY_ID INT NOT NULL,
    NAME VARCHAR(255) NOT NULL,
    VALUE TEXT,
    PRIMARY KEY (ID)
);

-- ENTITY_ID = 1
INSERT INTO otus_tab_crm_data (ENTITY_ID, NAME, VALUE) VALUES (1, 'Клиент', 'Иванов Петр, тел. +7(901)123-45-67');
INSERT INTO otus_tab_crm_data (ENTITY_ID, NAME, VALUE) VALUES (1, 'Сделка', 'Сумма: 250 000 руб., дата создания: 2025-05-10');

-- ENTITY_ID = 2
INSERT INTO otus_tab_crm_data (ENTITY_ID, NAME, VALUE) VALUES (2, 'Клиент', 'ООО "Ромашка", ИНН 1234567890');
INSERT INTO otus_tab_crm_data (ENTITY_ID, NAME, VALUE) VALUES (2, 'Время', 'Последний контакт: 2025-05-14 14:30:00');
INSERT INTO otus_tab_crm_data (ENTITY_ID, NAME, VALUE) VALUES (2, 'Статус', 'Сделка в работе, менеджер: Анна Смирнова');

-- ENTITY_ID = 3
INSERT INTO otus_tab_crm_data (ENTITY_ID, NAME, VALUE) VALUES (3, 'Заметка', 'Клиент из Москвы, предпочитает email: client3@example.com');

-- ENTITY_ID = 4
INSERT INTO otus_tab_crm_data (ENTITY_ID, NAME, VALUE) VALUES (4, 'Событие', 'Встреча назначена на 2025-05-20 в 11:00');
INSERT INTO otus_tab_crm_data (ENTITY_ID, NAME, VALUE) VALUES (4, 'Финансы', 'Аванс 50%, остаток 120 000 руб. до 01.06.2025');

-- ENTITY_ID = 5
INSERT INTO otus_tab_crm_data (ENTITY_ID, NAME, VALUE) VALUES (5, 'Клиент', 'Сидорова Елена, дата рождения 1988-03-12');
INSERT INTO otus_tab_crm_data (ENTITY_ID, NAME, VALUE) VALUES (5, 'История', 'Звонок 2025-05-01, отправлено КП от 2025-05-05');
INSERT INTO otus_tab_crm_data (ENTITY_ID, NAME, VALUE) VALUES (5, 'Система', 'Последний вход в личный кабинет: 2025-05-13 19:22');

-- ENTITY_ID = 6
INSERT INTO otus_tab_crm_data (ENTITY_ID, NAME, VALUE) VALUES (6, 'Срок', 'Дедлайн: 2025-05-25, приоритет высокий');

-- ENTITY_ID = 7
INSERT INTO otus_tab_crm_data (ENTITY_ID, NAME, VALUE) VALUES (7, 'Клиент', 'ИП Петров, юр. адрес: г. Санкт-Петербург');
INSERT INTO otus_tab_crm_data (ENTITY_ID, NAME, VALUE) VALUES (7, 'Дата', 'Счёт выставлен 2025-05-12, оплата до 2025-05-26');

-- ENTITY_ID = 8
INSERT INTO otus_tab_crm_data (ENTITY_ID, NAME, VALUE) VALUES (8, 'Комментарий', 'Клиент хочет скидку 10%, обсуждаем');
INSERT INTO otus_tab_crm_data (ENTITY_ID, NAME, VALUE) VALUES (8, 'Время', 'Следующее касание: 2025-05-18 09:00');
INSERT INTO otus_tab_crm_data (ENTITY_ID, NAME, VALUE) VALUES (8, 'Проект', 'Название проекта: Автоматизация склада, бюджет 2 млн руб.');

-- ENTITY_ID = 9
INSERT INTO otus_tab_crm_data (ENTITY_ID, NAME, VALUE) VALUES (9, 'Документ', 'Договор № 45/2025 от 2025-05-01, подписан обеими сторонами');

-- ENTITY_ID = 10
INSERT INTO otus_tab_crm_data (ENTITY_ID, NAME, VALUE) VALUES (10, 'Клиент', 'Технический директор: Кузнецов А.А., Skype: a.kuznetsov');
INSERT INTO otus_tab_crm_data (ENTITY_ID, NAME, VALUE) VALUES (10, 'Календарь', 'Демо-звонок запланирован на 2025-05-22 15:00 МСК');

-- ENTITY_ID = 11
INSERT INTO otus_tab_crm_data (ENTITY_ID, NAME, VALUE) VALUES (11, 'Лид', 'Источник: реклама в TG, заявка от 2025-05-09 12:45');
INSERT INTO otus_tab_crm_data (ENTITY_ID, NAME, VALUE) VALUES (11, 'Статус', 'Квалификация пройдена, передан в отдел продаж');
INSERT INTO otus_tab_crm_data (ENTITY_ID, NAME, VALUE) VALUES (11, 'Сумма', 'Потенциальная сделка: 870 000 руб., вероятность 70%');

-- ENTITY_ID = 12
INSERT INTO otus_tab_crm_data (ENTITY_ID, NAME, VALUE) VALUES (12, 'Поддержка', 'Обращение № 5678, тема: проблема с доступом, решено 2025-05-14');

-- ENTITY_ID = 13
INSERT INTO otus_tab_crm_data (ENTITY_ID, NAME, VALUE) VALUES (13, 'Партнёр', 'Компания "ТехноСервис", контакт: +7(495) 555-12-34');
INSERT INTO otus_tab_crm_data (ENTITY_ID, NAME, VALUE) VALUES (13, 'Дата', 'Совместный вебинар проведён 2025-05-07, запись разослана');