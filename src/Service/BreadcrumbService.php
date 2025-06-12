<?php

namespace App\Service;

class BreadcrumbService
{
    protected static $crumbs = [];

    public static function all(): array
    {
        return array_unique(self::$crumbs, SORT_REGULAR);
    }

    public static function add(array ...$crumbs): void
    {
        foreach ($crumbs as $crumb) {
            $data = [];

            if (isset($crumb['label'])) {
                $data['label'] = $crumb['label'];
            }

            if (isset($crumb['href'])) {
                $data['href'] = $crumb['href'];
            }

            if (isset($crumb['id'])) {
                $data['id'] = $crumb['id'];
            }

            if ($data) {
                self::$crumbs[] = $data;
            }
        }
    }
}
