<?php

namespace App\Service;

use App\Helper\StaticHelper;

class DataPackageService
{
    private $name;
    private $ableToCreate;
    private $ableToView;
    private $ableToUpdate;
    private $ableToDelete;
    private $ableToImport;
    private $ableToExport;

    public function __construct(string $name = 'default')
    {
        $this->name = $name;
        $this->ableToCreate = true;
        $this->ableToView = true;
        $this->ableToUpdate = true;
        $this->ableToDelete = true;
        $this->ableToImport = false;
        $this->ableToExport = false;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getAbleToCreate(): bool
    {
        return $this->ableToCreate;
    }

    public function setAbleToCreate(bool $ableToCreate): void
    {
        $this->ableToCreate = $ableToCreate;
    }

    public function getAbleToView(): bool
    {
        return $this->ableToView;
    }

    public function setAbleToView(bool $ableToView): void
    {
        $this->ableToView = $ableToView;
    }

    public function getAbleToUpdate(): bool
    {
        return $this->ableToUpdate;
    }

    public function setAbleToUpdate(bool $ableToUpdate): void
    {
        $this->ableToUpdate = $ableToUpdate;
    }

    public function getAbleToDelete(): bool
    {
        return $this->ableToDelete;
    }

    public function setAbleToDelete(bool $ableToDelete): void
    {
        $this->ableToDelete = $ableToDelete;
    }

    public function getAbleToImport(): bool
    {
        return $this->ableToImport;
    }

    public function setAbleToImport(bool $ableToImport): void
    {
        $this->ableToImport = $ableToImport;
    }

    public function getAbleToExport(): bool
    {
        return $this->ableToExport;
    }

    public function setAbleToExport(bool $ableToExport): void
    {
        $this->ableToExport = $ableToExport;
    }

    public function getPackageInformation(): array
    {
        $name = ucwords($this->getName());
        $key = StaticHelper::createSlug($name);

        return [
            $key => [
                'name' => $name,
                'create' => $this->getAbleToCreate(),
                'view' => $this->getAbleToView(),
                'update' => $this->getAbleToUpdate(),
                'delete' => $this->getAbleToDelete(),
                'import' => $this->getAbleToImport(),
                'export' => $this->getAbleToExport(),
            ],
        ];
    }
}
