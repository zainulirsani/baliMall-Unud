<?php

namespace App\Twig;

use App\Entity\Store;
use App\Helper\StaticHelper;
use App\Repository\StoreRepository;
use App\Traits\AppTrait;
use App\Traits\ContainerTrait;
use DateTime;
use Hashids\Hashids;
use Psr\Cache\InvalidArgumentException;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Asset\Package;
use Symfony\Component\Asset\Packages;
use Symfony\Component\Asset\VersionStrategy\StaticVersionStrategy;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\Cache\CacheItem;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Twig\Extension\RuntimeExtensionInterface;

class AppFunction implements RuntimeExtensionInterface
{
    use ContainerTrait;
    use AppTrait;

    /** @var ContainerInterface $container */
    protected $container;

    /** @var Packages $packages */
    protected $packages;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;

        $this->packages = new Packages(new Package(new StaticVersionStrategy('v232')));
    }

    public function getAssetUrl($path, $packageName = null): string
    {
        return sprintf('/%s', $this->packages->getUrl($path, $packageName));
    }

    public function getAssetVersion($path, $packageName = null): string
    {
        return $this->packages->getVersion($path, $packageName);
    }

    public function getSiteUrl(string $uri = ''): string
    {
        return $this->getRequest()->getSchemeAndHttpHost().'/'.$uri;
    }

    public function getCopyrightYear(): string
    {
        $year = '2018';
        $current = date('Y');

        return $current === $year ? $current : sprintf('%s - %s', $year, $current);
    }

    public function getContainerParameter(string $id)
    {
        return $this->getContainer()->getParameter($id);
    }

    public function getTranslation(string $id = null, array $parameter = []): string
    {
        return $this->getTranslator()->trans($id, $parameter);
    }

    public function renderCSRFInput(string $tokenId): string
    {
        $tokenManager = $this->getCsrfTokenManager();
        $tokenValue = $tokenManager->getToken($tokenId);
        $field = "<input type=\"hidden\" name=\"_csrf_token\" value=\"$tokenValue\">\n";
        $field .= "<input type=\"hidden\" name=\"_csrf_token_id\" value=\"$tokenId\">\n";

        return $field;
    }

    public function getNumberHashId(
        string $hash,
        string $hashType = 'encode',
        string $hashKey = 'BaliMall-Number',
        int $hashLength = 6
    )
    {
        if (in_array($hashType, ['encode', 'decode'], false)) {
            $encoder = new Hashids($hashKey, $hashLength);

            if ($hashType === 'encode') {
                if ((int) $hash[0] === 0) {
                    $hash = '62'.substr($hash, 1);
                }

                return $encoder->encode((int) $hash);
            }

            return current($encoder->decode($hash));
        }

        return false;
    }

    public function getDateDiff(DateTime $startDate, DateTime $endDate)
    {
        return $startDate->diff($endDate)->days;
    }

    public function getSetting(string $slug)
    {
        try {
            $cache = $this->getCache(0);
            /** @var CacheItem $settings */
            $settings = $cache->getItem(getenv('APP_SETTINGS_CACHE'));

            if ($settings = $settings->get()) {
                return $settings[$slug] ?? null;
            }
        } catch (InvalidArgumentException $e) {
        }

        return null;
    }

    public function getCountryName(string $countryId)
    {
        $countryList = $this->getCountryList();

        return $countryList[$countryId] ?? 'N/A';
    }

    public function getProvinceName(string $provinceId, string $countryId = 'all')
    {
        $provinceList = $this->getProvinceList($countryId);

        return $provinceList[$provinceId] ?? 'N/A';
    }

    public function getVendorCouriers(string $slug): array
    {
        /** @var StoreRepository $repository */
        $repository = $this->getRepository(Store::class);
        $store = $repository->findOneBy(['slug' => $slug]);

        return ($store instanceof Store) ? $store->getDeliveryCouriers() : [];
    }

    public function checkVendorIsPKP(string $slug): bool
    {
        /** @var StoreRepository $repository */
        $repository = $this->getRepository(Store::class);
        $store = $repository->findOneBy(['slug' => $slug]);

        return ($store instanceof Store) ? $store->getIsPKP() : false;
    }

    protected function getCache(int $lifetime = 3600): FilesystemAdapter
    {
        return new FilesystemAdapter('fs', $lifetime, __DIR__.'/../../var');
    }

    protected function get(string $service)
    {
        return $this->getContainer()->get($service);
    }

    public function getRandomString() :string
    {
        return StaticHelper::secureRandomCode(16);
    }
}
