<?php

namespace Otus\Service;

use Bitrix\Main\Error;
use Bitrix\Main\Result;
use Bitrix\Main\Type\DateTime;
use Otus\Orm\BookTable;


class BookService
{
    private const ALLOWED_UPDATE_FIELDS = ['TITLE', 'AUTHOR', 'YEAR', 'ISBN'];
    private const MAX_LIST_LIMIT = 50;

    public function add(array $fields, int $userId): Result
    {
        $result = new Result();

        $title = trim((string)($fields['TITLE'] ?? ''));

        if ($title === '') {
            $result->addError(new Error('Field TITLE is required', 'VALIDATION_ERROR'));
            return $result;
        }

        if (mb_strlen($title) > 255) {
            $result->addError(new Error('Field TITLE is too long (max 255)', 'VALIDATION_ERROR'));
            return $result;
        }

        if ($userId <= 0) {
            $result->addError(new Error('Unable to determine current user', 'ACCESS_DENIED'));
            return $result;
        }

        $data = [
            'TITLE' => $title,
            'AUTHOR' => isset($fields['AUTHOR']) ? (string)$fields['AUTHOR'] : null,
            'YEAR' => isset($fields['YEAR']) ? (int)$fields['YEAR'] : null,
            'ISBN' => isset($fields['ISBN']) ? (string)$fields['ISBN'] : null,
            'CREATED_BY' => $userId,
            'DATE_CREATE' => new DateTime(),
            'DATE_UPDATE' => new DateTime(),
        ];

        $addResult = BookTable::add($data);

        if (!$addResult->isSuccess()) {
            foreach ($addResult->getErrors() as $error) {
                $result->addError($error);
            }
            return $result;
        }

        $result->setData(['ID' => $addResult->getId()]);

        return $result;
    }

    public function update(int $id, array $fields, int $userId): Result
    {
        $result = new Result();

        $book = $this->getById($id);
        if (!$book) {
            $result->addError(new Error('Book not found', 'NOT_FOUND'));
            return $result;
        }

        if (!$this->canModify($book, $userId)) {
            $result->addError(new Error('Access denied', 'ACCESS_DENIED'));
            return $result;
        }

        $data = [];
        foreach (self::ALLOWED_UPDATE_FIELDS as $field) {
            if (array_key_exists($field, $fields)) {
                $data[$field] = $fields[$field];
            }
        }

        if (empty($data)) {
            $result->addError(new Error('Nothing to update', 'VALIDATION_ERROR'));
            return $result;
        }

        if (array_key_exists('TITLE', $data)) {
            $data['TITLE'] = trim((string)$data['TITLE']);
            if ($data['TITLE'] === '') {
                $result->addError(new Error('Field TITLE cannot be empty', 'VALIDATION_ERROR'));
                return $result;
            }
        }

        if (array_key_exists('YEAR', $data)) {
            $data['YEAR'] = (int)$data['YEAR'];
        }

        $data['DATE_UPDATE'] = new DateTime();

        $updateResult = BookTable::update($id, $data);

        if (!$updateResult->isSuccess()) {
            foreach ($updateResult->getErrors() as $error) {
                $result->addError($error);
            }
            return $result;
        }

        $result->setData(['ID' => $id]);

        return $result;
    }

    public function delete(int $id, int $userId): Result
    {
        $result = new Result();

        $book = $this->getById($id);
        if (!$book) {
            $result->addError(new Error('Book not found', 'NOT_FOUND'));
            return $result;
        }

        if (!$this->canModify($book, $userId)) {
            $result->addError(new Error('Access denied', 'ACCESS_DENIED'));
            return $result;
        }

        if (!$deleteResult->isSuccess()) {
            foreach ($deleteResult->getErrors() as $error) {
                $result->addError($error);
            }
            return $result;
        }

        $result->setData(['ID' => $id]);

        return $result;
    }

    public function get(int $id, int $userId): ?array
    {
        $book = $this->getById($id);

        if (!$book || !$this->canView($book, $userId)) {
            return null;
        }

        return $book;
    }

    /**
     * @return array{items: array, count: int}
     */
    public function getList(array $filter, array $order, array $select, int $limit, int $offset, int $userId): array
    {
        $limit = $limit > 0 ? min($limit, self::MAX_LIST_LIMIT) : self::MAX_LIST_LIMIT;
        $offset = max($offset, 0);

        if (empty($select)) {
            $select = ['ID', 'TITLE', 'AUTHOR', 'YEAR', 'ISBN', 'CREATED_BY', 'DATE_CREATE', 'DATE_UPDATE'];
        }

        if (empty($order)) {
            $order = ['ID' => 'DESC'];
        }

        if (!$this->isAdmin() && !array_key_exists('CREATED_BY', $filter) && !array_key_exists('=CREATED_BY', $filter)) {
            $filter['=CREATED_BY'] = $userId;
        }

        $items = BookTable::getList([
            'filter' => $filter,
            'order' => $order,
            'select' => $select,
            'limit' => $limit,
            'offset' => $offset,
        ])->fetchAll();

        return [
            'items' => $items,
            'count' => count($items),
        ];
    }

    private function getById(int $id): ?array
    {
        return BookTable::getList([
            'filter' => ['=ID' => $id],
            'limit' => 1,
        ])->fetch() ?: null;
    }

    private function canModify(array $book, int $userId): bool
    {
        return $this->isAdmin() || (int)$book['CREATED_BY'] === $userId;
    }

    private function canView(array $book, int $userId): bool
    {
        return $this->isAdmin() || (int)$book['CREATED_BY'] === $userId;
    }

    private function isAdmin(): bool
    {
        global $USER;

        return $USER instanceof \CUser && $USER->IsAdmin();
    }
}
