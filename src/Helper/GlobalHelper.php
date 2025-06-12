<?php

if (!function_exists('convertToFloat')) {
    function convertToFloat($number) {
        return (float) str_replace(',', '.', $number);
    }
}

if (!function_exists('processChildrenCategoryLevel1')) {
    function processChildrenCategoryLevel1(&$categories, array $category, int $id) {
        if (!empty($category['lvl1_id']) && (int) $category['lvl1_parentId'] === $id) {
            $parentId = (int) $category['lvl1_id'];
            $categories[$id]['children'][$parentId] = [
                'id' => $parentId,
                'text' => $category['lvl1_text'],
                'icon' => $category['lvl1_icon'],
                'class' => $category['lvl1_class'],
                'status' => $category['lvl1_status'],
                'children' => [],
            ];
        }
    }
}

if (!function_exists('processChildrenCategoryLevel2')) {
    function processChildrenCategoryLevel2(&$categories, array $category, int $id) {
        $parentId = (int) $category['lvl1_id'];
        if (!empty($category['lvl2_id']) && (int) $category['lvl2_parentId'] === $parentId) {
            $childId = (int) $category['lvl2_id'];
            $categories[$id]['children'][$parentId]['children'][$childId] = [
                'id' => $childId,
                'text' => $category['lvl2_text'],
                'icon' => $category['lvl2_icon'],
                'class' => $category['lvl2_class'],
                'status' => $category['lvl2_status'],
            ];
        }
    }
}

if (!function_exists('productCategoryConversionData')) {
    function productCategoryConversionData(array $categories) {
        $data = [];
        
        foreach ($categories as $category) {
            $data[] = [
                'id' => $category['id'],
                'text' => $category['text'],
                'level' => 1,
                'status' => $category['status'],
            ];

            foreach ($category['children'] as $sub) {
                $data[] = [
                    'id' => $sub['id'],
                    'text' => sprintf('%s >> %s', $category['text'], $sub['text']),
                    'level' => 2,
                    'status' => $sub['status'],
                ];

                foreach ($sub['children'] as $child) {
                    $data[] = [
                        'id' => $child['id'],
                        'text' => sprintf('%s >> %s >> %s', $category['text'], $sub['text'], $child['text']),
                        'level' => 3,
                        'status' => $child['status'],
                    ];
                }
            }
        }

        return $data;
    }
}

if (!function_exists('getInvoiceNumberFromNotificationContent')) {
    function getInvoiceNumberFromNotificationContent(string $notification) {
        $parts = explode(' ', $notification);

        foreach ($parts as $part) {
            // The former is format in DEV & STAGE, the latter is format in PROD
            if (strpos($part, '/Inv/') !== false || strpos($part, 'BM-INVOICE/') !== false) {
                return trim($part, ',');
            }
        }

        return 'n/a';
    }
}

if (!function_exists('indonesiaDateFormat')) {
    function indonesiaDateFormat(string $date, string $format = 'd F Y') {
        $month = date('F', strtotime($date));
        $months = [
            'January' => 'Januari',
            'February' => 'Februari',
            'March' => 'Maret',
            'April' => 'April',
            'May' => 'Mei',
            'June' => 'Juni',
            'July' => 'Juli',
            'August' => 'Agustus',
            'September' => 'September',
            'October' => 'Oktober',
            'November' => 'November',
            'December' => 'Desember',
        ];

        return str_replace($month, $months[$month], date($format, strtotime($date)));
    }
}

if (!function_exists('indonesiaDateFormatAlt')) {
    function indonesiaDateFormatAlt($timestamp = null, string $format = 'd F Y', $suffix = ''): string {
        if (in_array($timestamp, ['1970-01-01', '0000-00-00', '-25200'])) {
            return '-';
        }

        if (empty($timestamp)) {
            $timestamp = time();
        } elseif (!ctype_digit($timestamp)) {
            $timestamp = strtotime($timestamp);
        }

        $search = [
            '/Mon[^day]/', '/Tue[^sday]/', '/Wed[^nesday]/', '/Thu[^rsday]/', '/Fri[^day]/',
            '/Sat[^urday]/', '/Sun[^day]/', '/Monday/', '/Tuesday/', '/Wednesday/',
            '/Thursday/', '/Friday/', '/Saturday/', '/Sunday/', '/Jan[^uary]/',
            '/Feb[^ruary]/', '/Mar[^ch]/', '/Apr[^il]/', '/May/', '/Jun[^e]/',
            '/Jul[^y]/', '/Aug[^ust]/', '/Sep[^tember]/', '/Oct[^ober]/', '/Nov[^ember]/',
            '/Dec[^ember]/', '/January/', '/February/', '/March/', '/April/',
            '/June/', '/July/', '/August/', '/September/', '/October/', '/November/', '/December/',
        ];

        $replace = [
            'Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab', 'Min',
            'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu',
            'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Ags', 'Sep', 'Okt', 'Nov', 'Des',
            'Januari', 'Februari', 'Maret', 'April', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember',
        ];

        # Remove S (st,nd,rd,th) there are no such things in indonesia :p
        $format = preg_replace('/S/', '', $format);
        $date = preg_replace($search, $replace, date($format, $timestamp));

        return sprintf('%s %s', $date, $suffix);
    }
}

if (!function_exists('isEmpty')) {
    function isEmpty($data) {
        return empty($data);
    }
}