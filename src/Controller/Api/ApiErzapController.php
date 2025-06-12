<?php

namespace App\Controller\Api;

use App\Controller\PublicController;
use App\Entity\Order;
use App\Entity\Product;
use App\Entity\ProductCategory;
use App\Entity\ProductFile;
use App\Entity\Store;
use App\Repository\ProductRepository;
use Cocur\Slugify\Slugify;
use ErrorException;
use Exception;
use Hashids\Hashids;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class ApiErzapController extends PublicController
{
    protected $logger;

    public function __construct(TranslatorInterface $translator, ValidatorInterface $validator, LoggerInterface $logger)
    {
        parent::__construct($translator, $validator);

        $this->logger = $logger;
    }

    private function checkValidAccess(): bool
    {
        $request = $this->getRequest();
        $reqClientId = $request->headers->get('X-CLIENT-ID', null);
        $clientId = getenv('BALIMALL_CLIENT_ID');

        if ($clientId !== $reqClientId) {
            return false;
        }

        return true;
    }

    public function merchantConnect(): JsonResponse
    {
        $response = [
            'status' => true,
            'message' => ''
        ];

        $statusCode = 200;

        try {
            $data = json_decode(file_get_contents('php://input'), true);

            $data = json_decode(base64_decode($data['data']), true);

            $this->internalLogger('Erzap Merchant Connect Request', $data);

        } catch (\Throwable $th) {

            $response['status'] = false;
            $response['message'] = 'Invalid Request';
            $statusCode = 400;

            $this->internalLogger('Erzap Merchant Connect Invalid Request');

            return $this->response(base64_encode(json_encode($response)), $response['message'], $statusCode);
        }

        $client_id = getenv('ERZAP_CLIENT_ID');
        $client_secret = getenv('ERZAP_CLIENT_SECRET');

        if (isset($data['erzap_client_id']) && isset($data['erzap_client_secret']) &&
            $client_id === $data['erzap_client_id'] && $client_secret === $data['erzap_client_secret']
        ) {

            $storeRepo = $this->getRepository(Store::class);
            $store = $storeRepo->findOneBy(['shopId' => $data['shop_id']]);

            if ($store instanceof Store) {
                $response['status'] = true;
                $response['message'] = 'Merchant berhasil dihubungkan';
                $response['shop_id'] = $data['shop_id'];
                $response['nama_toko'] = $store->getName();

            } else {
                $response['status'] = false;
                $response['message'] = 'Shop / Merchant tidak ditemukan';
                $statusCode = 404;
            }

        } else {
            $response['status'] = false;
            $response['client_id'] = $client_id;
            $response['cid'] = $data;
            $response['message'] = 'Unauthorized';
            $statusCode = 401;
        }

        $this->internalLogger('Erzap Merchant Connect Response', $response);

        return $this->response(base64_encode(json_encode($response)), $response['message'], $statusCode);
    }


    public function productCreate(): JsonResponse
    {

        $response = [
            'status' => true,
            'message' => ''
        ];

        $statusCode = 200;

        try {
            $data = json_decode(file_get_contents('php://input'), true);
            $data = json_decode(base64_decode($data['data']), true);

            $this->internalLogger('Erzap Create Product', $data);

        } catch (\Throwable $th) {
            $response['status'] = false;
            $response['message'] = 'Invalid Request';
            $statusCode = 400;

            $this->internalLogger('Erzap Create Product Invalid Request');

            return $this->response(base64_encode(json_encode($response)), $response['message'], $statusCode);
        }

        $storeRepository = $this->getRepository(Store::class);
        $productRepository = $this->getRepository(Product::class);

        $store = $storeRepository->findOneBy(['shopId' => $data['shop_id']]);

        if ($store instanceof Store) {
            try {
                $product_req = $data['produk_mp'];

                $slug = $this->generateSlug($product_req['nama_produk']);

                $productCheck = $productRepository->findBy(['slug' => $slug]);

                $belum_ada_data = false;

                if (count($productCheck) === 0) {
                    $belum_ada_data = true;
                } else {
                    foreach ($productCheck as $product) {
                        $product_store = $product->getStore();
                        $belum_ada_data = $product_store->getId() == $store->getId() ? ($product->getStatus() != 'deleted' ? false : true) : true;
//                        $belum_ada_data = !($product_store->getId() == $store->getId()) || !($product->getStatus() != 'deleted');
                    }
                }

                if ($belum_ada_data) {
                    $product = new Product();
                    $product->setSlug($slug);
                    $product->setStore($store);
                    $product->setQuantity($product_req['stok_mp']);
                    $product->setPrice($product_req['harga_jual']);
                    $product->setBasePrice($product_req['harga_jual']);
                    $product->setName($product_req['nama_produk']);
                    $product->setSku($product_req['sku']);
                    $product->setStatus($product_req['is_active'] ? 'publish' : 'draft');
                    $product->setDescription($product_req['deskripsi_produk']);
                    $category = $this->searchCategory($product_req['kategori']);
                    $product->setCategory($category);

                    $em = $this->getEntityManager();

                    $em->persist($product);
                    $em->flush();

                    if (empty($product->getDirSlug())) {
                        $alphabet = getenv('HASHIDS_ALPHABET');
                        $encoder = new Hashids(Product::class, 6, $alphabet);
                        $productId = $product->getId();
                        $dirSlug = $encoder->encode($productId);
                        /** @var Product $duplicate */
                        $duplicate = $productRepository->findOneBy(['dirSlug' => $dirSlug]);

                        if ($duplicate instanceof Product) {
                            $salt = 'App\Entity\DuplicateProduct-' . date('YmdHis');
                            $duplicateEncoder = new Hashids($salt, 7, $alphabet);
                            $dirSlug = $duplicateEncoder->encode($productId);
                        }

                        $product->setDirSlug($dirSlug);

                        $em->persist($product);
                        $em->flush();
                    }

                    $image_urut = $this->sortImageCover($product_req['product_images']);

                    $this->uploadImageUsingLink($image_urut, $product);

                    $response['status'] = true;
                    $response['message'] = 'Produk berhasil dibuat';
                    $response['idproduk_mp'] = $product->getId();
                } else {
                    $response['status'] = false;
                    $response['message'] = 'Produk sudah terdaftar pada merchant ini';
                    $statusCode = 403;
                }
            } catch (Exception $e) {
                $response['status'] = false;
                $response['message'] = 'Terjadi Kesalahan pada Api';
                $statusCode = 500;
            }
        } else {
            $response['status'] = false;
            $response['message'] = 'Shop / Merchant tidak ditemukan';
            $statusCode = 404;

        }

        $this->internalLogger('Erzap Create Product Response', $response);


        return $this->response(base64_encode(json_encode($response)), $response['message'], $statusCode);
    }

    public function updateProductStock(): JsonResponse
    {
        $statusCode = 200;

        try {
            $data = json_decode(file_get_contents('php://input'), true);
            $data = json_decode(base64_decode($data['data']), true);

            $this->internalLogger('Erzap Update Product Stock Request', $data);

        } catch (\Throwable $th) {
            $response['status'] = false;
            $response['message'] = 'Invalid Request';
            $statusCode = 400;

            $this->internalLogger('Erzap Update Product Stock Invalid Request');

            return $this->response(base64_encode(json_encode($response)), $response['message'], $statusCode);
        }

        $productRepo = $this->getRepository(Product::class);
        $storeRepo = $this->getRepository(Store::class);

        $store = $storeRepo->findOneBy(['shopId' => $data['shop_id']]);
        $product = $productRepo->findOneBy(['id' => $data['idproduk_mp']]);

        $em = $this->getEntityManager();

        if ($store instanceof Store) {
            if ($product instanceof Product) {
                try {
                    $product->setQuantity((int)$data['stok_mp']);
                    $em->persist($product);
                    $em->flush();

                    $response['status'] = true;
                    $response['message'] = 'Stok produk berhasil diupdate';
                    $response['produk_mp']['idproduk_mp'] = $data['idproduk_mp'];
                    $response['produk_mp']['stok_mp'] = (int)$data['stok_mp'];

                } catch (Exception $e) {
                    $response['status'] = false;
                    $response['message'] = 'Terjadi Kesalahan pada Api';
                    $statusCode = 500;
                }
            } else {
                $response['status'] = false;
                $response['message'] = 'Product tidak ditemukan';
                $statusCode = 404;
            }
        } else {
            $response['status'] = false;
            $response['message'] = 'Shop / Merchant tidak ditemukan';
            $statusCode = 404;
        }

        $this->internalLogger('Erzap Update Product Stock Response', $response);

        return $this->response(base64_encode(json_encode($response)), $statusCode);
    }

    public function updateProductPrice(): JsonResponse
    {

        $statusCode = 200;

        try {
            $data = json_decode(file_get_contents('php://input'), true);
            $data = json_decode(base64_decode($data['data']), true);

            $this->internalLogger('Erzap Update Product Price Request', $data);

        } catch (\Throwable $th) {
            $response['status'] = false;
            $response['message'] = 'Invalid Request';
            $statusCode = 400;

            $this->internalLogger('Erzap Update Product Price Invalid Request');

            return $this->response(base64_encode(json_encode($response)), $response['message'], $statusCode);
        }

        $productRepo = $this->getRepository(Product::class);
        $storeRepo = $this->getRepository(Store::class);

        $store = $storeRepo->findOneBy(['shopId' => $data['shop_id']]);
        $product = $productRepo->findOneBy(['id' => $data['idproduk_mp']]);


        if ($store instanceof Store) {
            if ($product instanceof Product) {
                try {
                    $product->setPrice((float)$data['harga_jual']);
                    $product->setBasePrice((float)$data['harga_jual']);

                    $em = $this->getEntityManager();

                    $em->persist($product);
                    $em->flush();

                    $response['status'] = true;
                    $response['message'] = 'Harga produk berhasil diupdate';
                    $response['produk_mp']['idproduk_mp'] = $data['idproduk_mp'];
                    $response['produk_mp']['harga_jual'] = (int)$data['harga_jual'];
                } catch (Exception $e) {
                    $response['status'] = false;
                    $response['message'] = 'Terjadi Kesalahan pada Api';
                    $statusCode = 500;
                }
            } else {
                $response['status'] = false;
                $response['message'] = 'Product tidak ditemukan';
                $statusCode = 404;
            }
        } else {
            $response['status'] = false;
            $response['message'] = 'Shop / Merchant tidak ditemukan';
            $statusCode = 404;
        }

        $this->internalLogger('Erzap Update Product Price Response', $response);

        return $this->response(base64_encode(json_encode($response)), $response['message'], $statusCode);
    }

    public function productDelete(): JsonResponse
    {

        $statusCode = 200;

        try {
            $data = json_decode(file_get_contents('php://input'), true);
            $data = json_decode(base64_decode($data['data']), true);

            $this->internalLogger('Erzap Delete Product Request', $data);

        } catch (\Throwable $th) {
            $response['status'] = false;
            $response['message'] = 'Invalid Request';
            $statusCode = 400;

            $this->internalLogger('Erzap Delete Product Invalid Request');

            return $this->response(base64_encode(json_encode($response)), $response['message'], $statusCode);
        }

        $productRepo = $this->getRepository(Product::class);
        $product = $productRepo->findOneBy(['id' => $data['idproduk_mp']]);


        if ($product instanceof Product) {
            try {
                $product->setStatus('deleted');
                $em = $this->getEntityManager();
                $em->persist($product);
                $em->flush();

                $response['status'] = true;
                $response['message'] = 'Produk berhasil dihapus';
                $response['idproduk_mp'] = $data['idproduk_mp'];
            } catch (Exception $e) {
                $response['status'] = false;
                $response['message'] = 'Terjadi Kesalahan pada Api';
                $statusCode = 500;
            }
        } else {
            $response['status'] = false;
            $response['message'] = 'Product tidak ditemukan';
            $statusCode = 404;

        }

        $this->internalLogger('Erzap Delete Product Response', $response);

        return $this->response(base64_encode(json_encode($response)), $response['message'], $statusCode);
    }

    public function productInfoBySKU(): JsonResponse
    {
        $response = [
            'status' => true,
            'message' => '',
            'produk_mp' => []
        ];

        $statusCode = 200;

        try {
            $data = json_decode(file_get_contents('php://input'), true);
            $data = json_decode(base64_decode($data['data']), true);

            $this->internalLogger('Erzap Product Info By SKU', $data);

        } catch (\Throwable $th) {
            $response['status'] = false;
            $response['message'] = 'Invalid Request';
            $statusCode = 400;

            $this->internalLogger('Erzap Product Info By SKU Invalid Request');

            return $this->response(base64_encode(json_encode($response)), $response['message'], $statusCode);
        }

        $shopId = $data['shop_id'];
        $sku = $data['sku'];

        $repository = $this->getRepository(Store::class);
        $store = $repository->findOneBy(['shopId' => $shopId]);

        if ($store instanceof Store) {
            $product = $this->getRepository(Product::class)->findOneBy(['sku' => $sku]);

            if ($product instanceof Product) {
                $productMp = [
                    'idproduk_mp' => $product->getId(),
                    'stok_mp' => (int)$product->getQuantity(),
                    'harga_jual' => (int)$product->getPrice(),
                    'nama_produk' => $product->getName(),
                    'sku' => $product->getSku(),
                    'is_active' => $product->getStatus() === "publish",
                    'deskripsi_produk' => $product->getDescription(),
                ];

                $category = $this->getRepository(ProductCategory::class)->find($product->getCategory());

                $productMp['kategori'] = [
                    'nama' => $category->getName(),
                    'kode' => $category->getId(),
                ];

                $productMp['product_images'] = $this->constructProductImagePayload($product);

                $response['produk_mp'] = $productMp;

            } else {
                $response['status'] = false;
                $response['message'] = 'Product not found';
                $statusCode = 404;
            }
        } else {
            $response['status'] = false;
            $response['message'] = 'Store not found';
            $statusCode = 404;
        }

        return $this->response(base64_encode(json_encode($response)), $response['message'], $statusCode);
    }

    public function productInfoByIdProduk(): JsonResponse
    {
        $response = [
            'status' => true,
            'message' => '',
            'produk_mp' => []
        ];

        $statusCode = 200;

        try {
            $data = json_decode(file_get_contents('php://input'), true);
            $data = json_decode(base64_decode($data['data']), true);

            $this->internalLogger('Erzap Product Info By ID', $data);

        } catch (\Throwable $th) {
            $response['status'] = false;
            $response['message'] = 'Invalid Request';
            $statusCode = 400;

            $this->internalLogger('Erzap Product Info By ID Invalid Request');

            return $this->response(base64_encode(json_encode($response)), $response['message'], $statusCode);
        }

        $shopId = $data['shop_id'];

        $repository = $this->getRepository(Store::class);
        $store = $repository->findOneBy(['shopId' => $shopId]);

        if ($store instanceof Store) {
            $product = $this->getRepository(Product::class)->find(intval($data['idproduk_mp']));

            if ($product instanceof Product) {
                $productMp = [
                    'idproduk_mp' => $product->getId(),
                    'stok_mp' => (int)$product->getQuantity(),
                    'harga_jual' => (int)$product->getPrice(),
                    'nama_produk' => $product->getName(),
                    'sku' => $product->getSku(),
                    'is_active' => $product->getStatus() === "publish",
                    'deskripsi_produk' => $product->getDescription(),
                ];

                $category = $this->getRepository(ProductCategory::class)->find($product->getCategory());

                $productMp['kategori'] = [
                    'nama' => $category->getName(),
                    'kode' => $category->getId(),
                ];

                $productMp['product_images'] = $this->constructProductImagePayload($product);

                $response['produk_mp'] = $productMp;

            } else {
                $response['status'] = false;
                $response['message'] = 'Product not found';
                $statusCode = 404;
            }
        } else {
            $response['status'] = false;
            $response['message'] = 'Store not found';
            $statusCode = 404;
        }

        return $this->response(base64_encode(json_encode($response)), $response['message'], $statusCode);
    }

    private function constructProductImagePayload(Product $product): array
    {
        $files = $product->getFiles();
        $result = [];

        if (count($files) > 0) {
            $tmpFiles = [];

            foreach ($files as $index => $file) {
                $tmpFiles[] = [
                    'url' => $this->getBaseUrl() . '/' . $file->getFilePath(),
                    'is_cover' => $index === 0,
                ];
            }

            $result = $tmpFiles;
        }

        return $result;
    }


    private function generateSlug(string $productName, int $productId = 0, ?ProductRepository $repository = null): string
    {
        $productSlug = (new Slugify())->slugify($productName);

        if ($repository instanceof ProductRepository) {
            while (true) {
                $existing = $repository->checkSlug($productSlug, $productId);

                if ($existing > 0) {
                    $parts = explode('-', $productSlug);
                    $counter = count($parts);
                    $index = $counter - 1;
                    $last = end($parts);

                    if (is_numeric($last) && isset($parts[$index])) {
                        $parts[$index] = $existing + 1;
                        $productSlug = implode('-', $parts);
                    } else {
                        $productSlug .= '-' . $existing;
                    }
                } else {
                    break;
                }
            }
        }

        return $productSlug;
    }

    public function searchCategory($category)
    {
        $productCategoryRepo = $this->getRepository(ProductCategory::class);
        $param['search'] = explode(" ", $category['nama']);
        $data = $productCategoryRepo->getDataForApi($param);
        $defaultCategory = 41;
        if (count($data) > 0) {
            $id_category = $data[0]["id"];
        } else {
            $id_category = $defaultCategory;
        }

        return $id_category;
    }

    public function sortImageCover($array = [])
    {
        $arr_return = [];

        // cari yg cover dlu
        foreach ($array as $key => $value) {
            if ($value['is_cover']) {
                $arr_return[] = $value;
            }
        }

        // cari yg bukan cover dlu
        foreach ($array as $key => $value) {
            if (!$value['is_cover']) {
                $arr_return[] = $value;
            }
        }

        return $arr_return;
    }

    public function uploadImageUsingLink($image, $obj)
    {
        $em = $this->getEntityManager();

        $dirSlug = $obj->getDirSlug();

        $path = 'uploads/products/' . $dirSlug . '/';

        foreach ($image as $key => $value) {
            try {
                $extension = pathinfo($value['url'], PATHINFO_EXTENSION);
                $newFileName = date('Ymd') . time() . $key . '.' . $extension;

                try {
                    $dataImage = file_get_contents($value['url']);

                    if ($dataImage === false) {
                        $dataImage = file_get_contents('/dist/img/no-image.png');
                    }
                } catch (\Throwable $exception) {
                    try {
                        $dataImage = file_get_contents('/dist/img/no-image.png');
                    } catch (Exception $exception) {
                        $dataImage = '';
                    }
                }

                try {

                    if (!@mkdir($path, 0755, true) && !is_dir($path)) {
                        throw new ErrorException($this->getTranslator()->trans('message.error.create_dir'));
                    }

                    file_put_contents($path . $newFileName, $dataImage);
                } catch (Exception $exception) {

                }

                $productFile = new ProductFile();
                $productFile->setProduct($obj);
                $productFile->setFileName($newFileName);
                $productFile->setFileType('image');
                $productFile->setFileMimeType('image/' . $extension);
                $productFile->setFilePath($path . $newFileName);

                $em->persist($productFile);
                $em->flush();
            } catch (Exception $exception) {

            }
        }
    }

    public function orderRequestManual(): JsonResponse
    {
        $response = [];
        $status = 200;

        try {
            $data = json_decode(file_get_contents('php://input'), true);
            $data = json_decode(base64_decode($data['data']), true);

            $this->logger->error('Log API ERZAP', [$data]);
        } catch (\Throwable $th) {
            $response['status'] = false;
            $response['message'] = 'Invalid Request';

            return $this->response(base64_encode(json_encode($response)), $response['message'], 400);
        }

        $storeRepository = $this->getRepository(Store::class);
        $orderRepository = $this->getRepository(Order::class);

        $store = $storeRepository->findOneBy(['shopId' => $data['shop_id']]);

        if ($store instanceof Store) {
            $parameters = [];

            if (isset($data['from_date']) && !empty($data['from_date'])) {
                $dt = gmdate('Y-m-d H:i:s', $data['from_date']);
                $parameters['date_start'] = $dt;
            }

            if (isset($data['to_date']) && !empty($data['to_date'])) {
                $dt = gmdate('Y-m-d H:i:s', $data['to_date']);
                $parameters['date_end'] = $dt;
            }

            if (isset($data['page']) && !empty($data['page'])) {
                $parameters['page'] = $data['page'];
            }

            if (isset($data['per_page']) && !empty($data['per_page'])) {
                $parameters['per_page'] = $data['per_page'];
            } else {
                $parameters['per_page'] = 100;
            }

            if (isset($data['order_status']) && !empty($data['order_status']) && $data['order_status'] !== 'ALL') {
                $status = $data['order_status'] == 'READY_TO_SHIP' ? 'shipped' : 'cancel';
                $parameters['status'] = $status;
            }

            $parameters['from'] = 'api';

            $orders = $orderRepository->getDataForTable($parameters);

            if ($orders['total'] > 0) {
                $response['status'] = true;
                $response['message'] = 'Order berhasil direquest';
                $response['more'] = $orders['total'] > count($orders['data']);

                $arr_order = [];

                foreach ($orders['data'] as $value) {
                    $order['order_id'] = $value['o_invoice'];
                    $order['order_status'] = $value['o_status'];
                    $order['shipping_courrier'] = $value['o_shippingCourier'].'('.$value['o_shippingService'].')';
                    $order['order_notes'] = $value['o_note'] ?? "";
                    $order['order_time'] = strtotime(($value['o_createdAt'])->format('Y-m-d H:i:s'));
                    $order['shop_id'] = $data['shop_id'];

                    // product
                    $produk_mp = [];
                    $o_products = $orderRepository->getOrderProducts($value['o_id']);
                    foreach ($o_products as $p) {
                        $produk['idproduk_mp'] = $p['p_id'];
                        $produk['nama_produk'] = $p['p_name'];
                        $produk['quantity'] = (int)$p['op_quantity'];
                        $produk['notes'] = $p['op_note'] ?? "";
                        $produk['harga_jual'] = (int)$p['p_price'];
                        $produk['total_harga'] = (int)$p['p_price'] * (int)$p['op_quantity'];
                        $produk['sku'] = $p['p_sku'] ?? "";
                        $produk_mp[] = $produk;
                    }
                    $order['produk_mp'] = $produk_mp;

                    // reciept
                    $reciept['name'] = $value['o_name'];
                    $reciept['phone'] = $value['o_phone'];

                    // address
                    $address['address_full'] = $value['o_address'] ?? "";
                    $address['city'] = $value['o_city'] ?? "";
                    $address['district'] = $value['o_district'] ?? "";
                    $address['province'] = $value['o_province'] ?? "";
                    $address['country'] = $value['o_country'] ?? "";
                    $address['postal_code'] = $value['o_postCode'] ?? "";

                    $reciept['address'] = $address;

                    $order['recipient'] = $reciept;

                    // price
                    $price['subtotal'] = (int)$value['o_total'];
                    $price['biaya_kirim'] = (int)$value['o_shippingPrice'];
                    $price['discount'] = 0;
                    $price['total_payment'] = (int)$value['o_total'] + (int)$value['o_shippingPrice'];
                    $order['price'] = $price;

                    $arr_order[] = $order;
                }

                $response['orders'] = $arr_order;
            } else {
                $response['status'] = false;
                $response['message'] = 'Order tidak ditemukan';
                $status = 404;
            }

        } else {
            $response['status'] = false;
            $response['message'] = 'Shop / Merchant tidak ditemukan';
            $status = 404;
        }

        return $this->response(base64_encode(json_encode($response)), $response['message'], $status);

    }

    private function internalLogger(string $message, $data = null)
    {
        $this->logger->error($message, [$data]);
    }
}
