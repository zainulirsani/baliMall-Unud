<?php

namespace App\Controller\Site;

use App\Controller\PublicController;
use App\Email\BaseMail;
use App\Entity\ProductCategory;
use Dompdf\Dompdf;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\Constraints as Assert;

class StaticPageController extends PublicController
{
    protected $redBox = false;

    public function termsAndConditions()
    {
        return $this->view('@__main__/public/site/static_page/terms_and_conditions.html.twig', [
            'page_title' => 'title.page.terms_and_conditions',
        ]);
    }

    public function merchantTermsAndConditions()
    {
        $repository = $this->getRepository(ProductCategory::class);
        $parents = $repository->getCategoryParents();
        $data = [];

        foreach ($parents as $index => $item) {
            $data[$index]['parent'] = $item['text'];
            $data[$index]['children'] = $repository->getChildrenCategoryData($item['id']) ;
        };


        return $this->view('@__main__/public/site/static_page/merchant_terms_and_conditions.html.twig', [
            'product_category' => $data,
            'page_title' => 'title.page.merchant_terms_and_conditions',
        ]);
    }

    public function privacyPolicy()
    {
        return $this->view('@__main__/public/site/static_page/privacy_policy.html.twig', [
            'page_title' => 'title.page.privacy_policy',
        ]);
    }

    public function contact()
    {
        $request = $this->getRequest();
        $tokenId = 'contact_request';
        $formData = [];
        $errors = [];

        if ($request->isMethod('POST')) {
            $formData = $request->request->all();
            $constraint = new Assert\Collection([
                'name' => [new Assert\NotBlank(), new Assert\Length(['max' => 200])],
                'email' => [new Assert\NotBlank(), new Assert\Email()],
                'phone' => [new Assert\NotBlank(), new Assert\Type('numeric')],
                'message' => [new Assert\NotBlank(), new Assert\Length(['max' => 2000])],
            ]);

            $validator = $this->getValidator();
            $violations = $validator->validate($formData, $constraint);

            if ($violations->count() === 0) {
                $translator = $this->getTranslator();
                /** @var BaseMail $mail */
                $mail = $this->get(BaseMail::class);
                $mail->setMailSubject($translator->trans('message.info.new_contact_request'));
                $mail->setMailTemplate('@__main__/email/contact_us.html.twig');
                $mail->setToAdmin();
                $mail->setMailData($formData);
                $mail->send();

                $this->addFlash(
                    'success',
                    $translator->trans('message.success.contact_request')
                );

                return $this->redirectToRoute('contact');
            }

            // Add error messages into its own array
            foreach ($violations as $key => $error) {
                $errors[$error->getPropertyPath()] = $error->getMessage();
            }
        }

        return $this->view('@__main__/public/site/static_page/contact.html.twig', [
            'page_title' => 'title.page.contact',
            'token_id' => $tokenId,
            'form_data' => $formData,
            'errors' => $errors,
            'red_box' => true,
        ]);
    }

    public function faq()
    {
        return $this->view('@__main__/public/site/static_page/faq.html.twig', [
            'page_title' => 'title.page.faq',
        ]);
    }

    public function beAVendor()
    {
        return $this->view('@__main__/public/site/static_page/be_a_vendor.html.twig', [
            'page_title' => 'title.page.be_a_vendor',
        ]);
    }

    public function tips()
    {
        return $this->view('@__main__/public/site/static_page/tips.html.twig', [
            'page_title' => 'title.page.tips',
        ]);
    }
}
