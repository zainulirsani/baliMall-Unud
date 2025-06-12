<?php

namespace App\Controller\MasterTax;

use App\Controller\AdminController;
use App\Entity\MasterTax;
use App\Repository\MasterTaxRepository;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Annotation\Route;

class AdminMasterTaxController extends AdminController
{
    protected $key = 'mastertax';
    protected $entity = MasterTax::class;

    public function index()
    {
        /** @var MasterTaxRepository $repository */
        $repository = $this->getRepository($this->entity);
        $data       = $repository->findAll();
        return $this->view('@__main__/admin/master_tax/index.html.twig', [
            'tax' => $data 
        ]);
    }

    public function editdata()
    {
        return $this->view('@__main__/admin/master_tax/form.html.twig',[
            'token' => sha1(md5('input_tax'))
        ]);
    }

    public function savedata()
    {
        $req = $this->getRequest()->request;
        if ($req->get('umkm') != null && !empty($req->get('umkm'))) {
            $em  = $this->getEntityManager();
            
            /** @var MasterTaxRepository $repository */
            $repository = $this->getRepository($this->entity);
            $data       = $repository->findOneBy(['umkm_category' => $req->get('umkm')]);
            $data->setPpn($req->get('ppn'));
            $data->setPph($req->get('pph'));
            $em->persist($data);
            $em->flush();
            
        }
        $this->addFlash('success', $this->getTranslator()->trans('message.success.update'));
        return $this->redirectToRoute('admin_mastertax_index');
    }

    public function detail($category)
    {
        /** @var MasterTaxRepository $repository */
        $repository = $this->getRepository($this->entity);
        $data       = $repository->findOneBy(['umkm_category' => $category]);
        if ($data != null) {
            $response['status'] = true;
            $response['ppn'] = $data->getPpn();
            $response['pph'] = $data->getPph();
        } else {
            $response['status'] = false;
        }
        
        return $this->view('', $response, 'json');
    }
}
