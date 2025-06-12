<?php

namespace App\Controller\Setting;

use App\Controller\AdminController;
use App\Entity\Setting;
use App\EventListener\SettingEntityListener;
use App\Helper\StaticHelper;
use Psr\Cache\InvalidArgumentException;
use Symfony\Component\EventDispatcher\GenericEvent;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\KernelInterface;

class AdminSettingController extends AdminController
{
    protected $key = 'setting';
    protected $entity = Setting::class;
    protected $authorizedRole = 'ROLE_SUPER_ADMIN';

    public function index()
    {
        $repository = $this->getRepository($this->entity);
        $settings = $repository->findAll();

        return $this->view('@__main__/admin/setting/index.html.twig', [
            'settings' => $settings,
        ]);
    }

    public function save()
    {
        $this->isAjaxRequest('POST');

        $request = $this->getRequest();
        $name = $request->request->get('name', null);
        $slug = $request->request->get('slug', null);
        $slug = StaticHelper::createSlug($slug, '_');
        $description = $request->request->get('description', null);
        $type = $request->request->get('type', null);
        $value = $request->request->get('value', null);
        $options = $request->request->get('options', null);
        $optionsData = json_decode($options, true);
        $options = (is_array($optionsData)) ? json_encode($optionsData) : null;

        if ($type === 'image') {
            $value = $request->request->get('image_value', null);
            $value = ltrim($value, '/');
        } elseif ($type === 'select_multiple' || $type === 'checkbox') {
            $value = json_encode($value);
        }

        $setting = new Setting();
        $setting->setName(filter_var($name, FILTER_SANITIZE_STRING));
        $setting->setSlug(filter_var($slug, FILTER_SANITIZE_STRING));
        $setting->setDescription(filter_var($description, FILTER_SANITIZE_STRING));
        $setting->setType(filter_var($type, FILTER_SANITIZE_STRING));
        $setting->setDefaultValue(filter_var($value, FILTER_SANITIZE_STRING));
        $setting->setOptions($options);

        $validator = $this->getValidator();
        $validations = $validator->validate($setting);
        $errors = [];
        $response = ['status' => true];

        if (count($validations) === 0) {
            $em = $this->getEntityManager();
            $em->persist($setting);
            $em->flush();

            try {
                $this->getCache()->deleteItem(getenv('APP_SETTINGS_CACHE'));
            } catch (InvalidArgumentException $e) {
            }

            $request->request->remove('_csrf_token');
            $request->request->remove('_csrf_token_id');

            $response['data'] = $request->request->all();
        } else {
            foreach ($validations as $key => $validation) {
                $errors[$validation->getPropertyPath()] = $validation->getMessage();
            }

            $response['status'] = false;
            $response['errors'] = $errors;
        }

        return $this->view('', $response, 'json');
    }

    public function updateSetting(KernelInterface $kernel): RedirectResponse
    {
        $request = $this->getRequest();
        $post = $request->request->all();
        $repository = $this->getRepository($this->entity);
        $settings = $repository->findAll();
        $em = $this->getEntityManager();
        $updated = false;
        $updatedEntity = [];

        foreach ($settings as $setting) {
            $slug = $setting->getSlug();
            $oldValue = $setting->getDefaultValue();

            if (isset($post[$slug])) {
                switch ($setting->getType()) {
                    case 'image':
                        $newValue = ltrim($post[$slug], '/');
                        break;
                    case 'checkbox':
                    case 'select_multiple':
                        $newValue = (is_array($post[$slug]) && count($post[$slug]) > 0) ? json_encode($post[$slug]) : '';
                        break;
                    case 'password':
                        $newValue = $post[$slug];
                        break;
                    default:
                        $newValue = filter_var($post[$slug], FILTER_SANITIZE_STRING);
                        break;
                }

                if ($oldValue !== $newValue) {
                    $setting->setDefaultValue($newValue);

                    $em->persist($setting);
                    $em->flush();

                    $updated = true;
                    $updatedEntity[] = $setting;
                }
            }
        }

        if ($updated) {
            $this->appGenericEventDispatcher(new GenericEvent($updatedEntity), 'app.setting_update', new SettingEntityListener());

            try {
                $this->getCache()->deleteItem(getenv('APP_SETTINGS_CACHE'));
            } catch (InvalidArgumentException $e) {
            }

            $this->addFlash(
                'success',
                $this->getTranslator()->trans('message.success.setting_updated')
            );
        }

        return $this->redirectToRoute($this->getAppRoute());
    }
}
