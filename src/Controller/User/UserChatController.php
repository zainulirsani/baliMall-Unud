<?php

namespace App\Controller\User;

use App\Controller\PublicController;
use App\Entity\Chat;
use App\Entity\ChatMessage;
use App\Entity\Order;
use App\Entity\OrderComplaint;
use App\Entity\Store;
use App\Entity\User;
use App\Repository\ChatMessageRepository;
use App\Repository\ChatRepository;
use App\Repository\OrderRepository;
use App\Repository\StoreRepository;
use App\Repository\UserRepository;
use App\Service\BreadcrumbService;
use App\Utility\CustomPaginationTemplate;
use Exception;
use Hashids\Hashids;
use Pagerfanta\Adapter\DoctrineORMAdapter;
use Pagerfanta\Pagerfanta;
use Pagerfanta\View\DefaultView;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class UserChatController extends PublicController
{
    private $allowedRoles = ['ROLE_USER_SELLER', 'ROLE_USER_GOVERNMENT'];

    public function index()
    {
        $this->deniedManyAccess($this->allowedRoles);

        /** @var User $user */
        $user = $this->getUser();
        /** @var ChatRepository $repository */
        $repository = $this->getRepository(Chat::class);
        $request = $this->getRequest();
        $page = abs($request->query->get('page', '1'));
        $keywords = $request->query->get('keywords', null);
        $limit = $this->getParameter('result_per_page');
        $offset = $page > 1 ? ($page - 1) * $limit : 0;
        $parameters = [
            'limit' => $limit,
            'offset' => $offset,
            'initiator' => $user->getId(),
            'type' => 'direct',
            'order_by' => 'c.createdAt',
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

            $view = new DefaultView(new CustomPaginationTemplate());
            $options = ['proximity' => 3];
            $html = $view->render($pagination, $this->routeGenerator($parameters), $options);
            $messages = $adapter->getQuery()->getScalarResult();
        } catch (Exception $e) {
            $messages = [];
            $pagination = $html = null;
        }

        foreach ($messages as &$message) {
            $message['recent_chat'] = $repository->getRecentChat($message['c_room'], $message['c_participant']);
        }

        unset($message);

        BreadcrumbService::add(['label' => $this->getTranslation('label.messages')]);

        return $this->view('@__main__/public/user/chat/index.html.twig', [
            'messages' => $messages,
            'pagination' => $pagination,
            'parameters' => $parameters,
            'html' => $html,
        ]);
    }

    public function init(): RedirectResponse
    {
        $this->deniedAccess('ROLE_USER_GOVERNMENT');

        $request = $this->getRequest();
        $slug = $request->request->get('slug', null);
        $order = abs($request->request->get('order', '0'));
        $product = abs($request->request->get('product', '0'));

        if (empty($slug)) {
            throw new NotFoundHttpException(sprintf('Unable to init chat with store "%s"', $slug));
        }

        /** @var StoreRepository $repository */
        $repository = $this->getRepository(Store::class);
        /** @var Store $store */
        $store = $repository->findOneBy([
            'slug' => $slug,
            'isActive' => true,
            'isVerified' => true,
        ]);

        if (empty($store)) {
            throw new NotFoundHttpException(sprintf('Cannot find store "%s"', $slug));
        }

        /** @var User $owner */
        $owner = $store->getUser();

        if (!$owner instanceof User) {
            throw new NotFoundHttpException(sprintf('Store "%s" does not seem to have an owner', $slug));
        }

        if (!$owner->getIsActive() || $owner->getIsDeleted()) {
            throw new NotFoundHttpException(sprintf('Store owner of "%s" is either disabled or deleted!', $slug));
        }

        /** @var User $user */
        $user = $this->getUser();
        $initiator = $user->getId();
        $participant = $owner->getId();
        $parameters = [
            'initiator' => $initiator,
            'participant' => $participant,
            'type' => 'direct',
        ];

        if ($order > 0 && $product > 0) {
            $parameters['type'] = 'complain';
            $parameters['orderId'] = $order;
        }

        /** @var ChatRepository $repository */
        $repository = $this->getRepository(Chat::class);
        /** @var Chat $chat */
        $chat = $repository->findOneBy($parameters);
        $em = $this->getEntityManager();

        if (!$chat instanceof Chat) {
            $encoder = new Hashids(Chat::class, 16);
            $encodedId = $parameters['type'] === 'complain' ? [$initiator, $participant, $order, $product] : [$initiator, $participant];
            $roomId = $encoder->encode($encodedId);

            $chatInitiator = new Chat();
            $chatInitiator->setRoom($roomId);
            $chatInitiator->setInitiator($initiator);
            $chatInitiator->setParticipant($participant);

            $chatParticipant = new Chat();
            $chatParticipant->setRoom($roomId);
            $chatParticipant->setInitiator($participant);
            $chatParticipant->setParticipant($initiator);

            if ($parameters['type'] === 'complain') {
                $chatInitiator->setType($parameters['type']);
                $chatInitiator->setOrderId($order);

                $chatParticipant->setType($parameters['type']);
                $chatParticipant->setOrderId($order);

                /** @var OrderRepository $repository */
                $repository = $this->getRepository(Order::class);
                /** @var Order $orderData */
                $orderData = $repository->find($order);
                $orderData->setChatRoomId($roomId);

                $em->persist($orderData);
            }

            $em->persist($chatInitiator);
            $em->persist($chatParticipant);
            $em->flush();
        } else {
            $roomId = $chat->getRoom();
            $chatRooms = $repository->findBy(['room' => $roomId]);

            foreach ($chatRooms as $chatRoom) {
                if ($parameters['type'] === 'complain' && $chatRoom->getOrderId() === 0) {
                    $chatRoom->setType($parameters['type']);
                    $chatRoom->setOrderId($order);

                    $em->persist($chatRoom);
                    $em->flush();
                }
            }
        }

        return $this->redirectToRoute('user_chat_detail', ['room' => $roomId]);
    }

    public function submit()
    {
        $this->deniedManyAccess($this->allowedRoles);
        $this->isAjaxRequest('POST');

        $response = ['status' => false];
        $request = $this->getRequest();
        $initiator = abs($request->request->get('id', '0'));
        $room = $request->request->get('room', 'invalid');
        $message = $request->request->get('message', null);
        /** @var Chat $chat */
        $chat = $this->getChatEntity($room, $initiator);

        if ($chat instanceof Chat && !empty($message)) {
            if ($chat->getOrderId() > 0 && $chat->getType() === 'complain') {
                $orderStatus = $this->checkB2GOrderStatus($chat->getOrderId());

                if ($orderStatus === 'invalid') {
                    return $this->view('', $response, 'json');
                }
            }

            $chatMessage = new ChatMessage();
            $chatMessage->setRoom($room);
            $chatMessage->setSender($initiator);
            $chatMessage->setRecipient($chat->getParticipant());
            $chatMessage->setMessage(filter_var($message, FILTER_SANITIZE_STRING));

            $em = $this->getEntityManager();
            $em->persist($chatMessage);
            $em->flush();

            /** @var User $user */
            $user = $this->getUser();

            $response['status'] = true;
            $response['id'] = $chatMessage->getId();
            $response['message'] = $chatMessage->getMessage();
            $response['name'] = trim($user->getFirstName().' '.$user->getLastName());
            $response['ts'] = $chatMessage->getCreatedAt()->format('Y-m-d H:i:s');
            $response['formatted'] = $chatMessage->getCreatedAt()->format('d F Y H:i');
        }

        return $this->view('', $response, 'json');
    }

    public function delete()
    {
        $this->isAjaxRequest('POST');

        $response = ['status' => false];

        return $this->view('', $response, 'json');
    }

    public function detail($room)
    {
        $this->deniedManyAccess($this->allowedRoles);

        /** @var User $user */
        $user = $this->getUser();
        /** @var Chat $chat */
        $chat = $this->getChatEntity($room, $user->getId());

        if (!$chat instanceof Chat) {
            throw new NotFoundHttpException(sprintf('Chat room "%s" is not available!', $room));
        }

        /** @var UserRepository $repository */
        $repository = $this->getRepository(User::class);
        /** @var User $recipient */
        $recipient = $repository->find($chat->getParticipant());
        $recipientName = !empty($recipient) ? $recipient->getFirstName().' '.$recipient->getLastName() : '';

        /** @var ChatMessageRepository $repository */
        $repository = $this->getRepository(ChatMessage::class);
        $messages = $repository->getChatMessages($room);
        $orderStatus = 'valid';

        if ($chat->getOrderId() > 0 && $chat->getType() === 'complain') {
            $orderStatus = $this->checkB2GOrderStatus($chat->getOrderId());

            /** @var OrderRepository $repository */
            $repository = $this->getRepository(Order::class);
            /** @var Order $orderData */
            $orderData = $repository->find($chat->getOrderId());

            if (empty($orderData->getChatRoomId())) {
                $orderData->setChatRoomId($room);

                $em = $this->getEntityManager();
                $em->persist($orderData);
                $em->flush();
            }
        }

        BreadcrumbService::add(
            ['label' => $this->getTranslation('label.messages'), 'href' => $this->get('router')->generate('user_chat_index')],
            ['label' => $this->getTranslation('label.detail')]
        );

        return $this->view('@__main__/public/user/chat/detail.html.twig', [
            'room' => $room,
            'messages' => $messages,
            'recipient_name' => $recipientName,
            'order_status' => $orderStatus,
        ]);
    }

    public function fetch($room)
    {
        $this->isAjaxRequest('POST');

        $response = ['status' => false];
        $request = $this->getRequest();
        $initiator = abs($request->request->get('id', '0'));
        $timestamp = $request->request->get('ts', null);
        /** @var Chat $chat */
        $chat = $this->getChatEntity($room, $initiator);

        if ($chat instanceof Chat) {
            /** @var ChatMessageRepository $repository */
            $repository = $this->getRepository(ChatMessage::class);
            $messages = $repository->fetchReplies($room, $chat->getParticipant(), $timestamp);

            foreach ($messages as &$message) {
                $message['ts'] = $message['cm_createdAt']->format('Y-m-d H:i:s');
                $message['formatted'] = $message['cm_createdAt']->format('d F Y H:i');
            }

            unset($message);

            $response['status'] = true;
            $response['messages'] = $messages;
        }

        return $this->view('', $response, 'json');
    }

    private function getChatEntity(string $room, int $initiator)
    {
        /** @var ChatRepository $repository */
        $repository = $this->getRepository(Chat::class);

        return $repository->findOneBy([
            'initiator' => $initiator,
            'room' => $room,
        ]);
    }

    private function routeGenerator(array $parameters = []): callable
    {
        return function ($page) use ($parameters) {
            $query = ['page' => $page];

            if (isset($parameters['keywords'])) {
                $query['keywords'] = $parameters['keywords'];
            }

            return $this->get('router')->generate('user_chat_index', $query);
        };
    }

    private function checkB2GOrderStatus(int $orderId): string
    {
        /** @var Order $order */
        $order = $this->getRepository(Order::class)->find($orderId);

        if ($order instanceof Order && $order->getIsB2gTransaction()) {
            if ($order->getStatus() === 'paid') {
                return 'invalid';
            }

            /** @var OrderComplaint $hasComplain */
            $hasComplain = $order->getComplaint();

            if (!empty($hasComplain) && $hasComplain->getIsResolved()) {
                return 'invalid';
            }
        }

        return 'valid';
    }
}
