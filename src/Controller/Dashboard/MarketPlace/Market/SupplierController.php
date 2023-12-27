<?php

namespace App\Controller\Dashboard\MarketPlace\Market;

use App\Entity\MarketPlace\Market;
use App\Entity\MarketPlace\MarketProvider;
use App\Entity\MarketPlace\MarketSupplier;
use App\Form\Type\Dashboard\MarketPlace\ProviderType;
use App\Form\Type\Dashboard\MarketPlace\SupplerType;
use App\Service\MarketPlace\MarketTrait;
use App\Service\Navbar;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Intl\Countries;
use Symfony\Component\Intl\Locale;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route('/dashboard/market-place/supplier')]
class SupplierController extends AbstractController
{
    use Navbar, MarketTrait;

    /**
     * @param Request $request
     * @param UserInterface $user
     * @param EntityManagerInterface $em
     * @return Response
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    #[Route('/{market}', name: 'app_dashboard_market_place_market_supplier')]
    public function index(
        Request                $request,
        UserInterface          $user,
        EntityManagerInterface $em,
    ): Response
    {
        $criteria = $this->criteria($user, ['id' => $request->get('market')], 'owner');
        // TODO: check in future
        $market = $em->getRepository(Market::class)->findOneBy($criteria, ['id' => 'desc']);
        $suppliers = $em->getRepository(MarketSupplier::class)->findBy(['market' => $market], ['id' => 'desc']);

        return $this->render('dashboard/content/market_place/supplier/index.html.twig', $this->build($user) + [
                'market' => $market,
                'suppliers' => $suppliers,
                'countries' => Countries::getNames(Locale::getDefault()),
            ]);
    }

    /**
     * @param Request $request
     * @param UserInterface $user
     * @param EntityManagerInterface $em
     * @param TranslatorInterface $translator
     * @return Response
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    #[Route('/create/{market}', name: 'app_dashboard_market_place_create_supplier', methods: ['GET', 'POST'])]
    public function create(
        Request                $request,
        UserInterface          $user,
        EntityManagerInterface $em,
        TranslatorInterface    $translator,
    ): Response
    {
        $market = $this->market($request, $user, $em);
        $supplier = new MarketSupplier();

        $form = $this->createForm(SupplerType::class, $supplier);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $supplier->setMarket($market);
            $em->persist($supplier);
            $em->flush();

            $this->addFlash('success', json_encode(['message' => $translator->trans('user.entry.created')]));
            return $this->redirectToRoute('app_dashboard_market_place_edit_supplier', [
                'market' => $request->get('market'),
                'id' => $supplier->getId(),
            ]);
        }

        return $this->render('dashboard/content/market_place/supplier/_form.html.twig', $this->build($user) + [
                'form' => $form,
            ]);
    }

    #[Route('/edit/{market}/{id}', name: 'app_dashboard_market_place_edit_supplier', methods: ['GET', 'POST'])]
    public function edit(
        Request                $request,
        UserInterface          $user,
        MarketSupplier         $supplier,
        EntityManagerInterface $em,
        TranslatorInterface    $translator,
    ): Response
    {
        $form = $this->createForm(SupplerType::class, $supplier);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $em->persist($supplier);
            $em->flush();

            $this->addFlash('success', json_encode(['message' => $translator->trans('user.entry.updated')]));
            return $this->redirectToRoute('app_dashboard_market_place_edit_supplier', [
                'market' => $request->get('market'),
                'id' => $supplier->getId(),
            ]);
        }

        return $this->render('dashboard/content/market_place/supplier/_form.html.twig', $this->build($user) + [
                'form' => $form,
            ]);
    }
}