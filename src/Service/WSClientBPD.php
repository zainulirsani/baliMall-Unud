<?php

namespace App\Service;

use SoapClient;
use SoapFault;

class WSClientBPD
{
    private $institution;
    private $parameters;

    public function __construct()
    {
        $this->institution = getenv('WS_BPD_INSTITUTION');
        $this->parameters = [
            'username' => getenv('WS_BPD_USERNAME'),
            'password' => getenv('WS_BPD_PASSWORD'),
        ];
    }

    public function echoTest(): array
    {
        return $this->soapCall('ws_echo_test');
    }

    public function billInquiry($id): array
    {
        $this->parameters['instansi'] = $this->institution;
        $this->parameters['noid'] = $id;

        return $this->soapCall('ws_inquiry_tagihan');
    }

    public function billInsertion(array $data): array
    {
        $this->parameters['noid'] = $data['id'] ?? '';
        $this->parameters['nama'] = $data['name'] ?? '';
        $this->parameters['tagihan'] = $data['nominal'] ?? '';
        $this->parameters['instansi'] = $this->institution;
        $this->parameters['ket_1_val'] = $data['note_1'] ?? '';
        $this->parameters['ket_2_val'] = $data['note_2'] ?? '';
        $this->parameters['ket_3_val'] = $data['note_3'] ?? '';
        $this->parameters['ket_4_val'] = $data['note_4'] ?? '';
        $this->parameters['ket_5_val'] = $data['note_5'] ?? '';
        $this->parameters['ket_6_val'] = $data['note_6'] ?? '';
        $this->parameters['ket_7_val'] = $data['note_7'] ?? '';
        $this->parameters['ket_8_val'] = $data['note_8'] ?? '';
        $this->parameters['ket_9_val'] = $data['note_9'] ?? '';
        $this->parameters['ket_10_val'] = $data['note_10'] ?? '';
        $this->parameters['ket_11_val'] = $data['note_11'] ?? '';
        $this->parameters['ket_12_val'] = $data['note_12'] ?? '';
        $this->parameters['ket_13_val'] = $data['note_13'] ?? '';
        $this->parameters['ket_14_val'] = $data['note_14'] ?? '';
        $this->parameters['ket_15_val'] = $data['note_15'] ?? '';
        $this->parameters['ket_16_val'] = $data['note_16'] ?? '';
        $this->parameters['ket_17_val'] = $data['note_17'] ?? '';
        $this->parameters['ket_18_val'] = $data['note_18'] ?? '';
        $this->parameters['ket_19_val'] = $data['note_19'] ?? '';
        $this->parameters['ket_20_val'] = $data['note_20'] ?? '';
        $this->parameters['ket_21_val'] = $data['note_21'] ?? '';
        $this->parameters['ket_22_val'] = $data['note_22'] ?? '';
        $this->parameters['ket_23_val'] = $data['note_23'] ?? '';
        $this->parameters['ket_24_val'] = $data['note_24'] ?? '';
        $this->parameters['ket_25_val'] = $data['note_25'] ?? '';

        return $this->soapCall('ws_tagihan_insert');
    }

    public function billRemovalByInstitution(): array
    {
        $this->parameters['instansi'] = $this->institution;

        return $this->soapCall('ws_tagihan_delete_by_instansi');
    }

    public function billRemovalById($id): array
    {
        $this->parameters['instansi'] = $this->institution;
        $this->parameters['noid'] = $id;

        return $this->soapCall('ws_tagihan_delete_by_id');
    }

    public function billRemovalByRecordId($id, $recordId): array
    {
        $this->parameters['instansi'] = $this->institution;
        $this->parameters['noid'] = $id;
        $this->parameters['recordid'] = $recordId;

        return $this->soapCall('ws_tagihan_delete_by_record_id');
    }

    public function billRemovalByField($field, $value): array
    {
        $this->parameters['instansi'] = $this->institution;
        $this->parameters['field'] = $field;
        $this->parameters['recordvalue'] = $value;

        return $this->soapCall('ws_tagihan_delete_by_record_id');
    }

    public function paymentReport($date): array
    {
        $this->parameters['instansi'] = $this->institution;
        $this->parameters['tanggal'] = $date;

        return $this->soapCall('ws_laporan_payment');
    }

    public function paymentReportDetail($date): array
    {
        $this->parameters['instansi'] = $this->institution;
        $this->parameters['tanggal'] = $date;

        return $this->soapCall('ws_laporan_payment_detail');
    }

    public function paymentReportDetailByRecordId($date, $proofId): array
    {
        $this->parameters['instansi'] = $this->institution;
        $this->parameters['tanggal'] = $date;
        $this->parameters['nobukti'] = $proofId;

        return $this->soapCall('ws_laporan_payment_detail_setelah_no_bukti');
    }

    public function billHistoryById($id): array
    {
        $this->parameters['instansi'] = $this->institution;
        $this->parameters['noid'] = $id;

        return $this->soapCall('ws_tagihan_history_by_id');
    }

    public function bulkUpload($content, $name): array
    {
        $this->parameters['instansi'] = $this->institution;
        $this->parameters['decodecontent'] = $content;
        $this->parameters['namafile'] = $name;

        return $this->soapCall('ws_upload_bulk');
    }

    private function soapCall(string $name): array
    {
        try {
            $client = new SoapClient(getenv('WS_BPD_URL'));
            $result = $client->__soapCall($name, $this->parameters);
            $response = json_decode($result, true);
        } catch (SoapFault $e) {
            $response = [
                'status' => false,
                'code' => '99',
                'message' => $e->getMessage(),
                'data' => null,
            ];
        }

        return $response;
    }
}
