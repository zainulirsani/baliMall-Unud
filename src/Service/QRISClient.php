<?php

namespace App\Service;

use App\Exception\HttpClientException;

class QRISClient
{
    private $baseUrl;
    private $merchantName;
    private $merchantPAN;
    private $terminalUser;
    private $key;
    private $url;
    private $contentType;
    private $parameters;

    public function __construct()
    {
        $this->baseUrl = getenv('QRIS_BASE_URL');
        $this->merchantName = getenv('QRIS_MERCHANT_NAME');
        $this->merchantPAN = getenv('QRIS_MERCHANT_PAN');
        $this->terminalUser = getenv('QRIS_TERMINAL_USER');
        $this->key = getenv('QRIS_KEY');
        $this->contentType = 'application/json';
        $this->parameters = [
            'merchantPan' => $this->merchantPAN,
            'terminalUser' => $this->terminalUser,
        ];

    }

    public function setRequestParameters(array $data, string $type = 'generate'): void
    {
        switch ($type) {
            case 'generate':
                $amount = number_format($data['amount'],2,'.','') ?? '0.00';
                $billNumber = $data['billNumber'] ?? '';

                $this->url = $this->baseUrl.'/generateQrisPost';
                $this->parameters['amount'] = $amount;
                $this->parameters['billNumber'] = $billNumber;
                $this->parameters['merchantName'] = $this->merchantName;
                $this->parameters['hashcodeKey'] = $this->generateHashCodeKey([
                    $this->merchantPAN,
                    $this->terminalUser,
                    $billNumber,
                    $this->key,
                ]);

                break;
            case 'status_check':
                $qrValue = $data['qrValue'] ?? '';

                $this->url = $this->baseUrl.'/getTrxByQrString';
                $this->parameters['qrValue'] = $qrValue;
                $this->parameters['hashcodeKey'] = $this->generateHashCodeKey([
                    $this->merchantPAN,
                    $this->terminalUser,
                    $qrValue,
                    $this->key,
                ]);

                break;
            case 'history_list':
                $startDate = $data['startDate'] ?? ''; // Format: dmY [01072020] (max diff 31 days)
                $endDate = $data['endDate'] ?? ''; // Format: dmY [01072020] (max diff 31 days)

                $this->url = $this->baseUrl.'/historyMerchantPost';
                $this->parameters['startDate'] = $startDate;
                $this->parameters['endDate'] = $endDate;
                $this->parameters['hashcodeKey'] = $this->generateHashCodeKey([
                    $this->merchantPAN,
                    $this->terminalUser,
                    $startDate,
                    $endDate,
                    $this->key,
                ]);

                break;
            case 'history_detail':
            case 'refund':
                $trxId = $data['trxId'] ?? '';

                if ($type === 'history_detail') {
                    $this->url = $this->baseUrl.'/detailHistoryMerchantPost';
                } elseif ($type === 'refund') {
                    $this->url = $this->baseUrl.'/refundPost';
                }

                $this->parameters['trxId'] = $trxId;
                $this->parameters['hashcodeKey'] = $this->generateHashCodeKey([
                    $this->merchantPAN,
                    $this->terminalUser,
                    $trxId,
                    $this->key,
                ]);

                break;
            case 'history_qr':
                $startDate = $data['startDate'] ?? ''; // Format: dmY [01072020] (max diff 7 days)
                $endDate = $data['endDate'] ?? ''; // Format: dmY [01072020] (max diff 7 days)
                $status = $data['status'] ?? ''; // ['SUCCEED', 'FAILED', 'REFUNDED', 'TO_REFUND']

                $this->url = $this->baseUrl.'/historyQrInstitutePost';
                $this->parameters['startDate'] = $startDate;
                $this->parameters['endDate'] = $endDate;
                $this->parameters['status'] = $status;
                $this->parameters['hashcodeKey'] = $this->generateHashCodeKey([
                    $this->merchantPAN,
                    $this->terminalUser,
                    $startDate,
                    $endDate,
                    $this->key,
                ]);

                break;
            case 'va_inquiry_trf_out':
            case 'va_posting_trf_out':
                $accountNumber = $data['accountNumber'] ?? '';
                $amount = $data['amount'] ?? '';
                $dateTime = $data['dateTime'] ?? ''; // Format: YmdHis
                $referenceNumber = $data['referenceNumber'] ?? '';
                $terminalType = $data['terminalType'] ?? '';
                $terminalId = $data['terminalId'] ?? '';
                $destinationBankCode = 129;
                $destinationAccountNumber = $data['destinationAccountNumber'] ?? '';

                $this->contentType = 'application/x-www-form-urlencoded';
                $this->parameters['accountNumber'] = $accountNumber;
                $this->parameters['amount'] = $amount;
                $this->parameters['dateTime'] = $dateTime;
                $this->parameters['referenceNumber'] = $referenceNumber;
                $this->parameters['terminalType'] = $terminalType;
                $this->parameters['terminalId'] = $terminalId;
                $this->parameters['destinationBankCode'] = $destinationBankCode;
                $this->parameters['destinationAccountNumber'] = $destinationAccountNumber;

                if ($type === 'va_inquiry_trf_out') {
                    $this->url = $this->baseUrl.'/virtualAccount/inquiryTransferOut';
                    $this->parameters['hashCode'] = $this->generateHashCodeKey([
                        $accountNumber,
                        $amount,
                        $dateTime,
                        $referenceNumber,
                        $terminalType,
                        $terminalId,
                        $destinationBankCode,
                        $destinationAccountNumber,
                        $this->key,
                    ]);
                } elseif ($type === 'va_posting_trf_out') {
                    $destinationAccountName = $data['destinationAccountName'] ?? '';

                    $this->url = $this->baseUrl.'/virtualAccount/postingTransferOut';
                    $this->parameters['destinationAccountNumber'] = $destinationAccountNumber;
                    $this->parameters['hashCode'] = $this->generateHashCodeKey([
                        $accountNumber,
                        $amount,
                        $dateTime,
                        $referenceNumber,
                        $terminalType,
                        $terminalId,
                        $destinationBankCode,
                        $destinationAccountNumber,
                        $destinationAccountName,
                        $this->key,
                    ]);
                }

                break;
            case 'va_balance_inquiry':
            case 'va_statement_inquiry':
            case 'va_trf_out_status':
                $accountNumber = $data['accountNumber'] ?? '';
                $dateTime = $data['dateTime'] ?? ''; // Format: YmdHis
                $date = $data['date'] ?? ''; // Format: Ymd
                $referenceNumber = $data['referenceNumber'] ?? '';

                $this->contentType = 'application/x-www-form-urlencoded';
                $this->parameters['accountNumber'] = $accountNumber;
                $this->parameters['referenceNumber'] = $referenceNumber;

                if ($type === 'va_balance_inquiry') {
                    $this->url = $this->baseUrl.'/virtualAccount/inquiryBalance';
                    $this->parameters['dateTime'] = $dateTime;
                    $this->parameters['hashCode'] = $this->generateHashCodeKey([
                        $accountNumber,
                        $dateTime,
                        $referenceNumber,
                        $this->key,
                    ]);
                } elseif ($type === 'va_statement_inquiry') {
                    $this->url = $this->baseUrl.'/virtualAccount/inquiryStatement';
                    $this->parameters['date'] = $date;
                    $this->parameters['hashCode'] = $this->generateHashCodeKey([
                        $accountNumber,
                        $date,
                        $referenceNumber,
                        $this->key,
                    ]);
                } elseif ($type === 'va_trf_out_status') {
                    $this->url = $this->baseUrl.'/virtualAccount/checkStatusTransferOut';
                    $this->parameters['date'] = $date;
                    $this->parameters['hashCode'] = $this->generateHashCodeKey([
                        $accountNumber,
                        $date,
                        $referenceNumber,
                        $this->key,
                    ]);
                }

                break;
        }
    }

    public function getRequestParameters(): array
    {
        return $this->parameters;
    }

    public function generateHashCodeKey(array $data): string
    {
        return hash('sha256', implode('', $data));
    }

    public function execute(): array
    {
        try {
            $options = [
                'headers' => ['Content-Type' => $this->contentType],
                'json' => $this->parameters,
            ];

            if ($this->contentType === 'application/x-www-form-urlencoded') {
                unset($options['json']);

                $options['form_params'] = $this->parameters;
            }

//            dd(json_encode($options));

            return HttpClientService::run($this->url, $options, 'POST');
        } catch (HttpClientException $e) {
            return [
                'error' => true,
                'message' => $e->getMessage(),
                'data' => null,
            ];
        }
    }
}
