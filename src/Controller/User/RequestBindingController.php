<?php

namespace App\Controller\User;

use App\Controller\PublicController;
use App\Entity\User;
use App\Entity\BpdRequestBinding;

use DateTime;
use DateTimeZone;
use App\Service\BreadcrumbService;
use App\Utility\CustomPaginationTemplate;
use Exception;
use Pagerfanta\Adapter\DoctrineORMAdapter;
use Pagerfanta\Pagerfanta;
use Symfony\Component\HttpFoundation\RedirectResponse;
use App\Helper\StaticHelper;
use Symfony\Component\Routing\Annotation\Route;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\Response;
use Carbon\Carbon;
use App\Service\BpdSnapService;
use App\Entity\AccessTokenBpd;


class RequestBindingController extends PublicController
{

    public function index()
    {
        /** @var User $user */
        $user = $this->getUser();

        if ($user->getSubRole() != "TREASURER" && $user->getSubRole() != "PPK") {
            return $this->redirectToRoute('login');
        }

        $bpdReqBindingRepository = $this->getRepository(BpdRequestBinding::class);
        $request = $this->getRequest();
        $bindings = $bpdReqBindingRepository->findBy(['user' => $user->getId()]);
        // $created = $datas[0]->getCreated();
        // $created->setTimezone(new \DateTimeZone('Asia/Makassar'));
        // dd($created->format('Y-m-d\TH:i:sP'));
        // str_pad(id, 12, "0", STR_PAD_LEFT)
        $token = $this->handleGetToken()->getToken();

        $bpdSnap = $this->get(BpdSnapService::class);
        $responseJson = $bpdSnap->listBinding(['token'=> $token, 'externalId'=> gmdate("His"), 'partnerReferenceNo'=> $this->generateUniqueId()]);
        $responseJson = json_decode($responseJson, true);

        // filter list binding data
        // jika cpan sudah ada di list binding, ambil data dari list binding (API)
        // sebaliknya, ambil data dari db lokal
        $apiRequestBindingDatas = [];
        $cpans = [];
        // dd($responseJson);
        foreach ($responseJson['additionalInfo']['data'] as $item) {
            $filteredData = $bpdReqBindingRepository->findOneBy(['user' => $user->getId(), 'customerPan'=> $item['customerPan']]);    
            if($filteredData){
                $newData = [];
                $newData['customerPan'] = $filteredData->getCustomerPan();
                $newData['ott'] = $filteredData->getOtt();
                $newData['id'] = $filteredData->getId();
                // $newData['status'] = $item['status'];
                $newData['linkId'] = $item['linkId'];
                $newData['issuerToken'] = $item['issuerToken'];
                $newData['acquirerName'] = $item['acquirerName'];
                $newData['issuerName'] = $item['issuerName'];
                $apiRequestBindingDatas[] = $newData;

                // set status sesuai dengan response api list binding
                // $filteredData->setStatus($item['status']);
                $filteredData->setAcquirerName($item['acquirerName']);
                $filteredData->setIssuerName($item['issuerName']);
                $em = $this->getEntityManager();
                $em->persist($filteredData);
                $em->flush();

                $cpans[] = $item['customerPan'];
            }
        }

        // unbinding issuer status binding = 1 & tidak ada di list binding api
        $filteredUnbindingIssuer = array_map(function($binding) use ($cpans) {
            if(!in_array($binding->getCustomerPan(), $cpans) && $binding->getStatus() == '1'){
                $em = $this->getEntityManager();
                $em->remove($binding);
                $em->flush();
                return $binding;
            }
        }, $bindings);

        $filteredUnbindingIssuer = array_filter($filteredUnbindingIssuer);

        $bindings = $bpdReqBindingRepository->findBy(['user' => $user->getId()]);
        
        $dbRequestBindingDatas = [];
        foreach($bindings as $binding){
            $newData = [];
            $newData['customerPan'] = $binding->getCustomerPan();
            $newData['ott'] = $binding->getOtt();
            $newData['id'] = $binding->getId();
            $newData['status'] = intval($binding->getStatus());
            $newData['linkId'] = '';
            $newData['issuerToken'] = '';
            $newData['accuirerName'] = '';
            $newData['issuerName'] = '';
            $dbRequestBindingDatas[] = $newData;
            
        }

        // Buat array kosong untuk menyimpan hasil penggabungan
        $mergedBindingDatas = [];

        // Iterasi melalui $apiRequestBindingDatas untuk memasukkan data awal ke $mergedBindingDatas
        foreach ($apiRequestBindingDatas as $apiData) {
            $mergedBindingDatas[$apiData['customerPan']] = $apiData;
        }

        // Iterasi melalui $dbRequestBindingDatas untuk menambahkan data ke $mergedBindingDatas jika customerPan belum ada
        foreach ($dbRequestBindingDatas as $dbData) {
            if (!isset($mergedBindingDatas[$dbData['customerPan']])) {
                $mergedBindingDatas[$dbData['customerPan']] = $dbData;
            }
        }

        $mergedBindingDatas = array_values($mergedBindingDatas);

        $tokenId = 'user_requestbinding_delete';
        
        BreadcrumbService::add(['label' => $this->getTranslation('label.request_binding')]);
        return $this->view('@__main__/public/user/request_binding/index.html.twig',[
            'datas' => $mergedBindingDatas,
            'token_id' => $tokenId
        ]);
    }

    function generateUniqueId(){
        $min = 100000000000; // Angka minimum (12 digit pertama)
        $max = 999999999999; // Angka maksimum (12 digit terakhir)
        $id = mt_rand($min, $max);
        return $id;
    }

    // 1. cek ke tabel access_token_bpd data terakhir
    // 2. cek expired token
    // 3. jika expired generate token baru
    // 4. jika belum expired ambil token terakhir
    function handleGetToken(){
        $accessTokenRepository = $this->getRepository(AccessTokenBpd::class);
        $bpdSnap = $this->get(BpdSnapService::class);
        $dataAccess = $accessTokenRepository->findOneBy([], ['id' => 'DESC']);
        $dataExpired = new DateTime($dataAccess->getExpiredDate()->format('Y-m-d H:i:s'), new DateTimeZone('Asia/Makassar'));
        $dataNow = Carbon::now('Asia/Makassar')->format('Y-m-d H:i:s');
        $dataNow = new DateTime($dataNow, new DateTimeZone('Asia/Makassar'));
        $hasToken = false;
        $getToken = null;
        if ($dataExpired > $dataNow) {
            $hasToken = true;
            $getToken = $dataAccess;
        }
        $em = $this->getEntityManager();

        if (!$hasToken) {
            $accessToken = $bpdSnap->accessToken();
            $accessToken = json_decode($accessToken);
            if (isset($accessToken->responseMessage) && $accessToken->responseMessage == "Success") {
                $expiredDate = Carbon::now('Asia/Makassar')->addSeconds($accessToken->expiresIn)->format('Y-m-d H:i:s');
                $covertExpired = new DateTime($expiredDate, new DateTimeZone('Asia/Makassar'));
                $getToken = new AccessTokenBpd();
                $getToken->setToken($accessToken->accessToken);
                $getToken->setExpiredDate($covertExpired);
                $getToken->setCreatedAt();
    
                $em->persist($getToken);
                $em->flush();
            } 
        }

        return $getToken;
    }

    public function new()
    {
        /** @var User $user */
        $user = $this->getUser();
        $flashBag = $this->get('session.flash_bag');
        $tokenId = 'user_requestbinding_save';

        BreadcrumbService::add(['label' => $this->getTranslation('label.add_request_binding')]);

        return $this->view('@__main__/public/user/request_binding/form.html.twig', [
            'form_data' => $flashBag->get('form_data'),
            'errors' => $flashBag->get('errors'),
            'token_id' => $tokenId,
        ]);
    }

    public function save(): RedirectResponse
    {
        $request = $this->getRequest();
        $flashBag = $this->get('session.flash_bag');
        $route = 'user_requestbinding_index';

        if ($request->isMethod('POST')) {
            $formData = $request->request->all();
            /** @var User $user */
            $user = $this->getUser();
            $customerpan = $formData['customerpan'];
            $ott = $formData['ott'];

            $requestBinding = new BpdRequestBinding();
            $requestBinding->setCustomerPan(filter_var($customerpan, FILTER_SANITIZE_STRING));
            $requestBinding->setOtt(filter_var($ott, FILTER_SANITIZE_STRING));
            $requestBinding->setStatus(0);
            $uuid = Uuid::uuid4()->toString();
            $unique = $this->generateUniqueId();
            $requestBinding->setPartnerReferenceNo($unique);

            $validator = $this->getValidator();
            $requestBindingErrors = $validator->validate($requestBinding);

            // custom unique validation
            $existingBpdRequestBindingOtt = $this->getRepository(BpdRequestBinding::class)->findOneBy(['ott' => $ott]);
            $isAllowedOtt = true;
            if ($existingBpdRequestBindingOtt) {
                $isAllowedOtt = false;
            }

            $existingBpdRequestBindingCPan = $this->getRepository(BpdRequestBinding::class)->findOneBy(['customerPan' => $customerpan]);
            $isAllowedCustomerPan = true;
            if ($existingBpdRequestBindingCPan) {
                $isAllowedCustomerPan = false;
            }
            // ========================

            if (count($requestBindingErrors) === 0 && $isAllowedOtt && $isAllowedCustomerPan) {
                $requestBinding->setUser($user);

                $em = $this->getEntityManager();
                $em->persist($requestBinding);

                
                $created = $requestBinding->getCreated();
                $created->setTimezone(new \DateTimeZone('Asia/Makassar'));

                $token = $this->handleGetToken()->getToken();
                $bpdSnap = $this->get(BpdSnapService::class);
                $payloads = [
                    'token'=> $token, 
                    'externalId'=> gmdate("His"), 
                    'partnerReferenceNo'=> $unique,
                    'custIdMerchant'=> $user->getId(),
                    'ott'=> $ott,
                    'customerPan'=> $customerpan,
                    'created'=> $created->format('Y-m-d\TH:i:sP')
                ];
                
                $responseBinding = $bpdSnap->requestBinding($payloads);

                $apiData = json_decode($responseBinding, true);
                if($apiData['responseCode'] == '2000100'){
                    $em->flush();
                    $this->addFlash(
                        'success',
                        $this->getTranslator()->trans('message.success.user_requestbinding_created')
                    );
                }else{
                    $this->addFlash(
                        'error',
                        $this->getTranslator()->trans('message.error.user_requestbinding_error')
                    );
                }

            } else {
                $errors = [];
                $route = 'user_requestbinding_new';
                if($isAllowedOtt == false){
                    $errors['ott'] = $this->getTranslator()->trans('message.error.general_unique');
                }
                if($isAllowedCustomerPan == false){
                    $errors['customerPan'] = $this->getTranslator()->trans('message.error.general_unique');
                }
                foreach ($requestBindingErrors as $error) {
                    $errors[$error->getPropertyPath()] = $error->getMessage();
                }

                $flashBag->set('form_data', $formData);
                $flashBag->set('errors', $errors);
            }
        }

        return $this->redirectToRoute($route);
    }

    public function updateCpan(): RedirectResponse
    {
        $request = $this->getRequest();
        $flashBag = $this->get('session.flash_bag');
        $route = 'user_order_cc_payment';

        if ($request->isMethod('POST')) {
            $formData = $request->request->all();
            /** @var User $user */
            $user = $this->getUser();
            $ott = $formData['ott'];
            $binding_id = $formData['binding_id'];
            $shared_id = $formData['shared_id'];
            // dd($ott, $binding_id);
            $reqBpdRepository = $this->getRepository(BpdRequestBinding::class);
            $em = $this->getEntityManager();

            $dataBinding = $reqBpdRepository->findOneBy(['id'=> $binding_id]);
            // dd($dataBinding->getCustomerPan());
            if($dataBinding){
                $dataBinding->setNotes('');
                $dataBinding->setIsExpired(0);
                $em->persist($dataBinding);
            }

            $customerpan = $dataBinding->getCustomerPan();

            $existingBpdRequestBindingCPan = $this->getRepository(BpdRequestBinding::class)->findOneBy(['customerPan' => $customerpan]);
            $isAllowedCustomerPan = false;
            if ($existingBpdRequestBindingCPan) {
                $isAllowedCustomerPan = true;
            }
            // ========================
            if ($isAllowedCustomerPan) {
                // $requestBinding->setUser($user);

                // $em = $this->getEntityManager();
                // $em->persist($requestBinding);

                $created = $dataBinding->getCreated();
                // dd($partnerReferenceNo, $created);
                // $created->setTimezone(new \DateTimeZone('Asia/Makassar'));

                $token = $this->handleGetToken()->getToken();
                $bpdSnap = $this->get(BpdSnapService::class);
                $unique = $this->generateUniqueId();
                $payloads = [
                    'token'=> $token, 
                    'externalId'=> gmdate("His"), 
                    'partnerReferenceNo'=> $unique,
                    'custIdMerchant'=> $user->getId(),
                    'ott'=> $ott,
                    'customerPan'=> $customerpan,
                    'created'=> $created->format('Y-m-d\TH:i:sP')
                ];
                
                $responseBinding = $bpdSnap->requestBinding($payloads);
                $apiData = json_decode($responseBinding, true);
                if($apiData['responseCode'] == '2000100'){
                    $em->flush();
                    $this->addFlash(
                        'success',
                        $this->getTranslator()->trans('message.success.user_requestbinding_created')
                    );
                }else{
                    if ($apiData['responseMessage'] == "Invalid Token (RC 55)" && $apiData['responseCode'] == "404NS55"){
                        $this->addFlash(
                            'error',
                            'Update binding gagal dikarenakan token yang anda inputkan Invalid.',
                        );
                        
                        $this->addFlash(
                            'additional_info',
                            ['is_expired'=> true]
                        );
                    }else{
                        $this->addFlash(
                            'error',
                            $apiData['responseMessage'],
                        );
                    }
                }

            }else{
                $this->addFlash(
                    'error',
                    'cpan tidak terdaftar dalam sistem.'
                );
            }
        }

        return $this->redirectToRoute($route, ['id' => $shared_id]);
    }

    public function delete()
    {
        $route = 'user_requestbinding_index';
        $request = $this->getRequest();

        if ($request->isMethod('POST')) {
            $id = abs($request->request->get('id', '0'));
            /** @var User $user */
            $user = $this->getUser();
            $response = [
                'deleted' => false,
            ];

            if ($user->getId()) {
                
                $repository = $this->getRepository(BpdRequestBinding::class);
                $requestBinding = $repository->findOneBy([
                    'id' => $id
                ]);


                $token = $this->handleGetToken()->getToken();
                $bpdSnap = $this->get(BpdSnapService::class);
                $responseJson = $bpdSnap->listBinding(['token'=> $token, 'externalId'=> gmdate("His"), 'partnerReferenceNo'=> $this->generateUniqueId()]);
                $responseJson = json_decode($responseJson, true);
                $data = [];
                foreach ($responseJson['additionalInfo']['data'] as $item) {
                    // $filteredData = $this->getRepository(BpdRequestBinding::class)->findOneBy(['customerPan'=> $item['customerPan']]);
                    if($requestBinding->getCustomerPan() == $item['customerPan']){
                        $data = [
                            'issuerToken' =>$item['issuerToken'],
                            'linkId' =>$item['linkId']
                        ];
                        break;
                    }
                }

                if ($requestBinding) {
                    $em = $this->getEntityManager();

                    $token = $this->handleGetToken()->getToken();
                    $isDeleted = true;
                    // jika memiliki linkid dan issuer token, request unbinding
                    if( !empty($data['issuerToken']) && !empty($data['linkId']) ){
                        $customerPan = $requestBinding->getCustomerPan();
                        $issuerToken = $data['issuerToken'];
                        $linkId = $data['linkId'];
                        $payloads = [
                            'token'=> $token, 
                            'issuerToken' => $issuerToken,
                            'externalId'=> gmdate("His"), 
                            'partnerReferenceNo'=> $this->generateUniqueId(),
                            'customerPan'=> $customerPan,
                            'linkId'=> $linkId
                        ];

                        $responseBinding = $bpdSnap->requestUnbinding($payloads);
                        $apiData = json_decode($responseBinding, true);
                        if($apiData['responseCode'] != '2000500'){
                            $isDeleted == false;
                        }
                    }

                    if($isDeleted == true){
                        $em->remove($requestBinding);
                        $em->flush();
                        $this->addFlash(
                            'success',
                            $this->getTranslator()->trans('message.success.user_requestbinding_deleted')
                        );
                    }else{
                        $this->addFlash(
                            'error',
                            $this->getTranslator()->trans('message.error.user_requestbinding_deleted')
                        );
                    }
                }
            }
        }

        return $this->redirectToRoute($route);
    }

}
