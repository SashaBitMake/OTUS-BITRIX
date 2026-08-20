CREATE TABLE IF NOT EXISTS `otus_sc_garage` (
    `ID` INT(11) NOT NULL AUTO_INCREMENT,
    `CONTACT_ID` INT(11) NOT NULL,
    `BRAND` VARCHAR(255) NOT NULL,
    `MODEL` VARCHAR(255) NOT NULL,
    `NUMBER` VARCHAR(20) NOT NULL,
    `YEAR` INT(4) NOT NULL,
    `COLOR` VARCHAR(50) NULL,
    `MILEAGE` INT(11) NULL,
    PRIMARY KEY (`ID`),
    INDEX `CONTACT_ID` (`CONTACT_ID`),
    INDEX `NUMBER` (`NUMBER`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


INSERT INTO `otus_sc_garage` (`CONTACT_ID`, `BRAND`, `MODEL`, `NUMBER`, `YEAR`, `COLOR`, `MILEAGE`)
SELECT COALESCE((SELECT MIN(`ID`) FROM `b_crm_contact`), 1), 'Toyota', 'Camry', 'A123BC777', 2021, 'Black', 45000
WHERE NOT EXISTS (SELECT 1 FROM `otus_sc_garage`);

-- Заявки на закупку: позиции (1:N к шапке заявки otus_sc_purchase_request).
CREATE TABLE IF NOT EXISTS `otus_sc_purchase_request_items` (
    `ID` int NOT NULL AUTO_INCREMENT,
    `REQUEST_ID` int NOT NULL,
    `PRODUCT_ID` int NOT NULL,
    `QUANTITY` int NOT NULL DEFAULT 0,
    PRIMARY KEY (`ID`),
    KEY `IX_OTUS_SC_PRI_REQUEST` (`REQUEST_ID`),
    KEY `IX_OTUS_SC_PRI_PRODUCT` (`PRODUCT_ID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- Заявки на закупку: шапка (история). Позиции — otus_sc_purchase_request_items.
CREATE TABLE IF NOT EXISTS `otus_sc_purchase_request` (
    `ID` int NOT NULL AUTO_INCREMENT,
    `TITLE` varchar(255) NOT NULL DEFAULT '',
    `STATUS` varchar(20) NOT NULL DEFAULT 'NEW',
    `AUTHOR_ID` int NOT NULL DEFAULT 0,
    `PROCESSED_BY_ID` int NOT NULL DEFAULT 0,
    `PROCESSED_AT` datetime DEFAULT NULL,
    `REJECT_REASON` text,
    `IS_AUTOMATIC` char(1) NOT NULL DEFAULT 'N',
    `CREATED_AT` datetime DEFAULT NULL,
    PRIMARY KEY (`ID`),
    KEY `IX_OTUS_SC_PR_STATUS` (`STATUS`),
    KEY `IX_OTUS_SC_PR_AUTHOR` (`AUTHOR_ID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;