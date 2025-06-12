<?php

namespace App\Twig;

use App\Traits\ContainerTrait;
use DateTime;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Twig\Extension\AbstractExtension;
use Twig\Extension\GlobalsInterface;
use Twig\TwigFilter;
use Twig\TwigFunction;
use Twig\TwigTest;

class AppExtension extends AbstractExtension implements GlobalsInterface
{
    use ContainerTrait;

    /** @var ContainerInterface $container */
    protected $container;

    /** @var AppFunction $function */
    protected $function;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $this->function = new AppFunction($container);
    }

    public function getGlobals(): array
    {
        return [
            'locale' => $this->getRequest()->attributes->get('_locale') ?: $this->function->getContainerParameter('locale'),
            'base_url' => $this->getRequest()->getSchemeAndHttpHost(),
            'enable_chat' => $this->function->getContainerParameter('enable_chat'),
            'local_regions' => $this->function->getContainerParameter('bali_local_regions'),
            'checkout_payment_info' => 'hide',
            'confirmation_payment_info' => 'show',
            'show_pay_account_1' => 'yes',
            'show_pay_account_2' => 'yes',
            'show_qris_pay' => 'yes',
            'asset_qris_pay' => 'assets/img/QRIS-v3.jpg',
            'order_success_button' => 'v2',
            'multi_lang' => 'yes',
            'homepage_promo' => [
                'left' => [
                    //'route' => null,
                    //'parameters' => [],
                    //'image' => 'dist/img/produk-sendiri.png',
                    'route' => null,
                    'parameters' => [],
                    'image' => 'assets/img/banner-pameran.jpg',
                    'with_buttons' => 'yes',
                    'buttons_content' => [
                        [
                            'route' => 'search',
                            'parameters' => ['keywords' => '', 'category1' => [11 => 35]],
                            'url' => null,
                            'class' => '',
                            'label' => 'Klik Disini Untuk Berbelanja',
                        ],
                        [
                            'route' => 'external',
                            'parameters' => [],
                            'url' => 'https://www.artsteps.com/embed/608818f8e6bff2bfefc4450b/560/315 ',
                            'class' => '',
                            'label' => 'Klik Disini Untuk Mengunjungi Pameran Virtual',
                        ],
                        [
                            'route' => null,
                            'parameters' => [],
                            'url' => null,
                            'class' => '',
                            'label' => 'Tutorial Berbelanja',
                        ],
                    ],
                ],
                'right' => [
                    'route' => null,
                    'parameters' => [],
                    'image' => 'dist/img/produk-sendiri.png',
                    //'route' => 'search',
                    //'parameters' => ['keywords' => '', 'category1' => [14 => 72]],
                    //'image' => 'assets/img/promo-ultah.jpg',
                    'with_buttons' => 'no',
                    'buttons_content' => [],
                ],
            ],
            'social_buttons' => [
                'facebook' => 'https://www.facebook.com/Balimallid.Official',
                'twitter' => null,
                'instagram' => 'https://www.instagram.com/balimallid/',
            ],
            'search_filter' => [
                'price' => 'input', // ['input', 'slider']
            ],
            'product_unit_types' => [
                'unit' => 'label.unit_alt',
                'pcs' => 'label.pcs_alt',
                'box' => 'label.box',
                'package' => 'label.package',
                'rim' => 'label.rim',
                'doos' => 'label.doos',
                'pack' => 'label.pack',
                'buah' => 'label.buah',
                'lusin' => 'label.lusin',
                'set' => 'label.set',
                'roll' => 'label.roll',
                'sloop' => 'label.sloop',
                'dus' => 'label.dus',
                'bottle' => 'label.bottle',
                'pair' => 'label.pair',
                'meter' => 'label.meter',
                'm2' => 'label.m2',
                'eksemplar' => 'label.eksemplar',
                'galon' => 'label.galon',
                'pail' => 'label.pail',
                'kg' => 'label.kg',
                'gram' => 'label.gram',
            ],
            'free_shipping_method' => [
                'multiple' => 'yes',
                'v2' => 'include',
            ],
            'qris_pay' => [
                'enable' => 'yes',
                'nmid' => 'ID2020045566789',
                'merchant_name' => getenv('QRIS_MERCHANT_NAME'),
                'merchant_pan' => getenv('QRIS_MERCHANT_PAN'),
                'merchant_pan_print' => substr(getenv('QRIS_MERCHANT_PAN'), 0, 8),
                'terminal_user' => getenv('QRIS_TERMINAL_USER'),
                'amount_limit' => $this->function->getContainerParameter('qris_amount_limit'),
            ],
            'va_pay' => [
                'enable' => 'yes',
                'merchant_name' => getenv('WS_BPD_INSTITUTION'),
            ],
        ];
    }

    public function getTests(): array
    {
        return [
            new TwigTest('datetime', function ($value) { return $value instanceof DateTime; })
        ];
    }

    public function getFilters(): array
    {
        return [
            new TwigFilter('json_decode', [AppFilter::class, 'jsonDecode']),
            new TwigFilter('to_int', [AppFilter::class, 'toInt']),
            new TwigFilter('to_float', [AppFilter::class, 'toFloat']),
            new TwigFilter('to_string', [AppFilter::class, 'toString']),
            new TwigFilter('to_bool', [AppFilter::class, 'toBool']),
            new TwigFilter('to_array', [AppFilter::class, 'toArray']),
            new TwigFilter('to_object', [AppFilter::class, 'toObject']),
            new TwigFilter('number_format', [AppFilter::class, 'numberFormat']),
            new TwigFilter('array_unset', [AppFilter::class, 'arrayUnset']),
            new TwigFilter('base64_encode', [AppFilter::class, 'base64Encode']),
            new TwigFilter('base64_decode', [AppFilter::class, 'base64Decode']),
            new TwigFilter('normalize_phone', [AppFilter::class, 'normalizePhoneNumber']),
            new TwigFilter('number_format_to_words', [AppFilter::class, 'formatNumberToWords']),
        ];
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('asset', [AppFunction::class, 'getAssetUrl']),
            new TwigFunction('asset_version', [AppFunction::class, 'getAssetVersion']),
            new TwigFunction('site_url', [AppFunction::class, 'getSiteUrl']),
            new TwigFunction('copyright_year', [AppFunction::class, 'getCopyrightYear']),
            new TwigFunction('get_parameter', [AppFunction::class, 'getContainerParameter']),
            new TwigFunction('translation', [AppFunction::class, 'getTranslation']),
            new TwigFunction('csrf_field', [AppFunction::class, 'renderCSRFInput'], ['is_safe' => ['html']]),
            new TwigFunction('number_hash_id', [AppFunction::class, 'getNumberHashId']),
            new TwigFunction('date_diff', [AppFunction::class, 'getDateDiff']),
            new TwigFunction('get_setting', [AppFunction::class, 'getSetting']),
            new TwigFunction('get_country_name', [AppFunction::class, 'getCountryName']),
            new TwigFunction('get_province_name', [AppFunction::class, 'getProvinceName']),
            new TwigFunction('get_vendor_couriers', [AppFunction::class, 'getVendorCouriers']),
            new TwigFunction('check_vendor_is_pkp', [AppFunction::class, 'checkVendorIsPKP']),
            new TwigFunction('get_random_string', [AppFunction::class, 'getRandomString']),
            new TwigFunction('product_main_image', [ProductExtension::class, 'getProductMainImage']),
            new TwigFunction('product_hash_id', [ProductExtension::class, 'getProductHashId']),
            new TwigFunction('product_review', [ProductExtension::class, 'getProductReview']),
            new TwigFunction('product_category_name', [ProductExtension::class, 'getProductCategoryName']),
            new TwigFunction('order_products', [OrderExtension::class, 'getOrderProducts']),
            new TwigFunction('order_chat_room', [OrderExtension::class, 'getOrderChatRoom']),
            new TwigFunction('order_id_from_invoice', [OrderExtension::class, 'getOrderIdFromInvoice']),
            new TwigFunction('order_vouchers', [OrderExtension::class, 'getOrderVouchers']),
            new TwigFunction('order_related', [OrderExtension::class, 'getOrderRelated']),
            new TwigFunction('order_step_status', [OrderExtension::class, 'getOrderStepStatus']),
            new TwigFunction('get_ppn', [OrderExtension::class, 'getPPNAtTotalPrice']),
            new TwigFunction('generate_ppn', [OrderExtension::class, 'generatePPN']),
            new TwigFunction('get_ppn_percentage', [OrderExtension::class, 'getPpnPercentage']),
            new TwigFunction('get_base_price', [OrderExtension::class, 'getPriceAtTotalPrice']),
            new TwigFunction('generate_tax', [OrderExtension::class, 'generateTax']),
        ];
    }
}
