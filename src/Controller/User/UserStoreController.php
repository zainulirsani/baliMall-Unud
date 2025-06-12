<?php

namespace App\Controller\User;

use App\Controller\PublicController;
use App\Email\BaseMail;
use App\Entity\Notification;
use App\Entity\Product;
use App\Entity\ProductCategory;
use App\Entity\Store;
use App\Entity\User;
use App\Entity\Disbursement;
use App\Entity\UserAddress;
use App\Exception\StoreInactiveException;
use App\Helper\StaticHelper;
use App\Service\BreadcrumbService;
use App\Service\RajaOngkirService;
use Cocur\Slugify\Slugify;
use DateTime;
use Dompdf\Dompdf;
use Dompdf\Options;
use Exception;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\ConstraintViolation;

class UserStoreController extends PublicController
{
    public function apply()
    {
        $this->deniedAccess('ROLE_USER_SELLER');

        if ($this->getUserStore()) {
            return $this->redirectToRoute('user_store_edit');
        }

        $flashBag = $this->get('session.flash_bag');
        $rajaOngkir = $this->get(RajaOngkirService::class);
        $tokenId = 'user_store_register';
        $user = $this->getUser();

        BreadcrumbService::add(['label' => $this->getTranslation('title.page.store_create')]);

        $productCategories = productCategoryConversionData($this->getProductCategoriesFeatured(0, 'no', 'yes'));

        $productCategory = $this->getParentChildProductCategories();

        return $this->view('@__main__/public/user/store/form.html.twig', [
            'form_data' => $flashBag->get('form_data'),
            'errors' => $flashBag->get('errors'),
            'token_id' => $tokenId,
            'province_data' => $rajaOngkir->getProvince(),
            'city_data' => $this->manipulateCitiesData(),
            'text_editor' => true,
            'user' => $user,
            'kesepakatan_number' => $this->getRegisterNumber(),
            'kesepakatan_date' => $this->getTodayFormat(),
            'pernyataan_date' => $this->getTodayFormat(null, true),
            'user_tnc' => $user->getTnc() ? 'yes' : 'no',
            'total_complete_form' => $user->getTnc() ?? 0,
            'product_category' => $productCategory,
            'product_categories' => $productCategories,
        ]);
    }
    
    public function register(): RedirectResponse
    {
        $this->deniedAccess('ROLE_USER_SELLER');
        
        $request = $this->getRequest();
        $translator = $this->getTranslator();
        $route = 'user_dashboard';

        if ($request->isMethod('POST')) {
            $formData = $request->request->all();

            $deliveryCouriers = (isset($formData['delivery_couriers']) && is_array($formData['delivery_couriers'])) ? $formData['delivery_couriers'] : [];
            $productCategories = (isset($formData['product_categories']) && is_array($formData['product_categories'])) ? $formData['product_categories'] : [];

            /** @var User $user */
            $user = $this->getUser();
            $slugger = new Slugify();
            $dirSlug = $user->getDirSlug();
            $newPath = 'uploads/users/' . $dirSlug;
            $deleteTempFiles = [];

            if ($user->getTnc() === null && !empty($formData['user_tnc']) && $formData['user_tnc'] === 'yes') {
                $user->setTnc(1);

                $em = $this->getEntityManager();
                $em->persist($user);
                $em->flush();

                $message = 'message.success.user_store_temp';

                $route = 'user_store_apply';

                $this->addFlash('success', $this->getTranslation($message));

                return $this->redirectToRoute($route);
            }

            $store = new Store();
            $store->setUser($user);
            $store->setName(filter_var($formData['name'], FILTER_SANITIZE_STRING));
            $store->setBrand(filter_var($formData['brand'], FILTER_SANITIZE_STRING));
            $store->setSlug($slugger->slugify($formData['name']));
            $store->setDescription(filter_var($formData['description'], FILTER_SANITIZE_STRING));
            $store->setAddress($formData['address']);
            $store->setPostCode(filter_var($formData['post_code'], FILTER_SANITIZE_STRING));
            $store->setAddressLat((float)$formData['lat']);
            $store->setAddressLng((float)$formData['lng']);
            $store->setCity(filter_var($formData['city'], FILTER_SANITIZE_STRING));
            $store->setCityId((int)$formData['city_id']);
            $store->setDistrict(filter_var($formData['district'], FILTER_SANITIZE_STRING));
            $store->setDistrictId(0);
            $store->setProvince(filter_var($formData['province'], FILTER_SANITIZE_STRING));
            $store->setProvinceId((int)$formData['province_id']);
            $store->setCountry('ID');
            $store->setCountryId(0);
            $store->setDeliveryCouriers($deliveryCouriers);
            $store->setIsActive(0);
            $store->setProductCategories($productCategories);
            $store->setIsUsedErzap(isset($formData['used_erzap']));

            $user->setNpwp(filter_var($formData['no_npwp'], FILTER_SANITIZE_STRING));
            $user->setNpwpName(filter_var($formData['nama_npwp'], FILTER_SANITIZE_STRING));

            if (isset($formData['user_tnc']) && $formData['user_tnc'] === 'yes') {
                $user->setTnc(1);
            }

            if ($formData['status'] === 'COMPLETE') {
                $store->setFileHash(StaticHelper::secureRandomCode(16));

                $store->setStatus('NEW_MERCHANT');

                $store->setStatusLog('Pengajuan data usaha');

            } elseif ($store->getStatus() === 'ACTIVE' && $store->getIsActive()) {
                $store->setStatus('UPDATE');
                $store->setStatusLog('Update data usaha');
            } else {
                $store->setStatus('DRAFT');
            }

            if (!empty($formData['position'])) {
                $store->setPosition(filter_var($formData['position'], FILTER_SANITIZE_STRING));
            }

            $userAddress = new UserAddress();
            $userAddress->setUser($user);
            $userAddress->setTitle('Alamat');
            $userAddress->setAddress($formData['address']);
            $userAddress->setPostCode($formData['post_code']);
            $userAddress->setCity($formData['city']);
            $userAddress->setCityId(abs($formData['city_id']));
            $userAddress->setDistrict(null);
            $userAddress->setDistrictId(0);
            $userAddress->setProvince($formData['province']);
            $userAddress->setProvinceId(abs($formData['province_id']));
            $userAddress->setCountry('ID');
            $userAddress->setCountryId(0);

            $store->setTypeOfBusiness(filter_var($formData['jenis_usaha'], FILTER_SANITIZE_STRING));

            $store->setBusinessCriteria(filter_var($formData['kriteria_usaha'], FILTER_SANITIZE_STRING));

            $suratIjinFiles = json_decode($formData['surat-ijin-usaha-img'][0]);
            $documents = json_decode($formData['dokumen-tambahan-img'][0]);
            $sppkpImges = json_decode($formData['sppkp_img'][0]);
            $suratFiles = [];
            $docFiles = [];
            $sppkpFiles = [];

            if (count($suratIjinFiles) > 0) {
                foreach ($suratIjinFiles as $file) {
                    $deleteTempFiles[] = $file;
                    $uploadedFile = $this->handleUploadedFile($file, $newPath);
                    if (!empty($uploadedFile)) {
                        $suratFiles[] = $uploadedFile;
                        $deleteTempFiles[] = $uploadedFile;
                    }
                }
            }

            if (count($documents) > 0) {
                foreach ($documents as $file) {
                    $deleteTempFiles[] = $file;
                    $uploadedFile = $this->handleUploadedFile($file, $newPath);
                    if (!empty($uploadedFile)) {
                        $docFiles[] = $uploadedFile;
                        $deleteTempFiles[] = $uploadedFile;
                    }
                }
            }

            if (count($sppkpImges) > 0) {
                foreach ($sppkpImges as $file) {
                    $deleteTempFiles[] = $file;
                    $uploadedFile = $this->handleUploadedFile($file, $newPath);
                    if (!empty($uploadedFile)) {
                        $sppkpFiles[] = $uploadedFile;
                        $deleteTempFiles[] = $uploadedFile;
                    }
                }
            }

            if (count($suratFiles) > 0) {
                $user->setSuratIjinFile($suratFiles);
            }

            if (count($docFiles) > 0) {
                $user->setDokumenFile($docFiles);
            }

            if (count($sppkpFiles) > 0) {
                $store->setSppkpFile($sppkpFiles);
            }

            if (!empty($formData['logo_img_temp'])) {
                $file = $formData['logo_img_temp'];
                $deleteTempFiles[] = $file;
                $uploadedFile = $this->handleUploadedFile($file, $newPath);
                if (!empty($uploadedFile)) {
                    $user->setPhotoProfile($uploadedFile);
                    $deleteTempFiles[] = $uploadedFile;
                }
            }

            if (!empty($formData['dashboard_img_temp'])) {
                $file = $formData['dashboard_img_temp'];
                $deleteTempFiles[] = $file;
                $uploadedFile = $this->handleUploadedFile($file, $newPath);
                if (!empty($uploadedFile)) {
                    $user->setBannerProfile($uploadedFile);
                    $deleteTempFiles[] = $uploadedFile;
                }
            }

            if (!empty($formData['npwp_img_temp'])) {
                $file = $formData['npwp_img_temp'];
                $deleteTempFiles[] = $file;
                $uploadedFile = $this->handleUploadedFile($file, $newPath);
                if (!empty($uploadedFile)) {
                    $user->setNpwpFile($uploadedFile);
                    $deleteTempFiles[] = $uploadedFile;
                }
            }

            //============

            $user->setIpAddress();

            if (isset($formData['registered_number'])) {
                $store->setRegisteredNumber(filter_var($formData['registered_number'], FILTER_SANITIZE_STRING));
            }

            $validator = $this->getValidator();
            $storeErrors = $validator->validate($store);
            $userErrors = $validator->validate($user);
            $userAddressError = $validator->validate($userAddress);

            if (count($deliveryCouriers) < 1) {
                $message = $translator->trans('global.not_empty', [], 'validators');
                $constraint = new ConstraintViolation($message, $message, [], $store, 'deliveryCouriers', '', null, null, new Assert\NotBlank(), null);

                $storeErrors->add($constraint);
            }

            if (count($storeErrors) === 0) {
                $em = $this->getEntityManager();
                $em->persist($store);
                $em->flush();

                $em->persist($user);
                $em->flush();

                $em->persist($userAddress);
                $em->flush();

                $message = 'message.success.user_store_created';

                if ($store->getStatus() === 'NEW_MERCHANT') {
                    $storeViewUrl = $this->generateUrl('admin_store_view', ['id' => $store->getId()], UrlGeneratorInterface::ABSOLUTE_URL);

                    $notification = new Notification();
                    $notification->setSellerId(0);
                    $notification->setBuyerId(0);
                    $notification->setIsSentToSeller(false);
                    $notification->setIsSentToBuyer(false);
                    $notification->setIsAdmin(true);
                    $notification->setTitle($this->getTranslation('notifications.store_register'));
                    $notification->setContent($this->getTranslation('notifications.store_register_text', ['%name%' => $store->getName()]));
                    $notification->setUrl($storeViewUrl);

                    $em->persist($notification);
                    $em->flush();

                    //--- Send email notification to admin
                    $mailToAdmin = $this->get(BaseMail::class);
                    $mailToAdmin->setMailSubject($this->getTranslation('message.info.new_store_registered'));
                    $mailToAdmin->setMailTemplate('@__main__/email/new_store_registered.html.twig');
                    $mailToAdmin->setToAdmin();
                    $mailToAdmin->setMailData([
                        'link' => $storeViewUrl,
                    ]);
                    $mailToAdmin->send();
                    //--- Send email notification to admin

                    //--- Send email notification to user
                    $pernyataanViewUrl = $this->generateUrl('agreement', ['type' => 'pernyataan', 'filehash' => $store->getFileHash()], UrlGeneratorInterface::ABSOLUTE_URL);
                    $kesepakatanViewUrl = $this->generateUrl('agreement', ['type' => 'kesepakatan', 'filehash' => $store->getFileHash()], UrlGeneratorInterface::ABSOLUTE_URL);

                    $mailToUser = $this->get(BaseMail::class);
                    $mailToUser->setMailSubject($this->getTranslation('message.info.new_store_registered'));
                    $mailToUser->setMailTemplate('@__main__/email/new_store_registered_seller.html.twig');
                    $mailToUser->setMailSender(getenv('MAIL_SENDER'));
                    $mailToUser->setMailRecipient($user->getEmail());
                    $mailToUser->setMailData([
                        'link' => $storeViewUrl,
                        'name' => $user->getFirstName(),
                        'file_links' => [
                            $pernyataanViewUrl,
                            $kesepakatanViewUrl
                        ]
                    ]);
                    $mailToUser->send();
                    //--- Send email notification to user

                } else if ($store->getStatus() === 'DRAFT') {
                    $message = 'message.success.user_store_temp';

                    $route = 'user_store_edit';
                }

                $this->addFlash('success', $this->getTranslation($message));
            } else {
                $errors = [];
                $route = 'user_store_apply';

                foreach ($userErrors as $error) {
                    $errors[$error->getPropertyPath()] = $error->getMessage();
                }

                foreach ($userAddressError as $error) {
                    $errors[$error->getPropertyPath()] = $error->getMessage();
                }

                foreach ($storeErrors as $error) {
                    $errors[$error->getPropertyPath()] = $error->getMessage();
                }

                if (isset($errors['slug']) && in_array($errors['slug'], ['Slug sudah digunakan', 'Slug is already taken'])) {
                    $this->addFlash('warning', $this->getTranslation('message.error.store_name_unavailable'));
                }

                foreach ($deleteTempFiles as $tempFilePath) {
                    $publicDir = $this->getParameter('public_dir_path');
                    $uploadDir = $this->getParameter('upload_dir_path');
                    $tmpFile = $publicDir . '/' . $tempFilePath;

                    if (!file_exists($tmpFile)) {
                        $tmpFile = $uploadDir . '/' . $tempFilePath;
                    }

                    if (is_file($tmpFile)) {
                        unlink($tmpFile);
                    }

                }

                $flashBag = $this->get('session.flash_bag');
                $flashBag->set('form_data', $formData);
                $flashBag->set('errors', $errors);

            }
        }

        return $this->redirectToRoute($route);
    }

    public function handleUploadedFile($file, $dir, $oldFile = null): string
    {
        $publicDir = $this->getParameter('kernel.project_dir') . '/public/';
        $ext_type = array('gif', 'jpg', 'jpe', 'jpeg', 'png', 'pdf');

        $uploadedFile = filter_var($file, FILTER_SANITIZE_STRING);

        $tempFile = $publicDir . $uploadedFile;
        $newDir = $publicDir . '/' . $dir . '/';

        $ext = pathinfo($tempFile, PATHINFO_EXTENSION);
        $fname = pathinfo($tempFile, PATHINFO_FILENAME);
        
        if (!file_exists($newDir)) {
            if (!@mkdir($newDir, 0755, true) && !is_dir($newDir)) {
                throw new \ErrorException($this->getTranslator()->trans('message.error.create_dir'));
            }
        }
        $localPath = ltrim($uploadedFile, '/');
        $remoteDir = $dir . '/';
        if (file_exists($tempFile) && in_array($ext, $ext_type)) {
            $newName = $fname;
            $filename = $newName . '.' . $ext;
            $uploadFile = $this->sftpUploader->upload($localPath, $_ENV['SFTP_REMOTE_DIR'] . $remoteDir, $filename);
            
            if ($uploadFile){
                $deleteFile = $this->sftpUploader->deleteFolder($_ENV['SFTP_REMOTE_DIR'], $remoteDir, $uploadedFile, $filename);
                $path = $publicDir . '/' . $localPath;
                unlink($path);
                rmdir($newDir);
            }
        }

        return '';
    }

    public function getRegisterNumber($date = null)
    {
        if (is_null($date)) {
            $date = new DateTime();
        }

        $counter = $this->getRepository(Store::class)->getTotalRegisteredMerchantByDate($date);
        $counter += 1;

        if ($counter < 10) {
            $counter = '0' . (string)$counter;
        }

        $array_month = array(1 => "I", "II", "III", "IV", "V", "VI", "VII", "VIII", "IX", "X", "XI", "XII");
        $month = $date->format('n');
        $monthRomawi = $array_month[$month];

        $registerNumber = 'Nomor: ' . $date->format('d') . $counter . '/' . $monthRomawi . '/BUS-SKsp.Mc/' . $date->format('Y');

        return $registerNumber;
    }

    public function getTodayFormat($date = null, $dateOnly = false): string
    {
        if (is_null($date)) {
            $date = new DateTime();
        }

        $days = [
            'Monday' => 'Senin',
            'Tuesday' => 'Selasa',
            'Wednesday' => 'Rabu',
            'Thursday' => 'Kamis',
            'Friday' => 'Jumat',
            'Saturday' => 'Sabtu',
            'Sunday' => 'Minggu'
        ];

        $months = [
            '01' => 'Januari',
            '02' => 'Februari',
            '03' => 'Maret',
            '04' => 'April',
            '05' => 'Mei',
            '06' => 'Juni',
            '07' => 'Juli',
            '08' => 'Agustus',
            '09' => 'September',
            '10' => 'Oktober',
            '11' => 'November',
            '12' => 'Desember',
        ];

        if ($dateOnly) {
            $format = $date->format('d') . ' ' . $months[$date->format('m')] . ' ' . $date->format('Y');
        } else {
            $format = $days[$date->format('l')] . ' tanggal ' . $date->format('d') . ' bulan ' . $months[$date->format('m')] . ' tahun ' . $date->format('Y');
        }

        return $format;
    }

    public function edit()
    {
        $this->getDefaultData();

        $formData = $this->getUserStore();

        if (!$formData) {
            return $this->redirectToRoute('user_store_apply');
        }

        if ($formData->getStatus() === 'UPDATE' || ($formData->getStatus() === 'PENDING' && $formData->getIsActive()) && !empty($formData->getPreviousChanges())
            && (int)$formData->getPreviousChanges()['p_lastChangedBy'] === (int)$formData->getUser()->getId()) {

            $prevChanges = $formData->getPreviousChanges();
            $user = $formData->getUser();

            if (!empty($prevChanges['s_name'])) $formData->setName($prevChanges['s_name']);
            if (!empty($prevChanges['s_brand'])) $formData->setBrand($prevChanges['s_brand']);
            if (!empty($prevChanges['s_description'])) $formData->setDescription($prevChanges['s_description']);
            if (!empty($prevChanges['s_address'])) $formData->setAddress($prevChanges['s_address']);
            if (!empty($prevChanges['s_postCode'])) $formData->setPostCode($prevChanges['s_postCode']);
            if (!empty($prevChanges['s_city'])) $formData->setCity($prevChanges['s_city']);
            if (!empty($prevChanges['s_cityId'])) $formData->setCityId($prevChanges['s_cityId']);
            if (!empty($prevChanges['s_district'])) $formData->setDistrict($prevChanges['s_district']);
            if (!empty($prevChanges['s_districtId'])) $formData->setDistrictId($prevChanges['s_districtId']);
            if (!empty($prevChanges['s_province'])) $formData->setProvince($prevChanges['s_province']);
            if (!empty($prevChanges['s_provinceId'])) $formData->setProvinceId($prevChanges['s_provinceId']);
            if (!empty($prevChanges['s_country'])) $formData->setCountry($prevChanges['s_country']);
            if (!empty($prevChanges['s_countryId'])) $formData->setCountryId($prevChanges['s_countryId']);
            if (!empty($prevChanges['s_typeOfBusiness'])) $formData->setTypeOfBusiness($prevChanges['s_typeOfBusiness']);
            if (!empty($prevChanges['s_modalUsaha'])) $formData->setModalUsaha($prevChanges['s_modalUsaha']);
            if (!empty($prevChanges['s_totalManpower'])) $formData->setTotalManpower($prevChanges['s_totalManpower']);
            if (!empty($prevChanges['s_bankName'])) $formData->setBankName($prevChanges['s_bankName']);
            if (!empty($prevChanges['s_nomorRekening'])) $formData->setNomorRekening($prevChanges['s_nomorRekening']);
            if (!empty($prevChanges['s_rekeningFile'])) $formData->setRekeningFile($prevChanges['s_rekeningFile']);
            if (!empty($prevChanges['s_isPKP'])) $formData->setIsPKP((bool)$prevChanges['s_isPKP'] === 'pkp' ? 1 : 0);
            if (!empty($prevChanges['s_isUsedErzap'])) $formData->setIsUsedErzap($prevChanges['s_isUsedErzap']);
            if (!empty($prevChanges['s_businessCriteria'])) $formData->setBusinessCriteria($prevChanges['s_businessCriteria']);
            if (!empty($prevChanges['s_rekeningName'])) $formData->setRekeningName($prevChanges['s_rekeningName']);
            if (!empty($prevChanges['s_position'])) $formData->setPosition($prevChanges['s_position']);

            if (!empty($prevChanges['s_sppkpFile'])) {
                $files = explode(',', $prevChanges['s_sppkpFile']);
                $formData->setSppkpFile($files);
            }

            if (!empty($prevChanges['s_deliveryCouriers'])) {
                $couriers = explode(',', $prevChanges['s_deliveryCouriers']);
                $formData->setDeliveryCouriers($couriers);
            }

            // if (!empty($prevChanges['u_photoProfile'])) $user->setPhotoProfile($prevChanges['u_photoProfile']);
            //if (!empty($prevChanges['u_bannerProfile'])) $user->setBannerProfile($prevChanges['u_bannerProfile']);
            if (!empty($prevChanges['u_npwpName'])) $user->setNpwpName($prevChanges['u_npwpName']);
            if (!empty($prevChanges['u_npwpFile'])) $user->setNpwpFile($prevChanges['u_npwpFile']);
            if (!empty($prevChanges['u_npwp'])) $user->setNpwp($prevChanges['u_npwp']);
            if (!empty($prevChanges['u_ktpFile'])) $user->setKtpFile($prevChanges['u_ktpFile']);
            if (!empty($prevChanges['u_user_signature'])) $user->setUserSignature($prevChanges['u_user_signature']);
            if (!empty($prevChanges['u_user_cap'])) $user->setUserStamp($prevChanges['u_user_cap']);
            if (!empty($prevChanges['u_nik'])) $user->setNik($prevChanges['u_nik']);
            if (!empty($prevChanges['u_gender'])) $user->setGender($prevChanges['u_gender']);
            if (!empty($prevChanges['u_email'])) $user->setEmail($prevChanges['u_email']);
            if (!empty($prevChanges['u_phoneNumber'])) $user->setPhoneNumber($prevChanges['u_phoneNumber']);

            if (!empty($prevChanges['u_dob'])) {
                try {
                    $user->setDob(new DateTime($prevChanges['u_dob']));
                } catch (Exception $exception) {

                };
            }

            if (!empty($prevChanges['u_fullName'])) {
                $name = StaticHelper::splitFullName($prevChanges['u_fullName']);
                $user->setFirstName($name['first_name']);
                $user->setLastName($name['last_name']);
            }

            if (!empty($prevChanges['u_suratIjinFile'])) {
                $files = explode(',', $prevChanges['u_suratIjinFile']);
                $user->setSuratIjinFile($files);
            }

            if (!empty($prevChanges['u_dokumenFile'])) {
                $files = explode(',', $prevChanges['u_dokumenFile']);
                $user->setDokumenFile($files);
            }
        }

        if ($formData->getStatus() !== 'DRAFT' &&
            $formData->getStatus() !== 'PENDING' && $formData->getStatus() !== 'VERIFIED'
        ) {
            try {
                $this->checkForInvalidStoreAccess();
            } catch (StoreInactiveException $e) {
                $this->addFlash('warning', $e->getMessage());

                return $this->redirectToRoute('user_dashboard');
            }
        }

        //asumsi store yg register manual belum melengkapi field berikut
        if ($formData->getStatus() === 'ACTIVE' && empty($formData->getFileHash()) ||
            $formData->getStatus() === 'UPDATE' && empty($formData->getFileHash())
        ) {
            $formData->setStatus('ACTIVE_UPDATE');
        }

        $totalCompleteForm = 0;

        if (!empty($formData->getUser()->getTnc())) {
            $totalCompleteForm++;
        }

        if ($formData->getStatus() === 'DRAFT' ||
            $formData->getStatus() === 'VERIFIED' ||
            $formData->getStatus() === 'PENDING' ||
            ($formData->getStatus() === 'ACTIVE_UPDATE')
        ) {

            if (!empty($formData->getName()) &&
                (!empty($formData->getBrand()) ||
                    !empty($formData->getTypeOfBusiness())) &&
                !empty($formData->getAddressLat()) &&
                !empty($formData->getAddressLng())
            ) {
                $totalCompleteForm++;
            }

            if (!empty($formData->getUser()->getNik()) && !empty($formData->getUser()->getKtpFile())) {
                $totalCompleteForm++;
            }

            if (!empty($formData->getModalUsaha()) && $formData->getTotalManPower() !== null) {
                $totalCompleteForm++;
            }

            if ($formData->getStatus() !== 'ACTIVE_UPDATE') {
                if ($formData->getIsPKP() !== null) {
                    if ($formData->getIsPKP() == 0 || ($formData->getIsPKP() && !empty($formData->getSppkpFile()))) {
                        $totalCompleteForm++;
                    }
                }
            }

            if (!empty($formData->getTnc())) {
                $totalCompleteForm += $formData->getTnc();
            }

        } else {
            $totalCompleteForm = 7;
        }

        $flashBag = $this->get('session.flash_bag');
        $rajaOngkir = $this->get(RajaOngkirService::class);
        $tokenId = 'user_store_update';
        $createdAt = $formData->getCreatedAt();

        if ($formData->getRegisteredNumber() === null) {
            $registeredNumber = $this->getRegisterNumber($createdAt);
            $formData->setRegisteredNumber($registeredNumber);
        }

        BreadcrumbService::add(['label' => $this->getTranslation('label.manage_store')]);

        $productCategories = productCategoryConversionData($this->getProductCategoriesFeatured(0, 'no', 'yes'));

        $productCategory = $this->getParentChildProductCategories();

        return $this->view('@__main__/public/user/store/form.html.twig', [
            'form_data' => $formData,
            'errors' => $flashBag->get('errors'),
            'token_id' => $tokenId,
            'province_data' => $rajaOngkir->getProvince(),
            'city_data' => $this->manipulateCitiesData(),
            'text_editor' => true,
            'kesepakatan_date' => $this->getTodayFormat($createdAt),
            'pernyataan_date' => $this->getTodayFormat($createdAt, true),
            'total_complete_form' => $totalCompleteForm,
            'product_categories' => $productCategories,
            'product_category' => $productCategory,
        ]);
    }

    public function update(): RedirectResponse
    {
        /** @var DisbursementRepository $repository */
        $d_repo    = $this->getRepository(Disbursement::class);
        $em = $this->getEntityManager();
        
        $request = $this->getRequest();
        $translator = $this->getTranslator();
        $route = 'user_store_edit';
        if ($request->isMethod('POST')) {
            $formData = $request->request->all();

            $deliveryCouriers = (isset($formData['delivery_couriers']) && is_array($formData['delivery_couriers'])) ? $formData['delivery_couriers'] : [];
            $productCategories = (isset($formData['product_categories']) && is_array($formData['product_categories'])) ? $formData['product_categories'] : [];

            /** @var User $user */
            $user = $this->getUser();
            /** @var Store $store */
            $store = $this->getUserStore();

            //update disbursement yg pending
            $d_pending = $d_repo->getDataByStoreId($this->getUserStore()->getId(), ['status' => 'pending']);
            foreach ($d_pending as $key => $value) {
                $d_update = $d_repo->find($value['id']);
                $d_update->setRekeningName(filter_var($formData['rekening_name'], FILTER_SANITIZE_STRING));
                $d_update->setBankName(filter_var($formData['bank_name'], FILTER_SANITIZE_STRING));
                $d_update->setNomorRekening(filter_var($formData['nomor_rekening'], FILTER_SANITIZE_STRING));
                $em->persist($d_update);
                $em->flush();
                
            }

            $diff = $this->getStoreDiff($store, $formData, [
                'user_id' => (int)$user->getId(),
                'origin' => 'FE',
            ]);

            if (count($diff['diff']) > 0) {
                $store->setPreviousValues($diff['data']);

                if ($store->getStatus() === 'UPDATE' || ($store->getStatus() === 'PENDING' && $store->getIsActive())) {
                    $prevChanges = $store->getPreviousChanges();
                    if (!empty($prevChanges)) {
                        if ((int)$prevChanges['p_lastChangedBy'] === (int)$store->getUser()->getId()) {
                            $store->setPreviousChanges(array_merge($prevChanges, $diff['diff']));
                        } else {
                            $store->setPreviousChanges($diff['diff']);
                        }
                    } else {
                        $store->setPreviousChanges($diff['diff']);
                    }
                } else {
                    $store->setPreviousChanges($diff['diff']);
                }
            }

            $deleteTempFiles = [];
            $allowedBusinessCriteria = array_keys($this->getParameter('business_criteria'));
            $allowedBusinessType = array_keys($this->getParameter('type_of_business'));
            $dirSlug = $user->getDirSlug();
            $newPath = 'uploads/users/' . $dirSlug;

            $store->setName(filter_var($formData['name'], FILTER_SANITIZE_STRING));
            $store->setBrand(filter_var($formData['brand'], FILTER_SANITIZE_STRING));
            $store->setDescription(filter_var($formData['description'], FILTER_SANITIZE_STRING));
            $store->setAddress($formData['address']);
            $store->setPostCode(filter_var($formData['post_code'], FILTER_SANITIZE_STRING));
            $store->setCity(filter_var($formData['city'], FILTER_SANITIZE_STRING));
            $store->setCityId((int)$formData['city_id']);
            $store->setDistrict(filter_var($formData['district'], FILTER_SANITIZE_STRING));
            $store->setDistrictId(0);
            $store->setProvince(filter_var($formData['province'], FILTER_SANITIZE_STRING));
            $store->setProvinceId((int)$formData['province_id']);
            $store->setCountry('ID');
            $store->setCountryId(0);
            $store->setDeliveryCouriers($deliveryCouriers);
            $store->setProductCategories($productCategories);

            if (!empty($formData['lat'])) {
                $store->setAddressLat((float)$formData['lat']);
            }

            if (!empty($formData['lng'])) {
                $store->setAddressLng((float)$formData['lng']);
            }

            if (isset($formData['pkp'])) {
                $isPKP = (int)$formData['pkp'] === 1;
                $store->setIsPKP((bool)$isPKP);
            }

            if (isset($formData['used_erzap'])) {
                $store->setIsUsedErzap(true);
            } else {
                $store->setIsUsedErzap(false);
            }

            if (!empty($formData['position'])) {
                $store->setPosition(filter_var($formData['position'], FILTER_SANITIZE_STRING));
            }

            if ($store->getStatus() === 'VERIFIED' ||
                ($store->getStatus() === 'PENDING' && !$store->getIsActive())
            ) {
                $storeTnc = 0;

                if (isset($formData['tnc_perjanjian_kerjasama']) && $formData['tnc_perjanjian_kerjasama'] === 'yes') {
                    $storeTnc++;
                }

                if (isset($formData['tnc_surat_pernyataan']) && $formData['tnc_surat_pernyataan'] === 'yes') {
                    $storeTnc++;
                }

                $store->setTnc($storeTnc);
            }

            if (isset($formData['user_tnc']) && $formData['user_tnc'] === 'yes') {
                $user->setTnc(1);
            }

            $isPrevStatusPending = false;

            if ($store->getStatus() === 'DRAFT' && $formData['status'] === 'COMPLETE') {
                $store->setIsActive(0);
                $store->setStatus('NEW_MERCHANT');
                $store->setStatusLog('Pengajuan data usaha');

            } else if ($store->getStatus() === 'VERIFIED' && $formData['status'] === 'ACTIVE') {
                $store->setIsActive(1);
                $store->setStatus('ACTIVE');
                $store->setStatusLog('Merchant Aktif');

                if (empty($store->getFileHash())) {
                    $store->setFileHash(StaticHelper::secureRandomCode(16));
                }

            } else if ($store->getStatus() === 'ACTIVE') {
                $store->setIsActive(1);
                $store->setStatus('UPDATE');
                $store->setStatusLog('Update Data');

            } else if ($store->getStatus() === 'PENDING' && !empty($store->getFileHash())) {
                $store->setIsActive(0);
                $store->setStatus('UPDATE');
                $store->setStatusLog('Data telah diubah');

            } else if ($store->getStatus() === 'PENDING' && empty($store->getFileHash())) {
                $store->setIsActive(0);
                $store->setStatus('NEW_MERCHANT');
                $store->setStatusLog('Data telah diubah');
                $isPrevStatusPending = true;

            } else if ($store->getStatus() === 'UPDATE' && $formData['status'] === 'ACTIVE_UPDATE_COMPLETE') {
                $store->setIsActive(0);
                $store->setStatus('UPDATE');
                $store->setStatusLog('Data telah diubah');

//                $store->setFileHash(StaticHelper::secureRandomCode(16));
            }

            if (isset($formData['modal_usaha']) && (float)$formData['modal_usaha'] > 0) {
                $store->setModalUsaha(filter_var($formData['modal_usaha'], FILTER_SANITIZE_NUMBER_FLOAT));
            }

            if (isset($formData['total_manpower'])) {
                $store->setTotalManpower(filter_var($formData['total_manpower'], FILTER_SANITIZE_NUMBER_INT));
            }

            if (isset($formData['rekening_name'])) {
                $store->setRekeningName(filter_var($formData['rekening_name'], FILTER_SANITIZE_STRING));
            }

            if (isset($formData['bank_name'])) {
                $store->setBankName(filter_var($formData['bank_name'], FILTER_SANITIZE_STRING));
            }

            if (isset($formData['nomor_rekening'])) {
                $store->setNomorRekening(filter_var($formData['nomor_rekening'], FILTER_SANITIZE_STRING));
            }

            if (isset($formData['registered_number'])) {
                $store->setRegisteredNumber(filter_var($formData['registered_number'], FILTER_SANITIZE_STRING));
            }

            if (in_array($formData['jenis_usaha'], $allowedBusinessType)) {
                $store->setTypeOfBusiness(filter_var($formData['jenis_usaha'], FILTER_SANITIZE_STRING));
            }

            if (in_array($formData['kriteria_usaha'], $allowedBusinessCriteria)) {
                $store->setBusinessCriteria(filter_var($formData['kriteria_usaha'], FILTER_SANITIZE_STRING));
            }

            if (isset($formData['full_name'])) {
                $name = StaticHelper::splitFullName(filter_var($formData['full_name'], FILTER_SANITIZE_STRING));
                $user->setFirstName($name['first_name']);
                $user->setLastName($name['last_name']);
            }

            if (isset($formData['gender'])) {
                $user->setGender(filter_var($formData['gender'], FILTER_SANITIZE_STRING));
            }

            if (isset($formData['nik'])) {
                $user->setNik(filter_var($formData['nik'], FILTER_SANITIZE_NUMBER_INT));
            }

            if (isset($formData['nama_npwp'])) {
                $user->setNpwpName(filter_var($formData['nama_npwp'], FILTER_SANITIZE_STRING));
            }
            if (isset($formData['no_npwp'])) {
                $user->setNpwp(filter_var($formData['no_npwp'], FILTER_SANITIZE_STRING));
            }

            if (isset($formData['address_npwp'])) {
                $user->setNpwpAddress(filter_var($formData['address_npwp'], FILTER_SANITIZE_STRING));
            }

            if (isset($formData['user_email'])) {
                $user->setEmail(filter_var($formData['user_email'], FILTER_SANITIZE_EMAIL));
            }

            if (isset($formData['user_phone_number'])) {
                $user->setPhoneNumber(filter_var($formData['user_phone_number'], FILTER_SANITIZE_NUMBER_INT));
            }

            $user->setIpAddress();

            if (isset($formData['dob'])) {
                try {
                    $user->setDob(new DateTime($formData['dob']));
                } catch (Exception $e) {
                }
            }

            $userAddress = new UserAddress();
            $userAddress->setUser($user);
            $userAddress->setTitle('Alamat');
            $userAddress->setAddress($formData['address']);
            $userAddress->setPostCode($formData['post_code']);
            $userAddress->setCity($formData['city']);
            $userAddress->setCityId(abs($formData['city_id']));
            $userAddress->setDistrict(null);
            $userAddress->setDistrictId(0);
            $userAddress->setProvince($formData['province']);
            $userAddress->setProvinceId(abs($formData['province_id']));
            $userAddress->setCountry('ID');
            $userAddress->setCountryId(0);

            if (!empty($formData['rekening_img_temp'])) {
                $file = $formData['rekening_img_temp'];
                $deleteTempFiles[] = $file;
                $uploadedFile = $this->handleUploadedFile($file, $newPath);
                $filePath = trim(str_replace('/temp','',$file));
                $filenamePath = ltrim($filePath, '/');
                if (!empty($file)) {
                    $store->setRekeningFile($filenamePath);
                }
            }

            if (!empty($formData['logo_img_temp'])) {
                $file = $formData['logo_img_temp'];
                $deleteTempFiles[] = $file;
                $uploadedFile = $this->handleUploadedFile($file, $newPath);
                $filePath = trim(str_replace('/temp','',$file));
                $filenamePath = ltrim($filePath, '/');
                if (!empty($file)) {
                    $user->setPhotoProfile($filenamePath);
                }
            }

            if (!empty($formData['dashboard_img_temp'])) {
                $file = $formData['dashboard_img_temp'];
                $deleteTempFiles[] = $file;
                $uploadedFile = $this->handleUploadedFile($file, $newPath);
                $filePath = trim(str_replace('/temp','',$file));
                $filenamePath = ltrim($filePath, '/');
                if (!empty($file)) {
                    $user->setBannerProfile($filenamePath);
                }
            }

            if (!empty($formData['npwp_img_temp'])) {
                $file = $formData['npwp_img_temp'];
                $deleteTempFiles[] = $file;
                $uploadedFile = $this->handleUploadedFile($file, $newPath);
                $filePath = trim(str_replace('/temp','',$file));
                $filenamePath = ltrim($filePath, '/');
                if (!empty($file)) {
                    $user->setNpwpFile($filenamePath);
                }
            }

            if (!empty($formData['ktp_img_temp'])) {
                $file = $formData['ktp_img_temp'];
                $deleteTempFiles[] = $file;
                $uploadedFile = $this->handleUploadedFile($file, $newPath);
                $filePath = trim(str_replace('/temp','',$file));
                $filenamePath = ltrim($filePath, '/');
                if (!empty($file)) {
                    $user->setKtpFile($filenamePath);
                }
            }

            if (!empty($formData['ttd_img_temp'])) {
                $file = $formData['ttd_img_temp'];
                $deleteTempFiles[] = $file;
                $uploadedFile = $this->handleUploadedFile($file, $newPath);
                $filePath = trim(str_replace('/temp','',$file));
                $filenamePath = ltrim($filePath, '/');
                if (!empty($file)) {
                    $user->setUserSignature($filenamePath);
                }
            }

            if (!empty($formData['cap_img_temp'])) {
                $file = $formData['cap_img_temp'];
                $deleteTempFiles[] = $file;
                $uploadedFile = $this->handleUploadedFile($file, $newPath);
                $filePath = trim(str_replace('/temp','',$file));
                $filenamePath = ltrim($filePath, '/');
                if (!empty($file)) {
                    $user->setUserStamp($filenamePath);
                }
            }

            $suratIjinFiles = json_decode($formData['surat-ijin-usaha-img'][0]);
            $documents = json_decode($formData['dokumen-tambahan-img'][0]);
            $sppkpImages = json_decode($formData['sppkp_img'][0]);
            $suratFiles = [];
            $docFiles = [];
            $sppkpFiles = [];

            if (count($suratIjinFiles) > 0) {
                $currentSuratIjinFiles = $user->getSuratIjinFile();

                if (empty($currentSuratIjinFiles)) {
                    foreach ($suratIjinFiles as $file) {
                        $deleteTempFiles[] = $file;
                        $uploadedFile = $this->handleUploadedFile($file, $newPath);
                        $filePath = trim(str_replace('/temp','',$file));
                        $filenamePath = ltrim($filePath, '/');
                        if (!empty($file)) {
                            $suratFiles[] = $filenamePath;
                        }
                    }
                } else {
                    foreach ($suratIjinFiles as $suratIjinFile) {
                        if (!in_array($suratIjinFile, $currentSuratIjinFiles)) {
                            $uploadedFile = $this->handleUploadedFile($suratIjinFile, $newPath);
                            $filePath = trim(str_replace('/temp','',$suratIjinFile));
                            $filenamePath = ltrim($filePath, '/');
                            if (!empty($file)) {
                                $suratFiles[] = $filenamePath;
                            }
                        }
                    }

                    foreach ($currentSuratIjinFiles as $currentSuratIjinFile) {
                        if (in_array($currentSuratIjinFile, $suratIjinFiles)) {
                            $suratFiles[] = $currentSuratIjinFile;
                        }
                    }
                }
            }

            if (count($documents) > 0) {

                $currentDokumenTambahanFiles = $user->getDokumenFile();

                if (empty($currentDokumenTambahanFiles)) {
                    foreach ($documents as $file) {
                        $deleteTempFiles[] = $file;
                        $uploadedFile = $this->handleUploadedFile($file, $newPath);
                        $filePath = trim(str_replace('/temp','',$file));
                        $filenamePath = ltrim($filePath, '/');
                        if (!empty($file)) {
                            $docFiles[] = $filenamePath;
                        }
                    }
                } else {
                    foreach ($documents as $document) {
                        if (!in_array($document, $currentDokumenTambahanFiles)) {
                            $uploadedFile = $this->handleUploadedFile($document, $newPath);
                            $filePath = trim(str_replace('/temp','',$document));
                            $filenamePath = ltrim($filePath, '/');
                            if (!empty($file)) {
                                $docFiles[] = $filenamePath;
                            }
                        }
                    }

                    foreach ($currentDokumenTambahanFiles as $currentDokumenTambahanFile) {
                        if (in_array($currentDokumenTambahanFile, $documents)) {
                            $docFiles[] = $currentDokumenTambahanFile;
                        }
                    }
                }
            } else {
                if (!empty($currentDokumenTambahanFiles)) {
                    $docFiles = [];
                }
            }

            if (count($sppkpImages) > 0) {
                $currentSppkpFiles = $store->getSppkpFile();

                if (empty($currentSppkpFiles)) {
                    foreach ($sppkpImages as $file) {
                        $deleteTempFiles[] = $file;
                        $uploadedFile = $this->handleUploadedFile($file, $newPath);
                        $filePath = trim(str_replace('/temp','',$file));
                        $filenamePath = ltrim($filePath, '/');
                        if (!empty($file)) {
                            $sppkpFiles[] = $filenamePath;
                        }
                    }
                } else {
                    foreach ($sppkpImages as $sppkpImage) {
                        if (!in_array($sppkpImage, $currentSppkpFiles)) {
                            $uploadedFile = $this->handleUploadedFile($sppkpImage, $newPath);
                            $filePath = trim(str_replace('/temp','',$sppkpImage));
                            $filenamePath = ltrim($filePath, '/');
                            if (!empty($file)) {
                                $sppkpFiles[] = $filenamePath;
                            }
                        }
                    }

                    foreach ($currentSppkpFiles as $currentSppkpFile) {
                        if (in_array($currentSppkpFile, $sppkpImages)) {
                            $sppkpFiles[] = $currentSppkpFile;
                        }
                    }
                }
            }

            if (count($suratFiles) > 0) {
                $user->setSuratIjinFile($suratFiles);
            }

            $user->setDokumenFile($docFiles);

            if (count($sppkpFiles) > 0) {
                $store->setSppkpFile($sppkpFiles);
            }

            $validator = $this->getValidator();
            $storeErrors = $validator->validate($store);
            $userErrors = $validator->validate($user);
            $userAddressError = $validator->validate($userAddress);

//            if (count($deliveryCouriers) < 1) {
//                $message = $translator->trans('global.not_empty', [], 'validators');
//                $constraint = new ConstraintViolation($message, $message, [], $store, 'deliveryCouriers', '', null, null, new Assert\NotBlank(), null);
//
//                $storeErrors->add($constraint);
//            }

            if (count($storeErrors) === 0) {
                $em = $this->getEntityManager();
                $em->persist($user);
                $em->flush();

                $em->persist($store);
                $em->flush();

                $em->persist($userAddress);
                $em->flush();

                if (count($diff['diff']) > 0) {
                    $storeViewUrl = $this->generateUrl('admin_store_view', ['id' => $store->getId()], UrlGeneratorInterface::ABSOLUTE_URL);

                    $notification = new Notification();
                    $notification->setSellerId(0);
                    $notification->setBuyerId(0);
                    $notification->setIsSentToSeller(false);
                    $notification->setIsSentToBuyer(false);
                    $notification->setIsAdmin(true);
                    $notification->setTitle($this->getTranslation('notifications.store_change'));
                    $notification->setContent($this->getTranslation('notifications.store_change_text', ['%name%' => $store->getName()]));
                    $notification->setUrl($storeViewUrl);

                    $em->persist($notification);
                }

                $message = 'message.success.user_store_updated';

                if ($store->getStatus() === 'NEW_MERCHANT' && !$isPrevStatusPending) {

                    $message = 'message.info.store_inactive';
                    $route = 'user_dashboard';

                } else if ($store->getStatus() === 'DRAFT' || $store->getStatus() === 'VERIFIED') {
                    $message = 'message.success.user_store_temp';
                } else if ($store->getStatus() === 'ACTIVE') {
                    $message = 'message.success.user_store_active';
                    $route = 'user_dashboard';

                    $storeViewUrl = $this->generateUrl('admin_store_view', ['id' => $store->getId()], UrlGeneratorInterface::ABSOLUTE_URL);

                    //--- Send email notification to admin
                    $mailToAdmin = $this->get(BaseMail::class);
                    $mailToAdmin->setMailSubject($this->getTranslation('message.info.new_store_registered'));
                    $mailToAdmin->setMailTemplate('@__main__/email/new_store_registered.html.twig');
                    $mailToAdmin->setToAdmin();
                    $mailToAdmin->setMailData([
                        'link' => $storeViewUrl,
                    ]);
                    $mailToAdmin->send();
                    //--- Send email notification to admin

                    //--- Send email notification to user
                    $pernyataanViewUrl = $this->generateUrl('agreement', ['type' => 'pernyataan', 'filehash' => $store->getFileHash()], UrlGeneratorInterface::ABSOLUTE_URL);
                    $kesepakatanViewUrl = $this->generateUrl('agreement', ['type' => 'kesepakatan', 'filehash' => $store->getFileHash()], UrlGeneratorInterface::ABSOLUTE_URL);

                    $mailToUser = $this->get(BaseMail::class);
                    $mailToUser->setMailSubject($this->getTranslation('message.info.new_store_registered'));
                    $mailToUser->setMailTemplate('@__main__/email/new_store_registered_seller.html.twig');
                    $mailToUser->setMailSender(getenv('MAIL_SENDER'));
                    $mailToUser->setMailRecipient($user->getEmail());
                    $mailToUser->setMailData([
                        'link' => $storeViewUrl,
                        'name' => $user->getFirstName(),
                        'file_links' => [
                            $pernyataanViewUrl,
                            $kesepakatanViewUrl
                        ]
                    ]);
                    $mailToUser->send();
                    //--- Send email notification to user

                } else if ($isPrevStatusPending) {
                    $message = 'message.info.store_inactive';
                    $route = 'user_dashboard';
                }

                $this->addFlash(
                    'success',
                    $translator->trans($message)
                );

            } else {
                $errors = [];

                foreach ($storeErrors as $error) {
                    $errors[$error->getPropertyPath()] = $error->getMessage();
                }

                foreach ($userErrors as $error) {
                    $errors[$error->getPropertyPath()] = $error->getMessage();
                }

                foreach ($userAddressError as $error) {
                    $errors[$error->getPropertyPath()] = $error->getMessage();
                }

                $flashBag = $this->get('session.flash_bag');
                $flashBag->set('errors', $errors);

            }
        }

        return $this->redirectToRoute($route);
    }

    public function getSubdistrict($cityId)
    {

        $this->isAjaxRequest();

        $rajaOngkir = $this->get(RajaOngkirService::class);

        $res = $rajaOngkir->getSubDistrict($cityId);

        return $this->json($res, 200);
    }

    public function downloadAgreement($type, $filehash)
    {
        $allowedTypes = ['kesepakatan', 'pernyataan','tnc-merchant'];
        $fileHash = filter_var($filehash, FILTER_SANITIZE_STRING);

        if (!in_array($type, $allowedTypes) && strlen($fileHash) !== 32) {
            throw new NotFoundHttpException();
        }

        $repository = $this->getRepository(Store::class);
        $store = $repository->findOneBy(['fileHash' => $fileHash]);

        if (!$store) {
            throw new NotFoundHttpException();
        }

        $product_category = [];

        if ($type === 'tnc-merchant') {
            $product_category = $this->getParentChildProductCategories();
        }

        $data = [
            'kesepakatan_number' => $store->getRegisteredNumber(),
            'kesepakatan_date' => $this->getTodayFormat($store->getCreatedAt()),
            'pernyataan_date' => $this->getTodayFormat($store->getCreatedAt(), true),
            'nama' => $store->getUser()->getFirstName() . ' ' . $store->getUser()->getLastName(),
            'jabatan' => ucfirst(strtolower($store->getPosition())),
            'nik' => $store->getUser()->getNik(),
            'phone_number' => $store->getUser()->getPhoneNumber(),
            'merchant_name' => $store->getName(),
            'alamat' => $store->getAddress(),
            'city' => $store->getCity(),
            'product_category' => $product_category,
        ];

        $fileName = sprintf('%s.pdf', $type);
        $font = 'Times New Roman';
        $paperSize = 'A4';
        $paperOrientation = 'portrait';

        $options = new Options();
        $options->set('defaultFont', $font);

        $pdf = new Dompdf($options);

        $agreementFile = $this->renderView(sprintf('@__main__/public/user/store/download/%s.html.twig', $type), $data);

        $agreement = $pdf;
        $agreement->loadHtml($agreementFile);
        $agreement->setPaper($paperSize, $paperOrientation);
        $agreement->render();

        return new Response($agreement->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename=' . $fileName,
        ]);

    }

}
