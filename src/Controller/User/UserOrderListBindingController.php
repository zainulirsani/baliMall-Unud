<?php

namespace App\Controller\User;

use App\Controller\PublicController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use App\Service\BpdSnapService;
use App\Entity\BpdRequestBinding;
use App\Entity\AccessTokenBpd;
use App\Entity\User;
use DateTime;
use Carbon\Carbon;
use DateTimeZone;

class UserOrderListBindingController extends PublicController
{
    // ajax
    public function handleGetListBinding(Request $request): JsonResponse
    {

        $reqBpdRepository = $this->getRepository(BpdRequestBinding::class);
        /** @var User $user */
        $user = $this->getUser();

        $accessTokenRepository = $this->getRepository(AccessTokenBpd::class);
        $bpdSnap = $this->get(BpdSnapService::class);
        
        $data = $request->request->all(); // Jika menggunakan metode POST
        $newDatas = [];
        $success = true;
        $message = 'Sukses';
        try {
            $responseJson = $bpdSnap->listBinding(['token'=> $this->handleGetToken()->getToken(), 'externalId'=> gmdate("His"), 'partnerReferenceNo'=> $this->generateUniqueId()]);
            $responseJson = json_decode($responseJson, true);

            // dd($responseJson);
            foreach ($responseJson['additionalInfo']['data'] as $item) {
                $filteredData = $this->getRepository(BpdRequestBinding::class)->findOneBy(['user' => $user->getId(), 'customerPan'=> $item['customerPan']]);
                if($filteredData){
                    $newData = [];
                    $filteredData->setIssuerToken($item['issuerToken']);
                    $filteredData->setLinkId($item['linkId']);
                    $filteredData->setStatus(1);
                    $filteredData->setAcquirerName($item['acquirerName']);
                    $filteredData->setIssuerName($item['issuerName']);
                    $em = $this->getEntityManager();
                    $em->persist($filteredData);
                    $em->flush();

                    $newData['customerPan'] = $item['customerPan'];
                    $newData['ott'] = $filteredData->getOtt();
                    $newData['id'] = $filteredData->getId();
                    $newData['issuerToken'] = $item['issuerToken'];
                    $newData['linkId'] = $item['linkId'];
                    $newData['issuerName'] = $item['issuerName'];
                    $newData['notes'] = $filteredData->getNotes();
                    $newData['isExpired'] = $filteredData->getIsExpired();
                    $newDatas[] = $newData;
                }
            }

        } catch (\Exception $e) {
            $success = false;
            $message = 'Terjadi kesalahan saat memproses request: ' . $e->getMessage();
        }
        
        return new JsonResponse([
            'success' => $success,
            'message' => $message,
            'datas' => $newDatas
        ]);
        
    }

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

    function generateUniqueId(){
        $min = 100000000000; // Angka minimum (12 digit pertama)
        $max = 999999999999; // Angka maksimum (12 digit terakhir)
        $id = mt_rand($min, $max);
        return $id;
    }
}