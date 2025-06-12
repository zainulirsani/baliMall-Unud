<?php

namespace App\Service;

use App\Helper\StaticHelper;

class DataTableService
{
    private $table;
    private $filters;
    private $headers;
    private $buttons;

    public function __construct(?string $table = null)
    {
        if (!empty($table)) {
            $this->table = StaticHelper::createSlug($table);
        }
    }

    public function getTable(): ?string
    {
        return $this->table;
    }

    public function getFilters()
    {
        return $this->filters;
    }

    public function setFilters(array $filters): void
    {
        $this->filters = $filters;
    }

    public function getHeaders()
    {
        return $this->headers;
    }

    public function setHeaders(array $headers): void
    {
        $this->headers = $headers;
    }

    public function getButtons()
    {
        return $this->buttons;
    }

    public function setButtons(array $buttons): void
    {
        $this->buttons = $buttons;
    }
}
