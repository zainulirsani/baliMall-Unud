<?php

namespace App\Entity;

use Doctrine\ORM\PersistentCollection;

class BaseEntity
{
    private $id;
    private $slug;

    public function getId()
    {
        return $this->id;
    }

    public function getSlug()
    {
        return $this->slug;
    }

    public function setSlug(string $slug): void
    {
        $this->slug = $slug;
    }

    public function getSlugCheck(): bool
    {
        return false;
    }

    public function toArray(): array
    {
        $data = [];
        $objects = (array) $this;

        foreach ($objects as $key => $object) {
            $class = get_class($this);

            if (!$object instanceof PersistentCollection && strpos($key, $class) !== false) {
                $search = sprintf("\x00%s\x00", $class);
                $index = str_replace($search, '', $key);

                $data[$index] = $object;
            }
        }

        return $data;
    }
}
