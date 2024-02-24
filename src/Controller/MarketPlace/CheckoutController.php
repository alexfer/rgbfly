<?php

namespace App\Controller\MarketPlace;

use App\Entity\MarketPlace\MarketAddress;
use App\Entity\MarketPlace\MarketCustomer;
use App\Entity\MarketPlace\MarketCustomerOrders;
use App\Entity\MarketPlace\MarketInvoice;
use App\Entity\MarketPlace\MarketOrders;
use App\Entity\MarketPlace\MarketPaymentGateway;
use App\Entity\User;
use App\Form\Type\MarketPlace\AddressType;
use App\Form\Type\MarketPlace\CustomerType;
use App\Helper\MarketPlace\MarketPlaceHelper;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\User\UserInterface;

#[Route('/market-place/checkout')]
class CheckoutController extends AbstractController
{

    /**
     * @param Request $request
     * @param UserInterface|null $user
     * @param UserPasswordHasherInterface $userPasswordHasher
     * @param EntityManagerInterface $em
     * @return Response
     */
    #[Route('/{order}', name: 'app_market_place_order_checkout', methods: ['GET', 'POST'])]
    public function checkout(
        Request                     $request,
        ?UserInterface              $user,
        UserPasswordHasherInterface $userPasswordHasher,
        EntityManagerInterface      $em,
    ): Response
    {
        $session = $request->getSession();

        $order = $em->getRepository(MarketOrders::class)->findOneBy([
            'number' => $request->get('order'),
            'session' => $session->getId(),
            'status' => MarketOrders::STATUS['processing'],
        ]);

        if (!$order) {
            $this->redirectToRoute('app_market_place_order_summary');
        }

        $userCustomer = $em->getRepository(MarketCustomer::class)->findOneBy(['member' => $user]);

        if (!$userCustomer) {
            $customer = new MarketCustomer();
            $form = $this->createForm(CustomerType::class, $customer);
            $address = new MarketAddress();
//            $formAddress = $this->createForm(AddressType::class, $address);
        } else {
//            $form = $this->createForm(CustomerType::class, $userCustomer);
//            $address = $userCustomer->getMember()->getMarketAddresses()->first();
//            $formAddress = $this->createForm(AddressType::class, $address);
        }

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $checkEmail = $em->getRepository(User::class)->findOneBy(['email' => $form->get('email')->getData()]);

            if ($checkEmail) {
                $this->addFlash('danger', 'This email already in use');
                return $this->redirectToRoute('app_market_place_order_checkout', ['order' => $request->get('order')]);
            }
            if (!$userCustomer) {

                $password = substr(base_convert(sha1(uniqid(mt_rand())), 16, 36), 0, 8);
                $session->set('_temp_password', $password);

                $user = new User();

                $user->setEmail($form->get('email')->getData())
                    ->setPassword($userPasswordHasher->hashPassword($user, $password));

                $user->setIp($request->getClientIp())->setRoles([User::ROLE_CUSTOMER]);
                $em->persist($user);
                $em->flush();

                $customer->setMember($user);
                $em->persist($customer);
                $em->flush();

                $customerOrder = $em->getRepository(MarketCustomerOrders::class)->findOneBy(['orders' => $order]);

                $customerOrder->setCustomer($customer);
                $em->persist($customerOrder);
                $em->flush();

                $order->setSession(null)
                    ->setStatus(MarketOrders::STATUS['confirmed']);

                $paymentGateway = $em->getRepository(MarketPaymentGateway::class)->findOneBy([
                    'slug' => key($request->request->all('gateway')),
                ]);

                $invoice = new MarketInvoice();

                $invoice->setOrders($order)
                    ->setPaymentGateway($paymentGateway)
                    ->setNumber(MarketPlaceHelper::slug($order->getId(), 6, 'i'))
                    ->setAmount($order->getTotal())
                    ->setTax(0);

                $em->persist($invoice);
                $em->persist($order);
                $em->flush();
            }

            $session->set('quantity', 0);
            $session->remove('orders');

            return $this->redirectToRoute('app_market_place_order_success', ['order' => $order->getNumber()]);
        }

        return $this->render('market_place/checkout/index.html.twig', [
            'order' => $order,
            'form' => $form,
        ]);
    }

    /**
     * @param Request $request
     * @param EntityManagerInterface $em
     * @return Response
     */
    #[Route('/order-success/{order}', name: 'app_market_place_order_success', methods: ['GET'])]
    public function cashSuccess(
        Request                $request,
        EntityManagerInterface $em,
    ): Response
    {
        $order = $request->get('order');

        return $this->render('market_place/checkout/order_success.html.twig', [
            'order' => $order,
        ]);
    }
}