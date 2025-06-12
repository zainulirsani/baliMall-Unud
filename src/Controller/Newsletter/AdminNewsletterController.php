<?php

namespace App\Controller\Newsletter;

use App\Controller\AdminController;
use App\Entity\Newsletter;
use App\Repository\NewsletterRepository;
use Exception;
use Pagerfanta\Adapter\DoctrineORMAdapter;
use Pagerfanta\Pagerfanta;
use Pagerfanta\View\TwitterBootstrap3View;

class AdminNewsletterController extends AdminController
{
    protected $key = 'newsletter';
    protected $entity = Newsletter::class;

    public function index()
    {
        /** @var NewsletterRepository $repository */
        $repository = $this->getRepository($this->entity);
        $request = $this->getRequest();
        $page = abs($request->query->get('page', '1'));
        $page = $page > 0 ? $page : 1;
        $keywords = $request->query->get('keywords', null);
        $limit = $this->getParameter('result_per_page');
        $offset = $page > 1 ? ($page - 1) * $limit : 0;
        $parameters = [
            'limit' => $limit,
            'offset' => $offset,
            'order_by' => 'n.id',
            'sort_by' => 'DESC',
        ];

        if (!empty($keywords)) {
            $parameters['keywords'] = filter_var($keywords, FILTER_SANITIZE_STRING);
        }

        try {
            $adapter = new DoctrineORMAdapter($repository->getPaginatedResult($parameters));
            $pagination = New Pagerfanta($adapter);
            $pagination
                ->setMaxPerPage($limit)
                ->setCurrentPage($page)
            ;

            $view = new TwitterBootstrap3View();
            $options = ['proximity' => 3];
            $html = $view->render($pagination, $this->routeGenerator($parameters), $options);
            $subscribers = $adapter->getQuery()->getArrayResult();
        } catch (Exception $e) {
            $subscribers = [];
            $pagination = $html = null;
        }

        return $this->view('@__main__/admin/newsletter/index.html.twig', [
            'subscribers' => $subscribers,
            'pagination' => $pagination,
            'parameters' => $parameters,
            'html' => $html,
        ]);
    }

    private function routeGenerator(array $parameters = []): callable
    {
        return function ($page) use ($parameters) {
            $query = ['page' => $page];

            if (isset($parameters['keywords'])) {
                $query['keywords'] = $parameters['keywords'];
            }

            return $this->get('router')->generate($this->getAppRoute(), $query);
        };
    }
}
