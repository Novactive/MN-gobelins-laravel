<?php

namespace App\Controller;

use App\Classe\Cart;
use App\Entity\Order;
use App\Entity\OrderDetails;
use App\Entity\User;
use App\Form\OrderType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class OrderController extends AbstractController
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * @Route("/order", name="order")
     */
    public function index(Cart $cart, Request $request): Response
    {
        $order = new Order();

        $form = $this->createForm(OrderType::class, $order, [
            'user' => $this->getUser()
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            
            $user = $this->entityManager->getRepository(User::class)->find($request->get('order')['user']);
            $order->setUser($user);
            // remise
            $discount = $request->get('order')['discount'];
            $order->setDiscount($discount);
            // reglement (payer)
            $pay = $request->get('order')['pay'];
            $order->setPay($pay);

            $order->setAmount(0);
            $order->setNetToPay(0);
            $order->setRemainderToPay(0);

            $amount = 0;

            $this->entityManager->persist($order);

            foreach ($cart->getFull() as $product) {
                $orderDetails = new OrderDetails();
                $orderDetails->setMyOrder($order);
                $orderDetails->setProduct($product['product']->getName());
                $orderDetails->setQuantity($product['quantity']);
                $orderDetails->setPrice($product['product']->getPrice());
                $orderDetails->setTotal($product['product']->getPrice() * $product['quantity']);
                $orderDetails->setUser($this->getUser());

                $amount = $amount + ($product['product']->getPrice() * $product['quantity']);
                $this->entityManager->persist($orderDetails);
            }

            $this->entityManager->flush();

            // update order
            $orderToUpdate = $this->entityManager->getRepository(Order::class)->find($order->getId());

            // total avant remise
            $orderToUpdate->setAmount($amount);
            // net à pay (total arès remise)
            $netToPay = $amount-$discount;
            $orderToUpdate->setNetToPay($netToPay);

            // reste à payer
            $remainderToPay = $netToPay-$pay;
            $orderToUpdate->setRemainderToPay($remainderToPay);

            $this->entityManager->persist($orderToUpdate);

            $this->entityManager->flush();

            $cart->remove();


            return $this->redirectToRoute('cart');

        }


        return $this->render('order/index.html.twig', [
            'form' => $form->createView(),
            'cart' => $cart->getFull()
        ]);

    }
}
